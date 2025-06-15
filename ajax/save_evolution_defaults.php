<?php
session_start();
require_once('../database/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug para verificar os dados recebidos
    error_log("Dados recebidos POST: " . print_r($_POST, true));

    $default_subject = $_POST['default_subject'] ?? null;
    $default_description = $_POST['default_description'] ?? null;
    $default_participant = $_POST['default_participant'] ?? null;
    $default_picture = $_POST['default_picture'] ?? null;
    $auto_generate_invite = $_POST['auto_generate_invite'] ?? '0';
    $auto_add_to_links = $_POST['auto_add_to_links'] ?? '0';
    $auto_recreate_group = $_POST['auto_recreate_group'] ?? '0';

    // Debug para verificar os valores processados
    error_log("Valores processados:");
    error_log("auto_generate_invite: " . $auto_generate_invite);
    error_log("auto_add_to_links: " . $auto_add_to_links);
    error_log("auto_recreate_group: " . $auto_recreate_group);

    try {
        // Verificar se já existe configuração
        $stmt = $conn->prepare("SELECT id FROM evolution_group_defaults WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Debug para verificar a query de atualização
            error_log("Atualizando configurações existentes");
            
            $stmt = $conn->prepare("UPDATE evolution_group_defaults SET 
                default_subject = ?, 
                default_description = ?, 
                default_participant = ?,
                default_picture = ?,
                auto_generate_invite = ?,
                auto_add_to_links = ?,
                auto_recreate_group = ?
                WHERE user_id = ?");
            $stmt->bind_param("ssssiiii", 
                $default_subject, 
                $default_description, 
                $default_participant,
                $default_picture,
                $auto_generate_invite,
                $auto_add_to_links,
                $auto_recreate_group,
                $user_id
            );
        } else {
            // Debug para verificar a query de inserção
            error_log("Inserindo novas configurações");
            
            $stmt = $conn->prepare("INSERT INTO evolution_group_defaults 
                (user_id, default_subject, default_description, default_participant, 
                default_picture,
                auto_generate_invite, auto_add_to_links, auto_recreate_group) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiii", 
                $user_id, 
                $default_subject, 
                $default_description, 
                $default_participant,
                $default_picture,
                $auto_generate_invite,
                $auto_add_to_links,
                $auto_recreate_group
            );
        }

        if ($stmt->execute()) {
            // Verificar os valores após a execução
            error_log("Valores salvos no banco:");
            error_log("auto_recreate_group: " . $auto_recreate_group);
            
            // Buscar os valores atualizados do banco
            $verify_stmt = $conn->prepare("SELECT * FROM evolution_group_defaults WHERE user_id = ?");
            $verify_stmt->bind_param("i", $user_id);
            $verify_stmt->execute();
            $saved_data = $verify_stmt->get_result()->fetch_assoc();
            error_log("Valores verificados no banco: " . print_r($saved_data, true));

            echo json_encode([
                'success' => true, 
                'message' => 'Configurações padrão salvas com sucesso!'
            ]);
        } else {
            throw new Exception('Erro ao salvar configurações: ' . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Método não permitido'
    ]);
} 