# Guía de Actualización del Plugin

## Cómo Funciona el Sistema de Actualización

El plugin **Certificados Digitales** utiliza un sistema robusto de actualización de base de datos que permite actualizaciones seguras sin pérdida de datos.

### 1. Actualización Automática de Base de Datos

Cuando subes una nueva versión del plugin comprimido (.zip) y la instalas en WordPress:

#### ✅ Lo que SÍ hace automáticamente:

1. **Crea nuevas tablas** si no existen
2. **Agrega columnas faltantes** a tablas existentes
3. **Actualiza índices y claves** según sea necesario
4. **Preserva todos los datos** existentes

#### ❌ Lo que NO hace:

1. **NO elimina tablas** existentes
2. **NO borra datos** de ninguna tabla
3. **NO sobrescribe configuraciones** del usuario
4. **NO elimina archivos** subidos anteriormente

### 2. Tecnología Utilizada: dbDelta()

El plugin usa la función `dbDelta()` de WordPress que:

- Compara el esquema definido con el esquema existente
- Solo aplica los cambios necesarios
- Es 100% segura para actualizaciones
- No duplica ni pierde datos

### 3. Tablas del Plugin

#### Tablas Core (existentes desde v1.0):
1. `wp_certificados_eventos` - Eventos
2. `wp_certificados_pestanas` - Pestañas/Tipos de certificado
3. `wp_certificados_fuentes` - Fuentes personalizadas
4. `wp_certificados_campos_config` - Configuración de campos
5. `wp_certificados_descargas_log` - Log de descargas (histórico)
6. `wp_certificados_cache` - Caché de PDFs generados

#### Tablas Nuevas (v1.3.0+):
7. `wp_certificados_descargas` - Descargas para estadísticas
8. `wp_certificados_sheets_cache_meta` - Metadatos de caché
9. `wp_certificados_column_mapping` - Mapeo de columnas
10. `wp_certificados_survey_config` - Configuración de encuestas

### 4. Proceso de Actualización Paso a Paso

```
1. Usuario sube nueva versión .zip en WordPress
   ↓
2. WordPress extrae archivos
   ↓
3. WordPress detecta que es una actualización
   ↓
4. Se ejecuta register_activation_hook()
   ↓
5. Se llama a Certificados_Digitales_Activator::activate()
   ↓
6. Se ejecuta create_tables() que usa dbDelta()
   ↓
7. dbDelta() compara esquemas
   ↓
8. Se crean solo las tablas/columnas faltantes
   ↓
9. Se actualiza la versión en wp_options
   ↓
10. ✅ Actualización completada sin pérdida de datos
```

### 5. Ejemplo Práctico

**Escenario:** Instalas v1.3.0 sobre una instalación existente de v1.2.0

**Resultado:**
```
✅ Tablas existentes (1-6): Permanecen intactas con todos sus datos
✅ Nuevas tablas (7-10): Se crean automáticamente
✅ Configuraciones: Se preservan todas
✅ Eventos: Todos los eventos siguen funcionando
✅ PDFs generados: Permanecen en caché
✅ Estadísticas: Se mantiene el historial completo
```

### 6. Verificar Actualización Exitosa

Después de actualizar, verifica:

1. **WordPress Admin > Plugins**
   - Versión mostrada debe ser la nueva (ej: 1.3.0)

2. **Certificados > Configuración**
   - Tus eventos deben seguir ahí
   - La API Key debe estar guardada

3. **Nuevas Opciones del Menú**
   - Certificados > Mapeo de Columnas
   - Certificados > Encuestas
   - Certificados > Estadísticas

4. **Base de Datos (opcional)**
   ```sql
   -- En phpMyAdmin ejecuta:
   SHOW TABLES LIKE 'wp_certificados_%';
   -- Deberías ver las 10 tablas listadas arriba
   ```

### 7. Preservación de Datos al Desinstalar

**IMPORTANTE:** El plugin NO elimina datos al desinstalar.

#### Lo que se preserva:
- ✅ Todas las tablas de la base de datos
- ✅ Todos los datos de eventos y certificados
- ✅ Historial completo de descargas
- ✅ Configuraciones de mapeo y encuestas
- ✅ Archivos PDF generados
- ✅ Fuentes personalizadas subidas

#### Razón:
Los datos de certificados son críticos y no deben perderse accidentalmente.

#### Para eliminar datos manualmente:
Si realmente necesitas eliminar todo:

1. Edita `uninstall.php`
2. Descomenta las líneas 108-110:
   ```php
   certificados_digitales_delete_tables();
   certificados_digitales_delete_options();
   certificados_digitales_delete_uploads();
   ```
3. Desinstala el plugin desde WordPress

### 8. Rollback (Volver a Versión Anterior)

Si necesitas volver a una versión anterior:

1. **Desactiva** (NO desinstales) el plugin actual
2. **Elimina** los archivos del plugin del servidor
3. **Sube** la versión anterior
4. **Activa** el plugin

**Nota:** Las nuevas tablas permanecerán pero no causarán problemas.

### 9. Migración Entre Servidores

Para mover el plugin entre servidores:

1. **Exporta** la base de datos completa
2. **Copia** la carpeta `wp-content/uploads/certificados-digitales`
3. **Importa** la base de datos en el nuevo servidor
4. **Instala** el plugin en el nuevo WordPress
5. **Configura** la API Key de Google si es necesaria

### 10. Solución de Problemas

#### "Las nuevas tablas no se crearon"
**Solución:**
1. Desactiva el plugin
2. Activa el plugin nuevamente
3. Esto fuerza la ejecución de `create_tables()`

#### "Perdí datos al actualizar"
**Respuesta:** Imposible. El sistema NO elimina datos.
- Verifica que estés viendo el servidor/base de datos correctos
- Verifica que no hayas desinstalado por error

#### "Error de base de datos"
**Solución:**
1. Revisa permisos del usuario de MySQL
2. El usuario debe tener permisos: `CREATE`, `ALTER`, `INDEX`
3. Verifica el log de errores de WordPress

### 11. Mejores Prácticas

✅ **ANTES de actualizar:**
- Haz un backup completo de la base de datos
- Haz un backup de `wp-content/uploads/certificados-digitales`

✅ **DURANTE la actualización:**
- Usa un entorno de staging si es posible
- Actualiza primero en staging, luego en producción

✅ **DESPUÉS de actualizar:**
- Verifica que los eventos sigan funcionando
- Prueba generar un certificado de prueba
- Revisa las nuevas opciones del menú

### 12. Compatibilidad de Versiones

| Versión Instalada | Actualizar a | ¿Compatible? | Notas |
|-------------------|--------------|--------------|-------|
| v1.0.x | v1.3.0 | ✅ Sí | Sin problemas |
| v1.1.x | v1.3.0 | ✅ Sí | Sin problemas |
| v1.2.x | v1.3.0 | ✅ Sí | Sin problemas |
| v1.3.0 | v1.3.x | ✅ Sí | Sin problemas |

### 13. Registro de Cambios de Esquema

#### v1.3.0 - Nuevas Tablas
- `wp_certificados_descargas` - Para estadísticas mejoradas
- `wp_certificados_sheets_cache_meta` - Para caché inteligente
- `wp_certificados_column_mapping` - Para mapeo dinámico
- `wp_certificados_survey_config` - Para encuestas

#### v1.2.0 - Cambios en Esquema
- Agregada columna `font_style` a `certificados_campos_config`

#### v1.1.0 - Tablas Iniciales
- Primera versión con esquema completo

---

## Soporte

Si tienes problemas con la actualización:
1. Revisa esta guía completa
2. Verifica los logs de error de WordPress
3. Haz un backup antes de cualquier acción correctiva
4. Contacta al desarrollador con detalles del error
