<?php
session_start();
require_once('../CONFIG/roles.php');
require_once('../MODELOS/notificaciones_m.php');

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_actual = obtenerUsuarioActual();
$notificaciones_model = new NotificacionesModel();

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'obtener':
        $limite = $_GET['limite'] ?? 5;
        $notificaciones = $notificaciones_model->obtenerNotificacionesUsuario($usuario_actual['id'], $limite);
        $total_no_vistas = $notificaciones_model->contarNotificacionesNoVistas($usuario_actual['id']);
        
        // Formatear las notificaciones para el frontend
        $notificaciones_formateadas = [];
        foreach ($notificaciones as $notif) {
            $icono = '';
            $clase_color = '';
            
            switch ($notif['ale_tipo']) {
                case 'riego':
                    $icono = 'fas fa-tint';
                    $clase_color = 'text-primary';
                    break;
                case 'fertilizacion':
                    $icono = 'fas fa-seedling';
                    $clase_color = 'text-success';
                    break;
                case 'fumigacion':
                    $icono = 'fas fa-spray-can';
                    $clase_color = 'text-warning';
                    break;
                case 'cosecha':
                    $icono = 'fas fa-tractor';
                    $clase_color = 'text-info';
                    break;
                default:
                    $icono = 'fas fa-bell';
                    $clase_color = 'text-secondary';
            }
            
            switch ($notif['ale_prioridad']) {
                case 'critica':
                    $clase_prioridad = 'text-danger';
                    break;
                case 'alta':
                    $clase_prioridad = 'text-warning';
                    break;
                case 'media':
                    $clase_prioridad = 'text-info';
                    break;
                default:
                    $clase_prioridad = 'text-muted';
            }
            
            $tiempo_transcurrido = calcularTiempoTranscurrido($notif['ale_fecha_registro']);
            
            $notificaciones_formateadas[] = [
                'id' => $notif['ale_id'],
                'titulo' => $notif['ale_titulo'],
                'mensaje' => $notif['ale_mensaje'],
                'tipo' => $notif['ale_tipo'],
                'prioridad' => $notif['ale_prioridad'],
                'estado' => $notif['ale_estado'],
                'icono' => $icono,
                'clase_color' => $clase_color,
                'clase_prioridad' => $clase_prioridad,
                'tiempo' => $tiempo_transcurrido,
                'cultivo' => $notif['cultivo_nombre'] ?? 'General',
                'fecha_programada' => $notif['ale_fecha_programada']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notificaciones' => $notificaciones_formateadas,
            'total_no_vistas' => $total_no_vistas
        ]);
        break;
        
    case 'marcar_vista':
        $alerta_id = $_POST['alerta_id'] ?? 0;
        
        if ($alerta_id > 0) {
            $resultado = $notificaciones_model->marcarComoVista($alerta_id, $usuario_actual['id']);
            echo json_encode(['success' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de alerta inválido']);
        }
        break;
        
    case 'marcar_todas_vistas':
        $resultado = $notificaciones_model->marcarTodasComoVistas($usuario_actual['id']);
        echo json_encode(['success' => $resultado]);
        break;
        
    case 'contar_no_vistas':
        $total = $notificaciones_model->contarNotificacionesNoVistas($usuario_actual['id']);
        echo json_encode(['success' => true, 'total' => $total]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function calcularTiempoTranscurrido($fecha) {
    $fecha_actual = new DateTime();
    $fecha_notificacion = new DateTime($fecha);
    $diferencia = $fecha_actual->diff($fecha_notificacion);
    
    if ($diferencia->d > 0) {
        return "Hace " . $diferencia->d . " día" . ($diferencia->d > 1 ? "s" : "");
    } elseif ($diferencia->h > 0) {
        return "Hace " . $diferencia->h . " hora" . ($diferencia->h > 1 ? "s" : "");
    } elseif ($diferencia->i > 0) {
        return "Hace " . $diferencia->i . " minuto" . ($diferencia->i > 1 ? "s" : "");
    } else {
        return "Hace un momento";
    }
}
?>