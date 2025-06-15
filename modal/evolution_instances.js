// Função para mostrar modal de criação de instância
function showCreateInstanceModal() {
  Swal.fire({
    title: "Nova Instância",
    html: `
            <form id="createInstanceForm">
                <div class="mb-3">
                    <label class="form-label">Nome da Instância</label>
                    <input type="text" class="swal2-input" id="instanceName" 
                           placeholder="Use apenas letras, números e underscores" required>
                </div>
            </form>
        `,
    showCancelButton: true,
    confirmButtonText: "Criar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#dc3545",
    preConfirm: () => {
      const instanceName = document.getElementById("instanceName").value;
      if (!instanceName) {
        Swal.showValidationMessage("Nome da instância é obrigatório");
        return false;
      }
      return { instanceName };
    },
  }).then((result) => {
    if (result.isConfirmed) {
      createInstance(result.value.instanceName);
    }
  });
}

// Função para criar nova instância
async function createInstance(instanceName) {
  try {
    Swal.fire({
      title: "Criando instância...",
      text: "Aguarde enquanto configuramos sua instância",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await fetch("ajax/create_instance.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ instanceName }),
    });

    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Sucesso!",
        text: "Instância criada com sucesso",
        showConfirmButton: false,
        timer: 1500,
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(data.message || "Erro ao criar instância");
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message,
    });
  }
}

// Função para mostrar QR Code
function showQRCode(instanceName) {
  Swal.fire({
    title: "Carregando QR Code...",
    text: "Aguarde enquanto buscamos o QR Code",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  fetch(`ajax/get_qrcode.php?instance=${instanceName}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.qrcode) {
        const qrCodeSrc = data.qrcode.startsWith("data:image/png;base64,")
          ? data.qrcode
          : `data:image/png;base64,${data.qrcode}`;

        Swal.fire({
          title: "Escaneie o QR Code",
          imageUrl: qrCodeSrc,
          imageAlt: "QR Code WhatsApp",
          text: "Use o WhatsApp do seu celular para escanear",
          confirmButtonText: "Fechar",
          confirmButtonColor: "#3085d6",
        }).then(() => {
          localStorage.setItem("activeTab", "evolution");
          window.location.reload();
        });
      } else {
        throw new Error(data.message || "QR Code não disponível");
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

// Função para conectar instância
async function connectInstance(instanceName) {
  try {
    const response = await fetch("ajax/connect_instance.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ instanceName }),
    });

    const data = await response.json();

    if (data.success) {
      if (data.showQr && data.qrcode) {
        showQRCode(instanceName);
      } else {
        Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: data.message,
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          window.location.reload();
        });
      }
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message,
    });
  }
}

// Função para desconectar instância
async function logoutInstance(instanceName) {
  try {
    const result = await Swal.fire({
      title: "Desconectar instância?",
      text: "A instância será desconectada do WhatsApp",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sim, desconectar",
      cancelButtonText: "Cancelar",
    });

    if (result.isConfirmed) {
      const response = await fetch("ajax/logout_instance.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ instanceName }),
      });

      const data = await response.json();

      if (data.success) {
        window.location.reload();
      } else {
        throw new Error(data.message);
      }
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message,
    });
  }
}

// Função para deletar instância
async function deleteInstance(instanceName) {
  try {
    const result = await Swal.fire({
      title: "Excluir instância?",
      text: "Esta ação não pode ser desfeita",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sim, excluir",
      cancelButtonText: "Cancelar",
    });

    if (result.isConfirmed) {
      const response = await fetch("ajax/delete_instance.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ instanceName }),
      });

      const data = await response.json();

      if (data.success) {
        window.location.reload();
      } else {
        throw new Error(data.message);
      }
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message,
    });
  }
}

// Atualizar status das instâncias a cada 1 minuto
setInterval(() => {
  fetch("ajax/fetch_instances.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        window.location.reload();
      }
    })
    .catch(console.error);
}, 600000);
