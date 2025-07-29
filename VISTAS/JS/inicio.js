/**
 * Inicio Landing Page JS - Sistema AgroMonitor
 * JavaScript para la página de presentación del sistema
 */

// Variables globales
let animationsInitialized = false;
let statsAnimated = false;

// Configuración
const CONFIG = {
    animationDuration: 1500,
    counterSpeed: 2000,
    scrollOffset: 100
};

/**
 * =====================================================
 * INICIALIZACIÓN
 * =====================================================
 */

$(document).ready(function() {
    initializeLandingPage();
});

/**
 * Inicializar todas las funcionalidades de la landing page
 */
function initializeLandingPage() {
    console.log('🌱 Inicializando AgroMonitor Landing Page...');
    
    // Inicializar animaciones
    initializeAnimations();
    
    // Configurar event listeners
    setupEventListeners();
    
    // Configurar scroll effects
    setupScrollEffects();
    
    // Inicializar partículas si están disponibles
    initializeParticles();
    
    // Configurar smooth scrolling
    setupSmoothScrolling();
    
    console.log('✅ Landing Page inicializada correctamente');
}

/**
 * =====================================================
 * ANIMACIONES
 * =====================================================
 */

/**
 * Inicializar animaciones AOS
 */
function initializeAnimations() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 120,
            delay: 100
        });
        animationsInitialized = true;
        console.log('🎨 Animaciones AOS inicializadas');
    } else {
        console.warn('⚠️ AOS no está disponible');
        // Fallback animations
        setupFallbackAnimations();
    }
}

/**
 * Configurar animaciones de respaldo si AOS no está disponible
 */
function setupFallbackAnimations() {
    // Animación simple para elementos visibles
    $('.feature-card, .stat-item').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(30px)',
            'transition': 'all 0.6s ease-out'
        });
        
        setTimeout(() => {
            $(this).css({
                'opacity': '1',
                'transform': 'translateY(0)'
            });
        }, index * 100);
    });
}

/**
 * Animar contadores de estadísticas
 */
function animateStatsCounters() {
    if (statsAnimated) return;
    
    $('.stat-number').each(function() {
        const $counter = $(this);
        const target = parseInt($counter.data('target'));
        
        if (!target) return;
        
        let current = 0;
        const increment = target / (CONFIG.counterSpeed / 16);
        
        const timer = setInterval(() => {
            current += increment;
            
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            $counter.text(Math.floor(current));
        }, 16);
        
        $counter.addClass('animate');
    });
    
    statsAnimated = true;
    console.log('📊 Contadores de estadísticas animados');
}

/**
 * =====================================================
 * EVENT LISTENERS
 * =====================================================
 */

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Scroll indicator
    $('.scroll-indicator').on('click', function() {
        scrollToSection('#caracteristicas');
    });
    
    // Botones CTA
    $('.btn-hero, .cta-buttons .btn').on('click', function(e) {
        const href = $(this).attr('href');
        
        // Si es un enlace interno, hacer scroll suave
        if (href && href.startsWith('#')) {
            e.preventDefault();
            scrollToSection(href);
        } else if (href && (href !== '#' && href !== 'javascript:void(0)')) {
            // Para enlaces externos/páginas, permitir navegación normal
            // Solo agregar efecto visual sin interferir
            addButtonClickEffect($(this));
            return true;
        }
        
        // Efecto visual en el botón solo para enlaces internos
        if (href && href.startsWith('#')) {
            addButtonClickEffect($(this));
        }
    });
    
    // Hover effects para las cards
    $('.feature-card').on('mouseenter', function() {
        $(this).find('.feature-icon').css('transform', 'scale(1.1) rotate(5deg)');
    }).on('mouseleave', function() {
        $(this).find('.feature-icon').css('transform', 'scale(1) rotate(0deg)');
    });
    
    // Click effects para cards
    $('.feature-card').on('click', function() {
        const title = $(this).find('.feature-title').text();
        showFeatureModal(title, $(this));
    });
    
    // Navbar scroll effect
    $(window).on('scroll', handleNavbarScroll);
    
    // Parallax effect para hero
    if (!isMobileDevice()) {
        $(window).on('scroll', handleParallaxEffect);
    }
    
    // Resize handler
    $(window).on('resize', debounce(handleWindowResize, 250));
    
    console.log('🎯 Event listeners configurados');
}

/**
 * =====================================================
 * SCROLL EFFECTS
 * =====================================================
 */

/**
 * Configurar efectos de scroll
 */
function setupScrollEffects() {
    // Intersection Observer para animaciones
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    
                    // Animar estadísticas cuando entren en viewport
                    if (target.classList.contains('stats-section')) {
                        setTimeout(animateStatsCounters, 300);
                    }
                    
                    // Agregar clase para animaciones CSS
                    target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // Observar secciones
        document.querySelectorAll('.features-section, .stats-section, .cta-section').forEach(section => {
            observer.observe(section);
        });
        
        console.log('👁️ Intersection Observer configurado');
    }
}

/**
 * Manejar scroll del navbar
 */
function handleNavbarScroll() {
    const scrollTop = $(window).scrollTop();
    const $navbar = $('.navbar');
    
    if (scrollTop > 100) {
        $navbar.addClass('navbar-scrolled');
    } else {
        $navbar.removeClass('navbar-scrolled');
    }
}

/**
 * Efecto parallax para hero
 */
function handleParallaxEffect() {
    const scrollTop = $(window).scrollTop();
    const parallaxSpeed = 0.5;
    
    $('.hero-particles').css('transform', `translateY(${scrollTop * parallaxSpeed}px)`);
    $('.hero-background').css('transform', `translateY(${scrollTop * 0.3}px)`);
}

/**
 * =====================================================
 * SMOOTH SCROLLING
 * =====================================================
 */

/**
 * Configurar smooth scrolling
 */
function setupSmoothScrolling() {
    // Smooth scroll para enlaces internos
    $('a[href^="#"]').not('.dropdown-toggle').on('click', function(e) {
        const href = $(this).attr('href');
        
        if (href !== '#') {
            e.preventDefault();
            scrollToSection(href);
        }
    });
}

/**
 * Scroll suave a una sección
 */
function scrollToSection(target) {
    const $target = $(target);
    
    if ($target.length) {
        const offsetTop = $target.offset().top - 80; // Compensar navbar
        
        $('html, body').animate({
            scrollTop: offsetTop
        }, 800, 'easeInOutCubic');
        
        // Agregar efecto visual al target
        $target.addClass('section-highlight');
        setTimeout(() => {
            $target.removeClass('section-highlight');
        }, 2000);
    }
}

/**
 * =====================================================
 * EFECTOS INTERACTIVOS
 * =====================================================
 */

/**
 * Agregar efecto de click a botones
 */
function addButtonClickEffect($button) {
    $button.addClass('btn-clicked');
    
    setTimeout(() => {
        $button.removeClass('btn-clicked');
    }, 200);
}

/**
 * Mostrar modal de característica (simulado)
 */
function showFeatureModal(title, $card) {
    const description = $card.find('.feature-description').text();
    
    // Crear modal dinámico
    const modalHtml = `
        <div class="modal fade" id="featureModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-star me-2"></i>${title}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="lead">${description}</p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Beneficios principales:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Facilidad de uso</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Resultados inmediatos</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Soporte 24/7</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>¿Cómo funciona?</h6>
                                <p class="text-muted">
                                    Nuestro sistema utiliza tecnología avanzada para brindarte 
                                    la mejor experiencia en el monitoreo de tus cultivos.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <a href="registro.php" class="btn btn-primary">Comenzar ahora</a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente
    $('#featureModal').remove();
    
    // Agregar nuevo modal
    $('body').append(modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('featureModal'));
    modal.show();
    
    console.log(`📋 Modal abierto para: ${title}`);
}

/**
 * =====================================================
 * PARTÍCULAS Y EFECTOS VISUALES
 * =====================================================
 */

/**
 * Inicializar partículas
 */
function initializeParticles() {
    // Crear partículas dinámicas en el hero
    createFloatingParticles();
    
    console.log('✨ Partículas inicializadas');
}

/**
 * Crear partículas flotantes
 */
function createFloatingParticles() {
    const $heroSection = $('.hero-section');
    const particleCount = isMobileDevice() ? 10 : 20;
    
    for (let i = 0; i < particleCount; i++) {
        const $particle = $('<div class="floating-particle"></div>');
        
        // Posición aleatoria
        const left = Math.random() * 100;
        const animationDuration = 10 + Math.random() * 20;
        const size = 2 + Math.random() * 4;
        const delay = Math.random() * 20;
        
        $particle.css({
            position: 'absolute',
            left: left + '%',
            bottom: '-10px',
            width: size + 'px',
            height: size + 'px',
            background: 'rgba(255, 255, 255, 0.3)',
            borderRadius: '50%',
            animation: `floatUp ${animationDuration}s linear infinite ${delay}s`,
            pointerEvents: 'none',
            zIndex: '1'
        });
        
        $heroSection.append($particle);
    }
}

/**
 * =====================================================
 * UTILIDADES
 * =====================================================
 */

/**
 * Detectar dispositivo móvil
 */
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Función debounce para optimizar performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Manejar redimensionamiento de ventana
 */
function handleWindowResize() {
    // Reinicializar animaciones si es necesario
    if (animationsInitialized && typeof AOS !== 'undefined') {
        AOS.refresh();
    }
    
    // Recalcular posiciones de partículas
    $('.floating-particle').remove();
    if (!isMobileDevice()) {
        createFloatingParticles();
    }
}

/**
 * Mostrar/ocultar loading
 */
function toggleLoading(show = true) {
    if (show) {
        $('body').addClass('loading');
    } else {
        $('body').removeClass('loading');
    }
}

/**
 * =====================================================
 * EASTER EGG Y EXTRAS
 * =====================================================
 */

/**
 * Easter egg para desarrolladores
 */
$(document).ready(function() {
    let konamiCode = [];
    const correctCode = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // ↑↑↓↓←→←→BA
    
    $(document).on('keydown', function(e) {
        konamiCode.push(e.keyCode);
        
        if (konamiCode.length > 10) {
            konamiCode.shift();
        }
        
        if (konamiCode.join(',') === correctCode.join(',')) {
            activateEasterEgg();
            konamiCode = [];
        }
    });
});

/**
 * Activar easter egg
 */
function activateEasterEgg() {
    console.log('🎉 Easter egg activado!');
    
    // Efecto especial
    $('body').addClass('easter-egg-active');
    
    // Mensaje especial
    const message = `
        🌱 ¡Felicidades! Has encontrado el Easter Egg de AgroMonitor 🌱
        
        Gracias por explorar nuestro código. 
        Los desarrolladores apreciamos la curiosidad técnica.
        
        ¿Te gustaría unirte a nuestro equipo?
        Visita: careers@agromonitor.com
    `;
    
    console.log(message);
    
    // Efecto visual
    createConfetti();
    
    setTimeout(() => {
        $('body').removeClass('easter-egg-active');
    }, 5000);
}

/**
 * Crear confetti
 */
function createConfetti() {
    for (let i = 0; i < 50; i++) {
        const confetti = $('<div class="confetti"></div>');
        
        confetti.css({
            position: 'fixed',
            left: Math.random() * 100 + '%',
            top: '-10px',
            width: '10px',
            height: '10px',
            background: `hsl(${Math.random() * 360}, 50%, 50%)`,
            animation: `confettiFall ${2 + Math.random() * 3}s ease-out forwards`,
            zIndex: '9999',
            borderRadius: '50%'
        });
        
        $('body').append(confetti);
        
        setTimeout(() => {
            confetti.remove();
        }, 5000);
    }
}

/**
 * =====================================================
 * INTEGRACIÓN CON SISTEMA GLOBAL
 * =====================================================
 */

// Exponer funciones útiles globalmente
window.AgroMonitorLanding = {
    scrollToSection: scrollToSection,
    animateCounters: animateStatsCounters,
    showFeatureModal: showFeatureModal,
    toggleLoading: toggleLoading
};

// CSS adicional para efectos
const landingStyles = `
    <style>
    .navbar-scrolled {
        background: rgba(46, 125, 50, 0.95) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    }
    
    .section-highlight {
        animation: sectionPulse 2s ease-out;
    }
    
    @keyframes sectionPulse {
        0% { background: transparent; }
        50% { background: rgba(46, 125, 50, 0.1); }
        100% { background: transparent; }
    }
    
    .btn-clicked {
        transform: scale(0.95) !important;
    }
    
    .animate-in {
        animation: slideInUp 0.8s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes floatUp {
        from {
            bottom: -10px;
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        to {
            bottom: 100vh;
            opacity: 0;
        }
    }
    
    @keyframes confettiFall {
        to {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
    
    .easter-egg-active {
        animation: rainbow 2s ease-in-out infinite;
    }
    
    @keyframes rainbow {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }
    
    /* Mejoras de accesibilidad */
    @media (prefers-reduced-motion: reduce) {
        .floating-particle,
        .confetti,
        .animate-in {
            animation: none !important;
        }
        
        .easter-egg-active {
            animation: none !important;
        }
    }
    
    /* Loading state */
    .loading * {
        pointer-events: none;
    }
    
    .loading .btn {
        opacity: 0.6;
    }
    </style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', landingStyles);

console.log('🚀 AgroMonitor Landing Page JS cargado exitosamente');