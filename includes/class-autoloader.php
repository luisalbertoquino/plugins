<?php
/**
 * Autoloader PSR-4 para el plugin.
 *
 * @package    Certificados_Digitales
 * @subpackage Certificados_Digitales/includes
 */

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Clase Autoloader.
 *
 * Carga automáticamente las clases del plugin siguiendo el estándar PSR-4.
 */
class Certificados_Digitales_Autoloader {

    /**
     * Registra el autoloader.
     */
    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Función de autoload.
     *
     * @param string $class_name Nombre de la clase a cargar.
     */
    private static function autoload( $class_name ) {

        // Solo cargar clases que empiecen con nuestro prefijo
        $prefix_full = 'Certificados_Digitales_';
        $prefix_short = 'Certificados_';

        // Determinar qué prefijo usar
        if ( strpos( $class_name, $prefix_full ) === 0 ) {
            $class_name = str_replace( $prefix_full, '', $class_name );
        } elseif ( strpos( $class_name, $prefix_short ) === 0 ) {
            $class_name = str_replace( $prefix_short, '', $class_name );
        } else {
            return;
        }

        // Convertir nombre de clase a nombre de archivo
        // Ejemplo: Admin -> class-admin.php
        $file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

        // Determinar la carpeta según el tipo de clase
        $paths = array(
            CERTIFICADOS_DIGITALES_PATH . 'includes/',
            CERTIFICADOS_DIGITALES_PATH . 'admin/',
            CERTIFICADOS_DIGITALES_PATH . 'public/',
        );

        // Buscar el archivo en las carpetas
        foreach ( $paths as $path ) {
            $file = $path . $file_name;
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }
    }
}