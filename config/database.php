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
        $query = "SELECT * FROM proyectos ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerProyecto($id) {
        $query = "SELECT * FROM proyectos WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function crearProyecto($datos) {
        $query = "INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin_estimada, cliente, presupuesto, estado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'] ?? null,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin_estimada'] ?? null,
            $datos['cliente'] ?? null,
            $datos['presupuesto'] ?? 0.00,
            $datos['estado'] ?? 'Activo'
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
    
    public function obtenerEstadisticasProyecto($proyecto_id) {
        $query = "SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
            COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
            COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
            COALESCE(AVG(porcentaje_avance), 0) as avance_promedio
            FROM tareas 
            WHERE proyecto_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Asegurar que todos los valores sean numéricos
        $result['total'] = intval($result['total']);
        $result['completadas'] = intval($result['completadas']);
        $result['en_proceso'] = intval($result['en_proceso']);
        $result['pendientes'] = intval($result['pendientes']);
        $result['avance_promedio'] = floatval($result['avance_promedio']);
        
        return $result;
    }
    
    public function obtenerTareasProyecto($proyecto_id) {
        $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function duplicarTareasPlantilla($proyecto_origen_id, $proyecto_destino_id) {
        $query = "INSERT INTO tareas (nombre, tipo, duracion_dias, estado, porcentaje_avance, proyecto_id, contrato, peso_actividad)
                  SELECT nombre, tipo, duracion_dias, 'Pendiente', 0.00, ?, contrato, peso_actividad
                  FROM tareas 
                  WHERE proyecto_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$proyecto_destino_id, $proyecto_origen_id]);
    }
    
    public function obtenerTareasPorTipo($proyecto_id, $tipo = null) {
        if ($tipo) {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND tipo = ? ORDER BY id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id, $tipo]);
        } else {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY tipo, id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$proyecto_id]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function obtenerEstadisticasPorTipo($proyecto_id) {
        $query = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
                    COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
                    COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
                    AVG(porcentaje_avance) as avance_promedio
                  FROM tareas 
                  WHERE proyecto_id = ?
                  GROUP BY tipo
                  ORDER BY FIELD(tipo, 'Fase', 'Actividad', 'Tarea')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
