<?php
session_start(); // Iniciar la sesión
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT"); // Fecha en el pasado

// Verificar si la entidad está autenticada
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

// Obtén los datos de la entidad desde la base de datos
$id_entidad = $_SESSION['id_entidad']; // Obtener el ID de la entidad de la sesión

// Consulta a la base de datos para obtener `nombre_entidad` y `cuit`
$query = "SELECT nombre_entidad, cuit FROM entidades WHERE id_entidad = :id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id', $id_entidad, PDO::PARAM_INT);
$stmt->execute();
$entidad = $stmt->fetch(PDO::FETCH_ASSOC);

if ($entidad) {
    $nombre_entidad = $entidad['nombre_entidad']; // Almacenar el nombre de la entidad
    $cuit = $entidad['cuit'];
} else {
    die("Entidad no encontrada.");
}

// Generar el código QR con el CUIT, tamaño 200x200 píxeles
$generator = new barcode_generator();
$options = array('w' => 200, 'h' => 200); // Definir ancho y alto
$codigoQR = $generator->render_svg('qr', $cuit, $options); // Usar el CUIT para el QR y definir las opciones de tamaño
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tu QR - Entidad</title>
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
    <section class="transferir-user tu-qr">
      <nav class="navbar">
        <a href="index.php">
          <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Tu QR</p>
      </nav>
      <div class="container">
        <div class="container-1">
          <div class="qr-placeholder">
            <?php echo $codigoQR; // Mostrar el código QR generado ?>
          </div>
        </div>
        <div class="container-2">
            <div class="detalles-user">
                <p class="h2 text--light">Nombre de la Entidad</p>
                <p class="h2 text--darkblue"><?php echo htmlspecialchars($nombre_entidad); ?></p>
            </div>
            <div class="detalles-user">
                <p class="h2 text--light">CUIT</p>
                <p class="h2 text--darkblue"><?php echo htmlspecialchars($cuit); ?></p>
            </div>
        </div>
      </div>
    </section>
  </body>
</html>
