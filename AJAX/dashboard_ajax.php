<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Configurar manejo de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../php_error.log');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Verificar sesión
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Sesión no válida'
        ));
        exit;
    }

    // Incluir conexión
    require_once('../CONFIG/Conexion.php');
    $conexion = new Conexion();
    $mysqli = $conexion->getMysqli();

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        case 'get_estadisticas':
            echo json_encode(getEstadisticas($mysqli));
            break;
            
        case 'get_cultivos_recientes':
            echo json_encode(getCultivosRecientes($mysqli));
            break;
            
        case 'get_alertas':
            echo json_encode(getAlertas($mysqli));
            break;
            
        case 'get_grafico_produccion':
            $periodo = isset($_POST['periodo']) ? $_POST['periodo'] : '1y';
            echo json_encode(getGraficoProduccion($mysqli, $periodo));
            break;
            
        case 'get_actividades':
            echo json_encode(getActividadesProgramadas($mysqli));
            break;

        default:
            echo json_encode(array(
                'success' => false,
                'message' => 'Acción no válida'
            ));
    }

} catch (Exception $e) {
    error_log("Error en dashboard_ajax.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}

/**
 * Obtener estadísticas principales del dashboard
 */
function getEstadisticas($mysqli) {
    try {
        $estadisticas = array();
        
        // Usuarios activos
        $result = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE usu_estado = 'activo'");
        $estadisticas['usuarios_activos'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Fincas registradas
        $result = $mysqli->query("SELECT COUNT(*) as total FROM fincas WHERE fin_estado = 'activa'");
        $estadisticas['fincas_registradas'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Siembras activas
        $result = $mysqli->query("SELECT COUNT(*) as total FROM siembras WHERE sie_estado IN ('sembrada', 'en_crecimiento')");
        $estadisticas['siembras_activas'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Tareas pendientes
        $result = $mysqli->query("SELECT COUNT(*) as total FROM actividades WHERE act_fecha >= CURDATE() - INTERVAL 7 DAY");
        $estadisticas['tareas_pendientes'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        return array(
            'success' => true,
            'data' => $estadisticas
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
        );
    }
}

/**
 * Obtener cultivos recientes
 */
function getCultivosRecientes($mysqli) {
    try {
        $sql = "SELECT s.sie_id, tc.tip_nombre as cultivo, f.fin_nombre as finca, 
                       s.sie_estado, s.sie_fecha_siembra, a.act_tipo as ultima_actividad,
                       a.act_fecha as fecha_actividad
                FROM siembras s 
                LEFT JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id
                LEFT JOIN fincas f ON l.lot_finca_id = f.fin_id
                LEFT JOIN actividades a ON s.sie_id = a.act_siembra_id 
                WHERE s.sie_estado IN ('sembrada', 'en_crecimiento')
                ORDER BY s.sie_fecha_siembra DESC, a.act_fecha DESC
                LIMIT 10";
                
        $result = $mysqli->query($sql);
        $cultivos = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cultivos[] = array(
                    'id' => $row['sie_id'],
                    'cultivo' => $row['cultivo'] ?: 'Cultivo',
                    'ubicacion' => $row['finca'] ?: 'Sin ubicación',
                    'estado' => ucfirst($row['sie_estado']),
                    'ultima_actividad' => $row['ultima_actividad'] ?: 'Sin actividad',
                    'fecha_actividad' => $row['fecha_actividad'] ? date('d/m/Y', strtotime($row['fecha_actividad'])) : 'N/A'
                );
            }
        }
        
        return array(
            'success' => true,
            'data' => $cultivos
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al obtener cultivos: ' . $e->getMessage()
        );
    }
}

/**
 * Obtener alertas activas
 */
function getAlertas($mysqli) {
    try {
        $sql = "SELECT ale_titulo as titulo, ale_mensaje as mensaje, 
                       ale_tipo as tipo, ale_prioridad as prioridad,
                       ale_fecha_registro as fecha
                FROM alertas 
                WHERE ale_estado = 'pendiente'
                ORDER BY ale_prioridad DESC, ale_fecha_registro DESC
                LIMIT 5";
                
        $result = $mysqli->query($sql);
        $alertas = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $alertas[] = array(
                    'titulo' => $row['titulo'],
                    'mensaje' => $row['mensaje'],
                    'tipo' => $row['tipo'],
                    'prioridad' => $row['prioridad'],
                    'fecha' => date('H:i', strtotime($row['fecha']))
                );
            }
        }
        
        // Si no hay alertas reales, mostrar mensaje informativo
        if (empty($alertas)) {
            $alertas = array(
                array(
                    'titulo' => 'Sin alertas',
                    'mensaje' => 'No hay alertas pendientes en este momento',
                    'tipo' => 'info',
                    'prioridad' => 'baja',
                    'fecha' => date('H:i')
                )
            );
        }
        
        return array(
            'success' => true,
            'data' => $alertas
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al obtener alertas: ' . $e->getMessage()
        );
    }
}

/**
 * Obtener datos para gráfico de producción
 */
function getGraficoProduccion($mysqli, $periodo) {
    try {
        $datos = array();
        $etiquetas = array();
        
        switch ($periodo) {
            case '6m':
                // Últimos 6 meses
                $sql = "SELECT MONTH(cos_fecha_cosecha) as mes, SUM(cos_cantidad_cosechada) as total
                        FROM cosechas 
                        WHERE cos_fecha_cosecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                        GROUP BY MONTH(cos_fecha_cosecha)
                        ORDER BY mes";
                $etiquetas = ['Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                break;
                
            case '1y':
                // Último año
                $sql = "SELECT MONTH(cos_fecha_cosecha) as mes, SUM(cos_cantidad_cosechada) as total
                        FROM cosechas 
                        WHERE cos_fecha_cosecha >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                        GROUP BY MONTH(cos_fecha_cosecha)
                        ORDER BY mes";
                $etiquetas = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                break;
                
            case '2y':
                // Últimos 2 años
                $sql = "SELECT YEAR(cos_fecha_cosecha) as año, MONTH(cos_fecha_cosecha) as mes, 
                               SUM(cos_cantidad_cosechada) as total
                        FROM cosechas 
                        WHERE cos_fecha_cosecha >= DATE_SUB(NOW(), INTERVAL 2 YEAR)
                        GROUP BY año, mes
                        ORDER BY año, mes";
                $etiquetas = ['2023', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                break;
        }
        
        $result = $mysqli->query($sql);
        $datos_db = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $datos_db[] = floatval($row['total']);
            }
        }
        
        // Si no hay datos reales, mostrar vacío
        if (empty($datos_db)) {
            $datos = array_fill(0, count($etiquetas), 0);
        } else {
            $datos = $datos_db;
        }
        
        return array(
            'success' => true,
            'has_real_data' => !empty($datos_db),
            'data' => array(
                'labels' => $etiquetas,
                'datasets' => array(
                    array(
                        'label' => empty($datos_db) ? 'Sin datos de producción' : 'Producción (kg)',
                        'data' => $datos,
                        'backgroundColor' => empty($datos_db) ? 'rgba(200, 200, 200, 0.3)' : 'rgba(46, 125, 50, 0.8)',
                        'borderColor' => empty($datos_db) ? 'rgba(200, 200, 200, 0.5)' : 'rgba(46, 125, 50, 1)',
                        'borderWidth' => 2
                    )
                )
            ),
            'message' => empty($datos_db) ? 'No hay datos de producción registrados para este período' : ''
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al obtener datos de producción: ' . $e->getMessage()
        );
    }
}

/**
 * Obtener actividades programadas
 */
function getActividadesProgramadas($mysqli) {
    try {
        $sql = "SELECT act_id, act_titulo as titulo, act_descripcion as descripcion,
                       act_fecha, act_tipo, act_estado,
                       f.fin_nombre as ubicacion
                FROM actividades a
                LEFT JOIN siembras s ON a.act_siembra_id = s.sie_id
                LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id
                LEFT JOIN fincas f ON l.lot_finca_id = f.fin_id
                WHERE act_fecha >= CURDATE() 
                AND act_estado = 'pendiente'
                ORDER BY act_fecha ASC
                LIMIT 10";
                
        $result = $mysqli->query($sql);
        $actividades = array();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fecha_obj = new DateTime($row['act_fecha']);
                $actividades[] = array(
                    'id' => $row['act_id'],
                    'titulo' => $row['titulo'] ?: 'Actividad sin título',
                    'descripcion' => $row['descripcion'] ?: '',
                    'fecha' => $fecha_obj->format('d'),
                    'mes' => $fecha_obj->format('M'),
                    'ubicacion' => $row['ubicacion'] ?: 'Ubicación no especificada',
                    'tipo' => $row['act_tipo'],
                    'estado' => $row['act_estado']
                );
            }
        }
        
        return array(
            'success' => true,
            'data' => $actividades
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Error al obtener actividades: ' . $e->getMessage()
        );
    }
}
?>