# Nuevas Funcionalidades - Certificados Digitales PRO v1.3.0

Este documento describe las tres nuevas funcionalidades implementadas en el plugin **Certificados Digitales PRO**.

---

## 📋 Índice

1. [Sistema de Detección de Cambios y Caché Inteligente](#1-sistema-de-detección-de-cambios-y-caché-inteligente)
2. [Mapeo Dinámico de Columnas](#2-mapeo-dinámico-de-columnas)
3. [Sistema de Encuestas de Satisfacción](#3-sistema-de-encuestas-de-satisfacción)
4. [Compatibilidad con Versiones Anteriores](#compatibilidad)
5. [Instalación y Configuración](#instalación-y-configuración)

---

## 1. Sistema de Detección de Cambios y Caché Inteligente

### 🎯 Objetivo
Detectar automáticamente cuando el Google Sheet ha sido modificado y recargar la caché local solo cuando sea necesario, mejorando el rendimiento y reduciendo llamadas innecesarias a la API de Google.

### ✨ Características

- **Detección automática de cambios**: El sistema verifica cambios mediante:
  - Hash MD5 del contenido
  - Conteo de filas
  - Timestamp de última verificación

- **Caché inteligente**:
  - TTL (Time To Live) configurable (por defecto 5 minutos)
  - Almacenamiento en base de datos
  - Recarga automática solo cuando detecta cambios

- **Optimización de rendimiento**:
  - Reduce llamadas a Google Sheets API
  - Respuestas más rápidas al usuario
  - Menor carga en el servidor

### 📦 Archivos Creados

- `includes/class-sheets-cache-manager.php` - Clase principal del gestor de caché

### 🔧 Uso Programático

```php
// Obtener instancia del gestor de caché
$cache_manager = new Certificados_Sheets_Cache_Manager();

// Obtener datos con caché inteligente
$data = $cache_manager->get_sheet_data_cached(
    $sheet_id,      // ID del Google Sheet
    $sheet_name,    // Nombre de la hoja
    $api_key,       // API Key de Google
    false           // Force refresh (opcional)
);

// Verificar si necesita actualización
$needs_refresh = $cache_manager->needs_refresh( $sheet_id, $sheet_name, $api_key );

// Limpiar caché manualmente
$cache_manager->clear_cache( $sheet_id, $sheet_name );

// Obtener estadísticas de caché
$stats = $cache_manager->get_cache_stats();
```

### 📊 Tabla de Base de Datos

Se crea automáticamente la tabla `wp_certificados_sheets_cache_meta`:

```sql
- sheet_id: ID del Google Sheet
- sheet_name: Nombre de la hoja
- last_modified: Última fecha de modificación
- content_hash: Hash MD5 del contenido
- row_count: Número de filas
- etag: ETag (para futuras implementaciones)
- last_check: Última verificación
- needs_refresh: Bandera de actualización necesaria
- cached_data: Datos cacheados (serializado)
```

### ⚙️ Configuración

El TTL de caché se puede modificar:

```php
$cache_manager->set_cache_ttl( 600 ); // 10 minutos
```

---

## 2. Mapeo Dinámico de Columnas

### 🎯 Objetivo
Permitir que el administrador mapee manualmente las columnas del Google Sheet a los campos del sistema, sin importar cómo estén nombradas las cabeceras.

### ✨ Características

- **Lectura automática de cabeceras**: El sistema lee la primera fila del Google Sheet
- **Sugerencias inteligentes**: Algoritmo que sugiere mapeos automáticos basándose en nombres similares
- **Mapeo flexible**: Soporta diferentes nombres de columnas:
  - "Número de Documento", "Cedula", "ID", "NumDoc", "CC", "DNI"
  - "Nombre Completo", "Nombres", "Participante"
  - "Nombre del Evento", "Evento", "Curso", "Programa"
  - Y más...

- **Persistencia**: Los mapeos se guardan por evento y hoja
- **Interfaz visual**: Página de administración intuitiva en WordPress

### 📦 Archivos Creados

- `includes/class-column-mapper.php` - Clase principal del mapeador
- `admin/class-admin-column-mapper.php` - Página de administración
- `admin/js/mapper-admin.js` - JavaScript para la interfaz
- `admin/css/mapper-admin.css` - Estilos CSS

### 🖥️ Uso en el Administrador

1. Ve a **Certificados > Mapeo de Columnas**
2. Selecciona un evento
3. Ingresa el nombre de la hoja del Google Sheet
4. Haz clic en **Cargar Cabeceras**
5. El sistema mostrará:
   - Campos del sistema (izquierda)
   - Columnas del Sheet (centro)
   - Sugerencias automáticas (derecha)
6. Mapea manualmente o usa **Aplicar Sugerencias Automáticas**
7. Guarda la configuración

### 🔧 Uso Programático

```php
$mapper = new Certificados_Column_Mapper();

// Leer cabeceras del sheet
$headers = $mapper->read_sheet_headers( $sheet_id, $sheet_name, $api_key );

// Obtener sugerencias automáticas
$suggestions = $mapper->suggest_mappings( $headers );

// Guardar mapeo
$mappings = array(
    'numero_documento' => array(
        'sheet_column' => 'Cedula',
        'column_index' => 0
    ),
    'nombre_completo' => array(
        'sheet_column' => 'Nombre',
        'column_index' => 1
    )
);
$mapper->save_column_mapping( $evento_id, $sheet_name, $mappings );

// Obtener mapeo guardado
$mapping = $mapper->get_column_mapping( $evento_id, $sheet_name );

// Buscar con mapeo personalizado
$result = $mapper->search_with_mapping(
    $sheet_id,
    $sheet_name,
    $api_key,
    $evento_id,
    $numero_documento
);
```

### 📊 Campos del Sistema

Los siguientes campos están disponibles para mapear:

- `numero_documento` - Número de Documento (obligatorio)
- `nombre_completo` - Nombre Completo
- `nombre_evento` - Nombre del Evento
- `ciudad` - Ciudad
- `empresa` - Empresa/Institución
- `cargo` - Cargo
- `fecha_evento` - Fecha del Evento
- `tipo_certificado` - Tipo de Certificado
- `horas` - Horas
- `nota` - Nota/Calificación

Puedes agregar más campos usando el filtro:

```php
add_filter( 'certificados_system_fields', function( $fields ) {
    $fields['email'] = 'Correo Electrónico';
    return $fields;
});
```

---

## 3. Sistema de Encuestas de Satisfacción

### 🎯 Objetivo
Implementar un sistema de encuestas que puede ser opcional u obligatorio antes de descargar el certificado.

### ✨ Características

#### Modo Opcional
- Se muestra un enlace a la encuesta
- El usuario puede omitirlo
- Puede descargar el certificado sin completar la encuesta

#### Modo Obligatorio
- El usuario DEBE completar la encuesta antes de descargar
- El sistema verifica en el Google Sheet de respuestas si ya completó la encuesta
- Compara número de documento y opcionalmente el nombre del evento
- Muestra mensaje si no ha completado la encuesta

#### Modo Deshabilitado
- No se muestra ninguna encuesta

### 📦 Archivos Creados

- `includes/class-survey-manager.php` - Clase principal del gestor de encuestas
- `admin/class-admin-survey.php` - Página de administración
- `admin/js/survey-admin.js` - JavaScript para la interfaz

### 🖥️ Configuración en el Administrador

1. Ve a **Certificados > Encuestas**
2. Selecciona un evento
3. Configura los siguientes campos:

#### Configuración General
- **Modo de Encuesta**: Deshabilitada / Opcional / Obligatoria
- **URL de la Encuesta**: Enlace a Google Forms u otra plataforma
- **Título del Modal**: Texto personalizado
- **Mensaje**: Descripción o instrucciones

#### Configuración para Modo Obligatorio
- **ID del Google Sheet de Respuestas**: ID del sheet vinculado al formulario
- **Nombre de la Hoja de Respuestas**: Ej: "Respuestas de formulario 1"
- **Columna de Número de Documento**: Columna que contiene el documento
- **Columna del Nombre del Evento** (opcional): Para validar evento específico
- **Valor del Evento a Buscar**: Nombre del evento en las respuestas

### 🔧 Uso Programático

```php
$survey_manager = new Certificados_Survey_Manager();

// Guardar configuración
$config = array(
    'survey_mode' => 'mandatory', // 'disabled', 'optional', 'mandatory'
    'survey_url' => 'https://forms.google.com/...',
    'survey_title' => 'Encuesta de Satisfacción',
    'survey_message' => 'Por favor completa nuestra encuesta',
    'response_sheet_id' => '1abc...',
    'response_sheet_name' => 'Respuestas de formulario 1',
    'document_column' => 'Número de identificación',
    'document_column_index' => 1,
    'event_column' => 'Nombre del evento',
    'event_column_index' => 2,
    'event_match_value' => 'Mi Evento 2024'
);
$survey_manager->save_survey_config( $evento_id, $config );

// Verificar si completó la encuesta
$result = $survey_manager->check_survey_completed(
    $evento_id,
    $numero_documento,
    $api_key
);

if ( $result['completed'] ) {
    // Permitir descarga
} else {
    // Mostrar mensaje y enlace a encuesta
}
```

### 📋 Flujo de Trabajo con Google Forms

1. **Crear Formulario en Google Forms**
   - Incluye pregunta para número de documento
   - Opcionalmente, pregunta por el nombre del evento
   - Haz que la pregunta del documento sea obligatoria

2. **Conectar a Google Sheet**
   - En Google Forms: Respuestas > Crear hoja de cálculo
   - Copia el ID del Google Sheet creado (está en la URL)

3. **Configurar en WordPress**
   - Ingresa el ID del Sheet de respuestas
   - Ingresa el nombre de la hoja (generalmente "Respuestas de formulario 1")
   - Carga las cabeceras
   - Mapea las columnas correspondientes

4. **Publicar URL del Formulario**
   - Copia la URL del formulario de Google Forms
   - Pégala en la configuración de encuesta

### 🔄 Proceso de Validación

Cuando un usuario intenta descargar un certificado en modo obligatorio:

1. El sistema obtiene su número de documento
2. Consulta el Google Sheet de respuestas
3. Busca una fila con ese número de documento
4. Si está configurada, también valida el nombre del evento
5. Si encuentra coincidencia → Permite descarga
6. Si NO encuentra coincidencia → Muestra modal con enlace a encuesta

---

## Compatibilidad

### ✅ Retrocompatibilidad Garantizada

Todas las nuevas funcionalidades son **completamente opcionales** y **no afectan** el funcionamiento existente del plugin:

- Si no configuras el mapeo de columnas, el plugin usa el sistema tradicional de nombres de columnas
- Si no configuras encuestas, los certificados se descargan normalmente
- El caché se activa automáticamente pero es transparente para el usuario
- Todas las configuraciones anteriores se mantienen intactas

### 🔄 Migración

No es necesaria ninguna migración. Las nuevas tablas se crean automáticamente al actualizar el plugin.

### 🗄️ Nuevas Tablas de Base de Datos

```sql
wp_certificados_sheets_cache_meta          -- Caché de Google Sheets
wp_certificados_column_mapping             -- Mapeo de columnas
wp_certificados_survey_config              -- Configuración de encuestas
```

---

## Instalación y Configuración

### Requisitos

- WordPress 5.8+
- PHP 7.4+
- Plugin "Certificados Digitales PRO" instalado
- API Key de Google Sheets configurada

### Pasos de Instalación

1. **Actualizar el Plugin**
   - Los nuevos archivos ya están incluidos
   - Las tablas se crean automáticamente

2. **Verificar Instalación**
   - Ve a **Certificados > Dashboard**
   - Verás las nuevas opciones en el menú:
     - Mapeo de Columnas
     - Encuestas

3. **Configurar Funcionalidades (Opcional)**

   #### Para Mapeo de Columnas:
   - Ve a **Certificados > Mapeo de Columnas**
   - Selecciona un evento
   - Carga las cabeceras y mapea los campos

   #### Para Encuestas:
   - Crea un formulario en Google Forms
   - Conéctalo a un Google Sheet
   - Ve a **Certificados > Encuestas**
   - Configura el modo y los parámetros

4. **Probar Funcionalidades**
   - Intenta descargar un certificado
   - Verifica que todo funciona correctamente

---

## Soporte y Documentación Adicional

### 📚 Archivos de Referencia

- `class-sheets-cache-manager.php` - Documentación inline del sistema de caché
- `class-column-mapper.php` - Documentación inline del mapeador
- `class-survey-manager.php` - Documentación inline del gestor de encuestas

### 🐛 Resolución de Problemas

**Problema**: No se cargan las cabeceras del Google Sheet

**Solución**:
- Verifica que la API Key esté configurada correctamente
- Asegúrate de que el Google Sheet sea público o compartido con la cuenta de la API
- Verifica que el nombre de la hoja sea exacto (distingue mayúsculas/minúsculas)

**Problema**: La encuesta obligatoria no detecta que completé el formulario

**Solución**:
- Verifica que el ID del Sheet de respuestas sea correcto
- Asegúrate de que el nombre de la hoja sea exacto
- Verifica que el mapeo de columnas esté correcto
- Comprueba que el número de documento coincida exactamente

**Problema**: El caché no se actualiza cuando cambio el Google Sheet

**Solución**:
- El sistema verifica cambios cada 5 minutos por defecto
- Puedes forzar la recarga desde el código con `force_refresh = true`
- O limpiar la caché manualmente desde la base de datos

---

## Mejoras Futuras

Posibles mejoras para futuras versiones:

- [ ] Interfaz para gestionar caché (limpiar, ver estadísticas)
- [ ] Soporte para múltiples idiomas en encuestas
- [ ] Recordatorios automáticos para completar encuestas
- [ ] Estadísticas de completitud de encuestas
- [ ] Exportación de mapeos de columnas
- [ ] Importación masiva de configuraciones
- [ ] Webhooks para notificaciones cuando alguien descarga un certificado

---

## Changelog

### Versión 1.3.0 (2024)

**Nuevas Funcionalidades:**
- ✅ Sistema de detección de cambios y caché inteligente para Google Sheets
- ✅ Mapeo dinámico de columnas con sugerencias automáticas
- ✅ Sistema de encuestas de satisfacción con modos opcional y obligatorio

**Mejoras:**
- Optimización de rendimiento en consultas a Google Sheets
- Reducción de llamadas a la API de Google
- Mejor experiencia de usuario en el administrador

**Compatibilidad:**
- 100% compatible con versiones anteriores
- No requiere migración de datos
- Funcionalidades opcionales que no afectan el flujo existente

---

## Créditos

Desarrollado para el plugin **Certificados Digitales PRO**

**Licencia**: GPL-2.0+

---

## Contacto

Para soporte, reportar bugs o solicitar nuevas funcionalidades, por favor contacta al administrador del plugin.
