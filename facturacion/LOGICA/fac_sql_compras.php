<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_comf($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			* Consul729ta de la cuenta contable 
			*/
			case 11:
			$bus_xmld_11="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
			return $bus_xmld_11;	
			break;
			case 4:
			/** 
			* Consulta del usuario
			*/
			$sql = "SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
			break;

                        case 5:
			/** 
			* Consulta de los codigos ICE
			*/
			$sql = "SELECT Ice_Int, Ice_Cod,Ice_Sri, Ice_Por, Ice_Des FROM ice WHERE Ice_Est = 'A' AND Ice_Des like '%$Par_Sql[0]%' order by Ice_Cod Asc";
			//echo $sql;
			return $sql;
			break;

                        /** 
	                * Consulta de los codigos ICE
	                */
	                case 66:	
	                $sql = "SELECT Ice_Int, Ice_Cod,Ice_Sri, Ice_Por, Ice_Des FROM ice WHERE Ice_Sri='$Par_Sql[0]' And Ice_Est='A'";
	                //echo $sql;
	                return $sql;
	                break;                 
			
			case 12:
			/** 
			* Consulta el codigo del proceso 
			*/
			$sql = "SELECT Pcs_Cod FROM procesos WHERE Pcs_Nom = '$Par_Sql[0]'";
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
			return $sql;
			break;

			case 24:
			/**
			* Consulta del vendedor en base al codigo de la persona
			*/
			$sql = "SELECT vendedor.Vnd_Cod, vendedor.Pun_Cod, Pun_Des FROM vendedor, puntos_imp WHERE vendedor.Pun_Cod = puntos_imp.Pun_Cod AND vendedor.Vnd_Est = 'A' AND 
								vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1]";
			//echo $sql;
			return $sql;
			break;

			case 126: 
			/** 
			* Consulta la información la ciudada en base a la sucursal 
			*/
			$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, 
							sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
			break;
						
			case 143:
			$sql="SELECT comprobantes.Com_Cod, comprobantes.Com_Num, Pld_Des, Prs_Ape, Che_Cod, Prs_Nom, cheques.Asi_Cod, cheques.Prv_Cod, Che_Num, Che_Val, Che_Cob, Che_Obs, Com_Est, Che_Fec, cheques.Ban_Cod, cheques.Prv_Cod FROM cheques, comprobantes, asientos, banco, det_plan, proveedore, persona where comprobantes.Com_Cod = asientos.Com_Cod AND asientos.Asi_Cod = cheques.Asi_Cod AND cheques.Ban_Cod = banco.Ban_Cod AND banco.Pld_Cod = det_plan.Pld_Cod
			  AND cheques.Prv_Cod = proveedore.Prv_Cod AND proveedore.Prs_Cod = persona.Prs_Cod  
			  AND comprobantes.Com_Cod = $Par_Sql[0] ORDER BY Che_Num";
			return $sql;
			break;

			/**
			* Cargado de la cabecera del comprobante por codigo 
			*/
                        case 149: // Erik: Comente Tic_Cod=2 para compra, Agregado Tia_Abr
			$sql="SELECT Com_Cod, CONCAT(Tia_Abr,'-',IF(CHAR_LENGTH(MONTH(comprobantes.Com_Fec))=1,CONCAT('0',CAST(MONTH(comprobantes.Com_Fec) AS char)),CAST(MONTH(comprobantes.Com_Fec) AS char)),'-',CAST(comprobantes.Com_Num AS char)) AS Com_Num, Pec_Cod, persona.Prs_Ape, persona.Prs_Nom, Com_Con, Com_Obs, Com_Fec, ROUND(Com_Val,2) as Com_Val, Com_Tip, Com_Tipo, comprobantes.Prv_Cod, comprobantes.Cli_Cod, Com_Est FROM comprobantes, $Par_Sql[0], persona,tipo_asien WHERE Com_Cod =$Par_Sql[1] /*AND Tia_Cod='$Par_Sql[2]'*/ AND comprobantes.$Par_Sql[4]=$Par_Sql[0].$Par_Sql[4] AND $Par_Sql[0].Prs_Cod=persona.Prs_Cod AND comprobantes.Pec_Cod='$Par_Sql[3]' AND comprobantes.Com_Est='A' AND comprobantes.Tia_Cod=tipo_asien.Tia_Cod";
                        //echo    $sql; 
			return $sql;
			break;	

			case 152:
			/**
			* Selecionar el numero maximo de comprobante mensual según el tipo
			*/
			$sql="SELECT MAX(Com_Num)+1 AS Com_Num  FROM comprobantes WHERE Tia_Cod = $Par_Sql[0] AND Pec_Cod = $Par_Sql[1] AND 
						MONTH(Com_Fec) = $Par_Sql[2]";
			return $sql;
			break;		
			
			
			
			case 189: 
			/**
			* Consulta la información relacionada con el código del periodo contable 
			*/
			$sql =	"SELECT Pec_Cod, Pec_Fei, Pec_Fef, YEAR(Pec_Fei) as Ann, Pla_Cod FROM perio_cont WHERE Pec_Cod = $Par_Sql[0]";
			return $sql;
			break;	

			/**
			* Consulta el total de anticipos por proveedor y periodo contable 
			*/
			case 192:	
			$sql="SELECT (asientos.Asi_Val) AS Asi_Val, det_plan.Pld_Des, det_plan.Pld_Cod, anticipos.Ant_Cod, Ant_Fec, anticipos.Ant_Cod FROM anticipos
					  INNER JOIN compr_anti ON (compr_anti.Ant_Cod = anticipos.Ant_Cod) INNER JOIN comprobantes ON (compr_anti.Com_Cod = comprobantes.Com_Cod)
					  INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN det_plan ON (asientos.Pld_Cod = det_plan.Pld_Cod)
					  INNER JOIN anti_prove ON (det_plan.Pld_Cod = anti_prove.Pld_Cod) WHERE  anticipos.Prv_Cod = $Par_Sql[0] AND 
					  anticipos.Ant_Est = 'A'"; //GROUP BY det_plan.Pld_Des, det_plan.Pld_Cod sum
							  //AND anticipos.Ant_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
			return $sql;
			break;	

			/**
			* Consulta todos los valores de los anticipos ya cruzados 
			*/
			case 194:	
			$sql="SELECT sum(det_antici.Ant_Val) AS Ant_Val FROM compras INNER JOIN det_antici ON (compras.Cop_Cod = det_antici.Cop_Cod)
							WHERE det_antici.Ant_Cod = $Par_Sql[0] AND compras.Cop_Est = 'A'"; 						  
			return $sql;
			break;	

			/**
			* Consulto los tipos de adquisiciones en la base de datos 
			*/
			case 324:
			$sql="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor FROM compras, det_compra, adquisicio, proveedore WHERE 
	$Par_Sql[2]
	AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
	AND det_compra.Adq_Cod=adquisicio.Adq_Cod AND proveedore.Prv_Cod = compras.Prv_Cod  AND proveedore.Emp_Cod = $Par_Sql[4] GROUP BY adquisicio.Adq_Cod ORDER BY adquisicio.Adq_Des ASC";
			//echo $sql;
			return $sql;
			break;

			case 243:
			/**
			* Consulta los años de las facturas de compras recibidas 
			*/
			$sql = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras WHERE  compras.Cop_Est='A' 
						 ORDER BY YEAR(compras.Cop_Fec) DESC";//Antes GROUP BY YEAR(compras.Cop_Fec) compras.Tic_Cod=$Par_Sql[0] AND
			return $sql;
			break;

			case 247:
			/**
			* Consulta los años de las facturas de compras recibidas 
			*/
			$sql = "SELECT DISTINCT YEAR(compras.Cop_Fec) as Anio FROM compras, perio_cont, plan_cuenta WHERE compras.Pec_Cod = perio_cont.Pec_Cod AND perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND plan_cuenta.Emp_Cod = $Par_Sql[0] AND compras.Cop_Est='A' 
						 ORDER BY YEAR(compras.Cop_Fec) DESC";
			return $sql;
			break;

			/**
			* Consulta el codigo del plan de cuentas en base al periodo contable
			*/
			case 248: 
			$sql = "SELECT det_plan.Pla_Cod FROM det_plan, plan_cuenta, comprobantes, asientos WHERE plan_cuenta.Pla_Cod = det_plan.Pla_Cod 
						AND asientos.Pld_Cod = det_plan.Pld_Cod AND asientos.Com_Cod = comprobantes.Com_Cod AND comprobantes.Pec_Cod = $Par_Sql[0] GROUP BY det_plan.Pla_Cod ORDER BY det_plan.Pla_Cod DESC"; 
			//echo $sql;
			return $sql;
			break;

			case 249:
				$sql="SELECT Pld_Cod,Pld_Des FROM det_plan, plan_cuenta WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND det_plan.Pld_Cdc='$Par_Sql[0]' AND Emp_Cod=$Par_Sql[1] AND Pla_Est='A' AND Pld_Est='A' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
			//echo $sql;
			return $sql;		
			break;	
				
			/**
			* Determina cuenta unica del proveedor en el plan de cuentas 
			*/
			case 253:
			$sql = "SELECT ccpp_prove.Pld_Cod, det_plan.Pld_Des, ccpp_prove.Ccp_Def, ccpp_prove.Ccp_Cxp FROM det_plan INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;		

			case 255:
			/**
			* Inserta los datos en cuentas por pagar
			*/
			$sql = "INSERT INTO ccpp_pagar (Com_Cod, Cop_Cod, Cpp_Ven, Cpp_Obs) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'))";
			//echo "<br>".$sql."<br>";
			return $sql;
			break;

			case 256:
			/**
			* Inserta datos del asiento contable
			*/
			$sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
			//echo "<br>".$sql."<br>";
			return $sql;
			break;
		
			/**
			* Consulta los bancos del plan de cuentas actual 
			*/
			case 257:
			$sql="SELECT banco.Pld_Cod, det_plan.Pld_Des, banco.Ban_Cue, banco.Ban_Cod, banco.Ban_Tip FROM det_plan INNER JOIN banco ON (det_plan.Pld_Cod = banco.Pld_Cod)
				  INNER JOIN compr_plan ON (banco.Ban_Cod = compr_plan.Ban_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0] AND compr_plan.Pag_Cod = $Par_Sql[1]";
			return $sql;
			break;
	
			/**
			* Consulta los tipos de pago de las compras 
			*/
			case 258:
			$sql="SELECT DISTINCT compr_plan.Pag_Cod, tipos_pago.Pag_Des, banco.Ban_Tip FROM tipos_pago INNER JOIN compr_plan ON (tipos_pago.Pag_Cod = compr_plan.Pag_Cod)
						  INNER JOIN banco ON (compr_plan.Ban_Cod = banco.Ban_Cod) WHERE tipos_pago.For_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;	
			break;

			/**
			* Consulta el total de las compras
			*/
			case 323:
			$sql="SELECT SUM(det_compra.Cop_Imp-(det_compra.Cop_Imp*det_compra.Cop_Dec/100)) as Importe, compras.Cop_Des, iva.Iva_Cod, iva.Iva_Por, adquisicio.Adq_Des, adquisicio.Adq_Cod 
	FROM det_compra, iva, adquisicio, compras
	WHERE det_compra.Cop_Cod='$Par_Sql[0]' AND det_compra.Iva_Cod=iva.Iva_Cod 
	AND adquisicio.Adq_Cod=det_compra.Adq_Cod AND adquisicio.Adq_Cod='$Par_Sql[1]' 
	AND compras.Cop_Cod=det_compra.Cop_Cod
	/*AND det_compra.Iva_Cod='$Par_Sql[2]' */ /* borrado para q coja 14 % */
	GROUP BY adquisicio.Adq_Cod";
			return $sql;
			break;

			/** 
			* Consultar facturas sin retención 
			*/
			case 326:
			$sql="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
				compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut,
				compras.Tri_Cod, sustento.Tri_Des, 
				SUM(det_compra.Cop_Imp) as Cop_Imp, compras.Cop_Des,
				
				SUM(det_compra.Cop_Imp-(det_compra.Cop_Imp*det_compra.Cop_Dec/100)) as Importe
				
				FROM persona, proveedore,compras, det_compra, sustento, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod
				AND compras.Tri_Cod='$Par_Sql[4]'
				AND sustento.Tri_Cod = compras.Tri_Cod
				AND tipo_compr.Tic_Cod=compras.Tic_Cod
				AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Cop_Fec BETWEEN
				'$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
				AND $Par_Sql[2]  AND proveedore.Emp_Cod = $Par_Sql[5]
				AND compras.Cop_Cod NOT IN(SELECT retencion.Cop_Cod FROM compras,retencion  WHERE retencion.Cop_Cod=compras.Cop_Cod)
				GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Cod ASC"; //compras.Cop_Fec,       
			return $sql;
			break;

			/**
			* consultar los tipos de adquisiciones realizadas en una determinada compra  
			*/
			case 325:
			$sql="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor FROM det_compra,adquisicio 
			WHERE det_compra.Adq_Cod=adquisicio.Adq_Cod AND det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'";
			return $sql;
			break;

			/**
			* Dar de baja a las facturas de compra a las cuales no se les haya generado la retención 
			*/
			case 471:
			$sql="UPDATE compras SET Cop_Est=UPPER('$Par_Sql[1]') WHERE Cop_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta de campos para el cálculo de los totales de la factura 
			*/
			case 473:
			$sql="SELECT compras.Cop_Des ,det_compra.Cop_Int,det_compra.Cop_Pro, det_compra.Cop_Can, det_compra.Cop_Pru, 
			det_compra.Cop_Imp, det_compra.Cop_Dec, iva.Iva_Por
			FROM compras, det_compra, iva
			WHERE  compras.Cop_Cod=det_compra.Cop_Cod AND det_compra.Cop_Cod=$Par_Sql[0] 
			AND iva.Iva_Cod=det_compra.Iva_Cod"; //13 adquisicion gastos AND det_compra.Adq_Cod != 13
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta de facturas de compras por Apellido del proveedor con estado de Factura Activo e Inactivo 
			*/
			case 483:
			$sql="SELECT persona.Prs_Ape, Ret_Cod,compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
				FROM
				  persona
				  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
				  LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
				WHERE
				compras.Tic_Cod=$Par_Sql[1]
				AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND YEAR(Cop_Fec) = '$Par_Sql[2]' $Par_Sql[3]
				AND proveedore.Emp_Cod = $Par_Sql[4] AND Suc_Cod='$_SESSION[Ses_Suc_Cod]'
				ORDER BY  compras.Cop_Cod ASC";// compras.Cop_Fec
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta las facturas de compra por número de la factura de compra con estado activo e inactivo  
			*/
			case 484:
			$sql="SELECT persona.Prs_Ape, Ret_Cod,compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
			FROM
				  persona
				  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
				  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
				WHERE compras.Tic_Cod=$Par_Sql[1] AND compras.Cop_Num='$Par_Sql[0]' AND proveedore.Emp_Cod = $Par_Sql[2] AND Suc_Cod='$_SESSION[Ses_Suc_Cod]'
			AND compras.Cop_Cod ORDER BY  compras.Cop_Cod ASC";
			return $sql;
			break;

			/**
			* Consulta las facturas de compra por RUC  
			*/
			case 485:
			$sql="SELECT persona.Prs_Ape, Ret_Cod,compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
				FROM
				  persona
				  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
				  LEFT JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
				  INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
				  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
				WHERE				
				compras.Tic_Cod=$Par_Sql[1]				
				AND persona.Prs_Ced = '$Par_Sql[0]' AND YEAR(Cop_Fec) = '$Par_Sql[2]' $Par_Sql[3]
				AND proveedore.Emp_Cod = $Par_Sql[4] AND Suc_Cod='$_SESSION[Ses_Suc_Cod]'
				ORDER BY  compras.Cop_Cod ASC"; // compras.Cop_Fec
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta de los porcentajes del I.C.E 
			*/		
			case 527:
			$sql="SELECT SELECT ice.Ice_Int, ice.Ice_Por, ice.Ice_Sri FROM ice, det_compra
			WHERE  ice.Ice_Int=det_compra.Ice_Int AND det_compra.Cop_Int=$Par_Sql[0] AND det_compra.Cop_Cod=$Par_Sql[1]";
			//echo $sql;
			return $sql;
			break;
			
			/**
			* Carga las facturas las retenciones que se deben modifcar producto de la actualización de una factura 
			*/
			case 717:
			$sql="SELECT compras.Cop_Cod FROM compras WHERE compras.Cop_Cod=$Par_Sql[0]
	AND compras.Cop_Cod IN(SELECT retencion.Cop_Cod FROM retencion WHERE retencion.Ret_Est='A')";
			return $sql;
			break; 

			/**
			* Actualiza la cabecera de compras
			*/
			case 719:
			$sql="UPDATE compras SET Tic_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Ciu_Cod=$Par_Sql[2], Cop_Num=UPPER('$Par_Sql[3]'), Cop_Aut=UPPER('$Par_Sql[4]'), Cop_Fec='$Par_Sql[5]', Cop_Reg='$Par_Sql[6]', Cop_Des='$Par_Sql[7]', Cop_Obs=UPPER('$Par_Sql[8]'), Cop_Cad='$Par_Sql[9]', Cop_Imf='$Par_Sql[10]', Tri_Cod='$Par_Sql[11]',Tpc_Cod='$Par_Sql[12]',Cop_Ntd='$Par_Sql[13]',Cop_Nns='$Par_Sql[14]',Cop_Nna='$Par_Sql[15]' WHERE compras.Cop_Cod=$Par_Sql[16]";
			//echo $sql;
			return $sql;
			break;

			/**
			* Consultar facturas que tengan un sustento tributario 
			*/
			case 725:
			$sql="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
				compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut,
				compras.Tri_Cod, sustento.Tri_Des, det_compra.Cop_Imp
				FROM persona, proveedore, compras, det_compra, sustento, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod
				AND compras.Tri_Cod='$Par_Sql[4]'
				AND sustento.Tri_Cod = compras.Tri_Cod
				AND tipo_compr.Tic_Cod=compras.Tic_Cod
				AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Cop_Fec BETWEEN
				'$Par_Sql[0]' AND '$Par_Sql[1]' AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Est='$Par_Sql[3]'
				AND $Par_Sql[2] AND proveedore.Emp_Cod = $Par_Sql[5]				
				GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Cod ASC"; //compras.Cop_Fec,
			//echo $sql;
                        return $sql;
			break;
			
			/**
			* Consulta el Iva utilizado en las facturas de compra
			*/
			case 727:
			/*$con_iva="SELECT det_compra.Iva_Cod, det_compra.Cop_Imp, iva.Iva_Por FROM det_compra,iva WHERE Cop_Cod=$Par_Sql[0]
	AND iva.Iva_Cod=det_compra.Iva_Cod ORDER BY iva.Iva_Por";*/
			$sql="SELECT iva.Iva_Cod, iva.Iva_Por FROM iva WHERE iva.Iva_Est='A' ORDER BY iva.Iva_Por";
			return $sql;
			break;
						
			case 1039:
				$sql="DELETE FROM kardex_ie WHERE Vet_Cod='$Par_Sql[0]'";
			return $sql;
			break;

			
			
			/* Cargado de las cuentas del comprobante (Resumen)*/
			case 306:
			$sql="SELECT asientos.Pld_Cod, det_plan.Pld_Cdc, det_plan.Pld_Des, asientos.Asi_Glo, Asi_Deh, ROUND(Asi_Val,2) as Asi_Val FROM asientos, det_plan WHERE asientos.Com_Cod=$Par_Sql[0] AND asientos.Pld_Cod=det_plan.Pld_Cod ORDER BY asientos.Asi_Deh";
			return $sql;
			break;
			
			/** 
			* Consulta general de facturas 
			*/
			case 537:
			$sql="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num
			FROM
			  persona
			  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
			  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
			  INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
			  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
			  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
			  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
			WHERE compras.Tic_Cod=$Par_Sql[2] AND compras.Cop_Fec BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'			
			AND compras.Cop_Est='$Par_Sql[3]' AND proveedore.Emp_Cod = $Par_Sql[4] AND	Suc_Cod=$_SESSION[Ses_Suc_Cod] 		
			GROUP BY compras.Cop_Cod
			ORDER BY compras.Cop_Fec,compras.Cop_Cod ASC"; 
			//echo $sql;
			return $sql;
			break;

 		    case 338:
			/**
			* Consulta el codigo de retencion del SRI
			*/
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			 FROM renta_iva WHERE renta_iva.Ren_Ret='$Par_Sql[1]' 
			  AND  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%'  ORDER BY renta_iva.Ren_Sri";
			//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
			//echo $sql;
			return $sql;
			break;
			case 339:  //aqui la busqueda 
			/**
			* Consulta el codigo de retencion del SRI
			*/
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			 FROM renta_iva 
                            INNER JOIN reniva_pla ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                            WHERE renta_iva.Ren_Ret='$Par_Sql[1]' AND  reniva_pla.Ren_Tip='C'
			  AND  renta_iva.Ren_Est='A' AND renta_iva.Ren_Con LIKE '%$Par_Sql[3]%' AND plan_cuenta.Pla_Cod='$Par_Sql[2]' AND Emp_Cod='$Par_Sql[4]'  ORDER BY renta_iva.Ren_Sri";
			//Antes renta_iva.Adq_Cod='$Par_Sql[0]' AND
			//echo $sql;
			return $sql;
			
			/**
			* consulta los cheques generados para la factura de compra 
			*/
			 case 346:
				 $sql="SELECT det_plan.Pld_Cod, det_plan.Pld_Des, asientos.Asi_Cod , asientos.Com_Cod, asientos.Asi_Con , asientos.Asi_Val FROM asientos, det_plan, compras, 
			comprobantes, compr_auto
			WHERE 
			asientos.Asi_Deh='H'
			AND asientos.Pld_Cod=det_plan.Pld_Cod
			AND compras.Cop_Cod=compr_auto.Cop_Cod
			AND comprobantes.Com_Cod=compr_auto.Com_Cod
			AND comprobantes.Com_Cod=asientos.Com_Cod
			AND compras.Cop_Cod='$Par_Sql[0]' 
			AND (asientos.Pld_Cod NOT IN (SELECT reniva_pla.Pld_Cod FROM reniva_pla WHERE Ren_Tip='C'))
			AND (asientos.Pld_Cod  NOT IN (SELECT ccpp_prove.Pld_Cod FROM ccpp_prove))";
			return $sql;
			break;

			/**
			* Consulto los bancos considerando 'O' - 'B' 
			*/
			case 347:
			$sql="SELECT banco.Ban_Cod,det_plan.Pld_Cod, det_plan.Pld_Des, banco.Ban_Tip FROM banco, det_plan 
			WHERE banco.Pld_Cod=det_plan.Pld_Cod AND banco.Ban_Tip='$Par_Sql[0]' AND banco.Ban_Fac='A'";
			return $sql;
			break;

			/**
			* Carga los conceptos en la retención en la fuente de impuesto a la renta (AIR) 
			renta_iva.Adq_Cod='$Par_Sql[0]' AND
			*/
			case 361:
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			FROM renta_iva WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
			AND renta_iva.Ren_Est='A'  
			AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri";
			return $sql;
			break;
			 case 363: //aqui2
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, Ren_Con  
			FROM renta_iva INNER JOIN reniva_pla ON renta_iva.Ren_Cod=reniva_pla.Ren_Cod
                            INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod AND reniva_pla.Ren_Tip='C' 
                            INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod WHERE   renta_iva.Ren_Ret='$Par_Sql[1]' 
			AND renta_iva.Ren_Est='A' AND plan_cuenta.Pla_Cod='$Par_Sql[2]' AND Emp_Cod='$Par_Sql[4]'
			AND renta_iva.Ren_Por='$Par_Sql[3]' ORDER BY renta_iva.Ren_Sri";
                            //echo $sql;
			return $sql;
			/**
			* Buscamos las cuenta unica del proveedor en el plan de cuentas 
			*/
			case 362:
			$sql = "SELECT 
					  ccpp_prove.Pld_Cod,
					  ccpp_prove.Ccp_Cxp,
					  det_plan.Pld_Cod
					FROM
					  det_plan
					  INNER JOIN ccpp_prove ON (det_plan.Pld_Cod = ccpp_prove.Pld_Cod)
					  INNER JOIN asientos ON (det_plan.Pld_Cod = asientos.Pld_Cod)
					  INNER JOIN comprobantes ON (comprobantes.Com_Cod = asientos.Com_Cod)
					  INNER JOIN ccpp_pagar ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
					WHERE
					  ccpp_pagar.Cop_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
			/**
			* consulta los cheques generados para la factura de compra 
			*/
			case 367:
			$sql="SELECT banco.Ban_Cod,banco.Ban_Tip, banco.Pld_Cod, cheques.Che_Cod, cheques.Che_Num,  cheques.Che_Fec, cheques.Che_Obs, 
			cheques.Che_Val FROM cheques,  banco,  asientos 
			WHERE 	cheques.Ban_Cod=banco.Ban_Cod
					AND asientos.Asi_Cod=cheques.Asi_Cod
					AND asientos.Asi_Cod='$Par_Sql[0]' ";
			return $sql;
			break;

			case 372:
			/**
			* Consulta el banco
			*/
			$sql="SELECT banco.Ban_Cod,banco.Ban_Tip, banco.Pld_Cod FROM banco WHERE banco.Pld_Cod='$Par_Sql[0]'";
			return $sql;
			break;
			

        /**
		* Consultar el detalle de la retención 
		*/
		case 381:
		$consultar_automatica_manual_381="SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por,  
		(Ret_Bas * Ren_Por)/100 as Val_Ret, det_retenc.Adq_Cod,if(renta_iva.Ren_Ret='R','RENTA','IVA')as Ren_Ret FROM det_retenc, renta_iva WHERE det_retenc.Ren_Cod = renta_iva.Ren_Cod AND det_retenc.Ret_Cod = $Par_Sql[0]";
		return $consultar_automatica_manual_381;
		break;
	
			/**
			* Consultar el codigo de la retencion a modificar en base al codigo de la factura de compra 
			*/
			case 718:
			//$sql="SELECT retencion.Ret_Cod, retencion.Ret_Num, retencion.Aut_Cod,retencion.Ret_Xml,retencion.Ret_Sri,retencion.Ret_Aut FROM retencion WHERE retencion.Ret_Est='A' AND retencion.Cop_Cod='$Par_Sql[0]'";
                        $sql="SELECT retencion.Ret_Cod, retencion.Ret_Num,retencion.Ret_Fec,retencion.Aut_Cod,autorizaci.Aut_Sri,retencion.Ret_Xml,retencion.Ret_Aut 
			FROM retencion,autorizaci WHERE retencion.Aut_Cod=autorizaci.Aut_Cod AND retencion.Ret_Est='A' AND retencion.Cop_Cod='$Par_Sql[0]'";
			//echo "<br>".$sql;
			return $sql;
			break;
						
			/* Consulta que permite saber si la compra es automática o manual */	
			case 369:
			$sql="SELECT compr_auto.Com_Cod FROM compr_auto WHERE compr_auto.Com_Cod = $Par_Sql[0]";						
			return $sql;
			break;
		
 			/* consulta el comprobante de la factura de compra */
			case 345:
			$sql="SELECT  comprobantes.Tia_Cod, comprobantes.Com_Con,  comprobantes.Com_Num,comprobantes.Com_Cod, comprobantes.Com_Fec, comprobantes.Com_Val FROM compras, compr_auto,comprobantes  WHERE compr_auto.Cop_Cod=compras.Cop_Cod AND compr_auto.Com_Cod=comprobantes.Com_Cod AND compras.Cop_Cod='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;
			break;
			
			/**
			* Actualiza la cabecera de la retención
			*/
			case 354:
			$sql="UPDATE retencion SET Cop_Cod='$Par_Sql[0]', 
			Ret_Num='$Par_Sql[1]', Ret_Fec='$Par_Sql[2]',Ret_Con=UPPER('$Par_Sql[3]'), Tic_Cod='$Par_Sql[4]',Vnd_Cod='$Par_Sql[5]',Aut_Cod='$Par_Sql[6]' WHERE retencion.Ret_Cod=$Par_Sql[7]";
			//echo '<br>'.$sql;
			return $sql;
			break;

			/**
			* Elimina el detalle de la retencion 
			*/
			case 355:
			$sql="DELETE FROM det_retenc WHERE Ret_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			/**
			* Inserción de los cheques de los comprobantes de egreso 
			*/
			case 307:
			$sql="INSERT INTO cheques SET Prv_Cod=$Par_Sql[0], Ban_Cod=$Par_Sql[1], Asi_Cod=$Par_Sql[2], Che_Num='$Par_Sql[3]', Che_Val=$Par_Sql[4], Che_Obs=UPPER('$Par_Sql[5]'), Che_Fec='$Par_Sql[6]', Che_Cod = $Par_Sql[7]";
			//echo "<br>".$sql."<br>";
			return $sql;
			break;
			
			/* Consulta los proveedores que pueden recibir varios cheques */
			case 314:
			$sql="SELECT Prv_Cod FROM varicheque";
			return $sql;
			break;
	
			/* CUENTAS POR PAGAR */
			case 801:/*consulta de provedores que tiene pagos pendientes por apellido*/
			$sql= " SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape,  persona.Ide_Cod,
			proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod FROM  persona INNER JOIN proveedore ON (persona.Prs_Cod = 	
			proveedore.Prs_Cod)  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON 	
			(compras.Cop_Cod = ccpp_pagar.Cop_Cod) WHERE  proveedore.Prs_Cod = persona.Prs_Cod AND  Prs_Ape LIKE '%$Par_Sql[0]%'  
			GROUP BY proveedore.Prv_Cod";
			return $sql;
			break;
			
			case 802:/*consulta de provedores que tiene pagos pendientes por cédula*/
			$sql= "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Ide_Cod, 
			proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod FROM  persona INNER JOIN proveedore ON (persona.Prs_Cod = 
			proveedore.Prs_Cod)	INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod
			= ccpp_pagar.Cop_Cod) WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ced= '$Par_Sql[0]'  GROUP BY proveedore.Prv_Cod";
			return $sql;		
			break;
			
			case 803:/*consulta de facturas pendientes segun el proveedor*/
			$sql= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, compras.Cop_Fec, 
			compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val 
			FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
			INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) 
			INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) 
			INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod) 
			INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) 
			INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona
			WHERE proveedore.Prs_Cod = persona.Prs_Cod AND comprobantes.Com_Cod = ccpp_pagar.Com_Cod 
			AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND 
			comprobantes.Com_Est='A' AND proveedore.Prv_Cod = $Par_Sql[0] ORDER BY 
			ccpp_pagar.Cpp_Ven  $Par_Sql[1]"; //AND perio_cont.Pec_Cod= $Par_Sql[2]
			return $sql;
			break;
			
			case 804:/*consulta de pagos echo a las factura de compras a credito*/
			$sql= "SELECT sum(det_ccpp_p.Pag_Val)  as total FROM comprobantes, asientos, det_ccpp_p, ccpp_pagar, compras 
			WHERE compras.Cop_Cod= ccpp_pagar.Cop_Cod  AND ccpp_pagar.Cpp_Cod= det_ccpp_p.Cpp_Cod AND comprobantes.Com_Cod= 
			asientos.Com_Cod AND comprobantes.Com_Cod= det_ccpp_p.Com_Cod AND compras.Cop_Cod= $Par_Sql[0] AND asientos.Asi_Deh='D' AND det_ccpp_p.Pag_Est = 'A'";
			return $sql;
			break;
			
			/* Insercion de un comprobante de Ingreso/Egreso (Cliente/Proveedor) */
			case 805: 
			$sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], Prv_Cod=$Par_Sql[1], Com_Num='$Par_Sql[2]', 
				Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6],Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
				//echo "<br>".$sql."<br>";
			return $sql;
			break;
	
			/* Inserción de cada asiento del comprobante */
			case 806:
			$sql="INSERT INTO asientos SET Com_Cod=$Par_Sql[0], Asi_Deh='$Par_Sql[1]', Asi_Val=$Par_Sql[2], 
			Asi_Con=UPPER('$Par_Sql[3]'), Asi_Glo=UPPER('$Par_Sql[4]'), Pld_Cod=$Par_Sql[5]";
			//echo "<br>".$sql."<br>";
			return $sql;
			break;
			
			/* Inserción de detalle del pago de la factura de credito en la tabla det_ccpp_p */
			case 807:
			$sql="INSERT INTO det_ccpp_p SET Cpp_Cod=$Par_Sql[0], Pag_Cod=$Par_Sql[1], Com_Cod=$Par_Sql[2], 
			Pag_Fec='$Par_Sql[3]', Pag_Val= $Par_Sql[4], Pag_Obs= '$Par_Sql[5]'";
			return $sql;
			break;
			
			case 808:/*consulta para saber los dias que faltan para pagaro*/
			$sql= "SELECT DATEDIFF('$Par_Sql[0]', '$Par_Sql[1]' ) AS dias";
			return $sql;
			break;
			
			case 809:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
			$sql= "SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, 
			compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod,  
			asientos.Asi_Cod, asientos.Asi_Val, comprobantes.Com_Cod FROM  proveedore   INNER JOIN compras 
			ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
			INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
			INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
			INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
			INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona WHERE 
			proveedore.Prs_Cod= persona.Prs_Cod  and comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND 
			asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND perio_cont.Pec_Cod= 
			$Par_Sql[1] AND comprobantes.Com_Est='A' AND compras.Cop_Cod= $Par_Sql[2] ORDER BY ccpp_pagar.Cpp_Ven  $Par_Sql[0]";
			return $sql;
			break;
	
			case 810:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
			$sql= "SELECT proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_compra.Cop_Int,det_compra.Cop_Imp,
			compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
			comprobantes.Com_Cod, compras.Pec_Cod, det_ccpp_p.Pag_Est FROM proveedore 
			INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN det_compra ON (compras.Cop_Cod = 
			det_compra.Cop_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
			INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
			INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
			INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
			INNER JOIN det_ccpp_p ON( det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod )
			WHERE asientos.Asi_Deh = 'H' AND compras.Cop_Est = 'A' AND comprobantes.Com_Est = 'A' AND perio_cont.Pec_Cod=$Par_Sql[0] 
			AND  proveedore.Prv_Cod =$Par_Sql[0] AND det_ccpp_p.Pag_Est='A'";
			return $sql;
			break;

			case 811:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
			$sql= "SELECT proveedore.Prv_Cod, compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_compra.Cop_Int,det_compra.Cop_Imp,
			compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod,  asientos.Asi_Cod, asientos.Asi_Val, 
			comprobantes.Com_Cod FROM proveedore INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) INNER JOIN det_compra ON
			(compras.Cop_Cod = det_compra.Cop_Cod) INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod) INNER JOIN 
			comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) INNER JOIN asientos ON (comprobantes.Com_Cod = 
			asientos.Com_Cod)  INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod) WHERE comprobantes.Com_Cod = 
			ccpp_pagar.Com_Cod AND asientos.Com_Cod= comprobantes.Com_Cod AND asientos.Asi_Deh= 'H' AND compras.Cop_Est='A' AND 
			comprobantes.Com_Est='A' AND perio_cont.Pec_Cod= $Par_Sql[1] AND proveedore.Prv_Cod = $Par_Sql[0] $Par_Sql[2]";
			return $sql;
			break;	
	
			/* Consulta los pagos realizados a los proveedores  */
			case 812:
			$sql="SELECT compras.Cop_Cod, ccpp_pagar.Cpp_Cod, det_ccpp_p.Pag_Val, det_ccpp_p.Pag_Est, 	
			det_ccpp_p.Pag_Fec, det_ccpp_p.Cpp_Cod, det_ccpp_p.Com_Cod, comprobantes.Com_Num FROM  compras
			INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
			INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
			INNER JOIN comprobantes ON (det_ccpp_p.Com_Cod = comprobantes.Com_Cod) WHERE  compras.Cop_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
			/* Consulta el detalle de los pagos de cada proveedor */
			case 813:
			$sql="SELECT  proveedore.Prv_Cod, ccpp_pagar.Cpp_Cod,  ccpp_pagar.Cpp_Ven, det_ccpp_p.Pag_Est,
			det_ccpp_p.Pag_Fec, asientos.Asi_Cod, det_ccpp_p.Com_Cod, comprobantes.Pec_Cod, asientos.Asi_Val
			FROM ccpp_pagar INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) INNER JOIN det_ccpp_p ON 
			(det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod),
			asientos, proveedore WHERE  asientos.Asi_Deh = 'H' AND comprobantes.Com_Est = 'A' AND perio_cont.Pec_Cod = $Par_Sql[0] 
			AND  proveedore.Prv_Cod = $Par_Sql[1]  AND det_ccpp_p.Pag_Est = 'A' AND ccpp_pagar.Cpp_Cod = $Par_Sql[2] 
			AND ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod  AND  det_ccpp_p.Com_Cod = asientos.Com_Cod";
			return $sql;
			break;
	
			case 814:/*CONSULTA TODOS LOS PROVEEDORES QUE TIENEN FACTURA PENDIENTES SEGUN LA FECHA DE VENCIMIENTO*/
			$sql= "SELECT  proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, compras.Cop_Cod, ccpp_pagar.Cpp_Cod,
			compras.Cop_Fec, compras.Cop_Num, ccpp_pagar.Cpp_Ven, ccpp_pagar.Com_Cod, asientos.Asi_Cod, asientos.Asi_Val,
			comprobantes.Com_Cod FROM   proveedore  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod) 
			INNER JOIN ccpp_pagar ON (compras.Cop_Cod = ccpp_pagar.Cop_Cod)
			INNER JOIN comprobantes ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod)
			INNER JOIN asientos ON (comprobantes.Com_Cod = asientos.Com_Cod)
			INNER JOIN perio_cont ON (compras.Pec_Cod = perio_cont.Pec_Cod)
			INNER JOIN ccpp_prove ON (asientos.Pld_Cod = ccpp_prove.Pld_Cod), persona
			WHERE proveedore.Prs_Cod = persona.Prs_Cod AND  comprobantes.Com_Cod = ccpp_pagar.Com_Cod AND  asientos.Com_Cod = 
			comprobantes.Com_Cod AND asientos.Asi_Deh = 'H' AND compras.Cop_Est = 'A' AND  
			comprobantes.Com_Est = 'A' ORDER BY ccpp_pagar.Cpp_Ven, persona.Prs_Ape $Par_Sql[0]"; //AND  perio_cont.Pec_Cod = 3
			return $sql;
			break;						
	
			/****Consulta factura pagadas a proveedores segun elapellido*/
			case 815:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, comprobantes.Com_Cod, 	
			comprobantes.Com_Obs, comprobantes.Com_Num, comprobantes.Com_Fec, comprobantes.Com_Val FROM comprobantes 
			INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
			INNER JOIN persona ON ( persona.Prs_Cod = proveedore.Prs_Cod)
			INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
			INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)
			WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  comprobantes.Tia_Cod = 2 AND comprobantes.Com_Est='A' $Par_Sql[1]  GROUP BY Com_Cod"; 
			return $sql;
			break;
			
			case 349:
			$sql="SELECT form_compr.For_Cod,comprobantes.Com_Cod, forma_pago.For_Des FROM comprobantes, compr_auto,  form_compr, forma_pago
		WHERE 
		forma_pago.For_Cod=form_compr.For_Cod
		AND comprobantes.Com_Cod=compr_auto.Com_Cod 
		AND form_compr.Tia_Cod=comprobantes.Tia_Cod
		AND form_compr.For_Cod=form_compr.For_Cod
		AND compr_auto.Cop_Cod='$Par_Sql[0]'";
			return $sql;
			break;
			
			/* consulto si es compra a credito cargo los datos */
			case 350:
			$sql="SELECT Cpp_Cod, Cpp_Ven, Cpp_Obs FROM ccpp_pagar WHERE Com_Cod='$Par_Sql[0]'";
			return $sql;
			break;	
			
			case 16: 
			/* Consulta la forma de pago */
			$sql = "SELECT For_Cod, For_Des FROM forma_pago WHERE For_Est = 'A' ORDER BY For_Des ASC";
			return $sql;
			break;
			
			/*CONSULTA LOS PAGOS REALIZADAOS EN det_cccpp_p	 BASANDOSE ENEL CODIGO DEL COMPROBANTE D EEGREO*/
			case 816:
			$sql="SELECT  det_ccpp_p.Cpp_Cod, det_ccpp_p.Com_Cod as Com_Pag,  det_ccpp_p.Pag_Fec,  det_ccpp_p.Pag_Val,
			det_ccpp_p.Pag_Est, ccpp_pagar.Cop_Cod, compras.Cop_Num, compras.Cop_Fec, ccpp_pagar.Com_Cod FROM  ccpp_pagar
			INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
			INNER JOIN compras ON (ccpp_pagar.Cop_Cod = compras.Cop_Cod) INNER JOIN comprobantes 
			ON (ccpp_pagar.Com_Cod = comprobantes.Com_Cod) WHERE det_ccpp_p.Com_Cod = $Par_Sql[0]"; 
			return $sql;
			break;
			
			/*CONSULTA EL VALOR DE LA FACtURA EN BASE AL CODIGO DEL COMPROBANTE*/
			case 817:
			$sql="SELECT comprobantes.Com_Val FROM  ccpp_pagar 
			INNER JOIN det_compra ON (ccpp_pagar.Cop_Cod = det_compra.Cop_Cod) 
			INNER JOIN compr_auto ON (det_compra.Cop_Cod = compr_auto.Cop_Cod)
			INNER JOIN comprobantes ON (compr_auto.Com_Cod = comprobantes.Com_Cod)
			WHERE ccpp_pagar.Cop_Cod = $Par_Sql[0]"; 
			return $sql;
			break;
			
			/*ACTUALIZA EL CONCEPTO Y LA OBSERVACION DEL COMPROBANTE*/
			case 818:
			$sql="UPDATE comprobantes SET Com_Con ='$Par_Sql[0]', Com_Obs= '$Par_Sql[1]' WHERE Com_Cod = $Par_Sql[2]";
			return $sql;
			break;
			
			/****Consulta factura pagadas a proveedores segun la cedula*/
			case 819:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Ape, persona.Prs_Nom, comprobantes.Com_Cod, 	
			comprobantes.Com_Obs, comprobantes.Com_Num, comprobantes.Com_Fec, comprobantes.Com_Val FROM comprobantes 
			INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
			INNER JOIN persona ON ( persona.Prs_Cod = proveedore.Prs_Cod)
			INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
			INNER JOIN ccpp_pagar ON (det_ccpp_p.Cpp_Cod = ccpp_pagar.Cpp_Cod)
			WHERE persona.Prs_Ced= $Par_Sql[0] AND  comprobantes.Tia_Cod = 2 AND comprobantes.Com_Est='A' $Par_Sql[1]  GROUP BY Com_Cod";
			return $sql;
			break;
			
			/*ACTUALIZA EL ESTADO DEL COMPROBANTE DE ACTIVO A INACTIVO*/
			case 820:
			$sql="UPDATE comprobantes SET Com_Est ='I' WHERE Com_Cod =$Par_Sql[0]";
			return $sql;
			break;
			
			/*ACTUALIZA EL ESTADO DEL PAGO DE ACTIVOA A INACTIVO*/
			case 821:
			$sql="UPDATE det_ccpp_p SET Pag_Est ='I' WHERE Com_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
			/****Consulta factura pagadas a proveedores segun elapellido*/
			case 822:
			$sql="SELECT proveedore.Prv_Cod,persona.Prs_Nom,persona.Prs_Ced, persona.Prs_Ape, comprobantes.Com_Val, 
			comprobantes.Com_Cod,det_ccpp_p.Cpp_Cod ,
			comprobantes.Com_Num, comprobantes.Com_Est, asientos.Asi_Deh, det_ccpp_p.Pag_Cod, det_ccpp_p.Pag_Val, comprobantes.Com_Fec
			FROM comprobantes INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
			INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) INNER JOIN det_ccpp_p 
			ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod) INNER JOIN asientos ON (det_ccpp_p.Com_Cod = asientos.Com_Cod)
			AND (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
			WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  asientos.Asi_Deh = 'H' AND  comprobantes.Tia_Cod =2 
			AND perio_cont.Pec_Cod = $Par_Sql[2]  $Par_Sql[1]  GROUP by comprobantes.Com_Cod"; 
			return $sql;
			break;
			
			/****Consulta factura pagadas a proveedores segun la cedula*/
			case 823:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced,
			comprobantes.Com_Val,comprobantes.Com_Cod, det_ccpp_p.Cpp_Cod ,
			comprobantes.Com_Num, comprobantes.Com_Est, asientos.Asi_Deh, det_ccpp_p.Pag_Cod, det_ccpp_p.Pag_Val, comprobantes.Com_Fec
			FROM comprobantes INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod) 
			INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) INNER JOIN det_ccpp_p 
			ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod) INNER JOIN asientos ON (det_ccpp_p.Com_Cod = asientos.Com_Cod)
			AND (comprobantes.Com_Cod = asientos.Com_Cod) INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
			WHERE comprobantes.Com_Num= $Par_Sql[0] AND comprobantes.Tia_Cod=2 and asientos.Asi_Deh = 'H' 
			AND perio_cont.Pec_Cod = $Par_Sql[1]  $Par_Sql[2]";
			return $sql;
			break;
			
			/****Consulta factura pagadas a proveedores segun elapellido*/
			case 824:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape,  persona.Prs_Ced, comprobantes.Com_Val,
			comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, comprobantes.Com_Fec FROM comprobantes
			INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod)
			INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
			INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
			 INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
			WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND  comprobantes.Tia_Cod =2 AND 
			perio_cont.Pec_Cod = $Par_Sql[2]  $Par_Sql[1] GROUP BY comprobantes.Com_Cod"; 
			return $sql;
			break;
			
			/****Consulta factura pagadas a proveedores segun el nnumero de comprobantes*/
			case 825:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Nom, persona.Prs_Ape,  persona.Prs_Ced, comprobantes.Com_Val,
			comprobantes.Com_Cod, comprobantes.Com_Num, comprobantes.Com_Est, comprobantes.Com_Fec FROM comprobantes
			INNER JOIN proveedore ON (proveedore.Prv_Cod = comprobantes.Prv_Cod)
			INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod) 
			INNER JOIN perio_cont ON (comprobantes.Pec_Cod = perio_cont.Pec_Cod)
			INNER JOIN det_ccpp_p ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
			WHERE comprobantes.Com_Num= $Par_Sql[0] AND comprobantes.Tia_Cod=2 
			AND perio_cont.Pec_Cod = $Par_Sql[1]  $Par_Sql[2] GROUP BY comprobantes.Com_Cod" ; 
			return $sql;
			break;

			/* Consultar si la compra tiene retenciones anuladas */
			case 382:
			$sql="SELECT retencion.Ret_Cod FROM retencion WHERE Cop_Cod=$Par_Sql[0] AND retencion.Ret_Est='I'";
			return $sql;
			break;
					
			 case 378:
			 /**
			 * Consulta las autorizaciones 
			 */
			 $sql="SELECT autorizaci.Aut_Cod,  autorizaci.Pun_Cod, autorizaci.Tic_Cod, autorizaci.Aut_Sri, autorizaci.Aut_Ini FROM autorizaci 
			 WHERE autorizaci.Pun_Cod=$Par_Sql[0] AND autorizaci.Tic_Cod=$Par_Sql[1] AND autorizaci.Aut_Est = 'A'";
			 //echo $sql;
			 return $sql;
			 break;
			 			 
			case 511:
			/**
			* Consulta el maximo numero de las retenciones
			*/
			$sql="SELECT MAX(Ret_Num) AS Ret_Ide FROM retencion WHERE Aut_Cod = $Par_Sql[0]";
			return $sql;
			break;

			case 517:
			/**
			* Consulta la autorizacion de un documento especifico
			*/
			$sql="SELECT vendedor.Vnd_Cod, autorizaci.Aut_Sri, autorizaci.Pun_Sri,  autorizaci.Aut_Cod, autorizaci.Aut_Fci, autorizaci.Aut_Cad, autorizaci.Aut_Ini, autorizaci.Aut_Fin, autorizaci.Aut_Est, puntos_imp.Pun_Des, puntos_imp.Pun_Ubi FROM vendedor, puntos_imp, autorizaci, tipo_compr, persona
		 WHERE tipo_compr.Tic_Cod=autorizaci.Tic_Cod AND autorizaci.Tic_Cod=$Par_Sql[1] AND puntos_imp.Pun_Cod=vendedor.Pun_Cod 
		 AND puntos_imp.Pun_Cod=autorizaci.Pun_Cod AND autorizaci.Aut_Est='A'
		 AND puntos_imp.Pun_Est='A' AND persona.Prs_Cod=vendedor.Prs_Cod AND persona.Prs_Cod=$Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[2]
		 AND vendedor.Vnd_Est='A'  ";
			//echo $sql;
			return $sql;
			break;
				
			case 518:
			/**
			* Consulta las retenciones emitidas
			*/
			$sql="SELECT Ret_Cod, Ret_Num FROM retencion WHERE retencion.Aut_Cod=$Par_Sql[0] LIMIT 0,1";
			return $sql;
			break;
				
				
			case 254:
			/**
			* Relaciona una compra y un comprobante para saber que es automatico
			*/
			$sql = "INSERT INTO compr_auto (Com_Cod, Cop_Cod) VALUES ($Par_Sql[0], $Par_Sql[1])";
			return $sql;
			break;
			
			/**
			* Inserta el detalle en det_antici 
			*/
			case 195:	
			$sql="INSERT INTO det_antici (Ant_Cod, Cop_Cod, Ant_Val) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2])"; 						  
			//echo $sql; 
			return $sql;
			break;							
				
			case 360:
			/**
			* Selecciona la cuenta contable	del codigo de retenciion-iva del sri
			*/
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, reniva_pla.Pld_Cod  FROM renta_iva, reniva_pla WHERE 
				 renta_iva.Ren_Cod=$Par_Sql[0] AND reniva_pla.Ren_Cod=renta_iva.Ren_Cod AND reniva_pla.Ren_Tip='C'";
			//echo "<br>".$sql;
			return $sql;
			break;	
			
			case 1043:
			/**
			* Selecciona la cuenta contable	del codigo de retenciion-iva del sri
			*/
			$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por FROM renta_iva WHERE renta_iva.Ren_Cod=$Par_Sql[0]";
			//echo "<br>".$sql;
			return $sql;
			break;	

			case 491:
			/**
			* Inserta datos de la retención
			*/
			$sql="INSERT INTO retencion (Cop_Cod, Ret_Num, Ret_Fec, Ret_Con, Tic_Cod, Vnd_Cod, Aut_Cod, Ret_Xml) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', UPPER('$Par_Sql[3]'), $Par_Sql[4], $Par_Sql[5], $Par_Sql[6],'$Par_Sql[7]')";
			//echo '<br>'.$sql;
			return $sql;
			break;
			
			case 492:
			/**
			* Inserta el detalle de la retención
			*/
			$sql="INSERT INTO det_retenc(Ret_Cod,Ret_Bas, Ren_Cod, Ret_Imp, Ret_Int, Adq_Cod)
			VALUES($Par_Sql[0],'$Par_Sql[1]',$Par_Sql[2],UPPER('$Par_Sql[3]'),'$Par_Sql[4]', $Par_Sql[5])";
			//echo '<br>'.$sql;
			return $sql;
			break;							
			
			/*** consultar los tipos de adquisiciones realizadas en una determinada compra  ****/
			case 325:
			$sql="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor FROM det_compra,adquisicio 
			WHERE det_compra.Adq_Cod=adquisicio.Adq_Cod AND det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'   ";
			return $sql;
			break;
		
			/**
			* Consulta los datos del proveedor de una compra
			*/
			case 472: 
			$sql="SELECT persona.Prs_Cod, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, persona.Prs_Dir, compras.Cop_Cod,
	compras.Cop_Num, compras.Prv_Cod, compras.Cop_Aut, compras.Tpc_Cod, compras.Ciu_Cod, compras.Cop_Fec, compras.Cop_Reg, compras.Cop_Cad, compras.Tic_Cod,
	compras.Cop_Imf, compras.Cop_Des, compras.Pec_Cod, det_compra.Cop_Int, det_compra.Cop_Pru, compras.Cop_Obs, compras.Cop_Est, 
	det_compra.Cop_Pro, det_compra.Cop_Can, det_compra.Cop_Imp, det_compra.Cop_Dec, det_compra.Iva_Cod, det_compra.Ice_Int, iva.Iva_Por, Cop_Ice, 
	ciudad.Ciu_Des, compras.Tri_Cod, sustento.Tri_Des, adquisicio.Adq_Des, compras.Tic_Cod, tipo_compr.Tic_Des,det_compra.Pro_Cod,adquisicio.Adq_Cor,adquisicio.Adq_Cod,Iva_Cos
	
	FROM persona, proveedore, compras, det_compra, iva, ciudad, sustento, adquisicio, tipo_compr 
	WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod AND adquisicio.Adq_Cod = det_compra.Adq_Cod 
	AND compras.Cop_Cod=det_compra.Cop_Cod AND compras.Cop_Cod=$Par_Sql[0] 
	AND compras.Tic_Cod= tipo_compr.Tic_Cod
	AND det_compra.Iva_Cod=iva.Iva_Cod AND ciudad.Ciu_Cod=compras.Ciu_Cod AND sustento.Tri_Cod= compras.Tri_Cod ";
			//echo $sql; 
			return $sql; 
			break;
			
			/* Consulto el detalle de la cuenta contable relacionda con el detalle de la factura de compra */
			case 343:
			$sql="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc,det_plan.Pld_Des FROM det_compra,det_plan 
			WHERE det_compra.Pld_Cod=det_plan.Pld_Cod AND det_compra.Cop_Int='$Par_Sql[0]' AND det_compra.Cop_Cod='$Par_Sql[1]'   ";
			//echo $sql;
			return $sql;
			break;
			
			/* consulta los detalles de las retenciones OJOOOOOOOOOXOXOXOXOXOXOXOXOXOXXXXXXXXX 20/08/2009  */
			case 344:
			$det_compra_renta_344 = "SELECT det_retenc.Ren_Cod, Ren_Sri, Ren_Con, Ret_Bas, Ren_Por, (Ret_Bas * Ren_Por)/100 as Val_Ret 
		FROM det_retenc, renta_iva 
		WHERE det_retenc.Ren_Cod = renta_iva.Ren_Cod 
		AND det_retenc.Ret_Int='$Par_Sql[0]' AND det_retenc.Ret_Imp='$Par_Sql[1]' AND  det_retenc.Ret_Cod='$Par_Sql[2]'  ";
			//echo $det_compra_renta_344;
			return $det_compra_renta_344;
			break;

			case 720: 
			/**
			* Inserta el detalle de la compra
			*/
			$sql = "INSERT INTO 
			det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod, Ice_Int, Cop_Int, Pld_Cod,Pro_Cod) 
			VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'),$Par_Sql[4],$Par_Sql[5],'$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8], '$Par_Sql[9]',$Par_Sql[10],$Par_Sql[11])";  //  fue remplazado $Par_Sql[10]
			//echo "<br>".$sql."<br>";
			return $sql;
			break;

			case 52:
			/**
			* Consulta de los rubros sin tomar en cuenta la tabla unicación
			*/ 
			$sql = "SELECT  producto.Pro_Cod,Pro_Obs, Pro_Gen,Pro_Bar,Pre_Pvp, CONCAT(Ite_Lar,'  ', Pro_Obs) as Ite_Lar,Pre_Est,Mar_Des,Pro_Cdc,adquisicio.Adq_Cod,adquisicio.Adq_Cor, adquisicio.Adq_Des, producto.Iva_Cod, iva.Iva_Por 
					FROM 
						producto,item,precios,marca,tipo_preci,adquisicio, iva 
					WHERE 
						producto.Mar_Cod = marca.Mar_Cod  AND 
						precios.Pro_Cod=producto.Pro_cod AND 
						producto.Ite_Cod=item.Ite_Cod AND 
						producto.Adq_Cod=adquisicio.Adq_Cod AND 						
						tipo_preci.Tpv_Cod=precios.Tpv_Cod AND 
						producto.Iva_Cod = iva.Iva_Cod AND Tpv_Def='D' AND 
                                                producto.Pro_Est='A' AND  
						(Ite_Lar LIKE '%$Par_Sql[0]%' or Pro_Obs LIKE '%$Par_Sql[0]%') AND precios.Suc_Cod = $Par_Sql[1]";
			//echo $sql;

			return $sql;
			break;
case 53:
			/**
			* Consulta de los rubros sin tomar en cuenta la tabla unicación
			*/ 
			$sql = "SELECT producto.Pro_Cod,produ_plan.Pld_Cod,Pld_Cdc,Pld_Des,Pro_Obs, Pro_Gen,Pro_Bar,Pre_Pvp, CONCAT(Ite_Lar,' ', Pro_Obs) as Ite_Lar,Pre_Est,Mar_Des,Pro_Cdc,adquisicio.Adq_Cod,adquisicio.Adq_Cor, adquisicio.Adq_Des, producto.Iva_Cod, iva.Iva_Por 
FROM producto  
INNER JOIN item ON producto.Ite_Cod=item.Ite_Cod
INNER JOIN precios ON precios.Pro_Cod=producto.Pro_cod 
INNER JOIN marca ON producto.Mar_Cod = marca.Mar_Cod 
INNER JOIN tipo_preci ON tipo_preci.Tpv_Cod=precios.Tpv_Cod
INNER JOIN adquisicio ON producto.Adq_Cod=adquisicio.Adq_Cod 
INNER JOIN iva ON producto.Iva_Cod = iva.Iva_Cod 
INNER JOIN produ_plan ON producto.Pro_Cod= produ_plan.Pro_Cod 
INNER JOIN det_plan ON det_plan.Pld_Cod= produ_plan.Pld_Cod
INNER JOIN plan_cuenta ON det_plan.Pla_Cod= plan_cuenta.Pla_Cod
INNER JOIN perio_cont ON perio_cont.Pla_Cod= plan_cuenta.Pla_Cod
WHERE  Tpv_Def='D' AND Pec_Cod='$Par_Sql[2]' AND producto.Pro_Est='A' AND (Ite_Lar LIKE '%$Par_Sql[0]%' or Pro_Obs LIKE '%$Par_Sql[0]%') AND precios.Suc_Cod = $Par_Sql[1] AND (Tip_Pld='C' OR Tip_Pld='I')";
			//echo $sql;
			return $sql;
			
			case 250:
			/**
			* Instar un comprobante contable
			*/
			$sql="INSERT INTO comprobantes SET Pec_Cod=$Par_Sql[0], $Par_Sql[8]=$Par_Sql[1], Com_Num='$Par_Sql[2]', Com_Fec='$Par_Sql[3]', Com_Con=UPPER('$Par_Sql[4]'), Tia_Cod='$Par_Sql[5]', Com_Val=$Par_Sql[6], Com_Obs=UPPER('$Par_Sql[7]'),Com_Gen='A',Usu_Cod='$_SESSION[Ses_Usu_Cod]'";//Antes Com_Tip
			//echo "<br>".$sql."<br>"; 
			return $sql;
			break;
				
		
		
			case 251:
			/**
			* Consulta el tipo de comprobante
			*/
			$sql="SELECT Tia_Cod FROM form_compr WHERE For_Cod = $Par_Sql[0]";
			return $sql;
			break;
		
			/**
			* Consulta para determinar el iva pagado de un plan de cuentas 
			*/
			case 252:
			$sql = "SELECT iva_pagado.Pld_Cod FROM det_plan INNER JOIN iva_pagado ON (det_plan.Pld_Cod = iva_pagado.Pld_Cod) WHERE det_plan.Pla_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
			/* elimino de la base de datos el registro de compra a modificar */
			case 348:
			$sql="DELETE FROM det_compra WHERE Cop_Cod='$Par_Sql[0]'";
			//echo '<br>'.$sql.'<br>'; 
			return $sql; 
			break;
			
			/**
			* Actualizacion de la cabecera del comprobante 
			*/
			case 191:	
			$sql="UPDATE comprobantes SET Com_Con=UPPER('$Par_Sql[0]'), Com_Obs=UPPER('$Par_Sql[1]'), Com_Val=$Par_Sql[3], Com_Num = '$Par_Sql[4]', Com_Fec = '$Par_Sql[5]' WHERE Com_Cod=$Par_Sql[2]";
			return $sql;
			break;	
		
			/**
			* Consultar el detalle del comprobante de compra/asientos del comprobante 
			*/
			case 364:
			$sql="SELECT Asi_Cod, Com_Cod, Asi_Deh, Asi_Val, Asi_Con, Pld_Cod, Asi_Glo FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
			return $sql;
			break;
	
	 		/* elimino el asiento del comprobante */
			case 353:
			$sql="DELETE FROM asientos WHERE Com_Cod='$Par_Sql[0]'";
			return $sql;
			break;
 
 
			/* elimino de la base de datos los cheques del asiento contable */
			case 365:
			$sql="DELETE FROM cheques WHERE Asi_Cod='$Par_Sql[0]' ";
			return $sql; 
			break;

			case 701:
			/**
			* Búsqueda de un proveedor por apellido 
			*/
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Ced, Prs_Dir,persona.Prs_Ape, persona.Prs_Nom, proveedore.Prv_Fax,
						IF (Prv_Est='A','Activo','Inactivo') as Prv_Est,Prv_Con,Prv_Esp
					   FROM proveedore INNER JOIN persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ape LIKE '%$Par_Sql[0]%' AND proveedore.Emp_Cod = $Par_Sql[1]";
			return $sql;
			break;

			/**
			* Búsqueda de un proveedor por Cédula 
			*/
			case 702:
			$sql="SELECT proveedore.Prv_Cod, persona.Prs_Ced, Prs_Dir,persona.Prs_Ape, persona.Prs_Nom, proveedore.Prv_Fax,
						IF (Prv_Est='A','Activo','Inactivo') as Prv_Est,Prv_Con,Prv_Esp
					   FROM proveedore INNER JOIN persona WHERE proveedore.Prs_Cod = persona.Prs_Cod AND Prs_Ced= '$Par_Sql[0]' AND proveedore.Emp_Cod = $Par_Sql[1]";
			return $sql;
			break;
		
			case 704: 
			/**
			* insertar datos de la factura de compra
			*/
			$sql = "INSERT INTO compras (Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Aut, Cop_Fec, Cop_Reg, Cop_Obs, Cop_Cad, Cop_Imf, Tri_Cod, Cop_Des, Pec_Cod,Tpc_Cod,Cop_Ntd,Cop_Nns,Cop_Nna,Vnd_Cod) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], UPPER('$Par_Sql[3]'), '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', UPPER('$Par_Sql[8]'), '$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]', $Par_Sql[12], '$Par_Sql[13]','$Par_Sql[14]', '$Par_Sql[15]', '$Par_Sql[16]','$Par_Sql[17]')";		
			//echo $sql."<br>";
			return $sql;
			break;

			/**
			* Consulta de los iva activos 
			*/
			case 706:		
			$sql = "SELECT iva.Iva_Cod, Iva_Por FROM iva WHERE iva.Iva_Est = 'A'";
			return $sql;
			break;

			/** 
			* Consultas de porcentajes del I.C.E. 
			*/
			case 707:
			$sql="SELECT Ice_Int, Ice_Cod, Ice_Por, Ice_Sri FROM ice WHERE ice.Ice_Est='A'";
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulta de los datos del proveedor 
			*/
			case 708:	
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, 
							persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Ciu_Cod, persona.Prs_Cor, proveedore.Prv_Cod,Prv_Con,Prv_Esp
							FROM proveedore, persona WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Cod = '$Par_Sql[0]'";
			return $sql;
			break;		
			
			/**
			* Consulta de las ciudades 
			*/
			case 709:
			$sql="SELECT Ciu_Des, Ciu_Cod FROM ciudad WHERE Ciu_Des != '' ORDER BY Ciu_Des ASC";
			return $sql;
			break;
			
			/** 
			* Cargar el sustento tributario 
			*/			
			case 711:
			$sql="SELECT sustento.Tri_Sri, sustento.Tri_Cod, sustento.Tri_Des, sustento.Tri_Est FROM sustento WHERE sustento.Tri_Est='A'";
			return $sql;
			break;

			/**
			* Consulta las adquisiciones
			*/
			case 712:
			$sql="SELECT adquisicio.Adq_Cod, adquisicio.Adq_Cor , adquisicio.Adq_Des, adquisicio.Adq_Est FROM adquisicio WHERE adquisicio.Adq_Est='A'"; 
			return $sql;
			break;

	 		/**
			*  Consulta las facturas que se pueden modificar con Estado=Activa 
			*/
			case 713:
			$sql="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, compras.Tpc_Cod, compras.Cop_Ntd, compras.Cop_Nns, compras.Cop_Nna,tipo_compr.Tic_Des 
			FROM persona, proveedore, compras, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND compras.Tic_Cod = $Par_Sql[1]
			AND Pec_Cod = '$Par_Sql[2]' $Par_Sql[3] ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom"; //AND compras.Cop_Est='A'
			//echo $sql;
			return $sql;
			break;
			
			
			case 714:
			$sql="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, compras.Tpc_Cod, compras.Cop_Ntd, compras.Cop_Nns, compras.Cop_Nna,tipo_compr.Tic_Des
				FROM persona, compras, proveedore, tipo_compr
				WHERE persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Prv_Cod=compras.Prv_Cod	
				AND compras.Tic_Cod = tipo_compr.Tic_Cod
				AND compras.Cop_Num='$Par_Sql[0]' AND Pec_Cod = '$Par_Sql[2]' $Par_Sql[3]  ORDER BY compras.Cop_Cod ASC "; //AND compras.Cop_Est='A'
			return $sql;
			break;

			/**
			*  Consulta las facturas por RUC
			*/
			case 715:
			$sql="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, proveedore.Prv_Cod, compras.Tic_Cod, compras.Tpc_Cod, compras.Cop_Ntd, compras.Cop_Nns, compras.Cop_Nna, tipo_compr.Tic_Des 
			FROM persona, proveedore, compras, tipo_compr WHERE persona.Prs_Cod=proveedore.Prs_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND persona.Prs_Ced = '$Par_Sql[0]' AND compras.Tic_Cod = $Par_Sql[1]
			AND Pec_Cod = '$Par_Sql[2]' $Par_Sql[3] ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom"; //AND compras.Cop_Est='A'
			//echo $sql;
			return $sql;
			break;			
			/**
			* Tipo de documento
			*/
			case 729:
			$sql="SELECT tipo_compr.Tic_Cod,Tic_Sri, tipo_compr.Tic_Des, tipo_compr.Tic_Est
						FROM tipo_compr WHERE tipo_compr.Tic_Est='A'";
			//echo $sql;
			return $sql;
			break;			
		
			case 334:
			/**
			* Verifica si una compra ya ha sido ingresada
			*/
			$sql="SELECT compras.Cop_Num FROM compras WHERE compras.Cop_Num='$Par_Sql[0]' AND compras.Prv_Cod='$Par_Sql[1]' AND compras.Tic_Cod='$Par_Sql[2]' $Par_Sql[3] AND compras.Cop_Est='A'";
			//echo $sql;
			return $sql;
			break;
						

			/**
			* Consulto si existen pagos relizados por una compra realizada
			*/
			case 357:
			$sql="SELECT ccpp_pagar.Cpp_Cod FROM ccpp_pagar INNER JOIN det_ccpp_p ON (ccpp_pagar.Cpp_Cod = det_ccpp_p.Cpp_Cod)
				INNER JOIN comprobantes ON (comprobantes.Com_Cod = det_ccpp_p.Com_Cod)
			 WHERE det_ccpp_p.Pag_Est='A' AND Com_Est='A' AND ccpp_pagar.Com_Cod = $Par_Sql[0]";
			return $sql;
			break;

			/**
			* Baja lógica del comprobante en la base de datos
			*/
			case 359:
			$sql="UPDATE comprobantes SET Com_Est='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;
			break;

			/**
			* Consulto el numero del comprobante de compra 
			*/
			case 366:
			$sql="SELECT comprobantes.Com_Num,comprobantes.Com_Cod FROM comprobantes, compr_auto WHERE comprobantes.Com_Cod=compr_auto.Com_Cod AND compr_auto.Cop_Cod='$Par_Sql[0]' ";
			return $sql;
			break;

			/**
			* Consultar las retencion sin considerar el estado en la BD 
			*/
			case 373:
			$sql="SELECT retencion.Ret_Cod, retencion.Ret_Num FROM retencion WHERE  retencion.Cop_Cod='$Par_Sql[0]'";
			return $sql;
			break;

			 /**
			 * Consulta si existe la retencion ya registrada en el mismo punto de venta 
			 * Quite la restricción a que la retención sea solo duplicada por la persona que registro la compra retencion.Vnd_Cod=$Par_Sql[0] AND 
			 */
			case 376:
			$sql="SELECT retencion.Vnd_Cod, retencion.Ret_Cod FROM retencion WHERE  retencion.Ret_Num='$Par_Sql[1]' AND retencion.Aut_Cod=$Par_Sql[2] AND retencion.Ret_Est='A'";
	                                        
			 //echo $sql;
			 return $sql;
			 break; 

		/**
		* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por Apellido del proveedor 
		*/		
		case 468:		
		$sql="SELECT persona.Prs_Ape, tipo_compr.Tic_Des, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, 
		persona.Prs_Nom, compras.Cop_Num
		FROM persona, compras, proveedore, tipo_compr
		WHERE
		tipo_compr.Tic_Cod=compras.Tic_Cod
		AND persona.Prs_Cod=proveedore.Prs_Cod
		AND proveedore.Prv_Cod=compras.Prv_Cod
		AND persona.Prs_Ape LIKE '%$Par_Sql[0]%'
		AND compras.Pec_Cod = $Par_Sql[1] AND compras.Tic_Cod = $Par_Sql[3] $Par_Sql[2] 
		ORDER BY compras.Cop_Cod ASC, Prs_Ape, Prs_Nom
		";
		return $sql;
		break;
		
		/**
		* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por número de factura de compra 
		*/
		case 469:
		$sql="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, tipo_compr.Tic_Des
			FROM persona, compras, proveedore, tipo_compr
			WHERE persona.Prs_Cod=proveedore.Prs_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod 
			AND compras.Cop_Num='$Par_Sql[0]' AND compras.Pec_Cod = $Par_Sql[1] AND compras.Tic_Cod = $Par_Sql[2]
			ORDER BY compras.Cop_Cod";
		return $sql;
		break;
		
		/**
		* Carga las facturas que se pueden anular que no se le ha generado la retencion buscando por cedula del proveedor 
		*/
		case 470:
		$sql="SELECT persona.Prs_Ape, compras.Cop_Est, compras.Cop_Cod, compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, tipo_compr.Tic_Des
			FROM persona, compras, proveedore, tipo_compr
			WHERE persona.Prs_Cod=proveedore.Prs_Cod
			AND proveedore.Prv_Cod=compras.Prv_Cod AND compras.Tic_Cod = tipo_compr.Tic_Cod 
			AND persona.Prs_Ced='$Par_Sql[0]' AND compras.Pec_Cod = $Par_Sql[1] AND compras.Tic_Cod = $Par_Sql[3] $Par_Sql[2]
			ORDER BY compras.Cop_Cod";
		return $sql;
		break;

		/**
		* Dar de baja a la retencion por codigo de la factura dada de baja 
		*/
		case 510:
		$sql="UPDATE retencion SET Ret_Est=UPPER('$Par_Sql[1]') WHERE Cop_Cod=$Par_Sql[0] ";
		//echo $sql;
		return $sql;
		break;

		/**
		* Consulta el detalle de las compras 
		*/	
		case 723:
		$sql= "SELECT iva.Iva_Cod, det_compra.Cop_Pro, iva.Iva_Por, det_compra.Cop_Int, det_compra.Cop_Can, det_compra.Cop_Imp,
							  det_compra.Cop_Pru, compras.Cop_Obs, det_compra.Pro_Cod FROM det_compra INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
							  INNER JOIN compras ON (det_compra.Cop_Cod = compras.Cop_Cod) WHERE det_compra.Cop_Cod = $Par_Sql[0]";
		return $sql;
		break;

		case 1204: 
		/**
		* Consulta sentencia consulto stock del kardex  
		*/
		$sql= "UPDATE stock SET Stk_Can='$Par_Sql[0]' WHERE Pro_Cod='$Par_Sql[1]' AND Suc_Cod='$Par_Sql[2]'" ;	
		//echo '<br>'.$sql;
		return $sql;
		break;
			
		case 1206: 
		/**
		* Consulta sentencia consulto stock del kardex  
		*/
		$sql= "SELECT (SUM(Kar_Can)-SUM(Kar_Sal)) AS Stock
		FROM kardex_ie WHERE Pro_Cod=$Par_Sql[0] AND Kar_Est='A'";	
		//echo $sql;
                return $sql;
		break;
		
		/**
		*  Validar las Adquisiciones si son bienes
		*/
		case 1037:
		$sql = "SELECT adquisicio.Adq_Cod FROM producto,adquisicio WHERE producto.Adq_Cod=adquisicio.Adq_Cod AND adquisicio.Adq_Cor='B' AND producto.Pro_Cod=$Par_Sql[0]";
		return $sql;		
		break;
		
		case 1040:
		$bor_precio="DELETE FROM kardex_ie WHERE Cop_Cod='$Par_Sql[0]'";
		return $bor_precio;
		break;
		
		/**
		*  Validar las Adquisiciones si son bienes
		*/
		case 1041:
	    $sql = "SELECT Cof_Con FROM confi_fact WHERE Emp_Cod=$Par_Sql[0]";
		return $sql;		
		break;
		
		case 1042:
		/* Insertar liquidacion */
		$sql= "INSERT INTO liquidacio (Rcb_Cod, Cop_Cod, Liq_Est) VALUES ($Par_Sql[0],$Par_Sql[1],'L')";
		//echo $sql;
		return $sql;
		break;

		case 1043:
		/**
		* Selecciona la cuenta contable	del codigo de retenciion-iva del sri
		*/
		$sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por  FROM renta_iva WHERE 
			 renta_iva.Ren_Cod=$Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;

		/**
	       *  Consulta los datos del vale de caja chica
	    	*/
	    	case 1044:
		$sql="SELECT 
			recibo.Rcb_Num,
			recibo.Rcb_Cod,
			recibo.Rcb_Tot,
			recibo.Rcb_Cam
		  FROM
			recibo
		  WHERE
			recibo.Rcb_Cod = $Par_Sql[0]";
		return $sql;
	    	break;

		/**
		*  Actualizamos el Vale de caja chica
		*/
		case 1045:		
		$sql= "UPDATE recibo SET Rcb_Cam = $Par_Sql[0] WHERE Rcb_Cod = $Par_Sql[1]";
		//echo $sql;
		return $sql;
		break;	

		/**
	    	*  Consulta si una compra posee una Liquidacion con vales de caja chica
	    	*/
	    	case 1046:
			$sql="SELECT
			     liquidacio.Cop_Cod,
			     liquidacio.Rcb_Cod,
			     liquidacio.Cja_Cod,
			     liquidacio.Liq_Est,
			     recibo.Rcb_Cam,
			     recibo.Rcb_Tot,
			     recibo.Rcb_Num	   
			  FROM
			     liquidacio, recibo
			  WHERE 
			     liquidacio.Rcb_Cod = recibo.Rcb_Cod AND
			     liquidacio.Cop_Cod = $Par_Sql[0]";
		return $sql;
	    	break;	
	

    /**
	*  Consulta el tipo de pago SRI
	*/
	case 1047:
	$sql="SELECT Tpc_Cod,Tpc_Sri,Tpc_Des FROM tipopagocom WHERE Tpc_Est='A'";
	return $sql;
    break;

        case 1048: 
	/*Consulta del esquema del xml factura electronica */
	$sql= "SELECT Esq_Cod,Esq_Rec,Esq_Des,Esq_Xml,Esq_Ord FROM esquema WHERE esquema.Tan_Cod=$Par_Sql[0] AND esquema.Esq_Rec=$Par_Sql[1] AND esquema.Esq_Est='A' order by Esq_Ord Asc";
	//echo "1048: ".$sql."<br>";
	return $sql;
	break;
		
	case 1049: 
  	/*Consulta informacion de la empresa */
	$sql= "SELECT 
					  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,if(empresas.Emp_Cnt='S','SI','NO')as Emp_Cnt,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
					  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv
					FROM
					  empresas
				      INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
					  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
				   WHERE
				      sucursal.Suc_Cod=$Par_Sql[0]";
	//echo "1049: ".$sql."<br>";
	return $sql;
	break;
			
	case 1050: 
  	/*Consulta informacion de la empresa */
	$sql= "SELECT 
					  persona.Prs_Ced,persona.Prs_Ape,persona.Prs_Nom,
					  persona.Prs_Dir,persona.Prs_Tel,persona.Prs_Cor,
					  identifica.Ide_Prv,autorizaci.Pun_Sri,tipo_compr.Tic_Sri,
					  proveedore.Prv_Cod,proveedore.Prv_Con,compras.Cop_Cod,retencion.Ret_Num,
					  compras.Cop_Num,retencion.Ret_Fec,date_format(retencion.Ret_Fec, '%d/%m/%Y') AS fecha
					FROM
					  persona
					  INNER JOIN identifica ON (persona.Ide_Cod = identifica.Ide_Cod)
					  INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod)
					  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
					  INNER JOIN retencion ON (compras.Cop_Cod = retencion.Cop_Cod)
					  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
					  INNER JOIN autorizaci ON (retencion.Aut_Cod = autorizaci.Aut_Cod)
					WHERE
					  compras.Cop_Cod=$Par_Sql[0] AND Ret_Est='A'";
	//echo "1050: ".$sql."<br>";
	return $sql;
	break;
			
	case 1051: 
  	/*Consulta informacion total de venta y total del descuento de la venta */
	$sql= "SELECT 
			ventas.Vet_Cod,
			sum(ventas_det.Vet_Imp)as total,
			((sum(ventas_det.Vet_Imp)*ventas.Vet_Des)/100)as Dscto
		FROM
			ventas
			INNER JOIN ventas_det ON (ventas.Vet_Cod = ventas_det.Vet_Cod)
			WHERE
			ventas.Vet_Cod='$Par_Sql[0]' GROUP by ventas_det.Vet_Cod";
	//echo "1051: ".$sql."<br>";
	return $sql;
	break;

        case 1052:
	/* Consultamos los datos de la autorizacion */
	$sql= "SELECT 
		autorizaci.Aut_Sri,
		autorizaci.Pun_Sri,
		sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin
	FROM
		puntos_imp
		INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
		INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
	WHERE autorizaci.Aut_Cod = $Par_Sql[0]";
	//echo $sql;
        return $sql;
	break;

        case 1053:
	/* Consultamos los datos del periodo contable */
	$sql= "SELECT 
		perio_cont.Pec_Cod,date_format(perio_cont.Pec_Fei,'%m/%Y')as PerCon
	       FROM
		plan_cuenta
		INNER JOIN perio_cont ON (plan_cuenta.Pla_Cod = perio_cont.Pla_Cod)
		WHERE
		plan_cuenta.Emp_Cod='$Par_Sql[0]'";
        //echo $sql;
	return $sql;
	break;

        /**
	* Tipo de documento
	*/
	case 1054:
	$sql="SELECT tipo_compr.Tic_Cod, tipo_compr.Tic_Des, tipo_compr.Tic_Est FROM tipo_compr WHERE tipo_compr.Tic_Est='A' AND Tic_Sri='$Par_Sql[0]'";
	//echo $sql;
	return $sql;
	break;	

        /**
	 * Consulta si existe la retencion ya registrada en el mismo punto de venta 
	 * Quite la restricci�n a que la retenci�n sea solo duplicada por la persona que registro la compra retencion.Vnd_Cod=$Par_Sql[0] AND 
	 */
	case 1055:			
	 $sql="SELECT 
			retencion.Vnd_Cod,
			retencion.Ret_Cod,
			autorizaci.Aut_Cod
		  FROM
			 autorizaci
			 INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
		  WHERE
		 retencion.Ret_Num='$Par_Sql[1]' AND autorizaci.Aut_Sri='$Par_Sql[2]' AND retencion.Ret_Est='A'";
	//echo $sql;
	return $sql;
	break; 

    case 1056:			
	 $sql="SELECT 
			  retencion.Ret_Cod,
			  if(det_retenc.Ret_Imp = 'R','1','2')as ImpCod,
			  if(det_retenc.Ret_Imp = 'I', if(renta_iva.Ren_Por = '30', '1', if(renta_iva.Ren_Por = '70', '2', '3')), renta_iva.Ren_Sri) AS codigo,			
			  ((det_retenc.Ret_Bas*renta_iva.Ren_Por)/100) as ValRet,
			  renta_iva.Ren_Por,
			  det_retenc.Ret_Bas,
			  sustento.Tri_Sri,
			  tipo_compr.Tic_Sri,
			  compras.Cop_Num,
			  date_format(compras.Cop_Fec,'%d/%m/%Y') as Cop_Fec
			FROM
			  renta_iva
			  INNER JOIN det_retenc ON (renta_iva.Ren_Cod = det_retenc.Ren_Cod)
			  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
			  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
			  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
			  INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod)
			WHERE
			  retencion.Cop_Cod = '$Par_Sql[0]' AND 
			  retencion.Ret_Est = 'A'";
	//echo $sql;
	return $sql;

        /* Consultamos todos Sustentos*/
	case 1057:			
	 $sql="SELECT DISTINCT  
		  sustento.Tri_Sri,
		  sustento.Tri_Des,
		  sustento.Tri_Est,
		  compras.Tri_Cod
		FROM
		  sustento
		  INNER JOIN compras ON (sustento.Tri_Cod = compras.Tri_Cod)
		WHERE
		  compras.Cop_Est='A' order by compras.Cop_Fec Desc";
	//echo $sql;
	return $sql;
	break;

        /* Consultamos la Autorizacion segun Codigo de Retencion*/
	case 1058:			
	 $sql="SELECT  
	            autorizaci.Aut_Sri,autorizaci.Aut_Cod,autorizaci.Aut_Fci,
                    autorizaci.Aut_Cad,autorizaci.Aut_Ini,autorizaci.Aut_Fin,autorizaci.Aut_Est
	       FROM
		    autorizaci
		    INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
	       WHERE
		    retencion.Ret_Cod = '$Par_Sql[0]'";
	//echo $sql;
	return $sql;
	break;

        /**
	* Consulta si existe un numero de retencion segun Aut_Sri, sin importar la restriccion de Puntos de imprecion	
	*/
	case 1059:	
	 $sql="SELECT 
			retencion.Vnd_Cod,
			retencion.Ret_Cod,
			autorizaci.Aut_Cod
		  FROM
			 autorizaci
			 INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
		  WHERE
		 retencion.Ret_Num='$Par_Sql[1]' AND autorizaci.Aut_Sri='$Par_Sql[2]' AND retencion.Ret_Est='A'";
	//echo $sql;
	return $sql;
	break;

        case 1060: 
	/*Consulta informacion de la empresa */
	$sql= "SELECT 
			  empresas.Emp_Ruc,empresas.Emp_Nom,empresas.Emp_Reg,empresas.Emp_Cor,confi_fact.Cof_Fac,confi_fact.Cof_Gce,sucursal.Ciu_Cod,
			  sucursal.Suc_Sri,sucursal.Suc_Des,sucursal.Suc_Dir,sucursal.Suc_Te1,sucursal.Suc_Dir,confi_fact.Cof_Fte,confi_fact.Cof_Clv 
			FROM
			  empresas
			  INNER JOIN sucursal ON (empresas.Emp_Cod = sucursal.Emp_Cod)
			  INNER JOIN confi_fact ON (empresas.Emp_Cod = confi_fact.Emp_Cod)
		   WHERE
			  sucursal.Suc_Cod=$Par_Sql[0]";
	//echo "1060: ".$sql."<br>";
	return $sql;
	break;
	
	/**
	*  Concatenar el numero de factura 
	*/ 
        case 1061:
        $Num_factura= "SELECT 
		  autorizaci.Aut_Sri,
		  autorizaci.Pun_Sri,
		  sucursal.Suc_Sri, Aut_Fci, Aut_Cad, Aut_Ini, Aut_Fin
		FROM
		  puntos_imp
		  INNER JOIN autorizaci ON (puntos_imp.Pun_Cod = autorizaci.Pun_Cod)
		  INNER JOIN sucursal ON (puntos_imp.Suc_Cod = sucursal.Suc_Cod)
		WHERE autorizaci.Aut_Cod = $Par_Sql[0]";
        //echo $Num_factura;
        return $Num_factura;
        break;

        /**
	* Insertado de usuario
	*/
	case 1062:
		$sql="INSERT INTO usuarios (Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Cad,Usu_Tip)
			  VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',md5('$Par_Sql[3]'),'$Par_Sql[4]','C')";
	//echo $sql;
        return $sql;
	break;
	
	/*Consultando si una venta tiene retencion */
	case 1063:
	$sql="INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]')";
	//echo $sql;
	return $sql;
	break;
	
	case 1064:
	/* Consulta la base de datos de la empresa*/
	$sql = "SELECT 
			  data.Dat_Cod
			FROM
			  data
			WHERE data.Emp_Cod = '$Par_Sql[0]'";
	return $sql;
	break;
	
	case 1065:
	/* Consulta usuario con la cedula */
	$sql = "SELECT 
		    Usu_Ced, Suc_Cod
		FROM
		    usuarios
		WHERE Suc_Cod = '$Par_Sql[0]' AND Usu_Ced='$Par_Sql[1]' AND Usu_Est='A'";
        //echo $sql;
	return $sql;
	break;
	
	case 1066:
	/* Consulta la si existe usuario en la master */
	$sql = "SELECT 
			  Suc_Cod, Acc_Usr
			FROM
			  access
			WHERE Suc_Cod = '$Par_Sql[0]' AND Dat_Cod = '$Par_Sql[1]' AND Acc_Usr = '$Par_Sql[2]'";
	return $sql;
	break;
        
        /** 
	* Consulta general de facturas de un proveedor
	*/
	case 1067:	
	$sql="SELECT  
		  tipo_compr.Tic_Des,persona.Prs_Ape,persona.Prs_Nom,compras.Cop_Est,compras.Cop_Cod,compras.Cop_Fec,compras.Cop_Num
		FROM
		  persona
		  INNER JOIN proveedore ON (persona.Prs_Cod = proveedore.Prs_Cod)
		  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
		  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
		  INNER JOIN tipo_compr ON (compras.Tri_Cod = tipo_compr.Tic_Cod)
		WHERE
		  persona.Prs_Ced='$Par_Sql[0]' AND
		  compras.Tic_Cod='$Par_Sql[1]' AND
		  compras.Cop_Est='$Par_Sql[2]' AND 
		  proveedore.Emp_Cod = '$Par_Sql[3]' AND
		GROUP BY compras.Cop_Cod
		ORDER BY compras.Cop_Fec, compras.Cop_Cod ASC ";
	//echo $sql;
	return $sql;
	break;

    /**
	* Consulto los tipos de adquisiciones de todas las compras de un proveedor 
	*/
	case 1068:	
	$sql="SELECT 
			  adquisicio.Adq_Cod, adquisicio.Adq_Des, adquisicio.Adq_Cor
		  FROM
			  compras
			  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
			  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
			  INNER JOIN adquisicio ON (det_compra.Adq_Cod = adquisicio.Adq_Cod)
			  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
		  WHERE
			  $Par_Sql[0] AND 
			  persona.Prs_Ced = '$Par_Sql[1]' AND			   
			  compras.Cop_Est = '$Par_Sql[2]' AND
			  proveedore.Emp_Cod = '$Par_Sql[3]' 
		  GROUP BY adquisicio.Adq_Cod 
		  ORDER BY adquisicio.Adq_Des ASC";		  
	//echo $sql;
	return $sql;
	break;
	
	/**
	* Consultar facturas que tengan un sustento tributario 
	*/
	case 1069:	
	$sql="SELECT 
		  tipo_compr.Tic_Des, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced, compras.Cop_Est,
		  compras.Cop_Cod, compras.Cop_Fec, compras.Cop_Num, compras.Cop_Aut
		FROM
		  compras
		  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
		  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
		  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
		  INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod)
		  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
		WHERE
		  persona.Prs_Ced = '$Par_Sql[0]' AND
		  $Par_Sql[1] AND
  		  compras.Cop_Est = '$Par_Sql[2]' AND		  
		  compras.Tri_Cod='$Par_Sql[3]' AND
		  proveedore.Emp_Cod = '$Par_Sql[4]'
		GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Cod ASC"; //compras.Cop_Fec, 
	//echo $sql;
	return $sql;
	break;
	
	/**
	* Consultar proveedor por cedula 
	*/
	case 1070:	
	$sql="SELECT 
		  persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Ced
		FROM
		  compras		 
		  INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)		 
		  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
		WHERE
		  persona.Prs_Ced = '$Par_Sql[0]' AND proveedore.Emp_Cod = '$Par_Sql[1]'";
	//echo $sql;
	return $sql;
	break;

        /**
	* Consultar informacion de la compra
	*/
	case 1071:	
	$sql="SELECT 
		  compras.Cop_Cod,
          compras.Cop_Num, 
          compras.Cop_Aut, 
		  compras.Cop_Fec,
		  compras.Cop_Sys,
		  compras.Tic_Cod,
		  compras.Tri_Cod,
		  compras.Prv_Cod,
		  compras.Tpc_Cod,
		  persona.Prs_Ape,
		  persona.Prs_Nom,
		  persona.Prs_Ced
		FROM
		  proveedore
		  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
		  INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
		  
		WHERE
		  compras.Cop_Cod='$Par_Sql[0]'";
	//echo $sql;
	return $sql;
	break;
        
        case 1072:
	/**
	* Inserta datos en el kardex
	*/
	$sql = "INSERT  INTO kardex_ie (Vet_Cod,Aju_Cod,Vnd_Cod,Cop_Cod,Pro_Cod,Kar_Fec,Kar_Hor,Kar_Can,Kar_Sal,Kar_Pre,Kar_Prs,Kar_Ime,Kar_Ims,Kar_Des,Iva_Cod,Gia_Cod,Kar_Int)VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],$Par_Sql[3],$Par_Sql[4],'$Par_Sql[5]','$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8],$Par_Sql[9],$Par_Sql[10],$Par_Sql[11],$Par_Sql[12],$Par_Sql[13],$Par_Sql[14],$Par_Sql[15],$Par_Sql[16])";
	//echo '<br>'.$sql;
	return $sql;
	break;

        /**
	* Consultar informacion de la compra
	*/
	case 1073:	
	$sql="SELECT 
		  compras.Cop_Cod,
		  persona.Prs_Ape,
		  persona.Prs_Nom,
		  date_format(compras.Cop_Sys,'%d/%m/%Y %H:%i')as fecha
		FROM
		  persona
		  INNER JOIN vendedor ON (persona.Prs_Cod = vendedor.Prs_Cod)
		  INNER JOIN compras ON (vendedor.Vnd_Cod = compras.Vnd_Cod)
		WHERE
		  compras.Cop_Cod = '$Par_Sql[0]'";
	//echo $sql;
	return $sql;
	break;

        /**
        * Consultar el codigo del perfil Clientes segun la empresa
        */
        case 1074:	
        $sql="SELECT Per_Cod,Per_Des FROM perfiles WHERE Per_Des = 'Clientes' AND Emp_Cod = '$Par_Sql[0]' AND Per_Est='A'";		   
        return $sql;
        break;
	
	/**
        * Asignamos el perfil al cliente
        */
        case 1075:	
        $sql="INSERT INTO usuarperfi (Usu_Cod,Per_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";		   
        return $sql;
        break;

        /**
        * Consultar informacion tipo de comprobante segun codigo interno
        */
        case 1076:	
        $sql="SELECT Tic_Cod, Tic_Sri, Tic_Des FROM tipo_compr WHERE Tic_Cod = '$Par_Sql[0]'";		   
        return $sql;
        break;

          case 1077:
            /**
            * Selecciona la cuenta contable	del codigo de retenciion-iva del sri
            */
            $sql="SELECT renta_iva.Ren_Cod, renta_iva.Ren_Sri, renta_iva.Ren_Por, reniva_pla.Pld_Cod FROM renta_iva, reniva_pla 
                INNER JOIN det_plan ON det_plan.Pld_Cod=reniva_pla.Pld_Cod
                INNER JOIN plan_cuenta ON det_plan.Pla_Cod=plan_cuenta.Pla_Cod
                WHERE renta_iva.Ren_Cod='$Par_Sql[0]' AND plan_cuenta.Pla_Cod='$Par_Sql[1]' AND Emp_Cod='$Par_Sql[2]'  AND reniva_pla.Ren_Cod=renta_iva.Ren_Cod AND reniva_pla.Ren_Tip='C'";
            //echo "<br>".$sql;
            return $sql;
            break;
        case 1078:
                    $sql="SELECT MAX(Che_Num) as Che_Num FROM cheques WHERE Ban_Cod=$Par_Sql[0];";
             //echo $sql."<br>";
                    return $sql;
        case 1079:
                $sql="SELECT COUNT(Che_Cod) AS conteo FROM cheques WHERE Ban_Cod=$Par_Sql[0] AND Che_Num='$Par_Sql[1]' ";
               // echo $sql."<br>";
                return $sql;
        case 1080:
                $sql="SELECT   
                        sum(
                              ( 
                          (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
                              +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
                              )	*(1+iva.Iva_Por/100)	/* IVA */
                        )
                        AS total
                      FROM
                        compras
                        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
                        LEFT JOIN ice ON (ice.Ice_int=det_compra.Ice_Int)
                        INNER JOIN tipo_compr ON (tipo_compr.Tic_Cod = compras.Tic_Cod)
                      WHERE
                         compras.Prv_Cod = '$Par_Sql[0]' AND Tic_Sri='$Par_Sql[1]' 
                         AND Cop_Fec BETWEEN '$Par_Sql[2] 00:00:00' AND '$Par_Sql[3] 23:59:59'
                      GROUP BY
                        compras.Prv_Cod";
                //echo $sql."<br>";
                return $sql;
           case 1081:
                $sql="UPDATE cheques,asientos SET Che_Est='$Par_Sql[1]'  WHERE asientos.Asi_Cod=cheques.Asi_Cod AND Com_Cod='$Par_Sql[0]'";     
                //echo $sql."<br>";
                return $sql;    
           case 1082:
                $sql="SELECT MAX(Cop_Sec)+1 AS Com_Num FROM compras WHERE  Pec_Cod ='$Par_Sql[0]' AND MONTH(Cop_Fec)='$Par_Sql[1]'";     
                //echo $sql."<br>";
                return $sql;  
           case 1083:
                $sql="INSERT INTO compras (Tic_Cod, Prv_Cod, Ciu_Cod, Cop_Num, Cop_Aut, Cop_Fec, Cop_Reg, Cop_Obs, Cop_Cad, Cop_Imf, Tri_Cod, Cop_Des, Pec_Cod,Tpc_Cod,Cop_Ntd,Cop_Nns,Cop_Nna,Vnd_Cod,Cop_Sec) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], UPPER('$Par_Sql[3]'), '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', UPPER('$Par_Sql[8]'), '$Par_Sql[9]','$Par_Sql[10]','$Par_Sql[11]', $Par_Sql[12], '$Par_Sql[13]','$Par_Sql[14]', '$Par_Sql[15]', '$Par_Sql[16]','$Par_Sql[17]','$Par_Sql[18]')";     
                //echo $sql."<br>";
                return $sql; 
           /*Busqueda de comprobantes de compra*/
     case 1084:
                $sql="SELECT 
        compras.Cop_Cod,
        compras.Cop_Fec,
        compras.Cop_Sec,
        tipo_compr.Tic_Des,
        compras.Cop_Num,
          compras.Cop_Aut,
        persona.Prs_Ape,
        persona.Prs_Ced,
        persona.Prs_Nom,
        proveedore.Prv_Com,
        SUM(IF(Iva_Por = 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0')) AS Sub0,
		SUM(IF(Iva_Por != 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0')) AS Sub12,
		SUM( (Cop_Pru * Cop_Can)*(compras.Cop_Des/100)) AS Descu,
		SUM(IF(Iva_Por != 0, (Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100), '0'))*Iva_Por/100 AS IvaTot,
        sum( ( 
	  (det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100))) /* IMPORTE */
		  +(det_compra.Cop_Imp-(((det_compra.Cop_Imp*compras.Cop_Des)/100)+((det_compra.Cop_Imp*det_compra.Cop_Dec)/100)))*(IF(ice.Ice_Por IS NOT NULL,1+ice.Ice_Por/100,0)) /* ICE */
		  )	*(1+iva.Iva_Por/100)	/* IVA */
	) AS total
       FROM
        compras
        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
        LEFT JOIN ice ON (ice.Ice_int=det_compra.Ice_Int)
        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
        INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
		INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
		INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod) 
       WHERE
        proveedore.Emp_Cod='$Par_Sql[0]' $Par_Sql[1] $Par_Sql[2] AND Cop_Est='$Par_Sql[3]' AND Suc_Cod=$_SESSION[Ses_Suc_Cod] 
       GROUP BY compras.Cop_Cod  order by compras.Cop_Sec,compras.Cop_Cod Asc";     
                //echo $sql."<br>";
                return $sql;

      case 1085:	
				$sql="SELECT Cop_Pro, Cop_Int FROM det_compra WHERE Cop_Cod = '$Par_Sql[0]'";		   
				return $sql;
				break;
      case 1086:	
				$sql="SELECT iva_pagado.Pld_Cod,CONCAT(Pld_Des,' (',Pld_Cdc,')') AS Pld_Des FROM iva_pagado 
INNER JOIN det_plan ON det_plan.Pld_Cod=iva_pagado.Pld_Cod
WHERE Pla_Cod='$Par_Sql[0]'";
          //echo $sql;
				return $sql;
      case 1087:	
				$sql="SELECT asientos.Pld_Cod from asientos 
INNER JOIN iva_pagado ON iva_pagado.Pld_Cod=asientos.Pld_Cod
WHERE Com_Cod='$Par_Sql[0]'";
          //echo $sql;
				return $sql;
				break;

        /*Consulta de la retencion con el total renta e Iva*/
				case 1088:	
				$sql="SELECT 
						  retencion.Ret_Cod,retencion.Ret_Num,retencion.Ret_Fec,
						  autorizaci.Aut_Cod,if(Aut_Tem='N',autorizaci.Aut_Sri,Ret_Sri)as Aut_Sri,retencion.Ret_Xml,
						  retencion.Ret_Sri,retencion.Ret_Aut,
						  Round(sum(if(Ret_Imp = 'I',(Ret_Bas * Ren_Por) / 100, 0)), 2) AS TotIva,
						  Round(sum(if(Ret_Imp = 'R',(Ret_Bas * Ren_Por) / 100, 0)), 2) AS TotRen
						FROM
						  autorizaci
						  INNER JOIN retencion ON (autorizaci.Aut_Cod = retencion.Aut_Cod)
						  INNER JOIN det_retenc ON (retencion.Ret_Cod = det_retenc.Ret_Cod)
						  INNER JOIN renta_iva ON (det_retenc.Ren_Cod = renta_iva.Ren_Cod)
						WHERE
						  retencion.Cop_Cod = '$Par_Sql[0]' AND Ret_Est='A' 
						group by Ret_Cod";
          		//echo $sql;
				return $sql;
				break;

                                case 1089:
   $sql="SELECT Cop_Fec,Cop_Sec FROM compras WHERE Cop_Cod='$Par_Sql[0]'";
   return $sql;
    
    case 1090: 
   $sql="UPDATE compras SET Cop_Sec='$Par_Sql[1]' WHERE Cop_Cod='$Par_Sql[0]'";
          //echo $sql;
    return $sql;

    case 1091:
            $sql = "UPDATE compras SET Prv_Cod='$Par_Sql[1]' WHERE Cop_Cod='$Par_Sql[0]'";
            //echo $sql;
            return $sql;

    case 1092:
	//  CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)*ROUND((((Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100))+((Cop_Pru * Cop_Can)-(Cop_Pru * Cop_Can)*(compras.Cop_Des/100))*(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))),2)*Iva_Por/100, 0)) AS decimal(20,2))  AS IvaTot,
		$importe='CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
		$importe_con_desc="CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
		$ice="CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
		$iva="( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";
                $sql="SELECT '<div style=\"text-align:right\">Totales:</div>' AS proveedor, SUM(Sub0)AS Sub0, SUM(Sub12)AS Sub12, SUM(Descu)AS Descu, SUM(IvaTot)AS IvaTot, SUM(IceTot)AS IceTot, SUM(Cop_Irb)AS Cop_Irb, SUM(total)AS total  FROM (
       SELECT              
            CAST( SUM(IF(Iva_Por = 0, IF(Tic_Sri=4,-1,1)* $importe_con_desc , '0'))  AS decimal(20,2))AS Sub0,
            CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)* $importe_con_desc , '0'))  AS decimal(20,2))AS Sub12,
            CAST( SUM( IF(Tic_Sri=4,-1,1)* $importe * (compras.Cop_Des/100))  AS decimal(20,2)) AS Descu,
            CAST( SUM(IF(Cop_Ice != 0, IF(Tic_Sri=4,-1,1)* $ice, 0))  AS decimal(20,2)) AS IceTot,
            CAST( SUM(IF(Iva_Por != 0, IF(Tic_Sri=4,-1,1)* $iva, 0)) AS decimal(20,2))  AS IvaTot,
            CAST( IF(Tic_Sri=4,-1,1)* IF(Cop_Irb IS NULL,0,Cop_Irb)   AS decimal(20,2)) AS Cop_Irb, 
            CAST( SUM( IF(Tic_Sri=4,-1,1)* (
                    $importe_con_desc /* IMPORTE */
                    + $ice /* ICE */
                    + $iva /* IVA */ 
			)	  
            )  AS decimal(20,2)) + IF(Cop_Irb IS NULL,0,Cop_Irb) AS total
       FROM
        compras
        INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)        
        INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
        INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
        INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
        INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
		INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
		INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod AND Suc_Cod=$_SESSION[Ses_Suc_Cod])
       WHERE
        proveedore.Emp_Cod='$Par_Sql[0]' $Par_Sql[1] $Par_Sql[2] ".(!empty($Par_Sql[3])?" AND Cop_Est='$Par_Sql[3]' ":'')." GROUP BY compras.Cop_Cod   

        )AS compra_totales";  
      
                //echo $sql."<br>";
                return $sql;  
    case 1093:
		$importe='CAST( ((Cop_Pru * Cop_Can)-((Cop_Pru * Cop_Can) * Cop_Dec/100)) AS decimal(20,2) )';
		$importe_con_desc="CAST( ($importe - ( $importe * compras.Cop_Des/100 )) AS decimal(20,2) )";
		$ice="CAST( $importe_con_desc *(IF(det_compra.Cop_Ice IS NOT NULL,det_compra.Cop_Ice/100,0))  AS decimal(20,2) )";
		$iva="( CAST( $importe_con_desc + $ice  AS decimal(20,2) )*Iva_Por/100 )";
         if(!isset($Par_Sql[4])||$Par_Sql[4]==""){$campos="COUNT(compras.Cop_Cod) as total";}
         else{$campos="compras.Cop_Cod,
                    compras.Cop_Fec,
                    compras.Cop_Sec,
                    tipo_compr.Tic_Des,
                    compras.Cop_Num, 
                    compras.Cop_Aut, compras.Cop_Irb,
                    sustento.Tri_Sri,
                    CONCAT(Prs_Ape,' ',Prs_Nom) AS proveedor,
                    persona.Prs_Ced,
	            
                    SUM(IF(Iva_Por = 0, $importe_con_desc, '0')) AS Sub0,
                    SUM(IF(Iva_Por != 0, $importe_con_desc, '0')) AS Sub12,
                    SUM( $importe * (compras.Cop_Des/100) ) AS Descu,
					SUM(IF(Cop_Ice != 0, $ice , 0)) AS IceTot,
                    SUM(IF(Iva_Por != 0, $iva , 0)) AS IvaTot,                    
                    sum(  
                      $importe_con_desc /* IMPORTE */
                      + $ice /* ICE */
                      + $iva /* IVA */  				  		
                    )+ IF(Cop_Irb IS NULL,0,Cop_Irb) AS total";}
          $sql="SELECT $campos FROM compras 
                INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
                INNER JOIN sustento ON sustento.Tri_Cod=compras.Tri_Cod                
                INNER JOIN proveedore ON (compras.Prv_Cod = proveedore.Prv_Cod)
                INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
                INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
                INNER JOIN iva ON (det_compra.Iva_Cod = iva.Iva_Cod)
				INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
		INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod AND Suc_Cod=$_SESSION[Ses_Suc_Cod])
               WHERE
                proveedore.Emp_Cod='$Par_Sql[0]' $Par_Sql[1] $Par_Sql[2] ".(!empty($Par_Sql[3])?" AND Cop_Est='$Par_Sql[3]' ":'')."
               GROUP BY compras.Cop_Cod  order by compras.Cop_Sec,compras.Cop_Cod Asc ".(isset($Par_Sql[4])?$Par_Sql[4]:'');     
                //echo $sql."<br>";
                return $sql;


		case 4850:
			$sql="SELECT SUM(cop_can) as Cop_Can FROM det_compra WHERE cop_cod='$Par_Sql[0]'"; // compras.Cop_Fec
			//echo $sql;
			return $sql;
			break;

       case 1095:
               $sql="SELECT Cpp_Cod FROM ccpp_pagar
                    INNER JOIN comprobantes ON comprobantes.Com_Cod=ccpp_pagar.Com_Cod
                    INNER JOIN compras ON compras.Cop_Cod=ccpp_pagar.Cop_Cod
                    WHERE Cop_Est='A' AND Com_Est='A' AND Cop_Num='$Par_Sql[0]' AND compras.Prv_Cod='$Par_Sql[1]'";
                //echo $sql.'<br>';
               return $sql;   
            case 1096:
               $sql="INSERT INTO det_ccpp_p(Cpp_Cod,Pag_Cod,Com_Cod,Pag_Fec,Pag_Val,Pag_Est,Pag_Obs)
                    VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],'$Par_Sql[3]','$Par_Sql[4]','A','$Par_Sql[5]')";
               //echo $sql.'<br>';
                return $sql;
		case 1098:
			/**
			* Consulta de los bancos del plan de cuentas 
			*/
			$sql = "SELECT banco.Ban_Cod, det_plan.Pld_Cod, Ban_Cue, Ban_Obs, Pld_Des FROM banco, det_plan, pago_plan, plan_cuenta
			 WHERE banco.Pld_Cod = det_plan.Pld_Cod AND Ban_Est = 'A' AND banco.Ban_Cod = pago_plan.Ban_Cod AND det_plan.Pla_Cod = plan_cuenta.Pla_Cod AND pago_plan.Pag_Cod = $Par_Sql[0] AND plan_cuenta.Emp_Cod = $Par_Sql[1] ORDER BY Pld_Cdc, Pld_Des";
			//echo $sql;
			return $sql;  
                		
		/**
		* Consultar facturas que tengan un sustento tributario 
		*/
		case 1099:
		$sql="SELECT tipo_compr.Tic_Des,persona.Prs_Ape, persona.Prs_Ced, compras.Cop_Est, compras.Cop_Cod, 
			compras.Cop_Fec, persona.Prs_Nom, compras.Cop_Num, compras.Cop_Aut,
			compras.Tri_Cod, sustento.Tri_Des, det_compra.Cop_Imp
			FROM
			  persona
			  INNER JOIN proveedore ON (proveedore.Prs_Cod = persona.Prs_Cod)
			  INNER JOIN compras ON (proveedore.Prv_Cod = compras.Prv_Cod)
			  INNER JOIN vendedor ON (compras.Vnd_Cod = vendedor.Vnd_Cod)
			  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod)
			  INNER JOIN det_compra ON (compras.Cop_Cod = det_compra.Cop_Cod)
			  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
			  INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod)
			WHERE compras.Tri_Cod='$Par_Sql[3]'			
			AND proveedore.Prv_Cod=compras.Prv_Cod $Par_Sql[0] AND compras.Cop_Est='$Par_Sql[2]'
			AND $Par_Sql[1] AND proveedore.Emp_Cod = $Par_Sql[4] AND Suc_Cod=$_SESSION[Ses_Suc_Cod]			
			GROUP BY compras.Cop_Cod ORDER BY compras.Cop_Cod ASC"; //compras.Cop_Fec,
		//echo "<br>".$sql;
		return $sql;
		break;
		
        /**
		* Consultarlos ivas 
		*/
		case 1100:
			$sql="SELECT * FROM iva WHERE Iva_Por>0 AND ('$Par_Sql[0]' BETWEEN Iva_Ini AND Iva_Fin OR (DATE('$Par_Sql[0]')>=Iva_Ini AND Iva_Fin IS NULL) ) ORDER BY Iva_Por DESC"; //compras.Cop_Fec,
			//echo "<br>".$sql;
			return $sql;
			break;
		
		/* anular KARDEx */
		case 1101:
			$sql="UPDATE kardex_ie SET Kar_Est=UPPER('$Par_Sql[1]') WHERE Cop_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
		/* Buscar cta retenciones asumidas */
        case 1102:
			$sql="SELECT plan_param.Pld_Cod,Pld_Des,Pld_Est FROM plan_param 
                                INNER JOIN det_plan ON plan_param.Pld_Cod=det_plan.Pld_Cod 
                                INNER JOIN plan_cuenta ON plan_cuenta.Pla_Cod=det_plan.Pla_Cod 
                                INNER JOIN tipo_param ON plan_param.Tpa_Cod=tipo_param.Tpa_Cod 
                                WHERE Tpa_Abr='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]' AND Pld_Est='A'";
			//echo $sql;
			return $sql;
			break;   
        /* Buscar retenciones asumidas */           
        case 1103:
			$sql="SELECT Ret_Cod,Ret_Asu FROM retencion WHERE Ret_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break; 
        /* ACTUALIZAR retenciones asumidas */                     
        case 1104:
			$sql="UPDATE retencion SET Ret_Asu='$Par_Sql[1]' WHERE Ret_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;	
		// iva para notas de credito
		case 1105:
                $sql="SELECT DISTINCT * FROM iva WHERE Iva_Por!=0 AND Iva_Ini > '2001-07-01' GROUP BY Iva_Por ORDER BY Iva_Por DESC,Iva_Ini DESC ";
                //echo $sql;
                return $sql;
            break;       
        /* Verifico si la cuenta es de Caha chica */                     
        case 1106:
			$sql="SELECT plan_param.Pld_Cod,tipo_param.Tpa_Abr 
			     FROM plan_param
	  			 INNER JOIN tipo_param ON (plan_param.Tpa_Cod = tipo_param.Tpa_Cod) 
				 WHERE Pld_Cod='$Par_Sql[0]' AND Tpa_Abr='$Par_Sql[1]'";
			//echo $sql;
			return $sql;
			break;	
		/* Insertamos el registro de la compra a la tabla det_reposicion caja chica de tipo P=pendiente*/                     
        case 1107:
			$sql="INSERT INTO det_reposicion (Cop_Cod)VALUES('$Par_Sql[0]')";
			//echo $sql;
			return $sql;
			break;	
		/*consultamos si la compra esta repuesta en caja chica*/	
		case 1108:
		$sql="SELECT det_reposicion.Rep_Cod,Cop_Cod 
		FROM cab_reposicio
	    INNER JOIN det_reposicion ON (cab_reposicio.Rep_Cod = det_reposicion.Rep_Cod) 
		WHERE Dre_Tip='R' AND Rep_Est='A' AND Cop_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;
		
		/*consultamos los datos de la retencion de Cop_Cod*/	
		case 1109:
		$sql="SELECT Ret_Cod,Ret_Aut FROM retencion WHERE Ret_Est='A' AND Cop_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;	

		/*consultamos los datos de la retencion de Cop_Cod*/	
		case 1110:
		$sql = "INSERT INTO 
			det_compra (Cop_Cod, Cop_Can, Iva_Cod, Cop_Pro, Cop_Pru, Cop_Imp, Cop_Dec, Adq_Cod, Ice_Int, Cop_Int, Pld_Cod,Pro_Cod,Iva_Cos) 
			VALUES($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'),$Par_Sql[4],$Par_Sql[5],'$Par_Sql[6]',$Par_Sql[7],$Par_Sql[8], '$Par_Sql[9]',$Par_Sql[10],$Par_Sql[11],'$Par_Sql[12]')";  //  fue remplazado $Par_Sql[10]
		//echo $sql;
		return $sql;
		break;	
		
		/*SElect para obtener el campo Com_Cod de la tabla compr_auto*/
		case 1111:
				$sql="SELECT Com_Cod FROM compr_auto WHERE Cop_Cod='$Par_Sql[0]'";
				//echo $sql;
				return $sql;
				break;
		/*Update de la tabla comprobantes en el campo Prv_Cod*/
		case 1112:
		$sql="UPDATE comprobantes SET Prv_Cod='$Par_Sql[1]' WHERE Com_Cod='$Par_Sql[0]'";
		//echo $sql;
		return $sql;
		break;
		
		/*Suma y agrupa por codigo de retencion SRI los valos de la retencion*/
		case 1113:			
	    $sql="SELECT 
			  retencion.Ret_Cod,
			  if(det_retenc.Ret_Imp = 'R','1','2')as ImpCod,
			  if(det_retenc.Ret_Imp = 'I', if(renta_iva.Ren_Por = '10','9',if(renta_iva.Ren_Por = '20','10',if(renta_iva.Ren_Por = '30','1',if(renta_iva.Ren_Por = '50','11' ,if(renta_iva.Ren_Por = '70','2','3'))))), renta_iva.Ren_Sri) AS codigo,			
			  sum(((det_retenc.Ret_Bas*renta_iva.Ren_Por)/100)) as ValRet,
			  renta_iva.Ren_Por,
			  sum(det_retenc.Ret_Bas) as Ret_Bas,
			  sustento.Tri_Sri,
			  tipo_compr.Tic_Sri,
			  compras.Cop_Num,
			  date_format(compras.Cop_Fec,'%d/%m/%Y') as Cop_Fec
			FROM
			  renta_iva
			  INNER JOIN det_retenc ON (renta_iva.Ren_Cod = det_retenc.Ren_Cod)
			  INNER JOIN retencion ON (det_retenc.Ret_Cod = retencion.Ret_Cod)
			  INNER JOIN compras ON (retencion.Cop_Cod = compras.Cop_Cod)
			  INNER JOIN tipo_compr ON (compras.Tic_Cod = tipo_compr.Tic_Cod)
			  INNER JOIN sustento ON (compras.Tri_Cod = sustento.Tri_Cod)
			WHERE retencion.Cop_Cod = '$Par_Sql[0]' AND retencion.Ret_Est = 'A' GROUP BY Ren_Sri";
	    //echo $sql;
	    return $sql;
			
     }
}
?>