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

// ========== FUNÇÕES AUXILIARES (reutilizadas) ==========
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

// ========== PROCESSAMENTO ==========
$mensagem = '';
$tipo = 'erro'; // 'sucesso' ou 'erro'
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// 1. Verifica se há token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $mensagem = 'Nenhum token fornecido.';
    logEvento('Acesso sem token - verify');
}
// 2. Verifica bloqueio ativo
elseif (verificarBloqueio($pdo, $ip)) {
    $mensagem = 'Seu IP está temporariamente bloqueado. Tente mais tarde.';
    logEvento('Tentativa de verificação com IP bloqueado');
}
// 3. Verifica rate limit (tentativas de verificação)
elseif (!verificarRateLimit($pdo, $ip)) {
    registrarBloqueio($pdo, $ip, 15);
    $mensagem = 'Muitas tentativas. Aguarde 15 minutos.';
    logEvento('Rate limit excedido - verify bloqueado 15 min');
} else {
    $token = $_GET['token'];

    // Busca o usuário pelo token (apenas contas não ativadas)
    $stmt = $pdo->prepare("SELECT id, nome, email, token_expiracao FROM usuarios WHERE token = ? AND email_verificado = 0");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Verifica expiração
        $expiracao = new DateTime($usuario['token_expiracao']);
        $agora = new DateTime();
        if ($agora <= $expiracao) {
            // Token válido – ativa conta
            $update = $pdo->prepare("UPDATE usuarios SET email_verificado = 1, token = NULL, token_expiracao = NULL WHERE id = ?");
            $update->execute([$usuario['id']]);
            $mensagem = "Conta ativada com sucesso! Agora você pode fazer login.";
            $tipo = 'sucesso';
            logEvento('Conta ativada com sucesso: ' . $usuario['email'] . ' (ID: ' . $usuario['id'] . ')');

            // Remove tentativas deste IP (opcional)
            // $pdo->prepare("DELETE FROM tentativas_registro WHERE ip = ?")->execute([$ip]);
        } else {
            // Token expirado – gera novo token e notifica o usuário
            $novo_token = bin2hex(random_bytes(32));
            $nova_expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $update = $pdo->prepare("UPDATE usuarios SET token = ?, token_expiracao = ? WHERE id = ?");
            $update->execute([$novo_token, $nova_expiracao, $usuario['id']]);

            $mensagem = "O link de ativação expirou. Um novo link foi enviado para seu e-mail.";
            // Envia novo e-mail (reutilizar função do registro)
            // Nota: você precisará copiar a função enviarEmail() do register.php
            // ou integrar aqui.
            // Exemplo: enviarEmail($usuario['email'], $usuario['nome'], $novo_token);
            logEvento('Token expirado, novo gerado para: ' . $usuario['email']);
            // Opcional: enviar e-mail com o novo token
        }
    } else {
        // Token inválido ou conta já ativada – mensagem genérica
        $mensagem = 'Token inválido ou conta já ativada.';
        logEvento('Token inválido ou conta já ativada: ' . $token);
        registrarTentativa($pdo, $ip); // registra tentativa para rate limit

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
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativação de Conta - Constell</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ===== HEADERS DE SEGURANÇA (já via PHP, mas reforço no meta) ===== */
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
            max-width: 500px;
            width: 100%;
            text-align: center;
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

        .card .icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }

        .card h1 {
            color: #f0f6fc;
            font-size: 28px;
            font-weight: 400;
            margin-bottom: 12px;
        }

        .card p {
            color: #8b949e;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .card .btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.3s;
            font-family: 'Share Tech Mono', monospace;
        }

        .card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(40, 167, 69, 0.35);
        }

        .sucesso .icon {
            color: #4cd9a0;
        }

        .erro .icon {
            color: #f85149;
        }

        .card .btn-erro {
            background: linear-gradient(135deg, #f85149, #e03a2e);
        }

        .card .btn-erro:hover {
            box-shadow: 0 8px 28px rgba(248, 81, 73, 0.35);
        }
    </style>
</head>

<body>
    <div class="card <?= $tipo ?>">
        <span class="icon">
            <?php if ($tipo === 'sucesso'): ?>
                <i class="bi bi-check-circle-fill"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-triangle-fill"></i>
            <?php endif; ?>
        </span>
        <h1><?= $tipo === 'sucesso' ? 'Conta ativada!' : 'Ops!' ?></h1>
        <p><?= htmlspecialchars($mensagem) ?></p>
        <?php if ($tipo === 'sucesso'): ?>
            <a href="login.php" class="btn">Ir para o login</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-erro">Tentar novamente</a>
        <?php endif; ?>
    </div>
</body>

</html>