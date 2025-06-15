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
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();

    $.ajax({
      url: window.location.href,
      type: "POST",
      dataType: "json",
      data: {
        username: $("#username").val(),
        password: $("#password").val(),
      },
      success: function (response) {
        if (response.success) {
          window.location.href = "../index.php";
        } else if (response.redirect) {
          window.location.href = response.redirect;
        } else {
          Swal.fire({
            icon: "error",
            title: "Erro!",
            text: response.message,
            confirmButtonColor: "#0d524a",
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "error",
          title: "Erro!",
          text: "Erro ao processar a requisição",
          confirmButtonColor: "#0d524a",
        });
      },
    });
  });
});
