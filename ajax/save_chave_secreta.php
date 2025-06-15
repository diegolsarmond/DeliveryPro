<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['chave_secreta'])) {
    echo json_encode(['success' => false, 'message' => 'Chave secreta não fornecida']);
    exit;
}

try {
    $chave_secreta = $_POST['chave_secreta'];
    
    $stmt = $conn->prepare("UPDATE license_codes SET chave_secreta = ? WHERE is_active = 1");
    $stmt->bind_param("s", $chave_secreta);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Chave secreta atualizada com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar chave secreta');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 