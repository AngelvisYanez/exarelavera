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
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`, persona.Prs_Ape, persona.Prs_Nom,`tiposcargo`.`Tic_Des`
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
			echo $sql;
			return $sql;			
		break;	*/
		
		/**
		 * buscar personal contratado por cedula
		 */
		case 2:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`, persona.Prs_Ape, persona.Prs_Nom,`tiposcargo`.`Tic_Des`
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
		 * Obtener personal custodio por cedula
		 */
		case 5:
			$sql = "SELECT `custodio`.`Cus_Cod`,`persona`.`Prs_Ced`, CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Est`
			FROM
			`personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			INNER JOIN `custodio` ON `custodio`.`Con_Cod` = `contratos_lab`.`Con_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced`='$Par_Sql[1]'";
//			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'";
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
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ced` = '$Par_Sql[1]'";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * Actualizar estado de autorizado
		 */
		case 7:
			$sql = "UPDATE `custodio` SET `Cus_Est` = '$Par_Sql[0]' WHERE `Cus_Cod` = '$Par_Sql[1]'";
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
		 * Consulta la información la ciudada en base a la sucursal
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
			 * Consultar sucursal en base al código 
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
  seccion.Sec_Des,
  activo.Act_Obs,
  estado_act.Est_Des,
  activo.Act_Val 
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
  INNER JOIN estado_act ON (activo.Est_Cod = estado_act.Est_Cod)
		WHERE
		  custodio.Cus_Cod = $Par_Sql[0] ORDER BY departamen.Dep_Des, seccion.Sec_Des";
		   //echo $concus;
		   return $concus;
		   break;

		    
			
			
		/**
		 * Consulta los datos del custodio
		 */	
		    case 136: 
		   $concus = "SELECT 	 
		  persona.Prs_Ced,
		  CONCAT(persona.Prs_Ape, ' ', persona.Prs_Nom) AS Nombre		 		 
		FROM
		  custodio
		  INNER JOIN contratos_lab ON (custodio.Con_Cod = contratos_lab.Con_Cod)
		  INNER JOIN personal ON (contratos_lab.Per_Cod = personal.Per_Cod)
		  INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
		WHERE
		  custodio.Cus_Cod = $Par_Sql[0]";
		   //echo $concus;
		   return $concus;
		   break;
		   
		 /**
		 * buscar personal custodio contratado por apellido
		 */
		case 137:
			$sql = "SELECT `contratos_lab`.`Con_Cod`,`persona`.`Prs_Ced`,CONCAT(`persona`.`Prs_Ape`,' ',`persona`.`Prs_Nom`) as Nombre,`tiposcargo`.`Tic_Des`,`custodio`.`Cus_Cod`,`custodio`.`Cus_Est`
			FROM `personal`
			INNER JOIN `persona` ON `personal`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `contratos_lab` ON `personal`.`Per_Cod` = `contratos_lab`.`Per_Cod`
			INNER JOIN `tiposcargo` ON `contratos_lab`.`Tic_Cod` = `tiposcargo`.`Tic_Cod`
			INNER JOIN `custodio` ON `contratos_lab`.`Con_Cod` = `custodio`.`Con_Cod`
			WHERE `personal`.`Emp_Cod` = '$Par_Sql[0]' AND `persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'
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
		  * buscar personal custodio contratado por apellido
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
			$concam = "SELECT campos_act.Cam_Cod, campos_act.Cam_Lar, campos_act.Cam_Cor, campos_act.Cam_Tip, campos_act.Cam_Obs, campos_act.Cam_Est FROM campos_act WHERE campos_act.`Cam_Bus`= 'S' AND campos_act.Cam_Est = 'A' ORDER BY Cam_Cod";
			//echo $concam;
			return $concam;
			break;	
			
			/** 
			 * Consulta valor del campo  para un activo
			 */
			case 141: 
			$concam = "SELECT det_activo.Act_Val FROM det_activo INNER JOIN campos_act ON 
					   det_activo.Cam_Cod = campos_act.Cam_Cod INNER JOIN activo ON 
					   det_activo.Act_Cod= activo.Act_Cod WHERE campos_act.Cam_Est = 'A' AND det_activo.Act_Cod= $Par_Sql[0] AND campos_act.Cam_Cod = $Par_Sql[1]  ORDER BY campos_act.Cam_Cod";
			//echo $concam;
			return $concam;
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