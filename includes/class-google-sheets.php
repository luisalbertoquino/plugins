<?php
/**
 * Clase para interactuar con Google Sheets API v4
 * 
 * @package Certificados_Digitales
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Google_Sheets {

    /**
     * API Key de Google
     */
    private $api_key;

    /**
     * ID del documento de Google Sheets
     */
    private $spreadsheet_id;

    /**
     * Constructor
     */
    public function __construct( $api_key, $spreadsheet_id ) {
        $this->api_key = $api_key;
        $this->spreadsheet_id = $spreadsheet_id;
    }

    /**
     * Obtener datos de una hoja específica
     * 
     * @param string $sheet_name Nombre de la hoja
     * @return array|WP_Error Array de datos o error
     */
    public function get_sheet_data( $sheet_name ) {
        // Construir URL de la API
        // Rango extendido hasta AZ (52 columnas) para soportar más datos
        $range = urlencode( $sheet_name . '!A:AZ' );
        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
            $this->spreadsheet_id,
            $range,
            $this->api_key
        );

        // Hacer petición
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
        ) );

        // Verificar errores
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            return new WP_Error( 
                'api_error', 
                sprintf( 
                    __( 'Error al consultar Google Sheets. Código: %d', 'certificados-digitales' ),
                    $response_code 
                )
            );
        }

        // Decodificar respuesta
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['values'] ) ) {
            return new WP_Error( 
                'no_data', 
                __( 'No se encontraron datos en la hoja.', 'certificados-digitales' ) 
            );
        }

        return $data['values'];
    }

    /**
     * Buscar un registro por número de documento
     * 
     * @param string $sheet_name Nombre de la hoja
     * @param string $numero_documento Número de documento a buscar
     * @return array|null Array con los datos o null si no se encuentra
     */
    public function buscar_por_documento( $sheet_name, $numero_documento ) {
        $data = $this->get_sheet_data( $sheet_name );

        if ( is_wp_error( $data ) ) {
            return null;
        }

        // Primera fila son los encabezados
        $headers = array_shift( $data );
        
        // Normalizar encabezados (minúsculas, sin espacios)
        $headers = array_map( function( $header ) {
            return strtolower( trim( str_replace( ' ', '_', $header ) ) );
        }, $headers );

        // Buscar índice de la columna numero_documento
        $doc_index = array_search( 'numero_documento', $headers );
        
        if ( $doc_index === false ) {
            return null;
        }

        // Buscar el documento
        foreach ( $data as $row ) {
            if ( isset( $row[ $doc_index ] ) && trim( $row[ $doc_index ] ) == trim( $numero_documento ) ) {
                // Crear array asociativo
                $result = array();
                foreach ( $headers as $index => $header ) {
                    $result[ $header ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                }
                return $result;
            }
        }

        return null;
    }

    /**
     * Buscar un registro por número de documento en datos ya obtenidos
     *
     * @param array $data Datos del sheet (con encabezados en primera fila)
     * @param string $numero_documento Número de documento a buscar
     * @return array|null Array con los datos o null si no se encuentra
     */
    public function buscar_en_datos( $data, $numero_documento ) {
        if ( empty( $data ) ) {
            return null;
        }

        // Primera fila son los encabezados
        $headers = array_shift( $data );

        // Normalizar encabezados (minúsculas, sin espacios)
        $headers = array_map( function( $header ) {
            return strtolower( trim( str_replace( ' ', '_', $header ) ) );
        }, $headers );

        // Buscar índice de la columna numero_documento
        $doc_index = array_search( 'numero_documento', $headers );

        if ( $doc_index === false ) {
            return null;
        }

        // Buscar el documento
        foreach ( $data as $row ) {
            if ( isset( $row[ $doc_index ] ) && trim( $row[ $doc_index ] ) == trim( $numero_documento ) ) {
                // Crear array asociativo
                $result = array();
                foreach ( $headers as $index => $header ) {
                    $result[ $header ] = isset( $row[ $index ] ) ? $row[ $index ] : '';
                }
                return $result;
            }
        }

        return null;
    }

    /**
     * Validar conexión con Google Sheets
     *
     * @param string $sheet_name Nombre de la hoja
     * @return bool True si la conexión es válida
     */
    public function validar_conexion( $sheet_name ) {
        $data = $this->get_sheet_data( $sheet_name );
        return ! is_wp_error( $data );
    }
}