<?php
// File Hash: b8715adb675e64fd43a982c0fe8a5cf8
$stmt = $conn->prepare("SELECT * FROM evolution_settings WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$evolution_settings = $stmt->get_result()->fetch_assoc();

if (!$evolution_settings) {
    echo '<div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            Configure primeiro a Evolution API na aba Evolution para usar o Chaflow.
          </div>';
    exit;
}

?>

<div class="chaflow-container">
    <div class="row">
        <!-- Painel Lateral - Blocos Disponíveis -->
        <div class="col-md-3">
            <!-- Lista de Fluxos -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title2 mb-0">Meus Fluxos</h5>
                        <button class="btn btn-sm btn-success" onclick="createNewFlow()">
                            <i class="fas fa-plus"></i> Novo
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="flowsList">
                        <!-- Lista de fluxos será carregada via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Blocos Disponíveis -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title2 mb-0">Blocos</h5>
                </div>
                <div class="card-body">
                    <div class="blocks-container">
                        <!-- Mensagens -->
                        <div class="block-group">
                            <h6>Mensagens</h6>
                            <div class="block" data-type="text">
                                <i class="fas fa-comment"></i> Texto
                            </div>
                            <div class="block" data-type="image">
                                <i class="fas fa-image"></i> Imagem
                            </div>
                            <div class="block" data-type="video">
                                <i class="fas fa-video"></i> Vídeo
                            </div>
                            <div class="block" data-type="audio">
                                <i class="fas fa-music"></i> Áudio
                            </div>
                            <div class="block" data-type="narrated">
                                <i class="fas fa-microphone"></i> Áudio Narrado
                            </div>
                        
                        </div>

                        <!-- Controles -->
                        <div class="block-group">
                            <h6>Controles</h6>
                            <div class="block" data-type="wait">
                                <i class="fas fa-clock"></i> Espera
                            </div>
                        </div>
                        <!-- Recursos -->
                        <div class="block-group">
                            <h6>Recursos</h6>
                            <div class="block" data-type="location">
                                <i class="fas fa-map-marker-alt"></i> Localização
                            </div>
                            <div class="block" data-type="contact">
                                <i class="fas fa-address-book"></i> Contato
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área do Fluxo -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <input type="text" id="flowName" class="form-control" placeholder="Nome do Fluxo">
                    </div>
                    <div class="btn-group">
                        <!-- <button class="btn btn-primary" onclick="createNewFlow()">
                            <i class="fas fa-plus"></i> Novo
                        </button> -->
                        <button class="btn btn-success" onclick="saveFlow()">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                        <button class="btn btn-primary" onclick="testFlow()">
                            <i class="fas fa-play"></i> Disparar
                        </button>
                        <button class="btn btn-info" onclick="showSchedules()">
                            <i class="fas fa-calendar"></i> Agendamentos
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="canvas-container">
                        <!-- Controles de Zoom -->
                        <div class="zoom-controls">
                            <button class="zoom-btn" onclick="zoomIn()">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="zoom-btn" onclick="zoomOut()">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button class="zoom-btn" onclick="resetZoom()">
                                <i class="fas fa-expand"></i>
                            </button>
                            <button class="zoom-btn" onclick="downloadFlow()">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                        <div id="flowCanvas" class="flow-canvas">
                            <!-- Área onde os blocos serão arrastados -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos do Container */
.chaflow-container {
    padding: 20px;
    background: #f8f9fa;
    min-height: calc(100vh - 100px);
}

/* Estilos dos Blocos */
.blocks-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.block-group {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.block-group h6 {
    margin-bottom: 10px;
    color: #495057;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.block {
    background: white;
    border: 1px solid #e9ecef;
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    cursor: move;
    transition: all 0.2s;
    user-select: none;
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.block:hover {
    background: #f8f9fa;
    border-color: var(--primary-color);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.block i {
    margin-right: 8px;
    color: var(--primary-color);
    width: 20px;
    text-align: center;
}

/* Área do Fluxo */
.flow-canvas {
    min-height: 700px;
    min-width: 1000px;
    /* background: #ffffff;*/
    /* border: 1px solid #e2e8f0;*/
    /* border-radius: 8px;*/
    position: relative;
    overflow: hidden;
    /* box-shadow: 0 0 20px rgba(0,0,0,0.05);*/
    /* background-image: radial-gradient(#e9ecef 1px, transparent 1px);*/
    background-size: 20px 20px;
    transform-origin: 0 0;
    transition: transform 0.1s;
    cursor: grab;
    margin: 20px;
}

.flow-canvas.grabbing {
    cursor: grabbing;
}

/* Container do Canvas */
.canvas-container {
    position: relative;
    width: 100%;
    height: calc(100vh - 200px);
    background: #f8f9fa;
    background-image: radial-gradient(#e9ecef 1px, transparent 1px);
    background-size: 20px 20px;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

#flowCanvas {
    width: 300%;
    height: 300%;
    position: relative;
    transform-origin: 0 0;
    transition: transform 0.1s;
}

/* Nós do Fluxo */
.flow-node {
    position: absolute;
    background: white;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    min-width: 200px;
    max-width: 300px;
    cursor: move;
    user-select: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1;
}

/* Botões de ação do nó */
.flow-node .node-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 8px;
    margin-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.flow-node .node-header .node-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #495057;
}

.flow-node .node-header .node-actions {
    display: flex;
    gap: 4px;
}

.flow-node .node-header .btn {
    padding: 2px 4px;
    font-size: 0.7rem;
    line-height: 1;
    height: 20px;
    width: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flow-node .node-header .btn i {
    font-size: 0.7rem;
    margin: 0;
}

.flow-node:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.flow-node .node-content {
    min-height: 50px;
    max-height: 500px;
    overflow-y: auto;
    scrollbar-width: thin;
}

.flow-node .node-content::-webkit-scrollbar {
    width: 6px;
}

.flow-node .node-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.flow-node .node-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.flow-node .node-footer {
    padding-top: 10px;
    border-top: 1px solid #eee;
    margin-top: 10px;
    text-align: right;
}

/* Pontos de Conexão */
.endpoint {
    width: 12px;
    height: 12px;
    background: var(--primary-color);
    border-radius: 50%;
}

/* Estilos específicos por tipo */
.node-text { border-color: var(--primary-color); }
.node-image { border-color: #28a745; }
.node-audio { border-color: #17a2b8; }
.node-condition { border-color: #ffc107; }
.node-input { border-color: #6f42c1; }
.node-action { border-color: #dc3545; }

/* Controles de Zoom */
.zoom-controls {
    position: fixed;
    right: 20px;
    top: 100px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    padding: 5px;
    gap: 5px;
}

.zoom-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    transition: all 0.2s;
}

.zoom-btn:hover {
    background: #f8f9fa;
    color: var(--primary-color);
}

.zoom-btn:active {
    transform: scale(0.95);
}

/* Estilos para inputs dentro dos nós */
.node-content-form {
    margin-bottom: 10px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.node-content-form .input-group-text {
    background: #fff;
    font-size: 0.8rem;
    min-width: 60px;
}

.node-content-form .form-control-sm {
    font-size: 0.8rem;
}

.node-preview {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #eee;
    font-size: 0.8rem;
    max-height: 70px;
    /* overflow-y: auto;*/
    scrollbar-width: thin;
}

.node-preview::-webkit-scrollbar {
    width: 6px;
}

.node-preview::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.node-preview::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

/* Toolbar de formatação */
.format-toolbar {
    display: flex;
    gap: 5px;
    padding: 5px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.format-toolbar .btn {
    padding: 2px 8px;
    font-size: 12px;
}

.format-toolbar .btn:hover {
    background: #e9ecef;
}

/* Emoji picker */
.emoji-picker {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 8px;
    max-height: 200px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 5px;
}

.emoji-picker span {
    cursor: pointer;
    padding: 5px;
    text-align: center;
    border-radius: 4px;
}

.emoji-picker span:hover {
    background: #f8f9fa;
}

/* Estilos para endpoints */
.jtk-endpoint {
    z-index: 2;
    cursor: crosshair !important;
}

/* Estilo para quando estiver arrastando um conector */
.jtk-drag-select * {
    cursor: crosshair !important;
}

.jtk-connector-outline {
    stroke: var(--primary-color);
    stroke-width: 2;
    stroke-dasharray: 2, 2;
}

/* Estilo para endpoint quando hover */
.jtk-endpoint:hover {
    transform: scale(1.2);
    transition: transform 0.2s;
}

/* Estilo para endpoint de origem */
.jtk-endpoint-source {
    fill: var(--primary-color);
    stroke: var(--primary-color);
    stroke-width: 1;
}

/* Estilo para endpoint de destino */
.jtk-endpoint-target {
    fill: white;
    stroke: var(--primary-color);
    stroke-width: 2;
}

.jtk-connector {
    z-index: 1;
    cursor: pointer;
}

/* Estilos para conexões */
.jtk-connector path {
    stroke: var(--primary-color);
    stroke-width: 2;
    transition: stroke 0.3s;
}

.jtk-connector:hover path {
    stroke: var(--primary-color-dark);
    stroke-width: 3;
}

/* Ajustar textarea dentro dos nós */
.node-content-form textarea.form-control {
    max-height: 100px;
    min-height: 60px;
    font-size: 0.9rem;
    resize: vertical;
}
</style>

<!-- Incluir jsPlumb -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsPlumb/2.15.6/js/jsplumb.js"></script>
<!-- Ou use a versão Toolkit se tiver licença -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jsplumbtoolkit/2.15.6/js/jsplumbtoolkit.min.js"></script> --> 

<script>
// Verificar agendamentos a cada minuto
setInterval(async function() {
    try {
        await fetch('ajax/check_schedules.php');
    } catch (error) {
        console.error('Erro ao verificar agendamentos:', error);
    }
}, 30000); // 30000 ms = 30 segundos
</script> 