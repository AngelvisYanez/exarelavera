-- ============================================================
-- Tabla de jobs de descarga masiva SRI
-- Ejecutar en la base de datos del proyecto
-- ============================================================

CREATE TABLE IF NOT EXISTS sri_scraper_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruc VARCHAR(13) NOT NULL,
    fecha_desde DATE NOT NULL,
    fecha_hasta DATE NOT NULL,
    tipo_comprobante VARCHAR(10) DEFAULT 'todos',
    flow ENUM('recibidos','emitidos') DEFAULT 'recibidos',
    status ENUM('pending','running','completed','failed','cancelled') DEFAULT 'pending',
    progress_msg TEXT,
    total_found INT DEFAULT 0,
    xmls_downloaded INT DEFAULT 0,
    pdfs_downloaded INT DEFAULT 0,
    output_dir VARCHAR(500),
    pid INT DEFAULT NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    error TEXT NULL,
    created_by VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ruc (ruc),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
