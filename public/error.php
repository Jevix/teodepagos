<?php
$code = $_SERVER['REDIRECT_STATUS'] ?? ($_GET['code'] ?? 404);

$messages = [
  400 => "La solicitud no se pudo procesar",
  401 => "Necesitás iniciar sesión para acceder",
  403 => "No tenés permisos para acceder",
  404 => "No encontramos la página",
  405 => "Método no permitido",
  500 => "Error interno del servidor",
  503 => "Servicio no disponible",
];

$message = $messages[$code] ?? "Ocurrió un problema inesperado";

// Definir acción del botón según el error
if ($code == 404) {
    $buttonAction = "window.history.back()";
} else {
    $buttonAction = "window.location.href='logout.php'";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Error <?= htmlspecialchars($code) ?></title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: Inter, sans-serif;
      background: #fff;
    }
    .container-error {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 30px;
      max-width: 500px;
      padding: 20px;
      text-align: center;
    }
    img { width: 160px; height: 160px; }
    .h1 { color: #c82828; font-size: 40px; font-weight: 600; }
    .h2 { color: #c82828; font-size: 20px; font-weight: 600; }
    .h3 { color: #c82828; font-size: 96px; font-weight: 600; }
    .btn-volver {
      background-color: #2d7dd2;
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    .btn-volver:hover { background-color: #1e5fa3; }
    .text-error{
      display: flex;
flex-direction: column;
align-items: center;
align-self: stretch;
    }
  </style>
</head>
<body>
  <div class="container-error">
    <div class="content-error">
      <img src="img/logo404.png" alt="Logo error">
      <div class="text-error">
        <span class="h1">Error</span>
        <span class="h3"><?= htmlspecialchars($code) ?></span>
        <span class="h2"><?= htmlspecialchars($message) ?></span>
      </div>
    </div>
    <button class="btn-volver" onclick="<?= $buttonAction ?>">Volver</button>
  </div>
</body>
</html>
