<?php
/**
 * Gestor de Encuestas de Satisfacción
 *
 * Permite configurar encuestas opcionales u obligatorias antes de descargar certificados
 * sin afectar las funcionalidades existentes del plugin.
 *
 * @package Certificados_Digitales
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Survey_Manager {

    /**
     * Nombre de la tabla de configuración de encuestas
     */
    private $table_survey_config;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_survey_config = $wpdb->prefix . 'certificados_survey_config';

        // Las tablas se crean automáticamente en class-activator.php al activar/actualizar el plugin
        // add_action( 'admin_init', array( $this, 'maybe_create_tables' ) );

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_save_survey_config', array( $this, 'ajax_save_survey_config' ) );
        add_action( 'wp_ajax_certificados_get_survey_config', array( $this, 'ajax_get_survey_config' ) );
        add_action( 'wp_ajax_certificados_get_survey_sheet_headers', array( $this, 'ajax_get_survey_sheet_headers' ) );
        add_action( 'wp_ajax_certificados_check_survey_completed', array( $this, 'ajax_check_survey_completed' ) );

        // Frontend AJAX (para usuarios no logueados)
        add_action( 'wp_ajax_nopriv_certificados_check_survey_completed', array( $this, 'ajax_check_survey_completed' ) );
    }

    /**
     * Crear tablas si no existen
     */
    public function maybe_create_tables() {
        $version = get_option( 'certificados_survey_manager_version', '0' );

        if ( version_compare( $version, '1.0', '<' ) ) {
            $this->create_tables();
            update_option( 'certificados_survey_manager_version', '1.0' );
        }
    }

    /**
     * Crear tabla de configuración de encuestas
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_survey_config} (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL,
            survey_mode ENUM('disabled', 'optional', 'mandatory') DEFAULT 'disabled',
            survey_url TEXT,
            response_sheet_id VARCHAR(100),
            response_sheet_name VARCHAR(200),
            document_column VARCHAR(200),
            document_column_index INT DEFAULT 0,
            event_column VARCHAR(200),
            event_column_index INT DEFAULT 0,
            event_match_value VARCHAR(200),
            survey_title VARCHAR(255),
            survey_message TEXT,
            is_active TINYINT(1) DEFAULT 1,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_evento (evento_id),
            KEY idx_mode (survey_mode)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Obtener configuración de encuesta para un evento
     *
     * @param int $evento_id ID del evento
     * @return object|null
     */
    public function get_survey_config( $evento_id ) {
        global $wpdb;

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_survey_config}
             WHERE evento_id = %d AND is_active = 1",
            $evento_id
        ) );
    }

    /**
     * Guardar configuración de encuesta
     *
     * @param int $evento_id ID del evento
     * @param array $config Configuración
     * @return bool
     */
    public function save_survey_config( $evento_id, $config ) {
        global $wpdb;

        // Validar modo
        $valid_modes = array( 'disabled', 'optional', 'mandatory' );
        $survey_mode = isset( $config['survey_mode'] ) ? $config['survey_mode'] : 'disabled';

        if ( ! in_array( $survey_mode, $valid_modes ) ) {
            $survey_mode = 'disabled';
        }

        $data = array(
            'evento_id' => (int) $evento_id,
            'survey_mode' => $survey_mode,
            'survey_url' => isset( $config['survey_url'] ) ? esc_url_raw( $config['survey_url'] ) : '',
            'response_sheet_id' => isset( $config['response_sheet_id'] ) ? sanitize_text_field( $config['response_sheet_id'] ) : '',
            'response_sheet_name' => isset( $config['response_sheet_name'] ) ? sanitize_text_field( $config['response_sheet_name'] ) : '',
            'document_column' => isset( $config['document_column'] ) ? sanitize_text_field( $config['document_column'] ) : '',
            'document_column_index' => isset( $config['document_column_index'] ) ? intval( $config['document_column_index'] ) : 0,
            'event_column' => isset( $config['event_column'] ) ? sanitize_text_field( $config['event_column'] ) : '',
            'event_column_index' => isset( $config['event_column_index'] ) ? intval( $config['event_column_index'] ) : 0,
            'event_match_value' => isset( $config['event_match_value'] ) ? sanitize_text_field( $config['event_match_value'] ) : '',
            'survey_title' => isset( $config['survey_title'] ) ? sanitize_text_field( $config['survey_title'] ) : __( 'Encuesta de Satisfacción', 'certificados-digitales' ),
            'survey_message' => isset( $config['survey_message'] ) ? sanitize_textarea_field( $config['survey_message'] ) : '',
            'is_active' => 1,
            'fecha_modificacion' => current_time( 'mysql' )
        );

        // Verificar si existe configuración
        $exists = $this->get_survey_config( $evento_id );

        if ( $exists ) {
            // Actualizar
            return $wpdb->update(
                $this->table_survey_config,
                $data,
                array( 'evento_id' => $evento_id ),
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s' ),
                array( '%d' )
            );
        } else {
            // Insertar
            $data['fecha_creacion'] = current_time( 'mysql' );
            return $wpdb->insert(
                $this->table_survey_config,
                $data,
                array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
            );
        }
    }

    /**
     * Verificar si una encuesta fue completada (modo obligatorio)
     *
     * @param int $evento_id ID del evento
     * @param string $numero_documento Número de documento del usuario
     * @param string $api_key API Key de Google
     * @return array Resultado con 'completed' y 'message'
     */
    public function check_survey_completed( $evento_id, $numero_documento, $api_key ) {
        $config = $this->get_survey_config( $evento_id );

        // Si no hay configuración o está deshabilitada
        if ( ! $config || $config->survey_mode === 'disabled' ) {
            return array(
                'completed' => true,
                'required' => false,
                'message' => ''
            );
        }

        // Si es opcional, siempre permitir continuar
        if ( $config->survey_mode === 'optional' ) {
            return array(
                'completed' => true,
                'required' => false,
                'optional' => true,
                'survey_url' => $config->survey_url,
                'survey_title' => $config->survey_title,
                'survey_message' => $config->survey_message,
                'message' => __( 'Encuesta opcional disponible.', 'certificados-digitales' )
            );
        }

        // Modo obligatorio: verificar en el sheet de respuestas
        if ( $config->survey_mode === 'mandatory' ) {
            // Validar que haya configuración completa
            if ( empty( $config->response_sheet_id ) || empty( $config->response_sheet_name ) ) {
                return array(
                    'completed' => false,
                    'required' => true,
                    'message' => __( 'Configuración de encuesta incompleta. Contacte al administrador.', 'certificados-digitales' )
                );
            }

            // Buscar en el sheet de respuestas
            $found = $this->search_response_in_sheet(
                $config->response_sheet_id,
                $config->response_sheet_name,
                $api_key,
                $numero_documento,
                $config->document_column_index,
                $config->event_match_value,
                $config->event_column_index
            );

            if ( $found ) {
                return array(
                    'completed' => true,
                    'required' => true,
                    'message' => __( 'Encuesta completada. Puede proceder con la descarga.', 'certificados-digitales' )
                );
            } else {
                return array(
                    'completed' => false,
                    'required' => true,
                    'survey_url' => $config->survey_url,
                    'survey_title' => $config->survey_title,
                    'survey_message' => $config->survey_message ? $config->survey_message : __( 'Debe completar la encuesta de satisfacción antes de descargar su certificado.', 'certificados-digitales' ),
                    'message' => __( 'Debe completar la encuesta de satisfacción antes de descargar su certificado.', 'certificados-digitales' )
                );
            }
        }

        return array(
            'completed' => false,
            'required' => false,
            'message' => ''
        );
    }

    /**
     * Buscar respuesta en el sheet de encuestas
     *
     * @param string $sheet_id ID del Google Sheet de respuestas
     * @param string $sheet_name Nombre de la hoja de respuestas
     * @param string $api_key API Key de Google
     * @param string $numero_documento Número de documento a buscar
     * @param int $document_col_index Índice de la columna de documento
     * @param string $event_value Valor del evento a comparar
     * @param int $event_col_index Índice de la columna de evento
     * @return bool True si se encuentra la respuesta
     */
    private function search_response_in_sheet( $sheet_id, $sheet_name, $api_key, $numero_documento, $document_col_index, $event_value, $event_col_index ) {
        // Obtener datos del sheet de respuestas
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) || empty( $data ) ) {
            return false;
        }

        // Remover cabeceras
        array_shift( $data );

        // Buscar coincidencia
        foreach ( $data as $row ) {
            $row_document = isset( $row[ $document_col_index ] ) ? trim( $row[ $document_col_index ] ) : '';
            $row_event = isset( $row[ $event_col_index ] ) ? trim( $row[ $event_col_index ] ) : '';

            // Comparar documento
            if ( $row_document !== trim( $numero_documento ) ) {
                continue;
            }

            // Si no hay validación de evento, con el documento es suficiente
            if ( empty( $event_value ) ) {
                return true;
            }

            // Comparar evento (flexible)
            if ( $this->compare_event_values( $row_event, $event_value ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comparar valores de evento de forma flexible
     *
     * @param string $value1 Valor 1
     * @param string $value2 Valor 2
     * @return bool True si coinciden
     */
    private function compare_event_values( $value1, $value2 ) {
        $v1 = strtolower( trim( $value1 ) );
        $v2 = strtolower( trim( $value2 ) );

        // Comparación exacta
        if ( $v1 === $v2 ) {
            return true;
        }

        // Comparación parcial (contiene)
        if ( strpos( $v1, $v2 ) !== false || strpos( $v2, $v1 ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Leer cabeceras del sheet de respuestas
     *
     * @param string $sheet_id ID del Google Sheet
     * @param string $sheet_name Nombre de la hoja
     * @param string $api_key API Key de Google
     * @return array|WP_Error Array de cabeceras
     */
    public function get_response_sheet_headers( $sheet_id, $sheet_name, $api_key ) {
        $sheets_api = new Certificados_Google_Sheets( $api_key, $sheet_id );
        $data = $sheets_api->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        if ( empty( $data ) || ! isset( $data[0] ) ) {
            return new WP_Error(
                'no_headers',
                __( 'No se encontraron cabeceras en la hoja de respuestas.', 'certificados-digitales' )
            );
        }

        $headers = $data[0];
        $result = array();

        foreach ( $headers as $index => $header ) {
            if ( ! empty( trim( $header ) ) ) {
                $result[] = array(
                    'index' => $index,
                    'name' => trim( $header )
                );
            }
        }

        return $result;
    }

    /**
     * Eliminar configuración de encuesta
     *
     * @param int $evento_id ID del evento
     * @return bool
     */
    public function delete_survey_config( $evento_id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->table_survey_config,
            array( 'evento_id' => $evento_id ),
            array( '%d' )
        );
    }

    /**
     * AJAX: Guardar configuración de encuesta
     */
    public function ajax_save_survey_config() {
        check_ajax_referer( 'certificados_survey_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        $config = isset( $_POST['config'] ) ? $_POST['config'] : array();

        if ( ! $evento_id ) {
            wp_send_json_error( array( 'message' => __( 'ID de evento requerido.', 'certificados-digitales' ) ) );
        }

        $result = $this->save_survey_config( $evento_id, $config );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => __( 'Configuración de encuesta guardada correctamente.', 'certificados-digitales' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al guardar la configuración.', 'certificados-digitales' )
            ) );
        }
    }

    /**
     * AJAX: Obtener configuración de encuesta
     */
    public function ajax_get_survey_config() {
        check_ajax_referer( 'certificados_survey_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;

        if ( ! $evento_id ) {
            wp_send_json_error( array( 'message' => __( 'ID de evento requerido.', 'certificados-digitales' ) ) );
        }

        $config = $this->get_survey_config( $evento_id );

        wp_send_json_success( array(
            'config' => $config
        ) );
    }

    /**
     * AJAX: Obtener cabeceras del sheet de respuestas
     */
    public function ajax_get_survey_sheet_headers() {
        check_ajax_referer( 'certificados_survey_nonce', 'nonce' );

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

        $headers = $this->get_response_sheet_headers( $sheet_id, $sheet_name, $api_key );

        if ( is_wp_error( $headers ) ) {
            wp_send_json_error( array( 'message' => $headers->get_error_message() ) );
        }

        wp_send_json_success( array(
            'headers' => $headers
        ) );
    }

    /**
     * AJAX: Verificar si encuesta fue completada
     */
    public function ajax_check_survey_completed() {
        check_ajax_referer( 'certificados_frontend_nonce', 'nonce' );

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        $numero_documento = isset( $_POST['numero_documento'] ) ? sanitize_text_field( $_POST['numero_documento'] ) : '';
        $api_key = get_option( 'certificados_digitales_api_key', '' );

        if ( ! $evento_id || empty( $numero_documento ) ) {
            wp_send_json_error( array( 'message' => __( 'Parámetros incompletos.', 'certificados-digitales' ) ) );
        }

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API Key de Google no configurada.', 'certificados-digitales' ) ) );
        }

        $result = $this->check_survey_completed( $evento_id, $numero_documento, $api_key );

        if ( $result['completed'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Obtener configuración para el frontend (sanitizada)
     *
     * @param int $evento_id ID del evento
     * @return array|null
     */
    public function get_frontend_config( $evento_id ) {
        $config = $this->get_survey_config( $evento_id );

        if ( ! $config ) {
            return null;
        }

        return array(
            'mode' => $config->survey_mode,
            'url' => $config->survey_url,
            'title' => $config->survey_title,
            'message' => $config->survey_message,
            'required' => $config->survey_mode === 'mandatory'
        );
    }
}
