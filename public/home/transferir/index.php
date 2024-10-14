<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
  header('Location: ../../login');

}

?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transferir</title>
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
    <section class="main">
      <nav class="navbar">
        <a href="../index.php">
          <img src="../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
      </nav>
      <div class="container-white">
        <button
          class="btn-primary"
          onclick="window.location.href='buscar_usuario.php'"
        >
          Buscar usuario <img src="../../img/account-white.svg" alt="" />
        </button>
        <button class="btn-primary"
        onclick="window.location.href='escanear_qr.php'">
          Escanear QR <img src="../../img/qr-white.svg" alt="" />
        </button>
        <div class="background"></div>
      </div>
    </section>
  </body>
</html>
