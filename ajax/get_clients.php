<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id, nome, telefone 
        FROM cliente 
        WHERE telefone IS NOT NULL 
        AND telefone != 'Não informado'
        ORDER BY nome ASC
    ");
    
    $stmt->execute();
    $clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 