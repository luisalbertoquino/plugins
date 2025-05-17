<?php
/*
 * Página de prueba para la conexión con Google Sheets
 */

// Verificar si no estamos en WordPress
if (!defined('ABSPATH')) {
    // Si se accede directamente, mostrar error y salir
    die('Acceso directo no permitido');
}

// Verificar permisos de administrador
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'certificados-pdf'));
}

// Obtener la API Key de las opciones
$google_api_key = get_option('certificados_pdf_google_api_key', '');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Prueba de conexión con Google Sheets</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #0073aa;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #005a87;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
            display: none;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
            max-height: 400px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Prueba de conexión con Google Sheets</h1>
    
    <div class="container">
        <div class="form-group">
            <label for="api_key">API Key de Google:</label>
            <input type="text" id="api_key" value="<?php echo esc_attr($google_api_key); ?>" placeholder="Ingrese su API Key de Google">
        </div>
        <div class="form-group">
            <label for="sheet_id">ID de la hoja de cálculo:</label>
            <input type="text" id="sheet_id" value="" placeholder="Ej: 1gdKlT6iI-QUCxX5seqmtHjwlSoMo2dGKQQKU7dTofkk">
        </div>
        <div class="form-group">
            <label for="sheet_range">Rango (opcional):</label>
            <input type="text" id="sheet_range" value="A:Z" placeholder="Ej: A:Z o Sheet1!A1:F10">
        </div>
        
        <button id="test-connection">Probar Conexión</button>
    </div>
    
    <div id="result" class="result">
        <h2>Resultado:</h2>
        <div id="connection-status"></div>
        <pre id="json-response"></pre>
        
        <div id="data-table-container" style="display:none;">
            <h3>Vista de datos:</h3>
            <table id="data-table">
                <thead id="table-head"></thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnTest = document.getElementById('test-connection');
        const resultDiv = document.getElementById('result');
        const connectionStatus = document.getElementById('connection-status');
        const jsonResponse = document.getElementById('json-response');
        const dataTableContainer = document.getElementById('data-table-container');
        const tableHead = document.getElementById('table-head');
        const tableBody = document.getElementById('table-body');
        
        btnTest.addEventListener('click', async function() {
            const apiKey = document.getElementById('api_key').value.trim();
            const sheetId = document.getElementById('sheet_id').value.trim();
            let sheetRange = document.getElementById('sheet_range').value.trim();
            
            if (!apiKey) {
                alert('Por favor, ingrese la API Key de Google');
                return;
            }
            
            if (!sheetId) {
                alert('Por favor, ingrese el ID de la hoja de cálculo');
                return;
            }
            
            if (!sheetRange) {
                sheetRange = 'A:Z';
            }
            
            connectionStatus.innerHTML = '<div style="text-align:center; padding:20px;">Conectando...</div>';
            resultDiv.style.display = 'block';
            jsonResponse.textContent = '';
            dataTableContainer.style.display = 'none';
            resultDiv.className = 'result';
            
            try {
                const data = await getGoogleSheetData(sheetId, sheetRange, apiKey);
                
                if (data.error) {
                    connectionStatus.innerHTML = `<div class="error">Error: ${data.error.message}</div>`;
                    jsonResponse.textContent = JSON.stringify(data, null, 2);
                } else {
                    connectionStatus.innerHTML = `<div class="success">Conexión exitosa! Se encontraron ${data.values && data.values.length ? data.values.length : 0} filas de datos.</div>`;
                    jsonResponse.textContent = JSON.stringify(data, null, 2);
                    
                    // Si hay datos, mostrarlos en una tabla
                    if (data.values && data.values.length > 0) {
                        renderDataTable(data.values);
                        dataTableContainer.style.display = 'block';
                    }
                }
            } catch (error) {
                connectionStatus.innerHTML = `<div class="error">Error: ${error.message}</div>`;
                jsonResponse.textContent = error.toString();
            }
        });
        
        async function getGoogleSheetData(sheetId, range, apiKey) {
            const url = `https://sheets.googleapis.com/v4/spreadsheets/${sheetId}/values/${range}?key=${apiKey}`;
            const response = await fetch(url);
            return response.json();
        }
        
        function renderDataTable(values) {
            // Limpiar tabla
            tableHead.innerHTML = '';
            tableBody.innerHTML = '';
            
            // Crear encabezados (primera fila)
            const headerRow = document.createElement('tr');
            const headers = values[0];
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                headerRow.appendChild(th);
            });
            tableHead.appendChild(headerRow);
            
            // Crear filas de datos (desde la segunda fila)
            for (let i = 1; i < values.length; i++) {
                const dataRow = document.createElement('tr');
                const rowData = values[i];
                
                // Asegurarse de que cada fila tenga el mismo número de columnas que el encabezado
                for (let j = 0; j < headers.length; j++) {
                    const td = document.createElement('td');
                    td.textContent = j < rowData.length ? rowData[j] : '';
                    dataRow.appendChild(td);
                }
                
                tableBody.appendChild(dataRow);
            }
        }
    });
    </script>
</body>
</html>