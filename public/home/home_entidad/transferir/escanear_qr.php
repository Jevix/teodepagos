<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login/');  // Redirigir a la página de login si no está autenticado
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR</title>
    <link rel="stylesheet" href="../../../styles.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }

      .navbar {
          display: contents !important;
      }
    </style>
</head>

<body>
<section class="transferir-user">
      <nav class="navbar">
        <a href="index.php">
          <img src="../../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2" style="color: white;">Tu QR</p>
        </nav>

        <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-sm-4 shadow p-3">
                    <div id="reader"></div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../../../assets/lector-camara/escaner-qr/assets/plugins/scanapp.min.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/lector-camara/escaner-qr/assets/js/basico2.js?v=<?php echo time(); ?>"></script>
</body>

</html>
