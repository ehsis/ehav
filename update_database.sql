-- Actualización de base de datos para Sistema de Gestión de Proyectos
-- Ejecutar este script ANTES de usar los archivos PHP actualizados

-- 1. Agregar nuevas columnas a la tabla tareas
ALTER TABLE tareas 
ADD COLUMN contrato VARCHAR(50) DEFAULT 'Normal' AFTER proyecto_id,
ADD COLUMN peso_actividad DECIMAL(10, 8) DEFAULT 0.0000 AFTER contrato,
ADD COLUMN fase_principal VARCHAR(255) DEFAULT NULL AFTER peso_actividad;

-- 2. Agregar columna de progreso ponderado a proyectos
ALTER TABLE proyectos 
ADD COLUMN progreso_ponderado DECIMAL(5, 2) DEFAULT 0.00 AFTER estado;

-- 3. Crear índices para mejorar rendimiento
CREATE INDEX idx_tareas_tipo ON tareas(tipo);
CREATE INDEX idx_tareas_contrato ON tareas(contrato);
CREATE INDEX idx_tareas_fase ON tareas(fase_principal);
CREATE INDEX idx_tareas_peso ON tareas(peso_actividad);

-- 4. Crear vista para cálculos ponderados
DROP VIEW IF EXISTS vista_progreso_ponderado;
CREATE VIEW vista_progreso_ponderado AS
SELECT 
    proyecto_id,
    tipo,
    fase_principal,
    SUM(peso_actividad) as peso_total,
    SUM(CASE 
        WHEN estado = 'Listo' THEN peso_actividad 
        WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
        ELSE 0 
    END) as avance_ponderado,
    CASE 
        WHEN SUM(peso_actividad) > 0 
        THEN (SUM(CASE 
            WHEN estado = 'Listo' THEN peso_actividad 
            WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
            ELSE 0 
        END) / SUM(peso_actividad)) * 100
        ELSE 0 
    END as progreso_porcentaje
FROM tareas 
WHERE peso_actividad > 0
GROUP BY proyecto_id, tipo, fase_principal;

-- 5. Datos de ejemplo basados en el Excel (Proyecto Cafeto)
-- Verificar si ya existe el proyecto Cafeto
INSERT IGNORE INTO proyectos (id, nombre, descripcion, cliente, estado, fecha_inicio, progreso_ponderado, created_at) 
VALUES (1, 'Cafeto', 'Proyecto de construcción residencial basado en Excel', 'Cliente Cafeto', 'Activo', '2025-08-01', 84.44, NOW());

-- 6. Limpiar tareas existentes del proyecto Cafeto si existen
DELETE FROM tareas WHERE proyecto_id = 1;

-- 7. Insertar tareas de ejemplo basadas en el Excel del proyecto Cafeto
INSERT INTO tareas (nombre, tipo, proyecto_id, contrato, peso_actividad, fase_principal, duracion_dias, estado, porcentaje_avance, created_at) VALUES
-- Fase 1: Recepción de planos constructivos
('Recepción de Planos', 'Fase', 1, 'Normal', 0.0100, '1. Recepción de planos constructivos', 1, 'Pendiente', 0, NOW()),
('REVISIÓN DE PLANOS', 'Actividad', 1, 'Normal', 0.0066, '1. Recepción de planos constructivos', 1, 'Pendiente', 0, NOW()),
('INFRAESTRUCTURA', 'Tarea', 1, 'Normal', 0.0033, '1. Recepción de planos constructivos', 1, 'Listo', 100, NOW()),
('CASAS', 'Tarea', 1, 'Normal', 0.0033, '1. Recepción de planos constructivos', 1, 'Listo', 100, NOW()),
('CREACION RED LINES', 'Actividad', 1, 'Normal', 0.0017, '1. Recepción de planos constructivos', 1, 'Listo', 100, NOW()),
('ENVIAR RED LINES AL DISEÑADOR', 'Actividad', 1, 'Normal', 0.0017, '1. Recepción de planos constructivos', 1, 'Listo', 100, NOW()),

-- Fase 2: Cotizaciones (100% completada)
('COTIZACIONES', 'Fase', 1, 'Normal', 0.8400, '2. Cotizaciones', 47, 'Listo', 100, NOW()),
('COTIZAR SUBCONTRATOS INFRA Y AMENIDADES', 'Actividad', 1, 'Normal', 0.5460, '2. Cotizaciones', 31, 'Listo', 100, NOW()),
('MOVIMIENTOS DE TIERRA', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('MUROS DE TILO', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('TANQUE DE RETARDO PLUVIAL', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('PLANTA DE TRATAMIENTO', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('LASTRADOS', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('PAVIMENTOS', 'Tarea', 1, 'Contrato Clave', 0.0420, '2. Cotizaciones', 2, 'Listo', 100, NOW()),
('COTIZAR SUBCONTRATOS CASAS', 'Actividad', 1, 'Normal', 0.2940, '2. Cotizaciones', 16, 'Listo', 100, NOW()),

-- Fase 3: Presupuesto Infraestructura (Pendiente)
('PRESUPUESTO INFRAESTRUCTURA', 'Fase', 1, 'Normal', 0.0999, '3. Presupuesto Infraestructura', 6, 'Pendiente', 0, NOW()),
('COSTOS DIRECTOS CONSTRUCTIVOS', 'Actividad', 1, 'Normal', 0.0839, '3. Presupuesto Infraestructura', 5, 'Pendiente', 0, NOW()),
('COSTOS INDIRECTOS CONSTRUCTIVOS', 'Actividad', 1, 'Normal', 0.0080, '3. Presupuesto Infraestructura', 1, 'Pendiente', 0, NOW()),
('COSTOS INDIRECTOS ADMINISTRATIVOS', 'Actividad', 1, 'Normal', 0.0080, '3. Presupuesto Infraestructura', 1, 'Pendiente', 0, NOW()),

-- Fase 4: Presupuesto Casas (Pendiente)
('PRESUPUESTO CASAS', 'Fase', 1, 'Normal', 0.0500, '4. Presupuesto Casas', 3, 'Pendiente', 0, NOW()),
('PRESUPUESTO DETALLADO CASAS', 'Actividad', 1, 'Normal', 0.0095, '4. Presupuesto Casas', 1, 'Pendiente', 0, NOW()),
('DOCUMENTOS P&E', 'Actividad', 1, 'Normal', 0.0110, '4. Presupuesto Casas', 1, 'Pendiente', 0, NOW()),
('O4Bi', 'Actividad', 1, 'Normal', 0.0140, '4. Presupuesto Casas', 1, 'Pendiente', 0, NOW()),
('CRONOGRAMA DETALLADO', 'Actividad', 1, 'Normal', 0.0155, '4. Presupuesto Casas', 1, 'Pendiente', 0, NOW());

-- 8. Crear función almacenada para calcular progreso ponderado
DELIMITER //
DROP FUNCTION IF EXISTS calcular_progreso_ponderado//
CREATE FUNCTION calcular_progreso_ponderado(p_proyecto_id INT)
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_peso_total DECIMAL(10,8) DEFAULT 0;
    DECLARE v_avance_total DECIMAL(10,8) DEFAULT 0;
    DECLARE v_progreso DECIMAL(5,2) DEFAULT 0;
    
    SELECT 
        COALESCE(SUM(peso_actividad), 0),
        COALESCE(SUM(CASE 
            WHEN estado = 'Listo' THEN peso_actividad 
            WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
            ELSE 0 
        END), 0)
    INTO v_peso_total, v_avance_total
    FROM tareas 
    WHERE proyecto_id = p_proyecto_id;
    
    IF v_peso_total > 0 THEN
        SET v_progreso = (v_avance_total / v_peso_total) * 100;
    END IF;
    
    RETURN v_progreso;
END//
DELIMITER ;

-- 9. Trigger para actualizar automáticamente el progreso ponderado
DELIMITER //
DROP TRIGGER IF EXISTS actualizar_progreso_proyecto//
CREATE TRIGGER actualizar_progreso_proyecto
AFTER UPDATE ON tareas
FOR EACH ROW
BEGIN
    UPDATE proyectos 
    SET progreso_ponderado = calcular_progreso_ponderado(NEW.proyecto_id)
    WHERE id = NEW.proyecto_id;
END//

DROP TRIGGER IF EXISTS actualizar_progreso_proyecto_insert//
CREATE TRIGGER actualizar_progreso_proyecto_insert
AFTER INSERT ON tareas
FOR EACH ROW
BEGIN
    UPDATE proyectos 
    SET progreso_ponderado = calcular_progreso_ponderado(NEW.proyecto_id)
    WHERE id = NEW.proyecto_id;
END//

DROP TRIGGER IF EXISTS actualizar_progreso_proyecto_delete//
CREATE TRIGGER actualizar_progreso_proyecto_delete
AFTER DELETE ON tareas
FOR EACH ROW
BEGIN
    UPDATE proyectos 
    SET progreso_ponderado = calcular_progreso_ponderado(OLD.proyecto_id)
    WHERE id = OLD.proyecto_id;
END//
DELIMITER ;

-- 10. Verificar la instalación
SELECT 'Actualización completada exitosamente' as status;
SELECT COUNT(*) as total_tareas FROM tareas WHERE proyecto_id = 1;
SELECT progreso_ponderado FROM proyectos WHERE id = 1;
