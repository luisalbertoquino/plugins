# Empaquetado del Plugin para Producción

## Descripción

Esta funcionalidad permite descargar una versión limpia del plugin **Certificados Digitales PRO** lista para usar en producción, sin archivos de desarrollo, documentación o dependencias innecesarias.

## Características

- ✅ **Solo disponible en entornos de desarrollo** (localhost, .local, .test o con WP_DEBUG activado)
- ✅ **Interfaz visual** en la página de Configuración del plugin
- ✅ **Exclusión automática** de archivos innecesarios
- ✅ **Generación de ZIP** con fecha y hora en el nombre
- ✅ **Descarga directa** lista para instalar

## Uso

### Desde la Interfaz de WordPress

1. Ve a **Certificados Digitales** → **Configuración**
2. Desplázate hasta el final de la página
3. Encontrarás una tarjeta naranja con el título **"Descargar Plugin para Producción"**
4. Haz clic en el botón **"Descargar Plugin Limpio"**
5. Se descargará un archivo ZIP con el nombre: `certificate-pro-YYYY-MM-DD-HHMMSS.zip`
6. Este archivo está listo para instalar en tu sitio de producción

### Archivos y Carpetas Excluidos

El empaquetado excluye automáticamente:

**Carpetas:**
- `.claude/` - Configuración de Claude Code
- `.git/` - Control de versiones
- `node_modules/` - Dependencias de Node.js
- `docs/` - Documentación

**Archivos de documentación:**
- `README.md`
- `ACTUALIZACION.md`
- `NUEVAS_FUNCIONALIDADES.md`
- `EMPAQUETADO.md` (este archivo)

**Archivos de desarrollo:**
- `composer.json` y `composer.lock`
- `package.json` y `package-lock.json`
- `diagnostico.php`
- `limpiar-cache.php`
- `reiniciar-plugin.php`
- `test-clase.php`

**Otros:**
- `.gitignore`
- `.DS_Store`
- `Thumbs.db`
- `*.log`
- `.editorconfig`
- `.eslintrc`
- `.prettierrc`

## Requisitos

- PHP 7.4 o superior
- Extensión PHP `ZipArchive` habilitada
- Permisos de escritura en el directorio temporal del sistema
- Estar en un entorno de desarrollo (localhost, .local, .test o WP_DEBUG activado)

## Seguridad

- ✅ Verificación de nonce para prevenir CSRF
- ✅ Verificación de permisos (solo administradores)
- ✅ Solo disponible en entornos de desarrollo
- ✅ Limpieza automática de archivos temporales
- ✅ Sin modificación de archivos originales

## Solución de Problemas

### El botón no aparece
- Verifica que estés en un entorno de desarrollo (localhost, .local, .test)
- Si no, activa `WP_DEBUG` en tu `wp-config.php`

### Error al generar el ZIP
- Verifica que la extensión `ZipArchive` esté habilitada en PHP
- Verifica los permisos del directorio temporal (`sys_get_temp_dir()`)

### El archivo descargado está vacío
- Verifica los logs de PHP para errores
- Asegúrate de tener suficiente espacio en disco

## Código Técnico

La funcionalidad está implementada en:

- **Clase principal**: `includes/class-plugin-packager.php`
- **Hook de descarga**: `certificados-digitales.php` (línea 144-151)
- **Botón UI**: `admin/class-admin.php` (línea 1269-1272)
- **Estilos**: `admin/css/admin-style.css` (línea 106-133)

## Personalización

Para agregar o eliminar archivos de la exclusión, edita el array `$exclude_patterns` en la clase `Certificados_Plugin_Packager`:

```php
private static $exclude_patterns = array(
    'tu-archivo.php',
    'tu-carpeta',
    // ...
);
```

## Versión

- **Añadido en**: v1.5.13
- **Última actualización**: 2025-12-10
