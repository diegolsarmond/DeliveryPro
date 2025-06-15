<?php
header('Content-Type: application/json');

try {
    // Verificar se recebemos os dados do formulário
    if (!isset($_POST)) {
        throw new Exception('Nenhum dado recebido');
    }

    // Caminho para o arquivo de configuração
    $configFile = __DIR__ . '/../customizacao.json';

    // Verificar se o arquivo existe e tem permissão de escrita
    if (!is_writable($configFile)) {
        chmod($configFile, 0666);
        if (!is_writable($configFile)) {
            throw new Exception('Arquivo de configuração não tem permissão de escrita');
        }
    }

    // Carregar configuração atual
    $currentConfig = json_decode(file_get_contents($configFile), true);

    // Salvar as novas configurações
    $newConfig = array_merge($currentConfig, [
        'logo_menu' => $_POST['logo_menu'],
        'login_logo_url' => $_POST['login_logo_url'],
        'dashboard_logo_url' => $_POST['dashboard_logo_url'],
        'dashboard_logo_height' => $_POST['dashboard_logo_height'],
        'dashboard_info_text' => $_POST['dashboard_info_text'],
        'navbar_color' => $_POST['navbar_color'],
        'primary_color' => $_POST['primary_color'],
        'primary_hover_color' => $_POST['primary_hover_color'],
        'footer_text' => $_POST['footer_text'],
        'site_title' => $_POST['site_title'],
        'favicon_url' => $_POST['favicon_url']
    ]);

    $jsonString = json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($jsonString === false) {
        throw new Exception('Erro ao codificar JSON: ' . json_last_error_msg());
    }

    if (file_put_contents($configFile, $jsonString) === false) {
        throw new Exception('Erro ao salvar arquivo de configuração');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Configurações salvas com sucesso!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 