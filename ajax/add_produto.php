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
    $valor = $_POST['valor'] ?? null;
    $categoria = $_POST['categoria'] ?? null;
    
    if (!$nome || !$valor || !$categoria) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit;
    }

    try {
        $valor = str_replace(',', '.', $valor);
        $valor = number_format((float)$valor, 2, '.', '');
        
        $stmt = $conn->prepare("SELECT id_categoria FROM categorias_delivery WHERE id = ?");
        $stmt->bind_param("i", $categoria);
        $stmt->execute();
        $result = $stmt->get_result();
        $categoria_data = $result->fetch_assoc();
        
        if (!$categoria_data) {
            throw new Exception('Categoria não encontrada');
        }
        
        $categoria_id = $categoria_data['id_categoria'];
        
        $stmt = $conn->prepare("INSERT INTO produtos_delivery (item, valor, categoria_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $nome, $valor, $categoria_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produto adicionado com sucesso']);
        } else {
            throw new Exception('Erro ao adicionar produto');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 