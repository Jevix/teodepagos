<?php
session_start();
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Inicializar variables
$dniNombre = isset($_GET['Dni_Nombre']) ? trim($_GET['Dni_Nombre']) : '';
$usuarios = [];
$entidades = [];
$movimientos = [];

// Consultar los últimos movimientos de saldo de la entidad
$currentEntityId = $_SESSION['id_entidad'];
try {
    // Consulta para obtener los movimientos de la entidad o usuario
    $stmtMovimientos = $pdo->prepare("
       SELECT 
    ms.monto, 
    COALESCE(u.nombre_apellido, e.nombre_entidad) AS destinatario_nombre, 
    COALESCE(u.dni, e.cuit) AS destinatario_identificador, 
    e.tipo_entidad AS destinatario_tipo_entidad,
    ms.fecha
FROM movimientos_saldo ms
LEFT JOIN usuarios u ON ms.id_destinatario_usuario = u.id_usuario
LEFT JOIN entidades e ON ms.id_destinatario_entidad = e.id_entidad
WHERE ms.id_remitente_entidad = :id_entidad -- Solo movimientos donde la entidad de la sesión es el remitente
  AND ms.tipo_movimiento != 'Error' 
ORDER BY ms.fecha DESC 
LIMIT 5
    ");
    $stmtMovimientos->execute(['id_entidad' => $currentEntityId]);
    $movimientos = $stmtMovimientos->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si se han obtenido movimientos
    if (empty($movimientos)) {
        echo "<script>console.log('No se encontraron movimientos para esta entidad o usuario.');</script>";
    } else {
        echo "<script>console.log('Movimientos encontrados: " . json_encode($movimientos) . "');</script>";
    }
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
    <title>Buscar usuario o entidad</title>
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
      .recomendaciones {
        display: none;
      }
      .recomendaciones.show {
        display: block;
      }
      .componente--input--lupa {
  background: url("../../../img/search.svg") no-repeat 20px center !important;
}

      
    </style>
</head>
<body>
<section class="main">
    <nav class="navbar">
        <a href="index.php">
            <img src="../../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Transferir</p>
    </nav>

    <div class="container-white">
        <form action="buscar_usuario_encontrado.php" id="searchForm" method="get">
            <label for="usuario" class="h2">Buscar usuario o entidad</label>
            <input
                type="text"
                id="usuario"
                name="Dni_Nombre"
                placeholder="Busca por nombre o identificador..."
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
            <?php if (!empty($movimientos)): ?>
                <p class="h2">Anteriores transferencias</p>
                <?php foreach ($movimientos as $movimiento): ?>
    <div class="componente--usuario" onclick="redirigir(
        '<?php echo (strlen($movimiento['destinatario_identificador']) === 8) ? 'usuario' : 'entidad'; ?>', 
        '<?php echo htmlspecialchars($movimiento['destinatario_identificador']); ?>', 
        '<?php echo htmlspecialchars($movimiento['monto']); ?>')">
        <div class="left">
            <?php if (!empty($movimiento['destinatario_identificador'])): ?>
                <!-- Verificar la longitud del identificador para determinar si es usuario o entidad -->
                <?php if (strlen($movimiento['destinatario_identificador']) === 8): ?>
                    <img src="../../../img/user.svg" alt="Usuario" />
                <?php elseif (strlen($movimiento['destinatario_identificador']) === 11): ?>
                    <!-- Verificar si la entidad es un banco o una empresa -->
                    <?php if ($movimiento['destinatario_tipo_entidad'] === 'Banco'): ?>
                        <img src="../../../img/bank.svg" alt="Banco" />
                    <?php else: ?>
                        <img src="../../../img/empresa.svg" alt="Empresa" />
                    <?php endif; ?>
                <?php endif; ?>
                <div>
                    <p class="h5" id="h4"><?php echo htmlspecialchars($movimiento['destinatario_nombre']); ?></p>
                    <p class="hb">ID: <?php echo htmlspecialchars($movimiento['destinatario_identificador']); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <div class="right">
            <p class="h4 text--blue">$<?php echo number_format($movimiento['monto'], 0, ',', '.'); ?></p>
        </div>
    </div>
<?php endforeach; ?>
            <?php else: ?>
                <div class="ningun-movimiento">
  <div class="ningunsub-movimiento">
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 50%;"><path d="M20 28.3334V18.3334" stroke="black" stroke-width="2.5" stroke-linecap="round"/><path d="M19.9999 11.6667C20.9204 11.6667 21.6666 12.4129 21.6666 13.3333C21.6666 14.2538 20.9204 15 19.9999 15C19.0794 15 18.3333 14.2538 18.3333 13.3333C18.3333 12.4129 19.0794 11.6667 19.9999 11.6667Z" fill="black"/><path d="M3.33325 20C3.33325 12.1434 3.33325 8.21504 5.77325 5.77337C8.21659 3.33337 12.1433 3.33337 19.9999 3.33337C27.8566 3.33337 31.7849 3.33337 34.2249 5.77337C36.6666 8.21671 36.6666 12.1434 36.6666 20C36.6666 27.8567 36.6666 31.785 34.2249 34.225C31.7866 36.6667 27.8566 36.6667 19.9999 36.6667C12.1433 36.6667 8.21492 36.6667 5.77325 34.225C3.33325 31.7867 3.33325 27.8567 3.33325 20Z" stroke="black" stroke-width="2.5"/></svg>
    <p class="h2 text--light" style="color: #17214680; margin-top: 10px;">Todavía no tenes ningún movimiento.</p>
  </div>
</div>
            <?php endif; ?>
        </div>
        <div class="background"></div>
    </div>
</section>

<script>
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
