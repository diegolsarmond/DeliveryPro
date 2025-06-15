<?php
// Desativa a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

session_start();

// Inclui o arquivo de conexão
if (file_exists('../database/db.php')) {
    require_once '../database/db.php';
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Arquivo de conexão não encontrado']);
    exit;
}

// Verifica se a conexão foi estabelecida
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Verifica se os dados necessários foram enviados
if (!isset($_POST['user_id']) || !isset($_POST['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

$user_id = intval($_POST['user_id']);
$role = $_POST['role'];

// Verifica se a role é válida
if (!in_array($role, ['user', 'admin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Função inválida']);
    exit;
}

try {
    // Atualiza a função do usuário
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("si", $role, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar função: " . $stmt->error);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Erro em save_user_role.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
