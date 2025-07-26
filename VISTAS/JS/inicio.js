/**
 * Inicio JS - Sistema de Monitoreo de Cultivos
 * Dashboard principal de AgroMonitor
 */

// Variables globales del dashboard
let graficosInicializados = false;
let intervalosActivos = [];
let datosEnTiempoReal = {
    cultivos: [],
    alertas: [],
    actividades: []
};

// Configuración del dashboard
const DASHBOARD_CONFIG = {
    actualizacionDatos: 30000, // 30 segundos
    animacionContadores: 2000, // 2 segundos
    coloresGrafico: [
        'rgba(46, 125, 50, 0.8)',
        'rgba(76, 175, 80, 0.8)',
        'rgba(129, 199, 132, 0.8)',
        'rgba(255, 167, 38, 0.8)'
    ]
};

/**
 * =====================================================
 * INICIALIZACIÓN DEL DASHBOARD
 * =====================================================
 */

$(document).ready(function() {
    inicializarDashboard();
});

/**
 * Inicializar todas las funcionalidades del dashboard
 */
function inicializarDashboard() {
    AgroMonitor.log('Inicializando Dashboard AgroMonitor...', 'info');
    
    // Inicializar componentes básicos
    configurarFechaActual();
    inicializarContadores();
    inicializarGraficos();
    configurarEventListeners();
    
    // Cargar datos iniciales
    cargarDatosIniciales();
    
    // Configurar actualizaciones automáticas
    configurarActualizacionesAutomaticas();
    
    // Aplicar animaciones de entrada
    aplicarAnimacionesEntrada();
    
    AgroMonitor.log('Dashboard inicializado correctamente', 'success');
}

/**
 * =====================================================
 * CONFIGURACIÓN INICIAL
 * =====================================================
 */

/**
 * Configurar fecha actual
 */
function configurarFechaActual() {
    const opciones = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    
    const fechaActual = new Date().toLocaleDateString('es-ES', opciones);
    $('#fecha-actual').text(fechaActual);
    
    // Actualizar cada minuto
    setInterval(() => {
        const nuevaFecha = new Date().toLocaleDateString('es-ES', opciones);
        $('#fecha-actual').text(nuevaFecha);
    }, 60000);
}

/**
 * Configurar event listeners
 */
function configurarEventListeners() {
    // Cambio de período en gráfico de producción
    $('#periodo-produccion').on('change', function() {
        const periodo = $(this).val();
        actualizarGraficoProduccion(periodo);
    });
    
    // Botones de acciones rápidas
    $('.btn-outline-primary').on('click', function() {
        const accion = $(this).find('small').text().trim();
        manejarAccionRapida(accion);
    });
    
    // Botones de alertas
    $('.alert-item button').on('click', function() {
        const alertaItem = $(this).closest('.alert-item');
        manejarAccionAlerta(alertaItem);
    });
    
    // Botones de tabla de cultivos
    $('#tabla-cultivos button').on('click', function() {
        const accion = $(this).attr('title');
        const fila = $(this).closest('tr');
        manejarAccionCultivo(accion, fila);
    });
    
    // Actualización manual de datos
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            actualizarDatosManualmente();
        }
    });
}

/**
 * =====================================================
 * CONTADORES ANIMADOS
 * =====================================================
 */

/**
 * Inicializar contadores animados
 */
function inicializarContadores() {
    $('.stat-number').each(function() {
        const $contador = $(this);
        const valorFinal = parseFloat($contador.data('target'));
        
        // Iniciar animación después de un breve delay
        setTimeout(() => {
            animarContador($contador, valorFinal);
        }, 500);
    });
    
    // Animar barras de progreso
    $('.progress-bar').each(function() {
        const $barra = $(this);
        const porcentaje = $barra.data('percentage');
        
        setTimeout(() => {
            $barra.css('width', porcentaje + '%');
        }, 800);
    });
}

/**
 * Animar contador numérico
 */
function animarContador($elemento, valorFinal) {
    let valorActual = 0;
    const incremento = valorFinal / (DASHBOARD_CONFIG.animacionContadores / 16);
    
    const animacion = setInterval(() => {
        valorActual += incremento;
        
        if (valorActual >= valorFinal) {
            valorActual = valorFinal;
            clearInterval(animacion);
        }
        
        // Formatear número según tipo
        let valorMostrar = valorActual;
        if (valorFinal < 1) {
            valorMostrar = valorActual.toFixed(1);
        } else if (valorFinal >= 1000) {
            valorMostrar = AgroMonitor.utils.formatearNumero(Math.round(valorActual));
        } else {
            valorMostrar = Math.round(valorActual);
        }
        
        $elemento.text(valorMostrar);
    }, 16);
}

/**
 * =====================================================
 * GRÁFICOS Y VISUALIZACIONES
 * =====================================================
 */

/**
 * Inicializar gráficos
 */
function inicializarGraficos() {
    if (typeof Chart !== 'undefined') {
        crearGraficoProduccion();
        graficosInicializados = true;
    } else {
        AgroMonitor.log('Chart.js no está disponible', 'warning');
    }
}

/**
 * Crear gráfico de producción
 */
function crearGraficoProduccion() {
    const ctx = document.getElementById('grafico-produccion');
    if (!ctx) return;
    
    const datosEjemplo = {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [{
            label: 'Producción (Kg)',
            data: [650, 590, 800, 810, 560, 950, 1200, 1100, 900, 750, 680, 847],
            backgroundColor: 'rgba(46, 125, 50, 0.1)',
            borderColor: 'rgba(46, 125, 50, 0.8)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(46, 125, 50, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    };
    
    const opciones = {
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
                borderColor: 'rgba(46, 125, 50, 1)',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    color: '#666',
                    callback: function(value) {
                        return value + ' kg';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#666'
                }
            }
        },
        elements: {
            point: {
                hoverBackgroundColor: 'rgba(76, 175, 80, 1)'
            }
        }
    };
    
    window.graficoProduccion = new Chart(ctx, {
        type: 'line',
        data: datosEjemplo,
        options: opciones
    });
}

/**
 * Actualizar gráfico según período seleccionado
 */
function actualizarGraficoProduccion(periodo) {
    if (!window.graficoProduccion) return;
    
    AgroMonitor.log(`Actualizando gráfico para período: ${periodo}`, 'info');
    
    // Simular diferentes datos según período
    let nuevasDatos = [];
    let etiquetas = [];
    
    switch (periodo) {
        case '6m':
            etiquetas = ['Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            nuevasDatos = [1200, 1100, 900, 750, 680, 847];
            break;
        case '1y':
            etiquetas = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            nuevasDatos = [650, 590, 800, 810, 560, 950, 1200, 1100, 900, 750, 680, 847];
            break;
        case '2y':
            etiquetas = ['2023', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            nuevasDatos = [8500, 650, 590, 800, 810, 560, 950, 1200, 1100, 900, 750, 680, 847];
            break;
    }
    
    window.graficoProduccion.data.labels = etiquetas;
    window.graficoProduccion.data.datasets[0].data = nuevasDatos;
    window.graficoProduccion.update('active');
    
    AgroMonitor.alerta('Gráfico actualizado correctamente', 'success', 2000);
}

/**
 * =====================================================
 * GESTIÓN DE DATOS Y ACTUALIZACIONES
 * =====================================================
 */

/**
 * Cargar datos iniciales del dashboard
 */
function cargarDatosIniciales() {
    AgroMonitor.log('Cargando datos iniciales...', 'info');
    
    // En producción, estos serían llamadas AJAX reales
    setTimeout(() => {
        cargarEstadisticasCultivos();
        cargarAlertasRecientes();
        cargarActividadesProgramadas();
        
        AgroMonitor.log('Datos iniciales cargados', 'success');
    }, 1000);
}

/**
 * Cargar estadísticas de cultivos
 */
function cargarEstadisticasCultivos() {
    // Simular datos de API
    datosEnTiempoReal.cultivos = [
        { tipo: 'Tomates Cherry', estado: 'Floración', sector: 'A', lote: 3, actividad: 'Riego', tiempo: '2 horas' },
        { tipo: 'Zanahorias', estado: 'Crecimiento', sector: 'B', lote: 1, actividad: 'Fertilización', tiempo: '1 día' },
        { tipo: 'Lechuga', estado: 'Desarrollo', sector: 'C', lote: 2, actividad: 'Monitoreo', tiempo: '3 horas' }
    ];
    
    // Actualizar tabla si es necesario
    actualizarTablaCultivos();
}

/**
 * Cargar alertas recientes
 */
function cargarAlertasRecientes() {
    datosEnTiempoReal.alertas = [
        { tipo: 'warning', titulo: 'Riego Pendiente', mensaje: 'Tomates en Sector A necesitan riego', tiempo: '30 minutos' },
        { tipo: 'success', titulo: 'Monitoreo Completado', mensaje: 'Revisión semanal finalizada', tiempo: '2 horas' },
        { tipo: 'info', titulo: 'Previsión Climática', mensaje: 'Lluvia esperada mañana', tiempo: '1 hora' }
    ];
}

/**
 * Cargar actividades programadas
 */
function cargarActividadesProgramadas() {
    datosEnTiempoReal.actividades = [
        { fecha: '15', mes: 'Dic', titulo: 'Cosecha de Tomates', ubicacion: 'Sector A - Lote 3' },
        { fecha: '18', mes: 'Dic', titulo: 'Fertilización', ubicacion: 'Todos los sectores' },
        { fecha: '22', mes: 'Dic', titulo: 'Nueva Siembra', ubicacion: 'Sector D - Preparación' }
    ];
}

/**
 * Configurar actualizaciones automáticas
 */
function configurarActualizacionesAutomaticas() {
    // Actualizar datos cada 30 segundos
    const intervaloActualizacion = setInterval(() => {
        actualizarDatosEnTiempoReal();
    }, DASHBOARD_CONFIG.actualizacionDatos);
    
    intervalosActivos.push(intervaloActualizacion);
    
    // Limpiar intervalos al salir de la página
    $(window).on('beforeunload', () => {
        intervalosActivos.forEach(intervalo => clearInterval(intervalo));
    });
}

/**
 * Actualizar datos en tiempo real
 */
function actualizarDatosEnTiempoReal() {
    AgroMonitor.log('Actualizando datos en tiempo real...', 'info');
    
    // Simular cambios en los datos
    actualizarContadoresEnTiempoReal();
    actualizarEstadoAlertas();
    
    // Mostrar indicador de actualización
    mostrarIndicadorActualizacion();
}

/**
 * Actualizar contadores en tiempo real
 */
function actualizarContadoresEnTiempoReal() {
    // Simular pequeños cambios en las estadísticas
    $('.stat-number').each(function() {
        const $contador = $(this);
        const valorActual = parseInt($contador.text().replace(/[^0-9]/g, ''));
        const variacion = Math.floor(Math.random() * 3) - 1; // -1, 0, o 1
        const nuevoValor = Math.max(0, valorActual + variacion);
        
        if (variacion !== 0) {
            $contador.text(nuevoValor);
            
            // Efecto visual de cambio
            $contador.parent().parent().addClass('stat-updated');
            setTimeout(() => {
                $contador.parent().parent().removeClass('stat-updated');
            }, 1000);
        }
    });
}

/**
 * Actualizar estado de alertas
 */
function actualizarEstadoAlertas() {
    const $contadorAlertas = $('#notification-count');
    const contadorActual = parseInt($contadorAlertas.text());
    
    // Simular nuevas alertas ocasionalmente
    if (Math.random() < 0.1) { // 10% de probabilidad
        $contadorAlertas.text(contadorActual + 1);
        $contadorAlertas.addClass('notification-new');
        
        setTimeout(() => {
            $contadorAlertas.removeClass('notification-new');
        }, 2000);
        
        AgroMonitor.alerta('Nueva notificación recibida', 'info', 3000);
    }
}

/**
 * Mostrar indicador de actualización
 */
function mostrarIndicadorActualizacion() {
    const $indicador = $('<div class="update-indicator"><i class="fas fa-sync fa-spin"></i></div>');
    $('body').append($indicador);
    
    setTimeout(() => {
        $indicador.remove();
    }, 1000);
}

/**
 * Actualización manual de datos
 */
function actualizarDatosManualmente() {
    AgroMonitor.alerta('Actualizando datos...', 'info', 2000);
    
    // Mostrar loading en todas las tarjetas
    $('.stat-number').addClass('loading-stat');
    
    setTimeout(() => {
        $('.stat-number').removeClass('loading-stat');
        cargarDatosIniciales();
        AgroMonitor.alerta('Datos actualizados correctamente', 'success', 3000);
    }, 1500);
}

/**
 * =====================================================
 * MANEJO DE ACCIONES DEL USUARIO
 * =====================================================
 */

/**
 * Manejar acciones rápidas
 */
function manejarAccionRapida(accion) {
    AgroMonitor.log(`Acción rápida: ${accion}`, 'info');
    
    switch (accion) {
        case 'Nuevo Cultivo':
            abrirModalNuevoCultivo();
            break;
        case 'Monitoreo':
            iniciarMonitoreo();
            break;
        case 'Programar Riego':
            abrirProgramadorRiego();
            break;
        case 'Generar Reporte':
            generarReporte();
            break;
        default:
            AgroMonitor.alerta(`Función "${accion}" en desarrollo`, 'info');
    }
}

/**
 * Manejar acciones de alertas
 */
function manejarAccionAlerta(alertaItem) {
    const titulo = alertaItem.find('h6').text();
    const accionBtn = alertaItem.find('button').text().trim();
    
    AgroMonitor.log(`Acción en alerta: ${accionBtn} - ${titulo}`, 'info');
    
    if (accionBtn === 'Marcar como leído') {
        alertaItem.fadeOut(300, function() {
            $(this).remove();
        });
        AgroMonitor.alerta('Alerta marcada como leída', 'success', 2000);
    } else {
        AgroMonitor.alerta(`Abriendo detalles de: ${titulo}`, 'info');
    }
}

/**
 * Manejar acciones de cultivos
 */
function manejarAccionCultivo(accion, fila) {
    const nombreCultivo = fila.find('.fw-bold').text();
    
    AgroMonitor.log(`Acción en cultivo: ${accion} - ${nombreCultivo}`, 'info');
    
    switch (accion) {
        case 'Ver detalles':
            verDetallesCultivo(nombreCultivo, fila);
            break;
        case 'Editar':
            editarCultivo(nombreCultivo, fila);
            break;
        default:
            AgroMonitor.alerta(`Acción "${accion}" en desarrollo`, 'info');
    }
}

/**
 * =====================================================
 * FUNCIONES ESPECÍFICAS DE ACCIONES
 * =====================================================
 */

/**
 * Abrir modal para nuevo cultivo
 */
function abrirModalNuevoCultivo() {
    AgroMonitor.alerta('Abriendo formulario de nuevo cultivo...', 'info');
    // Aquí se abriría un modal o se redirigiría a la página correspondiente
}

/**
 * Iniciar proceso de monitoreo
 */
function iniciarMonitoreo() {
    AgroMonitor.alerta('Iniciando proceso de monitoreo...', 'info');
    // Aquí se iniciaría el proceso de monitoreo
}

/**
 * Abrir programador de riego
 */
function abrirProgramadorRiego() {
    AgroMonitor.alerta('Abriendo programador de riego...', 'info');
    // Aquí se abriría el programador de riego
}

/**
 * Generar reporte
 */
function generarReporte() {
    AgroMonitor.loading.mostrar('Generando reporte...');
    
    setTimeout(() => {
        AgroMonitor.loading.ocultar();
        AgroMonitor.alerta('Reporte generado exitosamente', 'success');
    }, 2000);
}

/**
 * Ver detalles de cultivo
 */
function verDetallesCultivo(nombre, fila) {
    const ubicacion = fila.find('td:nth-child(2)').text();
    const estado = fila.find('.badge').text();
    
    const detalles = `
        <strong>${nombre}</strong><br>
        Ubicación: ${ubicacion}<br>
        Estado: ${estado}
    `;
    
    AgroMonitor.alerta(detalles, 'info', 5000);
}

/**
 * Editar cultivo
 */
function editarCultivo(nombre, fila) {
    AgroMonitor.alerta(`Abriendo editor para: ${nombre}`, 'info');
    // Aquí se abriría el formulario de edición
}

/**
 * =====================================================
 * ACTUALIZACIÓN DE COMPONENTES DE UI
 * =====================================================
 */

/**
 * Actualizar tabla de cultivos
 */
function actualizarTablaCultivos() {
    // Esta función se llamaría cuando lleguen nuevos datos del servidor
    AgroMonitor.log('Tabla de cultivos actualizada', 'info');
}

/**
 * Aplicar animaciones de entrada
 */
function aplicarAnimacionesEntrada() {
    // Las animaciones CSS ya están definidas, aquí podríamos agregar lógica adicional
    setTimeout(() => {
        $('.stat-card').addClass('animate-in');
    }, 100);
}

/**
 * =====================================================
 * UTILIDADES DEL DASHBOARD
 * =====================================================
 */

/**
 * Obtener resumen de estadísticas
 */
function obtenerResumenEstadisticas() {
    const estadisticas = {
        cultivosActivos: parseInt($('.stat-number').eq(0).text()),
        hectareasCultivadas: parseFloat($('.stat-number').eq(1).text()),
        kgCosechados: parseInt($('.stat-number').eq(2).text()),
        saludGeneral: parseInt($('.stat-number').eq(3).text())
    };
    
    AgroMonitor.log('Estadísticas actuales:', estadisticas);
    return estadisticas;
}

/**
 * Exportar datos del dashboard
 */
function exportarDatosDashboard() {
    const datos = {
        fecha: new Date().toISOString(),
        estadisticas: obtenerResumenEstadisticas(),
        cultivos: datosEnTiempoReal.cultivos,
        alertas: datosEnTiempoReal.alertas,
        actividades: datosEnTiempoReal.actividades
    };
    
    const dataStr = JSON.stringify(datos, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `dashboard-agromonitor-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    URL.revokeObjectURL(url);
    AgroMonitor.alerta('Datos exportados exitosamente', 'success');
}

/**
 * =====================================================
 * ESTILOS ADICIONALES DINÁMICOS
 * =====================================================
 */

// Agregar estilos CSS adicionales para animaciones dinámicas
const estilosAdicionales = `
    <style>
    .stat-updated {
        animation: pulseUpdate 1s ease-in-out;
    }
    
    @keyframes pulseUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .notification-new {
        animation: bounce 0.6s ease-in-out;
        background: var(--warning-orange) !important;
    }
    
    @keyframes bounce {
        0%, 20%, 60%, 100% { transform: scale(1); }
        40% { transform: scale(1.1); }
        80% { transform: scale(1.05); }
    }
    
    .update-indicator {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--primary-green);
        color: white;
        padding: 10px;
        border-radius: 50%;
        z-index: 1060;
        animation: fadeInOut 1s ease-in-out;
    }
    
    @keyframes fadeInOut {
        0%, 100% { opacity: 0; }
        50% { opacity: 1; }
    }
    
    .animate-in {
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
`;

// Insertar estilos adicionales
document.head.insertAdjacentHTML('beforeend', estilosAdicionales);

// Exponer funciones útiles globalmente
window.AgroMonitorDashboard = {
    actualizar: actualizarDatosManualmente,
    exportar: exportarDatosDashboard,
    estadisticas: obtenerResumenEstadisticas
};

AgroMonitor.log('Dashboard JS cargado exitosamente', 'success');