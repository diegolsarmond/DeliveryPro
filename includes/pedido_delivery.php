



    <!-- Formulário de Cadastro Manual -->
    <div class="settings-card mb-4">
        <h4><i class="fas fa-plus-circle"></i> Cadastro Manual de Pedido</h4>
        <form id="cadastroPedidoFormDelivery" class="mt-3">
            <!-- Seleção de Cliente -->
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Selecionar Cliente Cadastrado</label>
                    <div class="input-group">
                        <select class="form-control" id="clienteSelectDelivery">
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
                        <select class="form-control" id="selectProdutoDelivery">
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
                    <div id="itensSelecionadosDelivery" class="list-group">
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

document.getElementById('cadastroPedidoFormDelivery').addEventListener('submit', function(e) {
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
    const select = document.getElementById('selectProdutoDelivery');
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
        
        document.getElementById('itensSelecionadosDelivery').insertAdjacentHTML('beforeend', itemHtml);
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
    const container = document.getElementById('itensSelecionadosDelivery');
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
    $('#clienteSelectDelivery').on('change', function() {
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
    document.getElementById('clienteSelectDelivery').value = '';
    document.getElementById('clienteSelectDelivery').dispatchEvent(new Event('change'));
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
    document.querySelectorAll('#itensSelecionadosDelivery .list-group-item').forEach(item => {
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
    document.querySelectorAll('#itensSelecionadosDelivery .list-group-item').forEach(item => {
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