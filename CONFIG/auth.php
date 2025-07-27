<?php
session_start();
require_once "Conexion.php";

class Auth {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    /**
     * Iniciar sesión segura
     */
    public static function iniciarSesion($usuario_data, $remember_me = false) {
        // Regenerar ID de sesión para prevenir fijación de sesión
        session_regenerate_id(true);
        
        // Establecer variables de sesión
        $_SESSION['usuario_id'] = $usuario_data['usuario_id'];
        $_SESSION['nombre'] = $usuario_data['nombre'];
        $_SESSION['apellido'] = $usuario_data['apellido'];
        $_SESSION['email'] = $usuario_data['email'];
        $_SESSION['rol'] = $usuario_data['rol'];
        $_SESSION['fecha_inicio_sesion'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Si se selecciona "recordarme", establecer cookie segura
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 días
            
            // Guardar token en base de datos
            self::guardarTokenRecordarme($usuario_data['usuario_id'], $token, $expiry);
            
            // Establecer cookie
            setcookie('remember_token', $token, $expiry, '/', '', true, true);
        }
        
        return true;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    public static function estaAutenticado() {
        // Verificar sesión activa
        if (isset($_SESSION['usuario_id'])) {
            // Verificar integridad de la sesión
            if (self::verificarIntegridadSesion()) {
                return true;
            } else {
                self::cerrarSesion();
                return false;
            }
        }
        
        // Verificar cookie "recordarme"
        if (isset($_COOKIE['remember_token'])) {
            return self::verificarTokenRecordarme($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Verificar integridad de la sesión
     */
    private static function verificarIntegridadSesion() {
        // Verificar IP (opcional, puede causar problemas con proxies)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            // Opcional: Permitir cambio de IP pero registrar evento
            error_log("Cambio de IP detectado para usuario " . $_SESSION['usuario_id']);
        }
        
        // Verificar que la sesión no sea muy antigua (2 horas)
        if (isset($_SESSION['fecha_inicio_sesion'])) {
            $tiempo_transcurrido = time() - $_SESSION['fecha_inicio_sesion'];
            if ($tiempo_transcurrido > 7200) { // 2 horas
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtener usuario actual
     */
    public static function getUsuario() {
        if (self::estaAutenticado()) {
            return array(
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'],
                'apellido' => $_SESSION['apellido'],
                'email' => $_SESSION['email'],
                'rol' => $_SESSION['rol']
            );
        }
        return null;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function tieneRol($rol_requerido) {
        if (!self::estaAutenticado()) {
            return false;
        }
        
        $usuario = self::getUsuario();
        return $usuario['rol'] === $rol_requerido;
    }
    
    /**
     * Verificar si el usuario tiene uno de varios roles
     */
    public static function tieneAlgunRol($roles_permitidos) {
        if (!self::estaAutenticado()) {
            return false;
        }
        
        $usuario = self::getUsuario();
        return in_array($usuario['rol'], $roles_permitidos);
    }
    
    /**
     * Middleware para requerir autenticación
     */
    public static function requiereAuth($redirigir_a = 'login.php') {
        if (!self::estaAutenticado()) {
            header("Location: $redirigir_a");
            exit();
        }
    }
    
    /**
     * Middleware para requerir rol específico
     */
    public static function requiereRol($rol_requerido, $redirigir_a = 'dashboard.php') {
        self::requiereAuth();
        
        if (!self::tieneRol($rol_requerido)) {
            header("Location: $redirigir_a?error=acceso_denegado");
            exit();
        }
    }
    
    /**
     * Middleware para requerir uno de varios roles
     */
    public static function requiereAlgunRol($roles_permitidos, $redirigir_a = 'dashboard.php') {
        self::requiereAuth();
        
        if (!self::tieneAlgunRol($roles_permitidos)) {
            header("Location: $redirigir_a?error=acceso_denegado");
            exit();
        }
    }
    
    /**
     * Middleware para administradores
     */
    public static function requiereAdmin($redirigir_a = 'dashboard.php') {
        self::requiereRol('administrador', $redirigir_a);
    }
    
    /**
     * Middleware para agricultores
     */
    public static function requiereAgricultor($redirigir_a = 'dashboard.php') {
        self::requiereRol('agricultor', $redirigir_a);
    }
    
    /**
     * Middleware para supervisores
     */
    public static function requiereSupervisor($redirigir_a = 'dashboard.php') {
        self::requiereRol('supervisor', $redirigir_a);
    }
    
    /**
     * Generar token CSRF
     */
    public static function generarCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validarCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Cerrar sesión
     */
    public static function cerrarSesion() {
        // Eliminar cookie "recordarme" si existe
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            self::eliminarTokenRecordarme($_COOKIE['remember_token']);
        }
        
        // Limpiar variables de sesión
        $_SESSION = array();
        
        // Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
    }
    
    /**
     * Guardar token "recordarme"
     */
    private static function guardarTokenRecordarme($usuario_id, $token, $expiry) {
        try {
            $conexion = new Conexion();
            
            // Crear tabla si no existe
            $sql_create = "CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expiry DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_usuario (usuario_id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(usu_id) ON DELETE CASCADE
            )";
            $conexion->getMysqli()->query($sql_create);
            
            // Eliminar tokens anteriores del usuario
            $sql_delete = "DELETE FROM remember_tokens WHERE usuario_id = $usuario_id";
            $conexion->getMysqli()->query($sql_delete);
            
            // Insertar nuevo token
            $token_hash = hash('sha256', $token);
            $expiry_date = date('Y-m-d H:i:s', $expiry);
            $sql_insert = "INSERT INTO remember_tokens (usuario_id, token, expiry) VALUES ($usuario_id, '$token_hash', '$expiry_date')";
            $conexion->getMysqli()->query($sql_insert);
            
        } catch (Exception $e) {
            error_log("Error guardando token recordarme: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar token "recordarme"
     */
    private static function verificarTokenRecordarme($token) {
        try {
            $conexion = new Conexion();
            $token_hash = hash('sha256', $token);
            
            $sql = "SELECT rt.usuario_id, u.usu_nombre, u.usu_apellido, u.usu_email, u.usu_rol 
                    FROM remember_tokens rt
                    JOIN usuarios u ON rt.usuario_id = u.usu_id
                    WHERE rt.token = '$token_hash' 
                    AND rt.expiry > NOW()
                    AND u.usu_estado = 'activo'";
            
            $resultado = $conexion->getMysqli()->query($sql);
            
            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                
                // Iniciar sesión automáticamente
                $usuario_data = array(
                    'usuario_id' => $usuario['usuario_id'],
                    'nombre' => $usuario['usu_nombre'],
                    'apellido' => $usuario['usu_apellido'],
                    'email' => $usuario['usu_email'],
                    'rol' => $usuario['usu_rol']
                );
                
                self::iniciarSesion($usuario_data);
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Error verificando token recordarme: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Eliminar token "recordarme"
     */
    private static function eliminarTokenRecordarme($token) {
        try {
            $conexion = new Conexion();
            $token_hash = hash('sha256', $token);
            
            $sql = "DELETE FROM remember_tokens WHERE token = '$token_hash'";
            $conexion->getMysqli()->query($sql);
            
        } catch (Exception $e) {
            error_log("Error eliminando token recordarme: " . $e->getMessage());
        }
    }
    
    /**
     * Limpiar tokens expirados
     */
    public static function limpiarTokensExpirados() {
        try {
            $conexion = new Conexion();
            $sql = "DELETE FROM remember_tokens WHERE expiry < NOW()";
            $conexion->getMysqli()->query($sql);
        } catch (Exception $e) {
            error_log("Error limpiando tokens expirados: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar intento de login
     */
    public static function registrarIntentoLogin($email, $exitoso = false, $ip = null) {
        try {
            $conexion = new Conexion();
            $ip = $ip ?: $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $exitoso_int = $exitoso ? 1 : 0;
            
            // Crear tabla si no existe
            $sql_create = "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                exitoso TINYINT DEFAULT 0,
                fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_ip (ip_address),
                INDEX idx_fecha (fecha_intento)
            )";
            $conexion->getMysqli()->query($sql_create);
            
            // Insertar intento
            $email = $conexion->getMysqli()->real_escape_string($email);
            $ip = $conexion->getMysqli()->real_escape_string($ip);
            $user_agent = $conexion->getMysqli()->real_escape_string($user_agent);
            
            $sql = "INSERT INTO login_attempts (email, ip_address, user_agent, exitoso) 
                    VALUES ('$email', '$ip', '$user_agent', $exitoso_int)";
            $conexion->getMysqli()->query($sql);
            
        } catch (Exception $e) {
            error_log("Error registrando intento de login: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar si IP está bloqueada por intentos fallidos
     */
    public static function ipEstaBloqueada($ip = null) {
        try {
            $ip = $ip ?: $_SERVER['REMOTE_ADDR'];
            $conexion = new Conexion();
            
            // Contar intentos fallidos en los últimos 15 minutos
            $sql = "SELECT COUNT(*) as intentos 
                    FROM login_attempts 
                    WHERE ip_address = '$ip' 
                    AND exitoso = 0 
                    AND fecha_intento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            
            $resultado = $conexion->getMysqli()->query($sql);
            $intentos = $resultado->fetch_assoc()['intentos'];
            
            // Bloquear si hay más de 5 intentos fallidos
            return $intentos >= 5;
            
        } catch (Exception $e) {
            error_log("Error verificando bloqueo de IP: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Renovar sesión (para mantener sesión activa)
     */
    public static function renovarSesion() {
        if (self::estaAutenticado()) {
            $_SESSION['fecha_inicio_sesion'] = time();
            return true;
        }
        return false;
    }
}

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
?>