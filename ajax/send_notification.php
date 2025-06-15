<?php
session_start();
require_once '../database/db.php';
require_once '../classes/EvolutionAPI.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Verificar se todos os dados necessários foram recebidos
    if (!isset($_POST['mensagem_id']) || !isset($_POST['pedido_id']) || !isset($_POST['pedido_data'])) {
        throw new Exception('Dados incompletos');
    }

    // Decodificar os dados do pedido
    $pedido = json_decode($_POST['pedido_data'], true);
    if (!$pedido) {
        throw new Exception('Erro ao decodificar dados do pedido');
    }

    // Buscar template da mensagem
    $stmt = $conn->prepare("SELECT template FROM mensagens_personalizadas WHERE id = ?");
    $stmt->bind_param("i", $_POST['mensagem_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $mensagem = $result->fetch_assoc();

    if (!$mensagem) {
        throw new Exception('Mensagem não encontrada');
    }

    // Substituir as variáveis no template
    $template = $mensagem['template'];
    $mensagem_formatada = $template;
    $mensagem_formatada = str_replace('$nome', $pedido['nome'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$pedido', $pedido['pedido'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$total', $pedido['total'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$status', $pedido['status'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$endereco', $pedido['rua'] . ', ' . $pedido['bairro'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$telefone', $pedido['telefone'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$itens', $pedido['itens'], $mensagem_formatada);

    // Instanciar a classe EvolutionAPI
    $evolution = new EvolutionAPI($conn);

    // Enviar a mensagem
    $resultado = $evolution->sendMessage($pedido['telefone'], $mensagem_formatada);

    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso'
        ]);
    } else {
        throw new Exception($resultado['message']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
    ]);
}
?>