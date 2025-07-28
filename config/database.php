<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'proyecto_cafeto';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

class ProyectoManager {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->verificarEstructuraTablas();
    }
    
    /**
     * NUEVO: Verificar y crear columna dias_totales si no existe
     */
    private function verificarEstructuraTablas() {
        try {
            // Verificar si existe la columna dias_totales
            $query = "SHOW COLUMNS FROM proyectos LIKE 'dias_totales'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Agregar columna dias_totales
                $alter_query = "ALTER TABLE proyectos ADD COLUMN dias_totales DECIMAL(8,2) DEFAULT 56.00 AFTER presupuesto";
                $this->conn->exec($alter_query);
                
                // Inicializar proyectos existentes
                $init_query = "UPDATE proyectos SET dias_totales = 56.00 WHERE dias_totales IS NULL";
                $this->conn->exec($init_query);
            }
        } catch (Exception $e) {
            error_log("Error verificando estructura: " . $e->getMessage());
        }
    }
    
    /**
     * NUEVO: Obtener días totales del proyecto
     */
    public function obtenerDiasTotalesProyecto($proyecto_id) {
        try {
            $query = "SELECT dias_totales FROM proyectos WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultado ? floatval($resultado['dias_totales']) : 56.0;
        } catch (Exception $e) {
            return 56.0; // Valor por defecto
        }
    }
    
    /**
     * NUEVO: Actualizar días totales del proyecto
     */
    public function actualizarDiasTotalesProyecto($proyecto_id, $dias_totales) {
        try {
            $query = "UPDATE proyectos SET dias_totales = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $resultado = $stmt->execute([floatval($dias_totales), $proyecto_id]);
            
            if ($resultado) {
                // Recalcular pesos basados en los nuevos días totales
                $this->recalcularPesosPorDias($proyecto_id);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error actualizando días totales: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * NUEVO: Calcular peso porcentual basado en días
     */
    public function calcularPesoPorcentual($proyecto_id, $duracion_dias) {
        $dias_totales = $this->obtenerDiasTotalesProyecto($proyecto_id);
        
        if ($dias_totales <= 0) {
            return 0.0;
        }
        
        $porcentaje = (floatval($duracion_dias) / $dias_totales) * 100;
        return round($porcentaje, 4); // 4 decimales para mayor precisión
    }
    
    /**
     * NUEVO: Recalcular todos los pesos basándose en días
     */
    public function recalcularPesosPorDias($proyecto_id) {
        try {
            $query = "SELECT id, duracion_dias FROM tareas WHERE proyecto_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tareas as $tarea) {
                $nuevo_peso = $this->calcularPesoPorcentual($proyecto_id, $tarea['duracion_dias']);
                
                $update_query = "UPDATE tareas SET peso_actividad = ? WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->execute([$nuevo_peso, $tarea['id']]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error recalculando pesos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * NUEVO: Obtener estadísticas detalladas con información de días
     */
    public function obtenerEstadisticasDetalladas($proyecto_id) {
        $query = "SELECT 
            COUNT(*) as total_tareas,
            SUM(duracion_dias) as total_dias_planificados,
            SUM(peso_actividad) as peso_total_actual,
            SUM(CASE WHEN estado = 'Listo' THEN duracion_dias ELSE 0 END) as dias_completados,
            SUM(CASE WHEN estado = 'Listo' THEN peso_actividad ELSE 0 END) as peso_completado,
            SUM(CASE WHEN estado = 'En Proceso' THEN duracion_dias ELSE 0 END) as dias_en_proceso,
            SUM(CASE WHEN estado = 'En Proceso' THEN peso_actividad ELSE 0 END) as peso_en_proceso,
            SUM(CASE WHEN estado = 'Pendiente' THEN duracion_dias ELSE 0 END) as dias_pendientes,
            SUM(CASE WHEN estado = 'Pendiente' THEN peso_actividad ELSE 0 END) as peso_pendiente,
            AVG(porcentaje_avance) as avance_promedio_simple,
            SUM(CASE 
                WHEN estado = 'Listo' THEN peso_actividad 
                WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
                ELSE 0 
            END) as avance_ponderado_total
            FROM tareas 
            WHERE proyecto_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $dias_totales_proyecto = $this->obtenerDiasTotalesProyecto($proyecto_id);
        
        return [
            'total_tareas' => intval($stats['total_tareas']),
            'dias_totales_proyecto' => $dias_totales_proyecto,
            'total_dias_planificados' => floatval($stats['total_dias_planificados']),
            'peso_total_actual' => floatval($stats['peso_total_actual']),
            'dias_completados' => floatval($stats['dias_completados']),
            'peso_completado' => floatval($stats['peso_completado']),
            'dias_en_proceso' => floatval($stats['dias_en_proceso']),
            'peso_en_proceso' => floatval($stats['peso_en_proceso']),
            'dias_pendientes' => floatval($stats['dias_pendientes']),
            'peso_pendiente' => floatval($stats['peso_pendiente']),
            'avance_promedio_simple' => floatval($stats['avance_promedio_simple']),
            'avance_ponderado_total' => floatval($stats['avance_ponderado_total']),
            'diferencia_dias' => $dias_totales_proyecto - floatval($stats['total_dias_planificados']),
            'diferencia_peso' => 100.0 - floatval($stats['peso_total_actual']),
            'porcentaje_dias_usados' => $dias_totales_proyecto > 0 ? (floatval($stats['total_dias_planificados']) / $dias_totales_proyecto) * 100 : 0,
            'consistencia_peso_ok' => abs(100.0 - floatval($stats['peso_total_actual'])) <= 1.0,
            'consistencia_dias_ok' => abs($dias_totales_proyecto - floatval($stats['total_dias_planificados'])) <= 0.5
        ];
    }
    
    /**
     * NUEVO: Distribuir días faltantes automáticamente
     */
    public function distribuirDiasFaltantes($proyecto_id, $solo_pendientes = true) {
        $stats = $this->obtenerEstadisticasDetalladas($proyecto_id);
        $diferencia_dias = $stats['diferencia_dias'];
        
        if (abs($diferencia_dias) < 0.01) {
            return ['success' => true, 'message' => 'Los días ya están correctamente distribuidos'];
        }
        
        try {
            // Obtener tareas que pueden ajustarse
            $condicion_estado = $solo_pendientes ? "AND estado IN ('Pendiente', 'En Proceso')" : "";
            $query = "SELECT id, duracion_dias, peso_actividad, estado FROM tareas 
                     WHERE proyecto_id = ? {$condicion_estado} ORDER BY peso_actividad DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
            $tareas_ajustables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tareas_ajustables)) {
                return ['success' => false, 'message' => 'No hay tareas ajustables disponibles'];
            }
            
            // Distribuir la diferencia proporcionalmente
            $dias_por_tarea = $diferencia_dias / count($tareas_ajustables);
            
            foreach ($tareas_ajustables as $tarea) {
                $nuevos_dias = max(0.01, floatval($tarea['duracion_dias']) + $dias_por_tarea);
                $nuevo_peso = $this->calcularPesoPorcentual($proyecto_id, $nuevos_dias);
                
                $update_query = "UPDATE tareas SET duracion_dias = ?, peso_actividad = ? WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->execute([$nuevos_dias, $nuevo_peso, $tarea['id']]);
            }
            
            return [
                'success' => true, 
                'message' => sprintf("Distribuidos %.2f días entre %d tareas", abs($diferencia_dias), count($tareas_ajustables)),
                'tareas_afectadas' => count($tareas_ajustables)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * NUEVO: Corregir pesos automáticamente para que sumen 100%
     */
    public function corregirPesosAutomaticamente($proyecto_id) {
        try {
            $stats = $this->obtenerEstadisticasDetalladas($proyecto_id);
            $peso_actual = $stats['peso_total_actual'];
            
            if (abs(100.0 - $peso_actual) < 0.1) {
                return ['success' => true, 'message' => 'Los pesos ya están correctos'];
            }
            
            // Obtener todas las tareas
            $query = "SELECT id, peso_actividad FROM tareas WHERE proyecto_id = ? AND peso_actividad > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
            $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tareas)) {
                return ['success' => false, 'message' => 'No hay tareas con peso para ajustar'];
            }
            
            // Calcular factor de corrección
            $factor_correccion = 100.0 / $peso_actual;
            
            foreach ($tareas as $tarea) {
                $nuevo_peso = floatval($tarea['peso_actividad']) * $factor_correccion;
                
                $update_query = "UPDATE tareas SET peso_actividad = ? WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->execute([round($nuevo_peso, 4), $tarea['id']]);
            }
            
            return [
                'success' => true, 
                'message' => sprintf("Pesos corregidos de %.2f%% a 100.00%% (factor: %.4f)", $peso_actual, $factor_correccion),
                'factor_correccion' => $factor_correccion
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * NUEVO: Validar consistencia del proyecto
     */
    public function validarConsistenciaProyecto($proyecto_id) {
        $stats = $this->obtenerEstadisticasDetalladas($proyecto_id);
        $alertas = [];
        
        // Verificar peso total
        if (!$stats['consistencia_peso_ok']) {
            $nivel = abs($stats['diferencia_peso']) > 5 ? 'danger' : 'warning';
            $alertas[] = [
                'tipo' => 'peso_total',
                'nivel' => $nivel,
                'mensaje' => sprintf("Peso total: %.2f%% (diferencia: %+.2f%%)", 
                                   $stats['peso_total_actual'], $stats['diferencia_peso']),
                'valor_actual' => $stats['peso_total_actual'],
                'valor_esperado' => 100.0
            ];
        }
        
        // Verificar días totales
        if (!$stats['consistencia_dias_ok']) {
            $alertas[] = [
                'tipo' => 'dias_totales',
                'nivel' => 'info',
                'mensaje' => sprintf("Días planificados: %.2f de %.2f días totales (%.1f%% utilizado)", 
                                   $stats['total_dias_planificados'], 
                                   $stats['dias_totales_proyecto'],
                                   $stats['porcentaje_dias_usados']),
                'valor_actual' => $stats['total_dias_planificados'],
                'valor_esperado' => $stats['dias_totales_proyecto']
            ];
        }
        
        // Verificar tareas sin peso
        $query = "SELECT COUNT(*) as tareas_sin_peso FROM tareas WHERE proyecto_id = ? AND peso_actividad = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $sin_peso = $stmt->fetch(PDO::FETCH_ASSOC)['tareas_sin_peso'];
        
        if ($sin_peso > 0) {
            $alertas[] = [
                'tipo' => 'tareas_sin_peso',
                'nivel' => 'warning',
                'mensaje' => sprintf("%d tareas sin peso asignado", $sin_peso),
                'cantidad' => $sin_peso
            ];
        }
        
        // Verificar tareas con peso excesivo
        $query = "SELECT COUNT(*) as tareas_peso_alto FROM tareas WHERE proyecto_id = ? AND peso_actividad > 20";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $peso_alto = $stmt->fetch(PDO::FETCH_ASSOC)['tareas_peso_alto'];
        
        if ($peso_alto > 0) {
            $alertas[] = [
                'tipo' => 'peso_excesivo',
                'nivel' => 'info',
                'mensaje' => sprintf("%d tareas con peso mayor al 20%%", $peso_alto),
                'cantidad' => $peso_alto
            ];
        }
        
        return [
            'consistente' => count($alertas) == 0,
            'alertas' => $alertas,
            'stats' => $stats,
            'recomendaciones' => $this->generarRecomendaciones($stats, $alertas)
        ];
    }
    
    /**
     * NUEVO: Generar recomendaciones basadas en el análisis
     */
    private function generarRecomendaciones($stats, $alertas) {
        $recomendaciones = [];
        
        foreach ($alertas as $alerta) {
            switch ($alerta['tipo']) {
                case 'peso_total':
                    if ($stats['peso_total_actual'] > 100) {
                        $recomendaciones[] = "Considera reducir la duración de algunas tareas o redistribuir el peso entre fases.";
                    } else {
                        $recomendaciones[] = "Puedes añadir más tareas o aumentar la duración de las existentes.";
                    }
                    break;
                    
                case 'dias_totales':
                    if ($stats['diferencia_dias'] > 0) {
                        $recomendaciones[] = sprintf("Tienes %.2f días disponibles para asignar a nuevas tareas.", $stats['diferencia_dias']);
                    } else {
                        $recomendaciones[] = "Considera aumentar los días totales del proyecto o reducir la duración de algunas tareas.";
                    }
                    break;
                    
                case 'tareas_sin_peso':
                    $recomendaciones[] = "Asigna duración en días a las tareas sin peso para incluirlas en el cálculo del progreso.";
                    break;
            }
        }
        
        if (empty($recomendaciones)) {
            $recomendaciones[] = "¡Excelente! Tu proyecto está bien balanceado en términos de días y pesos.";
        }
        
        return $recomendaciones;
    }
    
    // ===== MÉTODOS EXISTENTES (sin cambios) =====
    
    public function obtenerProyectos() {
        $query = "SELECT *, 
                  calcular_progreso_ponderado(id) as progreso_calculado
                  FROM proyectos ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerProyecto($id) {
        $query = "SELECT *, 
                  calcular_progreso_ponderado(id) as progreso_calculado
                  FROM proyectos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crearProyecto($datos) {
        $query = "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin_estimada, cliente, presupuesto, dias_totales, estado, progreso_ponderado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin_estimada'] ?? null,
            $datos['cliente'] ?? null,
            $datos['presupuesto'] ?? 0.00,
            $datos['dias_totales'] ?? 56.00, // NUEVO
            $datos['estado'] ?? 'Activo',
            0.00
        ]);
    }
    
    public function actualizarProyecto($datos) {
        $query = "UPDATE proyectos SET 
                  nombre = ?, 
                  descripcion = ?, 
                  fecha_inicio = ?, 
                  fecha_fin_estimada = ?, 
                  cliente = ?, 
                  presupuesto = ?, 
                  dias_totales = ?, 
                  estado = ?,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin_estimada'] ?? null,
            $datos['cliente'] ?? null,
            $datos['presupuesto'] ?? 0.00,
            $datos['dias_totales'] ?? 56.00, // NUEVO
            $datos['estado'] ?? 'Activo',
            $datos['id']
        ]);
    }
    
    public function eliminarProyecto($id) {
        // Primero eliminar todas las tareas del proyecto
        $query_tareas = "DELETE FROM tareas WHERE proyecto_id = ?";
        $stmt_tareas = $this->conn->prepare($query_tareas);
        $stmt_tareas->execute([$id]);
        
        // Luego eliminar el proyecto
        $query = "DELETE FROM proyectos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Obtener estadísticas ponderadas basadas en PORCENTAJES (0-100%)
     */
    public function obtenerEstadisticasProyecto($proyecto_id) {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(peso_actividad) as peso_total_porcentaje,
            COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
            COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
            COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
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
                ELSE AVG(porcentaje_avance)
            END as avance_promedio
            FROM tareas 
            WHERE proyecto_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que todos los valores sean numéricos
        return [
            'total' => intval($result['total']),
            'completadas' => intval($result['completadas']),
            'en_proceso' => intval($result['en_proceso']),
            'pendientes' => intval($result['pendientes']),
            'peso_total' => floatval($result['peso_total_porcentaje']), // Ahora en porcentajes
            'avance_ponderado' => floatval($result['avance_ponderado']),
            'avance_promedio' => floatval($result['avance_promedio'])
        ];
    }
    
    /**
     * Obtener estadísticas por tipo con peso ponderado EN PORCENTAJES
     */
    public function obtenerEstadisticasPorTipo($proyecto_id) {
        $query = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    SUM(peso_actividad) as peso_total,
                    COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
                    COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
                    COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
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
                        ELSE AVG(porcentaje_avance)
                    END as avance_promedio
                  FROM tareas 
                  WHERE proyecto_id = ?
                  GROUP BY tipo
                  ORDER BY FIELD(tipo, 'Fase', 'Actividad', 'Tarea')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener estadísticas por fase principal EN PORCENTAJES
     */
    public function obtenerEstadisticasPorFase($proyecto_id) {
        $query = "SELECT 
                    fase_principal,
                    COUNT(*) as total,
                    SUM(peso_actividad) as peso_total,
                    COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
                    COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
                    COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
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
                        ELSE AVG(porcentaje_avance)
                    END as avance_promedio
                  FROM tareas 
                  WHERE proyecto_id = ? AND fase_principal IS NOT NULL
                  GROUP BY fase_principal
                  ORDER BY fase_principal";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerTareasProyecto($proyecto_id) {
        $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY fase_principal, tipo, id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function duplicarTareasPlantilla($proyecto_origen_id, $proyecto_destino_id) {
        $query = "INSERT INTO tareas (nombre, tipo, duracion_dias, estado, porcentaje_avance, proyecto_id, contrato, peso_actividad, fase_principal)
                  SELECT nombre, tipo, duracion_dias, 'Pendiente', 0.00, ?, contrato, peso_actividad, fase_principal
                  FROM tareas 
                  WHERE proyecto_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$proyecto_destino_id, $proyecto_origen_id]);
    }
    
    public function obtenerTareasPorTipo($proyecto_id, $tipo = null) {
        if ($tipo) {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND tipo = ? ORDER BY fase_principal, id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id, $tipo]);
        } else {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY fase_principal, tipo, id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear tarea con peso EN PORCENTAJES (0-100%) - MEJORADO
     */
    public function crearTarea($datos) {
        // Si no se especifica peso, calcularlo basado en días
        if (!isset($datos['peso_actividad']) || $datos['peso_actividad'] == 0) {
            $datos['peso_actividad'] = $this->calcularPesoPorcentual(
                $datos['proyecto_id'], 
                $datos['duracion_dias'] ?? 1
            );
        }
        
        $query = "INSERT INTO tareas (nombre, tipo, duracion_dias, estado, porcentaje_avance, proyecto_id, contrato, peso_actividad, fase_principal) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['tipo'],
            $datos['duracion_dias'] ?? 1,
            $datos['estado'] ?? 'Pendiente',
            $datos['porcentaje_avance'] ?? 0,
            $datos['proyecto_id'],
            $datos['contrato'] ?? 'Normal',
            $datos['peso_actividad'],
            $datos['fase_principal'] ?? null
        ]);
    }
    
    /**
     * Actualizar tarea con peso EN PORCENTAJES - MEJORADO
     */
    public function actualizarTarea($datos) {
        // Recalcular peso si cambiaron los días
        if (isset($datos['duracion_dias'])) {
            $datos['peso_actividad'] = $this->calcularPesoPorcentual(
                $datos['proyecto_id'] ?? $this->obtenerProyectoIdDeTarea($datos['id']), 
                $datos['duracion_dias']
            );
        }
        
        $query = "UPDATE tareas SET 
                  nombre = ?, 
                  tipo = ?, 
                  duracion_dias = ?, 
                  estado = ?, 
                  porcentaje_avance = ?,
                  contrato = ?,
                  peso_actividad = ?,
                  fase_principal = ?,
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['tipo'],
            $datos['duracion_dias'],
            $datos['estado'],
            $datos['porcentaje_avance'],
            $datos['contrato'] ?? 'Normal',
            $datos['peso_actividad'],
            $datos['fase_principal'] ?? null,
            $datos['id']
        ]);
    }
    
    /**
     * NUEVO: Obtener proyecto_id de una tarea
     */
    private function obtenerProyectoIdDeTarea($tarea_id) {
        $query = "SELECT proyecto_id FROM tareas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$tarea_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['proyecto_id'] : null;
    }
    
    /**
     * Obtener una tarea específica
     */
    public function obtenerTarea($id) {
        $query = "SELECT * FROM tareas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Eliminar tarea
     */
    public function eliminarTarea($id) {
        $query = "DELETE FROM tareas WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Obtener todas las fases principales disponibles
     */
    public function obtenerFasesPrincipales($proyecto_id = null) {
        if ($proyecto_id) {
            $query = "SELECT DISTINCT fase_principal FROM tareas WHERE proyecto_id = ? AND fase_principal IS NOT NULL ORDER BY fase_principal";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
        } else {
            $query = "SELECT DISTINCT fase_principal FROM tareas WHERE fase_principal IS NOT NULL ORDER BY fase_principal";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Importar datos desde Excel (formato con PORCENTAJES) - MEJORADO
     */
    public function importarDatosExcel($proyecto_id, $datos_excel) {
        try {
            $this->conn->beginTransaction();
            
            // Limpiar tareas existentes si se especifica
            $query_clean = "DELETE FROM tareas WHERE proyecto_id = ?";
            $stmt_clean = $this->conn->prepare($query_clean);
            $stmt_clean->execute([$proyecto_id]);
            
            foreach ($datos_excel as $fila) {
                if (empty($fila['actividad']) || empty($fila['tipo'])) continue;
                
                $duracion_dias = floatval($fila['dias'] ?? 1);
                
                $datos_tarea = [
                    'nombre' => trim($fila['actividad']),
                    'tipo' => $fila['tipo'],
                    'proyecto_id' => $proyecto_id,
                    'contrato' => $fila['contrato'] ?? 'Normal',
                    'duracion_dias' => $duracion_dias,
                    'peso_actividad' => isset($fila['peso_actividad']) ? 
                                      floatval($fila['peso_actividad']) : 
                                      $this->calcularPesoPorcentual($proyecto_id, $duracion_dias),
                    'estado' => $fila['estado'] ?? 'Pendiente',
                    'porcentaje_avance' => ($fila['estado'] ?? '') === 'Listo' ? 100 : 0,
                    'fase_principal' => $fila['fase'] ?? null
                ];
                
                $this->crearTarea($datos_tarea);
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Calcular cronograma ponderado EN PORCENTAJES
     */
    public function obtenerCronogramaPonderado($proyecto_id) {
        $query = "SELECT 
                    fase_principal,
                    SUM(peso_actividad) as peso_fase,
                    AVG(duracion_dias) as duracion_promedio,
                    COUNT(*) as total_elementos,
                    COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completados,
                    SUM(CASE 
                        WHEN estado = 'Listo' THEN peso_actividad 
                        WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
                        ELSE 0 
                    END) as avance_ponderado_fase,
                    CASE 
                        WHEN SUM(peso_actividad) > 0 
                        THEN (SUM(CASE 
                            WHEN estado = 'Listo' THEN peso_actividad 
                            WHEN estado = 'En Proceso' THEN peso_actividad * (porcentaje_avance / 100)
                            ELSE 0 
                        END) / SUM(peso_actividad)) * 100
                        ELSE 0 
                    END as progreso_fase
                  FROM tareas 
                  WHERE proyecto_id = ? AND fase_principal IS NOT NULL
                  GROUP BY fase_principal
                  ORDER BY fase_principal";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Distribuir peso automáticamente basado en PORCENTAJES (deben sumar 100%) - MEJORADO
     */
    public function distribuirPesoAutomatico($proyecto_id, $metodo = 'por_fase') {
        $tareas = $this->obtenerTareasProyecto($proyecto_id);
        if (empty($tareas)) return false;
        
        try {
            $this->conn->beginTransaction();
            
            if ($metodo === 'por_dias') {
                // Distribuir basándose en días
                $this->recalcularPesosPorDias($proyecto_id);
            } else {
                // Método original por fase (Cafeto)
                $pesos_fases_cafeto = [
                    '1. Recepción de planos constructivos' => 1.00,
                    '2. Cotizaciones' => 84.00,
                    '3. Presupuesto Infraestructura' => 10.00,
                    '4. Presupuesto Casas' => 5.00
                ];
                
                // Agrupar por fase
                $tareas_por_fase = [];
                foreach ($tareas as $tarea) {
                    $fase = $tarea['fase_principal'] ?? 'Sin fase';
                    if (!isset($tareas_por_fase[$fase])) {
                        $tareas_por_fase[$fase] = [];
                    }
                    $tareas_por_fase[$fase][] = $tarea;
                }
                
                // Distribuir peso
                foreach ($tareas_por_fase as $fase => $tareas_fase) {
                    $peso_fase = $pesos_fases_cafeto[$fase] ?? (100.0 / count($tareas_por_fase));
                    $peso_por_tarea = $peso_fase / count($tareas_fase);
                    
                    foreach ($tareas_fase as $tarea) {
                        $query = "UPDATE tareas SET peso_actividad = ? WHERE id = ?";
                        $stmt = $this->conn->prepare($query);
                        $stmt->execute([$peso_por_tarea, $tarea['id']]);
                    }
                }
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>
