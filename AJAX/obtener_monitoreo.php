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

    // Verificar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];
    $rol = $usuario_actual['rol'];

    // Validar ID del monitoreo
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode(array(
            'success' => false,
            'message' => 'ID de monitoreo no válido'
        ));
        exit;
    }

    $monitoreo_id = intval($_GET['id']);

    // Crear instancia del modelo
    $monitoreo_modelo = new Monitoreo();

    // Obtener monitoreo
    $resultado = $monitoreo_modelo->obtenerMonitoreo($monitoreo_id, $usuario_id, $rol);

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en obtener_monitoreo.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>