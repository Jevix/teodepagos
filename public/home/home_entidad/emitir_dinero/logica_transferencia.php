<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
    exit;
}

// Verificar si la solicitud es por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../error');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Verificar si se recibió el identificador (DNI o CUIT) y el tipo de emisión
if (!isset($_POST['identificador'], $_POST['tipo_emision'])) {
    header('Location: ../../index.php');
    exit;
}

// Obtener el monto de la transferencia
$monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;

// Obtener el tipo de emisión (Recarga o Préstamo)
$tipo_emision = htmlspecialchars($_POST['tipo_emision']);

// ID de la entidad remitente (la entidad que está haciendo la transferencia)
$id_remitente_entidad = $_SESSION['id_entidad'];

// Obtener el identificador (DNI o CUIT)
$identificador = $_POST['identificador'];

$pdo->beginTransaction();

try {
    // Inicializar variables de destinatario
    $id_destinatario_usuario = null;
    $id_destinatario_entidad = null;
    $nombre_destinatario = '';
    $nombre_entidad = '';  // Para almacenar el nombre de la entidad si es un CUIT

    // Verificar si el identificador es un DNI (8 dígitos) o un CUIT (11 dígitos)
    if (strlen($identificador) === 8) {
        // Buscar al usuario por DNI
        $stmt = $pdo->prepare("SELECT id_usuario, nombre_apellido FROM usuarios WHERE dni = :identificador");
        $stmt->execute(['identificador' => $identificador]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($destinatario) {
            $id_destinatario_usuario = $destinatario['id_usuario'];
            $nombre_destinatario = $destinatario['nombre_apellido'];
        } else {
            throw new Exception("Usuario no encontrado.");
        }
    } elseif (strlen($identificador) === 11) {
        // Buscar la entidad por CUIT
        $stmt = $pdo->prepare("SELECT id_entidad, nombre_entidad FROM entidades WHERE cuit = :identificador");
        $stmt->execute(['identificador' => $identificador]);
        $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($entidad) {
            $id_destinatario_entidad = $entidad['id_entidad'];
            $nombre_destinatario = $entidad['nombre_entidad'];  // Nombre del destinatario si es entidad
            $nombre_entidad = $entidad['nombre_entidad'];  // Almacenar el nombre de la entidad para el formulario
        } else {
            throw new Exception("Entidad no encontrada.");
        }
    } else {
        throw new Exception("Identificador inválido.");
    }

    // Como es un banco, no importa su saldo, solo actualizamos el saldo del destinatario

    // Sumar el monto al saldo del destinatario si es un usuario
    if ($id_destinatario_usuario) {
        $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :monto WHERE id_usuario = :id_destinatario_usuario");
        $stmt->execute(['monto' => $monto, 'id_destinatario_usuario' => $id_destinatario_usuario]);
    }

    // Sumar el monto al saldo de la entidad si es una entidad
    if ($id_destinatario_entidad) {
        $stmt = $pdo->prepare("UPDATE entidades SET saldo = saldo + :monto WHERE id_entidad = :id_destinatario_entidad");
        $stmt->execute(['monto' => $monto, 'id_destinatario_entidad' => $id_destinatario_entidad]);
    }

    // Registrar la transferencia en la tabla de movimientos_saldo
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_saldo 
        (id_remitente_entidad, id_destinatario_usuario, id_destinatario_entidad, monto, tipo_movimiento, fecha) 
        VALUES (:id_remitente_entidad, :id_destinatario_usuario, :id_destinatario_entidad, :monto, :tipo_movimiento, NOW())
    ");
    
    // Utilizamos el valor de tipo_emision para tipo_movimiento
    $stmt->execute([
        'id_remitente_entidad' => $id_remitente_entidad,
        'id_destinatario_usuario' => $id_destinatario_usuario,
        'id_destinatario_entidad' => $id_destinatario_entidad,
        'monto' => $monto,
        'tipo_movimiento' => $tipo_emision  // Aquí usamos el tipo_emision en lugar de "Egreso"
    ]);

    // Confirmar la transacción
    $pdo->commit();

    // Redirigir a la página de confirmación usando POST, incluyendo nombre_entidad si es necesario
    echo '
        <form id="confirmForm" action="transferencia_confirmada.php" method="POST">
            <input type="hidden" name="monto" value="' . $monto . '">
            <input type="hidden" name="nombre" value="' . htmlspecialchars($nombre_destinatario) . '">
            <input type="hidden" name="identificador" value="' . htmlspecialchars($identificador) . '">
            <input type="hidden" name="nombre_entidad" value="' . htmlspecialchars($nombre_entidad) . '">  <!-- Aquí agregamos el campo -->
            <input type="hidden" name="fecha" value="' . htmlspecialchars(date("Y-m-d H:i:s")) . '">
        </form>
        <script>document.getElementById("confirmForm").submit();</script>
    ';
    exit;
} catch (Exception $e) {
    // En caso de error, realizar rollback de la transacción
    $pdo->rollBack();
    echo 'Error en la transferencia: ' . $e->getMessage();
    exit;
}
