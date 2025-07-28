<?php
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Siembra {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Siembra: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Listar siembras disponibles para cosecha según permisos del usuario
     */
    public function listarSiembrasParaCosecha($usuario_id, $rol) {
        try {
            $sql = "SELECT s.sie_id,
                           s.sie_fecha_siembra,
                           s.sie_fecha_estimada_cosecha,
                           s.sie_estado,
                           tc.tip_id as cul_id,
                           tc.tip_nombre as cul_nombre,
                           tc.tip_categoria as cul_categoria,
                           l.lot_id,
                           l.lot_nombre,
                           l.lot_area,
                           f.fin_id,
                           f.fin_nombre,
                           f.fin_propietario
                    FROM siembras s
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE s.sie_estado IN ('en_crecimiento', 'cosechada')"; // Solo siembras que pueden ser cosechadas
            
            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol == 'supervisor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            }
            // Los administradores ven todo
            
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
                    'message' => 'Siembras para cosecha obtenidas correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar la consulta'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en listarSiembrasParaCosecha: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener siembras para cosecha: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Listar siembras según permisos del usuario
     */
    public function listarSiembras($usuario_id, $rol) {
        try {
            $sql = "SELECT s.*, 
                           tc.tip_nombre, tc.tip_categoria, tc.tip_ciclo_dias,
                           l.lot_nombre, l.lot_area,
                           f.fin_nombre,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM siembras s
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON s.sie_responsable_id = u.usu_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                // Los supervisores pueden ver siembras asignadas a ellos
                $sql .= " WHERE s.sie_responsable_id = $usuario_id";
            }
            // Los administradores ven todo

            $sql .= " ORDER BY s.sie_fecha_siembra DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $siembras = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $fila['responsable_nombre'] = $fila['responsable_nombre'] . ' ' . $fila['responsable_apellido'];
                    $siembras[] = $fila;
                }

                return array(
                    'success' => true,
                    'siembras' => $siembras
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener las siembras: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarSiembras: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Crear una nueva siembra
     */
    public function crearSiembra($datos, $usuario_id) {
        try {
            // Escapar datos
            $lote_id = intval($datos['lote_id']);
            $tipo_cultivo_id = intval($datos['tipo_cultivo_id']);
            $fecha_siembra = $this->conexion->getMysqli()->real_escape_string($datos['fecha_siembra']);
            $fecha_estimada_cosecha = isset($datos['fecha_estimada_cosecha']) && $datos['fecha_estimada_cosecha'] ? 
                                     $this->conexion->getMysqli()->real_escape_string($datos['fecha_estimada_cosecha']) : null;
            $cantidad_semilla = isset($datos['cantidad_semilla']) && $datos['cantidad_semilla'] !== '' ? 
                               floatval($datos['cantidad_semilla']) : null;
            $unidad_semilla = isset($datos['unidad_semilla']) ? 
                             $this->conexion->getMysqli()->real_escape_string($datos['unidad_semilla']) : null;
            $densidad_siembra = isset($datos['densidad_siembra']) ? 
                               $this->conexion->getMysqli()->real_escape_string($datos['densidad_siembra']) : null;
            $metodo_siembra = isset($datos['metodo_siembra']) ? 
                             $this->conexion->getMysqli()->real_escape_string($datos['metodo_siembra']) : 'manual';
            $estado = isset($datos['estado']) ? 
                     $this->conexion->getMysqli()->real_escape_string($datos['estado']) : 'planificada';
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            // Verificar que el lote existe y el usuario tiene permisos
            if (!$this->verificarPermisosLote($lote_id, $usuario_id)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para usar este lote'
                );
            }

            // Verificar que el lote esté disponible
            if (!$this->verificarLoteDisponible($lote_id)) {
                return array(
                    'success' => false,
                    'message' => 'El lote seleccionado no está disponible para siembra'
                );
            }

            $sql = "INSERT INTO siembras (
                        sie_lote_id, sie_tipo_cultivo_id, sie_fecha_siembra, 
                        sie_fecha_estimada_cosecha, sie_cantidad_semilla, sie_unidad_semilla,
                        sie_densidad_siembra, sie_metodo_siembra, sie_responsable_id,
                        sie_estado, sie_observaciones
                    ) VALUES (
                        $lote_id, $tipo_cultivo_id, '$fecha_siembra', " . 
                        ($fecha_estimada_cosecha ? "'$fecha_estimada_cosecha'" : 'NULL') . ",
                        " . ($cantidad_semilla ? $cantidad_semilla : 'NULL') . ", " .
                        ($unidad_semilla ? "'$unidad_semilla'" : 'NULL') . ",
                        " . ($densidad_siembra ? "'$densidad_siembra'" : 'NULL') . ",
                        '$metodo_siembra', $usuario_id, '$estado', " . 
                        ($observaciones ? "'$observaciones'" : 'NULL') . "
                    )";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $siembra_id = $this->conexion->getMysqli()->insert_id;
                
                // Actualizar estado del lote si la siembra está confirmada
                if ($estado === 'sembrada') {
                    $this->actualizarEstadoLote($lote_id, 'sembrado');
                }

                return array(
                    'success' => true,
                    'message' => 'Siembra registrada exitosamente',
                    'siembra_id' => $siembra_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar la siembra: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearSiembra: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener una siembra específica
     */
    public function obtenerSiembra($siembra_id, $usuario_id, $rol) {
        try {
            $sql = "SELECT s.*, 
                           tc.tip_nombre, tc.tip_categoria, tc.tip_ciclo_dias,
                           l.lot_nombre, l.lot_area,
                           f.fin_nombre,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM siembras s
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON s.sie_responsable_id = u.usu_id
                    WHERE s.sie_id = $siembra_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                $sql .= " AND s.sie_responsable_id = $usuario_id";
            }

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $siembra = $resultado->fetch_assoc();
                $siembra['responsable_nombre'] = $siembra['responsable_nombre'] . ' ' . $siembra['responsable_apellido'];
                
                return array(
                    'success' => true,
                    'siembra' => $siembra
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Siembra no encontrada o sin permisos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerSiembra: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar una siembra
     */
    public function actualizarSiembra($siembra_id, $datos, $usuario_id, $rol) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosSiembra($siembra_id, $usuario_id, $rol)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para editar esta siembra'
                );
            }

            // Escapar datos
            $fecha_siembra = $this->conexion->getMysqli()->real_escape_string($datos['fecha_siembra']);
            $fecha_estimada_cosecha = isset($datos['fecha_estimada_cosecha']) && $datos['fecha_estimada_cosecha'] ? 
                                     $this->conexion->getMysqli()->real_escape_string($datos['fecha_estimada_cosecha']) : null;
            $cantidad_semilla = isset($datos['cantidad_semilla']) && $datos['cantidad_semilla'] !== '' ? 
                               floatval($datos['cantidad_semilla']) : null;
            $unidad_semilla = isset($datos['unidad_semilla']) ? 
                             $this->conexion->getMysqli()->real_escape_string($datos['unidad_semilla']) : null;
            $densidad_siembra = isset($datos['densidad_siembra']) ? 
                               $this->conexion->getMysqli()->real_escape_string($datos['densidad_siembra']) : null;
            $metodo_siembra = isset($datos['metodo_siembra']) ? 
                             $this->conexion->getMysqli()->real_escape_string($datos['metodo_siembra']) : 'manual';
            $estado = isset($datos['estado']) ? 
                     $this->conexion->getMysqli()->real_escape_string($datos['estado']) : 'planificada';
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            $sql = "UPDATE siembras SET 
                        sie_fecha_siembra = '$fecha_siembra',
                        sie_fecha_estimada_cosecha = " . ($fecha_estimada_cosecha ? "'$fecha_estimada_cosecha'" : 'NULL') . ",
                        sie_cantidad_semilla = " . ($cantidad_semilla ? $cantidad_semilla : 'NULL') . ",
                        sie_unidad_semilla = " . ($unidad_semilla ? "'$unidad_semilla'" : 'NULL') . ",
                        sie_densidad_siembra = " . ($densidad_siembra ? "'$densidad_siembra'" : 'NULL') . ",
                        sie_metodo_siembra = '$metodo_siembra',
                        sie_estado = '$estado',
                        sie_observaciones = " . ($observaciones ? "'$observaciones'" : 'NULL') . "
                    WHERE sie_id = $siembra_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Siembra actualizada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar la siembra: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarSiembra: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar una siembra
     */
    public function eliminarSiembra($siembra_id, $usuario_id, $rol) {
        try {
            // Solo administradores y dueños pueden eliminar
            if ($rol != 'administrador') {
                // Verificar que sea el responsable de la siembra
                $sql_check = "SELECT sie_responsable_id, sie_lote_id FROM siembras WHERE sie_id = $siembra_id";
                $resultado_check = $this->conexion->getMysqli()->query($sql_check);
                
                if (!$resultado_check || $resultado_check->num_rows == 0) {
                    return array(
                        'success' => false,
                        'message' => 'Siembra no encontrada'
                    );
                }
                
                $siembra = $resultado_check->fetch_assoc();
                if ($siembra['sie_responsable_id'] != $usuario_id) {
                    return array(
                        'success' => false,
                        'message' => 'No tienes permisos para eliminar esta siembra'
                    );
                }
            }

            // Obtener datos de la siembra antes de eliminar
            $sql_datos = "SELECT sie_lote_id FROM siembras WHERE sie_id = $siembra_id";
            $resultado_datos = $this->conexion->getMysqli()->query($sql_datos);
            $datos_siembra = $resultado_datos->fetch_assoc();

            $sql = "DELETE FROM siembras WHERE sie_id = $siembra_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                // Actualizar estado del lote a disponible
                $this->actualizarEstadoLote($datos_siembra['sie_lote_id'], 'disponible');

                return array(
                    'success' => true,
                    'message' => 'Siembra eliminada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar la siembra: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarSiembra: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Verificar permisos sobre un lote
     */
    private function verificarPermisosLote($lote_id, $usuario_id) {
        $sql = "SELECT f.fin_propietario 
                FROM lotes l 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE l.lot_id = $lote_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $lote = $resultado->fetch_assoc();
            return ($lote['fin_propietario'] == $usuario_id);
        }
        
        return false;
    }

    /**
     * Verificar si un lote está disponible
     */
    private function verificarLoteDisponible($lote_id) {
        $sql = "SELECT lot_estado FROM lotes WHERE lot_id = $lote_id";
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $lote = $resultado->fetch_assoc();
            return ($lote['lot_estado'] == 'disponible');
        }
        
        return false;
    }

    /**
     * Actualizar estado de un lote
     */
    private function actualizarEstadoLote($lote_id, $estado) {
        $sql = "UPDATE lotes SET lot_estado = '$estado' WHERE lot_id = $lote_id";
        $this->conexion->getMysqli()->query($sql);
    }

    /**
     * Verificar permisos sobre una siembra
     */
    private function verificarPermisosSiembra($siembra_id, $usuario_id, $rol) {
        if ($rol == 'administrador') {
            return true;
        }

        $sql = "SELECT s.sie_responsable_id, f.fin_propietario 
                FROM siembras s 
                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE s.sie_id = $siembra_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $siembra = $resultado->fetch_assoc();
            
            if ($rol == 'agricultor') {
                return ($siembra['sie_responsable_id'] == $usuario_id || $siembra['fin_propietario'] == $usuario_id);
            } elseif ($rol == 'supervisor') {
                return ($siembra['sie_responsable_id'] == $usuario_id);
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