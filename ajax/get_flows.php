<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Buscar fluxos do usuário
    $stmt = $conn->prepare("
        SELECT id, name, description, active, created_at, updated_at
        FROM chaflow_flows 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $flows = [];
    while ($row = $result->fetch_assoc()) {
        $flows[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'active' => (bool)$row['active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'flows' => $flows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 