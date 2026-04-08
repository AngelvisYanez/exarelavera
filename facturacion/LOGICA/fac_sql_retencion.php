<?php

/**
 * Retenciones
 */
function sentencias_ret($id, $Par_Sql)
{
	switch ($id) {

		case 3:
			/* 
			* Consulta la provicia y pais de la ciudad de la sucursal 
			*/
			$provincia = "SELECT 
				  provincia.Pro_Nom,
				  pais.Pas_Nom
				FROM
				  provincia
				  INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
				  INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
				  INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod) 
				 WHERE 
				  ciudad.Ciu_Cod = $Par_Sql[0]";
			//echo $provincia;
			return $provincia;
			break;

		case 4:
			/* 
		* Consulta del usuario
		*/
			$consulta_4 = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			//echo $consulta_4;
			return $consulta_4;
			break;

		case 12:
			/** 
			 * Consulta el codigo del proceso 
			 */
			$sql = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
			//echo $sql."<br>"; 
			return $sql;
			break;

		case 13:
			/** 
			 * Consulta el reporte recursivo 
			 */
			$sql = "SELECT 
					  reportes.Rep_Cod,
					  procesos.Pcs_Nom,
					  reportes.Rep_Ord,
					  rutas.Rut_Des
					FROM
					  procesos
					  INNER JOIN reportes ON (procesos.Pcs_Cod = reportes.Rep_Req)
					  INNER JOIN rutas ON (procesos.Rut_Cod = rutas.Rut_Cod) WHERE reportes.Pcs_Cod = $Par_Sql[0] AND reportes.Emp_Cod = $Par_Sql[1] ORDER BY reportes.Rep_Ord ";
			//echo $sql;
			return $sql;
			break;

		case 126:
			/* 
		* Consulta la información la ciudada en base a la sucursal 
		*/
			$cargar_ciudad = "SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $cargar_ciudad;
			return $cargar_ciudad;
			break;

		/** 
		 * Cargar retención de una liquidacion de compras 
		 */
		case 166:
			$carg_retenc = "SELECT persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, proveedore.Prv_Cod, compras.Aut_Cod, ciudad.Ciu_Des, compras.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, compras.Cop_Cad,Cop_Reg,Cop_Imf, retencion.Ret_Num, autorizaci.Aut_Sri, autorizaci.Pun_Sri, autorizaci.Aut_Fci, autorizaci.Aut_Cad, retencion.Ret_Est, retencion.Ret_Cod, retencion.Ret_Con, retencion.Ret_Fec, tipo_compr.Tic_Des, renta_iva.Ren_Por, renta_iva.Ren_Sri, det_retenc.Ret_Int, det_retenc.Ret_Bas, IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp, det_retenc.Ret_Cod, det_retenc.Ren_Cod,renta_iva.Ren_Con FROM tipo_compr,persona, proveedore, compras, retencion, autorizaci,
		 det_retenc, renta_iva, ciudad WHERE compras.Cop_Cod=retencion.Cop_cod AND retencion.Ret_Cod=det_retenc.Ret_Cod AND ciudad.Ciu_Cod = persona.Ciu_Cod AND renta_iva.Ren_Cod=det_retenc.Ren_Cod and autorizaci.Aut_Cod = compras.Aut_Cod 
		 AND persona.Prs_Cod=proveedore.Prs_Cod  AND proveedore.Prv_Cod=compras.Prv_Cod 
		 AND compras.Tic_Cod=tipo_compr.Tic_Cod AND retencion.Ret_Cod=$Par_Sql[0] ORDER BY det_retenc.Ret_Int ASC";
			//echo $carg_retenc;
			return $carg_retenc;
			break;

		/**
		 * Revisa si hay Codigo de autorizacion en la compra 
		 */
		case 167:
			$carg_retenc = "SELECT compras.Aut_Cod FROM compras, retencion, det_retenc WHERE compras.Cop_Cod=retencion.Cop_cod AND 
				retencion.Ret_Cod=det_retenc.Ret_Cod AND retencion.Ret_Cod=$Par_Sql[0] LIMIT 0,1";
			//echo $carg_retenc;
			return $carg_retenc;
			break;

		/**
		 *  Consulta del detalle de las retenciones 
		 */
		case 182:
			/* $det_renta_182 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, det_retenc.Iva_Cod, Ret_Bas, Ren_Por, Iva_Por, 
						(Ret_Bas * Ren_Por)/100 as Val_Ret FROM det_retenc, iva, renta_iva WHERE det_retenc.Iva_Cod 
						= iva.Iva_Cod AND det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]"; */
			$det_renta_182 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por,  
						(Ret_Bas * Ren_Por)/100 as Val_Ret, det_retenc.Adq_Cod FROM det_retenc, renta_iva WHERE 
						 det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]";
		//echo $det_renta_182;
		// ChromePhp::log($det_renta_182);
		return $det_renta_182;
		break;
				
		case 183:
			/* COnsulta del detalle de las retenciones */
			$caja_clien_183 = "SELECT det_retenc.Ret_Bas AS Total, (det_retenc.Ret_Bas * renta_iva.Ren_Por) / 100 AS Renta 
						FROM renta_iva, det_retenc,retencion, compras WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
						det_retenc.Ret_Cod =retencion.Ret_Cod AND retencion.Tic_Cod=$Par_Sql[0] AND 
						retencion.Ret_Est='$Par_Sql[4]' AND compras.Cop_Cod=retencion.Cop_Cod AND (compras.Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND 
						renta_iva.Ren_Cod = $Par_Sql[3]";
			//echo $caja_clien_183;
			return $caja_clien_183;
			break;

		/**
		 *  Consulta el numero de comprobantes de rentencion emitidos 
		 */
		case 184:
			$renta_184 = "SELECT count(renta_iva.Ren_Cod) as Renta_Iva FROM renta_iva, det_retenc,retencion, compras,proveedore 
					WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod AND det_retenc.Ret_Cod =retencion.Ret_Cod AND compras.Prv_Cod=proveedore.Prv_Cod AND proveedore.Emp_Cod='$Par_Sql[5]' AND retencion.Tic_Cod=$Par_Sql[0] AND retencion.Ret_Est='$Par_Sql[1]' AND compras.Cop_Cod=retencion.Cop_Cod AND 
					(compras.Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]') AND renta_iva.Ren_Ret = '$Par_Sql[4]'";
			//echo $renta_184;
			return $renta_184;
			break;

		/**
		 * Consulta la información relacionada con el código del periodo contable 
		 */
		case 189:
			$sql =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, YEAR(Pec_Fei) as Ann, Pla_Cod FROM perio_cont WHERE Pec_Cod = $Par_Sql[0]";
			return $sql;
			break;

		/**
		 * consulto los años para la consulta de las retenciones 
		 */
		case 329:
			$consulta_anio_retencion_329 = "SELECT YEAR(retencion.Ret_Fec) AS Anio, renta_iva.Ren_Cod, renta_iva.Ren_Por  FROM retencion, det_retenc, renta_iva
WHERE retencion.Ret_Cod=det_retenc.Ret_Cod
AND det_retenc.Ren_Cod=renta_iva.Ren_Cod
GROUP BY YEAR(retencion.Ret_Fec) ORDER BY YEAR(retencion.Ret_Fec) DESC";
			return $consulta_anio_retencion_329;
			break;

		/**
		 * Consulta de retenciones activas e inactivas buscadas por número de comprobante de retención 
		 */
		case 332:
			$carga_retenci_modif_332 = "SELECT 
			persona.Prs_Nom,persona.Prs_Ape, 
			proveedore.Prv_Cod,compras.Cop_Cod,
			compras.Cop_Num,compras.Cop_Fec, 
			compras.Cop_Cad, retencion.Ret_Num, 
			retencion.Ret_Cod, retencion.Ret_Cod, 
			retencion.Ret_Est, retencion.Ret_Fec,
			det_retenc.Ret_Int, det_retenc.Ret_Bas, 
			det_retenc.Ret_Cod, det_retenc.Ren_Cod, 
			autorizaci.Aut_Sri 
			FROM 
			persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci 
			WHERE 
			compras.Cop_Cod=retencion.Cop_cod AND  
			retencion.Ret_Cod=det_retenc.Ret_Cod AND  
			renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
			persona.Prs_Cod=proveedore.Prs_Cod AND 
			proveedore.Prv_Cod=compras.Prv_Cod AND 
			retencion.Tic_Cod=$Par_Sql[1] AND 
			retencion.Ret_Est='A' AND 
			retencion.Ret_Num='$Par_Sql[0]' AND 
			retencion.Ret_Fec>='$Par_Sql[2]' AND 
			retencion.Ret_Fec<='$Par_Sql[3]' AND proveedore.Emp_Cod='$Par_Sql[4]' AND 
			autorizaci.Aut_Cod= retencion.Aut_Cod GROUP BY retencion.Ret_Cod ORDER BY Ret_Num Asc";
			//echo $carga_retenci_modif_332;
			return $carga_retenci_modif_332;
			break;

		/**
		 * Consulta de retenciones activas e inactivas buscadas por apellidos 
		 */
		case 333:
			$carga_reten_modif_fac_333 = "SELECT 
				persona.Prs_Nom,persona.Prs_Ape, 
				proveedore.Prv_Cod,compras.Cop_Cod,
				compras.Cop_Num,compras.Cop_Fec, 
				compras.Cop_Cad, retencion.Ret_Est, 
				retencion.Ret_Num, retencion.Ret_Cod, 
				retencion.Ret_Fec,det_retenc.Ret_Int, 
				det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
				det_retenc.Ren_Cod, autorizaci.Aut_Sri 
				FROM
				  persona
				  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod AND Suc_Cod=$_SESSION[Ses_Suc_Cod])
				WHERE
				retencion.Tic_Cod=$Par_Sql[1] AND 
				retencion.Ret_Est='A' AND 
				persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND
				retencion.Ret_Fec>='$Par_Sql[2]' AND 
				retencion.Ret_Fec<='$Par_Sql[3]' AND proveedore.Emp_Cod='$Par_Sql[4]' 
				 GROUP BY retencion.Ret_Cod ORDER BY Ret_Num Asc";
			//echo $carga_reten_modif_fac_333;
			return $carga_reten_modif_fac_333;
			break;

		/**
		 *  consulto si existen pagos relizados por una compra realizada
		 */
		case 357:
			$consulta_pagos_compra_357 = "SELECT 
										ccpp_pagar.Cpp_Cod 
									FROM 
										ccpp_pagar 
										INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
									WHERE 
										det_ccpp_p.Pag_Est='A' AND 
										ccpp_pagar.Com_Cod = $Par_Sql[0]";
			//$consulta_pagos_compra_357="SELECT det_ccpp_p.Pag_Val FROM det_ccpp_p WHERE det_ccpp_p.Com_Cod='$Par_Sql[0]'";
			//echo $consulta_pagos_compra_357;
			return $consulta_pagos_compra_357;
			break;

		/*
		*  Consultar si la retención es automática 
		*/
		case 380:
			$consultar_automatica_manual_380 = "SELECT compr_auto.Com_Cod FROM compr_auto WHERE compr_auto.Cop_Cod = $Par_Sql[0]";
			//echo $consultar_automatica_manual_380;
			return $consultar_automatica_manual_380;
			break;

		/**
		 * Consultar el detalle de la retención 
		 */
		case 381:
			$consultar_automatica_manual_381 = "SELECT det_retenc.Ret_Int, det_retenc.Ret_Cod, det_retenc.Ret_Bas, renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por,renta_iva.Ren_Con  
		FROM det_retenc, renta_iva  WHERE  det_retenc.Ren_Cod=renta_iva.Ren_Cod AND  det_retenc.Ret_Cod = $Par_Sql[0] ";
			// echo $consultar_automatica_manual_381;
			return $consultar_automatica_manual_381;
			break;

		/**
		 *  Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) 
		 */
		case 476:
			$carg_ret_des_imp = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
		FROM renta_iva WHERE renta_iva.Ren_Est='A' ORDER BY renta_iva.Ren_Sri   ";
			return $carg_ret_des_imp;
			break;

		/** 
		 *  Cálculo del importe de la retención 
		 */
		case 500:
			/*$importe_retenido="SELECT SUM(det_retenc.Ret_Bas) AS suma_re FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod";ojo borrar*/

			$importe_retenido = "SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod";
			//echo '<br>'.$importe_retenido;
			return $importe_retenido;
			break;

		/** 
		 * Cargar retención a actualizar 
		 */
		case 501:
			$cargar_reten_actuali = "SELECT persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape, persona.Prs_Dir, proveedore.Prv_Cod, compras.Aut_Cod, compras.Cop_Aut, compras.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, compras.Cop_Imf, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Est, retencion.Ret_Cod, retencion.Ret_Con, retencion.Ret_Fec, tipo_compr.Tic_Des, renta_iva.Ren_Por, renta_iva.Ren_Sri,
det_retenc.Ret_Int, (det_retenc.Ret_Bas) as Ret_Bas, IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp
, det_retenc.Ret_Cod, det_retenc.Ren_Cod, Ciu_Des FROM tipo_compr,persona, proveedore, compras, retencion, det_retenc, renta_iva, ciudad WHERE compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod 
AND compras.Tic_Cod=tipo_compr.Tic_Cod AND retencion.Ret_Cod=$Par_Sql[0] AND persona.Ciu_Cod = ciudad.Ciu_Cod 
ORDER BY det_retenc.Ret_Int ASC";
			//echo $cargar_reten_actuali;
			return $cargar_reten_actuali;
			break;

		/*
		*  Consulta de retenciones activas e inactivas buscadas por cedula
		*/
		case 504:
			$carga_retenci_modif = "SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod, compras.Cop_Est,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Cod, retencion.Ret_Est, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva 
WHERE compras.Cop_Est='A' AND compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ced='$Par_Sql[0]' $Par_Sql[2] AND proveedore.Emp_Cod='$Par_Sql[3]' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
			//echo $carga_retenci_modif;
			return $carga_retenci_modif;
			break;

		/*
		*  Consulta de retenciones activas e inactivas buscadas por número de comprobante de retención 
		*/
		case 505:
			$carga_retenci_modif = "SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod, compras.Cop_Est,compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Cod, retencion.Ret_Est, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva 
WHERE compras.Cop_Est='A' AND compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND retencion.Ret_Num='$Par_Sql[0]' $Par_Sql[2] AND proveedore.Emp_Cod='$Par_Sql[3]' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
			//echo $carga_retenci_modif;
			return $carga_retenci_modif;
			break;

		/*
		*  Consulta de retenciones activas e inactivas buscadas por número de factura 
		*/
		case 506:
			$carga_reten_modif_fac = "SELECT persona.Prs_Nom,persona.Prs_Ape, proveedore.Prv_Cod, 
compras.Cop_Cod, compras.Cop_Est,  compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, retencion.Ret_Cod, retencion.Ret_Fec,
det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, det_retenc.Ren_Cod FROM persona, proveedore, compras, retencion, det_retenc, renta_iva 
WHERE compras.Cop_Est='A' AND compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
AND retencion.Tic_Cod=$Par_Sql[1]
AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'  $Par_Sql[2] AND proveedore.Emp_Cod='$Par_Sql[3]' GROUP BY retencion.Ret_Cod ORDER BY persona.Prs_Ape";
			return $carga_reten_modif_fac;
			break;

		/** 
		 *   Dar de baja a la retencion de compra 
		 */
		case 508:
			$baja_retencion = "UPDATE retencion SET Ret_Est=UPPER('$Par_Sql[1]') WHERE Ret_Cod=$Par_Sql[0] ";
			//echo $baja_retencion;
			return $baja_retencion;
			break;

		/**
		 *  Carga los conceptos en la retención IVA
		 */
		case 513:
			$carg_ret_des_imp = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
		FROM renta_iva WHERE renta_iva.Ren_Est='A' AND Ren_Ret='I' ORDER BY renta_iva.Ren_Sri";
			return $carg_ret_des_imp;
			break;

		/** 
		 *  Calculo del valor de la retencion por porcentaje de retención 
		 */
		case 515:
			$importe_porce_rete = "SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod  AND renta_iva.Ren_Por=$Par_Sql[1]";
			//echo $importe_porce_rete;
			return $importe_porce_rete;
			break;

		/** 
		 *  Consultar información de las liquidaciones de compra por codigo interno de la autorización 
		 */
		case 523:
			$con_inf_aut_liq = "SELECT Aut_Cad FROM autorizaci WHERE Aut_Cod=$Par_Sql[0]";
			//echo $con_inf_aut_liq;
			return $con_inf_aut_liq;
			break;

		/** 
		 *  Calculo del valor de la retencion por codifo de formulario de retención 
		 */
		case 544:
			$importe_porce_rete = "SELECT det_retenc.Ret_Bas, renta_iva.Ren_Por FROM det_retenc, renta_iva
		WHERE renta_iva.Ren_Cod=det_retenc.Ren_Cod  AND renta_iva.Ren_Cod=$Par_Sql[1] AND det_retenc.Ret_Cod=$Par_Sql[0]";
			return $importe_porce_rete;
			break;

		/** 
		 *  Consulta de comprobantes de compra por fecha de retención 
		 */
		case 545:
			$carg_reten_fechas_ret = "SELECT 
									persona.Prs_Nom,persona.Prs_Ape, 
									proveedore.Prv_Cod,compras.Cop_Cod,
									compras.Cop_Num,compras.Cop_Fec, 
									compras.Cop_Cad,retencion.Ret_Est, 
									retencion.Ret_Num,retencion.Ret_Cod, 
									retencion.Ret_Fec,det_retenc.Ret_Int, 
									det_retenc.Ret_Bas,det_retenc.Ret_Cod, 
									det_retenc.Ren_Cod, autorizaci.Aut_Sri 
								FROM 
									persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci 
								WHERE 
									compras.Cop_Cod=retencion.Cop_cod AND  
									retencion.Ret_Cod=det_retenc.Ret_Cod AND  
									renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
									persona.Prs_Cod=proveedore.Prs_Cod AND 
									proveedore.Prv_Cod=compras.Prv_Cod AND 
									retencion.Tic_Cod=$Par_Sql[2] AND 
									retencion.Ret_Est='$Par_Sql[3]' AND
									retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND proveedore.Emp_Cod='$Par_Sql[4]' AND  
									autorizaci.Aut_Cod= retencion.Aut_Cod GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num ASC";
			//echo   $carg_reten_fechas_ret;
			return $carg_reten_fechas_ret;
			break;

		/** 
		 *  Consulta las retenciones de acuerdo al porcentaje de retención por fechas de retencion 
		 */
		case 546:
			$carg_reten_fechas_por = "SELECT 
		                      persona.Prs_Nom,renta_iva.Ren_Por,persona.Prs_Ape, proveedore.Prv_Cod, compras.Cop_Cod,
							  compras.Cop_Num,compras.Cop_Fec, compras.Cop_Cad, retencion.Ret_Est, retencion.Ret_Num, 
					          retencion.Ret_Cod, retencion.Ret_Fec, det_retenc.Ret_Int, det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
							  det_retenc.Ren_Cod, autorizaci.Aut_Sri 
						FROM 
							  persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci
						WHERE 
							  compras.Cop_Cod=retencion.Cop_cod AND  retencion.Ret_Cod=det_retenc.Ret_Cod AND  
							  renta_iva.Ren_Cod=det_retenc.Ren_Cod AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod
							  AND retencion.Tic_Cod=$Par_Sql[2] AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
							  AND retencion.Ret_Est='$Par_Sql[3]'
							  AND renta_iva.Ren_Por=$Par_Sql[4] AND proveedore.Emp_Cod='$Par_Sql[5]' AND autorizaci.Aut_Cod= retencion.Aut_Cod  
					    GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num";
			//echo $carg_reten_fechas_por;
			return $carg_reten_fechas_por;
			break;

		/**
		 *  Consulta las retenciones de acuerdo al codifo del formulario de retención por fecha de retención 
		 */
		case 547:
			$carg_reten_fechas_for = "SELECT 
									persona.Prs_Nom,renta_iva.Ren_Por,
									renta_iva.Ren_Sri,persona.Prs_Ape, 
									proveedore.Prv_Cod, compras.Cop_Cod,
									compras.Cop_Num,compras.Cop_Fec, 
									compras.Cop_Cad,retencion.Ret_Est, 
									retencion.Ret_Num,retencion.Ret_Cod,
									retencion.Ret_Fec, det_retenc.Ret_Int, 
									det_retenc.Ret_Bas, det_retenc.Ret_Cod, 
									det_retenc.Ren_Cod, (det_retenc.Ret_Bas * renta_iva.Ren_Por) / 100 AS Renta,  
									COUNT(retencion.Cop_Cod) AS Num_Cop, autorizaci.Aut_Sri
								FROM 
									persona, proveedore, compras, retencion, det_retenc, renta_iva, autorizaci
								WHERE 
									compras.Cop_Cod=retencion.Cop_cod AND  
									retencion.Ret_Cod=det_retenc.Ret_Cod AND  
									autorizaci.Aut_Cod= retencion.Aut_Cod AND
									renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
									persona.Prs_Cod=proveedore.Prs_Cod AND 
									proveedore.Prv_Cod=compras.Prv_Cod AND 
									retencion.Tic_Cod=$Par_Sql[2] AND 
									retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND 
									retencion.Ret_Est='$Par_Sql[3]' AND proveedore.Emp_Cod='$Par_Sql[5]' AND
									renta_iva.Ren_Cod='$Par_Sql[4]' GROUP BY retencion.Ret_Cod ORDER BY retencion.Ret_Num";
			//echo $carg_reten_fechas_for;
			return $carg_reten_fechas_for;
			break;

		/** 
		 *  Consulta de totales por codigo de formulario de retención y fecha de comprobante de retención 
		 */
		case 549:
			/*$con_tot_form_ret_fec_ret="SELECT 
										renta_iva.Ren_Cod, autorizaci.Aut_Sri, 
										renta_iva.Ren_Por, renta_iva.Ren_Sri, 
										COUNT(retencion.Cop_Cod) AS Num_Cop, SUM(det_retenc.Ret_Bas) AS Total
								   FROM 
								   		renta_iva, det_retenc,retencion, autorizaci 
								   WHERE 
										renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
										det_retenc.Ret_Cod=retencion.Ret_Cod AND 
										retencion.Tic_Cod=$Par_Sql[2] AND 
										autorizaci.Aut_Cod= retencion.Aut_Cod AND 
										retencion.Ret_Est='$Par_Sql[3]' AND 
										retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
										GROUP BY renta_iva.Ren_Cod";*/
			$con_tot_form_ret_fec_ret = "SELECT 
		renta_iva.Ren_Cod,autorizaci.Aut_Sri,renta_iva.Ren_Por,renta_iva.Ren_Sri,COUNT(retencion.Cop_Cod) AS Num_Cop,sum(det_retenc.Ret_Bas) AS Total
		FROM
		  retencion
		  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
		  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
		  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
		  INNER JOIN compras ON (compras.Cop_Cod = retencion.Cop_Cod)
		  INNER JOIN proveedore ON (proveedore.Prv_Cod = compras.Prv_Cod)
		WHERE
		  retencion.Tic_Cod = '$Par_Sql[2]' AND retencion.Ret_Est = '$Par_Sql[3]' AND 
		  proveedore.Emp_Cod='$Par_Sql[4]' AND retencion.Ret_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
		GROUP BY
		  renta_iva.Ren_Cod,autorizaci.Aut_Sri,renta_iva.Ren_Por,renta_iva.Ren_Sri";

			//echo $con_tot_form_ret_fec_ret;
			return $con_tot_form_ret_fec_ret;
			break;

		/** 
		 * Consulta el detalle de las retenciones sumadas 
		 */
		case 553:
			$cargar_reten_consul_553 = "SELECT 
									persona.Prs_Ced,
									persona.Prs_Nom,
									persona.Prs_Ape,
									persona.Prs_Tel, 
									persona.Prs_Dir, 
									proveedore.Prv_Cod, 
									compras.Aut_Cod, 
									compras.Cop_Aut, 
									compras.Cop_Cod, 
									compras.Cop_Num, 
									compras.Cop_Fec, 
									compras.Cop_Reg,
									compras.Cop_Imf, 
									compras.Cop_Cad, 
									autorizaci.Aut_Fci,
									autorizaci.Aut_Cad,
									retencion.Ret_Num, 
									retencion.Ret_Est, 
									retencion.Ret_Cod, 
									retencion.Ret_Con, 
									retencion.Ret_Fec, 
									tipo_compr.Tic_Des, 
									renta_iva.Ren_Por, 
									renta_iva.Ren_Con, 
									renta_iva.Ren_Sri,
									det_retenc.Ret_Int, 
									sum(det_retenc.Ret_Bas) as Ret_Bas, 
									IF (det_retenc.Ret_Imp='R','RENTA','IVA') as Ret_Imp, 
									det_retenc.Ret_Cod, det_retenc.Ren_Cod, Ciu_Des 
								FROM 
									tipo_compr,persona, proveedore, compras, retencion, det_retenc, renta_iva, ciudad ,autorizaci
								WHERE 
									compras.Cop_Cod=retencion.Cop_cod AND  
									retencion.Ret_Cod=det_retenc.Ret_Cod AND
									autorizaci.Aut_Cod=retencion.Aut_Cod AND  
									renta_iva.Ren_Cod=det_retenc.Ren_Cod AND 
									persona.Prs_Cod=proveedore.Prs_Cod AND 
									proveedore.Prv_Cod=compras.Prv_Cod AND 
									compras.Tic_Cod=tipo_compr.Tic_Cod AND 
									retencion.Ret_Cod=$Par_Sql[0] AND 
									persona.Ciu_Cod = ciudad.Ciu_Cod 
									GROUP BY renta_iva.Ren_Sri, renta_iva.Ren_Por ORDER BY det_retenc.Ret_Int ASC";
			//echo $cargar_reten_consul_553;
			return $cargar_reten_consul_553;
			break;

		case 554:
			/**
			 * Consulta los a�os de las facturas de compras recibidas 
			 */
			$sql = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras, perio_cont, plan_cuenta WHERE compras.Pec_Cod = perio_cont.Pec_Cod AND perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] AND compras.Cop_Est='A' 
					 ORDER BY YEAR(compras.Cop_Fec) DESC";
			return $sql;
			break;

		case 555:
			/*Consulta todos los codigos de retencion de una empresa */
			$sql = "SELECT DISTINCT
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,renta_iva.Ren_Por, 
				  if(renta_iva.Ren_Ret='R','Renta','Iva')as Ren_Ret
				FROM
				  det_retenc
				  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' order by Ren_Ret Desc";
			//echo  $sql;
			return $sql;
			break;

		/* Carga los conceptos en la retenci�n por codigo sri */
		case 556:
			$sql = "SELECT renta_iva.Ren_Cod,if(renta_iva.Ren_Ret='R','Renta','Iva')as Ren_Ret, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
		FROM renta_iva WHERE renta_iva.Ren_Est='A' AND Ren_Sri='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;
			break;

		/* Carga los conceptos en la retención por codigo sri */
		case 557:
			$sql = "SELECT  
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Por,retencion.Ret_Num,
				  retencion.Ret_Fec,persona.Prs_Ape,persona.Prs_Nom,tipo_compr.Tic_Des,
				  compras.Cop_Num,compras.Cop_Fec,
				  Round(SUM(Ret_Bas),2)as Ret_Bas,
				  Round(SUM(Ret_Bas * Ren_Por)/100, 2) AS Ren_Ret,
				  autorizaci.Pun_Sri AS Pun_Rete,
				  sucursal.Suc_Sri AS Suc_Sri
				FROM
				  det_retenc
				  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
				  INNER JOIN puntos_imp ON (autorizaci.Pun_Cod = puntos_imp.Pun_Cod)
				  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
				  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
				WHERE
				  proveedore.Emp_Cod = '$Par_Sql[0]' AND Ret_Est='$Par_Sql[4]' AND renta_iva.Ren_Cod='$Par_Sql[1]' AND (Cop_Fec BETWEEN '$Par_Sql[2]' AND '$Par_Sql[3]') 
				  /*AND compras.Cop_Est = '$Par_Sql[4]' -- se lo deshabilito por problemas de que no aparecen todos los datos*/
				GROUP BY
				  compras.Cop_Cod
				order by retencion.Ret_Num, retencion.Ret_Fec Asc";
			//echo $sql."<br>";
			//ChromePhp::log($sql);
			return $sql;
			break;

		/* Carga los conceptos en la retención por codigo sri */
		case 558:
			$carg_ret_des_imp = "SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri,if(renta_iva.Ren_Ret='R','Renta','Iva')as Ren_Ret, renta_iva.Ren_Por, Ren_Con  
		FROM renta_iva WHERE renta_iva.Ren_Est='A' AND Ren_Cod='$Par_Sql[0]' ";
			return $carg_ret_des_imp;
			break;

		/* Carga las facturas q no tienen retencion y pasan a ser 332 en retencion */
		case 559:
			$importe = 'CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
			$importe_con_desc = "CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
			$sql = "SELECT   
                        compras.Cop_Cod,'-' AS Ret_Num,'-' AS Ret_Fec,
                        compras.Prv_Cod,Prs_Ape,Prs_Nom,Cop_Num,Cop_Fec,Cop_Reg,Emp_Cod,
                        CAST( SUM($importe_con_desc)  AS decimal(20,2)) AS Ret_Bas,0 AS Ren_Ret
                        FROM det_compra
                        INNER JOIN compras ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                        INNER JOIN persona ON (persona.Prs_Cod = proveedore.Prs_Cod)
                        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                        LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod) 
                        LEFT JOIN det_retenc ON (retencion.Ret_Cod=det_retenc.Ret_Cod AND det_retenc.Ret_Int=det_compra.Cop_Int AND det_retenc.Ret_Imp='R')
                        LEFT JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod) 
                        WHERE (retencion.Ret_Cod IS NULL OR det_retenc.Ret_Int IS NULL OR retencion.Ret_Est='I' OR Ren_Sri='332') AND Emp_Cod='$Par_Sql[0]' AND (Cop_Fec BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND compras.Cop_Est = '$Par_Sql[3]' AND (Tic_Sri!='4' AND Tic_Sri!='0')
					GROUP BY compras.Cop_Cod;";
			// echo $sql;
			return $sql;
			break;

		/* Carga los codigos q intervienen en las retenciones incluido 332 */
		case 560:
			$sql = "SELECT DISTINCT
				  renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,renta_iva.Ren_Por, 
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret
				FROM
				  det_retenc
				  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
				  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
				  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
				  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				WHERE
				  proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A'       
				UNION 
				SELECT renta_iva.Ren_Cod,renta_iva.Ren_Sri,renta_iva.Ren_Con,renta_iva.Ren_Por, 
				  if(renta_iva.Ren_Ret='R','Renta','Iva')AS Ren_Ret 
				  FROM renta_iva WHERE Ren_Sri='332' AND Ren_Est='A' 
				  AND (SELECT COUNT(compras.Cop_Cod) as total
			   FROM
				 compras
				 INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
				 LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)     
			   WHERE
				 proveedore.Emp_Cod='$Par_Sql[0]' AND compras.Cop_Est='A' AND Ret_Cod IS NULL)>0
				ORDER BY Ren_Sri Asc";
			//echo $sql;
			return $sql;
			break;

		case 561:
			$sql = "SELECT * FROM vendedor 
                            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod
                            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                            INNER JOIN autorizaci ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod
                            INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                            WHERE Vnd_Est='A' AND Aut_Est='A' AND Tic_Est='A' AND Tic_Sri='7' AND Emp_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1] AND '$Par_Sql[2]' BETWEEN Aut_Fci AND Aut_Cad";
			//echo $sql;
			return $sql;
		case 562:
			$sql = "SELECT * FROM tipo_compr WHERE Tic_Est='A' ";
			//echo $sql;
			return $sql;
		case 563:
			$sql = "SELECT Ret_Cod,Ret_Num FROM retencion
                        INNER JOIN autorizaci ON autorizaci.Aut_Cod=retencion.Aut_Cod
                        INNER JOIN vendedor ON vendedor.Vnd_Cod=retencion.Vnd_Cod
                        INNER JOIN puntos_imp ON vendedor.Pun_Cod=puntos_imp.Pun_Cod
                        INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                        WHERE Emp_Cod='$Par_Sql[Emp_Cod]' AND Suc_Sri='$Par_Sql[Suc_Sri]' AND Pun_Sri='$Par_Sql[Pun_Sri]' AND Ret_Num BETWEEN $Par_Sql[Ini] AND $Par_Sql[Fin]
                        ORDER BY Ret_Num";
			//echo $sql;
			return $sql;
		case 564:
			$sql = "INSERT INTO retencion(Cop_Cod,Vnd_Cod,Aut_Cod,Tic_Cod,Ret_Num,Ret_Fec,Ret_Con,Ret_Est)
                            VALUES($Par_Sql[Cop_Cod],$Par_Sql[Vnd_Cod],$Par_Sql[Aut_Cod],$Par_Sql[Tic_Cod],'$Par_Sql[Ret_Num]','$Par_Sql[Ret_Fec]','$Par_Sql[Ret_Con]','I');";
			//echo $sql.'<br>';
			return $sql;
		case 565:
			$sql = "INSERT INTO compras(Ciu_Cod,Tic_Cod,Prv_Cod,Cop_Est) VALUES(3,1,$Par_Sql[0],'E')";
			//echo $sql;
			return $sql;
		case 566:
			$sql = "SELECT compra_prov.Prv_Cod,Prs_Ape 
					FROM compra_prov INNER JOIN proveedore ON proveedore.Prv_Cod= compra_prov.Prv_Cod 
					INNER JOIN persona ON proveedore.Prs_Cod= persona.Prs_Cod 
					WHERE Emp_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
		case 567:
			$sql = "SELECT SUM(det_retenc.Ret_Bas) AS suma_re FROM det_retenc, renta_iva
                    WHERE det_retenc.Ret_Cod=$Par_Sql[0] AND renta_iva.Ren_Cod=det_retenc.Ren_Cod";
			//echo $sql;
			return $sql;

		case 568:
			$sql = "SELECT * FROM vendedor 
                            INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod
                            INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
                            INNER JOIN autorizaci ON autorizaci.Pun_Cod=puntos_imp.Pun_Cod
                            INNER JOIN tipo_compr ON autorizaci.Tic_Cod=tipo_compr.Tic_Cod
                            WHERE Vnd_Est='A' AND Aut_Est='A' AND Tic_Est='A' AND Tic_Sri='7' AND Emp_Cod=$Par_Sql[0] AND Prs_Cod=$Par_Sql[1] ";
			//echo $sql;
			return $sql;

		case 569:
			$fecha_ini = date("Y-m-d", strtotime($Par_Sql[1]));
			$fecha_fin = date("Y-m-d", strtotime($Par_Sql[2]));
			$sql = " SELECT comprobantes.Com_Cod, comprobantes.Prv_Cod , Com_Val , Tia_Abr, Tia_Ini FROM comprobantes
					INNER JOIN tipo_asien  ON tipo_asien.Tia_Cod = comprobantes.Tia_Cod
					INNER JOIN proveedore  ON proveedore.Prv_Cod = comprobantes.Prv_Cod
					WHERE comprobantes.Com_Est = 'A' AND proveedore.Emp_Cod = $Par_Sql[0] AND tipo_asien.Tia_Abr = 'LI'
					AND tipo_asien.Tia_Ini = 'D' AND comprobantes.Com_Fec BETWEEN '$fecha_ini' AND '$fecha_fin' ";
			return $sql;
	}
}
