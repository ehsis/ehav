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
            
            // CORREGIDO: Validar peso de actividad en rango 0-100%
            $peso_actividad = floatval($input['peso_actividad'] ?? 0.00);
            if ($peso_actividad < 0 || $peso_actividad > 100) {
                echo json_encode(['success' => false, 'message' => 'El peso de actividad debe estar entre 0% y 100%']);
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
                'peso_actividad' => $peso_actividad, // CORREGIDO: Ya en porcentajes 0-100
                'fase_principal' => !empty($input['fase_principal']) ? trim($input['fase_principal']) : null
            ];
            
            $result = $proyectoManager->crearTarea($datos);
            
            if ($result) {
                // NUEVO: Verificar si el peso total del proyecto excede 100%
                $estadisticas = $proyectoManager->obtenerEstadisticasProyecto($datos['proyecto_id']);
                $peso_total = $estadisticas['peso_total'];
                
                $response = ['success' => true, 'message' => 'Tarea creada exitosamente'];
                
                if ($peso_total > 105) {
                    $response['warning'] = "⚠️ El peso total del proyecto ahora es {$peso_total}%, excede el 100%";
                } elseif ($peso_total > 100) {
                    $response['info'] = "ℹ️ El peso total del proyecto es {$peso_total}%";
                }
                
                echo json_encode($response);
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
            
            // CORREGIDO: Validar porcentaje de avance
            $porcentaje_avance = floatval($input['porcentaje_avance']);
            if ($porcentaje_avance < 0 || $porcentaje_avance > 100) {
                echo json_encode(['success' => false, 'message' => 'El porcentaje de avance debe estar entre 0% y 100%']);
                break;
            }
            
            $query = "UPDATE tareas SET estado = ?, porcentaje_avance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                $input['estado'],
                $porcentaje_avance,
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
            
            // CORREGIDO: Validar peso de actividad en rango 0-100%
            $peso_actividad = floatval($input['peso_actividad'] ?? 0.00);
            if ($peso_actividad < 0 || $peso_actividad > 100) {
                echo json_encode(['success' => false, 'message' => 'El peso de actividad debe estar entre 0% y 100%']);
                break;
            }
            
            // CORREGIDO: Validar porcentaje de avance
            $porcentaje_avance = floatval($input['porcentaje_avance']);
            if ($porcentaje_avance < 0 || $porcentaje_avance > 100) {
                echo json_encode(['success' => false, 'message' => 'El porcentaje de avance debe estar entre 0% y 100%']);
                break;
            }
            
            $datos = [
                'id' => intval($input['id']),
                'nombre' => trim($input['nombre']),
                'tipo' => $input['tipo'],
                'duracion_dias' => intval($input['duracion_dias']),
                'estado' => $input['estado'],
                'porcentaje_avance' => $porcentaje_avance,
                'contrato' => $input['contrato'] ?? 'Normal',
                'peso_actividad' => $peso_actividad, // CORREGIDO: Ya en porcentajes 0-100
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
            
            // NUEVO: Formatear pesos para la respuesta
            foreach ($tareas as &$tarea) {
                $tarea['peso_actividad_formateado'] = number_format($tarea['peso_actividad'], 2) . '%';
                $tarea['porcentaje_avance_formateado'] = number_format($tarea['porcentaje_avance'], 1) . '%';
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
                    // NUEVO: Agregar datos formateados
                    $tarea['peso_actividad_formateado'] = number_format($tarea['peso_actividad'], 2) . '%';
                    $tarea['porcentaje_avance_formateado'] = number_format($tarea['porcentaje_avance'], 1) . '%';
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
                
                // NUEVO: Agregar validación de integridad
                $peso_total = $estadisticas['peso_total'];
                $estadisticas['integridad'] = [
                    'peso_total_correcto' => abs($peso_total - 100) < 1,
                    'peso_total_formateado' => number_format($peso_total, 2) . '%',
                    'diferencia_100' => $peso_total - 100,
                    'mensaje' => abs($peso_total - 100) < 1 ? 'Pesos correctos' : 'Revisar pesos'
                ];
                
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
                
                // NUEVO: Agregar datos formateados
                foreach ($estadisticas as &$stat) {
                    $stat['peso_total_formateado'] = number_format($stat['peso_total'], 2) . '%';
                    $stat['avance_promedio_formateado'] = number_format($stat['avance_promedio'], 1) . '%';
                }
                
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
                
                // NUEVO: Agregar datos formateados y validación
                foreach ($estadisticas as &$stat) {
                    $stat['peso_total_formateado'] = number_format($stat['peso_total'], 2) . '%';
                    $stat['avance_promedio_formateado'] = number_format($stat['avance_promedio'], 1) . '%';
                }
                
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
                
                // NUEVO: Formatear datos y calcular totales de la fase
                $peso_total_fase = 0;
                foreach ($tareas as &$tarea) {
                    $tarea['peso_actividad_formateado'] = number_format($tarea['peso_actividad'], 2) . '%';
                    $tarea['porcentaje_avance_formateado'] = number_format($tarea['porcentaje_avance'], 1) . '%';
                    $peso_total_fase += floatval($tarea['peso_actividad']);
                }
                
                echo json_encode([
                    'tareas' => $tareas,
                    'resumen_fase' => [
                        'total_tareas' => count($tareas),
                        'peso_total_fase' => number_format($peso_total_fase, 2) . '%'
                    ]
                ]);
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
                
                // NUEVO: Formatear datos
                foreach ($tareas as &$tarea) {
                    $tarea['peso_actividad_formateado'] = number_format($tarea['peso_actividad'], 2) . '%';
                    $tarea['porcentaje_avance_formateado'] = number_format($tarea['porcentaje_avance'], 1) . '%';
                }
                
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
                
                // NUEVO: Agregar validación general del proyecto
                $peso_total = $general['peso_total'];
                $validacion = [
                    'peso_total_correcto' => abs($peso_total - 100) < 1,
                    'peso_total' => $peso_total,
                    'diferencia_100' => $peso_total - 100,
                    'estado_integridad' => abs($peso_total - 100) < 1 ? 'correcto' : 'requiere_ajuste',
                    'mensaje' => abs($peso_total - 100) < 1 ? 
                        'Los pesos están correctamente balanceados' : 
                        "Los pesos suman {$peso_total}%, se requiere ajuste para llegar a 100%"
                ];
                
                echo json_encode([
                    'general' => $general,
                    'por_tipo' => $por_tipo,
                    'por_fase' => $por_fase,
                    'validacion' => $validacion
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
            
            // NUEVO: Validar que los pesos en el Excel estén en formato correcto
            $peso_total_excel = 0;
            foreach ($datos_excel as $fila) {
                $peso = floatval($fila['peso_actividad'] ?? 0);
                if ($peso > 1.5) {
                    // Ya está en porcentajes
                    $peso_total_excel += $peso;
                } else {
                    // Está en decimales, convertir
                    $peso_total_excel += $peso * 100;
                }
            }
            
            $result = $proyectoManager->importarDatosExcel($proyecto_id, $datos_excel);
            
            if ($result) {
                $mensaje = 'Datos importados exitosamente';
                if (abs($peso_total_excel - 100) > 5) {
                    $mensaje .= ". ⚠️ Advertencia: El peso total importado es {$peso_total_excel}%, considera revisar los pesos.";
                }
                echo json_encode(['success' => true, 'message' => $mensaje]);
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
            $peso_total = $input['peso_total'] ?? 100.0; // CORREGIDO: Por defecto 100% en lugar de 1.0
            $metodo = $input['metodo'] ?? 'equitativo'; // NUEVO: Método de distribución
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'Proyecto ID requerido']);
                break;
            }
            
            // CORREGIDO: Validar que peso_total esté en rango válido
            if ($peso_total <= 0 || $peso_total > 100) {
                echo json_encode(['success' => false, 'message' => 'El peso total debe estar entre 0.01% y 100%']);
                break;
            }
            
            // Obtener todas las tareas del proyecto
            $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
            $total_tareas = count($tareas);
            
            if ($total_tareas == 0) {
                echo json_encode(['success' => false, 'message' => 'No hay tareas en el proyecto']);
                break;
            }
            
            // NUEVO: Diferentes métodos de distribución
            switch ($metodo) {
                case 'por_fase':
                    $result = $proyectoManager->distribuirPesoAutomatico($proyecto_id, 'por_fase');
                    $mensaje = "Peso distribuido por fase según proyecto Cafeto";
                    break;
                    
                case 'por_tipo':
                    $result = $proyectoManager->distribuirPesoAutomatico($proyecto_id, 'por_tipo');
                    $mensaje = "Peso distribuido por tipo de tarea";
                    break;
                    
                case 'por_duracion':
                    $result = $proyectoManager->distribuirPesoAutomatico($proyecto_id, 'por_duracion');
                    $mensaje = "Peso distribuido según duración de tareas";
                    break;
                    
                case 'equitativo':
                default:
                    // CORREGIDO: Distribución equitativa usando porcentajes
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
                            'peso_actividad' => $peso_por_tarea, // CORREGIDO: Ya en porcentajes
                            'fase_principal' => $tarea['fase_principal']
                        ];
                        
                        $proyectoManager->actualizarTarea($datos);
                    }
                    
                    $mensaje = "Peso distribuido equitativamente: " . number_format($peso_por_tarea, 2) . "% por tarea";
                    $result = true;
                    break;
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => $mensaje]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al distribuir peso']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // NUEVO: Acción para corregir pesos automáticamente
    case 'corregir_pesos':
        try {
            $proyecto_id = $input['proyecto_id'] ?? null;
            
            if (!$proyecto_id) {
                echo json_encode(['success' => false, 'message' => 'Proyecto ID requerido']);
                break;
            }
            
            // Obtener estadísticas actuales
            $estadisticas = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
            $peso_total_actual = $estadisticas['peso_total'];
            
            // Si está muy alejado de 100%, normalizar
            if (abs($peso_total_actual - 100) > 1 && $peso_total_actual > 0) {
                $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
                $factor_correccion = 100 / $peso_total_actual;
                
                foreach ($tareas as $tarea) {
                    $nuevo_peso = $tarea['peso_actividad'] * $factor_correccion;
                    
                    $datos = [
                        'id' => $tarea['id'],
                        'nombre' => $tarea['nombre'],
                        'tipo' => $tarea['tipo'],
                        'duracion_dias' => $tarea['duracion_dias'],
                        'estado' => $tarea['estado'],
                        'porcentaje_avance' => $tarea['porcentaje_avance'],
                        'contrato' => $tarea['contrato'],
                        'peso_actividad' => $nuevo_peso,
                        'fase_principal' => $tarea['fase_principal']
                    ];
                    
                    $proyectoManager->actualizarTarea($datos);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Pesos corregidos. Antes: {$peso_total_actual}%, Ahora: 100%"
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => "Los pesos ya están correctos ({$peso_total_actual}%)"
                ]);
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
