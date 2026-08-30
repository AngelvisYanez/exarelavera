-- ============================================================
-- Extensión de la tabla directorio_modulos para módulos externos
-- ============================================================
-- Soporta módulos Node.js y Python para automatizaciones
-- Ejecutar después de directorio_modulos.sql
-- ============================================================

-- Agregar campos para módulos externos
ALTER TABLE directorio_modulos
  ADD COLUMN Dir_Ext_Tip VARCHAR(20) DEFAULT NULL COMMENT 'Tipo de ejecutor externo: node, python, shell, docker' AFTER Dir_Tip,
  ADD COLUMN Dir_Ext_Cmd VARCHAR(500) DEFAULT NULL COMMENT 'Comando para ejecutar el módulo externo' AFTER Dir_Ext_Tip,
  ADD COLUMN Dir_Ext_Args TEXT DEFAULT NULL COMMENT 'Argumentos JSON para el comando externo' AFTER Dir_Ext_Cmd,
  ADD COLUMN Dir_Ext_Cwd VARCHAR(500) DEFAULT NULL COMMENT 'Directorio de trabajo del módulo externo' AFTER Dir_Ext_Args,
  ADD COLUMN Dir_Ext_Env TEXT DEFAULT NULL COMMENT 'Variables de entorno JSON para el módulo externo' AFTER Dir_Ext_Cwd,
  ADD COLUMN Dir_Ext_Port INT DEFAULT NULL COMMENT 'Puerto del servicio externo (si aplica)' AFTER Dir_Ext_Env,
  ADD COLUMN Dir_Ext_Status VARCHAR(20) DEFAULT 'stopped' COMMENT 'Estado del servicio: running, stopped, error, starting' AFTER Dir_Ext_Port,
  ADD COLUMN Dir_Ext_Pid INT DEFAULT NULL COMMENT 'PID del proceso en ejecución' AFTER Dir_Ext_Status,
  ADD COLUMN Dir_Ext_Last_Run DATETIME DEFAULT NULL COMMENT 'Última ejecución del módulo' AFTER Dir_Ext_Pid,
  ADD COLUMN Dir_Ext_Timeout INT DEFAULT 300 COMMENT 'Timeout en segundos para ejecuciones' AFTER Dir_Ext_Last_Run,
  ADD COLUMN Dir_Ext_Max_Retries INT DEFAULT 3 COMMENT 'Número máximo de reintentos' AFTER Dir_Ext_Timeout,
  ADD COLUMN Dir_Ext_Auto_Start TINYINT(1) DEFAULT 0 COMMENT 'Iniciar automáticamente al arrancar el sistema' AFTER Dir_Ext_Max_Retries;

-- Crear tabla para logs de ejecución de módulos externos
CREATE TABLE IF NOT EXISTS directorio_modulos_log (
  Log_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Dir_Cod INT NOT NULL COMMENT 'Código del módulo directorio',
  Log_Fecha_Inicio DATETIME NOT NULL COMMENT 'Fecha y hora de inicio de ejecución',
  Log_Fecha_Fin DATETIME DEFAULT NULL COMMENT 'Fecha y hora de fin de ejecución',
  Log_Duracion_Seg INT DEFAULT NULL COMMENT 'Duración en segundos',
  Log_Estado VARCHAR(20) DEFAULT 'running' COMMENT 'Estado: running, completed, failed, timeout, killed',
  Log_Salida_Stdout TEXT DEFAULT NULL COMMENT 'Salida estándar del proceso',
  Log_Salida_Stderr TEXT DEFAULT NULL COMMENT 'Salida de error del proceso',
  Log_Error_Msg TEXT DEFAULT NULL COMMENT 'Mensaje de error si falló',
  Log_Parametros TEXT DEFAULT NULL COMMENT 'Parámetros de entrada JSON',
  Log_Resultado TEXT DEFAULT NULL COMMENT 'Resultado de la ejecución',
  Log_Creado_Por INT DEFAULT NULL COMMENT 'Usuario que ejecutó',
  KEY idx_dir_fecha (Dir_Cod, Log_Fecha_Inicio),
  KEY idx_estado (Log_Estado),
  FOREIGN KEY (Dir_Cod) REFERENCES directorio_modulos(Dir_Cod) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear tabla para configuración de módulos externos por empresa
CREATE TABLE IF NOT EXISTS directorio_modulos_config (
  Cfg_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Dir_Cod INT NOT NULL,
  Emp_Cod INT NOT NULL,
  Cfg_Clave VARCHAR(100) NOT NULL COMMENT 'Nombre de la configuración',
  Cfg_Valor TEXT DEFAULT NULL COMMENT 'Valor de la configuración',
  Cfg_Tipo VARCHAR(20) DEFAULT 'string' COMMENT 'Tipo: string, number, boolean, json, secret',
  Cfg_Descripcion TEXT DEFAULT NULL COMMENT 'Descripción de la configuración',
  Cfg_Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  Cfg_Fecha_Modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_dir_emp_cfg (Dir_Cod, Emp_Cod, Cfg_Clave),
  KEY idx_emp (Emp_Cod),
  FOREIGN KEY (Dir_Cod) REFERENCES directorio_modulos(Dir_Cod) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar módulos de ejemplo para automatizaciones con Node.js y Python
INSERT INTO directorio_modulos (
  Dir_Nom, Dir_Rut, Dir_Tip, Dir_Ext_Tip, Dir_Ext_Cmd, Dir_Ext_Args, Dir_Ext_Cwd,
  Dir_Est, Dir_Des, Dir_Ver, Dir_Aut, Emp_Cod, Dir_Ext_Port, Dir_Ext_Timeout, Dir_Ext_Auto_Start
) VALUES
-- Módulo Python: Scraper SRI (ya existe pero se configura como externo)
('Scraper SRI Enhanced', '/scrapers/', 'externo', 'python', 'python', '["sri_scraper.py", "--params-file", "{params_file}", "--output-dir", "{output_dir}"]', '/scrapers/', 'A', 'Descarga masiva de comprobantes del SRI usando Python', '2.0.0', 'S', 1, NULL, 600, 0),

-- Módulo Node.js: Automatización de procesamiento de documentos
('Procesador Docs Node', '/automations/doc-processor/', 'externo', 'node', 'node', '["server.js"]', '/automations/doc-processor/', 'A', 'Procesador de documentos con Node.js para automatización', '1.0.0', 'S', 1, 3001, 120, 0),

-- Módulo Python: Análisis de datos contables
('Analisis Contable Python', '/automations/accounting-analysis/', 'externo', 'python', 'python', '["main.py", "--config", "{config_file}"]', '/automations/accounting-analysis/', 'A', 'Análisis contable automatizado con Python', '1.0.0', 'S', 1, NULL, 300, 0),

-- Módulo Node.js: Notificaciones automatizadas
('Notificaciones Auto', '/automations/notifications/', 'externo', 'node', 'node', '["index.js"]', '/automations/notifications/', 'A', 'Sistema de notificaciones automatizadas con Node.js', '1.0.0', 'S', 1, 3002, 60, 0),

-- Módulo Python: Scraping de proveedores
('Scraper Proveedores', '/automations/supplier-scraper/', 'externo', 'python', 'python', '["scraper.py", "--empresa", "{emp_id}"]', '/automations/supplier-scraper/', 'A', 'Scraping automatizado de catálogos de proveedores', '1.0.0', 'S', 1, NULL, 300, 0),

-- Módulo Node.js: Generación de reportes automatizada
('Reportes Auto Node', '/automations/report-generator/', 'externo', 'node', 'node', '["generator.js", "--template", "{template}"]', '/automations/report-generator/', 'A', 'Generador automático de reportes con Node.js', '1.0.0', 'S', 1, 3003, 180, 0),

-- Módulo Python: Conciliación bancaria automática
('Conciliacion Bancaria', '/automations/bank-reconciliation/', 'externo', 'python', 'python', '["reconcile.py", "--cuenta", "{account_id}"]', '/automations/bank-reconciliation/', 'A', 'Conciliación bancaria automatizada con Python', '1.0.0', 'S', 1, NULL, 300, 0);

-- Insertar configuraciones de ejemplo para el módulo Scraper SRI
INSERT INTO directorio_modulos_config (Dir_Cod, Emp_Cod, Cfg_Clave, Cfg_Valor, Cfg_Tipo, Cfg_Descripcion) VALUES
(12, 1, 'sri_ruc_default', '1234567890123', 'string', 'RUC por defecto para el scraper SRI'),
(12, 1, 'sri_max_retries', '3', 'number', 'Número máximo de reintentos en caso de error'),
(12, 1, 'sri_headless', 'true', 'boolean', 'Ejecutar navegador en modo headless'),
(12, 1, 'sri_download_path', '/uploads/sri/', 'string', 'Ruta de descarga de archivos SRI');

-- Insertar configuraciones de ejemplo para el módulo Notificaciones
INSERT INTO directorio_modulos_config (Dir_Cod, Emp_Cod, Cfg_Clave, Cfg_Valor, Cfg_Tipo, Cfg_Descripcion) VALUES
(15, 1, 'smtp_host', 'smtp.gmail.com', 'string', 'Servidor SMTP para notificaciones'),
(15, 1, 'smtp_port', '587', 'number', 'Puerto SMTP'),
(15, 1, 'smtp_user', 'notificaciones@empresa.com', 'string', 'Usuario SMTP'),
(15, 1, 'smtp_pass', '', 'secret', 'Contraseña SMTP (encriptada)'),
(15, 1, 'whatsapp_api_token', '', 'secret', 'Token de API de WhatsApp Business');

-- Verificar estructura extendida
SELECT Dir_Cod, Dir_Nom, Dir_Tip, Dir_Ext_Tip, Dir_Ext_Cmd, Dir_Ext_Port, Dir_Ext_Status 
FROM directorio_modulos 
WHERE Dir_Ext_Tip IS NOT NULL 
ORDER BY Dir_Cod;
