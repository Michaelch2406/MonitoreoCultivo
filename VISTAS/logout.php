<?php
// Evitar cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start(); // Acceder a la sesión existente

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Limpiar cookies de Remember Me si existen
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 42000, '/', '', false, true);
}

// Finalmente, destruir la sesión.
session_destroy();

// Log del logout
error_log("Logout exitoso - IP: " . $_SERVER['REMOTE_ADDR'] . " - Fecha: " . date('Y-m-d H:i:s'));

// Redirigir a la página de inicio de sesión con parámetro para evitar cache
header("Location: login.php?logout=1");
exit;
?>