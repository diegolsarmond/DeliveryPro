<?php
class EvolutionAPI {
    private $conn;
    private $settings;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadSettings();
    }

    private function loadSettings() {
        // Carregar configurações do banco de dados
        $stmt = $this->conn->prepare("SELECT * FROM evolution_settings LIMIT 1");
        $stmt->execute();
        $this->settings = $stmt->get_result()->fetch_assoc();

        if (!$this->settings) {
            throw new Exception('Configurações da Evolution API não encontradas');
        }
    }

    public function sendMessage($phone, $message) {
        try {
            // Formatar número do telefone
            $phone = $this->formatPhone($phone);
            
            // Preparar dados para envio
            $data = [
                "number" => $phone,
                "text" => $message
            ];

            // Configurar e fazer a requisição
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->settings['base_url'] . '/message/sendText/' . $this->settings['instance_name'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'apikey: ' . $this->settings['instance_hash']
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if (curl_errno($curl)) {
                throw new Exception('Erro Curl: ' . curl_error($curl));
            }
            
            curl_close($curl);

            $responseData = json_decode($response, true);
            
            if ($httpCode !== 200 && $httpCode !== 201) {
                throw new Exception($responseData['error'] ?? 'Erro ao enviar mensagem');
            }

            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso'
            ];

        } catch (Exception $e) {
            error_log('Erro ao enviar mensagem: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()
            ];
        }
    }

    private function formatPhone($phone) {
        // Remover caracteres não numéricos
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Adicionar código do país se não existir
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }
        
        // Não adicionar @s.whatsapp.net - a API já lida com isso
        return $phone;
    }
} 