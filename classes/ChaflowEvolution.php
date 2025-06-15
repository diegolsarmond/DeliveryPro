<?php
class ChaflowEvolution {
    private $conn;
    private $base_url;
    private $api_key;
    private $instance;
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->loadSettings($user_id);
    }
    
    private function loadSettings($user_id) {
        // Buscar configurações da instância ativa
        $stmt = $this->conn->prepare("
            SELECT ei.instance_name, ei.hash as api_key, es.base_url
            FROM evolution_instances ei
            JOIN evolution_settings es ON es.user_id = ei.user_id
            WHERE ei.user_id = ? AND ei.status = 'connected'
            LIMIT 1
        ");
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Debug
        error_log("Settings: " . json_encode($result));
        
        if (!$result) {
            throw new Exception('Nenhuma instância ativa encontrada');
        }
        
        $this->base_url = rtrim($result['base_url'], '/'); // Remove trailing slash
        $this->api_key = $result['api_key'];
        $this->instance = $result['instance_name'];
        
        // Validar dados
        if (empty($this->base_url) || empty($this->api_key) || empty($this->instance)) {
            throw new Exception('Configurações da Evolution API incompletas');
        }
    }
    
    public function sendMessage($to, $message) {
        $url = "{$this->base_url}/message/sendText/{$this->instance}";
        
        $data = [
            'number' => $this->formatNumber($to),
            'text' => $message,
            'options' => [
                'delay' => 1200,
                'presence' => 'composing'
            ]
        ];
        
        // Debug
        error_log("URL: " . $url);
        error_log("Headers: apikey: " . trim($this->api_key));
        error_log("Data: " . json_encode($data));
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    public function sendLocation($to, $locationData) {
        $url = "{$this->base_url}/message/sendLocation/{$this->instance}";
        
        $data = [
            'number' => $this->formatNumber($to),
            'name' => $locationData['name'],
            'address' => $locationData['address'],
            'latitude' => floatval($locationData['latitude']),
            'longitude' => floatval($locationData['longitude'])
        ];
        
        // Debug
        error_log("Enviando localização: " . json_encode($data));
        error_log("URL: " . $url);
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    public function sendImage($to, $data) {
        $url = $this->base_url . '/message/sendMedia/' . $this->instance;
        
        $postData = [
            'number' => $this->formatNumber($to),
            'mediatype' => 'image',
            'media' => $data['media'],
            'caption' => $data['caption']
        ];
        
        return $this->makeRequest('POST', $url, $postData);
    }
    
    public function sendVideo($to, $data) {
        $url = $this->base_url . '/message/sendMedia/' . $this->instance;
        
        // Extrair o nome do arquivo da URL
        $fileName = basename(parse_url($data['media'], PHP_URL_PATH));
        
        $postData = [
            'number' => $this->formatNumber($to),
            'mediatype' => 'video',
            'media' => $data['media'],
            'caption' => $data['caption'],
            'fileName' => $fileName ?: 'video.mp4'
        ];
        
        // Debug
        error_log("Enviando vídeo: " . json_encode($postData));
        error_log("URL: " . $url);
        
        return $this->makeRequest('POST', $url, $postData);
    }
    
    public function sendAudio($to, $data) {
        $url = $this->base_url . '/message/sendMedia/' . $this->instance;
        
        // Extrair o nome do arquivo da URL
        $fileName = basename(parse_url($data['media'], PHP_URL_PATH));
        
        $postData = [
            'number' => $this->formatNumber($to),
            'mediatype' => 'audio',
            'media' => $data['media'],
            'fileName' => $fileName ?: 'audio.mp3'
        ];
        
        // Debug
        error_log("Enviando áudio: " . json_encode($postData));
        error_log("URL: " . $url);
        
        return $this->makeRequest('POST', $url, $postData);
    }
    
    public function sendNarratedAudio($to, $data) {
        $url = "{$this->base_url}/message/sendWhatsAppAudio/{$this->instance}";
        
        $postData = [
            'number' => $this->formatNumber($to),
            'audio' => $data['audio']
        ];
        
        // Debug
        error_log("Enviando áudio narrado: " . json_encode($postData));
        error_log("URL: " . $url);
        
        return $this->makeRequest('POST', $url, $postData);
    }
    
    public function sendContact($to, $contactData) {
        $url = "{$this->base_url}/message/sendContact/{$this->instance}";
        
        $data = [
            'number' => $this->formatNumber($to),
            'contact' => [
                [
                    'fullName' => $contactData['fullName'],
                    'wuid' => $contactData['wuid'],
                    'phoneNumber' => $contactData['phoneNumber']
                ]
            ]
        ];
        
        return $this->makeRequest('POST', $url, $data);
    }
    
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        // Garantir que a URL está formatada corretamente
        $url = rtrim($url, '/');
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $this->api_key
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ];
        
        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_SLASHES);
        }
        
        // Debug da requisição
        error_log("Request URL: " . $url);
        error_log("Request Headers: " . json_encode($options[CURLOPT_HTTPHEADER]));
        error_log("Request Data: " . json_encode($data, JSON_UNESCAPED_SLASHES));
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        
        // Debug
        error_log("Response: " . $response);
        error_log("Curl error: " . curl_error($ch));
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Erro na requisição: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = is_array($responseData) ? json_encode($responseData) : $response;
            throw new Exception('Erro na API: ' . $errorMessage);
        }
        
        return $responseData;
    }
    
    private function formatNumber($number) {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) <= 11) {
            $number = '55' . $number;
        }
        return $number;
    }
} 