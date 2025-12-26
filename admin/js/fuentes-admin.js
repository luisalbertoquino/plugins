/**
 * JavaScript para la gestión de fuentes
 * Certificados Digitales PRO
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ========================================
        // INICIALIZAR DATATABLES
        // ========================================
        if ($('#table-fuentes').length) {
            $('#table-fuentes').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [2, 4] } // Desactivar orden en vista previa y acciones
                ]
            });
        }

        // ========================================
        // MOSTRAR NOMBRE DEL ARCHIVO SELECCIONADO
        // ========================================
        $('#fuente_archivo').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $('#file-name').text(fileName).show();
            } else {
                $('#file-name').text('').hide();
            }
        });

        // ========================================
        // SUBIR FUENTE (AJAX)
        // ========================================
        $('#certificados-form-subir-fuente').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $('#btn-subir-fuente');
            var $spinner = $form.find('.spinner');
            var $message = $('#upload-message');
            var formData = new FormData(this);

            // Agregar action y nonce
            formData.append('action', 'certificados_subir_fuente');
            formData.append('nonce', certificadosFuentesAdmin.nonce);

            // Validar que se haya seleccionado un archivo
            var fileInput = $('#fuente_archivo')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                mostrarMensaje($message, 'error', 'Por favor selecciona un archivo.');
                return;
            }

            // Validar extensión
            var fileName = fileInput.files[0].name;
            var fileExt = fileName.split('.').pop().toLowerCase();
            if (fileExt !== 'ttf') {
                mostrarMensaje($message, 'error', 'Solo se permiten archivos .ttf');
                return;
            }

            // Validar tamaño (5MB)
            var fileSize = fileInput.files[0].size;
            var maxSize = 5 * 1024 * 1024; // 5MB
            if (fileSize > maxSize) {
                mostrarMensaje($message, 'error', 'El archivo no debe superar los 2MB.');
                return;
            }

            // Deshabilitar botón y mostrar spinner
            $button.prop('disabled', true).text(certificadosFuentesAdmin.i18n.uploading);
            $spinner.addClass('is-active');
            $message.removeClass('success error').hide();

            // Enviar AJAX
            $.ajax({
                url: certificadosFuentesAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        mostrarMensaje($message, 'success', response.data.message);
                        
                        // Resetear formulario
                        $form[0].reset();
                        $('#file-name').text('').hide();

                        // Recargar página después de 1.5 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        mostrarMensaje($message, 'error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    mostrarMensaje($message, 'error', 'Error en la conexión: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Subir Fuente');
                    $spinner.removeClass('is-active');
                }
            });
        });

        // ========================================
        // ELIMINAR FUENTE (AJAX)
        // ========================================
        $(document).on('click', '.btn-eliminar-fuente', function(e) {
            e.preventDefault();

            var $button = $(this);
            var fuenteId = $button.data('id');
            var fuenteNombre = $button.data('nombre');

            // Confirmar eliminación
            var confirmMessage = certificadosFuentesAdmin.i18n.confirmDelete.replace('%s', fuenteNombre);
            if (!confirm(confirmMessage)) {
                return;
            }

            // Deshabilitar botón
            $button.prop('disabled', true).text(certificadosFuentesAdmin.i18n.deleting);

            // Enviar AJAX
            $.ajax({
                url: certificadosFuentesAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_eliminar_fuente',
                    nonce: certificadosFuentesAdmin.nonce,
                    id: fuenteId
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        alert(response.data.message);
                        
                        // Recargar página
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false).text('🗑️ Eliminar');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error en la conexión: ' + error);
                    $button.prop('disabled', false).text('🗑️ Eliminar');
                }
            });
        });

        // ========================================
        // FUNCIÓN AUXILIAR: MOSTRAR MENSAJES
        // ========================================
        function mostrarMensaje($elemento, tipo, mensaje) {
            $elemento
                .removeClass('success error')
                .addClass(tipo)
                .html('<p>' + mensaje + '</p>')
                .slideDown();
        }

    });

})(jQuery);