<?php
session_start();

// Verificar si se ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login');
    exit; 
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Verificar si se ha pasado el monto y el nombre del destinatario
if (!isset($_POST['monto']) || (!isset($_POST['dni']) && !isset($_POST['cuit']))) {
    die("Datos incompletos para mostrar la confirmación.");
}

$monto = htmlspecialchars($_POST['monto']);
date_default_timezone_set('America/Argentina/Buenos_Aires');
$fecha = date('d/m H:i'); 
$nombre = isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '';
$dni = isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : '';
$cuit = isset($_POST['cuit']) ? htmlspecialchars($_POST['cuit']) : '';
$nombre_entidad = isset($_POST['nombre_entidad']) ? htmlspecialchars($_POST['nombre_entidad']) : '';

// Si hay un CUIT, buscar el nombre de la entidad en la base de datos
if ($cuit) {
    try {
        $stmt = $pdo->prepare("SELECT nombre_entidad FROM entidades WHERE cuit = :cuit");
        $stmt->execute(['cuit' => $cuit]);
        $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($entidad) {
            $nombre_entidad = $entidad['nombre_entidad'];  // Actualizar el nombre de la entidad con el resultado de la consulta
        } else {
            throw new Exception("Entidad no encontrada.");
        }
    } catch (Exception $e) {
        die("Error al buscar el nombre de la entidad: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transferencia enviada</title>
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
        <a href="./index.php">
          <img src="../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
      </nav>
      <div class="container-usuario-creado container-white">
        <div class="container-exito-1">
          <img src="../../img/Congrats.svg" alt="" class="icono-exito" />
          <p class="h2">Dinero transferido correctamente</p>
        </div>
        <div class="container-datos">
          <div class="datos-transferencia">
            <p class="h2 left">Dinero transferido</p>
            <p class="h2 right">$<?= number_format($monto, 0, '.', '.'); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">Día y horario</p>
            <p class="h2 right"><?= $fecha; ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">Destinatario</p>
          </div>
          <div class="transferencia">
            <div class="left">
              <div>
                <?php if ($dni): ?>
                  <p class="h4"><?= $nombre; ?></p>
                  <p class="hb">DNI: <?= $dni; ?></p>
                <?php elseif ($cuit): ?>
                  <p class="h4"><?= $nombre_entidad; ?></p> <!-- Aquí mostramos el nombre de la entidad -->
                  <p class="hb">CUIT: <?= $cuit; ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="container-exito-3">
          <button
            class="btn-primary"
            onclick="redireccionar('buscar_usuario.php')"
          >
            Volver a transferir
          </button>
          <button class="btn-secondary" onclick="redireccionar('../index.php')">
            Ir al inicio
          </button>
        </div>
        <div class="background"></div>
      </div>
    </section>
    <script>
      function redireccionar(enlace) {
        window.location.href = enlace;
      }
    </script>
  </body>
</html>
