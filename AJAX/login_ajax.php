<?php
header('Content-Type: application/json; charset=utf-8');

// Configurar manejo de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../php_error.log');

try {
    // Verificar que sea una petici�n POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('M�todo no permitido');
    }

    // Incluir dependencias
    require_once('../CONFIG/auth.php');
    require_once('../MODELOS/usuarios_m.php');

    // Obtener y validar datos del POST
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? $_POST['remember'] : false;

    // Validaciones b�sicas
    if (empty($email) || empty($password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Email y contrase�a son requeridos'
        ));
        exit;
    }

    // Crear instancia del modelo Usuario
    $usuario = new Usuario();

    // Validar formato de email
    if (!$usuario->validarEmail($email)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Formato de email inv�lido'
        ));
        exit;
    }

    // Limpiar datos de entrada
    $email = $usuario->limpiarDatos($email);

    // Intentar login
    $resultado = $usuario->loginUsuario($email, $password);

    if ($resultado['success']) {
        // Login exitoso - crear sesi�n
        $_SESSION['user_id'] = $resultado['user']['usuario_id'];
        $_SESSION['user_name'] = $resultado['user']['nombre'] . ' ' . $resultado['user']['apellido'];
        $_SESSION['user_email'] = $resultado['user']['email'];
        $_SESSION['user_role'] = $resultado['user']['rol'];
        $_SESSION['user_estado'] = $resultado['user']['estado'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Si seleccion� "recordar sesi�n", crear cookie por 30 d�as
        if ($remember) {
            $cookie_name = "remember_user";
            $cookie_value = base64_encode($resultado['user']['usuario_id'] . '|' . $resultado['user']['email']);
            $cookie_expire = time() + (30 * 24 * 60 * 60); // 30 d�as
            setcookie($cookie_name, $cookie_value, $cookie_expire, '/', '', false, true);
        }

        // Registrar evento de login en logs
        error_log("Login exitoso para usuario: " . $email . " - IP: " . $_SERVER['REMOTE_ADDR']);

        echo json_encode(array(
            'success' => true,
            'message' => 'Login exitoso',
            'user' => array(
                'id' => $resultado['user']['usuario_id'],
                'name' => $resultado['user']['nombre'] . ' ' . $resultado['user']['apellido'],
                'email' => $resultado['user']['email'],
                'role' => $resultado['user']['rol']
            ),
            'redirect' => 'dashboard.php'
        ));

    } else {
        // Login fallido - registrar intento
        error_log("Intento de login fallido para email: " . $email . " - IP: " . $_SERVER['REMOTE_ADDR'] . " - Raz�n: " . $resultado['message']);

        // Agregar delay para prevenir ataques de fuerza bruta
        sleep(1);

        echo json_encode(array(
            'success' => false,
            'message' => $resultado['message']
        ));
    }

} catch (Exception $e) {
    // Registrar error en log
    error_log("Error en login_ajax.php: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR']);

    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor. Por favor intenta nuevamente.'
    ));
}
?>