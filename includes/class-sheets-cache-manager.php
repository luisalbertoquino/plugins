<?php
/**
 * Gestor de Caché para Google Sheets
 *
 * Detecta cambios en Google Sheets y maneja la recarga automática de caché
 * sin afectar las funcionalidades existentes del plugin.
 *
 * @package Certificados_Digitales
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Sheets_Cache_Manager {

    /**
     * Nombre de la tabla de caché de metadatos
     */
    private $table_cache_meta;

    /**
     * Tiempo de vida de la caché en segundos (por defecto 5 minutos)
     */
    private $cache_ttl = 300;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_cache_meta = $wpdb->prefix . 'certificados_sheets_cache_meta';

        // Las tablas se crean automáticamente en class-activator.php al activar/actualizar el plugin
        // add_action( 'admin_init', array( $this, 'maybe_create_tables' ) );

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_clear_cache', array( $this, 'ajax_clear_cache' ) );
        add_action( 'wp_ajax_certificados_check_sheet_changes', array( $this, 'ajax_check_sheet_changes' ) );
        add_action( 'wp_ajax_certificados_get_monitored_fields', array( $this, 'ajax_get_monitored_fields' ) );
    }

    /**
     * Obtener lista de campos críticos monitoreados
     *
     * @return array
     */
    public function get_monitored_fields() {
        return array(
            'tipo_documento' => array(
                'label' => __( 'Tipo de Documento', 'certificados-digitales' ),
                'priority' => 'high',
                'patterns' => array( 'Tipo Documento', 'Tipo Doc', 'Tipo ID', 'Tipo Identificación', 'Clase Documento' )
            ),
            'numero_documento' => array(
                'label' => __( 'Número de Documento', 'certificados-digitales' ),
                'priority' => 'high',
                'patterns' => array( 'Número Documento', 'Documento', 'Cédula', 'ID', 'CC', 'DNI', 'Identificación' )
            ),
            'nombre' => array(
                'label' => __( 'Nombre Completo', 'certificados-digitales' ),
                'priority' => 'high',
                'patterns' => array( 'Nombre', 'Nombres', 'Nombre Completo', 'Apellidos', 'Participante', 'Nombre y Apellido' )
            ),
            'ciudad_expedicion' => array(
                'label' => __( 'Ciudad de Expedición', 'certificados-digitales' ),
                'priority' => 'medium',
                'patterns' => array( 'Ciudad Expedición', 'Lugar Expedición', 'Expedición', 'Expedido', 'Ciudad Exp' )
            ),
            'trabajo' => array(
                'label' => __( 'Trabajo/Empresa', 'certificados-digitales' ),
                'priority' => 'medium',
                'patterns' => array( 'Trabajo', 'Empresa', 'Institución', 'Organización', 'Entidad', 'Cargo', 'Puesto' )
            )
        );
    }

    /**
     * Crear tablas de caché si no existen
     */
    public function maybe_create_tables() {
        $version = get_option( 'certificados_cache_manager_version', '0' );

        if ( version_compare( $version, '1.0', '<' ) ) {
            $this->create_tables();
            update_option( 'certificados_cache_manager_version', '1.0' );
        }
    }

    /**
     * Crear tabla de metadatos de caché
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_cache_meta} (
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
        dbDelta( $sql );
    }

    /**
     * Obtener información de la caché para un sheet específico
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @return object|null
     */
    public function get_cache_info( $sheet_id, $sheet_name ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_cache_meta}
             WHERE sheet_id = %s AND sheet_name = %s",
            $sheet_id,
            $sheet_name
        ) );
    }

    /**
     * Verificar si la caché necesita actualización
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @return bool True si necesita actualización
     */
    public function needs_refresh( $sheet_id, $sheet_name, $api_key ) {
        $cache_info = $this->get_cache_info( $sheet_id, $sheet_name );

        // Si no existe caché, necesita actualización
        if ( ! $cache_info ) {
            return true;
        }

        // Si ya está marcado para refresh
        if ( $cache_info->needs_refresh == 1 ) {
            return true;
        }

        // Verificar TTL
        $last_check = strtotime( $cache_info->last_check );
        if ( ( time() - $last_check ) > $this->cache_ttl ) {
            // Verificar si hay cambios remotos
            return $this->check_remote_changes( $sheet_id, $sheet_name, $api_key, $cache_info );
        }

        return false;
    }

    /**
     * Verificar cambios remotos en Google Sheets
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @param object $cache_info Información de caché actual
     * @return bool True si hay cambios
     */
    private function check_remote_changes( $sheet_id, $sheet_name, $api_key, $cache_info ) {
        // Obtener datos del sheet
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) ) {
            // Si hay error, mantener caché actual
            return false;
        }

        // Calcular hash del contenido
        $new_hash = $this->calculate_content_hash( $data );
        $new_row_count = count( $data );

        // Actualizar last_check
        global $wpdb;
        $wpdb->update(
            $this->table_cache_meta,
            array( 'last_check' => current_time( 'mysql' ) ),
            array(
                'sheet_id' => $sheet_id,
                'sheet_name' => $sheet_name
            ),
            array( '%s' ),
            array( '%s', '%s' )
        );

        // Comparar hash y número de filas
        if ( $new_hash !== $cache_info->content_hash || $new_row_count !== (int) $cache_info->row_count ) {
            // Marcar para refresh
            $wpdb->update(
                $this->table_cache_meta,
                array( 'needs_refresh' => 1 ),
                array(
                    'sheet_id' => $sheet_id,
                    'sheet_name' => $sheet_name
                ),
                array( '%d' ),
                array( '%s', '%s' )
            );

            return true;
        }

        return false;
    }

    /**
     * Calcular hash del contenido enfocado en campos críticos
     *
     * @param array $data Datos del sheet
     * @return string Hash MD5 del contenido
     */
    private function calculate_content_hash( $data ) {
        if ( empty( $data ) ) {
            return md5( '' );
        }

        // Primera fila son los encabezados
        $headers = isset( $data[0] ) ? $data[0] : array();

        // Normalizar encabezados para búsqueda
        $normalized_headers = array_map( function( $header ) {
            return strtolower( trim( str_replace( array( ' ', '_', '-' ), '', $header ) ) );
        }, $headers );

        // Identificar índices de columnas críticas
        $critical_columns = array(
            'tipo_documento' => array( 'tipodocumento', 'tipodoc', 'tipoidentificacion', 'clasedocumento' ),
            'numero_documento' => array( 'numerodocumento', 'documento', 'cedula', 'id', 'numdoc', 'cc', 'dni', 'identificacion' ),
            'nombre' => array( 'nombre', 'nombres', 'nombrecompleto', 'apellidos', 'participante', 'nombreapellido' ),
            'ciudad_expedicion' => array( 'ciudadexpedicion', 'lugarexpedicion', 'expedicion', 'expedido', 'ciudadexp' ),
            'trabajo' => array( 'trabajo', 'empresa', 'institucion', 'organizacion', 'entidad', 'cargo', 'puesto' )
        );

        $column_indices = array();
        foreach ( $critical_columns as $field => $patterns ) {
            foreach ( $normalized_headers as $index => $normalized ) {
                foreach ( $patterns as $pattern ) {
                    if ( strpos( $normalized, $pattern ) !== false ) {
                        $column_indices[ $field ] = $index;
                        break 2; // Salir de ambos loops cuando encuentra coincidencia
                    }
                }
            }
        }

        // Construir string con valores de campos críticos
        $critical_data = array();

        // Recorrer todas las filas (excepto encabezados)
        for ( $i = 1; $i < count( $data ); $i++ ) {
            $row = $data[ $i ];
            $row_critical = array();

            // Extraer valores de columnas críticas identificadas
            foreach ( $column_indices as $field => $col_index ) {
                if ( isset( $row[ $col_index ] ) ) {
                    $row_critical[] = trim( $row[ $col_index ] );
                }
            }

            // Si la fila tiene al menos un valor crítico, incluirla
            if ( ! empty( $row_critical ) ) {
                $critical_data[] = implode( '|', $row_critical );
            }
        }

        // Calcular hash solo de los campos críticos
        // Esto hace que cambios en columnas no críticas NO invaliden la caché
        // pero cambios en documento, nombre, tipo doc, etc. SÍ la invaliden
        return md5( implode( "\n", $critical_data ) );
    }

    /**
     * Actualizar caché con nuevos datos
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param array $data Datos a cachear
     * @return bool
     */
    public function update_cache( $sheet_id, $sheet_name, $data ) {
        global $wpdb;

        $hash = $this->calculate_content_hash( $data );
        $row_count = count( $data );
        $current_time = current_time( 'mysql' );

        // Serializar datos para almacenar
        $cached_data = maybe_serialize( $data );

        $cache_data = array(
            'sheet_id' => $sheet_id,
            'sheet_name' => $sheet_name,
            'last_modified' => $current_time,
            'content_hash' => $hash,
            'row_count' => $row_count,
            'last_check' => $current_time,
            'needs_refresh' => 0,
            'cached_data' => $cached_data
        );

        // Verificar si existe
        $exists = $this->get_cache_info( $sheet_id, $sheet_name );

        if ( $exists ) {
            // Actualizar
            return $wpdb->update(
                $this->table_cache_meta,
                $cache_data,
                array(
                    'sheet_id' => $sheet_id,
                    'sheet_name' => $sheet_name
                ),
                array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s' ),
                array( '%s', '%s' )
            );
        } else {
            // Insertar
            return $wpdb->insert(
                $this->table_cache_meta,
                $cache_data,
                array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s' )
            );
        }
    }

    /**
     * Obtener datos cacheados
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @return array|null
     */
    public function get_cached_data( $sheet_id, $sheet_name ) {
        $cache_info = $this->get_cache_info( $sheet_id, $sheet_name );

        if ( ! $cache_info || empty( $cache_info->cached_data ) ) {
            return null;
        }

        return maybe_unserialize( $cache_info->cached_data );
    }

    /**
     * Limpiar caché de un sheet específico
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja (opcional)
     * @return bool
     */
    public function clear_cache( $sheet_id, $sheet_name = null ) {
        global $wpdb;

        if ( $sheet_name ) {
            return $wpdb->delete(
                $this->table_cache_meta,
                array(
                    'sheet_id' => $sheet_id,
                    'sheet_name' => $sheet_name
                ),
                array( '%s', '%s' )
            );
        } else {
            return $wpdb->delete(
                $this->table_cache_meta,
                array( 'sheet_id' => $sheet_id ),
                array( '%s' )
            );
        }
    }

    /**
     * Limpiar caché antigua (más de 7 días)
     */
    public function cleanup_old_cache() {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->table_cache_meta} WHERE last_check < %s",
            $date_limit
        ) );
    }

    /**
     * Obtener datos del sheet con caché inteligente
     *
     * Esta es la función principal que debe usarse para obtener datos
     * Integra detección de cambios y caché automático
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @param bool $force_refresh Forzar actualización
     * @return array|WP_Error
     */
    public function get_sheet_data_cached( $sheet_id, $sheet_name, $api_key, $force_refresh = false ) {
        // Si se fuerza refresh o necesita actualización
        if ( $force_refresh || $this->needs_refresh( $sheet_id, $sheet_name, $api_key ) ) {
            // Obtener datos frescos del API
            $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
            $data = $sheets_api->get_sheet_data( $sheet_name );

            if ( is_wp_error( $data ) ) {
                // Si hay error, intentar usar caché
                $cached = $this->get_cached_data( $sheet_id, $sheet_name );
                return $cached ? $cached : $data;
            }

            // Actualizar caché
            $this->update_cache( $sheet_id, $sheet_name, $data );

            return $data;
        }

        // Usar datos cacheados
        $cached = $this->get_cached_data( $sheet_id, $sheet_name );

        if ( $cached ) {
            return $cached;
        }

        // Si no hay caché, obtener datos frescos
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( ! is_wp_error( $data ) ) {
            $this->update_cache( $sheet_id, $sheet_name, $data );
        }

        return $data;
    }

    /**
     * AJAX: Limpiar caché
     */
    public function ajax_clear_cache() {
        check_ajax_referer( 'certificados_cache_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $sheet_id = isset( $_POST['sheet_id'] ) ? sanitize_text_field( $_POST['sheet_id'] ) : '';
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';

        if ( empty( $sheet_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Sheet ID requerido.', 'certificados-digitales' ) ) );
        }

        $result = $this->clear_cache( $sheet_id, $sheet_name );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Caché limpiada correctamente.', 'certificados-digitales' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al limpiar caché.', 'certificados-digitales' )
            ) );
        }
    }

    /**
     * AJAX: Verificar cambios en el sheet
     */
    public function ajax_check_sheet_changes() {
        check_ajax_referer( 'certificados_cache_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $sheet_id = isset( $_POST['sheet_id'] ) ? sanitize_text_field( $_POST['sheet_id'] ) : '';
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
        $api_key = get_option( 'certificados_digitales_api_key', '' );

        if ( empty( $sheet_id ) || empty( $sheet_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Parámetros incompletos.', 'certificados-digitales' ) ) );
        }

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API Key de Google no configurada.', 'certificados-digitales' ) ) );
        }

        $needs_refresh = $this->needs_refresh( $sheet_id, $sheet_name, $api_key );
        $cache_info = $this->get_cache_info( $sheet_id, $sheet_name );

        wp_send_json_success( array(
            'needs_refresh' => $needs_refresh,
            'cache_info' => $cache_info,
            'message' => $needs_refresh
                ? __( 'Se detectaron cambios en el sheet.', 'certificados-digitales' )
                : __( 'No hay cambios en el sheet.', 'certificados-digitales' )
        ) );
    }

    /**
     * AJAX: Obtener campos monitoreados
     */
    public function ajax_get_monitored_fields() {
        check_ajax_referer( 'certificados_cache_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $fields = $this->get_monitored_fields();

        wp_send_json_success( array(
            'fields' => $fields,
            'message' => __( 'El sistema monitorea cambios en estos campos críticos para invalidar la caché automáticamente.', 'certificados-digitales' )
        ) );
    }

    /**
     * Establecer tiempo de vida de la caché
     *
     * @param int $seconds Segundos
     */
    public function set_cache_ttl( $seconds ) {
        $this->cache_ttl = (int) $seconds;
    }

    /**
     * Obtener estadísticas de caché
     *
     * @return array
     */
    public function get_cache_stats() {
        global $wpdb;

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_cache_meta}" );
        $needs_refresh = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_cache_meta} WHERE needs_refresh = 1" );

        $old_date = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
        $old_entries = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_cache_meta} WHERE last_check < %s",
            $old_date
        ) );

        return array(
            'total' => (int) $total,
            'needs_refresh' => (int) $needs_refresh,
            'old_entries' => (int) $old_entries
        );
    }
}
