<?php
/**
 * Clase para gestionar la integración con Google Sheets.
 *
 * @since      1.0.0
 */
class Certificados_PDF_Google_Sheets {

    /**
     * La API key de Google.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    La API key de Google.
     */
    private $api_key;

    /**
     * La instancia del cliente de Google.
     *
     * @since    1.0.0
     * @access   private
     * @var      Google\Client    $client    La instancia del cliente de Google.
     */
    private $client;

    /**
     * El servicio de Google Sheets.
     *
     * @since    1.0.0
     * @access   private
     * @var      Google\Service\Sheets    $service    El servicio de Google Sheets.
     */
    private $service;

    /**
     * Inicializar la clase y establecer sus propiedades.
     *
     * @since    1.0.0
     * @param    string    $api_key    La API key de Google.
     */
    public function __construct($api_key = null) {
        // Si no se proporciona una API key, intentamos obtenerla de las opciones
        if (empty($api_key)) {
            $this->api_key = get_option('certificados_pdf_google_api_key', '');
        } else {
            $this->api_key = $api_key;
        }
        
        // Configurar cliente de Google
        if (!empty($this->api_key)) {
            $this->setup_client();
        }
    }

    /**
     * Configura el cliente de Google.
     *
     * @since    1.0.0
     * @access   private
     */
    private function setup_client() {
        try {
            // Usar la versión moderna con namespaces
            $this->client = new Google\Client();
            $this->client->setDeveloperKey($this->api_key);
            $this->client->setScopes(['https://www.googleapis.com/auth/spreadsheets.readonly']);
            $this->client->setApplicationName('Certificados PDF WordPress Plugin');
            
            $this->service = new Google\Service\Sheets($this->client);
        } catch (Exception $e) {
            throw new Exception(__('Error al configurar el cliente de Google: ', 'certificados-pdf') . $e->getMessage());
        }
    }

    /**
     * Obtiene las columnas de una hoja de Google Sheets.
     *
     * @since    1.0.0
     * @param    string    $sheet_id      El ID de la hoja de Google Sheets.
     * @param    string    $sheet_nombre  El nombre de la hoja dentro del documento.
     * @return   array                    Las columnas de la hoja.
     */
    public function obtener_columnas($sheet_id, $sheet_nombre) {
        if (empty($this->service)) {
            $this->setup_client();
        }
        
        try {
            // Obtenemos solo la primera fila que contiene los encabezados
            $range = $sheet_nombre . '!1:1';
            
            // Usamos directamente la API REST para obtener los valores
            $url = "https://sheets.googleapis.com/v4/spreadsheets/$sheet_id/values/$range?key=" . $this->api_key;
            
            // Log para debugging
            error_log("Intentando obtener datos de: $url");
            
            // Realizar la solicitud HTTP
            $response = wp_remote_get($url);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            
            if ($status_code !== 200) {
                $error_message = wp_remote_retrieve_response_message($response);
                $body = wp_remote_retrieve_body($response);
                
                if (!empty($body)) {
                    $data = json_decode($body, true);
                    if (isset($data['error']['message'])) {
                        $error_message = $data['error']['message'];
                    }
                }
                
                throw new Exception("Error HTTP $status_code: $error_message");
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!isset($data['values']) || empty($data['values'])) {
                return array();
            }
            
            // La primera fila contiene los encabezados
            $encabezados = $data['values'][0];
            
            $columnas = array();
            foreach ($encabezados as $index => $nombre) {
                $letra_columna = $this->num_a_letra_columna($index);
                $columnas[] = array(
                    'nombre' => $nombre,
                    'columna' => $letra_columna
                );
            }
            
            return $columnas;
        } catch (Exception $e) {
            throw new Exception(__('Error al obtener columnas: ', 'certificados-pdf') . $e->getMessage());
        }
    }
    
    /**
     * Busca un registro en Google Sheets por un valor específico en una columna.
     *
     * @since    1.0.0
     * @param    string    $sheet_id      El ID de la hoja de Google Sheets.
     * @param    string    $sheet_nombre  El nombre de la hoja dentro del documento.
     * @param    string    $columna       La columna donde buscar (A, B, C, etc.).
     * @param    string    $valor         El valor a buscar.
     * @return   array|false              El registro encontrado o false si no se encuentra.
     */
    public function buscar_registro($sheet_id, $sheet_nombre, $columna, $valor) {
        if (empty($this->service)) {
            $this->setup_client();
        }
        
        try {
            // Obtener todos los datos de la hoja
            $response = $this->service->spreadsheets_values->get($sheet_id, $sheet_nombre);
            $values = $response->getValues();
            
            if (empty($values)) {
                return false;
            }
            
            // Obtener los encabezados
            $encabezados = $values[0];
            
            // Determinar índice de columna
            $index_columna = -1;
            
            // Primero verificar si es un nombre de columna
            foreach ($encabezados as $i => $encabezado) {
                if ($encabezado == $columna) {
                    $index_columna = $i;
                    break;
                }
            }
            
            // Si no se encontró como nombre, verificar si es una letra de columna (A, B, C, etc.)
            if ($index_columna < 0 && preg_match('/^[A-Za-z]+$/', $columna)) {
                $index_columna = $this->letra_a_num_columna($columna);
            }
            
            // Si aún no se ha encontrado, intentar convertir a un índice numérico
            if ($index_columna < 0 && is_numeric($columna)) {
                $index_columna = intval($columna);
            }
            
            // Si no se pudo determinar la columna, retornar falso
            if ($index_columna < 0 || $index_columna >= count($encabezados)) {
                return false;
            }
            
            // Buscar el valor en la columna especificada
            foreach ($values as $i => $fila) {
                // Saltamos la primera fila (encabezados)
                if ($i === 0) {
                    continue;
                }
                
                // Verificar si la columna existe en esta fila
                if (isset($fila[$index_columna]) && $fila[$index_columna] == $valor) {
                    // Construir un array asociativo con los valores
                    $registro = array();
                    foreach ($encabezados as $j => $encabezado) {
                        $registro[$encabezado] = isset($fila[$j]) ? $fila[$j] : '';
                    }
                    return $registro;
                }
            }
            
            // No se encontró el registro
            return false;
            
        } catch (Exception $e) {
            throw new Exception(__('Error al buscar registro: ', 'certificados-pdf') . $e->getMessage());
        }
    }
    
    /**
     * Convierte un número de columna (0, 1, 2, ...) a letra (A, B, C, ...).
     *
     * @since    1.0.0
     * @access   private
     * @param    int       $num    El número de columna.
     * @return   string            La letra de la columna.
     */
    private function num_a_letra_columna($num) {
        $letra = '';
        
        while ($num >= 0) {
            $resto = $num % 26;
            $letra = chr(65 + $resto) . $letra;
            $num = intval($num / 26) - 1;
        }
        
        return $letra;
    }
    
    /**
     * Convierte una letra de columna (A, B, C, ...) a número (0, 1, 2, ...).
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $letra    La letra de la columna.
     * @return   int                 El número de columna.
     */
    private function letra_a_num_columna($letra) {
        $letra = strtoupper($letra);
        $num = 0;
        $len = strlen($letra);
        
        for ($i = 0; $i < $len; $i++) {
            $num = $num * 26 + (ord($letra[$i]) - 64);
        }
        
        return $num - 1;
    }
    
    /**
     * Método de diagnóstico para probar la conexión y obtener datos brutos.
     *
     * @since    1.0.0
     * @param    string    $sheet_id      El ID de la hoja de Google Sheets.
     * @param    string    $range         El rango a obtener (ejemplo: 'A:Z' o 'Sheet1!A1:D10').
     * @return   array                    Los datos obtenidos.
     */
    public function test_connection($sheet_id, $range = 'A:Z') {
        if (empty($this->service)) {
            $this->setup_client();
        }
        
        try {
            // Asegurarse de que el rango incluya el nombre de la hoja si no está presente
            if (strpos($range, '!') === false) {
                // Si no hay nombre de hoja en el rango, usar el rango tal cual (asumiendo que es toda la hoja)
                $response = $this->service->spreadsheets_values->get($sheet_id, $range);
            } else {
                // Si ya incluye el nombre de la hoja en el formato 'Sheet1!A1:Z', usar tal cual
                $response = $this->service->spreadsheets_values->get($sheet_id, $range);
            }
            
            // Devolver la respuesta completa para diagnóstico
            return $response;
            
        } catch (Exception $e) {
            throw new Exception(__('Error en la prueba de conexión: ', 'certificados-pdf') . $e->getMessage());
        }
    }
}