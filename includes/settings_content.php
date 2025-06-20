<?php
// File Hash: fae8d087ea59260baf135eca8547d299


$user_id = $_SESSION['user_id'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Buscar categorias
$stmt = $conn->prepare("SELECT * FROM categorias_delivery ORDER BY id ASC");
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar produtos
$stmt = $conn->prepare("SELECT p.*, c.item as categoria_nome 
                       FROM produtos_delivery p 
                       LEFT JOIN categorias_delivery c ON p.categoria_id = c.id_categoria 
                       ORDER BY p.categoria_id, p.item");
$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar dados do estabelecimento
$stmt = $conn->prepare("SELECT * FROM estabelecimento WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$estabelecimento = $stmt->get_result()->fetch_assoc();

?>

<div class="settings-container">
    <!-- Sub-abas de Configurações -->
    <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab">
                <i class="fas fa-user-circle"></i> Perfil
            </a>
        </li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
     <!--   <li class="nav-item">
            <a class="nav-link" id="license-tab" data-bs-toggle="tab" href="#license" role="tab">
                <i class="fas fa-key"></i> Licença
            </a>
        </li>-->
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" id="clientes-tab" data-bs-toggle="tab" href="#clientes" role="tab">
                <i class="fas fa-users"></i> Clientes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="categories-tab" data-bs-toggle="tab" href="#categories" role="tab">
                <i class="fas fa-tags"></i> Categorias
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#products" role="tab">
                <i class="fas fa-hamburger"></i> Produtos
            </a>
        </li>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" id="permissions-tab" data-bs-toggle="tab" href="#permissions" role="tab">
                <i class="fas fa-user-shield"></i> Permissões
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" id="estabelecimento-tab" data-bs-toggle="tab" href="#estabelecimento" role="tab">
                <i class="fas fa-store"></i> Dados do Estabelecimento
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link" id="parametrosestabelecimento-tab" data-bs-toggle="tab" href="#parametrosestabelecimento" role="tab">
                <i class="fas fa-store"></i> Parâmetros do Estabelecimento
            </a>
        </li>
    </ul>

    <!-- Conteúdo das Sub-abas -->
    <div class="tab-content" id="settingsTabContent">
        <!-- Aba de Perfil -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <div class="settings-card">
                <h4><i class="fas fa-user-circle"></i> Configurações de Perfil</h4>
                <form id="profileForm" class="mt-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nome de Usuário</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="newPassword" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="newPassword" name="newPassword">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar Senha
                    </button>
                </form>
            </div>
        </div>

        <!-- Aba de Clientes -->
        <div class="tab-pane fade" id="clientes" role="tabpanel">
            <div class="row mb-4">
                <!-- Lista de Clientes -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Gerenciar Clientes</h5>
                            <button class="btn btn-primary" onclick="abrirClienteModal()">
                                <i class="fas fa-user-plus"></i> Cadastrar Cliente
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Telefone</th>
                                            <th>Endereço</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $conn->prepare("SELECT * FROM clientes_delivery ORDER BY nome");
                                        $stmt->execute();
                                        $clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                        
                                        if (empty($clientes)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                                <p class="mb-0">Nenhum cliente cadastrado</p>
                                            </td>
                                        </tr>
                                        <?php else:
                                        foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($cliente['rua']) . ', ' . 
                                                     htmlspecialchars($cliente['bairro']);
                                                if (!empty($cliente['complemento'])) {
                                                    echo ' - ' . htmlspecialchars($cliente['complemento']);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editarCliente(<?php echo $cliente['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="excluirCliente(<?php echo $cliente['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; 
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cliente -->
        <div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="clienteForm" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clienteModalLabel">Cadastro de Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="clienteId">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" id="clienteNome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" id="clienteTelefone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="cep" id="clienteCep" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rua</label>
                            <input type="text" class="form-control" name="rua" id="clienteRua" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bairro</label>
                            <input type="text" class="form-control" name="bairro" id="clienteBairro" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Complemento</label>
                            <input type="text" class="form-control" name="complemento" id="clienteComplemento">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="limparFormulario()">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aba de Categorias -->
        <div class="tab-pane fade" id="categories" role="tabpanel">
            <div class="settings-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-tags"></i> Gestão de Categorias</h4>
                    <div>
                        <button class="btn btn-primary" onclick="novaCategoria()">
                            <i class="fas fa-plus"></i> Nova Categoria
                        </button>
                        <button class="btn btn-warning ms-2" onclick="resetarCategorias()">
                            <i class="fas fa-sync"></i> Resetar Categorias
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Categoria</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-tags fa-3x text-muted mb-3 d-block"></i>
                                    <p class="mb-0">Nenhuma categoria cadastrada</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id']; ?></td>
                                <td><?php echo $categoria['item']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editarCategoria(<?php echo json_encode($categoria, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="excluirCategoria(<?php echo $categoria['id']; ?>)">
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
        </div>

        <!-- Aba de Produtos -->
        <div class="tab-pane fade" id="products" role="tabpanel">
            <div class="settings-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-hamburger"></i> Produtos</h4>
                    <div>
                        <button class="btn btn-primary" onclick="novoProduto()">
                            <i class="fas fa-plus"></i> Novo Produto
                        </button>
                        <button class="btn btn-warning ms-2" onclick="resetarProdutos()">
                            <i class="fas fa-sync"></i> Resetar Produtos
                        </button>
                    </div>
                </div>

                <!-- Produtos Disponíveis -->
                <h5 class="mb-3">Produtos Disponíveis</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Valor</th>
                                <th>Categoria</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-hamburger fa-3x text-muted mb-3 d-block"></i>
                                    <p class="mb-0">Nenhum produto cadastrado</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($produtos as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['item']); ?></td>
                                <td>R$ <?php echo number_format($produto['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editarProduto(<?php echo json_encode($produto, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="excluirProduto(<?php echo $produto['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="indisponibilizarProduto(<?php echo $produto['id']; ?>)">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Produtos Indisponíveis -->
                <h5 class="mt-4 mb-3">Produtos Indisponíveis</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Valor</th>
                                <th>Categoria</th>
                                <th>Data Indisponibilidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT pi.*, c.item as categoria_nome 
                                              FROM produtos_indisponiveis pi 
                                              LEFT JOIN categorias_delivery c ON pi.categoria_id = c.id_categoria 
                                              ORDER BY pi.data_indisponivel DESC");
                            $stmt->execute();
                            $produtos_indisponiveis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            if (empty($produtos_indisponiveis)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-ban fa-3x text-muted mb-3 d-block"></i>
                                    <p class="mb-0">Nenhum produto indisponível no momento</p>
                                </td>
                            </tr>
                            <?php else:
                            foreach($produtos_indisponiveis as $produto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($produto['item']); ?></td>
                                <td>R$ <?php echo number_format($produto['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($produto['data_indisponivel'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="disponibilizarProduto(<?php echo $produto['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Aba de Licença -->
      <!--  <div class="tab-pane fade" id="license" role="tabpanel">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="settings-card">
                    <h4><i class="fas fa-key"></i> Informações da Licença</h4>
                    <?php
                    // Buscar informações da licença
                    $stmt = $conn->prepare("SELECT * FROM license_codes WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $license = $stmt->get_result()->fetch_assoc();
                    ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Código da Licença</label>
                                <input type="text" class="form-control" value="<?php echo $license['code'] ?? ''; ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Validade</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($license['valid_until'])); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <form id="chaveSecretaForm">
                        <div class="mb-3">
                            <label class="form-label">Chave Secreta para Tokens</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="chave_secreta" 
                                       value="<?php echo htmlspecialchars($license['chave_secreta'] ?? 'packtypebot'); ?>" required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save"></i> Salvar Chave
                                </button>
                            </div>
                            <small class="text-muted">Esta chave é usada para gerar e validar tokens de API</small>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-warning m-4">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Você não tem permissão para acessar esta área.
                </div>
            <?php endif; ?>
        </div>

        -->
        <!-- Adicionar conteúdo da aba de Permissões -->
        <div class="tab-pane fade" id="permissions" role="tabpanel">
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="settings-card">
                <h4><i class="fas fa-user-shield"></i> Gerenciar Permissões de Usuários</h4>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Configurações de Registro</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Buscar configuração atual
                        $stmt = $conn->prepare("SELECT value FROM system_settings WHERE setting_name = 'allow_registration'");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $allowRegistration = $result->num_rows > 0 ? $result->fetch_assoc()['value'] : '1';
                        ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allowRegistration" 
                                   <?php echo $allowRegistration == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="allowRegistration">
                                Permitir registro de novos usuários
                            </label>
                        </div>
                        <small class="text-muted">
                            Quando desativado, novos usuários não poderão se registrar no sistema.
                        </small>
                    </div>
                </div>
                
                <div class="table-responsive mt-4">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Função</th>
                                <th>Dashboard</th>
                                <th>Pedidos</th>
                                <th>Movimentação</th>
                                <th>Evolution</th>
                                <th>Typebot</th>
                                <th>Configurações</th>
                                <th>Personalização</th>
                                <th>Estatísticas</th>
                                <th>POS</th>
                                <th>Chaflow</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Debug
                            error_log("Session user_id: " . $_SESSION['user_id']);

                            $stmt = $conn->prepare("
                                SELECT 
                                    u.id,
                                    u.username,
                                    u.role,
                                    COALESCE(up.dashboard_access, 1) as dashboard_access,
                                    COALESCE(up.pedidos_access, 1) as pedidos_access,
                                    COALESCE(up.movimentacao_access, 1) as movimentacao_access,
                                    COALESCE(up.evolution_access, 0) as evolution_access,
                                    COALESCE(up.typebot_access, 0) as typebot_access,
                                    COALESCE(up.settings_access, 0) as settings_access,
                                    COALESCE(up.customization_access, 0) as customization_access,
                                    COALESCE(up.stats_access, 0) as stats_access,
                                    COALESCE(up.pos_access, 1) as pos_access,
                                    COALESCE(up.chaflow_access, 0) as chaflow_access
                                FROM users u 
                                LEFT JOIN user_permissions up ON u.id = up.user_id 
                                WHERE u.id != ?
                            ");
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            // Debug
                            error_log("Found users: " . json_encode($users));
                            
                            foreach($users as $user):
                                // Garantir valores padrão caso não existam permissões
                                $user['dashboard_access'] = isset($user['dashboard_access']) ? $user['dashboard_access'] : 1;
                                $user['pedidos_access'] = isset($user['pedidos_access']) ? $user['pedidos_access'] : 1;
                                $user['movimentacao_access'] = isset($user['movimentacao_access']) ? $user['movimentacao_access'] : 1;
                                $user['evolution_access'] = isset($user['evolution_access']) ? $user['evolution_access'] : 0;
                                $user['typebot_access'] = isset($user['typebot_access']) ? $user['typebot_access'] : 0;
                                $user['settings_access'] = isset($user['settings_access']) ? $user['settings_access'] : 0;
                                $user['customization_access'] = isset($user['customization_access']) ? $user['customization_access'] : 0;
                                $user['stats_access'] = isset($user['stats_access']) ? $user['stats_access'] : 0;
                                $user['pos_access'] = isset($user['pos_access']) ? $user['pos_access'] : 1;
                                $user['chaflow_access'] = isset($user['chaflow_access']) ? $user['chaflow_access'] : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <select class="form-select form-select-sm role-select" 
                                            data-user-id="<?php echo $user['id']; ?>">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Usuário</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="dashboard_access"
                                               <?php echo $user['dashboard_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="pedidos_access"
                                               <?php echo $user['pedidos_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="movimentacao_access"
                                               <?php echo $user['movimentacao_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="evolution_access"
                                               <?php echo $user['evolution_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="typebot_access"
                                               <?php echo $user['typebot_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="settings_access"
                                               <?php echo $user['settings_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="customization_access"
                                               <?php echo $user['customization_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="stats_access"
                                               <?php echo $user['stats_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="pos_access"
                                               <?php echo $user['pos_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input permission-check"
                                               data-user-id="<?php echo $user['id']; ?>"
                                               data-permission="chaflow_access"
                                               <?php echo $user['chaflow_access'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                Você não tem permissão para acessar esta área.
            </div>
            <?php endif; ?>
        </div>

        

                <!-- Adicione o conteúdo da aba de parametros estabelecimento -->
    <div class="tab-pane fade" id="parametrosestabelecimento" role="tabpanel">


        <!-- Configurações e Entregadores -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title2">Configurações de Entrega</h5>
                </div>
                <div class="card-body">
                    <form id="configEntregaForm">
                        <!-- Taxas de Entrega por Região -->
                        <div class="mb-3">
                            <h5>Taxas de Entrega por Região</h5>
                            <div id="taxasContainer">
                                <?php
                                // Buscar taxas existentes
                                $stmt = $conn->prepare("SELECT * FROM taxas_entrega ORDER BY id ASC");
                                $stmt->execute();
                                $taxas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                
                                if (empty($taxas)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhuma região de entrega cadastrada</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($taxas as $taxa): ?>
                                <div class="row mb-2 taxa-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="regioes[]" 
                                               value="<?php echo htmlspecialchars($taxa['regiao']); ?>" 
                                               placeholder="Nome da região" required>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" name="valores[]" 
                                                   value="<?php echo number_format($taxa['valor'], 2, '.', ''); ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remover-taxa" 
                                                onclick="confirmarRemoverRegiao(<?php echo $taxa['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-success mt-2" id="adicionarTaxa">
                                <i class="fas fa-plus"></i> Adicionar Região
                            </button>
                        </div>

                        <!-- Demais configurações existentes -->
                        <div class="mb-3">
                            <h5>Horário de Funcionamento</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Horário de Início</label>
                                    <input type="time" class="form-control" name="horario_inicio" 
                                           value="<?php echo $config_entregas['horario_inicio'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Horário de Fim</label>
                                    <input type="time" class="form-control" name="horario_fim" 
                                           value="<?php echo $config_entregas['horario_fim'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h5>Dias de Funcionamento</h5>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="0" id="dia0" 
                                       <?php echo in_array('0', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia0">Domingo</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="1" id="dia1" 
                                       <?php echo in_array('1', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia1">Segunda</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="2" id="dia2" 
                                       <?php echo in_array('2', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia2">Terça</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="3" id="dia3" 
                                       <?php echo in_array('3', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia3">Quarta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="4" id="dia4" 
                                       <?php echo in_array('4', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia4">Quinta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="5" id="dia5" 
                                       <?php echo in_array('5', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia5">Sexta</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="checkbox" class="form-check-input" name="dias[]" value="6" id="dia6" 
                                       <?php echo in_array('6', $dias_funcionamento) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="dia6">Sábado</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 card-title2">Entregadores</h5>
                    <button class="btn btn-sm btn-primary" onclick="novoEntregador()">
                        <i class="fas fa-plus"></i> Novo Entregador
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Telefone</th>
                                    <th>Veículo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($entregadores)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-motorcycle fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhum entregador cadastrado</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($entregadores as $entregador): ?>
                                <tr>
                                    <td><?php echo $entregador['nome']; ?></td>
                                    <td><?php echo $entregador['telefone']; ?></td>
                                    <td><?php echo $entregador['veiculo']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $entregador['status'] == 'Ativo' ? 'success' : 'warning'; ?>">
                                            <?php echo $entregador['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editarEntregador(<?php echo htmlspecialchars(json_encode($entregador)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="excluirEntregador(<?php echo $entregador['id']; ?>)">
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
            </div>
        </div>
    </div>













    </div>
        <!-- Adicione o conteúdo da aba de Dados do Estabelecimento -->
        <div class="tab-pane fade" id="estabelecimento" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Dados do Estabelecimento</h5>
                </div>
                <div class="card-body">
                    <form id="estabelecimentoForm" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Documento</label>
                                <select class="form-select" name="tipo_documento" id="tipo_documento" required>
                                    <option value="CNPJ" <?php echo ($estabelecimento['tipo_documento'] ?? '') == 'CNPJ' ? 'selected' : ''; ?>>CNPJ</option>
                                    <option value="CPF" <?php echo ($estabelecimento['tipo_documento'] ?? '') == 'CPF' ? 'selected' : ''; ?>>CPF</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número do Documento</label>
                                <input type="text" class="form-control" name="documento" id="documento" value="<?php echo htmlspecialchars($estabelecimento['documento'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Fantasia</label>
                                <input type="text" class="form-control" name="nome_fantasia" value="<?php echo htmlspecialchars($estabelecimento['nome_fantasia'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Razão Social</label>
                                <input type="text" class="form-control" name="razao_social" value="<?php echo htmlspecialchars($estabelecimento['razao_social'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone" value="<?php echo htmlspecialchars($estabelecimento['telefone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($estabelecimento['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">CEP</label>
                                <input type="text" class="form-control" name="cep" id="cep" value="<?php echo htmlspecialchars($estabelecimento['cep'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Endereço</label>
                                <input type="text" class="form-control" name="endereco" id="endereco" value="<?php echo htmlspecialchars($estabelecimento['endereco'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Número</label>
                                <input type="text" class="form-control" name="numero" value="<?php echo htmlspecialchars($estabelecimento['numero'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Complemento</label>
                                <input type="text" class="form-control" name="complemento" value="<?php echo htmlspecialchars($estabelecimento['complemento'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bairro</label>
                                <input type="text" class="form-control" name="bairro" id="bairro" value="<?php echo htmlspecialchars($estabelecimento['bairro'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" name="cidade" id="cidade" value="<?php echo htmlspecialchars($estabelecimento['cidade'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="estado" id="estado" required>
                                    <option value="">Selecione...</option>
                                    <?php
                                    $estados = array(
                                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
                                        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
                                        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
                                        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
                                        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
                                        'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                    );
                                    foreach ($estados as $uf => $nome):
                                        $selected = ($estabelecimento['estado'] ?? '') == $uf ? 'selected' : '';
                                        echo "<option value=\"$uf\" $selected>$nome</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL do Logo</label>
                            <input type="url" class="form-control" name="logo_url" value="<?php echo htmlspecialchars($estabelecimento['logo_url'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Dados
                        </button>
                    </form>

                    <?php if ($estabelecimento): ?>
                    <hr class="my-4">
                    <h5 class="mb-3">Dados Cadastrados</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <tbody>
                                <tr>
                                    <th width="200">Tipo de Documento</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['tipo_documento']); ?></td>
                                </tr>
                                <tr>
                                    <th>Documento</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['documento']); ?></td>
                                </tr>
                                <tr>
                                    <th>Nome Fantasia</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['nome_fantasia']); ?></td>
                                </tr>
                                <tr>
                                    <th>Razão Social</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['razao_social']); ?></td>
                                </tr>
                                <tr>
                                    <th>Telefone</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['telefone']); ?></td>
                                </tr>
                                <tr>
                                    <th>E-mail</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>CEP</th>
                                    <td><?php echo htmlspecialchars($estabelecimento['cep']); ?></td>
                                </tr>
                                <tr>
                                    <th>Endereço</th>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($estabelecimento['endereco']) . ', ' . 
                                             htmlspecialchars($estabelecimento['numero']);
                                        if (!empty($estabelecimento['complemento'])) {
                                            echo ' - ' . htmlspecialchars($estabelecimento['complemento']);
                                        }
                                        echo '<br>' . 
                                             htmlspecialchars($estabelecimento['bairro']) . ', ' .
                                             htmlspecialchars($estabelecimento['cidade']) . '/' .
                                             htmlspecialchars($estabelecimento['estado']);
                                        ?>
                                    </td>
                                </tr>
                                <?php if (!empty($estabelecimento['logo_url'])): ?>
                                <tr>
                                    <th>Logo</th>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($estabelecimento['logo_url']); ?>" 
                                             alt="Logo" style="max-height: 50px;">
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary" id="btnEditarEstabelecimento">
                            <i class="fas fa-edit"></i> Editar Dados
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script para atualização de senha
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!newPassword || !confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'As senhas não podem estar vazias'
        });
        return;
    }
    
    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'As senhas não coincidem'
        });
        return;
    }
    
    fetch('./ajax/update_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `newPassword=${encodeURIComponent(newPassword)}&confirmPassword=${encodeURIComponent(confirmPassword)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Senha atualizada com sucesso!'
            }).then(() => {
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
            });
        } else {
            throw new Error(data.message || 'Erro ao atualizar senha');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
});

// Adiciona evento de change para os selects de função
document.querySelectorAll('.role-select').forEach(select => {
    select.addEventListener('change', function() {
        const userId = this.dataset.userId;
        const role = this.value;

        // Mostra loading
        Swal.fire({
            title: 'Salvando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Envia requisição AJAX
        fetch('./ajax/save_user_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&role=${role}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            // Mostra mensagem de sucesso
            Swal.fire({
                icon: 'success',
                title: 'Função atualizada com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            // Reverte o select para o valor anterior
            this.value = this.value === 'user' ? 'admin' : 'user';
            // Mostra mensagem de erro
            Swal.fire({
                icon: 'error',
                title: 'Erro ao salvar função',
                text: error.message
            });
        });
    });
});

// Adiciona evento de change para os checkboxes de permissão
document.querySelectorAll('.permission-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const userId = this.dataset.userId;
        const permission = this.dataset.permission;
        const value = this.checked;

        // Debug
        console.log('Enviando dados:', {
            userId,
            permission,
            value
        });

        // Mostra loading
        Swal.fire({
            title: 'Salvando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Envia requisição AJAX
        fetch('./ajax/save_permissions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                user_id: userId,
                permission: permission,
                value: value
            }).toString()
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            // Mostra mensagem de sucesso
            Swal.fire({
                icon: 'success',
                title: 'Permissão atualizada com sucesso!',
                showConfirmButton: false,
                timer: 1500
            });
        })
        .catch(error => {
            console.error('Erro:', error);
            // Reverte o checkbox para o estado anterior
            this.checked = !value;
            // Mostra mensagem de erro
            Swal.fire({
                icon: 'error',
                title: 'Erro ao salvar permissão',
                text: error.message
            });
        });
    });
});

// Funções para gerenciar categorias
function novaCategoria() {
    Swal.fire({
        title: 'Nova Categoria',
        html: `<input type="text" id="categoria_nome" class="swal2-input" placeholder="Nome da categoria">`,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nome = document.getElementById('categoria_nome').value;
            if (!nome) {
                Swal.showValidationMessage('Por favor, insira um nome para a categoria');
            }
            return { nome };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/add_categoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `nome=${encodeURIComponent(result.value.nome)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Categoria adicionada com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao adicionar categoria');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

function editarCategoria(categoria) {
    Swal.fire({
        title: 'Editar Categoria',
        html: `<input type="text" id="categoria_nome" class="swal2-input" value="${categoria.item}">`,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nome = document.getElementById('categoria_nome').value;
            if (!nome) {
                Swal.showValidationMessage('Por favor, insira um nome para a categoria');
            }
            return { nome };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/edit_categoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${categoria.id}&nome=${encodeURIComponent(result.value.nome)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Categoria atualizada com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao atualizar categoria');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

function excluirCategoria(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/delete_categoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Categoria excluída com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao excluir categoria');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

// Funções para gerenciar produtos
function novoProduto() {
    Swal.fire({
        title: 'Novo Produto',
        html: `
            <input type="text" id="produto_nome" class="swal2-input" placeholder="Nome do produto">
            <input type="number" id="produto_valor" class="swal2-input" placeholder="Valor" step="0.01">
            <select id="produto_categoria" class="swal2-input">
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['item']); ?></option>
                <?php endforeach; ?>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nome = document.getElementById('produto_nome').value;
            const valor = document.getElementById('produto_valor').value;
            const categoria = document.getElementById('produto_categoria').value;
            
            if (!nome || !valor || !categoria) {
                Swal.showValidationMessage('Por favor, preencha todos os campos');
            }
            return { nome, valor, categoria };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/add_produto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `nome=${encodeURIComponent(result.value.nome)}&valor=${result.value.valor}&categoria=${result.value.categoria}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Produto adicionado com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao adicionar produto');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

function editarProduto(produto) {
    Swal.fire({
        title: 'Editar Produto',
        html: `
            <input type="text" id="produto_nome" class="swal2-input" value="${produto.item}">
            <input type="number" id="produto_valor" class="swal2-input" value="${produto.valor}" step="0.01">
            <select id="produto_categoria" class="swal2-input">
                <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" ${produto.categoria_id == <?php echo $cat['id']; ?> ? 'selected' : ''}>
                    <?php echo htmlspecialchars($cat['item']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nome = document.getElementById('produto_nome').value;
            const valor = document.getElementById('produto_valor').value;
            const categoria = document.getElementById('produto_categoria').value;
            
            if (!nome || !valor || !categoria) {
                Swal.showValidationMessage('Por favor, preencha todos os campos');
            }
            return { nome, valor, categoria };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/edit_produto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${produto.id}&nome=${encodeURIComponent(result.value.nome)}&valor=${result.value.valor}&categoria=${result.value.categoria}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Produto atualizado com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao atualizar produto');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

function excluirProduto(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/delete_produto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', 'Produto excluído com sucesso!', 'success')
                    .then(() => location.reload());
                } else {
                    throw new Error(data.message || 'Erro ao excluir produto');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', error.message, 'error');
            });
        }
    });
}

// Gerenciamento das abas
let clienteModal;
document.addEventListener('DOMContentLoaded', function() {
    // Recuperar última aba ativa das configurações
    const lastSettingsTab = localStorage.getItem('settingsLastTab');
    if (lastSettingsTab) {
        const tab = new bootstrap.Tab(document.querySelector(lastSettingsTab));
        tab.show();
    }

    // Salvar aba ativa das configurações quando mudar
    document.querySelectorAll('#settingsTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            const tabId = this.getAttribute('href');
            localStorage.setItem('settingsLastTab', '#' + this.id);
            
            // Manter a aba principal em "Configurações"
            localStorage.setItem('activeTab', 'settings');
            
            // Atualizar menu lateral
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-tab') === 'settings') {
                    item.classList.add('active');
                }
            });

            // Mostrar a aba usando Bootstrap Tab
            const bsTab = new bootstrap.Tab(this);
            bsTab.show();
        });
    });

    // Garantir que a aba principal permaneça em "Configurações"
    const settingsMenuItem = document.querySelector('.menu-item[data-tab="settings"]');
    if (settingsMenuItem) {
        settingsMenuItem.classList.add('active');
    }

    clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'));
    aplicarMascaraTelefone(document.getElementById('clienteTelefone'));
});

function editarCliente(id) {
    // Buscar dados do cliente
    fetch(`./ajax/buscar_cliente.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cliente = data.cliente;
                document.getElementById('clienteId').value = cliente.id;
                document.getElementById('clienteNome').value = cliente.nome;
                document.getElementById('clienteTelefone').value = cliente.telefone;
                document.getElementById('clienteCep').value = cliente.cep;
                document.getElementById('clienteRua').value = cliente.rua;
                document.getElementById('clienteBairro').value = cliente.bairro;
                document.getElementById('clienteComplemento').value = cliente.complemento || '';
                clienteModal.show();
                document.getElementById('clienteTelefone').dispatchEvent(new Event('input'));
                document.getElementById('clienteNome').focus();
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

// Limpar formulário
function limparFormulario() {
    document.getElementById('clienteForm').reset();
    document.getElementById('clienteId').value = '';
}

function abrirClienteModal() {
    limparFormulario();
    clienteModal.show();
    document.getElementById('clienteNome').focus();
    document.getElementById('clienteTelefone').dispatchEvent(new Event('input'));
}

function aplicarMascaraTelefone(input) {
    input.addEventListener('input', function(e) {
        let v = input.value.replace(/\D/g, '').slice(0,11);
        if (v.length >= 11) {
            v = v.replace(/(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else if (v.length >= 10) {
            v = v.replace(/(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
        } else if (v.length > 2) {
            v = v.replace(/(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            v = v.replace(/(\d*)/, '($1');
        }
        input.value = v;
    });
}

// Manipular envio do formulário
document.getElementById('clienteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('./ajax/salvar_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
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
});

function excluirCliente(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Tem certeza que deseja excluir este cliente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('./ajax/excluir_cliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
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

document.getElementById('chaveSecretaForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const response = await fetch('./ajax/save_chave_secreta.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new TypeError("Oops, não recebemos JSON!");
        }

        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: data.message || 'Chave secreta atualizada com sucesso',
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            throw new Error(data.message || 'Erro ao atualizar chave secreta');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Erro ao atualizar chave secreta'
        });
    }
});

function indisponibilizarProduto(id) {
    Swal.fire({
        title: "Indisponibilizar produto?",
        text: "O produto ficará indisponível para pedidos",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sim, indisponibilizar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("./ajax/indisponibilizar_produto.php", { id: id })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Produto indisponibilizado!",
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Erro!",
                            text: response.message
                        });
                    }
                })
                .fail(function() {
                    Swal.fire({
                        icon: "error",
                        title: "Erro!",
                        text: "Erro ao processar requisição"
                    });
                });
        }
    });
}

function disponibilizarProduto(id) {
    Swal.fire({
        title: "Disponibilizar produto?",
        text: "O produto voltará a ficar disponível para pedidos",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sim, disponibilizar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("./ajax/disponibilizar_produto.php", { id: id })
                .done(function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Produto disponibilizado!",
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Erro!",
                            text: response.message
                        });
                    }
                })
                .fail(function() {
                    Swal.fire({
                        icon: "error",
                        title: "Erro!",
                        text: "Erro ao processar requisição"
                    });
                });
        }
    });
}

document.getElementById('btnEditarEstabelecimento')?.addEventListener('click', function() {
    if (typeof editarEstabelecimento === 'function') {
        editarEstabelecimento();
    }
});

// Gerenciar permissão de registro
document.getElementById('allowRegistration')?.addEventListener('change', function() {
    const isChecked = this.checked;
    
    fetch('./ajax/toggle_registration.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `allow=${isChecked ? 1 : 0}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Configuração atualizada!',
                text: `Registro de novos usuários ${isChecked ? 'habilitado' : 'desabilitado'} com sucesso!`,
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            throw new Error(data.message || 'Erro ao atualizar configuração');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
        // Reverter o switch em caso de erro
        this.checked = !isChecked;
    });
});
</script>

<style>
/* Estilização das sub-abas */
#settingsTabs {
    border-bottom: 2px solid var(--primary-color);
    margin-bottom: 2rem;
}

#settingsTabs .nav-link {
    color: var(--text-color);
    border: none;
    padding: 0.75rem 1.5rem;
    margin-right: 0.5rem;
    border-radius: 8px 8px 0 0;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#settingsTabs .nav-link i {
    font-size: 1.1rem;
}

#settingsTabs .nav-link.active {
    color: white;
    background-color: var(--primary-color);
}

#settingsTabs .nav-link:not(.active):hover {
    background-color: rgba(var(--primary-color-rgb), 0.1);
}

/* Animação suave para troca de abas */
.tab-pane {
    transition: all 0.3s ease;
}

.tab-pane.fade {
    opacity: 0;
    transform: translateY(10px);
}

.tab-pane.fade.show {
    opacity: 1;
    transform: translateY(0);
}
.card-header {
    color: #000;
}
</style>

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>

<script src="modal/settings.js"></script>
<script src="assets/js/settings.js"></script>