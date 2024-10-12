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
        SELECT nombre_apellido AS nombre, dni AS identificador, saldo, password, 'usuario' AS tipo 
        FROM usuarios 
        WHERE dni = :id
        UNION
        SELECT nombre_entidad AS nombre, cuit AS identificador, saldo, 'N/A' AS password, tipo_entidad AS tipo 
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
        <div class="background"></div>
      </div>
    </section>
    <script>
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
  </body>
</html>
