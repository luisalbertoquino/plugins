<?php
/**
 * Clase para gestionar las fuentes tipográficas.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Fuentes.
 */
class Certificados_Digitales_Fuentes {

    /**
     * Nombre de la tabla de fuentes.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'certificados_fuentes';

        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_subir_fuente', array( $this, 'ajax_subir_fuente' ) );
        add_action( 'wp_ajax_certificados_eliminar_fuente', array( $this, 'ajax_eliminar_fuente' ) );
        add_action( 'wp_ajax_certificados_listar_fuentes', array( $this, 'ajax_listar_fuentes' ) );
    }

    /**
     * Obtiene todas las fuentes de la base de datos.
     *
     * @return array
     */
    public function get_all_fuentes() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY fecha_subida DESC" );
    }

    /**
     * Obtiene una fuente por ID.
     *
     * @param int $id ID de la fuente.
     * @return object|null
     */
    public function get_fuente_by_id( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ) );
    }

    /**
     * Sube una fuente al servidor y la guarda en la BD.
     *
     * @param array $file Archivo subido ($_FILES).
     * @return array Resultado con success y message.
     */
    public function subir_fuente( $file ) {
        
        // Validar que se haya subido un archivo
        if ( empty( $file['name'] ) ) {
            return array(
                'success' => false,
                'message' => __( 'No se seleccionó ningún archivo.', 'certificados-digitales' )
            );
        }

        // Validar extensión
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $file_ext !== 'ttf' ) {
            return array(
                'success' => false,
                'message' => __( 'Solo se permiten archivos .ttf', 'certificados-digitales' )
            );
        }

        // Validar tamaño (máximo 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB en bytes
        if ( $file['size'] > $max_size ) {
            return array(
                'success' => false,
                'message' => __( 'El archivo no debe superar los 2MB.', 'certificados-digitales' )
            );
        }

        // Validar tipo MIME
        $allowed_mimes = array( 'application/x-font-ttf', 'font/ttf', 'application/octet-stream' );
        if ( ! in_array( $file['type'], $allowed_mimes ) ) {
            return array(
                'success' => false,
                'message' => __( 'Tipo de archivo no válido.', 'certificados-digitales' )
            );
        }

        // Generar nombre único para evitar sobrescribir
        $nombre_fuente = sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) );
        $nombre_fuente_original = $nombre_fuente;
        $upload_dir = wp_upload_dir();
        $fuentes_dir = $upload_dir['basedir'] . '/certificados-digitales/fuentes/';
        
        // Verificar si el nombre ya existe
        $counter = 1;
        $archivo_nombre = $nombre_fuente . '.ttf';
        while ( file_exists( $fuentes_dir . $archivo_nombre ) ) {
            $nombre_fuente = $nombre_fuente_original . '-' . $counter;
            $archivo_nombre = $nombre_fuente . '.ttf';
            $counter++;
        }

        // Mover archivo
        $archivo_path = $fuentes_dir . $archivo_nombre;
        if ( ! move_uploaded_file( $file['tmp_name'], $archivo_path ) ) {
            return array(
                'success' => false,
                'message' => __( 'Error al subir el archivo al servidor.', 'certificados-digitales' )
            );
        }

        // Guardar en la base de datos
        global $wpdb;
        $archivo_url = $upload_dir['baseurl'] . '/certificados-digitales/fuentes/' . $archivo_nombre;

        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'nombre_fuente' => $nombre_fuente,
                'archivo_url'   => $archivo_url,
                'fecha_subida'  => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s' )
        );

        if ( $inserted ) {
            // Convertir fuente para TCPDF
            $tcpdf_result = $this->convertir_fuente_tcpdf( $archivo_path, $nombre_fuente );
            
            if ( $tcpdf_result['success'] ) {
                return array(
                    'success' => true,
                    'message' => __( 'Fuente subida y convertida correctamente.', 'certificados-digitales' ),
                    'fuente_id' => $wpdb->insert_id,
                    'tcpdf_name' => $tcpdf_result['tcpdf_name']
                );
            } else {
                return array(
                    'success' => true,
                    'message' => __( 'Fuente subida, pero no se pudo convertir para PDF. Se usará fuente estándar.', 'certificados-digitales' ),
                    'fuente_id' => $wpdb->insert_id,
                    'warning' => $tcpdf_result['message']
                );
            }
        } else {
            // Si falla la BD, eliminar el archivo
            unlink( $archivo_path );
            return array(
                'success' => false,
                'message' => __( 'Error al guardar en la base de datos.', 'certificados-digitales' )
            );
        }
    }

    /**
     * Elimina una fuente.
     *
     * @param int $id ID de la fuente.
     * @return array Resultado con success y message.
     */
    public function eliminar_fuente( $id ) {
        global $wpdb;

        // Obtener información de la fuente
        $fuente = $this->get_fuente_by_id( $id );
        
        if ( ! $fuente ) {
            return array(
                'success' => false,
                'message' => __( 'La fuente no existe.', 'certificados-digitales' )
            );
        }

        // Eliminar archivo físico
        $upload_dir = wp_upload_dir();
        $archivo_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $fuente->archivo_url );
        
        if ( file_exists( $archivo_path ) ) {
            unlink( $archivo_path );
        }

        // Eliminar de la base de datos
        $deleted = $wpdb->delete(
            $this->table_name,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $deleted ) {
            return array(
                'success' => true,
                'message' => __( 'Fuente eliminada correctamente.', 'certificados-digitales' )
            );
        } else {
            return array(
                'success' => false,
                'message' => __( 'Error al eliminar la fuente.', 'certificados-digitales' )
            );
        }
    }

    /**
     * AJAX: Subir fuente.
     */
    public function ajax_subir_fuente() {
        
        // Verificar nonce
        check_ajax_referer( 'certificados_fuentes_nonce', 'nonce' );

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        // Verificar que se haya subido un archivo
        if ( empty( $_FILES['fuente_archivo'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No se recibió ningún archivo.', 'certificados-digitales' ) ) );
        }

        // Subir fuente
        $resultado = $this->subir_fuente( $_FILES['fuente_archivo'] );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Eliminar fuente.
     */
    public function ajax_eliminar_fuente() {
        
        // Verificar nonce
        check_ajax_referer( 'certificados_fuentes_nonce', 'nonce' );

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        // Obtener ID
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'certificados-digitales' ) ) );
        }

        // Eliminar fuente
        $resultado = $this->eliminar_fuente( $id );

        if ( $resultado['success'] ) {
            wp_send_json_success( $resultado );
        } else {
            wp_send_json_error( $resultado );
        }
    }

    /**
     * AJAX: Listar fuentes para DataTables.
     */
    public function ajax_listar_fuentes() {
        
        // Verificar nonce
        check_ajax_referer( 'certificados_fuentes_nonce', 'nonce' );

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        // Obtener fuentes
        $fuentes = $this->get_all_fuentes();

        // Formatear datos para DataTables
        $data = array();
        foreach ( $fuentes as $fuente ) {
            $data[] = array(
                'id' => $fuente->id,
                'nombre_fuente' => esc_html( $fuente->nombre_fuente ),
                'archivo_url' => esc_url( $fuente->archivo_url ),
                'fecha_subida' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $fuente->fecha_subida ) )
            );
        }

        wp_send_json_success( array( 'data' => $data ) );
    }



    /**
     * Convertir fuente TTF para uso en TCPDF
     * 
     * @param string $font_path Ruta del archivo TTF
     * @param string $font_name Nombre de la fuente
     * @return array Resultado con success y tcpdf_name
     */
    private function convertir_fuente_tcpdf( $font_path, $font_name ) {
        try {
            // Verificar que la librería TCPDF esté disponible
            $tcpdf_path = CERTIFICADOS_DIGITALES_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
            if ( ! file_exists( $tcpdf_path ) ) {
                return array(
                    'success' => false,
                    'message' => 'TCPDF no encontrado'
                );
            }

            require_once $tcpdf_path;
            require_once CERTIFICADOS_DIGITALES_PATH . 'vendor/tecnickcom/tcpdf/include/tcpdf_fonts.php';

            // Crear directorio de fuentes TCPDF si no existe
            $tcpdf_fonts_dir = CERTIFICADOS_DIGITALES_PATH . 'vendor/tecnickcom/tcpdf/fonts/';
            
            // Nombre de fuente para TCPDF (minúsculas, sin espacios)
            $tcpdf_font_name = strtolower( str_replace( array( ' ', '-' ), '', $font_name ) );
            
            // Convertir fuente usando TCPDF
            $converted = \TCPDF_FONTS::addTTFfont(
                $font_path,
                'TrueTypeUnicode',
                '',
                96,
                $tcpdf_fonts_dir
            );

            if ( $converted ) {
                // Actualizar BD con el nombre TCPDF
                global $wpdb;
                $wpdb->update(
                    $this->table_name,
                    array( 'tcpdf_name' => $converted ),
                    array( 'nombre_fuente' => $font_name ),
                    array( '%s' ),
                    array( '%s' )
                );

                return array(
                    'success' => true,
                    'tcpdf_name' => $converted
                );
            }

            return array(
                'success' => false,
                'message' => 'Error al convertir fuente'
            );

        } catch ( Exception $e ) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}