<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$proyectoManager = new ProyectoManager($db);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'crear_proyecto':
        try {
            // Validar datos requeridos
            if (empty($input['nombre'])) {
                echo json_encode(['success' => false, 'message' => 'El nombre del proyecto es requerido']);
                break;
            }
            
            // Preparar datos del proyecto
            $datos_proyecto = [
                'nombre' => trim($input['nombre']),
                'descripcion' => trim($input['descripcion'] ?? ''),
                'fecha_inicio' => !empty($input['fecha_inicio']) ? $input['fecha_inicio'] : null,
                'fecha_fin_estimada' => !empty($input['fecha_fin_estimada']) ? $input['fecha_fin_estimada'] : null,
                'cliente' => trim($input['cliente'] ?? ''),
                'presupuesto' => floatval($input['presupuesto'] ?? 0),
                'estado' => $input['estado'] ?? 'Activo'
            ];
            
            $resultado = $proyectoManager->crearProyecto($datos_proyecto);
            
            if ($resultado) {
                // Obtener el ID del proyecto recién creado
                $nuevo_proyecto_id = $db->lastInsertId();
                
                // Si se especificó una plantilla, copiar las tareas
                if (!empty($input['plantilla_proyecto'])) {
                    $resultado_copia = $proyectoManager->duplicarTareasPlantilla($input['plantilla_proyecto'], $nuevo_proyecto_id);
                    if (!$resultado_copia) {
                        error_log("Error al copiar tareas de la plantilla: " . $input['plantilla_proyecto']);
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'proyecto_id' => $nuevo_proyecto_id,
                    'message' => 'Proyecto creado exitosamente'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el proyecto']);
            }
        } catch (Exception $e) {
            error_log("Error al crear proyecto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'actualizar_proyecto':
        try {
            if (empty($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            if (empty($input['nombre'])) {
                echo json_encode(['success' => false, 'message' => 'El nombre del proyecto es requerido']);
                break;
            }
            
            $datos_proyecto = [
                'id' => intval($input['id']),
                'nombre' => trim($input['nombre']),
                'descripcion' => trim($input['descripcion'] ?? ''),
                'fecha_inicio' => !empty($input['fecha_inicio']) ? $input['fecha_inicio'] : null,
                'fecha_fin_estimada' => !empty($input['fecha_fin_estimada']) ? $input['fecha_fin_estimada'] : null,
                'cliente' => trim($input['cliente'] ?? ''),
                'presupuesto' => floatval($input['presupuesto'] ?? 0),
                'estado' => $input['estado'] ?? 'Activo'
            ];
            
            $resultado = $proyectoManager->actualizarProyecto($datos_proyecto);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Proyecto actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el proyecto']);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar proyecto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'duplicar_proyecto':
        try {
            if (empty($input['proyecto_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            // Obtener datos del proyecto original
            $proyecto_original = $proyectoManager->obtenerProyecto($input['proyecto_id']);
            
            if ($proyecto_original) {
                // Crear nuevo proyecto con datos similares
                $nuevos_datos = [
                    'nombre' => $proyecto_original['nombre'] . ' - Copia',
                    'descripcion' => $proyecto_original['descripcion'],
                    'fecha_inicio' => null, // Resetear fechas para el nuevo proyecto
                    'fecha_fin_estimada' => null,
                    'cliente' => $proyecto_original['cliente'],
                    'presupuesto' => $proyecto_original['presupuesto'],
                    'estado' => 'Activo'
                ];
                
                $resultado = $proyectoManager->crearProyecto($nuevos_datos);
                
                if ($resultado) {
                    $nuevo_proyecto_id = $db->lastInsertId();
                    
                    // Copiar todas las tareas del proyecto original con sus pesos
                    $resultado_copia = $proyectoManager->duplicarTareasPlantilla($input['proyecto_id'], $nuevo_proyecto_id);
                    
                    if ($resultado_copia) {
                        echo json_encode([
                            'success' => true, 
                            'proyecto_id' => $nuevo_proyecto_id,
                            'message' => 'Proyecto duplicado exitosamente'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Proyecto creado pero error al copiar tareas']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al duplicar el proyecto']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Proyecto original no encontrado']);
            }
        } catch (Exception $e) {
            error_log("Error al duplicar proyecto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'eliminar_proyecto':
        try {
            if (empty($input['proyecto_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $proyecto_id = intval($input['proyecto_id']);
            
            // Verificar que el proyecto existe
            $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
            if (!$proyecto) {
                echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
                break;
            }
            
            $resultado = $proyectoManager->eliminarProyecto($proyecto_id);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Proyecto eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el proyecto']);
            }
        } catch (Exception $e) {
            error_log("Error al eliminar proyecto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'obtener_proyecto':
        try {
            $proyecto_id = $input['id'] ?? $_GET['id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
            
            if ($proyecto) {
                echo json_encode($proyecto);
            } else {
                echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
            }
        } catch (Exception $e) {
            error_log("Error al obtener proyecto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'obtener_proyectos':
        try {
            $proyectos = $proyectoManager->obtenerProyectos();
            echo json_encode($proyectos);
        } catch (Exception $e) {
            error_log("Error al obtener proyectos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'estadisticas_proyecto':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $estadisticas = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
            echo json_encode($estadisticas);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'estadisticas_por_tipo':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $estadisticas = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
            echo json_encode($estadisticas);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas por tipo: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'estadisticas_por_fase':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $estadisticas = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
            echo json_encode($estadisticas);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas por fase: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'cronograma_ponderado':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
            echo json_encode($cronograma);
        } catch (Exception $e) {
            error_log("Error al obtener cronograma: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'importar_excel_cafeto':
        try {
            $proyecto_id = $input['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            // Datos del Excel Cafeto analizados previamente
            $datos_cafeto = [
                // Fase 1: Recepción de planos constructivos
                [
                    'actividad' => 'Recepción de Planos',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0100,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'REVISIÓN DE PLANOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0066,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'INFRAESTRUCTURA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0033,
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'CASAS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0033,
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'CREACION RED LINES',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0017,
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'ENVIAR RED LINES AL DISEÑADOR',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.0017,
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                
                // Fase 2: Cotizaciones (100% completada)
                [
                    'actividad' => 'COTIZACIONES',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.8400,
                    'dias' => 47,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'COTIZAR SUBCONTRATOS INFRA Y AMENIDADES',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.5460,
                    'dias' => 31,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'MOVIMIENTOS DE TIERRA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'MUROS DE TILO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'TANQUE DE RETARDO PLUVIAL',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PLANTA DE TRATAMIENTO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'LASTRADOS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PAVIMENTOS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.0420,
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'COTIZAR SUBCONTRATOS CASAS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 0.2940,
                    'dias' => 16,
                    'estado' => 'Listo'
                ],
                
                // Fase 3: Presupuesto Infraestructura (Pendiente)
                [
                    'actividad' => 'PRESUPUESTO INFRAESTRUCTURA',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.0999,
                    'dias' => 6,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'COSTOS DIRECTOS CONSTRUCTIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.0839,
                    'dias' => 5,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'COSTOS INDIRECTOS CONSTRUCTIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.0080,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'COSTOS INDIRECTOS ADMINISTRATIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.0080,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                
                // Fase 4: Presupuesto Casas (Pendiente)
                [
                    'actividad' => 'PRESUPUESTO CASAS',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.0500,
                    'dias' => 3,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'PRESUPUESTO DETALLADO CASAS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.0095,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'DOCUMENTOS P&E',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.0110,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'O4Bi',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.0140,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'CRONOGRAMA DETALLADO',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.0155,
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ]
            ];
            
            $resultado = $proyectoManager->importarDatosExcel($proyecto_id, $datos_cafeto);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Datos del proyecto Cafeto importados exitosamente',
                    'total_tareas' => count($datos_cafeto)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al importar datos']);
            }
        } catch (Exception $e) {
            error_log("Error al importar datos Cafeto: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'calcular_progreso_total':
        try {
            $resultado = [];
            $proyectos = $proyectoManager->obtenerProyectos();
            
            foreach ($proyectos as $proyecto) {
                $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto['id']);
                $resultado[] = [
                    'proyecto_id' => $proyecto['id'],
                    'nombre' => $proyecto['nombre'],
                    'progreso_ponderado' => $stats['avance_promedio'],
                    'peso_total' => $stats['peso_total'],
                    'avance_total' => $stats['avance_ponderado']
                ];
            }
            
            echo json_encode($resultado);
        } catch (Exception $e) {
            error_log("Error al calcular progreso total: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'recalcular_progreso':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if ($proyecto_id) {
                // Recalcular para un proyecto específico
                $query = "UPDATE proyectos SET progreso_ponderado = calcular_progreso_ponderado(id) WHERE id = ?";
                $stmt = $db->prepare($query);
                $resultado = $stmt->execute([$proyecto_id]);
                
                if ($resultado) {
                    $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
                    echo json_encode([
                        'success' => true, 
                        'mensaje' => 'Progreso recalculado',
                        'progreso_actualizado' => $stats['avance_promedio']
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al recalcular progreso']);
                }
            } else {
                // Recalcular para todos los proyectos
                $query = "UPDATE proyectos SET progreso_ponderado = calcular_progreso_ponderado(id)";
                $stmt = $db->prepare($query);
                $resultado = $stmt->execute();
                
                if ($resultado) {
                    echo json_encode(['success' => true, 'mensaje' => 'Progreso recalculado para todos los proyectos']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al recalcular progreso']);
                }
            }
        } catch (Exception $e) {
            error_log("Error al recalcular progreso: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
        break;
}
?>
