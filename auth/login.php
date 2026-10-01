<?php
// ========== SEGURANÇA DE SESSÃO ==========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
session_start();

// ========== HEADERS DE SEGURANÇA ==========
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Se já estiver logado, redireciona
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header('Location: ../index.php');
    exit;
}

$erro = '';

// ========== CONFIGURAÇÃO ==========
require_once __DIR__ . '/../config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erro de conexão com banco: " . $e->getMessage());
    die('Falha na conexão com o banco de dados.');
}

// ========== CSRF TOKEN ==========
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ========== FUNÇÕES AUXILIARES (reutilizadas do registro) ==========
function verificarRateLimit($pdo, $ip)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tentativas_registro WHERE ip = ? AND momento > NOW() - INTERVAL 15 MINUTE");
    $stmt->execute([$ip]);
    return ($stmt->fetchColumn() < 5);
}

function registrarTentativa($pdo, $ip)
{
    $stmt = $pdo->prepare("INSERT INTO tentativas_registro (ip, momento) VALUES (?, NOW())");
    $stmt->execute([$ip]);
}

function verificarBloqueio($pdo, $ip)
{
    $stmt = $pdo->prepare("SELECT bloqueado_ate FROM bloqueios_ip WHERE ip = ? AND bloqueado_ate > NOW()");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn() !== false;
}

function registrarBloqueio($pdo, $ip, $minutos)
{
    $stmt = $pdo->prepare("INSERT INTO bloqueios_ip (ip, bloqueado_ate, tentativas) 
                           VALUES (?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 1)
                           ON DUPLICATE KEY UPDATE 
                           bloqueado_ate = DATE_ADD(NOW(), INTERVAL ? MINUTE), 
                           tentativas = tentativas + 1");
    $stmt->execute([$ip, $minutos, $minutos]);
}

function logEvento($mensagem)
{
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . '/registro.log',
        date('Y-m-d H:i:s') . ' - ' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . ' - ' . $mensagem . PHP_EOL,
        FILE_APPEND
    );
}

// ========== PROCESSAMENTO DO LOGIN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 1. Verifica CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = 'Token de segurança inválido.';
        logEvento('CSRF inválido - login');
    }
    // 2. Verifica Honeypot
    elseif (!empty($_POST['honeypot'])) {
        $erro = 'Erro de validação.';
        logEvento('Honeypot preenchido – possível bot (login)');
    }
    // 3. Verifica bloqueio ativo
    elseif (verificarBloqueio($pdo, $ip)) {
        $erro = 'Seu IP está temporariamente bloqueado. Tente mais tarde.';
        logEvento('Tentativa de login com IP bloqueado');
    }
    // 4. Verifica rate limit
    elseif (!verificarRateLimit($pdo, $ip)) {
        registrarBloqueio($pdo, $ip, 15);
        $erro = 'Muitas tentativas. Aguarde 15 minutos.';
        logEvento('Rate limit excedido – login bloqueado 15 min');
    } else {
        $username = trim($_POST['username'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (empty($username) || empty($senha)) {
            $erro = 'Preencha todos os campos.';
            registrarTentativa($pdo, $ip);
        } else {
            // Busca o usuário (apenas por nome para evitar enumeração)
            $stmt = $pdo->prepare("SELECT id, nome, email, senha, token, email_verificado FROM usuarios WHERE nome = ?");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica credenciais (tempo constante via password_verify)
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Verifica se a conta está ativada
                if ($usuario['email_verificado'] == 0) {
                    $erro = 'Esta conta ainda não foi ativada. Verifique seu e-mail (inclusive spam).';
                    logEvento('Tentativa de login com conta não ativada: ' . $username);
                    registrarTentativa($pdo, $ip);
                } else {
                    // Login bem-sucedido – regenera sessão
                    session_regenerate_id(true);
                    $_SESSION['logado'] = true;
                    $_SESSION['user_token'] = $usuario['token'];
                    $_SESSION['user_name'] = $usuario['nome'];
                    $_SESSION['user_username'] = $usuario['nome'];
                    $_SESSION['user_id'] = $usuario['id'];

                    logEvento('Login bem-sucedido: ' . $username . ' (ID: ' . $usuario['id'] . ')');

                    // Remove tentativas anteriores do IP (opcional)
                    // $pdo->prepare("DELETE FROM tentativas_registro WHERE ip = ?")->execute([$ip]);

                    // Redireciona com JavaScript (ou header)
                    echo '<!DOCTYPE html>
                    <html>
                    <head>
                        <script>
                            localStorage.setItem("user_token", "' . $usuario['token'] . '");
                            localStorage.setItem("user_name", "' . $usuario['nome'] . '");
                            window.location.href = "../index.php";
                        </script>
                    </head>
                    <body></body>
                    </html>';
                    exit;
                }
            } else {
                // Mensagem genérica para evitar enumeração
                $erro = 'Credenciais inválidas.';
                logEvento('Falha de login: ' . $username);
                registrarTentativa($pdo, $ip);

                // Escalona bloqueio se houver muitas tentativas
                $stmt = $pdo->prepare("SELECT tentativas FROM bloqueios_ip WHERE ip = ? AND bloqueado_ate > NOW()");
                $stmt->execute([$ip]);
                $tentativas_bloqueio = $stmt->fetchColumn();
                if ($tentativas_bloqueio !== false) {
                    if ($tentativas_bloqueio >= 20) {
                        registrarBloqueio($pdo, $ip, 1440); // 24h
                    } elseif ($tentativas_bloqueio >= 10) {
                        registrarBloqueio($pdo, $ip, 60);   // 1h
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Constell</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ===== TODO O SEU CSS ORIGINAL (mantido) ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            cursor: none !important;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d1117;
            font-family: 'Share Tech Mono', monospace;
            padding: 20px;
            overflow: hidden;
        }

        input,
        input:focus,
        input:hover,
        input:active,
        textarea,
        textarea:focus,
        button,
        button:focus,
        a,
        a:hover {
            cursor: none !important;
        }

        .custom-cursor {
            position: fixed;
            pointer-events: none;
            z-index: 9999;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1.5px solid rgba(200, 138, 74, 0.5);
            background: rgba(200, 138, 74, 0.04);
            transform: translate(-50%, -50%);
            transition: width 0.2s, height 0.2s, border-color 0.2s;
            box-shadow: 0 0 20px rgba(200, 138, 74, 0.05);
            backdrop-filter: blur(1px);
        }

        .custom-cursor .dot {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: rgba(200, 138, 74, 0.6);
            box-shadow: 0 0 8px rgba(200, 138, 74, 0.2);
        }

        .login-wrapper {
            width: 100%;
            max-width: 1400px;
            height: 90vh;
            max-height: 850px;
            display: flex;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            position: relative;
            background: #0d1117;
            animation: fadeUp 0.7s ease-out;
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background: #0d1117;
            pointer-events: none;
            border-radius: 5px;
        }

        .login-image {
            flex: 1;
            background-color: #0d1117;
            background-image: url('../assets/imgs/SelfMadeMan.png');
            background-size: auto 110%;
            background-position: 0% 35%;
            background-repeat: no-repeat;
            position: relative;
            min-height: 100%;
            will-change: background-position;
            transition: none;
            z-index: 1;
            overflow: hidden;
            mask-image: linear-gradient(to right, #000 65%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, #000 65%, transparent 100%);
            mask-composite: add;
            -webkit-mask-composite: add;
        }

        .login-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right,
                    transparent 0%,
                    transparent 25%,
                    rgba(13, 17, 23, 0.3) 45%,
                    rgba(13, 17, 23, 0.7) 65%,
                    #0d1117 95%,
                    #0d1117 100%);
            pointer-events: none;
            z-index: 1;
            border-radius: 5px;
        }

        .login-card {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 500px;
            max-width: 50%;
            padding: 52px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(13, 17, 23, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: -8px 0 40px rgba(0, 0, 0, 0.6);
            z-index: 2;
            animation: cardSlideIn 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardSlideIn {
            0% {
                opacity: 0;
                transform: translateX(40px) scale(0.96);
            }

            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .login-card .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 5px;
            background: linear-gradient(135deg, #c88a4a, #a86a2a);
            color: #fff;
            font-size: 28px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(200, 138, 74, 0.25);
        }

        .login-card h1 {
            font-size: 32px;
            font-weight: 600;
            color: #f0f6fc;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
            font-family: 'Share Tech Mono', monospace;
        }

        .login-card .subtitle {
            font-size: 15px;
            color: #8b949e;
            font-weight: 400;
            margin-bottom: 32px;
            font-family: 'Share Tech Mono', monospace;
        }

        .login-card .subtitle span {
            color: #c88a4a;
            font-weight: 500;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .input-group {
            position: relative;
            opacity: 0;
            transform: translateY(10px);
            animation: slideUp 0.5s ease forwards;
        }

        .input-group:nth-child(1) {
            animation-delay: 0.15s;
        }

        .input-group:nth-child(2) {
            animation-delay: 0.25s;
        }

        .input-group:nth-child(3) {
            animation-delay: 0.35s;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6e7681;
            font-size: 18px;
            pointer-events: none;
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 15px 16px 15px 48px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid #30363d;
            border-radius: 5px;
            color: #f0f6fc;
            font-size: 15px;
            font-family: 'Share Tech Mono', monospace;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s, background 0.3s, transform 0.2s;
        }

        .input-group input:hover {
            border-color: rgba(200, 138, 74, 0.3);
            background: rgba(255, 255, 255, 0.06);
        }

        .input-group input::placeholder {
            color: #6e7681;
            font-weight: 400;
            font-size: 14px;
            transition: color 0.3s;
        }

        .input-group input:focus::placeholder {
            color: transparent;
        }

        .input-group input:focus {
            border-color: #c88a4a;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 0 3px rgba(200, 138, 74, 0.15);
            transform: scale(1.01);
        }

        .input-group input:focus~.input-icon {
            color: #c88a4a;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6e7681;
            cursor: pointer !important;
            font-size: 18px;
            transition: color 0.3s;
            z-index: 3;
        }

        .toggle-password:hover {
            color: #c88a4a;
        }

        .btn-login {
            margin-top: 4px;
            padding: 15px;
            background: linear-gradient(135deg, #c88a4a, #a86a2a);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Share Tech Mono', monospace;
            transition: transform 0.2s ease, box-shadow 0.3s ease, opacity 0.3s;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 12px;
            padding: 2px;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(200, 138, 74, 0.35);
        }

        .btn-login:active:not(:disabled) {
            transform: scale(0.97);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }

        @keyframes rippleAnim {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .spinner-icon {
            display: inline-block;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .error-message {
            color: #f85149;
            font-size: 13px;
            text-align: center;
            min-height: 24px;
            margin-top: 4px;
            font-weight: 450;
            font-family: 'Share Tech Mono', monospace;
        }

        .forgot-link {
            text-align: right;
            margin-top: -6px;
        }

        .forgot-link a {
            color: #6e7681;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
            font-family: 'Share Tech Mono', monospace;
        }

        .forgot-link a:hover {
            color: #c88a4a;
            text-decoration: underline;
        }

        .forgot-link i {
            margin-right: 4px;
            opacity: 0.5;
        }

        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #21262d;
        }

        .login-footer .register-link {
            font-size: 14px;
            color: #8b949e;
            font-family: 'Share Tech Mono', monospace;
        }

        .login-footer .register-link a {
            color: #c88a4a;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-footer .register-link a:hover {
            color: #d99a5a;
            text-decoration: underline;
        }

        .login-footer .meta {
            font-size: 12px;
            color: #484f58;
            letter-spacing: 0.02em;
            font-family: 'Share Tech Mono', monospace;
        }

        .login-footer .meta i {
            color: #c88a4a;
            opacity: 0.6;
        }

        @media (max-width: 968px) {
            .login-wrapper {
                flex-direction: column;
                height: auto;
                max-height: none;
                border-radius: 24px;
            }

            .login-image {
                flex: 0 0 300px;
                background-position: center 30%;
                mask-image: none !important;
                -webkit-mask-image: none !important;
                background-size: cover;
            }

            .login-image::after {
                background: linear-gradient(to bottom, transparent, rgba(13, 17, 23, 0.9) 80%);
            }

            .login-card {
                position: relative;
                width: 100%;
                max-width: 100%;
                padding: 32px 28px;
                background: rgba(13, 17, 23, 0.95);
                backdrop-filter: blur(20px);
                border-left: none;
                border-top: 1px solid rgba(48, 54, 61, 0.3);
                box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.6);
                animation: none;
            }

            .custom-cursor {
                display: none;
            }

            body,
            * {
                cursor: auto !important;
            }

            .login-wrapper::before {
                opacity: 0.5;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 24px 20px;
            }

            .login-card h1 {
                font-size: 24px;
            }

            .login-image {
                flex: 0 0 200px;
            }

            .btn-login {
                padding: 12px;
                font-size: 15px;
            }

            .login-footer {
                flex-direction: column;
                gap: 12px;
                align-items: center;
            }

            .custom-cursor {
                display: none;
            }

            body,
            * {
                cursor: auto !important;
            }

            .login-wrapper::before {
                opacity: 0.5;
            }
        }
    </style>
</head>

<body>

    <div class="custom-cursor" id="customCursor">
        <div class="dot"></div>
    </div>

    <div class="login-wrapper">

        <div class="login-image" id="parallaxImage"></div>

        <div class="login-card">
            <div class="brand-icon">
                <i class="bi bi-boxes"></i>
            </div>
            <h1>Constell</h1>
            <p class="subtitle">
                workspace <span>•</span> faça login para acessar
            </p>

            <form class="login-form" method="post" id="loginForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <!-- HONEYPOT -->
                <div style="position: absolute; left: -9999px; top: -9999px;">
                    <input type="text" name="honeypot" id="honeypot" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="input-group">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="username" id="username" placeholder="Nome de usuário" required autofocus />
                </div>

                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="senha" id="senha" placeholder="Senha" required />
                    <i class="bi bi-eye-slash toggle-password" id="toggleSenha"></i>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span id="btnText">Entrar</span>
                    <i class="bi bi-arrow-right" id="btnIcon" style="font-size: 18px;"></i>
                </button>

                <div class="forgot-link">
                    <i class="bi bi-key"></i>
                    <a href="recuperar.php">Esqueceu a senha?</a>
                </div>

                <?php if ($erro): ?>
                    <div class="error-message">
                        <i class="bi bi-exclamation-circle" style="margin-right: 6px;"></i>
                        <?= htmlspecialchars($erro) ?>
                    </div>
                <?php else: ?>
                    <div class="error-message"></div>
                <?php endif; ?>
            </form>

            <div class="login-footer">
                <div class="register-link">
                    <i class="bi bi-person-plus" style="margin-right: 4px; opacity: 0.6;"></i>
                    <a href="register.php">Criar conta</a>
                </div>
                <div class="meta">
                    <i class="bi bi-dot"></i> v2.0
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const toggle = document.getElementById('toggleSenha');
            const senhaInput = document.getElementById('senha');
            if (toggle && senhaInput) {
                toggle.addEventListener('click', function() {
                    if (senhaInput.type === 'password') {
                        senhaInput.type = 'text';
                        this.classList.remove('bi-eye-slash');
                        this.classList.add('bi-eye');
                    } else {
                        senhaInput.type = 'password';
                        this.classList.remove('bi-eye');
                        this.classList.add('bi-eye-slash');
                    }
                });
            }

            const cursor = document.getElementById('customCursor');
            let mouseX = 0,
                mouseY = 0;
            let cursorX = 0,
                cursorY = 0;
            document.addEventListener('mousemove', function(e) {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            function animateCursor() {
                cursorX += (mouseX - cursorX) * 0.12;
                cursorY += (mouseY - cursorY) * 0.12;
                cursor.style.left = cursorX + 'px';
                cursor.style.top = cursorY + 'px';
                requestAnimationFrame(animateCursor);
            }
            animateCursor();

            const image = document.getElementById('parallaxImage');
            const centerX = 0;
            const centerY = 35;
            let currentX = centerX;
            let currentY = centerY;
            let targetX = centerX;
            let targetY = centerY;
            let isHovering = false;

            document.addEventListener('mousemove', function(e) {
                const rect = image.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                const maxOffsetX = 10;
                const maxOffsetY = 20;
                let rawTargetX = centerX + (x - 0.5) * maxOffsetX;
                let rawTargetY = centerY + (y - 0.5) * maxOffsetY;
                targetX = Math.max(0, Math.min(100, rawTargetX));
                targetY = Math.max(0, Math.min(100, rawTargetY));
                isHovering = true;
            });

            function animateParallax() {
                if (isHovering) {
                    currentX += (targetX - currentX) * 0.12;
                    currentY += (targetY - currentY) * 0.12;
                } else {
                    currentX += (centerX - currentX) * 0.05;
                    currentY += (centerY - currentY) * 0.05;
                }
                image.style.backgroundPosition = currentX + '% ' + currentY + '%';
                requestAnimationFrame(animateParallax);
            }
            animateParallax();

            document.addEventListener('mouseleave', function() {
                isHovering = false;
            });

            const btn = document.getElementById('btnLogin');
            btn.addEventListener('click', function(e) {
                if (this.disabled) return;
                const rect = this.getBoundingClientRect();
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });

            const form = document.getElementById('loginForm');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');

            form.addEventListener('submit', function() {
                if (btn.disabled) return;
                btn.disabled = true;
                btnText.textContent = 'Entrando...';
                btnIcon.className = 'bi bi-spinner spinner-icon';
                btnIcon.style.fontSize = '18px';
            });

            document.getElementById('username').focus();
        });
    </script>

</body>

</html>