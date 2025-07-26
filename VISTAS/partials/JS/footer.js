// JavaScript para Footer - Sistema de Monitoreo de Cultivos

document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const currentYearSpan = document.getElementById('current-year');
    const statNumbers = document.querySelectorAll('.stat-number');
    const footerLinks = document.querySelectorAll('.footer-link');
    const socialLinks = document.querySelectorAll('.social-link');
    const resourceLinks = document.querySelectorAll('.resource-link');
    const statusIndicator = document.querySelector('.status-indicator');
    
    // Configurar año actual
    function setCurrentYear() {
        if (currentYearSpan) {
            currentYearSpan.textContent = new Date().getFullYear();
        }
    }
    
    // Botón de scroll hacia arriba
    function initScrollTopButton() {
        if (!scrollTopBtn) return;
        
        // Mostrar/ocultar botón según scroll
        function toggleScrollButton() {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        }
        
        // Event listener para scroll
        window.addEventListener('scroll', throttle(toggleScrollButton, 100));
        
        // Click para volver arriba
        scrollTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Animación suave hacia arriba
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            // Efecto visual en el botón
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
        
        // Efecto hover mejorado
        scrollTopBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.1)';
        });
        
        scrollTopBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    }
    
    // Animación de contadores estadísticos
    function initStatCounters() {
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumber = entry.target;
                    const target = parseInt(statNumber.dataset.target);
                    animateCounter(statNumber, target);
                    observer.unobserve(statNumber);
                }
            });
        }, observerOptions);
        
        statNumbers.forEach(statNumber => {
            observer.observe(statNumber);
        });
    }
    
    // Función para animar contadores
    function animateCounter(element, target) {
        let current = 0;
        const increment = target / 50; // 50 pasos para la animación
        const duration = 2000; // 2 segundos
        const stepTime = duration / 50;
        
        const timer = setInterval(() => {
            current += increment;
            
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            // Formatear número según el valor
            let displayValue;
            if (target >= 1000) {
                displayValue = Math.floor(current).toLocaleString();
            } else {
                displayValue = Math.floor(current);
            }
            
            element.textContent = displayValue;
            
            // Efecto de escala en el número
            element.style.transform = 'scale(1.05)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 100);
            
        }, stepTime);
    }
    
    // Efectos de hover para enlaces del footer
    function initFooterLinkEffects() {
        // Enlaces principales del footer
        footerLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                // Efecto de onda
                createRippleEffect(this, 'footer-ripple');
                
                // Animación del icono
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transform = 'scale(1.2) rotate(10deg)';
                }
            });
            
            link.addEventListener('mouseleave', function() {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transform = 'scale(1) rotate(0deg)';
                }
            });
            
            // Efecto de click
            link.addEventListener('click', function(e) {
                // Efecto visual de click
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
        
        // Enlaces sociales
        socialLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.15) rotate(5deg)';
                
                // Efecto de brillo
                this.style.boxShadow = '0 10px 25px rgba(76, 175, 80, 0.4)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1) rotate(0deg)';
                this.style.boxShadow = '';
            });
            
            // Efecto de click con feedback
            link.addEventListener('click', function(e) {
                e.preventDefault(); // Para demo
                
                // Crear notificación
                showFooterNotification(`Abriendo ${this.title || 'enlace social'}...`, 'info');
                
                // Efecto visual
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
        
        // Enlaces de recursos
        resourceLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); // Para demo
                
                const resourceName = this.textContent.trim();
                showFooterNotification(`Descargando ${resourceName}...`, 'success');
                
                // Simular descarga
                this.style.opacity = '0.7';
                setTimeout(() => {
                    this.style.opacity = '1';
                }, 1000);
            });
        });
    }
    
    // Crear efecto de onda (ripple)
    function createRippleEffect(element, className = 'ripple') {
        const ripple = document.createElement('span');
        ripple.classList.add(className);
        
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (rect.width / 2 - size / 2) + 'px';
        ripple.style.top = (rect.height / 2 - size / 2) + 'px';
        
        element.appendChild(ripple);
        
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    }
    
    // Sistema de notificaciones del footer
    function showFooterNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `footer-notification footer-notification-${type}`;
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${getNotificationIcon(type)} notification-icon"></i>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Estilos de la notificación
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--white), var(--light-gray));
            color: var(--text-gray);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px var(--shadow-medium);
            border-left: 4px solid var(--${getNotificationColor(type)});
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Animación de entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-eliminar
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, duration);
    }
    
    function getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    function getNotificationColor(type) {
        const colors = {
            'info': 'water-blue',
            'success': 'secondary-green',
            'warning': 'sun-yellow',
            'error': 'text-gray'
        };
        return colors[type] || 'water-blue';
    }
    
    // Monitoreo del estado del sistema
    function initSystemStatusMonitor() {
        if (!statusIndicator) return;
        
        // Simular verificación de estado del sistema
        function checkSystemStatus() {
            // Aquí se haría una llamada real al backend
            // fetch('/api/system/status')
            //     .then(response => response.json())
            //     .then(data => updateSystemStatus(data.status));
            
            // Simulación para demo
            const isOnline = Math.random() > 0.1; // 90% probabilidad de estar online
            updateSystemStatus(isOnline ? 'online' : 'offline');
        }
        
        function updateSystemStatus(status) {
            statusIndicator.className = `fas fa-circle status-indicator ${status}`;
            
            const statusText = statusIndicator.parentElement.querySelector('.status-info');
            if (statusText) {
                statusText.innerHTML = `
                    <i class="fas fa-circle status-indicator ${status}"></i>
                    Sistema ${status === 'online' ? 'Operativo' : 'Desconectado'}
                `;
            }
        }
        
        // Verificar estado cada 30 segundos
        checkSystemStatus();
        setInterval(checkSystemStatus, 30000);
    }
    
    // Efectos de parallax suave para decoraciones
    function initParallaxEffects() {
        const decorationItems = document.querySelectorAll('.decoration-item');
        
        if (decorationItems.length === 0) return;
        
        function updateParallax() {
            const scrolled = window.pageYOffset;
            const footer = document.querySelector('.footer-custom');
            
            if (!footer) return;
            
            const footerRect = footer.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Solo aplicar parallax cuando el footer está visible
            if (footerRect.top < windowHeight && footerRect.bottom > 0) {
                decorationItems.forEach((item, index) => {
                    const speed = 0.5 + (index * 0.2);
                    const yPos = -(scrolled * speed);
                    item.style.transform = `translateY(${yPos}px) rotate(${scrolled * 0.1}deg)`;
                });
            }
        }
        
        window.addEventListener('scroll', throttle(updateParallax, 16)); // ~60fps
    }
    
    // Función throttle para optimizar performance
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Lazy loading para optimizar carga
    function initLazyLoading() {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        
        if (lazyElements.length === 0) return;
        
        const lazyObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const action = element.dataset.lazy;
                    
                    switch (action) {
                        case 'animate':
                            element.classList.add('animated');
                            break;
                        case 'load-content':
                            loadDynamicContent(element);
                            break;
                    }
                    
                    lazyObserver.unobserve(element);
                }
            });
        });
        
        lazyElements.forEach(element => {
            lazyObserver.observe(element);
        });
    }
    
    // Cargar contenido dinámico (estadísticas reales, etc.)
    function loadDynamicContent(element) {
        // Aquí se cargaría contenido real desde el backend
        console.log('Loading dynamic content for:', element);
    }
    
    // Gestión de cookies y preferencias
    function initCookieConsent() {
        const cookieConsent = localStorage.getItem('cookieConsent');
        
        if (!cookieConsent) {
            setTimeout(() => {
                showFooterNotification(
                    'Este sitio usa cookies para mejorar tu experiencia. <a href="#" style="color: var(--primary-green); text-decoration: underline;">Más info</a>',
                    'info',
                    10000
                );
            }, 3000);
        }
    }
    
    // Atajos de teclado para el footer
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl + Home para ir al inicio
            if (e.ctrlKey && e.key === 'Home') {
                e.preventDefault();
                if (scrollTopBtn) {
                    scrollTopBtn.click();
                }
            }
            
            // Ctrl + Shift + I para mostrar información del sistema
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
                showSystemInfo();
            }
        });
    }
    
    // Mostrar información del sistema
    function showSystemInfo() {
        const systemInfo = {
            'Versión': '1.0.0',
            'Navegador': navigator.userAgent.split(' ')[0],
            'Resolución': `${screen.width}x${screen.height}`,
            'Idioma': navigator.language,
            'Cookies Habilitadas': navigator.cookieEnabled ? 'Sí' : 'No',
            'Online': navigator.onLine ? 'Sí' : 'No'
        };
        
        const infoText = Object.entries(systemInfo)
            .map(([key, value]) => `${key}: ${value}`)
            .join('\\n');
        
        showFooterNotification(
            `Información del Sistema:\\n${infoText}`,
            'info',
            8000
        );
    }
    
    // Inicialización de todos los componentes
    function initializeFooter() {
        setCurrentYear();
        initScrollTopButton();
        initStatCounters();
        initFooterLinkEffects();
        initSystemStatusMonitor();
        initParallaxEffects();
        initLazyLoading();
        initCookieConsent();
        initKeyboardShortcuts();
        
        console.log('<1 Footer del Sistema de Monitoreo de Cultivos inicializado correctamente');
    }
    
    // Ejecutar inicialización
    initializeFooter();
    
    // Efecto de carga progresiva para el footer
    const footerSections = document.querySelectorAll('.footer-section');
    footerSections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            section.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, index * 200);
    });
    
    // Event listeners para cambios de conectividad
    window.addEventListener('online', function() {
        showFooterNotification('Conexión restablecida', 'success');
        if (statusIndicator) {
            statusIndicator.className = 'fas fa-circle status-indicator online';
        }
    });
    
    window.addEventListener('offline', function() {
        showFooterNotification('Sin conexión a internet', 'warning');
        if (statusIndicator) {
            statusIndicator.className = 'fas fa-circle status-indicator offline';
        }
    });
});

// Estilos CSS adicionales para efectos del footer
const footerStyles = `
    <style>
    .footer-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: scale(0);
        animation: footer-ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes footer-ripple-animation {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    .footer-notification {
        font-family: 'Arial', sans-serif;
        backdrop-filter: blur(10px);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-icon {
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .notification-message {
        flex: 1;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: var(--text-gray);
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    
    .notification-close:hover {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1.1);
    }
    
    .status-indicator.offline {
        background: #f44336 !important;
        box-shadow: 0 0 10px #f44336 !important;
    }
    
    .animated {
        animation: footer-fade-in-up 0.6s ease-out;
    }
    
    @keyframes footer-fade-in-up {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Mejoras de accesibilidad */
    @media (prefers-reduced-motion: reduce) {
        .footer-notification {
            transition: none !important;
            animation: none !important;
        }
        
        .decoration-item {
            animation: none !important;
        }
    }
    
    /* Alto contraste */
    @media (prefers-contrast: high) {
        .footer-custom {
            background: #000000 !important;
            color: #ffffff !important;
        }
        
        .footer-link,
        .social-link,
        .resource-link {
            border: 1px solid #ffffff !important;
        }
    }
    </style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', footerStyles);