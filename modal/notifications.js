document.addEventListener("DOMContentLoaded", function () {
  // Configurações iniciais
  let notificationsEnabled =
    localStorage.getItem("notificationsEnabled") === "true" ||
    Notification.permission === "granted";
  let soundEnabled = localStorage.getItem("soundEnabled") === "true";
  const notificationSound = new Audio("assets/audio/notification.mp3");

  // Criar o botão de permissão
  const permissionButton = document.createElement("div");
  permissionButton.id = "notification-permission-button";
  permissionButton.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #fff;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    z-index: 10000;
  `;

  // Botão fechar
  const closeButton = document.createElement("span");
  closeButton.innerHTML = "✕";
  closeButton.style.cssText = `
    position: absolute;
    top: -10px;
    right: -10px;
    background-color: #ff4444;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
  `;

  // Container para notificações
  const notificationContainer = document.createElement("div");
  notificationContainer.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
  `;
  document.body.appendChild(notificationContainer);

  // Verificar permissões existentes
  if (notificationsEnabled) {
    permissionButton.style.display = "none";
    startCheckingOrders();
  } else {
    permissionButton.innerHTML = "🔔 Ativar Notificações e Som";
    permissionButton.appendChild(closeButton);
    document.body.appendChild(permissionButton);
  }

  // Evento de clique no botão de permissão
  permissionButton.addEventListener("click", async function (e) {
    if (e.target === closeButton) {
      permissionButton.remove();
      const message = document.createElement("div");
      message.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #f8f9fa;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 10000;
      `;
      message.textContent =
        "Você pode ativar as notificações mais tarde nas configurações do navegador";
      document.body.appendChild(message);
      setTimeout(() => message.remove(), 5000);
      return;
    }

    try {
      const permission = await Notification.requestPermission();
      if (permission === "granted") {
        notificationsEnabled = true;
        soundEnabled = true;

        // Salvar ambos os estados no localStorage
        localStorage.setItem("notificationsEnabled", "true");
        localStorage.setItem("soundEnabled", "true");

        // Testar o som com interação do usuário
        try {
          await notificationSound.play();
          notificationSound.volume = 0.5;
        } catch (error) {
          console.warn("Não foi possível reproduzir o som:", error);
        }

        permissionButton.innerHTML = "🔔 Notificações e Som Ativados";
        setTimeout(() => {
          permissionButton.style.display = "none";
        }, 2000);
        startCheckingOrders();
      }
    } catch (error) {
      console.error("Erro ao solicitar permissão:", error);
    }
  });

  // Verificar novos pedidos
  function startCheckingOrders() {
    checkNewOrders();
    setInterval(checkNewOrders, 30000); // Verificar a cada 30 segundos
  }

  async function checkNewOrders() {
    try {
      const response = await fetch("ajax/check_new_orders.php");
      const pedidos = await response.json();

      if (pedidos.length > 0 && soundEnabled) {
        try {
          notificationSound.volume = 0.5;
          await notificationSound.play();
        } catch (error) {
          console.warn("Erro ao reproduzir som:", error);
        }
      }

      pedidos.forEach((pedido) => {
        createOrderNotification(pedido);
      });
    } catch (error) {
      console.error("Erro ao verificar pedidos:", error);
    }
  }

  function createOrderNotification(pedido) {
    const notification = document.createElement("div");
    notification.style.cssText = `
      background-color: #ffffff;
      border-left: 4px solid #dd2c2a;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      padding: 16px;
      margin-bottom: 10px;
      border-radius: 4px;
      width: 300px;
      position: relative;
      opacity: 0;
      transform: translateX(100%);
      animation: slideIn 0.5s ease-out forwards;
    `;

    notification.innerHTML = `
      <div style="margin-bottom: 8px;">
        <strong>Novo Pedido!</strong>
        <span style="position: absolute; right: 10px; top: 10px; cursor: pointer;" 
              onclick="closeOrderNotification(this, ${pedido.id}, true)">✕</span>
      </div>
      <div><i class="fas fa-user"></i> Cliente: ${pedido.nome}</div>
      <div><i class="fas fa-hashtag"></i> Pedido: #${pedido.pedido}</div>
      <div><i class="fas fa-money-bill"></i> Total: ${pedido.total}</div>
      <div><i class="fas fa-clock"></i> Data: ${pedido.data}</div>
    `;

    notificationContainer.appendChild(notification);

    // Auto-fechamento após 10 segundos
    setTimeout(() => {
      if (notification.parentElement) {
        notification.style.animation = "fadeOut 1s ease-out forwards";
      }
    }, 10000);
  }

  // Função global para fechar notificação
  window.closeOrderNotification = function (
    element,
    orderId,
    immediate = false
  ) {
    const notificationDiv = element.closest("div").parentElement;

    if (immediate) {
      fetch("ajax/mark_order_viewed.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "id=" + orderId,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            notificationDiv.style.animation = "slideOut 0.5s ease-out forwards";
          }
        })
        .catch((error) =>
          console.error("Erro ao marcar como visualizado:", error)
        );
    } else {
      notificationDiv.style.animation = "fadeOut 1s ease-out forwards";
    }
  };

  // Adicionar estilos de animação
  const style = document.createElement("style");
  style.textContent = `
    @keyframes slideIn {
      0% { 
        transform: translateX(100%);
        opacity: 0;
      }
      50% { 
        transform: translateX(-10px);
        opacity: 0.8;
      }
      100% { 
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOut {
      0% { 
        transform: translateX(0);
        opacity: 1;
      }
      100% { 
        transform: translateX(100%);
        opacity: 0;
      }
    }

    @keyframes fadeOut {
      0% {
        opacity: 1;
        transform: translateY(0);
      }
      100% {
        opacity: 0;
        transform: translateY(20px);
      }
    }
  `;
  document.head.appendChild(style);
});
