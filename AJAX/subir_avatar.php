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

    // Verificar que se haya subido un archivo
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array(
            'success' => false,
            'message' => 'No se pudo subir el archivo'
        ));
        exit;
    }

    $archivo = $_FILES['avatar'];
    
    // Validar tipo de archivo
    $tipos_permitidos = array('image/jpeg', 'image/png', 'image/gif');
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Solo se permiten archivos JPG, PNG y GIF'
        ));
        exit;
    }

    // Validar tamaño (2MB máximo)
    if ($archivo['size'] > 2 * 1024 * 1024) {
        echo json_encode(array(
            'success' => false,
            'message' => 'El archivo es demasiado grande. Máximo 2MB'
        ));
        exit;
    }

    // Obtener usuario actual
    $usuario_actual = obtenerUsuarioActual();
    $usuario_id = $usuario_actual['id'];

    // Crear directorio de avatares si no existe
    $directorio_avatares = '../PUBLIC/avatares/';
    if (!file_exists($directorio_avatares)) {
        mkdir($directorio_avatares, 0755, true);
    }

    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'avatar_' . $usuario_id . '_' . time() . '.' . $extension;
    $ruta_archivo = $directorio_avatares . $nombre_archivo;

    // Mover archivo subido
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Error al guardar el archivo'
        ));
        exit;
    }

    // Redimensionar imagen si es necesario (opcional)
    // Aquí puedes agregar código para redimensionar la imagen

    // Actualizar ruta en la base de datos
    $usuario_modelo = new Usuario();
    $ruta_relativa = 'PUBLIC/avatares/' . $nombre_archivo;
    $resultado = $usuario_modelo->subirFotoPerfil($usuario_id, $ruta_relativa);

    if ($resultado['success']) {
        // Eliminar avatar anterior si existe
        // Aquí puedes agregar código para eliminar la imagen anterior
        
        // Registrar evento en log
        error_log("Avatar actualizado por usuario: " . $usuario_actual['email'] . " - IP: " . $_SERVER['REMOTE_ADDR']);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Foto de perfil actualizada exitosamente',
            'ruta_avatar' => $ruta_relativa
        ));
    } else {
        // Eliminar archivo si no se pudo guardar en BD
        unlink($ruta_archivo);
        echo json_encode($resultado);
    }

} catch (Exception $e) {
    error_log("Error en subir_avatar.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'message' => 'Error interno del servidor'
    ));
}
?>