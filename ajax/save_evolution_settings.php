<?php
require_once '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $base_url = trim($_POST['base_url']);

    if (empty($base_url)) {
        die(json_encode(['success' => false, 'message' => 'URL base não pode estar vazia']));
    }

    try {
        // Verificar se já existe um registro
        $stmt = $conn->prepare("SELECT id FROM evolution_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Atualizar registro existente
            $stmt = $conn->prepare("UPDATE evolution_settings SET base_url = ? WHERE user_id = ?");
            $stmt->bind_param("si", $base_url, $user_id);
        } else {
            // Inserir novo registro
            $stmt = $conn->prepare("INSERT INTO evolution_settings (user_id, base_url) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $base_url);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);
        } else {
            throw new Exception('Erro ao salvar configurações');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 