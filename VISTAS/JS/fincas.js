/**
 * JavaScript para el módulo de gestión de fincas
 * AgroMonitor - Sistema de Monitoreo de Cultivos
 */

$(document).ready(function() {
    
    // Inicializar DataTable
    initDataTable();
    
    // Event Listeners
    initEventListeners();
    
    // Cargar datos iniciales
    cargarFincas();
});

/**
 * Inicializar DataTable para fincas
 */
function initDataTable() {
    if ($.fn.DataTable.isDataTable('#tablaFincas')) {
        $('#tablaFincas').DataTable().destroy();
    }
    
    $('#tablaFincas').DataTable({
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
        responsive: {
            details: {
                type: 'column',
                target: 'tr'
            }
        },
        pageLength: 10,
        pagingType: "full_numbers",
        order: [[0, 'desc']],
        columnDefs: [
            { 
                targets: [8], // Columna de acciones
                orderable: false,
                responsivePriority: 1
            },
            {
                targets: [0], // ID
                responsivePriority: 2
            },
            {
                targets: [1], // Nombre
                responsivePriority: 3
            },
            {
                targets: [6], // Estado
                responsivePriority: 4
            }
        ],
        scrollX: true,
        drawCallback: function() {
            // Reinicializar tooltips después de redraw
            $('[title]').tooltip();
        }
    });
}

/**
 * Inicializar Event Listeners
 */
function initEventListeners() {
    
    // Formulario nueva finca
    $('#formNuevaFinca').on('submit', function(e) {
        e.preventDefault();
        guardarNuevaFinca();
    });
    
    // Formulario editar finca
    $('#formEditarFinca').on('submit', function(e) {
        e.preventDefault();
        actualizarFinca();
    });
    
    // Botones de acciones en tabla
    $(document).on('click', '.btn-ver-finca', function() {
        const fincaId = $(this).data('id');
        verDetallesFinca(fincaId);
    });
    
    $(document).on('click', '.btn-editar-finca', function() {
        const fincaId = $(this).data('id');
        editarFinca(fincaId);
    });
    
    $(document).on('click', '.btn-eliminar-finca', function() {
        const fincaId = $(this).data('id');
        const nombreFinca = $(this).data('nombre');
        eliminarFinca(fincaId, nombreFinca);
    });
    
    // Filtros
    $('#filtroPropietario, #filtroEstado').on('change', function() {
        aplicarFiltros();
    });
    
    $('#filtroAreaMin, #filtroAreaMax').on('input', function() {
        aplicarFiltros();
    });
    
    $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
    });
    
    // Validación en tiempo real para coordenadas
    $('#nuevaLatitud, #nuevaLongitud').on('input', function() {
        validarCoordenadas();
    });
    
    // Tooltips
    $('[title]').tooltip();
}

/**
 * Guardar nueva finca
 */
function guardarNuevaFinca() {
    
    // Validar formulario
    if (!validarFormularioFinca()) {
        return;
    }
    
    // Mostrar loading
    mostrarLoading('#modalNuevaFinca .modal-body');
    
    const formData = new FormData($('#formNuevaFinca')[0]);
    
    // Añadir action
    formData.set('action', 'crear');
    
    // Si no es administrador, usar el ID del usuario actual
    if (window.usuarioActual && window.usuarioActual.rol !== 'administrador') {
        formData.set('propietario_id', window.usuarioActual.id);
    }
    
    $.ajax({
        url: '../CONTROLADORES/fincas_c.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarLoading('#modalNuevaFinca .modal-body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    $('#modalNuevaFinca').modal('hide');
                    $('#formNuevaFinca')[0].reset();
                    // Recargar la página para mostrar la nueva finca
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('#modalNuevaFinca .modal-body');
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de comunicación con el servidor',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Ver detalles de una finca
 */
function verDetallesFinca(fincaId) {
    
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/fincas_c.php',
        type: 'GET',
        data: {
            action: 'obtener',
            finca_id: fincaId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                mostrarModalDetalles(response.finca);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('body');
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar los detalles de la finca',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Mostrar modal con detalles de finca
 */
function mostrarModalDetalles(finca) {
    
    const coordenadas = (finca.fin_latitud && finca.fin_longitud) 
        ? `${finca.fin_latitud}, ${finca.fin_longitud}` 
        : 'No registradas';
    
    const modalHTML = `
        <div class="modal fade" id="modalDetallesFinca" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            Detalles de Finca: ${finca.fin_nombre}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Información General</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Nombre:</strong></td><td>${finca.fin_nombre}</td></tr>
                                    <tr><td><strong>Área Total:</strong></td><td>${finca.fin_area_total} hectáreas</td></tr>
                                    <tr><td><strong>Estado:</strong></td><td>
                                        <span class="badge ${finca.fin_estado === 'activa' ? 'bg-success' : 'bg-danger'}">
                                            ${finca.fin_estado.charAt(0).toUpperCase() + finca.fin_estado.slice(1)}
                                        </span>
                                    </td></tr>
                                    <tr><td><strong>Fecha Registro:</strong></td><td>${new Date(finca.fin_fecha_registro).toLocaleDateString('es-CO')}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Ubicación:</strong></td><td>${finca.fin_ubicacion || 'No especificada'}</td></tr>
                                    <tr><td><strong>Coordenadas:</strong></td><td>${coordenadas}</td></tr>
                                </table>
                            </div>
                        </div>
                        
                        ${finca.fin_descripcion ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary"><i class="fas fa-file-alt me-2"></i>Descripción</h6>
                                <p class="border p-3 rounded bg-light">${finca.fin_descripcion}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-user me-2"></i>Propietario</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Nombre:</strong></td><td>${finca.usu_nombre} ${finca.usu_apellido}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${finca.usu_email || 'No especificado'}</td></tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary"><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h4 class="text-success mb-1">${finca.total_lotes || 0}</h4>
                                            <small class="text-muted">Lotes Registrados</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        ${(window.usuarioActual.rol === 'administrador' || 
                          (window.usuarioActual.rol === 'agricultor' && finca.fin_propietario == window.usuarioActual.id)) ? 
                          `<button type="button" class="btn btn-primary" onclick="editarFinca(${finca.fin_id})">
                              <i class="fas fa-edit me-2"></i>Editar Finca
                           </button>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior si existe
    $('#modalDetallesFinca').remove();
    
    // Agregar nuevo modal al DOM
    $('body').append(modalHTML);
    
    // Mostrar modal
    $('#modalDetallesFinca').modal('show');
    
    // Limpiar cuando se cierre
    $('#modalDetallesFinca').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

/**
 * Editar finca
 */
function editarFinca(fincaId) {
    // Cerrar modal de detalles si está abierto
    $('#modalDetallesFinca').modal('hide');
    
    // Obtener datos de la finca
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/fincas_c.php',
        type: 'GET',
        data: {
            action: 'obtener',
            finca_id: fincaId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                cargarDatosFormularioEdicion(response.finca);
                $('#modalEditarFinca').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('body');
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'No se pudo obtener la información de la finca',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Cargar datos en el formulario de edición
 */
function cargarDatosFormularioEdicion(finca) {
    $('#editarFincaId').val(finca.fin_id);
    $('#editarNombreFinca').val(finca.fin_nombre);
    $('#editarAreaTotal').val(finca.fin_area_total);
    $('#editarUbicacion').val(finca.fin_ubicacion);
    $('#editarLatitud').val(finca.fin_latitud || '');
    $('#editarLongitud').val(finca.fin_longitud || '');
    $('#editarDescripcion').val(finca.fin_descripcion || '');
    $('#editarEstado').val(finca.fin_estado);
    
    // Solo cargar propietario si es administrador
    if (window.usuarioActual && window.usuarioActual.rol === 'administrador') {
        $('#editarPropietario').val(finca.fin_propietario);
    }
}

/**
 * Actualizar finca
 */
function actualizarFinca() {
    // Validar formulario
    if (!validarFormularioEdicion()) {
        return;
    }
    
    // Mostrar loading
    mostrarLoading('#modalEditarFinca .modal-body');
    
    const formData = new FormData($('#formEditarFinca')[0]);
    formData.set('action', 'actualizar');
    
    $.ajax({
        url: '../CONTROLADORES/fincas_c.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarLoading('#modalEditarFinca .modal-body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    $('#modalEditarFinca').modal('hide');
                    // Recargar la página para mostrar los cambios
                    window.location.reload();
                });
            } else {
                console.error('Error del servidor:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: response.message || 'Error desconocido al actualizar la finca',
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('#modalEditarFinca .modal-body');
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'No se pudo actualizar la finca. Error: ' + error,
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Validar formulario de edición
 */
function validarFormularioEdicion() {
    let esValido = true;
    let camposFaltantes = [];
    
    // Validar campos requeridos
    const camposRequeridos = {
        'nombre': 'Nombre de la Finca',
        'ubicacion': 'Ubicación',
        'area_total': 'Área Total'
    };
    
    Object.keys(camposRequeridos).forEach(function(campo) {
        const elemento = $(`#formEditarFinca [name="${campo}"]`);
        const valor = elemento.val();
        
        if (!valor || !valor.trim()) {
            elemento.addClass('is-invalid');
            camposFaltantes.push(camposRequeridos[campo]);
            esValido = false;
        } else {
            elemento.removeClass('is-invalid');
        }
    });
    
    // Validar área total
    const areaTotal = parseFloat($('#formEditarFinca [name="area_total"]').val());
    if (areaTotal && (isNaN(areaTotal) || areaTotal <= 0)) {
        $('#formEditarFinca [name="area_total"]').addClass('is-invalid');
        if (!camposFaltantes.includes('Área Total')) {
            camposFaltantes.push('Área Total (debe ser mayor a 0)');
        }
        esValido = false;
    }
    
    // Validar propietario (solo para administradores)
    if (window.usuarioActual && window.usuarioActual.rol === 'administrador') {
        const propietario = $('#editarPropietario').val();
        if (!propietario) {
            $('#editarPropietario').addClass('is-invalid');
            camposFaltantes.push('Propietario');
            esValido = false;
        } else {
            $('#editarPropietario').removeClass('is-invalid');
        }
    }
    
    if (!esValido) {
        const mensaje = camposFaltantes.length > 0 
            ? 'Campos faltantes o incorrectos:\n• ' + camposFaltantes.join('\n• ')
            : 'Por favor completa todos los campos obligatorios';
            
        Swal.fire({
            icon: 'warning',
            title: 'Formulario incompleto',
            text: mensaje,
            confirmButtonColor: '#2E7D32'
        });
    }
    
    return esValido;
}

/**
 * Eliminar finca
 */
function eliminarFinca(fincaId, nombreFinca) {
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar la finca "${nombreFinca}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F44336',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarEliminacion(fincaId, nombreFinca);
        }
    });
}

/**
 * Ejecutar eliminación de finca
 */
function ejecutarEliminacion(fincaId, nombreFinca) {
    
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/fincas_c.php',
        type: 'POST',
        data: {
            action: 'eliminar',
            finca_id: fincaId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminada!',
                    text: `La finca "${nombreFinca}" ha sido eliminada exitosamente.`,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    cargarFincas();
                    actualizarEstadisticas();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('body');
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al eliminar la finca',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
    const table = $('#tablaFincas').DataTable();
    
    // Filtro por propietario
    const propietario = $('#filtroPropietario').val();
    table.column(3).search(propietario).draw();
    
    // Filtro por estado
    const estado = $('#filtroEstado').val();
    table.column(6).search(estado).draw();
    
    // TODO: Implementar filtros por área
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
    $('#filtroPropietario').val('');
    $('#filtroEstado').val('');
    $('#filtroAreaMin').val('');
    $('#filtroAreaMax').val('');
    
    const table = $('#tablaFincas').DataTable();
    table.search('').columns().search('').draw();
}

/**
 * Validar formulario de finca
 */
function validarFormularioFinca() {
    
    let esValido = true;
    let camposFaltantes = [];
    
    // Validar campos requeridos
    const camposRequeridos = {
        'nombre': 'Nombre de la Finca',
        'ubicacion': 'Ubicación',
        'area_total': 'Área Total'
    };
    
    Object.keys(camposRequeridos).forEach(function(campo) {
        const elemento = $(`[name="${campo}"]`);
        const valor = elemento.val();
        
        if (!valor || !valor.trim()) {
            elemento.addClass('is-invalid');
            camposFaltantes.push(camposRequeridos[campo]);
            esValido = false;
        } else {
            elemento.removeClass('is-invalid');
        }
    });
    
    // Validar área total
    const areaTotal = parseFloat($('[name="area_total"]').val());
    if (areaTotal && (isNaN(areaTotal) || areaTotal <= 0)) {
        $('[name="area_total"]').addClass('is-invalid');
        if (!camposFaltantes.includes('Área Total')) {
            camposFaltantes.push('Área Total (debe ser mayor a 0)');
        }
        esValido = false;
    }
    
    // Validar propietario (solo para administradores)
    if (window.usuarioActual && window.usuarioActual.rol === 'administrador') {
        const propietario = $('[name="propietario_id"]').val();
        if (!propietario) {
            $('[name="propietario_id"]').addClass('is-invalid');
            camposFaltantes.push('Propietario');
            esValido = false;
        } else {
            $('[name="propietario_id"]').removeClass('is-invalid');
        }
    }
    
    if (!esValido) {
        const mensaje = camposFaltantes.length > 0 
            ? 'Campos faltantes o incorrectos:\n• ' + camposFaltantes.join('\n• ')
            : 'Por favor completa todos los campos obligatorios';
            
        Swal.fire({
            icon: 'warning',
            title: 'Formulario incompleto',
            text: mensaje,
            confirmButtonColor: '#2E7D32'
        });
    }
    
    return esValido;
}

/**
 * Validar coordenadas GPS
 */
function validarCoordenadas() {
    const latitud = parseFloat($('#nuevaLatitud').val());
    const longitud = parseFloat($('#nuevaLongitud').val());
    
    let mensaje = '';
    
    if (!isNaN(latitud)) {
        if (latitud < -90 || latitud > 90) {
            mensaje += 'La latitud debe estar entre -90 y 90. ';
            $('#nuevaLatitud').addClass('is-invalid');
        } else {
            $('#nuevaLatitud').removeClass('is-invalid');
        }
    }
    
    if (!isNaN(longitud)) {
        if (longitud < -180 || longitud > 180) {
            mensaje += 'La longitud debe estar entre -180 y 180.';
            $('#nuevaLongitud').addClass('is-invalid');
        } else {
            $('#nuevaLongitud').removeClass('is-invalid');
        }
    }
    
    if (mensaje) {
        $('#nuevaLatitud, #nuevaLongitud').attr('title', mensaje).tooltip('dispose').tooltip();
    }
}

/**
 * Cargar fincas desde el servidor
 */
function cargarFincas() {
    // Esta función se ejecuta automáticamente cuando se carga la página
    // ya que las fincas se cargan desde PHP
    console.log('Fincas cargadas desde PHP');
}

/**
 * Actualizar estadísticas
 */
function actualizarEstadisticas() {
    // Recalcular estadísticas desde la tabla actual
    const table = $('#tablaFincas').DataTable();
    const data = table.rows().data();
    
    let totalFincas = 0;
    let fincasActivas = 0;
    let areaTotal = 0;
    let lotesTotal = 0;
    
    data.each(function(row) {
        totalFincas++;
        // Aquí necesitarías acceso a los datos reales para calcular estadísticas
    });
    
    // Actualizar elementos en la interfaz
    $('#totalFincas').text(totalFincas);
    // etc...
}

/**
 * Mostrar indicador de carga
 */
function mostrarLoading(selector) {
    $(selector).append(`
        <div class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
    `);
}

/**
 * Ocultar indicador de carga
 */
function ocultarLoading(selector) {
    $(selector + ' .loading-overlay').remove();
}

/**
 * Utilidades
 */

// Formatear números
function formatearNumero(numero, decimales = 2) {
    return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    }).format(numero);
}

// Capitalizar primera letra
function capitalizar(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Validar email
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}