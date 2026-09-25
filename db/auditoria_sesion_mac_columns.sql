-- Columnas OAuth/MAC faltantes en auditoria.sesion (produccion).
-- Ejecutar como usuario con privilegio ALTER (phpMyAdmin / admin Plesk).
-- El usuario de la app (user_relavera) solo tiene SELECT/INSERT/UPDATE/DELETE.
-- Compatible con MariaDB 5.5 (sin IF NOT EXISTS en ADD COLUMN).

USE `auditoria`;

SET @db := 'auditoria';

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Dev_Cod'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Dev_Cod` VARCHAR(64) DEFAULT NULL AFTER `Ses_Token`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Mac'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Mac` VARCHAR(17) DEFAULT NULL AFTER `Ses_Dev_Cod`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_OAuth_Tok'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_OAuth_Tok` VARCHAR(64) DEFAULT NULL AFTER `Ses_Mac`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Fingerprint'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Fingerprint` VARCHAR(40) DEFAULT NULL AFTER `Ses_OAuth_Tok`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
