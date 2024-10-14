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
$error = '';

// Obtener el DNI del usuario actual usando su `id_usuario`
$currentUserId = $_SESSION['id_usuario'];
$currentUserDni = '';
try {
    $stmtDni = $pdo->prepare("SELECT dni FROM usuarios WHERE id_usuario = :id_usuario");
    $stmtDni->execute(['id_usuario' => $currentUserId]);
    $currentUser = $stmtDni->fetch(PDO::FETCH_ASSOC);
    if ($currentUser) {
        $currentUserDni = $currentUser['dni'];
    } else {
        $error = "Usuario no encontrado.";
    }
} catch (PDOException $e) {
    $error = "Error al obtener el DNI del usuario actual: " . $e->getMessage();
}

// Proceder con la búsqueda solo si se ha ingresado `Dni_Nombre`
if ($dniNombre && empty($error)) {
    try {
        // Consulta para buscar usuarios excluyendo al usuario actual y a usuarios de tipo `miembro` de bancos
        $queryUsuarios = "
    SELECT u.nombre_apellido, u.dni 
    FROM usuarios u 
    LEFT JOIN entidades e ON u.id_entidad = e.id_entidad 
    WHERE (u.nombre_apellido LIKE :dniNombre OR u.dni LIKE :dniNombre) 
    AND u.dni != :currentUserDni 
    AND (e.tipo_entidad IS NULL OR e.tipo_entidad != 'Banco')  -- Excluir si están asociados a un Banco
";
        
        // Consulta para buscar entidades por nombre o CUIT
        $queryEntidades = "SELECT nombre_entidad, cuit, tipo_entidad FROM entidades WHERE (nombre_entidad LIKE :dniNombre OR cuit LIKE :dniNombre)";
        
        // Ejecutar la consulta en la tabla `usuarios`
        $stmtUsuarios = $pdo->prepare($queryUsuarios);
        $stmtUsuarios->execute([
            'dniNombre' => "%$dniNombre%",
            'currentUserDni' => $currentUserDni
        ]);
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Ejecutar la consulta en la tabla `entidades`
        $stmtEntidades = $pdo->prepare($queryEntidades);
        $stmtEntidades->execute(['dniNombre' => "%$dniNombre%"]);
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
    <title>Buscar usuario</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
        }
        .btn-primary {
            margin-top: 20px !important;
        }
        .ningun-movimiento {
            margin-top: 20px !important;
        }

    </style>
</head>
<body>
<section class="main">
    <nav class="navbar">
        <a href="buscar_usuario.php">
            <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Transferir</p>
    </nav>
    <div class="container-white">
        <form action="" method="GET" id="searchForm">
            <label for="usuario" class="h2">Buscar usuario</label>
            <input
                type="text"
                name="Dni_Nombre"
                id="usuario"
                placeholder="Busca por nombre o dni..."
                class="componente--input--lupa"
                value="<?php echo htmlspecialchars($dniNombre); ?>"
            />
            
            <!-- Mostrar los resultados de la búsqueda -->
            <?php if ($dniNombre && (count($usuarios) > 0 || count($entidades) > 0)): ?>
                <!-- Mostrar usuarios -->
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="transferencia corto" onclick="window.location.href='procesar_transferencia.php?dni=<?php echo htmlspecialchars($usuario['dni']); ?>'">
                        <div class="left">
                            <!-- Mostrar ícono de usuario -->
                            <img src="../../img/user.svg" alt="Usuario" /> <!-- Asegúrate de tener un ícono de usuario en esta ruta -->
                            <div>
                            <p class="h5"><?php echo htmlspecialchars($usuario['nombre_apellido']); ?></p>
                            <p class="hb">DNI: <?php echo htmlspecialchars($usuario['dni']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Mostrar entidades -->
                <?php foreach ($entidades as $entidad): ?>
                    <div class="transferencia corto" onclick="window.location.href='procesar_transferencia.php?cuit=<?php echo htmlspecialchars($entidad['cuit']); ?>'">
                        <div class="left">
                            <!-- Verificar si es un banco o una empresa para mostrar el ícono adecuado -->
                            <?php if ($entidad['tipo_entidad'] === 'Banco'): ?>
                                <img src="../../img/banco.svg" alt="Banco" /> <!-- Ícono para bancos -->
                            <?php else: ?>
                                <img src="../../img/empresa.svg" alt="Entidad" /> <!-- Ícono para otras entidades (empresas) -->
                            <?php endif; ?>
                            <div>
                                <p class="h5"><?php echo htmlspecialchars($entidad['nombre_entidad']); ?></p>
                                <p class="hb">CUIT: <?php echo htmlspecialchars($entidad['cuit']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($dniNombre): ?>
                <div class="ningun-movimiento">
  <div class="ningunsub-movimiento">
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 50%;"><path d="M20 28.3334V18.3334" stroke="black" stroke-width="2.5" stroke-linecap="round"/><path d="M19.9999 11.6667C20.9204 11.6667 21.6666 12.4129 21.6666 13.3333C21.6666 14.2538 20.9204 15 19.9999 15C19.0794 15 18.3333 14.2538 18.3333 13.3333C18.3333 12.4129 19.0794 11.6667 19.9999 11.6667Z" fill="black"/><path d="M3.33325 20C3.33325 12.1434 3.33325 8.21504 5.77325 5.77337C8.21659 3.33337 12.1433 3.33337 19.9999 3.33337C27.8566 3.33337 31.7849 3.33337 34.2249 5.77337C36.6666 8.21671 36.6666 12.1434 36.6666 20C36.6666 27.8567 36.6666 31.785 34.2249 34.225C31.7866 36.6667 27.8566 36.6667 19.9999 36.6667C12.1433 36.6667 8.21492 36.6667 5.77325 34.225C3.33325 31.7867 3.33325 27.8567 3.33325 20Z" stroke="black" stroke-width="2.5"/></svg>
    <p class="h2 text--light" style="color: #17214680; margin-top: 10px;">No se encontro ningun usuario y/o entindad</p>
  </div>
</div>
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
