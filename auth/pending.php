<?php
// ========== HEADERS DE SEGURANÇA ==========
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
// (Opcional) Content-Security-Policy – ajuste conforme necessidade
// header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com;");

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
// Se o email não foi fornecido, exibe mensagem genérica e redireciona (opcional)
if (empty($email)) {
    // Pode redirecionar para login ou exibir mensagem
    $email = 'seu e-mail';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirme seu e-mail - Constell</title>
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
            color: #4cd9a0;
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
            margin-bottom: 8px;
        }

        .card .small {
            font-size: 14px;
            color: #484f58;
        }

        .card .btn {
            display: inline-block;
            margin-top: 20px;
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
    </style>
</head>

<body>
    <div class="card">
        <span class="icon"><i class="bi bi-envelope-check-fill"></i></span>
        <h1>Verifique seu e-mail</h1>
        <p>Enviamos um link de ativação para <strong><?= htmlspecialchars($email) ?></strong>.</p>
        <p>Clique no link para ativar sua conta e fazer login.</p>
        <p class="small">O link é válido por <strong>24 horas</strong>.</p>
        <a href="login.php" class="btn">Ir para o login</a>
    </div>
</body>

</html>