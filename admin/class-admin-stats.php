<?php
/**
 * Página de administración para Estadísticas
 *
 * @package Certificados_Digitales
 * @version 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Admin_Stats {

    /**
     * Instancia del Stats Manager
     */
    private $stats_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->stats_manager = new Certificados_Stats_Manager();

        // Hook para agregar al menú de administración
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 25 );

        // Enqueue scripts y estilos
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Agregar página al menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'certificados-digitales',
            __( 'Estadísticas', 'certificados-digitales' ),
            __( 'Estadísticas', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-stats',
            array( $this, 'render_page' )
        );
    }

    /**
     * Cargar scripts y estilos
     */
    public function enqueue_scripts( $hook ) {
        if ( 'certificados_page_certificados-digitales-stats' !== $hook ) {
            return;
        }

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        wp_enqueue_style(
            'certificados-stats-admin',
            CERTIFICADOS_DIGITALES_URL . 'admin/css/stats-admin.css',
            array(),
            CERTIFICADOS_DIGITALES_VERSION
        );

        wp_enqueue_script(
            'certificados-stats-admin',
            CERTIFICADOS_DIGITALES_URL . 'admin/js/stats-admin.js',
            array( 'jquery', 'chartjs' ),
            CERTIFICADOS_DIGITALES_VERSION,
            true
        );

        wp_localize_script(
            'certificados-stats-admin',
            'certificadosStats',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'certificados_stats_nonce' ),
                'i18n' => array(
                    'loading' => __( 'Cargando...', 'certificados-digitales' ),
                    'error' => __( 'Error al cargar datos.', 'certificados-digitales' ),
                    'no_data' => __( 'No hay datos disponibles.', 'certificados-digitales' ),
                    'export_success' => __( 'Exportación completada.', 'certificados-digitales' )
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

        // Obtener eventos para filtros
        global $wpdb;
        $eventos = $wpdb->get_results( "SELECT id, nombre FROM {$wpdb->prefix}certificados_eventos ORDER BY nombre ASC" );

        ?>
        <div class="wrap certificados-admin-wrap certificados-stats-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <h1><?php _e( 'Estadísticas de Descargas', 'certificados-digitales' ); ?></h1>
                </div>
                <div class="dashboard-header-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales' ); ?>" class="btn-quick-action">
                        <i class="fas fa-home"></i>
                        <?php _e( 'Dashboard', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-stats-description">
                <p><?php _e( 'Visualiza y analiza las estadísticas de descarga de certificados. Filtra por período de tiempo y evento específico para obtener información detallada sobre el uso de tus certificados.', 'certificados-digitales' ); ?></p>
            </div>

            <!-- Filtros -->
            <div class="certificados-stats-filters">
                <div class="filter-group">
                    <label for="stats-period"><?php _e( 'Período:', 'certificados-digitales' ); ?></label>
                    <select id="stats-period">
                        <option value="7"><?php _e( 'Últimos 7 días', 'certificados-digitales' ); ?></option>
                        <option value="30" selected><?php _e( 'Últimos 30 días', 'certificados-digitales' ); ?></option>
                        <option value="90"><?php _e( 'Últimos 90 días', 'certificados-digitales' ); ?></option>
                        <option value="365"><?php _e( 'Último año', 'certificados-digitales' ); ?></option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="stats-event"><?php _e( 'Evento:', 'certificados-digitales' ); ?></label>
                    <select id="stats-event">
                        <option value="0"><?php _e( 'Todos los eventos', 'certificados-digitales' ); ?></option>
                        <?php foreach ( $eventos as $evento ) : ?>
                            <option value="<?php echo esc_attr( $evento->id ); ?>">
                                <?php echo esc_html( $evento->nombre ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <button type="button" id="btn-refresh-stats" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e( 'Actualizar', 'certificados-digitales' ); ?>
                    </button>
                    <button type="button" id="btn-export-stats" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e( 'Exportar CSV', 'certificados-digitales' ); ?>
                    </button>
                </div>
            </div>

            <!-- Cards de resumen -->
            <div class="stats-overview-cards">
                <div class="stats-card">
                    <div class="stats-card-icon">
                        <span class="dashicons dashicons-download"></span>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-value" id="stat-total-downloads">-</div>
                        <div class="stats-card-label"><?php _e( 'Total Descargas', 'certificados-digitales' ); ?></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-card-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-value" id="stat-unique-users">-</div>
                        <div class="stats-card-label"><?php _e( 'Usuarios Únicos', 'certificados-digitales' ); ?></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-card-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-value" id="stat-today-downloads">-</div>
                        <div class="stats-card-label"><?php _e( 'Descargas Hoy', 'certificados-digitales' ); ?></div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-card-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-value" id="stat-avg-per-day">-</div>
                        <div class="stats-card-label"><?php _e( 'Promedio Diario', 'certificados-digitales' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de línea de tiempo -->
            <div class="stats-chart-container">
                <h2><?php _e( 'Evolución de Descargas', 'certificados-digitales' ); ?></h2>
                <div class="chart-controls">
                    <select id="chart-group-by">
                        <option value="day"><?php _e( 'Por día', 'certificados-digitales' ); ?></option>
                        <option value="week"><?php _e( 'Por semana', 'certificados-digitales' ); ?></option>
                        <option value="month"><?php _e( 'Por mes', 'certificados-digitales' ); ?></option>
                    </select>
                </div>
                <canvas id="timeline-chart"></canvas>
            </div>

            <div class="stats-two-columns">
                <!-- Estadísticas por evento -->
                <div class="stats-table-container">
                    <h2><?php _e( 'Descargas por Evento', 'certificados-digitales' ); ?></h2>
                    <div id="event-stats-table"></div>
                </div>

                <!-- Top certificados -->
                <div class="stats-table-container">
                    <h2><?php _e( 'Certificados Más Descargados', 'certificados-digitales' ); ?></h2>
                    <div class="top-limit-selector">
                        <label><?php _e( 'Mostrar:', 'certificados-digitales' ); ?></label>
                        <select id="top-limit">
                            <option value="10">Top 10</option>
                            <option value="25">Top 25</option>
                            <option value="50">Top 50</option>
                        </select>
                    </div>
                    <div id="top-downloads-table"></div>
                </div>
            </div>

        </div>
        <?php
    }
}
