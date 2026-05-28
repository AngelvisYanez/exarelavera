<?php

/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-06-08
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2013-01-08
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_kar($id, $Par_Sql)
{
	switch ($id) {
		case 207:
			/* SENTECIAS UTILILES EN REPORTES PARA CABECERAS */
			/* Consulta que permite cargar el nombre de la empresa a que pertenece el usuario */
			$cabecera_empresa = "SELECT
								 empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des 
							FROM 
								empresas, sucursal, ciudad 
							WHERE 
								empresas.Emp_Cod = sucursal.Emp_Cod AND 
								sucursal.Suc_Cod = $Par_Sql[0] AND 
								sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return  $cabecera_empresa;
			break;

		case 1010:
			/**
			 * Consultar todas las tablas relacionadas con la tablaproducto
			 */
			$sql = "SELECT 
				item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp 
				FROM 
					item,producto,categorias,marca,ubicacion,unidad,adquisicio,iva,precios 
				WHERE 
					item.Cat_Cod=categorias.Cat_Cod AND 
					producto.Ite_Cod=item.Ite_Cod AND 
					producto.Mar_Cod= marca.Mar_Cod AND  
					ubicacion.Ubi_Cod= producto.Ubi_Cod AND  
					unidad.Uni_Cod= producto.Uni_Cod AND  
					adquisicio.Adq_Cod= producto.Adq_Cod AND 
					producto.Iva_Cod=iva.Iva_Cod AND 
					precios.Pro_Cod=producto.Pro_Cod AND 
					precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0]"; //Antes AND precios.Tpv_Cod=1
			return $sql;
			break;
		case 1040:
			/**
			 * Consultar todas las tablas relacionadas con la tablaproducto
			 */
			$sql = "SELECT 
				  item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,				  producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec
				FROM
				  categorias
				  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
				  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
				  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
				  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
				  INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
				  INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
				  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
				WHERE 
				producto.Pro_Bar='$Par_Sql[0]' AND 
				categorias.Emp_Cod = $Par_Sql[1]";
			return $sql;
			break;

		case 1041:
			/**
			 * Consultar todas las tablas relacionadas con la tablaproducto
			 */
			$sql = "SELECT 
				  item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,				  producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec
				FROM
				  categorias
				  INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
				  INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
				  INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
				  INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
				  INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
				  INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
				  INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
				WHERE 
					item.Ite_Lar  LIKE '%$Par_Sql[0]%' AND 
					categorias.Emp_Cod = $Par_Sql[1]";
			//echo $sql;
			return $sql;
			break;
		case 1042:
			/**
			 * Con esta sentencia consulto el movimiento del kardex con fechas 
			 */
			$sql = "SELECT Vet_Cod,Aju_Cod,Cop_Cod,Vnd_Cod,Gia_Cod,Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock
		, (Kar_Ime-Kar_Ims)+(((((Kar_Ime-Kar_Ims)-(((Kar_Ime-Kar_Ims)*Kar_Des)/100)))*Iva_Por)/100) AS Saldo
		, (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_ent
		, (Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100) AS Precio_sal
			FROM 
				kardex_ie,iva 
			WHERE 
				Kar_Est='A' AND 
				Pro_Cod=$Par_Sql[2]  AND 
				Kar_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'AND 
				iva.Iva_Cod=kardex_ie.Iva_Cod 
			ORDER BY Kar_Fec";
			//echo $sql;
			return $sql;
			break;

		case 1043:
			/**
			 * Consulta la cantidad del producto por fecha 0000-00-00 
			 */
			$sql = " SELECT SUM(Kar_Can)-SUM(Kar_Sal) AS Stock FROM kardex_ie  WHERE  Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1]";
			return $sql;
			break;

		case 1044:
			/**
			 * Consulto las ventas para cargar al kardex  
			 */
			$sql = "SELECT  ventas.Vet_Num,Vet_Des,Tic_Des,Vet_Obs FROM ventas,kardex_ie,tipo_compr WHERE  kardex_ie.Vet_Cod=ventas.Vet_Cod AND tipo_compr.Tic_Cod=ventas.Tic_cod AND ventas.Vet_Cod=$Par_Sql[0]";
			return $sql;
			break;

		case 1045:
			/**
			 * Consulto las compras para cargar al kardex  
			 */
			$sql = "SELECT  compras.Cop_Cod,compras.Cop_Num,compras.Cop_Obs FROM compras,kardex_ie,tipo_compr WHERE  kardex_ie.Cop_Cod=compras.Cop_Cod AND tipo_compr.Tic_Cod=compras.Tic_cod AND compras.Cop_Cod=$Par_Sql[0]";
			return $sql;
			break;

		case 1046:
			/**
			 * Consulto los ajustes para cargar al kardex  
			 */
			$sql = "SELECT  ajuste_kar.Aju_Cod,ajuste_kar.Aju_Obs,Tia_Des,Aju_Sec FROM ajuste_kar,kardex_ie,`tipo_ajus` WHERE tipo_ajus.Tia_Cod=ajuste_kar.`Tia_Cod` AND  kardex_ie.Aju_Cod=ajuste_kar.Aju_Cod AND ajuste_kar.Aju_Cod=$Par_Sql[0]";
			return $sql;
			break;

		case 1047:
			/**
			 * Consulta la cantidad del producto por fecha 0000-00-00 
			 */
			$sql = " SELECT (Kar_Ime-Kar_Ims)+((  ((Kar_Ime-Kar_Ims)- (((Kar_Ime-Kar_Ims)*Kar_Des)/100)) *Iva_Por)/100) AS Saldo FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0]' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod";
			return $sql;
			break;

		case 1048:
			/**
			 * Consulta la cantidad del producto por fecha 0000-00-00 
			 */
			$sql = " SELECT (SUM(Kar_Can)- SUM(Kar_Sal)) as Stock, (SUM(Kar_Ims)- SUM(Kar_Ime)) AS Saldo, "
				. "SUM((Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_ent
                        , SUM((Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_sal "
				. "FROM kardex_ie,iva  WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[0] 00:00:00' AND Pro_Cod=$Par_Sql[1] AND iva.Iva_Cod= kardex_ie.Iva_Cod";
			//echo $sql.'<br>';
			return $sql;
			break;

		case 10488:
			/**
			 * Con esta sentencia consulto el movimiento del kardex con fechas 
			 */
			$sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2',''))) AS orden,
                            CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                            IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                            IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,

                            CONCAT(IF(compras.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(ventas.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),' - ',
                            IF(compras.Tic_Cod != 4,(IF(kardex_ie.Cop_Cod!=0, IF(kardex_ie.Kar_Can>=0, '', 'Anulada'), '')), ''),
                            IF(ventas.Tic_Cod != 4,(IF(kardex_ie.Vet_Cod!=0, IF(kardex_ie.Kar_Sal>=0, '', 'Anulada'), '')), ''), ' - ', 
                            IF(kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',Vet_Obs),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
                            kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,kardex_ie.Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock , (Kar_Ims-Kar_Ime) AS Saldo , 
                            
                           Sum(Round(if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'E' or ajuste_kar.Aju_Tip = 'O' or ajuste_kar.Aju_Tip = 'G',det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Sal * ventas_det.Vet_Pru),2)) AS Precio_ent,
                           Sum(Round(if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'I', det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Can * det_compra.Cop_Pru),2)) AS Precio_sal
                            FROM kardex_ie

                            INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
                            LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                            LEFT JOIN det_ajustek ON ajuste_kar.Aju_Cod=det_ajustek.Aju_Cod
                            LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                            LEFT JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                            LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                            LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                            LEFT JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                            LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                            LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
			WHERE 
				Kar_Est='A' AND
        		if(kardex_ie.Vet_Cod!=0, 
				ventas_det.Pro_Cod = $Par_Sql[1], 
				if(kardex_ie.Cop_Cod!=0, 
				det_compra.Pro_Cod =$Par_Sql[1], 
				det_ajustek.Pro_cod = $Par_Sql[1])) AND
				kardex_ie.Pro_Cod=$Par_Sql[1]  AND 
				Kar_Fec < '$Par_Sql[0] 00:00:00'
       		    group by kardex_ie.Kar_Int, kardex_ie.Vet_cod, kardex_ie.Iva_cod, kardex_ie.Aju_cod, 
				kardex_ie.Vnd_Cod, kardex_ie.Pro_cod, 
				kardex_ie.Cop_cod, kardex_ie.Gia_cod
			    ORDER BY Kar_Fec,orden,Kar_Hor";
	
			return $sql;
			break;

		case 104888:
			/**
			 * Consulta el kardex del producto
			 */
			$sql = " SELECT
				  IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
			      Pro_Cod,
			      Kar_Sal,
			      Kar_Pre,
			      Kar_Ime,
			      Kar_Can,
			      Kar_Prs,
			      Kar_Ims,
			      (Kar_Can-Kar_Sal) AS Stock, 
			      (Kar_Ims-Kar_Ime) AS Saldo
			      FROM kardex_ie
						WHERE 
						Kar_Est='A' AND Pro_Cod = $Par_Sql[0]
			      ORDER BY Kar_Fec,orden,Kar_Hor";
			return $sql;
			break;

		case 1050:
			/**
			 * Con esta sentencia consulto el movimiento del kardex con fechas 
			 */
			$sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
                            CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                            IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                            IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,
                            CONCAT(IF(compras.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(ventas.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),
                            IF(compras.Tic_Cod != 4,(IF(kardex_ie.Cop_Cod!=0, IF(kardex_ie.Kar_Can>=0, '', 'Anulada'), '')), ''),
                            IF(ventas.Tic_Cod != 4,(IF(kardex_ie.Vet_Cod!=0, IF(kardex_ie.Kar_Sal>=0, '', 'Anulada'), '')), ''), ' - ', 
                            IF(kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',(TRIM(REPLACE(REPLACE(Vet_Obs, CHAR(13), ''), CHAR(10), ' ')))),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
                            kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,kardex_ie.Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock ,
							
							
							--(Kar_Ims-Kar_Ime) AS Saldo ,                            
							(Kar_Ims-Kar_Ime) AS Saldo,   
							
							Round((kardex_ie.Kar_Sal/count(kardex_ie.Kar_Sal)) * Sum(ventas_det.Vet_Pru),4) AS Precio_ent,
                            /*Sum(Round(
							if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'E' or ajuste_kar.Aju_Tip = 'O' or ajuste_kar.Aju_Tip = 'G',det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Sal * ventas_det.Vet_Pru),4)) AS Precio_ent,*/
						   Sum(Round(if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'I',det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Can * det_compra.Cop_Pru),4)) AS Precio_sal,
                           IF(kardex_ie.Vet_Cod = 0, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom), CONCAT(prs.Prs_Ape, ' ', prs.Prs_Nom)) as Cli_Prv
                            FROM kardex_ie
                            INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
                            LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                            LEFT JOIN det_ajustek ON ajuste_kar.Aju_Cod=det_ajustek.Aju_Cod
                            LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                            LEFT JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
                            LEFT JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                            LEFT JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                            LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                            LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                            LEFT JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                            LEFT JOIN persona as prs ON cliente.Prs_Cod = prs.Prs_Cod
                            LEFT JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                            LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                            LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
			WHERE 
				Kar_Est='A' AND
        		if(kardex_ie.Vet_Cod!=0, ventas_det.Pro_Cod = $Par_Sql[2], if(kardex_ie.Cop_Cod!=0, det_compra.Pro_Cod =$Par_Sql[2], det_ajustek.Pro_cod = $Par_Sql[2])) AND
				kardex_ie.Pro_Cod=$Par_Sql[2]  AND 
				Kar_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59'
       		    group by kardex_ie.Kar_Int, kardex_ie.Vet_cod, kardex_ie.Iva_cod, kardex_ie.Aju_cod, kardex_ie.Vnd_Cod, kardex_ie.Pro_cod, kardex_ie.Cop_cod, kardex_ie.Gia_cod
			    ORDER BY Kar_Fec,orden,Kar_Hor";
			//echo $sql;
			return $sql;


			case 10500:
				/**
				 * Con esta sentencia consulto el movimiento del kardex con fechas 
				 */
				$sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
								CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
								IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
								IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,
								CONCAT(IF(compras.Tic_Cod = 4, 'Devolucion ', ''),
								IF(ventas.Tic_Cod = 4, 'Devolucion ', ''),
								IF(kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),
								IF(compras.Tic_Cod != 4,(IF(kardex_ie.Cop_Cod!=0, IF(kardex_ie.Kar_Can>=0, '', 'Anulada'), '')), ''),
								IF(ventas.Tic_Cod != 4,(IF(kardex_ie.Vet_Cod!=0, IF(kardex_ie.Kar_Sal>=0, '', 'Anulada'), '')), ''), ' - ', 
								IF(kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',(TRIM(REPLACE(REPLACE(Vet_Obs, CHAR(13), ''), CHAR(10), ' ')))),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
								kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,kardex_ie.Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock ,
								
								
								--(Kar_Ims-Kar_Ime) AS Saldo ,                            
								(Kar_Ims-Kar_Ime) AS Saldo,   
								
								Round((kardex_ie.Kar_Sal/count(kardex_ie.Kar_Sal)) * Sum(ventas_det.Vet_Pru),4) AS Precio_ent,
								/*Sum(Round(
								if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'E' or ajuste_kar.Aju_Tip = 'O' or ajuste_kar.Aju_Tip = 'G',det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Sal * ventas_det.Vet_Pru),4)) AS Precio_ent,*/
							   Sum(Round(if(kardex_ie.Aju_Cod!=0, if(ajuste_kar.Aju_Tip = 'I',det_ajustek.Aju_Imp, '0.00'), kardex_ie.Kar_Can * det_compra.Cop_Pru),4)) AS Precio_sal,
							   IF(kardex_ie.Vet_Cod = 0, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom), CONCAT(prs.Prs_Ape, ' ', prs.Prs_Nom)) as Cli_Prv
								FROM kardex_ie
								INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
								LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
								LEFT JOIN det_ajustek ON ajuste_kar.Aju_Cod=det_ajustek.Aju_Cod
								LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
								LEFT JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
								LEFT JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
								LEFT JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
								LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
								LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
								LEFT JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
								LEFT JOIN persona as prs ON cliente.Prs_Cod = prs.Prs_Cod
								LEFT JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
								LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
								LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
								LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
								LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
								LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
				WHERE 
					Kar_Est='A' AND
					if(kardex_ie.Vet_Cod!=0, ventas_det.Pro_Cod = $Par_Sql[2], if(kardex_ie.Cop_Cod!=0, det_compra.Pro_Cod =$Par_Sql[2], det_ajustek.Pro_cod = $Par_Sql[2])) AND
					kardex_ie.Pro_Cod=$Par_Sql[2]  AND 
					Kar_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59'
					group by kardex_ie.Kar_Int, kardex_ie.Vet_cod, kardex_ie.Iva_cod, kardex_ie.Aju_cod, kardex_ie.Vnd_Cod, kardex_ie.Pro_cod, kardex_ie.Cop_cod, kardex_ie.Gia_cod
					ORDER BY Kar_Fec,orden,Kar_Hor
					LIMIT 7000 OFFSET $Par_Sql[4]";
				//echo $sql;
				return $sql;

		case 1051:
			/**
			 * Con esta sentencia consulto producto y stock
			 */
			$sql = "SELECT item.Ite_Cod,Ite_Est,categorias.Cat_Cod,Cat_Des,Ite_Cor,Ite_Lar,marca.Mar_Cod,Mar_Des, CONCAT(Ite_Lar,IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar,'',CAST(CONCAT(' - ',Pro_Obs)AS CHAR) ) )AS Producto , adquisicio.Adq_Cod,Adq_Des, iva.Iva_Cod, iva.Iva_Por,Pro_Bar,Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,Uni_Des,Pro_Obs,producto.Pro_Cod,Pro_Est,Pro_Gen,Pro_Cdc,Pro_Sec,Pro_Uni,Pro_Cdc,Pro_Dsc,Pre_Pvp,Stk_Can
                        FROM producto
                        INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
                        INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
                        INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
                        INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
                        INNER JOIN ubicacion ON ubicacion.Ubi_Cod= producto.Ubi_Cod   
                        INNER JOIN unidad ON unidad.Uni_Cod= producto.Uni_Cod  
                        INNER JOIN adquisicio ON adquisicio.Adq_Cod= producto.Adq_Cod  
                        INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
                        WHERE precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0] AND stock.Suc_Cod=$Par_Sql[1]";
			//echo $sql;
			return $sql;


			case 10511:
				// Obtener los parámetros de paginación
				$pagina = isset($Par_Sql[2]) ? (int)$Par_Sql[2] : 1; // Página actual
				$registrosPorPagina = 10; // Cambia esto según tus necesidades
				$offset = ($pagina - 1) * $registrosPorPagina;
			
				$sql = "SELECT item.Ite_Cod, Ite_Est, categorias.Cat_Cod, Cat_Des, Ite_Cor, Ite_Lar, marca.Mar_Cod, Mar_Des, 
						CONCAT(Ite_Lar, IF(Pro_Obs IS NULL OR TRIM(Pro_Obs)='' OR Pro_Obs=Ite_Lar, '', 
						CAST(CONCAT(' - ', Pro_Obs) AS CHAR)) ) AS Producto, 
						adquisicio.Adq_Cod, Adq_Des, iva.Iva_Cod, iva.Iva_Por, Pro_Bar, Ubi_Des, ubicacion.Ubi_Cod, 
						unidad.Uni_Cod, Uni_Des, Pro_Obs, producto.Pro_Cod, Pro_Est, Pro_Gen, Pro_Cdc, Pro_Sec, 
						Pro_Uni, Pro_Cdc, Pro_Dsc, Pre_Pvp, Stk_Can
						FROM producto
						INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
						INNER JOIN marca ON producto.Mar_Cod=marca.Mar_Cod
						INNER JOIN iva ON producto.Iva_Cod=iva.Iva_Cod
						INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod
						INNER JOIN ubicacion ON ubicacion.Ubi_Cod= producto.Ubi_Cod   
						INNER JOIN unidad ON unidad.Uni_Cod= producto.Uni_Cod  
						INNER JOIN adquisicio ON adquisicio.Adq_Cod= producto.Adq_Cod  
						INNER JOIN precios ON precios.Pro_Cod=producto.Pro_Cod
						INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
						WHERE precios.Pre_Est='A' AND producto.Pro_Cod=$Par_Sql[0] AND stock.Suc_Cod=$Par_Sql[1]
						LIMIT $offset, $registrosPorPagina"; // Agregar LIMIT y OFFSET
				return $sql;

		case 1052:
			/**
			 * Con esta sentencia consulto producto y stock
			 */
			if ($Par_Sql[3] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
			else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,CONCAT(item.Ite_Lar,' - ',item.Ite_Cor)AS Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec ";
			if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
			else {
				$search = "";
				$array = explode(" ", strtoupper($Par_Sql[0]));
				foreach ($array as $ar) {
					if (!empty($ar) && $ar != '') $search .= (($search != '' ? " AND " : "") . "CAST(UPPER(CONCAT(Ite_Lar,Pro_Obs,Ite_Cor )) AS CHAR)LIKE '%$ar%'");
				}
				if ($search == '') $search = "1=1";
			}
			$sql = "SELECT  $campos
                    FROM categorias
                        INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                        INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                        INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                        INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                        INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                        INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                        INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod AND stock.Suc_Cod='$_SESSION[Ses_Suc_Cod]'
					WHERE $search AND Pro_Est='A' AND
					categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[3]";
			//echo $sql;
			return $sql;


		case 1053:
			/**
			 * Con esta sentencia consulto producto y stock
			 */
			if ($Par_Sql[4] == '') $campos = " COUNT(item.Ite_Cod) AS total ";
			else $campos = " item.Ite_Cod,item.Ite_Est,categorias.Cat_Cod,categorias.Cat_Des,item.Ite_Cor,item.Ite_Lar,marca.Mar_Cod,marca.Mar_Des,adquisicio.Adq_Cod,		  adquisicio.Adq_Des,iva.Iva_Cod,iva.Iva_Por,producto.Pro_Bar,ubicacion.Ubi_Des,ubicacion.Ubi_Cod,unidad.Uni_Cod,unidad.Uni_Des,producto.Pro_Obs,producto.Pro_Cod,producto.Pro_Est,producto.Pro_Gen,producto.Pro_Cdc,producto.Pro_Sec ";
			if ($Par_Sql[2] == 'c') $search = " producto.Pro_Bar='$Par_Sql[0]' ";
			else $search = " item.Ite_Lar  LIKE '%$Par_Sql[0]%' ";
			$sql = "SELECT 
                        $campos
                      FROM
                        categorias
                        INNER JOIN item ON (categorias.Cat_Cod = item.Cat_Cod)
                        INNER JOIN producto ON (item.Ite_Cod = producto.Ite_Cod)
                        INNER JOIN marca ON (producto.Mar_Cod = marca.Mar_Cod)
                        INNER JOIN adquisicio ON (producto.Adq_Cod = adquisicio.Adq_Cod)
                        INNER JOIN unidad ON (producto.Uni_Cod = unidad.Uni_Cod)
                        INNER JOIN ubicacion ON (producto.Ubi_Cod = ubicacion.Ubi_Cod)
                        INNER JOIN iva ON (producto.Iva_Cod = iva.Iva_Cod) 
                        INNER JOIN stock ON stock.Pro_Cod=producto.Pro_Cod 
                      WHERE 
                      $search AND Pro_Est='A' AND producto.Pro_Cod!='$Par_Sql[3]' AND
                      categorias.Emp_Cod = $Par_Sql[1] $Par_Sql[4]";
			//echo $sql;
			return $sql;
		case 1054:
			$sql = "SELECT Cop_Fec,CAST(CONCAT(det_compra.Cop_Cod,'_',Cop_Int)AS CHAR) AS Cop_Key,Cop_Num,Pro_Cod,compras.Prv_Cod,proveedore.Prs_Cod,
                                CONCAT(Prs_Ape,' ',Prs_Nom) AS Provee,
                                Cop_Can,Cop_Pru,Cop_Imp,Cop_Des,Cop_Dec,
                                (Cop_Imp*(Cop_Des + Cop_Dec)/100) AS Descuento,Iva_Por,
                                (Cop_Imp-(Cop_Imp*(Cop_Des + Cop_Dec)/100)) AS SubTotal,
                                (Cop_Imp*Iva_Por/100) As Iva,
                                ((Cop_Imp-(Cop_Imp*(Cop_Des + Cop_Dec)/100))+(Cop_Imp*Iva_Por/100)) AS Total,
                                (((Cop_Imp-(Cop_Imp*(Cop_Des + Cop_Dec)/100))+(Cop_Imp*Iva_Por/100))/Cop_Can) AS Unitario,
                                IF(compr_auto.Com_Cod IS NULL,'Ninguno',CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(Com_Fec))=1,CONCAT('0',CAST(MONTH(Com_Fec) AS char)),CAST(MONTH(Com_Fec) AS char)),'-',CAST(Com_Num AS char))) AS Com_Codigo 
                         FROM det_compra
                         INNER JOIN compras ON det_compra.Cop_Cod=compras.Cop_Cod
                         INNER JOIN proveedore ON compras.Prv_Cod=proveedore.Prv_Cod
                         INNER JOIN persona ON persona.Prs_Cod=proveedore.Prs_Cod
                         INNER JOIN iva ON det_compra.Iva_Cod=iva.Iva_Cod
                         LEFT JOIN compr_auto ON (compr_auto.Cop_Cod = compras.Cop_Cod) 
                         LEFT JOIN comprobantes ON (compr_auto.Com_Cod = comprobantes.Com_Cod) 
                         LEFT JOIN tipo_asien ON tipo_asien.Tia_Cod=comprobantes.Tia_Cod
                         
                         WHERE Cop_Est='A'
                                         AND Emp_Cod=$Par_Sql[0] AND Pro_Cod=$Par_Sql[2]
                                 AND Cop_fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'
                         ORDER BY Cop_Fec DESC";
			//echo $sql;
			return $sql;





		case 1055:
			if ($Par_Sql[2] == '' || $Par_Sql[2] == '0') $Par_Sql[2] = '';
			else $Par_Sql[2] = " AND producto.Pro_Cod=$Par_Sql[2]";
			if (empty($Par_Sql[5]))  $Par_Sql[5] = '';
			$sql = "SELECT 
                                Prs_Ape, Prs_Nom,Caj_Fec,CAST(CONCAT(ventas_det.Vet_Cod,'_',ventas_det.Pro_Cod,'_',Vet_Ite)AS CHAR) AS Vet_Key,
								CONCAT(Suc_Sri,'-',Pun_Sri,'-',
								CAST(LPAD(Vet_Num,9,'0')AS char)) AS Vet_Num,
                            Vet_Can,Vet_Pru,Vet_Imp,
							IF((Vet_Des+Vet_Dec)=0,NULL,(Vet_Imp*(Vet_Des+Vet_Dec)/100)) AS Descuento,
							(Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100)) AS SubTotal,
							IF(Iva_Por=0,NULL,(Vet_Imp*(Iva_Por)/100)) AS Iva,
                            ((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100)) + (Vet_Imp*Iva_Por/100)) AS Total,
                            (((Vet_Imp-(Vet_Imp*(Vet_Des + Vet_Dec)/100))+(Vet_Imp * Iva_Por/100))/Vet_Can) AS Unitario,Ite_Lar, Des_Adi
                        FROM ventas_det
                                INNER JOIN producto ON ventas_det.Pro_Cod=producto.Pro_Cod
                                INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod
                                INNER JOIN ventas ON ventas_det.Vet_Cod=ventas.Vet_Cod                                
                                INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                                INNER JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                                INNER JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                                INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
                                INNER JOIN iva ON ventas_det.Iva_Cod=iva.Iva_Cod
                                INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
                                INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
                        WHERE Vet_Est='A' 
                                AND sucursal.Suc_Cod=$Par_Sql[1] $Par_Sql[2]
                            AND Caj_Fec BETWEEN '$Par_Sql[3] 00:00:00' AND '$Par_Sql[4] 23:59:59'  $Par_Sql[5]
                        ORDER BY Caj_Fec DESC";
			//echo $sql;
			return $sql;






		case 6001:
			/**
			 * Actualizar Stock y precio promedio de la tabla stock
			 */
			$sql = "UPDATE stock SET Stk_Can = $Par_Sql[Stock], Stk_Prp = $Par_Sql[Promedio] WHERE Pro_Cod = $Par_Sql[Pro_Cod] and Suc_Cod = $Par_Sql[Ses_Suc_Cod]";
			return $sql;
			break;

		case 6002:
			/**
			 * Actualizar Stock y precio promedio de la tabla producto
			 */
			$sql = "UPDATE producto SET Pro_Stk = $Par_Sql[Stock], Pro_Prp = $Par_Sql[Promedio] WHERE Pro_Cod = $Par_Sql[Pro_Cod]";
			return $sql;
			break;


			/**
			 *  Consulta la provicia y pais de la ciudad de la sucursal
			 */
		case 5000:
			$sql = "SELECT
					provincia.Pro_Nom,
					pais.Pas_Nom
				  FROM
					provincia
					INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
					INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
					INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod)
				  WHERE
					ciudad.Ciu_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;

			/**
			 *  Consulta la informaci�n la ciudada en base a la sucursal
			 */
		case 5001:
			$sql = "SELECT 
					empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log 
				FROM 
					empresas, sucursal, ciudad 
				WHERE 
					sucursal.Suc_Cod = $Par_Sql[0] AND 
					empresas.Emp_Cod = sucursal.Emp_Cod AND 
					sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $sql;
			return $sql;
			break;

			/**
			 *  Consulta los datos del usuario
			 */
		case 5002:
			$sql = "SELECT 
						Prs_Ape, Prs_Nom, Prs_Ced 
					 FROM 
						persona, usuarios 
					 WHERE 
						persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta los datos del usuario
			 */
		case 5003:
			$sql = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
			break;

		case 5004:
			$sql = "SELECT DISTINCT Vnd_Cod,CONCAT(Prs_Ape,' ',Prs_Nom)AS Vendedor FROM vendedor
                            INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                            INNER JOIN persona ON vendedor.Prs_Cod=persona.Prs_Cod
                            WHERE sucursal.Suc_Cod=$Par_Sql[0]";
			return $sql;
		case 5005:
			$alfa = (($Par_Sql[1] == 'A' && $Par_Sql[2] == 'Z') || (empty($Par_Sql[1]) || empty($Par_Sql[2])) ? '' : " AND UPPER(Ite_Lar) BETWEEN '$Par_Sql[1]A%' AND '$Par_Sql[2]Z%' ");
			$cate = (empty($Par_Sql[3]) ? '' : " AND categorias.Cat_Cod=$Par_Sql[3] ");
			$ubic = (empty($Par_Sql[4]) ? '' : " AND producto.Ubi_Cod=$Par_Sql[4] ");
			$sql = "SELECT item.Ite_Cod,producto.Pro_Cod,Ite_Lar,Ite_Cor,Ubi_Des,Cat_Des,IF(Pro_Stk IS NULL,0,Pro_Stk)AS Pro_Stk,IF(Pro_Prp IS NULL,0,Pro_Prp)AS Pro_Prp,Stk_Can,Stk_Prp,(Stk_Can*Stk_Prp)AS Stk_Sal FROM producto
					INNER JOIN item ON item.Ite_Cod=producto.Ite_Cod 
					INNER JOIN categorias ON item.Cat_Cod=categorias.Cat_Cod 
					INNER JOIN ubicacion ON producto.Ubi_Cod=ubicacion.Ubi_Cod
					LEFT JOIN stock ON stock.Pro_Cod=producto.Pro_Cod
					WHERE Pro_Est='A' AND categorias.Emp_Cod=$Par_Sql[0] $alfa $cate $ubic /*AND producto.Pro_Cod=1602*/ ORDER BY " . (!empty($Par_Sql[5]) && $Par_Sql[5] != 'clear' ? "$Par_Sql[5]," : '') . "Ite_Lar; ";
			//echo $sql.'<br/>';
			return $sql;
		case 5006:
			$sql = "SELECT cat.Cat_Cod,cat.Cat_Des,cat.Cat_Rec,cat.Cat_Cdc,parent.Cat_Des AS Par_Cat_Des FROM categorias AS cat
				LEFT JOIN categorias AS parent ON   parent.Cat_Cod=cat.Cat_Cod
				WHERE cat.Cat_Est='A' AND cat.Cat_Tip='D' AND cat.Emp_Cod=$Par_Sql[0]";
			//echo $sql.'<br/>';
			return $sql;
		case 5007:
			$sql = "SELECT Ubi_Cod,Ubi_Des FROM ubicacion WHERE Ubi_Est='A' AND Emp_Cod = $Par_Sql[0] ORDER BY Ubi_Cod ASC";
			//echo $sql.'<br/>';
			return $sql;


		case 5008:
			/**
			 * Consultar bodegas por sucursal para filtro de busqueda
			 */

			$sql = "select bodega.Bod_Cod, bodega.Bod_Tip, bodega.Bod_Nom from bodega, sucursal, bodega_usuario 
                    where bodega.Suc_Cod=sucursal.Suc_Cod and bodega_usuario.bod_cod=bodega.bod_cod
                    and usu_cod=$_SESSION[Ses_Usu_Cod] and Emp_Cod = $_SESSION[Ses_Emp_Cod] and Bod_Est='A'";
			return $sql;
			break;

		case 5009:
			/**
			 * Con esta sentencia consulto el movimiento del kardex con fechas 
			 */
			$sql = "SELECT IF( kardex_ie.Vet_Cod!=0,'3',IF(kardex_ie.Cop_Cod!=0,'1',IF(kardex_ie.Aju_Cod!=0,'2','')))AS orden,
                            CONCAT(kardex_ie.Vet_Cod,'_',kardex_ie.Cop_Cod,'_',kardex_ie.Aju_Cod) AS Kar_Key,
                            /*IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',NULL))) AS Tipo,*/
                            IF( kardex_ie.Vet_Cod!=0,TIC2.Tic_Des,IF(kardex_ie.Cop_Cod!=0,TIC1.Tic_Des,IF(kardex_ie.Aju_Cod!=0,NULL,NULL))) AS Doc,
                            IF( kardex_ie.Vet_Cod!=0,CONCAT(Suc_Sri,'-',Pun_Sri,'-',CAST(LPAD(Vet_Num,9,'0')AS char)),IF(kardex_ie.Cop_Cod!=0,Cop_Num,IF(kardex_ie.Aju_Cod!=0,Aju_Sec,NULL))) AS Doc_Num,
							CONCAT(IF(compras.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(ventas.Tic_Cod = 4, 'Devolucion ', ''),
                            IF(kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),
                            IF(compras.Tic_Cod != 4,(IF(kardex_ie.Cop_Cod!=0, IF(kardex_ie.Kar_Can>=0, '', 'Anulada'), '')), ''),
                            IF(ventas.Tic_Cod != 4,(IF(kardex_ie.Vet_Cod!=0, IF(kardex_ie.Kar_Sal>=0, '', 'Anulada'), '')), ''), ' - ', 
                            IF(kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',(TRIM(REPLACE(REPLACE(Vet_Obs, CHAR(13), ''), CHAR(10), ' ')))),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
							/*
						      CONCAT(IF( kardex_ie.Vet_Cod!=0,'Venta',IF(kardex_ie.Cop_Cod!=0,'Compra',IF(kardex_ie.Aju_Cod!=0,'Ajuste',''))),' - ',IF( kardex_ie.Vet_Cod!=0,IF(Vet_Obs IS NULL,'',Vet_Obs),IF(kardex_ie.Cop_Cod!=0,IF(Cop_Obs IS NULL, '',Cop_Obs),IF(kardex_ie.Aju_Cod!=0,IF(Aju_Det IS NULL,'',Aju_Det),'')))) AS Kar_Det,
                            */
						    kardex_ie.Vet_Cod,kardex_ie.Aju_Cod,kardex_ie.Cop_Cod,kardex_ie.Vnd_Cod,Gia_Cod,kardex_ie.Pro_Cod,Kar_Fec,Kar_Can,Kar_Sal,Kar_Hor,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,kardex_ie.Iva_Cod ,(Kar_Can-Kar_Sal) AS Stock , 
						    (Kar_Ims-Kar_Ime) AS Saldo , 
						    Round((kardex_ie.Kar_Sal/count(kardex_ie.Kar_Sal)) * Sum(ventas_det.Vet_Pru),4)  AS Precio_ent,
						   /* (Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_ent, */
						    (Kar_Ims)+(( ((Kar_Ims)-(((Kar_Ims)*Kar_Des)/100) )*Iva_Por)/100) AS Precio_sal, Bod_Nom AS Bodega 
                            FROM kardex_ie
						  
						 /*   INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
	                        LEFT JOIN bodega on bodega.bod_cod=kardex_ie.bod_cod   
                            LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
						    LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                            LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                            LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
						    LEFT JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod) 
                            LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
                            LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod */
							INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod 
							LEFT JOIN bodega on bodega.bod_cod=kardex_ie.bod_cod   
                            LEFT JOIN ajuste_kar ON ajuste_kar.Aju_Cod=kardex_ie.Aju_Cod
                            LEFT JOIN det_ajustek ON ajuste_kar.Aju_Cod=det_ajustek.Aju_Cod
                            LEFT JOIN compras ON compras.Cop_Cod=kardex_ie.Cop_Cod
                            LEFT JOIN proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
                            LEFT JOIN persona ON proveedore.Prs_Cod = persona.Prs_Cod
                            LEFT JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                            LEFT JOIN tipo_compr AS TIC1 ON TIC1.Tic_Cod=compras.Tic_cod
                            LEFT JOIN ventas ON (kardex_ie.Vet_Cod = ventas.Vet_Cod)
                            LEFT JOIN cliente ON cliente.Cli_Cod = ventas.Cli_Cod
                            LEFT JOIN persona as prs ON cliente.Prs_Cod = prs.Prs_Cod
                            LEFT JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
                            LEFT JOIN tipo_compr AS TIC2 ON TIC2.Tic_Cod=ventas.Tic_cod
						    LEFT JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod) 
                            LEFT JOIN puntos_imp ON caja_aper.Pun_Cod=puntos_imp.Pun_Cod
                            LEFT JOIN autorizaci ON autorizaci.Aut_Cod=ventas.Aut_Cod 
                            LEFT JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod 
			WHERE 
				Kar_Est='A' AND 
			
				if(kardex_ie.Vet_Cod!=0, ventas_det.Pro_Cod = $Par_Sql[2], if(kardex_ie.Cop_Cod!=0, det_compra.Pro_Cod =$Par_Sql[2], det_ajustek.Pro_cod = $Par_Sql[2])) AND
				
				kardex_ie.Pro_Cod=$Par_Sql[2]  AND        
				Kar_Fec BETWEEN '$Par_Sql[0] 00:00:00' AND '$Par_Sql[1] 23:59:59' 
				" . $Par_Sql[3] . "

				GROUP BY kardex_ie.Kar_Int, kardex_ie.Vet_cod, kardex_ie.Iva_cod, kardex_ie.Aju_cod, kardex_ie.Vnd_Cod, kardex_ie.Pro_cod, kardex_ie.Cop_cod, kardex_ie.Gia_cod
		
				ORDER BY Kar_Fec,orden,Kar_Hor";
			//echo $sql;
			return $sql;
			break;
	
		case 5010:
			/**
			 * Consulta la cantidad del producto por fecha 0000-00-00 
			 */
			$joinBodega="";$bodega="";
			if(!empty($Par_Sql['tipos']) && $Par_Sql['tipos'] == 'S') {
				$bodega = " AND kardex_ie.Bod_Cod=" . $Par_Sql['Bod_Cod'];
				$joinBodega = " INNER JOIN bodega ON bodega.Bod_Cod=kardex_ie.Bod_Cod";
			}			
			$sql = " SELECT (SUM(Kar_Can)- SUM(Kar_Sal)) as Stock, (SUM(Kar_Ims)- SUM(Kar_Ime)) AS Saldo, 
				SUM((Kar_Ime)+(( ( (Kar_Ime)-(((Kar_Ime)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_ent, 
				SUM((Kar_Ims)+(( ( (Kar_Ims)-(((Kar_Ims)*Kar_Des)/100)  )*Iva_Por)/100)) AS Precio_sal
				FROM kardex_ie
				INNER JOIN iva ON iva.Iva_Cod=kardex_ie.Iva_Cod
				$joinBodega
				WHERE Kar_Est='A' AND Kar_Fec<'$Par_Sql[Kar_Fec] 00:00:00' AND Pro_Cod=$Par_Sql[Pro_Cod] $bodega ";
			//echo $sql.'<br>';
			return $sql;
			break;
	}
}
