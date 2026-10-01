<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Token não fornecido']);
    exit;
}

// Verifica se o token existe no banco (opcional, mas recomendado)
// Se quiser validar, faça uma consulta ao banco. Senão, apenas confia no token.

// Inicia a sessão
$_SESSION['logado'] = true;
$_SESSION['usuario'] = $input['usuario'] ?? 'Usuário';
$_SESSION['token'] = $token;

echo json_encode(['success' => true]);
