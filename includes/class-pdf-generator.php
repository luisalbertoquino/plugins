<?php
/**
 * Clase para generar certificados en PDF
 * 
 * @package Certificados_Digitales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Requerir la librería Html2Pdf
require_once CERTIFICADOS_DIGITALES_PATH . 'vendor/autoload.php';

use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Certificados_PDF_Generator {

    /**
     * ID de la pestaña
     */
    private $pestana_id;

    /**
     * Datos de la pestaña
     */
    private $pestana;

    /**
     * Datos del participante desde Google Sheets
     */
    private $participante;

    /**
     * Campos configurados
     */
    private $campos;

    /**
     * Constructor
     * 
     * @param int $pestana_id ID de la pestaña
     */
    public function __construct( $pestana_id ) {
        global $wpdb;
        
        $this->pestana_id = $pestana_id;
        
        // Obtener datos de la pestaña
        $this->pestana = $wpdb->get_row( 
            $wpdb->prepare( 
                "SELECT * FROM {$wpdb->prefix}certificados_pestanas WHERE id = %d", 
                $pestana_id 
            ),
            ARRAY_A
        );

        if ( ! $this->pestana ) {
            throw new Exception( __( 'Pestaña no encontrada.', 'certificados-digitales' ) );
        }

        // Obtener campos configurados
        $this->campos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_campos_config WHERE pestana_id = %d ORDER BY id ASC",
                $pestana_id
            ),
            ARRAY_A
        );
    }

    /**
     * Buscar participante en Google Sheets con caché inteligente
     *
     * @param string $numero_documento Número de documento
     * @return bool True si se encontró
     */
    public function buscar_participante( $numero_documento ) {
        global $wpdb;

        // Obtener evento y configuración
        $evento = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                $this->pestana['evento_id']
            ),
            ARRAY_A
        );

        if ( ! $evento ) {
            return false;
        }

        // Obtener API Key
        $api_key = get_option( 'certificados_digitales_api_key', '' );

        if ( empty( $api_key ) ) {
            throw new Exception( __( 'API Key de Google no configurada.', 'certificados-digitales' ) );
        }

        // Usar Sheets Cache Manager para obtener datos con detección de cambios
        require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-sheets-cache-manager.php';
        require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-google-sheets.php';

        $cache_manager = new Certificados_Sheets_Cache_Manager();

        // Obtener datos del sheet con caché inteligente (detecta cambios automáticamente)
        $sheet_data = $cache_manager->get_sheet_data_cached(
            $evento['sheet_id'],
            $this->pestana['nombre_hoja_sheet'],
            $api_key,
            false // No forzar refresh, usar detección automática
        );

        // Si hubo error al obtener datos
        if ( is_wp_error( $sheet_data ) ) {
            throw new Exception( $sheet_data->get_error_message() );
        }

        // Si no hay datos
        if ( empty( $sheet_data ) ) {
            return false;
        }

        // Intentar buscar con mapeo de columnas personalizado primero
        require_once CERTIFICADOS_DIGITALES_PATH . 'includes/class-column-mapper.php';
        $mapper = new Certificados_Column_Mapper();

        $this->participante = $mapper->search_with_mapping(
            $evento['sheet_id'],
            $this->pestana['nombre_hoja_sheet'],
            $api_key,
            $evento['id'],
            $numero_documento
        );

        // Si no hay mapeo configurado o no se encontró, usar búsqueda tradicional
        if ( $this->participante === null ) {
            $sheets = new Certificados_Google_Sheets( $api_key, $evento['sheet_id'] );
            $this->participante = $sheets->buscar_en_datos( $sheet_data, $numero_documento );
        }

        return $this->participante !== null;
    }

    /**
     * Generar el PDF del certificado
     * 
     * @return string Ruta del archivo PDF generado
     */
    public function generar_pdf() {
        if ( ! $this->participante ) {
            throw new Exception( __( 'No hay datos del participante.', 'certificados-digitales' ) );
        }

        // Obtener URL de la plantilla
        $plantilla_url = $this->pestana['plantilla_url'];
        
        if ( empty( $plantilla_url ) ) {
            throw new Exception( __( 'Plantilla no encontrada.', 'certificados-digitales' ) );
        }

        // Convertir URL a ruta del sistema
        $plantilla_path = $this->url_to_path( $plantilla_url );

        if ( ! file_exists( $plantilla_path ) ) {
            throw new Exception( __( 'El archivo de plantilla no existe en el servidor.', 'certificados-digitales' ) );
        }

        // Obtener dimensiones naturales de la imagen
        $image_info = getimagesize( $plantilla_path );
        if ( ! $image_info ) {
            throw new Exception( __( 'No se pudieron obtener las dimensiones de la plantilla.', 'certificados-digitales' ) );
        }
        $img_width_px_natural = $image_info[0];
        $img_height_px_natural = $image_info[1];

        // Intentar recuperar las dimensiones mostradas en el editor
        $display_info = get_option( 'certificados_plantilla_display_' . $this->pestana_id );

        if ( is_array( $display_info ) && ! empty( $display_info['width'] ) && ! empty( $display_info['height'] ) ) {
            $img_width_px = intval( $display_info['width'] );
            $img_height_px = intval( $display_info['height'] );
        } else {
            $img_width_px = $img_width_px_natural;
            $img_height_px = $img_height_px_natural;
        }

        // Convertir píxeles a milímetros usando 96 DPI (pantalla estándar)
        $img_width_mm = ($img_width_px / 96) * 25.4;
        $img_height_mm = ($img_height_px / 96) * 25.4;

        // Determinar orientación basada en la imagen original
        $is_landscape = $img_width_px > $img_height_px;

        // Dimensiones A4 según orientación (mm)
        if ($is_landscape) {
            $max_width = 297; // A4 landscape width
            $max_height = 210;
        } else {
            $max_width = 210; // A4 portrait width
            $max_height = 297;
        }

        // Calcular escala para que la imagen quepa en A4, pero NO agrandar si es más pequeña
        $scale_width = $max_width / $img_width_mm;
        $scale_height = $max_height / $img_height_mm;
        $scale_to_fit = min($scale_width, $scale_height);
        // No agrandar la imagen: si scale_to_fit > 1, mantener 1
        $scale = min(1, $scale_to_fit);

        $final_width_mm = $img_width_mm * $scale;
        $final_height_mm = $img_height_mm * $scale;

        try {
            // Crear PDF con tamaño igual a la imagen final (evita fondo de página)
            require_once CERTIFICADOS_DIGITALES_PATH . 'vendor/tecnickcom/tcpdf/tcpdf.php';
            
            // Determinar orientación para TCPDF
            $orientation = $is_landscape ? 'L' : 'P';
            // Usar tamaño de página igual al tamaño final de la imagen
            $pdf = new TCPDF($orientation, 'mm', array($final_width_mm, $final_height_mm), true, 'UTF-8', false);
            
            // Configurar documento
            $pdf->SetCreator('Certificados Digitales');
            $pdf->SetAuthor('Sistema de Certificados');
            $pdf->SetTitle('Certificado');
            
            // Quitar header y footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Márgenes en 0 para cubrir toda la hoja
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            
            // Agregar página
            $pdf->AddPage();
            
            // Posición inicial en 0,0 para cubrir toda la página
            $pos_x = 0;
            $pos_y = 0;
                        
            // Insertar imagen de fondo
            $pdf->Image(
                $plantilla_path,  // Ruta de la imagen
                $pos_x,           // X
                $pos_y,           // Y
                $final_width_mm,  // Ancho escalado
                $final_height_mm, // Alto escalado
                '',               // Tipo (auto-detectar)
                '',               // Link
                '',               // Align
                false,            // Resize
                300,              // DPI
                '',               // Palign
                false,            // Ismask
                false,            // Imgmask
                0,                // Border
                false,            // Fitbox
                false,            // Hidden
                true              // Fitonpage
            );
            
            // Las coordenadas de los campos están en porcentaje del canvas mostrado en admin
            // Necesitamos las dimensiones DEL CANVAS (antes de escalar), no las finales
            $canvas_width_mm = ($img_width_px / 96) * 25.4;  // Dimensiones del canvas en mm
            $canvas_height_mm = ($img_height_px / 96) * 25.4;

            // Agregar campos de texto
            $this->agregar_campos_tcpdf($pdf, $canvas_width_mm, $canvas_height_mm, $scale, $pos_x, $pos_y);
            
            // Configurar protección
            $pdf->SetProtection(array(), '', null, 0);
            
            // Crear directorio de PDFs
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/certificados-digitales/pdfs';
            
            if ( ! file_exists( $pdf_dir ) ) {
                wp_mkdir_p( $pdf_dir );
            }

            // Obtener nombre del evento
            global $wpdb;
            $evento = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT nombre FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                    $this->pestana['evento_id']
                ),
                ARRAY_A
            );

            $evento_nombre = isset($evento['nombre']) ? sanitize_file_name($evento['nombre']) : 'evento';
            $participante_nombre = isset($this->participante['nombre']) ? sanitize_file_name($this->participante['nombre']) : 'participante';

            // Limpiar nombres
            $evento_nombre = $this->limpiar_nombre_archivo($evento_nombre);
            $participante_nombre = $this->limpiar_nombre_archivo($participante_nombre);

            // Nombre del archivo
            $filename = $evento_nombre . '-' . $participante_nombre . '.pdf';
            $filepath = $pdf_dir . '/' . $filename;

            // Guardar PDF
            $pdf->Output($filepath, 'F');

            return $filepath;

        } catch ( Exception $e ) {
            throw new Exception( 
                sprintf( 
                    __( 'Error al generar PDF: %s', 'certificados-digitales' ),
                    $e->getMessage() 
                )
            );
        }
    }


    /**
     * Agregar código QR con TCPDF
     * 
     * @param TCPDF $pdf Instancia de TCPDF
     * @param array $campo Datos del campo
     * @param float $original_width Ancho original en mm
     * @param float $original_height Alto original en mm
     * @param float $scale Factor de escala
     * @param float $offset_x Desplazamiento X
     * @param float $offset_y Desplazamiento Y
     */
    private function agregar_qr_tcpdf($pdf, $campo, $original_width, $original_height, $scale = 1, $offset_x = 0, $offset_y = 0) {
        global $wpdb;

        // Obtener evento
        $evento = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                $this->pestana['evento_id']
            ),
            ARRAY_A
        );

        // Generar URL de validación
        // Si existe url_validacion, usarla; si no, buscar página con shortcode del evento
        if ( !empty($evento['url_validacion']) ) {
            $url_base = $evento['url_validacion'];
        } else {
            // Buscar todas las páginas que contengan shortcodes de certificados
            global $wpdb;
            $shortcode_patterns = array(
                '[certificados_evento evento_id="' . $evento['id'] . '"]',
                '[certificados_' // Para shortcodes dinámicos
            );

            $url_base = home_url();

            // Buscar en todas las páginas publicadas
            $pages = $wpdb->get_results(
                "SELECT ID, post_content FROM {$wpdb->posts}
                WHERE post_type = 'page'
                AND post_status = 'publish'
                AND (post_content LIKE '%[certificados_evento%' OR post_content LIKE '%[certificados_%')",
                ARRAY_A
            );

            foreach ( $pages as $page ) {
                // Verificar si contiene el shortcode del evento
                if (
                    strpos($page['post_content'], '[certificados_evento evento_id="' . $evento['id'] . '"]') !== false ||
                    strpos($page['post_content'], "[certificados_evento evento_id='" . $evento['id'] . "']") !== false
                ) {
                    $url_base = get_permalink($page['ID']);
                    break;
                }

                // Verificar shortcodes dinámicos
                $shortcode_slug = get_option('certificados_shortcode_' . $evento['id'], '');
                if ( !empty($shortcode_slug) && strpos($page['post_content'], '[certificados_' . $shortcode_slug) !== false ) {
                    $url_base = get_permalink($page['ID']);
                    break;
                }
            }
        }

        $url_validacion = add_query_arg(array(
            'validar' => '1',
            'doc' => $this->participante['numero_documento'],
            'pestana' => $this->pestana_id
        ), $url_base);
        
        // Offsets de calibración (ajusta estos valores según necesites)
        $calibracion_offset_x = 0; // mm (positivo = mover a la derecha)
        $calibracion_offset_y = 0; // mm (positivo = mover abajo)

        // Calcular posición escalada con calibración
        $x = ($campo['posicion_left'] / 100) * $original_width * $scale + $offset_x + $calibracion_offset_x;
        $y = ($campo['posicion_top'] / 100) * $original_height * $scale + $offset_y + $calibracion_offset_y;
        
        // Tamaño del QR escalado (aumentado de 25 a 40)
        // Obtener tamaño configurado o usar por defecto
        $qr_size_config = isset($campo['qr_size']) ? intval($campo['qr_size']) : 20;
        $qr_size = $qr_size_config * $scale;
        
        // Agregar QR code con TCPDF
        $style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1
        );
        
        $pdf->write2DBarcode($url_validacion, 'QRCODE,L', $x, $y, $qr_size, $qr_size, $style, 'N');
    }


    /**
     * Convertir color hexadecimal a RGB
     * 
     * @param string $hex Color en formato hexadecimal
     * @return array Array con r, g, b
     */
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }


    /**
     * Agregar campos de texto sobre el PDF con TCPDF
     * 
     * @param TCPDF $pdf Instancia de TCPDF
     * @param float $original_width Ancho original en mm
     * @param float $original_height Alto original en mm
     * @param float $scale Factor de escala
     * @param float $offset_x Desplazamiento X
     * @param float $offset_y Desplazamiento Y
     */
    private function agregar_campos_tcpdf($pdf, $original_width, $original_height, $scale = 1, $offset_x = 0, $offset_y = 0) {
        foreach ($this->campos as $campo) {
            $tipo = $campo['campo_tipo'];

            // Si es QR, agregar código QR
            if ($tipo === 'qr') {
                $this->agregar_qr_tcpdf($pdf, $campo, $original_width, $original_height, $scale, $offset_x, $offset_y);
                continue;
            }

            // Obtener texto del campo
            $texto = $this->obtener_texto_campo($tipo);

            if (empty($texto)) {
                continue;
            }

            // Calcular posición basado en porcentaje de la imagen original (coordenada del contenedor/box)
            $box_x = ($campo['posicion_left'] / 100) * $original_width * $scale + $offset_x;
            $box_y = ($campo['posicion_top'] / 100) * $original_height * $scale + $offset_y;

            // Obtener fuente TCPDF y tamaño
            $font_family = $this->mapear_fuente_tcpdf($campo['font_family']);

            // Convertir font_size de px a pt (1pt = 1.333px aprox)
            // La fórmula correcta es: pt = px * 0.75
            $font_size_pt = floatval($campo['font_size']) * 0.75;

            // Determinar estilo de fuente (normal, bold, italic, bold-italic)
            $font_style = isset($campo['font_style']) ? $campo['font_style'] : 'normal';
            $tcpdf_style = $this->mapear_estilo_fuente($font_style);

            $pdf->SetFont($font_family, $tcpdf_style, $font_size_pt);

            // Configurar color (convertir hex a RGB)
            $color = $this->hex_to_rgb($campo['color']);
            $pdf->SetTextColor($color['r'], $color['g'], $color['b']);

            // Alineación (TCPDF usa 'L','C','R')
            $align_map = array('left' => 'L', 'center' => 'C', 'right' => 'R');
            $align = isset($align_map[$campo['alineacion']]) ? $align_map[$campo['alineacion']] : 'L';

            // CSS del admin: border:2px; padding: 8px 12px; min-width:100px;
            // Calcular ancho del texto en TCPDF
            $text_width_mm = $pdf->GetStringWidth($texto, $font_family, $tcpdf_style, $font_size_pt);

            // Agregar padding horizontal (2mm por lado)
            $padding_mm = 2;
            $box_width_mm = $text_width_mm + ($padding_mm * 2);

            // Mínimo de 20mm
            if ($box_width_mm < 20) {
                $box_width_mm = 20;
            }

            // Altura de línea en mm a partir de puntos
            // Altura de línea: usar el tamaño de fuente en pt convertido a mm
            $line_height_mm = ($font_size_pt / 72) * 25.4;

            // Dar un mínimo razonable
            if ($line_height_mm < 3) {
                $line_height_mm = 3;
            }

            // CORRECCIÓN DE ALINEACIÓN:
            // La coordenada guardada representa el punto de referencia visual del campo
            // Pero TCPDF siempre espera la esquina superior izquierda de la caja
            // Debemos ajustar según la alineación:

            $pdf_box_x = $box_x;
            $pdf_box_y = $box_y;

            // Offsets de calibración para compensar padding/border del configurador
            // Padding del configurador (12px horizontal) → 3.175mm, Padding PDF (2mm) → diferencia ~1.2mm
            $calibracion_horizontal = 20.5; // mm (ajustar si es necesario)
            $calibracion_vertical = 2.0; // mm - Ajusta este valor para mover hacia arriba (-) o abajo (+)

            if ($align === 'C') {
                // Center: restar la mitad del ancho para que el centro quede en la posición guardada
                $pdf_box_x = $box_x - ($box_width_mm / 2) + $calibracion_horizontal;
            } elseif ($align === 'R') {
                // Right: restar el ancho completo para que el borde derecho quede en la posición guardada
                $pdf_box_x = $box_x - $box_width_mm + ($calibracion_horizontal * 2);
            } else {
                // Left: aplicar calibración directamente
                $pdf_box_x = $box_x + $calibracion_horizontal;
            }

            // Aplicar calibración vertical (igual para todas las alineaciones)
            $pdf_box_y = $box_y + $calibracion_vertical;

            // Colocar el texto dentro de la caja respetando padding y alineación
            // Colocar texto (soporta saltos de línea)
            $pdf->SetXY($pdf_box_x, $pdf_box_y);

            // Si el texto tiene saltos de línea, usar MultiCell
            if (strpos($texto, "\n") !== false) {
                $pdf->MultiCell($box_width_mm, $line_height_mm, $texto, 0, $align, false, 1, '', '', true, 0, false, true, 0, 'T', false);
            } else {
                $pdf->Cell($box_width_mm, $line_height_mm, $texto, 0, 0, $align, false, '', 0, false, 'T', 'T');
            }
        }
    }




    /**
     * Generar HTML del certificado
     * 
     * @param string $plantilla_path Ruta del sistema de archivos de la plantilla
     * @param float $img_width_mm Ancho de la imagen en mm
     * @param float $img_height_mm Alto de la imagen en mm
     * @return string HTML generado
     */
    private function generar_html( $plantilla_path, $img_width_mm, $img_height_mm ) {
        // Iniciar HTML
        $html = '<page backcolor="#ffffff" style="margin: 0; padding: 0;">';
        $html .= '<div style="position: relative; width: ' . $img_width_mm . 'mm; height: ' . $img_height_mm . 'mm; margin: 0; padding: 0; overflow: hidden;">';
        
        // Imagen de fondo
        $html .= '<img src="' . $plantilla_path . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;">';

        // Agregar campos
        foreach ( $this->campos as $campo ) {
            $html .= $this->generar_campo_html( $campo, $img_width_mm, $img_height_mm );
        }

        $html .= '</div>';
        $html .= '</page>';

        return $html;
    }

    /**
     * Generar HTML de un campo específico
     * 
     * @param array $campo Datos del campo
     * @param float $container_width Ancho del contenedor en mm
     * @param float $container_height Alto del contenedor en mm
     * @return string HTML del campo
     */
    private function generar_campo_html( $campo, $container_width, $container_height ) {
        $tipo = $campo['campo_tipo'];
        $texto = $this->obtener_texto_campo( $tipo );

        // Si es QR, generar código QR
        if ( $tipo === 'qr' ) {
            return $this->generar_qr_html( $campo, $container_width, $container_height );
        }

        // Calcular posición en mm
        $top_mm = ( $campo['posicion_top'] / 100 ) * $container_height;
        $left_mm = ( $campo['posicion_left'] / 100 ) * $container_width;

        // Cargar fuente si está configurada
        $font_family = 'helvetica';
        if ( ! empty( $campo['font_family'] ) ) {
            $font_family = $this->mapear_fuente_tcpdf( $campo['font_family'] );
            
            // DEBUG - escribir en archivo específico
            $log_file = CERTIFICADOS_DIGITALES_PATH . 'debug-fuentes.txt';
            $debug_msg = "\n========== DEBUG FUENTE ==========\n";
            $debug_msg .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
            $debug_msg .= "Campo: " . $campo['campo_tipo'] . "\n";
            $debug_msg .= "Fuente en BD: " . $campo['font_family'] . "\n";
            $debug_msg .= "Fuente mapeada: " . $font_family . "\n";
            $debug_msg .= "===================================\n";
            file_put_contents($log_file, $debug_msg, FILE_APPEND);
        }
        $html = '<div style="';
        $html .= 'position: absolute;';
        $html .= 'top: ' . $top_mm . 'mm;';
        $html .= 'left: ' . $left_mm . 'mm;';
        // Usar 'px' en el HTML para que la representación en el renderizador HTML coincida con la vista previa administrativa
        $html .= 'font-size: ' . $campo['font_size'] . 'px;';
        $html .= 'color: ' . $campo['color'] . ';';
        $html .= 'font-family: \'' . $font_family . '\';';
        $html .= 'text-align: ' . $campo['alineacion'] . ';';
        $html .= 'z-index: 10;';
        $html .= 'max-width: 80%;';
        $html .= '">';
        $html .= htmlspecialchars( $texto, ENT_QUOTES, 'UTF-8' );
        $html .= '</div>';

        return $html;
    }

    /**
     * Generar HTML del código QR
     * 
     * @param array $campo Datos del campo
     * @param float $container_width Ancho del contenedor en mm
     * @param float $container_height Alto del contenedor en mm
     * @return string HTML del QR
     */
    private function generar_qr_html( $campo, $container_width, $container_height ) {
        global $wpdb;

        // Obtener evento
        $evento = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}certificados_eventos WHERE id = %d",
                $this->pestana['evento_id']
            ),
            ARRAY_A
        );

        // Generar URL de validación
        $url_validacion = ! empty( $evento['url_validacion'] ) ? $evento['url_validacion'] : home_url( '/validar-certificado/' );
        $url_validacion = add_query_arg( array(
            'doc' => $this->participante['numero_documento'],
            'evento' => $evento['id'],
            'pestana' => $this->pestana_id
        ), $url_validacion );

        // Generar QR con librería local
        try {
            $qr_image_base64 = $this->generar_qr_base64( $url_validacion );
            
            // Calcular posición en mm
            $top_mm = ( $campo['posicion_top'] / 100 ) * $container_height;
            $left_mm = ( $campo['posicion_left'] / 100 ) * $container_width;
            
            // Tamaño del QR (por defecto 15mm x 15mm)
            $qr_size = 25;

            $html = '<img src="' . $qr_image_base64 . '" style="';
            $html .= 'position: absolute;';
            $html .= 'top: ' . $top_mm . 'mm;';
            $html .= 'left: ' . $left_mm . 'mm;';
            $html .= 'width: ' . $qr_size . 'mm;';
            $html .= 'height: ' . $qr_size . 'mm;';
            $html .= 'z-index: 10;';
            $html .= '">';

            return $html;
            
        } catch ( Exception $e ) {
            error_log( 'Error al generar QR: ' . $e->getMessage() );
            return ''; // Devolver vacío si falla
        }
    }

    /**
     * Generar código QR en formato base64
     * 
     * @param string $data Datos a codificar en el QR
     * @return string Imagen en formato base64
     */
    private function generar_qr_base64( $data ) {
        // Usar librería Endroid QR Code v3.x
        $qr_code = new QrCode( $data );
        $qr_code->setSize( 400 );
        $qr_code->setMargin( 20 );

        // Generar imagen PNG
        $writer = new PngWriter();
        $qr_image = $writer->writeString( $qr_code );

        // Convertir a base64
        $base64 = base64_encode( $qr_image );

        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Obtener texto de un campo según su tipo
     * 
     * @param string $tipo Tipo de campo
     * @return string Texto del campo
     */
    private function obtener_texto_campo( $tipo ) {
        switch ( $tipo ) {
            case 'nombre':
                $nombre = $this->participante['nombre'] ?? '';
                $nombre = $this->sanitizar_campo_texto($nombre, 100);
                // Convertir a formato título (primera letra mayúscula de cada palabra)
                return $this->convertir_a_titulo($nombre);

            case 'documento':
                // Intentar obtener tipo_documento con varias variantes
                $tipo_doc = '';
                $posibles_tipo = array('tipo_documento', 'tipo_de_documento', 'tipodocumento', 'tipo');
                foreach ($posibles_tipo as $key) {
                    if (isset($this->participante[$key]) && !empty(trim($this->participante[$key]))) {
                        $tipo_doc = $this->sanitizar_campo_texto(trim($this->participante[$key]), 10);
                        break;
                    }
                }

                // Intentar obtener numero_documento con varias variantes
                $numero = '';
                $posibles_numero = array('numero_documento', 'numero_de_documento', 'numerodocumento', 'documento', 'numero', 'nro_documento');
                foreach ($posibles_numero as $key) {
                    if (isset($this->participante[$key]) && !empty(trim($this->participante[$key]))) {
                        $numero = $this->sanitizar_campo_texto(trim($this->participante[$key]), 20);
                        break;
                    }
                }

                // Intentar obtener ciudad_expedicion con varias variantes
                $ciudad = '';
                $posibles_ciudad = array('ciudad_expedicion', 'ciudad_de_expedicion', 'ciudadexpedicion', 'ciudad', 'expedicion');
                foreach ($posibles_ciudad as $key) {
                    if (isset($this->participante[$key]) && !empty(trim($this->participante[$key]))) {
                        $ciudad = $this->sanitizar_campo_texto(trim($this->participante[$key]), 20);
                        break;
                    }
                }

                // Construir el texto del documento
                $parts = array();
                if ( $tipo_doc !== '' ) {
                    $parts[] = $tipo_doc;
                }
                if ( $numero !== '' ) {
                    $parts[] = $numero;
                }

                $texto = implode(' ', $parts);
                if ( $ciudad !== '' ) {
                    $texto .= ($texto !== '' ? ' - ' : '') . $ciudad;
                }

                return $texto;

            case 'trabajo':
                $trabajo = $this->participante['trabajo'] ?? '';
                return $this->sanitizar_campo_texto($trabajo, 150);

            case 'fecha_emision':
                // Texto en dos líneas
                $fecha = date( 'd \d\e F \d\e Y' ); // Ejemplo: 27 de noviembre de 2025
                return "Certificado generado:\n" . $fecha;
            
            default:
                return '';
        }
    }

    /**
     * Convertir URL de WordPress a ruta del sistema de archivos
     * 
     * @param string $url URL del archivo
     * @return string Ruta del sistema de archivos
     */
    private function url_to_path( $url ) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_path = $upload_dir['basedir'];
        
        return str_replace( $base_url, $base_path, $url );
    }


    /**
     * Obtener fuente TCPDF (personalizada o mapeada)
     * 
     * @param string $font_name Nombre de la fuente personalizada
     * @return string Nombre de fuente TCPDF compatible
     */
    private function mapear_fuente_tcpdf( $font_name ) {
        // Si está vacío, usar helvetica bold
        if ( empty( $font_name ) ) {
            return 'dejavusansb';
        }
        
        global $wpdb;
        
        // Buscar fuente por nombre exacto primero
        $fuente = $wpdb->get_row( $wpdb->prepare(
            "SELECT tcpdf_name FROM {$wpdb->prefix}certificados_fuentes WHERE nombre_fuente = %s",
            $font_name
        ) );
        
        // Si tiene tcpdf_name y existe el archivo, usarla
        if ( $fuente && ! empty( $fuente->tcpdf_name ) ) {
            return $fuente->tcpdf_name;
        }
        
        // Buscar por coincidencia parcial (normalizada)
        $font_normalized = strtolower( str_replace( array( ' ', '-', '_' ), '', $font_name ) );
        
        $fuentes_disponibles = $wpdb->get_results(
            "SELECT nombre_fuente, tcpdf_name FROM {$wpdb->prefix}certificados_fuentes"
        );
        
        foreach ( $fuentes_disponibles as $f ) {
            $f_normalized = strtolower( str_replace( array( ' ', '-', '_' ), '', $f->nombre_fuente ) );
            if ( strpos( $font_normalized, $f_normalized ) !== false || strpos( $f_normalized, $font_normalized ) !== false ) {
                if ( ! empty( $f->tcpdf_name ) ) {
                    return $f->tcpdf_name;
                }
            }
        }
        
        // Mapeo de fuentes conocidas a TCPDF estándar
        $font_map = array(
            'playwrite'     => 'dejavusansb',
            'sciencegothic' => 'helveticab',
            'roboto'        => 'helvetica',
            'opensans'      => 'helvetica',
            'lato'          => 'helvetica',
            'montserrat'    => 'helvetica',
        );
        
        foreach ( $font_map as $pattern => $tcpdf_font ) {
            if ( strpos( $font_normalized, $pattern ) !== false ) {
                return $tcpdf_font;
            }
        }
        
        // Por defecto: DejaVu Sans Bold (buena para textos destacados)
        return 'dejavusansb';
    }




    /**
     * Sanitizar campo de texto para prevenir inyecciones y limitar longitud
     *
     * @param string $texto Texto a sanitizar
     * @param int $max_length Longitud máxima permitida
     * @return string Texto sanitizado
     */
    private function sanitizar_campo_texto($texto, $max_length = 100) {
        // Eliminar etiquetas HTML y PHP
        $texto = strip_tags($texto);

        // Eliminar caracteres de control y caracteres especiales peligrosos
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);

        // Eliminar scripts y código malicioso
        $texto = preg_replace('/(javascript:|data:|vbscript:|on\w+\s*=)/i', '', $texto);

        // Sanitizar con función de WordPress
        $texto = sanitize_text_field($texto);

        // Limitar longitud
        if (mb_strlen($texto) > $max_length) {
            $texto = mb_substr($texto, 0, $max_length);
        }

        return $texto;
    }

    /**
     * Convertir texto a formato título (Primera Letra Mayúscula De Cada Palabra)
     * Soporta caracteres con tildes y la letra ñ
     *
     * @param string $texto Texto a convertir
     * @return string Texto en formato título
     */
    private function convertir_a_titulo($texto) {
        // Convertir todo a minúsculas primero (con soporte UTF-8)
        $texto = mb_strtolower($texto, 'UTF-8');

        // Convertir la primera letra de cada palabra a mayúscula
        // mb_convert_case funciona correctamente con tildes y ñ
        $texto = mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');

        return $texto;
    }

    /**
     * Mapear estilo de fuente a formato TCPDF
     *
     * @param string $font_style Estilo de fuente (normal, bold, italic, bold-italic)
     * @return string Estilo TCPDF ('', 'B', 'I', 'BI')
     */
    private function mapear_estilo_fuente($font_style) {
        $estilos = array(
            'normal' => '',
            'bold' => 'B',
            'italic' => 'I',
            'bold-italic' => 'BI'
        );

        return isset($estilos[$font_style]) ? $estilos[$font_style] : '';
    }

    /**
     * Limpiar nombre de archivo
     *
     * @param string $nombre Nombre a limpiar
     * @return string Nombre limpio
     */
    private function limpiar_nombre_archivo($nombre) {
        // Reemplazar espacios por guiones
        $nombre = str_replace(' ', '-', $nombre);
        
        // Convertir a minúsculas
        $nombre = strtolower($nombre);
        
        // Eliminar acentos
        $nombre = remove_accents($nombre);
        
        // Solo permitir letras, números y guiones
        $nombre = preg_replace('/[^a-z0-9\-]/', '', $nombre);
        
        // Eliminar guiones múltiples
        $nombre = preg_replace('/-+/', '-', $nombre);
        
        // Eliminar guiones al inicio y final
        $nombre = trim($nombre, '-');
        
        return $nombre;
    }



}