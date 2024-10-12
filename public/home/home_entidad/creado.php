<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../login.php');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Verificar si la entidad es de tipo 'Banco'
$stmt = $pdo->prepare("SELECT tipo_entidad FROM entidades WHERE id_entidad = :id_entidad");
$stmt->execute(['id_entidad' => $_SESSION['id_entidad']]);
$entidad = $stmt->fetch(PDO::FETCH_ASSOC);

if ($entidad['tipo_entidad'] !== 'Banco') {
    // Si no es un banco, redirigir a una página de error o inicio
    header('Location: ../index.php');
    exit;
}

// Obtener los datos del POST (enviados desde agregar_usuario.php)
$tipo_cuenta = $_POST['tipo_cuenta'] ?? 'Usuario';
$nombre = $_POST['nombre'] ?? 'N/A';
$apellido = $_POST['apellido'] ?? 'N/A';
$dni = $_POST['dni'] ?? 'N/A';
$fechaNacimiento = $_POST['fechaNacimiento'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cuenta creada</title>
    <link rel="stylesheet" href="../../styles.css" />
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
    </style>
  </head>
  <body>
    <section class="main">
      <nav class="navbar">
        <a href="index.php">
          <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Cuenta creada</p>
      </nav>
      <div class="container-usuario-creado container-white">
        <div class="container-exito-1">
          <img src="../../img/Congrats.svg" alt="" class="icono-exito" />
          <p class="h2">Cuenta creada correctamente</p>
        </div>
        <div class="container-datos">
          <div class="datos-transferencia">
            <p class="h2 left">Tipo de cuenta:</p>
            <p class="h2 right"><img src="../img/user.svg" alt=""><?= htmlspecialchars($tipo_cuenta); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">Nombre</p>
            <p class="h2 right"><?= htmlspecialchars($nombre); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">Apellido</p>
            <p class="h2 right"><?= htmlspecialchars($apellido); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">DNI</p>
            <p class="h2 right"><?= htmlspecialchars($dni); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 left">Password</p>
            <p class="h2 right"><?= htmlspecialchars($fechaNacimiento); ?></p>
          </div>
        </div>
        <div class="container-exito-3">
          <button
            class="btn-primary"
            onclick="redireccionar('agregar_usuario.php')"
          >
            Volver a emitir
          </button>
          <button
            class="btn-secondary"
            onclick="redireccionar('index.php')"
          >
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
