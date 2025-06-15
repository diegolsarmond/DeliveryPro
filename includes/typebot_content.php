<?php
// File Hash: e0ed6707691b28ebc604d726709000dc


// Verificar permissão
if (!isset($_SESSION['permissions']['typebot']) || !$_SESSION['permissions']['typebot']) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta área.</div>';
    exit;
}

// Buscar configurações do Typebot
$stmt = $conn->prepare("SELECT * FROM typebot_settings WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$typebot_settings = $stmt->get_result()->fetch_assoc();

?>

<div class="typebot-container">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title2">Configurações do Typebot</h5>
        </div>
        <div class="card-body">
            <form id="typebotSettingsForm">
                <!-- Configurações do Typebot -->
                <div class="mb-3">
                    <label class="form-label">URL do Typebot</label>
                    <input type="text" class="form-control" name="typebot_url" 
                           value="<?php echo $typebot_settings['typebot_url'] ?? ''; ?>">
                    <small class="text-muted">URL completa do seu bot no Typebot</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Configurações de Comportamento</label>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="listeningFromMe" name="listeningFromMe" 
                               <?php echo ($typebot_settings['listening_from_me'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="listeningFromMe">
                            Responder minhas próprias mensagens
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="stopBotFromMe" name="stopBotFromMe"
                               <?php echo ($typebot_settings['stop_bot_from_me'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="stopBotFromMe">
                            Parar bot quando eu responder
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </form>
        </div>
    </div>
</div>

<script src="modal/typebot.js"></script> 