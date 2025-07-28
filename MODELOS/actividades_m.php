<?php
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Actividad {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Actividad: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Listar actividades según permisos del usuario
     */
    public function listarActividades($usuario_id, $rol) {
        try {
            $sql = "SELECT a.*, 
                           s.sie_id, 
                           tc.tip_nombre, 
                           l.lot_nombre,
                           f.fin_nombre,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido,
                           CASE 
                               WHEN a.act_tipo = 'riego' THEN 'tint'
                               WHEN a.act_tipo = 'fertilizacion' THEN 'leaf'
                               WHEN a.act_tipo = 'fumigacion' THEN 'spray-can'
                               WHEN a.act_tipo = 'poda' THEN 'cut'
                               WHEN a.act_tipo = 'deshierbe' THEN 'broom'
                               WHEN a.act_tipo = 'aporque' THEN 'mountain'
                               ELSE 'tools'
                           END as icono
                    FROM actividades a
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON a.act_responsable_id = u.usu_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR a.act_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                // Los supervisores pueden ver actividades asignadas a ellos
                $sql .= " WHERE a.act_responsable_id = $usuario_id";
            }
            // Los administradores ven todo

            $sql .= " ORDER BY a.act_fecha DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $actividades = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $fila['responsable_nombre'] = $fila['responsable_nombre'] . ' ' . $fila['responsable_apellido'];
                    $actividades[] = $fila;
                }

                return array(
                    'success' => true,
                    'actividades' => $actividades
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener las actividades: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarActividades: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Crear una nueva actividad
     */
    public function crearActividad($datos, $usuario_id) {
        try {
            // Escapar datos
            $siembra_id = intval($datos['siembra_id']);
            $tipo = $this->conexion->getMysqli()->real_escape_string($datos['tipo']);
            $fecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha']);
            $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
            $productos_utilizados = isset($datos['productos_utilizados']) ? 
                                   $this->conexion->getMysqli()->real_escape_string($datos['productos_utilizados']) : null;
            $cantidad_producto = isset($datos['cantidad_producto']) && $datos['cantidad_producto'] !== '' ? 
                                floatval($datos['cantidad_producto']) : null;
            $unidad_producto = isset($datos['unidad_producto']) ? 
                              $this->conexion->getMysqli()->real_escape_string($datos['unidad_producto']) : null;
            $costo = isset($datos['costo']) && $datos['costo'] !== '' ? 
                     floatval($datos['costo']) : null;
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            // Verificar que la siembra existe y el usuario tiene permisos
            if (!$this->verificarPermisosSiembra($siembra_id, $usuario_id)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para registrar actividades en esta siembra'
                );
            }

            $sql = "INSERT INTO actividades (
                        act_siembra_id, act_tipo, act_fecha, act_descripcion,
                        act_productos_utilizados, act_cantidad_producto, act_unidad_producto,
                        act_costo, act_responsable_id, act_observaciones
                    ) VALUES (
                        $siembra_id, '$tipo', '$fecha', '$descripcion',
                        " . ($productos_utilizados ? "'$productos_utilizados'" : 'NULL') . ",
                        " . ($cantidad_producto ? $cantidad_producto : 'NULL') . ",
                        " . ($unidad_producto ? "'$unidad_producto'" : 'NULL') . ",
                        " . ($costo ? $costo : 'NULL') . ",
                        $usuario_id,
                        " . ($observaciones ? "'$observaciones'" : 'NULL') . "
                    )";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $actividad_id = $this->conexion->getMysqli()->insert_id;

                return array(
                    'success' => true,
                    'message' => 'Actividad registrada exitosamente',
                    'actividad_id' => $actividad_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar la actividad: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearActividad: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener una actividad específica
     */
    public function obtenerActividad($actividad_id, $usuario_id, $rol) {
        try {
            $sql = "SELECT a.*, 
                           s.sie_id, 
                           tc.tip_nombre, 
                           l.lot_nombre,
                           f.fin_nombre,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM actividades a
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON a.act_responsable_id = u.usu_id
                    WHERE a.act_id = $actividad_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR a.act_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                $sql .= " AND a.act_responsable_id = $usuario_id";
            }

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $actividad = $resultado->fetch_assoc();
                $actividad['responsable_nombre'] = $actividad['responsable_nombre'] . ' ' . $actividad['responsable_apellido'];
                
                return array(
                    'success' => true,
                    'actividad' => $actividad
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Actividad no encontrada o sin permisos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerActividad: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar una actividad
     */
    public function actualizarActividad($actividad_id, $datos, $usuario_id, $rol) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosActividad($actividad_id, $usuario_id, $rol)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para editar esta actividad'
                );
            }

            // Escapar datos
            $tipo = $this->conexion->getMysqli()->real_escape_string($datos['tipo']);
            $fecha = $this->conexion->getMysqli()->real_escape_string($datos['fecha']);
            $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
            $productos_utilizados = isset($datos['productos_utilizados']) ? 
                                   $this->conexion->getMysqli()->real_escape_string($datos['productos_utilizados']) : null;
            $cantidad_producto = isset($datos['cantidad_producto']) && $datos['cantidad_producto'] !== '' ? 
                                floatval($datos['cantidad_producto']) : null;
            $unidad_producto = isset($datos['unidad_producto']) ? 
                              $this->conexion->getMysqli()->real_escape_string($datos['unidad_producto']) : null;
            $costo = isset($datos['costo']) && $datos['costo'] !== '' ? 
                     floatval($datos['costo']) : null;
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            $sql = "UPDATE actividades SET 
                        act_tipo = '$tipo',
                        act_fecha = '$fecha',
                        act_descripcion = '$descripcion',
                        act_productos_utilizados = " . ($productos_utilizados ? "'$productos_utilizados'" : 'NULL') . ",
                        act_cantidad_producto = " . ($cantidad_producto ? $cantidad_producto : 'NULL') . ",
                        act_unidad_producto = " . ($unidad_producto ? "'$unidad_producto'" : 'NULL') . ",
                        act_costo = " . ($costo ? $costo : 'NULL') . ",
                        act_observaciones = " . ($observaciones ? "'$observaciones'" : 'NULL') . "
                    WHERE act_id = $actividad_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Actividad actualizada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar la actividad: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarActividad: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar una actividad
     */
    public function eliminarActividad($actividad_id, $usuario_id, $rol) {
        try {
            // Solo administradores y responsables pueden eliminar
            if ($rol != 'administrador') {
                // Verificar que sea el responsable de la actividad
                $sql_check = "SELECT act_responsable_id FROM actividades WHERE act_id = $actividad_id";
                $resultado_check = $this->conexion->getMysqli()->query($sql_check);
                
                if (!$resultado_check || $resultado_check->num_rows == 0) {
                    return array(
                        'success' => false,
                        'message' => 'Actividad no encontrada'
                    );
                }
                
                $actividad = $resultado_check->fetch_assoc();
                if ($actividad['act_responsable_id'] != $usuario_id) {
                    return array(
                        'success' => false,
                        'message' => 'No tienes permisos para eliminar esta actividad'
                    );
                }
            }

            $sql = "DELETE FROM actividades WHERE act_id = $actividad_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Actividad eliminada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar la actividad: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarActividad: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener actividades por siembra
     */
    public function obtenerActividadesPorSiembra($siembra_id, $usuario_id, $rol) {
        try {
            $sql = "SELECT a.*, 
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido,
                           CASE 
                               WHEN a.act_tipo = 'riego' THEN 'tint'
                               WHEN a.act_tipo = 'fertilizacion' THEN 'leaf'
                               WHEN a.act_tipo = 'fumigacion' THEN 'spray-can'
                               WHEN a.act_tipo = 'poda' THEN 'cut'
                               WHEN a.act_tipo = 'deshierbe' THEN 'broom'
                               WHEN a.act_tipo = 'aporque' THEN 'mountain'
                               ELSE 'tools'
                           END as icono
                    FROM actividades a
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON a.act_responsable_id = u.usu_id
                    WHERE a.act_siembra_id = $siembra_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR a.act_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                $sql .= " AND a.act_responsable_id = $usuario_id";
            }

            $sql .= " ORDER BY a.act_fecha DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $actividades = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $fila['responsable_nombre'] = $fila['responsable_nombre'] . ' ' . $fila['responsable_apellido'];
                    $actividades[] = $fila;
                }

                return array(
                    'success' => true,
                    'actividades' => $actividades
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener las actividades: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerActividadesPorSiembra: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener estadísticas de actividades
     */
    public function obtenerEstadisticasActividades($usuario_id, $rol) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_actividades,
                        COUNT(CASE WHEN DATE(a.act_fecha) = CURDATE() THEN 1 END) as actividades_hoy,
                        COUNT(CASE WHEN a.act_fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as actividades_semana,
                        COALESCE(SUM(a.act_costo), 0) as costo_total,
                        a.act_tipo,
                        COUNT(a.act_tipo) as cantidad_tipo
                    FROM actividades a
                    INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR a.act_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                $sql .= " WHERE a.act_responsable_id = $usuario_id";
            }

            $sql .= " GROUP BY a.act_tipo";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $estadisticas = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $estadisticas[] = $fila;
                }

                return array(
                    'success' => true,
                    'estadisticas' => $estadisticas
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener estadísticas: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasActividades: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Verificar permisos sobre una siembra
     */
    private function verificarPermisosSiembra($siembra_id, $usuario_id) {
        $sql = "SELECT s.sie_responsable_id, f.fin_propietario 
                FROM siembras s 
                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE s.sie_id = $siembra_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $siembra = $resultado->fetch_assoc();
            return ($siembra['sie_responsable_id'] == $usuario_id || $siembra['fin_propietario'] == $usuario_id);
        }
        
        return false;
    }

    /**
     * Verificar permisos sobre una actividad
     */
    private function verificarPermisosActividad($actividad_id, $usuario_id, $rol) {
        if ($rol == 'administrador') {
            return true;
        }

        $sql = "SELECT a.act_responsable_id, f.fin_propietario 
                FROM actividades a 
                INNER JOIN siembras s ON a.act_siembra_id = s.sie_id
                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE a.act_id = $actividad_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $actividad = $resultado->fetch_assoc();
            
            if ($rol == 'agricultor') {
                return ($actividad['act_responsable_id'] == $usuario_id || $actividad['fin_propietario'] == $usuario_id);
            } elseif ($rol == 'supervisor') {
                return ($actividad['act_responsable_id'] == $usuario_id);
            }
        }
        
        return false;
    }

    /**
     * Limpiar datos de entrada
     */
    public function limpiarDatos($dato) {
        return trim($this->conexion->getMysqli()->real_escape_string($dato));
    }
}
?>