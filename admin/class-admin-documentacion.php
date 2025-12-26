<?php
/**
 * Página de Documentación del Plugin
 *
 * @package Certificados_Digitales
 * @version 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Certificados_Admin_Documentacion {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
    }

    public function register_page() {
        add_submenu_page(
            'certificados-digitales',
            __( 'Documentación', 'certificados-digitales' ),
            __( 'Documentación', 'certificados-digitales' ),
            'manage_options',
            'certificados-digitales-documentacion',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        ?>
        <div class="wrap certificados-admin-wrap certificados-documentacion-wrap">

            <!-- Header estandarizado -->
            <div class="dashboard-header">
                <div class="dashboard-header-title">
                    <span class="dashicons dashicons-book"></span>
                    <h1><?php _e( 'Documentación', 'certificados-digitales' ); ?></h1>
                </div>
                <div class="dashboard-header-actions">
                    <a href="<?php echo admin_url( 'admin.php?page=certificados-digitales' ); ?>" class="btn-quick-action">
                        <i class="fas fa-home"></i>
                        <?php _e( 'Dashboard', 'certificados-digitales' ); ?>
                    </a>
                </div>
            </div>

            <!-- Descripción de la sección -->
            <div class="certificados-section-description certificados-documentacion-description">
                <p><?php _e( 'Guía completa para usar el plugin Certificados Digitales Pro. Aprende a configurar eventos, crear plantillas, gestionar fuentes personalizadas, mapear campos y generar certificados profesionales para tus participantes.', 'certificados-digitales' ); ?></p>
            </div>

            <div class="doc-container">
                <!-- Tabla de Contenidos -->
                <div class="doc-sidebar">
                    <h3><?php _e( 'Tabla de Contenidos', 'certificados-digitales' ); ?></h3>
                    <nav class="doc-nav">
                        <a href="#instalacion"><?php _e( '1. Instalación', 'certificados-digitales' ); ?></a>
                        <a href="#google-sheets"><?php _e( '2. Configuración Google Sheets', 'certificados-digitales' ); ?></a>
                        <a href="#personalizacion-colores"><?php _e( '3. Personalización de Colores', 'certificados-digitales' ); ?></a>
                        <a href="#gestion-fuentes"><?php _e( '4. Gestión de Fuentes Personalizadas', 'certificados-digitales' ); ?></a>
                        <a href="#crear-evento"><?php _e( '5. Crear un Evento', 'certificados-digitales' ); ?></a>
                        <a href="#plantillas"><?php _e( '6. Configurar Plantillas', 'certificados-digitales' ); ?></a>
                        <a href="#campos"><?php _e( '7. Mapeo de Campos', 'certificados-digitales' ); ?></a>
                        <a href="#encuestas"><?php _e( '8. Encuestas de Satisfacción', 'certificados-digitales' ); ?></a>
                        <a href="#estadisticas"><?php _e( '9. Estadísticas', 'certificados-digitales' ); ?></a>
                        <a href="#shortcodes"><?php _e( '10. Usar Shortcodes', 'certificados-digitales' ); ?></a>
                        <a href="#verificacion"><?php _e( '11. Verificación y Pruebas', 'certificados-digitales' ); ?></a>
                        <a href="#solucionar-problemas"><?php _e( '12. Solucionar Problemas', 'certificados-digitales' ); ?></a>
                        <a href="#experiencia-usuario"><?php _e( '13. Experiencia del Usuario Final', 'certificados-digitales' ); ?></a>
                    </nav>
                </div>

                <!-- Contenido Principal -->
                <div class="doc-content">

                    <!-- 1. Instalación -->
                    <section id="instalacion" class="doc-section">
                        <h2><span class="section-number">1.</span> <?php _e( 'Instalación', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Requisitos Previos', 'certificados-digitales' ); ?></h3>
                            <ul class="checklist">
                                <li>WordPress 5.0 o superior</li>
                                <li>PHP 7.4 o superior</li>
                                <li>Cuenta de Google con acceso a Google Sheets</li>
                                <li>API Key de Google Cloud Platform</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Pasos de Instalación', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Descarga el archivo .zip del plugin</li>
                                <li>Ve a <code>Plugins → Añadir nuevo → Subir plugin</code></li>
                                <li>Selecciona el archivo .zip y haz clic en <strong>Instalar ahora</strong></li>
                                <li>Activa el plugin después de la instalación</li>
                            </ol>
                        </div>

                        <div class="doc-callout doc-callout-success">
                            <strong>✅ Listo!</strong> El plugin se instalará automáticamente y creará las tablas necesarias en la base de datos.
                        </div>
                    </section>

                    <!-- 2. Google Sheets -->
                    <section id="google-sheets" class="doc-section">
                        <h2><span class="section-number">2.</span> <?php _e( 'Configuración de Google Sheets', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Obtener API Key de Google', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Ve a <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a></li>
                                <li>Crea un nuevo proyecto o selecciona uno existente</li>
                                <li>Habilita la <strong>Google Sheets API</strong></li>
                                <li>Ve a <code>Credenciales → Crear credenciales → Clave de API</code></li>
                                <li>Copia la API Key generada</li>
                                <li>En WordPress, ve a <code>Certificados → Configuración</code> y pega la API Key</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Preparar tu Hoja de Cálculo', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Tu hoja de Google Sheets debe tener al menos estas columnas:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li><strong>Número Documento</strong> (obligatorio): Identificador único</li>
                                <li><strong>Nombre</strong>: Nombre del participante</li>
                                <li>Otros campos personalizados según tus necesidades</li>
                            </ul>
                        </div>

                        <div class="doc-callout doc-callout-warning">
                            <strong>⚠️ Importante:</strong> Asegúrate de que la hoja sea pública o compartida con "Cualquiera con el enlace puede ver".
                        </div>
                    </section>

                    <!-- 3. Personalización de Colores -->
                    <section id="personalizacion-colores" class="doc-section">
                        <h2><span class="section-number">3.</span> <?php _e( 'Personalización de Colores', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Configurar Colores del Plugin', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'El plugin permite personalizar completamente los colores de la interfaz para adaptarlos a tu marca o estilo visual.', 'certificados-digitales' ); ?></p>
                            <ol>
                                <li>Ve a <code>Certificados → Configuración</code></li>
                                <li>Desplázate hasta la sección <strong>Personalización de Colores</strong></li>
                                <li>Configura los siguientes colores según tus preferencias:</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Colores Disponibles', 'certificados-digitales' ); ?></h3>
                            <ul>
                                <li><strong>Color Primario:</strong> Se aplica a todos los botones del dashboard, configurador, enlaces y elementos activos del plugin. Este color sobrescribe el color primario de WordPress para los elementos del plugin.</li>
                                <li><strong>Color Hover:</strong> Color que aparece al pasar el mouse sobre botones y elementos interactivos. Crea un efecto de inversión de colores elegante.</li>
                                <li><strong>Color Éxito:</strong> Utilizado en mensajes de confirmación y operaciones exitosas (ej: "Evento guardado correctamente").</li>
                                <li><strong>Color Error:</strong> Utilizado en mensajes de error y advertencia (ej: "No se pudo conectar con Google Sheets").</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Aplicación de Colores', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Los colores se aplican automáticamente en:', 'certificados-digitales' ); ?></p>
                            <ul class="checklist">
                                <li>Botones del dashboard (Nuevo Evento, Editar, Eliminar, etc.)</li>
                                <li>Botones del configurador de campos (Guardar Cambios, Modo Calibración)</li>
                                <li>Enlaces de navegación</li>
                                <li>Elementos activos y seleccionados</li>
                                <li>Notificaciones y mensajes del sistema</li>
                            </ul>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>💡 Nota:</strong> Si no personalizas el color primario del plugin, los botones usarán automáticamente el color primario de WordPress (configurable en <code>Apariencia → Personalizar → Colores</code>). Tu personalización sobrescribirá este comportamiento.
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Guardar Cambios', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Después de seleccionar tus colores, haz clic en <strong>Guardar Cambios</strong></li>
                                <li>Los colores se aplicarán inmediatamente en toda la interfaz del plugin</li>
                                <li>No es necesario recargar la página</li>
                            </ol>
                        </div>
                    </section>

                    <!-- 4. Gestión de Fuentes Personalizadas -->
                    <section id="gestion-fuentes" class="doc-section">
                        <h2><span class="section-number">4.</span> <?php _e( 'Gestión de Fuentes Personalizadas', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Subir Fuentes Personalizadas', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'El plugin permite usar fuentes personalizadas en tus certificados para mantener la identidad visual de tu institución.', 'certificados-digitales' ); ?></p>
                            <ol>
                                <li>Ve a <code>Certificados → Fuentes</code></li>
                                <li>Haz clic en <strong>Agregar Nueva Fuente</strong></li>
                                <li>Selecciona tu archivo de fuente (.ttf o .otf)</li>
                                <li>Asigna un nombre descriptivo a la fuente</li>
                                <li>Haz clic en <strong>Subir Fuente</strong></li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Formatos Soportados', 'certificados-digitales' ); ?></h3>
                            <ul>
                                <li><strong>TrueType (.ttf)</strong> - Recomendado, ampliamente compatible</li>
                                <li><strong>OpenType (.otf)</strong> - Compatible, características avanzadas de tipografía</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Usar Fuentes en Certificados', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Una vez subida la fuente:', 'certificados-digitales' ); ?></p>
                            <ol>
                                <li>Ve al <strong>Configurador de Campos</strong> de tu pestaña</li>
                                <li>Selecciona el campo de texto que deseas editar</li>
                                <li>En el selector de fuente, encontrarás tu fuente personalizada en la lista</li>
                                <li>Selecciónala y guarda los cambios</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Estilos de Fuente Disponibles', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Para cada campo puedes configurar:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li><strong>Normal:</strong> Estilo regular de la fuente</li>
                                <li><strong>Negrita:</strong> Peso de fuente en negrita</li>
                                <li><strong>Cursiva:</strong> Estilo itálica/cursiva</li>
                                <li><strong>Negrita Cursiva:</strong> Combinación de ambos estilos</li>
                            </ul>
                        </div>

                        <div class="doc-callout doc-callout-warning">
                            <strong>⚠️ Importante:</strong>
                            <ul style="margin: 10px 0 0 20px;">
                                <li>Las fuentes se almacenan en <code>/wp-content/uploads/certificados-fuentes/</code></li>
                                <li>Puedes subir múltiples variantes de la misma familia tipográfica con nombres diferentes (ej: "Arial Regular", "Arial Bold")</li>
                                <li>Asegúrate de tener los derechos de uso de las fuentes que subes, especialmente para uso comercial</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Gestionar Fuentes Existentes', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Desde la página de Fuentes puedes:', 'certificados-digitales' ); ?></p>
                            <ul class="checklist">
                                <li>Ver todas las fuentes subidas</li>
                                <li>Verificar el nombre y fecha de subida</li>
                                <li>Eliminar fuentes que ya no necesites</li>
                                <li>Subir nuevas variantes</li>
                            </ul>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>💡 Tip:</strong> Para lograr mejor consistencia visual, sube todas las variantes de tu familia tipográfica (Regular, Bold, Italic, Bold Italic) y nómbralas claramente (ej: "Montserrat Regular", "Montserrat Bold").
                        </div>
                    </section>

                    <!-- 5. Crear Evento -->
                    <section id="crear-evento" class="doc-section">
                        <h2><span class="section-number">5.</span> <?php _e( 'Crear un Evento', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso a Paso', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Ve a <code>Certificados → Dashboard</code></li>
                                <li>Haz clic en <strong>Crear Nuevo Evento</strong></li>
                                <li>Completa los campos:
                                    <ul>
                                        <li><strong>Nombre del Evento</strong>: Ej. "Conferencia 2025"</li>
                                        <li><strong>Sheet ID</strong>: ID de tu Google Sheets (lo encuentras en la URL)</li>
                                        <li><strong>Estado</strong>: Activo/Inactivo</li>
                                    </ul>
                                </li>
                                <li>Guarda el evento</li>
                            </ol>
                        </div>

                        <div class="doc-code">
                            <strong><?php _e( 'Ejemplo de Sheet ID:', 'certificados-digitales' ); ?></strong>
                            <code>https://docs.google.com/spreadsheets/d/<mark>1ABC123xyz</mark>/edit</code>
                            <p class="code-note">El Sheet ID es la parte marcada en la URL</p>
                        </div>
                    </section>

                    <!-- 6. Plantillas -->
                    <section id="plantillas" class="doc-section">
                        <h2><span class="section-number">6.</span> <?php _e( 'Configurar Plantillas y Pestañas', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Crear una Pestaña', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Haz clic en <strong>Pestañas</strong> en el evento creado</li>
                                <li>Añade una nueva pestaña con:
                                    <ul>
                                        <li><strong>Nombre</strong>: Ej. "Asistencia", "Ponente", etc.</li>
                                        <li><strong>Nombre de la Hoja</strong>: Nombre exacto de la pestaña en Google Sheets</li>
                                        <li><strong>Plantilla</strong>: Sube una imagen PNG/JPG para el fondo del certificado</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>💡 Consejo:</strong> Las plantillas funcionan mejor con imágenes de alta resolución (300 DPI) en formato apaisado.
                        </div>
                    </section>

                    <!-- 7. Mapeo de Campos -->
                    <section id="campos" class="doc-section">
                        <h2><span class="section-number">7.</span> <?php _e( 'Mapeo de Campos', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Configuración Automática', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Ve a <code>Certificados → Mapeo de Columnas</code></li>
                                <li>Selecciona tu evento</li>
                                <li>El sistema detectará automáticamente las columnas de tu Google Sheet</li>
                                <li>Revisa y guarda el mapeo</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Campos del Certificado', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Después de mapear, configura la posición de cada campo:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li>Coordenadas X, Y en la plantilla</li>
                                <li>Fuente y tamaño de texto</li>
                                <li>Alineación (izquierda, centro, derecha)</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 8. Encuestas -->
                    <section id="encuestas" class="doc-section">
                        <h2><span class="section-number">8.</span> <?php _e( 'Encuestas de Satisfacción', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Tipos de Encuestas', 'certificados-digitales' ); ?></h3>
                            <ul>
                                <li><strong>Deshabilitada:</strong> No se requiere encuesta</li>
                                <li><strong>Opcional:</strong> Se abre en nueva pestaña después de descargar</li>
                                <li><strong>Obligatoria:</strong> Bloquea la descarga hasta completarla</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Configuración', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Ve a <code>Certificados → Encuestas</code></li>
                                <li>Selecciona el evento</li>
                                <li>Configura:
                                    <ul>
                                        <li>URL del formulario (Google Forms, etc.)</li>
                                        <li>Sheet ID de respuestas</li>
                                        <li>Columnas de validación</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                    </section>

                    <!-- 9. Estadísticas -->
                    <section id="estadisticas" class="doc-section">
                        <h2><span class="section-number">9.</span> <?php _e( 'Estadísticas y Reportes', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <p><?php _e( 'El módulo de estadísticas te permite:', 'certificados-digitales' ); ?></p>
                            <ul class="checklist">
                                <li>Ver total de descargas en tiempo real</li>
                                <li>Analizar descargas por evento</li>
                                <li>Exportar reportes en CSV</li>
                                <li>Visualizar gráficos de tendencias</li>
                                <li>Identificar certificados más descargados</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 10. Shortcodes -->
                    <section id="shortcodes" class="doc-section">
                        <h2><span class="section-number">10.</span> <?php _e( 'Usar Shortcodes', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Insertar en Páginas', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Copia el shortcode del evento desde el dashboard y pégalo en cualquier página o entrada:', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
                                <code>[certificados_evento id="1"]</code>
                            </div>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>💡 Tip:</strong> Puedes usar el botón de copiar junto a cada evento para obtener el shortcode automáticamente.
                        </div>
                    </section>

                    <!-- 11. Verificación -->
                    <section id="verificacion" class="doc-section">
                        <h2><span class="section-number">11.</span> <?php _e( 'Verificación y Pruebas', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Checklist de Verificación', 'certificados-digitales' ); ?></h3>
                            <ul class="checklist">
                                <li>✅ API Key de Google configurada correctamente</li>
                                <li>✅ Google Sheet es accesible públicamente</li>
                                <li>✅ Columnas mapeadas correctamente</li>
                                <li>✅ Plantilla subida y visible</li>
                                <li>✅ Campos posicionados en coordenadas correctas</li>
                                <li>✅ Shortcode insertado en página de prueba</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Prueba de Descarga', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Ve a la página donde insertaste el shortcode</li>
                                <li>Ingresa un número de documento que exista en tu Google Sheet</li>
                                <li>Haz clic en <strong>Buscar</strong></li>
                                <li>Verifica que el certificado se descargue correctamente</li>
                                <li>Revisa que todos los campos estén visibles y correctos</li>
                            </ol>
                        </div>
                    </section>

                    <!-- 12. Solucionar Problemas -->
                    <section id="solucionar-problemas" class="doc-section">
                        <h2><span class="section-number">12.</span> <?php _e( 'Solucionar Problemas Comunes', 'certificados-digitales' ); ?></h2>

                        <div class="doc-troubleshoot">
                            <div class="trouble-item">
                                <h4>❌ "No se encontró certificado"</h4>
                                <p><strong>Solución:</strong></p>
                                <ul>
                                    <li>Verifica que el número de documento existe en el Sheet</li>
                                    <li>Revisa el nombre exacto de la hoja de Google Sheets</li>
                                    <li>Comprueba el mapeo de columnas</li>
                                </ul>
                            </div>

                            <div class="trouble-item">
                                <h4>❌ "Error al consultar Google Sheets"</h4>
                                <p><strong>Solución:</strong></p>
                                <ul>
                                    <li>Verifica que la API Key sea correcta</li>
                                    <li>Asegúrate de que Google Sheets API esté habilitada</li>
                                    <li>Revisa que el Sheet ID sea el correcto</li>
                                    <li>Confirma que el sheet sea público o compartido</li>
                                </ul>
                            </div>

                            <div class="trouble-item">
                                <h4>❌ Los campos no aparecen en el certificado</h4>
                                <p><strong>Solución:</strong></p>
                                <ul>
                                    <li>Revisa las coordenadas X, Y de cada campo</li>
                                    <li>Verifica el tamaño de fuente (debe ser > 0)</li>
                                    <li>Comprueba que los campos estén activos</li>
                                </ul>
                            </div>

                            <div class="trouble-item">
                                <h4>❌ Estadísticas vacías</h4>
                                <p><strong>Solución:</strong></p>
                                <ul>
                                    <li>Descarga al menos un certificado de prueba</li>
                                    <li>Espera unos segundos y refresca la página</li>
                                    <li>Verifica que la migración de datos se haya ejecutado</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- 13. Experiencia del Usuario Final -->
                    <section id="experiencia-usuario" class="doc-section">
                        <h2><span class="section-number">13.</span> <?php _e( 'Experiencia del Usuario Final', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( '¿Cómo descargan los usuarios sus certificados?', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Esta sección describe el proceso completo que experimentan los usuarios finales al descargar sus certificados:', 'certificados-digitales' ); ?></p>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 1: Acceder a la Página', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>El usuario recibe un enlace a la página donde está insertado el shortcode del evento</li>
                                <li>Al acceder, verá un formulario con el logo del evento (si fue configurado)</li>
                                <li>El formulario muestra campos según el mapeo configurado (típicamente: Tipo de Documento, Número de Documento)</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 2: Ingresar Datos', 'certificados-digitales' ); ?></h3>
                            <ul>
                                <li><strong>Tipo de Documento:</strong> El usuario selecciona su tipo de identificación (CC, CE, Pasaporte, etc.)</li>
                                <li><strong>Número de Documento:</strong> Ingresa su número de identificación exactamente como aparece en la base de datos</li>
                                <li>Si hay campos adicionales configurados, también aparecerán en el formulario</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 3: Buscar Certificado', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>Al hacer clic en el botón <strong>"Buscar"</strong>, el sistema muestra un loader animado</li>
                                <li>El plugin consulta Google Sheets (primero en caché, luego en la API si es necesario)</li>
                                <li>Si el documento no existe, muestra el mensaje: <em>"No se encontró ningún certificado con ese número de documento"</em></li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 4: Seleccionar Pestaña (si hay múltiples)', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Si el evento tiene múltiples pestañas configuradas:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li>El sistema muestra tarjetas (tabs) con los diferentes tipos de certificado disponibles</li>
                                <li>Cada tarjeta muestra una vista previa de la plantilla</li>
                                <li>El usuario hace clic en el certificado que desea descargar</li>
                            </ul>
                            <p><?php _e( 'Si solo hay una pestaña, se procede automáticamente a la descarga.', 'certificados-digitales' ); ?></p>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 5: Encuesta de Satisfacción (opcional)', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Dependiendo de la configuración:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li><strong>Sin encuesta:</strong> El certificado se descarga inmediatamente</li>
                                <li><strong>Encuesta opcional:</strong> Se abre la encuesta en una nueva pestaña/modal, pero el usuario puede cerrarla y descargar igual</li>
                                <li><strong>Encuesta obligatoria:</strong> El usuario DEBE completar la encuesta para poder descargar el certificado. El botón de descarga permanece bloqueado hasta que complete el formulario</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Paso 6: Descarga del PDF', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li>El navegador descarga automáticamente un archivo PDF con el nombre: <code>Certificado_[NombreEvento]_[NumeroDocumento].pdf</code></li>
                                <li>El PDF incluye:
                                    <ul>
                                        <li>La plantilla configurada como fondo</li>
                                        <li>Todos los campos dinámicos posicionados correctamente</li>
                                        <li>Un código QR de validación (si está configurado)</li>
                                        <li>Fuentes personalizadas y colores aplicados</li>
                                    </ul>
                                </li>
                                <li>El sistema registra esta descarga en las estadísticas</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Verificación del Certificado', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'Si el certificado incluye código QR de validación:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li>El usuario o terceros pueden escanear el código QR con su dispositivo móvil</li>
                                <li>El QR enlaza a una página de verificación que confirma la autenticidad del certificado</li>
                                <li>Muestra información como: Nombre del participante, evento, fecha de emisión, etc.</li>
                            </ul>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>💡 Experiencia Optimizada:</strong> El plugin está diseñado para funcionar perfectamente en dispositivos móviles, tablets y computadoras de escritorio. Los usuarios pueden descargar sus certificados desde cualquier dispositivo.
                        </div>

                        <div class="doc-callout doc-callout-success">
                            <strong>✅ Casos de Uso Comunes:</strong>
                            <ul style="margin-top: 10px;">
                                <li>Eventos académicos: Certificados de asistencia a conferencias, talleres, cursos</li>
                                <li>Capacitaciones empresariales: Constancias de formación</li>
                                <li>Eventos deportivos: Certificados de participación</li>
                                <li>Eventos culturales: Reconocimientos y diplomas</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Sección de Documentación Técnica Descargable -->
                    <section class="doc-download-section">
                        <h3>📚 <?php _e( 'Documentación Técnica para Desarrolladores', 'certificados-digitales' ); ?></h3>
                        <p><?php _e( 'Si eres desarrollador y necesitas información técnica sobre la estructura del plugin, base de datos, clases PHP, hooks y filtros disponibles, descarga la documentación técnica completa.', 'certificados-digitales' ); ?></p>
                        <a href="<?php echo CERTIFICADOS_DIGITALES_URL . 'docs/Documentacion_Tecnica.md'; ?>" class="btn-download-docs" download>
                            <span class="dashicons dashicons-download"></span>
                            <?php _e( 'Descargar Documentación Técnica', 'certificados-digitales' ); ?>
                        </a>
                    </section>

                    <!-- SECCIÓN TÉCNICA REMOVIDA - AHORA EN ARCHIVO DESCARGABLE -->

                    <!-- La documentación técnica (estructura, base de datos, clases, flujos, hooks)
                         ahora está disponible en el archivo descargable Documentacion_Tecnica.md -->

                    <!-- 13. Estructura del Proyecto --> <!-- REMOVIDO -->
                    <section id="estructura-proyecto" class="doc-section" style="display:none;">
                        <h2><span class="section-number">13.</span> <?php _e( 'Estructura del Proyecto', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Árbol de Directorios', 'certificados-digitales' ); ?></h3>
                            <div class="doc-code">
<pre>certificados-digitales/
├── admin/
│   ├── css/                          # Estilos del admin
│   │   ├── admin-style.css           # Estilos principales
│   │   ├── dashboard.css             # Estilos del dashboard
│   │   └── configurador-campos.css   # Estilos del configurador visual
│   ├── js/                           # Scripts del admin
│   │   ├── eventos-admin.js          # Gestión de eventos
│   │   ├── pestanas-admin.js         # Gestión de pestañas
│   │   ├── fuentes-admin.js          # Gestión de fuentes
│   │   ├── configurador-campos.js    # Configurador visual
│   │   ├── column-mapper.js          # Mapeo de columnas
│   │   ├── survey-admin.js           # Gestión de encuestas
│   │   └── stats-admin.js            # Estadísticas con Chart.js
│   ├── class-admin.php               # Controlador principal del admin
│   ├── class-admin-column-mapper.php # Página de mapeo de columnas
│   ├── class-admin-survey.php        # Página de encuestas
│   ├── class-admin-stats.php         # Página de estadísticas
│   ├── class-admin-documentacion.php # Esta página de documentación
│   ├── class-campos.php              # Gestión de campos (legacy)
│   └── class-fuentes.php             # Gestión de fuentes
├── includes/
│   ├── class-core.php                # Clase principal del plugin
│   ├── class-activator.php           # Ejecuta tareas al activar
│   ├── class-deactivator.php         # Ejecuta tareas al desactivar
│   ├── class-autoloader.php          # Carga automática de clases
│   ├── class-google-sheets.php       # Integración Google Sheets API v4
│   ├── class-pdf-generator.php       # Generación de PDFs con TCPDF
│   ├── class-eventos-manager.php     # CRUD de eventos
│   ├── class-pestanas-manager.php    # CRUD de pestañas
│   ├── class-campos-manager.php      # CRUD de campos
│   ├── class-fuentes-manager.php     # CRUD de fuentes personalizadas
│   ├── class-shortcode.php           # Shortcode del formulario frontend
│   ├── class-sheets-cache-manager.php # Sistema de caché para Google Sheets
│   ├── class-column-mapper.php       # Mapeo dinámico de columnas
│   ├── class-survey-manager.php      # Sistema de encuestas de satisfacción
│   └── class-stats-manager.php       # Sistema de estadísticas de descargas
├── public/
│   ├── css/
│   │   └── public-style.css          # Estilos del frontend
│   └── js/
│       └── public-script.js          # Scripts del frontend
├── uploads/                          # Directorio de archivos subidos
│   ├── fonts/                        # Fuentes personalizadas (.ttf)
│   ├── plantillas/                   # Plantillas PDF (.pdf)
│   └── logos/                        # Logos de eventos
├── languages/                        # Archivos de traducción
└── certificados-digitales.php        # Archivo principal del plugin</pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Arquitectura del Plugin', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'El plugin sigue el patrón arquitectónico MVC (Modelo-Vista-Controlador) adaptado a WordPress:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li><strong>Modelo:</strong> Clases *-manager.php en /includes/</li>
                                <li><strong>Vista:</strong> Métodos render_*_page() en clases admin</li>
                                <li><strong>Controlador:</strong> AJAX handlers y métodos de procesamiento</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 14. Base de Datos --> <!-- REMOVIDO -->
                    <section id="base-datos" class="doc-section" style="display:none;">
                        <h2><span class="section-number">14.</span> <?php _e( 'Estructura de Base de Datos', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Tablas Principales', 'certificados-digitales' ); ?></h3>
                            <p><?php _e( 'El plugin crea 8 tablas en la base de datos de WordPress:', 'certificados-digitales' ); ?></p>
                        </div>

                        <div class="doc-step">
                            <h4>1. certificados_eventos</h4>
                            <p><?php _e( 'Almacena los eventos configurados (cada evento representa un tipo de certificado).', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    sheet_id VARCHAR(255) NOT NULL,
    sheet_name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Campos clave:</strong> sheet_id (ID del Google Sheet), sheet_name (nombre de la hoja), activo (0/1)</p>
                        </div>

                        <div class="doc-step">
                            <h4>2. certificados_pestanas</h4>
                            <p><?php _e( 'Cada evento puede tener múltiples pestañas (diferentes plantillas de certificado).', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_pestanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    nombre_pestana VARCHAR(255) NOT NULL,
    plantilla_url VARCHAR(500),
    orden INT DEFAULT 0,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Relación:</strong> Muchas pestañas → Un evento. Eliminación en cascada.</p>
                        </div>

                        <div class="doc-step">
                            <h4>3. certificados_campos</h4>
                            <p><?php _e( 'Configuración de campos dinámicos en cada pestaña (posición, tamaño, fuente).', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_campos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pestana_id INT NOT NULL,
    nombre_campo VARCHAR(100) NOT NULL,
    tipo_campo VARCHAR(50) NOT NULL,
    pos_x FLOAT NOT NULL,
    pos_y FLOAT NOT NULL,
    ancho FLOAT,
    alto FLOAT,
    font_family VARCHAR(100),
    font_size INT,
    color_hex VARCHAR(7),
    alineacion VARCHAR(20) DEFAULT 'left',
    FOREIGN KEY (pestana_id) REFERENCES certificados_pestanas(id) ON DELETE CASCADE
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Tipos de campo:</strong> text, qr, image. Posiciones en milímetros.</p>
                        </div>

                        <div class="doc-step">
                            <h4>4. certificados_fuentes</h4>
                            <p><?php _e( 'Fuentes TTF personalizadas para usar en los certificados.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_fuentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    archivo_ttf VARCHAR(255) NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP
);</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h4>5. certificados_column_mapping</h4>
                            <p><?php _e( 'Mapeo dinámico entre columnas de Google Sheets y campos del sistema.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_column_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    campo_sistema VARCHAR(100) NOT NULL,
    columna_sheet VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_mapping (evento_id, campo_sistema)
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Ejemplo:</strong> campo_sistema='nombre' → columna_sheet='Nombre Completo'</p>
                        </div>

                        <div class="doc-step">
                            <h4>6. certificados_surveys</h4>
                            <p><?php _e( 'Configuración de encuestas de satisfacción por evento.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    activa TINYINT(1) DEFAULT 1,
    obligatoria TINYINT(1) DEFAULT 0,
    tipo_apertura ENUM('modal', 'nueva_ventana') DEFAULT 'modal',
    url_encuesta VARCHAR(500) NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Tipos:</strong> modal (iframe dentro de la página), nueva_ventana (tab separada)</p>
                        </div>

                        <div class="doc-step">
                            <h4>7. certificados_stats</h4>
                            <p><?php _e( 'Registro de cada descarga de certificado para estadísticas.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_stats (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    pestana_id INT,
    numero_documento VARCHAR(100),
    fecha_descarga DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_usuario VARCHAR(45),
    user_agent TEXT,
    INDEX idx_evento (evento_id),
    INDEX idx_fecha (fecha_descarga),
    INDEX idx_documento (numero_documento),
    FOREIGN KEY (evento_id) REFERENCES certificados_eventos(id) ON DELETE CASCADE
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Índices:</strong> Optimizados para consultas por evento, fecha y documento</p>
                        </div>

                        <div class="doc-step">
                            <h4>8. certificados_sheets_cache</h4>
                            <p><?php _e( 'Sistema de caché para evitar llamadas excesivas a Google Sheets API.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>CREATE TABLE certificados_sheets_cache (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sheet_id VARCHAR(255) NOT NULL,
    sheet_name VARCHAR(255) NOT NULL,
    numero_documento VARCHAR(100) NOT NULL,
    cached_data TEXT,
    data_hash VARCHAR(32),
    fecha_cache DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME,
    UNIQUE KEY unique_cache (sheet_id, sheet_name, numero_documento),
    INDEX idx_expiracion (fecha_expiracion)
);</code></pre>
                            </div>
                            <p class="code-note"><strong>Mecanismo:</strong> Hash MD5 para detectar cambios. Expiración automática.</p>
                        </div>

                        <div class="doc-callout doc-callout-info">
                            <strong>ℹ️ Importante:</strong> Todas las relaciones usan <code>ON DELETE CASCADE</code>, por lo que al eliminar un evento se eliminan automáticamente todas sus pestañas, campos, mapeos, encuestas y estadísticas relacionadas.
                        </div>
                    </section>

                    <!-- 15. Clases Principales --> <!-- REMOVIDO -->
                    <section id="clases-principales" class="doc-section" style="display:none;">
                        <h2><span class="section-number">15.</span> <?php _e( 'Clases Principales', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3>Certificados_Digitales_Core</h3>
                            <p><?php _e( 'Clase principal que inicializa el plugin y registra todos los hooks.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-core.php
class Certificados_Digitales_Core {
    public function run() {
        // Registra hooks de activación/desactivación
        // Carga textdomain para traducciones
        // Inicializa managers y componentes
    }
}</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_Google_Sheets</h3>
                            <p><?php _e( 'Wrapper para Google Sheets API v4. Gestiona la comunicación con Google.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-google-sheets.php
class Certificados_Google_Sheets {
    public function get_sheet_data($sheet_name)
    public function buscar_por_documento($sheet_name, $numero_documento)
    public function buscar_en_datos($data, $numero_documento)
    public function validar_conexion($sheet_name)
}</code></pre>
                            </div>
                            <p class="code-note"><strong>Métodos clave:</strong> Usa HTTP API de WordPress, no la librería PHP de Google</p>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_PDF_Generator</h3>
                            <p><?php _e( 'Genera PDFs usando TCPDF. Aplica plantillas y dibuja campos dinámicos.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-pdf-generator.php
class Certificados_PDF_Generator {
    public function generar_certificado($pestana_id, $datos_usuario)

    // Proceso interno:
    // 1. Carga plantilla PDF como fondo
    // 2. Lee configuración de campos desde BD
    // 3. Dibuja textos con fuentes personalizadas
    // 4. Genera código QR de validación
    // 5. Retorna PDF o error
}</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_Sheets_Cache_Manager</h3>
                            <p><?php _e( 'Sistema de caché con detección de cambios en Google Sheets.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-sheets-cache-manager.php
class Certificados_Sheets_Cache_Manager {
    public function get_cached_data($sheet_id, $sheet_name, $numero_documento)
    public function save_to_cache($sheet_id, $sheet_name, $numero_documento, $data)
    public function clear_expired_cache()
    public function detect_changes($sheet_id, $sheet_name, $numero_documento, $new_data)

    // Lógica de cambios:
    // - Calcula hash MD5 de los datos
    // - Compara con hash almacenado
    // - Si difieren, actualiza caché y retorna los nuevos datos
}</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_Column_Mapper</h3>
                            <p><?php _e( 'Permite mapear dinámicamente columnas de Sheets a campos del sistema.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-column-mapper.php
class Certificados_Column_Mapper {
    public function get_mapping($evento_id)
    public function save_mapping($evento_id, $mappings)
    public function apply_mapping($evento_id, $sheet_data)

    // Campos del sistema soportados:
    // - numero_documento
    // - nombre
    // - tipo_documento
    // - tipo_trabajo
    // - ciudad_expedicion
}</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_Survey_Manager</h3>
                            <p><?php _e( 'Gestión de encuestas de satisfacción integradas al flujo de descarga.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-survey-manager.php
class Certificados_Survey_Manager {
    public function get_survey_by_event($evento_id)
    public function should_show_survey($evento_id)
    public function render_survey_modal($survey_config)

    // Tipos de apertura:
    // - modal: iframe dentro de modal lightbox
    // - nueva_ventana: target="_blank"

    // Si es obligatoria: bloquea descarga hasta completar
}</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3>Certificados_Stats_Manager</h3>
                            <p><?php _e( 'Sistema completo de estadísticas y analytics de descargas.', 'certificados-digitales' ); ?></p>
                            <div class="doc-code">
<pre><code>// Ubicación: includes/class-stats-manager.php
class Certificados_Stats_Manager {
    public function register_download($evento_id, $pestana_id, $numero_documento)
    public function get_overview_stats($days)
    public function get_timeline_stats($days, $group_by)
    public function get_stats_by_event($days)
    public function get_top_downloads($days, $limit)
    public function export_to_csv($days, $evento_id)

    // Métricas calculadas:
    // - Total descargas
    // - Usuarios únicos (por número de documento)
    // - Descargas hoy
    // - Promedio diario
    // - Tendencias por día/semana/mes
}</code></pre>
                            </div>
                        </div>
                    </section>

                    <!-- 16. Flujo de Funcionamiento --> <!-- REMOVIDO -->
                    <section id="flujo-funcionamiento" class="doc-section" style="display:none;">
                        <h2><span class="section-number">16.</span> <?php _e( 'Flujo de Funcionamiento', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Proceso Completo de Descarga de Certificado', 'certificados-digitales' ); ?></h3>
                            <ol>
                                <li><strong>Usuario ingresa datos:</strong> En formulario del shortcode [certificados slug="evento"]</li>
                                <li><strong>Validación frontend:</strong> JavaScript valida campos obligatorios</li>
                                <li><strong>AJAX a WordPress:</strong> Envía datos a admin-ajax.php</li>
                                <li><strong>Aplicar mapeo de columnas:</strong> Traduce campos del sistema a nombres de columnas del Sheet</li>
                                <li><strong>Consulta a caché:</strong> ¿Existe en certificados_sheets_cache?
                                    <ul>
                                        <li>✅ Sí → Verifica hash para detectar cambios</li>
                                        <li>❌ No → Consulta Google Sheets API</li>
                                    </ul>
                                </li>
                                <li><strong>Google Sheets API:</strong> GET a https://sheets.googleapis.com/v4/spreadsheets/...</li>
                                <li><strong>Procesar datos:</strong> Buscar documento, mapear campos</li>
                                <li><strong>Guardar en caché:</strong> Con hash MD5 y fecha de expiración</li>
                                <li><strong>¿Hay encuesta configurada?</strong>
                                    <ul>
                                        <li>Sí + Obligatoria → Mostrar modal/ventana, bloquear descarga</li>
                                        <li>Sí + Opcional → Mostrar pero permitir cerrar</li>
                                        <li>No → Continuar directamente</li>
                                    </ul>
                                </li>
                                <li><strong>Generar PDF:</strong>
                                    <ul>
                                        <li>Cargar plantilla PDF como fondo</li>
                                        <li>Leer configuración de campos de certificados_campos</li>
                                        <li>Dibujar textos con TCPDF usando fuentes personalizadas</li>
                                        <li>Generar QR con datos de validación</li>
                                    </ul>
                                </li>
                                <li><strong>Registrar estadística:</strong> INSERT en certificados_stats</li>
                                <li><strong>Enviar PDF:</strong> Headers de descarga + Output del PDF</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Diagrama de Flujo de Datos', 'certificados-digitales' ); ?></h3>
                            <div class="doc-code">
<pre>┌─────────────────┐
│  Usuario Web    │
└────────┬────────┘
         │ 1. Ingresa documento
         ▼
┌─────────────────────────┐
│  Shortcode Frontend     │  (includes/class-shortcode.php)
└────────┬────────────────┘
         │ 2. AJAX Request
         ▼
┌─────────────────────────┐
│  Column Mapper          │  (includes/class-column-mapper.php)
└────────┬────────────────┘
         │ 3. Mapear campos
         ▼
┌─────────────────────────┐
│  Cache Manager          │  (includes/class-sheets-cache-manager.php)
└────────┬────────────────┘
         │ 4. ¿Cache válido?
         │    No ↓    Sí → Retornar datos
         ▼
┌─────────────────────────┐
│  Google Sheets API      │  (includes/class-google-sheets.php)
└────────┬────────────────┘
         │ 5. Obtener datos
         ▼
┌─────────────────────────┐
│  Survey Manager         │  (includes/class-survey-manager.php)
└────────┬────────────────┘
         │ 6. ¿Mostrar encuesta?
         ▼
┌─────────────────────────┐
│  PDF Generator          │  (includes/class-pdf-generator.php)
└────────┬────────────────┘
         │ 7. Generar certificado
         ▼
┌─────────────────────────┐
│  Stats Manager          │  (includes/class-stats-manager.php)
└────────┬────────────────┘
         │ 8. Registrar descarga
         ▼
┌─────────────────┐
│  Descarga PDF   │
└─────────────────┘</pre>
                            </div>
                        </div>
                    </section>

                    <!-- 17. Hooks y Filtros --> <!-- REMOVIDO -->
                    <section id="hooks-filtros" class="doc-section" style="display:none;">
                        <h2><span class="section-number">17.</span> <?php _e( 'Hooks y Filtros Disponibles', 'certificados-digitales' ); ?></h2>

                        <div class="doc-step">
                            <h3><?php _e( 'Actions (Acciones)', 'certificados-digitales' ); ?></h3>
                            <div class="doc-code">
<pre><code>// Después de activar el plugin
do_action('certificados_digitales_activated');

// Antes de generar el PDF
do_action('certificados_before_pdf_generation', $pestana_id, $datos);

// Después de generar el PDF
do_action('certificados_after_pdf_generation', $pestana_id, $datos, $pdf_path);

// Al registrar una descarga
do_action('certificados_download_registered', $stat_id, $evento_id);

// Al guardar mapeo de columnas
do_action('certificados_column_mapping_saved', $evento_id, $mappings);</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Filters (Filtros)', 'certificados-digitales' ); ?></h3>
                            <div class="doc-code">
<pre><code>// Modificar datos antes de usarlos en el PDF
apply_filters('certificados_pdf_data', $datos, $pestana_id);

// Modificar URL del QR de validación
apply_filters('certificados_qr_url', $url, $numero_documento);

// Modificar tiempo de expiración del caché (en segundos)
apply_filters('certificados_cache_expiration', 3600); // Default: 1 hora

// Modificar campos del sistema disponibles para mapeo
apply_filters('certificados_system_fields', $campos);

// Modificar SQL de consulta de estadísticas
apply_filters('certificados_stats_query', $query, $params);</code></pre>
                            </div>
                        </div>

                        <div class="doc-step">
                            <h3><?php _e( 'Ejemplo de Uso de Hooks', 'certificados-digitales' ); ?></h3>
                            <div class="doc-code">
<pre><code>// En functions.php del tema o en otro plugin

// Agregar campo personalizado a los datos del PDF
add_filter('certificados_pdf_data', function($datos, $pestana_id) {
    $datos['campo_custom'] = 'Valor personalizado';
    return $datos;
}, 10, 2);

// Enviar email al registrar descarga
add_action('certificados_download_registered', function($stat_id, $evento_id) {
    // Código para enviar email
    wp_mail(...);
}, 10, 2);

// Cambiar tiempo de caché a 2 horas
add_filter('certificados_cache_expiration', function($seconds) {
    return 7200; // 2 horas
});</code></pre>
                            </div>
                        </div>

                        <div class="doc-callout doc-callout-warning">
                            <strong>⚠️ Advertencia:</strong> Al usar hooks y filtros, asegúrate de mantener la compatibilidad con las actualizaciones del plugin. Siempre usa prioridades adecuadas y verifica los parámetros recibidos.
                        </div>
                    </section>

                    <!-- Soporte -->
                    <section class="doc-section doc-support">
                        <h2><?php _e( '¿Necesitas Ayuda Adicional?', 'certificados-digitales' ); ?></h2>
                        <div class="support-box">
                            <p><?php _e( 'Si tienes problemas o preguntas que no están cubiertas en esta documentación:', 'certificados-digitales' ); ?></p>
                            <ul>
                                <li>📧 Contacta a: <strong>webmaster@uninavarra.edu.co</strong></li>
                                <li>👨‍💻 Desarrollado por: <strong>Luis Quino</strong></li>
                                <li>🤖 Con ayuda de: <strong>Claude AI</strong></li>
                            </ul>
                        </div>
                    </section>

                </div>
            </div>
        </div>
        <?php
    }
}

// Inicializar
new Certificados_Admin_Documentacion();
