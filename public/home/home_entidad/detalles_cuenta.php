<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
    exit;
}

require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

$id_entidad_log = (int)$_SESSION['id_entidad'];

// Verificar que la entidad logueada sea un Banco
$stmt_tipo = $pdo->prepare("SELECT tipo_entidad FROM entidades WHERE id_entidad = :id");
$stmt_tipo->execute([':id' => $id_entidad_log]);
$row_tipo = $stmt_tipo->fetch(PDO::FETCH_ASSOC);
if (!$row_tipo) {
    echo "Error: No se encontró la entidad logueada.";
    exit;
}
if ($row_tipo['tipo_entidad'] !== 'Banco') {
    header('Location: index.php');
    exit;
}

// DNI o CUIT a consultar
$id_lookup = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($id_lookup === '') {
    echo "Falta el identificador (dni/cuit).";
    exit;
}

// Traer cuenta (usuario o entidad). Para usuario separo nombre / apellido.
$sql = "
    SELECT
        TRIM(SUBSTRING_INDEX(u.nombre_apellido, ' ', 1))  AS nombre,
        TRIM(SUBSTRING_INDEX(u.nombre_apellido, ' ', -1)) AS apellido,
        u.dni                                            AS identificador,
        u.id_usuario                                     AS id,
        u.saldo,
        u.password,
        'usuario'                                        AS tipo
    FROM usuarios u
    WHERE u.dni = :id

    UNION

    SELECT
        e.nombre_entidad                                 AS nombre,
        NULL                                             AS apellido,
        e.cuit                                           AS identificador,
        e.id_entidad                                     AS id,
        e.saldo,
        'N/A'                                            AS password,
        e.tipo_entidad                                   AS tipo
    FROM entidades e
    WHERE e.cuit = :id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_lookup]);
$cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cuenta) {
    echo "Cuenta no encontrada.";
    exit;
}

// Si es entidad (Empresa/Banco), cargar usuarios arraigados por id_entidad
$arraigados = [];
if ($cuenta && $cuenta['tipo'] !== 'usuario') {
    $stmtA = $pdo->prepare("
        SELECT id_usuario, nombre_apellido, dni, saldo
        FROM usuarios
        WHERE id_entidad = :id_entidad
        ORDER BY nombre_apellido ASC
    ");
    $stmtA->execute([':id_entidad' => (int)$cuenta['id']]);
    $arraigados = $stmtA->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($cuenta['nombre']); ?></title>
  <link rel="stylesheet" href="../../styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    body { background: linear-gradient(199deg, #324798 0%, #101732 65.93%); font-family: Inter, system-ui, sans-serif; }
    .arreglo_titulo{ font-size:20px; font-weight:600; }
    .right img{ width:40px; height:40px; }
    .contenedor-info-tranferencias{ display:flex; width:328px; flex-direction:column; gap:25px; }
    .section-title{ margin-top:8px; color:#6B7280; font-weight:600; }
    .arraigado-card{
      border: 2px solid rgba(28, 40, 86, 0.50); border-radius: 5px; padding:10px 15px;
      display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
      background:#fff;
    }
    .arraigado-card .left{ display:flex; flex-direction:column; align-items:flex-start !important; }
    .arraigado-card .title{ font-weight:600; color:#000 !important; }
    .arraigado-card .subtitle, .hb-body{ font-size:12px; color:#000 !important; margin-top:2px; }
    .arraigado-card .amount{ color:#1B72BA; font-weight:600; white-space:nowrap; }
    .arraigados-container{ display:flex; flex-direction:column; gap:8px; margin-top:8px; width: 328px;}
    .h4-sub{
      color:#1B72BA; text-align:right;
      font-size:19px; font-weight:500; margin:0;
    }

    .arraigado-link {
  display: block;                 /* que el <a> envuelva toda la card */
  text-decoration: none;
  color: inherit;
  border-radius: 5px;              /* que el focus/hover siga la forma */
}
.arraigado-link:focus-visible,
.arraigado-link:hover {
  outline: none;
}
.arraigado-link:hover .arraigado-card,
.arraigado-link:focus-visible .arraigado-card {
  box-shadow: 0 0 0 2px rgba(27, 114, 186, .25);
  transform: translateY(-1px);
  transition: box-shadow .15s ease, transform .15s ease;
}

.error{
  color:red;
}
.datos-transferenciax{
  display: flex;
width: 328px;
justify-content: space-between;
align-items: center;
}
.saldo-group{
  display: flex;
align-items: center;
gap: 10px;
}

  </style>
</head>
<body>
  <section class="main">
    <nav class="navbar">
      <a href="cuentas.php"><img src="../../img/back.svg" alt="Volver" /></a>
      <span class="h2 arreglo_titulo">Cuentas</span>
    </nav>
    <div class="container-white container-datos">
      <div class="datos-transferenciax">
        <p class="h2 text--darkblue arreglo_titulo">Tipo de cuenta:</p>
        <div class="right">
          <?php if ($cuenta['tipo'] === 'usuario'): ?>
            <img src="../../img/user.svg" alt="Usuario" />
            <p class="h2 text--darkblue arreglo_titulo">Usuario</p>
          <?php else: ?>
            <img src="../../img/empresa.svg" alt="Empresa" />
            <p class="h2 text--darkblue arreglo_titulo">Empresa</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="contenedor-info-tranferencias">
        <?php if ($cuenta['tipo'] === 'usuario'): ?>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">Nombre</p>
            <p class="h2 text--darkblue arreglo_titulo"><?= htmlspecialchars($cuenta['nombre']); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">Apellido</p>
            <p class="h2 text--darkblue arreglo_titulo"><?= htmlspecialchars($cuenta['apellido']); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">DNI</p>
            <p class="h2 text--darkblue arreglo_titulo"><?= htmlspecialchars($cuenta['identificador']); ?></p>
          </div>
        <?php else: ?>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">Nombre</p>
            <p class="h2 text--darkblue arreglo_titulo"><?= htmlspecialchars($cuenta['nombre']); ?></p>
          </div>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">CUIT</p>
            <p class="h2 text--darkblue arreglo_titulo"><?= htmlspecialchars($cuenta['identificador']); ?></p>
          </div>
        <?php endif; ?>

        <?php if ($cuenta['tipo'] === 'usuario'): ?>
          <div class="datos-transferencia">
            <p class="h2 text--light arreglo_titulo">Contraseña</p>
            <div class="right">
              <p class="h2 text--darkblue" id="password"><?= str_repeat('**', strlen($cuenta['password'])); ?></p>
              <img src="../../img/censurado.svg" alt="Mostrar" id="mostrar" style="cursor:pointer" />
            </div>
          </div>
        <?php endif; ?>
 

        <div class="datos-transferencia">
          <p class="h2 text--light">Saldo actual</p>
          <div class="saldo-group">
          <p class="h4-sub text--blue">$<?= number_format((float)$cuenta['saldo'], 0, ',', '.'); ?></p>
         <a
  href="editar_saldo/index.php?
  <?= ($cuenta['tipo'] === 'usuario')
        ? 'dni='  . urlencode($cuenta['identificador'])
        : 'cuit=' . urlencode($cuenta['identificador']) ?>
  &monto=<?= urlencode($cuenta['saldo']) ?>
  &back=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
  aria-label="Editar saldo"
>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M14.7569 2.62097C15.635 1.74284 16.826 1.24951 18.0679 1.24951C19.3098 1.24951 20.5008 1.74284 21.3789 2.62097C22.257 3.4991 22.7504 4.69011 22.7504 5.93197C22.7504 7.17384 22.257 8.36484 21.3789 9.24297L11.8929 18.729C11.3509 19.271 11.0329 19.589 10.6769 19.866C10.2582 20.194 9.80823 20.4723 9.32689 20.701C8.92089 20.894 8.4929 21.037 7.7669 21.279L4.4349 22.389L3.63289 22.657C3.31392 22.7635 2.97159 22.779 2.6443 22.7018C2.317 22.6246 2.01768 22.4578 1.77989 22.22C1.54211 21.9822 1.37527 21.6829 1.29808 21.3556C1.2209 21.0283 1.23641 20.6859 1.3429 20.367L2.72089 16.234C2.96289 15.507 3.1059 15.079 3.2989 14.672C3.52823 14.192 3.80656 13.742 4.13389 13.322C4.40989 12.968 4.72889 12.649 5.2709 12.107L14.7569 2.62097ZM4.3999 20.821L7.2409 19.873C8.0319 19.609 8.36789 19.496 8.68089 19.347C9.06223 19.1643 9.4199 18.9433 9.75389 18.684C10.0269 18.47 10.2789 18.221 10.8689 17.631L18.4389 10.061C17.401 9.69319 16.4589 9.09722 15.6819 8.31697C14.9024 7.5398 14.3071 6.59768 13.9399 5.55997L6.36989 13.13C5.77989 13.719 5.52989 13.97 5.3169 14.244C5.0569 14.5773 4.83589 14.935 4.65389 15.317C4.50489 15.63 4.39189 15.966 4.12789 16.757L3.1799 19.6L4.3999 20.821ZM15.1549 4.34297C15.1899 4.51797 15.2469 4.75597 15.3439 5.03297C15.6364 5.87002 16.1151 6.62976 16.7439 7.25497C17.3688 7.88361 18.1282 8.36229 18.9649 8.65497C19.2429 8.75197 19.4809 8.80897 19.6559 8.84397L20.3179 8.18197C20.9112 7.58452 21.2435 6.77627 21.242 5.93428C21.2405 5.09229 20.9054 4.28521 20.31 3.68983C19.7147 3.09446 18.9076 2.75932 18.0656 2.75785C17.2236 2.75638 16.4154 3.08868 15.8179 3.68197L15.1549 4.34297Z" fill="black"/>
          </svg>
</a>

</div>

        </div>
      </div>

      <?php if ($cuenta['tipo'] !== 'usuario'): ?>
        <div class="arraigados-container">
          <p class="h2 text--light" style="margin-bottom:4px;text-align:left">Usuarios arraigados</p>
          <?php if (empty($arraigados)): ?>
            <p class="h2 text--light error">No hay usuarios arraigados.</p>
          <?php else: ?>
            <?php foreach ($arraigados as $u): ?>
  <a
    class="arraigado-link"
    href="detalles_cuenta.php?id=<?= urlencode($u['dni']) ?>"
    aria-label="Ver detalles de <?= htmlspecialchars($u['nombre_apellido']) ?> (DNI <?= htmlspecialchars($u['dni']) ?>)"
  >
    <div class="arraigado-card">
      <div class="left">
        <span class="title"><?= htmlspecialchars($u['nombre_apellido']); ?></span>
        <span class="hb-body">DNI: <?= htmlspecialchars($u['dni']); ?></span>
      </div>
      <div class="right">
        <span class="h4-sub">$<?= number_format((float)$u['saldo'], 0, ',', '.'); ?></span>
      </div>
    </div>
  </a>
<?php endforeach; ?>

          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="background"></div>
    </div>
  </section>

  <?php if ($cuenta['tipo'] === 'usuario'): ?>
  <script>
    const icono = document.getElementById("mostrar");
    const password = document.getElementById("password");
    const textoOriginal = "<?= $cuenta['password']; ?>";
    if (icono) {
      const iconoCensurado = "../../img/censurado.svg";
      const iconoViendo = "../../img/viendo.svg";
      let censurado = true;
      icono.addEventListener("click", function () {
        if (censurado) {
          icono.src = iconoViendo;
          password.textContent = textoOriginal;
        } else {
          icono.src = iconoCensurado;
          password.textContent = "**".repeat(textoOriginal.length);
        }
        censurado = !censurado;
      });
    }
  </script>
  <?php endif; ?>
</body>
</html>
