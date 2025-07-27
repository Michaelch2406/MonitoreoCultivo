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
    $nueva_password = isset($_POST['nueva_password']) ? $_POST['nueva_password'] : '';

    // Validaciones básicas
    if (!$usuario_id || empty($nueva_password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Datos incompletos'
        ));
        exit;
    }

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Validar fortaleza de contraseña
    if (!$usuario_modelo->validarPassword($nueva_password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número'
        ));
        exit;
    }

    // Resetear contraseña
    $resultado = $usuario_modelo->resetearPassword($usuario_id, $nueva_password);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Contraseña reseteada por administrador: ID $usuario_id - Acción por: " . $_SESSION['user_email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en resetear_password.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>