<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? null;
    
    if (!$nome) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
        exit;
    }

    try {
        // Buscar o último ID da tabela
        $stmt = $conn->prepare("SELECT MAX(CAST(id_categoria AS UNSIGNED)) as ultimo_id FROM categorias_delivery");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Gerar próximo ID numérico
        $proximo_id = ($result['ultimo_id'] ?? 0) + 1;
        
        // Inserir nova categoria com ID sequencial
        $stmt = $conn->prepare("INSERT INTO categorias_delivery (item, id_categoria) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $proximo_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoria adicionada com sucesso']);
        } else {
            throw new Exception('Erro ao adicionar categoria');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 