// Variáveis globais
let currentFlow = null;
let flowNodes = [];
let jsPlumbInstance;
let nodeCounter = 0;

// Variáveis de zoom
let currentZoom = 1;
const ZOOM_STEP = 0.1;
const MIN_ZOOM = 0.5;
const MAX_ZOOM = 2;

// Variáveis de pan
let isPanning = false;
let startPoint = { x: 0, y: 0 };
let currentPan = { x: 0, y: 0 };

// Adicionar variável global para controlar estado do fluxo
let isFlowActive = false;

// Inicialização
document.addEventListener("DOMContentLoaded", function () {
  // Verificar se jsPlumb está disponível
  if (typeof window.jsPlumb === "undefined") {
    console.error("jsPlumb não está carregado");
    showError("Erro ao carregar biblioteca necessária (jsPlumb)");
    return;
  }

  loadFlows();
  initializeEditor();
  initializeJsPlumb();
  setupDragAndDrop();
  initializeFlow(); // Carregar fluxo da URL
});

// Carregar fluxos existentes
async function loadFlows() {
  try {
    const flowsList = document.getElementById("flowsList");
    if (!flowsList) {
      console.error("Elemento flowsList não encontrado");
      return;
    }

    const response = await fetch("ajax/get_flows.php");
    const data = await response.json();

    if (data.success) {
      flowsList.innerHTML = "";

      data.flows.forEach((flow) => {
        const item = document.createElement("a");
        item.href = "#";
        item.dataset.flowId = flow.id;
        item.className = "list-group-item list-group-item-action";
        item.addEventListener("click", (e) => {
          e.preventDefault();
          loadFlow(flow.id);
        });

        item.innerHTML = `
          <div class="d-flex justify-content-between align-items-center">
            <span>${flow.name}</span>
            <div>
              <button class="btn btn-sm btn-primary me-1" onclick="editFlow(${
                flow.id
              }, event)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteFlow(${
                flow.id
              }, event)">
                <i class="fas fa-trash"></i>
              </button>
              <span class="badge ${
                flow.active ? "bg-success" : "bg-secondary"
              } rounded-pill ms-2">
                ${flow.active ? "Ativo" : "Inativo"}
              </span>
            </div>
          </div>
        `;
        flowsList.appendChild(item);
      });
    }
  } catch (error) {
    showError(error.message);
  }
}

// Inicializar editor
function initializeEditor() {
  const canvas = document.getElementById("flowCanvas");

  // Adicionar eventos de mouse para pan
  canvas.addEventListener("mousedown", startPan);
  document.addEventListener("mousemove", doPan);
  document.addEventListener("mouseup", endPan);

  // Adicionar evento de roda do mouse para zoom
  canvas.addEventListener("wheel", handleZoom);
}

function startPan(e) {
  if (e.target.closest(".flow-node") || e.target.closest(".zoom-controls")) {
    return;
  }

  isPanning = true;
  startPoint = {
    x: e.clientX - currentPan.x,
    y: e.clientY - currentPan.y,
  };
  e.target.classList.add("grabbing");
}

function doPan(e) {
  if (!isPanning) return;

  currentPan = {
    x: e.clientX - startPoint.x,
    y: e.clientY - startPoint.y,
  };

  applyTransform();
}

function endPan() {
  isPanning = false;
  document.getElementById("flowCanvas").classList.remove("grabbing");
}

function handleZoom(e) {
  e.preventDefault();

  // Calcular o ponto de origem do zoom (posição do mouse)
  const rect = e.target.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  // Determinar direção do zoom
  if (e.deltaY < 0 && currentZoom < MAX_ZOOM) {
    currentZoom += ZOOM_STEP;
  } else if (e.deltaY > 0 && currentZoom > MIN_ZOOM) {
    currentZoom -= ZOOM_STEP;
  }

  // Ajustar o pan para manter o ponto sob o mouse
  currentPan.x = x - x * currentZoom;
  currentPan.y = y - y * currentZoom;

  applyTransform();
}

function applyTransform() {
  const canvas = document.getElementById("flowCanvas");
  canvas.style.transform = `translate(${currentPan.x}px, ${currentPan.y}px) scale(${currentZoom})`;
  jsPlumbInstance.setZoom(currentZoom);
  jsPlumbInstance.repaintEverything();
}

// Criar novo fluxo
function createNewFlow() {
  // Limpar dados do fluxo anterior
  flowNodes = [];
  if (jsPlumbInstance) {
    jsPlumbInstance.reset();
    document.getElementById("flowCanvas").innerHTML = "";
  }

  Swal.fire({
    title: "Novo Fluxo",
    html: `
      <form id="newFlowForm">
        <div class="mb-3">
          <label class="form-label">Nome do Fluxo</label>
          <input type="text" class="form-control" id="newFlowName" required>
        </div>
        <div class="mb-3">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="flowActive" checked>
            <label class="form-check-label" for="flowActive">Ativar Fluxo</label>
          </div>
        </div>
      </form>
    `,
    showCancelButton: true,
    confirmButtonText: "Criar",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      return {
        name: document.getElementById("newFlowName").value,
        active: document.getElementById("flowActive").checked ? 1 : 0,
        nodes: [], // Iniciar com array vazio
        connections: [], // Iniciar com array vazio
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      saveNewFlow(result.value);
    }
  });
}

// Salvar novo fluxo
async function saveNewFlow(flowData) {
  try {
    document.getElementById("flowName").value = flowData.name;

    const response = await fetch("ajax/save_flow.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(flowData),
    });

    const data = await response.json();

    if (data.success) {
      // Atualizar currentFlow com o novo ID
      currentFlow = data.flow_id;
      // Recarregar a lista de fluxos
      loadFlows();
      showSuccess("Fluxo criado com sucesso");
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showError(error.message);
  }
}

// Funções auxiliares
function showSuccess(message) {
  Swal.fire({
    icon: "success",
    title: "Sucesso!",
    text: message,
    showConfirmButton: false,
    timer: 1500,
  });
}

function showError(message) {
  Swal.fire({
    icon: "error",
    title: "Erro!",
    text: message,
  });
}

// Inicializar jsPlumb
function initializeJsPlumb() {
  jsPlumbInstance = jsPlumb.getInstance({
    Connector: ["Flowchart", { cornerRadius: 5 }],
    Anchors: ["Right", "Left"],
    Endpoint: ["Dot", { radius: 5 }],
    EndpointStyle: {
      fill: "white",
      stroke: "var(--primary-color)",
      strokeWidth: 2,
    },
    EndpointHoverStyle: {
      fill: "var(--primary-color)",
      stroke: "var(--primary-color)",
    },
    PaintStyle: {
      stroke: "var(--primary-color)",
      strokeWidth: 2,
    },
    HoverPaintStyle: {
      stroke: "var(--primary-color-dark)",
      strokeWidth: 3,
    },
    ConnectionsDetachable: true,
    ConnectionOverlays: [
      [
        "Arrow",
        {
          location: 1,
          width: 10,
          length: 10,
          foldback: 0.8,
        },
      ],
    ],
    Container: "flowCanvas",
  });

  // Configurar o canvas como container
  jsPlumbInstance.setContainer("flowCanvas");

  // Aguardar o DOM estar pronto
  jsPlumbInstance.ready(function () {
    console.log("jsPlumb está pronto");
  });

  // Adicionar validação antes de permitir conexão
  jsPlumbInstance.bind("beforeDrop", function (info) {
    // Verificar se já existe uma conexão com o mesmo destino
    const connections = jsPlumbInstance.getConnections({
      target: info.targetId,
    });

    if (connections.length > 0) {
      showError("Este nó já possui uma conexão de entrada");
      return false;
    }

    // Verificar se não está tentando conectar ao mesmo nó
    if (info.sourceId === info.targetId) {
      showError("Não é possível conectar um nó a ele mesmo");
      return false;
    }

    // Verificar se são dois nós de mensagem sem espera entre eles
    const sourceNode = flowNodes.find((n) => n.id === info.sourceId);
    const targetNode = flowNodes.find((n) => n.id === info.targetId);

    if (
      sourceNode &&
      targetNode &&
      sourceNode.type !== "wait" &&
      targetNode.type !== "wait" &&
      isMessageNode(sourceNode.type) &&
      isMessageNode(targetNode.type)
    ) {
      showError(
        "É necessário adicionar um bloco de espera entre as mensagens!"
      );
      return false;
    }

    return true;
  });

  // Adicionar eventos para conexões
  jsPlumbInstance.bind("connection", function (info) {
    // Salvar fluxo após criar conexão
    debouncedSave();
  });

  jsPlumbInstance.bind("connectionDetached", function (info) {
    // Salvar fluxo após remover conexão
    debouncedSave();
  });
}

function setupDragAndDrop() {
  const blocks = document.querySelectorAll(".block");
  const canvas = document.getElementById("flowCanvas");

  blocks.forEach((block) => {
    block.addEventListener("dragstart", handleDragStart);
    block.addEventListener("dragend", handleDragEnd);
    block.setAttribute("draggable", "true");
  });

  canvas.addEventListener("dragover", handleDragOver);
  canvas.addEventListener("drop", handleDrop);
}

function handleDragStart(e) {
  e.dataTransfer.setData("text/plain", e.target.dataset.type);
  e.dataTransfer.effectAllowed = "copy";
}

function handleDragOver(e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = "copy";
}

function handleDragEnd(e) {
  e.preventDefault();
}

function handleDrop(e) {
  e.preventDefault();
  if (!isFlowActive) {
    showError("Selecione um fluxo ativo para editar");
    return;
  }
  const type = e.dataTransfer.getData("text/plain");
  const canvasRect = e.target.getBoundingClientRect();
  createNode(type, e.clientX - canvasRect.left, e.clientY - canvasRect.top);
}

function createNode(type, x, y, nodeId = null, data = null) {
  // Se não foi fornecido um ID, criar um novo
  if (!nodeId) {
    nodeId = "node_" + ++nodeCounter;
  } else {
    // Atualizar o contador para evitar IDs duplicados
    const numericId = parseInt(nodeId.split("_")[1]);
    if (numericId > nodeCounter) {
      nodeCounter = numericId;
    }
  }

  // Criar elemento do nó
  const nodeElement = document.createElement("div");
  nodeElement.id = nodeId;
  nodeElement.className = `flow-node node-${type}`;
  // Ajustar posição considerando o zoom atual
  const adjustedX = x / currentZoom;
  const adjustedY = y / currentZoom;
  nodeElement.style.left = `${adjustedX}px`;
  nodeElement.style.top = `${adjustedY}px`;
  nodeElement.innerHTML = getNodeHTML(type, nodeId, data);

  // Adicionar ao canvas
  document.getElementById("flowCanvas").appendChild(nodeElement);

  // Tornar o nó draggable com jsPlumb
  jsPlumbInstance.draggable(nodeId, {
    grid: [10, 10],
    // Atualizar posição ao mover
    stop: function (event) {
      const node = flowNodes.find((n) => n.id === nodeId);
      if (node) {
        const pos = jsPlumbInstance.getOffset(nodeId);
        node.position = {
          x: pos.left * currentZoom,
          y: pos.top * currentZoom,
        };
        // Salvar silenciosamente após mover
        silentSave();
      }
    },
  });

  // Adicionar endpoints
  jsPlumbInstance.addEndpoint(nodeId, {
    anchor: "Right",
    isSource: true,
    paintStyle: {
      fill: "var(--primary-color)",
      stroke: "var(--primary-color)",
    },
    connectorStyle: {
      stroke: "var(--primary-color)",
      strokeWidth: 2,
    },
    maxConnections: -1,
  });
  jsPlumbInstance.addEndpoint(nodeId, {
    anchor: "Left",
    isTarget: true,
    paintStyle: {
      fill: "white",
      stroke: "var(--primary-color)",
      strokeWidth: 2,
    },
    maxConnections: -1,
  });

  // Adicionar à lista de nós
  flowNodes.push({
    id: nodeId,
    type: type,
    data:
      type === "text" ? { message: data?.message || data || "" } : data || {},
    position: {
      x: x * currentZoom,
      y: y * currentZoom,
    },
  });

  return nodeId;
}

function getDefaultDataForType(type) {
  switch (type) {
    case "text":
      return { message: "" };
    case "image":
      return { url: "", caption: "" };
    case "audio":
      return { url: "" };
    case "video":
      return { url: "", caption: "" };
    case "button":
      return { text: "", buttons: [] };
    case "contact":
      return { fullName: "", wuid: "", phoneNumber: "" };
    default:
      return {};
  }
}

function getIconForType(type) {
  const icons = {
    text: "fa-comment",
    image: "fa-image",
    audio: "fa-music",
    video: "fa-video",
    button: "fa-square",
    condition: "fa-code-branch",
    input: "fa-keyboard",
    wait: "fa-clock",
    api: "fa-code",
    webhook: "fa-link",
    variable: "fa-database",
    contact: "fa-address-book",
  };
  return icons[type] || "fa-cube";
}

function getContentForType(type, data) {
  switch (type) {
    case "text":
      const message = typeof data === "object" ? data?.message : data;
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="format-toolbar mb-2">
              <button type="button" class="btn btn-sm btn-light" onclick="formatText(this, '*')" title="Negrito">
                <i class="fas fa-bold"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light" onclick="formatText(this, '_')" title="Itálico">
                <i class="fas fa-italic"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light" onclick="formatText(this, '~')" title="Riscado">
                <i class="fas fa-strikethrough"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light" onclick="showEmojiPicker(this)" title="Emoji">
                <i class="far fa-smile"></i>
              </button>
            </div>
            <textarea 
              class="form-control form-control-sm node-data" 
              rows="3"
              placeholder="Digite sua mensagem"
              onchange="updateTextData(this)"
              >${message || ""}</textarea>
            <div class="format-help small text-muted mt-1">
              <span>*negrito* _itálico_ ~riscado~</span>
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Mensagem:</strong> ${
            formatPreview(message) || "Nenhuma mensagem definida"
          }</p>
        </div>
      `;
    case "image":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">URL</span>
              <input type="url" 
                     class="form-control form-control-sm node-data" 
                     placeholder="URL da imagem"
                     value="${data?.media || ""}"
                     onchange="updateImageData(this, 'media')">
              <button class="btn btn-sm btn-light" 
                      onclick="previewImage(this)" 
                      title="Visualizar imagem">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Legenda</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Legenda da imagem"
                     value="${data?.caption || ""}"
                     onchange="updateImageData(this, 'caption')">
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Imagem:</strong> ${
            data?.media || "Nenhuma imagem definida"
          }</p>
          <p><strong>Legenda:</strong> ${data?.caption || "Sem legenda"}</p>
        </div>
      `;
    case "audio":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">URL</span>
              <input type="url" 
                     class="form-control form-control-sm node-data" 
                     placeholder="URL do áudio"
                     value="${data?.media || ""}"
                     onchange="updateAudioData(this)">
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Áudio:</strong> ${
            data?.media || "Nenhum áudio definido"
          }</p>
        </div>
      `;
    case "video":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">URL</span>
              <input type="url" 
                     class="form-control form-control-sm node-data" 
                     placeholder="URL do vídeo"
                     value="${data?.media || ""}"
                     onchange="updateVideoData(this, 'media')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Legenda</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Legenda do vídeo"
                     value="${data?.caption || ""}"
                     onchange="updateVideoData(this, 'caption')">
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Vídeo:</strong> ${
            data?.media || "Nenhum vídeo definido"
          }</p>
          <p><strong>Legenda:</strong> ${data?.caption || "Sem legenda"}</p>
        </div>
      `;
    case "button":
      return `
        <div class="mb-3">
          <label class="form-label">Texto</label>
          <input type="text" class="form-control node-data" 
                 placeholder="Texto"
                 onchange="updateNodeContent(this)"
                 value="${data?.text || ""}">
        </div>
        <div class="mb-3">
          <label class="form-label">Botões</label>
          <div id="buttonsList">
            ${(data?.buttons || [])
              .map(
                (btn, idx) => `
              <div class="input-group mb-2">
                <input type="text" class="form-control" value="${btn}" data-button-index="${idx}">
                <button class="btn btn-danger" onclick="removeButton(${idx})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            `
              )
              .join("")}
          </div>
          <button class="btn btn-sm btn-primary" onclick="addButton()">
            <i class="fas fa-plus"></i> Adicionar Botão
          </button>
        </div>`;
    case "contact":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Nome do Contato</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Nome do contato"
                     value="${data?.fullName || ""}"
                     onchange="updateContactData(this, 'fullName')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">WhatsApp ID</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="ID do WhatsApp (sem @s.whatsapp.net)"
                     value="${data?.wuid || ""}"
                     onchange="updateContactData(this, 'wuid')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Número do Telefone</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Número do telefone"
                     value="${data?.phoneNumber || ""}"
                     onchange="updateContactData(this, 'phoneNumber')">
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Contato:</strong> ${data?.fullName || "Não definido"}</p>
          <p><strong>WhatsApp ID:</strong> ${data?.wuid || "Não definido"}</p>
          <p><strong>Telefone:</strong> ${
            data?.phoneNumber || "Não definido"
          }</p>
        </div>
      `;
    case "wait":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Tempo (ms)</span>
              <input type="number" 
                     class="form-control form-control-sm node-data" 
                     placeholder="1000"
                     value="${data?.delay || 1000}"
                     onchange="updateWaitData(this, 'delay')">
            </div>
            <small class="text-muted">1000ms = 1 segundo</small>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Tempo de espera:</strong> ${data?.delay || 1000}ms</p>
          <p><small>(${(data?.delay || 1000) / 1000} segundo${
        (data?.delay || 1000) > 1000 ? "s" : ""
      })</small></p>
        </div>`;
    case "narrated":
      return `
        <div class="node-content-form">
            <div class="mb-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">URL</span>
                    <input type="url" 
                           class="form-control form-control-sm node-data" 
                           placeholder="URL do áudio narrado"
                           value="${data?.audio || ""}"
                           onchange="updateNarratedData(this)">
                </div>
            </div>
        </div>
        <div class="node-preview">
            <p><strong>Áudio Narrado:</strong> ${
              data?.audio || "Nenhum áudio definido"
            }</p>
        </div>
    `;
    case "location":
      return `
        <div class="node-content-form">
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Nome</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Nome do local"
                     value="${data?.name || ""}"
                     onchange="updateLocationData(this, 'name')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Endereço</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Endereço completo"
                     value="${data?.address || ""}"
                     onchange="updateLocationData(this, 'address')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Latitude</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Ex: -10.0000"
                     value="${data?.latitude || ""}"
                     onchange="updateLocationData(this, 'latitude')">
            </div>
          </div>
          <div class="mb-2">
            <div class="input-group input-group-sm">
              <span class="input-group-text">Longitude</span>
              <input type="text" 
                     class="form-control form-control-sm node-data" 
                     placeholder="Ex: -20.0000"
                     value="${data?.longitude || ""}"
                     onchange="updateLocationData(this, 'longitude')">
            </div>
          </div>
        </div>
        <div class="node-preview">
          <p><strong>Local:</strong> ${data?.name || "Não definido"}</p>
          <p><strong>Endereço:</strong> ${data?.address || "Não definido"}</p>
          <p><strong>Coordenadas:</strong> ${data?.latitude || "?"}, ${
        data?.longitude || "?"
      }</p>
        </div>
      `;
    default:
      return `<div class="text-muted">Configurar ${type}</div>`;
  }
}

// Função de debounce para evitar múltiplos salvamentos
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Função de salvamento com debounce
const debouncedSave = debounce(() => {
  saveFlow(false).catch((error) => {
    showError("Erro ao salvar alterações: " + error.message);
  });
}, 1000);

// Função para atualizar dados do texto
function updateTextData(textarea) {
  const nodeId = textarea.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {};
    }
    flowNodes[nodeIndex].data = {
      message: textarea.value,
    };

    // Atualizar preview
    const preview = textarea
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Mensagem:</strong> ${
          formatPreview(flowNodes[nodeIndex].data.message) ||
          "Nenhuma mensagem definida"
        }</p>
      `;
    }

    // Usar salvamento com debounce
    debouncedSave();
  }
}

// Atualizar display do nó
function updateNodeDisplay(nodeId, data) {
  const nodeElement = document.getElementById(nodeId);
  if (!nodeElement) return;

  const contentElement = nodeElement.querySelector(".node-content");
  if (!contentElement) return;

  const node = flowNodes.find((n) => n.id === nodeId);
  if (!node) return;

  // Preservar o foco se algum elemento estiver sendo editado
  const focusedElement = document.activeElement;
  const focusPosition = focusedElement?.selectionStart;

  contentElement.innerHTML = getContentForType(node.type, data);

  // Restaurar o foco se necessário
  if (focusedElement && focusedElement.classList.contains("node-data")) {
    const newElement = contentElement.querySelector(".node-data");
    if (newElement) {
      newElement.focus();
      if (focusPosition !== undefined) {
        newElement.selectionStart = focusPosition;
        newElement.selectionEnd = focusPosition;
      }
    }
  }
}

function deleteNode(nodeId) {
  if (!isFlowActive) {
    showError("Não é possível excluir nós em um fluxo inativo");
    return;
  }
  Swal.fire({
    title: "Confirmar exclusão",
    text: "Tem certeza que deseja excluir este bloco?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (result.isConfirmed) {
      // Remover conexões do nó
      jsPlumbInstance.removeAllEndpoints(nodeId);

      // Remover elemento do DOM
      const element = document.getElementById(nodeId);
      if (element) {
        element.remove();
      }

      // Remover do array de nós
      const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);
      if (nodeIndex !== -1) {
        flowNodes.splice(nodeIndex, 1);
      }

      // Salvar alterações
      saveFlow();

      showSuccess("Bloco excluído com sucesso");
    }
  });
}

async function saveFlow(showConfirmation = true) {
  if (!isFlowActive && showConfirmation) {
    showError("Não é possível salvar alterações em um fluxo inativo");
    return;
  }
  try {
    if (!currentFlow) {
      showError("Nenhum fluxo selecionado");
      return;
    }

    // Mostrar confirmação apenas se solicitado (botão salvar)
    if (showConfirmation) {
      const result = await Swal.fire({
        title: "Salvar alterações?",
        text: "Deseja salvar todas as alterações feitas no fluxo?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sim, salvar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#28a745",
      });

      if (!result.isConfirmed) {
        return;
      }
    }

    const flowData = {
      id: currentFlow,
      name: document.getElementById("flowName").value,
      active: document
        .querySelector(`#flowsList a[data-flow-id="${currentFlow}"] .badge`)
        .classList.contains("bg-success"), // Pegar estado atual
      nodes: flowNodes,
      connections: jsPlumbInstance.getAllConnections().map((conn) => ({
        source: conn.source.id,
        target: conn.target.id,
      })),
    };

    const response = await fetch("ajax/update_flow.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(flowData),
    });

    const data = await response.json();

    if (data.success) {
      // Verificar se é um salvamento manual
      if (event && event.type === "click") {
        const toast = document.createElement("div");
        toast.className = "position-fixed top-0 end-0 p-3";
        toast.style.zIndex = "9999";
        toast.innerHTML = `
          <div class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
              <div class="toast-body">
                <i class="fas fa-check me-2"></i> Salvo
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
          </div>
        `;
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast.querySelector(".toast"), {
          delay: 1000,
          animation: true,
        });
        bsToast.show();
        toast.addEventListener("hidden.bs.toast", () => toast.remove());
        loadFlows(); // Recarregar lista após salvar
      }
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showError(error.message);
  }
}

function getNodeData(node) {
  const content = node.querySelector(".node-content");
  const type = node.className.split("node-")[1].split(" ")[0];

  switch (type) {
    case "text":
      return {
        message: content.querySelector("textarea").value,
      };
    case "image":
      return {
        url: content.querySelector("input").value,
      };
    case "condition":
      return {
        variable: content.querySelector("select").value,
        value: content.querySelector("input").value,
      };
    case "input":
      const inputs = content.querySelectorAll("input");
      return {
        question: inputs[0].value,
        variable: inputs[1].value,
      };
    default:
      return {};
  }
}

async function testFlow() {
  if (!currentFlow) {
    showError("Nenhum fluxo selecionado");
    return;
  }

  try {
    // Carregar lista de clientes
    const response = await fetch("ajax/get_clients.php");
    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message);
    }

    // Criar options do select
    const clientOptions = data.clients
      .map(
        (client) =>
          `<option value="${client.telefone}">${client.nome} (${client.telefone})</option>`
      )
      .join("");

    // Mostrar modal de seleção
    Swal.fire({
      title: "Disparar Fluxo",
      html: `
        <div class="mb-3">
          <select class="form-select" id="clientSelect">
            <option value="">Selecione um cliente...</option>
            ${clientOptions}
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Ou digite um número:</label>
          <input type="text" 
                 class="form-control" 
                 id="phoneInput" 
                 placeholder="Ex: 5599999999999">
        </div>
        <div class="mb-3">
          <label class="form-label">Agendamento (opcional):</label>
          <input type="datetime-local" 
                 class="form-control" 
                 id="scheduleInput">
          <small class="text-muted">Deixe em branco para disparo imediato</small>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: "Disparar",
      cancelButtonText: "Cancelar",
      preConfirm: () => {
        const select = document.getElementById("clientSelect");
        const input = document.getElementById("phoneInput");
        const schedule = document.getElementById("scheduleInput");
        const phone = select.value || input.value;

        if (!phone) {
          Swal.showValidationMessage(
            "Selecione um cliente ou digite um número"
          );
          return false;
        }

        return {
          phone,
          scheduled_for: schedule.value || null,
        };
      },
    }).then((result) => {
      if (result.isConfirmed) {
        if (result.value.scheduled_for) {
          scheduleFlow(
            currentFlow,
            result.value.phone,
            result.value.scheduled_for
          );
        } else {
          executeFlow(currentFlow, result.value.phone);
        }
      }
    });
  } catch (error) {
    showError(error.message);
  }
}

// Adicionar função para agendar fluxo
async function scheduleFlow(flowId, phone, scheduledFor) {
  try {
    const response = await fetch("ajax/schedule_flow.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        flow_id: flowId,
        phone: phone,
        scheduled_for: scheduledFor,
      }),
    });

    const data = await response.json();

    if (data.success) {
      showSuccess("Fluxo agendado com sucesso!");
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showError(error.message);
  }
}

// Executar fluxo
async function executeFlow(flowId, phone) {
  try {
    // Verificar se há nós sequenciais sem espera entre eles
    const hasInvalidSequence = checkSequentialNodes();
    if (hasInvalidSequence) {
      throw new Error(
        "É necessário adicionar um bloco de espera entre as mensagens!"
      );
    }

    const response = await fetch("ajax/execute_flow.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        flow_id: flowId,
        phone: phone,
        nodes: flowNodes.map((node) => ({
          id: node.id,
          type: node.type,
          data: node.data,
        })),
        connections: jsPlumbInstance.getAllConnections().map((conn) => ({
          source: conn.source.id,
          target: conn.target.id,
        })),
      }),
    });

    const result = await response.json();
    if (!result.success) {
      throw new Error(result.message);
    }

    showSuccess("Fluxo executado com sucesso!");
    // Atualizar lista de agendamentos se o modal existir e estiver visível
    const schedulesModal = document.getElementById("schedulesModal");
    if (schedulesModal && schedulesModal.classList.contains("show")) {
      loadSchedules();
    }
  } catch (error) {
    console.error("Erro ao executar fluxo:", error);
    showError(error.message);
  }
}

// Função para verificar nós sequenciais
function checkSequentialNodes() {
  // Obter todas as conexões
  const connections = jsPlumbInstance.getAllConnections();

  // Para cada conexão, verificar se há dois nós de mensagem conectados sem espera
  for (let i = 0; i < connections.length; i++) {
    const sourceId = connections[i].sourceId; // Corrigido: era connections[i].source.id
    const targetId = connections[i].targetId; // Corrigido: era connections[i].target.id

    // Encontrar os nós fonte e destino
    const sourceNode = flowNodes.find((n) => n.id === sourceId);
    const targetNode = flowNodes.find((n) => n.id === targetId);

    // Se ambos os nós são de mensagem (não são espera)
    if (
      sourceNode &&
      targetNode &&
      sourceNode.type !== "wait" &&
      targetNode.type !== "wait" &&
      isMessageNode(sourceNode.type) &&
      isMessageNode(targetNode.type)
    ) {
      return true; // Sequência inválida encontrada
    }
  }

  return false; // Nenhuma sequência inválida
}

// Função para verificar se é um nó de mensagem
function isMessageNode(type) {
  const messageTypes = [
    "text",
    "image",
    "audio",
    "video",
    "narrated",
    "location",
    "poll",
    "contact",
    "sticker",
    "button",
  ];
  return messageTypes.includes(type);
}

function editFlow(flowId, event) {
  event.preventDefault();
  event.stopPropagation();

  // Carregar dados do fluxo
  fetch(`ajax/get_flow.php?id=${flowId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const flow = data.flow;
        Swal.fire({
          title: "Editar Fluxo",
          html: `
                        <form id="editFlowForm">
                            <div class="mb-3">
                                <label class="form-label">Nome do Fluxo</label>
                                <input type="text" class="form-control" id="editFlowName" value="${
                                  flow.name
                                }" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="editFlowActive" ${
                                      flow.active == 1 ? "checked" : ""
                                    }>
                                    <label class="form-check-label">Ativo</label>
                                </div>
                            </div>
                        </form>
                    `,
          showCancelButton: true,
          confirmButtonText: "Salvar",
          cancelButtonText: "Cancelar",
          preConfirm: () => {
            return {
              id: flowId,
              name: document.getElementById("editFlowName").value,
              active: document.getElementById("editFlowActive").checked,
            };
          },
        }).then((result) => {
          if (result.isConfirmed) {
            updateFlow(result.value);
            // Se o fluxo atual está sendo editado, atualizar o estado
            if (currentFlow === flowId.toString()) {
              isFlowActive = result.value.active;
              toggleFlowEditing(isFlowActive);
            }
          }
        });
      } else {
        throw new Error(data.message);
      }
    })
    .catch((error) => {
      showError(error.message);
    });
}

async function deleteFlow(flowId, event) {
  event.stopPropagation();

  const result = await Swal.fire({
    title: "Confirmar exclusão",
    text: "Tem certeza que deseja excluir este fluxo?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, excluir",
    cancelButtonText: "Cancelar",
  });

  if (result.isConfirmed) {
    try {
      const response = await fetch("ajax/delete_flow.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: flowId }),
      });

      const data = await response.json();

      if (data.success) {
        showSuccess("Fluxo excluído com sucesso");
        // Redirecionar para a página principal do Chaflow
        window.location.href = "?page=chaflow";
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      showError(error.message);
    }
  }
}

function updateFlow(flowData) {
  fetch("ajax/update_flow.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      id: flowData.id,
      name: flowData.name,
      description: flowData.description,
      active: flowData.active, // Incluir campo active
      nodes: flowNodes,
      connections: jsPlumbInstance.getAllConnections().map((conn) => ({
        source: conn.source.id,
        target: conn.target.id,
      })),
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showSuccess("Fluxo atualizado com sucesso");
        loadFlows(); // Recarregar lista
      } else {
        throw new Error(data.message);
      }
    })
    .catch((error) => {
      showError(error.message);
    });
}

function loadFlow(flowId) {
  // Recarregar a página com o ID do fluxo na URL
  window.location.href = `?page=chaflow&flow=${flowId}`;
}

// Nova função para carregar o fluxo após a página carregar
function initializeFlow() {
  const urlParams = new URLSearchParams(window.location.search);
  const flowId = urlParams.get("flow");

  // Se não houver flowId, apenas retornar
  if (!flowId) return;

  currentFlow = flowId;

  fetch(`ajax/get_flow.php?id=${flowId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const flow = data.flow;
        document.getElementById("flowName").value = flow.name;
        isFlowActive = flow.active;

        // Atualizar visual do badge na lista
        document.querySelectorAll("#flowsList a").forEach((a) => {
          if (a.dataset.flowId === flowId) {
            a.classList.add("active");
            const badge = a.querySelector(".badge");
            if (badge) {
              badge.className = `badge ${
                isFlowActive ? "bg-success" : "bg-secondary"
              } rounded-pill ms-2`;
              badge.textContent = isFlowActive ? "Ativo" : "Inativo";
            }
          }
        });

        // Aplicar estado de edição
        toggleFlowEditing(isFlowActive);

        // Limpar canvas atual
        clearCanvas();

        // Resetar arrays e contadores
        flowNodes = [];
        nodeCounter = 0;

        // Carregar nós
        const nodes = JSON.parse(flow.nodes || "[]");
        nodes.forEach((node) => {
          createNode(
            node.type,
            node.position.x,
            node.position.y,
            node.id,
            node.data
          );
        });

        // Carregar conexões após um pequeno delay
        setTimeout(() => {
          const connections = JSON.parse(flow.connections || "[]");
          connections.forEach((conn) => {
            jsPlumbInstance.connect({
              source: conn.source,
              target: conn.target,
            });
          });
          repositionNodes();
        }, 100);
      } else {
        throw new Error(data.message);
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar fluxo:", error);
      showError(error.message);
      // Em caso de erro, redirecionar para a página principal
      window.location.href = "?page=chaflow";
    });
}

// Função para limpar o canvas
function clearCanvas() {
  // Remover todas as conexões
  jsPlumbInstance.deleteEveryConnection();

  // Remover todos os nós do DOM
  const canvas = document.getElementById("flowCanvas");
  const nodes = canvas.querySelectorAll(".flow-node");
  nodes.forEach((node) => node.remove());

  // Resetar zoom e pan
  currentZoom = 1;
  currentPan = { x: 0, y: 0 };
  applyTransform();
}

// Adicionar função para editar bloco
function editNode(nodeId) {
  if (!isFlowActive) {
    showError("Selecione um fluxo ativo para editar");
    return;
  }
  const node = flowNodes.find((n) => n.id === nodeId);
  if (!node) return;

  let formHtml = "";
  switch (node.type) {
    case "text":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">Mensagem</label>
          <textarea class="form-control" id="nodeMessage" rows="3">${
            node.data.message || ""
          }</textarea>
        </div>`;
      break;
    case "image":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">URL da Imagem</label>
          <div class="input-group">
            <input type="url" 
                   class="form-control" 
                   id="nodeImageUrl" 
                   value="${node.data?.media || ""}"
                   placeholder="https://exemplo.com/imagem.jpg">
            <button class="btn btn-light" 
                    onclick="previewImage(this)" 
                    title="Visualizar imagem">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Legenda</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeImageCaption" 
                 value="${node.data?.caption || ""}"
                 placeholder="Legenda da imagem">
        </div>`;
      break;
    case "audio":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">URL do Áudio</label>
          <input type="url" class="form-control" id="nodeAudioUrl" value="${
            node.data.media || ""
          }"
                 placeholder="https://exemplo.com/audio.mp3">
        </div>`;
      break;
    case "video":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">URL do Vídeo</label>
          <input type="url" class="form-control" id="nodeVideoUrl" value="${
            node.data.media || ""
          }" 
                 placeholder="https://exemplo.com/video.mp4">
        </div>
        <div class="mb-3">
          <label class="form-label">Legenda</label>
          <input type="text" 
                     class="form-control" 
                     id="nodeVideoCaption" 
                     value="${node.data.caption || ""}"
                     placeholder="Legenda do vídeo">
        </div>`;
      break;
    case "button":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">Texto</label>
          <input type="text" class="form-control" id="nodeButtonText" value="${
            node.data.text || ""
          }">
        </div>
        <div class="mb-3">
          <label class="form-label">Botões</label>
          <div id="buttonsList">
            ${(node.data.buttons || [])
              .map(
                (btn, idx) => `
              <div class="input-group mb-2">
                <input type="text" class="form-control" value="${btn}" data-button-index="${idx}">
                <button class="btn btn-danger" onclick="removeButton(${idx})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            `
              )
              .join("")}
          </div>
          <button class="btn btn-sm btn-primary" onclick="addButton()">
            <i class="fas fa-plus"></i> Adicionar Botão
          </button>
        </div>`;
      break;
    case "contact":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">Nome do Contato</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeContactName" 
                 value="${node.data.fullName || ""}"
                 placeholder="Nome do contato">
        </div>
        <div class="mb-3">
          <label class="form-label">WhatsApp ID</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeContactWuid" 
                 value="${node.data.wuid || ""}"
                 placeholder="ID do WhatsApp (sem @s.whatsapp.net)">
        </div>
        <div class="mb-3">
          <label class="form-label">Número do Telefone</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeContactPhone" 
                 value="${node.data.phoneNumber || ""}"
                 placeholder="Número do telefone">
        </div>`;
      break;
    case "location":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">Nome do Local</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeLocationName" 
                 value="${node.data.name || ""}"
                 placeholder="Nome do local">
        </div>
        <div class="mb-3">
          <label class="form-label">Endereço</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeLocationAddress" 
                 value="${node.data.address || ""}"
                 placeholder="Endereço completo">
        </div>
        <div class="mb-3">
          <label class="form-label">Latitude</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeLocationLatitude" 
                 value="${node.data.latitude || ""}"
                 placeholder="Ex: -23.5505">
        </div>
        <div class="mb-3">
          <label class="form-label">Longitude</label>
          <input type="text" 
                 class="form-control" 
                 id="nodeLocationLongitude" 
                 value="${node.data.longitude || ""}"
                 placeholder="Ex: -46.6333">
        </div>`;
      break;
    case "wait":
      formHtml = `
        <div class="mb-3">
          <label class="form-label">Tempo de espera (ms)</label>
          <input type="number" 
                 class="form-control" 
                 id="nodeWaitDelay" 
                 value="${node.data.delay || 1000}"
                 placeholder="1000">
          <small class="text-muted">1000ms = 1 segundo</small>
        </div>`;
      break;
    case "narrated":
      formHtml = `
        <div class="mb-3">
            <label class="form-label">URL do Áudio Narrado</label>
            <div class="input-group">
                <span class="input-group-text">URL</span>
                <input type="url" 
                       class="form-control" 
                       id="nodeNarratedAudio" 
                       value="${node.data?.audio || ""}"
                       placeholder="https://exemplo.com/audio.mp3">
            </div>
        </div>`;
      break;
  }

  Swal.fire({
    title: `Editar ${getNodeIcon(node.type)}`,
    html: formHtml,
    showCancelButton: true,
    confirmButtonText: "Salvar",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      switch (node.type) {
        case "image":
          node.data = node.data || { mediatype: "image" };
          node.data.media = document.getElementById("nodeImageUrl").value;
          node.data.caption = document.getElementById("nodeImageCaption").value;
          break;
        case "text":
          node.data = node.data || {};
          node.data.message = document.getElementById("nodeMessage").value;
          break;
        case "video":
          node.data = node.data || { mediatype: "video" };
          node.data.media = document.getElementById("nodeVideoUrl").value;
          node.data.caption = document.getElementById("nodeVideoCaption").value;
          break;
        case "button":
          node.data = node.data || {};
          node.data.text = document.getElementById("nodeButtonText").value;
          node.data.buttons = Array.from(
            document.querySelectorAll("#buttonsList input")
          ).map((input) => input.value);
          break;
        case "contact":
          node.data = node.data || {};
          node.data.fullName = document.getElementById("nodeContactName").value;
          node.data.wuid = document.getElementById("nodeContactWuid").value;
          node.data.phoneNumber =
            document.getElementById("nodeContactPhone").value;
          break;
        case "location":
          node.data = node.data || {};
          node.data.name = document.getElementById("nodeLocationName").value;
          node.data.address = document.getElementById(
            "nodeLocationAddress"
          ).value;
          node.data.latitude = document.getElementById(
            "nodeLocationLatitude"
          ).value;
          node.data.longitude = document.getElementById(
            "nodeLocationLongitude"
          ).value;
          break;
        case "wait":
          node.data = node.data || {};
          node.data.delay =
            parseInt(document.getElementById("nodeWaitDelay").value) || 1000;
          break;
        case "narrated":
          node.data = {
            audio: document.getElementById("nodeNarratedAudio").value,
            mediatype: "narrated",
          };
          break;
      }
      return true;
    },
  }).then((result) => {
    if (result.isConfirmed) {
      // Atualizar preview
      const nodeElement = document.getElementById(nodeId);
      if (nodeElement) {
        const preview = nodeElement.querySelector(".node-preview");
        if (preview) {
          preview.innerHTML = `
            <p><strong>Imagem:</strong> ${
              node.data?.media || "Nenhuma imagem definida"
            }</p>
            <p><strong>Legenda:</strong> ${
              node.data?.caption || "Sem legenda"
            }</p>
          `;
        }
      }

      // Salvar alterações
      saveFlow().catch((error) => {
        showError("Erro ao salvar alterações: " + error.message);
      });
    }
  });
}

// Atualizar dados do nó
function updateNodeData(nodeId, newData) {
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);
  if (nodeIndex !== -1) {
    flowNodes[nodeIndex].data = newData;
    updateNodeDisplay(nodeId, newData);
    // Salvar silenciosamente após editar
    saveFlow().catch((error) => {
      showError("Erro ao salvar alterações: " + error.message);
    });
  }
}

function getNodeHTML(type, nodeId, data) {
  return `
    <div class="node-header">
      <div class="node-title">
        ${getNodeIcon(type)}
      </div>
      <div class="node-actions">
        <button class="btn btn-primary" onclick="editNode('${nodeId}')">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger" onclick="deleteNode('${nodeId}')">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
    <div class="node-content">
      ${getContentForType(type, data)}
    </div>
  `;
}

function getNodeIcon(type) {
  switch (type) {
    case "text":
      return '<i class="fas fa-comment"></i> Texto';
    case "image":
      return '<i class="fas fa-image"></i> Imagem';
    case "audio":
      return '<i class="fas fa-music"></i> Áudio';
    case "video":
      return '<i class="fas fa-video"></i> Vídeo';
    case "narrated":
      return '<i class="fas fa-microphone"></i> Áudio Narrado';
    case "wait":
      return '<i class="fas fa-clock"></i> Espera';
    case "location":
      return '<i class="fas fa-map-marker-alt"></i> Localização';
    case "contact":
      return '<i class="fas fa-address-book"></i> Contato';
    default:
      return `<i class="fas fa-question"></i> ${type}`;
  }
}

// Funções de zoom
function zoomIn() {
  if (currentZoom < MAX_ZOOM) {
    currentZoom += ZOOM_STEP;
    applyTransform();
  }
}

function zoomOut() {
  if (currentZoom > MIN_ZOOM) {
    currentZoom -= ZOOM_STEP;
    applyTransform();
  }
}

function resetZoom() {
  currentZoom = 1;
  currentPan = { x: 0, y: 0 };
  applyTransform();
}

// Função para download do fluxo
function downloadFlow() {
  if (!currentFlow) {
    showError("Nenhum fluxo selecionado");
    return;
  }

  const flowData = {
    name: document.getElementById("flowName").value,
    nodes: flowNodes,
    connections: jsPlumbInstance.getAllConnections().map((conn) => ({
      source: conn.source.id,
      target: conn.target.id,
    })),
  };

  const blob = new Blob([JSON.stringify(flowData, null, 2)], {
    type: "application/json",
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `${flowData.name || "flow"}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// Função para atualizar dados do vídeo
function updateVideoData(input, field) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {
        mediatype: "video",
        fileName: "video.mp4",
      };
    }
    flowNodes[nodeIndex].data[field] = input.value;

    // Se o campo for media, atualizar o fileName
    if (field === "media") {
      try {
        const url = new URL(input.value);
        const fileName = url.pathname.split("/").pop();
        flowNodes[nodeIndex].data.fileName = fileName || "video.mp4";
      } catch (e) {
        flowNodes[nodeIndex].data.fileName = "video.mp4";
      }
    }

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Vídeo:</strong> ${
          flowNodes[nodeIndex].data.media || "Nenhum vídeo definido"
        }</p>
        <p><strong>Legenda:</strong> ${
          flowNodes[nodeIndex].data.caption || "Sem legenda"
        }</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Função para atualizar dados do áudio
function updateAudioData(input) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = { mediatype: "audio" };
    }
    flowNodes[nodeIndex].data.media = input.value;

    // Extrair nome do arquivo
    try {
      const url = new URL(input.value);
      const fileName = url.pathname.split("/").pop();
      flowNodes[nodeIndex].data.fileName = fileName || "audio.mp3";
    } catch (e) {
      flowNodes[nodeIndex].data.fileName = "audio.mp3";
    }

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Áudio:</strong> ${
          flowNodes[nodeIndex].data.media || "Nenhum áudio definido"
        }</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Função para atualizar dados do áudio narrado
function updateNarratedData(input) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {};
    }
    // Atualizar para usar o campo 'audio' consistentemente
    flowNodes[nodeIndex].data = {
      audio: input.value,
      mediatype: "narrated", // Adicionar tipo de mídia
    };

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Áudio Narrado:</strong> ${
          flowNodes[nodeIndex].data.audio || "Nenhum áudio definido"
        }</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Função para formatar texto
function formatText(button, marker) {
  const textarea = button
    .closest(".node-content-form")
    .querySelector("textarea");
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const text = textarea.value;

  if (start === end) return; // Nada selecionado

  const selectedText = text.substring(start, end);
  const newText =
    text.substring(0, start) +
    marker +
    selectedText +
    marker +
    text.substring(end);

  textarea.value = newText;
  updateTextData(textarea);
}

// Função para mostrar emoji picker
function showEmojiPicker(button) {
  const existingPicker = document.querySelector(".emoji-picker");
  if (existingPicker) {
    existingPicker.remove();
    return;
  }

  const emojis = [
    "😀",
    "😂",
    "😊",
    "😍",
    "🥰",
    "😎",
    "🤔",
    "😴",
    "👍",
    "👎",
    "❤️",
    "💔",
    "🎉",
    "✨",
    "🌟",
    "💡",
    "⚡",
    "💪",
  ];
  const picker = document.createElement("div");
  picker.className = "emoji-picker";

  emojis.forEach((emoji) => {
    const span = document.createElement("span");
    span.textContent = emoji;
    span.onclick = () => insertEmoji(emoji, button);
    picker.appendChild(span);
  });

  button.parentNode.appendChild(picker);

  // Fechar ao clicar fora
  document.addEventListener("click", (e) => {
    if (!picker.contains(e.target) && e.target !== button) {
      picker.remove();
    }
  });
}

// Função para inserir emoji
function insertEmoji(emoji, button) {
  const textarea = button
    .closest(".node-content-form")
    .querySelector("textarea");
  const pos = textarea.selectionStart;
  const text = textarea.value;

  textarea.value = text.substring(0, pos) + emoji + text.substring(pos);
  updateTextData(textarea);
}

// Função para formatar preview
function formatPreview(text) {
  // Verificar se text é uma string válida
  if (typeof text !== "string") {
    return text?.toString() || "";
  }

  // Formatar negrito
  text = text.replace(/\*(.*?)\*/g, "<strong>$1</strong>");
  // Formatar itálico
  text = text.replace(/_(.*?)_/g, "<em>$1</em>");
  // Formatar riscado
  text = text.replace(/~(.*?)~/g, "<del>$1</del>");

  return text;
}

// Função para atualizar dados da imagem
function updateImageData(input, field) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = { mediatype: "image" };
    }
    flowNodes[nodeIndex].data[field] = input.value;

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Imagem:</strong> ${
          flowNodes[nodeIndex].data.media || "Nenhuma imagem definida"
        }</p>
        <p><strong>Legenda:</strong> ${
          flowNodes[nodeIndex].data.caption || "Sem legenda"
        }</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

function updateContactData(input, field) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {};
    }
    flowNodes[nodeIndex].data[field] = input.value;

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Contato:</strong> ${
          flowNodes[nodeIndex].data.fullName || "Não definido"
        }</p>
        <p><strong>WhatsApp ID:</strong> ${
          flowNodes[nodeIndex].data.wuid || "Não definido"
        }</p>
        <p><strong>Telefone:</strong> ${
          flowNodes[nodeIndex].data.phoneNumber || "Não definido"
        }</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Função para visualizar imagem
function previewImage(button) {
  const input = button.closest(".input-group").querySelector("input");
  const imageUrl = input.value;

  if (!imageUrl) {
    showError("URL da imagem não definida");
    return;
  }

  Swal.fire({
    title: "Preview da Imagem",
    html: `
      <div class="text-center">
        <img src="${imageUrl}" 
             class="img-fluid" 
             style="max-height: 70vh; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
             onerror="this.onerror=null; this.src='assets/img/image-error.png';">
      </div>
    `,
    width: "auto",
    showCloseButton: true,
    showConfirmButton: false,
    heightAuto: true,
  });
}

// Adicionar função para reposicionar nós após carregar
function repositionNodes() {
  flowNodes.forEach((node) => {
    const element = document.getElementById(node.id);
    if (element && node.position) {
      const adjustedX = node.position.x / currentZoom;
      const adjustedY = node.position.y / currentZoom;
      element.style.left = `${adjustedX}px`;
      element.style.top = `${adjustedY}px`;
      jsPlumbInstance.revalidate(node.id);
    }
  });
}

// Função para mostrar modal de agendamentos
function showSchedules() {
  loadSchedules().then((schedules) => {
    Swal.fire({
      title: "Agendamentos",
      html: `
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Fluxo</th>
                                <th>Telefone</th>
                                <th>Data/Hora</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${schedules
                              .map(
                                (schedule) => `
                                <tr>
                                    <td>${schedule.flow_name}</td>
                                    <td>${schedule.phone}</td>
                                    <td>${formatDateTime(
                                      schedule.scheduled_for
                                    )}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-1" 
                                                onclick="editSchedule(${
                                                  schedule.id
                                                }, '${schedule.phone}', '${
                                  schedule.scheduled_for
                                }')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="cancelSchedule(${
                                                  schedule.id
                                                })">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            `
                              )
                              .join("")}
                            ${
                              schedules.length === 0
                                ? '<tr><td colspan="4" class="text-center">Nenhum agendamento pendente</td></tr>'
                                : ""
                            }
                        </tbody>
                    </table>
                </div>
            `,
      width: "800px",
      showConfirmButton: false,
      showCloseButton: true,
    });
  });
}

// Função para carregar agendamentos
async function loadSchedules() {
  try {
    const response = await fetch("ajax/get_schedules.php");
    const data = await response.json();

    if (data.success) {
      return data.schedules;
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    showError(error.message);
    return [];
  }
}

// Função para formatar data/hora
function formatDateTime(datetime) {
  return new Date(datetime).toLocaleString("pt-BR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Função para cancelar agendamento
async function cancelSchedule(scheduleId) {
  const result = await Swal.fire({
    title: "Confirmar cancelamento",
    text: "Tem certeza que deseja cancelar este agendamento?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, cancelar",
    cancelButtonText: "Não",
  });

  if (result.isConfirmed) {
    try {
      const response = await fetch("ajax/cancel_schedule.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          schedule_id: scheduleId,
        }),
      });

      const data = await response.json();

      if (data.success) {
        showSuccess("Agendamento cancelado com sucesso");
        showSchedules(); // Recarregar lista
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      showError(error.message);
    }
  }
}

// Função para editar agendamento
function editSchedule(scheduleId, phone, scheduledFor) {
  Swal.fire({
    title: "Editar Agendamento",
    html: `
            <div class="mb-3">
                <label class="form-label">Telefone</label>
                <input type="text" 
                       class="form-control" 
                       id="editPhone" 
                       value="${phone}">
            </div>
            <div class="mb-3">
                <label class="form-label">Data/Hora</label>
                <input type="datetime-local" 
                       class="form-control" 
                       id="editSchedule" 
                       value="${scheduledFor.replace(" ", "T")}">
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: "Salvar",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      return {
        phone: document.getElementById("editPhone").value,
        scheduled_for: document.getElementById("editSchedule").value,
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      updateSchedule(scheduleId, result.value);
    }
  });
}

// Função para atualizar agendamento
async function updateSchedule(scheduleId, data) {
  try {
    const response = await fetch("ajax/update_schedule.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        schedule_id: scheduleId,
        phone: data.phone,
        scheduled_for: data.scheduled_for,
      }),
    });

    const responseData = await response.json();

    if (responseData.success) {
      showSuccess("Agendamento atualizado com sucesso");
      showSchedules(); // Recarregar lista
    } else {
      throw new Error(responseData.message);
    }
  } catch (error) {
    showError(error.message);
  }
}

function updateLocationData(input, field) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {};
    }
    flowNodes[nodeIndex].data[field] = input.value;

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      preview.innerHTML = `
        <p><strong>Local:</strong> ${
          flowNodes[nodeIndex].data.name || "Não definido"
        }</p>
        <p><strong>Endereço:</strong> ${
          flowNodes[nodeIndex].data.address || "Não definido"
        }</p>
        <p><strong>Coordenadas:</strong> ${
          flowNodes[nodeIndex].data.latitude || "?"
        }, ${flowNodes[nodeIndex].data.longitude || "?"}</p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Função de salvamento silencioso (sem confirmação)
async function silentSave() {
  try {
    if (!currentFlow) {
      showError("Nenhum fluxo selecionado");
      return;
    }

    const flowData = {
      id: currentFlow,
      name: document.getElementById("flowName").value,
      nodes: flowNodes,
      connections: jsPlumbInstance.getAllConnections().map((conn) => ({
        source: conn.source.id,
        target: conn.target.id,
      })),
    };

    const response = await fetch("ajax/update_flow.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(flowData),
    });

    const data = await response.json();
    if (!data.success) throw new Error(data.message);
  } catch (error) {
    console.error("Erro ao salvar:", error);
    showError(error.message);
  }
}

function updateWaitData(input, field) {
  const nodeId = input.closest(".flow-node").id;
  const nodeIndex = flowNodes.findIndex((n) => n.id === nodeId);

  if (nodeIndex !== -1) {
    if (!flowNodes[nodeIndex].data) {
      flowNodes[nodeIndex].data = {};
    }
    flowNodes[nodeIndex].data[field] = parseInt(input.value) || 1000;

    // Atualizar preview
    const preview = input
      .closest(".node-content")
      .querySelector(".node-preview");
    if (preview) {
      const delay = flowNodes[nodeIndex].data.delay || 1000;
      preview.innerHTML = `
        <p><strong>Tempo de espera:</strong> ${delay}ms</p>
        <p><small>(${delay / 1000} segundo${
        delay > 1000 ? "s" : ""
      })</small></p>
      `;
    }

    // Salvar silenciosamente
    silentSave();
  }
}

// Nova função para controlar a edição do fluxo
function toggleFlowEditing(enabled) {
  // Desabilitar/habilitar drag and drop
  const blocks = document.querySelectorAll(".block");
  blocks.forEach((block) => {
    block.draggable = enabled;
    block.style.opacity = enabled ? "1" : "0.5";
    block.style.cursor = enabled ? "move" : "not-allowed";
  });

  // Desabilitar/habilitar botões de edição nos nós
  const nodeButtons = document.querySelectorAll(".flow-node .btn");
  nodeButtons.forEach((btn) => {
    btn.disabled = !enabled;
    btn.style.opacity = enabled ? "1" : "0.5";

    // Remover e reativar eventos de clique
    const action = btn.getAttribute("onclick");
    if (action) {
      btn.removeAttribute("onclick");
      if (enabled) {
        btn.setAttribute("onclick", action);
      }
    }
  });

  // Desabilitar/habilitar movimento dos nós e conexões
  flowNodes.forEach((node) => {
    const nodeElement = document.getElementById(node.id);
    if (nodeElement) {
      // Remover todos os endpoints e conexões existentes
      jsPlumbInstance.removeAllEndpoints(nodeElement);

      if (enabled) {
        // Recriar endpoints
        jsPlumbInstance.addEndpoint(nodeElement, {
          anchor: "Right",
          isSource: true,
          paintStyle: {
            fill: "var(--primary-color)",
            stroke: "var(--primary-color)",
          },
          connectorStyle: {
            stroke: "var(--primary-color)",
            strokeWidth: 2,
          },
          maxConnections: -1,
        });

        jsPlumbInstance.addEndpoint(nodeElement, {
          anchor: "Left",
          isTarget: true,
          paintStyle: {
            fill: "white",
            stroke: "var(--primary-color)",
            strokeWidth: 2,
          },
          maxConnections: -1,
        });

        // Reativar dragging
        jsPlumbInstance.draggable(nodeElement, {
          grid: [10, 10],
          stop: function (event) {
            const node = flowNodes.find((n) => n.id === nodeElement.id);
            if (node) {
              const pos = jsPlumbInstance.getOffset(nodeElement.id);
              node.position = {
                x: pos.left * currentZoom,
                y: pos.top * currentZoom,
              };
              silentSave();
            }
          },
        });
      } else {
        jsPlumbInstance.setDraggable(nodeElement, false);
      }
    }
  });

  // Recriar conexões se estiver habilitado
  if (enabled) {
    const connections = jsPlumbInstance.getAllConnections();
    const connectionsData = connections.map((conn) => ({
      source: conn.source.id,
      target: conn.target.id,
    }));

    // Limpar todas as conexões
    jsPlumbInstance.deleteEveryConnection();

    // Recriar conexões
    setTimeout(() => {
      connectionsData.forEach((conn) => {
        jsPlumbInstance.connect({
          source: conn.source,
          target: conn.target,
        });
      });
    }, 100);
  }

  // Desabilitar/habilitar campos de entrada
  const inputs = document.querySelectorAll(
    ".flow-node input, .flow-node textarea"
  );
  inputs.forEach((input) => {
    input.disabled = !enabled;
    input.style.opacity = enabled ? "1" : "0.7";
  });

  // Atualizar estilo do canvas
  const canvas = document.getElementById("flowCanvas");
  canvas.style.opacity = enabled ? "1" : "0.7";
  canvas.style.pointerEvents = enabled ? "auto" : "none";

  // Desabilitar/habilitar botões principais
  const actionButtons = document.querySelectorAll(
    ".card-header .btn:not(.btn-info)"
  );
  actionButtons.forEach((btn) => {
    if (!btn.classList.contains("btn-info")) {
      btn.disabled = !enabled;
      btn.style.opacity = enabled ? "1" : "0.5";
    }
  });

  // Recriar eventos de edição e exclusão para todos os nós
  if (enabled) {
    document.querySelectorAll(".flow-node").forEach((node) => {
      const editBtn = node.querySelector(".btn-primary");
      const deleteBtn = node.querySelector(".btn-danger");

      if (editBtn) {
        editBtn.onclick = () => editNode(node.id);
      }
      if (deleteBtn) {
        deleteBtn.onclick = () => deleteNode(node.id);
      }
    });
  }
}
