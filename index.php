<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$proyectoManager = new ProyectoManager($db);

// Obtener proyecto actual
$proyecto_actual_id = $_GET['proyecto'] ?? $_SESSION['proyecto_actual'] ?? 1;
$_SESSION['proyecto_actual'] = $proyecto_actual_id;

$proyecto_actual = $proyectoManager->obtenerProyecto($proyecto_actual_id);
$proyectos = $proyectoManager->obtenerProyectos();

// Obtener estadísticas del proyecto actual
$stats = $proyectoManager->obtenerEstadisticasProyecto($proyecto_actual_id);
$tareas = $proyectoManager->obtenerTareasProyecto($proyecto_actual_id);

$view = $_GET['view'] ?? 'dashboard';

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Selector de proyecto -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3"><i class="fas fa-building"></i> <?= htmlspecialchars($proyecto_actual['nombre']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($proyecto_actual['descripcion']) ?></p>
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
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Completadas</h5>
                        <div class="metric-number"><?= $stats['completadas'] ?></div>
                    </div>
                    <i class="fas fa-check-circle fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>En Proceso</h5>
                        <div class="metric-number"><?= $stats['en_proceso'] ?></div>
                    </div>
                    <i class="fas fa-clock fa-3x opacity-50"></i>
                </div>
            </div>

            <div class="metric-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5>Pendientes</h5>
                        <div class="metric-number"><?= $stats['pendientes'] ?></div>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Progreso general -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Progreso General</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Avance del Proyecto: <?= number_format($stats['avance_promedio'], 1) ?>%</label>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $stats['avance_promedio'] ?>%" 
                                     aria-valuenow="<?= $stats['avance_promedio'] ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    <?= number_format($stats['avance_promedio'], 1) ?>%
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Cliente:</strong> <?= htmlspecialchars($proyecto_actual['cliente']) ?></p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge bg-<?= strtolower($proyecto_actual['estado']) ?>">
                                        <?= $proyecto_actual['estado'] ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fecha Inicio:</strong> <?= $proyecto_actual['fecha_inicio'] ? date('d/m/Y', strtotime($proyecto_actual['fecha_inicio'])) : 'No definida' ?></p>
                                <p><strong>Fecha Estimada:</strong> <?= $proyecto_actual['fecha_fin_estimada'] ? date('d/m/Y', strtotime($proyecto_actual['fecha_fin_estimada'])) : 'No definida' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Distribución</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoProgreso" width="300" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas recientes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Tareas Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarea</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Última Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recent_query = "SELECT * FROM tareas WHERE proyecto_id = ? ORDER BY updated_at DESC LIMIT 10";
                                    $recent_stmt = $db->prepare($recent_query);
                                    $recent_stmt->execute([$proyecto_actual_id]);
                                    $recent_tareas = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($recent_tareas as $tarea): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tarea['nombre']) ?></td>
                                            <td><span class="badge badge-<?= strtolower($tarea['tipo']) ?>"><?= $tarea['tipo'] ?></span></td>
                                            <td><span class="estado-<?= strtolower(str_replace(' ', '', $tarea['estado'])) ?>">
                                                <i class="fas fa-circle"></i> <?= $tarea['estado'] ?>
                                            </span></td>
                                            <td>
                                                <div class="progress" style="height: 15px;">
                                                    <div class="progress-bar" style="width: <?= $tarea['porcentaje_avance'] ?>%">
                                                        <?= $tarea['porcentaje_avance'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($tarea['updated_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'proyectos'): ?>
        <!-- Gestión de Proyectos -->
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
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($proyecto['nombre']) ?></h5>
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

        <!-- Modal para nuevo proyecto -->
        <div class="modal fade" id="modalNuevoProyecto" tabindex="-1" aria-labelledby="modalNuevoProyectoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalNuevoProyectoLabel">
                            <i class="fas fa-plus"></i> Crear Nuevo Proyecto
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formNuevoProyecto">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre_proyecto" class="form-label">Nombre del Proyecto</label>
                                        <input type="text" class="form-control" id="nombre_proyecto" name="nombre" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cliente_proyecto" class="form-label">Cliente</label>
                                        <input type="text" class="form-control" id="cliente_proyecto" name="cliente" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_proyecto" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_proyecto" name="descripcion" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha_inicio_proyecto" class="form-label">Fecha de Inicio</label>
                                        <input type="date" class="form-control" id="fecha_inicio_proyecto" name="fecha_inicio">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fecha_fin_proyecto" class="form-label">Fecha Fin Estimada</label>
                                        <input type="date" class="form-control" id="fecha_fin_proyecto" name="fecha_fin_estimada">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="presupuesto_proyecto" class="form-label">Presupuesto</label>
                                        <input type="number" class="form-control" id="presupuesto_proyecto" name="presupuesto" step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="estado_proyecto" class="form-label">Estado</label>
                                        <select class="form-select" id="estado_proyecto" name="estado" required>
                                            <option value="Activo">Activo</option>
                                            <option value="Pausado">Pausado</option>
                                            <option value="Terminado">Terminado</option>
                                            <option value="Cancelado">Cancelado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="plantilla_proyecto" class="form-label">Copiar tareas desde proyecto existente</label>
                                <select class="form-select" id="plantilla_proyecto" name="plantilla_proyecto">
                                    <option value="">No copiar tareas</option>
                                    <?php foreach ($proyectos as $proyecto): ?>
                                        <option value="<?= $proyecto['id'] ?>">
                                            <?= htmlspecialchars($proyecto['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Si seleccionas un proyecto, se copiarán todas sus tareas al nuevo proyecto.</div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="crearProyecto()">Crear Proyecto</button>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'tareas'): ?>
        <!-- Vista de Tareas (mantiene el código anterior pero filtrado por proyecto) -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tasks"></i> Tareas - <?= htmlspecialchars($proyecto_actual['nombre']) ?></h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de tareas del proyecto actual -->
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="tablaTareas">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Duración (días)</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tareas as $t): ?>
                                <tr data-estado="<?= $t['estado'] ?>" data-tipo="<?= $t['tipo'] ?>">
                                    <td><?= htmlspecialchars($t['nombre']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($t['tipo']) ?>">
                                            <?= $t['tipo'] ?>
                                        </span>
                                    </td>
                                    <td><?= $t['duracion_dias'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= strtolower($t['estado']) ?>">
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
                                        <button class="btn btn-sm btn-warning" onclick="mostrarModalEditar(<?= $t['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarTarea(<?= $t['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal para nueva tarea (modificado para incluir proyecto_id) -->
        <div class="modal fade" id="modalNuevaTarea" tabindex="-1" aria-labelledby="modalNuevaTareaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalNuevaTareaLabel">
                            <i class="fas fa-plus"></i> Nueva Tarea para <?= htmlspecialchars($proyecto_actual['nombre']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formNuevaTarea">
                            <input type="hidden" name="proyecto_id" value="<?= $proyecto_actual_id ?>">
                            <div class="mb-3">
                                <label for="nombre_tarea" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre_tarea" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_tarea" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo_tarea" name="tipo" required>
                                    <option value="Fase">Fase</option>
                                    <option value="Actividad">Actividad</option>
                                    <option value="Tarea">Tarea</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="duracion_tarea" class="form-label">Duración (días)</label>
                                <input type="number" class="form-control" id="duracion_tarea" name="duracion_dias" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="estado_tarea" class="form-label">Estado</label>
                                <select class="form-select" id="estado_tarea" name="estado" required>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Listo">Listo</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="porcentaje_tarea" class="form-label">Porcentaje de avance</label>
                                <input type="number" class="form-control" id="porcentaje_tarea" name="porcentaje_avance" min="0" max="100" value="0" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="agregarTarea()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'reportes'): ?>
        <!-- Reportes del proyecto actual -->
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-chart-bar"></i> Reportes - <?= htmlspecialchars($proyecto_actual['nombre']) ?></h2>
                <hr>
            </div>
        </div>

        <!-- Gráficos y reportes aquí -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Avance por Fase</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoFases" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list-alt"></i> Tareas por Estado</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoEstados" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
// Función para cambiar de proyecto
function cambiarProyecto(proyectoId) {
    window.location.href = '?proyecto=' + proyectoId + '&view=<?= $view ?>';
}

// Función para cambiar de vista manteniendo el proyecto actual
function cambiarVista(vista) {
    window.location.href = '?proyecto=<?= $proyecto_actual_id ?>&view=' + vista;
}

// Función para crear nuevo proyecto
function crearProyecto() {
    const form = document.getElementById('formNuevoProyecto');
    const formData = new FormData(form);
    
    const data = {
        action: 'crear_proyecto',
        nombre: formData.get('nombre'),
        descripcion: formData.get('descripcion'),
        fecha_inicio: formData.get('fecha_inicio'),
        fecha_fin_estimada: formData.get('fecha_fin_estimada'),
        cliente: formData.get('cliente'),
        presupuesto: formData.get('presupuesto'),
        estado: formData.get('estado'),
        plantilla_proyecto: formData.get('plantilla_proyecto')
    };

    fetch('api/proyectos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Proyecto creado exitosamente');
            location.reload();
        } else {
            alert('Error al crear el proyecto: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear el proyecto');
    });
}

// Función para duplicar proyecto
function duplicarProyecto(proyectoId) {
    if (confirm('¿Desea duplicar este proyecto con todas sus tareas?')) {
        fetch('api/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'duplicar_proyecto',
                proyecto_id: proyectoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Proyecto duplicado exitosamente');
                location.reload();
            } else {
                alert('Error al duplicar el proyecto');
            }
        });
    }
}

// Función para eliminar proyecto
function eliminarProyecto(proyectoId) {
    if (confirm('¿Está seguro de que desea eliminar este proyecto? Esta acción no se puede deshacer.')) {
        fetch('api/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'eliminar_proyecto',
                proyecto_id: proyectoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Proyecto eliminado exitosamente');
                location.reload();
            } else {
                alert('Error al eliminar el proyecto');
            }
        });
    }
}

// Función para agregar tarea (modificada para incluir proyecto_id)
function agregarTarea() {
    const form = document.getElementById('formNuevaTarea');
    const formData = new FormData(form);
    
    const data = {
        action: 'crear',
        nombre: formData.get('nombre'),
        tipo: formData.get('tipo'),
        duracion_dias: formData.get('duracion_dias'),
        estado: formData.get('estado'),
        porcentaje_avance: formData.get('porcentaje_avance'),
        proyecto_id: formData.get('proyecto_id')
    };

    fetch('api/tareas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al crear la tarea');
        }
    });
}

// Gráfico de progreso para dashboard
<?php if ($view === 'dashboard'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficoProgreso');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completadas', 'En Proceso', 'Pendientes'],
                datasets: [{
                    data: [<?= $stats['completadas'] ?>, <?= $stats['en_proceso'] ?>, <?= $stats['pendientes'] ?>],
                    backgroundColor: [
                        '#27ae60',
                        '#f39c12',
                        '#e74c3c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>                    