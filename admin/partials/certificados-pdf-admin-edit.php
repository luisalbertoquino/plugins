<?php
/**
 * Vista para editar o crear certificados.
 * plugins-main/admin/partials/certificados-pdf-admin-edit.php
 * @since      1.0.0
 */
?>

<script type="text/javascript">
/* <![CDATA[ */
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
/* ]]> */
</script>
<div class="wrap certificados-pdf-admin certificados-pdf-edit">
    <h1 class="wp-heading-inline">
        <?php echo $certificado ? __('Editar Certificado', 'certificados-pdf') : __('Añadir Nuevo Certificado', 'certificados-pdf'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=certificados_pdf'); ?>" class="page-title-action"><?php _e('Volver a la lista', 'certificados-pdf'); ?></a>
    <hr class="wp-header-end">
    
    <form id="certificado-form" method="post">
        <input type="hidden" name="action" value="guardar_certificado">
        <input type="hidden" name="id" value="<?php echo $certificado ? $certificado->id : 0; ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('certificados_pdf_nonce'); ?>">
        
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- 1. Información del Certificado -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Información del Certificado', 'certificados-pdf'); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nombre"><?php _e('Nombre del Certificado', 'certificados-pdf'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="nombre" name="nombre" class="regular-text" value="<?php echo $certificado ? esc_attr($certificado->nombre) : ''; ?>" required>
                                        <p class="description"><?php _e('Nombre descriptivo para identificar este certificado', 'certificados-pdf'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="plantilla_url"><?php _e('Plantilla del Certificado', 'certificados-pdf'); ?></label>
                                    </th>
                                    <td>
                                        <div class="plantilla-preview-container">
                                            <div id="plantilla-preview" class="plantilla-preview">
                                                <?php if ($certificado && !empty($certificado->plantilla_url)): ?>
                                                    <img src="<?php echo esc_url($certificado->plantilla_url); ?>" alt="Plantilla">
                                                <?php else: ?>
                                                    <div class="no-image"><?php _e('No hay imagen seleccionada', 'certificados-pdf'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" id="plantilla_url" name="plantilla_url" value="<?php echo $certificado ? esc_attr($certificado->plantilla_url) : ''; ?>">
                                        <input type="button" id="upload-btn" class="button" value="<?php _e('Seleccionar Imagen', 'certificados-pdf'); ?>">
                                        <p class="description"><?php _e('Sube o selecciona la imagen de fondo para el certificado (PNG, JPG, PDF, etc.)', 'certificados-pdf'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- 2. Conexión con Google Sheets -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Conexión con Google Sheets', 'certificados-pdf'); ?></span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="sheet_id"><?php _e('ID de Google Sheet', 'certificados-pdf'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="sheet_id" name="sheet_id" class="regular-text" value="<?php echo $certificado ? esc_attr($certificado->sheet_id) : ''; ?>" required>
                                        <p class="description"><?php _e('ID de la hoja de cálculo (encontrado en la URL: https://docs.google.com/spreadsheets/d/[ID]/edit)', 'certificados-pdf'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="sheet_nombre"><?php _e('Nombre de la Hoja', 'certificados-pdf'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="sheet_nombre" name="sheet_nombre" class="regular-text" value="<?php echo $certificado ? esc_attr($certificado->sheet_nombre) : 'Hoja1'; ?>">
                                        <p class="description"><?php _e('Nombre de la pestaña dentro del documento de Google Sheets (por defecto: Hoja1)', 'certificados-pdf'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="campo_busqueda"><?php _e('Campo de Búsqueda', 'certificados-pdf'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="campo_busqueda" name="campo_busqueda" class="regular-text" value="<?php echo $certificado ? esc_attr($certificado->campo_busqueda) : 'documento'; ?>">
                                        <p class="description"><?php _e('Campo por el cual se buscará en el formulario (ej. documento, email, etc.)', 'certificados-pdf'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"></th>
                                    <td>
                                        <button type="button" id="probar-conexion" class="button button-secondary">
                                            <?php _e('Probar Conexión', 'certificados-pdf'); ?>
                                        </button>
                                        <span id="conexion-resultado"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 3. Editor de Campos -->
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Campos del Certificado', 'certificados-pdf'); ?></span></h2>
                        <div class="inside">
                            <div id="certificado-editor">
                                <div class="editor-tools">
                                    <button type="button" id="add-field" class="button button-primary">
                                        <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                                        <?php _e('Añadir Campo', 'certificados-pdf'); ?>
                                    </button>
                                    
                                    <!-- Controles de zoom -->
                                    <div class="zoom-controls">
                                        <span><?php _e('Zoom:', 'certificados-pdf'); ?></span>
                                        <button id="zoom-out" type="button" class="button">
                                            <span class="dashicons dashicons-minus" aria-hidden="true"></span>
                                        </button>
                                        <button id="zoom-reset" type="button" class="button">100%</button>
                                        <button id="zoom-in" type="button" class="button">
                                            <span class="dashicons dashicons-plus" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                    
                                    <!-- Controles de cuadrícula -->
                                    <div class="grid-controls">
                                        <label>
                                            <input type="checkbox" id="toggle-grid" checked>
                                            <?php _e('Mostrar cuadrícula', 'certificados-pdf'); ?>
                                        </label>
                                        <label>
                                            <input type="checkbox" id="toggle-guias">
                                            <?php _e('Mostrar guías de medida', 'certificados-pdf'); ?>
                                        </label>
                                        <label>
                                            <input type="checkbox" id="toggle-coords">
                                            <?php _e('Mostrar coordenadas', 'certificados-pdf'); ?>
                                        </label>
                                        <select id="grid-size">
                                            <option value="5">5px</option>
                                            <option value="10" selected>10px</option>
                                            <option value="20">20px</option>
                                            <option value="50">50px</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="editor-container">
                                    <div id="certificado-canvas" class="certificado-canvas" data-zoom="1.0">
                                        <!-- Cuadrícula para ayudar en el posicionamiento -->
                                        <div class="certificado-grid"></div>
                                        
                                        <?php if ($certificado && !empty($certificado->plantilla_url)): ?>
                                            <img src="<?php echo esc_url($certificado->plantilla_url); ?>" alt="Plantilla" class="certificate-bg">
                                        <?php else: ?>
                                            <div class="no-image-large"><?php _e('Sube una imagen de plantilla primero', 'certificados-pdf'); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($campos)): ?>
                                            <?php foreach ($campos as $campo): ?>
                                                <div class="campo-item" 
                                                    data-id="<?php echo $campo->id; ?>"
                                                    data-nombre="<?php echo esc_attr($campo->nombre); ?>"
                                                    data-tipo="<?php echo esc_attr($campo->tipo); ?>"
                                                    data-columna="<?php echo esc_attr($campo->columna_sheet); ?>"
                                                    data-posx="<?php echo esc_attr($campo->pos_x); ?>"
                                                    data-posy="<?php echo esc_attr($campo->pos_y); ?>"
                                                    data-ancho="<?php echo esc_attr($campo->ancho); ?>"
                                                    data-alto="<?php echo esc_attr($campo->alto); ?>"
                                                    data-color="<?php echo esc_attr($campo->color); ?>"
                                                    data-tamano="<?php echo esc_attr($campo->tamano_fuente); ?>"
                                                    data-alineacion="<?php echo esc_attr($campo->alineacion); ?>"
                                                    style="left: <?php echo $campo->pos_x; ?>px; top: <?php echo $campo->pos_y; ?>px; <?php echo ($campo->ancho > 0) ? 'width: ' . $campo->ancho . 'px;' : ''; ?> <?php echo ($campo->alto > 0) ? 'height: ' . $campo->alto . 'px;' : ''; ?> color: <?php echo $campo->color; ?>; font-size: <?php echo $campo->tamano_fuente; ?>px; text-align: <?php echo $campo->alineacion; ?>;">
                                                    <span class="campo-nombre"><?php echo esc_html($campo->nombre); ?></span>
                                                    <span class="campo-columna">[<?php echo esc_html($campo->columna_sheet); ?>]</span>
                                                    <div class="campo-actions">
                                                        <button type="button" class="edit-campo"><span class="dashicons dashicons-edit"></span></button>
                                                        <button type="button" class="delete-campo"><span class="dashicons dashicons-trash"></span></button>
                                                    </div>
                                                    <div class="campo-posicion">X: <span class="pos-x-display"><?php echo $campo->pos_x; ?></span>, Y: <span class="pos-y-display"><?php echo $campo->pos_y; ?></span></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Información de coordenadas -->
                                        <div class="coord-info"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Publicar', 'certificados-pdf'); ?></span></h2>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="minor-publishing">
                                    <div id="misc-publishing-actions">
                                        <div class="misc-pub-section">
                                            <label for="habilitado">
                                                <input type="checkbox" id="habilitado" name="habilitado" value="1" <?php echo ($certificado && $certificado->habilitado) ? 'checked' : ''; ?>>
                                                <?php _e('Habilitar este certificado', 'certificados-pdf'); ?>
                                            </label>
                                        </div>
                                        
                                        <?php if ($certificado): ?>
                                            <div class="misc-pub-section">
                                                <span class="dashicons dashicons-calendar"></span>
                                                <?php _e('Creado: ', 'certificados-pdf'); ?>
                                                <strong><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($certificado->fecha_creacion)); ?></strong>
                                            </div>
                                            <div class="misc-pub-section">
                                                <span class="dashicons dashicons-update"></span>
                                                <?php _e('Última modificación: ', 'certificados-pdf'); ?>
                                                <strong><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($certificado->fecha_modificacion)); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <button type="submit" id="guardar-certificado" class="button button-primary button-large">
                                            <?php echo $certificado ? __('Actualizar', 'certificados-pdf') : __('Publicar', 'certificados-pdf'); ?>
                                        </button>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($certificado): ?>
                        <div class="postbox">
                            <h2 class="hndle"><span><?php _e('Shortcode', 'certificados-pdf'); ?></span></h2>
                            <div class="inside">
                                <p><?php _e('Utiliza este shortcode para mostrar el formulario de búsqueda de certificados:', 'certificados-pdf'); ?></p>
                                <div class="shortcode-container">
                                    <code>[certificado_pdf id="<?php echo $certificado->id; ?>"]</code>
                                    <button type="button" class="button button-small copy-shortcode" data-shortcode='[certificado_pdf id="<?php echo $certificado->id; ?>"]'>
                                        <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
                                        <span class="screen-reader-text">Copiar shortcode</span>
                                    </button>
                                </div>
                                <p class="description"><?php _e('También puedes usar atributos adicionales para personalizar el formulario:', 'certificados-pdf'); ?></p>
                                <code>[certificado_pdf id="<?php echo $certificado->id; ?>" campo="documento" titulo="Buscar Certificado" placeholder="Ingrese su número de identificación" boton="Buscar" class="mi-clase"]</code>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template para el modal de campo -->
<div id="campo-modal" class="campo-modal" style="display: none;">
    <div class="campo-modal-content">
        <span class="campo-modal-close">&times;</span>
        <h2 id="campo-modal-title"><?php _e('Añadir Campo', 'certificados-pdf'); ?></h2>
        
        <form id="campo-form">
            <input type="hidden" id="campo-id" value="">
            
            <div class="campo-form-row">
                <label for="campo-nombre"><?php _e('Nombre del Campo', 'certificados-pdf'); ?></label>
                <input type="text" id="campo-nombre" required>
                <p class="description"><?php _e('Nombre descriptivo para identificar este campo', 'certificados-pdf'); ?></p>
            </div>
            
            <div class="campo-form-row">
                <label for="campo-tipo"><?php _e('Tipo de Campo', 'certificados-pdf'); ?></label>
                <select id="campo-tipo">
                    <option value="texto"><?php _e('Texto', 'certificados-pdf'); ?></option>
                    <option value="fecha"><?php _e('Fecha', 'certificados-pdf'); ?></option>
                    <option value="numero"><?php _e('Número', 'certificados-pdf'); ?></option>
                </select>
            </div>
            
            <div class="campo-form-row">
                <label for="campo-columna"><?php _e('Columna de Google Sheets', 'certificados-pdf'); ?></label>
                <select id="campo-columna" required>
                    <option value=""><?php _e('-- Seleccione una columna --', 'certificados-pdf'); ?></option>
                    <!-- Las opciones se cargarán dinámicamente con JavaScript -->
                </select>
                <p class="description"><?php _e('Seleccione la columna de Google Sheets que contiene los datos', 'certificados-pdf'); ?></p>
            </div>
            
            <div class="campo-form-row campo-form-coords">
                <div class="campo-form-coord">
                    <label for="campo-posx"><?php _e('Posición X', 'certificados-pdf'); ?></label>
                    <input type="number" id="campo-posx" min="0" step="1" value="0" onchange="this.value=Math.round(this.value)">
                </div>
                <div class="campo-form-coord">
                    <label for="campo-posy"><?php _e('Posición Y', 'certificados-pdf'); ?></label>
                    <input type="number" id="campo-posy" min="0" step="1" value="0" onchange="this.value=Math.round(this.value)">
                </div>
            </div>
            
            <div class="campo-form-row campo-form-coords">
                <div class="campo-form-coord">
                    <label for="campo-ancho"><?php _e('Ancho', 'certificados-pdf'); ?></label>
                    <input type="number" id="campo-ancho" min="0" step="1" value="0" onchange="this.value=Math.round(this.value)">
                    <p class="description"><?php _e('0 = auto', 'certificados-pdf'); ?></p>
                </div>
                <div class="campo-form-coord">
                    <label for="campo-alto"><?php _e('Alto', 'certificados-pdf'); ?></label>
                    <input type="number" id="campo-alto" min="0" step="1" value="0" onchange="this.value=Math.round(this.value)">
                    <p class="description"><?php _e('0 = auto', 'certificados-pdf'); ?></p>
                </div>
            </div>
            
            <div class="campo-form-row">
                <label for="campo-color"><?php _e('Color del Texto', 'certificados-pdf'); ?></label>
                <input type="text" id="campo-color" class="color-field" value="#000000">
            </div>
            
            <div class="campo-form-row">
                <label for="campo-tamano"><?php _e('Tamaño de Fuente', 'certificados-pdf'); ?></label>
                <input type="number" id="campo-tamano" min="8" max="72" step="1" value="12" onchange="this.value=Math.round(this.value)">
                <p class="description"><?php _e('Tamaño en píxeles', 'certificados-pdf'); ?></p>
            </div>
            
            <div class="campo-form-row">
                <label for="campo-alineacion"><?php _e('Alineación', 'certificados-pdf'); ?></label>
                <select id="campo-alineacion">
                    <option value="left"><?php _e('Izquierda', 'certificados-pdf'); ?></option>
                    <option value="center"><?php _e('Centro', 'certificados-pdf'); ?></option>
                    <option value="right"><?php _e('Derecha', 'certificados-pdf'); ?></option>
                </select>
            </div>
            
            <div class="campo-form-actions">
                <button type="button" id="campo-cancelar" class="button button-secondary"><?php _e('Cancelar', 'certificados-pdf'); ?></button>
                <button type="submit" id="campo-guardar" class="button button-primary"><?php _e('Guardar Campo', 'certificados-pdf'); ?></button>
            </div>
        </form>
    </div>

<!-- Cargar fuentes personalizadas y añadir selector de tipografía al modal -->
<style>
/* Cargar estilos para las fuentes disponibles */
<?php
// Generar CSS para las fuentes disponibles
$fonts_dir = plugin_dir_path(dirname(dirname(__FILE__))) . 'public/fonts/';
if (is_dir($fonts_dir)) {
    $font_files = glob($fonts_dir . '*.ttf');
    foreach ($font_files as $font_file) {
        $font_name = pathinfo($font_file, PATHINFO_FILENAME);
        $font_url = plugins_url('public/fonts/' . basename($font_file), dirname(dirname(__FILE__)));
        echo "@font-face {
            font-family: '{$font_name}';
            src: url('{$font_url}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }\n";
    }
}
?>

/* Estilos para campos con diferentes tipografías */
.campo-item[data-tipografia="default"] {
    font-family: inherit;
}
<?php
// Generar estilos para cada fuente personalizada
if (is_dir($fonts_dir)) {
    $font_files = glob($fonts_dir . '*.ttf');
    foreach ($font_files as $font_file) {
        $font_name = pathinfo($font_file, PATHINFO_FILENAME);
        echo ".campo-item[data-tipografia=\"{$font_name}\"] {
            font-family: '{$font_name}', sans-serif !important;
        }\n";
    }
}
?>

/* Fuentes del sistema */
.campo-item[data-tipografia="Arial"] {
    font-family: Arial, sans-serif !important;
}
.campo-item[data-tipografia="Helvetica"] {
    font-family: Helvetica, sans-serif !important;
}
.campo-item[data-tipografia="Times New Roman"] {
    font-family: 'Times New Roman', serif !important;
}
.campo-item[data-tipografia="Georgia"] {
    font-family: Georgia, serif !important;
}
.campo-item[data-tipografia="Courier New"] {
    font-family: 'Courier New', monospace !important;
}
.campo-item[data-tipografia="Verdana"] {
    font-family: Verdana, sans-serif !important;
}
.campo-item[data-tipografia="Tahoma"] {
    font-family: Tahoma, sans-serif !important;
}
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Función para añadir el selector de tipografías al modal
        function addTipografiaSelector() {
            // Comprobar si ya existe
            if ($('#campo-tipografia').length > 0) {
                return; // El selector ya existe
            }
            
            console.log('Añadiendo selector de tipografía');
            
            // Crear el selector de tipografía
            var $tipografiaRow = $('<div class="campo-form-row">\
                <label for="campo-tipografia">Tipografía</label>\
                <select id="campo-tipografia">\
                    <option value="default">Default (Sistema)</option>\
                    <option value="Arial">Arial</option>\
                    <option value="Helvetica">Helvetica</option>\
                    <option value="Times New Roman">Times New Roman</option>\
                    <option value="Georgia">Georgia</option>\
                    <option value="Courier New">Courier New</option>\
                    <option value="Verdana">Verdana</option>\
                    <option value="Tahoma">Tahoma</option>\
                </select>\
                <p class="description">Selecciona la tipografía para este campo</p>\
            </div>');
            
            // Insertar después de la alineación
            var $alineacionRow = $('.campo-form-row').has('#campo-alineacion');
            if ($alineacionRow.length) {
                $alineacionRow.after($tipografiaRow);
                
                // Cargar las fuentes personalizadas
                loadCustomFonts();
            } else {
                // Si no encuentra la fila de alineación, intentar insertar antes de las acciones
                var $actionsRow = $('.campo-form-actions');
                if ($actionsRow.length) {
                    $actionsRow.before($tipografiaRow);
                    loadCustomFonts();
                } else {
                    console.log('No se pudo encontrar dónde insertar el selector de tipografía');
                }
            }
        }
        
        // Cargar las fuentes personalizadas
        function loadCustomFonts() {
            console.log('Cargando fuentes personalizadas...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_available_fonts',
                    nonce: $('input[name="nonce"]').val()
                },
                success: function(response) {
                    console.log('Respuesta de get_available_fonts:', response);
                    if (response.success && response.data.fonts && response.data.fonts.length > 0) {
                        var $tipografia = $('#campo-tipografia');
                        
                        // Añadir separador
                        $tipografia.append('<option disabled>──────────</option>');
                        
                        // Añadir fuentes personalizadas
                        $.each(response.data.fonts, function(index, font) {
                            $tipografia.append('<option value="' + font + '">' + font + '</option>');
                        });
                        
                        console.log('Fuentes personalizadas cargadas:', response.data.fonts);
                    } else {
                        console.log('No se encontraron fuentes personalizadas o hubo un error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar fuentes:', error);
                }
            });
        }
        
        // Escuchar eventos para cuando se abre el modal
        $(document).on('click', '#add-field, .edit-campo', function() {
            console.log('Evento de campo detectado, verificando selector de tipografía');
            
            // Esperar a que el modal esté visible antes de intentar añadir el selector
            var checkModalInterval = setInterval(function() {
                if ($('#campo-modal').is(':visible')) {
                    clearInterval(checkModalInterval);
                    
                    // Intentar añadir el selector con un pequeño retraso
                    setTimeout(function() {
                        addTipografiaSelector();
                    }, 100);
                }
            }, 50);
        });
        
        // Intentar añadir el selector después de que el DOM esté listo
        addTipografiaSelector();
        
        // Si no funciona de inmediato, intentar nuevamente después de un breve retraso
        setTimeout(function() {
            if ($('#campo-tipografia').length === 0) {
                addTipografiaSelector();
            }
        }, 500);
        
        // Modificar las funciones existentes para soportar tipografías
        
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
                    tipografia: $('#campo-tipografia').val() || 'default' // Añadir tipografía
                };
                
                // Registrar datos del campo para depuración
                console.log('Guardando campo con datos:', campoData);
                
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
        } else {
            console.warn('La función saveField no está definida');
        }
        
        // 2. Modificar createFieldElement
        var originalCreateFieldElement = window.createFieldElement;
        if (typeof originalCreateFieldElement === 'function') {
            window.createFieldElement = function(campoData) {
                console.log('Creando elemento de campo con tipografía:', campoData.tipografia);
                
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
                    .attr('data-tipografia', campoData.tipografia || 'default') // Añadir atributo para tipografía
                    .css({
                        'left': campoData.pos_x + 'px',
                        'top': campoData.pos_y + 'px',
                        'color': campoData.color,
                        'font-size': Math.max(14, campoData.tamano_fuente) + 'px',
                        'text-align': campoData.alineacion,
                        'font-family': campoData.tipografia && campoData.tipografia !== 'default' ? campoData.tipografia : 'inherit' // Aplicar tipografía
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
        } else {
            console.warn('La función createFieldElement no está definida');
        }
        
        // 3. Modificar openEditModal
        var originalOpenEditModal = window.openEditModal;
        if (typeof originalOpenEditModal === 'function') {
            window.openEditModal = function($campo) {
                var campoId = $campo.data('id');
                var campoData = window.getFieldFromArray(campoId);
                
                if (!campoData) return;
                
                console.log('Abriendo modal para editar campo:', campoData);
                
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
                
                // Establecer tipografía
                if ($('#campo-tipografia').length) {
                    $('#campo-tipografia').val(campoData.tipografia || 'default');
                    console.log('Tipografía establecida en el modal:', campoData.tipografia || 'default');
                } else {
                    console.warn('No se encontró el selector de tipografía al abrir el modal');
                    // Intentar agregar el selector de tipografía nuevamente
                    addTipografiaSelector();
                    
                    // Intentar establecer el valor después de un breve retraso
                    setTimeout(function() {
                        if ($('#campo-tipografia').length) {
                            $('#campo-tipografia').val(campoData.tipografia || 'default');
                        }
                    }, 100);
                }
                
                // Actualizar el selector de color
                if ($.fn.wpColorPicker) {
                    $('#campo-color').wpColorPicker('color', campoData.color);
                }
                
                $('#campo-modal-title').text('Editar Campo');
                $('#campo-modal').show();
            };
        } else {
            console.warn('La función openEditModal no está definida');
        }
        
        // 4. Asegurarse de que los campos existentes tengan tipografía
        setTimeout(function() {
            $('.campo-item').each(function() {
                var $this = $(this);
                var id = $this.data('id');
                
                console.log('Procesando campo existente ID:', id);
                
                // Buscar en el array de campos
                if (window.camposArray) {
                    for (var i = 0; i < window.camposArray.length; i++) {
                        if (window.camposArray[i].id == id) {
                            // Si no tiene tipografía, asignar default
                            if (!window.camposArray[i].tipografia) {
                                window.camposArray[i].tipografia = 'default';
                                console.log('Asignando tipografía default a campo ID:', id);
                            }
                            
                            // Asegurarse de que el campo tiene el atributo de tipografía
                            if (!$this.attr('data-tipografia')) {
                                $this.attr('data-tipografia', window.camposArray[i].tipografia);
                                
                                // Aplicar la fuente si no es default
                                if (window.camposArray[i].tipografia !== 'default') {
                                    $this.css('font-family', window.camposArray[i].tipografia);
                                    console.log('Aplicando tipografía a campo ID:', id, 'Fuente:', window.camposArray[i].tipografia);
                                }
                            }
                            
                            break;
                        }
                    }
                } else {
                    console.warn('camposArray no está definido');
                }
            });
        }, 1000);
        
        // Añadir registro de depuración
        console.log('Script de gestión de tipografías inicializado');
    });
</script>
    
</div>