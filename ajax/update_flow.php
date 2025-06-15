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
    
    if (!isset($data['id'])) {
        throw new Exception('ID do fluxo não fornecido');
    }
    
    // Preparar dados para atualização
    $flowId = $data['id'];
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $active = isset($data['active']) ? ($data['active'] ? 1 : 0) : 1; // Valor padrão é ativo
    $nodes = isset($data['nodes']) ? json_encode($data['nodes']) : '[]';
    $connections = isset($data['connections']) ? json_encode($data['connections']) : '[]';
    
    // Atualizar fluxo
    $stmt = $conn->prepare("
        UPDATE chaflow_flows 
        SET name = ?, 
            description = ?, 
            active = ?,
            nodes = ?, 
            connections = ?, 
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param('ssissii', $name, $description, $active, $nodes, $connections, $flowId, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Fluxo atualizado com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao atualizar fluxo');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 