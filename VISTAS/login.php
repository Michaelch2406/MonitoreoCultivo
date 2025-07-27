<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/login.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Partículas decorativas de fondo -->
    <div class="particles-container">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
        <div class="particle particle-4"></div>
        <div class="particle particle-5"></div>
        <div class="particle particle-6"></div>
    </div>

    <!-- Contenedor principal -->
    <div class="login-container">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center justify-content-center">
                <!-- Panel izquierdo - Información -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="info-panel">
                        <div class="brand-section">
                            <div class="brand-logo">
                                <i class="fas fa-seedling brand-icon"></i>
                                <h1 class="brand-title">AgroMonitor</h1>
                            </div>
                            <p class="brand-subtitle">Sistema Integral de Monitoreo de Cultivos</p>
                        </div>
                        
                        <div class="features-section">
                            <h3 class="features-title">
                                <i class="fas fa-star me-2"></i>
                                Características Principales
                            </h3>
                            <div class="feature-list">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Monitoreo en Tiempo Real</h4>
                                        <p>Seguimiento continuo del estado de tus cultivos con alertas automáticas</p>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-cloud-rain"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Gestión de Riego</h4>
                                        <p>Control inteligente del riego basado en datos de humedad y clima</p>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Reportes Detallados</h4>
                                        <p>Análisis completo del rendimiento y productividad de tus cultivos</p>
                                    </div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Acceso Móvil</h4>
                                        <p>Gestiona tus cultivos desde cualquier dispositivo, en cualquier momento</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Panel derecho - Formulario de login -->
                <div class="col-lg-6 col-md-8 col-sm-10">
                    <div class="login-panel">
                        <div class="login-header">
                            <div class="login-icon">
                                <i class="fas fa-leaf"></i>
                            </div>
                            <h2 class="login-title">Bienvenido de vuelta</h2>
                            <p class="login-subtitle">Inicia sesión para acceder a tu panel de cultivos</p>
                        </div>
                        
                        <!-- Alertas -->
                        <div id="alert-container" class="alert-container"></div>
                        
                        <!-- Formulario de login -->
                        <form id="loginForm" class="login-form" novalidate>
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Correo Electrónico
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email"
                                           placeholder="tu.email@ejemplo.com"
                                           required>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password"
                                           placeholder="Tu contraseña"
                                           required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <div class="form-options">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Recordar mi sesión
                                    </label>
                                </div>
                                <span class="text-muted small">¿Olvidaste tu contraseña? Contacta al administrador</span>
                            </div>
                            
                            <button type="submit" class="btn btn-login" id="loginButton">
                                <span class="btn-text">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Iniciar Sesión
                                </span>
                                <span class="btn-loading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                    Iniciando sesión...
                                </span>
                            </button>
                        </form>
                        
                        <!-- Enlaces adicionales -->
                        <div class="login-footer">
                            <div class="divider">
                                <span class="divider-text">¿No tienes cuenta?</span>
                            </div>
                            <a href="registro.php" class="btn btn-register">
                                <i class="fas fa-user-plus me-2"></i>
                                Registrarse
                            </a>
                        </div>
                        
                        <!-- Enlaces de ayuda -->
                        <div class="help-links">
                            <a href="#" class="help-link">
                                <i class="fas fa-question-circle me-1"></i>
                                Centro de Ayuda
                            </a>
                            <a href="#" class="help-link">
                                <i class="fas fa-phone me-1"></i>
                                Contactar Soporte
                            </a>
                            <a href="#" class="help-link">
                                <i class="fas fa-shield-alt me-1"></i>
                                Política de Privacidad
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="loading-spinner">
                <i class="fas fa-seedling spinning-icon"></i>
            </div>
            <p class="loading-text">Verificando credenciales...</p>
        </div>
    </div>
    
    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="JS/login.js"></script>
</body>
</html>