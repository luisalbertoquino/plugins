<?php
/**
 * Plugin Name: Generador de Certificados PDF
 * Plugin URI: https://tucertificadodigital.com
 * Description: Crea formularios personalizados para generar y descargar certificados en PDF a partir de datos en Google Sheets.
 * Version: 1.0.0
 * Author: Luis Alberto Quino
 * Author URI: https://uninavarra.edu.co
 * Text Domain: certificados-pdf
 * Domain Path: /languages
 * License: GPL-2.0+
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('CERT_PDF_VERSION', '1.0.0');
define('CERT_PDF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CERT_PDF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CERT_PDF_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('CERT_PDF_FONTS_DIR', WP_CONTENT_DIR . '/plugins/PLUGINS-MAIN/public/fonts/');

/**
 * Verificar requisitos al activar el plugin
 */
function certificados_pdf_check_requirements() {
    $errors = array();
    
    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'Este plugin requiere PHP 7.4 o superior. Su versión es: ' . PHP_VERSION;
    }
    
    // Verificar dependencias
    if (!file_exists(CERT_PDF_PLUGIN_DIR . 'vendor/autoload.php')) {
        $errors[] = 'Faltan dependencias. Por favor, contacte al desarrollador del plugin.';
    }
    
    // Verificar permisos de escritura
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
        $errors[] = 'El directorio de uploads no tiene permisos de escritura: ' . $upload_dir['basedir'];
    }
    
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<p>' . implode('</p><p>', $errors) . '</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">Volver a plugins</a></p>'
        );
    }
}
register_activation_hook(__FILE__, 'certificados_pdf_check_requirements');

// Incluir archivos necesarios
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf.php';
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-admin.php';
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-google-sheets.php';
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-shortcode.php';
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-generator.php';
require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-fonts.php';

// Asegurarse de que autoload.php está incluido para las dependencias de Composer
if (file_exists(CERT_PDF_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once CERT_PDF_PLUGIN_DIR . 'vendor/autoload.php';
}

// Iniciar el plugin
function run_certificados_pdf() {
    $plugin = new Certificados_PDF();
    $plugin->run();
}
run_certificados_pdf();

// Activación y desactivación del plugin
register_activation_hook(__FILE__, 'certificados_pdf_activate');
register_deactivation_hook(__FILE__, 'certificados_pdf_deactivate');

function certificados_pdf_activate() {
    // Verificar requisitos antes de activar
    certificados_pdf_check_requirements();
    
    // Crear tablas de la base de datos y configuración inicial
    require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-activator.php';
    Certificados_PDF_Activator::activate();
    
    // Limpiar reglas de reescritura
    flush_rewrite_rules();
}

function certificados_pdf_deactivate() {
    // Limpiar datos temporales y configuraciones
    require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-deactivator.php';
    Certificados_PDF_Deactivator::deactivate();
    
    // Limpiar reglas de reescritura
    flush_rewrite_rules();
}

/**
 * Redirigir archivos de certificados antiguos al nuevo sistema
 */
function certificados_pdf_redirect_legacy_files() {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-content/uploads/certificados/') !== false) {
        $filename = basename($_SERVER['REQUEST_URI']);
        if (preg_match('/^certificado_\d+_[a-f0-9]+\.pdf$/', $filename)) {
            wp_redirect(CERT_PDF_PLUGIN_URL . 'serve-pdf.php?file=' . $filename);
            exit;
        }
    }
}
add_action('template_redirect', 'certificados_pdf_redirect_legacy_files');

/**
 * Agregar regla de reescritura para certificados (URLs amigables)
 */
function certificados_pdf_add_rewrite_rules() {
    add_rewrite_rule(
        'certificado/([0-9]+)/([a-zA-Z0-9]+)/?$',
        'index.php?certificado_id=$matches[1]&certificado_hash=$matches[2]',
        'top'
    );
}
add_action('init', 'certificados_pdf_add_rewrite_rules');

/**
 * Registrar variables de consulta
 */
function certificados_pdf_query_vars($vars) {
    $vars[] = 'certificado_id';
    $vars[] = 'certificado_hash';
    return $vars;
}
add_filter('query_vars', 'certificados_pdf_query_vars');

/**
 * Manejar la plantilla para certificados
 */
function certificados_pdf_template_redirect() {
    global $wp_query;
    
    if (isset($wp_query->query_vars['certificado_id']) && isset($wp_query->query_vars['certificado_hash'])) {
        $certificado_id = intval($wp_query->query_vars['certificado_id']);
        $certificado_hash = sanitize_text_field($wp_query->query_vars['certificado_hash']);
        
        // Construir nombre de archivo
        $filename = "certificado_{$certificado_id}_{$certificado_hash}.pdf";
        
        // Redirigir al servidor de PDF
        wp_redirect(CERT_PDF_PLUGIN_URL . 'serve-pdf.php?file=' . $filename);
        exit;
    }
}
add_action('template_redirect', 'certificados_pdf_template_redirect');

/**
 * Función para crear un directorio seguro para los certificados
 */
function certificados_pdf_ensure_directory() {
    $upload_dir = wp_upload_dir();
    $cert_dir = $upload_dir['basedir'] . '/certificados';
    
    if (!file_exists($cert_dir)) {
        wp_mkdir_p($cert_dir);
        
        // Establecer permisos adecuados
        @chmod($cert_dir, 0755);
        
        // Crear archivo index.php para protección
        file_put_contents($cert_dir . '/index.php', '<?php // Silence is golden');
        
        // Crear archivo .htaccess para protección y acceso
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "<FilesMatch \"\.pdf$\">\n";
        $htaccess_content .= "    Order Allow,Deny\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        file_put_contents($cert_dir . '/.htaccess', $htaccess_content);
    }
    
    return $cert_dir;
}

/**
 * Crear directorio para certificados al activar el plugin
 */
function certificados_pdf_create_directory() {
    certificados_pdf_ensure_directory();
}
add_action('plugins_loaded', 'certificados_pdf_create_directory');