/**
 * JavaScript para el área pública.
 *
 * @since      1.0.0
 */

(function($) {
    'use strict';

    /**
     * Inicializa los formularios de certificados cuando el DOM está listo.
     */
    $(document).ready(function() {
        // Debug para verificar que las variables están disponibles
        console.log('Certificados PDF Public JS loaded', {
            ajax_url: typeof certificados_pdf_vars !== 'undefined' ? certificados_pdf_vars.ajax_url : 'undefined',
            nonce: typeof certificados_pdf_vars !== 'undefined' ? certificados_pdf_vars.nonce : 'undefined'
        });
        
        // Validación adicional de cédula y eventos de formulario
        $('.certificado-pdf-form').each(function() {
            let $form = $(this);
            let $campoDocumento = $form.find('input[name="valor_busqueda"]');
            let $campoHidden = $form.find('input[name="nonce"]');
            
            // Depuración de valores importantes
            console.log('Form initialized', {
                form_id: $form.attr('id'),
                has_nonce: $campoHidden.length > 0,
                nonce_value: $campoHidden.val(),
                field_name: $form.find('input[name="campo_busqueda"]').val()
            });
            
            // Solo aplica esta validación si el campo de búsqueda es "documento" o "cedula"
            if ($form.find('input[name="campo_busqueda"]').val().toLowerCase().includes('documento') || 
                $form.find('input[name="campo_busqueda"]').val().toLowerCase().includes('cedula')) {
                
                $campoDocumento.on('keypress', function(e) {
                    // Permitir solo números
                    if (e.which < 48 || e.which > 57) {
                        e.preventDefault();
                    }
                });
                
                $campoDocumento.on('paste', function(e) {
                    // Limpiar pegado para permitir solo números
                    let pasteData = e.originalEvent.clipboardData.getData('text');
                    if (!/^\d+$/.test(pasteData)) {
                        e.preventDefault();
                    }
                });
            }
            
            // Asegurar que el formulario tiene un manejador de envío adecuado
            $form.on('submit', function(e) {
                console.log('Form submitted', {
                    certificado_id: $form.find('input[name="certificado_id"]').val(),
                    campo_busqueda: $form.find('input[name="campo_busqueda"]').val(),
                    valor_busqueda: $form.find('input[name="valor_busqueda"]').val(),
                    nonce: $form.find('input[name="nonce"]').val(),
                    action: $form.find('input[name="action"]').val()
                });
                
                // La lógica de submit está en el archivo PHP del shortcode
            });
        });
    });

})(jQuery);