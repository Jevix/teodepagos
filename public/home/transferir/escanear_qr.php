<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../../login/');  // Redirigir a la página de login si no está autenticado
    exit;
}
?>
<html lang="es"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear QR</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&amp;display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }

    </style>
  <style type="text/css" id="operaUserStyle"></style></head>
  <body cz-shortcut-listen="true">
    <section class="main">
      <nav class="navbar">
        <a href="index.php">
          <img src="../../img/back.svg" alt="">
        </a>
        <p class="h2">Escanear QR</p>
      </nav>
      
      <div class="container-white">
      <div class="container">
            <div class="row justify-content-center mt-5">
                <div class="col-sm-4 shadow p-3">
                    <div id="reader"></div>
                </div>
            </div>
        </div>
        <div class="background"></div>
      </div>
    </section>
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../../assets/lector-camara/escaner-qr/assets/plugins/scanapp.min.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/lector-camara/escaner-qr/assets/js/basico.js?v=<?php echo time(); ?>"></script>
<style>
/* ===== Paleta / tokens rápidos ===== */
:root{
  --azul:#324798;
  --azul-10: rgba(50,71,152,.10);
  --gris-borde: rgba(15,23,42,.08);
  --gris-texto:#0f172a;
  --gris-2:#64748b;
  --ok:#10b981;
  --peligro:#ef4444;
}

/* ===== Card del lector ===== */
#reader{
  background:#fff;
  border:1px solid var(--gris-borde);
  border-radius:18px;
  padding:16px;
  overflow:hidden;
}

/* Header/message limpio */
#reader__header_message{
  display:block !important;
  border-top:none !important;
  background:transparent !important;
  color:var(--gris-2) !important;
  font-weight:600;
  padding:6px 8px !important;
  margin:0 0 6px 0 !important;
}

/* Ocultar icono info */
#reader img[alt="Info icon"]{ display:none !important; }

/* ===== Área de cámara ===== */


/* Video / canvas redondeados */
#reader__scan_region video,
#reader__scan_region canvas{
  display:block;
  width:100% !important;
  height:auto !important;
  border-radius:14px;
}

/* Marco de escaneo (esquinas) */

/* “Scanner paused” badge */
#reader__scan_region > div[style*="Scanner paused"]{
  top:10px !important;
  left:50%;
  transform:translateX(-50%);
  width:auto !important;
  padding:6px 10px !important;
  border-radius:999px;
  background:rgba(2,6,23,.6) !important;
  color:#fffbe8 !important;
}

/* ===== Controles propios que inyectaste (.qr-ui) ===== */
.qr-ui{ margin-top:12px; display:flex; flex-direction:column; gap:10px; }
.qr-title{ margin:0; font-weight:800; color:var(--gris-texto); }
.qr-help{ color:var(--gris-2); font-size:.92rem; }

.qr-actions{ display:flex; gap:10px; flex-wrap:wrap; }
.qr-btn{
  appearance:none; border:0; cursor:pointer; font-weight:700;
  border-radius:999px; padding:10px 16px;
  transition: transform .06s ease, box-shadow .18s ease, opacity .18s ease;
}
.qr-btn.primary{
  background:var(--azul); color:#fff;
}
.qr-btn.primary:hover{ transform:translateY(-1px); }
.qr-btn.primary:active{ transform:none; }
.qr-btn.ghost{ background:#f1f5f9; color:var(--gris-texto); }

/* ===== Controles nativos (select + botones) ===== */
#reader__dashboard{ margin-top:10px; }

#html5-qrcode-select-camera{
  appearance:none; -webkit-appearance:none;
  background:#f8fafc
    url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'/></svg>")
    no-repeat right 10px center/14px;
  border:1px solid #e2e8f0; border-radius:10px;
  padding:10px 34px 10px 12px; font-weight:600; color:var(--gris-texto);
  margin-left:6px;
}
#html5-qrcode-select-camera:focus{ outline:2px solid var(--azul-10); }

/* Botones nativos */
#html5-qrcode-button-camera-start,
#html5-qrcode-button-camera-permission,
#html5-qrcode-button-file-selection,
#html5-qrcode-button-camera-stop{
  appearance:none; border:0; cursor:pointer; font-weight:700;
  border-radius:999px; padding:10px 16px; margin:6px 6px 0 0;
  transition: transform .06s ease, box-shadow .18s ease, opacity .18s ease;
}

#html5-qrcode-button-camera-start,
#html5-qrcode-button-camera-permission,
#html5-qrcode-button-file-selection{
  background:var(--azul); color:#fff;
}
#html5-qrcode-button-camera-start:hover,
#html5-qrcode-button-camera-permission:hover,
#html5-qrcode-button-file-selection:hover{ transform:translateY(-1px); }

#html5-qrcode-button-camera-stop{
  background:var(--peligro); color:#fff;
}

/* Zona “soltar imagen” si llega a mostrarse */
#reader [style*="dashed"][style*="padding"]{
  border-radius:12px !important;
  background:#fafafa;
}



/* ===== Estados (éxito/error; opcional por si seteás mensajes) ===== */
.html5-qrcode-success{ color:var(--ok) !important; }
.html5-qrcode-error{ color:var(--peligro) !important; }
/* Cuando hay escaneo activo, ocultar la UI auxiliar */
.scanning .qr-ui{ display:none !important; }

</style>

<script>
(() => {
  const LAST_CAM_KEY = 'preferred_cam_id_v2';
  let didAutoselect = false; // evitamos pisar la elección del usuario

  function preferBackOnce(select){
    if (!select || didAutoselect) return;
    const opts = Array.from(select.options || []);
    if (!opts.length) return;

    // Si hay una guardada válida, usala y listo (no tocamos más)
    const saved = localStorage.getItem(LAST_CAM_KEY);
    if (saved && opts.some(o => o.value === saved)) {
      select.value = saved;
      select.dispatchEvent(new Event('change', { bubbles:true }));
      didAutoselect = true;
      return;
    }

    // Si no, elegimos “trasera/environment” si existe; si no, dejamos la que vino
    const backIdx = opts.findIndex(o => /back|rear|environment|atr(á|a)s|traser/i.test(o.text || ''));
    if (backIdx >= 0 && select.selectedIndex !== backIdx) {
      select.selectedIndex = backIdx;
      select.dispatchEvent(new Event('change', { bubbles:true }));
    }
    didAutoselect = true;
  }

 // === helpers para mostrar/ocultar la UI auxiliar ===
  function setScanning(on){
    document.body.classList.toggle('scanning', !!on);
  }
  function bindVideoPlayingHideUI(){
    const video = document.querySelector('#reader__scan_region video');
    if (!video) return;
    // cuando el video empieza a reproducir, ocultamos la UI
    video.addEventListener('playing', () => setScanning(true), { once:false });
  }

  // Observador principal que ya tenés
  const mo = new MutationObserver(() => {
    const reader = document.getElementById('reader');
    if (!reader) return;

    const startBtn = document.getElementById('html5-qrcode-button-camera-start')
                   || document.getElementById('html5-qrcode-button-camera-permission');
    const stopBtn  = document.getElementById('html5-qrcode-button-camera-stop');
    const fileBtn  = document.getElementById('html5-qrcode-button-file-selection');
    const camSel   = document.getElementById('html5-qrcode-select-camera');

    if (!startBtn && !fileBtn) return;

    // ...tu código de labels / preferBackOnce / inyección de .qr-ui...

    // --- NUEVO: ocultar .qr-ui al iniciar / mostrar al detener ---
    // si usás tu botón "Usar cámara"
    document.getElementById('ui-open-camera')?.addEventListener('click', () => {
      // al hacer click pedimos cámara, esperamos que el <video> empiece
      // y cuando esté "playing" ocultamos la UI
      setTimeout(bindVideoPlayingHideUI, 50);
    });

    // si usan directamente el botón nativo de la librería
    startBtn?.addEventListener('click', () => {
      setTimeout(bindVideoPlayingHideUI, 50);
    }, { capture:true });

    stopBtn?.addEventListener('click', () => {
      setScanning(false);          // vuelve a mostrarse la .qr-ui
    });

    // Si ya hay un <video> activo (p.ej. permisos recordados), lo enganchamos
    bindVideoPlayingHideUI();

    mo.disconnect();
  });

  document.addEventListener('DOMContentLoaded', () => {
    const reader = document.getElementById('reader');
    if (reader) mo.observe(reader, { childList:true, subtree:true });
  });
})();
</script>

</body>

</html>
