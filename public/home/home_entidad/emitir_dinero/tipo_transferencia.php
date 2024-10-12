<?php
session_start();
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener identificador desde la URL (puede ser DNI o CUIT)
$dni = isset($_GET['dni']) ? htmlspecialchars($_GET['dni']) : '';
$cuit = isset($_GET['cuit']) ? htmlspecialchars($_GET['cuit']) : '';

// Si no hay identificador, redirigir de nuevo
if (!$dni && !$cuit) {
    header('Location: buscar_usuario.php');
    exit;
}

$identificador = $dni ? $dni : $cuit;

// Buscar en la tabla `usuarios` si es DNI o en `entidades` si es CUIT
$nombre_completo = '';
if ($dni) {
    // Buscar en la tabla `usuarios` por DNI
    $stmt = $pdo->prepare("SELECT nombre_apellido FROM usuarios WHERE dni = :identificador");
    $stmt->execute(['identificador' => $dni]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $nombre_completo = $usuario['nombre_apellido'];
    } else {
        die("No se encontró ningún usuario con ese DNI.");
    }
} elseif ($cuit) {
    // Buscar en la tabla `entidades` por CUIT
    $stmt = $pdo->prepare("SELECT nombre_entidad FROM entidades WHERE cuit = :identificador");
    $stmt->execute(['identificador' => $cuit]);
    $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entidad) {
        $nombre_completo = $entidad['nombre_entidad'];
    } else {
        die("No se encontró ninguna entidad con ese CUIT.");
    }
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
      <a href="buscar_usuario.php">
        <img src="../../../img/back.svg" alt="Volver" />
      </a>
      <p class="h2">Emitir dinero</p>
    </nav>

    <section class="container-white">
      <div class="container container-emitir-dinero">
        <div class="transferencia">
          <div class="left">
            <div>
              <p class="h4"><?= htmlspecialchars($nombre_completo); ?></p>
              <p class="hb"><?= $dni ? "DNI: $dni" : "CUIT: $cuit" ?></p>
            </div>
          </div>
        </div>

        <!-- Formulario para seleccionar tipo de emisión y enviar a procesar_transferencia.php -->
        <form action="procesar_transferencia.php" method="get">
          <input type="hidden" name="identificador" value="<?= $identificador ?>">
          
          <div class="container-2">
            <p class="h2 text--darkblue">Tipo de emisión</p>
          </div>

          <div class="container-3">
            <div class="container-checkbox">
              <p class="h2 text--darkblue">Recarga</p>
              <input type="radio" name="tipo_emision" value="Recarga" id="checkbox1" class="checkbox" onclick="verificarCheckbox()"/>
            </div>
            <div class="container-checkbox">
              <p class="h2 text--darkblue">Préstamo</p>
              <input type="radio" name="tipo_emision" value="Prestamo" id="checkbox2" class="checkbox" onclick="verificarCheckbox()"/>
            </div>
          </div>

          <div class="container-4">
            <button class="btn-primary submit--off" id="submitButton" type="submit" disabled>Emitir</button>
          </div>
        </form>

      </div>
      <div class="background"></div>
    </section>
  </section>

  <script>
    const checkboxes = document.querySelectorAll(".checkbox");
    const submitButton = document.getElementById("submitButton");

    // Desmarcar otros checkboxes si uno está marcado
    checkboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", function () {
        if (this.checked) {
          checkboxes.forEach((otherCheckbox) => {
            if (otherCheckbox !== this) {
              otherCheckbox.checked = false;
            }
          });
        }
        verificarCheckbox(); // Verificar si el botón debe habilitarse
      });
    });

    function verificarCheckbox(){
      // Si algún checkbox está marcado, habilitar el botón
      if (document.querySelector('input[name="tipo_emision"]:checked')) {
        submitButton.classList.remove("submit--off");
        submitButton.classList.add("submit--on");
        submitButton.disabled = false; // Habilita el botón
      } else {
        submitButton.classList.remove("submit--on");
        submitButton.classList.add("submit--off");
        submitButton.disabled = true; // Deshabilita el botón
      }
    }
  </script>
</body>
</html>
