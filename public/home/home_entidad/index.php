<?php
  session_start();
  if (!isset($_SESSION['id_entidad'])) {
      header('Location: ../index.php');  // Redirigir a la página de login si no está autenticado
      exit;
  }
  if (isset($_SESSION['tipo_entidad'])) {
      $id_entidad = $_SESSION['id_entidad'];
      $tipo_entidad = $_SESSION['tipo_entidad'];
      $id_usuario = $_SESSION['id_usuario'];

      require '../../../src/Models/Database.php';
      $config = require '../../../config/config.php';
      $db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
      $pdo = $db->getConnection();

      $stmt = $pdo->prepare("SELECT * FROM entidades WHERE id_entidad = :id_entidad");
      $stmt->execute(['id_entidad' => $id_entidad]);
      $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($entidad) {
          $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
          $_SESSION['tipo_entidad'] = $entidad['tipo_entidad'];
          $_SESSION['cuit'] = $entidad['cuit'];

          // Obtener los movimientos de saldo
          $stmt = $pdo->prepare("
    SELECT ms.*, 
       COALESCE(remitente_entidad.nombre_entidad, remitente_usuario.nombre_apellido) AS remitente_nombre,
       destinatario_entidad.nombre_entidad AS destinatario_nombre_entidad,
       destinatario_usuario.nombre_apellido AS destinatario_nombre_usuario,
       remitente_entidad.tipo_entidad AS remitente_tipo_entidad,
       destinatario_entidad.tipo_entidad AS destinatario_tipo_entidad
FROM movimientos_saldo ms
LEFT JOIN entidades AS remitente_entidad ON ms.id_remitente_entidad = remitente_entidad.id_entidad
LEFT JOIN usuarios AS remitente_usuario ON ms.id_remitente_usuario = remitente_usuario.id_usuario
LEFT JOIN entidades AS destinatario_entidad ON ms.id_destinatario_entidad = destinatario_entidad.id_entidad
LEFT JOIN usuarios AS destinatario_usuario ON ms.id_destinatario_usuario = destinatario_usuario.id_usuario
WHERE ms.id_remitente_entidad = :id_entidad OR ms.id_destinatario_entidad = :id_entidad
ORDER BY ms.fecha DESC
LIMIT 5;
");
$stmt->execute(['id_entidad' => $id_entidad]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
          echo "<script>console.log('Entidad no encontrada');</script>";
      }
  } else {
      header('Location: ../login.php');
      exit;
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home - Entidad</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
  </head>
  <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
        /* Estilos para el loader */
      .loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999; /* Asegúrate de que el loader esté por encima de todo */
        display: flex;
        justify-content: center;
        align-items: center;
      }

    </style>
  <body>
   <!-- Loader que muestra el GIF -->
   <div id="loader" class="loader" style="display: none;">
      <img src="../../img/loader.gif" alt="Cargando..." />
    </div>

    <section class="home-user">
      <nav class="navbar">
        <div class="left">
          <?php if ($entidad['tipo_entidad'] === 'Banco')
            echo '<img src="../../img/banco-white.svg" alt="" />'; 

            elseif ($entidad['tipo_entidad'] === 'Empresa')
            echo '<img src="../../img/empresa-white.svg" alt="" />';
            ?>
          <div>
              <p class="documento"><?php echo $entidad['cuit']; ?></p> <!-- Mostrar CUIT -->
              <p class="nombre"><?php echo $entidad['nombre_entidad']; ?></p> <!-- Mostrar nombre de la entidad -->
          </div>
        </div>
        <div class="right">
          <?php
          if ($entidad['tipo_entidad'] === 'Banco') {
              echo '<a href="../../logout.php">';
              echo '<img src="../../img/salir.svg" alt="" class="salir" />';
          } else {
              echo '<a href="../index.php">';
              echo '<img src="../../img/back.svg" alt="" class="salir" />';
          }
          ?>
               
               
          </a>
        </div>
      </nav>
      <div class="dinero">
        <p class="h2">Saldo disponible</p>
        <p class="h1">$ <?php echo number_format($entidad['saldo'], 0, ',', '.'); ?></p>
      </div>
      <div class="transacciones">
    <?php if ($entidad['tipo_entidad'] === 'Banco'): ?>
        <div onclick="showLoaderAndRedirect('cuentas.php')">
            <img src="../../img/account_1.svg" alt="Cuentas" />
            <p class="hb">Cuentas</p>
        </div>
        <div onclick="showLoaderAndRedirect('agregar_usuario.php')">
            <img src="../../img/agregar_usuario.svg" alt="Agregar" />
            <p class="hb">Agregar</p>
        </div>
        <div onclick="showLoaderAndRedirect('emitir_dinero')">
            <img src="../../img/emitir.svg" alt="Emitir Dinero" />
            <p class="hb">Emitir Dinero</p>
        </div>
    <?php elseif ($entidad['tipo_entidad'] === 'Empresa'): ?>
        <div onclick="showLoaderAndRedirect('transferir')">
            <img src="../../img/transferir.svg" alt="Transferir" />
            <p class="hb">Transferir</p>
        </div>
        <div onclick="showLoaderAndRedirect('miqr.php')">
            <img src="../../img/qr.svg" alt="Tu QR" />
            <p class="hb">Tu QR</p>
        </div>
    <?php endif; ?>
</div>
      <div class="movimientos">
        <p class="h2">Movimientos</p>
        <div class="movimientos-container">
          <p class="fecha">Hoy</p>
          <?php if ($movimientos): ?>
    <?php foreach ($movimientos as $movimiento): ?>
        <div class="movimiento">
            <div class="left">
                <?php
                $img_src = '../../img/user.svg';  // Imagen por defecto para un usuario

                // Verificar si el movimiento es un Prestamo o una Recarga
                if ($movimiento['tipo_movimiento'] == 'Prestamo' || $movimiento['tipo_movimiento'] == 'Recarga') {
                    $img_src = '../../img/bank.svg';  // Imagen para banco en movimientos de préstamo o recarga
                } 
                
                // Verificar si el remitente o destinatario es un banco o una empresa
                elseif ($movimiento['remitente_tipo_entidad'] == 'Banco' && $movimiento['destinatario_tipo_entidad'] == 'Banco') {
                    $img_src = '../../img/bank.svg';  // Imagen para bancos
                } elseif ($movimiento['remitente_tipo_entidad'] == 'Empresa' && $movimiento['destinatario_tipo_entidad'] == 'Empresa') {
                    $img_src = '../../img/company.svg';  // Imagen para empresas
                }

                if ($movimiento['remitente_tipo_entidad'] == 'Empresa' && $movimiento['destinatario_tipo_entidad'] == 'Banco') {
                    $img_src = '../../img/bank.svg';  // Imagen para bancos
                }
                

               
                ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Entidad" />
                <div>
                    <p class="h4">
                        <?php
                        $id_columna_remitente_usuario = $movimiento['id_remitente_usuario'];
                        $id_columna_remitente_entidad = $movimiento['id_remitente_entidad'];
                        $id_columna_destinatario_usuario = $movimiento['id_destinatario_usuario'];
                        $id_columna_destinatario_entidad = $movimiento['id_destinatario_entidad'];
                        
                        // Detectar siempre el nombre correcto basado en la sesión activa
                        if ($id_columna_destinatario_entidad == $id_entidad || $id_columna_destinatario_usuario == $id_usuario) {
                            // Si la sesión activa es el destinatario, mostrar el nombre del remitente
                            if ($id_columna_remitente_entidad == NULL) {
                                // Si el remitente es un usuario, mostrar el nombre del usuario remitente
                                $nombre_destinatario = $movimiento['remitente_nombre'];
                            } else {
                                // Si el remitente es una entidad, mostrar el nombre de la entidad remitente
                                $nombre_destinatario = $movimiento['remitente_nombre'];
                            }
                        } else {
                            // Si la sesión activa es el remitente, mostrar el nombre del destinatario
                            if ($id_columna_destinatario_entidad == NULL) {
                                // Si el destinatario es un usuario, mostrar el nombre del destinatario usuario
                                $nombre_destinatario = $movimiento['destinatario_nombre_usuario'];
                            } else {
                                // Si el destinatario es una entidad, mostrar el nombre del destinatario entidad
                                $nombre_destinatario = $movimiento['destinatario_nombre_entidad'];
                            }
                        }
                        
                        // Mostrar el nombre final
                        echo htmlspecialchars($nombre_destinatario);
                          
                        ?>
                    </p>
                    <p class="hb">
                        <?php
                            // Mostrar el tipo de movimiento
                            $descripcion_movimiento = '';
                            $signo = '';

                            if ($movimiento['tipo_movimiento'] == 'Prestamo') {
                                $descripcion_movimiento = "Préstamo";
                                $signo = "+";
                            } elseif ($movimiento['tipo_movimiento'] == 'Recarga') {
                                $descripcion_movimiento = "Recarga de saldo";
                                $signo = "+";
                            } elseif ($movimiento['id_remitente_entidad'] == $id_entidad) {
                                $descripcion_movimiento = "Transferencia enviada";
                                $signo = "-";
                            } else {
                                $descripcion_movimiento = "Transferencia recibida";
                                $signo = "+";
                            }

                            echo htmlspecialchars($descripcion_movimiento);
                        ?>
                    </p>
                </div>
            </div>
            <div class="right">
                <p class="h4 <?php echo ($movimiento['id_remitente_entidad'] == $id_entidad) ? 'text--minus' : 'text--plus'; ?>">
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
            <button class="btn-primary" onclick="window.location.href='./movimientos.php'">Historial</button>
          </div>
        </div>
      </div>
      <div class="background"></div>
    </section>
    <script>
      function showLoaderAndRedirect(url) {
        const loader = document.getElementById('loader');
        loader.style.display = 'flex';

        setTimeout(function() {
          window.location.href = url;
        }, 500);
      }
    </script>
  </body>
</html>
