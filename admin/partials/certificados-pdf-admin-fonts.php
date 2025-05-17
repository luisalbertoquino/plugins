<?php
/**
 * Vista para la gestión de fuentes.
 * plugins-main/admin/partials/certificados-pdf-admin-fonts.php
 * @since      1.0.0
 */

// Verificar que la clase gestora de fuentes esté cargada
if (!class_exists('Certificados_PDF_Fonts')) {
    require_once CERT_PDF_PLUGIN_DIR . 'includes/certificados-pdf-fonts.php';
}

// Crear instancia de la clase de fuentes
$fonts_manager = new Certificados_PDF_Fonts();
?>

<div class="wrap certificados-pdf-admin">
    <h1 class="wp-heading-inline"><?php _e('Gestión de Fuentes', 'certificados-pdf'); ?></h1>
    <hr class="wp-header-end">
    
    <div class="notice notice-info">
        <p>
            <?php _e('Gestiona las fuentes disponibles para tus certificados. Puedes utilizar las fuentes del sistema o subir tus propias fuentes personalizadas en formato TTF.', 'certificados-pdf'); ?>
        </p>
    </div>
    
    <?php
    // Renderizar el gestor de fuentes usando el método proporcionado por la clase
    $fonts_manager->render_fonts_manager();
    ?>
    
    <!-- Sección para mostrar más información sobre las fuentes -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header">
            <h2><?php _e('Cómo Usar las Fuentes', 'certificados-pdf'); ?></h2>
            <button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php _e('Alternar panel', 'certificados-pdf'); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button>
        </div>
        <div class="inside">
            <p><?php _e('Las fuentes TTF (TrueType Font) son utilizadas para personalizar el aspecto de los textos en tus certificados. Aquí puedes gestionar todas las fuentes disponibles para tu plugin.', 'certificados-pdf'); ?></p>
            
            <h3><?php _e('Cómo utilizar las fuentes en tus certificados:', 'certificados-pdf'); ?></h3>
            <ol>
                <li><?php _e('Sube las fuentes TTF que deseas utilizar desde esta página.', 'certificados-pdf'); ?></li>
                <li><?php _e('Al crear o editar un certificado, selecciona la tipografía deseada en el editor de campos.', 'certificados-pdf'); ?></li>
                <li><?php _e('La fuente se aplicará al texto del campo en el certificado generado.', 'certificados-pdf'); ?></li>
            </ol>
            
            <div class="notice notice-info inline">
                <p><strong><?php _e('Nota:', 'certificados-pdf'); ?></strong> <?php _e('Asegúrate de tener los derechos para utilizar las fuentes que subas.', 'certificados-pdf'); ?></p>
            </div>
        </div>
    </div>
</div>