/**
 * Gestor de Reportes y Gráficos
 * Sistema de Gestión de Proyectos con Peso Ponderado
 */

class ReportesManager {
    constructor() {
        this.charts = {};
        this.currentProjectId = null;
        this.initialized = false;
        
        // Configuración por defecto de Chart.js
        this.defaultChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };
        
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupChartDefaults();
            this.detectCurrentProject();
            this.initializeReports();
        });
    }

    setupChartDefaults() {
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
            Chart.defaults.color = '#666';
            Chart.defaults.borderColor = 'rgba(0,0,0,0.1)';
            this.initialized = true;
        } else {
            console.warn('Chart.js no está disponible');
        }
    }

    detectCurrentProject() {
        // Detectar proyecto actual desde variables globales o URL
        if (window.chartData && window.chartData.proyecto_id) {
            this.currentProjectId = window.chartData.proyecto_id;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            this.currentProjectId = urlParams.get('proyecto') || 1;
        }
    }

    initializeReports() {
        if (!this.initialized) {
            console.warn('ReportesManager no pudo inicializarse - Chart.js no disponible');
            return;
        }

        const currentView = this.getCurrentView();
        
        switch (currentView) {
            case 'dashboard':
                this.initializeDashboard();
                break;
            case 'reportes':
                this.initializeReportesView();
                break;
        }
    }

    getCurrentView() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('view') || 'dashboard';
    }

    // === DASHBOARD ===
    initializeDashboard() {
        this.createDashboardChart();
    }

    createDashboardChart() {
        const ctx = document.getElementById('graficoProgreso');
        if (!ctx || !window.chartData) return;

        const stats = window.chartData.stats;
        
        if (this.charts.dashboard) {
            this.charts.dashboard.destroy();
        }

        this.charts.dashboard = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completadas', 'En Proceso', 'Pendientes'],
                datasets: [{
                    data: [
                        parseInt(stats.completadas) || 0,
                        parseInt(stats.en_proceso) || 0,
                        parseInt(stats.pendientes) || 0
                    ],
                    backgroundColor: [
                        '#27ae60',
                        '#f39c12',
                        '#e74c3c'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 4
                }]
            },
            options: {
                ...this.defaultChartOptions,
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Distribución de Tareas',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }

    // === REPORTES ===
    initializeReportesView() {
        setTimeout(() => {
            this.createTypeChart();
            this.createStatusChart();
            this.createPhaseChart();
            this.loadAdvancedReports();
        }, 100);
    }

    createTypeChart() {
        const ctx = document.getElementById('graficoTipos');
        if (!ctx || !window.chartData) return;

        const tipos = window.chartData.estadisticas_por_tipo || [];
        
        if (this.charts.tipos) {
            this.charts.tipos.destroy();
        }

        this.charts.tipos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: tipos.map(t => t.tipo),
                datasets: [{
                    label: 'Progreso Ponderado (%)',
                    data: tipos.map(t => parseFloat(t.avance_promedio) || 0),
                    backgroundColor: [
                        '#2c3e50',
                        '#3498db', 
                        '#f39c12'
                    ],
                    borderColor: [
                        '#34495e',
                        '#2980b9',
                        '#e67e22'
                    ],
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                ...this.defaultChartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Progreso por Tipo de Tarea',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutCubic'
                }
            }
        });
    }

    createStatusChart() {
        const ctx = document.getElementById('graficoEstados');
        if (!ctx || !window.chartData) return;

        const stats = window.chartData.stats;
        
        if (this.charts.estados) {
            this.charts.estados.destroy();
        }

        this.charts.estados = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Listo', 'En Proceso', 'Pendiente'],
                datasets: [{
                    data: [
                        parseInt(stats.completadas) || 0,
                        parseInt(stats.en_proceso) || 0,
                        parseInt(stats.pendientes) || 0
                    ],
                    backgroundColor: [
                        '#27ae60',
                        '#f39c12', 
                        '#e74c3c'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                ...this.defaultChartOptions,
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Estados de las Tareas',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }

    createPhaseChart() {
        const ctx = document.getElementById('graficoFases');
        if (!ctx || !window.chartData) return;

        const fases = window.chartData.estadisticas_por_fase || [];
        if (fases.length === 0) return;

        if (this.charts.fases) {
            this.charts.fases.destroy();
        }

        this.charts.fases = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: fases.map(f => f.fase_principal.length > 30 ? 
                    f.fase_principal.substring(0, 30) + '...' : f.fase_principal),
                datasets: [{
                    label: 'Progreso (%)',
                    data: fases.map(f => parseFloat(f.avance_promedio) || 0),
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                ...this.defaultChartOptions,
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Progreso por Fase Principal',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const fullLabel = fases[context[0].dataIndex].fase_principal;
                                return fullLabel;
                            },
                            label: function(context) {
                                return 'Progreso: ' + context.parsed.x + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeOutCubic'
                }
            }
        });
    }

    // === REPORTES AVANZADOS ===
    loadAdvancedReports() {
        if (!this.currentProjectId) return;

        // Cargar datos adicionales para reportes avanzados
        this.loadDistribucionPeso();
        this.loadAnalisisTendencias();
    }

    loadDistribucionPeso() {
        fetch(`api/reportes.php?action=distribucion_peso&proyecto_id=${this.currentProjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.createDistribucionPesoChart(data.data);
                }
            })
            .catch(error => {
                console.error('Error al cargar distribución de peso:', error);
            });
    }

    createDistribucionPesoChart(data) {
        const container = document.getElementById('graficoDistribucionPeso');
        if (!container) return;

        // Crear canvas si no existe
        let canvas = container.querySelector('canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            container.appendChild(canvas);
        }

        if (this.charts.distribucionPeso) {
            this.charts.distribucionPeso.destroy();
        }

        this.charts.distribucionPeso = new Chart(canvas, {
            type: 'doughnut',
            data: data.distribucion_grafico,
            options: {
                ...this.defaultChartOptions,
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Distribución de Peso por Rangos',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    }

    loadAnalisisTendencias() {
        fetch(`api/reportes.php?action=progreso_tiempo&proyecto_id=${this.currentProjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.labels.length > 0) {
                    this.createTendenciasChart(data.data);
                }
            })
            .catch(error => {
                console.error('Error al cargar análisis de tendencias:', error);
            });
    }

    createTendenciasChart(data) {
        const container = document.getElementById('graficoTendencias');
        if (!container) return;

        let canvas = container.querySelector('canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            container.appendChild(canvas);
        }

        if (this.charts.tendencias) {
            this.charts.tendencias.destroy();
        }

        this.charts.tendencias = new Chart(canvas, {
            type: 'line',
            data: data,
            options: {
                ...this.defaultChartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' tareas';
                            }
                        }
                    }
                },
                plugins: {
                    ...this.defaultChartOptions.plugins,
                    title: {
                        display: true,
                        text: 'Actividad de Tareas (Últimos 30 días)',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    }

    // === UTILIDADES ===
    refreshCharts() {
        // Recargar datos y actualizar gráficos
        this.loadChartData(() => {
            const currentView = this.getCurrentView();
            if (currentView === 'dashboard') {
                this.createDashboardChart();
            } else if (currentView === 'reportes') {
                this.createTypeChart();
                this.createStatusChart();
                this.createPhaseChart();
                this.loadAdvancedReports();
            }
        });
    }

    loadChartData(callback) {
        if (!this.currentProjectId) return;

        fetch(`api/reportes.php?action=datos_graficos&proyecto_id=${this.currentProjectId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar datos globales
                    window.chartData = {
                        ...window.chartData,
                        ...data.data
                    };
                    if (callback) callback();
                }
            })
            .catch(error => {
                console.error('Error al cargar datos de gráficos:', error);
            });
    }

    destroyAllCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }

    // === EXPORTACIÓN ===
    exportChart(chartName, format = 'png') {
        const chart = this.charts[chartName];
        if (!chart) {
            console.error('Gráfico no encontrado:', chartName);
            return;
        }

        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = `grafico_${chartName}_${new Date().toISOString().split('T')[0]}.${format}`;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // === CONFIGURACIÓN DINÁMICA ===
    updateChartColors(colorScheme) {
        const schemes = {
            default: ['#27ae60', '#f39c12', '#e74c3c', '#3498db', '#9b59b6'],
            pastel: ['#a8e6cf', '#ffd3a5', '#ffaaa5', '#b4dff5', '#d4a5ff'],
            corporate: ['#2c3e50', '#34495e', '#7f8c8d', '#95a5a6', '#bdc3c7']
        };

        const colors = schemes[colorScheme] || schemes.default;
        
        Object.values(this.charts).forEach(chart => {
            if (chart.data && chart.data.datasets) {
                chart.data.datasets.forEach((dataset, index) => {
                    if (Array.isArray(dataset.backgroundColor)) {
                        dataset.backgroundColor = colors;
                    } else {
                        dataset.backgroundColor = colors[index % colors.length];
                    }
                });
                chart.update();
            }
        });
    }
}

// === FUNCIONES GLOBALES ===

// Función para actualizar gráficos desde el exterior
window.actualizarGraficos = function(proyectoId) {
    if (window.reportesManager) {
        window.reportesManager.currentProjectId = proyectoId;
        window.reportesManager.refreshCharts();
    }
};

// Función para exportar gráfico específico
window.exportarGrafico = function(nombreGrafico, formato = 'png') {
    if (window.reportesManager) {
        window.reportesManager.exportChart(nombreGrafico, formato);
    }
};

// Función para cambiar esquema de colores
window.cambiarEsquemaColores = function(esquema) {
    if (window.reportesManager) {
        window.reportesManager.updateChartColors(esquema);
    }
};

// === INICIALIZACIÓN ===

// Crear instancia global del gestor de reportes
window.reportesManager = new ReportesManager();

// Limpiar gráficos al cambiar de página
window.addEventListener('beforeunload', () => {
    if (window.reportesManager) {
        window.reportesManager.destroyAllCharts();
    }
});

// Redimensionar gráficos al cambiar el tamaño de la ventana
window.addEventListener('resize', () => {
    if (window.reportesManager) {
        Object.values(window.reportesManager.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
});

// Manejo de errores globales para Chart.js
window.addEventListener('error', (e) => {
    if (e.message.includes('Chart') || e.message.includes('canvas')) {
        console.error('Error en gráficos:', e.message);
        // Intentar reinicializar gráficos después de un error
        setTimeout(() => {
            if (window.reportesManager) {
                window.reportesManager.refreshCharts();
            }
        }, 1000);
    }
});

console.log('📊 Gestor de Reportes inicializado correctamente');
