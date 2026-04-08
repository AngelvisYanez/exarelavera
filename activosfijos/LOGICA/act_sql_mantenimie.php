<?php
    /**
     *  ACTIVOS FIJOS - SQL MANTENIMIENTO
     */
    function sentencias_con($id,$Par_Sql)
    {
		switch($id)
		{					
		  /**
		   * Consultar un tipo de mantenimiento
		   */
			case 1:
			$conman="SELECT Tma_Cod,Tma_Des,Tma_Est,Tma_Obs 
					 FROM 
						tipo_mante 
					 WHERE Tma_Est = 'A' AND Tma_Cod = $Par_Sql[0]" ;
			//echo $conman;
			return $conman;
			break;	
			
			/**
			 * Consulta un encargado de mantenimiento 
			 */
			case 2:
			
			$conenc="SELECT 
						persona.Prs_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre, Ema_Cod, Ema_Obs,
						 Ema_Esp, Ema_Est  
			 		  FROM 
					  	persona, enc_manten 
					  WHERE 
					  	enc_manten.Prs_Cod = persona.Prs_Cod AND 
						enc_manten.Ema_Est = 'A' AND Ema_Cod = $Par_Sql[0] " ;
			//echo $conenc;
			return $conenc;
			break;
			
			/**
			 * Consulta los Campos x cada Tipo de Activo
			 */
			case 419: 
			$concam = "SELECT campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est, campos_plan.Cam_Req 
					   FROM
					    campos_act, campos_plan
					   WHERE 
					   campos_act.Cam_Cod = campos_plan.Cam_Cod AND campos_plan.Tia_Cod = $Par_Sql[0] ORDER BY Cam_Cod";
			//echo $concam;
			return $concam;
			break;	
			
			/** 
			 * Consulta los peritos
			 */
			case 421: 
			$conper = " SELECT perito.Pri_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre 
						FROM perito, persona 
						WHERE perito.Pri_Est = 'A' AND perito.Prs_Cod = persona.Prs_Cod";
			//echo $conper;
			return $conper;
			break;
			
			/**
			 * Consulta las sucursales
			 */
			case 422: 
			$consuc = "SELECT Suc_Cod, Suc_Des 
					   FROM sucursal 
					   WHERE Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
			 * Consulta los estados
			 */
			case 423: 
			$consuc = "SELECT Est_Cod, Est_Des 
					   FROM estado_act 
					   WHERE Est_Est = 'A'";
			//echo $consuc;
			return $consuc;
			break;
			
			/** 
			 * Consulta los proveedores
			 */
			case 424: 
			$conprv = "SELECT 
						Prv_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre 
					   FROM 
					   	persona, proveedore 
					   WHERE 
					   	persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Est = 'A'";
			//echo $conprv;
			return $conprv;
			break;
			
			/**
			 * Consulta los custodios
			 */
			case 425: 
			$concus = "SELECT 
						Cus_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre 
					   FROM 
					   	custodio, persona, personal, distributi 
					   WHERE 
					   	custodio.Dis_Cod = distributi.Dis_Cod AND personal.Prs_Cod = persona.Prs_Cod AND distributi.Per_Cod = personal.Per_Cod AND custodio.Cus_Est = 'A'";
			//echo $concus;
			return $concus;
			break;
			
			/**
			 * Conaulta los Activos
			 */
			case 429: 
			$conact = "SELECT 
							Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est
					   FROM 
					   		activo, tipo_activo 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Des LIKE UPPER('%$Par_Sql[0]%')
							AND tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Conaulta los campos del Activo
			 */
			case 430: 
			$concam = "SELECT Act_Cod, Cam_Cod, Act_Val 
					   FROM det_activo 
					   WHERE Cam_Cod = $Par_Sql[0] AND Act_Cod = $Par_Sql[1]";
			//echo $concam;
			return $concam;
			break;
			
			/**
			 * Consulta el Activo  por codigo del activo
			 */
			case 431: 
			$conact = "SELECT Act_Cod, activo.Tia_Cod, Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann, Act_Est 
					   FROM activo, tipo_activo 
					   WHERE activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Cod = $Par_Sql[0]";
			//echo $conact;
			return $conact;
			break;
	
			/**
			 * Conaulta el Custodio por codigo del activo
			 */
			case 432: 
			$concus = "SELECT  CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre, persona.Prs_Ced, custodio.Cus_Cod, asignacion.Sec_Cod FROM custodio, persona, personal, contratos_lab, asignacion
						WHERE  
							asignacion.Act_Cod = $Par_Sql[0] 
						AND asignacion.Cus_Cod = custodio.Cus_Cod 
						AND custodio.Con_Cod = contratos_lab.Con_Cod 
						AND contratos_lab.Per_Cod = personal.Per_Cod 
						AND personal.Prs_Cod = persona.Prs_Cod AND asignacion.Asg_Est='A'";
			//echo $concus;
			return $concus;
			break;
			
			/**
			 * Consulta los Activos x Codigo Secuancial
			 */
			case 435: 
			$conact = "SELECT 
						Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
						FROM
						 activo, tipo_activo 
						WHERE 
						activo.Tia_Cod = tipo_activo.Tia_Cod AND
						Act_Cdc = '$Par_Sql[0]' AND 
						tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Conaulta los Activos x Codigo de Barra
			 */
			case 436: 
			$conact = "SELECT 
						Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar
					FROM
					 	activo, tipo_activo 
					WHERE 
						activo.Tia_Cod = tipo_activo.Tia_Cod AND
						Act_Bar =  Trim('$Par_Sql[0]') AND 
						tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 *  Consulta de las secciones 
			 */
			case 437:
			$direc_437="SELECT 
							seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des 
						FROM
							 seccion, departamen 
						WHERE 
							seccion.Sec_Est = 'A' AND
							departamen.Dep_Cod = seccion.Dep_Cod ";
			//echo $direc_437;
			return $direc_437;
			break;
			
			/**
			 * Consultar tipos de mantenimiento
			 */
			case 454:
			$conman="SELECT Tma_Cod,Tma_Des,Tma_Est,Tma_Obs FROM tipo_mante WHERE Tma_Est = 'A'" ;
			//echo $conman;
			return $conman;
			break;
			
			/**
			 * Consulta encargados de mantenimiento 
			 */ 
			case 455:
			$conenc="SELECT persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, Ema_Cod, Ema_Obs, Ema_Esp, Ema_Est  FROM persona, enc_manten WHERE enc_manten.Prs_Cod = persona.Prs_Cod AND enc_manten.Ema_Est = 'A'" ;
			//echo $conenc;
			return $conenc; 
			break;
			
			/**|
			 * Insertar los datos de Mantenimiento
			 */
			case 456:
			$insen="INSERT INTO mantenimie (Tma_Cod,Act_Cod,Ema_Cod,Man_Fec,Est_Cod) VALUE ($Par_Sql[0], $Par_Sql[1], $Par_Sql[2], '$Par_Sql[3]',$Par_Sql[4])";
			//echo $insen;
			return $insen;
			break;
	
			/**
			 * Consultar los datos de los Mantenimientos de un activo
			 */
			case 457:
			$conma="SELECT Man_Cod,tipo_mante.Tma_Cod, Tma_Des, Act_Cod,Ema_Cod,Man_Des,Man_Fec,Man_Fet,Man_Obs,Man_Est FROM mantenimie, tipo_mante WHERE tipo_mante.Tma_Cod = mantenimie.Tma_Cod AND mantenimie.Act_Cod = $Par_Sql[0]";
			//echo $conma;
			return $conma;
			break;
			
			/**
			 * Consultar los datos de un Mantenimiento
			 */
			case 458:
			$conman="SELECT Man_Cod, tipo_mante.Tma_Cod, Tma_Des, Act_Cod,Ema_Cod,Man_Des,Man_Fec,Man_Fet,Man_Obs,Man_Est,Man_Pro FROM mantenimie, tipo_mante WHERE mantenimie.Man_Cod = $Par_Sql[0]";
			//echo $conman;
			return $conman;
			break;
	
			/**
			 * Modifica los datos de Mantenimiento
			 */
			case 459:
			$modman="UPDATE mantenimie SET Tma_Cod = $Par_Sql[0], Ema_Cod = $Par_Sql[1], Man_Des = UPPER('$Par_Sql[2]'), Man_Fec = '$Par_Sql[3]',Man_Fet = '$Par_Sql[4]', Man_Obs = UPPER('$Par_Sql[5]') WHERE Man_Cod = $Par_Sql[6]";
			//echo $modman;
			return $modman;
			break;
			
			/**
			 * Elimina los datos de Mantenimiento
			 */
			case 460:
			$eliman="UPDATE mantenimie SET Man_Est = '$Par_Sql[0]' WHERE Man_Cod = $Par_Sql[1]";
			//echo $eliman;
			return $eliman;
			break;
			
			/**
			 * Consulta un custodio
			 */
			case 464: 
			$concus = "SELECT Cus_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Cus_Nom, persona.Prs_Ced FROM custodio, persona, personal, distributi 
						WHERE custodio.Dis_Cod = distributi.Dis_Cod AND personal.Prs_Cod = persona.Prs_Cod AND distributi.Per_Cod = personal.Per_Cod AND custodio.Cus_Est = 'A' AND custodio.Cus_Cod = $Par_Sql[0]";
			//echo $concus;
			return $concus;
			break;
			
			/**
			 *  Consulta una seccion 
			 */
			case 465:
			$consec="SELECT seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des FROM seccion, departamen WHERE seccion.Sec_Est = 'A' AND departamen.Dep_Cod = seccion.Dep_Cod AND seccion.Sec_Cod = $Par_Sql[0]";
			//echo $consec;
			return $consec;
			break;
			
			/**
			 * Consulta las sucursales por codigo de sucursal.
			 */
			case 466: 
			$consuc = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Suc_Est = 'A' AND Suc_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
		     * Consulta los proveedores por codigo del proveedor
		     */
		   case 467: 
		   $sql = "SELECT Prv_Cod, CONCAT(persona.Prs_Ape,\" \",persona.Prs_Nom) AS Nombre, proveedore.Prv_Com FROM persona, proveedore WHERE persona.Prs_Cod = proveedore.Prs_Cod AND proveedore.Prv_Est = 'A' AND proveedore.Emp_Cod = $Par_Sql[0] AND  proveedore.Prv_Cod= $Par_Sql[1] ORDER BY Prv_Com, Prs_Ape, Prs_Nom";
		   return $sql;
		   break;	
		   
		    /**
		     * Consulta los peritos por codigo del perito
		     */
		   case 468: 
		   $sql = "SELECT perito.Pri_Cod, persona.Prs_Ape, persona.Prs_Nom, perito.Pri_Esp FROM perito, persona WHERE perito.Pri_Est = 'A' AND perito.Prs_Cod = persona.Prs_Cod AND perito.Emp_Cod = $Par_Sql[0] AND perito.Pri_Cod = $Par_Sql[1] ORDER BY Prs_Ape, Prs_Nom";
		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		    * Consulta de las secciones por código 
			*/
			case 469:
			$direc_469="SELECT seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des FROM seccion, departamen WHERE seccion.Sec_Est = 'A' AND departamen.Dep_Cod = seccion.Dep_Cod AND seccion.Sec_Cod=$Par_Sql[0] ";
			//echo $direc_469;
			return $direc_469;
			break;		
			/**
		     *Consulta de los campos  por tipo de Busqueda
		     */
		   case 470: 
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
		     * Consulta de los activos  por campos
		     */
		   case 471: 
		   $sql = "SELECT 
		   			activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Act_Est, Prv_Cod, Act_Des, Act_Obs,
 		 tipo_activo.Tia_Cdc, Act_Cdc, Act_Can,det_activo.Act_val,campos_act.Cam_Cod, Act_Bar
		 			FROM 
						activo 
						INNER JOIN det_activo ON activo.Act_Cod= det_activo.Act_Cod
 		 				INNER JOIN tipo_activo ON activo.Tia_Cod= tipo_activo.Tia_Cod
                		INNER JOIN campos_act ON campos_act.Cam_Cod= det_activo.Cam_Cod
                	WHERE 
						campos_act.Cam_Cod= $Par_Sql[0]  AND det_activo.Act_val LIKE '%$Par_Sql[1]%'";					   		   //echo $sql;
		   return $sql;
		   break;
		   
		   /**
		 	* buscar personal contratado custodio por apellido
		 	*/
		case 472:
			$sql = "SELECT
					 `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Cod`,`custodio`.`Cus_Est`
					FROM 
						`personal`
						INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
						INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
						INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
						INNER JOIN `custodio` ON `contratos_lab`.`Con_Cod` = `custodio`.`Con_Cod`
					WHERE 
						`personal`.`Emp_Cod` = '$Par_Sql[0]' AND 
						`persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%' AND
						`contratos_lab`.`Con_Est` = 'A'";
			//echo $sql;
			return $sql;
		break;
		
		  /**
		   * Consulta los activos x custodio
		   */
		case 473: 
		   $concus = "SELECT 
		  asignacion.Cus_Cod,
		  activo.Act_Des,
		  activo.Act_Cod,
		  activo.Act_Cdc,
		  activo.Tia_Cod,
		  activo.Act_Can,
		  IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est,
		  persona.Prs_Ced,
		  CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,
		  tipo_activo.Tia_Cdc,
		  departamen.Dep_Des,
		  seccion.Sec_Des
		 
		FROM
		  custodio
		  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
		  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
		  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
		  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
		  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
		  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
		  INNER JOIN seccion ON (asignacion.Sec_Cod = seccion.Sec_Cod)
		  INNER JOIN departamen ON (seccion.Dep_Cod = departamen.Dep_Cod)
		WHERE
		  custodio.Cus_Cod = $Par_Sql[0]  ORDER BY departamen.Dep_Des, seccion.Sec_Des";
		   //echo $concus;
		   return $concus;
		   break;
		   
		   
		 /**
		  * Contar si ya ha sido asignado
		  */
		case 474:
			$sql = "SELECT `persona`.`Prs_Ced`, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre
			FROM `persona`
			INNER JOIN `enc_manten` ON `persona`.`Prs_Cod` = `enc_manten`.`Prs_Cod`
			WHERE  `persona`.`Prs_Cod` = $Par_Sql[0] AND contratos_lab`.`Con_Est` = 'A'";
			//$sql = "SELECT COUNT(`Cus_Cod`)AS 'count' FROM `custodio` WHERE `Con_Cod` = '$Par_Sql[0]'";
			return $sql;
		break;
		
		 /**
		  * Consulta de persona por cedula para registrar encargado de mantenimiento
		  */
		case 475:
			$sql = "SELECT 
						persona.Prs_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre
					FROM 
						persona
				 	WHERE  
						Prs_Ced = $Par_Sql[0]";
			//echo $sql;
			return $sql;
		break;
		
		 /**
		  * Consulta de persona por apellido para registrar encargado de mantenimiento.
		  */
		case 476:
			$sql = "SELECT
						persona.Prs_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre
					FROM 
						persona 
					WHERE  
						Prs_Ape like '%$Par_Sql[0]%'";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Insertar los datos del Encargado del Mantenimiento
		 */
		case 477:
		$insen="INSERT INTO enc_manten(Prs_Cod,Ema_Obs,Ema_Esp) VALUES ($Par_Sql[0],UPPER('$Par_Sql[1]'), UPPER('$Par_Sql[2]'))";
		//echo $insen;
		return $insen;
		break;
		
		 /**
		  * Consulta  si hay un registro con el codigo de la persona
		  */
		case 478:
		$insen="SELECT COUNT(Ema_Cod) as count FROM  enc_manten WHERE Prs_Cod=$Par_Sql[0]";
		//echo $insen;
		return $insen;
		break;
		
		/**
		 * Consulta los manteniminetos por ejecutarse para un activo especifico
		 */
		case 479:
			$sql="SELECT 
					mantenimie.Man_Cod,
					mantenimie.Act_Cod,
					CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Encargado,
					estado_act.Est_Des,
					mantenimie.Man_Fec,
					mantenimie.`Man_Fet`,
					mantenimie.`Man_Pro`,
					mantenimie.`Man_Des`,
					mantenimie.`Man_Obs`
					 
				FROM 
					mantenimie 
						INNER JOIN enc_manten ON enc_manten.Ema_Cod=mantenimie.Ema_Cod
						INNER JOIN persona ON enc_manten.Prs_Cod=persona.Prs_Cod
						INNER JOIN estado_act ON estado_act.Est_Cod=mantenimie.Est_Cod
				WHERE 
					mantenimie.Act_Cod =$Par_Sql[0] AND mantenimie.Man_Pro=0 
				ORDER BY mantenimie.Man_Fec";
				//echo $sql;
		return $sql;
		break;
		
		/**
		 * Obtener el estado del activo  segun el codigo del activo
		 */
		case 480:
			$sql="SELECT 
					estado_act.Est_Des,estado_act.Est_Cod
				  FROM 
				  	activo 
				  INNER JOIN estado_act ON activo.Est_Cod = estado_act.Est_Cod
				  WHERE activo.Act_Cod=$Par_Sql[0]";
			//echo $sql;
		return $sql;
		
		/**|
		 * Insertar los datos de Mantenimiento
		 */
		case 481:
		$insen="UPDATE 
					mantenimie 
				Set 
					Man_Des=UPPER('$Par_Sql[0]'),Man_Fet='$Par_Sql[1]',Man_Obs=UPPER('$Par_Sql[2]'),Man_Pro=1	
				WHERE 
					Man_Cod=$Par_Sql[3]";
		//echo $insen;
		return $insen;
		break;
		
		/**
		 * Conaulta los Activos
		 */
		case 482: 
		$conact = "SELECT 
					DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est 
				   FROM 
				   	activo, tipo_activo, mantenimie
				   WHERE 
				   	activo.Tia_Cod = tipo_activo.Tia_Cod AND
					activo.Act_Cod = mantenimie.Act_Cod AND
					tipo_activo.Emp_Cod = $Par_Sql[1] AND 
					Act_Des LIKE UPPER('%$Par_Sql[0]%')";
		//echo $conact;
		return $conact;
		break;
		
		case 483:
			/**
			 * Consultar sucursal en base al código 
			 */
			$cons_codsuc = "SELECT
							empresas.Emp_Cod, empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Cod, ciudad.Ciu_Des, sucursal.Suc_Sri, sucursal.Suc_Dir, sucursal.Suc_Des, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web ,Emp_Log
							FROM 
								empresas,ciudad,sucursal
							WHERE 
								sucursal.Ciu_Cod = ciudad.Ciu_Cod AND sucursal.Emp_Cod = empresas.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0]";
		//echo $cons_codsuc;
			return $cons_codsuc;
			break;
			
		/**
		 * Consulta los manteniminetos por ejecutarse para un activo especifico
		 */
		case 484:
			$sql="SELECT 
					mantenimie.Man_Cod,
					mantenimie.Act_Cod,
					CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Encargado,
					estado_act.Est_Des,
					mantenimie.Man_Fec,
					mantenimie.`Man_Fet`,
					mantenimie.`Man_Pro`,
					mantenimie.`Man_Des`,
					mantenimie.`Man_Obs`
					 
				FROM 
					mantenimie 
						INNER JOIN enc_manten ON enc_manten.Ema_Cod=mantenimie.Ema_Cod
						INNER JOIN persona ON enc_manten.Prs_Cod=persona.Prs_Cod
						INNER JOIN estado_act ON estado_act.Est_Cod=mantenimie.Est_Cod
				WHERE 
					mantenimie.Act_Cod =$Par_Sql[0] 
				ORDER BY mantenimie.Man_Fec";
				//echo $sql;
		return $sql;
		break;
		
		
			/**
			 * Consulta los Activos x Codigo secuencial de los mantenimientos de activos fijos
			 */
			case 485: 
			$conact = "SELECT 
						DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
					   FROM
					    activo, tipo_activo, mantenimie
					   WHERE 
					   	activo.Tia_Cod = tipo_activo.Tia_Cod AND 
						activo.Act_Cod = mantenimie.Act_Cod AND 
						tipo_activo.Emp_Cod = $Par_Sql[1] AND
						Act_Cdc = '$Par_Sql[0]'";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Conaulta los Activos x Codigo de Barra  de mantenimeintos de activos fijos
			 */
			case 486: 
			$conact = "SELECT 
						DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des, IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
						FROM 
							activo, tipo_activo,mantenimie 
						WHERE 
							activo.Tia_Cod = tipo_activo.Tia_Cod AND
							activo.Act_Cod = mantenimie.Act_Cod AND
						    Act_Bar =  Trim('$Par_Sql[0]')";
			//echo $conact;
			return $conact;
			break;
			
			/**
		     * Consulta de los activos  por campos para ek nantenimiento de activos
		     */
		   case 487: 
		   $sql = "SELECT 
					DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, activo.Act_Est, Prv_Cod, Act_Des, Act_Obs,
					 tipo_activo.Tia_Cdc, Act_Cdc, Act_Can,det_activo.Act_val,campos_act.Cam_Cod, Act_Bar
			 FROM 
				activo 
				INNER JOIN `det_activo` ON activo.`Act_Cod`= det_activo.`Act_Cod`
 		 		INNER JOIN `tipo_activo` ON `activo`.`Tia_Cod`= `tipo_activo`.`Tia_Cod`
                INNER JOIN `campos_act` ON `campos_act`.`Cam_Cod`= `det_activo`.`Cam_Cod`
				INNER JOIN `mantenimie` ON mantenimie.`Act_Cod`= activo.Act_Cod 
                WHERE 
				`campos_act`.`Cam_Cod`= $Par_Sql[0]  AND det_activo.Act_val LIKE '%$Par_Sql[1]%'
				ORDER BY mantenimie.Man_Fec";					   		   		  
		   //echo $sql;
		   return $sql;
		   break;
			
			/**
		 * Buscar una persona especifica
		 * @param string $Par_Sql[0] cedula de la persona
		 * @param string $Par_Sql[1] ruc de la persona
		 */
		case 488:
			$sql= "SELECT 
					Prs_Cod
				   FROM 
				   	persona
				   WHERE 
				   	(Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND Prs_Est='A' ORDER BY Prs_Cod ASC;";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Cadena para obtener los datos de una persona
		 * @param string $Par_Sql[0] Codigo principal de la persona
		 */
		case 489:
			$sql = "SELECT 
						enc_manten.Ema_Cod 
					FROM 
						enc_manten, persona
					WHERE
						persona.Prs_Cod = enc_manten.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]'"; 
						//AND personal.Emp_Cod = '$Par_Sql[1]'";
			//echo $sql;
			return $sql;
		break;	
		
		/**
		 * Obtiene datos de una persona
		 * @param string $Par_Sql[0] cedula de la persona
		 * @param string $Par_Sql[1] ruc de la persona
		 */
		case 490:
			$sql= "SELECT 
					persona.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, IF (persona.Prs_Sex='M','Masculino','Femenino') as sexo , Prs_Esc, Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel,Prs_Cor, identifica.Ide_Cod, Ide_Des,persona.Pas_Cod,Prs_San,Prs_Fec 
					FROM 
						persona, identifica
					WHERE 
						(Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND
						identifica.Ide_Cod=persona.Ide_Cod";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Insertar datos en la tabla persona
		 */
		case 491:
			$sql = "INSERT INTO 
				persona (Prs_Ced, Prs_Nom, Prs_Ape,Prs_Dir, Prs_Tel, Prs_Te2,Prs_Cel,Prs_Sex)
					VALUE ('$Par_Sql[0]', UPPER('$Par_Sql[1]'), UPPER('$Par_Sql[2]'), '$Par_Sql[3]',
			'$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]')";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Insertar datos en la tabla personal
		 */
		case 492:			
			$sql = "INSERT INTO 
						enc_manten (Prs_Cod,Ema_Obs,Ema_Esp) 
					VALUE 
						($Par_Sql[0],UPPER('$Par_Sql[1]'), UPPER('$Par_Sql[2]'))";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Cadena que nos permitira saber que tipo de identificacion es la ingresada
		 * @param string $Par_Sql[0] numero de la identificacion eje 10
		 */
		case 493:
			$sql= "SELECT Ide_Cod, Ide_Des, Ide_Max, Ide_Raz
					FROM 
						identifica
					WHERE 
						Ide_Max = '$Par_Sql[0]'";
			return $sql;
		break;
		
			
		 /**
		  * Consulta de encargado de mantenimiento por Apellido para 
		  * módulo modificar encargado de mantenimiento de Activo Fijo
		  */
		case 494:
			$sql = "SELECT
						persona.Prs_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,
						enc_manten.Ema_Cod,enc_manten.Ema_Esp,enc_manten.Ema_Est,enc_manten.Ema_Obs
					FROM 
						persona,enc_manten
					WHERE  
						persona.Prs_Cod=enc_manten.Prs_Cod AND
						Prs_Ape like '%$Par_Sql[0]%'";
			
			//echo $sql;
			return $sql;
		break;
			
		 /**
		  * Consulta de encargado de mantenimiento por céedula para
		  *	controlar modulo modificar encargado de mantenimiento.
		  */
		case 495:
			$sql = "SELECT 
						persona.Prs_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,
						enc_manten.Ema_Cod,enc_manten.Ema_Esp,enc_manten.Ema_Est,enc_manten.Ema_Obs							
					FROM 
						persona,enc_manten
				 	WHERE  
						persona.Prs_Cod=enc_manten.Prs_Cod AND 
						Prs_Ced = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		break;
		
		 /**
		  * Consulta de encargado de mantenimiento por Código del mante. para
		  *	controlar modulo modificar encargado de mantenimiento.
		  */
		case 496:
			$sql = "SELECT 
						persona.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, persona.Prs_Sex, Prs_Esc, Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel,Prs_Cor, identifica.Ide_Cod, Ide_Des,persona.Pas_Cod,Prs_San,Prs_Fec,
						enc_manten.Ema_Cod,enc_manten.Ema_Esp,enc_manten.Ema_Est,enc_manten.Ema_Obs							
					FROM 
						persona,enc_manten,identifica
				 	WHERE  
						persona.Prs_Cod=enc_manten.Prs_Cod AND
						identifica.Ide_Cod=persona.Ide_Cod AND  
						enc_manten.Ema_Cod = $Par_Sql[0]";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualiza datos en la tabla persona  por mantenimeinto de activo
		 */
		case 497:
			$sql = "UPDATE persona SET 
					 Prs_Nom=UPPER('$Par_Sql[0]'), Prs_Ape=UPPER('$Par_Sql[1]'),Prs_Dir='$Par_Sql[2]', Prs_Tel='$Par_Sql[3]', Prs_Te2='$Par_Sql[4]',Prs_Cel='$Par_Sql[5]',Prs_Sex='$Par_Sql[6]'
					WHERE Prs_Cod=$Par_Sql[7]";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualiza datos en la tabla mantenimiento
		 */
		case 498:			
			$sql = "UPDATE enc_manten SET 
						  Ema_Obs=UPPER('$Par_Sql[0]'),Ema_Esp=UPPER('$Par_Sql[1]') 
					WHERE Ema_Cod=$Par_Sql[2]";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualiza el estado  del encragado de mantenimineto en la tabla mantenimient
		 */
		case 499:			
			$sql = "UPDATE 
						enc_manten 
					SET 
						 Ema_Est='$Par_Sql[0]' 
					WHERE 
						 Ema_Cod=$Par_Sql[1]";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Conaulta los Activos que estan en mantenimiento ejecutado
		 */
		case 500: 
		$conact = "SELECT 
					DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est 
				   FROM 
				   	activo, tipo_activo, mantenimie
				   WHERE 
				   	activo.Tia_Cod = tipo_activo.Tia_Cod AND
					activo.Act_Cod = mantenimie.Act_Cod AND
					tipo_activo.Emp_Cod = $Par_Sql[1] AND 
					Act_Des LIKE UPPER('%$Par_Sql[0]%') AND 
					mantenimie.Man_Pro=0";
		//echo $conact;
		return $conact;
		break;
		
		/**
		 * Consulta los Activos x Codigo secuencial y que esten en mantenimientos
		 */
			case 501: 
			$conact = "SELECT 
						DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
					   FROM
					    activo, tipo_activo, mantenimie
					   WHERE 
					   	activo.Tia_Cod = tipo_activo.Tia_Cod AND 
						activo.Act_Cod = mantenimie.Act_Cod AND 
						tipo_activo.Emp_Cod = $Par_Sql[1] AND
						Act_Cdc = '$Par_Sql[0]' AND
						mantenimie.Man_Pro=0";
			//echo $conact;
			return $conact;
			break;
		
			/**
			 * Conaulta los Activos x Codigo de Barra  de los mantenimeintos  ejecutados
			 */
			case 502: 
			$conact = "SELECT 
						DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, Prv_Cod, Act_Des, IF(activo.Act_Est = 'A', 'Activo', 'Inactivo') AS Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
						FROM 
							activo, tipo_activo,mantenimie 
						WHERE 
							activo.Tia_Cod = tipo_activo.Tia_Cod AND
							activo.Act_Cod = mantenimie.Act_Cod AND
						    Act_Bar =  Trim('$Par_Sql[0]') AND
							mantenimie.Man_Pro=0";
			//echo $conact;
			return $conact;
			break;
		
			/**
		     * Consulta de los activos  por campos para ek nantenimiento de activos
		     */
		   case 503: 
		   $sql = "SELECT 
					DISTINCT(activo.Act_Cod), activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, activo.Est_Cod, activo.Act_Est, Prv_Cod, Act_Des, Act_Obs,
					 tipo_activo.Tia_Cdc, Act_Cdc, Act_Can,det_activo.Act_val,campos_act.Cam_Cod, Act_Bar
			 FROM 
				activo 
				INNER JOIN `det_activo` ON activo.`Act_Cod`= det_activo.`Act_Cod`
 		 		INNER JOIN `tipo_activo` ON `activo`.`Tia_Cod`= `tipo_activo`.`Tia_Cod`
                INNER JOIN `campos_act` ON `campos_act`.`Cam_Cod`= `det_activo`.`Cam_Cod`
				INNER JOIN `mantenimie` ON mantenimie.`Act_Cod`= activo.Act_Cod 
                WHERE 
				 `campos_act`.`Cam_Cod`= $Par_Sql[0]  AND
				 mantenimie.Man_Pro=0 AND
				 det_activo.Act_val LIKE '%$Par_Sql[1]%'
				ORDER BY 
				mantenimie.Man_Fec";					   		   		  
		   //echo $sql;
		   return $sql;
		   break;		
			
		
		/**
		 * Consulta los manteniminetos por ejecutarse  de los activos por fecha
		 */
		case 504:
			$sql="SELECT 
					mantenimie.Man_Cod,
					mantenimie.Act_Cod,
					CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS Encargado,
					estado_act.Est_Des,
					mantenimie.Man_Fec,
					mantenimie.`Man_Fet`,
					mantenimie.`Man_Pro`,
					mantenimie.`Man_Des`,
					mantenimie.`Man_Obs`,
					activo.Act_Des,
					activo.Act_Cdc					 
				FROM 
					mantenimie 
						INNER JOIN enc_manten ON enc_manten.Ema_Cod=mantenimie.Ema_Cod
						INNER JOIN persona ON enc_manten.Prs_Cod=persona.Prs_Cod
						INNER JOIN estado_act ON estado_act.Est_Cod=mantenimie.Est_Cod
						INNER JOIN activo ON activo.Act_Cod = mantenimie.Act_Cod
				WHERE 					
					mantenimie.Man_Fec >='$Par_Sql[0]' AND
					mantenimie.Man_Fec <='$Par_Sql[1]'	
				ORDER BY
					mantenimie.Man_Fec";
				//echo $sql;
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
			//echo $sql;
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
				$sql="SELECT 
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
			   $sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			   return $sql;
			  break;
			
                    
                        /*Inicio de case by Jos� Ambulud�*/
                        /**
                         * Consulta para listar los encargados de mantenimiento por empresa
                         */
			case 5004:
                            if($Par_Sql['op_encargado']=='d'){$search="Prs_Ape LIKE ('%$Par_Sql[search_encargado]%')";}
                            else{$search="Prs_Ced LIKE ('%$Par_Sql[search_encargado]%')";}
                            if(isset($Par_Sql['limits']))
                            {
                                $Par_Sql['limits']="ORDER BY encargado $Par_Sql[limits]";
                                $campos="Ema_Cod,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS encargado,Ema_Esp";
                            }
                            else{$campos="COUNT(Ema_Cod) as total";$Par_Sql['limits']="";}
                            $sql="SELECT $campos
                                  FROM enc_manten,persona
                                  WHERE $search AND enc_manten.Prs_Cod=persona.Prs_Cod AND Ema_Est='A' AND Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
                            return $sql;
                        break;
                        
                        /**
                         * Consulta para listar los encargados de mantenimiento por empresa
                         */
			case 5005:
                            if($Par_Sql['op_activo']=='d'){$search="Act_Des LIKE ('%$Par_Sql[search_activo]%')";}
                            else{$search="Act_Bar LIKE ('%$Par_Sql[search_activo]%')";}
                            if(isset($Par_Sql['limits']))
                            {
                                $Par_Sql['limits']="ORDER BY Act_Bar $Par_Sql[limits]";
                                $campos="Act_Cod,Act_Bar,Act_Des,Est_Des";
                            }
                            else{$campos="COUNT(Act_Cod) as total";$Par_Sql['limits']="";}
                            $sql="SELECT $campos
                                  FROM activo,estado_act
                                  WHERE $search AND activo.Est_Cod=estado_act.Est_Cod AND Act_Est='A' AND Suc_Cod=$Par_Sql[Suc_Cod] $Par_Sql[limits]";
                            return $sql;
                        break;
                        
                        /**
                         * Consulta para listar los estados de la tabla tipo_mante
                         */
                        case 5006:
                            $sql="SELECT * 
                                  FROM tipo_mante
                                  WHERE Tma_Est='A' ORDER BY Tma_Des";
                            return $sql;
                        break;
		}
	}
