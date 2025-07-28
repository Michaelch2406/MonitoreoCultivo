<?php
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
    
    // Verificar permisos básicos
    if ($usuario_actual['rol'] !== 'administrador' && $usuario_actual['rol'] !== 'agricultor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar siembras']);
        exit();
    }

    // Crear instancia del modelo
    $siembra_modelo = new Siembra();
    
    // Eliminar siembra
    $resultado = $siembra_modelo->eliminarSiembra($siembra_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en eliminar_siembra.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>