<?php
session_start();

// Verificar si la entidad está autenticada
if (!isset($_SESSION['id_entidad'])) {
    header('Location: ../login.php');
    exit;
}

// Incluir la configuración y la clase Database
require '../../../src/Models/Database.php';
$config = require '../../../config/config.php';
$db = new Database($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
$pdo = $db->getConnection();

$mensaje = '';
$tipo_cuenta = '';
$nombre = '';
$apellido = '';
$dni = '';
$fechaNacimiento = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'];  // Tipo de cuenta a crear (usuario o banco)

    try {
        // Iniciar la transacción
        $pdo->beginTransaction();

        // Si es un usuario común
        if ($tipo === 'usuario') {
            $nombre = htmlspecialchars($_POST['nombre']);
            $apellido = htmlspecialchars($_POST['apellido']);
            $dni = htmlspecialchars($_POST['dni']);
            $fechaNacimiento = htmlspecialchars($_POST['fechaNacimiento']);
            $nombre_apellido = $nombre . ' ' . $apellido;

            // Insertar al usuario con saldo 0 y tipo_usuario 'Usuario'
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_apellido, dni, password, tipo_usuario, saldo) VALUES (?, ?, ?, 'Usuario', 0)");
            $stmt->execute([$nombre_apellido, $dni, $fechaNacimiento]);

            $tipo_cuenta = 'Usuario';

        // Si es un banco
        } elseif ($tipo === 'banco') {
            $nombreEntidad = htmlspecialchars($_POST['nombreEntidad']);
            $cuit = htmlspecialchars($_POST['cuit']);
            $nombreResponsable = htmlspecialchars($_POST['nombreResponsable']);
            $apellidoResponsable = htmlspecialchars($_POST['apellidoResponsable']);
            $dniResponsable = htmlspecialchars($_POST['dniResponsable']);
            $fechaNacimientoResponsable = htmlspecialchars($_POST['fechaNacimientoResponsable']);
            $nombre_apellido_responsable = $nombreResponsable . ' ' . $apellidoResponsable;

            // Insertar la entidad (Banco) en la tabla entidades
            $stmt = $pdo->prepare("INSERT INTO entidades (nombre_entidad, cuit, tipo_entidad) VALUES (?, ?, 'Banco')");
            $stmt->execute([$nombreEntidad, $cuit]);

            // Obtener el ID de la entidad recién creada
            $idEntidad = $pdo->lastInsertId();

            // Insertar el responsable como un "Miembro" en la tabla usuarios, vinculándolo al banco
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_apellido, dni, password, tipo_usuario, saldo, id_entidad) VALUES (?, ?, ?, 'Miembro', 0, ?)");
            $stmt->execute([$nombre_apellido_responsable, $dniResponsable, $fechaNacimientoResponsable, $idEntidad]);

            $nombre = $nombreResponsable;
            $apellido = $apellidoResponsable;
            $dni = $dniResponsable;
            $fechaNacimiento = $fechaNacimientoResponsable;
            $tipo_cuenta = 'Banco';
        }

        // Confirmar la transacción
        $pdo->commit();

        // Redirigir a creado.php usando POST
        echo '
        <form id="creadoForm" action="creado.php" method="POST">
            <input type="hidden" name="tipo_cuenta" value="' . $tipo_cuenta . '">
            <input type="hidden" name="nombre" value="' . $nombre . '">
            <input type="hidden" name="apellido" value="' . $apellido . '">
            <input type="hidden" name="dni" value="' . $dni . '">
            <input type="hidden" name="fechaNacimiento" value="' . $fechaNacimiento . '">
        </form>
        <script>
            document.getElementById("creadoForm").submit();
        </script>
        ';
        exit;

    } catch (Exception $e) {
        // Si ocurre un error, hacer rollback
        $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Crear cuenta</title>
    <link rel="stylesheet" href="../../styles.css" />
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
        <p class="h2">Crear cuenta</p>
    </nav>
    <div class="container-white">
        <div class="container-crear-cuenta">
            <!-- Mostrar el mensaje de éxito o error -->
            <?php if ($mensaje): ?>
                <p class="mensaje"><?= $mensaje; ?></p>
            <?php endif; ?>

            <form action="" method="POST" id="myForm">
                <div class="checkboxes">
                    <div>
                        <img src="../../img/user.svg" alt="Usuario" />
                        <p class="h2 text--darkblue">Usuario</p>
                        <input
                            type="radio"
                            name="tipo"
                            id="checkbox1"
                            class="checkbox"
                            value="usuario"
                            checked
                        />
                    </div>
                    <div>
                        <img src="../../img/banco.svg" alt="Banco" />
                        <p class="h2 text--darkblue">Banco</p>
                        <input
                            type="radio"
                            name="tipo"
                            id="checkbox2"
                            class="checkbox"
                            value="banco"
                        />
                    </div>
                </div>

                <!-- Campos para USUARIO -->
                <div id="usuarioForm">
                    <label for="nombre" class="h3 text--black">Nombre</label>
                    <input type="text" name="nombre" id="nombre"  />

                    <label for="apellido" class="h3 text--black">Apellido</label>
                    <input type="text" name="apellido" id="apellido"  />

                    <label for="dni" class="h3 text--black">DNI</label>
                    <input type="text" name="dni" id="dni"  />

                    <label for="fechaNacimiento" class="h3 text--black">Fecha de nacimiento</label>
                    <input type="text" name="fechaNacimiento" id="fechaNacimiento"  />
                </div>

                <!-- Campos para BANCO -->
                <div id="bancoForm" style="display: none;">
                    <label for="nombreEntidad" class="h3 text--black">Nombre del banco</label>
                    <input type="text" name="nombreEntidad" id="nombreEntidad" />

                    <label for="cuit" class="h3 text--black">CUIT</label>
                    <input type="text" name="cuit" id="cuit" />

                    <label for="nombreResponsable" class="h3 text--black">Nombre del responsable</label>
                    <input type="text" name="nombreResponsable" id="nombreResponsable" />

                    <label for="apellidoResponsable" class="h3 text--black">Apellido del responsable</label>
                    <input type="text" name="apellidoResponsable" id="apellidoResponsable" />

                    <label for="dniResponsable" class="h3 text--black">DNI del responsable</label>
                    <input type="text" name="dniResponsable" id="dniResponsable" required />

                    <label for="fechaNacimientoResponsable" class="h3 text--black">Fecha de nacimiento del responsable</label>
                    <input type="text" name="fechaNacimientoResponsable" id="fechaNacimientoResponsable" />
                </div>

                <button class="btn-primary" type="submit">Crear cuenta</button>
            </form>
        </div>
        <div class="background"></div>
    </div>
</section>

<script>
    const checkboxes = document.querySelectorAll(".checkbox");
    const usuarioForm = document.getElementById("usuarioForm");
    const bancoForm = document.getElementById("bancoForm");

    // Alternar entre formularios de usuario y banco
    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener("change", function () {
            if (this.value === "usuario") {
                usuarioForm.style.display = "block";
                bancoForm.style.display = "none";
            } else if (this.value === "banco") {
                usuarioForm.style.display = "none";
                bancoForm.style.display = "block";
            }
        });
    });
</script>
</body>
</html>
