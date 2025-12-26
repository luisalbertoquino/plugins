<?php
/**
 * Script de diagnóstico del plugin Certificados Digitales PRO
 *
 * IMPORTANTE: ELIMINA este archivo después de usarlo
 */

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico del Plugin Certificados Digitales PRO</h2>";

// 1. Información de PHP
echo "<h3>1. Información de PHP</h3>";
echo "Versión de PHP: " . phpversion() . "<br>";
echo "Requerido: 7.4 o superior<br>";

if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo "✅ Versión compatible<br>";
} else {
    echo "❌ Versión NO compatible<br>";
}

// 2. OPcache
echo "<h3>2. Estado de OPcache</h3>";
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status(false);
    echo "OPcache habilitado: " . ($opcache_status['opcache_enabled'] ? 'Sí' : 'No') . "<br>";

    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✅ Caché de OPcache limpiado<br>";
    }
} else {
    echo "OPcache no disponible<br>";
}

// 3. Verificar constantes del plugin
echo "<h3>3. Constantes del Plugin</h3>";
define('WPINC', true); // Simular WordPress
define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');

$plugin_path = __DIR__ . '/';
define('CERTIFICADOS_DIGITALES_PATH', $plugin_path);

echo "CERTIFICADOS_DIGITALES_PATH: " . CERTIFICADOS_DIGITALES_PATH . "<br>";
echo "¿Existe? " . (file_exists(CERTIFICADOS_DIGITALES_PATH) ? 'Sí' : 'No') . "<br>";

// 4. Verificar archivo class-pdf-generator.php
echo "<h3>4. Archivo class-pdf-generator.php</h3>";
$pdf_generator_file = CERTIFICADOS_DIGITALES_PATH . 'includes/class-pdf-generator.php';
echo "Ruta: " . $pdf_generator_file . "<br>";
echo "¿Existe? " . (file_exists($pdf_generator_file) ? 'Sí ✅' : 'No ❌') . "<br>";

if (file_exists($pdf_generator_file)) {
    echo "Tamaño: " . filesize($pdf_generator_file) . " bytes<br>";
    echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($pdf_generator_file)) . "<br>";

    // Verificar errores de sintaxis
    $output = shell_exec("php -l \"$pdf_generator_file\" 2>&1");
    echo "Sintaxis PHP: <pre>" . htmlspecialchars($output) . "</pre>";
}

// 5. Cargar el archivo y verificar la clase
echo "<h3>5. Cargar y Verificar Clase</h3>";
if (file_exists($pdf_generator_file)) {
    // Leer el contenido del archivo
    $file_content = file_get_contents($pdf_generator_file);

    // Verificar que contiene la declaración de la clase
    if (strpos($file_content, 'class Certificados_PDF_Generator') !== false) {
        echo "✅ Declaración de clase encontrada en el archivo<br>";
    } else {
        echo "❌ Declaración de clase NO encontrada<br>";
    }

    // Verificar que contiene el método buscar_participante
    if (strpos($file_content, 'function buscar_participante') !== false ||
        strpos($file_content, 'public function buscar_participante') !== false) {
        echo "✅ Método buscar_participante() encontrado en el código<br>";
    } else {
        echo "❌ Método buscar_participante() NO encontrado<br>";
    }

    // Intentar cargar la clase
    try {
        require_once $pdf_generator_file;
        echo "✅ Archivo cargado sin errores<br>";

        if (class_exists('Certificados_PDF_Generator')) {
            echo "✅ Clase Certificados_PDF_Generator existe<br>";

            // Verificar métodos de la clase
            $methods = get_class_methods('Certificados_PDF_Generator');
            echo "<br><strong>Métodos disponibles:</strong><br>";
            echo "<pre>";
            print_r($methods);
            echo "</pre>";

            if (in_array('buscar_participante', $methods)) {
                echo "✅ Método buscar_participante() está disponible<br>";
            } else {
                echo "❌ Método buscar_participante() NO está disponible<br>";
            }
        } else {
            echo "❌ Clase NO existe después de cargar el archivo<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error al cargar: " . $e->getMessage() . "<br>";
    }
}

// 6. Verificar autoloader
echo "<h3>6. Autoloader</h3>";
$autoloader_file = CERTIFICADOS_DIGITALES_PATH . 'includes/class-autoloader.php';
echo "Archivo autoloader: " . ($file_exists($autoloader_file) ? 'Existe ✅' : 'No existe ❌') . "<br>";

echo "<br><hr>";
echo "<p><strong style='color: red;'>IMPORTANTE: Por favor, ELIMINA este archivo después de usarlo.</strong></p>";
