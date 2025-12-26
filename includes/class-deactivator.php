<?php
/**
 * Clase que maneja la desactivación del plugin.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/includes
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Deactivator.
 *
 * Se ejecuta durante la desactivación del plugin.
 */
class Certificados_Digitales_Deactivator {

    /**
     * Método principal de desactivación.
     */
    public static function deactivate() {
        
        // Limpiar transients (cache temporal)
        self::clear_transients();

        // Limpiar caché de WordPress
        wp_cache_flush();

        // Limpiar rewrite rules
        flush_rewrite_rules();

        // NO eliminamos tablas ni archivos aquí
        // Eso se hace en uninstall.php si el usuario desinstala el plugin
    }

    /**
     * Limpia todos los transients del plugin.
     */
    private static function clear_transients() {
        global $wpdb;

        // Eliminar todos los transients que empiecen con nuestro prefijo
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_certificados_digitales_%' 
            OR option_name LIKE '_transient_timeout_certificados_digitales_%'"
        );
    }
}