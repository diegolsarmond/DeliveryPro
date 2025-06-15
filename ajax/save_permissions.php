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
if (!isset($_POST['user_id']) || !isset($_POST['permission']) || !isset($_POST['value'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

$user_id = intval($_POST['user_id']);
$permission = $_POST['permission'];
$value = $_POST['value'] === 'true' ? 1 : 0;

// Lista de permissões válidas
$valid_permissions = [
    'dashboard_access',
    'pedidos_access',
    'movimentacao_access',
    'evolution_access',
    'typebot_access',
    'settings_access',
    'customization_access',
    'stats_access',
    'pos_access',
    'chaflow_access'
];

// Verifica se a permissão é válida
if (!in_array($permission, $valid_permissions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Permissão inválida: ' . $permission]);
    exit;
}

try {
    // Log para debug
    error_log("Tentando salvar permissão: user_id=$user_id, permission=$permission, value=$value");

    // Primeiro, verifica se o usuário existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta de usuário: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao verificar usuário: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("Usuário não encontrado: user_id=$user_id");
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }

    // Verifica se já existe um registro para este usuário
    $stmt = $conn->prepare("SELECT id FROM user_permissions WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Se não existir, insere todas as permissões padrão
        $stmt = $conn->prepare("INSERT INTO user_permissions (
            user_id, 
            dashboard_access,
            pedidos_access,
            movimentacao_access,
            evolution_access,
            typebot_access,
            settings_access,
            customization_access,
            stats_access,
            pos_access,
            chaflow_access
        ) VALUES (?, 1, 1, 1, 0, 0, 0, 0, 0, 1, 0)");
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar inserção: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao inserir permissões: " . $stmt->error);
        }
    }

    // Agora atualiza a permissão específica
    $stmt = $conn->prepare("UPDATE user_permissions SET $permission = ? WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Erro ao preparar atualização: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $value, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar permissão: " . $stmt->error);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Erro em save_permissions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
