<?php
/**
 * Clase para gestionar los eventos de certificados.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Eventos.
 */
class Certificados_Digitales_Eventos {

    /**
     * Nombre de la tabla de eventos.
     *
     * @var string
     */
    private $table_eventos;

    /**
     * Nombre de la tabla de pestañas.
     *
     * @var string
     */
    private $table_pestanas;

    /**
     * Nombre de la tabla de campos.
     *
     * @var string
     */
    private $table_campos;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_eventos = $wpdb->prefix . 'certificados_eventos';
        $this->table_pestanas = $wpdb->prefix . 'certificados_pestanas';
        $this->table_campos = $wpdb->prefix . 'certificados_campos_config';

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_crear_evento', array( $this, 'ajax_crear_evento' ) );
        add_action( 'wp_ajax_certificados_actualizar_evento', array( $this, 'ajax_actualizar_evento' ) );
        add_action( 'wp_ajax_certificados_eliminar_evento', array( $this, 'ajax_eliminar_evento' ) );
        add_action( 'wp_ajax_certificados_toggle_evento', array( $this, 'ajax_toggle_evento' ) );
        add_action( 'wp_ajax_certificados_listar_eventos', array( $this, 'ajax_listar_eventos' ) );
        add_action( 'wp_ajax_certificados_obtener_evento', array( $this, 'ajax_obtener_evento' ) );
    }

    /**
     * Obtiene todos los eventos.
     *
     * @return array
     */
    public function get_all_eventos() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_eventos} ORDER BY fecha_creacion DESC" );
    }

    /**
     * Obtiene un evento por ID.
     *
     * @param int $id ID del evento.
     * @return object|null
     */
    public function get_evento_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_eventos} WHERE id = %d",
            $id
        ) );
    }

    /**
     * Obtiene las pestañas de un evento.
     *
     * @param int $evento_id ID del evento.
     * @return array
     */
    public function get_pestanas_by_evento( $evento_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_pestanas} WHERE evento_id = %d ORDER BY orden ASC",
            $evento_id
        ) );
    }

    /**
     * Cuenta las pestañas de un evento.
     *
     * @param int $evento_id ID del evento.
     * @return int
     */
    public function count_pestanas_by_evento( $evento_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_pestanas} WHERE evento_id = %d",
            $evento_id
        ) );
    }

    /**
     * Crea un nuevo evento.
     *
     * @param array $data Datos del evento.
     * @return array Resultado con success y message.
     */
    public function crear_evento( $data ) {
        global $wpdb;

        // Validar datos requeridos
        $required_fields = array( 'nombre', 'sheet_id' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return array(
                    'success' => false,
                    'message' => sprintf( __( 'El campo "%s" es obligatorio.', 'certificados-digitales' ), $field )
                );
            }
        }

        // Sanitizar datos
        $evento_data = array(
            'nombre'            => sanitize_text_field( $data['nombre'] ),
            'sheet_id'          => sanitize_text_field( $data['sheet_id'] ),
            'url_encuesta'      => ! empty( $data['url_encuesta'] ) ? esc_url_raw( $data['url_encuesta'] ) : null,
            'logo_loader_url'   => ! empty( $data['logo_loader_url'] ) ? esc_url_raw( $data['logo_loader_url'] ) : null,
            'activo'            => 1,
            'fecha_creacion'    => current_time( 'mysql' ),
            'fecha_modificacion'=> current_time( 'mysql' )
        );

        // Insertar en la base de datos
        $inserted = $wpdb->insert(
            $this->table_eventos,
            $evento_data,
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( $inserted ) {
            return array(
                'success'   => true,
                'message'   => __( 'Evento creado correctamente.', 'certificados-digitales' ),
                'evento_id' => $wpdb->insert_id
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al crear el evento.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Actualiza un evento existente.
     *
     * @param int   $id   ID del evento.
     * @param array $data Datos a actualizar.
     * @return array Resultado con success y message.
     */
    public function actualizar_evento( $id, $data ) {
        global $wpdb;

        // Verificar que el evento existe
        $evento = $this->get_evento_by_id( $id );
        if ( ! $evento ) {
            return array(
                'success' => false,
                'message' => __( 'El evento no existe.', 'certificados-digitales' )
            );
        }

        // Sanitizar datos
        $evento_data = array(
            'nombre'            => sanitize_text_field( $data['nombre'] ),
            'sheet_id'          => sanitize_text_field( $data['sheet_id'] ),
            'url_encuesta'      => ! empty( $data['url_encuesta'] ) ? esc_url_raw( $data['url_encuesta'] ) : null,
            'logo_loader_url'   => ! empty( $data['logo_loader_url'] ) ? esc_url_raw( $data['logo_loader_url'] ) : null,
            'fecha_modificacion'=> current_time( 'mysql' )
        );

        // Actualizar
        $updated = $wpdb->update(
            $this->table_eventos,
            $evento_data,
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            return array(
                'success' => true,
                'message' => __( 'Evento actualizado correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al actualizar el evento.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Elimina un evento y todas sus pestañas.
     *
     * @param int $id ID del evento.
     * @return array Resultado con success y message.
     */
    public function eliminar_evento( $id ) {
        global $wpdb;

        // Verificar que el evento existe
        $evento = $this->get_evento_by_id( $id );
        if ( ! $evento ) {
            return array(
                'success' => false,
                'message' => __( 'El evento no existe.', 'certificados-digitales' )
            );
        }

        // Las pestañas y campos se eliminan automáticamente por CASCADE
        $deleted = $wpdb->delete(
            $this->table_eventos,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            return array(
                'success' => true,
                'message' => __( 'Evento eliminado correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al eliminar el evento.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Activa o desactiva un evento.
     *
     * @param int $id ID del evento.
     * @return array Resultado con success y message.
     */
    public function toggle_evento( $id ) {
        global $wpdb;

        // Obtener estado actual
        $evento = $this->get_evento_by_id( $id );
        if ( ! $evento ) {
            return array(
                'success' => false,
                'message' => __( 'El evento no existe.', 'certificados-digitales' )
            );
        }

        // Cambiar estado
        $nuevo_estado = $evento->activo ? 0 : 1;

        $updated = $wpdb->update(
            $this->table_eventos,
            array( 'activo' => $nuevo_estado ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            $mensaje = $nuevo_estado ? __( 'Evento activado.', 'certificados-digitales' ) : __( 'Evento desactivado.', 'certificados-digitales' );
            return array(
                'success' => true,
                'message' => $mensaje,
                'nuevo_estado' => $nuevo_estado
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al cambiar el estado.', 'certificados-digitales' )
            );
        }
    }

    /**
     * AJAX: Crear evento.
     */
    public function ajax_crear_evento() {
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->crear_evento( $_POST );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Actualizar evento.
     */
    public function ajax_actualizar_evento() {
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->actualizar_evento( $id, $_POST );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Eliminar evento.
     */
    public function ajax_eliminar_evento() {
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->eliminar_evento( $id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Toggle estado del evento.
     */
    public function ajax_toggle_evento() {
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->toggle_evento( $id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Listar eventos para DataTables.
     */
    public function ajax_listar_eventos() {
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $eventos = $this->get_all_eventos();

        $data = array();
        foreach ( $eventos as $evento ) {
            $num_pestanas = $this->count_pestanas_by_evento( $evento->id );
            
            $data[] = array(
                'id'                => $evento->id,
                'nombre'            => esc_html( $evento->nombre ),
                'sheet_id'          => esc_html( $evento->sheet_id ),
                'url_encuesta'      => esc_url( $evento->url_encuesta ),
                'num_pestanas'      => $num_pestanas,
                'activo'            => $evento->activo,
                'fecha_creacion'    => date_i18n( get_option( 'date_format' ), strtotime( $evento->fecha_creacion ) )
            );
        }

        wp_send_json_success( array( 'data' => $data ) );
    }

    /**
     * AJAX: Obtener un evento por ID.
     *
     * @since 1.4.0
     */
    public function ajax_obtener_evento() {
        // Verificar nonce
        check_ajax_referer( 'certificados_eventos_nonce', 'nonce' );

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'certificados-digitales' ) ) );
        }

        // Obtener ID del evento
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID de evento no válido.', 'certificados-digitales' ) ) );
        }

        // Obtener el evento
        $evento = $this->get_evento_by_id( $id );

        if ( ! $evento ) {
            wp_send_json_error( array( 'message' => __( 'Evento no encontrado.', 'certificados-digitales' ) ) );
        }

        // Preparar datos del evento
        $data = array(
            'id'              => $evento->id,
            'nombre'          => $evento->nombre,
            'sheet_id'        => $evento->sheet_id,
            'url_encuesta'    => $evento->url_encuesta,
            'logo_loader_url' => $evento->logo_loader_url,
            'activo'          => $evento->activo,
            'fecha_creacion'  => $evento->fecha_creacion
        );

        wp_send_json_success( $data );
    }
}