<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Inicializar variables
$dniNombre = isset($_GET['Dni_Nombre']) ? trim($_GET['Dni_Nombre']) : '';
$usuarios = [];
$entidades = [];
$movimientos = [];

// Consultar los últimos movimientos de saldo del usuario
$currentUserId = $_SESSION['id_usuario'];
try {
    // Consulta para obtener los movimientos
    $stmtMovimientos = $pdo->prepare("
       SELECT 
        ms.monto, 
        u.nombre_apellido AS destinatario_nombre, 
        u.dni AS destinatario_dni, 
        e.nombre_entidad AS destinatario_entidad, 
        e.cuit AS destinatario_cuit,
        e.tipo_entidad AS destinatario_tipo_entidad
    FROM movimientos_saldo ms
    LEFT JOIN usuarios u ON ms.id_destinatario_usuario = u.id_usuario
    LEFT JOIN entidades e ON ms.id_destinatario_entidad = e.id_entidad
    WHERE ms.id_remitente_usuario = :id_usuario 
    ORDER BY ms.fecha DESC 
    LIMIT 5
    ");
    $stmtMovimientos->execute(['id_usuario' => $currentUserId]);
    $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al obtener movimientos: " . $e->getMessage();
}

// Consultar usuarios y entidades por nombre o DNI ingresado
if ($dniNombre) {
    try {
        // Consulta para obtener usuarios
        $stmtUsuarios = $pdo->prepare("
            SELECT nombre_apellido, dni 
            FROM usuarios 
            WHERE nombre_apellido LIKE :dniNombre OR dni LIKE :dniNombre 
            LIMIT 5
        ");
        $stmtUsuarios->execute(['dniNombre' => "%$dniNombre%"]);
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Consulta para obtener entidades
        $stmtEntidades = $pdo->prepare("
            SELECT nombre_entidad, cuit 
            FROM entidades 
            WHERE nombre_entidad LIKE :dniNombre OR cuit LIKE :dniNombre 
            LIMIT 5
        ");
        $stmtEntidades->execute(['dniNombre' => "%$dniNombre%"]);
        $entidades = $stmtEntidades->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error al buscar usuarios/entidades: " . $e->getMessage();
    }
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
      .recomendaciones {
        display: none;
      }
      .recomendaciones.show {
        display: block;
      }
    </style>
</head>
<body>
<section class="main">
    <nav class="navbar">
        <a href="index.php">
            <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Transferir</p>
    </nav>

    <div class="container-white">
        <form action="buscar_usuario_encontrado.php" id="searchForm" method="get">
            <label for="usuario" class="h2">Buscar usuario</label>
            <input
                type="text"
                id="usuario"
                name="Dni_Nombre"
                placeholder="Busca por nombre o dni..."
                class="componente--input--lupa"
                value="<?php echo htmlspecialchars($dniNombre); ?>"
            />
            <div id="recomendaciones" class="recomendaciones">
                <?php if ($dniNombre && (count($usuarios) > 0 || count($entidades) > 0)): ?>
                    <ul>
                        <?php foreach ($usuarios as $usuario): ?>
                            <li><?php echo htmlspecialchars($usuario['nombre_apellido']) . ' - ' . htmlspecialchars($usuario['dni']); ?></li>
                        <?php endforeach; ?>
                        <?php foreach ($entidades as $entidad): ?>
                            <li><?php echo htmlspecialchars($entidad['nombre_entidad']) . ' - ' . htmlspecialchars($entidad['cuit']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No se encontraron resultados</p>
                <?php endif; ?>
            </div>

            <button
                type="submit"
                class="btn-primary submit--off"
                id="submitButton"
                disabled
            >
                Buscar cuenta
            </button>
        </form>

        <!-- Historial de transferencias -->
        <div class="container-anteriores" id="historialTransferencias">
            <p class="h2">Anteriores transferencias</p>
            <?php if (!empty($movimientos)): ?>
                <?php foreach ($movimientos as $movimiento): ?>
                    <div class="componente--usuario" onclick="redirigir(
                        '<?php echo (!empty($movimiento['destinatario_dni'])) ? 'usuario' : 'entidad'; ?>', 
                        '<?php echo (!empty($movimiento['destinatario_dni'])) ? htmlspecialchars($movimiento['destinatario_dni']) : htmlspecialchars($movimiento['destinatario_cuit']); ?>', 
                        '<?php echo htmlspecialchars($movimiento['monto']); ?>')">
                        <div class="left">
                            <?php if (!empty($movimiento['destinatario_dni'])): ?>
                                <!-- Logo de usuario -->
                                <img src="../../img/user.svg" alt="Usuario" />
                                <div>
                                    <p class="h5"><?php echo htmlspecialchars($movimiento['destinatario_nombre']); ?></p>
                                    <p class="hb">DNI: <?php echo htmlspecialchars($movimiento['destinatario_dni']); ?></p>
                                </div>
                            <?php elseif (!empty($movimiento['destinatario_cuit'])): ?>
                                <!-- Verificar si la entidad es un banco o una empresa -->
                                <?php if ($movimiento['destinatario_tipo_entidad'] === 'Banco'): ?>
                                    <!-- Logo de banco -->
                                    <img src="../../img/bank.svg" alt="Banco" />
                                <?php else: ?>
                                    <!-- Logo de empresa -->
                                    <img src="../../img/empresa.svg" alt="Empresa" />
                                <?php endif; ?>
                                <div>
                                    <p class="h5"><?php echo htmlspecialchars($movimiento['destinatario_entidad']); ?></p>
                                    <p class="hb">CUIT: <?php echo htmlspecialchars($movimiento['destinatario_cuit']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="right">
                            <p class="h4 text--blue">$<?php echo number_format($movimiento['monto'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay transferencias anteriores</p>
            <?php endif; ?>
        </div>
        <div class="background"></div>
    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", () => {
    const usuario = usuarioInput.value.trim();
    if (usuario) {
        submitButton.classList.remove("submit--off");
        submitButton.classList.add("submit--on");
        submitButton.disabled = false;
    }
});
    const form = document.getElementById("searchForm");
    const submitButton = document.getElementById("submitButton");
    const usuarioInput = document.getElementById("usuario");

    form.addEventListener("input", () => {
        const usuario = usuarioInput.value.trim();

        if (usuario) {
            submitButton.classList.remove("submit--off");
            submitButton.classList.add("submit--on");
            submitButton.disabled = false;
        } else {
            submitButton.classList.remove("submit--on");
            submitButton.classList.add("submit--off");
            submitButton.disabled = true;
        }
    });

    function redirigir(tipo, valor, monto) {
    let montoFormateado = parseInt(monto).toLocaleString('de-DE');
    if (tipo === 'usuario') {
        window.location.href = `procesar_transferencia.php?dni=${valor}&monto=${montoFormateado}`;
    } else if (tipo === 'entidad') {
        window.location.href = `procesar_transferencia.php?cuit=${valor}&monto=${montoFormateado}`;
    }
}
</script>
</body>
</html>