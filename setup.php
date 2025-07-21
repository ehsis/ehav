document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar tarea
    window.actualizarTarea = function(id) {
        const estado = document.getElementById('estado_' + id).value;
        const avance = document.getElementById('avance_' + id).value;
        
        fetch('api/tareas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'actualizar',
                id: id,
                estado: estado,
                porcentaje_avance: avance
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al actualizar la tarea');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la tarea');
        });
    };

    // Función para eliminar tarea
    window.eliminarTarea = function(id) {
        if (confirm('¿Está seguro de que desea eliminar esta tarea?')) {
            fetch('api/tareas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'eliminar',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar la tarea');
                }
            });
        }
    };

    // Agregar al archivo js/script.js existente

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    notification.textContent = mensaje;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Función para mostrar spinner de carga
function mostrarSpinner(contenedor) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = '<div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div>';
    
    contenedor.appendChild(spinner);
    return spinner;
}

// Función para ocultar spinner
function ocultarSpinner(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.parentNode.removeChild(spinner);
    }
}

// Función para actualizar estadísticas en tiempo real
function actualizarEstadisticas(proyectoId) {
    fetch(`api/tareas.php?action=estadisticas&proyecto_id=${proyectoId}`)
        .then(response => response.json())
        .then(data => {
            // Actualizar números en dashboard
            const estadisticas = {
                total: 0,
                completadas: 0,
                en_proceso: 0,
                pendientes: 0
            };
            
            data.forEach(item => {
                estadisticas.total += item.cantidad;
                switch(item.estado) {
                    case 'Listo':
                        estadisticas.completadas = item.cantidad;
                        break;
                    case 'En Proceso':
                        estadisticas.en_proceso = item.cantidad;
                        break;
                    case 'Pendiente':
                        estadisticas.pendientes = item.cantidad;
                        break;
                }
            });
            
            // Actualizar elementos en el DOM
            const totalElement = document.querySelector('.metric-card .metric-number');
            if (totalElement) totalElement.textContent = estadisticas.total;
            
            // Actualizar progreso
            const avancePromedio = ((estadisticas.completadas / estadisticas.total) * 100) || 0;
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = avancePromedio + '%';
                progressBar.textContent = avancePromedio.toFixed(1) + '%';
            }
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}

// Función para confirmar acciones
function confirmarAccion(mensaje, callback) {
    if (confirm(mensaje)) {
        callback();
    }
}

// Función para exportar datos del proyecto
function exportarProyecto(proyectoId, formato = 'json') {
    const spinner = mostrarSpinner(document.body);
    
    fetch(`api/exportar.php?proyecto_id=${proyectoId}&formato=${formato}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `proyecto_${proyectoId}.${formato}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            ocultarSpinner(spinner);
            mostrarNotificacion('Proyecto exportado exitosamente');
        })
        .catch(error => {
            ocultarSpinner(spinner);
            mostrarNotificacion('Error al exportar el proyecto', 'error');
            console.error('Error:', error);
        });
}

// Función para filtrar proyectos
function filtrarProyectos(termino) {
    const proyectos = document.querySelectorAll('.card-proyecto');
    
    proyectos.forEach(proyecto => {
        const nombre = proyecto.querySelector('.card-title').textContent.toLowerCase();
        const descripcion = proyecto.querySelector('.card-text').textContent.toLowerCase();
        
        if (nombre.includes(termino.toLowerCase()) || descripcion.includes(termino.toLowerCase())) {
            proyecto.style.display = 'block';
        } else {
            proyecto.style.display = 'none';
        }
    });
}

// Función para mostrar modal de edición de proyecto
function editarProyecto(proyectoId) {
    fetch(`api/proyectos.php?action=obtener_proyecto&id=${proyectoId}`)
        .then(response => response.json())
        .then(proyecto => {
            // Llenar formulario de edición
            document.getElementById('editarProyectoId').value = proyecto.id;
            document.getElementById('editarNombreProyecto').value = proyecto.nombre;
            document.getElementById('editarDescripcionProyecto').value = proyecto.descripcion;
            document.getElementById('editarClienteProyecto').value = proyecto.cliente;
            document.getElementById('editarFechaInicio').value = proyecto.fecha_inicio;
            document.getElementById('editarFechaFin').value = proyecto.fecha_fin_estimada;
            document.getElementById('editarPresupuesto').value = proyecto.presupuesto;
            document.getElementById('editarEstadoProyecto').value = proyecto.estado;
            // Mostrar modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditarProyecto'));
            modal.show();
        })
        .catch(error => {
            console.error('Error al obtener datos del proyecto:', error);
            mostrarNotificacion('Error al cargar los datos del proyecto', 'error');
        });
}

// Función para guardar cambios del proyecto
function guardarEdicionProyecto() {
    const id = document.getElementById('editarProyectoId').value;
    const data = {
        action: 'actualizar',
        id: id,
        nombre: document.getElementById('editarNombreProyecto').value,
        descripcion: document.getElementById('editarDescripcionProyecto').value,
        cliente: document.getElementById('editarClienteProyecto').value,
        fecha_inicio: document.getElementById('editarFechaInicio').value,
        fecha_fin_estimada: document.getElementById('editarFechaFin').value,
        presupuesto: document.getElementById('editarPresupuesto').value,
        estado: document.getElementById('editarEstadoProyecto').value
    };

    fetch('api/proyectos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            mostrarNotificacion('Proyecto actualizado');
            location.reload();
        } else {
            mostrarNotificacion('Error al actualizar proyecto', 'error');
        }
    })
    .catch(error => {
        console.error('Error al guardar:', error);
        mostrarNotificacion('Error al guardar cambios', 'error');
    });
}

// Función para eliminar proyecto
function eliminarProyecto(proyectoId) {
    confirmarAccion('¿Seguro que desea eliminar este proyecto? Esto eliminará todas sus tareas.', () => {
        fetch('api/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'eliminar_proyecto', proyecto_id: proyectoId })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                mostrarNotificacion('Proyecto eliminado');
                location.reload();
            } else {
                mostrarNotificacion('Error al eliminar', 'error');
            }
        })
        .catch(error => {
            console.error('Error al eliminar:', error);
            mostrarNotificacion('Error al eliminar el proyecto', 'error');
        });
    });
}
</script>

    // Función para añadir nueva tarea
    window.agregarTarea = function() {
        const form = document.getElementById('formNuevaTarea');
        const formData = new FormData(form);
        
        const data = {
            action: 'crear',
            nombre: formData.get('nombre'),
            tipo: formData.get('tipo'),
            duracion_dias: formData.get('duracion_dias'),
            estado: formData.get('estado'),
            porcentaje_avance: formData.get('porcentaje_avance')
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
    };

    // Filtro de tareas
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroTipo = document.getElementById('filtroTipo');
    
    if (filtroEstado && filtroTipo) {
        [filtroEstado, filtroTipo].forEach(filter => {
            filter.addEventListener('change', function() {
                filtrarTabla();
            });
        });
    }

    function filtrarTabla() {
        const estado = filtroEstado.value;
        const tipo = filtroTipo.value;
        const filas = document.querySelectorAll('#tablaTareas tbody tr');

        filas.forEach(fila => {
            const estadoFila = fila.dataset.estado;
            const tipoFila = fila.dataset.tipo;
            
            let mostrar = true;
            
            if (estado && estado !== estadoFila) {
                mostrar = false;
            }
            
            if (tipo && tipo !== tipoFila) {
                mostrar = false;
            }
            
            fila.style.display = mostrar ? '' : 'none';
        });
    }

    // Gráfico de progreso (usando Chart.js si está disponible)
    if (typeof Chart !== 'undefined') {
        const ctx = document.getElementById('graficoProgreso');
        if (ctx) {
            // Este gráfico se inicializará con datos desde PHP
            window.inicializarGrafico = function(datos) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completadas', 'En Proceso', 'Pendientes'],
                        datasets: [{
                            data: [datos.completadas, datos.proceso, datos.pendientes],
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
            };
        }
    }
});
