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
    cargarEstadisticasGenerales();
    
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
    // Event listeners específicos por rol
    const userRole = window.userRole || 'agricultor';
    
    switch (userRole) {
        case 'administrador':
            configurarEventListenersAdmin();
            break;
        case 'agricultor':
            configurarEventListenersAgricultor();
            break;
        case 'supervisor':
            configurarEventListenersSupervisor();
            break;
    }
    
    // Event listeners comunes
    configurarEventListenersComunes();
}

/**
 * Event listeners para administrador
 */
function configurarEventListenersAdmin() {
    // Cambio de período en gráfico del sistema
    $('#periodo-sistema').on('change', function() {
        const periodo = $(this).val();
        cargarDatosGraficoSistema();
    });
}

/**
 * Event listeners para agricultor
 */
function configurarEventListenersAgricultor() {
    // Cambio de período en gráfico de producción
    $('#periodo-produccion').on('change', function() {
        const periodo = $(this).val();
        actualizarGraficoProduccion(periodo);
    });
}

/**
 * Event listeners para supervisor
 */
function configurarEventListenersSupervisor() {
    // Cambio de período en gráfico de supervisión
    $('#periodo-supervision').on('change', function() {
        const periodo = $(this).val();
        cargarDatosSupervision();
    });
}

/**
 * Event listeners comunes para todos los roles
 */
function configurarEventListenersComunes() {
    // Botones de acciones rápidas
    $('.btn-outline-primary, .btn-outline-success, .btn-outline-warning, .btn-outline-info').on('click', function() {
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
        // Determinar qué gráficos crear según el rol del usuario
        const userRole = window.userRole || 'agricultor'; // Obtener del HTML
        
        switch (userRole) {
            case 'administrador':
                crearGraficoSistema();
                crearGraficoUsuarios();
                break;
            case 'agricultor':
                crearGraficoProduccion();
                crearGraficoGastosIngresos();
                break;
            case 'supervisor':
                crearGraficoSupervision();
                crearGraficoRendimiento();
                break;
        }
        
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
    
    const datosVacios = {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [{
            label: 'Sin datos de producción',
            data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            backgroundColor: 'rgba(200, 200, 200, 0.1)',
            borderColor: 'rgba(200, 200, 200, 0.5)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(200, 200, 200, 0.7)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
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
        data: datosVacios,
        options: opciones
    });
    
    // Cargar datos reales al inicializar
    actualizarGraficoProduccion('1y');
}

/**
 * Actualizar gráfico según período seleccionado
 */
function actualizarGraficoProduccion(periodo) {
    if (!window.graficoProduccion) return;
    
    AgroMonitor.log(`Actualizando gráfico para período: ${periodo}`, 'info');
    
    // Cargar datos reales desde la base de datos
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        type: 'GET',
        data: { 
            accion: 'produccion_mensual',
            año: new Date().getFullYear()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Actualizar el gráfico con datos reales
                window.graficoProduccion.data = response.data;
                window.graficoProduccion.update('active');
                
                AgroMonitor.log('Gráfico de producción actualizado exitosamente', 'success');
            } else {
                console.error('Error al cargar datos del gráfico:', response.message);
                AgroMonitor.alerta('Error al cargar datos del gráfico', 'error', 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cargar gráfico:', error);
            AgroMonitor.alerta('Error de conexión al cargar gráfico', 'error', 3000);
        }
    });
}

/**
 * =====================================================
 * GRÁFICOS ESPECÍFICOS POR ROL
 * =====================================================
 */

/**
 * Crear gráfico del sistema (Administrador)
 */
function crearGraficoSistema() {
    const ctx = document.getElementById('grafico-sistema');
    if (!ctx) return;
    
    const datosIniciales = {
        labels: ['Usuarios', 'Fincas', 'Siembras', 'Cosechas', 'Actividades', 'Reportes'],
        datasets: [{
            label: 'Estadísticas del Sistema',
            data: [0, 0, 0, 0, 0, 0],
            backgroundColor: [
                'rgba(46, 125, 50, 0.8)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(129, 199, 132, 0.8)',
                'rgba(255, 167, 38, 0.8)',
                'rgba(33, 150, 243, 0.8)',
                'rgba(156, 39, 176, 0.8)'
            ],
            borderColor: [
                'rgba(46, 125, 50, 1)',
                'rgba(76, 175, 80, 1)',
                'rgba(129, 199, 132, 1)',
                'rgba(255, 167, 38, 1)',
                'rgba(33, 150, 243, 1)',
                'rgba(156, 39, 176, 1)'
            ],
            borderWidth: 2
        }]
    };
    
    window.graficoSistema = new Chart(ctx, {
        type: 'doughnut',
        data: datosIniciales,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(46, 125, 50, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed;
                        }
                    }
                }
            }
        }
    });
    
    // Cargar datos reales
    cargarDatosGraficoSistema();
}

/**
 * Crear gráfico de usuarios activos (Administrador)
 */
function crearGraficoUsuarios() {
    const ctx = document.getElementById('grafico-usuarios');
    if (!ctx) return;
    
    const datosIniciales = {
        labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        datasets: [{
            label: 'Usuarios Activos',
            data: [0, 0, 0, 0, 0, 0, 0],
            backgroundColor: 'rgba(76, 175, 80, 0.2)',
            borderColor: 'rgba(76, 175, 80, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgba(76, 175, 80, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    };
    
    window.graficoUsuarios = new Chart(ctx, {
        type: 'line',
        data: datosIniciales,
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
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // Cargar datos reales
    cargarDatosUsuariosActivos();
}

/**
 * Crear gráfico de gastos vs ingresos (Agricultor)
 */
function crearGraficoGastosIngresos() {
    const ctx = document.getElementById('grafico-gastos-ingresos');
    if (!ctx) return;
    
    const datosIniciales = {
        labels: ['Gastos', 'Ingresos'],
        datasets: [{
            label: 'Comparativo Financiero',
            data: [0, 0],
            backgroundColor: [
                'rgba(244, 67, 54, 0.8)',  // Rojo para gastos
                'rgba(76, 175, 80, 0.8)'   // Verde para ingresos
            ],
            borderColor: [
                'rgba(244, 67, 54, 1)',
                'rgba(76, 175, 80, 1)'
            ],
            borderWidth: 2
        }]
    };
    
    window.graficoGastosIngresos = new Chart(ctx, {
        type: 'pie',
        data: datosIniciales,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(46, 125, 50, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Cargar datos reales
    cargarDatosGastosIngresos();
}

/**
 * Crear gráfico de supervisión (Supervisor)
 */
function crearGraficoSupervision() {
    const ctx = document.getElementById('grafico-supervision');
    if (!ctx) return;
    
    const datosIniciales = {
        labels: ['Fincas OK', 'Requieren Atención', 'En Proceso', 'Sin Revisar'],
        datasets: [{
            label: 'Estado de Supervisión',
            data: [0, 0, 0, 0],
            backgroundColor: [
                'rgba(76, 175, 80, 0.8)',   // Verde - OK
                'rgba(255, 193, 7, 0.8)',   // Amarillo - Atención
                'rgba(33, 150, 243, 0.8)',  // Azul - En proceso
                'rgba(158, 158, 158, 0.8)'  // Gris - Sin revisar
            ],
            borderColor: [
                'rgba(76, 175, 80, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(33, 150, 243, 1)',
                'rgba(158, 158, 158, 1)'
            ],
            borderWidth: 2
        }]
    };
    
    window.graficoSupervision = new Chart(ctx, {
        type: 'doughnut',
        data: datosIniciales,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(46, 125, 50, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            }
        }
    });
    
    // Cargar datos reales
    cargarDatosSupervision();
}

/**
 * Crear gráfico de rendimiento por finca (Supervisor)
 */
function crearGraficoRendimiento() {
    const ctx = document.getElementById('grafico-rendimiento');
    if (!ctx) return;
    
    const datosIniciales = {
        labels: ['Finca A', 'Finca B', 'Finca C', 'Finca D', 'Finca E'],
        datasets: [{
            label: 'Rendimiento (kg/ha)',
            data: [0, 0, 0, 0, 0],
            backgroundColor: 'rgba(76, 175, 80, 0.8)',
            borderColor: 'rgba(76, 175, 80, 1)',
            borderWidth: 2,
            borderRadius: 5,
            borderSkipped: false
        }]
    };
    
    window.graficoRendimiento = new Chart(ctx, {
        type: 'bar',
        data: datosIniciales,
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
                    callbacks: {
                        label: function(context) {
                            return 'Rendimiento: ' + context.parsed.y + ' kg/ha';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' kg/ha';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // Cargar datos reales
    cargarDatosRendimiento();
}

/**
 * =====================================================
 * FUNCIONES DE CARGA DE DATOS PARA GRÁFICOS
 * =====================================================
 */

/**
 * Cargar datos para gráfico del sistema
 */
function cargarDatosGraficoSistema() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        type: 'GET',
        data: { accion: 'dashboard_general' },
        dataType: 'json',
        success: function(response) {
            if (response.success && window.graficoSistema) {
                window.graficoSistema.data.datasets[0].data = response.data;
                window.graficoSistema.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar datos del sistema:', error);
        }
    });
}

/**
 * Cargar datos de usuarios activos
 */
function cargarDatosUsuariosActivos() {
    // Simular datos por ahora - en producción vendría del backend
    if (window.graficoUsuarios) {
        const datosSimulados = [12, 19, 15, 25, 22, 18, 20];
        window.graficoUsuarios.data.datasets[0].data = datosSimulados;
        window.graficoUsuarios.update();
    }
}

/**
 * Cargar datos de gastos vs ingresos
 */
function cargarDatosGastosIngresos() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        type: 'GET',
        data: { accion: 'gastos_ingresos', periodo: '12_meses' },
        dataType: 'json',
        success: function(response) {
            if (response.success && window.graficoGastosIngresos) {
                window.graficoGastosIngresos.data.datasets[0].data = [
                    response.gastos_total || 0,
                    response.ingresos_total || 0
                ];
                window.graficoGastosIngresos.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar datos financieros:', error);
        }
    });
}

/**
 * Cargar datos de supervisión
 */
function cargarDatosSupervision() {
    // Simular datos por ahora - en producción vendría del backend
    if (window.graficoSupervision) {
        const datosSimulados = [8, 3, 2, 1]; // Fincas OK, Atención, En proceso, Sin revisar
        window.graficoSupervision.data.datasets[0].data = datosSimulados;
        window.graficoSupervision.update();
    }
}

/**
 * Cargar datos de rendimiento
 */
function cargarDatosRendimiento() {
    $.ajax({
        url: '../AJAX/reportes_ajax.php',
        type: 'GET',
        data: { accion: 'rendimiento_lotes' },
        dataType: 'json',
        success: function(response) {
            if (response.success && window.graficoRendimiento) {
                const labels = response.data.map(item => item.nombre || 'Finca');
                const datos = response.data.map(item => parseFloat(item.rendimiento) || 0);
                
                window.graficoRendimiento.data.labels = labels;
                window.graficoRendimiento.data.datasets[0].data = datos;
                window.graficoRendimiento.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar datos de rendimiento:', error);
        }
    });
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
    
    cargarEstadisticasCultivos();
    cargarAlertasRecientes();
    cargarActividadesProgramadas();
    
    AgroMonitor.log('Datos iniciales cargados', 'success');
}

/**
 * Cargar estadísticas generales del dashboard
 */
function cargarEstadisticasGenerales() {
    $.ajax({
        url: '../AJAX/dashboard_ajax.php',
        type: 'POST',
        data: { action: 'get_estadisticas' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                actualizarContadoresEstadisticas(response.data);
            } else {
                console.error('Error al cargar estadísticas:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cargar estadísticas:', error);
        }
    });
}

/**
 * Actualizar contadores de estadísticas
 */
function actualizarContadoresEstadisticas(estadisticas) {
    const $contadores = $('.stat-number');
    
    if ($contadores.length >= 4) {
        $contadores.eq(0).attr('data-target', estadisticas.usuarios_activos || 0);
        $contadores.eq(1).attr('data-target', estadisticas.fincas_registradas || 0);
        $contadores.eq(2).attr('data-target', estadisticas.siembras_activas || 0);
        $contadores.eq(3).attr('data-target', estadisticas.tareas_pendientes || 0);
        
        // Reinicializar animaciones de contadores
        inicializarContadores();
    }
}

/**
 * Cargar estadísticas de cultivos desde la base de datos
 */
function cargarEstadisticasCultivos() {
    $.ajax({
        url: '../AJAX/dashboard_ajax.php',
        type: 'POST',
        data: { action: 'get_cultivos_recientes' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                datosEnTiempoReal.cultivos = response.data;
                actualizarTablaCultivos();
            } else {
                console.error('Error al cargar cultivos:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cargar cultivos:', error);
        }
    });
}

/**
 * Cargar alertas recientes
 */
function cargarAlertasRecientes() {
    $.ajax({
        url: '../AJAX/dashboard_ajax.php',
        type: 'POST',
        data: { action: 'get_alertas' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                datosEnTiempoReal.alertas = response.data;
                actualizarPanelAlertas();
            } else {
                console.error('Error al cargar alertas:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cargar alertas:', error);
        }
    });
}

/**
 * Cargar actividades programadas
 */
function cargarActividadesProgramadas() {
    $.ajax({
        url: '../AJAX/dashboard_ajax.php',
        type: 'POST',
        data: { action: 'get_actividades' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                datosEnTiempoReal.actividades = response.data;
                actualizarPanelActividades();
            } else {
                console.error('Error al cargar actividades:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX al cargar actividades:', error);
        }
    });
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
    
    // Recargar datos reales desde el servidor
    cargarEstadisticasGenerales();
    cargarEstadisticasCultivos();
    cargarAlertasRecientes();
    cargarActividadesProgramadas();
    
    // Mostrar indicador de actualización
    mostrarIndicadorActualizacion();
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
        cargarEstadisticasGenerales();
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
    const $tablaCuerpo = $('#tabla-cultivos tbody');
    
    if (!$tablaCuerpo.length || !datosEnTiempoReal.cultivos.length) {
        return;
    }
    
    let html = '';
    datosEnTiempoReal.cultivos.forEach(cultivo => {
        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-leaf text-success me-2"></i>
                        <div>
                            <div class="fw-bold">${cultivo.cultivo}</div>
                            <small class="text-muted">ID: ${cultivo.id}</small>
                        </div>
                    </div>
                </td>
                <td>${cultivo.ubicacion}</td>
                <td>
                    <span class="badge bg-${getEstadoBadgeColor(cultivo.estado)}">${cultivo.estado}</span>
                </td>
                <td>
                    <small class="text-muted">${cultivo.ultima_actividad}</small><br>
                    <small class="text-primary">${cultivo.fecha_actividad}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-success" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    $tablaCuerpo.html(html);
    AgroMonitor.log('Tabla de cultivos actualizada', 'info');
}

/**
 * Actualizar panel de alertas
 */
function actualizarPanelAlertas() {
    // Esta función actualizaría el panel de alertas en el UI
    AgroMonitor.log('Panel de alertas actualizado', 'info');
}

/**
 * Actualizar panel de actividades
 */
function actualizarPanelActividades() {
    // Esta función actualizaría el panel de actividades en el UI
    AgroMonitor.log('Panel de actividades actualizado', 'info');
}

/**
 * Obtener color del badge según estado
 */
function getEstadoBadgeColor(estado) {
    const colores = {
        'Sembrada': 'primary',
        'En_crecimiento': 'success',
        'Cosechada': 'warning',
        'Finalizada': 'secondary'
    };
    return colores[estado] || 'secondary';
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