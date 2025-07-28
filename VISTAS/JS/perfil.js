/**
 * JavaScript para el perfil de usuario
 */

$(document).ready(function() {
    
    // Configuración inicial
    initializeProfile();
    
    function initializeProfile() {
        // Inicializar tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Animaciones de entrada
        animateElements();
        
        // Validaciones en tiempo real
        setupRealTimeValidation();
    }

    // Animaciones de entrada
    function animateElements() {
        $('.profile-card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
    }

    // Función para mostrar notificaciones
    function showNotification(message, type = 'success', duration = 4000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Función para mostrar loading en tarjetas
    function showCardLoading(cardSelector) {
        const card = $(cardSelector);
        if (card.find('.loading-overlay').length === 0) {
            card.css('position', 'relative').append(`
                <div class="loading-overlay">
                    <div class="loading-spinner"></div>
                </div>
            `);
        }
    }

    function hideCardLoading(cardSelector) {
        $(cardSelector).find('.loading-overlay').remove();
    }

    // Validación de fortaleza de contraseña
    function validatePasswordStrength(password) {
        let strength = 0;
        const checks = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
        };

        Object.values(checks).forEach(check => {
            if (check) strength++;
        });

        return { strength, checks };
    }

    // Configurar validaciones en tiempo real
    function setupRealTimeValidation() {
        // Validación de contraseña en tiempo real
        $('#passwordNueva').on('input', function() {
            const password = $(this).val();
            const strengthBar = $('#passwordStrength');
            const helpText = $('#passwordHelp');
            
            if (password.length === 0) {
                strengthBar.removeClass('weak medium strong');
                helpText.text('Mínimo 8 caracteres, una mayúscula, una minúscula y un número')
                        .removeClass('text-danger text-warning text-success');
                return;
            }
            
            const validation = validatePasswordStrength(password);
            
            strengthBar.removeClass('weak medium strong');
            
            if (validation.strength < 3) {
                strengthBar.addClass('weak');
                helpText.text('Contraseña débil')
                        .removeClass('text-warning text-success')
                        .addClass('text-danger');
            } else if (validation.strength < 4) {
                strengthBar.addClass('medium');
                helpText.text('Contraseña moderada')
                        .removeClass('text-danger text-success')
                        .addClass('text-warning');
            } else {
                strengthBar.addClass('strong');
                helpText.text('Contraseña fuerte')
                        .removeClass('text-danger text-warning')
                        .addClass('text-success');
            }
        });

        // Validación de confirmación de contraseña
        $('#passwordConfirmar').on('input', function() {
            const password = $('#passwordNueva').val();
            const confirmPassword = $(this).val();
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            } else {
                $(this).removeClass('is-valid is-invalid');
            }
        });

        // Validación de campos requeridos
        $('#formActualizarPerfil input[required]').on('blur', function() {
            const value = $(this).val().trim();
            if (value === '') {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
            }
        });

        // Limpiar validaciones al escribir
        $('input').on('input', function() {
            if ($(this).hasClass('is-invalid') && $(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
            }
        });
    }

    // Actualizar perfil
    $('#formActualizarPerfil').on('submit', function(e) {
        e.preventDefault();
        
        // Validar campos requeridos
        let isValid = true;
        $(this).find('input[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            showNotification('Por favor completa todos los campos requeridos', 'error');
            return;
        }

        const btnSubmit = $(this).find('button[type="submit"]');
        const originalText = btnSubmit.html();
        
        btnSubmit.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);
        showCardLoading('.profile-card:first');
        
        $.ajax({
            url: '../AJAX/actualizar_perfil.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    
                    // Actualizar información en la cabecera
                    const nombre = $('#nombre').val();
                    const apellido = $('#apellido').val();
                    const nombreCompleto = `${nombre} ${apellido}`;
                    
                    $('.profile-header h2').text(nombreCompleto);
                    
                    // Marcar campos como válidos
                    $('#formActualizarPerfil input').removeClass('is-invalid').addClass('is-valid');
                    
                    // Animar éxito
                    $('.profile-card:first').addClass('border-success');
                    setTimeout(() => {
                        $('.profile-card:first').removeClass('border-success');
                    }, 2000);
                    
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showNotification('Error en la comunicación con el servidor', 'error');
            },
            complete: function() {
                btnSubmit.html(originalText).prop('disabled', false);
                hideCardLoading('.profile-card:first');
            }
        });
    });

    // Cambiar contraseña
    $('#formCambiarPassword').on('submit', function(e) {
        e.preventDefault();
        
        const passwordActual = $('#passwordActual').val();
        const passwordNueva = $('#passwordNueva').val();
        const passwordConfirmar = $('#passwordConfirmar').val();
        
        // Validaciones
        if (!passwordActual || !passwordNueva || !passwordConfirmar) {
            showNotification('Todos los campos de contraseña son requeridos', 'error');
            return;
        }

        if (passwordNueva !== passwordConfirmar) {
            showNotification('Las contraseñas nuevas no coinciden', 'error');
            $('#passwordConfirmar').addClass('is-invalid');
            return;
        }

        const validation = validatePasswordStrength(passwordNueva);
        if (validation.strength < 3) {
            showNotification('La contraseña nueva no cumple los requisitos de seguridad', 'error');
            $('#passwordNueva').addClass('is-invalid');
            return;
        }
        
        const btnSubmit = $(this).find('button[type="submit"]');
        const originalText = btnSubmit.html();
        
        btnSubmit.html('<i class="fas fa-spinner fa-spin me-2"></i>Cambiando...').prop('disabled', true);
        showCardLoading('.profile-card:last');
        
        $.ajax({
            url: '../AJAX/cambiar_password.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    
                    // Limpiar formulario
                    $('#formCambiarPassword')[0].reset();
                    $('#passwordStrength').removeClass('weak medium strong');
                    $('#passwordHelp').text('Mínimo 8 caracteres, una mayúscula, una minúscula y un número')
                                    .removeClass('text-danger text-warning text-success');
                    
                    // Remover clases de validación
                    $('#formCambiarPassword input').removeClass('is-valid is-invalid');
                    
                    // Animar éxito
                    $('.profile-card').last().addClass('border-success');
                    setTimeout(() => {
                        $('.profile-card').last().removeClass('border-success');
                    }, 2000);
                    
                } else {
                    showNotification(response.message, 'error');
                    
                    if (response.message.includes('actual')) {
                        $('#passwordActual').addClass('is-invalid');
                    }
                }
            },
            error: function() {
                showNotification('Error en la comunicación con el servidor', 'error');
            },
            complete: function() {
                btnSubmit.html(originalText).prop('disabled', false);
                hideCardLoading('.profile-card:last');
            }
        });
    });

    // Subir avatar con previsualización mejorada
    $('#avatarUpload').on('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            // Validar tipo de archivo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('Solo se permiten archivos JPG, PNG y GIF', 'error');
                $(this).val('');
                return;
            }
            
            // Validar tamaño (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('El archivo es demasiado grande. Máximo 2MB', 'error');
                $(this).val('');
                return;
            }
            
            // Previsualizar imagen con animación
            const reader = new FileReader();
            reader.onload = function(e) {
                $('.profile-avatar').fadeOut(200, function() {
                    $(this).attr('src', e.target.result).fadeIn(200);
                });
            };
            reader.readAsDataURL(file);
            
            // Subir archivo
            const formData = new FormData();
            formData.append('avatar', file);
            
            // Mostrar progreso
            showCardLoading('.profile-card:eq(1)');
            
            $.ajax({
                url: '../AJAX/subir_avatar.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification('Foto de perfil actualizada exitosamente', 'success');
                        
                        // Actualizar avatar en el navbar si existe
                        if ($('.navbar .user-avatar').length > 0) {
                            $('.navbar .user-avatar').attr('src', response.ruta_avatar);
                        }
                        
                        // Efecto de éxito
                        $('.profile-avatar').addClass('border-success');
                        setTimeout(() => {
                            $('.profile-avatar').removeClass('border-success');
                        }, 2000);
                        
                    } else {
                        showNotification(response.message, 'error');
                        
                        // Restaurar imagen anterior en caso de error
                        $('.profile-avatar').attr('src', '../PUBLIC/Img/user.png');
                    }
                },
                error: function() {
                    showNotification('Error subiendo la imagen', 'error');
                    $('.profile-avatar').attr('src', '../PUBLIC/Img/user.png');
                },
                complete: function() {
                    hideCardLoading('.profile-card');
                    // Limpiar input file
                    $('#avatarUpload').val('');
                }
            });
        }
    });

    // Efectos visuales mejorados
    
    // Hover effects para tarjetas
    $('.profile-card').hover(
        function() {
            $(this).css('transform', 'translateY(-8px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );

    // Animación para campos de formulario
    $('.form-floating input').on('focus', function() {
        $(this).parent().addClass('focused');
    }).on('blur', function() {
        $(this).parent().removeClass('focused');
    });

    // Contador de caracteres para campos de texto
    $('input[type="text"], input[type="email"], input[type="tel"]').on('input', function() {
        const maxLength = $(this).attr('maxlength');
        if (maxLength) {
            const currentLength = $(this).val().length;
            const remaining = maxLength - currentLength;
            
            let counter = $(this).siblings('.char-counter');
            if (counter.length === 0) {
                counter = $('<small class="char-counter text-muted"></small>');
                $(this).parent().append(counter);
            }
            
            counter.text(`${currentLength}/${maxLength}`);
            
            if (remaining < 10) {
                counter.addClass('text-warning').removeClass('text-muted');
            } else {
                counter.addClass('text-muted').removeClass('text-warning');
            }
        }
    });

    // Función para actualizar estadísticas (si aplica)
    function updateUserStats() {
        $.ajax({
            url: '../AJAX/obtener_estadisticas_usuario.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const stats = response.estadisticas;
                    const rol = response.rol;
                    
                    // Actualizar estadísticas según el rol
                    if (rol === 'administrador') {
                        updateStatBadges([
                            stats.total_usuarios || 0,
                            stats.total_fincas || 0,
                            stats.total_monitoreos || 0,
                            stats.alertas_pendientes || 0
                        ]);
                    } else if (rol === 'agricultor') {
                        updateStatBadges([
                            stats.fincas_registradas || 0,
                            stats.siembras_activas || 0,
                            stats.monitoreos_mes || 0,
                            stats.cosechas_registradas || 0
                        ]);
                    } else if (rol === 'supervisor') {
                        updateStatBadges([
                            stats.agricultores_supervisados || 0,
                            stats.fincas_asignadas || 0,
                            stats.inspecciones_realizadas || 0,
                            stats.reportes_generados || 0
                        ]);
                    }
                }
            },
            error: function() {
                console.log('Error cargando estadísticas del usuario');
            }
        });
    }
    
    // Función auxiliar para actualizar los badges
    function updateStatBadges(valores) {
        $('.card:has(.fa-chart-bar, .fa-binoculars, .fa-crown) .badge').each(function(index) {
            if (valores[index] !== undefined) {
                $(this).text(valores[index]);
                
                // Agregar animación de actualización
                $(this).addClass('badge-updated');
                setTimeout(() => {
                    $(this).removeClass('badge-updated');
                }, 1000);
            }
        });
    }

    // Actualizar estadísticas al cargar
    updateUserStats();

    // Funcionalidad de borrador removida por solicitud del usuario

    // Shortcuts de teclado
    $(document).on('keydown', function(e) {
        // Ctrl + S para guardar perfil
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            $('#formActualizarPerfil').submit();
        }
        
        // Escape para limpiar formularios
        if (e.keyCode === 27) {
            $('.form-control').blur();
        }
    });

    // Ver historial completo
    $('#verHistorialCompleto').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Historial de Actividad',
            html: `
                <div class="text-start">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-primary rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-sign-in-alt text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Inicio de Sesión</h6>
                            <small class="text-muted">Hoy - ${new Date().toLocaleTimeString()}</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-user-edit text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Perfil Actualizado</h6>
                            <small class="text-muted">Hace 2 días</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-info rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-key text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Contraseña Cambiada</h6>
                            <small class="text-muted">Hace 1 semana</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-warning rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-image text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Foto de Perfil Actualizada</h6>
                            <small class="text-muted">Hace 2 semanas</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <div class="bg-secondary rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Cuenta Creada</h6>
                            <small class="text-muted">Hace 1 mes</small>
                        </div>
                    </div>
                </div>
            `,
            width: 600,
            showConfirmButton: false,
            showCloseButton: true,
            customClass: {
                popup: 'swal-wide'
            }
        });
    });

    // Mensaje de salida si hay cambios sin guardar
    let hasUnsavedChanges = false;
    
    $('#formActualizarPerfil input, #formCambiarPassword input').on('input', function() {
        hasUnsavedChanges = true;
    });

    $('#formActualizarPerfil, #formCambiarPassword').on('submit', function() {
        hasUnsavedChanges = false;
    });

    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
        }
    });
});

// Función para exportar perfil (opcional)
function exportProfile() {
    const userData = {
        nombre: $('#nombre').val(),
        apellido: $('#apellido').val(),
        email: $('#email').val(),
        telefono: $('#telefono').val(),
        fechaExportacion: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(userData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'mi_perfil.json';
    link.click();
    
    URL.revokeObjectURL(url);
}

// Función para imprimir perfil (opcional)
function printProfile() {
    window.print();
}