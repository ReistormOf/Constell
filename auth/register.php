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
    header('Location: ferramenta.php');
    exit;
}

$erro = '';
$sucesso = '';

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

// ========== NONCE DE USO ÚNICO ==========
if (empty($_SESSION['register_nonce'])) {
    $_SESSION['register_nonce'] = bin2hex(random_bytes(16));
}
$register_nonce = $_SESSION['register_nonce'];

// ========== PHPMailer ==========
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ========== FUNÇÃO DE ENVIO DE E-MAIL ==========
function enviarEmail($destinatario, $nome, $token)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ]
        ];

        $mail->setFrom(SMTP_USER, 'Constell');
        $mail->addAddress($destinatario, $nome);
        $mail->isHTML(false);
        $mail->Subject = 'Ative sua conta - Constell';
        $link = "https://snowcoder.com.br/knowsnow1/auth/verify.php?token=" . urlencode($token);
        $mail->Body = "Olá $nome,\n\nObrigado por se registrar no Constell. Para ativar sua conta, clique no link abaixo:\n\n$link\n\nEste link é válido por 24 horas.\n\nSe você não solicitou, ignore este e-mail.\n\nAtenciosamente,\nEquipe Constell";
        return $mail->send();
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
        return false;
    }
}

// ========== FUNÇÕES AUXILIARES ==========
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

function emailValido($email)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    // ===== VERIFICAÇÃO MX (ativa em produção com DNS) =====
    $domain = substr(strrchr($email, "@"), 1);
    return checkdnsrr($domain, 'MX');
    return true;
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

// ===== NOVAS FUNÇÕES: BLOQUEIO PROGRESSIVO =====
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

function senhaEComum($senha)
{
    $arquivo = __DIR__ . '/senhas_comuns.txt';
    if (!file_exists($arquivo)) return false;
    $lista = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return in_array(strtolower($senha), $lista);
}

// ========== PROCESSAMENTO DO FORMULÁRIO ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // 1. Verifica CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = 'Token de segurança inválido.';
        logEvento('CSRF inválido');
    }
    // 2. Verifica nonce de uso único
    elseif (!isset($_POST['register_nonce']) || $_POST['register_nonce'] !== $_SESSION['register_nonce']) {
        $erro = 'Token de submissão inválido.';
        logEvento('Nonce inválido');
    }
    // 3. Verifica Honeypot (campo oculto)
    elseif (!empty($_POST['honeypot'])) {
        $erro = 'Erro de validação.';
        logEvento('Honeypot preenchido – possível bot');
        // Não registra tentativa para não consumir rate limit do usuário real
    }
    // 4. Verifica bloqueio ativo
    elseif (verificarBloqueio($pdo, $ip)) {
        $erro = 'Seu IP está temporariamente bloqueado. Tente mais tarde.';
        logEvento('Tentativa com IP bloqueado');
    }
    // 5. Verifica rate limit
    elseif (!verificarRateLimit($pdo, $ip)) {
        // Excedeu 5 tentativas – bloqueia por 15 min
        registrarBloqueio($pdo, $ip, 15);
        $erro = 'Muitas tentativas. Aguarde 15 minutos.';
        logEvento('Rate limit excedido – bloqueado 15 min');
    } else {
        // Consome o nonce (evita reenvio)
        unset($_SESSION['register_nonce']);

        // Sanitização
        $nome = trim(preg_replace('/[^a-zA-Z0-9_ ]/', '', $_POST['nome'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';
        $senha_confirm = $_POST['senha_confirm'] ?? '';

        // Validações
        if (empty($nome) || empty($email) || empty($senha) || empty($senha_confirm)) {
            $erro = 'Preencha todos os campos.';
        } elseif (strlen($nome) > 50) {
            $erro = 'Nome muito longo (máximo 50 caracteres).';
        } elseif ($senha !== $senha_confirm) {
            $erro = 'As senhas não coincidem.';
            logEvento('Senhas não coincidem para ' . $email);
        } elseif (!emailValido($email)) {
            $erro = 'E-mail inválido.';
            logEvento('E-mail inválido: ' . $email);
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $senha)) {
            $erro = 'A senha deve ter pelo menos 8 caracteres, com maiúscula, minúscula, número e símbolo.';
        } elseif (senhaEComum($senha)) {
            $erro = 'Esta senha é muito comum. Escolha uma mais segura.';
        } else {
            try {
                // Verifica duplicidade (mensagem genérica)
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ? OR email = ?");
                $stmt->execute([$nome, $email]);
                if ($stmt->fetch()) {
                    $erro = 'Nome de usuário ou e-mail já cadastrado.';
                    logEvento('Tentativa com nome/email existente: ' . $nome . ' / ' . $email);
                } else {
                    // Gera token e expiração
                    $token = bin2hex(random_bytes(32));
                    $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

                    $sql = "INSERT INTO usuarios 
                            (nome, email, senha, token, token_expiracao, email_verificado, 
                             ip_cadastro, user_agent_cadastro) 
                            VALUES (?, ?, ?, ?, ?, 0, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $email, $hash, $token, $expiracao, $ip, $user_agent]);

                    if (enviarEmail($email, $nome, $token)) {
                        $sucesso = 'Conta criada! Enviamos um link de ativação para seu e-mail.';
                        logEvento('Cadastro bem-sucedido: ' . $email);
                        header("refresh:3;url=pending.php?email=" . urlencode($email));
                        exit;
                    } else {
                        $link = "https://snowcoder.com.br/verify.php?token=" . urlencode($token);
                        $sucesso = "Conta criada, mas o e-mail não pôde ser enviado. <br> 
                             <strong>Link de ativação (copie e cole):</strong><br> 
                             <a href='$link' target='_blank'>$link</a>";
                        logEvento('E-mail não enviado para ' . $email);
                    }
                }
            } catch (PDOException $e) {
                error_log("Erro no registro: " . $e->getMessage());
                $erro = 'Ocorreu um erro interno. Tente novamente mais tarde.';
                logEvento('Erro PDO: ' . $e->getMessage());
            }
        }

        // Se houve erro, registra tentativa e gerencia bloqueio progressivo
        if ($erro) {
            registrarTentativa($pdo, $ip);

            // Verifica se já existe bloqueio ativo para escalonar
            $stmt = $pdo->prepare("SELECT tentativas FROM bloqueios_ip WHERE ip = ? AND bloqueado_ate > NOW()");
            $stmt->execute([$ip]);
            $tentativas_bloqueio = $stmt->fetchColumn();
            if ($tentativas_bloqueio !== false) {
                // Escalona com base no número de tentativas já registradas
                if ($tentativas_bloqueio >= 20) {
                    registrarBloqueio($pdo, $ip, 1440); // 24h
                } elseif ($tentativas_bloqueio >= 10) {
                    registrarBloqueio($pdo, $ip, 60);   // 1h
                }
                // Se for >=5, já foi bloqueado por 15 min na verificação de rate limit
            }
        }
    }
}

// Gera novo nonce para o próximo carregamento
if (empty($_SESSION['register_nonce'])) {
    $_SESSION['register_nonce'] = bin2hex(random_bytes(16));
}
$register_nonce = $_SESSION['register_nonce'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar · Constell</title>
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
            border: 1.5px solid rgba(40, 167, 69, 0.5);
            background: rgba(40, 167, 69, 0.04);
            transform: translate(-50%, -50%);
            transition: width 0.2s, height 0.2s, border-color 0.2s;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.05);
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
            background: rgba(40, 167, 69, 0.6);
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.2);
        }

        .register-wrapper {
            width: 100%;
            max-width: 1400px;
            height: 90vh;
            max-height: 850px;
            display: flex;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: -8px 0 40px rgba(0, 0, 0, 0.6);
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

        .register-wrapper::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            background: #0d1117;
            pointer-events: none;
            border-radius: 5px;
        }

        .register-image {
            flex: 1;
            background-color: #0d1117;
            background-image: url('../assets/imgs/EstatuaPlatão.png');
            background-size: cover;
            background-position: 25% center;
            background-repeat: no-repeat;
            position: relative;
            min-height: 100%;
            will-change: background-position;
            transition: none;
            z-index: 1;
            overflow: hidden;
            mask-image: none;
            -webkit-mask-image: none;
        }

        .register-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to left, transparent 0%, transparent 50%, rgba(13, 17, 23, 0.1) 70%, #0d1117 100%);
            pointer-events: none;
            z-index: 1;
            border-radius: 0;
        }

        .register-card {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 500px;
            max-width: 35%;
            padding: 44px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(13, 17, 23, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 8px 0 40px rgba(0, 0, 0, 0.6);
            z-index: 2;
            animation: cardSlideIn 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes cardSlideIn {
            0% {
                opacity: 0;
                transform: translateX(-40px) scale(0.96);
            }

            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .register-card .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 5px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            font-size: 28px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.25);
        }

        .register-card h1 {
            font-size: 32px;
            font-weight: 600;
            color: #f0f6fc;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
            font-family: 'Share Tech Mono', monospace;
        }

        .register-card .subtitle {
            font-size: 15px;
            color: #8b949e;
            font-weight: 400;
            margin-bottom: 32px;
            font-family: 'Share Tech Mono', monospace;
        }

        .register-card .subtitle span {
            color: #28a745;
            font-weight: 500;
        }

        .register-form {
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

        .input-group:nth-child(4) {
            animation-delay: 0.45s;
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
            border-color: rgba(40, 167, 69, 0.3);
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
            border-color: #28a745;
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
            transform: scale(1.01);
        }

        .input-group input:focus~.input-icon {
            color: #28a745;
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
            color: #28a745;
        }

        .btn-register {
            margin-top: 4px;
            padding: 15px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 5px;
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

        .btn-register::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 5px;
            padding: 2px;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(40, 167, 69, 0.35);
        }

        .btn-register:active:not(:disabled) {
            transform: scale(0.97);
        }

        .btn-register:disabled {
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

        .message {
            color: #f85149;
            font-size: 13px;
            text-align: center;
            min-height: 24px;
            margin-top: 4px;
            font-weight: 450;
            font-family: 'Share Tech Mono', monospace;
        }

        .message.success {
            color: #4cd9a0;
        }

        .register-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #21262d;
        }

        .register-footer .login-link {
            font-size: 14px;
            color: #8b949e;
            font-family: 'Share Tech Mono', monospace;
        }

        .register-footer .login-link a {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .register-footer .login-link a:hover {
            color: #34ce57;
            text-decoration: underline;
        }

        .register-footer .meta {
            font-size: 12px;
            color: #484f58;
            letter-spacing: 0.02em;
            font-family: 'Share Tech Mono', monospace;
        }

        .register-footer .meta i {
            color: #28a745;
            opacity: 0.6;
        }

        @media (max-width:968px) {
            .register-wrapper {
                flex-direction: column;
                height: auto;
                max-height: none;
                border-radius: 5px;
            }

            .register-image {
                flex: 0 0 300px;
                background-position: center 30%;
                background-size: cover;
                mask-image: none !important;
                -webkit-mask-image: none !important;
            }

            .register-image::after {
                background: linear-gradient(to bottom, transparent, rgba(13, 17, 23, 0.9) 80%);
            }

            .register-card {
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
        }

        @media (max-width:480px) {
            .register-card {
                padding: 24px 20px;
            }

            .register-card h1 {
                font-size: 24px;
            }

            .register-image {
                flex: 0 0 200px;
            }

            .btn-register {
                padding: 12px;
                font-size: 15px;
            }

            .register-footer {
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
        }
    </style>
</head>

<body>

    <div class="custom-cursor" id="customCursor">
        <div class="dot"></div>
    </div>

    <div class="register-wrapper">
        <div class="register-image" id="parallaxImage"></div>
        <div class="register-card">
            <div class="brand-icon"><i class="bi bi-person-plus"></i></div>
            <h1>Constell</h1>
            <p class="subtitle">workspace <span>•</span> crie sua conta</p>

            <form class="register-form" method="post" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="register_nonce" value="<?= htmlspecialchars($register_nonce) ?>">

                <!-- ===== HONEYPOT (campo invisível para bots) ===== -->
                <div style="position: absolute; left: -9999px; top: -9999px;">
                    <input type="text" name="honeypot" id="honeypot" value="" tabindex="-1" autocomplete="off">
                </div>

                <div class="input-group">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="nome" id="nome" placeholder="Nome de usuário" required autofocus>
                </div>
                <div class="input-group">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" id="email" placeholder="E-mail" required>
                </div>
                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="senha" id="senha" placeholder="Senha" required>
                    <i class="bi bi-eye-slash toggle-password" id="toggleSenha"></i>
                </div>
                <div class="input-group">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" name="senha_confirm" id="senha_confirm" placeholder="Confirmar senha" required>
                </div>

                <button type="submit" class="btn-register" id="btnRegister">
                    <span id="btnText">Criar conta</span>
                    <i class="bi bi-arrow-right" id="btnIcon" style="font-size:18px;"></i>
                </button>

                <div class="message <?= $sucesso ? 'success' : '' ?>" id="messageArea">
                    <?php if ($erro): ?>
                        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                    <?php elseif ($sucesso): ?>
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($sucesso) ?>
                    <?php endif; ?>
                </div>
            </form>

            <div class="register-footer">
                <div class="login-link"><i class="bi bi-box-arrow-in-right"></i> <a href="login.php">Já tenho conta</a></div>
                <div class="meta"><i class="bi bi-dot"></i> v2.0</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle senha
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

            // Cursor personalizado
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

            // Parallax
            const image = document.getElementById('parallaxImage');
            const centerX = 25,
                centerY = 35;
            let currentX = centerX,
                currentY = centerY;
            let targetX = centerX,
                targetY = centerY;
            let isHovering = false;
            document.addEventListener('mousemove', function(e) {
                const rect = image.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                const maxOffsetX = 10,
                    maxOffsetY = 20;
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

            // Ripple
            const btn = document.getElementById('btnRegister');
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

            // Loading
            const form = document.getElementById('registerForm');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            form.addEventListener('submit', function() {
                if (btn.disabled) return;
                btn.disabled = true;
                btnText.textContent = 'Criando...';
                btnIcon.className = 'bi bi-spinner spinner-icon';
                btnIcon.style.fontSize = '18px';
            });

            document.getElementById('nome').focus();
        });
    </script>
</body>

</html>