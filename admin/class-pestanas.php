<?php
/**
 * Clase para gestionar las pestañas de eventos.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Pestañas.
 */
class Certificados_Digitales_Pestanas {

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
        $this->table_pestanas = $wpdb->prefix . 'certificados_pestanas';
        $this->table_campos = $wpdb->prefix . 'certificados_campos_config';

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_crear_pestana', array( $this, 'ajax_crear_pestana' ) );
        add_action( 'wp_ajax_certificados_actualizar_pestana', array( $this, 'ajax_actualizar_pestana' ) );
        add_action( 'wp_ajax_certificados_eliminar_pestana', array( $this, 'ajax_eliminar_pestana' ) );
        add_action( 'wp_ajax_certificados_obtener_pestana', array( $this, 'ajax_obtener_pestana' ) );
        add_action( 'wp_ajax_certificados_reordenar_pestanas', array( $this, 'ajax_reordenar_pestanas' ) );
        add_action( 'wp_ajax_certificados_subir_plantilla_pestana', array( $this, 'ajax_subir_plantilla_pestana' ) );
    }

    /**
     * Obtiene todas las pestañas de un evento.
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
     * Obtiene una pestaña por ID.
     *
     * @param int $id ID de la pestaña.
     * @return object|null
     */
    public function get_pestana_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_pestanas} WHERE id = %d",
            $id
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
     * Crea una nueva pestaña.
     *
     * @param array $data Datos de la pestaña.
     * @return array Resultado con success y message.
     */
    public function crear_pestana( $data ) {
        global $wpdb;

        // Validar datos requeridos
        $required_fields = array( 'evento_id', 'nombre_pestana', 'nombre_hoja_sheet' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return array(
                    'success' => false,
                    'message' => sprintf( __( 'El campo "%s" es obligatorio.', 'certificados-digitales' ), $field )
                );
            }
        }

        $evento_id = intval( $data['evento_id'] );

        // Validar límite de 5 pestañas por evento
        $count = $this->count_pestanas_by_evento( $evento_id );
        if ( $count >= 5 ) {
            return array(
                'success' => false,
                'message' => __( 'No puedes agregar más de 5 pestañas por evento.', 'certificados-digitales' )
            );
        }

        // Obtener el siguiente orden
        $max_orden = $wpdb->get_var( $wpdb->prepare(
            "SELECT MAX(orden) FROM {$this->table_pestanas} WHERE evento_id = %d",
            $evento_id
        ) );
        $nuevo_orden = $max_orden !== null ? intval( $max_orden ) + 1 : 1;

        // Sanitizar datos
        $pestana_data = array(
            'evento_id'         => $evento_id,
            'nombre_pestana'    => sanitize_text_field( $data['nombre_pestana'] ),
            'nombre_hoja_sheet' => sanitize_text_field( $data['nombre_hoja_sheet'] ),
            'plantilla_url'     => ! empty( $data['plantilla_url'] ) ? esc_url_raw( $data['plantilla_url'] ) : '',
            'orden'             => $nuevo_orden,
            'activo'            => 1
        );

        // Insertar en la base de datos
        $inserted = $wpdb->insert(
            $this->table_pestanas,
            $pestana_data,
            array( '%d', '%s', '%s', '%s', '%d', '%d' )
        );

        if ( $inserted ) {
            return array(
                'success'    => true,
                'message'    => __( 'Pestaña creada correctamente.', 'certificados-digitales' ),
                'pestana_id' => $wpdb->insert_id
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al crear la pestaña.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Actualiza una pestaña existente.
     *
     * @param int   $id   ID de la pestaña.
     * @param array $data Datos a actualizar.
     * @return array Resultado con success y message.
     */
    public function actualizar_pestana( $id, $data ) {
        global $wpdb;

        // Verificar que la pestaña existe
        $pestana = $this->get_pestana_by_id( $id );
        if ( ! $pestana ) {
            return array(
                'success' => false,
                'message' => __( 'La pestaña no existe.', 'certificados-digitales' )
            );
        }

        // Sanitizar datos
        $pestana_data = array(
            'nombre_pestana'    => sanitize_text_field( $data['nombre_pestana'] ),
            'nombre_hoja_sheet' => sanitize_text_field( $data['nombre_hoja_sheet'] ),
        );

        // Solo actualizar plantilla si se proporciona
        if ( ! empty( $data['plantilla_url'] ) ) {
            $pestana_data['plantilla_url'] = esc_url_raw( $data['plantilla_url'] );
        }

        // Actualizar
        $updated = $wpdb->update(
            $this->table_pestanas,
            $pestana_data,
            array( 'id' => $id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $updated !== false ) {
            // LIMPIAR CACHÉ: Si se actualizó la plantilla, regenerar certificados
            if ( ! empty( $data['plantilla_url'] ) ) {
                $wpdb->delete(
                    $wpdb->prefix . 'certificados_cache',
                    array( 'pestana_id' => $id ),
                    array( '%d' )
                );
                
                error_log( sprintf( 
                    'Certificados: Caché limpiado de la pestaña %d (plantilla actualizada)', 
                    $id 
                ) );
            }

            return array(
                'success' => true,
                'message' => __( 'Pestaña actualizada correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al actualizar la pestaña.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Elimina una pestaña.
     *
     * @param int $id ID de la pestaña.
     * @return array Resultado con success y message.
     */
    public function eliminar_pestana( $id ) {
        global $wpdb;

        // Verificar que la pestaña existe
        $pestana = $this->get_pestana_by_id( $id );
        if ( ! $pestana ) {
            return array(
                'success' => false,
                'message' => __( 'La pestaña no existe.', 'certificados-digitales' )
            );
        }

        // Eliminar imagen de plantilla si existe
        if ( ! empty( $pestana->plantilla_url ) ) {
            $upload_dir = wp_upload_dir();
            $archivo_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $pestana->plantilla_url );
            if ( file_exists( $archivo_path ) ) {
                unlink( $archivo_path );
            }
        }

        // Los campos se eliminan automáticamente por CASCADE
        $deleted = $wpdb->delete(
            $this->table_pestanas,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            // Reordenar las pestañas restantes
            $this->reordenar_pestanas_evento( $pestana->evento_id );

            return array(
                'success' => true,
                'message' => __( 'Pestaña eliminada correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al eliminar la pestaña.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Reordena las pestañas de un evento.
     *
     * @param int $evento_id ID del evento.
     */
    private function reordenar_pestanas_evento( $evento_id ) {
        global $wpdb;
        
        $pestanas = $this->get_pestanas_by_evento( $evento_id );
        $orden = 1;
        
        foreach ( $pestanas as $pestana ) {
            $wpdb->update(
                $this->table_pestanas,
                array( 'orden' => $orden ),
                array( 'id' => $pestana->id ),
                array( '%d' ),
                array( '%d' )
            );
            $orden++;
        }
    }

    /**
     * Sube una plantilla de certificado.
     *
     * @param array $file    Archivo subido ($_FILES).
     * @param int   $evento_id ID del evento.
     * @return array Resultado con success, message y url.
     */
    public function subir_plantilla( $file, $evento_id ) {
        
        // Validar que se haya subido un archivo
        if ( empty( $file['name'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'No se seleccionó ningún archivo.', 'certificados-digitales' )
            );
        }

        // Validar extensión
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        $allowed_exts = array( 'jpg', 'jpeg', 'png' );
        if ( ! in_array( $file_ext, $allowed_exts ) ) {
            return array(
                'success' => false,
                'message' => __( 'Solo se permiten archivos JPG y PNG.', 'certificados-digitales' )
            );
        }

        // Validar tamaño (máximo 5MB)
        $max_size = 5 * 1024 * 1024;
        if ( $file['size'] > $max_size ) {
            return array(
                'success' => false,
                'message' => __( 'El archivo no debe superar los 5MB.', 'certificados-digitales' )
            );
        }

        // Generar nombre único
        $upload_dir = wp_upload_dir();
        $plantillas_dir = $upload_dir['basedir'] . '/certificados-digitales/plantillas/';
        
        $filename = 'evento-' . $evento_id . '-' . time() . '.' . $file_ext;
        $filepath = $plantillas_dir . $filename;

        // Mover archivo
        if ( ! move_uploaded_file( $file['tmp_name'], $filepath ) ) {
            return array(
                'success' => false,
                'message' => __( 'Error al subir el archivo.', 'certificados-digitales' )
            );
        }

        $file_url = $upload_dir['baseurl'] . '/certificados-digitales/plantillas/' . $filename;

        return array(
            'success' => true,
            'message' => __( 'Plantilla subida correctamente.', 'certificados-digitales' ),
            'url'     => $file_url
        );
    }

    /**
     * AJAX: Crear pestaña.
     */
    public function ajax_crear_pestana() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->crear_pestana( $_POST );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Actualizar pestaña.
     */
    public function ajax_actualizar_pestana() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->actualizar_pestana( $id, $_POST );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Eliminar pestaña.
     */
    public function ajax_eliminar_pestana() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->eliminar_pestana( $id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Obtener datos de una pestaña.
     */
    public function ajax_obtener_pestana() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $pestana = $this->get_pestana_by_id( $id );

        if ( $pestana ) {
            wp_send_json_success( $pestana );
        } else {
            wp_send_json_error( array( 'message' => __( 'Pestaña no encontrada.', 'certificados-digitales' ) ) );
        }
    }

    /**
     * AJAX: Reordenar pestañas.
     */
    public function ajax_reordenar_pestanas() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $orden = isset( $_POST['orden'] ) ? $_POST['orden'] : array();
        
        if ( empty( $orden ) || ! is_array( $orden ) ) {
            wp_send_json_error( array( 'message' => __( 'Datos inválidos.', 'certificados-digitales' ) ) );
        }

        global $wpdb;
        $position = 1;

        foreach ( $orden as $pestana_id ) {
            $wpdb->update(
                $this->table_pestanas,
                array( 'orden' => $position ),
                array( 'id' => intval( $pestana_id ) ),
                array( '%d' ),
                array( '%d' )
            );
            $position++;
        }

        wp_send_json_success( array( 'message' => __( 'Orden actualizado.', 'certificados-digitales' ) ) );
    }




    /**
     * AJAX: Subir plantilla.
     */
    public function ajax_subir_plantilla_pestana() {
        check_ajax_referer( 'certificados_pestanas_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        // Verificar que se haya subido un archivo
        if ( empty( $_FILES['plantilla_archivo'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No se recibió ningún archivo.', 'certificados-digitales' ) ) );
        }

        $evento_id = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;
        if ( ! $evento_id ) {
            wp_send_json_error( array( 'message' => __( 'ID de evento inválido.', 'certificados-digitales' ) ) );
        }

        // Subir plantilla
        $resultado = $this->subir_plantilla( $_FILES['plantilla_archivo'], $evento_id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }


}