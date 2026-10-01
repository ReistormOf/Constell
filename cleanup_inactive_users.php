<?php

/**
 * Cleanup Inactive Users
 * Remove usuários que não ativaram a conta em 24 horas.
 * Pode ser executado via cron job diariamente.
 */

// Configuração do banco
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro de conexão: ' . $e->getMessage());
}

// Define o limite de tempo: 24 horas atrás
$limite = date('Y-m-d H:i:s', strtotime('-24 hours'));

// Busca usuários não verificados com cadastro há mais de 24h
$sql = "SELECT id, nome, email, created_at FROM usuarios 
        WHERE email_verificado = 0 
        AND created_at < ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$limite]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quantidade = count($usuarios);

if ($quantidade > 0) {
    // Remove os usuários encontrados
    $delete = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    foreach ($usuarios as $usuario) {
        $delete->execute([$usuario['id']]);
    }

    echo "✅ Removidos $quantidade usuário(s) inativos (cadastrados há mais de 24h sem ativação).\n";
    echo "Usuários removidos:\n";
    foreach ($usuarios as $usuario) {
        echo " - ID: {$usuario['id']}, Nome: {$usuario['nome']}, Email: {$usuario['email']}, Data: {$usuario['created_at']}\n";
    }
} else {
    echo "✅ Nenhum usuário inativo para remover.\n";
}
