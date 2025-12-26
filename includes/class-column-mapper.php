<?php
/**
 * Mapeo Dinámico de Columnas para Google Sheets
 *
 * Permite al administrador mapear columnas del sheet a campos del sistema
 * sin afectar las funcionalidades existentes del plugin.
 *
 * @package Certificados_Digitales
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Column_Mapper {

    /**
     * Nombre de la tabla de mapeo de columnas
     */
    private $table_column_mapping;

    /**
     * Campos predefinidos del sistema
     */
    private $system_fields = array(
        'numero_documento' => 'Número de Documento',
        'tipo_documento' => 'Tipo de Documento',
        'nombre_completo' => 'Nombre Completo',
        'ciudad_expedicion' => 'Ciudad de Expedición',
        'nombre_evento' => 'Nombre del Evento',
        'ciudad' => 'Ciudad',
        'empresa' => 'Empresa/Institución',
        'cargo' => 'Cargo',
        'fecha_evento' => 'Fecha del Evento',
        'tipo_certificado' => 'Tipo de Certificado',
        'horas' => 'Horas',
        'nota' => 'Nota/Calificación'
    );

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_column_mapping = $wpdb->prefix . 'certificados_column_mapping';

        // Las tablas se crean automáticamente en class-activator.php al activar/actualizar el plugin
        // add_action( 'admin_init', array( $this, 'maybe_create_tables' ) );

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_get_sheet_headers', array( $this, 'ajax_get_sheet_headers' ) );
        add_action( 'wp_ajax_certificados_save_column_mapping', array( $this, 'ajax_save_column_mapping' ) );
        add_action( 'wp_ajax_certificados_get_column_mapping', array( $this, 'ajax_get_column_mapping' ) );
        add_action( 'wp_ajax_certificados_delete_column_mapping', array( $this, 'ajax_delete_column_mapping' ) );
    }

    /**
     * Crear tablas si no existen
     */
    public function maybe_create_tables() {
        $version = get_option( 'certificados_column_mapper_version', '0' );

        if ( version_compare( $version, '1.0', '<' ) ) {
            $this->create_tables();
            update_option( 'certificados_column_mapper_version', '1.0' );
        }
    }

    /**
     * Crear tabla de mapeo de columnas
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_column_mapping} (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL,
            sheet_name VARCHAR(200) NOT NULL,
            system_field VARCHAR(100) NOT NULL,
            sheet_column VARCHAR(200) NOT NULL,
            column_index INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_mapping (evento_id, sheet_name, system_field),
            KEY idx_evento (evento_id),
            KEY idx_sheet (sheet_name)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Obtener campos del sistema
     *
     * @return array
     */
    public function get_system_fields() {
        return apply_filters( 'certificados_system_fields', $this->system_fields );
    }

    /**
     * Leer cabeceras de un Google Sheet
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @return array|WP_Error Array de cabeceras o error
     */
    public function read_sheet_headers( $sheet_id, $sheet_name, $api_key ) {
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        if ( empty( $data ) || ! isset( $data[0] ) ) {
            return new WP_Error(
                'no_headers',
                __( 'No se encontraron cabeceras en la hoja.', 'certificados-digitales' )
            );
        }

        // Primera fila son las cabeceras
        $headers = $data[0];
        $result = array();

        foreach ( $headers as $index => $header ) {
            if ( ! empty( trim( $header ) ) ) {
                $result[] = array(
                    'index' => $index,
                    'name' => trim( $header ),
                    'normalized' => $this->normalize_column_name( $header )
                );
            }
        }

        return $result;
    }

    /**
     * Normalizar nombre de columna
     *
     * @param string $column_name Nombre de la columna
     * @return string Nombre normalizado
     */
    private function normalize_column_name( $column_name ) {
        $normalized = strtolower( trim( $column_name ) );
        $normalized = str_replace( array( ' ', '-', '_' ), '', $normalized );
        $normalized = remove_accents( $normalized );
        return $normalized;
    }

    /**
     * Guardar mapeo de columnas
     *
     * @param int $evento_id ID del evento
     * @param string $sheet_name Nombre de la hoja
     * @param array $mappings Array de mapeos ['system_field' => ['sheet_column' => '', 'column_index' => 0]]
     * @return bool
     */
    public function save_column_mapping( $evento_id, $sheet_name, $mappings ) {
        global $wpdb;

        if ( empty( $mappings ) || ! is_array( $mappings ) ) {
            return false;
        }

        // Iniciar transacción
        $wpdb->query( 'START TRANSACTION' );

        try {
            foreach ( $mappings as $system_field => $mapping ) {
                if ( empty( $mapping['sheet_column'] ) ) {
                    continue;
                }

                $data = array(
                    'evento_id' => (int) $evento_id,
                    'sheet_name' => sanitize_text_field( $sheet_name ),
                    'system_field' => sanitize_text_field( $system_field ),
                    'sheet_column' => sanitize_text_field( $mapping['sheet_column'] ),
                    'column_index' => (int) $mapping['column_index'],
                    'is_active' => 1,
                    'fecha_modificacion' => current_time( 'mysql' )
                );

                // Verificar si existe
                $exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$this->table_column_mapping}
                     WHERE evento_id = %d AND sheet_name = %s AND system_field = %s",
                    $evento_id,
                    $sheet_name,
                    $system_field
                ) );

                if ( $exists ) {
                    // Actualizar
                    $wpdb->update(
                        $this->table_column_mapping,
                        $data,
                        array(
                            'evento_id' => $evento_id,
                            'sheet_name' => $sheet_name,
                            'system_field' => $system_field
                        ),
                        array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' ),
                        array( '%d', '%s', '%s' )
                    );
                } else {
                    // Insertar
                    $data['fecha_creacion'] = current_time( 'mysql' );
                    $wpdb->insert(
                        $this->table_column_mapping,
                        $data,
                        array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
                    );
                }
            }

            $wpdb->query( 'COMMIT' );
            return true;

        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }
    }

    /**
     * Obtener mapeo de columnas
     *
     * @param int $evento_id ID del evento
     * @param string $sheet_name Nombre de la hoja
     * @return array Array de mapeos
     */
    public function get_column_mapping( $evento_id, $sheet_name ) {
        global $wpdb;

        $mappings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_column_mapping}
             WHERE evento_id = %d AND sheet_name = %s AND is_active = 1",
            $evento_id,
            $sheet_name
        ), ARRAY_A );

        $result = array();
        foreach ( $mappings as $mapping ) {
            $result[ $mapping['system_field'] ] = array(
                'sheet_column' => $mapping['sheet_column'],
                'column_index' => (int) $mapping['column_index']
            );
        }

        return $result;
    }

    /**
     * Obtener valor de una fila según el mapeo
     *
     * @param array $row Fila de datos del sheet
     * @param string $system_field Campo del sistema
     * @param int $evento_id ID del evento
     * @param string $sheet_name Nombre de la hoja
     * @return string|null Valor del campo
     */
    public function get_mapped_value( $row, $system_field, $evento_id, $sheet_name ) {
        $mapping = $this->get_column_mapping( $evento_id, $sheet_name );

        if ( ! isset( $mapping[ $system_field ] ) ) {
            return null;
        }

        $column_index = $mapping[ $system_field ]['column_index'];

        return isset( $row[ $column_index ] ) ? trim( $row[ $column_index ] ) : null;
    }

    /**
     * Buscar registro usando mapeo personalizado
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @param int $evento_id ID del evento
     * @param string $numero_documento Número de documento a buscar
     * @return array|null Array con los datos mapeados o null
     */
    public function search_with_mapping( $sheet_id, $sheet_name, $api_key, $evento_id, $numero_documento ) {
        // Obtener datos del sheet
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) || empty( $data ) ) {
            return null;
        }

        // Remover cabeceras
        $headers = array_shift( $data );

        // Obtener mapeo
        $mapping = $this->get_column_mapping( $evento_id, $sheet_name );

        if ( empty( $mapping ) || ! isset( $mapping['numero_documento'] ) ) {
            // Si no hay mapeo, usar búsqueda tradicional
            return null;
        }

        $doc_index = $mapping['numero_documento']['column_index'];

        // Buscar el documento
        foreach ( $data as $row ) {
            $row_doc = isset( $row[ $doc_index ] ) ? trim( $row[ $doc_index ] ) : '';

            if ( $row_doc === trim( $numero_documento ) ) {
                // Construir resultado con campos mapeados
                $result = array();

                foreach ( $mapping as $system_field => $map ) {
                    $col_index = $map['column_index'];
                    $result[ $system_field ] = isset( $row[ $col_index ] ) ? trim( $row[ $col_index ] ) : '';
                }

                // También incluir todos los datos raw por compatibilidad
                foreach ( $headers as $idx => $header ) {
                    $normalized = $this->normalize_column_name( $header );
                    $result[ $normalized ] = isset( $row[ $idx ] ) ? trim( $row[ $idx ] ) : '';
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * Sugerir mapeos automáticos basados en nombres similares
     *
     * @param array $headers Cabeceras del sheet
     * @return array Mapeos sugeridos
     */
    public function suggest_mappings( $headers ) {
        $suggestions = array();

        // Patrones de búsqueda para cada campo
        $patterns = array(
            'numero_documento' => array( 'documento', 'cedula', 'id', 'numdoc', 'cc', 'dni', 'identificacion', 'numerodocumento' ),
            'tipo_documento' => array( 'tipodocumento', 'tipodoc', 'tipoid', 'tipoidentificacion', 'clasedocumento' ),
            'nombre_completo' => array( 'nombre', 'nombres', 'apellidos', 'nombrecompleto', 'participante', 'nombreapellido' ),
            'ciudad_expedicion' => array( 'ciudadexpedicion', 'lugarexpedicion', 'expedicion', 'expedido', 'ciudadexp' ),
            'nombre_evento' => array( 'evento', 'nombreevento', 'curso', 'programa', 'capacitacion' ),
            'ciudad' => array( 'ciudad', 'municipio', 'localidad', 'ubicacion', 'residencia' ),
            'empresa' => array( 'empresa', 'institucion', 'organizacion', 'entidad', 'trabajo' ),
            'cargo' => array( 'cargo', 'puesto', 'posicion', 'ocupacion' ),
            'fecha_evento' => array( 'fecha', 'fechaevento', 'date', 'cuando' ),
            'horas' => array( 'horas', 'duracion', 'intensidad', 'horasacademicas' )
        );

        foreach ( $headers as $header ) {
            $normalized = $this->normalize_column_name( $header['name'] );

            foreach ( $patterns as $system_field => $search_patterns ) {
                foreach ( $search_patterns as $pattern ) {
                    if ( strpos( $normalized, $pattern ) !== false ) {
                        $suggestions[ $system_field ] = array(
                            'sheet_column' => $header['name'],
                            'column_index' => $header['index'],
                            'confidence' => 'high'
                        );
                        break 2;
                    }
                }
            }
        }

        return $suggestions;
    }

    /**
     * Eliminar mapeo de columnas
     *
     * @param int $evento_id ID del evento
     * @param string $sheet_name Nombre de la hoja
     * @return bool
     */
    public function delete_column_mapping( $evento_id, $sheet_name = null ) {
        global $wpdb;

        if ( $sheet_name ) {
            return $wpdb->delete(
                $this->table_column_mapping,
                array(
                    'evento_id' => $evento_id,
                    'sheet_name' => $sheet_name
                ),
                array( '%d', '%s' )
            );
        } else {
            return $wpdb->delete(
                $this->table_column_mapping,
                array( 'evento_id' => $evento_id ),
                array( '%d' )
            );
        }
    }

    /**
     * AJAX: Obtener cabeceras del sheet
     */
    public function ajax_get_sheet_headers() {
        check_ajax_referer( 'certificados_mapper_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $sheet_id = isset( $_POST['sheet_id'] ) ? sanitize_text_field( $_POST['sheet_id'] ) : '';
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
        $api_key = get_option( 'certificados_digitales_api_key', '' );

        if ( empty( $sheet_id ) || empty( $sheet_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Parámetros incompletos. Por favor proporciona el ID del Sheet y el nombre de la hoja.', 'certificados-digitales' ) ) );
        }

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API Key de Google no configurada. Por favor ve a Certificados > Configuración y agrega tu API Key.', 'certificados-digitales' ) ) );
        }

        $headers = $this->read_sheet_headers( $sheet_id, $sheet_name, $api_key );

        if ( is_wp_error( $headers ) ) {
            wp_send_json_error( array( 'message' => $headers->get_error_message() ) );
        }

        // Obtener sugerencias automáticas
        $suggestions = $this->suggest_mappings( $headers );

        wp_send_json_success( array(
            'headers' => $headers,
            'suggestions' => $suggestions,
            'system_fields' => $this->get_system_fields()
        ) );
    }

    /**
     * AJAX: Guardar mapeo de columnas
     */
    public function ajax_save_column_mapping() {
        check_ajax_referer( 'certificados_mapper_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';
        $mappings = isset( $_POST['mappings'] ) ? $_POST['mappings'] : array();

        if ( ! $evento_id || empty( $sheet_name ) || empty( $mappings ) ) {
            wp_send_json_error( array( 'message' => __( 'Parámetros incompletos.', 'certificados-digitales' ) ) );
        }

        $result = $this->save_column_mapping( $evento_id, $sheet_name, $mappings );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Mapeo guardado correctamente.', 'certificados-digitales' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al guardar el mapeo.', 'certificados-digitales' )
            ) );
        }
    }

    /**
     * AJAX: Obtener mapeo de columnas
     */
    public function ajax_get_column_mapping() {
        check_ajax_referer( 'certificados_mapper_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';

        if ( ! $evento_id || empty( $sheet_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Parámetros incompletos.', 'certificados-digitales' ) ) );
        }

        $mapping = $this->get_column_mapping( $evento_id, $sheet_name );

        wp_send_json_success( array(
            'mapping' => $mapping,
            'system_fields' => $this->get_system_fields()
        ) );
    }

    /**
     * AJAX: Eliminar mapeo de columnas
     */
    public function ajax_delete_column_mapping() {
        check_ajax_referer( 'certificados_mapper_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        $sheet_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( $_POST['sheet_name'] ) : '';

        if ( ! $evento_id ) {
            wp_send_json_error( array( 'message' => __( 'Evento ID requerido.', 'certificados-digitales' ) ) );
        }

        $result = $this->delete_column_mapping( $evento_id, $sheet_name );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Mapeo eliminado correctamente.', 'certificados-digitales' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al eliminar el mapeo.', 'certificados-digitales' )
            ) );
        }
    }
}
