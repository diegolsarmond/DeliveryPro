<?php
class EvolutionInstance {
    private $conn;
    private $user_id;
    private $base_url;
    private $api_key;
    private $api_global;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->conn->prepare("SELECT base_url, api_key, api_global FROM evolution_settings WHERE user_id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            throw new Exception('Configurações da Evolution API não encontradas');
        }

        $this->base_url = $result['base_url'];
        $this->api_key = $result['api_key'];
        $this->api_global = $result['api_global'];
    }

    public function createInstance($instanceName) {
        try {
            $url = rtrim($this->base_url, '/') . '/instance/create';
            
            error_log('=== INÍCIO DA CRIAÇÃO DE INSTÂNCIA ===');
            error_log('URL da requisição: ' . $url);
            
            $data = [
                'instanceName' => $instanceName,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS'
            ];

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->api_global
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Erro CURL: ' . curl_error($ch));
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API: ' . json_last_error_msg());
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                throw new Exception($decodedResponse['error'] ?? 'Erro desconhecido');
            }

            // Salvar dados da instância no banco
            $stmt = $this->conn->prepare("
                INSERT INTO evolution_instances 
                (user_id, instance_name, instance_id, hash, status, qr_code) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $instanceName = $decodedResponse['instance']['instanceName'];
            $instanceId = $decodedResponse['instance']['instanceId'];
            $hash = $decodedResponse['hash'];
            $status = $decodedResponse['instance']['status'];
            $qrCode = $decodedResponse['qrcode']['base64'] ?? null;

            $stmt->bind_param("isssss", 
                $this->user_id, 
                $instanceName, 
                $instanceId, 
                $hash, 
                $status, 
                $qrCode
            );

            if (!$stmt->execute()) {
                throw new Exception('Erro ao salvar instância no banco de dados');
            }

            // Salvar nome da instância e hash nas configurações
            $stmt = $this->conn->prepare("
                UPDATE evolution_settings 
                SET instance_name = ?, instance_hash = ? 
                WHERE user_id = ?
            ");
            
            $stmt->bind_param("ssi", $instanceName, $hash, $this->user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar configurações da Evolution API');
            }

            error_log('=== FIM DA CRIAÇÃO DE INSTÂNCIA ===');
            return $decodedResponse;

        } catch (Exception $e) {
            error_log('Erro ao criar instância: ' . $e->getMessage());
            throw $e;
        }
    }

    public function fetchInstances() {
        try {
            // Buscar instâncias do banco de dados local primeiro
            $stmt = $this->conn->prepare("
                SELECT instance_name, instance_id, hash, status, qr_code 
                FROM evolution_instances 
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $instances = [];
            while ($row = $result->fetch_assoc()) {
                $instances[] = [
                    'instanceName' => $row['instance_name'],
                    'instanceId' => $row['instance_id'],
                    'hash' => $row['hash'],
                    'status' => $row['status'],
                    'qrcode' => $row['qr_code']
                ];
            }

            // Buscar status atualizado da API
            $url = rtrim($this->base_url, '/') . '/instance/fetchInstances';
            
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->api_global
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                error_log('Erro CURL ao buscar status: ' . curl_error($ch));
                return [
                    'success' => true,
                    'instances' => $instances
                ];
            }
            
            curl_close($ch);

            $apiInstances = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($apiInstances)) {
                foreach ($apiInstances as $apiInstance) {
                    // Verificar se o item é um array válido
                    if (!is_array($apiInstance)) {
                        continue;
                    }

                    // Mapear status da API para nosso formato usando operador de coalescência nula
                    $status = 'disconnected';
                    $connectionStatus = $apiInstance['connectionStatus'] ?? '';
                    if ($connectionStatus === 'open') {
                        $status = 'connected';
                    } elseif ($connectionStatus === 'connecting') {
                        $status = 'connecting';
                    }

                    // Atualizar status no banco com valores seguros
                    $updateStmt = $this->conn->prepare("
                        UPDATE evolution_instances 
                        SET status = ?,
                            profile_name = ?,
                            profile_pic_url = ?,
                            owner_jid = ?,
                            connection_status = ?,
                            disconnection_reason = ?,
                            disconnected_at = ?
                        WHERE instance_name = ? AND user_id = ?
                    ");

                    $profileName = $apiInstance['profileName'] ?? '';
                    $profilePicUrl = $apiInstance['profilePicUrl'] ?? '';
                    $ownerJid = $apiInstance['ownerJid'] ?? '';
                    $connectionStatus = $apiInstance['connectionStatus'] ?? '';
                    $disconnectionReason = $apiInstance['disconnectionReasonCode'] ?? '';
                    $disconnectedAt = $apiInstance['disconnectionAt'] ?? '';
                    $instanceName = $apiInstance['name'] ?? '';

                    if (!empty($instanceName)) {
                        $updateStmt->bind_param("ssssssssi", 
                            $status,
                            $profileName,
                            $profilePicUrl,
                            $ownerJid,
                            $connectionStatus,
                            $disconnectionReason,
                            $disconnectedAt,
                            $instanceName,
                            $this->user_id
                        );
                        $updateStmt->execute();

                        // Atualizar status na resposta
                        foreach ($instances as &$instance) {
                            if ($instance['instanceName'] === $instanceName) {
                                $instance['status'] = $status;
                                $instance['profileName'] = $profileName;
                                $instance['profilePicUrl'] = $profilePicUrl;
                                $instance['ownerJid'] = $ownerJid;
                                $instance['connectionStatus'] = $connectionStatus;
                                break;
                            }
                        }
                    }
                }
            }

            return [
                'success' => true,
                'instances' => $instances
            ];

        } catch (Exception $e) {
            error_log('Erro ao buscar instâncias: ' . $e->getMessage());
            throw new Exception('Erro ao buscar instâncias: ' . $e->getMessage());
        }
    }

    public function connectInstance($instanceName) {
        try {
            $url = rtrim($this->base_url, '/') . '/instance/connect/' . $instanceName;
            
            error_log('=== INÍCIO DA CONEXÃO DA INSTÂNCIA ===');
            error_log('URL da requisição: ' . $url);

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->api_global
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPGET => true, // Alterado para GET
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log('HTTP Code: ' . $httpCode);
            error_log('Resposta: ' . $response);

            if (curl_errno($ch)) {
                throw new Exception('Erro CURL: ' . curl_error($ch));
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API: ' . json_last_error_msg());
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                $errorMessage = isset($decodedResponse['error']) 
                    ? $decodedResponse['error'] 
                    : (isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erro desconhecido');
                throw new Exception('Erro ao conectar instância: ' . $errorMessage);
            }

            // Atualizar status e QR code no banco
            $stmt = $this->conn->prepare("
                UPDATE evolution_instances 
                SET status = 'connecting', qr_code = ? 
                WHERE instance_name = ? AND user_id = ?
            ");

            $qrCode = $decodedResponse['base64'] ?? null;
            $stmt->bind_param("ssi", $qrCode, $instanceName, $this->user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar status no banco de dados');
            }

            error_log('=== FIM DA CONEXÃO DA INSTÂNCIA ===');
            return [
                'success' => true,
                'message' => 'Instância conectando. Escaneie o QR Code.',
                'qrcode' => $qrCode
            ];

        } catch (Exception $e) {
            error_log('Erro ao conectar instância: ' . $e->getMessage());
            throw new Exception('Erro ao conectar instância: ' . $e->getMessage());
        }
    }

    public function logoutInstance($instanceName) {
        try {
            $url = rtrim($this->base_url, '/') . '/instance/logout/' . $instanceName;
            
            error_log('=== INÍCIO DO LOGOUT DA INSTÂNCIA ===');
            error_log('URL da requisição: ' . $url);

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->api_global
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE', // Alterado para DELETE
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log('HTTP Code: ' . $httpCode);
            error_log('Resposta: ' . $response);

            if (curl_errno($ch)) {
                throw new Exception('Erro CURL: ' . curl_error($ch));
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API: ' . json_last_error_msg());
            }

            if ($httpCode !== 200 && $httpCode !== 204) {
                $errorMessage = isset($decodedResponse['error']) 
                    ? $decodedResponse['error'] 
                    : (isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erro desconhecido');
                throw new Exception('Erro ao desconectar instância: ' . $errorMessage);
            }

            // Atualizar status no banco
            $stmt = $this->conn->prepare("
                UPDATE evolution_instances 
                SET status = 'disconnected' 
                WHERE instance_name = ? AND user_id = ?
            ");
            $stmt->bind_param("si", $instanceName, $this->user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar status no banco de dados');
            }

            error_log('=== FIM DO LOGOUT DA INSTÂNCIA ===');
            return [
                'success' => true,
                'message' => 'Instância desconectada com sucesso'
            ];

        } catch (Exception $e) {
            error_log('Erro ao desconectar instância: ' . $e->getMessage());
            throw new Exception('Erro ao desconectar instância: ' . $e->getMessage());
        }
    }

    public function deleteInstance($instanceName) {
        try {
            $url = rtrim($this->base_url, '/') . '/instance/delete/' . $instanceName;
            
            error_log('=== INÍCIO DA DELEÇÃO DA INSTÂNCIA ===');
            error_log('URL da requisição: ' . $url);

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->api_global
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log('HTTP Code: ' . $httpCode);
            error_log('Resposta: ' . $response);

            if (curl_errno($ch)) {
                throw new Exception('Erro CURL: ' . curl_error($ch));
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API: ' . json_last_error_msg());
            }

            if ($httpCode !== 200 && $httpCode !== 204) {
                $errorMessage = isset($decodedResponse['error']) 
                    ? $decodedResponse['error'] 
                    : (isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erro desconhecido');
                throw new Exception('Erro ao deletar instância: ' . $errorMessage);
            }

            // Verificar se a deleção foi bem sucedida
            if (!isset($decodedResponse['status']) || $decodedResponse['status'] !== 'SUCCESS') {
                throw new Exception('Erro ao deletar instância: Resposta inesperada da API');
            }

            // Remover do banco
            $stmt = $this->conn->prepare("
                DELETE FROM evolution_instances 
                WHERE instance_name = ? AND user_id = ?
            ");
            $stmt->bind_param("si", $instanceName, $this->user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao remover instância do banco de dados');
            }

            error_log('=== FIM DA DELEÇÃO DA INSTÂNCIA ===');
            return [
                'success' => true,
                'message' => 'Instância excluída com sucesso'
            ];

        } catch (Exception $e) {
            error_log('Erro ao deletar instância: ' . $e->getMessage());
            throw new Exception('Erro ao deletar instância: ' . $e->getMessage());
        }
    }
} 