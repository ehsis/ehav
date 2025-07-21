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

$proyectoManager = new ProyectoManager($db);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear':
        try {
            // Validar datos requeridos
            if (empty($input['nombre']) || empty($input['proyecto_id'])) {
                echo json_encode(['success' => false, 'message' => 'Nombre y proyecto_id son requeridos']);
                break;
            }
            
            $datos = [
                'nombre' => trim($input['nombre']),
                'tipo' => $input['tipo'] ?? 'Tarea',
                'duracion_dias' => intval($input['duracion_dias'] ?? 1),
                'estado' => $input['estado'] ?? 'Pendiente',
                'porcentaje_avance' => floatval($input['porcentaje_avance'] ?? 0),
                'proyecto_id' => intval($input['proyecto_id']),
                'contrato' => $input['contrato'] ?? 'Normal',
                'peso_actividad' => floatval($input['peso_actividad'] ?? 0.0000),
                'fase_principal' => !empty($input['fase_principal']) ? trim($input['fase_principal']) : null
            ];
            
            $result = $proyectoManager->crearTarea($datos);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Tarea creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la tarea']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'actualizar':
        try {
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de tarea requerido']);
                break;
            }
            
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
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de tarea requerido']);
                break;
            }
            
            $datos = [
                'id' => intval($input['id']),
                'nombre' => trim($input['nombre']),
                'tipo' => $input['tipo'],
                'duracion_dias' => intval($input['duracion_dias']),
                'estado' => $input['estado'],
                'porcentaje_avance' => floatval($input['porcentaje_avance']),
                'contrato' => $input['contrato'] ?? 'Normal',
                'peso_actividad' => floatval($input['peso_actividad'] ?? 0.0000),
                'fase_principal' => !empty($input['fase_principal']) ? trim($input['fase_principal']) : null
            ];
            
            $result = $proyectoManager->actualizarTarea($datos);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        try {
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de tarea requerido']);
                break;
            }
            
            $result = $proyectoManager->eliminarTarea($input['id']);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
            } else {
                $query = "SELECT * FROM tareas ORDER BY proyecto_id, fase_principal, tipo, id";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($tareas);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_tarea':
        try {
            $tarea_id = $input['id'] ?? $_GET['id'] ?? null;
            
            if ($tarea_id) {
                $tarea = $proyectoManager->obtenerTarea($tarea_id);
                
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
                $estadisticas = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
                echo json_encode($estadisticas);
            } else {
                $query = "SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    SUM(peso_actividad) as peso_total,
                    AVG(porcentaje_avance) as promedio_avance
                    FROM tareas 
                    GROUP BY estado";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($estadisticas);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'estadisticas_por_tipo':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                $estadisticas = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
                echo json_encode($estadisticas);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'estadisticas_por_fase':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                $estadisticas = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
                echo json_encode($estadisticas);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_por_fase':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            $fase = $input['fase'] ?? $_GET['fase'] ?? '';
            
            if ($proyecto_id && $fase) {
                $query = "SELECT * FROM tareas WHERE proyecto_id = ? AND fase_principal = ? ORDER BY tipo, id";
                $stmt = $db->prepare($query);
                $stmt->execute([$proyecto_id, $fase]);
                
                $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($tareas);
            } else {
                echo json_encode(['success' => false, 'message' => 'Proyecto ID y fase requeridos']);
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
                $tareas = $proyectoManager->obtenerTareasPorTipo($proyecto_id, $tipo);
                echo json_encode($tareas);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'obtener_fases':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            $fases = $proyectoManager->obtenerFasesPrincipales($proyecto_id);
            echo json_encode($fases);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'resumen_proyecto':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                // Obtener estadísticas generales
                $general = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
                
                // Obtener estadísticas por tipo
                $por_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
                
                // Obtener estadísticas por fase
                $por_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
                
                echo json_encode([
                    'general' => $general,
                    'por_tipo' => $por_tipo,
                    'por_fase' => $por_fase
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'cronograma_ponderado':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
                echo json_encode($cronograma);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'importar_excel':
        try {
            $proyecto_id = $input['proyecto_id'] ?? null;
            $datos_excel = $input['datos'] ?? [];
            
            if (!$proyecto_id || empty($datos_excel)) {
                echo json_encode(['success' => false, 'message' => 'Proyecto ID y datos requeridos']);
                break;
            }
            
            $result = $proyectoManager->importarDatosExcel($proyecto_id, $datos_excel);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Datos importados exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al importar datos']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'distribuir_peso':
        try {
            $proyecto_id = $input['proyecto_id'] ?? null;
            $peso_total = $input['peso_total'] ?? 1.0;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'Proyecto ID requerido']);
                break;
            }
            
            // Obtener todas las tareas del proyecto
            $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
            $total_tareas = count($tareas);
            
            if ($total_tareas == 0) {
                echo json_encode(['success' => false, 'message' => 'No hay tareas en el proyecto']);
                break;
            }
            
            // Distribuir peso equitativamente
            $peso_por_tarea = $peso_total / $total_tareas;
            
            foreach ($tareas as $tarea) {
                $datos = [
                    'id' => $tarea['id'],
                    'nombre' => $tarea['nombre'],
                    'tipo' => $tarea['tipo'],
                    'duracion_dias' => $tarea['duracion_dias'],
                    'estado' => $tarea['estado'],
                    'porcentaje_avance' => $tarea['porcentaje_avance'],
                    'contrato' => $tarea['contrato'],
                    'peso_actividad' => $peso_por_tarea,
                    'fase_principal' => $tarea['fase_principal']
                ];
                
                $proyectoManager->actualizarTarea($datos);
            }
            
            echo json_encode(['success' => true, 'message' => "Peso distribuido equitativamente: {$peso_por_tarea} por tarea"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
        break;
}
?>
