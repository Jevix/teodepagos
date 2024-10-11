<?php
// procesar_transferencia.php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login');
    exit;
}
// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener el ID del usuario desde la sesión
$usuario_sesion_id = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;

// Variables para almacenar el saldo del usuario logueado y el destinatario
$saldo_sesion = 0;  // Saldo del usuario logueado (sesión activa)
$nombre_destinatario = '';
$tipo_destinatario = '';  // Puede ser 'usuario' o 'entidad'
$dni = isset($_GET['dni']) ? htmlspecialchars($_GET['dni']) : '';
$cuit = isset($_GET['cuit']) ? htmlspecialchars($_GET['cuit']) : '';
$monto = isset($_GET['monto']) ? htmlspecialchars($_GET['monto']) : '';

// Verificar si se ha iniciado sesión y obtener el saldo del usuario logueado
if ($usuario_sesion_id) {
    try {
        // Consulta para obtener el saldo del usuario logueado
        $stmtSesion = $pdo->prepare("SELECT saldo FROM usuarios WHERE id_usuario = :id");
        $stmtSesion->execute(['id' => $usuario_sesion_id]);
        $usuario_sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario_sesion) {
            $saldo_sesion = $usuario_sesion['saldo'];  // Almacenar el saldo del usuario en sesión
        } else {
            die("No se encontró el saldo del usuario en sesión.");
        }
    } catch (PDOException $e) {
        die("Error al obtener el saldo del usuario en sesión: " . $e->getMessage());
    }
} else {
    die("No se ha iniciado sesión.");
}

// Si se ha proporcionado un DNI, buscar en la tabla usuarios
if (!empty($dni)) {
    try {
        $stmtUsuario = $pdo->prepare("SELECT nombre_apellido FROM usuarios WHERE dni = :dni");
        $stmtUsuario->execute(['dni' => $dni]);
        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $nombre_destinatario = $usuario['nombre_apellido'];
            $tipo_destinatario = 'usuario';
        } else {
            die("No se encontró ningún usuario con ese DNI.");
        }
    } catch (PDOException $e) {
        die("Error en la búsqueda del usuario: " . $e->getMessage());
    }
}

// Si se ha proporcionado un CUIT, buscar en la tabla entidades
if (!empty($cuit)) {
    try {
        $stmtEntidad = $pdo->prepare("SELECT nombre_entidad FROM entidades WHERE cuit = :cuit");
        $stmtEntidad->execute(['cuit' => $cuit]);
        $entidad = $stmtEntidad->fetch(PDO::FETCH_ASSOC);

        if ($entidad) {
            $nombre_destinatario = $entidad['nombre_entidad'];
            $tipo_destinatario = 'entidad';
        } else {
            die("No se encontró ninguna entidad con ese CUIT.");
        }
    } catch (PDOException $e) {
        die("Error en la búsqueda de la entidad: " . $e->getMessage());
    }
}

// Si no se proporcionó ni DNI ni CUIT, mostrar un mensaje de error
if (empty($dni) && empty($cuit)) {
    die("No se ha proporcionado un DNI o CUIT válido.");
}

?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seleccionar monto</title>
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
      .loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999; /* Asegúrate de que el loader esté por encima de todo */
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: rgba(255, 255, 255, 0.7); /* Fondo semitransparente para mejorar la visibilidad */
}

.loader img {
  width: 100px; /* Ajusta el tamaño según sea necesario */
  height: 100px;
}
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px); /* Sube el texto */
    }
    60% {
        transform: translateY(-5px); /* Rebote menor */
    }
}

.texto-rojo {
    color: red !important;
    animation: bounce 0.5s ease; /* Aplica la animación solo cuando la clase está activa */
}

.bounce { 
    animation: bounce 0.5s ease; /* Aplica la animación */
}
    </style>
  </head>
  <body>
  <div id="loader" class="loader" style="display: none;">
  <img src="../../img/loader.gif" alt="Cargando..." />
</div>
    <section class="main">
      <nav class="navbar">
        <a href="buscar_usuario.php">
          <img src="../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
      </nav>
      <div class="container-white">
        <div class="transferencia" onclick="redirigir()">
          <div class="left">
            <div>
            <p class="h4"><?= htmlspecialchars($nombre_destinatario); ?></p>
            <?php if ($tipo_destinatario == 'usuario'): ?>
              <p class="hb">DNI: <?= htmlspecialchars($dni); ?></p>
              </div>
            <?php elseif ($tipo_destinatario == 'entidad'): ?>
              <p class="hb">CUIT: <?= htmlspecialchars($cuit); ?></p>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="dinero-disponible">
        <p class="h4" id="dineroDisponible">Tu dinero disponible: $<?= number_format($saldo_sesion, 0, ',', '.'); ?></p>
        <div>
            <p class="h1">$</p>
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
      const btn = document.getElementById("btn");
      const submitButton = document.getElementById("submitButton");
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

      document.addEventListener("DOMContentLoaded", function() {
    // Realizar scroll hacia abajo cuando la página se carga
    window.scrollTo(0, document.body.scrollHeight);

    // Código para habilitar/deshabilitar el botón basado en el monto de la URL
    const montoUrl = "<?= isset($_GET['monto']) ? $_GET['monto'] : ''; ?>";
    const display = document.getElementById("display");
    const submitButton = document.getElementById("submitButton");

    // Si el monto viene desde la URL, mostrarlo en el campo y habilitar el botón
    if (montoUrl && parseInt(montoUrl) > 0) {
        display.textContent = montoUrl.replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Formatear con puntos
        submitButton.classList.remove("submit--off");
        submitButton.classList.add("submit--on");
        submitButton.disabled = false;
    } else {
        // Si no hay monto o es 0, el botón permanece deshabilitado
        submitButton.classList.remove("submit--on");
        submitButton.classList.add("submit--off");
        submitButton.disabled = true;
    }
});

function toggleBtn() {
    let valorDisplay = display.textContent.replace(/\./g, ''); // Eliminar puntos para hacer comparaciones numéricas
    const montoNumerico = parseInt(valorDisplay, 10); // Convertir el texto a un número entero
    const saldoDisponible = <?= json_encode($saldo_sesion); ?>; // Obtenemos el saldo disponible desde PHP

    const dineroDisponible = document.getElementById('dineroDisponible'); // Selecciona el párrafo del saldo disponible

    // Validar si el monto es mayor que 0 y menor o igual al saldo disponible
    if (montoNumerico > 0 && montoNumerico <= saldoDisponible) {
        submitButton.classList.remove("submit--off");
        submitButton.classList.add("submit--on");
        submitButton.disabled = false; // Habilitar el botón
        dineroDisponible.classList.remove('texto-rojo', 'bounce');
    } else {
        submitButton.classList.remove("submit--on");
        submitButton.classList.add("submit--off");
        submitButton.disabled = true; // Deshabilitar el botón

        // Si el monto es mayor al saldo disponible, cambiar el color a rojo y aplicar el efecto de salto
        if (montoNumerico > saldoDisponible) {
            dineroDisponible.classList.add('texto-rojo', 'bounce');
        } else {
            dineroDisponible.classList.remove('texto-rojo', 'bounce');
        }
    }
}

      function transferir() {
    const display = document.getElementById("display");
    const loader = document.getElementById("loader");

    const monto = display.textContent.replace(/\./g, ''); // Eliminar puntos del monto
    const urlParams = new URLSearchParams(window.location.search);
    const dni = urlParams.get('dni') || '';
    const cuit = urlParams.get('cuit') || '';

    // Mostrar el loader
    loader.style.display = "flex";

    // Crear un formulario dinámicamente para enviar los datos mediante POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'logica_transferencia.php'; // Cambia a .php si necesitas procesar datos en PHP

    // Crear campos ocultos para el monto, DNI y CUIT
    const montoField = document.createElement('input');
    montoField.type = 'hidden';
    montoField.name = 'monto';
    montoField.value = monto;
    form.appendChild(montoField);

    if (dni) {
        const dniField = document.createElement('input');
        dniField.type = 'hidden';
        dniField.name = 'dni';
        dniField.value = dni;
        form.appendChild(dniField);
    }

    if (cuit) {
        const cuitField = document.createElement('input');
        cuitField.type = 'hidden';
        cuitField.name = 'cuit';
        cuitField.value = cuit;
        form.appendChild(cuitField);
    }

    // Agregar el formulario al documento y enviarlo
    document.body.appendChild(form);

    // Simular un retardo para que el loader se vea durante 2 segundos
    setTimeout(() => {
        form.submit(); // Enviar el formulario después del retardo
    }, 2000); // Puedes ajustar el tiempo de la simulación de carga
}

    </script>
  </body>
</html>
