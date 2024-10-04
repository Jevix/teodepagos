<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buscar usuario</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
    </style>
  </head>
  <body>
    <section class="transferir-user">
      <nav class="navbar">
        <a href="./index.php">
          <img src="../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
      </nav>
      <div class="container-buscar">
        <div>
          <form action="" id="searchForm">
            <label for="usuario" class="h2">Buscar usuario</label>
            <!-- <img src="./img/search.svg" alt=""> -->
            <input
              type="text"
              name=""
              id="usuario"
              placeholder="Busca por nombre o dni..."
            />
            <button type="submit" class="btn-primary submit--off" id="submitButton">
              Buscar
            </button>
          </form>
        </div>
        <div></div>
      </div>
      <div class="container-anteriores">
        <p class="h2">Anteriores transferencias</p>
        <div class="transferencias">
            <div class="left">
                <p class="h4">Nombre Apellido</p>
                <p class="hb">DNI</p>
            </div>
            <div>
                <p class="h4 text--blue">$50.000</p>
            </div>
        </div>
      </div>
      <div class="background"></div>
    </section>
    <script>
      const form = document.getElementById("searchForm");
      const submitButton = document.getElementById("submitButton");
      form.addEventListener("input", () => {
        const usuario = document.getElementById("usuario").value.trim();

        if (usuario) {
          submitButton.classList.remove("submit--off");
          submitButton.classList.add("submit--on");
          submitButton.disabled = false; // Habilita el botón
        } else {
          submitButton.classList.remove("submit--on");
          submitButton.classList.add("submit--off");
          submitButton.disabled = true; // Deshabilita el botón
        }
      });
    </script>
  </body>
</html>
