<?php
// Establecer la duración de la sesión antes de iniciarla
$session_lifetime = 60 * 60 * 24 * 30; // 30 días
session_set_cookie_params($session_lifetime);

// Iniciar la sesión
session_start();

// Si la sesión no está activa, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login');  // Redirigir a la página de login si no está autenticado
    exit;
}

if (isset($_SESSION['tipo_usuario'])) {
    // Obtener el id_usuario de la sesión
    $id_usuario = $_SESSION['id_usuario'];
    $tipo_usuario = $_SESSION['tipo_usuario'];  // Tipo de usuario

    // Conectar a la base de datos
    require '../../src/Models/Database.php';
    $config = require '../../config/config.php';
    $db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    $pdo = $db->getConnection();

    // Consultar la tabla usuarios para obtener más información
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->execute(['id_usuario' => $id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Consultar los movimientos de saldo
        $stmt = $pdo->prepare("
        SELECT ms.*, 
               remitente.nombre_apellido AS remitente_nombre_apellido, 
               destinatario.nombre_apellido AS destinatario_nombre_apellido, 
               remitente_entidad.nombre_entidad AS remitente_nombre_entidad,
               destinatario_entidad.nombre_entidad AS destinatario_nombre_entidad,
               remitente_entidad.tipo_entidad AS remitente_tipo_entidad,
               destinatario_entidad.tipo_entidad AS destinatario_tipo_entidad
        FROM movimientos_saldo ms
        LEFT JOIN usuarios AS remitente ON ms.id_remitente_usuario = remitente.id_usuario
        LEFT JOIN usuarios AS destinatario ON ms.id_destinatario_usuario = destinatario.id_usuario
        LEFT JOIN entidades AS remitente_entidad ON ms.id_remitente_entidad = remitente_entidad.id_entidad
        LEFT JOIN entidades AS destinatario_entidad ON ms.id_destinatario_entidad = destinatario_entidad.id_entidad
        WHERE ms.id_remitente_usuario = :id_usuario OR ms.id_destinatario_usuario = :id_usuario
        ORDER BY ms.fecha DESC
        ");

        $stmt->execute(['id_usuario' => $id_usuario]);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            echo "<script>console.log('Usuario no encontrado');</script>";
        }
    } else {
        echo "<script>console.log('Usuario no encontrado');</script>";
    }
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Transferir</title>
    <link rel="stylesheet" href="../styles.css" />
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
    <section class="main movimientos">
      <nav class="navbar">
        <a href="../index.php">
          <img src="../img/back.svg" alt="" />
        </a>
        <p class="h2">Movimientos</p>
      </nav>
      <div class="container-white">
        <div class="historial">
          <p class="hb">Hoy</p>

          <!-- Mostrar movimientos desde la base de datos -->
          <?php if ($movimientos): ?>
              <?php foreach ($movimientos as $movimiento): ?>
              <div class="componente--movimiento">
                <div class="left">
                  <?php
                    // Definir el ícono de la entidad
                    $img_src = '../img/user.svg';
                    if ($movimiento['tipo_movimiento'] == 'Prestamo' || $movimiento['tipo_movimiento'] == 'Recarga') {
                        $img_src = '../img/bank.svg';
                    } elseif ($movimiento['destinatario_tipo_entidad'] == 'Banco' || $movimiento['remitente_tipo_entidad'] == 'Banco') {
                        $img_src = '../img/bank.svg';
                    } elseif ($movimiento['destinatario_tipo_entidad'] == 'Empresa' || $movimiento['remitente_tipo_entidad'] == 'Empresa') {
                        $img_src = '../img/company.svg';
                    }
                  ?>
                  <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Entidad" />
                </div>
                <div class="right">
                  <div class="arriba">
                    <p class="h5">
                      <?php
                        // Mostrar el nombre del remitente o destinatario dependiendo del usuario
                        if ($movimiento['id_remitente_usuario'] == $id_usuario) {
                            echo htmlspecialchars($movimiento['destinatario_nombre_apellido'] ?: $movimiento['destinatario_nombre_entidad']);
                        } else {
                            echo htmlspecialchars($movimiento['remitente_nombre_apellido'] ?: $movimiento['remitente_nombre_entidad']);
                        }
                      ?>
                    </p>
                    <p class="h4 <?php echo ($movimiento['id_remitente_usuario'] == $id_usuario) ? 'text--minus' : 'text--plus'; ?>">
                      <?php
                        // Mostrar monto con formato
                        $signo = ($movimiento['id_remitente_usuario'] == $id_usuario) ? '-' : '+';
                        echo $signo . "$" . number_format(abs($movimiento['monto']), 0, ',', '.');
                      ?>
                    </p>
                  </div>
                  <div class="abajo">
                    <p class="hb">
                      <?php
                        // Mostrar el tipo de movimiento
                        $tipo_movimiento = '';
                        if ($movimiento['tipo_movimiento'] == 'Prestamo') {
                            $tipo_movimiento = "Préstamo";
                        } elseif ($movimiento['tipo_movimiento'] == 'Recarga') {
                            $tipo_movimiento = "Recarga de saldo";
                        } elseif ($movimiento['id_remitente_usuario'] == $id_usuario) {
                            $tipo_movimiento = "Transferencia enviada";
                        } elseif ($movimiento['id_destinatario_usuario'] == $id_usuario) {
                            $tipo_movimiento = "Transferencia recibida";
                        } else {
                            $tipo_movimiento = "Movimiento desconocido";
                        }
                        echo htmlspecialchars($tipo_movimiento);
                      ?>
                    </p>
                    <p class="hb">
                      <?php
                        // Mostrar la hora del movimiento
                        $fecha = new DateTime($movimiento['fecha']);
                        echo $fecha->format('H:i');
                      ?>
                    </p>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
          <?php else: ?>
              <p>No tienes movimientos todavía.</p>
          <?php endif; ?>

        </div>
        <div class="background"></div>
      </div>
    </section>
  </body>
</html>
