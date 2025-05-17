<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Opcional: Eliminar todas las tablas y datos creados por el plugin
// Descomentar para eliminar todos los datos al desinstalar

/*
global $wpdb;
$tablas = array(
    $wpdb->prefix . 'certificados_pdf_templates',
    $wpdb->prefix . 'certificados_pdf_campos'
);

foreach ($tablas as $tabla) {
    $wpdb->query("DROP TABLE IF EXISTS {$tabla}");
}

// Eliminar opciones
delete_option('certificados_pdf_db_version');
delete_option('certificados_pdf_google_api_key');

// Eliminar archivos de certificados generados
$upload_dir = wp_upload_dir();
$cert_dir = $upload_dir['basedir'] . '/certificados';

if (file_exists($cert_dir)) {
    // Función recursiva para eliminar directorio y todos sus contenidos
    function certificados_pdf_rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        certificados_pdf_rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }
    
    certificados_pdf_rrmdir($cert_dir);
}
*/