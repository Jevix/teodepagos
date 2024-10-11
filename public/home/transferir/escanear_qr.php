<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login/');  // Redirigir a la página de login si no está autenticado
    exit;
}
?>
<html lang="es"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&amp;display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
    </style>
  <style type="text/css" id="operaUserStyle"></style></head>
  <body cz-shortcut-listen="true">
    <section class="main">
      <nav class="navbar">
        <a href="index.php">
          <img src="../../img/back.svg" alt="">
        </a>
        <p class="h2">Escanear QR</p>
      </nav>
      
      <div class="container-white">
      <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-sm-4 shadow p-3">
                    <div id="reader"></div>
                </div>
            </div>
        </div>
        <div class="background"></div>
      </div>
    </section>
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../../assets/lector-camara/escaner-qr/assets/plugins/scanapp.min.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/lector-camara/escaner-qr/assets/js/basico.js?v=<?php echo time(); ?>"></script>
</body>

</html>
