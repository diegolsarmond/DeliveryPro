<?php
// Desativar exibição de erros
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de saída
ob_start();

session_start();
require_once('../database/db.php');

// Limpar qualquer saída anterior
ob_clean();

// Configurar header
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $tipo = $_POST['tipo'] ?? '';
    
    if (!in_array($tipo, ['categorias', 'produtos'])) {
        throw new Exception('Tipo de reset inválido');
    }

    $conn->begin_transaction();
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    if ($tipo === 'categorias') {
        // Reset apenas das categorias
        $conn->query("TRUNCATE TABLE categorias_delivery");
        $conn->query("ALTER TABLE categorias_delivery AUTO_INCREMENT = 1");
        
        
        $mensagem = 'Categorias resetadas com sucesso!';
    } else {
        // Reset apenas dos produtos
        $conn->query("TRUNCATE TABLE produtos_delivery");
        $conn->query("ALTER TABLE produtos_delivery AUTO_INCREMENT = 1");
    
        
        $mensagem = 'Produtos resetados com sucesso!';
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    $conn->commit();

    echo json_encode(['success' => true, 'message' => $mensagem]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao resetar: ' . $e->getMessage()]);
}

// Encerrar buffer de saída
ob_end_flush(); 