<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/monitoreo_m.php');
require_once('../MODELOS/siembras_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$monitoreo_modelo = new Monitoreo();
$siembra_modelo = new Siembra();

// Obtener monitoreos según permisos del usuario
$resultado_monitoreos = $monitoreo_modelo->listarMonitoreos($usuario_actual['id'], $usuario_actual['rol']);
$monitoreos = $resultado_monitoreos['success'] ? $resultado_monitoreos['monitoreos'] : array();

// Obtener siembras para el select
$resultado_siembras = $siembra_modelo->listarSiembras($usuario_actual['id'], $usuario_actual['rol']);
$siembras = $resultado_siembras['success'] ? $resultado_siembras['siembras'] : array();

// Calcular estadísticas
$total_monitoreos = count($monitoreos);
$monitoreos_hoy = 0;
$alertas_criticas = 0;
$siembras_monitoreadas = 0;

foreach ($monitoreos as $monitoreo) {
    if ($monitoreo['mon_fecha_observacion'] == date('Y-m-d')) {
        $monitoreos_hoy++;
    }
    if ($monitoreo['mon_estado_general'] == 'critico' || 
        $monitoreo['mon_presencia_plagas'] == 'severa' || 
        $monitoreo['mon_presencia_enfermedades'] == 'severa') {
        $alertas_criticas++;
    }
}

$siembras_monitoreadas = count(array_unique(array_column($monitoreos, 'mon_siembra_id')));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo de Cultivos - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/monitoreo.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container">
        <!-- Header -->
        <div class="monitoreo-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-eye me-2"></i>
                            <span class="d-block d-sm-inline">Monitoreo de Cultivos</span>
                        </h2>
                        <p class="page-subtitle">
                            Registro y seguimiento detallado del estado de tus cultivos
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor' || $usuario_actual['rol'] == 'supervisor'): ?>
                        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoMonitoreo">
                            <i class="fas fa-plus me-2"></i>
                            <span class="d-none d-sm-inline">Nuevo </span>Monitoreo
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
                            <div class="stats-number"><?php echo $total_monitoreos; ?></div>
                            <div class="stats-label">Total Monitoreos</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-success">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $monitoreos_hoy; ?></div>
                            <div class="stats-label">Hoy</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-warning">
                        <div class="stats-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $alertas_criticas; ?></div>
                            <div class="stats-label">Alertas Críticas</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-info">
                        <div class="stats-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $siembras_monitoreadas; ?></div>
                            <div class="stats-label">Siembras Monitoreadas</div>
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
                                <div class="col-md-4 mb-2">
                                    <select class="form-select" id="filtroSiembra">
                                        <option value="">Todas las siembras</option>
                                        <?php foreach ($siembras as $siembra): ?>
                                        <option value="<?php echo $siembra['sie_id']; ?>">
                                            <?php echo htmlspecialchars($siembra['tip_nombre'] . ' - ' . $siembra['lot_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <select class="form-select" id="filtroEstado">
                                        <option value="">Todos los estados</option>
                                        <option value="excelente">Excelente</option>
                                        <option value="bueno">Bueno</option>
                                        <option value="regular">Regular</option>
                                        <option value="malo">Malo</option>
                                        <option value="critico">Crítico</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="date" class="form-control" id="filtroFecha" placeholder="Fecha">
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

        <!-- Tabla de monitoreos -->
        <div class="container">
            <div class="card table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Historial de Monitoreos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaMonitoreos">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Siembra</th>
                                    <th>Estado General</th>
                                    <th>Plagas</th>
                                    <th>Enfermedades</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monitoreos as $monitoreo): ?>
                                <tr>
                                    <td>
                                        <span class="text-primary fw-bold">
                                            <?php echo date('d/m/Y', strtotime($monitoreo['mon_fecha_observacion'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="siembra-info">
                                            <div class="siembra-nombre"><?php echo htmlspecialchars($monitoreo['nombre_cultivo']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($monitoreo['nombre_lote']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge estado-<?php echo $monitoreo['mon_estado_general']; ?>">
                                            <?php echo ucfirst($monitoreo['mon_estado_general']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge plagas-<?php echo $monitoreo['mon_presencia_plagas']; ?>">
                                            <?php echo ucfirst($monitoreo['mon_presencia_plagas']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge enfermedades-<?php echo $monitoreo['mon_presencia_enfermedades']; ?>">
                                            <?php echo ucfirst($monitoreo['mon_presencia_enfermedades']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="responsable-info">
                                            <?php echo htmlspecialchars($monitoreo['responsable_nombre']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-ver" 
                                                    data-monitoreo-id="<?php echo $monitoreo['mon_id']; ?>"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                      ($usuario_actual['rol'] == 'agricultor' && $monitoreo['mon_responsable_id'] == $usuario_actual['id']) ||
                                                      ($usuario_actual['rol'] == 'supervisor')): ?>
                                            <button type="button" class="btn btn-outline-warning btn-editar" 
                                                    data-monitoreo-id="<?php echo $monitoreo['mon_id']; ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                      ($usuario_actual['rol'] == 'agricultor' && $monitoreo['mon_responsable_id'] == $usuario_actual['id'])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-eliminar" 
                                                    data-monitoreo-id="<?php echo $monitoreo['mon_id']; ?>"
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

    <!-- Modal Nuevo Monitoreo -->
    <div class="modal fade" id="modalNuevoMonitoreo" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Nuevo Monitoreo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoMonitoreo">
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
                                    <input type="date" class="form-control" id="fechaObservacion" name="fecha_observacion" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="fechaObservacion">Fecha de Observación *</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="time" class="form-control" id="horaObservacion" name="hora_observacion" 
                                           value="<?php echo date('H:i'); ?>">
                                    <label for="horaObservacion">Hora</label>
                                </div>
                            </div>
                        </div>

                        <!-- Parámetros de crecimiento -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-chart-line me-2"></i>Parámetros de Crecimiento
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="alturaPromedio" name="altura_promedio" 
                                           step="0.01" min="0" max="999.99">
                                    <label for="alturaPromedio">Altura Promedio (cm)</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="porcentajeGerminacion" name="porcentaje_germinacion" 
                                           step="0.01" min="0" max="100">
                                    <label for="porcentajeGerminacion">% Germinación</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="estadoGeneral" name="estado_general" required>
                                        <option value="">Selecciona...</option>
                                        <option value="excelente">Excelente</option>
                                        <option value="bueno">Bueno</option>
                                        <option value="regular">Regular</option>
                                        <option value="malo">Malo</option>
                                        <option value="critico">Crítico</option>
                                    </select>
                                    <label for="estadoGeneral">Estado General *</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="colorFollaje" name="color_follaje" 
                                           placeholder="Ej: Verde intenso">
                                    <label for="colorFollaje">Color del Follaje</label>
                                </div>
                            </div>
                        </div>

                        <!-- Control fitosanitario -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-bug me-2"></i>Control Fitosanitario
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="presenciaPlagas" name="presencia_plagas">
                                        <option value="ninguna">Ninguna</option>
                                        <option value="leve">Leve</option>
                                        <option value="moderada">Moderada</option>
                                        <option value="severa">Severa</option>
                                    </select>
                                    <label for="presenciaPlagas">Presencia de Plagas</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="tipoPlagas" name="tipo_plagas" 
                                           placeholder="Especifica los tipos de plagas">
                                    <label for="tipoPlagas">Tipo de Plagas</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="presenciaEnfermedades" name="presencia_enfermedades">
                                        <option value="ninguna">Ninguna</option>
                                        <option value="leve">Leve</option>
                                        <option value="moderada">Moderada</option>
                                        <option value="severa">Severa</option>
                                    </select>
                                    <label for="presenciaEnfermedades">Presencia de Enfermedades</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="tipoEnfermedades" name="tipo_enfermedades" 
                                           placeholder="Especifica los tipos de enfermedades">
                                    <label for="tipoEnfermedades">Tipo de Enfermedades</label>
                                </div>
                            </div>
                        </div>

                        <!-- Condiciones ambientales -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-cloud-sun me-2"></i>Condiciones Ambientales
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="condicionClima" name="condicion_clima" 
                                           placeholder="Ej: Soleado, nublado, lluvioso">
                                    <label for="condicionClima">Condición del Clima</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="humedadSuelo" name="humedad_suelo">
                                        <option value="seco">Seco</option>
                                        <option value="humedo" selected>Húmedo</option>
                                        <option value="saturado">Saturado</option>
                                    </select>
                                    <label for="humedadSuelo">Humedad del Suelo</label>
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
                                    <textarea class="form-control" id="observaciones" name="observaciones" 
                                              style="height: 100px" placeholder="Observaciones adicionales..."></textarea>
                                    <label for="observaciones">Observaciones</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarMonitoreo">
                        <i class="fas fa-save me-2"></i>Guardar Monitoreo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalVerMonitoreo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles del Monitoreo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesMonitoreo">
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
    <script src="JS/monitoreo.js"></script>
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