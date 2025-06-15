<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Ação não especificada'];

switch ($action) {
    case 'add_categoria':
        $item = $_POST['nome'];
        $id_categoria = $_POST['id'];
        
        $stmt = $conn->prepare("INSERT INTO categorias_delivery (item, id_categoria) VALUES (?, ?)");
        $stmt->bind_param("ss", $item, $id_categoria);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Categoria adicionada com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao adicionar categoria'];
        }
        break;

    case 'edit_categoria':
        $id = $_POST['id'];
        $item = $_POST['nome'];
        $id_categoria = $_POST['novo_id'];
        
        $stmt = $conn->prepare("UPDATE categorias_delivery SET item = ?, id_categoria = ? WHERE id = ?");
        $stmt->bind_param("ssi", $item, $id_categoria, $id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Categoria atualizada com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao atualizar categoria'];
        }
        break;

    case 'delete_categoria':
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM categorias_delivery WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Categoria excluída com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao excluir categoria'];
        }
        break;

    case 'add_produto':
        $item = $_POST['nome'];
        $valor = $_POST['valor'];
        $categoria_id = $_POST['categoria'];
        
        $stmt = $conn->prepare("INSERT INTO produtos_delivery (item, valor, categoria_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $item, $valor, $categoria_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Produto adicionado com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao adicionar produto'];
        }
        break;

    case 'edit_produto':
        $id = $_POST['id'];
        $item = $_POST['nome'];
        $valor = $_POST['valor'];
        $categoria_id = $_POST['categoria'];
        
        $stmt = $conn->prepare("UPDATE produtos_delivery SET item = ?, valor = ?, categoria_id = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $item, $valor, $categoria_id, $id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Produto atualizado com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao atualizar produto'];
        }
        break;

    case 'delete_produto':
        $id = $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM produtos_delivery WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Produto excluído com sucesso'];
        } else {
            $response = ['success' => false, 'message' => 'Erro ao excluir produto'];
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($response); 