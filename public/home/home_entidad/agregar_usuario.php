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

$mensaje = '';
$tipo_cuenta = '';
$nombre = '';
$apellido = '';
$dni = '';
$fechaNacimiento = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el tipo de cuenta está definido en el POST
    if (!isset($_POST['tipo'])) {
        $mensaje = "Error: No se ha seleccionado un tipo de cuenta.";
    } else {
        // Obtener el tipo de cuenta a crear (usuario o banco)
        $tipo = $_POST['tipo'];

        try {
            // Iniciar la transacción
            $pdo->beginTransaction();

            // Si es un usuario común
            if ($tipo === 'usuario') {
                $nombreEntidad = 'N/A';
                $nombre = htmlspecialchars($_POST['nombre']);
                $apellido = htmlspecialchars($_POST['apellido']);
                $dni = htmlspecialchars($_POST['dni']);
                $fechaNacimientoResponsable = htmlspecialchars($_POST['fechaNacimiento']);
                $nombre_apellido = $nombre . ' ' . $apellido;

                // Validar los campos para asegurarse de que no están vacíos
                if (empty($nombre) || empty($apellido) || empty($dni) || empty($fechaNacimientoResponsable)) {
                    throw new Exception("Por favor, completa todos los campos.");
                }

                // Insertar al usuario con saldo 0 y tipo_usuario 'Usuario'
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_apellido, dni, password, tipo_usuario, saldo) VALUES (?, ?, ?, 'Usuario', 0)");
                $stmt->execute([$nombre_apellido, $dni, $fechaNacimientoResponsable]);

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

                // Validar campos para banco
                if (empty($nombreEntidad) || empty($cuit) || empty($nombreResponsable) || empty($apellidoResponsable) || empty($dniResponsable) || empty($fechaNacimientoResponsable)) {
                    throw new Exception("Por favor, completa todos los campos de banco.");
                }

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
                $tipo_cuenta = 'Banco';
            } else {
                throw new Exception("Tipo de cuenta no válido.");
            }

            // Confirmar la transacción
            $pdo->commit();

            // Redirigir a creado.php usando POST
            echo '
            <form id="creadoForm" action="creado.php" method="POST">
                <input type="hidden" name="tipo_cuenta" value="' . $tipo_cuenta . '">
                <input type="hidden" name="nombre_entidad" value="' . $nombreEntidad . '">
                <input type="hidden" name="nombre" value="' . $nombre . '">
                <input type="hidden" name="apellido" value="' . $apellido . '">
                <input type="hidden" name="dni" value="' . $dni . '">
                <input type="hidden" name="fechaNacimiento" value="' . $fechaNacimientoResponsable . '">
            </form>
            <script>
                document.getElementById("creadoForm").submit();
            </script>
            ';
            exit;

        } catch (Exception $e) {
            // Si ocurre un error, hacer rollback y mostrar el mensaje de error
            $pdo->rollBack();
            $mensaje = "Error: " . $e->getMessage();
        }
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
        .error {
            color: red;
            font-size: 1.2em;
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
                    <p class="error"><?= $mensaje; ?></p>
                <?php endif; ?>
                <form action="agregar_usuario.php" method="POST" id="myForm">
                    <div class="checkboxes">
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <label class="h2 text--darkblue" for="checkbox1" style="display: flex;align-items: center;gap: 10px;width: 80%;">
                                <img src="../../img/user.svg" alt="Usuario" style="display: block;">
                                <span style="flex: 1;text-align: center;">Usuario</span>
                            </label>
                            <input
                                type="radio"
                                name="tipo"
                                id="checkbox1"
                                class="checkbox"
                                value="usuario"
                                checked
                                style="margin-left: auto;"
                            />
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <label class="h2 text--darkblue" for="checkbox2" style="display: flex;align-items: center;gap: 10px;width: 80%;">
                                <img src="../../img/user.svg" alt="Banco" style="display: block;">
                                <span style="flex: 1;text-align: center;">Banco</span>
                            </label>
                            <input
                                type="radio"
                                name="tipo"
                                id="checkbox2"
                                class="checkbox"
                                value="banco"
                                style="margin-left: auto;"
                            />
                        </div>
                    </div>
                    <!-- Campos para USUARIO -->
                    <div id="usuarioForm" style="display: flex; flex-direction: column; gap: 25px !important;">
                        <div>
                            <label for="nombre" class="h3 text--black">Nombre</label>
                            <input type="text" name="nombre" id="nombre"  />
                        </div>
                        <div>
                            <label for="apellido" class="h3 text--black">Apellido</label>
                            <input type="text" name="apellido" id="apellido"  />
                        </div>
                        <div>
                            <label for="dni" class="h3 text--black">DNI</label>
                            <input type="text" name="dni" id="dni"  />
                        </div>
                        <div>
                            <label for="fechaNacimiento" class="h3 text--black">Fecha de nacimiento</label>
                            <input type="text" name="fechaNacimiento" id="fechaNacimiento"  />
                        </div>
                    </div>
                    <!-- Campos para BANCO -->
                    <div id="bancoForm" style="display: none; flex-direction: column; gap: 25px !important;">
                        <div>
                            <label for="nombreEntidad" class="h3 text--black">Nombre del banco</label>
                            <input type="text" name="nombreEntidad" id="nombreEntidad" />
                        </div>
                        <div>
                            <label for="cuit" class="h3 text--black">CUIT</label>
                            <input type="text" name="cuit" id="cuit" />
                        </div>
                        <div>
                            <label for="nombreResponsable" class="h3 text--black">Nombre del responsable</label>
                            <input type="text" name="nombreResponsable" id="nombreResponsable" />
                        </div>
                        <div>
                            <label for="apellidoResponsable" class="h3 text--black">Apellido del responsable</label>
                            <input type="text" name="apellidoResponsable" id="apellidoResponsable" />
                        </div>
                        <div>
                            <label for="dniResponsable" class="h3 text--black">DNI del responsable</label>
                            <input type="text" name="dniResponsable" id="dniResponsable"/>
                        </div>
                        <div>
                            <label for="fechaNacimientoResponsable" class="h3 text--black">Fecha de nacimiento del responsable</label>
                            <input type="text" name="fechaNacimientoResponsable" id="fechaNacimientoResponsable" />
                        </div>
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
                    usuarioForm.style.display = "flex";
                    bancoForm.style.display = "none";
                } else if (this.value === "banco") {
                    usuarioForm.style.display = "none";
                    bancoForm.style.display = "flex";
                }
            });
        });
    </script>
</body>
</html>
