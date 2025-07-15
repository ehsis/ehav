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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building"></i> Sistema Multi-Proyecto
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-folder"></i> Proyectos
                        </a>
                        <ul class="dropdown-menu">
                            <?php
                            // Obtener proyectos para el menú
                            if (isset($proyectos) && !empty($proyectos)) {
                                foreach ($proyectos as $p) {
                                    $active = ($p['id'] == $proyecto_actual_id) ? 'active' : '';
                                    echo '<li><a class="dropdown-item ' . $active . '" href="?proyecto=' . $p['id'] . '">' . 
                                         htmlspecialchars($p['nombre']) . '</a></li>';
                                }
                            }
                            ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?view=proyectos">
                                <i class="fas fa-plus"></i> Gestionar Proyectos
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="fas fa-project-diagram"></i> 
                            <?php echo isset($proyecto_actual) ? htmlspecialchars($proyecto_actual['nombre']) : 'Sin proyecto'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>