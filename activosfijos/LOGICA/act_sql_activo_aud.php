<?php
	/**
	 * AUDITORIA DE ACTIVOS FIJOS
	 */
	function sentencias_act_aud($id,$Par_Sql)
	{
		switch($id)
		{

 			/************************************************************************************
		    * Sentencias para el control de Tenencia de Activos Fijos
			************************************************************************************/
			/**
		 * Obtener personal custodio por cedula y por empresa
		 */
		case 1:
			$sql = "SELECT 
						`custodio`.`Cus_Cod`,`persona`.`Prs_Ced`, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Est`
					FROM
						`personal`
						INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
						INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
						INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
						INNER JOIN `custodio` ON `custodio`.`Con_Cod` = `contratos_lab`.`Con_Cod`
					WHERE 
						`personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced`='$Par_Sql[1]'";
			//echo $sql;
			return $sql;
		break;
		
		 /**
		  * Buscar personal custodio contratado por apellido
		  */
		case 2:
			$sql = "SELECT 
						`contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Cod`,`custodio`.`Cus_Est`
					FROM 
						`personal`
						INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
						INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
						INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
						INNER JOIN `custodio` ON `contratos_lab`.`Con_Cod` = `custodio`.`Con_Cod`
					WHERE
					 	`personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'
						AND `contratos_lab`.`Con_Est` = 'A'";
			//echo $sql;
			return $sql;
		break;
		
		   
		 /**
		  * Consulta los activos x custodio y que no esten confirmados 
		  */
		   case 3: 
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
						  activo.Act_Obs,
						  persona.Prs_Cod,
						  estado_act.Est_Des,
						  estado_act.Est_Cod		 
						FROM
						  custodio
						  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
						  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
						  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
						  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
						  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
						  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
						  INNER JOIN estado_act ON (estado_act.Est_Cod = activo.Est_Cod)	  
						WHERE
						  custodio.Cus_Cod = $Par_Sql[0] AND asignacion.Asg_Con = 'C'
						  AND asignacion.Asg_Est='A'
						ORDER BY activo.Act_Des";
				//echo $concus;
			   return $concus;
			   break;
						   						   
						     
		   /**
			* Consulta de los estados de los activos
			*/
			case 4: 
				$sql = "SELECT Est_Cod, Est_Des FROM estado_act WHERE Est_Est = 'A'";
			//echo $sql;
			return $sql;
			break;
						
						
			case 5:
			/**
			 * Consultar sucursal en base al código 
			 */
			$cons_codsuc = "SELECT
							 empresas.Emp_Cod, empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Cod, ciudad.Ciu_Des, sucursal.Suc_Sri,
							 sucursal.Suc_Dir, sucursal.Suc_Des, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor,
							 sucursal.Suc_Web ,Emp_Log
						FROM 
							empresas,ciudad,sucursal
						WHERE 
							sucursal.Ciu_Cod = ciudad.Ciu_Cod AND sucursal.Emp_Cod = empresas.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0]";
			//echo $cons_codsuc;
			return $cons_codsuc;
			break;
						
			case 6:
			/**
			 * Insertado de la cabecera de la auditoria del activo.
			 */
				$sql = "INSERT INTO	 activo_aud (Cus_Cod,Aud_Cod,Aud_Fec) VALUE ($Par_Sql[0],$Par_Sql[1],'$Par_Sql[2]')";
			return $sql;
			break;
						
			case 7:
			/**
			 * Insertado el detalle de la auditoria del activo.
			 */
				$sql = "INSERT INTO det_audita (Aud_Int,Act_Cod,Est_Cod,Aud_Obs,Est_Act) VALUE ($Par_Sql[0],$Par_Sql[1],$Par_Sql[2],UPPER('$Par_Sql[3]'),$Par_Sql[4])";
			return $sql;
			break;
						
						
			case 8:
			/**
			 * Consultar el auditor  por id del persona
			 */
			 
				$sql="SELECT 
							`contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre,auditor.Aud_Cod
						FROM 
							`personal`
							INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
							INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
							INNER JOIN `auditor` ON `contratos_lab`.`Con_Cod` = `auditor`.`Con_Cod`
							
						WHERE
							`contratos_lab`.`Con_Est` = 'A'
								AND persona.Prs_Cod = $Par_Sql[0]
								AND  auditor.Aud_Est ='A'";
			 
			//echo $sql;
			return $sql;
			
			break;
				
				
			/**
			 * Consulta de las auditorias ralizadas a los custodios por fecha de registro de la 
			 *	la auditoria.
			 */			
			case 9:
				$sql="SELECT 
						activo_aud.Aud_Int,activo_aud.Aud_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as Custodio,
						activo_aud.Aud_Fec,custodio.Cus_Cod,activo_aud.Aud_Apr,activo_aud.Aud_Est, persona.Prs_Ced
					
					FROM 
						activo_aud 
						INNER JOIN custodio ON custodio.Cus_Cod = activo_aud.Cus_Cod
						INNER JOIN contratos_lab ON contratos_lab.Con_Cod=custodio.Con_Cod
						INNER JOIN personal ON personal.Per_Cod= contratos_lab.Per_Cod
						INNER JOIN persona ON persona.Prs_Cod= personal.Prs_Cod						
					WHERE 
						activo_aud.Aud_Fec 
						BETWEEN '$Par_Sql[0]' AND '$Par_Sql[1]'
						AND personal.Emp_Cod=$Par_Sql[2]  AND activo_aud.Aud_Cod=$Par_Sql[3]";
				//echo $sql;
				return $sql;
			break;		
				
			/**
			 * Consulta del detalle de la auditoria.
			 */			
			case 10:
				$sql="SELECT  
							activo.Act_Des,activo.Act_Cod,estado_act.Est_Des,det_audita.Aud_Obs,det_audita.Est_Act
						FROM 
							activo 
							INNER JOIN det_audita  ON activo.Act_Cod= det_audita.Act_Cod
							INNER JOIN estado_act ON estado_act.Est_Cod= det_audita.Est_Cod						
						WHERE 
							det_audita.Aud_Int=$Par_Sql[0]";
				return $sql;
			break;			
						
			case 11:
			/**
			 * Consultar el auditor 
			 */
			 
				$sql="SELECT 
							`contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre
						FROM 
							`personal`
							INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
							INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
							INNER JOIN `auditor` ON `contratos_lab`.`Con_Cod` = `auditor`.`Con_Cod`
							
						WHERE
							auditor.Aud_Cod = $Par_Sql[0]";
			 
			//echo $sql;
			return $sql;			
			break;
						
			case 12:
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
			 * Consulta de las auditorias
			 */			
			case 13:
				$sql="SELECT 
						activo_aud.Aud_Int,activo_aud.Aud_Cod,CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as Custodio,
						activo_aud.Aud_Fec,custodio.Cus_Cod,activo_aud.Aud_Apr,activo_aud.Aud_Est, persona.Prs_Ced
					
					FROM 
						activo_aud 
						INNER JOIN custodio ON custodio.Cus_Cod = activo_aud.Cus_Cod
						INNER JOIN contratos_lab ON contratos_lab.Con_Cod=custodio.Con_Cod
						INNER JOIN personal ON personal.Per_Cod= contratos_lab.Per_Cod
						INNER JOIN persona ON persona.Prs_Cod= personal.Prs_Cod						
					WHERE 
						activo_aud.Aud_Int=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;	
			
			
			/**
			 * Consulta de los estados de los activos por código del activo
			 */
			case 14: 
				$sql = "SELECT Est_Cod, Est_Des FROM estado_act WHERE Est_Cod =$Par_Sql[0]";
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
					 
					 
			
			
		}
	}
?>