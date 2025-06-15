<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    if (!isset($_POST['id'])) {
        throw new Exception('ID da região não informado');
    }

    $id = (int)$_POST['id'];
    
    $stmt = $conn->prepare("DELETE FROM taxas_entrega WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Região removida com sucesso']);
    } else {
        throw new Exception('Erro ao remover região');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close(); 