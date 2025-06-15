<?php
session_start();
require_once '../database/db.php';

// Desabilitar exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Limpar qualquer saída anterior
ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Buscar todas as mensagens
    $stmt = $conn->prepare("SELECT * FROM mensagens_personalizadas ORDER BY categoria, nome_mensagem");
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Agrupar mensagens por categoria
    $mensagens = [];
    while ($row = $result->fetch_assoc()) {
        // Escapar caracteres especiais no template
        $template = htmlspecialchars_decode($row['template']);
        
        $mensagens[$row['categoria']][] = [
            'id' => $row['id'],
            'nome_mensagem' => htmlspecialchars($row['nome_mensagem']),
            'template' => $template
        ];
    }
    
    // Garantir que nenhum outro conteúdo seja enviado
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $mensagens
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Fechar conexão
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
exit; 