/**
 * JavaScript para la página de contacto
 * AgroMonitor - Sistema de Monitoreo de Cultivos
 */

$(document).ready(function() {
    
    // Inicializar funcionalidades
    initEventListeners();
    initFormValidation();
    precargarDatosUsuario();
    
    // Configurar contador de caracteres
    setupCharacterCounter();
});

/**
 * Inicializar Event Listeners
 */
function initEventListeners() {
    
    // Envío del formulario de contacto
    $('#formContacto').on('submit', function(e) {
        e.preventDefault();
        enviarFormularioContacto();
    });
    
    // Botón para ver mapa
    $('#btnVerMapa').on('click', function() {
        abrirGoogleMaps();
    });
    
    // Validación en tiempo real
    $('#email').on('blur', function() {
        validarEmail($(this));
    });
    
    $('#telefono').on('input', function() {
        formatearTelefono($(this));
    });
    
    // Cambios en tipo de consulta
    $('#tipoConsulta').on('change', function() {
        actualizarCamposSegunTipo($(this).val());
    });
    
    // Reset del formulario
    $('#formContacto').on('reset', function() {
        setTimeout(() => {
            limpiarValidaciones();
            $('#contadorCaracteres').text('0');
        }, 10);
    });
    
    // Tooltips para información adicional
    $('[data-bs-toggle="tooltip"]').tooltip();
}

/**
 * Configurar contador de caracteres para el mensaje
 */
function setupCharacterCounter() {
    $('#mensaje').on('input', function() {
        const maxLength = 1000;
        const currentLength = $(this).val().length;
        
        $('#contadorCaracteres').text(currentLength);
        
        // Cambiar color según proximidad al límite
        if (currentLength > maxLength * 0.9) {
            $('#contadorCaracteres').addClass('text-warning');
        } else if (currentLength >= maxLength) {
            $('#contadorCaracteres').addClass('text-danger').removeClass('text-warning');
        } else {
            $('#contadorCaracteres').removeClass('text-warning text-danger');
        }
        
        // Limitar caracteres
        if (currentLength > maxLength) {
            $(this).val($(this).val().substring(0, maxLength));
            $('#contadorCaracteres').text(maxLength);
        }
    });
}

/**
 * Precargar datos del usuario si está logueado
 */
function precargarDatosUsuario() {
    if (window.usuarioLogueado && window.usuarioActual) {
        $('#nombreCompleto').val(window.usuarioActual.nombre);
        $('#email').val(window.usuarioActual.email);
        
        // Marcar campos como prellenados
        $('#nombreCompleto, #email').addClass('form-prellenado');
    }
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation() {
    // Validaciones personalizadas de Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Enviar formulario de contacto
 */
function enviarFormularioContacto() {
    
    // Validar formulario
    if (!validarFormularioContacto()) {
        return;
    }
    
    // Mostrar loading
    mostrarLoading();
    
    const formData = new FormData($('#formContacto')[0]);
    
    // Agregar datos adicionales
    formData.append('accion', 'enviar_contacto');
    formData.append('fecha_envio', new Date().toISOString());
    formData.append('user_agent', navigator.userAgent);
    
    // Simular envío (en producción conectar con backend)
    setTimeout(() => {
        procesarRespuestaContacto({
            success: true,
            message: 'Tu mensaje ha sido enviado exitosamente. Te contactaremos pronto.',
            ticket_id: 'AG-' + Date.now()
        });
    }, 2000);
    
    // En producción, usar esta estructura:
    /*
    $.ajax({
        url: '../CONTROLADORES/contacto_c.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            procesarRespuestaContacto(response);
        },
        error: function(xhr, status, error) {
            ocultarLoading();
            mostrarError('Error de comunicación', 'No se pudo enviar el mensaje. Por favor, intenta nuevamente.');
        }
    });
    */
}

/**
 * Procesar respuesta del envío de contacto
 */
function procesarRespuestaContacto(response) {
    ocultarLoading();
    
    if (response.success) {
        Swal.fire({
            icon: 'success',
            title: '¡Mensaje Enviado!',
            html: `
                <p>${response.message}</p>
                ${response.ticket_id ? `<p><strong>Número de ticket:</strong> ${response.ticket_id}</p>` : ''}
                <p><small>Te responderemos en un plazo máximo de 24 horas.</small></p>
            `,
            confirmButtonColor: '#2E7D32',
            confirmButtonText: 'Entendido'
        }).then(() => {
            $('#formContacto')[0].reset();
            limpiarValidaciones();
            $('#contadorCaracteres').text('0');
            
            // Enviar evento de conversión (opcional)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'contact_form_submit', {
                    'event_category': 'engagement',
                    'event_label': $('#tipoConsulta').val()
                });
            }
        });
    } else {
        mostrarError('Error al Enviar', response.message || 'Ocurrió un error inesperado');
    }
}

/**
 * Validar formulario de contacto
 */
function validarFormularioContacto() {
    let esValido = true;
    let errores = [];
    
    // Validar campos requeridos
    const camposRequeridos = {
        'nombre_completo': 'Nombre Completo',
        'email': 'Correo Electrónico',
        'tipo_consulta': 'Tipo de Consulta',
        'asunto': 'Asunto',
        'mensaje': 'Mensaje'
    };
    
    Object.keys(camposRequeridos).forEach(function(campo) {
        const elemento = $(`[name="${campo}"]`);
        const valor = elemento.val();
        
        if (!valor || !valor.trim()) {
            elemento.addClass('is-invalid');
            errores.push(camposRequeridos[campo]);
            esValido = false;
        } else {
            elemento.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    // Validar email específicamente
    if ($('#email').val() && !validarFormatoEmail($('#email').val())) {
        $('#email').addClass('is-invalid');
        errores.push('Formato de email inválido');
        esValido = false;
    }
    
    // Validar aceptación de términos
    if (!$('#aceptarTerminos').is(':checked')) {
        $('#aceptarTerminos').addClass('is-invalid');
        errores.push('Debes aceptar los términos y condiciones');
        esValido = false;
    } else {
        $('#aceptarTerminos').removeClass('is-invalid');
    }
    
    // Validar longitud del mensaje
    const mensaje = $('#mensaje').val();
    if (mensaje && mensaje.length < 10) {
        $('#mensaje').addClass('is-invalid');
        errores.push('El mensaje debe tener al menos 10 caracteres');
        esValido = false;
    }
    
    if (!esValido) {
        mostrarErrorValidacion(errores);
    }
    
    return esValido;
}

/**
 * Validar formato de email
 */
function validarEmail(elemento) {
    const email = elemento.val();
    if (email && !validarFormatoEmail(email)) {
        elemento.addClass('is-invalid');
        return false;
    } else if (email) {
        elemento.removeClass('is-invalid').addClass('is-valid');
        return true;
    }
    return true;
}

/**
 * Validar formato de email con regex
 */
function validarFormatoEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Formatear número de teléfono
 */
function formatearTelefono(elemento) {
    let telefono = elemento.val().replace(/\D/g, '');
    
    if (telefono.length >= 10) {
        telefono = telefono.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    } else if (telefono.length >= 6) {
        telefono = telefono.replace(/(\d{3})(\d{3})/, '($1) $2');
    } else if (telefono.length >= 3) {
        telefono = telefono.replace(/(\d{3})/, '($1)');
    }
    
    elemento.val(telefono);
}

/**
 * Actualizar campos según el tipo de consulta
 */
function actualizarCamposSegunTipo(tipo) {
    const asuntoElement = $('#asunto');
    
    switch (tipo) {
        case 'soporte_tecnico':
            asuntoElement.attr('placeholder', 'Ej: Error en el sistema de monitoreo');
            $('#prioridad').val('alta');
            break;
        case 'ventas':
            asuntoElement.attr('placeholder', 'Ej: Información sobre precios y planes');
            $('#prioridad').val('media');
            break;
        case 'demo':
            asuntoElement.attr('placeholder', 'Ej: Solicitud de demo personalizada');
            $('#prioridad').val('media');
            break;
        case 'partnership':
            asuntoElement.attr('placeholder', 'Ej: Propuesta de alianza estratégica');
            $('#prioridad').val('media');
            break;
        default:
            asuntoElement.attr('placeholder', 'Escribe el asunto de tu consulta');
            break;
    }
}

/**
 * Abrir Google Maps
 */
function abrirGoogleMaps() {
    const direccion = 'Av. Agricultura 123, Ciudad Agrícola, Colombia';
    const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(direccion)}`;
    window.open(url, '_blank');
}

/**
 * Mostrar loading durante el envío
 */
function mostrarLoading() {
    Swal.fire({
        title: 'Enviando mensaje...',
        html: 'Por favor espera mientras procesamos tu solicitud.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Ocultar loading
 */
function ocultarLoading() {
    Swal.close();
}

/**
 * Mostrar error de validación
 */
function mostrarErrorValidacion(errores) {
    Swal.fire({
        icon: 'warning',
        title: 'Formulario Incompleto',
        html: `
            <p>Por favor corrige los siguientes errores:</p>
            <ul style="text-align: left; margin: 1rem 0;">
                ${errores.map(error => `<li>${error}</li>`).join('')}
            </ul>
        `,
        confirmButtonColor: '#2E7D32',
        confirmButtonText: 'Entendido'
    });
}

/**
 * Mostrar error general
 */
function mostrarError(titulo, mensaje) {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#2E7D32'
    });
}

/**
 * Limpiar validaciones del formulario
 */
function limpiarValidaciones() {
    $('#formContacto .form-control, #formContacto .form-select, #formContacto .form-check-input')
        .removeClass('is-valid is-invalid');
}

/**
 * Utilidades adicionales
 */

// Formatear fecha para mostrar
function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Capitalizar texto
function capitalizar(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// Detectar si es dispositivo móvil
function esMobile() {
    return window.innerWidth <= 768;
}

// Smooth scroll para enlaces internos
$(document).on('click', 'a[href^="#"]', function(e) {
    e.preventDefault();
    
    const target = $(this.getAttribute('href'));
    if (target.length) {
        $('html, body').animate({
            scrollTop: target.offset().top - 100
        }, 800);
    }
});

// Inicializar tooltips después de la carga
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// Manejar errores globales de JavaScript
window.addEventListener('error', function(e) {
    console.error('Error en contacto.js:', e.error);
});

// Evento cuando la página está completamente cargada
$(window).on('load', function() {
    // Fade in suave de las tarjetas de información
    $('.contact-info-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 200).animate({
            'opacity': '1'
        }, 500).css('transform', 'translateY(0)');
    });
});