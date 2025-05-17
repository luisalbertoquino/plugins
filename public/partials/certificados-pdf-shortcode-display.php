<?php
/**
 * Vista del shortcode en el frontend.
 *
 * @since      1.0.0
 */
?>

<div class="certificado-pdf-container <?php echo esc_attr($atts['class']); ?>">
    <div class="certificado-pdf-header">
        <h3><?php echo esc_html($atts['titulo']); ?></h3>
    </div>
    
    <div class="certificado-pdf-content">
        <form id="certificado-form-<?php echo $id; ?>" class="certificado-pdf-form" method="post">
            <input type="hidden" name="certificado_id" value="<?php echo $id; ?>">
            <input type="hidden" name="campo_busqueda" value="<?php echo esc_attr($campo_busqueda); ?>">
            <input type="hidden" name="action" value="generar_certificado">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('certificados_pdf_public_nonce'); ?>">
            
            <div class="certificado-pdf-field">
                <label for="valor_busqueda_<?php echo $id; ?>"><?php echo esc_html($campo_busqueda); ?>:</label>
                <input type="text" id="valor_busqueda_<?php echo $id; ?>" name="valor_busqueda" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required>
            </div>
            
            <div class="certificado-pdf-actions">
                <button type="submit" class="certificado-pdf-submit"><?php echo esc_html($atts['boton']); ?></button>
            </div>
        </form>
        
        <div id="certificado-resultado-<?php echo $id; ?>" class="certificado-pdf-resultado"></div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#certificado-form-<?php echo $id; ?>').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $resultado = $('#certificado-resultado-<?php echo $id; ?>');
            
            // Mostrar indicador de carga
            $resultado.html('<div class="certificado-loading">' + certificados_pdf_vars.i18n.loading + '</div>');
            
            // Obtener datos del formulario
            var formData = $form.serialize();
            
            // Debug para ver qué datos se están enviando
            console.log('Enviando datos:', formData);
            
            $.ajax({
                url: certificados_pdf_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Respuesta AJAX:', response);
                    
                    if (response.success) {
                        // Mostrar enlace de descarga
                        $resultado.html('<div class="certificado-success">' +
                            '<p>' + response.data.message + '</p>' +
                            '<a href="' + response.data.pdf_url + '" class="certificado-download" target="_blank">' +
                            '<?php _e('Descargar Certificado', 'certificados-pdf'); ?></a></div>'
                        );
                        
                        // Abrir automáticamente en una nueva pestaña
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        // Mostrar mensaje de error
                        $resultado.html('<div class="certificado-error">' + 
                            (response.data && response.data.message ? response.data.message : '<?php _e('Error al generar el certificado', 'certificados-pdf'); ?>') + 
                            '</div>');
                        console.error('Error en la respuesta:', response);
                    }
                },
                error: function(xhr, status, error) {
                    // Mostrar mensaje de error detallado
                    $resultado.html('<div class="certificado-error">' + 
                        certificados_pdf_vars.i18n.error_connection + 
                        '<br><small>Error: ' + error + ' - Status: ' + status + '</small></div>');
                    
                    console.error('Error AJAX:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                }
            });
        });
    });
</script>