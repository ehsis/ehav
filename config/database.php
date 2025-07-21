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
    }
    
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
        $query = "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin_estimada, cliente, presupuesto, estado, progreso_ponderado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin_estimada'] ?? null,
            $datos['cliente'] ?? null,
            $datos['presupuesto'] ?? 0.00,
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
     * Obtener estadísticas ponderadas basadas en peso de actividad
     */
    public function obtenerEstadisticasProyecto($proyecto_id) {
        $query = "SELECT 
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
            'peso_total' => floatval($result['peso_total']),
            'avance_ponderado' => floatval($result['avance_ponderado']),
            'avance_promedio' => floatval($result['avance_promedio'])
        ];
    }
    
    /**
     * Obtener estadísticas por tipo con peso ponderado
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
     * Obtener estadísticas por fase principal
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
     * Crear tarea con todos los campos incluyendo peso
     */
    public function crearTarea($datos) {
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
            $datos['peso_actividad'] ?? 0.0000,
            $datos['fase_principal'] ?? null
        ]);
    }
    
    /**
     * Actualizar tarea con todos los campos
     */
    public function actualizarTarea($datos) {
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
            $datos['peso_actividad'] ?? 0.0000,
            $datos['fase_principal'] ?? null,
            $datos['id']
        ]);
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
     * Importar datos desde Excel (formato del archivo analizado)
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
                
                $datos_tarea = [
                    'nombre' => trim($fila['actividad']),
                    'tipo' => $fila['tipo'],
                    'proyecto_id' => $proyecto_id,
                    'contrato' => $fila['contrato'] ?? 'Normal',
                    'peso_actividad' => floatval($fila['peso_actividad'] ?? 0),
                    'duracion_dias' => floatval($fila['dias'] ?? 1),
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
     * Calcular cronograma ponderado
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
}
?>
