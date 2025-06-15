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
    
    if (!isset($data['schedule_id']) || !isset($data['phone']) || !isset($data['scheduled_for'])) {
        throw new Exception('Dados incompletos');
    }
    
    // Configurar timezone
    date_default_timezone_set('America/Sao_Paulo');
    
    // Converter a data/hora para o formato correto
    $scheduledFor = date('Y-m-d H:i:s', strtotime($data['scheduled_for']));
    
    // Verificar se a data não é no passado
    if (strtotime($scheduledFor) <= time()) {
        throw new Exception('A data de agendamento deve ser no futuro');
    }
    
    // Atualizar agendamento
    $stmt = $conn->prepare("
        UPDATE chaflow_schedules 
        SET phone = ?, scheduled_for = ?
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    
    $stmt->bind_param('ssii', $data['phone'], $scheduledFor, $data['schedule_id'], $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento atualizado com sucesso'
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