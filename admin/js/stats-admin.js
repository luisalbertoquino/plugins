/**
 * JavaScript para Estadísticas
 *
 * @package Certificados_Digitales
 */

(function($) {
    'use strict';

    let timelineChart = null;

    $(document).ready(function() {

        // Cargar datos iniciales
        loadAllStats();

        // Actualizar al cambiar período
        $('#stats-period, #stats-event').on('change', function() {
            loadAllStats();
        });

        // Botón refrescar
        $('#btn-refresh-stats').on('click', function() {
            loadAllStats();
        });

        // Cambiar agrupación del gráfico
        $('#chart-group-by').on('change', function() {
            loadTimelineStats();
        });

        // Cambiar límite de top descargas
        $('#top-limit').on('change', function() {
            loadTopDownloads();
        });

        // Exportar CSV
        $('#btn-export-stats').on('click', function() {
            exportStats();
        });

    });

    /**
     * Cargar todas las estadísticas
     */
    function loadAllStats() {
        loadOverviewStats();
        loadTimelineStats();
        loadEventStats();
        loadTopDownloads();
    }

    /**
     * Cargar resumen general
     */
    function loadOverviewStats() {
        const days = $('#stats-period').val();

        $.ajax({
            url: certificadosStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_stats_overview',
                nonce: certificadosStats.nonce,
                days: days
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#stat-total-downloads').text(formatNumber(data.total_downloads));
                    $('#stat-unique-users').text(formatNumber(data.unique_certificates));
                    $('#stat-today-downloads').text(formatNumber(data.today_downloads));
                    $('#stat-avg-per-day').text(formatNumber(data.avg_per_day));
                }
            },
            error: function() {
                console.error('Error loading overview stats');
            }
        });
    }

    /**
     * Cargar línea de tiempo
     */
    function loadTimelineStats() {
        const days = $('#stats-period').val();
        const groupBy = $('#chart-group-by').val();

        $.ajax({
            url: certificadosStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_stats_timeline',
                nonce: certificadosStats.nonce,
                days: days,
                group_by: groupBy
            },
            success: function(response) {
                if (response.success) {
                    renderTimelineChart(response.data.timeline);
                }
            },
            error: function() {
                console.error('Error loading timeline stats');
            }
        });
    }

    /**
     * Cargar estadísticas por evento
     */
    function loadEventStats() {
        const days = $('#stats-period').val();

        $.ajax({
            url: certificadosStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_stats_by_event',
                nonce: certificadosStats.nonce,
                days: days
            },
            success: function(response) {
                if (response.success) {
                    renderEventStatsTable(response.data.events);
                }
            },
            error: function() {
                console.error('Error loading event stats');
            }
        });
    }

    /**
     * Cargar top descargas
     */
    function loadTopDownloads() {
        const days = $('#stats-period').val();
        const limit = $('#top-limit').val();

        $.ajax({
            url: certificadosStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_get_top_downloads',
                nonce: certificadosStats.nonce,
                days: days,
                limit: limit
            },
            success: function(response) {
                if (response.success) {
                    renderTopDownloadsTable(response.data.top_downloads);
                }
            },
            error: function() {
                console.error('Error loading top downloads');
            }
        });
    }

    /**
     * Renderizar gráfico de línea de tiempo
     */
    function renderTimelineChart(data) {
        const ctx = document.getElementById('timeline-chart');

        if (!ctx) return;

        const labels = data.map(item => item.periodo);
        const downloads = data.map(item => parseInt(item.total_descargas));
        const uniqueUsers = data.map(item => parseInt(item.usuarios_unicos));

        // Destruir gráfico anterior si existe
        if (timelineChart) {
            timelineChart.destroy();
        }

        timelineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Descargas',
                        data: downloads,
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Usuarios Únicos',
                        data: uniqueUsers,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Renderizar tabla de estadísticas por evento
     */
    function renderEventStatsTable(events) {
        let html = '<table class="widefat striped">';
        html += '<thead><tr>';
        html += '<th>Evento</th>';
        html += '<th>Total Descargas</th>';
        html += '<th>Usuarios Únicos</th>';
        html += '<th>Última Descarga</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        if (events.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;">' + certificadosStats.i18n.no_data + '</td></tr>';
        } else {
            events.forEach(function(event) {
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(event.evento_nombre) + '</strong></td>';
                html += '<td>' + formatNumber(event.total_descargas) + '</td>';
                html += '<td>' + formatNumber(event.usuarios_unicos) + '</td>';
                html += '<td>' + formatDate(event.ultima_descarga) + '</td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table>';
        $('#event-stats-table').html(html);
    }

    /**
     * Renderizar tabla de top descargas
     */
    function renderTopDownloadsTable(downloads) {
        let html = '<table class="widefat striped">';
        html += '<thead><tr>';
        html += '<th>#</th>';
        html += '<th>Documento</th>';
        html += '<th>Evento</th>';
        html += '<th>Descargas</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        if (downloads.length === 0) {
            html += '<tr><td colspan="4" style="text-align:center;">' + certificadosStats.i18n.no_data + '</td></tr>';
        } else {
            downloads.forEach(function(item, index) {
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td><code>' + escapeHtml(item.numero_documento) + '</code></td>';
                html += '<td>' + escapeHtml(item.evento_nombre) + '</td>';
                html += '<td><strong>' + formatNumber(item.total_descargas) + '</strong></td>';
                html += '</tr>';
            });
        }

        html += '</tbody></table>';
        $('#top-downloads-table').html(html);
    }

    /**
     * Exportar estadísticas a CSV
     */
    function exportStats() {
        const days = $('#stats-period').val();
        const eventoId = $('#stats-event').val();

        const $btn = $('#btn-export-stats');
        $btn.prop('disabled', true);
        $btn.html('<span class="dashicons dashicons-update spin"></span> Exportando...');

        $.ajax({
            url: certificadosStats.ajaxurl,
            type: 'POST',
            data: {
                action: 'certificados_export_stats',
                nonce: certificadosStats.nonce,
                days: days,
                evento_id: eventoId
            },
            success: function(response) {
                if (response.success) {
                    // Descargar archivo
                    window.open(response.data.download_url, '_blank');
                    alert(certificadosStats.i18n.export_success);
                } else {
                    alert(response.data.message || certificadosStats.i18n.error);
                }
            },
            error: function() {
                alert(certificadosStats.i18n.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html('<span class="dashicons dashicons-download"></span> Exportar CSV');
            }
        });
    }

    /**
     * Formatear número con separadores de miles
     */
    function formatNumber(num) {
        if (num === null || num === undefined) return '-';
        return parseFloat(num).toLocaleString('es-ES');
    }

    /**
     * Formatear fecha
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Escapar HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})(jQuery);
