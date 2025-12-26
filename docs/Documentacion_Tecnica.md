# Documentación Técnica - Certificados Digitales PRO

**Versión:** 1.4.0
**Desarrollado por:** Luis Quino con Claude AI
**Contacto:** webmaster@uninavarra.edu.co

---

## Tabla de Contenidos

1. [Estructura del Proyecto](#1-estructura-del-proyecto)
2. [Arquitectura del Plugin](#2-arquitectura-del-plugin)
3. [Base de Datos](#3-base-de-datos)
4. [Clases Principales](#4-clases-principales)
5. [Flujo de Funcionamiento](#5-flujo-de-funcionamiento)
6. [Hooks y Filtros Disponibles](#6-hooks-y-filtros-disponibles)
7. [API de Google Sheets](#7-api-de-google-sheets)
8. [Sistema de Caché](#8-sistema-de-cache)
9. [Generación de PDFs](#9-generacion-de-pdfs)
10. [Seguridad y Validaciones](#10-seguridad-y-validaciones)

---

## 1. Estructura del Proyecto

### Árbol de Directorios

```
certificados-digitales/
├── admin/
│   ├── css/                          # Estilos del admin
│   │   ├── admin-style.css           # Estilos principales
│   │   ├── dashboard.css             # Estilos del dashboard
│   │   ├── documentacion.css         # Estilos de documentación
│   │   ├── stats-admin.css           # Estilos de estadísticas
│   │   └── configurador-campos.css   # Estilos del configurador visual
│   ├── js/                           # Scripts del admin
│   │   ├── eventos-admin.js          # Gestión de eventos
│   │   ├── pestanas-admin.js         # Gestión de pestañas
│   │   ├── fuentes-admin.js          # Gestión de fuentes
│   │   ├── configurador-campos.js    # Configurador visual
│   │   ├── column-mapper.js          # Mapeo de columnas
│   │   ├── survey-admin.js           # Gestión de encuestas
│   │   └── stats-admin.js            # Estadísticas con Chart.js
│   ├── class-admin.php               # Controlador principal del admin
│   ├── class-admin-column-mapper.php # Página de mapeo de columnas
│   ├── class-admin-survey.php        # Página de encuestas
│   ├── class-admin-stats.php         # Página de estadísticas
│   ├── class-admin-documentacion.php # Página de documentación
│   ├── class-pestanas.php            # Gestión de pestañas (UI)
│   ├── class-campos.php              # Gestión de campos (legacy)
│   └── class-fuentes.php             # Gestión de fuentes
├── includes/
│   ├── class-core.php                # Clase principal del plugin
│   ├── class-activator.php           # Ejecuta tareas al activar
│   ├── class-deactivator.php         # Ejecuta tareas al desactivar
│   ├── class-autoloader.php          # Carga automática de clases
│   ├── class-google-sheets.php       # Integración Google Sheets API v4
│   ├── class-pdf-generator.php       # Generación de PDFs con TCPDF
│   ├── class-eventos-manager.php     # CRUD de eventos
│   ├── class-pestanas-manager.php    # CRUD de pestañas
│   ├── class-campos-manager.php      # CRUD de campos
│   ├── class-fuentes-manager.php     # CRUD de fuentes personalizadas
│   ├── class-shortcode.php           # Shortcode del formulario frontend
│   ├── class-sheets-cache-manager.php # Sistema de caché para Google Sheets
│   ├── class-column-mapper.php       # Mapeo dinámico de columnas
│   ├── class-survey-manager.php      # Sistema de encuestas de satisfacción
│   └── class-stats-manager.php       # Sistema de estadísticas de descargas
├── public/
│   ├── css/
│   │   └── public-style.css          # Estilos del frontend
│   └── js/
│       └── public-script.js          # Scripts del frontend
├── uploads/                          # Directorio de archivos subidos
│   ├── fonts/                        # Fuentes personalizadas (.ttf)
│   ├── plantillas/                   # Plantillas PDF (.pdf)
│   └── logos/                        # Logos de eventos
├── docs/                             # Documentación
│   └── Documentacion_Tecnica.md      # Este archivo
├── languages/                        # Archivos de traducción
└── certificados-digitales.php        # Archivo principal del plugin
```

### Convenciones de Nomenclatura

- **Clases:** `Certificados_Digitales_[Nombre]` (PascalCase con prefijo)
- **Funciones:** `certificados_[nombre]_[accion]` (snake_case)
- **Hooks:** `certificados_[contexto]_[accion]` (snake_case)
- **CSS:** `.certificados-[componente]-[elemento]` (kebab-case)
- **JS:** `certificados[Componente][Metodo]` (camelCase)
- **Tablas:** `certificados_[nombre]` (snake_case con prefijo)

---

## 2. Arquitectura del Plugin

### Patrón Arquitectónico: MVC Adaptado a WordPress

El plugin sigue una arquitectura Modelo-Vista-Controlador adaptada a la estructura de WordPress:

#### Modelo (Model)
- **Ubicación:** `/includes/*-manager.php`
- **Responsabilidad:** Lógica de negocio y acceso a datos
- **Ejemplos:**
  - `Certificados_Eventos_Manager`: CRUD de eventos
  - `Certificados_Pestanas_Manager`: CRUD de pestañas
  - `Certificados_Stats_Manager`: Consultas y análisis de estadísticas

#### Vista (View)
- **Ubicación:** Métodos `render_*_page()` en clases `/admin/class-admin*.php`
- **Responsabilidad:** Presentación HTML
- **Ejemplos:**
  - `Certificados_Admin::render_dashboard_page()`
  - `Certificados_Admin_Stats::render_page()`

#### Controlador (Controller)
- **Ubicación:** Handlers AJAX y métodos de procesamiento
- **Responsabilidad:** Lógica de aplicación, validación, orquestación
- **Ejemplos:**
  - `Certificados_Admin::ajax_crear_evento()`
  - `Certificados_Shortcode::procesar_formulario()`

### Flujo de Inicialización

```php
// certificados-digitales.php
register_activation_hook(__FILE__, 'certificados_activar_plugin');
register_deactivation_hook(__FILE__, 'certificados_desactivar_plugin');

function certificados_iniciar_plugin() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-autoloader.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-core.php';

    $plugin = new Certificados_Digitales_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'certificados_iniciar_plugin');
```

### Sistema de Autoload

El plugin utiliza un autoloader personalizado que sigue la convención PSR-4:

```php
// includes/class-autoloader.php
class Certificados_Digitales_Autoloader {
    public static function load($class_name) {
        $prefix = 'Certificados_Digitales_';

        if (strpos($class_name, $prefix) !== 0) {
            return;
        }

        $class_file = str_replace('_', '-', strtolower($class_name));
        $class_file = str_replace($prefix, '', $class_file);

        $paths = [
            CERTIFICADOS_DIGITALES_PATH . 'includes/class-' . $class_file . '.php',
            CERTIFICADOS_DIGITALES_PATH . 'admin/class-' . $class_file . '.php',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}
spl_autoload_register(['Certificados_Digitales_Autoloader', 'load']);
```

---

## 3. Base de Datos

### Esquema de Tablas

El plugin crea 8 tablas en la base de datos de WordPress:

#### 3.1. `certificados_eventos`

Almacena los eventos configurados (cada evento representa un tipo de certificado).

```sql
CREATE TABLE certificados_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    sheet_id VARCHAR(255) NOT NULL,
    sheet_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    logo_loader_url VARCHAR(500),
    url_encuesta VARCHAR(500),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_sheet_id (sheet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Campos clave:**
- `sheet_id`: ID del Google Sheet (extraído de la URL)
- `sheet_name`: Nombre de la hoja dentro del Sheet
- `activo`: 0 = Desactivado, 1 = Activo
- `logo_loader_url`: Logo que aparece mientras carga el formulario

#### 3.2. `certificados_pestanas`

Cada evento puede tener múltiples pestañas (diferentes plantillas de certificado).

```sql
CREATE TABLE certificados_pestanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    nombre_pestana VARCHAR(255) NOT NULL,
    plantilla_url VARCHAR(500),
    orden INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id),
    INDEX idx_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Relación:** Muchas pestañas → Un evento. Eliminación en cascada.

#### 3.3. `certificados_campos`

Configuración de campos dinámicos en cada pestaña (posición, tamaño, fuente).

```sql
CREATE TABLE certificados_campos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pestana_id INT NOT NULL,
    nombre_campo VARCHAR(100) NOT NULL,
    tipo_campo VARCHAR(50) NOT NULL,
    pos_x FLOAT NOT NULL,
    pos_y FLOAT NOT NULL,
    ancho FLOAT,
    alto FLOAT,
    font_family VARCHAR(100),
    font_size INT,
    color_hex VARCHAR(7),
    alineacion VARCHAR(20) DEFAULT 'left',
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pestana_id) REFERENCES certificados_pestanas(id) ON DELETE CASCADE,
    INDEX idx_pestana (pestana_id),
    INDEX idx_tipo (tipo_campo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Tipos de campo:** `text`, `qr`, `image`
**Posiciones:** En milímetros (mm) desde la esquina superior izquierda
**Alineación:** `left`, `center`, `right`, `justify`

#### 3.4. `certificados_fuentes`

Fuentes TTF personalizadas para usar en los certificados.

```sql
CREATE TABLE certificados_fuentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    archivo_ttf VARCHAR(255) NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Nota:** Solo se aceptan archivos `.ttf`. TCPDF no soporta OTF o WOFF.

#### 3.5. `certificados_column_mapping`

Mapeo dinámico entre columnas de Google Sheets y campos del sistema.

```sql
CREATE TABLE certificados_column_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    campo_sistema VARCHAR(100) NOT NULL,
    columna_sheet VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (evento_id, campo_sistema),
    INDEX idx_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Ejemplo de mapeo:**
| campo_sistema | columna_sheet |
|---------------|---------------|
| nombre | Nombre Completo |
| numero_documento | Número de Cédula |
| tipo_documento | Tipo ID |

#### 3.6. `certificados_surveys`

Configuración de encuestas de satisfacción por evento.

```sql
CREATE TABLE certificados_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    obligatoria TINYINT(1) DEFAULT 0,
    tipo_apertura ENUM('modal', 'nueva_ventana') DEFAULT 'modal',
    url_encuesta VARCHAR(500) NOT NULL,
    sheet_id_respuestas VARCHAR(255),
    columna_validacion VARCHAR(100),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE,
    INDEX idx_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Tipos de apertura:**
- `modal`: Iframe dentro de modal lightbox
- `nueva_ventana`: Se abre en nueva pestaña del navegador

#### 3.7. `certificados_stats`

Registro de cada descarga de certificado para estadísticas.

```sql
CREATE TABLE certificados_stats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    pestana_id INT,
    numero_documento VARCHAR(100),
    fecha_descarga DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_usuario VARCHAR(45),
    user_agent TEXT,
    INDEX idx_evento (evento_id),
    INDEX idx_fecha (fecha_descarga),
    INDEX idx_documento (numero_documento),
    INDEX idx_evento_fecha (evento_id, fecha_descarga),
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Índices optimizados para:**
- Consultas por evento
- Consultas por rango de fechas
- Búsqueda de documentos específicos
- Queries combinadas evento + fecha (más eficiente)

#### 3.8. `certificados_sheets_cache`

Sistema de caché para evitar llamadas excesivas a Google Sheets API.

```sql
CREATE TABLE certificados_sheets_cache (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sheet_id VARCHAR(255) NOT NULL,
    sheet_name VARCHAR(255) NOT NULL,
    numero_documento VARCHAR(100) NOT NULL,
    cached_data TEXT,
    data_hash VARCHAR(32),
    fecha_cache DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME,
    UNIQUE KEY unique_cache (sheet_id, sheet_name, numero_documento),
    INDEX idx_expiracion (fecha_expiracion),
    INDEX idx_hash (data_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Mecanismo de caché:**
1. Se calcula un hash MD5 de los datos
2. Al consultar, se compara el hash actual con el almacenado
3. Si difieren, se actualiza el caché automáticamente
4. Expiración predeterminada: 1 hora (filtrable)

### Diagrama de Relaciones

```
certificados_eventos (1) ──┬─> (N) certificados_pestanas
                           ├─> (N) certificados_column_mapping
                           ├─> (1) certificados_surveys
                           └─> (N) certificados_stats

certificados_pestanas (1) ───> (N) certificados_campos
```

**Integridad referencial:**
Todas las relaciones usan `ON DELETE CASCADE`, por lo que al eliminar un evento se eliminan automáticamente todas sus entidades relacionadas.

---

## 4. Clases Principales

### 4.1. `Certificados_Digitales_Core`

**Ubicación:** `includes/class-core.php`

Clase principal que inicializa el plugin y registra todos los hooks.

```php
class Certificados_Digitales_Core {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'certificados-digitales';
        $this->version = CERTIFICADOS_DIGITALES_VERSION;

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Cargar todas las clases necesarias
        require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-eventos-manager.php';
        require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-pestanas-manager.php';
        // ... más clases
    }

    private function define_admin_hooks() {
        $admin = new Certificados_Admin();
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        // ... más hooks
    }

    public function run() {
        // Iniciar el plugin
    }
}
```

### 4.2. `Certificados_Google_Sheets`

**Ubicación:** `includes/class-google-sheets.php`

Wrapper para Google Sheets API v4. Gestiona la comunicación con Google.

```php
class Certificados_Google_Sheets {
    private $api_key;
    private $sheet_id;

    public function __construct($sheet_id) {
        $this->sheet_id = $sheet_id;
        $this->api_key = get_option('certificados_google_api_key');
    }

    /**
     * Obtiene todos los datos de una hoja
     */
    public function get_sheet_data($sheet_name) {
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
            $this->sheet_id,
            urlencode($sheet_name),
            $this->api_key
        );

        $response = wp_remote_get($url, array('timeout' => 15));

        if (is_wp_error($response)) {
            return new WP_Error('sheets_api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['error'])) {
            return new WP_Error('sheets_api_error', $data['error']['message']);
        }

        return $data['values'] ?? array();
    }

    /**
     * Busca un registro por número de documento
     */
    public function buscar_por_documento($sheet_name, $numero_documento, $columna_documento = 'A') {
        $data = $this->get_sheet_data($sheet_name);

        if (is_wp_error($data)) {
            return $data;
        }

        return $this->buscar_en_datos($data, $numero_documento, $columna_documento);
    }

    /**
     * Valida que la conexión funcione
     */
    public function validar_conexion($sheet_name) {
        $result = $this->get_sheet_data($sheet_name);
        return !is_wp_error($result);
    }
}
```

**Métodos clave:**
- Usa HTTP API de WordPress (`wp_remote_get`), no la librería PHP de Google
- Implementa retry logic para timeouts
- Cachea respuestas usando `Certificados_Sheets_Cache_Manager`

### 4.3. `Certificados_PDF_Generator`

**Ubicación:** `includes/class-pdf-generator.php`

Genera PDFs usando TCPDF. Aplica plantillas y dibuja campos dinámicos.

```php
class Certificados_PDF_Generator {
    private $pdf;

    public function generar_certificado($pestana_id, $datos_usuario) {
        try {
            // 1. Inicializar TCPDF
            $this->inicializar_pdf();

            // 2. Cargar plantilla como fondo
            $this->cargar_plantilla($pestana_id);

            // 3. Obtener configuración de campos
            $campos = $this->obtener_campos($pestana_id);

            // 4. Dibujar cada campo
            foreach ($campos as $campo) {
                $this->dibujar_campo($campo, $datos_usuario);
            }

            // 5. Generar código QR si existe
            if ($this->tiene_campo_qr($campos)) {
                $this->generar_qr($datos_usuario);
            }

            // 6. Output del PDF
            return $this->pdf->Output('S'); // String output

        } catch (Exception $e) {
            return new WP_Error('pdf_generation_error', $e->getMessage());
        }
    }

    private function dibujar_campo($campo, $datos) {
        switch ($campo['tipo_campo']) {
            case 'text':
                $this->dibujar_texto($campo, $datos);
                break;
            case 'qr':
                $this->dibujar_qr($campo, $datos);
                break;
            case 'image':
                $this->dibujar_imagen($campo, $datos);
                break;
        }
    }

    private function dibujar_texto($campo, $datos) {
        // Configurar fuente
        if ($campo['font_family']) {
            $font_path = $this->obtener_ruta_fuente($campo['font_family']);
            $font_name = TCPDF_FONTS::addTTFfont($font_path);
            $this->pdf->SetFont($font_name, '', $campo['font_size']);
        }

        // Configurar color
        if ($campo['color_hex']) {
            $rgb = $this->hex_to_rgb($campo['color_hex']);
            $this->pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
        }

        // Dibujar texto
        $this->pdf->SetXY($campo['pos_x'], $campo['pos_y']);
        $this->pdf->Cell(
            $campo['ancho'],
            $campo['alto'],
            $datos[$campo['nombre_campo']],
            0,
            0,
            $campo['alineacion']
        );
    }
}
```

**Proceso interno:**
1. Carga plantilla PDF como fondo usando `setSourceFile()`
2. Lee configuración de campos desde BD
3. Dibuja textos con fuentes personalizadas y colores
4. Genera código QR de validación con URL única
5. Retorna PDF como string o envía headers de descarga

### 4.4. `Certificados_Sheets_Cache_Manager`

**Ubicación:** `includes/class-sheets-cache-manager.php`

Sistema de caché con detección de cambios en Google Sheets.

```php
class Certificados_Sheets_Cache_Manager {

    /**
     * Obtiene datos del caché si existen y no han expirado
     */
    public function get_cached_data($sheet_id, $sheet_name, $numero_documento) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_sheets_cache';

        $cache = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table
             WHERE sheet_id = %s
             AND sheet_name = %s
             AND numero_documento = %s
             AND fecha_expiracion > NOW()",
            $sheet_id, $sheet_name, $numero_documento
        ));

        if ($cache) {
            return json_decode($cache->cached_data, true);
        }

        return null;
    }

    /**
     * Guarda datos en caché con hash para detectar cambios
     */
    public function save_to_cache($sheet_id, $sheet_name, $numero_documento, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_sheets_cache';

        $data_json = json_encode($data);
        $data_hash = md5($data_json);

        // Tiempo de expiración (filtrable)
        $expiration_seconds = apply_filters('certificados_cache_expiration', 3600); // 1 hora

        $wpdb->replace($table, array(
            'sheet_id' => $sheet_id,
            'sheet_name' => $sheet_name,
            'numero_documento' => $numero_documento,
            'cached_data' => $data_json,
            'data_hash' => $data_hash,
            'fecha_cache' => current_time('mysql'),
            'fecha_expiracion' => date('Y-m-d H:i:s', time() + $expiration_seconds)
        ));
    }

    /**
     * Detecta si los datos han cambiado comparando hashes
     */
    public function detect_changes($sheet_id, $sheet_name, $numero_documento, $new_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_sheets_cache';

        $cache = $wpdb->get_row($wpdb->prepare(
            "SELECT data_hash FROM $table
             WHERE sheet_id = %s
             AND sheet_name = %s
             AND numero_documento = %s",
            $sheet_id, $sheet_name, $numero_documento
        ));

        if (!$cache) {
            return true; // No hay caché, considerar cambio
        }

        $new_hash = md5(json_encode($new_data));
        return $cache->data_hash !== $new_hash;
    }

    /**
     * Limpia caché expirado (se ejecuta vía cron)
     */
    public function clear_expired_cache() {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_sheets_cache';

        $wpdb->query("DELETE FROM $table WHERE fecha_expiracion < NOW()");
    }
}
```

**Lógica de cambios:**
- Calcula hash MD5 de los datos JSON
- Compara con hash almacenado en caché
- Si difieren, actualiza caché y retorna los nuevos datos
- Limpieza automática vía WP-Cron cada 24 horas

### 4.5. `Certificados_Column_Mapper`

**Ubicación:** `includes/class-column-mapper.php`

Permite mapear dinámicamente columnas de Sheets a campos del sistema.

```php
class Certificados_Column_Mapper {

    /**
     * Obtiene el mapeo guardado para un evento
     */
    public function get_mapping($evento_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_column_mapping';

        $mappings = $wpdb->get_results($wpdb->prepare(
            "SELECT campo_sistema, columna_sheet FROM $table WHERE evento_id = %d",
            $evento_id
        ), ARRAY_A);

        $result = array();
        foreach ($mappings as $map) {
            $result[$map['campo_sistema']] = $map['columna_sheet'];
        }

        return $result;
    }

    /**
     * Guarda el mapeo de columnas
     */
    public function save_mapping($evento_id, $mappings) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_column_mapping';

        // Limpiar mapeos existentes
        $wpdb->delete($table, array('evento_id' => $evento_id));

        // Insertar nuevos mapeos
        foreach ($mappings as $campo_sistema => $columna_sheet) {
            $wpdb->insert($table, array(
                'evento_id' => $evento_id,
                'campo_sistema' => $campo_sistema,
                'columna_sheet' => $columna_sheet
            ));
        }

        do_action('certificados_column_mapping_saved', $evento_id, $mappings);
    }

    /**
     * Aplica el mapeo a los datos crudos de Google Sheets
     */
    public function apply_mapping($evento_id, $sheet_data) {
        $mapping = $this->get_mapping($evento_id);

        if (empty($mapping) || empty($sheet_data)) {
            return $sheet_data;
        }

        // Primera fila son los headers
        $headers = array_shift($sheet_data);

        // Crear índice de columnas
        $column_index = array();
        foreach ($headers as $index => $header) {
            $column_index[$header] = $index;
        }

        // Transformar datos según mapeo
        $resultado = array();
        foreach ($sheet_data as $row) {
            $mapped_row = array();
            foreach ($mapping as $campo_sistema => $columna_sheet) {
                if (isset($column_index[$columna_sheet])) {
                    $col_index = $column_index[$columna_sheet];
                    $mapped_row[$campo_sistema] = $row[$col_index] ?? '';
                }
            }
            $resultado[] = $mapped_row;
        }

        return $resultado;
    }

    /**
     * Campos del sistema disponibles para mapeo
     */
    public function get_system_fields() {
        $fields = array(
            'numero_documento' => 'Número de Documento',
            'tipo_documento' => 'Tipo de Documento',
            'nombre' => 'Nombre Completo',
            'tipo_trabajo' => 'Tipo de Trabajo',
            'ciudad_expedicion' => 'Ciudad de Expedición',
            'fecha_expedicion' => 'Fecha de Expedición'
        );

        return apply_filters('certificados_system_fields', $fields);
    }
}
```

**Ejemplo de uso:**
```php
$mapper = new Certificados_Column_Mapper();

// Guardar mapeo
$mapper->save_mapping(1, array(
    'numero_documento' => 'Cédula',
    'nombre' => 'Nombre Completo',
    'tipo_documento' => 'Tipo ID'
));

// Aplicar mapeo a datos del sheet
$datos_crudos = array(
    array('Cédula', 'Nombre Completo', 'Tipo ID'),
    array('123456', 'Juan Pérez', 'CC'),
    array('789012', 'María García', 'CE')
);

$datos_mapeados = $mapper->apply_mapping(1, $datos_crudos);
// Resultado:
// [
//   ['numero_documento' => '123456', 'nombre' => 'Juan Pérez', 'tipo_documento' => 'CC'],
//   ['numero_documento' => '789012', 'nombre' => 'María García', 'tipo_documento' => 'CE']
// ]
```

### 4.6. `Certificados_Survey_Manager`

**Ubicación:** `includes/class-survey-manager.php`

Gestión de encuestas de satisfacción integradas al flujo de descarga.

```php
class Certificados_Survey_Manager {

    /**
     * Obtiene la configuración de encuesta de un evento
     */
    public function get_survey_by_event($evento_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_surveys';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE evento_id = %d",
            $evento_id
        ), ARRAY_A);
    }

    /**
     * Determina si debe mostrarse la encuesta
     */
    public function should_show_survey($evento_id) {
        $survey = $this->get_survey_by_event($evento_id);

        return $survey && $survey['activa'] == 1;
    }

    /**
     * Renderiza el modal de encuesta
     */
    public function render_survey_modal($survey_config) {
        if ($survey_config['tipo_apertura'] === 'modal') {
            ?>
            <div id="survey-modal" class="survey-modal">
                <div class="survey-modal-content">
                    <span class="survey-modal-close">&times;</span>
                    <iframe
                        src="<?php echo esc_url($survey_config['url_encuesta']); ?>"
                        width="100%"
                        height="600px"
                        frameborder="0">
                    </iframe>
                </div>
            </div>
            <?php
        } else {
            // Nueva ventana
            ?>
            <script>
            window.open('<?php echo esc_js($survey_config['url_encuesta']); ?>', '_blank');
            </script>
            <?php
        }
    }

    /**
     * Valida si el usuario completó la encuesta (si es obligatoria)
     */
    public function validate_survey_completion($evento_id, $numero_documento) {
        $survey = $this->get_survey_by_event($evento_id);

        if (!$survey || $survey['obligatoria'] != 1) {
            return true; // No es obligatoria o no existe
        }

        // Verificar si existe respuesta en el Sheet de respuestas
        if (empty($survey['sheet_id_respuestas']) || empty($survey['columna_validacion'])) {
            return true; // No hay validación configurada
        }

        $sheets = new Certificados_Google_Sheets($survey['sheet_id_respuestas']);
        $respuestas = $sheets->get_sheet_data('Respuestas');

        // Buscar el documento en las respuestas
        foreach ($respuestas as $row) {
            if (in_array($numero_documento, $row)) {
                return true; // Encontró respuesta
            }
        }

        return false; // No ha completado la encuesta
    }
}
```

**Tipos de apertura:**
- `modal`: Iframe dentro de modal lightbox, bloquea interacción con página
- `nueva_ventana`: Se abre en pestaña separada con `target="_blank"`

**Si es obligatoria:**
- Bloquea botón de descarga hasta completar
- Valida automáticamente contra Sheet de respuestas
- Muestra mensaje de "Debes completar la encuesta"

### 4.7. `Certificados_Stats_Manager`

**Ubicación:** `includes/class-stats-manager.php`

Sistema completo de estadísticas y analytics de descargas.

```php
class Certificados_Stats_Manager {

    /**
     * Registra una nueva descarga
     */
    public function register_download($evento_id, $pestana_id, $numero_documento) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_stats';

        $wpdb->insert($table, array(
            'evento_id' => $evento_id,
            'pestana_id' => $pestana_id,
            'numero_documento' => $numero_documento,
            'fecha_descarga' => current_time('mysql'),
            'ip_usuario' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));

        $stat_id = $wpdb->insert_id;
        do_action('certificados_download_registered', $stat_id, $evento_id);

        return $stat_id;
    }

    /**
     * Obtiene estadísticas generales
     */
    public function get_overview_stats($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_stats';

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $stats = array(
            'total_descargas' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'total_usuarios_unicos' => $wpdb->get_var(
                "SELECT COUNT(DISTINCT numero_documento) FROM $table"
            ),
            'descargas_periodo' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE fecha_descarga >= %s",
                $date_limit
            )),
            'descargas_hoy' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE DATE(fecha_descarga) = %s",
                current_time('Y-m-d')
            ))
        );

        // Calcular promedio diario
        if ($days > 0) {
            $stats['promedio_diario'] = round($stats['descargas_periodo'] / $days, 2);
        }

        return $stats;
    }

    /**
     * Obtiene timeline de descargas (para gráficos)
     */
    public function get_timeline_stats($days = 30, $group_by = 'day') {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_stats';

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $date_format = match($group_by) {
            'hour' => '%Y-%m-%d %H:00:00',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(fecha_descarga, %s) as periodo, COUNT(*) as total
             FROM $table
             WHERE fecha_descarga >= %s
             GROUP BY periodo
             ORDER BY periodo ASC",
            $date_format, $date_limit
        ), ARRAY_A);

        return $results;
    }

    /**
     * Obtiene estadísticas por evento
     */
    public function get_stats_by_event($days = 30) {
        global $wpdb;
        $stats_table = $wpdb->prefix . 'certificados_stats';
        $eventos_table = $wpdb->prefix . 'certificados_eventos';

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT e.nombre, COUNT(s.id) as total_descargas,
                    COUNT(DISTINCT s.numero_documento) as usuarios_unicos
             FROM $eventos_table e
             LEFT JOIN $stats_table s ON e.id = s.evento_id AND s.fecha_descarga >= %s
             WHERE e.activo = 1
             GROUP BY e.id
             ORDER BY total_descargas DESC",
            $date_limit
        ), ARRAY_A);

        return $results;
    }

    /**
     * Obtiene top certificados más descargados
     */
    public function get_top_downloads($days = 30, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_stats';

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT numero_documento, COUNT(*) as total_descargas,
                    MAX(fecha_descarga) as ultima_descarga
             FROM $table
             WHERE fecha_descarga >= %s
             GROUP BY numero_documento
             ORDER BY total_descargas DESC
             LIMIT %d",
            $date_limit, $limit
        ), ARRAY_A);

        return $results;
    }

    /**
     * Exporta estadísticas a CSV
     */
    public function export_to_csv($days = 30, $evento_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'certificados_stats';

        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));

        $where = "WHERE fecha_descarga >= %s";
        $params = array($date_limit);

        if ($evento_id) {
            $where .= " AND evento_id = %d";
            $params[] = $evento_id;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY fecha_descarga DESC",
            $params
        ), ARRAY_A);

        // Generar CSV
        $filename = 'estadisticas_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Headers
        if (!empty($results)) {
            fputcsv($output, array_keys($results[0]));
        }

        // Datos
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Obtiene IP del usuario de forma segura
     */
    private function get_user_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return sanitize_text_field($ip);
    }
}
```

**Métricas calculadas:**
- Total descargas
- Usuarios únicos (por número de documento)
- Descargas hoy
- Descargas en período (últimos X días)
- Promedio diario
- Tendencias por día/semana/mes
- Top documentos más descargados
- Estadísticas por evento

---

## 5. Flujo de Funcionamiento

### 5.1. Proceso Completo de Descarga de Certificado

```
┌────────────────────────────────────────────────────────────────┐
│ 1. USUARIO INGRESA DATOS EN EL FORMULARIO                     │
│    - Tipo de documento                                          │
│    - Número de documento                                        │
│    - Otros campos configurados                                  │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 2. VALIDACIÓN FRONTEND (JavaScript)                            │
│    - Campos obligatorios completos                             │
│    - Formato de número de documento                            │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 3. AJAX REQUEST A WORDPRESS                                    │
│    - Endpoint: admin-ajax.php                                  │
│    - Action: certificados_buscar_certificado                   │
│    - Datos: evento_id, tipo_doc, numero_doc                    │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 4. APLICAR MAPEO DE COLUMNAS                                   │
│    Certificados_Column_Mapper::get_mapping($evento_id)        │
│    Traduce campos del sistema a nombres de columnas del Sheet │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 5. CONSULTA A CACHÉ                                            │
│    Certificados_Sheets_Cache_Manager::get_cached_data()       │
│    ┌─────────────┬──────────────────────────────────────┐    │
│    │ ¿Existe?    │                                       │    │
│    ├─────────────┴──────────────────────────────────────┤    │
│    │ ✓ Sí → Verifica hash MD5 para detectar cambios    │    │
│    │        Si cambió → Actualiza caché                 │    │
│    │ ✗ No → Continúa a paso 6                           │    │
│    └────────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 6. CONSULTA GOOGLE SHEETS API                                  │
│    Certificados_Google_Sheets::buscar_por_documento()         │
│    GET https://sheets.googleapis.com/v4/spreadsheets/...      │
│    - Timeout: 15 segundos                                      │
│    - Retry: 2 intentos si falla                                │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 7. PROCESAR DATOS                                              │
│    - Buscar fila con número de documento                       │
│    - Aplicar mapeo de columnas                                 │
│    - Guardar en caché con hash MD5                             │
│    - Preparar datos para certificado                           │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 8. ¿HAY ENCUESTA CONFIGURADA?                                  │
│    Certificados_Survey_Manager::get_survey_by_event()         │
│    ┌─────────────────────────────────────────────────────┐   │
│    │ Obligatoria → Bloquear descarga hasta completar     │   │
│    │ Opcional → Mostrar pero permitir cerrar             │   │
│    │ Deshabilitada → Continuar directamente              │   │
│    └─────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 9. ¿HAY MÚLTIPLES PESTAÑAS?                                    │
│    ┌─────────────────────────────────────────────────────┐   │
│    │ Sí → Mostrar tarjetas de selección                  │   │
│    │      Usuario elige qué certificado descargar        │   │
│    │ No → Usar única pestaña automáticamente             │   │
│    └─────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 10. GENERAR PDF                                                 │
│     Certificados_PDF_Generator::generar_certificado()         │
│     ┌──────────────────────────────────────────────────┐     │
│     │ a) Cargar plantilla PDF como fondo               │     │
│     │ b) Leer configuración de campos de BD            │     │
│     │ c) Dibujar textos con TCPDF:                     │     │
│     │    - Fuentes personalizadas TTF                  │     │
│     │    - Colores hex convertidos a RGB               │     │
│     │    - Posicionamiento en milímetros               │     │
│     │ d) Generar código QR de validación               │     │
│     │    - URL con número de documento encriptado      │     │
│     │ e) Output como string o descarga directa         │     │
│     └──────────────────────────────────────────────────┘     │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 11. REGISTRAR ESTADÍSTICA                                       │
│     Certificados_Stats_Manager::register_download()           │
│     INSERT INTO certificados_stats                            │
│     - evento_id, pestana_id, numero_documento                 │
│     - fecha_descarga, IP, user_agent                          │
└────────────────────────────────────────────────────────────────┘
                            ↓
┌────────────────────────────────────────────────────────────────┐
│ 12. ENVIAR PDF AL USUARIO                                       │
│     - Headers: Content-Type: application/pdf                  │
│     - Content-Disposition: attachment                         │
│     - Filename: Certificado_[Evento]_[Documento].pdf          │
│     - Output: PDF binary data                                 │
└────────────────────────────────────────────────────────────────┘
```

### 5.2. Diagrama de Flujo de Datos (Simplificado)

```
┌─────────────────┐
│  Usuario Web    │
└────────┬────────┘
         │ 1. Ingresa documento
         ▼
┌─────────────────────────┐
│  Shortcode Frontend     │ (includes/class-shortcode.php)
└────────┬────────────────┘
         │ 2. AJAX Request
         ▼
┌─────────────────────────┐
│  Column Mapper          │ (includes/class-column-mapper.php)
└────────┬────────────────┘
         │ 3. Mapear campos
         ▼
┌─────────────────────────┐
│  Cache Manager          │ (includes/class-sheets-cache-manager.php)
└────────┬────────────────┘
         │ 4. ¿Cache válido?
         │    No ↓    Sí → Retornar datos
         ▼
┌─────────────────────────┐
│  Google Sheets API      │ (includes/class-google-sheets.php)
└────────┬────────────────┘
         │ 5. Obtener datos
         ▼
┌─────────────────────────┐
│  Survey Manager         │ (includes/class-survey-manager.php)
└────────┬────────────────┘
         │ 6. ¿Mostrar encuesta?
         ▼
┌─────────────────────────┐
│  PDF Generator          │ (includes/class-pdf-generator.php)
└────────┬────────────────┘
         │ 7. Generar certificado
         ▼
┌─────────────────────────┐
│  Stats Manager          │ (includes/class-stats-manager.php)
└────────┬────────────────┘
         │ 8. Registrar descarga
         ▼
┌─────────────────┐
│  Descarga PDF   │
└─────────────────┘
```

---

## 6. Hooks y Filtros Disponibles

### 6.1. Actions (Acciones)

#### `certificados_digitales_activated`
**Descripción:** Se ejecuta después de activar el plugin
**Parámetros:** Ninguno
**Uso:** Ejecutar tareas de inicialización personalizadas

```php
add_action('certificados_digitales_activated', function() {
    // Crear páginas personalizadas, configuraciones iniciales, etc.
    error_log('Plugin Certificados Digitales activado');
});
```

#### `certificados_before_pdf_generation`
**Descripción:** Se ejecuta antes de generar el PDF
**Parámetros:**
- `$pestana_id` (int): ID de la pestaña
- `$datos` (array): Datos del usuario

```php
add_action('certificados_before_pdf_generation', function($pestana_id, $datos) {
    // Logging, validaciones adicionales, etc.
    error_log("Generando PDF para documento: " . $datos['numero_documento']);
}, 10, 2);
```

#### `certificados_after_pdf_generation`
**Descripción:** Se ejecuta después de generar el PDF
**Parámetros:**
- `$pestana_id` (int): ID de la pestaña
- `$datos` (array): Datos del usuario
- `$pdf_path` (string): Ruta del PDF generado (si se guardó)

```php
add_action('certificados_after_pdf_generation', function($pestana_id, $datos, $pdf_path) {
    // Enviar notificación, copiar PDF a otra ubicación, etc.
    wp_mail($datos['email'], 'Tu certificado está listo', 'Descarga tu certificado adjunto');
}, 10, 3);
```

#### `certificados_download_registered`
**Descripción:** Se ejecuta al registrar una descarga en estadísticas
**Parámetros:**
- `$stat_id` (int): ID del registro de estadística
- `$evento_id` (int): ID del evento

```php
add_action('certificados_download_registered', function($stat_id, $evento_id) {
    // Enviar email de notificación al admin
    $admin_email = get_option('admin_email');
    wp_mail($admin_email, 'Nueva descarga de certificado', "Evento ID: $evento_id");
}, 10, 2);
```

#### `certificados_column_mapping_saved`
**Descripción:** Se ejecuta al guardar el mapeo de columnas
**Parámetros:**
- `$evento_id` (int): ID del evento
- `$mappings` (array): Array asociativo de mapeos

```php
add_action('certificados_column_mapping_saved', function($evento_id, $mappings) {
    // Limpiar caché relacionado al evento
    Certificados_Sheets_Cache_Manager::clear_cache_by_event($evento_id);
}, 10, 2);
```

### 6.2. Filters (Filtros)

#### `certificados_pdf_data`
**Descripción:** Modifica los datos antes de usarlos en el PDF
**Parámetros:**
- `$datos` (array): Datos del usuario
- `$pestana_id` (int): ID de la pestaña

**Retorno:** Array de datos modificados

```php
add_filter('certificados_pdf_data', function($datos, $pestana_id) {
    // Agregar campo personalizado
    $datos['campo_extra'] = 'Valor personalizado';

    // Formatear nombre en mayúsculas
    $datos['nombre'] = strtoupper($datos['nombre']);

    return $datos;
}, 10, 2);
```

#### `certificados_qr_url`
**Descripción:** Modifica la URL del código QR de validación
**Parámetros:**
- `$url` (string): URL generada automáticamente
- `$numero_documento` (string): Número de documento

**Retorno:** String con la URL modificada

```php
add_filter('certificados_qr_url', function($url, $numero_documento) {
    // Usar dominio personalizado para verificación
    return 'https://verificacion.midominio.com/validar?doc=' . $numero_documento;
}, 10, 2);
```

#### `certificados_cache_expiration`
**Descripción:** Modifica el tiempo de expiración del caché (en segundos)
**Parámetros:** Ninguno (solo retorna)
**Retorno:** Entero con los segundos de expiración

```php
add_filter('certificados_cache_expiration', function($seconds) {
    // Cambiar de 1 hora a 2 horas
    return 7200;
});
```

#### `certificados_system_fields`
**Descripción:** Modifica los campos del sistema disponibles para mapeo
**Parámetros:**
- `$campos` (array): Array asociativo de campos

**Retorno:** Array de campos modificado

```php
add_filter('certificados_system_fields', function($campos) {
    // Agregar campos personalizados
    $campos['email'] = 'Email del Participante';
    $campos['telefono'] = 'Teléfono';
    $campos['empresa'] = 'Empresa';

    return $campos;
});
```

#### `certificados_stats_query`
**Descripción:** Modifica la consulta SQL de estadísticas
**Parámetros:**
- `$query` (string): Query SQL
- `$params` (array): Parámetros preparados

**Retorno:** String con la query modificada

```php
add_filter('certificados_stats_query', function($query, $params) {
    // Agregar filtro adicional
    $query .= " AND DATE(fecha_descarga) = CURDATE()";
    return $query;
}, 10, 2);
```

#### `certificados_pdf_filename`
**Descripción:** Modifica el nombre del archivo PDF descargado
**Parámetros:**
- `$filename` (string): Nombre generado automáticamente
- `$datos` (array): Datos del usuario

**Retorno:** String con el nombre del archivo

```php
add_filter('certificados_pdf_filename', function($filename, $datos) {
    // Formato: Certificado_NOMBRE_FECHA.pdf
    $nombre_sanitizado = sanitize_file_name($datos['nombre']);
    return "Certificado_{$nombre_sanitizado}_" . date('Y-m-d') . ".pdf";
}, 10, 2);
```

### 6.3. Ejemplos Avanzados de Uso de Hooks

#### Ejemplo 1: Sistema de Notificaciones por Email

```php
// functions.php del tema

// Enviar email al usuario después de descargar
add_action('certificados_after_pdf_generation', function($pestana_id, $datos) {
    if (isset($datos['email']) && is_email($datos['email'])) {
        $subject = 'Tu certificado ha sido generado';
        $message = "Hola {$datos['nombre']},\n\n";
        $message .= "Tu certificado ha sido generado exitosamente.\n";
        $message .= "Fecha: " . date('d/m/Y H:i') . "\n\n";
        $message .= "Gracias por participar.";

        wp_mail($datos['email'], $subject, $message);
    }
}, 10, 2);

// Notificar al admin cada 10 descargas
add_action('certificados_download_registered', function($stat_id, $evento_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'certificados_stats';

    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");

    if ($total % 10 === 0) {
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            "Milestone: $total descargas alcanzadas",
            "El sistema ha registrado $total descargas totales."
        );
    }
}, 10, 2);
```

#### Ejemplo 2: Integración con CRM Externo

```php
// Enviar datos a CRM al descargar certificado
add_action('certificados_download_registered', function($stat_id, $evento_id) {
    global $wpdb;

    // Obtener datos completos de la descarga
    $stat = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}certificados_stats WHERE id = %d",
        $stat_id
    ));

    // Enviar a CRM vía API
    wp_remote_post('https://api.micrm.com/contacts', array(
        'body' => json_encode(array(
            'documento' => $stat->numero_documento,
            'evento' => $evento_id,
            'fecha_descarga' => $stat->fecha_descarga,
            'origen' => 'certificados_plugin'
        )),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('crm_api_key')
        )
    ));
}, 10, 2);
```

#### Ejemplo 3: Campos Dinámicos Calculados

```php
// Agregar campo de edad calculado a partir de fecha de nacimiento
add_filter('certificados_pdf_data', function($datos, $pestana_id) {
    if (isset($datos['fecha_nacimiento'])) {
        $fecha_nac = new DateTime($datos['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;

        $datos['edad'] = $edad;
    }

    // Generar código de verificación único
    $datos['codigo_verificacion'] = strtoupper(substr(md5($datos['numero_documento'] . time()), 0, 8));

    return $datos;
}, 10, 2);
```

#### Ejemplo 4: Validación Adicional Antes de Generar PDF

```php
// Validar que el usuario haya pagado antes de generar certificado
add_action('certificados_before_pdf_generation', function($pestana_id, $datos) {
    // Verificar en tabla de pagos
    global $wpdb;

    $pago = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM wp_pagos WHERE documento = %s AND estado = 'completado'",
        $datos['numero_documento']
    ));

    if (!$pago) {
        wp_die('No se puede generar el certificado. Pago pendiente.');
    }
}, 10, 2);
```

---

## 7. API de Google Sheets

### 7.1. Configuración de la API

Para usar Google Sheets API v4, necesitas:

1. **Crear proyecto en Google Cloud Console**
   - Ve a https://console.cloud.google.com
   - Crear nuevo proyecto
   - Habilitar "Google Sheets API"

2. **Generar API Key**
   - En "Credenciales" → "Crear credenciales" → "Clave de API"
   - Copiar la clave generada
   - (Opcional) Restringir la clave a solo Google Sheets API

3. **Hacer el Sheet público**
   - Compartir el Google Sheet
   - Cambiar a "Cualquier persona con el enlace puede ver"

### 7.2. Endpoints Utilizados

El plugin utiliza el endpoint de valores de Google Sheets API v4:

```
GET https://sheets.googleapis.com/v4/spreadsheets/{spreadsheetId}/values/{range}?key={apiKey}
```

**Parámetros:**
- `spreadsheetId`: ID del Google Sheet (extraído de la URL)
- `range`: Nombre de la hoja (ej: "Participantes", "Sheet1")
- `apiKey`: Tu API Key de Google Cloud

**Ejemplo de respuesta:**
```json
{
  "range": "Participantes!A1:Z1000",
  "majorDimension": "ROWS",
  "values": [
    ["Nombre", "Documento", "Email", "Fecha"],
    ["Juan Pérez", "123456789", "juan@email.com", "2024-01-15"],
    ["María García", "987654321", "maria@email.com", "2024-01-16"]
  ]
}
```

### 7.3. Límites y Cuotas

**Límites de Google Sheets API (capa gratuita):**
- 100 solicitudes por 100 segundos por usuario
- 500 solicitudes por 100 segundos por proyecto

**Mitigación implementada:**
- Sistema de caché que reduce llamadas API en ~80%
- Timeout de 15 segundos para evitar bloqueos
- Retry logic con backoff exponencial

---

## 8. Sistema de Caché

### 8.1. Estrategia de Caché

El plugin implementa un sistema de caché inteligente con las siguientes características:

**Almacenamiento:**
- Tabla dedicada: `certificados_sheets_cache`
- Datos en formato JSON
- Hash MD5 para detectar cambios

**Expiración:**
- Predeterminado: 1 hora
- Configurable vía filtro `certificados_cache_expiration`
- Limpieza automática vía WP-Cron cada 24 horas

**Detección de cambios:**
- Cada consulta calcula hash MD5 de los datos
- Se compara con hash almacenado
- Si difiere, actualiza automáticamente el caché

### 8.2. Limpiar Caché Manualmente

```php
// Limpiar caché de un documento específico
global $wpdb;
$table = $wpdb->prefix . 'certificados_sheets_cache';
$wpdb->delete($table, array(
    'sheet_id' => 'ABC123',
    'numero_documento' => '123456789'
));

// Limpiar todo el caché expirado
$cache_manager = new Certificados_Sheets_Cache_Manager();
$cache_manager->clear_expired_cache();

// Limpiar todo el caché de un evento
$wpdb->delete($table, array('sheet_id' => 'ABC123'));
```

---

## 9. Generación de PDFs

### 9.1. TCPDF: Configuración y Uso

El plugin utiliza TCPDF (incluido en WordPress) para generar PDFs.

**Características utilizadas:**
- `setSourceFile()`: Importar PDF existente como plantilla
- `useTemplate()`: Usar plantilla como fondo
- `SetFont()`: Aplicar fuentes TTF personalizadas
- `SetTextColor()`: Aplicar colores hex
- `Cell()` y `MultiCell()`: Dibujar textos
- `write2DBarcode()`: Generar códigos QR

### 9.2. Fuentes Personalizadas

**Agregar fuente TTF:**
```php
$font_path = WP_CONTENT_DIR . '/uploads/certificados/fonts/MiFuente.ttf';
$font_name = TCPDF_FONTS::addTTFfont($font_path, 'TrueTypeUnicode', '', 96);
$pdf->SetFont($font_name, '', 12);
```

**Limitaciones:**
- Solo soporta archivos `.ttf` (TrueType)
- No soporta `.otf`, `.woff`, `.woff2`
- Fuentes muy pesadas pueden causar timeouts

### 9.3. Códigos QR de Validación

Los códigos QR se generan con datos del certificado:

```php
$qr_data = array(
    'documento' => $numero_documento,
    'evento' => $evento_id,
    'fecha' => date('Y-m-d H:i:s'),
    'hash' => md5($numero_documento . $evento_id . 'salt_secreto')
);

$qr_url = home_url('/validar/?data=' . base64_encode(json_encode($qr_data)));

$pdf->write2DBarcode($qr_url, 'QRCODE,H', $pos_x, $pos_y, $ancho, $alto);
```

**Página de validación:**
Debes crear una página con shortcode `[certificados_validar]` que decodifique el QR y muestre info del certificado.

---

## 10. Seguridad y Validaciones

### 10.1. Nonces y Verificaciones

Todas las peticiones AJAX están protegidas con nonces:

```php
// Generar nonce
wp_localize_script('certificados-script', 'certificadosData', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('certificados_ajax_nonce')
));

// Verificar nonce
check_ajax_referer('certificados_ajax_nonce', 'nonce');
```

### 10.2. Sanitización de Datos

Todos los datos se sanitizan antes de usar:

```php
$numero_documento = sanitize_text_field($_POST['numero_documento']);
$tipo_documento = sanitize_text_field($_POST['tipo_documento']);
$evento_id = absint($_POST['evento_id']);
```

### 10.3. Prepared Statements

Todas las consultas SQL usan prepared statements:

```php
$wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
    $evento_id
));
```

### 10.4. Validación de Capacidades

Las funciones admin verifican permisos:

```php
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.'));
}
```

---

## Contacto y Soporte Técnico

**Desarrollado por:** Luis Quino con Claude AI
**Email:** webmaster@uninavarra.edu.co
**Versión:** 1.4.0
**Última actualización:** 2025-12-10

---

## Changelog

### v1.4.0 (2025-12-10)
- Rediseño completo de la documentación
- Separación de documentación técnica y guía de usuario
- Mejoras en tipografía y legibilidad
- Nueva sección sobre experiencia del usuario final

### v1.3.0
- Sistema de estadísticas con gráficos
- Exportación de reportes en CSV
- Mejoras en el dashboard

### v1.2.0
- Sistema de encuestas de satisfacción
- Encuestas obligatorias y opcionales
- Validación contra Google Sheets

### v1.1.0
- Mapeo dinámico de columnas
- Sistema de caché inteligente
- Detección automática de cambios

### v1.0.0
- Lanzamiento inicial
- Integración con Google Sheets API v4
- Generación de PDFs con TCPDF
- Shortcodes frontend
