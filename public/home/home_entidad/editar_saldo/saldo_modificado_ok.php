<?php
// editar_saldo/saldo_modificado_ok.php
session_start();

// Proteger: solo si viene de un Banco autenticado
if (!isset($_SESSION['id_entidad'])) {
  header('Location: ../../../login');
  exit;
}

// Leer parámetros de la query string enviados por procesar_editar_saldo.php
$ok            = isset($_GET['ok']) ? (int)$_GET['ok'] : 0;
$tipo          = $_GET['tipo'] ?? '';             // 'usuario' | 'entidad'
$nombre        = $_GET['nombre'] ?? '';
$identificador = preg_replace('/\D/', '', $_GET['identificador'] ?? '');
$montoFinal    = isset($_GET['monto']) ? (int)$_GET['monto'] : 0;

// Si no vino ok=1 o faltan datos, redirigimos
if ($ok !== 1 || $nombre === '' || $identificador === '' || $montoFinal < 0) {
  header('Location: ../index.php');
  exit;
}

// Definir etiqueta para DNI/CUIT según largo
$labelIdent = (strlen($identificador) === 11) ? 'CUIT' : 'DNI';

// Fecha/hora actual para mostrar en el comprobante
date_default_timezone_set('America/Argentina/Buenos_Aires'); // ajustá si usás otra TZ
$fecha = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Saldo modificado</title>
    <link rel="stylesheet" href="../../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
    <style>
      body { background: linear-gradient(199deg, #324798 0%, #101732 65.93%); }
    </style>
  </head>
  <body>
    <section class="main">
      <nav class="navbar">
        <p class="h2">Edición de saldo</p>
      </nav>

      <div class="container-usuario-creado container-white">
        <!-- Encabezado éxito -->
        <div class="container-exito-1">
          <img src="../../../img/Congrats.svg" alt="" class="icono-exito" />
          <p class="h2">El saldo se modificó correctamente</p>
        </div>

        <!-- Datos -->
        <div class="container-datos">
          <div class="datos-transferencia">
            <p class="h2 left">Saldo final</p>
            <p class="h2 right">$<?= number_format($montoFinal, 0, ',', '.'); ?></p>
          </div>

          <div class="datos-transferencia">
            <p class="h2 left">Día y horario</p>
            <p class="h2 right"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="datos-transferencia">
            <p class="h2 left"><?= ($tipo === 'entidad' ? 'Entidad' : 'Usuario'); ?></p>
          </div>

          <div class="transferencia">
            <div class="left">
              <div>
                <p class="h4"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="hb"><?= $labelIdent; ?>: <?= htmlspecialchars($identificador, ENT_QUOTES, 'UTF-8'); ?></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="container-exito-3">
          <button class="btn-primary" onclick="redir('../index.php')">
            Volver al inicio
          </button>
        </div>

        <div class="background"></div>
      </div>
    </section>

    <script>
      function redir(url){ window.location.href = url; }
    </script>
  </body>
</html>
