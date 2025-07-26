/**
 * Global JS - Sistema de Monitoreo de Cultivos
 * Funciones globales utilizadas en todo el sistema AgroMonitor
 */

// Variables globales del sistema
const SISTEMA = {
    nombre: 'AgroMonitor',
    version: '1.0.0',
    debug: true,
    baseUrl: window.location.origin + '/MonitoreoCultivo/',
    ajaxUrl: window.location.origin + '/MonitoreoCultivo/AJAX/',
    session: {
        timeout: 30 * 60 * 1000, // 30 minutos
        warningTime: 5 * 60 * 1000 // Advertir 5 minutos antes
    }
};

// Configuración de colores del sistema
const COLORES = {
    primary: '#2E7D32',
    secondary: '#4CAF50',
    light: '#81C784',
    earth: '#8D6E63',
    water: '#1976D2',
    sun: '#FFA726',
    success: '#4CAF50',
    error: '#F44336',
    warning: '#FF9800',
    info: '#2196F3'
};

/**
 * =====================================================
 * UTILIDADES GENERALES
 * =====================================================
 */

/**
 * Función de logging personalizada
 */
function logSistema(mensaje, tipo = 'info') {
    if (!SISTEMA.debug) return;
    
    const timestamp = new Date().toLocaleString();
    const estilos = {
        info: 'color: #2196F3; background: #E3F2FD; padding: 2px 5px; border-radius: 3px;',
        warning: 'color: #FF9800; background: #FFF3E0; padding: 2px 5px; border-radius: 3px;',
        error: 'color: #F44336; background: #FFEBEE; padding: 2px 5px; border-radius: 3px;',
        success: 'color: #4CAF50; background: #E8F5E8; padding: 2px 5px; border-radius: 3px;'
    };
    
    console.log(`%c[${SISTEMA.nombre}] ${timestamp} - ${mensaje}`, estilos[tipo] || estilos.info);
}

/**
 * Validador de email universal
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validador de teléfono
 */
function validarTelefono(telefono) {
    const regex = /^[\+]?[1-9][\d]{9,15}$/;
    return regex.test(telefono.replace(/[\s\-\(\)]/g, ''));
}

/**
 * Validador de contraseña fuerte
 */
function validarPasswordFuerte(password) {
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return regex.test(password);
}

/**
 * Limpiar y escapar datos de entrada
 */
function limpiarDatos(datos) {
    if (typeof datos === 'string') {
        return datos.trim().replace(/[<>]/g, '');
    }
    return datos;
}

/**
 * Formatear números con separadores de miles
 */
function formatearNumero(numero, decimales = 0) {
    return Number(numero).toLocaleString('es-ES', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}

/**
 * Formatear fechas
 */
function formatearFecha(fecha, formato = 'completo') {
    const opciones = {
        'completo': { year: 'numeric', month: 'long', day: 'numeric' },
        'corto': { year: 'numeric', month: '2-digit', day: '2-digit' },
        'hora': { hour: '2-digit', minute: '2-digit', second: '2-digit' }
    };
    
    return new Date(fecha).toLocaleDateString('es-ES', opciones[formato]);
}

/**
 * =====================================================
 * MANEJO DE ALERTAS Y NOTIFICACIONES
 * =====================================================
 */

/**
 * Mostrar alerta personalizada del sistema
 */
function mostrarAlerta(mensaje, tipo = 'info', duracion = 5000, contenedor = '#alert-container') {
    const iconos = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };

    const alertaHtml = `
        <div class="alert alert-${tipo} alert-dismissible fade show sistema-alert" role="alert">
            <i class="${iconos[tipo]} me-2"></i>
            <span class="alert-message">${mensaje}</span>
            <button type="button" class="btn-close" onclick="cerrarAlerta(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    const $contenedor = $(contenedor);
    if ($contenedor.length) {
        $contenedor.html(alertaHtml);
        
        if (duracion > 0) {
            setTimeout(() => {
                $contenedor.find('.sistema-alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, duracion);
        }
    } else {
        // Si no existe el contenedor, crear uno temporal
        mostrarNotificacionFlotante(mensaje, tipo, duracion);
    }

    logSistema(`Alerta mostrada: ${mensaje}`, tipo);
}

/**
 * Cerrar alerta manualmente
 */
function cerrarAlerta(elemento) {
    $(elemento).closest('.alert').fadeOut(300, function() {
        $(this).remove();
    });
}

/**
 * Mostrar notificación flotante
 */
function mostrarNotificacionFlotante(mensaje, tipo = 'info', duracion = 3000) {
    // Crear contenedor de notificaciones si no existe
    if (!$('#notificaciones-flotantes').length) {
        $('body').append('<div id="notificaciones-flotantes" class="notificaciones-flotantes"></div>');
    }

    const id = 'notif-' + Date.now();
    const iconos = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };

    const notificacion = `
        <div id="${id}" class="notificacion-flotante notif-${tipo}">
            <div class="notif-icono">
                <i class="${iconos[tipo]}"></i>
            </div>
            <div class="notif-contenido">
                <div class="notif-mensaje">${mensaje}</div>
            </div>
            <button class="notif-cerrar" onclick="cerrarNotificacionFlotante('${id}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    $('#notificaciones-flotantes').prepend(notificacion);
    
    // Animar entrada
    $(`#${id}`).hide().slideDown(300);

    if (duracion > 0) {
        setTimeout(() => {
            cerrarNotificacionFlotante(id);
        }, duracion);
    }
}

/**
 * Cerrar notificación flotante
 */
function cerrarNotificacionFlotante(id) {
    $(`#${id}`).slideUp(300, function() {
        $(this).remove();
    });
}

/**
 * =====================================================
 * MANEJO DE LOADING Y ESTADOS
 * =====================================================
 */

/**
 * Mostrar overlay de carga
 */
function mostrarLoading(mensaje = 'Cargando...', contenedor = 'body') {
    const loadingHtml = `
        <div class="loading-overlay-global" id="loading-global">
            <div class="loading-content-global">
                <div class="loading-spinner-global">
                    <i class="fas fa-seedling spinning-icon"></i>
                </div>
                <p class="loading-text-global">${mensaje}</p>
            </div>
        </div>
    `;

    if (contenedor === 'body') {
        if (!$('#loading-global').length) {
            $('body').append(loadingHtml);
        }
        $('#loading-global').fadeIn(300);
    } else {
        $(contenedor).append(loadingHtml);
    }

    logSistema(`Loading mostrado: ${mensaje}`);
}

/**
 * Ocultar overlay de carga
 */
function ocultarLoading() {
    $('#loading-global').fadeOut(300, function() {
        $(this).remove();
    });
    logSistema('Loading ocultado');
}

/**
 * Cambiar estado de botón
 */
function cambiarEstadoBoton(selector, estado, textoLoading = 'Cargando...') {
    const $boton = $(selector);
    
    switch (estado) {
        case 'loading':
            $boton.prop('disabled', true)
                  .data('texto-original', $boton.html())
                  .html(`<i class="fas fa-spinner fa-spin me-2"></i>${textoLoading}`);
            break;
        case 'normal':
            $boton.prop('disabled', false)
                  .html($boton.data('texto-original') || $boton.html());
            break;
        case 'disabled':
            $boton.prop('disabled', true);
            break;
        case 'enabled':
            $boton.prop('disabled', false);
            break;
    }
}

/**
 * =====================================================
 * MANEJO DE FORMULARIOS Y AJAX
 * =====================================================
 */

/**
 * Realizar petición AJAX segura
 */
function peticionAjax(opciones) {
    const configuracion = {
        type: 'POST',
        dataType: 'json',
        timeout: 30000,
        cache: false,
        beforeSend: function() {
            if (opciones.loading !== false) {
                mostrarLoading(opciones.loadingText || 'Procesando...');
            }
            logSistema(`Iniciando petición AJAX a: ${opciones.url}`);
        },
        success: function(respuesta) {
            logSistema('Petición AJAX exitosa', 'success');
            if (opciones.success) {
                opciones.success(respuesta);
            }
        },
        error: function(xhr, status, error) {
            logSistema(`Error en petición AJAX: ${error}`, 'error');
            
            let mensaje = 'Error de conexión. Verifica tu internet.';
            
            if (xhr.status === 404) {
                mensaje = 'Recurso no encontrado.';
            } else if (xhr.status === 500) {
                mensaje = 'Error interno del servidor.';
            } else if (status === 'timeout') {
                mensaje = 'La petición tardó demasiado tiempo.';
            }

            mostrarAlerta(mensaje, 'error');
            
            if (opciones.error) {
                opciones.error(xhr, status, error);
            }
        },
        complete: function() {
            if (opciones.loading !== false) {
                ocultarLoading();
            }
            logSistema('Petición AJAX completada');
            if (opciones.complete) {
                opciones.complete();
            }
        },
        ...opciones
    };

    return $.ajax(configuracion);
}

/**
 * Serializar formulario a objeto
 */
function formularioAObjeto(formulario) {
    const array = $(formulario).serializeArray();
    const objeto = {};
    
    $.each(array, function(i, campo) {
        if (objeto[campo.name]) {
            if (!objeto[campo.name].push) {
                objeto[campo.name] = [objeto[campo.name]];
            }
            objeto[campo.name].push(campo.value || '');
        } else {
            objeto[campo.name] = campo.value || '';
        }
    });
    
    return objeto;
}

/**
 * Validar formulario
 */
function validarFormulario(formulario) {
    let esValido = true;
    const $formulario = $(formulario);
    
    // Limpiar validaciones anteriores
    $formulario.find('.is-invalid').removeClass('is-invalid');
    $formulario.find('.invalid-feedback').text('');
    
    // Validar campos requeridos
    $formulario.find('[required]').each(function() {
        const $campo = $(this);
        const valor = $campo.val().trim();
        
        if (!valor) {
            marcarCampoInvalido($campo, 'Este campo es requerido');
            esValido = false;
        }
    });
    
    // Validar emails
    $formulario.find('input[type="email"]').each(function() {
        const $campo = $(this);
        const valor = $campo.val().trim();
        
        if (valor && !validarEmail(valor)) {
            marcarCampoInvalido($campo, 'Formato de email inválido');
            esValido = false;
        }
    });
    
    return esValido;
}

/**
 * Marcar campo como inválido
 */
function marcarCampoInvalido(campo, mensaje) {
    const $campo = $(campo);
    $campo.addClass('is-invalid');
    $campo.siblings('.invalid-feedback').text(mensaje);
}

/**
 * Marcar campo como válido
 */
function marcarCampoValido(campo) {
    const $campo = $(campo);
    $campo.removeClass('is-invalid').addClass('is-valid');
    $campo.siblings('.invalid-feedback').text('');
}

/**
 * =====================================================
 * MANEJO DE SESIONES Y SEGURIDAD
 * =====================================================
 */

/**
 * Verificar si el usuario está autenticado
 */
function usuarioAutenticado() {
    return typeof window.usuarioLogueado !== 'undefined' && window.usuarioLogueado === true;
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    return {
        id: window.userId || null,
        nombre: window.userName || '',
        email: window.userEmail || '',
        rol: window.userRole || ''
    };
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    peticionAjax({
        url: SISTEMA.ajaxUrl + 'logout_ajax.php',
        success: function(respuesta) {
            if (respuesta.success) {
                window.location.href = 'login.php';
            } else {
                mostrarAlerta('Error al cerrar sesión', 'error');
            }
        }
    });
}

/**
 * Renovar sesión
 */
function renovarSesion() {
    peticionAjax({
        url: SISTEMA.ajaxUrl + 'renovar_sesion_ajax.php',
        loading: false,
        success: function(respuesta) {
            if (respuesta.success) {
                logSistema('Sesión renovada exitosamente', 'success');
            }
        },
        error: function() {
            logSistema('Error al renovar sesión', 'warning');
        }
    });
}

/**
 * =====================================================
 * UTILIDADES DE INTERFAZ
 * =====================================================
 */

/**
 * Confirmar acción con modal
 */
function confirmarAccion(mensaje, callback, titulo = '¿Estás seguro?') {
    if (confirm(`${titulo}\n\n${mensaje}`)) {
        callback();
    }
}

/**
 * Copiar texto al portapapeles
 */
function copiarAlPortapapeles(texto) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(() => {
            mostrarNotificacionFlotante('Texto copiado al portapapeles', 'success');
        }).catch(() => {
            mostrarNotificacionFlotante('Error al copiar texto', 'error');
        });
    } else {
        // Fallback para navegadores antiguos
        const textArea = document.createElement('textarea');
        textArea.value = texto;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            mostrarNotificacionFlotante('Texto copiado al portapapeles', 'success');
        } catch (err) {
            mostrarNotificacionFlotante('Error al copiar texto', 'error');
        }
        document.body.removeChild(textArea);
    }
}

/**
 * Scroll suave hacia elemento
 */
function scrollSuaveHacia(elemento, offset = 0) {
    const $elemento = $(elemento);
    if ($elemento.length) {
        $('html, body').animate({
            scrollTop: $elemento.offset().top + offset
        }, 500);
    }
}

/**
 * =====================================================
 * INICIALIZACIÓN Y EVENTOS GLOBALES
 * =====================================================
 */

/**
 * Inicializar funcionalidades globales
 */
function inicializarSistema() {
    logSistema('Inicializando sistema AgroMonitor...', 'info');
    
    // Configurar AJAX globalmente
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    
    // Configurar manejo de errores globales
    window.onerror = function(mensaje, archivo, linea, columna, error) {
        logSistema(`Error JavaScript: ${mensaje} en ${archivo}:${linea}`, 'error');
        return false;
    };
    
    // Inicializar verificación de sesión
    if (usuarioAutenticado()) {
        inicializarManejadorSesion();
    }
    
    // Configurar tooltips globales
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Configurar year dinámico
    $('#current-year').text(new Date().getFullYear());
    
    logSistema('Sistema inicializado correctamente', 'success');
}

/**
 * Inicializar manejador de sesión
 */
function inicializarManejadorSesion() {
    // Renovar sesión cada 10 minutos
    setInterval(renovarSesion, 10 * 60 * 1000);
    
    // Advertir antes de que expire la sesión
    setTimeout(() => {
        mostrarAlerta('Tu sesión expirará pronto. Guarda tu trabajo.', 'warning', 10000);
    }, SISTEMA.session.timeout - SISTEMA.session.warningTime);
}

/**
 * Agregar estilos CSS globales
 */
function agregarEstilosGlobales() {
    const estilos = `
        <style>
        /* Estilos para notificaciones flotantes */
        .notificaciones-flotantes {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }
        
        .notificacion-flotante {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            border-left: 4px solid;
            animation: slideInRight 0.3s ease-out;
        }
        
        .notif-success { border-left-color: ${COLORES.success}; }
        .notif-error { border-left-color: ${COLORES.error}; }
        .notif-warning { border-left-color: ${COLORES.warning}; }
        .notif-info { border-left-color: ${COLORES.info}; }
        
        .notif-icono {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        
        .notif-success .notif-icono { color: ${COLORES.success}; }
        .notif-error .notif-icono { color: ${COLORES.error}; }
        .notif-warning .notif-icono { color: ${COLORES.warning}; }
        .notif-info .notif-icono { color: ${COLORES.info}; }
        
        .notif-contenido {
            flex: 1;
        }
        
        .notif-mensaje {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .notif-cerrar {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }
        
        .notif-cerrar:hover {
            color: #333;
        }
        
        /* Loading global */
        .loading-overlay-global {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(46, 125, 50, 0.9);
            backdrop-filter: blur(5px);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .loading-content-global {
            text-align: center;
            color: white;
        }
        
        .loading-spinner-global {
            margin-bottom: 15px;
        }
        
        .spinning-icon {
            font-size: 2.5rem;
            color: ${COLORES.sun};
            animation: spin 2s linear infinite;
        }
        
        .loading-text-global {
            font-size: 1.1rem;
            margin: 0;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Alertas del sistema */
        .sistema-alert {
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .sistema-alert .btn-close {
            background: none;
            border: none;
            opacity: 0.7;
            padding: 0;
            color: inherit;
        }
        
        .sistema-alert .btn-close:hover {
            opacity: 1;
        }
        </style>
    `;
    
    $('head').append(estilos);
}

/**
 * =====================================================
 * INICIALIZACIÓN AUTOMÁTICA
 * =====================================================
 */

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    agregarEstilosGlobales();
    inicializarSistema();
    
    // Eventos globales
    $(document).on('click', '[data-action="logout"]', function(e) {
        e.preventDefault();
        confirmarAccion('¿Deseas cerrar tu sesión?', cerrarSesion, 'Cerrar Sesión');
    });
    
    // Manejo de formularios con clase 'ajax-form'
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        
        const $formulario = $(this);
        const url = $formulario.attr('action') || $formulario.data('url');
        
        if (!validarFormulario($formulario)) {
            mostrarAlerta('Por favor corrige los errores en el formulario', 'warning');
            return;
        }
        
        peticionAjax({
            url: url,
            data: $formulario.serialize(),
            success: function(respuesta) {
                if (respuesta.success) {
                    mostrarAlerta(respuesta.message, 'success');
                    if (respuesta.redirect) {
                        setTimeout(() => {
                            window.location.href = respuesta.redirect;
                        }, 1500);
                    }
                } else {
                    mostrarAlerta(respuesta.message, 'error');
                }
            }
        });
    });
});

// Exportar funciones para uso global
window.AgroMonitor = {
    log: logSistema,
    alerta: mostrarAlerta,
    loading: {
        mostrar: mostrarLoading,
        ocultar: ocultarLoading
    },
    ajax: peticionAjax,
    validar: {
        email: validarEmail,
        telefono: validarTelefono,
        password: validarPasswordFuerte,
        formulario: validarFormulario
    },
    usuario: {
        autenticado: usuarioAutenticado,
        actual: obtenerUsuarioActual,
        cerrarSesion: cerrarSesion
    },
    utils: {
        formatearNumero: formatearNumero,
        formatearFecha: formatearFecha,
        copiar: copiarAlPortapapeles,
        scroll: scrollSuaveHacia
    }
};

logSistema('AgroMonitor Global JS cargado exitosamente', 'success');