<?php
class TypebotIntegration {
    private $conn;
    private $evolution_settings;
    private $typebot_settings;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadSettings();
    }

    private function loadSettings() {
        try {
            // Carregar configurações da Evolution API
            $stmt = $this->conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $this->evolution_settings = $stmt->get_result()->fetch_assoc();

            if (!$this->evolution_settings) {
                throw new Exception('Configurações da Evolution API não encontradas. Configure primeiro a API.');
            }

            // Verificar se existe instância e hash configurados
            if (empty($this->evolution_settings['instance_name']) || empty($this->evolution_settings['instance_hash'])) {
                throw new Exception('Instância não configurada. Crie uma instância primeiro.');
            }

            // Carregar configurações do Typebot
            $stmt = $this->conn->prepare("SELECT settings FROM typebot_settings WHERE user_id = ?");
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $this->typebot_settings = $result ? json_decode($result['settings'], true) : null;

            if (!$this->typebot_settings) {
                throw new Exception('Configurações do Typebot não encontradas. Salve as configurações primeiro.');
            }
        } catch (Exception $e) {
            error_log('Erro ao carregar configurações: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createTypebot() {
        try {
            if (!$this->evolution_settings) {
                throw new Exception('Configurações não encontradas');
            }

            $baseUrl = rtrim($this->evolution_settings['base_url'], '/');
            $instance = $this->evolution_settings['instance_name'];
            $url = "{$baseUrl}/typebot/create/{$instance}";

            // Usar o hash da instância como API key
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->evolution_settings['instance_hash']
            ];

            // Validar valores permitidos
            $allowed_trigger_types = ['keyword', 'all', 'none'];
            if (!in_array($this->typebot_settings['triggerType'], $allowed_trigger_types)) {
                throw new Exception('Tipo de trigger inválido. Valores permitidos: ' . implode(', ', $allowed_trigger_types));
            }

            // Formatar dados conforme documentação exata da API
            $data = [
                'enabled' => true,
                'url' => filter_var(trim($this->typebot_settings['url']), FILTER_SANITIZE_URL),
                'typebot' => trim($this->typebot_settings['typebot']),
                'triggerType' => $this->typebot_settings['triggerType'],
                'triggerOperator' => $this->typebot_settings['triggerOperator'],
                'triggerValue' => trim($this->typebot_settings['triggerValue']),
                'expire' => (int)($this->typebot_settings['expire'] ?? 20),
                'keywordFinish' => trim($this->typebot_settings['keywordFinish'] ?? '#SAIR'),
                'delayMessage' => (int)($this->typebot_settings['delayMessage'] ?? 1000),
                'unknownMessage' => trim($this->typebot_settings['unknownMessage'] ?? 'Mensagem não reconhecida'),
                'listeningFromMe' => (bool)($this->typebot_settings['listeningFromMe'] ?? false),
                'stopBotFromMe' => (bool)($this->typebot_settings['stopBotFromMe'] ?? false),
                'keepOpen' => (bool)($this->typebot_settings['keepOpen'] ?? false),
                'debounceTime' => (int)($this->typebot_settings['debounceTime'] ?? 0)
            ];

            // Log detalhado
            error_log('=== INÍCIO DA REQUISIÇÃO TYPEBOT ===');
            error_log('URL da requisição: ' . $url);
            error_log('API Key: ' . substr($this->evolution_settings['api_key'], 0, 10) . '...');
            error_log('Dados enviados: ' . json_encode($data, JSON_PRETTY_PRINT));

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_VERBOSE => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            error_log('HTTP Code: ' . $httpCode);
            error_log('Headers enviados: ' . json_encode(curl_getinfo($ch, CURLINFO_HEADER_OUT)));
            error_log('Resposta bruta: ' . $response);

            if (curl_errno($ch)) {
                throw new Exception('Erro CURL: ' . curl_error($ch));
            }

            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API: ' . json_last_error_msg() . ' - Resposta: ' . $response);
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                $errorMessage = isset($decodedResponse['error']) 
                    ? $decodedResponse['error'] 
                    : (isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erro desconhecido');
                throw new Exception('Erro ao criar Typebot (HTTP ' . $httpCode . '): ' . $errorMessage . ' - Resposta completa: ' . $response);
            }

            error_log('=== FIM DA REQUISIÇÃO TYPEBOT ===');

            if ($httpCode === 200 || $httpCode === 201) {
                // Salvar o ID do bot e status inicial no banco de dados
                if (isset($decodedResponse['id'])) {
                    $botId = $decodedResponse['id'];
                    $enabled = true; // Bot começa ativado
                    $stmt = $this->conn->prepare("
                        UPDATE typebot_settings 
                        SET bot_id = ?, bot_enabled = ? 
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("sii", $botId, $enabled, $this->user_id);
                    $stmt->execute();
                }
            }

            return $decodedResponse;

        } catch (Exception $e) {
            error_log('Erro ao criar Typebot: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateTypebot($enabled = true) {
        try {
            // Buscar o ID do bot
            $stmt = $this->conn->prepare("SELECT bot_id FROM typebot_settings WHERE user_id = ?");
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result || !$result['bot_id']) {
                throw new Exception('ID do bot não encontrado. Crie o bot primeiro.');
            }

            $botId = $result['bot_id'];
            $baseUrl = rtrim($this->evolution_settings['base_url'], '/');
            $instance = $this->evolution_settings['instance_name'];
            $url = "{$baseUrl}/typebot/update/{$botId}/{$instance}";

            // Usar o hash da instância como API key
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->evolution_settings['instance_hash']
            ];

            $data = [
                'enabled' => $enabled,
                'url' => filter_var(trim($this->typebot_settings['url']), FILTER_SANITIZE_URL),
                'typebot' => trim($this->typebot_settings['typebot']),
                'expire' => (int)($this->typebot_settings['expire'] ?? 20),
                'keywordFinish' => trim($this->typebot_settings['keywordFinish'] ?? '#SAIR'),
                'delayMessage' => (int)($this->typebot_settings['delayMessage'] ?? 1000),
                'unknownMessage' => trim($this->typebot_settings['unknownMessage'] ?? 'Mensagem não reconhecida'),
                'listeningFromMe' => (bool)($this->typebot_settings['listeningFromMe'] ?? false),
                'stopBotFromMe' => (bool)($this->typebot_settings['stopBotFromMe'] ?? false),
                'keepOpen' => (bool)($this->typebot_settings['keepOpen'] ?? false),
                'debounceTime' => (int)($this->typebot_settings['debounceTime'] ?? 10),
                'triggerType' => $this->typebot_settings['triggerType'],
                'triggerOperator' => $this->typebot_settings['triggerOperator'],
                'triggerValue' => trim($this->typebot_settings['triggerValue'])
            ];

            error_log('=== INÍCIO DA ATUALIZAÇÃO TYPEBOT ===');
            error_log('URL da requisição: ' . $url);
            error_log('Dados enviados: ' . json_encode($data, JSON_PRETTY_PRINT));

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => json_encode($data),
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
                throw new Exception('Erro ao atualizar Typebot: ' . ($decodedResponse['error'] ?? 'Erro desconhecido'));
            }

            // Atualizar o status no banco de dados
            $stmt = $this->conn->prepare("UPDATE typebot_settings SET bot_enabled = ? WHERE user_id = ?");
            $enabledInt = $enabled ? 1 : 0;
            $stmt->bind_param("ii", $enabledInt, $this->user_id);
            $stmt->execute();

            error_log('=== FIM DA ATUALIZAÇÃO TYPEBOT ===');
            return $decodedResponse;

        } catch (Exception $e) {
            error_log('Erro ao atualizar Typebot: ' . $e->getMessage());
            throw $e;
        }
    }

    public function changeTypebotStatus($remoteJid, $status) {
        try {
            $url = rtrim($this->evolution_settings['base_url'], '/') . '/typebot/changeStatus/' . $this->evolution_settings['instance'];
            
            $data = [
                'remoteJid' => $remoteJid,
                'status' => $status
            ];

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->evolution_settings['api_key']
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
                throw new Exception('Erro Curl: ' . curl_error($ch));
            }
            
            curl_close($ch);

            $decodedResponse = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida da API');
            }

            return [
                'success' => ($httpCode === 200 || $httpCode === 201),
                'response' => $decodedResponse
            ];

        } catch (Exception $e) {
            error_log('Erro ao alterar status do Typebot: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteTypebot($botId) {
        try {
            if (!$this->evolution_settings) {
                throw new Exception('Configurações da Evolution API não encontradas');
            }

            $baseUrl = rtrim($this->evolution_settings['base_url'], '/');
            $instance = $this->evolution_settings['instance_name'];
            $url = "{$baseUrl}/typebot/delete/{$botId}/{$instance}";

            error_log('=== INÍCIO DA DELEÇÃO DO TYPEBOT ===');
            error_log('URL da requisição: ' . $url);

            // Usar o hash da instância como API key
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->evolution_settings['instance_hash']
            ];

            error_log('Hash usado: ' . $this->evolution_settings['instance_hash']);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
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

            // Verificar se a resposta é JSON
            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Resposta não-JSON: ' . $response);
                // Se não for JSON mas o código for 200, consideramos sucesso
                if ($httpCode === 200 || $httpCode === 204) {
                    // Limpar o ID e status do bot no banco
                    $stmt = $this->conn->prepare("
                        UPDATE typebot_settings 
                        SET bot_id = NULL, bot_enabled = NULL 
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("i", $this->user_id);
                    $stmt->execute();
                    
                    return ['success' => true];
                }
                throw new Exception('Resposta inválida da API');
            }

            if ($httpCode !== 200 && $httpCode !== 204) {
                $errorMessage = isset($decodedResponse['error']) 
                    ? $decodedResponse['error'] 
                    : (isset($decodedResponse['message']) ? $decodedResponse['message'] : 'Erro desconhecido');
                throw new Exception($errorMessage);
            }

            // Limpar o ID e status do bot no banco
            $stmt = $this->conn->prepare("
                UPDATE typebot_settings 
                SET bot_id = NULL, bot_enabled = NULL 
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();

            error_log('=== FIM DA DELEÇÃO DO TYPEBOT ===');
            return ['success' => true];

        } catch (Exception $e) {
            error_log('Erro ao deletar Typebot: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function findTypebots() {
        try {
            if (!$this->evolution_settings) {
                throw new Exception('Configurações da Evolution API não encontradas');
            }

            $baseUrl = rtrim($this->evolution_settings['base_url'], '/');
            $instance = trim($this->evolution_settings['instance'], '/');
            $url = "{$baseUrl}/typebot/find/{$instance}";

            error_log('=== INÍCIO DA BUSCA DE TYPEBOTS ===');
            error_log('URL da requisição: ' . $url);

            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $this->evolution_settings['api_key']
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
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

            // Atualizar o status no banco de dados se encontrar o bot
            if (isset($decodedResponse['typebots']) && is_array($decodedResponse['typebots'])) {
                $stmt = $this->conn->prepare("SELECT bot_id FROM typebot_settings WHERE user_id = ?");
                $stmt->bind_param("i", $this->user_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result && $result['bot_id']) {
                    foreach ($decodedResponse['typebots'] as $bot) {
                        if ($bot['id'] === $result['bot_id']) {
                            $stmt = $this->conn->prepare("UPDATE typebot_settings SET bot_enabled = ? WHERE user_id = ?");
                            $stmt->bind_param("ii", $bot['enabled'], $this->user_id);
                            $stmt->execute();
                            break;
                        }
                    }
                }
            }

            return $decodedResponse;

        } catch (Exception $e) {
            error_log('Erro ao buscar Typebots: ' . $e->getMessage());
            throw $e;
        }
    }
} 