<?php
// 30 días de sesión
$session_lifetime = 60 * 60 * 24 * 30;
session_set_cookie_params($session_lifetime);
session_start();

if (!isset($_SESSION['id_usuario'])) {
  header('Location: ../login');
  exit;
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Movimientos</title>
    <link rel="stylesheet" href="../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
    <style>
      body { background: linear-gradient(199deg, #324798 0%, #101732 65.93%); }
    </style>
  </head>
  <body>
    <section class="main movimientos">
      <nav class="navbar">
        <a href="../index.php">
          <img src="../img/back.svg" alt="" />
        </a>
        <p class="h2">Movimientos</p>
      </nav>

      <div class="container-white">
        <div class="historial" id="historial">
          <p class="hb">Hoy</p>
          <!-- Los movimientos se inyectan abajo por JS, mismo markup que antes -->
        </div>
        <div class="background"></div>
      </div>
    </section>

    <script>
      const historial = document.getElementById('historial');

      function iconPath(name) {
        switch (name) {
          case 'bank': return '../img/bank.svg';
          case 'company': return '../img/company.svg';
          default: return '../img/user.svg';
        }
      }
      function fmtMontoSigned(n) {
        const v = Number(n) || 0;
        const abs = Math.abs(v);
        return (v < 0 ? '-' : '+') + '$' + abs.toLocaleString('es-AR', { maximumFractionDigits: 0 });
      }
      function fmtHora(fechaStr) {
        try {
          const d = new Date(String(fechaStr || '').replace(' ', 'T'));
          if (isNaN(d.getTime())) return '';
          return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        } catch { return ''; }
      }
      function renderMovimiento(m) {
        // <div class="componente--movimiento"> … </div>
        const wrap = document.createElement('div');
        wrap.className = 'componente--movimiento';

        const left = document.createElement('div');
        left.className = 'left';
        const img = document.createElement('img');
        img.src = iconPath(m.icon);
        img.alt = 'Entidad';
        left.appendChild(img);

        const right = document.createElement('div');
        right.className = 'right';

        const arriba = document.createElement('div');
        arriba.className = 'arriba';
        const pNombre = document.createElement('p');
        pNombre.className = 'h5';
        pNombre.textContent = m.contraparte || '—';

       const pMonto = document.createElement('p');

if (m.tag === 'error') {
  // Caso especial para Error: sin signo, estilo neutral
  pMonto.className = 'h4 text--neutral';
  pMonto.textContent = '$' + (m.monto ?? 0).toLocaleString('es-AR', {
    maximumFractionDigits: 0
  });
} else {
  // Comportamiento normal
  const signed = Number(m.montoSigned) || 0;
  pMonto.className = 'h4 ' + (signed < 0 ? 'text--minus' : 'text--plus');
  pMonto.textContent = fmtMontoSigned(signed);
}

        arriba.appendChild(pNombre);
        arriba.appendChild(pMonto);

        const abajo = document.createElement('div');
        abajo.className = 'abajo';
        const pDesc = document.createElement('p');
        pDesc.className = 'hb';
        pDesc.textContent = m.descripcion || 'Movimiento';

        const pHora = document.createElement('p');
        pHora.className = 'hb';
        pHora.textContent = fmtHora(m.fecha);

        abajo.appendChild(pDesc);
        abajo.appendChild(pHora);

        right.appendChild(arriba);
        right.appendChild(abajo);

        wrap.appendChild(left);
        wrap.appendChild(right);

        historial.appendChild(wrap);
      }

      async function cargarMovimientos() {
        try {
          // Lee la API (usuario). Si tu endpoint quedó sin paginación, podés quitar page/page_size.
          const url = `../api/movimientos.php?for=usuario&page=1&page_size=1000`;
          const res = await fetch(url, { credentials: 'same-origin' });
          if (!res.ok) throw new Error('Error API');

          const data = await res.json();
          const items = Array.isArray(data.items) ? data.items : [];

          if (items.length === 0) {
            const p = document.createElement('p');
            p.textContent = 'No tienes movimientos todavía.';
            historial.appendChild(p);
            return;
          }

          items.forEach(renderMovimiento);
        } catch (e) {
          console.error(e);
          const p = document.createElement('p');
          p.textContent = 'No se pudieron cargar los movimientos.';
          historial.appendChild(p);
        }
      }

      document.addEventListener('DOMContentLoaded', cargarMovimientos);
    </script>
  </body>
</html>
