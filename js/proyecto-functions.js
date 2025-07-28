// ============================================================================
// FUNCIONES MEJORADAS PARA GESTIÓN DE TAREAS Y PROYECTOS CON DÍAS CONFIGURABLES
// Sistema de peso de actividad EN PORCENTAJES (0%-100%) basado en días totales
// Versión corregida - sin duplicaciones ni errores
// ============================================================================

// ========== VARIABLES GLOBALES ==========
let modoCalculoAutomatico = true;
let diasTotalesProyecto = 56.0;

// ========== FUNCIONES PARA GESTIÓN DE DÍAS TOTALES ==========

async function obtenerDiasTotalesProyecto(proyectoId = null) {
    try {
        if (!proyectoId) {
            proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        }
        if (!proyectoId) return 56.0;
        
        const response = await fetch(`api/proyectos.php?action=obtener_dias_totales&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            diasTotalesProyecto = parseFloat(data.dias_totales) || 56.0;
            actualizarInfoDiasTotales();
            return diasTotalesProyecto;
        }
        return 56.0;
    } catch (error) {
        console.error('Error obteniendo días totales:', error);
        return 56.0;
    }
}

function actualizarInfoDiasTotales() {
    const elementos = ['diasTotalesInfo', 'infoDiasTotales', 'diasTotalesActuales'];
    elementos.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = diasTotalesProyecto.toString();
        }
    });
}

function calcularPesoAutomatico() {
    if (!modoCalculoAutomatico) return;
    
    const duracionInput = document.getElementById('duracion_tarea');
    const pesoInput = document.getElementById('peso_actividad_tarea');
    
    if (!duracionInput || !pesoInput) return;
    
    const duracion = parseFloat(duracionInput.value) || 0;
    const peso = diasTotalesProyecto > 0 ? (duracion / diasTotalesProyecto) * 100 : 0;
    
    pesoInput.value = peso.toFixed(4);
    
    // Actualizar información contextual
    const infoElement = document.getElementById('infoProyectoCalculo');
    if (infoElement) {
        infoElement.innerHTML = `
            <small class="text-success">
                <i class="fas fa-calculator"></i> 
                Calculado: ${duracion} días ÷ ${diasTotalesProyecto} días = ${peso.toFixed(2)}%
            </small>
        `;
    }
}

function toggleModoCalculoAutomatico() {
    modoCalculoAutomatico = !modoCalculoAutomatico;
    
    const btnModo = document.getElementById('btnModoCalculo');
    const calculoAuto = document.getElementById('calculoAutomatico');
    const calculoManual = document.getElementById('calculoManual');
    const pesoInput = document.getElementById('peso_actividad_tarea');
    
    if (modoCalculoAutomatico) {
        if (btnModo) {
            btnModo.innerHTML = '<i class="fas fa-link"></i>';
            btnModo.className = 'btn btn-outline-success';
            btnModo.title = 'Cálculo automático activado';
        }
        if (calculoAuto) calculoAuto.classList.remove('d-none');
        if (calculoManual) calculoManual.classList.add('d-none');
        if (pesoInput) {
            pesoInput.readOnly = true;
            pesoInput.style.backgroundColor = '#f8f9fa';
        }
        calcularPesoAutomatico();
    } else {
        if (btnModo) {
            btnModo.innerHTML = '<i class="fas fa-unlink"></i>';
            btnModo.className = 'btn btn-outline-warning';
            btnModo.title = 'Cálculo manual activado';
        }
        if (calculoAuto) calculoAuto.classList.add('d-none');
        if (calculoManual) calculoManual.classList.remove('d-none');
        if (pesoInput) {
            pesoInput.readOnly = false;
            pesoInput.style.backgroundColor = '';
        }
    }
}

// ========== FUNCIONES DE UTILIDADES MEJORADAS ==========

function mostrarNotificacion(mensaje, tipo = 'success', duracion = 4000) {
    // Remover notificaciones existentes
    const existentes = document.querySelectorAll('.notification');
    existentes.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification ${tipo}`;
    
    // Crear contenido de la notificación con mejor formato
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
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <i class="${iconos[tipo] || iconos.info}" style="margin-top: 2px; font-size: 18px;"></i>
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 4px;">${tipo.toUpperCase()}</div>
                <div style="white-space: pre-line; line-height: 1.4;">${mensaje}</div>
            </div>
            <button onclick="this.closest('.notification').remove()" 
                    style="background: none; border: none; color: inherit; cursor: pointer; padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Estilos mejorados
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        minWidth: '350px',
        maxWidth: '500px',
        padding: '16px 20px',
        borderRadius: '12px',
        color: 'white',
        zIndex: '9999',
        transform: 'translateX(100%)',
        transition: 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
        backgroundColor: colores[tipo] || colores.info,
        boxShadow: '0 8px 32px rgba(0,0,0,0.3)',
        fontSize: '14px',
        fontFamily: 'system-ui, sans-serif',
        backdropFilter: 'blur(10px)'
    });
    
    document.body.appendChild(notification);
    
    // Mostrar con animación
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-ocultar
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 400);
    }, duracion);
}

// ========== FUNCIONES DE TAREAS ==========

function agregarTarea() {
    const form = document.getElementById('formNuevaTarea');
    if (!form) {
        mostrarNotificacion('Formulario no encontrado', 'error');
        return;
    }
    
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

    // Obtener peso automáticamente si está en modo automático
    let pesoActividad;
    if (modoCalculoAutomatico) {
        const duracion = parseFloat(formData.get('duracion_dias')) || 1;
        pesoActividad = diasTotalesProyecto > 0 ? (duracion / diasTotalesProyecto) * 100 : 0;
    } else {
        pesoActividad = parseFloat(formData.get('peso_actividad')) || 0;
    }
    
    // Validar peso en porcentajes (0%-100%)
    if (pesoActividad < 0 || pesoActividad > 100) {
        mostrarNotificacion('El peso debe estar entre 0% y 100%', 'error');
        return;
    }
    
    // Advertencia si el peso es muy alto
    if (pesoActividad > 25) {
        if (!confirm(`⚠️ El peso asignado es ${pesoActividad.toFixed(2)}%, que es bastante alto para una sola tarea. ¿Desea continuar?`)) {
            return;
        }
    }
    
    const data = {
        action: 'crear',
        nombre: formData.get('nombre').trim(),
        tipo: formData.get('tipo'),
        duracion_dias: parseFloat(formData.get('duracion_dias')) || 1,
        estado: formData.get('estado'),
        porcentaje_avance: parseFloat(formData.get('porcentaje_avance')) || 0,
        proyecto_id: parseInt(formData.get('proyecto_id')),
        contrato: formData.get('contrato') || 'Normal',
        peso_actividad: pesoActividad,
        fase_principal: formData.get('fase_principal')?.trim() || null,
        modo_calculo: modoCalculoAutomatico ? 'automatico' : 'manual'
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalNuevaTarea .btn-primary');
    if (submitBtn) {
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
                if (modal) modal.hide();
                
                // Limpiar formulario
                form.reset();
                const porcentajeValor = document.getElementById('porcentajeValor');
                if (porcentajeValor) porcentajeValor.textContent = '0%';
                
                // Mensaje informativo
                let mensaje = `Tarea creada exitosamente (Peso: ${pesoActividad.toFixed(2)}%)`;
                
                if (modoCalculoAutomatico) {
                    mensaje += `\n📊 Calculado automáticamente basado en ${data.duracion_dias || 1} días`;
                }
                
                // Mostrar advertencias del servidor si existen
                if (data.warning) {
                    mensaje += `\n${data.warning}`;
                    mostrarNotificacion(mensaje, 'warning', 6000);
                } else if (data.info) {
                    mensaje += `\n${data.info}`;
                    mostrarNotificacion(mensaje, 'info', 5000);
                } else {
                    mostrarNotificacion(mensaje, 'success');
                }
                
                // Recargar página después de un breve delay
                setTimeout(() => {
                    location.reload();
                }, 1500);
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
                const campos = [
                    ['editar_tarea_id', tarea.id],
                    ['editar_nombre_tarea', tarea.nombre || ''],
                    ['editar_tipo_tarea', tarea.tipo || 'Tarea'],
                    ['editar_duracion_tarea', tarea.duracion_dias || 1],
                    ['editar_estado_tarea', tarea.estado || 'Pendiente'],
                    ['editar_porcentaje_tarea', tarea.porcentaje_avance || 0],
                    ['editar_contrato_tarea', tarea.contrato || 'Normal'],
                    ['editar_peso_actividad_tarea', parseFloat(tarea.peso_actividad || 0).toFixed(2)],
                    ['editar_fase_principal_tarea', tarea.fase_principal || '']
                ];
                
                campos.forEach(([id, valor]) => {
                    const elemento = document.getElementById(id);
                    if (elemento) elemento.value = valor;
                });
                
                // Actualizar texto del porcentaje
                const porcentajeTexto = document.getElementById('editarPorcentajeValor');
                if (porcentajeTexto) {
                    porcentajeTexto.textContent = (tarea.porcentaje_avance || 0) + '%';
                }
                
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
    if (!form) {
        mostrarNotificacion('Formulario de edición no encontrado', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre de la tarea es requerido', 'error');
        return;
    }

    // Validar peso en porcentajes
    const pesoActividad = parseFloat(formData.get('peso_actividad')) || 0;
    if (pesoActividad < 0 || pesoActividad > 100) {
        mostrarNotificacion('El peso debe estar entre 0% y 100%', 'error');
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

    // Mostrar loading
    const submitBtn = document.querySelector('#modalEditarTarea .btn-warning');
    if (submitBtn) {
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
                if (modal) modal.hide();
                
                mostrarNotificacion(`Tarea actualizada exitosamente (Peso: ${pesoActividad.toFixed(2)}%)`, 'success');
                
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

// ========== FUNCIONES DE PROYECTOS ==========

function crearProyecto() {
    const form = document.getElementById('formNuevoProyecto');
    if (!form) {
        mostrarNotificacion('Formulario no encontrado', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
    // Obtener días totales del formulario
    const diasTotales = parseFloat(formData.get('dias_totales')) || 56.0;
    
    const data = {
        action: 'crear_proyecto',
        nombre: formData.get('nombre').trim(),
        descripcion: formData.get('descripcion')?.trim() || '',
        fecha_inicio: formData.get('fecha_inicio') || null,
        fecha_fin_estimada: formData.get('fecha_fin_estimada') || null,
        cliente: formData.get('cliente')?.trim() || '',
        presupuesto: parseFloat(formData.get('presupuesto')) || 0,
        dias_totales: diasTotales,
        estado: formData.get('estado') || 'Activo',
        plantilla_proyecto: formData.get('plantilla_proyecto') || null
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalNuevoProyecto .btn-primary');
    if (submitBtn) {
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
                if (modal) modal.hide();
                
                // Limpiar formulario
                form.reset();
                
                mostrarNotificacion(`Proyecto creado exitosamente con ${diasTotales} días totales configurables`, 'success');
                
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
                const campos = [
                    ['editarProyectoId', proyecto.id],
                    ['editarNombreProyecto', proyecto.nombre || ''],
                    ['editarDescripcionProyecto', proyecto.descripcion || ''],
                    ['editarClienteProyecto', proyecto.cliente || ''],
                    ['editarFechaInicio', proyecto.fecha_inicio || ''],
                    ['editarFechaFin', proyecto.fecha_fin_estimada || ''],
                    ['editarPresupuesto', proyecto.presupuesto || ''],
                    ['editarEstadoProyecto', proyecto.estado || 'Activo']
                ];
                
                campos.forEach(([id, valor]) => {
                    const elemento = document.getElementById(id);
                    if (elemento) elemento.value = valor;
                });
                
                // Llenar días totales
                const diasTotalesInput = document.getElementById('editarDiasTotales');
                if (diasTotalesInput) {
                    diasTotalesInput.value = proyecto.dias_totales || 56.0;
                }
                
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
    if (!form) {
        mostrarNotificacion('Formulario de edición no encontrado', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validar campos requeridos
    if (!formData.get('nombre').trim()) {
        mostrarNotificacion('El nombre del proyecto es requerido', 'error');
        return;
    }
    
    // Incluir días totales
    const diasTotales = parseFloat(formData.get('dias_totales')) || 56.0;
    
    const data = {
        action: 'actualizar_proyecto',
        id: parseInt(formData.get('id')),
        nombre: formData.get('nombre').trim(),
        descripcion: formData.get('descripcion')?.trim() || '',
        cliente: formData.get('cliente')?.trim() || '',
        fecha_inicio: formData.get('fecha_inicio') || null,
        fecha_fin_estimada: formData.get('fecha_fin_estimada') || null,
        presupuesto: parseFloat(formData.get('presupuesto')) || 0,
        dias_totales: diasTotales,
        estado: formData.get('estado') || 'Activo'
    };

    // Mostrar loading
    const submitBtn = document.querySelector('#modalEditarProyecto .btn-warning');
    if (submitBtn) {
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
                if (modal) modal.hide();
                
                let mensaje = 'Proyecto actualizado exitosamente';
                if (res.pesos_recalculados) {
                    mensaje += '\n📊 Pesos recalculados automáticamente por cambio en días totales';
                }
                
                mostrarNotificacion(mensaje, 'success');
                
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
}

function duplicarProyecto(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto inválido', 'error');
        return;
    }
    
    if (confirm('¿Desea duplicar este proyecto con todas sus tareas, pesos y configuración de días?')) {
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
                mostrarNotificacion('Proyecto duplicado exitosamente (incluye días totales y pesos ponderados)', 'success');
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
    
    if (confirm('¿Está seguro de que desea eliminar este proyecto? Esta acción eliminará todas sus tareas, pesos y configuración, y no se puede deshacer.')) {
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

// ========== FUNCIONES DE UTILIDADES ==========

function exportarProyecto(proyectoId, formato = 'json') {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    mostrarNotificacion('Iniciando exportación con pesos ponderados y días configurables...', 'info', 1000);
    
    const url = `api/exportar.php?action=proyecto&proyecto_id=${proyectoId}&formato=${formato}`;
    
    // Crear enlace temporal para descarga
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        mostrarNotificacion(`Exportación ${formato.toUpperCase()} iniciada (incluye días totales y pesos en porcentajes)`, 'success');
    }, 1000);
}

function exportarReporte(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    mostrarNotificacion('Generando reporte con análisis de peso ponderado y días configurables...', 'info', 1000);
    
    const url = `api/exportar.php?action=reporte_proyecto&proyecto_id=${proyectoId}&formato=html`;
    
    // Abrir en nueva ventana
    window.open(url, '_blank');
    
    setTimeout(() => {
        mostrarNotificacion('Reporte generado exitosamente con peso ponderado y análisis de días', 'success');
    }, 1000);
}

function importarDatosCafeto(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    if (confirm('¿Desea importar los datos de ejemplo del proyecto Cafeto con pesos en porcentajes y días configurables? Esto reemplazará las tareas existentes.')) {
        mostrarNotificacion('Importando datos del proyecto Cafeto (pesos en porcentajes basados en días)...', 'info');
        
        fetch('api/proyectos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'importar_excel_cafeto',
                proyecto_id: proyectoId,
                incluir_dias_configurables: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let mensaje = 'Datos del proyecto Cafeto importados exitosamente con pesos ponderados';
                if (data.dias_totales_configurados) {
                    mensaje += `\n📅 Días totales configurados: ${data.dias_totales_configurados}`;
                }
                mostrarNotificacion(mensaje, 'success', 4000);
                setTimeout(() => location.reload(), 2000);
            } else {
                mostrarNotificacion('Error al importar datos: ' + (data.message || 'Error desconocido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error al importar datos', 'error');
        });
    }
}

function actualizarEstadisticas(proyectoId) {
    if (!proyectoId) return;
    
    fetch(`api/tareas.php?action=estadisticas_detalladas&proyecto_id=${proyectoId}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.success && data.data) {
                const stats = data.data;
                
                // Actualizar elementos en el DOM si existen
                const elementos = {
                    total: document.querySelector('.metric-card .metric-number'),
                    completadas: document.querySelectorAll('.metric-card .metric-number')[1],
                    proceso: document.querySelectorAll('.metric-card .metric-number')[2],
                    pendientes: document.querySelectorAll('.metric-card .metric-number')[3]
                };
                
                if (elementos.total) elementos.total.textContent = stats.total_tareas || 0;
                if (elementos.completadas) elementos.completadas.textContent = stats.completadas || 0;
                if (elementos.proceso) elementos.proceso.textContent = stats.en_proceso || 0;
                if (elementos.pendientes) elementos.pendientes.textContent = stats.pendientes || 0;
                
                // Actualizar barra de progreso con porcentajes
                const progressBar = document.querySelector('.progress-bar');
                if (progressBar && stats.avance_ponderado_total !== undefined) {
                    const progreso = parseFloat(stats.avance_ponderado_total) || 0;
                    progressBar.style.width = progreso + '%';
                    progressBar.textContent = progreso.toFixed(1) + '%';
                }

                // Actualizar información de peso total y días
                const pesoTotalElement = document.querySelector('.peso-total-info');
                if (pesoTotalElement && stats.peso_total_actual !== undefined) {
                    pesoTotalElement.textContent = `Peso total: ${parseFloat(stats.peso_total_actual).toFixed(2)}%`;
                }
                
                const diasInfoElement = document.querySelector('.dias-info');
                if (diasInfoElement && stats.dias_totales_proyecto !== undefined) {
                    diasInfoElement.textContent = `Días totales: ${stats.dias_totales_proyecto} | Planificados: ${stats.total_dias_planificados.toFixed(1)}`;
                }
            }
        })
        .catch(error => {
            console.error('Error al actualizar estadísticas:', error);
        });
}

function distribuirPesoAutomatico(proyectoId, pesoTotal = 100.0) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    // Verificar si existe el modal mejorado
    const modalExistente = document.getElementById('modalDistribuirPeso');
    if (modalExistente) {
        // Usar el modal existente del archivo modales.php
        document.getElementById('distribuir_proyecto_id').value = proyectoId;
        const modal = new bootstrap.Modal(modalExistente);
        modal.show();
        return;
    }
    
    // Fallback: crear modal simple si no existe el mejorado
    const modalHtml = `
        <div class="modal fade" id="modalDistribuirPesoSimple" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">🎯 Distribuir Peso Automáticamente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>ℹ️ Información:</strong> El peso total se distribuirá para sumar exactamente 100%.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Método de distribución:</label>
                            <select class="form-select" id="metodoDistribucionSimple">
                                <option value="por_dias">📅 Por días totales (Automático - Recomendado)</option>
                                <option value="equitativo">📊 Equitativo (igual peso para todas)</option>
                                <option value="por_fase">🏗️ Por fase (según proyecto Cafeto)</option>
                                <option value="por_tipo">📋 Por tipo (Fases 20%, Actividades 60%, Tareas 20%)</option>
                                <option value="por_duracion">⏱️ Por duración (proporcional a días)</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-success border-0">
                            <h6><i class="fas fa-calculator"></i> Método "Por días totales"</h6>
                            <p class="mb-0 small">
                                Calcula automáticamente: <strong>(Días de tarea ÷ Días totales del proyecto) × 100%</strong><br>
                                Garantiza que los pesos sumen exactamente 100% y reflejen la duración real de cada tarea.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="ejecutarDistribucionSimple(${proyectoId})">
                            🎯 Distribuir Peso
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Eliminar modal existente si existe
    const modalExistenteSimple = document.getElementById('modalDistribuirPesoSimple');
    if (modalExistenteSimple) {
        modalExistenteSimple.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDistribuirPesoSimple'));
    modal.show();
}

function ejecutarDistribucionSimple(proyectoId) {
    const metodo = document.getElementById('metodoDistribucionSimple').value;
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalDistribuirPesoSimple'));
    if (modal) modal.hide();
    
    const metodosDescripcion = {
        'por_dias': 'basándose en días totales (automático)',
        'equitativo': 'equitativamente',
        'por_fase': 'por fase (según proyecto Cafeto)',
        'por_tipo': 'por tipo de tarea',
        'por_duracion': 'proporcionalmente por duración'
    };
    
    mostrarNotificacion(`Distribuyendo peso ${metodosDescripcion[metodo]}...`, 'info');
    
    fetch('api/tareas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'distribuir_peso',
            proyecto_id: proyectoId,
            peso_total: 100.0,
            metodo: metodo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let mensaje = `✅ ${data.message}`;
            if (metodo === 'por_dias') {
                mensaje += '\n📊 Pesos calculados automáticamente basándose en días totales del proyecto';
            }
            mostrarNotificacion(mensaje, 'success', 4000);
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacion('❌ Error al distribuir peso: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al distribuir peso', 'error');
    });
}

function recalcularProgreso(proyectoId) {
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    mostrarNotificacion('Recalculando progreso ponderado basado en días configurables...', 'info');
    
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
        if (data.success) {
            const progreso = data.progreso_actualizado ? ` (${data.progreso_actualizado.toFixed(1)}%)` : '';
            mostrarNotificacion(`Progreso ponderado recalculado exitosamente${progreso}`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion('Error al recalcular progreso', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al recalcular progreso', 'error');
    });
}

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
                
                // Limpiar clases previas
                this.classList.remove('is-invalid', 'is-warning', 'is-valid');
                
                if (isNaN(valor) || valor < 0 || valor > 100) {
                    this.classList.add('is-invalid');
                    this.title = 'El peso debe estar entre 0% y 100%';
                } else if (valor === 0) {
                    this.classList.add('is-warning');
                    this.title = 'Peso 0% - Esta tarea no contribuirá al progreso del proyecto';
                } else if (valor > 50) {
                    this.classList.add('is-warning');
                    this.title = 'Peso alto (>50%) - Verifique que sea correcto';
                } else if (valor > 25) {
                    this.classList.add('is-warning');
                    this.title = 'Peso considerable (>25%) - Revise el balance del proyecto';
                } else {
                    this.classList.add('is-valid');
                    this.title = `Peso: ${valor}% del proyecto total`;
                }
            });

            // Formatear valor al perder el foco
            input.addEventListener('blur', function() {
                const valor = parseFloat(this.value);
                if (!isNaN(valor) && valor >= 0 && valor <= 100) {
                    this.value = valor.toFixed(2);
                }
            });
        });
        
        // Validación para campos de días
        const diasInputs = form.querySelectorAll('input[name="dias_totales"], input[name="duracion_dias"]');
        diasInputs.forEach(input => {
            input.addEventListener('input', function() {
                const valor = parseFloat(this.value);
                
                this.classList.remove('is-invalid', 'is-warning', 'is-valid');
                
                if (isNaN(valor) || valor <= 0) {
                    this.classList.add('is-invalid');
                    this.title = 'Los días deben ser un número positivo';
                } else if (valor > 365) {
                    this.classList.add('is-warning');
                    this.title = 'Duración muy larga (>1 año)';
                } else {
                    this.classList.add('is-valid');
                    this.title = `${valor} días`;
                }
            });
        });
    });
}

// ========== INICIALIZACIÓN ==========

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando sistema con gestión de días totales configurables...');
    
    // Obtener días totales del proyecto actual
    obtenerDiasTotalesProyecto();
    
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
    
    // Auto-completar peso basado en tipo y contexto con días totales
    const tipoSelects = document.querySelectorAll('#tipo_tarea, #editar_tipo_tarea');
    tipoSelects.forEach(select => {
        select.addEventListener('change', function() {
            const pesoInput = this.closest('form').querySelector('input[name="peso_actividad"]');
            if (pesoInput && !modoCalculoAutomatico) {
                // Solo auto-completar en modo manual
                const valores = {'Fase': '20.00', 'Actividad': '5.00', 'Tarea': '1.00'};
                pesoInput.value = valores[this.value] || '1.00';
            }
        });
    });
    
    // Configurar eventos para cálculo automático de peso
    const duracionInput = document.getElementById('duracion_tarea');
    if (duracionInput) {
        duracionInput.addEventListener('input', function() {
            if (modoCalculoAutomatico) {
                calcularPesoAutomatico();
            }
        });
    }
    
    // Configurar tooltips si están disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Validar peso total del proyecto actual
    const proyectoActual = document.querySelector('input[name="proyecto_id"]')?.value;
    if (proyectoActual) {
        // Validación inicial
        setTimeout(() => {
            actualizarEstadisticas(proyectoActual);
        }, 2000);
        
        // Validación periódica
        setInterval(() => {
            actualizarEstadisticas(proyectoActual);
        }, 30000); // Cada 30 segundos
    }
    
    // Configurar validación de formularios
    configurarValidacionFormularios();
    
    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key.toLowerCase()) {
                case 'n':
                    e.preventDefault();
                    const modalNuevaTarea = document.getElementById('modalNuevaTarea');
                    if (modalNuevaTarea) {
                        const modal = new bootstrap.Modal(modalNuevaTarea);
                        modal.show();
                    }
                    break;
                case 'p':
                    e.preventDefault();
                    const modalNuevoProyecto = document.getElementById('modalNuevoProyecto');
                    if (modalNuevoProyecto) {
                        const modal = new bootstrap.Modal(modalNuevoProyecto);
                        modal.show();
                    }
                    break;
                case 'd':
                    e.preventDefault();
                    if (proyectoActual) {
                        distribuirPesoAutomatico(proyectoActual);
                    }
                    break;
            }
        }
    });
    
    console.log('✅ Sistema inicializado con días totales configurables');
});

// ========== EXPORTACIÓN GLOBAL DE FUNCIONES ==========

// Asegurar que todas las funciones estén disponibles globalmente
window.agregarTarea = agregarTarea;
window.editarTarea = editarTarea;
window.guardarEdicionTarea = guardarEdicionTarea;
window.eliminarTarea = eliminarTarea;
window.crearProyecto = crearProyecto;
window.editarProyecto = editarProyecto;
window.guardarEdicionProyecto = guardarEdicionProyecto;
window.duplicarProyecto = duplicarProyecto;
window.eliminarProyecto = eliminarProyecto;
window.mostrarNotificacion = mostrarNotificacion;
window.actualizarEstadisticas = actualizarEstadisticas;
window.distribuirPesoAutomatico = distribuirPesoAutomatico;
window.ejecutarDistribucionSimple = ejecutarDistribucionSimple;
window.exportarProyecto = exportarProyecto;
window.exportarReporte = exportarReporte;
window.importarDatosCafeto = importarDatosCafeto;
window.recalcularProgreso = recalcularProgreso;
window.obtenerDiasTotalesProyecto = obtenerDiasTotalesProyecto;
window.calcularPesoAutomatico = calcularPesoAutomatico;
window.toggleModoCalculoAutomatico = toggleModoCalculoAutomatico;

console.log('✅ Todas las funciones exportadas globalmente');
