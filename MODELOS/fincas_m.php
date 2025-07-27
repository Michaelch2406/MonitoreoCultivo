<?php
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Finca {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Finca: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear una nueva finca
     */
    public function crearFinca($nombre, $ubicacion, $area_total, $propietario_id, $latitud = null, $longitud = null, $descripcion = null) {
        try {
            // Escapar datos
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $ubicacion = $this->conexion->getMysqli()->real_escape_string($ubicacion);
            $descripcion = $descripcion ? $this->conexion->getMysqli()->real_escape_string($descripcion) : null;

            $sql = "INSERT INTO fincas (
                        fin_nombre, fin_ubicacion, fin_area_total, fin_propietario,
                        fin_latitud, fin_longitud, fin_descripcion, fin_estado, fin_fecha_registro
                    ) VALUES (
                        '$nombre', '$ubicacion', $area_total, $propietario_id,
                        " . ($latitud ? $latitud : "NULL") . ",
                        " . ($longitud ? $longitud : "NULL") . ",
                        " . ($descripcion ? "'$descripcion'" : "NULL") . ",
                        'activa', NOW()
                    )";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $finca_id = $this->conexion->getMysqli()->insert_id;
                return array(
                    'success' => true,
                    'message' => 'Finca registrada exitosamente',
                    'finca_id' => $finca_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar la finca: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearFinca: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Listar fincas con filtros y permisos por rol
     */
    public function listarFincas($usuario_id, $rol_usuario, $filtros = array()) {
        try {
            $sql = "SELECT f.fin_id, f.fin_nombre, f.fin_ubicacion, f.fin_area_total,
                           f.fin_latitud, f.fin_longitud, f.fin_descripcion,
                           f.fin_estado, f.fin_fecha_registro,
                           u.usu_nombre, u.usu_apellido, u.usu_email,
                           COUNT(l.lot_id) as total_lotes
                    FROM fincas f
                    INNER JOIN usuarios u ON f.fin_propietario = u.usu_id
                    LEFT JOIN lotes l ON f.fin_id = l.lot_finca_id
                    WHERE 1=1";

            // Aplicar permisos por rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                // Supervisores ven fincas asignadas o de agricultores bajo su supervisión
                $sql .= " AND (f.fin_supervisor = $usuario_id OR f.fin_propietario IN (
                    SELECT usu_id FROM usuarios WHERE usu_estado = 'activo' AND usu_rol = 'agricultor'
                ))";
            }
            // Administradores ven todas las fincas (sin restricción adicional)

            // Aplicar filtros
            if (isset($filtros['propietario']) && !empty($filtros['propietario'])) {
                $propietario = $this->conexion->getMysqli()->real_escape_string($filtros['propietario']);
                $sql .= " AND f.fin_propietario = '$propietario'";
            }

            if (isset($filtros['estado']) && !empty($filtros['estado'])) {
                $estado = $this->conexion->getMysqli()->real_escape_string($filtros['estado']);
                $sql .= " AND f.fin_estado = '$estado'";
            }

            if (isset($filtros['area_min']) && !empty($filtros['area_min'])) {
                $area_min = floatval($filtros['area_min']);
                $sql .= " AND f.fin_area_total >= $area_min";
            }

            if (isset($filtros['area_max']) && !empty($filtros['area_max'])) {
                $area_max = floatval($filtros['area_max']);
                $sql .= " AND f.fin_area_total <= $area_max";
            }

            if (isset($filtros['ubicacion']) && !empty($filtros['ubicacion'])) {
                $ubicacion = $this->conexion->getMysqli()->real_escape_string($filtros['ubicacion']);
                $sql .= " AND f.fin_ubicacion LIKE '%$ubicacion%'";
            }

            $sql .= " GROUP BY f.fin_id ORDER BY f.fin_fecha_registro DESC";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $fincas = array();
                while ($row = $resultado->fetch_assoc()) {
                    $fincas[] = array(
                        'finca_id' => $row['fin_id'],
                        'nombre' => $row['fin_nombre'],
                        'ubicacion' => $row['fin_ubicacion'],
                        'area_total' => $row['fin_area_total'],
                        'latitud' => $row['fin_latitud'],
                        'longitud' => $row['fin_longitud'],
                        'descripcion' => $row['fin_descripcion'],
                        'estado' => $row['fin_estado'],
                        'fecha_registro' => $row['fin_fecha_registro'],
                        'propietario_nombre' => $row['usu_nombre'],
                        'propietario_apellido' => $row['usu_apellido'],
                        'propietario_email' => $row['usu_email'],
                        'total_lotes' => $row['total_lotes']
                    );
                }

                return array(
                    'success' => true,
                    'fincas' => $fincas
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener fincas'
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarFincas: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener una finca específica
     */
    public function obtenerFinca($finca_id, $usuario_id, $rol_usuario) {
        try {
            $sql = "SELECT f.*, u.usu_nombre, u.usu_apellido, u.usu_email,
                           COUNT(l.lot_id) as total_lotes
                    FROM fincas f
                    INNER JOIN usuarios u ON f.fin_propietario = u.usu_id
                    LEFT JOIN lotes l ON f.fin_id = l.lot_finca_id
                    WHERE f.fin_id = $finca_id";

            // Aplicar permisos por rol
            if ($rol_usuario == 'agricultor') {
                $sql .= " AND f.fin_propietario = $usuario_id";
            } elseif ($rol_usuario == 'supervisor') {
                $sql .= " AND (f.fin_supervisor = $usuario_id OR f.fin_propietario IN (
                    SELECT usu_id FROM usuarios WHERE usu_estado = 'activo' AND usu_rol = 'agricultor'
                ))";
            }

            $sql .= " GROUP BY f.fin_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $finca = $resultado->fetch_assoc();
                return array(
                    'success' => true,
                    'finca' => $finca
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Finca no encontrada o sin permisos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerFinca: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar una finca
     */
    public function actualizarFinca($finca_id, $datos, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, 'editar')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para editar esta finca'
                );
            }

            $campos = array();
            
            if (isset($datos['nombre'])) {
                $nombre = $this->conexion->getMysqli()->real_escape_string($datos['nombre']);
                $campos[] = "fin_nombre = '$nombre'";
            }
            
            if (isset($datos['ubicacion'])) {
                $ubicacion = $this->conexion->getMysqli()->real_escape_string($datos['ubicacion']);
                $campos[] = "fin_ubicacion = '$ubicacion'";
            }
            
            if (isset($datos['latitud'])) {
                $latitud = floatval($datos['latitud']);
                $campos[] = "fin_latitud = $latitud";
            }
            
            if (isset($datos['longitud'])) {
                $longitud = floatval($datos['longitud']);
                $campos[] = "fin_longitud = $longitud";
            }
            
            if (isset($datos['area_total'])) {
                $area_total = floatval($datos['area_total']);
                $campos[] = "fin_area_total = $area_total";
            }
            
            if (isset($datos['descripcion'])) {
                $descripcion = $this->conexion->getMysqli()->real_escape_string($datos['descripcion']);
                $campos[] = "fin_descripcion = " . ($descripcion ? "'$descripcion'" : "NULL");
            }
            
            if (isset($datos['tipo_clima'])) {
                $tipo_clima = $this->conexion->getMysqli()->real_escape_string($datos['tipo_clima']);
                $campos[] = "fin_tipo_clima = " . ($tipo_clima ? "'$tipo_clima'" : "NULL");
            }
            
            if (isset($datos['acceso_agua'])) {
                $acceso_agua = $this->conexion->getMysqli()->real_escape_string($datos['acceso_agua']);
                $campos[] = "fin_acceso_agua = " . ($acceso_agua ? "'$acceso_agua'" : "NULL");
            }
            
            if (isset($datos['infraestructura'])) {
                $infraestructura = $this->conexion->getMysqli()->real_escape_string($datos['infraestructura']);
                $campos[] = "fin_infraestructura = " . ($infraestructura ? "'$infraestructura'" : "NULL");
            }
            
            if (isset($datos['estado_legal'])) {
                $estado_legal = $this->conexion->getMysqli()->real_escape_string($datos['estado_legal']);
                $campos[] = "fin_estado_legal = " . ($estado_legal ? "'$estado_legal'" : "NULL");
            }

            // Solo administradores pueden cambiar propietario y estado
            if ($rol_usuario == 'administrador') {
                if (isset($datos['propietario_id'])) {
                    $propietario_id = intval($datos['propietario_id']);
                    $campos[] = "fin_propietario = $propietario_id";
                }
                
                if (isset($datos['estado'])) {
                    $estado = $this->conexion->getMysqli()->real_escape_string($datos['estado']);
                    $campos[] = "fin_estado = '$estado'";
                }

                if (isset($datos['supervisor_id'])) {
                    $supervisor_id = intval($datos['supervisor_id']);
                    $campos[] = "fin_supervisor = " . ($supervisor_id > 0 ? $supervisor_id : "NULL");
                }
            }

            if (empty($campos)) {
                return array(
                    'success' => false,
                    'message' => 'No hay datos para actualizar'
                );
            }

            $campos[] = "fin_fecha_actualizacion = NOW()";
            $campos_sql = implode(', ', $campos);

            $sql = "UPDATE fincas SET $campos_sql WHERE fin_id = $finca_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Finca actualizada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar la finca'
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarFinca: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar una finca
     */
    public function eliminarFinca($finca_id, $usuario_id, $rol_usuario) {
        try {
            // Verificar permisos
            if (!$this->verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, 'eliminar')) {
                return array(
                    'success' => false,
                    'message' => 'No tiene permisos para eliminar esta finca'
                );
            }

            // Verificar si tiene lotes asociados
            $sql_check = "SELECT COUNT(*) as total FROM lotes WHERE lot_finca_id = $finca_id";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado_check) {
                $row = $resultado_check->fetch_assoc();
                if ($row['total'] > 0) {
                    return array(
                        'success' => false,
                        'message' => 'No se puede eliminar la finca porque tiene lotes asociados'
                    );
                }
            }

            $sql = "DELETE FROM fincas WHERE fin_id = $finca_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Finca eliminada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar la finca'
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarFinca: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Verificar permisos de usuario sobre una finca
     */
    private function verificarPermisosFinca($finca_id, $usuario_id, $rol_usuario, $accion) {
        if ($rol_usuario == 'administrador') {
            return true; // Administradores tienen todos los permisos
        }

        if ($rol_usuario == 'agricultor') {
            // Agricultores solo pueden trabajar con sus propias fincas
            $sql = "SELECT fin_propietario FROM fincas WHERE fin_id = $finca_id";
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado && $resultado->num_rows > 0) {
                $finca = $resultado->fetch_assoc();
                return $finca['fin_propietario'] == $usuario_id;
            }
            return false;
        }

        if ($rol_usuario == 'supervisor') {
            // Supervisores no pueden crear, editar o eliminar
            if (in_array($accion, ['crear', 'editar', 'eliminar'])) {
                return false;
            }
            // Solo pueden ver fincas asignadas
            return true;
        }

        return false;
    }

    /**
     * Obtener agricultores para asignación
     */
    public function obtenerAgricultores() {
        try {
            $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_email 
                    FROM usuarios 
                    WHERE usu_rol = 'agricultor' AND usu_estado = 'activo'
                    ORDER BY usu_nombre, usu_apellido";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $agricultores = array();
                while ($row = $resultado->fetch_assoc()) {
                    $agricultores[] = $row;
                }

                return array(
                    'success' => true,
                    'agricultores' => $agricultores
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener agricultores'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerAgricultores: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener supervisores para asignación
     */
    public function obtenerSupervisores() {
        try {
            $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_email 
                    FROM usuarios 
                    WHERE usu_rol = 'supervisor' AND usu_estado = 'activo'
                    ORDER BY usu_nombre, usu_apellido";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $supervisores = array();
                while ($row = $resultado->fetch_assoc()) {
                    $supervisores[] = $row;
                }

                return array(
                    'success' => true,
                    'supervisores' => $supervisores
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener supervisores'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerSupervisores: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Crear tabla de fincas si no existe
     */
    private function crearTablaFincas() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS fincas (
                fin_id INT AUTO_INCREMENT PRIMARY KEY,
                fin_nombre VARCHAR(200) NOT NULL,
                fin_direccion TEXT NOT NULL,
                fin_municipio VARCHAR(100) NOT NULL,
                fin_departamento VARCHAR(100) NOT NULL,
                fin_latitud DECIMAL(10, 8) NULL,
                fin_longitud DECIMAL(11, 8) NULL,
                fin_area_total DECIMAL(10, 4) NOT NULL,
                fin_propietario INT NOT NULL,
                fin_supervisor INT NULL,
                fin_descripcion TEXT NULL,
                fin_tipo_clima VARCHAR(100) NULL,
                fin_acceso_agua VARCHAR(200) NULL,
                fin_infraestructura TEXT NULL,
                fin_estado_legal VARCHAR(100) NULL,
                fin_estado ENUM('activa', 'inactiva') DEFAULT 'activa',
                fin_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fin_fecha_actualizacion TIMESTAMP NULL,
                INDEX idx_propietario (fin_propietario),
                INDEX idx_supervisor (fin_supervisor),
                INDEX idx_estado (fin_estado),
                FOREIGN KEY (fin_propietario) REFERENCES usuarios(usu_id) ON DELETE RESTRICT,
                FOREIGN KEY (fin_supervisor) REFERENCES usuarios(usu_id) ON DELETE SET NULL
            )";
            
            $this->conexion->getMysqli()->query($sql);

            // Crear tabla de lotes si no existe
            $sql_lotes = "CREATE TABLE IF NOT EXISTS lotes (
                lot_id INT AUTO_INCREMENT PRIMARY KEY,
                lot_nombre VARCHAR(100) NOT NULL,
                lot_finca_id INT NOT NULL,
                lot_area DECIMAL(8, 4) NOT NULL,
                lot_estado ENUM('activo', 'inactivo') DEFAULT 'activo',
                lot_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_finca (lot_finca_id),
                FOREIGN KEY (lot_finca_id) REFERENCES fincas(fin_id) ON DELETE CASCADE
            )";
            
            $this->conexion->getMysqli()->query($sql_lotes);
        } catch (Exception $e) {
            error_log("Error en crearTablaFincas: " . $e->getMessage());
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->conexion) {
            $this->conexion->cerrarConexion();
        }
    }
}
?>