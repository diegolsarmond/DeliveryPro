<?php
// Desativar exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de saída
ob_start();

session_start();
require_once '../database/db.php';

// Limpar qualquer saída anterior
ob_clean();

// Configurar header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Dados inválidos');
    }

    // Formatar data atual
    $data_atual = date('d/m/Y H:i');
    
    // Gerar número do pedido (4 dígitos aleatórios)
    $numero_pedido = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Preparar lista de itens
    $itens_lista = array_map(function($item) {
        return $item['name'] . ' (' . $item['quantity'] . 'x)';
    }, $data['items']);
    
    $itens = implode(', ', $itens_lista);
    
    // Calcular subtotal
    $subtotal = array_reduce($data['items'], function($carry, $item) {
        return $carry + ($item['price'] * $item['quantity']);
    }, 0);
    
    // Usar valores padrão se nome ou telefone não forem informados
    $nome_cliente = !empty($data['customer']) ? $data['customer'] : 'Cliente Balcão';
    $telefone_cliente = !empty($data['phone']) ? $data['phone'] : 'Não informado';
    
    // Preparar query com mesa_id se fornecido
    if (!empty($data['mesaId'])) {
        $stmt = $conn->prepare("
            INSERT INTO cliente (
                nome, telefone, pedido, itens, sub_total, total, 
                pagamento, status, data, tipo, cep, rua, bairro, 
                complemento, observacao, mesa_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'Pendente', ?, 'pos',
                'N/A', 'N/A', 'N/A', 'N/A', 'Venda POS', ?
            )
        ");
        
        $stmt->bind_param(
            'ssssssssi',
            $nome_cliente,
            $telefone_cliente,
            $numero_pedido,
            $itens,
            $subtotal_formatado,
            $total_formatado,
            $data['payment'],
            $data_atual,
            $data['mesaId']
        );

        // Atualizar status da mesa para Ocupada
        $stmt_mesa = $conn->prepare("UPDATE mesas SET status = 'Ocupada' WHERE id = ?");
        $stmt_mesa->bind_param('i', $data['mesaId']);
        $stmt_mesa->execute();
    } else {
        // Query original sem mesa_id
        $stmt = $conn->prepare("
            INSERT INTO cliente (
                nome, telefone, pedido, itens, sub_total, total, 
                pagamento, status, data, tipo, cep, rua, bairro, 
                complemento, observacao
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'Pendente', ?, 'pos',
                'N/A', 'N/A', 'N/A', 'N/A', 'Venda POS'
            )
        ");
        
        $stmt->bind_param(
            'ssssssss',
            $nome_cliente,
            $telefone_cliente,
            $numero_pedido,
            $itens,
            $subtotal_formatado,
            $total_formatado,
            $data['payment'],
            $data_atual
        );
    }
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $subtotal_formatado = number_format($subtotal, 2, ',', '.');
    $total_formatado = number_format($data['total'], 2, ',', '.');
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar venda: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Venda processada com sucesso',
        'pedido_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Encerrar buffer de saída
ob_end_flush();
$conn->close(); 