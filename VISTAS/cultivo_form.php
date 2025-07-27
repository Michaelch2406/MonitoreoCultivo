<?php
session_start();
require_once '../CONFIG/global.php';
require_once '../CONFIG/roles.php';
require_once '../MODELOS/cultivos_m.php';

// Verificar sesión simple
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Obtener permisos del usuario
$usuario_actual = obtenerUsuarioActual();
$permisos = obtenerPermisosUsuario($usuario_actual['rol']);

// Determinar si es crear o editar
$accion = $_GET['action'] ?? 'crear';
$es_edicion = ($accion === 'editar');
$titulo = $es_edicion ? 'Editar Cultivo' : 'Crear Nuevo Cultivo';

// Si es edición, obtener datos del cultivo
$cultivo = null;
if ($es_edicion) {
    $tip_id = intval($_GET['id'] ?? 0);
    if ($tip_id <= 0) {
        header("Location: cultivos.php");
        exit();
    }
    
    try {
        $cultivoModel = new Cultivo();
        $resultado = $cultivoModel->obtenerTipoCultivoPorId($tip_id);
        
        if (!$resultado['success']) {
            $_SESSION['mensaje'] = [
                'tipo' => 'error',
                'texto' => 'Cultivo no encontrado'
            ];
            header("Location: cultivos.php");
            exit();
        }
        
        $cultivo = $resultado['cultivo'];
    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error al cargar el cultivo'
        ];
        header("Location: cultivos.php");
        exit();
    }
}

// Verificar permisos
$permiso_requerido = $es_edicion ? 'editar' : 'crear';
if (!isset($permisos['cultivos'][$permiso_requerido]) || !$permisos['cultivos'][$permiso_requerido]) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'No tienes permisos para ' . ($es_edicion ? 'editar' : 'crear') . ' cultivos'
    ];
    header("Location: cultivos.php");
    exit();
}

// Obtener listas de opciones
$categorias_disponibles = [
    'cereales' => 'Cereales',
    'hortalizas' => 'Hortalizas',
    'leguminosas' => 'Leguminosas',
    'frutales' => 'Frutales',
    'tuberculos' => 'Tubérculos',
    'aromaticas' => 'Aromáticas'
];

$ciclos_vida = [
    'anual' => 'Anual',
    'perenne' => 'Perenne',
    'bianual' => 'Bianual'
];

// Datos del formulario (para repoblar en caso de errores)
$datos_form = $_SESSION['datos_form'] ?? ($cultivo ?: []);
$errores = $_SESSION['errores'] ?? [];

// Limpiar datos de sesión
unset($_SESSION['datos_form'], $_SESSION['errores']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - AgroMonitor</title>
    <link href="../Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="CSS/dashboard.css" rel="stylesheet">
    <link href="CSS/cultivos.css" rel="stylesheet">
    <link href="partials/CSS/navbar.css" rel="stylesheet">
    <link href="partials/CSS/footer.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Incluir Navbar -->
    <?php include 'partials/navbar.php'; ?>

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Header del Dashboard -->
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="dashboard-title">
                            <i class="fas fa-<?php echo $es_edicion ? 'edit' : 'plus'; ?> me-2"></i>
                            <?php echo $titulo; ?>
                        </h1>
                        <p class="dashboard-subtitle">
                            <?php echo $es_edicion ? 'Modifica la información del cultivo seleccionado' : 'Agrega un nuevo tipo de cultivo al catálogo'; ?>
                        </p>
                        <div class="admin-badge">
                            <i class="fas fa-seedling me-1"></i>
                            Gestión de Cultivos
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="cultivos.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al Catálogo
                        </a>
                    </div>
                </div>
            </div>

        <!-- Errores -->
        <?php if (!empty($errores)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>Errores de Validación:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form action="../CONTROLADORES/cultivos_c.php?action=guardar" method="POST" id="cultivoForm" novalidate>
            <input type="hidden" name="accion" value="<?php echo $es_edicion ? 'editar' : 'crear'; ?>">
            <?php if ($es_edicion): ?>
            <input type="hidden" name="tip_id" value="<?php echo $cultivo['tip_id']; ?>">
            <?php endif; ?>

            <div class="row">
                <!-- Información General -->
                <div class="col-lg-8">
                    <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-info-circle me-2"></i>
                                Información General
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tip_nombre" class="form-label required">Nombre Común *</label>
                                    <input type="text" class="form-control" id="tip_nombre" name="tip_nombre" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_nombre'] ?? ''); ?>" 
                                           required maxlength="100">
                                    <div class="invalid-feedback">El nombre del cultivo es requerido</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_nombre_cientifico" class="form-label">Nombre Científico</label>
                                    <input type="text" class="form-control" id="tip_nombre_cientifico" name="tip_nombre_cientifico" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_nombre_cientifico'] ?? ''); ?>" 
                                           maxlength="150" placeholder="Ej: Solanum lycopersicum">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_familia_botanica" class="form-label">Familia Botánica</label>
                                    <input type="text" class="form-control" id="tip_familia_botanica" name="tip_familia_botanica" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_familia_botanica'] ?? ''); ?>" 
                                           maxlength="100" placeholder="Ej: Solanaceae">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_categoria" class="form-label required">Categoría *</label>
                                    <select class="form-select" id="tip_categoria" name="tip_categoria" required>
                                        <?php foreach ($categorias_disponibles as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                                <?php echo (($datos_form['tip_categoria'] ?? '') === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="tip_descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="tip_descripcion" name="tip_descripcion" 
                                              rows="3" maxlength="1000" 
                                              placeholder="Descripción general del cultivo, usos, características importantes..."><?php echo htmlspecialchars($datos_form['tip_descripcion'] ?? ''); ?></textarea>
                                    <div class="form-text">Máximo 1000 caracteres</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Ciclo -->
                    <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Ciclo de Vida
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tip_ciclo_vida" class="form-label">Tipo de Ciclo</label>
                                    <select class="form-select" id="tip_ciclo_vida" name="tip_ciclo_vida">
                                        <?php foreach ($ciclos_vida as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" 
                                                <?php echo (($datos_form['tip_ciclo_vida'] ?? 'anual') === $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_ciclo_dias" class="form-label">Duración del Ciclo (días)</label>
                                    <input type="number" class="form-control" id="tip_ciclo_dias" name="tip_ciclo_dias" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_ciclo_dias'] ?? ''); ?>" 
                                           min="1" max="3650" placeholder="Ej: 120">
                                    <div class="form-text">Desde siembra hasta cosecha</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Requerimientos Técnicos -->
                    <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-thermometer-half me-2"></i>
                                Requerimientos Técnicos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tip_temperatura_min" class="form-label">Temperatura Mínima (°C)</label>
                                    <input type="number" class="form-control" id="tip_temperatura_min" name="tip_temperatura_min" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_temperatura_min'] ?? ''); ?>" 
                                           step="0.1" min="-50" max="60">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_temperatura_max" class="form-label">Temperatura Máxima (°C)</label>
                                    <input type="number" class="form-control" id="tip_temperatura_max" name="tip_temperatura_max" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_temperatura_max'] ?? ''); ?>" 
                                           step="0.1" min="-50" max="60">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_ph_min" class="form-label">pH Mínimo del Suelo</label>
                                    <input type="number" class="form-control" id="tip_ph_min" name="tip_ph_min" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_ph_min'] ?? ''); ?>" 
                                           step="0.1" min="0" max="14">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_ph_max" class="form-label">pH Máximo del Suelo</label>
                                    <input type="number" class="form-control" id="tip_ph_max" name="tip_ph_max" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_ph_max'] ?? ''); ?>" 
                                           step="0.1" min="0" max="14">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_tipo_suelo" class="form-label">Tipo de Suelo Recomendado</label>
                                    <input type="text" class="form-control" id="tip_tipo_suelo" name="tip_tipo_suelo" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_tipo_suelo'] ?? ''); ?>" 
                                           maxlength="100" placeholder="Ej: Franco arcilloso, bien drenado">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_precipitacion" class="form-label">Precipitación Necesaria</label>
                                    <input type="text" class="form-control" id="tip_precipitacion" name="tip_precipitacion" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_precipitacion'] ?? ''); ?>" 
                                           maxlength="100" placeholder="Ej: 500-800 mm anuales">
                                </div>
                                <div class="col-12">
                                    <label for="tip_temperatura_optima" class="form-label">Temperatura Óptima</label>
                                    <input type="text" class="form-control" id="tip_temperatura_optima" name="tip_temperatura_optima" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_temperatura_optima'] ?? ''); ?>" 
                                           maxlength="50" placeholder="Ej: 18-25°C diurna, 15-18°C nocturna">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Siembra -->
                    <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="400">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-leaf me-2"></i>
                                Información de Siembra
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tip_densidad_siembra" class="form-label">Densidad de Siembra</label>
                                    <input type="text" class="form-control" id="tip_densidad_siembra" name="tip_densidad_siembra" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_densidad_siembra'] ?? ''); ?>" 
                                           maxlength="50" placeholder="Ej: 60,000 plantas/ha">
                                </div>
                                <div class="col-md-6">
                                    <label for="tip_profundidad_siembra" class="form-label">Profundidad de Siembra</label>
                                    <input type="text" class="form-control" id="tip_profundidad_siembra" name="tip_profundidad_siembra" 
                                           value="<?php echo htmlspecialchars($datos_form['tip_profundidad_siembra'] ?? ''); ?>" 
                                           maxlength="50" placeholder="Ej: 3-5 cm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Adicional -->
                    <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="500">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-plus-circle me-2"></i>
                                Información Adicional
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="tip_requerimientos_agua" class="form-label">Requerimientos de Agua</label>
                                    <textarea class="form-control" id="tip_requerimientos_agua" name="tip_requerimientos_agua" 
                                              rows="2" maxlength="500" 
                                              placeholder="Describe los requerimientos hídricos específicos del cultivo..."><?php echo htmlspecialchars($datos_form['tip_requerimientos_agua'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="tip_requerimientos_suelo" class="form-label">Requerimientos de Suelo</label>
                                    <textarea class="form-control" id="tip_requerimientos_suelo" name="tip_requerimientos_suelo" 
                                              rows="2" maxlength="500" 
                                              placeholder="Describe los requerimientos específicos del suelo..."><?php echo htmlspecialchars($datos_form['tip_requerimientos_suelo'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Lateral -->
                <div class="col-lg-4">
                    <!-- Acciones -->
                    <div class="dashboard-card mb-4" data-aos="fade-left" data-aos-delay="100">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-cogs me-2"></i>
                                Acciones
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $es_edicion ? 'Actualizar Cultivo' : 'Crear Cultivo'; ?>
                                </button>
                                <a href="cultivos.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Ayuda -->
                    <div class="dashboard-card" data-aos="fade-left" data-aos-delay="300">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-question-circle me-2"></i>
                                Ayuda
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="help-section">
                                <h6><i class="fas fa-lightbulb me-1"></i> Consejos</h6>
                                <ul class="help-list">
                                    <li>El nombre común debe ser único en el sistema</li>
                                    <li>Usa nombres científicos válidos cuando sea posible</li>
                                    <li>La categoría ayuda a organizar el catálogo</li>
                                    <li>Los rangos de temperatura y pH son importantes para recomendaciones</li>
                                </ul>
                            </div>
                            
                            <div class="help-section mt-3">
                                <h6><i class="fas fa-info-circle me-1"></i> Campos Requeridos</h6>
                                <ul class="help-list">
                                    <li>Nombre común (*)</li>
                                    <li>Categoría (*)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    </main>

    <!-- Incluir Footer -->
    <?php include 'partials/footer.php'; ?>

    <!-- Scripts -->
    <script src="../Bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../PUBLIC/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="JS/global.js"></script>
    <script src="partials/JS/navbar.js"></script>
    <script>
        // Inicializar AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
        
        $(document).ready(function() {
            // Validación del formulario
            $('#cultivoForm').on('submit', function(e) {
                let isValid = true;
                
                // Validar nombre requerido
                const nombre = $('#tip_nombre').val().trim();
                if (!nombre) {
                    $('#tip_nombre').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#tip_nombre').removeClass('is-invalid');
                }
                
                // Validar temperaturas
                const tempMin = parseFloat($('#tip_temperatura_min').val());
                const tempMax = parseFloat($('#tip_temperatura_max').val());
                
                if (!isNaN(tempMin) && !isNaN(tempMax) && tempMin > tempMax) {
                    alert('La temperatura mínima no puede ser mayor que la máxima');
                    isValid = false;
                }
                
                // Validar pH
                const phMin = parseFloat($('#tip_ph_min').val());
                const phMax = parseFloat($('#tip_ph_max').val());
                
                if (!isNaN(phMin) && !isNaN(phMax) && phMin > phMax) {
                    alert('El pH mínimo no puede ser mayor que el máximo');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Contador de caracteres para textareas
            $('textarea[maxlength]').each(function() {
                const $textarea = $(this);
                const maxLength = $textarea.attr('maxlength');
                const $counter = $('<div class="form-text text-end">0/' + maxLength + ' caracteres</div>');
                $textarea.after($counter);
                
                $textarea.on('input', function() {
                    const currentLength = $(this).val().length;
                    $counter.text(currentLength + '/' + maxLength + ' caracteres');
                    
                    if (currentLength > maxLength * 0.9) {
                        $counter.addClass('text-warning');
                    } else {
                        $counter.removeClass('text-warning');
                    }
                });
                
                // Trigger inicial
                $textarea.trigger('input');
            });
        });
    </script>
</body>
</html>