<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login');
    exit;
}

// Verificar si la solicitud es por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../error'); // Redirigir a una página de error si no es POST
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Verificar si se recibieron los datos necesarios (DNI o CUIT y Monto)
if (!isset($_POST['dni']) && !isset($_POST['cuit'])) {
    header('Location: ../../index.php'); // Redirigir a página de error si ocurre alguna excepción
    exit;
}

if (!isset($_POST['monto']) || $_POST['monto'] <= 0) {
    header('Location: ../../index.php'); // Redirigir a página de error si ocurre alguna excepción
    exit;
}

$monto = floatval($_POST['monto']);
$dni = isset($_POST['dni']) ? $_POST['dni'] : null;
$cuit = isset($_POST['cuit']) ? $_POST['cuit'] : null;

// ID del remitente (el usuario que está haciendo la transferencia)
$id_remitente_usuario = $_SESSION['id_usuario'];

$pdo->beginTransaction();

try {
    // Buscar al destinatario (puede ser usuario o entidad)
    $id_destinatario_usuario = null;
    $id_destinatario_entidad = null;
    $nombre_destinatario = '';

    if ($dni) {
        // Buscar al usuario por DNI
        $stmt = $pdo->prepare("SELECT id_usuario, nombre_apellido, saldo FROM usuarios WHERE dni = :dni");
        $stmt->execute(['dni' => $dni]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($destinatario) {
            $id_destinatario_usuario = $destinatario['id_usuario'];
            $nombre_destinatario = $destinatario['nombre_apellido'];
        } else {
            throw new Exception("Usuario no encontrado.");
        }
    } elseif ($cuit) {
        // Buscar la entidad por CUIT
        $stmt = $pdo->prepare("SELECT id_entidad, nombre_entidad FROM entidades WHERE cuit = :cuit");
        $stmt->execute(['cuit' => $cuit]);
        $entidad = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($entidad) {
            $id_destinatario_entidad = $entidad['id_entidad'];
            $nombre_destinatario = $entidad['nombre_entidad'];
        } else {
            throw new Exception("Entidad no encontrada.");
        }
    }

    // Verificar si el remitente tiene saldo suficiente
    $stmt = $pdo->prepare("SELECT saldo FROM usuarios WHERE id_usuario = :id_remitente");
    $stmt->execute(['id_remitente' => $id_remitente_usuario]);
    $remitente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($remitente['saldo'] < $monto) {
        throw new Exception("Saldo insuficiente.");
    }

    // Restar el monto del saldo del remitente
    $stmt = $pdo->prepare("UPDATE usuarios SET saldo = saldo - :monto WHERE id_usuario = :id_remitente");
    $stmt->execute(['monto' => $monto, 'id_remitente' => $id_remitente_usuario]);

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
        (id_remitente_usuario, id_destinatario_usuario, id_destinatario_entidad, monto, tipo_movimiento, fecha) 
        VALUES (:id_remitente_usuario, :id_destinatario_usuario, :id_destinatario_entidad, :monto, 'Egreso', NOW())
    ");
    
    $stmt->execute([
        'id_remitente_usuario' => $id_remitente_usuario,
        'id_destinatario_usuario' => $id_destinatario_usuario,
        'id_destinatario_entidad' => $id_destinatario_entidad,
        'monto' => $monto
    ]);

    // Confirmar la transacción
    $pdo->commit();

    // Redirigir a la página de confirmación usando POST
    echo '
        <form id="confirmForm" action="transferencia_confirmada.php" method="POST">
            <input type="hidden" name="monto" value="' . $monto . '">
            <input type="hidden" name="nombre" value="' . htmlspecialchars($nombre_destinatario) . '">
            <input type="hidden" name="dni" value="' . htmlspecialchars($dni) . '">
            <input type="hidden" name="cuit" value="' . htmlspecialchars($cuit) . '">
        </form>
        <script>document.getElementById("confirmForm").submit();</script>
    ';
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../../index.php'); // Redirigir a página de error si ocurre alguna excepción
    exit;
}
?>
