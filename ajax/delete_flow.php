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
        throw new Exception('ID do fluxo não informado');
    }

    // Verificar se é o fluxo padrão (ID 1)
    if ($data['id'] == 1) {
        throw new Exception('O fluxo padrão não pode ser excluído');
    }

    // Verificar se é o fluxo padrão (ID 1)
    if ($data['id'] == 1) {
        throw new Exception('O fluxo padrão não pode ser excluído');
    }

    $stmt = $conn->prepare("
        DELETE FROM chaflow_flows 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param('ii', $data['id'], $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir fluxo');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fluxo excluído com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 