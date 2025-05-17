<?php
/**
 * La funcionalidad específica de administración del plugin.
 * plugins-main/includes/certificados-pdf-admin.php
 *
 * @since      1.0.0
 */



class Certificados_PDF_Admin {

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
        
        // Registrar endpoints AJAX al inicializar la clase
        $this->register_ajax_endpoints();
    }

    /**
     * Registrar todos los endpoints AJAX
     */
    private function register_ajax_endpoints() {
        // Registrar el endpoint para probar conexión con Google Sheets
        add_action('wp_ajax_probar_conexion_sheet', array($this, 'probar_conexion_sheet'));
        // Endpoint para obtener columnas
        add_action('wp_ajax_obtener_columnas_sheet', array($this, 'obtener_columnas_sheet'));
        // También puedes mover aquí los otros endpoints AJAX
        add_action('wp_ajax_guardar_certificado', array($this, 'guardar_certificado'));
        add_action('wp_ajax_obtener_certificado', array($this, 'obtener_certificado'));
        add_action('wp_ajax_eliminar_certificado', array($this, 'eliminar_certificado'));
        
    }

    /**
     * Registrar los estilos para el área de administración.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        if (!isset($screen->id) || strpos($screen->id, 'certificados_pdf') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style($this->plugin_name, CERT_PDF_PLUGIN_URL . 'admin/css/certificados-pdf-admin.css', array(), $this->version, 'all');
    }

    /**
     * Registrar los scripts para el área de administración.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        if (!isset($screen->id) || strpos($screen->id, 'certificados_pdf') === false) {
            return;
        }

        // Cargar los scripts necesarios
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('wp-color-picker');
        
        // Localiza las variables en jQuery
        wp_localize_script('jquery', 'certificados_pdf_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('certificados_pdf_nonce'),
            'plugin_url' => CERT_PDF_PLUGIN_URL,
            'current_page' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
            'current_id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
            'i18n' => array(
                // Traducciones aquí...
            )
        ));
        
        // Script principal de administración para todas las páginas del plugin
        wp_enqueue_script($this->plugin_name, CERT_PDF_PLUGIN_URL . 'admin/js/certificados-pdf-admin.js', array('jquery', 'wp-media-upload'), $this->version, true);
        
        // Mejorada la condición para detectar la página de edición
        $is_editor_page = false;
        if (isset($_GET['page']) && in_array($_GET['page'], array('certificados_pdf_nuevo', 'certificados_pdf_editar'))) {
            $is_editor_page = true;
        } else if ($screen->id && (
            $screen->id === 'certificados-pdf_page_certificados_pdf_nuevo' || 
            $screen->id === 'certificados-pdf_page_certificados_pdf_editar' ||
            strpos($screen->id, 'certificados_pdf_nuevo') !== false ||
            strpos($screen->id, 'certificados_pdf_editar') !== false
        )) {
            $is_editor_page = true;
        }
        
        // Si estamos en la página de edición de certificados
        if ($is_editor_page) {
            // Script específico para el editor - USA UN ARCHIVO DIFERENTE
            wp_enqueue_script(
                $this->plugin_name . '-editor',
                CERT_PDF_PLUGIN_URL . 'admin/js/certificados-pdf-editor.js', // ARCHIVO DIFERENTE
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-resizable', 'wp-color-picker'),
                $this->version,
                true
            );

            // Script para la integración de fuentes con el editor
            wp_enqueue_script(
                $this->plugin_name . '-fonts-editor',
                CERT_PDF_PLUGIN_URL . 'admin/js/certificados-pdf-fonts-editor.js',
                array('jquery', $this->plugin_name . '-editor'), // Depende del editor principal
                $this->version,
                true
            );

                        
            // Obtener campos si estamos editando un certificado
            $campos = array();
            
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                global $wpdb;
                $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
                $campos = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $tabla_campos WHERE certificado_id = %d ORDER BY orden ASC",
                    intval($_GET['id'])
                ));
            }
            
            // Pasar variables específicas al script del editor
            wp_localize_script($this->plugin_name . '-editor', 'certificados_pdf_editor_vars', array(
                'campos' => $campos,
                'api_key' => get_option('certificados_pdf_google_api_key', ''),
                // Otras variables aquí...
            ));

             // Ahora encolar los scripts en el orden correcto
            wp_enqueue_script($this->plugin_name . '-editor');
            wp_enqueue_script($this->plugin_name . '-fonts-editor');

            
        }
    }

    /**
     * Agregar menú de administración
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Certificados PDF', 'certificados-pdf'),
            __('Certificados PDF', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf',
            array($this, 'display_admin_page'),
            'dashicons-media-document',
            30
        );
        
        add_submenu_page(
            'certificados_pdf',
            __('Todos los Certificados', 'certificados-pdf'),
            __('Todos los Certificados', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf',
            array($this, 'display_admin_page')
        );
        
        add_submenu_page(
            'certificados_pdf',
            __('Añadir Nuevo', 'certificados-pdf'),
            __('Añadir Nuevo', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf_nuevo',
            array($this, 'display_new_certificate_page')
        );
        
        // Añadir esta nueva línea para la página de edición
        add_submenu_page(
            null, // Configurarlo como null para ocultarlo del menú
            __('Editar Certificado', 'certificados-pdf'),
            __('Editar Certificado', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf_editar',
            array($this, 'display_new_certificate_page') // Usa la misma función que para nuevo
        );
        
        add_submenu_page(
            'certificados_pdf',
            __('Configuración', 'certificados-pdf'),
            __('Configuración', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf_settings',
            array($this, 'display_settings_page')
        );


        add_submenu_page(
            'certificados_pdf',
            __('Prueba de Conexión', 'certificados-pdf'),
            __('Prueba de Conexión', 'certificados-pdf'),
            'manage_options',
            'certificados_pdf_test_connection',
            array($this, 'display_test_connection_page')
        );
    }

    /**
     * Renderiza la página principal de administración
     *
     * @since    1.0.0
     */
    public function display_admin_page() {
        // Obtener todos los certificados
        global $wpdb;
        $tabla = $wpdb->prefix . 'certificados_pdf_templates';
        $certificados = $wpdb->get_results("SELECT * FROM $tabla ORDER BY id DESC");
        
        include CERT_PDF_PLUGIN_DIR . 'admin/partials/certificados-pdf-admin-display.php';
    }

    /**
     * Renderiza la página para añadir/editar certificados
     *
     * @since    1.0.0
     */
    public function display_new_certificate_page() {
        $certificado = null;
        $campos = array();
        
        // Si hay un ID, cargamos el certificado para editar
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            global $wpdb;
            $tabla = $wpdb->prefix . 'certificados_pdf_templates';
            $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
            
            $certificado = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", intval($_GET['id'])));
            if ($certificado) {
                $campos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla_campos WHERE certificado_id = %d ORDER BY orden ASC", $certificado->id));
            }
        }
        
        include CERT_PDF_PLUGIN_DIR . 'admin/partials/certificados-pdf-admin-edit.php';
    }

    /**
     * Renderiza la página de configuración
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        $google_api_key = get_option('certificados_pdf_google_api_key', '');
        include CERT_PDF_PLUGIN_DIR . 'admin/partials/certificados-pdf-admin-settings.php';
    }

    /**
     * Guarda la información de un certificado
     *
     * @since    1.0.0
     */
    public function guardar_certificado() {
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
            return;
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'certificados_pdf_templates';
        $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = array(
            'nombre' => sanitize_text_field($_POST['nombre']),
            'plantilla_url' => esc_url_raw($_POST['plantilla_url']),
            'sheet_id' => sanitize_text_field($_POST['sheet_id']),
            'sheet_nombre' => sanitize_text_field($_POST['sheet_nombre']),
            'campo_busqueda' => sanitize_text_field($_POST['campo_busqueda']),
            'habilitado' => isset($_POST['habilitado']) ? 1 : 0,
            'fecha_modificacion' => current_time('mysql')
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%s');
        
        try {
            if ($id > 0) {
                // Actualizar certificado existente
                $result = $wpdb->update($tabla, $data, array('id' => $id), $format, array('%d'));
                if ($result === false) {
                    wp_send_json_error(array('message' => __('Error al actualizar el certificado: ', 'certificados-pdf') . $wpdb->last_error));
                    return;
                }
            } else {
                // Crear nuevo certificado
                $data['fecha_creacion'] = current_time('mysql');
                $result = $wpdb->insert($tabla, $data, array_merge($format, array('%s')));
                if ($result === false) {
                    wp_send_json_error(array('message' => __('Error al crear el certificado: ', 'certificados-pdf') . $wpdb->last_error));
                    return;
                }
                $id = $wpdb->insert_id;
            }
            
            // Verificar si hay campos para procesar
            if (!isset($_POST['campos']) || empty($_POST['campos'])) {
                wp_send_json_error(array('message' => __('No se recibieron campos para el certificado', 'certificados-pdf')));
                return;
            }
            
            // Procesar los campos
            if ($id) {
                // Obtener los campos desde POST
                $campos_json = sanitize_text_field($_POST['campos']);
                
                // Decodificar el JSON de campos
                $campos = json_decode(stripslashes($campos_json), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error(array('message' => __('Error al procesar los campos: ', 'certificados-pdf') . json_last_error_msg()));
                    return;
                }
                
                if (is_array($campos) && !empty($campos)) {
                    // Primero eliminamos todos los campos existentes
                    $wpdb->delete($tabla_campos, array('certificado_id' => $id), array('%d'));
                    
                    // Luego insertamos los nuevos campos
                    foreach ($campos as $orden => $campo) {
                        // Verificar que es un array y tiene las propiedades necesarias
                        if (!is_array($campo) || !isset($campo['nombre'])) {
                            continue;
                        }
                        
                        // Determinar la columna_sheet
                        $columna_sheet = '';
                        if (isset($campo['columna_sheet'])) {
                            $columna_sheet = $campo['columna_sheet'];
                        } elseif (isset($campo['columna'])) {
                            $columna_sheet = $campo['columna'];
                        }
                        
                        // Preparar los datos del campo
                        $campo_data = array(
                        'certificado_id' => $id,
                        'nombre' => isset($campo['nombre']) ? sanitize_text_field($campo['nombre']) : '',
                        'tipo' => isset($campo['tipo']) ? sanitize_text_field($campo['tipo']) : 'texto',
                        'columna_sheet' => sanitize_text_field($columna_sheet),
                        'pos_x' => isset($campo['pos_x']) ? intval($campo['pos_x']) : 0,
                        'pos_y' => isset($campo['pos_y']) ? intval($campo['pos_y']) : 0,
                        'ancho' => isset($campo['ancho']) ? intval($campo['ancho']) : 0,
                        'alto' => isset($campo['alto']) ? intval($campo['alto']) : 0,
                        'color' => isset($campo['color']) ? sanitize_hex_color($campo['color']) : '#000000',
                        'tamano_fuente' => isset($campo['tamano_fuente']) ? intval($campo['tamano_fuente']) : 12,
                        'alineacion' => isset($campo['alineacion']) ? sanitize_text_field($campo['alineacion']) : 'left',
                        'tipografia' => isset($campo['tipografia']) ? sanitize_text_field($campo['tipografia']) : 'default',
                        'orden' => intval($orden)
                    );

                    // Y luego necesitas actualizar la llamada a wpdb->insert para incluir el formato para tipografia:
                    $resultado = $wpdb->insert(
                        $tabla_campos,
                        $campo_data,
                        array('%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d')
                        //                                                                 ^^^^ Nuevo formato para tipografia
                    );
                        
                        if ($resultado === false) {
                            // No detener el proceso si falla un campo, solo registrar el error
                            error_log('Error al insertar campo: ' . $wpdb->last_error);
                        }
                    }
                }
            }
            
            // Generamos el shortcode para mostrar en la página de administración
            $shortcode = '[certificado_pdf id="' . $id . '"]';
            
            wp_send_json_success(array(
                'message' => __('Certificado guardado correctamente', 'certificados-pdf'),
                'id' => $id,
                'shortcode' => $shortcode
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error: ', 'certificados-pdf') . $e->getMessage()));
        }
    }

    /**
     * Obtiene la información de un certificado
     *
     * @since    1.0.0
     */
    public function obtener_certificado() {
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID de certificado inválido', 'certificados-pdf')));
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'certificados_pdf_templates';
        $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
        
        $certificado = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $id), ARRAY_A);
        
        if (!$certificado) {
            wp_send_json_error(array('message' => __('Certificado no encontrado', 'certificados-pdf')));
        }
        
        $campos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tabla_campos WHERE certificado_id = %d ORDER BY orden ASC", $id), ARRAY_A);
        
        $certificado['campos'] = $campos;
        $certificado['shortcode'] = '[certificado_pdf id="' . $id . '"]';
        
        wp_send_json_success($certificado);
    }

    /**
     * Elimina un certificado
     *
     * @since    1.0.0
     */
    public function eliminar_certificado() {
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            wp_send_json_error(array('message' => __('ID de certificado inválido', 'certificados-pdf')));
        }
        
        global $wpdb;
        $tabla = $wpdb->prefix . 'certificados_pdf_templates';
        $tabla_campos = $wpdb->prefix . 'certificados_pdf_campos';
        
        // Eliminar certificado y campos relacionados
        $wpdb->delete($tabla_campos, array('certificado_id' => $id), array('%d'));
        $resultado = $wpdb->delete($tabla, array('id' => $id), array('%d'));
        
        if ($resultado === false) {
            wp_send_json_error(array('message' => __('Error al eliminar el certificado', 'certificados-pdf')));
        }
        
        wp_send_json_success(array('message' => __('Certificado eliminado correctamente', 'certificados-pdf')));
    }

    /**
     * Prueba la conexión con Google Sheets
     *
     * @since    1.0.0
     */
    public function probar_conexion_sheets() {
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $sheet_id = sanitize_text_field($_POST['sheet_id']);
        $sheet_nombre = sanitize_text_field($_POST['sheet_nombre']);
        
        if (empty($api_key) || empty($sheet_id) || empty($sheet_nombre)) {
            wp_send_json_error(array('message' => __('Todos los campos son obligatorios', 'certificados-pdf')));
        }
        
        // Crear instancia de Google Sheets
        try {
            $google_sheets = new Certificados_PDF_Google_Sheets($api_key);
            
            // Intentar obtener las columnas
            try {
                $columnas = $google_sheets->obtener_columnas($sheet_id, $sheet_nombre);
                
                if (empty($columnas)) {
                    wp_send_json_error(array('message' => __('No se encontraron columnas en la hoja especificada', 'certificados-pdf')));
                }
                
                // Asegurar que las columnas estén en un formato consistente
                $columnas_normalizadas = array();
                foreach ($columnas as $columna) {
                    $columnas_normalizadas[] = array(
                        'nombre' => isset($columna['nombre']) ? $columna['nombre'] : '',
                        'columna' => isset($columna['columna']) ? $columna['columna'] : ''
                    );
                }
                
                wp_send_json_success(array(
                    'message' => __('Conexión exitosa', 'certificados-pdf'),
                    'columnas' => $columnas_normalizadas
                ));
            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Error al obtener columnas: ' . $e->getMessage()));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error al crear la instancia de Google Sheets: ' . $e->getMessage()));
        }
    }

    /**
     * Prueba la conexión con Google Sheets (alias para compatibilidad)
     *
     * @since    1.0.0
     */
    public function probar_conexion_sheet() {
        // Añadir log para depuración
        error_log('Función probar_conexion_sheet llamada');
        error_log('Datos recibidos: ' . print_r($_POST, true));
        
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
        }
        
        $sheet_id = sanitize_text_field($_POST['sheet_id']);
        $sheet_nombre = sanitize_text_field($_POST['sheet_nombre']);
        
        if (empty($sheet_id) || empty($sheet_nombre)) {
            wp_send_json_error(array('message' => __('ID de hoja y nombre de hoja son obligatorios', 'certificados-pdf')));
        }
        
        // Obtener la API Key de las opciones
        $api_key = get_option('certificados_pdf_google_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API Key de Google no configurada. Por favor, configúrala en Ajustes > Certificados PDF.', 'certificados-pdf')));
        }
        
        // Construir la URL de la API
        $api_url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/{$sheet_nombre}!A1:Z1?key={$api_key}";
        
        // Realizar la solicitud
        $response = wp_remote_get($api_url);
        
        // Verificar si hubo errores en la solicitud
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        // Obtener el código de respuesta HTTP
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Error desconocido al conectar con Google Sheets.', 'certificados-pdf');
            wp_send_json_error(array('message' => $error_message));
        }
        
        // Procesar la respuesta
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Verificar si tenemos valores
        if (!isset($body['values']) || empty($body['values'][0])) {
            wp_send_json_error(array('message' => __('No se encontraron columnas en la hoja especificada.', 'certificados-pdf')));
        }
        
        // Asegurar que las columnas estén en un formato válido
        $columnas = array_map('trim', $body['values'][0]);
        $columnas = array_filter($columnas, 'strlen'); // Eliminar columnas vacías
        
        // Devolver las columnas encontradas
        wp_send_json_success(array('columnas' => $columnas));
    }
        
    /**
     * Renderiza la página de prueba de conexión con Google Sheets
     *
     * @since    1.0.0
     */
    public function display_test_connection_page() {
        // Obtener la API Key de las opciones
        $google_api_key = get_option('certificados_pdf_google_api_key', '');
        ?>
        <div class="wrap certificados-pdf-admin">
            <h1 class="wp-heading-inline"><?php _e('Prueba de Conexión con Google Sheets', 'certificados-pdf'); ?></h1>
            <hr class="wp-header-end">
            
            <div class="notice notice-info">
                <p><?php _e('Esta página te permite probar la conexión con Google Sheets usando tu API Key configurada.', 'certificados-pdf'); ?></p>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px;">
                <h2><?php _e('Configuración de Conexión', 'certificados-pdf'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key"><?php _e('API Key de Google', 'certificados-pdf'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="api_key" class="regular-text" value="<?php echo esc_attr($google_api_key); ?>">
                            <p class="description">
                                <?php _e('Esta es la API Key configurada en la sección de Configuración.', 'certificados-pdf'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sheet_id"><?php _e('ID de Google Sheet', 'certificados-pdf'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sheet_id" class="regular-text" placeholder="1gdKlT6iI-QUCxX5seqmtHjwlSoMo2dGKQQKU7dTofkk">
                            <p class="description">
                                <?php _e('ID de la hoja de cálculo (encontrado en la URL: https://docs.google.com/spreadsheets/d/[ID]/edit)', 'certificados-pdf'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="range"><?php _e('Rango (opcional)', 'certificados-pdf'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="range" class="regular-text" value="A:Z">
                            <p class="description">
                                <?php _e('Rango de celdas a obtener. Por defecto: A:Z (todas las columnas). Puedes especificar una hoja y rango como "Hoja1!A1:D10".', 'certificados-pdf'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <button type="button" id="test-connection" class="button button-primary">
                                <?php _e('Probar Conexión', 'certificados-pdf'); ?>
                            </button>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div id="resultado" style="margin-top: 20px; display: none; max-width: 800px;">
                <h2><?php _e('Resultado', 'certificados-pdf'); ?></h2>
                <div id="estado-conexion" class="notice"></div>
                
                <div class="card" style="margin-top: 10px; padding: 15px;">
                    <h3><?php _e('Datos Obtenidos', 'certificados-pdf'); ?></h3>
                    <div id="tabla-datos" style="overflow-x: auto;"></div>
                </div>
                
                <div class="card" style="margin-top: 10px; padding: 15px;">
                    <h3><?php _e('Respuesta JSON', 'certificados-pdf'); ?></h3>
                    <pre id="respuesta-json" style="background: #f5f5f5; padding: 10px; max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
            
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#test-connection').on('click', function() {
                    var apiKey = $('#api_key').val().trim();
                    var sheetId = $('#sheet_id').val().trim();
                    var range = $('#range').val().trim() || 'A:Z';
                    
                    if (!apiKey) {
                        alert('<?php _e('Por favor, ingresa la API Key de Google.', 'certificados-pdf'); ?>');
                        return;
                    }
                    
                    if (!sheetId) {
                        alert('<?php _e('Por favor, ingresa el ID de la hoja de Google Sheets.', 'certificados-pdf'); ?>');
                        return;
                    }
                    
                    // Mostrar indicador de carga
                    $('#estado-conexion')
                        .attr('class', 'notice notice-info')
                        .html('<p><span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> <?php _e('Conectando con Google Sheets...', 'certificados-pdf'); ?></p>')
                        .show();
                    
                    $('#resultado').show();
                    $('#tabla-datos').empty();
                    $('#respuesta-json').empty();
                    
                    // Hacer consulta directa a la API de Google
                    var url = 'https://sheets.googleapis.com/v4/spreadsheets/' + sheetId + '/values/' + range + '?key=' + apiKey;
                    
                    $.ajax({
                        url: url,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            // Mostrar resultado exitoso
                            $('#estado-conexion')
                                .attr('class', 'notice notice-success')
                                .html('<p><span class="dashicons dashicons-yes"></span> <?php _e('Conexión exitosa! Se han obtenido los datos correctamente.', 'certificados-pdf'); ?></p>');
                            
                            // Mostrar respuesta JSON
                            $('#respuesta-json').text(JSON.stringify(data, null, 2));
                            
                            // Crear tabla con los datos
                            if (data.values && data.values.length > 0) {
                                var table = $('<table class="wp-list-table widefat fixed striped">');
                                var thead = $('<thead>').appendTo(table);
                                var tbody = $('<tbody>').appendTo(table);
                                
                                // Crear encabezados (primera fila)
                                var headerRow = $('<tr>');
                                data.values[0].forEach(function(headerText) {
                                    headerRow.append($('<th>').text(headerText));
                                });
                                thead.append(headerRow);
                                
                                // Crear filas de datos (desde la segunda fila)
                                for (var i = 1; i < data.values.length; i++) {
                                    var dataRow = $('<tr>');
                                    var rowData = data.values[i];
                                    
                                    // Asegurarse de que cada fila tenga el mismo número de columnas
                                    for (var j = 0; j < data.values[0].length; j++) {
                                        dataRow.append($('<td>').text(j < rowData.length ? rowData[j] : ''));
                                    }
                                    
                                    tbody.append(dataRow);
                                }
                                
                                $('#tabla-datos').html(table);
                            } else {
                                $('#tabla-datos').html('<p><?php _e('No se encontraron datos en la hoja especificada.', 'certificados-pdf'); ?></p>');
                            }
                        },
                        error: function(xhr, status, error) {
                            // Mostrar error
                            var errorMsg = '';
                            try {
                                var errorData = JSON.parse(xhr.responseText);
                                errorMsg = errorData.error.message || error;
                            } catch (e) {
                                errorMsg = error || xhr.statusText;
                            }
                            
                            $('#estado-conexion')
                                .attr('class', 'notice notice-error')
                                .html('<p><span class="dashicons dashicons-no"></span> <?php _e('Error de conexión:', 'certificados-pdf'); ?> ' + errorMsg + '</p>');
                            
                            $('#respuesta-json').text(xhr.responseText || 'Error: ' + error);
                        }
                    });
                });
            });
            </script>
            
            <style>
            @keyframes rotation {
                from { transform: rotate(0deg); }
                to { transform: rotate(359deg); }
            }
            </style>
        </div>
        <?php
    }


    /**
     * Obtiene las columnas de Google Sheets para un certificado existente
     * 
     * @since 1.0.0
     */
    public function obtener_columnas_sheet() {
        check_ajax_referer('certificados_pdf_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos para realizar esta acción', 'certificados-pdf')));
        }
        
        $sheet_id = sanitize_text_field($_POST['sheet_id']);
        $sheet_nombre = sanitize_text_field($_POST['sheet_nombre']);
        
        if (empty($sheet_id) || empty($sheet_nombre)) {
            wp_send_json_error(array('message' => __('ID de hoja y nombre de hoja son obligatorios', 'certificados-pdf')));
        }
        
        // Obtener la API Key de las opciones
        $api_key = get_option('certificados_pdf_google_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API Key de Google no configurada', 'certificados-pdf')));
        }
        
        // Construir la URL de la API
        $api_url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheet_id}/values/{$sheet_nombre}!A1:Z1?key={$api_key}";
        
        // Realizar la solicitud
        $response = wp_remote_get($api_url);
        
        // Verificar si hubo errores en la solicitud
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }
        
        // Obtener el código de respuesta HTTP
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Error desconocido al conectar con Google Sheets.', 'certificados-pdf');
            wp_send_json_error(array('message' => $error_message));
        }
        
        // Procesar la respuesta
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Verificar si tenemos valores
        if (!isset($body['values']) || empty($body['values'][0])) {
            wp_send_json_error(array('message' => __('No se encontraron columnas en la hoja especificada.', 'certificados-pdf')));
        }
        
        // Asegurar que las columnas estén en un formato válido
        $columnas = array_map('trim', $body['values'][0]);
        $columnas = array_filter($columnas, 'strlen'); // Eliminar columnas vacías
        
        // Devolver las columnas encontradas
        wp_send_json_success(array('columnas' => $columnas));
    }


}