<?php
// editar_saldo/procesar_editar_saldo.php
session_start();

if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../../login');
    exit;
}

require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// --- Solo Banco puede editar ---
$entidad_sesion_id = (int)$_SESSION['id_entidad'];
$stmtTipo = $pdo->prepare("SELECT tipo_entidad FROM entidades WHERE id_entidad = :id");
$stmtTipo->execute([':id' => $entidad_sesion_id]);
$rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
if (!$rowTipo || $rowTipo['tipo_entidad'] !== 'Banco') {
    header('Location: ../index.php');
    exit;
}

// --- Inputs ---
$dni   = isset($_POST['dni'])  ? preg_replace('/\D/', '', $_POST['dni'])  : '';
$cuit  = isset($_POST['cuit']) ? preg_replace('/\D/', '', $_POST['cuit']) : '';
$monto = isset($_POST['monto']) ? preg_replace('/\D/', '', $_POST['monto']) : '';

if ($monto === '' || !ctype_digit($monto)) {
    die('Monto inválido.');
}
$nuevo_saldo = (int)$monto;
if ($nuevo_saldo < 0) {
    die('El monto no puede ser negativo.');
}

try {
    $pdo->beginTransaction();

    $tipo_dest         = '';   // 'usuario' | 'entidad'
    $nombre            = '';
    $ident             = '';   // DNI o CUIT
    $saldo_anterior    = 0;
    $id_usuario_dest   = null;
    $id_entidad_dest   = null;

    // --- Resolver destino y tomar saldo actual con lock ---
    if ($dni !== '') {
        if (strlen($dni) !== 8) { throw new Exception('DNI inválido.'); }

        $stmt = $pdo->prepare("
            SELECT id_usuario, nombre_apellido AS nombre, dni AS identificador, saldo
            FROM usuarios
            WHERE dni = :dni
            FOR UPDATE
        ");
        $stmt->execute([':dni' => $dni]);
        $dest = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dest) { throw new Exception('No se encontró el usuario.'); }

        $tipo_dest       = 'usuario';
        $nombre          = $dest['nombre'];
        $ident           = $dest['identificador'];
        $saldo_anterior  = (int)$dest['saldo'];
        $id_usuario_dest = (int)$dest['id_usuario'];

    } elseif ($cuit !== '') {
        if (strlen($cuit) !== 11) { throw new Exception('CUIT inválido.'); }

        $stmt = $pdo->prepare("
            SELECT id_entidad, nombre_entidad AS nombre, cuit AS identificador, saldo
            FROM entidades
            WHERE cuit = :cuit
            FOR UPDATE
        ");
        $stmt->execute([':cuit' => $cuit]);
        $dest = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dest) { throw new Exception('No se encontró la entidad.'); }

        $tipo_dest       = 'entidad';
        $nombre          = $dest['nombre'];
        $ident           = $dest['identificador'];
        $saldo_anterior  = (int)$dest['saldo'];
        $id_entidad_dest = (int)$dest['id_entidad'];

    } else {
        throw new Exception('Debe proporcionar DNI o CUIT.');
    }

    // --- Calcular diferencia ---
    $diferencia = $nuevo_saldo - $saldo_anterior;

    // Siempre registrar como "Error", aunque sea correcto
    $monto_mov = abs($diferencia);

    $ins = $pdo->prepare("
        INSERT INTO movimientos_saldo
        (id_remitente_entidad, id_destinatario_usuario, id_destinatario_entidad, monto, tipo_movimiento, fecha)
        VALUES (:rem, :idu, :ide, :monto, 'Error', NOW())
    ");
    $ins->execute([
        ':rem'  => $entidad_sesion_id,
        ':idu'  => ($tipo_dest === 'usuario') ? $id_usuario_dest  : null,
        ':ide'  => ($tipo_dest === 'entidad') ? $id_entidad_dest  : null,
        ':monto'=> $monto_mov
    ]);

    // --- Actualizar saldo final ---
    if ($tipo_dest === 'usuario') {
        $up = $pdo->prepare("UPDATE usuarios  SET saldo = :saldo WHERE id_usuario = :id");
        $up->execute([':saldo' => $nuevo_saldo, ':id' => $id_usuario_dest]);
    } else {
        $up = $pdo->prepare("UPDATE entidades SET saldo = :saldo WHERE id_entidad = :id");
        $up->execute([':saldo' => $nuevo_saldo, ':id' => $id_entidad_dest]);
    }

    $pdo->commit();

    // --- Redirigir a OK ---
    $qs = http_build_query([
        'ok'            => 1,
        'tipo'          => $tipo_dest,
        'nombre'        => $nombre,
        'identificador' => $ident,
        'monto'         => $nuevo_saldo
    ]);
    header("Location: saldo_modificado_ok.php?{$qs}");
    exit;

} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die('Error al editar saldo: '.$ex->getMessage());
}

?>