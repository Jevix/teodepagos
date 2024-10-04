<?php

session_start();
//en el caso de que la session este activa, redirigir al home
if (isset($_SESSION['usuario'])) {
  // Redirigir al home si la sesión está activa
  header('Location: ../home.php');
  exit; // Es importante terminar el script después de la redirección
}

// Cargar el archivo de configuración de la base de datos
$config = require '../../config/config.php';

// Incluir la clase de la base de datos
require '../../src/Models/Database.php';

// Instanciar la conexión a la base de datos
try {
    $db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    $pdo = $db->getConnection();
} catch (PDOException $e) {
    // Si hay un error en la conexión, capturarlo y mostrar el mensaje de error
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}

// Inicializar variable de error
$error = "";

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
        // Si el DNI existe, verificar la contraseña
        if ($user['password'] === $password) {
            session_start();
            // Contraseña correcta, iniciar sesión y redirigir al home
            $_SESSION['id_usuario'] = $user['id_usuario'];

            // Verificar el tipo de usuario
            if ($user['tipo_usuario'] === 'Miembro') {
                // Si es un Miembro, almacenar también el id_entidad
                $_SESSION['id_entidad'] = $user['id_entidad'];
            }

            // Siempre almacenar el tipo de usuario, independientemente de si es 'Miembro' o no
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            
            header('Location: ../home');
            exit; // Terminar el script después de la redirección
        } else {
            // Contraseña incorrecta
            $error_password = "Contraseña incorrecta";
        }
    } else {
        // DNI no encontrado
        $error_dni = "No se encontró usuario con este DNI";
    }
}else{
    $error = "Coloque su dni y su password";
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
  </head>
  <body>
    <section class="login">
      <img src="../img/logo.png" alt="" />
      <section class="section-form">
        <form action="index.php" method="POST" class="form" id="loginForm" autocomplete="off">
          <div class="form-container">
            <label for="username"> DNI </label>
            <input type="text" id="username" name="username" inputmode="numeric"  required />
            <p class="error" style="display: <?= !empty($error_dni) ? 'block' : 'none'; ?>;">
              <?= !empty($error_dni) ? $error_dni : ''; ?>
            </p>
          </div>
          <div class="form-container"> <label for="password"> Contraseña <p class="description">Tu fecha de nacimiento (díamesaño)</p> </label>
            <input type="password" name="password" id="password" inputmode="numeric" placeholder="Ej: 25052005" />
            <img src="../img/viendo.svg" alt="" class="show_password" id="show_password" />
            <p class="error" style="display: <?= !empty($error_password) ? 'block' : 'none'; ?>;">
              <?= !empty($error_password) ? $error_password : ''; ?>
            </p>
          </div>
          <button type="submit" class="btn-primary submit--off" id="submitButton"> Ingresar </button>
        </form>
      </section>
    </section>
    <script src="../assets/js/login.js"></script>
  </body>
</html>
