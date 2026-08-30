-- ============================================================
-- Agregar menú de Módulos Externos al sistema
-- ============================================================
-- Ejecutar después de directorio_modulos_external.sql
-- ============================================================

-- Obtener el código del organizado padre para Administración
SET @org_adm = (SELECT Org_Cod FROM organizado WHERE Org_Des = 'Administración' LIMIT 1);

-- Si no existe Administración, usar un código base
SET @org_adm = IFNULL(@org_adm, 5);

-- Obtener la ruta para administrador
SET @rut_adm = (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%administrador%' LIMIT 1);

-- Insertar el organizado para Módulos Externos
INSERT IGNORE INTO organizado (Org_Niv, Org_Des, Org_Det, Org_Mod, Org_Ord, Org_Ico, Org_Est) 
VALUES (@org_adm, 'Módulos Externos', 'Gestión de módulos externos (Node.js, Python, Shell)', 'A', 99, 'fa fa-cogs', 'A');

-- Obtener el código del organizado recién creado
SET @org_externos = (SELECT Org_Cod FROM organizado WHERE Org_Des = 'Módulos Externos' AND Org_Niv = @org_adm LIMIT 1);

-- Insertar el proceso para la página de gestión de módulos externos
INSERT IGNORE INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Pcs_Det, Pcs_Tip, Pcs_Est, Rut_Cod, Pcs_Ord, Pcs_Ico) 
VALUES (@org_externos, 'Gestionar Módulos Externos', 'adm_gst_externos.php', 'Administrar módulos externos Node.js, Python y Shell para automatizaciones', 'P', 'A', @rut_adm, 1, 'fa fa-cogs');

-- Obtener el código del proceso recién creado
SET @pcs_externos = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adm_gst_externos.php' LIMIT 1);

-- Asignar acceso al perfil de Administrador (Per_Cod = 1 generalmente)
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod, Pai_Niv) 
VALUES (1, @pcs_externos, 1);

-- Verificar la inserción
SELECT 
    o.Org_Cod, o.Org_Des, o.Org_Det, o.Org_Ico,
    p.Pcs_Cod, p.Pcs_Lin, p.Pcs_Nom, p.Pcs_Det
FROM organizado o
LEFT JOIN procesos p ON o.Org_Cod = p.Org_Cod
WHERE o.Org_Des = 'Módulos Externos'
ORDER BY o.Org_Cod, p.Pcs_Cod;
