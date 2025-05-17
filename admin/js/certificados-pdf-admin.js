/**
 * JavaScript para el área de administración, incluyendo la funcionalidad para 
 * seleccionar plantillas de certificados.
 *plugins-main/admin/js/certificados-pdf-admin.js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    /**
     * Función para inicializar la tabla de certificados.
     */
    function initCertificadosTable() {
        // Copiar shortcode al portapapeles
        $('.copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            
            var $this = $(this);
            $this.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            setTimeout(function() {
                $this.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            }, 1000);
        });
        
        // Eliminar certificado
        $('.delete-cert').on('click', function(e) {
            e.preventDefault();
            
            var id = $(this).data('id');
            
            if (confirm(certificados_pdf_vars.i18n.confirm_delete)) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'eliminar_certificado',
                        id: id,
                        nonce: certificados_pdf_vars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            }
        });
    }

    /**
     * Función para inicializar la página de configuración.
     */
    function initConfiguracion() {
        // Aquí puedes agregar funcionalidades adicionales para la página de configuración
        $('#save-settings').on('click', function() {
            var $form = $(this).closest('form');
            var $submitBtn = $(this);
            var originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text(certificados_pdf_vars.i18n.saving || 'Guardando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        alert(response.data.message || certificados_pdf_vars.i18n.success_saving);
                    } else {
                        alert(response.data.message || certificados_pdf_vars.i18n.error_saving);
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                    alert(certificados_pdf_vars.i18n.error_saving);
                }
            });
        });
    }

    /**
     * Función para inicializar el selector de plantilla
     */
    function initPlantillaSelector() {
        // Comprobar si estamos en la página de edición de certificados
        if ($('#upload-btn').length && typeof wp !== 'undefined' && wp.media) {
            console.log('Inicializando selector de plantilla...');
            
            // Variable para almacenar el media uploader
            var mediaUploader;
            
            // Manejar el click en el botón de subir plantilla
            $('#upload-btn').on('click', function(e) {
                e.preventDefault();
                
                // Si el uploader ya existe, ábrelo
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                // Crear un nuevo media uploader
                mediaUploader = wp.media({
                    title: 'Seleccionar Plantilla de Certificado',
                    button: {
                        text: 'Usar esta imagen'
                    },
                    multiple: false,
                    library: {
                        type: ['image'] // Puedes usar 'image/png', 'image/jpeg', 'application/pdf', etc.
                    }
                });
                
                // Cuando se selecciona un archivo
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    console.log('Imagen seleccionada:', attachment);
                    
                    // Actualizar la URL en el campo oculto
                    $('#plantilla_url').val(attachment.url);
                    
                    // Actualizar la vista previa
                    $('#plantilla-preview').html('<img src="' + attachment.url + '" alt="Plantilla">');
                    
                    // Si también está en el canvas, actualizar ahí también
                    if ($('.certificate-bg').length) {
                        $('.certificate-bg').attr('src', attachment.url);
                    } else {
                        // Si no existe, la creamos
                        $('#certificado-canvas').find('.no-image-large').remove();
                        $('#certificado-canvas').append('<img src="' + attachment.url + '" alt="Plantilla" class="certificate-bg">');
                        
                        // Recalibrar coordenadas después de cargar la imagen
                        if (typeof calibrarCoordenadas === 'function') {
                            setTimeout(function() {
                                calibrarCoordenadas();
                            }, 500);
                        }
                    }
                });
                
                // Abrir el selector de medios
                mediaUploader.open();
            });
        }
    }

    /**
     * Función para probar la conexión con Google Sheets
     */
    function initProbarConexion() {
        $('#probar-conexion').on('click', function() {
            var sheetId = $('#sheet_id').val();
            var sheetNombre = $('#sheet_nombre').val();
            var $resultado = $('#conexion-resultado');
            
            if (!sheetId) {
                $resultado.html('<span class="error">Por favor, ingrese un ID de hoja.</span>');
                return;
            }
            
            $resultado.html('<span class="loading">Probando conexión...</span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'probar_conexion_sheet',
                    sheet_id: sheetId,
                    sheet_nombre: sheetNombre,
                    nonce: certificados_pdf_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $resultado.html('<span class="success">¡Conexión exitosa! Columnas encontradas: ' + response.data.columnas.join(', ') + '</span>');
                    } else {
                        $resultado.html('<span class="error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $resultado.html('<span class="error">Error al probar la conexión. Intente nuevamente.</span>');
                }
            });
        });
    }

    /**
     * Inicializa todos los componentes cuando el DOM está listo.
     */
    $(document).ready(function() {
        // Detectar la página actual
        var currentPage = window.location.search.match(/page=([^&]*)/);
        
        // IMPORTANTE: Siempre intentar inicializar el selector de plantilla
        // independientemente de la página
        initPlantillaSelector();
        
        if (currentPage && currentPage[1]) {
            switch (currentPage[1]) {
                case 'certificados_pdf':
                    initCertificadosTable();
                    break;
                case 'certificados_pdf_settings':
                    initConfiguracion();
                    break;
                case 'certificados_pdf_nuevo':
                case 'certificados_pdf_editar':
                    initProbarConexion();
                    break;
            }
        }
    });

})(jQuery);