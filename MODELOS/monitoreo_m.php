<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Monitoreo {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Monitoreo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Listar monitoreos según permisos del usuario
     */
    public function listarMonitoreos($usuario_id, $rol) {
        try {
            $sql = "SELECT m.*, 
                           s.sie_id, 
                           tc.tip_nombre as nombre_cultivo,
                           l.lot_nombre as nombre_lote,
                           f.fin_nombre as nombre_finca,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM monitoreo m
                    INNER JOIN siembras s ON m.mon_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON m.mon_responsable_id = u.usu_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " WHERE (f.fin_propietario = $usuario_id OR m.mon_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                // Los supervisores pueden ver monitoreos asignados a ellos o de sus agricultores supervisados
                $sql .= " WHERE m.mon_responsable_id = $usuario_id";
            }
            // Los administradores ven todo

            $sql .= " ORDER BY m.mon_fecha_observacion DESC, m.mon_fecha_registro DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $monitoreos = array();
                while ($fila = $resultado->fetch_assoc()) {
                    $fila['responsable_nombre'] = $fila['responsable_nombre'] . ' ' . $fila['responsable_apellido'];
                    $monitoreos[] = $fila;
                }

                return array(
                    'success' => true,
                    'monitoreos' => $monitoreos
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener los monitoreos: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarMonitoreos: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Crear un nuevo monitoreo
     */
    public function crearMonitoreo($datos, $usuario_id) {
        try {
            // Escapar datos
            $siembra_id = intval($datos['siembra_id']);
            $fecha_observacion = $this->conexion->getMysqli()->real_escape_string($datos['fecha_observacion']);
            $altura_promedio = isset($datos['altura_promedio']) && $datos['altura_promedio'] !== '' ? 
                              floatval($datos['altura_promedio']) : null;
            $estado_general = $this->conexion->getMysqli()->real_escape_string($datos['estado_general']);
            $porcentaje_germinacion = isset($datos['porcentaje_germinacion']) && $datos['porcentaje_germinacion'] !== '' ? 
                                     floatval($datos['porcentaje_germinacion']) : null;
            $color_follaje = isset($datos['color_follaje']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['color_follaje']) : null;
            $presencia_plagas = $this->conexion->getMysqli()->real_escape_string($datos['presencia_plagas']);
            $tipo_plagas = isset($datos['tipo_plagas']) ? 
                          $this->conexion->getMysqli()->real_escape_string($datos['tipo_plagas']) : null;
            $presencia_enfermedades = $this->conexion->getMysqli()->real_escape_string($datos['presencia_enfermedades']);
            $tipo_enfermedades = isset($datos['tipo_enfermedades']) ? 
                                $this->conexion->getMysqli()->real_escape_string($datos['tipo_enfermedades']) : null;
            $condicion_clima = isset($datos['condicion_clima']) ? 
                              $this->conexion->getMysqli()->real_escape_string($datos['condicion_clima']) : null;
            $humedad_suelo = $this->conexion->getMysqli()->real_escape_string($datos['humedad_suelo']);
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            // Verificar que la siembra existe y el usuario tiene permisos
            if (!$this->verificarPermisosSiembra($siembra_id, $usuario_id)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para monitorear esta siembra'
                );
            }

            $sql = "INSERT INTO monitoreo (
                        mon_siembra_id, mon_fecha_observacion, mon_altura_promedio, 
                        mon_estado_general, mon_porcentaje_germinacion, mon_color_follaje,
                        mon_presencia_plagas, mon_tipo_plagas, mon_presencia_enfermedades, 
                        mon_tipo_enfermedades, mon_condicion_clima, mon_humedad_suelo,
                        mon_observaciones, mon_responsable_id
                    ) VALUES (
                        $siembra_id, '$fecha_observacion', " . 
                        ($altura_promedio ? $altura_promedio : 'NULL') . ",
                        '$estado_general', " . 
                        ($porcentaje_germinacion ? $porcentaje_germinacion : 'NULL') . ", " .
                        ($color_follaje ? "'$color_follaje'" : 'NULL') . ",
                        '$presencia_plagas', " . 
                        ($tipo_plagas ? "'$tipo_plagas'" : 'NULL') . ",
                        '$presencia_enfermedades', " . 
                        ($tipo_enfermedades ? "'$tipo_enfermedades'" : 'NULL') . ",
                        " . ($condicion_clima ? "'$condicion_clima'" : 'NULL') . ",
                        '$humedad_suelo', " . 
                        ($observaciones ? "'$observaciones'" : 'NULL') . ",
                        $usuario_id
                    )";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $monitoreo_id = $this->conexion->getMysqli()->insert_id;
                
                // Verificar si necesita generar alertas automáticas
                $this->generarAlertasAutomaticas($monitoreo_id, $datos);

                return array(
                    'success' => true,
                    'message' => 'Monitoreo registrado exitosamente',
                    'monitoreo_id' => $monitoreo_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar el monitoreo: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearMonitoreo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener un monitoreo específico
     */
    public function obtenerMonitoreo($monitoreo_id, $usuario_id, $rol) {
        try {
            $sql = "SELECT m.*, 
                           s.sie_id, 
                           tc.tip_nombre as nombre_cultivo,
                           l.lot_nombre as nombre_lote,
                           f.fin_nombre as nombre_finca,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM monitoreo m
                    INNER JOIN siembras s ON m.mon_siembra_id = s.sie_id
                    INNER JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON m.mon_responsable_id = u.usu_id
                    WHERE m.mon_id = $monitoreo_id";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR m.mon_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                $sql .= " AND m.mon_responsable_id = $usuario_id";
            }

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $monitoreo = $resultado->fetch_assoc();
                $monitoreo['responsable_nombre'] = $monitoreo['responsable_nombre'] . ' ' . $monitoreo['responsable_apellido'];
                
                return array(
                    'success' => true,
                    'monitoreo' => $monitoreo
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Monitoreo no encontrado o sin permisos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerMonitoreo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar un monitoreo
     */
    public function actualizarMonitoreo($monitoreo_id, $datos, $usuario_id, $rol) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosMonitoreo($monitoreo_id, $usuario_id, $rol)) {
                return array(
                    'success' => false,
                    'message' => 'No tienes permisos para editar este monitoreo'
                );
            }

            // Escapar datos
            $fecha_observacion = $this->conexion->getMysqli()->real_escape_string($datos['fecha_observacion']);
            $altura_promedio = isset($datos['altura_promedio']) && $datos['altura_promedio'] !== '' ? 
                              floatval($datos['altura_promedio']) : null;
            $estado_general = $this->conexion->getMysqli()->real_escape_string($datos['estado_general']);
            $porcentaje_germinacion = isset($datos['porcentaje_germinacion']) && $datos['porcentaje_germinacion'] !== '' ? 
                                     floatval($datos['porcentaje_germinacion']) : null;
            $color_follaje = isset($datos['color_follaje']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['color_follaje']) : null;
            $presencia_plagas = $this->conexion->getMysqli()->real_escape_string($datos['presencia_plagas']);
            $tipo_plagas = isset($datos['tipo_plagas']) ? 
                          $this->conexion->getMysqli()->real_escape_string($datos['tipo_plagas']) : null;
            $presencia_enfermedades = $this->conexion->getMysqli()->real_escape_string($datos['presencia_enfermedades']);
            $tipo_enfermedades = isset($datos['tipo_enfermedades']) ? 
                                $this->conexion->getMysqli()->real_escape_string($datos['tipo_enfermedades']) : null;
            $condicion_clima = isset($datos['condicion_clima']) ? 
                              $this->conexion->getMysqli()->real_escape_string($datos['condicion_clima']) : null;
            $humedad_suelo = $this->conexion->getMysqli()->real_escape_string($datos['humedad_suelo']);
            $observaciones = isset($datos['observaciones']) ? 
                            $this->conexion->getMysqli()->real_escape_string($datos['observaciones']) : null;

            $sql = "UPDATE monitoreo SET 
                        mon_fecha_observacion = '$fecha_observacion',
                        mon_altura_promedio = " . ($altura_promedio ? $altura_promedio : 'NULL') . ",
                        mon_estado_general = '$estado_general',
                        mon_porcentaje_germinacion = " . ($porcentaje_germinacion ? $porcentaje_germinacion : 'NULL') . ",
                        mon_color_follaje = " . ($color_follaje ? "'$color_follaje'" : 'NULL') . ",
                        mon_presencia_plagas = '$presencia_plagas',
                        mon_tipo_plagas = " . ($tipo_plagas ? "'$tipo_plagas'" : 'NULL') . ",
                        mon_presencia_enfermedades = '$presencia_enfermedades',
                        mon_tipo_enfermedades = " . ($tipo_enfermedades ? "'$tipo_enfermedades'" : 'NULL') . ",
                        mon_condicion_clima = " . ($condicion_clima ? "'$condicion_clima'" : 'NULL') . ",
                        mon_humedad_suelo = '$humedad_suelo',
                        mon_observaciones = " . ($observaciones ? "'$observaciones'" : 'NULL') . "
                    WHERE mon_id = $monitoreo_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Monitoreo actualizado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el monitoreo: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarMonitoreo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar un monitoreo
     */
    public function eliminarMonitoreo($monitoreo_id, $usuario_id, $rol) {
        try {
            // Solo administradores y dueños pueden eliminar
            if ($rol != 'administrador') {
                // Verificar que sea el responsable del monitoreo
                $sql_check = "SELECT mon_responsable_id FROM monitoreo WHERE mon_id = $monitoreo_id";
                $resultado_check = $this->conexion->getMysqli()->query($sql_check);
                
                if (!$resultado_check || $resultado_check->num_rows == 0) {
                    return array(
                        'success' => false,
                        'message' => 'Monitoreo no encontrado'
                    );
                }
                
                $monitoreo = $resultado_check->fetch_assoc();
                if ($monitoreo['mon_responsable_id'] != $usuario_id) {
                    return array(
                        'success' => false,
                        'message' => 'No tienes permisos para eliminar este monitoreo'
                    );
                }
            }

            $sql = "DELETE FROM monitoreo WHERE mon_id = $monitoreo_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Monitoreo eliminado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar el monitoreo: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarMonitoreo: " . $e->getMessage());
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
        $sql = "SELECT f.fin_propietario, s.sie_responsable_id 
                FROM siembras s 
                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE s.sie_id = $siembra_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $siembra = $resultado->fetch_assoc();
            return ($siembra['fin_propietario'] == $usuario_id || $siembra['sie_responsable_id'] == $usuario_id);
        }
        
        return false;
    }

    /**
     * Verificar permisos sobre un monitoreo
     */
    private function verificarPermisosMonitoreo($monitoreo_id, $usuario_id, $rol) {
        if ($rol == 'administrador') {
            return true;
        }

        $sql = "SELECT m.mon_responsable_id, f.fin_propietario 
                FROM monitoreo m 
                INNER JOIN siembras s ON m.mon_siembra_id = s.sie_id
                INNER JOIN lotes l ON s.sie_lote_id = l.lot_id 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE m.mon_id = $monitoreo_id";
        
        $resultado = $this->conexion->getMysqli()->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $monitoreo = $resultado->fetch_assoc();
            
            if ($rol == 'agricultor') {
                return ($monitoreo['mon_responsable_id'] == $usuario_id || $monitoreo['fin_propietario'] == $usuario_id);
            } elseif ($rol == 'supervisor') {
                // Los supervisores pueden editar monitoreos que ellos crearon 
                // o de cualquier finca (asumiendo supervisión general)
                // TODO: Implementar tabla supervisor_fincas para supervisión específica
                return true; // Permitir edición temporal para supervisores
            }
        }
        
        return false;
    }

    /**
     * Generar alertas automáticas basadas en el monitoreo
     */
    private function generarAlertasAutomaticas($monitoreo_id, $datos) {
        try {
            $alertas = array();

            // Alerta por estado crítico
            if ($datos['estado_general'] == 'critico') {
                $alertas[] = array(
                    'tipo' => 'general',
                    'titulo' => 'Estado crítico del cultivo',
                    'mensaje' => 'El cultivo presenta un estado general crítico. Se requiere atención inmediata.',
                    'prioridad' => 'critica'
                );
            }

            // Alerta por plagas severas
            if ($datos['presencia_plagas'] == 'severa') {
                $alertas[] = array(
                    'tipo' => 'fumigacion',
                    'titulo' => 'Infestación severa de plagas',
                    'mensaje' => 'Se ha detectado una infestación severa de plagas. Se recomienda tratamiento inmediato.',
                    'prioridad' => 'alta'
                );
            }

            // Alerta por enfermedades severas
            if ($datos['presencia_enfermedades'] == 'severa') {
                $alertas[] = array(
                    'tipo' => 'fumigacion',
                    'titulo' => 'Enfermedad severa detectada',
                    'mensaje' => 'Se ha detectado una enfermedad severa en el cultivo. Se requiere tratamiento especializado.',
                    'prioridad' => 'alta'
                );
            }

            // Alerta por suelo seco
            if ($datos['humedad_suelo'] == 'seco') {
                $alertas[] = array(
                    'tipo' => 'riego',
                    'titulo' => 'Riego requerido',
                    'mensaje' => 'El suelo presenta condiciones secas. Se recomienda riego inmediato.',
                    'prioridad' => 'media'
                );
            }

            // Crear las alertas en la base de datos
            if (!empty($alertas)) {
                $this->crearAlertas($alertas, $datos['siembra_id']);
            }

        } catch (Exception $e) {
            error_log("Error en generarAlertasAutomaticas: " . $e->getMessage());
        }
    }

    /**
     * Crear alertas en la base de datos
     */
    private function crearAlertas($alertas, $siembra_id) {
        foreach ($alertas as $alerta) {
            $tipo = $this->conexion->getMysqli()->real_escape_string($alerta['tipo']);
            $titulo = $this->conexion->getMysqli()->real_escape_string($alerta['titulo']);
            $mensaje = $this->conexion->getMysqli()->real_escape_string($alerta['mensaje']);
            $prioridad = $this->conexion->getMysqli()->real_escape_string($alerta['prioridad']);

            $sql = "INSERT INTO alertas (ale_siembra_id, ale_tipo, ale_titulo, ale_mensaje, ale_prioridad) 
                    VALUES ($siembra_id, '$tipo', '$titulo', '$mensaje', '$prioridad')";
            
            $this->conexion->getMysqli()->query($sql);
        }
    }

    /**
     * Limpiar datos de entrada
     */
    public function limpiarDatos($dato) {
        return trim($this->conexion->getMysqli()->real_escape_string($dato));
    }
}
?>