<?php
	/*ACTIVOS FIJOS*/
	function sentencias_con($id,$Par_Sql)
	{
		switch($id)
		{		
			/**
			 * Consulta el Custodio
			 */
			case 1: 
			$concus = "SELECT  CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre FROM custodio, persona, personal, distributi, asignacion
						WHERE  custodio.Cus_Cod = $Par_Sql[0] 
						AND asignacion.Cus_Cod = custodio.Cus_Cod 
						AND custodio.Dis_Cod = distributi.Dis_Cod 
						AND distributi.Per_Cod = personal.Per_Cod 
						AND personal.Prs_Cod = persona.Prs_Cod";
			// echo $concus;
			return $concus;
			break;
			
			case 134:
			/**
			 * Consultar sucursal en base al código 
			 */
			$cons_codsuc = "SELECT empresas.Emp_Cod, empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Cod, ciudad.Ciu_Des, sucursal.Suc_Sri, sucursal.Suc_Dir, sucursal.Suc_Des, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web,empresas.Emp_Log FROM empresas,ciudad,sucursal WHERE sucursal.Ciu_Cod = ciudad.Ciu_Cod AND sucursal.Emp_Cod = empresas.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0]";
			//echo $cons_codsuc;
			return $cons_codsuc;
			break;
		
			/**
			 * Consulta los campos de tipos de activos existentes
			 */
			case 407: 
			$concam = "SELECT campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est
		FROM campos_act WHERE Cam_Lar = '$Par_Sql[0]'";
			//echo $concam;
			return $concam;
			break;		
			
			/**
			 * Ingresa los campos de los tipos de activos
			 */
			case 408: 
			$inscam = "INSERT INTO campos_act (Cam_Lar, Cam_Cor, Cam_Tip, Cam_Obs) VALUES ( UPPER('$Par_Sql[0]'), UPPER('$Par_Sql[1]'), UPPER('$Par_Sql[2]'), UPPER('$Par_Sql[3]'))";
			//echo $inscam;
			return $inscam;
			break;	
		
			/**
			 * Consulta todos los tipos de Activos
			 */
			case 413: 
			  $sql = "SELECT tipo_activo.Tia_Cod, tipo_activo.Tia_Des, tipo_activo.Tia_Est, tipo_activo.Tia_Cdc FROM tipo_activo WHERE tipo_activo.Tia_Est = 'A' AND tipo_activo.Tia_Tip = 'D' AND tipo_activo.Emp_Cod = $Par_Sql[0] ORDER BY Tia_Des";
			//echo $sql;
			return $sql;
			break;
			
		    /**
		    * Consulta todos los Campos
		    */
		    case 414: 
		    $sql = "SELECT campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Est FROM campos_act ORDER BY Cam_Lar";
		    return $sql;
		    break;
			
		   /**
		    * Consulta los Campos x Tipo de Activo
		    */
		   case 415: 
		   $sql = "SELECT campos_plan.Cam_Cod, campos_plan.Cam_Req FROM campos_plan WHERE campos_plan.Tia_Cod = $Par_Sql[0] AND campos_plan.Cam_Cod = $Par_Sql[1]";
		   return $sql;
		   break;	
			
		    /**
			* Consulta Descripcion Tipo de Activo
			*/
		  	case 416: 
		  	$sql = "SELECT tipo_activo.Tia_Des, tipo_activo.Tia_Cod, tipo_activo.Tia_Cdc FROM tipo_activo WHERE tipo_activo.Tia_Cod = $Par_Sql[0]";
		 	return $sql;
		 	break;
			
			/**
			 * Elimina los campos de campos_plan
			 */
			case 417: 
			$delcam = "DELETE FROM campos_plan WHERE Tia_Cod = $Par_Sql[0]";
			return $delcam;
			break;
			/**
			 * Inserta los campos de campos_plan
			 */
			case 418: 
			$sql = "INSERT INTO campos_plan (Cam_Cod, Tia_Cod, Cam_Ord, Cam_Req) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], '$Par_Sql[3]')";
			
			return $sql;
	break;
	
			/** 
			 * Consulta los Campos x cada Tipo de Activo
			 */
			case 419: 
			$concam = "SELECT 
					campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est, campos_plan.Cam_Req FROM campos_act, campos_plan
			WHERE campos_act.Cam_Cod = campos_plan.Cam_Cod AND campos_plan.Tia_Cod = $Par_Sql[0] ORDER BY Cam_Cor";
			//echo $concam;
			return $concam;
			break;	
			
			/**
			* Inserta los valores de cada campo
			*/
			case 420: 
			$sql = "INSERT INTO det_activo (Act_Cod, Cam_Cod, Act_Val) VALUES ($Par_Sql[0], $Par_Sql[1],trim( UPPER('$Par_Sql[2]')))";
			return $sql;
			break;	
			
		   /**
		   * Consulta los peritos
		   */
		   case 421: 
		   $sql = "SELECT perito.Pri_Cod, persona.Prs_Ape, persona.Prs_Nom, perito.Pri_Esp FROM perito, persona WHERE perito.Pri_Est = 'A' AND perito.Prs_Cod = persona.Prs_Cod AND perito.Emp_Cod = $Par_Sql[0] ORDER BY Prs_Ape, Prs_Nom";
		  // echo $sql;
		   return $sql;
		   break;
			
			/**
			 * Consulta las sucursales
			 */
			case 422: 
			$consuc = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
			* Consulta los estados
			*/
			case 423: 
			$sql = "SELECT Est_Cod, Est_Des FROM estado_act WHERE Est_Est = 'A'";
			return $sql;
			break;
			
		  /**
		   * Consulta los proveedores
		   */
		   case 424: 
		   $sql = "SELECT Prv_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre, proveedore.Prv_Com FROM persona, proveedore WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Est = 'A' AND proveedore.Emp_Cod = $Par_Sql[0] ORDER BY Prv_Com, Prs_Ape, Prs_Nom";
		   return $sql;
		   break;				
		  /**
		   * Consulta los custodios
		   */
		   case 425: 
		   $sql = "SELECT Cus_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre FROM custodio, persona, personal, contratos_lab WHERE personal.Prs_Cod = persona.Prs_Cod AND contratos_lab.Per_Cod = personal.Per_Cod AND  custodio.Con_Cod = contratos_lab.Con_Cod AND personal.Emp_Cod = $Par_Sql[0] AND  custodio.Cus_Est = 'A' ORDER BY Prs_Ape, Prs_Nom";
		   //echo $sql;
		   return $sql;
		   break;			
			/**
			 * Consulta del ultimo activo
			 */
			case 426: 
			$conult = "SELECT COUNT(activo.Act_Cod) AS Cod FROM activo WHERE activo.Tia_Cod = $Par_Sql[0]";
			//echo $conult;
			return $conult;
			break;
	
		  /**
		   * Inserta el activo
		   */
		   case 427: 
		   $sql = "INSERT INTO activo (Tia_Cod, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann, Act_Fec, Act_Gar,Act_Fot) VALUES ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], $Par_Sql[3], $Par_Sql[4],UPPER('$Par_Sql[5]'), UPPER('$Par_Sql[6]'), '$Par_Sql[7]',$Par_Sql[8],'$Par_Sql[9]','$Par_Sql[10]', $Par_Sql[11], $Par_Sql[12], $Par_Sql[13], '$Par_Sql[14]', $Par_Sql[15],'$Par_Sql[16]')";
		   //echo $sql;
		   return $sql;
		   break;
			
			
		   /**
			* Inserta la asiganacion
			*/
			case 428: 
			$sql = "INSERT INTO asignacion (Cus_Cod, Act_Cod, Asg_Fec, Asg_Hor, Sec_Cod, Asg_Ord) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', '$Par_Sql[3]', $Par_Sql[4], $Par_Sql[5])";
			//echo $sql;
			return $sql;
			break;
			
			/**
			 * Conaulta los Activos
			 */
			case 429: 
			$conact = "SELECT
						 Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Prv_Cod, Act_Des, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar,tipo_activo.Tia_Cod 
						FROM 
							activo, tipo_activo 
						WHERE 
							activo.Tia_Cod = tipo_activo.Tia_Cod 
							AND Act_Des LIKE UPPER('%$Par_Sql[0]%') $Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/** 
			 * Conaulta los campos del Activo
			 */
			case 430: 
			$concam = "SELECT Act_Cod, Cam_Cod, Act_Val FROM det_activo 
					   WHERE Cam_Cod = $Par_Sql[0] AND Act_Cod = $Par_Sql[1]";
			//echo $concam;
			return $concam;
			break;
			
			/**
			 * Conaulta el Activo por codigo del activo
			 */
			case 431: 
			$conact = "SELECT activo.Act_Cod, activo.Tia_Cod, Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Act_Est, Prv_Cod, Act_Des, Act_Fec, Act_Obs, 
	Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann, Act_Fec, asignacion.Sec_Cod, Act_Bar,activo.Act_Fot
	FROM activo, tipo_activo, asignacion WHERE activo.Tia_Cod = tipo_activo.Tia_Cod AND asignacion.Act_Cod = activo.Act_Cod AND asignacion.Asg_Est = 'A'  AND activo.Act_Cod = $Par_Sql[0]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Consulta el Custodio
			 */
			case 432: 
			$concus = "SELECT  CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre FROM custodio, persona, personal, contratos_lab, asignacion
						WHERE 
						 asignacion.Act_Cod = $Par_Sql[0] 
						AND asignacion.Cus_Cod = custodio.Cus_Cod 
						AND custodio.Con_Cod = contratos_lab.Con_Cod 
						AND contratos_lab.Per_Cod = personal.Per_Cod 
						AND personal.Prs_Cod = persona.Prs_Cod
						AND asignacion.Asg_Est ='A'";
						
			//echo $concus;
			return $concus;
			break;
	
			 /**
			  * Modifica al activo
			  */
			case 433: 
			$modact = "UPDATE activo SET Pri_Cod = $Par_Sql[0], Suc_Cod = $Par_Sql[1], Est_Cod = '$Par_Sql[2]', Prv_Cod = $Par_Sql[3], Act_Des = UPPER('$Par_Sql[4]'), Act_Obs = UPPER('$Par_Sql[5]'), Act_Cdc = '$Par_Sql[6]', Act_Can = $Par_Sql[7], Act_Bar = '$Par_Sql[8]', Act_Gen = '$Par_Sql[9]', Act_Val = $Par_Sql[11], Act_Res = $Par_Sql[12], Act_Ann = $Par_Sql[13], Act_Fec = '$Par_Sql[14]',Act_Gar='$Par_Sql[15]',Act_Fot='$Par_Sql[16]' WHERE Act_Cod = $Par_Sql[10]";
			//echo $modact;
			return $modact;
			break;
			
			/**
			 * Modifica cada campo de activo
			 */
			case 434: 
			$modcam = "UPDATE det_activo SET Act_Val = trim(UPPER('$Par_Sql[0]')) WHERE  Cam_Cod = $Par_Sql[1] AND Act_Cod = $Par_Sql[2]";
			//echo $modcam;
			return $modcam;
			break;	
	
			/**
			 * Consulta los Activos x Codigo
			 */
			case 435: 
			$conact = "SELECT Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des,IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar FROM activo, tipo_activo WHERE activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Cdc = Trim('$Par_Sql[0]') $Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Conaulta los Activos x Codigo de Barra
			 */
			case 436: 
			$conact = "SELECT Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar FROM activo, tipo_activo WHERE activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Bar =  Trim('$Par_Sql[0]') $Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
	
			/**
			*  Consulta de las secciones 
			*/
			case 437:
			$sql="SELECT seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des FROM seccion, departamen, areas_rrhh WHERE seccion.Sec_Est = 'A' AND areas_rrhh.Are_Cod = departamen.Are_Cod AND departamen.Dep_Cod = seccion.Dep_Cod AND areas_rrhh.Emp_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
			/**
			 * Consulta de los Departamentos 
			 */
			case 438:
			$direc_438="SELECT 
							departamen.Dep_Cod, departamen.Dep_Des
						FROM						  
						  	departamen						
						WHERE	
						   departamen.Dep_Est = 'A'";
			return $direc_438;
			break;
			
			/** 
			 * Consulta de las secciones en base al departamento 
			 */
			case 439:
			$direc_439="SELECT DISTINCT seccion.Sec_Cod, seccion.Sec_Des, departamen.Dep_Des FROM asignacion, seccion, departamen, activo 
			WHERE asignacion.Sec_Cod = seccion.Sec_Cod 
				AND seccion.Dep_Cod = departamen.Dep_Cod AND asignacion.Act_Cod = activo.Act_Cod AND departamen.Dep_Cod = $Par_Sql[0]";
			//echo $direc_439;
			return $direc_439;
			break;
			
			/** 
			 * Consulta los tipos de activos de cada departamento
			 */
			 case 440:
			 $direc_440="SELECT
			 
			 			DISTINCT 
						 	activo.Tia_Cod, tipo_activo.Tia_Des, tipo_activo.Tia_Cdc, tipo_activo.Tia_Rec  
						FROM 
							asignacion, activo, tipo_activo, departamen, custodio,contratos_lab,tiposcargo
							
						WHERE
							activo.Tia_Cod = tipo_activo.Tia_Cod
							AND activo.Act_Cod = asignacion.Act_Cod
							AND asignacion.Cus_Cod = custodio.Cus_Cod
							AND custodio.Con_Cod = contratos_lab.Con_Cod  
							AND contratos_lab.Tic_Cod = tiposcargo.Tic_Cod	
							AND tiposcargo.Dep_Cod = departamen.Dep_Cod
							AND departamen.Dep_Cod = $Par_Sql[0] 
							AND activo.Act_Est = 'A'";
			 //echo $direc_440;
			 return $direc_440;
			 break;
			 
			/**
			 * Consulta los activos x cada tipo de activo 
			 */
			case 441:
			$direc_441="SELECT 
						 activo.Act_Cod, activo.Tia_Cod, Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Fec, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann 
						FROM 
							asignacion, activo, tipo_activo, departamen, custodio,contratos_lab,tiposcargo
						WHERE 
						activo.Tia_Cod = tipo_activo.Tia_Cod
							AND activo.Act_Cod = asignacion.Act_Cod
							AND asignacion.Cus_Cod = custodio.Cus_Cod
							AND custodio.Con_Cod = contratos_lab.Con_Cod  
							AND contratos_lab.Tic_Cod = tiposcargo.Tic_Cod	
							AND tiposcargo.Dep_Cod = departamen.Dep_Cod AND
						    departamen.Dep_Cod = $Par_Sql[0] AND
						   activo.Tia_Cod = $Par_Sql[1] AND activo.Act_Est = 'A' ";
			//echo $direc_441;
			return $direc_441;
			break;
	
			/**
			 * Modifica del activo
			 */
			 case 442: 
			 $bajcam = "UPDATE activo SET Act_Est = UPPER('$Par_Sql[0]') WHERE  Act_Cod = $Par_Sql[1]";
			 //echo $bajcam;
			 return $bajcam;
			 break;

			/**
			* Consulta del ultimo activo Act_Cod
			*/
			case 443: 
			$sql = "SELECT 
						COUNT(activo.Act_Cod) AS Cod 
					FROM 
						activo, tipo_activ 
					WHERE 
						activo.Tia_Cod = tipo_activ.Tia_Cod AND
						 tipo_activ.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
			/** 
			 * Consulta todos los activos de un departamento 
			 */
			case 445: 
			$conult = "SELECT  
						SUM(ROUND(activo.Act_Val, 2)) AS Act_Val, SUM(ROUND(activo.Act_Res,2)) AS Act_Res, departamen.Dep_Des 
					 FROM 
						asignacion, activo, custodio, departamen,contratos_lab,tiposcargo
						WHERE 
							asignacion.Cus_Cod = custodio.Cus_Cod AND
							custodio.Con_Cod = contratos_lab.Con_Cod AND
							contratos_lab.Tic_Cod = tiposcargo.Tic_Cod AND
							departamen.Dep_Cod = tiposcargo.Dep_Cod AND							
							activo.Act_Cod = asignacion.Act_Cod AND 
							activo.Act_Est = 'A' AND 
							asignacion.Asg_Est = 'A' AND 	
							asignacion.Asg_Con = 'C'						
					GROUP BY departamen.Dep_Des";
			//echo $conult;
			return $conult;
			break;
	
			/**
			 *  Consulta una seccion 
			 */
			case 465:
			$consec="SELECT
						 seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des 
					 FROM 
					 	 seccion, departamen 
						WHERE 
						seccion.Sec_Est = 'A' AND
						departamen.Dep_Cod = seccion.Dep_Cod AND
						seccion.Sec_Cod = $Par_Sql[0]";
			//echo $consec;
			return $consec;
			break;
			
			/**
			 * Consultar los datos de una solicitud de reubicacion de un activo
			 */
			case 469:
			$conre="SELECT 
						asignacion.Cus_Cod,  asignacion.Act_Cod, asignacion.Asg_Fec, asignacion.Asg_Hor, 
						asignacion.Asg_Raz, asignacion.Sec_Cod, asignacion.Asg_Est, asignacion.Asg_Con  
					FROM 	
						asignacion 
					WHERE 
						asignacion.Act_Cod = $Par_Sql[0] AND
						 asignacion.Asg_Est = 'A' ";
			//echo $conre;
			return $conre;
			break;
			
			/**
			 * Actualiza los datos de la asignacion
			 */
			case 470:
			$acasg = "UPDATE asignacion SET Sec_Cod = $Par_Sql[0], Cus_Cod = $Par_Sql[1]  WHERE Act_Cod = $Par_Sql[2] AND asignacion.Asg_Est = 'A'";
			//echo $acasg;
			return $acasg;
			break;
			
			/** 
			 * Actualiza los datos de la asignacion
			 */
			case 471:
			$acasg = "UPDATE asignacion SET asignacion.Asg_Est = '$Par_Sql[0]' WHERE asignacion.Act_Cod = $Par_Sql[1] AND asignacion.Asg_Est = 'A'";
			//echo $acasg;
			return $acasg;
			break;

			/**
			 * Conaulta el Custodio x seccion
			 */
		   case 472: 
				   $concus = "SELECT distinct CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Nombre FROM custodio, persona, personal, distributi, seccion, asignacion 
					  WHERE  seccion.Sec_Cod = $Par_Sql[0] AND asignacion.Sec_Cod = seccion.Sec_Cod  AND custodio.Dis_Cod = distributi.Dis_Cod AND distributi.Per_Cod = 
					  personal.Per_Cod AND personal.Prs_Cod = persona.Prs_Cod AND custodio.Cus_Cod = asignacion.Cus_Cod";	  	  
		  // echo $concus;
		   return $concus;
		   break;

			/**
			 * Consultar los datos de los activos de acuerdo al plan de cuentas
			 */
			case 473:
			$conre="SELECT 
					  tipo_activo.Tia_Cod,
					  tipo_activo.Tia_Rec,
					  tipo_activo.Tia_Des,
					  tipo_activo.Tia_Est,
					  tipo_activo.Tia_Cdc,
					  tipo_activo.Tia_Tip
					FROM
					  tipo_activo
					WHERE 
					   tipo_activo.Tia_Rec = $Par_Sql[1] AND tipo_activo.Tia_Est = 'A'";// AND tipo_activo.Tia_Cod = $Par_Sql[0]";
			echo $conre;
			return $conre;
			break;
						
			/** 
			 * Conaulta los Activos x el tipo de activo
			 */
			case 474: 
			$conact = "SELECT
						 SUM(ROUND(activo.Act_Val,2)) AS Act_Val 
					  FROM 
					  	 activo, tipo_activo 
						 WHERE 
						 	activo.Tia_Cod = tipo_activo.Tia_Cod AND
						  	Act_Est = 'A' AND
							tipo_activo.Tia_Est = 'A' AND
							tipo_activo.Tia_Cod = $Par_Sql[0]"; 
			echo $conact;
			return $conact;
			break;
			/**
			 * Consulta los datos de reporte de Activos
			 */
		   case 635:
			$sql = "SELECT 
						Dep_Des, Act_Des, Act_Cdc AS Act_Sec, Act_Bar FROM tipo_activo, activo, seccion, departamen, asignacion 
					WHERE
						tipo_activo.Tia_Cod=activo.Tia_Cod AND
						activo.Act_Cod = asignacion.Act_Cod AND
						asignacion.Sec_Cod=seccion.Sec_Cod AND
						seccion.Dep_Cod = departamen.Dep_Cod AND
						departamen.Dep_Cod = $Par_Sql[0]";
			return $sql;
		   break;
		   
		    /**
			 * Consulta los datos de reporte de Activos de forma paginada
			 */
		   case 636:
			$sql = "SELECT 
						Dep_Des, Act_Des, Act_Cdc AS Act_Sec, Act_Bar 
					FROM 
						tipo_activo, activo, seccion, departamen, asignacion 
					WHERE 
						tipo_activo.Tia_Cod=activo.Tia_Cod AND 
						activo.Act_Cod = asignacion.Act_Cod AND 
						asignacion.Sec_Cod=seccion.Sec_Cod AND 
						seccion.Dep_Cod = departamen.Dep_Cod AND 
						departamen.Dep_Cod = $Par_Sql[0] LIMIT $Par_Sql[1],$Par_Sql[2];";
			//echo $sql;
			return $sql;
		   break;

			/**
			 * Consulta los datos de reporte de Activos
			 */
		   case 637:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec,
					  activo.Act_Cod,
					  activo.Act_Cdc,
					  activo.Act_Bar,
					  departamen.Dep_Cod,
					  seccion.Sec_Cod,
					  departamen.Dep_Des,
					  activo.Act_Des,
					  activo.Tia_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN seccion ON (seccion.Sec_Cod = asignacion.Sec_Cod)
					  INNER JOIN departamen ON (seccion.Dep_Cod = departamen.Dep_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  asignacion.Cus_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
		   break;
		   /**
		    * Consulta los datos de reporte de Activos de forma paginada
			*/
		   case 638:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec, activo.Act_Cod, activo.Act_Cdc, activo.Act_Bar,
					  activo.Act_Des, activo.Tia_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  asignacion.Cus_Cod = $Par_Sql[0] AND asignacion.Asg_Est ='A' ORDER BY  activo.Act_Des LIMIT $Par_Sql[1],$Par_Sql[2]";
			//echo $sql;
		   return $sql;
		   break;
		   
		   /**
		    * Consulta los custodios x su Apellido
			*/
			 
			case 639: 
			$concus = "SELECT 
							Cus_Cod, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Nombre, persona.Prs_Ced, custodio.Cus_Est 
						FROM 
							custodio, persona, personal, contratos_lab 
						WHERE 
							custodio.Con_Cod = contratos_lab.Con_Cod AND 
							personal.Prs_Cod = persona.Prs_Cod AND 
							contratos_lab.Per_Cod = personal.Per_Cod AND 
							custodio.Cus_Est = 'A' AND 
							persona.Prs_Ape LIKE ('%$Par_Sql[0]%')";
			//echo $concus;
			return $concus;
			break;
			
			/** 
			 * Consulta los custodios x su Cedula
			 */
			case 640: 
			$concus = "SELECT 
							Cus_Cod, CONCAT(persona.Prs_Ape,'',persona.Prs_Nom) AS Nombre, persona.Prs_Ced, custodio.Cus_Est 
						FROM 
							custodio, persona, personal, contratos_lab 
						WHERE 
							custodio.Con_Cod = contratos_lab.Con_Cod AND 
							personal.Prs_Cod = persona.Prs_Cod AND 
							contratos_lab.Per_Cod = personal.Per_Cod AND 
							custodio.Cus_Est = 'A' AND 
							persona.Prs_Ced = $Par_Sql[0]";
			//echo $concus;
			return $concus;
			break;	
			
		  /**
		   * Actualiza el codigo de barras
		   */
		   case 641: 
			$sql = "UPDATE activo SET Act_Bar = '$Par_Sql[0]' WHERE  Act_Cod = $Par_Sql[1]";
			//echo $sql;
		   return $sql;
		   break;
			
		  /**
		   * Consulta los tipos de activos
		   */
		   case 642: 
			$sql = "SELECT 
					  tipo_activo.Tia_Cod,
					  tipo_activo.Tia_Des,
					  tipo_activo.Tia_Cdc,
					  tipo_activo1.Tia_Des AS Subgrupo,
					  tipo_activo.Tia_Est,tipo_activo2.Tia_Des as Grupo
					FROM
					  tipo_activo
					  INNER JOIN tipo_activo tipo_activo1 ON (tipo_activo1.Tia_Cod = tipo_activo.Tia_Rec)
					  INNER JOIN tipo_activo tipo_activo2 ON (tipo_activo2.Tia_Cod = tipo_activo1.Tia_Rec)
					WHERE
					  tipo_activo.Tia_Tip = 'D' AND  tipo_activo.Tia_Des LIKE '%$Par_Sql[0]%' AND
					  tipo_activo.Emp_Cod = $Par_Sql[1]
					ORDER BY tipo_activo.Tia_Cdc";					  
			//echo $sql ;
		   return $sql;
		  
		   break;
		   
		   /**
		    *Consulta de los campos  por tipo de Busqueda
		    */
		   case 643: 
			$sql = "SELECT 
					 campos_act.Cam_Cod,campos_act.Cam_Cor
					FROM
					 campos_act 
					WHERE
					 campos_act.Cam_Est= 'A' AND campos_act.Cam_Bus='S'";
					 //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		    *Consulta de los activos  por campos
		    */
		   case 644: 
		   $sql = "SELECT activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Prv_Cod, Act_Des, Act_Obs,
 		 tipo_activo.Tia_Cdc, Act_Cdc, Act_Can,det_activo.Act_val,campos_act.Cam_Cod, Act_Bar
 FROM activo INNER JOIN `det_activo` ON activo.`Act_Cod`= det_activo.`Act_Cod`
 		 			  INNER JOIN `tipo_activo` ON `activo`.`Tia_Cod`= `tipo_activo`.`Tia_Cod`
                      INNER JOIN `campos_act` ON `campos_act`.`Cam_Cod`= `det_activo`.`Cam_Cod`
                       WHERE `campos_act`.`Cam_Cod`= $Par_Sql[0]  AND det_activo.Act_val LIKE '%$Par_Sql[1]%' $Par_Sql[2]";					   		   //echo $sql;
		   return $sql;
		   break;	
		   
		   
		   /**
			 * Consulta las sucursales por codigo de sucursal.
			 */
			case 645: 
			$consuc = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Suc_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
		   * Consulta los proveedores por codigo del proveedor
		   */
		   case 646: 
		   $sql = "SELECT Prv_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre, proveedore.Prv_Com FROM persona, proveedore WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Est = 'A' AND proveedore.Emp_Cod = $Par_Sql[0] AND  proveedore.Prv_Cod= $Par_Sql[1] ORDER BY Prv_Com, Prs_Ape, Prs_Nom";
		   return $sql;
		   break;	
		 
		   /**
		    * Consulta los peritos por codigo del perito
		    */
		   case 647: 
		   $sql = "SELECT perito.Pri_Cod, persona.Prs_Ape, persona.Prs_Nom, perito.Pri_Esp FROM perito, persona WHERE perito.Pri_Est = 'A' AND perito.Prs_Cod = persona.Prs_Cod AND perito.Emp_Cod = $Par_Sql[0] AND perito.Pri_Cod = $Par_Sql[1] ORDER BY Prs_Ape, Prs_Nom";
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
			* Consulta los estados por codigo
			*/
			case 648: 
			$sql = "SELECT Est_Cod, Est_Des FROM estado_act WHERE Est_Est = 'A' AND  Est_Cod=  $Par_Sql[0] ";
			return $sql;
			break;
			
			/**
			* Consulta los activos por codigo
			*/
			case 649: 
			$conact = "SELECT activo.Act_Cod,  Act_Bar	FROM activo  WHERE activo.Act_Cod = $Par_Sql[0]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			* Consulta los  activos todos
			*/
			case 650: 
			$conact = "SELECT activo.Act_Cod,  Act_Bar	FROM activo";
			//echo $conact;
			return $conact;
			break;
			
			/**
			* Actualiza los codigos de barra para codigos de activos menosres a 10
			*/
			case 651:
			$modificar_compr = " UPDATE activo  SET Act_Bar=CONCAT('00000000000',Act_Cod) where  Act_Cod < 10";		
 			//echo $conact;
			return $conact;
			break;
			/**
			* Actualiza los codigos de barra para codigos de activos  mayores a 9 y menores= a 99
			*/
			case 652:
			$modificar_compr = "UPDATE activo  SET Act_Bar=concat('0000000000',Act_Cod) where  Act_Cod >9 and Act_Cod<=99";		
 			//echo $conact;
			return $conact;
			break;
			
			/**
			* Actualiza los codigos de barra para codigos de activos mayores a 99 menosres= a 999
			*/
			case 653:
			$modificar_compr = "UPDATE activo  SET Act_Bar=concat('000000000',Act_Cod) where  Act_Cod >99 and Act_Cod<=999";		
 			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Consultar los datos de los activos de acuerdo al plan de cuentas
			 */
			case 654:
			$conre="SELECT 
					  tipo_activo.Tia_Cod,
					  tipo_activo.Tia_Rec,
					  tipo_activo.Tia_Des,
					  tipo_activo.Tia_Est,
					  tipo_activo.Tia_Cdc,
					  tipo_activo.Tia_Tip
					FROM
					  tipo_activo
					WHERE 
					   tipo_activo.Tia_Rec = $Par_Sql[1] AND tipo_activo.Tia_Est = 'A'";//tipo_activo.Tia_Cod = $Par_Sql[0] AND
				//echo $conre;
				return $conre;
			break;
			
			
			/** 
			 * Conaulta los Activos x el tipo de activo
			 */
			case 655: 
			$conact = "SELECT SUM(ROUND(activo.Act_Val,2)) AS Act_Val FROM activo, tipo_activo WHERE activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Est = 'A' AND tipo_activo.Tia_Est = 'A' AND tipo_activo.Tia_Cod = $Par_Sql[0]"; 
			//echo $conact;
			return $conact;
			break;
						
			/** 
			 * Conaulta los Activos x el tipo de activo
			 */
			case 656: 
			$conact = "SELECT Tia_Des FROM tipo_activo WHERE tipo_activo.Tia_Cod = $Par_Sql[0]"; 
			//echo $conact;
			return $conact;
			break;
					
			/** 
			 * Consulta los Campos x cada Tipo de Activo y Busqueda 
			 */
			case 657: 
			$concam = "SELECT campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est, campos_plan.Cam_Req FROM campos_act, campos_plan
			WHERE campos_act.Cam_Cod = campos_plan.Cam_Cod AND campos_plan.Tia_Cod = $Par_Sql[0] and campos_act.Cam_Bus = 'S' ORDER BY Cam_Cor";
			//echo $concam;
			return $concam;
			break;
			
			/** 
			 * Consulta  los campos existentes para un tipo de activo 
			 */
			case 658: 
			$concam = "SELECT Count(campos_plan.Cam_Cod) as Tot_Cam FROM campos_plan WHERE campos_plan.Tia_Cod = $Par_Sql[0] ";
			//echo $concam;
			return $concam;
			break;			
			
			/** 
			 * Consulta el maximo codigo de activo fijo registrado
			 */
			case 659: 
			$concam = "UPDATE activo SET Act_Fot = '$Par_Sql[0]' WHERE Act_Cod = $Par_Sql[1]";
			//echo $concam;
			return $concam;
			break;
			
			/** 
			 * Consulta los Campos x cada Tipo de activo
			 */
			case 660: 
			$concam = "SELECT 
							campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est
						FROM 
							campos_act 
						WHERE campos_act.`Cam_Bus`= 'S' AND campos_act.Cam_Est = 'A' ORDER BY Cam_Cod";
			//echo $concam;
			return $concam;
			break;	
			
			/** 
			 * Consulta valor del campo  para un activo
			 */
			case 661: 
			$concam = "SELECT 
							det_activo.Act_Val 
						FROM 
							det_activo 
						INNER JOIN campos_act ON det_activo.Cam_Cod = campos_act.Cam_Cod 
						INNER JOIN activo ON det_activo.Act_Cod= activo.Act_Cod 
						WHERE 
							campos_act.Cam_Est = 'A' AND det_activo.Act_Cod= $Par_Sql[0] AND campos_act.Cam_Cod = $Par_Sql[1]  ORDER BY campos_act.Cam_Cod";
			//echo $concam;
			return $concam;
			break;	
			
			/**
			 * Consulta los activos  por tipo_ctivo
			 */
			case 662:
				$direc_441="SELECT 
						asignacion.Sec_Cod, activo.Act_Cod, activo.Tia_Cod, Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Fec, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann 
						FROM 
							activo, tipo_activo, seccion, departamen, asignacion 
						WHERE 
							asignacion.Sec_Cod = seccion.Sec_Cod AND seccion.Dep_Cod = departamen.Dep_Cod 
							AND activo.Tia_Cod = tipo_activo.Tia_Cod AND asignacion.Act_Cod = activo.Act_Cod AND asignacion.Sec_Cod = $Par_Sql[0] AND activo.Tia_Cod = $Par_Sql[1] AND activo.Act_Est = 'A' ";
			//echo $direc_441;
			return $direc_441;
			break;		
			
			
			case 663:
			$sql= "SELECT 
						tipo_activo.Tia_Cod, tipo_activo.Tia_Des, tipo_activo.Tia_Cdc, tipo_activo1.Tia_Des AS Subgrupo,
					 	tipo_activo.Tia_Est,tipo_activo2.Tia_Des as Grupo FROM tipo_activo 
					 INNER JOIN tipo_activo tipo_activo1 ON (tipo_activo1.Tia_Cod = tipo_activo.Tia_Rec) 
					 INNER JOIN tipo_activo tipo_activo2 ON (tipo_activo2.Tia_Cod = tipo_activo1.Tia_Rec)
				   WHERE  
					tipo_activo.Tia_Tip = 'D' AND tipo_activo2.Tia_Des LIKE '%$Par_Sql[0]%' AND
				  	tipo_activo.Emp_Cod = $Par_Sql[1] ORDER BY tipo_activo.Tia_Cdc";
				  //echo $sql;
			return $sql;
			break;	 
			
			/**
			 * Consulta los Activos de manera exacta
			 */
			case 664: 
			$conact = "SELECT 
						Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod,  IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Prv_Cod, Act_Des, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar,tipo_activo.Tia_Cod 
						FROM 
							activo, tipo_activo 
						WHERE 
							activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Des = UPPER('$Par_Sql[0]') $Par_Sql[1]"; 
			//echo $conact;
			return $conact;
			break;
			 
		   /**
		   * Consulta los tipos de activos para la busqueda
		   */
		   case 665: 
			$sql = "SELECT DISTINCT 
					  activo.Tia_Cod,
					  tipo_activo.Tia_Des,
					  tipo_activo1.Tia_Des AS subgrupo
					FROM
					  tipo_activo
					  INNER JOIN activo ON (tipo_activo.Tia_Cod = activo.Tia_Cod)
					  INNER JOIN tipo_activo tipo_activo1 ON (tipo_activo.Tia_Rec = tipo_activo1.Tia_Cod)
					WHERE tipo_activo.Emp_Cod = $Par_Sql[0] 
					ORDER BY
					  tipo_activo.Tia_Des";					  
			//echo $sql ;
		   return $sql;
		   break;
		   
		   /**
			* Consulta de los Departamentos por Código
			*/
		   case 666:
		   $direc_666="SELECT 
			   departamen.Dep_Cod, departamen.Dep_Des
			  FROM        
				 departamen      
			  WHERE 
				 Dep_Cod=$Par_Sql[0]";
		   //echo $direc_666;
		   return $direc_666;
		   break;
		   
		   /**
		    * Consulta los custodios x su codigo de custodio
			*/			 
			case 1000: 
			$sql = "SELECT 
							Cus_Cod,custodio.Cus_Est,departamen.Dep_Des 
						FROM
							custodio 
							INNER JOIN  contratos_lab ON custodio.Con_Cod= contratos_lab.Con_Cod
							INNER JOIN tiposcargo ON contratos_lab.Tic_Cod = tiposcargo.Tic_Cod
							INNER JOIN departamen ON departamen.Dep_Cod=tiposcargo.Dep_Cod
						WHERE  
							custodio.Cus_Cod=$Par_Sql[0]";
			//echo $sql;
			return $sql;
			break;
			
			
		   	/**
			 * Consulta los datos de reporte de activos por tipo activo
			 */
		   case 1002:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec,
					  activo.Act_Cod,
					  activo.Act_Cdc,
					  activo.Act_Bar,
					  activo.Act_Des,
					  activo.Tia_Cod,
					  custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  activo.Act_Des like '%$Par_Sql[0]%' AND tipo_activo.Emp_Cod=$Par_Sql[1]
					  AND asignacion.Asg_Est ='A'";
			//echo $sql;
			return $sql;
		   break;
			
			 /**
		    * Consulta los datos de reporte de Activos de forma paginada  por tipo de activo
			*/
		   case 1003:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec, activo.Act_Cod, activo.Act_Cdc, activo.Act_Bar,
					  activo.Act_Des, activo.Tia_Cod,custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  activo.Act_Des like '%$Par_Sql[0]%'  AND asignacion.Asg_Est ='A' AND tipo_activo.Emp_Cod=$Par_Sql[3] ORDER BY  activo.Act_Des LIMIT $Par_Sql[1],$Par_Sql[2]";
			//echo $sql;
		   return $sql;
		   break;
		   
		   
		    /**
			 * Consulta los datos de reporte de activos por tipo activo
			 */
		   case 1004:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec,
					  activo.Act_Cod,
					  activo.Act_Cdc,
					  activo.Act_Bar,
					  activo.Act_Des,
					  activo.Tia_Cod,
					  custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  tipo_activo.Tia_Cod = $Par_Sql[0]  AND asignacion.Asg_Est ='A'";
			//echo $sql;
			return $sql;
		   break;
			
			/**
		     * Consulta los datos de reporte de Activos de forma paginada  por tipo de activo
			 */
		   case 1005:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec, activo.Act_Cod, activo.Act_Cdc, activo.Act_Bar,
					  activo.Act_Des, activo.Tia_Cod,custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  tipo_activo.Tia_Cod = $Par_Sql[0] AND asignacion.Asg_Est ='A' ORDER BY  tipo_activo.Tia_Cod LIMIT $Par_Sql[1],$Par_Sql[2]";
			//echo $sql;
		   return $sql;
		   break;
		   
		   
		  /**
		   * Consulta los tipos de activos para la busqueda
		   */
		   case 1006: 
			$sql = "SELECT DISTINCT 
					  activo.Tia_Cod,
					  tipo_activo.Tia_Des,
					  tipo_activo1.Tia_Des AS subgrupo
					FROM
					  tipo_activo
					  INNER JOIN activo ON (tipo_activo.Tia_Cod = activo.Tia_Cod)
					  INNER JOIN tipo_activo tipo_activo1 ON (tipo_activo.Tia_Rec = tipo_activo1.Tia_Cod)
					WHERE tipo_activo.Emp_Cod = $Par_Sql[0] 
					ORDER BY
					  tipo_activo.Tia_Des";					  
			//echo $sql ;
		   return $sql;
		   break;
		   		   
		   /**
		    *   Departamentos de los Custodios por Codigo de Custodio
			*/		   
		    case 1007:
			$sql = "select `departamen`.`Dep_Cod`, departamen.`Dep_Des` 
					from 
						`custodio` 
					INNER JOIN `contratos_lab` ON `custodio`.`Con_Cod`= `contratos_lab`.`Con_Cod`
					INNER JOIN `tiposcargo` ON  `tiposcargo`.`Tic_Cod`=`contratos_lab`.`Tic_Cod`
					INNER JOIN `departamen` ON `departamen`.`Dep_Cod`= `tiposcargo`.`Dep_Cod`
					WHERE `custodio`.`Cus_Cod` = $Par_Sql[0]  and `contratos_lab`.`Con_Est`='A'";
			//echo $sql;
		   return $sql;
		   break;	   
	

		  	
			
			
			
			/**
			 * Consulta los datos de reporte de activos por codigo de secuencial
			 */
		   case 1008:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec,
					  activo.Act_Cod,
					  activo.Act_Cdc,
					  activo.Act_Bar,
					  activo.Act_Des,
					  activo.Tia_Cod,
					  custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  activo.Act_Cdc = '$Par_Sql[0]' AND tipo_activo.Emp_Cod=$Par_Sql[1]
					  AND asignacion.Asg_Est ='A'";
			//echo $sql;
			return $sql;
		   break;
			
		   /**
		    * Consulta los datos de reporte de Activos de forma paginada  por  codigo de secuencial
			*/
		   case 1009:
			$sql = "SELECT 
					  Act_Cdc AS Act_Sec, activo.Act_Cod, activo.Act_Cdc, activo.Act_Bar,
					  activo.Act_Des, activo.Tia_Cod,custodio.Cus_Cod
					FROM
					  custodio
					  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
					  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
					  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
					WHERE
					  activo.Act_Cdc = '$Par_Sql[0]'  AND asignacion.Asg_Est ='A' AND tipo_activo.Emp_Cod=$Par_Sql[3] ORDER BY  activo.Act_Cdc LIMIT $Par_Sql[1],$Par_Sql[2]";
			//echo $sql;
		   return $sql;
		   break;
			
			
		   /**
		    * Consulta los datos de los Tipos de Activos  ordenandolos por empresa
			*/
		   case 1010:
			$sql = "SELECT 
						tipo_activo.Tia_Cdc,
						tipo_activo.Tia_Des,
						tipo_activo.Tia_Tip
					FROM
						tipo_activo
					WHERE 
						tipo_activo.Emp_Cod=$Par_Sql[0]     
						ORDER BY Tia_Cdc";
			echo $sql;				
			return $sql;
		   break;
			
			
			
			
			
			
			
			
			
			







		
		
		  /**
		   *  Consulta la provicia y pais de la ciudad de la sucursal
		   */
		   case 5000:
			$sql="SELECT
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
		   *  Consulta la información la ciudada en base a la sucursal
		   */
		   case 5001:
			$sql="SELECT 
					empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log 
				  FROM 
				  	empresas, sucursal, ciudad 
				  WHERE 
				  	sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			return $sql;
		   break;
   
			 /**
			 *  Consulta los datos del usuario
			 */
			case 5002:
		   		$sql="SELECT 
						Prs_Ape, Prs_Nom, Prs_Ced 
					 FROM 
					 	persona, usuarios 
					 WHERE 
					 	persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
		   		return $sql;
			 break;
							   
		}
	}
?>