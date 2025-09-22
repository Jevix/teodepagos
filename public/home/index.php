<?php
$session_lifetime = 60 * 60 * 24 * 30;
session_set_cookie_params($session_lifetime);
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login');
    exit;
}

$id_usuario  = $_SESSION['id_usuario'];
$tipo_usuario = $_SESSION['tipo_usuario'] ?? null;

require '../../src/Models/Database.php';
$config = require '../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
$stmt->execute(['id_usuario' => $id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Entidad (para mostrar/ocultar botón Empresa / redirigir Banco)
$entidad = null;
if ($usuario && !empty($usuario['id_entidad'])) {
    $stmt = $pdo->prepare("SELECT * FROM entidades WHERE id_entidad = :id_entidad");
    $stmt->execute(['id_entidad' => $usuario['id_entidad']]);
    $entidad = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entidad && $entidad['tipo_entidad'] === 'Banco') {
        $_SESSION['id_entidad'] = $entidad['id_entidad'];
        $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
        $_SESSION['tipo_entidad'] = $entidad['tipo_entidad'];
        header('Location: home_entidad/');
        exit;
    } elseif ($entidad && $entidad['tipo_entidad'] === 'Empresa') {
        $_SESSION['id_entidad'] = $entidad['id_entidad'];
        $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
        $_SESSION['tipo_entidad'] = $entidad['tipo_entidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es" translate="no">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Home</title>
  <link rel="stylesheet" href="../styles.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" />

  <style>
    html { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; text-size-adjust:100%; }
    body { background: linear-gradient(199deg, #324798 0%, #101732 65.93%); }

    /* Modal de logout existente */
    .bg-ventana-modal {
      position: fixed; top:0; left:0; width:100%; height:100%;
      background-color: rgba(0,0,0,.5);
      backdrop-filter: blur(10px);
      display: none; justify-content: center; align-items: center;
      z-index: 1000;
    }
    .ventana-modal {
      background: #fff; padding: 20px; border-radius: 10px; text-align: center;
      box-shadow: 0 4px 20px rgba(0,0,0,.1); z-index: 1001;
    }

    /* Loader */
    .loader {
      position: fixed; inset: 0; background-color: rgba(255,255,255,.7);
      display: flex; justify-content: center; align-items: center; z-index: 9999;
    }

    /* ====== Modal de cámara (único) ====== */
    .cam-modal-backdrop{
      position: fixed; inset: 0;
      display: grid; place-items: center;
      background: rgba(2,6,23,.55);
      backdrop-filter: blur(6px);
      z-index: 10000;
      animation: cam-fade .18s ease-out;
    }
    @keyframes cam-fade { from{ opacity:0 } to{ opacity:1 } }

    .cam-modal{
      width: 272px;
      border-radius: 16px;
      background: #fff; color: #0f172a;
      box-shadow: 0 24px 70px -28px rgba(0,0,0,.45);
      padding: 20px 20px 16px; position: relative;
      animation: cam-pop .18s ease-out;
    }
    @keyframes cam-pop { from{ transform: translateY(6px); opacity:.98 } to{ transform:none; opacity:1 } }

    .cam-close{
      position: absolute; top: 10px; right: 10px;
      border: 0; background: transparent; font-size: 18px; cursor: pointer; color: #334155;
    }
    .cam-header{ display:flex; align-items:center; gap:10px; margin-bottom: 6px; }
    .cam-header svg{ color:#324798 }
    .cam-header h2{ margin:0; font-size: 20px; }

    .cam-subtitle{ margin: 8px 0 10px; color:#475569 }
    .cam-bullets{ margin: 0 0 10px 18px; color:#334155 }
    .cam-bullets li{ margin: 6px 0 }

    .cam-actions{ display:flex; gap:10px; flex-wrap:wrap; margin: 6px 0 8px; justify-content: center;}

    .cam-btn-primary{
      appearance: none; border:0; cursor:pointer; font-weight:600;
      border-radius: 999px; padding: 10px 16px; color:#fff; background:#324798;
      box-shadow: 0 10px 22px -12px rgba(50,71,152,.55);
      transition: transform .06s ease, box-shadow .18s ease;
    }
    .cam-btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 12px 28px -14px rgba(50,71,152,.55); }
    .cam-btn-primary:active{ transform:none; box-shadow: 0 5px 14px -10px rgba(50,71,152,.55); }

    .cam-btn-ghost{
      appearance: none; border:1px solid #e2e8f0; background:#fff; color:#0f172a;
      border-radius: 999px; padding: 10px 16px; cursor:pointer; font-weight:600;
    }

    .cam-footnote{ margin: 4px 0 0; color:#64748b; font-size: .92rem; }
    .cam-modal-backdrop[hidden] { 
  display: none !important; 
}
  </style>
</head>
<body>
  <!-- Loader -->
  <div id="loader" class="loader" style="display:none;">
    <img src="../img/loader.gif" alt="Cargando..." />
  </div>

  <section class="home-user">
    <!-- NAV -->
    <nav class="navbar">
      <div class="left">
        <img src="../img/saludo.svg" alt="" />
        <div>
          <p class="documento"><?php echo htmlspecialchars($usuario['dni']); ?></p>
          <p class="nombre"><?php echo htmlspecialchars($usuario['nombre_apellido']); ?></p>
        </div>
      </div>
      <div class="right">
        <a href="javascript:void(0);" onclick="showModalLogout()">
          <img src="../img/salir.svg" alt="" class="salir" />
        </a>
      </div>
    </nav>

    <!-- SALDO -->
    <div class="dinero">
      <p class="h2">Dinero disponible</p>
      <p class="h1">$ <?php echo number_format((float)$usuario['saldo'], 0, ',', '.'); ?></p>
    </div>

    <!-- TRANSACCIONES -->
    <div class="transacciones">
      <div onclick="showLoaderAndRedirect('transferir')">
        <img src="../img/transferir.svg" alt="" />
        <p class="hb">Transferir</p>
      </div>
      <div onclick="showLoaderAndRedirect('miqr.php')">
        <img src="../img/qr.svg" alt="" />
        <p class="hb">Tu QR</p>
      </div>
      <div <?php echo (!empty($entidad) && $entidad['tipo_entidad'] === 'Empresa') ? '' : 'style="display:none;"'; ?>>
        <a onclick="showLoaderAndRedirect('home_entidad/')">
          <img src="../img/empresa-black.svg" alt="" />
        </a>
        <p class="hb">Empresa</p>
      </div>
    </div>

    <!-- MOVIMIENTOS -->
    <div class="movimientos">
      <p class="h2" id="h2">Movimientos</p>
      <div id="movimientos-list" class="movimientos-container">
        <!-- acá van los movimientos -->
        <div id="container-btn" class="container-btn"></div>
      </div>
    </div>

    <div class="background"></div>
  </section>

  <!-- MODAL LOGOUT -->
  <div class="bg-ventana-modal">
    <div class="ventana-modal">
      <p class="h2 text--darkblue">Estás por salir de tu cuenta.</p>
      <div>
        <button class="btn-modal-1 h3 text--blue" onclick="hideModalLogout()">Cancelar</button>
        <button class="btn-modal-2 h3" onclick="logout()" style="width:149px;">Cerrar sesión</button>
      </div>
    </div>
  </div>

  <!-- ===== Modal único de permiso de cámara ===== -->
<div id="cam-modal" class="cam-modal-backdrop" hidden>
  <div class="cam-modal" role="dialog" aria-labelledby="cam-modal-title" aria-modal="true">
    <button type="button" class="cam-close" aria-label="Cerrar" id="cam-close-btn">✕</button>

    <div class="cam-header">
      <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24">
        <path fill="currentColor" d="M4 5h11l3 3h2a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/>
      </svg>
      <h2 id="cam-modal-title">Permiso de cámara</h2>
    </div>

    <p class="cam-subtitle">
      Necesitamos acceso a la cámara para escanear tus códigos QR.  
      El permiso se usa solo en esta función.
    </p>

    <div class="cam-actions">
      <button type="button" id="cam-enable-btn" class="cam-btn-primary">Habilitar</button>
      <button type="button" id="cam-later-btn" class="cam-btn-ghost">Después</button>
    </div>
  </div>
</div>

  

  <script>
    function showLoaderAndRedirect(url) {
      const loader = document.getElementById('loader');
      loader.style.display = 'flex';
      setTimeout(() => { window.location.href = url; }, 1000);
    }

    function showModalLogout() {
      document.querySelector('.bg-ventana-modal').style.display = 'flex';
      // No bloquear scroll del body acá (ya tenés backdrop propio)
    }

    function hideModalLogout() {
      document.querySelector('.bg-ventana-modal').style.display = 'none';
    }

    function logout() {
      window.location.href = '../logout.php';
    }

    const VISIBLE_COUNT = 3;
    const REQUEST_PAGE_SIZE = 50;

    const listEl = document.getElementById('movimientos-list');
    const containerBtn = document.getElementById('container-btn');

    function fmtMonto(n) {
      const abs = Math.abs(Number(n) || 0);
      return (n < 0 ? '-' : '+') + '$' + abs.toLocaleString('es-AR', { maximumFractionDigits: 0 });
    }
    function iconPath(name) {
      switch (name) {
        case 'bank': return '../img/bank.svg';
        case 'company': return '../img/company.svg';
        case 'error': return '../img/bank.svg';
        default: return '../img/user.svg';
      }
    }
    function fmtHoraSafe(fechaStr) {
      try {
        const d = new Date((fechaStr || '').replace(' ', 'T'));
        if (isNaN(d.getTime())) return '';
        return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
      } catch { return ''; }
    }

    async function cargarMovimientos() {
      try {
        const res = await fetch(`../api/movimientos.php?page=1&page_size=${REQUEST_PAGE_SIZE}`, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('Error al cargar movimientos');

        const data = await res.json();
        const items = Array.isArray(data.items) ? data.items : [];

        if (items.length === 0) {
          listEl.innerHTML = `
            <div style="text-align:center;">
              <p class="h2 text--light">Todavía no realizaste ninguna transferencia.</p>
            </div>`;
          return;
        }

        items.slice(0, VISIBLE_COUNT).forEach(renderMovimiento);

        const total = (typeof data.total === 'number') ? data.total : items.length;
        if (total > VISIBLE_COUNT) {
          const btnMas = document.createElement('button');
          btnMas.className = "btn-primary";
          btnMas.textContent = "Historial de movimientos";
          btnMas.addEventListener('click', () => { window.location.href = './movimientos.php'; });
          containerBtn.appendChild(btnMas);
        }

      } catch (e) {
        console.error(e);
      }
    }

    function renderMovimiento(item) {
      try {
        const wrap = document.createElement('div');
        wrap.className = 'componente--movimiento';

        const left = document.createElement('div');
        left.className = 'left';
        const img = document.createElement('img');
        img.src = iconPath(item.icon);
        img.alt = 'Entidad';
        left.appendChild(img);

        const right = document.createElement('div');
        right.className = 'right';

        const arriba = document.createElement('div');
        arriba.className = 'arriba';

        const pNombre = document.createElement('p');
        pNombre.className = 'h5';
        pNombre.textContent = item.contraparte ?? '—';

        const pMonto = document.createElement('p');
        if (item.tag === 'error') {
          pMonto.className = 'h4 text--neutral';
          pMonto.textContent = '$' + (item.monto ?? 0).toLocaleString('es-AR', { maximumFractionDigits: 0 });
        } else {
          pMonto.className = 'h4 ' + ((item.montoSigned ?? 0) < 0 ? 'text--minus' : 'text--plus');
          pMonto.textContent = fmtMonto(item.montoSigned ?? 0);
        }

        arriba.appendChild(pNombre);
        arriba.appendChild(pMonto);

        const abajo = document.createElement('div');
        abajo.className = 'abajo';

        const pDesc = document.createElement('p');
        pDesc.className = 'hb';
        pDesc.textContent = item.descripcion ?? 'Movimiento';

        const pHora = document.createElement('p');
        pHora.className = 'hb';
        pHora.textContent = fmtHoraSafe(item.fecha);

        abajo.appendChild(pDesc);
        abajo.appendChild(pHora);

        right.appendChild(arriba);
        right.appendChild(abajo);

        wrap.appendChild(left);
        wrap.appendChild(right);

        listEl.insertBefore(wrap, containerBtn);
      } catch (e) {
        console.warn('No se pudo renderizar un movimiento:', e);
      }
    }

    document.addEventListener('DOMContentLoaded', cargarMovimientos);
  </script>

  <!-- ===== Script del modal de cámara (único) ===== -->
  <script>
(() => {
  const CAM_PRIME_KEY = 'cam_primed_v1';
  const CAM_DISMISSED_AT = 'cam_dismissed_at';
  const DISMISS_COOLDOWN_MS = 5 * 60 * 1000; // 5 minutos

  const $modal  = document.getElementById('cam-modal');
  const $enable = document.getElementById('cam-enable-btn');
  const $later  = document.getElementById('cam-later-btn');
  const $close  = document.getElementById('cam-close-btn');

  function openCamModal(){
    if ($modal && $modal.hasAttribute('hidden')) {
      $modal.removeAttribute('hidden');
      $modal.style.display = ''; // por si quedó inline
      document.body.style.overflow = 'hidden';
      setTimeout(() => $enable?.focus(), 0);
    }
  }

  function closeCamModal(){
    if ($modal && !$modal.hasAttribute('hidden')) {
      $modal.setAttribute('hidden', '');
      $modal.style.display = 'none';
      document.body.style.overflow = '';

      // Guardar marca de tiempo del descarte
      sessionStorage.setItem(CAM_DISMISSED_AT, Date.now().toString());
    }
  }

  function stopStream(stream){ try { stream.getTracks().forEach(t => t.stop()); } catch{} }

  async function tryPrimeCamera(){
    if (!('mediaDevices' in navigator) || !('getUserMedia' in navigator.mediaDevices)) return false;
    if (!window.isSecureContext) return false; // HTTPS o localhost
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } });
      stopStream(stream);
      localStorage.setItem(CAM_PRIME_KEY,'1');
      return true;
    } catch(e){
      console.warn('[cam] getUserMedia error:', e?.name, e?.message);
      return false;
    }
  }

  async function initCamPermissionUX(){
    // Si ya está primeado → no mostrar más
    if (localStorage.getItem(CAM_PRIME_KEY) === '1') return;

    // Chequear cooldown
    const dismissedAt = Number(sessionStorage.getItem(CAM_DISMISSED_AT) || 0);
    if (dismissedAt && Date.now() - dismissedAt < DISMISS_COOLDOWN_MS) {
      console.log('[cam] Modal descartado hace menos de 5 min, no mostrar.');
      return;
    }

    try {
      if (navigator.permissions?.query){
        const st = await navigator.permissions.query({ name: 'camera' });
        if (st.state === 'granted') {
          localStorage.setItem(CAM_PRIME_KEY,'1');
          return;
        }
      }
    } catch {}

    setTimeout(openCamModal, 700);
  }

  // Eventos de cierre
  $later?.addEventListener('click', closeCamModal);
  $close?.addEventListener('click', closeCamModal);

  // Cerrar al presionar Esc
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCamModal(); });

  // Cerrar clickeando el fondo
  $modal?.addEventListener('click', (e) => {
    if (e.target === $modal) closeCamModal();
  });

  // Botón habilitar cámara
  $enable?.addEventListener('click', async () => {
    $enable.disabled = true;
    const ok = await tryPrimeCamera();
    if (ok) {
      $enable.textContent = '¡Listo! ✔';
      setTimeout(closeCamModal, 900);
    } else {
      $enable.disabled = false;
    }
  });

  document.addEventListener('DOMContentLoaded', initCamPermissionUX);
})();
</script>


</body>
</html>
