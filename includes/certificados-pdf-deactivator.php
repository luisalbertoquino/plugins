<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 */
class Certificados_PDF_Deactivator {

    /**
     * Desactivación del plugin.
     *
     * Limpia archivos temporales y configuraciones.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Limpiar transients y caché
        delete_transient('certificados_pdf_cache');
        
        // No eliminamos las tablas ni los archivos generados para no perder datos del usuario
    }
}