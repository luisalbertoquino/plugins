<?php
/**
 * Script para reiniciar el plugin y limpiar caché
 *
 * Este script hace lo siguiente:
 * 1. Limpia OPcache si está disponible
 * 2. Invalida el archivo específico de class-pdf-generator.php
 * 3. Verifica que el método existe
 *
 * IMPORTANTE: ELIMINA este archivo después de usarlo
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Reiniciando Plugin y Limpiando Caché</h2>";

// Ruta del archivo problemático
$pdf_generator_file = __DIR__ . '/includes/class-pdf-generator.php';

echo "<h3>Paso 1: Limpiar OPcache</h3>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ OPcache completamente reseteado<br>";
    } else {
        echo "⚠️ No se pudo resetear OPcache<br>";
    }
} else {
    echo "⚠️ OPcache no está disponible<br>";
}

echo "<h3>Paso 2: Invalidar archivo específico</h3>";
if (function_exists('opcache_invalidate')) {
    if (file_exists($pdf_generator_file)) {
        opcache_invalidate($pdf_generator_file, true);
        echo "✅ Archivo class-pdf-generator.php invalidado en caché<br>";
        echo "Ruta: " . $pdf_generator_file . "<br>";
    } else {
        echo "❌ Archivo no encontrado: " . $pdf_generator_file . "<br>";
    }
} else {
    echo "⚠️ Función opcache_invalidate no disponible<br>";
}

echo "<h3>Paso 3: Forzar recarga del archivo</h3>";
if (file_exists($pdf_generator_file)) {
    // Cambiar el timestamp del archivo para forzar recarga
    touch($pdf_generator_file);
    echo "✅ Timestamp del archivo actualizado<br>";

    // Esperar un momento
    sleep(1);

    // Cargar el archivo nuevamente
    require_once $pdf_generator_file;

    if (class_exists('Certificados_PDF_Generator')) {
        echo "✅ Clase Certificados_PDF_Generator cargada correctamente<br>";

        if (method_exists('Certificados_PDF_Generator', 'buscar_participante')) {
            echo "✅✅✅ <strong style='color: green;'>ÉXITO: El método buscar_participante() ahora existe</strong><br>";
        } else {
            echo "❌ El método buscar_participante() AÚN NO existe<br>";
            echo "<br>Métodos disponibles:<br><pre>";
            print_r(get_class_methods('Certificados_PDF_Generator'));
            echo "</pre>";
        }
    } else {
        echo "❌ La clase NO se pudo cargar<br>";
    }
} else {
    echo "❌ Archivo no existe<br>";
}

echo "<h3>Paso 4: Información del sistema</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<br><hr>";
echo "<h3>¿Qué hacer ahora?</h3>";
echo "<ol>";
echo "<li>Si ves '✅✅✅ ÉXITO' arriba, intenta generar un certificado nuevamente desde WordPress</li>";
echo "<li>Si aún no funciona, necesitas <strong>reiniciar el servidor web o PHP-FPM</strong></li>";
echo "<li><strong style='color: red;'>ELIMINA este archivo por seguridad</strong></li>";
echo "</ol>";

echo "<h3 style='color: red;'>SI ESTO NO FUNCIONA:</h3>";
echo "<p>Necesitas acceso SSH al servidor para ejecutar:</p>";
echo "<pre>sudo systemctl restart php-fpm</pre>";
echo "<p>o contactar a tu proveedor de hosting para que reinicie el servicio PHP.</p>";
