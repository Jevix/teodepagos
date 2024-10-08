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

// Establecer el encabezado de la respuesta como JSON
header('Content-Type: application/json');

// Inicializar el array de respuesta
$response = ['usuarios' => [], 'entidades' => []];

// Obtener el parámetro `Dni_Nombre` y el `id_entidad` de la sesión
$dniNombre = isset($_GET['Dni_Nombre']) ? trim($_GET['Dni_Nombre']) : '';
$currentEntidadId = $_SESSION['id_entidad'];

// Verificar el CUIT de la entidad actual en la base de datos usando `id_entidad`
$currentEntidadCuit = '';
try {
    $stmtCuit = $pdo->prepare("SELECT cuit FROM entidades WHERE id_entidad = :id_entidad");
    $stmtCuit->execute(['id_entidad' => $currentEntidadId]);
    $currentEntidad = $stmtCuit->fetch(PDO::FETCH_ASSOC);
    if ($currentEntidad) {
        $currentEntidadCuit = $currentEntidad['cuit'];
    } else {
        echo json_encode(['error' => "Entidad no encontrada."]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => "Error al obtener el CUIT de la entidad actual: " . $e->getMessage()]);
    exit;
}

// Proceder con la búsqueda solo si se ha ingresado `Dni_Nombre`
if ($dniNombre) {
    try {
        // Consultas SQL para buscar en las tablas `usuarios` y `entidades`, excluyendo la entidad actual por CUIT
        $queryUsuarios = "SELECT nombre_apellido, dni FROM usuarios WHERE (nombre_apellido LIKE :dniNombre OR dni LIKE :dniNombre)";
        $queryEntidades = "SELECT nombre_entidad, cuit FROM entidades WHERE (nombre_entidad LIKE :dniNombre OR cuit LIKE :dniNombre) AND cuit != :currentEntidadCuit";

        // Ejecutar la consulta en la tabla `usuarios`
        $stmtUsuarios = $pdo->prepare($queryUsuarios);
        $stmtUsuarios->execute([
            'dniNombre' => "%$dniNombre%"
        ]);
        $response['usuarios'] = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

        // Ejecutar la consulta en la tabla `entidades`, excluyendo la entidad actual
        $stmtEntidades = $pdo->prepare($queryEntidades);
        $stmtEntidades->execute([
            'dniNombre' => "%$dniNombre%",
            'currentEntidadCuit' => $currentEntidadCuit
        ]);
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
