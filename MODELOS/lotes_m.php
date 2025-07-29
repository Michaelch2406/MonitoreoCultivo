<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Lote {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
            $this->crearTablaLotes();
        } catch (Exception $e) {
            error_log("Error al inicializar Lote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            throw $e;
        }
    }

    /**
     * Crear un nuevo lote
     */
    public function crearLote($nombre, $finca_id, $area, $tipo_suelo = null, $ph_suelo = null, $descripcion = null, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos sobre la finca
            if (!$this->verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, 'crear')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para crear lotes en esta finca'
                );
            }

            // Validar que el área del lote no exceda el área disponible de la finca
            if (!$this->validarAreaLote($finca_id, $area)) {
                return array(
                    'success' => false,
                    'message' => 'El área del lote excede el área disponible de la finca'
                );
            }

            // Escapar datos
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $descripcion = $descripcion ? $this->conexion->getMysqli()->real_escape_string($descripcion) : null;
            $tipo_suelo = $tipo_suelo ? $this->conexion->getMysqli()->real_escape_string($tipo_suelo) : null;

            $sql = "INSERT INTO lotes (
                        lot_nombre, lot_finca_id, lot_area, lot_tipo_suelo, 
                        lot_ph_suelo, lot_descripcion, lot_estado
                    ) VALUES (
                        '$nombre', $finca_id, $area, " . 
                        ($tipo_suelo ? "'$tipo_suelo'" : "NULL") . ", " .
                        ($ph_suelo ? $ph_suelo : "NULL") . ", " .
                        ($descripcion ? "'$descripcion'" : "NULL") . ", 
                        'disponible'
                    )";

            error_log("SQL Crear Lote: " . $sql, 3, dirname(__FILE__) . "/../php_error.log");

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $lote_id = $this->conexion->getMysqli()->insert_id;
                return array(
                    'success' => true,
                    'message' => 'Lote registrado exitosamente',
                    'lote_id' => $lote_id
                );
            } else {
                error_log("Error MySQL al crear lote: " . $this->conexion->getMysqli()->error, 3, dirname(__FILE__) . "/../php_error.log");
                return array(
                    'success' => false,
                    'message' => 'Error al registrar el lote: ' . $this->conexion->getMysqli()->error
                );
            }

        } catch (Exception $e) {
            error_log("Error en crearLote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Listar lotes con filtros
     */
    public function listarLotes($usuario_id, $rol_usuario, $filtros = array()) {
        try {
            $sql = "SELECT l.*, f.fin_nombre, f.fin_area_total,
                           u.usu_nombre, u.usu_apellido, u.usu_email
                    FROM lotes l
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN usuarios u ON f.fin_propietario = u.usu_id
                    WHERE 1=1";

            // Aplicar permisos por rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario IN (
                    SELECT usu_id FROM usuarios WHERE usu_estado = 'activo' AND usu_rol = 'agricultor'
                ))";
            }

            // Aplicar filtros
            if (!empty($filtros['finca_id'])) {
                $finca_id = intval($filtros['finca_id']);
                $sql .= " AND l.lot_finca_id = $finca_id";
            }

            if (!empty($filtros['estado'])) {
                $estado = $this->conexion->getMysqli()->real_escape_string($filtros['estado']);
                $sql .= " AND l.lot_estado = '$estado'";
            }

            if (!empty($filtros['tipo_suelo'])) {
                $tipo_suelo = $this->conexion->getMysqli()->real_escape_string($filtros['tipo_suelo']);
                $sql .= " AND l.lot_tipo_suelo = '$tipo_suelo'";
            }

            if (!empty($filtros['area_min'])) {
                $area_min = floatval($filtros['area_min']);
                $sql .= " AND l.lot_area >= $area_min";
            }

            if (!empty($filtros['area_max'])) {
                $area_max = floatval($filtros['area_max']);
                $sql .= " AND l.lot_area <= $area_max";
            }

            $sql .= " ORDER BY l.lot_fecha_registro DESC";

            error_log("SQL Listar Lotes: " . $sql, 3, dirname(__FILE__) . "/../php_error.log");

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $lotes = array();
                while ($row = $resultado->fetch_assoc()) {
                    $lotes[] = $row;
                }

                return array(
                    'success' => true,
                    'lotes' => $lotes
                );
            } else {
                $error_msg = $this->conexion->getMysqli()->error;
                error_log("Error MySQL al listar lotes: " . $error_msg, 3, dirname(__FILE__) . "/../php_error.log");
                return array(
                    'success' => false,
                    'message' => 'Error al obtener los lotes: ' . $error_msg
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarLotes: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener un lote específico
     */
    public function obtenerLote($lote_id, $usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT l.*, f.fin_nombre, f.fin_area_total, f.fin_propietario,
                           u.usu_nombre, u.usu_apellido, u.usu_email
                    FROM lotes l
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    INNER JOIN usuarios u ON f.fin_propietario = u.usu_id
                    WHERE l.lot_id = $lote_id";

            // Aplicar permisos por rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario IN (
                    SELECT usu_id FROM usuarios WHERE usu_estado = 'activo' AND usu_rol = 'agricultor'
                ))";
            }

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $lote = $resultado->fetch_assoc();
                return array(
                    'success' => true,
                    'lote' => $lote
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Lote no encontrado o sin permisos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerLote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar un lote
     */
    public function actualizarLote($lote_id, $datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosLote($lote_id, $usuario_id, $rol_usuario, 'editar')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para editar este lote'
                );
            }

            $campos = array();

            if (isset($datos['nombre'])) {
                $nombre = $this->conexion->getMysqli()->real_escape_string($datos['nombre']);
                $campos[] = "lot_nombre = '$nombre'";
            }

            if (isset($datos['area'])) {
                $area = floatval($datos['area']);
                $campos[] = "lot_area = $area";
            }

            if (isset($datos['tipo_suelo'])) {
                if (empty($datos['tipo_suelo'])) {
                    $campos[] = "lot_tipo_suelo = NULL";
                } else {
                    $tipo_suelo = $this->conexion->getMysqli()->real_escape_string($datos['tipo_suelo']);
                    $campos[] = "lot_tipo_suelo = '$tipo_suelo'";
                }
            }

            if (isset($datos['ph_suelo'])) {
                if (empty($datos['ph_suelo'])) {
                    $campos[] = "lot_ph_suelo = NULL";
                } else {
                    $ph_suelo = floatval($datos['ph_suelo']);
                    $campos[] = "lot_ph_suelo = $ph_suelo";
                }
            }

            if (isset($datos['descripcion'])) {
                if (empty($datos['descripcion'])) {
                    $campos[] = "lot_descripcion = NULL";
                } else {
                    $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
                    $campos[] = "lot_descripcion = '$descripcion'";
                }
            }

            if (isset($datos['estado'])) {
                $estado = $this->conexion->getMysqli()->real_escape_string($datos['estado']);
                $campos[] = "lot_estado = '$estado'";
            }

            if (empty($campos)) {
                return array(
                    'success' => false,
                    'message' => 'No hay datos para actualizar'
                );
            }

            $campos_sql = implode(', ', $campos);
            $sql = "UPDATE lotes SET $campos_sql WHERE lot_id = $lote_id";

            error_log("SQL Actualizar Lote: " . $sql, 3, dirname(__FILE__) . "/../php_error.log");

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Lote actualizado exitosamente'
                );
            } else {
                error_log("Error MySQL al actualizar lote: " . $this->conexion->getMysqli()->error, 3, dirname(__FILE__) . "/../php_error.log");
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el lote: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarLote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar un lote
     */
    public function eliminarLote($lote_id, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosLote($lote_id, $usuario_id, $rol_usuario, 'eliminar')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar este lote'
                );
            }

            // Verificar que el lote no tenga cultivos activos
            $sql_check = "SELECT lot_estado FROM lotes WHERE lot_id = $lote_id";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);

            if ($resultado_check && $resultado_check->num_rows > 0) {
                $lote = $resultado_check->fetch_assoc();
                if ($lote['lot_estado'] == 'sembrado' || $lote['lot_estado'] == 'en_preparacion') {
                    return array(
                        'success' => false,
                        'message' => 'No se puede eliminar un lote con cultivos activos o en preparación'
                    );
                }
            }

            $sql = "DELETE FROM lotes WHERE lot_id = $lote_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Lote eliminado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar el lote'
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarLote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener lotes por finca
     */
    public function obtenerLotesPorFinca($finca_id, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos sobre la finca
            if (!$this->verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, 'ver')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para ver los lotes de esta finca'
                );
            }

            $sql = "SELECT * FROM lotes WHERE lot_finca_id = $finca_id ORDER BY lot_nombre";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $lotes = array();
                while ($row = $resultado->fetch_assoc()) {
                    $lotes[] = $row;
                }

                return array(
                    'success' => true,
                    'lotes' => $lotes
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener los lotes'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerLotesPorFinca: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener estadísticas de lotes
     */
    public function obtenerEstadisticasLotes($usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_lotes,
                        SUM(lot_area) as area_total,
                        AVG(lot_area) as area_promedio,
                        lot_estado,
                        COUNT(*) as cantidad_por_estado
                    FROM lotes l
                    INNER JOIN fincas f ON l.lot_finca_id = f.fin_id
                    WHERE 1=1";

            // Aplicar permisos por rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_propietario IN (
                    SELECT usu_id FROM usuarios WHERE usu_estado = 'activo' AND usu_rol = 'agricultor'
                ))";
            }

            $sql .= " GROUP BY lot_estado";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $estadisticas = array();
                while ($row = $resultado->fetch_assoc()) {
                    $estadisticas[] = $row;
                }

                return array(
                    'success' => true,
                    'estadisticas' => $estadisticas
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener estadísticas'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasLotes: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Validar que el área del lote no exceda el área disponible de la finca
     */
    private function validarAreaLote($finca_id, $area_lote, $lote_id_excluir = null) {
        try {
            $sql = "SELECT f.fin_area_total, COALESCE(SUM(l.lot_area), 0) as area_ocupada
                    FROM fincas f
                    LEFT JOIN lotes l ON f.fin_id = l.lot_finca_id";
            
            if ($lote_id_excluir) {
                $sql .= " AND l.lot_id != $lote_id_excluir";
            }
            
            $sql .= " WHERE f.fin_id = $finca_id GROUP BY f.fin_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $row = $resultado->fetch_assoc();
                $area_total = floatval($row['fin_area_total']);
                $area_ocupada = floatval($row['area_ocupada']);
                $area_disponible = $area_total - $area_ocupada;

                return $area_lote <= $area_disponible;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error en validarAreaLote: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
            return false;
        }
    }

    /**
     * Verificar permisos sobre una finca
     */
    private function verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, $accion) {
        if ($rol_usuario == 'administrador') {
            return true;
        }

        if ($rol_usuario == 'agricultor') {
            $sql = "SELECT fin_propietario FROM fincas WHERE fin_id = $finca_id";
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado && $resultado->num_rows > 0) {
                $finca = $resultado->fetch_assoc();
                return $finca['fin_propietario'] == $usuario_id;
            }
            return false;
        }

        if ($rol_usuario == 'supervisor') {
            // Supervisores no pueden crear o eliminar
            if (in_array($accion, ['crear', 'eliminar'])) {
                return false;
            }
            return true; // Pueden ver y editar
        }

        return false;
    }

    /**
     * Verificar permisos sobre un lote específico
     */
    private function verificarPermisosLote($lote_id, $usuario_id, $rol_usuario, $accion) {
        if ($rol_usuario == 'administrador') {
            return true;
        }

        // Obtener la finca del lote
        $sql = "SELECT f.fin_propietario FROM lotes l 
                INNER JOIN fincas f ON l.lot_finca_id = f.fin_id 
                WHERE l.lot_id = $lote_id";
        $resultado = $this->conexion->getMysqli()->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();
            
            if ($rol_usuario == 'agricultor') {
                return $row['fin_propietario'] == $usuario_id;
            }

            if ($rol_usuario == 'supervisor') {
                // Supervisores no pueden crear o eliminar
                if (in_array($accion, ['crear', 'eliminar'])) {
                    return false;
                }
                return true; // Pueden ver y editar
            }
        }

        return false;
    }

    /**
     * Crear tabla de lotes si no existe
     */
    private function crearTablaLotes() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS lotes (
                lot_id INT AUTO_INCREMENT PRIMARY KEY,
                lot_nombre VARCHAR(100) NOT NULL,
                lot_finca_id INT NOT NULL,
                lot_area DECIMAL(10,2) DEFAULT NULL,
                lot_tipo_suelo VARCHAR(100) DEFAULT NULL,
                lot_ph_suelo DECIMAL(3,1) DEFAULT NULL,
                lot_descripcion TEXT DEFAULT NULL,
                lot_estado ENUM('disponible','sembrado','cosechado','en_preparacion') DEFAULT 'disponible',
                lot_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_finca (lot_finca_id),
                INDEX idx_estado (lot_estado),
                FOREIGN KEY (lot_finca_id) REFERENCES fincas(fin_id) ON DELETE CASCADE
            )";
            
            $this->conexion->getMysqli()->query($sql);
        } catch (Exception $e) {
            error_log("Error en crearTablaLotes: " . $e->getMessage(), 3, dirname(__FILE__) . "/../php_error.log");
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