<!-- Footer - Sistema de Monitoreo de Cultivos -->
<footer class="footer-custom">
    <div class="container-fluid">
        <!-- Sección principal del footer -->
        <div class="row">
            <!-- Información de la empresa -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-section">
                    <div class="footer-brand mb-3">
                        <i class="fas fa-seedling brand-icon me-2"></i>
                        <span class="brand-text">AgroMonitor</span>
                    </div>
                    <p class="footer-description">
                        Sistema integral de monitoreo de cultivos diseñado para optimizar la productividad agrícola 
                        mediante el seguimiento detallado y análisis de datos en tiempo real.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Enlaces rápidos -->
            <div class="col-lg-2 col-md-6 mb-4">
                <div class="footer-section">
                    <h5 class="footer-title">
                        <i class="fas fa-link me-2"></i>Enlaces Rápidos
                    </h5>
                    <ul class="footer-links">
                        <li><a href="dashboard.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Dashboard
                        </a></li>
                        <li><a href="cultivos.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Mis Cultivos
                        </a></li>
                        <li><a href="monitoreo.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Monitoreo
                        </a></li>
                        <li><a href="reportes.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Reportes
                        </a></li>
                        <li><a href="perfil.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Mi Perfil
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Herramientas -->
            <div class="col-lg-2 col-md-6 mb-4">
                <div class="footer-section">
                    <h5 class="footer-title">
                        <i class="fas fa-tools me-2"></i>Herramientas
                    </h5>
                    <ul class="footer-links">
                        <li><a href="fincas.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Gestión de Fincas
                        </a></li>
                        <li><a href="siembras.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Mis Siembras
                        </a></li>
                        <li><a href="cosechas.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Cosechas
                        </a></li>
                        <li><a href="finanzas.php" class="footer-link">
                            <i class="fas fa-chevron-right me-2"></i>Control Financiero
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Contacto e información -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-section">
                    <h5 class="footer-title">
                        <i class="fas fa-envelope me-2"></i>Contacto
                    </h5>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope contact-icon"></i>
                            <div class="contact-text">
                                <strong>Soporte:</strong><br>
                                <a href="contacto.php" class="footer-link">Formulario de Contacto</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-question-circle contact-icon"></i>
                            <div class="contact-text">
                                <strong>Ayuda:</strong><br>
                                <span class="text-muted">Sistema en funcionamiento</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Separador -->
        <hr class="footer-divider">
        <!-- Barra inferior -->
        <div class="row">
            <div class="col-lg-6">
                <div class="footer-bottom-left">
                    <p class="copyright">
                        © <span id="current-year"><?php echo date('Y'); ?></span> AgroMonitor. 
                        <span class="separator">|</span>
                        Todos los derechos reservados.
                        <span class="separator">|</span>
                        <a href="#" class="footer-bottom-link">Términos de Uso</a>
                        <span class="separator">|</span>
                        <a href="#" class="footer-bottom-link">Política de Privacidad</a>
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="footer-bottom-right">
                    <div class="footer-info">
                        <span class="version-info">
                            <i class="fas fa-code-branch me-1"></i>
                            Versión 1.0.0
                        </span>
                        <span class="separator">|</span>
                        <span class="status-info">
                            <i class="fas fa-circle status-indicator online"></i>
                            Sistema Operativo
                        </span>
                        <button class="scroll-top-btn" id="scrollTopBtn" title="Volver arriba">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Elementos decorativos -->
    <div class="footer-decoration">
        <div class="decoration-item decoration-leaf-1">
            <i class="fas fa-leaf"></i>
        </div>
        <div class="decoration-item decoration-leaf-2">
            <i class="fas fa-seedling"></i>
        </div>
        <div class="decoration-item decoration-leaf-3">
            <i class="fas fa-tree"></i>
        </div>
    </div>
</footer>

<!-- Los scripts de Bootstrap se cargan en la página principal -->