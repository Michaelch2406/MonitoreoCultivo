<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once('../CONFIG/roles.php');
require_once('../MODELOS/usuarios_m.php');

try {
    // Verificar que el usuario esté logueado
    if (!estaLogueado()) {
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
    $password_actual = isset($_POST['password_actual']) ? $_POST['password_actual'] : '';
    $password_nueva = isset($_POST['password_nueva']) ? $_POST['password_nueva'] : '';
    $password_confirmar = isset($_POST['password_confirmar']) ? $_POST['password_confirmar'] : '';

    // Validaciones básicas
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Todos los campos son requeridos'
        ));
        exit;
    }

    // Verificar que las contraseñas nuevas coincidan
    if ($password_nueva !== $password_confirmar) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Las contraseñas nuevas no coinciden'
        ));
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Validar fortaleza de contraseña nueva
    if (!$usuario_modelo->validarPassword($password_nueva)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'La contraseña nueva debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número'
        ));
        exit;
    }

    // Cambiar contraseña
    $resultado = $usuario_modelo->cambiarPassword($usuario_id, $password_actual, $password_nueva);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Contraseña cambiada por usuario: " . $usuario_actual['email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en cambiar_password.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>