<?php
session_start();
require_once '../database/db.php';
require_once '../classes/EvolutionAPI.php';

// Garantir que a saída seja sempre JSON
header('Content-Type: application/json');
error_reporting(0); // Desabilitar exibição de erros PHP
ini_set('display_errors', 0);

// Limpar qualquer saída anterior
ob_clean();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    if (!isset($_POST['mensagem_id']) || !isset($_POST['entrega_id'])) {
        throw new Exception('Parâmetros inválidos');
    }

    $mensagem_id = (int)$_POST['mensagem_id'];
    $entrega_id = (int)$_POST['entrega_id'];

    // Buscar a mensagem personalizada
    $stmt = $conn->prepare("SELECT template FROM mensagens_personalizadas WHERE id = ?");
    $stmt->bind_param("i", $mensagem_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->num_rows) {
        throw new Exception('Mensagem não encontrada');
    }

    $mensagem = $result->fetch_assoc()['template'];

    // Buscar dados da entrega com o número do pedido correto e dados do entregador
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            c.pedido as numero_pedido,
            c.nome as cliente_nome,
            c.total,
            c.rua,
            c.bairro,
            c.complemento,
            c.cep,
            ent.telefone as entregador_telefone,
            ent.nome as entregador_nome
        FROM entregas e
        JOIN cliente c ON e.pedido_id = c.id
        JOIN entregadores ent ON e.entregador_id = ent.id
        WHERE e.id = ?
    ");
    
    $stmt->bind_param("i", $entrega_id);
    $stmt->execute();
    $entrega = $stmt->get_result()->fetch_assoc();

    if (!$entrega) {
        throw new Exception('Entrega não encontrada');
    }

    if (empty($entrega['entregador_telefone'])) {
        throw new Exception('Telefone do entregador não encontrado');
    }

    // Formatar endereço completo
    $endereco = $entrega['rua'] . ', ' . 
               $entrega['bairro'] . 
               ($entrega['complemento'] ? ' - ' . $entrega['complemento'] : '') . 
               ' (CEP: ' . $entrega['cep'] . ')';

    // Substituir variáveis na mensagem
    $mensagem = str_replace('$pedido', $entrega['numero_pedido'], $mensagem);
    $mensagem = str_replace('$cliente', $entrega['cliente_nome'], $mensagem);
    $mensagem = str_replace('$total', $entrega['total'], $mensagem);
    $mensagem = str_replace('$status', 'Em Rota', $mensagem);
    $mensagem = str_replace('$nome_entregador', $entrega['entregador_nome'], $mensagem);
    $mensagem = str_replace('$endereco', $endereco, $mensagem);

    // Instanciar a classe EvolutionAPI
    $evolution = new EvolutionAPI($conn);

    // Enviar a mensagem para o número do entregador
    $resultado = $evolution->sendMessage($entrega['entregador_telefone'], $mensagem);

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
} finally {
    // Garantir que a conexão seja fechada
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
} 