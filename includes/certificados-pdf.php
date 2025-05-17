<?php
/**
 * La clase principal del plugin.
 * includes/certificados-pdf.php
 * Esta es la clase central que coordina todas las funcionalidades del plugin
 * y registra los hooks necesarios para su funcionamiento.
 *
 * @since      1.0.0
 */
class Certificados_PDF {

    /**
     * El cargador que es responsable de mantener y registrar todos los hooks del plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $loader    Mantiene y registra todos los hooks del plugin.
     */
    protected $loader;

    /**
     * El identificador único del plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    El identificador único del plugin.
     */
    protected $plugin_name;

    /**
     * La versión actual del plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    La versión actual del plugin.
     */
    protected $version;

    /**
     * El gestor de fuentes del plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Certificados_PDF_Fonts    $fonts_manager    Gestor de fuentes.
     */
    protected $fonts_manager;

    /**
     * Define la funcionalidad central del plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'certificados-pdf';
        $this->version = CERT_PDF_VERSION;
        $this->load_dependencies();
        
        // Inicializar el gestor de fuentes
        $this->fonts_manager = new Certificados_PDF_Fonts();
        
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carga las dependencias requeridas para este plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        $this->loader = [];
        
        // Cargar biblioteca TCPDF para la generación de PDFs
        if (!class_exists('TCPDF')) {
            require_once CERT_PDF_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        }
        
        // Cargar la biblioteca de Google API Client si no está ya cargada
        if (!class_exists('Google_Client')) {
            require_once CERT_PDF_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }

    /**
     * Registra todos los hooks relacionados con la funcionalidad administrativa.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $admin = new Certificados_PDF_Admin($this->get_plugin_name(), $this->get_version());
        
        // Agregar menú y páginas de administración
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        
        // Registrar scripts y estilos del admin
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        
        // Registrar AJAX handlers para la administración
        add_action('wp_ajax_guardar_certificado', array($admin, 'guardar_certificado'));
        add_action('wp_ajax_obtener_certificado', array($admin, 'obtener_certificado'));
        add_action('wp_ajax_eliminar_certificado', array($admin, 'eliminar_certificado'));
        add_action('wp_ajax_probar_conexion_sheets', array($admin, 'probar_conexion_sheets'));
        
        // Registrar AJAX handlers para la gestión de fuentes
        add_action('wp_ajax_upload_font', array($this->fonts_manager, 'ajax_upload_font'));
        add_action('wp_ajax_delete_font', array($this->fonts_manager, 'ajax_delete_font'));
        add_action('wp_ajax_get_available_fonts', array($this->fonts_manager, 'ajax_get_available_fonts'));
        
        // Añadir página de administración para la gestión de fuentes
        add_action('admin_menu', function() {
            add_submenu_page(
                'certificados_pdf', // Parent slug
                __('Gestión de Fuentes', 'certificados-pdf'), // Page title
                __('Fuentes', 'certificados-pdf'), // Menu title
                'manage_options', // Capability
                'certificados_pdf_fonts', // Menu slug
                array($this, 'display_fonts_page') // Callback function
            );
        });
        
        // Añadir estilos para la previsualización de fuentes
        add_action('admin_head', array($this->fonts_manager, 'add_font_styles'));
    }

    /**
     * Registra todos los hooks relacionados con la funcionalidad pública.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // Registrar el shortcode
        $shortcode = new Certificados_PDF_Shortcode($this->get_plugin_name(), $this->get_version());
        add_shortcode('certificado_pdf', array($shortcode, 'mostrar_formulario'));
        
        // Registrar el shortcode de prueba - NUEVO
        add_shortcode('certificado_test', array($shortcode, 'test_shortcode'));
        
        // Registrar scripts y estilos del frontend
        add_action('wp_enqueue_scripts', array($shortcode, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($shortcode, 'enqueue_scripts'));
        
        // Registrar AJAX handlers para el frontend
        add_action('wp_ajax_generar_certificado', array($shortcode, 'generar_certificado'));
        add_action('wp_ajax_nopriv_generar_certificado', array($shortcode, 'generar_certificado'));
        
        // Registrar acción AJAX de prueba - NUEVO
        add_action('wp_ajax_certificados_pdf_test', array($shortcode, 'test_ajax'));
        add_action('wp_ajax_nopriv_certificados_pdf_test', array($shortcode, 'test_ajax'));
    }

    /**
     * Ejecuta el cargador para ejecutar todos los hooks con WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        // El loader en esta implementación simplificada ya está ejecutado a través de los hooks de WordPress
    }

    /**
     * El nombre del plugin usado para identificarlo.
     *
     * @since     1.0.0
     * @return    string    El nombre del plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * La versión del plugin.
     *
     * @since     1.0.0
     * @return    string    El número de versión del plugin.
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Muestra la página de gestión de fuentes.
     *
     * @since    1.0.0
     */
    public function display_fonts_page() {
        // Simplemente incluir el archivo de vista que contiene todo lo necesario
        include CERT_PDF_PLUGIN_DIR . 'admin/partials/certificados-pdf-admin-fonts.php';
    }
    
    /**
     * Obtiene el gestor de fuentes.
     *
     * @since     1.0.0
     * @return    Certificados_PDF_Fonts    El gestor de fuentes.
     */
    public function get_fonts_manager() {
        return $this->fonts_manager;
    }
}