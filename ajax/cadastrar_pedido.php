<?php
// Desativar a exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de saída
ob_start();

session_start();
require_once '../database/db.php';

// Limpar qualquer saída anterior
ob_clean();

// Configurar headers para UTF-8
header('Content-Type: application/json; charset=utf-8');

// Garantir que a entrada também seja UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Validar dados recebidos
    $campos_obrigatorios = ['nome', 'telefone', 'cep', 'rua', 'bairro', 'itens', 'total', 'pagamento'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("Campo {$campo} é obrigatório");
        }
    }

    // Gerar número do pedido (4 dígitos aleatórios)
    $numero_pedido = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Limpar o valor total (remover R$ e converter vírgula para ponto)
    $total = $_POST['total']; // Mantém o formato R$ X,XX
    
    // Formatar a data no formato brasileiro
    $data_atual = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $data_formatada = $data_atual->format('d/m/Y H:i');

    // Adicionar os novos campos
    $sub_total = $_POST['sub_total'];
    $taxa_entrega = $_POST['taxa_entrega'];
    $zona_entrega = $_POST['zona_entrega'];

    // Preparar a query
    $stmt = $conn->prepare("
        INSERT INTO cliente (
            nome, telefone, cep, rua, bairro, complemento, 
            observacao, pedido, itens, sub_total, taxa_entrega, 
            zona_entrega, total, pagamento, data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        throw new Exception("Erro na preparação da query: " . $conn->error);
    }
    
    $complemento = $_POST['complemento'] ?? '';
    $observacao = $_POST['observacao'] ?? '';
    
    $stmt->bind_param(
        "sssssssssssssss",
        $_POST['nome'],
        $_POST['telefone'],
        $_POST['cep'],
        $_POST['rua'],
        $_POST['bairro'],
        $complemento,
        $observacao,
        $numero_pedido,
        $_POST['itens'],
        $sub_total,
        $taxa_entrega,
        $zona_entrega,
        $total,
        $_POST['pagamento'],
        $data_formatada
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Pedido cadastrado com sucesso',
            'pedido_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception('Erro ao cadastrar pedido: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();