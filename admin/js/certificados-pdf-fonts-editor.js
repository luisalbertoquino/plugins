/**
 * Integración de fuentes personalizadas en el editor de certificados.
 * plugins-main/admin/js/certificados-pdf-fonts-editor.js
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // Variable para almacenar la información de fuentes disponibles
    var availableFonts = [];
    var fontsLoaded = false;

    /**
     * Inicializa la integración de fuentes con el editor
     */
    function init() {
        console.log('Inicializando integración de fuentes...');
        
        // Al añadir o editar un campo, cargar las fuentes disponibles
        $(document).on('click', '#add-field, .edit-campo', function() {
            loadFonts();
        });
        
        // Cargar fuentes al inicio si estamos en la página del editor
        if ($('#certificado-canvas').length > 0) {
            loadFonts();
        }
        
        // Escuchar cambios en el selector de tipografía
        $(document).on('change', '#campo-tipografia', updateFontPreview);
    }

    /**
     * Carga las fuentes disponibles mediante AJAX
     */
    function loadFonts() {
        // Si ya cargamos las fuentes, no es necesario volver a hacerlo
        if (fontsLoaded) {
            ensureFontSelectorExists();
            return;
        }
        
        // Hacer la solicitud AJAX para obtener las fuentes
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_fonts',
                nonce: certificados_pdf_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('Fuentes cargadas correctamente');
                    
                    // Guardar las fuentes
                    availableFonts = response.data.fonts;
                    
                    // Añadir estilos para las fuentes
                    if (response.data.preview_styles) {
                        $('<style id="font-preview-styles">').text(response.data.preview_styles).appendTo('head');
                    }
                    
                    // Marcar como cargadas
                    fontsLoaded = true;
                    
                    // Asegurarse de que existe el selector
                    ensureFontSelectorExists();
                } else {
                    console.error('Error al cargar fuentes:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al cargar fuentes:', error);
            }
        });
    }

    /**
     * Se asegura de que el selector de tipografía existe en el formulario
     */
    function ensureFontSelectorExists() {
        // Esperar a que el modal esté visible
        var checkModalInterval = setInterval(function() {
            if ($('#campo-modal').is(':visible')) {
                clearInterval(checkModalInterval);
                
                console.log('Modal detectado, verificando selector de tipografía');
                
                // Añadir el selector si no existe
                if ($('#campo-tipografia').length === 0) {
                    addFontSelector();
                } else {
                    // Si ya existe, poblarlo con las fuentes
                    populateFontSelector();
                }
            }
        }, 100);
    }

    /**
     * Añade el selector de tipografía al formulario
     */
    function addFontSelector() {
        console.log('Añadiendo selector de tipografía');
        
        // Crear el contenedor para el selector
        var $tipografiaRow = $(
            '<div class="campo-form-row" id="tipografia-container">' +
                '<label for="campo-tipografia">Tipografía</label>' +
                '<select id="campo-tipografia" name="campo-tipografia">' +
                    '<option value="default">Default (Sistema)</option>' +
                '</select>' +
                '<div class="font-preview-container">' +
                    '<div id="font-preview-sample" class="font-preview-sample">Texto de ejemplo</div>' +
                '</div>' +
                '<p class="description">Selecciona la tipografía para este campo</p>' +
            '</div>'
        );
        
        // Encontrar dónde insertar el selector
        var $alineacionRow = $('.campo-form-row').has('#campo-alineacion');
        
        if ($alineacionRow.length) {
            $alineacionRow.after($tipografiaRow);
            // Poblar con las fuentes disponibles
            populateFontSelector();
        } else {
            console.warn('No se encontró la fila de alineación, buscando alternativa...');
            
            // Alternativa: insertar antes de las acciones
            var $actionsRow = $('.campo-form-actions');
            if ($actionsRow.length) {
                $actionsRow.before($tipografiaRow);
                populateFontSelector();
            } else {
                console.error('No se pudo encontrar un lugar adecuado para insertar el selector de tipografía');
            }
        }
        
        // Añadir estilos CSS para la vista previa
        var styles = 
            '.font-preview-container {' +
            '   margin-top: 10px;' +
            '   padding: 10px;' +
            '   background: #f9f9f9;' +
            '   border: 1px solid #ddd;' +
            '   border-radius: 3px;' +
            '}' +
            '.font-preview-sample {' +
            '   font-size: 16px;' +
            '   line-height: 1.4;' +
            '   min-height: 30px;' +
            '}';
        
        // Añadir estilos solo si no existen ya
        if ($('#font-preview-container-styles').length === 0) {
            $('<style id="font-preview-container-styles">').text(styles).appendTo('head');
        }
    }

    /**
     * Puebla el selector de tipografía con las fuentes disponibles
     */

    function populateFontSelector() {
        var $select = $('#campo-tipografia');
        
        if (!$select.length || !fontsLoaded) {
            console.warn('No se puede poblar el selector: no existe o las fuentes no están cargadas');
            return;
        }
        
        // Limpiar el selector completamente
        $select.empty();
        
        // Añadir opción por defecto
        $select.append('<option value="default">Default (Sistema)</option>');
        
        // Añadir fuentes del sistema de forma ordenada (una sola vez)
        $select.append('<optgroup label="Fuentes del Sistema">');
        $select.append('<option value="Arial">Arial</option>');
        $select.append('<option value="Helvetica">Helvetica</option>');
        $select.append('<option value="Times New Roman">Times New Roman</option>');
        $select.append('<option value="Georgia">Georgia</option>');
        $select.append('<option value="Courier New">Courier New</option>');
        $select.append('<option value="Verdana">Verdana</option>');
        $select.append('<option value="Tahoma">Tahoma</option>');
        $select.append('</optgroup>');
        
        // Añadir fuentes personalizadas si existen (una sola vez)
        if (availableFonts && availableFonts.length > 0) {
            // Filtrar fuentes que no son del sistema (ya añadidas arriba)
            var systemFonts = ['Arial', 'Helvetica', 'Times New Roman', 'Georgia', 'Courier New', 'Verdana', 'Tahoma', 'Default', 'default'];
            
            // Filtrar fuentes personalizadas y eliminar duplicados
            var customFonts = availableFonts.filter(function(font) {
                return systemFonts.indexOf(font) === -1;
            });
            
            // Eliminar duplicados si los hay
            customFonts = [...new Set(customFonts)];
            
            if (customFonts.length > 0) {
                $select.append('<optgroup label="Fuentes Personalizadas">');
                customFonts.forEach(function(font) {
                    $select.append('<option value="' + font + '">' + font + '</option>');
                });
                $select.append('</optgroup>');
            }
        }
        
        // Seleccionar la fuente actual si estamos editando
        var currentFont = getCurrentFieldTipografia();
        if (currentFont) {
            $select.val(currentFont);
        }
        
        // Actualizar la vista previa
        updateFontPreview();
        
        console.log('Selector de tipografía poblado correctamente');
    }

    /**
     * Obtiene la tipografía del campo actual (si estamos editando)
     */
    function getCurrentFieldTipografia() {
        var campoId = $('#campo-id').val();
        
        if (!campoId) {
            return null; // No estamos editando
        }
        
        // Buscar el campo en el canvas
        var $campo = $('#campo-item-' + campoId);
        
        if ($campo.length) {
            return $campo.attr('data-tipografia') || 'default';
        }
        
        return null;
    }

    /**
     * Actualiza la vista previa de la fuente
     */
    function updateFontPreview() {
        var selectedFont = $('#campo-tipografia').val();
        var $preview = $('#font-preview-sample');
        
        if (!$preview.length) {
            return;
        }
        
        // Limpiar estilos anteriores
        $preview.attr('style', '');
        
        if (selectedFont === 'default') {
            $preview.css('font-family', 'inherit');
        } else {
            // Aplicar la fuente seleccionada
            switch (selectedFont) {
                case 'Arial':
                    $preview.css('font-family', 'Arial, sans-serif');
                    break;
                case 'Helvetica':
                    $preview.css('font-family', 'Helvetica, Arial, sans-serif');
                    break;
                case 'Times New Roman':
                    $preview.css('font-family', '"Times New Roman", serif');
                    break;
                case 'Georgia':
                    $preview.css('font-family', 'Georgia, serif');
                    break;
                case 'Courier New':
                    $preview.css('font-family', '"Courier New", monospace');
                    break;
                case 'Verdana':
                    $preview.css('font-family', 'Verdana, sans-serif');
                    break;
                case 'Tahoma':
                    $preview.css('font-family', 'Tahoma, sans-serif');
                    break;
                default:
                    // Para fuentes personalizadas
                    $preview.css('font-family', '"' + selectedFont + '", sans-serif');
                    break;
            }
        }
        
        // Actualizar el contenido
        var fontSize = $('#campo-tamano').val() || 16;
        var sampleText = 'Texto de ejemplo (ABCabc123)';
        
        $preview.text(sampleText).css('font-size', fontSize + 'px');
        
        // Añadir tamaño de fuente
        $preview.css('font-size', fontSize + 'px');
        
        // Añadir color si está disponible
        var color = $('#campo-color').val();
        if (color) {
            $preview.css('color', color);
        }
        
        // Alineación
        var alineacion = $('#campo-alineacion').val();
        if (alineacion) {
            $preview.css('text-align', alineacion);
        }
    }

    /**
     * Modifica las funciones existentes para incluir la tipografía
     */
    function patchExistingFunctions() {
        console.log('Modificando funciones existentes para soportar tipografías...');
        
        // 1. Modificar saveField
        var originalSaveField = window.saveField;
        if (typeof originalSaveField === 'function') {
            window.saveField = function() {
                var campoId = $('#campo-id').val();
                var isEditing = campoId !== '';
                
                var campoData = {
                    id: isEditing ? parseInt(campoId) : window.nextCampoId++,
                    nombre: $('#campo-nombre').val(),
                    tipo: $('#campo-tipo').val(),
                    columna_sheet: $('#campo-columna').val(),
                    pos_x: parseInt($('#campo-posx').val()),
                    pos_y: parseInt($('#campo-posy').val()),
                    ancho: parseInt($('#campo-ancho').val()),
                    alto: parseInt($('#campo-alto').val()),
                    color: $('#campo-color').val(),
                    tamano_fuente: parseInt($('#campo-tamano').val()),
                    alineacion: $('#campo-alineacion').val(),
                    tipografia: (function() {
                        var tipografiaValue = $('#campo-tipografia').val();
                        console.log('Valor de tipografía seleccionada:', tipografiaValue);
                        return tipografiaValue || 'default';
                    })()
                };
                
                console.log('Guardando campo con tipografía:', campoData.tipografia);
                console.log('Guardando campo con tipografía:', campoData.tipografia);
                console.log('Tamaño de fuente:', campoData.tamano_fuente)
                
                // Validar campos requeridos
                if (!campoData.nombre || !campoData.columna_sheet) {
                    alert('Por favor, complete todos los campos requeridos.');
                    return;
                }
                
                if (isEditing) {
                    // Actualizar campo existente
                    window.updateFieldInArray(campoData.id, campoData);
                    $('#campo-item-' + campoData.id).remove();
                } else {
                    // Añadir nuevo campo al array
                    window.camposArray.push(campoData);
                }
                
                // Crear/actualizar elemento visual
                window.createFieldElement(campoData);
                
                // Cerrar modal
                $('#campo-modal').hide();
                
                // Reinicializar draggable
                window.initDraggable();
            };
            
            console.log('Función saveField modificada');
        } else {
            console.error('La función saveField no está definida');
        }
        
        // 2. Modificar createFieldElement
        var originalCreateFieldElement = window.createFieldElement;
        if (typeof originalCreateFieldElement === 'function') {
            window.createFieldElement = function(campoData) {
                console.log('Creando elemento con tipografía:', campoData.tipografia);
                
                var $campo = $('<div>')
                    .addClass('campo-item')
                    .attr('id', 'campo-item-' + campoData.id)
                    .attr('data-id', campoData.id)
                    .attr('data-nombre', campoData.nombre)
                    .attr('data-tipo', campoData.tipo)
                    .attr('data-columna', campoData.columna_sheet)
                    .attr('data-posx', campoData.pos_x)
                    .attr('data-posy', campoData.pos_y)
                    .attr('data-ancho', campoData.ancho)
                    .attr('data-alto', campoData.alto)
                    .attr('data-color', campoData.color)
                    .attr('data-tamano', campoData.tamano_fuente)
                    .attr('data-alineacion', campoData.alineacion)
                    .attr('data-tipografia', campoData.tipografia || 'default')
                    .css({
                        'left': campoData.pos_x + 'px',
                        'top': campoData.pos_y + 'px',
                        'color': campoData.color,
                        'font-size': Math.max(14, campoData.tamano_fuente) + 'px',
                        'text-align': campoData.alineacion
                    });
                
                // Aplicar tipografía
                if (campoData.tipografia && campoData.tipografia !== 'default') {
                    applyFontFamily($campo, campoData.tipografia);
                }
                
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
                
                // Añadir botones de acción con dashicons
                var $actions = $('<div class="campo-actions">');
                $actions.append('<button type="button" class="edit-campo" title="Editar"><span class="dashicons dashicons-edit" aria-hidden="true"></span></button>');
                $actions.append('<button type="button" class="delete-campo" title="Eliminar"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>');
                $campo.append($actions);
                
                // Añadir información de posición
                $campo.append('<div class="campo-posicion">X: <span class="pos-x-display">' + campoData.pos_x + '</span>, Y: <span class="pos-y-display">' + campoData.pos_y + '</span></div>');
                
                // Añadir al canvas con un pequeño efecto de aparición
                $campo.css('opacity', 0).appendTo('#certificado-canvas').animate({opacity: 1}, 300);
            };
            
            console.log('Función createFieldElement modificada');
        } else {
            console.error('La función createFieldElement no está definida');
        }
        
        // 3. Modificar openEditModal
        var originalOpenEditModal = window.openEditModal;
        if (typeof originalOpenEditModal === 'function') {
            window.openEditModal = function($campo) {
                var campoId = $campo.data('id');
                var campoData = window.getFieldFromArray(campoId);
                
                if (!campoData) return;
                
                console.log('Abriendo modal para editar campo con tipografía:', campoData.tipografia);
                
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
                
                // Asegurarse de que el selector existe
                ensureFontSelectorExists();
                
                // Esperar un momento para que el selector esté listo
                setTimeout(function() {
                    // Establecer la tipografía
                    if ($('#campo-tipografia').length) {
                        $('#campo-tipografia').val(campoData.tipografia || 'default');
                        // Actualizar la vista previa
                        updateFontPreview();
                    }
                }, 200);
                
                // Actualizar el selector de color
                if ($.fn.wpColorPicker) {
                    $('#campo-color').wpColorPicker('color', campoData.color);
                    
                    // Añadir evento para actualizar la vista previa cuando cambia el color
                    $('#campo-color').wpColorPicker('option', 'change', function() {
                        updateFontPreview();
                    });
                }
                
                $('#campo-modal-title').text('Editar Campo');
                $('#campo-modal').show();
            };
            
            console.log('Función openEditModal modificada');
        } else {
            console.error('La función openEditModal no está definida');
        }
    }

    /**
     * Aplica la familia de fuentes a un elemento según la fuente seleccionada
     */
    function applyFontFamily($element, fontName) {
        switch (fontName) {
            case 'Arial':
                $element.css('font-family', 'Arial, sans-serif');
                break;
            case 'Helvetica':
                $element.css('font-family', 'Helvetica, Arial, sans-serif');
                break;
            case 'Times New Roman':
                $element.css('font-family', '"Times New Roman", serif');
                break;
            case 'Georgia':
                $element.css('font-family', 'Georgia, serif');
                break;
            case 'Courier New':
                $element.css('font-family', '"Courier New", monospace');
                break;
            case 'Verdana':
                $element.css('font-family', 'Verdana, sans-serif');
                break;
            case 'Tahoma':
                $element.css('font-family', 'Tahoma, sans-serif');
                break;
            default:
                // Para fuentes personalizadas
                $element.css('font-family', '"' + fontName + '", sans-serif');
                break;
        }
    }

    /**
     * Vincula eventos para actualizar la vista previa
     */
    function bindPreviewEvents() {
        // Actualizar la vista previa cuando cambia el tamaño de fuente
        $(document).on('input change', '#campo-tamano', updateFontPreview);
        
        // Actualizar la vista previa cuando cambia la alineación
        $(document).on('change', '#campo-alineacion', updateFontPreview);
        
        // Actualizar la vista previa cuando cambia el color (se maneja en openEditModal)
    }

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        init();
        patchExistingFunctions();
        bindPreviewEvents();
        
        // Comportamiento adicional cuando se abre el modal
        $(document).on('click', '#add-field, .edit-campo', function() {
            console.log('Campo clickeado, cargando fuentes...');
            
            // Usar setTimeout para asegurar que el proceso se ejecute después de que aparezca el modal
            setTimeout(function() {
                ensureFontSelectorExists();
            }, 300);
        });
        
        console.log('Integración de fuentes inicializada');
    });

})(jQuery);