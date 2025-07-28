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
    require_once('../../MODELOS/siembras_m.php');

    $usuario_actual = obtenerUsuarioActual();
    
    // Verificar permisos
    if ($usuario_actual['rol'] !== 'administrador' && $usuario_actual['rol'] !== 'agricultor') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para actualizar siembras']);
        exit();
    }

    // Validar datos requeridos
    if (!isset($_POST['siembra_id']) || empty($_POST['siembra_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de siembra requerido']);
        exit();
    }

    $campos_requeridos = ['fecha_siembra'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "El campo $campo es requerido"]);
            exit();
        }
    }

    // Validar fecha
    $fecha_siembra = $_POST['fecha_siembra'];
    if (!DateTime::createFromFormat('Y-m-d', $fecha_siembra)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit();
    }

    $siembra_id = intval($_POST['siembra_id']);

    // Preparar datos
    $datos_siembra = [
        'fecha_siembra' => $fecha_siembra,
        'fecha_estimada_cosecha' => isset($_POST['fecha_estimada_cosecha']) && !empty($_POST['fecha_estimada_cosecha']) ? $_POST['fecha_estimada_cosecha'] : null,
        'cantidad_semilla' => isset($_POST['cantidad_semilla']) && !empty($_POST['cantidad_semilla']) ? floatval($_POST['cantidad_semilla']) : null,
        'unidad_semilla' => isset($_POST['unidad_semilla']) && !empty($_POST['unidad_semilla']) ? $_POST['unidad_semilla'] : null,
        'densidad_siembra' => isset($_POST['densidad_siembra']) && !empty($_POST['densidad_siembra']) ? $_POST['densidad_siembra'] : null,
        'metodo_siembra' => isset($_POST['metodo_siembra']) && !empty($_POST['metodo_siembra']) ? $_POST['metodo_siembra'] : 'manual',
        'estado' => isset($_POST['estado']) && !empty($_POST['estado']) ? $_POST['estado'] : 'planificada',
        'observaciones' => isset($_POST['observaciones']) && !empty($_POST['observaciones']) ? $_POST['observaciones'] : null
    ];

    // Crear instancia del modelo
    $siembra_modelo = new Siembra();
    
    // Actualizar siembra
    $resultado = $siembra_modelo->actualizarSiembra($siembra_id, $datos_siembra, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en actualizar_siembra.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>