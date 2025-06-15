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
    // Buscar produtos disponíveis
    $stmt = $conn->prepare("
        SELECT p.*, c.item as categoria_nome 
        FROM produtos_delivery p 
        LEFT JOIN categorias_delivery c ON p.categoria_id = c.id_categoria 
        ORDER BY p.categoria_id, p.item
    ");
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $result = $stmt->get_result();
    $products = [];
    
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'item' => $row['item'],
            'valor' => floatval($row['valor']),
            'categoria_id' => $row['categoria_id'],
            'categoria_nome' => $row['categoria_nome']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar produtos: ' . $e->getMessage()
    ]);
}

// Encerrar buffer de saída
ob_end_flush();
$conn->close(); 