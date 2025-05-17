<?php
/**
 * Vista principal de administración del plugin -
 * plugins-main/admin/partials/certificados-pdf-admin-display.php
 * @since      1.0.0
 */
?>

<div class="wrap certificados-pdf-admin">
    <h1 class="wp-heading-inline"><?php _e('Certificados PDF', 'certificados-pdf'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=certificados_pdf_nuevo'); ?>" class="page-title-action"><?php _e('Añadir Nuevo', 'certificados-pdf'); ?></a>
    <hr class="wp-header-end">
    
    <?php if (empty($certificados)): ?>
        <div class="notice notice-info">
            <p><?php _e('No hay certificados creados. ¡Comienza creando uno!', 'certificados-pdf'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped certificados-table">
            <thead>
                <tr>
                    <th scope="col" class="column-id"><?php _e('ID', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-name"><?php _e('Nombre', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-sheet"><?php _e('Google Sheet', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-status"><?php _e('Estado', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-shortcode"><?php _e('Shortcode', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-date"><?php _e('Fecha', 'certificados-pdf'); ?></th>
                    <th scope="col" class="column-actions"><?php _e('Acciones', 'certificados-pdf'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($certificados as $cert): ?>
                    <tr>
                        <td class="column-id"><?php echo $cert->id; ?></td>
                        <td class="column-name">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=certificados_pdf_nuevo&id=' . $cert->id); ?>"><?php echo esc_html($cert->nombre); ?></a>
                            </strong>
                        </td>
                        <td class="column-sheet">
                            <?php echo esc_html($cert->sheet_nombre); ?>
                            <div class="row-actions">
                                <span class="sheet-id"><?php echo esc_html($cert->sheet_id); ?></span>
                            </div>
                        </td>
                        <td class="column-status">
                            <?php if ($cert->habilitado): ?>
                                <span class="status-enabled"><?php _e('Habilitado', 'certificados-pdf'); ?></span>
                            <?php else: ?>
                                <span class="status-disabled"><?php _e('Deshabilitado', 'certificados-pdf'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-shortcode">
                            <code>[certificado_pdf id="<?php echo $cert->id; ?>"]</code>
                            <button type="button" class="button button-small copy-shortcode" data-shortcode='[certificado_pdf id="<?php echo $cert->id; ?>"]'>
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                        <td class="column-date">
                            <?php echo date_i18n(get_option('date_format'), strtotime($cert->fecha_creacion)); ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php echo admin_url('admin.php?page=certificados_pdf_nuevo&id=' . $cert->id); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Editar', 'certificados-pdf'); ?>
                            </a>
                            <a href="#" class="button button-small delete-cert" data-id="<?php echo $cert->id; ?>">
                                <span class="dashicons dashicons-trash"></span> <?php _e('Eliminar', 'certificados-pdf'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Copiar shortcode al portapapeles
    $('.copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        var $this = $(this);
        $this.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
        setTimeout(function() {
            $this.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
        }, 1000);
    });
    
    // Eliminar certificado
    $('.delete-cert').on('click', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        
        if (confirm('<?php _e('¿Estás seguro que deseas eliminar este certificado?', 'certificados-pdf'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eliminar_certificado',
                    id: id,
                    nonce: certificados_pdf_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>