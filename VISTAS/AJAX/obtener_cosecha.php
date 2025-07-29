<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

session_start();

// Configurar cabeceras para JSON
header('Content-Type: application/json');

try {
    require_once('../../CONFIG/roles.php');
    require_once('../../MODELOS/cosechas_m.php');

    // Verificar sesión
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión no válida'
        ]);
        exit();
    }

    // Verificar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit();
    }

    $usuario_actual = obtenerUsuarioActual();
    
    if (!$usuario_actual) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener información del usuario'
        ]);
        exit();
    }
    
    // Validar parámetro ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de cosecha no válido'
        ]);
        exit();
    }
    
    $cosecha_id = intval($_GET['id']);
    
    // Crear instancia del modelo
    $cosecha_modelo = new Cosecha();
    
    // Obtener la cosecha
    $resultado = $cosecha_modelo->obtenerCosecha($cosecha_id, $usuario_actual['id'], $usuario_actual['rol']);
    
    if ($resultado['success']) {
        // Formatear datos adicionales para la vista
        $cosecha = $resultado['cosecha'];
        
        // Calcular rendimiento por hectárea si hay datos
        if ($cosecha['lot_area'] > 0) {
            $cosecha['rendimiento_por_ha'] = $cosecha['cos_cantidad_cosechada'] / $cosecha['lot_area'];
        } else {
            $cosecha['rendimiento_por_ha'] = 0;
        }
        
        // Formatear fechas
        $cosecha['fecha_cosecha_formateada'] = date('d/m/Y', strtotime($cosecha['cos_fecha_cosecha']));
        $cosecha['fecha_siembra_formateada'] = date('d/m/Y', strtotime($cosecha['sie_fecha_siembra']));
        
        if ($cosecha['sie_fecha_estimada_cosecha']) {
            $cosecha['fecha_estimada_formateada'] = date('d/m/Y', strtotime($cosecha['sie_fecha_estimada_cosecha']));
            
            // Calcular si la cosecha fue a tiempo, temprana o tardía
            $fecha_cosecha = strtotime($cosecha['cos_fecha_cosecha']);
            $fecha_estimada = strtotime($cosecha['sie_fecha_estimada_cosecha']);
            $diferencia_dias = round(($fecha_cosecha - $fecha_estimada) / (60 * 60 * 24));
            
            if ($diferencia_dias == 0) {
                $cosecha['puntualidad'] = 'A tiempo';
                $cosecha['puntualidad_clase'] = 'success';
            } elseif ($diferencia_dias < 0) {
                $cosecha['puntualidad'] = 'Temprana (' . abs($diferencia_dias) . ' días)';
                $cosecha['puntualidad_clase'] = 'info';
            } else {
                $cosecha['puntualidad'] = 'Tardía (' . $diferencia_dias . ' días)';
                $cosecha['puntualidad_clase'] = 'warning';
            }
        }
        
        // Calcular eficiencia (esto es un ejemplo, se puede ajustar según criterios específicos)
        $eficiencia = 0;
        if ($cosecha['cos_calidad'] == 'primera') $eficiencia += 40;
        elseif ($cosecha['cos_calidad'] == 'segunda') $eficiencia += 30;
        elseif ($cosecha['cos_calidad'] == 'tercera') $eficiencia += 20;
        else $eficiencia += 5;
        
        if (isset($cosecha['puntualidad_clase'])) {
            if ($cosecha['puntualidad_clase'] == 'success') $eficiencia += 30;
            elseif ($cosecha['puntualidad_clase'] == 'info') $eficiencia += 35;
            else $eficiencia += 20;
        }
        
        if ($cosecha['rendimiento_por_ha'] > 0) {
            $eficiencia += min(30, $cosecha['rendimiento_por_ha'] * 2); // Ajustar según cultivo
        }
        
        $cosecha['eficiencia'] = min(100, $eficiencia);
        
        echo json_encode([
            'success' => true,
            'cosecha' => $cosecha,
            'message' => 'Cosecha obtenida correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultado['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error al obtener cosecha: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intente nuevamente.'
    ]);
}
?>