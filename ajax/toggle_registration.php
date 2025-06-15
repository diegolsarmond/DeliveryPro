<?php
session_start();
require_once '../database/db.php';

// Verificar se é admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allow = isset($_POST['allow']) ? (int)$_POST['allow'] : 0;
    
    // Verificar se o registro já existe
    $stmt = $conn->prepare("SELECT id FROM system_settings WHERE setting_name = 'allow_registration'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Atualizar configuração existente
        $stmt = $conn->prepare("UPDATE system_settings SET value = ? WHERE setting_name = 'allow_registration'");
    } else {
        // Inserir nova configuração
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_name, value) VALUES ('allow_registration', ?)");
    }
    
    $stmt->bind_param("s", $allow);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar configuração']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
} 