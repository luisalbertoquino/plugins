<?php
/**
 * Fired during plugin activation.
 * plugins-main/includes/certificados-pdf-activator.php
 * @since      1.0.0
 */
class Certificados_PDF_Activator {

    /**
     * Activación del plugin.
     *
     * Crea las tablas necesarias en la base de datos y configura los valores iniciales.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        // Charset y collate de la base de datos
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla para certificados
        $tabla_certificados = $wpdb->prefix . 'certificados_pdf_templates';
        
        // Tabla para campos de certificados
        $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
        
        // SQL para crear la tabla de certificados
        $sql_certificados = "CREATE TABLE $tabla_certificados (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            plantilla_url text NOT NULL,
            sheet_id varchar(255) NOT NULL,
            sheet_nombre varchar(255) NOT NULL,
            campo_busqueda varchar(255) NOT NULL,
            habilitado tinyint(1) NOT NULL DEFAULT 1,
            fecha_creacion datetime NOT NULL,
            fecha_modificacion datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // SQL para crear la tabla de campos
        $sql_campos = "CREATE TABLE $tabla_campos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            certificado_id mediumint(9) NOT NULL,
            nombre varchar(255) NOT NULL,
            tipo varchar(50) NOT NULL,
            columna_sheet varchar(255) NOT NULL,
            pos_x int NOT NULL DEFAULT 0,
            pos_y int NOT NULL DEFAULT 0,
            ancho int NOT NULL DEFAULT 0,
            alto int NOT NULL DEFAULT 0,
            color varchar(7) NOT NULL DEFAULT '#000000',
            tamano_fuente int NOT NULL DEFAULT 12,
            alineacion varchar(20) NOT NULL DEFAULT 'left',
            tipografia varchar(100) NOT NULL DEFAULT 'default',
            orden int NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY certificado_id (certificado_id)
        ) $charset_collate;";
        
        // Incluir el archivo dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Crear las tablas
        dbDelta($sql_certificados);
        dbDelta($sql_campos);
        
        // Versión de la base de datos
        add_option('certificados_pdf_db_version', CERT_PDF_VERSION);
        
        // Crear directorios para almacenar PDFs generados
        $upload_dir = wp_upload_dir();
        $cert_dir = $upload_dir['basedir'] . '/certificados';
        
        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            
            // Crear archivo index.php para protección
            file_put_contents($cert_dir . '/index.php', '<?php // Silence is golden');
            
            // Crear archivo .htaccess para protección adicional
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<FilesMatch \"\.pdf$\">\n";
            $htaccess_content .= "    Order Allow,Deny\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</FilesMatch>\n";
            file_put_contents($cert_dir . '/.htaccess', $htaccess_content);
        }
        
        // Crear directorio para fuentes personalizadas si no existe
        $fonts_dir = CERT_PDF_FONTS_DIR;
        if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
            // Crear archivo index.php para protección
            file_put_contents($fonts_dir . '/index.php', '<?php // Silence is golden');
        }
    }
}