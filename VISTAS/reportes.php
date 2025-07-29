<?php
session_start();
require_once('../CONFIG/roles.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();

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
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #2E7D32;
            --secondary-green: #4CAF50;
            --light-green: #81C784;
            --earth-brown: #8D6E63;
            --water-blue: #1976D2;
            --sun-yellow: #FFA726;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --text-gray: #424242;
            --hover-green: #1B5E20;
            --shadow-light: rgba(46, 125, 50, 0.1);
            --shadow-medium: rgba(46, 125, 50, 0.2);
            --shadow-heavy: rgba(46, 125, 50, 0.3);
            --error-red: #F44336;
            --success-green: #4CAF50;
            --warning-orange: #FF9800;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-gray);
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px var(--shadow-medium);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px var(--shadow-light);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px var(--shadow-medium);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
            border: none;
        }

        .nav-tabs {
            border-bottom: 2px solid var(--primary-green);
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            color: var(--text-gray);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-green);
            border-color: transparent;
        }

        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border-radius: 8px 8px 0 0;
            border-color: var(--primary-green);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--hover-green), var(--primary-green));
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            color: var(--primary-green);
            border-color: var(--primary-green);
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border-left: 4px solid var(--secondary-green);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-green);
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: var(--text-gray);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 1rem 0;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px var(--shadow-light);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            color: white;
            border: none;
            font-weight: 500;
            padding: 1rem;
        }

        .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(46, 125, 50, 0.05);
        }

        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: var(--success-green);
            background-color: rgba(76, 175, 80, 0.1);
        }

        .alert-warning {
            border-left-color: var(--warning-orange);
            background-color: rgba(255, 152, 0, 0.1);
        }

        .alert-danger {
            border-left-color: var(--error-red);
            background-color: rgba(244, 67, 54, 0.1);
        }

        .export-buttons {
            gap: 0.5rem;
        }

        .export-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .permission-note {
            background: rgba(255, 152, 0, 0.1);
            border: 1px solid var(--warning-orange);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            color: var(--text-gray);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include_once('partials/navbar.php'); ?>

    <!-- Header Principal -->
    <div class="main-header">
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
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </button>
            </li>
            
            <?php if ($permisos_usuario['reportes_produccion']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="produccion-tab" data-bs-toggle="tab" data-bs-target="#produccion" type="button" role="tab">
                    <i class="fas fa-seedling me-2"></i>Producción
                </button>
            </li>
            <?php endif; ?>
            
            <?php if ($permisos_usuario['reportes_financieros']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financiero-tab" data-bs-toggle="tab" data-bs-target="#financiero" type="button" role="tab">
                    <i class="fas fa-dollar-sign me-2"></i>Financiero
                </button>
            </li>
            <?php endif; ?>
            
            <?php if ($permisos_usuario['reportes_tecnicos']): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tecnico-tab" data-bs-toggle="tab" data-bs-target="#tecnico" type="button" role="tab">
                    <i class="fas fa-cogs me-2"></i>Técnico
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Contenido de las pestañas -->
        <div class="tab-content" id="reportesTabContent">
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- Estadísticas generales -->
                <div class="row mb-4" id="estadisticasGenerales">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number" id="totalFincas">-</div>
                            <div class="stats-label">Fincas Activas</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number" id="lotesProduccion">-</div>
                            <div class="stats-label">Lotes en Producción</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number" id="cultivosActivos">-</div>
                            <div class="stats-label">Cultivos Activos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number" id="alertasPendientes">-</div>
                            <div class="stats-label">Alertas Pendientes</div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos del Dashboard -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Producción Mensual
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="produccionMensualChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Gastos vs Ingresos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="gastosIngresosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Distribución de Cultivos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="distribucionCultivosChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reportes de Producción -->
            <?php if ($permisos_usuario['reportes_produccion']): ?>
            <div class="tab-pane fade" id="produccion" role="tabpanel">
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
    
    <!-- Script personalizado -->
    <script>
        // Configuración global
        const PERMISOS_USUARIO = <?php echo json_encode($permisos_usuario); ?>;
        const ROL_USUARIO = '<?php echo $usuario_actual['rol']; ?>';
        
        // Variables globales para los gráficos
        let produccionChart, gastosIngresosChart, distribucionChart, flujoCajaChart, rendimientoLoteChart, costosCategoriaChart, costosEvolucionChart, plagasChart, enfermedadesChart, efectividadTratamientosChart;
        
        // Inicialización cuando el documento esté listo
        $(document).ready(function() {
            inicializarDashboard();
            inicializarEventos();
            cargarDatosIniciales();
        });
        
        function inicializarDashboard() {
            // Cargar estadísticas generales
            cargarEstadisticasGenerales();
            
            // Inicializar gráficos del dashboard
            setTimeout(() => {
                inicializarGraficos();
            }, 500);
        }
        
        function inicializarEventos() {
            // Eventos de las pestañas
            $('#reportesTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).data('bs-target');
                
                switch(target) {
                    case '#produccion':
                        cargarOpcionesFiltros();
                        break;
                    case '#financiero':
                        cargarReportesFinancieros();
                        break;
                    case '#tecnico':
                        cargarReportesTecnicos();
                        break;
                }
            });
            
            // Eventos de filtros
            $('#filtrarProduccion').on('click', filtrarReportesProduccion);
            $('#limpiarFiltrosProduccion').on('click', limpiarFiltrosProduccion);
            
            // Eventos de exportación
            $('#exportarPDF').on('click', () => exportarReporte('pdf'));
            $('#exportarExcel').on('click', () => exportarReporte('excel'));
            $('#exportarCSV').on('click', () => exportarReporte('csv'));
        }
        
        function cargarDatosIniciales() {
            // Cargar datos de filtros
            cargarCultivosParaFiltro();
            cargarLotesParaFiltro();
        }
        
        function cargarEstadisticasGenerales() {
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'dashboard_general' },
                success: function(response) {
                    if (response.success) {
                        const stats = response.estadisticas;
                        
                        $('#totalFincas').text(stats.total_fincas || 0);
                        $('#lotesProduccion').text(stats.lotes_produccion || 0);
                        $('#alertasPendientes').text(stats.alertas_pendientes || 0);
                        
                        // Calcular total de cultivos activos
                        let totalCultivos = 0;
                        if (stats.cultivos_estado) {
                            totalCultivos = Object.values(stats.cultivos_estado)
                                .reduce((sum, val) => sum + parseInt(val), 0);
                        }
                        $('#cultivosActivos').text(totalCultivos);
                    }
                },
                error: function() {
                    console.error('Error al cargar estadísticas generales');
                }
            });
        }
        
        function inicializarGraficos() {
            // Gráfico de Producción Mensual
            const ctx1 = document.getElementById('produccionMensualChart').getContext('2d');
            produccionChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Producción (kg)',
                        data: [],
                        borderColor: '#2E7D32',
                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Gráfico de Gastos vs Ingresos
            const ctx2 = document.getElementById('gastosIngresosChart').getContext('2d');
            gastosIngresosChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: [],
                            backgroundColor: '#4CAF50',
                            borderRadius: 4
                        },
                        {
                            label: 'Gastos',
                            data: [],
                            backgroundColor: '#FF9800',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Gráfico de Distribución de Cultivos
            const ctx3 = document.getElementById('distribucionCultivosChart').getContext('2d');
            distribucionChart = new Chart(ctx3, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#2E7D32', '#4CAF50', '#81C784', '#1976D2', 
                            '#FFA726', '#8D6E63', '#FF9800', '#F44336'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Gráfico de Rendimiento por Lote
            const ctx4 = document.getElementById('rendimientoLoteChart');
            if (ctx4) {
                rendimientoLoteChart = new Chart(ctx4.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Rendimiento (kg/ha)',
                            data: [],
                            backgroundColor: '#2E7D32',
                            borderColor: '#1B5E20',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + 
                                               context.parsed.y.toFixed(2) + ' kg/ha';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Rendimiento (kg/ha)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Lotes'
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Distribución de Costos
            const ctx5 = document.getElementById('costosCategoriaChart');
            if (ctx5) {
                costosCategoriaChart = new Chart(ctx5.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: [],
                        datasets: [{
                            data: [],
                            backgroundColor: [
                                '#2E7D32', '#4CAF50', '#81C784', '#8D6E63', 
                                '#1976D2', '#FFA726', '#FF9800', '#F44336'
                            ],
                            borderWidth: 2,
                            borderColor: '#FFFFFF'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': $' + context.parsed.toLocaleString() + 
                                               ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Evolución de Costos
            const ctx6 = document.getElementById('costosEvolucionChart');
            if (ctx6) {
                costosEvolucionChart = new Chart(ctx6.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Total Costos',
                            data: [],
                            borderColor: '#2E7D32',
                            backgroundColor: 'rgba(46, 125, 50, 0.1)',
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#2E7D32',
                            pointBorderColor: '#FFFFFF',
                            pointBorderWidth: 2,
                            pointRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + 
                                               context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Costos ($)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Período'
                                }
                            }
                        }
                    }
                });
            }
            
            // Cargar datos para los gráficos
            cargarDatosGraficos();
        }
        
        function cargarDatosGraficos() {
            // Cargar producción mensual
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'produccion_mensual' },
                success: function(response) {
                    if (response.success && produccionChart) {
                        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                                     'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        
                        produccionChart.data.labels = response.produccion.map(item => meses[item.mes - 1]);
                        produccionChart.data.datasets[0].data = response.produccion.map(item => 
                            parseFloat(item.cantidad_total) || 0
                        );
                        produccionChart.update();
                    }
                }
            });
            
            // Cargar gastos vs ingresos
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'gastos_ingresos' },
                success: function(response) {
                    if (response.success && gastosIngresosChart) {
                        gastosIngresosChart.data.labels = response.datos.map(item => item.periodo);
                        gastosIngresosChart.data.datasets[0].data = response.datos.map(item => 
                            parseFloat(item.ingresos) || 0
                        );
                        gastosIngresosChart.data.datasets[1].data = response.datos.map(item => 
                            parseFloat(item.gastos) || 0
                        );
                        gastosIngresosChart.update();
                    }
                }
            });
            
            // Cargar distribución de cultivos
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'distribucion_cultivos' },
                success: function(response) {
                    if (response.success && distribucionChart) {
                        distribucionChart.data.labels = response.distribucion.map(item => item.cultivo);
                        distribucionChart.data.datasets[0].data = response.distribucion.map(item => 
                            parseInt(item.cantidad) || 0
                        );
                        distribucionChart.update();
                    }
                }
            });

            // Cargar rendimiento por lote
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'rendimiento_lotes' },
                success: function(response) {
                    if (response.success && rendimientoLoteChart) {
                        rendimientoLoteChart.data.labels = response.rendimiento.map(item => 
                            item.lote_nombre || 'Lote ' + item.lote_id
                        );
                        rendimientoLoteChart.data.datasets[0].data = response.rendimiento.map(item => 
                            parseFloat(item.rendimiento_promedio) || 0
                        );
                        rendimientoLoteChart.update();
                    }
                }
            });
        }
        
        function cargarCultivosParaFiltro() {
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'cultivos_filtro' },
                success: function(response) {
                    if (response.success) {
                        const select = $('#cultivoProduccion');
                        select.empty().append('<option value="">Todos los cultivos</option>');
                        
                        response.cultivos.forEach(cultivo => {
                            select.append(`<option value="${cultivo.tip_id}">${cultivo.tip_nombre}</option>`);
                        });
                    }
                }
            });
        }
        
        function cargarLotesParaFiltro() {
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'lotes_filtro' },
                success: function(response) {
                    if (response.success) {
                        const select = $('#loteProduccion');
                        select.empty().append('<option value="">Todos los lotes</option>');
                        
                        response.lotes.forEach(lote => {
                            select.append(`<option value="${lote.lot_id}">${lote.lot_nombre} (${lote.finca})</option>`);
                        });
                    }
                }
            });
        }
        
        function cargarOpcionesFiltros() {
            // Esta función se ejecuta cuando se activa la pestaña de producción
            // Ya se cargan los filtros al inicio, pero se puede usar para actualizar
        }
        
        function filtrarReportesProduccion() {
            // Obtener valores de filtros
            const filtros = {
                fecha_inicio: $('#fechaInicioProduccion').val() || null,
                fecha_fin: $('#fechaFinProduccion').val() || null,
                cultivo_id: $('#cultivoProduccion').val() || null,
                lote_id: $('#loteProduccion').val() || null
            };
            
            // Cargar reporte de cosechas
            cargarReporteCosechas(filtros);
            
            // Cargar reporte de rendimiento
            cargarReporteRendimiento(filtros);
        }
        
        function limpiarFiltrosProduccion() {
            $('#fechaInicioProduccion, #fechaFinProduccion').val('');
            $('#cultivoProduccion, #loteProduccion').val('');
            
            // Recargar reportes sin filtros
            filtrarReportesProduccion();
        }
        
        function cargarReporteCosechas(filtros = {}) {
            $('#loadingCosechas').show();
            
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'reporte_cosechas', ...filtros },
                success: function(response) {
                    $('#loadingCosechas').hide();
                    
                    if (response.success) {
                        // Destruir DataTable existente si existe
                        if ($.fn.DataTable.isDataTable('#tablaCosechas')) {
                            $('#tablaCosechas').DataTable().destroy();
                        }
                        
                        // Limpiar tbody
                        $('#tablaCosechas tbody').empty();
                        
                        // Agregar filas
                        response.cosechas.forEach(cosecha => {
                            const fila = `
                                <tr>
                                    <td>${cosecha.cos_fecha_cosecha}</td>
                                    <td>${cosecha.cultivo}</td>
                                    <td>${cosecha.lote}</td>
                                    <td>${parseFloat(cosecha.cos_cantidad_cosechada).toFixed(2)} ${cosecha.cos_unidad}</td>
                                    <td><span class="badge bg-${obtenerColorCalidad(cosecha.cos_calidad)}">${cosecha.cos_calidad}</span></td>
                                    <td>$${parseFloat(cosecha.cos_precio_venta_unitario || 0).toFixed(2)}</td>
                                    <td>$${parseFloat(cosecha.cos_total_ingresos || 0).toFixed(2)}</td>
                                    <td>${parseFloat(cosecha.rendimiento_hectarea || 0).toFixed(2)}</td>
                                </tr>
                            `;
                            $('#tablaCosechas tbody').append(fila);
                        });
                        
                        // Inicializar DataTable
                        $('#tablaCosechas').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            },
                            responsive: true,
                            pageLength: 25
                        });
                    } else {
                        mostrarAlerta('error', response.message);
                    }
                },
                error: function() {
                    $('#loadingCosechas').hide();
                    mostrarAlerta('error', 'Error al cargar el reporte de cosechas');
                }
            });
        }
        
        function cargarReporteRendimiento(filtros = {}) {
            $('#loadingRendimiento').show();
            
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'reporte_rendimiento', ...filtros },
                success: function(response) {
                    $('#loadingRendimiento').hide();
                    
                    if (response.success) {
                        // Destruir DataTable existente si existe
                        if ($.fn.DataTable.isDataTable('#tablaRendimiento')) {
                            $('#tablaRendimiento').DataTable().destroy();
                        }
                        
                        // Limpiar tbody
                        $('#tablaRendimiento tbody').empty();
                        
                        // Agregar filas
                        response.rendimientos.forEach(rendimiento => {
                            const fila = `
                                <tr>
                                    <td>${rendimiento.cultivo}</td>
                                    <td><span class="badge bg-info">${rendimiento.categoria}</span></td>
                                    <td>${rendimiento.total_cosechas}</td>
                                    <td>${parseFloat(rendimiento.cantidad_total).toFixed(2)}</td>
                                    <td>${parseFloat(rendimiento.promedio_cosecha).toFixed(2)}</td>
                                    <td>${parseFloat(rendimiento.rendimiento_promedio_hectarea).toFixed(2)}</td>
                                    <td>${Math.round(rendimiento.promedio_dias_cultivo)} días</td>
                                </tr>
                            `;
                            $('#tablaRendimiento tbody').append(fila);
                        });
                        
                        // Inicializar DataTable
                        $('#tablaRendimiento').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            },
                            responsive: true,
                            pageLength: 25
                        });
                    } else {
                        mostrarAlerta('error', response.message);
                    }
                },
                error: function() {
                    $('#loadingRendimiento').hide();
                    mostrarAlerta('error', 'Error al cargar el reporte de rendimiento');
                }
            });
        }
        
        function cargarReportesFinancieros() {
            cargarEstadoResultados();
            cargarFlujoCaja();
        }
        
        function cargarEstadoResultados() {
            $('#loadingResultados').show();
            
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'estado_resultados' },
                success: function(response) {
                    $('#loadingResultados').hide();
                    
                    if (response.success) {
                        // Destruir DataTable existente si existe
                        if ($.fn.DataTable.isDataTable('#tablaResultados')) {
                            $('#tablaResultados').DataTable().destroy();
                        }
                        
                        // Limpiar tbody
                        $('#tablaResultados tbody').empty();
                        
                        // Agregar filas
                        response.resultados.forEach(resultado => {
                            const fila = `
                                <tr>
                                    <td>${resultado.cultivo}</td>
                                    <td>${resultado.total_siembras}</td>
                                    <td class="text-success">$${parseFloat(resultado.total_ingresos).toFixed(2)}</td>
                                    <td class="text-danger">$${parseFloat(resultado.total_gastos).toFixed(2)}</td>
                                    <td class="${resultado.utilidad_bruta >= 0 ? 'text-success' : 'text-danger'}">
                                        $${parseFloat(resultado.utilidad_bruta).toFixed(2)}
                                    </td>
                                    <td>${parseFloat(resultado.margen_utilidad).toFixed(1)}%</td>
                                    <td>${parseFloat(resultado.area_total).toFixed(2)} ha</td>
                                </tr>
                            `;
                            $('#tablaResultados tbody').append(fila);
                        });
                        
                        // Inicializar DataTable
                        $('#tablaResultados').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            },
                            responsive: true,
                            pageLength: 25
                        });
                    } else {
                        mostrarAlerta('error', response.message);
                    }
                },
                error: function() {
                    $('#loadingResultados').hide();
                    mostrarAlerta('error', 'Error al cargar el estado de resultados');
                }
            });
        }
        
        function cargarFlujoCaja() {
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'flujo_caja' },
                success: function(response) {
                    if (response.success) {
                        // Si el gráfico ya existe, destruirlo
                        if (flujoCajaChart) {
                            flujoCajaChart.destroy();
                        }
                        
                        const ctx = document.getElementById('flujoCajaChart').getContext('2d');
                        flujoCajaChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: response.flujo.map(item => item.periodo),
                                datasets: [
                                    {
                                        label: 'Ingresos',
                                        data: response.flujo.map(item => parseFloat(item.ingresos)),
                                        borderColor: '#4CAF50',
                                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                        fill: false
                                    },
                                    {
                                        label: 'Gastos',
                                        data: response.flujo.map(item => parseFloat(item.gastos)),
                                        borderColor: '#FF9800',
                                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                                        fill: false
                                    },
                                    {
                                        label: 'Flujo Neto',
                                        data: response.flujo.map(item => parseFloat(item.flujo_neto)),
                                        borderColor: '#2E7D32',
                                        backgroundColor: 'rgba(46, 125, 50, 0.1)',
                                        fill: true
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                },
                error: function() {
                    mostrarAlerta('error', 'Error al cargar el flujo de caja');
                }
            });
        }
        
        function cargarReportesTecnicos() {
            cargarHistorialActividades();
            cargarRegistroMonitoreo();
        }
        
        function cargarHistorialActividades() {
            $('#loadingActividades').show();
            
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'historial_actividades' },
                success: function(response) {
                    $('#loadingActividades').hide();
                    
                    if (response.success) {
                        // Destruir DataTable existente si existe
                        if ($.fn.DataTable.isDataTable('#tablaActividades')) {
                            $('#tablaActividades').DataTable().destroy();
                        }
                        
                        // Limpiar tbody
                        $('#tablaActividades tbody').empty();
                        
                        // Agregar filas
                        response.actividades.forEach(actividad => {
                            const fila = `
                                <tr>
                                    <td>${actividad.act_fecha}</td>
                                    <td><span class="badge bg-primary">${actividad.act_tipo}</span></td>
                                    <td>${actividad.lote}</td>
                                    <td>${actividad.cultivo}</td>
                                    <td>${actividad.act_descripcion || '-'}</td>
                                    <td>${actividad.act_productos_utilizados || '-'}</td>
                                    <td>$${parseFloat(actividad.act_costo || 0).toFixed(2)}</td>
                                    <td>${actividad.responsable || '-'}</td>
                                </tr>
                            `;
                            $('#tablaActividades tbody').append(fila);
                        });
                        
                        // Inicializar DataTable
                        $('#tablaActividades').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            },
                            responsive: true,
                            pageLength: 25,
                            order: [[0, 'desc']] // Ordenar por fecha descendente
                        });
                    } else {
                        mostrarAlerta('error', response.message);
                    }
                },
                error: function() {
                    $('#loadingActividades').hide();
                    mostrarAlerta('error', 'Error al cargar el historial de actividades');
                }
            });
        }
        
        function cargarRegistroMonitoreo() {
            $('#loadingMonitoreo').show();
            
            $.ajax({
                url: '../AJAX/reportes_ajax.php',
                method: 'GET',
                data: { accion: 'registro_monitoreo' },
                success: function(response) {
                    $('#loadingMonitoreo').hide();
                    
                    if (response.success) {
                        // Destruir DataTable existente si existe
                        if ($.fn.DataTable.isDataTable('#tablaMonitoreo')) {
                            $('#tablaMonitoreo').DataTable().destroy();
                        }
                        
                        // Limpiar tbody
                        $('#tablaMonitoreo tbody').empty();
                        
                        // Agregar filas
                        response.monitoreos.forEach(monitoreo => {
                            const fila = `
                                <tr>
                                    <td>${monitoreo.mon_fecha_observacion}</td>
                                    <td>${monitoreo.lote}</td>
                                    <td>${monitoreo.cultivo}</td>
                                    <td><span class="badge bg-${obtenerColorEstado(monitoreo.mon_estado_general)}">${monitoreo.mon_estado_general}</span></td>
                                    <td>${parseFloat(monitoreo.mon_altura_promedio || 0).toFixed(2)} cm</td>
                                    <td>${parseFloat(monitoreo.mon_porcentaje_germinacion || 0).toFixed(1)}%</td>
                                    <td><span class="badge bg-${obtenerColorPlagas(monitoreo.mon_presencia_plagas)}">${monitoreo.mon_presencia_plagas}</span></td>
                                    <td><span class="badge bg-${obtenerColorEnfermedades(monitoreo.mon_presencia_enfermedades)}">${monitoreo.mon_presencia_enfermedades}</span></td>
                                    <td>${monitoreo.responsable || '-'}</td>
                                </tr>
                            `;
                            $('#tablaMonitoreo tbody').append(fila);
                        });
                        
                        // Inicializar DataTable
                        $('#tablaMonitoreo').DataTable({
                            language: {
                                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                            },
                            responsive: true,
                            pageLength: 25,
                            order: [[0, 'desc']] // Ordenar por fecha descendente
                        });
                    } else {
                        mostrarAlerta('error', response.message);
                    }
                },
                error: function() {
                    $('#loadingMonitoreo').hide();
                    mostrarAlerta('error', 'Error al cargar el registro de monitoreo');
                }
            });
        }
        
        function exportarReporte(formato) {
            // Obtener la pestaña activa
            const pestañaActiva = $('.nav-tabs .nav-link.active').data('bs-target');
            
            // Determinar qué reporte exportar basado en la pestaña activa
            let tipoReporte = '';
            let filtros = {};
            
            switch(pestañaActiva) {
                case '#produccion':
                    const subPestañaProduccion = $('#produccionSubTabs .nav-link.active').attr('id');
                    tipoReporte = subPestañaProduccion === 'rendimiento-subtab' ? 'rendimiento' : 'cosechas';
                    filtros = obtenerFiltrosProduccion();
                    break;
                case '#financiero':
                    const subPestañaFinanciero = $('#financieroSubTabs .nav-link.active').attr('id');
                    tipoReporte = subPestañaFinanciero === 'flujo-subtab' ? 'flujo_caja' : 'estado_resultados';
                    filtros = {};
                    break;
                case '#tecnico':
                    const subPestañaTecnico = $('#tecnicoSubTabs .nav-link.active').attr('id');
                    tipoReporte = subPestañaTecnico === 'monitoreo-subtab' ? 'monitoreo' : 'actividades';
                    filtros = {};
                    break;
                default:
                    mostrarAlerta('warning', 'Selecciona un reporte específico para exportar');
                    return;
            }
            
            // Construir URL con parámetros
            const params = new URLSearchParams({
                tipo: tipoReporte,
                formato: formato,
                ...filtros
            });
            
            // Realizar la exportación
            const url = `../AJAX/exportar_reportes.php?${params}`;
            
            // Mostrar mensaje de descarga
            mostrarAlerta('info', `Preparando descarga del reporte en formato ${formato.toUpperCase()}...`);
            
            // Abrir en nueva ventana/pestaña para descarga
            window.open(url, '_blank');
        }
        
        function obtenerFiltrosProduccion() {
            return {
                fecha_inicio: $('#fechaInicioProduccion').val() || null,
                fecha_fin: $('#fechaFinProduccion').val() || null,
                cultivo_id: $('#cultivoProduccion').val() || null,
                lote_id: $('#loteProduccion').val() || null
            };
        }
        
        // Funciones auxiliares para obtener colores de badges
        function obtenerColorCalidad(calidad) {
            const colores = {
                'primera': 'success',
                'segunda': 'warning',
                'tercera': 'secondary',
                'descarte': 'danger'
            };
            return colores[calidad] || 'secondary';
        }
        
        function obtenerColorEstado(estado) {
            const colores = {
                'excelente': 'success',
                'bueno': 'primary',
                'regular': 'warning',
                'malo': 'danger',
                'critico': 'danger'
            };
            return colores[estado] || 'secondary';
        }
        
        function obtenerColorPlagas(nivel) {
            const colores = {
                'ninguna': 'success',
                'leve': 'warning',
                'moderada': 'danger',
                'severa': 'danger'
            };
            return colores[nivel] || 'secondary';
        }
        
        function obtenerColorEnfermedades(nivel) {
            const colores = {
                'ninguna': 'success',
                'leve': 'warning',
                'moderada': 'danger',
                'severa': 'danger'
            };
            return colores[nivel] || 'secondary';
        }
        
        function mostrarAlerta(tipo, mensaje) {
            let claseAlerta = '';
            switch(tipo) {
                case 'success':
                    claseAlerta = 'alert-success';
                    break;
                case 'error':
                    claseAlerta = 'alert-danger';
                    break;
                case 'warning':
                    claseAlerta = 'alert-warning';
                    break;
                case 'info':
                    claseAlerta = 'alert-info';
                    break;
                default:
                    claseAlerta = 'alert-info';
            }
            
            const alerta = `
                <div class="alert ${claseAlerta} alert-dismissible fade show" role="alert">
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Mostrar alerta al principio del contenido principal
            $('.container-fluid').prepend(alerta);
            
            // Auto-remover después de 3 segundos
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 3000);
        }
    </script>
</body>
</html>