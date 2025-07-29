<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log'); 
require_once(__DIR__ . '/../CONFIG/Conexion.php');


class Cosecha {
    private $conexion;
    
    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Cosecha: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function listarCosechas($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        c.cos_id,
                        c.cos_fecha_cosecha,
                        c.cos_cantidad_cosechada,
                        c.cos_unidad,
                        c.cos_calidad,
                        c.cos_precio_venta_unitario,
                        c.cos_comprador,
                        c.cos_total_ingresos,
                        c.cos_observaciones,
                        c.cos_fecha_registro,
                        s.sie_id,
                        s.sie_fecha_siembra,
                        s.sie_estado as siembra_estado,
                        l.lot_id,
                        l.lot_nombre,
                        l.lot_area,
                        f.fin_id,
                        f.fin_nombre,
                        f.fin_propietario,
                        tc.tip_id as cul_id,
                        tc.tip_nombre as cul_nombre,
                        u.usu_id as responsable_id,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido,
                        up.usu_nombre as propietario_nombre,
                        up.usu_apellido as propietario_apellido
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    LEFT JOIN usuarios u ON c.cos_responsable_id = u.usu_id
                    LEFT JOIN usuarios up ON f.fin_propietario = up.usu_id";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " WHERE f.fin_propietario = " . intval($usuario_id);
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " WHERE (f.fin_propietario = " . intval($usuario_id) . " OR c.cos_responsable_id = " . intval($usuario_id) . ")";
            }
            // Administrador ve todas las cosechas
            
            $sql .= " ORDER BY c.cos_fecha_cosecha DESC";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $cosechas = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $cosechas[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'cosechas' => $cosechas,
                    'message' => 'Cosechas obtenidas correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en listarCosechas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener las cosechas: ' . $e->getMessage()
            ];
        }
    }
    
    public function obtenerCosecha($cosecha_id, $usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        c.*,
                        s.sie_id,
                        s.sie_fecha_siembra,
                        s.sie_estado as siembra_estado,
                        s.sie_fecha_estimada_cosecha,
                        l.lot_id,
                        l.lot_nombre,
                        l.lot_area,
                        f.fin_id,
                        f.fin_nombre,
                        f.fin_propietario,
                        tc.tip_id as cul_id,
                        tc.tip_nombre as cul_nombre,
                        tc.tip_categoria as cul_categoria,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido,
                        up.usu_nombre as propietario_nombre,
                        up.usu_apellido as propietario_apellido
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    LEFT JOIN usuarios u ON c.cos_responsable_id = u.usu_id
                    LEFT JOIN usuarios up ON f.fin_propietario = up.usu_id
                    WHERE c.cos_id = " . intval($cosecha_id);
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = " . intval($usuario_id);
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = " . intval($usuario_id) . " OR c.cos_responsable_id = " . intval($usuario_id) . ")";
            }
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $cosecha = $resultado->fetch_assoc();
                $resultado->free();
                
                if ($cosecha) {
                    return [
                        'success' => true,
                        'cosecha' => $cosecha,
                        'message' => 'Cosecha obtenida correctamente'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cosecha no encontrada o sin permisos'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerCosecha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener la cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    public function crearCosecha($datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar que la siembra existe y el usuario tiene permisos
            $verificar_sql = "SELECT s.sie_id, f.fin_propietario 
                            FROM siembras s
                            INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                            INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                            WHERE s.sie_id = " . intval($datos['siembra_id']);
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = " . intval($usuario_id);
            }
            
            $resultado_verificar = $this->conexion->ejecutarSP($verificar_sql);
            
            if (!$resultado_verificar) {
                return [
                    'success' => false,
                    'message' => 'Error al verificar la siembra'
                ];
            }
            
            $siembra = $resultado_verificar->fetch_assoc();
            $resultado_verificar->free();
            
            if (!$siembra) {
                return [
                    'success' => false,
                    'message' => 'Siembra no encontrada o sin permisos para crear cosecha'
                ];
            }
            
            // Escapar datos para prevenir inyección SQL
            $siembra_id = intval($datos['siembra_id']);
            $fecha_cosecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha_cosecha']);
            $cantidad_cosechada = floatval($datos['cantidad_cosechada']);
            $unidad = $this->conexion->getMysqli()->real_escape_string($datos['unidad']);
            $calidad = $this->conexion->getMysqli()->real_escape_string($datos['calidad']);
            $precio_venta_unitario = $datos['precio_venta_unitario'] ? floatval($datos['precio_venta_unitario']) : 'NULL';
            $comprador = $datos['comprador'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['comprador']) . "'" : 'NULL';
            $total_ingresos = $datos['total_ingresos'] ? floatval($datos['total_ingresos']) : 'NULL';
            $observaciones = $datos['observaciones'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) . "'" : 'NULL';
            
            // Insertar la cosecha
            $sql = "INSERT INTO cosechas (
                        cos_siembra_id,
                        cos_fecha_cosecha,
                        cos_cantidad_cosechada,
                        cos_unidad,
                        cos_calidad,
                        cos_precio_venta_unitario,
                        cos_comprador,
                        cos_total_ingresos,
                        cos_responsable_id,
                        cos_observaciones
                    ) VALUES (
                        $siembra_id,
                        '$fecha_cosecha',
                        $cantidad_cosechada,
                        '$unidad',
                        '$calidad',
                        $precio_venta_unitario,
                        $comprador,
                        $total_ingresos,
                        $usuario_id,
                        $observaciones
                    )";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $cosecha_id = $this->conexion->getMysqli()->insert_id;
                
                // Actualizar estado de la siembra a 'cosechada'
                $update_sql = "UPDATE siembras SET sie_estado = 'cosechada' WHERE sie_id = $siembra_id";
                $this->conexion->ejecutarSP($update_sql);
                
                return [
                    'success' => true,
                    'cosecha_id' => $cosecha_id,
                    'message' => 'Cosecha registrada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar la cosecha'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en crearCosecha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear la cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    public function actualizarCosecha($cosecha_id, $datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            $verificar_sql = "SELECT c.cos_id, f.fin_propietario, c.cos_responsable_id
                            FROM cosechas c
                            INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                            INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                            INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                            WHERE c.cos_id = $cosecha_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = " . intval($usuario_id);
            } elseif ($rol_usuario == 'supervisor') {
                $verificar_sql .= " AND (f.fin_propietario = " . intval($usuario_id) . " OR c.cos_responsable_id = " . intval($usuario_id) . ")";
            }
            
            $resultado_verificar = $this->conexion->ejecutarSP($verificar_sql);
            
            if (!$resultado_verificar) {
                return [
                    'success' => false,
                    'message' => 'Error al verificar permisos'
                ];
            }
            
            $cosecha = $resultado_verificar->fetch_assoc();
            $resultado_verificar->free();
            
            if (!$cosecha) {
                return [
                    'success' => false,
                    'message' => 'Cosecha no encontrada o sin permisos para editar'
                ];
            }
            
            // Escapar datos para prevenir inyección SQL
            $fecha_cosecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha_cosecha']);
            $cantidad_cosechada = floatval($datos['cantidad_cosechada']);
            $unidad = $this->conexion->getMysqli()->real_escape_string($datos['unidad']);
            $calidad = $this->conexion->getMysqli()->real_escape_string($datos['calidad']);
            $precio_venta_unitario = $datos['precio_venta_unitario'] ? floatval($datos['precio_venta_unitario']) : 'NULL';
            $comprador = $datos['comprador'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['comprador']) . "'" : 'NULL';
            $total_ingresos = $datos['total_ingresos'] ? floatval($datos['total_ingresos']) : 'NULL';
            $observaciones = $datos['observaciones'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) . "'" : 'NULL';
            
            // Actualizar la cosecha
            $sql = "UPDATE cosechas SET 
                        cos_fecha_cosecha = '$fecha_cosecha',
                        cos_cantidad_cosechada = $cantidad_cosechada,
                        cos_unidad = '$unidad',
                        cos_calidad = '$calidad',
                        cos_precio_venta_unitario = $precio_venta_unitario,
                        cos_comprador = $comprador,
                        cos_total_ingresos = $total_ingresos,
                        cos_observaciones = $observaciones
                    WHERE cos_id = $cosecha_id";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Cosecha actualizada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar la cosecha'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en actualizarCosecha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar la cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    public function eliminarCosecha($cosecha_id, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos y obtener info de la siembra
            $verificar_sql = "SELECT c.cos_id, c.cos_siembra_id, f.fin_propietario, c.cos_responsable_id
                            FROM cosechas c
                            INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                            INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                            INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                            WHERE c.cos_id = $cosecha_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = " . intval($usuario_id);
            }
            
            $resultado_verificar = $this->conexion->ejecutarSP($verificar_sql);
            
            if (!$resultado_verificar) {
                return [
                    'success' => false,
                    'message' => 'Error al verificar permisos'
                ];
            }
            
            $cosecha = $resultado_verificar->fetch_assoc();
            $resultado_verificar->free();
            
            if (!$cosecha) {
                return [
                    'success' => false,
                    'message' => 'Cosecha no encontrada o sin permisos para eliminar'
                ];
            }
            
            // Eliminar la cosecha
            $sql = "DELETE FROM cosechas WHERE cos_id = $cosecha_id";
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                // Verificar si hay más cosechas para esta siembra
                $check_sql = "SELECT COUNT(*) as total FROM cosechas WHERE cos_siembra_id = " . $cosecha['cos_siembra_id'];
                $check_resultado = $this->conexion->ejecutarSP($check_sql);
                
                if ($check_resultado) {
                    $count = $check_resultado->fetch_assoc();
                    $check_resultado->free();
                    
                    // Si no hay más cosechas, cambiar estado de siembra a 'en_crecimiento'
                    if ($count['total'] == 0) {
                        $update_sql = "UPDATE siembras SET sie_estado = 'en_crecimiento' WHERE sie_id = " . $cosecha['cos_siembra_id'];
                        $this->conexion->ejecutarSP($update_sql);
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Cosecha eliminada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al eliminar la cosecha'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en eliminarCosecha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar la cosecha: ' . $e->getMessage()
            ];
        }
    }
    
    public function obtenerEstadisticasProductividad($usuario_id, $rol_usuario, $filtros = []) {
        try {
            $sql = "SELECT 
                        COUNT(c.cos_id) as total_cosechas,
                        SUM(c.cos_cantidad_cosechada) as cantidad_total,
                        SUM(c.cos_total_ingresos) as ingresos_totales,
                        AVG(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_promedio_ha,
                        COUNT(CASE WHEN c.cos_calidad = 'primera' THEN 1 END) as cosechas_primera,
                        COUNT(CASE WHEN c.cos_calidad = 'segunda' THEN 1 END) as cosechas_segunda,
                        COUNT(CASE WHEN c.cos_calidad = 'tercera' THEN 1 END) as cosechas_tercera,
                        COUNT(CASE WHEN c.cos_calidad = 'descarte' THEN 1 END) as cosechas_descarte,
                        tc.tip_nombre as cul_nombre,
                        f.fin_nombre
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    WHERE 1=1";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = " . intval($usuario_id);
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = " . intval($usuario_id) . " OR c.cos_responsable_id = " . intval($usuario_id) . ")";
            }
            
            // Aplicar filtros adicionales
            if (!empty($filtros['fecha_inicio'])) {
                $fecha_inicio = $this->conexion->getMysqli()->real_escape_string($filtros['fecha_inicio']);
                $sql .= " AND c.cos_fecha_cosecha >= '$fecha_inicio'";
            }
            if (!empty($filtros['fecha_fin'])) {
                $fecha_fin = $this->conexion->getMysqli()->real_escape_string($filtros['fecha_fin']);
                $sql .= " AND c.cos_fecha_cosecha <= '$fecha_fin'";
            }
            if (!empty($filtros['cultivo_id'])) {
                $cultivo_id = intval($filtros['cultivo_id']);
                $sql .= " AND tc.tip_id = $cultivo_id";
            }
            
            $sql .= " GROUP BY tc.tip_id, f.fin_id
                     ORDER BY ingresos_totales DESC";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $estadisticas = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $estadisticas[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'estadisticas' => $estadisticas,
                    'message' => 'Estadísticas obtenidas correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta de estadísticas'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasProductividad: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
}
?>