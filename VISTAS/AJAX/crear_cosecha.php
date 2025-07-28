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
    
    // Verificar permisos
    if (!in_array($usuario_actual['rol'], ['administrador', 'agricultor'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes permisos para registrar cosechas'
        ]);
        exit();
    }
    
    // Validar datos requeridos
    $campos_requeridos = ['siembra_id', 'fecha_cosecha', 'cantidad_cosechada', 'unidad', 'calidad'];
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
    $fecha_cosecha = $_POST['fecha_cosecha'];
    if (!DateTime::createFromFormat('Y-m-d', $fecha_cosecha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Formato de fecha inválido'
        ]);
        exit();
    }
    
    // Validar que la fecha de cosecha no sea futura
    if (strtotime($fecha_cosecha) > strtotime('today')) {
        echo json_encode([
            'success' => false,
            'message' => 'La fecha de cosecha no puede ser futura'
        ]);
        exit();
    }
    
    // Validar cantidad
    $cantidad = floatval($_POST['cantidad_cosechada']);
    if ($cantidad <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La cantidad cosechada debe ser mayor a 0'
        ]);
        exit();
    }
    
    // Validar calidad
    $calidades_validas = ['primera', 'segunda', 'tercera', 'descarte'];
    if (!in_array($_POST['calidad'], $calidades_validas)) {
        echo json_encode([
            'success' => false,
            'message' => 'Calidad del producto no válida'
        ]);
        exit();
    }
    
    // Preparar datos para inserción
    $datos_cosecha = [
        'siembra_id' => intval($_POST['siembra_id']),
        'fecha_cosecha' => $fecha_cosecha,
        'cantidad_cosechada' => $cantidad,
        'unidad' => trim($_POST['unidad']),
        'calidad' => $_POST['calidad'],
        'precio_venta_unitario' => !empty($_POST['precio_unitario']) ? floatval($_POST['precio_unitario']) : null,
        'comprador' => !empty($_POST['comprador']) ? trim($_POST['comprador']) : null,
        'total_ingresos' => !empty($_POST['total_ingresos']) ? floatval($_POST['total_ingresos']) : null,
        'observaciones' => !empty($_POST['observaciones']) ? trim($_POST['observaciones']) : null
    ];
    
    // Validar coherencia de datos comerciales (opcional)
    if (!empty($datos_cosecha['precio_venta_unitario']) && empty($datos_cosecha['comprador'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Si especifica precio de venta, debe indicar el comprador'
        ]);
        exit();
    }
    
    // Auto-calcular ingresos si no se proporcionan pero sí hay precio y cantidad
    if (!empty($datos_cosecha['precio_venta_unitario']) && empty($datos_cosecha['total_ingresos'])) {
        $datos_cosecha['total_ingresos'] = $cantidad * $datos_cosecha['precio_venta_unitario'];
    }
    
    // Validar cálculo de ingresos si ambos están presentes
    if (!empty($datos_cosecha['precio_venta_unitario']) && !empty($datos_cosecha['total_ingresos'])) {
        $total_calculado = $cantidad * $datos_cosecha['precio_venta_unitario'];
        $diferencia = abs($total_calculado - $datos_cosecha['total_ingresos']);
        
        if ($diferencia > 0.01) { // Tolerancia de 1 centavo
            echo json_encode([
                'success' => false,
                'message' => 'El total de ingresos no coincide con el cálculo (cantidad × precio unitario)'
            ]);
            exit();
        }
    }
    
    // Crear instancia del modelo
    $cosecha_modelo = new Cosecha();
    
    // Crear la cosecha
    $resultado = $cosecha_modelo->crearCosecha($datos_cosecha, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Cosecha registrada exitosamente',
            'cosecha_id' => $resultado['cosecha_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al crear cosecha: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>