<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/siembras_m.php');
require_once('../MODELOS/lotes_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$siembra_modelo = new Siembra();
$lote_modelo = new Lote();

// Obtener siembras según permisos del usuario
$resultado_siembras = $siembra_modelo->listarSiembras($usuario_actual['id'], $usuario_actual['rol']);
$siembras = $resultado_siembras['success'] ? $resultado_siembras['siembras'] : array();

// Obtener lotes para el select
$resultado_lotes = $lote_modelo->listarLotes($usuario_actual['id'], $usuario_actual['rol']);
$lotes = $resultado_lotes['success'] ? $resultado_lotes['lotes'] : array();

// Obtener tipos de cultivo
$tipos_cultivo = array();
try {
    require_once('../CONFIG/Conexion.php');
    $conexion = new Conexion();
    $mysqli = $conexion->getMysqli();
    
    $sql = "SELECT tip_id, tip_nombre, tip_categoria, tip_ciclo_dias 
            FROM tipos_cultivos 
            WHERE tip_estado = 'activo' 
            ORDER BY tip_categoria, tip_nombre";
    
    $resultado = $mysqli->query($sql);
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $tipos_cultivo[] = $fila;
        }
    }
} catch (Exception $e) {
    error_log("Error obteniendo tipos de cultivo: " . $e->getMessage());
}

// Calcular estadísticas
$total_siembras = count($siembras);
$siembras_activas = 0;
$siembras_planificadas = 0;
$area_sembrada = 0;

foreach ($siembras as $siembra) {
    if ($siembra['sie_estado'] == 'en_crecimiento') {
        $siembras_activas++;
    } elseif ($siembra['sie_estado'] == 'planificada') {
        $siembras_planificadas++;
    }
    $area_sembrada += floatval($siembra['lot_area'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Siembras - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/siembras.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container">
        <!-- Header -->
        <div class="siembras-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-seedling me-2"></i>
                            <span class="d-block d-sm-inline">Gestión de Siembras</span>
                        </h2>
                        <p class="page-subtitle">
                            Planifica, registra y gestiona todas las siembras de tus cultivos
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevaSiembra">
                            <i class="fas fa-plus me-2"></i>
                            <span class="d-none d-sm-inline">Nueva </span>Siembra
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
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $total_siembras; ?></div>
                            <div class="stats-label">Total Siembras</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-success">
                        <div class="stats-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $siembras_activas; ?></div>
                            <div class="stats-label">En Crecimiento</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-warning">
                        <div class="stats-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $siembras_planificadas; ?></div>
                            <div class="stats-label">Planificadas</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stats-card stats-info">
                        <div class="stats-icon">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo number_format($area_sembrada, 1); ?></div>
                            <div class="stats-label">Hectáreas</div>
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
                                    <select class="form-select" id="filtroLote">
                                        <option value="">Todos los lotes</option>
                                        <?php foreach ($lotes as $lote): ?>
                                        <option value="<?php echo $lote['lot_id']; ?>">
                                            <?php echo htmlspecialchars($lote['lot_nombre'] . ' - ' . $lote['fin_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <select class="form-select" id="filtroEstado">
                                        <option value="">Todos los estados</option>
                                        <option value="planificada">Planificada</option>
                                        <option value="sembrada">Sembrada</option>
                                        <option value="en_crecimiento">En Crecimiento</option>
                                        <option value="cosechada">Cosechada</option>
                                        <option value="perdida">Perdida</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <select class="form-select" id="filtroCultivo">
                                        <option value="">Todos los cultivos</option>
                                        <?php foreach ($tipos_cultivo as $cultivo): ?>
                                        <option value="<?php echo $cultivo['tip_id']; ?>">
                                            <?php echo htmlspecialchars($cultivo['tip_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
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

        <!-- Tabla de siembras -->
        <div class="container">
            <div class="card table-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Registro de Siembras
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaSiembras">
                            <thead>
                                <tr>
                                    <th>Fecha Siembra</th>
                                    <th>Cultivo</th>
                                    <th>Lote</th>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                    <th>Fecha Cosecha</th>
                                    <th>Responsable</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($siembras as $siembra): ?>
                                <tr>
                                    <td>
                                        <span class="text-primary fw-bold">
                                            <?php echo date('d/m/Y', strtotime($siembra['sie_fecha_siembra'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cultivo-info">
                                            <div class="cultivo-nombre"><?php echo htmlspecialchars($siembra['tip_nombre']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($siembra['tip_categoria']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="lote-info">
                                            <div class="lote-nombre"><?php echo htmlspecialchars($siembra['lot_nombre']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($siembra['fin_nombre']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge estado-<?php echo $siembra['sie_estado']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $siembra['sie_estado'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $siembra['sie_cantidad_semilla'] ? number_format($siembra['sie_cantidad_semilla'], 2) . ' ' . $siembra['sie_unidad_semilla'] : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <?php echo $siembra['sie_fecha_estimada_cosecha'] ? date('d/m/Y', strtotime($siembra['sie_fecha_estimada_cosecha'])) : 'No estimada'; ?>
                                    </td>
                                    <td>
                                        <div class="responsable-info">
                                            <?php echo htmlspecialchars($siembra['responsable_nombre']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-ver" 
                                                    data-siembra-id="<?php echo $siembra['sie_id']; ?>"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-actividades" 
                                                    data-siembra-id="<?php echo $siembra['sie_id']; ?>"
                                                    title="Ver actividades">
                                                <i class="fas fa-tasks"></i>
                                            </button>
                                            <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                      ($usuario_actual['rol'] == 'agricultor' && $siembra['sie_responsable_id'] == $usuario_actual['id'])): ?>
                                            <button type="button" class="btn btn-outline-warning btn-editar" 
                                                    data-siembra-id="<?php echo $siembra['sie_id']; ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-eliminar" 
                                                    data-siembra-id="<?php echo $siembra['sie_id']; ?>"
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

    <!-- Modal Nueva Siembra -->
    <div class="modal fade" id="modalNuevaSiembra" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Nueva Siembra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevaSiembra">
                        <!-- Información básica -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-info-circle me-2"></i>Información Básica
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="loteId" name="lote_id" required>
                                        <option value="">Selecciona un lote</option>
                                        <?php foreach ($lotes as $lote): ?>
                                        <option value="<?php echo $lote['lot_id']; ?>" data-area="<?php echo $lote['lot_area']; ?>">
                                            <?php echo htmlspecialchars($lote['lot_nombre'] . ' - ' . $lote['fin_nombre'] . ' (' . $lote['lot_area'] . ' ha)'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="loteId">Lote *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="tipoCultivoId" name="tipo_cultivo_id" required>
                                        <option value="">Selecciona un cultivo</option>
                                        <?php 
                                        $categoria_actual = '';
                                        foreach ($tipos_cultivo as $cultivo): 
                                            if ($categoria_actual != $cultivo['tip_categoria']):
                                                if ($categoria_actual != '') echo '</optgroup>';
                                                echo '<optgroup label="' . ucfirst($cultivo['tip_categoria']) . '">';
                                                $categoria_actual = $cultivo['tip_categoria'];
                                            endif;
                                        ?>
                                        <option value="<?php echo $cultivo['tip_id']; ?>" data-ciclo="<?php echo $cultivo['tip_ciclo_dias']; ?>">
                                            <?php echo htmlspecialchars($cultivo['tip_nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php if ($categoria_actual != '') echo '</optgroup>'; ?>
                                    </select>
                                    <label for="tipoCultivoId">Tipo de Cultivo *</label>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-calendar me-2"></i>Planificación de Fechas
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="fechaSiembra" name="fecha_siembra" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="fechaSiembra">Fecha de Siembra *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" id="fechaEstimadaCosecha" name="fecha_estimada_cosecha">
                                    <label for="fechaEstimadaCosecha">Fecha Estimada de Cosecha</label>
                                    <div class="form-text">Se calculará automáticamente según el cultivo</div>
                                </div>
                            </div>
                        </div>

                        <!-- Detalles de siembra -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-seedling me-2"></i>Detalles de Siembra
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="cantidadSemilla" name="cantidad_semilla" 
                                           step="0.01" min="0">
                                    <label for="cantidadSemilla">Cantidad de Semilla</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="unidadSemilla" name="unidad_semilla">
                                        <option value="kg">Kilogramos (kg)</option>
                                        <option value="g">Gramos (g)</option>
                                        <option value="lb">Libras (lb)</option>
                                        <option value="semillas">Semillas</option>
                                        <option value="bolsas">Bolsas</option>
                                    </select>
                                    <label for="unidadSemilla">Unidad</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="densidadSiembra" name="densidad_siembra" 
                                           placeholder="Ej: 25 cm entre plantas">
                                    <label for="densidadSiembra">Densidad de Siembra</label>
                                </div>
                            </div>
                        </div>

                        <!-- Método y responsable -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="metodoSiembra" name="metodo_siembra">
                                        <option value="manual">Manual</option>
                                        <option value="mecanizada">Mecanizada</option>
                                        <option value="transplante">Transplante</option>
                                        <option value="directa">Siembra Directa</option>
                                    </select>
                                    <label for="metodoSiembra">Método de Siembra</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select" id="estadoSiembra" name="estado">
                                        <option value="planificada">Planificada</option>
                                        <option value="sembrada">Sembrada</option>
                                    </select>
                                    <label for="estadoSiembra">Estado Inicial</label>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="form-section-title">
                                    <i class="fas fa-sticky-note me-2"></i>Observaciones
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
                    <button type="button" class="btn btn-primary" id="btnGuardarSiembra">
                        <i class="fas fa-save me-2"></i>Guardar Siembra
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalVerSiembra" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles de la Siembra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detallesSiembra">
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
    <script src="JS/siembras.js"></script>
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