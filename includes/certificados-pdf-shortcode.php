<?php
/**
 * Clase para gestionar los shortcodes del plugin.
 *
 * @since      1.0.0
 */
class Certificados_PDF_Shortcode {

    /**
     * El ID del plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    El ID del plugin.
     */
    private $plugin_name;

    /**
     * La versión del plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    La versión actual del plugin.
     */
    private $version;

    /**
     * Inicializar la clase y establecer sus propiedades.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    El nombre del plugin.
     * @param    string    $version        La versión del plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registrar los estilos para el área pública.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, CERT_PDF_PLUGIN_URL . 'public/css/certificados-pdf-public.css', array(), $this->version, 'all');
    }

    /**
     * Registrar los scripts para el área pública.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, CERT_PDF_PLUGIN_URL . 'public/js/certificados-pdf-public.js', array('jquery'), $this->version, true);
        
        // Pasar variables al script - IMPORTANTE
        wp_localize_script($this->plugin_name, 'certificados_pdf_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('certificados_pdf_public_nonce'),
            'i18n' => array(
                'error_form' => __('Por favor, complete todos los campos requeridos.', 'certificados-pdf'),
                'error_search' => __('No se encontraron resultados para su búsqueda.', 'certificados-pdf'),
                'error_connection' => __('Error de conexión. Por favor, inténtelo de nuevo más tarde.', 'certificados-pdf'),
                'loading' => __('Cargando...', 'certificados-pdf')
            )
        ));
    }

    /**
     * Renderiza el formulario para buscar certificados.
     *
     * @since    1.0.0
     * @param    array     $atts    Atributos del shortcode.
     * @return   string             El HTML del formulario.
     */
    public function mostrar_formulario($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'campo' => '',
            'titulo' => __('Buscar Certificado', 'certificados-pdf'),
            'placeholder' => __('Ingrese su número de identificación', 'certificados-pdf'),
            'boton' => __('Buscar', 'certificados-pdf'),
            'class' => 'certificado-pdf-form',
        ), $atts, 'certificado_pdf');
        
        $id = intval($atts['id']);
        
        if ($id <= 0) {
            return '<p class="certificado-error">' . __('Error: ID de certificado no especificado en el shortcode.', 'certificados-pdf') . '</p>';
        }
        
        // Obtener información del certificado
        global $wpdb;
        $tabla = $wpdb->prefix . 'certificados_pdf_templates';
        
        $certificado = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d AND habilitado = 1", $id));
        
        if (!$certificado) {
            return '<p class="certificado-error">' . __('Error: Certificado no encontrado o deshabilitado.', 'certificados-pdf') . '</p>';
        }
        
        // Campo de búsqueda personalizado o el predeterminado del certificado
        $campo_busqueda = !empty($atts['campo']) ? $atts['campo'] : $certificado->campo_busqueda;
        
        // Comenzar a capturar la salida del buffer
        ob_start();
        
        // Incluir la plantilla del formulario
        include CERT_PDF_PLUGIN_DIR . 'public/partials/certificados-pdf-shortcode-display.php';
        
        // Obtener el contenido del buffer y limpiarlo
        $output = ob_get_clean();
        
        return $output;
    }

    /**
     * Función de prueba simple para verificar la comunicación AJAX.
     * 
     * @since    1.0.0
     */
    public function test_ajax() {
        // Establecer archivo de log para depuración
        $log_file = CERT_PDF_PLUGIN_DIR . 'shortcode-test.log';
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Ejecutando test_ajax\n", FILE_APPEND);
        
        // Registrar los datos recibidos
        $post_data = $_POST;
        file_put_contents($log_file, "Datos POST recibidos: " . print_r($post_data, true) . "\n", FILE_APPEND);
        
        // Si se proporcionó un nonce, verificarlo
        if (isset($_POST['nonce'])) {
            $verify = wp_verify_nonce($_POST['nonce'], 'certificados_pdf_public_nonce');
            file_put_contents($log_file, "Verificación de nonce: " . ($verify ? 'Exitosa' : 'Fallida') . "\n", FILE_APPEND);
        }
        
        // Devolver respuesta exitosa
        wp_send_json_success(array(
            'message' => 'Prueba de AJAX exitosa',
            'post_data' => $post_data,
            'time' => current_time('mysql'),
            'plugin_dir' => CERT_PDF_PLUGIN_DIR,
            'plugin_url' => CERT_PDF_PLUGIN_URL
        ));
    }

    /**
     * Shortcode de prueba para diagnóstico de AJAX.
     * 
     * @since    1.0.0
     * @return   string    El HTML del formulario de prueba.
     */
    public function test_shortcode() {
        // Comenzar a capturar la salida
        ob_start();
        ?>
        <div class="certificado-test-container" style="padding: 20px; border: 1px solid #ddd; margin: 20px 0; background: #f9f9f9;">
            <h3>Prueba de Conexión AJAX</h3>
            
            <p>Este formulario permite verificar si la comunicación AJAX está funcionando correctamente.</p>
            
            <button id="test-ajax-button" class="button button-primary" style="margin: 10px 0;">Probar AJAX</button>
            
            <div id="test-result" style="margin-top: 15px; padding: 10px; border: 1px solid #eee; background: #fff; min-height: 50px;">
                <p>Los resultados de la prueba aparecerán aquí.</p>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-ajax-button').on('click', function() {
                    // Mostrar mensaje de carga
                    $('#test-result').html('<p>Enviando solicitud AJAX...</p>');
                    
                    // Log para depuración
                    console.log('Enviando solicitud AJAX de prueba');
                    console.log('Ajax URL:', certificados_pdf_vars.ajax_url);
                    console.log('Nonce:', certificados_pdf_vars.nonce);
                    
                    // Realizar solicitud AJAX
                    $.ajax({
                        url: certificados_pdf_vars.ajax_url,
                        type: 'POST',
                        data: {
                            'action': 'certificados_pdf_test',
                            'nonce': certificados_pdf_vars.nonce,
                            'test_data': 'Este es un mensaje de prueba',
                            'timestamp': new Date().getTime()
                        },
                        success: function(response) {
                            console.log('Respuesta recibida:', response);
                            
                            // Mostrar respuesta formateada
                            var resultHTML = '<div style="color: green; font-weight: bold;">✓ ¡Prueba exitosa!</div>';
                            resultHTML += '<h4>Detalles de la respuesta:</h4>';
                            resultHTML += '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">' + 
                                          JSON.stringify(response, null, 2) + '</pre>';
                            
                            $('#test-result').html(resultHTML);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud AJAX:', xhr.responseText);
                            
                            // Mostrar detalles del error
                            var resultHTML = '<div style="color: red; font-weight: bold;">✗ Error en la prueba</div>';
                            resultHTML += '<h4>Detalles del error:</h4>';
                            resultHTML += '<p>Status: ' + status + '</p>';
                            resultHTML += '<p>Error: ' + error + '</p>';
                            resultHTML += '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">' + 
                                          (xhr.responseText || 'No hay respuesta disponible') + '</pre>';
                            
                            $('#test-result').html(resultHTML);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
        
        // Obtener el contenido del buffer y limpiarlo
        return ob_get_clean();
    }

    /**
     * Genera y descarga un certificado en PDF.
     *
     * @since    1.0.0
     */
    public function generar_certificado() {
        // Log inmediato para diagnóstico
        $log_file = CERT_PDF_PLUGIN_DIR . 'ajax-debug.log';
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Solicitud recibida\n", FILE_APPEND);
        file_put_contents($log_file, "GET: " . print_r($_GET, true) . "\n", FILE_APPEND);
        file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
        file_put_contents($log_file, "REQUEST: " . print_r($_REQUEST, true) . "\n", FILE_APPEND);
        
        try {
            // Verificar nonce (comentado para diagnóstico)
            // check_ajax_referer('certificados_pdf_public_nonce', 'nonce');
            
            file_put_contents($log_file, "Nonce recibido: " . $_POST['nonce'] . "\n", FILE_APPEND);
            
            $certificado_id = isset($_POST['certificado_id']) ? intval($_POST['certificado_id']) : 0;
            $campo_busqueda = isset($_POST['campo_busqueda']) ? sanitize_text_field($_POST['campo_busqueda']) : '';
            $valor_busqueda = isset($_POST['valor_busqueda']) ? sanitize_text_field($_POST['valor_busqueda']) : '';
            
            file_put_contents($log_file, "Parámetros procesados: ID=$certificado_id, Campo=$campo_busqueda, Valor=$valor_busqueda\n", FILE_APPEND);
            
            if ($certificado_id <= 0 || empty($campo_busqueda) || empty($valor_busqueda)) {
                file_put_contents($log_file, "Parámetros inválidos\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'Parámetros inválidos'));
                return;
            }
            
            file_put_contents($log_file, "Obteniendo información del certificado...\n", FILE_APPEND);
            
            // Obtener información del certificado
            global $wpdb;
            $tabla = $wpdb->prefix . 'certificados_pdf_templates';
            $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
            
            $certificado = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d AND habilitado = 1", $certificado_id));
            
            if (!$certificado) {
                file_put_contents($log_file, "Certificado no encontrado o deshabilitado\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'Certificado no encontrado o deshabilitado'));
                return;
            }
            
            file_put_contents($log_file, "Certificado encontrado: " . print_r($certificado, true) . "\n", FILE_APPEND);
            
            // Obtener campos del certificado
            $campos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla_campos WHERE certificado_id = %d ORDER BY orden ASC", $certificado_id));
            
            if (empty($campos)) {
                file_put_contents($log_file, "El certificado no tiene campos configurados\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'El certificado no tiene campos configurados'));
                return;
            }
            
            file_put_contents($log_file, "Campos encontrados: " . count($campos) . "\n", FILE_APPEND);
            
            // Obtener API Key de Google
            $api_key = get_option('certificados_pdf_google_api_key', '');
            if (empty($api_key)) {
                file_put_contents($log_file, "API Key de Google no configurada\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'No se ha configurado la API Key de Google'));
                return;
            }
            
            file_put_contents($log_file, "API Key de Google encontrada (por seguridad no se muestra)\n", FILE_APPEND);
            file_put_contents($log_file, "Inicializando Google Sheets...\n", FILE_APPEND);
            
            // PUNTO CLAVE: Verificar si la clase existe antes de instanciarla
            if (!class_exists('Certificados_PDF_Google_Sheets')) {
                file_put_contents($log_file, "ERROR CRÍTICO: Clase Certificados_PDF_Google_Sheets no encontrada\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'Error interno: componente Google Sheets no disponible'));
                return;
            }
            
            // Crear instancia de Google Sheets - ESTE PODRÍA SER EL PUNTO DE FALLO
            try {
                $google_sheets = new Certificados_PDF_Google_Sheets($api_key);
                file_put_contents($log_file, "Instancia de Google Sheets creada exitosamente\n", FILE_APPEND);
            } catch (Exception $e) {
                file_put_contents($log_file, "ERROR al crear instancia de Google Sheets: " . $e->getMessage() . "\n", FILE_APPEND);
                file_put_contents($log_file, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'Error al conectar con Google Sheets: ' . $e->getMessage()));
                return;
            }
            
            // Log de información para diagnóstico
            file_put_contents($log_file, "Buscando en Google Sheets:\n", FILE_APPEND);
            file_put_contents($log_file, "Sheet ID: {$certificado->sheet_id}\n", FILE_APPEND);
            file_put_contents($log_file, "Nombre de hoja: {$certificado->sheet_nombre}\n", FILE_APPEND);
            file_put_contents($log_file, "Campo de búsqueda: $campo_busqueda\n", FILE_APPEND);
            file_put_contents($log_file, "Valor de búsqueda: $valor_busqueda\n", FILE_APPEND);
            
            // Buscar el registro en Google Sheets - OTRO POSIBLE PUNTO DE FALLO
            try {
                file_put_contents($log_file, "Iniciando búsqueda de registro...\n", FILE_APPEND);
                $registro = $google_sheets->buscar_registro(
                    $certificado->sheet_id,
                    $certificado->sheet_nombre,
                    $campo_busqueda,
                    $valor_busqueda
                );
                
                if (!$registro) {
                    file_put_contents($log_file, "No se encontraron datos para el valor ingresado\n", FILE_APPEND);
                    wp_send_json_error(array('message' => 'No se encontraron datos para el valor ingresado'));
                    return;
                }
                
                file_put_contents($log_file, "Registro encontrado: " . print_r($registro, true) . "\n", FILE_APPEND);
                
                // Verificar si la clase generadora existe
                if (!class_exists('Certificados_PDF_Generator')) {
                    file_put_contents($log_file, "ERROR CRÍTICO: Clase Certificados_PDF_Generator no encontrada\n", FILE_APPEND);
                    wp_send_json_error(array('message' => 'Error interno: componente generador de PDF no disponible'));
                    return;
                }
                
                // Generar el PDF - OTRO POSIBLE PUNTO DE FALLO
                file_put_contents($log_file, "Inicializando generador de PDF...\n", FILE_APPEND);
                $generator = new Certificados_PDF_Generator();
                
                file_put_contents($log_file, "Generando PDF...\n", FILE_APPEND);
                $pdf_url = $generator->generar_pdf($certificado, $campos, $registro);
                
                if (!$pdf_url) {
                    file_put_contents($log_file, "Error al generar el PDF\n", FILE_APPEND);
                    wp_send_json_error(array('message' => 'Error al generar el certificado PDF'));
                    return;
                }
                
                file_put_contents($log_file, "PDF generado correctamente: $pdf_url\n", FILE_APPEND);
                
                // Enviar respuesta exitosa
                wp_send_json_success(array(
                    'message' => 'Certificado generado correctamente',
                    'pdf_url' => $pdf_url
                ));
                
            } catch (Exception $e) {
                file_put_contents($log_file, "ERROR en búsqueda o generación: " . $e->getMessage() . "\n", FILE_APPEND);
                file_put_contents($log_file, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
                wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
            }
            
        } catch (Exception $e) {
            file_put_contents($log_file, "EXCEPCIÓN GENERAL: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($log_file, "Traza: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            wp_send_json_error(array('message' => 'Error interno: ' . $e->getMessage()));
        }
        
        // Si llegamos hasta aquí, algo falló
        file_put_contents($log_file, "Llegamos al final de la función sin enviar respuesta (esto no debería ocurrir)\n", FILE_APPEND);
        wp_send_json_error(array('message' => 'Error inesperado al procesar la solicitud'));
    }
}