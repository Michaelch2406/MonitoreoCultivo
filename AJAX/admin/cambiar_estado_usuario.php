<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('../../CONFIG/roles.php');
require_once('../../MODELOS/usuarios_m.php');

try {
    // Verificar que sea administrador
    if (!esAdministrador()) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Acceso denegado'
        ));
        exit;
    }

    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y validar datos
    $usuario_id = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
    $nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

    // Validaciones básicas
    if (!$usuario_id || empty($nuevo_estado)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Datos incompletos'
        ));
        exit;
    }

    // Validar estados permitidos
    $estados_validos = array('activo', 'inactivo');
    if (!in_array($nuevo_estado, $estados_validos)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Estado no válido'
        ));
        exit;
    }

    // Verificar que no se esté auto-desactivando
    if ($usuario_id == $_SESSION['user_id'] && $nuevo_estado == 'inactivo') {
        echo json_encode(array(
            'success' => false,
            'message' => 'No puedes desactivarte a ti mismo'
        ));
        exit;
    }

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Cambiar estado
    $resultado = $usuario_modelo->cambiarEstadoUsuario($usuario_id, $nuevo_estado);

    if ($resultado['success']) {
        // Registrar evento en log
        $accion = $nuevo_estado == 'activo' ? 'activado' : 'desactivado';
        error_log("Usuario $accion por administrador: ID $usuario_id - Acción por: " . $_SESSION['user_email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en cambiar_estado_usuario.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>