<?php
/**
 * Plugin Name: Certificados Digitales PRO
 * Plugin URI: https://github.com/luisalbertoquino/plugins
 * Description: Sistema profesional de generación de certificados digitales en PDF con integración a Google Sheets, editor visual drag & drop y validación con códigos QR únicos.
 * Version: 1.5.13
 * Author: Luis Alberto Aquino
 * Author URI: https://github.com/luisalbertoquino
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: certificados-digitales
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Versión actual del plugin.
 */
define( 'CERTIFICADOS_DIGITALES_VERSION', '1.5.13' );

/**
 * Ruta absoluta del directorio del plugin.
 */
define( 'CERTIFICADOS_DIGITALES_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL del directorio del plugin.
 */
define( 'CERTIFICADOS_DIGITALES_URL', plugin_dir_url( __FILE__ ) );

/**
 * Nombre base del plugin (usado para hooks).
 */
define( 'CERTIFICADOS_DIGITALES_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Código que se ejecuta durante la activación del plugin.
 */
function activate_certificados_digitales() {
    require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-activator.php';
    Certificados_Digitales_Activator::activate();
}

/**
 * Código que se ejecuta durante la desactivación del plugin.
 */
function deactivate_certificados_digitales() {
    require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-deactivator.php';
    Certificados_Digitales_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_certificados_digitales' );
register_deactivation_hook( __FILE__, 'deactivate_certificados_digitales' );

/**
 * Carga el autoloader.
 */
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-autoloader.php';

/**
 * Inicializa el autoloader.
 */
Certificados_Digitales_Autoloader::register();

/**
 * Cargar clase de shortcode (frontend)
 */
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-shortcode.php';


/**
 * Cargar clase de campos
 */
require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-campos.php'; 

/**
 * Cargar clase de fuentes
 */
require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-fuentes.php';

/**
 * Cargar nuevas clases (versión 1.3.0+)
 */
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-sheets-cache-manager.php';
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-column-mapper.php';
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-survey-manager.php';
require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-stats-manager.php';

/**
 * Cargar clases de administración para nuevas funcionalidades
 */
if ( is_admin() ) {
    require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-admin-column-mapper.php';
    require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-admin-survey.php';
    require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-admin-stats.php';
    require_once CERTIFICADOS_DIGITALES_PATH . 'admin/class-admin-documentacion.php';
    require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-plugin-packager.php';
}

/**
 * Comienza la ejecución del plugin.
 */
function run_certificados_digitales() {
    $plugin = new Certificados_Digitales_Core();
    $plugin->run();

    // Inicializar clase de campos
    new Certificados_Digitales_Campos();
    // Inicializar clase de fuentes
    new Certificados_Digitales_Fuentes();

    // Inicializar nuevas funcionalidades (v1.3.0+)
    new Certificados_Sheets_Cache_Manager();
    new Certificados_Column_Mapper();
    new Certificados_Survey_Manager();
    new Certificados_Stats_Manager();

    // Inicializar páginas de administración para nuevas funcionalidades
    if ( is_admin() ) {
        new Certificados_Admin_Column_Mapper();
        new Certificados_Admin_Survey();
        new Certificados_Admin_Stats();
    }
}

// Cargar textdomain para traducciones
add_action( 'plugins_loaded', 'certificados_digitales_load_textdomain' );

function certificados_digitales_load_textdomain() {
    load_plugin_textdomain(
        'certificados-digitales',
        false,
        dirname( CERTIFICADOS_DIGITALES_BASENAME ) . '/languages/'
    );
}

// Hook para descargar el plugin empaquetado (solo en desarrollo)
add_action( 'admin_init', 'certificados_digitales_handle_package_download' );

function certificados_digitales_handle_package_download() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'certificados-digitales-config' &&
         isset( $_GET['action'] ) && $_GET['action'] === 'download_package' ) {
        Certificados_Plugin_Packager::generate_package();
    }
}

// Iniciar el plugin
run_certificados_digitales();