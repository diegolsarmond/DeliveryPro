document.addEventListener("DOMContentLoaded", function () {
  const typebotForm = document.getElementById("typebotSettingsForm");

  if (typebotForm) {
    typebotForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      try {
        // Mostrar loading
        Swal.fire({
          title: "Salvando...",
          text: "Aguarde enquanto salvamos as configurações",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        const formData = new FormData(this);

        // Adicionar valores dos checkboxes
        formData.set(
          "listeningFromMe",
          document.getElementById("listeningFromMe").checked
        );
        formData.set(
          "stopBotFromMe",
          document.getElementById("stopBotFromMe").checked
        );
        formData.set("keepOpen", document.getElementById("keepOpen").checked);

        // Enviar requisição
        const response = await fetch("ajax/save_typebot_settings.php", {
          method: "POST",
          body: formData,
        });

        // Verificar se a resposta é JSON
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
          throw new Error("Resposta inválida do servidor");
        }

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: "Configurações do Typebot salvas com sucesso",
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          throw new Error(data.message || "Erro ao salvar configurações");
        }
      } catch (error) {
        console.error("Erro:", error);
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao salvar configurações do Typebot",
        });
      }
    });
  }
});

async function activateTypebot() {
  try {
    // Primeiro, salvar as configurações atuais
    const formData = new FormData(
      document.getElementById("typebotSettingsForm")
    );

    const saveResponse = await fetch("ajax/save_typebot_settings.php", {
      method: "POST",
      body: formData,
    });

    const saveData = await saveResponse.json();

    if (!saveData.success) {
      throw new Error("Erro ao salvar configurações: " + saveData.message);
    }

    // Depois, ativar o Typebot
    Swal.fire({
      title: "Ativando Typebot...",
      text: "Aguarde enquanto configuramos a integração",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await fetch("ajax/activate_typebot.php");
    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Sucesso!",
        text: "Typebot ativado com sucesso",
        showConfirmButton: false,
        timer: 1500,
      }).then(() => {
        // Recarregar a página após o sucesso
        window.location.reload();
      });
    } else {
      throw new Error(data.message || "Erro ao ativar Typebot");
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao ativar Typebot",
    });
  }
}

async function toggleTypebot(enabled = true) {
  try {
    Swal.fire({
      title: enabled ? "Ativando Typebot..." : "Pausando Typebot...",
      text: "Aguarde enquanto processamos sua solicitação",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await fetch("ajax/toggle_typebot.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ enabled }),
    });

    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Sucesso!",
        text: enabled
          ? "Typebot ativado com sucesso"
          : "Typebot pausado com sucesso",
        showConfirmButton: false,
        timer: 1500,
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(data.message || "Erro ao alterar status do Typebot");
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao alterar status do Typebot",
    });
  }
}

async function disconnectTypebot() {
  try {
    const result = await Swal.fire({
      title: "Desconectar Typebot?",
      text: "Esta ação irá desconectar o Typebot. Você precisará configurar novamente para usar.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sim, desconectar",
      cancelButtonText: "Cancelar",
    });

    if (result.isConfirmed) {
      Swal.fire({
        title: "Desconectando Typebot...",
        text: "Aguarde enquanto processamos sua solicitação",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      const response = await fetch("ajax/disconnect_typebot.php", {
        method: "POST",
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: "Typebot desconectado com sucesso",
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          window.location.reload();
        });
      } else {
        throw new Error(data.message || "Erro ao desconectar Typebot");
      }
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao desconectar Typebot",
    });
  }
}

function checkTypebotStatus() {
  fetch("ajax/check_typebot_status.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const toggleButton = document.querySelector(
          '[onclick^="toggleTypebot"]'
        );
        if (toggleButton) {
          const enabled = data.data.typebots?.[0]?.enabled ?? false;
          toggleButton.className = `btn ${
            enabled ? "btn-warning" : "btn-success"
          } ms-2`;
          toggleButton.innerHTML = `
                        <i class="fas fa-${enabled ? "pause" : "play"}"></i>
                        ${enabled ? "Pausar Typebot" : "Reiniciar Typebot"}
                    `;
          toggleButton.onclick = () => toggleTypebot(!enabled);
        }
      }
    })
    .catch((error) => console.error("Erro ao verificar status:", error));
}

// Verificar status a cada 30 segundos
if (document.querySelector('[onclick^="toggleTypebot"]')) {
  setInterval(checkTypebotStatus, 30000);
}
