<?php
session_start(); // Iniciar la sesión

// Verificar si la sesión está activa
if (isset($_SESSION['usuario'])) {
    // Destruir todas las variables de sesión
    session_unset();

    // Destruir la sesión actual
    session_destroy();
    
    // Redirigir al usuario a la página de login u otra página
    header('Location: login/');
    exit; // Terminar el script después de la redirección
} else {
    // Si no hay sesión activa, redirigir al login directamente
    header('Location: login/');
    exit;
}
?>
