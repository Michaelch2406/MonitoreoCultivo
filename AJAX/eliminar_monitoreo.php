<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('../CONFIG/roles.php');
require_once('../MODELOS/monitoreo_m.php');

try {
    // Verificar que el usuario esté logueado
    if (!estaLogueado()) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Acceso denegado'
        ));
        exit;
    }

    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];
    $rol = $usuario_actual['rol'];

    // Validar ID del monitoreo
    if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
        echo json_encode(array(
            'success' => false,
            'message' => 'ID de monitoreo no válido'
        ));
        exit;
    }

    $monitoreo_id = intval($_POST['id']);

    // Crear instancia del modelo
    $monitoreo_modelo = new Monitoreo();

    // Eliminar monitoreo
    $resultado = $monitoreo_modelo->eliminarMonitoreo($monitoreo_id, $usuario_id, $rol);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Monitoreo eliminado por usuario: " . $usuario_actual['email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en eliminar_monitoreo.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>