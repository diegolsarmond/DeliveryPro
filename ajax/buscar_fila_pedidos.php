<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Buscar pedidos POS com os status específicos
    $stmt = $conn->prepare("
        SELECT c.*, m.numero as mesa_numero
        FROM cliente c
        LEFT JOIN mesas m ON c.mesa_id = m.id
        WHERE c.tipo = 'pos' 
        AND c.status IN ('Em Preparo', 'Pronto para Entrega', 'Entregue')
        AND DATE(STR_TO_DATE(c.data, '%d/%m/%Y %H:%i')) = CURDATE()
        ORDER BY 
            CASE c.status
                WHEN 'Em Preparo' THEN 1
                WHEN 'Pronto para Entrega' THEN 2
                WHEN 'Entregue' THEN 3
            END,
            STR_TO_DATE(c.data, '%d/%m/%Y %H:%i') DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'pedido' => $row['pedido'],
            'status' => $row['status'],
            'itens' => $row['itens'],
            'mesa_numero' => $row['mesa_numero'] ?? 'N/A',
            'data' => $row['data']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 