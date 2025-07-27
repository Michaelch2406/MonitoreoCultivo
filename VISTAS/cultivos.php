<?php
session_start();
require_once '../CONFIG/global.php';
require_once '../CONFIG/roles.php';

// Verificar sesión simple
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtener datos del usuario actual
$usuario_actual = obtenerUsuarioActual();
$permisos = obtenerPermisosUsuario($usuario_actual['rol']);

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
    <link href="../DataTables/datatables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/cultivos.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Header del Dashboard -->
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="dashboard-title">
                            <i class="fas fa-seedling me-2"></i>
                            Catálogo de Cultivos - AgroMonitor
                        </h1>
                        <p class="dashboard-subtitle">
                            Gestiona la información técnica y agronómica de los tipos de cultivos
                        </p>
                        <div class="admin-badge">
                            <i class="fas fa-leaf me-1"></i>
                            Módulo de Cultivos
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (isset($permisos['cultivos']['crear']) && $permisos['cultivos']['crear']): ?>
                        <a href="cultivo_form.php?action=crear" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>
                            Nuevo Cultivo
                        </a>
                        <?php endif; ?>
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

            <!-- Filtros y Búsqueda -->
            <div class="dashboard-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-filter me-2"></i>
                        Filtros de Búsqueda
                    </h5>
                </div>
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

            <!-- Tarjetas de estadísticas -->
            <div class="row stats-cards mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" id="statCereales">0</h3>
                                    <p class="stat-label">Cereales</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-wheat-awn"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="75"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-leaf text-success me-1"></i>
                                Granos básicos
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" id="statHortalizas">0</h3>
                                    <p class="stat-label">Hortalizas</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-carrot"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="85"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-leaf text-success me-1"></i>
                                Vegetales frescos
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" id="statFrutales">0</h3>
                                    <p class="stat-label">Frutales</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-apple-alt"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="60"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-tree text-success me-1"></i>
                                Árboles frutales
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" id="statTotal">0</h3>
                                    <p class="stat-label">Total Cultivos</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="100"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-check text-success me-1"></i>
                                Catálogo completo
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de cultivos -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-leaf me-2"></i>
                        Catálogo de Cultivos
                    </h5>
                    <a href="#" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cultivosTable" class="table table-striped table-hover">
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
    </main>

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
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="../DataTables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Pasar permisos del usuario a JavaScript
        window.userPermissions = <?php echo json_encode($permisos); ?>;
        
        // Inicializar AOS
        AOS.init({
            duration: 600,
            once: true,
            offset: 100
        });
    </script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="JS/cultivos.js"></script>
</body>
</html>