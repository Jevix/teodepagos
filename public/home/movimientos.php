<?php
// Establecer la duración de la sesión antes de iniciarla
$session_lifetime = 60 * 60 * 24 * 30; // 30 días
session_set_cookie_params($session_lifetime);

// Iniciar la sesión
session_start();

// Si la sesión no está activa, redirigir al login
if (!isset($_SESSION['id_usuario']) ) {
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

    echo "<script>console.log('ID de usuario: " . $id_usuario . "');</script>";

    // Consultar la tabla usuarios para obtener más información
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
    $stmt->execute(['id_usuario' => $id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
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

        echo "<script>console.log('Usuario: " . $usuario['nombre_apellido'] . "');</script>";

        // Verificar si el usuario tiene una entidad asociada
        if (!empty($usuario['id_entidad'])) {
            $id_entidad = $usuario['id_entidad'];

            // Buscar la entidad en la tabla entidades
            $stmt = $pdo->prepare("SELECT * FROM entidades WHERE id_entidad = :id_entidad");
            $stmt->execute(['id_entidad' => $id_entidad]);
            $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($entidad) {
                if ($entidad['tipo_entidad'] === 'Banco') {
                   $_SESSION['id_entidad'] = $entidad['id_entidad'];
                   $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
                   $_SESSION['tipo_entidad'] = $entidad['tipo_entidad'];
                    header('Location: home_entidad/');
                    exit;
                } elseif ($entidad['tipo_entidad'] === 'Empresa') {
                 $_SESSION['id_entidad'] = $entidad['id_entidad'];
                 $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
                 $_SESSION['tipo_entidad'] = $entidad['tipo_entidad'];
                }

            } else {
                echo "<script>console.log('Entidad no encontrada para el usuario.');</script>";
            }
        } else {
            echo "<script>console.log('Este usuario no pertenece a ninguna entidad.');</script>";
        }
    } else {
        echo "<script>console.log('Usuario no encontrado');</script>";
    }
} else {
    header('Location: ../login.php');
    exit;
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
    <section class="transferir-user">
      <nav class="navbar">
        <a href="../index.php">
          <img src="../img/back.svg" alt="" />
        </a>
        <p class="h2">Movimientos</p>
      </nav>
      <div class="container">
      <div class="movimientos">
        <p class="h2"></p>
        <div class="movimientos-container">
          <p class="fecha">Hoy</p>
          <?php if ($movimientos): ?>
               <?php foreach ($movimientos as $movimiento): ?>
               <div class="movimiento">
                  <div class="left">
                     <?php
                        $img_src = '../img/user.svg';
                        
                        // Verificar si el movimiento es un Prestamo o una Recarga
                        if ($movimiento['tipo_movimiento'] == 'Prestamo' || $movimiento['tipo_movimiento'] == 'Recarga') {
                            $img_src = '../img/bank.svg';
                        }
                        
                        // Verificar si el destinatario o remitente es un banco o una empresa
                        if ($movimiento['destinatario_tipo_entidad'] == 'Banco' || $movimiento['remitente_tipo_entidad'] == 'Banco') {
                            $img_src = '../img/bank.svg';
                        } elseif ($movimiento['destinatario_tipo_entidad'] == 'Empresa' || $movimiento['remitente_tipo_entidad'] == 'Empresa') {
                            $img_src = '../img/company.svg';
                        }
                        ?>
                     <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Entidad" />
                     <div>
                        <p class="h4">
                           <?php
                              $id_columna_remitente_usuario = $movimiento['id_remitente_usuario'];
                              $id_columna_destinatario_usuario = $movimiento['id_destinatario_usuario'];
                              $tipo_movimiento = $movimiento['tipo_movimiento'];
                              
                              // Definir las entidades
                              $id_columna_remitente_entidad = ($movimiento['id_remitente_entidad'] === NULL) ? NULL : $movimiento['id_remitente_entidad'];
                              $id_columna_destinatario_entidad = ($movimiento['id_destinatario_entidad'] === NULL) ? NULL : $movimiento['id_destinatario_entidad'];
                              
                              $nombre_remitente = $movimiento['remitente_nombre_apellido']; // Nombre completo del remitente
                              $nombre_destinatario = $movimiento['destinatario_nombre_apellido']; // Nombre completo del destinatario
                              
                              // Determinar si el usuario es remitente o destinatario
                              $accion = ''; // Variable para almacenar si fue "Enviaste" o "Recibiste"
                              $mensaje = ''; // Inicializar variable para el mensaje
                              
                              if ($id_columna_remitente_usuario == $id_usuario) {
                                  // El usuario es el remitente
                                  $accion = "Egreso"; // Guardar "Egreso" en la variable
                                  $mensaje = htmlspecialchars($nombre_destinatario); // Mostrar nombre del destinatario
                                  
                                  // Verificar si también hay entidad destinataria
                                  if ($id_columna_destinatario_entidad !== NULL) {
                                      $mensaje .= htmlspecialchars($movimiento['destinatario_nombre_entidad']); // Agregar entidad del destinatario
                                  }
                              
                              } elseif ($id_columna_destinatario_usuario == $id_usuario) {
                                  // El usuario es el destinatario
                                  $accion = "Ingreso"; // Guardar "Ingreso" en la variable
                                  $mensaje = htmlspecialchars($nombre_remitente); // Mostrar nombre del remitente
                                  
                                  // Verificar si también hay entidad remitente
                                  if ($id_columna_remitente_entidad !== NULL) {
                                      $mensaje .= htmlspecialchars($movimiento['remitente_nombre_entidad']); // Agregar entidad del remitente
                                  }
                              }
                              
                              // Mostrar el mensaje
                              echo $mensaje . "\n";
                              
                              // Mostrar detalles adicionales del movimiento, como el tipo de movimiento
                              ?>
                        </p>
                        <p class="hb">
                           <?php
                              $descripcion_movimiento = '';
                              $signo = '';
                              
                              if ($movimiento['tipo_movimiento'] == 'Prestamo') {
                                  $descripcion_movimiento = "Préstamo";
                                  $signo = "+";
                              } elseif ($movimiento['tipo_movimiento'] == 'Recarga') {
                                  $descripcion_movimiento = "Recarga de saldo";
                                  $signo = "+";
                              } elseif ($id_columna_remitente_usuario == $id_usuario) {
                                  $descripcion_movimiento = "Transferencia enviada";
                                  $signo = "-";
                              } elseif ($id_columna_destinatario_usuario == $id_usuario) {
                                  $descripcion_movimiento = "Transferencia recibida";
                                  $signo = "+";
                              } else {
                                  $descripcion_movimiento = "Movimiento desconocido";
                              }
                              
                              echo htmlspecialchars($descripcion_movimiento);
                              ?>
                        </p>
                     </div>
                  </div>
                  <div class="right">
                     <p class="h4 <?php echo ($id_columna_remitente_usuario == $id_usuario) ? 'text--minus' : 'text--plus'; ?>">
                        <?php echo $signo . "$" . number_format(abs($movimiento['monto']), 0, ',', '.'); ?>
                     </p>
                     <p class="hb">
                        <?php
                           $fecha = new DateTime($movimiento['fecha']);
                           echo $fecha->format('H:i');
                           ?>
                     </p>
                  </div>
               </div>
               <?php endforeach; ?>
               <?php else: ?>
               <div style="text-align: center;">
                  <p>No tienes movimientos todavía.</p>
               </div>
               <?php endif; ?>
               <div class="container-btn">
          </div>
            </div>
         </div>
    </section>
      </div>
      <!-- <div class="background"></div> -->
    </section>
  </body>
</html>
