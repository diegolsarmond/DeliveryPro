<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID não informado');
    }

    // Verificar se a mensagem existe
    $stmt = $conn->prepare("SELECT id FROM mensagens_personalizadas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Mensagem não encontrada');
    }

    // Excluir a mensagem
    $stmt = $conn->prepare("DELETE FROM mensagens_personalizadas WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir mensagem: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Mensagem excluída com sucesso'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Fechar conexões
if (isset($stmt)) {
    $stmt->close();
}
$conn->close(); 