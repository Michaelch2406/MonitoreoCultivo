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
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $rol = isset($_POST['rol']) ? trim($_POST['rol']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'activo';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($rol) || empty($password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Todos los campos obligatorios deben ser completados'
        ));
        exit;
    }

    // Validar roles permitidos
    $roles_validos = array('administrador', 'agricultor', 'supervisor');
    if (!in_array($rol, $roles_validos)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Rol no válido'
        ));
        exit;
    }

    // Validar estados permitidos
    $estados_validos = array('activo', 'inactivo');
    if (!in_array($estado, $estados_validos)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Estado no válido'
        ));
        exit;
    }

    // Crear instancia del modelo
    $usuario_modelo = new Usuario();

    // Validar formato de email
    if (!$usuario_modelo->validarEmail($email)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Formato de email inválido'
        ));
        exit;
    }

    // Validar fortaleza de contraseña
    if (!$usuario_modelo->validarPassword($password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número'
        ));
        exit;
    }

    // Limpiar datos
    $nombre = $usuario_modelo->limpiarDatos($nombre);
    $apellido = $usuario_modelo->limpiarDatos($apellido);
    $email = $usuario_modelo->limpiarDatos($email);
    $telefono = $telefono ? $usuario_modelo->limpiarDatos($telefono) : null;

    // Crear usuario
    $resultado = $usuario_modelo->crearUsuarioPorAdmin($nombre, $apellido, $email, $password, $telefono, $rol, $estado);

    if ($resultado['success']) {
        // Registrar evento en log
        error_log("Usuario creado por administrador: " . $email . " - Creado por: " . $_SESSION['user_email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log("Error en crear_usuario.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>