$(document).ready(function() {
    // Variables globales para notificaciones
    let notificacionesActualizando = false;
    let intervalId = null;
    
    // Inicializar notificaciones si el usuario está logueado
    if (window.usuarioLogueado) {
        inicializarNotificaciones();
    }
    
    function inicializarNotificaciones() {
        cargarNotificaciones();
        
        // Actualizar notificaciones cada 30 segundos
        intervalId = setInterval(cargarNotificaciones, 30000);
        
        // Event listeners
        $('#navbarNotifications').on('click', function() {
            cargarNotificaciones();
        });
        
        $('#mark-all-read').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            marcarTodasComoLeidas();
        });
        
        // Marcar como leída al hacer click en una notificación
        $(document).on('click', '.notification-item[data-id]', function() {
            const alertaId = $(this).data('id');
            marcarComoLeida(alertaId);
        });
    }
    
    function cargarNotificaciones() {
        if (notificacionesActualizando) return;
        
        notificacionesActualizando = true;
        
        $.ajax({
            url: '../AJAX/notificaciones_ajax.php',
            type: 'GET',
            data: { accion: 'obtener', limite: 5 },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    actualizarContadorNotificaciones(response.total_no_vistas);
                    mostrarNotificaciones(response.notificaciones);
                } else {
                    console.error('Error al cargar notificaciones:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar notificaciones:', error);
                mostrarErrorNotificaciones();
            },
            complete: function() {
                notificacionesActualizando = false;
            }
        });
    }
    
    function actualizarContadorNotificaciones(total) {
        const badge = $('#notification-count');
        const icon = $('.notification-icon');
        
        // Validar que total sea un número
        total = parseInt(total) || 0;
        
        if (total > 0) {
            // Mostrar el número correcto, máximo 99+
            const texto = total > 99 ? '99+' : total.toString();
            badge.text(texto);
            badge.removeClass('d-none');
            
            // Añadir estilo de notificación activa
            icon.addClass('text-warning');
            
            // Animación sutil cuando hay notificaciones nuevas
            badge.addClass('animate__animated animate__pulse');
            setTimeout(() => {
                badge.removeClass('animate__animated animate__pulse');
            }, 1000);
        } else {
            badge.addClass('d-none');
            icon.removeClass('text-warning');
        }
        
        // Actualizar título del elemento para accesibilidad
        $('#navbarNotifications').attr('title', 
            total > 0 ? `${total} notificación${total > 1 ? 'es' : ''} sin leer` : 'Sin notificaciones'
        );
    }
    
    function mostrarNotificaciones(notificaciones) {
        const container = $('#notifications-container');
        
        if (notificaciones.length === 0) {
            container.html(`
                <li class="text-center py-3 text-muted">
                    <i class="fas fa-inbox me-2"></i>
                    No tienes notificaciones
                </li>
            `);
            return;
        }
        
        let html = '';
        notificaciones.forEach(function(notif) {
            const estadoClass = notif.estado === 'pendiente' ? 'notification-unread' : '';
            
            html += `
                <li class="notification-item ${estadoClass}" data-id="${notif.id}">
                    <a class="dropdown-item" href="#" data-alerta-id="${notif.id}">
                        <div class="notification-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <small class="${notif.clase_prioridad}">
                                    <i class="${notif.icono} me-1"></i>
                                    ${capitalize(notif.tipo)}
                                </small>
                                <span class="badge bg-light text-dark">${notif.prioridad}</span>
                            </div>
                            <p class="mb-1 fw-bold">${notif.titulo}</p>
                            <p class="mb-1 small text-muted">${truncateText(notif.mensaje, 80)}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">${notif.tiempo}</small>
                                ${notif.cultivo !== 'General' ? `<small class="text-info"><i class="fas fa-seedling me-1"></i>${notif.cultivo}</small>` : ''}
                            </div>
                        </div>
                    </a>
                </li>
            `;
        });
        
        container.html(html);
    }
    
    function mostrarErrorNotificaciones() {
        $('#notifications-container').html(`
            <li class="text-center py-3 text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>Error al cargar notificaciones</div>
                <small class="text-muted mt-1 d-block">Reintentando en 5 segundos...</small>
            </li>
        `);
        
        // Reintentar después de 5 segundos
        setTimeout(() => {
            if (!notificacionesActualizando) {
                cargarNotificaciones();
            }
        }, 5000);
    }
    
    function marcarComoLeida(alertaId) {
        $.ajax({
            url: '../AJAX/notificaciones_ajax.php',
            type: 'POST',
            data: {
                accion: 'marcar_vista',
                alerta_id: alertaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar la interfaz
                    $(`.notification-item[data-id="${alertaId}"]`).removeClass('notification-unread');
                    
                    // Actualizar contador
                    setTimeout(cargarNotificaciones, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al marcar notificación como leída:', error);
            }
        });
    }
    
    function marcarTodasComoLeidas() {
        $.ajax({
            url: '../AJAX/notificaciones_ajax.php',
            type: 'POST',
            data: { accion: 'marcar_todas_vistas' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Actualizar la interfaz
                    $('.notification-item').removeClass('notification-unread');
                    actualizarContadorNotificaciones(0);
                    
                    // Mostrar mensaje de éxito
                    mostrarToast('Todas las notificaciones han sido marcadas como leídas', 'success');
                } else {
                    mostrarToast('Error al marcar notificaciones como leídas', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al marcar todas las notificaciones como leídas:', error);
                mostrarToast('Error de conexión', 'error');
            }
        });
    }
    
    // Funciones utilitarias
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    function mostrarToast(mensaje, tipo = 'info') {
        // Si existe SweetAlert2, usarlo
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: tipo === 'success' ? 'success' : tipo === 'error' ? 'error' : 'info',
                title: mensaje
            });
        } else {
            // Fallback a console.log si no está disponible SweetAlert2
            console.log(`${tipo.toUpperCase()}: ${mensaje}`);
        }
    }
    
    // Limpiar interval al salir de la página
    $(window).on('beforeunload', function() {
        if (intervalId) {
            clearInterval(intervalId);
        }
    });
});