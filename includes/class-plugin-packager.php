<?php
/**
 * Clase para empaquetar el plugin limpio para producción
 * Solo disponible en entornos de desarrollo
 *
 * @package Certificados_Digitales
 */

class Certificados_Plugin_Packager {

    /**
     * Archivos y carpetas a excluir del paquete de producción
     */
    private static $exclude_patterns = array(
        '.claude',
        '.git',
        '.gitignore',
        'node_modules',
        'docs',
        'ACTUALIZACION.md',
        'NUEVAS_FUNCIONALIDADES.md',
        'EMPAQUETADO.md',
        'README.md',
        'composer.json',
        'composer.lock',
        'diagnostico.php',
        'limpiar-cache.php',
        'reiniciar-plugin.php',
        'test-clase.php',
        'package.json',
        'package-lock.json',
        '.DS_Store',
        'Thumbs.db',
        '*.log',
        '.editorconfig',
        '.eslintrc',
        '.prettierrc',
    );

    /**
     * Verifica si estamos en un entorno de desarrollo
     */
    public static function is_development_environment() {
        // Verifica si estamos en localhost o entorno de desarrollo
        $is_local = in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) );
        $is_dev_domain = strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false
                      || strpos( $_SERVER['HTTP_HOST'], '.local' ) !== false
                      || strpos( $_SERVER['HTTP_HOST'], '.test' ) !== false;

        return $is_local || $is_dev_domain || defined( 'WP_DEBUG' ) && WP_DEBUG;
    }

    /**
     * Genera el paquete ZIP del plugin
     */
    public static function generate_package() {
        // Verificar permisos
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'No tienes permisos para realizar esta acción.', 'certificados-digitales' ) );
        }

        // Verificar nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'download_plugin_package' ) ) {
            wp_die( __( 'Verificación de seguridad fallida.', 'certificados-digitales' ) );
        }

        // Verificar que estamos en entorno de desarrollo
        if ( ! self::is_development_environment() ) {
            wp_die( __( 'Esta funcionalidad solo está disponible en entornos de desarrollo.', 'certificados-digitales' ) );
        }

        // Ruta del plugin
        $plugin_dir = dirname( dirname( __FILE__ ) );
        $plugin_name = basename( $plugin_dir );

        // Crear directorio temporal
        $temp_dir = sys_get_temp_dir() . '/certificados-pro-package-' . time();
        $package_dir = $temp_dir . '/' . $plugin_name;

        if ( ! mkdir( $package_dir, 0755, true ) ) {
            wp_die( __( 'No se pudo crear el directorio temporal.', 'certificados-digitales' ) );
        }

        // Copiar archivos excluyendo los de desarrollo
        self::copy_directory( $plugin_dir, $package_dir );

        // Crear archivo ZIP
        $zip_file = $temp_dir . '/certificate-pro.zip';
        $zip = new ZipArchive();

        if ( $zip->open( $zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            wp_die( __( 'No se pudo crear el archivo ZIP.', 'certificados-digitales' ) );
        }

        // Agregar archivos al ZIP
        self::add_directory_to_zip( $zip, $package_dir, $plugin_name );
        $zip->close();

        // Enviar archivo para descarga
        if ( file_exists( $zip_file ) ) {
            header( 'Content-Type: application/zip' );
            header( 'Content-Disposition: attachment; filename="certificate-pro-' . date( 'Y-m-d-His' ) . '.zip"' );
            header( 'Content-Length: ' . filesize( $zip_file ) );
            header( 'Cache-Control: no-cache, must-revalidate' );
            header( 'Expires: 0' );

            readfile( $zip_file );

            // Limpiar archivos temporales
            self::delete_directory( $temp_dir );

            exit;
        } else {
            wp_die( __( 'Error al generar el paquete.', 'certificados-digitales' ) );
        }
    }

    /**
     * Verifica si un archivo/directorio debe ser excluido
     */
    private static function should_exclude( $path ) {
        $basename = basename( $path );

        foreach ( self::$exclude_patterns as $pattern ) {
            // Coincidencia exacta
            if ( $basename === $pattern ) {
                return true;
            }

            // Patrón con comodines
            if ( strpos( $pattern, '*' ) !== false ) {
                $regex = '/^' . str_replace( '*', '.*', preg_quote( $pattern, '/' ) ) . '$/';
                if ( preg_match( $regex, $basename ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Copia un directorio recursivamente excluyendo archivos innecesarios
     */
    private static function copy_directory( $source, $destination ) {
        if ( ! is_dir( $destination ) ) {
            mkdir( $destination, 0755, true );
        }

        $dir = opendir( $source );

        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }

            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;

            // Verificar si debe ser excluido
            if ( self::should_exclude( $source_path ) ) {
                continue;
            }

            if ( is_dir( $source_path ) ) {
                self::copy_directory( $source_path, $dest_path );
            } else {
                copy( $source_path, $dest_path );
            }
        }

        closedir( $dir );
    }

    /**
     * Agrega un directorio al archivo ZIP recursivamente
     */
    private static function add_directory_to_zip( $zip, $source, $base_path = '' ) {
        $dir = opendir( $source );

        while ( false !== ( $file = readdir( $dir ) ) ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }

            $source_path = $source . '/' . $file;
            $zip_path = $base_path . '/' . $file;

            if ( is_dir( $source_path ) ) {
                $zip->addEmptyDir( $zip_path );
                self::add_directory_to_zip( $zip, $source_path, $zip_path );
            } else {
                $zip->addFile( $source_path, $zip_path );
            }
        }

        closedir( $dir );
    }

    /**
     * Elimina un directorio recursivamente
     */
    private static function delete_directory( $dir ) {
        if ( ! is_dir( $dir ) ) {
            return;
        }

        $files = array_diff( scandir( $dir ), array( '.', '..' ) );

        foreach ( $files as $file ) {
            $path = $dir . '/' . $file;

            if ( is_dir( $path ) ) {
                self::delete_directory( $path );
            } else {
                unlink( $path );
            }
        }

        rmdir( $dir );
    }

    /**
     * Muestra el botón de descarga en la página de configuración
     */
    public static function render_download_button() {
        if ( ! self::is_development_environment() ) {
            return;
        }

        $download_url = wp_nonce_url(
            admin_url( 'admin.php?page=certificados-digitales-config&action=download_package' ),
            'download_plugin_package'
        );

        ?>
        <div class="certificados-config-card dev-package-card" style="border-left: 4px solid #ff9800; background: #fff3e0;">
            <h2>
                <span class="dashicons dashicons-download" style="color: #ff9800;"></span>
                <?php _e( 'Descargar Plugin para Producción', 'certificados-digitales' ); ?>
                <span class="badge" style="background: #ff9800; color: white; font-size: 11px; padding: 3px 8px; border-radius: 3px; margin-left: 10px;">DESARROLLO</span>
            </h2>
            <p style="color: #666; margin-bottom: 15px;">
                <?php _e( 'Esta opción está disponible solo en entornos de desarrollo. Descarga una versión limpia del plugin sin archivos de desarrollo, documentación o dependencias innecesarias.', 'certificados-digitales' ); ?>
            </p>

            <div style="background: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <h4 style="margin-top: 0; color: #333;"><?php _e( 'Archivos excluidos:', 'certificados-digitales' ); ?></h4>
                <ul style="margin: 10px 0; padding-left: 20px; color: #666; font-size: 13px; line-height: 1.8;">
                    <li>Carpetas: <code>.claude</code>, <code>.git</code>, <code>node_modules</code>, <code>docs</code></li>
                    <li>Documentación: <code>README.md</code>, <code>ACTUALIZACION.md</code>, <code>NUEVAS_FUNCIONALIDADES.md</code></li>
                    <li>Archivos de desarrollo: <code>composer.json</code>, <code>package.json</code>, archivos de prueba</li>
                    <li>Utilidades: <code>diagnostico.php</code>, <code>limpiar-cache.php</code>, <code>reiniciar-plugin.php</code>, <code>test-clase.php</code></li>
                </ul>
            </div>

            <a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary button-large" style="background: #ff9800; border-color: #f57c00; text-shadow: none; box-shadow: none;">
                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php _e( 'Descargar Plugin Limpio', 'certificados-digitales' ); ?>
            </a>

            <p style="margin-top: 15px; font-size: 12px; color: #999;">
                <span class="dashicons dashicons-info" style="font-size: 14px; vertical-align: middle;"></span>
                <?php _e( 'El archivo descargado estará listo para instalar en un sitio de producción.', 'certificados-digitales' ); ?>
            </p>
        </div>
        <?php
    }
}
