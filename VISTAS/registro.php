<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/registro.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Part�culas decorativas de fondo -->
    <div class="particles-container">
        <div class="particle particle-1"></div>
        <div class="particle particle-2"></div>
        <div class="particle particle-3"></div>
        <div class="particle particle-4"></div>
        <div class="particle particle-5"></div>
        <div class="particle particle-6"></div>
        <div class="particle particle-7"></div>
        <div class="particle particle-8"></div>
    </div>

    <!-- Contenedor principal -->
    <div class="register-container">
        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center justify-content-center">
                <!-- Panel izquierdo - Informaci�n -->
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="info-panel">
                        <div class="brand-section">
                            <div class="brand-logo">
                                <i class="fas fa-seedling brand-icon"></i>
                                <h1 class="brand-title">AgroMonitor</h1>
                            </div>
                            <p class="brand-subtitle">Únete a la revolución agrícola digital</p>
                        </div>
                        
                        <div class="benefits-section">
                            <h3 class="benefits-title">
                                <i class="fas fa-gift me-2"></i>
                                Beneficios de unirte
                            </h3>
                            <div class="benefit-list">
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <div class="benefit-content">
                                        <h4>Aumenta tu productividad</h4>
                                        <p>Hasta 40% más de rendimiento en tus cultivos con nuestro sistema</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="benefit-content">
                                        <h4>Protege tus inversiones</h4>
                                        <p>Detecta problemas antes de que afecten tu producción</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-brain"></i>
                                    </div>
                                    <div class="benefit-content">
                                        <h4>Decisiones inteligentes</h4>
                                        <p>Análisis y reportes que te ayudan a tomar mejores decisiones</p>
                                    </div>
                                </div>
                                <div class="benefit-item">
                                    <div class="benefit-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="benefit-content">
                                        <h4>Comunidad activa</h4>
                                        <p>Conecta con otros agricultores y comparte experiencias</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="testimonial-section">
                            <div class="testimonial-card">
                                <div class="testimonial-content">
                                    <p>"AgroMonitor cambió completamente la manera en que manejo mis cultivos. Ahora tengo control total y mis cosechas son mejores que nunca."</p>
                                </div>
                                <div class="testimonial-author">
                                    <div class="author-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="author-info">
                                        <strong>Carlos Mendoza</strong>
                                        <span>Agricultor - Mendoza, Ecuador</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel derecho - Formulario de registro -->
                <div class="col-lg-7 col-md-10 col-sm-12">
                    <div class="register-panel">
                        <div class="register-header">
                            <div class="register-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h2 class="register-title">Crear cuenta nueva</h2>
                            <p class="register-subtitle">Completa tus datos para comenzar a monitorear tus cultivos</p>
                        </div>
                        
                        <!-- Indicador de progreso -->
                        <div class="progress-indicator">
                            <div class="step-indicator">
                                <div class="step active" data-step="1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Datos Personales</div>
                                </div>
                                <div class="step" data-step="2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Cuenta</div>
                                </div>
                                <div class="step" data-step="3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Verificación</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alertas -->
                        <div id="alert-container" class="alert-container"></div>
                        
                        <!-- Formulario de registro -->
                        <form id="registerForm" class="register-form" novalidate>
                            <!-- Paso 1: Datos Personales -->
                            <div class="form-step active" id="step-1">
                                <div class="step-title">
                                    <i class="fas fa-user me-2"></i>
                                    Información Personal
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre" class="form-label">
                                                <i class="fas fa-user me-2"></i>Nombre *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="nombre" 
                                                       name="nombre"
                                                       placeholder="Tu nombre"
                                                       required>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="apellido" class="form-label">
                                                <i class="fas fa-user me-2"></i>Apellido *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-user"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="apellido" 
                                                       name="apellido"
                                                       placeholder="Tu apellido"
                                                       required>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Teléfono
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="telefono" 
                                               name="telefono"
                                               placeholder="+54 9 11 1234-5678">
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="rol" class="form-label">
                                        <i class="fas fa-briefcase me-2"></i>¿Cuál es tu rol? *
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-briefcase"></i>
                                        </span>
                                        <select class="form-control" id="rol" name="rol" required>
                                            <option value="">Selecciona tu rol</option>
                                            <option value="agricultor">Agricultor</option>
                                            <option value="supervisor">Supervisor Agrícola</option>
                                            <option value="administrador">Administrador</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-next" id="next-step-1">
                                        Siguiente
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Paso 2: Informaci�n de Cuenta -->
                            <div class="form-step" id="step-2">
                                <div class="step-title">
                                    <i class="fas fa-envelope me-2"></i>
                                    Información de Cuenta
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Correo Electrónico *
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
                                        <button type="button" class="btn btn-outline-secondary verify-email" id="verify-email-btn">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Te enviaremos un c�digo de verificaci�n a este email
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password" class="form-label">
                                                <i class="fas fa-lock me-2"></i>Contraseña *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="password" 
                                                       name="password"
                                                       placeholder="Mínimo 8 caracteres"
                                                       required>
                                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="confirm-password" class="form-label">
                                                <i class="fas fa-lock me-2"></i>Confirmar Contraseña *
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="confirm-password" 
                                                       name="confirm-password"
                                                       placeholder="Repetir contraseña"
                                                       required>
                                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm-password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Indicador de fortaleza de contrase�a -->
                                <div class="password-strength">
                                    <div class="strength-indicator">
                                        <div class="strength-bar"></div>
                                    </div>
                                    <div class="strength-text">Fortaleza de la contraseña</div>
                                    <div class="strength-requirements">
                                        <div class="requirement" data-requirement="length">
                                            <i class="fas fa-times"></i> Al menos 8 caracteres
                                        </div>
                                        <div class="requirement" data-requirement="uppercase">
                                            <i class="fas fa-times"></i> Una letra mayúscula
                                        </div>
                                        <div class="requirement" data-requirement="lowercase">
                                            <i class="fas fa-times"></i> Una letra minúscula
                                        </div>
                                        <div class="requirement" data-requirement="number">
                                            <i class="fas fa-times"></i> Un número
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-prev" id="prev-step-2">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Anterior
                                    </button>
                                    <button type="button" class="btn btn-next" id="next-step-2">
                                        Siguiente
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Paso 3: Verificaci�n y T�rminos -->
                            <div class="form-step" id="step-3">
                                <div class="step-title">
                                    <i class="fas fa-shield-check me-2"></i>
                                    Verificaci�n y T�rminos
                                </div>
                                
                                <div class="verification-section">
                                    <div class="verification-email" id="verification-email-section" style="display: none;">
                                        <h5><i class="fas fa-envelope-open me-2"></i>Verificaci�n de Email</h5>
                                        <p>Hemos enviado un c�digo de verificaci�n a tu email. Ingr�salo a continuaci�n:</p>
                                        
                                        <div class="form-group">
                                            <label for="verification-code" class="form-label">Código de Verificación</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-key"></i>
                                                </span>
                                                <input type="text" 
                                                       class="form-control text-center" 
                                                       id="verification-code" 
                                                       name="verification-code"
                                                       placeholder="123456"
                                                       maxlength="6">
                                                <button type="button" class="btn btn-outline-secondary" id="resend-code">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="terms-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            Acepto los <a href="#" class="terms-link">Términos y Condiciones</a> 
                                            y la <a href="#" class="terms-link">Política de Privacidad</a>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                        <label class="form-check-label" for="newsletter">
                                            Quiero recibir noticias y actualizaciones sobre AgroMonitor
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="step-actions">
                                    <button type="button" class="btn btn-prev" id="prev-step-3">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Anterior
                                    </button>
                                    <button type="submit" class="btn btn-register" id="register-btn">
                                        <span class="btn-text">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Crear Cuenta
                                        </span>
                                        <span class="btn-loading" style="display: none;">
                                            <i class="fas fa-spinner fa-spin me-2"></i>
                                            Creando cuenta...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Enlaces adicionales -->
                        <div class="register-footer">
                            <div class="divider">
                                <span class="divider-text">¿Ya tienes cuenta?</span>
                            </div>
                            <a href="login.php" class="btn btn-login-link">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
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
            <p class="loading-text">Procesando registro...</p>
        </div>
    </div>
    
    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script src="partials/JS/footer.js"></script>
    <script src="JS/registro.js"></script>
</body>
</html>