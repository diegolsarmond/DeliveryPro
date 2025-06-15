// Função para manter a aba ativa
function maintainActiveTab() {
  // Definir dashboard como aba padrão se não houver nenhuma salva
  const lastActiveTab = localStorage.getItem("activeTab") || "dashboard";

  // Ativar a aba correta
  const tabEl = document.querySelector(`#${lastActiveTab}-tab`);
  if (tabEl) {
    const tab = new bootstrap.Tab(tabEl);
    tab.show();

    // Atualizar menu lateral
    document.querySelectorAll(".menu-item").forEach((item) => {
      item.classList.remove("active");
      if (item.getAttribute("data-tab") === lastActiveTab) {
        item.classList.add("active");
      }
    });
  } else {
    // Se não encontrar a aba, ativar dashboard como fallback
    const dashboardTab = document.querySelector("#dashboard-tab");
    if (dashboardTab) {
      const tab = new bootstrap.Tab(dashboardTab);
      tab.show();

      document.querySelectorAll(".menu-item").forEach((item) => {
        item.classList.remove("active");
        if (item.getAttribute("data-tab") === "dashboard") {
          item.classList.add("active");
        }
      });
    }
  }
}

// Adicionar chamada da função quando o documento carregar
document.addEventListener("DOMContentLoaded", function () {
  maintainActiveTab();

  // Adicionar event listeners para os itens do menu
  document.querySelectorAll(".menu-item").forEach((item) => {
    item.addEventListener("click", function (e) {
      if (this.id !== "logoutBtn") {
        e.preventDefault();
        const tabName = this.getAttribute("data-tab");
        if (tabName) {
          localStorage.setItem("activeTab", tabName);
        }
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  // Verificar se há uma aba ativa da sessão
  if (typeof activeTab !== "undefined" && activeTab) {
    // Ativar a aba
    const tabEl = document.querySelector(`#${activeTab}-tab`);
    if (tabEl) {
      const tab = new bootstrap.Tab(tabEl);
      tab.show();
    }

    // Atualizar menu lateral
    const menuItems = document.querySelectorAll(".menu-item");
    menuItems.forEach((item) => {
      item.classList.remove("active");
      if (item.getAttribute("data-tab") === activeTab) {
        item.classList.add("active");
      }
    });
  }

  // Toggle Sidebar
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("mainContent");

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.toggle("collapsed");
    sidebar.classList.toggle("mobile-active");
    mainContent.classList.toggle("expanded");
  });

  // Handle Menu Items
  const menuItems = document.querySelectorAll(".menu-item");
  menuItems.forEach((item) => {
    item.addEventListener("click", function (e) {
      if (this.id !== "logoutBtn") {
        e.preventDefault();
        menuItems.forEach((i) => i.classList.remove("active"));
        this.classList.add("active");

        // Activate corresponding tab
        const tabId = this.getAttribute("data-tab");
        localStorage.setItem("activeTab", tabId); // Salva a aba ativa

        // Recarrega a página mantendo a aba ativa
        window.location.reload();
      }
    });
  });

  // Close sidebar on mobile when clicking outside
  document.addEventListener("click", function (e) {
    if (window.innerWidth <= 768) {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove("mobile-active");
      }
    }
  });

  // Manter a aba ativa após recarregar a página
  maintainActiveTab();

  // Mostrar preloader inicialmente
  const preloader = document.getElementById("preloader");
  preloader.style.display = "flex";

  // Esconder preloader e mostrar conteúdo após um pequeno delay
  setTimeout(() => {
    preloader.style.display = "none";
    document.body.style.visibility = "visible";
  }, 300);
});

function deleteLink(linkId) {
  // Salva a aba atual antes da ação
  localStorage.setItem("activeTab", "links");

  Swal.fire({
    title: "Confirmar exclusão?",
    text: "Esta ação não poderá ser revertida!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#0d524a",
    cancelButtonColor: "#ef4444",
    confirmButtonText: "Sim, excluir!",
    cancelButtonText: "Cancelar",
    customClass: {
      confirmButton: "btn btn-primary me-2",
      cancelButton: "btn btn-danger",
    },
    buttonsStyling: true,
    showClass: {
      popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        window.location.href,
        { action: "delete", delete_id: linkId },
        function (response) {
          var data = JSON.parse(response);
          if (data.status === "success") {
            Swal.fire({
              title: "Excluído!",
              text: "O link foi excluído com sucesso.",
              icon: "success",
              confirmButtonColor: "#2563eb",
              customClass: {
                confirmButton: "btn btn-primary",
              },
              buttonsStyling: false,
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              // Recarrega a página mantendo a aba ativa
              window.location.reload();
            });
          } else {
            Swal.fire({
              title: "Erro!",
              text: data.message,
              icon: "error",
              confirmButtonColor: "#2563eb",
              customClass: {
                confirmButton: "btn btn-primary",
              },
              buttonsStyling: false,
            });
          }
        }
      );
    }
  });
}

function editLink(id, url, maxViews) {
  document.getElementById("edit_id").value = id;
  document.getElementById("edit_url").value = url;
  document.getElementById("edit_max_views").value = maxViews;
  $("#editLinkModal").modal("show");
}

function duplicateLink(id) {
  if (id === undefined) {
    console.error("ID indefinido para duplicação.");
    return;
  }

  // Salva a aba atual antes da ação
  localStorage.setItem("activeTab", "links");

  Swal.fire({
    title: "Tem certeza que deseja duplicar este link?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#0d524a",
    cancelButtonColor: "#ef4444",
    confirmButtonText: "Sim, duplicar!",
    cancelButtonText: "Não, cancelar!",
    customClass: {
      confirmButton: "btn btn-primary me-2",
      cancelButton: "btn btn-danger",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      $.post(
        window.location.href,
        { action: "duplicate", duplicate_id: id },
        function (response) {
          var data = JSON.parse(response);
          if (data.status === "success") {
            Swal.fire({
              icon: "success",
              title: "Link duplicado com sucesso!",
              showConfirmButton: false,
              timer: 1500,
            }).then(function () {
              window.location.reload();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Erro",
              text: data.message,
            });
          }
        }
      );
    }
  });
}

function logout() {
  Swal.fire({
    title: "Deseja realmente sair?",
    text: "Você será desconectado do sistema",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#0d524a",
    cancelButtonColor: "#ef4444",
    confirmButtonText: "Sim, sair",
    cancelButtonText: "Cancelar",
    customClass: {
      confirmButton: "btn btn-primary me-2",
      cancelButton: "btn btn-danger",
    },
    buttonsStyling: true,
    showClass: {
      popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp",
    },
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: "Saindo...",
        text: "Você será redirecionado em instantes",
        icon: "success",
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true,
        didOpen: () => {
          Swal.showLoading();
          setTimeout(() => {
            window.location.href = "../login/logout.php";
          }, 1500);
        },
      });
    }
  });
}

// Event Listeners
$(document).ready(function () {
  $("#editLinkForm").submit(function (e) {
    e.preventDefault();

    // Salva a aba atual antes da ação
    localStorage.setItem("activeTab", "links");

    var edit_id = $("#edit_id").val();
    var url = $("#edit_url").val();
    var max_views = $("#edit_max_views").val();

    // Validação básica
    if (!url || !max_views) {
      Swal.fire({
        icon: "error",
        title: "Erro!",
        text: "Todos os campos são obrigatórios",
      });
      return;
    }

    // Mostrar loading
    Swal.fire({
      title: "Salvando...",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Enviar requisição
    $.ajax({
      url: window.location.href,
      type: "POST",
      data: {
        action: "edit",
        edit_id: edit_id,
        url: url,
        max_views: max_views,
      },
      success: function (response) {
        try {
          // Fechar o modal de loading
          Swal.close();

          // Garantir que temos um objeto JSON válido
          var data =
            typeof response === "string" ? JSON.parse(response) : response;

          // Fechar o modal de edição
          $("#editLinkModal").modal("hide");

          // Mostrar mensagem de sucesso
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: "Link atualizado com sucesso!",
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            window.location.reload();
          });
        } catch (error) {
          console.error("Erro:", error);
          Swal.fire({
            icon: "error",
            title: "Erro!",
            text: error.message || "Erro ao processar resposta do servidor",
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Erro na requisição:", error);
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: "Erro ao enviar requisição",
        });
      },
    });
  });

  $("#addLinkForm").submit(function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.post(window.location.href, formData, function (response) {
      // Salva a aba atual antes do reload
      localStorage.setItem("activeTab", "links");

      Swal.fire({
        title: "Sucesso!",
        text: "Link adicionado com sucesso!",
        icon: "success",
        confirmButtonColor: "#2563eb",
        customClass: {
          confirmButton: "btn btn-primary",
        },
        buttonsStyling: false,
        showClass: {
          popup: "animate__animated animate__fadeInDown",
        },
      }).then(() => {
        window.location.reload();
      });
    });
  });

  // Adicionar event listener para o botão de logout
  $("#logoutBtn").on("click", function (e) {
    e.preventDefault();
    Swal.fire({
      title: "Deseja realmente sair?",
      text: "Você será desconectado do sistema",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#0d524a",
      cancelButtonColor: "#ef4444",
      confirmButtonText: "Sim, sair",
      cancelButtonText: "Cancelar",
      customClass: {
        confirmButton: "btn btn-primary me-2",
        cancelButton: "btn btn-danger",
      },
      buttonsStyling: true,
      showClass: {
        popup: "animate__animated animate__fadeInDown",
      },
      hideClass: {
        popup: "animate__animated animate__fadeOutUp",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: "Saindo...",
          text: "Você será redirecionado em instantes",
          icon: "success",
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true,
          didOpen: () => {
            Swal.showLoading();
            setTimeout(() => {
              window.location.href = "./login/logout.php";
            }, 1500);
          },
        });
      }
    });
  });

  // Adicionar manipulador para o botão fechar do modal
  $(".btn-close").click(function () {
    $("#editLinkModal").modal("hide");
  });
});

// Também vamos atualizar os event listeners das abas do Bootstrap
document.querySelectorAll(".nav-tabs .nav-link").forEach((tab) => {
  tab.addEventListener("click", function (e) {
    e.preventDefault();
    const tabId = this.id.replace("-tab", "");
    localStorage.setItem("activeTab", tabId);
    window.location.reload();
  });
});

// Função para mudar de aba
function switchTab(tabId) {
  // Salvar aba ativa no localStorage
  localStorage.setItem("activeTab", tabId);

  // Ativar a nova aba
  const tabEl = document.querySelector(`#${tabId}-tab`);
  if (tabEl) {
    const tab = new bootstrap.Tab(tabEl);
    tab.show();

    // Atualizar menu lateral
    const menuItems = document.querySelectorAll(".menu-item");
    menuItems.forEach((item) => {
      item.classList.remove("active");
      if (item.getAttribute("data-tab") === tabId) {
        item.classList.add("active");
      }
    });
  }
}

// Event listener para os itens do menu
document.querySelectorAll(".menu-item").forEach((item) => {
  item.addEventListener("click", function (e) {
    e.preventDefault();
    const tabId = this.getAttribute("data-tab");
    switchTab(tabId);
  });
});

// Event listener para as abas
document.querySelectorAll(".nav-link").forEach((tab) => {
  tab.addEventListener("click", function (e) {
    e.preventDefault();
    const tabId = this.id.replace("-tab", "");
    switchTab(tabId);
  });
});

function indisponibilizarProduto(id) {
  Swal.fire({
    title: "Indisponibilizar produto?",
    text: "O produto ficará indisponível para pedidos",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sim, indisponibilizar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("ajax/indisponibilizar_produto.php", { id: id })
        .done(function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Produto indisponibilizado!",
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Erro!",
              text: response.message,
            });
          }
        })
        .fail(function () {
          Swal.fire({
            icon: "error",
            title: "Erro!",
            text: "Erro ao processar requisição",
          });
        });
    }
  });
}

function disponibilizarProduto(id) {
  Swal.fire({
    title: "Disponibilizar produto?",
    text: "O produto voltará a ficar disponível para pedidos",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sim, disponibilizar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("ajax/disponibilizar_produto.php", { id: id })
        .done(function (response) {
          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Produto disponibilizado!",
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              window.location.reload();
            });
          } else {
            Swal.fire({
              icon: "error",
              title: "Erro!",
              text: response.message,
            });
          }
        })
        .fail(function () {
          Swal.fire({
            icon: "error",
            title: "Erro!",
            text: "Erro ao processar requisição",
          });
        });
    }
  });
}
