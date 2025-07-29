// Configuración global - Las variables se definen en reportes.php
let PERMISOS_USUARIO = window.PERMISOS_USUARIO || {};
let ROL_USUARIO = window.ROL_USUARIO || 'agricultor';

// Variables globales para los gráficos
let flujoCajaChart, rendimientoLoteChart, costosCategoriaChart, costosEvolucionChart, plagasChart, enfermedadesChart, efectividadTratamientosChart;

// Inicialización cuando el documento esté listo
$(document).ready(function() {
    inicializarEventos();
    cargarDatosIniciales();
    inicializarDataTables();
    inicializarGraficos();
});


function inicializarEventos() {
    // Eventos de las pestañas
    $('#reportesTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).data('bs-target');
        
        switch(target) {
            case '#produccion':
                cargarOpcionesFiltros();
                cargarReporteCosechas();
                cargarReporteRendimiento();
                break;
            case '#financiero':
                cargarReportesFinancieros();
                break;
            case '#tecnico':
                cargarReportesTecnicos();
                break;
        }
    });
    
    // Eventos de filtros
    $('#filtrarProduccion').on('click', filtrarReportesProduccion);
    $('#limpiarFiltrosProduccion').on('click', limpiarFiltrosProduccion);
    
    // Eventos de exportación
    $('#exportarPDF').on('click', () => exportarReporte('pdf'));
    $('#exportarExcel').on('click', () => exportarReporte('excel'));
    $('#exportarCSV').on('click', () => exportarReporte('csv'));
}

function cargarDatosIniciales() {
    // Cargar datos de filtros
    cargarCultivosParaFiltro();
    cargarLotesParaFiltro();
    
    // Cargar datos iniciales de la pestaña de producción (activa por defecto)
    cargarReporteCosechas();
    cargarReporteRendimiento();
}

function inicializarDataTables() {
    // Configuración global para DataTables en español
    const dataTableConfig = {
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            },
            aria: {
                sortAscending: ": Activar para ordenar la columna de manera ascendente",
                sortDescending: ": Activar para ordenar la columna de manera descendente"
            }
        },
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex align-items-center"f>>rtip',
        pagingType: "full_numbers",
        ordering: true,
        searching: true,
        info: true,
        autoWidth: false
    };

    // Inicializar todas las DataTables que existen en la página
    const tablesIds = [
        '#tablaProduccion',
        '#tablaFinanzas', 
        '#tablaActividades',
        '#tablaMonitoreo',
        '#tablaInsumos'
    ];

    tablesIds.forEach(tableId => {
        if ($(tableId).length) {
            // Destruir tabla existente si ya fue inicializada
            if ($.fn.dataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }
            
            // Inicializar DataTable con configuración
            $(tableId).DataTable(dataTableConfig);
            
            console.log(`DataTable inicializada para: ${tableId}`);
        }
    });
}


function inicializarGraficos() {

    // Gráfico de Rendimiento por Lote
    const ctx4 = document.getElementById('rendimientoLoteChart');
    if (ctx4) {
        rendimientoLoteChart = new Chart(ctx4.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Rendimiento (kg/ha)',
                    data: [],
                    backgroundColor: '#2E7D32',
                    borderColor: '#1B5E20',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + 
                                       context.parsed.y.toFixed(2) + ' kg/ha';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Rendimiento (kg/ha)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Lotes'
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Distribución de Costos
    const ctx5 = document.getElementById('costosCategoriaChart');
    if (ctx5) {
        costosCategoriaChart = new Chart(ctx5.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#2E7D32', '#4CAF50', '#81C784', '#8D6E63', 
                        '#1976D2', '#FFA726', '#FF9800', '#F44336'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toLocaleString() + 
                                       ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Evolución de Costos
    const ctx6 = document.getElementById('costosEvolucionChart');
    if (ctx6) {
        costosEvolucionChart = new Chart(ctx6.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Total Costos',
                    data: [],
                    borderColor: '#2E7D32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#2E7D32',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + 
                                       context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Costos ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Período'
                        }
                    }
                }
            }
        });
    }

    // Cargar rendimiento por lote
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'rendimiento_lotes' },
        success: function(response) {
            if (response.success && rendimientoLoteChart) {
                rendimientoLoteChart.data.labels = response.rendimiento.map(item => 
                    item.lote_nombre || 'Lote ' + item.lote_id
                );
                rendimientoLoteChart.data.datasets[0].data = response.rendimiento.map(item => 
                    parseFloat(item.rendimiento_promedio) || 0
                );
                rendimientoLoteChart.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando rendimiento por lote:', error);
        }
    });
}

function cargarCultivosParaFiltro() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'cultivos_filtro' },
        success: function(response) {
            if (response.success) {
                const select = $('#cultivoProduccion');
                select.empty().append('<option value="">Todos los cultivos</option>');
                
                response.cultivos.forEach(cultivo => {
                    select.append(`<option value="${cultivo.tip_id}">${cultivo.tip_nombre}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error cargando cultivos para filtro:', error);
        }
    });
}

function cargarLotesParaFiltro() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'lotes_filtro' },
        success: function(response) {
            if (response.success) {
                const select = $('#loteProduccion');
                select.empty().append('<option value="">Todos los lotes</option>');
                
                response.lotes.forEach(lote => {
                    select.append(`<option value="${lote.lot_id}">${lote.lot_nombre} (${lote.finca})</option>`);
                });
            }
        }
    });
}

function cargarOpcionesFiltros() {
    // Esta función se ejecuta cuando se activa la pestaña de producción
    // Ya se cargan los filtros al inicio, pero se puede usar para actualizar
}

function filtrarReportesProduccion() {
    // Obtener valores de filtros
    const filtros = {
        fecha_inicio: $('#fechaInicioProduccion').val() || null,
        fecha_fin: $('#fechaFinProduccion').val() || null,
        cultivo_id: $('#cultivoProduccion').val() || null,
        lote_id: $('#loteProduccion').val() || null
    };
    
    // Cargar reporte de cosechas
    cargarReporteCosechas(filtros);
    
    // Cargar reporte de rendimiento
    cargarReporteRendimiento(filtros);
}

function limpiarFiltrosProduccion() {
    $('#fechaInicioProduccion, #fechaFinProduccion').val('');
    $('#cultivoProduccion, #loteProduccion').val('');
    
    // Recargar reportes sin filtros
    filtrarReportesProduccion();
}

function cargarReporteCosechas(filtros = {}) {
    $('#loadingCosechas').show();
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'reporte_cosechas', ...filtros },
        success: function(response) {
            $('#loadingCosechas').hide();
            
            if (response.success) {
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaCosechas')) {
                    $('#tablaCosechas').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaCosechas tbody').empty();
                
                // Agregar filas
                response.cosechas.forEach(cosecha => {
                    const fila = `
                        <tr>
                            <td>${cosecha.cos_fecha_cosecha}</td>
                            <td>${cosecha.cultivo}</td>
                            <td>${cosecha.lote}</td>
                            <td>${parseFloat(cosecha.cos_cantidad_cosechada).toFixed(2)} ${cosecha.cos_unidad}</td>
                            <td><span class="badge bg-${obtenerColorCalidad(cosecha.cos_calidad)}">${cosecha.cos_calidad}</span></td>
                            <td>$${parseFloat(cosecha.cos_precio_venta_unitario || 0).toFixed(2)}</td>
                            <td>$${parseFloat(cosecha.cos_total_ingresos || 0).toFixed(2)}</td>
                            <td>${parseFloat(cosecha.rendimiento_hectarea || 0).toFixed(2)}</td>
                        </tr>
                    `;
                    $('#tablaCosechas tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaCosechas').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    pagingType: "full_numbers"
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingCosechas').hide();
            mostrarAlerta('error', 'Error al cargar el reporte de cosechas');
        }
    });
}

function cargarReporteRendimiento(filtros = {}) {
    $('#loadingRendimiento').show();
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'reporte_rendimiento', ...filtros },
        success: function(response) {
            $('#loadingRendimiento').hide();
            
            if (response.success) {
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaRendimiento')) {
                    $('#tablaRendimiento').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaRendimiento tbody').empty();
                
                // Agregar filas
                response.rendimientos.forEach(rendimiento => {
                    const fila = `
                        <tr>
                            <td>${rendimiento.cultivo}</td>
                            <td><span class="badge bg-info">${rendimiento.categoria}</span></td>
                            <td>${rendimiento.total_cosechas}</td>
                            <td>${parseFloat(rendimiento.cantidad_total).toFixed(2)}</td>
                            <td>${parseFloat(rendimiento.promedio_cosecha).toFixed(2)}</td>
                            <td>${parseFloat(rendimiento.rendimiento_promedio_hectarea).toFixed(2)}</td>
                            <td>${Math.round(rendimiento.promedio_dias_cultivo)} días</td>
                        </tr>
                    `;
                    $('#tablaRendimiento tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaRendimiento').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    pagingType: "full_numbers"
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingRendimiento').hide();
            mostrarAlerta('error', 'Error al cargar el reporte de rendimiento');
        }
    });
}

function cargarReportesFinancieros() {
    cargarEstadoResultados();
    cargarFlujoCaja();
}

function cargarEstadoResultados() {
    $('#loadingResultados').show();
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'estado_resultados' },
        success: function(response) {
            $('#loadingResultados').hide();
            
            if (response.success) {
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaResultados')) {
                    $('#tablaResultados').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaResultados tbody').empty();
                
                // Agregar filas
                response.resultados.forEach(resultado => {
                    const fila = `
                        <tr>
                            <td>${resultado.cultivo}</td>
                            <td>${resultado.total_siembras}</td>
                            <td class="text-success">$${parseFloat(resultado.total_ingresos).toFixed(2)}</td>
                            <td class="text-danger">$${parseFloat(resultado.total_gastos).toFixed(2)}</td>
                            <td class="${resultado.utilidad_bruta >= 0 ? 'text-success' : 'text-danger'}">
                                $${parseFloat(resultado.utilidad_bruta).toFixed(2)}
                            </td>
                            <td>${parseFloat(resultado.margen_utilidad).toFixed(1)}%</td>
                            <td>${parseFloat(resultado.area_total).toFixed(2)} ha</td>
                        </tr>
                    `;
                    $('#tablaResultados tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaResultados').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    pagingType: "full_numbers"
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingResultados').hide();
            mostrarAlerta('error', 'Error al cargar el estado de resultados');
        }
    });
}

function cargarFlujoCaja() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'flujo_caja' },
        success: function(response) {
            if (response.success) {
                // Si el gráfico ya existe, destruirlo
                if (flujoCajaChart) {
                    flujoCajaChart.destroy();
                }
                
                const ctx = document.getElementById('flujoCajaChart').getContext('2d');
                flujoCajaChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.flujo.map(item => item.periodo),
                        datasets: [
                            {
                                label: 'Ingresos',
                                data: response.flujo.map(item => parseFloat(item.ingresos)),
                                borderColor: '#4CAF50',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                fill: false
                            },
                            {
                                label: 'Gastos',
                                data: response.flujo.map(item => parseFloat(item.gastos)),
                                borderColor: '#FF9800',
                                backgroundColor: 'rgba(255, 152, 0, 0.1)',
                                fill: false
                            },
                            {
                                label: 'Flujo Neto',
                                data: response.flujo.map(item => parseFloat(item.flujo_neto)),
                                borderColor: '#2E7D32',
                                backgroundColor: 'rgba(46, 125, 50, 0.1)',
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },
        error: function() {
            mostrarAlerta('error', 'Error al cargar el flujo de caja');
        }
    });
}

function cargarReportesTecnicos() {
    cargarEstadisticasFitosanitarias();
    cargarHistorialActividades();
    cargarRegistroMonitoreo();
    cargarGraficosControlFitosanitario();
    cargarReporteInsumos();
}

function cargarEstadisticasFitosanitarias() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'estadisticas_fitosanitarias' },
        success: function(response) {
            if (response.success) {
                $('#cultivos-sanos').text(response.estadisticas.cultivos_sanos || 0);
                $('#plagas-detectadas').text(response.estadisticas.plagas_detectadas || 0);
                $('#enfermedades-activas').text(response.estadisticas.enfermedades_activas || 0);
                $('#tratamientos-aplicados').text(response.estadisticas.tratamientos_aplicados || 0);
            }
        },
        error: function() {
            console.error('Error al cargar estadísticas fitosanitarias');
        }
    });
}

function cargarReporteInsumos(filtros = {}) {
    $('#loadingInsumos').show();
    
    // Obtener filtros del formulario si no se proporcionan
    if (Object.keys(filtros).length === 0) {
        filtros = {
            fecha_inicio: $('#fechaInicioInsumos').val() || null,
            fecha_fin: $('#fechaFinInsumos').val() || null,
            tipo_insumo: $('#tipoInsumo').val() || null,
            cultivo_id: $('#cultivoInsumos').val() || null
        };
    }
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'uso_insumos', ...filtros },
        success: function(response) {
            $('#loadingInsumos').hide();
            
            if (response.success) {
                // Actualizar estadísticas
                actualizarEstadisticasInsumos(response.insumos);
                
                // Actualizar gráficos
                cargarGraficosInsumos(response.insumos);
                
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaInsumos')) {
                    $('#tablaInsumos').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaInsumos tbody').empty();
                
                // Agregar filas
                response.insumos.forEach(insumo => {
                    const eficiencia = calcularEficienciaInsumo(insumo);
                    const fila = `
                        <tr>
                            <td><span class="badge bg-secondary">${insumo.tipo_insumo}</span></td>
                            <td>${insumo.nombre_insumo}</td>
                            <td>${insumo.cultivo || 'N/A'}</td>
                            <td>${parseFloat(insumo.cantidad_total).toFixed(2)}</td>
                            <td>${insumo.unidad_medida || 'unidad'}</td>
                            <td class="text-success">$${parseFloat(insumo.costo_total).toFixed(2)}</td>
                            <td class="text-info">$${parseFloat(insumo.costo_unitario).toFixed(2)}</td>
                            <td>${insumo.lotes_aplicados}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-${eficiencia.color}" role="progressbar" 
                                        style="width: ${eficiencia.porcentaje}%" 
                                        title="${eficiencia.texto}">
                                        ${eficiencia.porcentaje}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                    $('#tablaInsumos tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaInsumos').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    order: [[5, 'desc']] // Ordenar por costo total descendente
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingInsumos').hide();
            mostrarAlerta('error', 'Error al cargar el reporte de uso de insumos');
        }
    });
}

function actualizarEstadisticasInsumos(insumos) {
    const totalInsumos = insumos.length;
    const costoTotal = insumos.reduce((sum, item) => sum + parseFloat(item.costo_total || 0), 0);
    const promedioCosto = totalInsumos > 0 ? costoTotal / totalInsumos : 0;
    const lotesAplicados = [...new Set(insumos.map(item => item.lotes_aplicados))].reduce((a, b) => a + b, 0);
    
    $('#total-insumos').text(totalInsumos);
    $('#costo-insumos').text('$' + costoTotal.toFixed(2));
    $('#promedio-costo').text('$' + promedioCosto.toFixed(2));
    $('#lotes-aplicados').text(lotesAplicados);
}

function cargarGraficosInsumos(insumos) {
    // Gráfico de distribución por tipo
    cargarGraficoTipoInsumos(insumos);
    
    // Gráfico de costo por cultivo
    cargarGraficoCostoCultivo(insumos);
}

function cargarGraficoTipoInsumos(insumos) {
    const tipoData = insumos.reduce((acc, item) => {
        acc[item.tipo_insumo] = (acc[item.tipo_insumo] || 0) + parseFloat(item.costo_total || 0);
        return acc;
    }, {});
    
    const ctx = document.getElementById('tipoInsumosChart');
    if (ctx) {
        // Destruir gráfico existente si existe
        if (window.tipoInsumosChart) {
            window.tipoInsumosChart.destroy();
        }
        
        window.tipoInsumosChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(tipoData),
                datasets: [{
                    data: Object.values(tipoData),
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.8)',
                        'rgba(33, 150, 243, 0.8)', 
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(156, 39, 176, 0.8)',
                        'rgba(255, 152, 0, 0.8)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
}

function cargarGraficoCostoCultivo(insumos) {
    const cultivoData = insumos.reduce((acc, item) => {
        const cultivo = item.cultivo || 'Sin especificar';
        acc[cultivo] = (acc[cultivo] || 0) + parseFloat(item.costo_total || 0);
        return acc;
    }, {});
    
    const ctx = document.getElementById('costoCultivoChart');
    if (ctx) {
        // Destruir gráfico existente si existe
        if (window.costoCultivoChart) {
            window.costoCultivoChart.destroy();
        }
        
        window.costoCultivoChart = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(cultivoData),
                datasets: [{
                    label: 'Costo Total ($)',
                    data: Object.values(cultivoData),
                    backgroundColor: 'rgba(76, 175, 80, 0.8)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
}

function calcularEficienciaInsumo(insumo) {
    // Calcular eficiencia basada en costo por lote
    const costoPorLote = parseFloat(insumo.costo_total) / Math.max(insumo.lotes_aplicados, 1);
    
    let porcentaje, color, texto;
    
    if (costoPorLote <= 100) {
        porcentaje = 90;
        color = 'success';
        texto = 'Muy eficiente';
    } else if (costoPorLote <= 200) {
        porcentaje = 75;
        color = 'info';
        texto = 'Eficiente';
    } else if (costoPorLote <= 300) {
        porcentaje = 60;
        color = 'warning';
        texto = 'Moderado';
    } else {
        porcentaje = 40;
        color = 'danger';
        texto = 'Revisar';
    }
    
    return { porcentaje, color, texto };
}

function limpiarFiltrosInsumos() {
    $('#fechaInicioInsumos').val('');
    $('#fechaFinInsumos').val('');
    $('#tipoInsumo').val('');
    $('#cultivoInsumos').val('');
    
    // Recargar datos sin filtros
    cargarReporteInsumos();
}

function cargarGraficosControlFitosanitario() {
    // Cargar los tres gráficos de control fitosanitario
    cargarGraficoPlagas();
    cargarGraficoEnfermedades();
    cargarGraficoEfectividadTratamientos();
}

function cargarGraficoPlagas() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'control_plagas' },
        success: function(response) {
            if (response.success) {
                // Si el gráfico ya existe, destruirlo
                if (plagasChart) {
                    plagasChart.destroy();
                }
                
                const ctx = document.getElementById('plagasChart').getContext('2d');
                plagasChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: response.plagas.map(item => item.tipo_plaga || 'Sin especificar'),
                        datasets: [{
                            label: 'Incidencia de Plagas',
                            data: response.plagas.map(item => parseInt(item.cantidad) || 0),
                            backgroundColor: [
                                'rgba(76, 175, 80, 0.8)',   // Verde - Nivel bajo
                                'rgba(255, 193, 7, 0.8)',   // Amarillo - Nivel medio
                                'rgba(255, 152, 0, 0.8)',   // Naranja - Nivel alto
                                'rgba(244, 67, 54, 0.8)',   // Rojo - Nivel crítico
                                'rgba(158, 158, 158, 0.8)'  // Gris - Sin datos
                            ],
                            borderColor: [
                                'rgba(76, 175, 80, 1)',
                                'rgba(255, 193, 7, 1)',
                                'rgba(255, 152, 0, 1)',
                                'rgba(244, 67, 54, 1)',
                                'rgba(158, 158, 158, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(46, 125, 50, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#2E7D32',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed * 100) / total).toFixed(1);
                                        return `${context.label}: ${context.parsed} casos (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                // Mostrar gráfico vacío con mensaje
                mostrarGraficoVacio('plagasChart', 'No hay datos de plagas disponibles');
            }
        },
        error: function() {
            mostrarGraficoVacio('plagasChart', 'Error al cargar datos de plagas');
        }
    });
}

function cargarGraficoEnfermedades() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'control_enfermedades' },
        success: function(response) {
            if (response.success) {
                // Si el gráfico ya existe, destruirlo
                if (enfermedadesChart) {
                    enfermedadesChart.destroy();
                }
                
                const ctx = document.getElementById('enfermedadesChart').getContext('2d');
                enfermedadesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: response.enfermedades.map(item => item.tipo_enfermedad || 'Sin especificar'),
                        datasets: [{
                            label: 'Casos de Enfermedades',
                            data: response.enfermedades.map(item => parseInt(item.cantidad) || 0),
                            backgroundColor: 'rgba(244, 67, 54, 0.8)',
                            borderColor: 'rgba(244, 67, 54, 1)',
                            borderWidth: 2,
                            borderRadius: 5,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(46, 125, 50, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#2E7D32',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return `Casos detectados: ${context.parsed.y}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Número de Casos'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Tipo de Enfermedad'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            } else {
                // Mostrar gráfico vacío con mensaje
                mostrarGraficoVacio('enfermedadesChart', 'No hay datos de enfermedades disponibles');
            }
        },
        error: function() {
            mostrarGraficoVacio('enfermedadesChart', 'Error al cargar datos de enfermedades');
        }
    });
}

function cargarGraficoEfectividadTratamientos() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'efectividad_tratamientos' },
        success: function(response) {
            if (response.success) {
                // Si el gráfico ya existe, destruirlo
                if (efectividadTratamientosChart) {
                    efectividadTratamientosChart.destroy();
                }
                
                const ctx = document.getElementById('efectividadTratamientosChart').getContext('2d');
                efectividadTratamientosChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: response.tratamientos.map(item => item.mes || 'Mes'),
                        datasets: [
                            {
                                label: 'Tratamientos Aplicados',
                                data: response.tratamientos.map(item => parseInt(item.aplicados) || 0),
                                borderColor: 'rgba(33, 150, 243, 1)',
                                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                fill: false,
                                tension: 0.4,
                                pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            },
                            {
                                label: 'Tratamientos Efectivos',
                                data: response.tratamientos.map(item => parseInt(item.efectivos) || 0),
                                borderColor: 'rgba(76, 175, 80, 1)',
                                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgba(76, 175, 80, 1)',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(46, 125, 50, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#2E7D32',
                                borderWidth: 1,
                                callbacks: {
                                    afterBody: function(tooltipItems) {
                                        const index = tooltipItems[0].dataIndex;
                                        const aplicados = response.tratamientos[index].aplicados || 0;
                                        const efectivos = response.tratamientos[index].efectivos || 0;
                                        const efectividad = aplicados > 0 ? ((efectivos / aplicados) * 100).toFixed(1) : 0;
                                        return `Efectividad: ${efectividad}%`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Número de Tratamientos'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Período'
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            } else {
                // Mostrar gráfico vacío con mensaje
                mostrarGraficoVacio('efectividadTratamientosChart', 'No hay datos de tratamientos disponibles');
            }
        },
        error: function() {
            mostrarGraficoVacio('efectividadTratamientosChart', 'Error al cargar datos de tratamientos');
        }
    });
}

function mostrarGraficoVacio(canvasId, mensaje) {
    const ctx = document.getElementById(canvasId);
    if (ctx) {
        const chart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Sin datos'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['rgba(158, 158, 158, 0.3)'],
                    borderColor: ['rgba(158, 158, 158, 0.5)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            },
            plugins: [{
                beforeDraw: function(chart) {
                    const ctx = chart.ctx;
                    const width = chart.width;
                    const height = chart.height;
                    
                    ctx.restore();
                    ctx.font = '16px Arial';
                    ctx.fillStyle = '#666';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(mensaje, width / 2, height / 2);
                    ctx.save();
                }
            }]
        });
        
        // Guardar referencia del gráfico
        if (canvasId === 'plagasChart') plagasChart = chart;
        else if (canvasId === 'enfermedadesChart') enfermedadesChart = chart;
        else if (canvasId === 'efectividadTratamientosChart') efectividadTratamientosChart = chart;
    }
}

function cargarHistorialActividades() {
    $('#loadingActividades').show();
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'historial_actividades' },
        success: function(response) {
            $('#loadingActividades').hide();
            
            if (response.success) {
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaActividades')) {
                    $('#tablaActividades').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaActividades tbody').empty();
                
                // Agregar filas
                response.actividades.forEach(actividad => {
                    const fila = `
                        <tr>
                            <td>${actividad.act_fecha}</td>
                            <td><span class="badge bg-primary">${actividad.act_tipo}</span></td>
                            <td>${actividad.lote}</td>
                            <td>${actividad.cultivo}</td>
                            <td>${actividad.act_descripcion || '-'}</td>
                            <td>${actividad.act_productos_utilizados || '-'}</td>
                            <td>$${parseFloat(actividad.act_costo || 0).toFixed(2)}</td>
                            <td>${actividad.responsable || '-'}</td>
                        </tr>
                    `;
                    $('#tablaActividades tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaActividades').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    order: [[0, 'desc']] // Ordenar por fecha descendente
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingActividades').hide();
            mostrarAlerta('error', 'Error al cargar el historial de actividades');
        }
    });
}

function cargarRegistroMonitoreo() {
    $('#loadingMonitoreo').show();
    
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        method: 'GET',
        data: { accion: 'registro_monitoreo' },
        success: function(response) {
            $('#loadingMonitoreo').hide();
            
            if (response.success) {
                // Destruir DataTable existente si existe
                if ($.fn.DataTable.isDataTable('#tablaMonitoreo')) {
                    $('#tablaMonitoreo').DataTable().destroy();
                }
                
                // Limpiar tbody
                $('#tablaMonitoreo tbody').empty();
                
                // Agregar filas
                response.monitoreos.forEach(monitoreo => {
                    const fila = `
                        <tr>
                            <td>${monitoreo.mon_fecha_observacion}</td>
                            <td>${monitoreo.lote}</td>
                            <td>${monitoreo.cultivo}</td>
                            <td><span class="badge bg-${obtenerColorEstado(monitoreo.mon_estado_general)}">${monitoreo.mon_estado_general}</span></td>
                            <td>${parseFloat(monitoreo.mon_altura_promedio || 0).toFixed(2)} cm</td>
                            <td>${parseFloat(monitoreo.mon_porcentaje_germinacion || 0).toFixed(1)}%</td>
                            <td><span class="badge bg-${obtenerColorPlagas(monitoreo.mon_presencia_plagas)}">${monitoreo.mon_presencia_plagas}</span></td>
                            <td><span class="badge bg-${obtenerColorEnfermedades(monitoreo.mon_presencia_enfermedades)}">${monitoreo.mon_presencia_enfermedades}</span></td>
                            <td>${monitoreo.responsable || '-'}</td>
                        </tr>
                    `;
                    $('#tablaMonitoreo tbody').append(fila);
                });
                
                // Inicializar DataTable
                $('#tablaMonitoreo').DataTable({
                    language: {
                        processing: "Procesando...",
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        emptyTable: "No hay datos disponibles en la tabla",
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
                    order: [[0, 'desc']] // Ordenar por fecha descendente
                });
            } else {
                mostrarAlerta('error', response.message);
            }
        },
        error: function() {
            $('#loadingMonitoreo').hide();
            mostrarAlerta('error', 'Error al cargar el registro de monitoreo');
        }
    });
}

function exportarReporte(formato) {
    // Obtener la pestaña activa
    const pestañaActiva = $('.nav-tabs .nav-link.active').data('bs-target');
    
    // Determinar qué reporte exportar basado en la pestaña activa
    let tipoReporte = '';
    let filtros = {};
    
    switch(pestañaActiva) {
        case '#produccion':
            const subPestañaProduccion = $('#produccionSubTabs .nav-link.active').attr('id');
            tipoReporte = subPestañaProduccion === 'rendimiento-subtab' ? 'rendimiento' : 'cosechas';
            filtros = obtenerFiltrosProduccion();
            break;
        case '#financiero':
            const subPestañaFinanciero = $('#financieroSubTabs .nav-link.active').attr('id');
            tipoReporte = subPestañaFinanciero === 'flujo-subtab' ? 'flujo_caja' : 'estado_resultados';
            filtros = {};
            break;
        case '#tecnico':
            const subPestañaTecnico = $('#tecnicoSubTabs .nav-link.active').attr('id');
            switch(subPestañaTecnico) {
                case 'monitoreo-subtab':
                    tipoReporte = 'monitoreo';
                    break;
                case 'insumos-subtab':
                    tipoReporte = 'uso_insumos';
                    filtros = obtenerFiltrosInsumos();
                    break;
                default:
                    tipoReporte = 'actividades';
            }
            break;
        default:
            mostrarAlerta('warning', 'Selecciona un reporte específico para exportar');
            return;
    }
    
    // Construir URL con parámetros
    const params = new URLSearchParams({
        tipo: tipoReporte,
        formato: formato,
        ...filtros
    });
    
    // Realizar la exportación
    const url = `../AJAX/exportar_reportes.php?${params}`;
    
    // Mostrar mensaje de descarga
    mostrarAlerta('info', `Preparando descarga del reporte en formato ${formato.toUpperCase()}...`);
    
    // Abrir en nueva ventana/pestaña para descarga
    window.open(url, '_blank');
}

function obtenerFiltrosProduccion() {
    return {
        fecha_inicio: $('#fechaInicioProduccion').val() || null,
        fecha_fin: $('#fechaFinProduccion').val() || null,
        cultivo_id: $('#cultivoProduccion').val() || null,
        lote_id: $('#loteProduccion').val() || null
    };
}

function obtenerFiltrosInsumos() {
    return {
        fecha_inicio: $('#fechaInicioInsumos').val() || null,
        fecha_fin: $('#fechaFinInsumos').val() || null,
        tipo_insumo: $('#tipoInsumo').val() || null,
        cultivo_id: $('#cultivoInsumos').val() || null
    };
}

// Funciones auxiliares para obtener colores de badges
function obtenerColorCalidad(calidad) {
    const colores = {
        'primera': 'success',
        'segunda': 'warning',
        'tercera': 'secondary',
        'descarte': 'danger'
    };
    return colores[calidad] || 'secondary';
}

function obtenerColorEstado(estado) {
    const colores = {
        'excelente': 'success',
        'bueno': 'primary',
        'regular': 'warning',
        'malo': 'danger',
        'critico': 'danger'
    };
    return colores[estado] || 'secondary';
}

function obtenerColorPlagas(nivel) {
    const colores = {
        'ninguna': 'success',
        'leve': 'warning',
        'moderada': 'danger',
        'severa': 'danger'
    };
    return colores[nivel] || 'secondary';
}

function obtenerColorEnfermedades(nivel) {
    const colores = {
        'ninguna': 'success',
        'leve': 'warning',
        'moderada': 'danger',
        'severa': 'danger'
    };
    return colores[nivel] || 'secondary';
}

function mostrarAlerta(tipo, mensaje) {
    let claseAlerta = '';
    switch(tipo) {
        case 'success':
            claseAlerta = 'alert-success';
            break;
        case 'error':
            claseAlerta = 'alert-danger';
            break;
        case 'warning':
            claseAlerta = 'alert-warning';
            break;
        case 'info':
            claseAlerta = 'alert-info';
            break;
        default:
            claseAlerta = 'alert-info';
    }
    
    const alerta = `
        <div class="alert ${claseAlerta} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Mostrar alerta al principio del contenido principal
    $('.container-fluid').prepend(alerta);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 3000);
}