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

// Log inicial
error_log("Iniciando exclusão do grupo: " . $group_jid);

// Buscar configurações da Evolution
$stmt = $conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

if (!$settings) {
    error_log("Configurações não encontradas para user_id: " . $user_id);
    exit(json_encode(['success' => false, 'message' => 'Configurações da API não encontradas']));
}

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $settings['base_url'] . '/group/leaveGroup/' . $settings['instance'] . '?groupJid=' . urlencode($group_jid),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'DELETE',
    CURLOPT_HTTPHEADER => array(
        'apikey: ' . $settings['api_key']
    ),
));

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Log da resposta
error_log("HTTP Code: " . $httpCode);
error_log("Resposta da API: " . $response);

curl_close($curl);

if ($httpCode === 200 || $httpCode === 201) {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['leave']) && $responseData['leave'] === true) {
        try {
            // Remover o grupo do banco de dados
            $stmt = $conn->prepare("DELETE FROM evolution_groups WHERE user_id = ? AND group_jid = ?");
            $stmt->bind_param("is", $user_id, $group_jid);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao remover grupo do banco de dados");
            }

            echo json_encode([
                'success' => true,
                'message' => 'Grupo removido com sucesso'
            ]);
        } catch (Exception $e) {
            error_log("Erro ao remover grupo do banco: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Não foi possível sair do grupo'
        ]);
    }
} else {
    $errorMessage = "Erro na API (HTTP $httpCode)";
    if ($response) {
        $errorData = json_decode($response, true);
        if ($errorData && isset($errorData['error'])) {
            $errorMessage .= ": " . $errorData['error'];
        }
    }
    
    error_log($errorMessage);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
} 