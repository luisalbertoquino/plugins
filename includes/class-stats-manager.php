<?php
/**
 * Gestor de Estadísticas de Descargas
 *
 * @package Certificados_Digitales
 * @version 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Stats_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar AJAX handlers
        add_action( 'wp_ajax_certificados_get_stats_overview', array( $this, 'ajax_get_stats_overview' ) );
        add_action( 'wp_ajax_certificados_get_stats_by_event', array( $this, 'ajax_get_stats_by_event' ) );
        add_action( 'wp_ajax_certificados_get_stats_timeline', array( $this, 'ajax_get_stats_timeline' ) );
        add_action( 'wp_ajax_certificados_get_top_downloads', array( $this, 'ajax_get_top_downloads' ) );
        add_action( 'wp_ajax_certificados_export_stats', array( $this, 'ajax_export_stats' ) );
        add_action( 'wp_ajax_certificados_migrate_stats_data', array( $this, 'ajax_migrate_stats_data' ) );

        // Migrar datos automáticamente al cargar (solo una vez)
        add_action( 'admin_init', array( $this, 'maybe_migrate_stats_data' ) );
    }

    /**
     * Obtener resumen general de estadísticas
     *
     * @param int $days Días hacia atrás (por defecto 30)
     * @return array
     */
    public function get_overview_stats( $days = 30 ) {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Total de descargas en el período
        $total_downloads = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}certificados_descargas
            WHERE fecha_descarga >= %s",
            $date_limit
        ) );

        // Certificados únicos descargados
        $unique_certificates = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT numero_documento) FROM {$wpdb->prefix}certificados_descargas
            WHERE fecha_descarga >= %s",
            $date_limit
        ) );

        // Eventos activos
        $active_events = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}certificados_eventos WHERE activo = 1"
        );

        // Total de certificados en caché
        $cached_certificates = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}certificados_cache"
        );

        // Descargas de hoy
        $today_downloads = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}certificados_descargas
            WHERE DATE(fecha_descarga) = %s",
            date( 'Y-m-d' )
        ) );

        // Promedio de descargas por día
        $avg_per_day = $days > 0 ? round( $total_downloads / $days, 2 ) : 0;

        return array(
            'total_downloads' => (int) $total_downloads,
            'unique_certificates' => (int) $unique_certificates,
            'active_events' => (int) $active_events,
            'cached_certificates' => (int) $cached_certificates,
            'today_downloads' => (int) $today_downloads,
            'avg_per_day' => $avg_per_day,
            'period_days' => $days
        );
    }

    /**
     * Obtener estadísticas por evento
     *
     * @param int $days Días hacia atrás
     * @return array
     */
    public function get_stats_by_event( $days = 30 ) {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                e.id as evento_id,
                e.nombre as evento_nombre,
                COUNT(d.id) as total_descargas,
                COUNT(DISTINCT d.numero_documento) as usuarios_unicos,
                MAX(d.fecha_descarga) as ultima_descarga
            FROM {$wpdb->prefix}certificados_eventos e
            LEFT JOIN {$wpdb->prefix}certificados_pestanas p ON e.id = p.evento_id
            LEFT JOIN {$wpdb->prefix}certificados_descargas d ON p.id = d.pestana_id
                AND d.fecha_descarga >= %s
            GROUP BY e.id, e.nombre
            ORDER BY total_descargas DESC",
            $date_limit
        ), ARRAY_A );

        return $results;
    }

    /**
     * Obtener línea de tiempo de descargas
     *
     * @param int $days Días hacia atrás
     * @param string $group_by Agrupar por: 'hour', 'day', 'week', 'month'
     * @return array
     */
    public function get_timeline_stats( $days = 30, $group_by = 'day' ) {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Determinar formato de agrupación
        switch ( $group_by ) {
            case 'hour':
                $date_format = '%Y-%m-%d %H:00:00';
                $label_format = 'Y-m-d H:00';
                break;
            case 'week':
                $date_format = '%Y-%U';
                $label_format = 'Y-\WW';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $label_format = 'Y-m';
                break;
            default: // day
                $date_format = '%Y-%m-%d';
                $label_format = 'Y-m-d';
                break;
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                DATE_FORMAT(fecha_descarga, %s) as periodo,
                COUNT(*) as total_descargas,
                COUNT(DISTINCT numero_documento) as usuarios_unicos
            FROM {$wpdb->prefix}certificados_descargas
            WHERE fecha_descarga >= %s
            GROUP BY periodo
            ORDER BY periodo ASC",
            $date_format,
            $date_limit
        ), ARRAY_A );

        return $results;
    }

    /**
     * Obtener top certificados descargados
     *
     * @param int $limit Límite de resultados
     * @param int $days Días hacia atrás
     * @return array
     */
    public function get_top_downloads( $limit = 10, $days = 30 ) {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                d.numero_documento,
                e.nombre as evento_nombre,
                p.nombre_pestana as tipo_certificado,
                COUNT(d.id) as total_descargas,
                MAX(d.fecha_descarga) as ultima_descarga,
                MIN(d.fecha_descarga) as primera_descarga
            FROM {$wpdb->prefix}certificados_descargas d
            INNER JOIN {$wpdb->prefix}certificados_pestanas p ON d.pestana_id = p.id
            INNER JOIN {$wpdb->prefix}certificados_eventos e ON p.evento_id = e.id
            WHERE d.fecha_descarga >= %s
            GROUP BY d.numero_documento, e.nombre, p.nombre_pestana
            ORDER BY total_descargas DESC
            LIMIT %d",
            $date_limit,
            $limit
        ), ARRAY_A );

        return $results;
    }

    /**
     * Obtener estadísticas detalladas de un evento específico
     *
     * @param int $evento_id ID del evento
     * @param int $days Días hacia atrás
     * @return array
     */
    public function get_event_details( $evento_id, $days = 30 ) {
        global $wpdb;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $stats = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COUNT(d.id) as total_descargas,
                COUNT(DISTINCT d.numero_documento) as usuarios_unicos,
                MAX(d.fecha_descarga) as ultima_descarga,
                MIN(d.fecha_descarga) as primera_descarga
            FROM {$wpdb->prefix}certificados_descargas d
            INNER JOIN {$wpdb->prefix}certificados_pestanas p ON d.pestana_id = p.id
            WHERE p.evento_id = %d AND d.fecha_descarga >= %s",
            $evento_id,
            $date_limit
        ), ARRAY_A );

        return $stats;
    }

    /**
     * Exportar estadísticas a CSV
     *
     * @param array $filters Filtros aplicados
     * @return string Ruta del archivo CSV
     */
    public function export_to_csv( $filters = array() ) {
        global $wpdb;

        $days = isset( $filters['days'] ) ? intval( $filters['days'] ) : 30;
        $evento_id = isset( $filters['evento_id'] ) ? intval( $filters['evento_id'] ) : 0;

        $date_limit = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Query base
        $query = "SELECT
            d.id,
            d.numero_documento,
            e.nombre as evento,
            p.nombre_pestana as tipo_certificado,
            d.fecha_descarga,
            d.ip_descarga
        FROM {$wpdb->prefix}certificados_descargas d
        INNER JOIN {$wpdb->prefix}certificados_pestanas p ON d.pestana_id = p.id
        INNER JOIN {$wpdb->prefix}certificados_eventos e ON p.evento_id = e.id
        WHERE d.fecha_descarga >= %s";

        if ( $evento_id > 0 ) {
            $query .= " AND p.evento_id = %d";
            $query .= " ORDER BY d.fecha_descarga DESC";
            $results = $wpdb->get_results( $wpdb->prepare( $query, $date_limit, $evento_id ), ARRAY_A );
        } else {
            $query .= " ORDER BY d.fecha_descarga DESC";
            $results = $wpdb->get_results( $wpdb->prepare( $query, $date_limit ), ARRAY_A );
        }

        // Verificar si hay datos
        if ( empty( $results ) ) {
            throw new Exception( __( 'No hay datos para exportar en el período seleccionado.', 'certificados-digitales' ) );
        }

        // Crear directorio si no existe
        $upload_dir = wp_upload_dir();
        $stats_dir = $upload_dir['basedir'] . '/certificados-stats';

        if ( ! file_exists( $stats_dir ) ) {
            wp_mkdir_p( $stats_dir );
        }

        // Verificar permisos de escritura
        if ( ! is_writable( $stats_dir ) ) {
            throw new Exception( __( 'No se puede escribir en el directorio de exportación. Verifica los permisos.', 'certificados-digitales' ) );
        }

        $filename = 'estadisticas-' . date( 'Y-m-d-His' ) . '.csv';
        $filepath = $stats_dir . '/' . $filename;

        // Abrir archivo para escritura
        $file = @fopen( $filepath, 'w' );

        if ( ! $file ) {
            throw new Exception( __( 'No se pudo crear el archivo CSV. Verifica los permisos del servidor.', 'certificados-digitales' ) );
        }

        // Agregar BOM UTF-8 para Excel
        fprintf( $file, chr(0xEF).chr(0xBB).chr(0xBF) );

        // Cabeceras
        fputcsv( $file, array(
            'ID',
            'Número Documento',
            'Evento',
            'Tipo Certificado',
            'Fecha Descarga',
            'IP'
        ), ';' );

        // Datos
        foreach ( $results as $row ) {
            fputcsv( $file, array(
                $row['id'],
                $row['numero_documento'],
                $row['evento'],
                $row['tipo_certificado'],
                $row['fecha_descarga'],
                $row['ip_descarga']
            ), ';' );
        }

        fclose( $file );

        // Retornar URL del archivo
        return $upload_dir['baseurl'] . '/certificados-stats/' . $filename;
    }

    /**
     * AJAX: Obtener resumen de estadísticas
     */
    public function ajax_get_stats_overview() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
        $stats = $this->get_overview_stats( $days );

        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Obtener estadísticas por evento
     */
    public function ajax_get_stats_by_event() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
        $stats = $this->get_stats_by_event( $days );

        wp_send_json_success( array( 'events' => $stats ) );
    }

    /**
     * AJAX: Obtener línea de tiempo
     */
    public function ajax_get_stats_timeline() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;
        $group_by = isset( $_POST['group_by'] ) ? sanitize_text_field( $_POST['group_by'] ) : 'day';

        $timeline = $this->get_timeline_stats( $days, $group_by );

        wp_send_json_success( array( 'timeline' => $timeline ) );
    }

    /**
     * AJAX: Obtener top descargas
     */
    public function ajax_get_top_downloads() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
        $days = isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30;

        $top = $this->get_top_downloads( $limit, $days );

        wp_send_json_success( array( 'top_downloads' => $top ) );
    }

    /**
     * AJAX: Exportar estadísticas
     */
    public function ajax_export_stats() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        $filters = array(
            'days' => isset( $_POST['days'] ) ? intval( $_POST['days'] ) : 30,
            'evento_id' => isset( $_POST['evento_id'] ) ? intval( $_POST['evento_id'] ) : 0
        );

        try {
            $csv_url = $this->export_to_csv( $filters );
            wp_send_json_success( array(
                'message' => __( 'Estadísticas exportadas correctamente.', 'certificados-digitales' ),
                'download_url' => $csv_url
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Verificar y migrar datos si es necesario (solo una vez)
     */
    public function maybe_migrate_stats_data() {
        // Verificar si ya se migró
        $migrated = get_option( 'certificados_stats_migrated', false );

        if ( $migrated ) {
            return;
        }

        // Ejecutar migración
        $this->migrate_stats_data();

        // Marcar como migrado
        update_option( 'certificados_stats_migrated', true );
    }

    /**
     * Migrar datos de descargas_log a descargas (para estadísticas)
     */
    public function migrate_stats_data() {
        global $wpdb;

        // Copiar registros de _log a _descargas que no existan
        $query = "INSERT INTO {$wpdb->prefix}certificados_descargas
                  (pestana_id, numero_documento, fecha_descarga, ip_descarga, user_agent)
                  SELECT
                      pestana_id,
                      numero_documento,
                      fecha as fecha_descarga,
                      ip as ip_descarga,
                      user_agent
                  FROM {$wpdb->prefix}certificados_descargas_log
                  WHERE accion = 'descarga'
                  AND NOT EXISTS (
                      SELECT 1 FROM {$wpdb->prefix}certificados_descargas d2
                      WHERE d2.pestana_id = {$wpdb->prefix}certificados_descargas_log.pestana_id
                      AND d2.numero_documento = {$wpdb->prefix}certificados_descargas_log.numero_documento
                      AND d2.fecha_descarga = {$wpdb->prefix}certificados_descargas_log.fecha
                  )";

        $wpdb->query( $query );
    }

    /**
     * AJAX: Migrar datos manualmente
     */
    public function ajax_migrate_stats_data() {
        check_ajax_referer( 'certificados_stats_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'certificados-digitales' ) ) );
        }

        try {
            $this->migrate_stats_data();

            wp_send_json_success( array(
                'message' => __( 'Datos migrados correctamente.', 'certificados-digitales' )
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }
}
