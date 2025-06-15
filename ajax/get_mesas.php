<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM mesas ORDER BY numero");
    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }
    
    $result = $stmt->get_result();
    $mesas = [];
    
    while ($row = $result->fetch_assoc()) {
        $mesas[] = [
            'id' => $row['id'],
            'numero' => $row['numero'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'mesas' => $mesas
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar mesas: ' . $e->getMessage()
    ]);
}

$conn->close(); 