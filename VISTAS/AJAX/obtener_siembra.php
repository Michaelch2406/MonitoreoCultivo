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
if (!isset($_POST['siembra_id']) || empty($_POST['siembra_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de siembra requerido']);
    exit();
}

try {
    require_once('../../CONFIG/roles.php');
    require_once('../../MODELOS/siembras_m.php');

    $usuario_actual = obtenerUsuarioActual();
    $siembra_id = intval($_POST['siembra_id']);

    // Crear instancia del modelo
    $siembra_modelo = new Siembra();
    
    // Obtener siembra
    $resultado = $siembra_modelo->obtenerSiembra($siembra_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en obtener_siembra.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>