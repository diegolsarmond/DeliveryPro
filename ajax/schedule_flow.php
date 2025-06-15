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
    
    if (!isset($data['flow_id']) || !isset($data['phone']) || !isset($data['scheduled_for'])) {
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
    
    $stmt = $conn->prepare("
        INSERT INTO chaflow_schedules (user_id, flow_id, phone, scheduled_for)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param('iiss', $_SESSION['user_id'], $data['flow_id'], $data['phone'], $scheduledFor);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Fluxo agendado com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 