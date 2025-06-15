<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

$user_id = $_SESSION['user_id'];
$newPassword = $_POST['newPassword'];
$confirmPassword = $_POST['confirmPassword'];

// Validar senhas
if (empty($newPassword) || empty($confirmPassword)) {
    exit(json_encode(['success' => false, 'message' => 'As senhas não podem estar vazias']));
}

if ($newPassword !== $confirmPassword) {
    exit(json_encode(['success' => false, 'message' => 'As senhas não coincidem']));
}

if (strlen($newPassword) < 6) {
    exit(json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']));
}

try {
    $password_hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso!']);
    } else {
        throw new Exception('Erro ao atualizar a senha');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 