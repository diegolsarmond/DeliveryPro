<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Receber dados do fluxo
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name'])) {
        throw new Exception('Nome do fluxo é obrigatório');
    }

    // Preparar dados para inserção
    $user_id = $_SESSION['user_id'];
    $name = $data['name'];
    $description = $data['description'] ?? '';
    $nodes = isset($data['nodes']) ? json_encode($data['nodes']) : '[]';
    $connections = isset($data['connections']) ? json_encode($data['connections']) : '[]';
    
    // Inserir novo fluxo
    $stmt = $conn->prepare("
        INSERT INTO chaflow_flows (user_id, name, description, nodes, connections, active) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('issssi', 
        $_SESSION['user_id'],
        $data['name'],
        $data['description'],
        $nodes,
        $connections,
        $data['active']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar fluxo: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fluxo salvo com sucesso',
        'flow_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 