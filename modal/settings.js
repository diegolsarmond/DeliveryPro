// Declarar a função no escopo global
window.editarEstabelecimento = function () {
  // Rolar suavemente até o formulário
  document.getElementById("estabelecimentoForm").scrollIntoView({
    behavior: "smooth",
    block: "start",
  });

  // Destacar o formulário brevemente
  const form = document.getElementById("estabelecimentoForm");
  form.style.transition = "background-color 0.3s";
  form.style.backgroundColor = "#fff3cd";

  setTimeout(() => {
    form.style.backgroundColor = "transparent";
  }, 1000);

  // Mostrar mensagem
  Swal.fire({
    icon: "info",
    title: "Editar Dados",
    text: "Você pode editar os dados diretamente no formulário acima e clicar em Salvar.",
    confirmButtonColor: getComputedStyle(
      document.documentElement
    ).getPropertyValue("--primary-color"),
  });
};

document.addEventListener("DOMContentLoaded", function () {
  console.log("Settings.js carregado");

  const estabelecimentoForm = document.getElementById("estabelecimentoForm");
  if (estabelecimentoForm) {
    console.log("Formulário encontrado");

    estabelecimentoForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      console.log("Form submit iniciado");

      try {
        const formData = new FormData(this);

        for (let pair of formData.entries()) {
          console.log(pair[0] + ": " + pair[1]);
        }

        Swal.fire({
          title: "Salvando...",
          text: "Aguarde enquanto salvamos os dados",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        const response = await fetch("ajax/save_estabelecimento.php", {
          method: "POST",
          body: formData,
        });

        console.log("Response status:", response.status);

        const data = await response.json();
        console.log("Response data:", data);

        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: "Dados do estabelecimento salvos com sucesso",
            showConfirmButton: false,
            timer: 1500,
          });
        } else {
          throw new Error(data.message || "Erro ao salvar dados");
        }
      } catch (error) {
        console.error("Erro completo:", error);
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: error.message || "Erro ao processar a requisição",
        });
      }
    });
  } else {
    console.log("Formulário não encontrado");
  }
});

// Adicionar estilo para a tabela
document.addEventListener("DOMContentLoaded", function () {
  const style = document.createElement("style");
  style.textContent = `
        .table-responsive {
            margin-top: 2rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
        }
    `;
  document.head.appendChild(style);
});
