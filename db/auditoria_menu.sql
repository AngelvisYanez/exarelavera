-- Directorio Auditoria + subdirectorio Monitoreo + procesos (idempotente).
-- Ejecutar en la base distribuida de la empresa (ej. exa, servicios).
-- Estructura:
--   Auditoria (Org_Niv=0)
--     Monitoreo (Org_Niv=Auditoria)
--       Consultar            -> aud_con_monitoreo_1.0.php
--       Configurar           -> aud_adm_config_monitoreo_1.0.php
--       Actividad Usuarios   -> aud_con_actividad_usuarios_1.0.php
--       Dashboard Comparativo-> aud_con_dashboard_comparativo_1.0.php

INSERT INTO `rutas` (`Rut_Des`, `Rut_Est`, `Rut_De2`)
SELECT '/auditoria/FRONT/', 'A', '/auditoria/FRONT/'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `rutas` WHERE `Rut_Des` LIKE '%/auditoria/FRONT/%'
);

INSERT INTO `organizado` (`Org_Niv`, `Org_Det`, `Org_Ord`, `Org_Mod`, `Org_Des`, `Org_Img`, `Org_Ime`, `Org_Ico`)
SELECT 0, 'Modulo para consultar y configurar el monitoreo de actividades',
  IFNULL((SELECT MAX(o.Org_Ord) FROM organizado o WHERE o.Org_Niv=0 AND o.Org_Ord < 90), 0) + 1,
  'A', 'Auditoria', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-history'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `organizado` WHERE `Org_Des` = 'Auditoria' AND `Org_Niv` = 0
);

INSERT INTO `organizado` (`Org_Niv`, `Org_Det`, `Org_Ord`, `Org_Mod`, `Org_Des`, `Org_Img`, `Org_Ime`, `Org_Ico`)
SELECT
  (SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1),
  'Consulta y configuracion del monitoreo de actividades de usuarios',
  1, 'A', 'Monitoreo', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-eye'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `organizado`
  WHERE `Org_Des` = 'Monitoreo'
    AND `Org_Niv` = (SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1)
);

-- 1. Consultar monitoreo
INSERT INTO `procesos` (`Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Tpr_Cod`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`)
SELECT
  (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  'Permite consultar las actividades y cambios realizados por los usuarios',
  1, 'Consultar', 'A',
  (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  'aud_con_monitoreo_1.0.php', 1, 'arrow-on.png', 'P', 'fa fa-search', 'N'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `procesos` WHERE `Pcs_Nom` = 'aud_con_monitoreo_1.0.php'
);

UPDATE `procesos` SET
  `Org_Cod` = (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  `Pcs_Lin` = 'Consultar',
  `Pcs_Det` = 'Permite consultar las actividades y cambios realizados por los usuarios',
  `Pcs_Ord` = 1,
  `Pcs_Est` = 'A',
  `Rut_Cod` = (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  `Pcs_Tip` = 'P',
  `Pcs_Ico` = 'fa fa-search'
WHERE `Pcs_Nom` = 'aud_con_monitoreo_1.0.php';

-- 2. Configurar monitoreo
INSERT INTO `procesos` (`Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Tpr_Cod`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`)
SELECT
  (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  'Configura que modulos, directorios y procesos se registran en el monitoreo de actividades',
  2, 'Configurar', 'A',
  (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  'aud_adm_config_monitoreo_1.0.php', 1, 'arrow-on.png', 'P', 'fa fa-cogs', 'N'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `procesos` WHERE `Pcs_Nom` = 'aud_adm_config_monitoreo_1.0.php'
);

UPDATE `procesos` SET
  `Org_Cod` = (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  `Pcs_Lin` = 'Configurar',
  `Pcs_Det` = 'Configura que modulos, directorios y procesos se registran en el monitoreo de actividades',
  `Pcs_Ord` = 2,
  `Pcs_Est` = 'A',
  `Rut_Cod` = (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  `Pcs_Tip` = 'P',
  `Pcs_Ico` = 'fa fa-cogs'
WHERE `Pcs_Nom` = 'aud_adm_config_monitoreo_1.0.php';

-- 3. Actividad de usuarios en vivo y sesiones
INSERT INTO `procesos` (`Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Tpr_Cod`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`)
SELECT
  (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  'Monitor de sesiones activas, tiempo de uso, IP, ubicacion, navegadores y control de inactividad',
  3, 'Actividad de Usuarios', 'A',
  (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  'aud_con_actividad_usuarios_1.0.php', 1, 'arrow-on.png', 'P', 'fa fa-users', 'N'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `procesos` WHERE `Pcs_Nom` = 'aud_con_actividad_usuarios_1.0.php'
);

UPDATE `procesos` SET
  `Org_Cod` = (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  `Pcs_Lin` = 'Actividad de Usuarios',
  `Pcs_Det` = 'Monitor de sesiones activas, tiempo de uso, IP, ubicacion, navegadores y control de inactividad',
  `Pcs_Ord` = 3,
  `Pcs_Est` = 'A',
  `Rut_Cod` = (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  `Pcs_Tip` = 'P',
  `Pcs_Ico` = 'fa fa-users'
WHERE `Pcs_Nom` = 'aud_con_actividad_usuarios_1.0.php';

-- 4. Dashboard estadistico comparativo
INSERT INTO `procesos` (`Org_Cod`, `Pcs_Det`, `Pcs_Ord`, `Pcs_Lin`, `Pcs_Est`, `Rut_Cod`, `Pcs_Nom`, `Tpr_Cod`, `Pcs_Img`, `Pcs_Tip`, `Pcs_Ico`, `Pcs_Int`)
SELECT
  (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  'Dashboard estadistico con comparativa temporal, emision de reporte PDF y envio por correo y WhatsApp',
  4, 'Dashboard Estadístico', 'A',
  (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  'aud_con_dashboard_comparativo_1.0.php', 1, 'arrow-on.png', 'P', 'fa fa-line-chart', 'N'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `procesos` WHERE `Pcs_Nom` = 'aud_con_dashboard_comparativo_1.0.php'
);

UPDATE `procesos` SET
  `Org_Cod` = (SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo'
     AND Org_Niv=(SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1) LIMIT 1),
  `Pcs_Lin` = 'Dashboard Estadístico',
  `Pcs_Det` = 'Dashboard estadistico con comparativa temporal, emision de reporte PDF y envio por correo y WhatsApp',
  `Pcs_Ord` = 4,
  `Pcs_Est` = 'A',
  `Rut_Cod` = (SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1),
  `Pcs_Tip` = 'P',
  `Pcs_Ico` = 'fa fa-line-chart'
WHERE `Pcs_Nom` = 'aud_con_dashboard_comparativo_1.0.php';

-- Asignar a Administrador de Sistemas, Gerente y perfiles de administracion de empresas
INSERT INTO `perfiorgan` (`Per_Cod`, `Pcs_Cod`)
SELECT p.Per_Cod, pr.Pcs_Cod
FROM perfiles p
CROSS JOIN procesos pr
WHERE (p.Per_Des IN ('Administrador de Sistemas', 'Gerente', 'ADMINISTRADOR CAPACITACION VIDEOS') OR p.Per_Cod IN (1130, 1131, 890))
  AND pr.Pcs_Nom IN (
    'aud_con_monitoreo_1.0.php',
    'aud_adm_config_monitoreo_1.0.php',
    'aud_con_actividad_usuarios_1.0.php',
    'aud_con_dashboard_comparativo_1.0.php'
  )
  AND NOT EXISTS (
    SELECT 1 FROM perfiorgan po WHERE po.Per_Cod = p.Per_Cod AND po.Pcs_Cod = pr.Pcs_Cod
  );
