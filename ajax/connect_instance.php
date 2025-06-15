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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['instanceName'])) {
        throw new Exception('Nome da instância é obrigatório');
    }

    $evolutionInstance = new EvolutionInstance($conn, $_SESSION['user_id']);
    $result = $evolutionInstance->connectInstance($data['instanceName']);
    
    // Se tiver QR code, retorna para exibição
    if (isset($result['qrcode'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Instância conectando',
            'showQr' => true,
            'qrcode' => $result['qrcode']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Instância conectada com sucesso',
            'showQr' => false
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 