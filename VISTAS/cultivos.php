<?php
require_once '../CONFIG/auth.php';
require_once '../CONFIG/global.php';
require_once '../CONFIG/roles.php';

verificarSesion();

// Obtener permisos del usuario
$permisos = obtenerPermisosUsuario($_SESSION['rol']);

// Verificar permisos básicos para ver cultivos
if (!isset($permisos['cultivos']['ver']) || !$permisos['cultivos']['ver']) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'No tienes permisos para ver el catálogo de cultivos'
    ];
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Cultivos - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/cultivos.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../DataTables/datatables.min.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <div class="container-fluid py-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card header-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title mb-2">
                                    <i class="fas fa-seedling me-2"></i>
                                    Catálogo de Cultivos
                                </h1>
                                <p class="page-subtitle mb-0">
                                    Gestiona la información técnica y agronómica de los tipos de cultivos
                                </p>
                            </div>
                            <div class="header-actions">
                                <?php if (isset($permisos['cultivos']['crear']) && $permisos['cultivos']['crear']): ?>
                                <a href="cultivo_form.php?action=crear" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>
                                    Nuevo Cultivo
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?php echo $_SESSION['mensaje']['tipo'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $_SESSION['mensaje']['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['mensaje']['texto']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <!-- Mensaje de bienvenida si no hay errores -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>¡Bienvenido al Catálogo de Cultivos!</strong> Aquí puedes consultar toda la información técnica de los diferentes tipos de cultivos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filters-card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="searchInput" class="form-label">Buscar cultivos</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Nombre, nombre científico...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="categoriaFilter" class="form-label">Categoría</label>
                                <select class="form-select" id="categoriaFilter">
                                    <option value="">Todas las categorías</option>
                                    <option value="cereales">Cereales</option>
                                    <option value="hortalizas">Hortalizas</option>
                                    <option value="leguminosas">Leguminosas</option>
                                    <option value="frutales">Frutales</option>
                                    <option value="tuberculos">Tubérculos</option>
                                    <option value="aromaticas">Aromáticas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="cicloFilter" class="form-label">Ciclo de vida</label>
                                <select class="form-select" id="cicloFilter">
                                    <option value="">Todos los ciclos</option>
                                    <option value="anual">Anual</option>
                                    <option value="perenne">Perenne</option>
                                    <option value="bianual">Bianual</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                        <i class="fas fa-times me-1"></i>
                                        Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-4" id="statsCards">
            <div class="col-md-3">
                <div class="card stat-card cereales">
                    <div class="card-body text-center">
                        <i class="fas fa-wheat-awn stat-icon"></i>
                        <h3 class="stat-number" id="statCereales">0</h3>
                        <p class="stat-label">Cereales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card hortalizas">
                    <div class="card-body text-center">
                        <i class="fas fa-carrot stat-icon"></i>
                        <h3 class="stat-number" id="statHortalizas">0</h3>
                        <p class="stat-label">Hortalizas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card frutales">
                    <div class="card-body text-center">
                        <i class="fas fa-apple-alt stat-icon"></i>
                        <h3 class="stat-number" id="statFrutales">0</h3>
                        <p class="stat-label">Frutales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card total">
                    <div class="card-body text-center">
                        <i class="fas fa-seedling stat-icon"></i>
                        <h3 class="stat-number" id="statTotal">0</h3>
                        <p class="stat-label">Total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Cultivos -->
        <div class="row">
            <div class="col-12">
                <div class="card main-content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Listado de Cultivos
                        </h5>
                        <div class="card-actions">
                            <?php if (isset($permisos['cultivos']['exportar']) && $permisos['cultivos']['exportar']): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-1"></i>
                                    Exportar
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../CONTROLADORES/cultivos_c.php?action=exportar&formato=csv">
                                        <i class="fas fa-file-csv me-2"></i>CSV
                                    </a></li>
                                    <li><a class="dropdown-item" href="../CONTROLADORES/cultivos_c.php?action=exportar&formato=pdf">
                                        <i class="fas fa-file-pdf me-2"></i>PDF
                                    </a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="cultivosTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Nombre Científico</th>
                                        <th>Categoría</th>
                                        <th>Ciclo</th>
                                        <th>Días</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargarán vía AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Detalles -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>
                        Detalles del Cultivo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Contenido se carga vía AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Acción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmMessage">
                    <!-- Mensaje de confirmación -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmAction">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>

    <!-- Scripts -->
    <script>
        // Pasar permisos del usuario a JavaScript
        window.userPermissions = <?php echo json_encode($permisos); ?>;
    </script>
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="JS/cultivos.js"></script>
</body>
</html>