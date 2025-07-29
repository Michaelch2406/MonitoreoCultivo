/**
 * JavaScript para el Módulo de Cultivos - AgroMonitor
 * Funcionalidades: CRUD, filtros, búsqueda, exportación
 */

$(document).ready(function() {
    // Variables globales
    let cultivosTable;
    let cultivosData = [];
    let filtrosActivos = {
        busqueda: '',
        categoria: '',
        ciclo: ''
    };

    // Inicializar componentes
    initializeDataTable();
    initializeEventListeners();
    loadCultivosData();
    loadEstadisticas();

    /**
     * Inicializar DataTable
     */
    function initializeDataTable() {
        cultivosTable = $('#cultivosTable').DataTable({
            language: {
                "decimal": "",
                "emptyTable": "No hay datos disponibles",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros coincidentes",
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
            pageLength: 25,
            pagingType: "full_numbers",
            order: [[0, 'asc']],
            columnDefs: [
                {
                    targets: -1, // Última columna (Acciones)
                    orderable: false,
                    searchable: false,
                    width: '150px',
                    className: 'text-center',
                    responsivePriority: 1
                },
                {
                    targets: 0, // Nombre (prioridad alta)
                    responsivePriority: 2,
                    className: 'text-start'
                },
                {
                    targets: [2, 3, 4, 5], // Categoría, Ciclo, Días, Estado
                    className: 'text-center'
                },
                {
                    targets: 1, // Nombre Científico (se oculta en móviles)
                    responsivePriority: 10000,
                    className: 'text-start d-none d-md-table-cell'
                },
                {
                    targets: 2, // Categoría (prioridad media)
                    responsivePriority: 3
                },
                {
                    targets: 5, // Estado (prioridad alta)
                    responsivePriority: 4
                },
                {
                    targets: [3, 4], // Ciclo y Días (se ocultan en móviles)
                    responsivePriority: 5000,
                    className: 'text-center d-none d-lg-table-cell'
                },
                {
                    targets: 4, // Días
                    width: '80px'
                }
            ],
            drawCallback: function() {
                // Reinicializar tooltips después de redibujar
                initializeTooltips();
                // Aplicar animaciones
                $('.fade-in-up').removeClass('fade-in-up');
                $('#cultivosTable tbody tr').addClass('fade-in-up');
            }
        });
    }

    /**
     * Inicializar event listeners
     */
    function initializeEventListeners() {
        // Búsqueda en tiempo real
        $('#searchInput').on('keyup', function() {
            const searchTerm = $(this).val();
            filtrosActivos.busqueda = searchTerm;
            aplicarFiltros();
        });

        // Filtros de categoría y ciclo
        $('#categoriaFilter, #cicloFilter').on('change', function() {
            const categoria = $('#categoriaFilter').val();
            const ciclo = $('#cicloFilter').val();
            
            filtrosActivos.categoria = categoria;
            filtrosActivos.ciclo = ciclo;
            
            aplicarFiltros();
            actualizarEstadisticas();
        });

        // Limpiar filtros
        $('#clearFilters').on('click', function() {
            $('#searchInput').val('');
            $('#categoriaFilter').val('');
            $('#cicloFilter').val('');
            
            filtrosActivos = {
                busqueda: '',
                categoria: '',
                ciclo: ''
            };
            
            aplicarFiltros();
            loadEstadisticas();
        });

        // Click en tarjetas de estadísticas para filtrar
        $('.stat-card').on('click', function() {
            if ($(this).hasClass('total')) {
                // Limpiar filtros
                $('#clearFilters').click();
            } else {
                const categoria = $(this).hasClass('cereales') ? 'cereales' :
                                $(this).hasClass('hortalizas') ? 'hortalizas' :
                                $(this).hasClass('frutales') ? 'frutales' : '';
                
                if (categoria) {
                    $('#categoriaFilter').val(categoria).trigger('change');
                }
            }
        });

        // Acciones de la tabla (delegación de eventos)
        $(document).on('click', '.btn-view', function() {
            const id = $(this).data('id');
            verDetalleCultivo(id);
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            editarCultivo(id);
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');
            confirmarEliminacion(id, nombre);
        });

        $(document).on('click', '.btn-toggle-estado', function() {
            const id = $(this).data('id');
            const estadoActual = $(this).data('estado');
            const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';
            cambiarEstadoCultivo(id, nuevoEstado);
        });

        // Confirmación de eliminación
        $('#confirmAction').on('click', function() {
            const action = $(this).data('action');
            const id = $(this).data('id');
            
            if (action === 'delete') {
                eliminarCultivo(id);
            }
            
            $('#confirmModal').modal('hide');
        });
    }

    /**
     * Cargar datos de cultivos
     */
    function loadCultivosData() {
        console.log('Cargando datos de cultivos...');
        
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'GET',
            data: { action: 'listar' },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta recibida:', response);
                
                if (response && response.success) {
                    cultivosData = response.cultivos || [];
                    renderizarTablaCultivos(cultivosData);
                } else {
                    console.error('Error en respuesta:', response);
                    // Mostrar mensaje de error pero continuar
                    renderizarTablaCultivos([]);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', {xhr: xhr, status: status, error: error});
                console.error('Respuesta del servidor:', xhr.responseText);
                // Mostrar mensaje de error pero continuar
                renderizarTablaCultivos([]);
            }
        });
    }

    /**
     * Renderizar tabla de cultivos
     */
    function renderizarTablaCultivos(cultivos) {
        cultivosTable.clear();
        
        if (cultivos.length === 0) {
            // Mostrar estado vacío
            $('#cultivosTable tbody').html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-seedling"></i>
                            <h4>No hay cultivos registrados</h4>
                            <p>Comienza agregando tu primer tipo de cultivo al catálogo</p>
                        </div>
                    </td>
                </tr>
            `);
            return;
        }

        cultivos.forEach(function(cultivo) {
            const fila = [
                `<strong>${cultivo.tip_nombre}</strong>`,
                cultivo.tip_nombre_cientifico || '<em>No especificado</em>',
                `<span class="badge categoria-${cultivo.tip_categoria}">${formatCategoria(cultivo.tip_categoria)}</span>`,
                `<span class="badge bg-info text-dark">${formatCicloVida(cultivo.tip_ciclo_vida)}</span>`,
                cultivo.tip_ciclo_dias ? `<span class="badge bg-light text-dark">${cultivo.tip_ciclo_dias} días</span>` : '<span class="text-muted">N/A</span>',
                `<span class="badge ${getEstadoBadgeClass(cultivo.tip_estado)}">${formatEstado(cultivo.tip_estado)}</span>`,
                generarBotonesAccion(cultivo)
            ];
            
            cultivosTable.row.add(fila);
        });
        
        cultivosTable.draw();
        initializeTooltips();
    }

    /**
     * Aplicar filtros a los datos
     */
    function aplicarFiltros() {
        let cultivosFiltrados = [...cultivosData];
        
        // Filtro de búsqueda
        if (filtrosActivos.busqueda) {
            const termino = filtrosActivos.busqueda.toLowerCase();
            cultivosFiltrados = cultivosFiltrados.filter(cultivo => 
                cultivo.tip_nombre.toLowerCase().includes(termino) ||
                (cultivo.tip_nombre_cientifico && cultivo.tip_nombre_cientifico.toLowerCase().includes(termino)) ||
                (cultivo.tip_descripcion && cultivo.tip_descripcion.toLowerCase().includes(termino))
            );
        }
        
        // Filtro de categoría
        if (filtrosActivos.categoria) {
            cultivosFiltrados = cultivosFiltrados.filter(cultivo => 
                cultivo.tip_categoria === filtrosActivos.categoria
            );
        }
        
        // Filtro de ciclo de vida
        if (filtrosActivos.ciclo) {
            cultivosFiltrados = cultivosFiltrados.filter(cultivo => 
                cultivo.tip_ciclo_vida === filtrosActivos.ciclo
            );
        }
        
        renderizarTablaCultivos(cultivosFiltrados);
    }

    /**
     * Cargar estadísticas
     */
    function loadEstadisticas() {
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'GET',
            data: { action: 'estadisticas' },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    actualizarTarjetasEstadisticas(response.estadisticas);
                } else {
                    console.error('Error al cargar estadísticas:', response);
                    // Usar estadísticas vacías
                    actualizarTarjetasEstadisticas({
                        cereales: 0,
                        hortalizas: 0,
                        frutales: 0,
                        total: 0
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX estadísticas:', error);
                // Usar estadísticas vacías
                actualizarTarjetasEstadisticas({
                    cereales: 0,
                    hortalizas: 0,
                    frutales: 0,
                    total: 0
                });
            }
        });
    }

    /**
     * Actualizar estadísticas basadas en filtros
     */
    function actualizarEstadisticas() {
        let cultivosFiltrados = [...cultivosData];
        
        if (filtrosActivos.categoria) {
            cultivosFiltrados = cultivosFiltrados.filter(cultivo => 
                cultivo.tip_categoria === filtrosActivos.categoria
            );
        }
        
        if (filtrosActivos.ciclo) {
            cultivosFiltrados = cultivosFiltrados.filter(cultivo => 
                cultivo.tip_ciclo_vida === filtrosActivos.ciclo
            );
        }
        
        const estadisticas = {
            cereales: cultivosFiltrados.filter(c => c.tip_categoria === 'cereales').length,
            hortalizas: cultivosFiltrados.filter(c => c.tip_categoria === 'hortalizas').length,
            frutales: cultivosFiltrados.filter(c => c.tip_categoria === 'frutales').length,
            total: cultivosFiltrados.length
        };
        
        actualizarTarjetasEstadisticas(estadisticas);
    }

    /**
     * Actualizar tarjetas de estadísticas
     */
    function actualizarTarjetasEstadisticas(stats) {
        animateNumber('#statCereales', stats.cereales || 0);
        animateNumber('#statHortalizas', stats.hortalizas || 0);
        animateNumber('#statFrutales', stats.frutales || 0);
        animateNumber('#statTotal', stats.total || 0);
    }

    /**
     * Ver detalle de un cultivo
     */
    function verDetalleCultivo(id) {
        $('#detailsContent').html('<div class="text-center"><div class="loading-spinner"></div></div>');
        $('#detailsModal').modal('show');
        
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'GET',
            data: { action: 'detalle', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderizarDetalleCultivo(response.cultivo);
                } else {
                    $('#detailsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar los detalles: ${response.message}
                        </div>
                    `);
                }
            },
            error: function() {
                $('#detailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error de conexión al cargar los detalles
                    </div>
                `);
            }
        });
    }

    /**
     * Renderizar detalles del cultivo
     */
    function renderizarDetalleCultivo(cultivo) {
        const html = `
            <div class="detail-section">
                <h5><i class="fas fa-info-circle me-2"></i>Información General</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Nombre Común</div>
                            <p class="detail-value">${cultivo.tip_nombre}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Nombre Científico</div>
                            <p class="detail-value">${cultivo.tip_nombre_cientifico || 'No especificado'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Familia Botánica</div>
                            <p class="detail-value">${cultivo.tip_familia_botanica || 'No especificada'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Categoría</div>
                            <p class="detail-value">
                                <span class="badge categoria-${cultivo.tip_categoria}">${formatCategoria(cultivo.tip_categoria)}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h5><i class="fas fa-calendar-alt me-2"></i>Ciclo de Vida</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Tipo de Ciclo</div>
                            <p class="detail-value">
                                <span class="badge ciclo-${cultivo.tip_ciclo_vida}">${formatCicloVida(cultivo.tip_ciclo_vida)}</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Duración del Ciclo</div>
                            <p class="detail-value">${cultivo.tip_ciclo_dias ? cultivo.tip_ciclo_dias + ' días' : 'No especificado'}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h5><i class="fas fa-thermometer-half me-2"></i>Requerimientos Técnicos</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Temperatura Mínima</div>
                            <p class="detail-value">${cultivo.tip_temperatura_min ? cultivo.tip_temperatura_min + '°C' : 'No especificada'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Temperatura Máxima</div>
                            <p class="detail-value">${cultivo.tip_temperatura_max ? cultivo.tip_temperatura_max + '°C' : 'No especificada'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">pH Mínimo</div>
                            <p class="detail-value">${cultivo.tip_ph_min || 'No especificado'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">pH Máximo</div>
                            <p class="detail-value">${cultivo.tip_ph_max || 'No especificado'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Tipo de Suelo</div>
                            <p class="detail-value">${cultivo.tip_tipo_suelo || 'No especificado'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Precipitación</div>
                            <p class="detail-value">${cultivo.tip_precipitacion || 'No especificada'}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h5><i class="fas fa-leaf me-2"></i>Información de Siembra</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Densidad de Siembra</div>
                            <p class="detail-value">${cultivo.tip_densidad_siembra || 'No especificada'}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Profundidad de Siembra</div>
                            <p class="detail-value">${cultivo.tip_profundidad_siembra || 'No especificada'}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${cultivo.tip_descripcion ? `
            <div class="detail-section">
                <h5><i class="fas fa-file-text me-2"></i>Descripción</h5>
                <div class="detail-item">
                    <p class="detail-value">${cultivo.tip_descripcion}</p>
                </div>
            </div>
            ` : ''}
        `;
        
        $('#detailsContent').html(html);
    }

    /**
     * Confirmar eliminación
     */
    function confirmarEliminacion(id, nombre) {
        $('#confirmMessage').html(`
            <p>¿Estás seguro de que deseas eliminar el cultivo <strong>"${nombre}"</strong>?</p>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Esta acción no se puede deshacer. Verifica que no haya siembras registradas con este tipo de cultivo.
            </div>
        `);
        
        $('#confirmAction')
            .removeClass('btn-danger btn-warning')
            .addClass('btn-danger')
            .text('Eliminar')
            .data('action', 'delete')
            .data('id', id);
        
        $('#confirmModal').modal('show');
    }

    /**
     * Eliminar cultivo
     */
    function eliminarCultivo(id) {
        showLoading();
        
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'POST',
            data: { 
                action: 'eliminar',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showSuccess(response.message);
                    loadCultivosData();
                    loadEstadisticas();
                } else {
                    showError(response.message);
                }
            },
            error: function() {
                hideLoading();
                showError('Error de conexión al eliminar el cultivo');
            }
        });
    }

    /**
     * Cambiar estado del cultivo
     */
    function cambiarEstadoCultivo(id, nuevoEstado) {
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'POST',
            data: { 
                action: 'cambiar_estado',
                id: id,
                estado: nuevoEstado
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    loadCultivosData();
                } else {
                    showError(response.message);
                }
            },
            error: function() {
                showError('Error de conexión al cambiar el estado');
            }
        });
    }

    /**
     * Generar botones de acción
     */
    function generarBotonesAccion(cultivo) {
        const permisos = window.userPermissions || {};
        let botones = '<div class="btn-group-actions d-flex justify-content-center gap-1">';
        
        // Botón ver (todos pueden ver)
        botones += `
            <button class="btn btn-outline-info btn-sm btn-view" 
                    data-id="${cultivo.tip_id}" 
                    title="Ver detalles">
                <i class="fas fa-eye"></i>
            </button>
        `;
        
        // Botón editar (solo administradores)
        if (permisos.cultivos && permisos.cultivos.editar) {
            botones += `
                <button class="btn btn-outline-primary btn-sm btn-edit" 
                        data-id="${cultivo.tip_id}" 
                        title="Editar cultivo">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        }
        
        // Botón eliminar (solo administradores)
        if (permisos.cultivos && permisos.cultivos.eliminar) {
            botones += `
                <button class="btn btn-outline-danger btn-sm btn-delete" 
                        data-id="${cultivo.tip_id}" 
                        data-nombre="${cultivo.tip_nombre}"
                        title="Eliminar cultivo">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }
        
        botones += '</div>';
        return botones;
    }

    /**
     * Funciones de formato
     */
    function formatCategoria(categoria) {
        const categorias = {
            'cereales': 'Cereales',
            'hortalizas': 'Hortalizas',
            'leguminosas': 'Leguminosas',
            'frutales': 'Frutales',
            'tuberculos': 'Tubérculos',
            'aromaticas': 'Aromáticas'
        };
        return categorias[categoria] || categoria;
    }

    function formatCicloVida(ciclo) {
        const ciclos = {
            'anual': 'Anual',
            'perenne': 'Perenne',
            'bianual': 'Bianual'
        };
        return ciclos[ciclo] || ciclo;
    }

    function formatEstado(estado) {
        const estados = {
            'activo': 'Activo',
            'inactivo': 'Inactivo'
        };
        return estados[estado] || estado;
    }

    function getEstadoBadgeClass(estado) {
        const clases = {
            'activo': 'bg-success',
            'inactivo': 'bg-secondary'
        };
        return clases[estado] || 'bg-secondary';
    }

    /**
     * Editar cultivo
     */
    function editarCultivo(id) {
        showLoading();
        
        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'GET',
            data: {
                action: 'detalle',
                id: id
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    console.log('Datos del cultivo:', response.cultivo); // Debug temporal
                    mostrarModalEditarCultivo(response.cultivo);
                } else {
                    showError(response.message || 'Error al cargar los datos del cultivo');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('Error al obtener cultivo:', error);
                showError('Error de conexión. Intente nuevamente.');
            }
        });
    }

    /**
     * Mostrar modal para editar cultivo
     */
    function mostrarModalEditarCultivo(cultivo) {
        // Remover modal existente si existe
        $('#modalEditarCultivo').remove();

        // Crear HTML del modal
        const modalHtml = `
            <div class="modal fade" id="modalEditarCultivo" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i>Editar Cultivo
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="formEditarCultivo">
                            <input type="hidden" name="id" value="${cultivo.tip_id}">
                            <input type="hidden" name="action" value="editar">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarNombreCultivo" class="form-label">Nombre del Cultivo *</label>
                                            <input type="text" class="form-control" id="editarNombreCultivo" 
                                                   name="nombre" value="${cultivo.tip_nombre}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarNombreCientifico" class="form-label">Nombre Científico</label>
                                            <input type="text" class="form-control" id="editarNombreCientifico" 
                                                   name="nombre_cientifico" value="${cultivo.tip_nombre_cientifico || ''}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarCategoria" class="form-label">Categoría *</label>
                                            <select class="form-select" id="editarCategoria" name="categoria" required>
                                                <option value="">Seleccionar categoría</option>
                                                <option value="cereales" ${(cultivo.tip_categoria || '') === 'cereales' ? 'selected' : ''}>Cereales</option>
                                                <option value="legumbres" ${(cultivo.tip_categoria || '') === 'legumbres' ? 'selected' : ''}>Legumbres</option>
                                                <option value="frutas" ${(cultivo.tip_categoria || '') === 'frutas' ? 'selected' : ''}>Frutas</option>
                                                <option value="verduras" ${(cultivo.tip_categoria || '') === 'verduras' ? 'selected' : ''}>Verduras</option>
                                                <option value="tuberculos" ${(cultivo.tip_categoria || '') === 'tuberculos' ? 'selected' : ''}>Tubérculos</option>
                                                <option value="especias" ${(cultivo.tip_categoria || '') === 'especias' ? 'selected' : ''}>Especias</option>
                                                <option value="otros" ${(cultivo.tip_categoria || '') === 'otros' ? 'selected' : ''}>Otros</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarCiclo" class="form-label">Ciclo *</label>
                                            <select class="form-select" id="editarCiclo" name="ciclo" required>
                                                <option value="">Seleccionar ciclo</option>
                                                <option value="anual" ${(cultivo.tip_ciclo_vida || '') === 'anual' ? 'selected' : ''}>Anual</option>
                                                <option value="bianual" ${(cultivo.tip_ciclo_vida || '') === 'bianual' ? 'selected' : ''}>Bianual</option>
                                                <option value="perenne" ${(cultivo.tip_ciclo_vida || '') === 'perenne' ? 'selected' : ''}>Perenne</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarDiasCosecha" class="form-label">Días hasta Cosecha</label>
                                            <input type="number" class="form-control" id="editarDiasCosecha" 
                                                   name="dias_cosecha" value="${cultivo.tip_ciclo_dias || ''}" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editarEstado" class="form-label">Estado *</label>
                                            <select class="form-select" id="editarEstado" name="estado" required>
                                                <option value="activo" ${(cultivo.tip_estado || '') === 'activo' ? 'selected' : ''}>Activo</option>
                                                <option value="inactivo" ${(cultivo.tip_estado || '') === 'inactivo' ? 'selected' : ''}>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="editarDescripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="editarDescripcion" name="descripcion" 
                                                      rows="3" placeholder="Descripción del cultivo">${cultivo.tip_descripcion || ''}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Añadir modal al DOM
        $('body').append(modalHtml);

        // Configurar evento de submit del formulario
        $('#formEditarCultivo').on('submit', function(e) {
            e.preventDefault();
            guardarCultivoEditado();
        });

        // Mostrar modal
        $('#modalEditarCultivo').modal('show');

        // Limpiar modal al cerrarse
        $('#modalEditarCultivo').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    }

    /**
     * Guardar cultivo editado
     */
    function guardarCultivoEditado() {
        const formData = new FormData(document.getElementById('formEditarCultivo'));
        
        showLoading();

        $.ajax({
            url: '../AJAX/cultivos_ajax.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showSuccess('Cultivo actualizado exitosamente');
                    $('#modalEditarCultivo').modal('hide');
                    // Recargar datos
                    loadCultivosData();
                    loadEstadisticas();
                } else {
                    showError(response.message || 'Error al actualizar el cultivo');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('Error al guardar cultivo:', error);
                showError('Error de conexión. Intente nuevamente.');
            }
        });
    }

    /**
     * Funciones de utilidad
     */
    function initializeTooltips() {
        $('[title]').tooltip({
            placement: 'top',
            trigger: 'hover'
        });
    }

    function animateNumber(selector, finalNumber) {
        const $element = $(selector);
        const currentNumber = parseInt($element.text()) || 0;
        
        $({ number: currentNumber }).animate({ number: finalNumber }, {
            duration: 1000,
            easing: 'swing',
            step: function() {
                $element.text(Math.floor(this.number));
            },
            complete: function() {
                $element.text(finalNumber);
            }
        });
    }

    function showLoading() {
        // Implementar loading global si es necesario
    }

    function hideLoading() {
        // Ocultar loading global
    }

    function showSuccess(message) {
        // Usar el sistema de notificaciones global
        if (window.showNotification) {
            window.showNotification(message, 'success');
        } else {
            alert(message);
        }
    }

    function showError(message) {
        // Usar el sistema de notificaciones global
        if (window.showNotification) {
            window.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }

    // Exponer funciones públicas si es necesario
    window.CultivosModule = {
        reloadData: loadCultivosData,
        reloadStats: loadEstadisticas
    };
});