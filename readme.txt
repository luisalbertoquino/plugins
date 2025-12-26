=== Certificados Digitales PRO ===
Contributors: luisalbertoquino
Tags: certificados, pdf, google sheets, qr code, certificates, diplomas
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.5.13
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sistema profesional de generación de certificados digitales en PDF con integración a Google Sheets, editor visual drag & drop y validación con códigos QR únicos.

== Description ==

**Certificados Digitales PRO** es un plugin completo para WordPress que te permite crear, gestionar y distribuir certificados digitales de forma profesional y automatizada.

= Características Principales =

* **Generación de PDFs de Alta Calidad** - Certificados profesionales con TCPDF
* **Editor Visual Drag & Drop** - Diseña tus certificados intuitivamente
* **Integración con Google Sheets** - Importa datos de participantes en tiempo real
* **Códigos QR Únicos** - Sistema de validación automático para cada certificado
* **Fuentes Personalizadas** - Soporte completo para archivos .ttf y .otf
* **Personalización de Colores** - Sistema completo de temas personalizables
* **Múltiples Plantillas** - Soporte para diferentes tipos de certificados y eventos
* **Modo Calibración** - Grilla visual para posicionamiento preciso de campos
* **Sistema de Estadísticas** - Dashboard con métricas de descargas
* **Búsqueda Frontend** - Shortcode para que usuarios busquen sus certificados
* **Auto-guardado** - Sistema inteligente de configuración
* **Responsive** - Interfaz adaptable a todos los dispositivos

= Características Avanzadas =

* Sistema de caché inteligente para optimizar peticiones a Google Sheets
* Mapeo flexible de columnas para diferentes estructuras de datos
* Migraciones automáticas de base de datos
* Panel de documentación integrado
* Sistema de validación segura con URLs únicas
* Soporte para estilos de fuente (normal, negrita, cursiva, combinaciones)
* Compatible con PHP 7.4 hasta PHP 8.3

= Casos de Uso =

* **Eventos y Conferencias** - Genera certificados de asistencia o participación
* **Cursos Online** - Emite diplomas de finalización automáticamente
* **Capacitaciones** - Certifica competencias y habilidades adquiridas
* **Webinars** - Entrega certificados de participación
* **Competencias** - Reconocimientos y premios digitales
* **Instituciones Educativas** - Sistema completo de certificación académica

= Cómo Funciona =

1. Configura tu plantilla de certificado con el editor visual
2. Conecta tu Google Sheets con los datos de participantes (opcional)
3. Inserta el shortcode `[certificados_buscar]` en cualquier página
4. Los usuarios buscan y descargan sus certificados automáticamente
5. Cada certificado incluye un código QR único para validación

= Requisitos del Sistema =

* WordPress 5.8 o superior
* PHP 7.4 o superior (compatible hasta PHP 8.3)
* MySQL 5.6 o superior / MariaDB 10.1+
* Extensiones PHP: gd/imagick, mbstring, json, curl

= Soporte y Documentación =

* Documentación completa integrada en el plugin
* Soporte a través de GitHub Issues
* Actualizaciones regulares y mejoras continuas

= Enlaces =

* [Repositorio GitHub](https://github.com/luisalbertoquino/plugins)
* [Reportar Issues](https://github.com/luisalbertoquino/plugins/issues)
* [Documentación](https://github.com/luisalbertoquino/plugins#readme)

== Installation ==

= Instalación Automática =

1. Ve a 'Plugins' → 'Añadir nuevo' en tu panel de WordPress
2. Busca 'Certificados Digitales PRO'
3. Haz clic en 'Instalar ahora'
4. Activa el plugin
5. Ve a 'Certificados' → 'Configuración' para empezar

= Instalación Manual =

1. Descarga el archivo ZIP del plugin
2. Ve a 'Plugins' → 'Añadir nuevo' → 'Subir plugin'
3. Selecciona el archivo ZIP descargado
4. Haz clic en 'Instalar ahora'
5. Activa el plugin
6. Ve a 'Certificados' → 'Configuración' para configurar

= Instalación desde GitHub =

1. Clona el repositorio: `git clone https://github.com/luisalbertoquino/plugins.git`
2. Copia la carpeta del plugin a `/wp-content/plugins/`
3. Ejecuta `composer install` en la carpeta del plugin
4. Activa el plugin desde el panel de WordPress

= Configuración Inicial =

1. Ve a 'Certificados' → 'Configuración'
2. Configura tu API Key de Google Sheets (opcional)
3. Personaliza los colores del plugin
4. Sube tus fuentes personalizadas (opcional)
5. Crea tu primera plantilla de certificado
6. Inserta el shortcode `[certificados_buscar]` en una página

== Frequently Asked Questions ==

= ¿Necesito Google Sheets obligatoriamente? =

No, Google Sheets es opcional. El plugin puede funcionar con datos almacenados localmente en la base de datos de WordPress o mediante importación manual.

= ¿Puedo personalizar el diseño de los certificados? =

Sí, completamente. El plugin incluye un editor visual drag & drop donde puedes posicionar campos, cambiar fuentes, tamaños, colores y estilos.

= ¿Los certificados incluyen validación? =

Sí, cada certificado incluye un código QR único que enlaza a una URL de validación, permitiendo verificar la autenticidad del certificado.

= ¿Puedo usar mis propias fuentes? =

Sí, el plugin soporta la carga de fuentes personalizadas en formato .ttf y .otf. Ve a 'Certificados' → 'Fuentes' para gestionarlas.

= ¿Es compatible con mi tema de WordPress? =

Sí, el plugin está diseñado para funcionar con cualquier tema de WordPress. Utiliza CSS con alta especificidad para evitar conflictos.

= ¿Qué versiones de PHP son compatibles? =

El plugin es compatible con PHP 7.4 hasta PHP 8.3. Recomendamos PHP 8.0 o superior para mejor rendimiento.

= ¿Puedo tener múltiples plantillas de certificados? =

Sí, puedes crear tantas plantillas como necesites para diferentes eventos, cursos o tipos de certificados.

= ¿Cómo funcionan las estadísticas? =

El plugin incluye un dashboard con métricas detalladas de descargas por evento, certificado y período de tiempo.

== Screenshots ==

1. Panel de configuración principal
2. Editor visual de certificados drag & drop
3. Gestión de fuentes personalizadas
4. Dashboard de estadísticas
5. Formulario de búsqueda frontend
6. Ejemplo de certificado generado con QR
7. Sistema de validación de certificados
8. Configuración de colores personalizados

== Changelog ==

= 1.5.13 (2025-12-09) =
* **CORRECCIÓN**: Verificación de índices antes de crearlos en migraciones
* **MEJORA**: Función helper `index_exists()` para validar índices en BD
* Previene errores de índices duplicados durante migraciones
* Sistema de migraciones más robusto y tolerante a fallos

= 1.5.12 (2025-12-09) =
* **CORRECCIÓN CRÍTICA**: Tabla `certificados_sheets_cache_meta` ahora se crea automáticamente
* **MEJORA**: Migración automática de columnas faltantes en tabla de caché
* Creación de columnas `etag`, `needs_refresh` y `cached_data` si no existen
* Sistema de caché completamente funcional en instalaciones nuevas y actualizaciones

= 1.5.11 (2025-12-09) =
* **CORRECCIÓN CRÍTICA**: Sistema de mapeo de columnas ahora funciona correctamente
* **MEJORA**: Búsqueda de certificados usa mapeo personalizado de columnas
* Compatibilidad total con Google Sheets que usan nombres de columnas personalizados

= 1.5.10 (2025-12-09) =
* **CORRECCIÓN CRÍTICA**: Sistema de estadísticas funciona correctamente después de actualizar
* **MEJORA**: Migración automática de tabla `certificados_descargas` si no existe
* Migración de datos históricos automática

= 1.5.9 (2025-12-09) =
* **MEJORA**: Sistema de migraciones de base de datos robusto y seguro
* **CORRECCIÓN**: Iconos perfectamente centrados en tarjetas de estadísticas
* Preservación completa de datos existentes durante actualizaciones

= 1.5.0 (2025-12-09) =
* **CORRECCIÓN**: Todos los modales ahora funcionan correctamente
* **MEJORA**: Botón "Guardar Cambios" visible en configurador de campos
* Sistema de apertura forzada de modales con `!important`

= 1.2.0 (2025-11-29) =
* **NUEVA CARACTERÍSTICA**: Estilo de fuente configurable por campo
* Opción para seleccionar entre: Normal, Negrita, Cursiva, Negrita Cursiva
* Actualización automática de base de datos para instalaciones existentes

= 1.1.1 (2025-11-29) =
* Formateo automático de nombres a formato título
* Soporte completo para caracteres con tildes y letra ñ

= 1.1.0 (2025-11-29) =
* **IMPORTANTE**: Ajuste completo de dependencias para PHP 7.4.33
* Downgrade de `endroid/qr-code` de v5.x a v3.9.7 (compatible con PHP 7.4)
* Actualización de TCPDF a v6.10.1

= 1.0.0 =
* Versión inicial del plugin

== Upgrade Notice ==

= 1.5.13 =
Correcciones importantes en el sistema de migraciones. Se recomienda actualizar.

= 1.5.12 =
Corrección crítica para el sistema de caché. Actualización recomendada para todos los usuarios.

= 1.5.11 =
Mejora crítica en el mapeo de columnas de Google Sheets. Actualice si usa nombres personalizados.

= 1.1.0 =
Actualización importante de compatibilidad con PHP 7.4. Si usas PHP 7.4, esta actualización es esencial.

== Soporte ==

Para soporte, reportar bugs o solicitar nuevas características:

* GitHub Issues: https://github.com/luisalbertoquino/plugins/issues
* Documentación: Consulta 'Certificados' → 'Documentación' en tu panel de WordPress

Por favor, incluye la siguiente información al reportar problemas:
* Versión de WordPress
* Versión de PHP
* Versión del plugin
* Tema activo
* Otros plugins activos
* Descripción detallada y pasos para reproducir

== Créditos ==

Desarrollado con:
* TCPDF - Generación de PDFs de alta calidad
* Endroid QR Code - Generación de códigos QR
* Symfony Components - Componentes de utilidad
* Google Sheets API - Integración con hojas de cálculo

Licencia: GPL-2.0+
Copyright (C) 2025 - Luis Alberto Aquino
