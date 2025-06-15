<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Validar campos obrigatórios
    $required_fields = [
        'url', 'typebot', 'triggerType', 'triggerOperator', 'triggerValue',
        'expire', 'keywordFinish', 'delayMessage', 'unknownMessage'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo obrigatório não preenchido: {$field}");
        }
    }

    // Preparar os dados
    $settings = [
        'enabled' => true,
        'url' => filter_var($_POST['url'], FILTER_SANITIZE_URL),
        'typebot' => trim($_POST['typebot']),
        'triggerType' => trim($_POST['triggerType']),
        'triggerOperator' => trim($_POST['triggerOperator']),
        'triggerValue' => trim($_POST['triggerValue']),
        'expire' => (int)$_POST['expire'],
        'keywordFinish' => trim($_POST['keywordFinish']),
        'delayMessage' => (int)$_POST['delayMessage'],
        'unknownMessage' => trim($_POST['unknownMessage']),
        'listeningFromMe' => isset($_POST['listeningFromMe']) ? filter_var($_POST['listeningFromMe'], FILTER_VALIDATE_BOOLEAN) : false,
        'stopBotFromMe' => isset($_POST['stopBotFromMe']) ? filter_var($_POST['stopBotFromMe'], FILTER_VALIDATE_BOOLEAN) : false,
        'keepOpen' => isset($_POST['keepOpen']) ? filter_var($_POST['keepOpen'], FILTER_VALIDATE_BOOLEAN) : false,
        'debounceTime' => isset($_POST['debounceTime']) ? (int)$_POST['debounceTime'] : 10
    ];

    // Validar URL do Typebot
    if (!filter_var($settings['url'], FILTER_VALIDATE_URL)) {
        throw new Exception('URL do Typebot inválida');
    }

    // Converter para JSON
    $settings_json = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if ($settings_json === false) {
        throw new Exception('Erro ao converter configurações para JSON: ' . json_last_error_msg());
    }

    // Iniciar transação
    $conn->begin_transaction();

    try {
        // Verificar se já existe configuração
        $stmt = $conn->prepare("SELECT id FROM typebot_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Atualizar configuração existente
            $stmt = $conn->prepare("UPDATE typebot_settings SET settings = ? WHERE user_id = ?");
            $stmt->bind_param("si", $settings_json, $user_id);
        } else {
            // Inserir nova configuração
            $stmt = $conn->prepare("INSERT INTO typebot_settings (user_id, settings) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $settings_json);
        }

        if (!$stmt->execute()) {
            throw new Exception("Erro ao salvar configurações: " . $stmt->error);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Configurações salvas com sucesso'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Erro ao salvar configurações do Typebot: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 