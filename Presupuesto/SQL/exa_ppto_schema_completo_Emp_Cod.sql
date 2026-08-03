-- =============================================================================
-- EXA PPTO - SCRIPT COMPLETO DE ESQUEMA + LLAVES FORANEAS
-- Nomenclatura EXA: Emp_Cod, Usu_Cod, Suc_Cod, Dep_Cod, Pla_Cod, Pld_Cod
-- Fecha: 2026-08-03
-- Motor: MySQL / MariaDB InnoDB utf8
--
-- REQUISITOS (tablas maestras EXA deben existir):
--   empresas(Emp_Cod BIGINT PK)
--   usuarios(Usu_Cod BIGINT PK)
--   sucursal(Suc_Cod BIGINT PK)
--   departamen(Dep_Cod BIGINT PK)
--   plan_cuenta(Pla_Cod BIGINT PK)
--   det_plan(Pld_Cod BIGINT PK)
--
-- NOTA TIPOS:
--   Emp_Cod/Usu_Cod/Suc_Cod/Dep_Cod/Pla_Cod se definen BIGINT para poder
--   crear FK reales hacia los maestros EXA (que usan BIGINT).
--   Usu_Cod se permite NULL (en lugar de 0) para no romper la FK.
-- =============================================================================

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1) NUCLEO EXA (pre_*)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `pre_presupuesto` (
  `Ppe_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK version de presupuesto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Suc_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK sucursal.Suc_Cod',
  `Dep_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK departamen.Dep_Cod',
  `Ppe_Ani` YEAR(4) NOT NULL COMMENT 'Anio fiscal de la version',
  `Ppe_Ver` TINYINT(4) NOT NULL COMMENT 'Numero de version dentro del anio',
  `Ppe_Des` VARCHAR(255) NOT NULL COMMENT 'Descripcion de la version',
  `Ppe_Est` CHAR(1) NOT NULL DEFAULT 'B' COMMENT 'Estado: B=Borrador A=Activo C=Cerrado',
  `Ppe_Fec` DATE NOT NULL COMMENT 'Fecha de registro',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod (registro)',
  `Usu_Apr` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod (aprobacion)',
  `Ppe_FecApr` DATE DEFAULT NULL COMMENT 'Fecha de aprobacion',
  PRIMARY KEY (`Ppe_Cod`),
  UNIQUE KEY `uq_pre_presupuesto` (`Emp_Cod`,`Ppe_Ani`,`Ppe_Ver`,`Suc_Cod`,`Dep_Cod`),
  KEY `idx_ppe_emp` (`Emp_Cod`),
  KEY `idx_ppe_usu` (`Usu_Cod`),
  KEY `idx_ppe_suc` (`Suc_Cod`),
  KEY `idx_ppe_dep` (`Dep_Cod`),
  CONSTRAINT `fk_ppe_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppe_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppe_usuario_apr` FOREIGN KEY (`Usu_Apr`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ppe_sucursal` FOREIGN KEY (`Suc_Cod`) REFERENCES `sucursal` (`Suc_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ppe_departamento` FOREIGN KEY (`Dep_Cod`) REFERENCES `departamen` (`Dep_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cabeceras/versiones de presupuesto por empresa y anio';

CREATE TABLE IF NOT EXISTS `pre_partidas` (
  `Ppa_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK partida presupuestaria',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppa_Cla` VARCHAR(50) NOT NULL COMMENT 'Codigo clasificacion jerarquico (ej 14.01.01)',
  `Ppa_Des` VARCHAR(255) NOT NULL COMMENT 'Descripcion de la partida',
  `Ppa_Tip` CHAR(1) NOT NULL COMMENT 'Tipo de partida',
  `Ppa_Nat` VARCHAR(20) NOT NULL COMMENT 'Naturaleza (gasto/ingreso/...)',
  `Ppa_Pad` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod (padre)',
  `Ppa_Niv` TINYINT(4) NOT NULL COMMENT 'Nivel jerarquico',
  `Ppa_Clase` CHAR(1) NOT NULL DEFAULT 'D' COMMENT 'G=Grupo D=Detalle',
  `Ppa_Pct` DECIMAL(10,4) DEFAULT NULL COMMENT 'Porcentaje tope del grupo',
  `Ppa_Meses` INT(11) DEFAULT NULL COMMENT 'Meses de prorrateo',
  `Ppa_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `Ppa_Fec` DATE NOT NULL COMMENT 'Fecha de registro',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ppa_Cod`),
  KEY `fk_pre_partidas_padre` (`Ppa_Pad`),
  KEY `idx_ppa_emp` (`Emp_Cod`),
  KEY `idx_ppa_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pre_partidas_padre` FOREIGN KEY (`Ppa_Pad`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppa_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppa_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Catalogo jerarquico de partidas presupuestarias';

CREATE TABLE IF NOT EXISTS `pre_detalle` (
  `Pde_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK detalle mensual estandar',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Pde_Mes` TINYINT(4) NOT NULL COMMENT 'Mes 1..12',
  `Pde_Mon` DECIMAL(14,2) NOT NULL COMMENT 'Monto del mes',
  PRIMARY KEY (`Pde_Cod`),
  UNIQUE KEY `uq_pre_detalle` (`Ppe_Cod`,`Ppa_Cod`,`Pde_Mes`),
  KEY `fk_pre_detalle_partida` (`Ppa_Cod`),
  CONSTRAINT `fk_pre_detalle_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pre_detalle_presupuesto` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Presupuesto estandar mensual por partida y version';

CREATE TABLE IF NOT EXISTS `pre_reglas` (
  `Prg_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK regla legacy EXA',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Prg_TipDoc` VARCHAR(50) NOT NULL COMMENT 'Tipo documento origen',
  `Prg_Campo` VARCHAR(50) DEFAULT NULL COMMENT 'Campo condicional',
  `Prg_Valor` VARCHAR(255) DEFAULT NULL COMMENT 'Valor esperado',
  `Prg_Signo` CHAR(1) NOT NULL COMMENT 'Signo +|-',
  `Prg_CamMon` VARCHAR(50) NOT NULL COMMENT 'Campo monto del documento',
  `Prg_Pri` TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'Prioridad',
  `Prg_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `Prg_Des` VARCHAR(255) NOT NULL COMMENT 'Descripcion',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Prg_Fec` DATE NOT NULL COMMENT 'Fecha registro',
  PRIMARY KEY (`Prg_Cod`),
  KEY `fk_pre_reglas_partida` (`Ppa_Cod`),
  KEY `idx_prg_emp` (`Emp_Cod`),
  CONSTRAINT `fk_pre_reglas_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prg_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prg_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Reglas legacy referenciadas por pre_ejecucion';

CREATE TABLE IF NOT EXISTS `pre_ejecucion` (
  `Pej_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK ejecucion/comprometido',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Suc_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK sucursal.Suc_Cod',
  `Dep_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK departamen.Dep_Cod',
  `Pec_Cod` VARCHAR(50) DEFAULT NULL COMMENT 'Codigo proyecto (vista proy_id)',
  `Pej_Mes` TINYINT(4) NOT NULL COMMENT 'Mes 1..12',
  `Pej_Ani` YEAR(4) NOT NULL COMMENT 'Anio',
  `Pej_TipDoc` VARCHAR(50) NOT NULL COMMENT 'Tipo documento origen',
  `Pej_DocCod` VARCHAR(50) NOT NULL COMMENT 'Codigo documento origen',
  `Pej_Mon` DECIMAL(14,2) NOT NULL COMMENT 'Monto absoluto',
  `Pej_Sig` CHAR(1) NOT NULL COMMENT 'Signo +|-',
  `Pej_Fec` DATE NOT NULL COMMENT 'Fecha documento',
  `Pej_FecReg` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Prg_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_reglas.Prg_Cod',
  `Pej_Fase` CHAR(1) NOT NULL DEFAULT 'E' COMMENT 'C=Comprometido E=Ejecutado',
  `Pej_Rubro` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro analitico proyecto',
  PRIMARY KEY (`Pej_Cod`),
  KEY `idx_pej_emp_ani_mes` (`Emp_Cod`,`Pej_Ani`,`Pej_Mes`),
  KEY `idx_pej_doc` (`Pej_TipDoc`,`Pej_DocCod`),
  KEY `idx_pej_ppe` (`Ppe_Cod`),
  KEY `idx_pej_ppa` (`Ppa_Cod`),
  KEY `idx_pej_prg` (`Prg_Cod`),
  KEY `idx_pej_fase_rubro` (`Pej_Fase`,`Pej_Rubro`),
  KEY `idx_pej_proyecto` (`Pec_Cod`),
  KEY `idx_pej_suc` (`Suc_Cod`),
  KEY `idx_pej_dep` (`Dep_Cod`),
  KEY `idx_pej_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pej_ppa` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_ppe` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_prg` FOREIGN KEY (`Prg_Cod`) REFERENCES `pre_reglas` (`Prg_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_sucursal` FOREIGN KEY (`Suc_Cod`) REFERENCES `sucursal` (`Suc_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pej_departamento` FOREIGN KEY (`Dep_Cod`) REFERENCES `departamen` (`Dep_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Ledger de ejecucion/comprometido presupuestario';

-- =============================================================================
-- 2) VISTAS PUENTE
-- =============================================================================

CREATE OR REPLACE VIEW `exa_ppto_cabeceras` AS
SELECT
  `Ppe_Cod` AS `ppe_id`,
  `Emp_Cod` AS `Emp_Cod`,
  `Ppe_Ani` AS `ppe_anio`,
  `Ppe_Ver` AS `ppe_version`,
  `Ppe_Des` AS `ppe_descripcion`,
  `Ppe_Est` AS `ppe_estado`,
  `Ppe_Fec` AS `ppe_fecha_registro`,
  `Usu_Cod` AS `Usu_Cod`
FROM `pre_presupuesto`;

CREATE OR REPLACE VIEW `exa_ppto_partidas` AS
SELECT
  `Ppa_Cod` AS `ppa_id`,
  `Emp_Cod` AS `Emp_Cod`,
  `Ppa_Cla` AS `ppa_codigo_clasificacion`,
  `Ppa_Des` AS `ppa_descripcion`,
  `Ppa_Tip` AS `ppa_tipo`,
  `Ppa_Nat` AS `ppa_naturaleza`,
  `Ppa_Pad` AS `ppa_padre_id`,
  `Ppa_Niv` AS `ppa_nivel`,
  `Ppa_Clase` AS `ppa_clase`,
  `Ppa_Pct` AS `ppa_porcentaje_tope`,
  `Ppa_Meses` AS `ppa_meses_prorrateo`,
  `Ppa_Est` AS `ppa_estado`,
  `Ppa_Fec` AS `ppa_fecha_registro`,
  `Usu_Cod` AS `Usu_Cod`
FROM `pre_partidas`;

CREATE OR REPLACE VIEW `exa_ppto_detalles` AS
SELECT
  `Pde_Cod` AS `pde_id`,
  `Ppe_Cod` AS `ppe_id`,
  `Ppa_Cod` AS `ppa_id`,
  `Pde_Mes` AS `pde_mes`,
  `Pde_Mon` AS `pde_monto`
FROM `pre_detalle`;

CREATE OR REPLACE VIEW `exa_ppto_ejecuciones` AS
SELECT
  `Pej_Cod` AS `pej_id`,
  `Ppe_Cod` AS `ppe_id`,
  `Ppa_Cod` AS `ppa_id`,
  `Emp_Cod` AS `Emp_Cod`,
  `Suc_Cod` AS `Suc_Cod`,
  `Dep_Cod` AS `Dep_Cod`,
  `Pec_Cod` AS `proy_id`,
  `Pej_Mes` AS `pej_mes`,
  `Pej_Ani` AS `pej_anio`,
  `Pej_TipDoc` AS `pej_tipo_documento`,
  `Pej_DocCod` AS `pej_documento_codigo`,
  `Pej_Mon` AS `pej_monto`,
  `Pej_Sig` AS `pej_signo`,
  `Pej_Fec` AS `pej_fecha_documento`,
  `Pej_FecReg` AS `pej_fecha_registro`,
  `Usu_Cod` AS `Usu_Cod`,
  `Prg_Cod` AS `prg_id`,
  `Pej_Fase` AS `pej_fase`,
  `Pej_Rubro` AS `pej_rubro`
FROM `pre_ejecucion`;

-- =============================================================================
-- 3) TABLAS MODULO PRESUPUESTO (exa_ppto_*) + FK
-- =============================================================================

CREATE TABLE IF NOT EXISTS `exa_ppto_bases` (
  `bas_id` VARCHAR(50) NOT NULL COMMENT 'Identificador canonico (toneladas, horas_maquina, ...)',
  `bas_nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre legible',
  `bas_descripcion` VARCHAR(255) DEFAULT NULL COMMENT 'Detalle de alimentacion del valor',
  `bas_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  PRIMARY KEY (`bas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bases de calculo del motor';

CREATE TABLE IF NOT EXISTS `exa_ppto_formulas` (
  `frm_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK formula',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `frm_nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre de la formula',
  `frm_expresion` VARCHAR(255) NOT NULL COMMENT 'Expresion algebraica',
  `frm_variables` TEXT NOT NULL COMMENT 'JSON de variables del motor',
  `frm_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  PRIMARY KEY (`frm_id`),
  KEY `idx_frm_empresa` (`Emp_Cod`),
  KEY `idx_frm_estado` (`frm_estado`),
  CONSTRAINT `fk_frm_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Formulas presupuestarias parametrizables';

CREATE TABLE IF NOT EXISTS `exa_ppto_plantillas` (
  `plt_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK plantilla',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `plt_nombre` VARCHAR(150) NOT NULL COMMENT 'Nombre plantilla',
  `plt_descripcion` TEXT COMMENT 'Descripcion',
  `plt_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  `plt_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`plt_id`),
  KEY `idx_plt_empresa` (`Emp_Cod`),
  KEY `idx_plt_estado` (`plt_estado`),
  KEY `idx_plt_usu` (`Usu_Cod`),
  CONSTRAINT `fk_plt_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_plt_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Plantillas presupuestarias por empresa';

CREATE TABLE IF NOT EXISTS `exa_ppto_plantilla_partidas` (
  `plp_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK detalle plantilla-partida',
  `plt_id` INT(11) NOT NULL COMMENT 'FK exa_ppto_plantillas.plt_id',
  `ppa_id` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  PRIMARY KEY (`plp_id`),
  UNIQUE KEY `idx_plp_unico` (`plt_id`,`ppa_id`),
  KEY `ppa_id` (`ppa_id`),
  CONSTRAINT `fk_plp_plantilla` FOREIGN KEY (`plt_id`) REFERENCES `exa_ppto_plantillas` (`plt_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_plp_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Partidas de cada plantilla';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyectos` (
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'Codigo proyecto (ej RCET-01)',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod (PK compuesta)',
  `proy_nombre` VARCHAR(150) NOT NULL COMMENT 'Nombre del proyecto',
  `proy_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `proy_fecha_registro` DATE NOT NULL COMMENT 'Fecha ingreso',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `plt_id` INT(11) DEFAULT NULL COMMENT 'FK exa_ppto_plantillas.plt_id',
  PRIMARY KEY (`proy_id`,`Emp_Cod`),
  KEY `idx_proy_empresa` (`Emp_Cod`),
  KEY `idx_proy_estado` (`proy_estado`),
  KEY `idx_proy_plantilla` (`plt_id`),
  KEY `idx_proy_usu` (`Usu_Cod`),
  CONSTRAINT `fk_proy_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_proy_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proy_plantilla` FOREIGN KEY (`plt_id`) REFERENCES `exa_ppto_plantillas` (`plt_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maestro de proyectos presupuestarios';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyecto_detalles` (
  `pdp_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK rubro analitico',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `ppa_id` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas / parte PK proyecto',
  `pdp_rubro` VARCHAR(100) NOT NULL COMMENT 'Nombre del sub-rubro analitico',
  `pdp_toneladas_base` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Toneladas base',
  `pdp_factor_anual_tonelada` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Factor anual USD/Ton',
  `pdp_presupuesto_anual` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Presupuesto anual base (no se sobrescribe por ajuste fin.)',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `pdp_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro',
  PRIMARY KEY (`pdp_id`),
  UNIQUE KEY `idx_pdp_unico_rubro` (`ppe_id`,`ppa_id`,`proy_id`,`pdp_rubro`),
  KEY `ppa_id` (`ppa_id`),
  KEY `idx_pdp_proyecto` (`proy_id`,`Emp_Cod`),
  KEY `idx_pdp_empresa` (`Emp_Cod`),
  KEY `idx_pdp_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pdp_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Rubros anuales del proyecto';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyecto_detalles_mes` (
  `pdm_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK mes del rubro',
  `pdp_id` INT(11) NOT NULL COMMENT 'FK exa_ppto_proyecto_detalles.pdp_id',
  `pdm_mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `pdm_dias_laborables` INT(11) NOT NULL DEFAULT 0 COMMENT 'Dias operativos',
  `pdm_factor_mensual` DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Peso del mes',
  `pdm_presupuesto_mensual` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Cuota del mes',
  `pdm_ejecutado` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Ejecutado',
  `pdm_comprometido` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Comprometido',
  `pdm_disponible` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Disponible',
  PRIMARY KEY (`pdm_id`),
  UNIQUE KEY `idx_pdm_unico_mes` (`pdp_id`,`pdm_mes`),
  KEY `idx_pdm_mes` (`pdm_mes`),
  CONSTRAINT `fk_pdm_detalle` FOREIGN KEY (`pdp_id`) REFERENCES `exa_ppto_proyecto_detalles` (`pdp_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Distribucion mensual del rubro';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyecto_version` (
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas / parte PK proyecto',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `pv_toneladas_base_mes` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Ton/mes base PDF (ingresos)',
  `pv_toneladas_costo_mes` DECIMAL(12,4) NOT NULL DEFAULT 77000.0000 COMMENT 'Ton/mes driver egresos',
  `pv_tarifa_ton_iva` DECIMAL(10,4) NOT NULL DEFAULT 3.0000 COMMENT 'Tarifa USD/Ton con IVA',
  `pv_iva_divisor` DECIMAL(6,4) NOT NULL DEFAULT 1.1500 COMMENT 'Divisor IVA',
  `pv_costo_capital_pct` DECIMAL(8,4) NOT NULL DEFAULT 11.0000 COMMENT '% costo capital sobre neto',
  `pv_gad_monto_objetivo` DECIMAL(14,2) NOT NULL DEFAULT 2000000.00 COMMENT 'Objetivo recuperacion GAD',
  `pv_gad_factor_ton` DECIMAL(12,6) NOT NULL DEFAULT 0.198400 COMMENT 'USD GAD por tonelada',
  `pv_gad_recuperado_acum` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD acumulado aplicado',
  `pv_ajuste_activo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=usar partida final en cuadro',
  `pv_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha configuracion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`proy_id`,`Emp_Cod`,`ppe_id`),
  KEY `idx_ppv_emp` (`Emp_Cod`),
  KEY `idx_ppv_ppe` (`ppe_id`),
  KEY `idx_ppv_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ppv_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Config ton/tarifa/ajuste por proyecto-version';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyecto_precio_anio` (
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas / parte PK proyecto',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `ppa_anio` INT(11) NOT NULL COMMENT 'Anio calendario de la tarifa',
  `ppa_tarifa_ton_iva` DECIMAL(12,4) NOT NULL DEFAULT 3.0000 COMMENT 'Tarifa USD/Ton c/IVA',
  `ppa_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`proy_id`,`Emp_Cod`,`ppe_id`,`ppa_anio`),
  KEY `idx_pppa_emp` (`Emp_Cod`),
  KEY `idx_pppa_ppe` (`ppe_id`),
  KEY `idx_pppa_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pppa_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pppa_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pppa_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Precio $/Ton con IVA por anio';

CREATE TABLE IF NOT EXISTS `exa_ppto_proyecto_publicacion` (
  `pub_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK auditoria publicacion',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `pub_anio` INT(11) NOT NULL COMMENT 'Anio publicado',
  `pub_mes` TINYINT(4) DEFAULT NULL COMMENT 'Mes publicado',
  `pub_total_anterior` DECIMAL(16,2) NOT NULL DEFAULT 0.00 COMMENT 'Total anterior',
  `pub_total_nuevo` DECIMAL(16,2) NOT NULL DEFAULT 0.00 COMMENT 'Total nuevo',
  `pub_rubros_driver` INT(11) NOT NULL DEFAULT 0 COMMENT 'Rubros con driver ton',
  `pub_rubros_fijo` INT(11) NOT NULL DEFAULT 0 COMMENT 'Rubros fijos',
  `pub_modo` VARCHAR(32) NOT NULL DEFAULT 'proyectada' COMMENT 'Modo publicacion',
  `pub_notas` VARCHAR(500) DEFAULT NULL COMMENT 'Observaciones',
  `pub_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha publicacion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`pub_id`),
  KEY `idx_pub_proy` (`proy_id`,`Emp_Cod`,`ppe_id`),
  KEY `idx_pub_fecha` (`pub_fecha_registro`),
  KEY `idx_pub_ppe` (`ppe_id`),
  KEY `idx_pub_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pub_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pub_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pub_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Auditoria de publicacion de presupuesto';

CREATE TABLE IF NOT EXISTS `exa_ppto_ajuste_fin_cab` (
  `ajc_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK cabecera ajuste financiero',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `ajc_anio` INT(11) NOT NULL COMMENT 'Anio del precio',
  `ajc_vista` VARCHAR(20) NOT NULL DEFAULT 'anual' COMMENT 'anual|acumulado|mes',
  `ajc_mes` INT(11) NOT NULL DEFAULT 0 COMMENT 'Mes si aplica',
  `ajc_escenario` VARCHAR(20) NOT NULL DEFAULT 'esperada' COMMENT 'esperada|proyectada|real',
  `ajc_estado` VARCHAR(20) NOT NULL DEFAULT 'aplicado' COMMENT 'simulado|aplicado|anulado',
  `ajc_precio_iva` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Tarifa c/IVA',
  `ajc_iva_divisor` DECIMAL(8,4) NOT NULL DEFAULT 1.1500 COMMENT 'Divisor IVA',
  `ajc_precio_neto` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Precio neto',
  `ajc_capital_pct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT '% capital',
  `ajc_capital_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Capital USD/Ton',
  `ajc_capital_total` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Capital total periodo',
  `ajc_gad_factor_ton` DECIMAL(12,6) NOT NULL DEFAULT 0.000000 COMMENT 'Factor GAD USD/Ton',
  `ajc_gad_toneladas` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Toneladas escenario',
  `ajc_gad_calculado` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD bruto',
  `ajc_gad_aplicado` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD aplicado',
  `ajc_gad_acum_antes` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Acum antes',
  `ajc_gad_acum_despues` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Acum despues',
  `ajc_gad_saldo_despues` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo despues',
  `ajc_gad_objetivo` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Objetivo GAD',
  `ajc_gasto_base` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Gasto base',
  `ajc_gasto_final` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Gasto final',
  `ajc_ingreso` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Ingreso periodo',
  `ajc_utilidad_base` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Utilidad referencia',
  `ajc_observacion` VARCHAR(255) DEFAULT NULL COMMENT 'Observacion',
  `ajc_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha evento',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`ajc_id`),
  KEY `idx_ajc_proy` (`proy_id`,`Emp_Cod`,`ppe_id`),
  KEY `idx_ajc_estado` (`ajc_estado`),
  KEY `idx_ajc_ppe` (`ppe_id`),
  KEY `idx_ajc_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ajc_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ajc_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ajc_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial cabecera ajuste capital+GAD';

CREATE TABLE IF NOT EXISTS `exa_ppto_ajuste_fin_det` (
  `ajd_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK detalle por grupo',
  `ajc_id` INT(11) NOT NULL COMMENT 'FK exa_ppto_ajuste_fin_cab.ajc_id',
  `grupo_cod` VARCHAR(20) NOT NULL COMMENT 'Codigo grupo (ej 14)',
  `grupo_nombre` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nombre grupo',
  `ajd_partida_base` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Partida base',
  `ajd_participacion_pct` DECIMAL(10,6) NOT NULL DEFAULT 0.000000 COMMENT '% participacion',
  `ajd_base_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Base USD/Ton',
  `ajd_capital_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Capital USD/Ton',
  `ajd_gad_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'GAD USD/Ton',
  `ajd_ajuste_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Ajuste USD/Ton',
  `ajd_final_por_ton` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Final USD/Ton',
  `ajd_capital_monto` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Capital monto',
  `ajd_gad_monto` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD monto',
  `ajd_partida_final` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Partida final',
  PRIMARY KEY (`ajd_id`),
  KEY `idx_ajd_cab` (`ajc_id`),
  CONSTRAINT `fk_ajd_cabecera` FOREIGN KEY (`ajc_id`) REFERENCES `exa_ppto_ajuste_fin_cab` (`ajc_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial detalle ajuste por grupo';

CREATE TABLE IF NOT EXISTS `exa_ppto_prod_config` (
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas / parte PK proyecto',
  `pco_origen` VARCHAR(50) NOT NULL COMMENT 'Origen produccion',
  `pco_campo` VARCHAR(100) NOT NULL COMMENT 'Campo numerico origen',
  `pco_frecuencia` VARCHAR(20) NOT NULL DEFAULT 'mensual' COMMENT 'diaria|semanal|mensual',
  `pco_metodo_forecast` ENUM('promedio_historico','produccion_proyectada','manual') NOT NULL DEFAULT 'produccion_proyectada' COMMENT 'Metodo proyeccion',
  `pco_periodo_inicio` DATE DEFAULT NULL COMMENT 'Inicio validez',
  `pco_periodo_fin` DATE DEFAULT NULL COMMENT 'Fin validez',
  `pco_extra_config` TEXT COMMENT 'JSON parametros extra',
  `pco_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`proy_id`,`Emp_Cod`),
  KEY `idx_pco_origen` (`pco_origen`),
  KEY `idx_pco_emp_origen` (`Emp_Cod`,`pco_origen`),
  KEY `idx_pco_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pco_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pco_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Config origen de produccion fisica';

CREATE TABLE IF NOT EXISTS `exa_ppto_prod_periodos` (
  `prd_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK medicion',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas',
  `prd_anio` INT(11) NOT NULL COMMENT 'Anio fiscal',
  `prd_mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `prd_esperada` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion esperada',
  `prd_real` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion real',
  `prd_proyectada` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion proyectada',
  `prd_estado` ENUM('sin_dato','en_curso','cerrado') NOT NULL DEFAULT 'sin_dato' COMMENT 'Estado periodo',
  `prd_fecha_cierre` DATETIME DEFAULT NULL COMMENT 'Fecha cierre',
  `prd_unidad` VARCHAR(20) NOT NULL DEFAULT 'Ton' COMMENT 'Unidad',
  `prd_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`prd_id`),
  UNIQUE KEY `idx_prd_unico_periodo` (`proy_id`,`Emp_Cod`,`prd_anio`,`prd_mes`),
  KEY `idx_prd_periodo` (`prd_anio`,`prd_mes`),
  KEY `idx_prd_empresa` (`Emp_Cod`),
  KEY `idx_prd_usu` (`Usu_Cod`),
  CONSTRAINT `fk_prd_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prd_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Produccion esperada/real/proyectada por mes';

CREATE TABLE IF NOT EXISTS `exa_ppto_prod_variaciones` (
  `var_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK variacion',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas',
  `var_anio` INT(11) NOT NULL COMMENT 'Anio',
  `var_mes` INT(11) NOT NULL COMMENT 'Mes',
  `var_absoluta` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real - Esperada',
  `var_porcentual` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Variacion %',
  `var_fecha_calculo` DATETIME NOT NULL COMMENT 'Fecha calculo',
  PRIMARY KEY (`var_id`),
  UNIQUE KEY `idx_var_unico_periodo` (`proy_id`,`Emp_Cod`,`var_anio`,`var_mes`),
  KEY `idx_var_periodo` (`var_anio`,`var_mes`),
  CONSTRAINT `fk_var_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Variaciones produccion real vs esperada';

CREATE TABLE IF NOT EXISTS `exa_ppto_prod_evento_log` (
  `pel_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK evento',
  `proy_id` VARCHAR(50) NOT NULL COMMENT 'FK proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas',
  `pel_anio` INT(11) NOT NULL COMMENT 'Anio',
  `pel_mes` INT(11) NOT NULL COMMENT 'Mes',
  `pel_tipo` ENUM('reapertura','correccion_sync_cerrado') NOT NULL COMMENT 'Tipo evento',
  `pel_origen` ENUM('manual','sync') NOT NULL COMMENT 'Origen evento',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `pel_fecha` DATETIME NOT NULL COMMENT 'Fecha evento',
  `pel_real_antes` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real antes',
  `pel_real_despues` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real despues',
  `pel_pf_antes` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'PF antes',
  `pel_pf_despues` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'PF despues',
  `pel_motivo` VARCHAR(255) DEFAULT NULL COMMENT 'Motivo',
  `pel_motor_version` VARCHAR(10) NOT NULL DEFAULT 'v1_legacy' COMMENT 'Version motor',
  PRIMARY KEY (`pel_id`),
  KEY `idx_pel_proy` (`Emp_Cod`,`proy_id`,`pel_anio`,`pel_mes`),
  KEY `idx_pel_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pel_proyecto` FOREIGN KEY (`proy_id`,`Emp_Cod`) REFERENCES `exa_ppto_proyectos` (`proy_id`,`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pel_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora eventos de produccion';

CREATE TABLE IF NOT EXISTS `exa_ppto_reglas` (
  `prg_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK regla modulo',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `ppa_id` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `prg_tipo_documento` VARCHAR(50) NOT NULL COMMENT 'Tipo documento origen',
  `prg_campo_evaluacion` VARCHAR(100) DEFAULT NULL COMMENT 'Campo condicional',
  `prg_valor_esperado` VARCHAR(100) DEFAULT NULL COMMENT 'Valor esperado',
  `prg_signo` CHAR(1) NOT NULL DEFAULT '+' COMMENT 'Signo +|-',
  `prg_campo_monto` VARCHAR(100) NOT NULL COMMENT 'Campo monto origen',
  `prg_prioridad` INT(11) NOT NULL DEFAULT 1 COMMENT 'Prioridad evaluacion',
  `prg_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `prg_descripcion` VARCHAR(255) DEFAULT NULL COMMENT 'Descripcion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `prg_fecha_registro` DATE DEFAULT NULL COMMENT 'Fecha registro',
  PRIMARY KEY (`prg_id`),
  KEY `idx_regla_emp_tipo` (`Emp_Cod`,`prg_tipo_documento`,`prg_prioridad`),
  KEY `idx_regla_ppa` (`ppa_id`),
  KEY `idx_regla_usu` (`Usu_Cod`),
  CONSTRAINT `fk_regla_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_regla_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_regla_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Reglas de imputacion automatica del modulo';

CREATE TABLE IF NOT EXISTS `exa_ppto_reajustes` (
  `rea_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK reajuste',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `rea_tipo` VARCHAR(20) NOT NULL COMMENT 'transferencia|incremento|reduccion',
  `ppa_id_origen` INT(11) DEFAULT NULL COMMENT 'FK partida origen',
  `proy_id_origen` VARCHAR(50) DEFAULT NULL COMMENT 'Proyecto origen (logico)',
  `pdp_rubro_origen` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro origen',
  `ppa_id_destino` INT(11) DEFAULT NULL COMMENT 'FK partida destino',
  `proy_id_destino` VARCHAR(50) DEFAULT NULL COMMENT 'Proyecto destino (logico)',
  `pdp_rubro_destino` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro destino',
  `rea_mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `rea_monto` DECIMAL(14,2) NOT NULL COMMENT 'Monto absoluto',
  `rea_justificacion` TEXT NOT NULL COMMENT 'Justificacion',
  `rea_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha movimiento',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`rea_id`),
  KEY `ppe_id` (`ppe_id`),
  KEY `idx_rea_origen` (`ppa_id_origen`,`proy_id_origen`),
  KEY `idx_rea_destino` (`ppa_id_destino`,`proy_id_destino`),
  KEY `idx_rea_empresa` (`Emp_Cod`),
  KEY `idx_rea_tipo` (`rea_tipo`),
  KEY `idx_rea_periodo` (`rea_mes`),
  KEY `idx_rea_usu` (`Usu_Cod`),
  CONSTRAINT `fk_rea_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_partida_origen` FOREIGN KEY (`ppa_id_origen`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_partida_destino` FOREIGN KEY (`ppa_id_destino`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora de reajustes presupuestarios';

CREATE TABLE IF NOT EXISTS `exa_ppto_movimientos` (
  `mov_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK movimiento externo',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `ppe_id` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `proy_id` VARCHAR(50) DEFAULT NULL COMMENT 'Codigo proyecto (logico)',
  `ppa_id` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `pdp_rubro` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro analitico',
  `mov_doc_id` VARCHAR(50) NOT NULL COMMENT 'ID documento origen',
  `mov_tipo_doc` VARCHAR(50) NOT NULL COMMENT 'Tipo documento',
  `mov_modulo` VARCHAR(50) NOT NULL COMMENT 'Modulo origen',
  `mov_tipo_mov` VARCHAR(20) NOT NULL COMMENT 'comprometido|ejecutado|reverso',
  `mov_monto` DECIMAL(14,2) NOT NULL COMMENT 'Monto absoluto',
  `mov_signo` CHAR(1) NOT NULL DEFAULT '+' COMMENT 'Signo +|-',
  `mov_mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `mov_anio` INT(11) NOT NULL COMMENT 'Anio fiscal',
  `mov_fecha_documento` DATE NOT NULL COMMENT 'Fecha documento',
  `mov_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha registro PPTO',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`mov_id`),
  KEY `ppe_id` (`ppe_id`),
  KEY `idx_mov_empresa_proyecto` (`Emp_Cod`,`proy_id`),
  KEY `idx_mov_partida_rubro` (`ppa_id`,`pdp_rubro`),
  KEY `idx_mov_documento` (`mov_tipo_doc`,`mov_doc_id`),
  KEY `idx_mov_modulo_tipo` (`mov_modulo`,`mov_tipo_mov`),
  KEY `idx_mov_periodo` (`mov_anio`,`mov_mes`),
  KEY `idx_mov_usu` (`Usu_Cod`),
  CONSTRAINT `fk_mov_version` FOREIGN KEY (`ppe_id`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora afectaciones desde modulos EXA';

CREATE TABLE IF NOT EXISTS `exa_ppto_partida_cuenta` (
  `ppc_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK mapeo partida-cuenta',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pla_Cod` BIGINT(20) NOT NULL COMMENT 'FK plan_cuenta.Pla_Cod',
  `ppa_id` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Pld_Cod` BIGINT(20) NOT NULL COMMENT 'FK det_plan.Pld_Cod',
  `ppc_estado` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `ppc_fecha_registro` DATETIME NOT NULL COMMENT 'Fecha asignacion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`ppc_id`),
  UNIQUE KEY `uk_ppc_emp_pla_pld` (`Emp_Cod`,`Pla_Cod`,`Pld_Cod`),
  KEY `idx_ppc_emp_ppa` (`Emp_Cod`,`ppa_id`),
  KEY `idx_ppc_pla` (`Pla_Cod`),
  KEY `idx_ppc_pld` (`Pld_Cod`),
  KEY `idx_ppc_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ppc_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_plan` FOREIGN KEY (`Pla_Cod`) REFERENCES `plan_cuenta` (`Pla_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_cuenta` FOREIGN KEY (`Pld_Cod`) REFERENCES `det_plan` (`Pld_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Puente partida presupuestaria <-> cuenta contable';

CREATE TABLE IF NOT EXISTS `exa_ppto_umbral_pf` (
  `ubp_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK umbral PF vs VA',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `ppa_id` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod (NULL=global)',
  `ubp_umbral_pct` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Umbral % alerta',
  `ubp_fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`ubp_id`),
  UNIQUE KEY `uk_ubp_emp_ppa` (`Emp_Cod`,`ppa_id`),
  KEY `idx_ubp_emp` (`Emp_Cod`),
  KEY `idx_ubp_ppa` (`ppa_id`),
  KEY `idx_ubp_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ubp_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ubp_partida` FOREIGN KEY (`ppa_id`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ubp_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Umbrales alerta PF vs VA (D8)';

-- =============================================================================
-- 4) VISTA RESUMEN
-- =============================================================================

DROP VIEW IF EXISTS `exa_ppto_resumen`;
CREATE VIEW `exa_ppto_resumen` AS
SELECT
  c.`Emp_Cod` AS `Emp_Cod`,
  d.`ppe_id` AS `ppe_id`,
  d.`ppa_id` AS `ppa_id`,
  CAST(NULL AS CHAR(50)) AS `proy_id`,
  CAST(NULL AS CHAR(100)) AS `pdp_rubro`,
  d.`pde_mes` AS `mes`,
  d.`pde_monto` AS `inicial`,
  CAST(0.00 AS DECIMAL(14,2)) AS `reajustes`,
  d.`pde_monto` AS `vigente`,
  CAST(0.00 AS DECIMAL(14,2)) AS `comprometido`,
  CAST(0.00 AS DECIMAL(14,2)) AS `ejecutado`,
  d.`pde_monto` AS `disponible`
FROM `exa_ppto_detalles` d
INNER JOIN `exa_ppto_cabeceras` c ON d.`ppe_id` = c.`ppe_id`
UNION ALL
SELECT
  pd.`Emp_Cod`, pd.`ppe_id`, pd.`ppa_id`, pd.`proy_id`, pd.`pdp_rubro`,
  pdm.`pdm_mes`, pdm.`pdm_presupuesto_mensual`, CAST(0.00 AS DECIMAL(14,2)),
  pdm.`pdm_presupuesto_mensual`,
  IFNULL(pdm.`pdm_comprometido`, 0.00),
  IFNULL(pdm.`pdm_ejecutado`, 0.00),
  IFNULL(pdm.`pdm_disponible`, pdm.`pdm_presupuesto_mensual`)
FROM `exa_ppto_proyecto_detalles` pd
INNER JOIN `exa_ppto_proyecto_detalles_mes` pdm ON pd.`pdp_id` = pdm.`pdp_id`
UNION ALL
SELECT
  r.`Emp_Cod`, r.`ppe_id`, r.`ppa_id_destino`, r.`proy_id_destino`, r.`pdp_rubro_destino`,
  r.`rea_mes`, 0.00, r.`rea_monto`, r.`rea_monto`, 0.00, 0.00, r.`rea_monto`
FROM `exa_ppto_reajustes` r WHERE r.`rea_tipo` IN ('incremento','transferencia')
UNION ALL
SELECT
  r.`Emp_Cod`, r.`ppe_id`, r.`ppa_id_origen`, r.`proy_id_origen`, r.`pdp_rubro_origen`,
  r.`rea_mes`, 0.00, -(r.`rea_monto`), -(r.`rea_monto`), 0.00, 0.00, -(r.`rea_monto`)
FROM `exa_ppto_reajustes` r WHERE r.`rea_tipo` = 'transferencia'
UNION ALL
SELECT
  r.`Emp_Cod`, r.`ppe_id`, r.`ppa_id_destino`, r.`proy_id_destino`, r.`pdp_rubro_destino`,
  r.`rea_mes`, 0.00, -(r.`rea_monto`), -(r.`rea_monto`), 0.00, 0.00, -(r.`rea_monto`)
FROM `exa_ppto_reajustes` r WHERE r.`rea_tipo` = 'reduccion'
UNION ALL
SELECT
  pe.`Emp_Cod`, pe.`ppe_id`, pe.`ppa_id`, pe.`proy_id`, pe.`pej_rubro`, pe.`pej_mes`,
  0.00, 0.00, 0.00,
  (CASE WHEN pe.`pej_fase`='C' THEN (CASE WHEN pe.`pej_signo`='+' THEN pe.`pej_monto` ELSE -(pe.`pej_monto`) END) ELSE 0.00 END),
  (CASE WHEN pe.`pej_fase`='E' THEN (CASE WHEN pe.`pej_signo`='+' THEN pe.`pej_monto` ELSE -(pe.`pej_monto`) END) ELSE 0.00 END),
  (-1*(CASE WHEN pe.`pej_fase` IN ('C','E') THEN (CASE WHEN pe.`pej_signo`='+' THEN pe.`pej_monto` ELSE -(pe.`pej_monto`) END) ELSE 0.00 END))
FROM `exa_ppto_ejecuciones` pe;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- RESUMEN DE FK PRINCIPALES
-- empresas      <- Emp_Cod (casi todas las tablas)
-- usuarios      <- Usu_Cod
-- sucursal      <- Suc_Cod (pre_presupuesto, pre_ejecucion)
-- departamen    <- Dep_Cod (pre_presupuesto, pre_ejecucion)
-- plan_cuenta   <- Pla_Cod (exa_ppto_partida_cuenta)
-- det_plan      <- Pld_Cod (exa_ppto_partida_cuenta)
-- pre_presupuesto / pre_partidas <- ppe_id / ppa_id
-- exa_ppto_proyectos <- (proy_id, Emp_Cod) en version/precio/prod/ajuste/publicacion
-- =============================================================================
