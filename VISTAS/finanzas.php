<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/finanzas_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$finanzas_modelo = new Finanzas();

// Obtener gastos según permisos del usuario
$resultado_gastos = $finanzas_modelo->listarGastos($usuario_actual['id'], $usuario_actual['rol']);
$gastos = $resultado_gastos['success'] ? $resultado_gastos['gastos'] : array();

// Obtener fincas para filtros
$resultado_fincas = $finanzas_modelo->obtenerFincasUsuario($usuario_actual['id'], $usuario_actual['rol']);
$fincas = $resultado_fincas['success'] ? $resultado_fincas['fincas'] : array();

// Obtener siembras para gastos específicos
$resultado_siembras = $finanzas_modelo->obtenerSiembrasUsuario($usuario_actual['id'], $usuario_actual['rol']);
$siembras = $resultado_siembras['success'] ? $resultado_siembras['siembras'] : array();

// Calcular estadísticas financieras
$total_gastos = 0;
$gastos_mes_actual = 0;
$gastos_por_tipo = [
    'semillas' => 0,
    'fertilizantes' => 0,
    'pesticidas' => 0,
    'mano_obra' => 0,
    'maquinaria' => 0,
    'otros' => 0
];

$mes_actual = date('Y-m');

foreach ($gastos as $gasto) {
    $total_gastos += floatval($gasto['gas_monto']);
    
    if (strpos($gasto['gas_fecha'], $mes_actual) === 0) {
        $gastos_mes_actual += floatval($gasto['gas_monto']);
    }
    
    if (isset($gastos_por_tipo[$gasto['gas_tipo']])) {
        $gastos_por_tipo[$gasto['gas_tipo']] += floatval($gasto['gas_monto']);
    }
}

// Obtener ingresos totales de cosechas
$resultado_ingresos = $finanzas_modelo->obtenerIngresosTotales($usuario_actual['id'], $usuario_actual['rol']);
$ingresos_totales = $resultado_ingresos['success'] ? $resultado_ingresos['total'] : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Financiero - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/finanzas.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container mt-4">
        <!-- Header -->
        <div class="finanzas-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-chart-line me-2"></i>
                            <span class="d-block d-sm-inline">Control Financiero</span>
                        </h2>
                        <p class="page-subtitle">
                            <?php if ($usuario_actual['rol'] == 'administrador'): ?>
                                <span class="d-block d-sm-inline">Administra todas las finanzas del sistema</span>
                            <?php elseif ($usuario_actual['rol'] == 'agricultor'): ?>
                                <span class="d-block d-sm-inline">Controla los gastos e ingresos de tus cultivos</span>
                            <?php else: ?>
                                <span class="d-block d-sm-inline">Supervisa la información financiera asignada</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end text-center">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                            <button type="button" class="btn btn-primary btn-responsive me-2" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
                                <i class="fas fa-plus me-2"></i>
                                <span class="d-none d-sm-inline">Nuevo Gasto</span>
                                <span class="d-inline d-sm-none">Gasto</span>
                            </button>
                            <button type="button" class="btn btn-success btn-responsive" id="btnGenerarReporte">
                                <i class="fas fa-file-excel me-2"></i>
                                <span class="d-none d-sm-inline">Reporte</span>
                                <span class="d-inline d-sm-none">Excel</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Financieras -->
        <div class="row mb-4" data-aos="fade-up">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card stats-card-expense">
                    <div class="stats-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="totalGastos">
                            $<?php echo number_format($total_gastos, 0, ',', '.'); ?>
                        </h3>
                        <p class="stats-label">Total Gastos</p>
                    </div>
                    <div class="stats-progress">
                        <div class="progress-bar bg-danger" style="width: 85%"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card stats-card-income">
                    <div class="stats-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="totalIngresos">
                            $<?php echo number_format($ingresos_totales, 0, ',', '.'); ?>
                        </h3>
                        <p class="stats-label">Total Ingresos</p>
                    </div>
                    <div class="stats-progress">
                        <div class="progress-bar bg-success" style="width: 70%"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card stats-card-profit">
                    <div class="stats-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="utilidadNeta">
                            $<?php echo number_format($ingresos_totales - $total_gastos, 0, ',', '.'); ?>
                        </h3>
                        <p class="stats-label">Utilidad Neta</p>
                    </div>
                    <div class="stats-progress">
                        <div class="progress-bar <?php echo ($ingresos_totales - $total_gastos) >= 0 ? 'bg-success' : 'bg-danger'; ?>" style="width: 60%"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card stats-card-monthly">
                    <div class="stats-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="gastosMesActual">
                            $<?php echo number_format($gastos_mes_actual, 0, ',', '.'); ?>
                        </h3>
                        <p class="stats-label">Gastos Este Mes</p>
                    </div>
                    <div class="stats-progress">
                        <div class="progress-bar bg-warning" style="width: 45%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Gastos por Tipo -->
        <div class="row mb-4" data-aos="fade-up">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie me-2"></i>
                            Distribución de Gastos por Categoría
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="gastos-chart-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon semillas">
                                            <i class="fas fa-seedling"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Semillas</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['semillas'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon fertilizantes">
                                            <i class="fas fa-leaf"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Fertilizantes</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['fertilizantes'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon pesticidas">
                                            <i class="fas fa-spray-can"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Pesticidas</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['pesticidas'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon mano-obra">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Mano de Obra</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['mano_obra'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon maquinaria">
                                            <i class="fas fa-tractor"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Maquinaria</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['maquinaria'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="gasto-item">
                                        <div class="gasto-icon otros">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </div>
                                        <div class="gasto-info">
                                            <h6>Otros</h6>
                                            <span>$<?php echo number_format($gastos_por_tipo['otros'], 0, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-calculator me-2"></i>
                            Análisis Rápido
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="analisis-item">
                            <div class="analisis-label">Margen de Ganancia:</div>
                            <div class="analisis-value <?php echo ($ingresos_totales - $total_gastos) >= 0 ? 'positive' : 'negative'; ?>">
                                <?php 
                                $margen = $ingresos_totales > 0 ? (($ingresos_totales - $total_gastos) / $ingresos_totales) * 100 : 0;
                                echo number_format($margen, 1) . '%';
                                ?>
                            </div>
                        </div>
                        <div class="analisis-item">
                            <div class="analisis-label">Costo por Hectárea:</div>
                            <div class="analisis-value">
                                <?php
                                // Calcular área total de fincas
                                $area_total = 0;
                                foreach ($fincas as $finca) {
                                    $area_total += floatval($finca['fin_area_total']);
                                }
                                $costo_ha = $area_total > 0 ? $total_gastos / $area_total : 0;
                                echo '$' . number_format($costo_ha, 0, ',', '.');
                                ?>
                            </div>
                        </div>
                        <div class="analisis-item">
                            <div class="analisis-label">ROI (Retorno Inversión):</div>
                            <div class="analisis-value <?php echo ($ingresos_totales - $total_gastos) >= 0 ? 'positive' : 'negative'; ?>">
                                <?php 
                                $roi = $total_gastos > 0 ? (($ingresos_totales - $total_gastos) / $total_gastos) * 100 : 0;
                                echo number_format($roi, 1) . '%';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4" data-aos="fade-up">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <label for="filtroTipo" class="form-label">Tipo de Gasto:</label>
                        <select class="form-select" id="filtroTipo">
                            <option value="">Todos los tipos</option>
                            <option value="semillas">Semillas</option>
                            <option value="fertilizantes">Fertilizantes</option>
                            <option value="pesticidas">Pesticidas</option>
                            <option value="mano_obra">Mano de Obra</option>
                            <option value="maquinaria">Maquinaria</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="filtroFinca" class="form-label">Finca:</label>
                        <select class="form-select" id="filtroFinca">
                            <option value="">Todas las fincas</option>
                            <?php foreach ($fincas as $finca): ?>
                                <option value="<?php echo $finca['fin_id']; ?>">
                                    <?php echo htmlspecialchars($finca['fin_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filtroFechaInicio" class="form-label">Fecha desde:</label>
                        <input type="date" class="form-control" id="filtroFechaInicio">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtroFechaFin" class="form-label">Fecha hasta:</label>
                        <input type="date" class="form-control" id="filtroFechaFin">
                    </div>

                    <div class="col-md-2">
                        <label for="filtroProveedor" class="form-label">Proveedor:</label>
                        <input type="text" class="form-control" id="filtroProveedor" placeholder="Buscar...">
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltros">
                            <i class="fas fa-broom"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Gastos -->
        <div class="card" data-aos="fade-up">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-receipt me-2"></i>
                    Registro de Gastos
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaGastos" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Finca/Siembra</th>
                                <th>Proveedor</th>
                                <th>Factura</th>
                                <th>Monto</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($gastos)): ?>
                                <?php foreach ($gastos as $gasto): ?>
                                    <tr data-gasto-id="<?php echo $gasto['gas_id']; ?>">
                                        <td><?php echo $gasto['gas_id']; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($gasto['gas_fecha'])); ?></td>
                                        <td>
                                            <span class="badge badge-tipo-<?php echo $gasto['gas_tipo']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $gasto['gas_tipo'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="descripcion-gasto">
                                                <?php echo htmlspecialchars($gasto['gas_descripcion']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ubicacion-gasto">
                                                <?php if ($gasto['fin_nombre']): ?>
                                                    <strong><?php echo htmlspecialchars($gasto['fin_nombre']); ?></strong>
                                                <?php endif; ?>
                                                <?php if ($gasto['lot_nombre']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($gasto['lot_nombre']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo $gasto['gas_proveedor'] ? htmlspecialchars($gasto['gas_proveedor']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td><?php echo $gasto['gas_factura_numero'] ? htmlspecialchars($gasto['gas_factura_numero']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <span class="monto-gasto">
                                                $<?php echo number_format($gasto['gas_monto'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group-actions">
                                                <!-- Ver detalles -->
                                                <button type="button" class="btn btn-sm btn-outline-info btn-ver-gasto" 
                                                        data-id="<?php echo $gasto['gas_id']; ?>" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                         ($usuario_actual['rol'] == 'agricultor' && $gasto['responsable_id'] == $usuario_actual['id'])): ?>
                                                <!-- Editar -->
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-editar-gasto" 
                                                        data-id="<?php echo $gasto['gas_id']; ?>" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- Eliminar -->
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-gasto" 
                                                        data-id="<?php echo $gasto['gas_id']; ?>"
                                                        data-descripcion="<?php echo htmlspecialchars($gasto['gas_descripcion']); ?>" 
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Gasto -->
    <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
    <div class="modal fade" id="modalNuevoGasto" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Registrar Nuevo Gasto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoGasto">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevaFechaGasto" class="form-label">Fecha del Gasto *</label>
                                    <input type="date" class="form-control" id="nuevaFechaGasto" name="fecha" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevoTipoGasto" class="form-label">Tipo de Gasto *</label>
                                    <select class="form-select" id="nuevoTipoGasto" name="tipo" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="semillas">Semillas e Insumos</option>
                                        <option value="fertilizantes">Fertilizantes</option>
                                        <option value="pesticidas">Pesticidas y Fungicidas</option>
                                        <option value="mano_obra">Mano de Obra</option>
                                        <option value="maquinaria">Maquinaria y Equipos</option>
                                        <option value="otros">Otros Gastos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevoMontoGasto" class="form-label">Monto *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="nuevoMontoGasto" name="monto" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="nuevaDescripcionGasto" class="form-label">Descripción del Gasto *</label>
                                    <textarea class="form-control" id="nuevaDescripcionGasto" name="descripcion" rows="2" required placeholder="Describa detalladamente el gasto..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Información de Ubicación -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Ubicación del Gasto</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaFincaGasto" class="form-label">Finca</label>
                                    <select class="form-select" id="nuevaFincaGasto" name="finca_id">
                                        <option value="">Gasto general (sin finca específica)</option>
                                        <?php foreach ($fincas as $finca): ?>
                                            <option value="<?php echo $finca['fin_id']; ?>">
                                                <?php echo htmlspecialchars($finca['fin_nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaSiembraGasto" class="form-label">Siembra Específica</label>
                                    <select class="form-select" id="nuevaSiembraGasto" name="siembra_id">
                                        <option value="">Sin siembra específica</option>
                                        <?php foreach ($siembras as $siembra): ?>
                                            <option value="<?php echo $siembra['sie_id']; ?>">
                                                <?php echo htmlspecialchars($siembra['lot_nombre'] . ' - ' . $siembra['cul_nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Información del Proveedor -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-truck me-2"></i>Información del Proveedor</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoProveedorGasto" class="form-label">Proveedor</label>
                                    <input type="text" class="form-control" id="nuevoProveedorGasto" name="proveedor" placeholder="Nombre del proveedor">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaFacturaGasto" class="form-label">Número de Factura</label>
                                    <input type="text" class="form-control" id="nuevaFacturaGasto" name="factura_numero" placeholder="Número de factura o recibo">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="nuevasObservacionesGasto" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="nuevasObservacionesGasto" name="observaciones" rows="2" placeholder="Observaciones adicionales..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Registrar Gasto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include('partials/footer.php'); ?>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="JS/finanzas.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Inicializar AOS para animaciones
        AOS.init({
            duration: 600,
            once: true,
            offset: 100
        });
        
        // Pasar datos de usuario para JavaScript
        window.usuarioActual = {
            id: <?php echo $usuario_actual['id']; ?>,
            rol: '<?php echo $usuario_actual['rol']; ?>'
        };
    </script>
</body>
</html>