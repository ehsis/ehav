<?php
/**
 * Utilidades y funciones de validación para el Sistema de Gestión de Proyectos
 * Actualizado para manejar peso de actividad y cálculos ponderados
 */

class Validator {
    
    /**
     * Validar datos de proyecto
     */
    public static function validarProyecto($datos) {
        $errores = [];
        
        // Nombre es requerido
        if (empty($datos['nombre']) || strlen(trim($datos['nombre'])) < 3) {
            $errores[] = 'El nombre del proyecto debe tener al menos 3 caracteres';
        }
        
        // Validar fechas si están presentes
        if (!empty($datos['fecha_inicio'])) {
            if (!self::validarFecha($datos['fecha_inicio'])) {
                $errores[] = 'La fecha de inicio no es válida';
            }
        }
        
        if (!empty($datos['fecha_fin_estimada'])) {
            if (!self::validarFecha($datos['fecha_fin_estimada'])) {
                $errores[] = 'La fecha de fin estimada no es válida';
            }
            
            // La fecha de fin debe ser posterior a la de inicio
            if (!empty($datos['fecha_inicio']) && $datos['fecha_fin_estimada'] <= $datos['fecha_inicio']) {
                $errores[] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }
        
        // Validar presupuesto
        if (!empty($datos['presupuesto'])) {
            if (!is_numeric($datos['presupuesto']) || $datos['presupuesto'] < 0) {
                $errores[] = 'El presupuesto debe ser un número positivo';
            }
        }
        
        // Validar estado
        $estados_validos = ['Activo', 'Pausado', 'Terminado', 'Cancelado'];
        if (!empty($datos['estado']) && !in_array($datos['estado'], $estados_validos)) {
            $errores[] = 'El estado del proyecto no es válido';
        }
        
        return $errores;
    }
    
    /**
     * Validar datos de tarea con peso de actividad
     */
    public static function validarTarea($datos) {
        $errores = [];
        
        // Nombre es requerido
        if (empty($datos['nombre']) || strlen(trim($datos['nombre'])) < 2) {
            $errores[] = 'El nombre de la tarea debe tener al menos 2 caracteres';
        }
        
        // Tipo debe ser válido
        $tipos_validos = ['Fase', 'Actividad', 'Tarea'];
        if (empty($datos['tipo']) || !in_array($datos['tipo'], $tipos_validos)) {
            $errores[] = 'El tipo de tarea no es válido';
        }
        
        // Duración debe ser positiva
        if (!empty($datos['duracion_dias'])) {
            if (!is_numeric($datos['duracion_dias']) || $datos['duracion_dias'] < 0) {
                $errores[] = 'La duración debe ser un número positivo';
            }
        }
        
        // Estado debe ser válido
        $estados_validos = ['Pendiente', 'En Proceso', 'Listo'];
        if (!empty($datos['estado']) && !in_array($datos['estado'], $estados_validos)) {
            $errores[] = 'El estado de la tarea no es válido';
        }
        
        // Porcentaje debe estar entre 0 y 100
        if (isset($datos['porcentaje_avance'])) {
            if (!is_numeric($datos['porcentaje_avance']) || 
                $datos['porcentaje_avance'] < 0 || 
                $datos['porcentaje_avance'] > 100) {
                $errores[] = 'El porcentaje de avance debe estar entre 0 y 100';
            }
        }
        
        // Validar peso de actividad
        if (isset($datos['peso_actividad'])) {
            if (!is_numeric($datos['peso_actividad']) || 
                $datos['peso_actividad'] < 0 || 
                $datos['peso_actividad'] > 1) {
                $errores[] = 'El peso de actividad debe estar entre 0.0000 y 1.0000';
            }
        }
        
        // Validar tipo de contrato
        $contratos_validos = ['Normal', 'Contrato Clave'];
        if (!empty($datos['contrato']) && !in_array($datos['contrato'], $contratos_validos)) {
            $errores[] = 'El tipo de contrato no es válido';
        }
        
        // Proyecto ID es requerido
        if (empty($datos['proyecto_id']) || !is_numeric($datos['proyecto_id'])) {
            $errores[] = 'ID de proyecto requerido';
        }
        
        return $errores;
    }
    
    /**
     * Validar formato de fecha
     */
    public static function validarFecha($fecha) {
        if (empty($fecha)) return true; // Fechas opcionales
        
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
    
    /**
     * Limpiar datos de entrada
     */
    public static function limpiarDatos($datos) {
        if (is_array($datos)) {
            return array_map([self::class, 'limpiarDatos'], $datos);
        }
        
        return trim(htmlspecialchars($datos, ENT_QUOTES, 'UTF-8'));
    }
    
    /**
     * Validar entrada de usuario para prevenir XSS y SQL injection
     */
    public static function validarEntrada($input, $tipo = 'string') {
        switch ($tipo) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'date':
                return self::validarFecha($input) ? $input : false;
            case 'peso':
                $peso = filter_var($input, FILTER_VALIDATE_FLOAT);
                return ($peso !== false && $peso >= 0 && $peso <= 1) ? $peso : false;
            default:
                return self::limpiarDatos($input);
        }
    }
    
    /**
     * Validar consistencia de pesos en un proyecto
     */
    public static function validarPesosProyecto($tareas) {
        $errores = [];
        $peso_total = 0;
        $pesos_por_fase = [];
        
        foreach ($tareas as $tarea) {
            $peso = floatval($tarea['peso_actividad']);
            $peso_total += $peso;
            
            $fase = $tarea['fase_principal'] ?? 'Sin fase';
            if (!isset($pesos_por_fase[$fase])) {
                $pesos_por_fase[$fase] = 0;
            }
            $pesos_por_fase[$fase] += $peso;
        }
        
        // Advertencia si el peso total se desvía mucho de 1.0
        if ($peso_total > 1.2) {
            $errores[] = "El peso total ({$peso_total}) es mayor a 1.2, puede indicar sobreponderación";
        }
        
        if ($peso_total < 0.8 && count($tareas) > 5) {
            $errores[] = "El peso total ({$peso_total}) es menor a 0.8, las tareas pueden estar subponderadas";
        }
        
        return [
            'errores' => $errores,
            'peso_total' => $peso_total,
            'pesos_por_fase' => $pesos_por_fase
        ];
    }
}

class Utils {
    
    /**
     * Formatear fecha para mostrar
     */
    public static function formatearFecha($fecha, $formato = 'd/m/Y') {
        if (empty($fecha)) return 'No definida';
        
        try {
            $dt = new DateTime($fecha);
            return $dt->format($formato);
        } catch (Exception $e) {
            return 'Fecha inválida';
        }
    }
    
    /**
     * Formatear número con separadores de miles
     */
    public static function formatearNumero($numero, $decimales = 2) {
        if (!is_numeric($numero)) return '0';
        return number_format($numero, $decimales, ',', '.');
    }
    
    /**
     * Formatear moneda
     */
    public static function formatearMoneda($cantidad, $moneda = '₡') {
        return $moneda . ' ' . self::formatearNumero($cantidad, 2);
    }
    
    /**
     * Formatear peso de actividad
     */
    public static function formatearPeso($peso, $decimales = 4) {
        return number_format(floatval($peso), $decimales, '.', '');
    }
    
    /**
     * Formatear porcentaje
     */
    public static function formatearPorcentaje($porcentaje, $decimales = 1) {
        return number_format(floatval($porcentaje), $decimales) . '%';
    }
    
    /**
     * Calcular días entre fechas
     */
    public static function diasEntreFechas($fecha_inicio, $fecha_fin) {
        if (empty($fecha_inicio) || empty($fecha_fin)) return 0;
        
        try {
            $inicio = new DateTime($fecha_inicio);
            $fin = new DateTime($fecha_fin);
            $diff = $inicio->diff($fin);
            return $diff->days;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Generar color basado en estado
     */
    public static function colorPorEstado($estado) {
        $colores = [
            'Pendiente' => '#dc3545',    // Rojo
            'En Proceso' => '#ffc107',   // Amarillo
            'Listo' => '#28a745',        // Verde
            'Activo' => '#007bff',       // Azul
            'Pausado' => '#6c757d',      // Gris
            'Terminado' => '#28a745',    // Verde
            'Cancelado' => '#dc3545'     // Rojo
        ];
        
        return $colores[$estado] ?? '#6c757d';
    }
    
    /**
     * Generar color basado en tipo
     */
    public static function colorPorTipo($tipo) {
        $colores = [
            'Fase' => '#2c3e50',         // Azul oscuro
            'Actividad' => '#3498db',    // Azul
            'Tarea' => '#f39c12'         // Naranja
        ];
        
        return $colores[$tipo] ?? '#6c757d';
    }
    
    /**
     * Calcular porcentaje de progreso del proyecto usando peso ponderado
     */
    public static function calcularProgresoProyectoPonderado($tareas) {
        if (empty($tareas)) return 0;
        
        $peso_total = 0;
        $avance_ponderado = 0;
        
        foreach ($tareas as $tarea) {
            $peso = floatval($tarea['peso_actividad']);
            $peso_total += $peso;
            
            if ($tarea['estado'] === 'Listo') {
                $avance_ponderado += $peso;
            } elseif ($tarea['estado'] === 'En Proceso') {
                $avance_ponderado += $peso * (floatval($tarea['porcentaje_avance']) / 100);
            }
        }
        
        return $peso_total > 0 ? ($avance_ponderado / $peso_total) * 100 : 0;
    }
    
    /**
     * Calcular progreso tradicional (método anterior)
     */
    public static function calcularProgresoProyecto($tareas) {
        if (empty($tareas)) return 0;
        
        $total_progreso = 0;
        $total_tareas = count($tareas);
        
        foreach ($tareas as $tarea) {
            $total_progreso += floatval($tarea['porcentaje_avance']);
        }
        
        return round($total_progreso / $total_tareas, 2);
    }
    
    /**
     * Generar slug/ID amigable desde texto
     */
    public static function generarSlug($texto) {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
        $texto = preg_replace('/[\s-]+/', '-', $texto);
        return trim($texto, '-');
    }
    
    /**
     * Obtener días laborales entre fechas (excluyendo fines de semana)
     */
    public static function diasLaborales($fecha_inicio, $fecha_fin) {
        if (empty($fecha_inicio) || empty($fecha_fin)) return 0;
        
        try {
            $inicio = new DateTime($fecha_inicio);
            $fin = new DateTime($fecha_fin);
            $dias = 0;
            
            while ($inicio <= $fin) {
                $dia_semana = $inicio->format('w');
                if ($dia_semana != 0 && $dia_semana != 6) { // No domingo ni sábado
                    $dias++;
                }
                $inicio->add(new DateInterval('P1D'));
            }
            
            return $dias;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Generar reporte de estadísticas con peso ponderado
     */
    public static function generarReporteEstadisticas($proyecto, $tareas) {
        $stats = [
            'proyecto' => $proyecto,
            'total_tareas' => count($tareas),
            'fases' => 0,
            'actividades' => 0,
            'tareas_simples' => 0,
            'completadas' => 0,
            'en_proceso' => 0,
            'pendientes' => 0,
            'progreso_promedio' => 0,
            'progreso_ponderado' => 0,
            'peso_total' => 0,
            'avance_ponderado_total' => 0,
            'duracion_total' => 0,
            'contratos' => [
                'normal' => 0,
                'clave' => 0
            ],
            'fases_principales' => [],
            'fechas' => [
                'inicio' => null,
                'fin_estimada' => null,
                'primera_tarea' => null,
                'ultima_tarea' => null
            ]
        ];
        
        if (empty($tareas)) return $stats;
        
        $total_progreso = 0;
        $peso_total = 0;
        $avance_ponderado = 0;
        $fechas_inicio = [];
        $fechas_fin = [];
        $fases_info = [];
        
        foreach ($tareas as $tarea) {
            // Contar por tipo
            switch ($tarea['tipo']) {
                case 'Fase':
                    $stats['fases']++;
                    break;
                case 'Actividad':
                    $stats['actividades']++;
                    break;
                case 'Tarea':
                    $stats['tareas_simples']++;
                    break;
            }
            
            // Contar por estado
            switch ($tarea['estado']) {
                case 'Listo':
                    $stats['completadas']++;
                    break;
                case 'En Proceso':
                    $stats['en_proceso']++;
                    break;
                case 'Pendiente':
                    $stats['pendientes']++;
                    break;
            }
            
            // Contar por contrato
            if ($tarea['contrato'] === 'Contrato Clave') {
                $stats['contratos']['clave']++;
            } else {
                $stats['contratos']['normal']++;
            }
            
            // Cálculos ponderados
            $peso = floatval($tarea['peso_actividad']);
            $peso_total += $peso;
            
            if ($tarea['estado'] === 'Listo') {
                $avance_ponderado += $peso;
            } elseif ($tarea['estado'] === 'En Proceso') {
                $avance_ponderado += $peso * (floatval($tarea['porcentaje_avance']) / 100);
            }
            
            $total_progreso += floatval($tarea['porcentaje_avance']);
            $stats['duracion_total'] += intval($tarea['duracion_dias']);
            
            // Agrupar por fase principal
            $fase = $tarea['fase_principal'] ?? 'Sin fase';
            if (!isset($fases_info[$fase])) {
                $fases_info[$fase] = [
                    'nombre' => $fase,
                    'total_tareas' => 0,
                    'peso_total' => 0,
                    'completadas' => 0,
                    'progreso' => 0
                ];
            }
            $fases_info[$fase]['total_tareas']++;
            $fases_info[$fase]['peso_total'] += $peso;
            if ($tarea['estado'] === 'Listo') {
                $fases_info[$fase]['completadas']++;
            }
            
            // Recopilar fechas
            if (!empty($tarea['fecha_inicio'])) {
                $fechas_inicio[] = $tarea['fecha_inicio'];
            }
            if (!empty($tarea['fecha_fin'])) {
                $fechas_fin[] = $tarea['fecha_fin'];
            }
        }
        
        // Calcular progresos
        $stats['progreso_promedio'] = count($tareas) > 0 ? round($total_progreso / count($tareas), 2) : 0;
        $stats['progreso_ponderado'] = $peso_total > 0 ? round(($avance_ponderado / $peso_total) * 100, 2) : 0;
        $stats['peso_total'] = $peso_total;
        $stats['avance_ponderado_total'] = $avance_ponderado;
        
        // Calcular progreso por fase
        foreach ($fases_info as &$fase_info) {
            if ($fase_info['peso_total'] > 0) {
                $fase_info['progreso'] = round(($fase_info['completadas'] / $fase_info['total_tareas']) * 100, 1);
            }
        }
        $stats['fases_principales'] = array_values($fases_info);
        
        // Determinar fechas del proyecto
        if (!empty($fechas_inicio)) {
            sort($fechas_inicio);
            $stats['fechas']['primera_tarea'] = $fechas_inicio[0];
        }
        
        if (!empty($fechas_fin)) {
            sort($fechas_fin);
            $stats['fechas']['ultima_tarea'] = end($fechas_fin);
        }
        
        $stats['fechas']['inicio'] = $proyecto['fecha_inicio'];
        $stats['fechas']['fin_estimada'] = $proyecto['fecha_fin_estimada'];
        
        return $stats;
    }
    
    /**
     * Exportar datos a CSV con peso ponderado
     */
    public static function exportarCSV($datos, $nombre_archivo = 'exportacion.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($datos)) {
            // Encabezados
            fputcsv($output, array_keys($datos[0]));
            
            // Datos
            foreach ($datos as $fila) {
                fputcsv($output, $fila);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Distribuir peso automáticamente entre tareas
     */
    public static function distribuirPesoAutomatico($tareas, $peso_total = 1.0, $metodo = 'equitativo') {
        if (empty($tareas)) return $tareas;
        
        switch ($metodo) {
            case 'por_tipo':
                return self::distribuirPesoPorTipo($tareas, $peso_total);
            case 'por_duracion':
                return self::distribuirPesoPorDuracion($tareas, $peso_total);
            case 'equitativo':
            default:
                return self::distribuirPesoEquitativo($tareas, $peso_total);
        }
    }
    
    /**
     * Distribuir peso equitativamente
     */
    private static function distribuirPesoEquitativo($tareas, $peso_total) {
        $peso_por_tarea = $peso_total / count($tareas);
        
        foreach ($tareas as &$tarea) {
            $tarea['peso_actividad'] = $peso_por_tarea;
        }
        
        return $tareas;
    }
    
    /**
     * Distribuir peso por tipo (Fase > Actividad > Tarea)
     */
    private static function distribuirPesoPorTipo($tareas, $peso_total) {
        $pesos_tipo = [
            'Fase' => 0.5,      // 50% del peso total
            'Actividad' => 0.3,  // 30% del peso total
            'Tarea' => 0.2       // 20% del peso total
        ];
        
        $conteo_tipos = ['Fase' => 0, 'Actividad' => 0, 'Tarea' => 0];
        
        // Contar tareas por tipo
        foreach ($tareas as $tarea) {
            $conteo_tipos[$tarea['tipo']]++;
        }
        
        // Asignar pesos
        foreach ($tareas as &$tarea) {
            $tipo = $tarea['tipo'];
            $peso_tipo_total = $peso_total * $pesos_tipo[$tipo];
            $tarea['peso_actividad'] = $conteo_tipos[$tipo] > 0 ? $peso_tipo_total / $conteo_tipos[$tipo] : 0;
        }
        
        return $tareas;
    }
    
    /**
     * Distribuir peso por duración
     */
    private static function distribuirPesoPorDuracion($tareas, $peso_total) {
        $duracion_total = array_sum(array_column($tareas, 'duracion_dias'));
        
        if ($duracion_total == 0) {
            return self::distribuirPesoEquitativo($tareas, $peso_total);
        }
        
        foreach ($tareas as &$tarea) {
            $proporcion = intval($tarea['duracion_dias']) / $duracion_total;
            $tarea['peso_actividad'] = $peso_total * $proporcion;
        }
        
        return $tareas;
    }
    
    /**
     * Log de actividades del sistema con contexto de peso
     */
    public static function log($mensaje, $nivel = 'INFO', $usuario = null, $contexto = []) {
        $log_file = 'logs/sistema.log';
        
        // Crear directorio de logs si no existe
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $usuario_info = $usuario ? " [Usuario: $usuario]" : '';
        $contexto_info = !empty($contexto) ? " [Contexto: " . json_encode($contexto) . "]" : '';
        $log_entry = "[$timestamp] [$nivel]$usuario_info$contexto_info $mensaje" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verificar permisos de escritura
     */
    public static function verificarPermisos() {
        $directorios = ['logs', 'uploads', 'cache', 'exports'];
        $problemas = [];
        
        foreach ($directorios as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $problemas[] = "No se puede crear el directorio: $dir";
                }
            } elseif (!is_writable($dir)) {
                $problemas[] = "Sin permisos de escritura en: $dir";
            }
        }
        
        return $problemas;
    }
    
    /**
     * Obtener información del sistema
     */
    public static function infoSistema() {
        return [
            'version_php' => PHP_VERSION,
            'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'sistema_operativo' => php_uname(),
            'memoria_limite' => ini_get('memory_limit'),
            'tiempo_limite' => ini_get('max_execution_time'),
            'zona_horaria' => date_default_timezone_get(),
            'fecha_actual' => date('Y-m-d H:i:s'),
            'version_sistema' => '2.0.0-peso-ponderado'
        ];
    }
}

class ResponseHelper {
    
    /**
     * Respuesta JSON de éxito
     */
    public static function success($data = null, $message = 'Operación exitosa') {
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Respuesta JSON de error
     */
    public static function error($message = 'Error en la operación', $code = 400, $details = null) {
        http_response_code($code);
        return json_encode([
            'success' => false,
            'error' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Respuesta JSON con validación fallida
     */
    public static function validationError($errors) {
        return self::error('Errores de validación', 422, $errors);
    }
    
    /**
     * Respuesta con datos de progreso ponderado
     */
    public static function progressData($progreso_ponderado, $peso_total, $avance_total, $additional_data = []) {
        return json_encode([
            'success' => true,
            'progreso_ponderado' => floatval($progreso_ponderado),
            'peso_total' => floatval($peso_total),
            'avance_total' => floatval($avance_total),
            'data' => $additional_data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Funciones helper globales
if (!function_exists('dd')) {
    /**
     * Dump and die para debugging
     */
    function dd(...$vars) {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}

if (!function_exists('formatearPorcentaje')) {
    /**
     * Formatear porcentaje
     */
    function formatearPorcentaje($numero, $decimales = 1) {
        return number_format($numero, $decimales) . '%';
    }
}

if (!function_exists('formatearPeso')) {
    /**
     * Formatear peso de actividad
     */
    function formatearPeso($peso, $decimales = 4) {
        return number_format(floatval($peso), $decimales, '.', '');
    }
}

if (!function_exists('calcularProgresoPonderado')) {
    /**
     * Calcular progreso ponderado rápido
     */
    function calcularProgresoPonderado($tareas) {
        return Utils::calcularProgresoProyectoPonderado($tareas);
    }
}

if (!function_exists('tiempoTranscurrido')) {
    /**
     * Calcular tiempo transcurrido desde una fecha
     */
    function tiempoTranscurrido($fecha) {
        if (empty($fecha)) return 'Nunca';
        
        try {
            $tiempo = time() - strtotime($fecha);
            
            if ($tiempo < 60) return 'Hace un momento';
            if ($tiempo < 3600) return 'Hace ' . floor($tiempo/60) . ' minutos';
            if ($tiempo < 86400) return 'Hace ' . floor($tiempo/3600) . ' horas';
            if ($tiempo < 2592000) return 'Hace ' . floor($tiempo/86400) . ' días';
            if ($tiempo < 31536000) return 'Hace ' . floor($tiempo/2592000) . ' meses';
            
            return 'Hace ' . floor($tiempo/31536000) . ' años';
        } catch (Exception $e) {
            return 'Fecha inválida';
        }
    }
}

if (!function_exists('generarColorPorTipo')) {
    /**
     * Generar color CSS para tipo de tarea
     */
    function generarColorPorTipo($tipo) {
        return Utils::colorPorTipo($tipo);
    }
}

if (!function_exists('validarPesoActividad')) {
    /**
     * Validar que un peso de actividad esté en rango válido
     */
    function validarPesoActividad($peso) {
        $peso_num = floatval($peso);
        return $peso_num >= 0 && $peso_num <= 1;
    }
}
?>
