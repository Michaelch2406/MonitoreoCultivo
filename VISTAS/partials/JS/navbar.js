// JavaScript para Navbar - Sistema de Monitoreo de Cultivos

// Optimización: usar función debounce para scroll
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

document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM - optimizado para evitar búsquedas repetidas
    const navbar = document.querySelector('.navbar-custom');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    const notificationBadge = document.getElementById('notification-count');
    const logoutBtn = document.getElementById('nav-logout');
    
    // Efecto de scroll en navbar optimizado con debounce
    let ticking = false;
    
    function updateNavbar() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 50) {
            navbar?.classList.add('scrolled');
        } else {
            navbar?.classList.remove('scrolled');
        }
        ticking = false;
    }
    
    const debouncedScroll = debounce(() => {
        if (!ticking) {
            requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    }, 10);
    
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
    
    // Event listeners optimizados para los enlaces de navegaci�n
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
    });
    
    // Agregar event listener para scroll optimizado
    window.addEventListener('scroll', debouncedScroll, { passive: true });
    
    // Restaurar enlace activo desde localStorage
    const savedActiveLink = localStorage.getItem('activeNavLink');
    if (savedActiveLink) {
        const linkToActivate = document.getElementById(savedActiveLink) || 
                              Array.from(navLinks).find(link => link.textContent.trim() === savedActiveLink);
        if (linkToActivate) {
            setActiveLink(linkToActivate);
        }
    }
    
    // Manejo directo del logout sin confirmación
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Limpiar localStorage
            localStorage.removeItem('activeNavLink');
            localStorage.removeItem('userSession');
            
            // Redireccionar directamente al logout.php
            window.location.href = 'logout.php';
        });
    }
    
    // Gesti�n de notificaciones
    function updateNotificationCount(count) {
        if (notificationBadge) {
            if (count > 0) {
                notificationBadge.textContent = count > 99 ? '99+' : count;
                notificationBadge.style.display = 'flex';
                
                // Animaci�n de nueva notificaci�n
                notificationBadge.style.animation = 'none';
                setTimeout(() => {
                    notificationBadge.style.animation = 'pulse 2s infinite, newNotification 0.5s ease-out';
                }, 10);
            } else {
                notificationBadge.style.display = 'none';
            }
        }
    }
    
    // Simulaci�n de nuevas notificaciones (se conectar� con el backend)
    function simulateNewNotification() {
        const currentCount = parseInt(notificationBadge?.textContent || '0');
        updateNotificationCount(currentCount + 1);
        
        // Mostrar toast de nueva notificaci�n
        showNotificationToast('Nueva notificaci�n recibida', 'info');
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
        
        // Animaci�n de entrada
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-eliminar despu�s de 5 segundos
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
            
            // Logout directo sin confirmación
            localStorage.removeItem('activeNavLink');
            localStorage.removeItem('userSession');
            window.location.href = 'logout.php';
        });
    }
    
    // Gesti�n de sesi�n de usuario
    function initializeUserSession() {
        // Obtener datos del usuario desde sessionStorage o localStorage
        const userData = JSON.parse(localStorage.getItem('userSession') || 'null');
        
        if (userData && usernameDisplay) {
            usernameDisplay.textContent = userData.nombre || 'Usuario';
            
            // Actualizar avatar si est� disponible
            const userAvatar = document.querySelector('.user-avatar');
            if (userAvatar && userData.avatar) {
                userAvatar.src = userData.avatar;
            }
        }
    }
    
    // Verificar permisos seg�n el rol del usuario
    function checkUserPermissions() {
        const userData = JSON.parse(localStorage.getItem('userSession') || 'null');
        
        if (userData && userData.rol) {
            // Ocultar elementos seg�n el rol
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
    
    // Actualizaci�n autom�tica de notificaciones
    function setupNotificationPolling() {
        // Simulaci�n - se conectar� con el backend real
        setInterval(() => {
            // Aqu� se har�a una llamada AJAX para obtener nuevas notificaciones
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
                if (!confirm('Este enlace te llevar� a un sitio externo. �Deseas continuar?')) {
                    e.preventDefault();
                }
            }
        });
    });
    
    // Inicializaci�n
    showNavbarLoadingEffect();
    initializeUserSession();
    checkUserPermissions();
    setupNotificationPolling();
    
    // Simular notificaci�n inicial (para demostraci�n)
    setTimeout(() => {
        updateNotificationCount(3);
    }, 2000);
    
    // Event listener para cambios de tama�o de ventana
    window.addEventListener('resize', function() {
        // Ajustar navbar en dispositivos m�viles
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

// Estilos CSS adicionales para efectos din�micos
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