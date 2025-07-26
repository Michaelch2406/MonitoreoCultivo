<?php
require_once("../CONFIG/Conexion.php");

class Usuario {
    private $conexion;

    public function __construct() {
        try {
            $this->conexion = new Conexion();
        } catch (Exception $e) {
            error_log("Error al inicializar Usuario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registrar un nuevo usuario con contrase�a hasheada
     */
    public function registrarUsuario($nombre, $apellido, $email, $password, $telefono = null, $rol = 'agricultor') {
        try {
            // Validar que el email no exista
            if ($this->emailExiste($email)) {
                return array(
                    'success' => false,
                    'message' => 'El email ya est� registrado en el sistema'
                );
            }

            // Hashear la contrase�a usando bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Escapar los datos para prevenir SQL injection
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $apellido = $this->conexion->getMysqli()->real_escape_string($apellido);
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            $telefono = $telefono ? $this->conexion->getMysqli()->real_escape_string($telefono) : null;
            $rol = $this->conexion->getMysqli()->real_escape_string($rol);

            // Llamar al stored procedure
            $sql = "CALL sp_registrar_usuario('$nombre', '$apellido', '$email', '$password_hash', " . 
                   ($telefono ? "'$telefono'" : "NULL") . ", '$rol')";

            $resultado = $this->conexion->ejecutarSP($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente',
                    'user_id' => $this->conexion->getMysqli()->insert_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar el usuario'
                );
            }

        } catch (Exception $e) {
            error_log("Error en registrarUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Autenticar usuario con email y contrase�a
     */
    public function loginUsuario($email, $password) {
        try {
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            
            // Llamar al stored procedure para login
            $sql = "CALL sp_login_usuario('$email')";
            $resultado = $this->conexion->ejecutarSP($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                
                // Verificar si el usuario est� activo
                if ($usuario['estado'] !== 'activo') {
                    return array(
                        'success' => false,
                        'message' => 'La cuenta est� inactiva. Contacta al administrador.'
                    );
                }

                // Verificar la contrase�a usando password_verify
                if (password_verify($password, $usuario['password'])) {
                    // Actualizar �ltimo acceso
                    $this->actualizarUltimoAcceso($usuario['usuario_id']);
                    
                    // Remover la contrase�a del array de respuesta por seguridad
                    unset($usuario['password']);
                    
                    return array(
                        'success' => true,
                        'message' => 'Login exitoso',
                        'user' => $usuario
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => 'Credenciales incorrectas'
                    );
                }
            } else {
                return array(
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                );
            }

        } catch (Exception $e) {
            error_log("Error en loginUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Verificar si un email ya existe en la base de datos
     */
    private function emailExiste($email) {
        try {
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            $sql = "SELECT usuario_id FROM usuarios WHERE email = '$email' LIMIT 1";
            $resultado = $this->conexion->ejecutarSP($sql);
            
            return ($resultado && $resultado->num_rows > 0);
        } catch (Exception $e) {
            error_log("Error en emailExiste: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar el �ltimo acceso del usuario
     */
    private function actualizarUltimoAcceso($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = $usuario_id";
            $this->conexion->ejecutarSP($sql);
        } catch (Exception $e) {
            error_log("Error en actualizarUltimoAcceso: " . $e->getMessage());
        }
    }

    /**
     * Obtener informaci�n del usuario por ID
     */
    public function obtenerUsuario($usuario_id) {
        try {
            $sql = "CALL sp_obtener_usuario($usuario_id)";
            $resultado = $this->conexion->ejecutarSP($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                unset($usuario['password']); // Remover contrase�a por seguridad
                
                return array(
                    'success' => true,
                    'user' => $usuario
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                );
            }

        } catch (Exception $e) {
            error_log("Error en obtenerUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Cambiar contrase�a del usuario
     */
    public function cambiarPassword($usuario_id, $password_actual, $password_nueva) {
        try {
            // Primero obtener la contrase�a actual hasheada
            $sql = "SELECT password FROM usuarios WHERE usuario_id = $usuario_id";
            $resultado = $this->conexion->ejecutarSP($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                
                // Verificar contrase�a actual
                if (password_verify($password_actual, $usuario['password'])) {
                    // Hashear nueva contrase�a
                    $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Llamar stored procedure para cambiar contrase�a
                    $sql = "CALL sp_cambiar_password($usuario_id, '$password_hash')";
                    $resultado = $this->conexion->ejecutarSP($sql);

                    if ($resultado) {
                        return array(
                            'success' => true,
                            'message' => 'Contrase�a actualizada exitosamente'
                        );
                    } else {
                        return array(
                            'success' => false,
                            'message' => 'Error al actualizar la contrase�a'
                        );
                    }
                } else {
                    return array(
                        'success' => false,
                        'message' => 'La contrase�a actual es incorrecta'
                    );
                }
            } else {
                return array(
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                );
            }

        } catch (Exception $e) {
            error_log("Error en cambiarPassword: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Actualizar informaci�n del usuario
     */
    public function actualizarUsuario($usuario_id, $nombre, $apellido, $telefono = null) {
        try {
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $apellido = $this->conexion->getMysqli()->real_escape_string($apellido);
            $telefono = $telefono ? $this->conexion->getMysqli()->real_escape_string($telefono) : null;

            $sql = "CALL sp_actualizar_usuario($usuario_id, '$nombre', '$apellido', " . 
                   ($telefono ? "'$telefono'" : "NULL") . ")";

            $resultado = $this->conexion->ejecutarSP($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el usuario'
                );
            }

        } catch (Exception $e) {
            error_log("Error en actualizarUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }

    /**
     * Validar formato de email
     */
    public function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validar fortaleza de contrase�a
     */
    public function validarPassword($password) {
        // M�nimo 8 caracteres, al menos una may�scula, una min�scula y un n�mero
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/';
        return preg_match($pattern, $password);
    }

    /**
     * Limpiar datos de entrada
     */
    public function limpiarDatos($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    /**
     * Destructor para cerrar conexi�n
     */
    public function __destruct() {
        if ($this->conexion) {
            $this->conexion->cerrarConexion();
        }
    }
}
?>