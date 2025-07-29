<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/reportes_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();

// Inicializar modelo de reportes
$reportes_modelo = new Reportes();

// Obtener estadísticas generales
$estadisticas = $reportes_modelo->obtenerEstadisticasGenerales($usuario_actual['id'], $usuario_actual['rol']);

// Verificar permisos - Todos los roles pueden acceder a reportes, pero con diferentes niveles
$permisos = [
    'administrador' => [
        'dashboard_global' => true,
        'dashboard_personal' => true,
        'reportes_produccion' => true,
        'reportes_financieros' => true,
        'reportes_tecnicos' => true,
        'exportar_pdf' => true,
        'exportar_excel' => true,
        'exportar_csv' => true
    ],
    'agricultor' => [
        'dashboard_global' => false,
        'dashboard_personal' => true,
        'reportes_produccion' => true,
        'reportes_financieros' => true,
        'reportes_tecnicos' => true,
        'exportar_pdf' => true,
        'exportar_excel' => true,
        'exportar_csv' => true
    ],
    'supervisor' => [
        'dashboard_global' => false,
        'dashboard_personal' => true,
        'reportes_produccion' => true,
        'reportes_financieros' => true,
        'reportes_tecnicos' => true,
        'exportar_pdf' => true,
        'exportar_excel' => true,
        'exportar_csv' => false
    ]
];

$permisos_usuario = $permisos[$usuario_actual['rol']] ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Dashboard - AgroMonitor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="partials/CSS/navbar.css" rel="stylesheet">
    
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link href="CSS/reportes.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include_once('partials/navbar.php'); ?>

    <!-- Header Principal -->
    <div class="main-header" style="margin-top: 80px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-chart-line me-3"></i>
                        Reportes y Dashboard
                    </h1>
                    <p class="mb-0 opacity-75">
                        Sistema integral de reportes y análisis para el monitoreo de cultivos
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex justify-content-end export-buttons">
                        <?php if ($permisos_usuario['exportar_pdf']): ?>
                        <button class="btn btn-outline-light btn-sm" id="exportarPDF">
                            <i class="fas fa-file-pdf me-2"></i>PDF
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($permisos_usuario['exportar_excel']): ?>
                        <button class="btn btn-outline-light btn-sm" id="exportarExcel">
                            <i class="fas fa-file-excel me-2"></i>Excel
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($permisos_usuario['exportar_csv']): ?>
                        <button class="btn btn-outline-light btn-sm" id="exportarCSV">
                            <i class="fas fa-file-csv me-2"></i>CSV
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Navegación por pestañas -->
        <ul class="nav nav-tabs" id="reportesTabs" role="tablist">
            <?php if ($permisos_usuario['reportes_produccion']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="produccion-tab" data-bs-toggle="tab" data-bs-target="#produccion" type="button" role="tab">
                    <i class="fas fa-seedling me-2"></i>Producción
                </button>
            </li>
            <?php endif; ?>
            
            <?php if ($permisos_usuario['reportes_financieros']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo !$permisos_usuario['reportes_produccion'] ? 'active' : ''; ?>" id="financiero-tab" data-bs-toggle="tab" data-bs-target="#financiero" type="button" role="tab">
                    <i class="fas fa-dollar-sign me-2"></i>Financiero
                </button>
            </li>
            <?php endif; ?>
            
            <?php if ($permisos_usuario['reportes_tecnicos']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo !$permisos_usuario['reportes_produccion'] && !$permisos_usuario['reportes_financieros'] ? 'active' : ''; ?>" id="tecnico-tab" data-bs-toggle="tab" data-bs-target="#tecnico" type="button" role="tab">
                    <i class="fas fa-cogs me-2"></i>Técnico
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Contenido de las pestañas -->
        <div class="tab-content" id="reportesTabContent">

            <!-- Reportes de Producción -->
            <?php if ($permisos_usuario['reportes_produccion']): ?>
            <div class="tab-pane fade show active" id="produccion" role="tabpanel">
                <!-- Filtros de Producción -->
                <div class="filter-section">
                    <h5><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fechaInicioProduccion">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fechaFinProduccion">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cultivo</label>
                            <select class="form-select" id="cultivoProduccion">
                                <option value="">Todos los cultivos</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lote</label>
                            <select class="form-select" id="loteProduccion">
                                <option value="">Todos los lotes</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button class="btn btn-primary" id="filtrarProduccion">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                            <button class="btn btn-outline-primary ms-2" id="limpiarFiltrosProduccion">
                                <i class="fas fa-times me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pestañas de Reportes de Producción -->
                <ul class="nav nav-pills mb-3" id="produccionSubTabs">
                    <li class="nav-item">
                        <button class="nav-link active" id="cosechas-subtab" data-bs-toggle="pill" data-bs-target="#cosechas-content">
                            <i class="fas fa-harvest me-2"></i>Reporte de Cosechas
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="rendimiento-subtab" data-bs-toggle="pill" data-bs-target="#rendimiento-content">
                            <i class="fas fa-chart-area me-2"></i>Reporte de Rendimiento
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Reporte de Cosechas -->
                    <div class="tab-pane fade show active" id="cosechas-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Reporte de Cosechas</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingCosechas">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaCosechas">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Cultivo</th>
                                                <th>Lote</th>
                                                <th>Cantidad</th>
                                                <th>Calidad</th>
                                                <th>Precio Unit.</th>
                                                <th>Total Ingresos</th>
                                                <th>Rendimiento/Ha</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Rendimiento -->
                    <div class="tab-pane fade" id="rendimiento-content">
                        <!-- Gráfico de Rendimiento por Lote -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            Rendimiento por Lote
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="rendimientoLoteChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabla de Rendimiento por Cultivo -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Reporte de Rendimiento por Cultivo</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingRendimiento">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaRendimiento">
                                        <thead>
                                            <tr>
                                                <th>Cultivo</th>
                                                <th>Categoría</th>
                                                <th>Total Cosechas</th>
                                                <th>Cantidad Total</th>
                                                <th>Promedio por Cosecha</th>
                                                <th>Rendimiento/Ha</th>
                                                <th>Días Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reportes Financieros -->
            <?php if ($permisos_usuario['reportes_financieros']): ?>
            <div class="tab-pane fade" id="financiero" role="tabpanel">
                <!-- Mensaje de permisos para supervisores -->
                <?php if ($usuario_actual['rol'] === 'supervisor'): ?>
                <div class="permission-note">
                    <i class="fas fa-info-circle me-2"></i>
                    Como supervisor, tienes acceso de solo lectura a los reportes financieros.
                </div>
                <?php endif; ?>

                <!-- Pestañas de Reportes Financieros -->
                <ul class="nav nav-pills mb-3" id="financieroSubTabs">
                    <li class="nav-item">
                        <button class="nav-link active" id="resultados-subtab" data-bs-toggle="pill" data-bs-target="#resultados-content">
                            <i class="fas fa-balance-scale me-2"></i>Estado de Resultados
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="costos-subtab" data-bs-toggle="pill" data-bs-target="#costos-content">
                            <i class="fas fa-calculator me-2"></i>Costos de Producción
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="flujo-subtab" data-bs-toggle="pill" data-bs-target="#flujo-content">
                            <i class="fas fa-stream me-2"></i>Flujo de Caja
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Estado de Resultados -->
                    <div class="tab-pane fade show active" id="resultados-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Estado de Resultados por Cultivo</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingResultados">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaResultados">
                                        <thead>
                                            <tr>
                                                <th>Cultivo</th>
                                                <th>Siembras</th>
                                                <th>Ingresos</th>
                                                <th>Gastos</th>
                                                <th>Utilidad</th>
                                                <th>Margen %</th>
                                                <th>Área Total</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Costos de Producción Detallados -->
                    <div class="tab-pane fade" id="costos-content">
                        <!-- Gráfico de Costos por Categoría -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            Distribución de Costos
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="costosCategoriaChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-line me-2"></i>
                                            Evolución de Costos
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="costosEvolucionChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Costos Detallados -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Análisis Detallado de Costos de Producción</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingCostos">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaCostos">
                                        <thead>
                                            <tr>
                                                <th>Cultivo</th>
                                                <th>Lote</th>
                                                <th>Semillas</th>
                                                <th>Fertilizantes</th>
                                                <th>Pesticidas</th>
                                                <th>Mano de Obra</th>
                                                <th>Maquinaria</th>
                                                <th>Otros</th>
                                                <th>Total</th>
                                                <th>Costo/Ha</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flujo de Caja -->
                    <div class="tab-pane fade" id="flujo-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Flujo de Caja Mensual</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="flujoCajaChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reportes Técnicos -->
            <?php if ($permisos_usuario['reportes_tecnicos']): ?>
            <div class="tab-pane fade" id="tecnico" role="tabpanel">
                <!-- Pestañas de Reportes Técnicos -->
                <ul class="nav nav-pills mb-3" id="tecnicoSubTabs">
                    <li class="nav-item">
                        <button class="nav-link active" id="actividades-subtab" data-bs-toggle="pill" data-bs-target="#actividades-content">
                            <i class="fas fa-tasks me-2"></i>Historial de Actividades
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="monitoreo-subtab" data-bs-toggle="pill" data-bs-target="#monitoreo-content">
                            <i class="fas fa-eye me-2"></i>Registro de Monitoreo
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="fitosanitario-subtab" data-bs-toggle="pill" data-bs-target="#fitosanitario-content">
                            <i class="fas fa-shield-alt me-2"></i>Control Fitosanitario
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="insumos-subtab" data-bs-toggle="pill" data-bs-target="#insumos-content">
                            <i class="fas fa-flask me-2"></i>Uso de Insumos
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Historial de Actividades -->
                    <div class="tab-pane fade show active" id="actividades-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Historial de Actividades por Lote</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingActividades">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaActividades">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Lote</th>
                                                <th>Cultivo</th>
                                                <th>Descripción</th>
                                                <th>Productos</th>
                                                <th>Costo</th>
                                                <th>Responsable</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registro de Monitoreo -->
                    <div class="tab-pane fade" id="monitoreo-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Registro Completo de Monitoreo</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingMonitoreo">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaMonitoreo">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Lote</th>
                                                <th>Cultivo</th>
                                                <th>Estado General</th>
                                                <th>Altura Prom.</th>
                                                <th>% Germinación</th>
                                                <th>Plagas</th>
                                                <th>Enfermedades</th>
                                                <th>Responsable</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Control Fitosanitario -->
                    <div class="tab-pane fade" id="fitosanitario-content">
                        <!-- Estadísticas de Control Fitosanitario -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card bg-success-light">
                                    <div class="stats-number text-success" id="cultivos-sanos">-</div>
                                    <div class="stats-label">Cultivos Sanos</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-warning-light">
                                    <div class="stats-number text-warning" id="plagas-detectadas">-</div>
                                    <div class="stats-label">Plagas Detectadas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-danger-light">
                                    <div class="stats-number text-danger" id="enfermedades-activas">-</div>
                                    <div class="stats-label">Enfermedades Activas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-info-light">
                                    <div class="stats-number text-info" id="tratamientos-aplicados">-</div>
                                    <div class="stats-label">Tratamientos Aplicados</div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráficos de Control Fitosanitario -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-bug me-2"></i>
                                            Incidencia de Plagas
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="plagasChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-virus me-2"></i>
                                            Enfermedades por Lote
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="enfermedadesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Efectividad de Tratamientos -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-line me-2"></i>
                                            Efectividad de Tratamientos Fitosanitarios
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="efectividadTratamientosChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Control Fitosanitario Detallado -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Registro Detallado de Control Fitosanitario</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingFitosanitario">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaFitosanitario">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Lote</th>
                                                <th>Cultivo</th>
                                                <th>Problema Detectado</th>
                                                <th>Tipo</th>
                                                <th>Severidad</th>
                                                <th>Tratamiento Aplicado</th>
                                                <th>Producto Utilizado</th>
                                                <th>Dosificación</th>
                                                <th>Efectividad</th>
                                                <th>Fecha Seguimiento</th>
                                                <th>Responsable</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Uso de Insumos -->
                    <div class="tab-pane fade" id="insumos-content">
                        <!-- Filtros para Uso de Insumos -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>
                                    Filtros de Búsqueda - Uso de Insumos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="fechaInicioInsumos" class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="fechaInicioInsumos">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="fechaFinInsumos" class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" id="fechaFinInsumos">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tipoInsumo" class="form-label">Tipo de Insumo</label>
                                        <select class="form-select" id="tipoInsumo">
                                            <option value="">Todos los tipos</option>
                                            <option value="semillas">Semillas</option>
                                            <option value="fertilizantes">Fertilizantes</option>
                                            <option value="pesticidas">Pesticidas</option>
                                            <option value="herbicidas">Herbicidas</option>
                                            <option value="fungicidas">Fungicidas</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="cultivoInsumos" class="form-label">Cultivo</label>
                                        <select class="form-select" id="cultivoInsumos">
                                            <option value="">Todos los cultivos</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-primary" onclick="cargarReporteInsumos()">
                                            <i class="fas fa-search me-2"></i>Buscar
                                        </button>
                                        <button type="button" class="btn btn-secondary ms-2" onclick="limpiarFiltrosInsumos()">
                                            <i class="fas fa-eraser me-2"></i>Limpiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas de Uso de Insumos -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card bg-primary-light">
                                    <div class="stats-number text-primary" id="total-insumos">-</div>
                                    <div class="stats-label">Total Insumos</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-success-light">
                                    <div class="stats-number text-success" id="costo-insumos">$-</div>
                                    <div class="stats-label">Costo Total</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-warning-light">
                                    <div class="stats-number text-warning" id="promedio-costo">$-</div>
                                    <div class="stats-label">Costo Promedio</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-info-light">
                                    <div class="stats-number text-info" id="lotes-aplicados">-</div>
                                    <div class="stats-label">Lotes Aplicados</div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Distribución de Insumos -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            Distribución por Tipo de Insumo
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="tipoInsumosChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-bar me-2"></i>
                                            Costo por Cultivo
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="costoCultivoChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Uso de Insumos -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Detalle de Uso de Insumos</h5>
                            </div>
                            <div class="card-body">
                                <div class="loading-spinner" id="loadingInsumos">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="tablaInsumos">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Nombre del Insumo</th>
                                                <th>Cultivo</th>
                                                <th>Cantidad Total</th>
                                                <th>Unidad</th>
                                                <th>Costo Total</th>
                                                <th>Costo Unitario</th>
                                                <th>Lotes Aplicados</th>
                                                <th>Eficiencia de Uso</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include_once('partials/footer.php'); ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    
<!-- Variables globales para JavaScript -->
<script>
    // Pasar datos PHP a JavaScript
    window.PERMISOS_USUARIO = <?php echo json_encode($permisos_usuario); ?>;
    window.ROL_USUARIO = '<?php echo $usuario_actual['rol']; ?>';
</script>

<!-- Script personalizado de reportes -->
<script src="JS/reportes.js"></script>
</body>
</html>