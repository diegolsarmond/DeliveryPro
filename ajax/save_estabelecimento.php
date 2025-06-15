<?php
session_start();
require_once '../database/db.php';

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Log dos dados recebidos
    error_log("Dados recebidos: " . print_r($_POST, true));
    
    // Verificar se já existe registro para este usuário
    $stmt = $conn->prepare("SELECT id FROM estabelecimento WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();

    if ($exists) {
        // Atualizar registro existente
        $sql = "UPDATE estabelecimento SET 
                tipo_documento = ?,
                documento = ?,
                nome_fantasia = ?,
                razao_social = ?,
                telefone = ?,
                email = ?,
                cep = ?,
                endereco = ?,
                numero = ?,
                complemento = ?,
                bairro = ?,
                cidade = ?,
                estado = ?,
                logo_url = ?
            WHERE user_id = ?";
    } else {
        // Inserir novo registro
        $sql = "INSERT INTO estabelecimento (
                tipo_documento, documento, nome_fantasia, razao_social, 
                telefone, email, cep, endereco, numero, complemento, 
                bairro, cidade, estado, logo_url, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }

    error_log("SQL a ser executado: " . $sql);
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssi",
        $_POST['tipo_documento'],
        $_POST['documento'],
        $_POST['nome_fantasia'],
        $_POST['razao_social'],
        $_POST['telefone'],
        $_POST['email'],
        $_POST['cep'],
        $_POST['endereco'],
        $_POST['numero'],
        $_POST['complemento'],
        $_POST['bairro'],
        $_POST['cidade'],
        $_POST['estado'],
        $_POST['logo_url'],
        $user_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar query: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Dados salvos com sucesso']);

} catch (Exception $e) {
    error_log("Erro no save_estabelecimento.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close(); 