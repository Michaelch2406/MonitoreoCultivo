<?php
/**
 * Sistema simple de roles y permisos
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
        return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
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
 * Verificar si el usuario es administrador
 */
function esAdministrador() {
    return tieneRol('administrador');
}

/**
 * Verificar si el usuario es agricultor
 */
function esAgricultor() {
    return tieneRol('agricultor');
}

/**
 * Verificar si el usuario es supervisor
 */
function esSupervisor() {
    return tieneRol('supervisor');
}

/**
 * Verificar si el usuario tiene uno de varios roles
 */
function tieneAlgunRol($roles_permitidos) {
    $rol_actual = obtenerRolUsuario();
    return in_array($rol_actual, $roles_permitidos);
}

/**
 * Requerir que el usuario esté logueado
 */
function requiereLogin($redirigir_a = 'login.php') {
    if (!estaLogueado()) {
        header("Location: $redirigir_a");
        exit();
    }
}

/**
 * Requerir rol específico
 */
function requiereRol($rol_requerido, $redirigir_a = 'dashboard.php') {
    requiereLogin();
    
    if (!tieneRol($rol_requerido)) {
        header("Location: $redirigir_a?error=acceso_denegado");
        exit();
    }
}

/**
 * Requerir que sea administrador
 */
function requiereAdmin($redirigir_a = 'dashboard.php') {
    requiereRol('administrador', $redirigir_a);
}

/**
 * Requerir que sea agricultor
 */
function requiereAgricultor($redirigir_a = 'dashboard.php') {
    requiereRol('agricultor', $redirigir_a);
}

/**
 * Requerir que sea supervisor
 */
function requiereSupervisor($redirigir_a = 'dashboard.php') {
    requiereRol('supervisor', $redirigir_a);
}

/**
 * Requerir uno de varios roles
 */
function requiereAlgunRol($roles_permitidos, $redirigir_a = 'dashboard.php') {
    requiereLogin();
    
    if (!tieneAlgunRol($roles_permitidos)) {
        header("Location: $redirigir_a?error=acceso_denegado");
        exit();
    }
}

/**
 * Obtener datos del usuario actual
 */
function obtenerUsuarioActual() {
    if (estaLogueado()) {
        return array(
            'id' => $_SESSION['user_id'] ?? null,
            'nombre' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'rol' => $_SESSION['user_role'] ?? '',
            'estado' => $_SESSION['user_estado'] ?? ''
        );
    }
    return null;
}

/**
 * Mostrar menú según el rol del usuario
 */
function mostrarMenuSegunRol() {
    $rol = obtenerRolUsuario();
    $usuario = obtenerUsuarioActual();
    
    $menu = array();
    
    // Opciones comunes para todos los usuarios logueados
    if (estaLogueado()) {
        $menu[] = array('titulo' => 'Dashboard', 'url' => 'dashboard.php', 'icono' => 'fas fa-tachometer-alt');
        $menu[] = array('titulo' => 'Mi Perfil', 'url' => 'perfil.php', 'icono' => 'fas fa-user');
    }
    
    // Opciones específicas por rol
    switch ($rol) {
        case 'administrador':
            $menu[] = array('titulo' => 'Gestión de Usuarios', 'url' => 'admin/usuarios.php', 'icono' => 'fas fa-users');
            $menu[] = array('titulo' => 'Configuración Sistema', 'url' => 'admin/configuracion.php', 'icono' => 'fas fa-cogs');
            $menu[] = array('titulo' => 'Reportes Globales', 'url' => 'admin/reportes.php', 'icono' => 'fas fa-chart-bar');
            $menu[] = array('titulo' => 'Backup', 'url' => 'admin/backup.php', 'icono' => 'fas fa-database');
            // También puede acceder a todas las funciones de agricultor
            $menu[] = array('titulo' => 'Mis Fincas', 'url' => 'fincas/index.php', 'icono' => 'fas fa-map-marked-alt');
            $menu[] = array('titulo' => 'Cultivos', 'url' => 'cultivos/index.php', 'icono' => 'fas fa-seedling');
            $menu[] = array('titulo' => 'Monitoreo', 'url' => 'monitoreo/index.php', 'icono' => 'fas fa-eye');
            break;
            
        case 'agricultor':
            $menu[] = array('titulo' => 'Mis Fincas', 'url' => 'fincas/index.php', 'icono' => 'fas fa-map-marked-alt');
            $menu[] = array('titulo' => 'Mis Lotes', 'url' => 'lotes/index.php', 'icono' => 'fas fa-th-large');
            $menu[] = array('titulo' => 'Siembras', 'url' => 'siembras/index.php', 'icono' => 'fas fa-seedling');
            $menu[] = array('titulo' => 'Monitoreo', 'url' => 'monitoreo/index.php', 'icono' => 'fas fa-eye');
            $menu[] = array('titulo' => 'Actividades', 'url' => 'actividades/index.php', 'icono' => 'fas fa-tasks');
            $menu[] = array('titulo' => 'Cosechas', 'url' => 'cosechas/index.php', 'icono' => 'fas fa-apple-alt');
            $menu[] = array('titulo' => 'Gastos', 'url' => 'gastos/index.php', 'icono' => 'fas fa-money-bill-wave');
            $menu[] = array('titulo' => 'Mis Reportes', 'url' => 'reportes/index.php', 'icono' => 'fas fa-chart-line');
            break;
            
        case 'supervisor':
            $menu[] = array('titulo' => 'Agricultores Supervisados', 'url' => 'supervisor/agricultores.php', 'icono' => 'fas fa-users');
            $menu[] = array('titulo' => 'Fincas Supervisadas', 'url' => 'supervisor/fincas.php', 'icono' => 'fas fa-map-marked-alt');
            $menu[] = array('titulo' => 'Monitoreo General', 'url' => 'supervisor/monitoreo.php', 'icono' => 'fas fa-binoculars');
            $menu[] = array('titulo' => 'Reportes Supervisión', 'url' => 'supervisor/reportes.php', 'icono' => 'fas fa-clipboard-list');
            $menu[] = array('titulo' => 'Alertas', 'url' => 'supervisor/alertas.php', 'icono' => 'fas fa-exclamation-triangle');
            break;
    }
    
    return $menu;
}

/**
 * Generar HTML del menú según el rol
 */
function generarHTMLMenu() {
    $menu = mostrarMenuSegunRol();
    $html = '';
    
    foreach ($menu as $item) {
        $html .= '<li class="nav-item">';
        $html .= '<a class="nav-link" href="' . $item['url'] . '">';
        $html .= '<i class="' . $item['icono'] . '"></i> ';
        $html .= $item['titulo'];
        $html .= '</a>';
        $html .= '</li>';
    }
    
    return $html;
}

/**
 * Verificar permisos específicos según el contexto
 */
function verificarPermisoRecurso($recurso, $accion, $propietario_id = null) {
    $rol = obtenerRolUsuario();
    $usuario = obtenerUsuarioActual();
    
    switch ($rol) {
        case 'administrador':
            // Los administradores tienen acceso total
            return true;
            
        case 'agricultor':
            // Los agricultores solo pueden acceder a sus propios recursos
            if ($propietario_id && $propietario_id != $usuario['id']) {
                return false;
            }
            
            // Permisos específicos para agricultores
            $permisos_agricultor = array(
                'fincas' => array('ver', 'crear', 'editar', 'eliminar'),
                'lotes' => array('ver', 'crear', 'editar', 'eliminar'),
                'siembras' => array('ver', 'crear', 'editar', 'eliminar'),
                'monitoreo' => array('ver', 'crear', 'editar'),
                'actividades' => array('ver', 'crear', 'editar', 'eliminar'),
                'cosechas' => array('ver', 'crear', 'editar', 'eliminar'),
                'gastos' => array('ver', 'crear', 'editar', 'eliminar'),
                'perfil' => array('ver', 'editar')
            );
            
            return isset($permisos_agricultor[$recurso]) && 
                   in_array($accion, $permisos_agricultor[$recurso]);
            
        case 'supervisor':
            // Los supervisores pueden ver datos de agricultores supervisados
            $permisos_supervisor = array(
                'fincas' => array('ver'),
                'lotes' => array('ver'),
                'siembras' => array('ver'),
                'monitoreo' => array('ver', 'crear'),
                'actividades' => array('ver'),
                'cosechas' => array('ver'),
                'gastos' => array('ver'),
                'reportes' => array('ver', 'crear'),
                'perfil' => array('ver', 'editar')
            );
            
            return isset($permisos_supervisor[$recurso]) && 
                   in_array($accion, $permisos_supervisor[$recurso]);
            
        default:
            return false;
    }
}

/**
 * Mostrar alerta de acceso denegado
 */
function mostrarAccesoDenegado() {
    return '<div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Acceso Denegado:</strong> No tienes permisos para acceder a este recurso.
            </div>';
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
 * Obtener color del badge según el rol
 */
function obtenerColorRol($rol) {
    $colores = array(
        'administrador' => 'badge-danger',
        'agricultor' => 'badge-success',
        'supervisor' => 'badge-warning'
    );
    
    return isset($colores[$rol]) ? $colores[$rol] : 'badge-secondary';
}
?>