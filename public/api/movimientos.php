<?php
// public/api/movimientos.php (SIN paginación y SIN filtro de fecha)
session_start();

// ----- Parámetro de vista (quién mira la lista) -----
$for = $_GET['for'] ?? 'usuario'; // 'usuario' | 'entidad'

// ----- Auth según la vista -----
if ($for === 'entidad') {
    if (!isset($_SESSION['id_entidad'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unauthenticated entidad']);
        exit;
    }
    $actorId = (int)$_SESSION['id_entidad'];
} else {
    if (!isset($_SESSION['id_usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unauthenticated usuario']);
        exit;
    }
    $actorId = (int)$_SESSION['id_usuario'];
}

require '../../src/Models/Database.php';
$config = require '../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// ----- Filtros (sin fechas) -----
$tipo      = $_GET['tipo']  ?? null; // 'Ingreso','Egreso','Prestamo','Recarga','Error' | 'enviadas' | 'recibidas'
$q         = trim($_GET['q'] ?? ''); // búsqueda por contraparte
$soloError = isset($_GET['solo_error']) ? (int)$_GET['solo_error'] : 0;

// Cómo tratar el signo de los movimientos tipo Error: -1 (resta), 0 (neutro), 1 (suma)
$allowedSigns = [-1, 0, 1];
$errorSign    = isset($_GET['error_sign']) ? (int)$_GET['error_sign'] : -1;
if (!in_array($errorSign, $allowedSigns, true)) { $errorSign = -1; }

// ----- Joins comunes -----
$joins = "
LEFT JOIN usuarios  AS ru  ON ms.id_remitente_usuario    = ru.id_usuario
LEFT JOIN usuarios  AS du  ON ms.id_destinatario_usuario = du.id_usuario
LEFT JOIN entidades AS re  ON ms.id_remitente_entidad    = re.id_entidad
LEFT JOIN entidades AS de  ON ms.id_destinatario_entidad = de.id_entidad
";

// ===================================================================================
// Reglas por "for": dirección, signo, descripción, CONTRAPARTE, ICON
// ===================================================================================
if ($for === 'entidad') {
    $where = " (ms.id_remitente_entidad = :actor OR ms.id_destinatario_entidad = :actor) ";

    $dir = "CASE
              WHEN ms.id_remitente_entidad    = :actor THEN 'sent'
              WHEN ms.id_destinatario_entidad = :actor THEN 'received'
              ELSE 'other'
            END";

    $signo = "CASE
                WHEN ms.tipo_movimiento = 'Error' THEN :error_sign
                WHEN ms.tipo_movimiento IN ('Recarga','Prestamo') AND ms.id_destinatario_entidad = :actor THEN 1
                WHEN ms.id_destinatario_entidad = :actor THEN 1
                WHEN ms.id_remitente_entidad    = :actor THEN -1
                ELSE 0
              END";

    $descripcion = "CASE
                      WHEN ms.tipo_movimiento = 'Prestamo' THEN 'Préstamo'
                      WHEN ms.tipo_movimiento = 'Recarga'  THEN 'Recarga de saldo'
                      WHEN ms.tipo_movimiento = 'Error'    THEN 'Ajuste aplicado'
                      WHEN ms.id_destinatario_entidad = :actor THEN 'Transferencia recibida'
                      WHEN ms.id_remitente_entidad    = :actor THEN 'Transferencia enviada'
                      ELSE 'Movimiento'
                    END";

    $contraparte = "
      CASE
        WHEN ms.id_destinatario_entidad = :actor THEN
          COALESCE(re.nombre_entidad, ru.nombre_apellido, '—')
        WHEN ms.id_remitente_entidad = :actor THEN
          COALESCE(de.nombre_entidad, du.nombre_apellido, '—')
        ELSE
          COALESCE(re.nombre_entidad, de.nombre_entidad, ru.nombre_apellido, du.nombre_apellido, '—')
      END
    ";

    $icono = "
      CASE
        WHEN ms.id_destinatario_entidad = :actor THEN
          CASE
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
        WHEN ms.id_remitente_entidad = :actor THEN
          CASE
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
        ELSE
          CASE
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Empresa' THEN 'company'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
      END
    ";

} else {
    $where = " (ms.id_remitente_usuario = :actor OR ms.id_destinatario_usuario = :actor) ";

    $dir = "CASE
              WHEN ms.id_remitente_usuario    = :actor THEN 'sent'
              WHEN ms.id_destinatario_usuario = :actor THEN 'received'
              ELSE 'other'
            END";

    $signo = "CASE
                WHEN ms.tipo_movimiento = 'Error' THEN :error_sign
                WHEN ms.tipo_movimiento IN ('Recarga','Prestamo') AND ms.id_destinatario_usuario = :actor THEN 1
                WHEN ms.id_destinatario_usuario = :actor THEN 1
                WHEN ms.id_remitente_usuario    = :actor THEN -1
                ELSE 0
              END";

    $descripcion = "CASE
                      WHEN ms.tipo_movimiento = 'Prestamo' THEN 'Préstamo'
                      WHEN ms.tipo_movimiento = 'Recarga'  THEN 'Recarga de saldo'
                      WHEN ms.tipo_movimiento = 'Error'    THEN 'Ajuste aplicado'
                      WHEN ms.id_destinatario_usuario = :actor THEN 'Transferencia recibida'
                      WHEN ms.id_remitente_usuario    = :actor THEN 'Transferencia enviada'
                      ELSE 'Movimiento'
                    END";

    $contraparte = "
      CASE
        WHEN ms.id_destinatario_usuario = :actor THEN
          COALESCE(re.nombre_entidad, ru.nombre_apellido, '—')
        WHEN ms.id_remitente_usuario = :actor THEN
          COALESCE(de.nombre_entidad, du.nombre_apellido, '—')
        ELSE
          COALESCE(re.nombre_entidad, de.nombre_entidad, ru.nombre_apellido, du.nombre_apellido, '—')
      END
    ";

    $icono = "
      CASE
        WHEN ms.id_destinatario_usuario = :actor THEN
          CASE
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
        WHEN ms.id_remitente_usuario = :actor THEN
          CASE
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
        ELSE
          CASE
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN re.id_entidad IS NOT NULL AND re.tipo_entidad = 'Empresa' THEN 'company'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Banco'   THEN 'bank'
            WHEN de.id_entidad IS NOT NULL AND de.tipo_entidad = 'Empresa' THEN 'company'
            ELSE 'user'
          END
      END
    ";
}

// ----- Filtro por texto (usa la expresión de contraparte real) -----
$filtroQ = '';
$paramsQ = [];
if ($q !== '') {
    $filtroQ = " AND (
        ($contraparte) LIKE :q
        OR ru.dni LIKE :q OR du.dni LIKE :q
        OR re.cuit LIKE :q OR de.cuit LIKE :q
    ) ";
    $paramsQ[':q'] = "%$q%";
}

// ----- Filtro por tipo alto-nivel -----
$filtroTipo = '';
if ($tipo) {
    if ($tipo === 'enviadas') {
        $filtroTipo = ($for === 'entidad')
            ? " AND ms.id_remitente_entidad = :actor "
            : " AND ms.id_remitente_usuario = :actor ";
    } elseif ($tipo === 'recibidas') {
        $filtroTipo = ($for === 'entidad')
            ? " AND ms.id_destinatario_entidad = :actor "
            : " AND ms.id_destinatario_usuario = :actor ";
    } else {
        $filtroTipo = " AND ms.tipo_movimiento = :tipo ";
    }
}

// ----- SIN filtros de fecha -----

// ======================
// Consulta principal (SIN LIMIT/OFFSET)
// ======================
$tag = "CASE
          WHEN ms.tipo_movimiento = 'Error'    THEN 'error'
          WHEN ms.tipo_movimiento = 'Prestamo' THEN 'prestamo'
          WHEN ms.tipo_movimiento = 'Recarga'  THEN 'recarga'
          WHEN ($dir) = 'received'             THEN 'in'
          WHEN ($dir) = 'sent'                 THEN 'out'
          ELSE 'mov'
        END";

$sql = "
SELECT
  ms.id_transaccion,
  ms.fecha,
  ms.monto,
  ($dir)         AS direccion,
  ($signo)       AS signo,
  ($descripcion) AS descripcion,
  ($contraparte) AS contraparte,
  CASE
    WHEN ms.tipo_movimiento = 'Error' THEN 'error'
    ELSE ($icono)
  END            AS icono,
  ($tag)         AS tag
FROM movimientos_saldo ms
$joins
WHERE $where
$filtroTipo
$filtroQ
ORDER BY ms.fecha DESC, ms.id_transaccion DESC
";


$stmt = $pdo->prepare($sql);
$stmt->bindValue(':actor', $actorId, PDO::PARAM_INT);
$stmt->bindValue(':error_sign', $errorSign, PDO::PARAM_INT);
if ($tipo && $tipo !== 'enviadas' && $tipo !== 'recibidas') {
    $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
}
foreach ($paramsQ as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// Resumen de errores (sin fechas, respeta mismos filtros tipo/q)
// ======================
$sumSql = "
SELECT
  COUNT(*) AS qty,
  COALESCE(SUM(ms.monto),0) AS total
FROM movimientos_saldo ms
$joins
WHERE $where
  AND ms.tipo_movimiento = 'Error'
$filtroTipo
$filtroQ
";
$sstmt = $pdo->prepare($sumSql);
$sstmt->bindValue(':actor', $actorId, PDO::PARAM_INT);
if ($tipo && $tipo !== 'enviadas' && $tipo !== 'recibidas') {
    $sstmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
}
foreach ($paramsQ as $k => $v) $sstmt->bindValue($k, $v, PDO::PARAM_STR);
$sstmt->execute();
$summaryError = $sstmt->fetch(PDO::FETCH_ASSOC) ?: ['qty'=>0,'total'=>0];

// ======================
// Respuesta
// ======================
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
  'total'   => count($rows),       // total devuelto (sin paginar)
  'has_more'=> false,              // sin paginación
  'summary' => [
      'error' => [
        'count' => (int)$summaryError['qty'],
        'total' => (float)$summaryError['total'],
      ],
  ],
  'items'   => array_map(function ($r) {
      $signed = ((int)$r['signo'] >= 0 ? 1 : -1) * (float)$r['monto'];
      return [
        'id'            => (int)$r['id_transaccion'],
        'fecha'         => $r['fecha'],
        'monto'         => (float)$r['monto'],
        'montoSigned'   => $signed,
        'descripcion'   => $r['descripcion'],
        'contraparte'   => $r['contraparte'] ?? '—',
        'icon'          => $r['icono'],
        'direction'     => $r['direccion'], // 'sent' | 'received'
        'tag'           => $r['tag'],       // 'error','prestamo','recarga','in','out','mov'
        'isError'       => ($r['tag'] === 'error'),
      ];
  }, $rows)
], JSON_UNESCAPED_UNICODE);
