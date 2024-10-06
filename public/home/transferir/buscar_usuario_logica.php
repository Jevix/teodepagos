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

// Establecer el encabezado de la respuesta como JSON
header('Content-Type: application/json');

// Inicializar el array de respuesta
$response = ['usuarios' => [], 'entidades' => []];

// Obtener el parámetro `Dni_Nombre` y el `id_usuario` de la sesión
$dniNombre = isset($_GET['Dni_Nombre']) ? trim($_GET['Dni_Nombre']) : '';
$currentUserId = $_SESSION['id_usuario'];

// Verificar el DNI del usuario actual en la base de datos usando `id_usuario`
$currentUserDni = '';
try {
    $stmtDni = $pdo->prepare("SELECT dni FROM usuarios WHERE id_usuario = :id_usuario");
    $stmtDni->execute(['id_usuario' => $currentUserId]);
    $currentUser = $stmtDni->fetch(PDO::FETCH_ASSOC);
    if ($currentUser) {
        $currentUserDni = $currentUser['dni'];
    } else {
        echo json_encode(['error' => "Usuario no encontrado."]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => "Error al obtener el DNI del usuario actual: " . $e->getMessage()]);
    exit;
}

// Proceder con la búsqueda solo si se ha ingresado `Dni_Nombre`
if ($dniNombre) {
    try {
        // Consultas SQL para buscar en las tablas `usuarios` y `entidades`, excluyendo al usuario actual por DNI
        $queryUsuarios = "SELECT nombre_apellido, dni FROM usuarios WHERE (nombre_apellido LIKE :dniNombre OR dni LIKE :dniNombre) AND dni != :currentUserDni";
        $queryEntidades = "SELECT nombre_entidad, cuit FROM entidades WHERE nombre_entidad LIKE :dniNombre OR cuit LIKE :dniNombre";
        
        // Ejecutar la consulta en la tabla `usuarios`, excluyendo el DNI del usuario actual
        $stmtUsuarios = $pdo->prepare($queryUsuarios);
        $stmtUsuarios->execute([
            'dniNombre' => "%$dniNombre%",
            'currentUserDni' => $currentUserDni
        ]);
        $response['usuarios'] = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Ejecutar la consulta en la tabla `entidades`
        $stmtEntidades = $pdo->prepare($queryEntidades);
        $stmtEntidades->execute(['dniNombre' => "%$dniNombre%"]);
        $response['entidades'] = $stmtEntidades->fetchAll(PDO::FETCH_ASSOC);

        // Devolver los resultados en formato JSON
        echo json_encode($response);
    } catch (PDOException $e) {
        // En caso de error en la consulta, devolver el mensaje de error en JSON
        echo json_encode(['error' => "Error en la consulta: " . $e->getMessage()]);
    }
} else {
    // Si no hay un valor en `Dni_Nombre`, devolver un mensaje de error en JSON
    echo json_encode(['error' => "Por favor, ingresa un nombre o DNI para buscar."]);
}
?>
