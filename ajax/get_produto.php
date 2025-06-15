<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

if (!isset($_GET['id'])) {
    exit(json_encode(['success' => false, 'message' => 'ID não fornecido']));
}

try {
    $stmt = $conn->prepare("SELECT * FROM produtos_delivery WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $produto = $result->fetch_assoc();
    
    if ($produto) {
        // Formatar o valor para exibição
        $produto['valor'] = number_format((float)$produto['valor'], 2, ',', '');
        echo json_encode($produto);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 