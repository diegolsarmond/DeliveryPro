<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['entrega_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    $entrega_id = (int)$_POST['entrega_id'];

    // Iniciar transação
    $conn->begin_transaction();

    // Buscar informações da entrega
    $stmt = $conn->prepare("SELECT entregador_id, pedido_id FROM entregas WHERE id = ?");
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    $entrega = $stmt->get_result()->fetch_assoc();

    if (!$entrega) {
        throw new Exception('Entrega não encontrada');
    }

    // Atualizar status do entregador
    if ($entrega['entregador_id']) {
        $stmt = $conn->prepare("UPDATE entregadores SET status = 'Ativo' WHERE id = ?");
        $stmt->bind_param('i', $entrega['entregador_id']);
        $stmt->execute();
    }

    // Atualizar entrega
    $stmt = $conn->prepare("
        UPDATE entregas 
        SET status = 'Entregue',
            hora_entrega = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();

    // Atualizar pedido
    $stmt = $conn->prepare("UPDATE cliente SET status = 'Entregue' WHERE id = ?");
    $stmt->bind_param('i', $entrega['pedido_id']);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Entrega concluída com sucesso'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao concluir entrega: ' . $e->getMessage()
    ]);
}

$conn->close();