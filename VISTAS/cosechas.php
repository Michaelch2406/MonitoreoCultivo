<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/cosechas_m.php');
require_once('../MODELOS/siembras_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$cosecha_modelo = new Cosecha();
$siembra_modelo = new Siembra();

// Obtener cosechas según permisos del usuario
$resultado_cosechas = $cosecha_modelo->listarCosechas($usuario_actual['id'], $usuario_actual['rol']);
$cosechas = $resultado_cosechas['success'] ? $resultado_cosechas['cosechas'] : array();

// Obtener siembras disponibles para cosecha
$resultado_siembras = $siembra_modelo->listarSiembrasParaCosecha($usuario_actual['id'], $usuario_actual['rol']);
$siembras = $resultado_siembras['success'] ? $resultado_siembras['siembras'] : array();

// Calcular estadísticas
$total_cosechas = count($cosechas);
$cantidad_total = 0;
$ingresos_totales = 0;
$cosechas_primera = 0;

foreach ($cosechas as $cosecha) {
    $cantidad_total += floatval($cosecha['cos_cantidad_cosechada']);
    $ingresos_totales += floatval($cosecha['cos_total_ingresos']);
    if ($cosecha['cos_calidad'] == 'primera') {
        $cosechas_primera++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cosechas - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/cosechas.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container mt-4">
        <!-- Header -->
        <div class="cosechas-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-tractor me-2"></i>
                            <span class="d-block d-sm-inline">Gestión de Cosechas</span>
                        </h2>
                        <p class="page-subtitle">
                            <?php if ($usuario_actual['rol'] == 'administrador'): ?>
                                <span class="d-block d-sm-inline">Administra todas las cosechas del sistema</span>
                            <?php elseif ($usuario_actual['rol'] == 'agricultor'): ?>
                                <span class="d-block d-sm-inline">Registra y gestiona tus cosechas</span>
                            <?php else: ?>
                                <span class="d-block d-sm-inline">Supervisa las cosechas asignadas</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end text-center">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                            <button type="button" class="btn btn-primary btn-responsive" data-bs-toggle="modal" data-bs-target="#modalNuevaCosecha">
                                <i class="fas fa-plus me-2"></i>
                                <span class="d-none d-sm-inline">Nueva Cosecha</span>
                                <span class="d-inline d-sm-none">Nueva</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4" data-aos="fade-up">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-tractor"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="totalCosechas">
                            <?php echo $total_cosechas; ?>
                        </h3>
                        <p class="stats-label">Total Cosechas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="cantidadTotal">
                            <?php echo number_format($cantidad_total, 1); ?>
                        </h3>
                        <p class="stats-label">Cantidad Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="ingresosTotal">
                            $<?php echo number_format($ingresos_totales, 0); ?>
                        </h3>
                        <p class="stats-label">Ingresos Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="calidadPrimera">
                            <?php echo $cosechas_primera; ?>
                        </h3>
                        <p class="stats-label">Calidad Primera</p>
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
                    <div class="col-md-3">
                        <label for="filtroSiembra" class="form-label">Siembra:</label>
                        <select class="form-select" id="filtroSiembra">
                            <option value="">Todas las siembras</option>
                            <?php foreach ($siembras as $siembra): ?>
                                <option value="<?php echo $siembra['sie_id']; ?>">
                                    <?php echo htmlspecialchars($siembra['lot_nombre'] . ' - ' . $siembra['cul_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtroCalidad" class="form-label">Calidad:</label>
                        <select class="form-select" id="filtroCalidad">
                            <option value="">Todas las calidades</option>
                            <option value="primera">Primera</option>
                            <option value="segunda">Segunda</option>
                            <option value="tercera">Tercera</option>
                            <option value="descarte">Descarte</option>
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

                    <div class="col-md-1">
                        <label for="filtroComprador" class="form-label">Comprador:</label>
                        <input type="text" class="form-control" id="filtroComprador" placeholder="Buscar...">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="btnLimpiarFiltros">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                        <button type="button" class="btn btn-success" id="btnExportarCosechas">
                            <i class="fas fa-file-excel"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Cosechas -->
        <div class="card" data-aos="fade-up">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaCosechas" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha Cosecha</th>
                                <th>Siembra</th>
                                <th>Cantidad</th>
                                <th>Calidad</th>
                                <th>Comprador</th>
                                <th>Precio Unitario</th>
                                <th>Total Ingresos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cosechas)): ?>
                                <?php foreach ($cosechas as $cosecha): ?>
                                    <tr data-cosecha-id="<?php echo $cosecha['cos_id']; ?>">
                                        <td><?php echo $cosecha['cos_id']; ?></td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($cosecha['cos_fecha_cosecha'])); ?>
                                        </td>
                                        <td>
                                            <div class="siembra-info">
                                                <strong><?php echo htmlspecialchars($cosecha['lot_nombre']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($cosecha['cul_nombre']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cantidad-badge">
                                                <?php echo number_format($cosecha['cos_cantidad_cosechada'], 2); ?> 
                                                <?php echo htmlspecialchars($cosecha['cos_unidad']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($cosecha['cos_calidad']) {
                                                case 'primera':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'segunda':
                                                    $badge_class = 'bg-info';
                                                    break;
                                                case 'tercera':
                                                    $badge_class = 'bg-warning';
                                                    break;
                                                case 'descarte':
                                                    $badge_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($cosecha['cos_calidad']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $cosecha['cos_comprador'] ? htmlspecialchars($cosecha['cos_comprador']) : '<span class="text-muted">Sin vender</span>'; ?>
                                        </td>
                                        <td>
                                            <?php echo $cosecha['cos_precio_venta_unitario'] ? '$' . number_format($cosecha['cos_precio_venta_unitario'], 2) : '<span class="text-muted">-</span>'; ?>
                                        </td>
                                        <td>
                                            <span class="ingresos-badge">
                                                <?php echo $cosecha['cos_total_ingresos'] ? '$' . number_format($cosecha['cos_total_ingresos'], 2) : '<span class="text-muted">-</span>'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $cosecha['cos_total_ingresos'] ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $cosecha['cos_total_ingresos'] ? 'Vendida' : 'Almacenada'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group-actions">
                                                <!-- Ver detalles -->
                                                <button type="button" class="btn btn-sm btn-outline-info btn-ver-cosecha" 
                                                        data-id="<?php echo $cosecha['cos_id']; ?>" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                         ($usuario_actual['rol'] == 'agricultor' && $cosecha['responsable_id'] == $usuario_actual['id'])): ?>
                                                <!-- Editar -->
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-editar-cosecha" 
                                                        data-id="<?php echo $cosecha['cos_id']; ?>" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- Eliminar -->
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-cosecha" 
                                                        data-id="<?php echo $cosecha['cos_id']; ?>"
                                                        data-fecha="<?php echo date('d/m/Y', strtotime($cosecha['cos_fecha_cosecha'])); ?>" 
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

    <!-- Modal Nueva Cosecha -->
    <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
    <div class="modal fade" id="modalNuevaCosecha" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Registrar Nueva Cosecha
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevaCosecha">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaFechaCosecha" class="form-label">Fecha de Cosecha *</label>
                                    <input type="date" class="form-control" id="nuevaFechaCosecha" name="fecha_cosecha" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaSiembra" class="form-label">Siembra a Cosechar *</label>
                                    <select class="form-select" id="nuevaSiembra" name="siembra_id" required>
                                        <option value="">Seleccionar siembra</option>
                                        <?php foreach ($siembras as $siembra): ?>
                                            <option value="<?php echo $siembra['sie_id']; ?>">
                                                <?php echo htmlspecialchars($siembra['lot_nombre'] . ' - ' . $siembra['cul_nombre'] . ' (Sembrado: ' . date('d/m/Y', strtotime($siembra['sie_fecha_siembra'])) . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevaCantidad" class="form-label">Cantidad Cosechada *</label>
                                    <input type="number" class="form-control" id="nuevaCantidad" name="cantidad_cosechada" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevaUnidad" class="form-label">Unidad de Medida *</label>
                                    <select class="form-select" id="nuevaUnidad" name="unidad" required>
                                        <option value="">Seleccionar unidad</option>
                                        <option value="kg">Kilogramos (kg)</option>
                                        <option value="ton">Toneladas (ton)</option>
                                        <option value="lb">Libras (lb)</option>
                                        <option value="qq">Quintales (qq)</option>
                                        <option value="bultos">Bultos</option>
                                        <option value="cajas">Cajas</option>
                                        <option value="unidades">Unidades</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nuevaCalidad" class="form-label">Calidad del Producto *</label>
                                    <select class="form-select" id="nuevaCalidad" name="calidad" required>
                                        <option value="primera">Primera (Exportación/Premium)</option>
                                        <option value="segunda">Segunda (Mercado Nacional)</option>
                                        <option value="tercera">Tercera (Proceso Industrial)</option>
                                        <option value="descarte">Descarte (Pérdidas)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Información Comercial -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-handshake me-2"></i>Información Comercial (Opcional)</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoComprador" class="form-label">Comprador</label>
                                    <input type="text" class="form-control" id="nuevoComprador" name="comprador" placeholder="Nombre del comprador">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="nuevoPrecioUnitario" class="form-label">Precio Unitario</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="nuevoPrecioUnitario" name="precio_unitario" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="nuevoTotalIngresos" class="form-label">Total Ingresos</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="nuevoTotalIngresos" name="total_ingresos" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="nuevasObservaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="nuevasObservaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales sobre la cosecha..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Registrar Cosecha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Cosecha -->
    <div class="modal fade" id="modalEditarCosecha" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Cosecha
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarCosecha">
                    <input type="hidden" id="editarCosechaId" name="cosecha_id">
                    <div class="modal-body">
                        <div class="row">
                            <!-- Información Básica -->
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarFechaCosecha" class="form-label">Fecha de Cosecha *</label>
                                    <input type="date" class="form-control" id="editarFechaCosecha" name="fecha_cosecha" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarSiembra" class="form-label">Siembra *</label>
                                    <select class="form-select" id="editarSiembra" name="siembra_id" required disabled>
                                        <!-- Se llena dinámicamente -->
                                    </select>
                                    <div class="form-text">La siembra no se puede cambiar una vez registrada</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editarCantidad" class="form-label">Cantidad Cosechada *</label>
                                    <input type="number" class="form-control" id="editarCantidad" name="cantidad_cosechada" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editarUnidad" class="form-label">Unidad de Medida *</label>
                                    <select class="form-select" id="editarUnidad" name="unidad" required>
                                        <option value="kg">Kilogramos (kg)</option>
                                        <option value="ton">Toneladas (ton)</option>
                                        <option value="lb">Libras (lb)</option>
                                        <option value="qq">Quintales (qq)</option>
                                        <option value="bultos">Bultos</option>
                                        <option value="cajas">Cajas</option>
                                        <option value="unidades">Unidades</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="editarCalidad" class="form-label">Calidad del Producto *</label>
                                    <select class="form-select" id="editarCalidad" name="calidad" required>
                                        <option value="primera">Primera (Exportación/Premium)</option>
                                        <option value="segunda">Segunda (Mercado Nacional)</option>
                                        <option value="tercera">Tercera (Proceso Industrial)</option>
                                        <option value="descarte">Descarte (Pérdidas)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Información Comercial -->
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="mb-3"><i class="fas fa-handshake me-2"></i>Información Comercial</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarComprador" class="form-label">Comprador</label>
                                    <input type="text" class="form-control" id="editarComprador" name="comprador" placeholder="Nombre del comprador">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="editarPrecioUnitario" class="form-label">Precio Unitario</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="editarPrecioUnitario" name="precio_unitario" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="editarTotalIngresos" class="form-label">Total Ingresos</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="editarTotalIngresos" name="total_ingresos" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="editarObservaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="editarObservaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales sobre la cosecha..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Cosecha
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
    <script src="JS/cosechas.js"></script>
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

        // Calcular total de ingresos automáticamente
        function calcularTotalIngresos(modal = '') {
            const cantidad = parseFloat(document.getElementById(modal + 'Cantidad').value) || 0;
            const precio = parseFloat(document.getElementById(modal + 'PrecioUnitario').value) || 0;
            const total = cantidad * precio;
            document.getElementById(modal + 'TotalIngresos').value = total.toFixed(2);
        }

        // Eventos para calcular total en modal nuevo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nuevaCantidad').addEventListener('input', () => calcularTotalIngresos('nueva'));
            document.getElementById('nuevoPrecioUnitario').addEventListener('input', () => calcularTotalIngresos('nueva'));
            
            document.getElementById('editarCantidad').addEventListener('input', () => calcularTotalIngresos('editar'));
            document.getElementById('editarPrecioUnitario').addEventListener('input', () => calcularTotalIngresos('editar'));
        });
    </script>
</body>
</html>