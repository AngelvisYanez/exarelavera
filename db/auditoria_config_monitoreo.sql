-- Tabla cfg_monitoreo (auditoria) + referencia a menu.
-- El menu completo (directorios/procesos) esta en db/auditoria_menu.sql

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

-- Nota: ejecutar tambien db/auditoria_menu.sql en la BD distribuida (exa)
-- para registrar Directorio Auditoria > Monitoreo > Consultar/Configurar.
