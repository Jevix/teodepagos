<?php
session_start();
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../index.php');
    exit;
}

$id_entidad   = $_SESSION['id_entidad'];
$tipo_entidad = $_SESSION['tipo_entidad'] ?? null;
$id_usuario   = $_SESSION['id_usuario'] ?? null;

require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

// Cargar entidad
$stmt = $pdo->prepare("SELECT * FROM entidades WHERE id_entidad = :id_entidad");
$stmt->execute(['id_entidad' => $id_entidad]);
$entidad = $stmt->fetch(PDO::FETCH_ASSOC);

if ($entidad) {
    $_SESSION['nombre_entidad'] = $entidad['nombre_entidad'];
    $_SESSION['tipo_entidad']   = $entidad['tipo_entidad'];
    $_SESSION['cuit']           = $entidad['cuit'];
} else {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" translate="no">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home - Entidad</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
    <style>
      body { background: linear-gradient(199deg, #324798 0%, #101732 65.93%); }
      .bg-ventana-modal {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,.5); backdrop-filter: blur(10px);
        display: none; justify-content: center; align-items: center; z-index: 1000;
      }
      .ventana-modal {
        background: #fff; padding: 20px; border-radius: 10px; text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,.1); z-index: 1001;
      }
      .loader {
        position: fixed; top:0; left:0; width:100%; height:100%;
        background-color: rgba(255,255,255,.7);
        display:flex; justify-content:center; align-items:center; z-index:9999;
      }
    </style>
  </head>
  <body>
    <!-- Loader -->
    <div id="loader" class="loader" style="display:none;">
      <img src="../../img/loader.gif" alt="Cargando..." />
    </div>

    <section class="home-user">
      <!-- NAV -->
      <nav class="navbar">
        <div class="left">
          <?php
            if ($entidad['tipo_entidad'] === 'Banco') {
              echo '<img src="../../img/banco-white.svg" alt="Banco" />';
            } elseif ($entidad['tipo_entidad'] === 'Empresa') {
              echo '<img src="../../img/empresa-white.svg" alt="Empresa" />';
            }
          ?>
          <div>
            <p class="documento"><?php echo htmlspecialchars($entidad['cuit'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="nombre"><?php echo htmlspecialchars($entidad['nombre_entidad'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </div>
        <div class="right">
          <?php if ($entidad['tipo_entidad'] === 'Banco'): ?>
            <a href="../../logout.php"><img src="../../img/salir.svg" alt="Salir" class="salir" /></a>
          <?php else: ?>
            <a href="../index.php"><img src="../../img/back.svg" alt="Volver" class="salir" /></a>
          <?php endif; ?>
        </div>
      </nav>

      <!-- HEADER SALDO / INFO -->
      <div class="dinero">
        <?php if ($entidad['tipo_entidad'] === 'Banco'): ?>
          <p class="h1 text--info">Entidad bancaria</p>
        <?php else: ?>
          <p class="h2">Saldo disponible</p>
          <p class="h1">$ <?php echo number_format((float)$entidad['saldo'], 0, ',', '.'); ?></p>
        <?php endif; ?>
      </div>

      <!-- ACCIONES -->
      <div class="transacciones">
        <?php if ($entidad['tipo_entidad'] === 'Banco'): ?>
          <div onclick="showLoaderAndRedirect('cuentas.php')">
            <img src="../../img/account_1.svg" alt="Cuentas" />
            <p class="hb">Cuentas</p>
          </div>
          <div onclick="showLoaderAndRedirect('agregar_usuario.php')">
            <img src="../../img/agregar_usuario.svg" alt="Agregar" />
            <p class="hb">Agregar</p>
          </div>
          <div onclick="showLoaderAndRedirect('emitir_dinero/buscar_usuario.php')">
            <img src="../../img/emitir.svg" alt="Emitir dinero" style="width:40px;height:40px;" />
            <p class="hb">Emitir dinero</p>
          </div>
        <?php else: ?>
          <div onclick="showLoaderAndRedirect('transferir')">
            <img src="../../img/transferir.svg" alt="Transferir" />
            <p class="hb">Transferir</p>
          </div>
          <div onclick="showLoaderAndRedirect('miqr.php')">
            <img src="../../img/qr.svg" alt="Tu QR" />
            <p class="hb">Tu QR</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- MOVIMIENTOS (idéntico patrón que en home de usuario) -->
      <div class="movimientos">
        <p class="h2" id="h2" style="color:#172146;">Movimientos</p>
        <div id="movs-list" class="movimientos-container">
          <!-- filas -->
          <div id="container-btn" class="container-btn"></div>
        </div>
      </div>

      <div class="background"></div>
    </section>

    <script>
      function showLoaderAndRedirect(url) {
        const loader = document.getElementById('loader');
        loader.style.display = 'flex';
        setTimeout(() => { window.location.href = url; }, 1000); // igual que home usuario
      }

      const VISIBLE_COUNT = 3;
      const REQUEST_PAGE_SIZE = 50;

      const listEl = document.getElementById('movs-list');
      const containerBtn = document.getElementById('container-btn');

      function fmtMonto(n) {
        const v = Number(n) || 0;
        const abs = Math.abs(v);
        return (v < 0 ? '-' : '+') + '$' + abs.toLocaleString('es-AR', { maximumFractionDigits: 0 });
      }
      function iconPath(name) {
        switch (name) {
          case 'bank': return '../../img/bank.svg';
          case 'company': return '../../img/company.svg';
          case 'error' : return '../../img/bank.svg';
          default: return '../../img/user.svg';
        }
      }
      function fmtHoraSafe(fechaStr) {
        try {
          const d = new Date(String(fechaStr || '').replace(' ', 'T'));
          if (isNaN(d.getTime())) return '';
          return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
        } catch {
          return '';
        }
      }

      async function cargarMovimientosEntidad() {
        try {
          const url = `../../api/movimientos.php?for=entidad&page=1&page_size=${REQUEST_PAGE_SIZE}`;
          const res = await fetch(url, { credentials: 'same-origin' });
          if (!res.ok) throw new Error('Error al cargar movimientos');

          const data = await res.json();
          const items = Array.isArray(data.items) ? data.items : [];

          // Empty state
          if (items.length === 0) {
            listEl.innerHTML = `
              <div style="text-align:center;">
                <p class="h2 text--light">Todavía no tenés ningún movimiento.</p>
              </div>`;
            return;
          }

          // Limpio y dejo el container del botón
          listEl.innerHTML = '<div id="container-btn" class="container-btn"></div>';
          const containerBtnLocal = document.getElementById('container-btn');

          // Render primeros 3 (como home usuario)
          items.slice(0, VISIBLE_COUNT).forEach(renderMovimiento);

          // Botón "Historial" si hay más
          const total = (typeof data.total === 'number') ? data.total : items.length;
          if (total > VISIBLE_COUNT || data.has_more) {
            const btnMas = document.createElement('button');
            btnMas.className = 'btn-primary';
            btnMas.textContent = 'Historial de movimientos';
            btnMas.addEventListener('click', () => {
              window.location.href = './movimientos.php';
            });
            containerBtnLocal.appendChild(btnMas);
          }

        } catch (e) {
          console.error(e);
          listEl.innerHTML = `<p class="hb" style="text-align:center;color:#b00;">No se pudieron cargar los movimientos.</p>`;
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
          const signed = Number(item.montoSigned) || 0;

          if (item.tag === 'error') {
            // Caso especial: Error → sin signo, estilo neutral
            pMonto.className = 'h4 text--neutral';
            pMonto.textContent = '$' + (item.monto ?? 0).toLocaleString('es-AR', { maximumFractionDigits: 0 });
          } else {
            // Normal: positivo/negativo
            pMonto.className = 'h4 ' + (signed < 0 ? 'text--minus' : 'text--plus');
            pMonto.textContent = fmtMonto(signed);
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

          // Insertar antes del container del botón
          const btnContainer = document.getElementById('container-btn');
          listEl.insertBefore(wrap, btnContainer || null);
        } catch (e) {
          console.warn('No se pudo renderizar un movimiento:', e);
        }
      }

      document.addEventListener('DOMContentLoaded', cargarMovimientosEntidad);
    </script>
  </body>
</html>
