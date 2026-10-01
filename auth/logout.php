<?php
// ========== SEGURANÇA DE SESSÃO ==========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');

// Inicia a sessão para poder destruí-la
session_start();

// ========== HEADERS DE SEGURANÇA ==========
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ========== FUNÇÃO DE LOG (opcional, mas útil) ==========
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

// ========== REGISTRA LOGOUT (se houver usuário logado) ==========
if (isset($_SESSION['user_username']) || isset($_SESSION['user_name'])) {
    $usuario = $_SESSION['user_username'] ?? $_SESSION['user_name'] ?? 'desconhecido';
    logEvento('Logout: ' . $usuario);
}

// ========== DESTRÓI A SESSÃO COMPLETAMENTE ==========
// 1. Limpa todas as variáveis de sessão
$_SESSION = array();

// 2. Se a sessão usa cookies, apaga o cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destrói a sessão
session_destroy();

// ========== REDIRECIONA COM LIMPEZA DO LOCALSTORAGE ==========
// Usamos JavaScript para limpar o localStorage antes do redirecionamento
echo '<!DOCTYPE html>
<html>
<head>
    <script>
        // Limpa os dados armazenados no localStorage (usados no front-end)
        localStorage.removeItem("user_token");
        localStorage.removeItem("user_name");
        // Redireciona para a página de login
        window.location.href = "login.php";
    </script>
</head>
<body>
    <p>Saindo...</p>
</body>
</html>';
exit;
