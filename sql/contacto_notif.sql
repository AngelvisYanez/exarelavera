-- ============================================================
-- Directorio curado de contactos autorizados para notificaciones
-- Caso: integración ERP Locator (consulta nocturna de contactos)
-- ============================================================
-- Ejecutar en la base distribuida de la empresa Ecoparkmining.
-- Compatible con MariaDB 5.5+ / MySQL 5.5+ (sin funciones de ventana).
-- Script idempotente: solo inserta contactos nuevos (por celular).
-- ============================================================
-- EMPRESA (ajusta según la master si cambia el código):
--   Producción: ECOPARKMINING S.A. -> Emp_Cod 620 (Bdd ecoparkmining)
--   Dev (local): ECOPARKMINING SA -> Emp_Cod 281 (Bdd servicios)
SET @emp_cod := 620;

CREATE TABLE IF NOT EXISTS contacto_notif (
  Cnt_Cod INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Identificador único y estable del contacto',
  Cnt_Nom VARCHAR(120) NOT NULL COMMENT 'Nombres',
  Cnt_Ape VARCHAR(120) NOT NULL DEFAULT '' COMMENT 'Apellidos',
  Cnt_Cor VARCHAR(160) DEFAULT NULL COMMENT 'Correo electrónico válido',
  Cnt_Cel VARCHAR(30) NOT NULL COMMENT 'Móvil WhatsApp (+5939XXXXXXXX o 09XXXXXXXX; el endpoint normaliza)',
  Cnt_Car VARCHAR(120) DEFAULT NULL COMMENT 'Cargo',
  Cnt_Are VARCHAR(120) DEFAULT NULL COMMENT 'Área / planta',
  Cnt_Cli VARCHAR(100) NOT NULL DEFAULT 'ecoparkmining' COMMENT 'Cliente/proyecto al que pertenece el contacto',
  Cnt_Est CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'Estado: A activo / I inactivo',
  Emp_Cod INT NOT NULL DEFAULT 1 COMMENT 'Código de empresa',
  Cnt_Fec_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  Cnt_Fec_Mod DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_emp_est (Emp_Cod, Cnt_Est),
  KEY idx_cli (Cnt_Cli)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Contactos autorizados para notificaciones (integración ERP Locator)';

-- ------------------------------------------------------------------
-- Seed (opcional, una sola vez en la puesta en marcha)
-- Deriva contactos iniciales desde los administradores/responsables de
-- planta vigentes (manifiesto_personal_planta) de cada planta activa.
-- Una fila por celular (se conserva la planta de menor Pla_Cod).
-- El directorio pasa a ser administrado manualmente después del seed.
-- ------------------------------------------------------------------
INSERT INTO contacto_notif (Cnt_Nom, Cnt_Ape, Cnt_Cor, Cnt_Cel, Cnt_Car, Cnt_Are, Cnt_Cli, Cnt_Est, Emp_Cod)
SELECT
    p.Prs_Nom,
    p.Prs_Ape,
    COALESCE(NULLIF(TRIM(mpp.Pep_Cor), ''), p.Prs_Cor) AS Cnt_Cor,
    REPLACE(COALESCE(NULLIF(TRIM(mpp.Pep_Tel), ''), p.Prs_Cel, p.Prs_Tel), ' ', '') AS Cnt_Cel,
    CASE mpp.Pep_Tip
        WHEN 'AP' THEN 'Administrador(a) de planta'
        WHEN 'AC' THEN 'Contador(a) de planta'
        WHEN 'AM' THEN 'Responsable ambiental de planta'
        ELSE 'Personal de planta'
    END AS Cnt_Car,
    mp.Pla_Nom AS Cnt_Are,
    'ecoparkmining' AS Cnt_Cli,
    'A' AS Cnt_Est,
    @emp_cod AS Emp_Cod
FROM manifiesto_personal_planta mpp
JOIN persona p ON p.Prs_Cod = mpp.Prs_Cod
JOIN manifiesto_plantas mp ON mp.Pla_Cod = mpp.Pla_Cod
WHERE mpp.Pep_Est = 'A'
  AND mp.Pla_Est = 'A'
  AND COALESCE(NULLIF(TRIM(mpp.Pep_Tel), ''), p.Prs_Cel, p.Prs_Tel) IS NOT NULL
  AND TRIM(COALESCE(NULLIF(TRIM(mpp.Pep_Tel), ''), p.Prs_Cel, p.Prs_Tel)) <> ''
  AND NOT EXISTS (
      SELECT 1 FROM contacto_notif cn
      WHERE cn.Cnt_Cel = REPLACE(COALESCE(NULLIF(TRIM(mpp.Pep_Tel), ''), p.Prs_Cel, p.Prs_Tel), ' ', '')
  );

-- Deduplicación dentro de esta misma corrida: si una misma persona
-- quedó repetida por celular (varias plantas), se conserva una sola
-- fila (la de menor Cnt_Cod, es decir la planta más antigua).
DELETE cn FROM contacto_notif cn
JOIN (
    SELECT Cnt_Cel, MIN(Cnt_Cod) AS conservar
      FROM contacto_notif
     WHERE Emp_Cod = @emp_cod
     GROUP BY Cnt_Cel
) k ON k.Cnt_Cel = cn.Cnt_Cel AND cn.Cnt_Cod <> k.conservar
WHERE cn.Emp_Cod = @emp_cod;

-- Verificación
SELECT Cnt_Cod, Cnt_Nom, Cnt_Ape, Cnt_Cor, Cnt_Cel, Cnt_Car, Cnt_Are, Cnt_Cli, Cnt_Est, Emp_Cod
FROM contacto_notif ORDER BY Cnt_Cod;