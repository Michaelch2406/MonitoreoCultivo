<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/fincas_m.php');

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_actual = obtenerUsuarioActual();
$finca_modelo = new Finca();

// Determinar la acción a realizar
$action = $_REQUEST['action'] ?? $_POST['action'] ?? 'listar';

try {
    switch ($action) {
        case 'crear':
            crearFinca();
            break;
            
        case 'listar':
            listarFincas();
            break;
            
        case 'obtener':
            obtenerFinca();
            break;
            
        case 'actualizar':
            actualizarFinca();
            break;
            
        case 'eliminar':
            eliminarFinca();
            break;
            
        case 'cambiar_estado':
            cambiarEstadoFinca();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error en fincas_c.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Crear nueva finca
 */
function crearFinca() {
    global $finca_modelo, $usuario_actual;
    
    // Verificar permisos
    if ($usuario_actual['rol'] != 'administrador' && $usuario_actual['rol'] != 'agricultor') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para crear fincas']);
        return;
    }
    
    // Validar datos requeridos
    $campos_requeridos = ['nombre', 'ubicacion', 'area_total'];
    
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "El campo $campo es requerido"]);
            return;
        }
    }
    
    // Determinar propietario
    if ($usuario_actual['rol'] == 'administrador') {
        $propietario_id = $_POST['propietario_id'] ?? null;
        if (empty($propietario_id)) {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar un propietario']);
            return;
        }
    } else {
        $propietario_id = $usuario_actual['id'];
    }
    
    // Validar área total
    $area_total = floatval($_POST['area_total']);
    if ($area_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'El área total debe ser mayor a 0']);
        return;
    }
    
    // Validar coordenadas si se proporcionan
    $latitud = !empty($_POST['latitud']) ? floatval($_POST['latitud']) : null;
    $longitud = !empty($_POST['longitud']) ? floatval($_POST['longitud']) : null;
    
    if ($latitud !== null && ($latitud < -90 || $latitud > 90)) {
        echo json_encode(['success' => false, 'message' => 'La latitud debe estar entre -90 y 90']);
        return;
    }
    
    if ($longitud !== null && ($longitud < -180 || $longitud > 180)) {
        echo json_encode(['success' => false, 'message' => 'La longitud debe estar entre -180 y 180']);
        return;
    }
    
    // Crear finca
    $resultado = $finca_modelo->crearFinca(
        $_POST['nombre'],
        $_POST['ubicacion'],
        $area_total,
        $propietario_id,
        $latitud,
        $longitud,
        $_POST['descripcion'] ?? null
    );
    
    echo json_encode($resultado);
}

/**
 * Listar fincas con filtros
 */
function listarFincas() {
    global $finca_modelo, $usuario_actual;
    
    // Preparar filtros
    $filtros = array();
    
    if (!empty($_GET['propietario'])) {
        $filtros['propietario'] = $_GET['propietario'];
    }
    
    if (!empty($_GET['estado'])) {
        $filtros['estado'] = $_GET['estado'];
    }
    
    if (!empty($_GET['area_min'])) {
        $filtros['area_min'] = $_GET['area_min'];
    }
    
    if (!empty($_GET['area_max'])) {
        $filtros['area_max'] = $_GET['area_max'];
    }
    
    if (!empty($_GET['ubicacion'])) {
        $filtros['ubicacion'] = $_GET['ubicacion'];
    }
    
    $resultado = $finca_modelo->listarFincas($usuario_actual['id'], $usuario_actual['rol'], $filtros);
    
    echo json_encode($resultado);
}

/**
 * Obtener una finca específica
 */
function obtenerFinca() {
    global $finca_modelo, $usuario_actual;
    
    $finca_id = $_GET['finca_id'] ?? null;
    
    if (empty($finca_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de finca requerido']);
        return;
    }
    
    $resultado = $finca_modelo->obtenerFinca($finca_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Actualizar finca
 */
function actualizarFinca() {
    global $finca_modelo, $usuario_actual;
    
    $finca_id = $_POST['finca_id'] ?? null;
    
    if (empty($finca_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de finca requerido']);
        return;
    }
    
    // Preparar datos para actualización
    $datos = array();
    
    $campos_editables = ['nombre', 'ubicacion', 'area_total', 
                        'descripcion', 'tipo_clima', 'acceso_agua', 'infraestructura', 'estado_legal'];
    
    foreach ($campos_editables as $campo) {
        if (isset($_POST[$campo])) {
            $datos[$campo] = $_POST[$campo];
        }
    }
    
    // Validar coordenadas si se proporcionan
    if (isset($_POST['latitud'])) {
        $latitud = floatval($_POST['latitud']);
        if ($latitud < -90 || $latitud > 90) {
            echo json_encode(['success' => false, 'message' => 'La latitud debe estar entre -90 y 90']);
            return;
        }
        $datos['latitud'] = $latitud;
    }
    
    if (isset($_POST['longitud'])) {
        $longitud = floatval($_POST['longitud']);
        if ($longitud < -180 || $longitud > 180) {
            echo json_encode(['success' => false, 'message' => 'La longitud debe estar entre -180 y 180']);
            return;
        }
        $datos['longitud'] = $longitud;
    }
    
    // Validar área total si se proporciona
    if (isset($_POST['area_total'])) {
        $area_total = floatval($_POST['area_total']);
        if ($area_total <= 0) {
            echo json_encode(['success' => false, 'message' => 'El área total debe ser mayor a 0']);
            return;
        }
        $datos['area_total'] = $area_total;
    }
    
    // Campos solo para administradores
    if ($usuario_actual['rol'] == 'administrador') {
        if (isset($_POST['propietario_id'])) {
            $datos['propietario_id'] = $_POST['propietario_id'];
        }
        
        if (isset($_POST['estado'])) {
            $datos['estado'] = $_POST['estado'];
        }
        
        if (isset($_POST['supervisor_id'])) {
            $datos['supervisor_id'] = $_POST['supervisor_id'];
        }
    }
    
    $resultado = $finca_modelo->actualizarFinca($finca_id, $datos, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Eliminar finca
 */
function eliminarFinca() {
    global $finca_modelo, $usuario_actual;
    
    $finca_id = $_POST['finca_id'] ?? null;
    
    if (empty($finca_id)) {
        echo json_encode(['success' => false, 'message' => 'ID de finca requerido']);
        return;
    }
    
    $resultado = $finca_modelo->eliminarFinca($finca_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}

/**
 * Cambiar estado de finca (solo administradores)
 */
function cambiarEstadoFinca() {
    global $finca_modelo, $usuario_actual;
    
    if ($usuario_actual['rol'] != 'administrador') {
        echo json_encode(['success' => false, 'message' => 'No tiene permisos para cambiar el estado']);
        return;
    }
    
    $finca_id = $_POST['finca_id'] ?? null;
    $nuevo_estado = $_POST['estado'] ?? null;
    
    if (empty($finca_id) || empty($nuevo_estado)) {
        echo json_encode(['success' => false, 'message' => 'ID de finca y estado requeridos']);
        return;
    }
    
    if (!in_array($nuevo_estado, ['activa', 'inactiva'])) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        return;
    }
    
    $datos = ['estado' => $nuevo_estado];
    $resultado = $finca_modelo->actualizarFinca($finca_id, $datos, $usuario_actual['id'], $usuario_actual['rol']);
    
    echo json_encode($resultado);
}
?>