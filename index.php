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
            
            <!-- Progreso ponderado principal -->
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-chart-line me-2"></i>
                <strong>Progreso Ponderado: <?= number_format($stats['avance_promedio'], 2) ?>%</strong>
                <span class="ms-3 text-muted">
                    (Peso total: <?= number_format($stats['peso_total'], 4) ?>)
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <label class="form-label">Cambiar Proyecto:</label>
                    <select class="form-select" onchange="cambiarProyecto(this.value)">
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
                                    onclick="cambiarVista('dashboard')">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </button>
                            <button class="nav-link <?= ($view === 'tareas') ? 'active' : '' ?>" 
                                    onclick="cambiarVista('tareas')">
                                <i class="fas fa-tasks"></i> Tareas
                            </button>
                            <button class="nav-link <?= ($view === 'reportes') ? 'active' : '' ?>" 
                                    onclick="cambiarVista('reportes')">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </button>
                            <button class="nav-link <?= ($view === 'proyectos') ? 'active' : '' ?>" 
                                    onclick="cambiarVista('proyectos')">
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
                        <small class="text-light">Peso total: <?= number_format($stats['peso_total'], 4) ?></small>
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Completadas</h5>
                        <div class="metric-number"><?= $stats['completadas'] ?></div>
                        <small class="text-light">
                            Peso: <?= number_format($stats['avance_ponderado'], 4) ?>
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
                                <small class="text-muted">
                                    Peso: <?= number_format($tipo_stat['peso_total'], 4) ?> | 
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
                        <canvas id="graficoProgreso" width="300" height="300"></canvas>
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
                                                <span class="fw-bold"><?= number_format($fase['peso_fase'], 4) ?></span>
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
                            <button class="btn btn-sm btn-success" onclick="importarDatosCafeto(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-file-excel"></i> Datos Ejemplo
                            </button>
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
                                                <span class="fw-bold"><?= number_format($tarea['peso_actividad'], 4) ?></span>
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
                        <button class="btn btn-success" onclick="importarDatosCafeto(<?= $proyecto_actual_id ?>)">
                            <i class="fas fa-file-excel"></i> Datos Ejemplo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select class="form-select" id="filtroTipo" onchange="filtrarTabla()">
                    <option value="">Todos los tipos</option>
                    <option value="Fase">Fase</option>
                    <option value="Actividad">Actividad</option>
                    <option value="Tarea">Tarea</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroEstado" onchange="filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Listo">Listo</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroFase" onchange="filtrarTabla()">
                    <option value="">Todas las fases</option>
                    <?php 
                    $fases_disponibles = $proyectoManager->obtenerFasesPrincipales($proyecto_actual_id);
                    foreach ($fases_disponibles as $fase): ?>
                        <option value="<?= htmlspecialchars($fase) ?>"><?= htmlspecialchars($fase) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroContrato" onchange="filtrarTabla()">
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
                                                <span class="fw-bold"><?= number_format($t['peso_actividad'], 4) ?></span>
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
                                                    <button class="btn btn-sm btn-warning" onclick="editarTarea(<?= $t['id'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="eliminarTarea(<?= $t['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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
                                <h3 class="text-info"><?= number_format($stats['peso_total'], 4) ?></h3>
                                <p class="text-muted">Peso Total</p>
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
                        <canvas id="graficoTipos" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Distribución por Estado</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoEstados" height="300"></canvas>
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
                        <canvas id="graficoFases" height="150"></canvas>
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
                                            <td><?= number_format($fase_stat['peso_total'], 4) ?></td>
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
                            <button class="btn btn-outline-primary" onclick="exportarProyecto(<?= $proyecto_actual_id ?>, 'csv')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-outline-success" onclick="exportarProyecto(<?= $proyecto_actual_id ?>, 'json')">
                                <i class="fas fa-file-code"></i> JSON
                            </button>
                            <button class="btn btn-outline-info" onclick="exportarReporte(<?= $proyecto_actual_id ?>)">
                                <i class="fas fa-file-alt"></i> Reporte HTML
                            </button>
                            <button class="btn btn-outline-warning" onclick="exportarProyecto(<?= $proyecto_actual_id ?>, 'xml')">
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
                                <small class="text-muted">Peso total: <?= number_format($stats_proyecto['peso_total'], 4) ?></small>
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
                                <button class="btn btn-outline-warning btn-sm" onclick="editarProyecto(<?= $proyecto['id'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="duplicarProyecto(<?= $proyecto['id'] ?>)">
                                    <i class="fas fa-copy"></i> Duplicar
                                </button>
                                <?php if ($proyecto['id'] != $proyecto_actual_id): ?>
                                    <button class="btn btn-outline-danger btn-sm" onclick="eliminarProyecto(<?= $proyecto['id'] ?>)">
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

<!-- Variables JavaScript para los gráficos -->
<script>
// Variables globales con datos para los gráficos
window.chartData = {
    stats: <?= json_encode($stats) ?>,
    estadisticas_por_tipo: <?= json_encode($estadisticas_por_tipo) ?>,
    estadisticas_por_fase: <?= json_encode($estadisticas_por_fase) ?>,
    proyecto_id: <?= $proyecto_actual_id ?>
};

// Función para cambiar de proyecto
function cambiarProyecto(proyectoId) {
    window.location.href = '?proyecto=' + proyectoId + '&view=<?= $view ?>';
}

// Función para cambiar de vista
function cambiarVista(vista) {
    window.location.href = '?proyecto=<?= $proyecto_actual_id ?>&view=' + vista;
}

// Filtrar tabla de tareas
function filtrarTabla() {
    const filtroTipo = document.getElementById('filtroTipo')?.value || '';
    const filtroEstado = document.getElementById('filtroEstado')?.value || '';
    const filtroFase = document.getElementById('filtroFase')?.value || '';
    const filtroContrato = document.getElementById('filtroContrato')?.value || '';
    
    const filas = document.querySelectorAll('#tablaTareas tbody tr');
    
    filas.forEach(fila => {
        const tipo = fila.dataset.tipo;
        const estado = fila.dataset.estado;
        const fase = fila.dataset.fase;
        const contrato = fila.dataset.contrato;
        
        let mostrar = true;
        
        if (filtroTipo && tipo !== filtroTipo) mostrar = false;
        if (filtroEstado && estado !== filtroEstado) mostrar = false;
        if (filtroFase && fase !== filtroFase) mostrar = false;
        if (filtroContrato && contrato !== filtroContrato) mostrar = false;
        
        fila.style.display = mostrar ? '' : 'none';
    });
}

// Inicialización de gráficos cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar gráficos si Chart.js está disponible
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    } else {
        console.warn('Chart.js no está disponible. Los gráficos no se mostrarán.');
    }
});

function initializeCharts() {
    const view = '<?= $view ?>';
    
    if (view === 'dashboard') {
        createDashboardChart();
    } else if (view === 'reportes') {
        createReportCharts();
    }
}

// Gráfico del dashboard
function createDashboardChart() {
    const ctx = document.getElementById('graficoProgreso');
    if (!ctx) return;
    
    const stats = window.chartData.stats;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completadas', 'En Proceso', 'Pendientes'],
            datasets: [{
                data: [stats.completadas, stats.en_proceso, stats.pendientes],
                backgroundColor: [
                    '#27ae60',
                    '#f39c12',
                    '#e74c3c'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Distribución de Tareas'
                }
            }
        }
    });
}

// Gráficos de reportes
function createReportCharts() {
    createTypeChart();
    createStatusChart();
    if (window.chartData.estadisticas_por_fase.length > 0) {
        createPhaseChart();
    }
}

function createTypeChart() {
    const ctx = document.getElementById('graficoTipos');
    if (!ctx) return;
    
    const tipos = window.chartData.estadisticas_por_tipo;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: tipos.map(t => t.tipo),
            datasets: [{
                label: 'Progreso Ponderado (%)',
                data: tipos.map(t => parseFloat(t.avance_promedio)),
                backgroundColor: [
                    '#2c3e50',
                    '#3498db', 
                    '#f39c12'
                ],
                borderColor: [
                    '#34495e',
                    '#2980b9',
                    '#e67e22'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Progreso por Tipo de Tarea'
                }
            }
        }
    });
}

function createStatusChart() {
    const ctx = document.getElementById('graficoEstados');
    if (!ctx) return;
    
    const stats = window.chartData.stats;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Listo', 'En Proceso', 'Pendiente'],
            datasets: [{
                data: [stats.completadas, stats.en_proceso, stats.pendientes],
                backgroundColor: [
                    '#27ae60',
                    '#f39c12', 
                    '#e74c3c'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Estados de las Tareas'
                }
            }
        }
    });
}

function createPhaseChart() {
    const ctx = document.getElementById('graficoFases');
    if (!ctx) return;
    
    const fases = window.chartData.estadisticas_por_fase;
    
    new Chart(ctx, {
        type: 'horizontalBar',
        data: {
            labels: fases.map(f => f.fase_principal),
            datasets: [{
                label: 'Progreso (%)',
                data: fases.map(f => parseFloat(f.avance_promedio)),
                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Progreso por Fase Principal'
                }
            }
        }
    });
}
</script>

<!-- Cargar scripts de funciones -->
<script src="js/proyecto-functions.js"></script>

<?php include 'includes/footer.php'; ?>
