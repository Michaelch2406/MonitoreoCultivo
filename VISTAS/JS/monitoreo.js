/**
 * JavaScript para el módulo de monitoreo de cultivos
 * Sistema AgroMonitor
 */

$(document).ready(function() {
    
    // Configuración inicial
    initializeMonitoreo();
    
    function initializeMonitoreo() {
        // Inicializar DataTable
        initializeDataTable();
        
        // Configurar eventos
        setupEventHandlers();
        
        // Inicializar tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Configurar validaciones
        setupFormValidation();
    }

    // Inicializar DataTable
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaMonitoreos')) {
            $('#tablaMonitoreos').DataTable().destroy();
        }

        $('#tablaMonitoreos').DataTable({
            language: {
                processing: "Procesando...",
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "Ningún dato disponible en esta tabla",
                info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
                infoFiltered: "(filtrado de un total de _MAX_ registros)",
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
            pagingType: "full_numbers",
            order: [[0, 'desc']], // Ordenar por fecha descendente
            columnDefs: [
                {
                    targets: -1, // Última columna (acciones)
                    orderable: false,
                    searchable: false
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel me-2"></i>Excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf me-2"></i>PDF',
                    className: 'btn btn-danger btn-sm'
                }
            ]
        });
    }

    // Configurar manejadores de eventos
    function setupEventHandlers() {
        // Guardar nuevo monitoreo
        $('#btnGuardarMonitoreo').on('click', function() {
            guardarMonitoreo();
        });

        // Ver detalles del monitoreo
        $(document).on('click', '.btn-ver', function() {
            const monitoreoId = $(this).data('monitoreo-id');
            verDetallesMonitoreo(monitoreoId);
        });

        // Editar monitoreo
        $(document).on('click', '.btn-editar', function() {
            const monitoreoId = $(this).data('monitoreo-id');
            editarMonitoreo(monitoreoId);
        });

        // Eliminar monitoreo
        $(document).on('click', '.btn-eliminar', function() {
            const monitoreoId = $(this).data('monitoreo-id');
            eliminarMonitoreo(monitoreoId);
        });

        // Filtros
        $('#filtroSiembra, #filtroEstado, #filtroFecha').on('change', function() {
            aplicarFiltros();
        });

        // Botón refrescar
        $('#btnRefrescar').on('click', function() {
            location.reload();
        });

        // Botón exportar
        $('#btnExportar').on('click', function() {
            exportarMonitoreos();
        });

        // Limpiar modal al cerrarse
        $('#modalNuevoMonitoreo').on('hidden.bs.modal', function() {
            limpiarFormulario();
        });

        // Validaciones en tiempo real
        $('#presenciaPlagas').on('change', function() {
            toggleCampoTipoPlagas();
        });

        $('#presenciaEnfermedades').on('change', function() {
            toggleCampoTipoEnfermedades();
        });
    }

    // Configurar validación del formulario
    function setupFormValidation() {
        // Validación personalizada para campos requeridos
        $('#formNuevoMonitoreo input[required], #formNuevoMonitoreo select[required]').on('blur', function() {
            validarCampo($(this));
        });

        // Validación de números
        $('#alturaPromedio, #porcentajeGerminacion').on('input', function() {
            validarNumero($(this));
        });
    }

    // Validar campo individual
    function validarCampo($campo) {
        const valor = $campo.val().trim();
        const esRequerido = $campo.prop('required');

        if (esRequerido && valor === '') {
            $campo.addClass('is-invalid');
            return false;
        } else {
            $campo.removeClass('is-invalid').addClass('is-valid');
            return true;
        }
    }

    // Validar campo numérico
    function validarNumero($campo) {
        const valor = parseFloat($campo.val());
        const min = parseFloat($campo.attr('min')) || 0;
        const max = parseFloat($campo.attr('max')) || Infinity;

        if ($campo.val() !== '' && (isNaN(valor) || valor < min || valor > max)) {
            $campo.addClass('is-invalid');
            return false;
        } else {
            $campo.removeClass('is-invalid');
            if ($campo.val() !== '') {
                $campo.addClass('is-valid');
            }
            return true;
        }
    }

    // Mostrar/ocultar campo tipo de plagas
    function toggleCampoTipoPlagas() {
        const presencia = $('#presenciaPlagas').val();
        const $tipoPlagas = $('#tipoPlagas');

        if (presencia !== 'ninguna') {
            $tipoPlagas.prop('required', true);
            $tipoPlagas.closest('.form-floating').show();
        } else {
            $tipoPlagas.prop('required', false);
            $tipoPlagas.val('');
            $tipoPlagas.closest('.form-floating').hide();
        }
    }

    // Mostrar/ocultar campo tipo de enfermedades
    function toggleCampoTipoEnfermedades() {
        const presencia = $('#presenciaEnfermedades').val();
        const $tipoEnfermedades = $('#tipoEnfermedades');

        if (presencia !== 'ninguna') {
            $tipoEnfermedades.prop('required', true);
            $tipoEnfermedades.closest('.form-floating').show();
        } else {
            $tipoEnfermedades.prop('required', false);
            $tipoEnfermedades.val('');
            $tipoEnfermedades.closest('.form-floating').hide();
        }
    }

    // Guardar nuevo monitoreo
    function guardarMonitoreo() {
        // Validar formulario
        if (!validarFormulario()) {
            return;
        }

        const $btn = $('#btnGuardarMonitoreo');
        const textoOriginal = $btn.html();
        
        $btn.html('<i class="fas fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);

        const formData = new FormData($('#formNuevoMonitoreo')[0]);

        $.ajax({
            url: '../AJAX/crear_monitoreo.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Monitoreo registrado exitosamente', 'success');
                    $('#modalNuevoMonitoreo').modal('hide');
                    
                    // Recargar tabla
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                showNotification('Error en la comunicación con el servidor', 'error');
            },
            complete: function() {
                $btn.html(textoOriginal).prop('disabled', false);
            }
        });
    }

    // Validar formulario completo
    function validarFormulario() {
        let esValido = true;
        const $campos = $('#formNuevoMonitoreo input[required], #formNuevoMonitoreo select[required]');

        $campos.each(function() {
            if (!validarCampo($(this))) {
                esValido = false;
            }
        });

        // Validar campos numéricos
        $('#alturaPromedio, #porcentajeGerminacion').each(function() {
            if (!validarNumero($(this))) {
                esValido = false;
            }
        });

        if (!esValido) {
            showNotification('Por favor completa todos los campos requeridos correctamente', 'error');
        }

        return esValido;
    }

    // Ver detalles del monitoreo
    function verDetallesMonitoreo(monitoreoId) {
        $.ajax({
            url: '../AJAX/obtener_monitoreo.php',
            method: 'GET',
            data: { id: monitoreoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarDetallesMonitoreo(response.monitoreo);
                    $('#modalVerMonitoreo').modal('show');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Error al cargar los detalles del monitoreo', 'error');
            }
        });
    }

    // Mostrar detalles en el modal
    function mostrarDetallesMonitoreo(monitoreo) {
        const detallesHtml = `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                    <p><strong>Siembra:</strong> ${monitoreo.nombre_cultivo} - ${monitoreo.nombre_lote}</p>
                    <p><strong>Fecha:</strong> ${formatearFecha(monitoreo.mon_fecha_observacion)}</p>
                    <p><strong>Responsable:</strong> ${monitoreo.responsable_nombre}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-success mb-3"><i class="fas fa-chart-line me-2"></i>Parámetros de Crecimiento</h6>
                    <p><strong>Estado General:</strong> <span class="badge estado-${monitoreo.mon_estado_general}">${capitalize(monitoreo.mon_estado_general)}</span></p>
                    <p><strong>Altura Promedio:</strong> ${monitoreo.mon_altura_promedio ? monitoreo.mon_altura_promedio + ' cm' : 'No registrada'}</p>
                    <p><strong>% Germinación:</strong> ${monitoreo.mon_porcentaje_germinacion ? monitoreo.mon_porcentaje_germinacion + '%' : 'No registrado'}</p>
                    <p><strong>Color Follaje:</strong> ${monitoreo.mon_color_follaje || 'No especificado'}</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-warning mb-3"><i class="fas fa-bug me-2"></i>Control Fitosanitario</h6>
                    <p><strong>Plagas:</strong> <span class="badge plagas-${monitoreo.mon_presencia_plagas}">${capitalize(monitoreo.mon_presencia_plagas)}</span></p>
                    ${monitoreo.mon_tipo_plagas ? `<p><strong>Tipo de Plagas:</strong> ${monitoreo.mon_tipo_plagas}</p>` : ''}
                    <p><strong>Enfermedades:</strong> <span class="badge enfermedades-${monitoreo.mon_presencia_enfermedades}">${capitalize(monitoreo.mon_presencia_enfermedades)}</span></p>
                    ${monitoreo.mon_tipo_enfermedades ? `<p><strong>Tipo de Enfermedades:</strong> ${monitoreo.mon_tipo_enfermedades}</p>` : ''}
                </div>
                <div class="col-md-6">
                    <h6 class="text-info mb-3"><i class="fas fa-cloud-sun me-2"></i>Condiciones Ambientales</h6>
                    <p><strong>Clima:</strong> ${monitoreo.mon_condicion_clima || 'No especificado'}</p>
                    <p><strong>Humedad del Suelo:</strong> ${capitalize(monitoreo.mon_humedad_suelo)}</p>
                </div>
            </div>
            ${monitoreo.mon_observaciones ? `
            <hr>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-secondary mb-3"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                    <p class="text-muted">${monitoreo.mon_observaciones}</p>
                </div>
            </div>
            ` : ''}
        `;

        $('#detallesMonitoreo').html(detallesHtml);
    }

    // Editar monitoreo
    function editarMonitoreo(monitoreoId) {
        $.ajax({
            url: '../AJAX/obtener_monitoreo.php',
            method: 'GET',
            data: { id: monitoreoId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    cargarDatosFormulario(response.monitoreo);
                    $('#modalNuevoMonitoreo .modal-title').html('<i class="fas fa-edit me-2"></i>Editar Monitoreo');
                    $('#btnGuardarMonitoreo').html('<i class="fas fa-save me-2"></i>Actualizar Monitoreo');
                    $('#btnGuardarMonitoreo').data('monitoreo-id', monitoreoId);
                    $('#modalNuevoMonitoreo').modal('show');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Error al cargar los datos del monitoreo', 'error');
            }
        });
    }

    // Cargar datos en el formulario
    function cargarDatosFormulario(monitoreo) {
        $('#siembraId').val(monitoreo.mon_siembra_id);
        $('#fechaObservacion').val(monitoreo.mon_fecha_observacion);
        $('#alturaPromedio').val(monitoreo.mon_altura_promedio);
        $('#estadoGeneral').val(monitoreo.mon_estado_general);
        $('#porcentajeGerminacion').val(monitoreo.mon_porcentaje_germinacion);
        $('#colorFollaje').val(monitoreo.mon_color_follaje);
        $('#presenciaPlagas').val(monitoreo.mon_presencia_plagas);
        $('#tipoPlagas').val(monitoreo.mon_tipo_plagas);
        $('#presenciaEnfermedades').val(monitoreo.mon_presencia_enfermedades);
        $('#tipoEnfermedades').val(monitoreo.mon_tipo_enfermedades);
        $('#condicionClima').val(monitoreo.mon_condicion_clima);
        $('#humedadSuelo').val(monitoreo.mon_humedad_suelo);
        $('#observaciones').val(monitoreo.mon_observaciones);

        // Actualizar campos dependientes
        toggleCampoTipoPlagas();
        toggleCampoTipoEnfermedades();
    }

    // Eliminar monitoreo
    function eliminarMonitoreo(monitoreoId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../AJAX/eliminar_monitoreo.php',
                    method: 'POST',
                    data: { id: monitoreoId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification('Monitoreo eliminado exitosamente', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification(response.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Error al eliminar el monitoreo', 'error');
                    }
                });
            }
        });
    }

    // Aplicar filtros
    function aplicarFiltros() {
        const tabla = $('#tablaMonitoreos').DataTable();
        const filtroSiembra = $('#filtroSiembra').val();
        const filtroEstado = $('#filtroEstado').val();
        const filtroFecha = $('#filtroFecha').val();

        // Limpiar filtros anteriores
        tabla.columns().search('').draw();

        // Aplicar filtros
        if (filtroSiembra) {
            tabla.column(1).search(filtroSiembra);
        }
        if (filtroEstado) {
            tabla.column(2).search(filtroEstado);
        }
        if (filtroFecha) {
            tabla.column(0).search(filtroFecha);
        }

        tabla.draw();
    }

    // Exportar monitoreos
    function exportarMonitoreos() {
        window.open('../AJAX/exportar_monitoreos.php', '_blank');
    }

    // Limpiar formulario
    function limpiarFormulario() {
        $('#formNuevoMonitoreo')[0].reset();
        $('#formNuevoMonitoreo .form-control, #formNuevoMonitoreo .form-select')
            .removeClass('is-valid is-invalid');
        
        // Restaurar valores por defecto
        $('#fechaObservacion').val(new Date().toISOString().split('T')[0]);
        $('#horaObservacion').val(new Date().toTimeString().split(' ')[0].substring(0, 5));
        $('#humedadSuelo').val('humedo');
        $('#presenciaPlagas').val('ninguna');
        $('#presenciaEnfermedades').val('ninguna');
        
        // Restaurar título del modal
        $('#modalNuevoMonitoreo .modal-title').html('<i class="fas fa-plus me-2"></i>Nuevo Monitoreo');
        $('#btnGuardarMonitoreo').html('<i class="fas fa-save me-2"></i>Guardar Monitoreo');
        $('#btnGuardarMonitoreo').removeData('monitoreo-id');
        
        // Ocultar campos condicionales
        $('#tipoPlagas, #tipoEnfermedades').closest('.form-floating').hide();
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

    // Funciones auxiliares
    function formatearFecha(fecha) {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    // Inicializar campos condicionales al cargar
    toggleCampoTipoPlagas();
    toggleCampoTipoEnfermedades();
});