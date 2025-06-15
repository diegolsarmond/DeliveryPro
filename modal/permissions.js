document.addEventListener("DOMContentLoaded", function () {
  // Salvar permissões
  document.querySelectorAll(".save-permissions").forEach((button) => {
    button.addEventListener("click", async function () {
      try {
        const userId = this.dataset.userId;
        const row = this.closest("tr");

        // Mostrar loading
        Swal.fire({
          title: "Salvando...",
          text: "Atualizando permissões do usuário",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        const permissions = {
          role: row.querySelector(".role-select").value,
          dashboard_access: row.querySelector(
            '[data-permission="dashboard_access"]'
          ).checked
            ? 1
            : 0,
          pedidos_access: row.querySelector(
            '[data-permission="pedidos_access"]'
          ).checked
            ? 1
            : 0,
          movimentacao_access: row.querySelector(
            '[data-permission="movimentacao_access"]'
          ).checked
            ? 1
            : 0,
          evolution_access: row.querySelector(
            '[data-permission="evolution_access"]'
          ).checked
            ? 1
            : 0,
          typebot_access: row.querySelector(
            '[data-permission="typebot_access"]'
          ).checked
            ? 1
            : 0,
          settings_access: row.querySelector(
            '[data-permission="settings_access"]'
          ).checked
            ? 1
            : 0,
          customization_access: row.querySelector(
            '[data-permission="customization_access"]'
          ).checked
            ? 1
            : 0,
          stats_access: row.querySelector('[data-permission="stats_access"]')
            .checked
            ? 1
            : 0,
          pos_access: row.querySelector('[data-permission="pos_access"]')
            .checked
            ? 1
            : 0,
        };

        const response = await fetch("ajax/update_permissions.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            user_id: userId,
            permissions: permissions,
          }),
        });

        const data = await response.json();

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: "Permissões atualizadas com sucesso",
            showConfirmButton: false,
            timer: 1500,
          }).then(() => {
            // Recarregar a página para atualizar as permissões
            window.location.reload();
          });
        } else {
          throw new Error(data.message || "Erro ao atualizar permissões");
        }
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao atualizar permissões",
        });
      }
    });
  });

  // Atualizar visual dos checkboxes quando role mudar
  document.querySelectorAll(".role-select").forEach((select) => {
    select.addEventListener("change", function () {
      const row = this.closest("tr");
      const isAdmin = this.value === "admin";

      row.querySelectorAll(".permission-check").forEach((checkbox) => {
        checkbox.checked = isAdmin;
        checkbox.disabled = isAdmin;
      });
    });
  });
});
