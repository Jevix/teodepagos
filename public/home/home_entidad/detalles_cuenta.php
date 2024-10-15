<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener el ID de la entidad desde la sesión
$id_entidad = $_SESSION['id_entidad'];

// Verificar el tipo de entidad
$query_tipo_entidad = "SELECT tipo_entidad FROM entidades WHERE id_entidad = :id_entidad";
$stmt_tipo = $pdo->prepare($query_tipo_entidad);
$stmt_tipo->bindParam(':id_entidad', $id_entidad, PDO::PARAM_INT);
$stmt_tipo->execute();
$tipo_entidad = $stmt_tipo->fetch(PDO::FETCH_ASSOC);

// Verificar si se obtuvo correctamente el tipo_entidad
if ($tipo_entidad === false) {
    echo "Error: No se encontró el tipo de entidad para la entidad ID: " . $id_entidad;
    exit;
}

// Si el tipo de entidad no es "banco", redirigir al index.php
if ($tipo_entidad['tipo_entidad'] !== 'Banco') {
    header('Location: ../index.php');
    exit;
}

// Obtener el DNI o CUIT de la cuenta desde la URL
$id = isset($_GET['id']) ? $_GET['id'] : null;

// Verificar si se proporcionó un ID válido en la URL
if ($id) {
    // Consulta para obtener los detalles de la cuenta (usuario o entidad)
    $query = "
        SELECT nombre_apellido AS nombre, dni AS identificador, id_usuario AS id, saldo, password, 'usuario' AS tipo 
        FROM usuarios 
        WHERE dni = :id
        UNION
        SELECT nombre_entidad AS nombre, cuit AS identificador, id_entidad AS id, saldo, 'N/A' AS password, tipo_entidad AS tipo 
        FROM entidades 
        WHERE cuit = :id
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si no se encuentra la cuenta, mostrar mensaje de error
if (!$cuenta) {
    echo "Cuenta no encontrada.";
    exit;
}

// Obtener el nuevo saldo del formulario si se ha enviado
$error_message = '';

if (isset($_POST['nuevo_saldo'])) {
    $nuevo_saldo = floatval($_POST['nuevo_saldo']);
    $saldo_anterior = floatval($cuenta['saldo']);

    // Verificar si el nuevo saldo es el mismo que el saldo actual o si es un número negativo o mayor al saldo actual
    if ($nuevo_saldo === $saldo_anterior) {
        $error_message = 'El nuevo saldo no puede ser igual al saldo anterior.';
    } elseif ($nuevo_saldo < 0) {
        $error_message = 'El saldo no puede ser negativo.';
    } elseif ($nuevo_saldo > $saldo_anterior) {
        $error_message = 'No se puede incrementar el saldo, solo restarlo.';
    } else {
        // Calcular la diferencia entre el saldo anterior y el nuevo
        $diferencia_monto = $saldo_anterior - $nuevo_saldo; // El monto restado

        // Insertar en la tabla `movimientos_saldo` con tipo de movimiento 'Error'
        $insert_movimiento = $pdo->prepare("
            INSERT INTO movimientos_saldo 
            (id_remitente_entidad, id_destinatario_usuario, id_destinatario_entidad, monto, tipo_movimiento, fecha) 
            VALUES (:id_entidad, :id_usuario, :id_entidad_destinatario, :monto, 'Error', NOW())
        ");

        // Verificar si la cuenta es un usuario o una entidad y ajustar las IDs
        $id_usuario = $cuenta['tipo'] === 'usuario' ? $cuenta['id'] : null;
        $id_entidad_destinatario = $cuenta['tipo'] === 'usuario' ? null : $cuenta['id'];

        // Ejecutar la inserción del movimiento
        $insert_movimiento->execute([
            'id_entidad' => $id_entidad,
            'id_usuario' => $id_usuario,
            'id_entidad_destinatario' => $id_entidad_destinatario,
            'monto' => $diferencia_monto // El monto restado
        ]);

        // Actualizar el saldo en la tabla `usuarios` o `entidades`
        if ($cuenta['tipo'] === 'usuario') {
            $update_saldo = $pdo->prepare("UPDATE usuarios SET saldo = :nuevo_saldo WHERE dni = :id");
        } else {
            $update_saldo = $pdo->prepare("UPDATE entidades SET saldo = :nuevo_saldo WHERE cuit = :id");
        }

        $update_saldo->execute([
            'nuevo_saldo' => $nuevo_saldo,
            'id' => $id
        ]);

        echo "<script>alert('Saldo actualizado con éxito.');</script>";
        header('Location: detalles_cuenta.php?id=' . $id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detalles de la Cuenta</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
      /* Ocultar el loader por defecto */
      #loader {
        display: none;
        width: 50px;
        height: 50px;
        margin: 20px auto;
      }
    </style>
</head>
<body>
    <section class="main">
      <nav class="navbar">
        <a href="cuentas.php">
          <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Detalles de la Cuenta</p>
      </nav>
      <div class="container-white container-datos">
        <div class="datos-transferencia">
          <p class="h2 text--darkblue">Tipo de cuenta:</p>
          <div class="right">
            <?php if ($cuenta['tipo'] === 'usuario'): ?>
              <img src="../../img/user.svg" alt="Usuario" />
              <p class="h2 text--darkblue">Usuario</p>
            <?php else: ?>
              <img src="../../img/empresa.svg" alt="Empresa" />
              <p class="h2 text--darkblue">Empresa</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="datos-transferencia">
          <p class="h2 text--light">Nombre</p>
          <p class="h2 text--darkblue"><?= htmlspecialchars($cuenta['nombre']); ?></p>
        </div>
        <div class="datos-transferencia">
          <p class="h2 text--light">Identificador</p>
          <p class="h2 text--darkblue"><?= htmlspecialchars($cuenta['identificador']); ?></p>
        </div>
        <div class="datos-transferencia">
          <p class="h2 text--light">Contraseña</p>
          <div class="right">
            <p class="h2 text--darkblue" id="password">
              <?php if ($cuenta['password'] !== 'N/A'): ?>
                <?= str_repeat('*', strlen($cuenta['password'])); ?>
              <?php else: ?>
                No disponible
              <?php endif; ?>
            </p>
            <?php if ($cuenta['password'] !== 'N/A'): ?>
              <img src="../../img/censurado.svg" alt="Mostrar" id="mostrar" />
            <?php endif; ?>
          </div>
        </div>
        <div class="datos-transferencia">
          <p class="h2 text--light">Saldo actual</p>
          <p class="h2 text--blue">$<?= number_format($cuenta['saldo'], 0, ',', '.'); ?></p>
        </div>

        <!-- Formulario para actualizar el saldo -->
        <form method="post" id="form-saldo">
          <div class="datos-transferencia">
            <p class="h2 text--light">Nuevo saldo</p>
            <input type="number" name="nuevo_saldo" step="0.01" value="<?= htmlspecialchars($cuenta['saldo']); ?>" required>
          </div>
          <div class="container-4">
            <button type="submit" class="btn-primary">Actualizar saldo</button>
          </div>
        </form>
        <p class="error-message" id="error-message" style="color: red;"><?= $error_message; ?></p>

        <!-- Loader GIF -->
        <img src="../../img/loader.gif" id="loader" alt="Cargando..." />

        <div class="background"></div>
      </div>
    </section>

    <script>
      const form = document.getElementById('form-saldo');
      const loader = document.getElementById('loader');

      form.addEventListener('submit', function(event) {
        event.preventDefault();  // Evita que se envíe el formulario inmediatamente
        loader.style.display = 'block';  // Muestra el GIF de carga

        // Simula un pequeño retraso antes de enviar el formulario
        setTimeout(function() {
          form.submit();  // Envía el formulario después del retraso
        }, 2000);
      });

      const icono = document.getElementById("mostrar");
      const password = document.getElementById("password");
      const textoOriginal = "<?= $cuenta['password']; ?>";

      if (icono) {
        const iconoCensurado = "../../img/censurado.svg";
        const iconoViendo = "../../img/viendo.svg";

        let censurado = true; // Por defecto, está censurado

        icono.addEventListener("click", function () {
          if (censurado) {
            icono.src = iconoViendo;
            password.textContent = textoOriginal;
          } else {
            icono.src = iconoCensurado;
            password.textContent = "*".repeat(textoOriginal.length);
          }
          censurado = !censurado;
        });
      }
      
    </script>
    <script>
      const form = document.getElementById('form-saldo');
      const loader = document.getElementById('loader');
      const errorMessage = document.getElementById('error-message');

      form.addEventListener('submit', function(event) {
        const nuevoSaldo = parseFloat(form.nuevo_saldo.value);
        const saldoActual = parseFloat("<?= $cuenta['saldo']; ?>");

        if (nuevoSaldo === saldoActual) {
          errorMessage.textContent = 'El nuevo saldo no puede ser igual al saldo anterior.';
          errorMessage.style.display = 'block';
          event.preventDefault();
          return;
        }

        if (nuevoSaldo < 0) {
          errorMessage.textContent = 'El saldo no puede ser negativo.';
          errorMessage.style.display = 'block';
          event.preventDefault();
          return;
        }

        if (nuevoSaldo > saldoActual) {
          errorMessage.textContent = 'No se puede incrementar el saldo, solo restarlo.';
          errorMessage.style.display = 'block';
          event.preventDefault();
          return;
        }

        loader.style.display = 'block'; 
        errorMessage.style.display = 'none'; // Ocultar el mensaje de error si no hay errores
      });
    </script>
</body>
</html>
