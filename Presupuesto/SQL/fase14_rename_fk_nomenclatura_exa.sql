-- =============================================================================
-- FASE 14: Alinear FK ERP a nomenclatura EXA
-- emp_id -> Emp_Cod | usu_id -> Usu_Cod | suc_id -> Suc_Cod | dep_id -> Dep_Cod
-- pla_cod -> Pla_Cod | pld_cod -> Pld_Cod | pel_usu_id -> Usu_Cod
-- PK internas del modulo (ppe_id, ppa_id, proy_id, ...) se mantienen.
-- Ejecutar en cada BD tenant. Idempotente parcial: si ya renombrado, ignorar errores.
-- =============================================================================

DROP VIEW IF EXISTS exa_ppto_resumen;

-- 1) Vistas puente sobre pre_* (exponen Emp_Cod / Usu_Cod / Suc_Cod / Dep_Cod)
CREATE OR REPLACE VIEW exa_ppto_cabeceras AS
SELECT
  Ppe_Cod AS ppe_id,
  Emp_Cod AS Emp_Cod,
  Ppe_Ani AS ppe_anio,
  Ppe_Ver AS ppe_version,
  Ppe_Des AS ppe_descripcion,
  Ppe_Est AS ppe_estado,
  Ppe_Fec AS ppe_fecha_registro,
  Usu_Cod AS Usu_Cod
FROM pre_presupuesto;

CREATE OR REPLACE VIEW exa_ppto_partidas AS
SELECT
  Ppa_Cod AS ppa_id,
  Emp_Cod AS Emp_Cod,
  Ppa_Cla AS ppa_codigo_clasificacion,
  Ppa_Des AS ppa_descripcion,
  Ppa_Tip AS ppa_tipo,
  Ppa_Nat AS ppa_naturaleza,
  Ppa_Pad AS ppa_padre_id,
  Ppa_Niv AS ppa_nivel,
  Ppa_Clase AS ppa_clase,
  Ppa_Pct AS ppa_porcentaje_tope,
  Ppa_Meses AS ppa_meses_prorrateo,
  Ppa_Est AS ppa_estado,
  Ppa_Fec AS ppa_fecha_registro,
  Usu_Cod AS Usu_Cod
FROM pre_partidas;

CREATE OR REPLACE VIEW exa_ppto_ejecuciones AS
SELECT
  Pej_Cod AS pej_id,
  Ppe_Cod AS ppe_id,
  Ppa_Cod AS ppa_id,
  Emp_Cod AS Emp_Cod,
  Suc_Cod AS Suc_Cod,
  Dep_Cod AS Dep_Cod,
  Pec_Cod AS proy_id,
  Pej_Mes AS pej_mes,
  Pej_Ani AS pej_anio,
  Pej_TipDoc AS pej_tipo_documento,
  Pej_DocCod AS pej_documento_codigo,
  Pej_Mon AS pej_monto,
  Pej_Sig AS pej_signo,
  Pej_Fec AS pej_fecha_documento,
  Pej_FecReg AS pej_fecha_registro,
  Usu_Cod AS Usu_Cod,
  Prg_Cod AS prg_id,
  Pej_Fase AS pej_fase,
  Pej_Rubro AS pej_rubro
FROM pre_ejecucion;

-- 2) Tablas fisicas: soltar FKs que bloquean rename de emp_id
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE exa_ppto_prod_config DROP FOREIGN KEY exa_ppto_prod_config_ibfk_1;
ALTER TABLE exa_ppto_prod_periodos DROP FOREIGN KEY exa_ppto_prod_periodos_ibfk_1;
ALTER TABLE exa_ppto_prod_variaciones DROP FOREIGN KEY exa_ppto_prod_variaciones_ibfk_1;
ALTER TABLE exa_ppto_proyecto_detalles DROP FOREIGN KEY exa_ppto_proyecto_detalles_ibfk_3;

ALTER TABLE exa_ppto_formulas CHANGE COLUMN emp_id Emp_Cod INT NOT NULL;
ALTER TABLE exa_ppto_movimientos CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_reajustes CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_umbral_pf CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NULL;
ALTER TABLE exa_ppto_partida_cuenta
  CHANGE COLUMN emp_id Emp_Cod INT NOT NULL,
  CHANGE COLUMN pla_cod Pla_Cod INT NOT NULL,
  CHANGE COLUMN pld_cod Pld_Cod BIGINT NOT NULL,
  CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_plantillas CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_proyectos CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_proyecto_detalles CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_proyecto_version CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL DEFAULT 0;
ALTER TABLE exa_ppto_proyecto_precio_anio CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL DEFAULT 0;
ALTER TABLE exa_ppto_proyecto_publicacion CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_ajuste_fin_cab CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL DEFAULT 0;
ALTER TABLE exa_ppto_prod_config CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_prod_periodos CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;
ALTER TABLE exa_ppto_prod_variaciones CHANGE COLUMN emp_id Emp_Cod INT NOT NULL;
ALTER TABLE exa_ppto_prod_evento_log CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN pel_usu_id Usu_Cod INT NOT NULL DEFAULT 0;
ALTER TABLE exa_ppto_reglas CHANGE COLUMN emp_id Emp_Cod INT NOT NULL, CHANGE COLUMN usu_id Usu_Cod INT NOT NULL;

ALTER TABLE exa_ppto_prod_config
  ADD CONSTRAINT exa_ppto_prod_config_ibfk_1
  FOREIGN KEY (proy_id, Emp_Cod) REFERENCES exa_ppto_proyectos (proy_id, Emp_Cod);
ALTER TABLE exa_ppto_prod_periodos
  ADD CONSTRAINT exa_ppto_prod_periodos_ibfk_1
  FOREIGN KEY (proy_id, Emp_Cod) REFERENCES exa_ppto_proyectos (proy_id, Emp_Cod);
ALTER TABLE exa_ppto_prod_variaciones
  ADD CONSTRAINT exa_ppto_prod_variaciones_ibfk_1
  FOREIGN KEY (proy_id, Emp_Cod) REFERENCES exa_ppto_proyectos (proy_id, Emp_Cod);
ALTER TABLE exa_ppto_proyecto_detalles
  ADD CONSTRAINT exa_ppto_proyecto_detalles_ibfk_3
  FOREIGN KEY (proy_id, Emp_Cod) REFERENCES exa_ppto_proyectos (proy_id, Emp_Cod);

SET FOREIGN_KEY_CHECKS = 1;

-- 3) Vista resumen
CREATE OR REPLACE VIEW exa_ppto_resumen AS
SELECT
  c.Emp_Cod AS Emp_Cod,
  d.ppe_id AS ppe_id,
  d.ppa_id AS ppa_id,
  CAST(NULL AS CHAR(50)) AS proy_id,
  CAST(NULL AS CHAR(100)) AS pdp_rubro,
  d.pde_mes AS mes,
  d.pde_monto AS inicial,
  CAST(0.00 AS DECIMAL(14,2)) AS reajustes,
  d.pde_monto AS vigente,
  CAST(0.00 AS DECIMAL(14,2)) AS comprometido,
  CAST(0.00 AS DECIMAL(14,2)) AS ejecutado,
  d.pde_monto AS disponible
FROM exa_ppto_detalles d
INNER JOIN exa_ppto_cabeceras c ON d.ppe_id = c.ppe_id
UNION ALL
SELECT
  pd.Emp_Cod AS Emp_Cod, pd.ppe_id AS ppe_id, pd.ppa_id AS ppa_id, pd.proy_id AS proy_id, pd.pdp_rubro AS pdp_rubro,
  pdm.pdm_mes AS mes, pdm.pdm_presupuesto_mensual AS inicial, CAST(0.00 AS DECIMAL(14,2)) AS reajustes,
  pdm.pdm_presupuesto_mensual AS vigente,
  IFNULL(pdm.pdm_comprometido, 0.00) AS comprometido,
  IFNULL(pdm.pdm_ejecutado, 0.00) AS ejecutado,
  IFNULL(pdm.pdm_disponible, pdm.pdm_presupuesto_mensual) AS disponible
FROM exa_ppto_proyecto_detalles pd
INNER JOIN exa_ppto_proyecto_detalles_mes pdm ON pd.pdp_id = pdm.pdp_id
UNION ALL
SELECT
  r.Emp_Cod, r.ppe_id, r.ppa_id_destino, r.proy_id_destino, r.pdp_rubro_destino,
  r.rea_mes, 0.00, r.rea_monto, r.rea_monto, 0.00, 0.00, r.rea_monto
FROM exa_ppto_reajustes r WHERE r.rea_tipo IN ('incremento', 'transferencia')
UNION ALL
SELECT
  r.Emp_Cod, r.ppe_id, r.ppa_id_origen, r.proy_id_origen, r.pdp_rubro_origen,
  r.rea_mes, 0.00, -(r.rea_monto), -(r.rea_monto), 0.00, 0.00, -(r.rea_monto)
FROM exa_ppto_reajustes r WHERE r.rea_tipo = 'transferencia'
UNION ALL
SELECT
  r.Emp_Cod, r.ppe_id, r.ppa_id_destino, r.proy_id_destino, r.pdp_rubro_destino,
  r.rea_mes, 0.00, -(r.rea_monto), -(r.rea_monto), 0.00, 0.00, -(r.rea_monto)
FROM exa_ppto_reajustes r WHERE r.rea_tipo = 'reduccion'
UNION ALL
SELECT
  pe.Emp_Cod, pe.ppe_id, pe.ppa_id, pe.proy_id, pe.pej_rubro, pe.pej_mes,
  0.00, 0.00, 0.00,
  (CASE WHEN pe.pej_fase = 'C' THEN (CASE WHEN pe.pej_signo = '+' THEN pe.pej_monto ELSE -(pe.pej_monto) END) ELSE 0.00 END),
  (CASE WHEN pe.pej_fase = 'E' THEN (CASE WHEN pe.pej_signo = '+' THEN pe.pej_monto ELSE -(pe.pej_monto) END) ELSE 0.00 END),
  (-1 * (CASE WHEN pe.pej_fase IN ('C','E') THEN (CASE WHEN pe.pej_signo = '+' THEN pe.pej_monto ELSE -(pe.pej_monto) END) ELSE 0.00 END))
FROM exa_ppto_ejecuciones pe;
