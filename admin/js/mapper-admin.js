/**
 * JavaScript para Mapeo de Columnas
 *
 * @package Certificados_Digitales
 */

(function($) {
    'use strict';

    let currentSheetId = '';
    let currentHeaders = [];
    let currentSuggestions = {};

    $(document).ready(function() {

        // Botones de configurar/editar en la tabla
        $('.btn-config-mapping').on('click', function() {
            const eventoId = $(this).data('evento-id');
            $('#mapper-evento').val(eventoId).trigger('change');
            $('html, body').animate({
                scrollTop: $('.certificados-mapper-card').eq(1).offset().top - 100
            }, 500);
        });

        // Cuando se selecciona un evento
        $('#mapper-evento').on('change', function() {
            const eventoId = $(this).val();
            currentSheetId = $(this).find(':selected').data('sheet-id');

            if (eventoId) {
                $('#sheet-name-group').fadeIn();
                $('#mapper-result').fadeOut();
            } else {
                $('#sheet-name-group').fadeOut();
                $('#mapper-result').fadeOut();
                currentSheetId = '';
            }
        });

        // Cargar cabeceras del sheet
        $('#btn-load-headers').on('click', function() {
            const eventoId = $('#mapper-evento').val();
            const sheetName = $('#mapper-sheet-name').val().trim();

            if (!eventoId || !sheetName) {
                alert('Por favor selecciona un evento e ingresa el nombre de la hoja.');
                return;
            }

            loadSheetHeaders(currentSheetId, sheetName);
        });

        // Guardar mapeo
        $('#btn-save-mapping').on('click', function() {
            saveMappingConfiguration();
        });

        // Aplicar sugerencias
        $('#btn-apply-suggestions').on('click', function() {
            applySuggestions();
        });

        // Limpiar mapeo
        $('#btn-clear-mapping').on('click', function() {
            if (confirm(certificadosMapper.i18n.confirm_delete)) {
                clearMapping();
            }
        });
    });

    /**
     * Cargar cabeceras del Google Sheet
     */
    function loadSheetHeaders(sheetId, sheetName) {
        $('#mapper-loading').fadeIn();
        $('#mapper-result').fadeOut();

        const eventoId = $('#mapper-evento').val();

        // Primero obtener las cabeceras del sheet
        $.ajax({
            url: certificadosMapper.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_sheet_headers',
                nonce: certificadosMapper.nonce,
                sheet_id: sheetId,
                sheet_name: sheetName
            },
            success: function(response) {
                if (response.success) {
                    currentHeaders = response.data.headers;
                    currentSuggestions = response.data.suggestions || {};

                    // Ahora obtener el mapeo guardado si existe
                    loadSavedMapping(eventoId, sheetName, response.data.system_fields);
                } else {
                    $('#mapper-loading').fadeOut();
                    alert(response.data.message || certificadosMapper.i18n.error);
                }
            },
            error: function() {
                $('#mapper-loading').fadeOut();
                alert(certificadosMapper.i18n.error);
            }
        });
    }

    /**
     * Cargar mapeo guardado si existe
     */
    function loadSavedMapping(eventoId, sheetName, systemFields) {
        $.ajax({
            url: certificadosMapper.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_column_mapping',
                nonce: certificadosMapper.nonce,
                evento_id: eventoId,
                sheet_name: sheetName
            },
            success: function(response) {
                $('#mapper-loading').fadeOut();

                let savedMapping = {};
                if (response.success && response.data.mapping) {
                    savedMapping = response.data.mapping;
                }

                // Renderizar tabla con el mapeo guardado
                renderMappingTable(systemFields, currentHeaders, savedMapping);
                $('#mapper-result').fadeIn();
            },
            error: function() {
                $('#mapper-loading').fadeOut();
                // Si falla al cargar el mapeo guardado, mostrar la tabla sin mapeo previo
                renderMappingTable(systemFields, currentHeaders, {});
                $('#mapper-result').fadeIn();
            }
        });
    }

    /**
     * Renderizar tabla de mapeo
     */
    function renderMappingTable(systemFields, headers, savedMapping) {
        savedMapping = savedMapping || {};

        let html = '<table class="widefat"><thead><tr>';
        html += '<th>Campo del Sistema</th>';
        html += '<th>Columna del Sheet</th>';
        html += '<th>Sugerencia</th>';
        html += '</tr></thead><tbody>';

        $.each(systemFields, function(fieldKey, fieldLabel) {
            html += '<tr>';
            html += '<td><strong>' + fieldLabel + '</strong></td>';
            html += '<td><select name="mapping[' + fieldKey + '][sheet_column]" class="mapper-select" data-field="' + fieldKey + '">';
            html += '<option value="">-- No mapear --</option>';

            // Determinar qué valor debe estar seleccionado (prioridad: guardado > sugerencia)
            let selectedValue = '';
            let selectedIndex = '';

            if (savedMapping[fieldKey]) {
                // Hay mapeo guardado, usarlo
                selectedValue = savedMapping[fieldKey].sheet_column;
                selectedIndex = savedMapping[fieldKey].column_index;
            } else if (currentSuggestions[fieldKey]) {
                // No hay mapeo guardado, usar sugerencia
                selectedValue = currentSuggestions[fieldKey].sheet_column;
                selectedIndex = currentSuggestions[fieldKey].column_index;
            }

            $.each(headers, function(index, header) {
                const selected = (header.name === selectedValue) ? 'selected' : '';
                html += '<option value="' + header.name + '" data-index="' + header.index + '" ' + selected + '>' + header.name + '</option>';
            });

            html += '</select>';
            html += '<input type="hidden" name="mapping[' + fieldKey + '][column_index]" class="mapper-index" data-field="' + fieldKey + '" value="' + selectedIndex + '">';
            html += '</td>';

            // Columna de sugerencias
            html += '<td class="mapper-suggestion">';
            if (savedMapping[fieldKey]) {
                html += '<span class="dashicons dashicons-saved" style="color: blue;"></span> <strong>Guardado:</strong> ' + savedMapping[fieldKey].sheet_column;
            } else if (currentSuggestions[fieldKey]) {
                html += '<span class="dashicons dashicons-yes-alt" style="color: green;"></span> ' + currentSuggestions[fieldKey].sheet_column;
            } else {
                html += '<span class="dashicons dashicons-warning" style="color: orange;"></span> Sin sugerencia';
            }
            html += '</td>';

            html += '</tr>';
        });

        html += '</tbody></table>';

        $('#mapping-table').html(html);

        // Actualizar índices cuando cambie la selección
        $('.mapper-select').on('change', function() {
            const field = $(this).data('field');
            const selectedIndex = $(this).find(':selected').data('index');
            $('input.mapper-index[data-field="' + field + '"]').val(selectedIndex || '');
        });

        // Disparar change para inicializar índices (solo si no hay valor previo)
        $('.mapper-select').each(function() {
            const $select = $(this);
            const field = $select.data('field');
            const $indexInput = $('input.mapper-index[data-field="' + field + '"]');

            // Si ya tiene un valor guardado, no sobrescribirlo
            if (!$indexInput.val()) {
                $select.trigger('change');
            }
        });
    }

    /**
     * Guardar configuración de mapeo
     */
    function saveMappingConfiguration() {
        const eventoId = $('#mapper-evento').val();
        const sheetName = $('#mapper-sheet-name').val();
        const mappings = {};

        // Recoger todos los mapeos
        $('.mapper-select').each(function() {
            const field = $(this).data('field');
            const sheetColumn = $(this).val();
            const columnIndex = $('input.mapper-index[data-field="' + field + '"]').val();

            if (sheetColumn) {
                mappings[field] = {
                    sheet_column: sheetColumn,
                    column_index: parseInt(columnIndex)
                };
            }
        });

        if (Object.keys(mappings).length === 0) {
            alert('Por favor mapea al menos un campo.');
            return;
        }

        $.ajax({
            url: certificadosMapper.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_save_column_mapping',
                nonce: certificadosMapper.nonce,
                evento_id: eventoId,
                sheet_name: sheetName,
                mappings: mappings
            },
            success: function(response) {
                if (response.success) {
                    alert(certificadosMapper.i18n.saved);
                    // Recargar la página para actualizar la tabla de estado
                    location.reload();
                } else {
                    alert(response.data.message || 'Error al guardar.');
                }
            },
            error: function() {
                alert('Error al guardar el mapeo.');
            }
        });
    }

    /**
     * Aplicar sugerencias automáticas
     */
    function applySuggestions() {
        $.each(currentSuggestions, function(field, suggestion) {
            const $select = $('.mapper-select[data-field="' + field + '"]');
            $select.val(suggestion.sheet_column).trigger('change');
        });

        alert('Sugerencias aplicadas. Revisa los mapeos y guarda si son correctos.');
    }

    /**
     * Limpiar mapeo
     */
    function clearMapping() {
        $('.mapper-select').val('').trigger('change');
    }

})(jQuery);
