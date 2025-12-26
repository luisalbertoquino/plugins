<?php
/**
 * Página de administración para Mapeo de Columnas
 *
 * @package Certificados_Digitales
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Admin_Column_Mapper {

    /**
     * Instancia del Column Mapper
     */
    private $mapper;

    /**
     * Constructor
     */
    public function __construct() {
        $this->mapper = new Certificados_Column_Mapper();

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
            __( 'Mapeo de Columnas', 'certificados-digitales' ),
            __( 'Mapeo de Columnas', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-column-mapper',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts( $hook ) {
        if ( 'certificados_page_certificados-digitales-column-mapper' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'certificados-mapper-admin',
            CERTIFICADOS_DIGITALES_URL . 'admin/css/mapper-admin.css',
            array(),
            CERTIFICADOS_DIGITALES_VERSION
        );

        wp_enqueue_script(
            'certificados-mapper-admin',
            CERTIFICADOS_DIGITALES_URL . 'admin/js/mapper-admin.js',
            array( 'jquery' ),
            CERTIFICADOS_DIGITALES_VERSION,
            true
        );

        wp_localize_script(
            'certificados-mapper-admin',
            'certificadosMapper',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'certificados_mapper_nonce' ),
                'i18n' => array(
                    'loading' => __( 'Cargando...', 'certificados-digitales' ),
                    'error' => __( 'Error al cargar datos.', 'certificados-digitales' ),
                    'saved' => __( 'Configuración guardada correctamente.', 'certificados-digitales' ),
                    'confirm_delete' => __( '¿Estás seguro de eliminar este mapeo?', 'certificados-digitales' )
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

        // Obtener todos los mapeos existentes agrupados por evento
        $table_mapping = $wpdb->prefix . 'certificados_column_mapping';
        $mappings_query = "SELECT evento_id, sheet_name, COUNT(*) as num_campos, GROUP_CONCAT(system_field) as campos_mapeados
                          FROM {$table_mapping}
                          WHERE is_active = 1
                          GROUP BY evento_id, sheet_name
                          ORDER BY evento_id, sheet_name";
        $mappings = $wpdb->get_results( $mappings_query );

        // Organizar mapeos por evento
        $mappings_by_event = array();
        foreach ( $mappings as $mapping ) {
            if ( ! isset( $mappings_by_event[ $mapping->evento_id ] ) ) {
                $mappings_by_event[ $mapping->evento_id ] = array();
            }
            $mappings_by_event[ $mapping->evento_id ][] = $mapping;
        }

        ?>
        <div class="wrap certificados-admin-wrap certificados-mapper-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-editor-table"></span>
                    <h1><?php _e( 'Mapeo Dinámico de Columnas', 'certificados-digitales' ); ?></h1>
                </div>
            </div>

            <div class="certificados-mapper-description">
                <p><?php _e( 'Esta herramienta te permite mapear las columnas de tus hojas de Google Sheets a los campos del sistema, incluso si las cabeceras tienen nombres diferentes.', 'certificados-digitales' ); ?></p>
            </div>

            <?php if ( empty( $eventos ) ) : ?>
                <div class="empty-state">
                    <div class="empty-icon">🗂️</div>
                    <p><?php _e( 'No hay eventos creados aún.', 'certificados-digitales' ); ?></p>
                    <p class="empty-description"><?php _e( 'Crea tu primer evento para poder mapear las columnas de tus hojas de Google Sheets.', 'certificados-digitales' ); ?></p>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-eventos' ); ?>" class="button button-primary button-hero">
                        <?php _e( 'Crear Primer Evento', 'certificados-digitales' ); ?>
                    </a>
                </div>
            <?php else : ?>

                <!-- Tabla de resumen de mapeos por evento -->
                <div class="certificados-mapper-card">
                    <h2><?php _e( 'Estado de Mapeos por Evento', 'certificados-digitales' ); ?></h2>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Evento', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Hojas Mapeadas', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Campos Configurados', 'certificados-digitales' ); ?></th>
                                <th><?php _e( 'Acciones', 'certificados-digitales' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $eventos as $evento ) :
                                $has_mapping = isset( $mappings_by_event[ $evento->id ] );
                                $event_mappings = $has_mapping ? $mappings_by_event[ $evento->id ] : array();
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $evento->nombre ); ?></strong></td>
                                    <td>
                                        <?php if ( $has_mapping ) : ?>
                                            <span style="color: green;">✓</span>
                                            <?php
                                            $sheet_names = array();
                                            foreach ( $event_mappings as $em ) {
                                                $sheet_names[] = esc_html( $em->sheet_name );
                                            }
                                            echo implode( ', ', $sheet_names );
                                            ?>
                                        <?php else : ?>
                                            <span style="color: #999;">○</span> <?php _e( 'Sin mapeos', 'certificados-digitales' ); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $has_mapping ) : ?>
                                            <?php
                                            $total_campos = 0;
                                            $campos_list = array();
                                            foreach ( $event_mappings as $em ) {
                                                $total_campos += (int) $em->num_campos;
                                                $campos_arr = explode( ',', $em->campos_mapeados );
                                                foreach ( $campos_arr as $campo ) {
                                                    if ( ! in_array( $campo, $campos_list ) ) {
                                                        $campos_list[] = $campo;
                                                    }
                                                }
                                            }
                                            ?>
                                            <span style="
                                                padding: 3px 10px;
                                                border-radius: 3px;
                                                background: #d4edda;
                                                color: #155724;
                                                font-weight: 600;
                                                font-size: 12px;
                                            ">
                                                <?php echo $total_campos; ?> <?php _e( 'campos', 'certificados-digitales' ); ?>
                                            </span>
                                            <br>
                                            <small style="color: #666;">
                                                <?php
                                                $campos_traducidos = array();
                                                foreach ( $campos_list as $campo ) {
                                                    $mapper_instance = new Certificados_Column_Mapper();
                                                    $system_fields = $mapper_instance->get_system_fields();
                                                    $campos_traducidos[] = isset( $system_fields[ $campo ] ) ? $system_fields[ $campo ] : $campo;
                                                }
                                                echo implode( ', ', array_slice( $campos_traducidos, 0, 3 ) );
                                                if ( count( $campos_traducidos ) > 3 ) {
                                                    echo '...';
                                                }
                                                ?>
                                            </small>
                                        <?php else : ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small btn-config-mapping" data-evento-id="<?php echo esc_attr( $evento->id ); ?>">
                                            <?php $has_mapping ? _e( 'Editar', 'certificados-digitales' ) : _e( 'Configurar', 'certificados-digitales' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="certificados-mapper-card" style="margin-top: 20px;">
                    <h2><?php _e( 'Configurar Mapeo de Columnas', 'certificados-digitales' ); ?></h2>

                    <div class="mapper-selector">
                        <div class="form-group">
                            <label for="mapper-evento"><?php _e( 'Evento:', 'certificados-digitales' ); ?></label>
                            <select id="mapper-evento" name="evento_id" class="regular-text">
                                <option value=""><?php _e( 'Seleccionar evento...', 'certificados-digitales' ); ?></option>
                                <?php foreach ( $eventos as $evento ) : ?>
                                    <option value="<?php echo esc_attr( $evento->id ); ?>" data-sheet-id="<?php echo esc_attr( $evento->sheet_id ); ?>">
                                        <?php echo esc_html( $evento->nombre ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="sheet-name-group" style="display:none;">
                            <label for="mapper-sheet-name"><?php _e( 'Nombre de la Hoja:', 'certificados-digitales' ); ?></label>
                            <input type="text" id="mapper-sheet-name" name="sheet_name" class="regular-text" placeholder="<?php esc_attr_e( 'Ejemplo: Hoja1, Asistentes, etc.', 'certificados-digitales' ); ?>">
                            <button type="button" id="btn-load-headers" class="button button-primary">
                                <?php _e( 'Cargar Cabeceras', 'certificados-digitales' ); ?>
                            </button>
                        </div>
                    </div>

                    <div id="mapper-loading" style="display:none;">
                        <p><span class="spinner is-active"></span> <?php _e( 'Cargando cabeceras...', 'certificados-digitales' ); ?></p>
                    </div>

                    <div id="mapper-result" style="display:none;">
                        <h3><?php _e( 'Mapear Campos', 'certificados-digitales' ); ?></h3>
                        <p class="description"><?php _e( 'Selecciona la columna del Google Sheet que corresponde a cada campo del sistema.', 'certificados-digitales' ); ?></p>

                        <div id="mapping-table"></div>

                        <div class="mapper-actions">
                            <button type="button" id="btn-save-mapping" class="button button-primary">
                                <?php _e( 'Guardar Mapeo', 'certificados-digitales' ); ?>
                            </button>
                            <button type="button" id="btn-apply-suggestions" class="button button-secondary">
                                <?php _e( 'Aplicar Sugerencias Automáticas', 'certificados-digitales' ); ?>
                            </button>
                            <button type="button" id="btn-clear-mapping" class="button button-link-delete">
                                <?php _e( 'Limpiar Mapeo', 'certificados-digitales' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="certificados-mapper-help">
                    <h3><?php _e( 'Ayuda', 'certificados-digitales' ); ?></h3>
                    <ul>
                        <li><?php _e( '<strong>Número de Documento:</strong> Campo obligatorio para identificar a los usuarios.', 'certificados-digitales' ); ?></li>
                        <li><?php _e( '<strong>Nombre Completo:</strong> Nombre del participante que aparecerá en el certificado.', 'certificados-digitales' ); ?></li>
                        <li><?php _e( '<strong>Nombre del Evento:</strong> Puede ser usado para validaciones o información adicional.', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Los campos no mapeados usarán el sistema de búsqueda tradicional basado en nombres de columnas.', 'certificados-digitales' ); ?></li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }
}
