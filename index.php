<?php
session_start();
require_once 'config/database.php';
require_once 'includes/includes.php';

$database = new Database();
$db = $database->getConnection();
$proyectoManager = new ProyectoManager($db);

// Obtener proyecto actual
$proyecto_actual_id = $_GET['proyecto'] ?? $_SESSION['proyecto_actual'] ?? 1;
$_SESSION['proyecto_actual'] = $proyecto_actual_id;

$proyecto_actual = $proyectoManager->obtenerProyecto($proyecto_actual_id);
$proyectos = $proyectoManager->obtenerProyectos();

// Si no se encuentra el proyecto, usar el primero disponible
if (!$proyecto_actual && !empty($proyectos)) {
    $proyecto_actual = $proyectos[0];
    $proyecto_actual_id = $proyecto_actual['id'];
    $_SESSION['proyecto_actual'] = $proyecto_actual_id;
}

// Obtener estadísticas del proyecto actual (con peso ponderado)
$stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_actual_id);
$tareas = $proyectoManager->obtenerTareasProyecto($proyecto_actual_id);
$estadisticas_por_tipo = $proyectoManager->obtenerEstadisticasPorTipo($proyecto_actual_id);
$estadisticas_por_fase = $proyectoManager->obtenerEstadisticasPorFase($proyecto_actual_id);
$cronograma_ponderado = $proyectoManager->obtenerCronogramaPonderado($proyecto_actual_id);

$view = $_GET['view'] ?? 'dashboard';

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Selector de proyecto y estadísticas principales -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">
                <i class="fas fa-building"></i> 
                <?= htmlspecialchars($proyecto_actual['nombre'] ?? 'Sin Proyecto') ?>
                <span class="badge bg-<?= strtolower($proyecto_actual['estado'] ?? 'activo') ?> ms-2">
                    <?= $proyecto_actual['estado'] ?? 'Activo' ?>
                </span>
            </h1>
            <p class="text-muted"><?= htmlspecialchars($proyecto_actual['descripcion'] ?? '') ?></p>
            
            <!-- CORREGIDO: Progreso ponderado principal -->
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-chart-line me-2"></i>
                <strong>Progreso Ponderado: <?= number_format($stats['avance_promedio'], 2) ?>%</strong>
                <span class="ms-3 text-muted">
                    (Peso total: <?= number_format($stats['peso_total'], 2) ?>%)
                </span>
                <?php if (abs($stats['peso_total'] - 100) > 5): ?>
                    <span class="ms-2 badge bg-warning text-dark">
                        ⚠️ No suma 100%
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label class="form-label">Cambiar Proyecto:</label>
                    <select class="form-select" onchange="ProyectoApp.cambiarProyecto(this.value)">
                        <?php foreach ($proyectos as $proyecto): ?>
                            <option value="<?= $proyecto['id'] ?>" 
                                    <?= ($proyecto['id'] == $proyecto_actual_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proyecto['nombre']) ?>
                                (<?= number_format($proyecto['progreso_calculado'] ?? 0, 1) ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link <?= ($view === 'dashboard') ? 'active' : '' ?>" 
                                    onclick="ProyectoApp.cambiarVista('dashboard')">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </button>
                            <button class="nav-link <?= ($view === 'tareas') ? 'active' : '' ?>" 
                                    onclick="ProyectoApp.cambiarVista('tareas')">
                                <i class="fas fa-tasks"></i> Tareas
                            </button>
                            <button class="nav-link <?= ($view === 'reportes') ? 'active' : '' ?>" 
                                    onclick="ProyectoApp.cambiarVista('reportes')">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </button>
                            <button class="nav-link <?= ($view === 'proyectos') ? 'active' : '' ?>" 
                                    onclick="ProyectoApp.cambiarVista('proyectos')">
                                <i class="fas fa-folder"></i> Gestión Proyectos
                            </button>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <?php if ($view === 'dashboard'): ?>
        <!-- Dashboard del proyecto actual -->
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard - <?= htmlspecialchars($proyecto_actual['nombre']) ?></h2>
                <hr>
            </div>
        </div>

        <!-- Métricas principales -->
        <div class="dashboard-stats">
            <div class="metric-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Total de Tareas</h5>
                        <div class="metric-number"><?= $stats['total'] ?></div>
                        <!-- CORREGIDO: Peso total con % -->
                        <small class="text-light">Peso total: <?= number_format($stats['peso_total'], 2) ?>%</small>
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Completadas</h5>
                        <div class="metric-number"><?= $stats['completadas'] ?></div>
                        <!-- CORREGIDO: Peso con % -->
                        <small class="text-light">
                            Peso: <?= number_format($stats['avance_ponderado'], 2) ?>%
                        </small>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>En Proceso</h5>
                        <div class="metric-number"><?= $stats['en_proceso'] ?></div>
                        <small class="text-light">Progreso parcial</small>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Pendientes</h5>
                        <div class="metric-number"><?= $stats['pendientes'] ?></div>
                        <small class="text-light">Sin iniciar</small>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Progreso por tipo y fases -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-layer-group"></i> Progreso por Tipo (Ponderado)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($estadisticas_por_tipo as $tipo_stat): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="badge badge-<?= strtolower($tipo_stat['tipo']) ?>">
                                        <?= $tipo_stat['tipo'] ?>
                                    </span>
                                    <span class="fw-bold">
                                        <?= number_format($tipo_stat['avance_promedio'], 1) ?>%
                                    </span>
                                </div>
                                <div class="progress mt-1" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $tipo_stat['avance_promedio'] ?>%" 
                                         aria-valuenow="<?= $tipo_stat['avance_promedio'] ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <!-- CORREGIDO: Peso con % -->
                                <small class="text-muted">
                                    Peso: <?= number_format($tipo_stat['peso_total'], 2) ?>% | 
                                    Tareas: <?= $tipo_stat['total'] ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Distribución Ponderada</h5>
                    </div>
                    <div class="card-body">
                        <div id="dashboardChartContainer" style="position: relative; height: 300px;">
                            <canvas id="graficoProgreso"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cronograma por fases -->
        <?php if (!empty($cronograma_ponderado)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Progreso por Fases Principales</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fase</th>
                                        <th>Total Elementos</th>
                                        <th>Peso de Fase</th>
                                        <th>Progreso</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cronograma_ponderado as $fase): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($fase['fase_principal']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= $fase['total_elementos'] ?></span>
                                                <small class="text-muted">(<?= $fase['completados'] ?> completados)</small>
                                            </td>
                                            <td>
                                                <!-- CORREGIDO: Peso de fase con % -->
                                                <span class="fw-bold"><?= number_format($fase['peso_fase'], 2) ?>%</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= $fase['progreso_fase'] ?>%" 
                                                         aria-valuenow="<?= $fase['progreso_fase'] ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?= number_format($fase['progreso_fase'], 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($fase['progreso_fase'] >= 100): ?>
                                                    <span class="badge bg-success">Completada</span>
                                                <?php elseif ($fase['progreso_fase'] > 0): ?>
                                                    <span class="badge bg-warning">En Proceso</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Pendiente</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tareas recientes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-history"></i> Tareas Recientes</h5>
                        <div>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                                <i class="fas fa-plus"></i> Nueva Tarea
                            </button>
                            <button class="btn btn-sm btn-success" onclick="ProyectoApp.importarDatosCafeto(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-file-excel"></i> Datos Ejemplo
                            </button>
                            <!-- NUEVO: Botones de gestión de peso -->
                            <button class="btn btn-sm btn-warning" onclick="ProyectoApp.distribuirPesoAutomatico(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-balance-scale"></i> Distribuir Peso
                            </button>
                            <?php if (abs($stats['peso_total'] - 100) > 5): ?>
                                <button class="btn btn-sm btn-info" onclick="ProyectoApp.corregirPesosAutomaticamente(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-wrench"></i> Corregir Pesos
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarea</th>
                                        <th>Tipo</th>
                                        <th>Fase</th>
                                        <th>Peso</th>
                                        <th>Contrato</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY updated_at DESC LIMIT 15";
                                    $recent_stmt = $db->prepare($recent_query);
                                    $recent_stmt->execute([$proyecto_actual_id]);
                                    $recent_tareas = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($recent_tareas as $tarea): ?>
                                        <tr class="task-card <?= strtolower($tarea['tipo']) ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($tarea['nombre']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($tarea['tipo']) ?>">
                                                    <?= $tarea['tipo'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($tarea['fase_principal'] ?? 'Sin fase') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <!-- CORREGIDO: Peso con % y color según valor -->
                                                <?php 
                                                $peso = floatval($tarea['peso_actividad']);
                                                $color_peso = $peso == 0 ? 'text-muted' : ($peso > 20 ? 'text-danger fw-bold' : ($peso > 10 ? 'text-warning fw-bold' : 'text-success fw-bold'));
                                                ?>
                                                <span class="<?= $color_peso ?>"><?= number_format($peso, 2) ?>%</span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $tarea['contrato'] === 'Contrato Clave' ? 'bg-warning' : 'bg-secondary' ?>">
                                                    <?= $tarea['contrato'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="estado-<?= strtolower(str_replace(' ', '', $tarea['estado'])) ?>">
                                                    <i class="fas fa-circle"></i> <?= $tarea['estado'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px;">
                                                    <div class="progress-bar" style="width: <?= $tarea['porcentaje_avance'] ?>%">
                                                        <?= $tarea['porcentaje_avance'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= date('d/m/Y H:i', strtotime($tarea['updated_at'])) ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'tareas'): ?>
        <!-- Vista de Tareas completa -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tasks"></i> Gestión de Tareas - <?= htmlspecialchars($proyecto_actual['nombre']) ?></h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                            <i class="fas fa-plus"></i> Nueva Tarea
                        </button>
                        <button class="btn btn-success" onclick="ProyectoApp.importarDatosCafeto(<?= $proyecto_actual_id ?>)">
                            <i class="fas fa-file-excel"></i> Datos Ejemplo
                        </button>
                        <!-- NUEVO: Herramientas de peso -->
                        <div class="btn-group" role="group">
                            <button class="btn btn-warning" onclick="ProyectoApp.distribuirPesoAutomatico(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-balance-scale"></i> Distribuir
                            </button>
                            <?php if (abs($stats['peso_total'] - 100) > 5): ?>
                                <button class="btn btn-info" onclick="ProyectoApp.corregirPesosAutomaticamente(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-wrench"></i> Corregir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- NUEVO: Indicador de estado de pesos -->
        <?php if (abs($stats['peso_total'] - 100) > 5): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Peso total: <?= number_format($stats['peso_total'], 2) ?>%</strong>
                        <?php if ($stats['peso_total'] > 100): ?>
                            - Excede el 100%, considera redistribuir
                        <?php else: ?>
                            - Falta para llegar al 100%
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-sm btn-outline-dark" onclick="ProyectoApp.corregirPesosAutomaticamente(<?= $proyecto_actual_id ?>)">
                        <i class="fas fa-magic"></i> Corregir Automáticamente
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" id="filtroTipo" onchange="ProyectoApp.filtrarTabla()">
                    <option value="">Todos los tipos</option>
                    <option value="Fase">Fase</option>
                    <option value="Actividad">Actividad</option>
                    <option value="Tarea">Tarea</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroEstado" onchange="ProyectoApp.filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Listo">Listo</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroFase" onchange="ProyectoApp.filtrarTabla()">
                    <option value="">Todas las fases</option>
                    <?php 
                    $fases_disponibles = $proyectoManager->obtenerFasesPrincipales($proyecto_actual_id);
                    foreach ($fases_disponibles as $fase): ?>
                        <option value="<?= htmlspecialchars($fase) ?>"><?= htmlspecialchars($fase) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroContrato" onchange="ProyectoApp.filtrarTabla()">
                    <option value="">Todos los contratos</option>
                    <option value="Normal">Normal</option>
                    <option value="Contrato Clave">Contrato Clave</option>
                </select>
            </div>
        </div>

        <!-- Tabla de tareas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="tablaTareas">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Fase Principal</th>
                                        <th>Peso</th>
                                        <th>Contrato</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tareas as $t): ?>
                                        <tr data-estado="<?= $t['estado'] ?>" 
                                            data-tipo="<?= $t['tipo'] ?>"
                                            data-fase="<?= htmlspecialchars($t['fase_principal'] ?? '') ?>"
                                            data-contrato="<?= $t['contrato'] ?>">
                                            <td>
                                                <strong><?= htmlspecialchars($t['nombre']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($t['tipo']) ?>">
                                                    <?= $t['tipo'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($t['fase_principal'] ?? 'Sin fase') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <!-- CORREGIDO: Peso con % y estilos según valor -->
                                                <?php 
                                                $peso = floatval($t['peso_actividad']);
                                                if ($peso == 0) {
                                                    $badge_class = 'bg-secondary';
                                                    $icon = 'fas fa-minus';
                                                } elseif ($peso > 25) {
                                                    $badge_class = 'bg-danger';
                                                    $icon = 'fas fa-exclamation-triangle';
                                                } elseif ($peso > 10) {
                                                    $badge_class = 'bg-warning text-dark';
                                                    $icon = 'fas fa-exclamation';
                                                } else {
                                                    $badge_class = 'bg-success';
                                                    $icon = 'fas fa-check';
                                                }
                                                ?>
                                                <span class="badge <?= $badge_class ?>" title="Peso: <?= number_format($peso, 2) ?>% del proyecto total">
                                                    <i class="<?= $icon ?>"></i> <?= number_format($peso, 2) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $t['contrato'] === 'Contrato Clave' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                                    <?= $t['contrato'] ?>
                                                </span>
                                            </td>
                                            <td><?= $t['duracion_dias'] ?> días</td>
                                            <td>
                                                <span class="badge bg-<?= $t['estado'] === 'Listo' ? 'success' : ($t['estado'] === 'En Proceso' ? 'warning' : 'danger') ?>">
                                                    <?= $t['estado'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= $t['porcentaje_avance'] ?>%;">
                                                        <?= $t['porcentaje_avance'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-warning" onclick="ProyectoApp.editarTarea(<?= $t['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="ProyectoApp.eliminarTarea(<?= $t['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <!-- NUEVO: Footer con totales -->
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3"><strong>TOTALES:</strong></td>
                                        <td>
                                            <span class="badge <?= abs($stats['peso_total'] - 100) < 1 ? 'bg-success' : 'bg-warning text-dark' ?> fs-6">
                                                <?= number_format($stats['peso_total'], 2) ?>%
                                            </span>
                                        </td>
                                        <td colspan="2"><?= count($tareas) ?> tareas</td>
                                        <td>
                                            <span class="badge bg-info fs-6">
                                                <?= number_format($stats['avance_promedio'], 1) ?>%
                                            </span>
                                        </td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'reportes'): ?>
        <!-- Vista de Reportes CORREGIDA -->
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-chart-bar"></i> Reportes y Análisis - <?= htmlspecialchars($proyecto_actual['nombre']) ?></h2>
                <hr>
            </div>
        </div>

        <!-- Resumen ejecutivo -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-chart-line"></i> Resumen Ejecutivo</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h3 class="text-primary"><?= number_format($stats['avance_promedio'], 1) ?>%</h3>
                                <p class="text-muted">Progreso Ponderado</p>
                            </div>
                            <div class="col-md-3 text-center">
                                <!-- CORREGIDO: Peso total con % -->
                                <h3 class="text-info"><?= number_format($stats['peso_total'], 2) ?>%</h3>
                                <p class="text-muted">Peso Total</p>
                                <?php if (abs($stats['peso_total'] - 100) > 1): ?>
                                    <small class="text-warning">⚠️ No suma 100%</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-success"><?= $stats['completadas'] ?></h3>
                                <p class="text-muted">Tareas Completadas</p>
                            </div>
                            <div class="col-md-3 text-center">
                                <h3 class="text-danger"><?= $stats['pendientes'] ?></h3>
                                <p class="text-muted">Tareas Pendientes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos principales -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Progreso por Tipo (Ponderado)</h5>
                    </div>
                    <div class="card-body">
                        <div id="tiposChartContainer" style="position: relative; height: 300px;">
                            <canvas id="graficoTipos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Distribución por Estado</h5>
                    </div>
                    <div class="card-body">
                        <div id="estadosChartContainer" style="position: relative; height: 300px;">
                            <canvas id="graficoEstados"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de fases -->
        <?php if (!empty($estadisticas_por_fase)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-area"></i> Progreso por Fases Principales</h5>
                    </div>
                    <div class="card-body">
                        <div id="fasesChartContainer" style="position: relative; height: 200px;">
                            <canvas id="graficoFases"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de estadísticas detalladas -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-table"></i> Análisis Detallado por Fases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fase Principal</th>
                                        <th>Total Tareas</th>
                                        <th>Peso Total</th>
                                        <th>Completadas</th>
                                        <th>En Proceso</th>
                                        <th>Pendientes</th>
                                        <th>Progreso Ponderado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estadisticas_por_fase as $fase_stat): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($fase_stat['fase_principal']) ?></strong></td>
                                            <td><?= $fase_stat['total'] ?></td>
                                            <!-- CORREGIDO: Peso con % -->
                                            <td><span class="fw-bold"><?= number_format($fase_stat['peso_total'], 2) ?>%</span></td>
                                            <td><span class="badge bg-success"><?= $fase_stat['completadas'] ?></span></td>
                                            <td><span class="badge bg-warning"><?= $fase_stat['en_proceso'] ?></span></td>
                                            <td><span class="badge bg-danger"><?= $fase_stat['pendientes'] ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" style="width: <?= $fase_stat['avance_promedio'] ?>%">
                                                        <?= number_format($fase_stat['avance_promedio'], 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opciones de exportación -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-download"></i> Opciones de Exportación</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary" onclick="ProyectoApp.exportarProyecto(<?= $proyecto_actual_id ?>, 'csv')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-outline-success" onclick="ProyectoApp.exportarProyecto(<?= $proyecto_actual_id ?>, 'json')">
                                <i class="fas fa-file-code"></i> JSON
                            </button>
                            <button class="btn btn-outline-info" onclick="ProyectoApp.exportarReporte(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-file-alt"></i> Reporte HTML
                            </button>
                            <button class="btn btn-outline-warning" onclick="ProyectoApp.exportarProyecto(<?= $proyecto_actual_id ?>, 'xml')">
                                <i class="fas fa-file-code"></i> XML
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'proyectos'): ?>
        <!-- Vista de gestión de proyectos (igual que antes pero con progreso ponderado) -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-folder"></i> Gestión de Proyectos</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de proyectos -->
        <div class="row">
            <?php foreach ($proyectos as $proyecto): 
                $stats_proyecto = $proyectoManager->obtenerEstadisticasProyecto($proyecto['id']);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 <?= ($proyecto['id'] == $proyecto_actual_id) ? 'proyecto-activo' : '' ?>">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($proyecto['nombre']) ?></h5>
                            <small>Progreso Ponderado: <?= number_format($proyecto['progreso_calculado'] ?? 0, 1) ?>%</small>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?= htmlspecialchars($proyecto['descripcion']) ?></p>
                            <div class="mb-3">
                                <small class="text-muted">Cliente: <?= htmlspecialchars($proyecto['cliente']) ?></small>
                            </div>
                            <div class="mb-3">
                                <span class="badge bg-<?= strtolower($proyecto['estado']) ?>">
                                    <?= $proyecto['estado'] ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar" style="width: <?= $stats_proyecto['avance_promedio'] ?>%">
                                        <?= number_format($stats_proyecto['avance_promedio'], 1) ?>%
                                    </div>
                                </div>
                                <!-- CORREGIDO: Peso total con % -->
                                <small class="text-muted">Peso total: <?= number_format($stats_proyecto['peso_total'], 2) ?>%</small>
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Total</small>
                                    <div class="fw-bold"><?= $stats_proyecto['total'] ?></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Completadas</small>
                                    <div class="fw-bold text-success"><?= $stats_proyecto['completadas'] ?></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Pendientes</small>
                                    <div class="fw-bold text-danger"><?= $stats_proyecto['pendientes'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <a href="?proyecto=<?= $proyecto['id'] ?>&view=dashboard" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <button class="btn btn-outline-warning btn-sm" onclick="ProyectoApp.editarProyecto(<?= $proyecto['id'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="ProyectoApp.duplicarProyecto(<?= $proyecto['id'] ?>)">
                                    <i class="fas fa-copy"></i> Duplicar
                                </button>
                                <?php if ($proyecto['id'] != $proyecto_actual_id): ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="ProyectoApp.eliminarProyecto(<?= $proyecto['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<!-- Incluir modales -->
<?php include 'includes/modales.php'; ?>

<!-- SISTEMA JAVASCRIPT CORREGIDO - SIN CONFLICTOS -->
<script>
// ============================================================================
// SISTEMA CONSOLIDADO - VERSIÓN CORREGIDA Y FUNCIONAL
// ============================================================================

window.ProyectoApp = window.ProyectoApp || {
    config: {
        stats: <?= json_encode($stats) ?>,
        estadisticas_por_tipo: <?= json_encode($estadisticas_por_tipo) ?>,
        estadisticas_por_fase: <?= json_encode($estadisticas_por_fase) ?>,
        proyecto_id: <?= $proyecto_actual_id ?>,
        view: '<?= $view ?>'
    },
    chartInstances: {},
    state: { chartsInitialized: false, chartjsLoaded: false }
};

// ========== FUNCIONES DE NAVEGACIÓN ==========
ProyectoApp.cambiarProyecto = function(proyectoId) {
    if (!proyectoId) return;
    window.location.href = `?proyecto=${proyectoId}&view=${ProyectoApp.config.view}`;
};

ProyectoApp.cambiarVista = function(vista) {
    if (!vista) return;
    window.location.href = `?proyecto=${ProyectoApp.config.proyecto_id}&view=${vista}`;
};

// ========== FILTROS ==========
ProyectoApp.filtrarTabla = function() {
    const filtros = {
        tipo: document.getElementById('filtroTipo')?.value || '',
        estado: document.getElementById('filtroEstado')?.value || '',
        fase: document.getElementById('filtroFase')?.value || '',
        contrato: document.getElementById('filtroContrato')?.value || ''
    };
    
    const filas = document.querySelectorAll('#tablaTareas tbody tr');
    
    filas.forEach(fila => {
        let mostrar = true;
        if (filtros.tipo && fila.dataset.tipo !== filtros.tipo) mostrar = false;
        if (filtros.estado && fila.dataset.estado !== filtros.estado) mostrar = false;
        if (filtros.fase && fila.dataset.fase !== filtros.fase) mostrar = false;
        if (filtros.contrato && fila.dataset.contrato !== filtros.contrato) mostrar = false;
        fila.style.display = mostrar ? '' : 'none';
    });
};

// ========== NOTIFICACIONES ==========
ProyectoApp.mostrarNotificacion = function(mensaje, tipo = 'success', duracion = 4000) {
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    
    const iconos = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-circle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    };
    
    const colores = {
        'success': '#27ae60',
        'error': '#e74c3c', 
        'warning': '#f39c12',
        'info': '#3498db'
    };
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="${iconos[tipo]}" style="font-size: 18px;"></i>
            <div style="flex: 1;">${mensaje}</div>
            <button onclick="this.closest('.notification').remove()" 
                    style="background: none; border: none; color: inherit; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    Object.assign(notification.style, {
        position: 'fixed', top: '20px', right: '20px', minWidth: '300px', maxWidth: '500px',
        padding: '16px 20px', borderRadius: '8px', color: 'white', zIndex: '9999',
        transform: 'translateX(100%)', transition: 'transform 0.3s ease',
        backgroundColor: colores[tipo], boxShadow: '0 4px 12px rgba(0,0,0,0.2)', fontSize: '14px'
    });
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.parentNode && notification.remove(), 300);
    }, duracion);
};

// ========== GESTIÓN DE CHART.JS ==========
ProyectoApp.verifyChartJS = function() {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const maxAttempts = 10;
        
        function check() {
            if (typeof Chart !== 'undefined') {
                console.log('✅ Chart.js disponible');
                ProyectoApp.state.chartjsLoaded = true;
                resolve(true);
                return;
            }
            
            attempts++;
            if (attempts >= maxAttempts) {
                console.error('❌ Chart.js no disponible');
                reject(new Error('Chart.js no disponible'));
                return;
            }
            
            console.log(`⏳ Esperando Chart.js... ${attempts}/${maxAttempts}`);
            setTimeout(check, 200);
        }
        
        check();
    });
};

ProyectoApp.destroyChart = function(chartId) {
    if (ProyectoApp.chartInstances[chartId]) {
        try {
            ProyectoApp.chartInstances[chartId].destroy();
            delete ProyectoApp.chartInstances[chartId];
        } catch (error) {
            console.error(`Error destruyendo gráfico ${chartId}:`, error);
        }
    }
};

// ========== INICIALIZACIÓN DE GRÁFICOS ==========
ProyectoApp.initializeCharts = async function() {
    if (ProyectoApp.state.chartsInitialized) return;
    
    try {
        await ProyectoApp.verifyChartJS();
        
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#666';
        
        const view = ProyectoApp.config.view;
        
        if (view === 'dashboard') {
            ProyectoApp.createDashboardChart();
        } else if (view === 'reportes') {
            setTimeout(() => {
                ProyectoApp.createTypeChart();
                ProyectoApp.createStatusChart();
                if (ProyectoApp.config.estadisticas_por_fase.length > 0) {
                    ProyectoApp.createPhaseChart();
                }
            }, 100);
        }
        
        ProyectoApp.state.chartsInitialized = true;
        console.log('✅ Gráficos inicializados');
        
    } catch (error) {
        console.error('❌ Error inicializando gráficos:', error);
    }
};

// ========== GRÁFICOS ==========
ProyectoApp.createDashboardChart = function() {
    const canvas = document.getElementById('graficoProgreso');
    if (!canvas) return;

    ProyectoApp.destroyChart('dashboard');
    const stats = ProyectoApp.config.stats;
    
    try {
        ProyectoApp.chartInstances.dashboard = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Completadas', 'En Proceso', 'Pendientes'],
                datasets: [{
                    data: [
                        parseInt(stats.completadas) || 0,
                        parseInt(stats.en_proceso) || 0,
                        parseInt(stats.pendientes) || 0
                    ],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c'],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: 'Distribución de Tareas' }
                },
                cutout: '60%'
            }
        });
    } catch (error) {
        console.error('Error creando gráfico dashboard:', error);
    }
};

ProyectoApp.createTypeChart = function() {
    const canvas = document.getElementById('graficoTipos');
    if (!canvas) return;

    ProyectoApp.destroyChart('tipos');
    const tipos = ProyectoApp.config.estadisticas_por_tipo || [];
    
    try {
        ProyectoApp.chartInstances.tipos = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: tipos.map(t => t.tipo),
                datasets: [{
                    label: 'Progreso (%)',
                    data: tipos.map(t => parseFloat(t.avance_promedio) || 0),
                    backgroundColor: ['#2c3e50', '#3498db', '#f39c12']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
    } catch (error) {
        console.error('Error creando gráfico tipos:', error);
    }
};

ProyectoApp.createStatusChart = function() {
    const canvas = document.getElementById('graficoEstados');
    if (!canvas) return;

    ProyectoApp.destroyChart('estados');
    const stats = ProyectoApp.config.stats;
    
    try {
        ProyectoApp.chartInstances.estados = new Chart(canvas, {
            type: 'pie',
            data: {
                labels: ['Listo', 'En Proceso', 'Pendiente'],
                datasets: [{
                    data: [
                        parseInt(stats.completadas) || 0,
                        parseInt(stats.en_proceso) || 0,
                        parseInt(stats.pendientes) || 0
                    ],
                    backgroundColor: ['#27ae60', '#f39c12', '#e74c3c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    } catch (error) {
        console.error('Error creando gráfico estados:', error);
    }
};

ProyectoApp.createPhaseChart = function() {
    const canvas = document.getElementById('graficoFases');
    if (!canvas) return;

    ProyectoApp.destroyChart('fases');
    const fases = ProyectoApp.config.estadisticas_por_fase || [];
    if (fases.length === 0) return;

    try {
        ProyectoApp.chartInstances.fases = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: fases.map(f => f.fase_principal.length > 25 ? 
                    f.fase_principal.substring(0, 25) + '...' : f.fase_principal),
                datasets: [{
                    label: 'Progreso (%)',
                    data: fases.map(f => parseFloat(f.avance_promedio) || 0),
                    backgroundColor: 'rgba(52, 152, 219, 0.8)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { beginAtZero: true, max: 100 } }
            }
        });
    } catch (error) {
        console.error('Error creando gráfico fases:', error);
    }
};

// ========== FUNCIONES DE TAREAS (CORREGIDAS) ==========
ProyectoApp.agregarTarea = function() {
    const form = document.getElementById('formNuevaTarea');
    if (!form) {
        ProyectoApp.mostrarNotificacion('Formulario no encontrado', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    if (!formData.get('nombre')?.trim()) {
        ProyectoApp.mostrarNotificacion('El nombre de la tarea es requerido', 'error');
        return;
    }
    
    const data = {
        action: 'crear',
        nombre: formData.get('nombre').trim(),
        tipo: formData.get('tipo') || 'Tarea',
        duracion_dias: parseFloat(formData.get('duracion_dias')) || 1,
        estado: formData.get('estado') || 'Pendiente',
        porcentaje_avance: parseFloat(formData.get('porcentaje_avance')) || 0,
        proyecto_id: parseInt(formData.get('proyecto_id')),
        contrato: formData.get('contrato') || 'Normal',
        peso_actividad: parseFloat(formData.get('peso_actividad')) || 0,
        fase_principal: formData.get('fase_principal')?.trim() || null
    };

    const submitBtn = document.querySelector('#modalNuevaTarea .btn-primary');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        submitBtn.disabled = true;

        fetch('api/tareas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaTarea'));
                if (modal) modal.hide();
                
                form.reset();
                ProyectoApp.mostrarNotificacion('Tarea creada exitosamente', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ProyectoApp.mostrarNotificacion('Error al crear la tarea', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
};

ProyectoApp.editarTarea = function(tareaId) {
    if (!tareaId) {
        ProyectoApp.mostrarNotificacion('ID de tarea inválido', 'error');
        return;
    }
    
    const btn = document.querySelector(`button[onclick*="editarTarea(${tareaId})"]`);
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
    }
    
    fetch(`api/tareas.php?action=obtener_tarea&id=${tareaId}`)
        .then(response => response.json())
        .then(tarea => {
            if (tarea && tarea.id) {
                const campos = {
                    'editar_tarea_id': tarea.id,
                    'editar_nombre_tarea': tarea.nombre || '',
                    'editar_tipo_tarea': tarea.tipo || 'Tarea',
                    'editar_duracion_tarea': tarea.duracion_dias || 1,
                    'editar_estado_tarea': tarea.estado || 'Pendiente',
                    'editar_porcentaje_tarea': tarea.porcentaje_avance || 0,
                    'editar_contrato_tarea': tarea.contrato || 'Normal',
                    'editar_peso_actividad_tarea': parseFloat(tarea.peso_actividad || 0).toFixed(2),
                    'editar_fase_principal_tarea': tarea.fase_principal || ''
                };
                
                Object.entries(campos).forEach(([id, valor]) => {
                    const elemento = document.getElementById(id);
                    if (elemento) elemento.value = valor;
                });
                
                const porcentajeValor = document.getElementById('editarPorcentajeValor');
                if (porcentajeValor) {
                    porcentajeValor.textContent = (tarea.porcentaje_avance || 0) + '%';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('modalEditarTarea'));
                modal.show();
            } else {
                throw new Error('Datos de tarea no válidos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ProyectoApp.mostrarNotificacion('Error al cargar los datos de la tarea', 'error');
        })
        .finally(() => {
            if (btn) {
                btn.innerHTML = '<i class="fas fa-edit"></i>';
                btn.disabled = false;
            }
        });
};

ProyectoApp.guardarEdicionTarea = function() {
    const form = document.getElementById('formEditarTarea');
    if (!form) {
        ProyectoApp.mostrarNotificacion('Formulario de edición no encontrado', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    if (!formData.get('nombre')?.trim()) {
        ProyectoApp.mostrarNotificacion('El nombre de la tarea es requerido', 'error');
        return;
    }

    const pesoActividad = parseFloat(formData.get('peso_actividad')) || 0;
    if (pesoActividad < 0 || pesoActividad > 100) {
        ProyectoApp.mostrarNotificacion('El peso debe estar entre 0% y 100%', 'error');
        return;
    }
    
    const data = {
        action: 'actualizar_completa',
        id: parseInt(formData.get('id')),
        nombre: formData.get('nombre').trim(),
        tipo: formData.get('tipo'),
        duracion_dias: parseFloat(formData.get('duracion_dias')) || 1,
        estado: formData.get('estado'),
        porcentaje_avance: parseFloat(formData.get('porcentaje_avance')) || 0,
        contrato: formData.get('contrato') || 'Normal',
        peso_actividad: pesoActividad,
        fase_principal: formData.get('fase_principal')?.trim() || null
    };

    const submitBtn = document.querySelector('#modalEditarTarea .btn-warning');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        submitBtn.disabled = true;

        fetch('api/tareas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarTarea'));
                if (modal) modal.hide();
                
                ProyectoApp.mostrarNotificacion('Tarea actualizada exitosamente', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ProyectoApp.mostrarNotificacion('Error al actualizar la tarea', 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
};

ProyectoApp.eliminarTarea = function(tareaId) {
    if (!tareaId) {
        ProyectoApp.mostrarNotificacion('ID de tarea inválido', 'error');
        return;
    }
    
    if (!confirm('¿Está seguro de que desea eliminar esta tarea? Esta acción no se puede deshacer.')) {
        return;
    }
    
    const btn = document.querySelector(`button[onclick*="eliminarTarea(${tareaId})"]`);
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
    }
    
    fetch('api/tareas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'eliminar',
            id: tareaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            ProyectoApp.mostrarNotificacion('Tarea eliminada exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        ProyectoApp.mostrarNotificacion('Error al eliminar la tarea', 'error');
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-trash"></i>';
            btn.disabled = false;
        }
    });
};

// ========== FUNCIONES TEMPORALES PARA EVITAR ERRORES ==========
ProyectoApp.editarProyecto = function(id) { 
    console.log('editarProyecto:', id); 
};
ProyectoApp.duplicarProyecto = function(id) { 
    console.log('duplicarProyecto:', id); 
};
ProyectoApp.eliminarProyecto = function(id) { 
    console.log('eliminarProyecto:', id); 
};
ProyectoApp.importarDatosCafeto = function(id) { 
    console.log('importarDatosCafeto:', id); 
};
ProyectoApp.distribuirPesoAutomatico = function(id) { 
    console.log('distribuirPesoAutomatico:', id); 
};
ProyectoApp.corregirPesosAutomaticamente = function(id) { 
    console.log('corregirPesosAutomaticamente:', id); 
};
ProyectoApp.exportarProyecto = function(id, formato) { 
    window.open(`api/exportar.php?action=proyecto&proyecto_id=${id}&formato=${formato}`, '_blank');
};
ProyectoApp.exportarReporte = function(id) { 
    window.open(`api/exportar.php?action=reporte_proyecto&proyecto_id=${id}&formato=html`, '_blank');
};

// ========== EVENTOS ==========
window.addEventListener('beforeunload', function() {
    Object.keys(ProyectoApp.chartInstances).forEach(chartId => {
        ProyectoApp.destroyChart(chartId);
    });
});

window.addEventListener('resize', function() {
    Object.values(ProyectoApp.chartInstances).forEach(chart => {
        if (chart && typeof chart.resize === 'function') {
            chart.resize();
        }
    });
});

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando ProyectoApp...');
    
    ProyectoApp.initializeCharts().catch(error => {
        console.error('Error en inicialización de gráficos:', error);
    });
    
    console.log('✅ ProyectoApp inicializado correctamente');
});

// ========== FUNCIONES GLOBALES ==========
window.cambiarProyecto = ProyectoApp.cambiarProyecto;
window.cambiarVista = ProyectoApp.cambiarVista;
window.filtrarTabla = ProyectoApp.filtrarTabla;
window.editarTarea = ProyectoApp.editarTarea;
window.eliminarTarea = ProyectoApp.eliminarTarea;
window.agregarTarea = ProyectoApp.agregarTarea;
window.guardarEdicionTarea = ProyectoApp.guardarEdicionTarea;
window.mostrarNotificacion = ProyectoApp.mostrarNotificacion;

console.log('📊 Sistema centralizado cargado correctamente');
</script>

<!-- Cargar proyecto-functions.js directamente -->
<script src="js/proyecto-functions.js"></script>

<?php include 'includes/footer.php'; ?>
