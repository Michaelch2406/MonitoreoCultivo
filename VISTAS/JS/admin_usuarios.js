/**
 * JavaScript para la gestión de usuarios del administrador
 */

$(document).ready(function() {
    // Configuración de DataTables en español
    const dataTableConfig = {
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        responsive: true,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [7] } // Columna de acciones no ordenable
        ],
        pageLength: 25,
        pagingType: "full_numbers",
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        drawCallback: function() {
            // Reinicializar tooltips después de cada redibujado
            initializeTooltips();
        }
    };

    // Inicializar DataTable
    const tabla = $('#tablaUsuarios').DataTable(dataTableConfig);

    // Inicializar tooltips
    function initializeTooltips() {
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });
    }

    initializeTooltips();

    // Función para aplicar filtros
    function aplicarFiltros() {
        const rol = $('#filtroRol').val();
        const estado = $('#filtroEstado').val();

        // Mostrar/ocultar filas según filtros
        tabla.rows().every(function() {
            const row = $(this.node());
            const rowRol = row.data('rol');
            const rowEstado = row.data('estado');
            let mostrar = true;

            if (rol && rowRol !== rol) {
                mostrar = false;
            }

            if (estado && rowEstado !== estado) {
                mostrar = false;
            }

            if (mostrar) {
                row.show();
            } else {
                row.hide();
            }
        });

        tabla.draw();
    }

    // Event listeners para filtros
    $('#filtroRol, #filtroEstado').on('change', aplicarFiltros);

    $('#btnLimpiarFiltros').on('click', function() {
        $('#filtroRol, #filtroEstado').val('');
        aplicarFiltros();
        showNotification('Filtros limpiados', 'info');
    });

    // Función para mostrar notificaciones
    function showNotification(message, type = 'success', duration = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: duration,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Función para validar contraseña
    function validarPassword(password) {
        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
        return regex.test(password);
    }

    // Función para limpiar formulario
    function limpiarFormulario(formId) {
        $(formId)[0].reset();
        $(formId).find('.is-invalid').removeClass('is-invalid');
        $(formId).find('.invalid-feedback').text('');
    }

    // Crear nuevo usuario
    $('#formNuevoUsuario').on('submit', function(e) {
        e.preventDefault();
        
        const password = $('#nuevoPassword').val();
        const confirmar = $('#confirmarPassword').val();
        
        // Validar contraseñas
        if (password !== confirmar) {
            $('#confirmarPassword').addClass('is-invalid')
                .next('.invalid-feedback').text('Las contraseñas no coinciden');
            return;
        }

        if (!validarPassword(password)) {
            $('#nuevoPassword').addClass('is-invalid')
                .next('.invalid-feedback').text('La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número');
            return;
        }
        
        // Mostrar loading
        const btnSubmit = $(this).find('button[type="submit"]');
        const originalText = btnSubmit.html();
        btnSubmit.html('<i class="fas fa-spinner fa-spin me-2"></i>Creando...').prop('disabled', true);
        
        $.ajax({
            url: '../AJAX/admin/crear_usuario.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalNuevoUsuario').modal('hide');
                    limpiarFormulario('#formNuevoUsuario');
                    showNotification(response.message, 'success');
                    
                    // Recargar página después de un breve delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#667eea'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de comunicación',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#667eea'
                });
            },
            complete: function() {
                btnSubmit.html(originalText).prop('disabled', false);
            }
        });
    });

    // Editar usuario
    $(document).on('click', '.btn-editar', function() {
        const userId = $(this).data('id');
        
        // Mostrar loading
        $(this).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: '../AJAX/admin/obtener_usuario.php',
            method: 'POST',
            data: { usuario_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const user = response.user;
                    $('#editarUsuarioId').val(user.usuario_id);
                    $('#editarNombre').val(user.nombre);
                    $('#editarApellido').val(user.apellido);
                    $('#editarEmail').val(user.email);
                    $('#editarTelefono').val(user.telefono || '');
                    $('#editarRol').val(user.rol);
                    $('#editarEstado').val(user.estado);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#667eea'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de comunicación',
                    text: 'No se pudo obtener la información del usuario',
                    confirmButtonColor: '#667eea'
                });
            },
            complete: function() {
                $('.btn-editar').html('<i class="fas fa-edit"></i>').prop('disabled', false);
            }
        });
    });

    $('#formEditarUsuario').on('submit', function(e) {
        e.preventDefault();
        
        const btnSubmit = $(this).find('button[type="submit"]');
        const originalText = btnSubmit.html();
        btnSubmit.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);
        
        $.ajax({
            url: '../AJAX/admin/editar_usuario.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalEditarUsuario').modal('hide');
                    showNotification(response.message, 'success');
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#667eea'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de comunicación',
                    text: 'No se pudo actualizar el usuario',
                    confirmButtonColor: '#667eea'
                });
            },
            complete: function() {
                btnSubmit.html(originalText).prop('disabled', false);
            }
        });
    });

    // Cambiar estado de usuario
    $(document).on('click', '.btn-cambiar-estado', function() {
        const userId = $(this).data('id');
        const nuevoEstado = $(this).data('estado');
        const textoEstado = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
        const btn = $(this);
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas ${textoEstado} este usuario?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                
                $.ajax({
                    url: '../AJAX/admin/cambiar_estado_usuario.php',
                    method: 'POST',
                    data: { 
                        usuario_id: userId,
                        nuevo_estado: nuevoEstado
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.message, 'success');
                            
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#667eea'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de comunicación',
                            text: 'No se pudo cambiar el estado del usuario',
                            confirmButtonColor: '#667eea'
                        });
                    },
                    complete: function() {
                        // Restaurar botón original
                        const iconClass = nuevoEstado === 'activo' ? 'fas fa-user-slash' : 'fas fa-user-check';
                        btn.html(`<i class="${iconClass}"></i>`).prop('disabled', false);
                    }
                });
            }
        });
    });

    // Resetear contraseña
    $(document).on('click', '.btn-resetear-password', function() {
        const userId = $(this).data('id');
        const btn = $(this);
        
        Swal.fire({
            title: 'Resetear Contraseña',
            text: 'Ingresa la nueva contraseña para el usuario:',
            input: 'password',
            inputAttributes: {
                autocapitalize: 'off',
                placeholder: 'Nueva contraseña',
                class: 'form-control'
            },
            showCancelButton: true,
            confirmButtonText: 'Resetear',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            inputValidator: (value) => {
                if (!value) {
                    return 'La contraseña es requerida';
                }
                if (!validarPassword(value)) {
                    return 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número';
                }
            },
            preConfirm: (password) => {
                return new Promise((resolve) => {
                    btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                    
                    $.ajax({
                        url: '../AJAX/admin/resetear_password.php',
                        method: 'POST',
                        data: { 
                            usuario_id: userId,
                            nueva_password: password
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                resolve(response);
                            } else {
                                Swal.showValidationMessage(response.message);
                                resolve(false);
                            }
                        },
                        error: function() {
                            Swal.showValidationMessage('Error de comunicación con el servidor');
                            resolve(false);
                        },
                        complete: function() {
                            btn.html('<i class="fas fa-key"></i>').prop('disabled', false);
                        }
                    });
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                showNotification(result.value.message, 'success');
            }
        });
    });

    // Eliminar usuario
    $(document).on('click', '.btn-eliminar', function() {
        const userId = $(this).data('id');
        const nombre = $(this).data('nombre');
        const btn = $(this);
        
        Swal.fire({
            title: '¿Estás seguro?',
            html: `¿Deseas eliminar permanentemente a <strong>${nombre}</strong>?<br><br><small class="text-danger">Esta acción no se puede deshacer.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
                
                $.ajax({
                    url: '../AJAX/admin/eliminar_usuario.php',
                    method: 'POST',
                    data: { usuario_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: response.message,
                                confirmButtonColor: '#667eea'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#667eea'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de comunicación',
                            text: 'No se pudo eliminar el usuario',
                            confirmButtonColor: '#667eea'
                        });
                    },
                    complete: function() {
                        btn.html('<i class="fas fa-trash"></i>').prop('disabled', false);
                    }
                });
            }
        });
    });

    // Limpiar formularios al cerrar modales
    $('#modalNuevoUsuario').on('hidden.bs.modal', function() {
        limpiarFormulario('#formNuevoUsuario');
    });

    $('#modalEditarUsuario').on('hidden.bs.modal', function() {
        limpiarFormulario('#formEditarUsuario');
    });

    // Validaciones en tiempo real
    $('#nuevoPassword, #confirmarPassword').on('input', function() {
        const password = $('#nuevoPassword').val();
        const confirmar = $('#confirmarPassword').val();
        
        if (password && !validarPassword(password)) {
            $('#nuevoPassword').addClass('is-invalid')
                .next('.invalid-feedback').text('Contraseña no cumple los requisitos');
        } else {
            $('#nuevoPassword').removeClass('is-invalid');
        }
        
        if (confirmar && password !== confirmar) {
            $('#confirmarPassword').addClass('is-invalid')
                .next('.invalid-feedback').text('Las contraseñas no coinciden');
        } else {
            $('#confirmarPassword').removeClass('is-invalid');
        }
    });

    // Mejorar la experiencia de usuario con animaciones
    $('.card').hide().fadeIn(800);
    
    // Contador de usuarios por rol
    function actualizarContadores() {
        const totalUsuarios = tabla.rows().count();
        const administradores = tabla.rows('[data-rol="administrador"]').count();
        const agricultores = tabla.rows('[data-rol="agricultor"]').count();
        const supervisores = tabla.rows('[data-rol="supervisor"]').count();
        
        // Actualizar contadores en la interfaz si existen elementos para mostrarlos
        $('#totalUsuarios').text(totalUsuarios);
        $('#totalAdministradores').text(administradores);
        $('#totalAgricultores').text(agricultores);
        $('#totalSupervisores').text(supervisores);
    }

    // Ejecutar contador inicial
    actualizarContadores();
});

// Función global para exportar datos (opcional)
function exportarUsuarios(formato = 'excel') {
    const tabla = $('#tablaUsuarios').DataTable();
    
    if (formato === 'excel') {
        // Aquí puedes implementar la exportación a Excel
        console.log('Exportando a Excel...');
    } else if (formato === 'pdf') {
        // Aquí puedes implementar la exportación a PDF
        console.log('Exportando a PDF...');
    }
}