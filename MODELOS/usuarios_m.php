<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_error.log');
require_once(dirname(__FILE__) . "/../CONFIG/Conexion.php");

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

            // Insertar usuario directamente en la tabla (nomenclatura BD real)
            $sql = "INSERT INTO usuarios (usu_nombre, usu_apellido, usu_email, usu_password, usu_telefono, usu_rol, usu_estado, usu_fecha_registro) 
                    VALUES ('$nombre', '$apellido', '$email', '$password_hash', " . 
                   ($telefono ? "'$telefono'" : "NULL") . ", '$rol', 'activo', NOW())";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $user_id = $this->conexion->getMysqli()->insert_id;
                return array(
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente',
                    'user_id' => $user_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al registrar el usuario: ' . $this->conexion->getMysqli()->error
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
            
            // Consulta directa a la tabla usuarios (nomenclatura BD real)
            $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_email, usu_password, usu_rol, usu_estado, usu_fecha_registro 
                    FROM usuarios 
                    WHERE usu_email = '$email' AND usu_estado = 'activo'";
            
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                

                // Verificar la contrase�a usando password_verify
                if (password_verify($password, $usuario['usu_password'])) {
                    // Actualizar �ltimo acceso
                    $this->actualizarUltimoAcceso($usuario['usu_id']);
                    
                    // Adaptar nombres de campos para compatibilidad
                    $usuario_adaptado = array(
                        'usuario_id' => $usuario['usu_id'],
                        'nombre' => $usuario['usu_nombre'],
                        'apellido' => $usuario['usu_apellido'],
                        'email' => $usuario['usu_email'],
                        'rol' => $usuario['usu_rol'],
                        'estado' => $usuario['usu_estado'],
                        'fecha_creacion' => $usuario['usu_fecha_registro']
                    );
                    
                    return array(
                        'success' => true,
                        'message' => 'Login exitoso',
                        'user' => $usuario_adaptado
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
            $sql = "SELECT usu_id FROM usuarios WHERE usu_email = '$email' LIMIT 1";
            $resultado = $this->conexion->getMysqli()->query($sql);
            
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
            $sql = "UPDATE usuarios SET usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";
            $this->conexion->getMysqli()->query($sql);
        } catch (Exception $e) {
            error_log("Error en actualizarUltimoAcceso: " . $e->getMessage());
        }
    }

    /**
     * Obtener informaci�n del usuario por ID
     */
    public function obtenerUsuario($usuario_id) {
        try {
            $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_email, usu_telefono, usu_rol, usu_estado, usu_fecha_registro FROM usuarios WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                // Adaptar nombres de campos para compatibilidad
                $usuario_adaptado = array(
                    'usuario_id' => $usuario['usu_id'],
                    'nombre' => $usuario['usu_nombre'],
                    'apellido' => $usuario['usu_apellido'],
                    'email' => $usuario['usu_email'],
                    'telefono' => $usuario['usu_telefono'],
                    'rol' => $usuario['usu_rol'],
                    'estado' => $usuario['usu_estado'],
                    'fecha_creacion' => $usuario['usu_fecha_registro']
                );
                
                return array(
                    'success' => true,
                    'user' => $usuario_adaptado
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
            $sql = "SELECT usu_password FROM usuarios WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                $usuario = $resultado->fetch_assoc();
                
                // Verificar contrase�a actual
                if (password_verify($password_actual, $usuario['usu_password'])) {
                    // Hashear nueva contrase�a
                    $password_hash = password_hash($password_nueva, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Llamar stored procedure para cambiar contrase�a
                    $sql = "UPDATE usuarios SET usu_password = '$password_hash', usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";
                    $resultado = $this->conexion->getMysqli()->query($sql);

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

            $sql = "UPDATE usuarios SET usu_nombre = '$nombre', usu_apellido = '$apellido', usu_telefono = " .
                   ($telefono ? "'$telefono'" : "NULL") . ", usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

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
     * FUNCIONALIDADES DE ADMINISTRACIÓN
     */
    
    /**
     * Listar todos los usuarios (solo administradores)
     */
    public function listarUsuarios($limite = null, $offset = 0, $filtro_rol = null, $filtro_estado = null) {
        try {
            $sql = "SELECT usu_id, usu_nombre, usu_apellido, usu_email, usu_telefono, usu_rol, usu_estado, usu_fecha_registro, usu_fecha_actualizacion 
                    FROM usuarios WHERE 1=1";
            
            if ($filtro_rol) {
                $filtro_rol = $this->conexion->getMysqli()->real_escape_string($filtro_rol);
                $sql .= " AND usu_rol = '$filtro_rol'";
            }
            
            if ($filtro_estado) {
                $filtro_estado = $this->conexion->getMysqli()->real_escape_string($filtro_estado);
                $sql .= " AND usu_estado = '$filtro_estado'";
            }
            
            $sql .= " ORDER BY usu_fecha_registro DESC";
            
            if ($limite) {
                $sql .= " LIMIT $limite OFFSET $offset";
            }
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $usuarios = array();
                while ($row = $resultado->fetch_assoc()) {
                    $usuarios[] = array(
                        'usuario_id' => $row['usu_id'],
                        'nombre' => $row['usu_nombre'],
                        'apellido' => $row['usu_apellido'],
                        'email' => $row['usu_email'],
                        'telefono' => $row['usu_telefono'],
                        'rol' => $row['usu_rol'],
                        'estado' => $row['usu_estado'],
                        'fecha_registro' => $row['usu_fecha_registro'],
                        'fecha_actualizacion' => $row['usu_fecha_actualizacion']
                    );
                }
                
                return array(
                    'success' => true,
                    'usuarios' => $usuarios
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener usuarios'
                );
            }
        } catch (Exception $e) {
            error_log("Error en listarUsuarios: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Crear usuario por administrador
     */
    public function crearUsuarioPorAdmin($nombre, $apellido, $email, $password, $telefono = null, $rol = 'agricultor', $estado = 'activo') {
        try {
            // Validar que el email no exista
            if ($this->emailExiste($email)) {
                return array(
                    'success' => false,
                    'message' => 'El email ya está registrado en el sistema'
                );
            }

            // Hashear la contraseña
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Escapar los datos
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $apellido = $this->conexion->getMysqli()->real_escape_string($apellido);
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            $telefono = $telefono ? $this->conexion->getMysqli()->real_escape_string($telefono) : null;
            $rol = $this->conexion->getMysqli()->real_escape_string($rol);
            $estado = $this->conexion->getMysqli()->real_escape_string($estado);

            $sql = "INSERT INTO usuarios (usu_nombre, usu_apellido, usu_email, usu_password, usu_telefono, usu_rol, usu_estado, usu_fecha_registro) 
                    VALUES ('$nombre', '$apellido', '$email', '$password_hash', " . 
                   ($telefono ? "'$telefono'" : "NULL") . ", '$rol', '$estado', NOW())";

            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                $user_id = $this->conexion->getMysqli()->insert_id;
                return array(
                    'success' => true,
                    'message' => 'Usuario creado exitosamente',
                    'user_id' => $user_id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al crear el usuario: ' . $this->conexion->getMysqli()->error
                );
            }
        } catch (Exception $e) {
            error_log("Error en crearUsuarioPorAdmin: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Editar usuario por administrador
     */
    public function editarUsuarioPorAdmin($usuario_id, $nombre, $apellido, $email, $telefono = null, $rol = null, $estado = null) {
        try {
            // Verificar que el email no esté siendo usado por otro usuario
            $email_check = $this->conexion->getMysqli()->real_escape_string($email);
            $sql_check = "SELECT usu_id FROM usuarios WHERE usu_email = '$email_check' AND usu_id != $usuario_id LIMIT 1";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado_check && $resultado_check->num_rows > 0) {
                return array(
                    'success' => false,
                    'message' => 'El email ya está siendo usado por otro usuario'
                );
            }

            // Escapar datos
            $nombre = $this->conexion->getMysqli()->real_escape_string($nombre);
            $apellido = $this->conexion->getMysqli()->real_escape_string($apellido);
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            $telefono = $telefono ? $this->conexion->getMysqli()->real_escape_string($telefono) : null;
            
            $sql = "UPDATE usuarios SET 
                    usu_nombre = '$nombre', 
                    usu_apellido = '$apellido', 
                    usu_email = '$email', 
                    usu_telefono = " . ($telefono ? "'$telefono'" : "NULL");
            
            if ($rol) {
                $rol = $this->conexion->getMysqli()->real_escape_string($rol);
                $sql .= ", usu_rol = '$rol'";
            }
            
            if ($estado) {
                $estado = $this->conexion->getMysqli()->real_escape_string($estado);
                $sql .= ", usu_estado = '$estado'";
            }
            
            $sql .= ", usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";

            $resultado = $this->conexion->getMysqli()->query($sql);

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
            error_log("Error en editarUsuarioPorAdmin: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Cambiar estado de usuario (activar/desactivar)
     */
    public function cambiarEstadoUsuario($usuario_id, $nuevo_estado) {
        try {
            $nuevo_estado = $this->conexion->getMysqli()->real_escape_string($nuevo_estado);
            
            $sql = "UPDATE usuarios SET usu_estado = '$nuevo_estado', usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Estado del usuario actualizado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar el estado del usuario'
                );
            }
        } catch (Exception $e) {
            error_log("Error en cambiarEstadoUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Eliminar usuario (solo administradores)
     */
    public function eliminarUsuario($usuario_id) {
        try {
            // Verificar que no sea el único administrador
            $sql_admin_count = "SELECT COUNT(*) as total FROM usuarios WHERE usu_rol = 'administrador' AND usu_estado = 'activo'";
            $resultado_count = $this->conexion->getMysqli()->query($sql_admin_count);
            $admin_count = $resultado_count->fetch_assoc()['total'];
            
            // Verificar si el usuario a eliminar es administrador
            $sql_user_check = "SELECT usu_rol FROM usuarios WHERE usu_id = $usuario_id";
            $resultado_user = $this->conexion->getMysqli()->query($sql_user_check);
            $user_data = $resultado_user->fetch_assoc();
            
            if ($user_data['usu_rol'] == 'administrador' && $admin_count <= 1) {
                return array(
                    'success' => false,
                    'message' => 'No se puede eliminar el último administrador del sistema'
                );
            }
            
            $sql = "DELETE FROM usuarios WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Usuario eliminado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al eliminar el usuario'
                );
            }
        } catch (Exception $e) {
            error_log("Error en eliminarUsuario: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Resetear contraseña de usuario (solo administradores)
     */
    public function resetearPassword($usuario_id, $nueva_password) {
        try {
            $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $sql = "UPDATE usuarios SET usu_password = '$password_hash', usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Contraseña reseteada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al resetear la contraseña'
                );
            }
        } catch (Exception $e) {
            error_log("Error en resetearPassword: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * FUNCIONALIDADES DE SUPERVISOR
     */
    
    /**
     * Obtener usuarios bajo supervisión (agricultores)
     */
    public function obtenerUsuariosSupervisados($supervisor_id) {
        try {
            // Por ahora devolvemos todos los agricultores, en una versión futura se puede crear una tabla de asignaciones
            $sql = "SELECT u.usu_id, u.usu_nombre, u.usu_apellido, u.usu_email, u.usu_telefono, u.usu_estado, u.usu_fecha_registro,
                           COUNT(f.fin_id) as total_fincas
                    FROM usuarios u
                    LEFT JOIN fincas f ON u.usu_id = f.fin_propietario
                    WHERE u.usu_rol = 'agricultor' AND u.usu_estado = 'activo'
                    GROUP BY u.usu_id
                    ORDER BY u.usu_nombre, u.usu_apellido";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $agricultores = array();
                while ($row = $resultado->fetch_assoc()) {
                    $agricultores[] = array(
                        'usuario_id' => $row['usu_id'],
                        'nombre' => $row['usu_nombre'],
                        'apellido' => $row['usu_apellido'],
                        'email' => $row['usu_email'],
                        'telefono' => $row['usu_telefono'],
                        'estado' => $row['usu_estado'],
                        'fecha_registro' => $row['usu_fecha_registro'],
                        'total_fincas' => $row['total_fincas']
                    );
                }
                
                return array(
                    'success' => true,
                    'usuarios' => $agricultores
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener usuarios supervisados'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerUsuariosSupervisados: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * FUNCIONALIDADES DE PERFIL
     */
    
    /**
     * Subir foto de perfil
     */
    public function subirFotoPerfil($usuario_id, $ruta_foto) {
        try {
            // Primero crear la columna si no existe
            $this->agregarColumnaFotoPerfil();
            
            $ruta_foto = $this->conexion->getMysqli()->real_escape_string($ruta_foto);
            
            $sql = "UPDATE usuarios SET usu_foto_perfil = '$ruta_foto', usu_fecha_actualizacion = NOW() WHERE usu_id = $usuario_id";
            $resultado = $this->conexion->getMysqli()->query($sql);

            if ($resultado) {
                return array(
                    'success' => true,
                    'message' => 'Foto de perfil actualizada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar la foto de perfil'
                );
            }
        } catch (Exception $e) {
            error_log("Error en subirFotoPerfil: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Agregar columna foto_perfil si no existe
     */
    private function agregarColumnaFotoPerfil() {
        try {
            $sql_check = "SHOW COLUMNS FROM usuarios LIKE 'usu_foto_perfil'";
            $resultado = $this->conexion->getMysqli()->query($sql_check);
            
            if ($resultado->num_rows == 0) {
                $sql_add = "ALTER TABLE usuarios ADD COLUMN usu_foto_perfil VARCHAR(255) NULL AFTER usu_telefono";
                $this->conexion->getMysqli()->query($sql_add);
            }
        } catch (Exception $e) {
            error_log("Error en agregarColumnaFotoPerfil: " . $e->getMessage());
        }
    }
    
    /**
     * Generar token para recuperación de contraseña
     */
    public function generarTokenRecuperacion($email) {
        try {
            $email = $this->conexion->getMysqli()->real_escape_string($email);
            
            // Verificar que el email existe
            $sql_check = "SELECT usu_id FROM usuarios WHERE usu_email = '$email' AND usu_estado = 'activo'";
            $resultado_check = $this->conexion->getMysqli()->query($sql_check);
            
            if (!$resultado_check || $resultado_check->num_rows == 0) {
                return array(
                    'success' => false,
                    'message' => 'Email no encontrado en el sistema'
                );
            }
            
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Crear tabla de tokens si no existe
            $this->crearTablaTokens();
            
            // Eliminar tokens anteriores del usuario
            $sql_delete = "DELETE FROM password_tokens WHERE email = '$email'";
            $this->conexion->getMysqli()->query($sql_delete);
            
            // Insertar nuevo token
            $sql_insert = "INSERT INTO password_tokens (email, token, expiracion, usado) VALUES ('$email', '$token', '$expiracion', 0)";
            $resultado = $this->conexion->getMysqli()->query($sql_insert);
            
            if ($resultado) {
                return array(
                    'success' => true,
                    'token' => $token,
                    'message' => 'Token de recuperación generado exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al generar el token de recuperación'
                );
            }
        } catch (Exception $e) {
            error_log("Error en generarTokenRecuperacion: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion($token) {
        try {
            $token = $this->conexion->getMysqli()->real_escape_string($token);
            
            $sql = "SELECT email FROM password_tokens 
                    WHERE token = '$token' 
                    AND expiracion > NOW() 
                    AND usado = 0";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado && $resultado->num_rows > 0) {
                $data = $resultado->fetch_assoc();
                return array(
                    'success' => true,
                    'email' => $data['email']
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                );
            }
        } catch (Exception $e) {
            error_log("Error en validarTokenRecuperacion: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Recuperar contraseña con token
     */
    public function recuperarPassword($token, $nueva_password) {
        try {
            // Validar token
            $validacion = $this->validarTokenRecuperacion($token);
            if (!$validacion['success']) {
                return $validacion;
            }
            
            $email = $validacion['email'];
            $password_hash = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Actualizar contraseña
            $sql_update = "UPDATE usuarios SET usu_password = '$password_hash', usu_fecha_actualizacion = NOW() WHERE usu_email = '$email'";
            $resultado_update = $this->conexion->getMysqli()->query($sql_update);
            
            if ($resultado_update) {
                // Marcar token como usado
                $token = $this->conexion->getMysqli()->real_escape_string($token);
                $sql_token = "UPDATE password_tokens SET usado = 1 WHERE token = '$token'";
                $this->conexion->getMysqli()->query($sql_token);
                
                return array(
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al actualizar la contraseña'
                );
            }
        } catch (Exception $e) {
            error_log("Error en recuperarPassword: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
    }
    
    /**
     * Crear tabla de tokens si no existe
     */
    private function crearTablaTokens() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS password_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expiracion DATETIME NOT NULL,
                usado TINYINT DEFAULT 0,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_email (email)
            )";
            $this->conexion->getMysqli()->query($sql);
        } catch (Exception $e) {
            error_log("Error en crearTablaTokens: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas del sistema (solo administradores)
     */
    public function obtenerEstadisticasUsuarios() {
        try {
            $sql = "SELECT 
                        usu_rol,
                        usu_estado,
                        COUNT(*) as total
                    FROM usuarios 
                    GROUP BY usu_rol, usu_estado
                    ORDER BY usu_rol, usu_estado";
            
            $resultado = $this->conexion->getMysqli()->query($sql);
            
            if ($resultado) {
                $estadisticas = array();
                while ($row = $resultado->fetch_assoc()) {
                    $estadisticas[] = $row;
                }
                
                return array(
                    'success' => true,
                    'estadisticas' => $estadisticas
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Error al obtener estadísticas'
                );
            }
        } catch (Exception $e) {
            error_log("Error en obtenerEstadisticasUsuarios: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Error interno del servidor'
            );
        }
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