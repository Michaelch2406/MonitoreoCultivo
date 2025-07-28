<?php
session_start();
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/cosechas_m.php');

// Configurar cabeceras para JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

try {
    $usuario_actual = obtenerUsuarioActual();
    
    // Verificar permisos - Solo administradores y agricultores pueden eliminar
    if (!in_array($usuario_actual['rol'], ['administrador', 'agricultor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para eliminar cosechas'
        ]);
        exit();
    }
    
    // Validar ID de cosecha
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cosecha no válido'
        ]);
        exit();
    }
    
    $cosecha_id = intval($_POST['id']);
    
    // Crear instancia del modelo
    $cosecha_modelo = new Cosecha();
    
    // Primero verificar que la cosecha existe y el usuario tiene permisos
    $cosecha_existente = $cosecha_modelo->obtenerCosecha($cosecha_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    if (!$cosecha_existente['success']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cosecha no encontrada o sin permisos para eliminar'
        ]);
        exit();
    }
    
    // Verificar si es seguro eliminar (por ejemplo, si hay transacciones relacionadas)
    // Esta validación puede expandirse según las reglas del negocio
    $cosecha_data = $cosecha_existente['cosecha'];
    
    // Si la cosecha tiene ingresos registrados, podríamos requerir confirmación adicional
    if ($cosecha_data['cos_total_ingresos'] > 0) {
        // Por ahora permitimos la eliminación, pero se podría agregar lógica adicional
        // como requerir una justificación o permisos especiales
    }
    
    // Eliminar la cosecha
    $resultado = $cosecha_modelo->eliminarCosecha($cosecha_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        // Log de auditoría (opcional)
        error_log("Cosecha eliminada - ID: {$cosecha_id}, Usuario: {$usuario_actual['id']} ({$usuario_actual['rol']})");
        
        echo json_encode([
            'success' => true,
            'message' => 'Cosecha eliminada exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al eliminar cosecha: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>