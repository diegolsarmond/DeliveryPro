// Tempo de espera antes do redirecionamento (5 segundos)
var seconds = 5;
var countdown = setInterval(function () {
  seconds--;
  document.getElementById("countdown").textContent = seconds;

  if (seconds <= 0) {
    clearInterval(countdown);
    // Adiciona classe de fade out antes do redirecionamento
    document.querySelector(".redirect-card").style.opacity = "0";
    document.querySelector(".redirect-card").style.transform =
      "translateY(20px)";
    document.querySelector(".redirect-card").style.transition = "all 0.3s ease";

    setTimeout(() => {
      window.location.href = redirectUrl;
    }, 300);
  }
}, 1000);
