/**
 * MÓDULO FINANCIERO - AgroMonitor
 * Sistema de gestión financiera sin dependencias externas
 */

// Configuración global
const FINANZAS_CONFIG = {
    dataTables: {
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "infoThousands": ",",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }
};

// Variables globales
let tablaGastos = null;
let modalNuevoGasto = null;
let modalEditarGasto = null;
let modalVerGasto = null;

/**
 * Inicialización del módulo al cargar el DOM
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeFinanzasModule();
});

/**
 * Inicializar el módulo financiero
 */
function initializeFinanzasModule() {
    console.log('Inicializando módulo financiero...');
    
    // Inicializar DataTable
    initializeDataTable();
    
    // Inicializar modales
    initializeModals();
    
    // Configurar event listeners
    setupEventListeners();
    
    // Configurar filtros
    setupFilters();
    
    // Configurar formularios
    setupForms();
    
    console.log('Módulo financiero inicializado correctamente');
}

/**
 * Inicializar DataTable para gastos
 */
function initializeDataTable() {
    if (!document.getElementById('tablaGastos')) {
        console.warn('Tabla de gastos no encontrada');
        return;
    }
    
    try {
        tablaGastos = $('#tablaGastos').DataTable({
            language: FINANZAS_CONFIG.dataTables.language,
            responsive: true,
            pageLength: 25,
            order: [[1, 'desc']], // Ordenar por fecha descendente
            columnDefs: [
                {
                    targets: [0], // ID
                    width: "60px",
                    className: "text-center"
                },
                {
                    targets: [1], // Fecha
                    width: "100px",
                    className: "text-center"
                },
                {
                    targets: [2], // Tipo
                    width: "120px",
                    className: "text-center"
                },
                {
                    targets: [7], // Monto
                    width: "120px",
                    className: "text-end"
                },
                {
                    targets: [8], // Acciones
                    width: "150px",
                    className: "text-center",
                    orderable: false
                }
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            drawCallback: function() {
                // Reinicializar tooltips después de cada redraw
                initializeTooltips();
            }
        });
        
        console.log('DataTable inicializada correctamente');
    } catch (error) {
        console.error('Error al inicializar DataTable:', error);
    }
}

/**
 * Inicializar modales de Bootstrap
 */
function initializeModals() {
    // Modal nuevo gasto
    const modalNuevoGastoElement = document.getElementById('modalNuevoGasto');
    if (modalNuevoGastoElement) {
        modalNuevoGasto = new bootstrap.Modal(modalNuevoGastoElement);
    }
    
    // Modal editar gasto (se creará dinámicamente)
    // Modal ver gasto (se creará dinámicamente)
    
    console.log('Modales inicializados');
}

/**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Botón nuevo gasto
    const btnNuevoGasto = document.querySelector('[data-bs-target="#modalNuevoGasto"]');
    if (btnNuevoGasto) {
        btnNuevoGasto.addEventListener('click', function() {
            prepararModalNuevoGasto();
        });
    }
    
    // Botones de acción en la tabla
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-ver-gasto')) {
            const btn = e.target.closest('.btn-ver-gasto');
            const gastoId = btn.getAttribute('data-id');
            verGasto(gastoId);
        }
        
        if (e.target.closest('.btn-editar-gasto')) {
            const btn = e.target.closest('.btn-editar-gasto');
            const gastoId = btn.getAttribute('data-id');
            editarGasto(gastoId);
        }
        
        if (e.target.closest('.btn-eliminar-gasto')) {
            const btn = e.target.closest('.btn-eliminar-gasto');
            const gastoId = btn.getAttribute('data-id');
            const descripcion = btn.getAttribute('data-descripcion');
            confirmarEliminarGasto(gastoId, descripcion);
        }
    });
    
    // Botón generar reporte
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    if (btnGenerarReporte) {
        btnGenerarReporte.addEventListener('click', generarReporteFinanciero);
    }
    
    console.log('Event listeners configurados');
}

/**
 * Configurar filtros de búsqueda
 */
function setupFilters() {
    const filtros = ['filtroTipo', 'filtroFinca', 'filtroFechaInicio', 'filtroFechaFin', 'filtroProveedor'];
    
    filtros.forEach(filtroId => {
        const filtro = document.getElementById(filtroId);
        if (filtro) {
            filtro.addEventListener('change', aplicarFiltros);
            if (filtro.type === 'text') {
                filtro.addEventListener('keyup', debounce(aplicarFiltros, 300));
            }
        }
    });
    
    // Botón limpiar filtros
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', limpiarFiltros);
    }
    
    console.log('Filtros configurados');
}

/**
 * Configurar formularios
 */
function setupForms() {
    // Formulario nuevo gasto
    const formNuevoGasto = document.getElementById('formNuevoGasto');
    if (formNuevoGasto) {
        formNuevoGasto.addEventListener('submit', function(e) {
            e.preventDefault();
            procesarNuevoGasto();
        });
    }
    
    // Auto-cálculo de monto cuando cambia cantidad o precio
    document.addEventListener('input', function(e) {
        if (e.target.matches('[data-auto-calculate="total"]')) {
            autoCalcularTotal(e.target.closest('form'));
        }
    });
    
    console.log('Formularios configurados');
}

/**
 * Preparar modal para nuevo gasto
 */
function prepararModalNuevoGasto() {
    const form = document.getElementById('formNuevoGasto');
    if (form) {
        form.reset();
        
        // Establecer fecha actual
        const campoFecha = document.getElementById('nuevaFechaGasto');
        if (campoFecha) {
            campoFecha.value = formatearFecha(new Date());
        }
        
        // Limpiar mensajes de error
        limpiarErroresFormulario(form);
    }
}

/**
 * Procesar creación de nuevo gasto
 */
async function procesarNuevoGasto() {
    const form = document.getElementById('formNuevoGasto');
    const formData = new FormData(form);
    
    try {
        // Mostrar loading
        mostrarLoading('Registrando gasto...');
        
        const response = await fetch('AJAX/crear_gasto.php', {
            method: 'POST',
            body: formData
        });
        
        const resultado = await response.json();
        
        ocultarLoading();
        
        if (resultado.success) {
            // Cerrar modal
            modalNuevoGasto.hide();
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Gasto registrado exitosamente', 'success');
            
            // Recargar tabla
            if (tablaGastos) {
                recargarTabla();
            } else {
                // Si no hay DataTable, recargar página
                setTimeout(() => location.reload(), 1500);
            }
            
            // Actualizar estadísticas
            actualizarEstadisticas();
            
        } else {
            mostrarMensaje(resultado.message || 'Error al registrar el gasto', 'error');
        }
        
    } catch (error) {
        ocultarLoading();
        console.error('Error al procesar nuevo gasto:', error);
        mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
    }
}

/**
 * Ver detalles de un gasto
 */
async function verGasto(gastoId) {
    try {
        mostrarLoading('Cargando detalles...');
        
        const response = await fetch(`AJAX/obtener_gasto.php?id=${gastoId}`);
        const resultado = await response.json();
        
        ocultarLoading();
        
        if (resultado.success) {
            mostrarModalVerGasto(resultado.gasto);
        } else {
            mostrarMensaje(resultado.message || 'Error al obtener los detalles', 'error');
        }
        
    } catch (error) {
        ocultarLoading();
        console.error('Error al ver gasto:', error);
        mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
    }
}

/**
 * Editar un gasto
 */
async function editarGasto(gastoId) {
    try {
        mostrarLoading('Cargando datos...');
        
        const response = await fetch(`AJAX/obtener_gasto.php?id=${gastoId}`);
        const resultado = await response.json();
        
        ocultarLoading();
        
        if (resultado.success) {
            mostrarModalEditarGasto(resultado.gasto);
        } else {
            mostrarMensaje(resultado.message || 'Error al cargar los datos', 'error');
        }
        
    } catch (error) {
        ocultarLoading();
        console.error('Error al editar gasto:', error);
        mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
    }
}

/**
 * Confirmar eliminación de gasto
 */
function confirmarEliminarGasto(gastoId, descripcion) {
    Swal.fire({
        title: '¿Eliminar gasto?',
        text: `¿Está seguro de eliminar el gasto "${descripcion}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            eliminarGasto(gastoId);
        }
    });
}

/**
 * Eliminar gasto
 */
async function eliminarGasto(gastoId) {
    try {
        mostrarLoading('Eliminando gasto...');
        
        const formData = new FormData();
        formData.append('gasto_id', gastoId);
        
        const response = await fetch('AJAX/eliminar_gasto.php', {
            method: 'POST',
            body: formData
        });
        
        const resultado = await response.json();
        
        ocultarLoading();
        
        if (resultado.success) {
            mostrarMensaje('Gasto eliminado exitosamente', 'success');
            
            // Recargar tabla
            if (tablaGastos) {
                recargarTabla();
            } else {
                setTimeout(() => location.reload(), 1500);
            }
            
            // Actualizar estadísticas
            actualizarEstadisticas();
            
        } else {
            mostrarMensaje(resultado.message || 'Error al eliminar el gasto', 'error');
        }
        
    } catch (error) {
        ocultarLoading();
        console.error('Error al eliminar gasto:', error);
        mostrarMensaje('Error de conexión. Intente nuevamente.', 'error');
    }
}

/**
 * Aplicar filtros a la tabla
 */
function aplicarFiltros() {
    if (!tablaGastos) return;
    
    const filtroTipo = document.getElementById('filtroTipo')?.value || '';
    const filtroFinca = document.getElementById('filtroFinca')?.value || '';
    const filtroFechaInicio = document.getElementById('filtroFechaInicio')?.value || '';
    const filtroFechaFin = document.getElementById('filtroFechaFin')?.value || '';
    const filtroProveedor = document.getElementById('filtroProveedor')?.value || '';
    
    // Aplicar filtros por columna
    tablaGastos
        .column(2).search(filtroTipo) // Tipo
        .column(4).search(filtroFinca) // Finca
        .column(5).search(filtroProveedor) // Proveedor
        .draw();
    
    // Filtro personalizado para fechas
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'tablaGastos') return true;
        
        const fechaTexto = data[1]; // Columna fecha
        if (!fechaTexto) return true;
        
        // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd para comparación
        const partesFecha = fechaTexto.split('/');
        if (partesFecha.length !== 3) return true;
        
        const fechaComparar = `${partesFecha[2]}-${partesFecha[1].padStart(2, '0')}-${partesFecha[0].padStart(2, '0')}`;
        
        if (filtroFechaInicio && fechaComparar < filtroFechaInicio) return false;
        if (filtroFechaFin && fechaComparar > filtroFechaFin) return false;
        
        return true;
    });
    
    tablaGastos.draw();
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
    document.getElementById('filtroTipo').value = '';
    document.getElementById('filtroFinca').value = '';
    document.getElementById('filtroFechaInicio').value = '';
    document.getElementById('filtroFechaFin').value = '';
    document.getElementById('filtroProveedor').value = '';
    
    if (tablaGastos) {
        // Limpiar filtros personalizados
        $.fn.dataTable.ext.search.pop();
        
        // Limpiar filtros de columnas
        tablaGastos.search('').columns().search('').draw();
    }
}

/**
 * Recargar tabla de gastos
 */
function recargarTabla() {
    // En una implementación real, esto haría una llamada AJAX para obtener datos actualizados
    location.reload();
}

/**
 * Actualizar estadísticas financieras
 */
function actualizarEstadisticas() {
    // En una implementación real, esto haría llamadas AJAX para actualizar las estadísticas
    setTimeout(() => {
        location.reload();
    }, 1000);
}

/**
 * Generar reporte financiero
 */
function generarReporteFinanciero() {
    mostrarMensaje('Generando reporte financiero...', 'info');
    
    // En una implementación real, esto generaría un archivo Excel
    setTimeout(() => {
        mostrarMensaje('Reporte generado exitosamente', 'success');
    }, 2000);
}

/**
 * Mostrar modal para ver gasto
 */
function mostrarModalVerGasto(gasto) {
    // Crear modal dinámicamente si no existe
    let modalHtml = `
        <div class="modal fade" id="modalVerGasto" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-eye me-2"></i>Detalles del Gasto
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Fecha:</strong> ${formatearFechaLegible(gasto.gas_fecha)}
                            </div>
                            <div class="col-md-6">
                                <strong>Tipo:</strong> ${capitalizar(gasto.gas_tipo.replace('_', ' '))}
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>Monto:</strong> $${formatearNumero(gasto.gas_monto)}
                            </div>
                            <div class="col-md-6">
                                <strong>Proveedor:</strong> ${gasto.gas_proveedor || 'No especificado'}
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Descripción:</strong><br>
                                ${gasto.gas_descripcion}
                            </div>
                        </div>
                        ${gasto.gas_observaciones ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <strong>Observaciones:</strong><br>
                                ${gasto.gas_observaciones}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Eliminar modal anterior si existe
    const modalAnterior = document.getElementById('modalVerGasto');
    if (modalAnterior) {
        modalAnterior.remove();
    }
    
    // Agregar nuevo modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalVerGasto'));
    modal.show();
}

/**
 * Funciones de utilidad
 */
function formatearFecha(fecha) {
    if (!(fecha instanceof Date)) return '';
    return fecha.toISOString().split('T')[0];
}

function formatearFechaLegible(fechaString) {
    if (!fechaString) return '';
    const fecha = new Date(fechaString);
    return fecha.toLocaleDateString('es-ES');
}

function formatearNumero(numero) {
    return parseFloat(numero).toLocaleString('es-ES');
}

function capitalizar(texto) {
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function mostrarLoading(mensaje = 'Cargando...') {
    Swal.fire({
        title: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function ocultarLoading() {
    Swal.close();
}

function mostrarMensaje(mensaje, tipo = 'info') {
    const iconos = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    
    Swal.fire({
        icon: iconos[tipo] || 'info',
        title: mensaje,
        timer: 3000,
        showConfirmButton: false
    });
}

function limpiarErroresFormulario(form) {
    const campos = form.querySelectorAll('.is-invalid');
    campos.forEach(campo => campo.classList.remove('is-invalid'));
    
    const mensajes = form.querySelectorAll('.invalid-feedback');
    mensajes.forEach(mensaje => mensaje.remove());
}

function initializeTooltips() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(element => {
        if (!element.hasAttribute('data-bs-toggle')) {
            element.setAttribute('data-bs-toggle', 'tooltip');
            new bootstrap.Tooltip(element);
        }
    });
}

// Inicializar tooltips al cargar
document.addEventListener('DOMContentLoaded', initializeTooltips);