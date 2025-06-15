<?php
require_once '../database/db.php';
require_once '../classes/ChaflowEvolution.php';

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

try {
    // Buscar agendamentos pendentes que já devem ser executados
    $stmt = $conn->prepare("
        SELECT s.*, f.nodes, f.connections 
        FROM chaflow_schedules s
        JOIN chaflow_flows f ON f.id = s.flow_id
        WHERE s.status = 'pending' 
        AND s.scheduled_for <= NOW()
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($schedule = $result->fetch_assoc()) {
        try {
            // Inicializar ChaflowEvolution
            $evolution = new ChaflowEvolution($conn, $schedule['user_id']);
            
            // Decodificar nós do fluxo
            $nodes = json_decode($schedule['nodes'], true);
            
            // Ordenar nós baseado nas conexões
            $connections = json_decode($schedule['connections'], true);
            $orderedNodes = orderNodes($nodes, $connections);
            
            // Executar cada nó em ordem
            foreach ($orderedNodes as $node) {
                // Processar espera se necessário
                if ($node['type'] === 'wait' && isset($node['data']['delay'])) {
                    sleep((int)($node['data']['delay'] / 1000)); // Converter ms para segundos
                    continue;
                }
                
                // Executar ação baseada no tipo do nó
                switch ($node['type']) {
                    case 'text':
                        if (isset($node['data']['message'])) {
                            $evolution->sendMessage($schedule['phone'], $node['data']['message']);
                        }
                        break;
                        
                    case 'location':
                        if (isset($node['data']['name']) && 
                            isset($node['data']['address']) && 
                            isset($node['data']['latitude']) && 
                            isset($node['data']['longitude'])) {
                            $locationData = [
                                'name' => $node['data']['name'],
                                'address' => $node['data']['address'],
                                'latitude' => $node['data']['latitude'],
                                'longitude' => $node['data']['longitude']
                            ];
                            $evolution->sendLocation($schedule['phone'], $locationData);
                        }
                        break;
                        
                    case 'image':
                        if (isset($node['data']['media'])) {
                            $evolution->sendImage($schedule['phone'], $node['data']);
                        }
                        break;
                        
                    case 'video':
                        if (isset($node['data']['media'])) {
                            $evolution->sendVideo($schedule['phone'], $node['data']);
                        }
                        break;
                        
                    case 'audio':
                        if (isset($node['data']['media'])) {
                            $evolution->sendAudio($schedule['phone'], $node['data']);
                        }
                        break;
                        
                    case 'narrated':
                        if (isset($node['data']['audio'])) {
                            $evolution->sendNarratedAudio($schedule['phone'], $node['data']);
                        }
                        break;
                        
                    case 'contact':
                        if (isset($node['data']['fullName']) && isset($node['data']['phoneNumber'])) {
                            $evolution->sendContact($schedule['phone'], [
                                'fullName' => $node['data']['fullName'],
                                'wuid' => $node['data']['wuid'],
                                'phoneNumber' => $node['data']['phoneNumber']
                            ]);
                        }
                        break;
                }
                
                // Pequena pausa entre mensagens para evitar bloqueio
                usleep(500000); // 500ms
            }
            
            // Primeiro registrar que foi completado (para histórico)
            $logStmt = $conn->prepare("
                INSERT INTO chaflow_schedules_log 
                SELECT * FROM chaflow_schedules WHERE id = ?
            ");
            $logStmt->bind_param('i', $schedule['id']);
            $logStmt->execute();
            
            // Depois apagar o registro
            $deleteStmt = $conn->prepare("
                DELETE FROM chaflow_schedules 
                WHERE id = ?
            ");
            $deleteStmt->bind_param('i', $schedule['id']);
            $deleteStmt->execute();
            
        } catch (Exception $e) {
            // Em caso de erro, marcar como failed
            $updateStmt = $conn->prepare("
                UPDATE chaflow_schedules 
                SET status = 'failed', executed_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->bind_param('i', $schedule['id']);
            $updateStmt->execute();
            
            error_log("Erro ao executar agendamento {$schedule['id']}: " . $e->getMessage());
        }
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Função para ordenar nós baseado nas conexões
function orderNodes($nodes, $connections) {
    $orderedNodes = [];
    $nodeMap = [];
    
    // Criar mapa de nós para fácil acesso
    foreach ($nodes as $node) {
        $nodeMap[$node['id']] = $node;
    }
    
    // Encontrar nó inicial (sem conexões de entrada)
    $startNode = null;
    $incomingConnections = [];
    
    foreach ($connections as $conn) {
        if (!isset($incomingConnections[$conn['target']])) {
            $incomingConnections[$conn['target']] = 0;
        }
        $incomingConnections[$conn['target']]++;
    }
    
    foreach ($nodes as $node) {
        if (!isset($incomingConnections[$node['id']])) {
            $startNode = $node;
            break;
        }
    }
    
    // Se encontrou nó inicial, começar ordenação
    if ($startNode) {
        $orderedNodes[] = $startNode;
        followConnections($startNode['id'], $connections, $nodeMap, $orderedNodes);
    } else {
        // Se não encontrou nó inicial, retornar nós na ordem original
        return $nodes;
    }
    
    return $orderedNodes;
}

function followConnections($currentNodeId, $connections, $nodeMap, &$orderedNodes) {
    foreach ($connections as $conn) {
        if ($conn['source'] === $currentNodeId && isset($nodeMap[$conn['target']])) {
            $nextNode = $nodeMap[$conn['target']];
            if (!in_array($nextNode, $orderedNodes)) {
                $orderedNodes[] = $nextNode;
                followConnections($nextNode['id'], $connections, $nodeMap, $orderedNodes);
            }
        }
    }
} 