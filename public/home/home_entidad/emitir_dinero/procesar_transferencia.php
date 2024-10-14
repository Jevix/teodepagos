<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener el ID de la entidad desde la sesión
$id_remitente_entidad = $_SESSION['id_entidad'];

// Recibir los datos GET del formulario
$identificador = isset($_GET['identificador']) ? htmlspecialchars($_GET['identificador']) : null;
$tipo_emision = isset($_GET['tipo_emision']) ? htmlspecialchars($_GET['tipo_emision']) : null;
$monto = isset($_GET['monto']) ? htmlspecialchars($_GET['monto']) : 0;

if (!$identificador || !$tipo_emision || $monto <= 0) {
    die('Faltan datos para procesar la transferencia.');
}

// Lógica para buscar el destinatario en la tabla correcta según la longitud del identificador (DNI o CUIT)
try {
    $nombre_destinatario = '';
    $tipo_destinatario = '';

    if (strlen($identificador) === 8) {
        // Buscar al usuario por DNI
        $stmt = $pdo->prepare("SELECT nombre_apellido FROM usuarios WHERE dni = :identificador");
        $stmt->execute(['identificador' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            $nombre_destinatario = $usuario['nombre_apellido'];
            $tipo_destinatario = 'usuario';
        } else {
            throw new Exception("Usuario no encontrado.");
        }
    } elseif (strlen($identificador) === 11) {
        // Buscar la entidad por CUIT
        $stmt = $pdo->prepare("SELECT nombre_entidad FROM entidades WHERE cuit = :identificador");
        $stmt->execute(['identificador' => $identificador]);
        $entidad = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($entidad) {
            $nombre_destinatario = $entidad['nombre_entidad'];
            $tipo_destinatario = 'entidad';
        } else {
            throw new Exception("Entidad no encontrada.");
        }
    } else {
        throw new Exception("Identificador inválido.");
    }
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seleccionar monto</title>
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
<body onload="inicializarBoton();">
  <section class="main">
    <nav class="navbar">
      <a href="buscar_usuario.php">
        <img src="../../../img/back.svg" alt="Volver" />
      </a>
      <p class="h2">Transferir</p>
    </nav>
    <div class="container-white">
      <div class="transferencia">
        <div class="left">
          <div>
            <p class="h4"><?= htmlspecialchars($nombre_destinatario); ?></p>
            <?php if ($tipo_destinatario == 'usuario'): ?>
              <p class="hb">DNI: <?= htmlspecialchars($identificador); ?></p>
            <?php elseif ($tipo_destinatario == 'entidad'): ?>
              <p class="hb">CUIT: <?= htmlspecialchars($identificador); ?></p>
            <?php endif; ?>
            <p class="hb">Tipo de emisión: <?= htmlspecialchars($tipo_emision); ?></p>
          </div>
        </div>
      </div>

      <!-- Aquí se muestra el saldo disponible -->
      <div class="dinero-disponible">
        <div>
            <p class="h1">$</p>
            <!-- Mostrar el monto recibido por GET -->
            <p class="h1" id="display" oninput="toggleBtn()"><?= htmlspecialchars($monto); ?></p>
        </div>
      </div>

      <div class="teclado-numerico">
        <div class="row">
          <button class="h2" id="btn" onclick="agregarNum(1)">1</button>
          <button class="h2" id="btn" onclick="agregarNum(2)">2</button>
          <button class="h2" id="btn" onclick="agregarNum(3)">3</button>
        </div>
        <div class="row">
          <button class="h2" id="btn" onclick="agregarNum(4)">4</button>
          <button class="h2" id="btn" onclick="agregarNum(5)">5</button>
          <button class="h2" id="btn" onclick="agregarNum(6)">6</button>
        </div>
        <div class="row">
          <button class="h2" id="btn" onclick="agregarNum(7)">7</button>
          <button class="h2" id="btn" onclick="agregarNum(8)">8</button>
          <button class="h2" id="btn" onclick="agregarNum(9)">9</button>
        </div>
        <div class="row">
          <button class="h2" id="btn"></button>
          <button class="h2" id="btn" onclick="agregarNum(0)">0</button>
          <button class="h2" id="btn" onclick="borrar()"><</button>
        </div>
      </div>
      <button
        class="btn-primary submit--off"
        id="submitButton"
        onclick="transferir()"
        disabled
      >
        Transferir
      </button>
      <div class="background"></div>
    </div>
  </section>

  <script>
    const display = document.getElementById("display");
    const submitButton = document.getElementById("submitButton");

    // Función para verificar si el monto inicial es mayor que 0 y habilitar el botón
    function inicializarBoton() {
      toggleBtn();  // Verifica el valor inicial del display
    }

    function agregarNum(number) {
      let value = display.textContent;
      if (value === "0") {
        value = number.toString();
      } else if (value.length < 12) {
        value += number.toString();
      }
      value = value.replace(/\./g, "");
      if (value.length > 3) {
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      }
      display.textContent = value;
      toggleBtn();
    }

    function borrar() {
      let value = display.textContent.replace(/\./g, "");
      if (value.length > 1) {
        value = value.slice(0, -1);
      } else {
        value = "0";
      }
      value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
      display.textContent = value;
      toggleBtn();
    }

    function toggleBtn() {
      const valorDisplay = display.textContent.replace(/\./g, '');
      const montoNumerico = parseInt(valorDisplay, 10);

      if (montoNumerico > 0) {
          submitButton.classList.remove("submit--off");
          submitButton.classList.add("submit--on");
          submitButton.disabled = false;
      } else {
          submitButton.classList.remove("submit--on");
          submitButton.classList.add("submit--off");
          submitButton.disabled = true;
      }
    }

    function transferir() {
    const monto = display.textContent.replace(/\./g, '');  // Monto del input de pantalla
    const identificador = "<?= $identificador; ?>";
    const tipo_emision = "<?= $tipo_emision; ?>";  // Tipo de emisión seleccionado

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'logica_transferencia.php';

    // Campo oculto para el monto
    const montoField = document.createElement('input');
    montoField.type = 'hidden';
    montoField.name = 'monto';
    montoField.value = monto;
    form.appendChild(montoField);

    // Campo oculto para el identificador
    const idField = document.createElement('input');
    idField.type = 'hidden';
    idField.name = 'identificador';
    idField.value = identificador;
    form.appendChild(idField);

    // Campo oculto para el tipo de emisión
    const tipoEmisionField = document.createElement('input');
    tipoEmisionField.type = 'hidden';
    tipoEmisionField.name = 'tipo_emision';
    tipoEmisionField.value = tipo_emision;
    form.appendChild(tipoEmisionField);

    document.body.appendChild(form);
    form.submit();
}
  </script>
</body>
</html>
