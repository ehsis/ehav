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
            
            // CORREGIDO: Datos del Excel Cafeto con PORCENTAJES según el análisis
            $datos_cafeto = [
                // Fase 1: Recepción de planos constructivos (1.00% total)
                [
                    'actividad' => 'RECEPCIÓN DE PLANOS',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 1.00,    // 1.00%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'REVISIÓN DE PLANOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.66,    // 0.66%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'INFRAESTRUCTURA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.33,    // 0.33%
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'CASAS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.33,    // 0.33%
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'CREACION RED LINES',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.17,    // 0.17%
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'ENVIAR RED LINES AL DISEÑADOR',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '1. Recepción de planos constructivos',
                    'peso_actividad' => 0.17,    // 0.17%
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                
                // Fase 2: Cotizaciones (84.00% total - LA MÁS PESADA)
                [
                    'actividad' => 'COTIZACIONES',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 84.00,   // 84.00% - fase principal
                    'dias' => 47,
                    'estado' => 'En Proceso'
                ],
                [
                    'actividad' => 'COTIZAR SUBCONTRATOS INFRA Y AMENIDADES',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 54.60,   // 54.60%
                    'dias' => 31,
                    'estado' => 'En Proceso'
                ],
                [
                    'actividad' => 'MOVIMIENTOS DE TIERRA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'MUROS DE TILO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'TANQUE DE RETARDO PLUVIAL',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PLANTA DE TRATAMIENTO O ESTACIÓN BOMBEO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'LASTRADOS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PAVIMENTOS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'LANDSCAPING Y RIEGO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'SISTEMA ELÉCTRICO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PISCINA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'VENTANERÍA',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'MOBILIARIO',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'BARANDAS',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'PLAY GROUND',
                    'tipo' => 'Tarea',
                    'contrato' => 'Contrato Clave',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 4.20,    // 4.20%
                    'dias' => 2,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'COTIZAR SUBCONTRATOS CASAS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '2. Cotizaciones',
                    'peso_actividad' => 29.40,   // 29.40%
                    'dias' => 16,
                    'estado' => 'Listo'
                ],
                
                // Fase 3: Presupuesto Infraestructura (9.99% total)
                [
                    'actividad' => 'PRESUPUESTO INFRAESTRUCTURA',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 9.99,    // 9.99%
                    'dias' => 6,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'COSTOS DIRECTOS CONSTRUCTIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 8.39,    // 8.39%
                    'dias' => 5,
                    'estado' => 'En Proceso'
                ],
                [
                    'actividad' => '01. OBRAS INICIALES',
                    'tipo' => 'Tarea',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.23,    // 0.23%
                    'dias' => 1,
                    'estado' => 'Listo'
                ],
                [
                    'actividad' => 'COSTOS INDIRECTOS CONSTRUCTIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.80,    // 0.80%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'COSTOS INDIRECTOS ADMINISTRATIVOS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '3. Presupuesto Infraestructura',
                    'peso_actividad' => 0.80,    // 0.80%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                
                // Fase 4: Presupuesto Casas (5.00% total)
                [
                    'actividad' => 'PRESUPUESTO CASAS',
                    'tipo' => 'Fase',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 5.00,    // 5.00%
                    'dias' => 3,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'PRESUPUESTO DETALLADO CASAS',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.95,    // 0.95%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'DOCUMENTOS P&E',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 1.10,    // 1.10%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'O4Bi',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 1.40,    // 1.40%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'DOCUMENTOS OPERACIONES',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 0.17,    // 0.17%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ],
                [
                    'actividad' => 'CRONOGRAMA DETALLADO',
                    'tipo' => 'Actividad',
                    'contrato' => 'Normal',
                    'fase' => '4. Presupuesto Casas',
                    'peso_actividad' => 1.55,    // 1.55%
                    'dias' => 1,
                    'estado' => 'Pendiente'
                ]
            ];
            
            // Verificar que la suma sea aproximadamente 100%
            $peso_total_verificacion = array_sum(array_column($datos_cafeto, 'peso_actividad'));
            error_log("Peso total del proyecto Cafeto: " . number_format($peso_total_verificacion, 2) . "%");
            
            $resultado = $proyectoManager->importarDatosExcel($proyecto_id, $datos_cafeto);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Datos del proyecto Cafeto importados exitosamente',
                    'total_tareas' => count($datos_cafeto),
                    'peso_total' => round($peso_total_verificacion, 2),
                    'distribucion_fases' => [
                        'Recepción de planos' => '1.00%',
                        'Cotizaciones' => '84.00%',
                        'Presupuesto Infraestructura' => '9.99%',
                        'Presupuesto Casas' => '5.00%'
                    ]
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
                // Obtener estadísticas actualizadas después del recálculo
                $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
                
                echo json_encode([
                    'success' => true, 
                    'mensaje' => 'Progreso recalculado',
                    'progreso_actualizado' => $stats['avance_promedio'],
                    'peso_total' => $stats['peso_total'],
                    'avance_ponderado' => $stats['avance_ponderado']
                ]);
            } else {
                echo json_encode(['success' => true, 'mensaje' => 'Progreso recalculado para todos los proyectos']);
            }
        } catch (Exception $e) {
            error_log("Error al recalcular progreso: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    case 'validar_pesos_proyecto':
        try {
            $proyecto_id = $input['proyecto_id'] ?? $_GET['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
                break;
            }
            
            $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
            $peso_total = array_sum(array_column($tareas, 'peso_actividad'));
            
            $validacion = [
                'peso_total' => $peso_total,
                'es_valido' => abs($peso_total - 100) <= 5, // Tolerancia del 5%
                'diferencia' => $peso_total - 100,
                'mensaje' => ''
            ];
            
            if ($peso_total > 105) {
                $validacion['mensaje'] = 'El peso total excede el 100% - hay sobreponderación';
            } elseif ($peso_total < 95) {
                $validacion['mensaje'] = 'El peso total es menor al 95% - faltan pesos por asignar';
            } else {
                $validacion['mensaje'] = 'Los pesos están balanceados correctamente';
            }
            
            echo json_encode([
                'success' => true,
                'validacion' => $validacion
            ]);
            
        } catch (Exception $e) {
            error_log("Error al validar pesos: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $action]);
        break;
}
?>
