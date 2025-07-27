-- Script para actualizar la tabla tipos_cultivos con los campos completos del módulo
-- Sistema de Monitoreo de Cultivos - AgroMonitor
-- Ejecutar este script para agregar los campos faltantes

-- Verificar si la tabla existe y crear/actualizar según sea necesario
CREATE TABLE IF NOT EXISTS tipos_cultivos (
    tip_id INT AUTO_INCREMENT PRIMARY KEY,
    tip_nombre VARCHAR(100) NOT NULL,
    tip_nombre_cientifico VARCHAR(150) NULL,
    tip_ciclo_dias INT NULL,
    tip_descripcion TEXT NULL,
    tip_requerimientos_agua TEXT NULL,
    tip_requerimientos_suelo TEXT NULL,
    tip_temperatura_optima VARCHAR(50) NULL,
    tip_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar campos nuevos si no existen
-- Información botánica
ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_familia_botanica VARCHAR(100) NULL AFTER tip_nombre_cientifico;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_ciclo_vida ENUM('anual', 'perenne', 'bianual') DEFAULT 'anual' AFTER tip_familia_botanica;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_categoria ENUM('cereales', 'hortalizas', 'leguminosas', 'frutales', 'tuberculos', 'aromaticas') DEFAULT 'hortalizas' AFTER tip_ciclo_vida;

-- Requerimientos técnicos específicos
ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_temperatura_min DECIMAL(4,1) NULL AFTER tip_descripcion;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_temperatura_max DECIMAL(4,1) NULL AFTER tip_temperatura_min;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_precipitacion VARCHAR(100) NULL AFTER tip_temperatura_max;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_tipo_suelo VARCHAR(100) NULL AFTER tip_precipitacion;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_ph_min DECIMAL(3,1) NULL AFTER tip_tipo_suelo;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_ph_max DECIMAL(3,1) NULL AFTER tip_ph_min;

-- Información de siembra
ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_densidad_siembra VARCHAR(50) NULL AFTER tip_ph_max;

ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_profundidad_siembra VARCHAR(50) NULL AFTER tip_densidad_siembra;

-- Control de estado
ALTER TABLE tipos_cultivos 
ADD COLUMN IF NOT EXISTS tip_estado ENUM('activo', 'inactivo') DEFAULT 'activo' AFTER tip_temperatura_optima;

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_tipos_cultivos_nombre ON tipos_cultivos(tip_nombre);
CREATE INDEX IF NOT EXISTS idx_tipos_cultivos_categoria ON tipos_cultivos(tip_categoria);
CREATE INDEX IF NOT EXISTS idx_tipos_cultivos_estado ON tipos_cultivos(tip_estado);
CREATE INDEX IF NOT EXISTS idx_tipos_cultivos_ciclo_vida ON tipos_cultivos(tip_ciclo_vida);

-- Agregar restricción única para el nombre del cultivo
-- ALTER TABLE tipos_cultivos ADD CONSTRAINT uk_tipos_cultivos_nombre UNIQUE (tip_nombre);

-- Insertar datos de ejemplo para el catálogo básico
INSERT IGNORE INTO tipos_cultivos (
    tip_nombre, tip_nombre_cientifico, tip_familia_botanica, tip_categoria, 
    tip_ciclo_vida, tip_ciclo_dias, tip_descripcion, tip_temperatura_min, 
    tip_temperatura_max, tip_ph_min, tip_ph_max, tip_tipo_suelo,
    tip_densidad_siembra, tip_profundidad_siembra, tip_precipitacion
) VALUES 
-- CEREALES
('Maíz', 'Zea mays', 'Poaceae', 'cereales', 'anual', 120, 
 'Cereal de alto valor nutricional y múltiples usos industriales. Requiere suelos bien drenados y abundante agua durante la floración.',
 18, 30, 6.0, 7.5, 'Franco, bien drenado', '60,000-80,000 plantas/ha', '3-5 cm', '500-800 mm'),

('Arroz', 'Oryza sativa', 'Poaceae', 'cereales', 'anual', 150,
 'Cereal básico en la alimentación mundial. Cultivo adaptado a suelos inundados con alta demanda de agua.',
 20, 35, 5.5, 7.0, 'Arcilloso, inundable', '250-300 kg/ha', '2-3 cm', '1200-1500 mm'),

('Trigo', 'Triticum aestivum', 'Poaceae', 'cereales', 'anual', 180,
 'Cereal de clima templado, base para la producción de harina y productos de panadería.',
 10, 25, 6.0, 7.5, 'Franco arcilloso', '120-180 kg/ha', '2-4 cm', '300-500 mm'),

-- HORTALIZAS
('Tomate', 'Solanum lycopersicum', 'Solanaceae', 'hortalizas', 'anual', 120,
 'Hortaliza de fruto rico en licopeno y vitamina C. Requiere tutoreo y manejo intensivo.',
 18, 28, 6.0, 7.0, 'Franco, bien drenado', '25,000-40,000 plantas/ha', '1-2 cm', '400-600 mm'),

('Lechuga', 'Lactuca sativa', 'Asteraceae', 'hortalizas', 'anual', 70,
 'Hortaliza de hoja para ensaladas. Cultivo de ciclo corto y alta rotación.',
 15, 20, 6.0, 7.0, 'Franco, rico en materia orgánica', '300,000-500,000 plantas/ha', '0.5 cm', '200-300 mm'),

('Zanahoria', 'Daucus carota', 'Apiaceae', 'hortalizas', 'anual', 90,
 'Raíz comestible rica en carotenos. Requiere suelos profundos y bien trabajados.',
 16, 25, 6.0, 7.5, 'Franco arenoso, profundo', '3-4 kg/ha', '1-2 cm', '350-500 mm'),

('Cebolla', 'Allium cepa', 'Amaryllidaceae', 'hortalizas', 'anual', 150,
 'Bulbo comestible de uso culinario universal. Requiere días largos para bulbificación.',
 13, 24, 6.0, 7.5, 'Franco, bien drenado', '800,000-1,200,000 plantas/ha', '1-2 cm', '350-550 mm'),

-- LEGUMINOSAS
('Frijol', 'Phaseolus vulgaris', 'Fabaceae', 'leguminosas', 'anual', 90,
 'Leguminosa rica en proteínas que fija nitrógeno atmosférico. Importante en rotaciones.',
 18, 28, 6.0, 7.5, 'Franco, bien drenado', '200,000-400,000 plantas/ha', '3-5 cm', '300-500 mm'),

('Soya', 'Glycine max', 'Fabaceae', 'leguminosas', 'anual', 120,
 'Oleaginosa de alto contenido proteico. Excelente para rotación y fijación de nitrógeno.',
 20, 30, 6.0, 7.0, 'Franco arcilloso', '300,000-500,000 plantas/ha', '2-4 cm', '450-700 mm'),

('Garbanzo', 'Cicer arietinum', 'Fabaceae', 'leguminosas', 'anual', 120,
 'Leguminosa adaptada a climas secos. Alta tolerancia a sequía una vez establecida.',
 15, 25, 6.5, 7.5, 'Franco, bien drenado', '250,000-350,000 plantas/ha', '3-4 cm', '300-400 mm'),

-- FRUTALES
('Aguacate', 'Persea americana', 'Lauraceae', 'frutales', 'perenne', 365,
 'Árbol frutal de clima tropical y subtropical. Fruto rico en grasas saludables.',
 18, 28, 6.0, 7.0, 'Franco, profundo, bien drenado', '200-400 árboles/ha', 'Trasplante', '800-1200 mm'),

('Mango', 'Mangifera indica', 'Anacardiaceae', 'frutales', 'perenne', 365,
 'Árbol frutal tropical de gran longevidad. Fruto de alta demanda comercial.',
 24, 30, 6.0, 7.5, 'Franco arcilloso, profundo', '100-200 árboles/ha', 'Trasplante', '600-1500 mm'),

('Naranja', 'Citrus sinensis', 'Rutaceae', 'frutales', 'perenne', 365,
 'Cítrico de amplio consumo. Requiere clima subtropical y riego complementario.',
 13, 30, 6.0, 7.5, 'Franco, bien drenado', '200-400 árboles/ha', 'Trasplante', '600-1000 mm'),

-- TUBÉRCULOS
('Papa', 'Solanum tuberosum', 'Solanaceae', 'tuberculos', 'anual', 120,
 'Tubérculo de alto contenido energético. Cultivo de clima fresco y alta demanda hídrica.',
 15, 20, 5.5, 6.5, 'Franco arenoso, suelto', '40,000-60,000 plantas/ha', '8-10 cm', '500-700 mm'),

('Yuca', 'Manihot esculenta', 'Euphorbiaceae', 'tuberculos', 'anual', 300,
 'Raíz tuberosa tropical resistente a sequía. Base alimentaria en regiones tropicales.',
 25, 35, 5.5, 7.0, 'Franco arenoso, bien drenado', '10,000-15,000 plantas/ha', 'Estaca 15-20 cm', '600-1000 mm'),

('Ñame', 'Dioscorea alata', 'Dioscoreaceae', 'tuberculos', 'anual', 240,
 'Tubérculo tropical de alto valor nutricional. Requiere tutoreo para guía.',
 25, 30, 6.0, 7.0, 'Franco, profundo, bien drenado', '20,000-25,000 plantas/ha', 'Tubérculo semilla', '1000-1500 mm'),

-- AROMÁTICAS
('Cilantro', 'Coriandrum sativum', 'Apiaceae', 'aromaticas', 'anual', 60,
 'Hierba aromática de amplio uso culinario. Cultivo de ciclo muy corto.',
 15, 25, 6.0, 7.5, 'Franco, rico en materia orgánica', '15-20 kg/ha', '1-2 cm', '300-400 mm'),

('Albahaca', 'Ocimum basilicum', 'Lamiaceae', 'aromaticas', 'anual', 90,
 'Hierba aromática medicinal y culinaria. Sensible a heladas y exceso de humedad.',
 20, 30, 6.0, 7.5, 'Franco, bien drenado', '100,000-150,000 plantas/ha', '0.5 cm', '400-600 mm'),

('Romero', 'Rosmarinus officinalis', 'Lamiaceae', 'aromaticas', 'perenne', 365,
 'Arbusto aromático medicinal de bajo mantenimiento. Muy resistente a sequía.',
 10, 30, 6.0, 8.0, 'Franco arenoso, bien drenado', '5,000-10,000 plantas/ha', 'Trasplante', '200-400 mm');

-- Verificar la estructura final de la tabla
DESCRIBE tipos_cultivos;

-- Mostrar algunos registros de ejemplo
SELECT 
    tip_nombre,
    tip_categoria,
    tip_ciclo_vida,
    tip_ciclo_dias,
    tip_estado
FROM tipos_cultivos 
ORDER BY tip_categoria, tip_nombre 
LIMIT 10;