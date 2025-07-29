<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Verificar que se envió el ID
if (!isset($_POST['actividad_id']) || empty($_POST['actividad_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de actividad requerido']);
    exit();
}

try {
    require_once('../../CONFIG/roles.php');
    require_once('../../MODELOS/actividades_m.php');

    $usuario_actual = obtenerUsuarioActual();
    $actividad_id = intval($_POST['actividad_id']);
    
    // Verificar permisos básicos
    if ($usuario_actual['rol'] !== 'administrador' && $usuario_actual['rol'] !== 'agricultor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar actividades']);
        exit();
    }

    // Crear instancia del modelo
    $actividad_modelo = new Actividad();
    
    // Eliminar actividad
    $resultado = $actividad_modelo->eliminarActividad($actividad_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en eliminar_actividad.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>