<?php
function initializeConfig() {
    $configFile = __DIR__ . '/../customizacao.json';
    
    // Verificar se o arquivo existe
    if (!file_exists($configFile)) {
        // Configurações padrão
        $defaultConfig = [
            'logo_menu' => 'https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/linkpro-logo-wt.png',
            'login_logo_url' => 'https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/linkpro-logo.png',
            'dashboard_logo_url' => 'https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/logo-dashboard-agendapro.png',
            'dashboard_logo_height' => '120px',
            'dashboard_info_text' => '<div><h1>Bem-vindo ao Delivery PRO</h1><p>Seja bem-vindo ao seu painel de controle!</p></div>',
            'navbar_color' => '#0d6efd',
            'primary_color' => '#4789eb',
            'primary_hover_color' => '#5294ff',
            'footer_text' => 'Copyright © 2024 Delivery PRO - Feito com ❤️',
            'site_title' => 'Delivery PRO - Sistema de Gestão de Pedidos',
            'favicon_url' => 'https://cdn.jsdelivr.net/gh/mathuzabr/img-packtypebot/favicon.ico'
        ];

        // Criar arquivo com permissões corretas
        file_put_contents($configFile, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        chmod($configFile, 0666); // Dar permissões de leitura e escrita
    }

    // Verificar permissões
    if (!is_writable($configFile)) {
        chmod($configFile, 0666);
    }

    return json_decode(file_get_contents($configFile), true);
}
?> 