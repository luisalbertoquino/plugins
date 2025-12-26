<?php
/**
 * Clase para manejar el shortcode del frontend
 * 
 * @package Certificados_Digitales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'certificados_evento', array( $this, 'render_shortcode' ) );

        // Registrar shortcodes dinámicos definidos en la opción `certificados_shortcodes`
        $dynamic = get_option( 'certificados_shortcodes', array() );
        if ( is_array( $dynamic ) && ! empty( $dynamic ) ) {
            foreach ( $dynamic as $slug => $evento_id ) {
                $tag = 'certificados_' . sanitize_title( $slug );

                // Crear un closure que capture el evento y delegue al renderizador
                $_this = $this;
                add_shortcode( $tag, function( $atts = array(), $content = null ) use ( $evento_id, $_this ) {
                    $atts = is_array( $atts ) ? $atts : array();
                    $atts['evento_id'] = intval( $evento_id );
                    return $_this->render_shortcode( $atts );
                } );
            }
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Cargar scripts y estilos del frontend
     */
    public function enqueue_scripts() {
        // Solo cargar si hay un shortcode en la página
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has = false;

        // Comprobar el shortcode base
        if ( has_shortcode( $post->post_content, 'certificados_evento' ) ) {
            $has = true;
        }

        // Comprobar shortcodes dinámicos
        if ( ! $has ) {
            $dynamic = get_option( 'certificados_shortcodes', array() );
            if ( is_array( $dynamic ) && ! empty( $dynamic ) ) {
                foreach ( $dynamic as $slug => $evento_id ) {
                    $tag = 'certificados_' . sanitize_title( $slug );
                    if ( has_shortcode( $post->post_content, $tag ) ) {
                        $has = true;
                        break;
                    }
                }
            }
        }

        if ( ! $has ) {
            return;
        }

        // CSS del frontend
        wp_enqueue_style(
            'certificados-frontend',
            CERTIFICADOS_DIGITALES_URL . 'public/css/certificados-frontend.css',
            array(),
            CERTIFICADOS_DIGITALES_VERSION
        );

        // CSS del loader
        wp_enqueue_style(
            'certificados-loader',
            CERTIFICADOS_DIGITALES_URL . 'public/css/loader.css',
            array(),
            CERTIFICADOS_DIGITALES_VERSION
        );

        // JavaScript del frontend
        wp_enqueue_script(
            'certificados-frontend',
            CERTIFICADOS_DIGITALES_URL . 'public/js/certificados-frontend.js',
            array( 'jquery' ),
            CERTIFICADOS_DIGITALES_VERSION,
            true
        );

        // Localizar script
        wp_localize_script(
            'certificados-frontend',
            'certificadosFrontend',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'certificados_frontend_nonce' ),
                'i18n' => array(
                    'buscando' => __( 'Buscando certificado...', 'certificados-digitales' ),
                    'generando' => __( 'Generando certificado...', 'certificados-digitales' ),
                    'descargando' => __( 'Preparando descarga...', 'certificados-digitales' ),
                    'error' => __( 'Error al procesar la solicitud.', 'certificados-digitales' ),
                    'documentoRequerido' => __( 'Por favor ingresa tu número de documento.', 'certificados-digitales' ),
                )
            )
        );
    }

    /**
     * Renderizar el shortcode
     * 
     * @param array $atts Atributos del shortcode
     * @return string HTML del shortcode
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'evento_id' => 0,
        ), $atts, 'certificados_evento' );

        $evento_id = intval( $atts['evento_id'] );

        if ( ! $evento_id ) {
            return '<div class="certificados-error">' . __( 'ID de evento no especificado.', 'certificados-digitales' ) . '</div>';
        }

        // Obtener datos del evento
        global $wpdb;
        $evento = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                $evento_id
            ),
            ARRAY_A
        );

        if ( ! $evento ) {
            return '<div class="certificados-error">' . __( 'Evento no encontrado.', 'certificados-digitales' ) . '</div>';
        }

        // Establecer logo por defecto si no hay logo_loader_url
        if ( empty( $evento['logo_loader_url'] ) ) {
            $evento['logo_loader_url'] = CERTIFICADOS_DIGITALES_URL . 'public/img/loader.png';
        }


        // Obtener pestañas del evento
        $pestanas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_pestanas WHERE evento_id = %d ORDER BY orden ASC",
                $evento_id
            ),
            ARRAY_A
        );

        if ( empty( $pestanas ) ) {
            return '<div class="certificados-error">' . __( 'No hay certificados disponibles para este evento.', 'certificados-digitales' ) . '</div>';
        }

        // Generar HTML
        ob_start();
        ?>
        <div class="certificados-container" data-evento-id="<?php echo esc_attr( $evento_id ); ?>" data-logo-url="<?php echo esc_url( $evento['logo_loader_url'] ); ?>">
            
            <!-- Pantalla de carga -->
            <div class="certificados-loader" style="display: none;">
                <div class="certificados-loader-content">
                    <div class="certificados-loader-spinner">
                        <img src="" alt="Loading" class="certificados-loader-img" />
                    </div>
                </div>
            </div>
            
            <div class="certificados-header">
                <h2>Certificados - <?php echo esc_html( $evento['nombre'] ); ?></h2>
                <?php if ( isset( $evento['descripcion'] ) && ! empty( $evento['descripcion'] ) ) : ?>
                    <p class="certificados-descripcion"><?php echo esc_html( $evento['descripcion'] ); ?></p>
                <?php endif; ?>
            </div>

            <?php if ( count( $pestanas ) > 1 ) : ?>
                <!-- Pestañas de navegación -->
                <div class="certificados-tabs">
                    <?php foreach ( $pestanas as $index => $pestana ) : ?>
                        <button class="certificados-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-tab="tab-<?php echo esc_attr( $pestana['id'] ); ?>">
                            <?php echo isset( $pestana['nombre_pestana'] ) ? esc_html( $pestana['nombre_pestana'] ) : 'Certificado'; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Contenido de las pestañas -->
            <div class="certificados-tabs-content">
                <?php foreach ( $pestanas as $index => $pestana ) : ?>
                    <div class="certificados-tab-panel <?php echo $index === 0 ? 'active' : ''; ?>" 
                         id="tab-<?php echo esc_attr( $pestana['id'] ); ?>"
                         data-pestana-id="<?php echo esc_attr( $pestana['id'] ); ?>">
                        
                        <div class="certificados-form-container">
                    <p class="certificados-subtitulo">Certificado de <?php echo esc_html( $pestana['nombre_pestana'] ); ?></p>
                
                    <form class="certificados-form">
                                <div class="form-group">
                                    <label for="documento-<?php echo esc_attr( $pestana['id'] ); ?>">
                                        <?php _e( 'Número de Documento', 'certificados-digitales' ); ?>
                                    </label>
                                    <input type="text"
                                           id="documento-<?php echo esc_attr( $pestana['id'] ); ?>"
                                           name="numero_documento"
                                           class="certificados-input"
                                           placeholder="<?php esc_attr_e( 'Ingresa tu número de documento', 'certificados-digitales' ); ?>"
                                           maxlength="20"
                                           pattern="[a-zA-Z0-9\-\s]+"
                                           title="<?php esc_attr_e( 'Solo se permiten letras, números, guiones y espacios (máximo 20 caracteres)', 'certificados-digitales' ); ?>"
                                           required>
                                </div>

                                <button type="submit" class="certificados-btn-buscar">
                                    <span class="btn-text"><?php _e( '🔍 Buscar Certificado', 'certificados-digitales' ); ?></span>
                                    <span class="btn-loading" style="display: none;"><?php _e( '⏳ Buscando...', 'certificados-digitales' ); ?></span>
                                </button>
                            </form>

                            <div class="certificados-result" style="display: none;">
                                <div class="result-success">
                                    <p class="result-message"></p>
                                    <button class="certificados-btn-download">
                                        <?php _e( '📥 Descargar Certificado', 'certificados-digitales' ); ?>
                                    </button>
                                </div>
                                <div class="result-error">
                                    <p class="error-message"></p>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Modal de validación de certificado -->
        <div id="certificados-modal-validacion" class="certificados-modal certificados-modal-validacion" style="display: none;">
            <div class="modal-content modal-validacion-content">
                <div class="modal-header">
                    <h3><?php _e( 'Validación de Certificado', 'certificados-digitales' ); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="validacion-loading" style="display: none;">
                        <div class="spinner"></div>
                        <p><?php _e( 'Verificando certificado...', 'certificados-digitales' ); ?></p>
                    </div>
                    <div class="validacion-resultado" style="display: none;">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <?php if ( ! empty( $evento['url_encuesta'] ) ) : ?>
            <!-- Modal de encuesta -->
            <div id="certificados-modal-encuesta" class="certificados-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e( '¡Tu certificado está listo!', 'certificados-digitales' ); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p><?php _e( '¿Te gustaría ayudarnos completando una breve encuesta?', 'certificados-digitales' ); ?></p>
                        <div class="modal-actions">
                            <a href="<?php echo esc_url( $evento['url_encuesta'] ); ?>"
                               target="_blank"
                               class="certificados-btn-primary">
                                <?php _e( 'Sí, completar encuesta', 'certificados-digitales' ); ?>
                            </a>
                            <button class="certificados-btn-secondary modal-close">
                                <?php _e( 'No, gracias', 'certificados-digitales' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}

// Inicializar
new Certificados_Shortcode();