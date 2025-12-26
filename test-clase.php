<?php
/**
 * Script para probar la carga de la clase Certificados_PDF_Generator
 * ELIMINA este archivo después de usarlo
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Carga de Clase Certificados_PDF_Generator</h2>";

// Definir constantes necesarias
if (!defined('WPINC')) {
    define('WPINC', true);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}

if (!defined('CERTIFICADOS_DIGITALES_PATH')) {
    define('CERTIFICADOS_DIGITALES_PATH', __DIR__ . '/');
}

echo "<h3>1. Constantes definidas</h3>";
echo "CERTIFICADOS_DIGITALES_PATH: " . CERTIFICADOS_DIGITALES_PATH . "<br><br>";

// Intentar cargar las dependencias en orden
echo "<h3>2. Cargando dependencias</h3>";

$archivos_a_cargar = [
    'includes/class-sheets-cache-manager.php',
    'includes/class-google-sheets.php',
    'includes/class-column-mapper.php',
    'includes/class-pdf-generator.php'
];

foreach ($archivos_a_cargar as $archivo) {
    $ruta_completa = CERTIFICADOS_DIGITALES_PATH . $archivo;
    echo "Cargando: $archivo<br>";

    if (!file_exists($ruta_completa)) {
        echo "❌ ERROR: Archivo no existe: $ruta_completa<br>";
        continue;
    }

    try {
        require_once $ruta_completa;
        echo "✅ Cargado exitosamente<br>";
    } catch (Error $e) {
        echo "❌ ERROR FATAL: " . $e->getMessage() . "<br>";
        echo "Archivo: " . $e->getFile() . "<br>";
        echo "Línea: " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        die();
    } catch (Exception $e) {
        echo "❌ EXCEPCIÓN: " . $e->getMessage() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        die();
    }
}

echo "<br><h3>3. Verificando clases cargadas</h3>";

$clases_requeridas = [
    'Certificados_Sheets_Cache_Manager',
    'Certificados_Google_Sheets',
    'Certificados_Column_Mapper',
    'Certificados_PDF_Generator'
];

foreach ($clases_requeridas as $clase) {
    if (class_exists($clase)) {
        echo "✅ Clase '$clase' existe<br>";
    } else {
        echo "❌ Clase '$clase' NO existe<br>";
    }
}

echo "<br><h3>4. Verificando método buscar_participante()</h3>";

if (class_exists('Certificados_PDF_Generator')) {
    if (method_exists('Certificados_PDF_Generator', 'buscar_participante')) {
        echo "✅✅✅ <strong style='color: green; font-size: 18px;'>ÉXITO: El método buscar_participante() existe</strong><br><br>";

        // Mostrar todos los métodos
        $metodos = get_class_methods('Certificados_PDF_Generator');
        echo "<strong>Métodos públicos disponibles:</strong><br>";
        echo "<ul>";
        foreach ($metodos as $metodo) {
            echo "<li>" . $metodo;
            if ($metodo === 'buscar_participante') {
                echo " <strong style='color: green;'>← ESTE ES EL QUE BUSCAMOS</strong>";
            }
            echo "</li>";
        }
        echo "</ul>";

    } else {
        echo "❌ El método buscar_participante() NO existe<br>";
        echo "<strong>Métodos disponibles:</strong><br><pre>";
        print_r(get_class_methods('Certificados_PDF_Generator'));
        echo "</pre>";
    }
} else {
    echo "❌ La clase Certificados_PDF_Generator NO existe<br>";
}

echo "<br><h3>5. Verificando archivo class-pdf-generator.php directamente</h3>";
$pdf_file = CERTIFICADOS_DIGITALES_PATH . 'includes/class-pdf-generator.php';
$contenido = file_get_contents($pdf_file);

if (strpos($contenido, 'public function buscar_participante') !== false) {
    echo "✅ El código fuente SÍ contiene 'public function buscar_participante'<br>";
} else {
    echo "❌ El código fuente NO contiene 'public function buscar_participante'<br>";
}

// Buscar la línea exacta
$lineas = file($pdf_file);
foreach ($lineas as $num => $linea) {
    if (strpos($linea, 'function buscar_participante') !== false) {
        echo "✅ Encontrado en línea " . ($num + 1) . ": " . htmlspecialchars(trim($linea)) . "<br>";
    }
}

echo "<br><hr>";
echo "<p><strong style='color: red;'>IMPORTANTE: ELIMINA este archivo después de usarlo</strong></p>";
