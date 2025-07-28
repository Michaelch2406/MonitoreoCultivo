$(document).ready(function() {
    // Inicializar DataTables
    const tablaSiembras = $('#tablaSiembras').DataTable({
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
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: -1 } // Desactivar ordenamiento en la columna de acciones
        ]
    });

    // Variables globales
    let siembraEditando = null;

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

    // Calcular fecha estimada de cosecha automáticamente
    $('#tipoCultivoId').on('change', function() {
        const cicloSelect = $(this).find(':selected');
        const cicloDias = cicloSelect.data('ciclo');
        const fechaSiembra = $('#fechaSiembra').val();
        
        if (cicloDias && fechaSiembra) {
            const fecha = new Date(fechaSiembra);
            fecha.setDate(fecha.getDate() + parseInt(cicloDias));
            
            const fechaEstimada = fecha.toISOString().split('T')[0];
            $('#fechaEstimadaCosecha').val(fechaEstimada);
        }
    });

    // Recalcular fecha cuando cambie la fecha de siembra
    $('#fechaSiembra').on('change', function() {
        const fechaSiembra = $(this).val();
        const cicloSelect = $('#tipoCultivoId').find(':selected');
        const cicloDias = cicloSelect.data('ciclo');
        
        if (cicloDias && fechaSiembra) {
            const fecha = new Date(fechaSiembra);
            fecha.setDate(fecha.getDate() + parseInt(cicloDias));
            
            const fechaEstimada = fecha.toISOString().split('T')[0];
            $('#fechaEstimadaCosecha').val(fechaEstimada);
        }
    });

    // Limpiar formulario
    function limpiarFormulario() {
        $('#formNuevaSiembra')[0].reset();
        $('#fechaSiembra').val(new Date().toISOString().split('T')[0]);
        $('#fechaEstimadaCosecha').val('');
        siembraEditando = null;
        
        // Cambiar título del modal
        $('.modal-title').html('<i class="fas fa-plus me-2"></i>Nueva Siembra');
        $('#btnGuardarSiembra').html('<i class="fas fa-save me-2"></i>Guardar Siembra');
    }

    // Abrir modal nueva siembra
    $('#modalNuevaSiembra').on('show.bs.modal', function() {
        if (!siembraEditando) {
            limpiarFormulario();
        }
    });

    // Cerrar modal
    $('#modalNuevaSiembra').on('hidden.bs.modal', function() {
        limpiarFormulario();
    });

    // Guardar siembra
    $('#btnGuardarSiembra').on('click', function() {
        const formData = new FormData($('#formNuevaSiembra')[0]);
        
        // Validaciones básicas
        if (!formData.get('lote_id')) {
            mostrarAlerta('error', 'Error', 'Debe seleccionar un lote');
            return;
        }
        
        if (!formData.get('tipo_cultivo_id')) {
            mostrarAlerta('error', 'Error', 'Debe seleccionar un tipo de cultivo');
            return;
        }
        
        if (!formData.get('fecha_siembra')) {
            mostrarAlerta('error', 'Error', 'Debe especificar la fecha de siembra');
            return;
        }

        mostrarCargando();

        const url = siembraEditando ? 
            'AJAX/actualizar_siembra.php' : 
            'AJAX/crear_siembra.php';
            
        if (siembraEditando) {
            formData.append('siembra_id', siembraEditando);
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
                    $('#modalNuevaSiembra').modal('hide');
                    
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

    // Ver detalles de siembra
    $(document).on('click', '.btn-ver', function() {
        const siembraId = $(this).data('siembra-id');
        
        mostrarCargando();
        
        $.ajax({
            url: 'AJAX/obtener_siembra.php',
            type: 'POST',
            data: { siembra_id: siembraId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    const siembra = response.siembra;
                    
                    let detallesHtml = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Información Básica</h6>
                                <p><strong>Cultivo:</strong> ${siembra.tip_nombre}</p>
                                <p><strong>Categoría:</strong> ${siembra.tip_categoria}</p>
                                <p><strong>Lote:</strong> ${siembra.lot_nombre}</p>
                                <p><strong>Finca:</strong> ${siembra.fin_nombre}</p>
                                <p><strong>Área:</strong> ${siembra.lot_area} ha</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success"><i class="fas fa-calendar me-2"></i>Fechas</h6>
                                <p><strong>Fecha Siembra:</strong> ${new Date(siembra.sie_fecha_siembra).toLocaleDateString('es-ES')}</p>
                                <p><strong>Fecha Est. Cosecha:</strong> ${siembra.sie_fecha_estimada_cosecha ? new Date(siembra.sie_fecha_estimada_cosecha).toLocaleDateString('es-ES') : 'No estimada'}</p>
                                <p><strong>Ciclo:</strong> ${siembra.tip_ciclo_dias} días</p>
                                <p><strong>Estado:</strong> <span class="badge estado-${siembra.sie_estado}">${siembra.sie_estado.replace('_', ' ').toUpperCase()}</span></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-warning"><i class="fas fa-seedling me-2"></i>Detalles de Siembra</h6>
                                <p><strong>Cantidad Semilla:</strong> ${siembra.sie_cantidad_semilla ? siembra.sie_cantidad_semilla + ' ' + siembra.sie_unidad_semilla : 'No especificada'}</p>
                                <p><strong>Densidad:</strong> ${siembra.sie_densidad_siembra || 'No especificada'}</p>
                                <p><strong>Método:</strong> ${siembra.sie_metodo_siembra}</p>
                                <p><strong>Responsable:</strong> ${siembra.responsable_nombre}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-info"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                                <p>${siembra.sie_observaciones || 'Sin observaciones'}</p>
                            </div>
                        </div>
                    `;
                    
                    $('#detallesSiembra').html(detallesHtml);
                    $('#modalVerSiembra').modal('show');
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

    // Ver actividades de siembra
    $(document).on('click', '.btn-actividades', function() {
        const siembraId = $(this).data('siembra-id');
        window.location.href = `actividades.php?siembra_id=${siembraId}`;
    });

    // Editar siembra
    $(document).on('click', '.btn-editar', function() {
        const siembraId = $(this).data('siembra-id');
        siembraEditando = siembraId;
        
        mostrarCargando();
        
        $.ajax({
            url: 'AJAX/obtener_siembra.php',
            type: 'POST',
            data: { siembra_id: siembraId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    const siembra = response.siembra;
                    
                    // Llenar el formulario
                    $('#loteId').val(siembra.sie_lote_id);
                    $('#tipoCultivoId').val(siembra.sie_tipo_cultivo_id);
                    $('#fechaSiembra').val(siembra.sie_fecha_siembra);
                    $('#fechaEstimadaCosecha').val(siembra.sie_fecha_estimada_cosecha);
                    $('#cantidadSemilla').val(siembra.sie_cantidad_semilla);
                    $('#unidadSemilla').val(siembra.sie_unidad_semilla);
                    $('#densidadSiembra').val(siembra.sie_densidad_siembra);
                    $('#metodoSiembra').val(siembra.sie_metodo_siembra);
                    $('#estadoSiembra').val(siembra.sie_estado);
                    $('#observaciones').val(siembra.sie_observaciones);
                    
                    // Cambiar título del modal
                    $('.modal-title').html('<i class="fas fa-edit me-2"></i>Editar Siembra');
                    $('#btnGuardarSiembra').html('<i class="fas fa-save me-2"></i>Actualizar Siembra');
                    
                    $('#modalNuevaSiembra').modal('show');
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

    // Eliminar siembra
    $(document).on('click', '.btn-eliminar', function() {
        const siembraId = $(this).data('siembra-id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará la siembra y no se puede deshacer',
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
                    url: 'AJAX/eliminar_siembra.php',
                    type: 'POST',
                    data: { siembra_id: siembraId },
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
    $('#filtroLote, #filtroEstado, #filtroCultivo').on('change', function() {
        aplicarFiltros();
    });

    function aplicarFiltros() {
        const filtroLote = $('#filtroLote').val();
        const filtroEstado = $('#filtroEstado').val();
        const filtroCultivo = $('#filtroCultivo').val();
        
        tablaSiembras.columns().search('').draw();
        
        if (filtroLote) {
            tablaSiembras.column(2).search(filtroLote, false, false);
        }
        
        if (filtroEstado) {
            tablaSiembras.column(3).search(filtroEstado, false, false);
        }
        
        if (filtroCultivo) {
            tablaSiembras.column(1).search(filtroCultivo, false, false);
        }
        
        tablaSiembras.draw();
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
    $('#formNuevaSiembra input, #formNuevaSiembra select, #formNuevaSiembra textarea').on('blur', function() {
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

    // Tooltips
    $('[title]').tooltip();

    // Responsive DataTables
    $(window).on('resize', function() {
        tablaSiembras.columns.adjust().responsive.recalc();
    });
});