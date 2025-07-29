<?php
// Iniciar sesión
session_start();

// Incluir sistema de roles
require_once '../CONFIG/roles.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtener datos del usuario
$usuario = obtenerUsuarioActual();
$usuario_id = $usuario['id'] ?? $_SESSION['user_id'];
$nombre_usuario = $usuario['nombre'] ?? $_SESSION['user_name'];
$rol_usuario = $usuario['rol'] ?? $_SESSION['rol'];
$email_usuario = $usuario['email'] ?? $_SESSION['user_email'];

// Incluir conexión para estadísticas
require_once '../CONFIG/Conexion.php';
$conexion = new Conexion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
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
                        <?php if ($rol_usuario == 'administrador'): ?>
                            <h1 class="dashboard-title">
                                <i class="fas fa-crown me-2"></i>
                                Panel de Administración - <?php echo htmlspecialchars(explode(' ', $nombre_usuario)[0]); ?>
                            </h1>
                            <p class="dashboard-subtitle">
                                Control total del sistema AgroMonitor - Gestión de usuarios, cultivos y configuración global
                            </p>
                        <?php elseif ($rol_usuario == 'agricultor'): ?>
                            <h1 class="dashboard-title">
                                <i class="fas fa-seedling me-2"></i>
                                Panel del Agricultor - <?php echo htmlspecialchars(explode(' ', $nombre_usuario)[0]); ?>
                            </h1>
                            <p class="dashboard-subtitle">
                                Gestiona tus fincas, cultivos y monitorea tu producción agrícola
                            </p>
                        <?php elseif ($rol_usuario == 'supervisor'): ?>
                            <h1 class="dashboard-title">
                                <i class="fas fa-binoculars me-2"></i>
                                Panel de Supervisión - <?php echo htmlspecialchars(explode(' ', $nombre_usuario)[0]); ?>
                            </h1>
                            <p class="dashboard-subtitle">
                                Supervisa múltiples fincas y monitorea el progreso de los cultivos
                            </p>
                        <?php endif; ?>
                        <div class="admin-badge">
                            <i class="fas fa-shield-alt me-1"></i>
                            <?php echo obtenerTextoRol($rol_usuario); ?>
                        </div>
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

            <!-- Tarjetas de estadísticas principales - Basadas en rol -->
            <div class="row stats-cards mb-4">
                <?php if ($rol_usuario == 'administrador'): ?>
                    <!-- Estadísticas del Administrador -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM usuarios WHERE usu_estado = 'activo'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Usuarios Activos</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
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
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM fincas WHERE fin_estado = 'activa'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Fincas Totales</p>
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
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM siembras WHERE sie_estado IN ('sembrada', 'en_crecimiento')");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Siembras Activas</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-seedling"></i>
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
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM monitoreo WHERE estado = 'pendiente'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Tareas del Sistema</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-cogs"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="98"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-check text-success me-1"></i>
                                    Sistema operativo
                                </small>
                            </div>
                        </div>
                    </div>

                <?php elseif ($rol_usuario == 'agricultor'): ?>
                    <!-- Estadísticas del Agricultor -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-success">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM fincas WHERE fin_propietario = '$usuario_id' AND fin_estado = 'activa'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Mis Fincas</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="100"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-leaf text-success me-1"></i>
                                    Todas activas
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM siembras s JOIN lotes l ON s.sie_lote = l.lot_id JOIN fincas f ON l.lot_finca = f.fin_id WHERE f.fin_propietario = '$usuario_id' AND s.sie_estado IN ('sembrada', 'en_crecimiento')");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Siembras Activas</p>
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
                                    En crecimiento saludable
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM cosechas c JOIN lotes l ON c.cos_lote = l.lot_id JOIN fincas f ON l.lot_finca = f.fin_id WHERE f.fin_propietario = '$usuario_id' AND MONTH(c.cos_fecha) = MONTH(CURDATE()) AND YEAR(c.cos_fecha) = YEAR(CURDATE())");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Cosechas Este Mes</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-apple-alt"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="70"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-calendar text-info me-1"></i>
                                    Mes actual
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-info">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM monitoreo m JOIN lotes l ON m.lote_id = l.lot_id JOIN fincas f ON l.lot_finca = f.fin_id WHERE f.fin_propietario = '$usuario_id' AND m.estado = 'pendiente'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Tareas Pendientes</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="95"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-check text-success me-1"></i>
                                    Al día con las tareas
                                </small>
                            </div>
                        </div>
                    </div>

                <?php elseif ($rol_usuario == 'supervisor'): ?>
                    <!-- Estadísticas del Supervisor -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-warning">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(DISTINCT f.fin_id) as total FROM fincas f JOIN supervisor_fincas sf ON f.fin_id = sf.finca_id WHERE sf.supervisor_id = '$usuario_id' AND f.fin_estado = 'activa'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Fincas Supervisadas</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-binoculars"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="88"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-eye text-info me-1"></i>
                                    Bajo supervisión
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-success">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(DISTINCT u.usu_id) as total FROM usuarios u JOIN fincas f ON u.usu_id = f.fin_propietario JOIN supervisor_fincas sf ON f.fin_id = sf.finca_id WHERE sf.supervisor_id = '$usuario_id' AND u.usu_estado = 'activo'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Agricultores Asignados</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="92"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-handshake text-success me-1"></i>
                                    Colaboración activa
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM reportes_supervision rs WHERE rs.supervisor_id = '$usuario_id' AND DATE(rs.fecha_reporte) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Reportes Esta Semana</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="75"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-calendar-week text-info me-1"></i>
                                    Última semana
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card stat-card-info">
                            <div class="stat-card-body">
                                <div class="stat-content">
                                    <div class="stat-info">
                                        <h3 class="stat-number" data-target="<?php 
                                            $stmt = $conexion->getMysqli()->query("SELECT COUNT(*) as total FROM alertas_supervision als WHERE als.supervisor_id = '$usuario_id' AND als.estado = 'pendiente'");
                                            $result = $stmt ? $stmt->fetch_assoc() : ['total' => 0];
                                            echo $result['total'];
                                        ?>">0</h3>
                                        <p class="stat-label">Alertas Pendientes</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress-bar" data-percentage="65"></div>
                                </div>
                                <small class="stat-description">
                                    <i class="fas fa-bell text-warning me-1"></i>
                                    Requieren atención
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contenido principal del dashboard -->
            <div class="row">
                <!-- Panel izquierdo -->
                <div class="col-lg-8">
                    <?php if ($rol_usuario == 'administrador'): ?>
                        <!-- Dashboard de Administrador -->
                        <!-- Gráfico de estadísticas generales del sistema -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estadísticas Generales del Sistema
                                </h5>
                                <div class="card-actions">
                                    <select class="form-select form-select-sm" id="periodo-sistema">
                                        <option value="7d">Últimos 7 días</option>
                                        <option value="30d" selected>Últimos 30 días</option>
                                        <option value="90d">Últimos 3 meses</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="grafico-sistema" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Gráfico de usuarios activos -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-users me-2"></i>
                                    Actividad de Usuarios
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="grafico-usuarios" height="80"></canvas>
                            </div>
                        </div>

                    <?php elseif ($rol_usuario == 'agricultor'): ?>
                        <!-- Dashboard del Agricultor -->
                        <!-- Gráfico de producción -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Mi Producción Mensual
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

                        <!-- Gráfico de gastos vs ingresos -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Gastos vs Ingresos
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="grafico-gastos-ingresos" height="80"></canvas>
                            </div>
                        </div>

                    <?php elseif ($rol_usuario == 'supervisor'): ?>
                        <!-- Dashboard del Supervisor -->
                        <!-- Gráfico de fincas supervisadas -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-map-marked-alt me-2"></i>
                                    Resumen de Fincas Supervisadas
                                </h5>
                                <div class="card-actions">
                                    <select class="form-select form-select-sm" id="periodo-supervision">
                                        <option value="semana">Esta semana</option>
                                        <option value="mes" selected>Este mes</option>
                                        <option value="trimestre">Este trimestre</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="grafico-supervision" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Gráfico de rendimiento por finca -->
                        <div class="dashboard-card mb-4">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-seedling me-2"></i>
                                    Rendimiento por Finca
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="grafico-rendimiento" height="80"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>

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
                                <i class="fas fa-cogs me-2"></i>
                                <?php echo $rol_usuario == 'administrador' ? 'Panel de Control' : 'Acciones Rápidas'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <?php if ($rol_usuario == 'administrador'): ?>
                                    <div class="col-6">
                                        <a href="usuarios.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-users mb-1"></i>
                                            <br><small>Gestionar Usuarios</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="fincas.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-map-marked-alt mb-1"></i>
                                            <br><small>Gestionar Fincas</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/reportes.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-chart-bar mb-1"></i>
                                            <br><small>Reportes Sistema</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="admin/configuracion.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-cog mb-1"></i>
                                            <br><small>Configuración</small>
                                        </a>
                                    </div>
                                <?php elseif ($rol_usuario == 'agricultor'): ?>
                                    <div class="col-6">
                                        <a href="fincas.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-map-marked-alt mb-1"></i>
                                            <br><small>Mis Fincas</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="siembras/index.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-seedling mb-1"></i>
                                            <br><small>Mis Siembras</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="monitoreo/index.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-eye mb-1"></i>
                                            <br><small>Monitoreo</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="cosechas/index.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-apple-alt mb-1"></i>
                                            <br><small>Cosechas</small>
                                        </a>
                                    </div>
                                <?php elseif ($rol_usuario == 'supervisor'): ?>
                                    <div class="col-6">
                                        <a href="supervisor/agricultores.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-users mb-1"></i>
                                            <br><small>Agricultores</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="fincas.php" class="btn btn-outline-success w-100">
                                            <i class="fas fa-map-marked-alt mb-1"></i>
                                            <br><small>Fincas Supervisadas</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="supervisor/monitoreo.php" class="btn btn-outline-warning w-100">
                                            <i class="fas fa-binoculars mb-1"></i>
                                            <br><small>Supervisión</small>
                                        </a>
                                    </div>
                                    <div class="col-6">
                                        <a href="supervisor/reportes.php" class="btn btn-outline-info w-100">
                                            <i class="fas fa-clipboard-list mb-1"></i>
                                            <br><small>Reportes</small>
                                        </a>
                                    </div>
                                <?php endif; ?>
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
    
    <!-- Configuración del usuario para JavaScript -->
    <script>
        window.userRole = '<?php echo $rol_usuario; ?>';
        window.userId = '<?php echo $usuario_id; ?>';
        window.userName = '<?php echo htmlspecialchars($nombre_usuario); ?>';
    </script>
    
    <script src="JS/dashboard.js"></script>
</body>
</html>