<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/includes.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$proyectoManager = new ProyectoManager($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$proyecto_id = $_GET['proyecto_id'] ?? $_POST['proyecto_id'] ?? null;

switch ($action) {
    case 'datos_graficos':
        obtenerDatosGraficos($proyectoManager, $proyecto_id);
        break;
        
    case 'estadisticas_completas':
        obtenerEstadisticasCompletas($proyectoManager, $proyecto_id);
        break;
        
    case 'datos_dashboard':
        obtenerDatosDashboard($proyectoManager, $proyecto_id);
        break;
        
    case 'progreso_tiempo':
        obtenerProgresoTiempo($proyectoManager, $proyecto_id);
        break;
        
    case 'distribucion_peso':
        obtenerDistribucionPeso($proyectoManager, $proyecto_id);
        break;
        
    case 'analisis_fases':
        obtenerAnalisisFases($proyectoManager, $proyecto_id);
        break;
        
    case 'comparativa_proyectos':
        obtenerComparativaProyectos($proyectoManager);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function obtenerDatosGraficos($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
        $stats_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
        $stats_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
        
        $datos = [
            'distribucio_estados' => [
                'labels' => ['Completadas', 'En Proceso', 'Pendientes'],
                'data' => [
                    intval($stats['completadas']),
                    intval($stats['en_proceso']),
                    intval($stats['pendientes'])
                ],
                'colors' => ['#27ae60', '#f39c12', '#e74c3c']
            ],
            'progreso_tipos' => [
                'labels' => array_column($stats_tipo, 'tipo'),
                'data' => array_map(function($item) {
                    return round(floatval($item['avance_promedio']), 2);
                }, $stats_tipo),
                'colors' => ['#2c3e50', '#3498db', '#f39c12']
            ],
            'progreso_fases' => [
                'labels' => array_column($stats_fase, 'fase_principal'),
                'data' => array_map(function($item) {
                    return round(floatval($item['avance_promedio']), 2);
                }, $stats_fase),
                'pesos' => array_map(function($item) {
                    return round(floatval($item['peso_total']), 4);
                }, $stats_fase)
            ],
            'stats_generales' => $stats
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $datos
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerEstadisticasCompletas($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
        $stats_general = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
        $stats_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
        $stats_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
        $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
        
        // Cálculos adicionales
        $peso_promedio_por_tipo = [];
        foreach ($stats_tipo as $tipo) {
            $peso_promedio_por_tipo[$tipo['tipo']] = [
                'peso_promedio' => round(floatval($tipo['peso_total']) / max(intval($tipo['total']), 1), 4),
                'total_elementos' => intval($tipo['total']),
                'peso_total' => round(floatval($tipo['peso_total']), 4)
            ];
        }
        
        // Análisis de distribución de contratos
        $distribucion_contratos = [];
        $query_contratos = "SELECT contrato, COUNT(*) as cantidad, SUM(peso_actividad) as peso_total 
                           FROM tareas WHERE proyecto_id = ? GROUP BY contrato";
        $stmt = $proyectoManager->conn->prepare($query_contratos);
        $stmt->execute([$proyecto_id]);
        $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contratos as $contrato) {
            $distribucion_contratos[] = [
                'tipo' => $contrato['contrato'],
                'cantidad' => intval($contrato['cantidad']),
                'peso_total' => round(floatval($contrato['peso_total']), 4)
            ];
        }
        
        // Tareas críticas (alto peso, estado pendiente)
        $tareas_criticas = array_filter($tareas, function($tarea) {
            return floatval($tarea['peso_actividad']) > 0.05 && $tarea['estado'] === 'Pendiente';
        });
        
        // Ranking de tareas por peso
        usort($tareas, function($a, $b) {
            return floatval($b['peso_actividad']) <=> floatval($a['peso_actividad']);
        });
        $top_tareas = array_slice($tareas, 0, 10);
        
        $resultado = [
            'proyecto' => $proyecto,
            'resumen' => [
                'total_tareas' => count($tareas),
                'peso_total' => round(floatval($stats_general['peso_total']), 4),
                'progreso_ponderado' => round(floatval($stats_general['avance_promedio']), 2),
                'tareas_criticas' => count($tareas_criticas),
                'fecha_generacion' => date('Y-m-d H:i:s')
            ],
            'estadisticas' => [
                'general' => $stats_general,
                'por_tipo' => $stats_tipo,
                'por_fase' => $stats_fase,
                'cronograma' => $cronograma
            ],
            'analisis' => [
                'peso_promedio_por_tipo' => $peso_promedio_por_tipo,
                'distribucion_contratos' => $distribucion_contratos,
                'tareas_criticas' => array_map(function($tarea) {
                    return [
                        'id' => $tarea['id'],
                        'nombre' => $tarea['nombre'],
                        'tipo' => $tarea['tipo'],
                        'peso' => round(floatval($tarea['peso_actividad']), 4),
                        'fase' => $tarea['fase_principal']
                    ];
                }, $tareas_criticas),
                'top_tareas_peso' => array_map(function($tarea) {
                    return [
                        'nombre' => $tarea['nombre'],
                        'tipo' => $tarea['tipo'],
                        'peso' => round(floatval($tarea['peso_actividad']), 4),
                        'estado' => $tarea['estado'],
                        'progreso' => intval($tarea['porcentaje_avance'])
                    ];
                }, $top_tareas)
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $resultado
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerDatosDashboard($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        
        // Datos para el gráfico de dona
        $grafico_dona = [
            'labels' => ['Completadas', 'En Proceso', 'Pendientes'],
            'datasets' => [[
                'data' => [
                    intval($stats['completadas']),
                    intval($stats['en_proceso']),
                    intval($stats['pendientes'])
                ],
                'backgroundColor' => ['#27ae60', '#f39c12', '#e74c3c'],
                'borderWidth' => 2,
                'borderColor' => '#fff'
            ]]
        ];
        
        // Métricas principales
        $metricas = [
            'total_tareas' => intval($stats['total']),
            'completadas' => intval($stats['completadas']),
            'en_proceso' => intval($stats['en_proceso']),
            'pendientes' => intval($stats['pendientes']),
            'peso_total' => round(floatval($stats['peso_total']), 4),
            'progreso_ponderado' => round(floatval($stats['avance_promedio']), 2),
            'avance_ponderado' => round(floatval($stats['avance_ponderado']), 4)
        ];
        
        // Progreso por día (últimos 30 días)
        $progreso_historico = obtenerProgresoHistorico($proyectoManager->conn, $proyecto_id);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'grafico_dona' => $grafico_dona,
                'metricas' => $metricas,
                'progreso_historico' => $progreso_historico,
                'proyecto' => [
                    'nombre' => $proyecto['nombre'],
                    'estado' => $proyecto['estado'],
                    'progreso_calculado' => round(floatval($proyecto['progreso_calculado']), 2)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerProgresoTiempo($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        // Simular progreso en el tiempo basado en fechas de actualización
        $query = "SELECT DATE(updated_at) as fecha, COUNT(*) as tareas_actualizadas 
                  FROM tareas 
                  WHERE proyecto_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  GROUP BY DATE(updated_at) 
                  ORDER BY fecha";
        
        $stmt = $proyectoManager->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $actividad = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data = [];
        
        foreach ($actividad as $dia) {
            $labels[] = date('d/m', strtotime($dia['fecha']));
            $data[] = intval($dia['tareas_actualizadas']);
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Tareas actualizadas',
                    'data' => $data,
                    'borderColor' => '#3498db',
                    'backgroundColor' => 'rgba(52, 152, 219, 0.1)',
                    'fill' => true,
                    'tension' => 0.4
                ]]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerDistribucionPeso($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $query = "SELECT tipo, fase_principal, peso_actividad, estado 
                  FROM tareas 
                  WHERE proyecto_id = ? AND peso_actividad > 0 
                  ORDER BY peso_actividad DESC";
        
        $stmt = $proyectoManager->conn->prepare($query);
        $stmt->execute([$proyecto_id]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar por rango de peso
        $rangos = [
            'alto' => ['min' => 0.05, 'max' => 1.0, 'count' => 0, 'peso_total' => 0],
            'medio' => ['min' => 0.01, 'max' => 0.05, 'count' => 0, 'peso_total' => 0],
            'bajo' => ['min' => 0.0001, 'max' => 0.01, 'count' => 0, 'peso_total' => 0],
            'minimo' => ['min' => 0, 'max' => 0.0001, 'count' => 0, 'peso_total' => 0]
        ];
        
        foreach ($tareas as $tarea) {
            $peso = floatval($tarea['peso_actividad']);
            
            if ($peso >= 0.05) {
                $rangos['alto']['count']++;
                $rangos['alto']['peso_total'] += $peso;
            } elseif ($peso >= 0.01) {
                $rangos['medio']['count']++;
                $rangos['medio']['peso_total'] += $peso;
            } elseif ($peso >= 0.0001) {
                $rangos['bajo']['count']++;
                $rangos['bajo']['peso_total'] += $peso;
            } else {
                $rangos['minimo']['count']++;
                $rangos['minimo']['peso_total'] += $peso;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'rangos' => $rangos,
                'total_tareas' => count($tareas),
                'distribucion_grafico' => [
                    'labels' => ['Alto (>0.05)', 'Medio (0.01-0.05)', 'Bajo (0.0001-0.01)', 'Mínimo (<0.0001)'],
                    'data' => [
                        $rangos['alto']['count'],
                        $rangos['medio']['count'],
                        $rangos['bajo']['count'],
                        $rangos['minimo']['count']
                    ],
                    'backgroundColor' => ['#e74c3c', '#f39c12', '#3498db', '#95a5a6']
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerAnalisisFases($proyectoManager, $proyecto_id) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
        
        // Análisis detallado por fase
        $analisis_fases = [];
        foreach ($cronograma as $fase) {
            $query_detalle = "SELECT tipo, COUNT(*) as cantidad, SUM(peso_actividad) as peso,
                             AVG(duracion_dias) as duracion_promedio
                             FROM tareas 
                             WHERE proyecto_id = ? AND fase_principal = ?
                             GROUP BY tipo";
            
            $stmt = $proyectoManager->conn->prepare($query_detalle);
            $stmt->execute([$proyecto_id, $fase['fase_principal']]);
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $analisis_fases[] = [
                'fase' => $fase['fase_principal'],
                'progreso' => round(floatval($fase['progreso_fase']), 2),
                'peso_total' => round(floatval($fase['peso_fase']), 4),
                'total_elementos' => intval($fase['total_elementos']),
                'completados' => intval($fase['completados']),
                'detalle_por_tipo' => $detalle,
                'eficiencia' => round((intval($fase['completados']) / max(intval($fase['total_elementos']), 1)) * 100, 2)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $analisis_fases
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerComparativaProyectos($proyectoManager) {
    try {
        $proyectos = $proyectoManager->obtenerProyectos();
        $comparativa = [];
        
        foreach ($proyectos as $proyecto) {
            $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto['id']);
            
            $comparativa[] = [
                'id' => $proyecto['id'],
                'nombre' => $proyecto['nombre'],
                'estado' => $proyecto['estado'],
                'total_tareas' => intval($stats['total']),
                'progreso_ponderado' => round(floatval($stats['avance_promedio']), 2),
                'peso_total' => round(floatval($stats['peso_total']), 4),
                'completadas' => intval($stats['completadas']),
                'pendientes' => intval($stats['pendientes']),
                'eficiencia' => round((intval($stats['completadas']) / max(intval($stats['total']), 1)) * 100, 2)
            ];
        }
        
        // Ordenar por progreso ponderado
        usort($comparativa, function($a, $b) {
            return $b['progreso_ponderado'] <=> $a['progreso_ponderado'];
        });
        
        echo json_encode([
            'success' => true,
            'data' => [
                'proyectos' => $comparativa,
                'resumen' => [
                    'total_proyectos' => count($comparativa),
                    'progreso_promedio' => count($comparativa) > 0 ? 
                        round(array_sum(array_column($comparativa, 'progreso_ponderado')) / count($comparativa), 2) : 0,
                    'peso_total_sistema' => round(array_sum(array_column($comparativa, 'peso_total')), 4)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function obtenerProgresoHistorico($db, $proyecto_id) {
    try {
        // Simular datos históricos basados en las actualizaciones
        $query = "SELECT DATE(updated_at) as fecha, 
                         AVG(porcentaje_avance) as progreso_promedio
                  FROM tareas 
                  WHERE proyecto_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 15 DAY)
                  GROUP BY DATE(updated_at) 
                  ORDER BY fecha";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$proyecto_id]);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data = [];
        
        foreach ($historico as $dia) {
            $labels[] = date('d/m', strtotime($dia['fecha']));
            $data[] = round(floatval($dia['progreso_promedio']), 2);
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
        
    } catch (Exception $e) {
        return [
            'labels' => [],
            'data' => []
        ];
    }
}
?>
