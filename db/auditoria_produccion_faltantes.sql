-- =============================================================================
-- Auditoria EXA - Esquema faltante para produccion
-- Ejecutar en la base `auditoria` (MySQL/MariaDB) ANTES de subir el codigo.
-- Seguro de re-ejecutar: usa IF NOT EXISTS / comprobaciones de columnas.
-- =============================================================================

USE `auditoria`;

-- -----------------------------------------------------------------------------
-- 1) Configuracion de cierre por inactividad (NUEVO - requerido para esta entrega)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_inactividad` (
  `Emp_Cod` INT(11) NOT NULL,
  `Cfg_Activo` CHAR(1) NOT NULL DEFAULT 'A',
  `Cfg_Minutos` INT(11) NOT NULL DEFAULT 15,
  `Cfg_Advertencia_Seg` INT(11) NOT NULL DEFAULT 60,
  `Cfg_Titulo` VARCHAR(160) NOT NULL DEFAULT 'Advertencia de Inactividad',
  `Cfg_Texto` VARCHAR(600) NOT NULL DEFAULT 'Tu sesion ha permanecido inactiva. Por seguridad, se cerrara automaticamente en {segundos} segundos si no se detecta actividad.',
  `Usu_Cod` INT(11) DEFAULT NULL,
  `Cfg_Fec` DATETIME DEFAULT NULL,
  PRIMARY KEY (`Emp_Cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Opcional: dejar inactivo por defecto en prod hasta configurar tiempos
-- (descomentar si quieres activar solo desde la UI despues del deploy)
-- INSERT INTO `auditoria`.`cfg_inactividad`
--   (`Emp_Cod`, `Cfg_Activo`, `Cfg_Minutos`, `Cfg_Advertencia_Seg`, `Cfg_Titulo`, `Cfg_Texto`, `Cfg_Fec`)
-- VALUES
--   (1, 'I', 15, 60, 'Advertencia de Inactividad',
--    'Tu sesion ha permanecido inactiva por casi {minutos} minutos. Por seguridad, se cerrara automaticamente en {segundos} segundos si no se detecta actividad.',
--    NOW())
-- ON DUPLICATE KEY UPDATE `Emp_Cod` = `Emp_Cod`;

-- -----------------------------------------------------------------------------
-- 2) Configuracion de monitoreo (si aun no existe)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_monitoreo` (
  `Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
  `Emp_Cod` INT(11) NOT NULL,
  `Org_Cod` INT(11) NOT NULL,
  `Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
  `Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
  `Cfg_Fec` DATETIME DEFAULT NULL,
  `Usu_Cod` INT(11) DEFAULT NULL,
  PRIMARY KEY (`Cfg_Cod`),
  UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
  KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------------------------------
-- 3) Clave de acceso al directorio
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_acceso` (
  `Emp_Cod` INT(11) NOT NULL,
  `Acc_Clave_Hash` VARCHAR(255) NOT NULL,
  `Acc_Est` CHAR(1) NOT NULL DEFAULT 'A',
  `Acc_Fec` DATETIME DEFAULT NULL,
  `Usu_Cod` INT(11) DEFAULT NULL,
  PRIMARY KEY (`Emp_Cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------------------------------
-- 4) Notificaciones por correo
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_notificaciones` (
  `Not_Cod` INT(11) NOT NULL AUTO_INCREMENT,
  `Emp_Cod` INT(11) NOT NULL,
  `Org_Cod` INT(11) NOT NULL,
  `Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
  `Not_Eventos` VARCHAR(10) NOT NULL DEFAULT 'U,D',
  `Not_Correos` TEXT NULL,
  `Not_Usuarios` VARCHAR(255) NULL,
  `Not_Est` CHAR(1) NOT NULL DEFAULT 'A',
  `Not_Fec` DATETIME DEFAULT NULL,
  `Usu_Cod` INT(11) DEFAULT NULL,
  PRIMARY KEY (`Not_Cod`),
  KEY `idx_emp_est` (`Emp_Cod`,`Not_Est`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_correo_usuario` (
  `Usu_Cod` INT(11) NOT NULL,
  `Emp_Cod` INT(11) NOT NULL,
  `Correo` VARCHAR(150) NOT NULL,
  `Fec_Reg` DATETIME DEFAULT NULL,
  PRIMARY KEY (`Usu_Cod`,`Emp_Cod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- -----------------------------------------------------------------------------
-- 5) Tabla sesion (base) + columnas de actividad / OAuth / fingerprint
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria`.`sesion` (
  `Ses_Cod` INT(11) NOT NULL,
  `Usu_Cod` INT(11) NOT NULL,
  `Ses_Int` DATETIME DEFAULT NULL,
  `Ses_Out` DATETIME DEFAULT NULL,
  `Emp_Cod` INT(11) DEFAULT NULL,
  `Suc_Cod` INT(11) DEFAULT NULL,
  `Ses_Ip` VARCHAR(64) DEFAULT NULL,
  `Ses_Ubi` VARCHAR(180) DEFAULT NULL,
  `Ses_Nav` VARCHAR(180) DEFAULT NULL,
  `Ses_Ult_Act` DATETIME DEFAULT NULL,
  `Ses_Min_Uso` INT(11) NOT NULL DEFAULT 0,
  `Ses_Est` CHAR(1) NOT NULL DEFAULT 'A',
  `Ses_Token` VARCHAR(64) DEFAULT NULL,
  `Ses_Dev_Cod` VARCHAR(80) DEFAULT NULL,
  `Ses_Mac` VARCHAR(64) DEFAULT NULL,
  `Ses_OAuth_Tok` VARCHAR(255) DEFAULT NULL,
  `Ses_Fingerprint` VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`Ses_Cod`),
  KEY `idx_ses_est_act` (`Ses_Est`,`Ses_Ult_Act`),
  KEY `idx_ses_emp` (`Emp_Cod`),
  KEY `idx_ses_usu` (`Usu_Cod`),
  KEY `idx_ses_int` (`Ses_Int`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Columnas e indices por si la tabla ya existia con esquema antiguo
-- (MySQL 8 / MariaDB 10.3+ soportan IF NOT EXISTS en ADD COLUMN/INDEX)

SET @db := 'auditoria';

-- Ses_Ip
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Ip'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Ip` VARCHAR(64) DEFAULT NULL AFTER `Suc_Cod`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Ubi
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Ubi'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Ubi` VARCHAR(180) DEFAULT NULL AFTER `Ses_Ip`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Nav
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Nav'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Nav` VARCHAR(180) DEFAULT NULL AFTER `Ses_Ubi`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Ult_Act
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Ult_Act'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Ult_Act` DATETIME DEFAULT NULL AFTER `Ses_Nav`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Min_Uso
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Min_Uso'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Min_Uso` INT(11) NOT NULL DEFAULT 0 AFTER `Ses_Ult_Act`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Est
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Est'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Est` CHAR(1) NOT NULL DEFAULT ''A'' AFTER `Ses_Min_Uso`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Token
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Token'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Token` VARCHAR(64) DEFAULT NULL AFTER `Ses_Est`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Dev_Cod
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Dev_Cod'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Dev_Cod` VARCHAR(80) DEFAULT NULL AFTER `Ses_Token`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Mac
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Mac'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Mac` VARCHAR(64) DEFAULT NULL AFTER `Ses_Dev_Cod`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_OAuth_Tok
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_OAuth_Tok'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_OAuth_Tok` VARCHAR(255) DEFAULT NULL AFTER `Ses_Mac`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ses_Fingerprint
SET @exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND COLUMN_NAME='Ses_Fingerprint'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD COLUMN `Ses_Fingerprint` VARCHAR(128) DEFAULT NULL AFTER `Ses_OAuth_Tok`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indices
SET @exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND INDEX_NAME='idx_ses_est_act'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_est_act` (`Ses_Est`,`Ses_Ult_Act`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND INDEX_NAME='idx_ses_emp'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_emp` (`Emp_Cod`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND INDEX_NAME='idx_ses_usu'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_usu` (`Usu_Cod`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='sesion' AND INDEX_NAME='idx_ses_int'
);
SET @sql := IF(@exists=0,
  'ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_int` (`Ses_Int`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Verificacion rapida
-- -----------------------------------------------------------------------------
SHOW TABLES FROM `auditoria` LIKE 'cfg_%';
SHOW TABLES FROM `auditoria` LIKE 'sesion';
SHOW COLUMNS FROM `auditoria`.`cfg_inactividad`;
