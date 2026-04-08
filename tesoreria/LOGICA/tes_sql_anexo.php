<?php

/**
 * Anexo transaacional
 */


function sentencias_anx($id, $Par_Sql)
{
	switch ($id) {
			/**
		 * Consulta la provicia y pais de la ciudad de la sucursal
		 */
		case 21:
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
			return $sql;
			break;

			/**
			 * Consulta la informaci�n la ciudada en base a la sucursal
			 */
		case 22:
			$sql = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
				sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
			break;

			/**
			 * Consulta los datos del usuario
			 */
		case 23:
			$sql = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
			break;

		case 226:
			/**
			 * Consulta la identificaci�n del archivo xml
			 */
			$sql = "SELECT Emp_Ruc, Emp_Nom, Suc_Dir, Suc_Sri,Suc_Te1, Suc_Fax, Suc_Cor, Emp_Rce, Emp_Rep, Emp_Rco, Emp_Ren FROM empresas, sucursal
									WHERE empresas.Emp_Cod = sucursal.Emp_Cod AND empresas.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;

		case '227_group':
			/**
			 * Consulta del esquema sin recursividad
			 */
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml, Esq_Rec FROM esquema WHERE Esq_Rec IN(".($Par_Sql[0]?:'NULL').") AND Tan_Cod = $Par_Sql[1] AND Esq_Est = 'A';";
			//echo "<br>".$sql;
			return $sql;

		case 227:
			/**
			 * Consulta del esquema sin recursividad
			 */
			$sql = "SELECT Esq_Cod,Esq_Des,Esq_Xml FROM esquema WHERE esquema.Esq_Rec=$Par_Sql[0] AND esquema.Tan_Cod=$Par_Sql[1] AND esquema.Esq_Est='A'";
			//echo "<br>".$sql;
			return $sql;
			break;

		case 229:
			/**
			 * Consulta del iva mayor a cero (0)
			 */
			$sql = "SELECT Iva_Cod, Iva_Sri, Iva_Por FROM iva WHERE iva.Iva_Por > 0 AND iva.Iva_Est = 'A'";
			return $sql;
			break;

			//case 230:
			//			/**
			//			* Consulta de los datos del detalle
			//			*/
			//			$sl = "SELECT (sum(Cop_Imp) -
			//			(sum((Cop_Imp * Cop_Des) /100) + sum((Cop_Imp * Cop_Dec) /100))) as Cop_Imp, Iva_Sri, Iva_Por FROM compras, det_compra, iva
			//			WHERE compras.Cop_Cod = det_compra.Cop_Cod AND det_compra.Iva_Cod = iva.Iva_Cod AND det_compra.Cop_Cod = $Par_Sql[0] AND det_compra.Adq_Cod !=13 GROUP BY Iva_Sri, Iva_Por";
			//			return $sql;
			//			break;


		case '230_group':
			/**
			 * Consulta de los datos del detalle
			 */
			$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
			$ice = "CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
			$iva = "( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";
			$sql = "SELECT compras.Cop_Cod,Iva_Por,
						SUM((dato.nobIva))AS nobIva ,
						SUM((dato.sub_0))AS Sub0 ,
						SUM((dato.sub_12))AS Sub12,
						SUM((dato.sub_12+dato.sub_0))AS Cop_Imp,
						SUM(dato.IvaTot)AS IvaTot
						from (
							SELECT compras.Cop_Cod,Iva_Por,
									CAST( SUM(IF(Iva_Por = 0 and Iva_Sri=6,  $importe_con_desc , '0'))  AS decimal(20,2))AS nobIva,
									CAST( SUM(IF(Iva_Por = 0 and Iva_Sri!=6,  $importe_con_desc , '0'))  AS decimal(20,2))AS sub_0,
									CAST( SUM(IF(Iva_Por != 0, $importe_con_desc , '0'))  AS decimal(20,2))AS sub_12,
									CAST( SUM(  $importe * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
									CAST( SUM(IF(Cop_Ice != 0, $ice, 0))  AS decimal(20,2)) AS IceTot,
									CAST( SUM(IF(Iva_Por != 0, $iva, 0)) AS decimal(20,2))  AS IvaTot,

									CAST( SUM((
											$importe_con_desc /* IMPORTE */
											+ $ice /* ICE */
											+ $iva /* IVA */
										)
									)  AS decimal(20,2)) AS total
							FROM compras
							INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
							INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
							INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
							WHERE det_compra.Cop_Cod IN (".($Par_Sql[0]?:'NULL').")
							GROUP BY compras.Cop_Cod,Iva_Sri, Iva_Por) AS dato
						INNER JOIN compras ON compras.Cop_Cod=dato.Cop_Cod Group by compras.Cop_Cod";
			//echo $sql."<br>";die();
			return $sql;

		case 230:
			/**
			 * Consulta de los datos del detalle
			 */
			$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
			$ice = "CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
			$iva = "( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";
			$sql = "SELECT Iva_Por,
					SUM((dato.sub_0))AS Sub0 ,
					SUM((dato.sub_12))AS Sub12,
					SUM((dato.sub_12+dato.sub_0))AS Cop_Imp,
					SUM(dato.IvaTot)AS IvaTot
					from (
						SELECT Iva_Por,compras.Cop_Cod,
							    CAST( SUM(IF(Iva_Por = 0,  $importe_con_desc , '0'))  AS decimal(20,2))AS sub_0,
								CAST( SUM(IF(Iva_Por != 0, $importe_con_desc , '0'))  AS decimal(20,2))AS sub_12,
								CAST( SUM(  $importe * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
								CAST( SUM(IF(Cop_Ice != 0, $ice, 0))  AS decimal(20,2)) AS IceTot,
								CAST( SUM(IF(Iva_Por != 0, $iva, 0)) AS decimal(20,2))  AS IvaTot,

								CAST( SUM((
										$importe_con_desc /* IMPORTE */
										+ $ice /* ICE */
										+ $iva /* IVA */
									)
								)  AS decimal(20,2)) AS total
						FROM compras
						INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
						INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
						INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
						WHERE det_compra.Cop_Cod = $Par_Sql[0]
						GROUP BY Iva_Sri, Iva_Por) AS dato
					INNER JOIN compras ON compras.Cop_Cod=dato.Cop_Cod Group by compras.Cop_Cod";
			//echo $sql."<br>";
			return $sql;

		case '231_group':
			/**
			 * Consulta de los datos del ICE
			 */
			$sql = "SELECT det_compra.Cop_Cod, Sum(Cop_Imp) as Cop_Imp, Ice_Sri, Ice_Por, (Sum(Cop_Imp) * Ice_Por )/100 as Mon_Ice, Ice_Cod
					FROM det_compra, ice
					WHERE det_compra.Ice_Int = ice.Ice_Int AND det_compra.Cop_Cod IN (".($Par_Sql[0]?:'NULL').")
					GROUP BY det_compra.Cop_Cod, Ice_Sri, Ice_Por";
			return $sql;
		case 231:
			/**
			 * Consulta de los datos del ICE
			 */
			$sql = "SELECT Sum(Cop_Imp) as Cop_Imp, Ice_Sri, Ice_Por, (Sum(Cop_Imp) * Ice_Por )/100 as Mon_Ice, Ice_Cod
					FROM det_compra, ice
					WHERE det_compra.Ice_Int = ice.Ice_Int AND det_compra.Cop_Cod = $Par_Sql[0]
					GROUP BY Ice_Sri, Ice_Por";
			return $sql;

		case '232_group':
			/**
			 * Consulta de los montos bienes o servicios
			 */
			$sql = "SELECT
			            retencion.Cop_Cod,
						sum(det_retenc.Ret_Bas) AS Ret_Bas,
						det_retenc.Adq_Cod,
						renta_iva.Ren_Por
					FROM
						retencion
						INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
						INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
						INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
						INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
						INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
			WHERE (det_retenc.Adq_Cod = 1 OR det_retenc.Adq_Cod = 2 OR det_retenc.Adq_Cod = 3 OR det_retenc.Adq_Cod = 13 OR det_retenc.Adq_Cod = 14)
			AND  renta_iva.Ren_Por != 100
			AND  retencion.Cop_Cod IN (".($Par_Sql[0]?:'NULL').") AND det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A' AND sucursal.Emp_Cod = $Par_Sql[1]
			GROUP BY retencion.Cop_Cod,Ren_Por";
			//echo $sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
		case 232:
			/**
			 * Consulta de los montos bienes o servicios
			 */
			$sql = "SELECT
					  sum(det_retenc.Ret_Bas) AS Ret_Bas,
					  det_retenc.Adq_Cod,
					  renta_iva.Ren_Por
					FROM
					  retencion
					  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
					  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
					  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
			WHERE  (det_retenc.Adq_Cod = 1 OR det_retenc.Adq_Cod = 2 OR det_retenc.Adq_Cod = 3 OR det_retenc.Adq_Cod = 13 OR det_retenc.Adq_Cod = 14) AND  renta_iva.Ren_Por != 100 AND  retencion.Cop_Cod = $Par_Sql[0] AND det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A' AND sucursal.Emp_Cod = $Par_Sql[1] GROUP BY  Ren_Por";
			//echo $sql."<br>";
			//ChromePhp::log($sql);
			return $sql;

		case '233_group':
			/**
			 * Consulta de los datos AIR
			 */
			$sql = "SELECT retencion.Cop_Cod, Ren_Sri, sum(Ret_Bas) AS Ret_Bas,Suc_Sri,Pun_Sri, Ren_Por, sum((Ret_Bas * Ren_Por)/100) as Val_Air,
			Ret_Num,retencion.Ret_Cod,Ret_Uca,Ret_Pca, 	Ret_Sri,Aut_Tem,Aut_Sri, Ret_Fec
			FROM retencion
			INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
			INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
			INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
			INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
			INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
			WHERE retencion.Cop_Cod IN (".($Par_Sql[0]?:'NULL').") AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'
			GROUP BY retencion.Cop_Cod, Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec";/*En esta SQL se agrego GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec para unificar
								en caso de que a una retencion se le agregue 2 codigos de los mismos */
			//echo $sql;
			//ChromePhp::log($sql);
			return $sql;
		case 233:
			/**
			 * Consulta de los datos AIR
			 */
			$sql = "SELECT Ren_Sri, sum(Ret_Bas) AS Ret_Bas,Suc_Sri,Pun_Sri, Ren_Por, sum((Ret_Bas * Ren_Por)/100) as Val_Air,
			Ret_Num,retencion.Ret_Cod,Ret_Uca,Ret_Pca, 	Ret_Sri,Aut_Tem,Aut_Sri, Ret_Fec
			FROM retencion
			INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
			INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
			INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
			INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
			INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
			WHERE retencion.Cop_Cod = $Par_Sql[0] AND renta_iva.Ren_Ret = '$Par_Sql[1]' AND Ret_Est = 'A'
			GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec";/*En esta SQL se agrego GROUP BY Ren_Sri, Ren_Por, Ret_Num, Aut_Sri, Ret_Fec para unificar
								en caso de que a una retencion se le agregue 2 codigos de los mismos */
			//echo $sql;
			//ChromePhp::log($sql);
			return $sql;

		case 234:
			/**
			 * Consulta del esquema sin recursividad para los grupos
			 */
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] AND Esq_Ini = '$Par_Sql[2]'";
			//echo $sql;
			return $sql;
			break;

		case 237:
			/**
			 * Consulta total de las  retenciones o liquidaciones anuladas o activas
			 */
			$sql = "SELECT retencion.Ret_Cod,retencion.Cop_Cod,tipo_compr.Tic_Sri,Tic_Des,Pun_Sri,Suc_Sri,
					retencion.Ret_Num,if(Aut_Tem='N',Aut_Sri,Ret_Sri)as Aut_Sri,retencion.Ret_Fec
					FROM
					  autorizaci
					  INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
					  INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN puntos_imp ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
					  INNER JOIN sucursal ON (sucursal.Suc_Cod = puntos_imp.Suc_Cod)
					WHERE (Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND Ret_Est = '$Par_Sql[2]' AND Emp_Cod = '$Par_Sql[3]'";
			//echo $sql;
			return $sql;
			break;

		case 238:
			/**
			 * Consulta las compras o liquidaciones de compra en base al Estado y Tipo de comprobante
			 */
			$esquema_238 = "SELECT compras.Cop_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num,
						Cop_Imf, Cop_Aut, Cop_Cad FROM compras, sustento, proveedore, persona, identifica, tipo_compr
						WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND
						proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND
						compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Reg BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
						AND compras.Cop_Est = '$Par_Sql[2]' AND tipo_compr.Tic_Sri = $Par_Sql[3] AND proveedore.Emp_Cod = $Par_Sql[4]";
			//echo $esquema_238;
			return $esquema_238;
			break;

		case 260:
			/**
			 * Consulta de los datos de la cabecera
			 */
			$sql = "SELECT compras.Cop_Cod,compras.Tpc_Cod, sustento.Tri_Sri, Prs_Ced, COALESCE(Cop_Ide,Ide_Prc)as Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, Cop_Imf,
	Cop_Aut, Cop_Cad, Cop_Reg, Cop_Ntd, Cop_Nns, Cop_Nna , proveedore.Prv_Tic,proveedore.Prv_Com
	FROM compras, sustento, proveedore, persona, identifica, tipo_compr
	WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND
	proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND
	compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
	AND compras.Cop_Est = 'A' AND tipo_compr.Tic_Est='A' AND (Tic_Sri = 1 OR Tic_Sri = 2 OR Tic_Sri = 5 OR Tic_Sri = 3 OR Tic_Sri = 4 OR Tic_Sri = 11 OR Tic_Sri = 15 OR  Tic_Sri = 19 OR Tic_Sri = 41 OR Tic_Sri = 26 OR Tic_Sri = 21)  AND compras.Tri_Cod != 1 AND proveedore.Emp_Cod = $Par_Sql[2]";




			//if($_SESSION['Ses_Prs_Cod']==1) echo $sql;
			//ChromePhp::log($sql);
			return $sql;
			break;
			/**
			 * Consulta del esquema sin recursividad
			 */

		case '386_group':
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml, Esq_Ord, Esq_Rec FROM esquema WHERE esquema.Esq_Rec IN (".($Par_Sql[0]?:'NULL').") AND esquema.Tan_Cod = $Par_Sql[1] ORDER BY Esq_Ord ASC";
			//echo $sql;
			return $sql;
		case 386:
			$sql = "SELECT Esq_Cod, Esq_Des, Esq_Xml, Esq_Ord FROM esquema WHERE esquema.Esq_Rec = $Par_Sql[0] AND esquema.Tan_Cod = $Par_Sql[1] ORDER BY Esq_Ord ASC";
			//echo $sql;
			return $sql;

			/**
			 * Consultando los CLIENTES q se emitio Ventas en un determinado MES para el Anexo Transaccional
			 */
		case '387_new':
			$sql = "SELECT
                                    ver.Prs_Ced,
                                    ver.Prs_Ape,
                                    ver.Prs_Nom,
                                    ver.Cli_Cod,
                                    ver.CliTic,
                                    ver.Ide_Prv,
                                    ver.Tic_Cod,
                                    ver.Tic_Cod,
                                    ver.Aut_Tem,
                                    ver.Tic_Des,
                                    ver.TicSri,
                                    count(ver.Aut_Tem)as total
				FROM (
					SELECT
                                            persona.Prs_Ced,
                                            persona.Prs_Ape,
                                            persona.Prs_Nom,
                                            ventas.Cli_Cod,
                                            if(cliente.Cli_Tic='N','01','02')as CliTic,
					    identifica.Ide_Prv,
					    identifica.Ide_Prc,
                                            tipo_compr.Tic_Cod,
                                            Aut_Tem,
                                            Tic_Sri,
                                            tipo_compr.Tic_Des,
                                            IF(reemb.Vet_Cod IS NULL,Tic_Sri,'41')as TicSri
					FROM caja_aper
                                        INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
					INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
                                        INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					LEFT JOIN venta_reembolsos as reemb ON (ventas.Vet_Cod = reemb.Vet_Cod)
					LEFT JOIN exporta_vent as expo ON (ventas.Vet_Cod = expo.Vet_Cod)
					WHERE
					expo.Vet_Cod IS NULL AND
					cliente.Emp_Cod = $Par_Sql[3] AND
					ventas.Vet_Est ='A' AND tipo_compr.Tic_Sri IN('1', '2', '4', '5', '41') AND
					caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
					GROUP BY ventas.Cli_Cod,ventas.Vet_Cod,autorizaci.Aut_Tem,IF(reemb.Vet_Cod IS NULL,Tic_Sri,'41')
				) AS ver
				GROUP BY ver.Cli_Cod, Aut_Tem, TicSri
                                ORDER BY ver.Prs_Ced";
			//echo $sql; //, cliente.Cli_Cod, persona.Prs_Cod, identifica.Ide_Prv
			return $sql;
		case 387:
			$sql = "SELECT
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Ape,
					  persona.Prs_Nom,
					  cliente.Cli_Cod,
					  if(cliente.Cli_Tic='N','01','02')as CliTic,
					  identifica.Ide_Prv,
					  identifica.Ide_Prc
					FROM
					  cliente
					  INNER JOIN ventas ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					WHERE
					  ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
					  ventas.Vet_Est = '$Par_Sql[0]' AND (tipo_compr.Tic_Sri='1' or tipo_compr.Tic_Sri='2' or tipo_compr.Tic_Sri='4' or tipo_compr.Tic_Sri='5'  or tipo_compr.Tic_Sri='41') AND
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND cliente.Emp_Cod = $Par_Sql[3]
					GROUP BY
					  persona.Prs_Ced
					ORDER BY
					  persona.Prs_Ced";
			//echo $sql; //, cliente.Cli_Cod, persona.Prs_Cod, identifica.Ide_Prv
			return $sql;

			/**
			 * Consultando la cabecera de Facturas
			 */
		case '388_group':
			$sql = "SELECT ver.Cli_Cod,ver.Tic_Cod, ver.Aut_Tem,ver.Tic_Des,IF(Vet_Cod IS NULL,Tic_Sri,'41')as TicSri ,count(Aut_Tem)as total
				FROM (
					SELECT ventas.Cli_Cod,reemb.*,tipo_compr.Tic_Cod, Aut_Tem, Tic_Sri, tipo_compr.Tic_Des
					FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
					INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					LEFT JOIN venta_reembolsos as reemb ON (ventas.Vet_Cod = reemb.Vet_Cod)
					LEFT JOIN exporta_vent as expo ON (ventas.Vet_Cod = expo.Vet_Cod)
					WHERE
					expo.Vet_Cod IS NULL AND
					ventas.Cli_Cod IN (".($Par_Sql[0]?:'NULL').") AND
					ventas.Vet_Est ='A' AND tipo_compr.Tic_Sri ON('1', '2', '4', '5', '41') AND
					caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
					GROUP BY autorizaci.Aut_Tem,tipo_compr.Tic_Cod,ventas.Vet_Cod
				) AS ver
				GROUP BY ver.Cli_Cod, Aut_Tem, TicSri";
			//echo "<br>".$sql;die();
			return $sql;
		case 388:
			/*$sql= "SELECT distinct tipo_compr.Tic_Cod, Aut_Tem,	tipo_compr.Tic_Sri, tipo_compr.Tic_Des,
						count(ventas.Aut_Cod)as total
					FROM
						caja_aper
						INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
						INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
						INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					WHERE
						ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						ventas.Vet_Cod not in(select reemb.Vet_Cod from venta_reembolsos as reemb) AND
						ventas.Cli_Cod = '$Par_Sql[0]' AND
						ventas.Vet_Est ='A' AND (tipo_compr.Tic_Sri='1' or tipo_compr.Tic_Sri='2' or tipo_compr.Tic_Sri='4' or tipo_compr.Tic_Sri='5' or tipo_compr.Tic_Sri='41') AND
						caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' group by tipo_compr.Tic_Cod,autorizaci.Aut_Tem
					UNION
			        SELECT distinct tipo_compr.Tic_Cod, Aut_Tem, '41'as Tic_Sri,
							tipo_compr.Tic_Des, count(ventas.Aut_Cod)as total
					FROM
						caja_aper
						INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
						INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
						INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						INNER JOIN  (
								SELECT distinct reemb.Vet_Cod FROM venta_reembolsos AS reemb Inner Join ventas On(reemb.Vet_Cod=ventas.Vet_Cod)
							 )as reembolso ON (ventas.Vet_Cod = reembolso.Vet_Cod)
					WHERE
						ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						ventas.Cli_Cod = '$Par_Sql[0]' AND
						ventas.Vet_Est = 'A' AND (tipo_compr.Tic_Sri='1' or tipo_compr.Tic_Sri='2' or tipo_compr.Tic_Sri='4' or tipo_compr.Tic_Sri='5' or tipo_compr.Tic_Sri='41' ) AND
						caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' group by tipo_compr.Tic_Cod,autorizaci.Aut_Tem";
                        */
			$sql = "SELECT ver.Tic_Cod, ver.Aut_Tem,ver.Tic_Des,IF(Vet_Cod IS NULL,Tic_Sri,'41')as TicSri ,count(Aut_Tem)as total  FROM (
						  SELECT reemb.*,tipo_compr.Tic_Cod, Aut_Tem, Tic_Sri, tipo_compr.Tic_Des
						  FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
						  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
						  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						  LEFT JOIN venta_reembolsos as reemb ON (ventas.Vet_Cod = reemb.Vet_Cod)
						  WHERE
						  ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						  ventas.Cli_Cod = '$Par_Sql[0]' AND
						  ventas.Vet_Est ='A' AND (tipo_compr.Tic_Sri='1' or tipo_compr.Tic_Sri='2' or tipo_compr.Tic_Sri='4' or
						  tipo_compr.Tic_Sri='5' or tipo_compr.Tic_Sri='41') AND
						  caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
						  group by autorizaci.Aut_Tem,tipo_compr.Tic_Cod,ventas.Vet_Cod
						  ) AS ver
						GROUP BY Aut_Tem, TicSri";
			//echo "<br>".$sql;
			return $sql;

                /**
			 * Consultando los Detalles de Ventas de Factura
			 */
		case '389_group':
			$sql = "SELECT
                            SUM(BaseNObjIva)AS BaseNObjIva,
							SUM(BaseCero)AS BaseCero,
                            SUM(BaseIva)AS BaseIva,
                            SUM(Total)AS Total,
                            SUM(Iva)AS Iva,
                            CONCAT(Cli_Cod,'_',Aut_Tem,'_',Tic_Sri_Aux)AS Aux_Key
                          FROM(
							SELECT
					  ventas.Cli_Cod,Aut_Tem,ventas.Tic_Cod,IF(reemb.Vet_Cod IS NOT NULL,41,Tic_Sri)AS Tic_Sri_Aux,
					  CAST( SUM(IF(Iva_Por  = 0 AND Iva_Sri=6, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*1 ), 0)) AS decimal(20,2)) AS BaseNObjIva,
					  CAST( SUM(IF(Iva_Por  = 0 AND Iva_Sri=0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*1 ), 0)) AS decimal(20,2)) AS BaseCero,
					  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*1 ), 0)) AS decimal(20,2)) AS BaseIva,
					  CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Total,
					  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva,
					  iva.Iva_Por
					FROM
					  ventas
                      INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
                      /*LEFT JOIN venta_reembolsos AS reemb ON reemb.Vet_Cod=ventas.Vet_Cod*/
					  LEFT JOIN (
				      		select distinct venta_reembolsos.Vet_Cod from venta_reembolsos inner join ventas as vent ON venta_reembolsos.Vet_Cod=vent.Vet_Cod where vent.Cli_Cod in ($Par_Sql[2])
				      ) AS reemb ON `reemb`.`Vet_Cod` = `ventas`.`Vet_Cod`
                      LEFT JOIN exporta_vent AS expo ON expo.Vet_Cod=ventas.Vet_Cod
					WHERE
					  expo.Vet_Cod IS NULL AND
					  ventas.Vet_Est = 'A' AND
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
					  ventas.Cli_Cod IN(".(!empty($Par_Sql[2])?$Par_Sql[2]:'NULL').")
					GROUP BY
                                          ventas.Cli_Cod,
					  ventas.Vet_Cod,
					  iva.Iva_Por
                                )AS tbl GROUP BY CONCAT(Cli_Cod,'_',Aut_Tem,'_',Tic_Sri_Aux) ";
			//echo $sql."<br>";
			return $sql;

			/**
			 * Consultando los Detalles de Ventas de Factura
			 */
		case 389:
			if ($Par_Sql[5] == '41') {
				$parReem1 = "ventas.Vet_Cod IN(SELECT distinct vntas.Vet_Cod FROM ventas AS vntas INNER JOIN venta_reembolsos as reemb ON (vntas.Vet_Cod = reemb.Vet_Cod) where vntas.Cli_Cod=$Par_Sql[2]) AND ";
			} else {
				$parReem1 = 'ventas.Vet_Cod NOT IN(SELECT reemb.Vet_Cod FROM venta_reembolsos AS reemb) AND';
			}
			$sql = "SELECT
					  ventas.Vet_Est,ventas.Tic_Cod,
                                          CAST( SUM(IF(Iva_Por  = 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*1 ), 0)) AS decimal(20,2)) AS BaseCero,
                                          CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*1 ), 0)) AS decimal(20,2)) AS BaseIva,
					  CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Total,
					  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Iva,
					FROM
					  ventas
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					WHERE
					  $parReem1
					  ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
					  ventas.Vet_Est = 'A' AND
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
					  ventas.Cli_Cod ='$Par_Sql[2]' AND ventas.Tic_Cod='$Par_Sql[3]' AND Aut_Tem='$Par_Sql[4]'
					GROUP BY
                                          ventas.Cli_Cod
					  ventas.Vet_Est";
			//echo $sql."<br>";
			return $sql;

			/**
			 * Consultando las facturas Anuladas en un mes y a�o determinado Anexos Transaccionales 2010
			 */
		case 390:
			$sql = "	SELECT
					  ventas.Vet_Num,
					  tipo_compr.Tic_Sri,
					  tipo_compr.Tic_Des,
					  ventas.Vet_Sys,
					  ventas.Cli_Cod,
					  sucursal.Suc_Sri,
					  if(Aut_Tem='N',Aut_Sri,Vet_Sri)as Aut_Sri,
					  if(Vet_Aut='N','0','1')as ventaFalsa,
					  autorizaci.Pun_Sri
					FROM
					  ventas
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN vendedor ON (ventas.Vnd_Cod = vendedor.Vnd_Cod)
					  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					WHERE
					  ventas.Vet_Est = 'I' AND (Tic_Sri=1 OR Tic_Sri=2) AND
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND sucursal.Emp_Cod = $Par_Sql[2] having ventaFalsa<>0";
			return $sql;
			break;

			/**
			 * Consultando el total de las facturas
			 */
		case 391:
			$sql = "SELECT
				ventas.Vet_Est,Count(ventas.Vet_Cod)as num,
				SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
				SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
			      FROM
				ventas
				INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
			      WHERE
				ventas.Vet_Est = 'A' AND  (Vet_Aut is null || Vet_Aut='' || Vet_Aut='S') AND
				caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
				cliente.Emp_Cod = $Par_Sql[2] AND
				ventas.Tic_Cod='$Par_Sql[3]'
				GROUP BY
				ventas.Vet_Est";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consultando el total de las facturas por punto de impresi�n
			 */
		case 392:
			$sql = "SELECT
					  ventas.Vet_Est,
					  SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
					  sucursal.Suc_Sri
			      FROM
					  ventas
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
					  INNER JOIN puntos_imp ON (caja_aper.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
			      WHERE
					  ventas.Vet_Est = 'A' AND
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
					  cliente.Emp_Cod = $Par_Sql[2]
			      GROUP BY
					  ventas.Vet_Est,
					  sucursal.Suc_Sri";
			//echo "<br>".$sql;
			return $sql;
                case '393_new':
			$sql = "
				SELECT sucursal.Suc_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,COALESCE(SUM(totales.Total),0)AS Total
					FROM sucursal
						LEFT JOIN
						(
						 SELECT sucursal.Suc_Sri,puntos_imp.Suc_Cod,ventas.Vet_Est, IF(autorizaci.Aut_Tem='N', ( IF(Tic_Sri=4,-1,1)*CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) ), 0 ) AS Total
						 FROM ventas
						 INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						 INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						 INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
						 INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
						 INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						 INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						 INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
						 INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
                                                 LEFT JOIN venta_reembolsos AS reemb ON reemb.Vet_Cod=ventas.Vet_Cod
                                                 LEFT JOIN exporta_vent AS expo ON expo.Vet_Cod=ventas.Vet_Cod
						WHERE expo.Vet_Cod IS NULL AND
						  reemb.Vet_Cod IS NULL AND
						  ventas.Vet_Est = 'A' AND autorizaci.Aut_Tem='N' AND Tic_Sri<>0 AND Tic_Sri<>41 AND Tic_Sri<>50 AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND cliente.Emp_Cod = $Par_Sql[2]
						  GROUP BY ventas.Vet_Est, sucursal.Suc_Sri, ventas.Vet_Cod )AS totales ON totales.Suc_Cod=sucursal.Suc_Cod
					WHERE	sucursal.Emp_Cod = $Par_Sql[2]
						  GROUP BY sucursal.Suc_Sri";
			//echo "<br>".$sql;
			return $sql;

		case 393:
			$sql = "
				SELECT sucursal.Suc_Cod,sucursal.Suc_Sri,sucursal.Suc_Des,COALESCE(SUM(totales.Total),0)AS Total
					FROM sucursal
						LEFT JOIN
						(
						 SELECT sucursal.Suc_Sri,puntos_imp.Suc_Cod,ventas.Vet_Est, IF(autorizaci.Aut_Tem='N', ( IF(Tic_Sri=4,-1,1)*CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) ), 0 ) AS Total
						 FROM ventas
						 INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						 INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						 INNER JOIN puntos_imp ON (caja_aper.Pun_Cod=puntos_imp.Pun_Cod)
						 INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
						 INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						 INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						 INNER JOIN autorizaci ON ventas.Aut_Cod=autorizaci.Aut_Cod
						 INNER JOIN tipo_compr ON (autorizaci.Tic_Cod = tipo_compr.Tic_Cod)
						WHERE ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						ventas.Vet_Cod not in(select reemb.Vet_Cod from venta_reembolsos as reemb) AND
						ventas.Vet_Est = 'A'  AND autorizaci.Aut_Tem='N' AND Tic_Sri<>0 AND Tic_Sri<>41 AND Tic_Sri<>50 AND caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND cliente.Emp_Cod = $Par_Sql[2]
						  GROUP BY ventas.Vet_Est, sucursal.Suc_Sri, ventas.Vet_Cod )AS totales ON totales.Suc_Cod=sucursal.Suc_Cod
					WHERE	sucursal.Emp_Cod = $Par_Sql[2]
						  GROUP BY sucursal.Suc_Sri";
			//echo "<br>".$sql;
			return $sql;
			break;

			/**
			 * Anexo trnsaccional 2010 - retencion del iva 100%
			 */
		case '854_group':
			$sql = "SELECT retencion.Cop_Cod, SUM(det_retenc.Ret_Bas) AS Ret_Bas,  det_retenc.Adq_Cod, renta_iva.Ren_Por FROM  retencion
			INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
			INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
			WHERE  renta_iva.Ren_Por = 100 AND retencion.Cop_Cod IN ($Par_Sql[0]) AND  det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A'
			GROUP BY retencion.Cop_Cod;
			/*GROUP BY  det_retenc.Adq_Cod*//* este cambio xq no entiendo xq agrupa por cod adquisicion*/";
			//ChromePhp::log($sql);
			return $sql;
		case 854:
			$sql = "SELECT retencion.Cop_Cod, SUM(det_retenc.Ret_Bas) AS Ret_Bas,  det_retenc.Adq_Cod, renta_iva.Ren_Por FROM  retencion
			 INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
			 INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
			 WHERE  renta_iva.Ren_Por = 100 AND retencion.Cop_Cod = $Par_Sql[0] AND  det_retenc.Ret_Imp = 'I' AND Ret_Est = 'A'
			 /*GROUP BY  det_retenc.Adq_Cod*//* este cambio xq no entiendo xq agrupa por cod adquisicion*/";


			//ChromePhp::log($sql);
			return $sql;
			break;

			/**
			 * Consulta para obtener valor de codigo d eretencion 332// facturas que no tienen retencion
			 */
		case '855_group':
			$sql = "SELECT
				Cop_Cod,
				Ret_Ren_Sri,
				Iva_Por,
				Pro_Cod,
				SUM(Cop_Pru) AS sum_pru,
				/*Calcular el importe total */
				SUM(importe_total) AS importe_total,
				/*Calcular el importe con descuento */
				SUM(importe_con_desc) AS importe_con_desc,
				/*Calcular sub_0 */
				SUM(sub_0) AS Sub0,
				/*Calcular sub_12 */
				SUM(sub_12) AS Sub12

			FROM (
				SELECT
					compras.Cop_Cod,
					Ret_Ren_Sri,
					Pro_Cod,
					Iva_Por,
					Cop_Pru,
                                        /*Calcular el importe total para cada fila */
					CAST(((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) AS decimal(20,2)) AS importe_total,
					/* Calcular el importe con descuento para cada fila */
					CAST(
						(
							((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))
							- (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) * compras.Cop_Des / 100)
						)
						AS decimal(20,2)
					) AS importe_con_desc,
					/* Calcular sub_0 para cada fila */
					CAST(
						IF(Iva_Por = 0,
							IF(Tic_Sri = 4, -1, 1) *
							CAST(
								((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))  -  (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))* Cop_Des/100)
								AS decimal(20,2)
							),
							0
						) AS decimal(20,2)
					) AS sub_0,
					/*Calcular sub_12 para cada fila */
					CAST(
						IF(Iva_Por != 0,
							IF(Tic_Sri = 4, -1, 1) *
							CAST(
								((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) -  (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))* Cop_Des/100)
								AS decimal(20,2)
							),
							0
						) AS decimal(20,2)
					) AS sub_12

				FROM
					det_compra
					INNER JOIN compras ON compras.Cop_Cod = det_compra.Cop_Cod
					INNER JOIN tipo_compr ON compras.Tic_Cod = tipo_compr.Tic_Cod
					INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
				WHERE
					compras.Tic_Cod != 4
					AND CONCAT(`compras`.`Cop_Cod`,'_',`Cop_Int`) NOT IN (
						SELECT CONCAT(`retencion`.`Cop_Cod`,'_',`det_retenc`.`Ret_Int`)
						FROM retencion
						INNER JOIN det_retenc ON retencion.Ret_Cod = det_retenc.Ret_Cod
						WHERE det_retenc.Ret_Imp = 'R'
						  AND retencion.Ret_Est = 'A'
						  AND retencion.Cop_Cod IN ($Par_Sql[0])
					)
					AND det_compra.Cop_Cod IN ($Par_Sql[0])
			) AS subquery
			GROUP BY Cop_Cod, Ret_Ren_Sri";


			//ChromePhp::log($sql);
			//echo $sql."<br>";die();
			//if($_SESSION['Ses_Prs_Cod']==1) echo $sql."<br>";
			return $sql;
		case 855:
			/*$sql="SELECT dato.Cop_Cod, dato.Cop_Int,(dato.CopImp-(dato.CopImp*Cop_Des/100))AS Cop_Imp from (
				SELECT SUM((Cop_Can*Cop_Pru)- (((Cop_Can*Cop_Pru)*Cop_Dec)/100)) as CopImp, det_compra.Cop_Cod, det_compra.Cop_Int
				FROM det_compra
				INNER JOIN compras ON compras.Cop_Cod=det_compra.Cop_Cod
				WHERE compras.Tic_Cod!=4 AND compras.Tic_Cod!=5 AND det_compra.Cop_Int NOT IN
				  (SELECT det_retenc.Ret_Int FROM retencion INNER JOIN det_retenc ON
				   (retencion.Ret_Cod = det_retenc.Ret_Cod AND det_retenc.Ret_Imp='R')
				   WHERE retencion.Ret_Est = 'A' AND retencion.Cop_Cod =$Par_Sql[0])
				 AND
				 det_compra.Cop_Cod = $Par_Sql[0]
				GROUP by Cop_Cod ) AS dato
				INNER JOIN compras ON compras.Cop_Cod=dato.Cop_Cod"; // AND det_compra.Adq_Cod!=13*/


			/*$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
			$ice = "CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
			$iva = "( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";

				$sql = "SELECT Iva_Por,
					SUM((dato.sub_0))AS Sub0 ,
					SUM((dato.sub_12))AS Sub12,
					SUM(dato.IvaTot)AS IvaTot
					from (
						SELECT Iva_Por,compras.Cop_Cod,
							    CAST( SUM(IF(Iva_Por = 0, IF(Tic_Sri=4,-1,1)* $importe_con_desc , '0'))  AS decimal(20,2))AS sub_0,
								CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)* $importe_con_desc , '0'))  AS decimal(20,2))AS sub_12,
								CAST( SUM( IF(Tic_Sri=4,-1,1)* $importe * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
								CAST( SUM(IF(Cop_Ice != 0, IF(Tic_Sri=4,-1,1)* $ice, 0))  AS decimal(20,2)) AS IceTot,
								CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)* $iva, 0)) AS decimal(20,2))  AS IvaTot,

								CAST( SUM( IF(Tic_Sri=4,-1,1)* (
										$importe_con_desc /* IMPORTE */
			/*	+ $ice /* ICE */
			/*	+ $iva /* IVA */
			/*	)
								)  AS decimal(20,2)) AS total
						FROM compras
						INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
						INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
						INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
						WHERE compras.Tic_Cod!=4 AND compras.Cop_Cod= '$Par_Sql[0]' AND det_compra.Cop_Int NOT IN
							(SELECT det_retenc.Ret_Int
							 FROM retencion INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod AND det_retenc.Ret_Imp='R')
					         WHERE retencion.Ret_Est = 'A' AND retencion.Cop_Cod =$Par_Sql[0])
						GROUP BY compras.Cop_Cod) AS dato
					INNER JOIN compras ON compras.Cop_Cod=dato.Cop_Cod GROUP BY compras.Cop_Cod";		*/


			$sql = "SELECT
				Ret_Ren_Sri,
				Iva_Por,
				Pro_Cod,
				SUM(Cop_Pru) AS sum_pru,
				-- Calcular el importe total
				SUM(importe_total) AS importe_total,
				-- Calcular el importe con descuento
				SUM(importe_con_desc) AS importe_con_desc,
				-- Calcular sub_0
				SUM(sub_0) AS Sub0,
				-- Calcular sub_12
				SUM(sub_12) AS Sub12

			FROM (
				SELECT
					Ret_Ren_Sri,
					Pro_Cod,
					Iva_Por,
					Cop_Pru,
					-- Calcular el importe total para cada fila
					CAST(((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) AS decimal(20,2)) AS importe_total,
					-- Calcular el importe con descuento para cada fila
					CAST(
						(
							((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))
							- (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) * compras.Cop_Des / 100)
						)
						AS decimal(20,2)
					) AS importe_con_desc,
					-- Calcular sub_0 para cada fila
					CAST(
						IF(Iva_Por = 0,
							IF(Tic_Sri = 4, -1, 1) *
							CAST(
								((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))  -  (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))* Cop_Des/100)
								AS decimal(20,2)
							),
							0
						) AS decimal(20,2)
					) AS sub_0,
					-- Calcular sub_12 para cada fila
					CAST(
						IF(Iva_Por != 0,
							IF(Tic_Sri = 4, -1, 1) *
							CAST(
								((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100)) -  (((Cop_Pru * Cop_Can) - ((Cop_Pru * Cop_Can) * Cop_Dec / 100))* Cop_Des/100)
								AS decimal(20,2)
							),
							0
						) AS decimal(20,2)
					) AS sub_12

				FROM
					det_compra
					INNER JOIN compras ON compras.Cop_Cod = det_compra.Cop_Cod
					INNER JOIN tipo_compr ON compras.Tic_Cod = tipo_compr.Tic_Cod
					INNER JOIN iva ON det_compra.Iva_Cod = iva.Iva_Cod
				WHERE
					compras.Tic_Cod != 4
					AND Cop_Int NOT IN (
						SELECT det_retenc.Ret_Int
						FROM retencion
						INNER JOIN det_retenc ON retencion.Ret_Cod = det_retenc.Ret_Cod
						WHERE det_retenc.Ret_Imp = 'R'
						  AND retencion.Ret_Est = 'A'
						  AND retencion.Cop_Cod = $Par_Sql[0]
					)
					AND det_compra.Cop_Cod = $Par_Sql[0]
			) AS subquery
			GROUP BY  Ret_Ren_Sri";
			//if($_SESSION['Ses_Prs_Cod']==1) echo $sql."<br>";
			return $sql;

			/**
			 * Consulta tipo de pago del SRI
			 */
		case '856_group':
			$sql = "SELECT Tpc_Cod,Tpc_Des,Tpc_Sri FROM tipopagocom WHERE Tpc_Est='A'";
			//echo $sql."<br>";die();
			return $sql;

		case 856:
			$sql = "SELECT Tpc_Cod,Tpc_Des,Tpc_Sri FROM tipopagocom WHERE Tpc_Cod=$Par_Sql[0] AND Tpc_Est='A'";

			//ChromePhp::log($sql);
			return $sql;

			/**
			 * Consulta de todas las compras de un cliente por mes
			 */
		case 857:
			$sql = "SELECT
				  ventas.Vet_Cod,caja_aper.Caj_Fec,ventas.Vet_Num,ventas.Tic_Cod
				FROM
				  caja_aper
				  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
				  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				WHERE
				  ventas.Vet_Est = 'A' AND
				  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
				  cliente.Emp_Cod = '$Par_Sql[2]' AND
				  persona.Prs_Ced='$Par_Sql[3]' AND
				  ventas.Tic_Cod='$Par_Sql[4]' AND Aut_Tem='$Par_Sql[5]'";
			//echo $sql."<br>";
			return $sql;

			/**
			 * Consulta de los detalles de la venta segun la sql 857
			 */
                case '858_group_final':
			$sql = "SELECT
                            SUM(Tot_Imp)AS Tot_Imp,
                            SUM(Tot_Iva)AS Tot_Iva,
                            CONCAT(Cli_Cod,'_',Aut_Tem,'_',Tic_Sri_Aux)AS Aux_Key
                          FROM(
                                SELECT
                                        ventas.Cli_Cod,Aut_Tem,ventas.Tic_Cod,IF(reemb.Vet_Cod IS NOT NULL,41,Tic_Sri)AS Tic_Sri_Aux,
					
					/*CAST( SUM( IF(rent.Ren_Por IS NULL ,0, (rent.Ren_Por/100)* CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) * ventas.Vet_Des / 100 ))) AS DECIMAL(20,2) ) AS Tot_Imp,*/
					CAST(SUM(IF( rent.Ren_Por IS NULL, 0, (rent.Ren_Por / 100) * (CAST(((Vet_Pru * Vet_Can) - ((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2)) - (CAST( ((Vet_Pru * Vet_Can) - ((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) * ventas.Vet_Des / 100 ) ))) AS DECIMAL(20,2)) AS Tot_Imp,
					
					CAST( SUM(IF(Iva_Por = 0 OR riva.Ren_Por IS NULL,0, (riva.Ren_Por/100)*( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) * ventas.Vet_Des / 100 )) AS DECIMAL(20, 2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec / 100)) AS DECIMAL(20, 2) ) * ventas.Vet_Des / 100 )) AS DECIMAL(20, 2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL, ventas_det.Vet_Ice / 100, 0)) AS DECIMAL(20, 2) ) AS DECIMAL(20, 2) )* Iva_Por / 100 ))) AS DECIMAL(20,2)) AS Tot_Iva,
			
					iva.Iva_Por,
					ventas_det.Ren_Cod,
					rent.Ren_Sri AS Rent_Ren_Sri,rent.Ren_Por AS Rent_Ren_Por,
					ventas_det.Ren_Iva,
					riva.Ren_Sri AS Riva_Ren_Sri,riva.Ren_Por AS Riva_Ren_Por
				FROM
					ventas
                                        INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
                                        INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
                                        INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
                                        LEFT JOIN venta_reembolsos AS reemb ON reemb.Vet_Cod=ventas.Vet_Cod
                                        LEFT JOIN exporta_vent AS expo ON expo.Vet_Cod=ventas.Vet_Cod
					INNER JOIN ventas_det ON ventas.Vet_Cod = ventas_det.Vet_Cod
					INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					LEFT JOIN renta_iva AS rent ON rent.Ren_Cod = ventas_det.Ren_Cod
					LEFT JOIN renta_iva AS riva ON riva.Ren_Cod = ventas_det.Ren_Iva
				WHERE
                                        ventas.Vet_Est = 'A' AND expo.Vet_Cod IS NULL AND
					caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
                                        ventas.Cli_Cod IN(".(!empty($Par_Sql[2])?$Par_Sql[2]:'NULL').")
				GROUP BY ventas.Cli_Cod,
					 ventas.Vet_Cod
                             )AS tbl GROUP BY CONCAT(Cli_Cod,'_',Aut_Tem,'_',Tic_Sri_Aux)";
			//echo $sql."<br>";die();
			return $sql;
		case '858_group':
			$sql = "SELECT
					ventas.Vet_Cod,
					ventas.Vet_Est,
					
					CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Tot_Imp,
					
					
					CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Tot_Iva,
					iva.Iva_Por,
					ventas_det.Ren_Cod,
					rent.Ren_Sri AS Rent_Ren_Sri,rent.Ren_Por AS Rent_Ren_Por,
					ventas_det.Ren_Iva,
					riva.Ren_Sri AS Riva_Ren_Sri,riva.Ren_Por AS Riva_Ren_Por
				FROM
					ventas
					INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					LEFT JOIN renta_iva AS rent ON rent.Ren_Cod = ventas_det.Ren_Cod
					LEFT JOIN renta_iva AS riva ON riva.Ren_Cod = ventas_det.Ren_Iva
				WHERE
					ventas.Vet_Cod IN ($Par_Sql[0])
				GROUP BY ventas.Vet_Cod,CONCAT(IFNULL(ventas_det.Ren_Cod,'0'),'_',IFNULL(ventas_det.Ren_Iva,'0'))";
			//echo $sql."<br>";die();
			return $sql;
		case 858:
			$sql = "SELECT
					 ventas.Vet_Est,
					 CAST( SUM( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) 
					 - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) AS Tot_Imp,


					 CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) + CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Tot_Iva,
					 iva.Iva_Por,
					 ventas_det.Ren_Cod,
					 ventas_det.Ren_Iva
				FROM
					 ventas
					 INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					 INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				WHERE
					 ventas.Vet_Cod='$Par_Sql[0]' group by Ren_Cod,Ren_Iva";
			//echo $sql."<br>";
			return $sql;

			/**
			 * Consulta los % para la retencion
			 */
		case 859:
			$sql = "SELECT Ren_Cod,Ren_Sri,Ren_Por FROM renta_iva WHERE Ren_Cod = '$Par_Sql[0]' AND Ren_Est='A'";
			return $sql;
			break;

			/**
			 * Consulta los peridos existentes de una empresa
			 */
		case 860:
			$sql = "SELECT DISTINCT
			date_format(perio_cont.Pec_Fei,'%Y')as Pec_Fei,
			plan_cuenta.Emp_Cod,
			perio_cont.Pec_Cod
		  FROM
			plan_cuenta
			INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
		  WHERE
			plan_cuenta.Emp_Cod = '$Par_Sql[0]' order by Pec_Fei desc";
			return $sql;
			break;

			/**
			 * Consultando los comprobantes segun Tic_Cod
			 */
		case 861:
			$sql = "SELECT
						ventas.Vet_Cod,
						ventas.Vet_Sys,
						tipo_compr.Tic_Sri
					FROM
						caja_aper
						INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
						INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					WHERE
						ventas.Cli_Cod = '$Par_Sql[0]' AND
						ventas.Vet_Est = 'A' AND
						caja_aper.Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND ventas.Tic_Cod='$Par_Sql[3]'";
			return $sql;
			break;

			/**
			 * Consultando los comprobantes segun Tic_Cod
			 */
		case 862:
			$sql = "SELECT
				  tipo_compr.Tic_Cod,tipo_compr.Tic_Sri
			       FROM
				  tipo_compr
			       WHERE
				  tipo_compr.Tic_Sri='$Par_Sql[0]' OR tipo_compr.Tic_Sri='$Par_Sql[1]' OR tipo_compr.Tic_Sri='$Par_Sql[2]'";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consultando si la empresa genera facturas electronicos
			 */
		case 863:
			$sql = "SELECT
					Cof_Cod, Cof_Gce
				FROM
					confi_fact
				WHERE
					Emp_Cod='$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 * Consulta los % para la retencion
			 */
		case 864:
			$sql = "SELECT Ren_Cod,Ren_Sri,Ren_Por FROM renta_iva WHERE Ren_Cod = '$Par_Sql[0]'";
			return $sql;
			break;

			/**
			 * Consulta tipo de comprobantes registrados
			 */
		case 865:
			$sql = "SELECT tipo_compr.Tic_Cod, LPAD(tipo_compr.Tic_Sri, 2, 0) AS Tic_Sri, tipo_compr.Tic_Des, count(Cop_Cod) AS total
				  FROM
					  tipo_compr
					  INNER JOIN compras ON (tipo_compr.Tic_Cod = compras.Tic_Cod)
					  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  WHERE
					  compras.Cop_Est='A' AND proveedore.Emp_Cod = '$Par_Sql[0]' AND
					   (tipo_compr.Tic_Sri = 1 OR tipo_compr.Tic_Sri = 2 OR tipo_compr.Tic_Sri = 3 OR tipo_compr.Tic_Sri = 4 OR tipo_compr.Tic_Sri = 5 OR tipo_compr.Tic_Sri = 11 OR tipo_compr.Tic_Sri = 19 OR tipo_compr.Tic_Sri = 41) AND
					  Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' group by Tic_Cod";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta Base imponible 0% y 12% Compras
			 */
		case 866:
			$sql = "SELECT SUM(det_compra.Cop_Imp-(det_compra.Cop_Imp*det_compra.Cop_Dec/100)) as Importe
				FROM det_compra, iva, compras, proveedore ,tipo_compr
				WHERE compras.Tic_Cod = tipo_compr.Tic_Cod AND (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
				AND det_compra.Iva_Cod=iva.Iva_Cod AND compras.Tic_Cod='$Par_Sql[2]'
				AND compras.Cop_Cod=det_compra.Cop_Cod
				AND compras.Prv_Cod = proveedore.Prv_Cod
				AND proveedore.Emp_Cod= '$Par_Sql[3]' AND Cop_Est='A'
				AND iva.Iva_Por = '$Par_Sql[4]' ";
			//echo "<br>".$sql;
			return $sql;
			break;

			/**
			 * Consulta total de ventas
			 */
		case 867:
			$sql = "SELECT
					  tipo_compr.Tic_Cod,
					  if(tipo_compr.Tic_Cod='1','18',LPAD(tipo_compr.Tic_Sri, 2, 0)) AS Tic_Sri,
					  if(tipo_compr.Tic_Cod='1',
					  (select tc.Tic_Des from tipo_compr as tc where tc.Tic_Sri='18'),tipo_compr.Tic_Des)as Tic_Des,
					  count( ventas.Cli_Cod) AS total
					FROM
					  tipo_compr
					  INNER JOIN ventas ON (tipo_compr.Tic_Cod = ventas.Tic_Cod)
					  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					WHERE
					  Emp_Cod = '$Par_Sql[0]' AND (tipo_compr.Tic_Sri <> '0' OR tipo_compr.Tic_Sri <> '00') AND
					  ventas.Vet_Est = 'A' AND Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]'
					GROUP BY tipo_compr.Tic_Cod";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta Base imponible 0% y 12% Ventas
			 */
		case 868:
			$sql = "SELECT SUM(ventas_det.Vet_Imp-((ventas_det.Vet_Imp*ventas_det.Vet_Dec/100)+(ventas_det.Vet_Imp*ventas.Vet_Des/100))) as Importe
					FROM ventas_det, iva, ventas, cliente ,tipo_compr,caja_aper
					WHERE ventas.Tic_Cod = tipo_compr.Tic_Cod AND (Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
					AND ventas_det.Iva_Cod=iva.Iva_Cod AND ventas.Tic_Cod='$Par_Sql[2]'
					AND ventas.Vet_Cod=ventas_det.Vet_Cod
					AND ventas.Caj_Cod=caja_aper.Caj_Cod
					AND ventas.Cli_Cod = cliente.Cli_Cod
					AND cliente.Emp_Cod= '$Par_Sql[3]' AND Vet_Est='A'
					AND iva.Iva_Por = '$Par_Sql[4]' ";
			//echo "<br>".$sql;
			return $sql;
			break;

			/**
			 * Consulta los codgios de Renta que intervienes en Compras
			 */
		case 869:
			$sql = "SELECT
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,count(DISTINCT det_retenc.Ret_Cod)as total
				FROM
				  proveedore
				  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				WHERE
				  Emp_Cod = '$Par_Sql[0]' AND Ret_Est='A' AND Cop_Est='A' AND Ren_Ret='$Par_Sql[1]' AND
				  Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'
				GROUP BY Ren_Sri UNION
				SELECT '106'as Ren_Cod, '332'as Ren_Sri, 'OTRAS COMPRAS DE BIENES Y SERVICIOS NO SUJETAS A RETENCION'as Ren_Con,count(compras.Cop_Cod)as total
				from proveedore
				INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod AND Ret_Est='A')
				INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
				where Emp_Cod='$Par_Sql[0]' AND Cop_Est='A' AND (compras.Tic_Cod!=4) AND Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'AND retencion.Cop_Cod is null
				order by Ren_Sri Asc";
			//echo "<br>".$sql;
			return $sql;
			break;

			/**
			 * Consulta los codgios de Iva que intervienes en Compras
			 */
		case 870:
			$sql = "SELECT 'COMPRAS' as DetRen,Ren_Con,Ren_Cod  FROM renta_iva WHERE Ren_Ret='I' AND Ren_Est='A' order by Ren_Por Asc";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta los codgios de Iva que intervienes en Compras
			 */
		case 871:
			$sql = "SELECT
						sum(((Ret_Bas*Ren_Por)/100))as valor
					FROM compras
						INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
						INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
						INNER JOIN det_retenc ON (retencion.Ret_Cod=det_retenc.Ret_Cod)
						INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
					WHERE
						proveedore.Emp_Cod='$Par_Sql[0]' AND Ret_Est='A' AND (Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND renta_iva.Ren_Cod='$Par_Sql[3]' group by Ren_Por ";
			//echo $sql;
			return $sql;
			break;

		case 872:
			$sql = "SELECT
				  ventas.Vet_Cod,caja_aper.Caj_Fec,ventas.Vet_Num
				FROM
				  caja_aper
				  INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
				  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
				  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  ventas.Vet_Est = 'A' AND
				  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
				  cliente.Emp_Cod = '$Par_Sql[2]' AND (tipo_compr.Tic_Sri<>'0' or tipo_compr.Tic_Sri<>'00')";
			//echo $sql."<br>";
			return $sql;
			break;

			/*consulta los montos de la RENTA de compras*/
		case 873:
			$sql = "SELECT renta.Ren_Cod , ROUND(SUM(renta.base),2)AS base, ROUND(SUM(renta.valor),2)AS valor FROM (
                    SELECT renta_iva.Ren_Cod,sum(Ret_Bas)as base,ROUND(sum((Ret_Bas * Ren_Por) / 100),2) AS valor
                    FROM
					  proveedore
					  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
					  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
					  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
                    WHERE
					  proveedore.Emp_Cod = '$Par_Sql[0]' AND retencion.Ret_Est = 'A' AND compras.Cop_Est = 'A' AND
					  renta_iva.Ren_Cod = '$Par_Sql[1]' AND compras.Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]'
                    GROUP BY compras.Cop_Cod
                ) as renta
                GROUP BY renta.Ren_Cod";
			//if($Par_Sql[1]=='76'){echo $sql."<br>";}

			return $sql;
			break;

		case 874:
			/**
			 * consulta compras
			 */
			$sql = "SELECT compras.Cop_Cod,compras.Tpc_Cod, sustento.Tri_Sri, Prs_Ced, Ide_Prc, Tic_Sri, Cop_Fec, Cop_Num, Cop_Imf,
	Cop_Aut, Cop_Cad, Cop_Reg, Cop_Ntd, Cop_Nns, Cop_Nna
	FROM compras, sustento, proveedore, persona, identifica, tipo_compr
	WHERE compras.Tri_Cod = sustento.Tri_Cod AND compras.Prv_Cod = proveedore.Prv_Cod AND
	proveedore.Prs_Cod = persona.Prs_Cod AND persona.Ide_Cod = identifica.Ide_Cod AND
	compras.Tic_Cod = tipo_compr.Tic_Cod AND (Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
	AND compras.Cop_Est = 'A' AND (Tic_Sri = 1 OR Tic_Sri = 2 OR Tic_Sri = 5 OR Tic_Sri = 3 OR Tic_Sri = 4 OR Tic_Sri = 11 OR Tic_Sri = 19 OR Tic_Sri = 41 OR Tic_Sri = 26) AND compras.Tri_Cod != 1 AND proveedore.Emp_Cod = $Par_Sql[2]";
			//echo "<br>".$sql;
			return $sql;
			break;

			/*consulta datos de la persona por cedula*/
		case 875:
			$sql = "SELECT Prs_Nom, Prs_Ape,Prs_Ced,Prs_Cod
				  FROM
					  persona
				  WHERE
					  Prs_Ced='$Par_Sql[0]' AND Prs_Est='A'";
			//echo $sql."<br>";
			return $sql;
			break;

		case 876:
			/**
			 * Consulta del iva mayor a cero (0)
			 */
			$sql = "SELECT Iva_Cod, Iva_Sri, Iva_Por FROM iva WHERE iva.Iva_Por > 0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin)";
			//echo $sql;
			return $sql;
			break;

		case 877:
			/**
			 * Consulta del iva mayor a cero (0)
			 */
			$sql = "SELECT DISTINCT iva.Iva_Cod,iva.Iva_PorFROM
					  proveedore
					  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
					  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
					  INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
					WHERE Emp_Cod = '81' AND $Par_Sql[0] BETWEEN Iva_Ini AND Iva_Fin";
			//echo $sql;
			return $sql;
			break;

			/**
			 * Consulta Base imponible 0% y 12% Compras JOSE AMBULUDI
			 */
		case 878:
			if ($Par_Sql[4] > 0) {
				$ivajose = "iva.Iva_Por>0";
			} else {
				$ivajose = "iva.Iva_Por=0";
			}
			$sql = "SELECT CAST( SUM((Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100))  AS decimal(20,2))AS Importe,
CAST( SUM((((Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100))+((Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100))*(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0)))*Iva_Por/100) AS decimal(20,2)) AS Iva_Val
				FROM det_compra, iva, compras, proveedore ,tipo_compr
				WHERE compras.Tic_Cod = tipo_compr.Tic_Cod AND (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]')
				AND det_compra.Iva_Cod=iva.Iva_Cod AND compras.Tic_Cod='$Par_Sql[2]'
				AND compras.Cop_Cod=det_compra.Cop_Cod
				AND compras.Prv_Cod = proveedore.Prv_Cod
				AND proveedore.Emp_Cod= '$Par_Sql[3]' AND Cop_Est='A'
				AND $ivajose";
			//echo "<br>".$sql;
			return $sql;

			/*Consultamos los diferentes tipo de pagos SRI de las ventas*/
		case '879_group':
			$sql = "SELECT CONCAT(ventas.Cli_Cod,'_',ventas.Tic_Cod)AS Aux_Key,Tpc_Sri,ventas.Vet_Cod,ventas.Cli_Cod,caja_aper.Caj_Fec
					FROM
						tipopagocom
						RIGHT OUTER JOIN ventas ON (tipopagocom.Tpc_Cod = ventas.Tpc_Cod)
                                                INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
						INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
                                                LEFT JOIN exporta_vent AS expo ON expo.Vet_Cod=ventas.Vet_Cod
					WHERE
                                                expo.Vet_Cod IS NULL AND
						caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
						cliente.Emp_Cod = '$Par_Sql[2]' AND
						ventas.Cli_Cod IN (".($Par_Sql[3]?:'NULL').") AND
						/*ventas.Tic_Cod IN (".(!isset($Par_Sql[4])?'NULL':($Par_Sql[4]?:'NULL')).") AND*/
                                                ventas.Vet_Est ='A' AND tipo_compr.Tic_Sri IN('1', '2', '4', '5', '41')
					GROUP By CONCAT(ventas.Cli_Cod,'_',ventas.Tic_Cod), Tpc_Sri";
			return $sql;
		case 879:
			$sql = "SELECT Tpc_Sri,ventas.Vet_Cod,ventas.Cli_Cod,caja_aper.Caj_Fec
				  FROM
					  tipopagocom
					  RIGHT OUTER JOIN ventas ON (tipopagocom.Tpc_Cod = ventas.Tpc_Cod)
					  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
				  WHERE
					  caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
					  cliente.Emp_Cod = '$Par_Sql[2]' AND
					  persona.Prs_Ced = '$Par_Sql[3]' AND
					  ventas.Tic_Cod = '$Par_Sql[4]' AND Vet_Est='A'
				  GROUP By Tpc_Sri";
			return $sql;

			/**
			 * Consultando el total de las facturas ventas filtrado por Aut_Tem
			 */
		case 880:
			$sql = "SELECT
				ventas.Vet_Est,
				SUM(ROUND((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))), 2)) AS Total,
				SUM(((ventas_det.Vet_Imp - (((Vet_Imp * Vet_Des) / 100) + ((Vet_Imp * Vet_Dec) / 100))) * Iva_Por) / 100) AS Iva
			      FROM
				ventas
				INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
				INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
				INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
				INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
				INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
			      WHERE
				ventas.Vet_Est = 'A' AND
				caja_aper.Caj_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND
				cliente.Emp_Cod = $Par_Sql[2] AND
				ventas.Tic_Cod='$Par_Sql[3]' AND Aut_Tem='N'
				GROUP BY
				ventas.Vet_Est";
			//echo $sql;
			return $sql;
			break;

		case 881:
			/**
			 * Consulta de los datos AIR
			 */
			$sql = "SELECT Ren_Sri, sum(Ret_Bas) AS Ret_Bas,  sum(Ret_Bas * Ren_Por)/sum(Ret_Bas) as Ren_Por, sum((Ret_Bas * Ren_Por)/100) as Val_Air, Ret_Num,retencion.Ret_Cod,Ret_Uca,Ret_Pca, Ret_Sri,Aut_Tem,Aut_Sri, Ret_Fec
								FROM retencion, det_retenc, renta_iva, autorizaci WHERE retencion.Ret_Cod = det_retenc.Ret_Cod
								AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND retencion.Aut_Cod = autorizaci.Aut_Cod
								AND retencion.Ret_Cod = '$Par_Sql[0]' AND Ren_Sri='$Par_Sql[1]' AND renta_iva.Ren_Ret = 'R' AND Ret_Est = 'A'
								GROUP BY Cop_Cod";
			//echo $sql."<br>";
			return $sql;
			break;
		case 882:
			/**
			 * Consulta de los datos AIR
			 */
			$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
			$ice = "CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
			$iva = "( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";
			$sql = "SELECT Iva_Por,
					SUM((dato.sub_0))AS Sub0 ,
					SUM((dato.sub_12))AS Sub12,
					SUM(dato.IvaTot)AS IvaTot
					from (
						SELECT Iva_Por,compras.Cop_Cod,compras.Tic_Cod,
							    CAST( SUM(IF(Iva_Por = 0,  $importe_con_desc , '0'))  AS decimal(20,2))AS sub_0,
								CAST( SUM(IF(Iva_Por != 0,  $importe_con_desc , '0'))  AS decimal(20,2))AS sub_12,
								CAST( SUM(  $importe * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
								CAST( SUM(IF(Cop_Ice != 0,  $ice, 0))  AS decimal(20,2)) AS IceTot,
								CAST( SUM(IF(Iva_Por != 0,  $iva, 0)) AS decimal(20,2))  AS IvaTot,

								CAST( SUM(  (
										$importe_con_desc /* IMPORTE */
										+ $ice /* ICE */
										+ $iva /* IVA */
									)
								)  AS decimal(20,2)) AS total
						FROM compras
						INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
						INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
						INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
						INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
						WHERE Cop_Est='A' AND (compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]') AND compras.Tic_Cod='$Par_Sql[2]' AND proveedore.Emp_Cod='$Par_Sql[3]'
						GROUP BY compras.Cop_Cod) AS dato
					INNER JOIN compras ON compras.Cop_Cod=dato.Cop_Cod group by compras.Tic_Cod ";
			//echo $sql."<br>";
			return $sql;
			break;
		case 883:
			/**
			 * Consulta ventas agrupada por Tic_Cod
			 */
			$sql = "SELECT
						  if(Tic_Sri=1,18,LPAD(Tic_Sri,2,0))as Tic_Sri,Tic_Des,COUNT(ventas.Vet_Cod)as total,
						  CAST(SUM(IF(Iva_Por=0, CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 ),0)) AS decimal(20,2)) as Importe0,
						  CAST(SUM(IF(Iva_Por<>0, CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 ),0)) AS decimal(20,2)) as Importe12,
						  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) +
						  CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Tot_Iva
						FROM
						  ventas
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
						WHERE ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						  ventas.Vet_Cod not in(select reemb.Vet_Cod from venta_reembolsos as reemb) AND
						  Tic_Sri<>0 AND Emp_Cod=$Par_Sql[0] AND Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND Vet_Est='A'
						GROUP BY ventas.Tic_Cod   ";
			//echo $sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
			break;
		case 884:
			/**
			 * Consulta ventas agrupada por Tic_Cod
			 */
			$sql = "SELECT
						  LPAD(tipo_compr.Tic_Sri, 2, 0)as Tic_Sri,Tic_Des,COUNT(ventas.Vet_Cod)as total,
						  CAST(SUM(CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2)) as Importe,
						  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) +
						  CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Tot_Iva
						FROM
						  ventas
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
						  INNER JOIN exporta_vent ON (ventas.Vet_Cod = exporta_vent.Vet_Cod)
						WHERE Tic_Sri<>0 AND Emp_Cod=$Par_Sql[0] AND Eve_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND Vet_Est='A'
						GROUP BY ventas.Tic_Cod";
			//echo $sql."<br>";
			return $sql;
			break;

			/*   E X P O R T A C I O N E S  */
		case 885:
			/**
			 * Consulta ventas asociadas a la exportacion
			 */
			$sql = "SELECT Suc_Sri,Pun_Sri,LPAD(Vet_Num,9,0)as Vet_Num,LPAD(tipo_compr.Tic_Sri, 2, 0)as Tic_Sri,
						   if(Aut_Tem='N',Aut_Sri,Vet_Sri)as Aut_Num,
						   CAST(SUM(CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2))+
						   (CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) +
						   CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)))as total,CAST(Reg_Den as char(100))as RegDen,Paf_Sri,Pas_Sri,
						   exporta_vent.*, exporta_regi.*, exporta_ingr.*,exporta_dist.*,if(Eve_Rel='N','NO','SI')as EveRel, DATE_FORMAT(Eve_Fec,'%d/%m/%Y')as Eve_Fec, Prs_Ape,Prs_Nom,Prs_Ced,Ide_Pre,if(Cli_Tic='N','01','02')as Cli_Tic,DATE_FORMAT(Caj_Fec,'%d/%m/%Y')as Caj_Fec
					FROM
					  ventas
					  INNER JOIN exporta_vent ON (ventas.Vet_Cod = exporta_vent.Vet_Cod)
					  INNER JOIN cliente ON (cliente.Cli_Cod = ventas.Cli_Cod)
					  LEFT JOIN exporta_regi ON (exporta_vent.Ere_Cod = exporta_regi.Ere_Cod)
					  LEFT JOIN exporta_ingr ON (exporta_vent.Ein_Cod = exporta_ingr.Ein_Cod)
					  LEFT JOIN exporta_dist ON (exporta_vent.Edi_Cod = exporta_dist.Edi_Cod)
					  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
					  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					  INNER JOIN autorizaci ON (ventas.Aut_Cod = autorizaci.Aut_Cod)
					  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
					  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
					  INNER JOIN pais ON (exporta_vent.Pas_Cod = pais.Pas_Cod)
					  LEFT JOIN paraisos_fisc ON (exporta_vent.Paf_Cod = paraisos_fisc.Paf_Cod)
					WHERE
					  cliente.Emp_Cod='$Par_Sql[0]' AND Eve_Est='A' AND Vet_Est='A' AND Eve_Fec between '$Par_Sql[1]' AND '$Par_Sql[2]' GROUP BY ventas.Vet_Cod ORDER BY Vet_Num";
			//if($_SESSION['Ses_Prs_Cod']==1) echo $sql."<br>";
			return $sql;
			break;

		case 886:
			/**
			 * Consulta de Retenciones Bancarias en Ventas
			 */
			$sql = "SELECT
					  retcre_vta.Cli_Cod,cliente.regtot,Rvt_Tem,Prs_Ced,Ide_Prv,
					  SUM(IF(`Ren_Ret` = 'R', CAST(((`Rvt_Bas` * `Ren_Por`) / 100) AS DECIMAL(10,2)), 0)) AS 'renTotal',
					  SUM(IF(`Ren_Ret` = 'I', CAST(((`Rvt_Bas` * `Ren_Por`) / 100) AS DECIMAL(10,2)), 0)) AS 'ivaTotal'
					FROM
					  retcre_vta
					  INNER JOIN retcrevta_det ON (retcre_vta.Rvt_Cod = retcrevta_det.Rvt_Cod)
					  INNER JOIN renta_iva ON (retcrevta_det.Ren_Cod = renta_iva.Ren_Cod)
					  INNER JOIN (
					  SELECT retcre_vta.Cli_Cod,Prs_Cod,Emp_Cod, count(retcre_vta.Cli_Cod)as regtot FROM retcre_vta
						INNER JOIN cliente ON (cliente.Cli_Cod = retcre_vta.Cli_Cod)
						WHERE cliente.Emp_Cod='$Par_Sql[0]' AND Rvt_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]'
						GROUP BY retcre_vta.Cli_Cod,Rvt_Tem )as cliente ON (cliente.Cli_Cod = retcre_vta.Cli_Cod)
					  INNER JOIN persona ON (persona.Prs_Cod = cliente.Prs_Cod)
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					WHERE Rvt_Est='A' AND cliente.Emp_Cod='$Par_Sql[0]' AND Rvt_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]' GROUP BY Cli_Cod,Rvt_Tem";
			//echo $sql."<br>";
			return $sql;

		case '887_group':
			/**
			 * Consulta formas de pago de Retenciones Bancarias en Ventas
			 */
			$sql = "SELECT cliente.Cli_Cod,Tpc_Sri,Tpc_Des
					FROM tipopagocom
						INNER JOIN retcre_vta ON (tipopagocom.Tpc_Cod = retcre_vta.Tpc_Cod)
						INNER JOIN cliente ON (retcre_vta.Cli_Cod = cliente.Cli_Cod)
					WHERE Emp_Cod='$Par_Sql[0]' AND Rvt_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]' AND cliente.Cli_Cod IN (".($Par_Sql[3]?:'NULL').")
					GROUP BY cliente.Cli_Cod,retcre_vta.Tpc_Cod,Rvt_Tem";
			//echo $sql."<br>";
			return $sql;
		case 887:
			/**
			 * Consulta formas de pago de Retenciones Bancarias en Ventas
			 */
			$sql = "SELECT Tpc_Sri,Tpc_Des
					FROM tipopagocom
					  INNER JOIN retcre_vta ON (tipopagocom.Tpc_Cod = retcre_vta.Tpc_Cod)
					  INNER JOIN cliente ON (retcre_vta.Cli_Cod = cliente.Cli_Cod)
					WHERE Emp_Cod='$Par_Sql[0]' AND Rvt_Fec Between '$Par_Sql[1]' AND '$Par_Sql[2]' AND cliente.Cli_Cod='$Par_Sql[3]' GROUP BY retcre_vta.Tpc_Cod,Rvt_Tem";
			//echo $sql."<br>";
			return $sql;

		case 888:
			$sql = "SELECT
						  '41'AS Tic_Sri,'COMPROBANTES DE VENTA EMITIDO POR REEMBOLSO' AS Tic_Des,COUNT(ventas.Vet_Cod)as total,
						  CAST(SUM(IF(Iva_Por=0, CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 ),0)) AS decimal(20,2)) as Importe0,
						  CAST(SUM(IF(Iva_Por<>0, CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 ),0)) AS decimal(20,2)) as Importe12,
						  CAST( SUM(IF(Iva_Por != 0, ( CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) +
						  CAST( CAST( ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) - ( CAST( ((Vet_Pru * Vet_Can)-((Vet_Pru * Vet_Can) * Vet_Dec/100)) AS decimal(20,2) ) * ventas.Vet_Des/100 )) AS decimal(20,2) ) *(IF(ventas_det.Vet_Ice IS NOT NULL,ventas_det.Vet_Ice/100,0))  AS decimal(20,2) )  AS decimal(20,2) )*Iva_Por/100 ), 0)) AS decimal(20,2)) AS Tot_Iva
						FROM
						  ventas
						  INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
						  INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
						  INNER JOIN tipo_compr ON (ventas.Tic_Cod = tipo_compr.Tic_Cod)
						  INNER JOIN iva ON (ventas_det.Iva_Cod = iva.Iva_Cod)
						  INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
						  INNER JOIN  (
								SELECT distinct reemb.Vet_Cod FROM venta_reembolsos AS reemb Inner Join ventas On(reemb.Vet_Cod=ventas.Vet_Cod)
						  )as reembolso ON (ventas.Vet_Cod = reembolso.Vet_Cod)
						WHERE ventas.Vet_Cod not in(select expo.Vet_Cod from exporta_vent as expo) AND
						  Tic_Sri<>0 AND Emp_Cod=$Par_Sql[0] AND Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND Vet_Est='A'
						GROUP BY ventas.Tic_Cod ";
			//echo $sql."<br>";
			return $sql;
			break;

		case 889:
			$sql = "SELECT distinct ventas.Vet_Cod, count(ventas.Vet_Cod)as total
					FROM ventas INNER JOIN caja_aper ON (ventas.Caj_Cod = caja_aper.Caj_Cod)
					INNER JOIN cliente ON (ventas.Cli_Cod = cliente.Cli_Cod)
					WHERE ventas.Vet_Cod in(select reemb.Vet_Cod from venta_reembolsos as reemb) AND  Emp_Cod=$Par_Sql[0] AND Caj_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND Vet_Est='A'";
			//echo $sql."<br>";
			return $sql;
			break;

			/*Consultamos las facturas reembolsos de una compra */
		/*case '890_group':
			$sql = "SELECT *FROM compras
					INNER JOIN compra_reembolsos ON (compras.Cop_Cod = compra_reembolsos.Cop_Cod)
					INNER JOIN(
					SELECT coalesce(sum(reem.Rem_Niv+reem.Rem_Siv+reem.Rem_Oiv+reem.Rem_Eiv),0)AS tot,reem.Cop_Cod
					FROM compra_reembolsos AS reem WHERE reem.Cop_Cod IN ($Par_Sql[0]) )AS docu ON(compras.Cop_Cod=docu.Cop_Cod)
					WHERE compra_reembolsos.Cop_Cod IN ($Par_Sql[0])
				   GROUP BY compras.Cop_Cod";
			//ChromePhp::log($sql);
			return $sql;*/



			case '890_group':
				$sql = "SELECT * FROM compras
				INNER JOIN compra_reembolsos ON compras.Cop_Cod = compra_reembolsos.Cop_Cod
				INNER JOIN (
					SELECT COALESCE(SUM(reem.Rem_Niv + reem.Rem_Siv + reem.Rem_Oiv + reem.Rem_Eiv), 0) AS tot, reem.Cop_Cod
					FROM compra_reembolsos AS reem
					WHERE reem.Cop_Cod IN (".($Par_Sql[0]?:'NULL').")
					GROUP BY reem.Cop_Cod
				) AS docu ON compras.Cop_Cod = docu.Cop_Cod
				WHERE compras.Cop_Cod IN (".($Par_Sql[0]?:'NULL').")";
				//ChromePhp::log($sql);
				return $sql;


			/*Consultamos las facturas reembolsos de una compra */
		case 890:
			$sql = "SELECT *FROM compras
				  INNER JOIN compra_reembolsos ON (compras.Cop_Cod = compra_reembolsos.Cop_Cod)
				  INNER JOIN(
					SELECT coalesce(sum(reem.Rem_Niv+reem.Rem_Siv+reem.Rem_Oiv+reem.Rem_Eiv),0)AS tot,reem.Cop_Cod
					FROM compra_reembolsos AS reem WHERE reem.Cop_Cod=$Par_Sql[0]) AS docu ON(compras.Cop_Cod=docu.Cop_Cod)
				  WHERE compra_reembolsos.Cop_Cod=$Par_Sql[0]";
			//echo $sql."<br>";
			return $sql;
			break;

			/*Consultamos retenciones bancarias en ventas */
		case 891:
			$sql = "SELECT
			        SUM(IF(Ren_Ret='R',CAST((Rvt_Bas*Ren_Por)/100 AS DECIMAL(10,2)),0))as renTot,
					SUM(IF(Ren_Ret='I',CAST((Rvt_Bas*Ren_Por)/100 AS DECIMAL(10,2)),0))as ivaTot
					FROM retcre_vta
					INNER JOIN retcrevta_det ON (retcre_vta.Rvt_Cod = retcrevta_det.Rvt_Cod)
					INNER JOIN cliente ON (retcre_vta.Cli_Cod = cliente.Cli_Cod)
					INNER JOIN renta_iva ON (retcrevta_det.Ren_Cod = renta_iva.Ren_Cod)
					WHERE Emp_Cod = '$Par_Sql[0]' AND Rvt_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]' AND Rvt_Est='A'";
			//echo $sql."<br>";
			return $sql;
			break;

			/*Consultamos Ruc retenciones Rendimientos financieros */
		case 892:
			$sql = "SELECT distinct compras.Prv_Cod,persona.Prs_Ced,identifica.Ide_Prr
					FROM retencion
					INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
					INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
					INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
					INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					WHERE proveedore.Emp_Cod = '$Par_Sql[0]' AND Ret_Dep>0 AND Cop_Est='E' AND Ret_Est='A' AND Ret_Fec between '$Par_Sql[1]' AND '$Par_Sql[2]'";
			//echo $sql."<br>";
			return $sql;

			/*Consultamos detalle retenciones Rendimientos financieros */
		case 893:
			$sql = "SELECT retencion.Ret_Cod,sucursal.Suc_Sri,Pun_Sri,IF(Aut_Tem='E',Ret_Sri,Aut_Sri)as Aut_Sri,Ret_Num,DATE_FORMAT(Ret_Fec, '%d/%m/%Y')as Ret_Fec
					FROM retencion
					INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
					INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
					INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
					INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
					INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
					WHERE compras.Prv_Cod = '$Par_Sql[0]' AND Ret_Dep>0 AND Cop_Est='E' AND Ret_Est='A'";
			//echo $sql."<br>";
			return $sql;

		case 894:
			$sql = "SELECT Tic_Des FROM tipo_compr WHERE Tic_Sri = '$Par_Sql[Tic_Sri]' AND Tic_Est='A'";
			return $sql;
		case 895:
			$sql='SELECT concat(sucursal.Suc_Sri,"-",autorizaci.Pun_Sri,"-",LPAD(Vet_Num,9,0)) as Vet_Num FROM ventas 
            inner join caja_aper ON ventas.Caj_Cod = caja_aper.Caj_Cod 
            inner join cliente ON ventas.Cli_Cod = cliente.Cli_Cod
			inner join autorizaci ON ventas.Aut_Cod = autorizaci.Aut_Cod
			inner join puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
			inner join sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
            WHERE Vet_Est="A" AND ventas.Vet_Aut = "N" AND caja_aper.Caj_Fec BETWEEN "'.$Par_Sql['ini'].'" AND "'.$Par_Sql['fin'].'" AND cliente.Emp_Cod = '.$Par_Sql['Emp_Cod'];
			return $sql;
		case 896:
			$sql='SELECT concat(sucursal.Suc_Sri,"-",autorizaci.Pun_Sri,"-",LPAD(Ret_Num,9,0)) as Ret_Num FROM retencion 
            inner join compras ON retencion.Cop_Cod = compras.Cop_Cod 
            inner join proveedore ON compras.Prv_Cod = proveedore.Prv_Cod
			inner join autorizaci ON retencion.Aut_Cod = autorizaci.Aut_Cod
			inner join puntos_imp ON autorizaci.Pun_Cod = puntos_imp.Pun_Cod
			inner join sucursal ON puntos_imp.Suc_Cod = sucursal.Suc_Cod
            WHERE Ret_Est="A" AND Ret_Aut = "N" AND Cop_Fec BETWEEN "'.$Par_Sql['ini'].'" AND "'.$Par_Sql['fin'].'" AND proveedore.Emp_Cod = '.$Par_Sql['Emp_Cod'];
			return $sql;
	}
}
