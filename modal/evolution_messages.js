function showAddMessageModal(isEdit = false) {
  Swal.fire({
    title: isEdit ? "Editar Mensagem" : "Nova Mensagem",
    html: `
      <form id="messageForm">
        <div class="mb-3">
          <label class="form-label">Nome da Mensagem</label>
          <input type="text" class="form-control" id="messageName" name="nome_mensagem" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Categoria</label>
          <select class="form-control" id="messageCategory" name="categoria" required>
            <option value="">Selecione uma categoria</option>
            <option value="Agradecimento">Agradecimento</option>
            <option value="Entrega">Entrega</option>
            <option value="Pedido Recebido">Pedido Recebido</option>
            <option value="Status">Status</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Template da Mensagem</label>
          <textarea class="form-control" id="messageTemplate" name="template" rows="4" required></textarea>
          <small class="text-muted">
            Variáveis disponíveis:<br>
            $pedido - Número do pedido<br>
            $status - Status atual do pedido
          </small>
        </div>
        <input type="hidden" id="messageId" name="id">
      </form>
    `,
    showCancelButton: true,
    confirmButtonText: "Salvar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#0d524a",
    cancelButtonColor: "#6c757d",
    customClass: {
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-secondary",
      validationMessage: "text-danger",
    },
    buttonsStyling: true,
    focusConfirm: false,
    preConfirm: () => {
      const nome = document.getElementById("messageName").value.trim();
      const categoria = document.getElementById("messageCategory").value;
      const template = document.getElementById("messageTemplate").value.trim();

      if (!nome) {
        document.getElementById("messageName").focus();
        Swal.showValidationMessage("Digite o nome da mensagem");
        return false;
      }
      if (!categoria) {
        document.getElementById("messageCategory").focus();
        Swal.showValidationMessage("Selecione uma categoria");
        return false;
      }
      if (!template) {
        document.getElementById("messageTemplate").focus();
        Swal.showValidationMessage("Digite o template da mensagem");
        return false;
      }

      return {
        id: document.getElementById("messageId").value || "",
        nome_mensagem: nome,
        categoria: categoria,
        template: template,
      };
    },
    didOpen: () => {
      document.getElementById("messageName").focus();

      if (isEdit) {
        const messageId = document.getElementById("messageId").value;
        const messageName = document.getElementById("messageName").value;
        const messageCategory =
          document.getElementById("messageCategory").value;
        const messageTemplate =
          document.getElementById("messageTemplate").value;

        if (messageId && messageName && messageCategory && messageTemplate) {
          document.getElementById("messageId").value = messageId;
          document.getElementById("messageName").value = messageName;
          document.getElementById("messageCategory").value = messageCategory;
          document.getElementById("messageTemplate").value = messageTemplate;
        }
      }
    },
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      saveMessage(result.value);
    }
  });
}

async function saveMessage(formData) {
  try {
    Swal.fire({
      title: "Salvando...",
      text: "Aguarde enquanto salvamos a mensagem",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await fetch("ajax/save_message.php", {
      method: "POST",
      body: new URLSearchParams(formData),
    });

    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Sucesso!",
        text: "Mensagem salva com sucesso",
        showConfirmButton: false,
        timer: 1500,
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(data.message || "Erro ao salvar mensagem");
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message,
    });
  }
}
