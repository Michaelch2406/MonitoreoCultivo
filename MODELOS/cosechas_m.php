<?php
require_once('../CONFIG/Conexion.php');

class Cosecha {
    private $conexion;
    
    public function __construct() {
        $this->conexion = conectar();
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
                        tc.cul_id,
                        tc.cul_nombre,
                        u.usu_id as responsable_id,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido,
                        up.usu_nombre as propietario_nombre,
                        up.usu_apellido as propietario_apellido
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.cul_id
                    LEFT JOIN usuarios u ON c.cos_responsable_id = u.usu_id
                    LEFT JOIN usuarios up ON f.fin_propietario = up.usu_id";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " WHERE f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " WHERE (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            // Administrador ve todas las cosechas
            
            $sql .= " ORDER BY c.cos_fecha_cosecha DESC";
            
            $stmt = $this->conexion->prepare($sql);
            
            if ($rol_usuario != 'administrador') {
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $cosechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'cosechas' => $cosechas,
                'message' => 'Cosechas obtenidas correctamente'
            ];
            
        } catch (Exception $e) {
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
                        tc.cul_id,
                        tc.cul_nombre,
                        tc.cul_categoria,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido,
                        up.usu_nombre as propietario_nombre,
                        up.usu_apellido as propietario_apellido
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.cul_id
                    LEFT JOIN usuarios u ON c.cos_responsable_id = u.usu_id
                    LEFT JOIN usuarios up ON f.fin_propietario = up.usu_id
                    WHERE c.cos_id = :cosecha_id";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':cosecha_id', $cosecha_id, PDO::PARAM_INT);
            
            if ($rol_usuario != 'administrador') {
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $cosecha = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            
        } catch (Exception $e) {
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
                            WHERE s.sie_id = :siembra_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = :usuario_id";
            }
            
            $stmt_verificar = $this->conexion->prepare($verificar_sql);
            $stmt_verificar->bindParam(':siembra_id', $datos['siembra_id'], PDO::PARAM_INT);
            
            if ($rol_usuario == 'agricultor') {
                $stmt_verificar->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt_verificar->execute();
            $siembra = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if (!$siembra) {
                return [
                    'success' => false,
                    'message' => 'Siembra no encontrada o sin permisos para crear cosecha'
                ];
            }
            
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
                        :siembra_id,
                        :fecha_cosecha,
                        :cantidad_cosechada,
                        :unidad,
                        :calidad,
                        :precio_venta_unitario,
                        :comprador,
                        :total_ingresos,
                        :responsable_id,
                        :observaciones
                    )";
            
            $stmt = $this->conexion->prepare($sql);
            
            $stmt->bindParam(':siembra_id', $datos['siembra_id'], PDO::PARAM_INT);
            $stmt->bindParam(':fecha_cosecha', $datos['fecha_cosecha']);
            $stmt->bindParam(':cantidad_cosechada', $datos['cantidad_cosechada']);
            $stmt->bindParam(':unidad', $datos['unidad']);
            $stmt->bindParam(':calidad', $datos['calidad']);
            $stmt->bindParam(':precio_venta_unitario', $datos['precio_venta_unitario']);
            $stmt->bindParam(':comprador', $datos['comprador']);
            $stmt->bindParam(':total_ingresos', $datos['total_ingresos']);
            $stmt->bindParam(':responsable_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':observaciones', $datos['observaciones']);
            
            if ($stmt->execute()) {
                $cosecha_id = $this->conexion->lastInsertId();
                
                // Actualizar estado de la siembra a 'cosechada'
                $update_sql = "UPDATE siembras SET sie_estado = 'cosechada' WHERE sie_id = :siembra_id";
                $update_stmt = $this->conexion->prepare($update_sql);
                $update_stmt->bindParam(':siembra_id', $datos['siembra_id'], PDO::PARAM_INT);
                $update_stmt->execute();
                
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
                            WHERE c.cos_id = :cosecha_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $verificar_sql .= " AND (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            
            $stmt_verificar = $this->conexion->prepare($verificar_sql);
            $stmt_verificar->bindParam(':cosecha_id', $cosecha_id, PDO::PARAM_INT);
            
            if ($rol_usuario != 'administrador') {
                $stmt_verificar->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt_verificar->execute();
            $cosecha = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if (!$cosecha) {
                return [
                    'success' => false,
                    'message' => 'Cosecha no encontrada o sin permisos para editar'
                ];
            }
            
            // Actualizar la cosecha
            $sql = "UPDATE cosechas SET 
                        cos_fecha_cosecha = :fecha_cosecha,
                        cos_cantidad_cosechada = :cantidad_cosechada,
                        cos_unidad = :unidad,
                        cos_calidad = :calidad,
                        cos_precio_venta_unitario = :precio_venta_unitario,
                        cos_comprador = :comprador,
                        cos_total_ingresos = :total_ingresos,
                        cos_observaciones = :observaciones
                    WHERE cos_id = :cosecha_id";
            
            $stmt = $this->conexion->prepare($sql);
            
            $stmt->bindParam(':cosecha_id', $cosecha_id, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_cosecha', $datos['fecha_cosecha']);
            $stmt->bindParam(':cantidad_cosechada', $datos['cantidad_cosechada']);
            $stmt->bindParam(':unidad', $datos['unidad']);
            $stmt->bindParam(':calidad', $datos['calidad']);
            $stmt->bindParam(':precio_venta_unitario', $datos['precio_venta_unitario']);
            $stmt->bindParam(':comprador', $datos['comprador']);
            $stmt->bindParam(':total_ingresos', $datos['total_ingresos']);
            $stmt->bindParam(':observaciones', $datos['observaciones']);
            
            if ($stmt->execute()) {
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
                            WHERE c.cos_id = :cosecha_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND f.fin_propietario = :usuario_id";
            }
            
            $stmt_verificar = $this->conexion->prepare($verificar_sql);
            $stmt_verificar->bindParam(':cosecha_id', $cosecha_id, PDO::PARAM_INT);
            
            if ($rol_usuario == 'agricultor') {
                $stmt_verificar->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt_verificar->execute();
            $cosecha = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            
            if (!$cosecha) {
                return [
                    'success' => false,
                    'message' => 'Cosecha no encontrada o sin permisos para eliminar'
                ];
            }
            
            // Eliminar la cosecha
            $sql = "DELETE FROM cosechas WHERE cos_id = :cosecha_id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':cosecha_id', $cosecha_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Verificar si hay más cosechas para esta siembra
                $check_sql = "SELECT COUNT(*) as total FROM cosechas WHERE cos_siembra_id = :siembra_id";
                $check_stmt = $this->conexion->prepare($check_sql);
                $check_stmt->bindParam(':siembra_id', $cosecha['cos_siembra_id'], PDO::PARAM_INT);
                $check_stmt->execute();
                $count = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si no hay más cosechas, cambiar estado de siembra a 'en_crecimiento'
                if ($count['total'] == 0) {
                    $update_sql = "UPDATE siembras SET sie_estado = 'en_crecimiento' WHERE sie_id = :siembra_id";
                    $update_stmt = $this->conexion->prepare($update_sql);
                    $update_stmt->bindParam(':siembra_id', $cosecha['cos_siembra_id'], PDO::PARAM_INT);
                    $update_stmt->execute();
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
                        tc.cul_nombre,
                        f.fin_nombre
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.cul_id
                    WHERE 1=1";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            
            // Aplicar filtros adicionales
            if (!empty($filtros['fecha_inicio'])) {
                $sql .= " AND c.cos_fecha_cosecha >= :fecha_inicio";
            }
            if (!empty($filtros['fecha_fin'])) {
                $sql .= " AND c.cos_fecha_cosecha <= :fecha_fin";
            }
            if (!empty($filtros['cultivo_id'])) {
                $sql .= " AND tc.cul_id = :cultivo_id";
            }
            
            $sql .= " GROUP BY tc.cul_id, f.fin_id
                     ORDER BY ingresos_totales DESC";
            
            $stmt = $this->conexion->prepare($sql);
            
            if ($rol_usuario != 'administrador') {
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            if (!empty($filtros['fecha_inicio'])) {
                $stmt->bindParam(':fecha_inicio', $filtros['fecha_inicio']);
            }
            if (!empty($filtros['fecha_fin'])) {
                $stmt->bindParam(':fecha_fin', $filtros['fecha_fin']);
            }
            if (!empty($filtros['cultivo_id'])) {
                $stmt->bindParam(':cultivo_id', $filtros['cultivo_id'], PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'estadisticas' => $estadisticas,
                'message' => 'Estadísticas obtenidas correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
    
    public function obtenerComparacionHistorica($usuario_id, $rol_usuario, $periodo_actual, $periodo_anterior) {
        try {
            $sql = "SELECT 
                        'actual' as periodo,
                        COUNT(c.cos_id) as total_cosechas,
                        SUM(c.cos_cantidad_cosechada) as cantidad_total,
                        SUM(c.cos_total_ingresos) as ingresos_totales,
                        AVG(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_promedio_ha
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE c.cos_fecha_cosecha BETWEEN :inicio_actual AND :fin_actual";
            
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            
            $sql .= " UNION ALL 
                     SELECT 
                        'anterior' as periodo,
                        COUNT(c.cos_id) as total_cosechas,
                        SUM(c.cos_cantidad_cosechada) as cantidad_total,
                        SUM(c.cos_total_ingresos) as ingresos_totales,
                        AVG(c.cos_cantidad_cosechada / l.lot_area) as rendimiento_promedio_ha
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE c.cos_fecha_cosecha BETWEEN :inicio_anterior AND :fin_anterior";
            
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = :usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = :usuario_id OR c.cos_responsable_id = :usuario_id)";
            }
            
            $stmt = $this->conexion->prepare($sql);
            
            $stmt->bindParam(':inicio_actual', $periodo_actual['inicio']);
            $stmt->bindParam(':fin_actual', $periodo_actual['fin']);
            $stmt->bindParam(':inicio_anterior', $periodo_anterior['inicio']);
            $stmt->bindParam(':fin_anterior', $periodo_anterior['fin']);
            
            if ($rol_usuario != 'administrador') {
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $comparacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'comparacion' => $comparacion,
                'message' => 'Comparación histórica obtenida correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener comparación histórica: ' . $e->getMessage()
            ];
        }
    }
}
?>