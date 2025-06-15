<?php
// File Hash: 164338f35ab73efcd6525dc04aeb27cf

// Verificar permissão
if (!$_SESSION['permissions']['pos']) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta área.</div>';
    exit;
}

?>

<div class="pos-container">

    <div class="row">
        <!-- Área de Produtos -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex flex-column">
                    <h5 class="card-title2 mb-3">Produtos</h5>
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchProduct" placeholder="Buscar produto...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" id="productsGrid">
                        <!-- Os produtos serão carregados aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Área do Carrinho -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2">Carrinho</h5>
                </div>
                <div class="card-body">
                    <div id="cartItems">
                        <!-- Itens do carrinho serão exibidos aqui -->
                    </div>
                    <hr>
                    <div class="cart-totals">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">R$ 0,00</span>
                        </div>
                        
                        <!-- Botões de Taxa e Desconto -->
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-sm btn-outline-primary w-50" onclick="showAdjustmentModal('taxa')">
                                <i class="fas fa-plus-circle"></i> ‎ ‎ Taxa
                            </button>
                            <button class="btn btn-sm btn-outline-success w-50" onclick="showAdjustmentModal('desconto')">
                                <i class="fas fa-minus-circle"></i> ‎ ‎ Desconto
                            </button>
                        </div>
                        
                        <!-- Área de ajustes sempre visível -->
                        <div id="adjustments" class="mb-2">
                            <div id="taxaRow" class="d-flex justify-content-between mb-1">
                                <span class="text-primary">Taxa:</span>
                                <span id="taxaValue">R$ 0,00</span>
                            </div>
                            <div id="descontoRow" class="d-flex justify-content-between mb-1">
                                <span class="text-success">Desconto:</span>
                                <span id="descontoValue">R$ 0,00</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong id="total">R$ 0,00</strong>
                        </div>
                    </div>
                    <hr>
                    <button class="btn btn-success w-100 mb-2" onclick="finalizarVenda()">
                        <i class="fas fa-check"></i> Finalizar Venda
                    </button>
                    <button class="btn btn-danger w-100" onclick="limparCarrinho()">
                        <i class="fas fa-trash"></i> Limpar Carrinho
                    </button>
                </div>
                <!-- Após a div do carrinho, adicionar: -->
        <div class="col-md-12 mt-4">
            <div>
                <div class="card-header">
                    <h5 class="card-title2">Mesas</h5>
                </div>
                <div class="card-body">
                    <div class="mesas-grid" id="mesasGrid">
                        <!-- As mesas serão carregadas aqui via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
            </div>
                <!-- Adicionar botão do Painel de Filas -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <button type="button" class="btn btn-primary" onclick="abrirPainelFilas()">
                <i class="fas fa-tv"></i> Abrir Painel de Filas
            </button>
        </div>
    </div>
        </div>
    </div>
</div>

<style>
.pos-container {
    padding: 20px;
}

.product-card {
    cursor: pointer;
    transition: transform 0.2s;
    margin-bottom: 15px;
}

.product-card:hover {
    transform: translateY(-5px);
}

.cart-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-totals {
    font-size: 1.1em;
}

#searchProduct {
    border-radius: 20px;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.categoria-titulo {
    color: var(--primary-color);
    padding: 10px;
    margin-top: 15px;
    border-bottom: 2px solid var(--primary-color);
}

.product-card {
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 15px;
    border: 1px solid #eee;
    position: relative;
    overflow: hidden;
}

.product-card::before {
    content: '\f07a';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 80px;
    opacity: 0.05;
    transform: rotate(-15deg);
    pointer-events: none;
    z-index: 0;
}

.product-card .card-body {
    padding: 15px;
    position: relative;
    z-index: 1;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-card .card-title {
    margin-bottom: 10px;
    font-weight: 500;
}
.card-title3 {
    color: #000000;
}

.product-card .card-text {
    color: var(--primary-color);
    font-weight: bold;
    margin-bottom: 0;
}

.search-container {
    margin-top: 15px;
    position: relative;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    border-radius: 50px;
    overflow: hidden;
}

.search-input {
    border: none !important;
    padding: 12px 20px;
    font-size: 14px;
    border-radius: 50px !important;
    background-color: #f8f9fa;
}

.search-input:focus {
    box-shadow: none !important;
    background-color: #fff;
}

.btn-search {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    border: none;
    background-color: var(--primary-color);
    color: white;
    padding: 0 25px;
    z-index: 10;
    transition: all 0.3s ease;
}

.btn-search:hover {
    background-color: var(--primary-hover-color);
    color: white;
}

.btn-search i {
    font-size: 14px;
}

.input-group:focus-within .search-input {
    border-color: transparent;
}

.search-input::placeholder {
    color: #adb5bd;
    font-size: 14px;
}

.search-box {
    position: relative;
    width: 100%;
    margin-bottom: 10px;
}

.search-box input {
    width: 100%;
    padding: 10px 40px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(221, 44, 42, 0.1);
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 14px;
}

.search-box input::placeholder {
    color: #999;
}

.mesas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    padding: 15px;
}

.mesa-item {
    text-align: center;
    cursor: pointer;
    padding: 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    border: 2px solid #ddd;
    background-color: #fff;
}

.mesa-item:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
}

.mesa-item.selected {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.mesa-item.ocupada {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
    opacity: 0.8;
}

.mesa-icon {
    font-size: 24px;
    margin-bottom: 10px;
    color: inherit;
}

.mesa-numero {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 5px;
}

.mesa-status {
    font-size: 12px;
    opacity: 0.8;
}

.calculator-modal .calc-btn {
    width: 50px;
    height: 50px;
    margin: 5px;
    font-size: 1.2em;
    border-radius: 8px;
    border: 1px solid #ddd;
    background-color: #fff;
    transition: all 0.2s;
}

.calculator-modal .calc-btn:hover {
    background-color: #f8f9fa;
}

.calculator-modal .calc-btn:active {
    transform: scale(0.95);
}

.calculator-modal .calc-display {
    font-size: 1.5em;
    padding: 10px;
    text-align: right;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 10px;
    background-color: #f8f9fa;
}

.calculator-modal .calc-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 5px;
}

.calculator-modal .calc-btn.operator {
    background-color: var(--primary-color);
    color: white;
}

.calculator-modal .calc-btn.equals {
    background-color: #28a745;
    color: white;
    grid-column: span 2;
}

/* Estilo para o botão do Painel de Filas */
.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-hover);
    border-color: var(--primary-hover);
}
</style>

<script src="modal/pos.js"></script>
<script>
// Função para abrir o Painel de Filas em uma nova janela
function abrirPainelFilas() {
    // Abrir em nova janela com tamanho específico
    const width = 1024;
    const height = 768;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    window.open(
        'fila_pedidos.php',
        'PainelFilas',
        `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=no,status=no`
    );
}
</script> 