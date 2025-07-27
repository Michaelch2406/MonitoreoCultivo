<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/fincas_m.php');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$usuario_actual = obtenerUsuarioActual();
$finca_modelo = new Finca();

// Obtener fincas según permisos del usuario
$fincas = $finca_modelo->listarFincas($usuario_actual['id'], $usuario_actual['rol']);

// Obtener agricultores para filtros (solo administradores y supervisores)
$agricultores = array();
if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'supervisor') {
    $agricultores = $finca_modelo->obtenerAgricultores();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Fincas - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/fincas.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>

    <div class="container-fluid main-container mt-4">
        <!-- Header -->
        <div class="fincas-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            <span class="d-block d-sm-inline">Gestión de Fincas</span>
                        </h2>
                        <p class="page-subtitle">
                            <?php if ($usuario_actual['rol'] == 'administrador'): ?>
                                <span class="d-block d-sm-inline">Administra todas las fincas del sistema</span>
                            <?php elseif ($usuario_actual['rol'] == 'agricultor'): ?>
                                <span class="d-block d-sm-inline">Gestiona tus fincas registradas</span>
                            <?php else: ?>
                                <span class="d-block d-sm-inline">Supervisa las fincas asignadas</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end text-center">
                        <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
                            <button type="button" class="btn btn-primary btn-responsive" data-bs-toggle="modal" data-bs-target="#modalNuevaFinca">
                                <i class="fas fa-plus me-2"></i>
                                <span class="d-none d-sm-inline">Nueva Finca</span>
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
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="totalFincas">
                            <?php echo $fincas['success'] ? count($fincas['fincas']) : 0; ?>
                        </h3>
                        <p class="stats-label">Total Fincas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="fincasActivas">
                            <?php 
                            $activas = 0;
                            if ($fincas['success']) {
                                foreach ($fincas['fincas'] as $finca) {
                                    if ($finca['estado'] == 'activa') $activas++;
                                }
                            }
                            echo $activas;
                            ?>
                        </h3>
                        <p class="stats-label">Fincas Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-ruler-combined"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="areaTotal">
                            <?php 
                            $area_total = 0;
                            if ($fincas['success']) {
                                foreach ($fincas['fincas'] as $finca) {
                                    $area_total += $finca['area_total'];
                                }
                            }
                            echo number_format($area_total, 1);
                            ?>
                        </h3>
                        <p class="stats-label">Hectáreas Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number" id="totalLotes">
                            <?php 
                            $lotes_total = 0;
                            if ($fincas['success']) {
                                foreach ($fincas['fincas'] as $finca) {
                                    $lotes_total += $finca['total_lotes'];
                                }
                            }
                            echo $lotes_total;
                            ?>
                        </h3>
                        <p class="stats-label">Lotes Totales</p>
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
                    <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'supervisor'): ?>
                    <div class="col-md-3">
                        <label for="filtroPropietario" class="form-label">Propietario:</label>
                        <select class="form-select" id="filtroPropietario">
                            <option value="">Todos los propietarios</option>
                            <?php if ($agricultores['success']): ?>
                                <?php foreach ($agricultores['agricultores'] as $agricultor): ?>
                                    <option value="<?php echo $agricultor['usu_id']; ?>">
                                        <?php echo htmlspecialchars($agricultor['usu_nombre'] . ' ' . $agricultor['usu_apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <label for="filtroEstado" class="form-label">Estado:</label>
                        <select class="form-select" id="filtroEstado">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtroAreaMin" class="form-label">Área mín. (ha):</label>
                        <input type="number" class="form-control" id="filtroAreaMin" step="0.1" min="0">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="filtroAreaMax" class="form-label">Área máx. (ha):</label>
                        <input type="number" class="form-control" id="filtroAreaMax" step="0.1" min="0">
                    </div>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="btnLimpiarFiltros">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Fincas -->
        <div class="card" data-aos="fade-up">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaFincas" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Ubicación</th>
                                <th>Propietario</th>
                                <th>Área (ha)</th>
                                <th class="d-none d-md-table-cell">Lotes</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fincas['success']): ?>
                                <?php foreach ($fincas['fincas'] as $finca): ?>
                                    <tr data-finca-id="<?php echo $finca['finca_id']; ?>">
                                        <td><?php echo $finca['finca_id']; ?></td>
                                        <td>
                                            <div class="finca-info">
                                                <strong><?php echo htmlspecialchars($finca['nombre']); ?></strong>
                                                <?php if ($finca['descripcion']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($finca['descripcion'], 0, 50)) . '...'; ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ubicacion-info">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($finca['ubicacion']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="propietario-info">
                                                <strong><?php echo htmlspecialchars($finca['propietario_nombre'] . ' ' . $finca['propietario_apellido']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($finca['propietario_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="area-badge"><?php echo number_format($finca['area_total'], 1); ?> ha</span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge bg-info"><?php echo $finca['total_lotes']; ?> lotes</span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $finca['estado'] == 'activa' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($finca['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($finca['fecha_registro'])); ?></td>
                                        <td>
                                            <div class="btn-group-actions">
                                                <!-- Ver detalles -->
                                                <button type="button" class="btn btn-sm btn-outline-info btn-ver-finca" 
                                                        data-id="<?php echo $finca['finca_id']; ?>" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($usuario_actual['rol'] == 'administrador' || 
                                                         ($usuario_actual['rol'] == 'agricultor' && $finca['propietario_nombre'])): ?>
                                                <!-- Editar -->
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-editar-finca" 
                                                        data-id="<?php echo $finca['finca_id']; ?>" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <!-- Eliminar -->
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-finca" 
                                                        data-id="<?php echo $finca['finca_id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($finca['nombre']); ?>" title="Eliminar">
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

    <!-- Modal Nueva Finca -->
    <?php if ($usuario_actual['rol'] == 'administrador' || $usuario_actual['rol'] == 'agricultor'): ?>
    <div class="modal fade" id="modalNuevaFinca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Registrar Nueva Finca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevaFinca">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoNombreFinca" class="form-label">Nombre de la Finca *</label>
                                    <input type="text" class="form-control" id="nuevoNombreFinca" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevoAreaTotal" class="form-label">Área Total (hectáreas) *</label>
                                    <input type="number" class="form-control" id="nuevoAreaTotal" name="area_total" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nuevaUbicacion" class="form-label">Ubicación *</label>
                            <textarea class="form-control" id="nuevaUbicacion" name="ubicacion" rows="2" required placeholder="Ingrese la ubicación completa de la finca (dirección, municipio, departamento)"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaLatitud" class="form-label">Latitud</label>
                                    <input type="number" class="form-control" id="nuevaLatitud" name="latitud" step="0.00000001">
                                    <div class="form-text">Ejemplo: 4.570868</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nuevaLongitud" class="form-label">Longitud</label>
                                    <input type="number" class="form-control" id="nuevaLongitud" name="longitud" step="0.00000001">
                                    <div class="form-text">Ejemplo: -74.297333</div>
                                </div>
                            </div>
                        </div>

                        <?php if ($usuario_actual['rol'] == 'administrador'): ?>
                        <div class="mb-3">
                            <label for="nuevoPropietario" class="form-label">Propietario *</label>
                            <select class="form-select" id="nuevoPropietario" name="propietario_id" required>
                                <option value="">Seleccionar propietario</option>
                                <?php if ($agricultores['success']): ?>
                                    <?php foreach ($agricultores['agricultores'] as $agricultor): ?>
                                        <option value="<?php echo $agricultor['usu_id']; ?>">
                                            <?php echo htmlspecialchars($agricultor['usu_nombre'] . ' ' . $agricultor['usu_apellido']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="nuevaDescripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="nuevaDescripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Registrar Finca
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Finca -->
    <div class="modal fade" id="modalEditarFinca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Finca
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarFinca">
                    <input type="hidden" id="editarFincaId" name="finca_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarNombreFinca" class="form-label">Nombre de la Finca *</label>
                                    <input type="text" class="form-control" id="editarNombreFinca" name="nombre" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarAreaTotal" class="form-label">Área Total (hectáreas) *</label>
                                    <input type="number" class="form-control" id="editarAreaTotal" name="area_total" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editarUbicacion" class="form-label">Ubicación *</label>
                            <textarea class="form-control" id="editarUbicacion" name="ubicacion" rows="2" required placeholder="Ingrese la ubicación completa de la finca"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarLatitud" class="form-label">Latitud</label>
                                    <input type="number" class="form-control" id="editarLatitud" name="latitud" step="0.00000001">
                                    <div class="form-text">Ejemplo: 4.570868</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editarLongitud" class="form-label">Longitud</label>
                                    <input type="number" class="form-control" id="editarLongitud" name="longitud" step="0.00000001">
                                    <div class="form-text">Ejemplo: -74.297333</div>
                                </div>
                            </div>
                        </div>

                        <?php if ($usuario_actual['rol'] == 'administrador'): ?>
                        <div class="mb-3">
                            <label for="editarPropietario" class="form-label">Propietario *</label>
                            <select class="form-select" id="editarPropietario" name="propietario_id" required>
                                <option value="">Seleccionar propietario</option>
                                <?php if ($agricultores['success']): ?>
                                    <?php foreach ($agricultores['agricultores'] as $agricultor): ?>
                                        <option value="<?php echo $agricultor['usu_id']; ?>">
                                            <?php echo htmlspecialchars($agricultor['usu_nombre'] . ' ' . $agricultor['usu_apellido']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="editarDescripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="editarDescripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editarEstado" class="form-label">Estado</label>
                            <select class="form-select" id="editarEstado" name="estado">
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Finca
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
    <script src="JS/fincas.js"></script>
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