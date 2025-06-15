function togglePassword() {
  const passwordInput = document.getElementById("password");
  const icon = document.querySelector(".password-toggle i");

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    passwordInput.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

$(document).ready(function () {
  $("#registerForm").submit(function (e) {
    e.preventDefault();

    // Desabilitar o botão de submit
    const submitButton = $(this).find('button[type="submit"]');
    submitButton.prop("disabled", true);

    // Mostrar loading
    Swal.fire({
      title: "Registrando...",
      text: "Por favor, aguarde",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    $.ajax({
      url: "registrar.php",
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Registro realizado com sucesso!",
            text: "Você será redirecionado para o login",
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false,
          }).then(() => {
            window.location.href = "./";
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Erro!",
            text: response.message || "Erro ao registrar usuário",
            confirmButtonColor: "#0d524a",
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Status:", status);
        console.error("Erro:", error);
        console.error("Resposta:", xhr.responseText);

        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: "Erro ao conectar com o servidor. Por favor, tente novamente.",
          confirmButtonColor: "#0d524a",
        });
      },
      complete: function () {
        // Reabilitar o botão de submit
        submitButton.prop("disabled", false);
      },
    });
  });
});
