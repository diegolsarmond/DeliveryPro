<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID do fluxo não informado');
    }

    $stmt = $conn->prepare("
        SELECT * FROM chaflow_flows 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param('ii', $_GET['id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($flow = $result->fetch_assoc()) {
        // Garantir que os campos nodes e connections são arrays JSON válidos
        $flow['nodes'] = $flow['nodes'] ?: '[]';
        $flow['connections'] = $flow['connections'] ?: '[]';

        // Validar JSON
        json_decode($flow['nodes']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $flow['nodes'] = '[]';
        }

        json_decode($flow['connections']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $flow['connections'] = '[]';
        }

        echo json_encode([
            'success' => true,
            'flow' => $flow
        ]);
    } else {
        throw new Exception('Fluxo não encontrado');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 