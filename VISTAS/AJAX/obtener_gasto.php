<?php
session_start();
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/finanzas_m.php');

// Configurar cabeceras para JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit();
}

// Verificar método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $usuario_actual = obtenerUsuarioActual();
    
    // Validar ID de gasto
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de gasto no válido'
        ]);
        exit();
    }
    
    $gasto_id = intval($_GET['id']);
    
    // Crear instancia del modelo
    $finanzas_modelo = new Finanzas();
    
    // Obtener el gasto
    $resultado = $finanzas_modelo->obtenerGasto($gasto_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'gasto' => $resultado['gasto'],
            'message' => 'Gasto obtenido exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al obtener gasto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>