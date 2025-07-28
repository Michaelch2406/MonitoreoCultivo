<?php
require_once('../CONFIG/Conexion.php');

class Finanzas {
    private $conexion;
    
    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Finanzas: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Listar gastos según permisos del usuario
     */
    public function listarGastos($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        g.gas_id,
                        g.gas_tipo,
                        g.gas_descripcion,
                        g.gas_fecha,
                        g.gas_monto,
                        g.gas_proveedor,
                        g.gas_factura_numero,
                        g.gas_observaciones,
                        g.gas_fecha_registro,
                        g.gas_responsable_id as responsable_id,
                        f.fin_id,
                        f.fin_nombre,
                        s.sie_id,
                        l.lot_nombre,
                        tc.tip_nombre as cul_nombre,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido
                    FROM gastos g
                    LEFT JOIN fincas f ON g.gas_finca_id = f.fin_id
                    LEFT JOIN siembras s ON g.gas_siembra_id = s.sie_id
                    LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id
                    LEFT JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    LEFT JOIN usuarios u ON g.gas_responsable_id = u.usu_id";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            }
            // Administrador ve todos los gastos
            
            $sql .= " ORDER BY g.gas_fecha DESC";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $gastos = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $gastos[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'gastos' => $gastos,
                    'message' => 'Gastos obtenidos correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en listarGastos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener los gastos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener un gasto específico
     */
    public function obtenerGasto($gasto_id, $usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        g.*,
                        f.fin_nombre,
                        s.sie_id,
                        l.lot_nombre,
                        tc.tip_nombre as cul_nombre,
                        u.usu_nombre as responsable_nombre,
                        u.usu_apellido as responsable_apellido
                    FROM gastos g
                    LEFT JOIN fincas f ON g.gas_finca_id = f.fin_id
                    LEFT JOIN siembras s ON g.gas_siembra_id = s.sie_id
                    LEFT JOIN lotes l ON s.sie_lote_id = l.lot_id
                    LEFT JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    LEFT JOIN usuarios u ON g.gas_responsable_id = u.usu_id
                    WHERE g.gas_id = $gasto_id";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            }
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $gasto = $resultado->fetch_assoc();
                $resultado->free();
                
                if ($gasto) {
                    return [
                        'success' => true,
                        'gasto' => $gasto,
                        'message' => 'Gasto obtenido correctamente'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Gasto no encontrado o sin permisos'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener el gasto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crear nuevo gasto
     */
    public function crearGasto($datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            if (!in_array($rol_usuario, ['administrador', 'agricultor'])) {
                return [
                    'success' => false,
                    'message' => 'No tienes permisos para crear gastos'
                ];
            }
            
            // Escapar datos para prevenir inyección SQL
            $tipo = $this->conexion->getMysqli()->real_escape_string($datos['tipo']);
            $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
            $fecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha']);
            $monto = floatval($datos['monto']);
            
            $finca_id = !empty($datos['finca_id']) ? intval($datos['finca_id']) : 'NULL';
            $siembra_id = !empty($datos['siembra_id']) ? intval($datos['siembra_id']) : 'NULL';
            $proveedor = !empty($datos['proveedor']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['proveedor']) . "'" : 'NULL';
            $factura_numero = !empty($datos['factura_numero']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['factura_numero']) . "'" : 'NULL';
            $observaciones = !empty($datos['observaciones']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) . "'" : 'NULL';
            
            // Insertar el gasto
            $sql = "INSERT INTO gastos (
                        gas_tipo,
                        gas_descripcion,
                        gas_fecha,
                        gas_monto,
                        gas_finca_id,
                        gas_siembra_id,
                        gas_proveedor,
                        gas_factura_numero,
                        gas_responsable_id,
                        gas_observaciones
                    ) VALUES (
                        '$tipo',
                        '$descripcion',
                        '$fecha',
                        $monto,
                        $finca_id,
                        $siembra_id,
                        $proveedor,
                        $factura_numero,
                        $usuario_id,
                        $observaciones
                    )";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $gasto_id = $this->conexion->getMysqli()->insert_id;
                
                return [
                    'success' => true,
                    'gasto_id' => $gasto_id,
                    'message' => 'Gasto registrado correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar el gasto'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en crearGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear el gasto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualizar gasto existente
     */
    public function actualizarGasto($gasto_id, $datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            $verificar_sql = "SELECT g.gas_id, f.fin_propietario, g.gas_responsable_id
                            FROM gastos g
                            LEFT JOIN fincas f ON g.gas_finca_id = f.fin_id
                            WHERE g.gas_id = $gasto_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            }
            
            $resultado_verificar = $this->conexion->ejecutarSP($verificar_sql);
            
            if (!$resultado_verificar) {
                return [
                    'success' => false,
                    'message' => 'Error al verificar permisos'
                ];
            }
            
            $gasto = $resultado_verificar->fetch_assoc();
            $resultado_verificar->free();
            
            if (!$gasto) {
                return [
                    'success' => false,
                    'message' => 'Gasto no encontrado o sin permisos para editar'
                ];
            }
            
            // Escapar datos para prevenir inyección SQL
            $tipo = $this->conexion->getMysqli()->real_escape_string($datos['tipo']);
            $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
            $fecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha']);
            $monto = floatval($datos['monto']);
            
            $finca_id = !empty($datos['finca_id']) ? intval($datos['finca_id']) : 'NULL';
            $siembra_id = !empty($datos['siembra_id']) ? intval($datos['siembra_id']) : 'NULL';
            $proveedor = !empty($datos['proveedor']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['proveedor']) . "'" : 'NULL';
            $factura_numero = !empty($datos['factura_numero']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['factura_numero']) . "'" : 'NULL';
            $observaciones = !empty($datos['observaciones']) ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) . "'" : 'NULL';
            
            // Actualizar el gasto
            $sql = "UPDATE gastos SET 
                        gas_tipo = '$tipo',
                        gas_descripcion = '$descripcion',
                        gas_fecha = '$fecha',
                        gas_monto = $monto,
                        gas_finca_id = $finca_id,
                        gas_siembra_id = $siembra_id,
                        gas_proveedor = $proveedor,
                        gas_factura_numero = $factura_numero,
                        gas_observaciones = $observaciones
                    WHERE gas_id = $gasto_id";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Gasto actualizado correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar el gasto'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en actualizarGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar el gasto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Eliminar gasto
     */
    public function eliminarGasto($gasto_id, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            $verificar_sql = "SELECT g.gas_id, f.fin_propietario, g.gas_responsable_id
                            FROM gastos g
                            LEFT JOIN fincas f ON g.gas_finca_id = f.fin_id
                            WHERE g.gas_id = $gasto_id";
            
            if ($rol_usuario == 'agricultor') {
                $verificar_sql .= " AND (f.fin_propietario = $usuario_id OR g.gas_responsable_id = $usuario_id)";
            }
            
            $resultado_verificar = $this->conexion->ejecutarSP($verificar_sql);
            
            if (!$resultado_verificar) {
                return [
                    'success' => false,
                    'message' => 'Error al verificar permisos'
                ];
            }
            
            $gasto = $resultado_verificar->fetch_assoc();
            $resultado_verificar->free();
            
            if (!$gasto) {
                return [
                    'success' => false,
                    'message' => 'Gasto no encontrado o sin permisos para eliminar'
                ];
            }
            
            // Eliminar el gasto
            $sql = "DELETE FROM gastos WHERE gas_id = $gasto_id";
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Gasto eliminado correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al eliminar el gasto'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en eliminarGasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al eliminar el gasto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener fincas del usuario
     */
    public function obtenerFincasUsuario($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT fin_id, fin_nombre, fin_area_total
                    FROM fincas";
            
            if ($rol_usuario == 'agricultor') {
                $sql .= " WHERE fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " WHERE fin_propietario = $usuario_id";
            }
            
            $sql .= " ORDER BY fin_nombre";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $fincas = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $fincas[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'fincas' => $fincas,
                    'message' => 'Fincas obtenidas correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerFincasUsuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener fincas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener siembras del usuario
     */
    public function obtenerSiembrasUsuario($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        s.sie_id,
                        l.lot_nombre,
                        tc.tip_nombre as cul_nombre,
                        s.sie_fecha_siembra,
                        s.sie_estado
                    FROM siembras s
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id";
            
            if ($rol_usuario == 'agricultor') {
                $sql .= " WHERE f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            $sql .= " ORDER BY s.sie_fecha_siembra DESC";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $siembras = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $siembras[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'siembras' => $siembras,
                    'message' => 'Siembras obtenidas correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerSiembrasUsuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener siembras: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener ingresos totales de cosechas
     */
    public function obtenerIngresosTotales($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT SUM(c.cos_total_ingresos) as total_ingresos
                    FROM cosechas c
                    INNER JOIN siembras s ON c.cos_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE c.cos_total_ingresos IS NOT NULL";
            
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR c.cos_responsable_id = $usuario_id)";
            }
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $fila = $resultado->fetch_assoc();
                $resultado->free();
                
                return [
                    'success' => true,
                    'total' => floatval($fila['total_ingresos']),
                    'message' => 'Ingresos obtenidos correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerIngresosTotales: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener ingresos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener análisis financiero por cultivo
     */
    public function obtenerAnalisisFinancieroPorCultivo($usuario_id, $rol_usuario, $fecha_inicio = null, $fecha_fin = null) {
        try {
            $sql = "SELECT 
                        tc.tip_nombre as cultivo,
                        COUNT(DISTINCT s.sie_id) as total_siembras,
                        SUM(IFNULL(g.gas_monto, 0)) as total_gastos,
                        SUM(IFNULL(c.cos_total_ingresos, 0)) as total_ingresos,
                        (SUM(IFNULL(c.cos_total_ingresos, 0)) - SUM(IFNULL(g.gas_monto, 0))) as utilidad_neta,
                        AVG(l.lot_area) as area_promedio,
                        (SUM(IFNULL(g.gas_monto, 0)) / AVG(l.lot_area)) as costo_por_hectarea
                    FROM tipos_cultivos tc
                    INNER JOIN siembras s ON tc.tip_id = s.sie_tipo_cultivo_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN gastos g ON s.sie_id = g.gas_siembra_id
                    LEFT JOIN cosechas c ON s.sie_id = c.cos_siembra_id
                    WHERE 1=1";
            
            // Aplicar filtros según el rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            
            // Filtros de fecha
            if ($fecha_inicio) {
                $fecha_inicio_escaped = $this->conexion->getMysqli()->real_escape_string($fecha_inicio);
                $sql .= " AND s.sie_fecha_siembra >= '$fecha_inicio_escaped'";
            }
            if ($fecha_fin) {
                $fecha_fin_escaped = $this->conexion->getMysqli()->real_escape_string($fecha_fin);
                $sql .= " AND s.sie_fecha_siembra <= '$fecha_fin_escaped'";
            }
            
            $sql .= " GROUP BY tc.tip_id, tc.tip_nombre
                     HAVING total_siembras > 0
                     ORDER BY utilidad_neta DESC";
            
            $resultado = $this->conexion->ejecutarSP($sql);
            
            if ($resultado) {
                $analisis = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $analisis[] = $fila;
                }
                $resultado->free();
                
                return [
                    'success' => true,
                    'analisis' => $analisis,
                    'message' => 'Análisis financiero obtenido correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en obtenerAnalisisFinancieroPorCultivo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener análisis financiero: ' . $e->getMessage()
            ];
        }
    }
}
?>