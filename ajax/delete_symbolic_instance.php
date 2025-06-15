<?php
session_start();
require_once '../database/db.php';

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Verifica se o ID foi fornecido
if (!isset($_POST['id']) || $_POST['id'] != 1) {
    die(json_encode(['success' => false, 'message' => 'ID inválido']));
}

try {
    // Prepara e executa a query de exclusão
    $stmt = $conn->prepare("DELETE FROM evolution_instances WHERE id = 1");
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Instância excluída com sucesso']);
    } else {
        throw new Exception('Erro ao excluir instância');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 