<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../../login');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

$id_entidad = $_SESSION['id_entidad'];

// Verificar el tipo de entidad y el tipo de usuario (miembro o no)
$query = "
    SELECT e.tipo_entidad, u.tipo_usuario 
    FROM entidades e
    LEFT JOIN usuarios u ON u.id_entidad = e.id_entidad
    WHERE e.id_entidad = :id_entidad";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':id_entidad', $id_entidad, PDO::PARAM_INT);
$stmt->execute();
$entidad = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no es un banco o el tipo de usuario no es miembro, redirigir a index.php
if ($entidad['tipo_entidad'] !== 'Banco' || $entidad['tipo_usuario'] !== 'Miembro') {
    header('Location: index.php');
    exit;
}

// Configurar los parámetros para la paginación o búsqueda
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$results_per_page = 10; // Resultados por página
$start_from = ($page - 1) * $results_per_page;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para obtener los datos de entidades y usuarios con opción de búsqueda
$query = "
    SELECT * FROM (
        SELECT nombre_entidad AS nombre, cuit AS identificador, saldo, tipo_entidad AS tipo FROM entidades
        WHERE nombre_entidad LIKE :search OR cuit LIKE :search
        UNION ALL
        SELECT nombre_apellido AS nombre, dni AS identificador, saldo, 'usuario' AS tipo FROM usuarios
        WHERE nombre_apellido LIKE :search OR dni LIKE :search
    ) AS cuentas_combinadas
    ORDER BY nombre ASC
    LIMIT :start_from, :results_per_page
";

$stmt = $pdo->prepare($query);
$search_term = "%$search_query%";
$stmt->bindParam(':start_from', $start_from, PDO::PARAM_INT);
$stmt->bindParam(':results_per_page', $results_per_page, PDO::PARAM_INT);
$stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si es una solicitud AJAX, devolver los resultados en JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode($cuentas);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cuentas</title>
    <link rel="stylesheet" href="../../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
    <style>
      body {
        background: linear-gradient(199deg, #324798 0%, #101732 65.93%);
      }
    </style>
</head>
<body>
<section class="main">
    <nav class="navbar">
        <a href="index.php">
          <img src="../../img/back.svg" alt="Volver" />
        </a>
        <p class="h2">Cuentas</p>
    </nav>

    <div class="container-input">
        <input
            type="text"
            name="search"
            id="search"
            placeholder="Busca por nombre,dni,cuit..."
            oninput="filtrarCuentas()"
        />
    </div>

    <div class="container-white container-buscar-cuentas" id="listaCuentas">
        <!-- Aquí es donde se insertarán las cuentas dinámicamente -->
        <?php foreach ($cuentas as $cuenta): ?>
          <div class="componente--usuario" onclick="redirigir('<?= htmlspecialchars($cuenta['identificador']); ?>')">
            <div class="left">
              <?php if ($cuenta['tipo'] === 'Banco'): ?>
                <img src="../../img/banco.svg" alt="Banco" />
              <?php elseif ($cuenta['tipo'] === 'Empresa'): ?>
                <img src="../../img/empresa.svg" alt="Empresa" />
              <?php elseif ($cuenta['tipo'] === 'usuario'): ?>
                <img src="../../img/user.svg" alt="Usuario" />
              <?php endif; ?>
              <div>
                <p class="h4"><?= htmlspecialchars($cuenta['nombre']); ?></p>
                <p class="hb"><?= htmlspecialchars($cuenta['identificador']); ?></p>
              </div>
            </div>
            <div class="right">
              <p class="h4 text--blue">$<?= number_format($cuenta['saldo'], 0, ',', '.'); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="background"></div>
    </div>
</section>

<script>
let page = 2; // Comenzamos desde la segunda página, ya que la primera está cargada
let isLoading = false;

// Función para cargar más cuentas cuando se hace scroll o buscar
function cargarMasCuentas() {
    if (isLoading) return; // Evitar peticiones duplicadas
    isLoading = true;
    const search = document.getElementById('search').value;

    const xhr = new XMLHttpRequest();
    xhr.open('GET', `<?php echo $_SERVER['PHP_SELF']; ?>?page=${page}&search=${encodeURIComponent(search)}&ajax=1`, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            const listaCuentas = document.getElementById('listaCuentas');
            
            // Guardar el div.background sin eliminarlo
            let backgroundDiv = document.querySelector('.background');

            // Si no existe, crear el div.background
            if (!backgroundDiv) {
                backgroundDiv = document.createElement('div');
                backgroundDiv.classList.add('background');
                listaCuentas.appendChild(backgroundDiv);
            }

            // Agregar las nuevas cuentas
            data.forEach(cuenta => {
                const divCuenta = document.createElement('div');
                divCuenta.classList.add('componente--usuario');
                divCuenta.onclick = () => redirigir(cuenta.identificador);

                // Creando el div.left con su estructura correcta
                const leftDiv = document.createElement('div');
                leftDiv.classList.add('left');

                let img = document.createElement('img');
                if (cuenta.tipo === 'Banco') {
                    img.src = '../../img/banco.svg';
                    img.alt = 'Banco';
                } else if (cuenta.tipo === 'Empresa') {
                    img.src = '../../img/empresa.svg';
                    img.alt = 'Empresa';
                } else if (cuenta.tipo === 'usuario') {
                    img.src = '../../img/user.svg';
                    img.alt = 'Usuario';
                }

                const textContainer = document.createElement('div'); // Aquí es donde irán los textos
                const nameP = document.createElement('p');
                nameP.classList.add('h4');
                nameP.textContent = cuenta.nombre;

                const idP = document.createElement('p');
                idP.classList.add('hb');
                idP.textContent = cuenta.identificador;

                // Agregar textos al contenedor de textos
                textContainer.appendChild(nameP);
                textContainer.appendChild(idP);

                // Agregar la imagen y el contenedor de textos al div.left
                leftDiv.appendChild(img);
                leftDiv.appendChild(textContainer);

                // Creando el div.right para el saldo
                const rightDiv = document.createElement('div');
                rightDiv.classList.add('right');

                const saldoP = document.createElement('p');
                saldoP.classList.add('h4', 'text--blue');
                saldoP.textContent = `$${cuenta.saldo.toLocaleString()}`;

                // Agregar el saldo al div.right
                rightDiv.appendChild(saldoP);

                // Agregar div.left y div.right al divCuenta
                divCuenta.appendChild(leftDiv);
                divCuenta.appendChild(rightDiv);

                // Añadir la nueva cuenta antes del div.background
                listaCuentas.insertBefore(divCuenta, backgroundDiv);
            });

            page++; // Incrementar el número de página para la siguiente carga
            isLoading = false;
        }
    };
    xhr.send();
}

// Función para redirigir a los detalles de la cuenta
function redirigir(id) {
    window.location.href = './detalles_cuenta.php?id=' + id;
}

// Función para filtrar las cuentas y buscar en la base de datos
function filtrarCuentas() {
    page = 1; // Reiniciar el contador de páginas
    const listaCuentas = document.getElementById('listaCuentas');

    // Limpiar la lista para nuevos resultados cuando se realiza una búsqueda
    const cuentasExistentes = listaCuentas.querySelectorAll('.componente--usuario');
    cuentasExistentes.forEach(cuenta => cuenta.remove());

    cargarMasCuentas(); // Cargar los resultados filtrados desde el servidor
}

// Detectar el final del scroll para cargar más cuentas
window.onscroll = function () {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight) {
        cargarMasCuentas(); // Cargar más resultados cuando se llega al final de la página
    }
};


</script>
</body>
</html>
