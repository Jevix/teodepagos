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
        echo "Error al decodificar los datos JSON.";
        exit();
    }

    // Verificar si todos los campos necesarios están presentes
    $campos_necesarios = ['nombre_apellido', 'dni', 'password', 'tipo_usuario'];
    $campos_faltantes = [];

    foreach ($campos_necesarios as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            $campos_faltantes[] = $campo;
        }
    }

    if (count($campos_faltantes) > 0) {
        echo "Faltan los siguientes datos: " . implode(', ', $campos_faltantes);
    } else {
        $nombre_apellido = trim($data['nombre_apellido']);
        $dni = $data['dni'];
        $password = isset($data['password']) ? $data['password'] : "default_password";  // Asignar contraseña por defecto si no existe
        $tipo_usuario = $data['tipo_usuario'];
        $saldo = isset($data['saldo']) ? $data['saldo'] : 0;  // Asignar saldo por defecto si no existe
        $id_entidad = isset($data['id_entidad']) ? $data['id_entidad'] : null;  // Solo será usado si es 'Miembro'

        // Verificar si el saldo es numérico
        if (!is_numeric($saldo)) {
            echo "El saldo debe ser un número válido.";
        } else {
            // Crear la consulta SQL para insertar el usuario
            $sql = "INSERT INTO usuarios (nombre_apellido, dni, password, tipo_usuario, id_entidad, saldo) 
                    VALUES (:nombre_apellido, :dni, :password, :tipo_usuario, :id_entidad, :saldo)";

            // Preparar la consulta con PDO
            $stmt = $pdo->prepare($sql);
            
            // Verificar si es un Usuario o un Miembro
            if ($tipo_usuario === 'Usuario') {
                $id_entidad = null;  // Asegurar que id_entidad sea NULL si el tipo es 'Usuario'
            }

            // Ejecutar la consulta
            try {
                $stmt->execute([
                    ':nombre_apellido' => $nombre_apellido,
                    ':dni' => $dni,
                    ':password' => $password,  // Asegúrate de cifrar las contraseñas en producción
                    ':tipo_usuario' => $tipo_usuario,
                    ':id_entidad' => $id_entidad,
                    ':saldo' => $saldo
                ]);

                echo "Usuario registrado correctamente.";

            } catch (PDOException $e) {
                echo "Error al insertar el usuario: " . $e->getMessage();
            }
        }
    }
} else {
    echo "Solo se aceptan solicitudes POST.";
}
?>
