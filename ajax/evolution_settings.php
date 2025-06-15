<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Validar a URL base
    $base_url = filter_var($_POST['base_url'], FILTER_SANITIZE_URL);
    if (!filter_var($base_url, FILTER_VALIDATE_URL)) {
        throw new Exception('URL base inválida');
    }

    // Validar API key global
    $api_global = trim($_POST['api_key']);
    if (empty($api_global)) {
        throw new Exception('API key global é obrigatória');
    }

    // Verificar se já existe configuração para este usuário
    $stmt = $conn->prepare("SELECT id FROM evolution_settings WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Atualizar configuração existente
        $stmt = $conn->prepare("UPDATE evolution_settings SET base_url = ?, api_global = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $base_url, $api_global, $user_id);
    } else {
        // Inserir nova configuração
        $stmt = $conn->prepare("INSERT INTO evolution_settings (user_id, base_url, api_global) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $base_url, $api_global);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao salvar configurações');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();