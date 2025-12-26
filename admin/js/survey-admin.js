/**
 * JavaScript para Administración de Encuestas
 *
 * @package Certificados_Digitales
 */

(function($) {
    'use strict';

    let currentResponseHeaders = [];

    $(document).ready(function() {

        // Botones de configurar/editar en la tabla
        $('.btn-config-survey').on('click', function() {
            const eventoId = $(this).data('evento-id');
            $('#survey-evento').val(eventoId).trigger('change');
            $('html, body').animate({
                scrollTop: $('#survey-config-container').offset().top - 100
            }, 500);
        });

        // Cuando se selecciona un evento
        $('#survey-evento').on('change', function() {
            const eventoId = $(this).val();
            const sheetId = $(this).find(':selected').data('sheet-id');

            if (eventoId) {
                loadSurveyConfig(eventoId, sheetId);
            } else {
                $('#survey-config-container').fadeOut();
            }
        });

        // Cambio de modo de encuesta
        $('#survey-mode').on('change', function() {
            const mode = $(this).val();

            if (mode === 'mandatory') {
                $('#mandatory-config').fadeIn();
            } else {
                $('#mandatory-config').fadeOut();
            }
        });

        // Cargar cabeceras del sheet de respuestas
        $('#btn-load-response-headers').on('click', function() {
            loadResponseHeaders();
        });

        // Cambio en columna de evento
        $('#event-column').on('change', function() {
            const value = $(this).val();
            const index = $(this).find(':selected').data('index');

            $('#event-column-index').val(index || '');

            if (value) {
                $('#event-value-row').fadeIn();
            } else {
                $('#event-value-row').fadeOut();
            }
        });

        // Cambio en columna de documento
        $('#document-column').on('change', function() {
            const index = $(this).find(':selected').data('index');
            $('#document-column-index').val(index || '');
        });

        // Guardar configuración
        $('#survey-config-form').on('submit', function(e) {
            e.preventDefault();
            saveSurveyConfig();
        });
    });

    /**
     * Cargar configuración existente de encuesta
     */
    function loadSurveyConfig(eventoId, sheetId) {
        $('#survey-loading').fadeIn();
        $('#survey-config-container').fadeOut();

        $.ajax({
            url: certificadosSurvey.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_survey_config',
                nonce: certificadosSurvey.nonce,
                evento_id: eventoId
            },
            success: function(response) {
                $('#survey-loading').fadeOut();

                if (response.success) {
                    populateForm(response.data.config, eventoId);
                    $('#survey-config-container').fadeIn();
                } else {
                    // No hay configuración, mostrar formulario vacío
                    resetForm();
                    $('#survey-evento-id').val(eventoId);
                    $('#survey-config-container').fadeIn();
                }
            },
            error: function() {
                $('#survey-loading').fadeOut();
                alert(certificadosSurvey.i18n.error);
            }
        });
    }

    /**
     * Poblar formulario con datos existentes
     */
    function populateForm(config, eventoId) {
        if (!config) {
            resetForm();
            $('#survey-evento-id').val(eventoId);
            return;
        }

        $('#survey-evento-id').val(eventoId);
        $('#survey-mode').val(config.survey_mode || 'disabled');
        $('#survey-url').val(config.survey_url || '');
        $('#survey-title').val(config.survey_title || '');
        $('#survey-message').val(config.survey_message || '');
        $('#response-sheet-id').val(config.response_sheet_id || '');
        $('#response-sheet-name').val(config.response_sheet_name || '');
        $('#document-column-index').val(config.document_column_index || '');
        $('#event-column-index').val(config.event_column_index || '');
        $('#event-match-value').val(config.event_match_value || '');

        // Mostrar sección de configuración obligatoria si aplica
        if (config.survey_mode === 'mandatory') {
            $('#mandatory-config').show();
        }

        // Disparar change en el modo
        $('#survey-mode').trigger('change');
    }

    /**
     * Resetear formulario
     */
    function resetForm() {
        $('#survey-config-form')[0].reset();
        $('#mandatory-config').hide();
        $('#document-column-row').hide();
        $('#event-column-row').hide();
        $('#event-value-row').hide();
    }

    /**
     * Cargar cabeceras del sheet de respuestas
     */
    function loadResponseHeaders() {
        const sheetId = $('#response-sheet-id').val().trim();
        const sheetName = $('#response-sheet-name').val().trim();

        if (!sheetId || !sheetName) {
            alert('Por favor ingresa el ID del Sheet y el nombre de la hoja.');
            return;
        }

        $.ajax({
            url: certificadosSurvey.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_survey_sheet_headers',
                nonce: certificadosSurvey.nonce,
                sheet_id: sheetId,
                sheet_name: sheetName
            },
            success: function(response) {
                if (response.success) {
                    currentResponseHeaders = response.data.headers;
                    renderHeaderSelectors(currentResponseHeaders);
                    $('#document-column-row').fadeIn();
                    $('#event-column-row').fadeIn();
                } else {
                    alert(response.data.message || certificadosSurvey.i18n.error);
                }
            },
            error: function() {
                alert(certificadosSurvey.i18n.error);
            }
        });
    }

    /**
     * Renderizar selectores de cabeceras
     */
    function renderHeaderSelectors(headers) {
        let options = '<option value="">Seleccionar columna...</option>';

        $.each(headers, function(index, header) {
            options += '<option value="' + header.name + '" data-index="' + header.index + '">' + header.name + '</option>';
        });

        $('#document-column').html(options);

        let eventOptions = '<option value="">No validar evento</option>';
        $.each(headers, function(index, header) {
            eventOptions += '<option value="' + header.name + '" data-index="' + header.index + '">' + header.name + '</option>';
        });

        $('#event-column').html(eventOptions);
    }

    /**
     * Guardar configuración de encuesta
     */
    function saveSurveyConfig() {
        const eventoId = $('#survey-evento-id').val();
        const config = {
            survey_mode: $('#survey-mode').val(),
            survey_url: $('#survey-url').val(),
            survey_title: $('#survey-title').val(),
            survey_message: $('#survey-message').val(),
            response_sheet_id: $('#response-sheet-id').val(),
            response_sheet_name: $('#response-sheet-name').val(),
            document_column: $('#document-column').val(),
            document_column_index: $('#document-column-index').val(),
            event_column: $('#event-column').val(),
            event_column_index: $('#event-column-index').val(),
            event_match_value: $('#event-match-value').val()
        };

        // Validaciones básicas
        if (config.survey_mode !== 'disabled' && !config.survey_url) {
            alert('Por favor ingresa la URL de la encuesta.');
            return;
        }

        if (config.survey_mode === 'mandatory') {
            if (!config.response_sheet_id || !config.response_sheet_name) {
                alert('Para el modo obligatorio, debes configurar el Google Sheet de respuestas.');
                return;
            }
            if (!config.document_column) {
                alert('Debes seleccionar la columna de número de documento.');
                return;
            }
        }

        $.ajax({
            url: certificadosSurvey.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_save_survey_config',
                nonce: certificadosSurvey.nonce,
                evento_id: eventoId,
                config: config
            },
            success: function(response) {
                if (response.success) {
                    alert(certificadosSurvey.i18n.saved);
                    // Recargar la página para actualizar la tabla de estado
                    location.reload();
                } else {
                    alert(response.data.message || certificadosSurvey.i18n.error);
                }
            },
            error: function() {
                alert(certificadosSurvey.i18n.error);
            }
        });
    }

})(jQuery);
