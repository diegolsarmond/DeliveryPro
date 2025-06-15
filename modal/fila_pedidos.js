document.addEventListener("DOMContentLoaded", function () {
  // Atualizar pedidos a cada 5 segundos
  atualizarPedidos();
  setInterval(atualizarPedidos, 5000);
});

function atualizarPedidos() {
  fetch("ajax/buscar_fila_pedidos.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        renderizarPedidos(data.pedidos);
      }
    })
    .catch((error) => console.error("Erro:", error));
}

function renderizarPedidos(pedidos) {
  // Limpar todas as seções
  document.getElementById("preparo-grid").innerHTML = "";
  document.getElementById("finalizando-grid").innerHTML = "";
  document.getElementById("finalizados-grid").innerHTML = "";

  pedidos.forEach((pedido) => {
    const card = criarCardPedido(pedido);
    let targetGrid;

    switch (pedido.status) {
      case "Em Preparo":
        targetGrid = "preparo-grid";
        break;
      case "Pronto para Entrega":
        targetGrid = "finalizando-grid";
        break;
      case "Entregue":
        targetGrid = "finalizados-grid";
        break;
    }

    if (targetGrid) {
      document.getElementById(targetGrid).appendChild(card);
    }
  });
}

function criarCardPedido(pedido) {
  const div = document.createElement("div");
  let statusClass = "";

  switch (pedido.status) {
    case "Em Preparo":
      statusClass = "em-preparo";
      break;
    case "Pronto para Entrega":
      statusClass = "finalizando";
      break;
    case "Entregue":
      statusClass = "finalizado";
      break;
  }

  div.className = `pedido-card ${statusClass}`;
  div.innerHTML = `
    <div class="pedido-label">Pedido</div>
    <div class="pedido-numero">${pedido.pedido.padStart(4, "0")}</div>
  `;

  return div;
}

function formatarItens(itens) {
  return itens
    .split(",")
    .map((item) => `• ${item.trim()}`)
    .join("<br>");
}
