function resetarCategorias() {
  Swal.fire({
    title: "Confirmar Reset de Categorias",
    text: "Isso irá resetar todas as categorias para o estado inicial. Tem certeza?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, resetar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("./ajax/resetar_categorias_produtos.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "tipo=categorias",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: "Sucesso!",
              text: "Categorias resetadas com sucesso!",
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              window.location.reload();
            });
          } else {
            throw new Error(data.message || "Erro ao resetar categorias");
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
  });
}

function resetarProdutos() {
  Swal.fire({
    title: "Confirmar Reset de Produtos",
    text: "Isso irá resetar todos os produtos para o estado inicial. Tem certeza?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sim, resetar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
  }).then((result) => {
    if (result.isConfirmed) {
      fetch("./ajax/resetar_categorias_produtos.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "tipo=produtos",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            Swal.fire({
              icon: "success",
              title: "Sucesso!",
              text: "Produtos resetados com sucesso!",
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              window.location.reload();
            });
          } else {
            throw new Error(data.message || "Erro ao resetar produtos");
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
  });
}
