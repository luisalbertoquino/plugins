<?php
/**
 * Vista de configuración del plugin.
 * plugins-main/admin/partials/certificados-pdf-admin-settings.php
 * @since      1.0.0
 */
?>

<div class="wrap certificados-pdf-admin certificados-pdf-settings">
    <h1 class="wp-heading-inline"><?php _e('Configuración de Certificados PDF', 'certificados-pdf'); ?></h1>
    <hr class="wp-header-end">
    
    <?php
    // Procesar formulario de configuración
    if (isset($_POST['certificados_pdf_settings_nonce']) && wp_verify_nonce($_POST['certificados_pdf_settings_nonce'], 'certificados_pdf_settings')) {
        // Guardar la API Key de Google
        $google_api_key = sanitize_text_field($_POST['google_api_key']);
        update_option('certificados_pdf_google_api_key', $google_api_key);
        
        // Mostrar mensaje de éxito
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'certificados-pdf') . '</p></div>';
    }
    ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('certificados_pdf_settings', 'certificados_pdf_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="google_api_key"><?php _e('API Key de Google', 'certificados-pdf'); ?></label>
                </th>
                <td>
                    <input type="text" id="google_api_key" name="google_api_key" class="regular-text" value="<?php echo esc_attr($google_api_key); ?>">
                    <p class="description">
                        <?php _e('Introduce tu API Key de Google para acceder a Google Sheets. Puedes obtener una en la <a href="https://console.developers.google.com/" target="_blank">Consola de Google Cloud</a>.', 'certificados-pdf'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Permisos Necesarios', 'certificados-pdf'); ?></h2>
        <p><?php _e('Para utilizar este plugin, necesitas habilitar las siguientes APIs en la Consola de Google Cloud:', 'certificados-pdf'); ?></p>
        <ul class="ul-disc">
            <li><?php _e('Google Sheets API', 'certificados-pdf'); ?></li>
            <li><?php _e('Google Drive API', 'certificados-pdf'); ?></li>
        </ul>
        
        <h2><?php _e('Pasos para Configurar', 'certificados-pdf'); ?></h2>
        <ol>
            <li><?php _e('Crea un proyecto en la <a href="https://console.developers.google.com/" target="_blank">Consola de Google Cloud</a>.', 'certificados-pdf'); ?></li>
            <li><?php _e('Habilita las APIs mencionadas anteriormente.', 'certificados-pdf'); ?></li>
            <li><?php _e('Crea una API Key y cópiala en el campo de arriba.', 'certificados-pdf'); ?></li>
            <li><?php _e('Asegúrate de que tu hoja de Google Sheets esté compartida públicamente con permisos de lectura (o al menos con la cuenta de servicio que utilizas).', 'certificados-pdf'); ?></li>
        </ol>
        
        <p><?php _e('Recuerda que la primera fila de tu hoja de Google Sheets debe contener los encabezados de las columnas.', 'certificados-pdf'); ?></p>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Guardar Configuración', 'certificados-pdf'); ?>">
        </p>
    </form>


    <h2><?php _e('Gestión de Tipografías', 'certificados-pdf'); ?></h2>
    <div class="postbox">
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Fuentes disponibles', 'certificados-pdf'); ?></label>
                    </th>
                    <td>
                        <div id="fonts-list" class="fonts-list">
                            <?php
                            // Listar fuentes existentes
                            $fonts_dir = ABSPATH . 'public/fonts/';
                            if (is_dir($fonts_dir)) {
                                $fonts = glob($fonts_dir . '*.ttf');
                                
                                if (!empty($fonts)) {
                                    echo '<ul class="font-items">';
                                    foreach ($fonts as $font_path) {
                                        $font_name = basename($font_path, '.ttf');
                                        echo '<li class="font-item">';
                                        echo '<span class="font-name" style="font-family: \'' . esc_attr($font_name) . '\';">' . esc_html($font_name) . '</span>';
                                        echo '<a href="#" class="delete-font" data-font="' . esc_attr($font_name) . '">Eliminar</a>';
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    echo '<p>' . __('No hay fuentes personalizadas disponibles.', 'certificados-pdf') . '</p>';
                                }
                            } else {
                                echo '<p>' . __('El directorio de fuentes no existe. Se creará al subir la primera fuente.', 'certificados-pdf') . '</p>';
                            }
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="font-upload"><?php _e('Añadir nueva fuente', 'certificados-pdf'); ?></label>
                    </th>
                    <td>
                        <input type="file" id="font-upload" accept=".ttf" />
                        <p class="description"><?php _e('Sube un archivo TTF. Asegúrate de tener los derechos para usar esta fuente.', 'certificados-pdf'); ?></p>
                        <button type="button" id="upload-font-btn" class="button button-secondary"><?php _e('Subir fuente', 'certificados-pdf'); ?></button>
                        <div id="font-upload-message"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <style>
    /* Estilos para la gestión de fuentes */
    .fonts-list {
        margin-bottom: 15px;
    }
    .font-items {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .font-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px;
        margin-bottom: 5px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 3px;
    }
    .font-name {
        font-size: 16px;
        margin-right: 10px;
    }
    .delete-font {
        color: #dc3232;
        text-decoration: none;
    }
    .delete-font:hover {
        text-decoration: underline;
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
                        // Recargar la lista de fuentes
                        window.location.reload();
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