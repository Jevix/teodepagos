document
.getElementById("show_password")
.addEventListener("click", function () {
  var input = document.getElementById("password");
  if (this.src.includes("viendo.svg")) {
    this.src = "../img/censurado.svg";
    input.type = "text";
  } else {
    this.src = "../img/viendo.svg";
    input.type = "password";
  }
});

const form = document.getElementById("loginForm");
const submitButton = document.getElementById("submitButton");

form.addEventListener("input", () => {
const username = document.getElementById("username").value.trim(); // Obtener valor del input de usuario
const password = document.getElementById("password").value.trim(); // Obtener valor del input de contraseña

if (username && password) {
  submitButton.classList.remove("submit--off");
  submitButton.classList.add("submit--on");
  submitButton.disabled = false; // Habilita el botón
} else {
  submitButton.classList.remove("submit--on");
  submitButton.classList.add("submit--off");
  submitButton.disabled = true; // Deshabilita el botón
}
});

function redirigir() {
window.location.href = "./"; // URL a la que deseas redirigir
}