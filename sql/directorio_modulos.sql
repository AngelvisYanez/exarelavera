-- ============================================================
-- Tabla de Directorios de Módulos del sistema ERP
-- ============================================================
-- Ejecutar en la base de datos del proyecto
-- Script idempotente: puede ejecutarse múltiples veces.
-- ============================================================

CREATE TABLE IF NOT EXISTS directorio_modulos (
  Dir_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Dir_Nom VARCHAR(255) NOT NULL,
  Dir_Rut VARCHAR(500) NOT NULL,
  Dir_Tip VARCHAR(50) DEFAULT 'modulo',
  Dir_Est CHAR(1) DEFAULT 'A',
  Dir_Des TEXT,
  Dir_Ver VARCHAR(50),
  Dir_Aut CHAR(1) DEFAULT 'N',
  Emp_Cod INT DEFAULT 1,
  KEY idx_emp_est (Emp_Cod, Dir_Est)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar los módulos existentes del ERP legacy en directorio_modulos
-- Esto permite que el frontend muestre los módulos que ya están registrados

-- Obtener empresa por defecto
SET @emp_def = 1;

-- Módulos base del sistema (basados en los directorios del ERP legacy)
INSERT IGNORE INTO directorio_modulos (Dir_Nom, Dir_Rut, Dir_Tip, Dir_Est, Dir_Des, Dir_Ver, Dir_Aut, Emp_Cod) VALUES
('Tesoreria', '/tesoreria/', 'modulo', 'A', 'Gestión de tesorería y pagos', '1.0.0', 'S', @emp_def),
('Adquisiciones', '/adquisiciones/', 'modulo', 'A', 'Gestión de adquisiciones y proveedores', '1.0.0', 'S', @emp_def),
('Inventario', '/inventario/', 'modulo', 'A', 'Gestión de inventario, categorías, marcas y productos', '1.0.0', 'S', @emp_def),
('Actores', '/dashboard/actores/', 'modulo', 'A', 'Gestión de clientes y proveedores', '1.0.0', 'S', @emp_def),
('Facturación', '/facturacion/', 'modulo', 'A', 'Emisión de facturas y comprobantes', '1.0.0', 'S', @emp_def),
('Contabilidad', '/contabilidad/', 'modulo', 'A', 'Gestión contable y balances', '1.0.0', 'S', @emp_def),
('Administración', '/administrador/', 'modulo', 'A', 'Panel de administración del sistema', '1.0.0', 'S', @emp_def),
('Scraper SRI', '/scrapers/', 'api', 'A', 'Descarga masiva de comprobantes del SRI', '1.0.0', 'S', @emp_def),
('Flujo Adquisiciones', '/flujo/', 'modulo', 'A', 'Flujo de trabajo de adquisiciones', '1.0.0', 'S', @emp_def),
('SRI Scraper', '/dashboard/sri-scraper/', 'api', 'A', 'Descarga masiva de comprobantes del SRI (Frontend)', '1.0.0', 'S', @emp_def),
('Auditorías', '/auditorias/tareas/', 'modulo', 'A', 'Tareas del equipo de auditoría', '1.0.0', 'S', @emp_def);

-- Verificar
SELECT Dir_Cod, Dir_Nom, Dir_Rut, Dir_Tip, Dir_Est FROM directorio_modulos WHERE Emp_Cod = @emp_def ORDER BY Dir_Cod;
