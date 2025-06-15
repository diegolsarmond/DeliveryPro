<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? null;
    
    if (!$id || !$nome) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE categorias_delivery SET item = ? WHERE id = ?");
        $stmt->bind_param("si", $nome, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoria atualizada com sucesso']);
        } else {
            throw new Exception('Erro ao atualizar categoria');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 