<?php
/**
 * Clase principal del plugin.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/includes
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Core.
 *
 * Esta es la clase principal que coordina todos los componentes del plugin.
 */
class Certificados_Digitales_Core {

    /**
     * Instancia de la clase Admin.
     *
     * @var Certificados_Digitales_Admin
     */
    protected $admin;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carga las dependencias necesarias.
     */
    private function load_dependencies() {

        // Cargar clase Admin si estamos en el backend
        if ( is_admin() ) {
            // Inicializar clases para que los hooks AJAX estén disponibles
            new Certificados_Digitales_Fuentes();
            new Certificados_Digitales_Eventos();
            new Certificados_Digitales_Pestanas();
            new Certificados_Digitales_Campos();
        }

        // Inicializar Admin tanto en frontend como backend
        // (necesario para inyectar CSS personalizado en ambos lados)
        $this->admin = new Certificados_Digitales_Admin();

        // Aquí cargaremos más clases en el futuro:
        // - Public (para el frontend/shortcode)
        // - API handlers
        // - PDF Generator
        // etc.
    }

    /**
     * Define todos los hooks del área administrativa.
     */
    private function define_admin_hooks() {
        
        if ( ! is_admin() ) {
            return;
        }

        // Hook para registrar el menú administrativo
        add_action( 'admin_menu', array( $this->admin, 'register_admin_menu' ) );

        // Hook para cargar estilos del admin (lo usaremos después)
        add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_styles' ) );

        // Hook para cargar scripts del admin (lo usaremos después)
        add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_scripts' ) );
    }

    /**
     * Define todos los hooks del área pública (frontend).
     */
    private function define_public_hooks() {
        
        // Por ahora vacío, aquí registraremos:
        // - Shortcodes
        // - Estilos públicos
        // - Scripts públicos
        // - AJAX handlers para búsqueda de certificados
    }

    /**
     * Ejecuta el plugin.
     */
    public function run() {
        // El plugin ya está ejecutándose al instanciar esta clase
        // Este método existe por si necesitamos inicializaciones adicionales
    }
}