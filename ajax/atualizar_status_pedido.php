<?php
// Garantir que nenhuma saída seja enviada antes do JSON
ob_clean();
header('Content-Type: application/json');

// Incluir arquivo de conexão
require_once '../database/db.php';

try {
    $conn = getConnection();

    // Verificar se os parâmetros necessários foram enviados
    if (!isset($_POST['pedido_id']) || !isset($_POST['status'])) {
        throw new Exception('Parâmetros inválidos');
    }

    $pedido_id = $_POST['pedido_id'];
    $status = $_POST['status'];

    // Validar o status
    $status_validos = [
        'Pendente',
        'Em Preparo',
        'Pronto para Entrega',
        'Entregue',
        'Cancelado',
        'Finalizado'
    ];

    if (!in_array($status, $status_validos)) {
        throw new Exception('Status inválido');
    }

    $stmt = $conn->prepare("UPDATE cliente SET status = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erro na preparação da query: " . $conn->error);
    }

    $stmt->bind_param("si", $status, $pedido_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
    } else {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
} 