<?php
session_start();
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/cosechas_m.php');

// Configurar cabeceras para JSON
header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Archivo de prueba funciona correctamente',
        'data' => [
            'session_active' => isset($_SESSION['logged_in']),
            'user_id' => $_SESSION['user_id'] ?? null,
            'get_params' => $_GET
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>