<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 1.0
 * 
 * @param number $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package cajachica.LOGICA
 */
function sentencias_cch($id,$Par_Sql)
{
	switch($id)
	{		
		/**
		 * buscar personal contratado por apellido
		 */
		case 1:
			$sql = "SELECT 
				`contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,`tiposcargo`.`Tic_Des`
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'
			AND `contratos_lab`.`Con_Est` = 'A'";
			//echo $sql;
			return $sql;
		break;		
		/**
		 * buscar personal contratado por cedula		 
		case 2:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre, `tiposcargo`.`Tic_Des`
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced` = '$Par_Sql[1]'
			AND `contratos_lab`.`Con_Est` = 'A'";
			return $sql;			
		break;	*/
		
		/**
		 * buscar personal contratado por cedula
		 */
		case 2:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,`tiposcargo`.`Tic_Des`
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced` = '$Par_Sql[1]'
			AND `contratos_lab`.`Con_Est` = 'A'";
			//echo $sql;
			return $sql;
		break;
						
		/**
		 * Contar si ya ha sido asignado o tiene un autorizador vigente
		 */
		case 3:			
			$sql = "SELECT COUNT(`Cus_Cod`)AS 'count' FROM `custodio` WHERE `Con_Cod` = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
		break;	
		/**
		 * Insertado de datos en autorizado
		 */
		case 4:
			$sql = "INSERT INTO `custodio` (`Con_Cod`) VALUES ('$Par_Sql[0]')";
			//echo $sql;
			return $sql;
		break;		
		/**
		 * Obtener personal custodio por cedula y por empresa
		 */
		case 5:
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
		 * obtener personal autorizado por cedula
		 */
		case 6:
			$sql = "SELECT `custodio`.`Cus_Cod`,`persona`.`Prs_Ced`,`persona`.`Prs_Ape`,`persona`.`Prs_Nom`,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Est`
			FROM
				`personal`
				INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
				INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
				INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
				INNER JOIN `custodio` ON `custodio`.`Con_Cod` = `contratos_lab`.`Con_Cod`
			WHERE 
				`personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced` = '$Par_Sql[1]'";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualizar estado de autorizado
		 */
		case 7:
			$sql = "UPDATE 
						`custodio` 
							SET `Cus_Est` = '$Par_Sql[0]' WHERE `Cus_Cod` = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Consulta la provicia y pais de la ciudad de la sucursal
		 */
		case 21:
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
		 * Consulta la informaci�n la ciudada en base a la sucursal
		 */
		case 22:
			$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
			sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Consulta los datos del usuario
		 */
		case 23:
			$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
			return $sql;
			//echo $sql;
			
		break;		
		
	    case 134:
			/**
			 * Consultar sucursal en base al c�digo 
			 */
			$cons_codsuc = "SELECT empresas.Emp_Cod, empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Cod, ciudad.Ciu_Des, sucursal.Suc_Sri, sucursal.Suc_Dir, sucursal.Suc_Des, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web ,Emp_Log
			FROM empresas,ciudad,sucursal
			WHERE sucursal.Ciu_Cod = ciudad.Ciu_Cod AND sucursal.Emp_Cod = empresas.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0]";
		//echo $cons_codsuc;
		return $cons_codsuc;
		break;
		 /**
		  * Consulta los activos x custodio
		  */
		   case 135: 
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
		  activo.Act_Obs,
		  persona.Prs_Cod		 
		FROM
		  custodio
		  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
		  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
		  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
		  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
		  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
		  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
		  INNER JOIN tiposcargo ON (tiposcargo.Tic_Cod= contratos_lab.Tic_Cod)
		  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod) 
		WHERE
		  	custodio.Cus_Cod = $Par_Sql[0] 
		  AND asignacion.Asg_Con = 'C' 
		  AND asignacion.Asg_Est='A' 
		ORDER BY 
			departamen.Dep_Des";
		   //echo $concus;
		   return $concus;
		   break;
		    			
			
		/**
		 * Consulta los datos del custodio
		 */	
		    case 136: 
		$concus = "SELECT 	 
				  persona.Prs_Ced,
				  CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,persona.Prs_Cod, persona.Prs_Cod,custodio.Cus_Cod,
				  departamen.Dep_Des		 
				FROM
				  custodio
				  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
				  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
				  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
				  INNER JOIN tiposcargo ON (tiposcargo.Tic_Cod=contratos_lab.Tic_Cod)
				  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod) 
				WHERE
				  custodio.Cus_Cod = $Par_Sql[0]";
		   //echo $concus;
		   return $concus;
		   break;
		   
		 /**
		  * buscar personal custodio contratado por apellido
		  */
		case 137:
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
		   
		   // Consulta los activos x custodio
		case 138: 
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
		  * buscar personal custodio contratado  por codigo de contrato
		  */
		case 139:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Cod`,`custodio`.`Cus_Est`
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			INNER JOIN `custodio` ON `contratos_lab`.`Con_Cod` = `custodio`.`Con_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `custodio`.`Con_Cod`=$Par_Sql[1]
			AND `contratos_lab`.`Con_Est` = 'A'";
			//echo $sql;
			return $sql;
		break;		
		
			
			/** 
			 * Consulta los Campos x cada Tipo de Activo
			 */
			case 140: 
			$concam = "SELECT 
						campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est 
						FROM 
							campos_act 
						WHERE 
							campos_act.`Cam_Bus`= 'S' AND
							campos_act.Cam_Est = 'A' ORDER BY Cam_Cod";
			//echo $concam;
			return $concam;
			break;	
			
			/** 
			 * Consulta valor del campo  para un activo
			 */
			case 141: 
			$concam = "SELECT 
							det_activo.Act_Val 
						FROM 
							det_activo INNER JOIN campos_act ON 
					   		det_activo.Cam_Cod = campos_act.Cam_Cod INNER JOIN activo ON 
					   		det_activo.Act_Cod= activo.Act_Cod 
						WHERE 
							campos_act.Cam_Est = 'A' AND 
							det_activo.Act_Cod= $Par_Sql[0] AND 
							campos_act.Cam_Cod = $Par_Sql[1] 
						ORDER BY campos_act.Cam_Cod";
			//echo $concam;
			return $concam;
			break;
			
		/**
		 * buscar personal contratado por apellido
		 */
		case 142:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,`tiposcargo`.`Tic_Des`, persona.Prs_Cod
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' 
			AND `contratos_lab`.`Con_Est` = 'A' order by `persona`.`Prs_Ape` " ;
			//echo $sql;
			return $sql;
		break;	
		
		 /**
		  * Consulta de los custodio activos 
		  */	
		    case 143: 
		   $concus = "SELECT 	 
					  persona.Prs_Ced,
					  CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,persona.Prs_Cod, persona.Prs_Cod,custodio.Cus_Cod		 
					FROM
					  custodio
					  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
					  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
					  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
					WHERE
					  contratos_lab.Con_Est ='A' and custodio.Cus_Est = 'A' AND personal.Emp_Cod = '$Par_Sql[0]'
					ORDER BY 
					  Nombre";
//		   echo $concus;
		   return $concus;
		   break;		   
		   
		/**
		 * Consulta de la catidad  del orden de la asignacion segun el codigo del custodio 
		 * y el codigo del activo
		 */
		case 144:
			$sql="SELECT 
					COUNT(`asignacion`.`Asg_Ord`) AS Orden
				FROM 
					asignacion 
						INNER JOIN activo ON activo.`Act_Cod` = `asignacion`.`Act_Cod`
        				INNER JOIN custodio ON custodio.Cus_Cod = asignacion.`Cus_Cod`
				WHERE 
					asignacion.Cus_Cod= $Par_Sql[0]  AND activo.`Act_Cod`=$Par_Sql[1]";
					//echo $sql;
			return $sql;		
		break;	
					
		/**
		 * Inserta asignacion de activos fijos a custodio. 
		 */
		 case 149:
			$sql = "INSERT INTO 
						asignacion 
					(Cus_Cod, Act_Cod, Asg_Fec, Asg_Hor, Sec_Cod, Asg_Ord,Asg_Con,Asg_Raz) 
					VALUE 
						($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]', '$Par_Sql[3]', $Par_Sql[4], $Par_Sql[5],'N','$Par_Sql[6]')";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualiza campo de confirmacion de signacion de activo fijo a custodio.
		 */
		 case 150:
			$sql = "UPDATE 
						asignacion  
					SET 
						Asg_Con='N' 
					WHERE 
						Cus_Cod = '$Par_Sql[0]' AND
						 Act_Cod='$Par_Sql[1]'";  
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualiza estado de la signacion de activo fijo  del custodio que entrega.
		 */
		 case 151:
			$sql = "UPDATE 
						asignacion  
					SET 
						Asg_Est='I' 
					WHERE 
						Cus_Cod = '$Par_Sql[0]' AND
						 Act_Cod='$Par_Sql[1]'";  
			//echo $sql;
			return $sql;
		break;
					
			
		   /*************************************************************************
		    * Sentencias para el control de Autorizacion de Transferencia de Activo *
			*************************************************************************/
			/**
		  	 * Consulta todos los custodios que no esten confirmados 
		  	 */
		   case 2001: 
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
						  activo.Act_Obs,
						  persona.Prs_Cod,
						  estado_act.Est_Des,
						  asignacion.Asg_Fec,
						  asignacion.Asg_Ord
		 
						FROM
						  custodio
						  INNER JOIN asignacion ON (custodio.Cus_Cod = asignacion.Cus_Cod)
						  INNER JOIN activo ON (asignacion.Act_Cod = activo.Act_Cod)
						  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
						  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
						  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
						  INNER JOIN tipo_activo ON (activo.Tia_Cod = tipo_activo.Tia_Cod)
						  INNER JOIN tiposcargo ON (tiposcargo.Tic_Cod= contratos_lab.Tic_Cod)
						  INNER JOIN departamen ON (tiposcargo.Dep_Cod = departamen.Dep_Cod) 
						  INNER JOIN estado_act ON (estado_act.Est_Cod = activo.Est_Cod)	  
						WHERE
						  custodio.Cus_Cod = $Par_Sql[0] AND
						   asignacion.Asg_Con = 'N' 
						ORDER BY departamen.Dep_Des";
						   //echo $concus;
						   return $concus;
						   break;
			
			/**
			 * Consulta todos los Custodios registrados  que esten en estado activo
			 */
			case 2002:
				$sql="SELECT custodio.Cus_Cod,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) AS Nombres
						 FROM `personal` 
							INNER JOIN persona ON  `personal`.`Prs_Cod` = persona.`Prs_Cod`
							INNER JOIN contratos_lab ON  `personal`.`Per_Cod` = contratos_lab.`Per_Cod` 
							INNER JOIN custodio ON custodio.`Con_Cod`= `contratos_lab`.`Con_Cod`       
						 WHERE 
							`personal`.`Per_Est`='A' AND
							`personal`.`Emp_Cod`=$Par_Sql[0] AND 
							`custodio`.`Cus_Est` = 'A'
						ORDER BY Nombres";
			return $sql;
			break;
			
		   case 2003:
		   $sql="SELECT 
				* 
				FROM 
				`asignacion` 
				WHERE 
				`asignacion`.`Asg_Est`= 'A' AND `asignacion`.`Asg_Con`= 'N' AND `asignacion`.`Cus_Cod`=$Par_Sql[0]";
				return $sql;
		   break;		   
		   
		    /**
			 * Actualiza campo de confirmacion de signacion de activo fijo a custodio.
			 */
			 case 2004:
				$sql = "UPDATE asignacion  SET Asg_Con='C' WHERE Cus_Cod = '$Par_Sql[0]' AND Act_Cod='$Par_Sql[1]' AND Asg_Est='A'";  
				//echo $sql;
				return $sql;
			break;		
			
			 /**
			 * Obtener los custodios de activos que ya esten inactivos par ORDEN
			 */
			 case 2005:
				$sql = "SELECT     
							custodio.Cus_Cod,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) AS Nombres,asignacion.`Asg_Ord`,
							asignacion.`Asg_Est`
							 FROM
							  `personal` 
								INNER JOIN persona ON  `personal`.`Prs_Cod` = persona.`Prs_Cod`
								INNER JOIN contratos_lab ON  `personal`.`Per_Cod` = contratos_lab.`Per_Cod` 
								INNER JOIN custodio ON custodio.`Con_Cod`= `contratos_lab`.`Con_Cod`       
								INNER JOIN asignacion ON `asignacion`.`Cus_Cod`= custodio.`Cus_Cod`
							 WHERE 
								`personal`.`Per_Est`='A' AND
								`personal`.`Emp_Cod`=$Par_Sql[0] AND 
								`custodio`.`Cus_Est` = 'A' AND 
								 asignacion.`Act_Cod`=$Par_Sql[1] AND 
								 asignacion.Asg_Est='I'  AND
								`asignacion`.`Asg_Con`='C'        
							ORDER BY Asg_Ord DESC";  
				//echo $sql;
				return $sql;
			break;	
			
			
 
			
		   
		   /**********************************************************
		    * Sentencias para el control de Tenencia de Activos Fijos*
			**********************************************************/
		   
		 /**
		  * Consulta los activos x custodio y que no esten confirmados 
		  */
		   case 1001: 
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
						  seccion.Sec_Des,
						  activo.Act_Obs,
						  persona.Prs_Cod,
						  estado_act.Est_Des
		 
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
						  INNER JOIN estado_act ON (estado_act.Est_Cod = activo.Est_Cod)	  
						WHERE
						  custodio.Cus_Cod = $Par_Sql[0] AND asignacion.Asg_Con = 'N' ORDER BY departamen.Dep_Des, seccion.Sec_Des";
						   //echo $concus;
						   return $concus;
						   break;
		   
		   
		   /**
		    * Consulta de los estados de los activos
			*/
			case 1002: 
			$sql = "SELECT Est_Cod, Est_Des FROM estado_act WHERE Est_Est = 'A'";
			//echo $sql;
			return $sql;
			break;
		   
		   
		   /**
		    * Consulta de los Custodios activos 
			*/
		   
		   
		   
		   /***********************************************************
		    * Fin de Sentencias de Control de tenecia de Activos Fijos*
		    ***********************************************************/
		  
		  
		  
		  
		  	
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
	   *  Consulta la informaci�n la ciudada en base a la sucursal
	   */
	   case 5001:
		$sql="SELECT empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
		sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log FROM empresas, sucursal, ciudad WHERE sucursal.Suc_Cod = $Par_Sql[0] AND empresas.Emp_Cod = sucursal.Emp_Cod AND sucursal.Ciu_Cod = ciudad.Ciu_Cod";
		return $sql;
	   break;
   
	 /**
	 *  Consulta los datos del usuario
	 */
		case 5002:
	   $sql="SELECT Prs_Ape, Prs_Nom, Prs_Ced FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
	   return $sql;
		 break;
		 
		 
		
			
			
	}
}
?>