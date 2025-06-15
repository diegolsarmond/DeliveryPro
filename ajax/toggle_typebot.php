<?php
session_start();
require_once '../database/db.php';
require_once '../classes/TypebotIntegration.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;

    $typebot = new TypebotIntegration($conn, $_SESSION['user_id']);
    $result = $typebot->updateTypebot($enabled);
    
    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Typebot ativado com sucesso' : 'Typebot pausado com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 