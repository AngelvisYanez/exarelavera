-- =============================================================================
-- EXA ERP PRESUPUESTO (pre_*) - ESQUEMA COMPLETO REFACTORIZADO (27 TABLAS)
-- Nomenclatura EXA: Prefijo unico pre_ + Llaves Primarias simples _Cod + Nombres Unicos
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
-- NOTA TIPOS Y CONVENCION EXA:
--   - Todas las 27 tablas usan prefijo 'pre_'.
--   - Cada tabla tiene una UNICA llave primaria entera autoincremental terminada en '_Cod'.
--   - Ningun campo que no sea Foreign Key repite su nombre en otra tabla.
--   - Las Foreign Keys compartidas (Emp_Cod, Usu_Cod, Suc_Cod, Dep_Cod, Pla_Cod, Pld_Cod,
--     Ppe_Cod, Ppa_Cod, Pro_Cod, Plt_Cod, Pdp_Cod, Ajc_Cod, Prg_Cod) mantienen
--     exactamente el mismo nombre, tipo y comentario.
-- =============================================================================

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. pre_presupuesto (Cabeceras y versiones de presupuesto)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_presupuesto` (
  `Ppe_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK version de presupuesto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Suc_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK sucursal.Suc_Cod',
  `Dep_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK departamen.Dep_Cod',
  `Ppe_Ani` YEAR(4) NOT NULL COMMENT 'Anio fiscal de la version',
  `Ppe_Ver` TINYINT(4) NOT NULL COMMENT 'Numero de version en el anio',
  `Ppe_Des` VARCHAR(255) NOT NULL COMMENT 'Descripcion de la version',
  `Ppe_Est` CHAR(1) NOT NULL DEFAULT 'B' COMMENT 'Estado: B=Borrador A=Activo C=Cerrado',
  `Ppe_FecReg` DATE NOT NULL COMMENT 'Fecha de registro',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Cabeceras y versiones de presupuesto por empresa y anio';

-- -----------------------------------------------------------------------------
-- 2. pre_partidas (Catalogo jerarquico de partidas presupuestarias)
-- -----------------------------------------------------------------------------
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
  `Ppa_FecReg` DATE NOT NULL COMMENT 'Fecha de registro',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ppa_Cod`),
  KEY `fk_pre_partidas_padre` (`Ppa_Pad`),
  KEY `idx_ppa_emp` (`Emp_Cod`),
  KEY `idx_ppa_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pre_partidas_padre` FOREIGN KEY (`Ppa_Pad`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppa_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppa_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Catalogo jerarquico de partidas presupuestarias';

-- -----------------------------------------------------------------------------
-- 3. pre_detalle (Presupuesto mensual estandar por version/partida)
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 4. pre_reglas (Reglas de imputacion automatica de documentos)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_reglas` (
  `Prg_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK regla de imputacion',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Prg_TipDoc` VARCHAR(50) NOT NULL COMMENT 'Tipo documento origen',
  `Prg_Campo` VARCHAR(50) DEFAULT NULL COMMENT 'Campo condicional',
  `Prg_Valor` VARCHAR(255) DEFAULT NULL COMMENT 'Valor esperado',
  `Prg_Signo` CHAR(1) NOT NULL DEFAULT '+' COMMENT 'Signo +|-',
  `Prg_CamMon` VARCHAR(50) NOT NULL COMMENT 'Campo monto del documento',
  `Prg_Pri` TINYINT(4) NOT NULL DEFAULT 1 COMMENT 'Prioridad',
  `Prg_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `Prg_Des` VARCHAR(255) DEFAULT NULL COMMENT 'Descripcion de la regla',
  `Usu_Cod` BIGINT(20) NOT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Prg_FecReg` DATE NOT NULL COMMENT 'Fecha de registro',
  PRIMARY KEY (`Prg_Cod`),
  KEY `fk_pre_reglas_partida` (`Ppa_Cod`),
  KEY `idx_prg_emp` (`Emp_Cod`),
  KEY `idx_prg_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pre_reglas_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prg_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prg_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Reglas de imputacion automatica de documentos ERP';

-- -----------------------------------------------------------------------------
-- 5. pre_ejecucion (Ledger de ejecucion y comprometido presupuestario)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_ejecucion` (
  `Pej_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK ejecucion/comprometido',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Suc_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK sucursal.Suc_Cod',
  `Dep_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK departamen.Dep_Cod',
  `Pro_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
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
  KEY `idx_pej_proyecto` (`Pro_Cod`),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Ledger de ejecucion y comprometido presupuestario';

-- -----------------------------------------------------------------------------
-- 6. pre_proyectos (Maestro de proyectos presupuestarios)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyectos` (
  `Pro_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK proyecto presupuestario',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pro_Ide` VARCHAR(50) NOT NULL COMMENT 'Codigo o identificador funcional (ej RCET-01)',
  `Pro_Nom` VARCHAR(150) NOT NULL COMMENT 'Nombre del proyecto',
  `Pro_Des` VARCHAR(255) DEFAULT NULL COMMENT 'Descripcion funcional',
  `Pro_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `Pro_FecReg` DATE NOT NULL COMMENT 'Fecha de ingreso',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Plt_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_plantillas.Plt_Cod',
  PRIMARY KEY (`Pro_Cod`),
  UNIQUE KEY `uq_pro_emp_ide` (`Emp_Cod`,`Pro_Ide`),
  KEY `idx_pro_empresa` (`Emp_Cod`),
  KEY `idx_pro_estado` (`Pro_Est`),
  KEY `idx_pro_plantilla` (`Plt_Cod`),
  KEY `idx_pro_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pro_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pro_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Maestro de proyectos presupuestarios';

-- -----------------------------------------------------------------------------
-- 7. pre_proyecto_detalles (Rubros analiticos del proyecto)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyecto_detalles` (
  `Pdp_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK rubro analitico proyecto',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pdp_Rubro` VARCHAR(100) NOT NULL COMMENT 'Nombre del sub-rubro analitico',
  `Pdp_TonBase` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Toneladas base',
  `Pdp_FacAnualTon` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Factor anual USD/Ton',
  `Pdp_PreAnual` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Presupuesto anual base',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Pdp_FecReg` DATETIME NOT NULL COMMENT 'Fecha de registro',
  PRIMARY KEY (`Pdp_Cod`),
  UNIQUE KEY `idx_pdp_unico_rubro` (`Ppe_Cod`,`Ppa_Cod`,`Pro_Cod`,`Pdp_Rubro`),
  KEY `idx_pdp_partida` (`Ppa_Cod`),
  KEY `idx_pdp_proyecto` (`Pro_Cod`),
  KEY `idx_pdp_empresa` (`Emp_Cod`),
  KEY `idx_pdp_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pdp_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pdp_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Rubros analiticos anuales del proyecto';

-- -----------------------------------------------------------------------------
-- 8. pre_proyecto_detalles_mes (Distribucion mensual del rubro analitico)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyecto_detalles_mes` (
  `Pdm_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK mes del rubro analitico',
  `Pdp_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyecto_detalles.Pdp_Cod',
  `Pdm_Mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `Pdm_DiasLab` INT(11) NOT NULL DEFAULT 0 COMMENT 'Dias operativos laborables',
  `Pdm_FacMensual` DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Peso mensual',
  `Pdm_PreMensual` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Cuota mensual',
  `Pdm_Ejecutado` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Ejecutado',
  `Pdm_Comprometido` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Comprometido',
  `Pdm_Disponible` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Disponible',
  PRIMARY KEY (`Pdm_Cod`),
  UNIQUE KEY `idx_pdm_unico_mes` (`Pdp_Cod`,`Pdm_Mes`),
  KEY `idx_pdm_mes` (`Pdm_Mes`),
  CONSTRAINT `fk_pdm_detalle` FOREIGN KEY (`Pdp_Cod`) REFERENCES `pre_proyecto_detalles` (`Pdp_Cod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Distribucion mensual del rubro analitico';

-- -----------------------------------------------------------------------------
-- 9. pre_proyecto_version (Configuracion ton/tarifa/ajuste por proyecto-version)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyecto_version` (
  `Ppv_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK version proyecto',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppv_TonBaseMes` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Ton/mes base PDF (ingresos)',
  `Ppv_TonCostoMes` DECIMAL(12,4) NOT NULL DEFAULT 77000.0000 COMMENT 'Ton/mes driver egresos',
  `Ppv_TarifaTonIva` DECIMAL(10,4) NOT NULL DEFAULT 3.0000 COMMENT 'Tarifa USD/Ton con IVA',
  `Ppv_IvaDivisor` DECIMAL(6,4) NOT NULL DEFAULT 1.1500 COMMENT 'Divisor IVA',
  `Ppv_CostCapPct` DECIMAL(8,4) NOT NULL DEFAULT 11.0000 COMMENT '% costo capital sobre neto',
  `Ppv_GadObjetivo` DECIMAL(14,2) NOT NULL DEFAULT 2000000.00 COMMENT 'Objetivo recuperacion GAD',
  `Ppv_GadFacTon` DECIMAL(12,6) NOT NULL DEFAULT 0.198400 COMMENT 'USD GAD por tonelada',
  `Ppv_GadRecAcum` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD acumulado aplicado',
  `Ppv_AjuActivo` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=usar partida final en cuadro',
  `Ppv_FecReg` DATETIME NOT NULL COMMENT 'Fecha configuracion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ppv_Cod`),
  UNIQUE KEY `uq_ppv_pro_emp_ppe` (`Pro_Cod`,`Emp_Cod`,`Ppe_Cod`),
  KEY `idx_ppv_emp` (`Emp_Cod`),
  KEY `idx_ppv_ppe` (`Ppe_Cod`),
  KEY `idx_ppv_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ppv_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppv_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Configuracion de toneladas, tarifas y ajuste por proyecto-version';

-- -----------------------------------------------------------------------------
-- 10. pre_proyecto_precio_anio (Proyeccion de precios $/Ton c/IVA por anio)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyecto_precio_anio` (
  `Ppr_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK precio anio proyecto',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppr_Anio` INT(11) NOT NULL COMMENT 'Anio calendario de la tarifa',
  `Ppr_TarifaTonIva` DECIMAL(12,4) NOT NULL DEFAULT 3.0000 COMMENT 'Tarifa USD/Ton c/IVA',
  `Ppr_FecReg` DATETIME NOT NULL COMMENT 'Fecha de registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ppr_Cod`),
  UNIQUE KEY `uq_ppr_pro_emp_ppe_anio` (`Pro_Cod`,`Emp_Cod`,`Ppe_Cod`,`Ppr_Anio`),
  KEY `idx_ppr_emp` (`Emp_Cod`),
  KEY `idx_ppr_ppe` (`Ppe_Cod`),
  KEY `idx_ppr_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ppr_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppr_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppr_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppr_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Precio USD/Ton con IVA por anio de proyecto';

-- -----------------------------------------------------------------------------
-- 11. pre_proyecto_publicacion (Auditoria de publicacion y aprobacion de proyectos)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_proyecto_publicacion` (
  `Pub_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK auditoria publicacion',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Pub_Anio` INT(11) NOT NULL COMMENT 'Anio publicado',
  `Pub_Mes` TINYINT(4) DEFAULT NULL COMMENT 'Mes publicado',
  `Pub_TotAnterior` DECIMAL(16,2) NOT NULL DEFAULT 0.00 COMMENT 'Total anterior',
  `Pub_TotNuevo` DECIMAL(16,2) NOT NULL DEFAULT 0.00 COMMENT 'Total nuevo',
  `Pub_RubDriver` INT(11) NOT NULL DEFAULT 0 COMMENT 'Rubros con driver ton',
  `Pub_RubFijo` INT(11) NOT NULL DEFAULT 0 COMMENT 'Rubros fijos',
  `Pub_Modo` VARCHAR(32) NOT NULL DEFAULT 'proyectada' COMMENT 'Modo publicacion',
  `Pub_Obs` VARCHAR(500) DEFAULT NULL COMMENT 'Observaciones',
  `Pub_FecReg` DATETIME NOT NULL COMMENT 'Fecha publicacion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Pub_Cod`),
  KEY `idx_pub_proy` (`Pro_Cod`,`Emp_Cod`,`Ppe_Cod`),
  KEY `idx_pub_fecha` (`Pub_FecReg`),
  KEY `idx_pub_ppe` (`Ppe_Cod`),
  KEY `idx_pub_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pub_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pub_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pub_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pub_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Auditoria de publicacion de presupuesto de proyectos';

-- -----------------------------------------------------------------------------
-- 12. pre_ajuste_fin_cab (Historial cabecera de ajuste financiero capital+GAD)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_ajuste_fin_cab` (
  `Ajc_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK cabecera ajuste financiero',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ajc_Anio` INT(11) NOT NULL COMMENT 'Anio del precio',
  `Ajc_Vista` VARCHAR(20) NOT NULL DEFAULT 'anual' COMMENT 'anual|acumulado|mes',
  `Ajc_Mes` INT(11) NOT NULL DEFAULT 0 COMMENT 'Mes si aplica',
  `Ajc_Escenario` VARCHAR(20) NOT NULL DEFAULT 'esperada' COMMENT 'esperada|proyectada|real',
  `Ajc_Est` VARCHAR(20) NOT NULL DEFAULT 'aplicado' COMMENT 'simulado|aplicado|anulado',
  `Ajc_PreIva` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Tarifa c/IVA',
  `Ajc_IvaDivisor` DECIMAL(8,4) NOT NULL DEFAULT 1.1500 COMMENT 'Divisor IVA',
  `Ajc_PreNeto` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Precio neto',
  `Ajc_CapPct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000 COMMENT '% capital',
  `Ajc_CapPorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Capital USD/Ton',
  `Ajc_CapTotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Capital total periodo',
  `Ajc_GadFacTon` DECIMAL(12,6) NOT NULL DEFAULT 0.000000 COMMENT 'Factor GAD USD/Ton',
  `Ajc_GadTon` DECIMAL(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Toneladas escenario',
  `Ajc_GadCalc` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD bruto',
  `Ajc_GadApli` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD aplicado',
  `Ajc_GadAcumAnt` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Acum antes',
  `Ajc_GadAcumDes` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Acum despues',
  `Ajc_GadSalDes` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo despues',
  `Ajc_GadObjetivo` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Objetivo GAD',
  `Ajc_GasBase` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Gasto base',
  `Ajc_GasFinal` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Gasto final',
  `Ajc_Ingreso` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Ingreso periodo',
  `Ajc_UtiBase` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Utilidad referencia',
  `Ajc_Obs` VARCHAR(255) DEFAULT NULL COMMENT 'Observacion',
  `Ajc_FecReg` DATETIME NOT NULL COMMENT 'Fecha de evento',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ajc_Cod`),
  KEY `idx_ajc_proy` (`Pro_Cod`,`Emp_Cod`,`Ppe_Cod`),
  KEY `idx_ajc_estado` (`Ajc_Est`),
  KEY `idx_ajc_ppe` (`Ppe_Cod`),
  KEY `idx_ajc_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ajc_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ajc_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ajc_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ajc_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial cabecera de ajuste financiero capital+GAD';

-- -----------------------------------------------------------------------------
-- 13. pre_ajuste_fin_det (Historial detalle por grupo de ajuste financiero)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_ajuste_fin_det` (
  `Ajd_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK detalle por grupo ajuste',
  `Ajc_Cod` INT(11) NOT NULL COMMENT 'FK pre_ajuste_fin_cab.Ajc_Cod',
  `Ajd_GrpCod` VARCHAR(20) NOT NULL COMMENT 'Codigo grupo (ej 14)',
  `Ajd_GrpNom` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nombre grupo',
  `Ajd_ParBase` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Partida base',
  `Ajd_PartPct` DECIMAL(10,6) NOT NULL DEFAULT 0.000000 COMMENT '% participacion',
  `Ajd_BasePorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Base USD/Ton',
  `Ajd_CapPorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Capital USD/Ton',
  `Ajd_GadPorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'GAD USD/Ton',
  `Ajd_AjuPorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Ajuste USD/Ton',
  `Ajd_FinPorTon` DECIMAL(14,6) NOT NULL DEFAULT 0.000000 COMMENT 'Final USD/Ton',
  `Ajd_CapMonto` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Capital monto',
  `Ajd_GadMonto` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'GAD monto',
  `Ajd_ParFinal` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Partida final',
  PRIMARY KEY (`Ajd_Cod`),
  KEY `idx_ajd_cab` (`Ajc_Cod`),
  CONSTRAINT `fk_ajd_cabecera` FOREIGN KEY (`Ajc_Cod`) REFERENCES `pre_ajuste_fin_cab` (`Ajc_Cod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial detalle de ajuste financiero por grupo';

-- -----------------------------------------------------------------------------
-- 14. pre_prod_config (Configuracion origen de produccion fisica)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_prod_config` (
  `Pco_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK configuracion produccion',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pco_Origen` VARCHAR(50) NOT NULL COMMENT 'Origen produccion',
  `Pco_Campo` VARCHAR(100) NOT NULL COMMENT 'Campo numerico origen',
  `Pco_Frecuencia` VARCHAR(20) NOT NULL DEFAULT 'mensual' COMMENT 'diaria|semanal|mensual',
  `Pco_MetodoFc` ENUM('promedio_historico','produccion_proyectada','manual') NOT NULL DEFAULT 'produccion_proyectada' COMMENT 'Metodo proyeccion',
  `Pco_FecIni` DATE DEFAULT NULL COMMENT 'Inicio validez',
  `Pco_FecFin` DATE DEFAULT NULL COMMENT 'Fin validez',
  `Pco_CfgExtra` TEXT COMMENT 'JSON parametros extra',
  `Pco_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Pco_Cod`),
  UNIQUE KEY `uq_pco_pro_emp` (`Pro_Cod`,`Emp_Cod`),
  KEY `idx_pco_origen` (`Pco_Origen`),
  KEY `idx_pco_emp_origen` (`Emp_Cod`,`Pco_Origen`),
  KEY `idx_pco_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pco_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pco_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pco_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Configuracion origen de produccion fisica';

-- -----------------------------------------------------------------------------
-- 15. pre_prod_periodos (Produccion esperada, real y proyectada por mes)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_prod_periodos` (
  `Prd_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK medicion periodo produccion',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Prd_Anio` INT(11) NOT NULL COMMENT 'Anio fiscal',
  `Prd_Mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `Prd_Esperada` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion esperada',
  `Prd_Real` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion real',
  `Prd_Proyectada` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Produccion proyectada',
  `Prd_Est` ENUM('sin_dato','en_curso','cerrado') NOT NULL DEFAULT 'sin_dato' COMMENT 'Estado periodo',
  `Prd_FecCierre` DATETIME DEFAULT NULL COMMENT 'Fecha cierre',
  `Prd_Unidad` VARCHAR(20) NOT NULL DEFAULT 'Ton' COMMENT 'Unidad de medida',
  `Prd_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Prd_Cod`),
  UNIQUE KEY `idx_prd_unico_periodo` (`Pro_Cod`,`Emp_Cod`,`Prd_Anio`,`Prd_Mes`),
  KEY `idx_prd_periodo` (`Prd_Anio`,`Prd_Mes`),
  KEY `idx_prd_empresa` (`Emp_Cod`),
  KEY `idx_prd_usu` (`Usu_Cod`),
  CONSTRAINT `fk_prd_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prd_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prd_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Produccion esperada, real y proyectada por mes';

-- -----------------------------------------------------------------------------
-- 16. pre_prod_variaciones (Variaciones produccion real vs esperada)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_prod_variaciones` (
  `Var_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK variacion produccion',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Var_Anio` INT(11) NOT NULL COMMENT 'Anio',
  `Var_Mes` INT(11) NOT NULL COMMENT 'Mes',
  `Var_Absoluta` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real - Esperada',
  `Var_Porcentual` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Variacion %',
  `Var_FecCal` DATETIME NOT NULL COMMENT 'Fecha calculo',
  PRIMARY KEY (`Var_Cod`),
  UNIQUE KEY `idx_var_unico_periodo` (`Pro_Cod`,`Emp_Cod`,`Var_Anio`,`Var_Mes`),
  KEY `idx_var_periodo` (`Var_Anio`,`Var_Mes`),
  CONSTRAINT `fk_var_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_var_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Variaciones de produccion real vs esperada';

-- -----------------------------------------------------------------------------
-- 17. pre_prod_evento_log (Bitacora de cierre/reapertura de produccion)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_prod_evento_log` (
  `Pel_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK evento produccion',
  `Pro_Cod` INT(11) NOT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pel_Anio` INT(11) NOT NULL COMMENT 'Anio',
  `Pel_Mes` INT(11) NOT NULL COMMENT 'Mes',
  `Pel_Tipo` ENUM('reapertura','correccion_sync_cerrado') NOT NULL COMMENT 'Tipo evento',
  `Pel_Origen` ENUM('manual','sync') NOT NULL COMMENT 'Origen evento',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  `Pel_FecEvt` DATETIME NOT NULL COMMENT 'Fecha evento',
  `Pel_RealAntes` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real antes',
  `Pel_RealDespues` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Real despues',
  `Pel_PfAntes` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'PF antes',
  `Pel_PfDespues` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'PF despues',
  `Pel_Motivo` VARCHAR(255) DEFAULT NULL COMMENT 'Motivo',
  `Pel_MotorVer` VARCHAR(10) NOT NULL DEFAULT 'v1_legacy' COMMENT 'Version motor',
  PRIMARY KEY (`Pel_Cod`),
  KEY `idx_pel_proy` (`Emp_Cod`,`Pro_Cod`,`Pel_Anio`,`Pel_Mes`),
  KEY `idx_pel_usu` (`Usu_Cod`),
  CONSTRAINT `fk_pel_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pel_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pel_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora de eventos de produccion';

-- -----------------------------------------------------------------------------
-- 18. pre_plantillas (Plantillas presupuestarias por empresa)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_plantillas` (
  `Plt_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK plantilla presupuestaria',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Plt_Nom` VARCHAR(150) NOT NULL COMMENT 'Nombre plantilla',
  `Plt_Des` TEXT DEFAULT NULL COMMENT 'Descripcion',
  `Plt_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  `Plt_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Plt_Cod`),
  KEY `idx_plt_empresa` (`Emp_Cod`),
  KEY `idx_plt_estado` (`Plt_Est`),
  KEY `idx_plt_usu` (`Usu_Cod`),
  CONSTRAINT `fk_plt_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_plt_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Plantillas presupuestarias por empresa';

-- -----------------------------------------------------------------------------
-- 19. pre_plantilla_partidas (Partidas asociadas a cada plantilla)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_plantilla_partidas` (
  `Plp_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK detalle plantilla-partida',
  `Plt_Cod` INT(11) NOT NULL COMMENT 'FK pre_plantillas.Plt_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  PRIMARY KEY (`Plp_Cod`),
  UNIQUE KEY `idx_plp_unico` (`Plt_Cod`,`Ppa_Cod`),
  KEY `idx_plp_ppa` (`Ppa_Cod`),
  CONSTRAINT `fk_plp_plantilla` FOREIGN KEY (`Plt_Cod`) REFERENCES `pre_plantillas` (`Plt_Cod`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_plp_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Partidas asociadas a cada plantilla';

-- -----------------------------------------------------------------------------
-- 20. pre_reajustes (Bitacora de reajustes presupuestarios)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_reajustes` (
  `Rea_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK reajuste presupuestario',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Rea_Tipo` VARCHAR(20) NOT NULL COMMENT 'transferencia|incremento|reduccion',
  `Ppa_Cod_Origen` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod origen',
  `Pro_Cod_Origen` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod origen',
  `Rea_RubroOrigen` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro origen',
  `Ppa_Cod_Destino` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod destino',
  `Pro_Cod_Destino` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod destino',
  `Rea_RubroDestino` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro destino',
  `Rea_Mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `Rea_Mon` DECIMAL(14,2) NOT NULL COMMENT 'Monto absoluto',
  `Rea_Jus` TEXT NOT NULL COMMENT 'Justificacion',
  `Rea_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Rea_Cod`),
  KEY `idx_rea_ppe` (`Ppe_Cod`),
  KEY `idx_rea_origen` (`Ppa_Cod_Origen`,`Pro_Cod_Origen`),
  KEY `idx_rea_destino` (`Ppa_Cod_Destino`,`Pro_Cod_Destino`),
  KEY `idx_rea_empresa` (`Emp_Cod`),
  KEY `idx_rea_tipo` (`Rea_Tipo`),
  KEY `idx_rea_periodo` (`Rea_Mes`),
  KEY `idx_rea_usu` (`Usu_Cod`),
  CONSTRAINT `fk_rea_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_partida_origen` FOREIGN KEY (`Ppa_Cod_Origen`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_partida_destino` FOREIGN KEY (`Ppa_Cod_Destino`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_proyecto_origen` FOREIGN KEY (`Pro_Cod_Origen`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_proyecto_destino` FOREIGN KEY (`Pro_Cod_Destino`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_rea_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora de reajustes presupuestarios';

-- -----------------------------------------------------------------------------
-- 21. pre_movimientos (Bitacora de afectaciones externas de otros modulos ERP)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_movimientos` (
  `Mov_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK movimiento externo',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Pro_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Mov_Rubro` VARCHAR(100) DEFAULT NULL COMMENT 'Rubro analitico',
  `Mov_DocCod` VARCHAR(50) NOT NULL COMMENT 'ID documento origen',
  `Mov_TipDoc` VARCHAR(50) NOT NULL COMMENT 'Tipo documento',
  `Mov_Modulo` VARCHAR(50) NOT NULL COMMENT 'Modulo origen',
  `Mov_TipMov` VARCHAR(20) NOT NULL COMMENT 'comprometido|ejecutado|reverso',
  `Mov_Mon` DECIMAL(14,2) NOT NULL COMMENT 'Monto absoluto',
  `Mov_Sig` CHAR(1) NOT NULL DEFAULT '+' COMMENT 'Signo +|-',
  `Mov_Mes` INT(11) NOT NULL COMMENT 'Mes 1..12',
  `Mov_Ani` INT(11) NOT NULL COMMENT 'Anio fiscal',
  `Mov_FecDoc` DATE NOT NULL COMMENT 'Fecha documento',
  `Mov_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro PPTO',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Mov_Cod`),
  KEY `idx_mov_ppe` (`Ppe_Cod`),
  KEY `idx_mov_empresa_proyecto` (`Emp_Cod`,`Pro_Cod`),
  KEY `idx_mov_partida_rubro` (`Ppa_Cod`,`Mov_Rubro`),
  KEY `idx_mov_documento` (`Mov_TipDoc`,`Mov_DocCod`),
  KEY `idx_mov_modulo_tipo` (`Mov_Modulo`,`Mov_TipMov`),
  KEY `idx_mov_periodo` (`Mov_Ani`,`Mov_Mes`),
  KEY `idx_mov_usu` (`Usu_Cod`),
  CONSTRAINT `fk_mov_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bitacora de afectaciones externas de otros modulos ERP';

-- -----------------------------------------------------------------------------
-- 22. pre_partida_cuenta (Puente partida presupuestaria <-> cuenta contable)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_partida_cuenta` (
  `Ppc_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK mapeo partida-cuenta',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pla_Cod` BIGINT(20) NOT NULL COMMENT 'FK plan_cuenta.Pla_Cod',
  `Ppa_Cod` INT(11) NOT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Pld_Cod` BIGINT(20) NOT NULL COMMENT 'FK det_plan.Pld_Cod',
  `Ppc_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activo I=Inactivo',
  `Ppc_FecReg` DATETIME NOT NULL COMMENT 'Fecha asignacion',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ppc_Cod`),
  UNIQUE KEY `uk_ppc_emp_pla_pld` (`Emp_Cod`,`Pla_Cod`,`Pld_Cod`),
  KEY `idx_ppc_emp_ppa` (`Emp_Cod`,`Ppa_Cod`),
  KEY `idx_ppc_pla` (`Pla_Cod`),
  KEY `idx_ppc_pld` (`Pld_Cod`),
  KEY `idx_ppc_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ppc_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_plan` FOREIGN KEY (`Pla_Cod`) REFERENCES `plan_cuenta` (`Pla_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_cuenta` FOREIGN KEY (`Pld_Cod`) REFERENCES `det_plan` (`Pld_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ppc_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Puente partida presupuestaria <-> cuenta contable';

-- -----------------------------------------------------------------------------
-- 23. pre_umbral_pf (Umbrales de alerta D8 PF vs VA)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_umbral_pf` (
  `Ubp_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK umbral PF vs VA',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppa_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod (NULL=global)',
  `Ubp_UmbralPct` DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Umbral % alerta',
  `Ubp_FecReg` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Ubp_Cod`),
  UNIQUE KEY `uk_ubp_emp_ppa` (`Emp_Cod`,`Ppa_Cod`),
  KEY `idx_ubp_emp` (`Emp_Cod`),
  KEY `idx_ubp_ppa` (`Ppa_Cod`),
  KEY `idx_ubp_usu` (`Usu_Cod`),
  CONSTRAINT `fk_ubp_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_ubp_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ubp_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Umbrales de alerta PF vs VA (D8)';

-- -----------------------------------------------------------------------------
-- 24. pre_bases (Bases de calculo del motor)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_bases` (
  `Bas_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK base de calculo',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Bas_Ide` VARCHAR(50) NOT NULL COMMENT 'Identificador funcional (toneladas, horas_maquina, ...)',
  `Bas_Nom` VARCHAR(100) NOT NULL COMMENT 'Nombre legible',
  `Bas_Des` VARCHAR(255) DEFAULT NULL COMMENT 'Detalle alimentacion del valor',
  `Bas_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  `Bas_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Bas_Cod`),
  UNIQUE KEY `uq_bas_emp_ide` (`Emp_Cod`,`Bas_Ide`),
  KEY `idx_bas_empresa` (`Emp_Cod`),
  KEY `idx_bas_usu` (`Usu_Cod`),
  CONSTRAINT `fk_bas_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bas_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Bases de calculo del motor presupuestario';

-- -----------------------------------------------------------------------------
-- 25. pre_formulas (Formulas presupuestarias parametrizables)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_formulas` (
  `Frm_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK formula presupuestaria',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Frm_Nom` VARCHAR(100) NOT NULL COMMENT 'Nombre de la formula',
  `Frm_Expresion` VARCHAR(255) NOT NULL COMMENT 'Expresion algebraica',
  `Frm_Variables` TEXT NOT NULL COMMENT 'JSON de variables del motor',
  `Frm_Est` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A=Activa I=Inactiva',
  `Frm_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Frm_Cod`),
  KEY `idx_frm_empresa` (`Emp_Cod`),
  KEY `idx_frm_estado` (`Frm_Est`),
  KEY `idx_frm_usu` (`Usu_Cod`),
  CONSTRAINT `fk_frm_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_frm_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Formulas presupuestarias parametrizables';

-- -----------------------------------------------------------------------------
-- 26. pre_divergencias (Divergencias D2 y auditoria de alertas)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_divergencias` (
  `Dvg_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK divergencia presupuestaria',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Ppe_Cod` INT(11) NOT NULL COMMENT 'FK pre_presupuesto.Ppe_Cod',
  `Ppa_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_partidas.Ppa_Cod',
  `Pro_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Dvg_Tipo` VARCHAR(50) NOT NULL COMMENT 'Tipo divergencia D2',
  `Dvg_MontoPF` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto Presupuesto Final',
  `Dvg_MontoVA` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto Valor Aprobado',
  `Dvg_Diferencia` DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Diferencia calculada',
  `Dvg_Pct` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje desviacion',
  `Dvg_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Dvg_Cod`),
  KEY `idx_dvg_emp` (`Emp_Cod`),
  KEY `idx_dvg_ppe` (`Ppe_Cod`),
  KEY `idx_dvg_ppa` (`Ppa_Cod`),
  KEY `idx_dvg_pro` (`Pro_Cod`),
  KEY `idx_dvg_usu` (`Usu_Cod`),
  CONSTRAINT `fk_dvg_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dvg_version` FOREIGN KEY (`Ppe_Cod`) REFERENCES `pre_presupuesto` (`Ppe_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dvg_partida` FOREIGN KEY (`Ppa_Cod`) REFERENCES `pre_partidas` (`Ppa_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dvg_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dvg_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Divergencias D2 y auditoria de alertas';

-- -----------------------------------------------------------------------------
-- 27. pre_perfiles (Perfiles y parametrizacion avanzada de proyectos)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_perfiles` (
  `Prf_Cod` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'PK perfil proyecto',
  `Emp_Cod` BIGINT(20) NOT NULL COMMENT 'FK empresas.Emp_Cod',
  `Pro_Cod` INT(11) DEFAULT NULL COMMENT 'FK pre_proyectos.Pro_Cod',
  `Prf_Nom` VARCHAR(100) NOT NULL COMMENT 'Nombre perfil (reinversion, relavera, ...)',
  `Prf_Tipo` VARCHAR(50) NOT NULL COMMENT 'Tipo perfil',
  `Prf_Config` TEXT DEFAULT NULL COMMENT 'JSON configuracion perfil',
  `Prf_FecReg` DATETIME NOT NULL COMMENT 'Fecha registro',
  `Usu_Cod` BIGINT(20) DEFAULT NULL COMMENT 'FK usuarios.Usu_Cod',
  PRIMARY KEY (`Prf_Cod`),
  KEY `idx_prf_emp` (`Emp_Cod`),
  KEY `idx_prf_pro` (`Pro_Cod`),
  KEY `idx_prf_usu` (`Usu_Cod`),
  CONSTRAINT `fk_prf_empresa` FOREIGN KEY (`Emp_Cod`) REFERENCES `empresas` (`Emp_Cod`) ON UPDATE CASCADE,
  CONSTRAINT `fk_prf_proyecto` FOREIGN KEY (`Pro_Cod`) REFERENCES `pre_proyectos` (`Pro_Cod`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_prf_usuario` FOREIGN KEY (`Usu_Cod`) REFERENCES `usuarios` (`Usu_Cod`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Perfiles y parametrizacion avanzada de proyectos';

-- -----------------------------------------------------------------------------
-- CONSULTAS Y SUBOCONSULTAS DINAMICAS EN CODIGO PHP
-- Nota: Se eliminaron todas las vistas en BD (CREATE VIEW). La logica de
-- consolidacion presupuestaria se genera dinamicamente mediante la funcion
-- ppto_sql_resumen_subquery() en PHP.
-- -----------------------------------------------------------------------------
DROP VIEW IF EXISTS `pre_resumen`;
DROP VIEW IF EXISTS `exa_ppto_resumen`;
DROP VIEW IF EXISTS `exa_ppto_cabeceras`;
DROP VIEW IF EXISTS `exa_ppto_partidas`;
DROP VIEW IF EXISTS `exa_ppto_detalles`;
DROP VIEW IF EXISTS `exa_ppto_ejecuciones`;

SET FOREIGN_KEY_CHECKS = 1;
