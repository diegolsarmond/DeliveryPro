<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("
        UPDATE cliente 
        SET nome = ?, 
            telefone = ?, 
            cep = ?, 
            rua = ?, 
            bairro = ?, 
            complemento = ?,
            total = ?,
            pagamento = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param('ssssssssi', 
        $data['nome'],
        $data['telefone'],
        $data['cep'],
        $data['rua'],
        $data['bairro'],
        $data['complemento'],
        $data['total'],
        $data['pagamento'],
        $data['id']
    );
    
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar pedido: ' . $e->getMessage()
    ]);
}

$conn->close(); 