<?php
session_start(); // Iniciar la sesión

// Verificar si alguna sesión está activa
if (session_status() == PHP_SESSION_ACTIVE) {
    // Destruir todas las variables de sesión
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, también eliminar la cookie de sesión.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        // Eliminar la cookie de la sesión
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir la sesión actual
    session_destroy();
}

// Eliminar todas las cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        // Eliminar la cookie configurándola con una fecha de expiración pasada
        setcookie($name, '', time() - 42000, '/');
        setcookie($name, '', time() - 42000, '/', $_SERVER['HTTP_HOST']);
    }
}

// Redirigir al usuario a la página de login u otra página
header('Location: login/');
exit; // Terminar el script después de la redirección
?>