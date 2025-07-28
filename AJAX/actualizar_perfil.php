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
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;

    // Validaciones básicas
    if (empty($nombre) || empty($apellido)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Nombre y apellido son requeridos'
        ));
        exit;
    }
    
    // Validar email si se está intentando cambiar
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'El formato del email no es válido'
        ));
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Limpiar datos
    $nombre = $usuario_modelo->limpiarDatos($nombre);
    $apellido = $usuario_modelo->limpiarDatos($apellido);
    $email = $usuario_modelo->limpiarDatos($email);
    $telefono = $telefono ? $usuario_modelo->limpiarDatos($telefono) : null;

    // Verificar si es administrador para permitir cambio de email
    $puede_cambiar_email = $usuario_actual['rol'] === 'administrador';
    
    // Actualizar usuario
    if ($puede_cambiar_email && !empty($email)) {
        // Actualizar incluyendo email
        $resultado = $usuario_modelo->actualizarUsuario($usuario_id, $nombre, $apellido, $telefono, $email);
    } else {
        $resultado = $usuario_modelo->actualizarUsuario($usuario_id, $nombre, $apellido, $telefono);
    }

    if ($resultado['success']) {
        // Actualizar datos en la sesión
        $_SESSION['user_name'] = $nombre . ' ' . $apellido;
        
        // Registrar evento en log
        error_log("Perfil actualizado por usuario: " . $usuario_actual['email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en actualizar_perfil.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>