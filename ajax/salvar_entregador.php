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
        INSERT INTO entregadores (nome, telefone, documento, veiculo, placa, status)
        VALUES (?, ?, ?, ?, ?, 'Ativo')
    ");
    
    $stmt->bind_param('sssss', 
        $data['nome'],
        $data['telefone'],
        $data['documento'],
        $data['veiculo'],
        $data['placa']
    );
    
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao salvar entregador: ' . $e->getMessage()
    ]);
}

$conn->close(); 