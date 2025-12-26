# Certificados Digitales PRO

Sistema completo de generación de certificados digitales en PDF con integración a Google Sheets, múltiples plantillas y sistema de validación con QR.

## Requisitos del Sistema

### Requisitos Mínimos

- **WordPress**: 5.8 o superior
- **PHP**: 7.4.0 o superior (compatible con PHP 7.4.33)
- **MySQL**: 5.6 o superior (o MariaDB 10.1+)

### Requisitos Recomendados

- **WordPress**: 6.0 o superior
- **PHP**: 8.0 o superior
- **MySQL**: 5.7 o superior (o MariaDB 10.3+)
- **Memoria PHP**: 256 MB o superior (recomendado para generación de PDFs)
- **Tamaño máximo de archivo**: 20 MB o superior (para subir plantillas)

### Nota Importante sobre PHP 7.4

Este plugin está completamente optimizado para **PHP 7.4**, incluyendo todas sus dependencias (TCPDF, Endroid QR Code, Symfony components). Si tu servidor usa PHP 7.4.33, el plugin funcionará perfectamente sin problemas de compatibilidad.

### Extensiones PHP Requeridas

El plugin requiere las siguientes extensiones de PHP (generalmente incluidas en instalaciones estándar):

- `gd` o `imagick` - Para procesamiento de imágenes
- `mbstring` - Para manejo de cadenas multibyte
- `json` - Para procesamiento de datos JSON
- `curl` - Para integración con Google Sheets API
- `zip` - Para gestión de archivos comprimidos (opcional)

### Compatibilidad de Versiones PHP

✅ **PHP 7.4** - Compatible (mínimo requerido)
✅ **PHP 8.0** - Compatible y recomendado
✅ **PHP 8.1** - Compatible
✅ **PHP 8.2** - Compatible
✅ **PHP 8.3** - Compatible

⚠️ **PHP 7.3 o anterior** - No compatible

## Características Principales

- 📄 **Generación de PDFs**: Certificados de alta calidad en formato PDF con TCPDF
- 🎨 **Editor Visual**: Diseña tus certificados con un editor drag & drop intuitivo
- 📊 **Google Sheets**: Importa datos de participantes desde Google Sheets en tiempo real
- 🔍 **Validación QR**: Sistema de validación con códigos QR únicos por certificado
- 🎨 **Personalización Completa**:
  - Colores personalizables (primario, hover, éxito, error)
  - Fuentes personalizadas (.ttf, .otf)
  - Estilos de fuente (normal, negrita, cursiva, negrita cursiva)
  - Tamaños y colores configurables por campo
- 📱 **Responsive**: Interfaz adaptable a todos los dispositivos
- 🔐 **Seguro**: Certificados con código único de validación
- 📑 **Múltiples Plantillas**: Soporte para diferentes tipos de certificados y eventos
- 🔧 **Modo Calibración**: Grilla visual para posicionamiento preciso de campos
- 💾 **Auto-guardado**: Sistema inteligente de guardado de configuraciones

## Instalación

1. Sube el directorio `certificate-pro` a `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Ve a 'Certificados → Configuración' para configurar el plugin
4. Configura tu API Key de Google Sheets (si deseas usar esta función)
5. Crea tu primera plantilla de certificado

## Configuración Inicial

### 1. API de Google Sheets (Opcional)

Para usar la integración con Google Sheets:

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la API de Google Sheets
4. Crea credenciales (API Key)
5. Copia la API Key en 'Certificados → Configuración'

### 2. Personalización de Colores

1. Ve a 'Certificados → Configuración'
2. Desplázate a la sección 'Personalización de Colores'
3. Selecciona tus colores preferidos:
   - **Color Primario**: Se aplica a botones del dashboard, configurador, enlaces y elementos activos
   - **Color Hover**: Color que aparece al pasar el mouse sobre elementos interactivos
   - **Color Éxito**: Para mensajes de éxito y confirmación
   - **Color Error**: Para mensajes de error y advertencia
4. Haz clic en 'Guardar Cambios'

**Nota:** Los botones del dashboard y configurador utilizan el color primario de tu WordPress (configurable en Personalización → Colores). Si personalizas el color primario del plugin, este sobrescribirá el color de WordPress para los elementos del plugin.

### 3. Gestión de Fuentes Personalizadas

El plugin permite subir y usar fuentes personalizadas en tus certificados:

1. Ve a 'Certificados → Fuentes'
2. Haz clic en 'Agregar Nueva Fuente'
3. Sube tu archivo de fuente (.ttf)
4. Asigna un nombre descriptivo a la fuente
5. La fuente estará disponible en el configurador de campos

**Formatos soportados:**
- TrueType (.ttf)
- OpenType (.otf)

**Recomendaciones:**
- Usa fuentes con licencia comercial si es necesario
- Las fuentes se almacenan en `/wp-content/uploads/certificados-fuentes/`
- Puedes subir variantes (Regular, Bold, Italic) con nombres diferentes

### 4. Crear Plantilla

1. Ve a 'Certificados → Plantillas'
2. Haz clic en 'Agregar Nueva Plantilla'
3. Sube tu imagen de fondo
4. Arrastra y coloca los campos (nombre, fecha, etc.)
5. Ajusta estilos (fuente, tamaño, color)
6. Guarda la plantilla

## Shortcodes

### Mostrar Formulario de Búsqueda

```
[certificados_buscar]
```

Muestra un formulario para que los usuarios busquen y descarguen sus certificados.

**Parámetros opcionales:**
- `pestana` - ID de la pestaña/evento específico (por defecto muestra todas)

**Ejemplo:**
```
[certificados_buscar pestana="1"]
```

## Preguntas Frecuentes

### ¿Necesito PHP 8.0 obligatoriamente?

No, el plugin funciona con PHP 7.4 en adelante. Sin embargo, recomendamos PHP 8.0+ para mejor rendimiento y seguridad.

### ¿Cuánta memoria PHP necesito?

Recomendamos al menos 256 MB de memoria PHP. Para generar PDFs con imágenes grandes o muchos certificados simultáneos, puede necesitar más.

### ¿Es compatible con mi tema de WordPress?

Sí, el plugin está diseñado para funcionar con cualquier tema de WordPress. Usa CSS con alta especificidad para evitar conflictos.

### ¿Puedo personalizar los colores del plugin?

Sí, desde 'Certificados → Configuración' en la sección 'Personalización de Colores' puedes personalizar:
- Color primario (botones, enlaces, elementos activos)
- Color hover (efecto al pasar el mouse)
- Color de éxito (mensajes de confirmación)
- Color de error (mensajes de advertencia)

Los colores se aplican automáticamente a todo el plugin, incluyendo el dashboard y el configurador de campos.

### ¿Puedo usar mis propias fuentes en los certificados?

Sí, el plugin soporta fuentes personalizadas. Ve a 'Certificados → Fuentes' y sube tus archivos .ttf o .otf. Las fuentes estarán disponibles inmediatamente en el configurador de campos para todos tus certificados.

### ¿Qué formatos de fuente están soportados?

El plugin soporta:
- TrueType (.ttf) - Recomendado
- OpenType (.otf) - Compatible

Puedes subir múltiples variantes de la misma fuente (Regular, Bold, Italic, etc.) con nombres diferentes.

## Soporte

Para reportar problemas o solicitar nuevas características, contacta con el desarrollador.

## Changelog

### 1.5.13 (2025-12-09)
- **CORRECCIÓN**: Verificación de índices antes de crearlos en migraciones
- **MEJORA**: Función helper `index_exists()` para validar índices en BD
- Previene errores de índices duplicados durante migraciones
- Sistema de migraciones más robusto y tolerante a fallos

### 1.5.12 (2025-12-09)
- **CORRECCIÓN CRÍTICA**: Tabla `certificados_sheets_cache_meta` ahora se crea automáticamente
- **MEJORA**: Migración automática de columnas faltantes en tabla de caché
- Creación de columnas `etag`, `needs_refresh` y `cached_data` si no existen
- Sistema de caché completamente funcional en instalaciones nuevas y actualizaciones
- Soluciona error "Unknown column 'needs_refresh' in 'field list'"

### 1.5.11 (2025-12-09)
- **CORRECCIÓN CRÍTICA**: Sistema de mapeo de columnas ahora funciona correctamente
- **MEJORA**: Búsqueda de certificados usa mapeo personalizado de columnas
- La búsqueda intenta primero con mapeo personalizado, luego con nombres estándar
- Compatibilidad total con Google Sheets que usan nombres de columnas personalizados
- Fallback automático a búsqueda tradicional si no hay mapeo configurado

### 1.5.10 (2025-12-09)
- **CORRECCIÓN CRÍTICA**: Sistema de estadísticas ahora funciona correctamente después de actualizar
- **MEJORA**: Migración automática de tabla `certificados_descargas` si no existe
- Creación automática de tabla de estadísticas al actualizar desde versiones antiguas
- Migración de datos históricos de descargas_log a descargas
- Sistema de estadísticas completamente funcional después de actualizaciones

### 1.5.9 (2025-12-09)
- **MEJORA**: Sistema de migraciones de base de datos robusto y seguro
- **CORRECCIÓN**: Iconos perfectamente centrados en tarjetas de estadísticas
- Sistema de versiones de BD para actualizar sin perder datos
- Migración automática de columnas faltantes al actualizar desde versiones antiguas
- Preservación completa de datos existentes durante actualizaciones
- Función helper `column_exists()` para verificaciones de esquema

### 1.5.8 (2025-12-09)
- **MEJORA**: Tabla de contenidos de documentación con scroll automático
- **MEJORA**: Scrollbar personalizado y elegante en el sidebar de documentación
- Mejor UX en navegación de documentación con contenido extenso
- Sidebar responsivo con altura máxima basada en viewport

### 1.5.7 (2025-12-09)
- **MEJORA**: Documentación completa de personalización de colores
- **MEJORA**: Documentación de gestión de fuentes personalizadas
- **MEJORA**: Descripción actualizada del Color Primario en configuración
- Información detallada sobre formatos de fuente soportados (.ttf, .otf)
- Guía de uso del sistema de colores personalizables
- Aclaración sobre integración con colores de WordPress

### 1.5.6 (2025-12-09)
- **MEJORA**: Botones del dashboard ahora usan el color primario de WordPress
- **MEJORA**: Sistema de inversión de colores en hover para todos los botones
- Eliminado efecto zoom en botones por diseño más profesional
- Mejor integración visual con el panel de administración de WordPress

### 1.5.5 (2025-12-09)
- **MEJORA**: Efecto hover mejorado con inversión de colores en botones
- Eliminado zoom (transform) en favor de transiciones más suaves
- Mejor experiencia visual en todo el dashboard y configurador

### 1.5.0 (2025-12-09)
- **CORRECCIÓN**: Todos los modales ahora funcionan correctamente
- **CORRECCIÓN**: Método AJAX `ajax_obtener_evento()` implementado
- **MEJORA**: Botón "Guardar Cambios" visible en configurador de campos
- Sistema de apertura forzada de modales con `!important`
- Mejor manejo de conflictos CSS con otros plugins

### 1.2.0 (2025-11-29)
- **NUEVA CARACTERÍSTICA**: Estilo de fuente configurable por campo
- Opción para seleccionar entre: Normal, Negrita, Cursiva, Negrita Cursiva
- Por defecto todos los campos usan estilo normal (sin negrita)
- Actualización automática de base de datos para instalaciones existentes
- Interfaz mejorada en el configurador con selector de estilo

### 1.1.1 (2025-11-29)
- Formateo automático de nombres a formato título (Primera Letra Mayúscula)
- Soporte completo para caracteres con tildes y letra ñ en nombres
- Mejora en presentación de nombres en certificados

### 1.1.0 (2025-11-29)
- **IMPORTANTE**: Ajuste completo de dependencias para PHP 7.4.33
- Downgrade de `endroid/qr-code` de v5.x a v3.9.7 (compatible con PHP 7.4)
- Actualización de TCPDF a v6.10.1
- Corrección de autoloader para soportar prefijos múltiples
- Mejora en manejo de errores con logs detallados
- Captura de errores fatales de PHP 7+

### 1.0.9 (2025-11-29)
- Limpieza de logs de depuración para producción
- Optimización de rendimiento
- Confirmación de compatibilidad con PHP 7.4-8.3

### 1.0.8 (2025-11-29)
- Corrección: CSS personalizado ahora se inyecta en frontend y backend
- Mejora: Loader con fondo blanco y spinner personalizable

### 1.0.7 (2025-11-29)
- Mejora: Sistema de colores personalizables con mayor especificidad CSS
- Corrección: Problemas de carga de CSS en algunos temas

### 1.0.0
- Versión inicial del plugin

## Licencia

Este plugin está licenciado bajo GPL-2.0+

## Créditos

Desarrollado con:
- [TCPDF](https://tcpdf.org/) - Generación de PDFs
- [Endroid QR Code](https://github.com/endroid/qr-code) - Códigos QR
- Google Sheets API - Integración con hojas de cálculo
