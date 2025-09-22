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
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
    rel="stylesheet"
  />
  <style>
    html {
  -webkit-text-size-adjust: 100%;
  -ms-text-size-adjust: 100%;
  text-size-adjust: 100%;
}

    body {
      background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
    }
    .bg-ventana-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(10px);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    .ventana-modal {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
      z-index: 1001;
    }
    .loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
  </style>
</head>
<body>
  <!-- Loader -->
  <div id="loader" class="loader" style="display: none;">
    <img src="../img/loader.gif" alt="Cargando..." />
  </div>

  <section class="home-user">
    <!-- NAV -->
    <nav class="navbar">
      <div class="left">
        <img src="../img/saludo.svg" alt="" />
        <div>
          <p class="documento"><?php echo $usuario['dni']; ?></p>
          <p class="nombre"><?php echo $usuario['nombre_apellido']; ?></p>
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
      <p class="h1">$ <?php echo number_format($usuario['saldo'], 0, ',', '.'); ?></p>
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
      <div <?php echo (!empty($entidad) && $entidad['tipo_entidad'] === 'Empresa') ? '' : 'style="display: none;"'; ?>>
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
        <button class="btn-modal-2 h3" onclick="logout()" style="width: 149px;">Cerrar sesión</button>
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
      const abs = Math.abs(n);
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
        console.log(items);

        if (items.length === 0) {
          listEl.innerHTML = `
            <div style="text-align:center;">
              <p class="h2 text--light">Todavía no realizaste ninguna transferencia.</p>
            </div>`;
          return;
        }

        // Render primeros 3
        items.slice(0, VISIBLE_COUNT).forEach(renderMovimiento);

        // Agregar botón si hay más
        const total = (typeof data.total === 'number') ? data.total : items.length;
        if (total > VISIBLE_COUNT) {
          const btnMas = document.createElement('button');
          btnMas.className = "btn-primary";
          btnMas.textContent = "Historial de movimientos";
          btnMas.addEventListener('click', () => {
            window.location.href = './movimientos.php';
          });
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
          // Caso especial para Error
          pMonto.className = 'h4 text--neutral';
          pMonto.textContent = '$' + (item.monto ?? 0).toLocaleString('es-AR', { maximumFractionDigits: 0 });
        } else {
          // Comportamiento normal
          pMonto.className = 'h4 ' + (item.montoSigned < 0 ? 'text--minus' : 'text--plus');
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
</body>
</html>
