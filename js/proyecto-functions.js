// Funciones para gestión de tareas

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
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaTarea'));
            modal.hide();
            
            // Limpiar formulario
            form.reset();
            
            // Mostrar mensaje de éxito
            mostrarNotificacion('Tarea creada exitosamente', 'success');
            
            // Recargar página después de un breve delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            mostrarNotificacion('Error al crear la tarea', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al crear la tarea', 'error');
    });
}

function editarTarea(tareaId) {
    // Obtener datos de la tarea
    fetch(`api/tareas.php?action=obtener_tarea&id=${tareaId}`)
        .then(response => response.json())
        .then(tarea => {
            if (tarea) {
                // Llenar el formulario de edición
                document.getElementById('editar_tarea_id').value = tarea.id;
                document.getElementById('editar_nombre_tarea').value = tarea.nombre;
                document.getElementById('editar_tipo_tarea').value = tarea.tipo;
                document.getElementById('editar_duracion_tarea').value = tarea.duracion_dias;
                document.getElementById('editar_estado_tarea').value = tarea.estado;
                document.getElementById('editar_porcentaje_tarea').value = tarea.porcentaje_avance;
                document.getElementById('editarPorcentajeValor').textContent = tarea.porcentaje_avance + '%';
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEditarTarea'));
                modal.show();
            } else {
                mostrarNotificacion('Error al cargar los datos de la tarea', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al cargar los datos de la tarea', 'error');
        });
}

function guardarEdicionTarea() {
    const form = document.getElementById('formEditarTarea');
    const formData = new FormData(form);
    
    const data = {
        action: 'actualizar_completa',
        id: formData.get('id'),
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
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarTarea'));
            modal.hide();
            
            mostrarNotificacion('Tarea actualizada exitosamente', 'success');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            mostrarNotificacion('Error al actualizar la tarea', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar la tarea', 'error');
    });
}

function eliminarTarea(tareaId) {
    if (confirm('¿Está seguro de que desea eliminar esta tarea? Esta acción no se puede deshacer.')) {
        fetch('api/tareas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'eliminar',
                id: tareaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Tarea eliminada exitosamente', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                mostrarNotificacion('Error al eliminar la tarea', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al eliminar la tarea', 'error');
        });
    }
}

// Funciones para gestión de proyectos

function crearProyecto() {
    const form = document.getElementById('formNuevoProyecto');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre')) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
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
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoProyecto'));
            modal.hide();
            
            // Limpiar formulario
            form.reset();
            
            mostrarNotificacion('Proyecto creado exitosamente', 'success');
            
            // Redirigir al nuevo proyecto
            setTimeout(() => {
                window.location.href = '?proyecto=' + data.proyecto_id + '&view=dashboard';
            }, 1500);
        } else {
            mostrarNotificacion('Error al crear el proyecto: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al crear el proyecto', 'error');
    });
}

function editarProyecto(proyectoId) {
    fetch(`api/proyectos.php?action=obtener_proyecto&id=${proyectoId}`)
        .then(response => response.json())
        .then(proyecto => {
            if (proyecto && proyecto.id) {
                // Llenar formulario de edición
                document.getElementById('editarProyectoId').value = proyecto.id;
                document.getElementById('editarNombreProyecto').value = proyecto.nombre || '';
                document.getElementById('editarDescripcionProyecto').value = proyecto.descripcion || '';
                document.getElementById('editarClienteProyecto').value = proyecto.cliente || '';
                document.getElementById('editarFechaInicio').value = proyecto.fecha_inicio || '';
                document.getElementById('editarFechaFin').value = proyecto.fecha_fin_estimada || '';
                document.getElementById('editarPresupuesto').value = proyecto.presupuesto || '';
                document.getElementById('editarEstadoProyecto').value = proyecto.estado || 'Activo';
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalEditarProyecto'));
                modal.show();
            } else {
                mostrarNotificacion('Error al cargar los datos del proyecto', 'error');
            }
        })
        .catch(error => {
            console.error('Error al obtener datos del proyecto:', error);
            mostrarNotificacion('Error al cargar los datos del proyecto', 'error');
        });
}

function guardarEdicionProyecto() {
    const form = document.getElementById('formEditarProyecto');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre')) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
    const data = {
        action: 'actualizar_proyecto',
        id: formData.get('id'),
        nombre: formData.get('nombre'),
        descripcion: formData.get('descripcion'),
        cliente: formData.get('cliente'),
        fecha_inicio: formData.get('fecha_inicio'),
        fecha_fin_estimada: formData.get('fecha_fin_estimada'),
        presupuesto: formData.get('presupuesto'),
        estado: formData.get('estado')
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
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarProyecto'));
            modal.hide();
            
            mostrarNotificacion('Proyecto actualizado exitosamente', 'success');
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            mostrarNotificacion('Error al actualizar proyecto: ' + (res.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error al guardar:', error);
        mostrarNotificacion('Error al guardar cambios', 'error');
    });
}

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
                mostrarNotificacion('Proyecto duplicado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = '?proyecto=' + data.proyecto_id + '&view=dashboard';
                }, 1500);
            } else {
                mostrarNotificacion('Error al duplicar el proyecto: ' + (data.message || 'Error desconocido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al duplicar el proyecto', 'error');
        });
    }
}

function eliminarProyecto(proyectoId) {
    if (confirm('¿Está seguro de que desea eliminar este proyecto? Esta acción eliminará todas sus tareas y no se puede deshacer.')) {
        fetch('api/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                action: 'eliminar_proyecto', 
                proyecto_id: proyectoId 
            })
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                mostrarNotificacion('Proyecto eliminado exitosamente', 'success');
                setTimeout(() => {
                    window.location.href = '?view=proyectos';
                }, 1500);
            } else {
                mostrarNotificacion('Error al eliminar el proyecto: ' + (res.message || 'Error desconocido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error al eliminar:', error);
            mostrarNotificacion('Error al eliminar el proyecto', 'error');
        });
    }
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'success') {
    // Remover notificaciones existentes
    const existentes = document.querySelectorAll('.notification');
    existentes.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    notification.textContent = mensaje;
    
    // Estilos CSS inline para las notificaciones
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 20px',
        borderRadius: '5px',
        color: 'white',
        fontWeight: 'bold',
        zIndex: '9999',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        backgroundColor: tipo === 'success' ? '#27ae60' : '#e74c3c'
    });
    
    document.body.appendChild(notification);
    
    // Mostrar la notificación
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar la notificación después de 3 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Función para actualizar estadísticas en tiempo real
function actualizarEstadisticas(proyectoId) {
    fetch(`api/proyectos.php?action=estadisticas_proyecto&proyecto_id=${proyectoId}`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                // Actualizar elementos en el DOM si existen
                const elementos = {
                    total: document.querySelector('.metric-card .metric-number'),
                    completadas: document.querySelectorAll('.metric-card .metric-number')[1],
                    proceso: document.querySelectorAll('.metric-card .metric-number')[2],
                    pendientes: document.querySelectorAll('.metric-card .metric-number')[3]
                };
                
                if (elementos.total) elementos.total.textContent = data.total;
                if (elementos.completadas) elementos.completadas.textContent = data.completadas;
                if (elementos.proceso) elementos.proceso.textContent = data.en_proceso;
                if (elementos.pendientes) elementos.pendientes.textContent = data.pendientes;
                
                // Actualizar barra de progreso
                const progressBar = document.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = data.avance_promedio + '%';
                    progressBar.textContent = data.avance_promedio.toFixed(1) + '%';
                }
            }
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}

// Función para obtener datos de una tarea específica
window.obtenerTarea = function(tareaId) {
    return fetch(`api/tareas.php?action=obtener_tarea&id=${tareaId}`)
        .then(response => response.json())
        .catch(error => {
            console.error('Error al obtener tarea:', error);
            return null;
        });
};

// Inicialización cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    // Configurar eventos para los sliders de porcentaje
    const porcentajeSliders = document.querySelectorAll('input[type="range"]');
    porcentajeSliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const valorSpan = this.parentNode.querySelector('.text-nowrap, #porcentajeValor, #editarPorcentajeValor');
            if (valorSpan) {
                valorSpan.textContent = this.value + '%';
            }
        });
    });
    
    // Configurar tooltips si están disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Funciones globales adicionales para compatibilidad
window.agregarTarea = agregarTarea;
window.editarTarea = editarTarea;
window.eliminarTarea = eliminarTarea;
window.crearProyecto = crearProyecto;
window.editarProyecto = editarProyecto;
window.duplicarProyecto = duplicarProyecto;
window.eliminarProyecto = eliminarProyecto;
window.guardarEdicionProyecto = guardarEdicionProyecto;
window.guardarEdicionTarea = guardarEdicionTarea;
window.mostrarNotificacion = mostrarNotificacion;
