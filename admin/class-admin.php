<?php
/**
 * Clase que maneja toda la funcionalidad del área administrativa.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/admin
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Admin.
 *
 * Define toda la funcionalidad del panel de administración.
 */
class Certificados_Digitales_Admin {

    /**
     * Instancia de la clase Fuentes.
     *
     * @var Certificados_Digitales_Fuentes
     */
    protected $fuentes_manager;


    /**
     * Instancia de la clase Eventos.
     *
     * @var Certificados_Digitales_Eventos
     */
    protected $eventos_manager;


    /**
     * Instancia de la clase Pestañas.
     *
     * @var Certificados_Digitales_Pestanas
     */
    protected $pestanas_manager;


    /**
     * Instancia de la clase Campos.
     *
     * @var Certificados_Digitales_Campos
     */
    protected $campos_manager;

    /**
     * Constructor.
     */
    public function __construct() {
        // Inicializar gestor de fuentes
        $this->fuentes_manager = new Certificados_Digitales_Fuentes();
        
        // Inicializar gestor de eventos
        $this->eventos_manager = new Certificados_Digitales_Eventos();

        // Inicializar gestor de pestañas
        $this->pestanas_manager = new Certificados_Digitales_Pestanas();

        // Inicializar gestor de campos
        $this->campos_manager = new Certificados_Digitales_Campos();
        
        // Hook para inyectar CSS de fuentes personalizadas
        add_action( 'admin_head', array( $this, 'inject_custom_fonts_css' ) );

        // Hook para inyectar CSS de colores personalizados (directamente en head)
        add_action( 'wp_head', array( $this, 'inject_custom_colors_css' ), 999 );
        add_action( 'admin_head', array( $this, 'inject_custom_colors_css' ), 999 );

        // AJAX para generar certificados (público y admin)
        add_action( 'wp_ajax_certificados_generar_certificado', array( $this, 'ajax_generar_certificado' ) );
        add_action( 'wp_ajax_nopriv_certificados_generar_certificado', array( $this, 'ajax_generar_certificado' ) );

        // AJAX para registrar descargas
        add_action( 'wp_ajax_certificados_registrar_descarga', array( $this, 'ajax_registrar_descarga' ) );
        add_action( 'wp_ajax_nopriv_certificados_registrar_descarga', array( $this, 'ajax_registrar_descarga' ) );

        // AJAX para validar certificados (público)
        add_action( 'wp_ajax_certificados_validar_certificado', array( $this, 'ajax_validar_certificado' ) );
        add_action( 'wp_ajax_nopriv_certificados_validar_certificado', array( $this, 'ajax_validar_certificado' ) );
    }

    /**
     * Registra el menú administrativo.
     */
    public function register_admin_menu() {
        
        // Menú principal
        add_menu_page(
            __( 'Certificados Digitales', 'certificados-digitales' ),  // Título de la página
            __( 'Certificados', 'certificados-digitales' ),             // Título del menú
            'manage_options',                                            // Capacidad requerida
            'certificados-digitales',                                    // Slug del menú
            array( $this, 'render_dashboard_page' ),                    // Función callback
            'dashicons-awards',                                          // Icono
            30                                                           // Posición
        );

        // Submenú: Dashboard (renombrar el primer item)
        add_submenu_page(
            'certificados-digitales',
            __( 'Dashboard', 'certificados-digitales' ),
            __( 'Dashboard', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales',
            array( $this, 'render_dashboard_page' )
        );

        // Submenú: Eventos
        add_submenu_page(
            'certificados-digitales',
            __( 'Eventos', 'certificados-digitales' ),
            __( 'Eventos', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-eventos',
            array( $this, 'render_eventos_page' )
        );

        // Submenú: Fuentes
        add_submenu_page(
            'certificados-digitales',
            __( 'Fuentes', 'certificados-digitales' ),
            __( 'Fuentes', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-fuentes',
            array( $this, 'render_fuentes_page' )
        );

        // Submenú: Gestionar Pestañas (oculto del menú lateral pero vinculado al parent)
        add_submenu_page(
            'certificados-digitales', // Vinculado al menú principal
            __( 'Gestionar Pestañas', 'certificados-digitales' ),
            __( 'Gestionar Pestañas', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-pestanas',
            array( $this, 'render_pestanas_page' )
        );

        // Submenú: Configurador de Campos (oculto del menú lateral pero vinculado al parent)
        add_submenu_page(
            'certificados-digitales', // Vinculado al menú principal
            __( 'Configurar Campos', 'certificados-digitales' ),
            __( 'Configurar Campos', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-configurador',
            array( $this, 'render_configurador_page' )
        );

        // Submenú: Configuración
        add_submenu_page(
            'certificados-digitales',
            __( 'Configuración', 'certificados-digitales' ),
            __( 'Configuración', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-config',
            array( $this, 'render_config_page' )
        );
    }

    /**
     * Renderiza la página de Dashboard.
     */
    public function render_dashboard_page() {
        // Obtener estadísticas de eventos
        $eventos = $this->eventos_manager->get_all_eventos();
        $total_eventos = count( $eventos );
        $eventos_activos = count( array_filter( $eventos, function( $e ) { return $e->activo == 1; } ) );

        // Obtener estadísticas de descargas
        $stats_data = array(
            'total_downloads' => 0,
            'unique_certificates' => 0,
            'today_downloads' => 0,
            'avg_per_day' => 0
        );

        $timeline_data = array();
        $events_stats = array();

        if ( class_exists( 'Certificados_Stats_Manager' ) ) {
            $stats_manager = new Certificados_Stats_Manager();
            $stats_data = $stats_manager->get_overview_stats( 30 );
            $timeline_data = $stats_manager->get_timeline_stats( 7, 'day' );
            $events_stats = $stats_manager->get_stats_by_event( 30 );
        }
        ?>
        <div class="wrap certificados-admin-wrap certificados-dashboard">

            <!-- Header con título y accesos rápidos -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-awards"></span>
                    <h1><?php _e( 'Certificados Digitales Pro', 'certificados-digitales' ); ?></h1>
                    <span class="version-badge">v1.4.0</span>
                </div>
                <div class="dashboard-header-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-eventos' ); ?>" class="btn-quick-action btn-primary">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e( 'Eventos', 'certificados-digitales' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-stats' ); ?>" class="btn-quick-action btn-stats">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e( 'Estadísticas', 'certificados-digitales' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-documentacion' ); ?>" class="btn-quick-action btn-docs">
                        <span class="dashicons dashicons-book"></span>
                        <?php _e( 'Documentación', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-dashboard-description">
                <p><?php _e( 'Panel de control principal del plugin. Visualiza estadísticas generales, gráficas de descargas, accede rápidamente a las funciones más utilizadas y consulta el estado de la integración con Google Sheets.', 'certificados-digitales' ); ?></p>
            </div>

            <!-- Grid principal: Estadísticas y Gráficas -->
            <div class="dashboard-main-grid">

                <!-- Columna izquierda: Estadísticas -->
                <div class="dashboard-left-column">

                    <!-- Tarjetas de estadísticas -->
                    <div class="stats-cards-row">
                        <div class="stat-card stat-card-primary">
                            <div class="stat-card-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-number"><?php echo number_format( $stats_data['total_downloads'] ); ?></div>
                                <div class="stat-card-label"><?php _e( 'Descargas Totales', 'certificados-digitales' ); ?></div>
                                <div class="stat-card-period"><?php _e( 'Últimos 30 días', 'certificados-digitales' ); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-card-success">
                            <div class="stat-card-icon">
                                <span class="dashicons dashicons-groups"></span>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-number"><?php echo number_format( $stats_data['unique_certificates'] ); ?></div>
                                <div class="stat-card-label"><?php _e( 'Certificados Únicos', 'certificados-digitales' ); ?></div>
                                <div class="stat-card-period"><?php _e( 'Usuarios diferentes', 'certificados-digitales' ); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-card-warning">
                            <div class="stat-card-icon">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-number"><?php echo number_format( $stats_data['today_downloads'] ); ?></div>
                                <div class="stat-card-label"><?php _e( 'Descargas Hoy', 'certificados-digitales' ); ?></div>
                                <div class="stat-card-period"><?php echo date_i18n( 'd M Y' ); ?></div>
                            </div>
                        </div>

                        <div class="stat-card stat-card-info">
                            <div class="stat-card-icon">
                                <span class="dashicons dashicons-chart-line"></span>
                            </div>
                            <div class="stat-card-data">
                                <div class="stat-card-number"><?php echo number_format( $stats_data['avg_per_day'], 1 ); ?></div>
                                <div class="stat-card-label"><?php _e( 'Promedio Diario', 'certificados-digitales' ); ?></div>
                                <div class="stat-card-period"><?php _e( 'Últimos 30 días', 'certificados-digitales' ); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de línea de tiempo -->
                    <div class="dashboard-chart-card">
                        <div class="chart-card-header">
                            <h3><?php _e( 'Tendencia de Descargas', 'certificados-digitales' ); ?></h3>
                            <span class="chart-period"><?php _e( 'Últimos 7 días', 'certificados-digitales' ); ?></span>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="downloadsChart" height="80"></canvas>
                        </div>
                    </div>

                    <!-- Eventos por descargas -->
                    <div class="dashboard-events-card">
                        <div class="events-card-header">
                            <h3><?php _e( 'Eventos Más Descargados', 'certificados-digitales' ); ?></h3>
                            <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-stats' ); ?>" class="view-all-link">
                                <?php _e( 'Ver todo', 'certificados-digitales' ); ?>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        </div>
                        <div class="events-card-body">
                            <?php if ( ! empty( $events_stats ) ) : ?>
                                <?php foreach ( array_slice( $events_stats, 0, 5 ) as $event ) : ?>
                                    <div class="event-stats-item">
                                        <div class="event-stats-info">
                                            <div class="event-name"><?php echo esc_html( $event['evento_nombre'] ); ?></div>
                                            <div class="event-meta">
                                                <?php echo number_format( $event['usuarios_unicos'] ); ?> <?php _e( 'usuarios únicos', 'certificados-digitales' ); ?>
                                            </div>
                                        </div>
                                        <div class="event-stats-number">
                                            <?php echo number_format( $event['total_descargas'] ); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="no-stats-message">
                                    <span class="dashicons dashicons-info"></span>
                                    <p><?php _e( 'No hay estadísticas disponibles', 'certificados-digitales' ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Columna derecha: Información y accesos -->
                <div class="dashboard-right-column">

                    <!-- Resumen del sistema -->
                    <div class="system-summary-card">
                        <div class="summary-header">
                            <h3><?php _e( 'Resumen del Sistema', 'certificados-digitales' ); ?></h3>
                        </div>
                        <div class="summary-body">
                            <div class="summary-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <div class="summary-content">
                                    <strong><?php echo $total_eventos; ?></strong>
                                    <span><?php _e( 'Eventos Configurados', 'certificados-digitales' ); ?></span>
                                </div>
                            </div>
                            <div class="summary-item">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <div class="summary-content">
                                    <strong><?php echo $eventos_activos; ?></strong>
                                    <span><?php _e( 'Eventos Activos', 'certificados-digitales' ); ?></span>
                                </div>
                            </div>
                            <div class="summary-item">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <div class="summary-content">
                                    <strong><?php _e( 'Google Sheets', 'certificados-digitales' ); ?></strong>
                                    <span><?php _e( 'Integración activa', 'certificados-digitales' ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accesos rápidos -->
                    <div class="quick-links-card">
                        <div class="quick-links-header">
                            <h3><?php _e( 'Accesos Rápidos', 'certificados-digitales' ); ?></h3>
                        </div>
                        <div class="quick-links-body">
                            <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-column-mapper' ); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-editor-table"></span>
                                <span><?php _e( 'Mapeo de Columnas', 'certificados-digitales' ); ?></span>
                            </a>
                            <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-survey' ); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-forms"></span>
                                <span><?php _e( 'Gestionar Encuestas', 'certificados-digitales' ); ?></span>
                            </a>
                            <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-stats' ); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <span><?php _e( 'Ver Estadísticas', 'certificados-digitales' ); ?></span>
                            </a>
                            <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-documentacion' ); ?>" class="quick-link-item">
                                <span class="dashicons dashicons-book-alt"></span>
                                <span><?php _e( 'Documentación', 'certificados-digitales' ); ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Desarrolladores -->
                    <div class="developer-info-card">
                        <div class="developer-header">
                            <span class="dashicons dashicons-admin-users"></span>
                            <h3><?php _e( 'Desarrollado por', 'certificados-digitales' ); ?></h3>
                        </div>
                        <div class="developer-body">
                            <div class="developer-name">Webmaster Luis Quino</div>
                            <div class="developer-collab">
                                <?php _e( 'Con ayuda de su compañero', 'certificados-digitales' ); ?>
                            </div>
                            <div class="developer-ai">Claude AI</div>
                        </div>
                    </div>

                    <!-- Características -->
                    <div class="features-card">
                        <div class="features-header">
                            <h3><?php _e( 'Características', 'certificados-digitales' ); ?></h3>
                        </div>
                        <div class="features-body">
                            <div class="feature-item">
                                <span class="feature-icon">📊</span>
                                <span><?php _e( 'Integración Google Sheets', 'certificados-digitales' ); ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">🎨</span>
                                <span><?php _e( 'Plantillas Personalizadas', 'certificados-digitales' ); ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">📋</span>
                                <span><?php _e( 'Encuestas de Satisfacción', 'certificados-digitales' ); ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">📈</span>
                                <span><?php _e( 'Estadísticas Avanzadas', 'certificados-digitales' ); ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">🔄</span>
                                <span><?php _e( 'Detección de Cambios', 'certificados-digitales' ); ?></span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">🔗</span>
                                <span><?php _e( 'Mapeo Dinámico', 'certificados-digitales' ); ?></span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Datos de la gráfica desde PHP
            var timelineData = <?php echo json_encode( $timeline_data ); ?>;

            // Preparar datos para Chart.js
            var labels = [];
            var downloads = [];
            var uniqueUsers = [];

            if (timelineData && timelineData.length > 0) {
                timelineData.forEach(function(item) {
                    labels.push(item.periodo);
                    downloads.push(parseInt(item.total_descargas || 0));
                    uniqueUsers.push(parseInt(item.usuarios_unicos || 0));
                });
            } else {
                // Datos de ejemplo si no hay datos
                labels = ['Día 1', 'Día 2', 'Día 3', 'Día 4', 'Día 5', 'Día 6', 'Día 7'];
                downloads = [0, 0, 0, 0, 0, 0, 0];
                uniqueUsers = [0, 0, 0, 0, 0, 0, 0];
            }

            // Crear gráfica
            var ctx = document.getElementById('downloadsChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '<?php _e( 'Descargas', 'certificados-digitales' ); ?>',
                                data: downloads,
                                borderColor: '#0073aa',
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#0073aa',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            },
                            {
                                label: '<?php _e( 'Usuarios Únicos', 'certificados-digitales' ); ?>',
                                data: uniqueUsers,
                                borderColor: '#00a32a',
                                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#00a32a',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 15,
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                borderColor: '#ddd',
                                borderWidth: 1,
                                titleFont: {
                                    size: 13,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 12
                                },
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('es-ES');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Renderiza la página de Eventos.
     */
    public function render_eventos_page() {

        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener eventos existentes
        $eventos = $this->eventos_manager->get_all_eventos();
        // Obtener shortcodes mapeados (slug => evento_id)
        $shortcodes_map = get_option( 'certificados_shortcodes', array() );
        $total_eventos = count( $eventos );
        $eventos_activos = count( array_filter( $eventos, function( $e ) { return $e->activo == 1; } ) );
        ?>
        <div class="wrap certificados-admin-wrap">

            <!-- Header con título y accesos rápidos -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <h1><?php _e( 'Gestión de Eventos', 'certificados-digitales' ); ?></h1>
                </div>
                <div class="dashboard-header-actions">
                    <a href="#" class="btn-quick-action btn-primary" id="btn-nuevo-evento">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e( 'Nuevo Evento', 'certificados-digitales' ); ?>
                    </a>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales' ); ?>" class="btn-quick-action">
                        <i class="fas fa-home"></i>
                        <?php _e( 'Dashboard', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-eventos-description">
                <p><?php _e( 'Gestiona los eventos para los cuales se generarán certificados. Cada evento puede tener múltiples pestañas (plantillas) y está vinculado a una hoja de Google Sheets con los datos de los participantes.', 'certificados-digitales' ); ?></p>
            </div>

            <!-- Resumen rápido -->
            <div class="stats-cards-row" style="margin-bottom: 20px;">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stat-card-data">
                        <div class="stat-card-number"><?php echo $total_eventos; ?></div>
                        <div class="stat-card-label"><?php _e( 'Eventos Totales', 'certificados-digitales' ); ?></div>
                    </div>
                </div>

                <div class="stat-card stat-card-success">
                    <div class="stat-card-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stat-card-data">
                        <div class="stat-card-number"><?php echo $eventos_activos; ?></div>
                        <div class="stat-card-label"><?php _e( 'Eventos Activos', 'certificados-digitales' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Sección de eventos -->
            <div class="certificados-eventos-container">

                <!-- Listado de eventos -->
                <div class="certificados-list-card">
                    <h2><?php _e( 'Todos los Eventos', 'certificados-digitales' ); ?></h2>
                    
                    <?php if ( $total_eventos > 0 ) : ?>
                        <table id="table-eventos" class="display wp-list-table widefat fixed striped" style="width:100%">
                            <thead>
                                <tr>
                                        <th><?php _e( 'ID', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Nombre del Evento', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Sheet ID', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Shortcode(s)', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Pestañas', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Estado', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Fecha Creación', 'certificados-digitales' ); ?></th>
                                        <th><?php _e( 'Acciones', 'certificados-digitales' ); ?></th>
                                    </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $eventos as $evento ) : 
                                    $num_pestanas = $this->eventos_manager->count_pestanas_by_evento( $evento->id );
                                    // Buscar shortcodes asignados a este evento
                                    $assigned = array();
                                    if ( ! empty( $shortcodes_map ) && is_array( $shortcodes_map ) ) {
                                        foreach ( $shortcodes_map as $slug => $eid ) {
                                            if ( intval( $eid ) === intval( $evento->id ) ) {
                                                $assigned[] = $slug;
                                            }
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td class="evento-id-cell"><strong><?php echo $evento->id; ?></strong></td>
                                        <td class="evento-nombre-cell">
                                            <strong title="<?php echo esc_attr( $evento->nombre ); ?>">
                                                <?php echo esc_html( $evento->nombre ); ?>
                                            </strong>
                                        </td>
                                        <td class="shortcodes-cell">
                                            <button class="button btn-copiar-sheet-id"
                                                    data-sheet-id="<?php echo esc_attr( $evento->sheet_id ); ?>"
                                                    title="<?php _e( 'Copiar Sheet ID al portapapeles', 'certificados-digitales' ); ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td class="shortcodes-cell">
                                            <button class="button btn-copiar-shortcode"
                                                    data-id="<?php echo $evento->id; ?>"
                                                    title="<?php _e( 'Copiar shortcode al portapapeles', 'certificados-digitales' ); ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td class="pestanas-cell">
                                            <span class="badge badge-info" title="<?php echo $num_pestanas . ' ' . __( 'pestaña(s)', 'certificados-digitales' ); ?>">
                                                <?php echo $num_pestanas; ?>
                                            </span>
                                        </td>
                                        <td class="estado-cell">
                                            <?php if ( $evento->activo ) : ?>
                                                <span class="badge badge-success" title="<?php _e( 'Activo', 'certificados-digitales' ); ?>">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            <?php else : ?>
                                                <span class="badge badge-secondary" title="<?php _e( 'Inactivo', 'certificados-digitales' ); ?>">
                                                    <i class="fas fa-pause-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fecha-cell"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $evento->fecha_creacion ) ); ?></td>
                                            <td class="actions-cell">
                                                <div class="actions-buttons-stack">
                                                    <a
                                                        href="<?php echo admin_url( 'admin.php?page=certificados-digitales-pestanas&evento_id=' . $evento->id ); ?>"
                                                        class="button button-small button-primary btn-action-stack"
                                                        title="<?php _e( 'Gestionar Pestañas', 'certificados-digitales' ); ?>">
                                                        <i class="fas fa-folder-open"></i>
                                                    </a>
                                                    <button
                                                        class="button button-small btn-editar-evento btn-action-stack"
                                                        data-id="<?php echo $evento->id; ?>"
                                                        title="<?php _e( 'Editar Evento', 'certificados-digitales' ); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button
                                                        class="button button-small btn-toggle-evento btn-action-stack <?php echo $evento->activo ? 'btn-warning' : 'btn-success'; ?>"
                                                        data-id="<?php echo $evento->id; ?>"
                                                        data-estado="<?php echo $evento->activo; ?>"
                                                        title="<?php echo $evento->activo ? __( 'Desactivar', 'certificados-digitales' ) : __( 'Activar', 'certificados-digitales' ); ?>">
                                                        <i class="fas <?php echo $evento->activo ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                    </button>
                                                    <button
                                                        class="button button-small button-danger btn-eliminar-evento btn-action-stack"
                                                        data-id="<?php echo $evento->id; ?>"
                                                        data-nombre="<?php echo esc_attr( $evento->nombre ); ?>"
                                                        title="<?php _e( 'Eliminar Evento', 'certificados-digitales' ); ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <p><?php _e( 'No hay eventos creados aún.', 'certificados-digitales' ); ?></p>
                            <p class="empty-description"><?php _e( 'Crea tu primer evento para comenzar a generar certificados.', 'certificados-digitales' ); ?></p>
                            <button class="button button-primary button-hero" id="btn-crear-primer-evento">
                                <?php _e( 'Crear Primer Evento', 'certificados-digitales' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Modal: Crear/Editar Evento -->
        <div id="modal-evento" class="certificados-modal" style="display:none;">
            <div class="certificados-modal-content">
                <div class="certificados-modal-header">
                    <h2 id="modal-evento-title"><?php _e( 'Nuevo Evento', 'certificados-digitales' ); ?></h2>
                    <button class="certificados-modal-close" id="btn-close-modal">&times;</button>
                </div>
                
                <div class="certificados-modal-body">
                    <form id="form-evento">
                        <input type="hidden" id="evento_id" name="evento_id" value="">

                        <div class="form-row">
                            <label for="evento_nombre">
                                <?php _e( 'Nombre del Evento', 'certificados-digitales' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="evento_nombre" 
                                name="nombre" 
                                class="regular-text" 
                                required
                                placeholder="<?php _e( 'Ej: Congreso Internacional 2024', 'certificados-digitales' ); ?>"
                            />
                        </div>

                        <div class="form-row">
                            <label for="evento_sheet_id">
                                <?php _e( 'Google Sheet ID', 'certificados-digitales' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="evento_sheet_id" 
                                name="sheet_id" 
                                class="regular-text" 
                                required
                                placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                            />
                            <p class="description">
                                <?php _e( 'ID del documento de Google Sheets (está en la URL del documento)', 'certificados-digitales' ); ?>
                            </p>
                        </div>

                        <div class="form-row">
                            <label for="evento_url_encuesta">
                                <?php _e( 'URL de Encuesta (Opcional)', 'certificados-digitales' ); ?>
                            </label>
                            <input 
                                type="url" 
                                id="evento_url_encuesta" 
                                name="url_encuesta" 
                                class="regular-text"
                                placeholder="https://forms.google.com/..."
                            />
                            <p class="description">
                                <?php _e( 'Se mostrará después de descargar el certificado', 'certificados-digitales' ); ?>
                            </p>
                        </div>

                        <div class="form-row">
                            <label for="evento_logo_loader">
                                <?php _e( 'Logo (Opcional)', 'certificados-digitales' ); ?>
                            </label>
                            <div style="display: flex; gap: 10px; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div id="logo_preview" style="margin-bottom: 10px; max-width: 200px;">
                                        <img id="logo_preview_img" src="" style="max-width: 100%; max-height: 200px; display: none; border: 1px solid #ddd; padding: 5px;" />
                                    </div>
                                    <button type="button" class="button" id="btn-select-logo">
                                        <?php _e( 'Seleccionar Imagen', 'certificados-digitales' ); ?>
                                    </button>
                                    <button type="button" class="button" id="btn-remove-logo" style="display: none; margin-left: 5px;">
                                        <?php _e( 'Eliminar Imagen', 'certificados-digitales' ); ?>
                                    </button>
                                    <input 
                                        type="hidden" 
                                        id="evento_logo_loader" 
                                        name="logo_loader_url" 
                                        value=""
                                    />
                                </div>
                            </div>
                            <p class="description">
                                <?php _e( 'Logo que se muestra mientras se genera el certificado. Si no selecciona una imagen, se usará la imagen predeterminada.', 'certificados-digitales' ); ?>
                            </p>
                        </div>

                        <div id="form-evento-message" class="form-message"></div>
                    </form>
                </div>

                <div class="certificados-modal-footer">
                    <button type="button" class="button" id="btn-cancel-modal">
                        <?php _e( 'Cancelar', 'certificados-digitales' ); ?>
                    </button>
                    <button type="submit" form="form-evento" class="button button-primary" id="btn-save-evento">
                        <?php _e( 'Guardar Evento', 'certificados-digitales' ); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </div>
        </div>

        <?php
    }

    /**
     * Renderiza la página de Fuentes.
     */
    public function render_fuentes_page() {
        
        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener fuentes existentes
        $fuentes = $this->fuentes_manager->get_all_fuentes();
        $total_fuentes = count( $fuentes );

        ?>
        <div class="wrap certificados-admin-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-editor-textcolor"></span>
                    <h1><?php _e( 'Gestión de Fuentes', 'certificados-digitales' ); ?></h1>
                </div>
                <div class="dashboard-header-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales' ); ?>" class="btn-quick-action">
                        <i class="fas fa-home"></i>
                        <?php _e( 'Dashboard', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-fuentes-description">
                <p><?php _e( 'Administra las fuentes tipográficas personalizadas disponibles para usar en tus certificados. Sube archivos .ttf u .otf para aplicar estilos únicos a los textos de tus plantillas.', 'certificados-digitales' ); ?></p>
            </div>

            <!-- Resumen rápido -->
            <div class="stats-cards-row" style="margin-bottom: 20px;">
                <div class="stat-card stat-card-primary">
                    <div class="stat-card-icon">
                        <span class="dashicons dashicons-editor-textcolor"></span>
                    </div>
                    <div class="stat-card-data">
                        <div class="stat-card-number"><?php echo $total_fuentes; ?></div>
                        <div class="stat-card-label"><?php _e( 'Fuentes Disponibles', 'certificados-digitales' ); ?></div>
                    </div>
                </div>
            </div>

            <div class="certificados-fuentes-container">

                <!-- Formulario de subida -->
                <div class="certificados-upload-card">
                    <h2><?php _e( 'Subir Nueva Fuente', 'certificados-digitales' ); ?></h2>
                    
                    <form id="certificados-form-subir-fuente" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'certificados_fuentes_nonce', 'certificados_fuentes_nonce' ); ?>
                        
                        <div class="upload-area">
                            <div class="upload-icon">📁</div>
                            <p class="upload-text">
                                <strong><?php _e( 'Selecciona un archivo .ttf', 'certificados-digitales' ); ?></strong>
                            </p>
                            <p class="upload-description">
                                <?php _e( 'Tamaño máximo: 5MB', 'certificados-digitales' ); ?>
                            </p>
                            <input 
                                type="file" 
                                id="fuente_archivo" 
                                name="fuente_archivo" 
                                accept=".ttf"
                                required
                            />
                            <label for="fuente_archivo" class="button button-secondary">
                                <?php _e( 'Elegir archivo', 'certificados-digitales' ); ?>
                            </label>
                            <span id="file-name" class="file-name-display"></span>
                        </div>

                        <div class="upload-actions">
                            <button type="submit" class="button button-primary" id="btn-subir-fuente">
                                <?php _e( 'Subir Fuente', 'certificados-digitales' ); ?>
                            </button>
                            <span class="spinner"></span>
                        </div>

                        <div id="upload-message" class="upload-message"></div>
                    </form>
                </div>

                <!-- Listado de fuentes -->
                <div class="certificados-list-card">
                    <h2><?php _e( 'Fuentes Disponibles', 'certificados-digitales' ); ?></h2>
                    
                    <?php if ( $total_fuentes > 0 ) : ?>
                        <table id="table-fuentes" class="display wp-list-table widefat fixed striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th><?php _e( 'ID', 'certificados-digitales' ); ?></th>
                                    <th><?php _e( 'Nombre de la Fuente', 'certificados-digitales' ); ?></th>
                                    <th><?php _e( 'Vista Previa', 'certificados-digitales' ); ?></th>
                                    <th><?php _e( 'Fecha de Subida', 'certificados-digitales' ); ?></th>
                                    <th><?php _e( 'Acciones', 'certificados-digitales' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $fuentes as $fuente ) : ?>
                                    <tr>
                                        <td><?php echo $fuente->id; ?></td>
                                        <td><strong><?php echo esc_html( $fuente->nombre_fuente ); ?></strong></td>
                                        <td>
                                            <span class="font-preview" style="font-family: '<?php echo esc_attr( $fuente->nombre_fuente ); ?>';">
                                                AaBbCc 123
                                            </span>
                                        </td>
                                        <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $fuente->fecha_subida ) ); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url( $fuente->archivo_url ); ?>" 
                                            class="button button-small" 
                                            download 
                                            title="<?php _e( 'Descargar', 'certificados-digitales' ); ?>">
                                                ⬇️ <?php _e( 'Descargar', 'certificados-digitales' ); ?>
                                            </a>
                                            <button 
                                                class="button button-small button-danger btn-eliminar-fuente" 
                                                data-id="<?php echo $fuente->id; ?>"
                                                data-nombre="<?php echo esc_attr( $fuente->nombre_fuente ); ?>"
                                                title="<?php _e( 'Eliminar', 'certificados-digitales' ); ?>">
                                                🗑️ <?php _e( 'Eliminar', 'certificados-digitales' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <p><?php _e( 'No hay fuentes disponibles aún.', 'certificados-digitales' ); ?></p>
                            <p class="empty-description"><?php _e( 'Sube tu primera fuente para comenzar.', 'certificados-digitales' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Información adicional -->
                <div class="certificados-info-card">
                    <h3>ℹ️ <?php _e( 'Información sobre fuentes', 'certificados-digitales' ); ?></h3>
                    <ul>
                        <li><?php _e( 'Solo se aceptan archivos .ttf (TrueType Font)', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Tamaño máximo por archivo: 2MB', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Las fuentes se utilizarán para personalizar los certificados', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Asegúrate de tener los derechos de uso de las fuentes que subes', 'certificados-digitales' ); ?></li>
                    </ul>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la página de Configuración.
     */
    public function render_config_page() {
        
        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Procesar formulario si se envió
        if ( isset( $_POST['certificados_config_nonce'] ) && 
            wp_verify_nonce( $_POST['certificados_config_nonce'], 'certificados_config_save' ) ) {
            $this->save_config();
        }

        // Obtener valores actuales
        $api_key = get_option( 'certificados_digitales_api_key', '' );
        $api_key_exists = ! empty( $api_key );

        // Obtener colores personalizados (con valores por defecto)
        $color_primario = get_option( 'certificados_color_primario', '#2271b1' );
        $color_hover = get_option( 'certificados_color_hover', '#135e96' );
        $color_exito = get_option( 'certificados_color_exito', '#00a32a' );
        $color_error = get_option( 'certificados_color_error', '#d63638' );

        ?>
        <div class="wrap certificados-admin-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h1><?php _e( 'Configuración', 'certificados-digitales' ); ?></h1>
                </div>
                <div class="dashboard-header-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales' ); ?>" class="btn-quick-action">
                        <i class="fas fa-home"></i>
                        <?php _e( 'Dashboard', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-config-description">
                <p><?php _e( 'Configura los ajustes principales del plugin, incluyendo la conexión con Google Sheets API y la personalización de colores para la interfaz.', 'certificados-digitales' ); ?></p>
            </div>

            <!-- Estado de la API -->
            <div class="stats-cards-row" style="margin-bottom: 20px;">
                <div class="stat-card <?php echo $api_key_exists ? 'stat-card-success' : 'stat-card-warning'; ?>">
                    <div class="stat-card-icon">
                        <span class="dashicons dashicons-admin-network"></span>
                    </div>
                    <div class="stat-card-data">
                        <div class="stat-card-label"><?php _e( 'API de Google Sheets', 'certificados-digitales' ); ?></div>
                        <div class="stat-card-number" style="font-size: 18px;">
                            <?php if ( $api_key_exists ) : ?>
                                ✓ <?php _e( 'Configurada', 'certificados-digitales' ); ?>
                            <?php else : ?>
                                ⚠ <?php _e( 'No configurada', 'certificados-digitales' ); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="certificados-config-container">

                <!-- Formulario de configuración -->
                <div class="certificados-config-card">
                    <h2><?php _e( 'Configuración de Google Sheets API', 'certificados-digitales' ); ?></h2>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field( 'certificados_config_save', 'certificados_config_nonce' ); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="certificados_api_key">
                                        <?php _e( 'API Key de Google Sheets', 'certificados-digitales' ); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <input 
                                        type="text" 
                                        id="certificados_api_key" 
                                        name="certificados_api_key" 
                                        value="<?php echo esc_attr( $api_key ); ?>" 
                                        class="regular-text"
                                        placeholder="AIzaSyD..."
                                    />
                                    <p class="description">
                                        <?php _e( 'Ingresa tu API Key de Google Cloud Console para acceder a Google Sheets.', 'certificados-digitales' ); ?>
                                        <br>
                                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">
                                            <?php _e( 'Obtener API Key →', 'certificados-digitales' ); ?>
                                        </a>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top: 30px;"><?php _e( 'Personalización de Colores', 'certificados-digitales' ); ?></h3>
                        <p class="description"><?php _e( 'Personaliza los colores del plugin para que coincidan con tu marca. Estos colores se aplicarán a todos los formularios y elementos del plugin.', 'certificados-digitales' ); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="certificados_color_primario">
                                        <?php _e( 'Color Primario', 'certificados-digitales' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="color"
                                        id="certificados_color_primario"
                                        name="certificados_color_primario"
                                        value="<?php echo esc_attr($color_primario); ?>"
                                        style="width: 100px; height: 40px;"
                                    />
                                    <input
                                        type="text"
                                        value="<?php echo esc_attr($color_primario); ?>"
                                        readonly
                                        style="width: 100px; margin-left: 10px;"
                                        class="color-preview"
                                        data-target="certificados_color_primario"
                                    />
                                    <p class="description">
                                        <?php _e( 'Color principal de botones, enlaces y elementos activos del dashboard y configurador. Este color se aplica automáticamente a todos los botones de acción del plugin.', 'certificados-digitales' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="certificados_color_hover">
                                        <?php _e( 'Color Hover', 'certificados-digitales' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="color"
                                        id="certificados_color_hover"
                                        name="certificados_color_hover"
                                        value="<?php echo esc_attr($color_hover); ?>"
                                        style="width: 100px; height: 40px;"
                                    />
                                    <input
                                        type="text"
                                        value="<?php echo esc_attr($color_hover); ?>"
                                        readonly
                                        style="width: 100px; margin-left: 10px;"
                                        class="color-preview"
                                        data-target="certificados_color_hover"
                                    />
                                    <p class="description">
                                        <?php _e( 'Color de botones y elementos al pasar el mouse', 'certificados-digitales' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="certificados_color_exito">
                                        <?php _e( 'Color de Éxito', 'certificados-digitales' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="color"
                                        id="certificados_color_exito"
                                        name="certificados_color_exito"
                                        value="<?php echo esc_attr($color_exito); ?>"
                                        style="width: 100px; height: 40px;"
                                    />
                                    <input
                                        type="text"
                                        value="<?php echo esc_attr($color_exito); ?>"
                                        readonly
                                        style="width: 100px; margin-left: 10px;"
                                        class="color-preview"
                                        data-target="certificados_color_exito"
                                    />
                                    <p class="description">
                                        <?php _e( 'Color para mensajes de éxito y validaciones correctas', 'certificados-digitales' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="certificados_color_error">
                                        <?php _e( 'Color de Error', 'certificados-digitales' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input
                                        type="color"
                                        id="certificados_color_error"
                                        name="certificados_color_error"
                                        value="<?php echo esc_attr($color_error); ?>"
                                        style="width: 100px; height: 40px;"
                                    />
                                    <input
                                        type="text"
                                        value="<?php echo esc_attr($color_error); ?>"
                                        readonly
                                        style="width: 100px; margin-left: 10px;"
                                        class="color-preview"
                                        data-target="certificados_color_error"
                                    />
                                    <p class="description">
                                        <?php _e( 'Color para mensajes de error y validaciones incorrectas', 'certificados-digitales' ); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"></th>
                                <td>
                                    <button type="button" class="button" id="btn-reset-colors">
                                        <?php _e( 'Restaurar Colores por Defecto', 'certificados-digitales' ); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary button-large">
                                <?php _e( 'Guardar Configuración', 'certificados-digitales' ); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Instrucciones -->
                <div class="certificados-help-card">
                    <h3><?php _e( '¿Cómo obtener una API Key de Google Sheets?', 'certificados-digitales' ); ?></h3>
                    <ol>
                        <li><?php _e( 'Ve a', 'certificados-digitales' ); ?> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li><?php _e( 'Crea un proyecto nuevo o selecciona uno existente', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Ve a "APIs y servicios" → "Credenciales"', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Haz clic en "Crear credenciales" → "Clave de API"', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Habilita la API de Google Sheets v4', 'certificados-digitales' ); ?></li>
                        <li><?php _e( 'Copia la clave y pégala arriba', 'certificados-digitales' ); ?></li>
                    </ol>
                </div>

                <?php
                // Mostrar botón de descarga del plugin (solo en desarrollo)
                Certificados_Plugin_Packager::render_download_button();
                ?>

            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Actualizar preview de color cuando cambia el color picker
            $('input[type="color"]').on('change input', function() {
                var targetId = $(this).attr('id');
                var color = $(this).val();
                $('.color-preview[data-target="' + targetId + '"]').val(color);
            });

            // Resetear colores a los valores por defecto
            $('#btn-reset-colors').on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php _e( '¿Estás seguro de que quieres restaurar los colores por defecto?', 'certificados-digitales' ); ?>')) {
                    $('#certificados_color_primario').val('#2271b1').trigger('change');
                    $('#certificados_color_hover').val('#135e96').trigger('change');
                    $('#certificados_color_exito').val('#00a32a').trigger('change');
                    $('#certificados_color_error').val('#d63638').trigger('change');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Guarda la configuración del plugin.
     */
    private function save_config() {

        // Obtener y sanitizar la API Key
        $api_key = isset( $_POST['certificados_api_key'] ) ? sanitize_text_field( $_POST['certificados_api_key'] ) : '';

        // Obtener y sanitizar colores
        $color_primario = isset( $_POST['certificados_color_primario'] ) ? sanitize_hex_color( $_POST['certificados_color_primario'] ) : '#2271b1';
        $color_hover = isset( $_POST['certificados_color_hover'] ) ? sanitize_hex_color( $_POST['certificados_color_hover'] ) : '#135e96';
        $color_exito = isset( $_POST['certificados_color_exito'] ) ? sanitize_hex_color( $_POST['certificados_color_exito'] ) : '#00a32a';
        $color_error = isset( $_POST['certificados_color_error'] ) ? sanitize_hex_color( $_POST['certificados_color_error'] ) : '#d63638';

        // Validar que no esté vacía
        if ( empty( $api_key ) ) {
            add_settings_error(
                'certificados_digitales_messages',
                'certificados_digitales_message',
                __( 'La API Key no puede estar vacía.', 'certificados-digitales' ),
                'error'
            );
            return;
        }

        // Validar formato básico de API Key de Google
        // Las API Keys de Google suelen empezar con "AIza" y tienen 39 caracteres
        if ( ! preg_match( '/^AIza[0-9A-Za-z_-]{35}$/', $api_key ) ) {
            add_settings_error(
                'certificados_digitales_messages',
                'certificados_digitales_message',
                __( 'El formato de la API Key no parece ser válido. Las API Keys de Google comienzan con "AIza" y tienen 39 caracteres.', 'certificados-digitales' ),
                'warning'
            );
            // Aún así la guardamos por si el formato ha cambiado
        }

        // Guardar en la base de datos
        $updated = update_option( 'certificados_digitales_api_key', $api_key );

        // Guardar colores
        update_option( 'certificados_color_primario', $color_primario );
        update_option( 'certificados_color_hover', $color_hover );
        update_option( 'certificados_color_exito', $color_exito );
        update_option( 'certificados_color_error', $color_error );

        if ( $updated ) {
            add_settings_error(
                'certificados_digitales_messages',
                'certificados_digitales_message',
                __( 'Configuración guardada correctamente.', 'certificados-digitales' ),
                'success'
            );
        } else {
            // Si no se actualizó, puede ser porque el valor es el mismo
            $existing_key = get_option( 'certificados_digitales_api_key' );
            if ( $existing_key === $api_key ) {
                add_settings_error(
                    'certificados_digitales_messages',
                    'certificados_digitales_message',
                    __( 'La configuración no ha cambiado.', 'certificados-digitales' ),
                    'info'
                );
            } else {
                add_settings_error(
                    'certificados_digitales_messages',
                    'certificados_digitales_message',
                    __( 'Hubo un error al guardar la configuración.', 'certificados-digitales' ),
                    'error'
                );
            }
        }

        // Mostrar mensajes
        settings_errors( 'certificados_digitales_messages' );
    }


    /**
     * Carga los estilos CSS del admin.
     */
    public function enqueue_styles() {
        // Cargar estilos solo en las páginas del plugin
        $screen = get_current_screen();

        if ( strpos( $screen->id, 'certificados-digitales' ) !== false ) {
            // Cargar FontAwesome
            wp_enqueue_style(
                'fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
            );

            wp_enqueue_style(
                'certificados-digitales-admin',
                CERTIFICADOS_DIGITALES_URL . 'admin/css/admin-style.css',
                array(),
                CERTIFICADOS_DIGITALES_VERSION
            );

            // Nuevo dashboard mejorado
            wp_enqueue_style(
                'certificados-digitales-dashboard',
                CERTIFICADOS_DIGITALES_URL . 'admin/css/dashboard.css',
                array(),
                CERTIFICADOS_DIGITALES_VERSION
            );

            // Documentación con diseño mejorado
            wp_enqueue_style(
                'certificados-digitales-documentacion',
                CERTIFICADOS_DIGITALES_URL . 'admin/css/documentacion.css',
                array(),
                CERTIFICADOS_DIGITALES_VERSION
            );

            // CSS inline para ocultar submenús de pestañas y configurador del lateral
            $custom_css = "
                #adminmenu a[href='admin.php?page=certificados-digitales-pestanas'],
                #adminmenu a[href='admin.php?page=certificados-digitales-configurador'] {
                    display: none !important;
                }
            ";
            wp_add_inline_style( 'certificados-digitales-admin', $custom_css );
        }
    }

    /**
     * Carga los scripts JS del admin.
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();

        // Cargar scripts solo en las páginas del plugin
        if ( strpos( $screen->id, 'certificados-digitales' ) !== false ) {

            // Cargar Chart.js en el dashboard principal
            if ( $screen->id === 'toplevel_page_certificados-digitales' ) {
                wp_enqueue_script(
                    'chartjs',
                    'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
                    array( 'jquery' ),
                    '4.4.0',
                    true
                );
            }

            // Cargar DataTables si estamos en la página de fuentes
            if ( strpos( $screen->id, 'fuentes' ) !== false ) {
                // DataTables CSS
                wp_enqueue_style( 
                    'datatables', 
                    'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', 
                    array(), 
                    '1.13.6' 
                );
                
                // DataTables JS
                wp_enqueue_script( 
                    'datatables', 
                    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', 
                    array( 'jquery' ), 
                    '1.13.6', 
                    true 
                );
                
                // Script de fuentes
                wp_enqueue_script( 
                    'certificados-fuentes-admin', 
                    CERTIFICADOS_DIGITALES_URL . 'admin/js/fuentes-admin.js', 
                    array( 'jquery', 'datatables' ), 
                    CERTIFICADOS_DIGITALES_VERSION, 
                    true 
                );
                
                // Pasar variables a JavaScript DE FUENTES (corregido)
                wp_localize_script( 
                    'certificados-fuentes-admin', 
                    'certificadosFuentesAdmin', 
                    array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'certificados_fuentes_nonce' ),
                        'i18n'    => array(
                            'confirmDelete' => __( '¿Estás seguro de eliminar la fuente "%s"?', 'certificados-digitales' ),
                            'uploading'     => __( 'Subiendo...', 'certificados-digitales' ),
                            'deleting'      => __( 'Eliminando...', 'certificados-digitales' ),
                        )
                    )
                );
            }

            // Cargar scripts si estamos en la página de eventos
            if ( strpos( $screen->id, 'eventos' ) !== false ) {
                // DataTables CSS
                wp_enqueue_style( 
                    'datatables', 
                    'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', 
                    array(), 
                    '1.13.6' 
                );
                
                // DataTables JS
                wp_enqueue_script( 
                    'datatables', 
                    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', 
                    array( 'jquery' ), 
                    '1.13.6', 
                    true 
                );
                
                // Encargar media uploader
                wp_enqueue_media();
                
                // Script de eventos
                wp_enqueue_script( 
                    'certificados-eventos-admin', 
                    CERTIFICADOS_DIGITALES_URL . 'admin/js/eventos-admin.js', 
                    array( 'jquery', 'datatables', 'wp-media-utils' ), 
                    CERTIFICADOS_DIGITALES_VERSION, 
                    true 
                );
                
                // Pasar variables a JavaScript
                wp_localize_script( 
                    'certificados-eventos-admin', 
                    'certificadosEventosAdmin', 
                    array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'certificados_eventos_nonce' ),
                        'i18n'    => array(
                            'confirmDelete' => __( '¿Estás seguro de eliminar el evento "%s"? También se eliminarán todas sus pestañas.', 'certificados-digitales' ),
                            'saving'        => __( 'Guardando...', 'certificados-digitales' ),
                            'selectLogoTitle' => __( 'Seleccionar Logo', 'certificados-digitales' ),
                            'selectLogoButton' => __( 'Usar esta imagen', 'certificados-digitales' ),
                            'deleting'      => __( 'Eliminando...', 'certificados-digitales' ),
                            'loading'       => __( 'Cargando...', 'certificados-digitales' ),
                        )
                    )
                );
            }

            // Cargar scripts si estamos en la página de pestañas
            if ( strpos( $screen->id, 'pestanas' ) !== false ) {
                // jQuery UI Sortable (para reordenar pestañas con drag & drop)
                wp_enqueue_script( 'jquery-ui-sortable' );
                
                // Script de pestañas
                wp_enqueue_script( 
                    'certificados-pestanas-admin', 
                    CERTIFICADOS_DIGITALES_URL . 'admin/js/pestanas-admin.js', 
                    array( 'jquery', 'jquery-ui-sortable' ), 
                    CERTIFICADOS_DIGITALES_VERSION, 
                    true 
                );
                
                // Pasar variables a JavaScript
                wp_localize_script( 
                    'certificados-pestanas-admin', 
                    'certificadosPestanasAdmin', 
                    array(
                        'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                        'adminurl' => admin_url(),
                        'nonce'    => wp_create_nonce( 'certificados_pestanas_nonce' ),
                        'i18n'     => array(
                            'confirmDelete' => __( '¿Estás seguro de eliminar la pestaña "%s"? También se eliminarán todos sus campos configurados.', 'certificados-digitales' ),
                            'saving'        => __( 'Guardando...', 'certificados-digitales' ),
                            'deleting'      => __( 'Eliminando...', 'certificados-digitales' ),
                            'uploading'     => __( 'Subiendo plantilla...', 'certificados-digitales' ),
                        )
                    )
                );
            }

            // Cargar scripts si estamos en la página del configurador
            if ( strpos( $screen->id, 'configurador' ) !== false ) {
                // jQuery UI Draggable (para mover campos en el canvas)
                wp_enqueue_script( 'jquery-ui-draggable' );
                
                // Script del configurador
                wp_enqueue_script( 
                    'certificados-configurador-admin', 
                    CERTIFICADOS_DIGITALES_URL . 'admin/js/configurador-campos.js', 
                    array( 'jquery', 'jquery-ui-draggable' ), 
                    CERTIFICADOS_DIGITALES_VERSION, 
                    true 
                );
                
                // Pasar variables a JavaScript
                wp_localize_script( 
                    'certificados-configurador-admin', 
                    'certificadosCamposAdmin', 
                    array(
                        'ajaxurl' => admin_url( 'admin-ajax.php' ),
                        'nonce'   => wp_create_nonce( 'certificados_campos_nonce' ),
                        'i18n'    => array(
                            'saving'   => __( 'Guardando...', 'certificados-digitales' ),
                            'saved'    => __( 'Guardado correctamente', 'certificados-digitales' ),
                            'error'    => __( 'Error al guardar', 'certificados-digitales' ),
                        )
                    )
                );
            }
        }

    }



    /**
     * Inyecta CSS para cargar las fuentes personalizadas.
     */
    public function inject_custom_fonts_css() {
        $screen = get_current_screen();
        
        // Solo en la página de fuentes y configurador
        if ( strpos( $screen->id, 'fuentes' ) === false && strpos( $screen->id, 'configurador' ) === false ) {
            return;
        }

        // Obtener todas las fuentes
        $fuentes = $this->fuentes_manager->get_all_fuentes();

        if ( empty( $fuentes ) ) {
            return;
        }

        // Generar CSS dinámico
        echo '<style type="text/css">';
        foreach ( $fuentes as $fuente ) {
            $nombre_fuente = esc_attr( $fuente->nombre_fuente );
            $archivo_url = esc_url( $fuente->archivo_url );
            
            echo "
            @font-face {
                font-family: '{$nombre_fuente}';
                src: url('{$archivo_url}') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
            ";
        }
        echo '</style>';
    }

    /**
     * Renderiza la página de Gestión de Pestañas.
     */
    public function render_pestanas_page() {
        
        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener ID del evento desde URL
        $evento_id = isset( $_GET['evento_id'] ) ? intval( $_GET['evento_id'] ) : 0;

        if ( ! $evento_id ) {
            wp_die( __( 'ID de evento inválido.', 'certificados-digitales' ) );
        }

        // Obtener datos del evento
        $evento = $this->eventos_manager->get_evento_by_id( $evento_id );

        if ( ! $evento ) {
            wp_die( __( 'El evento no existe.', 'certificados-digitales' ) );
        }

        // Obtener pestañas del evento
        $pestanas = $this->pestanas_manager->get_pestanas_by_evento( $evento_id );
        $total_pestanas = count( $pestanas );
        $puede_agregar = $total_pestanas < 5;

        // Obtener todas las fuentes disponibles
        $fuentes = $this->fuentes_manager->get_all_fuentes();

        ?>
        <div class="wrap certificados-admin-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-admin-page"></span>
                    <h1><?php _e( 'Gestionar Pestañas', 'certificados-digitales' ); ?></h1>
                    <span class="version-badge" style="background: linear-gradient(135deg, #00a32a, #008a20);">
                        <?php echo esc_html( $evento->nombre ); ?>
                    </span>
                </div>
                <div class="dashboard-header-actions">
                    <?php if ( $puede_agregar ) : ?>
                        <button class="btn-quick-action btn-primary" id="btn-nueva-pestana">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e( 'Nueva Pestaña', 'certificados-digitales' ); ?>
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-eventos' ); ?>" class="btn-quick-action">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php _e( 'Volver a Eventos', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Contador de pestañas -->
            <div class="stats-cards-row" style="margin-bottom: 20px;">
                <div class="stat-card <?php echo $total_pestanas >= 5 ? 'stat-card-warning' : 'stat-card-info'; ?>">
                    <div class="stat-card-icon">
                        <span class="dashicons dashicons-admin-page"></span>
                    </div>
                    <div class="stat-card-data">
                        <div class="stat-card-number"><?php echo $total_pestanas; ?> / 5</div>
                        <div class="stat-card-label"><?php _e( 'Pestañas Configuradas', 'certificados-digitales' ); ?></div>
                        <?php if ( ! $puede_agregar ) : ?>
                            <div class="stat-card-period" style="color: #f0b849;">
                                ⚠️ <?php _e( 'Límite alcanzado', 'certificados-digitales' ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Listado de pestañas -->
            <div class="certificados-pestanas-container">
                
                <?php if ( $total_pestanas > 0 ) : ?>
                    <div class="pestanas-grid" id="pestanas-sortable">
                        <?php foreach ( $pestanas as $pestana ) : ?>
                            <div class="pestana-card" data-id="<?php echo $pestana->id; ?>">
                                <div class="pestana-card-header">
                                    <div class="pestana-drag-handle" title="<?php _e( 'Arrastrar para reordenar', 'certificados-digitales' ); ?>">
                                        ⋮⋮
                                    </div>
                                    <span class="pestana-orden">#<?php echo $pestana->orden; ?></span>
                                    <h3><?php echo esc_html( $pestana->nombre_pestana ); ?></h3>
                                </div>

                                <div class="pestana-card-body">
                                    <?php if ( ! empty( $pestana->plantilla_url ) ) : ?>
                                        <div class="pestana-preview">
                                            <img src="<?php echo esc_url( $pestana->plantilla_url ); ?>" 
                                                alt="<?php echo esc_attr( $pestana->nombre_pestana ); ?>">
                                        </div>
                                    <?php else : ?>
                                        <div class="pestana-no-preview">
                                            <span class="no-preview-icon">🖼️</span>
                                            <p><?php _e( 'Sin plantilla', 'certificados-digitales' ); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="pestana-info">
                                        <p>
                                            <strong><?php _e( 'Hoja:', 'certificados-digitales' ); ?></strong>
                                            <code><?php echo esc_html( $pestana->nombre_hoja_sheet ); ?></code>
                                        </p>
                                    </div>
                                </div>

                                <div class="pestana-card-footer">
                                    <button class="button button-small btn-editar-pestana" 
                                            data-id="<?php echo $pestana->id; ?>"
                                            title="<?php _e( 'Editar', 'certificados-digitales' ); ?>">
                                        ✏️ <?php _e( 'Editar', 'certificados-digitales' ); ?>
                                    </button>
                                    <button class="button button-small btn-configurar-campos" 
                                            data-id="<?php echo $pestana->id; ?>"
                                            title="<?php _e( 'Configurar Campos', 'certificados-digitales' ); ?>">
                                        ⚙️ <?php _e( 'Campos', 'certificados-digitales' ); ?>
                                    </button>
                                    <button class="button button-small button-danger btn-eliminar-pestana" 
                                            data-id="<?php echo $pestana->id; ?>"
                                            data-nombre="<?php echo esc_attr( $pestana->nombre_pestana ); ?>"
                                            title="<?php _e( 'Eliminar', 'certificados-digitales' ); ?>">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <div class="empty-icon">📑</div>
                        <p><?php _e( 'No hay pestañas creadas para este evento.', 'certificados-digitales' ); ?></p>
                        <p class="empty-description">
                            <?php _e( 'Crea al menos una pestaña para empezar a configurar los certificados.', 'certificados-digitales' ); ?>
                        </p>
                        <button class="button button-primary button-hero" id="btn-crear-primera-pestana">
                            <?php _e( 'Crear Primera Pestaña', 'certificados-digitales' ); ?>
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Modal: Crear/Editar Pestaña -->
        <div id="modal-pestana" class="certificados-modal" style="display:none;">
            <div class="certificados-modal-content">
                <div class="certificados-modal-header">
                    <h2 id="modal-pestana-title"><?php _e( 'Nueva Pestaña', 'certificados-digitales' ); ?></h2>
                    <button class="certificados-modal-close" id="btn-close-modal-pestana">&times;</button>
                </div>
                
                <div class="certificados-modal-body">
                    <form id="form-pestana" enctype="multipart/form-data">
                        <input type="hidden" id="pestana_id" name="pestana_id" value="">
                        <input type="hidden" name="evento_id" value="<?php echo $evento_id; ?>">

                        <div class="form-row">
                            <label for="pestana_nombre">
                                <?php _e( 'Nombre de la Pestaña', 'certificados-digitales' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="pestana_nombre" 
                                name="nombre_pestana" 
                                class="regular-text" 
                                required
                                placeholder="<?php _e( 'Ej: Asistentes, Ponentes, Organizadores', 'certificados-digitales' ); ?>"
                            />
                            <p class="description">
                                <?php _e( 'Este nombre aparecerá como pestaña en el formulario público', 'certificados-digitales' ); ?>
                            </p>
                        </div>

                        <div class="form-row">
                            <label for="pestana_hoja">
                                <?php _e( 'Nombre de la Hoja en Google Sheets', 'certificados-digitales' ); ?>
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="pestana_hoja" 
                                name="nombre_hoja_sheet" 
                                class="regular-text" 
                                required
                                placeholder="<?php _e( 'Ej: Hoja1, Asistentes, Datos', 'certificados-digitales' ); ?>"
                            />
                            <p class="description">
                                <?php _e( 'Debe coincidir exactamente con el nombre de la pestaña en tu Google Sheet', 'certificados-digitales' ); ?>
                            </p>
                        </div>

                        <div class="form-row">
                            <label for="pestana_plantilla">
                                <?php _e( 'Plantilla de Certificado', 'certificados-digitales' ); ?>
                                <span class="required">*</span>
                            </label>
                            
                            <div id="preview-plantilla-container" style="display:none; margin-bottom: 15px;">
                                <img id="preview-plantilla" src="" alt="Vista previa" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;">
                                <button type="button" class="button button-small" id="btn-cambiar-plantilla" style="margin-top: 10px;">
                                    <?php _e( 'Cambiar plantilla', 'certificados-digitales' ); ?>
                                </button>
                            </div>

                            <div id="upload-plantilla-container">
                                <input 
                                    type="file" 
                                    id="pestana_plantilla" 
                                    name="plantilla_archivo" 
                                    accept="image/jpeg,image/png"
                                />
                                <p class="description">
                                    <?php _e( 'Sube una imagen JPG o PNG (máx. 5MB). Tamaño recomendado: 1920x1080px en orientación horizontal.', 'certificados-digitales' ); ?>
                                </p>
                            </div>

                            <input type="hidden" id="plantilla_url_actual" name="plantilla_url" value="">
                        </div>

                        <div id="form-pestana-message" class="form-message"></div>
                    </form>
                </div>

                <div class="certificados-modal-footer">
                    <button type="button" class="button" id="btn-cancel-modal-pestana">
                        <?php _e( 'Cancelar', 'certificados-digitales' ); ?>
                    </button>
                    <button type="submit" form="form-pestana" class="button button-primary" id="btn-save-pestana">
                        <?php _e( 'Guardar Pestaña', 'certificados-digitales' ); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </div>
        </div>

        <?php
    }


    /**
     * Renderiza la página del Configurador de Campos.
     */
    public function render_configurador_page() {
        
        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para acceder a esta página.', 'certificados-digitales' ) );
        }

        // Obtener ID de la pestaña desde URL
        $pestana_id = isset( $_GET['pestana_id'] ) ? intval( $_GET['pestana_id'] ) : 0;

        if ( ! $pestana_id ) {
            wp_die( __( 'ID de pestaña inválido.', 'certificados-digitales' ) );
        }

        // Obtener datos de la pestaña
        $pestana = $this->pestanas_manager->get_pestana_by_id( $pestana_id );

        if ( ! $pestana ) {
            wp_die( __( 'La pestaña no existe.', 'certificados-digitales' ) );
        }

        // Obtener datos del evento
        $evento = $this->eventos_manager->get_evento_by_id( $pestana->evento_id );

        // Obtener campos configurados
        $campos = $this->campos_manager->get_campos_by_pestana( $pestana_id );
        
        // Obtener todas las fuentes disponibles
        $fuentes = $this->fuentes_manager->get_all_fuentes();
        
        // Obtener tipos de campos disponibles
        $tipos_campos = $this->campos_manager->get_tipos_campos();

        ?>
        <div class="wrap certificados-admin-wrap configurador-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-edit"></span>
                    <h1><?php _e( 'Configurador de Campos', 'certificados-digitales' ); ?></h1>
                    <span class="version-badge" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <?php echo esc_html( $pestana->nombre_pestana ); ?>
                    </span>
                </div>
                <div class="dashboard-header-actions">
                    <button class="btn-quick-action btn-primary" id="btn-guardar-todo">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e( 'Guardar Cambios', 'certificados-digitales' ); ?>
                    </button>
                    <div id="btn-calibracion-container"></div>
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-pestanas&evento_id=' . $evento->id ); ?>" class="btn-quick-action">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php _e( 'Volver a Pestañas', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Breadcrumb -->
            <div style="margin-bottom: 20px; padding: 10px 15px; background: #f6f7f7; border-radius: 8px; font-size: 13px;">
                <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-eventos' ); ?>" style="color: #0073aa; text-decoration: none;">
                    <?php _e( 'Eventos', 'certificados-digitales' ); ?>
                </a>
                <span style="color: #646970; margin: 0 5px;">→</span>
                <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-pestanas&evento_id=' . $evento->id ); ?>" style="color: #0073aa; text-decoration: none;">
                    <?php echo esc_html( $evento->nombre ); ?>
                </a>
                <span style="color: #646970; margin: 0 5px;">→</span>
                <strong style="color: #1d2327;"><?php echo esc_html( $pestana->nombre_pestana ); ?></strong>
            </div>

            <div class="configurador-container">
                
                <!-- Panel Izquierdo: Campos disponibles -->
                <div class="configurador-sidebar">
                    <div class="sidebar-section">
                        <h3><?php _e( 'Campos Disponibles', 'certificados-digitales' ); ?></h3>
                        <p class="sidebar-description">
                            <?php _e( 'Arrastra los campos sobre la plantilla para posicionarlos', 'certificados-digitales' ); ?>
                        </p>
                        
                        <div class="campos-disponibles" id="campos-disponibles">
                            <?php foreach ( $tipos_campos as $tipo ) : 
                                $label = $this->campos_manager->get_label_campo( $tipo );
                                
                                // Verificar si ya existe este campo
                                $campo_existe = false;
                                foreach ( $campos as $campo ) {
                                    if ( $campo->campo_tipo === $tipo ) {
                                        $campo_existe = true;
                                        break;
                                    }
                                }
                            ?>
                                <div class="campo-item <?php echo $campo_existe ? 'campo-usado' : ''; ?>" 
                                    data-tipo="<?php echo esc_attr( $tipo ); ?>"
                                    draggable="<?php echo ( $tipo === 'documento' ) ? 'true' : ( $campo_existe ? 'false' : 'true' ); ?>">
                                    <span class="campo-icon">
                                        <?php echo $this->get_campo_icon( $tipo ); ?>
                                    </span>
                                    <span class="campo-label"><?php echo esc_html( $label ); ?></span>
                                    <?php if ( $campo_existe ) : ?>
                                        <span class="campo-badge">✓</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3><?php _e( 'Instrucciones', 'certificados-digitales' ); ?></h3>
                        <ul class="instrucciones-list">
                            <li>📌 <?php _e( 'Arrastra los campos sobre la plantilla', 'certificados-digitales' ); ?></li>
                            <li>✏️ <?php _e( 'Haz clic en un campo para configurarlo', 'certificados-digitales' ); ?></li>
                            <li>🎨 <?php _e( 'Ajusta fuente, tamaño y color', 'certificados-digitales' ); ?></li>
                            <li>💾 <?php _e( 'Guarda cuando termines', 'certificados-digitales' ); ?></li>
                        </ul>
                    </div>
                </div>

                <!-- Centro: Canvas con plantilla -->
                <!-- Centro: Canvas con plantilla -->
                <div class="configurador-canvas-wrapper">
                    <div class="canvas-wrapper">
                        <!-- Los botones de zoom se agregarán aquí con JS -->
                        <div class="canvas-container" id="canvas-container">
                            <?php if ( ! empty( $pestana->plantilla_url ) ) : ?>
                                <img src="<?php echo esc_url( $pestana->plantilla_url ); ?>" 
                                    alt="<?php echo esc_attr( $pestana->nombre_pestana ); ?>"
                                    id="plantilla-imagen"
                                    class="plantilla-background">
                                
                                <div class="canvas-overlay" id="canvas-overlay">
                                    <!-- Aquí se renderizarán los campos posicionados -->
                                    <?php foreach ( $campos as $campo ) : ?>
                                        <div class="campo-posicionado" 
                                            data-id="<?php echo $campo->id; ?>"
                                            data-tipo="<?php echo esc_attr( $campo->campo_tipo ); ?>"
                                            <?php if ( $campo->campo_tipo === 'qr' ) : ?>
                                                data-qr-size="<?php echo isset( $campo->qr_size ) ? intval( $campo->qr_size ) : 20; ?>"
                                            <?php endif; ?>
                                            style="top: <?php echo $campo->posicion_top; ?>%; left: <?php echo $campo->posicion_left; ?>%; font-size: <?php echo $campo->font_size; ?>px; color: <?php echo $campo->color; ?>; font-family: '<?php echo esc_attr( $campo->font_family ); ?>'; text-align: <?php echo $campo->alineacion; ?>;">
                                            <span class="campo-texto">
                                                <?php echo $this->get_campo_preview_text( $campo->campo_tipo ); ?>
                                            </span>
                                            <div class="campo-controls">
                                                <button class="btn-edit-campo" title="<?php _e( 'Editar', 'certificados-digitales' ); ?>">✏️</button>
                                                <button class="btn-delete-campo" title="<?php _e( 'Eliminar', 'certificados-digitales' ); ?>">🗑️</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="plantilla-no-disponible">
                                    <span class="no-plantilla-icon">🖼️</span>
                                    <p><?php _e( 'No hay plantilla disponible para esta pestaña.', 'certificados-digitales' ); ?></p>
                                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales-pestanas&evento_id=' . $evento->id ); ?>" 
                                    class="button">
                                        <?php _e( 'Subir Plantilla', 'certificados-digitales' ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho: Propiedades del campo -->
                <div class="configurador-properties" id="properties-panel">
                    <div class="properties-empty">
                        <span class="properties-icon">👆</span>
                        <p><?php _e( 'Selecciona un campo para configurar sus propiedades', 'certificados-digitales' ); ?></p>
                    </div>
                    
                    <div class="properties-content" style="display:none;">
                        <h3 id="properties-title"><?php _e( 'Propiedades del Campo', 'certificados-digitales' ); ?></h3>
                        
                        <div class="property-group">
                            <label><?php _e( 'Tipo de Campo', 'certificados-digitales' ); ?></label>
                            <input type="text" id="prop-tipo" readonly class="readonly-input">
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Posición Top (%)', 'certificados-digitales' ); ?></label>
                            <input type="number" id="prop-top" step="0.1" min="0" max="100">
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Posición Left (%)', 'certificados-digitales' ); ?></label>
                            <input type="number" id="prop-left" step="0.1" min="0" max="100">
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Fuente', 'certificados-digitales' ); ?></label>
                            <select id="prop-font-family">
                                <option value="">-- <?php _e( 'Seleccionar', 'certificados-digitales' ); ?> --</option>
                                <?php foreach ( $fuentes as $fuente ) : ?>
                                    <option value="<?php echo esc_attr( $fuente->nombre_fuente ); ?>">
                                        <?php echo esc_html( $fuente->nombre_fuente ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Estilo', 'certificados-digitales' ); ?></label>
                            <select id="prop-font-style">
                                <option value="normal"><?php _e( 'Normal', 'certificados-digitales' ); ?></option>
                                <option value="bold"><?php _e( 'Negrita', 'certificados-digitales' ); ?></option>
                                <option value="italic"><?php _e( 'Cursiva', 'certificados-digitales' ); ?></option>
                                <option value="bold-italic"><?php _e( 'Negrita Cursiva', 'certificados-digitales' ); ?></option>
                            </select>
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Tamaño (px)', 'certificados-digitales' ); ?></label>
                            <input type="number" id="prop-font-size" min="8" max="200" value="16">
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Color', 'certificados-digitales' ); ?></label>
                            <input type="color" id="prop-color" value="#000000">
                        </div>

                        <div class="property-group">
                            <label><?php _e( 'Alineación', 'certificados-digitales' ); ?></label>
                            <select id="prop-alineacion">
                                <option value="left"><?php _e( 'Izquierda', 'certificados-digitales' ); ?></option>
                                <option value="center"><?php _e( 'Centro', 'certificados-digitales' ); ?></option>
                                <option value="right"><?php _e( 'Derecha', 'certificados-digitales' ); ?></option>
                            </select>
                        </div>


                        <div class="property-group" id="prop-qr-size-group" style="display:none;">
                            <label><?php _e( 'Tamaño QR (mm)', 'certificados-digitales' ); ?></label>
                            <input type="number" id="prop-qr-size" min="10" max="60" value="20" step="1">
                            <p class="description"><?php _e( 'Tamaño del código QR en milímetros (10-60)', 'certificados-digitales' ); ?></p>
                        </div>

                        <input type="hidden" id="campo-actual-id">
                        <input type="hidden" id="campo-actual-tipo">
                    </div>
                </div>

            </div>

            <!-- Data para JavaScript -->
            <input type="hidden" id="pestana-id" value="<?php echo $pestana_id; ?>">
            <input type="hidden" id="campos-data" value='<?php echo json_encode( $campos ); ?>'>
        </div>
        <?php
    }

    /**
     * Obtiene el icono de un tipo de campo.
     */
    private function get_campo_icon( $tipo ) {
        $icons = array(
            'nombre'        => '👤',
            'documento'     => '🆔',
            'trabajo'       => '📄',
            'qr'            => '📱',
            'fecha_emision' => '📅',
        );
        return isset( $icons[ $tipo ] ) ? $icons[ $tipo ] : '📌';
    }

    /**
     * Obtiene el texto de preview de un tipo de campo.
     */
    private function get_campo_preview_text( $tipo ) {
        $texts = array(
            'nombre'        => 'Juan Pérez García',
            'documento'     => 'CC 1234567890 - Bogotá',
            'trabajo'       => 'Desarrollo de Software con IA',
            'qr'            => '[QR]',
            'fecha_emision' => date_i18n( get_option( 'date_format' ) ),
        );
        return isset( $texts[ $tipo ] ) ? $texts[ $tipo ] : $tipo;
    }




    /**
     * AJAX: Generar certificado PDF
     */
    public function ajax_generar_certificado() {
        // Verificar nonce
        check_ajax_referer( 'certificados_frontend_nonce', 'nonce' );

        // Obtener datos
        $pestana_id = isset( $_POST['pestana_id'] ) ? intval( $_POST['pestana_id'] ) : 0;
        $numero_documento = isset( $_POST['numero_documento'] ) ? sanitize_text_field( $_POST['numero_documento'] ) : '';

        // Validación de datos básicos
        if ( ! $pestana_id || empty( $numero_documento ) ) {
            wp_send_json_error( array(
                'message' => __( 'Datos incompletos.', 'certificados-digitales' )
            ) );
        }

        // Validación de longitud del número de documento
        if ( strlen( $numero_documento ) > 20 ) {
            wp_send_json_error( array(
                'message' => __( 'El número de documento no puede exceder 20 caracteres.', 'certificados-digitales' )
            ) );
        }

        // Validación de caracteres permitidos (solo alfanuméricos, guiones y espacios)
        if ( ! preg_match( '/^[a-zA-Z0-9\-\s]+$/', $numero_documento ) ) {
            wp_send_json_error( array(
                'message' => __( 'El número de documento contiene caracteres no permitidos.', 'certificados-digitales' )
            ) );
        }

        // Sanitización adicional: eliminar múltiples espacios y trim
        $numero_documento = preg_replace( '/\s+/', ' ', trim( $numero_documento ) );

        try {
            global $wpdb;

            // Obtener la pestaña y verificar que el evento está activo
            $pestana = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}certificados_pestanas WHERE id = %d",
                    $pestana_id
                ),
                ARRAY_A
            );

            if ( ! $pestana ) {
                wp_send_json_error( array(
                    'message' => __( 'Pestaña no encontrada.', 'certificados-digitales' )
                ) );
            }

            // Obtener el evento
            $evento = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                    $pestana['evento_id']
                ),
                ARRAY_A
            );

            if ( ! $evento ) {
                wp_send_json_error( array(
                    'message' => __( 'Evento no encontrado.', 'certificados-digitales' )
                ) );
            }

            // VALIDACIÓN: Verificar si el evento está inactivo
            if ( ! $evento['activo'] ) {
                $evento_nombre = isset( $evento['nombre'] ) ? $evento['nombre'] : 'el evento';
                $mensaje = sprintf(
                    __( 'Se ha deshabilitado la descarga de certificados al evento "%s". Por favor, comunícate con webmaster@uninavarra.edu.co', 'certificados-digitales' ),
                    $evento_nombre
                );
                wp_send_json_error( array(
                    'message' => $mensaje,
                    'event_disabled' => true
                ) );
            }

            // VALIDACIÓN: Verificar encuesta obligatoria (nueva funcionalidad v1.3.0+)
            if ( class_exists( 'Certificados_Survey_Manager' ) ) {
                $survey_manager = new Certificados_Survey_Manager();
                $api_key = get_option( 'certificados_digitales_api_key', '' );

                if ( ! empty( $api_key ) ) {
                    $survey_check = $survey_manager->check_survey_completed( $evento['id'], $numero_documento, $api_key );

                    // Si la encuesta es obligatoria y NO ha sido completada
                    if ( isset( $survey_check['completed'] ) && ! $survey_check['completed'] ) {
                        $survey_config = $survey_manager->get_survey_config( $evento['id'] );

                        $mensaje = ! empty( $survey_config->survey_message )
                            ? $survey_config->survey_message
                            : __( 'Debes completar la encuesta de satisfacción antes de descargar tu certificado.', 'certificados-digitales' );

                        wp_send_json_error( array(
                            'message' => $mensaje,
                            'survey_required' => true,
                            'survey_url' => $survey_config->survey_url,
                            'survey_title' => $survey_config->survey_title,
                            'survey_mode' => $survey_config->survey_mode
                        ) );
                    }
                }
            }

            // Verificar si el sheet ha cambiado (detección de cambios en campos críticos)
            require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-sheets-cache-manager.php';
            $cache_manager = new Certificados_Sheets_Cache_Manager();
            $api_key = get_option( 'certificados_digitales_api_key', '' );

            $sheet_needs_refresh = false;
            if ( ! empty( $api_key ) ) {
                $sheet_needs_refresh = $cache_manager->needs_refresh(
                    $evento['sheet_id'],
                    $pestana['nombre_hoja_sheet'],
                    $api_key
                );
            }

            // Verificar si existe en caché PDF
            $cache = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}certificados_cache
                    WHERE pestana_id = %d
                    AND numero_documento = %s
                    AND fecha_generacion > DATE_SUB(NOW(), INTERVAL 30 DAY)",
                    $pestana_id,
                    $numero_documento
                ),
                ARRAY_A
            );

            // Si hay caché PDF válido Y el sheet NO ha cambiado
            if ( $cache && file_exists( $cache['ruta_archivo'] ) && ! $sheet_needs_refresh ) {
                // Devolver desde caché
                $download_url = $this->get_download_url( $cache['ruta_archivo'] );

                // Incrementar contador de descargas
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}certificados_cache
                    SET descargas = descargas + 1
                    WHERE id = %d",
                    $cache['id']
                ) );

                // Verificar si hay encuesta opcional
                $survey_data = $this->get_optional_survey_data( $evento['id'] );

                wp_send_json_success( array(
                    'message' => __( 'Certificado encontrado.', 'certificados-digitales' ),
                    'download_url' => $download_url,
                    'from_cache' => true,
                    'survey_data' => $survey_data
                ) );
            }

            // Si el sheet cambió, eliminar el PDF en caché para regenerarlo
            if ( $cache && $sheet_needs_refresh ) {
                // Eliminar archivo físico si existe
                if ( file_exists( $cache['ruta_archivo'] ) ) {
                    @unlink( $cache['ruta_archivo'] );
                }

                // Eliminar registro de caché
                $wpdb->delete(
                    $wpdb->prefix . 'certificados_cache',
                    array( 'id' => $cache['id'] ),
                    array( '%d' )
                );
            }

            // Generar nuevo certificado
            $generator = new Certificados_PDF_Generator( $pestana_id );
            
            // Buscar participante
            if ( ! $generator->buscar_participante( $numero_documento ) ) {
                wp_send_json_error( array(
                    'message' => __( 'No se encontró un certificado con ese número de documento.', 'certificados-digitales' )
                ) );
            }

            // Generar PDF
            $filepath = $generator->generar_pdf();

            // Guardar en caché
            $wpdb->insert(
                $wpdb->prefix . 'certificados_cache',
                array(
                    'pestana_id' => $pestana_id,
                    'numero_documento' => $numero_documento,
                    'ruta_archivo' => $filepath,
                    'fecha_generacion' => current_time( 'mysql' ),
                    'descargas' => 1
                ),
                array( '%d', '%s', '%s', '%s', '%d' )
            );

            // Generar URL de descarga
            $download_url = $this->get_download_url( $filepath );

            // Verificar si hay encuesta opcional
            $survey_data = $this->get_optional_survey_data( $evento['id'] );

            wp_send_json_success( array(
                'message' => __( 'Certificado generado correctamente.', 'certificados-digitales' ),
                'download_url' => $download_url,
                'from_cache' => false,
                'survey_data' => $survey_data
            ) );

        } catch ( Exception $e ) {
            // Log del error para debugging
            error_log( 'Error al generar certificado: ' . $e->getMessage() );
            error_log( 'Trace: ' . $e->getTraceAsString() );

            wp_send_json_error( array(
                'message' => __( 'Error al generar el certificado: ', 'certificados-digitales' ) . $e->getMessage()
            ) );
        } catch ( Error $e ) {
            // Capturar errores fatales de PHP 7+
            error_log( 'Error fatal al generar certificado: ' . $e->getMessage() );
            error_log( 'Trace: ' . $e->getTraceAsString() );

            wp_send_json_error( array(
                'message' => __( 'Error crítico al procesar la solicitud. Por favor, contacte al administrador.', 'certificados-digitales' )
            ) );
        }
    }

    /**
     * Generar URL de descarga segura
     * 
     * @param string $filepath Ruta del archivo
     * @return string URL de descarga
     */
    private function get_download_url( $filepath ) {
        $upload_dir = wp_upload_dir();
        $base_path = $upload_dir['basedir'];
        $base_url = $upload_dir['baseurl'];
        
        return str_replace( $base_path, $base_url, $filepath );
    }

    /**
     * AJAX: Registrar log de descarga
     */
    public function ajax_registrar_descarga() {
        // Verificar nonce
        check_ajax_referer( 'certificados_frontend_nonce', 'nonce' );

        global $wpdb;

        $pestana_id = isset( $_POST['pestana_id'] ) ? intval( $_POST['pestana_id'] ) : 0;
        $numero_documento = isset( $_POST['numero_documento'] ) ? sanitize_text_field( $_POST['numero_documento'] ) : '';

        if ( ! $pestana_id || empty( $numero_documento ) ) {
            wp_send_json_error();
        }

        $ip = $this->get_client_ip();
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
        $fecha_actual = current_time( 'mysql' );

        // Registrar en tabla de logs (registro detallado)
        $wpdb->insert(
            $wpdb->prefix . 'certificados_descargas_log',
            array(
                'pestana_id' => $pestana_id,
                'numero_documento' => $numero_documento,
                'accion' => 'descarga',
                'ip' => $ip,
                'user_agent' => $user_agent,
                'fecha' => $fecha_actual
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        // Registrar en tabla de estadísticas (simplificada para reportes)
        $wpdb->insert(
            $wpdb->prefix . 'certificados_descargas',
            array(
                'pestana_id' => $pestana_id,
                'numero_documento' => $numero_documento,
                'fecha_descarga' => $fecha_actual,
                'ip_descarga' => $ip,
                'user_agent' => $user_agent
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        wp_send_json_success();
    }

    /**
     * Obtener datos de encuesta opcional
     *
     * @param int $evento_id ID del evento
     * @return array|null Datos de la encuesta si es opcional, null si no existe o es obligatoria
     */
    private function get_optional_survey_data( $evento_id ) {
        if ( ! class_exists( 'Certificados_Survey_Manager' ) ) {
            return null;
        }

        $survey_manager = new Certificados_Survey_Manager();
        $survey_config = $survey_manager->get_survey_config( $evento_id );

        // Solo devolver datos si la encuesta existe y es opcional
        if ( $survey_config && $survey_config->survey_mode === 'optional' ) {
            return array(
                'mode' => 'optional',
                'url' => $survey_config->survey_url,
                'title' => $survey_config->survey_title,
                'message' => $survey_config->survey_message
            );
        }

        return null;
    }

    /**
     * Obtener IP del cliente
     *
     * @return string IP del cliente
     */
    private function get_client_ip() {
        $ip = '';

        if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field( $ip );
    }

    /**
     * AJAX: Validar certificado
     */
    public function ajax_validar_certificado() {
        // Verificar nonce
        check_ajax_referer( 'certificados_frontend_nonce', 'nonce' );

        global $wpdb;

        // Obtener parámetros
        $pestana_id = isset( $_POST['pestana_id'] ) ? intval( $_POST['pestana_id'] ) : 0;
        $numero_documento = isset( $_POST['numero_documento'] ) ? sanitize_text_field( $_POST['numero_documento'] ) : '';

        // Validación básica
        if ( ! $pestana_id || empty( $numero_documento ) ) {
            wp_send_json_error( array(
                'message' => __( 'Datos incompletos para validar.', 'certificados-digitales' )
            ) );
        }

        // Validación de longitud y caracteres
        if ( strlen( $numero_documento ) > 20 || ! preg_match( '/^[a-zA-Z0-9\-\s]+$/', $numero_documento ) ) {
            wp_send_json_error( array(
                'message' => __( 'Número de documento inválido.', 'certificados-digitales' )
            ) );
        }

        try {
            // Obtener pestaña
            $pestana = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}certificados_pestanas WHERE id = %d",
                    $pestana_id
                ),
                ARRAY_A
            );

            if ( ! $pestana ) {
                wp_send_json_error( array(
                    'message' => __( 'Certificado no encontrado.', 'certificados-digitales' )
                ) );
            }

            // Obtener evento
            $evento = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                    $pestana['evento_id']
                ),
                ARRAY_A
            );

            if ( ! $evento ) {
                wp_send_json_error( array(
                    'message' => __( 'Evento no encontrado.', 'certificados-digitales' )
                ) );
            }

            // Obtener API Key
            $api_key = get_option( 'certificados_digitales_api_key', '' );

            if ( empty( $api_key ) ) {
                wp_send_json_error( array(
                    'message' => __( 'Error de configuración del sistema.', 'certificados-digitales' )
                ) );
            }

            // Buscar en Google Sheets
            require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-google-sheets.php';

            $sheets = new Certificados_Google_Sheets(
                $api_key,
                $evento['sheet_id']
            );

            $participante = $sheets->buscar_por_documento(
                $pestana['nombre_hoja_sheet'],
                $numero_documento
            );

            if ( $participante ) {
                // Certificado válido
                wp_send_json_success( array(
                    'valido' => true,
                    'mensaje' => __( '✅ Certificado Válido', 'certificados-digitales' ),
                    'datos' => array(
                        'nombre' => isset( $participante['nombre'] ) ? $participante['nombre'] : '',
                        'documento' => $numero_documento,
                        'evento' => $evento['nombre'],
                        'tipo' => $pestana['nombre_pestana']
                    )
                ) );
            } else {
                // Certificado no encontrado
                wp_send_json_success( array(
                    'valido' => false,
                    'mensaje' => __( '❌ Certificado No Válido', 'certificados-digitales' ),
                    'descripcion' => __( 'No se encontró ningún certificado con ese número de documento.', 'certificados-digitales' )
                ) );
            }

        } catch ( Exception $e ) {
            wp_send_json_error( array(
                'message' => __( 'Error al validar el certificado. Por favor, intente nuevamente.', 'certificados-digitales' )
            ) );
        }
    }

    /**
     * Inyecta CSS personalizado con los colores configurados
     */
    public function inject_custom_colors_css() {
        // Obtener colores personalizados
        $color_primario = get_option( 'certificados_color_primario', '#2271b1' );
        $color_hover = get_option( 'certificados_color_hover', '#135e96' );
        $color_exito = get_option( 'certificados_color_exito', '#00a32a' );
        $color_error = get_option( 'certificados_color_error', '#d63638' );

        // Generar tonalidades para diferentes estados
        $color_primario_light = $this->adjust_color_brightness( $color_primario, 0.9 );
        $color_primario_dark = $this->adjust_color_brightness( $color_primario, -0.1 );
        $rgb_exito = implode(',', $this->hex_to_rgb_array( $color_exito ) );
        $rgb_error = implode(',', $this->hex_to_rgb_array( $color_error ) );

        // Construir el CSS directamente con echo dentro del hook
        ob_start();
        ?>
        <!-- CERTIFICADOS PRO: CSS Personalizado Inyectado v1.0.9 -->
        <style type="text/css" id="certificados-custom-colors">
        /* ========================================
           COLORES PERSONALIZADOS DEL PLUGIN
           Estos estilos sobrescriben todos los del tema
           ======================================== */

        /* Botones primarios - Máxima especificidad */
        body .certificados-container .certificados-form button.certificados-btn-buscar,
        body .certificados-container button.certificados-btn-buscar,
        body button.certificados-btn-buscar,
        body .certificados-btn-buscar,
        body .certificados-container .certificados-btn-primary,
        body .certificados-container .certificados-btn-download {
            background: <?php echo esc_html($color_primario); ?> !important;
            background-color: <?php echo esc_html($color_primario); ?> !important;
            background-image: none !important;
            border-color: <?php echo esc_html($color_primario); ?> !important;
            color: #fff !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15) !important;
        }

        /* Botones hover */
        body .certificados-container .certificados-form button.certificados-btn-buscar:hover,
        body .certificados-container button.certificados-btn-buscar:hover,
        body button.certificados-btn-buscar:hover,
        body .certificados-btn-buscar:hover,
        body .certificados-container .certificados-btn-primary:hover,
        body .certificados-container .certificados-btn-download:hover {
            background: <?php echo esc_html($color_hover); ?> !important;
            background-color: <?php echo esc_html($color_hover); ?> !important;
            background-image: none !important;
            border-color: <?php echo esc_html($color_hover); ?> !important;
            color: #fff !important;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-2px) !important;
        }

        /* Botones active */
        body .certificados-container .certificados-form button.certificados-btn-buscar:active,
        body .certificados-container button.certificados-btn-buscar:active,
        body button.certificados-btn-buscar:active,
        body .certificados-btn-buscar:active,
        body .certificados-container .certificados-btn-primary:active {
            background: <?php echo esc_html($color_primario_dark); ?> !important;
            background-color: <?php echo esc_html($color_primario_dark); ?> !important;
            background-image: none !important;
            border-color: <?php echo esc_html($color_primario_dark); ?> !important;
            color: #fff !important;
            transform: translateY(0) !important;
        }

        /* Estado deshabilitado */
        body .certificados-container button.certificados-btn-buscar:disabled,
        body button.certificados-btn-buscar:disabled,
        body .certificados-btn-buscar:disabled {
            background: <?php echo esc_html($color_primario); ?> !important;
            background-color: <?php echo esc_html($color_primario); ?> !important;
            background-image: none !important;
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }

        /* Pestañas activas */
        body .certificados-container .certificados-tab.active {
            background: <?php echo esc_html($color_primario); ?> !important;
            border-color: <?php echo esc_html($color_primario); ?> !important;
            color: #fff !important;
        }

        /* Pestañas hover */
        body .certificados-container .certificados-tab:hover:not(.active) {
            background: <?php echo esc_html($color_primario_light); ?> !important;
            color: <?php echo esc_html($color_primario); ?> !important;
        }

        /* Input focus */
        body .certificados-container .certificados-input:focus {
            border-color: <?php echo esc_html($color_primario); ?> !important;
            box-shadow: 0 0 0 1px <?php echo esc_html($color_primario); ?> !important;
        }

        /* Loader/Spinner */
        body .certificados-loader {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        body .certificados-loader-spinner {
            border-color: rgba(0,0,0,0.1) !important;
            border-top-color: <?php echo esc_html($color_primario); ?> !important;
        }

        /* Mensajes de éxito */
        body .certificados-container .result-success {
            border-left-color: <?php echo esc_html($color_exito); ?> !important;
            background: rgba(<?php echo esc_html($rgb_exito); ?>, 0.1) !important;
        }

        body .certificados-container .result-success .result-message strong {
            color: <?php echo esc_html($color_exito); ?> !important;
        }

        /* Mensajes de error */
        body .certificados-container .result-error {
            border-left-color: <?php echo esc_html($color_error); ?> !important;
            background: rgba(<?php echo esc_html($rgb_error); ?>, 0.1) !important;
        }

        body .certificados-container .result-error .error-message strong {
            color: <?php echo esc_html($color_error); ?> !important;
        }

        /* Modal de validación */
        body .certificados-modal-validacion .validacion-exito h4 {
            color: <?php echo esc_html($color_exito); ?> !important;
        }

        body .certificados-modal-validacion .validacion-error h4 {
            color: <?php echo esc_html($color_error); ?> !important;
        }

        body .certificados-modal .certificados-btn-primary {
            background: <?php echo esc_html($color_primario); ?> !important;
            border-color: <?php echo esc_html($color_primario); ?> !important;
        }

        body .certificados-modal .certificados-btn-primary:hover {
            background: <?php echo esc_html($color_hover); ?> !important;
            border-color: <?php echo esc_html($color_hover); ?> !important;
        }

        /* ========== ADMIN BACKEND ========== */

        /* Botones en el admin */
        body .certificados-digitales-admin .certificados-btn-primary {
            background: <?php echo esc_html($color_primario); ?> !important;
            border-color: <?php echo esc_html($color_primario); ?> !important;
        }

        body .certificados-digitales-admin .certificados-btn-primary:hover {
            background: <?php echo esc_html($color_hover); ?> !important;
            border-color: <?php echo esc_html($color_hover); ?> !important;
        }

        /* ========== SOBRESCRIBIR ESTILOS DEL TEMA ========== */

        /* Asegurar que los botones del plugin no hereden estilos del tema */
        body .certificados-container button,
        body .certificados-container .certificados-btn-buscar,
        body .certificados-container .certificados-btn-primary,
        body .certificados-container .certificados-btn-download {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
            text-transform: none !important;
            letter-spacing: normal !important;
        }

        /* Resetear transiciones del tema */
        body .certificados-container * {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease !important;
        }
        </style>
        <?php
        $custom_css = ob_get_clean();

        // Imprimir directamente el CSS
        echo $custom_css;
    }

    /**
     * Ajusta el brillo de un color hexadecimal
     *
     * @param string $hex Color en formato hexadecimal
     * @param float $percent Porcentaje de ajuste (-1 a 1)
     * @return string Color ajustado en formato hexadecimal
     */
    private function adjust_color_brightness( $hex, $percent ) {
        $hex = str_replace( '#', '', $hex );

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, min( 255, $r + ( $r * $percent ) ) );
        $g = max( 0, min( 255, $g + ( $g * $percent ) ) );
        $b = max( 0, min( 255, $b + ( $b * $percent ) ) );

        return '#' . sprintf( '%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Convierte un color hexadecimal a array RGB
     *
     * @param string $hex Color en formato hexadecimal
     * @return array Array con valores r, g, b
     */
    private function hex_to_rgb_array( $hex ) {
        $hex = str_replace( '#', '', $hex );
        return array(
            hexdec( substr( $hex, 0, 2 ) ),
            hexdec( substr( $hex, 2, 2 ) ),
            hexdec( substr( $hex, 4, 2 ) )
        );
    }
}
