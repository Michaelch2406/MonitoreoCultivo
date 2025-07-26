/**
 * Registro JS - Sistema de Monitoreo de Cultivos
 * Funcionalidad para el formulario de registro multi-paso
 */

// Variables globales
let currentStep = 1;
const totalSteps = 2;
let emailVerified = false;
let formData = {};

// Validaciones de fortaleza de contrase�a
const passwordRequirements = {
    length: { pattern: /.{8,}/, message: 'Al menos 8 caracteres' },
    uppercase: { pattern: /[A-Z]/, message: 'Una letra may�scula' },
    lowercase: { pattern: /[a-z]/, message: 'Una letra min�scula' },
    number: { pattern: /\d/, message: 'Un n�mero' }
};

// Inicializaci�n cuando el documento est� listo
$(document).ready(function() {
    initializeForm();
    setupEventListeners();
    showStep(1);
});

/**
 * Inicializa el formulario de registro
 */
function initializeForm() {
    // Configurar validaciones en tiempo real
    setupRealTimeValidation();
    
    // Configurar tooltips de Bootstrap
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Animar entrada de elementos
    animateFormElements();
    
    // Actualizar a�o actual en footer si existe
    updateCurrentYear();
}

/**
 * Configura todos los event listeners
 */
function setupEventListeners() {
    // Navegaci�n entre pasos
    $('#next-step-1').click(() => handleStepNavigation('next', 1));
    $('#next-step-2').click(() => handleStepNavigation('next', 2));
    $('#prev-step-2').click(() => handleStepNavigation('prev', 2));
    $('#prev-step-3').click(() => handleStepNavigation('prev', 3));
    
    // Validaci�n de fortaleza de contrase�a
    $('#password').on('input', validatePasswordStrength);
    $('#confirm-password').on('input', validatePasswordMatch);
    
    // Toggle de contrase�as
    $('.toggle-password').click(function() {
        togglePasswordVisibility($(this).data('target'));
    });
    
    // Verificaci�n de email
    $('#verify-email-btn').click(handleEmailVerification);
    $('#resend-code').click(handleResendCode);
    
    // Validaci�n de c�digo de verificaci�n
    $('#verification-code').on('input', validateVerificationCode);
    
    // Env�o del formulario
    $('#registerForm').submit(handleFormSubmission);
    
    // Validaci�n en tiempo real de campos
    $('.form-control').on('blur', function() {
        validateField($(this));
    });
    
    // Teclas de acceso r�pido
    $(document).keydown(handleKeyboardShortcuts);
    
    // Prevenir env�o accidental del formulario
    $('.form-control').keypress(function(e) {
        if (e.which === 13 && !$(this).is('textarea')) {
            e.preventDefault();
            handleEnterKey();
        }
    });
}

/**
 * Maneja la navegaci�n entre pasos
 */
function handleStepNavigation(direction, step) {
    if (direction === 'next') {
        if (validateCurrentStep(step)) {
            if (step < totalSteps) {
                currentStep = step + 1;
                showStep(currentStep);
                updateStepIndicator();
            }
        }
    } else if (direction === 'prev') {
        if (step > 1) {
            currentStep = step - 1;
            showStep(currentStep);
            updateStepIndicator();
        }
    }
}

/**
 * Muestra el paso especificado
 */
function showStep(stepNumber) {
    // Ocultar todos los pasos
    $('.form-step').removeClass('active').hide();
    
    // Mostrar el paso actual
    $(`#step-${stepNumber}`).addClass('active').fadeIn(300);
    
    // Enfocar el primer campo del paso
    setTimeout(() => {
        $(`#step-${stepNumber} .form-control:first`).focus();
    }, 350);
    
    // Scroll hacia arriba del formulario
    $('.register-panel').animate({ scrollTop: 0 }, 300);
}

/**
 * Actualiza el indicador de progreso
 */
function updateStepIndicator() {
    $('.step').removeClass('active completed');
    
    for (let i = 1; i <= totalSteps; i++) {
        if (i < currentStep) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        } else if (i === currentStep) {
            $(`.step[data-step="${i}"]`).addClass('active');
        }
    }
}

/**
 * Valida el paso actual
 */
function validateCurrentStep(step) {
    let isValid = true;
    const currentStepElement = $(`#step-${step}`);
    
    // Obtener todos los campos requeridos del paso actual
    const requiredFields = currentStepElement.find('.form-control[required]');
    
    requiredFields.each(function() {
        if (!validateField($(this))) {
            isValid = false;
        }
    });
    
    // Validaciones espec�ficas por paso
    switch (step) {
        case 1:
            isValid = validateStep1() && isValid;
            break;
        case 2:
            isValid = validateStep2() && isValid;
            break;
        // Paso 3 eliminado
    }
    
    if (!isValid) {
        showAlert('Por favor, completa todos los campos requeridos correctamente.', 'danger');
    }
    
    return isValid;
}

/**
 * Validaciones espec�ficas del paso 1
 */
function validateStep1() {
    let isValid = true;
    
    // Validar nombre y apellido
    const nombre = $('#nombre').val().trim();
    const apellido = $('#apellido').val().trim();
    const rol = $('#rol').val();
    
    if (nombre.length < 2) {
        setFieldError('#nombre', 'El nombre debe tener al menos 2 caracteres');
        isValid = false;
    } else {
        setFieldSuccess('#nombre');
    }
    
    if (apellido.length < 2) {
        setFieldError('#apellido', 'El apellido debe tener al menos 2 caracteres');
        isValid = false;
    } else {
        setFieldSuccess('#apellido');
    }
    
    if (!rol) {
        setFieldError('#rol', 'Debes seleccionar un rol');
        isValid = false;
    } else {
        setFieldSuccess('#rol');
    }
    
    // Validar tel�fono si est� presente
    const telefono = $('#telefono').val().trim();
    if (telefono && !validatePhone(telefono)) {
        setFieldError('#telefono', 'Formato de tel�fono inv�lido');
        isValid = false;
    } else if (telefono) {
        setFieldSuccess('#telefono');
    }
    
    return isValid;
}

/**
 * Validaciones espec�ficas del paso 2
 */
function validateStep2() {
    let isValid = true;
    
    // Validar email
    const email = $('#email').val().trim();
    if (!validateEmail(email)) {
        setFieldError('#email', 'Formato de email inv�lido');
        isValid = false;
    } else {
        setFieldSuccess('#email');
    }
    
    // Validar contrase�a
    const password = $('#password').val();
    if (!validatePassword(password)) {
        setFieldError('#password', 'La contrase�a no cumple con los requisitos');
        isValid = false;
    } else {
        setFieldSuccess('#password');
    }
    
    // Validar confirmaci�n de contrase�a
    const confirmPassword = $('#confirm-password').val();
    if (password !== confirmPassword) {
        setFieldError('#confirm-password', 'Las contrase�as no coinciden');
        isValid = false;
    } else if (confirmPassword) {
        setFieldSuccess('#confirm-password');
    }
    
    // Validar términos y condiciones
    if (!$('#terms').is(':checked')) {
        showAlert('Debes aceptar los términos y condiciones para continuar', 'warning');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validaciones espec�ficas del paso 3
 */
function validateStep3() {
    let isValid = true;
    
    // Validar t�rminos y condiciones
    if (!$('#terms').is(':checked')) {
        showAlert('Debes aceptar los t�rminos y condiciones para continuar', 'warning');
        isValid = false;
    }
    
    // Validar c�digo de verificaci�n si est� visible
    if ($('#verification-email-section').is(':visible')) {
        const code = $('#verification-code').val().trim();
        if (!code || code.length !== 6) {
            setFieldError('#verification-code', 'C�digo de verificaci�n inv�lido');
            isValid = false;
        } else {
            setFieldSuccess('#verification-code');
        }
    }
    
    return isValid;
}

/**
 * Valida un campo individual
 */
function validateField($field) {
    const value = $field.val().trim();
    const fieldName = $field.attr('name');
    let isValid = true;
    
    // Validaci�n de campos requeridos
    if ($field.attr('required') && !value) {
        setFieldError($field, 'Este campo es requerido');
        return false;
    }
    
    // Validaciones espec�ficas por tipo
    switch (fieldName) {
        case 'email':
            if (value && !validateEmail(value)) {
                setFieldError($field, 'Formato de email inv�lido');
                isValid = false;
            }
            break;
        case 'telefono':
            if (value && !validatePhone(value)) {
                setFieldError($field, 'Formato de tel�fono inv�lido');
                isValid = false;
            }
            break;
        case 'password':
            if (value && !validatePassword(value)) {
                setFieldError($field, 'La contrase�a no cumple con los requisitos');
                isValid = false;
            }
            break;
        case 'confirm-password':
            const password = $('#password').val();
            if (value && value !== password) {
                setFieldError($field, 'Las contrase�as no coinciden');
                isValid = false;
            }
            break;
    }
    
    if (isValid && value) {
        setFieldSuccess($field);
    }
    
    return isValid;
}

/**
 * Configura validaci�n en tiempo real
 */
function setupRealTimeValidation() {
    // Validaci�n de email mientras escribe
    $('#email').on('input', function() {
        const email = $(this).val().trim();
        if (email && validateEmail(email)) {
            $('#verify-email-btn').prop('disabled', false);
        } else {
            $('#verify-email-btn').prop('disabled', true);
        }
    });
    
    // Validaci�n de c�digo de verificaci�n
    $('#verification-code').on('input', function() {
        const code = $(this).val().replace(/\D/g, '').substring(0, 6);
        $(this).val(code);
        
        if (code.length === 6) {
            // Simular validaci�n del c�digo
            setTimeout(() => {
                if (Math.random() > 0.3) { // 70% de �xito
                    setFieldSuccess($(this));
                    showAlert('C�digo verificado correctamente', 'success');
                } else {
                    setFieldError($(this), 'C�digo incorrecto');
                }
            }, 1000);
        }
    });
}

/**
 * Valida la fortaleza de la contrase�a
 */
function validatePasswordStrength() {
    const password = $('#password').val();
    const strengthBar = $('.strength-bar');
    const strengthText = $('.strength-text');
    let score = 0;
    let strengthLevel = '';
    
    // Evaluar cada requisito
    Object.keys(passwordRequirements).forEach(requirement => {
        const requirementElement = $(`.requirement[data-requirement="${requirement}"]`);
        const icon = requirementElement.find('i');
        
        if (passwordRequirements[requirement].pattern.test(password)) {
            requirementElement.addClass('valid');
            icon.removeClass('fa-times').addClass('fa-check');
            score++;
        } else {
            requirementElement.removeClass('valid');
            icon.removeClass('fa-check').addClass('fa-times');
        }
    });
    
    // Determinar nivel de fortaleza
    if (score === 0) {
        strengthLevel = '';
        strengthText.text('Fortaleza de la contrase�a');
    } else if (score === 1) {
        strengthLevel = 'weak';
        strengthText.text('Contrase�a d�bil');
    } else if (score === 2) {
        strengthLevel = 'fair';
        strengthText.text('Contrase�a regular');
    } else if (score === 3) {
        strengthLevel = 'good';
        strengthText.text('Contrase�a buena');
    } else if (score === 4) {
        strengthLevel = 'strong';
        strengthText.text('Contrase�a fuerte');
    }
    
    // Actualizar barra visual
    strengthBar.removeClass('weak fair good strong').addClass(strengthLevel);
}

/**
 * Valida que las contrase�as coincidan
 */
function validatePasswordMatch() {
    const password = $('#password').val();
    const confirmPassword = $('#confirm-password').val();
    
    if (confirmPassword && password !== confirmPassword) {
        setFieldError('#confirm-password', 'Las contrase�as no coinciden');
    } else if (confirmPassword) {
        setFieldSuccess('#confirm-password');
    }
}

/**
 * Maneja la verificaci�n de email
 */
function handleEmailVerification() {
    const email = $('#email').val().trim();
    
    if (!validateEmail(email)) {
        showAlert('Por favor ingresa un email v�lido antes de verificar', 'warning');
        return;
    }
    
    // Mostrar loading
    const btn = $('#verify-email-btn');
    const originalHtml = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    
    // Simular env�o de c�digo
    setTimeout(() => {
        btn.html(originalHtml).prop('disabled', false);
        $('#verification-email-section').slideDown(300);
        showAlert('C�digo de verificaci�n enviado a tu email', 'success');
        emailVerified = true;
    }, 2000);
}

/**
 * Maneja el reenv�o de c�digo
 */
function handleResendCode() {
    const btn = $('#resend-code');
    const originalHtml = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    
    setTimeout(() => {
        btn.html(originalHtml).prop('disabled', false);
        showAlert('Nuevo c�digo enviado', 'info');
    }, 1500);
}

/**
 * Valida el c�digo de verificaci�n
 */
function validateVerificationCode() {
    const code = $('#verification-code').val().trim();
    if (code.length === 6) {
        // Aqu� ir�a la validaci�n real del c�digo
        setFieldSuccess('#verification-code');
    }
}

/**
 * Maneja el env�o del formulario
 */
function handleFormSubmission(e) {
    e.preventDefault();
    
    if (!validateCurrentStep(2)) {
        return;
    }
    
    // Recopilar datos del formulario
    collectFormData();
    
    // Mostrar loading
    showLoadingOverlay('Creando tu cuenta...');
    
    // Deshabilitar bot�n de env�o
    const submitBtn = $('#register-btn');
    submitBtn.find('.btn-text').hide();
    submitBtn.find('.btn-loading').show();
    submitBtn.prop('disabled', true);
    
    // Simular proceso de registro
    setTimeout(() => {
        // Simular �xito o error aleatorio
        // Enviar datos al servidor vía AJAX
        $.ajax({
            url: '../AJAX/registro_ajax.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    handleRegistrationSuccess();
                } else {
                    handleRegistrationError(response.message);
                }
            },
            error: function(xhr, status, error) {
                handleRegistrationError('Error de conexión. Por favor intenta nuevamente.');
            }
        });
    }, 3000);
}

/**
 * Recopila todos los datos del formulario
 */
function collectFormData() {
    formData = {
        nombre: $('#nombre').val().trim(),
        apellido: $('#apellido').val().trim(),
        telefono: $('#telefono').val().trim(),
        rol: $('#rol').val() || 'agricultor',
        email: $('#email').val().trim(),
        password: $('#password').val(),
        'confirm-password': $('#confirm-password').val(),
        terms: $('#terms').is(':checked'),
        newsletter: $('#newsletter').is(':checked')
    };
}

/**
 * Maneja el �xito del registro
 */
function handleRegistrationSuccess() {
    hideLoadingOverlay();
    
    // Mostrar mensaje de �xito
    showAlert('�Cuenta creada exitosamente! Redirigiendo al dashboard...', 'success');
    
    // Animar �xito
    $('.register-panel').addClass('animate__animated animate__pulse');
    
    // Redirigir al dashboard despu�s de un delay (ya hay sesi�n creada)
    setTimeout(() => {
        window.location.href = 'dashboard.php';
    }, 2000);
}

/**
 * Maneja errores en el registro
 */
function handleRegistrationError(message) {
    hideLoadingOverlay();
    
    // Restaurar bot�n
    const submitBtn = $('#register-btn');
    submitBtn.find('.btn-loading').hide();
    submitBtn.find('.btn-text').show();
    submitBtn.prop('disabled', false);
    
    // Mostrar error
    showAlert(message, 'danger');
    
    // Vibrar formulario
    $('.register-panel').addClass('animate__animated animate__shakeX');
    setTimeout(() => {
        $('.register-panel').removeClass('animate__animated animate__shakeX');
    }, 1000);
}

/**
 * Alterna la visibilidad de la contrase�a
 */
function togglePasswordVisibility(target) {
    const field = $(target);
    const button = $(`.toggle-password[data-target="${target}"]`);
    const icon = button.find('i');
    
    if (field.attr('type') === 'password') {
        field.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        field.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

/**
 * Maneja teclas de acceso r�pido
 */
function handleKeyboardShortcuts(e) {
    // Escape para cerrar alertas
    if (e.key === 'Escape') {
        $('.alert').fadeOut();
    }
    
    // Ctrl+Enter para enviar formulario si est� en el �ltimo paso
    if (e.ctrlKey && e.key === 'Enter' && currentStep === totalSteps) {
        e.preventDefault();
        $('#registerForm').submit();
    }
    
    // Flechas para navegar entre pasos
    if (e.ctrlKey) {
        if (e.key === 'ArrowRight' && currentStep < totalSteps) {
            e.preventDefault();
            handleStepNavigation('next', currentStep);
        } else if (e.key === 'ArrowLeft' && currentStep > 1) {
            e.preventDefault();
            handleStepNavigation('prev', currentStep);
        }
    }
}

/**
 * Maneja la tecla Enter en campos
 */
function handleEnterKey() {
    if (currentStep < totalSteps) {
        // Intentar avanzar al siguiente paso
        if (validateCurrentStep(currentStep)) {
            handleStepNavigation('next', currentStep);
        }
    } else {
        // En el �ltimo paso, enviar formulario
        $('#registerForm').submit();
    }
}

/**
 * Funciones de validaci�n
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

function validatePassword(password) {
    return Object.values(passwordRequirements).every(req => 
        req.pattern.test(password)
    );
}

/**
 * Funciones de UI
 */
function setFieldError(field, message) {
    const $field = $(field);
    $field.removeClass('is-valid').addClass('is-invalid');
    $field.siblings('.invalid-feedback').text(message);
}

function setFieldSuccess(field) {
    const $field = $(field);
    $field.removeClass('is-invalid').addClass('is-valid');
    $field.siblings('.invalid-feedback').text('');
}

function showAlert(message, type, duration = 5000) {
    const alertHtml = `
        <div class="custom-alert alert-${type}" role="alert">
            <i class="fas fa-${getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="$(this).parent().fadeOut()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    $('#alert-container').html(alertHtml);
    
    if (duration > 0) {
        setTimeout(() => {
            $('#alert-container .custom-alert').fadeOut();
        }, duration);
    }
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

function showLoadingOverlay(message = 'Procesando...') {
    $('#loadingOverlay .loading-text').text(message);
    $('#loadingOverlay').fadeIn(300);
}

function hideLoadingOverlay() {
    $('#loadingOverlay').fadeOut(300);
}

function animateFormElements() {
    // Animar elementos del formulario al cargar
    $('.form-group').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 100).animate({
            'opacity': '1'
        }, 500).css('transform', 'translateY(0)');
    });
}

function updateCurrentYear() {
    const currentYear = new Date().getFullYear();
    $('#current-year').text(currentYear);
}

/**
 * Funciones de utilidad
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

// Configurar validaci�n con debounce para mejor rendimiento
const debouncedValidation = debounce(function(field) {
    validateField($(field));
}, 300);

// Aplicar debounce a validaci�n en tiempo real
$(document).on('input', '.form-control', function() {
    debouncedValidation(this);
});

/**
 * Eventos de ventana
 */
$(window).on('beforeunload', function(e) {
    // Advertir si hay datos sin guardar
    if (currentStep > 1 && Object.keys(formData).length === 0) {
        e.preventDefault();
        return 'Tienes un formulario de registro sin completar. �Est�s seguro de que quieres salir?';
    }
});

// Manejar redimensionamiento de ventana
$(window).resize(function() {
    // Ajustar altura del panel si es necesario
    if ($(window).width() < 768) {
        $('.register-panel').css('max-height', 'none');
    } else {
        $('.register-panel').css('max-height', '90vh');
    }
});

/**
 * Accessibility enhancements
 */
function enhanceAccessibility() {
    // Agregar roles ARIA
    $('.form-step').attr('role', 'tabpanel');
    $('.step-indicator').attr('role', 'tablist');
    
    // Configurar navegaci�n por teclado
    $('.step').attr('tabindex', '0').keypress(function(e) {
        if (e.which === 13 || e.which === 32) {
            const stepNumber = $(this).data('step');
            if (stepNumber < currentStep) {
                currentStep = stepNumber;
                showStep(currentStep);
                updateStepIndicator();
            }
        }
    });
}

// Inicializar mejoras de accesibilidad
$(document).ready(enhanceAccessibility);