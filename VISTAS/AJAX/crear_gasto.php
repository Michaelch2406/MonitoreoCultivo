<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

session_start();
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/finanzas_m.php');

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
    
    // Verificar permisos
    if (!in_array($usuario_actual['rol'], ['administrador', 'agricultor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para registrar gastos'
        ]);
        exit();
    }
    
    // Validar datos requeridos
    $campos_requeridos = ['fecha', 'tipo', 'descripcion', 'monto'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
            echo json_encode([
                'success' => false,
                'message' => "El campo {$campo} es requerido"
            ]);
            exit();
        }
    }
    
    // Validar formato de fecha
    $fecha = $_POST['fecha'];
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de fecha inválido'
        ]);
        exit();
    }
    
    // Validar que la fecha no sea futura
    if (strtotime($fecha) > strtotime('today')) {
        echo json_encode([
            'success' => false,
            'message' => 'La fecha del gasto no puede ser futura'
        ]);
        exit();
    }
    
    // Validar monto
    $monto = floatval($_POST['monto']);
    if ($monto <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'El monto debe ser mayor a 0'
        ]);
        exit();
    }
    
    // Validar tipo de gasto
    $tipos_validos = ['semillas', 'fertilizantes', 'pesticidas', 'mano_obra', 'maquinaria', 'otros'];
    if (!in_array($_POST['tipo'], $tipos_validos)) {
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de gasto no válido'
        ]);
        exit();
    }
    
    // Preparar datos para inserción
    $datos_gasto = [
        'fecha' => $fecha,
        'tipo' => $_POST['tipo'],
        'descripcion' => trim($_POST['descripcion']),
        'monto' => $monto,
        'finca_id' => !empty($_POST['finca_id']) ? intval($_POST['finca_id']) : null,
        'siembra_id' => !empty($_POST['siembra_id']) ? intval($_POST['siembra_id']) : null,
        'proveedor' => !empty($_POST['proveedor']) ? trim($_POST['proveedor']) : null,
        'factura_numero' => !empty($_POST['factura_numero']) ? trim($_POST['factura_numero']) : null,
        'observaciones' => !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null
    ];
    
    // Validar descripción no vacía después de trim
    if (empty($datos_gasto['descripcion'])) {
        echo json_encode([
            'success' => false,
            'message' => 'La descripción no puede estar vacía'
        ]);
        exit();
    }
    
    // Crear instancia del modelo
    $finanzas_modelo = new Finanzas();
    
    // Crear el gasto
    $resultado = $finanzas_modelo->crearGasto($datos_gasto, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Gasto registrado exitosamente',
            'gasto_id' => $resultado['gasto_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al crear gasto: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>