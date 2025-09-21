<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login');  // Redirigir a la página de login si no está autenticado
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Obtener los últimos movimientos de saldo del usuario actual de tipo "egreso"
$currentUserId = $_SESSION['id_usuario'];
$movimientos = [];

try {
    // Consulta con LEFT JOIN para unir información de usuarios y entidades
    $stmtMovimientos = $pdo->prepare("
        SELECT 
    ms.monto, 
    ms.tipo_movimiento, 
    ms.fecha, 
    u.nombre_apellido AS destinatario_nombre, 
    u.dni AS destinatario_dni,
    e.nombre_entidad AS destinatario_entidad, 
    e.cuit AS destinatario_cuit,
    e.tipo_entidad AS tipo_entidad  -- Agregamos el tipo de entidad
FROM movimientos_saldo ms
LEFT JOIN usuarios u ON ms.id_destinatario_usuario = u.id_usuario
LEFT JOIN entidades e ON ms.id_destinatario_entidad = e.id_entidad
WHERE ms.id_remitente_usuario = :id_usuario 
AND ms.tipo_movimiento = 'Egreso'
ORDER BY ms.fecha DESC 
LIMIT 5;
    ");
    $stmtMovimientos->execute(['id_usuario' => $currentUserId]);
    $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al obtener movimientos: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buscar usuario</title>
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
<section class="buscar-usuario">
    <nav class="navbar">
        <a href="./index.php">
            <img src="../../img/back.svg" alt="" />
        </a>
        <p class="h2">Transferir</p>
    </nav>
    <div class="container">
        <form action="buscar_usuario_encontrado.php" method="get" id="searchForm">
            <label for="usuario" class="h2">Buscar usuario</label>
            <input
                type="text"
                name="Dni_Nombre"
                id="usuario"
                placeholder="Busca por nombre o dni..."
            />
            <button
                type="submit"
                class="btn-primary submit--off"
                id="submitButton"
                disabled
            >
                Buscar cuenta
            </button>
        </form>

        <div class="container-anteriores">
            <p class="h2">Anteriores transferencias</p>
            <?php if (!empty($movimientos)): ?>
    <?php foreach ($movimientos as $movimiento): ?>
        <div class="transferencia" 
            <?php if (!empty($movimiento['destinatario_dni'])): ?>
                onclick="redirigir('usuario', '<?= htmlspecialchars($movimiento['destinatario_dni']); ?>', '<?= htmlspecialchars($movimiento['monto']); ?>')"
            <?php elseif (!empty($movimiento['destinatario_cuit'])): ?>
                onclick="redirigir('entidad', '<?= htmlspecialchars($movimiento['destinatario_cuit']); ?>', '<?= htmlspecialchars($movimiento['monto']); ?>')"
            <?php endif; ?>
        >
            <div class="left">
                <?php if (!empty($movimiento['destinatario_dni'])): ?>
                    <img src="../../img/user.svg" alt="Usuario" />
                    <div>
                        <p class="h5"><?= htmlspecialchars($movimiento['destinatario_nombre']); ?></p>
                        <p class="hb">DNI: <?= htmlspecialchars($movimiento['destinatario_dni']); ?></p>
                    </div>
                <?php elseif (!empty($movimiento['destinatario_cuit'])): ?>
                    <?php if ($movimiento['tipo_entidad'] === 'Banco'): ?>
                        <img src="../../img/bank.svg" alt="Banco" />
                    <?php elseif ($movimiento['tipo_entidad'] === 'Empresa'): ?>
                        <img src="../../img/company.svg" alt="Empresa" />
                    <?php endif; ?>
                    <div>
                        <p class="h5"><?= htmlspecialchars($movimiento['destinatario_entidad']); ?></p>
                        <p class="hb">CUIT: <?= htmlspecialchars($movimiento['destinatario_cuit']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="right">
            <p class="h4 text--blue">$<?= number_format($movimiento['monto'], 0, ',', '.'); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Aún no realizaste transferencias.</p>
<?php endif; ?>

        </div>
    </div>
</section>
<script>
    const form = document.getElementById("searchForm");
    const submitButton = document.getElementById("submitButton");

    form.addEventListener("input", () => {
        const usuario = document.getElementById("usuario").value.trim();
        if (usuario.length >= 3) {
            submitButton.classList.remove("submit--off");
            submitButton.classList.add("submit--on");
            submitButton.disabled = false; // Habilita el botón
        } else {
            submitButton.classList.remove("submit--on");
            submitButton.classList.add("submit--off");
            submitButton.disabled = true; // Deshabilita el botón
        }
    });

    function redirigir(tipo, valor, monto) {
    // Formatear el monto en miles con punto
    let montoFormateado = parseInt(monto).toLocaleString('de-DE'); // Esto convierte a '10.000' por ejemplo

    if (tipo === 'usuario') {
        // Redirigir a la página de usuario con el DNI y monto formateado
        window.location.href = `procesar_transferencia.php?dni=${valor}&monto=${montoFormateado}`;
    } else if (tipo === 'entidad') {
        // Redirigir a la página de entidad con el CUIT y monto formateado
        window.location.href = `procesar_transferencia.php?cuit=${valor}&monto=${montoFormateado}`;
    }
}

</script>
</body>
</html>
