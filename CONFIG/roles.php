<?php
/**
 * Sistema simple de roles y permisos - AgroMonitor
 */

/**
 * Verificar si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Obtener rol del usuario actual
 */
function obtenerRolUsuario() {
    if (estaLogueado()) {
        return isset($_SESSION['rol']) ? $_SESSION['rol'] : null;
    }
    return null;
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function tieneRol($rol_requerido) {
    $rol_actual = obtenerRolUsuario();
    return $rol_actual === $rol_requerido;
}

/**
 * Obtener permisos completos del usuario según su rol
 */
function obtenerPermisosUsuario($rol) {
    switch ($rol) {
        case 'administrador':
            return array(
                'cultivos' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true,
                    'categorizar' => true,
                    'exportar' => true
                ),
                'fincas' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'lotes' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'siembras' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'usuarios' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                )
            );
            
        case 'agricultor':
            return array(
                'cultivos' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false,
                    'categorizar' => false,
                    'exportar' => false
                ),
                'fincas' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'lotes' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'siembras' => array(
                    'ver' => true,
                    'crear' => true,
                    'editar' => true,
                    'eliminar' => true
                ),
                'usuarios' => array(
                    'ver' => false,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false
                )
            );
            
        case 'supervisor':
            return array(
                'cultivos' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false,
                    'categorizar' => false,
                    'exportar' => false
                ),
                'fincas' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false
                ),
                'lotes' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false
                ),
                'siembras' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false
                ),
                'usuarios' => array(
                    'ver' => true,
                    'crear' => false,
                    'editar' => false,
                    'eliminar' => false
                )
            );
            
        default:
            return array();
    }
}

/**
 * Obtener usuario actual de forma simple
 */
function obtenerUsuarioActual() {
    if (estaLogueado()) {
        return array(
            'id' => $_SESSION['user_id'] ?? null,
            'nombre' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'rol' => $_SESSION['rol'] ?? '',
        );
    }
    return null;
}

/**
 * Obtener texto del rol en español
 */
function obtenerTextoRol($rol) {
    $roles = array(
        'administrador' => 'Administrador',
        'agricultor' => 'Agricultor',
        'supervisor' => 'Supervisor'
    );
    
    return isset($roles[$rol]) ? $roles[$rol] : $rol;
}

/**
 * Verificar permisos específicos según el contexto
 */
function verificarPermisoRecurso($recurso, $accion, $propietario_id = null) {
    $rol = obtenerRolUsuario();
    $usuario = obtenerUsuarioActual();
    $permisos = obtenerPermisosUsuario($rol);
    
    // Verificar si tiene el permiso básico
    if (!isset($permisos[$recurso][$accion]) || !$permisos[$recurso][$accion]) {
        return false;
    }
    
    // Para agricultores, verificar que sea propietario del recurso
    if ($rol === 'agricultor' && $propietario_id && $propietario_id != $usuario['id']) {
        return false;
    }
    
    return true;
}

/**
 * Obtener color CSS para el badge del rol
 */
function obtenerColorRol($rol) {
    $colores = array(
        'administrador' => 'bg-danger',
        'agricultor' => 'bg-success',
        'supervisor' => 'bg-warning'
    );
    
    return isset($colores[$rol]) ? $colores[$rol] : 'bg-secondary';
}

/**
 * Verificar si el usuario actual es administrador
 */
function esAdministrador() {
    return estaLogueado() && obtenerRolUsuario() === 'administrador';
}

/**
 * Verificar si el usuario actual es agricultor
 */
function esAgricultor() {
    return estaLogueado() && obtenerRolUsuario() === 'agricultor';
}

/**
 * Verificar si el usuario actual es supervisor
 */
function esSupervisor() {
    return estaLogueado() && obtenerRolUsuario() === 'supervisor';
}

/**
 * Requiere que el usuario sea administrador o redirige
 */
function requiereAdmin() {
    if (!esAdministrador()) {
        header('HTTP/1.1 403 Forbidden');
        exit('Acceso denegado');
    }
}

/**
 * Verificar sesión (alias de estaLogueado para compatibilidad)
 */
function verificarSesion() {
    return estaLogueado();
}

/**
 * Requiere que el usuario esté logueado
 */
function requiereSesion() {
    if (!estaLogueado()) {
        header('Location: ../index.php');
        exit();
    }
}
?>