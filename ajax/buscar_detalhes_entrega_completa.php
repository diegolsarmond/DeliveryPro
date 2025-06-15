<?php
session_start();
require_once '../database/db.php';

// Desabilitar exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Garantir que a saída seja JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da entrega não informado']);
    exit;
}

try {
    $entrega_id = (int)$_GET['id'];
    
    // Buscar detalhes da entrega junto com dados do pedido e entregador
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            c.*,
            ent.id as entregador_id,
            ent.nome as entregador_nome,
            ent.telefone as entregador_telefone,
            c.pedido as numero_pedido,
            c.taxa_entrega as taxa_entrega
        FROM entregas e
        LEFT JOIN cliente c ON e.pedido_id = c.id
        LEFT JOIN entregadores ent ON e.entregador_id = ent.id
        WHERE e.id = ?
    ");
    
    $stmt->bind_param('i', $entrega_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Separar dados da entrega e do pedido
        $entrega = [
            'id' => $row['id'],
            'status' => $row['status'],
            'entregador_id' => $row['entregador_id'],
            'entregador_nome' => $row['entregador_nome'],
            'entregador_telefone' => $row['entregador_telefone'],
            'hora_saida' => $row['hora_saida'],
            'hora_entrega' => $row['hora_entrega']
        ];
        
        $pedido = [
            'numero_pedido' => $row['numero_pedido'],
            'nome' => $row['nome'],
            'telefone' => $row['telefone'],
            'rua' => $row['rua'],
            'bairro' => $row['bairro'],
            'complemento' => $row['complemento'],
            'cep' => $row['cep'],
            'observacao' => $row['observacao'],
            'itens' => $row['itens'],
            'sub_total' => $row['sub_total'],
            'total' => $row['total'],
            'pagamento' => $row['pagamento'],
            'taxa_entrega' => $row['taxa_entrega']
        ];
        
        echo json_encode([
            'success' => true,
            'entrega' => $entrega,
            'pedido' => $pedido
        ]);
    } else {
        throw new Exception('Entrega não encontrada');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()
    ]);
}

$conn->close(); 