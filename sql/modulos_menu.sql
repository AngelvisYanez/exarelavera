-- ============================================================
-- Registro de Módulos: Scraper SRI y Flujo de Adquisiciones
-- en la navegación del ERP legacy
-- ============================================================
-- Ejecutar en la base de datos del proyecto
-- El script es idempotente: puede ejecutarse múltiples veces
-- sin crear duplicados.
-- ============================================================

-- ============================================================
-- PASO 1: Verificar/Crear las rutas necesarias
-- ============================================================

-- Ruta para administrador
INSERT IGNORE INTO rutas (Rut_Des, Rut_De2, Rut_Est)
SELECT '/administrador/FRONT/', '/administrador/FRONT/', 'A'
WHERE NOT EXISTS (
    SELECT 1 FROM rutas WHERE Rut_Des = '/administrador/FRONT/' LIMIT 1
);

-- Ruta para flujo
INSERT IGNORE INTO rutas (Rut_Des, Rut_De2, Rut_Est)
SELECT '/flujo/FRONT/', '/flujo/FRONT/', 'A'
WHERE NOT EXISTS (
    SELECT 1 FROM rutas WHERE Rut_Des = '/flujo/FRONT/' LIMIT 1
);

-- Obtener códigos de rutas
SET @rut_admin = (SELECT Rut_Cod FROM rutas WHERE Rut_Des = '/administrador/FRONT/' LIMIT 1);
SET @rut_flujo = (SELECT Rut_Cod FROM rutas WHERE Rut_Des = '/flujo/FRONT/' LIMIT 1);

-- ============================================================
-- PASO 2: Crear directorio "Scraper SRI" (si no existe)
-- ============================================================

-- Verificar si ya existe
SET @existe_sri = (SELECT COUNT(*) FROM organizado WHERE Org_Des = 'Scraper SRI' AND Org_Niv = 0);

INSERT INTO organizado (Org_Niv, Org_Det, Org_Des, Org_Img, Org_Ime, Org_Ord, Org_Mod)
SELECT 0, 'Herramientas de descarga masiva del SRI', 'Scraper SRI', 'fa fa-cloud-download', 'fa fa-cloud-download', 98, 'A'
WHERE @existe_sri = 0;

SET @org_sri = (SELECT Org_Cod FROM organizado WHERE Org_Des = 'Scraper SRI' AND Org_Niv = 0 LIMIT 1);

-- ============================================================
-- PASO 3: Crear proceso "Descarga Masiva SRI" (si no existe)
-- ============================================================

SET @existe_sri_proc = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'scrapers.php');

INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_sri, 'Descarga Masiva SRI', 'scrapers.php', @rut_admin, 'P', 'Descarga masiva de comprobantes electrónicos desde el SRI', 1, 1, 'A', 'fa fa-cloud-download', 'fa fa-angle-right'
WHERE @existe_sri_proc = 0;

SET @pcs_sri = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'scrapers.php' LIMIT 1);

-- Asignar al perfil Administrador (Per_Cod = 1)
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod)
SELECT 1, @pcs_sri
WHERE NOT EXISTS (
    SELECT 1 FROM perfiorgan WHERE Per_Cod = 1 AND Pcs_Cod = @pcs_sri
);

-- ============================================================
-- PASO 4: Crear directorio "Flujo de Adquisiciones" (si no existe)
-- ============================================================

SET @existe_flujo = (SELECT COUNT(*) FROM organizado WHERE Org_Des = 'Flujo de Adquisiciones' AND Org_Niv = 0);

INSERT INTO organizado (Org_Niv, Org_Det, Org_Des, Org_Img, Org_Ime, Org_Ord, Org_Mod)
SELECT 0, 'Gestión de flujos de trabajo y adquisiciones', 'Flujo de Adquisiciones', 'fa fa-random', 'fa fa-random', 97, 'A'
WHERE @existe_flujo = 0;

SET @org_flujo = (SELECT Org_Cod FROM organizado WHERE Org_Des = 'Flujo de Adquisiciones' AND Org_Niv = 0 LIMIT 1);

-- ============================================================
-- PASO 5: Crear procesos del módulo Flujo (si no existen)
-- ============================================================

-- 5.1 Bandeja de Trabajo
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_bandeja.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Bandeja de Trabajo', 'adq_bandeja.php', @rut_flujo, 'P', 'Bandeja de trabajo de usuarios para aprobar/rechazar solicitudes', 1, 1, 'A', 'fa fa-inbox', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.2 Nueva Solicitud
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_solicitud.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Nueva Solicitud', 'adq_solicitud.php', @rut_flujo, 'P', 'Registro de nuevas solicitudes de adquisición', 1, 2, 'A', 'fa fa-file-text-o', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.3 Listado de Solicitudes
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_lista_solicitud.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Listado de Solicitudes', 'adq_lista_solicitud.php', @rut_flujo, 'P', 'Listado general de solicitudes de adquisición', 1, 3, 'A', 'fa fa-list-alt', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.4 Dashboard Gerencial
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_dashboard.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Dashboard Gerencial', 'adq_dashboard.php', @rut_flujo, 'P', 'Dashboard gerencial de flujos y estadísticas de adquisiciones', 1, 4, 'A', 'fa fa-tachometer', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.5 Seguimiento de Requerimientos
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_seguimiento.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Seguimiento', 'adq_seguimiento.php', @rut_flujo, 'P', 'Seguimiento detallado de requerimientos con línea de tiempo y SLA', 1, 5, 'A', 'fa fa-eye', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.6 Configuración (Panel Unificado)
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_configuracion.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Configuración', 'adq_configuracion.php', @rut_flujo, 'P', 'Panel de configuración: tipos de requerimientos y diseñador de flujos', 1, 6, 'A', 'fa fa-cog', 'fa fa-angle-right'
WHERE @existe = 0;

-- 5.7 Departamentos
SET @existe = (SELECT COUNT(*) FROM procesos WHERE Pcs_Nom = 'adq_departamentos.php');
INSERT INTO procesos (Org_Cod, Pcs_Lin, Pcs_Nom, Rut_Cod, Pcs_Tip, Pcs_Det, Tpr_Cod, Pcs_Ord, Pcs_Est, Pcs_Img, Pcs_Ico)
SELECT @org_flujo, 'Departamentos', 'adq_departamentos.php', @rut_flujo, 'P', 'CRUD de departamentos y asignación de usuarios', 1, 7, 'A', 'fa fa-building', 'fa fa-angle-right'
WHERE @existe = 0;

-- ============================================================
-- PASO 6: Asignar procesos del Flujo al perfil Administrador
-- ============================================================

SET @pcs_bandeja = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_bandeja.php' LIMIT 1);
SET @pcs_solicitud = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_solicitud.php' LIMIT 1);
SET @pcs_lista = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_lista_solicitud.php' LIMIT 1);
SET @pcs_dashboard = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_dashboard.php' LIMIT 1);
SET @pcs_seguimiento = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_seguimiento.php' LIMIT 1);
SET @pcs_config = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_configuracion.php' LIMIT 1);
SET @pcs_deptos = (SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = 'adq_departamentos.php' LIMIT 1);

-- Asignar todos al perfil Administrador (Per_Cod = 1)
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_bandeja WHERE @pcs_bandeja IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_solicitud WHERE @pcs_solicitud IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_lista WHERE @pcs_lista IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_dashboard WHERE @pcs_dashboard IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_seguimiento WHERE @pcs_seguimiento IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_config WHERE @pcs_config IS NOT NULL;
INSERT IGNORE INTO perfiorgan (Per_Cod, Pcs_Cod) SELECT 1, @pcs_deptos WHERE @pcs_deptos IS NOT NULL;

-- ============================================================
-- CONSULTAS DE VERIFICACIÓN
-- ============================================================

-- Verificar directorios creados
SELECT Org_Cod, Org_Des, Org_Niv, Org_Ord, Org_Mod 
FROM organizado 
WHERE Org_Des IN ('Scraper SRI', 'Flujo de Adquisiciones')
ORDER BY Org_Ord;

-- Verificar procesos creados
SELECT p.Pcs_Cod, p.Pcs_Lin, p.Pcs_Nom, p.Pcs_Ord, o.Org_Des as Directorio, r.Rut_Des as Ruta
FROM procesos p
LEFT JOIN organizado o ON p.Org_Cod = o.Org_Cod
LEFT JOIN rutas r ON p.Rut_Cod = r.Rut_Cod
WHERE p.Pcs_Nom IN (
    'scrapers.php',
    'adq_bandeja.php', 'adq_solicitud.php', 'adq_lista_solicitud.php',
    'adq_dashboard.php', 'adq_seguimiento.php', 'adq_configuracion.php',
    'adq_departamentos.php'
)
ORDER BY o.Org_Ord, p.Pcs_Ord;

-- Verificar asignaciones al perfil Administrador
SELECT pf.Per_Cod, p.Pcs_Lin, p.Pcs_Nom
FROM perfiorgan pf
JOIN procesos p ON pf.Pcs_Cod = p.Pcs_Cod
WHERE p.Pcs_Nom IN (
    'scrapers.php',
    'adq_bandeja.php', 'adq_solicitud.php', 'adq_lista_solicitud.php',
    'adq_dashboard.php', 'adq_seguimiento.php', 'adq_configuracion.php',
    'adq_departamentos.php'
)
AND pf.Per_Cod = 1;
