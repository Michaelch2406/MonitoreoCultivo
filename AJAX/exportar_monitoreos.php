<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/monitoreo_m.php');

try {
    // Verificar que el usuario esté logueado
    if (!estaLogueado()) {
        header("Location: ../VISTAS/login.php");
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];
    $rol = $usuario_actual['rol'];

    // Crear instancia del modelo
    $monitoreo_modelo = new Monitoreo();

    // Obtener monitoreos
    $resultado = $monitoreo_modelo->listarMonitoreos($usuario_id, $rol);
    
    if (!$resultado['success']) {
        throw new Exception('Error al obtener los monitoreos');
    }

    $monitoreos = $resultado['monitoreos'];

    // Configurar headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="monitoreos_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    // Crear contenido del archivo Excel
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>Fecha</th>';
    echo '<th>Siembra</th>';
    echo '<th>Lote</th>';
    echo '<th>Finca</th>';
    echo '<th>Estado General</th>';
    echo '<th>Altura Promedio (cm)</th>';
    echo '<th>% Germinación</th>';
    echo '<th>Color Follaje</th>';
    echo '<th>Presencia Plagas</th>';
    echo '<th>Tipo Plagas</th>';
    echo '<th>Presencia Enfermedades</th>';
    echo '<th>Tipo Enfermedades</th>';
    echo '<th>Condición Clima</th>';
    echo '<th>Humedad Suelo</th>';
    echo '<th>Responsable</th>';
    echo '<th>Observaciones</th>';
    echo '</tr>';

    foreach ($monitoreos as $monitoreo) {
        echo '<tr>';
        echo '<td>' . date('d/m/Y', strtotime($monitoreo['mon_fecha_observacion'])) . '</td>';
        echo '<td>' . htmlspecialchars($monitoreo['nombre_cultivo']) . '</td>';
        echo '<td>' . htmlspecialchars($monitoreo['nombre_lote']) . '</td>';
        echo '<td>' . htmlspecialchars($monitoreo['nombre_finca']) . '</td>';
        echo '<td>' . ucfirst($monitoreo['mon_estado_general']) . '</td>';
        echo '<td>' . ($monitoreo['mon_altura_promedio'] ?? 'N/A') . '</td>';
        echo '<td>' . ($monitoreo['mon_porcentaje_germinacion'] ?? 'N/A') . '</td>';
        echo '<td>' . ($monitoreo['mon_color_follaje'] ?? 'N/A') . '</td>';
        echo '<td>' . ucfirst($monitoreo['mon_presencia_plagas']) . '</td>';
        echo '<td>' . ($monitoreo['mon_tipo_plagas'] ?? 'N/A') . '</td>';
        echo '<td>' . ucfirst($monitoreo['mon_presencia_enfermedades']) . '</td>';
        echo '<td>' . ($monitoreo['mon_tipo_enfermedades'] ?? 'N/A') . '</td>';
        echo '<td>' . ($monitoreo['mon_condicion_clima'] ?? 'N/A') . '</td>';
        echo '<td>' . ucfirst($monitoreo['mon_humedad_suelo']) . '</td>';
        echo '<td>' . htmlspecialchars($monitoreo['responsable_nombre']) . '</td>';
        echo '<td>' . htmlspecialchars($monitoreo['mon_observaciones'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';

} catch (Exception $e) {
    error_log("Error en exportar_monitoreos.php: " . $e->getMessage());
    header("Location: ../VISTAS/monitoreo.php?error=export_failed");
    exit;
}
?>