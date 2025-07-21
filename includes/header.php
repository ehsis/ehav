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
$proyecto_actual = $proyecto_actual ?? ['nombre' => 'Sistema', 'descripcion' => ''];
$view = $view ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos - Sistema Multi-Proyecto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Estilos adicionales para mejorar la navegación */
        .navbar-brand {
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            transition: all 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .dropdown-item {
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        .navbar-text {
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #f3f3f3;
            border-top: 6px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Overlay de carga -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center text-white">
            <div class="loading-spinner mb-3"></div>
            <div>Cargando...</div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building"></i> Sistema Multi-Proyecto
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Menú de Proyectos -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarProyectos" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-folder"></i> Proyectos
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarProyectos">
                            <?php if (!empty($proyectos)): ?>
                                <?php foreach ($proyectos as $p): ?>
                                    <?php $active = ($p['id'] == $proyecto_actual_id) ? 'active' : ''; ?>
                                    <li>
                                        <a class="dropdown-item <?= $active ?>" href="?proyecto=<?= $p['id'] ?>&view=<?= $view ?>">
                                            <i class="fas fa-project-diagram me-2"></i>
                                            <?= htmlspecialchars($p['nombre']) ?>
                                            <?php if ($active): ?>
                                                <i class="fas fa-check float-end text-success"></i>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="?view=proyectos">
                                    <i class="fas fa-plus me-2"></i> Gestionar Proyectos
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Menú de Vistas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarVistas" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-th-large"></i> Vistas
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?= ($view === 'dashboard') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'tareas') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=tareas">
                                    <i class="fas fa-tasks me-2"></i> Tareas
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'reportes') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=reportes">
                                    <i class="fas fa-chart-bar me-2"></i> Reportes
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= ($view === 'calendario') ? 'active' : '' ?>" 
                                   href="?proyecto=<?= $proyecto_actual_id ?>&view=calendario">
                                    <i class="fas fa-calendar me-2"></i> Calendario
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Acciones Rápidas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarAcciones" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bolt"></i> Acciones
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="#" onclick="mostrarModalNuevaTarea()">
                                    <i class="fas fa-plus me-2"></i> Nueva Tarea
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="mostrarModalNuevoProyecto()">
                                    <i class="fas fa-folder-plus me-2"></i> Nuevo Proyecto
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportarProyecto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-download me-2"></i> Exportar Proyecto
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="importarDatos()">
                                    <i class="fas fa-upload me-2"></i> Importar Datos
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <!-- Información del proyecto actual -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarProyectoActual" role="button" data-bs-toggle="dropdown">
                            <span class="navbar-text me-2">
                                <i class="fas fa-project-diagram"></i> 
                                <?= htmlspecialchars($proyecto_actual['nombre']) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="fas fa-info-circle"></i> Información del Proyecto
                                </h6>
                            </li>
                            <li>
                                <div class="dropdown-item-text">
                                    <small class="text-muted">
                                        <?= htmlspecialchars($proyecto_actual['descripcion'] ?? 'Sin descripción') ?>
                                    </small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="editarProyecto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-edit me-2"></i> Editar Proyecto
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="duplicarProyecto(<?= $proyecto_actual_id ?>)">
                                    <i class="fas fa-copy me-2"></i> Duplicar Proyecto
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?view=proyectos">
                                    <i class="fas fa-cog me-2"></i> Configuración
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-2">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-home"></i> Inicio
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="?proyecto=<?= $proyecto_actual_id ?>" class="text-decoration-none">
                        <?= htmlspecialchars($proyecto_actual['nombre']) ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php
                    $view_names = [
                        'dashboard' => 'Dashboard',
                        'tareas' => 'Gestión de Tareas',
                        'reportes' => 'Reportes y Análisis',
                        'proyectos' => 'Gestión de Proyectos',
                        'calendario' => 'Vista de Calendario'
                    ];
                    echo $view_names[$view] ?? ucfirst($view);
                    ?>
                </li>
            </ol>
        </div>
    </nav>

    <script>
        // Funciones para los modales desde el menú
        function mostrarModalNuevaTarea() {
            const modal = new bootstrap.Modal(document.getElementById('modalNuevaTarea'));
            modal.show();
        }

        function mostrarModalNuevoProyecto() {
            const modal = new bootstrap.Modal(document.getElementById('modalNuevoProyecto'));
            modal.show();
        }

        // Función para mostrar/ocultar loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Intercepiar navegación para mostrar loading
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href*="?"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!this.getAttribute('onclick') && !this.getAttribute('data-bs-toggle')) {
                        showLoading();
                    }
                });
            });
        });

        // Ocultar loading al cargar la página
        window.addEventListener('load', function() {
            hideLoading();
        });

        // Función para exportar proyecto (placeholder)
        function exportarProyecto(proyectoId) {
            showLoading();
            
            // Simular proceso de exportación
            setTimeout(() => {
                hideLoading();
                mostrarNotificacion('Función de exportación en desarrollo', 'info');
            }, 2000);
        }

        // Función para importar datos (placeholder)
        function importarDatos() {
            mostrarNotificacion('Función de importación en desarrollo', 'info');
        }
    </script>
