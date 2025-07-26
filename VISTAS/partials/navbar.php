<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$nombre_usuario = $usuario_logueado ? $_SESSION['user_name'] : '';
$rol_usuario = $usuario_logueado ? $_SESSION['user_role'] : '';
$email_usuario = $usuario_logueado ? $_SESSION['user_email'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <!-- Logo y Título -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo $usuario_logueado ? 'inicio.php' : 'login.php'; ?>" id="navbar-brand">
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
                    <a class="nav-link active" href="inicio.php" id="nav-dashboard">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCultivos" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-leaf me-1"></i>Cultivos
                    </a>
                    <ul class="dropdown-menu dropdown-custom">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-plus-circle me-2"></i>Nuevo Cultivo</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-list me-2"></i>Listar Cultivos</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-chart-line me-2"></i>Estadísticas</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMonitoreo" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-eye me-1"></i>Monitoreo
                    </a>
                    <ul class="dropdown-menu dropdown-custom">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-clipboard-check me-2"></i>Registrar Observación</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-history me-2"></i>Historial</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle me-2"></i>Alertas</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFincas" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-map-marked-alt me-1"></i>Fincas
                    </a>
                    <ul class="dropdown-menu dropdown-custom">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-plus me-2"></i>Nueva Finca</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-list-ul me-2"></i>Mis Fincas</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-map me-2"></i>Ubicaciones</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="nav-reportes">
                        <i class="fas fa-file-alt me-1"></i>Reportes
                    </a>
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
                            <small class="user-role d-block text-muted"><?php echo ucfirst($rol_usuario); ?></small>
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
                        <li><a class="dropdown-item text-danger" href="#" data-action="logout" id="nav-logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </li>
            </ul>

            <?php else: ?>
            <!-- Menú para usuarios no logueados -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#inicio">
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