<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear':
        try {
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
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizar':
        try {
            $query = "UPDATE tareas SET estado = ?, porcentaje_avance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['estado'],
                $input['porcentaje_avance'],
                $input['id']
            ]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizar_completa':
        try {
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
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        try {
            $query = "DELETE FROM tareas WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([$input['id']]);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener':
        try {
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
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_tarea':
        try {
            $tarea_id = $input['id'] ?? $_GET['id'] ?? null;
            
            if ($tarea_id) {
                $query = "SELECT * FROM tareas WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$tarea_id]);
                $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tarea) {
                    echo json_encode($tarea);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Tarea no encontrada']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de tarea requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'estadisticas':
        try {
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
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_por_fase':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            $fase = $input['fase'] ?? $_GET['fase'] ?? '';
            
            if ($proyecto_id && $fase) {
                $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND nombre LIKE ? ORDER BY id";
                $stmt = $db->prepare($query);
                $stmt->execute([$proyecto_id, "%$fase%"]);
                
                $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($tareas);
            } else {
                echo json_encode(['success' => false, 'message' => 'Parámetros insuficientes']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_por_tipo':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            $tipo = $input['tipo'] ?? $_GET['tipo'] ?? null;
            
            if ($proyecto_id) {
                if ($tipo) {
                    $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND tipo = ? ORDER BY id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$proyecto_id, $tipo]);
                } else {
                    $query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY tipo, id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$proyecto_id]);
                }
                
                $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($tareas);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'estadisticas_por_tipo':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
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
                $stmt = $db->prepare($query);
                $stmt->execute([$proyecto_id]);
                
                $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($estadisticas);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'resumen_proyecto':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                // Obtener estadísticas generales
                $query_general = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as completadas,
                    COUNT(CASE WHEN estado = 'En Proceso' THEN 1 END) as en_proceso,
                    COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
                    AVG(porcentaje_avance) as avance_promedio,
                    SUM(duracion_dias) as duracion_total
                    FROM tareas 
                    WHERE proyecto_id = ?";
                $stmt_general = $db->prepare($query_general);
                $stmt_general->execute([$proyecto_id]);
                $general = $stmt_general->fetch(PDO::FETCH_ASSOC);
                
                // Obtener estadísticas por tipo
                $query_tipos = "SELECT 
                    tipo,
                    COUNT(*) as cantidad,
                    AVG(porcentaje_avance) as avance_promedio
                    FROM tareas 
                    WHERE proyecto_id = ?
                    GROUP BY tipo";
                $stmt_tipos = $db->prepare($query_tipos);
                $stmt_tipos->execute([$proyecto_id]);
                $tipos = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'general' => $general,
                    'por_tipo' => $tipos
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
        break;
}
?>
