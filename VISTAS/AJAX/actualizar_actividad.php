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

try {
    require_once('../../CONFIG/roles.php');
    require_once('../../MODELOS/actividades_m.php');

    $usuario_actual = obtenerUsuarioActual();
    
    // Verificar permisos
    if ($usuario_actual['rol'] !== 'administrador' && $usuario_actual['rol'] !== 'agricultor' && $usuario_actual['rol'] !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para actualizar actividades']);
        exit();
    }

    // Validar datos requeridos
    if (!isset($_POST['actividad_id']) || empty($_POST['actividad_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de actividad requerido']);
        exit();
    }

    $campos_requeridos = ['tipo', 'fecha', 'descripcion'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "El campo $campo es requerido"]);
            exit();
        }
    }

    // Validar fecha
    $fecha = $_POST['fecha'];
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit();
    }

    // Validar tipo de actividad
    $tipos_validos = ['riego', 'fertilizacion', 'fumigacion', 'poda', 'deshierbe', 'aporque', 'otro'];
    if (!in_array($_POST['tipo'], $tipos_validos)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de actividad inválido']);
        exit();
    }

    $actividad_id = intval($_POST['actividad_id']);

    // Preparar datos
    $datos_actividad = [
        'tipo' => $_POST['tipo'],
        'fecha' => $fecha,
        'descripcion' => $_POST['descripcion'],
        'productos_utilizados' => isset($_POST['productos_utilizados']) && !empty($_POST['productos_utilizados']) ? $_POST['productos_utilizados'] : null,
        'cantidad_producto' => isset($_POST['cantidad_producto']) && !empty($_POST['cantidad_producto']) ? floatval($_POST['cantidad_producto']) : null,
        'unidad_producto' => isset($_POST['unidad_producto']) && !empty($_POST['unidad_producto']) ? $_POST['unidad_producto'] : null,
        'costo' => isset($_POST['costo']) && !empty($_POST['costo']) ? floatval($_POST['costo']) : null,
        'observaciones' => isset($_POST['observaciones']) && !empty($_POST['observaciones']) ? $_POST['observaciones'] : null
    ];

    // Crear instancia del modelo
    $actividad_modelo = new Actividad();
    
    // Actualizar actividad
    $resultado = $actividad_modelo->actualizarActividad($actividad_id, $datos_actividad, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en actualizar_actividad.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>