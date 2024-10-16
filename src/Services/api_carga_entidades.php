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
    $campos_necesarios = ['nombre_apellido', 'dni', 'password', 'tipo_usuario'];
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
        $nombre_apellido = trim($data['nombre_apellido']);
        $dni = trim($data['dni']);
        $password = isset($data['password']) ? $data['password'] : "default_password";  // Asignar contraseña predeterminada si no se especifica
        $tipo_usuario = trim($data['tipo_usuario']);
        $saldo = isset($data['saldo']) ? floatval($data['saldo']) : 0;  // Asignar saldo predeterminado si no está presente
        $id_entidad = isset($data['id_entidad']) ? intval($data['id_entidad']) : null;  // Solo será usado si es 'Miembro'

        // Validar el tipo de usuario
        if (!in_array($tipo_usuario, ['Usuario', 'Miembro'])) {
            echo json_encode(['error' => 'Tipo de usuario no válido.']);
            exit();
        }

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

            echo json_encode(['success' => "Usuario registrado correctamente."]);

        } catch (PDOException $e) {
            echo json_encode(['error' => "Error al insertar el usuario: " . $e->getMessage()]);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['accion']) && $_GET['accion'] === 'cargar_usuarios') {
    // Aquí ejecutamos el script Python al recibir una solicitud GET para cargar usuarios
    $output = shell_exec('python3 api_carga_usuarios.py 2>&1');
    
    // Verificamos si se ejecutó correctamente
    if (strpos($output, 'Error') === false) {
        echo "Se cargaron los usuarios correctamente.";
    } else {
        echo "Error al cargar los usuarios: $output";
    }
} else {
    echo json_encode(['error' => 'Solo se aceptan solicitudes POST para registrar usuarios o GET con "accion=cargar_usuarios" para cargar usuarios.']);
}
