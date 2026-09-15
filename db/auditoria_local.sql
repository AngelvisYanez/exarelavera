-- Esquema mínimo de auditoría para entorno local (login y sesiones)
USE auditoria;

CREATE TABLE IF NOT EXISTS `eventos` (
  `Eve_Cod` int(11) NOT NULL AUTO_INCREMENT,
  `Eve_Ini` char(1) NOT NULL,
  `Eve_Des` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`Eve_Cod`),
  KEY `Eve_Ini` (`Eve_Ini`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `tablas` (
  `Tab_Cod` int(11) NOT NULL AUTO_INCREMENT,
  `Tab_Nom` varchar(64) NOT NULL,
  `Tab_Des` varchar(255) DEFAULT NULL,
  `Tab_Ali` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`Tab_Cod`),
  KEY `Tab_Nom` (`Tab_Nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `logs` (
  `Log_Cod` bigint(20) NOT NULL AUTO_INCREMENT,
  `Usu_Cod` int(11) NOT NULL,
  `Pcs_Cod` int(11) NOT NULL,
  `Tab_Cod` int(11) NOT NULL,
  `Log_Fec` datetime NOT NULL,
  `Eve_Cod` int(11) NOT NULL,
  `Log_Cam` varchar(255) DEFAULT NULL,
  `Log_Val` text,
  `Log_Int` text,
  `Emp_Cod` int(11) DEFAULT NULL,
  `Suc_Cod` int(11) DEFAULT NULL,
  PRIMARY KEY (`Log_Cod`),
  KEY `Emp_Fec` (`Emp_Cod`,`Log_Fec`),
  KEY `Usu_Fec` (`Usu_Cod`,`Log_Fec`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- BD ya existente: agregar empresa/sucursal si faltan
SET @aud_emp := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'auditoria' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'Emp_Cod'
);
SET @sql_aud_emp := IF(@aud_emp = 0,
  'ALTER TABLE `auditoria`.`logs` ADD `Emp_Cod` int(11) DEFAULT NULL, ADD `Suc_Cod` int(11) DEFAULT NULL, ADD KEY `Emp_Fec` (`Emp_Cod`,`Log_Fec`)',
  'SELECT 1'
);
PREPARE stmt_aud_emp FROM @sql_aud_emp;
EXECUTE stmt_aud_emp;
DEALLOCATE PREPARE stmt_aud_emp;

INSERT IGNORE INTO `eventos` (`Eve_Cod`, `Eve_Ini`, `Eve_Des`) VALUES
  (1, 'F', 'Fallido'),
  (2, 'I', 'Insertar'),
  (3, 'U', 'Actualizar'),
  (4, 'D', 'Eliminar');

INSERT IGNORE INTO `tablas` (`Tab_Cod`, `Tab_Nom`, `Tab_Des`, `Tab_Ali`) VALUES
  (1, 'usuarios', 'Usuarios del sistema', 'usuarios'),
  (2, 'comprobantes', 'Comprobantes contables', 'comprobantes'),
  (3, 'asientos', 'Asientos contables', 'asientos'),
  (4, 'manifiesto', 'Manifiestos Relavera', 'Manifiestos'),
  (5, 'manifiesto_turnos_cab', 'Turnos Relavera', 'Turnos'),
  (6, 'manifiesto_turnos_det', 'Detalle de turnos Relavera', 'Detalle de turnos'),
  (7, 'manifiesto_visitante', 'Visitantes Relavera', 'Visitantes'),
  (8, 'manifiesto_evento', 'Eventos Relavera', 'Eventos'),
  (9, 'ventas', 'Facturas de venta', 'Facturas de venta'),
  (10, 'ventas_det', 'Detalle de facturas de venta', 'Detalle de venta');
