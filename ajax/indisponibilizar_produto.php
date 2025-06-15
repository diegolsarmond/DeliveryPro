<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do produto não informado']);
    exit;
}

try {
    $produto_id = (int)$_POST['id'];
    
    // Iniciar transação
    $conn->begin_transaction();
    
    // Buscar produto original
    $stmt = $conn->prepare("SELECT * FROM produtos_delivery WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }
    
    // Inserir na tabela de indisponíveis
    $stmt = $conn->prepare("INSERT INTO produtos_indisponiveis (item, valor, categoria_id, produto_original_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdsi", 
        $produto['item'], 
        $produto['valor'], 
        $produto['categoria_id'], 
        $produto_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir produto na tabela de indisponíveis: ' . $stmt->error);
    }
    
    // Deletar da tabela original
    $stmt = $conn->prepare("DELETE FROM produtos_delivery WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao remover produto da tabela original: ' . $stmt->error);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Produto indisponibilizado com sucesso']);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Erro ao indisponibilizar produto: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao indisponibilizar produto: ' . $e->getMessage()
    ]);
}

$conn->close(); 