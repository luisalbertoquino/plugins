<div align="center">

# 🎓 Certificados Digitales PRO

### Sistema profesional de generación de certificados digitales para WordPress

[![WordPress](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0+-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.5.13-orange.svg)](CHANGELOG.md)

**Genera certificados PDF de alta calidad con integración a Google Sheets, editor visual drag & drop y validación con códigos QR únicos**

[Características](#-características-principales) • [Instalación](#-instalación) • [Configuración](#-configuración-inicial) • [Documentación](#-uso-y-shortcodes) • [Soporte](#-soporte)

</div>

## 📋 Requisitos del Sistema

<table>
<tr>
<td width="50%">

### Requisitos Mínimos
- ✅ **WordPress** 5.8+
- ✅ **PHP** 7.4.0+
- ✅ **MySQL** 5.6+ / MariaDB 10.1+
- ✅ **Memoria PHP** 128 MB

</td>
<td width="50%">

### Recomendado
- 🚀 **WordPress** 6.0+
- 🚀 **PHP** 8.0+
- 🚀 **MySQL** 5.7+ / MariaDB 10.3+
- 🚀 **Memoria PHP** 256 MB

</td>
</tr>
</table>

### Extensiones PHP Requeridas

| Extensión | Propósito |
|-----------|-----------|
| `gd` o `imagick` | Procesamiento de imágenes y QR |
| `mbstring` | Manejo de caracteres especiales |
| `json` | Procesamiento de datos |
| `curl` | Integración con Google Sheets |
| `zip` | Gestión de archivos (opcional) |

### Compatibilidad PHP

| Versión | Estado |
|---------|--------|
| PHP 7.4 | ✅ Compatible (mínimo) |
| PHP 8.0 | ✅ Compatible (recomendado) |
| PHP 8.1 | ✅ Compatible |
| PHP 8.2 | ✅ Compatible |
| PHP 8.3 | ✅ Compatible |
| PHP 7.3 o anterior | ❌ No compatible |

## ✨ Características Principales

<table>
<tr>
<td width="50%">

### 🎨 Diseño y Personalización
- **Editor Visual Drag & Drop** - Diseña certificados intuitivamente
- **Fuentes Personalizadas** - Soporte para .ttf y .otf
- **Estilos de Fuente** - Normal, negrita, cursiva y combinaciones
- **Colores Personalizables** - Sistema completo de temas
- **Modo Calibración** - Grilla visual para posicionamiento preciso
- **Múltiples Plantillas** - Para diferentes eventos

</td>
<td width="50%">

### 🚀 Funcionalidades Avanzadas
- **Generación de PDFs** - Alta calidad con TCPDF
- **Integración Google Sheets** - Importa datos en tiempo real
- **Códigos QR Únicos** - Sistema de validación automático
- **Búsqueda Frontend** - Shortcode para usuarios finales
- **Estadísticas** - Dashboard con métricas de descargas
- **Auto-guardado** - Sistema inteligente de configuración

</td>
</tr>
</table>

### 🎯 Características Destacadas

```
✅ Totalmente Responsive          ✅ Sistema de Caché Inteligente
✅ Compatible Multiidioma          ✅ Mapeo de Columnas Flexible
✅ Validación Segura               ✅ Migraciones Automáticas
✅ Panel de Estadísticas           ✅ Documentación Integrada
```

## 📦 Instalación

### Instalación Manual

```bash
# 1. Descarga el plugin
git clone https://github.com/luisalbertoquino/plugins.git

# 2. Copia a la carpeta de plugins de WordPress
cp -r certificate-pro /ruta/a/wordpress/wp-content/plugins/

# 3. Instala las dependencias
cd /ruta/a/wordpress/wp-content/plugins/certificate-pro
composer install
```

### Instalación desde WordPress

1. Ve a **Plugins** → **Añadir nuevo**
2. Haz clic en **Subir plugin**
3. Selecciona el archivo `.zip` del plugin
4. Haz clic en **Instalar ahora**
5. Activa el plugin

### Configuración Rápida

1. ✅ Ve a **Certificados** → **Configuración**
2. ✅ Configura tu API Key de Google Sheets (opcional)
3. ✅ Personaliza los colores del plugin
4. ✅ Sube tus fuentes personalizadas (opcional)
5. ✅ Crea tu primera plantilla de certificado

## ⚙️ Configuración Inicial

### 🔗 1. Integración con Google Sheets

<details>
<summary>Haz clic para ver los pasos de configuración</summary>

1. Accede a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita **Google Sheets API**
4. Genera tus credenciales (API Key)
5. Copia la API Key en **Certificados** → **Configuración**

> 💡 **Tip:** La API Key es opcional. El plugin funciona sin Google Sheets usando datos manuales.

</details>

### 🎨 2. Personalización de Colores

El plugin incluye un sistema completo de personalización de colores:

| Color | Uso |
|-------|-----|
| **Primario** | Botones, enlaces, elementos activos |
| **Hover** | Efectos al pasar el mouse |
| **Éxito** | Mensajes de confirmación |
| **Error** | Mensajes de advertencia |

**Ruta:** `Certificados → Configuración → Personalización de Colores`

### ✍️ 3. Gestión de Fuentes Personalizadas

<details>
<summary>Cómo subir fuentes personalizadas</summary>

**Formatos soportados:**
- ✅ TrueType (`.ttf`)
- ✅ OpenType (`.otf`)

**Pasos:**
1. Ve a **Certificados** → **Fuentes**
2. Haz clic en **Agregar Nueva Fuente**
3. Sube tu archivo de fuente
4. Asigna un nombre descriptivo
5. Usa la fuente en el configurador de campos

**Ubicación:** Las fuentes se almacenan en `/wp-content/uploads/certificados-fuentes/`

> ⚠️ **Nota:** Asegúrate de tener licencia para usar las fuentes comercialmente.

</details>

### 📄 4. Crear tu Primera Plantilla

```
1. Certificados → Plantillas → Agregar Nueva
2. Sube imagen de fondo (JPG/PNG recomendado)
3. Arrastra campos al certificado (nombre, fecha, etc.)
4. Personaliza estilos (fuente, tamaño, color)
5. Activa modo calibración para ajustes precisos
6. Guarda la plantilla
```

## 📖 Uso y Shortcodes

### Shortcode Principal

Inserta el formulario de búsqueda de certificados en cualquier página o entrada:

```php
[certificados_buscar]
```

### Parámetros Disponibles

| Parámetro | Descripción | Ejemplo |
|-----------|-------------|---------|
| `pestana` | ID del evento/pestaña específico | `[certificados_buscar pestana="1"]` |

### Ejemplo de Uso

```html
<!-- Búsqueda general (todos los eventos) -->
[certificados_buscar]

<!-- Búsqueda para un evento específico -->
[certificados_buscar pestana="5"]
```

### 🎯 Flujo de Usuario

```mermaid
Usuario → Ingresa datos → Busca certificado → Descarga PDF con QR
```

1. El usuario accede a la página con el shortcode
2. Ingresa su información (nombre, documento, etc.)
3. El sistema busca en Google Sheets o base de datos
4. Si existe, genera el PDF con código QR único
5. El usuario descarga su certificado

## ❓ Preguntas Frecuentes (FAQ)

<details>
<summary><strong>¿Necesito PHP 8.0 obligatoriamente?</strong></summary>

No, el plugin funciona desde **PHP 7.4** en adelante. Sin embargo, recomendamos **PHP 8.0+** para mejor rendimiento y seguridad.

</details>

<details>
<summary><strong>¿Cuánta memoria PHP necesito?</strong></summary>

**Mínimo:** 128 MB
**Recomendado:** 256 MB o más

Para generar PDFs con imágenes grandes o múltiples certificados simultáneos, puede requerir más memoria.

</details>

<details>
<summary><strong>¿Es compatible con mi tema de WordPress?</strong></summary>

✅ Sí, el plugin está diseñado para funcionar con **cualquier tema de WordPress**. Utiliza CSS con alta especificidad para evitar conflictos de estilos.

</details>

<details>
<summary><strong>¿Puedo personalizar los colores del plugin?</strong></summary>

✅ Sí, desde **Certificados → Configuración → Personalización de Colores** puedes personalizar:
- Color primario
- Color hover
- Color de éxito
- Color de error

Los cambios se aplican inmediatamente en todo el plugin.

</details>

<details>
<summary><strong>¿Puedo usar mis propias fuentes?</strong></summary>

✅ Sí, el plugin soporta fuentes personalizadas:
- **Formatos:** `.ttf` (TrueType) y `.otf` (OpenType)
- **Ubicación:** `Certificados → Fuentes`
- Puedes subir múltiples variantes (Regular, Bold, Italic)

</details>

<details>
<summary><strong>¿Necesito Google Sheets obligatoriamente?</strong></summary>

❌ No, Google Sheets es **opcional**. El plugin puede funcionar con:
- Integración con Google Sheets (recomendado para grandes volúmenes)
- Base de datos local de WordPress
- Importación manual de datos

</details>

<details>
<summary><strong>¿Los certificados tienen validación?</strong></summary>

✅ Sí, cada certificado incluye:
- **Código QR único** con URL de validación
- **ID único** por certificado
- Sistema de validación automático en frontend

</details>

## 💬 Soporte

¿Necesitas ayuda? Estamos aquí para ti:

- 🐛 **Reportar Bugs:** [Abrir Issue](https://github.com/luisalbertoquino/plugins/issues)
- 💡 **Sugerencias:** [Solicitar Funcionalidad](https://github.com/luisalbertoquino/plugins/issues/new)
- 📚 **Documentación:** Consulta la documentación integrada en `Certificados → Documentación`
- 📧 **Contacto:** Para soporte personalizado, contacta al desarrollador

### 🔍 Antes de Reportar un Problema

Por favor, incluye la siguiente información:

```
- Versión de WordPress
- Versión de PHP
- Versión del plugin
- Tema activo
- Otros plugins activos
- Descripción detallada del problema
- Pasos para reproducirlo
```

## 📝 Changelog

<details>
<summary><strong>Ver historial completo de versiones</strong></summary>

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

</details>

---

## 📄 Licencia

Este plugin está licenciado bajo **GPL-2.0+**

```
Copyright (C) 2025 - Certificados Digitales PRO
Este programa es software libre; puede redistribuirlo y/o modificarlo
bajo los términos de la Licencia Pública General GNU.
```

## 🙏 Créditos y Tecnologías

Este plugin fue desarrollado utilizando las siguientes tecnologías:

| Librería | Propósito | Versión |
|----------|-----------|---------|
| [TCPDF](https://tcpdf.org/) | Generación de PDFs de alta calidad | 6.10.1 |
| [Endroid QR Code](https://github.com/endroid/qr-code) | Generación de códigos QR | 3.9.7 |
| [Symfony Components](https://symfony.com/) | Componentes de utilidad | ^5.0 |
| [Google Sheets API](https://developers.google.com/sheets/api) | Integración con hojas de cálculo | v4 |

---

<div align="center">

### ⭐ ¿Te ha sido útil este plugin?

Si este plugin te ha ayudado, considera darle una estrella en GitHub

**Hecho con ❤️ para la comunidad de WordPress**

[⬆ Volver arriba](#-certificados-digitales-pro)

</div>
