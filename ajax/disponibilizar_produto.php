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
    
    // Buscar produto indisponível
    $stmt = $conn->prepare("SELECT * FROM produtos_indisponiveis WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }
    
    // Inserir de volta na tabela original
    $stmt = $conn->prepare("INSERT INTO produtos_delivery (item, valor, categoria_id) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", 
        $produto['item'], 
        $produto['valor'], 
        $produto['categoria_id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir produto na tabela original: ' . $stmt->error);
    }
    
    // Deletar da tabela de indisponíveis
    $stmt = $conn->prepare("DELETE FROM produtos_indisponiveis WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao remover produto da tabela de indisponíveis: ' . $stmt->error);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Produto disponibilizado com sucesso']);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Erro ao disponibilizar produto: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao disponibilizar produto: ' . $e->getMessage()
    ]);
}

$conn->close(); 