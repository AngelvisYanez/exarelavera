-- ============================================================
-- Script de optimización de índices para cierres de mes
-- Ejecutar en la base de datos de cada empresa
-- Uso: mysql -u root -p nombre_empresa < este_script.sql
-- ============================================================

-- 1. Índices compuestos para tablas puente (hub-and-spoke)
CREATE INDEX IF NOT EXISTS idx_ventas_compr_vet_cod ON ventas_compr (Vet_Cod, Com_Cod);
CREATE INDEX IF NOT EXISTS idx_compr_auto_cop_cod ON compr_auto (Cop_Cod, Com_Cod);

-- 2. Índices para cuentas por cobrar/pagar (cierres de tesorería)
CREATE INDEX IF NOT EXISTS idx_ccpp_cobrar_vet ON ccpp_cobrar (Vet_Cod, Ccp_Est, Ccp_Fec);
CREATE INDEX IF NOT EXISTS idx_ccpp_pagar_cop ON ccpp_pagar (Cop_Cod, Ccp_Est, Ccp_Fec);

-- 3. Índice compuesto para kardex (inventario + contabilidad)
CREATE INDEX IF NOT EXISTS idx_kardex_ie_emp_pro ON kardex_ie (Pro_Cod, Emp_Cod, Kar_Fec);

-- 4. Índice crítico para comprobantes (cierres contables por período)
CREATE INDEX IF NOT EXISTS idx_comprobantes_fec_est ON comprobantes (Com_Fec, Com_Est, Emp_Cod);

-- 5. Índice para bloqueo de períodos (validaCierrePeriodo)
CREATE INDEX IF NOT EXISTS idx_perio_cierre_emp ON perio_cierre (Pec_Cod, Pci_Est, Emp_Cod);

-- 6. Índices para asientos contables (mayorización)
CREATE INDEX IF NOT EXISTS idx_asientos_comprobante ON asientos (Com_Cod, Asi_Cod);
CREATE INDEX IF NOT EXISTS idx_det_plan_asiento ON det_plan (Asi_Cod, Pld_Cod);

-- 7. Índices para reportes de ventas (grid optimizado N+1)
CREATE INDEX IF NOT EXISTS idx_pago_venta_vet ON pago_venta (Vet_Cod, Pag_Cod);
CREATE INDEX IF NOT EXISTS idx_renta_iva_ren ON renta_iva (Ren_Cod, Ren_Por);

-- ============================================================
-- Análisis y mantenimiento
-- ============================================================
ANALYZE TABLE ventas_compr, compr_auto, ccpp_cobrar, ccpp_pagar,
                kardex_ie, comprobantes, perio_cierre, asientos,
                det_plan, pago_venta, renta_iva;

-- ============================================================
-- Verificar índices creados
-- ============================================================
SHOW INDEX FROM ventas_compr;
SHOW INDEX FROM comprobantes;
SHOW INDEX FROM perio_cierre;
