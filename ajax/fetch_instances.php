<?php
session_start();
require_once '../database/db.php';
require_once '../classes/EvolutionInstance.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $evolutionInstance = new EvolutionInstance($conn, $_SESSION['user_id']);
    $result = $evolutionInstance->fetchInstances();
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 