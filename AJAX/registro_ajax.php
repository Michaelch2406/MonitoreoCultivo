<?php
session_start();
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

    // Incluir el modelo de usuario
    require_once('../MODELOS/usuarios_m.php');

    // Obtener y validar datos del POST
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'agricultor';
    $terms = isset($_POST['terms']) ? $_POST['terms'] : false;
    $newsletter = isset($_POST['newsletter']) ? $_POST['newsletter'] : false;

    // Validaciones b�sicas
    $errores = array();

    if (empty($nombre)) {
        $errores[] = 'El nombre es requerido';
    } elseif (strlen($nombre) < 2) {
        $errores[] = 'El nombre debe tener al menos 2 caracteres';
    }

    if (empty($apellido)) {
        $errores[] = 'El apellido es requerido';
    } elseif (strlen($apellido) < 2) {
        $errores[] = 'El apellido debe tener al menos 2 caracteres';
    }

    if (empty($email)) {
        $errores[] = 'El email es requerido';
    }

    if (empty($password)) {
        $errores[] = 'La contrase�a es requerida';
    }

    if ($password !== $confirm_password) {
        $errores[] = 'Las contrase�as no coinciden';
    }

    if (!$terms) {
        $errores[] = 'Debes aceptar los t�rminos y condiciones';
    }

    // Si hay errores b�sicos, devolver inmediatamente
    if (!empty($errores)) {
        echo json_encode(array(
            'success' => false,
            'message' => implode(', ', $errores)
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

    // Validar fortaleza de contrase�a
    if (!$usuario->validarPassword($password)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'La contrase�a debe tener al menos 8 caracteres, una may�scula, una min�scula y un n�mero'
        ));
        exit;
    }

    // Validar tel�fono si est� presente
    if (!empty($telefono)) {
        $telefono_limpio = preg_replace('/[^0-9+]/', '', $telefono);
        if (strlen($telefono_limpio) < 10) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Formato de tel�fono inv�lido'
            ));
            exit;
        }
        $telefono = $telefono_limpio;
    }

    // Validar rol permitido
    $roles_permitidos = array('agricultor', 'supervisor', 'administrador');
    if (!in_array($rol, $roles_permitidos)) {
        $rol = 'agricultor'; // Valor por defecto
    }

    // Limpiar datos de entrada
    $nombre = $usuario->limpiarDatos($nombre);
    $apellido = $usuario->limpiarDatos($apellido);
    $email = $usuario->limpiarDatos($email);

    // Intentar registro
    $resultado = $usuario->registrarUsuario($nombre, $apellido, $email, $password, $telefono, $rol);

    if ($resultado['success']) {
        // Registro exitoso
        
        // Registrar evento en logs
        error_log("Registro exitoso para usuario: " . $email . " - IP: " . $_SERVER['REMOTE_ADDR']);

        // Opcional: Crear sesi�n autom�ticamente despu�s del registro
        $_SESSION['user_id'] = $resultado['user_id'];
        $_SESSION['user_name'] = $nombre . ' ' . $apellido;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $rol;
        $_SESSION['user_estado'] = 'activo';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Si se suscribi� al newsletter, registrarlo
        if ($newsletter) {
            // Aqu� podr�as agregar la l�gica para suscribir al newsletter
            error_log("Usuario " . $email . " se suscribi� al newsletter");
        }

        echo json_encode(array(
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'user' => array(
                'id' => $resultado['user_id'],
                'name' => $nombre . ' ' . $apellido,
                'email' => $email,
                'role' => $rol
            ),
            'auto_login' => true,
            'redirect' => 'dashboard.php'
        ));

    } else {
        // Registro fallido
        error_log("Intento de registro fallido para email: " . $email . " - IP: " . $_SERVER['REMOTE_ADDR'] . " - Raz�n: " . $resultado['message']);

        echo json_encode(array(
            'success' => false,
            'message' => $resultado['message']
        ));
    }

} catch (Exception $e) {
    // Registrar error en log
    error_log("Error en registro_ajax.php: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR']);

    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor. Por favor intenta nuevamente.'
    ));
}
?>