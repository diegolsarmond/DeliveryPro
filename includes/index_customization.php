<?php
// Carregar configurações do arquivo JSON
$configFile = __DIR__ . '/../customizacao.json';
$config = json_decode(file_get_contents($configFile), true);
?>

<div class="settings-card">
    <h4><i class="fas fa-paint-brush"></i> Personalização da Página de Redirecionamento</h4>
    <style>
        /* Estilos específicos para o formulário de personalização */
        #indexCustomizationForm .input-group {
            flex-wrap: nowrap;
            max-width: 100%;
        }
        
        #indexCustomizationForm .form-control-color {
            min-width: 50px;
            padding: 0.375rem;
        }
        
        #indexCustomizationForm .input-group > .form-control {
            flex: 1;
            min-width: 0;
        }
        
        @media (min-width: 768px) {
            #indexCustomizationForm .input-group {
                width: 100%;
            }
        }
    </style>
    <form id="indexCustomizationForm">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">URL da Logo</label>
                <div class="input-group">
                    <input type="url" class="form-control" name="logo_url" id="index_logo_url" 
                           value="<?php echo htmlspecialchars($redirect_config['logo_url']); ?>" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="previewIndexLogo()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text text-muted">URL da imagem que será exibida no topo</small>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Cor de Fundo</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <input type="color" class="form-control form-control-color" name="background_color" 
                               value="<?php echo htmlspecialchars($redirect_config['background_color']); ?>" required>
                    </div>
                    <input type="text" class="form-control" id="background_color_text" 
                           value="<?php echo htmlspecialchars($redirect_config['background_color']); ?>" 
                           pattern="^#[0-9A-Fa-f]{6}$" required>
                </div>
                <small class="form-text text-muted">Cor do gradiente de fundo</small>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="title" 
                       value="<?php echo htmlspecialchars($redirect_config['title']); ?>" required>
                <small class="form-text text-muted">Título exibido acima do contador</small>
            </div>

            <div class="col-md-12 mb-3">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" name="description" 
                       value="<?php echo htmlspecialchars($redirect_config['description']); ?>" required>
                <small class="form-text text-muted">Texto exibido abaixo do contador</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Tempo de Redirecionamento (segundos)</label>
                <input type="number" class="form-control" name="redirect_time" min="1" max="30" 
                       value="<?php echo (int)$redirect_config['redirect_time']; ?>" required>
                <small class="form-text text-muted">Tempo de espera antes do redirecionamento (1-30 segundos)</small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salvar Configurações
        </button>
    </form>
</div>

<script>
// Função para salvar configurações
async function saveIndexCustomization(form) {
    try {
        // Mostrar loading
        Swal.fire({
            title: 'Salvando...',
            text: 'Aguarde enquanto as configurações são salvas',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Pegar valores atuais do arquivo de configuração
        const currentConfig = JSON.parse('<?php echo addslashes(json_encode($config, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)); ?>');

        const response = await fetch('ajax/save_customization.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(currentConfig)
        });

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Configurações salvas com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });

            // Recarregar a página para atualizar as configurações
            window.location.reload();
        } else {
            throw new Error(result.message || 'Erro ao salvar configurações');
        }
    } catch (error) {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Erro ao salvar configurações'
        });
    }
}

// Adicionar event listener quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar o arquivo customizacao.json quando o formulário for enviado
    document.getElementById('indexCustomizationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveIndexCustomization(this);
    });

    // Sincronizar input de cor
    const colorInput = document.querySelector('input[name="background_color"]');
    const colorText = document.getElementById('background_color_text');

    if (colorInput && colorText) {
        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });

        colorText.addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                colorInput.value = this.value;
            }
        });
    }
});
</script> 