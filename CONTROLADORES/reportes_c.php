<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/reportes_m.php');

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_actual = obtenerUsuarioActual();
$reportes_modelo = new Reportes();

// Determinar la acción a realizar
$action = $_REQUEST['action'] ?? $_POST['action'] ?? 'dashboard';

try {
    switch ($action) {
        case 'dashboard':
            // Dashboard principal - datos para gráficos
            $estadisticas = $reportes_modelo->obtenerEstadisticasGenerales(
                $usuario_actual['id'], 
                $usuario_actual['rol']
            );
            echo json_encode($estadisticas);
            break;
            
        case 'produccion_mensual':
            // Gráfico de producción mensual
            $año = $_GET['año'] ?? null;
            $produccion = $reportes_modelo->obtenerProduccionMensual(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                $año
            );
            echo json_encode($produccion);
            break;
            
        case 'gastos_vs_ingresos':
            // Gráfico de gastos vs ingresos
            $periodo = $_GET['periodo'] ?? '12_meses';
            $datos = $reportes_modelo->obtenerGastosVsIngresos(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                $periodo
            );
            echo json_encode($datos);
            break;
            
        case 'distribucion_cultivos':
            // Gráfico de distribución de cultivos
            $distribucion = $reportes_modelo->obtenerDistribucionCultivos(
                $usuario_actual['id'], 
                $usuario_actual['rol']
            );
            echo json_encode($distribucion);
            break;
            
        case 'reporte_cosechas':
            // Reporte de cosechas
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null,
                'lote_id' => $_GET['lote_id'] ?? null,
                'calidad' => $_GET['calidad'] ?? null
            ];
            
            $cosechas = $reportes_modelo->reporteCosechas(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                array_filter($filtros)
            );
            echo json_encode($cosechas);
            break;
            
        case 'reporte_rendimiento':
            // Reporte de rendimiento
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null
            ];
            
            $rendimiento = $reportes_modelo->reporteRendimiento(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                array_filter($filtros)
            );
            echo json_encode($rendimiento);
            break;
            
        case 'estado_resultados':
            // Estado de resultados financieros
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null
            ];
            
            $resultados = $reportes_modelo->estadoResultados(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                array_filter($filtros)
            );
            echo json_encode($resultados);
            break;
            
        case 'flujo_caja':
            // Flujo de caja
            $periodo = $_GET['periodo'] ?? 'mensual';
            $año = $_GET['año'] ?? null;
            
            $flujo = $reportes_modelo->flujoCaja(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                $periodo, 
                $año
            );
            echo json_encode($flujo);
            break;
            
        case 'historial_actividades':
            // Historial de actividades
            $filtros = [
                'lote_id' => $_GET['lote_id'] ?? null,
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'tipo_actividad' => $_GET['tipo_actividad'] ?? null
            ];
            
            $actividades = $reportes_modelo->historialActividades(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                array_filter($filtros)
            );
            echo json_encode($actividades);
            break;
            
        case 'registro_monitoreo':
            // Registro de monitoreo
            $filtros = [
                'lote_id' => $_GET['lote_id'] ?? null,
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null
            ];
            
            $monitoreos = $reportes_modelo->registroMonitoreo(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                array_filter($filtros)
            );
            echo json_encode($monitoreos);
            break;
            
        case 'cultivos_filtro':
            // Obtener cultivos para filtros
            $cultivos = $reportes_modelo->obtenerCultivosParaFiltro(
                $usuario_actual['id'], 
                $usuario_actual['rol']
            );
            echo json_encode($cultivos);
            break;
            
        case 'lotes_filtro':
            // Obtener lotes para filtros
            $lotes = $reportes_modelo->obtenerLotesParaFiltro(
                $usuario_actual['id'], 
                $usuario_actual['rol']
            );
            echo json_encode($lotes);
            break;
            
        case 'exportar':
            // Exportar datos
            $tipo_reporte = $_POST['tipo_reporte'] ?? $_GET['tipo_reporte'] ?? '';
            $formato = $_POST['formato'] ?? $_GET['formato'] ?? 'pdf';
            $filtros = $_POST['filtros'] ?? $_GET['filtros'] ?? [];
            
            // Verificar permisos de exportación
            if ($formato === 'csv' && $usuario_actual['rol'] === 'supervisor') {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para exportar en formato CSV']);
                exit;
            }
            
            // Procesar exportación según tipo y formato
            exportarReporte($tipo_reporte, $formato, $filtros, $usuario_actual);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en reportes_c.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Función para exportar reportes en diferentes formatos
 */
function exportarReporte($tipo_reporte, $formato, $filtros, $usuario_actual) {
    global $reportes_modelo;
    
    try {
        // Obtener datos según tipo de reporte
        $datos = [];
        $titulo = '';
        
        switch ($tipo_reporte) {
            case 'cosechas':
                $resultado = $reportes_modelo->reporteCosechas(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $filtros
                );
                $datos = $resultado['success'] ? $resultado['cosechas'] : [];
                $titulo = 'Reporte de Cosechas';
                break;
                
            case 'rendimiento':
                $resultado = $reportes_modelo->reporteRendimiento(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $filtros
                );
                $datos = $resultado['success'] ? $resultado['rendimientos'] : [];
                $titulo = 'Reporte de Rendimiento';
                break;
                
            case 'estado_resultados':
                $resultado = $reportes_modelo->estadoResultados(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $filtros
                );
                $datos = $resultado['success'] ? $resultado['resultados'] : [];
                $titulo = 'Estado de Resultados';
                break;
                
            case 'flujo_caja':
                $periodo = $filtros['periodo'] ?? 'mensual';
                $año = $filtros['año'] ?? null;
                $resultado = $reportes_modelo->flujoCaja(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $periodo, 
                    $año
                );
                $datos = $resultado['success'] ? $resultado['flujo'] : [];
                $titulo = 'Flujo de Caja';
                break;
                
            case 'actividades':
                $resultado = $reportes_modelo->historialActividades(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $filtros
                );
                $datos = $resultado['success'] ? $resultado['actividades'] : [];
                $titulo = 'Historial de Actividades';
                break;
                
            case 'monitoreo':
                $resultado = $reportes_modelo->registroMonitoreo(
                    $usuario_actual['id'], 
                    $usuario_actual['rol'], 
                    $filtros
                );
                $datos = $resultado['success'] ? $resultado['monitoreos'] : [];
                $titulo = 'Registro de Monitoreo';
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Tipo de reporte no válido']);
                return;
        }
        
        if (empty($datos)) {
            echo json_encode(['success' => false, 'message' => 'No hay datos para exportar']);
            return;
        }
        
        // Procesar según formato
        switch ($formato) {
            case 'pdf':
                exportarPDF($datos, $titulo, $tipo_reporte);
                break;
                
            case 'excel':
                exportarExcel($datos, $titulo, $tipo_reporte);
                break;
                
            case 'csv':
                exportarCSV($datos, $titulo, $tipo_reporte);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Formato no válido']);
                return;
        }
        
    } catch (Exception $e) {
        error_log("Error en exportarReporte: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al exportar reporte']);
    }
}

/**
 * Exportar a PDF
 */
function exportarPDF($datos, $titulo, $tipo_reporte) {
    // Esta función requeriría una librería como TCPDF o DOMPDF
    // Por ahora retornamos un placeholder
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Funcionalidad PDF en desarrollo',
        'download_url' => '#'
    ]);
}

/**
 * Exportar a Excel
 */
function exportarExcel($datos, $titulo, $tipo_reporte) {
    // Esta función requeriría una librería como PhpSpreadsheet
    // Por ahora retornamos un placeholder
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Funcionalidad Excel en desarrollo',
        'download_url' => '#'
    ]);
}

/**
 * Exportar a CSV
 */
function exportarCSV($datos, $titulo, $tipo_reporte) {
    if (empty($datos)) {
        echo json_encode(['success' => false, 'message' => 'No hay datos para exportar']);
        return;
    }
    
    // Configurar headers para descarga
    $filename = strtolower(str_replace(' ', '_', $titulo)) . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear archivo CSV
    $output = fopen('php://output', 'w');
    
    // Escribir BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Escribir encabezados
    if (!empty($datos)) {
        $headers = array_keys($datos[0]);
        fputcsv($output, $headers, ';');
        
        // Escribir datos
        foreach ($datos as $row) {
            fputcsv($output, $row, ';');
        }
    }
    
    fclose($output);
    exit;
}
?>