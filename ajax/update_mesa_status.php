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
    
    if (!isset($data['mesa_id']) || !isset($data['status'])) {
        throw new Exception('Dados inválidos');
    }

    $stmt = $conn->prepare("UPDATE mesas SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $data['status'], $data['mesa_id']);
    
    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    // Se a mesa está sendo liberada, verificar se há pedidos pendentes
    if ($data['status'] === 'Livre') {
        $stmt = $conn->prepare("UPDATE cliente SET mesa_id = NULL WHERE mesa_id = ? AND status IN ('Pendente', 'Em Preparo')");
        $stmt->bind_param('i', $data['mesa_id']);
        $stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status da mesa atualizado com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar status da mesa: ' . $e->getMessage()
    ]);
}

$conn->close(); 