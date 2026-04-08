/*
Created: 03/09/2011
Modified: 30/05/2018
Project: Exa by Ofsercont
Model: Exa
Company: CorproInfo S.A.
Author: Lewis Chimarro
Version: 1.5
Database: MySQL 5.0
*/




-- Create tables section -------------------------------------------------

-- Table mesclas

CREATE TABLE mesclas
(
  Bam_Cod Bigint
 COMMENT 'marca de banano, solo para tipo cajas',
  Mes_Cod Bigint NOT NULL AUTO_INCREMENT,
  Pro_Cod Bigint NOT NULL
 COMMENT 'Producto resultado de la mescla',
  Mes_Nom Varchar(32) NOT NULL
 COMMENT 'Nombre de la Mescla.',
  Mes_Des Text
 COMMENT 'Descripcion de la mescla.',
  Mes_Res Double NOT NULL DEFAULT 1
 COMMENT 'Guarda el total resultante del producto.',
  Mes_Max Smallint NOT NULL
 COMMENT 'Cantidad Maxima por Lote',
  Mes_Par Char(1) NOT NULL DEFAULT 'N'
 COMMENT 'Para Saber si la produccion tiene paradas.
S=Si, N=No',
  Mes_Tip Char(1) NOT NULL DEFAULT 'P'
 COMMENT 'Tipo de Mescla. P=Produccion, O=Otros, C=Cajas, M=Material Chico.',
  Mes_Est Char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (Mes_Cod)
) ENGINE = InnoDB
 COMMENT = 'Guarda las Productos Terminados y sus Formulas de produccion.'
;

CREATE INDEX IX_MarcaBamMescla ON mesclas (Bam_Cod)
;

-- Table mesclas_det

CREATE TABLE mesclas_det
(
  Mes_Cod Bigint NOT NULL,
  Mes_Int Int NOT NULL DEFAULT 1,
  Pro_Cod Bigint NOT NULL,
  Mes_Can Double NOT NULL
) ENGINE = InnoDB
 COMMENT = 'Detalle de la Formula de Produccion.'
;

ALTER TABLE mesclas_det ADD  PRIMARY KEY (Mes_Int,Mes_Cod,Pro_Cod)
;

-- Table acopio

CREATE TABLE acopio
(
  Aco_Cod Bigint NOT NULL AUTO_INCREMENT,
  Suc_Cod Bigint NOT NULL,
  Act_Tip Char(2) NOT NULL
 COMMENT 'Tipo de Acopio',
  Prd_Cod Bigint
 COMMENT 'Key opcional, para los productores.',
  Aco_Des Varchar(32) NOT NULL,
  Aco_Est Char(1) DEFAULT 'A',
  Aco_Def Char(1) NOT NULL DEFAULT 'N'
 COMMENT 'Default',
  Aco_Tip Char(1)
 COMMENT 'P=Produccion
V=Venta',
  PRIMARY KEY (Aco_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_SucursalAcopio ON acopio (Suc_Cod)
;

CREATE INDEX IX_TipoAcopio ON acopio (Act_Tip)
;

CREATE INDEX IX_ProductorAcoio ON acopio (Prd_Cod)
;

-- Table productor_haci

CREATE TABLE productor_haci
(
  Prh_Cod Bigint NOT NULL AUTO_INCREMENT,
  Prd_Cod Bigint NOT NULL
 COMMENT 'Codigo de Productor',
  Prh_Nom Varchar(64) NOT NULL
 COMMENT 'Nombre de la Hacienda',
  Prh_Mag Varchar(20)
 COMMENT 'Codigo Magap',
  Prh_Inm Varchar(10)
 COMMENT 'Inscripcion en el Magap',
  Prh_Hec Smallint
 COMMENT 'Hectareas',
  Prh_Dir Text
 COMMENT 'Direccion de la hacienda',
  Prh_Est Char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (Prh_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_Prh_Cod ON productor_haci (Prd_Cod)
;

-- Table productor_bana

CREATE TABLE productor_bana
(
  Prd_Cod Bigint NOT NULL AUTO_INCREMENT,
  Prv_Cod Bigint NOT NULL
 COMMENT 'Codigo de productor como proveedor',
  Prd_Cup Int NOT NULL DEFAULT 0
 COMMENT 'Cupo de Cajas de Fruta',
  Prd_Est Char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (Prd_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_Prv_Cod ON productor_bana (Prv_Cod)
;

-- Table productor_tarja

CREATE TABLE productor_tarja
(
  Prt_Cod Bigint NOT NULL AUTO_INCREMENT,
  Prh_Cod Bigint NOT NULL
 COMMENT 'Codigo de hacienda de productor',
  Bam_Cod Bigint NOT NULL
 COMMENT 'Marca de la Caja de Banano',
  Exc_Cod Bigint
 COMMENT 'Codigo del Container en el buque',
  Lib_Cod Bigint
 COMMENT 'Codigo Liquidacion de Fruta',
  Prt_Ano Varchar(32) NOT NULL
 COMMENT 'Anio de la Tarja',
  Prt_Num Bigint NOT NULL
 COMMENT 'Numero de Secuencia de la tarja.',
  Prt_Sem Tinyint NOT NULL
 COMMENT 'Numero de Semana. Del 1 al 52/53.',
  Prt_Grp Varchar(32)
 COMMENT 'Grupo.',
  Prt_Fec Date NOT NULL
 COMMENT 'Fecha de la tarja',
  Prt_Hoe Time
 COMMENT 'Hora Entrada.',
  Prt_Hos Time
 COMMENT 'Hora Salida.',
  Prt_Nqc Varchar(16)
 COMMENT 'Numero QC',
  Prt_Por Decimal(6,2) NOT NULL DEFAULT 100.00
 COMMENT 'Porcentaje de calidad',
  Prt_Obs Text NOT NULL DEFAULT ''
 COMMENT 'Observacion',
  Prt_Cam Text
 COMMENT 'Placas Tansportista, y cantidad entregada, Se un valor compuesto json.',
  Prt_Cad Smallint NOT NULL
 COMMENT 'Cajas Declaradas',
  Prt_Car Smallint NOT NULL
 COMMENT 'Cajas Recibidas.',
  Prt_Cah Smallint NOT NULL DEFAULT 0
 COMMENT 'Cajas Rechazadas',
  Prt_Caf Smallint NOT NULL DEFAULT 0
 COMMENT 'Cajas Faltantes',
  Prt_Caj Smallint NOT NULL DEFAULT 0
 COMMENT 'cajas caidas.',
  Prt_Est Char(1) NOT NULL DEFAULT 'A',
  Prt_Sys Timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Prt_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_HaciTarja ON productor_tarja (Prh_Cod)
;

CREATE INDEX IX_Liqui_Tarja ON productor_tarja (Lib_Cod)
;

CREATE INDEX IX_Marca_Bana ON productor_tarja (Bam_Cod)
;

CREATE INDEX IX_ContainerTarja ON productor_tarja (Exc_Cod)
;

-- Table liquidacion_bana

CREATE TABLE liquidacion_bana
(
  Lib_Cod Bigint NOT NULL AUTO_INCREMENT,
  Prd_Cod Bigint NOT NULL
 COMMENT 'Productor',
  Bam_Cod Bigint NOT NULL
 COMMENT 'marca del la caja de fruta',
  Cop_Cod Bigint
 COMMENT 'Compra',
  Lib_Num Bigint NOT NULL
 COMMENT 'Numero de Liquidacion',
  Lib_Int Smallint NOT NULL DEFAULT 1
 COMMENT 'Orden para saber cual liquidacion se hizo primero y recalcular los valores.',
  Lib_Ano Char(4) NOT NULL
 COMMENT 'Anio de la liquidacion',
  Lib_Sem Tinyint NOT NULL
 COMMENT 'Numero de Semana. Del 1 al 52/53.',
  Lib_Fec Date NOT NULL
 COMMENT 'fecha de la liquidacion',
  Lib_Obs Text
 COMMENT 'Observacion',
  Lib_Cin Bigint NOT NULL DEFAULT 0
 COMMENT 'Inicio offset de las cajas de banano para los calculos de la retencion.',
  Lib_Caj Bigint NOT NULL
 COMMENT 'Cajas de la liquidacion',
  Lib_Pru Decimal(12,4) NOT NULL
 COMMENT 'Precio Unitario de la caja de Fruta',
  Lib_Est Char(1) DEFAULT 'A',
  Lib_Sys Timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (Lib_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_Bana_Liquidacion ON liquidacion_bana (Prd_Cod)
;

CREATE INDEX IX_CompraLiq ON liquidacion_bana (Cop_Cod)
;

CREATE INDEX IX_MarcaLiquidacion ON liquidacion_bana (Bam_Cod)
;

-- Table liquidacion_bana_det

CREATE TABLE liquidacion_bana_det
(
  Lib_Cod Bigint NOT NULL,
  Lid_Tip Char(1) NOT NULL
 COMMENT 'Tipo de registro, I=Ingreso, D=Descuentos',
  Lid_Grp Tinyint NOT NULL
 COMMENT 'Codigo de Grupo, -1 Tablas Huerfanas, 0=Cartones, 1=Materia Chico, 2=Material Chico 2',
  Lid_Int Tinyint NOT NULL
 COMMENT 'Codigo de Detalle',
  Pro_Cod Bigint
 COMMENT 'Codigo del Producto en detalle',
  Lid_Des Varchar(64) NOT NULL
 COMMENT 'Descripcion del Producto',
  Lid_Can Decimal(10,2) NOT NULL
 COMMENT 'Cantidad',
  Lid_Pru Decimal(14,4) NOT NULL
 COMMENT 'Precio Unitario',
  Lid_Imp Decimal(14,2) NOT NULL
 COMMENT 'Importe Total'
) ENGINE = InnoDB
;

CREATE INDEX IX_ProdLiqui ON liquidacion_bana_det (Pro_Cod)
;

ALTER TABLE liquidacion_bana_det ADD  PRIMARY KEY (Lib_Cod,Lid_Tip,Lid_Grp,Lid_Int)
;

-- Table banano_marca

CREATE TABLE banano_marca
(
  Bam_Cod Bigint NOT NULL AUTO_INCREMENT,
  Emp_Cod Bigint,
  Bam_Nom Varchar(32) NOT NULL
 COMMENT 'Nombre de la Marca',
  Bam_Des Text
 COMMENT 'Descripcion de la marca',
  Bam_Tam Varchar(10)
 COMMENT 'Descripcion del tipo de caja. Ejem, 10X22',
  Bam_Est Char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (Bam_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_EmpresaMarcaCaja ON banano_marca (Emp_Cod)
;

-- Table productor_det_plan

CREATE TABLE productor_det_plan
(
  Pld_Cod Bigint NOT NULL,
  Prd_Cod Bigint NOT NULL,
  Prp_Tip Char(2) NOT NULL
 COMMENT 'Tipo de relacion, CC=Ctas x cobrar, IN=Inventario'
) ENGINE = InnoDB
;

ALTER TABLE productor_det_plan ADD  PRIMARY KEY (Pld_Cod,Prd_Cod,Prp_Tip)
;

-- Table exportacion_container

CREATE TABLE exportacion_container
(
  Exc_Cod Bigint NOT NULL AUTO_INCREMENT,
  Emp_Cod Bigint NOT NULL,
  Exc_Ano Char(4) NOT NULL
 COMMENT 'Anio del Periodo',
  Exc_Sem Tinyint NOT NULL
 COMMENT 'Semana de Embarque, ejemplo 1 al 52',
  Exc_Fec Date
 COMMENT 'Fecha del Embarque',
  Exc_Vap Varchar(64) NOT NULL
 COMMENT 'Nombre de la nave, buque, vapor.',
  Exc_Con Varchar(32) NOT NULL
 COMMENT 'Container',
  Exc_Ter Varchar(32)
 COMMENT 'Term',
  Exc_Can Varchar(32)
 COMMENT 'Can',
  Exc_Bod Varchar(32)
 COMMENT 'Bodega/Piso',
  Exc_Pto Varchar(32)
 COMMENT 'Puerto Maritimo',
  Exc_Zon Varchar(32)
 COMMENT 'Ciudad/Zona',
  Exc_Obs Text
 COMMENT 'Observacion',
  Exc_Cho Varchar(64)
 COMMENT 'Chofer Encargado',
  Exc_Pla Varchar(10)
 COMMENT 'Placa Chofer Encargado',
  Exc_Aco Varchar(32)
 COMMENT 'Acopio de Cajas',
  Exc_Est Char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (Exc_Cod)
) ENGINE = InnoDB
;

CREATE INDEX IX_EmpresaContainer ON exportacion_container (Emp_Cod)
;

-- Table productor_tarja_det

CREATE TABLE productor_tarja_det
(
  Prt_Cod Bigint NOT NULL,
  Pro_Cod Bigint NOT NULL,
  Ptd_Can Int NOT NULL
) ENGINE = InnoDB
;

ALTER TABLE productor_tarja_det ADD  PRIMARY KEY (Prt_Cod,Pro_Cod)
;

-- Create relationships section ------------------------------------------------- 

ALTER TABLE mesclas ADD CONSTRAINT prod_mesc FOREIGN KEY (Pro_Cod) REFERENCES producto (Pro_Cod) ON DELETE CASCADE ON UPDATE RESTRICT
;

ALTER TABLE mesclas_det ADD CONSTRAINT mesc_deta FOREIGN KEY (Mes_Cod) REFERENCES mesclas (Mes_Cod) ON DELETE CASCADE ON UPDATE RESTRICT
;

ALTER TABLE mesclas_det ADD CONSTRAINT prod_mate_mesc FOREIGN KEY (Pro_Cod) REFERENCES producto (Pro_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_bana ADD CONSTRAINT prov_productor FOREIGN KEY (Prv_Cod) REFERENCES proveedore (Prv_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_haci ADD CONSTRAINT prod_haci FOREIGN KEY (Prd_Cod) REFERENCES productor_bana (Prd_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja ADD CONSTRAINT hacien_tarja FOREIGN KEY (Prh_Cod) REFERENCES productor_haci (Prh_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja ADD CONSTRAINT liquidacion_tarja FOREIGN KEY (Lib_Cod) REFERENCES liquidacion_bana (Lib_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE liquidacion_bana ADD CONSTRAINT bana_liqui FOREIGN KEY (Prd_Cod) REFERENCES productor_bana (Prd_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE liquidacion_bana_det ADD CONSTRAINT liquidacion_detalle FOREIGN KEY (Lib_Cod) REFERENCES liquidacion_bana (Lib_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE liquidacion_bana_det ADD CONSTRAINT Producto_Liquidacion FOREIGN KEY (Pro_Cod) REFERENCES producto (Pro_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE liquidacion_bana ADD CONSTRAINT Compra_Liquidacion FOREIGN KEY (Cop_Cod) REFERENCES compras (Cop_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE acopio ADD CONSTRAINT producor_acopio FOREIGN KEY (Prd_Cod) REFERENCES productor_bana (Prd_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE banano_marca ADD CONSTRAINT empresa_marca_ban FOREIGN KEY (Emp_Cod) REFERENCES empresas (Emp_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja ADD CONSTRAINT marc_ban_tarja FOREIGN KEY (Bam_Cod) REFERENCES banano_marca (Bam_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_det_plan ADD CONSTRAINT cuenta_productor FOREIGN KEY (Pld_Cod) REFERENCES det_plan (Pld_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_det_plan ADD CONSTRAINT productor_cuentas FOREIGN KEY (Prd_Cod) REFERENCES productor_bana (Prd_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE liquidacion_bana ADD CONSTRAINT MarcaBamLiquidacion FOREIGN KEY (Bam_Cod) REFERENCES banano_marca (Bam_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE mesclas ADD CONSTRAINT MarcaBanMescla FOREIGN KEY (Bam_Cod) REFERENCES banano_marca (Bam_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja ADD CONSTRAINT container_tarja FOREIGN KEY (Exc_Cod) REFERENCES exportacion_container (Exc_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja_det ADD CONSTRAINT tarja_producto FOREIGN KEY (Prt_Cod) REFERENCES productor_tarja (Prt_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE productor_tarja_det ADD CONSTRAINT producto_tarja FOREIGN KEY (Pro_Cod) REFERENCES producto (Pro_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;

ALTER TABLE exportacion_container ADD CONSTRAINT empresa_container_expor FOREIGN KEY (Emp_Cod) REFERENCES empresas (Emp_Cod) ON DELETE RESTRICT ON UPDATE RESTRICT
;


