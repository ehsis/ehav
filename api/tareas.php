<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear':
        $query = "INSERT INTO tareas (nombre, tipo, duracion_dias, estado, porcentaje_avance, proyecto_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['nombre'],
            $input['tipo'],
            $input['duracion_dias'],
            $input['estado'],
            $input['porcentaje_avance'],
            $input['proyecto_id']
        ]);
        echo json_encode(['success' => $result]);
        break;

    case 'actualizar':
        $query = "UPDATE tareas SET estado = ?, porcentaje_avance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['estado'],
            $input['porcentaje_avance'],
            $input['id']
        ]);
        echo json_encode(['success' => $result]);
        break;

    case 'eliminar':
        $query = "DELETE FROM tareas WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$input['id']]);
        echo json_encode(['success' => $result]);
        break;

    case 'obtener':
        $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
        
        if ($proyecto_id) {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute([$proyecto_id]);
        } else {
            $query = "SELECT * FROM tareas ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }
        
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tareas);
        break;

    case 'estadisticas':
        $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
        
        if ($proyecto_id) {
            $query = "SELECT 
                estado,
                COUNT(*) as cantidad,
                AVG(porcentaje_avance) as promedio_avance
                FROM tareas 
                WHERE proyecto_id = ?
                GROUP BY estado";
            $stmt = $db->prepare($query);
            $stmt->execute([$proyecto_id]);
        } else {
            $query = "SELECT 
                estado,
                COUNT(*) as cantidad,
                AVG(porcentaje_avance) as promedio_avance
                FROM tareas 
                GROUP BY estado";
            $stmt = $db->prepare($query);
            $stmt->execute();
        }
        
        $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($estadisticas);
        break;

    case 'obtener_por_fase':
        $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
        $fase = $input['fase'] ?? $_GET['fase'] ?? '';
        
        if ($proyecto_id && $fase) {
            $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND nombre LIKE ? ORDER BY id";
            $stmt = $db->prepare($query);
            $stmt->execute([$proyecto_id, "%$fase%"]);
        } else {
            echo json_encode(['error' => 'Parámetros insuficientes']);
            break;
        }
        
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tareas);
        break;

    case 'actualizar_completa':
        $query = "UPDATE tareas SET 
                  nombre = ?, 
                  tipo = ?, 
                  duracion_dias = ?, 
                  estado = ?, 
                  porcentaje_avance = ?, 
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $input['nombre'],
            $input['tipo'],
            $input['duracion_dias'],
            $input['estado'],
            $input['porcentaje_avance'],
            $input['id']
        ]);
        echo json_encode(['success' => $result]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>