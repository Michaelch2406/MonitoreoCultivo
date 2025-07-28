<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('../CONFIG/roles.php');
require_once('../CONFIG/Conexion.php');

try {
    // Verificar que el usuario esté logueado
    if (!estaLogueado()) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Acceso denegado'
        ));
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];
    $rol = $usuario_actual['rol'];

    $conexion = new Conexion();
    $mysqli = $conexion->getMysqli();
    $estadisticas = array();

    // Obtener estadísticas según el rol con consultas simples
    switch($rol) {
        case 'administrador':
            // Contar usuarios
            $result = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE usu_estado = 'activo'");
            $total_usuarios = $result ? $result->fetch_assoc()['total'] : 0;
            
            // Para el resto usamos valores simulados por ahora
            $estadisticas = array(
                'total_usuarios' => $total_usuarios,
                'total_fincas' => 5, // Simulado
                'total_monitoreos' => 25, // Simulado
                'alertas_pendientes' => 3 // Simulado
            );
            break;
            
        case 'agricultor':
            $estadisticas = array(
                'fincas_registradas' => 2, // Simulado
                'siembras_activas' => 8, // Simulado
                'monitoreos_mes' => 12, // Simulado
                'cosechas_registradas' => 4 // Simulado
            );
            break;
            
        case 'supervisor':
            $estadisticas = array(
                'agricultores_supervisados' => 6, // Simulado
                'fincas_asignadas' => 15, // Simulado
                'inspecciones_realizadas' => 18, // Simulado
                'reportes_generados' => 9 // Simulado
            );
            break;
    }

    echo json_encode(array(
        'success' => true,
        'estadisticas' => $estadisticas,
        'rol' => $rol
    ));

} catch (Exception $e) {
    error_log("Error en obtener_estadisticas_usuario.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>