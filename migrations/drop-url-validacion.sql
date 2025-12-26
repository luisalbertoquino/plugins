-- =====================================================
-- MIGRACIÓN: Eliminar columna url_validacion
-- =====================================================
-- Este script elimina la columna url_validacion de la tabla
-- wp_certificados_eventos ya que ya no se utiliza.
--
-- NOTA: La columna se eliminará automáticamente cuando
-- se reactive el plugin, pero puedes ejecutar este script
-- manualmente si necesitas hacerlo antes.
--
-- INSTRUCCIONES:
-- 1. Reemplaza 'wp_' por el prefijo de tu base de datos si es diferente
-- 2. Ejecuta este script en phpMyAdmin o en tu cliente MySQL
-- =====================================================

-- Verificar si la columna existe antes de eliminarla
SET @table_name = 'wp_certificados_eventos';
SET @column_name = 'url_validacion';

SET @query = CONCAT('ALTER TABLE ', @table_name, ' DROP COLUMN IF EXISTS ', @column_name);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mensaje de confirmación
SELECT CONCAT('Columna ', @column_name, ' eliminada de ', @table_name) AS resultado;
