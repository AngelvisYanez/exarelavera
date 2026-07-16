-- ============================================================
-- Registro del Scraper SRI en la navegación del ERP legacy
-- ============================================================
-- NOTA: Este script es idempotente. Puede ejecutarse múltiples
--       veces sin crear duplicados.
-- ============================================================

-- 1. Verificar/Crear la ruta para administrador
INSERT IGNORE INTO rutas (Rut_Des, Rut_De2, Rut_Est)
SELECT '/administrador/FRONT/', '/administrador/FRONT/', 'A'
WHERE NOT EXISTS (
    SELECT 1 FROM rutas WHERE Rut_Des = '/administrador/FRONT/' LIMIT 1
);

SET @rut_cod = (SELECT Rut_Cod FROM rutas WHERE Rut_Des = '/administrador/FRONT/' LIMIT 1);

-- 2. Crear la carpeta/categoría "Scraper SRI" en el menú lateral
SET @existe_dir = (SELECT COUNT(*) FROM organizado WHERE Org_Des = 'Scraper SRI' AND Org_Niv = 0);

INSERT INTO organizado (Org_Niv, Org_Det, Org_Des, Org_Img, Org_Ime, Org_Ord, Org_Mod)
SELECT 0, 'Herramientas de descarga masiva del SRI', 'Scraper SRI', 'fa fa-cloud-download', 'fa fa-cloud-download', 98, 'A'
WHERE @existe_dir = 0;

SET @org_cod = (SELECT Org_Cod FROM organizado WHERE Org_Des = 'Scraper SRI' AND Org_Niv = 0 LIMIT 1);

-- 3. Crear el proceso/enlace "Descarga Masiva SRI"
SET @existe_proc = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'scrapers.php');

INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_cod, 'Descarga Masiva SRI', 'scrapers.php', @rut_cod, 'P', 'Descarga masiva de comprobantes electrónicos desde el SRI', 1, 1, 'A', 'fa fa-cloud-download', 'fa fa-angle-right'
WHERE @existe_proc = 0;

SET @pcs_cod = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'scrapers.php' LIMIT 1);

-- 4. Asignar el proceso al perfil Administrador (Per_Cod = 1)
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod)
SELECT 1, @pcs_cod
WHERE NOT EXISTS (
    SELECT 1 FROM perfiorgan WHERE Per_Cod = 1 AND Pcs_Cod = @pcs_cod
);

-- ============================================================
-- CONSULTAS DE VERIFICACIÓN
-- ============================================================

-- Verificar ruta
SELECT Rut_Cod, Rut_Des FROM rutas WHERE Rut_Des = '/administrador/FRONT/';

-- Verificar directorio
SELECT Org_Cod, Org_Des, Org_Niv, Org_Ord FROM organizado WHERE Org_Des = 'Scraper SRI';

-- Verificar proceso
SELECT Pcs_Cod, Pcs_Lin, Pcs_Nom, Pcs_Ord, Org_Cod FROM procesos WHERE Pcs_Nom = 'scrapers.php';

-- Verificar asignación a perfil
SELECT pf.Per_Cod, p.Pcs_Lin, p.Pcs_Nom 
FROM perfiorgan pf 
JOIN procesos p ON pf.Pcs_Cod = p.Pcs_Cod 
WHERE p.Pcs_Nom = 'scrapers.php' AND pf.Per_Cod = 1;
