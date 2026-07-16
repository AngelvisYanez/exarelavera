-- ============================================================
-- Menú: Control Tributario EC (versión nueva)
-- Ejecutar en la base de datos de EXA
-- ============================================================

-- 1. Agregar proceso en la tabla 'procesos'
-- Pcs_Cod 912 (siguiente AUTO_INCREMENT después del último registro)
-- Org_Cod 62 = nodo padre "Control Tributario" en Tesorería
-- Rut_Cod 6 = /tesoreria/FRONT/
INSERT INTO `procesos` (
    `Pcs_Cod`, `Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, 
    `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Pcs_Hab`, `Tpr_Cod`, 
    `Pcs_Rec`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`
) VALUES (
    912, 
    62, 
    'Control Tributario EC - Herramienta completa con scrapers, parsers y cálculos automáticos', 
    20, 
    'Control Trib. EC', 
    'A', 
    6, 
    'tes_con_trib_ec.php', 
    NULL, 
    1, 
    NULL, 
    'new-on.png', 
    'P', 
    'fa fa-calculator', 
    'S'
);

-- 2. Asignar acceso al perfil 1 (Administrador)
INSERT INTO `perfiorgan` (`Per_Cod`, `Pcs_Cod`) VALUES (1, 912);

-- 3. Asignar acceso al perfil 2 (Si existe - Gerente/Contador)
-- Descomentar si el perfil 2 también debe tener acceso:
-- INSERT INTO `perfiorgan` (`Per_Cod`, `Pcs_Cod`) VALUES (2, 912);

-- 4. Asignar acceso al perfil 3 (Si existe - Técnico)
-- Descomentar si el perfil 3 también debe tener acceso:
-- INSERT INTO `perfiorgan` (`Per_Cod`, `Pcs_Cod`) VALUES (3, 912);

-- ============================================================
-- Verificación
-- ============================================================
-- Para verificar que se agregó correctamente:
-- SELECT * FROM procesos WHERE Pcs_Cod = 912;
-- SELECT * FROM perfiorgan WHERE Pcs_Cod = 912;
