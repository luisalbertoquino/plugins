<?php
/**
 * Clase para gestionar shortcodes desde el admin
 *
 * @package Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Certificados_Digitales_Shortcodes {

    private $option_key = 'certificados_shortcodes';

    public function __construct() {
        add_action( 'wp_ajax_certificados_shortcodes_save', array( $this, 'ajax_save_shortcode' ) );
        add_action( 'wp_ajax_certificados_shortcodes_delete', array( $this, 'ajax_delete_shortcode' ) );
    }

    /**
     * Render page
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener shortcodes existentes
        $items = get_option( $this->option_key, array() );

        // Obtener eventos para seleccionar
        global $wpdb;
        $eventos = $wpdb->get_results( "SELECT id, nombre FROM {$wpdb->prefix}certificados_eventos ORDER BY nombre ASC" );
        // Si vienen por GET con evento_id, preseleccionar
        $preselect_evento = isset( $_GET['evento_id'] ) ? intval( $_GET['evento_id'] ) : 0;

        // Nonce
        $nonce = wp_create_nonce( 'certificados_shortcodes_nonce' );

        ?>
        <div class="wrap">
            <h1><?php _e( 'Shortcodes - Certificados', 'certificados-digitales' ); ?></h1>

            <h2><?php _e( 'Shortcodes existentes', 'certificados-digitales' ); ?></h2>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th><?php _e( 'Shortcode', 'certificados-digitales' ); ?></th>
                        <th><?php _e( 'Evento', 'certificados-digitales' ); ?></th>
                        <th><?php _e( 'Uso', 'certificados-digitales' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $items ) ) : ?>
                        <tr><td colspan="3"><?php _e( 'No hay shortcodes creados.', 'certificados-digitales' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $items as $slug => $evento_id ) :
                            $evento_name = '';
                            foreach ( $eventos as $e ) { if ( $e->id == $evento_id ) { $evento_name = $e->nombre; break; } }
                        ?>
                            <tr data-slug="<?php echo esc_attr( $slug ); ?>" data-evento-id="<?php echo intval( $evento_id ); ?>">
                                <td class="sc-shortcode"><code>[certificados_<?php echo esc_html( $slug ); ?>]</code></td>
                                <td class="sc-evento-name"><?php echo esc_html( $evento_name ); ?> <small>(ID: <?php echo intval( $evento_id ); ?>)</small></td>
                                <td>
                                    <button class="button button-secondary btn-copy" data-shortcode="[certificados_<?php echo esc_attr( $slug ); ?>]"><?php _e( 'Copiar', 'certificados-digitales' ); ?></button>
                                    <button class="button button-danger btn-delete" data-slug="<?php echo esc_attr( $slug ); ?>"><?php _e( 'Eliminar', 'certificados-digitales' ); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2 style="margin-top:20px"><?php _e( 'Crear nuevo Shortcode', 'certificados-digitales' ); ?></h2>
            <form id="certificados-shortcode-form">
                <table class="form-table">
                    <tr>
                        <th><label for="sc-slug"><?php _e( 'Slug (sin espacios)', 'certificados-digitales' ); ?></label></th>
                        <td><input type="text" id="sc-slug" name="slug" required pattern="[a-z0-9_]+" placeholder="ej: asistentes"></td>
                    </tr>
                    <tr>
                        <th><label for="sc-evento"><?php _e( 'Evento', 'certificados-digitales' ); ?></label></th>
                        <td>
                            <select id="sc-evento" name="evento_id">
                                <?php foreach ( $eventos as $e ) : ?>
                                    <option value="<?php echo intval( $e->id ); ?>" <?php selected( $preselect_evento, $e->id ); ?>><?php echo esc_html( $e->nombre ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button class="button button-primary" id="btn-save-shortcode"><?php _e( 'Crear Shortcode', 'certificados-digitales' ); ?></button>
                </p>
                <input type="hidden" id="certificados_shortcodes_nonce" value="<?php echo esc_attr( $nonce ); ?>">
            </form>

            <script>
            (function($){
                $(function(){
                    $('#certificados-shortcode-form').on('submit', function(e){
                        e.preventDefault();
                        var slug = $('#sc-slug').val();
                        var evento = $('#sc-evento').val();
                        var nonce = $('#certificados_shortcodes_nonce').val();
                        $.post( ajaxurl, { action: 'certificados_shortcodes_save', slug: slug, evento_id: evento, nonce: nonce }, function(resp){
                            if ( resp.success ) {
                                // Insert new row dynamically
                                var newSlug = slug;
                                var newEventoId = evento;
                                var newEventoName = resp.data.evento_name || '';
                                var $row = $('<tr>').attr('data-slug', newSlug).attr('data-evento-id', newEventoId);
                                $row.append($('<td>').addClass('sc-shortcode').html('<code>[certificados_' + $('<div>').text(newSlug).html() + ']</code>'));
                                $row.append($('<td>').addClass('sc-evento-name').html( $('<div>').text(newEventoName).text() + ' <small>(ID: ' + newEventoId + ')</small>' ));
                                var $actions = $('<td>');
                                $actions.append($('<button>').addClass('button button-secondary btn-copy').attr('data-shortcode', '[certificados_' + newSlug + ']').text('Copiar'));
                                $actions.append(' ');
                                $actions.append($('<button>').addClass('button button-danger btn-delete').attr('data-slug', newSlug).text('Eliminar'));
                                $row.append($actions);
                                $('table.widefat tbody').append($row);
                                // Notify other admin pages that shortcodes changed
                                try { localStorage.setItem('certificados_shortcodes_updated', Date.now()); } catch (e) {}
                            } else {
                                alert(resp.data.message || 'Error');
                            }
                        });
                    });

                    $('.btn-delete').on('click', function(){
                        if (!confirm('¿Eliminar shortcode?')) return;
                        var slug = $(this).data('slug');
                        var nonce = $('#certificados_shortcodes_nonce').val();
                        $.post( ajaxurl, { action: 'certificados_shortcodes_delete', slug: slug, nonce: nonce }, function(resp){
                            if ( resp.success ) {
                                // Remove row
                                $('tr[data-slug="' + slug + '"]').remove();
                                // Notify other admin pages that shortcodes changed
                                try { localStorage.setItem('certificados_shortcodes_updated', Date.now()); } catch (e) {}
                            } else {
                                alert(resp.data.message || 'Error');
                            }
                        });
                    });

                    $('.btn-copy').on('click', function(){
                        var sc = $(this).data('shortcode');
                        navigator.clipboard && navigator.clipboard.writeText(sc);
                        alert('Shortcode copiado: ' + sc);
                    });
                });
            })(jQuery);
            </script>

        </div>
        <?php
    }

    /** AJAX save */
    public function ajax_save_shortcode() {
        check_ajax_referer( 'certificados_shortcodes_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos.' ) );
        }

        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( $_POST['slug'] ) : '';
        $evento = isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0;

        if ( empty( $slug ) || ! preg_match('/^[a-z0-9_]+$/', $slug) ) {
            wp_send_json_error( array( 'message' => 'Slug inválido. Usa solo minúsculas, números y guiones bajos.' ) );
        }

        if ( ! $evento ) {
            wp_send_json_error( array( 'message' => 'Evento inválido.' ) );
        }

        $items = get_option( $this->option_key, array() );
        if ( isset( $items[ $slug ] ) ) {
            wp_send_json_error( array( 'message' => 'El slug ya existe.' ) );
        }

        $items[ $slug ] = $evento;
        update_option( $this->option_key, $items );

        // Obtener nombre del evento para respuesta (mejor experiencia UX)
        global $wpdb;
        $evento_row = $wpdb->get_row( $wpdb->prepare( "SELECT nombre FROM {$wpdb->prefix}certificados_eventos WHERE id = %d", $evento ) );
        $evento_name = $evento_row ? $evento_row->nombre : '';

        wp_send_json_success( array( 'evento_name' => $evento_name ) );
    }

    /** AJAX delete */
    public function ajax_delete_shortcode() {
        check_ajax_referer( 'certificados_shortcodes_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos.' ) );
        }

        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( $_POST['slug'] ) : '';
        if ( empty( $slug ) ) {
            wp_send_json_error( array( 'message' => 'Slug inválido.' ) );
        }

        $items = get_option( $this->option_key, array() );
        if ( isset( $items[ $slug ] ) ) {
            unset( $items[ $slug ] );
            update_option( $this->option_key, $items );
            wp_send_json_success( array( 'slug' => $slug ) );
        }

        wp_send_json_error( array( 'message' => 'No encontrado.' ) );
    }

}

// No instanciar aquí — será creado desde el Admin principal
