<?php
// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivo de configuración
$config = require '../../config/config.php';

// Incluir la clase de la base de datos
require '../../src/Models/Database.php';

// Instanciar la conexión a la base de datos
try {
    $db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    $pdo = $db->getConnection();
} catch (PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Verificar si los datos fueron enviados con el método POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Leer el contenido JSON del cuerpo de la solicitud
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Verificar si los datos fueron decodificados correctamente
    if ($data === null) {
        echo json_encode(['error' => 'Error al decodificar los datos JSON.']);
        exit();
    }

    // Verificar si todos los campos necesarios están presentes
    $campos_necesarios = ['nombre_entidad', 'cuit', 'tipo_entidad'];
    $campos_faltantes = [];

    foreach ($campos_necesarios as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            $campos_faltantes[] = $campo;
        }
    }

    if (count($campos_faltantes) > 0) {
        echo json_encode(['error' => "Faltan los siguientes datos: " . implode(', ', $campos_faltantes)]);
    } else {
        // Obtener los datos enviados desde la API
        $nombre_entidad = trim($data['nombre_entidad']);
        $cuit = trim($data['cuit']);
        $tipo_entidad = trim($data['tipo_entidad']);
        $saldo = isset($data['saldo']) ? $data['saldo'] : 0;  // Asignar saldo predeterminado si no está presente

        // Validar que CUIT sea numérico y tenga exactamente 11 dígitos
        if (!is_numeric($cuit) || strlen($cuit) != 11) {
            echo json_encode(['error' => 'El CUIT debe tener 11 dígitos y contener solo números.']);
            exit();
        }

        // Verificar que el saldo sea numérico y no negativo
        if (!is_numeric($saldo) || $saldo < 0) {
            echo json_encode(['error' => 'El saldo debe ser un número válido y no puede ser negativo.']);
            exit();
        }

        // Crear la consulta SQL para insertar la entidad
        $sql = "INSERT INTO entidades (nombre_entidad, cuit, tipo_entidad, saldo) 
                VALUES (:nombre_entidad, :cuit, :tipo_entidad, :saldo)";

        // Preparar la consulta con PDO
        $stmt = $pdo->prepare($sql);

        // Ejecutar la consulta
        try {
            $stmt->execute([
                ':nombre_entidad' => $nombre_entidad,
                ':cuit' => $cuit,
                ':tipo_entidad' => $tipo_entidad,
                ':saldo' => $saldo
            ]);

            echo json_encode(['success' => "Entidad registrada correctamente."]);

        } catch (PDOException $e) {
            echo json_encode(['error' => "Error al insertar la entidad: " . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['error' => 'Solo se aceptan solicitudes POST.']);
}
