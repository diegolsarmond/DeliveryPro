<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    $id = (int)$_GET['id'];
    error_log("Buscando detalhes para ID: " . $id);
    
    // Primeiro busca o pedido
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($pedido = $result->fetch_assoc()) {
        error_log("Encontrado pedido: " . json_encode($pedido));
        
        // Agora busca se tem entrega associada
        $stmt = $conn->prepare("
            SELECT e.*, ent.nome as entregador_nome
            FROM entregas e
            LEFT JOIN entregadores ent ON e.entregador_id = ent.id
            WHERE e.pedido_id = ?
        ");
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $entrega = $result->fetch_assoc();
        
        $response = [
            'success' => true,
            'pedido' => $pedido
        ];
        
        if ($entrega) {
            $response['entrega'] = [
                'id' => $entrega['id'],
                'status' => $entrega['status'],
                'taxa_entrega' => number_format($entrega['taxa_entrega'], 2, ',', '.'),
                'entregador_nome' => $entrega['entregador_nome'],
                'hora_saida' => $entrega['hora_saida'],
                'hora_entrega' => $entrega['hora_entrega']
            ];
        }
        
        echo json_encode($response);
    } else {
        error_log("Pedido não encontrado");
        throw new Exception('Pedido não encontrado');
    }
    
} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()
    ]);
}

$conn->close(); 