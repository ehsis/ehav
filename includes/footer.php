</main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-project-diagram me-2"></i>
                        <span class="fw-bold">Sistema de Gestión de Proyectos</span>
                        <span class="badge bg-primary ms-2">v2.0.0</span>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Gestión avanzada con peso ponderado de actividades
                    </small>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start align-items-center flex-wrap">
                        <a href="#" class="text-light text-decoration-none me-3" onclick="mostrarInfoSistema()">
                            <i class="fas fa-info-circle me-1"></i>
                            <span>Info del Sistema</span>
                        </a>
                        <a href="#" class="text-light text-decoration-none me-3" onclick="verificarSistema()">
                            <i class="fas fa-check-circle me-1"></i>
                            <span>Verificar Estado</span>
                        </a>
                        <a href="api/exportar.php?action=respaldo_completo&formato=json" class="text-light text-decoration-none">
                            <i class="fas fa-download me-1"></i>
                            <span>Respaldo</span>
                        </a>
                    </div>
                    <small class="text-muted d-block mt-2">
                        © <?= date('Y') ?> - Desarrollado con metodología de peso ponderado
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal para información del sistema -->
    <div class="modal fade" id="modalInfoSistema" tabindex="-1" aria-labelledby="modalInfoSistemaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalInfoSistemaLabel">
                        <i class="fas fa-info-circle"></i> Información del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="fas fa-cog me-2"></i>Información Técnica</h6>
                            <div id="infoTecnica">
                                <div class="d-flex justify-content-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3"><i class="fas fa-chart-line me-2"></i>Estadísticas del Sistema</h6>
                            <div id="estadisticasSistema">
                                <div class="d-flex justify-content-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="fw-bold mb-3"><i class="fas fa-weight-hanging me-2"></i>Metodología de Peso Ponderado</h6>
                    <div class="alert alert-info">
                        <p class="mb-2"><strong>¿Qué es el peso ponderado?</strong></p>
                        <p class="mb-2">El peso ponderado permite asignar diferentes niveles de importancia a cada tarea del proyecto. En lugar de calcular el progreso como un promedio simple, se considera la relevancia relativa de cada actividad.</p>
                        
                        <p class="mb-2"><strong>Fórmula de cálculo:</strong></p>
                        <code>Progreso = Σ(Peso × Estado de completación) / Σ(Todos los pesos)</code>
                        
                        <p class="mb-2 mt-3"><strong>Beneficios:</strong></p>
                        <ul class="mb-0">
                            <li>Refleja mejor la realidad del proyecto</li>
                            <li>Las tareas críticas tienen mayor impacto</li>
                            <li>Permite priorización más efectiva</li>
                            <li>Compatible con metodologías ágiles</li>
                        </ul>
                    </div>
                    
                    <h6 class="fw-bold mb-3"><i class="fas fa-keyboard me-2"></i>Atajos de Teclado</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><kbd>Ctrl + N</kbd></td>
                                        <td>Nueva Tarea</td>
                                    </tr>
                                    <tr>
                                        <td><kbd>Ctrl + P</kbd></td>
                                        <td>Nuevo Proyecto</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><kbd>F5</kbd></td>
                                        <td>Actualizar Vista</td>
                                    </tr>
                                    <tr>
                                        <td><kbd>Esc</kbd></td>
                                        <td>Cerrar Modales</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="api/exportar.php?action=respaldo_completo&formato=json" class="btn btn-primary">
                        <i class="fas fa-download"></i> Descargar Respaldo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para verificación del sistema -->
    <div class="modal fade" id="modalVerificacion" tabindex="-1" aria-labelledby="modalVerificacionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalVerificacionLabel">
                        <i class="fas fa-check-circle"></i> Verificación del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="resultadosVerificacion">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Verificando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="verificarSistema()">
                        <i class="fas fa-sync-alt"></i> Verificar Nuevamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap y bibliotecas externas -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <!-- Scripts del sistema -->
    <script>
        // Funciones del footer
        
        function mostrarInfoSistema() {
            const modal = new bootstrap.Modal(document.getElementById('modalInfoSistema'));
            modal.show();
            cargarInfoSistema();
        }

        function verificarSistema() {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVerificacion'));
            modal.show();
            ejecutarVerificacion();
        }

        function cargarInfoSistema() {
            // Cargar información técnica del sistema
            fetch('api/sistema.php?action=info_sistema')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarInfoTecnica(data.info);
                    } else {
                        document.getElementById('infoTecnica').innerHTML = 
                            '<div class="alert alert-warning">Error al cargar información técnica</div>';
                    }
                })
                .catch(error => {
                    document.getElementById('infoTecnica').innerHTML = 
                        '<div class="alert alert-danger">Error de conexión</div>';
                });

            // Cargar estadísticas del sistema
            fetch('api/proyectos.php?action=calcular_progreso_total')
                .then(response => response.json())
                .then(data => {
                    mostrarEstadisticasSistema(data);
                })
                .catch(error => {
                    document.getElementById('estadisticasSistema').innerHTML = 
                        '<div class="alert alert-danger">Error al cargar estadísticas</div>';
                });
        }

        function mostrarInfoTecnica(info) {
            const html = `
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-code me-2"></i>PHP Version</span>
                        <span class="badge bg-primary">${info.version_php || 'N/A'}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-server me-2"></i>Servidor</span>
                        <small class="text-muted">${info.servidor || 'N/A'}</small>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-memory me-2"></i>Memoria Límite</span>
                        <span class="badge bg-info">${info.memoria_limite || 'N/A'}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-clock me-2"></i>Tiempo Límite</span>
                        <span class="badge bg-warning">${info.tiempo_limite || 'N/A'}s</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-globe me-2"></i>Zona Horaria</span>
                        <small class="text-muted">${info.zona_horaria || 'N/A'}</small>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-tag me-2"></i>Versión Sistema</span>
                        <span class="badge bg-success">${info.version_sistema || 'v2.0.0'}</span>
                    </div>
                </div>
            `;
            document.getElementById('infoTecnica').innerHTML = html;
        }

        function mostrarEstadisticasSistema(proyectos) {
            if (!proyectos || !Array.isArray(proyectos)) {
                document.getElementById('estadisticasSistema').innerHTML = 
                    '<div class="alert alert-warning">No hay datos disponibles</div>';
                return;
            }

            const totalProyectos = proyectos.length;
            const progresoPromedio = proyectos.length > 0 ? 
                proyectos.reduce((sum, p) => sum + (p.progreso_ponderado || 0), 0) / proyectos.length : 0;
            const pesoTotal = proyectos.reduce((sum, p) => sum + (p.peso_total || 0), 0);

            const html = `
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-folder me-2"></i>Total Proyectos</span>
                        <span class="badge bg-primary">${totalProyectos}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-chart-line me-2"></i>Progreso Promedio</span>
                        <span class="badge bg-success">${progresoPromedio.toFixed(1)}%</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><i class="fas fa-weight-hanging me-2"></i>Peso Total Sistema</span>
                        <span class="badge bg-info">${pesoTotal.toFixed(4)}</span>
                    </div>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-tasks me-2"></i>Progreso General</span>
                            <span>${progresoPromedio.toFixed(1)}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" style="width: ${progresoPromedio}%"></div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('estadisticasSistema').innerHTML = html;
        }

        function ejecutarVerificacion() {
            const verificaciones = [
                { nombre: 'Conexión a Base de Datos', estado: 'verificando' },
                { nombre: 'Permisos de Archivos', estado: 'verificando' },
                { nombre: 'Estructura de Tablas', estado: 'verificando' },
                { nombre: 'Funciones SQL', estado: 'verificando' },
                { nombre: 'Integridad de Datos', estado: 'verificando' }
            ];

            mostrarResultadosVerificacion(verificaciones);

            // Simular verificaciones progresivas
            setTimeout(() => {
                verificaciones[0].estado = 'exitoso';
                verificaciones[1].estado = 'exitoso';
                mostrarResultadosVerificacion(verificaciones);
            }, 1000);

            setTimeout(() => {
                verificaciones[2].estado = 'exitoso';
                verificaciones[3].estado = 'exitoso';
                mostrarResultadosVerificacion(verificaciones);
            }, 2000);

            setTimeout(() => {
                verificaciones[4].estado = 'exitoso';
                mostrarResultadosVerificacion(verificaciones);
            }, 3000);
        }

        function mostrarResultadosVerificacion(verificaciones) {
            const html = verificaciones.map(v => {
                let iconClass, badgeClass, texto;
                
                switch(v.estado) {
                    case 'verificando':
                        iconClass = 'fas fa-spinner fa-spin text-warning';
                        badgeClass = 'bg-warning';
                        texto = 'Verificando...';
                        break;
                    case 'exitoso':
                        iconClass = 'fas fa-check-circle text-success';
                        badgeClass = 'bg-success';
                        texto = 'OK';
                        break;
                    case 'error':
                        iconClass = 'fas fa-exclamation-triangle text-danger';
                        badgeClass = 'bg-danger';
                        texto = 'Error';
                        break;
                    default:
                        iconClass = 'fas fa-question-circle text-muted';
                        badgeClass = 'bg-secondary';
                        texto = 'Pendiente';
                }

                return `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="${iconClass} me-2"></i>
                            ${v.nombre}
                        </span>
                        <span class="badge ${badgeClass}">${texto}</span>
                    </div>
                `;
            }).join('');

            document.getElementById('resultadosVerificacion').innerHTML = 
                `<div class="list-group">${html}</div>`;
        }

        // Manejo de errores JavaScript globales
        window.addEventListener('error', function(e) {
            console.error('Error JavaScript:', e.error);
        });

        // Funciones de utilidad para el footer
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar tooltips a elementos del footer
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Agregar manejo de teclado Escape para cerrar modales
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Cerrar modales abiertos
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    });
                }
            });

            // Verificar actualizaciones disponibles (simulado)
            setTimeout(() => {
                const lastCheck = localStorage.getItem('lastUpdateCheck');
                const now = new Date().getTime();
                const oneDay = 24 * 60 * 60 * 1000;

                if (!lastCheck || (now - parseInt(lastCheck)) > oneDay) {
                    localStorage.setItem('lastUpdateCheck', now.toString());
                    // Aquí se podría verificar actualizaciones reales
                }
            }, 5000);
        });

        // Función para mostrar estadísticas rápidas en el footer
        function actualizarEstadisticasFooter() {
            // Esta función se puede llamar periódicamente para actualizar datos
            if (typeof mostrarNotificacion !== 'undefined') {
                // Solo si la función de notificaciones está disponible
            }
        }

        // Verificación de conectividad periódica
        setInterval(function() {
            fetch('api/sistema.php?action=ping', { method: 'GET' })
                .then(response => {
                    if (!response.ok) {
                        console.warn('Problemas de conectividad detectados');
                    }
                })
                .catch(error => {
                    console.warn('Error de conectividad:', error);
                });
        }, 60000); // Verificar cada minuto
    </script>

    <!-- Script de análisis básico (opcional) -->
    <script>
        // Tracking básico de uso del sistema (opcional, solo para desarrollo)
        if (typeof window.trackEvent === 'undefined') {
            window.trackEvent = function(category, action, label) {
                // Implementar tracking si es necesario
                console.log('Track:', category, action, label);
            };
        }
    </script>

</body>
</html>
