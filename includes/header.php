<?php
// Obtener proyectos para el menú si no están definidos
if (!isset($proyectos) || empty($proyectos)) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        $proyectoManager = new ProyectoManager($db);
        $proyectos = $proyectoManager->obtenerProyectos();
    } else {
        $proyectos = [];
    }
}

// Variables por defecto si no están definidas
$proyecto_actual_id = $proyecto_actual_id ?? ($_SESSION['proyecto_actual'] ?? 1);
$proyecto_actual = $proyecto_actual ?? ['nombre' => 'Sistema', 'descripcion' => '', 'progreso_calculado' => 0];
$view = $view ?? 'dashboard';

// Obtener estadísticas rápidas del proyecto actual para el header
$progreso_actual = 0;
if ($proyecto_actual_id && isset($db)) {
    try {
        $query_progreso = "SELECT calcular_progreso_ponderado(?) as progreso";
        $stmt_progreso = $db->prepare($query_progreso);
        $stmt_progreso->execute([$proyecto_actual_id]);
        $result_progreso = $stmt_progreso->fetch(PDO::FETCH_ASSOC);
        $progreso_actual = $result_progreso['progreso'] ?? 0;
    } catch (Exception $e) {
        $progreso_actual = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Proyectos con Peso Ponderado - Controla el progreso de tus proyectos basado en la importancia de cada actividad">
    <meta name="keywords" content="gestión proyectos, peso ponderado, tareas, actividades, planificación">
    <meta name="author" content="Sistema de Gestión de Proyectos">
    
    <title>
        <?php 
        $page_titles = [
            'dashboard' => 'Dashboard',
            'tareas' => 'Gestión de Tareas',
            'reportes' => 'Reportes y Análisis',
            'proyectos' => 'Gestión de Proyectos',
            'calendario' => 'Vista de Calendario'
        ];
        $current_title = $page_titles[$view] ?? 'Sistema';
        echo $current_title . ' - ' . htmlspecialchars($proyecto_actual['nombre']) . ' - Gestión de Proyectos';
        ?>
    </title>
    
    <!-- External CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    
    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js" 
            integrity="sha384-6qiwyb4nERTEKXJy9DzYwdePxGNzOlnN9U+5aO6VdR4VyebdJ7WtF6fD2HzGm5LU" crossorigin="anonymous"></script>
    
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Additional meta tags -->
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Gestión Proyectos">
    
    <style>
        /* Header-specific styles */
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .navbar-nav .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 0.25rem;
            font-weight: 500;
        }
        
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            padding: 0.5rem;
        }
        
        .dropdown-item {
            transition: all 0.2s ease;
            border-radius: 6px;
            margin: 0.125rem 0;
            padding: 0.5rem 0.75rem;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--info-color));
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .navbar-text {
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .progreso-badge {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(3px);
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid rgba(255, 255, 255, 0.3);
            border-top: 6px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Notification badge for updates */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Skip link for accessibility */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 10000;
            font-weight: 600;
        }
        
        .skip-link:focus {
            top: 6px;
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Skip link for accessibility -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center text-white">
            <div class="loading-spinner mb-3"></div>
            <div class="h5">Cargando...</div>
            <div class="text-muted">Procesando información del proyecto</div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-project-diagram me-2"></i>
                <span>Sistema Multi-Proyecto</span>
                <small class="ms-2 opacity-75 d-none d-md-inline">v2.0</small>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Menú de Proyectos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarProyectos" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-folder me-1"></i>
                            <span>Proyectos</span>
                            <?php if (count($proyectos) > 0): ?>
                                <span class="badge bg-light text-primary ms-2"><?= count($proyectos) ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarProyectos">
                            <?php if (!empty($proyectos)): ?>
                                <li><h6 class="dropdown-header"><i class="fas fa-list me-1"></i>Proyectos Activos</h6></li>
                                <?php foreach ($proyectos as $p): ?>
                                    <?php $active = ($p['id'] == $proyecto_actual_id) ? 'active' : ''; ?>
                                    <li>
                                        <a class="dropdown-item <?= $active ?>" href="?proyecto=<?= $p['id'] ?>&view=<?= $view ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-project-diagram me-2"></i>
                                                    <span><?= htmlspecialchars($p['nombre']) ?></span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($active): ?>
                                                        <i class="fas fa-check text-success me-1"></i>
                                                    <?php endif; ?>
                                                    <small class="progreso-badge">
                                                        <?= number_format($p['progreso_calculado'] ?? 0, 1) ?>%
                                                    </small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li><span class="dropdown-item-text text-muted">No hay proyectos disponibles</span></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="?view=proyectos">
                                    <i class="fas fa-cog me-2"></i>Gestionar Proyectos
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Menú de Vistas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarVistas" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-th-large me-1"></i>
                            <span>Vistas</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-eye me-1"></i>Vistas Disponibles</h6></li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'dashboard') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    <small class="text-muted ms-auto">Resumen general</small>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'tareas') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=tareas">
                                    <i class="fas fa-tasks me-2"></i>Gestión de Tareas
                                    <small class="text-muted ms-auto">CRUD completo</small>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'reportes') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=reportes">
                                    <i class="fas fa-chart-bar me-2"></i>Reportes y Análisis
                                    <small class="text-muted ms-auto">Peso ponderado</small>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Acciones Rápidas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarAcciones" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt me-1"></i>
                            <span>Acciones</span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><h6 class="dropdown-header"><i class="fas fa-lightning-bolt me-1"></i>Acciones Rápidas</h6></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="mostrarModalNuevaTarea()">
                                    <i class="fas fa-plus me-2"></i>Nueva Tarea
                                    <small class="text-muted ms-auto">Ctrl+N</small>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="mostrarModalNuevoProyecto()">
                                    <i class="fas fa-folder-plus me-2"></i>Nuevo Proyecto
                                    <small class="text-muted ms-auto">Ctrl+P</small>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header"><i class="fas fa-download me-1"></i>Exportación</h6></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportarProyecto(<?= $proyecto_actual_id ?>, 'csv')">
                                    <i class="fas fa-file-csv me-2"></i>Exportar CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportarProyecto(<?= $proyecto_actual_id ?>, 'json')">
                                    <i class="fas fa-file-code me-2"></i>Exportar JSON
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportarReporte(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-file-pdf me-2"></i>Reporte HTML
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="importarDatosCafeto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-upload me-2"></i>Importar Datos Cafeto
                                    <small class="text-muted ms-auto">Ejemplo</small>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <!-- Información del proyecto actual -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarProyectoActual" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="navbar-text me-2 d-flex align-items-center">
                                <i class="fas fa-project-diagram me-2"></i>
                                <span class="d-none d-lg-inline"><?= htmlspecialchars($proyecto_actual['nombre']) ?></span>
                                <span class="d-lg-none">Proyecto</span>
                                <span class="progreso-badge ms-2">
                                    <?= number_format($progreso_actual, 1) ?>%
                                </span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header d-flex align-items-center">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Información del Proyecto
                                </h6>
                            </li>
                            <li>
                                <div class="dropdown-item-text">
                                    <div class="fw-bold"><?= htmlspecialchars($proyecto_actual['nombre']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($proyecto_actual['descripcion'] ?? 'Sin descripción') ?>
                                    </small>
                                </div>
                            </li>
                            <li>
                                <div class="dropdown-item-text">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Progreso Ponderado:</small>
                                        <span class="progreso-badge"><?= number_format($progreso_actual, 1) ?>%</span>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="editarProyecto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-edit me-2"></i>Editar Proyecto
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="duplicarProyecto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-copy me-2"></i>Duplicar Proyecto
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="recalcularProgreso(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-sync-alt me-2"></i>Recalcular Progreso
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="?view=proyectos">
                                    <i class="fas fa-cog me-2"></i>Configuración
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="bg-light py-2 border-bottom">
        <div class="container-fluid">
            <ol class="breadcrumb mb-0 d-flex align-items-center">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none d-flex align-items-center">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="?proyecto=<?= $proyecto_actual_id ?>" class="text-decoration-none">
                        <?= htmlspecialchars($proyecto_actual['nombre']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active d-flex align-items-center" aria-current="page">
                    <?php
                    $view_icons = [
                        'dashboard' => 'fas fa-tachometer-alt',
                        'tareas' => 'fas fa-tasks',
                        'reportes' => 'fas fa-chart-bar',
                        'proyectos' => 'fas fa-folder',
                        'calendario' => 'fas fa-calendar'
                    ];
                    $view_names = [
                        'dashboard' => 'Dashboard',
                        'tareas' => 'Gestión de Tareas',
                        'reportes' => 'Reportes y Análisis',
                        'proyectos' => 'Gestión de Proyectos',
                        'calendario' => 'Vista de Calendario'
                    ];
                    $current_icon = $view_icons[$view] ?? 'fas fa-file';
                    $current_name = $view_names[$view] ?? ucfirst($view);
                    ?>
                    <i class="<?= $current_icon ?> me-1"></i>
                    <?= $current_name ?>
                </li>
            </ol>
        </div>
    </nav>

    <!-- Main content wrapper -->
    <main id="main-content" role="main">

    <script>
        // Header JavaScript functions
        
        // Funciones para los modales desde el menú
        function mostrarModalNuevaTarea() {
            const modal = document.getElementById('modalNuevaTarea');
            if (modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                mostrarNotificacion('Modal de nueva tarea no encontrado', 'error');
            }
        }

        function mostrarModalNuevoProyecto() {
            const modal = document.getElementById('modalNuevoProyecto');
            if (modal) {
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            } else {
                mostrarNotificacion('Modal de nuevo proyecto no encontrado', 'error');
            }
        }

        // Función para mostrar/ocultar loading
        function showLoading(mensaje = 'Cargando...') {
            const overlay = document.getElementById('loadingOverlay');
            const messageEl = overlay.querySelector('.h5');
            if (messageEl) messageEl.textContent = mensaje;
            overlay.style.display = 'flex';
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'none';
        }

        // Función para exportar reportes
        function exportarReporte(proyectoId) {
            showLoading('Generando reporte...');
            window.open(`api/exportar.php?action=reporte_proyecto&proyecto_id=${proyectoId}&formato=html`, '_blank');
            setTimeout(hideLoading, 2000);
        }

        // Función para importar datos del proyecto Cafeto
        function importarDatosCafeto(proyectoId) {
            if (confirm('¿Desea importar los datos de ejemplo del proyecto Cafeto? Esto reemplazará las tareas existentes.')) {
                showLoading('Importando datos de ejemplo...');
                
                fetch('api/proyectos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'importar_excel_cafeto',
                        proyecto_id: proyectoId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        mostrarNotificacion('Datos del proyecto Cafeto importados exitosamente', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarNotificacion('Error al importar datos: ' + (data.message || 'Error desconocido'), 'error');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    mostrarNotificacion('Error al importar datos', 'error');
                });
            }
        }

        // Función para recalcular progreso
        function recalcularProgreso(proyectoId) {
            showLoading('Recalculando progreso...');
            
            fetch('api/proyectos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'recalcular_progreso',
                    proyecto_id: proyectoId
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    mostrarNotificacion('Progreso recalculado exitosamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarNotificacion('Error al recalcular progreso', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                mostrarNotificacion('Error al recalcular progreso', 'error');
            });
        }

        // Interceptar navegación para mostrar loading
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href*="?"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Solo mostrar loading para enlaces de navegación, no para acciones
                    if (!this.getAttribute('onclick') && 
                        !this.getAttribute('data-bs-toggle') && 
                        !this.getAttribute('target') &&
                        this.href.includes('proyecto=')) {
                        showLoading('Cargando vista...');
                    }
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey) {
                    switch(e.key) {
                        case 'n':
                            e.preventDefault();
                            mostrarModalNuevaTarea();
                            break;
                        case 'p':
                            e.preventDefault();
                            mostrarModalNuevoProyecto();
                            break;
                    }
                }
            });
        });

        // Ocultar loading al cargar la página
        window.addEventListener('load', function() {
            hideLoading();
        });

        // Función básica de notificación (se sobrescribe en proyecto-functions.js)
        if (typeof mostrarNotificacion === 'undefined') {
            window.mostrarNotificacion = function(mensaje, tipo = 'info') {
                alert(mensaje); // Fallback básico
            };
        }

        // Función básica de exportación (se sobrescribe en proyecto-functions.js)
        if (typeof exportarProyecto === 'undefined') {
            window.exportarProyecto = function(proyectoId, formato = 'csv') {
                window.open(`api/exportar.php?action=proyecto&proyecto_id=${proyectoId}&formato=${formato}`, '_blank');
            };
        }
    </script>
