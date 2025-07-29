<?php
session_start();
require_once('../CONFIG/roles.php');

// No requiere autenticación - página pública
$usuario_logueado = estaLogueado();
$usuario_actual = $usuario_logueado ? obtenerUsuarioActual() : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/contacto.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <?php include('partials/navbar.php'); ?>
    
    <div class="container-fluid main-container"
         style="margin-left: var(--sidebar-width, 0); 
                transition: margin-left 0.3s ease; 
                padding-top: calc(var(--navbar-height, 70px) + 1rem);">
        
        <!-- Ajuste dinámico para cuando el sidebar esté colapsado -->
        <style>
            @media (max-width: 768px) {
                .main-container {
                    margin-left: 0 !important;
                }
            }
        </style>
        <!-- Header -->
        <div class="contacto-header" data-aos="fade-down">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12 mb-3 mb-lg-0">
                        <h2 class="page-title">
                            <i class="fas fa-envelope me-2"></i>
                            <span class="d-block d-sm-inline">Contáctanos</span>
                        </h2>
                        <p class="page-subtitle">
                            Estamos aquí para ayudarte con cualquier pregunta sobre AgroMonitor
                        </p>
                    </div>
                    <div class="col-lg-4 col-md-12 text-lg-end">
                        <div class="contact-quick-info">
                            <div class="quick-info-item">
                                <i class="fas fa-phone text-success"></i>
                                <span>(+593) 123-4567</span>
                            </div>
                            <div class="quick-info-item">
                                <i class="fas fa-clock text-primary"></i>
                                <span>Lun - Vie: 8:00 AM - 6:00 PM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información de Contacto Rápida -->
        <div class="container mb-4">
            <div class="row" data-aos="fade-up">
                <div class="col-md-4 mb-3">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-content">
                            <h5>Nuestra Oficina</h5>
                            <p>Av. Agricultura 123<br>
                            Ciudad Agrícola, CA 12345<br>
                            Ecuador</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-content">
                            <h5>Teléfonos</h5>
                            <p><strong>Principal:</strong> (+593) 989972254<br>
                            <strong>Soporte:</strong> (+593) 987-6543<br>
                            <strong>WhatsApp:</strong> (+593) 456-7890</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-content">
                            <h5>Correos Electrónicos</h5>
                            <p><strong>General:</strong> info@agromonitor.com<br>
                            <strong>Soporte:</strong> soporte@agromonitor.com<br>
                            <strong>Ventas:</strong> ventas@agromonitor.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de Contacto y Mapa -->
        <div class="container">
            <div class="row">
                <!-- Formulario de Contacto -->
                <div class="col-lg-8 mb-4">
                    <div class="card" data-aos="fade-up">
                        <div class="card-header">
                            <h5><i class="fas fa-paper-plane me-2"></i>Envíanos un Mensaje</h5>
                        </div>
                        <div class="card-body">
                            <form id="formContacto">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombreCompleto" class="form-label">Nombre Completo *</label>
                                        <input type="text" class="form-control" id="nombreCompleto" name="nombre_completo" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Correo Electrónico *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="empresa" class="form-label">Empresa/Organización</label>
                                        <input type="text" class="form-control" id="empresa" name="empresa">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tipoConsulta" class="form-label">Tipo de Consulta *</label>
                                        <select class="form-select" id="tipoConsulta" name="tipo_consulta" required>
                                            <option value="">Seleccionar tipo</option>
                                            <option value="informacion_general">Información General</option>
                                            <option value="soporte_tecnico">Soporte Técnico</option>
                                            <option value="ventas">Ventas y Precios</option>
                                            <option value="demo">Solicitar Demo</option>
                                            <option value="partnership">Alianzas Estratégicas</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="prioridad" class="form-label">Prioridad</label>
                                        <select class="form-select" id="prioridad" name="prioridad">
                                            <option value="baja">Baja</option>
                                            <option value="media" selected>Media</option>
                                            <option value="alta">Alta</option>
                                            <option value="urgente">Urgente</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="asunto" class="form-label">Asunto *</label>
                                    <input type="text" class="form-control" id="asunto" name="asunto" required>
                                </div>
                                <div class="mb-3">
                                    <label for="mensaje" class="form-label">Mensaje *</label>
                                    <textarea class="form-control" id="mensaje" name="mensaje" rows="5" required placeholder="Escribe tu mensaje aquí..."></textarea>
                                    <div class="form-text">
                                        <span id="contadorCaracteres">0</span>/1000 caracteres
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="aceptarTerminos" name="aceptar_terminos" required>
                                        <label class="form-check-label" for="aceptarTerminos">
                                            Acepto los <a href="#" class="text-success">términos y condiciones</a> y la <a href="#" class="text-success">política de privacidad</a> *
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="recibirNoticias" name="recibir_noticias">
                                        <label class="form-check-label" for="recibirNoticias">
                                            Deseo recibir noticias y actualizaciones sobre AgroMonitor
                                        </label>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-eraser me-2"></i>Limpiar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Mensaje
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="col-lg-4 mb-4">
                    <!-- Horarios de Atención -->
                    <div class="card mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card-header">
                            <h5><i class="fas fa-clock me-2"></i>Horarios de Atención</h5>
                        </div>
                        <div class="card-body">
                            <div class="horario-item">
                                <div class="dia">Lunes - Viernes</div>
                                <div class="hora">8:00 AM - 6:00 PM</div>
                            </div>
                            <div class="horario-item">
                                <div class="dia">Sábados</div>
                                <div class="hora">9:00 AM - 1:00 PM</div>
                            </div>
                            <div class="horario-item">
                                <div class="dia">Domingos</div>
                                <div class="hora">Cerrado</div>
                            </div>
                            <div class="horario-item soporte">
                                <div class="dia">Soporte 24/7</div>
                                <div class="hora">
                                    <span class="badge bg-success">Disponible</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Redes Sociales -->
                    <div class="card mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-header">
                            <h5><i class="fas fa-share-alt me-2"></i>Síguenos</h5>
                        </div>
                        <div class="card-body">
                            <div class="social-links-contact">
                                <a href="#" class="social-link-contact facebook">
                                    <i class="fab fa-facebook-f"></i>
                                    <span>Facebook</span>
                                </a>
                                <a href="#" class="social-link-contact twitter">
                                    <i class="fab fa-twitter"></i>
                                    <span>Twitter</span>
                                </a>
                                <a href="#" class="social-link-contact linkedin">
                                    <i class="fab fa-linkedin-in"></i>
                                    <span>LinkedIn</span>
                                </a>
                                <a href="#" class="social-link-contact instagram">
                                    <i class="fab fa-instagram"></i>
                                    <span>Instagram</span>
                                </a>
                                <a href="#" class="social-link-contact youtube">
                                    <i class="fab fa-youtube"></i>
                                    <span>YouTube</span>
                                </a>
                                <a href="#" class="social-link-contact whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Emergencia -->
                    <div class="card" data-aos="fade-up" data-aos-delay="300">
                        <div class="card-header">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Contacto de Emergencia</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Para problemas críticos o emergencias técnicas:</p>
                            <div class="emergency-contact">
                                <div class="emergency-item">
                                    <i class="fas fa-phone text-danger"></i>
                                    <div>
                                        <strong>Línea de Emergencia</strong>
                                        <div>+1 (+593) 911-AGRO</div>
                                    </div>
                                </div>
                                <div class="emergency-item">
                                    <i class="fas fa-envelope text-warning"></i>
                                    <div>
                                        <strong>Email Urgente</strong>
                                        <div>emergencia@agromonitor.com</div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <small><i class="fas fa-info-circle me-1"></i>
                                Disponible 24/7 para clientes premium</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mapa -->
        <div class="container-fluid px-0 mb-4">
            <div class="map-section" data-aos="fade-up">
                <div class="map-container">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <h4>Nuestra Ubicación</h4>
                        <p>Av. Agricultura 123, Ciudad Agrícola, Colombia</p>
                        <button class="btn btn-outline-light" id="btnVerMapa">
                            <i class="fas fa-directions me-2"></i>Ver en Google Maps
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Rápidas -->
        <div class="container mb-4">
            <div class="row">
                <div class="col-12">
                    <div class="card" data-aos="fade-up">
                        <div class="card-header">
                            <h5><i class="fas fa-question-circle me-2"></i>Preguntas Frecuentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="accordionFAQ">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                            ¿Cómo puedo solicitar una demo de AgroMonitor?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body">
                                            Puedes solicitar una demo gratuita seleccionando "Solicitar Demo" en el formulario de contacto o llamando directamente a nuestro equipo de ventas al (+593) 123-4567.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                            ¿Cuáles son los planes de precios disponibles?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body">
                                            Ofrecemos planes flexibles para diferentes tamaños de operación: Básico (hasta 10 hectáreas), Profesional (hasta 100 hectáreas) y Empresarial (sin límites). Contacta con ventas para información detallada.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                            ¿Ofrecen soporte técnico 24/7?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                                        <div class="accordion-body">
                                            Sí, ofrecemos soporte 24/7 para clientes con plan Profesional y Empresarial. Los clientes con plan Básico tienen soporte durante horario comercial.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('partials/footer.php'); ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <!-- Usuario actual para JavaScript -->
    <script>
        window.usuarioLogueado = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
        <?php if ($usuario_logueado): ?>
        window.usuarioActual = {
            id: <?php echo $usuario_actual['id']; ?>,
            rol: '<?php echo $usuario_actual['rol']; ?>',
            nombre: '<?php echo addslashes($usuario_actual['nombre']); ?>',
            email: '<?php echo addslashes($usuario_actual['email']); ?>'
        };
        <?php endif; ?>
        
        // Inicializar AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
    </script>
    
    <script src="JS/contacto.js"></script>
</body>
</html>