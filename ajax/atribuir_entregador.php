<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['entrega_id']) || !isset($_POST['entregador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    $entrega_id = (int)$_POST['entrega_id'];
    $entregador_id = (int)$_POST['entregador_id'];

    // Iniciar transação
    $conn->begin_transaction();

    // Verificar se o entregador está disponível
    $stmt = $conn->prepare("SELECT status FROM entregadores WHERE id = ?");
    $stmt->bind_param('i', $entregador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entregador = $result->fetch_assoc();

    if (!$entregador) {
        throw new Exception('Entregador não encontrado');
    }

    if ($entregador['status'] !== 'Ativo') {
        throw new Exception('Entregador não está disponível');
    }

    // Atualizar status do entregador
    $stmt = $conn->prepare("UPDATE entregadores SET status = 'Em Entrega' WHERE id = ?");
    $stmt->bind_param('i', $entregador_id);
    $stmt->execute();

    // Atualizar entrega com hora atual
    $stmt = $conn->prepare("
        UPDATE entregas 
        SET entregador_id = ?, 
            status = 'Em Rota',
            hora_saida = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('ii', $entregador_id, $entrega_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Entregador atribuído com sucesso'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atribuir entregador: ' . $e->getMessage()
    ]);
}

$conn->close();