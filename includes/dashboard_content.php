<?php
// File Hash: 1d7dec9aecc95c7df11f3c94ed7f538b





$config = json_decode(file_get_contents('customizacao.json'), true);

// Buscar estatísticas do banco de dados
$user_id = $_SESSION['user_id'];

// Total de vendas hoje
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        COALESCE(SUM(CASE 
            WHEN total REGEXP '^[0-9]+(\.[0-9]+)?$' THEN CAST(total AS DECIMAL(10,2))
            ELSE CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))
        END), 0) as total_vendas
    FROM cliente 
    WHERE DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) = CURDATE()
    AND status != 'Cancelado'
");
$stmt->execute();
$vendas_hoje = $stmt->get_result()->fetch_assoc();

// Total de vendas do mês
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        COALESCE(SUM(CASE 
            WHEN total REGEXP '^[0-9]+(\.[0-9]+)?$' THEN CAST(total AS DECIMAL(10,2))
            ELSE CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))
        END), 0) as total_vendas
    FROM cliente 
    WHERE MONTH(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) = MONTH(CURRENT_DATE())
    AND YEAR(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) = YEAR(CURRENT_DATE())
    AND status != 'Cancelado'
");
$stmt->execute();
$vendas_mes = $stmt->get_result()->fetch_assoc();

// Pedidos pendentes
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM cliente 
    WHERE status = 'Pendente'
");
$stmt->execute();
$pedidos_pendentes = $stmt->get_result()->fetch_assoc();

// Pedidos em rota
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM entregas 
    WHERE status = 'Em Rota'
");
$stmt->execute();
$pedidos_rota = $stmt->get_result()->fetch_assoc();

// Itens mais vendidos
$stmt = $conn->prepare("
    SELECT itens, COUNT(*) as quantidade
    FROM cliente
    WHERE status != 'Cancelado'
    GROUP BY itens
    ORDER BY quantidade DESC
    LIMIT 5
");
$stmt->execute();
$itens_populares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Vendas recentes
$stmt = $conn->prepare("
    SELECT id, pedido, nome, total, status, data
    FROM cliente
    ORDER BY data DESC
    LIMIT 5
");
$stmt->execute();
$vendas_recentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<div class="dashboard-container">
    <!-- Boas-vindas -->
    <div class="welcome-section text-center mb-4">
        <?php echo $config['dashboard_info_text']; ?>
    </div>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <!-- Vendas Hoje -->
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mt-2">Vendas Hoje</h6>
                            <h3 class="mt-2 mb-0">R$ <?php echo number_format($vendas_hoje['total_vendas'] ?? 0, 2, ',', '.'); ?></h3>
                            <small><?php echo $vendas_hoje['total_pedidos']; ?> pedidos</small>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendas do Mês -->
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Vendas do Mês</h6>
                            <h3 class="mt-2 mb-0">R$ <?php echo number_format($vendas_mes['total_vendas'] ?? 0, 2, ',', '.'); ?></h3>
                            <small><?php echo $vendas_mes['total_pedidos']; ?> pedidos</small>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pedidos Pendentes -->
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Pedidos Pendentes</h6>
                            <h3 class="mt-2 mb-0"><?php echo $pedidos_pendentes['total']; ?></h3>
                            <small>Aguardando preparo</small>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Em Entrega -->
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Em Rota</h6>
                            <h3 class="mt-2 mb-0"><?php echo $pedidos_rota['total']; ?></h3>
                            <small>Pedidos em rota</small>
                        </div>
                        <i class="fas fa-motorcycle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Tabelas -->
    <div class="row">
        <!-- Itens Mais Vendidos -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title2 mb-0">
                        <i class="fas fa-star text-warning"></i> Itens Mais Vendidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($itens_populares)): ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4">
                                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                        <p class="mb-0 mt-2">Nenhum item vendido ainda</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($itens_populares as $item): ?>
                                <tr>
                                    <td><?php echo $item['itens']; ?></td>
                                    <td class="text-end"><?php echo $item['quantidade']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendas Recentes -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title2 mb-0">
                        <i class="fas fa-history text-primary"></i> Vendas Recentes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vendas_recentes)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <p class="mb-0 mt-2">Nenhuma venda registrada ainda</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($vendas_recentes as $venda): ?>
                                <tr>
                                    <td>#<?php echo str_pad($venda['pedido'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo $venda['nome']; ?></td>
                                    <td><?php echo $venda['total']; ?></td>
                                    <td>
                                    <span class="badge bg-<?php 
                                            switch($venda['status']) {
                                                case 'Pendente': echo 'warning'; break;
                                                case 'Em Preparo': echo 'info'; break;
                                                case 'Pronto para Entrega': echo 'primary'; break;
                                                case 'Em Entrega': echo 'info'; break;
                                                case 'Entregue': echo 'success'; break;
                                                case 'Cancelado': echo 'danger'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo $venda['status']; ?>
                                        </span>
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

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>

<style>
.dashboard-container {
    padding: 20px;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
    padding: 1rem;
}

.card-title {
    color: #fff;
    font-weight: 600;
}

.card-title2 {
    color: #000;
    font-weight: 600;
}

.table {
    margin-bottom: 0;
}

.badge {
    padding: 0.5em 0.8em;
}

.opacity-50 {
    opacity: 0.5;
}

.welcome-section {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    padding: 2rem;
    border-radius: 10px;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.welcome-section h1 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.welcome-section p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 0;
}
</style> 