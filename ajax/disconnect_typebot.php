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
    // Primeiro, buscar o ID do bot
    $stmt = $conn->prepare("SELECT bot_id FROM typebot_settings WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result || !$result['bot_id']) {
        throw new Exception('ID do bot não encontrado');
    }

    $typebot = new TypebotIntegration($conn, $_SESSION['user_id']);
    $deleteResult = $typebot->deleteTypebot($result['bot_id']);

    if ($deleteResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Typebot desconectado com sucesso'
        ]);
    } else {
        throw new Exception($deleteResult['message'] ?? 'Erro ao desconectar Typebot');
    }

} catch (Exception $e) {
    error_log('Erro ao desconectar Typebot: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 