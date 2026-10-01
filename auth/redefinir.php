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

$mensagem = '';
$tipo = '';
$token_valido = false;
$user_id = null;

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

// ========== VERIFICA TOKEN NA URL ==========
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $mensagem = 'Token não fornecido.';
    $tipo = 'erro';
    logEvento('Acesso sem token - redefinir');
} elseif (verificarBloqueio($pdo, $ip)) {
    $mensagem = 'Seu IP está temporariamente bloqueado. Tente mais tarde.';
    $tipo = 'erro';
    logEvento('Tentativa de redefinição com IP bloqueado');
} elseif (!verificarRateLimit($pdo, $ip)) {
    registrarBloqueio($pdo, $ip, 15);
    $mensagem = 'Muitas tentativas. Aguarde 15 minutos.';
    $tipo = 'erro';
    logEvento('Rate limit excedido - redefinir bloqueado 15 min');
} else {
    $token = $_GET['token'];

    // Busca o usuário pelo token de redefinição
    $stmt = $pdo->prepare("SELECT id, nome, email, reset_token_expiracao FROM usuarios WHERE reset_token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $expiracao = new DateTime($usuario['reset_token_expiracao']);
        $agora = new DateTime();
        if ($agora <= $expiracao) {
            $token_valido = true;
            $user_id = $usuario['id'];
            // O token é válido – exibe o formulário para nova senha
        } else {
            // Token expirado – remove do banco e informa
            $pdo->prepare("UPDATE usuarios SET reset_token = NULL, reset_token_expiracao = NULL WHERE id = ?")
                ->execute([$usuario['id']]);
            $mensagem = 'O link de redefinição expirou. Solicite um novo.';
            $tipo = 'erro';
            logEvento('Token expirado: ' . $token);
            registrarTentativa($pdo, $ip);
        }
    } else {
        // Token inválido
        $mensagem = 'Token inválido ou já utilizado.';
        $tipo = 'erro';
        logEvento('Token inválido: ' . $token);
        registrarTentativa($pdo, $ip);

        // Escalona bloqueio
        $stmt = $pdo->prepare("SELECT tentativas FROM bloqueios_ip WHERE ip = ? AND bloqueado_ate > NOW()");
        $stmt->execute([$ip]);
        $tentativas_bloqueio = $stmt->fetchColumn();
        if ($tentativas_bloqueio !== false) {
            if ($tentativas_bloqueio >= 20) {
                registrarBloqueio($pdo, $ip, 1440);
            } elseif ($tentativas_bloqueio >= 10) {
                registrarBloqueio($pdo, $ip, 60);
            }
        }
    }
}

// ========== PROCESSAMENTO DO FORMULÁRIO (nova senha) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valido && $user_id) {
    // Verifica CSRF (campo hidden)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = 'Token de segurança inválido.';
        $tipo = 'erro';
        logEvento('CSRF inválido - redefinir');
    } else {
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (empty($nova_senha) || empty($confirmar_senha)) {
            $mensagem = 'Preencha todos os campos.';
            $tipo = 'erro';
        } elseif ($nova_senha !== $confirmar_senha) {
            $mensagem = 'As senhas não coincidem.';
            $tipo = 'erro';
            logEvento('Senhas não coincidem - redefinir para usuário ID: ' . $user_id);
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $nova_senha)) {
            $mensagem = 'A senha deve ter pelo menos 8 caracteres, com maiúscula, minúscula, número e símbolo.';
            $tipo = 'erro';
        } else {
            // Atualiza a senha e remove o token
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_token_expiracao = NULL WHERE id = ?");
            $update->execute([$hash, $user_id]);

            $mensagem = 'Senha redefinida com sucesso! Agora você pode fazer login.';
            $tipo = 'sucesso';
            logEvento('Senha redefinida para usuário ID: ' . $user_id);

            // Redireciona após 3 segundos
            header("refresh:3;url=login.php");
            exit;
        }
    }
}

// Gera CSRF token (se não existir)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Constell</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d1117;
            font-family: 'Share Tech Mono', monospace;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid #30363d;
            border-radius: 24px;
            padding: 40px 30px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.5);
            animation: fadeUp 0.6s ease-out;
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #c88a4a, #a86a2a);
            color: #fff;
            font-size: 28px;
            margin-bottom: 24px;
            box-shadow: 0 6px 20px rgba(200, 138, 74, 0.25);
        }

        .card h1 {
            color: #f0f6fc;
            font-size: 28px;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .card .subtitle {
            color: #8b949e;
            font-size: 15px;
            margin-bottom: 28px;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6e7681;
            font-size: 18px;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 15px 16px 15px 48px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid #30363d;
            border-radius: 12px;
            color: #f0f6fc;
            font-size: 15px;
            font-family: 'Share Tech Mono', monospace;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .input-group input:focus {
            border-color: #c88a4a;
            box-shadow: 0 0 0 3px rgba(200, 138, 74, 0.15);
        }

        .input-group input:focus~.input-icon {
            color: #c88a4a;
        }

        .btn-redefinir {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Share Tech Mono', monospace;
            transition: transform 0.2s, box-shadow 0.3s;
            cursor: pointer;
        }

        .btn-redefinir:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(40, 167, 69, 0.35);
        }

        .btn-redefinir:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .message {
            text-align: center;
            font-size: 14px;
            margin-top: 16px;
            padding: 12px;
            border-radius: 8px;
        }

        .message.sucesso {
            color: #4cd9a0;
            background: rgba(76, 217, 160, 0.08);
            border: 1px solid rgba(76, 217, 160, 0.2);
        }

        .message.erro {
            color: #f85149;
            background: rgba(248, 81, 73, 0.08);
            border: 1px solid rgba(248, 81, 73, 0.2);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            color: #8b949e;
            font-size: 14px;
        }

        .login-link a {
            color: #c88a4a;
            text-decoration: none;
            transition: color 0.2s;
        }

        .login-link a:hover {
            color: #d99a5a;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="brand-icon">
            <i class="bi bi-pencil-square"></i>
        </div>
        <h1>Redefinir senha</h1>
        <p class="subtitle">Crie uma <span>nova senha</span> para sua conta</p>

        <?php if ($mensagem): ?>
            <div class="message <?= $tipo ?>">
                <i class="bi <?= $tipo === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($token_valido): ?>
            <form method="post" id="redefinirForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="nova_senha" id="nova_senha" placeholder="Nova senha" required>
                </div>
                <div class="input-group">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" placeholder="Confirmar nova senha" required>
                </div>
                <button type="submit" class="btn-redefinir" id="btnRedefinir">
                    <span id="btnText">Redefinir senha</span>
                    <i class="bi bi-arrow-right" id="btnIcon" style="font-size:18px; margin-left:8px;"></i>
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="recuperar.php" class="btn-redefinir" style="display:inline-block; text-decoration:none; padding:12px 28px; background:linear-gradient(135deg,#c88a4a,#a86a2a);">
                    Solicitar novo link
                </a>
            </div>
        <?php endif; ?>

        <div class="login-link">
            <i class="bi bi-box-arrow-in-left"></i>
            <a href="login.php">Voltar para o login</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('redefinirForm');
            if (form) {
                const btn = document.getElementById('btnRedefinir');
                const btnText = document.getElementById('btnText');
                const btnIcon = document.getElementById('btnIcon');

                form.addEventListener('submit', function() {
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btnText.textContent = 'Redefinindo...';
                    btnIcon.className = 'bi bi-spinner spinner-icon';
                    btnIcon.style.display = 'inline-block';
                    btnIcon.style.animation = 'spin 0.8s linear infinite';
                });
            }

            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
                .spinner-icon {
                    display: inline-block;
                    animation: spin 0.8s linear infinite;
                }
            `;
            document.head.appendChild(style);
        });
    </script>

</body>

</html>