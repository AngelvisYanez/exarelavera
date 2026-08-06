-- ============================================================
-- Fixtures de prueba para tests unitarios
-- ============================================================

-- Limpiar datos previos
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE comprobantes;
TRUNCATE TABLE asientos;
TRUNCATE TABLE det_plan;
TRUNCATE TABLE ventas;
TRUNCATE TABLE ventas_det;
TRUNCATE TABLE ventas_compr;
TRUNCATE TABLE perio_cierre;
TRUNCATE TABLE perio_cont;
TRUNCATE TABLE plan_cuenta;
TRUNCATE TABLE tipo_asien;
TRUNCATE TABLE cliente;
TRUNCATE TABLE persona;
TRUNCATE TABLE identifica;
TRUNCATE TABLE puntos_imp;
TRUNCATE TABLE autorizaci;
TRUNCATE TABLE sucursal;
TRUNCATE TABLE vendedor;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Datos maestros
-- ============================================================

INSERT INTO tipo_asien (Tia_Cod, Tia_Abr, Tia_Des) VALUES
(1, 'VEN', 'Ventas'),
(2, 'COM', 'Compras'),
(3, 'CIE', 'Cierre'),
(4, 'AJU', 'Ajustes');

INSERT INTO plan_cuenta (Pla_Cod, Pla_Des, Pla_Niv) VALUES
('1', 'ACTIVO', 1),
('1.1', 'CORRIENTE', 2),
('1.1.1', 'CAJA', 3),
('2', 'PASIVO', 1),
('2.1', 'CORRIENTE', 2),
('2.1.1', 'PROVEEDORES', 3),
('3', 'PATRIMONIO', 1),
('3.1', 'CAPITAL', 2),
('4', 'INGRESOS', 1),
('4.1', 'VENTAS', 2),
('5', 'GASTOS', 1),
('5.1', 'COSTOS', 2);

INSERT INTO persona (Per_Cod, Per_Nom, Per_Ape) VALUES
(1, 'CLIENTE', 'PRUEBA'),
(2, 'PROVEEDOR', 'PRUEBA');

INSERT INTO identifica (Ide_Cod, Ide_Des) VALUES
(1, 'RUC'),
(2, 'CEDULA');

INSERT INTO cliente (Cli_Cod, Per_Cod, Cli_Ide, Ide_Cod) VALUES
(1, 1, '1790012345001', 1);

INSERT INTO sucursal (Suc_Cod, Suc_Des) VALUES
(1, 'MATRIZ');

INSERT INTO puntos_imp (Pun_Cod, Pun_Des) VALUES
(1, 'PUNTO PRUEBA');

INSERT INTO autorizaci (Aut_Cod, Aut_Num, Aut_Fei, Aut_Fef) VALUES
(1, '1234567890', '2026-01-01', '2027-01-01');

INSERT INTO vendedor (Ven_Cod, Ven_Nom) VALUES
(1, 'VENDEDOR PRUEBA');

-- ============================================================
-- Período contable abierto
-- ============================================================
INSERT INTO perio_cont (Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est) VALUES
(1, '2026-01-01', '2026-12-31', 'A');

-- ============================================================
-- Comprobante de venta de prueba
-- ============================================================
INSERT INTO comprobantes (Com_Cod, Com_Num, Com_Fec, Tia_Cod, Com_Est, Emp_Cod) VALUES
(1, '001-001-000000001', '2026-06-15', 1, 'A', 1);

INSERT INTO asientos (Asi_Cod, Com_Cod, Asi_Num, Asi_Doc, Asi_Fec) VALUES
(1, 1, 1, 'Factura #1', '2026-06-15');

INSERT INTO det_plan (Pld_Cod, Asi_Cod, Pla_Cod, Pld_Deb, Pld_Hab) VALUES
(1, 1, '4.1', 0, 100.00),
(2, 1, '1.1.1', 100.00, 0);

-- ============================================================
-- Ventas de prueba
-- ============================================================
INSERT INTO ventas (Vet_Cod, Cli_Cod, Suc_Cod, Ven_Cod, Pun_Cod, Aut_Cod,
                    Vet_Fec, Vet_Sub, Vet_Iva, Vet_Total, Vet_Est) VALUES
(1, 1, 1, 1, 1, 1, '2026-06-15', 100.00, 12.00, 112.00, 'A');

INSERT INTO ventas_det (Vde_Cod, Vet_Cod, Pro_Cod, Vde_Can, Vde_Pre, Vde_Sub,
                        Iva_Por, Ren_Cod, Ren_Iva) VALUES
(1, 1, 1, 10, 10.00, 100.00, 12, NULL, NULL);

INSERT INTO ventas_compr (Vet_Cod, Com_Cod) VALUES (1, 1);

-- ============================================================
-- Período cerrado de prueba
-- ============================================================
INSERT INTO perio_cierre (Pci_Cod, Pec_Cod, Pci_Fec, Pci_Est, Emp_Cod) VALUES
(1, 1, '2026-05-31', 'C', 1);
