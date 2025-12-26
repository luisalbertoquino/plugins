/**
 * JavaScript para la gestión de eventos
 * Certificados Digitales PRO
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Escuchar cambios en shortcodes (sincronización entre pestañas)
        try {
            window.addEventListener('storage', function(e) {
                if ( e.key === 'certificados_shortcodes_updated' ) {
                    // Recargar la página para refrescar la tabla de eventos
                    location.reload();
                }
            });
        } catch (err) {
            // no-op
        }


        // ========================================
        // VARIABLES GLOBALES
        // ========================================
        var $modal = $('#modal-evento');
        var $form = $('#form-evento');
        var $modalTitle = $('#modal-evento-title');
        var eventoEditando = null;

        // ========================================
        // INICIALIZAR DATATABLES
        // ========================================
        if ($('#table-eventos').length) {
            $('#table-eventos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] } // Desactivar orden en acciones (índice actualizado por la nueva columna Shortcode)
                ]
            });
        }

        // ========================================
        // COPIAR SHORTCODE AL PORTAPAPELES
        // ========================================
        $(document).on('click', '.btn-copiar-shortcode', function(e) {
            e.preventDefault();
            var $button = $(this);
            var eventoId = $button.data('id');
            var shortcode = '[certificados_evento evento_id="' + eventoId + '"]';

            // Copiar al portapapeles
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    // Feedback visual
                    var $icon = $button.find('i');
                    var originalClass = $icon.attr('class');

                    $icon.removeClass('fa-copy').addClass('fa-check');
                    $button.css('background-color', '#d4edda').css('border-color', '#28a745');

                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy');
                        $button.css('background-color', '').css('border-color', '');
                    }, 1500);
                }).catch(function(err) {
                    console.error('Error al copiar:', err);
                    alert('Error al copiar el shortcode al portapapeles');
                });
            } else {
                // Fallback para navegadores antiguos
                var $textarea = $('<textarea>').text(shortcode).appendTo('body');
                $textarea.select();
                try {
                    document.execCommand('copy');

                    // Feedback visual fallback
                    var $icon = $button.find('i');
                    $icon.removeClass('fa-copy').addClass('fa-check');
                    $button.css('background-color', '#d4edda').css('border-color', '#28a745');

                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy');
                        $button.css('background-color', '').css('border-color', '');
                    }, 1500);
                } catch (err) {
                    alert('No se pudo copiar el shortcode');
                }
                $textarea.remove();
            }
        });

        // ========================================
        // COPIAR SHEET ID AL PORTAPAPELES
        // ========================================
        $(document).on('click', '.btn-copiar-sheet-id', function(e) {
            e.preventDefault();
            var $button = $(this);
            var sheetId = $button.data('sheet-id');

            // Copiar al portapapeles
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(sheetId).then(function() {
                    // Feedback visual
                    var $icon = $button.find('i');

                    $icon.removeClass('fa-copy').addClass('fa-check');
                    $button.css('background-color', '#d4edda').css('border-color', '#28a745').css('color', '#fff');

                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy');
                        $button.css('background-color', '').css('border-color', '').css('color', '');
                    }, 1500);
                }).catch(function(err) {
                    console.error('Error al copiar:', err);
                    alert('Error al copiar el Sheet ID al portapapeles');
                });
            } else {
                // Fallback para navegadores antiguos
                var $textarea = $('<textarea>').text(sheetId).appendTo('body');
                $textarea.select();
                try {
                    document.execCommand('copy');

                    // Feedback visual fallback
                    var $icon = $button.find('i');
                    $icon.removeClass('fa-copy').addClass('fa-check');
                    $button.css('background-color', '#d4edda').css('border-color', '#28a745').css('color', '#fff');

                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy');
                        $button.css('background-color', '').css('border-color', '').css('color', '');
                    }, 1500);
                } catch (err) {
                    alert('No se pudo copiar el Sheet ID');
                }
                $textarea.remove();
            }
        });

        // ========================================
        // ABRIR MODAL: NUEVO EVENTO
        // ========================================
        $('#btn-nuevo-evento, #btn-crear-primer-evento').on('click', function(e) {
            e.preventDefault();
            abrirModalNuevo();
        });

        // ========================================
        // CERRAR MODAL
        // ========================================
        $('#btn-close-modal, #btn-cancel-modal').on('click', function(e) {
            e.preventDefault();
            cerrarModal();
        });

        // Cerrar modal al hacer clic fuera (en el overlay, no en el contenido)
        $('#modal-evento').on('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con tecla ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if ($modal.is(':visible')) {
                    cerrarModal();
                }
            }
        });

        // ========================================
        // GUARDAR EVENTO (CREAR O EDITAR)
        // ========================================
        $form.on('submit', function(e) {
            e.preventDefault();

            var $button = $('#btn-save-evento');
            var $spinner = $('.certificados-modal-footer .spinner');
            var $message = $('#form-evento-message');
            var formData = $form.serialize();

            // Determinar si es crear o editar
            var eventoId = $('#evento_id').val();
            var action = eventoId ? 'certificados_actualizar_evento' : 'certificados_crear_evento';

            // Agregar action y nonce
            formData += '&action=' + action;
            formData += '&nonce=' + certificadosEventosAdmin.nonce;
            
            if (eventoId) {
                formData += '&id=' + eventoId;
            }

            // Deshabilitar botón y mostrar spinner
            $button.prop('disabled', true).text(certificadosEventosAdmin.i18n.saving);
            $spinner.addClass('is-active');
            $message.removeClass('success error').hide();

            // Enviar AJAX
            $.ajax({
                url: certificadosEventosAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        mostrarMensaje($message, 'success', response.data.message);
                        
                        // Recargar página después de 1 segundo
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        mostrarMensaje($message, 'error', response.data.message);
                        $button.prop('disabled', false).text(eventoId ? 'Actualizar Evento' : 'Guardar Evento');
                        $spinner.removeClass('is-active');
                    }
                },
                error: function(xhr, status, error) {
                    mostrarMensaje($message, 'error', 'Error en la conexión: ' + error);
                    $button.prop('disabled', false).text(eventoId ? 'Actualizar Evento' : 'Guardar Evento');
                    $spinner.removeClass('is-active');
                }
            });
        });

        // ========================================
        // EDITAR EVENTO
        // ========================================
        $(document).on('click', '.btn-editar-evento', function(e) {
            e.preventDefault();
            var eventoId = $(this).data('id');
            cargarEventoParaEditar(eventoId);
        });

        // ========================================
        // TOGGLE ESTADO (ACTIVAR/DESACTIVAR)
        // ========================================
        $(document).on('click', '.btn-toggle-evento', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $row = $button.closest('tr');
            var eventoId = $button.data('id');
            var estadoActual = $button.data('estado');

            // Deshabilitar botón
            $button.prop('disabled', true);

            // Enviar AJAX
            $.ajax({
                url: certificadosEventosAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_toggle_evento',
                    nonce: certificadosEventosAdmin.nonce,
                    id: eventoId
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de éxito
                        var mensaje = response.data.nuevo_estado ? 
                            'Evento activado correctamente.' :
                            'Evento desactivado correctamente.';
                        
                        mostrarMensaje($('#form-evento-message'), 'success', mensaje);
                        
                        // Recargar página después de 1.5 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        alert('Error: ' + response.data.message);
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error en la conexión: ' + error);
                    $button.prop('disabled', false);
                }
            });
        });

        // ========================================
        // ELIMINAR EVENTO
        // ========================================
        $(document).on('click', '.btn-eliminar-evento', function(e) {
            e.preventDefault();

            var $button = $(this);
            var eventoId = $button.data('id');
            var eventoNombre = $button.data('nombre');

            // Confirmar eliminación
            var confirmMessage = certificadosEventosAdmin.i18n.confirmDelete.replace('%s', eventoNombre);
            if (!confirm(confirmMessage)) {
                return;
            }

            // Deshabilitar botón
            $button.prop('disabled', true).text(certificadosEventosAdmin.i18n.deleting);

            // Enviar AJAX
            $.ajax({
                url: certificadosEventosAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_eliminar_evento',
                    nonce: certificadosEventosAdmin.nonce,
                    id: eventoId
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
        // FUNCIONES AUXILIARES
        // ========================================

        function abrirModalNuevo() {
            eventoEditando = null;
            $modalTitle.text('Nuevo Evento');
            $('#btn-save-evento').text('Guardar Evento');
            $form[0].reset();
            $('#evento_id').val('');
            $('#logo_preview_img').hide();
            $('#btn-remove-logo').hide();
            $('#form-evento-message').removeClass('success error').hide();

            // Forzar visualización del modal
            $modal.show();
            if (!$modal.is(':visible')) {
                $modal.attr('style', 'display: block !important;');
            }
        }

        function abrirModalEditar() {
            $modalTitle.text('Editar Evento');
            $('#btn-save-evento').text('Actualizar Evento');
            $('#form-evento-message').removeClass('success error').hide();

            // Usar el mismo método que abrirModalNuevo
            $modal.show();
            if (!$modal.is(':visible')) {
                $modal.attr('style', 'display: block !important;');
            }
        }

        function cerrarModal() {
            $modal.fadeOut(300);
            $form[0].reset();
            $('#evento_id').val('');
            $('#logo_preview_img').hide();
            $('#btn-remove-logo').hide();
            eventoEditando = null;
        }

        function cargarEventoParaEditar(eventoId) {
            // Buscar el evento en la tabla
            var $row = $('.btn-editar-evento[data-id="' + eventoId + '"]').closest('tr');
            var tabla = $('#table-eventos').DataTable();
            var data = tabla.row($row).data();

            if (!data) {
                alert('No se pudo cargar el evento');
                return;
            }

            // Hacer petición AJAX para obtener todos los datos del evento
            $.ajax({
                url: certificadosEventosAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_obtener_evento',
                    nonce: certificadosEventosAdmin.nonce,
                    id: eventoId
                },
                success: function(response) {
                    if (response.success) {
                        var evento = response.data;
                        
                        // Llenar el formulario
                        $('#evento_id').val(evento.id);
                        $('#evento_nombre').val(evento.nombre);
                        $('#evento_sheet_id').val(evento.sheet_id);
                        $('#evento_url_encuesta').val(evento.url_encuesta || '');
                        $('#evento_logo_loader').val(evento.logo_loader_url || '');
                        
                        // Mostrar preview del logo si existe
                        if (evento.logo_loader_url) {
                            $('#logo_preview_img').attr('src', evento.logo_loader_url).show();
                            $('#btn-remove-logo').show();
                        } else {
                            $('#logo_preview_img').hide();
                            $('#btn-remove-logo').hide();
                        }
                        
                        abrirModalEditar();
                    } else {
                        // Fallback: usar datos básicos de la tabla
                        $('#evento_id').val(eventoId);
                        $('#evento_nombre').val($row.find('td:eq(1)').text().trim());
                        $('#evento_sheet_id').val($row.find('td:eq(2) code').text().trim());
                        
                        abrirModalEditar();
                    }
                },
                error: function() {
                    // Fallback: usar datos básicos de la tabla
                    $('#evento_id').val(eventoId);
                    $('#evento_nombre').val($row.find('td:eq(1)').text().trim());
                    $('#evento_sheet_id').val($row.find('td:eq(2) code').text().trim());
                    
                    abrirModalEditar();
                }
            });
        }

        // ========================================
        // MEDIA UPLOADER PARA LOGO
        // ========================================
        var mediaUploader;

        $('#btn-select-logo').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: certificadosEventosAdmin.i18n.selectLogoTitle || 'Seleccionar Logo',
                button: {
                    text: certificadosEventosAdmin.i18n.selectLogoButton || 'Usar esta imagen'
                },
                multiple: false,
                library: {
                    type: ['image']
                }
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Establecer la URL en el input oculto
                $('#evento_logo_loader').val(attachment.url);
                
                // Mostrar preview
                $('#logo_preview_img').attr('src', attachment.url).show();
                $('#btn-remove-logo').show();
            });
            
            mediaUploader.open();
        });

        $('#btn-remove-logo').on('click', function(e) {
            e.preventDefault();
            
            // Limpiar el input
            $('#evento_logo_loader').val('');
            
            // Ocultar preview
            $('#logo_preview_img').hide();
            $('#btn-remove-logo').hide();
        });

        // ========================================
        // CARGAR PREVIEW AL EDITAR
        // ========================================
        var originalFormDataHandler = window.cargarEventoParaEditar;
        window.cargarEventoParaEditar = function() {
            // Llamar a la función original si existe
            if (typeof originalFormDataHandler === 'function') {
                originalFormDataHandler.apply(this, arguments);
            }
            
            // Mostrar preview si hay logo
            var logoUrl = $('#evento_logo_loader').val();
            if (logoUrl) {
                $('#logo_preview_img').attr('src', logoUrl).show();
                $('#btn-remove-logo').show();
            } else {
                $('#logo_preview_img').hide();
                $('#btn-remove-logo').hide();
            }
        };

        function mostrarMensaje($elemento, tipo, mensaje) {
            $elemento
                .removeClass('success error')
                .addClass(tipo)
                .html('<p>' + mensaje + '</p>')
                .slideDown();
        }

    });

})(jQuery);