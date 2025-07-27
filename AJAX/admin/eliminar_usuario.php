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

    // Validaciones básicas
    if (!$usuario_id) {
        echo json_encode(array(
            'success' => false,
            'message' => 'ID de usuario no válido'
        ));
        exit;
    }

    // Verificar que no se esté auto-eliminando
    if ($usuario_id == $_SESSION['user_id']) {
        echo json_encode(array(
            'success' => false,
            'message' => 'No puedes eliminarte a ti mismo'
        ));
        exit;
    }

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Eliminar usuario
    $resultado = $usuario_modelo->eliminarUsuario($usuario_id);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Usuario eliminado por administrador: ID $usuario_id - Eliminado por: " . $_SESSION['user_email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en eliminar_usuario.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>