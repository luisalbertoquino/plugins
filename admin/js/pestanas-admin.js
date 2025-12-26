/**
 * JavaScript para la gestión de pestañas
 * Certificados Digitales PRO
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ========================================
        // VARIABLES GLOBALES
        // ========================================
        var $modal = $('#modal-pestana');
        var $form = $('#form-pestana');
        var $modalTitle = $('#modal-pestana-title');
        var pestanaEditando = null;

        // ========================================
        // ABRIR MODAL: NUEVA PESTAÑA
        // ========================================
        $('#btn-nueva-pestana, #btn-crear-primera-pestana').on('click', function(e) {
            e.preventDefault();
            abrirModalNueva();
        });

        // ========================================
        // CERRAR MODAL
        // ========================================
        $('#btn-close-modal-pestana, #btn-cancel-modal-pestana').on('click', function(e) {
            e.preventDefault();
            cerrarModal();
        });

        // Cerrar modal al hacer clic fuera
        $(window).on('click', function(e) {
            if ($(e.target).is('#modal-pestana')) {
                cerrarModal();
            }
        });

        // ========================================
        // MOSTRAR PREVIEW DE PLANTILLA AL SELECCIONAR
        // ========================================
        $('#pestana_plantilla').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview-plantilla').attr('src', e.target.result);
                    $('#preview-plantilla-container').show();
                    $('#upload-plantilla-container').hide();
                };
                reader.readAsDataURL(file);
            }
        });

        // Botón para cambiar plantilla
        $('#btn-cambiar-plantilla').on('click', function(e) {
            e.preventDefault();
            $('#preview-plantilla-container').hide();
            $('#upload-plantilla-container').show();
            $('#pestana_plantilla').val('');
            $('#plantilla_url_actual').val('');
        });

        // ========================================
        // GUARDAR PESTAÑA (CREAR O EDITAR)
        // ========================================
        $form.on('submit', function(e) {
            e.preventDefault();

            var $button = $('#btn-save-pestana');
            var $spinner = $('.certificados-modal-footer .spinner');
            var $message = $('#form-pestana-message');
            
            var formData = new FormData(this);
            var pestanaId = $('#pestana_id').val();

            // Validar que haya plantilla (solo al crear)
            if (!pestanaId) {
                var fileInput = $('#pestana_plantilla')[0];
                if (!fileInput.files || !fileInput.files[0]) {
                    mostrarMensaje($message, 'error', 'Debes seleccionar una plantilla.');
                    return;
                }
            }

            // Deshabilitar botón y mostrar spinner
            $button.prop('disabled', true).text(certificadosPestanasAdmin.i18n.saving);
            $spinner.addClass('is-active');
            $message.removeClass('success error').hide();

            // Si hay archivo de plantilla (crear nueva o editar con nueva plantilla)
            var fileInput = $('#pestana_plantilla')[0];
            if (fileInput.files && fileInput.files.length > 0) {
                // Primero subir la plantilla, luego guardar la pestaña
                subirPlantillaYGuardar(formData, pestanaId, $button, $spinner, $message);
            } else {
                // No hay plantilla nueva, guardar directamente (solo en edición)
                var action = pestanaId ? 'certificados_actualizar_pestana' : 'certificados_crear_pestana';
                formData.append('action', action);
                formData.append('nonce', certificadosPestanasAdmin.nonce);
                
                if (pestanaId) {
                    formData.append('id', pestanaId);
                }
                
                guardarPestana(formData, $button, $spinner, $message);
            }
        });

        // Función para subir plantilla y luego guardar
        function subirPlantillaYGuardar(formData, pestanaId, $button, $spinner, $message) {
            var uploadData = new FormData();
            uploadData.append('action', 'certificados_subir_plantilla_pestana');
            uploadData.append('nonce', certificadosPestanasAdmin.nonce);
            uploadData.append('plantilla_archivo', $('#pestana_plantilla')[0].files[0]);
            uploadData.append('evento_id', $('input[name="evento_id"]').val());

            $.ajax({
                url: certificadosPestanasAdmin.ajaxurl,
                type: 'POST',
                data: uploadData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Ahora guardar la pestaña con la URL de la plantilla
                        var saveData = new FormData();
                        
                        // Determinar acción
                        var action = pestanaId ? 'certificados_actualizar_pestana' : 'certificados_crear_pestana';
                        saveData.append('action', action);
                        saveData.append('nonce', certificadosPestanasAdmin.nonce);
                        saveData.append('evento_id', $('input[name="evento_id"]').val());
                        saveData.append('nombre_pestana', $('#pestana_nombre').val());
                        saveData.append('nombre_hoja_sheet', $('#pestana_hoja').val());
                        saveData.append('plantilla_url', response.data.url);
                        
                        if (pestanaId) {
                            saveData.append('id', pestanaId);
                        }
                        
                        guardarPestana(saveData, $button, $spinner, $message);
                    } else {
                        mostrarMensaje($message, 'error', response.data.message);
                        $button.prop('disabled', false).text('Guardar Pestaña');
                        $spinner.removeClass('is-active');
                    }
                },
                error: function() {
                    mostrarMensaje($message, 'error', 'Error al subir la plantilla.');
                    $button.prop('disabled', false).text('Guardar Pestaña');
                    $spinner.removeClass('is-active');
                }
            });
        }

        // Función para guardar pestaña
        function guardarPestana(formData, $button, $spinner, $message) {
            $.ajax({
                url: certificadosPestanasAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        mostrarMensaje($message, 'success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        mostrarMensaje($message, 'error', response.data.message);
                        $button.prop('disabled', false).text('Guardar Pestaña');
                        $spinner.removeClass('is-active');
                    }
                },
                error: function(xhr, status, error) {
                    mostrarMensaje($message, 'error', 'Error en la conexión: ' + error);
                    $button.prop('disabled', false).text('Guardar Pestaña');
                    $spinner.removeClass('is-active');
                }
            });
        }

        // ========================================
        // EDITAR PESTAÑA
        // ========================================
        $(document).on('click', '.btn-editar-pestana', function(e) {
            e.preventDefault();
            var pestanaId = $(this).data('id');
            cargarPestanaParaEditar(pestanaId);
        });

        function cargarPestanaParaEditar(pestanaId) {
            $.ajax({
                url: certificadosPestanasAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_obtener_pestana',
                    nonce: certificadosPestanasAdmin.nonce,
                    id: pestanaId
                },
                success: function(response) {
                    if (response.success) {
                        var pestana = response.data;
                        
                        // Llenar el formulario
                        $('#pestana_id').val(pestana.id);
                        $('#pestana_nombre').val(pestana.nombre_pestana);
                        $('#pestana_hoja').val(pestana.nombre_hoja_sheet);
                        $('#plantilla_url_actual').val(pestana.plantilla_url);
                        
                        // Mostrar preview si hay plantilla
                        if (pestana.plantilla_url) {
                            $('#preview-plantilla').attr('src', pestana.plantilla_url);
                            $('#preview-plantilla-container').show();
                            $('#upload-plantilla-container').hide();
                        }
                        
                        abrirModalEditar();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Error al cargar los datos de la pestaña.');
                }
            });
        }

        // ========================================
        // ELIMINAR PESTAÑA
        // ========================================
        $(document).on('click', '.btn-eliminar-pestana', function(e) {
            e.preventDefault();

            var $button = $(this);
            var pestanaId = $button.data('id');
            var pestanaNombre = $button.data('nombre');

            // Confirmar eliminación
            var confirmMessage = certificadosPestanasAdmin.i18n.confirmDelete.replace('%s', pestanaNombre);
            if (!confirm(confirmMessage)) {
                return;
            }

            // Deshabilitar botón
            $button.prop('disabled', true).text(certificadosPestanasAdmin.i18n.deleting);

            // Enviar AJAX
            $.ajax({
                url: certificadosPestanasAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_eliminar_pestana',
                    nonce: certificadosPestanasAdmin.nonce,
                    id: pestanaId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false).text('🗑️');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error en la conexión: ' + error);
                    $button.prop('disabled', false).text('🗑️');
                }
            });
        });

        // ========================================
        // CONFIGURAR CAMPOS (placeholder por ahora)
        // ========================================
        // ========================================
        // CONFIGURAR CAMPOS
        // ========================================
        $(document).on('click', '.btn-configurar-campos', function(e) {
            e.preventDefault();
            var pestanaId = $(this).data('id');
            // Redirigir al configurador
            var url = certificadosPestanasAdmin.ajaxurl.replace('admin-ajax.php', 'admin.php');
            window.location.href = url + '?page=certificados-digitales-configurador&pestana_id=' + pestanaId;
        });

        // ========================================
        // SORTABLE (REORDENAR PESTAÑAS)
        // ========================================
        if ($('#pestanas-sortable').length && typeof $.fn.sortable !== 'undefined') {
            $('#pestanas-sortable').sortable({
                handle: '.pestana-drag-handle',
                placeholder: 'pestana-placeholder',
                update: function(event, ui) {
                    var orden = [];
                    $('.pestana-card').each(function() {
                        orden.push($(this).data('id'));
                    });

                    // Guardar nuevo orden
                    $.ajax({
                        url: certificadosPestanasAdmin.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'certificados_reordenar_pestanas',
                            nonce: certificadosPestanasAdmin.nonce,
                            orden: orden
                        },
                        success: function(response) {
                            if (response.success) {
                                // Actualizar números de orden visualmente
                                $('.pestana-card').each(function(index) {
                                    $(this).find('.pestana-orden').text('#' + (index + 1));
                                });
                            }
                        }
                    });
                }
            });
        }

        // ========================================
        // FUNCIONES AUXILIARES
        // ========================================

        function abrirModalNueva() {
            pestanaEditando = null;
            $modalTitle.text('Nueva Pestaña');
            $('#btn-save-pestana').text('Guardar Pestaña');
            $form[0].reset();
            $('#pestana_id').val('');
            $('#plantilla_url_actual').val('');
            $('#preview-plantilla-container').hide();
            $('#upload-plantilla-container').show();
            $('#form-pestana-message').removeClass('success error').hide();

            // Usar el mismo método de forzado
            $modal.show();
            if (!$modal.is(':visible')) {
                $modal.attr('style', 'display: block !important;');
            }
        }

        function abrirModalEditar() {
            $modalTitle.text('Editar Pestaña');
            $('#btn-save-pestana').text('Actualizar Pestaña');
            $('#form-pestana-message').removeClass('success error').hide();

            // Usar el mismo método de forzado
            $modal.show();
            if (!$modal.is(':visible')) {
                $modal.attr('style', 'display: block !important;');
            }
        }

        function cerrarModal() {
            $modal.fadeOut(300);
            $form[0].reset();
            $('#pestana_id').val('');
            $('#preview-plantilla-container').hide();
            $('#upload-plantilla-container').show();
            pestanaEditando = null;
        }

        function mostrarMensaje($elemento, tipo, mensaje) {
            $elemento
                .removeClass('success error')
                .addClass(tipo)
                .html('<p>' + mensaje + '</p>')
                .slideDown();
        }

    });

})(jQuery);