// Funciones para gestión de tareas con peso de actividad

function agregarTarea() {
    const form = document.getElementById('formNuevaTarea');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre de la tarea es requerido', 'error');
        return;
    }
    
    if (!formData.get('proyecto_id')) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    const data = {
        action: 'crear',
        nombre: formData.get('nombre').trim(),
        tipo: formData.get('tipo'),
        duracion_dias: parseInt(formData.get('duracion_dias')) || 1,
        estado: formData.get('estado'),
        porcentaje_avance: parseFloat(formData.get('porcentaje_avance')) || 0,
        proyecto_id: parseInt(formData.get('proyecto_id')),
        contrato: formData.get('contrato') || 'Normal',
        peso_actividad: parseFloat(formData.get('peso_actividad')) || 0.0000,
        fase_principal: formData.get('fase_principal')?.trim() || null
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalNuevaTarea .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;

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
            document.getElementById('porcentajeValor').textContent = '0%';
            
            // Mostrar mensaje de éxito
            mostrarNotificacion('Tarea creada exitosamente', 'success');
            
            // Recargar página después de un breve delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            mostrarNotificacion('Error al crear la tarea: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al crear la tarea', 'error');
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function editarTarea(tareaId) {
    if (!tareaId) {
        mostrarNotificacion('ID de tarea inválido', 'error');
        return;
    }
    
    // Mostrar loading en el botón
    const btn = document.querySelector(`button[onclick="editarTarea(${tareaId})"]`);
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
    }
    
    // Obtener datos de la tarea
    fetch(`api/tareas.php?action=obtener_tarea&id=${tareaId}`)
        .then(response => response.json())
        .then(tarea => {
            if (tarea && tarea.id) {
                // Llenar el formulario de edición
                document.getElementById('editar_tarea_id').value = tarea.id;
                document.getElementById('editar_nombre_tarea').value = tarea.nombre || '';
                document.getElementById('editar_tipo_tarea').value = tarea.tipo || 'Tarea';
                document.getElementById('editar_duracion_tarea').value = tarea.duracion_dias || 1;
                document.getElementById('editar_estado_tarea').value = tarea.estado || 'Pendiente';
                document.getElementById('editar_porcentaje_tarea').value = tarea.porcentaje_avance || 0;
                document.getElementById('editarPorcentajeValor').textContent = (tarea.porcentaje_avance || 0) + '%';
                
                // Nuevos campos
                document.getElementById('editar_contrato_tarea').value = tarea.contrato || 'Normal';
                document.getElementById('editar_peso_actividad_tarea').value = tarea.peso_actividad || 0.0000;
                document.getElementById('editar_fase_principal_tarea').value = tarea.fase_principal || '';
                
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
        })
        .finally(() => {
            // Restaurar botón
            if (btn) {
                btn.innerHTML = '<i class="fas fa-edit"></i>';
                btn.disabled = false;
            }
        });
}

function guardarEdicionTarea() {
    const form = document.getElementById('formEditarTarea');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre de la tarea es requerido', 'error');
        return;
    }
    
    const data = {
        action: 'actualizar_completa',
        id: parseInt(formData.get('id')),
        nombre: formData.get('nombre').trim(),
        tipo: formData.get('tipo'),
        duracion_dias: parseInt(formData.get('duracion_dias')) || 1,
        estado: formData.get('estado'),
        porcentaje_avance: parseFloat(formData.get('porcentaje_avance')) || 0,
        contrato: formData.get('contrato') || 'Normal',
        peso_actividad: parseFloat(formData.get('peso_actividad')) || 0.0000,
        fase_principal: formData.get('fase_principal')?.trim() || null
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalEditarTarea .btn-warning');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;

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
            mostrarNotificacion('Error al actualizar la tarea: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar la tarea', 'error');
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function eliminarTarea(tareaId) {
    if (!tareaId) {
        mostrarNotificacion('ID de tarea inválido', 'error');
        return;
    }
    
    if (confirm('¿Está seguro de que desea eliminar esta tarea? Esta acción no se puede deshacer.')) {
        const btn = document.querySelector(`button[onclick="eliminarTarea(${tareaId})"]`);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
        }
        
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
                mostrarNotificacion('Error al eliminar la tarea: ' + (data.message || 'Error desconocido'), 'error');
                // Restaurar botón si hay error
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-trash"></i>';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al eliminar la tarea', 'error');
            // Restaurar botón si hay error
            if (btn) {
                btn.innerHTML = '<i class="fas fa-trash"></i>';
                btn.disabled = false;
            }
        });
    }
}

// Funciones para gestión de proyectos

function crearProyecto() {
    const form = document.getElementById('formNuevoProyecto');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
    const data = {
        action: 'crear_proyecto',
        nombre: formData.get('nombre').trim(),
        descripcion: formData.get('descripcion')?.trim() || '',
        fecha_inicio: formData.get('fecha_inicio') || null,
        fecha_fin_estimada: formData.get('fecha_fin_estimada') || null,
        cliente: formData.get('cliente')?.trim() || '',
        presupuesto: parseFloat(formData.get('presupuesto')) || 0,
        estado: formData.get('estado') || 'Activo',
        plantilla_proyecto: formData.get('plantilla_proyecto') || null
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalNuevoProyecto .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
    submitBtn.disabled = true;

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
                window.location.href = '?proyecto=' + (data.proyecto_id || '') + '&view=dashboard';
            }, 1500);
        } else {
            mostrarNotificacion('Error al crear el proyecto: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al crear el proyecto', 'error');
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function editarProyecto(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto inválido', 'error');
        return;
    }
    
    // Mostrar loading
    const btn = document.querySelector(`button[onclick="editarProyecto(${proyectoId})"]`);
    if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
    }
    
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
        })
        .finally(() => {
            // Restaurar botón
            if (btn) {
                btn.innerHTML = '<i class="fas fa-edit"></i>';
                btn.disabled = false;
            }
        });
}

function guardarEdicionProyecto() {
    const form = document.getElementById('formEditarProyecto');
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
    const data = {
        action: 'actualizar_proyecto',
        id: parseInt(formData.get('id')),
        nombre: formData.get('nombre').trim(),
        descripcion: formData.get('descripcion')?.trim() || '',
        cliente: formData.get('cliente')?.trim() || '',
        fecha_inicio: formData.get('fecha_inicio') || null,
        fecha_fin_estimada: formData.get('fecha_fin_estimada') || null,
        presupuesto: parseFloat(formData.get('presupuesto')) || 0,
        estado: formData.get('estado') || 'Activo'
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalEditarProyecto .btn-warning');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;

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
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function duplicarProyecto(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto inválido', 'error');
        return;
    }
    
    if (confirm('¿Desea duplicar este proyecto con todas sus tareas?')) {
        const btn = document.querySelector(`button[onclick="duplicarProyecto(${proyectoId})"]`);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
        }
        
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
                    window.location.href = '?proyecto=' + (data.proyecto_id || '') + '&view=dashboard';
                }, 1500);
            } else {
                mostrarNotificacion('Error al duplicar el proyecto: ' + (data.message || 'Error desconocido'), 'error');
                // Restaurar botón si hay error
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al duplicar el proyecto', 'error');
            // Restaurar botón si hay error
            if (btn) {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.disabled = false;
            }
        });
    }
}

function eliminarProyecto(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto inválido', 'error');
        return;
    }
    
    if (confirm('¿Está seguro de que desea eliminar este proyecto? Esta acción eliminará todas sus tareas y no se puede deshacer.')) {
        const btn = document.querySelector(`button[onclick="eliminarProyecto(${proyectoId})"]`);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
        }
        
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
                // Restaurar botón si hay error
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-trash"></i>';
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error al eliminar:', error);
            mostrarNotificacion('Error al eliminar el proyecto', 'error');
            // Restaurar botón si hay error
            if (btn) {
                btn.innerHTML = '<i class="fas fa-trash"></i>';
                btn.disabled = false;
            }
        });
    }
}

// Función para mostrar notificaciones mejorada
function mostrarNotificacion(mensaje, tipo = 'success', duracion = 3000) {
    // Remover notificaciones existentes
    const existentes = document.querySelectorAll('.notification');
    existentes.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    
    // Crear contenido de la notificación
    const icon = tipo === 'success' ? 'fas fa-check-circle' : 
                 tipo === 'error' ? 'fas fa-exclamation-circle' : 
                 'fas fa-info-circle';
    
    notification.innerHTML = `
        <i class="${icon}"></i>
        <span>${mensaje}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; float: right; cursor: pointer;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Estilos CSS inline para las notificaciones
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        minWidth: '300px',
        padding: '15px 20px',
        borderRadius: '5px',
        color: 'white',
        fontWeight: 'bold',
        zIndex: '9999',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease',
        backgroundColor: tipo === 'success' ? '#27ae60' : 
                        tipo === 'error' ? '#e74c3c' : '#3498db',
        boxShadow: '0 4px 15px rgba(0,0,0,0.2)',
        display: 'flex',
        alignItems: 'center',
        gap: '10px'
    });
    
    document.body.appendChild(notification);
    
    // Mostrar la notificación
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Ocultar la notificación después del tiempo especificado
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, duracion);
}

// Función para actualizar estadísticas en tiempo real
function actualizarEstadisticas(proyectoId) {
    if (!proyectoId) return;
    
    fetch(`api/tareas.php?action=estadisticas&proyecto_id=${proyectoId}`)
        .then(response => response.json())
        .then(data => {
            if (data && typeof data === 'object') {
                // Actualizar elementos en el DOM si existen
                const elementos = {
                    total: document.querySelector('.metric-card .metric-number'),
                    completadas: document.querySelectorAll('.metric-card .metric-number')[1],
                    proceso: document.querySelectorAll('.metric-card .metric-number')[2],
                    pendientes: document.querySelectorAll('.metric-card .metric-number')[3]
                };
                
                if (elementos.total) elementos.total.textContent = data.total || 0;
                if (elementos.completadas) elementos.completadas.textContent = data.completadas || 0;
                if (elementos.proceso) elementos.proceso.textContent = data.en_proceso || 0;
                if (elementos.pendientes) elementos.pendientes.textContent = data.pendientes || 0;
                
                // Actualizar barra de progreso
                const progressBar = document.querySelector('.progress-bar');
                if (progressBar && data.avance_promedio !== undefined) {
                    const progreso = parseFloat(data.avance_promedio) || 0;
                    progressBar.style.width = progreso + '%';
                    progressBar.textContent = progreso.toFixed(1) + '%';
                }
            }
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}

// Función para distribuir peso automáticamente
function distribuirPesoAutomatico(proyectoId, pesoTotal = 1.0) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    if (!confirm(`¿Desea distribuir automáticamente el peso total (${pesoTotal}) entre todas las tareas del proyecto?`)) {
        return;
    }
    
    fetch('api/tareas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'distribuir_peso',
            proyecto_id: proyectoId,
            peso_total: pesoTotal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Peso distribuido exitosamente', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion('Error al distribuir peso: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al distribuir peso', 'error');
    });
}

// Función para exportar proyecto con peso ponderado
function exportarProyecto(proyectoId, formato = 'json') {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    mostrarNotificacion('Iniciando exportación...', 'info', 1000);
    
    const url = `api/exportar.php?action=proyecto&proyecto_id=${proyectoId}&formato=${formato}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = url;
    link.download = `proyecto_${proyectoId}_${new Date().toISOString().split('T')[0]}.${formato}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        mostrarNotificacion('Archivo descargado exitosamente', 'success');
    }, 1000);
}

// Función para importar datos desde Excel
function importarDatosExcel(proyectoId, datosExcel, limpiarExistentes = true) {
    if (!proyectoId || !datosExcel || !Array.isArray(datosExcel)) {
        mostrarNotificacion('Datos de importación inválidos', 'error');
        return;
    }
    
    const data = {
        action: 'importar_excel',
        proyecto_id: proyectoId,
        datos: datosExcel,
        limpiar_existentes: limpiarExistentes
    };
    
    fetch('api/tareas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            mostrarNotificacion(`${datosExcel.length} tareas importadas exitosamente`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion('Error al importar: ' + (result.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al importar datos', 'error');
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
    
    // Auto-completar peso basado en tipo
    const tipoSelects = document.querySelectorAll('#tipo_tarea, #editar_tipo_tarea');
    tipoSelects.forEach(select => {
        select.addEventListener('change', function() {
            const pesoInput = this.closest('form').querySelector('input[name="peso_actividad"]');
            if (pesoInput) {
                // Sugerir pesos según el tipo (basado en el análisis del Excel)
                switch(this.value) {
                    case 'Fase':
                        pesoInput.value = '0.1000';
                        break;
                    case 'Actividad':
                        pesoInput.value = '0.0500';
                        break;
                    case 'Tarea':
                        pesoInput.value = '0.0100';
                        break;
                    default:
                        pesoInput.value = '0.0000';
                }
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
    
    // Actualizar estadísticas cada 30 segundos si hay un proyecto activo
    const proyectoActual = document.querySelector('input[name="proyecto_id"]')?.value;
    if (proyectoActual) {
        setInterval(() => {
            actualizarEstadisticas(proyectoActual);
        }, 30000);
    }
    
    // Configurar validación de formularios
    configurarValidacionFormularios();
});

// Función para configurar validación de formularios
function configurarValidacionFormularios() {
    const formularios = document.querySelectorAll('form');
    formularios.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevenir envío por defecto
        });
        
        // Validación en tiempo real para campos de peso
        const pesoInputs = form.querySelectorAll('input[name="peso_actividad"]');
        pesoInputs.forEach(input => {
            input.addEventListener('input', function() {
                const valor = parseFloat(this.value);
                if (valor < 0 || valor > 1) {
                    this.classList.add('is-invalid');
                    this.title = 'El peso debe estar entre 0.0000 y 1.0000';
                } else {
                    this.classList.remove('is-invalid');
                    this.title = '';
                }
            });
        });
    });
}

// Funciones globales para compatibilidad con el HTML
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
window.actualizarEstadisticas = actualizarEstadisticas;
window.distribuirPesoAutomatico = distribuirPesoAutomatico;
window.exportarProyecto = exportarProyecto;
window.importarDatosExcel = importarDatosExcel;
