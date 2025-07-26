<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario
$usuario_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['user_name'];
$rol_usuario = $_SESSION['user_role'];
$email_usuario = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/inicio.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css">
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
                            <i class="fas fa-tachometer-alt me-2"></i>
                            ¡Bienvenido, <?php echo htmlspecialchars(explode(' ', $nombre_usuario)[0]); ?>!
                        </h1>
                        <p class="dashboard-subtitle">
                            Aquí tienes un resumen completo de tus cultivos y actividades del día
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="dashboard-date">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <span id="fecha-actual"></span>
                        </div>
                        <div class="dashboard-weather">
                            <i class="fas fa-cloud-sun me-2"></i>
                            <span>24°C - Parcialmente nublado</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de estadísticas principales -->
            <div class="row stats-cards mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-primary">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" data-target="12">0</h3>
                                    <p class="stat-label">Cultivos Activos</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="85"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-arrow-up text-success me-1"></i>
                                15% más que el mes pasado
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-success">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" data-target="2.5">0</h3>
                                    <p class="stat-label">Hectáreas Cultivadas</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="92"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-arrow-up text-success me-1"></i>
                                8% más que el trimestre anterior
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-warning">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" data-target="847">0</h3>
                                    <p class="stat-label">Kg Cosechados</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-weight-hanging"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="78"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-arrow-down text-warning me-1"></i>
                                3% menos que la temporada pasada
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card stat-card-info">
                        <div class="stat-card-body">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3 class="stat-number" data-target="98">0</h3>
                                    <p class="stat-label">% Salud General</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-heartbeat"></i>
                                </div>
                            </div>
                            <div class="stat-progress">
                                <div class="progress-bar" data-percentage="98"></div>
                            </div>
                            <small class="stat-description">
                                <i class="fas fa-check text-success me-1"></i>
                                Excelente estado general
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal del dashboard -->
            <div class="row">
                <!-- Panel izquierdo -->
                <div class="col-lg-8">
                    <!-- Gráfico de producción -->
                    <div class="dashboard-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line me-2"></i>
                                Producción Mensual
                            </h5>
                            <div class="card-actions">
                                <select class="form-select form-select-sm" id="periodo-produccion">
                                    <option value="6m">Últimos 6 meses</option>
                                    <option value="1y" selected>Último año</option>
                                    <option value="2y">Últimos 2 años</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="grafico-produccion" height="100"></canvas>
                        </div>
                    </div>

                    <!-- Tabla de cultivos recientes -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-leaf me-2"></i>
                                Cultivos Recientes
                            </h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">Ver todos</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cultivo</th>
                                            <th>Ubicación</th>
                                            <th>Estado</th>
                                            <th>Última actividad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-cultivos">
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="cultivo-icon bg-success me-2">
                                                        <i class="fas fa-pepper-hot"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Tomates Cherry</div>
                                                        <small class="text-muted">Plantado hace 45 días</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Sector A - Lote 3</td>
                                            <td><span class="badge bg-success">Floración</span></td>
                                            <td>
                                                <small>Riego - Hace 2 horas</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="cultivo-icon bg-warning me-2">
                                                        <i class="fas fa-carrot"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Zanahorias</div>
                                                        <small class="text-muted">Plantado hace 60 días</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Sector B - Lote 1</td>
                                            <td><span class="badge bg-warning">Crecimiento</span></td>
                                            <td>
                                                <small>Fertilización - Hace 1 día</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="cultivo-icon bg-info me-2">
                                                        <i class="fas fa-leaf"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Lechuga</div>
                                                        <small class="text-muted">Plantado hace 25 días</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Sector C - Lote 2</td>
                                            <td><span class="badge bg-primary">Desarrollo</span></td>
                                            <td>
                                                <small>Monitoreo - Hace 3 horas</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho -->
                <div class="col-lg-4">
                    <!-- Alertas y notificaciones -->
                    <div class="dashboard-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Alertas Activas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert-item">
                                <div class="alert-icon bg-warning">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div class="alert-content">
                                    <h6>Riego Pendiente</h6>
                                    <p>Tomates en Sector A necesitan riego</p>
                                    <small class="text-muted">Hace 30 minutos</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary">Marcar como leído</button>
                            </div>
                            <div class="alert-item">
                                <div class="alert-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="alert-content">
                                    <h6>Monitoreo Completado</h6>
                                    <p>Revisión semanal finalizada</p>
                                    <small class="text-muted">Hace 2 horas</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary">Ver detalles</button>
                            </div>
                            <div class="alert-item">
                                <div class="alert-icon bg-info">
                                    <i class="fas fa-cloud-rain"></i>
                                </div>
                                <div class="alert-content">
                                    <h6>Previsión Climática</h6>
                                    <p>Lluvia esperada mañana</p>
                                    <small class="text-muted">Hace 1 hora</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary">Ver pronóstico</button>
                            </div>
                        </div>
                    </div>

                    <!-- Calendario de actividades -->
                    <div class="dashboard-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Próximas Actividades
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="activity-item">
                                <div class="activity-date">
                                    <span class="day">15</span>
                                    <span class="month">Dic</span>
                                </div>
                                <div class="activity-content">
                                    <h6>Cosecha de Tomates</h6>
                                    <p class="text-muted">Sector A - Lote 3</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-date">
                                    <span class="day">18</span>
                                    <span class="month">Dic</span>
                                </div>
                                <div class="activity-content">
                                    <h6>Fertilización</h6>
                                    <p class="text-muted">Todos los sectores</p>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-date">
                                    <span class="day">22</span>
                                    <span class="month">Dic</span>
                                </div>
                                <div class="activity-content">
                                    <h6>Nueva Siembra</h6>
                                    <p class="text-muted">Sector D - Preparación</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-bolt me-2"></i>
                                Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary w-100">
                                        <i class="fas fa-plus-circle mb-1"></i>
                                        <br><small>Nuevo Cultivo</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-success w-100">
                                        <i class="fas fa-eye mb-1"></i>
                                        <br><small>Monitoreo</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-warning w-100">
                                        <i class="fas fa-tint mb-1"></i>
                                        <br><small>Programar Riego</small>
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info w-100">
                                        <i class="fas fa-file-alt mb-1"></i>
                                        <br><small>Generar Reporte</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>

    <!-- Container para alertas -->
    <div id="alert-container" class="alert-container-fixed"></div>

    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="partials/JS/footer.js"></script>
    <script src="JS/inicio.js"></script>
</body>
</html>