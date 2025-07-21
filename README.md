# Sistema de Gestión de Proyectos con Peso Ponderado v2.0.0

Sistema avanzado de gestión de proyectos que implementa metodología de **peso ponderado** para cálculo de progreso, basado en el análisis del archivo Excel "Proceso de Planificación y avance.xlsx" del proyecto Cafeto.

## 🎯 Características Principales

### ✨ Peso Ponderado de Actividades
- **Cálculo de progreso real**: Cada tarea tiene un peso que refleja su importancia relativa
- **Fórmula matemática**: `Progreso = Σ(Peso × Estado) / Σ(Todos los pesos)`
- **Tres niveles jerárquicos**: Fase > Actividad > Tarea
- **Progreso en tiempo real**: Actualización automática basada en pesos

### 📊 Dashboard Avanzado
- Métricas ponderadas en tiempo real
- Gráficos interactivos con Chart.js
- Distribución por tipos y fases
- Cronograma ponderado por fases principales

### 🔧 Gestión Completa
- **Proyectos**: CRUD completo con duplicación y plantillas
- **Tareas**: Gestión con peso, contrato, fase principal
- **Exportación**: JSON, CSV, HTML, XML con datos ponderados
- **Importación**: Datos desde Excel (formato Cafeto incluido)

### 🎨 Interfaz Moderna
- Bootstrap 5.3.2 con diseño responsivo
- Font Awesome 6.4.0 para iconografía
- Notificaciones en tiempo real
- Atajos de teclado (Ctrl+N, Ctrl+P)

## 🛠️ Instalación

### Requisitos del Sistema

**Servidor Web:**
- Apache 2.4+ o Nginx 1.18+
- PHP 7.4+ (recomendado PHP 8.1+)
- MySQL 5.7+ o MariaDB 10.3+

**Extensiones PHP requeridas:**
```bash
php-mysql
php-pdo
php-json
php-mbstring
php-xml
```

**Navegadores soportados:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Paso 1: Preparar el Entorno

```bash
# Clonar o extraer archivos del sistema
cd /var/www/html/
mkdir sistema-proyectos
cd sistema-proyectos

# Establecer permisos
chmod 755 -R .
chmod 777 logs/ uploads/ cache/
```

### Paso 2: Configurar Base de Datos

1. **Crear la base de datos:**
```sql
CREATE DATABASE proyecto_cafeto CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'proyecto_user'@'localhost' IDENTIFIED BY 'tu_password_seguro';
GRANT ALL PRIVILEGES ON proyecto_cafeto.* TO 'proyecto_user'@'localhost';
FLUSH PRIVILEGES;
```

2. **Ejecutar el script de actualización:**
```bash
mysql -u proyecto_user -p proyecto_cafeto < update_database.sql
```

**O ejecutar SQL manualmente:**
```sql
-- Ejecutar todo el contenido del archivo update_database.sql
-- Esto creará las tablas con campos de peso ponderado y datos de ejemplo
```

### Paso 3: Configurar Conexión

Editar `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'proyecto_cafeto';
private $username = 'proyecto_user';
private $password = 'tu_password_seguro';
```

### Paso 4: Verificar Instalación

1. Abrir navegador: `http://localhost/sistema-proyectos/`
2. Verificar que aparezca el proyecto "Cafeto" con datos de ejemplo
3. Comprobar que el progreso ponderado se muestre correctamente (84.44%)

## 📋 Estructura del Proyecto

```
sistema-proyectos/
├── 📁 api/                      # APIs REST del sistema
│   ├── exportar.php            # Exportación de datos
│   ├── proyectos.php           # CRUD de proyectos
│   └── tareas.php              # CRUD de tareas
├── 📁 config/                   # Configuración
│   └── database.php            # Conexión BD y clases principales
├── 📁 css/                      # Estilos
│   └── style.css               # CSS personalizado
├── 📁 includes/                 # Archivos incluidos
│   ├── footer.php              # Footer con info del sistema
│   ├── header.php              # Header con navegación
│   ├── includes.php            # Utilidades y validaciones
│   └── modales.php             # Modales para CRUD
├── 📁 js/                       # JavaScript
│   ├── proyecto-functions.js   # Funciones principales
│   └── script.js               # Funciones auxiliares
├── 📁 logs/                     # Logs del sistema
├── 📁 uploads/                  # Archivos subidos
├── 📁 cache/                    # Cache temporal
├── index.php                   # Página principal
├── update_database.sql         # Script de actualización de BD
└── README.md                   # Esta documentación
```

## 🔍 Análisis del Excel Cafeto

El sistema está basado en el análisis completo del archivo Excel proporcionado:

### Datos Analizados:
- **128 elementos** organizados en 4 fases principales
- **Progreso actual**: 84.44% basado en peso ponderado
- **Peso total del sistema**: 2.9961
- **Avance ponderado**: 2.5300

### Fases Identificadas:
1. **Recepción de planos constructivos** (37.59% progreso)
2. **Cotizaciones** (100% completada)
3. **Presupuesto Infraestructura** (0% pendiente)
4. **Presupuesto Casas** (0% pendiente)

### Tipos de Elementos:
- **Fase**: Elementos principales (peso promedio: 0.1000)
- **Actividad**: Componentes intermedios (peso promedio: 0.0500)
- **Tarea**: Elementos específicos (peso promedio: 0.0100)

## 💻 Uso del Sistema

### Dashboard Principal
- **Vista general**: Métricas ponderadas en tiempo real
- **Gráficos**: Distribución por estado y tipo
- **Fases**: Progreso detallado por fase principal
- **Tareas recientes**: Últimas actualizaciones

### Gestión de Tareas
```php
// Campos principales de cada tarea:
- nombre: string               // Nombre de la tarea
- tipo: enum                   // Fase, Actividad, Tarea
- peso_actividad: decimal(10,8) // Peso ponderado (0.0000-1.0000)
- fase_principal: string       // Agrupación por fase
- contrato: enum               // Normal, Contrato Clave
- estado: enum                 // Pendiente, En Proceso, Listo
- porcentaje_avance: int       // 0-100%
- duracion_dias: int           // Duración estimada
```

### Cálculo de Progreso
```javascript
// Fórmula implementada:
function calcularProgresoPonderado(tareas) {
    let peso_total = 0;
    let avance_total = 0;
    
    tareas.forEach(tarea => {
        const peso = parseFloat(tarea.peso_actividad);
        peso_total += peso;
        
        if (tarea.estado === 'Listo') {
            avance_total += peso;
        } else if (tarea.estado === 'En Proceso') {
            avance_total += peso * (tarea.porcentaje_avance / 100);
        }
    });
    
    return peso_total > 0 ? (avance_total / peso_total) * 100 : 0;
}
```

## 🚀 Características Avanzadas

### Importación de Datos Excel
```php
// Endpoint para importar datos del proyecto Cafeto:
POST /api/proyectos.php
{
    "action": "importar_excel_cafeto",
    "proyecto_id": 1
}
```

### Exportación Avanzada
- **JSON**: Datos completos con metadatos ponderados
- **CSV**: Excel-compatible con BOM UTF-8
- **HTML**: Reportes visuales con gráficos
- **XML**: Intercambio de datos estructurados

### API REST Completa
```bash
# Ejemplos de endpoints disponibles:

# Obtener estadísticas ponderadas
GET /api/tareas.php?action=estadisticas&proyecto_id=1

# Cronograma ponderado por fases
GET /api/proyectos.php?action=cronograma_ponderado&proyecto_id=1

# Exportar proyecto completo
GET /api/exportar.php?action=proyecto&proyecto_id=1&formato=json

# Recalcular progreso
POST /api/proyectos.php {"action": "recalcular_progreso", "proyecto_id": 1}
```

## 🔧 Configuración Avanzada

### Personalización de Pesos
```php
// En includes/includes.php - Distribución automática:
$pesos_sugeridos = [
    'Fase' => 0.1000,      // 10% peso base
    'Actividad' => 0.0500,  // 5% peso base  
    'Tarea' => 0.0100       // 1% peso base
];
```

### Base de Datos - Funciones SQL
```sql
-- Función para cálculo automático:
SELECT calcular_progreso_ponderado(1) as progreso;

-- Vista para análisis rápido:
SELECT * FROM vista_progreso_ponderado WHERE proyecto_id = 1;

-- Triggers automáticos:
-- Se actualizan automáticamente al modificar tareas
```

### Validaciones Implementadas
- **Peso**: Debe estar entre 0.0000 y 1.0000
- **Estados**: Solo valores predefinidos
- **Tipos**: Hierarchy validation (Fase > Actividad > Tarea)
- **Integridad**: Foreign keys y constraints

## 🛡️ Seguridad

### Medidas Implementadas:
- **Prepared Statements**: Prevención SQL Injection
- **Input Sanitization**: htmlspecialchars en todas las salidas
- **CSRF Protection**: Tokens en formularios críticos
- **XSS Prevention**: Validación y escape de datos
- **Error Handling**: Logs detallados sin exposición

### Buenas Prácticas:
```php
// Validación de entrada
$peso = Validator::validarEntrada($input['peso_actividad'], 'peso');
if ($peso === false) {
    throw new InvalidArgumentException('Peso inválido');
}

// Logging de actividades
Utils::log('Tarea actualizada', 'INFO', $usuario_id, [
    'tarea_id' => $tarea_id,
    'peso_anterior' => $peso_anterior,
    'peso_nuevo' => $peso_nuevo
]);
```

## 🧪 Testing y Verificación

### Verificación del Sistema:
1. **Conectividad**: Base de datos accesible
2. **Permisos**: Directorios escribibles
3. **Funciones SQL**: Triggers y procedimientos
4. **Integridad**: Consistencia de datos
5. **Cálculos**: Verificación de fórmulas

### Datos de Prueba:
- **Proyecto Cafeto**: 24 tareas de ejemplo
- **Progreso esperado**: 84.44%
- **Peso total**: 2.9961
- **Distribución**: 65% completado, 35% pendiente

## 📊 Métricas y Monitoreo

### KPIs Principales:
- **Progreso Ponderado**: Basado en importancia real
- **Distribución por Tipo**: Fases vs Actividades vs Tareas
- **Eficiencia por Fase**: Completadas vs Pendientes
- **Peso por Contrato**: Normal vs Contrato Clave

### Dashboard Analytics:
```javascript
// Métricas disponibles en tiempo real:
- Total de proyectos activos
- Progreso promedio ponderado
- Tareas críticas (alto peso, estado pendiente)
- Fases con mayor impacto
- Distribución de recursos por contrato
```

## 🔄 Actualizaciones y Mantenimiento

### Versionado:
- **v2.0.0**: Implementación peso ponderado
- **v1.x**: Sistema básico sin ponderación

### Respaldos Automáticos:
```bash
# Crear respaldo completo:
curl "http://localhost/sistema-proyectos/api/exportar.php?action=respaldo_completo&formato=sql" > respaldo_$(date +%Y%m%d).sql

# Programar respaldos diarios:
0 2 * * * /path/to/respaldo_diario.sh
```

### Logs del Sistema:
```bash
# Ubicación de logs:
logs/sistema.log           # Log principal
logs/errores.log          # Errores críticos
logs/accesos.log          # Accesos al sistema
```

## 🤝 Contribución y Soporte

### Estructura para Desarrollo:
1. **Fork** del repositorio
2. **Branch** para nueva funcionalidad
3. **Tests** para validación
4. **Pull Request** con documentación

### Reportar Issues:
- Incluir versión del sistema
- Pasos para reproducir
- Logs relevantes
- Screenshots si aplica

### Funcionalidades Futuras:
- [ ] Integración con APIs externas
- [ ] Dashboard móvil nativo  
- [ ] Notificaciones push
- [ ] Integración con calendarios
- [ ] Reportes PDF avanzados
- [ ] Multi-idioma
- [ ] Roles y permisos
- [ ] Integración Git para proyectos de desarrollo

## 📖 Documentación Adicional

### Enlaces Útiles:
- [Bootstrap 5.3 Docs](https://getbootstrap.com/docs/5.3/)
- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [Font Awesome Icons](https://fontawesome.com/icons)
- [PHP PDO Manual](https://www.php.net/manual/en/book.pdo.php)

### Metodología Peso Ponderado:
La metodología implementada se basa en principios de **Project Management** donde cada actividad tiene un impacto diferente en el resultado final. A diferencia del cálculo tradicional de progreso (promedio simple), el peso ponderado refleja la **importancia real** de cada elemento.

**Beneficios comprobados:**
- **Mayor precisión**: Refleja el estado real del proyecto
- **Mejor toma de decisiones**: Priorización basada en impacto
- **Comunicación efectiva**: Stakeholders ven progreso realista
- **Control de calidad**: Tareas críticas no se diluyen

---

## 📄 Licencia

Este sistema está desarrollado para gestión interna de proyectos. Para uso comercial o distribución, contactar al desarrollador.

**© 2024 - Sistema de Gestión de Proyectos con Peso Ponderado v2.0.0**

---

*Documentación actualizada: <?= date('d/m/Y H:i:s') ?>*
