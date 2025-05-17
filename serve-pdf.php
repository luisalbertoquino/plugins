<?php
// Verificar que se está ejecutando dentro de WordPress
if (!defined('ABSPATH')) {
    if (file_exists('../../../wp-load.php')) {
        require_once('../../../wp-load.php');
    } else {
        die('No se puede acceder directamente a este archivo.');
    }
}

// Verificar parámetros requeridos
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('Parámetro de archivo no especificado.');
}

// Validar el nombre del archivo (solo permitir caracteres seguros)
$filename = sanitize_file_name($_GET['file']);
if ($filename !== $_GET['file']) {
    die('Nombre de archivo no válido.');
}

// Construir la ruta al archivo
$upload_dir = wp_upload_dir();
$filepath = $upload_dir['basedir'] . '/certificados/' . $filename;

// Verificar que el archivo existe y está dentro del directorio permitido
if (!file_exists($filepath) || !is_file($filepath) || strpos(realpath($filepath), realpath($upload_dir['basedir'] . '/certificados/')) !== 0) {
    die('El archivo solicitado no existe o no es accesible.');
}

// Verificar que es un archivo PDF
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);

if ($mime_type !== 'application/pdf') {
    die('El archivo no es un PDF válido.');
}

// Servir el archivo
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

readfile($filepath);
exit;