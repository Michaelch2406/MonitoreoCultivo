<?php
require_once('../CONFIG/roles.php');
require_once('../MODELOS/finanzas_m.php');

class FinanzasController {
    private $finanzas_modelo;
    
    public function __construct() {
        $this->finanzas_modelo = new Finanzas();
    }
    
    /**
     * Obtener datos para el dashboard financiero
     */
    public function obtenerDashboard($usuario_id, $rol_usuario) {
        try {
            // Obtener gastos
            $resultado_gastos = $this->finanzas_modelo->listarGastos($usuario_id, $rol_usuario);
            $gastos = $resultado_gastos['success'] ? $resultado_gastos['gastos'] : array();
            
            // Obtener ingresos totales
            $resultado_ingresos = $this->finanzas_modelo->obtenerIngresosTotales($usuario_id, $rol_usuario);
            $ingresos_totales = $resultado_ingresos['success'] ? $resultado_ingresos['total'] : 0;
            
            // Calcular estadísticas
            $total_gastos = 0;
            $gastos_mes_actual = 0;
            $gastos_por_tipo = [
                'semillas' => 0,
                'fertilizantes' => 0,
                'pesticidas' => 0,
                'mano_obra' => 0,
                'maquinaria' => 0,
                'otros' => 0
            ];
            
            $mes_actual = date('Y-m');
            
            foreach ($gastos as $gasto) {
                $monto = floatval($gasto['gas_monto']);
                $total_gastos += $monto;
                
                if (strpos($gasto['gas_fecha'], $mes_actual) === 0) {
                    $gastos_mes_actual += $monto;
                }
                
                if (isset($gastos_por_tipo[$gasto['gas_tipo']])) {
                    $gastos_por_tipo[$gasto['gas_tipo']] += $monto;
                }
            }
            
            // Calcular métricas financieras
            $utilidad_neta = $ingresos_totales - $total_gastos;
            $margen_ganancia = $ingresos_totales > 0 ? (($utilidad_neta) / $ingresos_totales) * 100 : 0;
            $roi = $total_gastos > 0 ? ($utilidad_neta / $total_gastos) * 100 : 0;
            
            return [
                'success' => true,
                'data' => [
                    'gastos' => $gastos,
                    'total_gastos' => $total_gastos,
                    'ingresos_totales' => $ingresos_totales,
                    'utilidad_neta' => $utilidad_neta,
                    'gastos_mes_actual' => $gastos_mes_actual,
                    'gastos_por_tipo' => $gastos_por_tipo,
                    'margen_ganancia' => $margen_ganancia,
                    'roi' => $roi
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerDashboard: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener datos del dashboard financiero'
            ];
        }
    }
    
    /**
     * Procesar creación de gasto
     */
    public function crearGasto($datos, $usuario_id, $rol_usuario) {
        try {
            return $this->finanzas_modelo->crearGasto($datos, $usuario_id, $rol_usuario);
        } catch (Exception $e) {
            error_log("Error en crearGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear el gasto'
            ];
        }
    }
    
    /**
     * Procesar actualización de gasto
     */
    public function actualizarGasto($gasto_id, $datos, $usuario_id, $rol_usuario) {
        try {
            return $this->finanzas_modelo->actualizarGasto($gasto_id, $datos, $usuario_id, $rol_usuario);
        } catch (Exception $e) {
            error_log("Error en actualizarGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar el gasto'
            ];
        }
    }
    
    /**
     * Procesar eliminación de gasto
     */
    public function eliminarGasto($gasto_id, $usuario_id, $rol_usuario) {
        try {
            return $this->finanzas_modelo->eliminarGasto($gasto_id, $usuario_id, $rol_usuario);
        } catch (Exception $e) {
            error_log("Error en eliminarGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar el gasto'
            ];
        }
    }
    
    /**
     * Obtener análisis financiero por cultivo
     */
    public function obtenerAnalisisFinanciero($usuario_id, $rol_usuario, $fecha_inicio = null, $fecha_fin = null) {
        try {
            return $this->finanzas_modelo->obtenerAnalisisFinancieroPorCultivo(
                $usuario_id, 
                $rol_usuario, 
                $fecha_inicio, 
                $fecha_fin
            );
        } catch (Exception $e) {
            error_log("Error en obtenerAnalisisFinanciero: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener el análisis financiero'
            ];
        }
    }
    
    /**
     * Obtener datos para reportes financieros
     */
    public function obtenerDatosReporte($usuario_id, $rol_usuario, $filtros = []) {
        try {
            $dashboard = $this->obtenerDashboard($usuario_id, $rol_usuario);
            
            if (!$dashboard['success']) {
                return $dashboard;
            }
            
            $analisis = $this->obtenerAnalisisFinanciero(
                $usuario_id, 
                $rol_usuario, 
                $filtros['fecha_inicio'] ?? null,
                $filtros['fecha_fin'] ?? null
            );
            
            return [
                'success' => true,
                'dashboard' => $dashboard['data'],
                'analisis_cultivos' => $analisis['success'] ? $analisis['analisis'] : []
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerDatosReporte: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al generar reporte financiero'
            ];
        }
    }
    
    /**
     * Calcular punto de equilibrio por cultivo
     */
    public function calcularPuntoEquilibrio($usuario_id, $rol_usuario, $cultivo_id = null) {
        try {
            $sql_base = "SELECT 
                            tc.tip_nombre as cultivo,
                            SUM(g.gas_monto) as total_gastos,
                            AVG(c.cos_precio_venta_unitario) as precio_promedio,
                            AVG(l.lot_area) as area_promedio
                        FROM tipos_cultivos tc
                        INNER JOIN siembras s ON tc.tip_id = s.sie_tipo_cultivo_id
                        INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                        INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                        LEFT JOIN gastos g ON s.sie_id = g.gas_siembra_id
                        LEFT JOIN cosechas c ON s.sie_id = c.cos_siembra_id
                        WHERE g.gas_monto IS NOT NULL";
            
            if ($cultivo_id) {
                $sql_base .= " AND tc.tip_id = " . intval($cultivo_id);
            }
            
            // Aplicar filtros de permisos
            if ($rol_usuario == 'agricultor') {
                $sql_base .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql_base .= " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $sql_base .= " GROUP BY tc.tip_id, tc.tip_nombre
                          HAVING total_gastos > 0 AND precio_promedio > 0
                          ORDER BY cultivo";
            
            $resultado = $this->finanzas_modelo->conexion->ejecutarSP($sql_base);
            
            $puntos_equilibrio = [];
            if ($resultado) {
                while ($fila = $resultado->fetch_assoc()) {
                    $punto_equilibrio = floatval($fila['total_gastos']) / floatval($fila['precio_promedio']);
                    $puntos_equilibrio[] = [
                        'cultivo' => $fila['cultivo'],
                        'total_gastos' => floatval($fila['total_gastos']),
                        'precio_promedio' => floatval($fila['precio_promedio']),
                        'area_promedio' => floatval($fila['area_promedio']),
                        'punto_equilibrio_kg' => round($punto_equilibrio, 2),
                        'punto_equilibrio_por_ha' => $fila['area_promedio'] > 0 ? round($punto_equilibrio / floatval($fila['area_promedio']), 2) : 0
                    ];
                }
                $resultado->free();
            }
            
            return [
                'success' => true,
                'puntos_equilibrio' => $puntos_equilibrio
            ];
            
        } catch (Exception $e) {
            error_log("Error en calcularPuntoEquilibrio: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al calcular punto de equilibrio'
            ];
        }
    }
}
?>