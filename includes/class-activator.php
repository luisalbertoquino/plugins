<?php
/**
 * Clase que maneja la activación del plugin.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/includes
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Activator.
 *
 * Se ejecuta durante la activación del plugin.
 */
class Certificados_Digitales_Activator {

    /**
     * Método principal de activación.
     */
    public static function activate() {
        
        // Verificar versión de PHP
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            wp_die( 
                __( 'Este plugin requiere PHP 7.4 o superior. Tu versión actual es: ', 'certificados-digitales' ) . PHP_VERSION,
                __( 'Error de activación', 'certificados-digitales' ),
                array( 'back_link' => true )
            );
        }

        // Verificar versión de WordPress
        global $wp_version;
        if ( version_compare( $wp_version, '5.8', '<' ) ) {
            wp_die(
                __( 'Este plugin requiere WordPress 5.8 o superior. Tu versión actual es: ', 'certificados-digitales' ) . $wp_version,
                __( 'Error de activación', 'certificados-digitales' ),
                array( 'back_link' => true )
            );
        }

        // Crear tablas de base de datos
        self::create_tables();

        // Actualizar base de datos si es necesario
        self::update_database();

        // Crear carpetas necesarias
        self::create_folders();

        // Guardar versión del plugin
        update_option( 'certificados_digitales_version', CERTIFICADOS_DIGITALES_VERSION );

        // Guardar fecha de activación
        if ( ! get_option( 'certificados_digitales_activated_date' ) ) {
            update_option( 'certificados_digitales_activated_date', current_time( 'mysql' ) );
        }

        // Forzar flush de rewrite rules (por si en el futuro usamos custom post types)
        flush_rewrite_rules();
    }

    /**
     * Crea las tablas de la base de datos.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;

        // Incluir archivo de upgrade para usar dbDelta()
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // TABLA 1: Eventos
        $table_eventos = $table_prefix . 'certificados_eventos';
        $sql_eventos = "CREATE TABLE $table_eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            sheet_id VARCHAR(100) NOT NULL,
            url_encuesta TEXT,
            logo_loader_url TEXT,
            activo TINYINT(1) DEFAULT 1,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";

        // TABLA 2: Pestañas
        $table_pestanas = $table_prefix . 'certificados_pestanas';
        $sql_pestanas = "CREATE TABLE $table_pestanas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL,
            nombre_pestana VARCHAR(100) NOT NULL,
            nombre_hoja_sheet VARCHAR(100) NOT NULL,
            plantilla_url TEXT NOT NULL,
            orden INT DEFAULT 0,
            activo TINYINT(1) DEFAULT 1,
            FOREIGN KEY (evento_id) REFERENCES $table_eventos(id) ON DELETE CASCADE
        ) $charset_collate;";

        // TABLA 3: Fuentes
        $table_fuentes = $table_prefix . 'certificados_fuentes';
        $sql_fuentes = "CREATE TABLE $table_fuentes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_fuente VARCHAR(100) NOT NULL UNIQUE,
            archivo_url TEXT NOT NULL,
            tcpdf_name VARCHAR(255),
            fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        // TABLA 4: Configuración de campos
        $table_campos = $table_prefix . 'certificados_campos_config';
        $sql_campos = "CREATE TABLE $table_campos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pestana_id INT NOT NULL,
            campo_tipo ENUM('nombre', 'documento', 'ciudad', 'trabajo', 'qr', 'fecha_emision') NOT NULL,
            posicion_top DECIMAL(5,2),
            posicion_left DECIMAL(5,2),
            font_size INT,
            font_family VARCHAR(100),
            font_style ENUM('normal', 'bold', 'italic', 'bold-italic') DEFAULT 'normal',
            color VARCHAR(7),
            alineacion ENUM('left', 'center', 'right') DEFAULT 'center',
            qr_size INT DEFAULT 20,
            activo TINYINT(1) DEFAULT 1,
            FOREIGN KEY (pestana_id) REFERENCES $table_pestanas(id) ON DELETE CASCADE
        ) $charset_collate;";

        // TABLA 5: Log de descargas
        // Incluye columnas antiguas y nuevas para compatibilidad con diferentes versiones del código.
        $table_log = $table_prefix . 'certificados_descargas_log';
        $sql_log = "CREATE TABLE $table_log (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NULL,
            pestana_id INT NULL,
            numero_documento VARCHAR(100) NOT NULL,
            accion VARCHAR(50),
            tipo_documento VARCHAR(10),
            nombre_usuario VARCHAR(200),
            fecha DATETIME NOT NULL,
            fecha_descarga DATETIME NULL,
            ip VARCHAR(45),
            ip_address VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (evento_id) REFERENCES $table_eventos(id) ON DELETE CASCADE,
            FOREIGN KEY (pestana_id) REFERENCES $table_pestanas(id) ON DELETE CASCADE,
            KEY idx_evento (evento_id),
            KEY idx_fecha (fecha),
            KEY idx_documento (numero_documento)
        ) $charset_collate;";

        // Ejecutar dbDelta para crear/actualizar tablas
        dbDelta( $sql_eventos );
        dbDelta( $sql_pestanas );
        dbDelta( $sql_fuentes );
        dbDelta( $sql_campos );
        dbDelta( $sql_log );

        // TABLA 6: Cache de certificados (archivos generados)
        $table_cache = $table_prefix . 'certificados_cache';
        $sql_cache = "CREATE TABLE $table_cache (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            pestana_id INT NOT NULL,
            numero_documento VARCHAR(100) NOT NULL,
            ruta_archivo TEXT NOT NULL,
            fecha_generacion DATETIME NOT NULL,
            descargas INT DEFAULT 0,
            FOREIGN KEY (pestana_id) REFERENCES $table_pestanas(id) ON DELETE CASCADE,
            KEY idx_pestana (pestana_id),
            KEY idx_numero (numero_documento),
            KEY idx_fecha (fecha_generacion)
        ) $charset_collate";

        dbDelta( $sql_cache );

        // TABLA 7: Descargas (tabla simplificada para estadísticas)
        $table_descargas = $table_prefix . 'certificados_descargas';
        $sql_descargas = "CREATE TABLE $table_descargas (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            pestana_id INT NOT NULL,
            numero_documento VARCHAR(100) NOT NULL,
            fecha_descarga DATETIME NOT NULL,
            ip_descarga VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (pestana_id) REFERENCES $table_pestanas(id) ON DELETE CASCADE,
            KEY idx_pestana (pestana_id),
            KEY idx_documento (numero_documento),
            KEY idx_fecha (fecha_descarga)
        ) $charset_collate;";

        dbDelta( $sql_descargas );

        // TABLA 8: Metadata de caché de sheets (v1.3.0+)
        $table_cache_meta = $table_prefix . 'certificados_sheets_cache_meta';
        $sql_cache_meta = "CREATE TABLE $table_cache_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sheet_id VARCHAR(100) NOT NULL,
            sheet_name VARCHAR(100) NOT NULL,
            content_hash VARCHAR(32) NOT NULL,
            row_count INT DEFAULT 0,
            etag VARCHAR(255),
            last_check DATETIME NOT NULL,
            last_modified DATETIME NOT NULL,
            needs_refresh TINYINT(1) DEFAULT 0,
            cached_data LONGTEXT,
            UNIQUE KEY unique_sheet (sheet_id, sheet_name),
            KEY idx_last_check (last_check),
            KEY idx_needs_refresh (needs_refresh)
        ) $charset_collate;";

        dbDelta( $sql_cache_meta );

        // TABLA 9: Mapeo de columnas (v1.3.0+)
        $table_column_mapping = $table_prefix . 'certificados_column_mapping';
        $sql_column_mapping = "CREATE TABLE $table_column_mapping (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL,
            sheet_name VARCHAR(100) NOT NULL,
            system_field VARCHAR(50) NOT NULL,
            sheet_column VARCHAR(100) NOT NULL,
            column_index INT NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (evento_id) REFERENCES $table_eventos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_mapping (evento_id, sheet_name, system_field),
            KEY idx_evento (evento_id),
            KEY idx_active (is_active)
        ) $charset_collate;";

        dbDelta( $sql_column_mapping );

        // TABLA 10: Configuración de encuestas (v1.3.0+)
        $table_survey = $table_prefix . 'certificados_survey_config';
        $sql_survey = "CREATE TABLE $table_survey (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL,
            survey_mode ENUM('disabled', 'optional', 'mandatory') DEFAULT 'disabled',
            survey_url TEXT,
            survey_title VARCHAR(200),
            survey_message TEXT,
            response_sheet_id VARCHAR(100),
            response_sheet_name VARCHAR(100),
            document_column VARCHAR(100),
            document_column_index INT,
            event_column VARCHAR(100),
            event_column_index INT,
            event_match_value VARCHAR(200),
            is_active TINYINT(1) DEFAULT 1,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (evento_id) REFERENCES $table_eventos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_evento (evento_id),
            KEY idx_mode (survey_mode)
        ) $charset_collate;";

        dbDelta( $sql_survey );

        // MIGRACIÓN: Eliminar columna url_validacion si existe (ya no se usa)
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_eventos LIKE 'url_validacion'");
        if ( !empty($column_exists) ) {
            $wpdb->query("ALTER TABLE $table_eventos DROP COLUMN url_validacion");
        }
    }

    /**
     * Crea las carpetas necesarias para almacenar archivos.
     */
    private static function create_folders() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/certificados-digitales';

        $folders = array(
            $base_dir,
            $base_dir . '/fuentes',
            $base_dir . '/plantillas',
            $base_dir . '/temp',
        );

        foreach ( $folders as $folder ) {
            if ( ! file_exists( $folder ) ) {
                wp_mkdir_p( $folder );
                
                // Crear archivo .htaccess para proteger carpetas
                $htaccess_file = $folder . '/.htaccess';
                if ( ! file_exists( $htaccess_file ) ) {
                    $htaccess_content = "Options -Indexes\n";
                    $htaccess_content .= "<Files *.php>\n";
                    $htaccess_content .= "deny from all\n";
                    $htaccess_content .= "</Files>\n";
                    file_put_contents( $htaccess_file, $htaccess_content );
                }

                // Crear archivo index.php vacío para mayor seguridad
                $index_file = $folder . '/index.php';
                if ( ! file_exists( $index_file ) ) {
                    file_put_contents( $index_file, '<?php // Silence is golden' );
                }
            }
        }
    }

    /**
     * Actualizar base de datos para versiones nuevas
     * Sistema de migraciones seguro que preserva datos existentes
     */
    private static function update_database() {
        global $wpdb;
        $table_prefix = $wpdb->prefix;

        // Obtener versión actual de la BD
        $current_db_version = get_option( 'certificados_digitales_db_version', '0.0.0' );

        // Ejecutar migraciones según la versión
        self::migrate_to_1_2_0( $current_db_version );
        self::migrate_to_1_5_8( $current_db_version );

        // Actualizar versión de BD
        update_option( 'certificados_digitales_db_version', CERTIFICADOS_DIGITALES_VERSION );
    }

    /**
     * Migración a versión 1.2.0
     * Agrega columna font_style si no existe
     */
    private static function migrate_to_1_2_0( $current_version ) {
        if ( version_compare( $current_version, '1.2.0', '>=' ) ) {
            return; // Ya se ejecutó esta migración
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $table_campos = $table_prefix . 'certificados_campos_config';

        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_campos'" );
        if ( ! $table_exists ) {
            return;
        }

        // Verificar si la columna font_style existe
        $column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'font_style'",
                DB_NAME,
                $table_campos
            )
        );

        // Si no existe, agregarla
        if ( ! $column_exists ) {
            $wpdb->query(
                "ALTER TABLE $table_campos
                ADD COLUMN font_style ENUM('normal', 'bold', 'italic', 'bold-italic') DEFAULT 'normal'
                AFTER font_family"
            );
        }
    }

    /**
     * Migración a versión 1.5.8
     * Verifica y agrega columnas faltantes en todas las tablas
     */
    private static function migrate_to_1_5_8( $current_version ) {
        if ( version_compare( $current_version, '1.5.8', '>=' ) ) {
            return; // Ya se ejecutó esta migración
        }

        global $wpdb;
        $table_prefix = $wpdb->prefix;

        // MIGRACIÓN 1: Tabla eventos - agregar columnas si faltan
        $table_eventos = $table_prefix . 'certificados_eventos';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_eventos'" ) ) {

            // Columna url_encuesta
            if ( ! self::column_exists( $table_eventos, 'url_encuesta' ) ) {
                $wpdb->query( "ALTER TABLE $table_eventos ADD COLUMN url_encuesta TEXT AFTER sheet_id" );
            }

            // Columna logo_loader_url
            if ( ! self::column_exists( $table_eventos, 'logo_loader_url' ) ) {
                $wpdb->query( "ALTER TABLE $table_eventos ADD COLUMN logo_loader_url TEXT AFTER url_encuesta" );
            }

            // Columna fecha_modificacion
            if ( ! self::column_exists( $table_eventos, 'fecha_modificacion' ) ) {
                $wpdb->query(
                    "ALTER TABLE $table_eventos
                    ADD COLUMN fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    AFTER fecha_creacion"
                );
            }
        }

        // MIGRACIÓN 2: Tabla pestañas - agregar columnas si faltan
        $table_pestanas = $table_prefix . 'certificados_pestanas';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_pestanas'" ) ) {

            // Columna nombre_hoja_sheet
            if ( ! self::column_exists( $table_pestanas, 'nombre_hoja_sheet' ) ) {
                $wpdb->query(
                    "ALTER TABLE $table_pestanas
                    ADD COLUMN nombre_hoja_sheet VARCHAR(100) NOT NULL DEFAULT ''
                    AFTER nombre_pestana"
                );
            }

            // Columna orden
            if ( ! self::column_exists( $table_pestanas, 'orden' ) ) {
                $wpdb->query( "ALTER TABLE $table_pestanas ADD COLUMN orden INT DEFAULT 0 AFTER plantilla_url" );
            }

            // Columna activo
            if ( ! self::column_exists( $table_pestanas, 'activo' ) ) {
                $wpdb->query( "ALTER TABLE $table_pestanas ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER orden" );
            }
        }

        // MIGRACIÓN 3: Tabla campos_config - agregar columnas si faltan
        $table_campos = $table_prefix . 'certificados_campos_config';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_campos'" ) ) {

            // Columna font_style (por si no se ejecutó la migración 1.2.0)
            if ( ! self::column_exists( $table_campos, 'font_style' ) ) {
                $wpdb->query(
                    "ALTER TABLE $table_campos
                    ADD COLUMN font_style ENUM('normal', 'bold', 'italic', 'bold-italic') DEFAULT 'normal'
                    AFTER font_family"
                );
            }

            // Columna qr_size
            if ( ! self::column_exists( $table_campos, 'qr_size' ) ) {
                $wpdb->query( "ALTER TABLE $table_campos ADD COLUMN qr_size INT DEFAULT 20 AFTER alineacion" );
            }

            // Columna activo
            if ( ! self::column_exists( $table_campos, 'activo' ) ) {
                $wpdb->query( "ALTER TABLE $table_campos ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER qr_size" );
            }
        }

        // MIGRACIÓN 4: Tabla descargas (tabla de estadísticas) - crear si no existe
        $table_descargas = $table_prefix . 'certificados_descargas';
        $table_exists_descargas = $wpdb->get_var( "SHOW TABLES LIKE '$table_descargas'" );

        if ( ! $table_exists_descargas ) {
            // La tabla no existe, crearla
            $charset_collate = $wpdb->get_charset_collate();
            $sql_descargas = "CREATE TABLE $table_descargas (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                pestana_id INT NOT NULL,
                numero_documento VARCHAR(100) NOT NULL,
                fecha_descarga DATETIME NOT NULL,
                ip_descarga VARCHAR(45),
                user_agent TEXT,
                KEY idx_pestana (pestana_id),
                KEY idx_documento (numero_documento),
                KEY idx_fecha (fecha_descarga)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_descargas );

            // Migrar datos existentes de descargas_log si existe
            $table_log = $table_prefix . 'certificados_descargas_log';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_log'" ) ) {
                $wpdb->query(
                    "INSERT INTO $table_descargas (pestana_id, numero_documento, fecha_descarga, ip_descarga, user_agent)
                    SELECT pestana_id, numero_documento, fecha, ip, user_agent
                    FROM $table_log
                    WHERE accion = 'descarga'"
                );
            }
        }

        // MIGRACIÓN 5: Tabla descargas_log - agregar columnas si faltan
        $table_log = $table_prefix . 'certificados_descargas_log';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_log'" ) ) {

            // Columna evento_id
            if ( ! self::column_exists( $table_log, 'evento_id' ) ) {
                $wpdb->query( "ALTER TABLE $table_log ADD COLUMN evento_id INT NULL FIRST" );
            }

            // Columna pestana_id
            if ( ! self::column_exists( $table_log, 'pestana_id' ) ) {
                $wpdb->query( "ALTER TABLE $table_log ADD COLUMN pestana_id INT NULL AFTER evento_id" );
            }

            // Columna fecha_descarga (alias de fecha)
            if ( ! self::column_exists( $table_log, 'fecha_descarga' ) ) {
                $wpdb->query( "ALTER TABLE $table_log ADD COLUMN fecha_descarga DATETIME NULL AFTER fecha" );
                // Copiar datos de fecha a fecha_descarga si existen
                $wpdb->query( "UPDATE $table_log SET fecha_descarga = fecha WHERE fecha_descarga IS NULL" );
            }

            // Columna ip_address (alias de ip)
            if ( ! self::column_exists( $table_log, 'ip_address' ) ) {
                $wpdb->query( "ALTER TABLE $table_log ADD COLUMN ip_address VARCHAR(45) AFTER ip" );
                // Copiar datos de ip a ip_address si existen
                $wpdb->query( "UPDATE $table_log SET ip_address = ip WHERE ip_address IS NULL OR ip_address = ''" );
            }
        }

        // MIGRACIÓN 6: Tabla fuentes - agregar columna tcpdf_name si falta
        $table_fuentes = $table_prefix . 'certificados_fuentes';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_fuentes'" ) ) {
            if ( ! self::column_exists( $table_fuentes, 'tcpdf_name' ) ) {
                $wpdb->query( "ALTER TABLE $table_fuentes ADD COLUMN tcpdf_name VARCHAR(255) AFTER archivo_url" );
            }
        }

        // MIGRACIÓN 7: Tabla sheets_cache_meta - crear si no existe o agregar columnas faltantes
        $table_cache = $table_prefix . 'certificados_sheets_cache_meta';
        $table_exists_cache = $wpdb->get_var( "SHOW TABLES LIKE '$table_cache'" );

        if ( ! $table_exists_cache ) {
            // La tabla no existe, crearla completa
            $charset_collate = $wpdb->get_charset_collate();
            $sql_cache = "CREATE TABLE $table_cache (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                sheet_id VARCHAR(100) NOT NULL,
                sheet_name VARCHAR(200) NOT NULL,
                last_modified DATETIME NOT NULL,
                content_hash VARCHAR(64) NOT NULL,
                row_count INT DEFAULT 0,
                etag VARCHAR(255),
                last_check DATETIME NOT NULL,
                needs_refresh TINYINT(1) DEFAULT 0,
                cached_data LONGTEXT,
                UNIQUE KEY unique_sheet (sheet_id, sheet_name),
                KEY idx_last_check (last_check),
                KEY idx_needs_refresh (needs_refresh)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql_cache );
        } else {
            // La tabla existe, verificar columnas faltantes
            if ( ! self::column_exists( $table_cache, 'etag' ) ) {
                $wpdb->query( "ALTER TABLE $table_cache ADD COLUMN etag VARCHAR(255) AFTER row_count" );
            }

            if ( ! self::column_exists( $table_cache, 'needs_refresh' ) ) {
                $wpdb->query( "ALTER TABLE $table_cache ADD COLUMN needs_refresh TINYINT(1) DEFAULT 0 AFTER last_check" );

                // Verificar si el índice existe antes de crearlo
                if ( ! self::index_exists( $table_cache, 'idx_needs_refresh' ) ) {
                    $wpdb->query( "ALTER TABLE $table_cache ADD KEY idx_needs_refresh (needs_refresh)" );
                }
            }

            if ( ! self::column_exists( $table_cache, 'cached_data' ) ) {
                $wpdb->query( "ALTER TABLE $table_cache ADD COLUMN cached_data LONGTEXT AFTER needs_refresh" );
            }
        }
    }

    /**
     * Verifica si una columna existe en una tabla
     *
     * @param string $table_name Nombre de la tabla
     * @param string $column_name Nombre de la columna
     * @return bool True si existe, false si no
     */
    private static function column_exists( $table_name, $column_name ) {
        global $wpdb;

        $column = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = %s",
                DB_NAME,
                $table_name,
                $column_name
            )
        );

        return ! empty( $column );
    }

    /**
     * Verifica si un índice existe en una tabla
     *
     * @param string $table_name Nombre de la tabla
     * @param string $index_name Nombre del índice
     * @return bool True si existe, false si no
     */
    private static function index_exists( $table_name, $index_name ) {
        global $wpdb;

        $index = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND INDEX_NAME = %s",
                DB_NAME,
                $table_name,
                $index_name
            )
        );

        return ! empty( $index );
    }
}