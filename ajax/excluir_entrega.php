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
    
    // Iniciar transação
    $conn->begin_transaction();
    
    // Excluir a entrega
    $stmt = $conn->prepare("DELETE FROM entregas WHERE id = ? AND status = 'Entregue'");
    $stmt->bind_param('i', $entrega_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir entrega');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Entrega não encontrada ou não pode ser excluída');
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Entrega excluída com sucesso'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 