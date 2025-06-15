<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Validar dados recebidos
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome_mensagem'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $template = $_POST['template'] ?? '';

    if (empty($nome) || empty($categoria) || empty($template)) {
        throw new Exception('Todos os campos são obrigatórios');
    }

    // Preparar a conexão
    if ($id) {
        // Atualizar mensagem existente
        $stmt = $conn->prepare("UPDATE mensagens_personalizadas SET nome_mensagem = ?, categoria = ?, template = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nome, $categoria, $template, $id);
    } else {
        // Inserir nova mensagem
        $stmt = $conn->prepare("INSERT INTO mensagens_personalizadas (nome_mensagem, categoria, template) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $categoria, $template);
    }

    // Executar a query
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar mensagem: ' . $stmt->error);
    }

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem salva com sucesso'
    ]);

} catch (Exception $e) {
    // Retornar erro
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Fechar conexões
if (isset($stmt)) {
    $stmt->close();
}
$conn->close(); 