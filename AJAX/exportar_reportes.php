<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/reportes_m.php');

// Verificar que el usuario esté logueado
if (!estaLogueado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_actual = obtenerUsuarioActual();

try {
    // Obtener parámetros
    $tipo_reporte = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
    $formato = $_GET['formato'] ?? $_POST['formato'] ?? 'csv';
    $filtros = $_GET['filtros'] ?? $_POST['filtros'] ?? [];
    
    // Validar formato
    $formatos_permitidos = ['csv', 'excel', 'pdf'];
    if (!in_array($formato, $formatos_permitidos)) {
        throw new Exception('Formato no válido');
    }
    
    // Verificar permisos según formato y rol
    if ($formato === 'csv' && $usuario_actual['rol'] === 'supervisor') {
        throw new Exception('No tienes permisos para exportar en formato CSV');
    }
    
    // Crear instancia del modelo
    $reportes_modelo = new Reportes();
    
    // Obtener datos según tipo de reporte
    $datos = [];
    $titulo = '';
    $columnas = [];
    
    switch ($tipo_reporte) {
        case 'cosechas':
            $resultado = $reportes_modelo->reporteCosechas(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                is_array($filtros) ? $filtros : []
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['cosechas'];
            $titulo = 'Reporte de Cosechas';
            $columnas = [
                'cos_fecha_cosecha' => 'Fecha',
                'cultivo' => 'Cultivo',
                'lote' => 'Lote',
                'finca' => 'Finca',
                'cos_cantidad_cosechada' => 'Cantidad',
                'cos_unidad' => 'Unidad',
                'cos_calidad' => 'Calidad',
                'cos_precio_venta_unitario' => 'Precio Unitario',
                'cos_total_ingresos' => 'Total Ingresos',
                'rendimiento_hectarea' => 'Rendimiento/Ha',
                'responsable' => 'Responsable'
            ];
            break;
            
        case 'rendimiento':
            $fecha_filtros = is_array($filtros) ? $filtros : [];
            $resultado = $reportes_modelo->reporteRendimiento(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                $fecha_filtros
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['rendimientos'];
            $titulo = 'Reporte de Rendimiento';
            $columnas = [
                'cultivo' => 'Cultivo',
                'categoria' => 'Categoría',
                'total_cosechas' => 'Total Cosechas',
                'cantidad_total' => 'Cantidad Total',
                'promedio_cosecha' => 'Promedio por Cosecha',
                'rendimiento_promedio_hectarea' => 'Rendimiento/Ha',
                'promedio_dias_cultivo' => 'Días Promedio'
            ];
            break;
            
        case 'estado_resultados':
            $resultado = $reportes_modelo->estadoResultados(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                is_array($filtros) ? $filtros : []
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['resultados'];
            $titulo = 'Estado de Resultados';
            $columnas = [
                'cultivo' => 'Cultivo',
                'categoria' => 'Categoría',
                'total_siembras' => 'Total Siembras',
                'total_ingresos' => 'Total Ingresos',
                'total_gastos' => 'Total Gastos',
                'utilidad_bruta' => 'Utilidad Bruta',
                'margen_utilidad' => 'Margen %',
                'area_total' => 'Área Total (ha)'
            ];
            break;
            
        case 'flujo_caja':
            $periodo = $filtros['periodo'] ?? 'mensual';
            $año = $filtros['año'] ?? date('Y');
            $resultado = $reportes_modelo->flujoCaja(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                $periodo, 
                $año
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['flujo'];
            $titulo = 'Flujo de Caja';
            $columnas = [
                'periodo' => 'Período',
                'ingresos' => 'Ingresos',
                'gastos' => 'Gastos',
                'flujo_neto' => 'Flujo Neto',
                'saldo_acumulado' => 'Saldo Acumulado'
            ];
            break;
            
        case 'actividades':
            $resultado = $reportes_modelo->historialActividades(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                is_array($filtros) ? $filtros : []
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['actividades'];
            $titulo = 'Historial de Actividades';
            $columnas = [
                'act_fecha' => 'Fecha',
                'act_tipo' => 'Tipo',
                'lote' => 'Lote',
                'cultivo' => 'Cultivo',
                'act_descripcion' => 'Descripción',
                'act_productos_utilizados' => 'Productos',
                'act_cantidad_producto' => 'Cantidad',
                'act_costo' => 'Costo',
                'responsable' => 'Responsable'
            ];
            break;
            
        case 'monitoreo':
            $resultado = $reportes_modelo->registroMonitoreo(
                $usuario_actual['id'], 
                $usuario_actual['rol'], 
                is_array($filtros) ? $filtros : []
            );
            
            if (!$resultado['success']) {
                throw new Exception($resultado['message']);
            }
            
            $datos = $resultado['monitoreos'];
            $titulo = 'Registro de Monitoreo';
            $columnas = [
                'mon_fecha_observacion' => 'Fecha',
                'lote' => 'Lote',
                'cultivo' => 'Cultivo',
                'mon_estado_general' => 'Estado General',
                'mon_altura_promedio' => 'Altura Prom. (cm)',
                'mon_porcentaje_germinacion' => 'Germinación %',
                'mon_presencia_plagas' => 'Plagas',
                'mon_presencia_enfermedades' => 'Enfermedades',
                'responsable' => 'Responsable'
            ];
            break;
            
        default:
            throw new Exception('Tipo de reporte no válido');
    }
    
    if (empty($datos)) {
        throw new Exception('No hay datos para exportar');
    }
    
    // Exportar según formato
    switch ($formato) {
        case 'csv':
            exportarCSV($datos, $titulo, $columnas);
            break;
            
        case 'excel':
            exportarExcel($datos, $titulo, $columnas);
            break;
            
        case 'pdf':
            exportarPDF($datos, $titulo, $columnas);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_reportes.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Exportar datos a CSV
 */
function exportarCSV($datos, $titulo, $columnas) {
    if (empty($datos)) {
        throw new Exception('No hay datos para exportar');
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
    
    // Escribir encabezados (nombres legibles)
    $headers = array_values($columnas);
    fputcsv($output, $headers, ';');
    
    // Escribir datos
    foreach ($datos as $fila) {
        $fila_csv = [];
        foreach (array_keys($columnas) as $campo) {
            $valor = $fila[$campo] ?? '';
            
            // Formatear valores especiales
            if (is_numeric($valor) && strpos($campo, 'precio') !== false || 
                strpos($campo, 'ingresos') !== false || 
                strpos($campo, 'gastos') !== false || 
                strpos($campo, 'costo') !== false) {
                $valor = number_format((float)$valor, 2, ',', '.');
            } elseif (is_numeric($valor) && strpos($campo, 'porcentaje') !== false) {
                $valor = number_format((float)$valor, 1, ',', '.') . '%';
            }
            
            $fila_csv[] = $valor;
        }
        fputcsv($output, $fila_csv, ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Exportar datos a Excel (requiere PhpSpreadsheet)
 */
function exportarExcel($datos, $titulo, $columnas) {
    // Por ahora, implementamos una versión básica usando CSV con formato Excel
    // En el futuro se puede integrar PhpSpreadsheet para mayor funcionalidad
    
    if (empty($datos)) {
        throw new Exception('No hay datos para exportar');
    }
    
    // Configurar headers para descarga como Excel
    $filename = strtolower(str_replace(' ', '_', $titulo)) . '_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear contenido HTML que Excel puede interpretar
    echo chr(0xEF).chr(0xBB).chr(0xBF); // BOM para UTF-8
    
    echo "<html>\n<head>\n";
    echo "<meta charset='utf-8'>\n";
    echo "<style>\n";
    echo "table { border-collapse: collapse; width: 100%; }\n";
    echo "th, td { border: 1px solid #000; padding: 8px; text-align: left; }\n";
    echo "th { background-color: #2E7D32; color: white; font-weight: bold; }\n";
    echo ".number { text-align: right; }\n";
    echo ".currency { text-align: right; }\n";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    
    echo "<h2>" . htmlspecialchars($titulo) . "</h2>\n";
    echo "<p>Generado el: " . date('d/m/Y H:i:s') . "</p>\n";
    
    echo "<table>\n<thead>\n<tr>\n";
    
    // Encabezados
    foreach ($columnas as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>\n";
    }
    echo "</tr>\n</thead>\n<tbody>\n";
    
    // Datos
    foreach ($datos as $fila) {
        echo "<tr>\n";
        foreach (array_keys($columnas) as $campo) {
            $valor = $fila[$campo] ?? '';
            $clase = '';
            
            // Formatear valores y asignar clases CSS
            if (is_numeric($valor)) {
                if (strpos($campo, 'precio') !== false || 
                    strpos($campo, 'ingresos') !== false || 
                    strpos($campo, 'gastos') !== false || 
                    strpos($campo, 'costo') !== false) {
                    $valor = '$' . number_format((float)$valor, 2);
                    $clase = 'currency';
                } elseif (strpos($campo, 'porcentaje') !== false) {
                    $valor = number_format((float)$valor, 1) . '%';
                    $clase = 'number';
                } elseif (is_float($valor) || strpos($valor, '.') !== false) {
                    $valor = number_format((float)$valor, 2);
                    $clase = 'number';
                }
            }
            
            echo "<td class='" . $clase . "'>" . htmlspecialchars($valor) . "</td>\n";
        }
        echo "</tr>\n";
    }
    
    echo "</tbody>\n</table>\n";
    echo "</body>\n</html>";
    exit;
}

/**
 * Exportar datos a PDF (requiere TCPDF o similar)
 */
function exportarPDF($datos, $titulo, $columnas) {
    // Por ahora, implementamos una versión básica usando HTML
    // En el futuro se puede integrar TCPDF o DOMPDF para mayor funcionalidad
    
    if (empty($datos)) {
        throw new Exception('No hay datos para exportar');
    }
    
    // Configurar headers para descarga como PDF
    $filename = strtolower(str_replace(' ', '_', $titulo)) . '_' . date('Y-m-d_H-i-s') . '.html';
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "<!DOCTYPE html>\n<html lang='es'>\n<head>\n";
    echo "<meta charset='utf-8'>\n";
    echo "<title>" . htmlspecialchars($titulo) . "</title>\n";
    echo "<style>\n";
    echo "
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            color: #424242;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #2E7D32; 
            padding-bottom: 15px;
        }
        .header h1 { 
            color: #2E7D32; 
            margin: 0;
        }
        .header p { 
            margin: 5px 0; 
            color: #666;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px;
            font-size: 12px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #2E7D32; 
            color: white; 
            font-weight: bold;
            text-align: center;
        }
        tr:nth-child(even) { 
            background-color: #f9f9f9; 
        }
        tr:hover { 
            background-color: #f5f5f5; 
        }
        .number { 
            text-align: right; 
        }
        .currency { 
            text-align: right; 
            color: #2E7D32;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
        }
    ";
    echo "</style>\n";
    echo "</head>\n<body>\n";
    
    // Header del reporte
    echo "<div class='header'>\n";
    echo "<h1>" . htmlspecialchars($titulo) . "</h1>\n";
    echo "<p>Sistema AgroMonitor - Reporte generado el " . date('d/m/Y H:i:s') . "</p>\n";
    echo "</div>\n";
    
    // Tabla de datos
    echo "<table>\n<thead>\n<tr>\n";
    
    // Encabezados
    foreach ($columnas as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>\n";
    }
    echo "</tr>\n</thead>\n<tbody>\n";
    
    // Datos
    foreach ($datos as $fila) {
        echo "<tr>\n";
        foreach (array_keys($columnas) as $campo) {
            $valor = $fila[$campo] ?? '';
            $clase = '';
            
            // Formatear valores y asignar clases CSS
            if (is_numeric($valor)) {
                if (strpos($campo, 'precio') !== false || 
                    strpos($campo, 'ingresos') !== false || 
                    strpos($campo, 'gastos') !== false || 
                    strpos($campo, 'costo') !== false ||
                    strpos($campo, 'utilidad') !== false) {
                    $valor = '$' . number_format((float)$valor, 2);
                    $clase = 'currency';
                } elseif (strpos($campo, 'porcentaje') !== false || strpos($campo, 'margen') !== false) {
                    $valor = number_format((float)$valor, 1) . '%';
                    $clase = 'number';
                } elseif (is_float($valor) || strpos($valor, '.') !== false) {
                    $valor = number_format((float)$valor, 2);
                    $clase = 'number';
                }
            }
            
            echo "<td class='" . $clase . "'>" . htmlspecialchars($valor) . "</td>\n";
        }
        echo "</tr>\n";
    }
    
    echo "</tbody>\n</table>\n";
    
    // Footer
    echo "<div class='footer'>\n";
    echo "<p>Reporte generado por Sistema AgroMonitor | " . date('d/m/Y H:i:s') . "</p>\n";
    echo "<p>Total de registros: " . count($datos) . "</p>\n";
    echo "</div>\n";
    
    echo "</body>\n</html>";
    exit;
}
?>