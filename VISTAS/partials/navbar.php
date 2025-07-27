<?php
// Verificar si el usuario está logueado de forma simple
$usuario_logueado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if ($usuario_logueado) {
    $nombre_usuario = $_SESSION['user_name'] ?? '';
    $rol_usuario = $_SESSION['user_role'] ?? '';
    $email_usuario = $_SESSION['user_email'] ?? '';
} else {
    $nombre_usuario = '';
    $rol_usuario = '';
    $email_usuario = '';
}

// Función simple para obtener texto del rol
function obtenerTextoRolSimple($rol) {
    $roles = array(
        'administrador' => 'Administrador',
        'agricultor' => 'Agricultor',
        'supervisor' => 'Supervisor'
    );
    return isset($roles[$rol]) ? $roles[$rol] : $rol;
}
?>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <!-- Logo y Título -->
        <a class="navbar-brand d-flex align-items-center" href="inicio.php" id="navbar-brand">
            <i class="fas fa-seedling brand-icon me-2"></i>
            <span class="brand-text">AgroMonitor</span>
        </a>

        <!-- Botón hamburguesa para móvil -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú de navegación -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if ($usuario_logueado): ?>
            <!-- Menú principal - Solo para usuarios logueados -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php" id="nav-dashboard">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                
                <?php if ($rol_usuario == 'administrador'): ?>
                    <!-- Menú específico para Administradores -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-crown me-1"></i>Administración
                        </a>
                        <ul class="dropdown-menu dropdown-custom">
                            <li><a class="dropdown-item" href="admin/usuarios.php"><i class="fas fa-users me-2"></i>Gestión de Usuarios</a></li>
                            <li><a class="dropdown-item" href="admin/configuracion.php"><i class="fas fa-cogs me-2"></i>Configuración Sistema</a></li>
                            <li><a class="dropdown-item" href="admin/backup.php"><i class="fas fa-database me-2"></i>Backup</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <?php if ($rol_usuario == 'administrador' || $rol_usuario == 'agricultor'): ?>
                    <!-- Menú de Cultivos - Para Administradores y Agricultores -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCultivos" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-leaf me-1"></i>Cultivos
                        </a>
                        <ul class="dropdown-menu dropdown-custom">
                            <li><a class="dropdown-item" href="siembras/nueva.php"><i class="fas fa-plus-circle me-2"></i>Nueva Siembra</a></li>
                            <li><a class="dropdown-item" href="siembras/index.php"><i class="fas fa-list me-2"></i>Mis Siembras</a></li>
                            <li><a class="dropdown-item" href="cosechas/index.php"><i class="fas fa-apple-alt me-2"></i>Cosechas</a></li>
                        </ul>
                    </li>
                    
                    <!-- Menú de Fincas - Para Administradores y Agricultores -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFincas" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-map-marked-alt me-1"></i>Fincas
                        </a>
                        <ul class="dropdown-menu dropdown-custom">
                            <li><a class="dropdown-item" href="fincas/nueva.php"><i class="fas fa-plus me-2"></i>Nueva Finca</a></li>
                            <li><a class="dropdown-item" href="fincas/index.php"><i class="fas fa-list-ul me-2"></i>Mis Fincas</a></li>
                            <li><a class="dropdown-item" href="lotes/index.php"><i class="fas fa-th-large me-2"></i>Mis Lotes</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <!-- Menú de Monitoreo - Para todos los roles -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMonitoreo" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-eye me-1"></i>Monitoreo
                    </a>
                    <ul class="dropdown-menu dropdown-custom">
                        <?php if ($rol_usuario == 'administrador' || $rol_usuario == 'agricultor'): ?>
                            <li><a class="dropdown-item" href="monitoreo/nuevo.php"><i class="fas fa-clipboard-check me-2"></i>Nuevo Monitoreo</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="monitoreo/index.php"><i class="fas fa-history me-2"></i>Historial</a></li>
                        <li><a class="dropdown-item" href="alertas/index.php"><i class="fas fa-exclamation-triangle me-2"></i>Alertas</a></li>
                        <?php if ($rol_usuario == 'supervisor'): ?>
                            <li><a class="dropdown-item" href="supervisor/monitoreo.php"><i class="fas fa-binoculars me-2"></i>Supervisión</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <?php if ($rol_usuario == 'supervisor'): ?>
                    <!-- Menú específico para Supervisores -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownSupervisor" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-binoculars me-1"></i>Supervisión
                        </a>
                        <ul class="dropdown-menu dropdown-custom">
                            <li><a class="dropdown-item" href="supervisor/agricultores.php"><i class="fas fa-users me-2"></i>Agricultores</a></li>
                            <li><a class="dropdown-item" href="supervisor/fincas.php"><i class="fas fa-map-marked-alt me-2"></i>Fincas Supervisadas</a></li>
                            <li><a class="dropdown-item" href="supervisor/reportes.php"><i class="fas fa-clipboard-list me-2"></i>Reportes</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <!-- Menú de Reportes - Para todos los roles -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownReportes" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt me-1"></i>Reportes
                    </a>
                    <ul class="dropdown-menu dropdown-custom">
                        <?php if ($rol_usuario == 'administrador'): ?>
                            <li><a class="dropdown-item" href="admin/reportes.php"><i class="fas fa-chart-bar me-2"></i>Reportes Globales</a></li>
                        <?php endif; ?>
                        <?php if ($rol_usuario == 'agricultor'): ?>
                            <li><a class="dropdown-item" href="reportes/mis_cultivos.php"><i class="fas fa-seedling me-2"></i>Mis Cultivos</a></li>
                            <li><a class="dropdown-item" href="reportes/produccion.php"><i class="fas fa-chart-line me-2"></i>Producción</a></li>
                            <li><a class="dropdown-item" href="gastos/index.php"><i class="fas fa-money-bill-wave me-2"></i>Gastos</a></li>
                        <?php endif; ?>
                        <?php if ($rol_usuario == 'supervisor'): ?>
                            <li><a class="dropdown-item" href="supervisor/reportes.php"><i class="fas fa-clipboard-list me-2"></i>Reportes Supervisión</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>

            <!-- Menú de usuario logueado -->
            <ul class="navbar-nav">
                <!-- Notificaciones -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="navbarNotifications" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell notification-icon"></i>
                        <span class="notification-badge" id="notification-count">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <li class="dropdown-header">
                            <i class="fas fa-bell me-2"></i>Notificaciones
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="notification-item">
                            <a class="dropdown-item" href="#">
                                <div class="notification-content">
                                    <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Alerta</small>
                                    <p class="mb-1">Cultivo de tomate requiere riego</p>
                                    <small class="text-muted">Hace 2 horas</small>
                                </div>
                            </a>
                        </li>
                        <li class="notification-item">
                            <a class="dropdown-item" href="#">
                                <div class="notification-content">
                                    <small class="text-success"><i class="fas fa-check-circle me-1"></i>Éxito</small>
                                    <p class="mb-1">Monitoreo completado exitosamente</p>
                                    <small class="text-muted">Hace 4 horas</small>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">Ver todas las notificaciones</a></li>
                    </ul>
                </li>

                <!-- Perfil de usuario -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarUserDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="../PUBLIC/Img/user.png" alt="Usuario" class="user-avatar me-2" width="32" height="32">
                        <div class="user-info d-none d-md-block">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                            <small class="user-role d-block text-muted"><?php echo obtenerTextoRolSimple($rol_usuario); ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <img src="../PUBLIC/Img/user.png" alt="Usuario" class="user-avatar-large me-3" width="48" height="48">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($email_usuario); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="nav-perfil">
                            <i class="fas fa-user me-2"></i>Mi Perfil
                        </a></li>
                        <li><a class="dropdown-item" href="#" id="nav-configuracion">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a></li>
                        <li><a class="dropdown-item" href="#" id="nav-ayuda">
                            <i class="fas fa-question-circle me-2"></i>Ayuda
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php" id="nav-logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </li>
            </ul>

            <?php else: ?>
            <!-- Menú para usuarios no logueados -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="inicio.php">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#características">
                        <i class="fas fa-star me-1"></i>Características
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#precios">
                        <i class="fas fa-dollar-sign me-1"></i>Precios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contacto">
                        <i class="fas fa-envelope me-1"></i>Contacto
                    </a>
                </li>
            </ul>

            <!-- Botones de autenticación -->
            <ul class="navbar-nav">
                <li class="nav-item me-2">
                    <a class="nav-link btn-login-nav" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn-register-nav" href="registro.php">
                        <i class="fas fa-user-plus me-1"></i>Registrarse
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Agregar variables JavaScript globales para el manejo de sesión -->
<script>
    window.usuarioLogueado = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
    <?php if ($usuario_logueado): ?>
    window.userId = <?php echo $_SESSION['user_id']; ?>;
    window.userName = '<?php echo addslashes($nombre_usuario); ?>';
    window.userEmail = '<?php echo addslashes($email_usuario); ?>';
    window.userRole = '<?php echo addslashes($rol_usuario); ?>';
    <?php endif; ?>
</script>