<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logo_url = $_POST['logo_url'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $background_color = $_POST['background_color'] ?? '#0d524a';
    $redirect_time = (int)($_POST['redirect_time'] ?? 5);

    try {
        // Verificar se já existe configuração
        $stmt = $conn->prepare("SELECT id FROM index_customization WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE index_customization SET 
                logo_url = ?, 
                title = ?,
                description = ?,
                background_color = ?,
                redirect_time = ?
                WHERE user_id = ?");
            $stmt->bind_param("ssssii", 
                $logo_url, 
                $title,
                $description,
                $background_color,
                $redirect_time,
                $user_id
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO index_customization 
                (user_id, logo_url, title, description, background_color, redirect_time) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", 
                $user_id,
                $logo_url, 
                $title,
                $description,
                $background_color,
                $redirect_time
            );
        }

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Configurações salvas com sucesso!'
            ]);
        } else {
            throw new Exception('Erro ao salvar configurações');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} 