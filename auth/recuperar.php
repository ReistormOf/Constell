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
$tipo = ''; // 'sucesso' ou 'erro'

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

// ========== PHPMailer (reaproveitado) ==========
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailRecuperacao($destinatario, $nome, $token)
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
        $mail->Subject = 'Redefina sua senha - Constell';
        $link = "https://snowcoder.com.br/redefinir.php?token=" . urlencode($token);
        $mail->Body = "Olá $nome,\n\nRecebemos uma solicitação para redefinir sua senha no Constell.\n\nClique no link abaixo para criar uma nova senha:\n\n$link\n\nEste link é válido por 15 minutos.\n\nSe você não solicitou, ignore este e-mail.\n\nAtenciosamente,\nEquipe Constell";
        return $mail->send();
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail de recuperação: " . $mail->ErrorInfo);
        return false;
    }
}

// ========== PROCESSAMENTO ==========
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica bloqueio
    if (verificarBloqueio($pdo, $ip)) {
        $mensagem = 'Seu IP está temporariamente bloqueado. Tente mais tarde.';
        $tipo = 'erro';
        logEvento('Tentativa de recuperação com IP bloqueado');
    }
    // Verifica rate limit
    elseif (!verificarRateLimit($pdo, $ip)) {
        registrarBloqueio($pdo, $ip, 15);
        $mensagem = 'Muitas tentativas. Aguarde 15 minutos.';
        $tipo = 'erro';
        logEvento('Rate limit excedido - recuperação bloqueado 15 min');
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (empty($email)) {
            $mensagem = 'Preencha o campo de e-mail.';
            $tipo = 'erro';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = 'E-mail inválido.';
            $tipo = 'erro';
            logEvento('E-mail inválido na recuperação: ' . $email);
            registrarTentativa($pdo, $ip);
        } else {
            // Verifica se o e-mail existe no banco (mensagem genérica)
            $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // Gera token e expiração (15 min)
                $token = bin2hex(random_bytes(32));
                $expiracao = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Remove tokens antigos e insere o novo
                $update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expiracao = ? WHERE id = ?");
                $update->execute([$token, $expiracao, $usuario['id']]);

                // Envia e-mail
                if (enviarEmailRecuperacao($usuario['email'], $usuario['nome'], $token)) {
                    $mensagem = 'Enviamos um link de redefinição para seu e-mail.';
                    $tipo = 'sucesso';
                    logEvento('Recuperação solicitada para: ' . $usuario['email']);
                } else {
                    $mensagem = 'Não foi possível enviar o e-mail. Tente novamente mais tarde.';
                    $tipo = 'erro';
                    logEvento('Falha ao enviar e-mail de recuperação para: ' . $usuario['email']);
                    registrarTentativa($pdo, $ip);
                }
            } else {
                // Mensagem genérica (não diz se o e-mail existe)
                $mensagem = 'Se este e-mail estiver cadastrado, você receberá um link de redefinição.';
                $tipo = 'sucesso'; // para não revelar que não existe
                logEvento('Tentativa de recuperação com e-mail não cadastrado: ' . $email);
                registrarTentativa($pdo, $ip);
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
    <title>Recuperar Senha - Constell</title>
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

        .card .subtitle span {
            color: #c88a4a;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
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

        .btn-enviar {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #c88a4a, #a86a2a);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Share Tech Mono', monospace;
            transition: transform 0.2s, box-shadow 0.3s;
            cursor: pointer;
        }

        .btn-enviar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(200, 138, 74, 0.35);
        }

        .btn-enviar:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .message {
            text-align: center;
            font-size: 14px;
            margin: 16px 0px;
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
            <i class="bi bi-key"></i>
        </div>
        <h1>Recuperar senha</h1>
        <p class="subtitle">Enviaremos um link para <span>redefinir</span> sua senha</p>

        <?php if ($mensagem): ?>
            <div class="message <?= $tipo ?>">
                <i class="bi <?= $tipo === 'sucesso' ? 'bi-check-circle' : 'bi-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="recuperarForm">
            <div class="input-group">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" name="email" id="email" placeholder="Seu e-mail cadastrado" required>
            </div>
            <button type="submit" class="btn-enviar" id="btnEnviar">
                <span id="btnText">Enviar link</span>
                <i class="bi bi-arrow-right" id="btnIcon" style="font-size:18px; margin-left:8px;"></i>
            </button>
        </form>

        <div class="login-link">
            <i class="bi bi-box-arrow-in-left"></i>
            <a href="login.php">Voltar para o login</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('recuperarForm');
            const btn = document.getElementById('btnEnviar');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');

            form.addEventListener('submit', function() {
                if (btn.disabled) return;
                btn.disabled = true;
                btnText.textContent = 'Enviando...';
                btnIcon.className = 'bi bi-spinner spinner-icon';
                btnIcon.style.display = 'inline-block';
                btnIcon.style.animation = 'spin 0.8s linear infinite';
            });

            // Estilo do spinner (adicionado dinamicamente)
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