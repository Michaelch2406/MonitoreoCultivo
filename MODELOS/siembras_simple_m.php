<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
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
     * Listar siembras según permisos del usuario
     */
    public function listarSiembras($usuario_id, $rol) {
        try {
            $sql = "SELECT s.sie_id, s.sie_fecha_siembra, s.sie_estado,
                           tc.cul_nombre as nombre_cultivo,
                           l.lot_nombre as nombre_lote,
                           f.fin_nombre as nombre_finca,
                           u.usu_nombre as responsable_nombre,
                           u.usu_apellido as responsable_apellido
                    FROM siembras s
                    INNER JOIN tipos_cultivo tc ON s.sie_tipo_cultivo_id = tc.cul_id
                    INNER JOIN lotes l ON s.sie_lote_id = l.lot_id
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    LEFT JOIN usuarios u ON s.sie_responsable_id = u.usu_id
                    WHERE s.sie_estado IN ('sembrada', 'en_crecimiento')";

            // Aplicar filtros según el rol
            if ($rol == 'agricultor') {
                $sql .= " AND (f.fin_propietario = $usuario_id OR s.sie_responsable_id = $usuario_id)";
            } elseif ($rol == 'supervisor') {
                // Los supervisores pueden ver siembras asignadas a ellos
                $sql .= " AND s.sie_responsable_id = $usuario_id";
            }
            // Los administradores ven todo

            $sql .= " ORDER BY s.sie_fecha_siembra DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $siembras = array();
                while ($fila = $resultado->fetch_assoc()) {
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
}
?>