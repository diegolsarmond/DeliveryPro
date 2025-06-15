<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

// Log para debug
error_log("Iniciando atualização de permissões");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Acesso não autorizado: user_id=" . ($_SESSION['user_id'] ?? 'none') . ", role=" . ($_SESSION['role'] ?? 'none'));
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Dados recebidos: " . json_encode($data));
    
    if (!$data || !isset($data['user_id']) || !isset($data['permissions'])) {
        throw new Exception('Dados inválidos');
    }

    $conn->begin_transaction();

    // Atualizar role do usuário
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $data['permissions']['role'], $data['user_id']);
    if (!$stmt->execute()) {
        throw new Exception('Erro ao atualizar role: ' . $stmt->error);
    }

    // Converter valores para inteiros
    $userId = (int)$data['user_id'];
    $dashboardAccess = (int)$data['permissions']['dashboard_access'];
    $pedidosAccess = (int)$data['permissions']['pedidos_access'];
    $movimentacaoAccess = (int)$data['permissions']['movimentacao_access'];
    $evolutionAccess = (int)$data['permissions']['evolution_access'];
    $typebotAccess = (int)$data['permissions']['typebot_access'];
    $settingsAccess = (int)$data['permissions']['settings_access'];
    $customizationAccess = (int)$data['permissions']['customization_access'];
    $statsAccess = (int)$data['permissions']['stats_access'];

    // Verificar se já existem permissões para o usuário
    $stmt = $conn->prepare("SELECT id FROM user_permissions WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Atualizar permissões existentes
        $stmt = $conn->prepare("
            UPDATE user_permissions SET 
                dashboard_access = ?,
                pedidos_access = ?,
                movimentacao_access = ?,
                evolution_access = ?,
                typebot_access = ?,
                settings_access = ?,
                customization_access = ?,
                stats_access = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("iiiiiiiii",
            $dashboardAccess,
            $pedidosAccess,
            $movimentacaoAccess,
            $evolutionAccess,
            $typebotAccess,
            $settingsAccess,
            $customizationAccess,
            $statsAccess,
            $userId
        );
    } else {
        // Inserir novas permissões
        $stmt = $conn->prepare("
            INSERT INTO user_permissions (
                user_id,
                dashboard_access,
                pedidos_access,
                movimentacao_access,
                evolution_access,
                typebot_access,
                settings_access,
                customization_access,
                stats_access
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiiiiii",
            $userId,
            $dashboardAccess,
            $pedidosAccess,
            $movimentacaoAccess,
            $evolutionAccess,
            $typebotAccess,
            $settingsAccess,
            $customizationAccess,
            $statsAccess
        );
    }

    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar permissões: ' . $stmt->error);
    }

    $conn->commit();
    error_log("Permissões atualizadas com sucesso");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro na atualização de permissões: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close(); 