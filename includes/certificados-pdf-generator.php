<?php
/**
 * Clase para generar certificados en PDF.
 * plugins-main/includes/certificados-pdf-generator.php
 * @since      1.0.0
 */
class Certificados_PDF_Generator {

    /**
     * Constructor de la clase.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Asegurarse de que TCPDF esté disponible
        if (!class_exists('TCPDF')) {
            require_once CERT_PDF_PLUGIN_DIR . 'vendor/autoload.php';
        }
    }

    /**
     * Genera un certificado en PDF.
     *
     * @since    1.0.0
     * @param    object    $certificado    Datos del certificado.
     * @param    array     $campos         Campos del certificado.
     * @param    array     $datos          Datos del usuario/registro.
     * @return   string                   URL del PDF generado o false si hay error.
     */
    public function generar_pdf($certificado, $campos, $datos) {
        // Crear directorio para los PDFs si no existe
        $upload_dir = wp_upload_dir();
        $cert_dir = $upload_dir['basedir'] . '/certificados';
        
        if (!file_exists($cert_dir)) {
            wp_mkdir_p($cert_dir);
            
            // Establecer permisos adecuados
            @chmod($cert_dir, 0755);
            
            // Crear archivo index.php para protección
            file_put_contents($cert_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Generar nombre de archivo único
        $filename = 'certificado_' . $certificado->id . '_' . uniqid() . '.pdf';
        $filepath = $cert_dir . '/' . $filename;
        
        // URL segura a través de serve-pdf.php en lugar de acceso directo
        $fileurl = CERT_PDF_PLUGIN_URL . 'serve-pdf.php?file=' . $filename;
        
        // Verificar la URL de la plantilla
        $imagen_plantilla = $certificado->plantilla_url;
        
        // Si la imagen es una URL externa o relativa al sitio, convertirla a ruta absoluta
        if (filter_var($imagen_plantilla, FILTER_VALIDATE_URL)) {
            // Si es una URL dentro del sitio, obtener la ruta del servidor
            $site_url = site_url();
            if (strpos($imagen_plantilla, $site_url) === 0) {
                $imagen_plantilla = str_replace($site_url, ABSPATH, $imagen_plantilla ?? '');
            } else {
                // Si es una URL externa, descargar la imagen
                $temp_image = download_url($imagen_plantilla);
                if (!is_wp_error($temp_image)) {
                    $imagen_plantilla = $temp_image;
                }
            }
        } else {
            // Si es una ruta relativa al plugin
            if (strpos($imagen_plantilla, CERT_PDF_PLUGIN_URL) === 0) {
                $imagen_plantilla = str_replace(CERT_PDF_PLUGIN_URL, CERT_PDF_PLUGIN_DIR, $imagen_plantilla ?? '');
            }
        }
        
        // Verificar si el archivo existe
        if (!file_exists($imagen_plantilla)) {
            error_log('Certificados PDF: La plantilla no existe en ruta: ' . $imagen_plantilla);
            return false;
        }
        
        // Obtener dimensiones de la imagen de plantilla
        $img_info = getimagesize($imagen_plantilla);
        
        if ($img_info === false) {
            error_log('Certificados PDF: No se pudo obtener información de imagen: ' . $imagen_plantilla);
            return false;
        }
        
        $img_width = $img_info[0];
        $img_height = $img_info[1];
        
        // Calcular relación de aspecto de la imagen
        $img_ratio = $img_width / $img_height;
        
        // Establecer tamaño para una hoja A4 horizontal en puntos (1 pt = 1/72 pulgadas)
        // A4 = 210mm × 297mm = 595pt × 842pt (en vertical/portrait)
        // A4 horizontal (landscape) = 842pt × 595pt
        $page_width = 842;
        $page_height = 595;
        
        // Factor de escala para convertir de píxeles a puntos
        // Necesitamos un factor de escala para todas las posiciones y dimensiones
        $scale_factor = $page_width / $img_width;
        
        // Crear instancia de TCPDF con unidades en puntos (pt)
        $pdf = new TCPDF('L', 'pt', 'A4', true, 'UTF-8', false);
        
        // Configuración del PDF
        $pdf->SetCreator('Certificados PDF Plugin');
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle($certificado->nombre);
        $pdf->SetSubject($certificado->nombre);
        $pdf->SetKeywords('certificado, pdf');
        
        // Eliminar encabezado y pie de página
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Establecer márgenes
        $pdf->SetMargins(0, 0, 0);
        // Desactivar auto-page-break
        $pdf->SetAutoPageBreak(false, 0);
        
        // Añadir página
        $pdf->AddPage('L', 'A4'); // Forzar orientación horizontal
        
        // Añadir imagen de fondo ajustada a la página completa
        $pdf->Image($imagen_plantilla, 0, 0, $page_width, $page_height, '', '', '', false, 300, '', false, false, 0);
        
        // Procesar cada campo
        foreach ($campos as $campo) {
            // Verificar si el campo tiene una columna en Google Sheets mapeada
            if (empty($campo->columna_sheet) || !isset($datos[$campo->columna_sheet])) {
                continue;
            }
            
            // Obtener valor del campo desde los datos
            $valor = $datos[$campo->columna_sheet];
            
            // Formatear los valores según el tipo de campo
            if ($campo->tipo === 'fecha' && !empty($valor)) {
                // Intentar convertir formato de fecha
                $timestamp = strtotime($valor);
                if ($timestamp !== false) {
                    $valor = date_i18n(get_option('date_format'), $timestamp);
                }
            } elseif ($campo->tipo === 'numero' && is_numeric($valor)) {
                // Formatear número según configuración regional
                $valor = number_format_i18n($valor);
            }
            
            // Configurar fuente y tipografía
            $fontname = 'helvetica'; // Tipografía predeterminada
            $fontstyle = '';
            
            // Usar tipografía personalizada si está definida
            if (!empty($campo->tipografia) && $campo->tipografia !== 'default') {
                // Comprobar si es una fuente del sistema o personalizada
                $system_fonts = array('Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana', 'Tahoma', 'Georgia');
                
                if (in_array($campo->tipografia, $system_fonts)) {
                    // Mapear fuentes del sistema a las disponibles en TCPDF
                    switch($campo->tipografia) {
                        case 'Arial':
                        case 'Helvetica':
                            $fontname = 'helvetica';
                            break;
                        case 'Times New Roman':
                            $fontname = 'times';
                            break;
                        case 'Courier New':
                            $fontname = 'courier';
                            break;
                        case 'Verdana':
                        case 'Tahoma':
                            $fontname = 'freesans';
                            break;
                        case 'Georgia':
                            $fontname = 'freeserif';
                            break;
                        default:
                            $fontname = 'helvetica';
                    }
                } else {
                    // Es una fuente personalizada, verificar si existe
                    $font_file = CERT_PDF_FONTS_DIR . '/' . $campo->tipografia . '.ttf';
                    
                    if (file_exists($font_file)) {
                        // Registrar la fuente en TCPDF
                        $font_params = array(
                            'name' => $campo->tipografia,
                            'path' => dirname($font_file) . '/',
                            'file' => basename($font_file)
                        );
                        
                        try {
                            // Intentar añadir la fuente a TCPDF directamente
                            $fontname = TCPDF_FONTS::addTTFfont($font_file, 'TrueTypeUnicode', '', 96);
                            
                            if (!$fontname) {
                                // Si falla el método estándar, intentar con un enfoque alternativo
                                $pdf->AddFont($campo->tipografia, '', $font_file, true);
                                $fontname = $campo->tipografia;
                            }
                            
                            // Registrar el uso de la fuente para depuración
                            error_log('Certificados PDF: Fuente cargada: ' . $fontname . ' desde ' . $font_file);
                        } catch (Exception $e) {
                            // Si hay un error al cargar la fuente, registrarlo y usar la fuente predeterminada
                            error_log('Certificados PDF: Error al cargar fuente personalizada: ' . $font_file . ' - ' . $e->getMessage());
                            $fontname = 'helvetica';
                        }
                    } else {
                        // Si el archivo no existe, intentar en el directorio del plugin
                        $plugin_font_file = CERT_PDF_PLUGIN_DIR . 'public/fonts/' . $campo->tipografia . '.ttf';
                        
                        if (file_exists($plugin_font_file)) {
                            try {
                                $fontname = TCPDF_FONTS::addTTFfont($plugin_font_file, 'TrueTypeUnicode', '', 96);
                                
                                if (!$fontname) {
                                    $pdf->AddFont($campo->tipografia, '', $plugin_font_file, true);
                                    $fontname = $campo->tipografia;
                                }
                                
                                error_log('Certificados PDF: Fuente cargada del directorio del plugin: ' . $fontname);
                            } catch (Exception $e) {
                                error_log('Certificados PDF: Error al cargar fuente del plugin: ' . $plugin_font_file . ' - ' . $e->getMessage());
                                $fontname = 'helvetica';
                            }
                        } else {
                            error_log('Certificados PDF: Archivo de fuente no encontrado: ' . $font_file . ' ni ' . $plugin_font_file);
                            $fontname = 'helvetica';
                        }
                    }
                }
            }
            
            // Calcular el tamaño de fuente escalado (asegurar valor mínimo para legibilidad)
            $font_size = max(8, $campo->tamano_fuente * $scale_factor);
            
            // Configurar la fuente en el PDF con registro de depuración
            $pdf->SetFont($fontname, $fontstyle, $font_size);
            error_log("Certificados PDF: Fuente aplicada - Nombre: {$fontname}, Estilo: {$fontstyle}, Tamaño: {$font_size}");
            
            $pdf->SetTextColor(
                hexdec(substr($campo->color, 1, 2)), 
                hexdec(substr($campo->color, 3, 2)), 
                hexdec(substr($campo->color, 5, 2))
            );
            
            // Determinar alineación
            $align = 'L'; // Left por defecto
            if ($campo->alineacion == 'center') {
                $align = 'C';
            } elseif ($campo->alineacion == 'right') {
                $align = 'R';
            }
            
            // Escalar las coordenadas y dimensiones
            $pos_x = $campo->pos_x * $scale_factor;
            $pos_y = $campo->pos_y * $scale_factor;
            $ancho = ($campo->ancho > 0) ? $campo->ancho * $scale_factor : 0;
            $alto = ($campo->alto > 0) ? $campo->alto * $scale_factor : 0;
            
            // Escribir texto
            $pdf->SetXY($pos_x, $pos_y);
            
            // Si el ancho es 0, usar un método diferente
            if ($ancho == 0) {
                $pdf->Cell(0, $alto, $valor, 0, 0, $align);
            } else {
                $pdf->MultiCell(
                    $ancho, 
                    $alto, 
                    $valor, 
                    0, 
                    $align, 
                    false, 
                    1, 
                    $pos_x, 
                    $pos_y, 
                    true, 
                    0, 
                    false, 
                    true, 
                    0, 
                    'T', 
                    false
                );
            }
        }
        
        // Añadir información de metadatos al PDF
        $pdf->SetCreator('Plugin Certificados PDF v' . CERT_PDF_VERSION);
        $pdf->SetAuthor(get_bloginfo('name'));
        $pdf->SetTitle($certificado->nombre);
        $pdf->SetSubject('Certificado: ' . $certificado->nombre);
        $pdf->SetKeywords('certificado, pdf, ' . $certificado->nombre);
        
        // Generar PDF en el servidor
        try {
            $pdf->Output($filepath, 'F');
            
            // Establecer permisos adecuados para el archivo generado
            @chmod($filepath, 0644);
            
            // Verificar si se generó correctamente
            if (file_exists($filepath)) {
                error_log('Certificados PDF: Archivo generado correctamente en ' . $filepath);
                
                // Registrar el certificado generado en la base de datos para seguimiento
                $this->registrar_certificado_generado($certificado->id, $filename, $datos[$certificado->campo_busqueda]);
                return $fileurl;
            } else {
                error_log('Certificados PDF: Error - Archivo no encontrado después de generación: ' . $filepath);
            }
        } catch (Exception $e) {
            error_log('Certificados PDF: Error al generar PDF - ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Registra el certificado generado en la base de datos para seguimiento.
     *
     * @since    1.0.0
     * @param    int      $certificado_id  ID del certificado.
     * @param    string   $filename        Nombre del archivo generado.
     * @param    string   $identificador   Valor del campo de búsqueda.
     */
    private function registrar_certificado_generado($certificado_id, $filename, $identificador) {
        global $wpdb;
        
        // Tabla para registros de certificados generados
        $tabla_registros = $wpdb->prefix . 'certificados_pdf_registros';
        
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_registros'") != $tabla_registros) {
            // Crear la tabla si no existe
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $tabla_registros (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                certificado_id mediumint(9) NOT NULL,
                identificador varchar(255) NOT NULL,
                filename varchar(255) NOT NULL,
                fecha_generacion datetime NOT NULL,
                ip varchar(45) NOT NULL,
                user_agent text,
                PRIMARY KEY  (id),
                KEY certificado_id (certificado_id),
                KEY identificador (identificador)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
        }
        
        // Insertar registro
        $wpdb->insert(
            $tabla_registros,
            array(
                'certificado_id' => $certificado_id,
                'identificador' => $identificador,
                'filename' => $filename,
                'fecha_generacion' => current_time('mysql'),
                'ip' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Obtiene la dirección IP del cliente.
     *
     * @since    1.0.0
     * @return   string    Dirección IP del cliente.
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }
}