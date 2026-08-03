-- Ajustes financieros: costo de capital + recuperacion GAD (fase 13)
-- Compatible MariaDB / MySQL 5.x+

ALTER TABLE exa_ppto_proyecto_version
    ADD COLUMN pv_costo_capital_pct DECIMAL(8,4) NOT NULL DEFAULT 11.0000
        COMMENT 'Porcentaje costo de capital sobre precio neto' AFTER pv_iva_divisor,
    ADD COLUMN pv_gad_monto_objetivo DECIMAL(14,2) NOT NULL DEFAULT 2000000.00
        COMMENT 'Monto objetivo recuperacion GAD' AFTER pv_costo_capital_pct,
    ADD COLUMN pv_gad_factor_ton DECIMAL(12,6) NOT NULL DEFAULT 0.198400
        COMMENT 'USD recuperacion GAD por tonelada' AFTER pv_gad_monto_objetivo,
    ADD COLUMN pv_gad_recuperado_acum DECIMAL(14,2) NOT NULL DEFAULT 0.00
        COMMENT 'Recuperacion GAD acumulada aplicada' AFTER pv_gad_factor_ton,
    ADD COLUMN pv_ajuste_activo TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=usar partida final en cuadro' AFTER pv_gad_recuperado_acum;

CREATE TABLE IF NOT EXISTS exa_ppto_proyecto_precio_anio (
  proy_id VARCHAR(50) NOT NULL,
  emp_id INT NOT NULL,
  ppe_id INT NOT NULL,
  ppa_anio INT NOT NULL COMMENT 'Anio calendario de la tarifa',
  ppa_tarifa_ton_iva DECIMAL(12,4) NOT NULL DEFAULT 3.0000,
  ppa_fecha_registro DATETIME NOT NULL,
  usu_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY (proy_id, emp_id, ppe_id, ppa_anio),
  KEY idx_pppa_emp (emp_id),
  KEY idx_pppa_ppe (ppe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Proyeccion de precio $/Ton con IVA por anio';

CREATE TABLE IF NOT EXISTS exa_ppto_ajuste_fin_cab (
  ajc_id INT NOT NULL AUTO_INCREMENT,
  proy_id VARCHAR(50) NOT NULL,
  emp_id INT NOT NULL,
  ppe_id INT NOT NULL,
  ajc_anio INT NOT NULL,
  ajc_vista VARCHAR(20) NOT NULL DEFAULT 'anual',
  ajc_mes INT NOT NULL DEFAULT 0,
  ajc_escenario VARCHAR(20) NOT NULL DEFAULT 'esperada',
  ajc_estado VARCHAR(20) NOT NULL DEFAULT 'aplicado' COMMENT 'simulado|aplicado|anulado',
  ajc_precio_iva DECIMAL(12,4) NOT NULL DEFAULT 0,
  ajc_iva_divisor DECIMAL(8,4) NOT NULL DEFAULT 1.1500,
  ajc_precio_neto DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajc_capital_pct DECIMAL(8,4) NOT NULL DEFAULT 0,
  ajc_capital_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajc_capital_total DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_factor_ton DECIMAL(12,6) NOT NULL DEFAULT 0,
  ajc_gad_toneladas DECIMAL(14,4) NOT NULL DEFAULT 0,
  ajc_gad_calculado DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_aplicado DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_acum_antes DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_acum_despues DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_saldo_despues DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gad_objetivo DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gasto_base DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_gasto_final DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_ingreso DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_utilidad_base DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajc_observacion VARCHAR(255) NULL,
  ajc_fecha_registro DATETIME NOT NULL,
  usu_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY (ajc_id),
  KEY idx_ajc_proy (proy_id, emp_id, ppe_id),
  KEY idx_ajc_estado (ajc_estado),
  KEY idx_ajc_fecha (ajc_fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial cabecera ajustes financieros';

CREATE TABLE IF NOT EXISTS exa_ppto_ajuste_fin_det (
  ajd_id INT NOT NULL AUTO_INCREMENT,
  ajc_id INT NOT NULL,
  grupo_cod VARCHAR(20) NOT NULL,
  grupo_nombre VARCHAR(255) NOT NULL DEFAULT '',
  ajd_partida_base DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajd_participacion_pct DECIMAL(10,6) NOT NULL DEFAULT 0,
  ajd_base_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajd_capital_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajd_gad_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajd_ajuste_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajd_final_por_ton DECIMAL(14,6) NOT NULL DEFAULT 0,
  ajd_capital_monto DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajd_gad_monto DECIMAL(14,2) NOT NULL DEFAULT 0,
  ajd_partida_final DECIMAL(14,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (ajd_id),
  KEY idx_ajd_cab (ajc_id),
  KEY idx_ajd_grupo (grupo_cod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Historial detalle ajustes por grupo/partida';
