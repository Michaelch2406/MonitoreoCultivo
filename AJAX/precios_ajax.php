<?php
session_start();
require_once '../CONFIG/global.php';
require_once '../MODELOS/precios_m.php';

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Instanciar modelo
$preciosModel = new Precios();

// Obtener acción solicitada
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'listar':
            listarPlanes();
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

/**
 * Listar todos los planes de precios
 */
function listarPlanes() {
    global $preciosModel;
    
    $resultado = $preciosModel->obtenerTodosPlanes();
    
    echo json_encode($resultado);
}
?>
