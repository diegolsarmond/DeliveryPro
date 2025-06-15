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
    if (!isset($_POST['remoteJid']) || !isset($_POST['status'])) {
        throw new Exception('Parâmetros inválidos');
    }

    $typebot = new TypebotIntegration($conn, $_SESSION['user_id']);
    $result = $typebot->changeTypebotStatus($_POST['remoteJid'], $_POST['status']);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Status do Typebot alterado com sucesso',
            'data' => $result['response']
        ]);
    } else {
        throw new Exception('Erro ao alterar status do Typebot');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 