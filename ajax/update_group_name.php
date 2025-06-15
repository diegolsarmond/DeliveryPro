<?php
session_start();
require_once('../database/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];
$group_jid = $_POST['group_jid'];
$new_name = $_POST['subject'];

// Buscar configurações da Evolution
$stmt = $conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

if (!$settings) {
    exit(json_encode(['success' => false, 'message' => 'Configurações da API não encontradas']));
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $settings['base_url'] . '/group/updateGroupSubject/' . $settings['instance'] . '?groupJid=' . urlencode($group_jid),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode(['subject' => $new_name]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'apikey: ' . $settings['api_key']
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($httpCode === 200 || $httpCode === 201) {
    $responseData = json_decode($response, true);
    if (isset($responseData['update']) && $responseData['update'] === 'success') {
        // Atualizar no banco de dados
        $stmt = $conn->prepare("UPDATE evolution_groups SET subject = ? WHERE user_id = ? AND group_jid = ?");
        $stmt->bind_param("sis", $new_name, $user_id, $group_jid);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Nome do grupo atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar nome do grupo']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na requisição: ' . $httpCode]);
} 