<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(__DIR__ . '/../CONFIG/Conexion.php');

class NotificacionesModel {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    public function obtenerNotificacionesUsuario($usuario_id, $limite = 5) {
        try {
            $sql = "SELECT 
                        a.ale_id,
                        a.ale_tipo,
                        a.ale_titulo,
                        a.ale_mensaje,
                        a.ale_prioridad,
                        a.ale_estado,
                        a.ale_fecha_programada,
                        a.ale_fecha_registro,
                        s.sie_id,
                        tc.tip_nombre as cultivo_nombre
                    FROM alertas a
                    LEFT JOIN siembras s ON a.ale_siembra_id = s.sie_id
                    LEFT JOIN tipos_cultivos tc ON s.sie_tipo_cultivo_id = tc.tip_id
                    WHERE a.ale_usuario_id = ? OR a.ale_usuario_id IS NULL
                    ORDER BY 
                        CASE a.ale_prioridad
                            WHEN 'critica' THEN 1
                            WHEN 'alta' THEN 2
                            WHEN 'media' THEN 3
                            WHEN 'baja' THEN 4
                        END,
                        a.ale_fecha_registro DESC
                    LIMIT ?";
            
            $stmt = $this->conexion->getMysqli()->prepare($sql);
            $stmt->bind_param("ii", $usuario_id, $limite);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function contarNotificacionesNoVistas($usuario_id) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM alertas 
                    WHERE (ale_usuario_id = ? OR ale_usuario_id IS NULL) 
                    AND ale_estado = 'pendiente'";
            
            $stmt = $this->conexion->getMysqli()->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            
            $resultado = $stmt->get_result()->fetch_assoc();
            return $resultado['total'];
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function marcarComoVista($alerta_id, $usuario_id) {
        try {
            $sql = "UPDATE alertas 
                    SET ale_estado = 'vista' 
                    WHERE ale_id = ? AND (ale_usuario_id = ? OR ale_usuario_id IS NULL)";
            
            $stmt = $this->conexion->getMysqli()->prepare($sql);
            $stmt->bind_param("ii", $alerta_id, $usuario_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function marcarTodasComoVistas($usuario_id) {
        try {
            $sql = "UPDATE alertas 
                    SET ale_estado = 'vista' 
                    WHERE (ale_usuario_id = ? OR ale_usuario_id IS NULL) 
                    AND ale_estado = 'pendiente'";
            
            $stmt = $this->conexion->getMysqli()->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function crearNotificacion($datos) {
        try {
            $sql = "INSERT INTO alertas (
                        ale_siembra_id, 
                        ale_tipo, 
                        ale_titulo, 
                        ale_mensaje, 
                        ale_fecha_programada, 
                        ale_prioridad, 
                        ale_usuario_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conexion->getMysqli()->prepare($sql);
            $stmt->bind_param(
                "isssssi",
                $datos['siembra_id'],
                $datos['tipo'],
                $datos['titulo'],
                $datos['mensaje'],
                $datos['fecha_programada'],
                $datos['prioridad'],
                $datos['usuario_id']
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}
?>