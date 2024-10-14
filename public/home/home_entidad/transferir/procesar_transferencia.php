<?php
// procesar_transferencia.php
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
$entidad_sesion_id = isset($_SESSION['id_entidad']) ? $_SESSION['id_entidad'] : null;

// Variables para almacenar el saldo de la entidad logueada y el destinatario
$saldo_sesion = 0;  // Saldo de la entidad logueada (sesión activa)
$nombre_destinatario = '';
$tipo_destinatario = '';  // Puede ser 'usuario' o 'entidad'
$dni = isset($_GET['dni']) ? htmlspecialchars($_GET['dni']) : '';
$cuit = isset($_GET['cuit']) ? htmlspecialchars($_GET['cuit']) : '';
$monto = isset($_GET['monto']) ? htmlspecialchars($_GET['monto']) : '0';  // Monto es opcional

// Verificar si se ha iniciado sesión y obtener el saldo de la entidad logueada
if ($entidad_sesion_id) {
    try {
        // Consulta para obtener el saldo de la entidad logueada
        $stmtSesion = $pdo->prepare("SELECT saldo FROM entidades WHERE id_entidad = :id");
        $stmtSesion->execute(['id' => $entidad_sesion_id]);
        $entidad_sesion = $stmtSesion->fetch(PDO::FETCH_ASSOC);
        
        if ($entidad_sesion) {
            $saldo_sesion = $entidad_sesion['saldo'];  // Almacenar el saldo de la entidad en sesión
        } else {
            die("No se encontró el saldo de la entidad en sesión.");
        }
    } catch (PDOException $e) {
        die("Error al obtener el saldo de la entidad en sesión: " . $e->getMessage());
    }
} else {
    die("No se ha iniciado sesión.");
}

// Lógica para buscar en la tabla correcta según la longitud del identificador (DNI)
if (!empty($dni) || !empty($cuit)) {
    // Verificar si es un DNI (8 dígitos) o un CUIT (11 dígitos)
    if (strlen($dni) === 8) {
        // Buscar en la tabla 'usuarios' por DNI
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
    } elseif (strlen($cuit) === 11) {
        // Buscar en la tabla 'entidades' por CUIT
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
    } else {
        die("El identificador proporcionado no es válido.");
    }
} else {
    die("Debe proporcionar un DNI o CUIT.");
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
      
      .loader {
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          z-index: 9999;
          display: flex;
          justify-content: center;
          align-items: center;
          background-color: rgba(255, 255, 255, 0.7);
      }
      .loader img {
          width: 100px;
          height: 100px;
      }
      @keyframes bounce {
          0%, 20%, 50%, 80%, 100% {
              transform: translateY(0);
          }
          40% {
              transform: translateY(-10px);
          }
          60% {
              transform: translateY(-5px);
          }
      }
      .texto-rojo {
          color: red !important;
          animation: bounce 0.5s ease;
      }
      .bounce { 
          animation: bounce 0.5s ease;
      }
      
    </style>
</head>
<body>
  <div id="loader" class="loader" style="display: none;">
    <img src="../../../img/loader.gif" alt="Cargando..." />
  </div>
  <section class="main">
    <nav class="navbar">
      <a href="buscar_usuario.php">
        <img src="../../../img/back.svg" alt="" />
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
        style="margin-top: 0px !important;"
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
    // Desplazar la ventana hacia abajo
    window.scrollTo(0, document.body.scrollHeight);

    // Obtener el monto de la URL si está presente
    const montoUrl = "<?= isset($_GET['monto']) ? $_GET['monto'] : ''; ?>";
    const display = document.getElementById("display");
    const submitButton = document.getElementById("submitButton");

    // Comprobar el saldo de la sesión
    const saldoDisponible = <?= json_encode($saldo_sesion); ?>; // El saldo de la sesión se pasa como variable PHP
    const dineroDisponible = document.getElementById('dineroDisponible'); // Elemento que muestra el saldo disponible

    if (montoUrl && parseInt(montoUrl) > 0) {
        // Formatear el monto si es mayor a 0
        display.textContent = montoUrl.replace(/\B(?=(\d{3})+(?!\d))/g, "."); 
        const montoNumerico = parseInt(montoUrl.replace(/\./g, ''), 10); // Monto convertido a número

        // Habilitar o deshabilitar el botón según el saldo
        if (montoNumerico <= saldoDisponible) {
            submitButton.classList.remove("submit--off");
            submitButton.classList.add("submit--on");
            submitButton.disabled = false;
        } else {
            submitButton.classList.remove("submit--on");
            submitButton.classList.add("submit--off");
            submitButton.disabled = true;
            dineroDisponible.classList.add('texto-rojo', 'bounce'); // Añadir animación si el saldo es insuficiente
        }
    } else {
        submitButton.classList.remove("submit--on");
        submitButton.classList.add("submit--off");
        submitButton.disabled = true;
    }
});
    function toggleBtn() {
      let valorDisplay = display.textContent.replace(/\./g, '');
      const montoNumerico = parseInt(valorDisplay, 10); 
      const saldoDisponible = <?= json_encode($saldo_sesion); ?>;

      const dineroDisponible = document.getElementById('dineroDisponible');

      if (montoNumerico > 0 && montoNumerico <= saldoDisponible) {
          submitButton.classList.remove("submit--off");
          submitButton.classList.add("submit--on");
          submitButton.disabled = false; 
          dineroDisponible.classList.remove('texto-rojo', 'bounce');
      } else {
          submitButton.classList.remove("submit--on");
          submitButton.classList.add("submit--off");
          submitButton.disabled = true; 

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

      const monto = display.textContent.replace(/\./g, '');  // Monto del input de pantalla
      const urlParams = new URLSearchParams(window.location.search);
      const identificador = urlParams.get('dni') || urlParams.get('cuit') || '';  // Verifica ambos

      loader.style.display = "flex";

      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'logica_transferencia.php';

      const montoField = document.createElement('input');
      montoField.type = 'hidden';
      montoField.name = 'monto';
      montoField.value = monto;
      form.appendChild(montoField);

      if (identificador) {
          const idField = document.createElement('input');
          idField.type = 'hidden';
          idField.name = 'identificador';  // Aquí debería ser 'identificador' en lugar de 'dni'
          idField.value = identificador;
          form.appendChild(idField);
      }

      document.body.appendChild(form);

      setTimeout(() => {
          form.submit(); 
      }, 2000);
    }
  </script>
</body>
</html>
