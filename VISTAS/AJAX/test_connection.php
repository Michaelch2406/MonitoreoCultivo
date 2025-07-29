<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

session_start();
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Conexión AJAX funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'session_active' => isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true,
        'method' => $_SERVER['REQUEST_METHOD'],
        'file_path' => __FILE__
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en test: ' . $e->getMessage()
    ]);
}
?>