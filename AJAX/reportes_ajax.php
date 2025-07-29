<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('../CONFIG/roles.php');
require_once('../MODELOS/reportes_m.php');

try {
    // Verificar que el usuario esté logueado
    if (!estaLogueado()) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];
    $rol = $usuario_actual['rol'];

    // Crear instancia del modelo
    $reportes_modelo = new Reportes();

    // Obtener acción
    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

    switch ($accion) {
        case 'dashboard_general':
            $resultado = $reportes_modelo->obtenerEstadisticasGenerales($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'produccion_mensual':
            $año = $_GET['año'] ?? date('Y');
            $resultado = $reportes_modelo->obtenerProduccionMensual($usuario_id, $rol, $año);
            echo json_encode($resultado);
            break;

        case 'gastos_ingresos':
            $periodo = $_GET['periodo'] ?? '12_meses';
            $resultado = $reportes_modelo->obtenerGastosVsIngresos($usuario_id, $rol, $periodo);
            echo json_encode($resultado);
            break;

        case 'distribucion_cultivos':
            $resultado = $reportes_modelo->obtenerDistribucionCultivos($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'rendimiento_lotes':
            $resultado = $reportes_modelo->obtenerRendimientoLotes($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'reporte_cosechas':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null,
                'lote_id' => $_GET['lote_id'] ?? null,
                'calidad' => $_GET['calidad'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->reporteCosechas($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'reporte_rendimiento':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->reporteRendimiento($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'estado_resultados':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->estadoResultados($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'flujo_caja':
            $periodo = $_GET['periodo'] ?? 'mensual';
            $año = $_GET['año'] ?? date('Y');
            $resultado = $reportes_modelo->flujoCaja($usuario_id, $rol, $periodo, $año);
            echo json_encode($resultado);
            break;

        case 'costos_categoria':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->obtenerCostosPorCategoria($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'costos_evolucion':
            $periodo = $_GET['periodo'] ?? '12_meses';
            $resultado = $reportes_modelo->obtenerEvolucionCostos($usuario_id, $rol, $periodo);
            echo json_encode($resultado);
            break;

        case 'costos_detallados':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null,
                'lote_id' => $_GET['lote_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->obtenerCostosDetallados($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'historial_actividades':
            $filtros = [
                'lote_id' => $_GET['lote_id'] ?? null,
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'tipo_actividad' => $_GET['tipo_actividad'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->historialActividades($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'registro_monitoreo':
            $filtros = [
                'lote_id' => $_GET['lote_id'] ?? null,
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->registroMonitoreo($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'cultivos_filtro':
            $resultado = $reportes_modelo->obtenerCultivosParaFiltro($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'lotes_filtro':
            $resultado = $reportes_modelo->obtenerLotesParaFiltro($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'control_plagas':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'lote_id' => $_GET['lote_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->obtenerControlPlagas($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'control_enfermedades':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'lote_id' => $_GET['lote_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->obtenerControlEnfermedades($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'efectividad_tratamientos':
            $periodo = $_GET['periodo'] ?? '12_meses';
            $resultado = $reportes_modelo->obtenerEfectividadTratamientos($usuario_id, $rol, $periodo);
            echo json_encode($resultado);
            break;

        case 'uso_insumos':
            $filtros = [
                'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
                'fecha_fin' => $_GET['fecha_fin'] ?? null,
                'tipo_insumo' => $_GET['tipo_insumo'] ?? null,
                'cultivo_id' => $_GET['cultivo_id'] ?? null
            ];
            $filtros = array_filter($filtros);
            $resultado = $reportes_modelo->obtenerUsoInsumos($usuario_id, $rol, $filtros);
            echo json_encode($resultado);
            break;

        case 'estadisticas_fitosanitarias':
            $resultado = $reportes_modelo->obtenerEstadisticasFitosanitarias($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        case 'usuarios_activos':
            $resultado = $reportes_modelo->obtenerUsuariosActivos($usuario_id, $rol);
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    error_log("Error en reportes_ajax.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>