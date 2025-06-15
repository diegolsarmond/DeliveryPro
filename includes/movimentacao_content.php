<?php
// File Hash: a33806b5745458f0b19766d4c836d2a0

// Buscar configurações de entrega
$stmt = $conn->prepare("SELECT * FROM config_entregas LIMIT 1");
$stmt->execute();
$config_entregas = $stmt->get_result()->fetch_assoc();

// Buscar estatísticas
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_entregas,
        SUM(CASE WHEN status = 'Entregue' THEN 1 ELSE 0 END) as entregas_concluidas,
        SUM(taxa_entrega) as total_taxas
    FROM entregas
    WHERE DATE(hora_saida) = CURDATE()
");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Buscar entregadores ativos
$stmt = $conn->prepare("SELECT * FROM entregadores WHERE status != 'Inativo'");
$stmt->execute();
$entregadores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Configuração da paginação para Entregas em Andamento
$itens_por_pagina_andamento = 5;
$pagina_atual_andamento = isset($_GET['pagina_andamento']) ? (int)$_GET['pagina_andamento'] : 1;
$offset_andamento = ($pagina_atual_andamento - 1) * $itens_por_pagina_andamento;

// Buscar total de entregas em andamento (excluindo POS)
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM entregas e 
    LEFT JOIN cliente c ON e.pedido_id = c.id
    WHERE e.status IN ('Aguardando', 'Em Rota')
    AND c.tipo != 'pos'
");
$stmt->execute();
$total_entregas_andamento = $stmt->get_result()->fetch_assoc()['total'];
$total_paginas_andamento = ceil($total_entregas_andamento / $itens_por_pagina_andamento);

// Modificar a query de entregas pendentes para excluir pedidos POS
$stmt = $conn->prepare("
    SELECT e.*, c.nome as cliente_nome, c.telefone, c.rua, c.bairro, c.complemento, c.cep,
           ent.nome as entregador_nome, c.pedido as numero_pedido
    FROM entregas e
    LEFT JOIN cliente c ON e.pedido_id = c.id
    LEFT JOIN entregadores ent ON e.entregador_id = ent.id
    WHERE e.status IN ('Aguardando', 'Em Rota')
    AND c.tipo != 'pos'
    ORDER BY e.id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $itens_por_pagina_andamento, $offset_andamento);
$stmt->execute();
$entregas_pendentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$dias_funcionamento = explode(',', $config_entregas['dias_funcionamento']);

// Configuração da paginação para Entregas Finalizadas
$itens_por_pagina_finalizadas = 5;
$pagina_atual_finalizadas = isset($_GET['pagina_finalizadas']) ? (int)$_GET['pagina_finalizadas'] : 1;
$offset_finalizadas = ($pagina_atual_finalizadas - 1) * $itens_por_pagina_finalizadas;

// Buscar total de entregas finalizadas
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM entregas e 
    WHERE e.status = 'Entregue'
");
$stmt->execute();
$total_entregas_finalizadas = $stmt->get_result()->fetch_assoc()['total'];
$total_paginas_finalizadas = ceil($total_entregas_finalizadas / $itens_por_pagina_finalizadas);

// Buscar entregas finalizadas com paginação
$stmt = $conn->prepare("
    SELECT e.*, c.nome as cliente_nome, c.telefone, c.rua, c.bairro, c.complemento, c.cep,
           ent.nome as entregador_nome, c.pedido as numero_pedido
    FROM entregas e
    LEFT JOIN cliente c ON e.pedido_id = c.id
    LEFT JOIN entregadores ent ON e.entregador_id = ent.id
    WHERE e.status = 'Entregue'
    ORDER BY e.id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $itens_por_pagina_finalizadas, $offset_finalizadas);
$stmt->execute();
$entregas_finalizadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="movimentacao-container">
    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Entregas Hoje</h5>
                            <h3><?php echo $stats['total_entregas']; ?></h3>
                        </div>
                        <i class="fas fa-truck fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Entregas Concluídas</h5>
                            <h3><?php echo $stats['entregas_concluidas'] ?? 0; ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total em Taxas</h5>
                            <h3>R$ <?php echo number_format($stats['total_taxas'], 2, ',', '.'); ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Entregadores Ativos</h5>
                            <?php 
                            // Modificar a query para contar apenas entregadores com status 'Ativo'
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM entregadores WHERE status = 'Ativo'");
                            $stmt->execute();
                            $entregadores_ativos = $stmt->get_result()->fetch_assoc();
                            ?>
                            <h3><?php echo $entregadores_ativos['total']; ?></h3>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>














    <!-- Entregas Pendentes -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 card-title2">Entregas em Andamento</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Endereço</th>
                            <th>Entregador</th>
                            <th>Status</th>
                            <th>Tempo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entregas_pendentes)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-route fa-3x text-muted mb-3 d-block"></i>
                                <p class="mb-0">Nenhuma entrega em andamento</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($entregas_pendentes as $entrega): ?>
                        <tr>
                            <td>#<?php echo str_pad($entrega['numero_pedido'], 6, "0", STR_PAD_LEFT); ?></td>
                            <td>
                                <?php echo $entrega['cliente_nome']; ?><br>
                                <small><?php echo $entrega['telefone']; ?></small>
                            </td>
                            <td><?php echo $entrega['rua'] . ', ' . $entrega['bairro']; ?></td>
                            <td>
                                <?php if ($entrega['entregador_id']): ?>
                                    <?php echo $entrega['entregador_nome']; ?>
                                <?php else: ?>
                                    <select class="form-select form-select-sm" onchange="atribuirEntregador(<?php echo $entrega['id']; ?>, this.value)">
                                        <option value="">Selecionar...</option>
                                        <?php foreach ($entregadores as $entregador): ?>
                                            <?php if ($entregador['status'] == 'Ativo'): ?>
                                                <option value="<?php echo $entregador['id']; ?>"><?php echo $entregador['nome']; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $entrega['status'] == 'Em Rota' ? 'info' : 'warning'; ?>">
                                    <?php echo $entrega['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($entrega['hora_saida']) {
                                    $inicio = new DateTime($entrega['hora_saida'], new DateTimeZone('America/Sao_Paulo'));
                                    $agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
                                    $diff = $inicio->diff($agora);
                                    // Formatar para mostrar minutos totais
                                    $minutos_totais = ($diff->h * 60) + $diff->i;
                                    $horas = floor($minutos_totais / 60);
                                    $minutos = $minutos_totais % 60;
                                    echo sprintf("%02d:%02d", $horas, $minutos);
                                } else {
                                    echo '--:--';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($entrega['status'] == 'Em Rota'): ?>
                                        <button class="btn btn-sm btn-success" onclick="marcarEntregue(<?php echo $entrega['id']; ?>)">
                                            <i class="fas fa-check"></i> Entregue
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($entrega['status'] != 'Entregue'): ?>
                                        <button class="btn btn-sm btn-danger" onclick="cancelarEntrega(<?php echo $entrega['id']; ?>)">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="verDetalhesEntrega(<?php echo $entrega['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($entrega['entregador_id']): ?>
                                        <button class="btn btn-sm btn-success" onclick="enviarMensagemEntregador(<?php echo $entrega['id']; ?>)">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paginação das Entregas em Andamento -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando <?php echo ($offset_andamento + 1); ?>-<?php echo min($offset_andamento + $itens_por_pagina_andamento, $total_entregas_andamento); ?> de <?php echo $total_entregas_andamento; ?> entregas
        </div>
        <nav aria-label="Navegação de páginas">
            <ul class="pagination mb-0">
                <?php if ($pagina_atual_andamento > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina_andamento=<?php echo ($pagina_atual_andamento - 1); ?>" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $inicio_paginacao = max(1, $pagina_atual_andamento - 2);
                $fim_paginacao = min($total_paginas_andamento, $pagina_atual_andamento + 2);

                if ($inicio_paginacao > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?pagina_andamento=1">1</a></li>';
                    if ($inicio_paginacao > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++) {
                    echo '<li class="page-item ' . ($i == $pagina_atual_andamento ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?pagina_andamento=' . $i . '">' . $i . '</a>';
                    echo '</li>';
                }

                if ($fim_paginacao < $total_paginas_andamento) {
                    if ($fim_paginacao < $total_paginas_andamento - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?pagina_andamento=' . $total_paginas_andamento . '">' . $total_paginas_andamento . '</a></li>';
                }
                ?>

                <?php if ($pagina_atual_andamento < $total_paginas_andamento): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina_andamento=<?php echo ($pagina_atual_andamento + 1); ?>" aria-label="Próxima">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<br>
    <!-- Entregas Finalizadas -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 card-title2">Entregas Finalizadas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Endereço</th>
                            <th>Entregador</th>
                            <th>Tempo Total</th>
                            <th>Taxa</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entregas_finalizadas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-muted mb-3 d-block"></i>
                                <p class="mb-0">Nenhuma entrega finalizada</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($entregas_finalizadas as $entrega): ?>
                            <tr>
                                <td>#<?php echo str_pad($entrega['numero_pedido'], 6, "0", STR_PAD_LEFT); ?></td>
                                <td>
                                    <?php echo $entrega['cliente_nome']; ?><br>
                                    <small><?php echo $entrega['telefone']; ?></small>
                                </td>
                                <td>
                                    <?php 
                                    echo $entrega['rua'] . ', ' . $entrega['bairro'];
                                    if (!empty($entrega['complemento'])) {
                                        echo ' - ' . $entrega['complemento'];
                                    }
                                    ?>
                                </td>
                                <td><?php echo $entrega['entregador_nome']; ?></td>
                                <td>
                                    <?php 
                                    if ($entrega['hora_saida'] && $entrega['hora_entrega']) {
                                        $inicio = new DateTime($entrega['hora_saida']);
                                        $fim = new DateTime($entrega['hora_entrega']);
                                        $intervalo = $inicio->diff($fim);
                                        
                                        // Calcular tempo total em minutos
                                        $minutos_totais = ($intervalo->h * 60) + $intervalo->i;
                                        $horas = floor($minutos_totais / 60);
                                        $minutos = $minutos_totais % 60;
                                        
                                        // Formatar a saída
                                        echo sprintf("%02d:%02d", $horas, $minutos);
                                    } else {
                                        echo '--:--';
                                    }
                                    ?>
                                </td>
                                <td>R$ <?php echo number_format($entrega['taxa_entrega'], 2, ',', '.'); ?></td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-success">
                                            Entregue
                                        </span>
                                        <button class="btn btn-sm btn-danger" onclick="excluirEntregaFinalizada(<?php echo $entrega['id']; ?>)">
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
        </div>
    </div>
    <!-- Paginação das Entregas Finalizadas -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Mostrando <?php echo ($offset_finalizadas + 1); ?>-<?php echo min($offset_finalizadas + $itens_por_pagina_finalizadas, $total_entregas_finalizadas); ?> de <?php echo $total_entregas_finalizadas; ?> entregas
        </div>
        <nav aria-label="Navegação de páginas">
            <ul class="pagination mb-0">
                <?php if ($pagina_atual_finalizadas > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina_finalizadas=<?php echo ($pagina_atual_finalizadas - 1); ?>" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $inicio_paginacao = max(1, $pagina_atual_finalizadas - 2);
                $fim_paginacao = min($total_paginas_finalizadas, $pagina_atual_finalizadas + 2);

                if ($inicio_paginacao > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?pagina_finalizadas=1">1</a></li>';
                    if ($inicio_paginacao > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++) {
                    echo '<li class="page-item ' . ($i == $pagina_atual_finalizadas ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?pagina_finalizadas=' . $i . '">' . $i . '</a>';
                    echo '</li>';
                }

                if ($fim_paginacao < $total_paginas_finalizadas) {
                    if ($fim_paginacao < $total_paginas_finalizadas - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?pagina_finalizadas=' . $total_paginas_finalizadas . '">' . $total_paginas_finalizadas . '</a></li>';
                }
                ?>

                <?php if ($pagina_atual_finalizadas < $total_paginas_finalizadas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina_finalizadas=<?php echo ($pagina_atual_finalizadas + 1); ?>" aria-label="Próxima">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<br>
<script>
// Funções JavaScript para gerenciar entregas e entregadores
function novoEntregador() {
    Swal.fire({
        title: 'Novo Entregador',
        html: `
            <form id="entregadorForm">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Documento</label>
                    <input type="text" class="form-control" id="documento" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Veículo</label>
                    <input type="text" class="form-control" id="veiculo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Placa</label>
                    <input type="text" class="form-control" id="placa">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Coletar dados do formulário
            const formData = {
                nome: document.getElementById('nome').value,
                telefone: document.getElementById('telefone').value,
                documento: document.getElementById('documento').value,
                veiculo: document.getElementById('veiculo').value,
                placa: document.getElementById('placa').value
            };

            // Enviar para o backend
            return fetch('ajax/salvar_entregador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao salvar entregador');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Entregador cadastrado com sucesso',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.reload();
            });
        }
    }).catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
}

function editarEntregador(entregador) {
    Swal.fire({
        title: 'Editar Entregador',
        html: `
            <form id="entregadorForm">
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome" value="${entregador.nome}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="telefone" value="${entregador.telefone}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Documento</label>
                    <input type="text" class="form-control" id="documento" value="${entregador.documento}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Veículo</label>
                    <input type="text" class="form-control" id="veiculo" value="${entregador.veiculo}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Placa</label>
                    <input type="text" class="form-control" id="placa" value="${entregador.placa || ''}">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const formData = {
                id: entregador.id,
                nome: document.getElementById('nome').value,
                telefone: document.getElementById('telefone').value,
                documento: document.getElementById('documento').value,
                veiculo: document.getElementById('veiculo').value,
                placa: document.getElementById('placa').value
            };
 
            return fetch('ajax/atualizar_entregador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao atualizar entregador');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Entregador atualizado com sucesso',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.reload();
            });
        }
    });
}

function excluirEntregador(id) {
    Swal.fire({
        title: 'Confirmar Exclusão',
        text: 'Tem certeza que deseja excluir este entregador?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/excluir_entregador.php', {
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
                        text: 'Entregador excluído com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao excluir entregador');
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

function atribuirEntregador(entregaId, entregadorId) {
    fetch('ajax/atribuir_entregador.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `entrega_id=${entregaId}&entregador_id=${entregadorId}`
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

function marcarEntregue(entregaId) {
    Swal.fire({
        title: 'Confirmar Entrega',
        text: 'Deseja marcar esta entrega como concluída?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim',
        cancelButtonText: 'Não'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/concluir_entrega.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `entrega_id=${entregaId}`
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

function verDetalhesEntrega(entregaId) {
    console.log('ID da entrega:', entregaId);

    fetch('ajax/buscar_detalhes_entrega_completa.php?id=' + entregaId)
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        if (data.success) {
            let detalhesHtml = `
                <div class="text-left">
                    <h2 class="text-center mb-4">Pedido #${data.pedido.numero_pedido}</h2>
                    <p><strong>Cliente:</strong> ${data.pedido.nome}</p>
                    <p><strong>Telefone:</strong> ${data.pedido.telefone}</p>
                    <p><strong>Endereço:</strong> ${data.pedido.rua}, ${data.pedido.bairro}</p>
                    <p><strong>Complemento:</strong> ${data.pedido.complemento || 'Não informado'}</p>
                    <!-- <p><strong>CEP:</strong> ${data.pedido.cep}</p> -->
                    <p><strong>Observação:</strong> ${data.pedido.observacao || 'Não informado'}</p>
                    <p><strong>Itens:</strong> ${data.pedido.itens}</p>
                    <p><strong>Taxa de Entrega:</strong> ${data.pedido.taxa_entrega ? `${data.pedido.taxa_entrega}` : 'R$ 0,00'}</p>
                    <p><strong>Sub Total:</strong> ${data.pedido.sub_total}</p>
                    <p><strong>Total:</strong> ${data.pedido.total}</p>
                    <p><strong>Forma de Pagamento:</strong> ${data.pedido.pagamento}</p>
                    <hr>
                    <p><strong>Status da Entrega:</strong> ${data.entrega.status}</p>
                    ${data.entrega.entregador_nome ? `<p><strong>Entregador:</strong> ${data.entrega.entregador_nome}</p>` : ''}
                    ${data.entrega.hora_saida ? `<p><strong>Hora de Saída:</strong> ${new Date(data.entrega.hora_saida).toLocaleTimeString()}</p>` : ''}
                    ${data.entrega.hora_entrega ? `<p><strong>Hora de Entrega:</strong> ${new Date(data.entrega.hora_entrega).toLocaleTimeString()}</p>` : ''}
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
            throw new Error(data.message || 'Erro ao buscar detalhes');
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    });
}

function atualizarStatusEntrega(entregaId) {
    // Primeiro buscar o status atual do pedido
    fetch('ajax/buscar_status_pedido.php?entrega_id=' + entregaId)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Atualizar Status',
                html: `
                    <div class="mb-3">
                        <label class="form-label">Status do Pedido</label>
                        <select class="form-select" id="statusPedido">
                            <option value="Pendente" ${data.status === 'Pendente' ? 'selected' : ''}>Pendente</option>
                            <option value="Em Preparo" ${data.status === 'Em Preparo' ? 'selected' : ''}>Em Preparo</option>
                            <option value="Pronto para Entrega" ${data.status === 'Pronto para Entrega' ? 'selected' : ''}>Pronto para Entrega</option>
                            <option value="Em Rota" ${data.status === 'Em Rota' ? 'selected' : ''}>Em Rota</option>
                            <option value="Entregue" ${data.status === 'Entregue' ? 'selected' : ''}>Entregue</option>
                        </select>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Atualizar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const status = document.getElementById('statusPedido').value;
                    return fetch('ajax/atualizar_status_entrega.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `entrega_id=${entregaId}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Erro ao atualizar status');
                        }
                        return data;
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
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

function cancelarEntrega(entregaId) {
    Swal.fire({
        title: 'Cancelar Entrega',
        text: 'Tem certeza que deseja cancelar esta entrega?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/cancelar_entrega.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `entrega_id=${entregaId}`
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

function salvarConfiguracoes(event) {
    event.preventDefault();
    
    // Pegar os dias selecionados
    const diasSelecionados = [];
    document.querySelectorAll('input[name="dias[]"]:checked').forEach(checkbox => {
        diasSelecionados.push(checkbox.value);
    });
    
    const formData = {
        taxa_entrega: document.querySelector('input[name="taxa_entrega"]').value,
        horario_inicio: document.querySelector('input[name="horario_inicio"]').value,
        horario_fim: document.querySelector('input[name="horario_fim"]').value,
        dias_funcionamento: diasSelecionados.join(',')
    };
    
    fetch('ajax/salvar_config_entrega.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: 'Configurações salvas com sucesso',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Erro ao salvar configurações');
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

function enviarMensagemEntregador(entregaId) {
    fetch('ajax/get_messages.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro ao buscar mensagens');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Buscar detalhes da entrega primeiro
            fetch(`ajax/buscar_detalhes_entrega_completa.php?id=${entregaId}`)
            .then(response => response.json())
            .then(entregaData => {
                if (!entregaData.success) {
                    throw new Error('Erro ao buscar detalhes da entrega');
                }

                const entrega = entregaData.entrega;
                const pedido = entregaData.pedido;

                // Criar opções do select agrupadas por categoria
                let optionsHtml = '<option value="">Selecione uma mensagem...</option>';
                Object.entries(data.messages).forEach(([categoria, mensagens]) => {
                    // Filtrar apenas mensagens da categoria "Entregadores"
                    if (categoria === 'Entregadores') {
                        optionsHtml += `<optgroup label="${categoria}">`;
                        mensagens.forEach(msg => {
                            optionsHtml += `<option value="${msg.id}" data-template="${msg.template}">${msg.nome_mensagem}</option>`;
                        });
                        optionsHtml += '</optgroup>';
                    }
                });

                // Mostrar modal de seleção
                Swal.fire({
                    title: 'Enviar Notificação <br> <small>🛵 Entregador</small>',
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
                                    .replace(/\$nome_entregador/g, entrega.entregador_nome)
                                    .replace(/\$pedido/g, pedido.numero_pedido)
                                    .replace(/\$cliente/g, pedido.nome)
                                    .replace(/\$endereco/g, `${pedido.rua}, ${pedido.bairro}`)
                                    .replace(/\$total/g, pedido.total)
                                    .replace(/\$taxa/g, entrega.taxa_entrega)
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

                        // Enviar notificação com os dados da entrega e pedido
                        const formData = new FormData();
                        formData.append('mensagem_id', mensagemId);
                        formData.append('entrega_id', entregaId);
                        formData.append('entrega_data', JSON.stringify({
                            entrega: entrega,
                            pedido: pedido
                        }));

                        fetch('ajax/send_notification_entregador.php', {
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
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
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

function excluirEntregaFinalizada(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Tem certeza que deseja excluir esta entrega?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('ajax/excluir_entrega.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `entrega_id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Entrega excluída com sucesso',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Erro ao excluir entrega');
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

function confirmarRemoverRegiao(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Esta ação não poderá ser revertida!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, remover!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            removerRegiao(id);
        }
    });
}

function removerRegiao(id) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('ajax/remover_regiao_entrega.php', {
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
            throw new Error(data.message || 'Erro ao remover região');
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

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar nova taxa
    document.getElementById('adicionarTaxa').addEventListener('click', function() {
        const container = document.getElementById('taxasContainer');
        const novaTaxa = document.createElement('div');
        novaTaxa.className = 'row mb-2 taxa-row';
        novaTaxa.innerHTML = `
            <div class="col-md-5">
                <input type="text" class="form-control" name="regioes[]" 
                       placeholder="Nome da região" required>
            </div>
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="number" class="form-control" name="valores[]" 
                           step="0.01" min="0" required>
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remover-taxa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(novaTaxa);
    });

    // Remover taxa
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remover-taxa')) {
            e.target.closest('.taxa-row').remove();
        }
    });

    // Salvar configurações
    document.getElementById('configEntregaForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('ajax/salvar_config_entrega.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Configurações salvas com sucesso',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                throw new Error(data.message || 'Erro ao salvar configurações');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message
            });
        }
    });
});
</script>

<style>
.card {
    margin-bottom: 20px;
}

.badge {
    font-size: 0.9em;
    padding: 0.5em 0.7em;
}

.table th {
    white-space: nowrap;
}
.card-header2 {
    color: #000;
}
</style>

<footer>
    <p><?php echo $config['footer_text']; ?></p>
</footer>