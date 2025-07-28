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

    // Verificar permisos
    if (!in_array($usuario_actual['rol'], ['administrador', 'agricultor', 'supervisor'])) {
        echo json_encode(array(
            'success' => false,
            'message' => 'No tienes permisos para crear monitoreos'
        ));
        exit;
    }

    // Validar datos requeridos
    if (empty($_POST['siembra_id']) || empty($_POST['fecha_observacion']) || empty($_POST['estado_general'])) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Los campos siembra, fecha de observación y estado general son requeridos'
        ));
        exit;
    }

    // Crear instancia del modelo
    $monitoreo_modelo = new Monitoreo();

    // Preparar datos
    $datos = array(
        'siembra_id' => $_POST['siembra_id'],
        'fecha_observacion' => $_POST['fecha_observacion'],
        'altura_promedio' => $_POST['altura_promedio'] ?? null,
        'estado_general' => $_POST['estado_general'],
        'porcentaje_germinacion' => $_POST['porcentaje_germinacion'] ?? null,
        'color_follaje' => $_POST['color_follaje'] ?? null,
        'presencia_plagas' => $_POST['presencia_plagas'] ?? 'ninguna',
        'tipo_plagas' => $_POST['tipo_plagas'] ?? null,
        'presencia_enfermedades' => $_POST['presencia_enfermedades'] ?? 'ninguna',
        'tipo_enfermedades' => $_POST['tipo_enfermedades'] ?? null,
        'condicion_clima' => $_POST['condicion_clima'] ?? null,
        'humedad_suelo' => $_POST['humedad_suelo'] ?? 'humedo',
        'observaciones' => $_POST['observaciones'] ?? null
    );

    // Crear monitoreo
    $resultado = $monitoreo_modelo->crearMonitoreo($datos, $usuario_id);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Monitoreo creado por usuario: " . $usuario_actual['email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en crear_monitoreo.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>