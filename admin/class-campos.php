<?php
/**
 * Clase para gestionar los campos de configuración de certificados.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Campos.
 */
class Certificados_Digitales_Campos {

    /**
     * Nombre de la tabla de campos.
     *
     * @var string
     */
    private $table_campos;

    /**
     * Tipos de campos disponibles.
     *
     * @var array
     */
    private $tipos_campos = array(
        'nombre',
        'documento',
        'trabajo',
        'qr',
        'fecha_emision'
    );

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_campos = $wpdb->prefix . 'certificados_campos_config';

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_guardar_campos', array( $this, 'ajax_guardar_campos' ) );
        add_action( 'wp_ajax_certificados_obtener_campos', array( $this, 'ajax_obtener_campos' ) );
        add_action( 'wp_ajax_certificados_eliminar_campo', array( $this, 'ajax_eliminar_campo' ) );
    }

    /**
     * Obtiene todos los campos de una pestaña.
     *
     * @param int $pestana_id ID de la pestaña.
     * @return array
     */
    public function get_campos_by_pestana( $pestana_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table_campos} WHERE pestana_id = %d AND activo = 1",
            $pestana_id
        ) );
    }

    /**
     * Obtiene un campo por ID.
     *
     * @param int $id ID del campo.
     * @return object|null
     */
    public function get_campo_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_campos} WHERE id = %d",
            $id
        ) );
    }

    /**
     * Guarda o actualiza un campo.
     *
     * @param array $data Datos del campo.
     * @return array Resultado con success y message.
     */
    public function guardar_campo( $data ) {
        global $wpdb;

        // Validar datos requeridos
        $required_fields = array( 'pestana_id', 'campo_tipo' );
        foreach ( $required_fields as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return array(
                    'success' => false,
                    'message' => sprintf( __( 'El campo "%s" es obligatorio.', 'certificados-digitales' ), $field )
                );
            }
        }

        // Guardar dimensiones de display como opción (primera vez que se guarda)
        if ( ! empty( $data['plantilla_display_width'] ) && ! empty( $data['plantilla_display_height'] ) ) {
            update_option(
                'certificados_plantilla_display_' . $data['pestana_id'],
                array(
                    'width' => intval( $data['plantilla_display_width'] ),
                    'height' => intval( $data['plantilla_display_height'] )
                )
            );
        }

        // Validar que el tipo de campo sea válido
        if ( ! in_array( $data['campo_tipo'], $this->tipos_campos ) ) {
            return array(
                'success' => false,
                'message' => __( 'Tipo de campo no válido.', 'certificados-digitales' )
            );
        }

        $pestana_id = intval( $data['pestana_id'] );
        $campo_tipo = sanitize_text_field( $data['campo_tipo'] );

        // Verificar si ya existe un campo de este tipo para esta pestaña
        $campo_existente = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$this->table_campos} WHERE pestana_id = %d AND campo_tipo = %s",
            $pestana_id,
            $campo_tipo
        ) );

        // Preparar datos
        $campo_data = array(
            'pestana_id'    => $pestana_id,
            'campo_tipo'    => $campo_tipo,
            'posicion_top'  => isset( $data['posicion_top'] ) ? floatval( $data['posicion_top'] ) : null,
            'posicion_left' => isset( $data['posicion_left'] ) ? floatval( $data['posicion_left'] ) : null,
            'font_size'     => isset( $data['font_size'] ) ? intval( $data['font_size'] ) : null,
            'font_family'   => isset( $data['font_family'] ) ? sanitize_text_field( $data['font_family'] ) : null,
            'font_style'    => isset( $data['font_style'] ) ? sanitize_text_field( $data['font_style'] ) : 'normal',
            'color'         => isset( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : null,
            'alineacion'    => isset( $data['alineacion'] ) ? sanitize_text_field( $data['alineacion'] ) : 'center',
            'qr_size'       => isset( $data['qr_size'] ) ? intval( $data['qr_size'] ) : 20,
            'activo'        => 1
        );

        $campo_format = array( '%d', '%s', '%f', '%f', '%d', '%s', '%s', '%s', '%s', '%d', '%d' );

        if ( $campo_existente ) {
            // ========================================
            // ACTUALIZAR CAMPO EXISTENTE
            // ========================================
            $updated = $wpdb->update(
                $this->table_campos,
                $campo_data,
                array( 'id' => $campo_existente->id ),
                $campo_format,
                array( '%d' )
            );

            if ( $updated !== false ) {
                // Guardar dimensiones de plantilla mostrada si vienen
                if ( isset( $data['plantilla_display_width'] ) && isset( $data['plantilla_display_height'] ) ) {
                    $display_info = array(
                        'width'  => intval( $data['plantilla_display_width'] ),
                        'height' => intval( $data['plantilla_display_height'] )
                    );
                    update_option( 'certificados_plantilla_display_' . $pestana_id, $display_info );
                }

                // Guardar ancho de texto mostrado por campo (px) en una opción por pestaña
                if ( isset( $data['display_text_width'] ) ) {
                    $map = get_option( 'certificados_campos_display_' . $pestana_id, array() );
                    $map[ $campo_tipo ] = intval( $data['display_text_width'] );
                    update_option( 'certificados_campos_display_' . $pestana_id, $map );
                }

                // LIMPIAR CACHÉ: Los certificados de esta pestaña deben regenerarse
                $this->limpiar_cache_pestana( $pestana_id );

                return array(
                    'success'  => true,
                    'message'  => __( 'Campo actualizado correctamente.', 'certificados-digitales' ),
                    'campo_id' => $campo_existente->id
                );
            }
        } else {
            // ========================================
            // INSERTAR NUEVO CAMPO
            // ========================================
            $inserted = $wpdb->insert(
                $this->table_campos,
                $campo_data,
                $campo_format
            );

            if ( $inserted ) {
                // Guardar dimensiones de plantilla mostrada si vienen
                if ( isset( $data['plantilla_display_width'] ) && isset( $data['plantilla_display_height'] ) ) {
                    $display_info = array(
                        'width'  => intval( $data['plantilla_display_width'] ),
                        'height' => intval( $data['plantilla_display_height'] )
                    );
                    update_option( 'certificados_plantilla_display_' . $pestana_id, $display_info );
                }

                // Guardar ancho de texto mostrado por campo (px) en una opción por pestaña
                if ( isset( $data['display_text_width'] ) ) {
                    $map = get_option( 'certificados_campos_display_' . $pestana_id, array() );
                    $map[ $campo_tipo ] = intval( $data['display_text_width'] );
                    update_option( 'certificados_campos_display_' . $pestana_id, $map );
                }

                // LIMPIAR CACHÉ: Los certificados de esta pestaña deben regenerarse
                $this->limpiar_cache_pestana( $pestana_id );

                return array(
                    'success'  => true,
                    'message'  => __( 'Campo guardado correctamente.', 'certificados-digitales' ),
                    'campo_id' => $wpdb->insert_id
                );
            }
        }

        return array(
            'success' => false,
            'message' => __( 'Error al guardar el campo.', 'certificados-digitales' )
        );
    }

    /**
     * Elimina un campo.
     *
     * @param int $id ID del campo.
     * @return array Resultado con success y message.
     */
    public function eliminar_campo( $id ) {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->table_campos,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            return array(
                'success' => true,
                'message' => __( 'Campo eliminado correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al eliminar el campo.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Obtiene los tipos de campos disponibles.
     *
     * @return array
     */
    public function get_tipos_campos() {
        return $this->tipos_campos;
    }

    /**
     * Obtiene la etiqueta de un tipo de campo.
     *
     * @param string $tipo Tipo de campo.
     * @return string
     */
    public function get_label_campo( $tipo ) {
        $labels = array(
            'nombre'        => __( 'Nombre', 'certificados-digitales' ),
            'documento'     => __( 'Documento', 'certificados-digitales' ),
            'trabajo'       => __( 'Trabajo/Título', 'certificados-digitales' ),
            'qr'            => __( 'Código QR', 'certificados-digitales' ),
            'fecha_emision' => __( 'Fecha de Emisión', 'certificados-digitales' ),
        );

        return isset( $labels[ $tipo ] ) ? $labels[ $tipo ] : $tipo;
    }

    /**
     * AJAX: Guardar campos.
     */
    public function ajax_guardar_campos() {
        check_ajax_referer( 'certificados_campos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->guardar_campo( $_POST );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Obtener campos de una pestaña.
     */
    public function ajax_obtener_campos() {
        check_ajax_referer( 'certificados_campos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $pestana_id = isset( $_POST['pestana_id'] ) ? intval( $_POST['pestana_id'] ) : 0;
        if ( ! $pestana_id ) {
            wp_send_json_error( array( 'message' => __( 'ID de pestaña inválido.', 'certificados-digitales' ) ) );
        }

        $campos = $this->get_campos_by_pestana( $pestana_id );

        wp_send_json_success( array( 'campos' => $campos ) );
    }

    /**
     * AJAX: Eliminar campo.
     */
    public function ajax_eliminar_campo() {
        check_ajax_referer( 'certificados_campos_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        $resultado = $this->eliminar_campo( $id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }


    /**
     * Limpiar caché de certificados de una pestaña
     * 
     * @param int $pestana_id ID de la pestaña
     */
    private function limpiar_cache_pestana( $pestana_id ) {
        global $wpdb;
        
        // Eliminar registros de caché
        $eliminados = $wpdb->delete(
            $wpdb->prefix . 'certificados_cache',
            array( 'pestana_id' => $pestana_id ),
            array( '%d' )
        );
        
        if ( $eliminados ) {
            error_log( sprintf( 
                'Certificados: Limpiados %d certificados en caché de la pestaña %d', 
                $eliminados, 
                $pestana_id 
            ) );
        }
        
        return $eliminados;
    }
}