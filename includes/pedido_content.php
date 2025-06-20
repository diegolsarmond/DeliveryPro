<?php
// File Hash: 3eaaea74009c352f0f2a1bafd6ecb3d7

$itens_por_pagina = 5;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Buscar total de pedidos
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cliente");
$stmt->execute();
$total_pedidos = $stmt->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_pedidos / $itens_por_pagina);


?>

<?php

// Capturar os filtros
$clienteFiltro = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$telefoneFiltro = isset($_GET['telefone']) ? $_GET['telefone'] : '';
$statusFiltro = isset($_GET['status']) ? $_GET['status'] : '';
$pagamentoFiltro = isset($_GET['pagamento']) ? $_GET['pagamento'] : '';

// Modificar a consulta SQL para incluir filtros
$sql = "SELECT c.*, m.numero as numero_mesa, m.status as mesa_status 
        FROM cliente c 
        LEFT JOIN mesas m ON c.mesa_id = m.id 
        WHERE 1=1";
if ($clienteFiltro) {
    $sql .= " AND nome LIKE ?";
}
if ($telefoneFiltro) {
    $sql .= " AND telefone LIKE ?";
}
if ($statusFiltro) {
    $sql .= " AND status = ?";
}
if ($pagamentoFiltro) {
    $sql .= " AND pagamento = ?";
}

// Adicionar ORDER BY para mostrar os últimos pedidos primeiro
$sql .= " ORDER BY c.id DESC";

// Adicionar LIMIT e OFFSET para paginação
$sql .= " LIMIT ? OFFSET ?";

// Preparar a consulta
$stmt = $conn->prepare($sql);

// Bind dos parâmetros
$params = [];
$types = '';
if ($clienteFiltro) {
    $params[] = '%' . $clienteFiltro . '%';
    $types .= 's';
}
if ($telefoneFiltro) {
    $params[] = '%' . $telefoneFiltro . '%';
    $types .= 's';
}
if ($statusFiltro) {
    $params[] = $statusFiltro;
    $types .= 's';
}
if ($pagamentoFiltro) {
    $params[] = $pagamentoFiltro;
    $types .= 's';
}

// Adicionar parâmetros de paginação
$params[] = $itens_por_pagina;
$params[] = $offset;
$types .= 'ii';

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar totais por status
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cliente WHERE status = 'Pendente'");
$stmt->execute();
$total_pendentes = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cliente WHERE status = 'Em Preparo'");
$stmt->execute();
$total_preparo = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM cliente WHERE status = 'Pronto para Entrega'");
$stmt->execute();
$total_pronto = $stmt->get_result()->fetch_assoc()['total'];
?>

<div class="pedido-container">
    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total de Vendas</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT SUM(REPLACE(REPLACE(total, 'R$', ''), ',', '.')) as total FROM cliente WHERE status != 'Cancelado'");
                            $stmt->execute();
                            $total = $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                            <h3>R$ <?php echo number_format($total ?? 0, 2, ',', '.'); ?></h3>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total de Cancelados</h5>
                            <?php
                            $stmt = $conn->prepare("SELECT SUM(REPLACE(REPLACE(total, 'R$', ''), ',', '.')) as total FROM cliente WHERE status = 'Cancelado'");
                            $stmt->execute();
                            $total_cancelados = $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                            <h3>R$ <?php echo number_format($total_cancelados ?? 0, 2, ',', '.'); ?></h3>
                        </div>
                        <i class="fas fa-ban fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-02">
                        <i class="fas fa-clock"></i> Pedidos Pendentes 
                        <span class="badge bg-secondary"><?php echo $total_pendentes; ?></span>
                    </h5>
                </div>
                <div class="card-body pedidos-list">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT c.*, m.numero as numero_mesa, m.status as mesa_status 
                        FROM cliente c 
                        LEFT JOIN mesas m ON c.mesa_id = m.id 
                        WHERE c.status = 'Pendente' 
                        ORDER BY c.data DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $pedidos_pendentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($pedidos_pendentes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-0">Nenhum pedido pendente</p>
                        </div>
                    <?php else:
                        foreach($pedidos_pendentes as $pedido): ?>
                    <div class="pedido-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php if ($pedido['tipo'] === 'pos'): ?>
                                <span class="badge bg-info" title="Venda Presencial">
                                    <i class="fas fa-cash-register"></i> Venda Presencial Feito em Balcão
                                </span>
                            <?php endif; ?>
                            <?php if ($pedido['mesa_id'] && !is_null($pedido['numero_mesa'])): ?>
                                <span class="badge bg-warning" title="Mesa">
                                    <i class="fas fa-utensils"></i> Mesa <?php echo $pedido['numero_mesa']; ?>
                                </span>
                            <?php else: ?>
                                <?php if ($pedido['nome'] === 'Cliente Balcão'): ?>
                                    <span class="badge bg-secondary" title="Cliente Balcão">
                                        <i class="fas fa-user"></i> Cliente Balcão Sem Nome
                                    </span>
                                <?php else: ?>
                                    <span><?php echo $pedido['nome']; ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><i class="fas fa-phone text-muted"></i> <?php echo $pedido['telefone']; ?></p>
                        <p class="mb-2"><i class="fas fa-money-bill text-muted"></i> R$ <?php 
                            $valor = str_replace(['R$', ' ', '.'], '', $pedido['total']);
                            $valor = str_replace(',', '.', $valor);
                            echo number_format(floatval($valor), 2, ',', '.'); 
                        ?></p>
                        <div class="btn-group w-100">
                            <button class="btn btn-sm btn-secondary" onclick="imprimirPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick='verDetalhes(<?php echo $pedido["id"]; ?>)'>
                                <i class="fas fa-eye"></i> Ver pedido
                            </button>
                            <button class="btn btn-sm btn-success" onclick="aceitarPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-check"></i> Aceitar
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="enviarNotificacao(<?php echo $pedido['id']; ?>, '<?php echo $pedido['status']; ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info">
                    <h5 class="mb-02">
                        <i class="fas fa-utensils"></i> Em Preparo
                        <span class="badge bg-secondary"><?php echo $total_preparo; ?></span>
                    </h5>
                </div>
                <div class="card-body pedidos-list">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT c.*, m.numero as numero_mesa, m.status as mesa_status 
                        FROM cliente c 
                        LEFT JOIN mesas m ON c.mesa_id = m.id 
                        WHERE c.status = 'Em Preparo' 
                        ORDER BY c.data DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $pedidos_preparo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($pedidos_preparo)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-utensils fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-0">Nenhum pedido em preparo</p>
                        </div>
                    <?php else:
                        foreach($pedidos_preparo as $pedido): ?>
                    <div class="pedido-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php if ($pedido['tipo'] === 'pos'): ?>
                                <span class="badge bg-info" title="Venda realizada no POS">
                                    <i class="fas fa-cash-register"></i> Venda Presencial
                                </span>
                            <?php endif; ?>
                            <?php if ($pedido['mesa_id'] && !is_null($pedido['numero_mesa'])): ?>
                                <span class="badge bg-warning" title="Mesa">
                                    <i class="fas fa-utensils"></i> Mesa <?php echo $pedido['numero_mesa']; ?>
                                </span>
                            <?php else: ?>
                                <?php if ($pedido['nome'] === 'Cliente Balcão'): ?>
                                    <span class="badge bg-secondary" title="Cliente Balcão">
                                       <p> <i class="fas fa-user"></i> Cliente Balcão </p>
                                    </span>
                                <?php else: ?>
                                    <span><?php echo $pedido['nome']; ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><i class="fas fa-phone text-muted"></i> <?php echo $pedido['telefone']; ?></p>
                        <p class="mb-2"><i class="fas fa-money-bill text-muted"></i> R$ <?php 
                            $valor = str_replace(['R$', ' ', '.'], '', $pedido['total']);
                            $valor = str_replace(',', '.', $valor);
                            echo number_format(floatval($valor), 2, ',', '.'); 
                        ?></p>
                        <div class="btn-group w-100">
                            <button class="btn btn-sm btn-secondary" onclick="imprimirPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick='verDetalhes(<?php echo $pedido["id"]; ?>)'>
                                <i class="fas fa-eye"></i> Ver pedido
                            </button>
                            <button class="btn btn-sm btn-success" onclick="marcarPronto(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-check"></i> Pronto
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="enviarNotificacao(<?php echo $pedido['id']; ?>, '<?php echo $pedido['status']; ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success">
                    <h5 class="mb-02">
                        <i class="fas fa-motorcycle"></i> Prontos para Entrega
                        <span class="badge bg-secondary"><?php echo $total_pronto; ?></span>
                    </h5>
                </div>
                <div class="card-body pedidos-list">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT c.*, m.numero as numero_mesa, m.status as mesa_status 
                        FROM cliente c 
                        LEFT JOIN mesas m ON c.mesa_id = m.id 
                        WHERE c.status = 'Pronto para Entrega' 
                        ORDER BY c.data DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $pedidos_prontos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (empty($pedidos_prontos)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-0">Nenhum pedido pronto para entrega</p>
                        </div>
                    <?php else:
                        foreach($pedidos_prontos as $pedido): ?>
                    <div class="pedido-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php if ($pedido['tipo'] === 'pos'): ?>
                                <span class="badge bg-info" title="Venda realizada no POS">
                                    <i class="fas fa-cash-register"></i> POS
                                </span>
                                <span class="badge bg-success" style="cursor: pointer;" onclick="atualizarStatus(<?php echo $pedido['id']; ?>, 'Entregue')" title="Marcar como entregue">
                                    <i class="fas fa-check"></i> Marcar Entregue
                                </span>
                            <?php endif; ?>
                            <?php if ($pedido['mesa_id'] && !is_null($pedido['numero_mesa'])): ?>
                                <span class="badge bg-warning" title="Mesa">
                                    <i class="fas fa-utensils"></i> Mesa <?php echo $pedido['numero_mesa']; ?>
                                </span>
                            <?php else: ?>
                                <?php if ($pedido['nome'] === 'Cliente Balcão'): ?>
                                    <span class="badge bg-secondary" title="Cliente Balcão">
                                        <i class="fas fa-user"></i> Cliente Balcão
                                    </span>
                                <?php else: ?>
                                    <span><?php echo $pedido['nome']; ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="mb-1"><i class="fas fa-phone text-muted"></i> <?php echo $pedido['telefone']; ?></p>
                        <p class="mb-2"><i class="fas fa-money-bill text-muted"></i> R$ <?php 
                            $valor = str_replace(['R$', ' ', '.'], '', $pedido['total']);
                            $valor = str_replace(',', '.', $valor);
                            echo number_format(floatval($valor), 2, ',', '.'); 
                        ?></p>
                        <div class="btn-group w-100">
                            <button class="btn btn-sm btn-secondary" onclick="imprimirPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick='verDetalhes(<?php echo $pedido["id"]; ?>)'>
                                <i class="fas fa-eye"></i> Ver pedido
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="enviarNotificacao(<?php echo $pedido['id']; ?>, '<?php echo $pedido['status']; ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                    <?php 
                        endforeach;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Pedidos -->
    <div class="settings-card">
<!-- Adicionar barra de filtros -->
<div class="filter-container mb-4">
    <form id="filterForm" method="GET" class="d-flex">
        <input type="text" name="cliente" placeholder="Nome do Cliente" class="form-control me-2" value="<?php echo htmlspecialchars($_GET['cliente'] ?? ''); ?>">
        <input type="text" name="telefone" placeholder="Telefone" class="form-control me-2" value="<?php echo htmlspecialchars($_GET['telefone'] ?? ''); ?>">
        <select name="status" class="form-select me-2">
            <option value="">Todos os Status</option>
            <option value="Pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
            <option value="Em Preparo" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Em Preparo') ? 'selected' : ''; ?>>Em Preparo</option>
            <option value="Pronto para Entrega" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pronto para Entrega') ? 'selected' : ''; ?>>Pronto para Entrega</option>
            <option value="Entregue" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Entregue') ? 'selected' : ''; ?>>Entregue</option>
            <option value="Cancelado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
            <option value="Finalizado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Finalizado') ? 'selected' : ''; ?>>Finalizado</option>
        </select>
        <select name="pagamento" class="form-select me-2">
            <option value="">Todas as Formas de Pagamento</option>
            <option value="Cartão" <?php echo (isset($_GET['pagamento']) && $_GET['pagamento'] === 'Cartão') ? 'selected' : ''; ?>>Cartão</option>
            <option value="Dinheiro" <?php echo (isset($_GET['pagamento']) && $_GET['pagamento'] === 'Dinheiro') ? 'selected' : ''; ?>>Dinheiro</option>
            <option value="PIX" <?php echo (isset($_GET['pagamento']) && $_GET['pagamento'] === 'PIX') ? 'selected' : ''; ?>>PIX</option>
        </select>
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary ms-2">Limpar Filtros</a>
    </form>
</div>        
<h4><i class="fas fa-shopping-cart"></i> Gestão de Pedidos</h4>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Telefone</th>
                        <th>Endereço</th>
                        <th>Pedido</th>
                        <th>Itens</th>
                        <th class="text-end">Total</th>
                        <th>Pagamento</th>
                        <th>Status</th>
                        <th class="text-center">Data</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>
                            <p class="mb-0">Nenhum pedido registrado</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td>
                            <?php if ($pedido['tipo'] === 'pos'): ?>
                                <span class="badge bg-info" title="Venda realizada no POS">
                                    <i class="fas fa-cash-register"></i> POS
                                </span>
                            <?php endif; ?>
                            <?php if ($pedido['mesa_id'] && !is_null($pedido['numero_mesa'])): ?>
                                <span class="badge bg-warning" title="Mesa">
                                    <i class="fas fa-utensils"></i> Mesa <?php echo $pedido['numero_mesa']; ?>
                                </span>
                            <?php else: ?>
                                <?php if ($pedido['nome'] === 'Cliente Balcão'): ?>
                                    <span class="badge bg-secondary" title="Cliente Balcão">
                                        <i class="fas fa-user"></i> Cliente Balcão
                                    </span>
                                <?php else: ?>
                                    <i class="fas fa-user text-muted"></i> <?php echo $pedido['nome']; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="fas fa-phone text-muted"></i> <?php echo $pedido['telefone']; ?>
                        </td>
                        <td>
                            <i class="fas fa-money-bill text-muted"></i> R$ <?php 
                                $valor = str_replace(['R$', ' ', '.'], '', $pedido['total']);
                                $valor = str_replace(',', '.', $valor);
                                echo number_format(floatval($valor), 2, ',', '.'); 
                            ?>
                        </td>
                        <td><?php echo $pedido['pedido']; ?></td>
                        <td><?php echo $pedido['itens']; ?></td>
                        <td class="text-end">R$ <?php 
                            $valor = str_replace(['R$', ' ', '.'], '', $pedido['total']);
                            $valor = str_replace(',', '.', $valor);
                            echo number_format(floatval($valor), 2, ',', '.'); 
                        ?></td>
                        <td><?php echo $pedido['pagamento']; ?></td>
                        <td>
                            <select class="form-select form-select-sm status-select" 
                                    onchange="atualizarStatus(<?php echo $pedido['id']; ?>, this.value)">
                                <option value="Pendente" <?php echo $pedido['status'] == 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="Em Preparo" <?php echo $pedido['status'] == 'Em Preparo' ? 'selected' : ''; ?>>Em Preparo</option>
                                <option value="Pronto para Entrega" <?php echo $pedido['status'] == 'Pronto para Entrega' ? 'selected' : ''; ?>>Pronto para Entrega</option>
                                <option value="Entregue" <?php echo $pedido['status'] == 'Entregue' ? 'selected' : ''; ?>>Entregue</option>
                                <option value="Cancelado" <?php echo $pedido['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                <option value="Finalizado" <?php echo $pedido['status'] == 'Finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                            </select>
                        </td>
                        <td class="text-center"><?php 
                            $data_pedido = DateTime::createFromFormat('d/m/Y H:i', $pedido['data']);
                            if ($data_pedido) {
                                echo $data_pedido->format('d/m/Y H:i');
                            } else {
                                echo $pedido['data'];
                            }
                        ?></td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-info" onclick="imprimirPedido(<?php echo $pedido['id']; ?>)">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="editarPedido(<?php echo htmlspecialchars(json_encode($pedido)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="excluirPedido(<?php echo $pedido['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>    
            <!-- Paginação -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Mostrando <?php echo ($offset + 1); ?>-<?php echo min($offset + $itens_por_pagina, $total_pedidos); ?> de <?php echo $total_pedidos; ?> pedidos
                </div>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination mb-0">
                        <?php
                        // Preservar parâmetros de filtro na URL
                        $params = [];
                        if ($clienteFiltro) $params['cliente'] = $clienteFiltro;
                        if ($telefoneFiltro) $params['telefone'] = $telefoneFiltro;
                        if ($statusFiltro) $params['status'] = $statusFiltro;
                        if ($pagamentoFiltro) $params['pagamento'] = $pagamentoFiltro;
                        
                        // Função para gerar URL com parâmetros
                        function gerarURL($pagina, $params) {
                            $url = '?pagina=' . $pagina;
                            foreach ($params as $key => $value) {
                                $url .= '&' . urlencode($key) . '=' . urlencode($value);
                            }
                            return $url;
                        }
                        ?>
                        
                        <?php if ($pagina_atual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo gerarURL($pagina_atual - 1, $params); ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php
                        // Determinar quais números de página mostrar
                        $inicio_paginacao = max(1, $pagina_atual - 2);
                        $fim_paginacao = min($total_paginas, $pagina_atual + 2);

                        // Mostrar primeira página e reticências se necessário
                        if ($inicio_paginacao > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . gerarURL(1, $params) . '">1</a></li>';
                            if ($inicio_paginacao > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        // Mostrar páginas
                        for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++) {
                            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '">';
                            echo '<a class="page-link" href="' . gerarURL($i, $params) . '">' . $i . '</a>';
                            echo '</li>';
                        }

                        // Mostrar última página e reticências se necessário
                        if ($fim_paginacao < $total_paginas) {
                            if ($fim_paginacao < $total_paginas - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="' . gerarURL($total_paginas, $params) . '">' . $total_paginas . '</a></li>';
                        }
                        ?>

                        <?php if ($pagina_atual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo gerarURL($pagina_atual + 1, $params); ?>" aria-label="Próximo">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
    </div>

    <!-- Formulário de Cadastro Manual -->
    <div class="settings-card mb-4">
        <h4><i class="fas fa-plus-circle"></i> Cadastro Manual de Pedido</h4>
        <form id="cadastroPedidoForm" class="mt-3">
            <!-- Seleção de Cliente -->
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Selecionar Cliente Cadastrado</label>
                    <div class="input-group">
                        <select class="form-control" id="clienteSelect">
                            <option value="">Novo cliente ou selecione um cliente...</option>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM clientes_delivery ORDER BY nome");
                            $stmt->execute();
                            $clientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            foreach ($clientes as $cliente) {
                                $nome = htmlspecialchars($cliente['nome']);
                                $telefone = htmlspecialchars($cliente['telefone']);
                                $cep = htmlspecialchars($cliente['cep']);
                                $rua = htmlspecialchars($cliente['rua']);
                                $bairro = htmlspecialchars($cliente['bairro']);
                                $complemento = htmlspecialchars($cliente['complemento'] ?? '');
                                
                                echo "<option value='{$cliente['id']}' 
                                    data-nome='{$nome}' 
                                    data-telefone='{$telefone}'
                                    data-cep='{$cep}'
                                    data-rua='{$rua}'
                                    data-bairro='{$bairro}'
                                    data-complemento='{$complemento}'>
                                    {$nome} - {$telefone}
                                </option>";
                            }
                            ?>
                        </select>
                        <button class="btn btn-outline-secondary" type="button" onclick="limparDadosCliente()">
                            <i class="fas fa-eraser"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nome do Cliente</label>
                    <input type="text" class="form-control" name="nome" id="nomeCliente" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="telefone" id="telefoneCliente" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" class="form-control" name="cep" id="cepCliente" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Rua</label>
                    <input type="text" class="form-control" name="rua" id="ruaCliente" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control" name="bairro" id="bairroCliente" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control" name="complemento" id="complementoCliente">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Itens do Pedido</label>
                    <div class="mb-2">
                        <select class="form-control" id="selectProduto">
                            <option value="">Selecione um produto...</option>
                            <?php
                            // Buscar produtos agrupados por categoria
                            $stmt = $conn->prepare("
                                SELECT p.*, c.item as categoria_nome 
                                FROM produtos_delivery p 
                                LEFT JOIN categorias_delivery c ON p.categoria_id = c.id_categoria 
                                ORDER BY c.item, p.item
                            ");
                            $stmt->execute();
                            $produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            $categoria_atual = '';
                            foreach ($produtos as $produto) {
                                if ($categoria_atual != $produto['categoria_nome']) {
                                    if ($categoria_atual != '') {
                                        echo '</optgroup>';
                                    }
                                    echo '<optgroup label="' . htmlspecialchars($produto['categoria_nome']) . '">';
                                    $categoria_atual = $produto['categoria_nome'];
                                }
                                echo '<option value="' . $produto['id'] . '" data-valor="' . $produto['valor'] . '">' 
                                     . htmlspecialchars($produto['item']) . ' - R$ ' 
                                     . number_format($produto['valor'], 2, ',', '.') . '</option>';
                            }
                            if ($categoria_atual != '') {
                                echo '</optgroup>';
                            }
                            ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary mt-2" onclick="adicionarProduto()">
                            <i class="fas fa-plus"></i> Adicionar Produto
                        </button>
                    </div>
                    <div id="itensSelecionados" class="list-group">
                        <!-- Itens selecionados serão adicionados aqui -->
                    </div>
                    <input type="hidden" name="itens" id="itensInput" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Região de Entrega</label>
                    <select class="form-control" name="zona_entrega" id="zonaEntrega" required>
                        <option value="">Selecione a região...</option>
                        <?php
                        // Buscar regiões do banco
                        $stmt = $conn->prepare("SELECT * FROM taxas_entrega ORDER BY regiao");
                        $stmt->execute();
                        $regioes = $stmt->get_result();
                        while ($regiao = $regioes->fetch_assoc()) {
                            echo "<option value='{$regiao['valor']}' data-regiao='{$regiao['regiao']}'>{$regiao['regiao']} - R$ {$regiao['valor']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Forma de Pagamento</label>
                    <select class="form-control" name="pagamento" required>
                        <option value="">Selecione...</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="PIX">PIX</option>
                        <option value="Cartão">Cartão</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Sub Total</label>
                    <input type="text" class="form-control" name="sub_total" id="subTotalPedido" readonly required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Taxa de Entrega</label>
                    <input type="text" class="form-control" name="taxa_entrega" id="taxaEntrega" readonly required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" name="total" id="totalPedido" readonly required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Cadastrar Pedido
            </button>
        </form>
    </div>
</div>

<style>

.mb-02 {
    color: #ffffff;

}
.pedidos-list {
    max-height: 600px;
    overflow-y: auto;
}

.pedido-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #eee;
}

.pedido-card .badge {
    font-size: 0.85em;
    padding: 6px 10px;
}

.pedido-card p {
    margin-bottom: 0.5rem;
    color: #666;
}

.pedido-card .btn-group {
    margin-top: 10px;
}

.gap-2 {
    gap: 0.5rem !important;
}

.card-header {
    color: white;
}

.pagination .page-link {
    color: #888;
}

.pagination .page-item.active .page-link {
    background-color: #888;
    border-color: #888;
    color: white;
}

.pagination .page-link:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 82, 74, 0.25);
}

/* Novos estilos para a tabela */
.table {
    font-size: 0.9rem;
    margin-bottom: 1rem;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.table thead th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    vertical-align: middle;
    padding: 0.75rem;
}

.table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.02);
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.04);
}

.btn-group {
    gap: 3px;
}

.form-select-sm {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    font-size: 0.85em;
    font-weight: 500;
    white-space: nowrap;
}

.badge i {
    font-size: 0.9em;
}

.badge.bg-info {
    background-color: var(--primary-color) !important;
    color: white;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #000;
}

td .badge {
    margin-right: 5px;
}

td i.text-muted {
    margin-right: 5px;
    opacity: 0.7;
}

.form-group label i {
    margin-right: 5px;
    width: 16px;
    text-align: center;
}

#mesaSelect option i {
    margin-right: 5px;
}

.badge.bg-secondary {
    background-color: #6c757d !important;
    color: white;
}

.badge.bg-secondary i {
    opacity: 0.8;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css" rel="stylesheet" />

<script>
// Funções para manipular os pedidos
function verDetalhes(pedidoId) {
    fetch('ajax/buscar_detalhes_pedido.php?id=' + pedidoId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let detalhesHtml = `
                <div class="text-left">
                    <h2 class="text-center mb-4">Pedido #${data.pedido.pedido}</h2>
                    <p><strong>Cliente:</strong> ${data.pedido.nome}</p>
                    <p><strong>Telefone:</strong> ${data.pedido.telefone}</p>
                    <p><strong>Endereço:</strong> ${data.pedido.rua}, ${data.pedido.bairro}</p>
                    <p><strong>Complemento:</strong> ${data.pedido.complemento || 'Não informado'}</p>
                    <p><strong>Observação:</strong> ${data.pedido.observacao || 'Não informado'}</p>
                    <p><strong>Itens:</strong> ${data.pedido.itens}</p>
                    <p><strong>Taxa de Entrega:</strong> ${data.pedido.taxa_entrega ? `${data.pedido.taxa_entrega}` : 'R$ 0,00'}</p>
                    <p><strong>Sub Total:</strong> ${data.pedido.sub_total}</p>
                    <p><strong>Total:</strong> ${data.pedido.total}</p>
                    <p><strong>Forma de Pagamento:</strong> ${data.pedido.pagamento}</p>
                    <hr>
                    <p><strong>Status:</strong> ${data.pedido.status}</p>
                    <p><strong>Data:</strong> ${data.pedido.data}</p>
                </div>
            `;

            Swal.fire({
                title: '',
                html: detalhesHtml,
                confirmButtonText: 'Fechar',
                confirmButtonColor: '#0d524a',
                width: '600px'
            });
        } else {
            throw new Error(data.message || 'Erro ao buscar detalhes do pedido');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
}

function aceitarPedido(pedidoId) {
    Swal.fire({
        title: 'Aceitar Pedido',
        text: 'Deseja aceitar este pedido e notificar o cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, aceitar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Primeiro atualizar o status do pedido
            fetch('ajax/atualizar_status_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pedido_id=${pedidoId}&status=Em Preparo`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Buscar mensagens disponíveis
                    fetch('ajax/get_messages.php')
                    .then(response => response.json())
                    .then(msgData => {
                        if (msgData.success) {
                            // Buscar detalhes do pedido
                            fetch(`ajax/buscar_detalhes_pedido.php?id=${pedidoId}`)
                            .then(response => response.json())
                            .then(pedidoData => {
                                if (!pedidoData.success) {
                                    throw new Error('Erro ao buscar detalhes do pedido');
                                }

                                const pedido = pedidoData.pedido;

                                // Filtrar apenas mensagens da categoria "Pedido Recebido"
                                let optionsHtml = '<option value="">Selecione uma mensagem...</option>';
                                Object.entries(msgData.messages).forEach(([categoria, mensagens]) => {
                                    if (categoria === 'Pedido Recebido') {
                                        optionsHtml += `<optgroup label="${categoria}">`;
                                        mensagens.forEach(msg => {
                                            optionsHtml += `<option value="${msg.id}" data-template="${msg.template}">${msg.nome_mensagem}</option>`;
                                        });
                                        optionsHtml += '</optgroup>';
                                    }
                                });

                                // Mostrar modal de seleção de mensagem
                                Swal.fire({
                                    title: 'Enviar Notificação <br> <small>👥 Cliente</small>',
                                    html: `
                                        <div class="mb-3">
                                            <label class="form-label">Escolha a mensagem:</label>
                                            <select class="form-select" id="selectMensagem">
                                                ${optionsHtml}
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Preview da mensagem:</label>
                                            <div id="previewMensagem" class="form-text text-muted" 
                                                 style="white-space: pre-line; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                                Selecione uma mensagem para ver o preview
                                            </div>
                                        </div>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Enviar',
                                    cancelButtonText: 'Não enviar',
                                    confirmButtonColor: '#0d524a',
                                    didOpen: () => {
                                        // Adicionar evento para atualizar preview
                                        document.getElementById('selectMensagem').addEventListener('change', function() {
                                            const selectedOption = this.options[this.selectedIndex];
                                            if (selectedOption.value) {
                                                let template = selectedOption.getAttribute('data-template');
                                                
                                                // Substituir todas as variáveis possíveis
                                                template = template
                                                    .replace(/\$nome/g, pedido.nome)
                                                    .replace(/\$pedido/g, pedido.pedido)
                                                    .replace(/\$total/g, pedido.total)
                                                    .replace(/\$status/g, pedido.status)
                                                    .replace(/\$endereco/g, `${pedido.rua}, ${pedido.bairro}`)
                                                    .replace(/\$telefone/g, pedido.telefone)
                                                    .replace(/\$itens/g, pedido.itens);
                                                
                                                document.getElementById('previewMensagem').textContent = template;
                                            } else {
                                                document.getElementById('previewMensagem').textContent = 'Selecione uma mensagem para ver o preview';
                                            }
                                        });
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const mensagemId = document.getElementById('selectMensagem').value;
                                        if (!mensagemId) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erro!',
                                                text: 'Selecione uma mensagem para enviar'
                                            });
                                            return;
                                        }

                                        // Enviar notificação
                                        const formData = new FormData();
                                        formData.append('mensagem_id', mensagemId);
                                        formData.append('pedido_id', pedidoId);
                                        formData.append('pedido_data', JSON.stringify(pedido));

                                        fetch('ajax/send_notification.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Sucesso!',
                                                    text: 'Pedido aceito e cliente notificado',
                                                    timer: 1500,
                                                    showConfirmButton: false
                                                }).then(() => {
                                                    window.location.reload();
                                                });
                                            } else {
                                                throw new Error(data.message || 'Erro ao enviar notificação');
                                            }
                                        })
                                        .catch(error => {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erro!',
                                                text: error.message
                                            });
                                        });
                                    } else {
                                        // Se não quiser enviar mensagem, apenas recarrega a página
                                        window.location.reload();
                                    }
                                });
                            });
                        }
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status do pedido');
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

function marcarPronto(pedidoId) {
    Swal.fire({
        title: 'Pedido Pronto',
        text: 'Deseja marcar este pedido como pronto e notificar o cliente?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, está pronto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Primeiro atualizar o status do pedido
            fetch('ajax/atualizar_status_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pedido_id=${pedidoId}&status=Pronto para Entrega`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Buscar mensagens disponíveis
                    fetch('ajax/get_messages.php')
                    .then(response => response.json())
                    .then(msgData => {
                        if (msgData.success) {
                            // Buscar detalhes do pedido
                            fetch(`ajax/buscar_detalhes_pedido.php?id=${pedidoId}`)
                            .then(response => response.json())
                            .then(pedidoData => {
                                if (!pedidoData.success) {
                                    throw new Error('Erro ao buscar detalhes do pedido');
                                }

                                const pedido = pedidoData.pedido;

                                // Filtrar apenas mensagens da categoria "Status"
                                let optionsHtml = '<option value="">Selecione uma mensagem...</option>';
                                Object.entries(msgData.messages).forEach(([categoria, mensagens]) => {
                                    if (categoria === 'Entrega') {
                                        optionsHtml += `<optgroup label="${categoria}">`;
                                        mensagens.forEach(msg => {
                                            optionsHtml += `<option value="${msg.id}" data-template="${msg.template}">${msg.nome_mensagem}</option>`;
                                        });
                                        optionsHtml += '</optgroup>';
                                    }
                                });

                                // Mostrar modal de seleção de mensagem
                                Swal.fire({
                                    title: 'Enviar Notificação <br> <small>👥 Cliente</small>',
                                    html: `
                                        <div class="mb-3">
                                            <label class="form-label">Escolha a mensagem:</label>
                                            <select class="form-select" id="selectMensagem">
                                                ${optionsHtml}
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Preview da mensagem:</label>
                                            <div id="previewMensagem" class="form-text text-muted" 
                                                 style="white-space: pre-line; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                                Selecione uma mensagem para ver o preview
                                            </div>
                                        </div>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Enviar',
                                    cancelButtonText: 'Cancelar',
                                    confirmButtonColor: '#0d524a',
                                    didOpen: () => {
                                        // Adicionar evento para atualizar preview
                                        document.getElementById('selectMensagem').addEventListener('change', function() {
                                            const selectedOption = this.options[this.selectedIndex];
                                            if (selectedOption.value) {
                                                let template = selectedOption.getAttribute('data-template');
                                                
                                                // Substituir todas as variáveis possíveis
                                                template = template
                                                    .replace(/\$nome/g, pedido.nome)
                                                    .replace(/\$pedido/g, pedido.pedido)
                                                    .replace(/\$total/g, pedido.total)
                                                    .replace(/\$status/g, 'Pronto para Entrega')
                                                    .replace(/\$endereco/g, `${pedido.rua}, ${pedido.bairro}`)
                                                    .replace(/\$telefone/g, pedido.telefone)
                                                    .replace(/\$itens/g, pedido.itens);
                                                
                                                document.getElementById('previewMensagem').textContent = template;
                                            } else {
                                                document.getElementById('previewMensagem').textContent = 'Selecione uma mensagem para ver o preview';
                                            }
                                        });
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const mensagemId = document.getElementById('selectMensagem').value;
                                        if (!mensagemId) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erro!',
                                                text: 'Selecione uma mensagem para enviar'
                                            });
                                            return;
                                        }

                                        // Enviar notificação
                                        const formData = new FormData();
                                        formData.append('mensagem_id', mensagemId);
                                        formData.append('pedido_id', pedidoId);
                                        formData.append('pedido_data', JSON.stringify(pedido));

                                        fetch('ajax/send_notification.php', {
                                            method: 'POST',
                                            body: formData
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Sucesso!',
                                                    text: 'Pedido atualizado e cliente notificado',
                                                    timer: 1500,
                                                    showConfirmButton: false
                                                }).then(() => {
                                                    window.location.reload();
                                                });
                                            } else {
                                                throw new Error(data.message || 'Erro ao enviar notificação');
                                            }
                                        })
                                        .catch(error => {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Erro!',
                                                text: error.message
                                            });
                                        });
                                    } else {
                                        // Se não quiser enviar mensagem, apenas recarrega a página
                                        window.location.reload();
                                    }
                                });
                            });
                        }
                    });
                } else {
                    throw new Error(data.message || 'Erro ao atualizar status do pedido');
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

function marcarEntregue(id) {
    Swal.fire({
        title: 'Confirmar entrega',
        text: 'Deseja marcar este pedido como entregue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Sim, entregar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            atualizarStatus(id, 'Entregue');
        }
    });
}

function cancelarPedido(id) {
    Swal.fire({
        title: 'Cancelar Pedido',
        text: 'Tem certeza que deseja cancelar este pedido?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não'
    }).then((result) => {
        if (result.isConfirmed) {
            atualizarStatus(id, 'Cancelado');
        }
    });
}

function atualizarStatus(id, status) {
    // Mostrar loading
    Swal.fire({
        title: 'Atualizando...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('ajax/atualizar_status_pedido.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `pedido_id=${id}&status=${encodeURIComponent(status)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição');
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Resposta do servidor:', text);
                throw new Error('Resposta inválida do servidor');
            }
        });
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Status atualizado!',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Erro ao atualizar status');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message,
            confirmButtonColor: '#0d524a'
        });
    });
}

function imprimirPedido(id) {
    window.open(`imprimir_pedido.php?id=${id}`, '_blank', 'width=400,height=600');
}

function editarPedido(pedido) {
    Swal.fire({
        title: 'Editar Pedido',
        html: `
            <form id="pedidoForm">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" value="${pedido.nome}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" value="${pedido.telefone}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" value="${pedido.cep}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Rua</label>
                    <input type="text" class="form-control" id="rua" value="${pedido.rua}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" value="${pedido.bairro}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" value="${pedido.complemento || ''}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" id="total" value="${pedido.total}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Forma de Pagamento</label>
                    <input type="text" class="form-control" id="pagamento" value="${pedido.pagamento}" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const formData = {
                id: pedido.id,
                nome: document.getElementById('nome').value,
                telefone: document.getElementById('telefone').value,
                cep: document.getElementById('cep').value,
                rua: document.getElementById('rua').value,
                bairro: document.getElementById('bairro').value,
                complemento: document.getElementById('complemento').value,
                total: document.getElementById('total').value,
                pagamento: document.getElementById('pagamento').value
            };
 
            return fetch('ajax/atualizar_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao atualizar pedido');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.reload();
        }
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
}

function excluirPedido(id) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        text: 'Tem certeza que deseja excluir este pedido?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/excluir_pedido.php', {
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

document.getElementById('cadastroPedidoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Converter o valor para formato correto antes de enviar
    const totalStr = formData.get('total');
    const totalLimpo = totalStr.replace('R$', '').trim().replace('.', '').replace(',', '.');
    formData.set('total', `R$ ${parseFloat(totalLimpo).toFixed(2).replace('.', ',')}`);
    
    fetch('ajax/cadastrar_pedido.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na requisição');
        }
        return response.json();
    })
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
            throw new Error(data.message || 'Erro ao cadastrar pedido');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
});

let itensPedido = [];
let totalPedido = 0;

function adicionarProduto() {
    const select = document.getElementById('selectProduto');
    const option = select.options[select.selectedIndex];
    
    if (select.value) {
        const produto = {
            id: select.value,
            nome: option.text,
            valor: parseFloat(option.getAttribute('data-valor')),
            quantidade: 1
        };

        // Adicionar à lista de itens
        const itemHtml = `
            <div class="list-group-item d-flex justify-content-between align-items-center" data-id="${produto.id}">
                <div>
                    ${produto.nome} 
                    <input type="number" class="form-control form-control-sm d-inline-block ml-2" 
                           style="width: 60px;" value="1" min="1" onchange="atualizarQuantidade(this)">
                </div>
                <button class="btn btn-sm btn-danger" onclick="removerItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        document.getElementById('itensSelecionados').insertAdjacentHTML('beforeend', itemHtml);
        select.value = '';

        // Atualizar subtotal e total
        atualizarTotais();
    }
}

function removerProduto(index) {
    itensPedido.splice(index, 1);
    atualizarListaProdutos();
    atualizarTotal();
}

function atualizarListaProdutos() {
    const container = document.getElementById('itensSelecionados');
    const itensInput = document.getElementById('itensInput');
    
    container.innerHTML = '';
    
    itensPedido.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = 'list-group-item d-flex justify-content-between align-items-center';
        itemElement.innerHTML = `
            ${item.nome} - R$ ${parseFloat(item.valor).toFixed(2)}
            <button type="button" class="btn btn-sm btn-danger" onclick="removerProduto(${index})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(itemElement);
    });

    // Atualizar o input hidden com os itens formatados
    const itensFormatados = itensPedido.map(item => item.nome).join(', ');
    itensInput.value = itensFormatados;
}

function atualizarTotal() {
    totalPedido = itensPedido.reduce((total, item) => total + parseFloat(item.valor), 0);
    document.getElementById('totalPedido').value = totalPedido.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

$(document).ready(function() {
    // Evento de mudança do select de clientes
    $('#clienteSelect').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            // Preencher campos usando getAttribute
            document.getElementById('nomeCliente').value = selectedOption[0].getAttribute('data-nome');
            document.getElementById('telefoneCliente').value = selectedOption[0].getAttribute('data-telefone');
            document.getElementById('cepCliente').value = selectedOption[0].getAttribute('data-cep');
            document.getElementById('ruaCliente').value = selectedOption[0].getAttribute('data-rua');
            document.getElementById('bairroCliente').value = selectedOption[0].getAttribute('data-bairro');
            document.getElementById('complementoCliente').value = selectedOption[0].getAttribute('data-complemento');
        }
    });
});

function limparDadosCliente() {
    document.getElementById('clienteSelect').value = '';
    document.getElementById('clienteSelect').dispatchEvent(new Event('change'));
    document.getElementById('nomeCliente').value = '';
    document.getElementById('telefoneCliente').value = '';
    document.getElementById('cepCliente').value = '';
    document.getElementById('ruaCliente').value = '';
    document.getElementById('bairroCliente').value = '';
    document.getElementById('complementoCliente').value = '';
}

function enviarNotificacao(pedidoId, status) {
    fetch('ajax/get_messages.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao buscar mensagens');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Buscar detalhes do pedido primeiro
            fetch(`ajax/buscar_detalhes_pedido.php?id=${pedidoId}`)
            .then(response => response.json())
            .then(pedidoData => {
                if (!pedidoData.success) {
                    throw new Error('Erro ao buscar detalhes do pedido');
                }

                const pedido = pedidoData.pedido;

                // Criar opções do select agrupadas por categoria
                let optionsHtml = '<option value="">Selecione uma mensagem...</option>';
                Object.entries(data.messages).forEach(([categoria, mensagens]) => {
                    optionsHtml += `<optgroup label="${categoria}">`;
                    mensagens.forEach(msg => {
                        optionsHtml += `<option value="${msg.id}" data-template="${msg.template}">${msg.nome_mensagem}</option>`;
                    });
                    optionsHtml += '</optgroup>';
                });

                // Mostrar modal de seleção
                Swal.fire({
                    title: 'Enviar Notificação <br> <small>👥 Cliente</small>',
                    html: `
                        <div class="mb-3">
                            <label class="form-label">Escolha a mensagem:</label>
                            <select class="form-select" id="selectMensagem">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Preview da mensagem:</label>
                            <div id="previewMensagem" class="form-text text-muted" 
                                 style="white-space: pre-line; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                Selecione uma mensagem para ver o preview
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Enviar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d524a',
                    didOpen: () => {
                        // Adicionar evento para atualizar preview
                        document.getElementById('selectMensagem').addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            if (selectedOption.value) {
                                let template = selectedOption.getAttribute('data-template');
                                
                                // Substituir todas as variáveis possíveis
                                template = template
                                    .replace(/\$nome/g, pedido.nome)
                                    .replace(/\$pedido/g, pedido.pedido)
                                    .replace(/\$total/g, pedido.total)
                                    .replace(/\$status/g, pedido.status)
                                    .replace(/\$endereco/g, `${pedido.rua}, ${pedido.bairro}`)
                                    .replace(/\$telefone/g, pedido.telefone)
                                    .replace(/\$itens/g, pedido.itens);
                                                
                                document.getElementById('previewMensagem').textContent = template;
                            } else {
                                document.getElementById('previewMensagem').textContent = 'Selecione uma mensagem para ver o preview';
                            }
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const mensagemId = document.getElementById('selectMensagem').value;
                        if (!mensagemId) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Selecione uma mensagem para enviar'
                            });
                            return;
                        }

                        // Enviar notificação com os dados do pedido
                        const formData = new FormData();
                        formData.append('mensagem_id', mensagemId);
                        formData.append('pedido_id', pedidoId);
                        formData.append('pedido_data', JSON.stringify(pedido));

                        fetch('ajax/send_notification.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Notificação enviada com sucesso',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                throw new Error(data.message || 'Erro ao enviar notificação');
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
            });
        } else {
            throw new Error(data.message || 'Erro ao carregar mensagens');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Erro ao carregar mensagens disponíveis'
        });
    });
}

function calcularTotal() {
    const subTotal = parseFloat(document.getElementById('subTotalPedido').value.replace('R$ ', '').replace(',', '.')) || 0;
    const taxaEntrega = parseFloat(document.getElementById('taxaEntrega').value.replace('R$ ', '').replace(',', '.')) || 0;
    
    const total = subTotal + taxaEntrega;
    
    document.getElementById('totalPedido').value = `R$ ${total.toFixed(2).replace('.', ',')}`;
}

// Atualizar o evento de adicionar item para incluir o cálculo do subtotal
function adicionarItem() {
    const produto = document.getElementById('produto');
    const quantidade = document.getElementById('quantidade').value;
    const valor = parseFloat(produto.options[produto.selectedIndex].getAttribute('data-valor'));
    
    if (produto.value && quantidade > 0) {
        const item = {
            nome: produto.options[produto.selectedIndex].text,
            quantidade: quantidade,
            valor: valor,
            total: valor * quantidade
        };
        
        itensPedido.push(item);
        atualizarListaItens();
        
        // Calcular subtotal
        const subTotal = itensPedido.reduce((acc, item) => acc + item.total, 0);
        document.getElementById('subTotalPedido').value = `R$ ${subTotal.toFixed(2).replace('.', ',')}`;
        
        // Limpar campos
        produto.value = '';
        document.getElementById('quantidade').value = '1';
        
        calcularTotal();
    }
}

// Adicionar evento para quando mudar a região de entrega
document.getElementById('zonaEntrega').addEventListener('change', function() {
    const taxaEntrega = this.value ? parseFloat(this.value) : 0;
    document.getElementById('taxaEntrega').value = `R$ ${taxaEntrega.toFixed(2).replace('.', ',')}`;
    calcularTotal();
});

function atualizarQuantidade(input) {
    atualizarTotais();
}

function removerItem(button) {
    button.closest('.list-group-item').remove();
    atualizarTotais();
}

function atualizarTotais() {
    let subtotal = 0;
    
    // Calcular subtotal baseado nos itens selecionados
    document.querySelectorAll('#itensSelecionados .list-group-item').forEach(item => {
        const produtoText = item.querySelector('div').childNodes[0].textContent.trim();
        const valor = parseFloat(produtoText.split('R$')[1].split('-')[0].trim().replace(',', '.'));
        const quantidade = parseInt(item.querySelector('input[type="number"]').value);
        subtotal += valor * quantidade;
    });

    // Atualizar campo subtotal
    document.getElementById('subTotalPedido').value = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;

    // Pegar taxa de entrega
    const taxaEntrega = parseFloat(document.getElementById('taxaEntrega').value?.replace('R$ ', '').replace(',', '.')) || 0;

    // Calcular total
    const total = subtotal + taxaEntrega;
    document.getElementById('totalPedido').value = `R$ ${total.toFixed(2).replace('.', ',')}`;

    // Atualizar lista de itens para envio - CORREÇÃO AQUI
    const itensParaEnvio = [];
    document.querySelectorAll('#itensSelecionados .list-group-item').forEach(item => {
        const produtoText = item.querySelector('div').childNodes[0].textContent.trim();
        const quantidade = parseInt(item.querySelector('input[type="number"]').value);
        // Extrair apenas o nome do produto (antes do "- R$")
        const nomeProduto = produtoText.split(' - R$')[0].trim();
        itensParaEnvio.push(`${nomeProduto} (${quantidade}x)`);
    });
    document.getElementById('itensInput').value = itensParaEnvio.join(', ');
}

// Atualizar o evento de mudança da região de entrega
document.getElementById('zonaEntrega').addEventListener('change', function() {
    const taxaEntrega = this.value ? parseFloat(this.value) : 0;
    document.getElementById('taxaEntrega').value = `R$ ${taxaEntrega.toFixed(2).replace('.', ',')}`;
    atualizarTotais();
});
</script>

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>