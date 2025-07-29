<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

class Cultivo {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Cultivo: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * GESTIÓN DE TIPOS DE CULTIVOS (CATÁLOGO)
     */

    /**
     * Obtener todos los tipos de cultivos
     */
    public function obtenerTodosTiposCultivos($categoria = null, $activos_solo = true) {
        try {
            $sql = "SELECT tip_id, tip_nombre, tip_nombre_cientifico, tip_familia_botanica, 
                          tip_ciclo_vida, tip_ciclo_dias, tip_categoria, tip_descripcion,
                          tip_temperatura_min, tip_temperatura_max, tip_precipitacion,
                          tip_tipo_suelo, tip_ph_min, tip_ph_max, tip_densidad_siembra,
                          tip_profundidad_siembra, tip_requerimientos_agua, 
                          tip_requerimientos_suelo, tip_temperatura_optima, 
                          tip_estado, tip_fecha_registro
                    FROM tipos_cultivos WHERE 1=1";
            
            if ($activos_solo) {
                $sql .= " AND tip_estado = 'activo'";
            }
            
            if ($categoria) {
                $categoria = $this->conexion->getMysqli()->real_escape_string($categoria);
                $sql .= " AND tip_categoria = '$categoria'";
            }
            
            $sql .= " ORDER BY tip_categoria, tip_nombre";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $cultivos = array();
                while ($row = $resultado->fetch_assoc()) {
                    $cultivos[] = $row;
                }
                
                return array(
                    'success' => true,
                    'cultivos' => $cultivos
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener tipos de cultivos: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerTodosTiposCultivos: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener un tipo de cultivo por ID
     */
    public function obtenerTipoCultivoPorId($tip_id) {
        try {
            $tip_id = intval($tip_id);
            $sql = "SELECT * FROM tipos_cultivos WHERE tip_id = $tip_id";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado && $resultado->num_rows > 0) {
                $cultivo = $resultado->fetch_assoc();
                return array(
                    'success' => true,
                    'cultivo' => $cultivo
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Tipo de cultivo no encontrado'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerTipoCultivoPorId: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Crear nuevo tipo de cultivo (solo administradores)
     */
    public function crearTipoCultivo($datos) {
        try {
            // Validar datos requeridos
            if (empty($datos['tip_nombre'])) {
                return array('success' => false, 'message' => 'El nombre del cultivo es requerido');
            }

            // Verificar que no exista un cultivo con el mismo nombre
            $nombre = $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre']);
            $sql_check = "SELECT tip_id FROM tipos_cultivos WHERE tip_nombre = '$nombre'";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado_check && $resultado_check->num_rows > 0) {
                return array(
                    'success' => false,
                    'message' => 'Ya existe un tipo de cultivo con ese nombre'
                );
            }

            // Escapar datos
            $tip_nombre = $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre']);
            $tip_nombre_cientifico = isset($datos['tip_nombre_cientifico']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre_cientifico']) : null;
            $tip_familia_botanica = isset($datos['tip_familia_botanica']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_familia_botanica']) : null;
            $tip_ciclo_vida = isset($datos['tip_ciclo_vida']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_ciclo_vida']) : 'anual';
            $tip_ciclo_dias = isset($datos['tip_ciclo_dias']) ? intval($datos['tip_ciclo_dias']) : null;
            $tip_categoria = isset($datos['tip_categoria']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_categoria']) : 'hortalizas';
            $tip_descripcion = isset($datos['tip_descripcion']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_descripcion']) : null;
            
            // Requerimientos técnicos
            $tip_temperatura_min = isset($datos['tip_temperatura_min']) ? floatval($datos['tip_temperatura_min']) : null;
            $tip_temperatura_max = isset($datos['tip_temperatura_max']) ? floatval($datos['tip_temperatura_max']) : null;
            $tip_precipitacion = isset($datos['tip_precipitacion']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_precipitacion']) : null;
            $tip_tipo_suelo = isset($datos['tip_tipo_suelo']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_tipo_suelo']) : null;
            $tip_ph_min = isset($datos['tip_ph_min']) ? floatval($datos['tip_ph_min']) : null;
            $tip_ph_max = isset($datos['tip_ph_max']) ? floatval($datos['tip_ph_max']) : null;
            $tip_densidad_siembra = isset($datos['tip_densidad_siembra']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_densidad_siembra']) : null;
            $tip_profundidad_siembra = isset($datos['tip_profundidad_siembra']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_profundidad_siembra']) : null;
            
            // Información adicional
            $tip_requerimientos_agua = isset($datos['tip_requerimientos_agua']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_requerimientos_agua']) : null;
            $tip_requerimientos_suelo = isset($datos['tip_requerimientos_suelo']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_requerimientos_suelo']) : null;
            $tip_temperatura_optima = isset($datos['tip_temperatura_optima']) ? $this->conexion->getMysqli()->real_escape_string($datos['tip_temperatura_optima']) : null;

            $sql = "INSERT INTO tipos_cultivos (
                        tip_nombre, tip_nombre_cientifico, tip_familia_botanica, tip_ciclo_vida, 
                        tip_ciclo_dias, tip_categoria, tip_descripcion, tip_temperatura_min, 
                        tip_temperatura_max, tip_precipitacion, tip_tipo_suelo, tip_ph_min, 
                        tip_ph_max, tip_densidad_siembra, tip_profundidad_siembra, 
                        tip_requerimientos_agua, tip_requerimientos_suelo, tip_temperatura_optima,
                        tip_estado, tip_fecha_registro
                    ) VALUES (
                        '$tip_nombre', " . ($tip_nombre_cientifico ? "'$tip_nombre_cientifico'" : "NULL") . ", 
                        " . ($tip_familia_botanica ? "'$tip_familia_botanica'" : "NULL") . ", '$tip_ciclo_vida', 
                        " . ($tip_ciclo_dias ? $tip_ciclo_dias : "NULL") . ", '$tip_categoria', 
                        " . ($tip_descripcion ? "'$tip_descripcion'" : "NULL") . ", 
                        " . ($tip_temperatura_min ? $tip_temperatura_min : "NULL") . ", 
                        " . ($tip_temperatura_max ? $tip_temperatura_max : "NULL") . ", 
                        " . ($tip_precipitacion ? "'$tip_precipitacion'" : "NULL") . ", 
                        " . ($tip_tipo_suelo ? "'$tip_tipo_suelo'" : "NULL") . ", 
                        " . ($tip_ph_min ? $tip_ph_min : "NULL") . ", 
                        " . ($tip_ph_max ? $tip_ph_max : "NULL") . ", 
                        " . ($tip_densidad_siembra ? "'$tip_densidad_siembra'" : "NULL") . ", 
                        " . ($tip_profundidad_siembra ? "'$tip_profundidad_siembra'" : "NULL") . ", 
                        " . ($tip_requerimientos_agua ? "'$tip_requerimientos_agua'" : "NULL") . ", 
                        " . ($tip_requerimientos_suelo ? "'$tip_requerimientos_suelo'" : "NULL") . ", 
                        " . ($tip_temperatura_optima ? "'$tip_temperatura_optima'" : "NULL") . ", 
                        'activo', NOW()
                    )";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $cultivo_id = $this->conexion->getMysqli()->insert_id;
                return array(
                    'success' => true,
                    'message' => 'Tipo de cultivo creado exitosamente',
                    'cultivo_id' => $cultivo_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al crear el tipo de cultivo: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearTipoCultivo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar tipo de cultivo (solo administradores)
     */
    public function actualizarTipoCultivo($tip_id, $datos) {
        try {
            $tip_id = intval($tip_id);
            
            if (empty($datos['tip_nombre'])) {
                return array('success' => false, 'message' => 'El nombre del cultivo es requerido');
            }

            // Verificar que no exista otro cultivo con el mismo nombre
            $nombre = $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre']);
            $sql_check = "SELECT tip_id FROM tipos_cultivos WHERE tip_nombre = '$nombre' AND tip_id != $tip_id";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado_check && $resultado_check->num_rows > 0) {
                return array(
                    'success' => false,
                    'message' => 'Ya existe otro tipo de cultivo con ese nombre'
                );
            }

            // Construir query de actualización
            $campos_actualizar = array();
            
            if (isset($datos['tip_nombre'])) {
                $campos_actualizar[] = "tip_nombre = '" . $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre']) . "'";
            }
            if (isset($datos['tip_nombre_cientifico'])) {
                $campos_actualizar[] = "tip_nombre_cientifico = " . ($datos['tip_nombre_cientifico'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_nombre_cientifico']) . "'" : "NULL");
            }
            if (isset($datos['tip_familia_botanica'])) {
                $campos_actualizar[] = "tip_familia_botanica = " . ($datos['tip_familia_botanica'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_familia_botanica']) . "'" : "NULL");
            }
            if (isset($datos['tip_ciclo_vida'])) {
                $campos_actualizar[] = "tip_ciclo_vida = '" . $this->conexion->getMysqli()->real_escape_string($datos['tip_ciclo_vida']) . "'";
            }
            if (isset($datos['tip_ciclo_dias'])) {
                $campos_actualizar[] = "tip_ciclo_dias = " . ($datos['tip_ciclo_dias'] ? intval($datos['tip_ciclo_dias']) : "NULL");
            }
            if (isset($datos['tip_categoria'])) {
                $campos_actualizar[] = "tip_categoria = '" . $this->conexion->getMysqli()->real_escape_string($datos['tip_categoria']) . "'";
            }
            if (isset($datos['tip_descripcion'])) {
                $campos_actualizar[] = "tip_descripcion = " . ($datos['tip_descripcion'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_descripcion']) . "'" : "NULL");
            }
            if (isset($datos['tip_temperatura_min'])) {
                $campos_actualizar[] = "tip_temperatura_min = " . ($datos['tip_temperatura_min'] ? floatval($datos['tip_temperatura_min']) : "NULL");
            }
            if (isset($datos['tip_temperatura_max'])) {
                $campos_actualizar[] = "tip_temperatura_max = " . ($datos['tip_temperatura_max'] ? floatval($datos['tip_temperatura_max']) : "NULL");
            }
            if (isset($datos['tip_precipitacion'])) {
                $campos_actualizar[] = "tip_precipitacion = " . ($datos['tip_precipitacion'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_precipitacion']) . "'" : "NULL");
            }
            if (isset($datos['tip_tipo_suelo'])) {
                $campos_actualizar[] = "tip_tipo_suelo = " . ($datos['tip_tipo_suelo'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_tipo_suelo']) . "'" : "NULL");
            }
            if (isset($datos['tip_ph_min'])) {
                $campos_actualizar[] = "tip_ph_min = " . ($datos['tip_ph_min'] ? floatval($datos['tip_ph_min']) : "NULL");
            }
            if (isset($datos['tip_ph_max'])) {
                $campos_actualizar[] = "tip_ph_max = " . ($datos['tip_ph_max'] ? floatval($datos['tip_ph_max']) : "NULL");
            }
            if (isset($datos['tip_densidad_siembra'])) {
                $campos_actualizar[] = "tip_densidad_siembra = " . ($datos['tip_densidad_siembra'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_densidad_siembra']) . "'" : "NULL");
            }
            if (isset($datos['tip_profundidad_siembra'])) {
                $campos_actualizar[] = "tip_profundidad_siembra = " . ($datos['tip_profundidad_siembra'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_profundidad_siembra']) . "'" : "NULL");
            }
            if (isset($datos['tip_requerimientos_agua'])) {
                $campos_actualizar[] = "tip_requerimientos_agua = " . ($datos['tip_requerimientos_agua'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_requerimientos_agua']) . "'" : "NULL");
            }
            if (isset($datos['tip_requerimientos_suelo'])) {
                $campos_actualizar[] = "tip_requerimientos_suelo = " . ($datos['tip_requerimientos_suelo'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_requerimientos_suelo']) . "'" : "NULL");
            }
            if (isset($datos['tip_temperatura_optima'])) {
                $campos_actualizar[] = "tip_temperatura_optima = " . ($datos['tip_temperatura_optima'] ? "'" . $this->conexion->getMysqli()->real_escape_string($datos['tip_temperatura_optima']) . "'" : "NULL");
            }

            if (empty($campos_actualizar)) {
                return array('success' => false, 'message' => 'No hay campos para actualizar');
            }

            $sql = "UPDATE tipos_cultivos SET " . implode(', ', $campos_actualizar) . " WHERE tip_id = $tip_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Tipo de cultivo actualizado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el tipo de cultivo: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en actualizarTipoCultivo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Cambiar estado de tipo de cultivo (activar/desactivar)
     */
    public function cambiarEstadoTipoCultivo($tip_id, $nuevo_estado) {
        try {
            $tip_id = intval($tip_id);
            $nuevo_estado = $this->conexion->getMysqli()->real_escape_string($nuevo_estado);
            
            $sql = "UPDATE tipos_cultivos SET tip_estado = '$nuevo_estado' WHERE tip_id = $tip_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Estado del tipo de cultivo actualizado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el estado del tipo de cultivo'
                );
            }
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoTipoCultivo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Eliminar tipo de cultivo (solo administradores)
     */
    public function eliminarTipoCultivo($tip_id) {
        try {
            $tip_id = intval($tip_id);
            
            // Verificar si hay siembras que usan este tipo de cultivo
            $sql_check = "SELECT COUNT(*) as total FROM siembras WHERE sie_tipo_cultivo_id = $tip_id";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado_check) {
                $count = $resultado_check->fetch_assoc()['total'];
                if ($count > 0) {
                    return array(
                        'success' => false,
                        'message' => 'No se puede eliminar este tipo de cultivo porque está siendo usado en siembras'
                    );
                }
            }
            
            $sql = "DELETE FROM tipos_cultivos WHERE tip_id = $tip_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Tipo de cultivo eliminado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar el tipo de cultivo'
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarTipoCultivo: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Obtener cultivos por categoría
     */
    public function obtenerCultivosPorCategoria() {
        try {
            $sql = "SELECT tip_categoria, COUNT(*) as total 
                    FROM tipos_cultivos 
                    WHERE tip_estado = 'activo' 
                    GROUP BY tip_categoria 
                    ORDER BY tip_categoria";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $categorias = array();
                while ($row = $resultado->fetch_assoc()) {
                    $categorias[] = $row;
                }
                
                return array(
                    'success' => true,
                    'categorias' => $categorias
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener categorías de cultivos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerCultivosPorCategoria: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Buscar cultivos por término
     */
    public function buscarCultivos($termino, $categoria = null) {
        try {
            $termino = $this->conexion->getMysqli()->real_escape_string($termino);
            
            $sql = "SELECT tip_id, tip_nombre, tip_nombre_cientifico, tip_categoria, 
                          tip_ciclo_dias, tip_descripcion
                    FROM tipos_cultivos 
                    WHERE tip_estado = 'activo' 
                    AND (tip_nombre LIKE '%$termino%' 
                         OR tip_nombre_cientifico LIKE '%$termino%' 
                         OR tip_descripcion LIKE '%$termino%')";
            
            if ($categoria) {
                $categoria = $this->conexion->getMysqli()->real_escape_string($categoria);
                $sql .= " AND tip_categoria = '$categoria'";
            }
            
            $sql .= " ORDER BY tip_nombre";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $cultivos = array();
                while ($row = $resultado->fetch_assoc()) {
                    $cultivos[] = $row;
                }
                
                return array(
                    'success' => true,
                    'cultivos' => $cultivos
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error en la búsqueda de cultivos'
                );
            }
        } catch (Exception $e) {
            error_log("Error en buscarCultivos: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * FUNCIONES AUXILIARES
     */

    /**
     * Validar datos de cultivo
     */
    public function validarDatosCultivo($datos) {
        $errores = array();

        if (empty($datos['tip_nombre'])) {
            $errores[] = 'El nombre del cultivo es requerido';
        }

        if (isset($datos['tip_temperatura_min']) && isset($datos['tip_temperatura_max'])) {
            if ($datos['tip_temperatura_min'] > $datos['tip_temperatura_max']) {
                $errores[] = 'La temperatura mínima no puede ser mayor que la máxima';
            }
        }

        if (isset($datos['tip_ph_min']) && isset($datos['tip_ph_max'])) {
            if ($datos['tip_ph_min'] > $datos['tip_ph_max']) {
                $errores[] = 'El pH mínimo no puede ser mayor que el máximo';
            }
        }

        if (isset($datos['tip_categoria'])) {
            $categorias_validas = array('cereales', 'hortalizas', 'leguminosas', 'frutales', 'tuberculos', 'aromaticas');
            if (!in_array($datos['tip_categoria'], $categorias_validas)) {
                $errores[] = 'Categoría no válida';
            }
        }

        if (isset($datos['tip_ciclo_vida'])) {
            $ciclos_validos = array('anual', 'perenne', 'bianual');
            if (!in_array($datos['tip_ciclo_vida'], $ciclos_validos)) {
                $errores[] = 'Ciclo de vida no válido';
            }
        }

        return $errores;
    }

    /**
     * Obtener lista de categorías disponibles
     */
    public function obtenerCategoriasDisponibles() {
        return array(
            'cereales' => 'Cereales',
            'hortalizas' => 'Hortalizas',
            'leguminosas' => 'Leguminosas',
            'frutales' => 'Frutales',
            'tuberculos' => 'Tubérculos',
            'aromaticas' => 'Aromáticas'
        );
    }

    /**
     * Obtener lista de ciclos de vida disponibles
     */
    public function obtenerCiclosVidaDisponibles() {
        return array(
            'anual' => 'Anual',
            'perenne' => 'Perenne',
            'bianual' => 'Bianual'
        );
    }

    /**
     * Crear tabla tipos_cultivos si no existe con todos los campos necesarios
     */
    public function crearTablaCompleta() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS tipos_cultivos (
                tip_id INT AUTO_INCREMENT PRIMARY KEY,
                tip_nombre VARCHAR(100) NOT NULL,
                tip_nombre_cientifico VARCHAR(150) NULL,
                tip_familia_botanica VARCHAR(100) NULL,
                tip_ciclo_vida ENUM('anual', 'perenne', 'bianual') DEFAULT 'anual',
                tip_ciclo_dias INT NULL,
                tip_categoria ENUM('cereales', 'hortalizas', 'leguminosas', 'frutales', 'tuberculos', 'aromaticas') DEFAULT 'hortalizas',
                tip_descripcion TEXT NULL,
                tip_temperatura_min DECIMAL(4,1) NULL,
                tip_temperatura_max DECIMAL(4,1) NULL,
                tip_precipitacion VARCHAR(100) NULL,
                tip_tipo_suelo VARCHAR(100) NULL,
                tip_ph_min DECIMAL(3,1) NULL,
                tip_ph_max DECIMAL(3,1) NULL,
                tip_densidad_siembra VARCHAR(50) NULL,
                tip_profundidad_siembra VARCHAR(50) NULL,
                tip_requerimientos_agua TEXT NULL,
                tip_requerimientos_suelo TEXT NULL,
                tip_temperatura_optima VARCHAR(50) NULL,
                tip_estado ENUM('activo', 'inactivo') DEFAULT 'activo',
                tip_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_nombre (tip_nombre),
                INDEX idx_categoria (tip_categoria),
                INDEX idx_estado (tip_estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                return array('success' => true, 'message' => 'Tabla tipos_cultivos creada/verificada exitosamente');
            } else {
                return array('success' => false, 'message' => 'Error al crear tabla: ' . $this->conexion->getMysqli()->error);
            }
        } catch (Exception $e) {
            error_log("Error en crearTablaCompleta: " . $e->getMessage());
            return array('success' => false, 'message' => 'Error interno del servidor');
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