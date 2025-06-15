<?php
// Desativar a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de saída
ob_start();

session_start();
require_once '../database/db.php';
require_once '../classes/EvolutionAPI.php';

// Limpar qualquer saída anterior
ob_clean();

// Configurar headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Debug dos dados recebidos
    error_log('Dados recebidos POST: ' . print_r($_POST, true));
    
    // Verificar se todos os dados necessários foram recebidos
    if (!isset($_POST['mensagem_id']) || !isset($_POST['entrega_id']) || !isset($_POST['entrega_data'])) {
        throw new Exception('Dados incompletos');
    }

    // Decodificar os dados da entrega
    $dados = json_decode($_POST['entrega_data'], true);
    if (!$dados || !isset($dados['entrega']) || !isset($dados['pedido'])) {
        throw new Exception('Erro ao decodificar dados da entrega: ' . json_last_error_msg());
    }

    $entrega = $dados['entrega'];
    $pedido = $dados['pedido'];

    // Debug dos dados da entrega
    error_log('ID do entregador: ' . print_r($entrega['entregador_id'], true));
    
    // Buscar telefone do entregador com debug
    $stmt = $conn->prepare("SELECT id, telefone FROM entregadores WHERE id = ?");
    $stmt->bind_param("i", $entrega['entregador_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug da query
    error_log('Query executada para buscar entregador. Rows: ' . $result->num_rows);
    
    $entregador = $result->fetch_assoc();

    if (!$entregador) {
        error_log('Entregador não encontrado para o ID: ' . $entrega['entregador_id']);
        throw new Exception('Entregador não encontrado. ID: ' . $entrega['entregador_id']);
    }

    // Debug dos dados do entregador
    error_log('Dados do entregador encontrado: ' . print_r($entregador, true));

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
    $mensagem_formatada = str_replace('$nome_entregador', $entrega['entregador_nome'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$pedido', $pedido['numero_pedido'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$cliente', $pedido['nome'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$endereco', $pedido['rua'] . ', ' . $pedido['bairro'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$total', $pedido['total'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$taxa', $entrega['taxa_entrega'], $mensagem_formatada);
    $mensagem_formatada = str_replace('$itens', $pedido['itens'], $mensagem_formatada);

    // Debug
    error_log('Mensagem formatada: ' . $mensagem_formatada);

    // Instanciar a classe EvolutionAPI
    $evolution = new EvolutionAPI($conn);

    // Enviar a mensagem
    $resultado = $evolution->sendMessage($entregador['telefone'], $mensagem_formatada);

    // Debug
    error_log('Resultado do envio: ' . print_r($resultado, true));

    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada com sucesso'
        ]);
    } else {
        throw new Exception($resultado['message']);
    }

} catch (Exception $e) {
    error_log('Erro na execução: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
    ]);
}

// Garantir que nenhuma saída adicional seja enviada
$output = ob_get_clean();
if (json_decode($output) === null) {
    error_log('Saída inválida detectada: ' . $output);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
} else {
    echo $output;
}
?>