<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    // Verificar se o entregador está em entrega
    $stmt = $conn->prepare("SELECT status FROM entregadores WHERE id = ?");
    $stmt->bind_param('i', $_POST['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $entregador = $result->fetch_assoc();

    if ($entregador['status'] == 'Em Entrega') {
        throw new Exception('Não é possível excluir um entregador que está em entrega');
    }

    // Excluir entregador
    $stmt = $conn->prepare("DELETE FROM entregadores WHERE id = ?");
    $stmt->bind_param('i', $_POST['id']);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir entregador: ' . $e->getMessage()
    ]);
}

$conn->close(); 