<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['entrega_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da entrega não informado']);
    exit;
}

try {
    $entrega_id = (int)$_GET['entrega_id'];
    
    $stmt = $conn->prepare("
        SELECT c.status 
        FROM entregas e
        JOIN cliente c ON e.pedido_id = c.id
        WHERE e.id = ?
    ");
    
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'status' => $row['status']
        ]);
    } else {
        throw new Exception('Entrega não encontrada');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 