# 🛠️ SJB Overrides para PrestaShop

![Version](https://img.shields.io/badge/version-2.3.0-blue.svg)
![PrestaShop](https://img.shields.io/badge/prestashop-1.7.8-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)
![Licencia](https://img.shields.io/badge/licencia-Comercial-red.svg)

## 📋 Descripción General

SJB Overrides es un módulo avanzado para PrestaShop que extiende y mejora la funcionalidad core mediante overrides estratégicos y hooks personalizados. Diseñado para ofrecer máxima flexibilidad y control sobre aspectos críticos de la tienda.

### 🎯 Características Principales

#### 🖼️ Sistema Avanzado de Imágenes
- Override de `ImageManager.php`
  - Recorte inteligente de imágenes
  - Sistema especial para miniaturas con sufijo `_sjb`
  - Preservación automática de transparencia en PNG/WebP
  - Ajuste proporcional configurable

#### 🔄 Gestión de Estados y Fechas
- Override de `OrderHistory.php`
  - Asignación automática de fecha de facturación
  - Personalización del estado que activa la facturación
  - Doble verificación de la fecha de factura (pre y post hook)
  - Mantiene toda la funcionalidad original de gestión de estados
  - Integración con el sistema de hooks de PrestaShop

##### Funcionalidad Principal
1. Monitoriza cambios de estado de pedidos
2. Cuando detecta cambio al estado configurado como "Facturado":
   - Asigna automáticamente la fecha actual como fecha de facturación
   - Actualiza la información del pedido
   - Asegura la persistencia mediante doble actualización

##### Características Técnicas
- Respeta el flujo de trabajo de PrestaShop
- Mantiene compatibilidad con otros módulos
- Gestión segura de timestamps
- No interfiere con otros estados del pedido
- Preserva todas las validaciones de seguridad

#### 🏭 Gestión de Fabricantes / Marcas
- Override de `Manufacturer.php`
  - Ocultación selectiva de fabricantes mediante texto específico en **meta-título**
  - Preservación de datos en backend mientras se ocultan en frontend
  - Filtrado automático en consultas SQL
  - Mantenimiento de todas las funcionalidades core

##### Configuración de Ocultación
1. Activar el módulo sjboverrides
2. En el panel de administración, configurar el "Texto a incluir en metatitulo"
3. Añadir ese mismo texto en el meta-título de los fabricantes que se deseen ocultar

##### Características
- No requiere eliminación de fabricantes
- Mantiene integridad referencial
- Compatible con múltiples idiomas
- Preserva funcionalidad de búsqueda y filtrado
- No afecta al rendimiento de la tienda

#### 🔗 URLs Optimizadas
- Sistema de reescritura avanzado
- Eliminación de IDs en URLs
- Mejora para SEO
- Patrones personalizables

### 🛠️ Herramientas de Desarrollo

#### 🐛 Sistema de Debug
- Activación/desactivación desde panel
- Niveles configurables de logging
- Trazabilidad completa de operaciones
- Registro detallado de eventos

#### ⚙️ Mantenimiento
- Reinstalación de overrides
- Limpieza de caché
- Gestión de hooks
- Preservación de datos en BD

## 📦 Instalación y Configuración

### Instalación
1. Descargar el archivo .zip del módulo
2. Acceder a Módulos > Gestor de módulos
3. Clic en "Subir un módulo"
4. Seleccionar el archivo descargado
5. Confirmar la instalación

### Panel de Administración
- **Acceso**: Personalizar > SJB Overrides
- **Ubicación**: Menú lateral izquierdo, sección Personalizar

### Configuración por Secciones

#### 🖼️ Gestión de Imágenes
1. **Configuración de Recortes**
   - Activar/desactivar recorte inteligente
   - Definir sufijo personalizado (_sjb por defecto)
   - Configurar dimensiones máximas

2. **Ajustes de Calidad**
   - Nivel de compresión JPEG/PNG
   - Preservación de transparencia
   - Conversión automática a WebP

3. **Formatos Soportados**
   - JPEG/JPG
   - PNG con transparencia
   - WebP automático

#### 📅 Estados y Fechas
1. **Estados de Pedido**
   - Seleccionar estado para facturación automática
   - Configurar comportamiento por estado
   - Activar/desactivar hooks específicos

2. **Gestión de Fechas**
   - Formato de fecha y hora
   - Zona horaria
   - Reglas de actualización

#### 🏭 Gestión de Fabricantes
1. **Configuración de Visibilidad**
   - Activar/desactivar sistema de ocultación
   - Definir texto para meta-título
   - Opciones por idioma

2. **Herramientas de Gestión**
   - Vista previa de fabricantes ocultos
   - Exportar/Importar configuración
   - Limpieza de caché

#### �️ Herramientas Avanzadas
1. **Debug Mode**
   - Niveles de logging disponibles:
     - INFO: Información general
     - WARNING: Advertencias
     - ERROR: Errores críticos
   - Activación por sección
   - Rotación de logs

2. **Mantenimiento**
   - Reinstalación de overrides
   - Limpieza de caché
   - Backup de configuración
   - Restauración de datos

## 🎣 Hooks y Widgets

### Hooks Implementados
```php
[
    'actionAdminControllerSetMedia'    => 'Recursos de administración',
    'actionOrderStatusPostUpdate'      => 'Actualización de estados',
    'displayProductBuilder'            => 'Construcción de productos',
    'actionObjectManufacturerUpdate'   => 'Actualización de fabricantes'
]
```

### Widgets Disponibles
1. **displayProductBuilder**
   ```smarty
   {widget name='sjboverrides' hook='displayProductBuilder'}
   ```
   - Parámetros disponibles:
     - `type`: Tipo de visualización
     - `layout`: Diseño específico
     - `cache`: Tiempo de caché

## 🔧 Desarrollo y Personalización

### Requisitos del Sistema
- PrestaShop 1.7.8+
- PHP 7.2+ (recomendado 7.4)
- MySQL 5.7+ / MariaDB 10.2+
- Memoria mínima: 256MB
- Permisos de escritura en:
  - `/modules/sjboverrides/`
  - `/var/cache/`
  - `/img/`

### Debug y Desarrollo
```php
// Activar debug en desarrollo
static::debug('mensaje', 'INFO');
static::debug('warning', 'WARNING');
static::debug('error', 'ERROR');

// Ubicación de logs
/modules/sjboverrides/logs/debug.log
```

### Desinstalación
1. **Preparación**
   - Backup de configuración
   - Exportar datos personalizados

2. **Proceso**
   - Desactivar módulo
   - Eliminar archivos
   - Limpiar base de datos (opcional)

3. **Restauración**
   - Conservar backups
   - Mantener logs
   - Preservar configuraciones

## 📄 Información Legal y Soporte

### Licencia
- © 2012-2025 Daniel Prol "Cancrexo"
- Licencia comercial
- Uso exclusivo por licenciatario
- No redistribuible

### Soporte Técnico
- 👨‍💻 **Desarrollador Principal:** Daniel Prol "Cancrexo"
- 📧 **Soporte:** dani@sjbdixital.es
- 🌐 **Web:** [sjbdixital.es](https://sjbdixital.es)
- ⏰ **Horario:** L-V 9:00-18:00 (CET)

### Actualizaciones
- Notificaciones automáticas
- Canal de changelog
- Notas de versión detalladas
- Guías de actualización

---

