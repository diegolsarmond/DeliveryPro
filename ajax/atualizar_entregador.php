<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("
        UPDATE entregadores 
        SET nome = ?, telefone = ?, documento = ?, veiculo = ?, placa = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param('sssssi', 
        $data['nome'],
        $data['telefone'],
        $data['documento'],
        $data['veiculo'],
        $data['placa'],
        $data['id']
    );
    
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar entregador: ' . $e->getMessage()
    ]);
}

$conn->close(); 