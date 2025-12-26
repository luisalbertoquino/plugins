<?php
/**
 * Script temporal para limpiar caché de OPcache
 *
 * INSTRUCCIONES:
 * 1. Accede a este archivo desde el navegador: http://tudominio.com/wp-content/plugins/certificate-pro/limpiar-cache.php
 * 2. Después de ejecutarlo, ELIMINA este archivo por seguridad
 */

// Limpiar OPcache si está habilitado
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ Caché de OPcache limpiado correctamente.<br>";
} else {
    echo "⚠️ OPcache no está habilitado en este servidor.<br>";
}

// Mostrar información de OPcache
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo "<br><strong>Estado de OPcache:</strong><br>";
    echo "- Habilitado: " . ($status['opcache_enabled'] ? 'Sí' : 'No') . "<br>";
    echo "- Caché lleno: " . ($status['cache_full'] ? 'Sí' : 'No') . "<br>";
    echo "- Memoria usada: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB<br>";
} else {
    echo "<br>No se puede obtener información de OPcache.<br>";
}

// Verificar versión de PHP
echo "<br><strong>Versión de PHP:</strong> " . phpversion() . "<br>";
echo "<strong>Versión recomendada:</strong> 7.4 o superior<br>";

// Verificar si la clase existe
echo "<br><strong>Verificando clase Certificados_PDF_Generator:</strong><br>";
if (class_exists('Certificados_PDF_Generator')) {
    echo "✅ La clase está cargada correctamente.<br>";

    // Verificar si el método existe
    if (method_exists('Certificados_PDF_Generator', 'buscar_participante')) {
        echo "✅ El método buscar_participante() existe.<br>";
    } else {
        echo "❌ El método buscar_participante() NO existe.<br>";
    }
} else {
    echo "❌ La clase NO está cargada.<br>";
}

echo "<br><hr>";
echo "<strong>IMPORTANTE:</strong> Por favor, ELIMINA este archivo después de usarlo por razones de seguridad.";
