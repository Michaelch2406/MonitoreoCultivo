/**
 * JavaScript para el módulo de gestión de lotes
 * AgroMonitor - Sistema de Monitoreo de Cultivos
 */

$(document).ready(function() {
    
    // Inicializar DataTable
    initDataTable();
    
    // Event Listeners
    initEventListeners();
    
    // Cargar datos iniciales
    cargarLotes();
});

/**
 * Inicializar DataTable para lotes
 */
function initDataTable() {
    if ($.fn.DataTable.isDataTable('#tablaLotes')) {
        $('#tablaLotes').DataTable().destroy();
    }
    
    $('#tablaLotes').DataTable({
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
    
    // Formulario nuevo lote
    $('#formNuevoLote').on('submit', function(e) {
        e.preventDefault();
        guardarNuevoLote();
    });
    
    // Formulario editar lote
    $('#formEditarLote').on('submit', function(e) {
        e.preventDefault();
        actualizarLote();
    });
    
    // Botones de acciones en tabla
    $(document).on('click', '.btn-ver-lote', function() {
        const loteId = $(this).data('id');
        verDetallesLote(loteId);
    });
    
    $(document).on('click', '.btn-editar-lote', function() {
        const loteId = $(this).data('id');
        editarLote(loteId);
    });
    
    $(document).on('click', '.btn-eliminar-lote', function() {
        const loteId = $(this).data('id');
        const nombreLote = $(this).data('nombre');
        eliminarLote(loteId, nombreLote);
    });
    
    // Filtros
    $('#filtroFinca, #filtroEstado, #filtroTipoSuelo').on('change', function() {
        aplicarFiltros();
    });
    
    $('#filtroAreaMin, #filtroAreaMax').on('input', function() {
        aplicarFiltros();
    });
    
    $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
    });
    
    // Validación en tiempo real para pH
    $('#nuevoPHSuelo, #editarPHSuelo').on('input', function() {
        validarPH($(this));
    });
    
    // Tooltips
    $('[title]').tooltip();
}

/**
 * Guardar nuevo lote
 */
function guardarNuevoLote() {
    
    // Validar formulario
    if (!validarFormularioLote('#formNuevoLote')) {
        return;
    }
    
    // Mostrar loading
    mostrarLoading('#modalNuevoLote .modal-body');
    
    const formData = new FormData($('#formNuevoLote')[0]);
    
    // Añadir action
    formData.set('action', 'crear');
    
    // Debug - log datos que se envían
    console.log('Enviando datos del lote:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    $.ajax({
        url: '../CONTROLADORES/lotes_c.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarLoading('#modalNuevoLote .modal-body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    $('#modalNuevoLote').modal('hide');
                    $('#formNuevoLote')[0].reset();
                    // Recargar la página para mostrar el nuevo lote
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
            ocultarLoading('#modalNuevoLote .modal-body');
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
 * Ver detalles de un lote
 */
function verDetallesLote(loteId) {
    
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/lotes_c.php',
        type: 'GET',
        data: {
            action: 'obtener',
            lote_id: loteId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                mostrarModalDetalles(response.lote);
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
                text: 'Error al cargar los detalles del lote',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Mostrar modal con detalles de lote
 */
function mostrarModalDetalles(lote) {
    
    const estadoBadge = getEstadoBadge(lote.lot_estado);
    
    const modalHTML = `
        <div class="modal fade" id="modalDetallesLote" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-th-large me-2"></i>
                            Detalles del Lote: ${lote.lot_nombre}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Información General</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Nombre:</strong></td><td>${lote.lot_nombre}</td></tr>
                                    <tr><td><strong>Área:</strong></td><td>${parseFloat(lote.lot_area).toFixed(4)} hectáreas</td></tr>
                                    <tr><td><strong>Estado:</strong></td><td>${estadoBadge}</td></tr>
                                    <tr><td><strong>Fecha Registro:</strong></td><td>${new Date(lote.lot_fecha_registro).toLocaleDateString('es-CO')}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-seedling me-2"></i>Información del Suelo</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Tipo de Suelo:</strong></td><td>${lote.lot_tipo_suelo || 'No especificado'}</td></tr>
                                    <tr><td><strong>pH del Suelo:</strong></td><td>${lote.lot_ph_suelo ? parseFloat(lote.lot_ph_suelo).toFixed(1) : 'No medido'}</td></tr>
                                </table>
                            </div>
                        </div>
                        
                        ${lote.lot_descripcion ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary"><i class="fas fa-file-alt me-2"></i>Descripción</h6>
                                <p class="border p-3 rounded bg-light">${lote.lot_descripcion}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-map-marked-alt me-2"></i>Finca</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Nombre:</strong></td><td>${lote.fin_nombre}</td></tr>
                                    <tr><td><strong>Área Total:</strong></td><td>${parseFloat(lote.fin_area_total).toFixed(2)} hectáreas</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-user me-2"></i>Propietario</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>Nombre:</strong></td><td>${lote.usu_nombre} ${lote.usu_apellido}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${lote.usu_email || 'No especificado'}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        ${(window.usuarioActual.rol === 'administrador' || 
                          (window.usuarioActual.rol === 'agricultor' && lote.fin_propietario == window.usuarioActual.id) ||
                          window.usuarioActual.rol === 'supervisor') ? 
                          `<button type="button" class="btn btn-primary" onclick="editarLote(${lote.lot_id})">
                              <i class="fas fa-edit me-2"></i>Editar Lote
                           </button>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior si existe
    $('#modalDetallesLote').remove();
    
    // Agregar nuevo modal al DOM
    $('body').append(modalHTML);
    
    // Mostrar modal
    $('#modalDetallesLote').modal('show');
    
    // Limpiar cuando se cierre
    $('#modalDetallesLote').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

/**
 * Editar lote
 */
function editarLote(loteId) {
    // Cerrar modal de detalles si está abierto
    $('#modalDetallesLote').modal('hide');
    
    // Obtener datos del lote
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/lotes_c.php',
        type: 'GET',
        data: {
            action: 'obtener',
            lote_id: loteId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                cargarDatosFormularioEdicion(response.lote);
                $('#modalEditarLote').modal('show');
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
                text: 'No se pudo obtener la información del lote',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Cargar datos en el formulario de edición
 */
function cargarDatosFormularioEdicion(lote) {
    $('#editarLoteId').val(lote.lot_id);
    $('#editarNombreLote').val(lote.lot_nombre);
    $('#editarArea').val(lote.lot_area);
    $('#editarTipoSuelo').val(lote.lot_tipo_suelo || '');
    $('#editarPHSuelo').val(lote.lot_ph_suelo || '');
    $('#editarDescripcion').val(lote.lot_descripcion || '');
    $('#editarEstado').val(lote.lot_estado);
}

/**
 * Actualizar lote
 */
function actualizarLote() {
    // Validar formulario
    if (!validarFormularioLote('#formEditarLote')) {
        return;
    }
    
    // Mostrar loading
    mostrarLoading('#modalEditarLote .modal-body');
    
    const formData = new FormData($('#formEditarLote')[0]);
    formData.set('action', 'actualizar');
    
    $.ajax({
        url: '../CONTROLADORES/lotes_c.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            ocultarLoading('#modalEditarLote .modal-body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    $('#modalEditarLote').modal('hide');
                    // Recargar la página para mostrar los cambios
                    window.location.reload();
                });
            } else {
                console.error('Error del servidor:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: response.message || 'Error desconocido al actualizar el lote',
                    confirmButtonColor: '#2E7D32'
                });
            }
        },
        error: function(xhr, status, error) {
            ocultarLoading('#modalEditarLote .modal-body');
            console.error('Error AJAX:', xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'No se pudo actualizar el lote. Error: ' + error,
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Eliminar lote
 */
function eliminarLote(loteId, nombreLote) {
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar el lote "${nombreLote}"? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F44336',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarEliminacion(loteId, nombreLote);
        }
    });
}

/**
 * Ejecutar eliminación de lote
 */
function ejecutarEliminacion(loteId, nombreLote) {
    
    mostrarLoading('body');
    
    $.ajax({
        url: '../CONTROLADORES/lotes_c.php',
        type: 'POST',
        data: {
            action: 'eliminar',
            lote_id: loteId
        },
        dataType: 'json',
        success: function(response) {
            ocultarLoading('body');
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: `El lote "${nombreLote}" ha sido eliminado exitosamente.`,
                    confirmButtonColor: '#2E7D32'
                }).then(() => {
                    cargarLotes();
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
                text: 'Error al eliminar el lote',
                confirmButtonColor: '#2E7D32'
            });
        }
    });
}

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
    const table = $('#tablaLotes').DataTable();
    
    // Filtro por finca
    const finca = $('#filtroFinca').val();
    table.column(2).search(finca).draw();
    
    // Filtro por estado
    const estado = $('#filtroEstado').val();
    table.column(6).search(estado).draw();
    
    // Filtro por tipo de suelo
    const tipoSuelo = $('#filtroTipoSuelo').val();
    table.column(4).search(tipoSuelo).draw();
    
    // TODO: Implementar filtros por área
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
    $('#filtroFinca').val('');
    $('#filtroEstado').val('');
    $('#filtroTipoSuelo').val('');
    $('#filtroAreaMin').val('');
    $('#filtroAreaMax').val('');
    
    const table = $('#tablaLotes').DataTable();
    table.search('').columns().search('').draw();
}

/**
 * Validar formulario de lote
 */
function validarFormularioLote(formulario) {
    
    let esValido = true;
    let camposFaltantes = [];
    
    // Validar campos requeridos
    const camposRequeridos = {
        'nombre': 'Nombre del Lote',
        'area': 'Área'
    };
    
    // Para formulario nuevo, también validar finca
    if (formulario === '#formNuevoLote') {
        camposRequeridos['finca_id'] = 'Finca';
    }
    
    Object.keys(camposRequeridos).forEach(function(campo) {
        const elemento = $(`${formulario} [name="${campo}"]`);
        const valor = elemento.val();
        
        if (!valor || (typeof valor === 'string' && !valor.trim())) {
            elemento.addClass('is-invalid');
            camposFaltantes.push(camposRequeridos[campo]);
            esValido = false;
        } else {
            elemento.removeClass('is-invalid');
        }
    });
    
    // Validar área
    const area = parseFloat($(`${formulario} [name="area"]`).val());
    if (area && (isNaN(area) || area <= 0)) {
        $(`${formulario} [name="area"]`).addClass('is-invalid');
        if (!camposFaltantes.includes('Área')) {
            camposFaltantes.push('Área (debe ser mayor a 0)');
        }
        esValido = false;
    }
    
    // Validar pH si se proporciona
    const ph = $(`${formulario} [name="ph_suelo"]`).val();
    if (ph && (isNaN(parseFloat(ph)) || parseFloat(ph) < 0 || parseFloat(ph) > 14)) {
        $(`${formulario} [name="ph_suelo"]`).addClass('is-invalid');
        camposFaltantes.push('pH del Suelo (debe estar entre 0 y 14)');
        esValido = false;
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
 * Validar pH del suelo
 */
function validarPH(elemento) {
    const ph = parseFloat(elemento.val());
    
    if (elemento.val() && (isNaN(ph) || ph < 0 || ph > 14)) {
        elemento.addClass('is-invalid');
        elemento.attr('title', 'El pH debe estar entre 0 y 14').tooltip('dispose').tooltip();
    } else {
        elemento.removeClass('is-invalid').removeAttr('title').tooltip('dispose');
    }
}

/**
 * Obtener badge HTML para el estado
 */
function getEstadoBadge(estado) {
    let badgeClass = '';
    let estadoTexto = '';
    
    switch (estado) {
        case 'disponible':
            badgeClass = 'bg-success';
            estadoTexto = 'Disponible';
            break;
        case 'sembrado':
            badgeClass = 'bg-info';
            estadoTexto = 'Sembrado';
            break;
        case 'cosechado':
            badgeClass = 'bg-warning';
            estadoTexto = 'Cosechado';
            break;
        case 'en_preparacion':
            badgeClass = 'bg-secondary';
            estadoTexto = 'En Preparación';
            break;
        default:
            badgeClass = 'bg-light text-dark';
            estadoTexto = estado;
    }
    
    return `<span class="badge ${badgeClass}">${estadoTexto}</span>`;
}

/**
 * Cargar lotes desde el servidor
 */
function cargarLotes() {
    // Esta función se ejecuta automáticamente cuando se carga la página
    // ya que los lotes se cargan desde PHP
    console.log('Lotes cargados desde PHP');
}

/**
 * Actualizar estadísticas
 */
function actualizarEstadisticas() {
    // Recalcular estadísticas desde la tabla actual
    const table = $('#tablaLotes').DataTable();
    const data = table.rows().data();
    
    let totalLotes = 0;
    let lotesDisponibles = 0;
    let areaTotal = 0;
    let lotesSembrados = 0;
    
    data.each(function(row) {
        totalLotes++;
        // Aquí necesitarías acceso a los datos reales para calcular estadísticas
    });
    
    // Actualizar elementos en la interfaz
    // $('#totalLotes').text(totalLotes);
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