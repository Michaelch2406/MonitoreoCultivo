<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/lotes_m.php');

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_actual = obtenerUsuarioActual();
$lote_modelo = new Lote();

// Determinar la acción a realizar
$action = $_REQUEST['action'] ?? $_POST['action'] ?? 'listar';

try {
    switch ($action) {
        case 'crear':
            crearLote();
            break;
            
        case 'listar':
            listarLotes();
            break;
            
        case 'obtener':
            obtenerLote();
            break;
            
        case 'actualizar':
            actualizarLote();
            break;
            
        case 'eliminar':
            eliminarLote();
            break;
            
        case 'obtener_por_finca':
            obtenerLotesPorFinca();
            break;
            
        case 'estadisticas':
            obtenerEstadisticasLotes();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en lotes_c.php: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Crear nuevo lote
 */
function crearLote() {
    global $lote_modelo, $usuario_actual;
    
    // Debug log
    error_log("Creando lote - Datos recibidos: " . print_r($_POST, true), 3, dirname(__FILE__) . "/../php_error.log");
    
    // Verificar permisos
    if ($usuario_actual['rol'] != 'administrador' && $usuario_actual['rol'] != 'agricultor') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear lotes']);
        return;
    }
    
    // Validar datos requeridos
    $campos_requeridos = ['nombre', 'finca_id', 'area'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "El campo $campo es requerido"]);
            return;
        }
    }
    
    // Validar área
    $area = floatval($_POST['area']);
    if ($area <= 0) {
        echo json_encode(['success' => false, 'message' => 'El área debe ser mayor a 0']);
        return;
    }
    
    // Validar pH del suelo si se proporciona
    $ph_suelo = null;
    if (!empty($_POST['ph_suelo'])) {
        $ph_suelo = floatval($_POST['ph_suelo']);
        if ($ph_suelo < 0 || $ph_suelo > 14) {
            echo json_encode(['success' => false, 'message' => 'El pH del suelo debe estar entre 0 y 14']);
            return;
        }
    }
    
    // Validar finca_id
    $finca_id = intval($_POST['finca_id']);
    if ($finca_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de finca no válido']);
        return;
    }
    
    // Crear lote
    $resultado = $lote_modelo->crearLote(
        $_POST['nombre'],
        $finca_id,
        $area,
        $_POST['tipo_suelo'] ?? null,
        $ph_suelo,
        $_POST['descripcion'] ?? null,
        $usuario_actual['id'],
        $usuario_actual['rol']
    );
    
    echo json_encode($resultado);
}

/**
 * Listar lotes con filtros
 */
function listarLotes() {
    global $lote_modelo, $usuario_actual;
    
    // Preparar filtros
    $filtros = array();
    
    if (!empty($_GET['finca_id'])) {
        $filtros['finca_id'] = $_GET['finca_id'];
    }
    
    if (!empty($_GET['estado'])) {
        $filtros['estado'] = $_GET['estado'];
    }
    
    if (!empty($_GET['tipo_suelo'])) {
        $filtros['tipo_suelo'] = $_GET['tipo_suelo'];
    }
    
    if (!empty($_GET['area_min'])) {
        $filtros['area_min'] = $_GET['area_min'];
    }
    
    if (!empty($_GET['area_max'])) {
        $filtros['area_max'] = $_GET['area_max'];
    }
    
    $resultado = $lote_modelo->listarLotes($usuario_actual['id'], $usuario_actual['rol'], $filtros);
    
    echo json_encode($resultado);
}

/**
 * Obtener un lote específico
 */
function obtenerLote() {
    global $lote_modelo, $usuario_actual;
    
    $lote_id = $_GET['lote_id'] ?? null;
    
    if (empty($lote_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de lote requerido']);
        return;
    }
    
    $resultado = $lote_modelo->obtenerLote($lote_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Actualizar lote
 */
function actualizarLote() {
    global $lote_modelo, $usuario_actual;
    
    $lote_id = $_POST['lote_id'] ?? null;
    
    if (empty($lote_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de lote requerido']);
        return;
    }
    
    // Preparar datos para actualización
    $datos = array();
    
    $campos_editables = ['nombre', 'area', 'tipo_suelo', 'ph_suelo', 'descripcion', 'estado'];
    
    // Debug log
    error_log("Datos recibidos para actualizar lote ID $lote_id: " . print_r($_POST, true), 3, dirname(__FILE__) . "/../php_error.log");
    
    foreach ($campos_editables as $campo) {
        if (isset($_POST[$campo])) {
            $datos[$campo] = $_POST[$campo];
        }
    }
    
    // Validar área si se proporciona
    if (isset($_POST['area'])) {
        $area = floatval($_POST['area']);
        if ($area <= 0) {
            echo json_encode(['success' => false, 'message' => 'El área debe ser mayor a 0']);
            return;
        }
        $datos['area'] = $area;
    }
    
    // Validar pH del suelo si se proporciona
    if (isset($_POST['ph_suelo']) && !empty($_POST['ph_suelo'])) {
        $ph_suelo = floatval($_POST['ph_suelo']);
        if ($ph_suelo < 0 || $ph_suelo > 14) {
            echo json_encode(['success' => false, 'message' => 'El pH del suelo debe estar entre 0 y 14']);
            return;
        }
        $datos['ph_suelo'] = $ph_suelo;
    }
    
    // Validar estado si se proporciona
    if (isset($_POST['estado'])) {
        $estados_validos = ['disponible', 'sembrado', 'cosechado', 'en_preparacion'];
        if (!in_array($_POST['estado'], $estados_validos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            return;
        }
        $datos['estado'] = $_POST['estado'];
    }
    
    $resultado = $lote_modelo->actualizarLote($lote_id, $datos, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Eliminar lote
 */
function eliminarLote() {
    global $lote_modelo, $usuario_actual;
    
    $lote_id = $_POST['lote_id'] ?? null;
    
    if (empty($lote_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de lote requerido']);
        return;
    }
    
    $resultado = $lote_modelo->eliminarLote($lote_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Obtener lotes por finca
 */
function obtenerLotesPorFinca() {
    global $lote_modelo, $usuario_actual;
    
    $finca_id = $_GET['finca_id'] ?? null;
    
    if (empty($finca_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de finca requerido']);
        return;
    }
    
    $resultado = $lote_modelo->obtenerLotesPorFinca($finca_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Obtener estadísticas de lotes
 */
function obtenerEstadisticasLotes() {
    global $lote_modelo, $usuario_actual;
    
    $resultado = $lote_modelo->obtenerEstadisticasLotes($usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}
?>