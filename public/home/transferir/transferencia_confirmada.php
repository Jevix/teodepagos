<?php
// Verificar si se ha pasado el monto y el nombre del destinatario
if (!isset($_GET['monto']) || (!isset($_GET['dni']) && !isset($_GET['cuit']))) {
    die("Faltan datos para completar la confirmación de la transferencia.");
}

$monto = htmlspecialchars($_GET['monto']);
$fecha = date('Y-m-d H:i:s');  // Formato de fecha y hora actual
$nombre = isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : '';
$dni = isset($_GET['dni']) ? htmlspecialchars($_GET['dni']) : '';
$cuit = isset($_GET['cuit']) ? htmlspecialchars($_GET['cuit']) : '';
$nombre_entidad = isset($_GET['nombre_entidad']) ? htmlspecialchars($_GET['nombre_entidad']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia enviada</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
        }
    </style>
</head>
<body>
<section class="transferencia-exitosa">
    <nav class="navbar">
        <a href="../index.php">
            <img src="../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
    </nav>
    <div class="container">
        <div class="container-1">
            <img src="../../img/Congrats.svg" alt="" class="icono-exito" />
            <p class="h2">Dinero transferido correctamente</p>
        </div>
        <div class="container-2">
            <div class="datos-transferencia">
                <p class="h2 left">Dinero transferido</p>
                <p class="h2 right">$<?= number_format($monto, 0, '.', ''); ?></p>
            </div>
            <div class="datos-transferencia">
                <p class="h2 left">Día y horario</p>
                <p class="h2 right"><?= $fecha; ?></p>
            </div>
            <div class="datos-transferencia">
                <p class="h2 left">Destinatario</p>
            </div>

            <div class="transferencia">
                <div class="left">
                    <div>
                        <?php if ($dni): ?>
                            <!-- Mostrar destinatario por DNI -->
                            <p class="h4"><?= $nombre; ?></p>
                            <p class="hb">DNI: <?= $dni; ?></p>
                        <?php elseif ($cuit): ?>
                            <!-- Mostrar destinatario por CUIT -->
                            <p class="h4"><?= $nombre_entidad; ?></p>
                            <p class="hb">CUIT: <?= $cuit; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-3">
            <button class="btn-primary" onclick="redireccionar('./index.php')">Volver a transferir</button>
            <button class="btn-secondary" onclick="redireccionar('../index.php')">Ir al inicio</button>
        </div>
    </div>
</section>
<script>
    function redireccionar(enlace) {
        window.location.href = enlace;
    }
</script>
</body>
</html>
