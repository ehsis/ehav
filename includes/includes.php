<?php
/**
 * Utilidades y funciones de validación para el Sistema de Gestión de Proyectos
 * MEJORADO para manejar días totales configurables y peso de actividad en PORCENTAJES (0%-100%)
 */

class Validator {
    
    /**
     * Validar datos de proyecto - MEJORADO con días totales
     */
    public static function validarProyecto($datos) {
        $errores = [];
        
        // Nombre es requerido
        if (empty($datos['nombre']) || strlen(trim($datos['nombre'])) < 3) {
            $errores[] = 'El nombre del proyecto debe tener al menos 3 caracteres';
        }
        
        // NUEVO: Validar días totales
        if (isset($datos['dias_totales'])) {
            if (!is_numeric($datos['dias_totales']) || $datos['dias_totales'] <= 0) {
                $errores[] = 'Los días totales deben ser un número positivo';
            } elseif ($datos['dias_totales'] > 3650) { // Máximo 10 años
                $errores[] = 'Los días totales no pueden exceder 3650 días (10 años)';
            }
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
     * Validar datos de tarea con peso de actividad EN PORCENTAJES (0%-100%) - MEJORADO
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
        
        // MEJORADO: Duración debe ser positiva y puede ser fraccionaria
        if (!empty($datos['duracion_dias'])) {
            if (!is_numeric($datos['duracion_dias']) || $datos['duracion_dias'] <= 0) {
                $errores[] = 'La duración debe ser un número positivo (puede incluir decimales)';
            } elseif ($datos['duracion_dias'] > 365) {
                $errores[] = 'La duración no puede exceder 365 días por tarea';
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
        
        // CORREGIDO: Validar peso de actividad EN PORCENTAJES (0% - 100%)
        if (isset($datos['peso_actividad'])) {
            if (!is_numeric($datos['peso_actividad']) || 
                $datos['peso_actividad'] < 0 || 
                $datos['peso_actividad'] > 100) {
                $errores[] = 'El peso de actividad debe estar entre 0% y 100%';
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
     * NUEVO: Validar configuración de días totales
     */
    public static function validarDiasTotales($dias_totales, $dias_actuales_usados = 0) {
        $errores = [];
        $warnings = [];
        
        if (!is_numeric($dias_totales) || $días_totales <= 0) {
            $errores[] = 'Los días totales deben ser un número positivo';
            return ['errores' => $errores, 'warnings' => $warnings];
        }
        
        $dias_totales = floatval($dias_totales);
        
        // Validaciones de rango
        if ($dias_totales < 1) {
            $errores[] = 'El proyecto debe tener al menos 1 día';
        } elseif ($dias_totales > 3650) {
            $errores[] = 'Los días totales no pueden exceder 3650 días (10 años)';
        }
        
        // Validar consistencia con días ya utilizados
        if ($dias_actuales_usados > 0) {
            if ($dias_totales < $dias_actuales_usados) {
                $errores[] = sprintf(
                    'Los días totales (%.2f) no pueden ser menores a los días ya planificados (%.2f)',
                    $dias_totales,
                    $dias_actuales_usados
                );
            } elseif ($dias_totales < $dias_actuales_usados * 1.2) {
                $warnings[] = sprintf(
                    'Margen muy ajustado: solo %.2f días disponibles para nuevas tareas',
                    $dias_totales - $dias_actuales_usados
                );
            }
        }
        
        // Sugerencias según el tamaño del proyecto
        if ($dias_totales < 30) {
            $warnings[] = 'Proyecto de corta duración (menos de 1 mes)';
        } elseif ($dias_totales > 365) {
            $warnings[] = 'Proyecto de larga duración (más de 1 año) - considera dividir en fases';
        }
        
        return [
            'errores' => $errores,
            'warnings' => $warnings,
            'valido' => empty($errores)
        ];
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
     * Validar entrada de usuario para prevenir XSS y SQL injection - MEJORADO
     */
    public static function validarEntrada($input, $tipo = 'string') {
        switch ($tipo) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT);
            case 'dias_fraccionarios': // NUEVO: Para días con decimales
                $dias = filter_var($input, FILTER_VALIDATE_FLOAT);
                return ($dias !== false && $dias > 0 && $dias <= 365) ? $dias : false;
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL);
            case 'date':
                return self::validarFecha($input) ? $input : false;
            case 'peso_porcentaje': // NUEVO: Validación específica para porcentajes
                $peso = filter_var($input, FILTER_VALIDATE_FLOAT);
                return ($peso !== false && $peso >= 0 && $peso <= 100) ? $peso : false;
            default:
                return self::limpiarDatos($input);
        }
    }
    
    /**
     * Validar consistencia de pesos en un proyecto (DEBEN SUMAR 100%) - MEJORADO
     */
    public static function validarPesosProyecto($tareas, $tolerancia = 2.0) {
        $errores = [];
        $warnings = [];
        $peso_total = 0;
        $pesos_por_fase = [];
        $tareas_sin_peso = 0;
        $tareas_peso_alto = 0;
        
        foreach ($tareas as $tarea) {
            $peso = floatval($tarea['peso_actividad']);
            $peso_total += $peso;
            
            // Contar tareas problemáticas
            if ($peso == 0) {
                $tareas_sin_peso++;
            } elseif ($peso > 25) {
                $tareas_peso_alto++;
            }
            
            $fase = $tarea['fase_principal'] ?? 'Sin fase';
            if (!isset($pesos_por_fase[$fase])) {
                $pesos_por_fase[$fase] = 0;
            }
            $pesos_por_fase[$fase] += $peso;
        }
        
        // MEJORADO: Validaciones más granulares
        $diferencia = abs($peso_total - 100);
        
        if ($diferencia > $tolerancia) {
            if ($peso_total > 100) {
                $errores[] = sprintf("Sobreponderación: el peso total (%.2f%%) excede el 100%% por %.2f%%", 
                                   $peso_total, $peso_total - 100);
            } else {
                $errores[] = sprintf("Subponderación: el peso total (%.2f%%) es menor al 100%% por %.2f%%", 
                                   $peso_total, 100 - $peso_total);
            }
        } elseif ($diferencia > 0.5) {
            $warnings[] = sprintf("Ligera desviación: el peso total es %.2f%% (diferencia: %.2f%%)", 
                                $peso_total, $diferencia);
        }
        
        if ($tareas_sin_peso > 0) {
            $warnings[] = sprintf("%d tareas sin peso asignado", $tareas_sin_peso);
        }
        
        if ($tareas_peso_alto > 0) {
            $warnings[] = sprintf("%d tareas con peso mayor al 25%% (considerar subdividir)", $tareas_peso_alto);
        }
        
        // Validar distribución por fases
        foreach ($pesos_por_fase as $fase => $peso_fase) {
            if ($peso_fase > 50 && $fase !== 'Sin fase') {
                $warnings[] = sprintf("La fase '%s' concentra %.1f%% del peso total", $fase, $peso_fase);
            }
        }
        
        return [
            'errores' => $errores,
            'warnings' => $warnings,
            'peso_total' => $peso_total,
            'pesos_por_fase' => $pesos_por_fase,
            'tareas_sin_peso' => $tareas_sin_peso,
            'tareas_peso_alto' => $tareas_peso_alto,
            'diferencia_100' => $diferencia,
            'valido' => empty($errores)
        ];
    }
}

class Utils {
    
    /**
     * NUEVO: Calcular peso automático basado en días totales del proyecto
     */
    public static function calcularPesoAutomatico($duracion_dias, $dias_totales_proyecto) {
        if ($dias_totales_proyecto <= 0) return 0.0;
        
        $porcentaje = (floatval($duracion_dias) / floatval($dias_totales_proyecto)) * 100;
        return round($porcentaje, 4); // 4 decimales para mayor precisión
    }
    
    /**
     * NUEVO: Validar consistencia de días en un proyecto
     */
    public static function validarConsistenciaDias($tareas, $dias_totales_proyecto) {
        $total_dias_planificados = array_sum(array_column($tareas, 'duracion_dias'));
        $diferencia = $dias_totales_proyecto - $total_dias_planificados;
        $porcentaje_utilizado = ($total_dias_planificados / $dias_totales_proyecto) * 100;
        
        $nivel_consistencia = 'excelente';
        $mensaje = '';
        
        if (abs($diferencia) <= 0.5) {
            $nivel_consistencia = 'excelente';
            $mensaje = 'Los días están perfectamente distribuidos';
        } elseif (abs($diferencia) <= 2.0) {
            $nivel_consistencia = 'bueno';
            $mensaje = sprintf('Ligera diferencia de %.2f días', abs($diferencia));
        } elseif ($diferencia > 2.0) {
            $nivel_consistencia = 'sobrante';
            $mensaje = sprintf('%.2f días disponibles para nuevas tareas', $diferencia);
        } else {
            $nivel_consistencia = 'exceso';
            $mensaje = sprintf('Exceso de %.2f días - considera aumentar días totales', abs($diferencia));
        }
        
        return [
            'dias_totales' => $dias_totales_proyecto,
            'dias_planificados' => $total_dias_planificados,
            'diferencia' => $diferencia,
            'porcentaje_utilizado' => $porcentaje_utilizado,
            'nivel_consistencia' => $nivel_consistencia,
            'mensaje' => $mensaje,
            'necesita_accion' => abs($diferencia) > 2.0
        ];
    }
    
    /**
     * NUEVO: Sugerir ajustes para días del proyecto
     */
    public static function sugerirAjustesDias($analisis_consistencia, $tareas) {
        $sugerencias = [];
        
        if ($analisis_consistencia['nivel_consistencia'] === 'exceso') {
            $sugerencias[] = [
                'tipo' => 'aumentar_dias_totales',
                'descripcion' => sprintf('Aumentar días totales a %.0f días', 
                                       $analisis_consistencia['dias_planificados'] + 5),
                'impacto' => 'Permitirá planificación más holgada'
            ];
            
            $sugerencias[] = [
                'tipo' => 'reducir_duracion_tareas',
                'descripcion' => 'Reducir duración de tareas menos críticas',
                'impacto' => 'Optimizará el cronograma actual'
            ];
        } elseif ($analisis_consistencia['nivel_consistencia'] === 'sobrante') {
            $dias_sobrantes = $analisis_consistencia['diferencia'];
            
            if ($dias_sobrantes > 10) {
                $sugerencias[] = [
                    'tipo' => 'agregar_tareas',
                    'descripcion' => sprintf('Agregar tareas de revisión o calidad (%.1f días disponibles)', 
                                           $dias_sobrantes),
                    'impacto' => 'Mejorará la calidad del proyecto'
                ];
            }
            
            $sugerencias[] = [
                'tipo' => 'buffer_tiempo',
                'descripcion' => 'Mantener días como buffer para imprevistos',
                'impacto' => 'Reducirá riesgos de retrasos'
            ];
        }
        
        // Analizar tareas con pesos desproporcionados
        $tareas_largas = array_filter($tareas, function($t) {
            return floatval($t['duracion_dias']) > 10;
        });
        
        if (!empty($tareas_largas)) {
            $sugerencias[] = [
                'tipo' => 'subdividir_tareas',
                'descripcion' => sprintf('Considerar subdividir %d tareas de larga duración', 
                                       count($tareas_largas)),
                'impacto' => 'Mejorará el control y seguimiento'
            ];
        }
        
        return $sugerencias;
    }
    
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
     * NUEVO: Formatear días con decimales de forma legible
     */
    public static function formatearDias($dias, $mostrar_unidad = true) {
        $dias_num = floatval($dias);
        
        if ($dias_num == 0) {
            return $mostrar_unidad ? '0 días' : '0';
        }
        
        // Si es un número entero, no mostrar decimales
        if ($dias_num == floor($dias_num)) {
            $formato = number_format($dias_num, 0);
        } else {
            $formato = number_format($dias_num, 2);
        }
        
        if (!$mostrar_unidad) return $formato;
        
        $unidad = ($dias_num == 1) ? 'día' : 'días';
        return $formato . ' ' . $unidad;
    }
    
    /**
     * Formatear moneda
     */
    public static function formatearMoneda($cantidad, $moneda = '₡') {
        return $moneda . ' ' . self::formatearNumero($cantidad, 2);
    }
    
    /**
     * CORREGIDO: Formatear peso de actividad como PORCENTAJE
     */
    public static function formatearPeso($peso, $decimales = 2) {
        return number_format(floatval($peso), $decimales, '.', '') . '%';
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
     * NUEVO: Generar color basado en nivel de peso
     */
    public static function colorPorPeso($peso) {
        $peso_num = floatval($peso);
        
        if ($peso_num === 0) return '#6c757d';      // Gris - sin peso
        if ($peso_num <= 5) return '#28a745';       // Verde - peso bajo
        if ($peso_num <= 15) return '#17a2b8';      // Azul - peso normal
        if ($peso_num <= 25) return '#ffc107';      // Amarillo - peso alto
        return '#dc3545';                           // Rojo - peso muy alto
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
     * CORREGIDO: Calcular porcentaje de progreso del proyecto usando peso ponderado EN PORCENTAJES
     */
    public static function calcularProgresoProyectoPonderado($tareas) {
        if (empty($tareas)) return 0;
        
        $peso_total = 0;
        $avance_ponderado = 0;
        
        foreach ($tareas as $tarea) {
            $peso = floatval($tarea['peso_actividad']); // Ya en porcentajes
            $peso_total += $peso;
            
            if ($tarea['estado'] === 'Listo') {
                $avance_ponderado += $peso;
            } elseif ($tarea['estado'] === 'En Proceso') {
                $avance_ponderado += $peso * (floatval($tarea['porcentaje_avance']) / 100);
            }
        }
        
        // CORREGIDO: Ya no dividimos por 100 porque el peso ya está en porcentajes
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
     * MEJORADO: Generar reporte de estadísticas con peso ponderado EN PORCENTAJES e información de días
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
            'dias_totales_proyecto' => floatval($proyecto['dias_totales'] ?? 56), // NUEVO
            'diferencia_dias' => 0, // NUEVO
            'porcentaje_dias_usado' => 0, // NUEVO
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
            ],
            'consistencia' => [ // NUEVO
                'pesos_ok' => true,
                'dias_ok' => true,
                'alertas' => []
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
            
            // CORREGIDO: Cálculos ponderados con peso EN PORCENTAJES
            $peso = floatval($tarea['peso_actividad']); // Ya en porcentajes
            $peso_total += $peso;
            
            if ($tarea['estado'] === 'Listo') {
                $avance_ponderado += $peso;
            } elseif ($tarea['estado'] === 'En Proceso') {
                $avance_ponderado += $peso * (floatval($tarea['porcentaje_avance']) / 100);
            }
            
            $total_progreso += floatval($tarea['porcentaje_avance']);
            $stats['duracion_total'] += floatval($tarea['duracion_dias']); // MEJORADO: Permite decimales
            
            // Agrupar por fase principal
            $fase = $tarea['fase_principal'] ?? 'Sin fase';
            if (!isset($fases_info[$fase])) {
                $fases_info[$fase] = [
                    'nombre' => $fase,
                    'total_tareas' => 0,
                    'peso_total' => 0,
                    'duracion_total' => 0, // NUEVO
                    'completadas' => 0,
                    'progreso' => 0
                ];
            }
            $fases_info[$fase]['total_tareas']++;
            $fases_info[$fase]['peso_total'] += $peso;
            $fases_info[$fase]['duracion_total'] += floatval($tarea['duracion_dias']); // NUEVO
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
        
        // CORREGIDO: Calcular progresos con porcentajes
        $stats['progreso_promedio'] = count($tareas) > 0 ? round($total_progreso / count($tareas), 2) : 0;
        $stats['progreso_ponderado'] = $peso_total > 0 ? round(($avance_ponderado / $peso_total) * 100, 2) : 0;
        $stats['peso_total'] = $peso_total; // Ya en porcentajes
        $stats['avance_ponderado_total'] = $avance_ponderado; // Ya en porcentajes
        
        // NUEVO: Cálculos de consistencia de días
        $stats['diferencia_dias'] = $stats['dias_totales_proyecto'] - $stats['duracion_total'];
        $stats['porcentaje_dias_usado'] = $stats['dias_totales_proyecto'] > 0 ? 
            ($stats['duracion_total'] / $stats['dias_totales_proyecto']) * 100 : 0;
        
        // NUEVO: Análisis de consistencia
        $stats['consistencia']['pesos_ok'] = abs($peso_total - 100) <= 2.0;
        $stats['consistencia']['dias_ok'] = abs($stats['diferencia_dias']) <= 2.0;
        
        if (!$stats['consistencia']['pesos_ok']) {
            $stats['consistencia']['alertas'][] = sprintf(
                'Peso total: %.2f%% (diferencia: %+.2f%%)', 
                $peso_total, $peso_total - 100
            );
        }
        
        if (!$stats['consistencia']['dias_ok']) {
            $stats['consistencia']['alertas'][] = sprintf(
                'Días planificados: %.2f de %.2f días totales', 
                $stats['duracion_total'], $stats['dias_totales_proyecto']
            );
        }
        
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
     * MEJORADO: Distribuir peso automáticamente entre tareas basado en días totales
     */
    public static function distribuirPesoAutomatico($tareas, $dias_totales_proyecto, $metodo = 'por_dias') {
        if (empty($tareas) || $dias_totales_proyecto <= 0) return $tareas;
        
        switch ($metodo) {
            case 'por_dias':
                return self::distribuirPesoPorDiasTotales($tareas, $dias_totales_proyecto);
            case 'por_tipo':
                return self::distribuirPesoPorTipo($tareas, 100.0);
            case 'por_duracion':
                return self::distribuirPesoPorDuracion($tareas, 100.0);
            case 'por_fase':
                return self::distribuirPesoPorFase($tareas, 100.0);
            case 'equitativo':
            default:
                return self::distribuirPesoEquitativo($tareas, 100.0);
        }
    }
    
    /**
     * NUEVO: Distribuir peso basándose en días totales del proyecto
     */
    private static function distribuirPesoPorDiasTotales($tareas, $dias_totales_proyecto) {
        foreach ($tareas as &$tarea) {
            $duracion = floatval($tarea['duracion_dias']);
            $tarea['peso_actividad'] = self::calcularPesoAutomatico($duracion, $dias_totales_proyecto);
        }
        
        return $tareas;
    }
    
    /**
     * Distribuir peso equitativamente (cada tarea = 100% / total tareas)
     */
    private static function distribuirPesoEquitativo($tareas, $peso_total) {
        $peso_por_tarea = $peso_total / count($tareas);
        
        foreach ($tareas as &$tarea) {
            $tarea['peso_actividad'] = $peso_por_tarea;
        }
        
        return $tareas;
    }
    
    /**
     * CORREGIDO: Distribuir peso por tipo según análisis del Excel Cafeto
     */
    private static function distribuirPesoPorTipo($tareas, $peso_total) {
        $pesos_tipo = [
            'Fase' => 20.0,      // 20% del peso total para fases
            'Actividad' => 60.0,  // 60% del peso total para actividades
            'Tarea' => 20.0       // 20% del peso total para tareas
        ];
        
        $conteo_tipos = ['Fase' => 0, 'Actividad' => 0, 'Tarea' => 0];
        
        // Contar tareas por tipo
        foreach ($tareas as $tarea) {
            $conteo_tipos[$tarea['tipo']]++;
        }
        
        // Asignar pesos
        foreach ($tareas as &$tarea) {
            $tipo = $tarea['tipo'];
            $peso_tipo_total = $pesos_tipo[$tipo];
            $tarea['peso_actividad'] = $conteo_tipos[$tipo] > 0 ? $peso_tipo_total / $conteo_tipos[$tipo] : 0;
        }
        
        return $tareas;
    }
    
    /**
     * CORREGIDO: Distribuir peso por fase según el Excel Cafeto
     */
    private static function distribuirPesoPorFase($tareas, $peso_total) {
        // Pesos según el análisis del Excel
        $pesos_fases_cafeto = [
            '1. Recepción de planos constructivos' => 1.0,
            '2. Cotizaciones' => 84.0,
            '3. Presupuesto Infraestructura' => 10.0,
            '4. Presupuesto Casas' => 5.0
        ];
        
        // Agrupar por fase
        $tareas_por_fase = [];
        foreach ($tareas as $tarea) {
            $fase = $tarea['fase_principal'] ?? 'Sin fase';
            if (!isset($tareas_por_fase[$fase])) {
                $tareas_por_fase[$fase] = [];
            }
            $tareas_por_fase[$fase][] = &$tarea;
        }
        
        // Distribuir peso por fase
        foreach ($tareas_por_fase as $fase => $tareas_fase) {
            $peso_fase = $pesos_fases_cafeto[$fase] ?? ($peso_total / count($tareas_por_fase));
            $peso_por_tarea = $peso_fase / count($tareas_fase);
            
            foreach ($tareas_fase as &$tarea) {
                $tarea['peso_actividad'] = $peso_por_tarea;
            }
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
            $proporcion = floatval($tarea['duracion_dias']) / $duracion_total;
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
     * Obtener información del sistema - MEJORADO
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
            'version_sistema' => '2.1.0-dias-configurables' // ACTUALIZADO
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
     * MEJORADO: Respuesta con datos de progreso ponderado EN PORCENTAJES e información de días
     */
    public static function progressData($progreso_ponderado, $peso_total, $avance_total, $dias_info = [], $additional_data = []) {
        return json_encode([
            'success' => true,
            'progreso_ponderado' => floatval($progreso_ponderado), // Ya en porcentajes
            'peso_total' => floatval($peso_total), // Ya en porcentajes
            'avance_total' => floatval($avance_total), // Ya en porcentajes
            'dias_info' => $dias_info, // NUEVO
            'data' => $additional_data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * NUEVO: Respuesta especializada para operaciones de días
     */
    public static function diasOperationResponse($success, $message, $dias_afectados = 0, $tareas_afectadas = 0, $nuevo_peso_total = null) {
        return json_encode([
            'success' => $success,
            'message' => $message,
            'dias_afectados' => floatval($dias_afectados),
            'tareas_afectadas' => intval($tareas_afectadas),
            'nuevo_peso_total' => $nuevo_peso_total ? floatval($nuevo_peso_total) : null,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// ===== NUEVAS FUNCIONES HELPER GLOBALES =====

if (!function_exists('calcularPesoAutomatico')) {
    /**
     * NUEVO: Calcular peso automático basado en días totales del proyecto
     */
    function calcularPesoAutomatico($duracion_dias, $dias_totales_proyecto) {
        return Utils::calcularPesoAutomatico($duracion_dias, $dias_totales_proyecto);
    }
}

if (!function_exists('formatearDias')) {
    /**
     * NUEVO: Formatear días con decimales
     */
    function formatearDias($dias, $mostrar_unidad = true) {
        return Utils::formatearDias($dias, $mostrar_unidad);
    }
}

if (!function_exists('validarDiasFraccionarios')) {
    /**
     * NUEVO: Validar entrada de días fraccionarios
     */
    function validarDiasFraccionarios($dias) {
        return Validator::validarEntrada($dias, 'dias_fraccionarios');
    }
}

if (!function_exists('colorPorPeso')) {
    /**
     * NUEVO: Color CSS basado en nivel de peso
     */
    function colorPorPeso($peso) {
        return Utils::colorPorPeso($peso);
    }
}

// ===== FUNCIONES HELPER GLOBALES EXISTENTES =====

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
     * CORREGIDO: Formatear peso de actividad como porcentaje
     */
    function formatearPeso($peso, $decimales = 2) {
        return number_format(floatval($peso), $decimales, '.', '') . '%';
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
     * CORREGIDO: Validar que un peso de actividad esté en rango válido (0%-100%)
     */
    function validarPesoActividad($peso) {
        $peso_num = floatval($peso);
        return $peso_num >= 0 && $peso_num <= 100;
    }
}
?>
