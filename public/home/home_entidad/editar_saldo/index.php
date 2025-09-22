<?php
// editar_saldo/index.php
session_start();

// === Requiere sesión con entidad ===
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../../login');
    exit;
}

require '../../../../src/Models/Database.php';
$config = require '../../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

$entidad_sesion_id = (int)($_SESSION['id_entidad'] ?? 0);

// === Solo permite entidades tipo Banco ===
$stmtTipo = $pdo->prepare("SELECT tipo_entidad, saldo FROM entidades WHERE id_entidad = :id");
$stmtTipo->execute([':id' => $entidad_sesion_id]);
$rowTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);

if (!$rowTipo) {
    // Sesión inconsistente
    header('Location: ../../../login');
    exit;
}
if ($rowTipo['tipo_entidad'] !== 'Banco') {
    // No autorizado: no es Banco
    header('Location: ../index.php');
    exit;
}

$saldo_sesion = (int)$rowTipo['saldo'];

// === Params de entrada ===
$dni   = isset($_GET['dni'])  ? preg_replace('/\D/', '', $_GET['dni'])  : '';
$cuit  = isset($_GET['cuit']) ? preg_replace('/\D/', '', $_GET['cuit']) : '';
$monto = isset($_GET['monto']) ? preg_replace('/\D/', '', $_GET['monto']) : '0';
$back  = isset($_GET['back'])  ? (string)$_GET['back'] : 'index.php';

// === Resuelve destinatario según parámetro recibido ===
$nombre_destinatario = '';
$tipo_destinatario   = '';  // 'usuario' | 'entidad'
$tipo_entidad_dest   = '';  // 'Banco' | 'Empresa' (si es entidad)
$identificador_label = '';  // "DNI: ..." o "CUIT: ..."

// Si llega ambos por error, priorizo DNI
if (!empty($dni)) {
    if (strlen($dni) !== 8) {
        die('DNI inválido.');
    }
    $stmtU = $pdo->prepare("SELECT nombre_apellido FROM usuarios WHERE dni = :dni");
    $stmtU->execute([':dni' => $dni]);
    $u = $stmtU->fetch(PDO::FETCH_ASSOC);
    if (!$u) { die('No se encontró ningún usuario con ese DNI.'); }

    $nombre_destinatario = $u['nombre_apellido'];
    $tipo_destinatario   = 'usuario';
    $identificador_label = 'DNI: ' . $dni;

} elseif (!empty($cuit)) {
    if (strlen($cuit) !== 11) {
        die('CUIT inválido.');
    }
    $stmtE = $pdo->prepare("SELECT nombre_entidad, tipo_entidad FROM entidades WHERE cuit = :cuit");
    $stmtE->execute([':cuit' => $cuit]);
    $e = $stmtE->fetch(PDO::FETCH_ASSOC);
    if (!$e) { die('No se encontró ninguna entidad con ese CUIT.'); }

    $nombre_destinatario = $e['nombre_entidad'];
    $tipo_destinatario   = 'entidad';
    $tipo_entidad_dest   = $e['tipo_entidad']; // 'Banco' | 'Empresa'
    $identificador_label = 'CUIT: ' . $cuit;

} else {
    die('Debe proporcionar un DNI o CUIT.');
}

// === Helpers ícono/alt según tipo ===
function icono_dest($tipo_destinatario, $tipo_entidad_dest) {
    if ($tipo_destinatario === 'usuario') return 'user.svg';
    if ($tipo_destinatario === 'entidad' && $tipo_entidad_dest === 'Banco') return 'banco.svg';
    return 'empresa.svg';
}
function alt_dest($tipo_destinatario, $tipo_entidad_dest) {
    if ($tipo_destinatario === 'usuario') return 'Usuario';
    if ($tipo_destinatario === 'entidad' && $tipo_entidad_dest === 'Banco') return 'Banco';
    return 'Entidad';
}
$icono = icono_dest($tipo_destinatario, $tipo_entidad_dest);
$alt   = alt_dest($tipo_destinatario, $tipo_entidad_dest);

$monto_inicial = (int)$monto;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Editar Saldo</title>
  <link rel="stylesheet" href="../../../styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>

  <style>
    body{ background: linear-gradient(199deg, #324798 0%, #101732 65.93%); }
    .loader{ position:fixed; inset:0; display:none; place-content:center; z-index:9999; background: rgba(255,255,255,.7); }
    .loader img{ width:100px; height:100px; }
    @keyframes bounce{0%,20%,50%,80%,100%{transform:translateY(0)}40%{transform:translateY(-10px)}60%{transform:translateY(-5px)}}
    .texto-rojo{ color:red !important; }
    .bounce{ animation:bounce .5s ease; }
    .teclado-numerico .row{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .h2.key{ display:flex; align-items:center; justify-content:center; height:56px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; cursor:pointer; }
    .btn-primary.submit--off{ opacity:.5; cursor:not-allowed; }
    .btn-primary.submit--on{ opacity:1; cursor:pointer; }

  /* === MODAL === */
  .modal-overlay{
    position: fixed; inset: 0;
    background: #d9d9d9c9;
    display: none;              /* por defecto oculto */
    align-items: center; justify-content: center;
    z-index: 10000;
  }
  .modal-overlay.is-open{ display: flex; }

  .modal{
    width: min(420px, 92vw);
    display: inline-flex;
    padding: 30px;
    flex-direction: column;
    align-items: flex-start;
    gap: 40px;
    border-radius: 10px;

    background: #fff;
    margin-left: 22px;
    margin-right: 22px;
  }
  .modal-overlay.is-open .modal{
    opacity: 1; transform: translateY(0);
  }
  .modal h3{
    font-size: 18px; font-weight: 700; margin: 0 0 8px;
  }
  .modal p{
    margin: 0 0 18px; color: #374151;
  }
  .modal .amount{
    font-size: 22px; font-weight: 700; color: #1B72BA; margin-bottom: 16px;
  }

  .button-group{
   display: flex;
    width: 257px;
    justify-content: space-between;
    align-items: center;
  }
  .buttonmodal-cancel,
  .buttonmodal-editar{
   display: flex;
height: 54px;
padding: 15px 10px;
justify-content: center;
align-items: center;
gap: 10px;
  }
  .buttonmodal-cancel{
   border-radius: 5px;
    background: transparent;
    border: none;
    color:#1B72BA;
    font-family: Inter;
    font-size: 20px;
    font-style: normal;
    font-weight: 500;
    line-height: normal;
  }
  .buttonmodal-editar{
   display: flex;
    height: 54px;
    padding: 15px 10px;
    justify-content: center;
    align-items: center;
    gap: 10px;
    border-radius: 5px;
    background: #1B72BA;
    color: #FFF;
    border: none;

/* H3 - Boton */
font-family: Inter;
font-size: 20px;
font-style: normal;
font-weight: 500;
line-height: normal;
  }
  .center{
    text-align: center !important;
  }
  </style>
</head>
<body>
<div id="loader" class="loader">
  <img src="../../../img/loader.gif" alt="Cargando..." />
</div>

<section class="main">
  <nav class="navbar">
    <a href="<?php echo htmlspecialchars($back ?: 'index.php'); ?>" aria-label="Atrás"
       onclick="if (history.length > 1) { history.back(); return false; }">
      <img src="../../../img/back.svg" alt="Volver" />
    </a>
    <p class="h2">Editar Saldo</p>
  </nav>

  <div class="container-white">
    <div class="transferencia">
      <div class="left">
        <img src="../../../img/<?php echo htmlspecialchars($icono); ?>" alt="<?php echo htmlspecialchars($alt); ?>" />
        <div>
          <p class="h4"><?php echo htmlspecialchars($nombre_destinatario); ?></p>
          <p class="hb"><?php echo htmlspecialchars($identificador_label); ?></p>
        </div>
      </div>
    </div>

    <!-- Si querés validar contra el saldo del banco logueado, mostrás y usás este dato -->
    <!-- <p class="h4" id="dineroDisponible">Tu dinero disponible: $<?php echo number_format($saldo_sesion,0,',','.'); ?></p> -->

    <div class="dinero-disponible">
      <div>
        <p class="h1">$</p>
        <p class="h1" id="display"><?php echo number_format($monto_inicial,0,',','.'); ?></p>
      </div>
    </div>

    <div class="teclado-numerico">
      <div class="row">
        <button type="button" class="h2 key" data-key="1">1</button>
        <button type="button" class="h2 key" data-key="2">2</button>
        <button type="button" class="h2 key" data-key="3">3</button>
      </div>
      <div class="row">
        <button type="button" class="h2 key" data-key="4">4</button>
        <button type="button" class="h2 key" data-key="5">5</button>
        <button type="button" class="h2 key" data-key="6">6</button>
      </div>
      <div class="row">
        <button type="button" class="h2 key" data-key="7">7</button>
        <button type="button" class="h2 key" data-key="8">8</button>
        <button type="button" class="h2 key" data-key="9">9</button>
      </div>
      <div class="row">
        <button type="button" class="h2 key" data-key="x"></button>
        <button type="button" class="h2 key" data-key="0">0</button>
        <button type="button" class="h2 key" data-key="back">&lt;</button>
      </div>
    </div>

    <button class="btn-primary submit--off arreglo_buton_editarsaldo" id="submitButton" disabled>
      Editar Saldo
    </button>
<div id="modalOverlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-hidden="true">
  <div class="modal" tabindex="-1">
    <h3 class="center" id="modal-title">Se esta por editar el saldo.</h3>
    <p style="display:none">Monto a aplicar: <span class="amount" id="modalMonto">$0</span></p>
    <div class="button-group">
      <button type="button" class="buttonmodal-cancel" id="modalCancel">Cancelar</button>
      <button type="button" class="buttonmodal-editar" id="modalConfirm">Editar</button>
    </div>
  </div>
</div>

    <div class="background"></div>
  </div>
</section>

<script>
  // Siempre arriba
  window.addEventListener('load', function(){ window.scrollTo(0,0); });

  const display = document.getElementById('display');
  const submitButton = document.getElementById('submitButton');
  const dineroDisponible = document.getElementById('dineroDisponible'); // puede ser null

  // Si querés validar contra saldo del banco logueado:
  // const saldoDisponible = <?php echo json_encode((int)$saldo_sesion); ?>;

  const unformat = (s) => String(s||'').replace(/\./g,'').trim();
  const format   = (n) => (Number(n||0)).toLocaleString('es-AR');

  function toggleBtn() {
    const montoNumerico = parseInt(unformat(display.textContent) || '0', 10);
    let ok = montoNumerico > 0;

    // Validación contra saldo (opcional):
    // if (montoNumerico > saldoDisponible) {
    //   ok = false;
    //   if (dineroDisponible) {
    //     dineroDisponible.classList.add('texto-rojo', 'bounce');
    //     setTimeout(()=>dineroDisponible.classList.remove('bounce'), 500);
    //   }
    // } else if (dineroDisponible) {
    //   dineroDisponible.classList.remove('texto-rojo');
    // }

    if (ok) {
      submitButton.disabled = false;
      submitButton.classList.remove('submit--off');
      submitButton.classList.add('submit--on');
    } else {
      submitButton.disabled = true;
      submitButton.classList.add('submit--off');
      submitButton.classList.remove('submit--on');
    }
  }

  function agregarNum(n) {
    let raw = unformat(display.textContent);
    if (raw === '0') raw = '';
    if (raw.length >= 12) return;
    raw += String(n);
    display.textContent = format(parseInt(raw, 10) || 0);
    toggleBtn();
  }

  function borrar() {
    let raw = unformat(display.textContent);
    if (raw.length > 1) raw = raw.slice(0, -1);
    else raw = '0';
    display.textContent = format(parseInt(raw,10) || 0);
    toggleBtn();
  }

  document.querySelectorAll('.key').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const k = btn.getAttribute('data-key');
      if (!k) return;
      if (k === 'back') return borrar();
      if (/^\d$/.test(k)) return agregarNum(k);
    });
  });

  // ===== Modal =====
  const modalOverlay = document.getElementById('modalOverlay');
  const modalCancel  = document.getElementById('modalCancel');
  const modalConfirm = document.getElementById('modalConfirm');
  const modalMonto   = document.getElementById('modalMonto');

  // Abrir modal al click en "Editar Saldo"
  submitButton.addEventListener('click', (e)=>{
    e.preventDefault();
    const monto = unformat(display.textContent);
    if (!monto || parseInt(monto,10) <= 0) return;

    modalMonto.textContent = '$' + (Number(monto).toLocaleString('es-AR'));
    modalOverlay.classList.add('is-open');
    modalOverlay.setAttribute('aria-hidden', 'false');
  });

  // Cerrar modal
  function closeModal(){
    modalOverlay.classList.remove('is-open');
    modalOverlay.setAttribute('aria-hidden', 'true');
  }
  modalCancel.addEventListener('click', closeModal);
  modalOverlay.addEventListener('click', (ev)=>{
    if (ev.target === modalOverlay) closeModal();
  });
  window.addEventListener('keydown', (ev)=>{
    if (ev.key === 'Escape' && modalOverlay.classList.contains('is-open')) closeModal();
  });

  // Confirmar -> POST a /procesar_editar_saldo.php
  let posting = false;
  modalConfirm.addEventListener('click', ()=>{
    if (posting) return;
    const monto = unformat(display.textContent);
    if (!monto || parseInt(monto,10) <= 0) return;

    posting = true;
    modalConfirm.disabled = true;
    document.getElementById('loader').style.display = 'grid';

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'procesar_editar_saldo.php';   // <-- tu endpoint

    const addHidden = (name,val)=>{
      if (val==null || val==='') return;
      const input = document.createElement('input');
      input.type='hidden'; input.name=name; input.value=val;
      form.appendChild(input);
    };

    addHidden('monto', monto);
    <?php if (!empty($dni)): ?>
      addHidden('dni',  <?= json_encode($dni); ?>);
    <?php else: ?>
      addHidden('cuit', <?= json_encode($cuit); ?>);
    <?php endif; ?>

    document.body.appendChild(form);
    form.submit();
  });

  // Estado inicial
  toggleBtn();

  // Accesos rápidos con teclado físico (opcional)
  window.addEventListener('keydown', (ev)=>{
    if (ev.key >= '0' && ev.key <= '9') agregarNum(ev.key);
    else if (ev.key === 'Backspace')    borrar();
    else if (ev.key === 'Enter' && !submitButton.disabled) submitButton.click();
  });
</script>

</body>
</html>
