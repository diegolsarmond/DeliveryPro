<?php
session_start();
require_once '../database/db.php';
require_once '../classes/ChaflowEvolution.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuário não autenticado');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['flow_id']) || !isset($data['phone'])) {
        throw new Exception('Dados incompletos');
    }

    // Buscar fluxo
    $stmt = $conn->prepare("
        SELECT * FROM chaflow_flows 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param('ii', $data['flow_id'], $_SESSION['user_id']);
    $stmt->execute();
    $flow = $stmt->get_result()->fetch_assoc();
    
    if (!$flow) {
        throw new Exception('Fluxo não encontrado');
    }

    // Carregar nós e conexões
    $nodes = json_decode($flow['nodes'], true);
    $connections = json_decode($flow['connections'], true);
    if (empty($nodes)) {
        throw new Exception('Fluxo sem nós definidos');
    }

    // Inicializar Evolution
    $evolution = new ChaflowEvolution($conn, $_SESSION['user_id']);

    // Função para encontrar o próximo nó
    function findNextNode($currentNodeId, $connections) {
        foreach ($connections as $conn) {
            if ($conn['source'] === $currentNodeId) {
                return $conn['target'];
            }
        }
        return null;
    }

    // Função para encontrar nó por ID
    function findNodeById($nodeId, $nodes) {
        foreach ($nodes as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }
        return null;
    }

    // Função para executar nó com delay
    function executeNodeWithDelay($node, $phone, $evolution) {
        switch ($node['type']) {
            case 'text':
                return $evolution->sendMessage($phone, $node['data']['message']);
            case 'image':
                return $evolution->sendImage(
                    $phone,
                    [
                        'mediatype' => 'image',
                        'media' => $node['data']['media'],
                        'caption' => $node['data']['caption']
                    ]
                );
            case 'audio':
                return $evolution->sendAudio(
                    $phone,
                    [
                        'mediatype' => 'audio',
                        'media' => $node['data']['media'],
                        'fileName' => basename(parse_url($node['data']['media'], PHP_URL_PATH)) ?: 'audio.mp3'
                    ]
                );
            case 'video':
                $fileName = basename(parse_url($node['data']['media'], PHP_URL_PATH));
                return $evolution->sendVideo(
                    $phone,
                    [
                        'mediatype' => 'video',
                        'media' => $node['data']['media'],
                        'caption' => $node['data']['caption'],
                        'fileName' => $fileName ?: 'video.mp4'
                    ]
                );
            case 'wait':
                return ['success' => true, 'delay' => $node['data']['delay'] ?? 1000];
            case 'location':
                return $evolution->sendLocation($phone, $node['data']);
            case 'contact':
                return $evolution->sendContact($phone, [
                    'fullName' => $node['data']['fullName'],
                    'wuid' => $node['data']['wuid'],
                    'phoneNumber' => $node['data']['phoneNumber']
                ]);
            case 'poll':
                return $evolution->sendPoll(
                    $phone,
                    $node['data']['name'],
                    $node['data']['values'],
                    $node['data']['selectableCount']
                );
            case 'sticker':
                return $evolution->sendSticker(
                    $phone,
                    $node['data']['url']
                );
            case 'narrated':
                return $evolution->sendNarratedAudio(
                    $phone,
                    [
                        'audio' => $node['data']['audio']
                    ]
                );
            default:
                throw new Exception('Tipo de nó não suportado: ' . $node['type']);
        }
    }

    // Encontrar o primeiro nó
    $currentNodeId = $nodes[0]['id'];
    $results = [];
    $totalDelay = 0;

    // Executar todos os nós em sequência
    while ($currentNodeId) {
        $currentNode = findNodeById($currentNodeId, $nodes);
        if (!$currentNode) break;

        // Se for um nó de espera, apenas acumular o delay
        if ($currentNode['type'] === 'wait') {
            $totalDelay += intval($currentNode['data']['delay'] ?? 1000);
        } else {
            // Executar o nó após o delay acumulado
            if ($totalDelay > 0) {
                usleep($totalDelay * 1000); // Converter milissegundos para microssegundos
            }
            $result = executeNodeWithDelay($currentNode, $data['phone'], $evolution);
            $results[] = $result;
            $totalDelay = 0; // Resetar delay após execução
        }

        // Encontrar próximo nó
        $currentNodeId = findNextNode($currentNodeId, $connections);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Fluxo executado com sucesso',
        'results' => $results
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 