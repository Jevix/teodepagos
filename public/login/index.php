<?php
session_start();

// Si la sesión está activa o la cookie existe, redirigir al home
if (isset($_SESSION['id_usuario'])) {
    header('Location: ../home');
    exit;
}

// Si la cookie 'remember_me' existe, restaurar la sesión desde la cookie
if (isset($_COOKIE['remember_me'])) {
    $user_data = json_decode($_COOKIE['remember_me'], true);
    
    // Restaurar los datos de la sesión desde la cookie
    $_SESSION['id_usuario'] = $user_data['id_usuario'];
    $_SESSION['tipo_usuario'] = $user_data['tipo_usuario'];
    
    if (isset($user_data['id_entidad'])) {
        $_SESSION['id_entidad'] = $user_data['id_entidad'];
    }

    // Redirigir al home directamente
    header('Location: ../home');
    exit;
}

// Cargar la configuración de la base de datos
$config = require '../../config/config.php';

// Incluir la clase de la base de datos
require '../../src/Models/Database.php';

// Instanciar la conexión a la base de datos
try {
    $db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    $pdo = $db->getConnection();
} catch (PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Inicializar variables de error
$error_dni = "";
$error_password = "";

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escapar los datos ingresados
    $dni = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    // Consulta a la base de datos para verificar el usuario por DNI
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE dni = :dni");
    $stmt->execute(['dni' => $dni]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el DNI existe en la base de datos
    if ($user) {
        // Verificar la contraseña
        if ($user['password'] === $password) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['id_usuario'] = $user['id_usuario'];

            // Verificar el tipo de usuario
            if ($user['tipo_usuario'] === 'Miembro') {
                $_SESSION['id_entidad'] = $user['id_entidad'];
            }

            // Almacenar el tipo de usuario
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

            // Crear una cookie para recordar al usuario, se almacena por 30 días
            $user_data = json_encode([
                'id_usuario' => $user['id_usuario'],
                'tipo_usuario' => $user['tipo_usuario'],
                'id_entidad' => isset($user['id_entidad']) ? $user['id_entidad'] : null
            ]);
            setcookie('remember_me', $user_data, time() + (30 * 24 * 60 * 60), "/"); // 30 días

            // Redirigir al home con retraso para simular la espera
            echo "<script>
                    setTimeout(function() {
                        window.location.href = '../home';
                    }, 1000); // Retraso de 2 segundos
                  </script>";
            exit;
        } else {
            // Contraseña incorrecta
            $error_password = "Contraseña incorrecta";
        }
    } else {
        // DNI no encontrado
        $error_dni = "No se encontró usuario con este DNI";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="../styles.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet" /> 
    <style>
      /* Loader Styles */
      .loader {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8); /* Fondo semitransparente */
        z-index: 9999; /* Asegúrate de que el loader esté por encima de todo */
        display: flex;
        justify-content: center;
        align-items: center;
      }

      /* Reemplazar la animación CSS con una imagen GIF */
      .loader img {
        width: 100px; /* Ajusta el tamaño del GIF */
        height: 100px;
      }

      /* Difuminar fondo cuando el loader está visible */
      .blurred-content {
        filter: blur(5px);
      }
    </style>
  </head>

  <body>

    <section class="login">
      <!-- Loader con el GIF -->
      <div id="loader" class="loader" style="display: none;">
        <img src="../img/loader.gif" alt="Cargando..." />
      </div>

      <!-- Contenido que no será afectado por el blur -->
      <img src="../img/logo.png" alt="Logo" />

      <!-- Contenido que sí será afectado por el blur -->
      <section class="section-form" id="formContent">
        <form action="index.php" method="POST" class="form" id="loginForm" autocomplete="off">
          <div class="form-container">
            <label for="username"> DNI </label>
            <input type="text" id="username" name="username" inputmode="numeric" required />
            <p class="error" style="display: <?= !empty($error_dni) ? 'block' : 'none'; ?>;">
              <?= !empty($error_dni) ? $error_dni : ''; ?>
            </p>
          </div>
          <div class="form-container">
            <label for="password"> Contraseña 
              <p class="description">Tu fecha de nacimiento (díamesaño)</p> 
            </label>
            <input type="password" name="password" id="password" inputmode="numeric" placeholder="Ej: 25052005" required />
            <img src="../img/viendo.svg" alt="Mostrar contraseña" class="show_password" id="show_password" />
            <p class="error" style="display: <?= !empty($error_password) ? 'block' : 'none'; ?>;">
              <?= !empty($error_password) ? $error_password : ''; ?>
            </p>
          </div>
          <button type="submit" class="btn-primary submit--off" id="submitButton"> Ingresar </button>
        </form>
      </section>
    </section>

    <script src="../assets/js/login.js"></script>

    <script>
      document.addEventListener("DOMContentLoaded", function() {
        const loader = document.getElementById("loader");
        const formContent = document.getElementById("formContent");

        // Mostrar el loader cuando se envía el formulario
        const form = document.getElementById("loginForm");
        form.addEventListener("submit", function(event) {
          event.preventDefault(); // Detener el envío del formulario

          loader.style.display = "flex"; // Mostrar el loader
          formContent.classList.add("blurred-content"); // Aplicar blur al contenido del formulario

          // Simular un retraso de 2 segundos antes de enviar el formulario
          setTimeout(function() {
            form.submit(); // Enviar el formulario después del retraso
          }, 2000); // 2000 milisegundos = 2 segundos
        });
      });
    </script>
  </body>
</html>
