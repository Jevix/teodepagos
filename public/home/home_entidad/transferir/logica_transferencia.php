<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Verificar si se recibieron los datos necesarios (DNI o CUIT y Monto)
if (!isset($_POST['dni']) && !isset($_POST['cuit'])) {
    die("No se ha proporcionado un DNI o CUIT válido.");
}

if (!isset($_POST['monto']) || $_POST['monto'] <= 0) {
    die("Monto inválido.");
}

$monto = floatval($_POST['monto']);
$dni = isset($_POST['dni']) ? $_POST['dni'] : null;
$cuit = isset($_POST['cuit']) ? $_POST['cuit'] : null;

// ID del remitente (la entidad que está haciendo la transferencia)
$id_remitente_entidad = $_SESSION['id_entidad'];

// Empezar la transacción
$pdo->beginTransaction();

try {
    // Buscar al destinatario (puede ser usuario o entidad)
    $id_destinatario_usuario = null;
    $id_destinatario_entidad = null;

    if ($dni) {
        // Buscar al usuario por DNI
        $stmt = $pdo->prepare("SELECT id_usuario, nombre_apellido, saldo FROM usuarios WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($destinatario) {
            $id_destinatario_usuario = $destinatario['id_usuario'];
            $saldo_destinatario = $destinatario['saldo'];
        } else {
            throw new Exception("No se encontró ningún usuario con el DNI proporcionado.");
        }
    } elseif ($cuit) {
        // Buscar la entidad por CUIT
        $stmt = $pdo->prepare("SELECT id_entidad, nombre_entidad, saldo, tipo_entidad FROM entidades WHERE cuit = :cuit");
        $stmt->execute(['cuit' => $cuit]);
        $entidad = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($entidad) {
            $id_destinatario_entidad = $entidad['id_entidad'];
            $saldo_entidad = $entidad['saldo'];
            $tipo_entidad_destinatario = $entidad['tipo_entidad']; // Tipo de entidad del destinatario (Banco, Empresa)
        } else {
            throw new Exception("No se encontró ninguna entidad con el CUIT proporcionado.");
        }
    }

    // Verificar si la entidad remitente es un banco, y si no es un banco, verificar el saldo.
    $stmt = $pdo->prepare("SELECT saldo, tipo_entidad FROM entidades WHERE id_entidad = :id_remitente");
    $stmt->execute(['id_remitente' => $id_remitente_entidad]);
    $remitente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($remitente['tipo_entidad'] !== 'Banco') {
        // Si la entidad remitente NO es un banco, verificar su saldo
        if ($remitente['saldo'] < $monto) {
            throw new Exception("Saldo insuficiente para realizar la transferencia.");
        }

        // Restar el monto del saldo de la entidad remitente
        $stmt = $pdo->prepare("UPDATE entidades SET saldo = saldo - :monto WHERE id_entidad = :id_remitente");
        $stmt->execute(['monto' => $monto, 'id_remitente' => $id_remitente_entidad]);

        // Verificar si la actualización afectó algún registro
        if ($stmt->rowCount() === 0) {
            throw new Exception("No se pudo actualizar el saldo de la entidad remitente.");
        }
    }

    // Sumar el monto al saldo del destinatario si es un usuario
    if ($id_destinatario_usuario) {
        $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo + :monto WHERE id_usuario = :id_destinatario_usuario");
        $stmt->execute(['monto' => $monto, 'id_destinatario_usuario' => $id_destinatario_usuario]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No se pudo actualizar el saldo del destinatario.");
        }
    }

    // Sumar el monto al saldo de la entidad si es una entidad
    if ($id_destinatario_entidad) {
        $stmt = $pdo->prepare("UPDATE entidades SET saldo = saldo + :monto WHERE id_entidad = :id_destinatario_entidad");
        $stmt->execute(['monto' => $monto, 'id_destinatario_entidad' => $id_destinatario_entidad]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No se pudo actualizar el saldo de la entidad.");
        }
    }

    // Registrar la transferencia en la tabla de movimientos_saldo
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_saldo 
        (id_remitente_entidad, id_destinatario_usuario, id_destinatario_entidad, monto, tipo_movimiento, fecha) 
        VALUES (:id_remitente_entidad, :id_destinatario_usuario, :id_destinatario_entidad, :monto, 'Egreso', NOW())
    ");
    
    $stmt->execute([
        'id_remitente_entidad' => $id_remitente_entidad,
        'id_destinatario_usuario' => $id_destinatario_usuario,
        'id_destinatario_entidad' => $id_destinatario_entidad,
        'monto' => $monto
    ]);

    // Confirmar la transacción
    $pdo->commit();

    $fecha = urlencode(date('Y-m-d H:i:s'));  // Generar la fecha en el formato correcto

    if ($dni) {
        // Redirigir con datos del usuario y fecha
        header("Location: ./transferencia_confirmada.php?monto={$monto}&dni={$dni}&nombre={$destinatario['nombre_apellido']}&fecha={$fecha}");
    } elseif ($cuit) {
        // Redirigir con datos de la entidad y fecha
        header("Location: ./transferencia_confirmada.php?monto={$monto}&cuit={$cuit}&nombre_entidad={$entidad['nombre_entidad']}&fecha={$fecha}");
    }
    exit;
} catch (Exception $e) {
    // Si algo sale mal, revertir la transacción
    $pdo->rollBack();
    die("Error al procesar la transferencia: " . $e->getMessage());
}

?>
