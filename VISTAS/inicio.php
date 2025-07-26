<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroMonitor - Sistema de Monitoreo de Cultivos</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/inicio.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Hero Section -->
    <section id="hero" class="hero-section">
        <div class="hero-background">
            <div class="hero-particles"></div>
        </div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            <span class="highlight">AgroMonitor</span>
                            <br>El futuro de la agricultura
                        </h1>
                        <p class="hero-subtitle">
                            Revoluciona la gestión de tus cultivos con tecnología avanzada. 
                            Monitorea, analiza y optimiza tu producción agrícola desde cualquier lugar.
                        </p>
                        <div class="hero-features">
                            <div class="feature-item">
                                <i class="fas fa-chart-line"></i>
                                <span>Análisis en tiempo real</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Acceso móvil</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-cloud"></i>
                                <span>Datos en la nube</span>
                            </div>
                        </div>
                        <div class="hero-buttons">
                            <a href="registro.php" class="btn btn-primary btn-lg btn-hero">
                                <i class="fas fa-rocket me-2"></i>
                                Comenzar Gratis
                            </a>
                            <a href="#caracteristicas" class="btn btn-outline-light btn-lg btn-hero-outline">
                                <i class="fas fa-play me-2"></i>
                                Ver Demo
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-image">
                        <div class="dashboard-preview">
                            <img src="../PUBLIC/Img/dashboard-preview.png" alt="Vista previa del dashboard" class="img-fluid">
                            <div class="preview-overlay">
                                <div class="preview-stats">
                                    <div class="stat-item">
                                        <div class="stat-number">95%</div>
                                        <div class="stat-label">Precisión</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">24/7</div>
                                        <div class="stat-label">Monitoreo</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number">+500</div>
                                        <div class="stat-label">Usuarios</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <div class="scroll-arrow">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </section>

    <!-- Características Section -->
    <section id="caracteristicas" class="features-section py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12" data-aos="fade-up">
                    <h2 class="section-title">¿Por qué elegir AgroMonitor?</h2>
                    <p class="section-subtitle">
                        Descubre las características que hacen de AgroMonitor la mejor opción para tu agricultura
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h3 class="feature-title">Gestión Inteligente</h3>
                        <p class="feature-description">
                            Administra todos tus cultivos desde una sola plataforma. 
                            Registra siembras, controla el crecimiento y programa actividades.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Control de inventario</li>
                            <li><i class="fas fa-check"></i> Programación automática</li>
                            <li><i class="fas fa-check"></i> Alertas personalizadas</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3 class="feature-title">Análisis Avanzado</h3>
                        <p class="feature-description">
                            Obtén insights profundos sobre tu producción con gráficos 
                            interactivos y reportes detallados en tiempo real.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Dashboards interactivos</li>
                            <li><i class="fas fa-check"></i> Predicciones de cosecha</li>
                            <li><i class="fas fa-check"></i> Análisis de rentabilidad</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-cloud-sun"></i>
                        </div>
                        <h3 class="feature-title">Clima Inteligente</h3>
                        <p class="feature-description">
                            Integración con datos meteorológicos para optimizar 
                            el riego y proteger tus cultivos de condiciones adversas.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Pronóstico del clima</li>
                            <li><i class="fas fa-check"></i> Alertas meteorológicas</li>
                            <li><i class="fas fa-check"></i> Optimización de riego</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Acceso Móvil</h3>
                        <p class="feature-description">
                            Monitorea tus cultivos desde cualquier lugar con nuestra 
                            aplicación móvil optimizada para el campo.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> App móvil nativa</li>
                            <li><i class="fas fa-check"></i> Modo offline</li>
                            <li><i class="fas fa-check"></i> Sincronización automática</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Colaboración</h3>
                        <p class="feature-description">
                            Trabaja en equipo con múltiples usuarios, asigna tareas 
                            y mantén comunicación constante con tu equipo.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Múltiples usuarios</li>
                            <li><i class="fas fa-check"></i> Roles y permisos</li>
                            <li><i class="fas fa-check"></i> Chat integrado</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Seguridad Total</h3>
                        <p class="feature-description">
                            Tus datos están protegidos con encriptación de nivel bancario 
                            y respaldos automáticos en la nube.
                        </p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Encriptación SSL</li>
                            <li><i class="fas fa-check"></i> Respaldos automáticos</li>
                            <li><i class="fas fa-check"></i> Acceso seguro</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas Section -->
    <section class="stats-section py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number" data-target="1500">0</div>
                        <div class="stat-label">Agricultores activos</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="stat-number" data-target="5000">0</div>
                        <div class="stat-label">Hectáreas monitoreadas</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number" data-target="35">0</div>
                        <div class="stat-label">% Aumento en productividad</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-number" data-target="15">0</div>
                        <div class="stat-label">Países utilizando AgroMonitor</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center" data-aos="fade-up">
                    <div class="cta-content">
                        <h2 class="cta-title">¿Listo para revolucionar tu agricultura?</h2>
                        <p class="cta-subtitle">
                            Únete a miles de agricultores que ya están optimizando sus cosechas con AgroMonitor
                        </p>
                        <div class="cta-buttons">
                            <a href="registro.php" class="btn btn-primary btn-lg me-3">
                                <i class="fas fa-rocket me-2"></i>
                                Comenzar ahora
                            </a>
                            <a href="login.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar sesión
                            </a>
                        </div>
                        <div class="cta-note">
                            <small>
                                <i class="fas fa-lock me-1"></i>
                                Sin compromiso • Cancela cuando quieras • Soporte 24/7
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>

    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="JS/inicio.js"></script>
</body>
</html>