-- Tablas básicas para el sistema contable

-- Tabla de empresas
CREATE TABLE IF NOT EXISTS empresas (
  Emp_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Emp_Nom VARCHAR(255) NOT NULL,
  Emp_Cor VARCHAR(100),
  Emp_Est CHAR(1) DEFAULT 'A'
);

-- Tabla de sucursales
CREATE TABLE IF NOT EXISTS sucursal (
  Suc_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Suc_Des VARCHAR(255),
  Emp_Cod INT,
  FOREIGN KEY (Emp_Cod) REFERENCES empresas(Emp_Cod)
);

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
  Usu_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Usu_Ced VARCHAR(20),
  Usu_Nom VARCHAR(255),
  Suc_Cod INT,
  Usu_Est CHAR(1) DEFAULT 'A',
  FOREIGN KEY (Suc_Cod) REFERENCES sucursal(Suc_Cod)
);

-- Tabla de accesos
CREATE TABLE IF NOT EXISTS access (
  Acc_Cod INT PRIMARY KEY AUTO_INCREMENT,
  Acc_Usr VARCHAR(100),
  Suc_Cod INT,
  Dat_Cod INT,
  Acc_Est CHAR(1) DEFAULT 'A',
  FOREIGN KEY (Suc_Cod) REFERENCES sucursal(Suc_Cod)
);

-- Insertar datos de prueba
INSERT IGNORE INTO empresas (Emp_Cod, Emp_Nom, Emp_Cor, Emp_Est) 
VALUES (1, 'Empresa Prueba', 'EMP001', 'A');

INSERT IGNORE INTO sucursal (Suc_Cod, Suc_Des, Emp_Cod) 
VALUES (1, 'Sucursal Principal', 1);

INSERT IGNORE INTO usuarios (Usu_Cod, Usu_Ced, Usu_Nom, Suc_Cod, Usu_Est) 
VALUES (1, '1234567890', 'Admin', 1, 'A');

INSERT IGNORE INTO access (Acc_Cod, Acc_Usr, Suc_Cod, Dat_Cod, Acc_Est) 
VALUES (1, '1234567890', 1, 1, 'A');
