<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $conn->begin_transaction();

    // Salvar configurações gerais
    $stmt = $conn->prepare("
        UPDATE config_entregas 
        SET horario_inicio = ?,
            horario_fim = ?,
            dias_funcionamento = ?
    ");
    
    $dias = isset($_POST['dias']) ? implode(',', $_POST['dias']) : '';
    $stmt->bind_param("sss", 
        $_POST['horario_inicio'],
        $_POST['horario_fim'],
        $dias
    );
    $stmt->execute();

    // Limpar taxas antigas
    $conn->query("DELETE FROM taxas_entrega");

    // Inserir novas taxas
    if (isset($_POST['regioes']) && isset($_POST['valores'])) {
        $stmt = $conn->prepare("INSERT INTO taxas_entrega (regiao, valor) VALUES (?, ?)");
        
        foreach ($_POST['regioes'] as $i => $regiao) {
            if (!empty($regiao) && isset($_POST['valores'][$i])) {
                $valor = floatval($_POST['valores'][$i]);
                $stmt->bind_param("sd", $regiao, $valor);
                $stmt->execute();
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações: ' . $e->getMessage()]);
}

$conn->close(); 