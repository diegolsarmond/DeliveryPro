<?php
// File Hash: f379b67ca20f8c80c6b6dce4a0dd0633

// Verificar se existem configurações salvas
$stmt = $conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$evolution_settings = $stmt->get_result()->fetch_assoc();

?>

<div class="evolution-container">
    <!-- Configurações da API - Apenas para Admin -->
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="settings-card mb-4">
            <h4><i class="fas fa-cog"></i> Configurações da EvolutionAPI</h4>
            <form id="evolutionSettingsForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Base URL</label>
                        <input type="text" class="form-control" name="base_url" 
                               value="<?php echo $evolution_settings['base_url'] ?? ''; ?>" required>
                        <small class="text-muted">Endereço da sua API</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">API Key Global</label>
                        <input type="text" class="form-control" name="api_key" 
                               value="<?php echo $evolution_settings['api_global'] ?? ''; ?>" required>
                        <small class="text-muted">Esta é a chave global usada para gerenciar instâncias</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Gerenciamento de Instâncias -->
    <div class="settings-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-server"></i> Instâncias WhatsApp</h4>
            <button type="button" class="btn btn-primary" onclick="showCreateInstanceModal()">
                <i class="fas fa-plus"></i> Nova Instância
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nome da Instância</th>
                        <th>Status</th>
                        <th>API Key</th>
                        <th>QR Code</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="instancesTableBody">
                    <?php
                    require_once 'classes/EvolutionInstance.php';
                    $evolutionInstance = new EvolutionInstance($conn, $user_id);
                    
                    try {
                        $result = $evolutionInstance->fetchInstances();
                        if ($result['success'] && !empty($result['instances'])) {
                            foreach ($result['instances'] as $instance) {
                                $statusClass = '';
                                switch ($instance['status']) {
                                    case 'connected':
                                        $statusClass = 'text-success';
                                        break;
                                    case 'connecting':
                                        $statusClass = 'text-warning';
                                        break;
                                    case 'disconnected':
                                        $statusClass = 'text-danger';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($instance['instanceName']); ?></td>
                                    <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($instance['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($instance['hash']); ?></td>
                                    <td>
                                        <?php if ($instance['status'] === 'connecting'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="showQRCode('<?php echo htmlspecialchars($instance['instanceName']); ?>')">
                                                <i class="fas fa-qrcode"></i> Ver QR Code
                                            </button>
                                        <?php elseif ($instance['status'] === 'connected'): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Conectado</span>
                                        <?php else: ?>
                                            <span class="text-muted">QR Code não disponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($instance['status'] === 'disconnected'): ?>
                                            <button class="btn btn-sm btn-success" onclick="connectInstance('<?php echo htmlspecialchars($instance['instanceName']); ?>')">
                                                <i class="fas fa-plug"></i> Conectar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-warning" onclick="logoutInstance('<?php echo htmlspecialchars($instance['instanceName']); ?>')">
                                                <i class="fas fa-power-off"></i> Desconectar
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($instance['id']) && $instance['id'] == 1): ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSymbolicInstance(1)">
                                                <i class="fas fa-database"></i> Excluir
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" onclick="deleteInstance('<?php echo htmlspecialchars($instance['instanceName']); ?>')">
                                                <i class="fas fa-trash"></i> Excluir
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">Nenhuma instância encontrada</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar instâncias: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para criar nova instância -->
    <div class="modal fade" id="createInstanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Instância</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createInstanceForm">
                        <div class="mb-3">
                            <label class="form-label">Nome da Instância</label>
                            <input type="text" class="form-control" name="instanceName" required>
                            <small class="text-muted">Use apenas letras, números e underscores</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="createInstance()">Criar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para exibir QR Code -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="qrCodeImage" src="" alt="QR Code" style="max-width: 100%;">
                </div>
            </div>
        </div>
    </div>

    <!-- Typebot Settings -->
    <div class="settings-card mb-4">
        <h4><i class="fas fa-robot"></i> Configurações do Typebot</h4>
        <?php
        // Buscar configurações do Typebot
        $stmt = $conn->prepare("SELECT settings, bot_id, bot_enabled FROM typebot_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $typebot_config = $stmt->get_result()->fetch_assoc();
        $typebot_settings = $typebot_config ? json_decode($typebot_config['settings'], true) : [];
        $bot_connected = !empty($typebot_config['bot_id']);
        $bot_enabled = $bot_connected && $typebot_config['bot_enabled'];
        ?>
        <form id="typebotSettingsForm">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">URL do Typebot</label>
                    <input type="url" class="form-control" name="url" 
                           value="<?php echo htmlspecialchars($typebot_settings['url'] ?? ''); ?>" 
                           placeholder="https://typebot.io" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Typebot</label>
                    <input type="text" class="form-control" name="typebot" 
                           value="<?php echo htmlspecialchars($typebot_settings['typebot'] ?? ''); ?>"
                           placeholder="deliverypro" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de Gatilho</label>
                    <select class="form-control" name="triggerType" required>
                        <option value="keyword" <?php echo ($typebot_settings['triggerType'] ?? '') === 'keyword' ? 'selected' : ''; ?>>Palavra-chave</option>
                        <option value="all" <?php echo ($typebot_settings['triggerType'] ?? '') === 'all' ? 'selected' : ''; ?>>Todas as mensagens</option>
                        <option value="none" <?php echo ($typebot_settings['triggerType'] ?? '') === 'none' ? 'selected' : ''; ?>>Nenhum (manual)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Operador do Gatilho</label>
                    <select class="form-control" name="triggerOperator" required>
                        <option value="contains" <?php echo ($typebot_settings['triggerOperator'] ?? '') === 'contains' ? 'selected' : ''; ?>>Contém</option>
                        <option value="equals" <?php echo ($typebot_settings['triggerOperator'] ?? '') === 'equals' ? 'selected' : ''; ?>>Igual a</option>
                        <option value="startsWith" <?php echo ($typebot_settings['triggerOperator'] ?? '') === 'startsWith' ? 'selected' : ''; ?>>Começa com</option>
                        <option value="endsWith" <?php echo ($typebot_settings['triggerOperator'] ?? '') === 'endsWith' ? 'selected' : ''; ?>>Termina com</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Palavra-chave de Gatilho</label>
                    <input type="text" class="form-control" name="triggerValue" 
                           value="<?php echo htmlspecialchars($typebot_settings['triggerValue'] ?? 'Olá'); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Palavra-chave de Finalização</label>
                    <input type="text" class="form-control" name="keywordFinish" 
                           value="<?php echo htmlspecialchars($typebot_settings['keywordFinish'] ?? '#SAIR'); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mensagem Desconhecida</label>
                    <input type="text" class="form-control" name="unknownMessage" 
                           value="<?php echo htmlspecialchars($typebot_settings['unknownMessage'] ?? 'Mensagem não reconhecida'); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tempo de Expiração (minutos)</label>
                    <input type="number" class="form-control" name="expire" 
                           value="<?php echo (int)($typebot_settings['expire'] ?? 20); ?>" min="1" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Delay de Mensagem (ms)</label>
                    <input type="number" class="form-control" name="delayMessage" 
                           value="<?php echo (int)($typebot_settings['delayMessage'] ?? 1000); ?>" min="0" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tempo de Debounce (s)</label>
                    <input type="number" class="form-control" name="debounceTime" 
                           value="<?php echo (int)($typebot_settings['debounceTime'] ?? 10); ?>" min="1" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="listeningFromMe" id="listeningFromMe"
                               <?php echo ($typebot_settings['listeningFromMe'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="listeningFromMe">Escutar minhas mensagens</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="stopBotFromMe" id="stopBotFromMe"
                               <?php echo ($typebot_settings['stopBotFromMe'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="stopBotFromMe">Parar bot com minhas mensagens</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="keepOpen" id="keepOpen"
                               <?php echo ($typebot_settings['keepOpen'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="keepOpen">Manter sessão aberta</label>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
                <?php if (!$bot_connected): ?>
                    <button type="button" class="btn btn-success ms-2" onclick="activateTypebot()">
                        <i class="fas fa-play"></i> Conectar Typebot
                    </button>
                <?php else: ?>
                    <button type="button" class="btn <?php echo $bot_enabled ? 'btn-warning' : 'btn-success'; ?> ms-2" 
                            onclick="toggleTypebot(<?php echo !$bot_enabled ? 'true' : 'false'; ?>)">
                        <i class="fas fa-<?php echo $bot_enabled ? 'pause' : 'play'; ?>"></i>
                        <?php echo $bot_enabled ? 'Pausar Typebot' : 'Reiniciar Typebot'; ?>
                    </button>
                    <button type="button" class="btn btn-danger ms-2" onclick="disconnectTypebot()">
                        <i class="fas fa-stop"></i> Desconectar Typebot
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Mensagens Personalizadas -->
    <div class="settings-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="fas fa-comment-dots"></i> Mensagens Personalizadas</h4>
            <button type="button" class="btn btn-primary" onclick="showAddMessageModal()">
                <i class="fas fa-plus"></i> Nova Mensagem
            </button>
        </div>

        <!-- Abas para categorias -->
        <style>
            .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
                color: var(--bs-nav-pills-link-active-color);
                background-color: <?php echo $config['primary_color']; ?>;
            }

            .nav-link {
                color: #000000;
            }

            .nav-link:focus, .nav-link:hover {
                color: <?php echo $config['primary_hover_color']; ?>;
            }
        </style>

        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="pills-agradecimento-tab" data-bs-toggle="pill" href="#pills-agradecimento" role="tab">Agradecimento</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-entrega-tab" data-bs-toggle="pill" href="#pills-entrega" role="tab">Entrega</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-pedidorecebido-tab" data-bs-toggle="pill" href="#pills-pedidorecebido" role="tab">Pedido Recebido</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-problemas-tab" data-bs-toggle="pill" href="#pills-problemas" role="tab">Problemas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pills-entregadores-tab" data-bs-toggle="pill" href="#pills-entregadores" role="tab">Entregadores</a>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <?php
            // Buscar mensagens do banco de dados
            $stmt = $conn->prepare("SELECT * FROM mensagens_personalizadas ORDER BY categoria, nome_mensagem");
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Agrupar mensagens por categoria
            $mensagens = [];
            while ($row = $result->fetch_assoc()) {
                $mensagens[$row['categoria']][] = $row;
            }

            // Array com mapeamento de categorias para IDs
            $categorias = [
                'Agradecimento' => 'agradecimento',
                'Entrega' => 'entrega',
                'Pedido Recebido' => 'pedidorecebido',
                'Problemas' => 'problemas',
                'Entregadores' => 'entregadores'
            ];
            ?>

            <!-- Conteúdo das abas -->
            <?php foreach($categorias as $categoria => $id): ?>
                <div class="tab-pane fade <?php echo $categoria === 'Agradecimento' ? 'show active' : ''; ?>" 
                     id="pills-<?php echo $id; ?>" 
                     role="tabpanel">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Template</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($mensagens[$categoria])): ?>
                                    <?php foreach ($mensagens[$categoria] as $msg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($msg['nome_mensagem']); ?></td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 500px;" 
                                                     title="<?php echo htmlspecialchars($msg['template']); ?>">
                                                    <?php echo htmlspecialchars($msg['template']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary me-2" 
                                                        onclick='editMessage(<?php echo json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function showAddMessageModal(isEdit = false) {
    Swal.fire({
        title: 'Nova Mensagem',
        html: `
            <form id="messageForm">
                <input type="hidden" id="messageId" name="id">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" id="messageName" name="nome_mensagem" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select class="form-control" id="messageCategory" name="categoria" required>
                        <option value="">Selecione uma categoria</option>
                        <option value="Pedido Recebido">Pedido Recebido</option>
                        <option value="Entrega">Entrega</option>
                        <option value="Problemas">Problemas</option>
                        <option value="Entregadores">Entregadores</option>
                        <option value="Agradecimento">Agradecimento</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem</label>
                    <textarea class="form-control" id="messageTemplate" name="template" rows="4" required></textarea>
                    <small class="text-muted">Variáveis: $nome, $pedido, $total, $nome_entregador, $endereco</small>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('messageForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return false;
            }

            const formData = new FormData(form);
            
            fetch('ajax/save_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Mensagem salva com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Salvar aba ativa antes do reload
                        localStorage.setItem('activeTab', 'evolution');
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao salvar mensagem');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}

function editMessage(message) {
    try {
        // Converter string para objeto se necessário
        const msg = typeof message === 'string' ? JSON.parse(message) : message;
        
        // Debug
        console.log('Mensagem recebida:', msg);
        
        // Abrir modal sem resetar
        Swal.fire({
            title: 'Editar Mensagem',
            html: `
                <form id="messageForm">
                    <input type="hidden" id="messageId" name="id" value="${msg.id}">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="messageName" name="nome_mensagem" value="${msg.nome_mensagem}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-control" id="messageCategory" name="categoria" required>
                            <option value="">Selecione uma categoria</option>
                            <option value="Pedido Recebido" ${msg.categoria === 'Pedido Recebido' ? 'selected' : ''}>Pedido Recebido</option>
                            <option value="Entrega" ${msg.categoria === 'Entrega' ? 'selected' : ''}>Entrega</option>
                            <option value="Problemas" ${msg.categoria === 'Problemas' ? 'selected' : ''}>Problemas</option>
                            <option value="Entregadores" ${msg.categoria === 'Entregadores' ? 'selected' : ''}>Entregadores</option>
                            <option value="Agradecimento" ${msg.categoria === 'Agradecimento' ? 'selected' : ''}>Agradecimento</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mensagem</label>
                        <textarea class="form-control" id="messageTemplate" name="template" rows="4" required>${msg.template}</textarea>
                        <small class="text-muted">Variáveis: $nome, $pedido, $total, $nome_entregador, $endereco</small>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const form = document.getElementById('messageForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }

                const formData = new FormData(form);
                
                fetch('ajax/save_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Mensagem salva com sucesso',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Salvar aba ativa antes do reload
                            localStorage.setItem('activeTab', 'evolution');
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Erro ao salvar mensagem');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: error.message
                    });
                });
            }
        });
        
    } catch (error) {
        console.error('Erro ao editar mensagem:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Erro ao carregar dados da mensagem'
        });
    }
}

function deleteMessage(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Tem certeza que deseja excluir esta mensagem?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/delete_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Mensagem excluída com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Salvar aba ativa antes do reload
                        localStorage.setItem('activeTab', 'evolution');
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}

function deleteSymbolicInstance(id) {
    Swal.fire({
        title: 'Atenção!',
        text: 'Certifique-se de que já tenha criado a sua primeira instância, pois, sem ela, o painel não funcionará corretamente! Deseja continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/delete_symbolic_instance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Instância excluída com sucesso do banco de dados',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao excluir instância');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}
</script>

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>