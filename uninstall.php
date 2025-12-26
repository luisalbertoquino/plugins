<?php
/**
 * Archivo de desinstalación del plugin.
 *
 * Se ejecuta cuando el usuario DESINSTALA el plugin desde WordPress.
 * IMPORTANTE: Este archivo NO se ejecuta al desactivar, solo al desinstalar.
 *
 * @package Certificados_Digitales
 */

// Si uninstall no es llamado desde WordPress, salir
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Eliminar todas las tablas del plugin.
 */
function certificados_digitales_delete_tables() {
    global $wpdb;

    $table_prefix = $wpdb->prefix;

    // Array con los nombres de las tablas a eliminar
    $tables = array(
        $table_prefix . 'certificados_cache',
        $table_prefix . 'certificados_descargas_log',
        $table_prefix . 'certificados_campos_config',
        $table_prefix . 'certificados_pestanas',
        $table_prefix . 'certificados_eventos',
        $table_prefix . 'certificados_fuentes',
    );

    // Desactivar foreign key checks temporalmente
    $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

    // Eliminar cada tabla
    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS $table" );
    }

    // Reactivar foreign key checks
    $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );
}

/**
 * Eliminar todas las opciones del plugin en wp_options.
 */
function certificados_digitales_delete_options() {
    delete_option( 'certificados_digitales_version' );
    delete_option( 'certificados_digitales_activated_date' );
    delete_option( 'certificados_digitales_api_key' );
    
    // Eliminar transients
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_certificados_digitales_%' 
        OR option_name LIKE '_transient_timeout_certificados_digitales_%'"
    );
}

/**
 * Eliminar carpetas y archivos subidos.
 */
function certificados_digitales_delete_uploads() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/certificados-digitales';

    if ( file_exists( $base_dir ) ) {
        certificados_digitales_delete_directory( $base_dir );
    }
}

/**
 * Función recursiva para eliminar un directorio y su contenido.
 *
 * @param string $dir Ruta del directorio a eliminar.
 */
function certificados_digitales_delete_directory( $dir ) {
    if ( ! file_exists( $dir ) ) {
        return;
    }

    $files = array_diff( scandir( $dir ), array( '.', '..' ) );

    foreach ( $files as $file ) {
        $path = $dir . '/' . $file;
        is_dir( $path ) ? certificados_digitales_delete_directory( $path ) : unlink( $path );
    }

    rmdir( $dir );
}

/**
 * IMPORTANTE: Las tablas y datos NO se eliminan al desinstalar el plugin.
 * Esto es intencional para preservar:
 * - Todos los eventos y certificados configurados
 * - Historial de descargas y estadísticas
 * - Configuraciones de mapeo y encuestas
 * - Archivos PDF generados
 *
 * Si desea eliminar COMPLETAMENTE todos los datos del plugin,
 * debe hacerlo manualmente desde phpMyAdmin o usando el siguiente código:
 */

// DESCOMENTADO: No ejecutar limpieza automática
// certificados_digitales_delete_tables();
// certificados_digitales_delete_options();
// certificados_digitales_delete_uploads();

// Para eliminar datos manualmente, descomente las líneas anteriores