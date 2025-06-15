<?php
session_start();
require_once('../database/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Não autorizado']));
}

$user_id = $_SESSION['user_id'];
$subject = $_POST['batch_subject'] ?? null;
$description = $_POST['batch_description'] ?? null;
$image = $_POST['batch_image'] ?? null;

// Verificar se pelo menos um campo foi preenchido
if (!$subject && !$description && !$image) {
    exit(json_encode(['success' => false, 'message' => 'Preencha pelo menos um campo para atualizar']));
}

// Buscar configurações da Evolution
$stmt = $conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

if (!$settings) {
    exit(json_encode(['success' => false, 'message' => 'Configurações da API não encontradas']));
}

// Buscar todos os grupos do usuário
$stmt = $conn->prepare("SELECT group_jid, id FROM evolution_groups WHERE user_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$successCount = 0;
$errorCount = 0;
$errors = [];

while ($group = $result->fetch_assoc()) {
    $groupJid = $group['group_jid'];
    $groupId = $group['id'];
    
    try {
        // Atualizar nome do grupo
        if ($subject) {
            $formattedNumber = str_pad($groupId, 2, '0', STR_PAD_LEFT);
            $groupSubject = str_replace('{numero}', $formattedNumber, $subject);
            
            $response = updateGroupSubject($settings, $groupJid, $groupSubject);
            if ($response['success']) {
                $successCount++;
                $updateStmt = $conn->prepare("UPDATE evolution_groups SET subject = ? WHERE group_jid = ? AND user_id = ?");
                $updateStmt->bind_param("ssi", $groupSubject, $groupJid, $user_id);
                $updateStmt->execute();
            } else {
                $errorCount++;
                $errors[] = "Erro ao atualizar nome do grupo {$groupJid}: {$response['message']}";
            }
        }
        
        // Atualizar descrição do grupo
        if ($description) {
            $response = updateGroupDescription($settings, $groupJid, $description);
            if ($response['success']) $successCount++;
            else {
                $errorCount++;
                $errors[] = "Erro ao atualizar descrição do grupo {$groupJid}: {$response['message']}";
            }
        }
        
        // Atualizar foto do grupo
        if ($image) {
            $response = updateGroupPicture($settings, $groupJid, $image);
            if ($response['success']) $successCount++;
            else {
                $errorCount++;
                $errors[] = "Erro ao atualizar foto do grupo {$groupJid}: {$response['message']}";
            }
        }
        
        // Atualizar no banco de dados
        if ($subject || $description) {
            $updateFields = [];
            $updateParams = [];
            $types = '';
            
            if ($subject) {
                $updateFields[] = "subject = ?";
                $updateParams[] = $subject;
                $types .= 's';
            }
            if ($description) {
                $updateFields[] = "description = ?";
                $updateParams[] = $description;
                $types .= 's';
            }
            
            $updateParams[] = $user_id;
            $updateParams[] = $groupJid;
            $types .= 'is';
            
            $sql = "UPDATE evolution_groups SET " . implode(', ', $updateFields) . " WHERE user_id = ? AND group_jid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$updateParams);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $errorCount++;
        $errors[] = $e->getMessage();
    }
}

echo json_encode([
    'success' => true,
    'message' => "Atualização em lote concluída. Sucesso: $successCount, Erros: $errorCount",
    'errors' => $errors
]);

function updateGroupSubject($settings, $groupJid, $subject) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $settings['base_url'] . '/group/updateGroupSubject/' . $settings['instance'] . '?groupJid=' . urlencode($groupJid),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['subject' => $subject]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $settings['api_key']
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return ['success' => ($httpCode === 200 || $httpCode === 201)];
}

function updateGroupDescription($settings, $groupJid, $description) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $settings['base_url'] . '/group/updateGroupDescription/' . $settings['instance'] . '?groupJid=' . urlencode($groupJid),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['description' => $description]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $settings['api_key']
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return ['success' => ($httpCode === 200 || $httpCode === 201)];
}

function updateGroupPicture($settings, $groupJid, $image) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $settings['base_url'] . '/group/updateGroupPicture/' . $settings['instance'] . '?groupJid=' . urlencode($groupJid),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['image' => $image]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $settings['api_key']
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return ['success' => ($httpCode === 200 || $httpCode === 201)];
} 