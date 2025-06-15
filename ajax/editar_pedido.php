<?php
session_start();
require_once '../database/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Recebe e decodifica os dados JSON
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (!$dados) {
        throw new Exception('Dados inválidos');
    }

    // Prepara a query de atualização
    $sql = "UPDATE cliente SET 
            nome = ?, 
            telefone = ?, 
            cep = ?, 
            rua = ?, 
            bairro = ?, 
            complemento = ?, 
            pagamento = ? 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar query: ' . $conn->error);
    }

    // Faz o bind dos parâmetros
    $stmt->bind_param(
        'sssssssi',
        $dados['nome'],
        $dados['telefone'],
        $dados['cep'],
        $dados['rua'],
        $dados['bairro'],
        $dados['complemento'],
        $dados['pagamento'],
        $dados['id']
    );

    // Executa a query
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar query: ' . $stmt->error);
    }

    // Verifica se alguma linha foi afetada
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Nenhuma alteração realizada']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar pedido: ' . $e->getMessage()
    ]);
}

// Fecha a conexão
$stmt->close();
$conn->close(); 