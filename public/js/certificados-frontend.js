/**
 * JavaScript Frontend - Certificados Digitales
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ========================================
        // VALIDACIÓN DE CERTIFICADO (desde URL)
        // ========================================
        function verificarValidacionURL() {
            var urlParams = new URLSearchParams(window.location.search);
            var validar = urlParams.get('validar');
            var doc = urlParams.get('doc');
            var pestanaId = urlParams.get('pestana');

            if (validar === '1' && doc && pestanaId) {
                // Abrir modal de validación
                validarCertificado(pestanaId, doc);
            }
        }

        function validarCertificado(pestanaId, numeroDocumento) {
            var $modal = $('#certificados-modal-validacion');
            var $loading = $modal.find('.validacion-loading');
            var $resultado = $modal.find('.validacion-resultado');

            // Mostrar modal y loading
            $modal.fadeIn(300);
            $loading.show();
            $resultado.hide();

            // Hacer petición AJAX
            $.ajax({
                url: certificadosFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_validar_certificado',
                    nonce: certificadosFrontend.nonce,
                    pestana_id: pestanaId,
                    numero_documento: numeroDocumento
                },
                success: function(response) {
                    $loading.hide();

                    if (response.success && response.data) {
                        if (response.data.valido) {
                            // Certificado válido
                            var html = '<div class="validacion-exito">';
                            html += '<div class="validacion-icono">✅</div>';
                            html += '<h4>' + response.data.mensaje + '</h4>';
                            html += '<div class="validacion-datos">';
                            html += '<p><strong>Nombre:</strong> ' + response.data.datos.nombre + '</p>';
                            html += '<p><strong>Documento:</strong> ' + response.data.datos.documento + '</p>';
                            html += '<p><strong>Evento:</strong> ' + response.data.datos.evento + '</p>';
                            html += '<p><strong>Tipo:</strong> ' + response.data.datos.tipo + '</p>';
                            html += '</div>';
                            html += '</div>';
                            $resultado.html(html);
                        } else {
                            // Certificado no válido
                            var html = '<div class="validacion-error">';
                            html += '<div class="validacion-icono">❌</div>';
                            html += '<h4>' + response.data.mensaje + '</h4>';
                            html += '<p>' + response.data.descripcion + '</p>';
                            html += '</div>';
                            $resultado.html(html);
                        }
                    } else {
                        // Error
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Error al validar el certificado.';
                        $resultado.html('<div class="validacion-error"><p>' + errorMsg + '</p></div>');
                    }

                    $resultado.fadeIn(300);
                },
                error: function() {
                    $loading.hide();
                    $resultado.html('<div class="validacion-error"><p>Error de conexión. Por favor, intente nuevamente.</p></div>');
                    $resultado.fadeIn(300);
                }
            });
        }

        // Verificar si hay parámetros de validación en la URL
        verificarValidacionURL();

        // ========================================
        // NAVEGACIÓN DE PESTAÑAS
        // ========================================
        $('.certificados-tab').on('click', function() {
            var targetTab = $(this).data('tab');
            
            // Cambiar pestaña activa
            $('.certificados-tab').removeClass('active');
            $(this).addClass('active');
            
            // Cambiar panel activo
            $('.certificados-tab-panel').removeClass('active');
            $('#' + targetTab).addClass('active');
            
            // Limpiar resultados previos
            $('.certificados-result').hide();
            $('.result-success, .result-error').hide();
        });

        // ========================================
        // VALIDACIÓN Y SANITIZACIÓN DE INPUT
        // ========================================
        $('.certificados-input').on('input', function() {
            var $input = $(this);
            var value = $input.val();

            // Eliminar caracteres peligrosos y limitar a 20 caracteres
            value = value.replace(/[<>'"&;`]/g, '');
            value = value.substring(0, 20);

            $input.val(value);
        });

        // ========================================
        // ENVÍO DEL FORMULARIO DE BÚSQUEDA
        // ========================================
        $('.certificados-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $panel = $form.closest('.certificados-tab-panel');
            var $button = $form.find('.certificados-btn-buscar');
            var $result = $panel.find('.certificados-result');
            var $success = $result.find('.result-success');
            var $error = $result.find('.result-error');

            var pestanaId = $panel.data('pestana-id');
            var numeroDocumento = $form.find('input[name="numero_documento"]').val().trim();

            // Validación básica
            if (!numeroDocumento) {
                alert(certificadosFrontend.i18n.documentoRequerido);
                return;
            }

            // Validación de longitud
            if (numeroDocumento.length > 20) {
                alert('El número de documento no puede exceder 20 caracteres.');
                return;
            }

            // Validación de caracteres permitidos (solo letras, números, guiones y espacios)
            if (!/^[a-zA-Z0-9\-\s]+$/.test(numeroDocumento)) {
                alert('El número de documento contiene caracteres no permitidos.');
                return;
            }
            
            // Deshabilitar botón
            $button.prop('disabled', true);
            $button.find('.btn-text').hide();
            $button.find('.btn-loading').show();
            
            // Ocultar resultados anteriores
            $result.hide();
            $success.hide();
            $error.hide();
            
            // Hacer petición AJAX
            $.ajax({
                url: certificadosFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_generar_certificado',
                    nonce: certificadosFrontend.nonce,
                    pestana_id: pestanaId,
                    numero_documento: numeroDocumento
                },
                success: function(response) {
                    if (response.success) {
                        var downloadUrl = response.data.download_url;
                        var $container = $('.certificados-container');
                        var $loader = $container.find('.certificados-loader');
                        var logoUrl = $container.data('logo-url');
                        
                        // Mostrar loader con el logo
                        $loader.find('.certificados-loader-img').attr('src', logoUrl);
                        $loader.fadeIn(300);
                        
                        // Después de 2 segundos, ejecutar descarga y encuesta
                        setTimeout(function() {
                            // Descargar automáticamente el PDF
                            var link = document.createElement('a');
                            link.href = downloadUrl;
                            link.download = downloadUrl.split('/').pop();
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            // Registrar descarga
                            registrarDescarga(pestanaId, numeroDocumento);

                            // Ocultar loader
                            $loader.fadeOut(300);

                            // Verificar si hay encuesta opcional
                            if (response.data && response.data.survey_data && response.data.survey_data.mode === 'optional') {
                                var surveyUrl = response.data.survey_data.url;
                                if (surveyUrl) {
                                    setTimeout(function() {
                                        window.open(surveyUrl, '_blank');
                                    }, 500);
                                }
                            }
                            
                            // Mostrar mensaje de éxito
                            $success.find('.result-message').html(
                                '✅ <strong>¡Tu certificado se ha descargado correctamente!</strong>' +
                                '<small style="display: block; margin-top: 10px; opacity: 0.85;">Revisa tu carpeta de descargas.</small>'
                            );
                            
                            // Ocultar el botón de descarga
                            $success.find('.certificados-btn-download').remove();
                            
                            // Limpiar el input
                            $form.find('input[name="numero_documento"]').val('');
                            
                            $success.show();
                            $result.show();
                            
                            // Desplazar al resultado
                            setTimeout(function() {
                                $result.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }, 300);
                        }, 2000);
                        
                        // Rehabilitar botón
                        $button.prop('disabled', false);
                        $button.find('.btn-text').show();
                        $button.find('.btn-loading').hide();
                    } else {
                        // Verificar si es un error de encuesta obligatoria
                        if (response.data && response.data.survey_required) {
                            var surveyMessage = response.data.message || 'Debes completar la encuesta de satisfacción antes de descargar tu certificado.';
                            var surveyUrl = response.data.survey_url || '';
                            var surveyTitle = response.data.survey_title || 'Encuesta de Satisfacción';
                            var surveyMode = response.data.survey_mode || 'mandatory';

                            var displayMessage = '<div style="text-align: center;">';
                            displayMessage += '<div style="font-size: 48px; margin-bottom: 15px;">📋</div>';
                            displayMessage += '<strong style="font-size: 16px; color: #856404;">' + surveyMessage + '</strong>';

                            if (surveyUrl) {
                                displayMessage += '<div style="margin-top: 20px;">';

                                // IMPORTANTE: Encuesta obligatoria = misma pestaña, opcional = nueva pestaña
                                var target = (surveyMode === 'mandatory') ? '_self' : '_blank';

                                displayMessage += '<a href="' + surveyUrl + '" target="' + target + '" class="certificados-btn-primary" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; font-weight: 600;">';
                                displayMessage += '🔗 ' + surveyTitle;
                                displayMessage += '</a>';
                                displayMessage += '</div>';

                                if (surveyMode === 'mandatory') {
                                    displayMessage += '<p style="margin-top: 15px; font-size: 13px; color: #666;">Serás redirigido a la encuesta. Una vez completada, regresa aquí para descargar tu certificado.</p>';
                                } else {
                                    displayMessage += '<p style="margin-top: 15px; font-size: 13px; color: #666;">Una vez completada la encuesta, vuelve e intenta descargar nuevamente tu certificado.</p>';
                                }
                            }

                            displayMessage += '</div>';

                            $error.find('.error-message').html(displayMessage);
                            $error.css('background', '#fff3cd');
                            $error.css('border-color', '#ffc107');
                        } else {
                            // Mostrar error normal
                            var errorMessage = response.data && response.data.message ? response.data.message : certificadosFrontend.i18n.error;
                            var displayMessage = '<strong>⚠️ No se pudo procesar tu solicitud</strong><br>' + errorMessage;
                            $error.find('.error-message').html(displayMessage);
                            $error.css('background', '');
                            $error.css('border-color', '');
                        }

                        $error.show();
                        $result.show();

                        // Desplazar al resultado
                        setTimeout(function() {
                            $result.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);

                        // Rehabilitar botón
                        $button.prop('disabled', false);
                        $button.find('.btn-text').show();
                        $button.find('.btn-loading').hide();
                    }
                },
                error: function() {
                    // Error de conexión
                    var displayMessage = '<strong>⚠️ Error de conexión</strong>' + certificadosFrontend.i18n.error;
                    $error.find('.error-message').html(displayMessage);
                    $error.show();
                    $result.show();
                    
                    // Desplazar al resultado
                    setTimeout(function() {
                        $result.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 300);
                    
                    // Rehabilitar botón
                    $button.prop('disabled', false);
                    $button.find('.btn-text').show();
                    $button.find('.btn-loading').hide();
                }
            });
        });



        // ========================================
        // CERRAR MODAL DE ENCUESTA
        // ========================================
        $(document).on('click', '.modal-close', function() {
            $('#certificados-modal-encuesta').fadeOut(300);
        });

        // Cerrar modal al hacer clic fuera
        $(document).on('click', '.certificados-modal', function(e) {
            if ($(e.target).hasClass('certificados-modal')) {
                $(this).fadeOut(300);
            }
        });

        // Cerrar modal con tecla ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('#certificados-modal-encuesta').fadeOut(300);
            }
        });

        // ========================================
        // REGISTRAR DESCARGA EN EL SERVIDOR
        // ========================================
        function registrarDescarga(pestanaId, numeroDocumento) {
            $.ajax({
                url: certificadosFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_registrar_descarga',
                    nonce: certificadosFrontend.nonce,
                    pestana_id: pestanaId,
                    numero_documento: numeroDocumento
                }
            });
        }

        // ========================================
        // PERMITIR ENTER EN EL INPUT
        // ========================================
        $('.certificados-input').on('keypress', function(e) {
            if (e.which === 13) { // Enter
                $(this).closest('form').submit();
            }
        });

        // ========================================
        // LIMPIAR INPUT AL CAMBIAR DE PESTAÑA
        // ========================================
        $('.certificados-tab').on('click', function() {
            $('.certificados-input').val('');
        });

    });

})(jQuery);