/**
 * JavaScript para el editor de certificados.
 * plugins-main/admin/js/certificados-pdf-editor.js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Variables globales
    var camposArray = []; // Array para almacenar los datos de los campos
    var zoomLevel = 1.0;  // Nivel de zoom actual
    var gridSize = 10;    // Tamaño de la cuadrícula
    var nextCampoId = 1;  // ID para el próximo campo a crear

    // Inicialización cuando el DOM está listo
    $(document).ready(function() {
        console.log('Editor de certificados inicializado');
        
        // Inicializar el selector de color
        if ($.fn.wpColorPicker) {
            $('.color-field').wpColorPicker();
        }
        
        // Inicializar el selector de plantilla (imagen)
        initPlantillaSelector();

        // Inicializar eventos del editor
        initEditorEvents();
        
        // Inicializar el copiado de shortcode
        initCopyShortcode();
        
        // Inicializar draggable para los campos existentes
        initDraggable();
        
        // Cargar campos existentes si los hay
        loadExistingFields();
        
        // Inicializar el formulario de guardado
        initSaveForm();

        // Inicializar la funcionalidad de probar conexión
        initProbarConexion();  
        
        // Comprobar si ya hay columnas disponibles
        checkColumnas();
    });

    /**
     * Inicializa los eventos del editor
     */
    function initEditorEvents() {
        // Botón Añadir Campo
        $('#add-field').on('click', function() {
            // Verificar si hay columnas disponibles
            if (window.columnasSheet && window.columnasSheet.length === 0) {
                alert('Primero debe probar la conexión con Google Sheets para obtener las columnas disponibles.');
                return;
            }
            
            // Limpiar y preparar el modal para añadir campo
            $('#campo-id').val('');
            $('#campo-nombre').val('');
            $('#campo-tipo').val('texto');
            $('#campo-columna').val('');
            $('#campo-posx').val('0');
            $('#campo-posy').val('0');
            $('#campo-ancho').val('0');
            $('#campo-alto').val('0');
            $('#campo-color').val('#000000');
            $('#campo-tamano').val('12');
            $('#campo-alineacion').val('left');
            
            // Actualizar título del modal y mostrar
            $('#campo-modal-title').text(certificados_pdf_vars.i18n.add_field || 'Añadir Campo');
            $('#campo-modal').show();
        });
        
        // Cerrar modal
        $('.campo-modal-close, #campo-cancelar').on('click', function() {
            $('#campo-modal').hide();
        });
        
        // Guardar campo desde el modal
        $('#campo-form').on('submit', function(e) {
            e.preventDefault();
            saveField();
        });
        
        // Controles de zoom
        $('#zoom-in').on('click', function() {
            updateZoom(zoomLevel + 0.1);
        });
        $('#zoom-out').on('click', function() {
            updateZoom(zoomLevel - 0.1);
        });
        $('#zoom-reset').on('click', function() {
            updateZoom(1.0);
        });
        
        // Controles de cuadrícula
        $('#toggle-grid').on('change', function() {
            $('.certificado-grid').toggle($(this).is(':checked'));
        });
        $('#toggle-guias').on('change', function() {
            toggleGuias($(this).is(':checked'));
        });
        
        // Añadir evento para el checkbox de coordenadas
        $('#toggle-coords').on('change', function() {
            if ($(this).is(':checked')) {
                $('.coord-info').show();
                // Activar el mousemove solo cuando está checked
                $('#certificado-canvas').on('mousemove.coords', showCoordinates);
            } else {
                $('.coord-info').hide();
                // Desactivar el mousemove cuando está unchecked
                $('#certificado-canvas').off('mousemove.coords');
            }
        });
        
        // Inicializar estado de coordenadas (ocultas por defecto)
        $('.coord-info').hide();
        $('#toggle-coords').prop('checked', false);
        
        $('#grid-size').on('change', function() {
            gridSize = parseInt($(this).val());
            updateGrid();
        });
        
        // Delegación de eventos para botones de acción en los campos
        $('#certificado-canvas').on('click', '.edit-campo', function(e) {
            e.stopPropagation();
            var $campo = $(this).closest('.campo-item');
            openEditModal($campo);
        });
        
        $('#certificado-canvas').on('click', '.delete-campo', function(e) {
            e.stopPropagation();
            var $campo = $(this).closest('.campo-item');
            var campoId = $campo.data('id');
            
            if (confirm(certificados_pdf_vars.i18n.confirm_delete || '¿Estás seguro que deseas eliminar este campo?')) {
                $campo.remove();
                removeFieldFromArray(campoId);
            }
        });
        
        // Reemplazar el evento mousemove existente
        $('#certificado-canvas').off('mousemove').on('mousemove', function(e) {
            // Solo mostrar coordenadas si el checkbox está activado
            if ($('#toggle-coords').is(':checked')) {
                showCoordinates.call(this, e);
            }
        });

        // Inicializar grid al cargar
        updateGrid();
    }

    /**
     * Función para mostrar las coordenadas
     */
    function showCoordinates(e) {
        var offset = $(this).offset();
        var x = Math.round((e.pageX - offset.left) / zoomLevel);
        var y = Math.round((e.pageY - offset.top) / zoomLevel);
        
        $('.coord-info').text('X: ' + x + ', Y: ' + y).css({
            left: e.pageX - offset.left + 10,
            top: e.pageY - offset.top + 10
        });
    }


    /**
     * Inicializa el draggable para los campos
     */
    function initDraggable() {
        $('.campo-item').draggable({
            containment: '#certificado-canvas',
            grid: [gridSize, gridSize],
            start: function(event, ui) {
                $(this).addClass('dragging');
            },
            drag: function(event, ui) {
                // Convertir a enteros las posiciones
                var posX = Math.round(ui.position.left);
                var posY = Math.round(ui.position.top);
                
                // Actualizar display de coordenadas con enteros
                $(this).find('.pos-x-display').text(posX);
                $(this).find('.pos-y-display').text(posY);
            },
            stop: function(event, ui) {
                $(this).removeClass('dragging');
                
                // Guardar nueva posición como enteros
                var campoId = $(this).data('id');
                var newPosX = Math.round(ui.position.left);
                var newPosY = Math.round(ui.position.top);
                
                // Actualizar atributos y CSS con enteros
                $(this).attr('data-posx', newPosX);
                $(this).attr('data-posy', newPosY);
                $(this).css('left', newPosX + 'px');
                $(this).css('top', newPosY + 'px');
                
                // Actualizar array de campos
                updateFieldInArray(campoId, {
                    pos_x: newPosX,
                    pos_y: newPosY
                });
                
                console.log('Posición actualizada (enteros) para campo ID:', campoId, 'X:', newPosX, 'Y:', newPosY);
            }
        }).resizable({
            containment: '#certificado-canvas',
            grid: [gridSize, gridSize],
            handles: 'all',
            resize: function(event, ui) {
                // También usar valores enteros aquí
                var $campo = $(this);
                var ancho = Math.round(ui.size.width);
                var alto = Math.round(ui.size.height);
                
                // Si modificas el tamaño de fuente, también usar enteros
                var originalFontSize = parseInt($campo.data('tamano'));
                var scaleFactor = Math.max(0.8, Math.min(1.5, ancho / (parseInt($campo.data('ancho')) || 120)));
                var newFontSize = Math.round(Math.max(12, Math.min(36, originalFontSize * scaleFactor)));
                
                // Aplicar valores
                $campo.css({
                    'width': ancho + 'px',
                    'height': alto + 'px',
                    'font-size': newFontSize + 'px'
                });
            },
            stop: function(event, ui) {
                var $campo = $(this);
                var campoId = $campo.data('id');
                
                // Valores redondeados
                var ancho = Math.round(ui.size.width);
                var alto = Math.round(ui.size.height);
                
                // Actualizar el campo en el array con enteros
                updateFieldInArray(campoId, {
                    ancho: ancho,
                    alto: alto
                });
                
                // Actualizar atributos con enteros
                $campo.attr('data-ancho', ancho);
                $campo.attr('data-alto', alto);
            }
        });
    }

    /**
     * Carga los campos existentes
     */
    function loadExistingFields() {
        // Si hay campos existentes en el HTML
        $('.campo-item').each(function() {
            var $campo = $(this);
            var campoData = {
                id: $campo.data('id'),
                nombre: $campo.data('nombre'),
                tipo: $campo.data('tipo'),
                columna_sheet: $campo.data('columna'),
                pos_x: parseInt($campo.data('posx')),
                pos_y: parseInt($campo.data('posy')),
                ancho: parseInt($campo.data('ancho')),
                alto: parseInt($campo.data('alto')),
                color: $campo.data('color'),
                tamano_fuente: parseInt($campo.data('tamano')),
                alineacion: $campo.data('alineacion')
            };
            
            // Añadir al array
            camposArray.push(campoData);
            
            // Actualizar nextCampoId si es necesario
            if (campoData.id >= nextCampoId) {
                nextCampoId = campoData.id + 1;
            }
        });
        
        console.log('Campos cargados:', camposArray);
    }

    /**
     * Guarda el campo actualmente en el modal
     */
    function saveField() {
        var campoId = $('#campo-id').val();
        var isEditing = campoId !== '';
        
        // Obtener valores del formulario
        var campoData = {
            id: isEditing ? parseInt(campoId) : nextCampoId++,
            nombre: $('#campo-nombre').val(),
            tipo: $('#campo-tipo').val(),
            columna_sheet: $('#campo-columna').val(),
            pos_x: parseInt($('#campo-posx').val()) || 0, // Asegurar que sea número o 0
            pos_y: parseInt($('#campo-posy').val()) || 0,
            ancho: parseInt($('#campo-ancho').val()) || 0,
            alto: parseInt($('#campo-alto').val()) || 0,
            color: $('#campo-color').val(),
            tamano_fuente: parseInt($('#campo-tamano').val()) || 12, // Valor por defecto de 12
            alineacion: $('#campo-alineacion').val(),
            tipografia: $('#campo-tipografia').val() || 'default'
        };
        
        // Validar campos requeridos
        if (!campoData.nombre || !campoData.columna_sheet) {
            alert(certificados_pdf_vars.i18n.required_fields || 'Por favor, complete todos los campos requeridos.');
            return;
        }
        
        // Registrar los datos que se guardarán
        console.log('Guardando campo con datos:', campoData);
        
        if (isEditing) {
            // Si estamos editando, actualizamos directamente con los valores del formulario
            updateFieldInArray(campoData.id, campoData);
            $('#campo-item-' + campoData.id).remove();
        } else {
            // Añadir nuevo campo al array
            camposArray.push(campoData);
        }
        
        // Crear/actualizar elemento visual
        createFieldElement(campoData);
        
        // Cerrar modal
        $('#campo-modal').hide();
        
        // Reinicializar draggable
        initDraggable();
    }
    /**
     * Crea el elemento visual para un campo
     */
    function createFieldElement(campoData) {
        // Asegurar que todos los valores numéricos sean enteros
        var data = {
            id: parseInt(campoData.id),
            nombre: campoData.nombre,
            tipo: campoData.tipo,
            columna_sheet: campoData.columna_sheet,
            pos_x: Math.round(parseInt(campoData.pos_x) || 0),
            pos_y: Math.round(parseInt(campoData.pos_y) || 0),
            ancho: Math.round(parseInt(campoData.ancho) || 0),
            alto: Math.round(parseInt(campoData.alto) || 0),
            color: campoData.color,
            tamano_fuente: Math.round(parseInt(campoData.tamano_fuente) || 12),
            alineacion: campoData.alineacion,
            tipografia: campoData.tipografia || 'default'
        };
        
        console.log('Creando elemento con valores enteros:', data);
        
        var $campo = $('<div>')
            .addClass('campo-item')
            .attr('id', 'campo-item-' + data.id)
            .attr('data-id', data.id)
            .attr('data-nombre', data.nombre)
            .attr('data-tipo', data.tipo)
            .attr('data-columna', data.columna_sheet)
            .attr('data-posx', data.pos_x)
            .attr('data-posy', data.pos_y)
            .attr('data-ancho', data.ancho)
            .attr('data-alto', data.alto)
            .attr('data-color', data.color)
            .attr('data-tamano', data.tamano_fuente)
            .attr('data-alineacion', data.alineacion)
            .attr('data-tipografia', data.tipografia)
            .css({
                'left': data.pos_x + 'px',
                'top': data.pos_y + 'px',
                'color': data.color,
                'font-size': data.tamano_fuente + 'px',
                'text-align': data.alineacion
            });
        
        // Añadir clases adicionales según el tipo de campo
        if (campoData.tipo === 'texto') {
            $campo.addClass('campo-tipo-texto');
        } else if (campoData.tipo === 'fecha') {
            $campo.addClass('campo-tipo-fecha');
        } else if (campoData.tipo === 'numero') {
            $campo.addClass('campo-tipo-numero');
        }
        
        // Añadir ancho/alto si están especificados
        if (campoData.ancho > 0) {
            $campo.css('width', campoData.ancho + 'px');
        } else {
            // Si no se especifica ancho, establecer un mínimo
            $campo.css('min-width', '120px');
        }
        
        if (campoData.alto > 0) {
            $campo.css('height', campoData.alto + 'px');
        } else {
            // Si no se especifica alto, establecer un mínimo
            $campo.css('min-height', '35px');
        }
        
        // Añadir contenido del campo
        $campo.append('<span class="campo-nombre">' + campoData.nombre + '</span>');
        $campo.append('<span class="campo-columna">[' + campoData.columna_sheet + ']</span>');
        
        // Añadir botones de acción con dashicons (mejorado)
        var $actions = $('<div class="campo-actions">');
        $actions.append('<button type="button" class="edit-campo" title="Editar"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>');
        $actions.append('<button type="button" class="delete-campo" title="Eliminar"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>');
        $campo.append($actions);
        
        // Añadir información de posición
        $campo.append('<div class="campo-posicion">X: <span class="pos-x-display">' + campoData.pos_x + '</span>, Y: <span class="pos-y-display">' + campoData.pos_y + '</span></div>');
        
        // Añadir al canvas con un pequeño efecto de aparición
        $campo.css('opacity', 0).appendTo('#certificado-canvas').animate({opacity: 1}, 300);
    }

    /**
     * Abre el modal para editar un campo
     */
    function openEditModal($campo) {
        var campoId = $campo.data('id');
        var campoData = getFieldFromArray(campoId);
        
        if (!campoData) {
            console.error('No se encontró el campo con ID:', campoId);
            return;
        }
        
        console.log('Abriendo modal para editar campo (convertir a enteros):', campoData);
        
        // Aseguramos que todos los valores numéricos sean enteros
        campoData.pos_x = Math.round(campoData.pos_x);
        campoData.pos_y = Math.round(campoData.pos_y);
        campoData.ancho = Math.round(campoData.ancho);
        campoData.alto = Math.round(campoData.alto);
        campoData.tamano_fuente = Math.round(campoData.tamano_fuente);
        
        // Actualizar el array con estos valores redondeados
        updateFieldInArray(campoId, campoData);
        
        // Establecer los valores en el formulario
        $('#campo-id').val(campoData.id);
        $('#campo-nombre').val(campoData.nombre);
        $('#campo-tipo').val(campoData.tipo);
        $('#campo-columna').val(campoData.columna_sheet);
        $('#campo-posx').val(campoData.pos_x);
        $('#campo-posy').val(campoData.pos_y);
        $('#campo-ancho').val(campoData.ancho);
        $('#campo-alto').val(campoData.alto);
        $('#campo-color').val(campoData.color);
        $('#campo-tamano').val(campoData.tamano_fuente);
        $('#campo-alineacion').val(campoData.alineacion);
        
        // Tipografía (si existe el campo)
        if ($('#campo-tipografia').length && campoData.tipografia) {
            $('#campo-tipografia').val(campoData.tipografia);
        }
        
        // Actualizar el selector de color
        if ($.fn.wpColorPicker) {
            $('#campo-color').wpColorPicker('color', campoData.color);
        }
        
        $('#campo-modal-title').text(certificados_pdf_vars.i18n.edit_field || 'Editar Campo');
        $('#campo-modal').show();
    }

    /**
     * Actualiza el nivel de zoom
     */
    function updateZoom(newZoom) {
        zoomLevel = Math.max(0.1, Math.min(2.0, newZoom));
        $('#certificado-canvas').css('transform', 'scale(' + zoomLevel + ')').data('zoom', zoomLevel);
        $('#zoom-reset').text(Math.round(zoomLevel * 100) + '%');
    }


    /**
     * Inicializa el formulario de guardado
     */
    function initSaveForm() {
        $('#certificado-form').on('submit', function(e) {
            e.preventDefault();
            
            // Verificar que hay al menos un campo
            if (camposArray.length === 0) {
                alert(certificados_pdf_vars.i18n.add_at_least_one_field || 'Debe añadir al menos un campo al certificado.');
                return false;
            }
            
            // Añadir el array de campos como JSON
            var camposJSON = JSON.stringify(camposArray);
            
            // Eliminar campo oculto anterior si existe
            $('input[name="campos"]').remove();
            
            // Añadir campo oculto con los datos
            $(this).append('<input type="hidden" name="campos" value=\'' + camposJSON + '\'>');
            
            // Submit form via AJAX
            var formData = $(this).serialize();
            var $submitBtn = $('#guardar-certificado');
            var originalText = $submitBtn.text();
            var isNewCertificate = !$('#certificado-form input[name="id"]').val() || $('#certificado-form input[name="id"]').val() === '0';
            
            $submitBtn.prop('disabled', true).text(certificados_pdf_vars.i18n.saving || 'Guardando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    // Verificar si la respuesta es un objeto JSON válido
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch(e) {
                            console.error('Error al parsear respuesta JSON:', e);
                        }
                    }
                    
                    if (response && response.success) {
                        // Mostrar mensaje de éxito
                        alert(response.data.message || 'Certificado guardado correctamente');
                        
                        // Si es un nuevo certificado, manejar la redirección
                        if (isNewCertificate && response.data && response.data.id) {
                            // Obtener el ID del certificado recién creado
                            var certificadoId = response.data.id;
                            
                            // Usar window.location.href en lugar de window.location para más control
                            var baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
                            var redirectUrl = baseUrl + '?page=certificados_pdf_editar&id=' + certificadoId;
                            
                            console.log('Redirigiendo a:', redirectUrl);
                            
                            // Usar setTimeout para asegurar que el alert se cierre antes de la redirección
                            setTimeout(function() {
                                // Modificar la URL directamente en lugar de usar window.location.href
                                window.location.replace(redirectUrl);
                                
                                // Añadir un fallback por si location.replace no funciona
                                setTimeout(function() {
                                    window.location = redirectUrl;
                                }, 1000);
                            }, 500);
                        } else {
                            // Si es una actualización, simplemente habilitar el botón de nuevo
                            $submitBtn.prop('disabled', false).text(originalText);
                        }
                    } else {
                        // Manejar error
                        var errorMsg = response && response.data && response.data.message 
                            ? response.data.message 
                            : 'Error al guardar el certificado';
                        
                        alert(errorMsg);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {xhr: xhr, status: status, error: error});
                    
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        alert(errorResponse.data && errorResponse.data.message 
                            ? errorResponse.data.message 
                            : 'Error al guardar el certificado: ' + error);
                    } catch(e) {
                        // Si la respuesta no es JSON válido
                        alert('Error de conexión AJAX: ' + error);
                    }
                    
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
            
            return false;
        });
    }

    /**
     * Funciones de utilidad para manejar el array de campos
     */
    function getFieldFromArray(id) {
        id = parseInt(id);
        
        console.log('Buscando campo con ID:', id, 'en array:', camposArray);
        
        for (var i = 0; i < camposArray.length; i++) {
            if (camposArray[i].id === id) {
                return camposArray[i];
            }
        }
        
        console.error('No se encontró campo con ID:', id);
        return null;
    }

    /**
     * Actualiza un campo en el array
     */
    function updateFieldInArray(id, data) {
        id = parseInt(id);
        
        for (var i = 0; i < camposArray.length; i++) {
            if (camposArray[i].id === id) {
                var updatedField = $.extend({}, camposArray[i], data);
                camposArray[i] = updatedField;
                console.log('Campo actualizado:', updatedField);
                return true;
            }
        }
        
        console.error('No se pudo actualizar campo con ID:', id);
        return false;
    }
        

    
    function removeFieldFromArray(id) {
        id = parseInt(id);
        for (var i = 0; i < camposArray.length; i++) {
            if (camposArray[i].id === id) {
                camposArray.splice(i, 1);
                return true;
            }
        }
        return false;
    }


    /**
     * Inicializa la funcionalidad de probar conexión con Google Sheets
     */
    function initProbarConexion() {
        console.log('Inicializando probar conexión...');
        
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
                        // Mostrar resultado exitoso
                        $resultado.html('<span class="success">¡Conexión exitosa! Columnas encontradas: ' + response.data.columnas.join(', ') + '</span>');
                        
                        // Actualizar los selectores de columnas
                        updateColumnasSelectors(response.data.columnas);
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
     * Crea un select list con las columnas encontradas en Google Sheets
     */
    function createColumnasSelect(columnas) {
        // Crear el contenedor para el select si no existe
        if ($('#campo_busqueda_container').length === 0) {
            var $container = $('<div id="campo_busqueda_container" class="campo-busqueda-select"></div>');
            $('#campo_busqueda').closest('tr').find('td').append($container);
        }
        
        var $container = $('#campo_busqueda_container');
        
        // Crear el select con las columnas
        var $select = $('<select id="campo_busqueda_select"></select>');
        
        // Añadir opción por defecto
        $select.append('<option value="">' + (certificados_pdf_vars.i18n.select_column || '-- Seleccione una columna --') + '</option>');
        
        // Añadir las columnas como opciones
        $.each(columnas, function(index, columna) {
            $select.append('<option value="' + columna + '">' + columna + '</option>');
        });
        
        // Seleccionar la columna actual si existe
        var currentValue = $('#campo_busqueda').val();
        if (currentValue) {
            $select.val(currentValue);
        }
        
        // Evento change para actualizar el campo de búsqueda
        $select.on('change', function() {
            var selectedColumn = $(this).val();
            if (selectedColumn) {
                $('#campo_busqueda').val(selectedColumn);
                // Opcional: Mostrar un mensaje de confirmación
                $container.append('<p class="description success-message">' + 
                    (certificados_pdf_vars.i18n.search_field_updated || 'Campo de búsqueda actualizado a: ') + 
                    selectedColumn + '</p>');
                
                // Eliminar el mensaje después de unos segundos
                setTimeout(function() {
                    $container.find('.success-message').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
        
        // Reemplazar el select existente o añadir uno nuevo
        $container.html($select);
    }



    /**
     * Inicializa el selector de plantilla de imagen
     */
    function initPlantillaSelector() {
        console.log('Inicializando selector de plantilla...');
        
        // Comprobar si estamos en la página de edición de certificados y wp.media está disponible
        if ($('#upload-btn').length && typeof wp !== 'undefined' && wp.media) {
            console.log('Selector de medios de WordPress detectado');
            
            // Variable para almacenar el media uploader
            var mediaUploader;
            
            // Manejar el click en el botón de subir plantilla
            $('#upload-btn').on('click', function(e) {
                e.preventDefault();
                console.log('Botón de subida de imagen clickeado');
                
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
                    }
                    
                    // Recalibrar coordenadas después de cargar la imagen
                    // Aumentamos el timeout para dar tiempo a la imagen a cargar completamente
                    setTimeout(function() {
                        updateCanvasDimensions();
                    }, 500);
                });
                
                // Abrir el selector de medios
                mediaUploader.open();
            });
        } else {
            console.error('No se pudo inicializar el selector de plantilla. wp.media no está disponible o no estamos en la página correcta');
        }
    }


    /**
     * Actualiza las dimensiones del canvas basado en la imagen de fondo
     */
    function updateCanvasDimensions() {
        var $canvas = $('#certificado-canvas');
        var $bg = $('.certificate-bg');
        var $container = $('.editor-container');
        
        if ($bg.length) {
            // Esperar a que la imagen cargue completamente
            $bg.on('load', function() {
                var naturalWidth = this.naturalWidth;
                var naturalHeight = this.naturalHeight;
                
                console.log('Dimensiones naturales de la imagen:', naturalWidth, 'x', naturalHeight);
                
                // Establecer el tamaño del canvas al tamaño natural de la imagen
                $canvas.css({
                    'width': naturalWidth + 'px',
                    'height': naturalHeight + 'px',
                    'min-width': naturalWidth + 'px', // Importante para prevenir redimensionamiento
                    'min-height': naturalHeight + 'px'
                });
                
                // Asegurarse de que la imagen también tenga el tamaño correcto
                $bg.css({
                    'width': naturalWidth + 'px',
                    'height': naturalHeight + 'px',
                    'max-width': 'none', // Importante para prevenir redimensionamiento responsivo
                    'display': 'block'
                });

                // Establecer el tamaño mínimo del contenedor para asegurar scroll adecuado
                $container.css({
                    'min-width': '100%',
                    'min-height': '500px'
                });
                
                console.log('Canvas ajustado a las dimensiones naturales de la imagen');
                
                // Ajustar el zoom inicial para que se vea bien en la pantalla
                var containerWidth = $container.width();
                if (naturalWidth > containerWidth) {
                    var initialZoom = Math.min(0.9, (containerWidth - 40) / naturalWidth);
                    updateZoom(initialZoom);
                    console.log('Zoom inicial ajustado a:', initialZoom);
                }

                // Actualizar la cuadrícula para ajustarse al nuevo tamaño
                updateGrid();
                
                // Si hay guías de medida, actualizarlas también
                if ($('#toggle-guias').is(':checked')) {
                    toggleGuias(true);
                }
            });
            
            // Disparar el evento load si la imagen ya está cargada
            if ($bg[0].complete) {
                $bg.trigger('load');
            }
        }
    }


    /**
     * Actualiza el selector de columnas con las columnas encontradas
     * @param {Array} columnas - Array de nombres de columnas
     */
    function updateColumnasSelectors(columnas) {
        // Actualizar el selector en el campo de búsqueda
        if ($('#campo_busqueda_container').length === 0) {
            var $container = $('<div id="campo_busqueda_container" class="campo-busqueda-select"></div>');
            $('#campo_busqueda').closest('tr').find('td').append($container);
        }
        
        var $container = $('#campo_busqueda_container');
        var $select = $('<select id="campo_busqueda_select" class="campo-select"></select>');
        
        // Añadir opción por defecto
        $select.append('<option value="">' + (certificados_pdf_vars.i18n.select_column || '-- Seleccione una columna --') + '</option>');
        
        // Añadir las columnas como opciones
        $.each(columnas, function(index, columna) {
            $select.append('<option value="' + columna + '">' + columna + '</option>');
        });
        
        // Seleccionar la columna actual si existe
        var currentValue = $('#campo_busqueda').val();
        if (currentValue) {
            $select.val(currentValue);
        }
        
        // Evento change para actualizar el campo de búsqueda
        $select.on('change', function() {
            var selectedColumn = $(this).val();
            if (selectedColumn) {
                $('#campo_busqueda').val(selectedColumn);
                // Opcional: Mostrar un mensaje de confirmación
                $container.append('<p class="description success-message">' + 
                    (certificados_pdf_vars.i18n.search_field_updated || 'Campo de búsqueda actualizado a: ') + 
                    selectedColumn + '</p>');
                
                // Eliminar el mensaje después de unos segundos
                setTimeout(function() {
                    $container.find('.success-message').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
        
        // Reemplazar el select existente o añadir uno nuevo
        $container.html($select);
        
        // También actualizar el selector de columnas en el modal de campo
        var $modalSelect = $('#campo-columna');
        $modalSelect.empty();
        $modalSelect.append('<option value="">' + (certificados_pdf_vars.i18n.select_column || '-- Seleccione una columna --') + '</option>');
        
        // Añadir las columnas como opciones
        $.each(columnas, function(index, columna) {
            $modalSelect.append('<option value="' + columna + '">' + columna + '</option>');
        });
        
        // Guardar las columnas para usarlas más tarde
        window.columnasSheet = columnas;
    }


    /**
     * Comprueba si ya hay columnas guardadas en el servidor
     */
    function checkColumnas() {
        var sheetId = $('#sheet_id').val();
        var sheetNombre = $('#sheet_nombre').val();
        
        if (!sheetId || !sheetNombre) return;
        
        // Intentar cargar las columnas del servidor
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'obtener_columnas_sheet',
                sheet_id: sheetId,
                sheet_nombre: sheetNombre,
                nonce: certificados_pdf_vars.nonce
            },
            success: function(response) {
                if (response.success && response.data.columnas && response.data.columnas.length > 0) {
                    updateColumnasSelectors(response.data.columnas);
                }
            }
        });
    }




    /**
     * Inicializa los botones para copiar shortcodes
     */
    function initCopyShortcode() {
        $('.copy-shortcode').on('click', function() {
            var shortcode = $(this).data('shortcode');
            
            // Función para copiar al portapapeles usando la API moderna
            function copyToClipboard(text) {
                // Comprobamos si la API Clipboard está disponible
                if (navigator.clipboard && window.isSecureContext) {
                    // Usar la API moderna
                    navigator.clipboard.writeText(text)
                        .then(() => {
                            showSuccess();
                        })
                        .catch((err) => {
                            console.error('Error al copiar: ', err);
                            // Fallback al método antiguo si falla
                            legacyCopy(text);
                        });
                } else {
                    // Usar el método tradicional para navegadores más antiguos
                    legacyCopy(text);
                }
            }
            
            // Método de respaldo para navegadores antiguos
            function legacyCopy(text) {
                var textArea = document.createElement("textarea");
                textArea.value = text;
                
                // Hacer el textarea no visible
                textArea.style.position = "fixed";
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.width = "2em";
                textArea.style.height = "2em";
                textArea.style.padding = "0";
                textArea.style.border = "none";
                textArea.style.outline = "none";
                textArea.style.boxShadow = "none";
                textArea.style.background = "transparent";
                
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    var successful = document.execCommand('copy');
                    if (successful) {
                        showSuccess();
                    } else {
                        console.error('El comando de copiar no fue exitoso');
                    }
                } catch (err) {
                    console.error('Error al intentar copiar', err);
                }
                
                document.body.removeChild(textArea);
            }
            
            // Mostrar indicador de éxito
            function showSuccess() {
                var $button = $(this);
                $button.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
                
                // Mostrar tooltip de éxito
                var $tooltip = $('<div class="copy-tooltip">¡Copiado!</div>');
                $button.append($tooltip);
                
                // Eliminar clases y tooltip después de un tiempo
                setTimeout(function() {
                    $button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                    $tooltip.fadeOut(200, function() {
                        $tooltip.remove();
                    });
                }, 2000);
            }
            
            // Ejecutar la copia
            copyToClipboard(shortcode);
            
            // Cambiar el icono para indicar éxito
            var $this = $(this);
            $this.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
            
            // Mostrar tooltip de éxito
            var $tooltip = $('<div class="copy-tooltip">¡Copiado!</div>');
            $this.append($tooltip);
            
            // Eliminar clases y tooltip después de un tiempo
            setTimeout(function() {
                $this.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                $tooltip.fadeOut(200, function() {
                    $tooltip.remove();
                });
            }, 2000);
        });
    }


    /**
     * Muestra u oculta las guías de medida
     * @param {boolean} show - Indica si se deben mostrar u ocultar las guías
     */
    function toggleGuias(show) {
        if (show) {
            // Crear o mostrar las guías
            if ($('.certificado-guias').length === 0) {
                $('#certificado-canvas').append('<div class="certificado-guias"></div>');
                
                // Crear líneas horizontales cada 100px
                var height = $('#certificado-canvas').height();
                for (var i = 100; i < height; i += 100) {
                    $('.certificado-guias').append('<div class="guia-h" style="width:100%; top:' + i + 'px"><div class="guia-label">' + i + 'px</div></div>');
                }
                
                // Crear líneas verticales cada 100px
                var width = $('#certificado-canvas').width();
                for (var j = 100; j < width; j += 100) {
                    $('.certificado-guias').append('<div class="guia-v" style="height:100%; left:' + j + 'px"><div class="guia-label">' + j + 'px</div></div>');
                }
            } else {
                $('.certificado-guias').show();
            }
        } else {
            // Ocultar las guías
            $('.certificado-guias').hide();
        }
    }

    /**
     * Actualiza la cuadrícula basada en el tamaño actual del canvas y el gridSize
     */
    function updateGrid() {
        var $canvas = $('#certificado-canvas');
        var $grid = $('.certificado-grid');
        
        if ($grid.length === 0) {
            $grid = $('<div class="certificado-grid"></div>');
            $canvas.prepend($grid);
        }
        
        var canvasWidth = $canvas.width();
        var canvasHeight = $canvas.height();
        
        // Crear el CSS para la cuadrícula con líneas de gridSize x gridSize
        var gridCSS = `
            .certificado-grid {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-size: ${gridSize}px ${gridSize}px;
                background-image:
                    linear-gradient(to right, rgba(0, 0, 0, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
                pointer-events: none;
                z-index: 0;
            }
        `;
        
        // Actualizar o crear el estilo de la cuadrícula
        if ($('#grid-style').length) {
            $('#grid-style').html(gridCSS);
        } else {
            $('<style id="grid-style">').html(gridCSS).appendTo('head');
        }
        
        console.log('Cuadrícula actualizada con tamaño de celda:', gridSize, 'px');
    }
        
    // Exportar funciones al ámbito global para que puedan ser utilizadas por otros scripts
    window.saveField = saveField;
    window.createFieldElement = createFieldElement;
    window.openEditModal = openEditModal;
    window.getFieldFromArray = getFieldFromArray;
    window.updateFieldInArray = updateFieldInArray;
    window.removeFieldFromArray = removeFieldFromArray;
    window.camposArray = camposArray;
    window.nextCampoId = nextCampoId;
    window.initDraggable = initDraggable;

    // También puedes crear un objeto para agrupar todas las funciones relacionadas 
    // (esto es opcional, pero organiza mejor el código)
    window.certificadosPdfEditor = {
        saveField: saveField,
        createFieldElement: createFieldElement,
        openEditModal: openEditModal,
        getFieldFromArray: getFieldFromArray,
        updateFieldInArray: updateFieldInArray,
        removeFieldFromArray: removeFieldFromArray,
        toggleGuias: toggleGuias,
        updateZoom: updateZoom,
        camposArray: camposArray,
        nextCampoId: nextCampoId,
        initDraggable: initDraggable
    };

})(jQuery);