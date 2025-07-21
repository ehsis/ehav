<?php
/**
 * Utilidades y funciones de validación para el Sistema de Gestión de Proyectos
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
     * Validar datos de tarea
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
            default:
                return self::limpiarDatos($input);
        }
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
     * Calcular porcentaje de progreso del proyecto
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
     * Generar reporte de estadísticas
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
            'duracion_total' => 0,
            'fechas' => [
                'inicio' => null,
                'fin_estimada' => null,
                'primera_tarea' => null,
                'ultima_tarea' => null
            ]
        ];
        
        if (empty($tareas)) return $stats;
        
        $total_progreso = 0;
        $fechas_inicio = [];
        $fechas_fin = [];
        
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
            
            $total_progreso += floatval($tarea['porcentaje_avance']);
            $stats['duracion_total'] += intval($tarea['duracion_dias']);
            
            // Recopilar fechas
            if (!empty($tarea['fecha_inicio'])) {
                $fechas_inicio[] = $tarea['fecha_inicio'];
            }
            if (!empty($tarea['fecha_fin'])) {
                $fechas_fin[] = $tarea['fecha_fin'];
            }
        }
        
        $stats['progreso_promedio'] = round($total_progreso / count($tareas), 2);
        
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
     * Exportar datos a CSV
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
     * Log de actividades del sistema
     */
    public static function log($mensaje, $nivel = 'INFO', $usuario = null) {
        $log_file = 'logs/sistema.log';
        
        // Crear directorio de logs si no existe
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $usuario_info = $usuario ? " [Usuario: $usuario]" : '';
        $log_entry = "[$timestamp] [$nivel]$usuario_info $mensaje" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verificar permisos de escritura
     */
    public static function verificarPermisos() {
        $directorios = ['logs', 'uploads', 'cache'];
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
            'fecha_actual' => date('Y-m-d H:i:s')
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
?>
