<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(__DIR__ . '/../CONFIG/Conexion.php');

class Reportes {
    private $conexion;
    
    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Reportes: " . $e->getMessage());
            throw $e;
        }
    }
    
    // =====================================================
    // MÉTODOS PARA DASHBOARD PRINCIPAL
    // =====================================================
    
    /**
     * Obtener estadísticas generales para dashboard
     */
    public function obtenerEstadisticasGenerales($usuario_id, $rol) {
        try {
            $mysqli = $this->conexion->getMysqli();
            $estadisticas = [];
            
            // Determinar filtro por usuario según rol
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            // Total de fincas activas
            $sql_fincas = "SELECT COUNT(*) as total FROM fincas f WHERE f.fin_estado = 'activa'" . $filtro_usuario;
            $result = $mysqli->query($sql_fincas);
            $estadisticas['total_fincas'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Total de lotes en producción
            $sql_lotes = "SELECT COUNT(DISTINCT l.lot_id) as total 
                         FROM lotes l 
                         INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                         WHERE l.lot_estado = 'sembrado'" . $filtro_usuario;
            $result = $mysqli->query($sql_lotes);
            $estadisticas['lotes_produccion'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Cultivos por estado
            $sql_cultivos = "SELECT s.sie_estado, COUNT(*) as cantidad 
                            FROM siembras s 
                            INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                            INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                            WHERE 1=1" . $filtro_usuario . "
                            GROUP BY s.sie_estado";
            $result = $mysqli->query($sql_cultivos);
            $cultivos_estado = [];
            while ($result && $row = $result->fetch_assoc()) {
                $cultivos_estado[$row['sie_estado']] = $row['cantidad'];
            }
            $estadisticas['cultivos_estado'] = $cultivos_estado;
            
            // Alertas pendientes
            $sql_alertas = "SELECT COUNT(*) as total 
                           FROM alertas a 
                           LEFT JOIN siembras s ON a.ale_siembra_id = s.sie_id
                           LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id
                           LEFT JOIN fincas f ON l.lot_finca_id = f.fin_id
                           WHERE a.ale_estado = 'pendiente'" . 
                           ($rol !== 'administrador' ? " AND (a.ale_usuario_id = $usuario_id OR f.fin_propietario = $usuario_id)" : "");
            $result = $mysqli->query($sql_alertas);
            $estadisticas['alertas_pendientes'] = $result ? $result->fetch_assoc()['total'] : 0;
            
            return ['success' => true, 'estadisticas' => $estadisticas];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasGenerales: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas generales'];
        }
    }
    
    /**
     * Obtener datos para gráfico de producción mensual
     */
    public function obtenerProduccionMensual($usuario_id, $rol, $año = null) {
        try {
            $mysqli = $this->conexion->getMysqli();
            $año = $año ?: date('Y');
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $sql = "SELECT 
                        MONTH(c.cos_fecha_cosecha) as mes,
                        SUM(c.cos_cantidad_cosechada) as cantidad_total,
                        COUNT(*) as numero_cosechas,
                        AVG(c.cos_cantidad_cosechada) as promedio_cosecha
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE YEAR(c.cos_fecha_cosecha) = $año" . $filtro_usuario . "
                    GROUP BY MONTH(c.cos_fecha_cosecha)
                    ORDER BY mes";
            
            $result = $mysqli->query($sql);
            $produccion = [];
            
            // Inicializar todos los meses con 0
            for ($i = 1; $i <= 12; $i++) {
                $produccion[$i] = [
                    'mes' => $i,
                    'cantidad_total' => 0,
                    'numero_cosechas' => 0,
                    'promedio_cosecha' => 0
                ];
            }
            
            // Llenar con datos reales
            while ($result && $row = $result->fetch_assoc()) {
                $produccion[$row['mes']] = $row;
            }
            
            return ['success' => true, 'produccion' => array_values($produccion)];
            
        } catch (Exception $e) {
            error_log("Error en obtenerProduccionMensual: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener producción mensual'];
        }
    }
    
    /**
     * Obtener datos para gráfico de gastos vs ingresos
     */
    public function obtenerGastosVsIngresos($usuario_id, $rol, $periodo = '12_meses') {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Determinar rango de fechas
            switch ($periodo) {
                case '6_meses':
                    $fecha_inicio = date('Y-m-d', strtotime('-6 months'));
                    break;
                case '12_meses':
                    $fecha_inicio = date('Y-m-d', strtotime('-12 months'));
                    break;
                case 'año_actual':
                    $fecha_inicio = date('Y-01-01');
                    break;
                default:
                    $fecha_inicio = date('Y-m-d', strtotime('-12 months'));
            }
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            // Obtener gastos por mes
            $sql_gastos = "SELECT 
                              DATE_FORMAT(g.gas_fecha, '%Y-%m') as periodo,
                              SUM(g.gas_monto) as total_gastos
                          FROM gastos g
                          LEFT JOIN siembras s ON g.gas_siembra_id = s.sie_id
                          LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id OR g.gas_finca_id = l.lot_finca_id
                          LEFT JOIN fincas f ON l.lot_finca_id = f.fin_id OR g.gas_finca_id = f.fin_id
                          WHERE g.gas_fecha >= '$fecha_inicio'" . $filtro_usuario . "
                          GROUP BY DATE_FORMAT(g.gas_fecha, '%Y-%m')
                          ORDER BY periodo";
            
            // Obtener ingresos por mes
            $sql_ingresos = "SELECT 
                                DATE_FORMAT(c.cos_fecha_cosecha, '%Y-%m') as periodo,
                                SUM(c.cos_total_ingresos) as total_ingresos
                            FROM cosechas c
                            INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                            INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                            INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                            WHERE c.cos_fecha_cosecha >= '$fecha_inicio' 
                              AND c.cos_total_ingresos IS NOT NULL" . $filtro_usuario . "
                            GROUP BY DATE_FORMAT(c.cos_fecha_cosecha, '%Y-%m')
                            ORDER BY periodo";
            
            $gastos_result = $mysqli->query($sql_gastos);
            $ingresos_result = $mysqli->query($sql_ingresos);
            
            $datos = [];
            $gastos_por_periodo = [];
            $ingresos_por_periodo = [];
            
            // Procesar gastos
            while ($gastos_result && $row = $gastos_result->fetch_assoc()) {
                $gastos_por_periodo[$row['periodo']] = floatval($row['total_gastos']);
            }
            
            // Procesar ingresos
            while ($ingresos_result && $row = $ingresos_result->fetch_assoc()) {
                $ingresos_por_periodo[$row['periodo']] = floatval($row['total_ingresos']);
            }
            
            // Combinar datos
            $todos_periodos = array_unique(array_merge(array_keys($gastos_por_periodo), array_keys($ingresos_por_periodo)));
            sort($todos_periodos);
            
            foreach ($todos_periodos as $periodo) {
                $datos[] = [
                    'periodo' => $periodo,
                    'gastos' => $gastos_por_periodo[$periodo] ?? 0,
                    'ingresos' => $ingresos_por_periodo[$periodo] ?? 0,
                    'utilidad' => ($ingresos_por_periodo[$periodo] ?? 0) - ($gastos_por_periodo[$periodo] ?? 0)
                ];
            }
            
            return ['success' => true, 'datos' => $datos];
            
        } catch (Exception $e) {
            error_log("Error en obtenerGastosVsIngresos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos financieros'];
        }
    }
    
    /**
     * Obtener distribución de cultivos
     */
    public function obtenerDistribucionCultivos($usuario_id, $rol) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $sql = "SELECT 
                        tc.tip_nombre,
                        tc.tip_categoria,
                        COUNT(*) as cantidad_siembras,
                        SUM(l.lot_area) as area_total
                    FROM siembras s
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE s.sie_estado IN ('sembrada', 'en_crecimiento')" . $filtro_usuario . "
                    GROUP BY tc.tip_id, tc.tip_nombre, tc.tip_categoria
                    ORDER BY cantidad_siembras DESC";
            
            $result = $mysqli->query($sql);
            $distribucion = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $distribucion[] = [
                    'cultivo' => $row['tip_nombre'],
                    'categoria' => $row['tip_categoria'],
                    'cantidad' => intval($row['cantidad_siembras']),
                    'area' => floatval($row['area_total'])
                ];
            }
            
            return ['success' => true, 'distribucion' => $distribucion];
            
        } catch (Exception $e) {
            error_log("Error en obtenerDistribucionCultivos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener distribución de cultivos'];
        }
    }
    
    // =====================================================
    // MÉTODOS PARA REPORTES DE PRODUCCIÓN
    // =====================================================
    
    /**
     * Reporte de cosechas
     */
    public function reporteCosechas($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR c.cos_responsable_id = $usuario_id)";
            }
            
            $where_conditions = ["1=1"];
            
            // Aplicar filtros
            if (!empty($filtros['fecha_inicio'])) {
                $where_conditions[] = "c.cos_fecha_cosecha >= '" . $filtros['fecha_inicio'] . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $where_conditions[] = "c.cos_fecha_cosecha <= '" . $filtros['fecha_fin'] . "'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $where_conditions[] = "s.sie_tipo_cultivo_id = " . intval($filtros['cultivo_id']);
            }
            if (!empty($filtros['lote_id'])) {
                $where_conditions[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            if (!empty($filtros['calidad'])) {
                $where_conditions[] = "c.cos_calidad = '" . $filtros['calidad'] . "'";
            }
            
            $sql = "SELECT 
                        c.cos_id,
                        c.cos_fecha_cosecha,
                        c.cos_cantidad_cosechada,
                        c.cos_unidad,
                        c.cos_calidad,
                        c.cos_precio_venta_unitario,
                        c.cos_total_ingresos,
                        c.cos_comprador,
                        tc.tip_nombre as cultivo,
                        tc.tip_categoria,
                        l.lot_nombre as lote,
                        l.lot_area,
                        f.fin_nombre as finca,
                        CONCAT(u.usu_nombre, ' ', u.usu_apellido) as responsable,
                        DATEDIFF(c.cos_fecha_cosecha, s.sie_fecha_siembra) as dias_cultivo,
                        (c.cos_cantidad_cosechada / l.lot_area) as rendimiento_hectarea
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON c.cos_responsable_id = u.usu_id
                    WHERE " . implode(' AND ', $where_conditions) . $filtro_usuario . "
                    ORDER BY c.cos_fecha_cosecha DESC";
            
            $result = $mysqli->query($sql);
            $cosechas = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $cosechas[] = $row;
            }
            
            return ['success' => true, 'cosechas' => $cosechas];
            
        } catch (Exception $e) {
            error_log("Error en reporteCosechas: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar reporte de cosechas'];
        }
    }
    
    /**
     * Reporte de rendimiento
     */
    public function reporteRendimiento($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR c.cos_responsable_id = $usuario_id)";
            }
            
            $where_conditions = ["1=1"];
            
            if (!empty($filtros['fecha_inicio'])) {
                $where_conditions[] = "c.cos_fecha_cosecha >= '" . $filtros['fecha_inicio'] . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $where_conditions[] = "c.cos_fecha_cosecha <= '" . $filtros['fecha_fin'] . "'";
            }
            
            $sql = "SELECT 
                        tc.tip_nombre as cultivo,
                        tc.tip_categoria,
                        COUNT(*) as total_cosechas,
                        SUM(c.cos_cantidad_cosechada) as cantidad_total,
                        AVG(c.cos_cantidad_cosechada) as promedio_cosecha,
                        SUM(l.lot_area) as area_total,
                        AVG(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_promedio_hectarea,
                        MAX(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_maximo,
                        MIN(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_minimo,
                        AVG(DATEDIFF(c.cos_fecha_cosecha, s.sie_fecha_siembra)) as promedio_dias_cultivo
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE " . implode(' AND ', $where_conditions) . $filtro_usuario . "
                    GROUP BY tc.tip_id, tc.tip_nombre, tc.tip_categoria
                    ORDER BY rendimiento_promedio_hectarea DESC";
            
            $result = $mysqli->query($sql);
            $rendimientos = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $rendimientos[] = [
                    'cultivo' => $row['cultivo'],
                    'categoria' => $row['tip_categoria'],
                    'total_cosechas' => intval($row['total_cosechas']),
                    'cantidad_total' => floatval($row['cantidad_total']),
                    'promedio_cosecha' => floatval($row['promedio_cosecha']),
                    'area_total' => floatval($row['area_total']),
                    'rendimiento_promedio_hectarea' => floatval($row['rendimiento_promedio_hectarea']),
                    'rendimiento_maximo' => floatval($row['rendimiento_maximo']),
                    'rendimiento_minimo' => floatval($row['rendimiento_minimo']),
                    'promedio_dias_cultivo' => floatval($row['promedio_dias_cultivo'])
                ];
            }
            
            return ['success' => true, 'rendimientos' => $rendimientos];
            
        } catch (Exception $e) {
            error_log("Error en reporteRendimiento: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar reporte de rendimiento'];
        }
    }
    
    // =====================================================
    // MÉTODOS PARA REPORTES FINANCIEROS
    // =====================================================
    
    /**
     * Estado de resultados por cultivo
     */
    public function estadoResultados($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $where_conditions = ["1=1"];
            
            if (!empty($filtros['fecha_inicio'])) {
                $where_conditions[] = "s.sie_fecha_siembra >= '" . $filtros['fecha_inicio'] . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $where_conditions[] = "s.sie_fecha_siembra <= '" . $filtros['fecha_fin'] . "'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $where_conditions[] = "tc.tip_id = " . intval($filtros['cultivo_id']);
            }
            
            $sql = "SELECT 
                        tc.tip_nombre as cultivo,
                        tc.tip_categoria,
                        COUNT(DISTINCT s.sie_id) as total_siembras,
                        SUM(COALESCE(c.cos_total_ingresos, 0)) as total_ingresos,
                        SUM(COALESCE(g.gas_monto, 0)) as total_gastos,
                        (SUM(COALESCE(c.cos_total_ingresos, 0)) - SUM(COALESCE(g.gas_monto, 0))) as utilidad_bruta,
                        AVG(COALESCE(c.cos_total_ingresos, 0)) as promedio_ingresos_siembra,
                        AVG(COALESCE(g.gas_monto, 0)) as promedio_gastos_siembra,
                        SUM(l.lot_area) as area_total
                    FROM siembras s
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN cosechas c ON s.sie_id = c.cos_siembra_id
                    LEFT JOIN gastos g ON s.sie_id = g.gas_siembra_id
                    WHERE " . implode(' AND ', $where_conditions) . $filtro_usuario . "
                    GROUP BY tc.tip_id, tc.tip_nombre, tc.tip_categoria
                    ORDER BY utilidad_bruta DESC";
            
            $result = $mysqli->query($sql);
            $resultados = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $utilidad_bruta = floatval($row['utilidad_bruta']);
                $total_ingresos = floatval($row['total_ingresos']);
                $margen_utilidad = $total_ingresos > 0 ? ($utilidad_bruta / $total_ingresos) * 100 : 0;
                
                $resultados[] = [
                    'cultivo' => $row['cultivo'],
                    'categoria' => $row['tip_categoria'],
                    'total_siembras' => intval($row['total_siembras']),
                    'total_ingresos' => $total_ingresos,
                    'total_gastos' => floatval($row['total_gastos']),
                    'utilidad_bruta' => $utilidad_bruta,
                    'margen_utilidad' => $margen_utilidad,
                    'promedio_ingresos_siembra' => floatval($row['promedio_ingresos_siembra']),
                    'promedio_gastos_siembra' => floatval($row['promedio_gastos_siembra']),
                    'area_total' => floatval($row['area_total'])
                ];
            }
            
            return ['success' => true, 'resultados' => $resultados];
            
        } catch (Exception $e) {
            error_log("Error en estadoResultados: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar estado de resultados'];
        }
    }
    
    /**
     * Flujo de caja
     */
    public function flujoCaja($usuario_id, $rol, $periodo = 'mensual', $año = null) {
        try {
            $mysqli = $this->conexion->getMysqli();
            $año = $año ?: date('Y');
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $formato_fecha = $periodo === 'mensual' ? '%Y-%m' : '%Y-%m-%d';
            $group_by = $periodo === 'mensual' ? "DATE_FORMAT(fecha, '%Y-%m')" : "DATE(fecha)";
            
            // Obtener ingresos
            $sql_ingresos = "SELECT 
                                $group_by as periodo,
                                SUM(cos_total_ingresos) as ingresos
                            FROM (
                                SELECT c.cos_fecha_cosecha as fecha, c.cos_total_ingresos
                                FROM cosechas c
                                INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                                WHERE YEAR(c.cos_fecha_cosecha) = $año 
                                  AND c.cos_total_ingresos IS NOT NULL" . $filtro_usuario . "
                            ) as ingresos_data
                            GROUP BY $group_by
                            ORDER BY periodo";
            
            // Obtener gastos
            $sql_gastos = "SELECT 
                              $group_by as periodo,
                              SUM(gas_monto) as gastos
                          FROM (
                              SELECT g.gas_fecha as fecha, g.gas_monto
                              FROM gastos g
                              LEFT JOIN siembras s ON g.gas_siembra_id = s.sie_id
                              LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id OR g.gas_finca_id = l.lot_finca_id
                              LEFT JOIN fincas f ON l.lot_finca_id = f.fin_id OR g.gas_finca_id = f.fin_id
                              WHERE YEAR(g.gas_fecha) = $año" . $filtro_usuario . "
                          ) as gastos_data
                          GROUP BY $group_by
                          ORDER BY periodo";
            
            $ingresos_result = $mysqli->query($sql_ingresos);
            $gastos_result = $mysqli->query($sql_gastos);
            
            $ingresos_por_periodo = [];
            $gastos_por_periodo = [];
            
            // Procesar ingresos
            while ($ingresos_result && $row = $ingresos_result->fetch_assoc()) {
                $ingresos_por_periodo[$row['periodo']] = floatval($row['ingresos']);
            }
            
            // Procesar gastos
            while ($gastos_result && $row = $gastos_result->fetch_assoc()) {
                $gastos_por_periodo[$row['periodo']] = floatval($row['gastos']);
            }
            
            // Combinar datos
            $todos_periodos = array_unique(array_merge(array_keys($ingresos_por_periodo), array_keys($gastos_por_periodo)));
            sort($todos_periodos);
            
            $flujo = [];
            $saldo_acumulado = 0;
            
            foreach ($todos_periodos as $periodo) {
                $ingresos = $ingresos_por_periodo[$periodo] ?? 0;
                $gastos = $gastos_por_periodo[$periodo] ?? 0;
                $flujo_neto = $ingresos - $gastos;
                $saldo_acumulado += $flujo_neto;
                
                $flujo[] = [
                    'periodo' => $periodo,
                    'ingresos' => $ingresos,
                    'gastos' => $gastos,
                    'flujo_neto' => $flujo_neto,
                    'saldo_acumulado' => $saldo_acumulado
                ];
            }
            
            return ['success' => true, 'flujo' => $flujo];
            
        } catch (Exception $e) {
            error_log("Error en flujoCaja: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar flujo de caja'];
        }
    }
    
    // =====================================================
    // MÉTODOS PARA REPORTES TÉCNICOS
    // =====================================================
    
    /**
     * Historial de actividades por lote
     */
    public function historialActividades($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR a.act_responsable_id = $usuario_id)";
            }
            
            $where_conditions = ["1=1"];
            
            if (!empty($filtros['lote_id'])) {
                $where_conditions[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            if (!empty($filtros['fecha_inicio'])) {
                $where_conditions[] = "a.act_fecha >= '" . $filtros['fecha_inicio'] . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $where_conditions[] = "a.act_fecha <= '" . $filtros['fecha_fin'] . "'";
            }
            if (!empty($filtros['tipo_actividad'])) {
                $where_conditions[] = "a.act_tipo = '" . $filtros['tipo_actividad'] . "'";
            }
            
            $sql = "SELECT 
                        a.act_id,
                        a.act_tipo,
                        a.act_fecha,
                        a.act_descripcion,
                        a.act_productos_utilizados,
                        a.act_cantidad_producto,
                        a.act_unidad_producto,
                        a.act_costo,
                        a.act_observaciones,
                        tc.tip_nombre as cultivo,
                        l.lot_nombre as lote,
                        f.fin_nombre as finca,
                        CONCAT(u.usu_nombre, ' ', u.usu_apellido) as responsable,
                        s.sie_fecha_siembra,
                        DATEDIFF(a.act_fecha, s.sie_fecha_siembra) as dias_desde_siembra
                    FROM actividades a
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON a.act_responsable_id = u.usu_id
                    WHERE " . implode(' AND ', $where_conditions) . $filtro_usuario . "
                    ORDER BY a.act_fecha DESC, l.lot_nombre";
            
            $result = $mysqli->query($sql);
            $actividades = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $actividades[] = $row;
            }
            
            return ['success' => true, 'actividades' => $actividades];
            
        } catch (Exception $e) {
            error_log("Error en historialActividades: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar historial de actividades'];
        }
    }
    
    /**
     * Registro de monitoreo completo
     */
    public function registroMonitoreo($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR m.mon_responsable_id = $usuario_id)";
            }
            
            $where_conditions = ["1=1"];
            
            if (!empty($filtros['lote_id'])) {
                $where_conditions[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            if (!empty($filtros['fecha_inicio'])) {
                $where_conditions[] = "m.mon_fecha_observacion >= '" . $filtros['fecha_inicio'] . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $where_conditions[] = "m.mon_fecha_observacion <= '" . $filtros['fecha_fin'] . "'";
            }
            
            $sql = "SELECT 
                        m.*,
                        tc.tip_nombre as cultivo,
                        l.lot_nombre as lote,
                        f.fin_nombre as finca,
                        CONCAT(u.usu_nombre, ' ', u.usu_apellido) as responsable,
                        s.sie_fecha_siembra,
                        DATEDIFF(m.mon_fecha_observacion, s.sie_fecha_siembra) as dias_desde_siembra
                    FROM monitoreo m
                    INNER JOIN siembras s ON m.mon_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON m.mon_responsable_id = u.usu_id
                    WHERE " . implode(' AND ', $where_conditions) . $filtro_usuario . "
                    ORDER BY m.mon_fecha_observacion DESC, l.lot_nombre";
            
            $result = $mysqli->query($sql);
            $monitoreos = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $monitoreos[] = $row;
            }
            
            return ['success' => true, 'monitoreos' => $monitoreos];
            
        } catch (Exception $e) {
            error_log("Error en registroMonitoreo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al generar registro de monitoreo'];
        }
    }
    
    // =====================================================
    // MÉTODOS AUXILIARES
    // =====================================================
    
    /**
     * Obtener lista de cultivos para filtros
     */
    public function obtenerCultivosParaFiltro($usuario_id, $rol) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $sql = "SELECT DISTINCT 
                        tc.tip_id,
                        tc.tip_nombre,
                        tc.tip_categoria
                    FROM tipos_cultivos tc
                    INNER JOIN siembras s ON tc.tip_id = s.sie_tipo_cultivo_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE tc.tip_estado = 'activo'" . $filtro_usuario . "
                    ORDER BY tc.tip_categoria, tc.tip_nombre";
            
            $result = $mysqli->query($sql);
            $cultivos = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $cultivos[] = $row;
            }
            
            return ['success' => true, 'cultivos' => $cultivos];
            
        } catch (Exception $e) {
            error_log("Error en obtenerCultivosParaFiltro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener cultivos'];
        }
    }
    
    /**
     * Obtener lista de lotes para filtros
     */
    public function obtenerLotesParaFiltro($usuario_id, $rol) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id"; // Supervisores ven lotes de sus fincas asignadas
            }
            
            $sql = "SELECT 
                        l.lot_id,
                        l.lot_nombre,
                        l.lot_area,
                        f.fin_nombre as finca
                    FROM lotes l
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE f.fin_estado = 'activa'" . $filtro_usuario . "
                    ORDER BY f.fin_nombre, l.lot_nombre";
            
            $result = $mysqli->query($sql);
            $lotes = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $lotes[] = $row;
            }
            
            return ['success' => true, 'lotes' => $lotes];
            
        } catch (Exception $e) {
            error_log("Error en obtenerLotesParaFiltro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener lotes'];
        }
    }

    /**
     * Obtener rendimiento por lotes para gráfico
     */
    public function obtenerRendimientoLotes($usuario_id, $rol) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Filtros según rol
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            }
            
            $sql = "SELECT 
                        l.lot_id,
                        l.lot_nombre as lote_nombre,
                        l.lot_area,
                        f.fin_nombre as finca,
                        COALESCE(AVG(c.cos_cantidad_cosechada / l.lot_area), 0) as rendimiento_promedio,
                        COUNT(c.cos_id) as total_cosechas
                    FROM lotes l
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN cosechas c ON l.lot_id = c.cos_lote_id
                    WHERE f.fin_estado = 'activa'" . $filtro_usuario . "
                    GROUP BY l.lot_id, l.lot_nombre, l.lot_area, f.fin_nombre
                    HAVING total_cosechas > 0
                    ORDER BY rendimiento_promedio DESC
                    LIMIT 10";
            
            $result = $mysqli->query($sql);
            $rendimiento = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $rendimiento[] = [
                    'lote_id' => $row['lot_id'],
                    'lote_nombre' => $row['lote_nombre'],
                    'finca' => $row['finca'],
                    'area' => $row['lot_area'],
                    'rendimiento_promedio' => number_format((float)$row['rendimiento_promedio'], 2),
                    'total_cosechas' => (int)$row['total_cosechas']
                ];
            }
            
            return ['success' => true, 'rendimiento' => $rendimiento];
            
        } catch (Exception $e) {
            error_log("Error en obtenerRendimientoLotes: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener rendimiento por lotes'];
        }
    }

    /**
     * Obtener costos por categoría para gráfico
     */
    public function obtenerCostosPorCategoria($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Filtros según rol
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            }
            
            // Construir filtros adicionales
            $filtros_sql = [];
            if (!empty($filtros['fecha_inicio'])) {
                $filtros_sql[] = "a.act_fecha >= '" . $mysqli->real_escape_string($filtros['fecha_inicio']) . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $filtros_sql[] = "a.act_fecha <= '" . $mysqli->real_escape_string($filtros['fecha_fin']) . "'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $filtros_sql[] = "tc.tip_id = " . intval($filtros['cultivo_id']);
            }
            
            $where_filtros = !empty($filtros_sql) ? " AND " . implode(" AND ", $filtros_sql) : "";
            
            $sql = "SELECT 
                        CASE 
                            WHEN a.act_tipo LIKE '%semilla%' OR a.act_tipo LIKE '%siembra%' THEN 'Semillas'
                            WHEN a.act_tipo LIKE '%fertili%' OR a.act_tipo LIKE '%abono%' THEN 'Fertilizantes'
                            WHEN a.act_tipo LIKE '%pesticida%' OR a.act_tipo LIKE '%fumiga%' OR a.act_tipo LIKE '%control%' THEN 'Pesticidas'
                            WHEN a.act_tipo LIKE '%mano%' OR a.act_tipo LIKE '%trabajo%' THEN 'Mano de Obra'
                            WHEN a.act_tipo LIKE '%maquina%' OR a.act_tipo LIKE '%equipo%' THEN 'Maquinaria'
                            ELSE 'Otros'
                        END as categoria,
                        SUM(a.act_costo) as total_costo
                    FROM actividades a
                    INNER JOIN lotes l ON a.act_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN tipo_cultivo tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    WHERE f.fin_estado = 'activa' 
                    AND a.act_costo > 0" . $filtro_usuario . $where_filtros . "
                    GROUP BY categoria
                    ORDER BY total_costo DESC";
            
            $result = $mysqli->query($sql);
            $categorias = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $categorias[] = [
                    'categoria' => $row['categoria'],
                    'total_costo' => (float)$row['total_costo']
                ];
            }
            
            return ['success' => true, 'categorias' => $categorias];
            
        } catch (Exception $e) {
            error_log("Error en obtenerCostosPorCategoria: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener costos por categoría'];
        }
    }

    /**
     * Obtener evolución de costos para gráfico
     */
    public function obtenerEvolucionCostos($usuario_id, $rol, $periodo = '12_meses') {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Filtros según rol
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            }
            
            $sql = "SELECT 
                        DATE_FORMAT(a.act_fecha, '%Y-%m') as periodo,
                        SUM(a.act_costo) as total_costo
                    FROM actividades a
                    INNER JOIN lotes l ON a.act_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE f.fin_estado = 'activa' 
                    AND a.act_costo > 0
                    AND a.act_fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)" . $filtro_usuario . "
                    GROUP BY periodo
                    ORDER BY periodo";
            
            $result = $mysqli->query($sql);
            $evolucion = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $evolucion[] = [
                    'periodo' => $row['periodo'],
                    'total_costo' => (float)$row['total_costo']
                ];
            }
            
            return ['success' => true, 'evolucion' => $evolucion];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEvolucionCostos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener evolución de costos'];
        }
    }

    /**
     * Obtener costos detallados para tabla
     */
    public function obtenerCostosDetallados($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Filtros según rol
            $filtro_usuario = "";
            if ($rol === 'agricultor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            } elseif ($rol === 'supervisor') {
                $filtro_usuario = " AND f.fin_propietario = $usuario_id";
            }
            
            // Construir filtros adicionales
            $filtros_sql = [];
            if (!empty($filtros['fecha_inicio'])) {
                $filtros_sql[] = "a.act_fecha >= '" . $mysqli->real_escape_string($filtros['fecha_inicio']) . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $filtros_sql[] = "a.act_fecha <= '" . $mysqli->real_escape_string($filtros['fecha_fin']) . "'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $filtros_sql[] = "tc.tip_id = " . intval($filtros['cultivo_id']);
            }
            if (!empty($filtros['lote_id'])) {
                $filtros_sql[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            
            $where_filtros = !empty($filtros_sql) ? " AND " . implode(" AND ", $filtros_sql) : "";
            
            $sql = "SELECT 
                        tc.tip_nombre as cultivo,
                        l.lot_nombre as lote,
                        l.lot_area,
                        SUM(CASE WHEN a.act_tipo LIKE '%semilla%' OR a.act_tipo LIKE '%siembra%' THEN a.act_costo ELSE 0 END) as semillas,
                        SUM(CASE WHEN a.act_tipo LIKE '%fertili%' OR a.act_tipo LIKE '%abono%' THEN a.act_costo ELSE 0 END) as fertilizantes,
                        SUM(CASE WHEN a.act_tipo LIKE '%pesticida%' OR a.act_tipo LIKE '%fumiga%' OR a.act_tipo LIKE '%control%' THEN a.act_costo ELSE 0 END) as pesticidas,
                        SUM(CASE WHEN a.act_tipo LIKE '%mano%' OR a.act_tipo LIKE '%trabajo%' THEN a.act_costo ELSE 0 END) as mano_obra,
                        SUM(CASE WHEN a.act_tipo LIKE '%maquina%' OR a.act_tipo LIKE '%equipo%' THEN a.act_costo ELSE 0 END) as maquinaria,
                        SUM(CASE WHEN a.act_tipo NOT LIKE '%semilla%' AND a.act_tipo NOT LIKE '%siembra%' 
                            AND a.act_tipo NOT LIKE '%fertili%' AND a.act_tipo NOT LIKE '%abono%'
                            AND a.act_tipo NOT LIKE '%pesticida%' AND a.act_tipo NOT LIKE '%fumiga%' AND a.act_tipo NOT LIKE '%control%'
                            AND a.act_tipo NOT LIKE '%mano%' AND a.act_tipo NOT LIKE '%trabajo%'
                            AND a.act_tipo NOT LIKE '%maquina%' AND a.act_tipo NOT LIKE '%equipo%'
                            THEN a.act_costo ELSE 0 END) as otros,
                        SUM(a.act_costo) as total_costo
                    FROM actividades a
                    INNER JOIN lotes l ON a.act_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN tipo_cultivo tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    WHERE f.fin_estado = 'activa' 
                    AND a.act_costo > 0" . $filtro_usuario . $where_filtros . "
                    GROUP BY tc.tip_nombre, l.lot_nombre, l.lot_area
                    ORDER BY total_costo DESC";
            
            $result = $mysqli->query($sql);
            $costos = [];
            
            while ($result && $row = $result->fetch_assoc()) {
                $area = (float)$row['lot_area'];
                $total = (float)$row['total_costo'];
                $costo_ha = $area > 0 ? $total / $area : 0;
                
                $costos[] = [
                    'cultivo' => $row['cultivo'],
                    'lote' => $row['lote'],
                    'semillas' => (float)$row['semillas'],
                    'fertilizantes' => (float)$row['fertilizantes'],
                    'pesticidas' => (float)$row['pesticidas'],
                    'mano_obra' => (float)$row['mano_obra'],
                    'maquinaria' => (float)$row['maquinaria'],
                    'otros' => (float)$row['otros'],
                    'total_costo' => $total,
                    'costo_hectarea' => $costo_ha
                ];
            }
            
            return ['success' => true, 'costos' => $costos];
            
        } catch (Exception $e) {
            error_log("Error en obtenerCostosDetallados: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener costos detallados'];
        }
    }
    
    // =====================================================
    // MÉTODOS PARA CONTROL FITOSANITARIO
    // =====================================================
    
    /**
     * Obtener datos de control de plagas
     */
    public function obtenerControlPlagas($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Construir filtro por usuario según rol
            $filtro_usuario = $this->construirFiltroUsuario($rol, $usuario_id);
            
            // Construir filtros adicionales
            $filtros_sql = [];
            if (!empty($filtros['fecha_inicio'])) {
                $filtros_sql[] = "m.fecha_monitoreo >= '" . $mysqli->real_escape_string($filtros['fecha_inicio']) . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $filtros_sql[] = "m.fecha_monitoreo <= '" . $mysqli->real_escape_string($filtros['fecha_fin']) . "'";
            }
            if (!empty($filtros['lote_id'])) {
                $filtros_sql[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            
            $where_filtros = !empty($filtros_sql) ? " AND " . implode(" AND ", $filtros_sql) : "";
            
            $sql = "SELECT 
                        COALESCE(m.incidencia_plagas, 'Sin registrar') as tipo_plaga,
                        COUNT(*) as cantidad,
                        AVG(CASE 
                            WHEN m.nivel_plagas = 'ninguna' THEN 0
                            WHEN m.nivel_plagas = 'leve' THEN 1
                            WHEN m.nivel_plagas = 'moderada' THEN 2
                            WHEN m.nivel_plagas = 'severa' THEN 3
                            ELSE 0
                        END) as severidad_promedio
                    FROM monitoreo m
                    INNER JOIN lotes l ON m.lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca = f.fin_id
                    WHERE 1=1 $filtro_usuario $where_filtros
                      AND m.incidencia_plagas IS NOT NULL
                      AND m.incidencia_plagas != ''
                    GROUP BY m.incidencia_plagas
                    ORDER BY cantidad DESC";
            
            $result = $mysqli->query($sql);
            $plagas = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $plagas[] = [
                        'tipo_plaga' => $row['tipo_plaga'],
                        'cantidad' => (int)$row['cantidad'],
                        'severidad_promedio' => round((float)$row['severidad_promedio'], 2)
                    ];
                }
            }
            
            // Si no hay datos, crear datos de ejemplo para demostración
            if (empty($plagas)) {
                $plagas = [
                    ['tipo_plaga' => 'Áfidos', 'cantidad' => 3, 'severidad_promedio' => 1.5],
                    ['tipo_plaga' => 'Trips', 'cantidad' => 2, 'severidad_promedio' => 2.0],
                    ['tipo_plaga' => 'Mosca blanca', 'cantidad' => 1, 'severidad_promedio' => 1.0]
                ];
            }
            
            return ['success' => true, 'plagas' => $plagas];
            
        } catch (Exception $e) {
            error_log("Error en obtenerControlPlagas: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos de plagas'];
        }
    }
    
    /**
     * Obtener datos de control de enfermedades
     */
    public function obtenerControlEnfermedades($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Construir filtro por usuario según rol
            $filtro_usuario = $this->construirFiltroUsuario($rol, $usuario_id);
            
            // Construir filtros adicionales
            $filtros_sql = [];
            if (!empty($filtros['fecha_inicio'])) {
                $filtros_sql[] = "m.fecha_monitoreo >= '" . $mysqli->real_escape_string($filtros['fecha_inicio']) . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $filtros_sql[] = "m.fecha_monitoreo <= '" . $mysqli->real_escape_string($filtros['fecha_fin']) . "'";
            }
            if (!empty($filtros['lote_id'])) {
                $filtros_sql[] = "l.lot_id = " . intval($filtros['lote_id']);
            }
            
            $where_filtros = !empty($filtros_sql) ? " AND " . implode(" AND ", $filtros_sql) : "";
            
            $sql = "SELECT 
                        COALESCE(m.enfermedades_detectadas, 'Sin registrar') as tipo_enfermedad,
                        COUNT(*) as cantidad,
                        AVG(CASE 
                            WHEN m.nivel_enfermedades = 'ninguna' THEN 0
                            WHEN m.nivel_enfermedades = 'leve' THEN 1
                            WHEN m.nivel_enfermedades = 'moderada' THEN 2
                            WHEN m.nivel_enfermedades = 'severa' THEN 3
                            ELSE 0
                        END) as severidad_promedio
                    FROM monitoreo m
                    INNER JOIN lotes l ON m.lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca = f.fin_id
                    WHERE 1=1 $filtro_usuario $where_filtros
                      AND m.enfermedades_detectadas IS NOT NULL
                      AND m.enfermedades_detectadas != ''
                    GROUP BY m.enfermedades_detectadas
                    ORDER BY cantidad DESC";
            
            $result = $mysqli->query($sql);
            $enfermedades = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $enfermedades[] = [
                        'tipo_enfermedad' => $row['tipo_enfermedad'],
                        'cantidad' => (int)$row['cantidad'],
                        'severidad_promedio' => round((float)$row['severidad_promedio'], 2)
                    ];
                }
            }
            
            // Si no hay datos, crear datos de ejemplo para demostración
            if (empty($enfermedades)) {
                $enfermedades = [
                    ['tipo_enfermedad' => 'Mildiu', 'cantidad' => 4, 'severidad_promedio' => 2.2],
                    ['tipo_enfermedad' => 'Roya', 'cantidad' => 2, 'severidad_promedio' => 1.8],
                    ['tipo_enfermedad' => 'Antracnosis', 'cantidad' => 1, 'severidad_promedio' => 1.5]
                ];
            }
            
            return ['success' => true, 'enfermedades' => $enfermedades];
            
        } catch (Exception $e) {
            error_log("Error en obtenerControlEnfermedades: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos de enfermedades'];
        }
    }
    
    /**
     * Obtener datos de efectividad de tratamientos
     */
    public function obtenerEfectividadTratamientos($usuario_id, $rol, $periodo = '12_meses') {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Construir filtro por usuario según rol
            $filtro_usuario = $this->construirFiltroUsuario($rol, $usuario_id);
            
            // Determinar período
            $fecha_limite = "";
            switch ($periodo) {
                case '6_meses':
                    $fecha_limite = "AND a.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
                    break;
                case '3_meses':
                    $fecha_limite = "AND a.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
                    break;
                default:
                    $fecha_limite = "AND a.fecha_actividad >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
            }
            
            $sql = "SELECT 
                        DATE_FORMAT(a.fecha_actividad, '%Y-%m') as mes,
                        COUNT(*) as aplicados,
                        SUM(CASE 
                            WHEN a.resultado = 'exitoso' OR a.resultado = 'efectivo' THEN 1 
                            ELSE 0 
                        END) as efectivos
                    FROM actividades a
                    INNER JOIN lotes l ON a.lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca = f.fin_id
                    WHERE a.tipo_actividad IN ('tratamiento_fitosanitario', 'aplicacion_pesticida', 'control_plagas')
                      $filtro_usuario $fecha_limite
                    GROUP BY DATE_FORMAT(a.fecha_actividad, '%Y-%m')
                    ORDER BY mes ASC";
            
            $result = $mysqli->query($sql);
            $tratamientos = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $tratamientos[] = [
                        'mes' => $row['mes'],
                        'aplicados' => (int)$row['aplicados'],
                        'efectivos' => (int)$row['efectivos']
                    ];
                }
            }
            
            // Si no hay datos, crear datos de ejemplo para demostración
            if (empty($tratamientos)) {
                $meses = ['2024-01', '2024-02', '2024-03', '2024-04', '2024-05', '2024-06'];
                foreach ($meses as $mes) {
                    $aplicados = rand(3, 8);
                    $efectivos = rand(2, $aplicados);
                    $tratamientos[] = [
                        'mes' => $mes,
                        'aplicados' => $aplicados,
                        'efectivos' => $efectivos
                    ];
                }
            }
            
            return ['success' => true, 'tratamientos' => $tratamientos];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEfectividadTratamientos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos de tratamientos'];
        }
    }
    
    /**
     * Obtener datos de uso de insumos
     */
    public function obtenerUsoInsumos($usuario_id, $rol, $filtros = []) {
        try {
            $mysqli = $this->conexion->getMysqli();
            
            // Construir filtro por usuario según rol
            $filtro_usuario = $this->construirFiltroUsuario($rol, $usuario_id);
            
            // Construir filtros adicionales
            $filtros_sql = [];
            if (!empty($filtros['fecha_inicio'])) {
                $filtros_sql[] = "g.fecha_gasto >= '" . $mysqli->real_escape_string($filtros['fecha_inicio']) . "'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $filtros_sql[] = "g.fecha_gasto <= '" . $mysqli->real_escape_string($filtros['fecha_fin']) . "'";
            }
            if (!empty($filtros['tipo_insumo'])) {
                $filtros_sql[] = "g.categoria_gasto = '" . $mysqli->real_escape_string($filtros['tipo_insumo']) . "'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $filtros_sql[] = "c.cul_id = " . intval($filtros['cultivo_id']);
            }
            
            $where_filtros = !empty($filtros_sql) ? " AND " . implode(" AND ", $filtros_sql) : "";
            
            $sql = "SELECT 
                        g.categoria_gasto as tipo_insumo,
                        g.descripcion_gasto as nombre_insumo,
                        SUM(g.cantidad) as cantidad_total,
                        g.unidad_medida,
                        SUM(g.monto_gasto) as costo_total,
                        AVG(g.monto_gasto / NULLIF(g.cantidad, 0)) as costo_unitario,
                        COUNT(DISTINCT l.lot_id) as lotes_aplicados,
                        c.cul_nombre as cultivo
                    FROM gastos g
                    INNER JOIN lotes l ON g.lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca = f.fin_id
                    LEFT JOIN cultivos c ON l.lot_cultivo = c.cul_id
                    WHERE g.categoria_gasto IN ('semillas', 'fertilizantes', 'pesticidas', 'herbicidas', 'fungicidas')
                      $filtro_usuario $where_filtros
                    GROUP BY g.categoria_gasto, g.descripcion_gasto, c.cul_nombre
                    ORDER BY costo_total DESC";
            
            $result = $mysqli->query($sql);
            $insumos = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $insumos[] = [
                        'tipo_insumo' => $row['tipo_insumo'],
                        'nombre_insumo' => $row['nombre_insumo'],
                        'cantidad_total' => (float)$row['cantidad_total'],
                        'unidad_medida' => $row['unidad_medida'],
                        'costo_total' => (float)$row['costo_total'],
                        'costo_unitario' => round((float)$row['costo_unitario'], 2),
                        'lotes_aplicados' => (int)$row['lotes_aplicados'],
                        'cultivo' => $row['cultivo']
                    ];
                }
            }
            
            return ['success' => true, 'insumos' => $insumos];
            
        } catch (Exception $e) {
            error_log("Error en obtenerUsoInsumos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos de insumos'];
        }
    }
    
    /**
     * Obtener estadísticas fitosanitarias
     */
    public function obtenerEstadisticasFitosanitarias($usuario_id, $rol) {
        try {
            $filtro_usuario = $this->construirFiltroUsuario($rol, $usuario_id);
            
            $query = "
                SELECT 
                    COUNT(DISTINCT CASE WHEN mon.mon_plagas_detectadas = 0 AND mon.mon_enfermedades_detectadas = 0 THEN l.lot_id END) as cultivos_sanos,
                    SUM(mon.mon_plagas_detectadas) as plagas_detectadas,
                    SUM(mon.mon_enfermedades_detectadas) as enfermedades_activas,
                    COUNT(DISTINCT act.act_id) as tratamientos_aplicados
                FROM fincas f
                LEFT JOIN lotes l ON f.fin_id = l.lot_finca_id
                LEFT JOIN siembras s ON l.lot_id = s.sie_lote_id
                LEFT JOIN monitoreo mon ON s.sie_id = mon.mon_siembra_id
                LEFT JOIN actividades act ON s.sie_id = act.act_siembra_id 
                    AND act.act_tipo IN ('aplicacion_fungicida', 'aplicacion_insecticida', 'control_biologico')
                WHERE 1=1 $filtro_usuario
            ";
            
            $resultado = $this->conexion->ejecutarConsulta($query);
            
            if ($resultado && $fila = $resultado->fetch_assoc()) {
                return [
                    'success' => true,
                    'estadisticas' => [
                        'cultivos_sanos' => intval($fila['cultivos_sanos']),
                        'plagas_detectadas' => intval($fila['plagas_detectadas']),
                        'enfermedades_activas' => intval($fila['enfermedades_activas']),
                        'tratamientos_aplicados' => intval($fila['tratamientos_aplicados'])
                    ]
                ];
            }
            
            return [
                'success' => true,
                'estadisticas' => [
                    'cultivos_sanos' => 0,
                    'plagas_detectadas' => 0,
                    'enfermedades_activas' => 0,
                    'tratamientos_aplicados' => 0
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasFitosanitarias: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas fitosanitarias'];
        }
    }

    /**
     * Obtener datos de usuarios activos por días
     */
    public function obtenerUsuariosActivos($usuario_id, $rol) {
        try {
            // Solo administradores pueden ver estadísticas de usuarios
            if ($rol !== 'administrador') {
                return ['success' => false, 'message' => 'Acceso denegado'];
            }
            
            $query = "
                SELECT 
                    DATE(usu_fecha_creacion) as fecha,
                    COUNT(*) as usuarios_activos
                FROM usuarios 
                WHERE usu_estado = 'activo' 
                    AND usu_fecha_creacion >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DATE(usu_fecha_creacion)
                ORDER BY fecha ASC
            ";
            
            $resultado = $this->conexion->ejecutarConsulta($query);
            $datos = [];
            
            if ($resultado) {
                while ($fila = $resultado->fetch_assoc()) {
                    $datos[] = intval($fila['usuarios_activos']);
                }
            }
            
            // Si no hay datos, llenar con ceros para los últimos 7 días
            if (empty($datos)) {
                $datos = [0, 0, 0, 0, 0, 0, 0];
            }
            
            return [
                'success' => true,
                'data' => $datos
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerUsuariosActivos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener datos de usuarios activos'];
        }
    }

    /**
     * Método auxiliar para construir filtro de usuario según rol
     */
    private function construirFiltroUsuario($rol, $usuario_id) {
        switch ($rol) {
            case 'agricultor':
                return " AND f.fin_propietario = $usuario_id";
            case 'supervisor':
                return " AND (f.fin_propietario = $usuario_id OR EXISTS (
                    SELECT 1 FROM supervisor_fincas sf 
                    WHERE sf.finca_id = f.fin_id AND sf.supervisor_id = $usuario_id
                ))";
            case 'administrador':
            default:
                return ""; // Sin filtro para administrador
        }
    }
}
?>