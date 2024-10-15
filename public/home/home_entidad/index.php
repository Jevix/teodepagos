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
               COALESCE(remitente_entidad.nombre_entidad, remitente.nombre_apellido) AS remitente_nombre,
               COALESCE(destinatario_entidad.nombre_entidad, destinatario.nombre_apellido) AS destinatario_nombre,
               remitente_entidad.tipo_entidad AS remitente_tipo_entidad,
               destinatario_entidad.tipo_entidad AS destinatario_tipo_entidad
        FROM movimientos_saldo ms
        LEFT JOIN entidades AS remitente_entidad ON ms.id_remitente_entidad = remitente_entidad.id_entidad
        LEFT JOIN usuarios AS remitente ON ms.id_remitente_usuario = remitente.id_usuario
        LEFT JOIN entidades AS destinatario_entidad ON ms.id_destinatario_entidad = destinatario_entidad.id_entidad
        LEFT JOIN usuarios AS destinatario ON ms.id_destinatario_usuario = destinatario.id_usuario
        WHERE ms.id_remitente_entidad = :id_entidad OR ms.id_destinatario_entidad = :id_entidad
        ORDER BY ms.fecha DESC
   LIMIT 3;
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
      .bg-ventana-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5); /* Fondo oscuro semitransparente */
      backdrop-filter: blur(10px); /* Aplicar el desenfoque al fondo */
      display: none; /* Ocultar por defecto */
      justify-content: center;
      align-items: center;
      z-index: 1000; /* Asegurarse de que esté por encima de todo */
      }
      .ventana-modal {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
      z-index: 1001; /* El modal debe estar por encima del fondo */
      }
      .loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.7); /* Fondo blanco semitransparente */
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
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
                  <p class="documento"><?php echo $entidad['cuit']; ?></p>
                  <!-- Mostrar CUIT -->
                  <p class="nombre"><?php echo $entidad['nombre_entidad']; ?></p>
                  <!-- Mostrar nombre de la entidad -->
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
            <div onclick="showLoaderAndRedirect('emitir_dinero/buscar_usuario.php')">
               <img src="../../img/emitir.svg" alt="Emitir Dinero" style="width: 40px; height: 40px;" />
               <p class="hb">Emitir dinero</p>
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
            <p class="h2" style="color: #172146;">Movimientos</p>
            <div class="movimientos-container">
               <?php if ($movimientos): ?>
               <p class="fecha">Hoy</p>
               <?php foreach ($movimientos as $movimiento): ?>
               <div class="componente--movimiento">
                  <div class="left">
                     <?php
                        $img_src = '../../img/user.svg';  // Imagen por defecto para un usuario
                        
                        // Verificar si el movimiento es un Prestamo o una Recarga
                        if ($movimiento['tipo_movimiento'] == 'Prestamo' || $movimiento['tipo_movimiento'] == 'Recarga' || $movimiento['tipo_movimiento'] == 'Error') {
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
                  </div>
                  <div class="right">
                     <div class="arriba">
                        <p class="h5" id="h4">
                           <?php
                          if ($movimiento['id_remitente_entidad'] == $id_entidad) {
                           echo htmlspecialchars($movimiento['destinatario_nombre']);
                       } else {
                           echo htmlspecialchars($movimiento['remitente_nombre']);
                       }
                                
                              ?>
                        </p>
                        <p class="h4 <?php 
                          $descripcion_movimiento = '';
                          $signo = '';
                          
                          if ($movimiento['tipo_movimiento'] == 'Prestamo') {
                              $descripcion_movimiento = "Préstamo";
                              $signo = "+";
                          } elseif ($movimiento['tipo_movimiento'] == 'Recarga') {
                              $descripcion_movimiento = "Recarga de saldo";
                              $signo = "+";
                          } elseif ($movimiento['tipo_movimiento'] == 'Error') {
                              $descripcion_movimiento = "Error Bancario";
                              $signo = "-";
                          } elseif ($movimiento['id_remitente_entidad'] == $id_entidad) {
                              $descripcion_movimiento = "Transferencia enviada";
                              $signo = "-";
                          } else {
                              $descripcion_movimiento = "Transferencia recibida";
                              $signo = "+";
                          }
                          if ($movimiento['tipo_movimiento'] == 'Error') {
                           $clase_css = 'text--minus'; // Aplicar 'text--minus' si es un Error
                           echo $clase_css;
                        }else{
                          echo ($movimiento['id_remitente_entidad'] == $id_entidad) ? 'text--minus' : 'text--plus';    }?>">
                       


                          <?php echo $signo . "$" . number_format(abs($movimiento['monto']), 0, ',', '.'); ?>
                     </p>
                     </div>
                     <div class="abajo">
                        <p class="hb">
                           <?php
                              echo htmlspecialchars($descripcion_movimiento);
                              ?>
                        </p>
                        <p class="hb">
                        <?php
                           $fecha = new DateTime($movimiento['fecha']);
                           echo $fecha->format('H:i');
                           ?>
                     </p>
                     </div>
                  </div>
               </div>
               <?php endforeach; ?>
               <?php if (count($movimientos) >= 3): ?>
               <div class="container-btn">
                  <button class="btn-primary" onclick="window.location.href='./movimientos.php'">Historial de movimientos</button>
               </div>
               <?php endif; ?>
               <?php else: ?>
               <div style="text-align: center;">
                  <p class="h2 text--light" style="display: block;">Todavía no tenes ningún movimiento.</p>
               </div>
               <?php endif; ?>
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