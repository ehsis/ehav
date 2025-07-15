<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$proyectoManager = new ProyectoManager($db);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear_proyecto':
        try {
            $resultado = $proyectoManager->crearProyecto($input);
            
            if ($resultado) {
                // Obtener el ID del proyecto recién creado
                $nuevo_proyecto_id = $db->lastInsertId();
                
                // Si se especificó una plantilla, copiar las tareas
                if (!empty($input['plantilla_proyecto'])) {
                    $proyectoManager->duplicarTareasPlantilla($input['plantilla_proyecto'], $nuevo_proyecto_id);
                }
                
                echo json_encode(['success' => true, 'proyecto_id' => $nuevo_proyecto_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el proyecto']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'duplicar_proyecto':
        try {
            // Obtener datos del proyecto original
            $proyecto_original = $proyectoManager->obtenerProyecto($input['proyecto_id']);
            
            if ($proyecto_original) {
                // Crear nuevo proyecto con datos similares
                $nuevos_datos = [
                    'nombre' => $proyecto_original['nombre'] . ' - Copia',
                    'descripcion' => $proyecto_original['descripcion'],
                    'fecha_inicio' => null,
                    'fecha_fin_estimada' => null,
                    'cliente' => $proyecto_original['cliente'],
                    'presupuesto' => $proyecto_original['presupuesto'],
                    'estado' => 'Activo'
                ];
                
                $resultado = $proyectoManager->crearProyecto($nuevos_datos);
                
                if ($resultado) {
                    $nuevo_proyecto_id = $db->lastInsertId();
                    
                    // Copiar todas las tareas del proyecto original
                    $proyectoManager->duplicarTareasPlantilla($input['proyecto_id'], $nuevo_proyecto_id);
                    
                    echo json_encode(['success' => true, 'proyecto_id' => $nuevo_proyecto_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al duplicar el proyecto']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Proyecto original no encontrado']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar_proyecto':
        try {
            // Primero eliminar todas las tareas del proyecto
            $query_tareas = "DELETE FROM tareas WHERE proyecto_id = ?";
            $stmt_tareas = $db->prepare($query_tareas);
 $stmt_tareas->execute([$input['proyecto_id']]);