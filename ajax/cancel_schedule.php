<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['schedule_id'])) {
        throw new Exception('ID do agendamento não informado');
    }
    
    // Verificar se o agendamento pertence ao usuário
    $stmt = $conn->prepare("
        DELETE FROM chaflow_schedules 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    
    $stmt->bind_param('ii', $data['schedule_id'], $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento cancelado com sucesso'
        ]);
    } else {
        throw new Exception('Agendamento não encontrado ou já executado');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 