<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    // Buscar pedidos não visualizados
    $stmt = $conn->prepare("
        SELECT id, nome, pedido, total, status, data 
        FROM cliente 
        WHERE visualizado = 0 
        AND status = 'Pendente'
        ORDER BY data DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $pedidos = [];
    
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'pedido' => $row['pedido'],
            'total' => $row['total'],
            'status' => $row['status'],
            'data' => date('d/m/Y H:i', strtotime($row['data']))
        ];
    }
    
    echo json_encode($pedidos);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();