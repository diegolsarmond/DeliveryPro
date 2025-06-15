// Função para visualizar a foto padrão
function previewDefaultPicture() {
  const pictureUrl = document.getElementById("default_picture").value;

  if (!pictureUrl) {
    Swal.fire({
      icon: "warning",
      title: "Atenção!",
      text: "Insira uma URL de imagem primeiro",
    });
    return;
  }

  Swal.fire({
    title: "Visualização da Foto",
    imageUrl: pictureUrl,
    imageAlt: "Foto padrão do grupo",
    confirmButtonText: "Fechar",
    showCloseButton: true,
    imageWidth: 400,
    imageHeight: 400,
    customClass: {
      image: "img-fluid",
    },
    showClass: {
      popup: "animate__animated animate__fadeIn",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOut",
    },
  }).catch((error) => {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: "Não foi possível carregar a imagem. Verifique se a URL está correta.",
    });
  });
}

document.addEventListener("DOMContentLoaded", function () {
  console.log("DOMContentLoaded event fired");

  // Adicionar event listener para o botão de preview
  const previewPictureBtn = document.getElementById("previewPictureBtn");
  if (previewPictureBtn) {
    previewPictureBtn.addEventListener("click", previewDefaultPicture);
  }

  // Event listener para o formulário de configurações padrão
  const groupDefaultsForm = document.getElementById("groupDefaultsForm");
  if (groupDefaultsForm) {
    groupDefaultsForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      try {
        const formData = new FormData(this);

        // Garantir que os checkboxes sejam enviados mesmo quando desmarcados
        formData.set(
          "auto_generate_invite",
          document.getElementById("auto_generate_invite").checked ? "1" : "0"
        );
        formData.set(
          "auto_add_to_links",
          document.getElementById("auto_add_to_links").checked ? "1" : "0"
        );
        formData.set(
          "auto_recreate_group",
          document.getElementById("auto_recreate_group").checked ? "1" : "0"
        );

        // Debug para verificar os valores antes de enviar
        console.log(
          "auto_recreate_group:",
          document.getElementById("auto_recreate_group").checked
        );

        const response = await fetch("ajax/save_evolution_defaults.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: data.message,
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            // Recarregar a página para atualizar os dados
            window.location.reload();
          });
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        console.error("Erro:", error);
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao salvar configurações padrão",
        });
      }
    });
  }

  // Adicionar event listeners para os checkboxes interdependentes
  const autoGenerateInvite = document.getElementById("auto_generate_invite");
  const autoAddToLinks = document.getElementById("auto_add_to_links");

  if (autoGenerateInvite && autoAddToLinks) {
    autoGenerateInvite.addEventListener("change", function () {
      if (!this.checked) {
        autoAddToLinks.checked = false;
        autoAddToLinks.disabled = true;
      } else {
        autoAddToLinks.disabled = false;
      }
    });

    // Verificar estado inicial
    if (!autoGenerateInvite.checked) {
      autoAddToLinks.checked = false;
      autoAddToLinks.disabled = true;
    }
  }

  // Formulário de Configurações
  const evolutionSettingsForm = document.getElementById(
    "evolutionSettingsForm"
  );
  if (evolutionSettingsForm) {
    evolutionSettingsForm.addEventListener("submit", async function (e) {
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
        const response = await fetch("ajax/evolution_settings.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: data.message,
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao salvar configurações",
        });
      }
    });
  }

  const batchUpdateForm = document.getElementById("batchUpdateForm");
  console.log("batchUpdateForm:", batchUpdateForm);

  if (batchUpdateForm) {
    console.log("Adding submit event listener to batchUpdateForm");
    batchUpdateForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("batchUpdateForm submit event fired");
      await submitBatchUpdate();
    });
  }

  // Função para preencher campos com valores padrão
  function fillDefaultValues(form) {
    // Decodificar as strings para preservar quebras de linha
    const defaultSubject = defaultGroupSettings.subject || "";
    const defaultDescription = (defaultGroupSettings.description || "")
      .replace(/\\n/g, "\n")
      .replace(/<br\s*\/?>/g, "\n"); // Remove tags <br> se existirem
    const defaultPicture = defaultGroupSettings.picture || "";
    const defaultParticipant = defaultGroupSettings.participant || "";

    console.log("Valores padrão carregados:", {
      defaultSubject,
      defaultDescription,
      defaultPicture,
      defaultParticipant,
    });

    if (form.id === "createGroupForm") {
      form.subject.value = defaultSubject;
      form.description.value = defaultDescription;
      form.participants.value = defaultParticipant;
    } else if (form.id === "batchUpdateForm") {
      form.batch_subject.value = defaultSubject;
      form.batch_description.value = defaultDescription;
      form.batch_image.value = defaultPicture;
    }
  }

  // Event listener para o checkbox de usar configurações padrão no criar grupo
  const useDefaultSettings = document.getElementById("useDefaultSettings");
  if (useDefaultSettings) {
    useDefaultSettings.addEventListener("change", function () {
      const form = document.getElementById("createGroupForm");
      if (this.checked) {
        fillDefaultValues(form);
      } else {
        form.reset();
      }
    });
  }

  // Event listener para o checkbox de usar configurações padrão na atualização em lote
  const useDefaultBatch = document.getElementById("useDefaultBatch");
  if (useDefaultBatch) {
    useDefaultBatch.addEventListener("change", function () {
      const form = document.getElementById("batchUpdateForm");
      if (this.checked) {
        fillDefaultValues(form);
      } else {
        form.reset();
        document.getElementById("confirmBatchUpdate").checked = false;
      }
    });
  }

  // Sincronizar input de cor
  const colorInput = document.querySelector('input[name="background_color"]');
  const colorText = document.getElementById("background_color_text");

  if (colorInput && colorText) {
    colorInput.addEventListener("input", function () {
      colorText.value = this.value;
    });

    colorText.addEventListener("input", function () {
      if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
        colorInput.value = this.value;
      }
    });
  }
});

// Função para gerar link de convite
async function generateInvite(groupJid) {
  try {
    const response = await fetch("ajax/generate_invite.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `group_jid=${encodeURIComponent(groupJid)}`,
    });

    const data = await response.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Link gerado com sucesso!",
        showConfirmButton: false,
        timer: 1500,
      }).then(() => {
        window.location.reload();
      });
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao gerar link de convite",
    });
  }
}

// Função para adicionar aos links
async function addToLinks(inviteUrl) {
  if (!inviteUrl) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: "Link de convite não disponível",
    });
    return;
  }

  try {
    const formData = new FormData();
    formData.append("url", inviteUrl);
    formData.append("max_views", 100); // Valor padrão ou buscar das preferências

    const response = await fetch(window.location.href, {
      method: "POST",
      body: formData,
    });

    Swal.fire({
      icon: "success",
      title: "Link adicionado com sucesso!",
      showConfirmButton: false,
      timer: 1500,
    }).then(() => {
      // Mudar para a aba de links
      localStorage.setItem("activeTab", "links");
      window.location.reload();
    });
  } catch (error) {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao adicionar link",
    });
  }
}

async function updateGroupName(groupJid) {
  try {
    const { value: newName } = await Swal.fire({
      title: "Alterar Nome do Grupo",
      input: "text",
      inputLabel: "Novo nome",
      inputPlaceholder: "Digite o novo nome do grupo",
      showCancelButton: true,
      confirmButtonText: "Alterar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#0d524a",
      inputValidator: (value) => {
        if (!value) {
          return "O nome não pode ficar em branco";
        }
      },
    });

    if (newName) {
      const response = await fetch("ajax/update_group_name.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `group_jid=${encodeURIComponent(
          groupJid
        )}&subject=${encodeURIComponent(newName)}`,
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: data.message,
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          window.location.reload();
        });
      } else {
        throw new Error(data.message);
      }
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao atualizar nome do grupo",
    });
  }
}

async function updateGroupDescription(groupJid) {
  try {
    const { value: newDescription } = await Swal.fire({
      title: "Alterar Descrição do Grupo",
      input: "textarea",
      inputLabel: "Nova descrição",
      inputPlaceholder: "Digite a nova descrição do grupo",
      showCancelButton: true,
      confirmButtonText: "Alterar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#0d524a",
      inputValidator: (value) => {
        if (!value) {
          return "A descrição não pode ficar em branco";
        }
      },
    });

    if (newDescription) {
      const response = await fetch("ajax/update_group_description.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `group_jid=${encodeURIComponent(
          groupJid
        )}&description=${encodeURIComponent(newDescription)}`,
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: data.message,
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          window.location.reload();
        });
      } else {
        throw new Error(data.message);
      }
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao atualizar descrição do grupo",
    });
  }
}

async function updateGroupPicture(groupJid) {
  try {
    const { value: imageUrl } = await Swal.fire({
      title: "Alterar Foto do Grupo",
      input: "url",
      inputLabel: "URL da imagem",
      inputPlaceholder: "Cole a URL da imagem aqui",
      showCancelButton: true,
      confirmButtonText: "Alterar",
      cancelButtonText: "Cancelar",
      confirmButtonColor: "#0d524a",
      inputValidator: (value) => {
        if (!value) {
          return "A URL da imagem não pode ficar em branco";
        }
      },
    });

    if (imageUrl) {
      const response = await fetch("ajax/update_group_picture.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `group_jid=${encodeURIComponent(
          groupJid
        )}&image=${encodeURIComponent(imageUrl)}`,
      });

      const data = await response.json();

      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: data.message,
          showConfirmButton: false,
          timer: 1500,
        }).then(() => {
          window.location.reload();
        });
      } else {
        throw new Error(data.message);
      }
    }
  } catch (error) {
    console.error("Erro:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao atualizar foto do grupo",
    });
  }
}

// Função para submeter atualização em lote
async function submitBatchUpdate() {
  console.log("submitBatchUpdate function called");
  const form = document.getElementById("batchUpdateForm");
  console.log("Form found:", form);
  const formData = new FormData(form);

  // Verificar se pelo menos um campo foi preenchido
  const subject = form.batch_subject.value;
  const description = form.batch_description.value;
  const image = form.batch_image.value;
  const useDefaults = form.use_defaults.checked;
  console.log("Form values:", { subject, description, image });

  // Validar o formato da numeração
  if (subject && subject.includes("{numero}")) {
    console.log("Nome contém marcador de numeração");
  }

  if (!useDefaults && !subject && !description && !image) {
    console.log("No fields filled");
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: "Preencha pelo menos um campo para atualizar",
    });
    return;
  }

  // Verificar se a confirmação está marcada
  console.log("Checkbox checked:", form.confirmBatchUpdate.checked);
  if (!form.confirmBatchUpdate.checked) {
    console.log("Checkbox not checked");
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: "Por favor, confirme que deseja atualizar todos os grupos",
    });
    return;
  }

  try {
    console.log("Starting batch update process");
    // Mostrar loading
    Swal.fire({
      title: "Processando...",
      text: "Atualizando grupos em lote",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const response = await fetch("ajax/batch_update_groups.php", {
      method: "POST",
      body: formData,
    });
    console.log("API Response status:", response.status);

    const data = await response.json();
    console.log("API Response data:", data);

    if (data.success) {
      let message = data.message;
      if (data.errors && data.errors.length > 0) {
        console.log("Errors found:", data.errors);
        message += "\n\nDetalhes dos erros:\n" + data.errors.join("\n");
      }

      await Swal.fire({
        icon: "success",
        title: "Atualização Concluída",
        text: message,
        confirmButtonText: "OK",
      });

      window.location.reload();
    } else {
      console.log("API returned error:", data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error("Error in submitBatchUpdate:", error);
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: error.message || "Erro ao atualizar grupos",
    });
  }
}

// Preview da logo do index
function previewIndexLogo() {
  const logoUrl = document.getElementById("index_logo_url").value;

  if (!logoUrl) {
    Swal.fire({
      icon: "warning",
      title: "Atenção!",
      text: "Insira uma URL de imagem primeiro",
    });
    return;
  }

  Swal.fire({
    title: "Visualização da Logo",
    imageUrl: logoUrl,
    imageAlt: "Logo da página de redirecionamento",
    confirmButtonText: "Fechar",
    showCloseButton: true,
    imageWidth: 400,
    customClass: {
      image: "img-fluid",
    },
  }).catch(() => {
    Swal.fire({
      icon: "error",
      title: "Erro!",
      text: "Não foi possível carregar a imagem. Verifique se a URL está correta.",
    });
  });
}

// Sincronizar input de cor
document.addEventListener("DOMContentLoaded", function () {
  const colorInput = document.querySelector('input[name="background_color"]');
  const colorText = document.getElementById("background_color_text");

  if (colorInput && colorText) {
    colorInput.addEventListener("input", function () {
      colorText.value = this.value;
    });

    colorText.addEventListener("input", function () {
      if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
        colorInput.value = this.value;
      }
    });
  }

  // Form submit handler
  const indexCustomizationForm = document.getElementById(
    "indexCustomizationForm"
  );
  if (indexCustomizationForm) {
    indexCustomizationForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      try {
        const formData = new FormData(this);
        const response = await fetch("ajax/save_index_customization.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: data.message,
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao salvar configurações",
        });
      }
    });
  }
});
