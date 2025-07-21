<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/utils.php';

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
        
        if (!$proyecto) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado']);
            return;
        }
        
        $datos = [
            'proyecto' => $proyecto,
            'tareas' => $tareas,
            'estadisticas' => $stats,
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
        
        switch ($formato) {
            case 'json':
                exportarJSON($datos, "proyecto_{$proyecto_id}_" . date('Y-m-d'));
                break;
                
            case 'csv':
                exportarProyectoCSV($proyecto, $tareas);
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
                'estadisticas' => $stats
            ];
        }
        
        $exportacion = [
            'proyectos' => $datos_completos,
            'total_proyectos' => count($proyectos),
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'version' => '1.0'
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
            'version' => '1.0',
            'tablas' => []
        ];
        
        foreach ($tablas as $tabla) {
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
                exportarJSON(['tareas' => $tareas, 'proyecto' => $proyecto['nombre']], 
                           "tareas_proyecto_{$proyecto_id}_" . date('Y-m-d'));
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
    fputcsv($output, []);
    
    // Tareas
    fputcsv($output, ['TAREAS DEL PROYECTO']);
    fputcsv($output, ['ID', 'Nombre', 'Tipo', 'Estado', 'Progreso (%)', 'Duración (días)', 'Fecha Inicio', 'Fecha Fin', 'Creada', 'Actualizada']);
    
    foreach ($tareas as $tarea) {
        fputcsv($output, [
            $tarea['id'],
            $tarea['nombre'],
            $tarea['tipo'],
            $tarea['estado'],
            $tarea['porcentaje_avance'],
            $tarea['duracion_dias'],
            $tarea['fecha_inicio'],
            $tarea['fecha_fin'],
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
    
    // Encabezados
    fputcsv($output, ['ID', 'Nombre', 'Tipo', 'Estado', 'Progreso (%)', 'Duración (días)', 'Fecha Inicio', 'Fecha Fin', 'Contrato', 'Peso', 'Creada', 'Actualizada']);
    
    foreach ($tareas as $tarea) {
        fputcsv($output, [
            $tarea['id'],
            $tarea['nombre'],
            $tarea['tipo'],
            $tarea['estado'],
            $tarea['porcentaje_avance'],
            $tarea['duracion_dias'],
            $tarea['fecha_inicio'],
            $tarea['fecha_fin'],
            $tarea['contrato'],
            $tarea['peso_actividad'],
            $tarea['created_at'],
            $tarea['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportarTodosProyectosCSV($datos_proyectos) {
    $nombre_archivo = "todos_proyectos_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, ['ID Proyecto', 'Nombre Proyecto', 'Cliente', 'Estado Proyecto', 'Progreso (%)', 'Total Tareas', 'Completadas', 'En Proceso', 'Pendientes']);
    
    foreach ($datos_proyectos as $datos) {
        $proyecto = $datos['proyecto'];
        $stats = $datos['estadisticas'];
        
        fputcsv($output, [
            $proyecto['id'],
            $proyecto['nombre'],
            $proyecto['cliente'],
            $proyecto['estado'],
            number_format($stats['avance_promedio'], 2),
            $stats['total'],
            $stats['completadas'],
            $stats['en_proceso'],
            $stats['pendientes']
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
    
    // Tareas
    $tareas_xml = $xml->addChild('tareas');
    foreach ($tareas as $tarea) {
        $tarea_xml = $tareas_xml->addChild('tarea');
        $tarea_xml->addChild('id', $tarea['id']);
        $tarea_xml->addChild('nombre', htmlspecialchars($tarea['nombre']));
        $tarea_xml->addChild('tipo', $tarea['tipo']);
        $tarea_xml->addChild('estado', $tarea['estado']);
        $tarea_xml->addChild('porcentaje_avance', $tarea['porcentaje_avance']);
        $tarea_xml->addChild('duracion_dias', $tarea['duracion_dias']);
        $tarea_xml->addChild('fecha_inicio', $tarea['fecha_inicio']);
        $tarea_xml->addChild('fecha_fin', $tarea['fecha_fin']);
    }
    
    echo $xml->asXML();
    exit;
}

function exportarRespaldoSQL($db, $tablas) {
    $nombre_archivo = "respaldo_sql_" . date('Y-m-d_H-i-s') . ".sql";
    
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    echo "-- Respaldo de Base de Datos\n";
    echo "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Sistema de Gestión de Proyectos\n\n";
    
    echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
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
            echo "INSERT INTO `$tabla` VALUES\n";
            
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
    
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
    exit;
}

function exportarReporteHTML($proyecto, $tareas, $stats) {
    $nombre_archivo = "reporte_" . Utils::generarSlug($proyecto['nombre']) . "_" . date('Y-m-d') . ".html";
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
    
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Proyecto - ' . htmlspecialchars($proyecto['nombre']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table th, .info-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .info-table th { background-color: #f2f2f2; }
        .stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .progress-bar { width: 100%; height: 20px; background-color: #f0f0f0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background-color: #28a745; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Proyecto</h1>
        <h2>' . htmlspecialchars($proyecto['nombre']) . '</h2>
        <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
    </div>
    
    <h3>Información del Proyecto</h3>
    <table class="info-table">
        <tr><th>Cliente</th><td>' . htmlspecialchars($proyecto['cliente'] ?? 'No especificado') . '</td></tr>
        <tr><th>Estado</th><td>' . $proyecto['estado'] . '</td></tr>
        <tr><th>Fecha Inicio</th><td>' . ($proyecto['fecha_inicio'] ? Utils::formatearFecha($proyecto['fecha_inicio']) : 'No definida') . '</td></tr>
        <tr><th>Fecha Fin Estimada</th><td>' . ($proyecto['fecha_fin_estimada'] ? Utils::formatearFecha($proyecto['fecha_fin_estimada']) : 'No definida') . '</td></tr>
        <tr><th>Presupuesto</th><td>' . Utils::formatearMoneda($proyecto['presupuesto']) . '</td></tr>
    </table>
    
    <h3>Estadísticas</h3>
    <div class="stats">
        <div class="stat-box">
            <h4>' . $stats['total_tareas'] . '</h4>
            <p>Total Tareas</p>
        </div>
        <div class="stat-box">
            <h4>' . $stats['completadas'] . '</h4>
            <p>Completadas</p>
        </div>
        <div class="stat-box">
            <h4>' . $stats['en_proceso'] . '</h4>
            <p>En Proceso</p>
        </div>
        <div class="stat-box">
            <h4>' . $stats['pendientes'] . '</h4>
            <p>Pendientes</p>
        </div>
    </div>
    
    <h3>Progreso General</h3>
    <div class="progress-bar">
        <div class="progress-fill" style="width: ' . $stats['progreso_promedio'] . '%"></div>
    </div>
    <p>Progreso: ' . number_format($stats['progreso_promedio'], 1) . '%</p>
    
    <h3>Detalle de Tareas</h3>
    <table class="info-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
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
            <td>' . $tarea['estado'] . '</td>
            <td>' . $tarea['porcentaje_avance'] . '%</td>
            <td>' . $tarea['duracion_dias'] . ' días</td>
        </tr>';
    }
    
    $html .= '</tbody>
    </table>
</body>
</html>';
    
    echo $html;
    exit;
}

// Nota: Para exportar PDF se necesitaría una librería como TCPDF o DOMPDF
function exportarProyectoPDF($proyecto, $tareas, $stats) {
    // Por simplicidad, redirigir al HTML por ahora
    exportarReporteHTML($proyecto, $tareas, $stats);
}

function exportarReportePDF($proyecto, $tareas, $stats) {
    // Por simplicidad, redirigir al HTML por ahora
    exportarReporteHTML($proyecto, $tareas, $stats);
}
?>
