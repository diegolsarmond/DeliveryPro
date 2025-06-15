<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT s.*, f.name as flow_name 
        FROM chaflow_schedules s
        JOIN chaflow_flows f ON f.id = s.flow_id
        WHERE s.user_id = ? 
        AND s.status = 'pending'
        ORDER BY s.scheduled_for ASC
    ");
    
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        // Converter para timezone local
        $scheduledFor = new DateTime($row['scheduled_for']);
        $scheduledFor->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        
        $schedules[] = [
            'id' => $row['id'],
            'flow_id' => $row['flow_id'],
            'flow_name' => $row['flow_name'],
            'phone' => $row['phone'],
            'scheduled_for' => $scheduledFor->format('Y-m-d H:i:s'),
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 