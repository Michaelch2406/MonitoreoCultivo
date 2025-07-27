<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/lotes_m.php');
require_once('../MODELOS/fincas_m.php');

// Verificar que el usuario esté logueado
requiereLogin('login.php');

$usuario_actual = obtenerUsuarioActual();
$lote_modelo = new Lote();
$finca_modelo = new Finca();

// Obtener lotes según permisos del usuario
$resultado_lotes = $lote_modelo->listarLotes($usuario_actual['id'], $usuario_actual['rol']);
$lotes = $resultado_lotes['success'] ? $resultado_lotes['lotes'] : array();

// Obtener fincas según permisos del usuario
$resultado_fincas = $finca_modelo->listarFincas($usuario_actual['id'], $usuario_actual['rol']);
$fincas = $resultado_fincas['success'] ? $resultado_fincas['fincas'] : array();

// Calcular estadísticas simples
$total_lotes = count($lotes);
$area_total = 0;
$lotes_disponibles = 0;
$lotes_sembrados = 0;

foreach ($lotes as $lote) {
    $area_total += floatval($lote['lot_area']);
    
    if ($lote['lot_estado'] == 'disponible') {
        $lotes_disponibles++;
    } elseif ($lote['lot_estado'] == 'sembrado') {
        $lotes_sembrados++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Lotes - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/lotes.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container mt-4">
        <!-- Header -->
        <div class="lotes-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-th-large me-2"></i>
                            <span class="d-block d-sm-inline">Gestión de Lotes</span>
                        </h2>
                        <p class="page-subtitle">
                            Administra los lotes y parcelas de tus fincas de manera eficiente
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                        <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalNuevoLote">
                            <i class="fas fa-plus me-2"></i>
                            <span class="d-none d-sm-inline">Nuevo </span>Lote
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
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $total_lotes; ?></div>
                            <div class="stats-label">Total Lotes</div>
                        </div>
                    </div>
                </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo number_format($area_total, 2); ?></div>
                        <div class="stats-label">Hectáreas Totales</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $lotes_disponibles; ?></div>
                        <div class="stats-label">Lotes Disponibles</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number"><?php echo $lotes_sembrados; ?></div>
                        <div class="stats-label">Lotes Sembrados</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controles y Filtros -->
        <div class="container mb-4">
            <div class="row">
                <div class="col-12">
                    <div class="card" data-aos="fade-up">
                        <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-filter me-2"></i>Filtros y Controles</h5>
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                        <button type="button" class="btn btn-primary btn-responsive" data-bs-toggle="modal" data-bs-target="#modalNuevoLote">
                            <i class="fas fa-plus me-2"></i>Nuevo Lote
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="filtroFinca" class="form-label">Finca</label>
                                <select id="filtroFinca" class="form-select">
                                    <option value="">Todas las fincas</option>
                                    <?php foreach ($fincas as $finca): ?>
                                    <option value="<?php echo $finca['finca_id']; ?>"><?php echo htmlspecialchars($finca['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filtroEstado" class="form-label">Estado</label>
                                <select id="filtroEstado" class="form-select">
                                    <option value="">Todos los estados</option>
                                    <option value="disponible">Disponible</option>
                                    <option value="sembrado">Sembrado</option>
                                    <option value="cosechado">Cosechado</option>
                                    <option value="en_preparacion">En Preparación</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filtroTipoSuelo" class="form-label">Tipo de Suelo</label>
                                <select id="filtroTipoSuelo" class="form-select">
                                    <option value="">Todos los tipos</option>
                                    <option value="arcilloso">Arcilloso</option>
                                    <option value="arenoso">Arenoso</option>
                                    <option value="limoso">Limoso</option>
                                    <option value="franco">Franco</option>
                                    <option value="humifero">Humífero</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filtroAreaMin" class="form-label">Área Mín (ha)</label>
                                <input type="number" id="filtroAreaMin" class="form-control" step="0.1" min="0">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="filtroAreaMax" class="form-label">Área Máx (ha)</label>
                                <input type="number" id="filtroAreaMax" class="form-control" step="0.1" min="0">
                            </div>
                            <div class="col-md-1 mb-2 d-flex align-items-end">
                                <button type="button" id="btnLimpiarFiltros" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de Lotes -->
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card" data-aos="fade-up">
                        <div class="card-header">
                            <h5><i class="fas fa-list me-2"></i>Lista de Lotes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaLotes" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre del Lote</th>
                                        <th>Finca</th>
                                        <th>Área (ha)</th>
                                        <th>Tipo de Suelo</th>
                                        <th>pH Suelo</th>
                                        <th>Estado</th>
                                        <th>Propietario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lotes as $lote): ?>
                                    <tr>
                                        <td><?php echo $lote['lot_id']; ?></td>
                                        <td>
                                            <div class="lote-info">
                                                <strong><?php echo htmlspecialchars($lote['lot_nombre']); ?></strong>
                                                <?php if (!empty($lote['lot_descripcion'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($lote['lot_descripcion'], 0, 50)) . (strlen($lote['lot_descripcion']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="finca-info">
                                                <strong><?php echo htmlspecialchars($lote['fin_nombre']); ?></strong>
                                                <br><small class="text-muted"><?php echo number_format($lote['fin_area_total'], 2); ?> ha totales</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="area-badge"><?php echo number_format($lote['lot_area'], 2); ?> ha</span>
                                        </td>
                                        <td><?php echo $lote['lot_tipo_suelo'] ? htmlspecialchars($lote['lot_tipo_suelo']) : '<span class="text-muted">No especificado</span>'; ?></td>
                                        <td><?php echo $lote['lot_ph_suelo'] ? number_format($lote['lot_ph_suelo'], 1) : '<span class="text-muted">No medido</span>'; ?></td>
                                        <td>
                                            <?php
                                            $badge_class = '';
                                            switch ($lote['lot_estado']) {
                                                case 'disponible':
                                                    $badge_class = 'bg-success';
                                                    break;
                                                case 'sembrado':
                                                    $badge_class = 'bg-info';
                                                    break;
                                                case 'cosechado':
                                                    $badge_class = 'bg-warning';
                                                    break;
                                                case 'en_preparacion':
                                                    $badge_class = 'bg-secondary';
                                                    break;
                                                default:
                                                    $badge_class = 'bg-light text-dark';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($lote['lot_estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="propietario-info">
                                                <strong><?php echo htmlspecialchars($lote['usu_nombre'] . ' ' . $lote['usu_apellido']); ?></strong>
                                                <?php if (!empty($lote['usu_email'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($lote['usu_email']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group-actions">
                                                <button type="button" class="btn btn-outline-info btn-sm btn-ver-lote" 
                                                        data-id="<?php echo $lote['lot_id']; ?>" 
                                                        title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                         ($usuario_actual['rol'] == 'agricultor' && $lote['fin_propietario'] == $usuario_actual['id']) ||
                                                         $usuario_actual['rol'] == 'supervisor'): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-editar-lote" 
                                                        data-id="<?php echo $lote['lot_id']; ?>" 
                                                        title="Editar lote">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                         ($usuario_actual['rol'] == 'agricultor' && $lote['fin_propietario'] == $usuario_actual['id'])): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-lote" 
                                                        data-id="<?php echo $lote['lot_id']; ?>" 
                                                        data-nombre="<?php echo htmlspecialchars($lote['lot_nombre']); ?>"
                                                        title="Eliminar lote">
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
        </div>
    </div>

    <!-- Modal Nuevo Lote -->
    <div class="modal fade" id="modalNuevoLote" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Registrar Nuevo Lote
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoLote">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nuevoNombreLote" class="form-label">Nombre del Lote *</label>
                                <input type="text" class="form-control" id="nuevoNombreLote" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nuevaFinca" class="form-label">Finca *</label>
                                <select class="form-select" id="nuevaFinca" name="finca_id" required>
                                    <option value="">Seleccionar finca</option>
                                    <?php foreach ($fincas as $finca): ?>
                                    <option value="<?php echo $finca['finca_id']; ?>"><?php echo htmlspecialchars($finca['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nuevaArea" class="form-label">Área (hectáreas) *</label>
                                <input type="number" class="form-control" id="nuevaArea" name="area" step="0.0001" min="0.0001" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nuevoTipoSuelo" class="form-label">Tipo de Suelo</label>
                                <select class="form-select" id="nuevoTipoSuelo" name="tipo_suelo">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="arcilloso">Arcilloso</option>
                                    <option value="arenoso">Arenoso</option>
                                    <option value="limoso">Limoso</option>
                                    <option value="franco">Franco</option>
                                    <option value="humifero">Humífero</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nuevoPHSuelo" class="form-label">pH del Suelo</label>
                                <input type="number" class="form-control" id="nuevoPHSuelo" name="ph_suelo" step="0.1" min="0" max="14">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="nuevaDescripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="nuevaDescripcion" name="descripcion" rows="3" placeholder="Descripción opcional del lote"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Registrar Lote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Lote -->
    <div class="modal fade" id="modalEditarLote" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Editar Lote
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarLote">
                    <input type="hidden" id="editarLoteId" name="lote_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editarNombreLote" class="form-label">Nombre del Lote *</label>
                                <input type="text" class="form-control" id="editarNombreLote" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editarArea" class="form-label">Área (hectáreas) *</label>
                                <input type="number" class="form-control" id="editarArea" name="area" step="0.0001" min="0.0001" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editarTipoSuelo" class="form-label">Tipo de Suelo</label>
                                <select class="form-select" id="editarTipoSuelo" name="tipo_suelo">
                                    <option value="">Seleccionar tipo</option>
                                    <option value="arcilloso">Arcilloso</option>
                                    <option value="arenoso">Arenoso</option>
                                    <option value="limoso">Limoso</option>
                                    <option value="franco">Franco</option>
                                    <option value="humifero">Humífero</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editarPHSuelo" class="form-label">pH del Suelo</label>
                                <input type="number" class="form-control" id="editarPHSuelo" name="ph_suelo" step="0.1" min="0" max="14">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="editarEstado" class="form-label">Estado</label>
                                <select class="form-select" id="editarEstado" name="estado">
                                    <option value="disponible">Disponible</option>
                                    <option value="sembrado">Sembrado</option>
                                    <option value="cosechado">Cosechado</option>
                                    <option value="en_preparacion">En Preparación</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="editarDescripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="editarDescripcion" name="descripcion" rows="3" placeholder="Descripción opcional del lote"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Lote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('partials/footer.php'); ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <!-- Usuario actual para JavaScript -->
    <script>
        window.usuarioActual = {
            id: <?php echo $usuario_actual['id']; ?>,
            rol: '<?php echo $usuario_actual['rol']; ?>'
        };
        
        // Inicializar AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    </script>
    
    <script src="JS/lotes.js"></script>
</body>
</html>