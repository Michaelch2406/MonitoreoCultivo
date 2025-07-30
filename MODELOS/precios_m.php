<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Precios {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Precios: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener todos los planes de precios activos
     */
    public function obtenerTodosPlanes() {
        try {
            $sql = "SELECT * FROM planes_precios WHERE activo = TRUE ORDER BY precio_mensual";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $planes = array();
                while ($row = $resultado->fetch_assoc()) {
                    $planes[] = $row;
                }
                
                return array(
                    'success' => true,
                    'planes' => $planes
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener los planes de precios: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerTodosPlanes: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Destructor para cerrar conexión
     */
    public function __destruct() {
        if ($this->conexion) {
            $this->conexion->cerrarConexion();
        }
    }
}
?>
