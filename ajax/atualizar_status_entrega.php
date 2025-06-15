<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['entrega_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    $entrega_id = (int)$_POST['entrega_id'];
    $status = $_POST['status'];
    
    $conn->begin_transaction();
    
    // Buscar pedido_id da entrega
    $stmt = $conn->prepare("SELECT pedido_id FROM entregas WHERE id = ?");
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    $pedido_id = $stmt->get_result()->fetch_assoc()['pedido_id'];
    
    // Atualizar status do pedido
    $stmt = $conn->prepare("UPDATE cliente SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $pedido_id);
    $stmt->execute();
    
    // Atualizar status da entrega se necessário
    if ($status == 'Em Rota') {
        $stmt = $conn->prepare("UPDATE entregas SET status = 'Em Rota' WHERE id = ?");
        $stmt->bind_param('i', $entrega_id);
        $stmt->execute();
    } elseif ($status == 'Entregue') {
        $stmt = $conn->prepare("
            UPDATE entregas 
            SET status = 'Entregue',
                hora_entrega = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $entrega_id);
        $stmt->execute();
    }
    
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