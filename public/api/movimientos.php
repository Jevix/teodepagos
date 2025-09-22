<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthenticated']);
  exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];

require '../../src/Models/Database.php';
$config = require '../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

$page     = max(1, (int)($_GET['page'] ?? 1));
$pageSize = min(50, max(1, (int)($_GET['page_size'] ?? 10)));
$offset   = ($page - 1) * $pageSize;

/* 
   Calculamos en SQL:
   - monto_signed: + o - según seas destinatario/remitente
   - descripcion: etiqueta humana
   - contraparte_nombre: usuario o entidad del otro lado
   - icono: bank/company/user
*/
$sql = "
SELECT
  ms.id_transaccion,
  ms.fecha,
  ms.monto,
  CASE
    WHEN ms.tipo_movimiento IN ('Recarga','Prestamo') THEN 1
    WHEN ms.tipo_movimiento = 'Error' THEN -1
    WHEN ms.id_destinatario_usuario = :uid THEN 1
    WHEN ms.id_remitente_usuario    = :uid THEN -1
    ELSE 0
  END AS signo,

  CASE
    WHEN ms.tipo_movimiento = 'Prestamo' THEN 'Préstamo'
    WHEN ms.tipo_movimiento = 'Recarga'  THEN 'Recarga de saldo'
    WHEN ms.tipo_movimiento = 'Error'    THEN 'Error bancario'
    WHEN ms.id_destinatario_usuario = :uid THEN 'Transferencia recibida'
    WHEN ms.id_remitente_usuario    = :uid THEN 'Transferencia enviada'
    ELSE 'Movimiento'
  END AS descripcion,

  -- contraparte: primero usuario, si no, entidad
  COALESCE(destinatario.nombre_apellido, remitente.nombre_apellido,
           destinatario_entidad.nombre_entidad, remitente_entidad.nombre_entidad) AS contraparte_nombre,

  CASE
    WHEN destinatario_entidad.tipo_entidad = 'Banco' OR remitente_entidad.tipo_entidad = 'Banco' THEN 'bank'
    WHEN destinatario_entidad.tipo_entidad = 'Empresa' OR remitente_entidad.tipo_entidad = 'Empresa' THEN 'company'
    ELSE 'user'
  END AS icono

FROM movimientos_saldo ms
LEFT JOIN usuarios   AS remitente            ON ms.id_remitente_usuario    = remitente.id_usuario
LEFT JOIN usuarios   AS destinatario         ON ms.id_destinatario_usuario = destinatario.id_usuario
LEFT JOIN entidades  AS remitente_entidad    ON ms.id_remitente_entidad    = remitente_entidad.id_entidad
LEFT JOIN entidades  AS destinatario_entidad ON ms.id_destinatario_entidad = destinatario_entidad.id_entidad
WHERE (ms.id_remitente_usuario = :uid OR ms.id_destinatario_usuario = :uid)
ORDER BY ms.fecha DESC
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $id_usuario, PDO::PARAM_INT);
$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// total para saber si hay más
$countSql = "
  SELECT COUNT(*) AS total
  FROM movimientos_saldo
  WHERE (id_remitente_usuario = :uid OR id_destinatario_usuario = :uid)
";
$cstmt = $pdo->prepare($countSql);
$cstmt->execute([':uid' => $id_usuario]);
$total = (int)$cstmt->fetchColumn();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'page'       => $page,
  'page_size'  => $pageSize,
  'total'      => $total,
  'has_more'   => ($offset + $pageSize) < $total,
  'items'      => array_map(function($r){
    // formato de salida simple para la vista
    $signed = ((int)$r['signo'] >= 0 ? 1 : -1) * (float)$r['monto'];
    return [
      'id'         => (int)$r['id_transaccion'],
      'fecha'      => $r['fecha'],
      'monto'      => (float)$r['monto'],
      'montoSigned'=> $signed,
      'descripcion'=> $r['descripcion'],
      'contraparte'=> $r['contraparte_nombre'] ?? '—',
      'icon'       => $r['icono'],
    ];
  }, $rows)
], JSON_UNESCAPED_UNICODE);
