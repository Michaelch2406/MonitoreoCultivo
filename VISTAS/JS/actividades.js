$(document).ready(function() {
    // Inicializar DataTables
    const tablaActividades = $('#tablaActividades').DataTable({
        language: {
            url: '../DataTables/Spanish.json'
        },
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: -1 } // Desactivar ordenamiento en la columna de acciones
        ]
    });

    // Variables globales
    let actividadEditando = null;

    // Funciones de utilidad
    function mostrarAlerta(tipo, titulo, mensaje) {
        Swal.fire({
            icon: tipo,
            title: titulo,
            text: mensaje,
            showConfirmButton: true,
            timer: tipo === 'success' ? 3000 : null
        });
    }

    function mostrarCargando() {
        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
    }

    // Mostrar/ocultar sección de productos según tipo de actividad
    $('#tipoActividad').on('change', function() {
        const tipo = $(this).val();
        const seccionProductos = $('#seccionProductos');
        
        // Tipos que requieren productos
        const tiposConProductos = ['fertilizacion', 'fumigacion'];
        
        if (tiposConProductos.includes(tipo)) {
            seccionProductos.addClass('show').show();
            // Hacer campos requeridos
            $('#productosUtilizados').prop('required', true);
        } else {
            seccionProductos.removeClass('show').hide();
            // Quitar requerimiento y limpiar
            $('#productosUtilizados').prop('required', false).val('');
            $('#cantidadProducto').val('');
            $('#unidadProducto').val('ml');
        }
        
        // Actualizar placeholder de descripción según tipo
        actualizarPlaceholderDescripcion(tipo);
    });

    function actualizarPlaceholderDescripcion(tipo) {
        const placeholders = {
            'riego': 'Describe el método de riego, duración, área cubierta...',
            'fertilizacion': 'Describe el tipo de fertilizante, método de aplicación...',
            'fumigacion': 'Describe la plaga o enfermedad tratada, método de aplicación...',
            'poda': 'Describe el tipo de poda realizada, herramientas utilizadas...',
            'deshierbe': 'Describe el área deshierbada, método utilizado...',
            'aporque': 'Describe el área aporcada, altura del aporque...',
            'otro': 'Describe detalladamente la actividad realizada...'
        };
        
        $('#descripcionActividad').attr('placeholder', placeholders[tipo] || 'Describe la actividad realizada...');
    }

    // Limpiar formulario
    function limpiarFormulario() {
        $('#formNuevaActividad')[0].reset();
        $('#fechaActividad').val(new Date().toISOString().split('T')[0]);
        $('#seccionProductos').removeClass('show').hide();
        $('#productosUtilizados').prop('required', false);
        actividadEditando = null;
        
        // Cambiar título del modal
        $('.modal-title').html('<i class="fas fa-plus me-2"></i>Nueva Actividad');
        $('#btnGuardarActividad').html('<i class="fas fa-save me-2"></i>Guardar Actividad');
    }

    // Abrir modal nueva actividad
    $('#modalNuevaActividad').on('show.bs.modal', function() {
        if (!actividadEditando) {
            limpiarFormulario();
        }
    });

    // Cerrar modal
    $('#modalNuevaActividad').on('hidden.bs.modal', function() {
        limpiarFormulario();
    });

    // Guardar actividad
    $('#btnGuardarActividad').on('click', function() {
        const formData = new FormData($('#formNuevaActividad')[0]);
        
        // Validaciones básicas
        if (!formData.get('siembra_id')) {
            mostrarAlerta('error', 'Error', 'Debe seleccionar una siembra');
            return;
        }
        
        if (!formData.get('tipo')) {
            mostrarAlerta('error', 'Error', 'Debe seleccionar el tipo de actividad');
            return;
        }
        
        if (!formData.get('fecha')) {
            mostrarAlerta('error', 'Error', 'Debe especificar la fecha de la actividad');
            return;
        }
        
        if (!formData.get('descripcion')) {
            mostrarAlerta('error', 'Error', 'Debe proporcionar una descripción de la actividad');
            return;
        }

        // Validar productos si es necesario
        const tipo = formData.get('tipo');
        const tiposConProductos = ['fertilizacion', 'fumigacion'];
        
        if (tiposConProductos.includes(tipo) && !formData.get('productos_utilizados')) {
            mostrarAlerta('error', 'Error', 'Debe especificar los productos utilizados para este tipo de actividad');
            return;
        }

        mostrarCargando();

        const url = actividadEditando ? 
            'AJAX/actualizar_actividad.php' : 
            'AJAX/crear_actividad.php';
            
        if (actividadEditando) {
            formData.append('actividad_id', actividadEditando);
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    mostrarAlerta('success', 'Éxito', response.message);
                    $('#modalNuevaActividad').modal('hide');
                    
                    // Recargar la página para mostrar los cambios
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    mostrarAlerta('error', 'Error', response.message);
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error AJAX:', error);
                mostrarAlerta('error', 'Error', 'Error de comunicación con el servidor');
            }
        });
    });

    // Ver detalles de actividad
    $(document).on('click', '.btn-ver', function() {
        const actividadId = $(this).data('actividad-id');
        
        mostrarCargando();
        
        $.ajax({
            url: 'AJAX/obtener_actividad.php',
            type: 'POST',
            data: { actividad_id: actividadId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    const actividad = response.actividad;
                    
                    let detallesHtml = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                                <p><strong>Tipo:</strong> <span class="badge tipo-${actividad.act_tipo}"><i class="fas fa-${getIconoActividad(actividad.act_tipo)} me-1"></i>${actividad.act_tipo.charAt(0).toUpperCase() + actividad.act_tipo.slice(1)}</span></p>
                                <p><strong>Fecha:</strong> ${new Date(actividad.act_fecha).toLocaleDateString('es-ES')}</p>
                                <p><strong>Siembra:</strong> ${actividad.tip_nombre}</p>
                                <p><strong>Lote:</strong> ${actividad.lot_nombre}</p>
                                <p><strong>Responsable:</strong> ${actividad.responsable_nombre}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success"><i class="fas fa-flask me-2"></i>Productos y Costos</h6>
                                <p><strong>Productos:</strong> ${actividad.act_productos_utilizados || 'No aplica'}</p>
                                <p><strong>Cantidad:</strong> ${actividad.act_cantidad_producto ? actividad.act_cantidad_producto + ' ' + actividad.act_unidad_producto : 'No especificada'}</p>
                                <p><strong>Costo:</strong> ${actividad.act_costo ? '$' + parseFloat(actividad.act_costo).toLocaleString('es-ES', {minimumFractionDigits: 2}) : 'No especificado'}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-warning"><i class="fas fa-file-text me-2"></i>Descripción</h6>
                                <p class="bg-light p-3 rounded">${actividad.act_descripcion}</p>
                            </div>
                        </div>
                        ${actividad.act_observaciones ? `
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-info"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                                <p class="bg-light p-3 rounded">${actividad.act_observaciones}</p>
                            </div>
                        </div>
                        ` : ''}
                    `;
                    
                    $('#detallesActividad').html(detallesHtml);
                    $('#modalVerActividad').modal('show');
                } else {
                    mostrarAlerta('error', 'Error', response.message);
                }
            },
            error: function() {
                Swal.close();
                mostrarAlerta('error', 'Error', 'Error de comunicación con el servidor');
            }
        });
    });

    // Función para obtener icono según tipo de actividad
    function getIconoActividad(tipo) {
        const iconos = {
            'riego': 'tint',
            'fertilizacion': 'leaf',
            'fumigacion': 'spray-can',
            'poda': 'cut',
            'deshierbe': 'broom',
            'aporque': 'mountain',
            'otro': 'tools'
        };
        return iconos[tipo] || 'tools';
    }

    // Editar actividad
    $(document).on('click', '.btn-editar', function() {
        const actividadId = $(this).data('actividad-id');
        actividadEditando = actividadId;
        
        mostrarCargando();
        
        $.ajax({
            url: 'AJAX/obtener_actividad.php',
            type: 'POST',
            data: { actividad_id: actividadId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    const actividad = response.actividad;
                    
                    // Llenar el formulario
                    $('#siembraId').val(actividad.act_siembra_id);
                    $('#tipoActividad').val(actividad.act_tipo).trigger('change');
                    $('#fechaActividad').val(actividad.act_fecha);
                    $('#descripcionActividad').val(actividad.act_descripcion);
                    $('#productosUtilizados').val(actividad.act_productos_utilizados);
                    $('#cantidadProducto').val(actividad.act_cantidad_producto);
                    $('#unidadProducto').val(actividad.act_unidad_producto);
                    $('#costoActividad').val(actividad.act_costo);
                    $('#observacionesActividad').val(actividad.act_observaciones);
                    
                    // Cambiar título del modal
                    $('.modal-title').html('<i class="fas fa-edit me-2"></i>Editar Actividad');
                    $('#btnGuardarActividad').html('<i class="fas fa-save me-2"></i>Actualizar Actividad');
                    
                    $('#modalNuevaActividad').modal('show');
                } else {
                    mostrarAlerta('error', 'Error', response.message);
                }
            },
            error: function() {
                Swal.close();
                mostrarAlerta('error', 'Error', 'Error de comunicación con el servidor');
            }
        });
    });

    // Eliminar actividad
    $(document).on('click', '.btn-eliminar', function() {
        const actividadId = $(this).data('actividad-id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará la actividad y no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarCargando();
                
                $.ajax({
                    url: 'AJAX/eliminar_actividad.php',
                    type: 'POST',
                    data: { actividad_id: actividadId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            mostrarAlerta('success', 'Eliminado', response.message);
                            
                            // Recargar la página
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            mostrarAlerta('error', 'Error', response.message);
                        }
                    },
                    error: function() {
                        Swal.close();
                        mostrarAlerta('error', 'Error', 'Error de comunicación con el servidor');
                    }
                });
            }
        });
    });

    // Filtros
    $('#filtroSiembra, #filtroTipo, #filtroFechaDesde, #filtroFechaHasta').on('change', function() {
        aplicarFiltros();
    });

    function aplicarFiltros() {
        const filtroSiembra = $('#filtroSiembra').val();
        const filtroTipo = $('#filtroTipo').val();
        const filtroFechaDesde = $('#filtroFechaDesde').val();
        const filtroFechaHasta = $('#filtroFechaHasta').val();
        
        // Limpiar filtros existentes
        tablaActividades.columns().search('').draw();
        
        // Aplicar filtros
        if (filtroSiembra) {
            tablaActividades.column(2).search(filtroSiembra, false, false);
        }
        
        if (filtroTipo) {
            tablaActividades.column(1).search(filtroTipo, false, false);
        }
        
        // Para filtros de fecha necesitaríamos implementar filtrado personalizado
        if (filtroFechaDesde || filtroFechaHasta) {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'tablaActividades') return true;
                    
                    const fechaActividad = new Date(data[0].split('/').reverse().join('-'));
                    const fechaDesde = filtroFechaDesde ? new Date(filtroFechaDesde) : null;
                    const fechaHasta = filtroFechaHasta ? new Date(filtroFechaHasta) : null;
                    
                    if (fechaDesde && fechaActividad < fechaDesde) return false;
                    if (fechaHasta && fechaActividad > fechaHasta) return false;
                    
                    return true;
                }
            );
        }
        
        tablaActividades.draw();
        
        // Limpiar filtro personalizado después del filtrado
        if (filtroFechaDesde || filtroFechaHasta) {
            $.fn.dataTable.ext.search.pop();
        }
    }

    // Botón refrescar
    $('#btnRefrescar').on('click', function() {
        location.reload();
    });

    // Botón exportar
    $('#btnExportar').on('click', function() {
        mostrarAlerta('info', 'Función no disponible', 'La función de exportar estará disponible próximamente');
    });

    // Validación en tiempo real
    $('#formNuevaActividad input, #formNuevaActividad select, #formNuevaActividad textarea').on('blur', function() {
        validarCampo($(this));
    });

    function validarCampo($campo) {
        const valor = $campo.val();
        const esRequerido = $campo.prop('required');
        
        $campo.removeClass('is-invalid is-valid');
        
        if (esRequerido && !valor) {
            $campo.addClass('is-invalid');
            return false;
        } else if (valor) {
            $campo.addClass('is-valid');
        }
        
        return true;
    }

    // Filtrar siembras por parámetro GET
    const urlParams = new URLSearchParams(window.location.search);
    const siembraParam = urlParams.get('siembra_id');
    if (siembraParam) {
        $('#filtroSiembra').val(siembraParam).trigger('change');
    }

    // Tooltips
    $('[title]').tooltip();

    // Responsive DataTables
    $(window).on('resize', function() {
        tablaActividades.columns.adjust().responsive.recalc();
    });

    // Auto-resize textarea
    $('textarea').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});