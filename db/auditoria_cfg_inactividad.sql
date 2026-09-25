-- Configuracion de cierre de sesion por inactividad (por empresa).
-- Tambien se crea automaticamente via aud_idle_ensure_schema().

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
