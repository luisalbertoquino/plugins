/**
 * JavaScript para el Configurador Visual de Campos
 * Certificados Digitales PRO
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ========================================
        // VARIABLES GLOBALES
        // ========================================
        var $canvasOverlay = $('#canvas-overlay');
        var $canvasContainer = $('#canvas-container');
        var $plantillaImagen = $('#plantilla-imagen');
        var $propertiesPanel = $('#properties-panel');
        var $propertiesEmpty = $('.properties-empty');
        var $propertiesContent = $('.properties-content');
        var campoActual = null;
        var pestanaId = $('#pestana-id').val();
        var camposGuardados = {};
        var baseWidth = 0;
        var baseHeight = 0;
        var currentZoom = 1;

        // Cargar campos guardados
        try {
            var camposData = $('#campos-data').val();
            if (camposData) {
                var camposArray = JSON.parse(camposData);
                camposArray.forEach(function(campo) {
                    camposGuardados[campo.campo_tipo] = campo;
                });
            }
        } catch(e) {
            console.error('Error al cargar campos:', e);
        }

        // ========================================
        // AJUSTAR CANVAS AL TAMAÑO FIJO
        // ========================================
        if ($plantillaImagen.length) {
            // Función para inicializar dimensiones
            // Función para inicializar dimensiones
            function inicializarCanvas() {
                var img = $plantillaImagen[0];
                
                // Obtener dimensiones NATURALES
                var naturalWidth = img.naturalWidth;
                var naturalHeight = img.naturalHeight;
                
                console.log('📐 Dimensiones naturales:', naturalWidth, 'x', naturalHeight);
                
                // Obtener espacio disponible en el contenedor
                var containerMaxWidth = $('.canvas-wrapper').width() - 40; // 40px de padding
                var containerMaxHeight = Math.min(800, window.innerHeight * 0.7); // Máximo 70% de altura de ventana
                
                // Calcular escala para que quepa
                var scaleX = containerMaxWidth / naturalWidth;
                var scaleY = containerMaxHeight / naturalHeight;
                var scale = Math.min(scaleX, scaleY, 1); // No agrandar más del tamaño natural
                
                // Calcular dimensiones finales manteniendo aspect ratio
                baseWidth = Math.round(naturalWidth * scale);
                baseHeight = Math.round(naturalHeight * scale);
                
                // Aplicar dimensiones FIJAS
                $plantillaImagen.css({
                    width: baseWidth + 'px',
                    height: baseHeight + 'px',
                    display: 'block'
                });
                
                $canvasOverlay.css({
                    width: baseWidth + 'px',
                    height: baseHeight + 'px'
                });
                
                $canvasContainer.css({
                    width: baseWidth + 'px',
                    height: baseHeight + 'px',
                    position: 'relative'
                });
                
                console.log('✅ Canvas fijado a:', baseWidth, 'x', baseHeight, 'px');
                console.log('📊 Escala aplicada:', (scale * 100).toFixed(1) + '%');
                console.log('🔒 Zoom del navegador NO afectará estas dimensiones');
            }

            // Inicializar cuando la imagen cargue
            $plantillaImagen.on('load', inicializarCanvas);

            // Si ya está cargada, inicializar inmediatamente
            if ($plantillaImagen[0].complete) {
                inicializarCanvas();
            }
        }

        // ========================================
        // CONTROLES DE ZOOM
        // ========================================
        var $zoomControls = $('<div class="zoom-controls">' +
            '<span style="margin-right: 10px; font-weight: 500;">🔍 Zoom:</span>' +
            '<button type="button" class="btn-zoom" data-zoom="0.5">50%</button>' +
            '<button type="button" class="btn-zoom" data-zoom="0.75">75%</button>' +
            '<button type="button" class="btn-zoom active" data-zoom="1">100%</button>' +
            '<button type="button" class="btn-zoom" data-zoom="1.25">125%</button>' +
            '<button type="button" class="btn-zoom" data-zoom="1.5">150%</button>' +
            '</div>');

        $('.canvas-wrapper').prepend($zoomControls);

        $(document).on('click', '.btn-zoom', function() {
            var zoom = parseFloat($(this).data('zoom'));
            currentZoom = zoom;
            
            // Aplicar zoom SOLO con transform (no cambia el tamaño real)
            $canvasContainer.css({
                'transform': 'scale(' + zoom + ')',
                'transform-origin': 'top left'
            });
            
            // Marcar botón activo
            $('.btn-zoom').removeClass('active');
            $(this).addClass('active');
            
            console.log('🔍 Zoom aplicado:', zoom * 100 + '%');
        });

        // ========================================
        // MODO CALIBRACIÓN
        // ========================================
        var $calibracionBtn = $('<button type="button" class="btn-quick-action"><span class="dashicons dashicons-admin-tools"></span> Modo Calibración</button>');
        $('#btn-calibracion-container').append($calibracionBtn);

        var calibracionActiva = false;

        $calibracionBtn.on('click', function() {
            calibracionActiva = !calibracionActiva;

            if (calibracionActiva) {
                $(this).addClass('active');
                $(this).find('.dashicons').removeClass('dashicons-admin-tools').addClass('dashicons-yes');
                $(this).contents().filter(function() {
                    return this.nodeType === 3;
                }).first().replaceWith(' Calibración ON');
                $canvasOverlay.addClass('calibracion-activa');

                // Agregar grilla
                if (!$('.calibracion-grid').length) {
                    var $grid = $('<div class="calibracion-grid"></div>');
                    for (var i = 0; i <= 100; i += 10) {
                        $grid.append('<div class="grid-line-h" style="top: ' + i + '%;"></div>');
                        $grid.append('<div class="grid-line-v" style="left: ' + i + '%;"></div>');
                    }
                    $canvasOverlay.append($grid);
                }
            } else {
                $(this).removeClass('active');
                $(this).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-admin-tools');
                $(this).contents().filter(function() {
                    return this.nodeType === 3;
                }).first().replaceWith(' Modo Calibración');
                $canvasOverlay.removeClass('calibracion-activa');
                $('.calibracion-grid').remove();
            }
        });

        // ========================================
        // DRAG & DROP DE CAMPOS DISPONIBLES
        // ========================================
        $('.campo-item[draggable="true"]').on('dragstart', function(e) {
            var tipo = $(this).data('tipo');
            e.originalEvent.dataTransfer.setData('tipo', tipo);
            $(this).addClass('dragging');
        });

        $('.campo-item').on('dragend', function() {
            $(this).removeClass('dragging');
        });

        $canvasOverlay.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $canvasOverlay.on('dragleave', function() {
            $(this).removeClass('dragover');
        });

        $canvasOverlay.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            var tipo = e.originalEvent.dataTransfer.getData('tipo');
            if (!tipo) return;

            // Calcular posición relativa
            var containerOffset = $canvasOverlay.offset();
            var containerWidth = baseWidth; // Usar tamaño fijo
            var containerHeight = baseHeight;
            
            var x = e.pageX - containerOffset.left;
            var y = e.pageY - containerOffset.top;

            // Ajustar por zoom actual
            x = x / currentZoom;
            y = y / currentZoom;

            // Convertir a porcentaje
            var leftPercent = (x / containerWidth) * 100;
            var topPercent = (y / containerHeight) * 100;

            // Limitar a bordes
            leftPercent = Math.max(0, Math.min(95, leftPercent));
            topPercent = Math.max(0, Math.min(95, topPercent));

            // Crear campo
            crearCampoEnCanvas(tipo, topPercent, leftPercent);

            // Marcar como usado
            $('.campo-item[data-tipo="' + tipo + '"]')
                .addClass('campo-usado')
                .attr('draggable', 'false');
        });

        // ========================================
        // CREAR CAMPO EN CANVAS
        // ========================================
        function crearCampoEnCanvas(tipo, top, left, campoData) {
            var defaults = {
                font_size: 16,
                color: '#000000',
                font_family: '',
                font_style: 'normal',
                alineacion: 'center'
            };

            var data = campoData || defaults;
            var textoPreview = getTextoPreview(tipo);

            // Mapear font_style a CSS
            var fontWeight = (data.font_style === 'bold' || data.font_style === 'bold-italic') ? 'bold' : 'normal';
            var fontStyle = (data.font_style === 'italic' || data.font_style === 'bold-italic') ? 'italic' : 'normal';

            var $campo = $('<div>', {
                class: 'campo-posicionado',
                'data-tipo': tipo,
                'data-id': data.id || '',
                'data-font-style': data.font_style || 'normal',
                css: {
                    top: top + '%',
                    left: left + '%',
                    fontSize: data.font_size + 'px',
                    color: data.color,
                    fontFamily: data.font_family ? "'" + data.font_family + "'" : 'inherit',
                    fontWeight: fontWeight,
                    fontStyle: fontStyle,
                    textAlign: data.alineacion
                }
            });

            $campo.html(
                '<span class="campo-texto">' + textoPreview + '</span>' +
                '<div class="campo-controls">' +
                '<button class="btn-edit-campo" title="Editar">✏️</button>' +
                '<button class="btn-delete-campo" title="Eliminar">🗑️</button>' +
                '</div>'
            );

            $canvasOverlay.append($campo);
            hacerCampoDraggable($campo);
            return $campo;
        }

        // ========================================
        // HACER CAMPO DRAGGABLE
        // ========================================
        function hacerCampoDraggable($campo) {
            $campo.draggable({
                containment: '#canvas-overlay',
                stop: function(event, ui) {
                    // Usar dimensiones fijas
                    var leftPercent = (ui.position.left / baseWidth) * 100;
                    var topPercent = (ui.position.top / baseHeight) * 100;

                    $(this).css({
                        left: leftPercent + '%',
                        top: topPercent + '%'
                    });

                    if (campoActual && campoActual[0] === this) {
                        $('#prop-top').val(topPercent.toFixed(2));
                        $('#prop-left').val(leftPercent.toFixed(2));
                    }
                }
            });
        }

        $('.campo-posicionado').each(function() {
            hacerCampoDraggable($(this));
        });

        // ========================================
        // SELECCIONAR CAMPO
        // ========================================
        $(document).on('click', '.campo-posicionado', function(e) {
            if ($(e.target).is('button')) return;
            seleccionarCampo($(this));
        });

        function seleccionarCampo($campo) {
            $('.campo-posicionado').removeClass('campo-seleccionado');
            $campo.addClass('campo-seleccionado');
            campoActual = $campo;

            var tipo = $campo.data('tipo');
            var top = parseFloat($campo.css('top')) / baseHeight * 100;
            var left = parseFloat($campo.css('left')) / baseWidth * 100;
            var fontSize = parseInt($campo.css('font-size'));
            var color = rgbToHex($campo.css('color'));
            var fontFamily = $campo.css('font-family').replace(/['"]/g, '').split(',')[0];
            var alineacion = $campo.css('text-align');

            $propertiesEmpty.hide();
            $propertiesContent.show();

            $('#prop-tipo').val(getLabelCampo(tipo));
            $('#prop-top').val(top.toFixed(2));
            $('#prop-left').val(left.toFixed(2));
            $('#prop-font-size').val(fontSize);
            $('#prop-color').val(color);
            $('#prop-font-family').val(fontFamily);
            $('#prop-font-style').val($campo.data('font-style') || 'normal');
            $('#prop-alineacion').val(alineacion);
            $('#campo-actual-id').val($campo.data('id') || '');
            $('#campo-actual-tipo').val(tipo);

            // Mostrar/ocultar propiedades específicas según tipo de campo
            if (tipo === 'qr') {
                // Mostrar tamaño QR
                $('#prop-qr-size-group').show();
                var qrSize = $campo.data('qr-size') || 20;
                $('#prop-qr-size').val(qrSize);
                
                // Ocultar propiedades de texto
                $('#prop-font-size').closest('.property-group').hide();
                $('#prop-color').closest('.property-group').hide();
                $('#prop-font-family').closest('.property-group').hide();
                $('#prop-font-style').closest('.property-group').hide();
                $('#prop-alineacion').closest('.property-group').hide();
            } else {
                // Ocultar tamaño QR
                $('#prop-qr-size-group').hide();

                // Mostrar propiedades de texto
                $('#prop-font-size').closest('.property-group').show();
                $('#prop-color').closest('.property-group').show();
                $('#prop-font-family').closest('.property-group').show();
                $('#prop-font-style').closest('.property-group').show();
                $('#prop-alineacion').closest('.property-group').show();
            }
        }

        // ========================================
        // ACTUALIZAR PROPIEDADES EN TIEMPO REAL
        // ========================================
        $('#prop-top, #prop-left').on('input change', function() {
            if (!campoActual) return;
            campoActual.css({
                top: $('#prop-top').val() + '%',
                left: $('#prop-left').val() + '%'
            });
        });

        $('#prop-font-size').on('input change', function() {
            if (!campoActual) return;
            campoActual.css('font-size', $(this).val() + 'px');
        });

        $('#prop-color').on('input change', function() {
            if (!campoActual) return;
            campoActual.css('color', $(this).val());
        });

        $('#prop-font-family').on('change', function() {
            if (!campoActual) return;
            var fontFamily = $(this).val();
            campoActual.css('font-family', fontFamily ? "'" + fontFamily + "'" : 'inherit');
        });

        $('#prop-font-style').on('change', function() {
            if (!campoActual) return;
            var fontStyle = $(this).val();
            campoActual.data('font-style', fontStyle);

            // Aplicar estilos CSS
            var fontWeight = (fontStyle === 'bold' || fontStyle === 'bold-italic') ? 'bold' : 'normal';
            var fontStyleCSS = (fontStyle === 'italic' || fontStyle === 'bold-italic') ? 'italic' : 'normal';

            campoActual.css({
                'font-weight': fontWeight,
                'font-style': fontStyleCSS
            });
        });

        $('#prop-alineacion').on('change', function() {
            if (!campoActual) return;
            campoActual.css('text-align', $(this).val());
        });

        // Actualizar tamaño QR en tiempo real
        $('#prop-qr-size').on('input change', function() {
            if (!campoActual) return;
            var qrSize = $(this).val();
            campoActual.data('qr-size', qrSize);
            
            // Actualizar visualmente el tamaño del preview
            campoActual.css({
                'width': qrSize * 2 + 'px',  // Escala visual
                'height': qrSize * 2 + 'px'
            });
        });

        // ========================================
        // ELIMINAR CAMPO
        // ========================================
        $(document).on('click', '.btn-delete-campo', function(e) {
            e.stopPropagation();
            var $campo = $(this).closest('.campo-posicionado');
            var tipo = $campo.data('tipo');
            
            if (confirm('¿Estás seguro de eliminar este campo?')) {
                var campoId = $campo.data('id');
                if (campoId) {
                    eliminarCampoBD(campoId);
                }
                $campo.remove();
                $('.campo-item[data-tipo="' + tipo + '"]')
                    .removeClass('campo-usado')
                    .attr('draggable', 'true')
                    .find('.campo-badge').remove();
                $propertiesContent.hide();
                $propertiesEmpty.show();
                campoActual = null;
            }
        });

        function eliminarCampoBD(campoId) {
            $.ajax({
                url: certificadosCamposAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'certificados_eliminar_campo',
                    nonce: certificadosCamposAdmin.nonce,
                    id: campoId
                }
            });
        }

        // ========================================
        // EDITAR CAMPO
        // ========================================
        $(document).on('click', '.btn-edit-campo', function(e) {
            e.stopPropagation();
            seleccionarCampo($(this).closest('.campo-posicionado'));
        });

        // ========================================
        // GUARDAR TODO
        // ========================================
        $('#btn-guardar-todo').on('click', function() {
            var $button = $(this);
            var $icon = $button.find('.dashicons');

            // Guardar el ancho original del botón
            var originalWidth = $button.outerWidth();
            $button.css('min-width', originalWidth + 'px');

            $button.prop('disabled', true);
            $icon.removeClass('dashicons-saved').addClass('dashicons-update dashicons-spin');

            // Cambiar todo el contenido del botón
            $button.html('<span class="dashicons dashicons-update dashicons-spin"></span> Guardando...');

            var campos = [];
            
            $('.campo-posicionado').each(function() {
                var $campo = $(this);
                
                // Usar dimensiones fijas
                var top = (parseFloat($campo.css('top')) / baseHeight) * 100;
                var left = (parseFloat($campo.css('left')) / baseWidth) * 100;
                var fontSize = parseInt($campo.css('font-size'));
                var color = rgbToHex($campo.css('color'));
                var fontFamily = $campo.css('font-family').replace(/['"]/g, '').split(',')[0];
                var alineacion = $campo.css('text-align');

                var campoObj = {
                    id: $campo.data('id') || '',
                    pestana_id: pestanaId,
                    campo_tipo: $campo.data('tipo'),
                    posicion_top: top.toFixed(2),
                    posicion_left: left.toFixed(2),
                    font_size: fontSize,
                    color: color,
                    font_family: fontFamily,
                    font_style: $campo.data('font-style') || 'normal',
                    alineacion: alineacion,
                    plantilla_display_width: baseWidth,
                    plantilla_display_height: baseHeight
                };

                // Si es QR, incluir tamaño
                if ($campo.data('tipo') === 'qr') {
                    campoObj.qr_size = $campo.data('qr-size') || 20;
                }

                campos.push(campoObj);
            });

            if (campos.length === 0) {
                alert('No hay campos para guardar.');
                $button.prop('disabled', false);
                $button.html('<span class="dashicons dashicons-saved"></span> Guardar Cambios');
                $button.css('min-width', '');
                return;
            }

            var promises = [];
            campos.forEach(function(campo) {
                promises.push(guardarCampo(campo));
            });

            Promise.all(promises).then(function() {
                alert('¡Campos guardados correctamente!');
                location.reload();
            }).catch(function(error) {
                alert('Error: ' + error);
                $button.prop('disabled', false);
                $button.html('<span class="dashicons dashicons-saved"></span> Guardar Cambios');
                $button.css('min-width', '');
            });
        });

        function guardarCampo(campo) {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: certificadosCamposAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'certificados_guardar_campos',
                        nonce: certificadosCamposAdmin.nonce,
                        ...campo
                    },
                    success: function(response) {
                        response.success ? resolve(response) : reject(response.data.message);
                    },
                    error: function() {
                        reject('Error de conexión');
                    }
                });
            });
        }

        // ========================================
        // FUNCIONES AUXILIARES
        // ========================================
        function getTextoPreview(tipo) {
            var texts = {
                'nombre': 'Juan Pérez García',
                'documento': 'CC 1234567890 - Bogotá',
                'trabajo': 'Desarrollo de Software con IA',
                'qr': '[QR]',
                'fecha_emision': new Date().toLocaleDateString()
            };
            return texts[tipo] || tipo;
        }

        function getLabelCampo(tipo) {
            var labels = {
                'nombre': 'Nombre',
                'documento': 'Documento',
                'trabajo': 'Trabajo/Título',
                'qr': 'Código QR',
                'fecha_emision': 'Fecha de Emisión'
            };
            return labels[tipo] || tipo;
        }

        function rgbToHex(rgb) {
            if (rgb.indexOf('#') === 0) return rgb;
            var parts = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
            if (!parts) return '#000000';
            return "#" + 
                ("0" + parseInt(parts[1],10).toString(16)).slice(-2) +
                ("0" + parseInt(parts[2],10).toString(16)).slice(-2) +
                ("0" + parseInt(parts[3],10).toString(16)).slice(-2);
        }

    }); // FIN document.ready

})(jQuery);