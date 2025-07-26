// JavaScript para Navbar - Sistema de Monitoreo de Cultivos

document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const navbar = document.querySelector('.navbar-custom');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const notificationBadge = document.getElementById('notification-count');
    const logoutBtn = document.getElementById('logout-btn');
    const usernameDisplay = document.getElementById('username-display');
    
    // Efecto de scroll en navbar
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
    
    // Manejo de enlaces activos
    function setActiveLink(clickedLink) {
        // Remover clase active de todos los enlaces
        navLinks.forEach(link => {
            link.classList.remove('active');
        });
        
        // Agregar clase active al enlace clickeado
        if (clickedLink) {
            clickedLink.classList.add('active');
        }
    }
    
    // Event listeners para los enlaces de navegación
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Solo para enlaces que no son dropdowns
            if (!this.classList.contains('dropdown-toggle')) {
                setActiveLink(this);
                
                // Guardar el enlace activo en localStorage
                const linkId = this.getAttribute('id') || this.textContent.trim();
                localStorage.setItem('activeNavLink', linkId);
            }
        });
        
        // Efecto hover mejorado
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Restaurar enlace activo desde localStorage
    const savedActiveLink = localStorage.getItem('activeNavLink');
    if (savedActiveLink) {
        const linkToActivate = document.getElementById(savedActiveLink) || 
                              Array.from(navLinks).find(link => link.textContent.trim() === savedActiveLink);
        if (linkToActivate) {
            setActiveLink(linkToActivate);
        }
    }
    
    // Animación para items del dropdown
    dropdownItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px) scale(1.02)';
            
            // Efecto de onda
            const ripple = document.createElement('span');
            ripple.classList.add('ripple-effect');
            this.appendChild(ripple);
            
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.parentNode.removeChild(ripple);
                }
            }, 600);
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
        });
    });
    
    // Gestión de notificaciones
    function updateNotificationCount(count) {
        if (notificationBadge) {
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'flex';
                
                // Animación de nueva notificación
                notificationBadge.style.animation = 'none';
                setTimeout(() => {
                    notificationBadge.style.animation = 'pulse 2s infinite, newNotification 0.5s ease-out';
                }, 10);
            } else {
                notificationBadge.style.display = 'none';
            }
        }
    }
    
    // Simulación de nuevas notificaciones (se conectará con el backend)
    function simulateNewNotification() {
        const currentCount = parseInt(notificationBadge?.textContent || '0');
        updateNotificationCount(currentCount + 1);
        
        // Mostrar toast de nueva notificación
        showNotificationToast('Nueva notificación recibida', 'info');
    }
    
    // Sistema de toast para notificaciones
    function showNotificationToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `notification-toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${getToastIcon(type)} toast-icon"></i>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Estilos para el toast
        toast.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, var(--white), var(--light-gray));
            color: var(--text-gray);
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 8px 25px var(--shadow-medium);
            border-left: 4px solid var(--${getToastColor(type)});
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out, fadeOut 0.3s ease-out 4.7s;
            transform: translateX(100%);
        `;
        
        document.body.appendChild(toast);
        
        // Animación de entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }, 5000);
    }
    
    function getToastIcon(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    function getToastColor(type) {
        const colors = {
            'info': 'water-blue',
            'success': 'secondary-green',
            'warning': 'sun-yellow',
            'error': 'text-gray'
        };
        return colors[type] || 'water-blue';
    }
    
    // Manejo del logout
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Mostrar confirmación
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                // Animación de logout
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cerrando sesión...';
                
                // Limpiar datos locales
                localStorage.removeItem('activeNavLink');
                localStorage.removeItem('userSession');
                
                // Simular tiempo de logout
                setTimeout(() => {
                    showNotificationToast('Sesión cerrada exitosamente', 'success');
                    
                    // Redireccionar después de un breve delay
                    setTimeout(() => {
                        window.location.href = '../login.php';
                    }, 1000);
                }, 1500);
            }
        });
    }
    
    // Gestión de sesión de usuario
    function initializeUserSession() {
        // Obtener datos del usuario desde sessionStorage o localStorage
        const userData = JSON.parse(localStorage.getItem('userSession') || 'null');
        
        if (userData && usernameDisplay) {
            usernameDisplay.textContent = userData.nombre || 'Usuario';
            
            // Actualizar avatar si está disponible
            const userAvatar = document.querySelector('.user-avatar');
            if (userAvatar && userData.avatar) {
                userAvatar.src = userData.avatar;
            }
        }
    }
    
    // Verificar permisos según el rol del usuario
    function checkUserPermissions() {
        const userData = JSON.parse(localStorage.getItem('userSession') || 'null');
        
        if (userData && userData.rol) {
            // Ocultar elementos según el rol
            const adminElements = document.querySelectorAll('[data-role="administrador"]');
            const supervisorElements = document.querySelectorAll('[data-role="supervisor"]');
            
            if (userData.rol !== 'administrador') {
                adminElements.forEach(element => {
                    element.style.display = 'none';
                });
            }
            
            if (userData.rol === 'agricultor') {
                supervisorElements.forEach(element => {
                    element.style.display = 'none';
                });
            }
        }
    }
    
    // Actualización automática de notificaciones
    function setupNotificationPolling() {
        // Simulación - se conectará con el backend real
        setInterval(() => {
            // Aquí se haría una llamada AJAX para obtener nuevas notificaciones
            // fetch('/api/notifications/check')
            //     .then(response => response.json())
            //     .then(data => {
            //         if (data.newCount > 0) {
            //             updateNotificationCount(data.totalCount);
            //         }
            //     });
        }, 30000); // Verificar cada 30 segundos
    }
    
    // Efecto de carga para el navbar
    function showNavbarLoadingEffect() {
        navbar.style.opacity = '0';
        navbar.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            navbar.style.transition = 'all 0.5s ease-out';
            navbar.style.opacity = '1';
            navbar.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Manejo de enlaces externos
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.hostname !== window.location.hostname) {
                if (!confirm('Este enlace te llevará a un sitio externo. ¿Deseas continuar?')) {
                    e.preventDefault();
                }
            }
        });
    });
    
    // Inicialización
    showNavbarLoadingEffect();
    initializeUserSession();
    checkUserPermissions();
    setupNotificationPolling();
    
    // Simular notificación inicial (para demostración)
    setTimeout(() => {
        updateNotificationCount(3);
    }, 2000);
    
    // Event listener para cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        // Ajustar navbar en dispositivos móviles
        if (window.innerWidth <= 991.98) {
            navbar.classList.add('mobile-navbar');
        } else {
            navbar.classList.remove('mobile-navbar');
        }
    });
    
    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // Alt + H para ir al dashboard
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            const dashboardLink = document.getElementById('nav-dashboard');
            if (dashboardLink) {
                dashboardLink.click();
            }
        }
        
        // Alt + L para logout
        if (e.altKey && e.key === 'l') {
            e.preventDefault();
            if (logoutBtn) {
                logoutBtn.click();
            }
        }
        
        // Escape para cerrar dropdowns
        if (e.key === 'Escape') {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
    
    console.log('<1 Navbar del Sistema de Monitoreo de Cultivos inicializado correctamente');
});

// Estilos CSS adicionales para efectos dinámicos
const additionalStyles = `
    <style>
    .ripple-effect {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        left: 50%;
        top: 50%;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes newNotification {
        0% { transform: scale(1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1); }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    .notification-toast {
        font-family: 'Arial', sans-serif;
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .toast-icon {
        font-size: 1.2rem;
    }
    
    .toast-message {
        flex: 1;
        font-weight: 500;
    }
    
    .toast-close {
        background: none;
        border: none;
        color: var(--text-gray);
        cursor: pointer;
        padding: 0.2rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .toast-close:hover {
        background-color: rgba(0, 0, 0, 0.1);
        transform: scale(1.1);
    }
    
    .mobile-navbar .navbar-collapse {
        max-height: 70vh;
        overflow-y: auto;
    }
    </style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', additionalStyles);