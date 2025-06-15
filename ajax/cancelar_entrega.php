<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['entrega_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da entrega não informado']);
    exit;
}

try {
    $entrega_id = (int)$_POST['entrega_id'];
    
    $conn->begin_transaction();
    
    // Buscar informações da entrega
    $stmt = $conn->prepare("
        SELECT entregador_id, pedido_id 
        FROM entregas 
        WHERE id = ?
    ");
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    $entrega = $stmt->get_result()->fetch_assoc();
    
    // Atualizar status do entregador para Ativo
    if ($entrega['entregador_id']) {
        $stmt = $conn->prepare("UPDATE entregadores SET status = 'Ativo' WHERE id = ?");
        $stmt->bind_param('i', $entrega['entregador_id']);
        $stmt->execute();
    }
    
    // Excluir a entrega
    $stmt = $conn->prepare("DELETE FROM entregas WHERE id = ?");
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    
    // Atualizar status do pedido para Cancelado
    $stmt = $conn->prepare("UPDATE cliente SET status = 'Cancelado' WHERE id = ?");
    $stmt->bind_param('i', $entrega['pedido_id']);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 