// Variables globales
let tablaCosechas;
let modalNuevaCosecha, modalEditarCosecha;

// Inicialización cuando el DOM esté listo
$(document).ready(function() {
    initializeDataTable();
    initializeModals();
    bindEventHandlers();
    actualizarEstadisticas();
    
    // Test de conectividad AJAX
    console.log('Iniciando test de conectividad AJAX...');
    $.ajax({
        url: 'AJAX/test_connection.php',
        type: 'GET',
        success: function(response) {
            console.log('✅ Test AJAX exitoso:', response);
        },
        error: function(xhr, status, error) {
            console.error('❌ Error en test AJAX:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
        }
    });
});

// Inicializar DataTable
function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#tablaCosechas')) {
        $('#tablaCosechas').DataTable().destroy();
    }

    tablaCosechas = $('#tablaCosechas').DataTable({
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        responsive: true,
        pageLength: 25,
        order: [[1, 'desc']], // Ordenar por fecha de cosecha descendente
        columnDefs: [
            {
                targets: [9], // Columna de acciones
                orderable: false,
                searchable: false
            }
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] // Excluir columna de acciones
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                },
                customize: function (doc) {
                    doc.content[1].table.widths = ['8%', '12%', '15%', '12%', '10%', '15%', '12%', '12%', '8%'];
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8]
                }
            }
        ]
    });
}

// Inicializar modales
function initializeModals() {
    modalNuevaCosecha = new bootstrap.Modal(document.getElementById('modalNuevaCosecha'));
    modalEditarCosecha = new bootstrap.Modal(document.getElementById('modalEditarCosecha'));
}

// Enlazar eventos
function bindEventHandlers() {
    // Filtros
    $('#filtroSiembra, #filtroCalidad, #filtroFechaInicio, #filtroFechaFin, #filtroComprador').on('change keyup', function() {
        aplicarFiltros();
    });

    // Limpiar filtros
    $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
    });

    // Exportar cosechas
    $('#btnExportarCosechas').on('click', function() {
        exportarCosechas();
    });

    // Formulario nueva cosecha
    $('#formNuevaCosecha').on('submit', function(e) {
        e.preventDefault();
        crearCosecha();
    });

    // Formulario editar cosecha
    $('#formEditarCosecha').on('submit', function(e) {
        e.preventDefault();
        actualizarCosecha();
    });

    // Botones de acción en la tabla
    $(document).on('click', '.btn-ver-cosecha', function() {
        const cosechaId = $(this).data('id');
        verDetallesCosecha(cosechaId);
    });

    $(document).on('click', '.btn-editar-cosecha', function() {
        const cosechaId = $(this).data('id');
        editarCosecha(cosechaId);
    });

    $(document).on('click', '.btn-eliminar-cosecha', function() {
        const cosechaId = $(this).data('id');
        const fecha = $(this).data('fecha');
        eliminarCosecha(cosechaId, fecha);
    });

    // Calcular total de ingresos automáticamente
    $('#nuevaCantidad, #nuevoPrecioUnitario').on('input', function() {
        calcularTotalIngresos('nuevo');
    });

    $('#editarCantidad, #editarPrecioUnitario').on('input', function() {
        calcularTotalIngresos('editar');
    });
}

// Aplicar filtros a la tabla
function aplicarFiltros() {
    const filtroSiembra = $('#filtroSiembra').val();
    const filtroCalidad = $('#filtroCalidad').val();
    const filtroFechaInicio = $('#filtroFechaInicio').val();
    const filtroFechaFin = $('#filtroFechaFin').val();
    const filtroComprador = $('#filtroComprador').val();

    tablaCosechas.columns().search('').draw();

    if (filtroSiembra) {
        tablaCosechas.column(2).search(filtroSiembra, false, false);
    }
    if (filtroCalidad) {
        tablaCosechas.column(4).search(filtroCalidad, false, false);
    }
    if (filtroComprador) {
        tablaCosechas.column(5).search(filtroComprador, true, false);
    }

    // Filtro por rango de fechas simplificado
    if (filtroFechaInicio || filtroFechaFin) {
        // Remover filtros anteriores
        $.fn.dataTable.ext.search.pop();
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tablaCosechas') {
                return true;
            }

            try {
                // Convertir fecha DD/MM/YYYY a Date para comparación
                const fechaPartes = data[1].split('/');
                const fechaCosecha = new Date(fechaPartes[2], fechaPartes[1] - 1, fechaPartes[0]);
                
                if (filtroFechaInicio) {
                    const fechaInicio = new Date(filtroFechaInicio);
                    if (fechaCosecha < fechaInicio) {
                        return false;
                    }
                }
                
                if (filtroFechaFin) {
                    const fechaFin = new Date(filtroFechaFin);
                    if (fechaCosecha > fechaFin) {
                        return false;
                    }
                }
                
                return true;
            } catch (e) {
                return true; // Si hay error en el parseo, mostrar la fila
            }
        });
    }

    tablaCosechas.draw();
}

// Limpiar filtros
function limpiarFiltros() {
    $('#filtroSiembra, #filtroCalidad, #filtroFechaInicio, #filtroFechaFin, #filtroComprador').val('');
    
    // Limpiar filtros de DataTables
    while ($.fn.dataTable.ext.search.length > 0) {
        $.fn.dataTable.ext.search.pop();
    }
    
    tablaCosechas.columns().search('').draw();
}

// Crear nueva cosecha
function crearCosecha() {
    const formData = new FormData(document.getElementById('formNuevaCosecha'));
    
    // Validaciones básicas
    if (!formData.get('fecha_cosecha') || !formData.get('siembra_id') || 
        !formData.get('cantidad_cosechada') || !formData.get('unidad') || 
        !formData.get('calidad')) {
        
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'Por favor, complete todos los campos requeridos'
        });
        return;
    }

    // Mostrar indicador de carga
    Swal.fire({
        title: 'Registrando cosecha...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '../VISTAS/AJAX/crear_cosecha.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message || 'Cosecha registrada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    modalNuevaCosecha.hide();
                    document.getElementById('formNuevaCosecha').reset();
                    location.reload(); // Recargar para actualizar estadísticas
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al registrar la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Intente nuevamente.'
            });
        }
    });
}

// Crear nueva cosecha
function crearCosecha() {
    const formData = new FormData(document.getElementById('formNuevaCosecha'));
    
    // Validaciones básicas
    if (!formData.get('siembra_id') || !formData.get('fecha_cosecha') || 
        !formData.get('cantidad_cosechada') || !formData.get('unidad') || !formData.get('calidad')) {
        
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'Por favor, complete todos los campos requeridos'
        });
        return;
    }

    // Mostrar indicador de carga
    Swal.fire({
        title: 'Creando cosecha...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'AJAX/crear_cosecha.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message || 'Cosecha creada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    modalNuevaCosecha.hide();
                    document.getElementById('formNuevaCosecha').reset();
                    location.reload(); // Recargar para actualizar estadísticas
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al crear la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Intente nuevamente.'
            });
        }
    });
}

// Ver detalles de cosecha
function verDetallesCosecha(cosechaId) {
    console.log('🔍 Iniciando ver detalles de cosecha:', cosechaId);
    
    Swal.fire({
        title: 'Cargando detalles...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'AJAX/obtener_cosecha.php',
        type: 'GET',
        data: { id: cosechaId },
        success: function(response) {
            console.log('✅ Respuesta AJAX obtener_cosecha:', response);
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    const cosecha = result.cosecha;
                    mostrarDetallesCosecha(cosecha);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al obtener los detalles de la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        }
    });
}

// Mostrar detalles de cosecha en modal
function mostrarDetallesCosecha(cosecha) {
    const calidadBadge = {
        'primera': '<span class="badge bg-success">Primera</span>',
        'segunda': '<span class="badge bg-info">Segunda</span>',
        'tercera': '<span class="badge bg-warning">Tercera</span>',
        'descarte': '<span class="badge bg-danger">Descarte</span>'
    };

    const estadoBadge = cosecha.cos_total_ingresos > 0 ? 
        '<span class="badge bg-success">Vendida</span>' : 
        '<span class="badge bg-warning">Almacenada</span>';

    const rendimientoHa = cosecha.lot_area > 0 ? 
        (cosecha.cos_cantidad_cosechada / cosecha.lot_area).toFixed(2) : 
        'N/A';

    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                <table class="table table-sm">
                    <tr><td><strong>Fecha de Cosecha:</strong></td><td>${formatearFecha(cosecha.cos_fecha_cosecha)}</td></tr>
                    <tr><td><strong>Siembra:</strong></td><td>${cosecha.lot_nombre} - ${cosecha.cul_nombre}</td></tr>
                    <tr><td><strong>Cantidad:</strong></td><td>${parseFloat(cosecha.cos_cantidad_cosechada).toLocaleString()} ${cosecha.cos_unidad}</td></tr>
                    <tr><td><strong>Calidad:</strong></td><td>${calidadBadge[cosecha.cos_calidad]}</td></tr>
                    <tr><td><strong>Rendimiento/Ha:</strong></td><td>${rendimientoHa} ${cosecha.cos_unidad}/ha</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-handshake me-2"></i>Información Comercial</h6>
                <table class="table table-sm">
                    <tr><td><strong>Estado:</strong></td><td>${estadoBadge}</td></tr>
                    <tr><td><strong>Comprador:</strong></td><td>${cosecha.cos_comprador || 'Sin vender'}</td></tr>
                    <tr><td><strong>Precio Unitario:</strong></td><td>${cosecha.cos_precio_venta_unitario ? '$' + parseFloat(cosecha.cos_precio_venta_unitario).toLocaleString() : 'N/A'}</td></tr>
                    <tr><td><strong>Total Ingresos:</strong></td><td><strong>${cosecha.cos_total_ingresos ? '$' + parseFloat(cosecha.cos_total_ingresos).toLocaleString() : 'N/A'}</strong></td></tr>
                    <tr><td><strong>Responsable:</strong></td><td>${cosecha.responsable_nombre} ${cosecha.responsable_apellido}</td></tr>
                </table>
            </div>
        </div>
        ${cosecha.cos_observaciones ? `
        <div class="row mt-3">
            <div class="col-12">
                <h6><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                <p class="alert alert-info">${cosecha.cos_observaciones}</p>
            </div>
        </div>
        ` : ''}
    `;

    Swal.fire({
        title: `<i class="fas fa-tractor me-2"></i>Detalles de Cosecha #${cosecha.cos_id}`,
        html: html,
        width: '80%',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'swal-wide'
        }
    });
}

// Editar cosecha
function editarCosecha(cosechaId) {
    Swal.fire({
        title: 'Cargando datos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'AJAX/obtener_cosecha.php',
        type: 'GET',
        data: { id: cosechaId },
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    const cosecha = result.cosecha;
                    llenarFormularioEdicion(cosecha);
                    Swal.close();
                    modalEditarCosecha.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al obtener los datos de la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        }
    });
}

// Llenar formulario de edición
function llenarFormularioEdicion(cosecha) {
    $('#editarCosechaId').val(cosecha.cos_id);
    $('#editarFechaCosecha').val(cosecha.cos_fecha_cosecha);
    $('#editarCantidad').val(cosecha.cos_cantidad_cosechada);
    $('#editarUnidad').val(cosecha.cos_unidad);
    $('#editarCalidad').val(cosecha.cos_calidad);
    $('#editarComprador').val(cosecha.cos_comprador || '');
    $('#editarPrecioUnitario').val(cosecha.cos_precio_venta_unitario || '');
    $('#editarTotalIngresos').val(cosecha.cos_total_ingresos || '');
    $('#editarObservaciones').val(cosecha.cos_observaciones || '');

    // Llenar select de siembra (solo mostrar la actual, deshabilitado)
    const siembraOption = `<option value="${cosecha.sie_id}" selected>
        ${cosecha.lot_nombre} - ${cosecha.cul_nombre} (Sembrado: ${formatearFecha(cosecha.sie_fecha_siembra)})
    </option>`;
    $('#editarSiembra').html(siembraOption);
}

// Actualizar cosecha
function actualizarCosecha() {
    const formData = new FormData(document.getElementById('formEditarCosecha'));
    
    // Validaciones básicas
    if (!formData.get('fecha_cosecha') || !formData.get('cantidad_cosechada') || 
        !formData.get('unidad') || !formData.get('calidad')) {
        
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'Por favor, complete todos los campos requeridos'
        });
        return;
    }

    // Mostrar indicador de carga
    Swal.fire({
        title: 'Actualizando cosecha...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'AJAX/actualizar_cosecha.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: result.message || 'Cosecha actualizada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    modalEditarCosecha.hide();
                    location.reload(); // Recargar para actualizar estadísticas
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al actualizar la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Intente nuevamente.'
            });
        }
    });
}

// Eliminar cosecha
function eliminarCosecha(cosechaId, fecha) {
    Swal.fire({
        title: '¿Está seguro?',
        html: `¿Desea eliminar la cosecha del <strong>${fecha}</strong>?<br><br>
               <small class="text-muted">Esta acción no se puede deshacer</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarEliminacion(cosechaId);
        }
    });
}

// Ejecutar eliminación
function ejecutarEliminacion(cosechaId) {
    Swal.fire({
        title: 'Eliminando cosecha...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'AJAX/eliminar_cosecha.php',
        type: 'POST',
        data: { id: cosechaId },
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: result.message || 'Cosecha eliminada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    location.reload(); // Recargar para actualizar estadísticas
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Error al eliminar la cosecha'
                    });
                }
            } catch (e) {
                console.error('Error al parsear JSON:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la respuesta del servidor'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        }
    });
}

// Calcular total de ingresos
function calcularTotalIngresos(prefijo) {
    let cantidadId, precioId, totalId;
    
    if (prefijo === 'nuevo') {
        cantidadId = '#nuevaCantidad';
        precioId = '#nuevoPrecioUnitario';
        totalId = '#nuevoTotalIngresos';
    } else if (prefijo === 'editar') {
        cantidadId = '#editarCantidad';
        precioId = '#editarPrecioUnitario';
        totalId = '#editarTotalIngresos';
    }
    
    const cantidad = parseFloat($(cantidadId).val()) || 0;
    const precio = parseFloat($(precioId).val()) || 0;
    const total = cantidad * precio;
    
    $(totalId).val(total > 0 ? total.toFixed(2) : '');
}

// Actualizar estadísticas (para futuras implementaciones)
function actualizarEstadisticas() {
    // Aquí se pueden hacer llamadas AJAX para actualizar las estadísticas
    // Por ahora, las estadísticas se calculan en PHP
    console.log('Estadísticas actualizadas');
}

// Exportar cosechas
function exportarCosechas() {
    // Usar la funcionalidad de exportación de DataTables
    tablaCosechas.button(0).trigger(); // Exportar a Excel
}

// Función auxiliar para formatear fechas
function formatearFecha(fecha) {
    if (!fecha) return '';
    
    try {
        const date = new Date(fecha);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch (e) {
        return fecha; // Devolver la fecha original si hay error
    }
}

// Función auxiliar para formatear números
function formatearNumero(numero, decimales = 2) {
    return parseFloat(numero).toLocaleString('es-ES', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}