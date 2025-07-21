<!-- Modal para nueva tarea -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1" aria-labelledby="modalNuevaTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNuevaTareaLabel">
                    <i class="fas fa-plus"></i> Nueva Tarea
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaTarea">
                    <input type="hidden" name="proyecto_id" value="<?= $proyecto_actual_id ?>">
                    
                    <!-- Fila 1: Nombre y Tipo -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nombre_tarea" class="form-label">Nombre de la Tarea *</label>
                                <input type="text" class="form-control" id="nombre_tarea" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo_tarea" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo_tarea" name="tipo" required>
                                    <option value="Fase">Fase</option>
                                    <option value="Actividad">Actividad</option>
                                    <option value="Tarea" selected>Tarea</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 2: Fase Principal y Contrato -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="fase_principal_tarea" class="form-label">Fase Principal</label>
                                <input type="text" class="form-control" id="fase_principal_tarea" name="fase_principal" 
                                       placeholder="Ej: 1. Recepción de planos constructivos">
                                <div class="form-text">Agrupa las tareas por fase del proyecto</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contrato_tarea" class="form-label">Tipo de Contrato</label>
                                <select class="form-select" id="contrato_tarea" name="contrato">
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Contrato Clave">Contrato Clave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 3: Peso y Duración -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="peso_actividad_tarea" class="form-label">Peso de Actividad</label>
                                <input type="number" class="form-control" id="peso_actividad_tarea" name="peso_actividad" 
                                       step="0.0001" min="0" max="1" value="0.0000">
                                <div class="form-text">Determina la importancia relativa (0.0001 - 1.0000)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duracion_tarea" class="form-label">Duración (días)</label>
                                <input type="number" class="form-control" id="duracion_tarea" name="duracion_dias" min="0" value="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 4: Estado y Porcentaje -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado_tarea" class="form-label">Estado *</label>
                                <select class="form-select" id="estado_tarea" name="estado" required>
                                    <option value="Pendiente" selected>Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Listo">Listo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="porcentaje_tarea" class="form-label">Porcentaje de avance</label>
                                <input type="range" class="form-range" id="porcentaje_tarea" name="porcentaje_avance" min="0" max="100" value="0">
                                <div class="d-flex justify-content-between">
                                    <span>0%</span>
                                    <span id="porcentajeValor" class="fw-bold">0%</span>
                                    <span>100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="agregarTarea()">
                    <i class="fas fa-save"></i> Guardar Tarea
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar tarea -->
<div class="modal fade" id="modalEditarTarea" tabindex="-1" aria-labelledby="modalEditarTareaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditarTareaLabel">
                    <i class="fas fa-edit"></i> Editar Tarea
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarTarea">
                    <input type="hidden" id="editar_tarea_id" name="id">
                    
                    <!-- Fila 1: Nombre y Tipo -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="editar_nombre_tarea" class="form-label">Nombre de la Tarea *</label>
                                <input type="text" class="form-control" id="editar_nombre_tarea" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editar_tipo_tarea" class="form-label">Tipo *</label>
                                <select class="form-select" id="editar_tipo_tarea" name="tipo" required>
                                    <option value="Fase">Fase</option>
                                    <option value="Actividad">Actividad</option>
                                    <option value="Tarea">Tarea</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 2: Fase Principal y Contrato -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="editar_fase_principal_tarea" class="form-label">Fase Principal</label>
                                <input type="text" class="form-control" id="editar_fase_principal_tarea" name="fase_principal">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editar_contrato_tarea" class="form-label">Tipo de Contrato</label>
                                <select class="form-select" id="editar_contrato_tarea" name="contrato">
                                    <option value="Normal">Normal</option>
                                    <option value="Contrato Clave">Contrato Clave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 3: Peso y Duración -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_peso_actividad_tarea" class="form-label">Peso de Actividad</label>
                                <input type="number" class="form-control" id="editar_peso_actividad_tarea" name="peso_actividad" 
                                       step="0.0001" min="0" max="1">
                                <div class="form-text">Importancia relativa en el proyecto</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_duracion_tarea" class="form-label">Duración (días)</label>
                                <input type="number" class="form-control" id="editar_duracion_tarea" name="duracion_dias" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 4: Estado y Porcentaje -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_estado_tarea" class="form-label">Estado *</label>
                                <select class="form-select" id="editar_estado_tarea" name="estado" required>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Listo">Listo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_porcentaje_tarea" class="form-label">Porcentaje de avance</label>
                                <input type="range" class="form-range" id="editar_porcentaje_tarea" name="porcentaje_avance" min="0" max="100">
                                <div class="d-flex justify-content-between">
                                    <span>0%</span>
                                    <span id="editarPorcentajeValor" class="fw-bold">0%</span>
                                    <span>100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="guardarEdicionTarea()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
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
                                <label for="nombre_proyecto" class="form-label">Nombre del Proyecto *</label>
                                <input type="text" class="form-control" id="nombre_proyecto" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cliente_proyecto" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="cliente_proyecto" name="cliente">
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
                                <input type="number" class="form-control" id="presupuesto_proyecto" name="presupuesto" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado_proyecto" class="form-label">Estado</label>
                                <select class="form-select" id="estado_proyecto" name="estado" required>
                                    <option value="Activo" selected>Activo</option>
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
                            <?php if (isset($proyectos) && !empty($proyectos)): ?>
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <option value="<?= $proyecto['id'] ?>">
                                        <?= htmlspecialchars($proyecto['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Si seleccionas un proyecto, se copiarán todas sus tareas al nuevo proyecto.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="crearProyecto()">
                    <i class="fas fa-save"></i> Crear Proyecto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar proyecto -->
<div class="modal fade" id="modalEditarProyecto" tabindex="-1" aria-labelledby="modalEditarProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditarProyectoLabel">
                    <i class="fas fa-edit"></i> Editar Proyecto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarProyecto">
                    <input type="hidden" id="editarProyectoId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarNombreProyecto" class="form-label">Nombre del Proyecto *</label>
                                <input type="text" class="form-control" id="editarNombreProyecto" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarClienteProyecto" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="editarClienteProyecto" name="cliente">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editarDescripcionProyecto" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editarDescripcionProyecto" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarFechaInicio" class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" id="editarFechaInicio" name="fecha_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarFechaFin" class="form-label">Fecha Fin Estimada</label>
                                <input type="date" class="form-control" id="editarFechaFin" name="fecha_fin_estimada">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarPresupuesto" class="form-label">Presupuesto</label>
                                <input type="number" class="form-control" id="editarPresupuesto" name="presupuesto" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarEstadoProyecto" class="form-label">Estado</label>
                                <select class="form-select" id="editarEstadoProyecto" name="estado" required>
                                    <option value="Activo">Activo</option>
                                    <option value="Pausado">Pausado</option>
                                    <option value="Terminado">Terminado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="guardarEdicionProyecto()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para importar desde Excel -->
<div class="modal fade" id="modalImportarExcel" tabindex="-1" aria-labelledby="modalImportarExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalImportarExcelLabel">
                    <i class="fas fa-file-excel"></i> Importar desde Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Formato esperado:</strong> El archivo Excel debe tener las columnas: ACTIVIDAD, Tipo, CONTRATO, Fase, Peso de actividad, días, Estado, Avance real
                </div>
                
                <form id="formImportarExcel">
                    <input type="hidden" name="proyecto_id" value="<?= $proyecto_actual_id ?? 1 ?>">
                    
                    <div class="mb-3">
                        <label for="archivoExcel" class="form-label">Seleccionar archivo Excel</label>
                        <input type="file" class="form-control" id="archivoExcel" accept=".xlsx,.xls" required>
                        <div class="form-text">Formatos soportados: .xlsx, .xls</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="limpiarTareasExistentes" name="limpiar_existentes" checked>
                            <label class="form-check-label" for="limpiarTareasExistentes">
                                Eliminar tareas existentes antes de importar
                            </label>
                        </div>
                    </div>
                    
                    <div id="previewImportacion" class="d-none">
                        <h6>Vista previa de datos a importar:</h6>
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Actividad</th>
                                        <th>Tipo</th>
                                        <th>Peso</th>
                                        <th>Estado</th>
                                        <th>Fase</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaPreview">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnImportar" onclick="procesarImportacion()" disabled>
                    <i class="fas fa-upload"></i> Importar Datos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Actualizar porcentaje en tiempo real en los modales
document.addEventListener('DOMContentLoaded', function() {
    // Modal nuevo
    const porcentajeTarea = document.getElementById('porcentaje_tarea');
    const porcentajeValor = document.getElementById('porcentajeValor');
    
    // Modal editar
    const editarPorcentajeTarea = document.getElementById('editar_porcentaje_tarea');
    const editarPorcentajeValor = document.getElementById('editarPorcentajeValor');
    
    if (porcentajeTarea && porcentajeValor) {
        porcentajeTarea.addEventListener('input', function() {
            porcentajeValor.textContent = this.value + '%';
        });
    }
    
    if (editarPorcentajeTarea && editarPorcentajeValor) {
        editarPorcentajeTarea.addEventListener('input', function() {
            editarPorcentajeValor.textContent = this.value + '%';
        });
    }
    
    // Auto-completar peso basado en tipo
    const tipoSelect = document.getElementById('tipo_tarea');
    const pesoInput = document.getElementById('peso_actividad_tarea');
    
    if (tipoSelect && pesoInput) {
        tipoSelect.addEventListener('change', function() {
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
            }
        });
    }
    
    // Manejador de archivos Excel
    const archivoExcel = document.getElementById('archivoExcel');
    if (archivoExcel) {
        archivoExcel.addEventListener('change', procesarArchivoExcel);
    }
});

// Función para procesar archivo Excel (placeholder)
function procesarArchivoExcel(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const btnImportar = document.getElementById('btnImportar');
    const preview = document.getElementById('previewImportacion');
    
    // Aquí iría la lógica para leer el Excel con una librería como SheetJS
    // Por ahora solo habilitamos el botón
    btnImportar.disabled = false;
    preview.classList.remove('d-none');
    
    // Mensaje temporal
    document.getElementById('tablaPreview').innerHTML = 
        '<tr><td colspan="5" class="text-center">Procesando archivo...</td></tr>';
}

// Función para procesar importación
function procesarImportacion() {
    mostrarNotificacion('Función de importación en desarrollo', 'info');
}
</script>
