<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $campos_obrigatorios = ['nome', 'telefone', 'cep', 'rua', 'bairro'];
    foreach ($campos_obrigatorios as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            throw new Exception("Campo {$campo} é obrigatório");
        }
    }
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Atualizar cliente existente
        $stmt = $conn->prepare("
            UPDATE clientes_delivery 
            SET nome = ?, telefone = ?, cep = ?, rua = ?, bairro = ?, complemento = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", 
            $_POST['nome'],
            $_POST['telefone'],
            $_POST['cep'],
            $_POST['rua'],
            $_POST['bairro'],
            $_POST['complemento'],
            $_POST['id']
        );
    } else {
        // Inserir novo cliente
        $stmt = $conn->prepare("
            INSERT INTO clientes_delivery 
            (nome, telefone, cep, rua, bairro, complemento) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", 
            $_POST['nome'],
            $_POST['telefone'],
            $_POST['cep'],
            $_POST['rua'],
            $_POST['bairro'],
            $_POST['complemento']
        );
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cliente salvo com sucesso'
        ]);
    } else {
        throw new Exception('Erro ao salvar cliente: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close(); 