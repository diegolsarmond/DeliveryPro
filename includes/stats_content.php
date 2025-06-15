<?php
// File Hash: 4198f73f7f51d522d477f38525b232ce

// Buscar estatísticas do banco de dados
$user_id = $_SESSION['user_id'];

// Vendas por dia nos últimos 7 dias
$stmt = $conn->prepare("
    SELECT 
        DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) as dia,
        COUNT(*) as total_pedidos,
        COALESCE(SUM(CASE 
            WHEN total REGEXP '^[0-9]+(\.[0-9]+)?$' THEN CAST(total AS DECIMAL(10,2))
            ELSE CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))
        END), 0) as total_vendas
    FROM cliente 
    WHERE DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    AND status != 'Cancelado'
    GROUP BY DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i'))
    ORDER BY dia ASC
");

// Buscar dados para 7 dias
$dias_7 = 7;
$stmt->bind_param('i', $dias_7);
$stmt->execute();
$vendas_7_dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar dados para 30 dias
$dias_30 = 30;
$stmt->bind_param('i', $dias_30);
$stmt->execute();
$vendas_30_dias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Produtos mais vendidos
$stmt = $conn->prepare("
    SELECT 
        itens,
        COUNT(*) as quantidade,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as total_vendas
    FROM cliente
    WHERE status != 'Cancelado'
    GROUP BY itens
    ORDER BY quantidade DESC
    LIMIT 5
");
$stmt->execute();
$produtos_populares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Status dos pedidos
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as quantidade
    FROM cliente
    GROUP BY status
");
$stmt->execute();
$status_pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Estatísticas de formas de pagamento
$stmt = $conn->prepare("
    SELECT 
        pagamento,
        COUNT(*) as quantidade,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as total_valor
    FROM cliente
    WHERE status != 'Cancelado'
    GROUP BY pagamento
    ORDER BY quantidade DESC
");
$stmt->execute();
$pagamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Bairros mais atendidos
$stmt = $conn->prepare("
    SELECT 
        bairro,
        COUNT(*) as quantidade,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as total_valor
    FROM cliente
    WHERE status != 'Cancelado'
    GROUP BY bairro
    ORDER BY quantidade DESC
    LIMIT 10
");
$stmt->execute();
$bairros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calendário de Ganhos
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(data, '%d/%m/%Y %H:%i'), '%Y-%m') as mes,
        COUNT(*) as total_pedidos,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as total_vendas,
        SUM(CAST(REPLACE(REPLACE(REPLACE(taxa_entrega, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as total_taxas
    FROM cliente 
    WHERE status != 'Cancelado'
    AND STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(STR_TO_DATE(data, '%d/%m/%Y %H:%i'), '%Y-%m')
    ORDER BY mes DESC
");
$stmt->execute();
$ganhos_mensais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Análise de Lucratividade
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(data, '%d/%m/%Y %H:%i'), '%Y-%m') as mes,
        COUNT(*) as total_pedidos,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as receita_bruta,
        SUM(CAST(REPLACE(REPLACE(REPLACE(taxa_entrega, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as custos_entrega,
        (SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) - 
         SUM(CAST(REPLACE(REPLACE(REPLACE(taxa_entrega, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2)))) as receita_liquida,
        ROUND((SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) - 
               SUM(CAST(REPLACE(REPLACE(REPLACE(taxa_entrega, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2)))) / 
              SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) * 100, 2) as margem_lucro
    FROM cliente 
    WHERE status != 'Cancelado'
    AND STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY mes
    ORDER BY mes DESC
");
$stmt->execute();
$analise_lucratividade = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Análise de Ticket Médio
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(data, '%d/%m/%Y %H:%i'), '%Y-%m') as mes,
        ROUND(AVG(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))), 2) as ticket_medio,
        MIN(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as menor_pedido,
        MAX(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as maior_pedido
    FROM cliente 
    WHERE status != 'Cancelado'
    AND STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY mes
    ORDER BY mes DESC
");
$stmt->execute();
$analise_ticket = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Análise de Horários dos Pedidos
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(STR_TO_DATE(data, '%d/%m/%Y %H:%i'), '%H:%i') as hora_pedido,
        COUNT(*) as total_pedidos,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as valor_total
    FROM cliente 
    WHERE STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status != 'Cancelado'
    GROUP BY hora_pedido
    ORDER BY hora_pedido ASC
");
$stmt->execute();
$horarios_pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// KPIs Financeiros
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_pedidos,
        ROUND(AVG(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))), 2) as ticket_medio_geral,
        SUM(CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as receita_total,
        SUM(CAST(REPLACE(REPLACE(REPLACE(taxa_entrega, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))) as custos_entrega_total,
        COUNT(DISTINCT bairro) as total_bairros_atendidos,
        COUNT(DISTINCT pagamento) as formas_pagamento_utilizadas
    FROM cliente 
    WHERE status != 'Cancelado'
    AND STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->execute();
$kpis_financeiros = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
?>

<div class="stats-container">
    <?php
    // Função para buscar vendas por período
    function buscarVendasPorPeriodo($conn, $dias) {
        $stmt = $conn->prepare("
            SELECT 
                DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) as dia,
                COUNT(*) as total_pedidos,
                COALESCE(SUM(CASE 
                    WHEN total REGEXP '^[0-9]+(\.[0-9]+)?$' THEN CAST(total AS DECIMAL(10,2))
                    ELSE CAST(REPLACE(REPLACE(REPLACE(total, 'R$', ''), '.', ''), ',', '.') AS DECIMAL(10,2))
                END), 0) as total_vendas
            FROM cliente 
            WHERE DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i')) >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND status != 'Cancelado'
            GROUP BY DATE(STR_TO_DATE(data, '%d/%m/%Y %H:%i'))
            ORDER BY dia ASC
        ");
        $stmt->bind_param('i', $dias);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Buscar dados iniciais (7 dias)
    $vendas_7_dias = buscarVendasPorPeriodo($conn, 7);
    $vendas_30_dias = buscarVendasPorPeriodo($conn, 30);
    ?>

    <!-- Botões de Exportação -->
    <div class="mb-4">
        <button class="btn btn-success" onclick="exportarRelatorio('excel')">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </button>
        <button class="btn btn-danger" onclick="exportarRelatorio('pdf')">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </button>
    </div>

    <div class="row">
        <!-- Gráfico de Vendas -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title2 mb-0">Total de Vendas</h5>
                    <select id="periodoVendas" class="form-select" style="width: auto;">
                        <option value="7">Últimos 7 Dias</option>
                        <option value="30">Últimos 30 Dias</option>
                    </select>
                </div>
                <div class="card-body">
                    <div id="graficoContainer">
                    <canvas id="vendasChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Status -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Status dos Pedidos</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Produtos Mais Vendidos -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Produtos Mais Vendidos</h5>
                </div>
                <div class="card-body">
                    <canvas id="produtosChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabela de Produtos -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Detalhamento de Produtos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Total Vendas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($produtos_populares)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <i class="fas fa-box fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhum produto vendido ainda</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($produtos_populares as $produto): ?>
                                <tr>
                                    <td><?php echo $produto['itens']; ?></td>
                                    <td><?php echo $produto['quantidade']; ?></td>
                                    <td>R$ <?php echo number_format($produto['total_vendas'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formas de Pagamento -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Formas de Pagamento</h5>
                </div>
                <div class="card-body">
                    <canvas id="pagamentosChart"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Forma de Pagamento</th>
                                    <th>Quantidade</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pagamentos)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <i class="fas fa-credit-card fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhuma forma de pagamento registrada</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($pagamentos as $pagamento): ?>
                                <tr>
                                    <td><?php echo $pagamento['pagamento']; ?></td>
                                    <td><?php echo $pagamento['quantidade']; ?></td>
                                    <td>R$ <?php echo number_format($pagamento['total_valor'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bairros Mais Atendidos -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Bairros Mais Atendidos</h5>
                </div>
                <div class="card-body">
                    <canvas id="bairrosChart"></canvas>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bairro</th>
                                    <th>Pedidos</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bairros)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhum bairro atendido ainda</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($bairros as $bairro): ?>
                                <tr>
                                    <td><?php echo $bairro['bairro']; ?></td>
                                    <td><?php echo $bairro['quantidade']; ?></td>
                                    <td>R$ <?php echo number_format($bairro['total_valor'], 2, ',', '.'); ?></td>
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

<!-- Análise de Horários dos Pedidos -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title2 mb-0">Distribuição de Pedidos por Horário</h5>
            </div>
            <div class="card-body">
                <canvas id="horariosChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Nova seção de KPIs Financeiros -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title2 mb-0">KPIs Financeiros (Últimos 30 dias)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Receita Total</h6>
                            <h3>R$ <?php echo number_format($kpis_financeiros['receita_total'], 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Ticket Médio</h6>
                            <h3>R$ <?php echo number_format($kpis_financeiros['ticket_medio_geral'], 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="kpi-card">
                            <h6>Custos de Entrega</h6>
                            <h3>R$ <?php echo number_format($kpis_financeiros['custos_entrega_total'], 2, ',', '.'); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Análise de Lucratividade -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title2 mb-0">Análise de Lucratividade Mensal</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Receita Bruta</th>
                                <th>Custos Entrega</th>
                                <th>Receita Líquida</th>
                                <th>Margem de Lucro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($analise_lucratividade)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-chart-line fa-3x text-muted mb-3 d-block"></i>
                                    <p class="mb-0">Nenhum dado de lucratividade disponível</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($analise_lucratividade as $mes): ?>
                            <tr>
                                <td><?php echo date('M/Y', strtotime($mes['mes'])); ?></td>
                                <td>R$ <?php echo number_format($mes['receita_bruta'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($mes['custos_entrega'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($mes['receita_liquida'], 2, ',', '.'); ?></td>
                                <td><?php echo $mes['margem_lucro']; ?>%</td>
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

<!-- Calendário de Ganhos -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title2 mb-0">Calendário de Ganhos</h5>
            </div>
            <div class="card-body">
                <div class="calendar-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead style="background-color: <?php echo $config['primary_color']; ?>; color: white;">
                                <tr>
                                    <th>Mês</th>
                                    <th>Pedidos</th>
                                    <th>Vendas</th>
                                    <th>Taxas</th>
                                    <th>Total</th>
                                    <th>Média por Pedido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ganhos_mensais)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-calendar fa-3x text-muted mb-3 d-block"></i>
                                        <p class="mb-0">Nenhum ganho registrado ainda</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($ganhos_mensais as $ganho): 
                                    $total = $ganho['total_vendas'] + $ganho['total_taxas'];
                                    $media = $ganho['total_pedidos'] > 0 ? $total / $ganho['total_pedidos'] : 0;
                                    $mes = date('F Y', strtotime($ganho['mes'] . '-01'));
                                    // Traduzir mês para português
                                    $mes = str_replace(
                                        ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                                        ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                                        $mes
                                    );
                                ?>
                                <tr>
                                    <td><strong><?php echo $mes; ?></strong></td>
                                    <td><?php echo $ganho['total_pedidos']; ?></td>
                                    <td>R$ <?php echo number_format($ganho['total_vendas'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($ganho['total_taxas'], 2, ',', '.'); ?></td>
                                    <td class="text-success"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                                    <td>R$ <?php echo number_format($media, 2, ',', '.'); ?></td>
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

<!-- Adicionar bibliotecas necessárias para exportação -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let vendasChart; // Variável global para armazenar a instância do gráfico

    // Função para criar/atualizar o gráfico de vendas
    function atualizarGraficoVendas(periodo) {
        const dados = periodo === '7' ? {
            labels: <?php echo json_encode(array_map(function($venda) {
                return date('d/m/Y', strtotime($venda['dia']));
            }, $vendas_7_dias)); ?>,
            valores: <?php echo json_encode(array_column($vendas_7_dias, 'total_vendas')); ?>
        } : {
            labels: <?php echo json_encode(array_map(function($venda) {
                return date('d/m/Y', strtotime($venda['dia']));
            }, $vendas_30_dias)); ?>,
            valores: <?php echo json_encode(array_column($vendas_30_dias, 'total_vendas')); ?>
        };

        // Se já existe um gráfico, destrua-o
        if (vendasChart) {
            vendasChart.destroy();
        }

        // Criar novo gráfico
        const ctx = document.getElementById('vendasChart').getContext('2d');
        vendasChart = new Chart(ctx, {
            type: 'bar', // Mudado de 'line' para 'bar'
            data: {
                labels: dados.labels,
                datasets: [{
                    label: 'Total de Vendas (R$)',
                    data: dados.valores,
                    backgroundColor: '<?php echo $config['primary_color']; ?>80', // Adicionado transparência
                    borderColor: '<?php echo $config['primary_color']; ?>',
                    borderWidth: 1,
                    borderRadius: 4 // Adicionado bordas arredondadas
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: `Vendas dos Últimos ${periodo} Dias`
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false // Remove as linhas de grade verticais
                        }
                    }
                }
            }
        });
    }

    // Adicionar evento de mudança ao select
    document.getElementById('periodoVendas').addEventListener('change', function() {
        atualizarGraficoVendas(this.value);
    });

    // Inicializar gráfico com 7 dias
    atualizarGraficoVendas('7');

    // Gráfico de Status
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($status_pedidos, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_pedidos, 'quantidade')); ?>,
                backgroundColor: [
                    '#28a745',
                    '#dc3545',
                    '#ffc107',
                    '#17a2b8',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Produtos
    const ctxProdutos = document.getElementById('produtosChart').getContext('2d');
    new Chart(ctxProdutos, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($produtos_populares, 'itens')); ?>,
            datasets: [{
                label: 'Quantidade Vendida',
                data: <?php echo json_encode(array_column($produtos_populares, 'quantidade')); ?>,
                backgroundColor: '<?php echo $config['primary_color']; ?>'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico de Pagamentos
    const ctxPagamentos = document.getElementById('pagamentosChart').getContext('2d');
    new Chart(ctxPagamentos, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($pagamentos, 'pagamento')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($pagamentos, 'quantidade')); ?>,
                backgroundColor: [
                    '#28a745',
                    '#007bff',
                    '#ffc107',
                    '#dc3545',
                    '#6610f2'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Bairros
    const ctxBairros = document.getElementById('bairrosChart').getContext('2d');
    new Chart(ctxBairros, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($bairros, 'bairro')); ?>,
            datasets: [{
                label: 'Quantidade de Pedidos',
                data: <?php echo json_encode(array_column($bairros, 'quantidade')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)',
                    'rgba(83, 102, 255, 0.7)',
                    'rgba(40, 159, 64, 0.7)',
                    'rgba(210, 199, 199, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico de Horários
    const ctxHorarios = document.getElementById('horariosChart').getContext('2d');
    new Chart(ctxHorarios, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($horarios_pedidos, 'hora_pedido')); ?>,
            datasets: [{
                label: 'Total de Pedidos',
                data: <?php echo json_encode(array_column($horarios_pedidos, 'total_pedidos')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                title: {
                    display: true,
                    text: 'Distribuição de Pedidos por Horário'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

// Função para exportar relatórios
async function exportarRelatorio(tipo) {
    const periodo = document.getElementById('periodoVendas').value;
    const dados = periodo === '7' ? {
        vendas: <?php echo json_encode($vendas_7_dias); ?>,
        periodo: '7'
    } : {
        vendas: <?php echo json_encode($vendas_30_dias); ?>,
        periodo: '30'
    };

    if (tipo === 'excel') {
        await exportarExcel(dados);
    } else if (tipo === 'pdf') {
        exportarPDF(dados);
    }
}

async function exportarExcel(dados) {
    const workbook = new ExcelJS.Workbook();
    
    // Planilha de Vendas Diárias
    const wsVendas = workbook.addWorksheet('Vendas Diárias');
    wsVendas.columns = [
        { header: 'Data', key: 'data', width: 15 },
        { header: 'Total Pedidos', key: 'pedidos', width: 15 },
        { header: 'Total Vendas (R$)', key: 'vendas', width: 20 }
    ];

    // Adicionar dados de vendas
    dados.vendas.forEach(venda => {
        wsVendas.addRow({
            data: venda.dia,
            pedidos: venda.total_pedidos,
            vendas: parseFloat(venda.total_vendas)
        });
    });

    // Nova planilha para Pedidos Detalhados
    const wsPedidos = workbook.addWorksheet('Pedidos Detalhados');
    wsPedidos.columns = [
        { header: 'ID', key: 'id', width: 10 },
        { header: 'Nome Cliente', key: 'nome', width: 30 },
        { header: 'Telefone', key: 'telefone', width: 15 },
        { header: 'Endereço', key: 'endereco', width: 40 },
        { header: 'Bairro', key: 'bairro', width: 20 },
        { header: 'Itens', key: 'itens', width: 40 },
        { header: 'Total', key: 'total', width: 15 },
        { header: 'Taxa Entrega', key: 'taxa_entrega', width: 15 },
        { header: 'Pagamento', key: 'pagamento', width: 15 },
        { header: 'Status', key: 'status', width: 15 },
        { header: 'Data', key: 'data', width: 20 },
        { header: 'Tipo', key: 'tipo', width: 10 }
    ];

    // Adicionar dados dos pedidos
    <?php
    $stmt = $conn->prepare("
        SELECT * FROM cliente 
        WHERE STR_TO_DATE(data, '%d/%m/%Y %H:%i') >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY STR_TO_DATE(data, '%d/%m/%Y %H:%i') DESC
    ");
    $dias = 30; // Últimos 30 dias
    $stmt->bind_param('i', $dias);
    $stmt->execute();
    $pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    ?>

    // Converter dados dos pedidos para JavaScript
    const pedidosData = <?php echo json_encode($pedidos); ?>;
    
    // Adicionar cada pedido na planilha
    pedidosData.forEach(pedido => {
        wsPedidos.addRow({
            id: pedido.id,
            nome: pedido.nome,
            telefone: pedido.telefone,
            endereco: `${pedido.rua} ${pedido.complemento || ''}`,
            bairro: pedido.bairro,
            itens: pedido.itens,
            total: pedido.total,
            taxa_entrega: pedido.taxa_entrega,
            pagamento: pedido.pagamento,
            status: pedido.status,
            data: pedido.data,
            tipo: pedido.tipo
        });
    });

    // Formatar células numéricas na planilha de pedidos
    wsPedidos.getColumn('total').numFmt = '"R$ "#,##0.00';
    wsPedidos.getColumn('taxa_entrega').numFmt = '"R$ "#,##0.00';

    // Planilha de Produtos
    const wsProdutos = workbook.addWorksheet('Produtos Populares');
    wsProdutos.columns = [
        { header: 'Produto', key: 'produto', width: 40 },
        { header: 'Quantidade', key: 'quantidade', width: 15 },
        { header: 'Total (R$)', key: 'total', width: 20 }
    ];

    // Adicionar dados dos produtos
    <?php foreach ($produtos_populares as $produto): ?>
    wsProdutos.addRow({
        produto: <?php echo json_encode($produto['itens']); ?>,
        quantidade: <?php echo $produto['quantidade']; ?>,
        total: <?php echo $produto['total_vendas']; ?>
    });
    <?php endforeach; ?>

    // Planilha de Pagamentos
    const wsPagamentos = workbook.addWorksheet('Formas de Pagamento');
    wsPagamentos.columns = [
        { header: 'Forma de Pagamento', key: 'forma', width: 25 },
        { header: 'Quantidade', key: 'quantidade', width: 15 },
        { header: 'Total (R$)', key: 'total', width: 20 }
    ];

    // Adicionar dados dos pagamentos
    <?php foreach ($pagamentos as $pagamento): ?>
    wsPagamentos.addRow({
        forma: <?php echo json_encode($pagamento['pagamento']); ?>,
        quantidade: <?php echo $pagamento['quantidade']; ?>,
        total: <?php echo $pagamento['total_valor']; ?>
    });
    <?php endforeach; ?>

    // Planilha de Análise de Lucratividade
    const wsLucratividade = workbook.addWorksheet('Análise de Lucratividade');
    wsLucratividade.columns = [
        { header: 'Mês', key: 'mes', width: 15 },
        { header: 'Receita Bruta (R$)', key: 'receita_bruta', width: 20 },
        { header: 'Custos Entrega (R$)', key: 'custos_entrega', width: 20 },
        { header: 'Receita Líquida (R$)', key: 'receita_liquida', width: 20 },
        { header: 'Margem de Lucro (%)', key: 'margem_lucro', width: 20 }
    ];

    // Adicionar dados de lucratividade
    <?php foreach ($analise_lucratividade as $mes): ?>
    wsLucratividade.addRow({
        mes: <?php echo json_encode(date('M/Y', strtotime($mes['mes']))); ?>,
        receita_bruta: <?php echo $mes['receita_bruta']; ?>,
        custos_entrega: <?php echo $mes['custos_entrega']; ?>,
        receita_liquida: <?php echo $mes['receita_liquida']; ?>,
        margem_lucro: <?php echo $mes['margem_lucro']; ?>
    });
    <?php endforeach; ?>

    // Formatar células numéricas
    wsLucratividade.getColumn('receita_bruta').numFmt = '"R$ "#,##0.00';
    wsLucratividade.getColumn('custos_entrega').numFmt = '"R$ "#,##0.00';
    wsLucratividade.getColumn('receita_liquida').numFmt = '"R$ "#,##0.00';
    wsLucratividade.getColumn('margem_lucro').numFmt = '0.00"%"';

    // Gerar arquivo
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Relatório_Vendas_${dados.periodo}_dias_${new Date().toLocaleDateString()}.xlsx`;
    a.click();
    window.URL.revokeObjectURL(url);
}

function exportarPDF(dados) {
    // Importar jsPDF do namespace global
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Configurações
    const pageWidth = doc.internal.pageSize.getWidth();
    const margin = 10;
    let yPos = 20;
    
    // Título
    doc.setFontSize(16);
    doc.text(`Relatório de Vendas - Últimos ${dados.periodo} dias`, margin, yPos);
    
    // Subtítulo com data
    yPos += 10;
    doc.setFontSize(12);
    doc.text(`Gerado em: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}`, margin, yPos);
    
    // Tabela de vendas
    yPos += 15;
    doc.setFontSize(14);
    doc.text('Vendas Diárias', margin, yPos);
    
    const vendasData = dados.vendas.map(venda => [
        venda.dia,
        venda.total_pedidos.toString(),
        `R$ ${parseFloat(venda.total_vendas).toFixed(2)}`
    ]);
    
    doc.autoTable({
        startY: yPos + 5,
        head: [['Data', 'Total Pedidos', 'Total Vendas']],
        body: vendasData,
        margin: { left: margin },
        theme: 'grid'
    });
    
    // Produtos mais vendidos
    yPos = doc.lastAutoTable.finalY + 15;
    doc.setFontSize(14);
    doc.text('Produtos Mais Vendidos', margin, yPos);
    
    const produtosData = <?php echo json_encode(array_map(function($produto) {
        return [
            $produto['itens'],
            $produto['quantidade'],
            'R$ ' . number_format($produto['total_vendas'], 2, ',', '.')
        ];
    }, $produtos_populares)); ?>;
    
    doc.autoTable({
        startY: yPos + 5,
        head: [['Produto', 'Quantidade', 'Total']],
        body: produtosData,
        margin: { left: margin },
        theme: 'grid'
    });
    
    // Salvar PDF
    doc.save(`Relatório_Vendas_${dados.periodo}_dias_${new Date().toLocaleDateString()}.pdf`);
}
</script>

<style>
.stats-container {
    padding: 20px;
}

.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: none;
    border-radius: 10px;
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.btn {
    margin-right: 10px;
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
}
.card-title2 {
    color: #000;
    font-weight: 600;
}

.card-header .form-select {
    min-width: 150px;
    margin-left: 15px;
    font-size: 0.9rem;
    padding: 0.375rem 2rem 0.375rem 0.75rem;
    border-radius: 5px;
    border: 1px solid #ced4da;
    background-color: #fff;
}

.card-header .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(var(--primary-color-rgb), 0.25);
}

.d-flex {
    display: flex !important;
}

.justify-content-between {
    justify-content: space-between !important;
}

.align-items-center {
    align-items: center !important;
}

.calendar-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-container .table {
    margin-bottom: 0;
}

.calendar-container .table thead th {
    border-top: none;
    border-bottom: 2px solid <?php echo $config['primary_color']; ?>;
    font-weight: 600;
}

.calendar-container .table td {
    vertical-align: middle;
    padding: 12px;
    border-color: rgba(0,0,0,0.05);
}

.calendar-container .table tbody tr:hover {
    background-color: rgba(<?php 
        $hex = str_replace('#', '', $config['primary_color']);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        echo "$r,$g,$b,0.05";
    ?>);
}

.card-header img {
    max-height: 40px;
    object-fit: contain;
}

.text-success {
    color: #4CAF50 !important;
}

/* Animação suave ao carregar */
.calendar-container {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
