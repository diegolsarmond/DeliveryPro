<?php
// File Hash: 1fe032058115eccf75b6f4fbed620200


ob_start();

// Verificar se é uma requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Carregar configurações do arquivo JSON
$configFile = __DIR__ . '/../customizacao.json';
$config = json_decode(file_get_contents($configFile), true);

// Função para salvar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customization'])) {
    // Limpar todo o buffer de saída
    ob_end_clean();
    
    // Se não for uma requisição AJAX, não continuar
    if (!$isAjax) {
        exit('Acesso Negado');
    }

    // Atualizar configurações
    $newConfig = [
        'logo_menu' => $_POST['logo_menu'],
        'login_logo_url' => $_POST['login_logo_url'],
        'dashboard_logo_url' => $_POST['dashboard_logo_url'],
        'dashboard_logo_height' => $_POST['dashboard_logo_height'],
        'dashboard_info_text' => $_POST['dashboard_info_text'],
        'navbar_color' => $_POST['navbar_color'],
        'primary_color' => $_POST['primary_color'],
        'primary_hover_color' => $_POST['primary_hover_color'],
        'footer_text' => $_POST['footer_text']
    ];

    $jsonConfig = json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($configFile, $jsonConfig)) {
        $response = [
            'success' => true,
            'message' => 'Configurações salvas com sucesso!'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Erro ao salvar as configurações.'
        ];
    }
    
    // Garantir que nenhum outro conteúdo seja enviado
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>

<div class="customization-container">
    <form method="POST" class="customization-form" id="customizationForm">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="settings-card">
                    <h4><i class="fas fa-images"></i> Logos</h4>
                    <div class="mb-3">
                        <label class="form-label">Logo do Menu</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="logo_menu" id="menu_logo_url" 
                                   value="<?php echo htmlspecialchars($config['logo_menu']); ?>" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="previewLogo('menu_logo_url')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL da logo no menu lateral</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo da Página de Login</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="login_logo_url" id="login_logo_url" 
                                   value="<?php echo htmlspecialchars($config['login_logo_url']); ?>" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="previewLogo('login_logo_url')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL da logo na página de login</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo do Dashboard</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="dashboard_logo_url" id="dashboard_logo_url" 
                                   value="<?php echo htmlspecialchars($config['dashboard_logo_url']); ?>" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="previewLogo('dashboard_logo_url')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL da logo no dashboard</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL do Favicon</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="favicon_url" id="favicon_url"
                                   value="<?php echo htmlspecialchars($config['favicon_url'] ?? ''); ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="previewLogo('favicon_url')">
                            <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL do ícone que aparece na aba do navegador (formato .ico, .png ou .svg)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Altura da Logo do Dashboard</label>
                        <input type="text" class="form-control" name="dashboard_logo_height" value="<?php echo htmlspecialchars($config['dashboard_logo_height']); ?>">
                        <small class="text-muted">Exemplo: 120px</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="settings-card">
                    <h4><i class="fas fa-palette"></i> Cores</h4>
                    <div class="mb-3">
                        <label class="form-label">Cor da Barra de Navegação</label>
                        <input type="color" class="form-control form-control-color" name="navbar_color" value="<?php echo $config['navbar_color']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor Primária</label>
                        <input type="color" class="form-control form-control-color" name="primary_color" value="<?php echo $config['primary_color']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cor Hover</label>
                        <input type="color" class="form-control form-control-color" name="primary_hover_color" value="<?php echo $config['primary_hover_color']; ?>">
                    </div>
                </div>
            </div>
            
            <div class="col-12 mb-4">
                <div class="settings-card">
                    <h4><i class="fas fa-edit"></i> Textos</h4>
                    <div class="mb-3">
                        <label class="form-label">Texto de Boas-vindas</label>
                        <textarea class="form-control" name="dashboard_info_text" rows="4"><?php echo htmlspecialchars($config['dashboard_info_text']); ?></textarea>
                        <small class="text-muted">Suporta HTML</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Texto do Rodapé</label>
                        <input type="text" class="form-control" name="footer_text" value="<?php echo htmlspecialchars($config['footer_text']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Título do Site</label>
                        <input type="text" class="form-control" name="site_title" 
                               value="<?php echo htmlspecialchars($config['site_title'] ?? 'Delivery PRO - Sistema de Gestão de Pedidos'); ?>" required>
                        <small class="text-muted">Este título será exibido na aba do navegador em todas as páginas</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </div>
            </div>

        </div>


    </form>
</div>

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>

<script>
// Função para visualizar qualquer logo
function previewLogo(inputId) {
    const logoUrl = document.getElementById(inputId).value;
    
    if (!logoUrl) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Insira uma URL de imagem primeiro'
        });
        return;
    }

    // Criar uma imagem temporária para verificar se a URL é válida
    const img = new Image();
    img.onload = function() {
        Swal.fire({
            title: 'Visualização da Imagem',
            imageUrl: logoUrl,
            imageAlt: 'Preview da imagem',
            confirmButtonText: 'Fechar',
            showCloseButton: true,
            imageWidth: inputId === 'favicon_url' ? 64 : 400, // Tamanho menor para favicon
            imageHeight: inputId === 'favicon_url' ? 64 : 400
        });
    };
    img.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Não foi possível carregar a imagem. Verifique se a URL está correta.'
        });
    };
    img.src = logoUrl;
}

document.getElementById('customizationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Formulário submetido');

    try {
        // Salvar a aba ativa
        localStorage.setItem('activeTab', 'customization');

        // Enviar formulário via AJAX
        const formData = new FormData(this);
        
        const response = await fetch('ajax/save_customization.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Erro na requisição: ' + response.status);
        }

        const data = await response.json();
        
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
            window.location.reload();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Ocorreu um erro ao salvar as configurações.'
        });
    }
});

// Ativar a aba correta quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeTab');
    console.log('Aba ativa:', activeTab);
    
    if (activeTab === 'customization') {
        const customizationTab = document.querySelector('#customization-tab');
        if (customizationTab) {
            const tab = new bootstrap.Tab(customizationTab);
            tab.show();
            
            // Atualizar menu lateral
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-tab') === 'customization') {
                    item.classList.add('active');
                }
            });
        }
    }
});
</script> 