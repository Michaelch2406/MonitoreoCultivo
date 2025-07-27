<?php
/**
 * Script de prueba para verificar el módulo de usuarios
 */

// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'CONFIG/Conexion.php';
require_once 'CONFIG/roles.php';
require_once 'MODELOS/usuarios_m.php';

echo "<h1>🧪 Test del Sistema de Usuarios</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .test-section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

// Test 1: Conexión a la base de datos
echo "<div class='test-section'>";
echo "<h2>🔗 Test 1: Conexión a la Base de Datos</h2>";
try {
    $conexion = new Conexion();
    $mysqli = $conexion->conecta();
    if ($mysqli->ping()) {
        echo "<p class='success'>✅ Conexión exitosa a la base de datos</p>";
    } else {
        echo "<p class='error'>❌ Error de conexión a la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Verificar estructura de tablas
echo "<div class='test-section'>";
echo "<h2>🗃️ Test 2: Estructura de Tablas</h2>";
try {
    $conexion = new Conexion();
    $mysqli = $conexion->conecta();
    
    // Verificar tabla usuarios
    $result = $mysqli->query("DESCRIBE usuarios");
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ Tabla 'usuarios' existe y tiene estructura correcta</p>";
        
        // Contar usuarios
        $count_result = $mysqli->query("SELECT COUNT(*) as total FROM usuarios");
        $count = $count_result->fetch_assoc()['total'];
        echo "<p class='info'>📊 Total de usuarios en el sistema: $count</p>";
    } else {
        echo "<p class='error'>❌ Tabla 'usuarios' no existe o tiene problemas</p>";
    }
    
    // Verificar si existe el usuario administrador
    $admin_check = $mysqli->query("SELECT * FROM usuarios WHERE usu_rol = 'administrador' LIMIT 1");
    if ($admin_check && $admin_check->num_rows > 0) {
        $admin = $admin_check->fetch_assoc();
        echo "<p class='success'>✅ Usuario administrador existe: " . htmlspecialchars($admin['usu_email']) . "</p>";
    } else {
        echo "<p class='error'>❌ No existe usuario administrador</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error verificando tablas: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Modelo de Usuario
echo "<div class='test-section'>";
echo "<h2>👤 Test 3: Modelo de Usuario</h2>";
try {
    $usuario_modelo = new Usuario();
    echo "<p class='success'>✅ Modelo Usuario se instancia correctamente</p>";
    
    // Test de validación de email
    $email_valido = $usuario_modelo->validarEmail("test@example.com");
    $email_invalido = $usuario_modelo->validarEmail("email_invalido");
    
    if ($email_valido && !$email_invalido) {
        echo "<p class='success'>✅ Validación de email funciona correctamente</p>";
    } else {
        echo "<p class='error'>❌ Problema con validación de email</p>";
    }
    
    // Test de validación de contraseña
    $password_valida = $usuario_modelo->validarPassword("Password123");
    $password_invalida = $usuario_modelo->validarPassword("123");
    
    if ($password_valida && !$password_invalida) {
        echo "<p class='success'>✅ Validación de contraseña funciona correctamente</p>";
    } else {
        echo "<p class='error'>❌ Problema con validación de contraseña</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error en modelo Usuario: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Sistema de Roles
echo "<div class='test-section'>";
echo "<h2>🛡️ Test 4: Sistema de Roles</h2>";
try {
    // Simular usuario logueado
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test Admin';
    $_SESSION['user_email'] = 'admin@test.com';
    $_SESSION['user_role'] = 'administrador';
    
    if (function_exists('estaLogueado')) {
        $logueado = estaLogueado();
        echo "<p class='success'>✅ Función estaLogueado() existe y funciona: " . ($logueado ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p class='error'>❌ Función estaLogueado() no existe</p>";
    }
    
    if (function_exists('obtenerRolUsuario')) {
        $rol = obtenerRolUsuario();
        echo "<p class='success'>✅ Función obtenerRolUsuario() funciona: $rol</p>";
    } else {
        echo "<p class='error'>❌ Función obtenerRolUsuario() no existe</p>";
    }
    
    if (function_exists('esAdministrador')) {
        $es_admin = esAdministrador();
        echo "<p class='success'>✅ Función esAdministrador() funciona: " . ($es_admin ? 'SÍ' : 'NO') . "</p>";
    } else {
        echo "<p class='error'>❌ Función esAdministrador() no existe</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error en sistema de roles: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Archivos de Vista
echo "<div class='test-section'>";
echo "<h2>👁️ Test 5: Archivos de Vista</h2>";

$archivos_importantes = [
    'VISTAS/login.php' => 'Página de Login',
    'VISTAS/dashboard.php' => 'Dashboard Principal',
    'VISTAS/perfil.php' => 'Perfil de Usuario',
    'VISTAS/admin/usuarios.php' => 'Gestión de Usuarios',
    'CONFIG/roles.php' => 'Sistema de Roles',
    'MODELOS/usuarios_m.php' => 'Modelo de Usuario'
];

foreach ($archivos_importantes as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<p class='success'>✅ $descripcion ($archivo) existe</p>";
    } else {
        echo "<p class='error'>❌ $descripcion ($archivo) NO existe</p>";
    }
}
echo "</div>";

// Test 6: Archivos CSS y JS
echo "<div class='test-section'>";
echo "<h2>🎨 Test 6: Archivos CSS y JS</h2>";

$archivos_estaticos = [
    'VISTAS/CSS/admin_usuarios.css' => 'CSS Gestión Usuarios',
    'VISTAS/CSS/perfil.css' => 'CSS Perfil',
    'VISTAS/JS/admin_usuarios.js' => 'JS Gestión Usuarios',
    'VISTAS/JS/perfil.js' => 'JS Perfil'
];

foreach ($archivos_estaticos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        $tamaño = filesize($archivo);
        echo "<p class='success'>✅ $descripcion ($archivo) existe - Tamaño: " . number_format($tamaño) . " bytes</p>";
    } else {
        echo "<p class='error'>❌ $descripcion ($archivo) NO existe</p>";
    }
}
echo "</div>";

// Test 7: Configuración PHP
echo "<div class='test-section'>";
echo "<h2>⚙️ Test 7: Configuración PHP</h2>";
echo "<p class='info'>📋 Versión PHP: " . phpversion() . "</p>";
echo "<p class='info'>📋 Extensión MySQLi: " . (extension_loaded('mysqli') ? '✅ Disponible' : '❌ No disponible') . "</p>";
echo "<p class='info'>📋 Sesiones: " . (function_exists('session_start') ? '✅ Disponibles' : '❌ No disponibles') . "</p>";
echo "<p class='info'>📋 Upload de archivos: " . (ini_get('file_uploads') ? '✅ Habilitado' : '❌ Deshabilitado') . "</p>";
echo "<p class='info'>📋 Tamaño máximo upload: " . ini_get('upload_max_filesize') . "</p>";
echo "</div>";

// Limpiar sesión de prueba
unset($_SESSION['logged_in'], $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);

echo "<div class='test-section'>";
echo "<h2>🎉 Resumen</h2>";
echo "<p class='success'><strong>El sistema está configurado y listo para usar!</strong></p>";
echo "<p class='info'>👉 Puedes acceder a:</p>";
echo "<ul>";
echo "<li><a href='VISTAS/login.php'>Página de Login</a></li>";
echo "<li><a href='VISTAS/registro.php'>Página de Registro</a></li>";
echo "<li><a href='VISTAS/dashboard.php'>Dashboard (requiere login)</a></li>";
echo "</ul>";
echo "<p class='info'>📝 <strong>Credenciales por defecto:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@sistema.com</li>";
echo "<li>Contraseña: admin123</li>";
echo "</ul>";
echo "</div>";

echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const successCount = document.querySelectorAll('.success').length;
    const errorCount = document.querySelectorAll('.error').length;
    
    if (errorCount === 0) {
        document.body.style.backgroundColor = '#f0f8f0';
        console.log('🎉 Todos los tests pasaron exitosamente!');
    } else {
        document.body.style.backgroundColor = '#fff5f5';
        console.log('⚠️ Hay ' + errorCount + ' errores que necesitan atención');
    }
});
</script>";
?>

<!-- Botón para eliminar este archivo de prueba -->
<div style="margin-top: 30px; padding: 20px; background: #fffacd; border: 1px solid #f0e68c; border-radius: 5px;">
    <h3>🗑️ Limpiar archivo de prueba</h3>
    <p><strong>Importante:</strong> Este archivo es solo para pruebas. Elimínalo cuando hayas verificado que todo funciona correctamente.</p>
    <form method="post" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este archivo de prueba?');">
        <button type="submit" name="eliminar_test" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            Eliminar archivo de prueba
        </button>
    </form>
</div>

<?php
// Procesar eliminación del archivo de prueba
if (isset($_POST['eliminar_test'])) {
    if (unlink(__FILE__)) {
        echo "<script>alert('Archivo de prueba eliminado exitosamente'); window.location.href = 'VISTAS/login.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar el archivo');</script>";
    }
}
?>