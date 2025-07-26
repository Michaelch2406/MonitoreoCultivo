// JavaScript para Login - Sistema de Monitoreo de Cultivos

$(document).ready(function() {
    
    // Elementos del DOM
    const $loginForm = $('#loginForm');
    const $emailInput = $('#email');
    const $passwordInput = $('#password');
    const $rememberCheckbox = $('#remember');
    const $loginButton = $('#loginButton');
    const $alertContainer = $('#alert-container');
    const $loadingOverlay = $('#loadingOverlay');
    const $togglePasswordBtns = $('.toggle-password');
    
    // Variables de configuraci�n
    const config = {
        maxLoginAttempts: 5,
        lockoutDuration: 15 * 60 * 1000, // 15 minutos
        debounceDelay: 300,
        animationDuration: 300
    };
    
    // Inicializaci�n
    function initializeLogin() {
        setupEventListeners();
        checkSavedCredentials();
        checkLoginAttempts();
        setupFormValidation();
        startParticleAnimation();
        
        console.log('<1 Sistema de Login AgroMonitor inicializado');
    }
    
    // Configurar event listeners
    function setupEventListeners() {
        // Env�o del formulario
        $loginForm.on('submit', handleFormSubmit);
        
        // Validaci�n en tiempo real
        $emailInput.on('input', debounce(validateEmail, config.debounceDelay));
        $passwordInput.on('input', debounce(validatePassword, config.debounceDelay));
        
        // Toggle password visibility
        $togglePasswordBtns.on('click', togglePasswordVisibility);
        
        // Enlaces de ayuda
        $('.forgot-password').on('click', handleForgotPassword);
        $('.help-link').on('click', handleHelpLinks);
        
        // Atajos de teclado
        $(document).on('keydown', handleKeyboardShortcuts);
        
        // Prevenir env�o con Enter en campos espec�ficos
        $emailInput.add($passwordInput).on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $loginForm.trigger('submit');
            }
        });
        
        // Efectos de hover en botones
        $loginButton.on('mouseenter', function() {
            $(this).css('transform', 'translateY(-2px) scale(1.02)');
        }).on('mouseleave', function() {
            $(this).css('transform', 'translateY(0) scale(1)');
        });
        
        // Auto-resize del formulario
        $(window).on('resize', debounce(adjustFormLayout, 250));
    }
    
    // Manejar env�o del formulario
    function handleFormSubmit(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            showAlert('Por favor, corrige los errores en el formulario', 'danger');
            return;
        }
        
        if (isLoginLocked()) {
            showAlert('Demasiados intentos fallidos. Intenta de nuevo m�s tarde.', 'warning');
            return;
        }
        
        const formData = getFormData();
        submitLogin(formData);
    }
    
    // Obtener datos del formulario
    function getFormData() {
        return {
            email: $emailInput.val().trim(),
            password: $passwordInput.val(),
            remember: $rememberCheckbox.is(':checked'),
            timestamp: Date.now(),
            userAgent: navigator.userAgent,
            language: navigator.language
        };
    }
    
    // Enviar login al servidor
    function submitLogin(formData) {
        setLoadingState(true);
        
        $.ajax({
            url: '../AJAX/login_ajax.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                if (response.status === 'success') {
                    handleLoginSuccess(response.data);
                } else {
                    handleLoginError(response.message);
                }
            },
            error: function(xhr, status, error) {
                handleAjaxError(xhr, status, error);
            },
            complete: function() {
                setLoadingState(false);
            }
        });
    }
    
    // Simular autenticaci�n (solo para demo)
    function simulateAuthentication(formData) {
        // Credenciales de demostraci�n
        const demoCredentials = [
            { email: 'admin@agromonitor.com', password: 'admin123', role: 'administrador' },
            { email: 'agricultor@test.com', password: 'agri123', role: 'agricultor' },
            { email: 'supervisor@test.com', password: 'super123', role: 'supervisor' }
        ];
        
        return demoCredentials.some(cred => 
            cred.email === formData.email && cred.password === formData.password
        );
    }
    
    // Manejar login exitoso
    function handleLoginSuccess(userData) {
        setLoadingState(false);
        
        // Guardar datos de sesi�n
        const sessionData = {
            id: Date.now(),
            email: userData.email || $emailInput.val(),
            nombre: userData.nombre || 'Usuario',
            apellido: userData.apellido || 'Demo',
            rol: userData.rol || 'agricultor',
            loginTime: new Date().toISOString(),
            avatar: userData.avatar || null
        };
        
        // Guardar en localStorage o sessionStorage seg�n "recordar"
        const storage = $rememberCheckbox.is(':checked') ? localStorage : sessionStorage;
        storage.setItem('userSession', JSON.stringify(sessionData));
        
        // Limpiar intentos de login
        localStorage.removeItem('loginAttempts');
        
        // Mostrar mensaje de �xito
        showAlert(`�Bienvenido, ${sessionData.nombre}!`, 'success');
        
        // Animaci�n de �xito
        $loginForm.css({
            'transform': 'scale(0.95)',
            'opacity': '0.8'
        });
        
        // Redireccionar despu�s de un breve delay
        setTimeout(() => {
            // En producci�n, redirigir al dashboard
            // window.location.href = 'dashboard.php';
            
            // Para demo, mostrar mensaje
            showAlert('Redirigiendo al dashboard...', 'success');
            console.log('<� Login exitoso. Datos de sesi�n:', sessionData);
        }, 1500);
    }
    
    // Manejar error de login
    function handleLoginError(message = null) {
        setLoadingState(false);
        
        // Incrementar intentos de login
        incrementLoginAttempts();
        
        const attempts = getLoginAttempts();
        const remaining = config.maxLoginAttempts - attempts.count;
        
        let errorMessage = message || 'Credenciales incorrectas. ';
        
        if (remaining > 0) {
            errorMessage += `Te quedan ${remaining} intentos.`;
        } else {
            errorMessage = 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente.';
            lockLogin();
        }
        
        showAlert(errorMessage, 'danger');
        
        // Efecto de shake en el formulario
        $loginForm.addClass('shake');
        setTimeout(() => {
            $loginForm.removeClass('shake');
        }, 600);
        
        // Limpiar contrase�a
        $passwordInput.val('').focus();
    }
    
    // Manejar errores de AJAX
    function handleAjaxError(xhr, status, error) {
        setLoadingState(false);
        
        let message = 'Error de conexi�n. ';
        
        switch (status) {
            case 'timeout':
                message += 'La solicitud tard� demasiado tiempo.';
                break;
            case 'error':
                message += 'Error del servidor.';
                break;
            case 'abort':
                message += 'Solicitud cancelada.';
                break;
            default:
                message += 'Intenta de nuevo m�s tarde.';
        }
        
        showAlert(message, 'danger');
        console.error('Error AJAX:', { xhr, status, error });
    }
    
    // Validar formulario completo
    function validateForm() {
        const emailValid = validateEmail();
        const passwordValid = validatePassword();
        
        return emailValid && passwordValid;
    }
    
    // Validar email
    function validateEmail() {
        const email = $emailInput.val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        let isValid = true;
        let message = '';
        
        if (!email) {
            isValid = false;
            message = 'El email es requerido';
        } else if (!emailRegex.test(email)) {
            isValid = false;
            message = 'Formato de email inv�lido';
        } else if (email.length > 150) {
            isValid = false;
            message = 'El email es demasiado largo';
        }
        
        updateFieldValidation($emailInput, isValid, message);
        return isValid;
    }
    
    // Validar contrase�a
    function validatePassword() {
        const password = $passwordInput.val();
        
        let isValid = true;
        let message = '';
        
        if (!password) {
            isValid = false;
            message = 'La contrase�a es requerida';
        } else if (password.length < 6) {
            isValid = false;
            message = 'La contrase�a debe tener al menos 6 caracteres';
        } else if (password.length > 255) {
            isValid = false;
            message = 'La contrase�a es demasiado larga';
        }
        
        updateFieldValidation($passwordInput, isValid, message);
        return isValid;
    }
    
    // Actualizar validaci�n visual de campos
    function updateFieldValidation($field, isValid, message) {
        const $group = $field.closest('.form-group');
        const $feedback = $group.find('.invalid-feedback');
        
        $field.removeClass('is-valid is-invalid');
        
        if (!isValid && $field.val()) {
            $field.addClass('is-invalid');
            $feedback.text(message).show();
        } else if (isValid && $field.val()) {
            $field.addClass('is-valid');
            $feedback.hide();
        } else {
            $feedback.hide();
        }
    }
    
    // Configurar validaci�n del formulario
    function setupFormValidation() {
        // Validaci�n Bootstrap personalizada
        $loginForm.addClass('needs-validation');
        
        // Limpiar validaci�n al enfocar
        $('input').on('focus', function() {
            $(this).removeClass('is-invalid');
            $(this).closest('.form-group').find('.invalid-feedback').hide();
        });
    }
    
    // Toggle visibilidad de contrase�a
    function togglePasswordVisibility(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const targetSelector = $btn.data('target');
        const $target = $(targetSelector);
        const $icon = $btn.find('i');
        
        if ($target.attr('type') === 'password') {
            $target.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $btn.attr('title', 'Ocultar contrase�a');
        } else {
            $target.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $btn.attr('title', 'Mostrar contrase�a');
        }
        
        // Efecto visual
        $btn.css('transform', 'scale(0.95)');
        setTimeout(() => {
            $btn.css('transform', 'scale(1)');
        }, 100);
    }
    
    // Gesti�n de intentos de login
    function getLoginAttempts() {
        const stored = localStorage.getItem('loginAttempts');
        return stored ? JSON.parse(stored) : { count: 0, lastAttempt: null };
    }
    
    function incrementLoginAttempts() {
        const attempts = getLoginAttempts();
        attempts.count++;
        attempts.lastAttempt = Date.now();
        localStorage.setItem('loginAttempts', JSON.stringify(attempts));
    }
    
    function isLoginLocked() {
        const attempts = getLoginAttempts();
        
        if (attempts.count >= config.maxLoginAttempts) {
            const timePassed = Date.now() - attempts.lastAttempt;
            
            if (timePassed < config.lockoutDuration) {
                return true;
            } else {
                // Reset attempts after lockout period
                localStorage.removeItem('loginAttempts');
                return false;
            }
        }
        
        return false;
    }
    
    function lockLogin() {
        $loginButton.prop('disabled', true);
        $emailInput.prop('disabled', true);
        $passwordInput.prop('disabled', true);
        
        const lockTime = config.lockoutDuration / 60000; // minutos
        showAlert(`Cuenta bloqueada por ${lockTime} minutos por seguridad.`, 'warning');
        
        // Countdown timer
        startLockoutCountdown();
    }
    
    function checkLoginAttempts() {
        if (isLoginLocked()) {
            const attempts = getLoginAttempts();
            const timeRemaining = config.lockoutDuration - (Date.now() - attempts.lastAttempt);
            const minutesRemaining = Math.ceil(timeRemaining / 60000);
            
            showAlert(`Cuenta bloqueada. Intenta de nuevo en ${minutesRemaining} minutos.`, 'warning');
            lockLogin();
        }
    }
    
    // Countdown para desbloqueo
    function startLockoutCountdown() {
        const attempts = getLoginAttempts();
        
        const countdownInterval = setInterval(() => {
            const timeRemaining = config.lockoutDuration - (Date.now() - attempts.lastAttempt);
            
            if (timeRemaining <= 0) {
                clearInterval(countdownInterval);
                unlockLogin();
            } else {
                const minutesRemaining = Math.ceil(timeRemaining / 60000);
                updateLockoutMessage(minutesRemaining);
            }
        }, 1000);
    }
    
    function updateLockoutMessage(minutes) {
        const $alert = $alertContainer.find('.alert-warning');
        if ($alert.length) {
            $alert.find('.alert-message').text(`Cuenta bloqueada. Intenta de nuevo en ${minutes} minutos.`);
        }
    }
    
    function unlockLogin() {
        $loginButton.prop('disabled', false);
        $emailInput.prop('disabled', false);
        $passwordInput.prop('disabled', false);
        
        localStorage.removeItem('loginAttempts');
        $alertContainer.empty();
        
        showAlert('Cuenta desbloqueada. Puedes intentar iniciar sesi�n nuevamente.', 'success');
    }
    
    // Verificar credenciales guardadas
    function checkSavedCredentials() {
        const savedEmail = localStorage.getItem('savedEmail');
        const rememberMe = localStorage.getItem('rememberMe') === 'true';
        
        if (savedEmail && rememberMe) {
            $emailInput.val(savedEmail);
            $rememberCheckbox.prop('checked', true);
            $passwordInput.focus();
        }
    }
    
    // Estado de carga
    function setLoadingState(loading) {
        if (loading) {
            $loadingOverlay.fadeIn(config.animationDuration);
            $loginButton.prop('disabled', true);
            $loginButton.find('.btn-text').hide();
            $loginButton.find('.btn-loading').show();
        } else {
            $loadingOverlay.fadeOut(config.animationDuration);
            $loginButton.prop('disabled', false);
            $loginButton.find('.btn-text').show();
            $loginButton.find('.btn-loading').hide();
        }
    }
    
    // Mostrar alertas
    function showAlert(message, type = 'info', autoDismiss = true) {
        const alertClass = `alert-${type}`;
        const iconClass = getAlertIcon(type);
        
        const $alert = $(`
            <div class="custom-alert ${alertClass}" role="alert">
                <i class="fas fa-${iconClass}"></i>
                <span class="alert-message">${message}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `);
        
        $alertContainer.empty().append($alert);
        
        // Auto-dismiss despu�s de 5 segundos
        if (autoDismiss) {
            setTimeout(() => {
                $alert.fadeOut(config.animationDuration, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to alert
        $alert[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // Manejar "Olvid� mi contrase�a"
    function handleForgotPassword(e) {
        e.preventDefault();
        
        const email = $emailInput.val().trim();
        
        if (!email) {
            showAlert('Ingresa tu email primero para recuperar la contrase�a', 'warning');
            $emailInput.focus();
            return;
        }
        
        if (!validateEmail()) {
            showAlert('Ingresa un email v�lido', 'warning');
            return;
        }
        
        // Simular env�o de email de recuperaci�n
        showAlert(`Se ha enviado un email de recuperaci�n a ${email}`, 'success');
        
        // En producci�n, aqu� se har�a una llamada AJAX
        console.log('Solicitud de recuperaci�n de contrase�a para:', email);
    }
    
    // Manejar enlaces de ayuda
    function handleHelpLinks(e) {
        e.preventDefault();
        
        const linkText = $(this).text().trim();
        showAlert(`Abriendo ${linkText}...`, 'info');
        
        // En producci�n, estos abrir�an p�ginas reales
        console.log('Enlace de ayuda clickeado:', linkText);
    }
    
    // Atajos de teclado
    function handleKeyboardShortcuts(e) {
        // Ctrl + Enter para enviar formulario
        if (e.ctrlKey && e.keyCode === 13) {
            e.preventDefault();
            $loginForm.trigger('submit');
        }
        
        // Escape para limpiar formulario
        if (e.keyCode === 27) {
            clearForm();
        }
        
        // F1 para ayuda
        if (e.keyCode === 112) {
            e.preventDefault();
            showAlert('Atajos de teclado: Ctrl+Enter (enviar), Escape (limpiar), F1 (ayuda)', 'info');
        }
    }
    
    // Limpiar formulario
    function clearForm() {
        $loginForm[0].reset();
        $('input').removeClass('is-valid is-invalid');
        $('.invalid-feedback').hide();
        $alertContainer.empty();
        $emailInput.focus();
    }
    
    // Ajustar layout del formulario
    function adjustFormLayout() {
        const windowHeight = $(window).height();
        const formHeight = $('.login-panel').outerHeight();
        
        if (windowHeight < formHeight + 100) {
            $('.login-container').css('padding', '1rem 0');
        } else {
            $('.login-container').css('padding', '2rem 0');
        }
    }
    
    // Animaci�n de part�culas
    function startParticleAnimation() {
        $('.particle').each(function(index) {
            const $particle = $(this);
            const delay = Math.random() * 20000; // Delay aleatorio
            
            $particle.css('animation-delay', `-${delay}ms`);
        });
    }
    
    // Funci�n debounce para optimizar performance
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
    
    // Detecci�n de dispositivo m�vil
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Optimizaciones para m�vil
    if (isMobileDevice()) {
        // Ajustes espec�ficos para m�viles
        $('meta[name=viewport]').attr('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no');
        
        // Deshabilitar animaciones complejas en m�viles
        $('.particle').hide();
        
        // Optimizar inputs para m�vil
        $emailInput.attr('autocomplete', 'email');
        $passwordInput.attr('autocomplete', 'current-password');
    }
    
    // Inicializar el sistema
    initializeLogin();
    
    // Exponer funciones �tiles globalmente
    window.AgroMonitorLogin = {
        clearForm: clearForm,
        showAlert: showAlert,
        validateForm: validateForm
    };
});

// CSS adicional para efectos JavaScript
const loginStyles = `
    <style>
    .shake {
        animation: shake 0.6s ease-in-out;
    }
    
    @keyframes shake {
        0%, 20%, 40%, 60%, 80%, 100% {
            transform: translateX(0);
        }
        10%, 30%, 50%, 70%, 90% {
            transform: translateX(-10px);
        }
    }
    
    .btn-close {
        background: none;
        border: none;
        font-size: 0.8rem;
        opacity: 0.7;
        transition: opacity 0.3s ease;
        padding: 0.25rem;
        margin-left: auto;
    }
    
    .btn-close:hover {
        opacity: 1;
    }
    
    .custom-alert {
        position: relative;
        animation: slideInDown 0.3s ease-out;
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Efectos de accesibilidad */
    @media (prefers-reduced-motion: reduce) {
        .shake,
        .custom-alert,
        .particle {
            animation: none !important;
        }
    }
    
    /* Mejoras para pantalla t�ctil */
    @media (hover: none) and (pointer: coarse) {
        .btn-login:hover,
        .btn-register:hover {
            transform: none;
        }
        
        .toggle-password {
            min-width: 44px;
            min-height: 44px;
        }
    }
    </style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', loginStyles);