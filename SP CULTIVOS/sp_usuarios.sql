-- Stored Procedures para Gesti�n de Usuarios
-- Sistema de Monitoreo de Cultivos

USE sistemacultivos;

DELIMITER //

-- Stored Procedure para Registrar Usuario
DROP PROCEDURE IF EXISTS sp_registrar_usuario;
DELIMITER //
CREATE PROCEDURE sp_registrar_usuario(
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_email VARCHAR(150),
    IN p_password VARCHAR(255),
    IN p_telefono VARCHAR(20),
    IN p_rol ENUM('administrador', 'agricultor', 'supervisor')
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Verificar si el email ya existe
    SELECT COUNT(*) INTO v_existe FROM usuarios WHERE usu_email = p_email;
    
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El email ya est� registrado';
    ELSE
        -- Insertar nuevo usuario
        INSERT INTO usuarios (
            usu_nombre, 
            usu_apellido, 
            usu_email, 
            usu_password, 
            usu_telefono, 
            usu_rol,
            usu_estado
        ) VALUES (
            p_nombre, 
            p_apellido, 
            p_email, 
            p_password, 
            p_telefono, 
            IFNULL(p_rol, 'agricultor'),
            'activo'
        );
        
        -- Retornar el ID del usuario creado
        SELECT LAST_INSERT_ID() as usuario_id, 'Usuario registrado exitosamente' as mensaje;
    END IF;
    
    COMMIT;
END //
DELIMITER ;

-- Stored Procedure para Login de Usuario
DROP PROCEDURE IF EXISTS sp_login_usuario;
DELIMITER //
CREATE PROCEDURE sp_login_usuario(
    IN p_email VARCHAR(150),
    IN p_password VARCHAR(255)
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    -- Verificar credenciales y estado del usuario
    SELECT COUNT(*) INTO v_count 
    FROM usuarios 
    WHERE usu_email = p_email 
    AND usu_password = p_password 
    AND usu_estado = 'activo';
    
    IF v_count = 1 THEN
        -- Retornar datos del usuario si las credenciales son correctas
        SELECT 
            usu_id,
            usu_nombre,
            usu_apellido,
            usu_email,
            usu_telefono,
            usu_rol,
            usu_estado,
            usu_fecha_registro,
            'Login exitoso' as mensaje,
            'success' as status
        FROM usuarios 
        WHERE usu_email = p_email 
        AND usu_password = p_password 
        AND usu_estado = 'activo';
    ELSE
        -- Retornar error si las credenciales son incorrectas
        SELECT 
            NULL as usu_id,
            NULL as usu_nombre,
            NULL as usu_apellido,
            NULL as usu_email,
            NULL as usu_telefono,
            NULL as usu_rol,
            NULL as usu_estado,
            NULL as usu_fecha_registro,
            'Credenciales incorrectas o usuario inactivo' as mensaje,
            'error' as status;
    END IF;
END //
DELIMITER ;

-- Stored Procedure para Obtener Usuario por ID
DROP PROCEDURE IF EXISTS sp_obtener_usuario;
DELIMITER //
CREATE PROCEDURE sp_obtener_usuario(
    IN p_usuario_id INT
)
BEGIN
    SELECT 
        usu_id,
        usu_nombre,
        usu_apellido,
        usu_email,
        usu_telefono,
        usu_rol,
        usu_estado,
        usu_fecha_registro,
        usu_fecha_actualizacion
    FROM usuarios 
    WHERE usu_id = p_usuario_id;
END //
DELIMITER ;

-- Stored Procedure para Actualizar Usuario
DROP PROCEDURE IF EXISTS sp_actualizar_usuario;
DELIMITER //
CREATE PROCEDURE sp_actualizar_usuario(
    IN p_usuario_id INT,
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_rol ENUM('administrador', 'agricultor', 'supervisor')
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Verificar si el usuario existe
    SELECT COUNT(*) INTO v_existe FROM usuarios WHERE usu_id = p_usuario_id;
    
    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    ELSE
        -- Actualizar datos del usuario
        UPDATE usuarios SET 
            usu_nombre = p_nombre,
            usu_apellido = p_apellido,
            usu_telefono = p_telefono,
            usu_rol = p_rol,
            usu_fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE usu_id = p_usuario_id;
        
        SELECT 'Usuario actualizado exitosamente' as mensaje;
    END IF;
    
    COMMIT;
END //
DELIMITER ;

-- Stored Procedure para Cambiar Password
DROP PROCEDURE IF EXISTS sp_cambiar_password;
DELIMITER //
CREATE PROCEDURE sp_cambiar_password(
    IN p_usuario_id INT,
    IN p_password_actual VARCHAR(255),
    IN p_password_nueva VARCHAR(255)
)
BEGIN
    DECLARE v_password_actual VARCHAR(255);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Obtener password actual
    SELECT usu_password INTO v_password_actual 
    FROM usuarios 
    WHERE usu_id = p_usuario_id;
    
    -- Verificar password actual
    IF v_password_actual != p_password_actual THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Password actual incorrecta';
    ELSE
        -- Actualizar password
        UPDATE usuarios SET 
            usu_password = p_password_nueva,
            usu_fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE usu_id = p_usuario_id;
        
        SELECT 'Password actualizada exitosamente' as mensaje;
    END IF;
    
    COMMIT;
END //
DELIMITER ;

-- Stored Procedure para Activar/Desactivar Usuario
DROP PROCEDURE IF EXISTS sp_cambiar_estado_usuario;
DELIMITER //
-- Esta stored procedure permite activar o desactivar un usuario
CREATE PROCEDURE sp_cambiar_estado_usuario(
    IN p_usuario_id INT,
    IN p_estado ENUM('activo', 'inactivo')
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Verificar si el usuario existe
    SELECT COUNT(*) INTO v_existe FROM usuarios WHERE usu_id = p_usuario_id;
    
    IF v_existe = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado';
    ELSE
        -- Cambiar estado del usuario
        UPDATE usuarios SET 
            usu_estado = p_estado,
            usu_fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE usu_id = p_usuario_id;
        
        SELECT CONCAT('Usuario ', p_estado, ' exitosamente') as mensaje;
    END IF;
    
    COMMIT;
END //
DELIMITER ;


-- Stored Procedure para Listar Usuarios
DROP PROCEDURE IF EXISTS sp_listar_usuarios;
DELIMITER //
CREATE PROCEDURE sp_listar_usuarios(
    IN p_rol VARCHAR(50),
    IN p_estado VARCHAR(20)
)
BEGIN
    SELECT 
        usu_id,
        usu_nombre,
        usu_apellido,
        usu_email,
        usu_telefono,
        usu_rol,
        usu_estado,
        usu_fecha_registro
    FROM usuarios 
    WHERE (p_rol IS NULL OR usu_rol = p_rol)
    AND (p_estado IS NULL OR usu_estado = p_estado)
    ORDER BY usu_fecha_registro DESC;
END //
DELIMITER ;

-- Stored Procedure para Verificar Email �nico
DROP PROCEDURE IF EXISTS sp_verificar_email;
DELIMITER //
CREATE PROCEDURE sp_verificar_email(
    IN p_email VARCHAR(150),
    IN p_usuario_id INT
)
BEGIN
    DECLARE v_existe INT DEFAULT 0;
    
    SELECT COUNT(*) INTO v_existe 
    FROM usuarios 
    WHERE usu_email = p_email 
    AND (p_usuario_id IS NULL OR usu_id != p_usuario_id);
    
    IF v_existe > 0 THEN
        SELECT 'El email ya est� en uso' as mensaje, 'error' as status;
    ELSE
        SELECT 'Email disponible' as mensaje, 'success' as status;
    END IF;
END //

DELIMITER ;