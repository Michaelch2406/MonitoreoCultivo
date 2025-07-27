<?php
require_once '../CONFIG/auth.php';
require_once '../CONFIG/global.php';
require_once '../MODELOS/cultivos_m.php';

// Verificar que la sesión esté iniciada
verificarSesion();

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Instanciar modelo
$cultivoModel = new Cultivo();

// Obtener acción solicitada
$action = $_REQUEST['action'] ?? '';

// Obtener permisos del usuario
$permisos = obtenerPermisosUsuario($_SESSION['rol']);

try {
    switch ($action) {
        case 'listar':
            listarCultivos();
            break;
            
        case 'detalle':
            obtenerDetalleCultivo();
            break;
            
        case 'estadisticas':
            obtenerEstadisticas();
            break;
            
        case 'buscar':
            buscarCultivos();
            break;
            
        case 'categoria':
            obtenerPorCategoria();
            break;
            
        case 'crear':
            crearCultivo();
            break;
            
        case 'editar':
            editarCultivo();
            break;
            
        case 'eliminar':
            eliminarCultivo();
            break;
            
        case 'cambiar_estado':
            cambiarEstadoCultivo();
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    logAjaxError($action, $e->getMessage(), $_SESSION['user_id'] ?? null);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

/**
 * Listar todos los cultivos
 */
function listarCultivos() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para ver cultivos'
        ]);
        return;
    }
    
    $categoria = $_GET['categoria'] ?? null;
    $resultado = $cultivoModel->obtenerTodosTiposCultivos($categoria);
    
    if ($resultado['success']) {
        logOperation('Listar cultivos', $_SESSION['user_id'], "Categoría: " . ($categoria ?: 'todas'));
    }
    
    echo json_encode($resultado);
}

/**
 * Obtener detalle de un cultivo específico
 */
function obtenerDetalleCultivo() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para ver detalles de cultivos'
        ]);
        return;
    }
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cultivo no válido'
        ]);
        return;
    }
    
    $resultado = $cultivoModel->obtenerTipoCultivoPorId($id);
    
    if ($resultado['success']) {
        logOperation('Ver detalle cultivo', $_SESSION['user_id'], "ID: $id");
    }
    
    echo json_encode($resultado);
}

/**
 * Obtener estadísticas de cultivos por categoría
 */
function obtenerEstadisticas() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para ver estadísticas'
        ]);
        return;
    }
    
    $resultado = $cultivoModel->obtenerCultivosPorCategoria();
    
    if ($resultado['success']) {
        $estadisticas = [
            'cereales' => 0,
            'hortalizas' => 0,
            'leguminosas' => 0,
            'frutales' => 0,
            'tuberculos' => 0,
            'aromaticas' => 0,
            'total' => 0
        ];
        
        foreach ($resultado['categorias'] as $categoria) {
            $estadisticas[$categoria['tip_categoria']] = intval($categoria['total']);
            $estadisticas['total'] += intval($categoria['total']);
        }
        
        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
        
        logOperation('Obtener estadísticas cultivos', $_SESSION['user_id']);
    } else {
        echo json_encode($resultado);
    }
}

/**
 * Buscar cultivos por término
 */
function buscarCultivos() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para buscar cultivos'
        ]);
        return;
    }
    
    $termino = trim($_GET['q'] ?? '');
    $categoria = $_GET['categoria'] ?? null;
    
    if (strlen($termino) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'El término de búsqueda debe tener al menos 2 caracteres'
        ]);
        return;
    }
    
    $resultado = $cultivoModel->buscarCultivos($termino, $categoria);
    
    if ($resultado['success']) {
        logOperation('Buscar cultivos', $_SESSION['user_id'], "Término: $termino, Categoría: " . ($categoria ?: 'todas'));
    }
    
    echo json_encode($resultado);
}

/**
 * Obtener cultivos por categoría
 */
function obtenerPorCategoria() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para ver cultivos'
        ]);
        return;
    }
    
    $categoria = $_GET['categoria'] ?? '';
    
    if (empty($categoria)) {
        echo json_encode([
            'success' => false,
            'message' => 'Categoría no especificada'
        ]);
        return;
    }
    
    $resultado = $cultivoModel->obtenerTodosTiposCultivos($categoria);
    
    if ($resultado['success']) {
        logOperation('Obtener cultivos por categoría', $_SESSION['user_id'], "Categoría: $categoria");
    }
    
    echo json_encode($resultado);
}

/**
 * Crear nuevo cultivo
 */
function crearCultivo() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['crear']) || !$permisos['cultivos']['crear']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para crear cultivos'
        ]);
        return;
    }
    
    // Validar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    // Recopilar datos del formulario
    $datos = [
        'tip_nombre' => trim($_POST['tip_nombre'] ?? ''),
        'tip_nombre_cientifico' => trim($_POST['tip_nombre_cientifico'] ?? ''),
        'tip_familia_botanica' => trim($_POST['tip_familia_botanica'] ?? ''),
        'tip_ciclo_vida' => $_POST['tip_ciclo_vida'] ?? 'anual',
        'tip_ciclo_dias' => $_POST['tip_ciclo_dias'] ?? null,
        'tip_categoria' => $_POST['tip_categoria'] ?? 'hortalizas',
        'tip_descripcion' => trim($_POST['tip_descripcion'] ?? ''),
        'tip_temperatura_min' => $_POST['tip_temperatura_min'] ?? null,
        'tip_temperatura_max' => $_POST['tip_temperatura_max'] ?? null,
        'tip_precipitacion' => trim($_POST['tip_precipitacion'] ?? ''),
        'tip_tipo_suelo' => trim($_POST['tip_tipo_suelo'] ?? ''),
        'tip_ph_min' => $_POST['tip_ph_min'] ?? null,
        'tip_ph_max' => $_POST['tip_ph_max'] ?? null,
        'tip_densidad_siembra' => trim($_POST['tip_densidad_siembra'] ?? ''),
        'tip_profundidad_siembra' => trim($_POST['tip_profundidad_siembra'] ?? ''),
        'tip_requerimientos_agua' => trim($_POST['tip_requerimientos_agua'] ?? ''),
        'tip_requerimientos_suelo' => trim($_POST['tip_requerimientos_suelo'] ?? ''),
        'tip_temperatura_optima' => trim($_POST['tip_temperatura_optima'] ?? '')
    ];
    
    // Limpiar campos vacíos
    foreach ($datos as $key => $value) {
        if (is_string($value) && $value === '') {
            $datos[$key] = null;
        }
    }
    
    // Validar datos
    $errores = $cultivoModel->validarDatosCultivo($datos);
    if (!empty($errores)) {
        echo json_encode([
            'success' => false,
            'message' => 'Errores de validación',
            'errores' => $errores
        ]);
        return;
    }
    
    $resultado = $cultivoModel->crearTipoCultivo($datos);
    
    if ($resultado['success']) {
        logOperation('Crear cultivo', $_SESSION['user_id'], "Nombre: {$datos['tip_nombre']}, ID: {$resultado['cultivo_id']}");
    } else {
        logAjaxError('crear_cultivo', $resultado['message'], $_SESSION['user_id']);
    }
    
    echo json_encode($resultado);
}

/**
 * Editar cultivo existente
 */
function editarCultivo() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['editar']) || !$permisos['cultivos']['editar']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para editar cultivos'
        ]);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cultivo no válido'
        ]);
        return;
    }
    
    // Recopilar datos del formulario (misma estructura que crear)
    $datos = [
        'tip_nombre' => trim($_POST['tip_nombre'] ?? ''),
        'tip_nombre_cientifico' => trim($_POST['tip_nombre_cientifico'] ?? ''),
        'tip_familia_botanica' => trim($_POST['tip_familia_botanica'] ?? ''),
        'tip_ciclo_vida' => $_POST['tip_ciclo_vida'] ?? 'anual',
        'tip_ciclo_dias' => $_POST['tip_ciclo_dias'] ?? null,
        'tip_categoria' => $_POST['tip_categoria'] ?? 'hortalizas',
        'tip_descripcion' => trim($_POST['tip_descripcion'] ?? ''),
        'tip_temperatura_min' => $_POST['tip_temperatura_min'] ?? null,
        'tip_temperatura_max' => $_POST['tip_temperatura_max'] ?? null,
        'tip_precipitacion' => trim($_POST['tip_precipitacion'] ?? ''),
        'tip_tipo_suelo' => trim($_POST['tip_tipo_suelo'] ?? ''),
        'tip_ph_min' => $_POST['tip_ph_min'] ?? null,
        'tip_ph_max' => $_POST['tip_ph_max'] ?? null,
        'tip_densidad_siembra' => trim($_POST['tip_densidad_siembra'] ?? ''),
        'tip_profundidad_siembra' => trim($_POST['tip_profundidad_siembra'] ?? ''),
        'tip_requerimientos_agua' => trim($_POST['tip_requerimientos_agua'] ?? ''),
        'tip_requerimientos_suelo' => trim($_POST['tip_requerimientos_suelo'] ?? ''),
        'tip_temperatura_optima' => trim($_POST['tip_temperatura_optima'] ?? '')
    ];
    
    // Limpiar campos vacíos
    foreach ($datos as $key => $value) {
        if (is_string($value) && $value === '') {
            $datos[$key] = null;
        }
    }
    
    // Validar datos
    $errores = $cultivoModel->validarDatosCultivo($datos);
    if (!empty($errores)) {
        echo json_encode([
            'success' => false,
            'message' => 'Errores de validación',
            'errores' => $errores
        ]);
        return;
    }
    
    $resultado = $cultivoModel->actualizarTipoCultivo($id, $datos);
    
    if ($resultado['success']) {
        logOperation('Editar cultivo', $_SESSION['user_id'], "ID: $id, Nombre: {$datos['tip_nombre']}");
    } else {
        logAjaxError('editar_cultivo', $resultado['message'], $_SESSION['user_id']);
    }
    
    echo json_encode($resultado);
}

/**
 * Eliminar cultivo
 */
function eliminarCultivo() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['eliminar']) || !$permisos['cultivos']['eliminar']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para eliminar cultivos'
        ]);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cultivo no válido'
        ]);
        return;
    }
    
    // Obtener información del cultivo antes de eliminarlo para el log
    $cultivo_info = $cultivoModel->obtenerTipoCultivoPorId($id);
    $nombre_cultivo = $cultivo_info['success'] ? $cultivo_info['cultivo']['tip_nombre'] : "ID: $id";
    
    $resultado = $cultivoModel->eliminarTipoCultivo($id);
    
    if ($resultado['success']) {
        logOperation('Eliminar cultivo', $_SESSION['user_id'], "Cultivo eliminado: $nombre_cultivo");
    } else {
        logAjaxError('eliminar_cultivo', $resultado['message'], $_SESSION['user_id']);
    }
    
    echo json_encode($resultado);
}

/**
 * Cambiar estado de cultivo (activo/inactivo)
 */
function cambiarEstadoCultivo() {
    global $cultivoModel, $permisos;
    
    if (!isset($permisos['cultivos']['editar']) || !$permisos['cultivos']['editar']) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para cambiar el estado de cultivos'
        ]);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        return;
    }
    
    $id = intval($_POST['id'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    
    if ($id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cultivo no válido'
        ]);
        return;
    }
    
    if (!in_array($estado, ['activo', 'inactivo'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Estado no válido'
        ]);
        return;
    }
    
    $resultado = $cultivoModel->cambiarEstadoTipoCultivo($id, $estado);
    
    if ($resultado['success']) {
        logOperation('Cambiar estado cultivo', $_SESSION['user_id'], "ID: $id, Nuevo estado: $estado");
    } else {
        logAjaxError('cambiar_estado_cultivo', $resultado['message'], $_SESSION['user_id']);
    }
    
    echo json_encode($resultado);
}
?>