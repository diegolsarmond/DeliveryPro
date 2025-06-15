<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $valor = $_POST['valor'] ?? null;
    $categoria = $_POST['categoria'] ?? null;
    
    if (!$id || !$nome || !$valor || !$categoria) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit;
    }

    try {
        // Formatar o valor
        $valor = str_replace(',', '.', $valor);
        $valor = number_format((float)$valor, 2, '.', '');
        
        // Buscar o id_categoria correto
        $stmt = $conn->prepare("SELECT id_categoria FROM categorias_delivery WHERE id = ?");
        $stmt->bind_param("i", $categoria);
        $stmt->execute();
        $result = $stmt->get_result();
        $categoria_data = $result->fetch_assoc();
        
        if (!$categoria_data) {
            throw new Exception('Categoria não encontrada');
        }
        
        $categoria_id = $categoria_data['id_categoria'];
        
        // Atualizar o produto com o id_categoria correto
        $stmt = $conn->prepare("UPDATE produtos_delivery SET item = ?, valor = ?, categoria_id = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $valor, $categoria_id, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Produto atualizado com sucesso']);
        } else {
            throw new Exception('Erro ao atualizar produto');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 