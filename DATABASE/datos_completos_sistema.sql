-- Script completo para poblar las tablas del sistema AgroMonitor
-- Basado en los datos existentes de usuarios, fincas, lotes y siembras

-- =============================================
-- INSERTAR MÁS SIEMBRAS
-- =============================================
INSERT INTO siembras (sie_lote_id, sie_tipo_cultivo_id, sie_fecha_siembra, sie_fecha_estimada_cosecha, sie_cantidad_semilla, sie_unidad_semilla, sie_densidad_siembra, sie_metodo_siembra, sie_responsable_id, sie_estado, sie_observaciones) VALUES
(1, 2, '2025-07-20', '2025-10-18', 5.00, 'kg', '25000', 'trasplante', 2, 'en_crecimiento', 'Tomates para mercado local'),
(2, 9, '2025-07-15', '2025-11-12', 25.00, 'kg', '70000', 'directa', 2, 'en_crecimiento', 'Maíz para consumo'),
(1, 4, '2025-07-10', '2025-10-08', 2000.00, 'kg', '45000', 'directa', 2, 'en_crecimiento', 'Papas variedad superchola'),
(3, 6, '2025-07-25', '2025-11-22', 3.00, 'kg', '400000', 'directa', 2, 'planificada', 'Cebollas blancas'),
(2, 7, '2025-07-28', '2025-10-16', 2.50, 'kg', '2500000', 'directa', 2, 'planificada', 'Zanahorias para exportación');

-- =============================================
-- INSERTAR ACTIVIDADES AGRÍCOLAS
-- =============================================
INSERT INTO actividades (act_siembra_id, act_tipo, act_fecha, act_descripcion, act_productos_utilizados, act_cantidad_producto, act_unidad_producto, act_costo, act_responsable_id, act_observaciones) VALUES
-- Actividades para lechuga (siembra ID: 1)
(1, 'riego', '2025-07-29', 'Riego por aspersión matutino', 'Agua', 500.00, 'litros', 15.00, 2, 'Riego uniforme aplicado'),
(1, 'fertilizacion', '2025-07-27', 'Aplicación de fertilizante foliar', 'Fertilizante 10-10-10', 5.00, 'kg', 25.00, 2, 'Aplicado en horas frescas'),

-- Actividades para tomate (siembra ID: 2)
(2, 'riego', '2025-07-28', 'Riego por goteo', 'Agua', 800.00, 'litros', 20.00, 2, 'Sistema de goteo funcionando correctamente'),
(2, 'fertilizacion', '2025-07-25', 'Fertilización base', 'Compost orgánico', 50.00, 'kg', 75.00, 2, 'Incorporado al suelo'),
(2, 'fumigacion', '2025-07-26', 'Control preventivo plagas', 'Bacillus thuringiensis', 2.00, 'litros', 40.00, 2, 'Aplicación preventiva'),

-- Actividades para maíz (siembra ID: 3)
(3, 'riego', '2025-07-27', 'Riego por surcos', 'Agua', 1200.00, 'litros', 30.00, 2, 'Riego profundo aplicado'),
(3, 'fertilizacion', '2025-07-22', 'Primera fertilización', 'Urea', 30.00, 'kg', 45.00, 2, 'Aplicado al voleo'),
(3, 'deshierbe', '2025-07-24', 'Control de malezas', 'Mano de obra', 8.00, 'horas', 80.00, 2, 'Deshierbe manual'),

-- Actividades para papa (siembra ID: 4)
(4, 'aporque', '2025-07-20', 'Primer aporque', 'Mano de obra', 6.00, 'horas', 60.00, 2, 'Aporque realizado correctamente'),
(4, 'riego', '2025-07-26', 'Riego por aspersión', 'Agua', 600.00, 'litros', 18.00, 2, 'Riego nocturno'),
(4, 'fumigacion', '2025-07-23', 'Control gorgojo', 'Insecticida sistémico', 1.50, 'litros', 35.00, 2, 'Aplicación preventiva gorgojo'),

-- Actividades para cebolla (siembra ID: 5)
(5, 'riego', '2025-07-29', 'Riego ligero', 'Agua', 300.00, 'litros', 12.00, 2, 'Riego post-siembra'),

-- Actividades para zanahoria (siembra ID: 6)
(6, 'riego', '2025-07-29', 'Riego por micro aspersión', 'Agua', 400.00, 'litros', 15.00, 2, 'Riego para germinación'),
(6, 'fertilizacion', '2025-07-28', 'Fertilización pre-siembra', 'Fertilizante complejo', 8.00, 'kg', 32.00, 2, 'Incorporado antes de siembra'),

-- Otras actividades de mantenimiento
(1, 'otro', '2025-07-28', 'Instalación de malla sombra', 'Malla sombra 50%', 100.00, 'metros', 150.00, 2, 'Protección contra sol intenso');

-- =============================================
-- INSERTAR MONITOREOS
-- =============================================
INSERT INTO monitoreo (mon_siembra_id, mon_fecha_observacion, mon_altura_promedio, mon_estado_general, mon_porcentaje_germinacion, mon_color_follaje, mon_presencia_plagas, mon_tipo_plagas, mon_presencia_enfermedades, mon_tipo_enfermedades, mon_condicion_clima, mon_humedad_suelo, mon_observaciones, mon_responsable_id) VALUES
-- Monitoreos para lechuga
(1, '2025-07-29', 8.50, 'bueno', 85.00, 'verde claro', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 24°C', 'humedo', 'Crecimiento normal, requiere riego frecuente', 2),
(1, '2025-07-27', 6.20, 'bueno', 80.00, 'verde', 'leve', 'pulgones', 'ninguna', NULL, 'nublado, 22°C', 'humedo', 'Presencia leve de pulgones, aplicar control', 2),

-- Monitoreos para tomate
(2, '2025-07-28', 25.30, 'excelente', 90.00, 'verde intenso', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 26°C', 'humedo', 'Excelente desarrollo, plantas vigorosas', 2),
(2, '2025-07-25', 18.50, 'bueno', 88.00, 'verde', 'ninguna', NULL, 'leve', 'marchitez leve', 'caluroso, 28°C', 'seco', 'Requiere más agua, síntomas de estrés hídrico', 2),

-- Monitoreos para maíz
(3, '2025-07-27', 45.00, 'excelente', 95.00, 'verde intenso', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 25°C', 'humedo', 'Excelente germinación y desarrollo', 2),
(3, '2025-07-24', 30.20, 'bueno', 92.00, 'verde', 'leve', 'cogollero', 'ninguna', NULL, 'variable, 24°C', 'humedo', 'Presencia leve de cogollero, monitorear', 2),

-- Monitoreos para papa
(4, '2025-07-26', 15.80, 'bueno', 88.00, 'verde', 'ninguna', NULL, 'ninguna', NULL, 'fresco, 18°C', 'humedo', 'Buen desarrollo, ideal para papa', 2),
(4, '2025-07-22', 8.90, 'regular', 85.00, 'verde pálido', 'moderada', 'pulguilla', 'leve', 'tizón leve', 'húmedo, 16°C', 'saturado', 'Presencia de plagas y enfermedades, requiere tratamiento', 2),

-- Monitoreos para cebolla
(5, '2025-07-29', 3.20, 'bueno', 70.00, 'verde', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 23°C', 'humedo', 'Germinación en proceso, normal para cebolla', 2),

-- Monitoreos para zanahoria
(6, '2025-07-29', 2.50, 'regular', 65.00, 'verde tenue', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 24°C', 'humedo', 'Germinación lenta, normal para zanahoria', 2),

-- Monitoreos adicionales
(1, '2025-07-26', 5.10, 'regular', 78.00, 'verde', 'leve', 'caracoles', 'ninguna', NULL, 'lluvioso, 20°C', 'saturado', 'Exceso de humedad, presencia de caracoles', 3),
(2, '2025-07-23', 12.40, 'bueno', 85.00, 'verde', 'ninguna', NULL, 'ninguna', NULL, 'soleado, 27°C', 'humedo', 'Desarrollo satisfactorio del cultivo', 3);

-- =============================================
-- INSERTAR GASTOS
-- =============================================
INSERT INTO gastos (gas_siembra_id, gas_finca_id, gas_tipo, gas_descripcion, gas_fecha, gas_monto, gas_proveedor, gas_factura_numero, gas_responsable_id, gas_observaciones) VALUES
-- Gastos específicos por siembra
(1, 1, 'semillas', 'Semilla de lechuga variedad criolla', '2025-07-28', 45.00, 'Semillas del Campo', 'FC-001', 2, 'Semilla certificada'),
(1, 1, 'fertilizantes', 'Fertilizante foliar para lechuga', '2025-07-27', 25.00, 'AgroTech', 'AT-456', 2, 'Aplicación quincenal'),

(2, 1, 'semillas', 'Semilla de tomate hibrido', '2025-07-20', 120.00, 'Semillas Premium', 'SP-789', 2, 'Variedad resistente'),
(2, 1, 'pesticidas', 'Insecticida biológico', '2025-07-26', 40.00, 'BioControl SA', 'BC-234', 2, 'Control preventivo'),

(3, 2, 'semillas', 'Semilla de maíz amarillo', '2025-07-15', 75.00, 'Agrícola Andes', 'AA-567', 2, 'Variedad local adaptada'),
(3, 2, 'fertilizantes', 'Urea granulada', '2025-07-22', 45.00, 'Fertilizantes del Norte', 'FN-890', 2, 'Primera aplicación'),

(4, 1, 'semillas', 'Papa semilla superchola', '2025-07-10', 180.00, 'Tubérculos Andinos', 'TA-123', 2, 'Semilla certificada libre de virus'),
(4, 1, 'pesticidas', 'Fungicida para tizón', '2025-07-23', 35.00, 'Protección Agrícola', 'PA-345', 2, 'Prevención enfermedades'),

(5, 1, 'semillas', 'Semilla cebolla blanca', '2025-07-25', 30.00, 'Hortalizas Select', 'HS-678', 2, 'Variedad de día largo'),
(6, 2, 'semillas', 'Semilla zanahoria chantenay', '2025-07-28', 28.00, 'Semillas Orgánicas', 'SO-901', 2, 'Variedad para exportación'),

-- Gastos generales por finca
(NULL, 1, 'mano_obra', 'Jornales agrícolas julio', '2025-07-29', 480.00, 'Cooperativa La Unión', 'CLU-202', 2, '12 jornales a $40 c/u'),
(NULL, 2, 'mano_obra', 'Trabajo de preparación suelo', '2025-07-25', 320.00, 'Servicios Agrícolas', 'SA-789', 2, '8 jornales preparación'),
(NULL, 3, 'maquinaria', 'Alquiler tractor para arado', '2025-07-20', 250.00, 'Maquinaria Pesada', 'MP-456', 2, '5 horas de trabajo'),

(NULL, 1, 'otros', 'Sistema de riego por goteo', '2025-07-18', 450.00, 'Riegos Modernos', 'RM-123', 2, 'Instalación completa'),
(NULL, 2, 'fertilizantes', 'Compost orgánico', '2025-07-16', 120.00, 'Abonos Naturales', 'AN-789', 2, '2 toneladas'),
(NULL, 3, 'otros', 'Herramientas agrícolas varias', '2025-07-22', 85.00, 'Ferretería El Campo', 'FEC-456', 2, 'Palas, azadas, rastrillos');

-- =============================================
-- INSERTAR COSECHAS
-- =============================================
INSERT INTO cosechas (cos_siembra_id, cos_fecha_cosecha, cos_cantidad_cosechada, cos_unidad, cos_calidad, cos_precio_venta_unitario, cos_comprador, cos_total_ingresos, cos_responsable_id, cos_observaciones) VALUES
-- Cosechas de cultivos completados (simulando cultivos anteriores)
(1, '2025-09-27', 450.00, 'kg', 'primera', 2.50, 'Mercado Central', 1125.00, 2, 'Lechuga de excelente calidad'),
(1, '2025-09-28', 120.00, 'kg', 'segunda', 1.80, 'Procesadora Local', 216.00, 2, 'Producto para procesamiento'),

(2, '2025-10-18', 2800.00, 'kg', 'primera', 1.20, 'Distribuidora Regional', 3360.00, 2, 'Tomates calibre grande'),
(2, '2025-10-19', 600.00, 'kg', 'segunda', 0.80, 'Mercado Mayorista', 480.00, 2, 'Tomates medianos'),
(2, '2025-10-20', 200.00, 'kg', 'tercera', 0.40, 'Procesadora Jugos', 80.00, 2, 'Para elaboración pulpa'),

(3, '2025-11-12', 1800.00, 'kg', 'primera', 0.65, 'Molino San José', 1170.00, 2, 'Maíz seco óptimo'),
(3, '2025-11-13', 450.00, 'kg', 'segunda', 0.50, 'Avícola Regional', 225.00, 2, 'Para alimento animal'),

(4, '2025-10-08', 3200.00, 'kg', 'primera', 0.85, 'Supermercados Unidos', 2720.00, 2, 'Papa consumo directo'),
(4, '2025-10-09', 800.00, 'kg', 'segunda', 0.60, 'Restaurante Popular', 480.00, 2, 'Papa para cocción'),
(4, '2025-10-10', 300.00, 'kg', 'tercera', 0.30, 'Procesadora Chips', 90.00, 2, 'Para elaboración snacks'),

(5, '2025-11-22', 280.00, 'kg', 'primera', 1.50, 'Exportadora Andes', 420.00, 2, 'Cebolla calibre exportación'),
(5, '2025-11-23', 150.00, 'kg', 'segunda', 1.00, 'Mercado Local', 150.00, 2, 'Cebolla mercado interno'),

(6, '2025-10-16', 380.00, 'kg', 'primera', 1.80, 'Exportadora Premium', 684.00, 2, 'Zanahoria baby exportación'),
(6, '2025-10-17', 220.00, 'kg', 'segunda', 1.20, 'Supermercado Regional', 264.00, 2, 'Zanahoria consumo local'),

-- Cosechas adicionales
(1, '2025-09-25', 80.00, 'kg', 'descarte', 0.20, 'Abono Orgánico', 16.00, 2, 'Para compostaje');

-- =============================================
-- INSERTAR ALERTAS/NOTIFICACIONES
-- =============================================
INSERT INTO alertas (ale_siembra_id, ale_tipo, ale_titulo, ale_mensaje, ale_fecha_programada, ale_prioridad, ale_usuario_id, ale_estado) VALUES
-- Alertas para el administrador (ID: 1)
(NULL, 'general', 'Sistema Actualizado', 'El sistema AgroMonitor ha sido actualizado con nuevas funcionalidades.', CURDATE(), 'media', 1, 'pendiente'),
(NULL, 'general', 'Respaldo Completado', 'Se ha completado el respaldo automático de la base de datos.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'baja', 1, 'vista'),

-- Alertas para MICHAEL NOQUEZ (ID: 2, agricultor)
(1, 'riego', 'Riego Lechuga Urgente', 'La lechuga en el lote Conjunto requiere riego inmediato.', CURDATE(), 'alta', 2, 'pendiente'),
(2, 'fertilizacion', 'Fertilizar Tomates', 'Es momento de aplicar fertilizante foliar a los tomates.', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'media', 2, 'pendiente'),
(3, 'fumigacion', 'Control Cogollero Maíz', 'Se detectó presencia de cogollero en el maíz, aplicar control.', CURDATE(), 'alta', 2, 'pendiente'),
(4, 'general', 'Aporque Papa', 'Realizar segundo aporque en el cultivo de papa.', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'media', 2, 'pendiente'),
(5, 'riego', 'Riego Cebolla', 'Las cebollas necesitan riego ligero y frecuente.', CURDATE(), 'baja', 2, 'pendiente'),
(6, 'general', 'Monitoreo Zanahoria', 'Revisar germinación de zanahoria en el lote.', CURDATE(), 'baja', 2, 'pendiente'),
(2, 'cosecha', 'Preparar Cosecha Tomate', 'Los tomates estarán listos para cosechar en 3 semanas.', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'media', 2, 'pendiente'),

-- Alertas para Alejandro Cevallos (ID: 3, supervisor)
(NULL, 'general', 'Supervisión Semanal', 'Realizar supervisión semanal de todas las fincas.', CURDATE(), 'alta', 3, 'pendiente'),
(1, 'general', 'Inspección Lechuga', 'Revisar el estado de los pulgones en la lechuga.', CURDATE(), 'media', 3, 'pendiente'),
(4, 'general', 'Evaluar Tratamiento Papa', 'Verificar efectividad del tratamiento contra pulguilla.', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'media', 3, 'pendiente'),

-- Alertas generales para todos (ale_usuario_id = NULL)
(NULL, 'general', 'Mantenimiento Programado', 'Mantenimiento del sistema el domingo de 2:00 AM a 4:00 AM.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'alta', NULL, 'pendiente'),
(NULL, 'general', 'Alerta Climática', 'Se pronostica lluvia intensa para los próximos 3 días.', CURDATE(), 'media', NULL, 'pendiente'),
(NULL, 'general', 'Nuevo Módulo Disponible', 'El módulo de análisis financiero ya está disponible.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'baja', NULL, 'vista'),

-- Alertas ya resueltas para mostrar variedad
(1, 'riego', 'Riego Matutino Completado', 'Se completó el riego matutino de la lechuga.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'baja', 2, 'resuelta'),
(2, 'fumigacion', 'Control Preventivo Aplicado', 'Se aplicó control preventivo en tomates exitosamente.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'media', 2, 'resuelta');