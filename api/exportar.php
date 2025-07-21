<?php
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

$action = $_GET['action'] ?? '';
$formato = $_GET['formato'] ?? 'json';
$proyecto_id = $_GET['proyecto_id'] ?? null;

switch ($action) {
    case 'proyecto':
        exportarProyecto($proyectoManager, $proyecto_id, $formato);
        break;
        
    case 'todos_proyectos':
        exportarTodosProyectos($proyectoManager, $formato);
        break;
        
    case 'respaldo_completo':
        crearRespaldoCompleto($db, $formato);
        break;
        
    case 'tareas_proyecto':
        exportarTareasProyecto($proyectoManager, $proyecto_id, $formato);
        break;
        
    case 'reporte_proyecto':
        exportarReporteProyecto($proyectoManager, $proyecto_id, $formato);
        break;
        
    case 'cronograma_ponderado':
        exportarCronogramaPonderado($proyectoManager, $proyecto_id, $formato);
        break;
        
    case 'estadisticas_peso':
        exportarEstadisticasPeso($proyectoManager, $proyecto_id, $formato);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

function exportarProyecto($proyectoManager, $proyecto_id, $formato) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
        $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
        $stats_por_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
        $stats_por_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
        $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
        
        if (!$proyecto) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
            return;
        }
        
        $datos = [
            'proyecto' => $proyecto,
            'tareas' => $tareas,
            'estadisticas_generales' => $stats,
            'estadisticas_por_tipo' => $stats_por_tipo,
            'estadisticas_por_fase' => $stats_por_fase,
            'cronograma_ponderado' => $cronograma,
            'resumen_pesos' => [
                'peso_total' => $stats['peso_total'],
                'avance_ponderado' => $stats['avance_ponderado'],
                'progreso_ponderado' => $stats['avance_promedio']
            ],
            'metadatos' => [
                'fecha_exportacion' => date('Y-m-d H:i:s'),
                'version_sistema' => '2.0.0-peso-ponderado',
                'total_tareas' => count($tareas),
                'metodo_calculo' => 'peso_ponderado'
            ]
        ];
        
        switch ($formato) {
            case 'json':
                exportarJSON($datos, "proyecto_{$proyecto_id}_" . date('Y-m-d'));
                break;
                
            case 'csv':
                exportarProyectoCSV($proyecto, $tareas);
                break;
                
            case 'excel':
                exportarProyectoExcel($proyecto, $tareas, $stats);
                break;
                
            case 'xml':
                exportarProyectoXML($proyecto, $tareas);
                break;
                
            case 'pdf':
                exportarProyectoPDF($proyecto, $tareas, $stats);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportarTodosProyectos($proyectoManager, $formato) {
    try {
        $proyectos = $proyectoManager->obtenerProyectos();
        $datos_completos = [];
        
        foreach ($proyectos as $proyecto) {
            $tareas = $proyectoManager->obtenerTareasProyecto($proyecto['id']);
            $stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto['id']);
            
            $datos_completos[] = [
                'proyecto' => $proyecto,
                'tareas' => $tareas,
                'estadisticas' => $stats,
                'peso_total' => $stats['peso_total'],
                'progreso_ponderado' => $stats['avance_promedio']
            ];
        }
        
        $exportacion = [
            'proyectos' => $datos_completos,
            'resumen' => [
                'total_proyectos' => count($proyectos),
                'peso_total_sistema' => array_sum(array_column(array_column($datos_completos, 'estadisticas'), 'peso_total')),
                'progreso_promedio_sistema' => array_sum(array_column(array_column($datos_completos, 'estadisticas'), 'avance_promedio')) / max(count($proyectos), 1)
            ],
            'metadatos' => [
                'fecha_exportacion' => date('Y-m-d H:i:s'),
                'version_sistema' => '2.0.0-peso-ponderado',
                'metodo_calculo' => 'peso_ponderado'
            ]
        ];
        
        switch ($formato) {
            case 'json':
                exportarJSON($exportacion, "todos_proyectos_" . date('Y-m-d'));
                break;
                
            case 'csv':
                exportarTodosProyectosCSV($datos_completos);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado para exportación completa']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function crearRespaldoCompleto($db, $formato) {
    try {
        // Obtener estructura de las tablas
        $tablas = ['proyectos', 'tareas'];
        $respaldo = [
            'fecha_respaldo' => date('Y-m-d H:i:s'),
            'version_sistema' => '2.0.0-peso-ponderado',
            'tablas' => [],
            'estructura' => []
        ];
        
        foreach ($tablas as $tabla) {
            // Obtener estructura de la tabla
            $query_estructura = "DESCRIBE $tabla";
            $stmt_estructura = $db->prepare($query_estructura);
            $stmt_estructura->execute();
            $estructura = $stmt_estructura->fetchAll(PDO::FETCH_ASSOC);
            $respaldo['estructura'][$tabla] = $estructura;
            
            // Obtener datos de la tabla
            $query = "SELECT * FROM $tabla";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $respaldo['tablas'][$tabla] = $datos;
        }
        
        switch ($formato) {
            case 'json':
                exportarJSON($respaldo, "respaldo_completo_" . date('Y-m-d_H-i-s'));
                break;
                
            case 'sql':
                exportarRespaldoSQL($db, $tablas);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado para respaldo']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportarTareasProyecto($proyectoManager, $proyecto_id, $formato) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
        
        switch ($formato) {
            case 'csv':
                exportarTareasCSV($tareas, $proyecto['nombre']);
                break;
                
            case 'json':
                exportarJSON([
                    'tareas' => $tareas, 
                    'proyecto' => $proyecto['nombre'],
                    'metadatos' => [
                        'total_tareas' => count($tareas),
                        'peso_total' => array_sum(array_column($tareas, 'peso_actividad')),
                        'fecha_exportacion' => date('Y-m-d H:i:s')
                    ]
                ], "tareas_proyecto_{$proyecto_id}_" . date('Y-m-d'));
                break;
                
            case 'excel':
                exportarTareasExcel($tareas, $proyecto['nombre']);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportarReporteProyecto($proyectoManager, $proyecto_id, $formato) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        $tareas = $proyectoManager->obtenerTareasProyecto($proyecto_id);
        $stats = Utils::generarReporteEstadisticas($proyecto, $tareas);
        
        switch ($formato) {
            case 'html':
                exportarReporteHTML($proyecto, $tareas, $stats);
                break;
                
            case 'pdf':
                exportarReportePDF($proyecto, $tareas, $stats);
                break;
                
            case 'json':
                exportarJSON($stats, "reporte_proyecto_{$proyecto_id}_" . date('Y-m-d'));
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado para reportes']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportarCronogramaPonderado($proyectoManager, $proyecto_id, $formato) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $cronograma = $proyectoManager->obtenerCronogramaPonderado($proyecto_id);
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        
        $datos = [
            'proyecto' => $proyecto['nombre'],
            'cronograma' => $cronograma,
            'metadatos' => [
                'total_fases' => count($cronograma),
                'fecha_exportacion' => date('Y-m-d H:i:s'),
                'metodo_calculo' => 'peso_ponderado'
            ]
        ];
        
        switch ($formato) {
            case 'json':
                exportarJSON($datos, "cronograma_proyecto_{$proyecto_id}_" . date('Y-m-d'));
                break;
                
            case 'csv':
                exportarCronogramaCSV($cronograma, $proyecto['nombre']);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Formato no soportado']);
                break;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function exportarEstadisticasPeso($proyectoManager, $proyecto_id, $formato) {
    if (!$proyecto_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de proyecto requerido']);
        return;
    }
    
    try {
        $stats_general = $proyectoManager->obtenerEstadisticasProyecto($proyecto_id);
        $stats_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_id);
        $stats_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_id);
        $proyecto = $proyectoManager->obtenerProyecto($proyecto_id);
        
        $datos = [
            'proyecto' => $proyecto['nombre'],
            'estadisticas_generales' => $stats_general,
            'por_tipo' => $stats_tipo,
            'por_fase' => $stats_fase,
            'resumen_pesos' => [
                'peso_total' => $stats_general['peso_total'],
                'avance_ponderado' => $stats_general['avance_ponderado'],
                'progreso_porcentaje' => $stats_general['avance_promedio']
            ]
        ];
        
        exportarJSON($datos, "estadisticas_peso_{$proyecto_id}_" . date('Y-m-d'));
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Funciones de exportación específicas

function exportarJSON($datos, $nombre_archivo) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.json"');
    
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function exportarProyectoCSV($proyecto, $tareas) {
    $nombre_archivo = "proyecto_" . Utils::generarSlug($proyecto['nombre']) . "_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Información del proyecto
    fputcsv($output, ['INFORMACIÓN DEL PROYECTO']);
    fputcsv($output, ['Nombre', $proyecto['nombre']]);
    fputcsv($output, ['Descripción', $proyecto['descripcion']]);
    fputcsv($output, ['Cliente', $proyecto['cliente']]);
    fputcsv($output, ['Estado', $proyecto['estado']]);
    fputcsv($output, ['Fecha Inicio', $proyecto['fecha_inicio']]);
    fputcsv($output, ['Fecha Fin Estimada', $proyecto['fecha_fin_estimada']]);
    fputcsv($output, ['Presupuesto', $proyecto['presupuesto']]);
    fputcsv($output, ['Progreso Ponderado', $proyecto['progreso_calculado'] . '%']);
    fputcsv($output, []);
    
    // Tareas con peso ponderado
    fputcsv($output, ['TAREAS DEL PROYECTO CON PESO PONDERADO']);
    fputcsv($output, [
        'ID', 'Nombre', 'Tipo', 'Fase Principal', 'Contrato', 'Peso Actividad', 
        'Estado', 'Progreso (%)', 'Duración (días)', 'Fecha Creación', 'Última Actualización'
    ]);
    
    foreach ($tareas as $tarea) {
        fputcsv($output, [
            $tarea['id'],
            $tarea['nombre'],
            $tarea['tipo'],
            $tarea['fase_principal'] ?? '',
            $tarea['contrato'],
            number_format($tarea['peso_actividad'], 4),
            $tarea['estado'],
            $tarea['porcentaje_avance'],
            $tarea['duracion_dias'],
            $tarea['created_at'],
            $tarea['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportarTareasCSV($tareas, $nombre_proyecto) {
    $nombre_archivo = "tareas_" . Utils::generarSlug($nombre_proyecto) . "_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados con peso ponderado
    fputcsv($output, [
        'ID', 'Nombre', 'Tipo', 'Fase Principal', 'Contrato', 'Peso Actividad', 
        'Estado', 'Progreso (%)', 'Duración (días)', 'Creada', 'Actualizada'
    ]);
    
    foreach ($tareas as $tarea) {
        fputcsv($output, [
            $tarea['id'],
            $tarea['nombre'],
            $tarea['tipo'],
            $tarea['fase_principal'] ?? '',
            $tarea['contrato'],
            number_format($tarea['peso_actividad'], 4),
            $tarea['estado'],
            $tarea['porcentaje_avance'],
            $tarea['duracion_dias'],
            $tarea['created_at'],
            $tarea['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportarTodosProyectosCSV($datos_proyectos) {
    $nombre_archivo = "todos_proyectos_peso_ponderado_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'ID Proyecto', 'Nombre Proyecto', 'Cliente', 'Estado Proyecto', 
        'Peso Total', 'Progreso Ponderado (%)', 'Total Tareas', 'Completadas', 
        'En Proceso', 'Pendientes', 'Avance Ponderado'
    ]);
    
    foreach ($datos_proyectos as $datos) {
        $proyecto = $datos['proyecto'];
        $stats = $datos['estadisticas'];
        
        fputcsv($output, [
            $proyecto['id'],
            $proyecto['nombre'],
            $proyecto['cliente'],
            $proyecto['estado'],
            number_format($stats['peso_total'], 4),
            number_format($stats['avance_promedio'], 2),
            $stats['total'],
            $stats['completadas'],
            $stats['en_proceso'],
            $stats['pendientes'],
            number_format($stats['avance_ponderado'], 4)
        ]);
    }
    
    fclose($output);
    exit;
}

function exportarCronogramaCSV($cronograma, $nombre_proyecto) {
    $nombre_archivo = "cronograma_" . Utils::generarSlug($nombre_proyecto) . "_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, [
        'Fase Principal', 'Peso de Fase', 'Total Elementos', 'Completados', 
        'Duración Promedio', 'Progreso Ponderado (%)'
    ]);
    
    foreach ($cronograma as $fase) {
        fputcsv($output, [
            $fase['fase_principal'],
            number_format($fase['peso_fase'], 4),
            $fase['total_elementos'],
            $fase['completados'],
            number_format($fase['duracion_promedio'], 1),
            number_format($fase['progreso_fase'], 2)
        ]);
    }
    
    fclose($output);
    exit;
}

function exportarProyectoXML($proyecto, $tareas) {
    $nombre_archivo = "proyecto_" . $proyecto['id'] . "_" . date('Y-m-d') . ".xml";
    
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><proyecto></proyecto>');
    
    // Información del proyecto
    $xml->addChild('id', $proyecto['id']);
    $xml->addChild('nombre', htmlspecialchars($proyecto['nombre']));
    $xml->addChild('descripcion', htmlspecialchars($proyecto['descripcion']));
    $xml->addChild('cliente', htmlspecialchars($proyecto['cliente']));
    $xml->addChild('estado', $proyecto['estado']);
    $xml->addChild('fecha_inicio', $proyecto['fecha_inicio']);
    $xml->addChild('fecha_fin_estimada', $proyecto['fecha_fin_estimada']);
    $xml->addChild('presupuesto', $proyecto['presupuesto']);
    $xml->addChild('progreso_ponderado', $proyecto['progreso_calculado']);
    
    // Tareas con peso ponderado
    $tareas_xml = $xml->addChild('tareas');
    foreach ($tareas as $tarea) {
        $tarea_xml = $tareas_xml->addChild('tarea');
        $tarea_xml->addChild('id', $tarea['id']);
        $tarea_xml->addChild('nombre', htmlspecialchars($tarea['nombre']));
        $tarea_xml->addChild('tipo', $tarea['tipo']);
        $tarea_xml->addChild('fase_principal', htmlspecialchars($tarea['fase_principal'] ?? ''));
        $tarea_xml->addChild('contrato', $tarea['contrato']);
        $tarea_xml->addChild('peso_actividad', $tarea['peso_actividad']);
        $tarea_xml->addChild('estado', $tarea['estado']);
        $tarea_xml->addChild('porcentaje_avance', $tarea['porcentaje_avance']);
        $tarea_xml->addChild('duracion_dias', $tarea['duracion_dias']);
    }
    
    echo $xml->asXML();
    exit;
}

function exportarRespaldoSQL($db, $tablas) {
    $nombre_archivo = "respaldo_peso_ponderado_" . date('Y-m-d_H-i-s') . ".sql";
    
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    echo "-- Respaldo de Base de Datos con Peso Ponderado\n";
    echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Sistema de Gestión de Proyectos v2.0.0\n";
    echo "-- Incluye campos: peso_actividad, contrato, fase_principal\n\n";
    
    echo "SET FOREIGN_KEY_CHECKS = 0;\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET AUTOCOMMIT = 0;\n";
    echo "START TRANSACTION;\n";
    echo "SET time_zone = \"+00:00\";\n\n";
    
    foreach ($tablas as $tabla) {
        // Estructura de la tabla
        $query = "SHOW CREATE TABLE $tabla";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $estructura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "-- Estructura de tabla: $tabla\n";
        echo "DROP TABLE IF EXISTS `$tabla`;\n";
        echo $estructura['Create Table'] . ";\n\n";
        
        // Datos de la tabla
        $query = "SELECT * FROM $tabla";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($datos)) {
            echo "-- Datos de tabla: $tabla\n";
            
            // Obtener nombres de columnas
            $columnas = array_keys($datos[0]);
            echo "INSERT INTO `$tabla` (`" . implode("`, `", $columnas) . "`) VALUES\n";
            
            $valores = [];
            foreach ($datos as $fila) {
                $valor = "(";
                $campos = [];
                foreach ($fila as $campo) {
                    if (is_null($campo)) {
                        $campos[] = "NULL";
                    } else {
                        $campos[] = "'" . addslashes($campo) . "'";
                    }
                }
                $valor .= implode(", ", $campos) . ")";
                $valores[] = $valor;
            }
            
            echo implode(",\n", $valores) . ";\n\n";
        }
    }
    
    echo "COMMIT;\n";
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit;
}

function exportarReporteHTML($proyecto, $tareas, $stats) {
    $nombre_archivo = "reporte_" . Utils::generarSlug($proyecto['nombre']) . "_" . date('Y-m-d') . ".html";
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $peso_total = array_sum(array_column($tareas, 'peso_actividad'));
    $progreso_ponderado = Utils::calcularProgresoProyectoPonderado($tareas);
    
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Ponderado - ' . htmlspecialchars($proyecto['nombre']) . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
        }
        .header { 
            text-align: center; 
            border-bottom: 3px solid #2c3e50; 
            padding-bottom: 20px; 
            margin-bottom: 30px;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        .info-table th, .info-table td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        .info-table th { 
            background-color: #f8f9fa; 
            font-weight: bold;
        }
        .stats { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0; 
        }
        .stat-box { 
            text-align: center; 
            padding: 20px; 
            border: 2px solid #dee2e6; 
            border-radius: 8px; 
            background-color: #f8f9fa;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .progress-bar { 
            width: 100%; 
            height: 25px; 
            background-color: #e9ecef; 
            border-radius: 12px; 
            overflow: hidden; 
            margin: 10px 0;
        }
        .progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #28a745, #20c997); 
            transition: width 0.3s ease;
        }
        .peso-badge {
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .fase-section {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Proyecto con Peso Ponderado</h1>
        <h2>' . htmlspecialchars($proyecto['nombre']) . '</h2>
        <p><strong>Generado el:</strong> ' . date('d/m/Y H:i:s') . '</p>
        <p><strong>Sistema:</strong> Gestión de Proyectos v2.0.0 - Peso Ponderado</p>
    </div>
    
    <h3>📊 Información del Proyecto</h3>
    <table class="info-table">
        <tr><th>Cliente</th><td>' . htmlspecialchars($proyecto['cliente'] ?? 'No especificado') . '</td></tr>
        <tr><th>Estado</th><td>' . $proyecto['estado'] . '</td></tr>
        <tr><th>Fecha Inicio</th><td>' . ($proyecto['fecha_inicio'] ? Utils::formatearFecha($proyecto['fecha_inicio']) : 'No definida') . '</td></tr>
        <tr><th>Fecha Fin Estimada</th><td>' . ($proyecto['fecha_fin_estimada'] ? Utils::formatearFecha($proyecto['fecha_fin_estimada']) : 'No definida') . '</td></tr>
        <tr><th>Presupuesto</th><td>' . Utils::formatearMoneda($proyecto['presupuesto']) . '</td></tr>
        <tr><th style="background-color: #e3f2fd;">Peso Total del Proyecto</th><td><span class="peso-badge">' . number_format($peso_total, 4) . '</span></td></tr>
        <tr><th style="background-color: #e8f5e8;">Progreso Ponderado</th><td><strong>' . number_format($progreso_ponderado, 2) . '%</strong></td></tr>
    </table>
    
    <h3>📈 Estadísticas Generales</h3>
    <div class="stats">
        <div class="stat-box">
            <div class="stat-number">' . $stats['total_tareas'] . '</div>
            <p>Total Tareas</p>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . $stats['completadas'] . '</div>
            <p>Completadas</p>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . $stats['en_proceso'] . '</div>
            <p>En Proceso</p>
        </div>
        <div class="stat-box">
            <div class="stat-number">' . $stats['pendientes'] . '</div>
            <p>Pendientes</p>
        </div>
    </div>
    
    <h3>🎯 Progreso Ponderado General</h3>
    <div class="progress-bar">
        <div class="progress-fill" style="width: ' . $progreso_ponderado . '%"></div>
    </div>
    <p style="text-align: center;"><strong>Progreso: ' . number_format($progreso_ponderado, 2) . '%</strong> 
    (basado en peso de actividades)</p>';

    // Agrupar tareas por fase
    $tareas_por_fase = [];
    foreach ($tareas as $tarea) {
        $fase = $tarea['fase_principal'] ?? 'Sin fase definida';
        if (!isset($tareas_por_fase[$fase])) {
            $tareas_por_fase[$fase] = [];
        }
        $tareas_por_fase[$fase][] = $tarea;
    }

    $html .= '<h3>📋 Detalle por Fases Principales</h3>';
    
    foreach ($tareas_por_fase as $fase => $tareas_fase) {
        $peso_fase = array_sum(array_column($tareas_fase, 'peso_actividad'));
        $completadas_fase = count(array_filter($tareas_fase, function($t) { return $t['estado'] === 'Listo'; }));
        $progreso_fase = Utils::calcularProgresoProyectoPonderado($tareas_fase);
        
        $html .= '<div class="fase-section">
            <h4>' . htmlspecialchars($fase) . '</h4>
            <p><strong>Peso de la fase:</strong> <span class="peso-badge">' . number_format($peso_fase, 4) . '</span> | 
            <strong>Tareas:</strong> ' . count($tareas_fase) . ' | 
            <strong>Completadas:</strong> ' . $completadas_fase . ' | 
            <strong>Progreso:</strong> ' . number_format($progreso_fase, 1) . '%</p>
            
            <div class="progress-bar" style="height: 15px;">
                <div class="progress-fill" style="width: ' . $progreso_fase . '%"></div>
            </div>
        </div>';
    }
    
    $html .= '<h3>📝 Detalle de Todas las Tareas</h3>
    <table class="info-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Fase</th>
                <th>Peso</th>
                <th>Contrato</th>
                <th>Estado</th>
                <th>Progreso</th>
                <th>Duración</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($tareas as $tarea) {
        $html .= '<tr>
            <td>' . htmlspecialchars($tarea['nombre']) . '</td>
            <td>' . $tarea['tipo'] . '</td>
            <td><small>' . htmlspecialchars($tarea['fase_principal'] ?? 'Sin fase') . '</small></td>
            <td><span class="peso-badge">' . number_format($tarea['peso_actividad'], 4) . '</span></td>
            <td>' . $tarea['contrato'] . '</td>
            <td>' . $tarea['estado'] . '</td>
            <td>' . $tarea['porcentaje_avance'] . '%</td>
            <td>' . $tarea['duracion_dias'] . ' días</td>
        </tr>';
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión de Proyectos</p>
        <p>Metodología: Cálculo de progreso basado en peso ponderado de actividades</p>
        <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';
    
    echo $html;
    exit;
}

// Funciones PDF simplificadas (redirigen a HTML por ahora)
function exportarProyectoPDF($proyecto, $tareas, $stats) {
    exportarReporteHTML($proyecto, $tareas, $stats);
}

function exportarReportePDF($proyecto, $tareas, $stats) {
    exportarReporteHTML($proyecto, $tareas, $stats);
}
?>
