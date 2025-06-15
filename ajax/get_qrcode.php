<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $instance = $_GET['instance'] ?? null;
    if (!$instance) {
        throw new Exception('Instância não especificada');
    }

    // Log para debug
    error_log('Buscando QR Code para instância: ' . $instance);

    $stmt = $conn->prepare("
        SELECT qr_code, status 
        FROM evolution_instances 
        WHERE instance_name = ? AND user_id = ?
    ");
    $stmt->bind_param("si", $instance, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        throw new Exception('Instância não encontrada');
    }

    if ($result['status'] !== 'connecting') {
        throw new Exception('QR Code só está disponível quando a instância está conectando');
    }

    if (empty($result['qr_code'])) {
        throw new Exception('QR Code ainda não está disponível');
    }

    // Remover o prefixo se já existir
    $qrCode = $result['qr_code'];
    $qrCode = str_replace('data:image/png;base64,', '', $qrCode);

    error_log('QR Code encontrado com sucesso');

    echo json_encode([
        'success' => true,
        'qrcode' => $qrCode // Retorna apenas o base64, sem o prefixo
    ]);

} catch (Exception $e) {
    error_log('Erro ao buscar QR Code: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 