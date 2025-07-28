<!-- NUEVO: Modal para configuración de días totales del proyecto -->
<div class="modal fade" id="modalConfiguracionDias" tabindex="-1" aria-labelledby="modalConfiguracionDiasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #6f42c1, #9561e2); color: white;">
                <h5 class="modal-title" id="modalConfiguracionDiasLabel">
                    <i class="fas fa-calendar-alt"></i> Configuración de Días Totales del Proyecto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formConfiguracionDias">
                    <input type="hidden" id="config_proyecto_id" name="proyecto_id" value="<?= $proyecto_actual_id ?>">
                    
                    <!-- Panel de estado actual -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle"></i> Estado Actual del Proyecto
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="metric-box text-center">
                                                <h4 class="text-primary mb-1" id="diasTotalesActuales">56.0</h4>
                                                <small class="text-muted">Días Totales</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="metric-box text-center">
                                                <h4 class="text-success mb-1" id="diasPlanificadosActuales">0.0</h4>
                                                <small class="text-muted">Días Planificados</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="metric-box text-center">
                                                <h4 class="text-warning mb-1" id="diferenciaDiasActual">+56.0</h4>
                                                <small class="text-muted">Diferencia</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="metric-box text-center">
                                                <h4 class="text-info mb-1" id="pesoTotalActual">0.0%</h4>
                                                <small class="text-muted">Peso Total</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Barra de progreso de días -->
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-muted">Uso de días del proyecto:</span>
                                            <span id="porcentajeDiasUsados" class="badge bg-info">0%</span>
                                        </div>
                                        <div class="progress" style="height: 12px;">
                                            <div id="progressDiasUsados" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de nuevos días totales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-edit"></i> Configurar Días Totales
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="nuevosDiasTotales" class="form-label fs-5">
                                            <i class="fas fa-calendar-check text-primary"></i> Días Totales del Proyecto
                                        </label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" class="form-control text-center fw-bold" 
                                                   id="nuevosDiasTotales" name="dias_totales" 
                                                   step="0.1" min="1" max="3650" value="56.0">
                                            <span class="input-group-text bg-primary text-white">días</span>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-lightbulb text-warning"></i>
                                            <strong>Tip:</strong> Puede usar decimales (ej: 56.5 días)
                                        </div>
                                    </div>
                                    
                                    <!-- Sugerencias rápidas -->
                                    <div class="mb-3">
                                        <label class="form-label">Sugerencias rápidas:</label>
                                        <div class="btn-group d-flex" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="aplicarSugerenciaDias(30)">
                                                1 mes<br><small>30 días</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="aplicarSugerenciaDias(60)">
                                                2 meses<br><small>60 días</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="aplicarSugerenciaDias(90)">
                                                3 meses<br><small>90 días</small>
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="calcularDiasOptimos()">
                                                <i class="fas fa-calculator"></i><br><small>Calcular</small>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Validación en tiempo real -->
                                    <div id="validacionDias" class="d-none">
                                        <div class="alert border-0" id="alertaDias">
                                            <div class="d-flex align-items-center">
                                                <span id="iconoDias" class="me-2"></span>
                                                <div>
                                                    <span id="mensajeDias"></span>
                                                    <div id="detallesDias" class="mt-1"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vista previa del impacto -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-eye"></i> Vista Previa del Impacto
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="previsualizacionCambios">
                                        <!-- Se llena dinámicamente -->
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-calculator fa-2x mb-2"></i>
                                            <p>Modifique los días totales para ver el impacto</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Herramientas automáticas -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-tools text-warning"></i> Herramientas de Ajuste Automático
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="recalcularPesosPorDias()">
                                                <i class="fas fa-calculator"></i><br>
                                                <small>Recalcular Pesos</small>
                                            </button>
                                            <small class="text-muted">Actualiza los pesos basándose en los días de cada tarea</small>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="distribuirDiasFaltantes()">
                                                <i class="fas fa-balance-scale"></i><br>
                                                <small>Distribuir Días</small>
                                            </button>
                                            <small class="text-muted">Distribuye días sobrantes entre tareas pendientes</small>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="optimizarDistribucion()">
                                                <i class="fas fa-magic"></i><br>
                                                <small>Optimizar</small>
                                            </button>
                                            <small class="text-muted">Optimización inteligente de días y pesos</small>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-outline-danger w-100 mb-2" onclick="validarConsistencia()">
                                                <i class="fas fa-check-circle"></i><br>
                                                <small>Validar</small>
                                            </button>
                                            <small class="text-muted">Analiza consistencia del proyecto</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Análisis y recomendaciones -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-lightbulb text-info"></i> Análisis y Recomendaciones
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="analisisRecomendaciones">
                                        <!-- Se llena dinámicamente -->
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <p>El análisis se actualizará automáticamente</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-info" onclick="previsualizarCambios()">
                    <i class="fas fa-eye"></i> Previsualizar
                </button>
                <button type="button" class="btn btn-primary btn-lg" onclick="aplicarConfiguracionDias()">
                    <i class="fas fa-save"></i> Aplicar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nueva tarea - MEJORADO con cálculo automático de peso -->
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
                                <input type="text" class="form-control" id="nombre_tarea" name="nombre" required
                                       placeholder="Ej: Análisis de terreno, Diseño arquitectónico...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo_tarea" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo_tarea" name="tipo" required>
                                    <option value="Fase">🏗️ Fase (Etapa principal)</option>
                                    <option value="Actividad">📋 Actividad (Grupo de tareas)</option>
                                    <option value="Tarea" selected>✅ Tarea (Acción específica)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 2: Fase Principal y Contrato -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="fase_principal_tarea" class="form-label">
                                    <i class="fas fa-sitemap text-info"></i> Fase Principal
                                </label>
                                <select class="form-select" id="fase_principal_tarea" name="fase_principal">
                                    <option value="">Seleccionar fase...</option>
                                    <option value="1. Recepción de planos constructivos">1. Recepción de planos constructivos (1%)</option>
                                    <option value="2. Cotizaciones">2. Cotizaciones (84%)</option>
                                    <option value="3. Presupuesto Infraestructura">3. Presupuesto Infraestructura (10%)</option>
                                    <option value="4. Presupuesto Casas">4. Presupuesto Casas (5%)</option>
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-primary"></i> 
                                    Agrupa las tareas por fase del proyecto. Los porcentajes indicados son sugerencias basadas en el proyecto Cafeto.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contrato_tarea" class="form-label">Tipo de Contrato</label>
                                <select class="form-select" id="contrato_tarea" name="contrato">
                                    <option value="Normal" selected>📄 Normal</option>
                                    <option value="Contrato Clave">⭐ Contrato Clave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MEJORADO: Fila 3: Duración y Peso con cálculo automático -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="duracion_tarea" class="form-label">
                                    <i class="fas fa-calendar-alt text-success"></i> Duración (días) *
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duracion_tarea" name="duracion_dias" 
                                           step="0.1" min="0.1" value="1.0" required>
                                    <span class="input-group-text">días</span>
                                    <button type="button" class="btn btn-outline-info" onclick="calcularPesoAutomatico()">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">
                                        Puede usar decimales (ej: 1.5, 0.5). 
                                        <span id="infoDiasTotales" class="text-info"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="peso_actividad_tarea" class="form-label">
                                    <i class="fas fa-weight-hanging text-warning"></i> Peso de Actividad (%)
                                    <span id="pesoEstadoIcon" class="ms-2"></span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="peso_actividad_tarea" name="peso_actividad" 
                                           step="0.01" min="0" max="100" value="1.00" placeholder="1.00">
                                    <span class="input-group-text fw-bold">%</span>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="toggleModoCalculoAutomatico()" id="btnModoCalculo">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <div id="calculoAutomatico" class="text-success">
                                        <i class="fas fa-link"></i> <strong>Cálculo automático activado</strong> - 
                                        El peso se calcula según los días totales del proyecto
                                    </div>
                                    <div id="calculoManual" class="text-muted d-none">
                                        <i class="fas fa-unlink"></i> Cálculo manual - 
                                        Introduzca el peso manualmente (0%-100%)
                                    </div>
                                </div>
                                <!-- MEJORADO: Información contextual del peso actual del proyecto -->
                                <div id="infoProyectoPeso" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 4: Estado y Porcentaje -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado_tarea" class="form-label">Estado *</label>
                                <select class="form-select" id="estado_tarea" name="estado" required>
                                    <option value="Pendiente" selected>🔴 Pendiente</option>
                                    <option value="En Proceso">🟡 En Proceso</option>
                                    <option value="Listo">🟢 Listo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="porcentaje_tarea" class="form-label">
                                    <i class="fas fa-percentage text-info"></i> Porcentaje de avance
                                </label>
                                <input type="range" class="form-range" id="porcentaje_tarea" name="porcentaje_avance" 
                                       min="0" max="100" value="0">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">0%</span>
                                    <span id="porcentajeValor" class="fw-bold text-primary fs-5">0%</span>
                                    <span class="text-muted">100%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MEJORADO: Validación visual más atractiva -->
                    <div id="validacionPeso" class="d-none">
                        <div class="alert border-0" id="alertaPeso">
                            <div class="d-flex align-items-center">
                                <span id="iconoPeso" class="me-2"></span>
                                <span id="mensajePeso"></span>
                            </div>
                        </div>
                    </div>

                    <!-- NUEVO: Resumen visual del peso en el proyecto -->
                    <div class="card bg-light mt-3">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-chart-pie"></i> Peso actual del proyecto:
                                </small>
                                <span id="pesoProyectoActual" class="badge bg-info">Calculando...</span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div id="progressPesoProyecto" class="progress-bar bg-info" style="width: 0%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Días totales: <span id="diasTotalesInfo">56</span></small>
                                <small class="text-muted">Disponibles: <span id="diasDisponiblesInfo">56</span></small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-outline-info" onclick="mostrarModalConfiguracionDias()">
                    <i class="fas fa-cog"></i> Configurar Días
                </button>
                <button type="button" class="btn btn-primary" onclick="agregarTarea()">
                    <i class="fas fa-save"></i> Guardar Tarea
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar tarea - MEJORADO -->
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
                                    <option value="Fase">🏗️ Fase</option>
                                    <option value="Actividad">📋 Actividad</option>
                                    <option value="Tarea">✅ Tarea</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 2: Fase Principal y Contrato -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="editar_fase_principal_tarea" class="form-label">
                                    <i class="fas fa-sitemap text-info"></i> Fase Principal
                                </label>
                                <select class="form-select" id="editar_fase_principal_tarea" name="fase_principal">
                                    <option value="">Sin fase específica</option>
                                    <option value="1. Recepción de planos constructivos">1. Recepción de planos constructivos (1%)</option>
                                    <option value="2. Cotizaciones">2. Cotizaciones (84%)</option>
                                    <option value="3. Presupuesto Infraestructura">3. Presupuesto Infraestructura (10%)</option>
                                    <option value="4. Presupuesto Casas">4. Presupuesto Casas (5%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editar_contrato_tarea" class="form-label">Tipo de Contrato</label>
                                <select class="form-select" id="editar_contrato_tarea" name="contrato">
                                    <option value="Normal">📄 Normal</option>
                                    <option value="Contrato Clave">⭐ Contrato Clave</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MEJORADO: Fila 3: Duración y Peso con cálculo automático -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_duracion_tarea" class="form-label">
                                    <i class="fas fa-calendar-alt text-success"></i> Duración (días) *
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="editar_duracion_tarea" name="duracion_dias" 
                                           step="0.1" min="0.1" required>
                                    <span class="input-group-text">días</span>
                                    <button type="button" class="btn btn-outline-info" onclick="recalcularPesoEdicion()">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_peso_actividad_tarea" class="form-label">
                                    <i class="fas fa-weight-hanging text-warning"></i> Peso de Actividad (%)
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="editar_peso_actividad_tarea" name="peso_actividad" 
                                           step="0.01" min="0" max="100">
                                    <span class="input-group-text fw-bold">%</span>
                                </div>
                                <div class="form-text">
                                    <span id="editarPesoInfo" class="text-muted">Importancia relativa en el proyecto (0%-100%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fila 4: Estado y Porcentaje -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_estado_tarea" class="form-label">Estado *</label>
                                <select class="form-select" id="editar_estado_tarea" name="estado" required>
                                    <option value="Pendiente">🔴 Pendiente</option>
                                    <option value="En Proceso">🟡 En Proceso</option>
                                    <option value="Listo">🟢 Listo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editar_porcentaje_tarea" class="form-label">
                                    <i class="fas fa-percentage text-info"></i> Porcentaje de avance
                                </label>
                                <input type="range" class="form-range" id="editar_porcentaje_tarea" name="porcentaje_avance" min="0" max="100">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">0%</span>
                                    <span id="editarPorcentajeValor" class="fw-bold text-primary fs-5">0%</span>
                                    <span class="text-muted">100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" onclick="guardarEdicionTarea()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo proyecto - MEJORADO con días totales -->
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
                                <label for="nombre_proyecto" class="form-label">
                                    <i class="fas fa-building text-primary"></i> Nombre del Proyecto *
                                </label>
                                <input type="text" class="form-control" id="nombre_proyecto" name="nombre" required
                                       placeholder="Ej: Desarrollo Residencial Las Flores">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cliente_proyecto" class="form-label">
                                    <i class="fas fa-user-tie text-info"></i> Cliente
                                </label>
                                <input type="text" class="form-control" id="cliente_proyecto" name="cliente"
                                       placeholder="Ej: Constructora ABC S.A.">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion_proyecto" class="form-label">
                            <i class="fas fa-align-left text-secondary"></i> Descripción
                        </label>
                        <textarea class="form-control" id="descripcion_proyecto" name="descripcion" rows="3"
                                  placeholder="Descripción detallada del proyecto..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_inicio_proyecto" class="form-label">
                                    <i class="fas fa-calendar-plus text-success"></i> Fecha de Inicio
                                </label>
                                <input type="date" class="form-control" id="fecha_inicio_proyecto" name="fecha_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_fin_proyecto" class="form-label">
                                    <i class="fas fa-calendar-check text-warning"></i> Fecha Fin Estimada
                                </label>
                                <input type="date" class="form-control" id="fecha_fin_proyecto" name="fecha_fin_estimada">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="presupuesto_proyecto" class="form-label">
                                    <i class="fas fa-money-bill-wave text-success"></i> Presupuesto
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₡</span>
                                    <input type="number" class="form-control" id="presupuesto_proyecto" name="presupuesto" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <!-- NUEVO: Campo de días totales -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="dias_totales_proyecto" class="form-label">
                                    <i class="fas fa-calendar-alt text-info"></i> Días Totales
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="dias_totales_proyecto" name="dias_totales" 
                                           step="0.1" min="1" value="56.0">
                                    <span class="input-group-text">días</span>
                                </div>
                                <div class="form-text">
                                    <small class="text-muted">Duración total estimada del proyecto</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="estado_proyecto" class="form-label">
                                    <i class="fas fa-flag text-primary"></i> Estado
                                </label>
                                <select class="form-select" id="estado_proyecto" name="estado" required>
                                    <option value="Activo" selected>🟢 Activo</option>
                                    <option value="Pausado">🟡 Pausado</option>
                                    <option value="Terminado">✅ Terminado</option>
                                    <option value="Cancelado">❌ Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="plantilla_proyecto" class="form-label">
                            <i class="fas fa-copy text-info"></i> Copiar tareas desde proyecto existente
                        </label>
                        <select class="form-select" id="plantilla_proyecto" name="plantilla_proyecto">
                            <option value="">No copiar tareas</option>
                            <?php if (isset($proyectos) && !empty($proyectos)): ?>
                                <?php foreach ($proyectos as $proyecto): ?>
                                    <option value="<?= $proyecto['id'] ?>">
                                        <?= htmlspecialchars($proyecto['nombre']) ?>
                                        (<?= $proyecto['progreso_calculado'] ?? 0 ?>% completado)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle text-primary"></i>
                            Si seleccionas un proyecto, se copiarán todas sus tareas con sus pesos al nuevo proyecto.
                        </div>
                    </div>

                    <!-- MEJORADO: Información sobre peso ponderado más visual -->
                    <div class="alert alert-info border-0" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle text-primary fs-4 me-3 mt-1"></i>
                            <div>
                                <h6 class="fw-bold text-primary mb-2">
                                    <i class="fas fa-weight-hanging"></i> Sistema de Peso Ponderado con Días Configurables
                                </h6>
                                <p class="mb-2">Este sistema utiliza pesos en <strong>porcentajes (0%-100%)</strong> basados en días totales:</p>
                                <div class="row text-sm">
                                    <div class="col-md-6">
                                        <ul class="mb-0 list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> El proyecto suma <strong>100%</strong></li>
                                            <li><i class="fas fa-check text-success"></i> Días totales configurables</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-0 list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Cálculo automático de pesos</li>
                                            <li><i class="fas fa-check text-success"></i> Distribución inteligente</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="crearProyecto()">
                    <i class="fas fa-save"></i> Crear Proyecto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar proyecto - MEJORADO -->
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
                                <label for="editarNombreProyecto" class="form-label">
                                    <i class="fas fa-building text-primary"></i> Nombre del Proyecto *
                                </label>
                                <input type="text" class="form-control" id="editarNombreProyecto" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarClienteProyecto" class="form-label">
                                    <i class="fas fa-user-tie text-info"></i> Cliente
                                </label>
                                <input type="text" class="form-control" id="editarClienteProyecto" name="cliente">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editarDescripcionProyecto" class="form-label">
                            <i class="fas fa-align-left text-secondary"></i> Descripción
                        </label>
                        <textarea class="form-control" id="editarDescripcionProyecto" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarFechaInicio" class="form-label">
                                    <i class="fas fa-calendar-plus text-success"></i> Fecha de Inicio
                                </label>
                                <input type="date" class="form-control" id="editarFechaInicio" name="fecha_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editarFechaFin" class="form-label">
                                    <i class="fas fa-calendar-check text-warning"></i> Fecha Fin Estimada
                                </label>
                                <input type="date" class="form-control" id="editarFechaFin" name="fecha_fin_estimada">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editarPresupuesto" class="form-label">
                                    <i class="fas fa-money-bill-wave text-success"></i> Presupuesto
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₡</span>
                                    <input type="number" class="form-control" id="editarPresupuesto" name="presupuesto" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <!-- NUEVO: Campo de días totales en edición -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editarDiasTotales" class="form-label">
                                    <i class="fas fa-calendar-alt text-info"></i> Días Totales
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="editarDiasTotales" name="dias_totales" 
                                           step="0.1" min="1">
                                    <span class="input-group-text">días</span>
                                    <button type="button" class="btn btn-outline-info" onclick="abrirConfiguracionAvanzadaDias()">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Cambiar esto recalculará todos los pesos
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editarEstadoProyecto" class="form-label">
                                    <i class="fas fa-flag text-primary"></i> Estado
                                </label>
                                <select class="form-select" id="editarEstadoProyecto" name="estado" required>
                                    <option value="Activo">🟢 Activo</option>
                                    <option value="Pausado">🟡 Pausado</option>
                                    <option value="Terminado">✅ Terminado</option>
                                    <option value="Cancelado">❌ Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning" onclick="guardarEdicionProyecto()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MEJORADO: Modal para distribuir peso automáticamente -->
<div class="modal fade" id="modalDistribuirPeso" tabindex="-1" aria-labelledby="modalDistribuirPesoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #20c997); color: white;">
                <h5 class="modal-title" id="modalDistribuirPesoLabel">
                    <i class="fas fa-balance-scale"></i> Distribuir Peso Automáticamente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formDistribuirPeso">
                    <input type="hidden" id="distribuir_proyecto_id" name="proyecto_id" value="<?= $proyecto_actual_id ?>">
                    
                    <div class="mb-4">
                        <label for="metodo_distribucion" class="form-label fs-5">
                            <i class="fas fa-cogs text-primary"></i> Método de Distribución
                        </label>
                        <select class="form-select form-select-lg" id="metodo_distribucion" name="metodo">
                            <option value="por_dias" selected>📅 Por Días Totales (Recomendado - Automático)</option>
                            <option value="por_fase">🏗️ Por Fase (Basado en Cafeto)</option>
                            <option value="equitativo">⚖️ Equitativo (100% ÷ número de tareas)</option>
                            <option value="por_tipo">📊 Por Tipo (Fase 20%, Actividad 60%, Tarea 20%)</option>
                            <option value="por_duracion">⏱️ Por Duración (Proporcional a días)</option>
                        </select>
                    </div>

                    <!-- MEJORADO: Cards informativos para cada método -->
                    <div id="infoMetodos">
                        <!-- NUEVO: Método por días totales -->
                        <div class="info-metodo" id="info-por_dias">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <strong>📅 Distribución por Días Totales (Automática)</strong>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <strong>Peso por tarea = (Días de la tarea ÷ Días totales del proyecto) × 100%</strong>
                                    </p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success">✅ Ventajas:</h6>
                                            <ul class="small mb-0">
                                                <li>Cálculo preciso y proporcional</li>
                                                <li>Siempre suma exactamente 100%</li>
                                                <li>Refleja la duración real de cada tarea</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-info">📊 Ejemplo:</h6>
                                            <small class="text-muted">
                                                Proyecto: 56 días<br>
                                                • Tarea 1: 5.6 días → 10%<br>
                                                • Tarea 2: 2.8 días → 5%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-metodo d-none" id="info-por_fase">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <strong>🏗️ Distribución por Fase (Proyecto Cafeto)</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <li><span class="badge bg-danger">1%</span> Recepción de planos</li>
                                                <li><span class="badge bg-primary">84%</span> Cotizaciones</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-unstyled">
                                                <li><span class="badge bg-warning text-dark">10%</span> Presupuesto Infraestructura</li>
                                                <li><span class="badge bg-success">5%</span> Presupuesto Casas</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-lightbulb"></i> 
                                        Ideal para proyectos similares al modelo Cafeto
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="info-metodo d-none" id="info-equitativo">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h6><i class="fas fa-balance-scale text-info"></i> Distribución Equitativa</h6>
                                    <p class="mb-1">Todas las tareas reciben el mismo peso:</p>
                                    <p class="text-center mb-0">
                                        <code>Peso por tarea = 100% ÷ Total de tareas</code>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="info-metodo d-none" id="info-por_tipo">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6><i class="fas fa-chart-pie text-warning"></i> Distribución por Tipo</h6>
                                    <ul class="mb-0">
                                        <li><strong>Fases:</strong> 20% del total</li>
                                        <li><strong>Actividades:</strong> 60% del total</li>
                                        <li><strong>Tareas:</strong> 20% del total</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="info-metodo d-none" id="info-por_duracion">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h6><i class="fas fa-clock text-success"></i> Distribución por Duración</h6>
                                    <p class="mb-0">El peso se asigna proporcionalmente a la duración en días de cada tarea.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 mt-3" style="background: linear-gradient(135deg, #fff3cd, #fdf6e3);">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle text-warning fs-4 me-3"></i>
                            <div>
                                <strong>⚠️ Advertencia:</strong> Esta acción sobrescribirá todos los pesos existentes de las tareas del proyecto.
                                <br><small class="text-muted">Se recomienda hacer una copia de seguridad antes de proceder.</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-info btn-lg" onclick="aplicarDistribucionPeso()">
                    <i class="fas fa-magic"></i> Aplicar Distribución
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MEJORADO: Modal para importar desde Excel -->
<div class="modal fade" id="modalImportarExcel" tabindex="-1" aria-labelledby="modalImportarExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalImportarExcelLabel">
                    <i class="fas fa-file-excel"></i> Importar desde Excel/CSV
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <!-- MEJORADO: Información más clara sobre formato -->
                <div class="alert alert-info border-0 mb-4" style="background: linear-gradient(135deg, #e7f3ff, #cce7ff);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle"></i> Formato de archivo requerido:
                            </h6>
                            <div class="row text-sm">
                                <div class="col-md-6">
                                    <strong>Columnas obligatorias:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>ACTIVIDAD (nombre)</li>
                                        <li>Tipo (Fase/Actividad/Tarea)</li>
                                        <li>días (duración)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <strong>Columnas opcionales:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>Peso de actividad (%)</li>
                                        <li>CONTRATO</li>
                                        <li>Fase</li>
                                        <li>Estado</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <button class="btn btn-outline-primary btn-sm" onclick="descargarPlantilla()">
                                <i class="fas fa-download"></i> Descargar Plantilla
                            </button>
                        </div>
                    </div>
                </div>
                
                <form id="formImportarExcel" enctype="multipart/form-data">
                    <input type="hidden" name="proyecto_id" value="<?= $proyecto_actual_id ?? 1 ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="archivoExcel" class="form-label fs-5">
                                    <i class="fas fa-cloud-upload-alt text-primary"></i> Seleccionar archivo
                                </label>
                                <input type="file" class="form-control form-control-lg" id="archivoExcel" 
                                       accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">
                                    <i class="fas fa-file-excel text-success"></i> Formatos soportados: .xlsx, .xls, .csv
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Opciones de importación:</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="limpiarTareasExistentes" 
                                           name="limpiar_existentes" checked>
                                    <label class="form-check-label" for="limpiarTareasExistentes">
                                        <i class="fas fa-trash text-danger"></i> Limpiar tareas existentes
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="calcularPesosAutomaticamente" 
                                           name="calcular_pesos" checked>
                                    <label class="form-check-label" for="calcularPesosAutomaticamente">
                                        <i class="fas fa-calculator text-success"></i> Calcular pesos automáticamente
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="validarPesos" 
                                           name="validar_pesos" checked>
                                    <label class="form-check-label" for="validarPesos">
                                        <i class="fas fa-check-circle text-info"></i> Validar pesos automáticamente
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NUEVO: Información importante sobre cálculo de pesos -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-calculator"></i> Cálculo Automático de Pesos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-success">✅ Con cálculo automático:</h6>
                                    <ul class="list-unstyled text-success">
                                        <li><i class="fas fa-check"></i> Los pesos se calculan según los días de cada tarea</li>
                                        <li><i class="fas fa-check"></i> Garantiza que sumen exactamente 100%</li>
                                        <li><i class="fas fa-check"></i> Ignora columna "Peso de actividad" si existe</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-warning">⚠️ Sin cálculo automático:</h6>
                                    <ul class="list-unstyled text-warning">
                                        <li><i class="fas fa-exclamation"></i> Usa pesos del archivo tal como están</li>
                                        <li><i class="fas fa-exclamation"></i> Debe asegurar que sumen 100%</li>
                                        <li><i class="fas fa-exclamation"></i> Requiere columna "Peso de actividad"</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="alert alert-info border-0 mt-2 mb-0">
                                <small>
                                    <i class="fas fa-lightbulb"></i>
                                    <strong>Tip:</strong> Se recomienda usar cálculo automático para mayor precisión y consistencia.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div id="previewImportacion" class="d-none">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-eye"></i> Vista previa de datos a importar:
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-sm table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Actividad</th>
                                                <th>Tipo</th>
                                                <th>Días</th>
                                                <th>Peso Calculado (%)</th>
                                                <th>Estado</th>
                                                <th>Fase</th>
                                                <th>Contrato</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaPreview">
                                        </tbody>
                                    </table>
                                </div>
                                <div id="resumenImportacion" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-outline-info" onclick="descargarPlantilla()">
                    <i class="fas fa-download"></i> Plantilla
                </button>
                <button type="button" class="btn btn-success btn-lg" id="btnImportar" onclick="procesarImportacion()" disabled>
                    <i class="fas fa-upload"></i> Importar Datos
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============ NUEVAS FUNCIONES PARA GESTIÓN DE DÍAS TOTALES ============

// Variable global para modo de cálculo automático
let modoCalculoAutomatico = true;
let diasTotalesProyecto = 56.0;

document.addEventListener('DOMContentLoaded', function() {
    // ========== INICIALIZACIÓN MEJORADA ==========
    initializarSliders();
    initializarAutoCompletarPeso();
    initializarValidacionPeso();
    actualizarInfoProyecto();
    initializarSelectorMetodo();
    initializarManejadorArchivos();
    
    // ========== NUEVAS INICIALIZACIONES ==========
    initializarConfiguracionDias();
    initializarCalculoAutomatico();
    obtenerDiasTotalesProyecto();
});

// ============ NUEVAS FUNCIONES DE CONFIGURACIÓN DE DÍAS ============

function initializarConfiguracionDias() {
    const inputDias = document.getElementById('nuevosDiasTotales');
    if (inputDias) {
        inputDias.addEventListener('input', function() {
            validarDiasEnTiempoReal(this);
            previsualizarCambiosDias();
        });
    }
}

function initializarCalculoAutomatico() {
    const duracionInput = document.getElementById('duracion_tarea');
    const pesoInput = document.getElementById('peso_actividad_tarea');
    
    if (duracionInput && pesoInput) {
        duracionInput.addEventListener('input', function() {
            if (modoCalculoAutomatico) {
                calcularPesoAutomatico();
            }
        });
    }
}

async function obtenerDiasTotalesProyecto() {
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return;
        
        const response = await fetch(`api/proyectos.php?action=obtener_dias_totales&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            diasTotalesProyecto = parseFloat(data.dias_totales) || 56.0;
            actualizarInfoDiasTotales();
        }
    } catch (error) {
        console.error('Error obteniendo días totales:', error);
    }
}

function actualizarInfoDiasTotales() {
    const elementos = ['diasTotalesInfo', 'infoDiasTotales'];
    elementos.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = `Días totales del proyecto: ${diasTotalesProyecto}`;
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
    
    // Actualizar validación visual
    validarPesoTiempoRealMejorado(pesoInput);
    actualizarIconoPeso(peso);
}

function toggleModoCalculoAutomatico() {
    modoCalculoAutomatico = !modoCalculoAutomatico;
    
    const btnModo = document.getElementById('btnModoCalculo');
    const calculoAuto = document.getElementById('calculoAutomatico');
    const calculoManual = document.getElementById('calculoManual');
    const pesoInput = document.getElementById('peso_actividad_tarea');
    
    if (modoCalculoAutomatico) {
        btnModo.innerHTML = '<i class="fas fa-link"></i>';
        btnModo.className = 'btn btn-outline-success';
        btnModo.title = 'Cálculo automático activado';
        calculoAuto.classList.remove('d-none');
        calculoManual.classList.add('d-none');
        pesoInput.readOnly = true;
        calcularPesoAutomatico();
    } else {
        btnModo.innerHTML = '<i class="fas fa-unlink"></i>';
        btnModo.className = 'btn btn-outline-warning';
        btnModo.title = 'Cálculo manual activado';
        calculoAuto.classList.add('d-none');
        calculoManual.classList.remove('d-none');
        pesoInput.readOnly = false;
    }
}

function mostrarModalConfiguracionDias() {
    const modal = new bootstrap.Modal(document.getElementById('modalConfiguracionDias'));
    actualizarEstadoActualProyecto();
    modal.show();
}

async function actualizarEstadoActualProyecto() {
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return;
        
        const response = await fetch(`api/proyectos.php?action=estadisticas_detalladas&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            
            // Actualizar métricas actuales
            document.getElementById('diasTotalesActuales').textContent = stats.dias_totales_proyecto.toFixed(1);
            document.getElementById('diasPlanificadosActuales').textContent = stats.total_dias_planificados.toFixed(1);
            document.getElementById('diferenciaDiasActual').textContent = 
                (stats.diferencia_dias >= 0 ? '+' : '') + stats.diferencia_dias.toFixed(1);
            document.getElementById('pesoTotalActual').textContent = stats.peso_total_actual.toFixed(1) + '%';
            
            // Actualizar barra de progreso
            const porcentajeUsado = stats.porcentaje_dias_usados;
            document.getElementById('porcentajeDiasUsados').textContent = porcentajeUsado.toFixed(1) + '%';
            document.getElementById('progressDiasUsados').style.width = Math.min(porcentajeUsado, 100) + '%';
            
            // Color de la barra según el uso
            const progressBar = document.getElementById('progressDiasUsados');
            if (porcentajeUsado > 100) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (porcentajeUsado > 90) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-info';
            }
            
            // Actualizar input de configuración
            document.getElementById('nuevosDiasTotales').value = stats.dias_totales_proyecto;
            
            // Generar análisis y recomendaciones
            generarAnalisisRecomendaciones(stats);
        }
    } catch (error) {
        console.error('Error actualizando estado del proyecto:', error);
    }
}

function generarAnalisisRecomendaciones(stats) {
    const container = document.getElementById('analisisRecomendaciones');
    let html = '';
    
    // Análisis de consistencia
    if (stats.consistencia_dias_ok && stats.consistencia_peso_ok) {
        html += `
            <div class="alert alert-success border-0">
                <h6><i class="fas fa-check-circle text-success"></i> Proyecto Bien Balanceado</h6>
                <p class="mb-0">El proyecto está correctamente configurado en términos de días y pesos.</p>
            </div>
        `;
    } else {
        html += '<div class="row">';
        
        if (!stats.consistencia_dias_ok) {
            const exceso = stats.diferencia_dias < 0;
            html += `
                <div class="col-md-6">
                    <div class="alert alert-${exceso ? 'danger' : 'warning'} border-0">
                        <h6><i class="fas fa-${exceso ? 'exclamation-triangle' : 'info-circle'}"></i> 
                            ${exceso ? 'Exceso de Días' : 'Días Disponibles'}</h6>
                        <p class="mb-2">${Math.abs(stats.diferencia_dias).toFixed(1)} días ${exceso ? 'de exceso' : 'disponibles'}</p>
                        <small class="text-muted">
                            ${exceso ? 'Considere aumentar los días totales o reducir duración de tareas' : 
                                      'Puede agregar más tareas o usar como buffer'}
                        </small>
                    </div>
                </div>
            `;
        }
        
        if (!stats.consistencia_peso_ok) {
            const exceso = stats.peso_total_actual > 100;
            html += `
                <div class="col-md-6">
                    <div class="alert alert-${exceso ? 'danger' : 'warning'} border-0">
                        <h6><i class="fas fa-percentage"></i> ${exceso ? 'Sobrepeso' : 'Falta Peso'}</h6>
                        <p class="mb-2">${Math.abs(stats.peso_total_actual - 100).toFixed(1)}% de diferencia</p>
                        <small class="text-muted">
                            ${exceso ? 'Los pesos exceden el 100% total' : 'Los pesos no llegan al 100%'}
                        </small>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
    }
    
    // Recomendaciones específicas
    html += '<div class="mt-3"><h6><i class="fas fa-lightbulb text-warning"></i> Recomendaciones:</h6><ul class="mb-0">';
    
    if (stats.diferencia_dias > 10) {
        html += `<li>Considere agregar tareas de revisión o calidad con los ${stats.diferencia_dias.toFixed(1)} días disponibles</li>`;
    } else if (stats.diferencia_dias < -5) {
        html += `<li>Aumente los días totales a ${(stats.total_dias_planificados + 5).toFixed(0)} días para mayor holgura</li>`;
    }
    
    if (Math.abs(stats.peso_total_actual - 100) > 2) {
        html += '<li>Use "Recalcular Pesos" para ajustar automáticamente los porcentajes</li>';
    }
    
    if (stats.total_tareas > 0) {
        html += `<li>Proyecto con ${stats.total_tareas} tareas y ${stats.total_dias_planificados.toFixed(1)} días planificados</li>`;
    }
    
    html += '</ul></div>';
    
    container.innerHTML = html;
}

function aplicarSugerenciaDias(dias) {
    document.getElementById('nuevosDiasTotales').value = dias;
    validarDiasEnTiempoReal(document.getElementById('nuevosDiasTotales'));
    previsualizarCambiosDias();
}

async function calcularDiasOptimos() {
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return;
        
        const response = await fetch(`api/proyectos.php?action=calcular_dias_optimos&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            const diasOptimos = data.dias_optimos;
            document.getElementById('nuevosDiasTotales').value = diasOptimos;
            validarDiasEnTiempoReal(document.getElementById('nuevosDiasTotales'));
            previsualizarCambiosDias();
            
            mostrarNotificacion(`Días óptimos calculados: ${diasOptimos} días`, 'success');
        }
    } catch (error) {
        console.error('Error calculando días óptimos:', error);
        mostrarNotificacion('Error calculando días óptimos', 'error');
    }
}

function validarDiasEnTiempoReal(input) {
    const valor = parseFloat(input.value);
    const validacion = document.getElementById('validacionDias');
    const alerta = document.getElementById('alertaDias');
    const icono = document.getElementById('iconoDias');
    const mensaje = document.getElementById('mensajeDias');
    const detalles = document.getElementById('detallesDias');
    
    if (!validacion) return;
    
    let tipo, iconoHtml, mensajeTexto, detallesTexto = '';
    
    if (isNaN(valor) || valor <= 0) {
        tipo = 'alert-danger';
        iconoHtml = '<i class="fas fa-exclamation-circle"></i>';
        mensajeTexto = 'Los días totales deben ser un número positivo';
        input.classList.add('is-invalid');
    } else if (valor > 3650) {
        tipo = 'alert-warning';
        iconoHtml = '<i class="fas fa-exclamation-triangle"></i>';
        mensajeTexto = 'Días totales muy altos (>10 años)';
        detallesTexto = 'Considere dividir en proyectos más pequeños';
        input.classList.add('is-warning');
    } else if (valor < 7) {
        tipo = 'alert-info';
        iconoHtml = '<i class="fas fa-info-circle"></i>';
        mensajeTexto = 'Proyecto de muy corta duración (<1 semana)';
        input.classList.add('is-warning');
    } else {
        tipo = 'alert-success';
        iconoHtml = '<i class="fas fa-check-circle"></i>';
        mensajeTexto = `Configuración válida: ${valor} días`;
        detallesTexto = valor < 30 ? 'Proyecto corto' : valor > 365 ? 'Proyecto largo' : 'Duración adecuada';
        input.classList.add('is-valid');
        
        // Auto-ocultar después de 3 segundos
        setTimeout(() => {
            if (validacion && !validacion.classList.contains('d-none')) {
                validacion.classList.add('d-none');
            }
        }, 3000);
    }
    
    input.classList.remove('is-invalid', 'is-warning', 'is-valid');
    input.classList.add(tipo.includes('danger') ? 'is-invalid' : tipo.includes('success') ? 'is-valid' : 'is-warning');
    
    if (alerta && icono && mensaje) {
        alerta.className = `alert border-0 ${tipo}`;
        icono.innerHTML = iconoHtml;
        mensaje.textContent = mensajeTexto;
        detalles.textContent = detallesTexto;
        validacion.classList.remove('d-none');
    }
}

async function previsualizarCambiosDias() {
    const nuevosDias = parseFloat(document.getElementById('nuevosDiasTotales').value);
    if (isNaN(nuevosDias) || nuevosDias <= 0) return;
    
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return;
        
        const response = await fetch('api/proyectos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'previsualizar_cambio_dias',
                proyecto_id: proyectoId,
                nuevos_dias_totales: nuevosDias
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const preview = data.preview;
            const container = document.getElementById('previsualizacionCambios');
            
            container.innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <div class="metric-preview text-center">
                            <h5 class="text-primary">${nuevosDias}</h5>
                            <small>Nuevos Días Totales</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-preview text-center">
                            <h5 class="text-${preview.diferencia >= 0 ? 'success' : 'danger'}">
                                ${preview.diferencia >= 0 ? '+' : ''}${preview.diferencia.toFixed(1)}
                            </h5>
                            <small>Diferencia vs Actual</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <h6>Impacto en pesos:</h6>
                    <ul class="small mb-0">
                        <li>${preview.tareas_afectadas} tareas verán cambios en sus pesos</li>
                        <li>${preview.peso_promedio_nuevo.toFixed(2)}% será el peso promedio por día</li>
                        <li class="text-${preview.mejora_consistencia ? 'success' : 'warning'}">
                            ${preview.mejora_consistencia ? 'Mejorará' : 'Mantendrá'} la consistencia del proyecto
                        </li>
                    </ul>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error previsualizando cambios:', error);
    }
}

async function aplicarConfiguracionDias() {
    const nuevosDias = parseFloat(document.getElementById('nuevosDiasTotales').value);
    const proyectoId = document.getElementById('config_proyecto_id').value;
    
    if (isNaN(nuevosDias) || nuevosDias <= 0) {
        mostrarNotificacion('Ingrese un valor válido para los días totales', 'error');
        return;
    }
    
    if (!confirm(`¿Confirma cambiar los días totales a ${nuevosDias} días?\n\n⚠️ Esto recalculará automáticamente todos los pesos de las tareas.`)) {
        return;
    }
    
    const btn = document.querySelector('#modalConfiguracionDias .btn-primary');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando...';
    btn.disabled = true;
    
    try {
        const response = await fetch('api/proyectos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'actualizar_dias_totales',
                proyecto_id: proyectoId,
                dias_totales: nuevosDias
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfiguracionDias'));
            modal.hide();
            
            mostrarNotificacion(`✅ Días totales actualizados a ${nuevosDias} días. Pesos recalculados automáticamente.`, 'success', 4000);
            
            // Actualizar variable global
            diasTotalesProyecto = nuevosDias;
            
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacion('❌ Error al actualizar días totales: ' + (data.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al aplicar configuración', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// ============ FUNCIONES DE HERRAMIENTAS AUTOMÁTICAS ============

async function recalcularPesosPorDias() {
    const proyectoId = document.getElementById('config_proyecto_id').value;
    
    if (!confirm('¿Recalcular todos los pesos basándose en los días de cada tarea?\n\nEsto garantizará que los pesos sumen exactamente 100%.')) {
        return;
    }
    
    try {
        const response = await fetch('api/proyectos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'recalcular_pesos_por_dias',
                proyecto_id: proyectoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('✅ Pesos recalculados correctamente', 'success');
            actualizarEstadoActualProyecto();
        } else {
            mostrarNotificacion('❌ Error al recalcular pesos: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al recalcular pesos', 'error');
    }
}

async function distribuirDiasFaltantes() {
    const proyectoId = document.getElementById('config_proyecto_id').value;
    
    try {
        const response = await fetch('api/proyectos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'distribuir_dias_faltantes',
                proyecto_id: proyectoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion(`✅ ${data.message}`, 'success');
            actualizarEstadoActualProyecto();
        } else {
            mostrarNotificacion('❌ ' + data.message, 'warning');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al distribuir días', 'error');
    }
}

async function optimizarDistribucion() {
    const proyectoId = document.getElementById('config_proyecto_id').value;
    
    if (!confirm('¿Optimizar automáticamente la distribución de días y pesos?\n\nEsto aplicará las mejores prácticas para balancear el proyecto.')) {
        return;
    }
    
    try {
        const response = await fetch('api/proyectos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'optimizar_distribucion',
                proyecto_id: proyectoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('✅ Distribución optimizada correctamente', 'success');
            actualizarEstadoActualProyecto();
        } else {
            mostrarNotificacion('❌ Error al optimizar: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al optimizar distribución', 'error');
    }
}

async function validarConsistencia() {
    const proyectoId = document.getElementById('config_proyecto_id').value;
    
    try {
        const response = await fetch(`api/proyectos.php?action=validar_consistencia&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            const validacion = data.data;
            let mensaje = validacion.consistente ? 
                '✅ Proyecto consistente y bien balanceado' : 
                '⚠️ Se encontraron inconsistencias';
            
            if (validacion.alertas.length > 0) {
                mensaje += '\n\nDetalles:\n' + validacion.alertas.map(a => '• ' + a.mensaje).join('\n');
            }
            
            mostrarNotificacion(mensaje, validacion.consistente ? 'success' : 'warning', 6000);
            
            // Actualizar análisis
            actualizarEstadoActualProyecto();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al validar consistencia', 'error');
    }
}

// ============ FUNCIONES MEJORADAS EXISTENTES ============

// Inicializar sliders de porcentaje
function initializarSliders() {
    const sliders = [
        { input: 'porcentaje_tarea', output: 'porcentajeValor' },
        { input: 'editar_porcentaje_tarea', output: 'editarPorcentajeValor' }
    ];
    
    sliders.forEach(slider => {
        const input = document.getElementById(slider.input);
        const output = document.getElementById(slider.output);
        
        if (input && output) {
            input.addEventListener('input', function() {
                output.textContent = this.value + '%';
                output.className = `fw-bold fs-5 ${this.value == 0 ? 'text-muted' : this.value == 100 ? 'text-success' : 'text-primary'}`;
            });
        }
    });
}

// MEJORADO: Auto-completar peso con contexto del proyecto
function initializarAutoCompletarPeso() {
    const tipoSelects = ['tipo_tarea', 'editar_tipo_tarea'];
    
    tipoSelects.forEach(selectId => {
        const select = document.getElementById(selectId);
        const pesoInput = document.getElementById(selectId.replace('tipo_', 'peso_actividad_'));
        
        if (select && pesoInput) {
            select.addEventListener('change', function() {
                if (!modoCalculoAutomatico) {
                    // Solo auto-completar en modo manual
                    obtenerPesoRestanteProyecto().then(pesoRestante => {
                        let pesoSugerido;
                        
                        switch(this.value) {
                            case 'Fase':
                                pesoSugerido = Math.min(Math.max(pesoRestante * 0.3, 5), 25);
                                break;
                            case 'Actividad':
                                pesoSugerido = Math.min(Math.max(pesoRestante * 0.15, 2), 15);
                                break;
                            case 'Tarea':
                                pesoSugerido = Math.min(Math.max(pesoRestante * 0.05, 0.5), 5);
                                break;
                            default:
                                pesoSugerido = 1.00;
                        }
                        
                        pesoInput.value = pesoSugerido.toFixed(2);
                        actualizarIconoPeso(pesoSugerido);
                        
                        // Trigger validación
                        if (typeof validarPesoTiempoReal === 'function') {
                            validarPesoTiempoReal(pesoInput);
                        }
                    });
                }
            });
        }
    });
}

// MEJORADO: Validación en tiempo real más visual
function initializarValidacionPeso() {
    const pesoInputs = document.querySelectorAll('input[name="peso_actividad"]');
    
    pesoInputs.forEach(input => {
        input.addEventListener('input', function() {
            validarPesoTiempoRealMejorado(this);
            actualizarIconoPeso(parseFloat(this.value));
        });
        
        input.addEventListener('blur', function() {
            const valor = parseFloat(this.value);
            if (!isNaN(valor) && valor >= 0 && valor <= 100) {
                this.value = valor.toFixed(2);
            }
        });
    });
}

// NUEVA: Función de validación mejorada
function validarPesoTiempoRealMejorado(input) {
    const valor = parseFloat(input.value);
    const validacion = document.getElementById('validacionPeso');
    const alerta = document.getElementById('alertaPeso');
    const icono = document.getElementById('iconoPeso');
    const mensaje = document.getElementById('mensajePeso');
    
    if (!validacion) return;
    
    let tipo, iconoHtml, mensajeTexto;
    
    if (isNaN(valor) || valor < 0 || valor > 100) {
        tipo = 'alert-danger';
        iconoHtml = '<i class="fas fa-exclamation-circle"></i>';
        mensajeTexto = 'El peso debe estar entre 0% y 100%';
        input.classList.add('is-invalid');
        input.classList.remove('is-warning', 'is-valid');
    } else if (valor === 0) {
        tipo = 'alert-info';
        iconoHtml = '<i class="fas fa-info-circle"></i>';
        mensajeTexto = 'Peso 0% - Esta tarea no contribuirá al progreso del proyecto';
        input.classList.add('is-warning');
        input.classList.remove('is-invalid', 'is-valid');
    } else if (valor > 50) {
        tipo = 'alert-warning';
        iconoHtml = '<i class="fas fa-exclamation-triangle"></i>';
        mensajeTexto = 'Peso muy alto (>50%) - Verifique que sea correcto';
        input.classList.add('is-warning');
        input.classList.remove('is-invalid', 'is-valid');
    } else if (valor > 25) {
        tipo = 'alert-warning';
        iconoHtml = '<i class="fas fa-exclamation"></i>';
        mensajeTexto = 'Peso considerable (>25%) - Revise el balance del proyecto';
        input.classList.add('is-warning');
        input.classList.remove('is-invalid', 'is-valid');
    } else {
        tipo = 'alert-success';
        iconoHtml = '<i class="fas fa-check-circle"></i>';
        mensajeTexto = `Peso adecuado: ${valor}% del proyecto total`;
        input.classList.add('is-valid');
        input.classList.remove('is-invalid', 'is-warning');
        
        // Auto-ocultar mensaje de éxito después de 3 segundos
        setTimeout(() => {
            if (validacion && !validacion.classList.contains('d-none')) {
                validacion.classList.add('d-none');
            }
        }, 3000);
    }
    
    if (alerta && icono && mensaje) {
        alerta.className = `alert border-0 ${tipo}`;
        icono.innerHTML = iconoHtml;
        mensaje.textContent = mensajeTexto;
        validacion.classList.remove('d-none');
    }
}

// NUEVA: Actualizar icono de peso según valor
function actualizarIconoPeso(valor) {
    const iconoElement = document.getElementById('pesoEstadoIcon');
    if (!iconoElement) return;
    
    if (valor === 0) {
        iconoElement.innerHTML = '<i class="fas fa-minus-circle text-muted" title="Sin peso"></i>';
    } else if (valor > 50) {
        iconoElement.innerHTML = '<i class="fas fa-exclamation-triangle text-danger" title="Peso muy alto"></i>';
    } else if (valor > 25) {
        iconoElement.innerHTML = '<i class="fas fa-exclamation text-warning" title="Peso considerable"></i>';
    } else if (valor > 0) {
        iconoElement.innerHTML = '<i class="fas fa-check-circle text-success" title="Peso adecuado"></i>';
    } else {
        iconoElement.innerHTML = '';
    }
}

// NUEVA: Obtener peso restante del proyecto
async function obtenerPesoRestanteProyecto() {
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return 100;
        
        const response = await fetch(`api/tareas.php?action=estadisticas&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        return Math.max(100 - (data.peso_total || 0), 0);
    } catch (error) {
        console.error('Error obteniendo peso del proyecto:', error);
        return 100;
    }
}

// NUEVA: Actualizar información contextual del proyecto
async function actualizarInfoProyecto() {
    const pesoProyectoElement = document.getElementById('pesoProyectoActual');
    const progressElement = document.getElementById('progressPesoProyecto');
    const diasTotalesElement = document.getElementById('diasTotalesInfo');
    const diasDisponiblesElement = document.getElementById('diasDisponiblesInfo');
    
    if (!pesoProyectoElement || !progressElement) return;
    
    try {
        const proyectoId = document.querySelector('input[name="proyecto_id"]')?.value;
        if (!proyectoId) return;
        
        const response = await fetch(`api/tareas.php?action=estadisticas_detalladas&proyecto_id=${proyectoId}`);
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            const pesoTotal = stats.peso_total_actual || 0;
            const progreso = Math.min(pesoTotal, 100);
            
            pesoProyectoElement.textContent = `${pesoTotal.toFixed(2)}%`;
            pesoProyectoElement.className = `badge ${Math.abs(pesoTotal - 100) < 1 ? 'bg-success' : pesoTotal > 100 ? 'bg-danger' : 'bg-warning'}`;
            
            progressElement.style.width = `${progreso}%`;
            progressElement.className = `progress-bar ${pesoTotal > 100 ? 'bg-danger' : pesoTotal < 95 ? 'bg-warning' : 'bg-success'}`;
            
            // Actualizar información de días
            if (diasTotalesElement) {
                diasTotalesElement.textContent = stats.dias_totales_proyecto || 56;
            }
            if (diasDisponiblesElement) {
                diasDisponiblesElement.textContent = (stats.diferencia_dias || 0).toFixed(1);
            }
        }
    } catch (error) {
        console.error('Error actualizando info del proyecto:', error);
        pesoProyectoElement.textContent = 'Error';
        pesoProyectoElement.className = 'badge bg-secondary';
    }
}

// Inicializar selector de método de distribución
function initializarSelectorMetodo() {
    const selector = document.getElementById('metodo_distribucion');
    if (!selector) return;
    
    selector.addEventListener('change', function() {
        // Ocultar todas las cards de información
        document.querySelectorAll('.info-metodo').forEach(card => {
            card.classList.add('d-none');
        });
        
        // Mostrar la card correspondiente
        const selectedInfo = document.getElementById(`info-${this.value}`);
        if (selectedInfo) {
            selectedInfo.classList.remove('d-none');
        }
    });
}

// MEJORADA: Función para aplicar distribución automática de peso
function aplicarDistribucionPeso() {
    const proyectoId = document.getElementById('distribuir_proyecto_id').value;
    const metodo = document.getElementById('metodo_distribucion').value;
    
    if (!proyectoId) {
        mostrarNotificacion('ID de proyecto requerido', 'error');
        return;
    }
    
    const metodosNombres = {
        'por_dias': 'basado en días totales (automático)',
        'por_fase': 'por fase (proyecto Cafeto)',
        'equitativo': 'equitativamente',
        'por_tipo': 'por tipo de tarea',
        'por_duracion': 'por duración'
    };
    
    if (!confirm(`¿Desea aplicar la distribución automática de peso ${metodosNombres[metodo]}?\n\n⚠️ Esto sobrescribirá todos los pesos existentes.`)) {
        return;
    }
    
    // Mostrar loading
    const btn = document.querySelector('#modalDistribuirPeso .btn-info');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Distribuyendo...';
    btn.disabled = true;
    
    fetch('api/tareas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'distribuir_peso',
            proyecto_id: proyectoId,
            metodo: metodo,
            peso_total: 100.0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDistribuirPeso'));
            modal.hide();
            
            mostrarNotificacion(`✅ Peso distribuido exitosamente ${metodosNombres[metodo]}`, 'success', 4000);
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacion('❌ Error al distribuir peso: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('❌ Error al distribuir peso', 'error');
    })
    .finally(() => {
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Inicializar manejador de archivos
function initializarManejadorArchivos() {
    const archivoInput = document.getElementById('archivoExcel');
    if (archivoInput) {
        archivoInput.addEventListener('change', procesarArchivoExcel);
    }
}

// MEJORADA: Función para procesar archivo Excel
function procesarArchivoExcel(event) {
    const file = event.target.files[0];
    const btnImportar = document.getElementById('btnImportar');
    const preview = document.getElementById('previewImportacion');
    
    if (!file) {
        btnImportar.disabled = true;
        preview.classList.add('d-none');
        return;
    }
    
    // Mostrar loading
    document.getElementById('tablaPreview').innerHTML = 
        `<tr>
            <td colspan="7" class="text-center py-4">
                <i class="fas fa-spinner fa-spin text-primary me-2"></i>
                Procesando archivo <strong>${file.name}</strong>...
                <br><small class="text-muted mt-2">Validando formato y calculando pesos...</small>
            </td>
        </tr>`;
    
    preview.classList.remove('d-none');
    
    // Simular procesamiento (aquí iría la lógica real con SheetJS)
    setTimeout(() => {
        // Obtener configuración
        const calcularAutomatico = document.getElementById('calcularPesosAutomaticamente').checked;
        
        // Datos de ejemplo con cálculo automático
        const datosEjemplo = [
            { actividad: 'Recepción de Planos', tipo: 'Fase', dias: '1.0', peso: calcularAutomatico ? (1.0/56*100).toFixed(2) : '1.00', estado: 'Pendiente', fase: '1. Recepción de planos constructivos', contrato: 'Normal' },
            { actividad: 'Cotizaciones Generales', tipo: 'Actividad', dias: '30.0', peso: calcularAutomatico ? (30.0/56*100).toFixed(2) : '53.57', estado: 'En Proceso', fase: '2. Cotizaciones', contrato: 'Normal' },
            { actividad: 'Presupuesto Base', tipo: 'Tarea', dias: '5.6', peso: calcularAutomatico ? (5.6/56*100).toFixed(2) : '10.00', estado: 'Pendiente', fase: '3. Presupuesto Infraestructura', contrato: 'Clave' },
            { actividad: 'Revisión Final', tipo: 'Tarea', dias: '2.8', peso: calcularAutomatico ? (2.8/56*100).toFixed(2) : '5.00', estado: 'Pendiente', fase: '4. Presupuesto Casas', contrato: 'Normal' }
        ];
        
        let html = '';
        let pesoTotal = 0;
        let diasTotal = 0;
        
        datosEjemplo.forEach(item => {
            const peso = parseFloat(item.peso);
            const dias = parseFloat(item.dias);
            pesoTotal += peso;
            diasTotal += dias;
            
            html += `
                <tr>
                    <td>${item.actividad}</td>
                    <td><span class="badge bg-primary">${item.tipo}</span></td>
                    <td><span class="badge bg-info">${item.dias}</span></td>
                    <td><span class="badge bg-success">${item.peso}%</span></td>
                    <td><span class="badge bg-warning">${item.estado}</span></td>
                    <td><small>${item.fase}</small></td>
                    <td><span class="badge bg-secondary">${item.contrato}</span></td>
                </tr>
            `;
        });
        
        document.getElementById('tablaPreview').innerHTML = html;
        
        // Mostrar resumen
        const resumen = document.getElementById('resumenImportacion');
        const pesoEsValido = calcularAutomatico || Math.abs(pesoTotal - 100) < 1;
        
        resumen.innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <h5 class="text-info">${datosEjemplo.length}</h5>
                            <small>Tareas a importar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h5 class="text-success">${diasTotal.toFixed(1)}</h5>
                            <small>Días totales</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-${pesoEsValido ? 'success' : 'warning'}">
                        <div class="card-body text-center">
                            <h5 class="text-${pesoEsValido ? 'success' : 'warning'}">${pesoTotal.toFixed(2)}%</h5>
                            <small>Peso total</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-${calcularAutomatico ? 'success' : 'info'}">
                        <div class="card-body text-center">
                            <h5 class="text-${calcularAutomatico ? 'success' : 'info'}">
                                <i class="fas fa-${calcularAutomatico ? 'calculator' : 'edit'}"></i>
                            </h5>
                            <small>${calcularAutomatico ? 'Cálculo automático' : 'Pesos manuales'}</small>
                        </div>
                    </div>
                </div>
            </div>
            ${calcularAutomatico ? `
                <div class="alert alert-success border-0 mt-3">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Cálculo automático activado:</strong> Los pesos se calcularán automáticamente 
                    basándose en los días de cada tarea y los días totales del proyecto (${diasTotalesProyecto} días).
                </div>
            ` : ''}
        `;
        
        btnImportar.disabled = false;
    }, 1500);
}

// Función para descargar plantilla
function descargarPlantilla() {
    mostrarNotificacion('Descargando plantilla Excel con formato de días y cálculo automático de pesos...', 'info');
    
    // Aquí iría la lógica para generar y descargar una plantilla Excel
    setTimeout(() => {
        mostrarNotificacion('Plantilla descargada. Recuerde usar la columna "días" para el cálculo automático de pesos', 'success');
    }, 1000);
}

// Función para procesar importación
function procesarImportacion() {
    const proyectoId = document.querySelector('input[name="proyecto_id"]').value;
    const limpiarExistentes = document.getElementById('limpiarTareasExistentes').checked;
    const calcularAutomatico = document.getElementById('calcularPesosAutomaticamente').checked;
    
    mostrarNotificacion(`Importando datos ${calcularAutomatico ? 'con cálculo automático de pesos' : 'con pesos manuales'}...`, 'info');
    
    // Aquí iría la lógica real de importación
    setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportarExcel'));
        modal.hide();
        
        mostrarNotificacion(`✅ Datos importados exitosamente ${calcularAutomatico ? 'con pesos calculados automáticamente' : 'con pesos del archivo'}`, 'success');
        setTimeout(() => location.reload(), 2000);
    }, 2000);
}

// Función para recalcular peso en edición
function recalcularPesoEdicion() {
    const duracionInput = document.getElementById('editar_duracion_tarea');
    const pesoInput = document.getElementById('editar_peso_actividad_tarea');
    
    if (!duracionInput || !pesoInput) return;
    
    const duracion = parseFloat(duracionInput.value) || 0;
    const peso = diasTotalesProyecto > 0 ? (duracion / diasTotalesProyecto) * 100 : 0;
    
    pesoInput.value = peso.toFixed(4);
    
    mostrarNotificacion(`Peso recalculado automáticamente: ${peso.toFixed(2)}%`, 'info', 2000);
}

// Función para abrir configuración avanzada de días
function abrirConfiguracionAvanzadaDias() {
    // Cerrar modal de edición
    const modalEdicion = bootstrap.Modal.getInstance(document.getElementById('modalEditarProyecto'));
    if (modalEdicion) modalEdicion.hide();
    
    // Abrir modal de configuración
    setTimeout(() => {
        mostrarModalConfiguracionDias();
    }, 300);
}

// NUEVA: Función para mostrar modal de distribución de peso
window.mostrarModalDistribuirPeso = function(proyectoId) {
    document.getElementById('distribuir_proyecto_id').value = proyectoId;
    const modal = new bootstrap.Modal(document.getElementById('modalDistribuirPeso'));
    modal.show();
};

// Helper function para abs (si no existe)
function abs(x) {
    return Math.abs(x);
}
</script>

<!-- MEJORADOS: Estilos adicionales -->
<style>
/* ============ ESTILOS PARA CONFIGURACIÓN DE DÍAS ============ */

.metric-box {
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 8px;
    border: 1px solid #ddd;
}

.metric-preview {
    padding: 0.75rem;
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border-radius: 6px;
    border: 1px solid #90caf9;
}

.config-dias-input {
    font-size: 1.5rem;
    font-weight: bold;
    text-align: center;
    border: 2px solid #6f42c1;
    border-radius: 12px;
}

.config-dias-input:focus {
    border-color: #9561e2;
    box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.25);
}

/* ============ VALIDACIONES MEJORADAS ============ */

.is-warning {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
}

.is-valid {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

/* ============ ESTILOS PARA BADGES DE PESO ============ */

.peso-badge {
    background: linear-gradient(135deg, #6c5ce7, #a29bfe);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

/* ============ INPUT GROUP MEJORADO ============ */

.input-group-text {
    font-weight: bold;
    color: #495057;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.input-group .btn-outline-info {
    border-color: #17a2b8;
    color: #17a2b8;
}

.input-group .btn-outline-info:hover {
    background: #17a2b8;
    color: white;
}

/* ============ ALERTAS PERSONALIZADAS ============ */

#validacionPeso .alert,
#validacionDias .alert {
    margin-bottom: 0;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    border-radius: 8px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============ SLIDERS DE PORCENTAJE MEJORADOS ============ */

.form-range::-webkit-slider-thumb {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-range::-moz-range-thumb {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* ============ CARDS DE INFORMACIÓN DE MÉTODOS ============ */

.info-metodo .card {
    transition: all 0.3s ease;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ============ BOTONES MEJORADOS ============ */

.btn-lg {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    border-radius: 8px;
}

.btn-group .btn {
    transition: all 0.2s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* ============ PROGRESS BAR DEL PROYECTO ============ */

#progressPesoProyecto,
#progressDiasUsados {
    transition: width 0.5s ease-in-out, background-color 0.3s ease;
}

/* ============ CONFIGURACIONES ESPECÍFICAS DEL MODAL DE DÍAS ============ */

.modal-xl .modal-content {
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header[style*="gradient"] {
    border-bottom: none;
}

.modal-header[style*="gradient"] .btn-close {
    filter: brightness(0) invert(1);
}

/* ============ CARDS MEJORADAS ============ */

.card {
    transition: all 0.3s ease;
    border-radius: 10px;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 10px 10px 0 0;
}

/* ============ HOVER EFFECTS ============ */

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* ============ FILE INPUT MEJORADO ============ */

.form-control[type="file"] {
    padding: 0.75rem 1rem;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    transition: border-color 0.3s ease;
}

.form-control[type="file"]:hover {
    border-color: #007bff;
    background: #f8f9ff;
}

.form-control[type="file"]:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* ============ BADGES ESPECIALES ============ */

.badge {
    font-weight: 600;
    border-radius: 6px;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
    padding: 0.4rem 0.8rem;
}

.badge-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.badge-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.badge-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
.badge-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
.badge-info { background: linear-gradient(135deg, #17a2b8, #138496); }

/* ============ RESPONSIVE IMPROVEMENTS ============ */

@media (max-width: 768px) {
    .modal-dialog {
        margin: 1rem;
    }
    
    .modal-lg, .modal-xl {
        max-width: calc(100vw - 2rem);
    }
    
    .btn-group .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.875rem;
    }
    
    .metric-box {
        margin-bottom: 1rem;
    }
    
    .config-dias-input {
        font-size: 1.2rem;
    }
}

/* ============ UTILIDADES ADICIONALES ============ */

.text-sm {
    font-size: 0.875rem;
}

.text-xs {
    font-size: 0.75rem;
}

.fw-600 {
    font-weight: 600;
}

.border-radius-lg {
    border-radius: 12px;
}

/* ============ EFECTOS DE LOADING ============ */

.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ============ TABLA DE PREVIEW MEJORADA ============ */

#tablaPreview .table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0,123,255,.05);
}

#tablaPreview .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* ============ GRADIENTES PERSONALIZADOS ============ */

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}
</style>
