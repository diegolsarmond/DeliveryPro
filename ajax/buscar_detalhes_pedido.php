<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
    exit;
}

try {
    $pedido_id = (int)$_GET['id'];
    
    // Buscar apenas dados do pedido da tabela cliente
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id = ?");
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($pedido = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'pedido' => [
                'pedido' => $pedido['pedido'],
                'nome' => $pedido['nome'],
                'telefone' => $pedido['telefone'],
                'rua' => $pedido['rua'],
                'bairro' => $pedido['bairro'],
                'complemento' => $pedido['complemento'],
                'observacao' => $pedido['observacao'],
                'itens' => $pedido['itens'],
                'sub_total' => $pedido['sub_total'],
                'taxa_entrega' => $pedido['taxa_entrega'],
                'total' => $pedido['total'],
                'pagamento' => $pedido['pagamento'],
                'status' => $pedido['status'],
                'data' => $pedido['data']
            ]
        ]);
    } else {
        throw new Exception('Pedido não encontrado');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()
    ]);
}

$conn->close(); 