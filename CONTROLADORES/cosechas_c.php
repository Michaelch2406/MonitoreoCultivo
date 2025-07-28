<?php
require_once('../MODELOS/cosechas_m.php');
require_once('../CONFIG/roles.php');

class CosechaControlador {
    private $cosecha_modelo;
    
    public function __construct() {
        $this->cosecha_modelo = new Cosecha();
    }
    
    /**
     * Listar cosechas con filtros opcionales
     */
    public function listarCosechas($usuario_id, $rol_usuario, $filtros = []) {
        try {
            $resultado = $this->cosecha_modelo->listarCosechas($usuario_id, $rol_usuario);
            
            if ($resultado['success'] && !empty($filtros)) {
                $cosechas_filtradas = $this->aplicarFiltros($resultado['cosechas'], $filtros);
                $resultado['cosechas'] = $cosechas_filtradas;
                $resultado['total_filtradas'] = count($cosechas_filtradas);
            }
            
            return $resultado;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar cosechas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener una cosecha específica
     */
    public function obtenerCosecha($cosecha_id, $usuario_id, $rol_usuario) {
        try {
            return $this->cosecha_modelo->obtenerCosecha($cosecha_id, $usuario_id, $rol_usuario);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear nueva cosecha
     */
    public function crearCosecha($datos, $usuario_id, $rol_usuario) {
        try {
            // Validaciones adicionales del controlador
            $validacion = $this->validarDatosCosecha($datos);
            if (!$validacion['valido']) {
                return [
                    'success' => false,
                    'message' => $validacion['mensaje']
                ];
            }
            
            return $this->cosecha_modelo->crearCosecha($datos, $usuario_id, $rol_usuario);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar cosecha existente
     */
    public function actualizarCosecha($cosecha_id, $datos, $usuario_id, $rol_usuario) {
        try {
            // Validaciones adicionales del controlador
            $validacion = $this->validarDatosCosecha($datos, true);
            if (!$validacion['valido']) {
                return [
                    'success' => false,
                    'message' => $validacion['mensaje']
                ];
            }
            
            return $this->cosecha_modelo->actualizarCosecha($cosecha_id, $datos, $usuario_id, $rol_usuario);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar cosecha
     */
    public function eliminarCosecha($cosecha_id, $usuario_id, $rol_usuario) {
        try {
            return $this->cosecha_modelo->eliminarCosecha($cosecha_id, $usuario_id, $rol_usuario);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de productividad
     */
    public function obtenerEstadisticasProductividad($usuario_id, $rol_usuario, $filtros = []) {
        try {
            return $this->cosecha_modelo->obtenerEstadisticasProductividad($usuario_id, $rol_usuario, $filtros);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener comparación histórica
     */
    public function obtenerComparacionHistorica($usuario_id, $rol_usuario, $periodo_actual, $periodo_anterior) {
        try {
            return $this->cosecha_modelo->obtenerComparacionHistorica($usuario_id, $rol_usuario, $periodo_actual, $periodo_anterior);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener comparación histórica: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener resumen de cosechas por período
     */
    public function obtenerResumenPorPeriodo($usuario_id, $rol_usuario, $fecha_inicio, $fecha_fin) {
        try {
            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ];
            
            $resultado_cosechas = $this->cosecha_modelo->listarCosechas($usuario_id, $rol_usuario);
            
            if (!$resultado_cosechas['success']) {
                return $resultado_cosechas;
            }
            
            $cosechas = $resultado_cosechas['cosechas'];
            $cosechas_periodo = [];
            
            // Filtrar por período
            foreach ($cosechas as $cosecha) {
                $fecha_cosecha = strtotime($cosecha['cos_fecha_cosecha']);
                $inicio = strtotime($fecha_inicio);
                $fin = strtotime($fecha_fin);
                
                if ($fecha_cosecha >= $inicio && $fecha_cosecha <= $fin) {
                    $cosechas_periodo[] = $cosecha;
                }
            }
            
            // Calcular estadísticas del período
            $resumen = $this->calcularResumenCosechas($cosechas_periodo);
            
            return [
                'success' => true,
                'resumen' => $resumen,
                'cosechas' => $cosechas_periodo,
                'periodo' => [
                    'inicio' => $fecha_inicio,
                    'fin' => $fecha_fin
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener resumen por período: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Exportar datos de cosechas
     */
    public function exportarCosechas($usuario_id, $rol_usuario, $formato = 'excel', $filtros = []) {
        try {
            $resultado = $this->listarCosechas($usuario_id, $rol_usuario, $filtros);
            
            if (!$resultado['success']) {
                return $resultado;
            }
            
            $cosechas = $resultado['cosechas'];
            
            switch ($formato) {
                case 'excel':
                    return $this->exportarAExcel($cosechas);
                case 'csv':
                    return $this->exportarACSV($cosechas);
                case 'pdf':
                    return $this->exportarAPDF($cosechas);
                default:
                    return [
                        'success' => false,
                        'message' => 'Formato de exportación no válido'
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al exportar cosechas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de cosecha
     */
    private function validarDatosCosecha($datos, $es_actualizacion = false) {
        $errores = [];
        
        // Validar fecha de cosecha
        if (empty($datos['fecha_cosecha'])) {
            $errores[] = 'La fecha de cosecha es requerida';
        } elseif (strtotime($datos['fecha_cosecha']) > strtotime('today')) {
            $errores[] = 'La fecha de cosecha no puede ser futura';
        }
        
        // Validar cantidad
        if (empty($datos['cantidad_cosechada']) || $datos['cantidad_cosechada'] <= 0) {
            $errores[] = 'La cantidad cosechada debe ser mayor a 0';
        }
        
        // Validar unidad
        $unidades_validas = ['kg', 'ton', 'lb', 'qq', 'bultos', 'cajas', 'unidades'];
        if (empty($datos['unidad']) || !in_array($datos['unidad'], $unidades_validas)) {
            $errores[] = 'La unidad de medida no es válida';
        }
        
        // Validar calidad
        $calidades_validas = ['primera', 'segunda', 'tercera', 'descarte'];
        if (empty($datos['calidad']) || !in_array($datos['calidad'], $calidades_validas)) {
            $errores[] = 'La calidad del producto no es válida';
        }
        
        // Validar coherencia comercial
        if (!empty($datos['precio_venta_unitario']) && empty($datos['comprador'])) {
            $errores[] = 'Si especifica precio de venta, debe indicar el comprador';
        }
        
        if (!empty($datos['total_ingresos']) && empty($datos['precio_venta_unitario'])) {
            $errores[] = 'Si especifica ingresos totales, debe indicar el precio unitario';
        }
        
        // Validar cálculo de ingresos
        if (!empty($datos['precio_venta_unitario']) && !empty($datos['total_ingresos'])) {
            $total_calculado = $datos['cantidad_cosechada'] * $datos['precio_venta_unitario'];
            $diferencia = abs($total_calculado - $datos['total_ingresos']);
            
            if ($diferencia > 0.01) {
                $errores[] = 'El total de ingresos no coincide con el cálculo (cantidad × precio unitario)';
            }
        }
        
        return [
            'valido' => empty($errores),
            'mensaje' => empty($errores) ? 'Datos válidos' : implode(', ', $errores),
            'errores' => $errores
        ];
    }
    
    /**
     * Aplicar filtros a las cosechas
     */
    private function aplicarFiltros($cosechas, $filtros) {
        $cosechas_filtradas = $cosechas;
        
        if (!empty($filtros['siembra_id'])) {
            $cosechas_filtradas = array_filter($cosechas_filtradas, function($cosecha) use ($filtros) {
                return $cosecha['sie_id'] == $filtros['siembra_id'];
            });
        }
        
        if (!empty($filtros['calidad'])) {
            $cosechas_filtradas = array_filter($cosechas_filtradas, function($cosecha) use ($filtros) {
                return $cosecha['cos_calidad'] == $filtros['calidad'];
            });
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $cosechas_filtradas = array_filter($cosechas_filtradas, function($cosecha) use ($filtros) {
                return strtotime($cosecha['cos_fecha_cosecha']) >= strtotime($filtros['fecha_inicio']);
            });
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $cosechas_filtradas = array_filter($cosechas_filtradas, function($cosecha) use ($filtros) {
                return strtotime($cosecha['cos_fecha_cosecha']) <= strtotime($filtros['fecha_fin']);
            });
        }
        
        if (!empty($filtros['comprador'])) {
            $cosechas_filtradas = array_filter($cosechas_filtradas, function($cosecha) use ($filtros) {
                return stripos($cosecha['cos_comprador'], $filtros['comprador']) !== false;
            });
        }
        
        return array_values($cosechas_filtradas);
    }
    
    /**
     * Calcular resumen de un conjunto de cosechas
     */
    private function calcularResumenCosechas($cosechas) {
        $resumen = [
            'total_cosechas' => count($cosechas),
            'cantidad_total' => 0,
            'ingresos_totales' => 0,
            'cosechas_por_calidad' => [
                'primera' => 0,
                'segunda' => 0,
                'tercera' => 0,
                'descarte' => 0
            ],
            'cosechas_vendidas' => 0,
            'cosechas_almacenadas' => 0,
            'precio_promedio' => 0,
            'rendimiento_promedio' => 0
        ];
        
        $total_precios = 0;
        $cosechas_con_precio = 0;
        $total_rendimiento = 0;
        $cosechas_con_rendimiento = 0;
        
        foreach ($cosechas as $cosecha) {
            // Cantidad total
            $resumen['cantidad_total'] += floatval($cosecha['cos_cantidad_cosechada']);
            
            // Ingresos totales
            if ($cosecha['cos_total_ingresos']) {
                $resumen['ingresos_totales'] += floatval($cosecha['cos_total_ingresos']);
            }
            
            // Cosechas por calidad
            if (isset($resumen['cosechas_por_calidad'][$cosecha['cos_calidad']])) {
                $resumen['cosechas_por_calidad'][$cosecha['cos_calidad']]++;
            }
            
            // Estado de venta
            if ($cosecha['cos_total_ingresos'] > 0) {
                $resumen['cosechas_vendidas']++;
            } else {
                $resumen['cosechas_almacenadas']++;
            }
            
            // Precio promedio
            if ($cosecha['cos_precio_venta_unitario'] > 0) {
                $total_precios += floatval($cosecha['cos_precio_venta_unitario']);
                $cosechas_con_precio++;
            }
            
            // Rendimiento promedio (si hay datos de área)
            if (isset($cosecha['lot_area']) && $cosecha['lot_area'] > 0) {
                $rendimiento = $cosecha['cos_cantidad_cosechada'] / $cosecha['lot_area'];
                $total_rendimiento += $rendimiento;
                $cosechas_con_rendimiento++;
            }
        }
        
        // Calcular promedios
        if ($cosechas_con_precio > 0) {
            $resumen['precio_promedio'] = $total_precios / $cosechas_con_precio;
        }
        
        if ($cosechas_con_rendimiento > 0) {
            $resumen['rendimiento_promedio'] = $total_rendimiento / $cosechas_con_rendimiento;
        }
        
        return $resumen;
    }
    
    /**
     * Exportar a Excel (implementación básica)
     */
    private function exportarAExcel($cosechas) {
        // Aquí se implementaría la lógica para generar Excel
        // Por ahora retornamos un placeholder
        return [
            'success' => true,
            'message' => 'Funcionalidad de exportación a Excel en desarrollo',
            'data' => $cosechas
        ];
    }
    
    /**
     * Exportar a CSV
     */
    private function exportarACSV($cosechas) {
        // Implementación básica de CSV
        $csv_data = "ID,Fecha Cosecha,Siembra,Cantidad,Unidad,Calidad,Comprador,Precio Unitario,Total Ingresos\n";
        
        foreach ($cosechas as $cosecha) {
            $csv_data .= sprintf(
                "%d,%s,%s,%.2f,%s,%s,%s,%.2f,%.2f\n",
                $cosecha['cos_id'],
                $cosecha['cos_fecha_cosecha'],
                $cosecha['lot_nombre'] . ' - ' . $cosecha['cul_nombre'],
                $cosecha['cos_cantidad_cosechada'],
                $cosecha['cos_unidad'],
                $cosecha['cos_calidad'],
                $cosecha['cos_comprador'] ?: 'Sin vender',
                $cosecha['cos_precio_venta_unitario'] ?: 0,
                $cosecha['cos_total_ingresos'] ?: 0
            );
        }
        
        return [
            'success' => true,
            'data' => $csv_data,
            'filename' => 'cosechas_' . date('Y-m-d') . '.csv'
        ];
    }
    
    /**
     * Exportar a PDF (implementación básica)
     */
    private function exportarAPDF($cosechas) {
        // Aquí se implementaría la lógica para generar PDF
        // Por ahora retornamos un placeholder
        return [
            'success' => true,
            'message' => 'Funcionalidad de exportación a PDF en desarrollo',
            'data' => $cosechas
        ];
    }
}
?>