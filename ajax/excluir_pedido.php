<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    $conn->begin_transaction();

    // Excluir entregas relacionadas primeiro
    $stmt = $conn->prepare("DELETE FROM entregas WHERE pedido_id = ?");
    $stmt->bind_param('i', $_POST['id']);
    $stmt->execute();

    // Depois excluir o pedido
    $stmt = $conn->prepare("DELETE FROM cliente WHERE id = ?");
    $stmt->bind_param('i', $_POST['id']);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir pedido: ' . $e->getMessage()
    ]);
}

$conn->close(); 