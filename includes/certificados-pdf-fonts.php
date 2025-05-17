<?php
/**
 * Clase para la gestión de fuentes.
 * plugins-main/includes/certificados-pdf-fonts.php
 * @since      1.0.0
 */
class Certificados_PDF_Fonts {

    /**
     * Directorio de fuentes
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $fonts_dir    Directorio de fuentes
     */
    private $fonts_dir;

    /**
     * URL del directorio de fuentes
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $fonts_url    URL del directorio de fuentes
     */
    private $fonts_url;

    /**
     * Fuentes del sistema disponibles
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $system_fonts    Lista de fuentes del sistema
     */
    private $system_fonts;

    /**
     * Inicializa la clase.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Definir el directorio principal de fuentes
        $this->fonts_dir = CERT_PDF_FONTS_DIR;
        
        // Definir la URL de fuentes (para vista previa)
        $this->fonts_url = plugins_url('public/fonts/', dirname(plugin_basename(__FILE__)));
        
        // Definir fuentes del sistema disponibles
        $this->system_fonts = array(
            'Arial' => array(
                'family' => 'Arial, sans-serif',
                'pdf_name' => 'helvetica',
                'type' => 'system'
            ),
            'Helvetica' => array(
                'family' => 'Helvetica, Arial, sans-serif',
                'pdf_name' => 'helvetica',
                'type' => 'system'
            ),
            'Times New Roman' => array(
                'family' => '"Times New Roman", serif',
                'pdf_name' => 'times',
                'type' => 'system'
            ),
            'Georgia' => array(
                'family' => 'Georgia, serif',
                'pdf_name' => 'freeserif',
                'type' => 'system'
            ),
            'Courier New' => array(
                'family' => '"Courier New", monospace',
                'pdf_name' => 'courier',
                'type' => 'system'
            ),
            'Verdana' => array(
                'family' => 'Verdana, sans-serif',
                'pdf_name' => 'freesans',
                'type' => 'system'
            ),
            'Tahoma' => array(
                'family' => 'Tahoma, sans-serif',
                'pdf_name' => 'freesans',
                'type' => 'system'
            )
        );

        // Asegurar que existe el directorio de fuentes
        $this->ensure_fonts_directory();
        
        // Registrar hooks para el admin
        add_action('wp_ajax_upload_font', array($this, 'ajax_upload_font'));
        add_action('wp_ajax_delete_font', array($this, 'ajax_delete_font'));
        add_action('wp_ajax_get_available_fonts', array($this, 'ajax_get_available_fonts'));
        
        // Agregar estilos para las fuentes en el admin
        add_action('admin_head', array($this, 'add_font_styles'));
    }

    /**
     * Asegura que el directorio de fuentes existe.
     *
     * @since    1.0.0
     */
    private function ensure_fonts_directory() {
        if (!file_exists($this->fonts_dir)) {
            // Crear el directorio de fuentes
            wp_mkdir_p($this->fonts_dir);
            
            // Establecer permisos adecuados
            @chmod($this->fonts_dir, 0755);
            
            // Crear archivo index.php para protección
            file_put_contents($this->fonts_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Obtiene la lista de todas las fuentes disponibles.
     *
     * @since    1.0.0
     * @return   array    Lista de fuentes disponibles
     */
    public function get_fonts() {
        $fonts = array();
        
        // Añadir fuentes del sistema
        $fonts['system'] = $this->system_fonts;
        
        // Añadir fuentes personalizadas
        $custom_fonts = array();
        
        if (file_exists($this->fonts_dir)) {
            $font_files = glob($this->fonts_dir . '/*.ttf');
            
            foreach ($font_files as $font_file) {
                $font_name = basename($font_file, '.ttf');
                $custom_fonts[$font_name] = array(
                    'family' => "'{$font_name}', sans-serif",
                    'pdf_name' => $font_name,
                    'file' => $font_file,
                    'url' => $this->fonts_url . basename($font_file),
                    'type' => 'custom'
                );
            }
        }
        
        // Comprobar directorio alternativo del plugin si está definido
        $plugin_fonts_dir = CERT_PDF_PLUGIN_DIR . 'public/fonts/';
        if (file_exists($plugin_fonts_dir)) {
            $plugin_font_files = glob($plugin_fonts_dir . '/*.ttf');
            
            foreach ($plugin_font_files as $font_file) {
                $font_name = basename($font_file, '.ttf');
                
                // Solo añadir si no existe ya
                if (!isset($custom_fonts[$font_name])) {
                    $custom_fonts[$font_name] = array(
                        'family' => "'{$font_name}', sans-serif",
                        'pdf_name' => $font_name,
                        'file' => $font_file,
                        'url' => CERT_PDF_PLUGIN_URL . 'public/fonts/' . basename($font_file),
                        'type' => 'plugin'
                    );
                }
            }
        }
        
        $fonts['custom'] = $custom_fonts;
        
        return $fonts;
    }

    /**
     * Genera estilos CSS para las fuentes personalizadas.
     *
     * @since    1.0.0
     * @return   string    Estilos CSS para las fuentes
     */
    public function get_font_styles() {
        $fonts = $this->get_fonts();
        $styles = '';
        
        // Generar @font-face para fuentes personalizadas
        foreach ($fonts['custom'] as $font_name => $font_data) {
            $styles .= "@font-face {\n";
            $styles .= "    font-family: '{$font_name}';\n";
            $styles .= "    src: url('{$font_data['url']}') format('truetype');\n";
            $styles .= "    font-weight: normal;\n";
            $styles .= "    font-style: normal;\n";
            $styles .= "}\n";
        }
        
        // Generar estilos para previsualización
        $styles .= ".font-preview {\n";
        $styles .= "    margin-bottom: 10px;\n";
        $styles .= "    padding: 15px;\n";
        $styles .= "    background: #fff;\n";
        $styles .= "    border: 1px solid #ddd;\n";
        $styles .= "    border-radius: 4px;\n";
        $styles .= "}\n";
        
        // Estilos para cada fuente
        foreach ($fonts['system'] as $font_name => $font_data) {
            $styles .= ".font-preview.font-{$this->sanitize_font_name($font_name)} {\n";
            $styles .= "    font-family: {$font_data['family']};\n";
            $styles .= "}\n";
        }
        
        foreach ($fonts['custom'] as $font_name => $font_data) {
            $styles .= ".font-preview.font-{$this->sanitize_font_name($font_name)} {\n";
            $styles .= "    font-family: '{$font_name}', sans-serif;\n";
            $styles .= "}\n";
        }
        
        return $styles;
    }

    /**
     * Sanitiza un nombre de fuente para usarlo en clases CSS.
     *
     * @since    1.0.0
     * @param    string    $font_name    Nombre de la fuente
     * @return   string                  Nombre sanitizado
     */
    private function sanitize_font_name($font_name) {
        // Reemplazar espacios y caracteres especiales con guiones
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '-', $font_name);
        // Convertir a minúsculas
        $sanitized = strtolower($sanitized);
        // Eliminar guiones múltiples
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Eliminar guiones al principio y al final
        $sanitized = trim($sanitized, '-');
        
        return $sanitized;
    }

    /**
     * Agrega estilos CSS para las fuentes en el admin.
     *
     * @since    1.0.0
     */
    public function add_font_styles() {
        // Solo agregar estilos en páginas del plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'certificados_pdf') === false) {
            return;
        }
        
        echo '<style type="text/css">' . $this->get_font_styles() . '</style>';
    }

    /**
     * Maneja la subida de fuentes por AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_upload_font() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'upload_font_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
            return;
        }
        
        // Verificar archivo
        if (!isset($_FILES['font_file']) || $_FILES['font_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $_FILES['font_file']['error'] ?? 'Desconocido';
            wp_send_json_error(array('message' => 'Error al subir el archivo: ' . $error));
            return;
        }
        
        // Verificar extensión
        $file_extension = strtolower(pathinfo($_FILES['font_file']['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'ttf') {
            wp_send_json_error(array('message' => 'Solo se permiten archivos TTF.'));
            return;
        }
        
        // Asegurar que existe el directorio
        $this->ensure_fonts_directory();
        
        // Sanitizar nombre de archivo
        $font_name = pathinfo($_FILES['font_file']['name'], PATHINFO_FILENAME);
        $font_name = sanitize_file_name($font_name);
        
        // Ruta de destino
        $dest_path = $this->fonts_dir . '/' . $font_name . '.ttf';
        
        // Mover archivo
        if (move_uploaded_file($_FILES['font_file']['tmp_name'], $dest_path)) {
            // Establecer permisos adecuados
            @chmod($dest_path, 0644);
            
            // Regenerar los estilos de fuentes
            $this->add_font_styles();
            
            wp_send_json_success(array(
                'message' => 'Fuente subida correctamente.',
                'font_name' => $font_name,
                'font_url' => $this->fonts_url . $font_name . '.ttf'
            ));
        } else {
            wp_send_json_error(array('message' => 'Error al guardar el archivo. Verifica los permisos del directorio.'));
        }
    }

    /**
     * Maneja la eliminación de fuentes por AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_delete_font() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_font_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
            return;
        }
        
        // Verificar nombre de fuente
        if (!isset($_POST['font_name']) || empty($_POST['font_name'])) {
            wp_send_json_error(array('message' => 'Nombre de fuente no válido.'));
            return;
        }
        
        $font_name = sanitize_file_name($_POST['font_name']);
        $font_path = $this->fonts_dir . '/' . $font_name . '.ttf';
        
        // Verificar que el archivo existe
        if (!file_exists($font_path)) {
            wp_send_json_error(array('message' => 'La fuente no existe.'));
            return;
        }
        
        // Eliminar archivo
        if (unlink($font_path)) {
            wp_send_json_success(array('message' => 'Fuente eliminada correctamente.'));
        } else {
            wp_send_json_error(array('message' => 'Error al eliminar la fuente. Verifica los permisos del archivo.'));
        }
    }

    /**
     * Devuelve la lista de fuentes disponibles por AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_get_available_fonts() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'certificados_pdf_nonce')) {
            wp_send_json_error(array('message' => 'Error de seguridad. Por favor, recarga la página.'));
            return;
        }
        
        $fonts = $this->get_fonts();
        $font_list = array();
        
        // Agregar fuentes del sistema
        foreach ($fonts['system'] as $font_name => $font_data) {
            $font_list[] = $font_name;
        }
        
        // Agregar fuentes personalizadas
        foreach ($fonts['custom'] as $font_name => $font_data) {
            $font_list[] = $font_name;
        }
        
        wp_send_json_success(array(
            'fonts' => $font_list,
            'preview_styles' => $this->get_font_styles()
        ));
    }

    /**
     * Renderiza la sección de gestión de fuentes en la página de configuración.
     *
     * @since    1.0.0
     */
    public function render_fonts_manager() {
        $fonts = $this->get_fonts();
        
        // Agrupar fuentes para la visualización
        $system_fonts = $fonts['system'];
        $custom_fonts = $fonts['custom'];
        
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php _e('Gestión de Tipografías', 'certificados-pdf'); ?></span></h2>
            <div class="inside">
                <div class="fonts-manager">
                    <div class="system-fonts-section">
                        <h3><?php _e('Fuentes del Sistema', 'certificados-pdf'); ?></h3>
                        <p class="description"><?php _e('Estas fuentes están disponibles en todos los sistemas y son seguras para usar en tus certificados.', 'certificados-pdf'); ?></p>
                        
                        <div class="font-grid">
                            <?php foreach ($system_fonts as $font_name => $font_data): ?>
                                <div class="font-item">
                                    <div class="font-preview font-<?php echo $this->sanitize_font_name($font_name); ?>">
                                        <div class="preview-text" style="font-size: 18px;"><?php echo $font_name; ?></div>
                                        <div class="preview-text" style="font-size: 14px;">ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
                                        <div class="preview-text" style="font-size: 14px;">abcdefghijklmnopqrstuvwxyz</div>
                                        <div class="preview-text" style="font-size: 14px;">0123456789</div>
                                    </div>
                                    <div class="font-info">
                                        <div class="font-name"><?php echo $font_name; ?></div>
                                        <div class="font-type"><?php _e('Fuente del Sistema', 'certificados-pdf'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="custom-fonts-section">
                        <h3><?php _e('Fuentes Personalizadas', 'certificados-pdf'); ?></h3>
                        
                        <?php if (empty($custom_fonts)): ?>
                            <p><?php _e('No hay fuentes personalizadas disponibles. Sube una fuente usando el formulario a continuación.', 'certificados-pdf'); ?></p>
                        <?php else: ?>
                            <div class="font-grid">
                                <?php foreach ($custom_fonts as $font_name => $font_data): ?>
                                    <div class="font-item">
                                        <div class="font-preview font-<?php echo $this->sanitize_font_name($font_name); ?>">
                                            <div class="preview-text" style="font-size: 18px;"><?php echo $font_name; ?></div>
                                            <div class="preview-text" style="font-size: 14px;">ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
                                            <div class="preview-text" style="font-size: 14px;">abcdefghijklmnopqrstuvwxyz</div>
                                            <div class="preview-text" style="font-size: 14px;">0123456789</div>
                                        </div>
                                        <div class="font-info">
                                            <div class="font-name"><?php echo $font_name; ?></div>
                                            <div class="font-type">
                                                <?php 
                                                if ($font_data['type'] === 'custom') {
                                                    _e('Fuente Personalizada', 'certificados-pdf');
                                                } else {
                                                    _e('Fuente del Plugin', 'certificados-pdf');
                                                }
                                                ?>
                                            </div>
                                            <?php if ($font_data['type'] === 'custom'): ?>
                                                <a href="#" class="delete-font button button-small" data-font="<?php echo esc_attr($font_name); ?>">
                                                    <span class="dashicons dashicons-trash"></span> <?php _e('Eliminar', 'certificados-pdf'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="font-upload-section">
                            <h4><?php _e('Añadir Nueva Fuente', 'certificados-pdf'); ?></h4>
                            <p class="description"><?php _e('Sube un archivo TTF para añadir una nueva fuente personalizada. Asegúrate de tener los derechos para usar la fuente.', 'certificados-pdf'); ?></p>
                            
                            <div class="font-upload-form">
                                <input type="file" id="font-upload" accept=".ttf" />
                                <p class="description"><?php _e('Solo se permiten archivos TTF.', 'certificados-pdf'); ?></p>
                                <button type="button" id="upload-font-btn" class="button button-primary">
                                    <span class="dashicons dashicons-upload"></span> <?php _e('Subir Fuente', 'certificados-pdf'); ?>
                                </button>
                                <div id="font-upload-message"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                /* Estilos para el gestor de fuentes */
                .fonts-manager {
                    margin-top: 20px;
                }
                
                .system-fonts-section,
                .custom-fonts-section {
                    margin-bottom: 30px;
                }
                
                .font-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                    margin-top: 15px;
                }
                
                .font-item {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    overflow: hidden;
                    background: #f9f9f9;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                
                .font-preview {
                    padding: 15px;
                    background: #fff;
                    border-bottom: 1px solid #eee;
                }
                
                .preview-text {
                    margin-bottom: 8px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                
                .font-info {
                    padding: 10px 15px;
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .font-name {
                    font-weight: bold;
                    margin-bottom: 5px;
                    width: 100%;
                }
                
                .font-type {
                    color: #666;
                    font-size: 12px;
                    margin-right: 10px;
                }
                
                .delete-font {
                    font-size: 12px;
                }
                
                .font-upload-section {
                    margin-top: 25px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                
                .font-upload-form {
                    margin-top: 15px;
                    padding: 15px;
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                
                #font-upload-message {
                    margin-top: 10px;
                }
                
                #font-upload-message .success {
                    color: #46b450;
                }
                
                #font-upload-message .error {
                    color: #dc3232;
                }
                
                #font-upload-message .loading {
                    color: #0073aa;
                    display: flex;
                    align-items: center;
                }
                
                #font-upload-message .loading:before {
                    content: "";
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    margin-right: 5px;
                    border: 2px solid #0073aa;
                    border-top-color: transparent;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                </style>
                
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Manejar la carga de fuentes
                    $('#upload-font-btn').on('click', function() {
                        var fileInput = $('#font-upload')[0];
                        
                        if (fileInput.files.length === 0) {
                            $('#font-upload-message').html('<p class="error">Por favor, selecciona un archivo TTF.</p>');
                            return;
                        }
                        
                        var file = fileInput.files[0];
                        if (!file.name.toLowerCase().endsWith('.ttf')) {
                            $('#font-upload-message').html('<p class="error">Solo se permiten archivos TTF.</p>');
                            return;
                        }
                        
                        // Crear un FormData para enviar el archivo
                        var formData = new FormData();
                        formData.append('action', 'upload_font');
                        formData.append('font_file', file);
                        formData.append('nonce', '<?php echo wp_create_nonce('upload_font_nonce'); ?>');
                        
                        // Mostrar indicador de carga
                        $('#font-upload-message').html('<p class="loading">Subiendo fuente...</p>');
                        
                        // Enviar la solicitud AJAX
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    $('#font-upload-message').html('<p class="success">' + response.data.message + '</p>');
                                    // Recargar la página para mostrar la nueva fuente
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    $('#font-upload-message').html('<p class="error">' + response.data.message + '</p>');
                                }
                            },
                            error: function() {
                                $('#font-upload-message').html('<p class="error">Error al subir la fuente. Inténtalo de nuevo.</p>');
                            }
                        });
                    });
                    
                    // Manejar la eliminación de fuentes
                    $('.delete-font').on('click', function(e) {
                        e.preventDefault();
                        
                        var fontName = $(this).data('font');
                        if (confirm('¿Estás seguro de que deseas eliminar la fuente "' + fontName + '"?')) {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'delete_font',
                                    font_name: fontName,
                                    nonce: '<?php echo wp_create_nonce('delete_font_nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        // Recargar la página después de eliminar
                                        window.location.reload();
                                    } else {
                                        alert(response.data.message);
                                    }
                                },
                                error: function() {
                                    alert('Error al eliminar la fuente. Inténtalo de nuevo.');
                                }
                            });
                        }
                    });
                });
                </script>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los datos de una fuente específica.
     *
     * @since    1.0.0
     * @param    string    $font_name    Nombre de la fuente
     * @return   array                  Datos de la fuente o vacío si no existe
     */
    public function get_font_data($font_name) {
        $fonts = $this->get_fonts();
        
        // Buscar en fuentes del sistema
        if (isset($fonts['system'][$font_name])) {
            return $fonts['system'][$font_name];
        }
        
        // Buscar en fuentes personalizadas
        if (isset($fonts['custom'][$font_name])) {
            return $fonts['custom'][$font_name];
        }
        
        return array();
    }

    /**
     * Prepara un archivo de fuente para TCPDF.
     *
     * @since    1.0.0
     * @param    string    $font_name    Nombre de la fuente
     * @return   array                  Datos para TCPDF o falso si falla
     */
    public function prepare_font_for_tcpdf($font_name) {
        $font_data = $this->get_font_data($font_name);
        
        if (empty($font_data)) {
            return false;
        }
        
        // Si es una fuente del sistema, devolver el nombre de fuente para TCPDF
        if ($font_data['type'] === 'system') {
            return array(
                'fontname' => $font_data['pdf_name'],
                'fontstyle' => '',
                'is_system' => true
            );
        }
        
        // Si es una fuente personalizada, verificar que existe el archivo
        if (!isset($font_data['file']) || !file_exists($font_data['file'])) {
            return false;
        }
        
        // Intentar añadir la fuente a TCPDF
        $font_file = $font_data['file'];
        
        try {
            // Registrar la fuente en TCPDF
            $tcpdf_font = TCPDF_FONTS::addTTFfont($font_file, 'TrueTypeUnicode', '', 96);
            
            if ($tcpdf_font) {
                return array(
                    'fontname' => $tcpdf_font,
                    'fontstyle' => '',
                    'is_system' => false
                );
            }
        } catch (Exception $e) {
            error_log('Certificados PDF: Error al preparar fuente para TCPDF - ' . $e->getMessage());
        }
        
        return false;
    }
}