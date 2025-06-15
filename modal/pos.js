document.addEventListener("DOMContentLoaded", function () {
  loadProducts();
  loadMesas();
  setupEventListeners();
});

let cart = [];
let selectedMesa = null;
let taxa = 0;
let desconto = 0;

function loadProducts() {
  fetch("ajax/get_products.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Agrupar produtos por categoria
        const productsByCategory = data.products.reduce((acc, product) => {
          if (!acc[product.categoria_id]) {
            acc[product.categoria_id] = {
              nome: product.categoria_nome,
              produtos: [],
            };
          }
          acc[product.categoria_id].produtos.push(product);
          return acc;
        }, {});

        displayProducts(productsByCategory);
      } else {
        throw new Error(data.message);
      }
    })
    .catch((error) => {
      console.error("Erro ao carregar produtos:", error);
      Swal.fire({
        icon: "error",
        title: "Erro!",
        text: "Não foi possível carregar os produtos",
      });
    });
}

function displayProducts(productsByCategory) {
  const grid = document.getElementById("productsGrid");
  grid.innerHTML = "";

  // Para cada categoria
  Object.entries(productsByCategory).forEach(([categoriaId, categoria]) => {
    // Criar cabeçalho da categoria
    const categoryHeader = document.createElement("div");
    categoryHeader.className = "col-12 mb-3";
    categoryHeader.innerHTML = `<h5 class="categoria-titulo">${categoria.nome}</h5>`;
    grid.appendChild(categoryHeader);

    // Criar cards dos produtos
    categoria.produtos.forEach((product) => {
      const card = document.createElement("div");
      card.className = "col-md-4 col-lg-3";
      card.innerHTML = `
        <div class="card product-card" onclick="addToCart(${product.id}, '${
        product.item
      }', ${product.valor})">
          <div class="card-body">
            <h6 class="card-title3">${product.item}</h6>
            <p class="card-text">R$ ${product.valor.toFixed(2)}</p>
          </div>
        </div>
      `;
      grid.appendChild(card);
    });
  });
}

function addToCart(id, name, price) {
  const existingItem = cart.find((item) => item.id === id);

  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.push({
      id: id,
      name: name,
      price: price,
      quantity: 1,
    });
  }

  updateCartDisplay();
}

function updateCartDisplay() {
  const cartDiv = document.getElementById("cartItems");
  cartDiv.innerHTML = "";

  cart.forEach((item) => {
    const itemDiv = document.createElement("div");
    itemDiv.className = "cart-item";
    itemDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span>${item.name}</span>
                    <br>
                    <small>R$ ${item.price.toFixed(2)} x ${
      item.quantity
    }</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${
                      item.id
                    })">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        `;
    cartDiv.appendChild(itemDiv);
  });

  updateCartTotal();
}

function removeFromCart(id) {
  const index = cart.findIndex((item) => item.id === id);
  if (index > -1) {
    if (cart[index].quantity > 1) {
      cart[index].quantity--;
    } else {
      cart.splice(index, 1);
    }
    updateCartDisplay();
  }
}

function updateCartTotal() {
  // Calcular subtotal dos itens
  const subtotal = cart.reduce(
    (total, item) => total + item.price * item.quantity,
    0
  );

  // Atualizar subtotal
  document.getElementById("subtotal").textContent = `R$ ${subtotal.toFixed(2)}`;

  // Calcular total com taxa e desconto
  const total = subtotal + taxa - desconto;
  document.getElementById("total").textContent = `R$ ${total.toFixed(2)}`;
}

function limparCarrinho() {
  // Limpar array do carrinho
  cart = [];

  // Limpar taxa e desconto
  taxa = 0;
  desconto = 0;

  // Limpar exibição do carrinho
  document.getElementById("cartItems").innerHTML = "";

  // Limpar totais
  document.getElementById("subtotal").textContent = "R$ 0,00";
  document.getElementById("total").textContent = "R$ 0,00";

  // Zerar taxa e desconto (sem ocultar)
  document.getElementById("taxaValue").textContent = "R$ 0,00";
  document.getElementById("descontoValue").textContent = "R$ 0,00";

  // Remover seleção da mesa
  if (selectedMesa) {
    document.querySelectorAll(".mesa-item.selected").forEach((el) => {
      el.classList.remove("selected");
    });
    selectedMesa = null;
  }

  // Feedback visual
  Swal.fire({
    icon: "success",
    title: "Carrinho Limpo!",
    text: "Todos os itens foram removidos",
    timer: 1500,
    showConfirmButton: false,
  });
}

function finalizarVenda() {
  if (cart.length === 0) {
    Swal.fire({
      icon: "warning",
      title: "Carrinho Vazio",
      text: "Adicione produtos antes de finalizar a venda",
    });
    return;
  }

  Swal.fire({
    title: "Finalizar Venda",
    html: `
      <div class="form-group mb-3">
        <label><i class="fas fa-user"></i> Nome do Cliente (Opcional)</label>
        <input id="customerName" class="form-control" placeholder="Nome do cliente">
      </div>
      <div class="form-group mb-3">
        <label><i class="fas fa-phone"></i> Telefone (Opcional)</label>
        <input id="customerPhone" class="form-control" placeholder="Telefone do cliente">
      </div>
      <div class="form-group">
        <label><i class="fas fa-money-bill"></i> Forma de Pagamento</label>
        <select id="paymentMethod" class="form-control" required>
          <option value="Pendente de Pagamento">Pendente de Pagamento</option>
          <option value="Dinheiro">Dinheiro</option>
          <option value="Cartão de Crédito">Cartão de Crédito</option>
          <option value="Cartão de Débito">Cartão de Débito</option>
          <option value="PIX">PIX</option>
        </select>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Finalizar",
    cancelButtonText: "Cancelar",
    preConfirm: () => {
      return {
        customerName:
          document.getElementById("customerName").value || "Cliente Balcão",
        customerPhone:
          document.getElementById("customerPhone").value || "Não informado",
        paymentMethod: document.getElementById("paymentMethod").value,
        mesaId: selectedMesa,
      };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      processarVenda({
        customerName: result.value.customerName.trim(),
        customerPhone: result.value.customerPhone,
        paymentMethod: result.value.paymentMethod,
        mesaId: result.value.mesaId,
      });
    }
  });
}

function processarVenda(data) {
  const venda = {
    customer: data.customerName,
    phone: data.customerPhone,
    payment: data.paymentMethod,
    items: cart,
    taxa: taxa,
    desconto: desconto,
    subtotal: cart.reduce(
      (total, item) => total + item.price * item.quantity,
      0
    ),
    total:
      cart.reduce((total, item) => total + item.price * item.quantity, 0) +
      taxa -
      desconto,
    mesaId: data.mesaId,
  };

  fetch("ajax/process_sale.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(venda),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Venda realizada!",
          text: "Venda processada com sucesso",
        }).then(() => {
          limparCarrinho();
        });
      } else {
        throw new Error(data.message || "Erro ao processar venda");
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: "error",
        title: "Erro!",
        text: error.message,
      });
    });
}

function setupEventListeners() {
  const searchInput = document.getElementById("searchProduct");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase();
      const productCards = document.querySelectorAll(".product-card");

      productCards.forEach((card) => {
        const productName = card
          .querySelector(".card-title3")
          .textContent.toLowerCase();
        if (productName.includes(searchTerm)) {
          card.closest(".col-md-4").style.display = "";
        } else {
          card.closest(".col-md-4").style.display = "none";
        }
      });
    });
  }
}

function loadMesas() {
  fetch("ajax/get_mesas.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const mesasGrid = document.getElementById("mesasGrid");
        mesasGrid.innerHTML = data.mesas
          .map(
            (mesa) => `
          <div class="mesa-item ${mesa.status === "Ocupada" ? "ocupada" : ""}" 
               data-mesa-id="${mesa.id}" 
               onclick="selectMesa(this, ${mesa.id})">
            <i class="fas fa-utensils mesa-icon"></i>
            <div class="mesa-numero">Mesa ${mesa.numero}</div>
            ${
              mesa.status === "Ocupada"
                ? `<div class="mesa-status">${mesa.status}</div>`
                : ""
            }
          </div>
        `
          )
          .join("");
      }
    })
    .catch((error) => console.error("Erro ao carregar mesas:", error));
}

function selectMesa(element, mesaId) {
  if (element.classList.contains("ocupada")) {
    Swal.fire({
      icon: "warning",
      title: "Mesa Ocupada",
      text: "Esta mesa já está em uso",
      showCancelButton: true,
      confirmButtonText: "Liberar Mesa",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#dc3545",
    }).then((result) => {
      if (result.isConfirmed) {
        liberarMesa(mesaId, element);
      }
    });
    return;
  }

  // Remover seleção anterior
  document.querySelectorAll(".mesa-item.selected").forEach((el) => {
    el.classList.remove("selected");
  });

  // Selecionar nova mesa
  element.classList.add("selected");
  selectedMesa = mesaId;
}

function liberarMesa(mesaId, element) {
  fetch("ajax/update_mesa_status.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      mesa_id: mesaId,
      status: "Livre",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        element.classList.remove("ocupada");
        element.querySelector(".mesa-status")?.remove();
        Swal.fire({
          icon: "success",
          title: "Mesa Liberada!",
          text: "A mesa está disponível para uso",
          timer: 1500,
          showConfirmButton: false,
        });
      } else {
        throw new Error(data.message || "Erro ao liberar mesa");
      }
    })
    .catch((error) => {
      Swal.fire({
        icon: "error",
        title: "Erro!",
        text: error.message,
      });
    });
}

function showAdjustmentModal(type) {
  const isDiscount = type === "desconto";
  const title = isDiscount ? "Adicionar Desconto" : "Adicionar Taxa";
  const color = isDiscount ? "#28a745" : "#0d6efd";

  Swal.fire({
    title: title,
    html: `
            <div class="calculator-modal">
                <div class="calc-display" id="calcDisplay">0</div>
                <div class="calc-grid">
                    <button class="calc-btn" onclick="calcInput('7')">7</button>
                    <button class="calc-btn" onclick="calcInput('8')">8</button>
                    <button class="calc-btn" onclick="calcInput('9')">9</button>
                    <button class="calc-btn operator" onclick="calcInput('/')">/</button>
                    <button class="calc-btn" onclick="calcInput('4')">4</button>
                    <button class="calc-btn" onclick="calcInput('5')">5</button>
                    <button class="calc-btn" onclick="calcInput('6')">6</button>
                    <button class="calc-btn operator" onclick="calcInput('*')">x</button>
                    <button class="calc-btn" onclick="calcInput('1')">1</button>
                    <button class="calc-btn" onclick="calcInput('2')">2</button>
                    <button class="calc-btn" onclick="calcInput('3')">3</button>
                    <button class="calc-btn operator" onclick="calcInput('-')">-</button>
                    <button class="calc-btn" onclick="calcInput('0')">0</button>
                    <button class="calc-btn" onclick="calcInput('.')">.</button>
                    <button class="calc-btn operator" onclick="calcInput('+')">+</button>
                    <button class="calc-btn equals" onclick="calcEquals()">=</button>
                    <button class="calc-btn" onclick="calcClear()">C</button>
                </div>
            </div>
        `,
    showCancelButton: true,
    confirmButtonText: "Aplicar",
    confirmButtonColor: color,
    preConfirm: () => {
      const value = parseFloat(
        document.getElementById("calcDisplay").textContent
      );
      return isNaN(value) ? 0 : value;
    },
  }).then((result) => {
    if (result.isConfirmed) {
      if (isDiscount) {
        desconto = result.value;
        document.getElementById(
          "descontoValue"
        ).textContent = `- R$ ${desconto.toFixed(2)}`;
      } else {
        taxa = result.value;
        document.getElementById("taxaValue").textContent = `R$ ${taxa.toFixed(
          2
        )}`;
      }
      updateCartTotal();
    }
  });

  // Inicializar variáveis da calculadora
  window.calcDisplay = "0";
  window.lastOperator = "";
  window.lastNumber = 0;
  window.newNumber = true;
}

// Funções da calculadora
function calcInput(value) {
  const display = document.getElementById("calcDisplay");

  if ("0123456789.".includes(value)) {
    if (window.newNumber) {
      display.textContent = value;
      window.newNumber = false;
    } else {
      display.textContent += value;
    }
  } else {
    calcEquals();
    window.lastOperator = value;
    window.lastNumber = parseFloat(display.textContent);
    window.newNumber = true;
  }
}

function calcEquals() {
  const display = document.getElementById("calcDisplay");
  const currentNumber = parseFloat(display.textContent);
  let result = currentNumber;

  if (window.lastOperator && !window.newNumber) {
    switch (window.lastOperator) {
      case "+":
        result = window.lastNumber + currentNumber;
        break;
      case "-":
        result = window.lastNumber - currentNumber;
        break;
      case "*":
        result = window.lastNumber * currentNumber;
        break;
      case "/":
        result = window.lastNumber / currentNumber;
        break;
    }
    display.textContent = result.toFixed(2);
    window.newNumber = true;
  }
}

function calcClear() {
  document.getElementById("calcDisplay").textContent = "0";
  window.newNumber = true;
  window.lastOperator = "";
  window.lastNumber = 0;
}
