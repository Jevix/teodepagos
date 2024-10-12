<?php
session_start();
if (!isset($_SESSION['id_entidad'])) {
    // Redirigir al login si no está autenticado
    header('Location: ../../../login');
    exit;
}

// Incluir la configuración de la base de datos
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener el ID de la entidad desde la sesión
$id_entidad = $_SESSION['id_entidad'];

// Verificar el tipo de entidad
$query = "SELECT tipo_entidad FROM entidades WHERE id_entidad = :id_entidad";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id_entidad', $id_entidad, PDO::PARAM_INT);
$stmt->execute();
$entidad = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no es miembro de un banco, redirigir a index.php
if ($entidad === false || $entidad['tipo_entidad'] !== 'Banco') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Emitir dinero</title>
    <link rel="stylesheet" href="../../../styles.css" />
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
          <img src="../../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
      </nav>
      <div class="container-white">
        <button
          class="btn-primary"
          onclick="window.location.href='buscar_usuario.php'"
        >
          Buscar usuario <img src="../../../img/account-white.svg" alt="" />
        </button>
        <button class="btn-primary"
        onclick="window.location.href='escanear_qr.php'">
          Escanear QR <img src="../../../img/qr-white.svg" alt="" />
        </button>
        <div class="background"></div>
      </div>
    </section>
  </body>
</html>
