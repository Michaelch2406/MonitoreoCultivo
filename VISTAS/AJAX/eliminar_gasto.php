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

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $usuario_actual = obtenerUsuarioActual();
    
    // Verificar permisos
    if (!in_array($usuario_actual['rol'], ['administrador', 'agricultor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para eliminar gastos'
        ]);
        exit();
    }
    
    // Validar ID de gasto
    if (!isset($_POST['gasto_id']) || !is_numeric($_POST['gasto_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de gasto no válido'
        ]);
        exit();
    }
    
    $gasto_id = intval($_POST['gasto_id']);
    
    // Crear instancia del modelo
    $finanzas_modelo = new Finanzas();
    
    // Eliminar el gasto
    $resultado = $finanzas_modelo->eliminarGasto($gasto_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Gasto eliminado exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar gasto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>