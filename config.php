<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'constell');
define('DB_USER', 'root');
define('DB_PASS', '');   // vazio no XAMPP

define('SMTP_USER', 'contato@snowcoder.com.br');
define('SMTP_PASS', 'Ze1478@Kingo');


function getDB()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erro no banco: " . $e->getMessage());
    }
}
