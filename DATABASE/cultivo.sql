-- Base de Datos para Sistema de Monitoreo de Cultivos
-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS sistemacultivos;
USE sistemacultivos;

-- Tabla de Usuarios
CREATE TABLE usuarios (
    usu_id INT PRIMARY KEY AUTO_INCREMENT,
    usu_nombre VARCHAR(100) NOT NULL,
    usu_apellido VARCHAR(100) NOT NULL,
    usu_email VARCHAR(150) UNIQUE NOT NULL,
    usu_password VARCHAR(255) NOT NULL,
    usu_telefono VARCHAR(20),
    usu_rol ENUM('administrador', 'agricultor', 'supervisor') DEFAULT 'agricultor',
    usu_estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    usu_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usu_fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de Fincas/Predios
CREATE TABLE fincas (
    fin_id INT PRIMARY KEY AUTO_INCREMENT,
    fin_nombre VARCHAR(100) NOT NULL,
    fin_ubicacion TEXT,
    fin_area_total DECIMAL(10,2), -- en hectáreas
    fin_latitud DECIMAL(10,8),
    fin_longitud DECIMAL(11,8),
    fin_propietario INT,
    fin_descripcion TEXT,
    fin_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fin_estado ENUM('activa', 'inactiva') DEFAULT 'activa',
    FOREIGN KEY (fin_propietario) REFERENCES usuarios(usu_id)
);

-- Tabla de Tipos de Cultivos
CREATE TABLE tipos_cultivos (
    tip_id INT PRIMARY KEY AUTO_INCREMENT,
    tip_nombre VARCHAR(100) NOT NULL,
    tip_nombre_cientifico VARCHAR(150),
    tip_ciclo_dias INT, -- duración promedio del ciclo en días
    tip_descripcion TEXT,
    tip_requerimientos_agua TEXT,
    tip_requerimientos_suelo TEXT,
    tip_temperatura_optima VARCHAR(50),
    tip_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Lotes/Parcelas
CREATE TABLE lotes (
    lot_id INT PRIMARY KEY AUTO_INCREMENT,
    lot_nombre VARCHAR(100) NOT NULL,
    lot_finca_id INT NOT NULL,
    lot_area DECIMAL(10,2), -- en hectáreas
    lot_tipo_suelo VARCHAR(100),
    lot_ph_suelo DECIMAL(3,1),
    lot_descripcion TEXT,
    lot_estado ENUM('disponible', 'sembrado', 'cosechado', 'en_preparacion') DEFAULT 'disponible',
    lot_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lot_finca_id) REFERENCES fincas(fin_id)
);

-- Tabla de Siembras (Ciclos de Cultivo)
CREATE TABLE siembras (
    sie_id INT PRIMARY KEY AUTO_INCREMENT,
    sie_lote_id INT NOT NULL,
    sie_tipo_cultivo_id INT NOT NULL,
    sie_fecha_siembra DATE NOT NULL,
    sie_fecha_estimada_cosecha DATE,
    sie_cantidad_semilla DECIMAL(10,2),
    sie_unidad_semilla VARCHAR(20), -- kg, gramos, semillas, etc.
    sie_densidad_siembra VARCHAR(50),
    sie_metodo_siembra VARCHAR(100),
    sie_responsable_id INT,
    sie_estado ENUM('planificada', 'sembrada', 'en_crecimiento', 'cosechada', 'perdida') DEFAULT 'planificada',
    sie_observaciones TEXT,
    sie_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sie_lote_id) REFERENCES lotes(lot_id),
    FOREIGN KEY (sie_tipo_cultivo_id) REFERENCES tipos_cultivos(tip_id),
    FOREIGN KEY (sie_responsable_id) REFERENCES usuarios(usu_id)
);

-- Tabla de Monitoreo Manual
CREATE TABLE monitoreo (
    mon_id INT PRIMARY KEY AUTO_INCREMENT,
    mon_siembra_id INT NOT NULL,
    mon_fecha_observacion DATE NOT NULL,
    mon_altura_promedio DECIMAL(5,2), -- en cm
    mon_estado_general ENUM('excelente', 'bueno', 'regular', 'malo', 'critico') NOT NULL,
    mon_porcentaje_germinacion DECIMAL(5,2),
    mon_color_follaje VARCHAR(50),
    mon_presencia_plagas ENUM('ninguna', 'leve', 'moderada', 'severa') DEFAULT 'ninguna',
    mon_tipo_plagas TEXT,
    mon_presencia_enfermedades ENUM('ninguna', 'leve', 'moderada', 'severa') DEFAULT 'ninguna',
    mon_tipo_enfermedades TEXT,
    mon_condicion_clima VARCHAR(100),
    mon_humedad_suelo ENUM('seco', 'humedo', 'saturado') DEFAULT 'humedo',
    mon_observaciones TEXT,
    mon_responsable_id INT,
    mon_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mon_siembra_id) REFERENCES siembras(sie_id),
    FOREIGN KEY (mon_responsable_id) REFERENCES usuarios(usu_id)
);

-- Tabla de Actividades Agrícolas
CREATE TABLE actividades (
    act_id INT PRIMARY KEY AUTO_INCREMENT,
    act_siembra_id INT NOT NULL,
    act_tipo ENUM('riego', 'fertilizacion', 'fumigacion', 'poda', 'deshierbe', 'aporque', 'otro') NOT NULL,
    act_fecha DATE NOT NULL,
    act_descripcion TEXT,
    act_productos_utilizados TEXT,
    act_cantidad_producto DECIMAL(10,2),
    act_unidad_producto VARCHAR(20),
    act_costo DECIMAL(10,2),
    act_responsable_id INT,
    act_observaciones TEXT,
    act_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (act_siembra_id) REFERENCES siembras(sie_id),
    FOREIGN KEY (act_responsable_id) REFERENCES usuarios(usu_id)
);

-- Tabla de Cosechas
CREATE TABLE cosechas (
    cos_id INT PRIMARY KEY AUTO_INCREMENT,
    cos_siembra_id INT NOT NULL,
    cos_fecha_cosecha DATE NOT NULL,
    cos_cantidad_cosechada DECIMAL(10,2) NOT NULL,
    cos_unidad VARCHAR(20) NOT NULL, -- kg, toneladas, bultos, etc.
    cos_calidad ENUM('primera', 'segunda', 'tercera', 'descarte') DEFAULT 'primera',
    cos_precio_venta_unitario DECIMAL(10,2),
    cos_comprador VARCHAR(150),
    cos_total_ingresos DECIMAL(12,2),
    cos_responsable_id INT,
    cos_observaciones TEXT,
    cos_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cos_siembra_id) REFERENCES siembras(sie_id),
    FOREIGN KEY (cos_responsable_id) REFERENCES usuarios(usu_id)
);

-- Tabla de Gastos/Costos
CREATE TABLE gastos (
    gas_id INT PRIMARY KEY AUTO_INCREMENT,
    gas_siembra_id INT,
    gas_finca_id INT,
    gas_tipo ENUM('semillas', 'fertilizantes', 'pesticidas', 'mano_obra', 'maquinaria', 'otros') NOT NULL,
    gas_descripcion VARCHAR(200) NOT NULL,
    gas_fecha DATE NOT NULL,
    gas_monto DECIMAL(10,2) NOT NULL,
    gas_proveedor VARCHAR(150),
    gas_factura_numero VARCHAR(50),
    gas_responsable_id INT,
    gas_observaciones TEXT,
    gas_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (gas_siembra_id) REFERENCES siembras(sie_id),
    FOREIGN KEY (gas_finca_id) REFERENCES fincas(fin_id),
    FOREIGN KEY (gas_responsable_id) REFERENCES usuarios(usu_id)
);

-- Tabla de Alertas/Notificaciones
CREATE TABLE alertas (
    ale_id INT PRIMARY KEY AUTO_INCREMENT,
    ale_siembra_id INT,
    ale_tipo ENUM('riego', 'fertilizacion', 'fumigacion', 'cosecha', 'general') NOT NULL,
    ale_titulo VARCHAR(150) NOT NULL,
    ale_mensaje TEXT NOT NULL,
    ale_fecha_programada DATE,
    ale_prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    ale_estado ENUM('pendiente', 'vista', 'resuelta') DEFAULT 'pendiente',
    ale_usuario_id INT,
    ale_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ale_siembra_id) REFERENCES siembras(sie_id),
    FOREIGN KEY (ale_usuario_id) REFERENCES usuarios(usu_id)
);

-- Inserción de datos iniciales

-- Usuario administrador por defecto
INSERT INTO usuarios (usu_nombre, usu_apellido, usu_email, usu_password, usu_rol) 
VALUES ('Admin', 'Sistema', 'admin@sistema.com', MD5('admin123'), 'administrador');

-- Tipos de cultivos básicos
INSERT INTO tipos_cultivos (tip_nombre, tip_nombre_cientifico, tip_ciclo_dias, tip_descripcion) VALUES
('Maíz', 'Zea mays', 120, 'Cereal de alto rendimiento'),
('Tomate', 'Solanum lycopersicum', 90, 'Hortaliza de consumo directo'),
('Frijol', 'Phaseolus vulgaris', 75, 'Leguminosa rica en proteínas'),
('Papa', 'Solanum tuberosum', 100, 'Tubérculo de consumo masivo'),
('Arroz', 'Oryza sativa', 150, 'Cereal base de alimentación'),
('Cebolla', 'Allium cepa', 120, 'Hortaliza condimentaria'),
('Zanahoria', 'Daucus carota', 80, 'Hortaliza rica en vitamina A'),
('Lechuga', 'Lactuca sativa', 60, 'Hortaliza de hoja verde');

-- Índices para optimizar consultas
CREATE INDEX idx_siembras_lote ON siembras(sie_lote_id);
CREATE INDEX idx_siembras_fecha ON siembras(sie_fecha_siembra);
CREATE INDEX idx_monitoreo_siembra ON monitoreo(mon_siembra_id);
CREATE INDEX idx_monitoreo_fecha ON monitoreo(mon_fecha_observacion);
CREATE INDEX idx_actividades_siembra ON actividades(act_siembra_id);
CREATE INDEX idx_actividades_fecha ON actividades(act_fecha);
CREATE INDEX idx_cosechas_siembra ON cosechas(cos_siembra_id);
CREATE INDEX idx_gastos_siembra ON gastos(gas_siembra_id);
CREATE INDEX idx_alertas_usuario ON alertas(ale_usuario_id);
CREATE INDEX idx_usuarios_email ON usuarios(usu_email);