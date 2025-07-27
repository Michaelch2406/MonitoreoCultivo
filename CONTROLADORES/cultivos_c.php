<?php
session_start();
require_once(dirname(__FILE__) . "/../CONFIG/global.php");
require_once(dirname(__FILE__) . "/../CONFIG/roles.php");
require_once(dirname(__FILE__) . "/../MODELOS/cultivos_m.php");

class CultivosController {
    private $cultivoModel;
    private $permisos;

    public function __construct() {
        // Verificar que hay sesión activa
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header("Location: ../VISTAS/login.php");
            exit();
        }
        
        $this->cultivoModel = new Cultivo();
        $this->permisos = obtenerPermisosUsuario($_SESSION['rol']);
    }

    /**
     * Mostrar página principal de cultivos
     */
    public function index() {
        try {
            // Verificar permisos de lectura
            if (!isset($this->permisos['cultivos']['ver']) || !$this->permisos['cultivos']['ver']) {
                $this->redirectConError("No tienes permisos para ver el catálogo de cultivos");
                return;
            }

            // Obtener todos los cultivos
            $resultado = $this->cultivoModel->obtenerTodosTiposCultivos();
            $cultivos = $resultado['success'] ? $resultado['cultivos'] : array();

            // Obtener categorías con contadores
            $resultado_categorias = $this->cultivoModel->obtenerCultivosPorCategoria();
            $categorias = $resultado_categorias['success'] ? $resultado_categorias['categorias'] : array();

            // Obtener listas de opciones
            $categorias_disponibles = $this->cultivoModel->obtenerCategoriasDisponibles();
            $ciclos_vida = $this->cultivoModel->obtenerCiclosVidaDisponibles();

            $data = array(
                'cultivos' => $cultivos,
                'categorias' => $categorias,
                'categorias_disponibles' => $categorias_disponibles,
                'ciclos_vida' => $ciclos_vida,
                'permisos' => $this->permisos['cultivos'],
                'usuario' => $_SESSION,
                'titulo' => 'Catálogo de Cultivos',
                'mensaje' => isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : null
            );

            // Limpiar mensaje después de mostrarlo
            unset($_SESSION['mensaje']);

            $this->cargarVista('cultivos', $data);

        } catch (Exception $e) {
            error_log("Error en CultivosController::index: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Ver detalles de un cultivo específico
     */
    public function ver($tip_id) {
        try {
            if (!isset($this->permisos['cultivos']['ver']) || !$this->permisos['cultivos']['ver']) {
                $this->redirectConError("No tienes permisos para ver detalles de cultivos");
                return;
            }

            $resultado = $this->cultivoModel->obtenerTipoCultivoPorId($tip_id);
            
            if (!$resultado['success']) {
                $this->redirectConError("Cultivo no encontrado");
                return;
            }

            $data = array(
                'cultivo' => $resultado['cultivo'],
                'permisos' => $this->permisos['cultivos'],
                'usuario' => $_SESSION,
                'titulo' => 'Detalles del Cultivo'
            );

            $this->cargarVista('cultivo_detalle', $data);

        } catch (Exception $e) {
            error_log("Error en CultivosController::ver: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Mostrar formulario para crear nuevo cultivo
     */
    public function crear() {
        try {
            if (!isset($this->permisos['cultivos']['crear']) || !$this->permisos['cultivos']['crear']) {
                $this->redirectConError("No tienes permisos para crear cultivos");
                return;
            }

            $data = array(
                'categorias_disponibles' => $this->cultivoModel->obtenerCategoriasDisponibles(),
                'ciclos_vida' => $this->cultivoModel->obtenerCiclosVidaDisponibles(),
                'permisos' => $this->permisos['cultivos'],
                'usuario' => $_SESSION,
                'titulo' => 'Crear Nuevo Cultivo',
                'accion' => 'crear'
            );

            $this->cargarVista('cultivo_form', $data);

        } catch (Exception $e) {
            error_log("Error en CultivosController::crear: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Mostrar formulario para editar cultivo
     */
    public function editar($tip_id) {
        try {
            if (!isset($this->permisos['cultivos']['editar']) || !$this->permisos['cultivos']['editar']) {
                $this->redirectConError("No tienes permisos para editar cultivos");
                return;
            }

            $resultado = $this->cultivoModel->obtenerTipoCultivoPorId($tip_id);
            
            if (!$resultado['success']) {
                $this->redirectConError("Cultivo no encontrado");
                return;
            }

            $data = array(
                'cultivo' => $resultado['cultivo'],
                'categorias_disponibles' => $this->cultivoModel->obtenerCategoriasDisponibles(),
                'ciclos_vida' => $this->cultivoModel->obtenerCiclosVidaDisponibles(),
                'permisos' => $this->permisos['cultivos'],
                'usuario' => $_SESSION,
                'titulo' => 'Editar Cultivo',
                'accion' => 'editar'
            );

            $this->cargarVista('cultivo_form', $data);

        } catch (Exception $e) {
            error_log("Error en CultivosController::editar: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Procesar formulario de creación/edición
     */
    public function guardar() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectConError("Método no permitido");
                return;
            }

            $accion = $_POST['accion'] ?? '';
            $tip_id = $_POST['tip_id'] ?? null;

            // Verificar permisos según la acción
            if ($accion === 'crear') {
                if (!isset($this->permisos['cultivos']['crear']) || !$this->permisos['cultivos']['crear']) {
                    $this->redirectConError("No tienes permisos para crear cultivos");
                    return;
                }
            } elseif ($accion === 'editar') {
                if (!isset($this->permisos['cultivos']['editar']) || !$this->permisos['cultivos']['editar']) {
                    $this->redirectConError("No tienes permisos para editar cultivos");
                    return;
                }
            } else {
                $this->redirectConError("Acción no válida");
                return;
            }

            // Recopilar datos del formulario
            $datos = array(
                'tip_nombre' => trim($_POST['tip_nombre'] ?? ''),
                'tip_nombre_cientifico' => trim($_POST['tip_nombre_cientifico'] ?? ''),
                'tip_familia_botanica' => trim($_POST['tip_familia_botanica'] ?? ''),
                'tip_ciclo_vida' => $_POST['tip_ciclo_vida'] ?? 'anual',
                'tip_ciclo_dias' => $_POST['tip_ciclo_dias'] ?? null,
                'tip_categoria' => $_POST['tip_categoria'] ?? 'hortalizas',
                'tip_descripcion' => trim($_POST['tip_descripcion'] ?? ''),
                'tip_temperatura_min' => $_POST['tip_temperatura_min'] ?? null,
                'tip_temperatura_max' => $_POST['tip_temperatura_max'] ?? null,
                'tip_precipitacion' => trim($_POST['tip_precipitacion'] ?? ''),
                'tip_tipo_suelo' => trim($_POST['tip_tipo_suelo'] ?? ''),
                'tip_ph_min' => $_POST['tip_ph_min'] ?? null,
                'tip_ph_max' => $_POST['tip_ph_max'] ?? null,
                'tip_densidad_siembra' => trim($_POST['tip_densidad_siembra'] ?? ''),
                'tip_profundidad_siembra' => trim($_POST['tip_profundidad_siembra'] ?? ''),
                'tip_requerimientos_agua' => trim($_POST['tip_requerimientos_agua'] ?? ''),
                'tip_requerimientos_suelo' => trim($_POST['tip_requerimientos_suelo'] ?? ''),
                'tip_temperatura_optima' => trim($_POST['tip_temperatura_optima'] ?? '')
            );

            // Limpiar campos vacíos (convertir a null)
            foreach ($datos as $key => $value) {
                if (is_string($value) && $value === '') {
                    $datos[$key] = null;
                }
            }

            // Validar datos
            $errores = $this->cultivoModel->validarDatosCultivo($datos);
            if (!empty($errores)) {
                $_SESSION['errores'] = $errores;
                $_SESSION['datos_form'] = $datos;
                
                if ($accion === 'crear') {
                    header("Location: ../VISTAS/cultivos.php?action=crear");
                } else {
                    header("Location: ../VISTAS/cultivos.php?action=editar&id=$tip_id");
                }
                exit();
            }

            // Ejecutar acción
            if ($accion === 'crear') {
                $resultado = $this->cultivoModel->crearTipoCultivo($datos);
            } else {
                $resultado = $this->cultivoModel->actualizarTipoCultivo($tip_id, $datos);
            }

            if ($resultado['success']) {
                $_SESSION['mensaje'] = array(
                    'tipo' => 'success',
                    'texto' => $resultado['message']
                );
            } else {
                $_SESSION['mensaje'] = array(
                    'tipo' => 'error',
                    'texto' => $resultado['message']
                );
            }

            header("Location: ../VISTAS/cultivos.php");
            exit();

        } catch (Exception $e) {
            error_log("Error en CultivosController::guardar: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Eliminar cultivo
     */
    public function eliminar($tip_id) {
        try {
            if (!isset($this->permisos['cultivos']['eliminar']) || !$this->permisos['cultivos']['eliminar']) {
                $this->redirectConError("No tienes permisos para eliminar cultivos");
                return;
            }

            $resultado = $this->cultivoModel->eliminarTipoCultivo($tip_id);

            if ($resultado['success']) {
                $_SESSION['mensaje'] = array(
                    'tipo' => 'success',
                    'texto' => $resultado['message']
                );
            } else {
                $_SESSION['mensaje'] = array(
                    'tipo' => 'error',
                    'texto' => $resultado['message']
                );
            }

            header("Location: ../VISTAS/cultivos.php");
            exit();

        } catch (Exception $e) {
            error_log("Error en CultivosController::eliminar: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * Cambiar estado de cultivo (activar/desactivar)
     */
    public function cambiarEstado($tip_id, $nuevo_estado) {
        try {
            if (!isset($this->permisos['cultivos']['editar']) || !$this->permisos['cultivos']['editar']) {
                echo json_encode(array('success' => false, 'message' => 'No tienes permisos para cambiar el estado'));
                return;
            }

            $resultado = $this->cultivoModel->cambiarEstadoTipoCultivo($tip_id, $nuevo_estado);
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en CultivosController::cambiarEstado: " . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => 'Error interno del servidor'));
        }
    }

    /**
     * Buscar cultivos (AJAX)
     */
    public function buscar() {
        try {
            if (!isset($this->permisos['cultivos']['ver']) || !$this->permisos['cultivos']['ver']) {
                echo json_encode(array('success' => false, 'message' => 'No tienes permisos para buscar cultivos'));
                return;
            }

            $termino = $_GET['q'] ?? '';
            $categoria = $_GET['categoria'] ?? null;

            if (strlen($termino) < 2) {
                echo json_encode(array('success' => false, 'message' => 'El término de búsqueda debe tener al menos 2 caracteres'));
                return;
            }

            $resultado = $this->cultivoModel->buscarCultivos($termino, $categoria);
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en CultivosController::buscar: " . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => 'Error interno del servidor'));
        }
    }

    /**
     * Obtener cultivos por categoría (AJAX)
     */
    public function porCategoria($categoria) {
        try {
            if (!isset($this->permisos['cultivos']['ver']) || !$this->permisos['cultivos']['ver']) {
                echo json_encode(array('success' => false, 'message' => 'No tienes permisos para ver cultivos'));
                return;
            }

            $resultado = $this->cultivoModel->obtenerTodosTiposCultivos($categoria);
            echo json_encode($resultado);

        } catch (Exception $e) {
            error_log("Error en CultivosController::porCategoria: " . $e->getMessage());
            echo json_encode(array('success' => false, 'message' => 'Error interno del servidor'));
        }
    }

    /**
     * Exportar catálogo de cultivos (CSV/PDF)
     */
    public function exportar($formato = 'csv') {
        try {
            if (!isset($this->permisos['cultivos']['ver']) || !$this->permisos['cultivos']['ver']) {
                $this->redirectConError("No tienes permisos para exportar cultivos");
                return;
            }

            $resultado = $this->cultivoModel->obtenerTodosTiposCultivos();
            
            if (!$resultado['success']) {
                $this->redirectConError("Error al obtener datos para exportar");
                return;
            }

            $cultivos = $resultado['cultivos'];

            if ($formato === 'csv') {
                $this->exportarCSV($cultivos);
            } elseif ($formato === 'pdf') {
                $this->exportarPDF($cultivos);
            } else {
                $this->redirectConError("Formato de exportación no válido");
            }

        } catch (Exception $e) {
            error_log("Error en CultivosController::exportar: " . $e->getMessage());
            $this->redirectConError("Error interno del servidor");
        }
    }

    /**
     * MÉTODOS AUXILIARES
     */

    private function exportarCSV($cultivos) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="catalogo_cultivos_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Encabezados
        fputcsv($output, array(
            'ID', 'Nombre', 'Nombre Científico', 'Familia Botánica', 'Categoría',
            'Ciclo de Vida', 'Días del Ciclo', 'Temp. Mín (°C)', 'Temp. Máx (°C)',
            'pH Mín', 'pH Máx', 'Tipo de Suelo', 'Densidad Siembra', 'Estado'
        ));
        
        // Datos
        foreach ($cultivos as $cultivo) {
            fputcsv($output, array(
                $cultivo['tip_id'],
                $cultivo['tip_nombre'],
                $cultivo['tip_nombre_cientifico'],
                $cultivo['tip_familia_botanica'],
                ucfirst($cultivo['tip_categoria']),
                ucfirst($cultivo['tip_ciclo_vida']),
                $cultivo['tip_ciclo_dias'],
                $cultivo['tip_temperatura_min'],
                $cultivo['tip_temperatura_max'],
                $cultivo['tip_ph_min'],
                $cultivo['tip_ph_max'],
                $cultivo['tip_tipo_suelo'],
                $cultivo['tip_densidad_siembra'],
                ucfirst($cultivo['tip_estado'])
            ));
        }
        
        fclose($output);
    }

    private function exportarPDF($cultivos) {
        // Implementación básica - se puede mejorar con librerías como TCPDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="catalogo_cultivos_' . date('Y-m-d') . '.pdf"');
        
        // Por ahora, generamos un HTML simple que se puede imprimir como PDF
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Catálogo de Cultivos</title></head><body>";
        echo "<h1>Catálogo de Cultivos - " . date('d/m/Y') . "</h1>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Nombre</th><th>Categoría</th><th>Ciclo</th><th>Días</th><th>Estado</th></tr>";
        
        foreach ($cultivos as $cultivo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($cultivo['tip_nombre']) . "</td>";
            echo "<td>" . ucfirst($cultivo['tip_categoria']) . "</td>";
            echo "<td>" . ucfirst($cultivo['tip_ciclo_vida']) . "</td>";
            echo "<td>" . ($cultivo['tip_ciclo_dias'] ?? 'N/A') . "</td>";
            echo "<td>" . ucfirst($cultivo['tip_estado']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table></body></html>";
    }

    private function cargarVista($vista, $data = array()) {
        extract($data);
        include "../VISTAS/{$vista}.php";
    }

    private function redirectConError($mensaje) {
        $_SESSION['mensaje'] = array(
            'tipo' => 'error',
            'texto' => $mensaje
        );
        header("Location: ../VISTAS/cultivos.php");
        exit();
    }
}

// Manejo de rutas básico
if (isset($_GET['action'])) {
    $controller = new CultivosController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'ver':
            $tip_id = $_GET['id'] ?? 0;
            $controller->ver($tip_id);
            break;
        case 'crear':
            $controller->crear();
            break;
        case 'editar':
            $tip_id = $_GET['id'] ?? 0;
            $controller->editar($tip_id);
            break;
        case 'guardar':
            $controller->guardar();
            break;
        case 'eliminar':
            $tip_id = $_GET['id'] ?? 0;
            $controller->eliminar($tip_id);
            break;
        case 'cambiar_estado':
            $tip_id = $_GET['id'] ?? 0;
            $estado = $_GET['estado'] ?? '';
            $controller->cambiarEstado($tip_id, $estado);
            break;
        case 'buscar':
            $controller->buscar();
            break;
        case 'categoria':
            $categoria = $_GET['cat'] ?? '';
            $controller->porCategoria($categoria);
            break;
        case 'exportar':
            $formato = $_GET['formato'] ?? 'csv';
            $controller->exportar($formato);
            break;
        default:
            $controller->index();
            break;
    }
} else {
    $controller = new CultivosController();
    $controller->index();
}
?>