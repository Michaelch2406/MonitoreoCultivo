<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/actividades_m.php');
require_once('../MODELOS/siembras_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$actividad_modelo = new Actividad();
$siembra_modelo = new Siembra();

// Obtener actividades según permisos del usuario
$resultado_actividades = $actividad_modelo->listarActividades($usuario_actual['id'], $usuario_actual['rol']);
$actividades = $resultado_actividades['success'] ? $resultado_actividades['actividades'] : array();

// Obtener siembras para el select
$resultado_siembras = $siembra_modelo->listarSiembras($usuario_actual['id'], $usuario_actual['rol']);
$siembras = $resultado_siembras['success'] ? $resultado_siembras['siembras'] : array();

// Calcular estadísticas
$total_actividades = count($actividades);
$actividades_hoy = 0;
$actividades_semana = 0;
$costo_total = 0;

foreach ($actividades as $actividad) {
    if ($actividad['act_fecha'] == date('Y-m-d')) {
        $actividades_hoy++;
    }
    
    $fecha_actividad = strtotime($actividad['act_fecha']);
    $fecha_semana = strtotime('-7 days');
    if ($fecha_actividad >= $fecha_semana) {
        $actividades_semana++;
    }
    
    $costo_total += floatval($actividad['act_costo'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades Agrícolas - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/actividades.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container">
        <!-- Header -->
        <div class="actividades-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-tasks me-2"></i>
                            <span class="d-block d-sm-inline">Actividades Agrícolas</span>
                        </h2>
                        <p class="page-subtitle">
                            Registra y gestiona todas las actividades de mantenimiento de tus cultivos
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor' || $usuario_actual['rol'] == 'supervisor'): ?>
                        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevaActividad">
                            <i class="fas fa-plus me-2"></i>
                            <span class="d-none d-sm-inline">Nueva </span>Actividad
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="container mb-4">
            <div class="row" data-aos="fade-up">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-primary">
                        <div class="stats-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $total_actividades; ?></div>
                            <div class="stats-label">Total Actividades</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-success">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $actividades_hoy; ?></div>
                            <div class="stats-label">Hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-warning">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $actividades_semana; ?></div>
                            <div class="stats-label">Esta Semana</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-info">
                        <div class="stats-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number">$<?php echo number_format($costo_total, 0); ?></div>
                            <div class="stats-label">Costo Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y acciones -->
        <div class="container mb-4">
            <div class="card actions-card" data-aos="fade-up" data-aos-delay="100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8 col-md-6 mb-3 mb-md-0">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <select class="form-select" id="filtroSiembra">
                                        <option value="">Todas las siembras</option>
                                        <?php foreach ($siembras as $siembra): ?>
                                        <option value="<?php echo $siembra['sie_id']; ?>">
                                            <?php echo htmlspecialchars($siembra['tip_nombre'] . ' - ' . $siembra['lot_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <select class="form-select" id="filtroTipo">
                                        <option value="">Todos los tipos</option>
                                        <option value="riego">Riego</option>
                                        <option value="fertilizacion">Fertilización</option>
                                        <option value="fumigacion">Fumigación</option>
                                        <option value="poda">Poda</option>
                                        <option value="deshierbe">Deshierbe</option>
                                        <option value="aporque">Aporque</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <input type="date" class="form-control" id="filtroFechaDesde" placeholder="Desde">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <input type="date" class="form-control" id="filtroFechaHasta" placeholder="Hasta">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 text-md-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btnExportar">
                                    <i class="fas fa-download me-2"></i>Exportar
                                </button>
                                <button type="button" class="btn btn-outline-primary" id="btnRefrescar">
                                    <i class="fas fa-sync-alt me-2"></i>Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de actividades -->
        <div class="container">
            <div class="card table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Registro de Actividades
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaActividades">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Siembra</th>
                                    <th>Descripción</th>
                                    <th>Productos</th>
                                    <th>Costo</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividades as $actividad): ?>
                                <tr>
                                    <td>
                                        <span class="text-primary fw-bold">
                                            <?php echo date('d/m/Y', strtotime($actividad['act_fecha'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge tipo-<?php echo $actividad['act_tipo']; ?>">
                                            <i class="fas fa-<?php echo $actividad['icono']; ?> me-1"></i>
                                            <?php echo ucfirst($actividad['act_tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="siembra-info">
                                            <div class="siembra-nombre"><?php echo htmlspecialchars($actividad['tip_nombre']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($actividad['lot_nombre']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="descripcion-actividad">
                                            <?php echo htmlspecialchars(substr($actividad['act_descripcion'], 0, 50)) . (strlen($actividad['act_descripcion']) > 50 ? '...' : ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($actividad['act_productos_utilizados']): ?>
                                        <div class="productos-info">
                                            <?php echo htmlspecialchars(substr($actividad['act_productos_utilizados'], 0, 30)) . (strlen($actividad['act_productos_utilizados']) > 30 ? '...' : ''); ?>
                                            <?php if ($actividad['act_cantidad_producto']): ?>
                                            <br><small class="text-muted"><?php echo $actividad['act_cantidad_producto'] . ' ' . $actividad['act_unidad_producto']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($actividad['act_costo']): ?>
                                        <span class="costo-actividad">$<?php echo number_format($actividad['act_costo'], 2); ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="responsable-info">
                                            <?php echo htmlspecialchars($actividad['responsable_nombre']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-ver" 
                                                    data-actividad-id="<?php echo $actividad['act_id']; ?>"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                      ($usuario_actual['rol'] == 'agricultor' && $actividad['act_responsable_id'] == $usuario_actual['id']) ||
                                                      ($usuario_actual['rol'] == 'supervisor')): ?>
                                            <button type="button" class="btn btn-outline-warning btn-editar" 
                                                    data-actividad-id="<?php echo $actividad['act_id']; ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                      ($usuario_actual['rol'] == 'agricultor' && $actividad['act_responsable_id'] == $usuario_actual['id'])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-eliminar" 
                                                    data-actividad-id="<?php echo $actividad['act_id']; ?>"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Actividad -->
    <div class="modal fade" id="modalNuevaActividad" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Nueva Actividad
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevaActividad">
                        <!-- Información básica -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-info-circle me-2"></i>Información Básica
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="siembraId" name="siembra_id" required>
                                        <option value="">Selecciona una siembra</option>
                                        <?php foreach ($siembras as $siembra): ?>
                                        <option value="<?php echo $siembra['sie_id']; ?>">
                                            <?php echo htmlspecialchars($siembra['tip_nombre'] . ' - ' . $siembra['lot_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="siembraId">Siembra *</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="tipoActividad" name="tipo" required>
                                        <option value="">Selecciona tipo</option>
                                        <option value="riego">Riego</option>
                                        <option value="fertilizacion">Fertilización</option>
                                        <option value="fumigacion">Fumigación</option>
                                        <option value="poda">Poda</option>
                                        <option value="deshierbe">Deshierbe</option>
                                        <option value="aporque">Aporque</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <label for="tipoActividad">Tipo de Actividad *</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="fechaActividad" name="fecha" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="fechaActividad">Fecha *</label>
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-file-text me-2"></i>Descripción de la Actividad
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="descripcionActividad" name="descripcion" 
                                              style="height: 100px" placeholder="Describe la actividad realizada..." required></textarea>
                                    <label for="descripcionActividad">Descripción de la Actividad *</label>
                                </div>
                            </div>
                        </div>

                        <!-- Productos utilizados -->
                        <div id="seccionProductos" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-flask me-2"></i>Productos Utilizados
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <textarea class="form-control" id="productosUtilizados" name="productos_utilizados" 
                                              style="height: 80px" placeholder="Listado de productos utilizados..."></textarea>
                                    <label for="productosUtilizados">Productos Utilizados</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="cantidadProducto" name="cantidad_producto" 
                                           step="0.01" min="0">
                                    <label for="cantidadProducto">Cantidad</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="unidadProducto" name="unidad_producto">
                                        <option value="ml">Mililitros (ml)</option>
                                        <option value="l">Litros (l)</option>
                                        <option value="g">Gramos (g)</option>
                                        <option value="kg">Kilogramos (kg)</option>
                                        <option value="lb">Libras (lb)</option>
                                        <option value="oz">Onzas (oz)</option>
                                        <option value="unidades">Unidades</option>
                                    </select>
                                    <label for="unidadProducto">Unidad</label>
                                </div>
                            </div>
                        </div>

                        <!-- Información de costo -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-dollar-sign me-2"></i>Información de Costo
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="costoActividad" name="costo" 
                                           step="0.01" min="0">
                                    <label for="costoActividad">Costo ($)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-sticky-note me-2"></i>Observaciones Adicionales
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="observacionesActividad" name="observaciones" 
                                              style="height: 100px" placeholder="Observaciones adicionales..."></textarea>
                                    <label for="observacionesActividad">Observaciones</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarActividad">
                        <i class="fas fa-save me-2"></i>Guardar Actividad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalVerActividad" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles de la Actividad
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesActividad">
                    <!-- Contenido dinámico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include('partials/footer.php'); ?>

    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="JS/actividades.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>