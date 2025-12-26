<?php
/**
 * Página de administración para Encuestas de Satisfacción
 *
 * @package Certificados_Digitales
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Admin_Survey {

    /**
     * Instancia del Survey Manager
     */
    private $survey_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->survey_manager = new Certificados_Survey_Manager();

        // Hook para agregar al menú de administración
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Agregar página al menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'certificados-digitales',
            __( 'Encuestas de Satisfacción', 'certificados-digitales' ),
            __( 'Encuestas', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-surveys',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts( $hook ) {
        if ( 'certificados_page_certificados-digitales-surveys' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'certificados-survey-admin',
            CERTIFICADOS_DIGITALES_URL . 'admin/js/survey-admin.js',
            array( 'jquery' ),
            CERTIFICADOS_DIGITALES_VERSION,
            true
        );

        wp_localize_script(
            'certificados-survey-admin',
            'certificadosSurvey',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'certificados_survey_nonce' ),
                'i18n' => array(
                    'loading' => __( 'Cargando...', 'certificados-digitales' ),
                    'saved' => __( 'Configuración guardada correctamente.', 'certificados-digitales' ),
                    'error' => __( 'Error al guardar la configuración.', 'certificados-digitales' ),
                    'confirm_disable' => __( '¿Desactivar la encuesta para este evento?', 'certificados-digitales' )
                )
            )
        );
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener eventos
        global $wpdb;
        $eventos = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}certificados_eventos ORDER BY nombre ASC" );

        // Obtener todas las configuraciones de encuestas
        $table_survey = $wpdb->prefix . 'certificados_survey_config';
        $surveys = $wpdb->get_results( "SELECT evento_id, survey_mode FROM {$table_survey} WHERE is_active = 1", OBJECT_K );

        ?>
        <div class="wrap certificados-admin-wrap certificados-survey-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-forms"></span>
                    <h1><?php _e( 'Encuestas de Satisfacción', 'certificados-digitales' ); ?></h1>
                </div>
            </div>

            <div class="certificados-survey-description">
                <p><?php _e( 'Configura encuestas de satisfacción opcionales u obligatorias que los usuarios deben completar antes de descargar sus certificados.', 'certificados-digitales' ); ?></p>
            </div>

            <?php if ( empty( $eventos ) ) : ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <p><?php _e( 'No hay eventos creados aún.', 'certificados-digitales' ); ?></p>
                    <p class="empty-description"><?php _e( 'Crea tu primer evento para poder configurar encuestas de satisfacción.', 'certificados-digitales' ); ?></p>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-eventos' ); ?>" class="button button-primary button-hero">
                        <?php _e( 'Crear Primer Evento', 'certificados-digitales' ); ?>
                    </a>
                </div>
            <?php else : ?>

                <!-- Tabla de resumen de encuestas por evento -->
                <div class="certificados-survey-card">
                    <h2><?php _e( 'Estado de Encuestas por Evento', 'certificados-digitales' ); ?></h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Evento', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Estado de Encuesta', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Modo', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Acciones', 'certificados-digitales' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $eventos as $evento ) :
                                $has_survey = isset( $surveys[ $evento->id ] );
                                $survey_mode = $has_survey ? $surveys[ $evento->id ]->survey_mode : 'disabled';

                                // Determinar badge según el modo
                                $badge_class = '';
                                $badge_text = '';
                                switch ( $survey_mode ) {
                                    case 'optional':
                                        $badge_class = 'badge-info';
                                        $badge_text = __( 'Opcional', 'certificados-digitales' );
                                        break;
                                    case 'mandatory':
                                        $badge_class = 'badge-warning';
                                        $badge_text = __( 'Obligatoria', 'certificados-digitales' );
                                        break;
                                    default:
                                        $badge_class = 'badge-default';
                                        $badge_text = __( 'Sin encuesta', 'certificados-digitales' );
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $evento->nombre ); ?></strong></td>
                                    <td>
                                        <?php if ( $has_survey && $survey_mode !== 'disabled' ) : ?>
                                            <span style="color: green;">✓</span> <?php _e( 'Configurada', 'certificados-digitales' ); ?>
                                        <?php else : ?>
                                            <span style="color: #999;">○</span> <?php _e( 'No configurada', 'certificados-digitales' ); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="survey-mode-badge <?php echo esc_attr( $badge_class ); ?>" style="
                                            padding: 3px 8px;
                                            border-radius: 3px;
                                            font-size: 11px;
                                            font-weight: 600;
                                            text-transform: uppercase;
                                            <?php
                                            if ( $survey_mode === 'optional' ) {
                                                echo 'background: #e3f2fd; color: #1976d2;';
                                            } elseif ( $survey_mode === 'mandatory' ) {
                                                echo 'background: #fff3cd; color: #856404;';
                                            } else {
                                                echo 'background: #f1f1f1; color: #666;';
                                            }
                                            ?>
                                        ">
                                            <?php echo esc_html( $badge_text ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small btn-config-survey" data-evento-id="<?php echo esc_attr( $evento->id ); ?>">
                                            <?php $has_survey && $survey_mode !== 'disabled' ? _e( 'Editar', 'certificados-digitales' ) : _e( 'Configurar', 'certificados-digitales' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="certificados-survey-card" style="margin-top: 20px;">
                    <h2><?php _e( 'Configurar Encuesta', 'certificados-digitales' ); ?></h2>

                    <div class="survey-selector">
                        <label for="survey-evento"><?php _e( 'Evento:', 'certificados-digitales' ); ?></label>
                        <select id="survey-evento" name="evento_id" class="regular-text">
                            <option value=""><?php _e( 'Seleccionar evento...', 'certificados-digitales' ); ?></option>
                            <?php foreach ( $eventos as $evento ) : ?>
                                <option value="<?php echo esc_attr( $evento->id ); ?>" data-sheet-id="<?php echo esc_attr( $evento->sheet_id ); ?>">
                                    <?php echo esc_html( $evento->nombre ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="survey-config-container" style="display:none;">
                        <form id="survey-config-form">
                            <input type="hidden" id="survey-evento-id" name="evento_id">

                            <h3><?php _e( 'Configuración de Encuesta', 'certificados-digitales' ); ?></h3>

                            <!-- Modo de Encuesta -->
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="survey-mode"><?php _e( 'Modo de Encuesta:', 'certificados-digitales' ); ?></label>
                                    </th>
                                    <td>
                                        <select id="survey-mode" name="survey_mode" class="regular-text">
                                            <option value="disabled"><?php _e( 'Deshabilitada', 'certificados-digitales' ); ?></option>
                                            <option value="optional"><?php _e( 'Opcional', 'certificados-digitales' ); ?></option>
                                            <option value="mandatory"><?php _e( 'Obligatoria', 'certificados-digitales' ); ?></option>
                                        </select>
                                        <p class="description"><?php _e( 'Selecciona si la encuesta es opcional, obligatoria o está deshabilitada.', 'certificados-digitales' ); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="survey-url"><?php _e( 'URL de la Encuesta:', 'certificados-digitales' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" id="survey-url" name="survey_url" class="regular-text" placeholder="https://forms.google.com/...">
                                        <p class="description"><?php _e( 'URL del formulario de Google Forms u otra plataforma de encuestas.', 'certificados-digitales' ); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="survey-title"><?php _e( 'Título del Modal:', 'certificados-digitales' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="survey-title" name="survey_title" class="regular-text" placeholder="<?php esc_attr_e( 'Encuesta de Satisfacción', 'certificados-digitales' ); ?>">
                                        <p class="description"><?php _e( 'Título que se mostrará en el modal de encuesta.', 'certificados-digitales' ); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="survey-message"><?php _e( 'Mensaje:', 'certificados-digitales' ); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="survey-message" name="survey_message" rows="3" class="large-text"></textarea>
                                        <p class="description"><?php _e( 'Mensaje que se mostrará al usuario sobre la encuesta.', 'certificados-digitales' ); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Configuración para Modo Obligatorio -->
                            <div id="mandatory-config" style="display:none;">
                                <h3><?php _e( 'Configuración para Modo Obligatorio', 'certificados-digitales' ); ?></h3>
                                <p class="description"><?php _e( 'En modo obligatorio, el sistema verificará si el usuario completó la encuesta antes de permitir la descarga del certificado.', 'certificados-digitales' ); ?></p>

                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="response-sheet-id"><?php _e( 'ID del Google Sheet de Respuestas:', 'certificados-digitales' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="response-sheet-id" name="response_sheet_id" class="regular-text">
                                            <p class="description"><?php _e( 'ID del Google Sheet donde se almacenan las respuestas de la encuesta.', 'certificados-digitales' ); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="response-sheet-name"><?php _e( 'Nombre de la Hoja de Respuestas:', 'certificados-digitales' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="response-sheet-name" name="response_sheet_name" class="regular-text" placeholder="<?php esc_attr_e( 'Respuestas de formulario 1', 'certificados-digitales' ); ?>">
                                            <button type="button" id="btn-load-response-headers" class="button button-secondary">
                                                <?php _e( 'Cargar Cabeceras', 'certificados-digitales' ); ?>
                                            </button>
                                            <p class="description"><?php _e( 'Nombre de la hoja donde están las respuestas (por ejemplo: "Hoja1" o "Respuestas de formulario 1").', 'certificados-digitales' ); ?></p>
                                        </td>
                                    </tr>

                                    <tr id="document-column-row" style="display:none;">
                                        <th scope="row">
                                            <label for="document-column"><?php _e( 'Columna de Número de Documento:', 'certificados-digitales' ); ?></label>
                                        </th>
                                        <td>
                                            <select id="document-column" name="document_column" class="regular-text">
                                                <option value=""><?php _e( 'Seleccionar columna...', 'certificados-digitales' ); ?></option>
                                            </select>
                                            <input type="hidden" id="document-column-index" name="document_column_index">
                                            <p class="description"><?php _e( 'Columna que contiene el número de documento en las respuestas.', 'certificados-digitales' ); ?></p>
                                        </td>
                                    </tr>

                                    <tr id="event-column-row" style="display:none;">
                                        <th scope="row">
                                            <label for="event-column"><?php _e( 'Columna del Nombre del Evento (opcional):', 'certificados-digitales' ); ?></label>
                                        </th>
                                        <td>
                                            <select id="event-column" name="event_column" class="regular-text">
                                                <option value=""><?php _e( 'No validar evento', 'certificados-digitales' ); ?></option>
                                            </select>
                                            <input type="hidden" id="event-column-index" name="event_column_index">
                                            <p class="description"><?php _e( 'Si la encuesta pregunta por el evento, selecciona esa columna.', 'certificados-digitales' ); ?></p>
                                        </td>
                                    </tr>

                                    <tr id="event-value-row" style="display:none;">
                                        <th scope="row">
                                            <label for="event-match-value"><?php _e( 'Valor del Evento a Buscar:', 'certificados-digitales' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="text" id="event-match-value" name="event_match_value" class="regular-text">
                                            <p class="description"><?php _e( 'Valor exacto o parcial del nombre del evento en las respuestas.', 'certificados-digitales' ); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <p class="submit">
                                <button type="submit" class="button button-primary" id="btn-save-survey">
                                    <?php _e( 'Guardar Configuración', 'certificados-digitales' ); ?>
                                </button>
                            </p>
                        </form>
                    </div>

                    <div id="survey-loading" style="display:none;">
                        <p><span class="spinner is-active"></span> <?php _e( 'Cargando configuración...', 'certificados-digitales' ); ?></p>
                    </div>
                </div>

                <div class="certificados-survey-help">
                    <h3><?php _e( 'Modos de Encuesta', 'certificados-digitales' ); ?></h3>
                    <ul>
                        <li><strong><?php _e( 'Deshabilitada:', 'certificados-digitales' ); ?></strong> <?php _e( 'No se muestra ninguna encuesta.', 'certificados-digitales' ); ?></li>
                        <li><strong><?php _e( 'Opcional:', 'certificados-digitales' ); ?></strong> <?php _e( 'Se muestra un enlace a la encuesta pero el usuario puede omitirlo y descargar el certificado.', 'certificados-digitales' ); ?></li>
                        <li><strong><?php _e( 'Obligatoria:', 'certificados-digitales' ); ?></strong> <?php _e( 'El usuario DEBE completar la encuesta antes de poder descargar el certificado. El sistema verificará si completó la encuesta consultando el Google Sheet de respuestas.', 'certificados-digitales' ); ?></li>
                    </ul>

                    <h3><?php _e( 'Configuración de Modo Obligatorio', 'certificados-digitales' ); ?></h3>
                    <ol>
                        <li><?php _e( 'Crea un formulario de Google Forms con al menos una pregunta que capture el número de documento.', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Conecta el formulario a un Google Sheet (Google Forms > Respuestas > Crear hoja de cálculo).', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Obtén el ID del Google Sheet de respuestas (está en la URL después de /d/).', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Ingresa el ID del Sheet y el nombre de la hoja en esta configuración.', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Carga las cabeceras y mapea las columnas correspondientes.', 'certificados-digitales' ); ?></li>
                    </ol>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }
}
