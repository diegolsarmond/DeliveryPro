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
    $typebot = new TypebotIntegration($conn, $_SESSION['user_id']);
    $result = $typebot->createTypebot();
    
    echo json_encode([
        'success' => true,
        'message' => 'Typebot ativado com sucesso',
        'data' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao ativar Typebot: ' . $e->getMessage()
    ]);
} 