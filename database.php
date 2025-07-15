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
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Crear tabla si no existe
$database = new Database();
$db = $database->getConnection();

$query = "CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    tipo ENUM('Fase', 'Actividad', 'Tarea') NOT NULL,
    proyecto VARCHAR(100) DEFAULT 'CAFETO',
    duracion_dias INT DEFAULT 0,
    fecha_inicio DATE,
    fecha_fin DATE,
    porcentaje_avance DECIMAL(5,2) DEFAULT 0.00,
    estado ENUM('Pendiente', 'En Proceso', 'Listo') DEFAULT 'Pendiente',
    contrato VARCHAR(50) DEFAULT 'Normal',
    peso_actividad INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$db->exec($query);

// Insertar datos iniciales si la tabla está vacía
$count = $db->query("SELECT COUNT(*) FROM tareas")->fetchColumn();
if ($count == 0) {
    $datos_iniciales = [
        ["FASE 1. PRECONSTRUCCIÓN", "Fase", 0, "Listo", 100.00],
        ["Estudio de Factibilidad", "Actividad", 0, "Listo", 100.00],
        ["Terreno", "Tarea", 60, "Listo", 100.00],
        ["Planos", "Tarea", 30, "Listo", 100.00],
        ["Permisos", "Tarea", 45, "Listo", 100.00],
        ["FASE 2. COTIZACIONES", "Fase", 0, "En Proceso", 75.00],
        ["Cotización Infraestructura", "Actividad", 0, "Listo", 100.00],
        ["Movimiento de Tierra", "Tarea", 15, "Listo", 100.00],
        ["Servicios Públicos", "Tarea", 20, "Listo", 100.00],
        ["Vías de Acceso", "Tarea", 10, "Listo", 100.00],
        ["Cotización Casas", "Actividad", 0, "En Proceso", 50.00],
        ["Estructura", "Tarea", 25, "Listo", 100.00],
        ["Acabados", "Tarea", 30, "Pendiente", 0.00],
        ["Instalaciones", "Tarea", 20, "Pendiente", 0.00],
        ["FASE 3. PRESUPUESTO INFRAESTRUCTURA", "Fase", 0, "Pendiente", 0.00],
        ["Análisis de Costos", "Actividad", 0, "Pendiente", 0.00],
        ["Presupuesto Detallado", "Tarea", 7, "Pendiente", 0.00],
        ["Cronograma de Pagos", "Tarea", 3, "Pendiente", 0.00],
        ["FASE 4. PRESUPUESTO CASAS", "Fase", 0, "Pendiente", 0.00],
        ["Costeo por Unidad", "Actividad", 0, "Pendiente", 0.00],
        ["Presupuesto Final", "Tarea", 5, "Pendiente", 0.00],
        ["Aprobación", "Tarea", 2, "Pendiente", 0.00]
    ];
    
    $stmt = $db->prepare("INSERT INTO tareas (nombre, tipo, duracion_dias, estado, porcentaje_avance) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($datos_iniciales as $dato) {
        $stmt->execute($dato);
    }
}
?>