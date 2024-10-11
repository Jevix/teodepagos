<?php
session_start(); // Iniciar la sesión
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT"); // Fecha en el pasado

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_entidad'])) {
    // Redirige al usuario a la página de inicio de sesión
    header("Location: index.php");
    exit(); // Asegúrate de detener la ejecución del script después de redirigir
}

include '../../assets/barcode-master/barcode.php';
// Incluye la conexión a la base de datos personalizada
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtén los datos del usuario desde la base de datos
$id_entidad = $_SESSION['id_entidad']; // Obtener el ID del usuario de la sesión

// Consulta a la base de datos para obtener `nombre_apellido` y `dni`
$query = "SELECT nombre_entidad, cuit FROM entidades WHERE id_entidad = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id_entidad, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $nombre_apellido = $usuario['nombre_entidad']; // Almacenar el nombre y apellido juntos
    $dni = $usuario['cuit'];
} else {
    die("Usuario no encontrado.");
}

// Generar el código QR con el DNI, tamaño 200x200 píxeles
$generator = new barcode_generator();
$options = array('w' => 200, 'h' => 200); // Definir ancho y alto
$codigoQR = $generator->render_svg('qr', $dni, $options); // Usar el DNI para el QR y definir las opciones de tamaño
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tu QR</title>
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
        <a href="index.php">
          <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Tu QR</p>
      </nav>
      <div class="container-white container-tuqr">
        <div class="container-tuqr-1">
          <?php echo $codigoQR; // Mostrar el código QR generado ?>
        </div>
        <div class="container-datos">
          <div class="datos-transferencia">
            <p class="h2 text--light left">Nombre</p>
            <p class="h2 text--darkblue right"><?php echo htmlspecialchars($nombre_apellido); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 text--light left">DNI</p>
            <p class="h2 text--darkblue right"><?php echo htmlspecialchars($dni); ?></p>
          </div>
        </div>
        <div class="background"></div>
      </div>
    </section>
  </body>
</html>
