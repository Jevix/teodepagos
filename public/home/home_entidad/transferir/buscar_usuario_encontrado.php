<?php
session_start();
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
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
$error = '';

// Obtener el ID de la entidad actual desde la sesión
$currentEntityId = $_SESSION['id_entidad'];

// Proceder con la búsqueda solo si se ha ingresado `Dni_Nombre`
if ($dniNombre && empty($error)) {
    try {
        // Consulta para buscar usuarios, excluyendo usuarios de tipo "miembro" de bancos y excluyendo la entidad en sesión
        $queryUsuarios = "
            SELECT u.nombre_apellido, u.dni 
            FROM usuarios u 
            LEFT JOIN entidades e ON u.id_entidad = e.id_entidad 
            WHERE (u.nombre_apellido LIKE :dniNombre OR u.dni LIKE :dniNombre)
            AND (u.tipo_usuario != 'miembro' OR e.tipo_entidad != 'banco' OR e.id_entidad IS NULL)
            AND (e.id_entidad != :currentEntityId OR u.id_entidad IS NULL)
        ";

        // Consulta para buscar entidades (empresas y bancos), excluyendo la entidad en sesión
        $queryEntidades = "
            SELECT nombre_entidad, cuit, tipo_entidad 
            FROM entidades 
            WHERE (nombre_entidad LIKE :dniNombre OR cuit LIKE :dniNombre) 
            AND id_entidad != :currentEntityId
        ";

        // Ejecutar la consulta en la tabla `usuarios`
        $stmtUsuarios = $pdo->prepare($queryUsuarios);
        $stmtUsuarios->execute([
            'dniNombre' => "%$dniNombre%",
            'currentEntityId' => $currentEntityId
        ]);
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Ejecutar la consulta en la tabla `entidades`
        $stmtEntidades = $pdo->prepare($queryEntidades);
        $stmtEntidades->execute([
            'dniNombre' => "%$dniNombre%",
            'currentEntityId' => $currentEntityId
        ]);
        $entidades = $stmtEntidades->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error = "Error en la consulta: " . $e->getMessage();
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
        }
    </style>
</head>
<body>
<section class="main">
    <nav class="navbar">
        <a href="buscar_usuario.php">
            <img src="../../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Transferir</p>
    </nav>
    <div class="container-white">
        <form action="" method="GET" id="searchForm">
            <label for="usuario" class="h2">Buscar usuario o entidad</label>
            <input
                type="text"
                name="Dni_Nombre"
                id="usuario"
                placeholder="Busca por nombre o identificador..."
                class="componente--input--lupa"
                value="<?php echo htmlspecialchars($dniNombre); ?>"
            />
            
            <!-- Mostrar los resultados de la búsqueda -->
            <?php if ($dniNombre && (count($usuarios) > 0 || count($entidades) > 0)): ?>
                <!-- Mostrar usuarios -->
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="transferencia corto" onclick="window.location.href='procesar_transferencia.php?dni=<?php echo htmlspecialchars($usuario['dni']); ?>'">
                        <div class="left">
                            <!-- Mostrar ícono de usuario por defecto -->
                            <img src="../../../img/user.svg" alt="Usuario" />
                            <p class="h5"><?php echo htmlspecialchars($usuario['nombre_apellido']); ?></p>
                            <p class="hb">DNI: <?php echo htmlspecialchars($usuario['dni']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Mostrar entidades (empresas o bancos) -->
                <?php foreach ($entidades as $entidad): ?>
                    <div class="transferencia corto" onclick="window.location.href='procesar_transferencia.php?cuit=<?php echo htmlspecialchars($entidad['cuit']); ?>'">
                        <div class="left">
                            <!-- Determinar si la entidad es un banco o una empresa para mostrar el ícono adecuado -->
                            <img src="../../../img/<?php echo ($entidad['tipo_entidad'] === 'Banco') ? 'bank' : 'empresa'; ?>.svg" alt="<?php echo htmlspecialchars($entidad['tipo_entidad']); ?>" />
                            <p class="h5"><?php echo htmlspecialchars($entidad['nombre_entidad']); ?></p>
                            <p class="hb">CUIT: <?php echo htmlspecialchars($entidad['cuit']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($dniNombre): ?>
                <p>No se encontraron resultados.</p>
            <?php endif; ?>

            <button
                type="submit"
                class="btn-primary submit--off"
                id="submitButton"
                disabled
            >
                Buscar cuenta
            </button>
        </form>
        <div class="background"></div>

    </div>
</section>

<script>
    const form = document.getElementById("searchForm");
    const submitButton = document.getElementById("submitButton");
    const usuarioInput = document.getElementById("usuario");

    form.addEventListener("input", () => {
        const usuario = usuarioInput.value.trim();
        submitButton.disabled = usuario.length < 3; // Habilita el botón solo si hay al menos 3 caracteres
        if (usuario) {
            submitButton.classList.remove("submit--off");
            submitButton.classList.add("submit--on");
        } else {
            submitButton.classList.remove("submit--on");
            submitButton.classList.add("submit--off");
        }
    });
</script>
</body>
</html>