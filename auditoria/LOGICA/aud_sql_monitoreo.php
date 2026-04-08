<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 1.0
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package auditoria.LOGICA
 */
function sentencias($id,$Par_Sql){
	switch($id){
		/**
		 * Busqueda de un usuario por sucursal y coincidencias en su apellido
		 */
		case 1:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			`exa`.`usuarios`
			INNER JOIN `exa`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Suc_Cod` = $Par_Sql[0] AND
			`persona`.`Prs_Ape` LIKE '%$Par_Sql[1]%'";
                        //echo $sql;
			return $sql;
		break;
		/**
		 * busqueda de un usuario por sucursal y su cedula
		 */
		case 2:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			`exa`.`usuarios`
			INNER JOIN `exa`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Suc_Cod` = $Par_Sql[0] AND
			`persona`.`Prs_Ced` = '$Par_Sql[1]'";
			return $sql;
		break;
		/**
		 * Obtener datos de usuario
		 */
		case 3:
			$sql = "SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`
			FROM
			`exa`.`usuarios`
			INNER JOIN `exa`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`usuarios`.`Usu_Cod` = $Par_Sql[0]";
                        //echo $sql;
			return $sql;
		break;
		/**
		 * Obtener las seciones segun el usuario y entre fechas
		 */
		case 4:
			$sql = "SELECT `sesion`.`Ses_Cod`,`sesion`.`Ses_Int`,`sesion`.`Ses_Out`
			FROM
			`exa`.`usuarios`
			INNER JOIN `auditoria`.`sesion` ON `usuarios`.`Usu_Cod` = `sesion`.`Usu_Cod`
			INNER JOIN `exa`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			WHERE
			`sesion`.`Usu_Cod` = $Par_Sql[0] AND
			(DATE_FORMAT(`Ses_Int`,'%Y-%m-%d') BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]')
			ORDER BY `Ses_Int` DESC";
                        //echo $sql;
			return $sql;
		break;
		
		/**
		 * Obtener datos de la secion seleccionada
		 */
		case 5:
			$sql="SELECT `persona`.`Prs_Cod`,`persona`.`Prs_Nom`,`persona`.`Prs_Ape`,`persona`.`Prs_Ced`,`usuarios`.`Usu_Cod`,`usuarios`.`Usu_Est`,`sesion`.`Ses_Int`,`sesion`.`Ses_Out`
			FROM
			`exa`.`usuarios`
			INNER JOIN `exa`.`persona` ON `usuarios`.`Prs_Cod` = `persona`.`Prs_Cod`
			INNER JOIN `auditoria`.`sesion` ON `sesion`.`Usu_Cod` = `usuarios`.`Usu_Cod`
			WHERE
			`usuarios`.`Usu_Cod` = $Par_Sql[0] AND
			`sesion`.`Ses_Cod` = $Par_Sql[1]";
			return $sql;
		break;
		
		/**
		 * Obtener las actividades durante esa sessión del usuario 
		 */
		case 6:
			$sql = "SELECT `logs`.`Log_Fec`,`eventos`.`Eve_Des`,`tablas`.`Tab_Ali`,`procesos`.`Pcs_Det`,`procesos`.`Pcs_Lin`,`logs`.`Log_Cam`,`logs`.`Log_Val`,`logs`.`Tab_Cod`,`tablas`.`Tab_Nom`
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			INNER JOIN `exa`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]')
			ORDER BY `Log_Fec` DESC";
			return $sql;
		break;
		
		/**
		 * Obtener datos de una tabla
		 */
		case 7:
			$sql = "SELECT `tablas`.`Tab_Ali`,`tablas`.`Tab_Des`,`tablas`.`Tab_Nom`
			FROM `auditoria`.`tablas`
			WHERE `tablas`.`Tab_Cod` = $Par_Sql[0]";
			return $sql;
		break;
		
		/**
		 * obtener las carectaeristicas del campo
		 */
		case 8:
			$sql = "SELECT `campos`.`Cam_Ali`,`campos`.`Cam_Cod`,`campos`.`Cam_Des`
				FROM
				`auditoria`.`campos`
				WHERE
				`campos`.`Tab_Cod` = $Par_Sql[0] AND
				`campos`.`Cam_Atr` = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Obtener nombres de las tablas involucradas en la sql 
		 */
		case 9:
			$sql = "SELECT DISTINCT `tablas`.`Tab_Ali`,`tablas`.`Tab_Nom`,`tablas`.`Tab_Cod`
			FROM `auditoria`.`logs` 
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]')
			ORDER BY `Tab_Nom` ASC";
			return $sql;
		break;
		
		/**
		 * Obtener las actividades durante esa sessión del usuario
		 */
		case 10:
			$sql = "SELECT `logs`.`Log_Fec`,`eventos`.`Eve_Des`,`tablas`.`Tab_Ali`,`procesos`.`Pcs_Det`,`procesos`.`Pcs_Lin`,`logs`.`Log_Cam`,`logs`.`Log_Val`,`logs`.`Tab_Cod`,`tablas`.`Tab_Nom`
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			INNER JOIN `exa`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]') AND `tablas`.`Tab_Cod` = '$Par_Sql[3]'
			ORDER BY `Log_Fec` DESC";
			return $sql;
		break;
		
		/**
		 * Obtener el conteo de registro segun la sesión
		 */
		case 11:
			$sql = "SELECT COUNT(`logs`.`Log_Fec`)AS 'count'
			FROM
			`auditoria`.`logs`
			INNER JOIN `auditoria`.`eventos` ON `logs`.`Eve_Cod` = `eventos`.`Eve_Cod`
			INNER JOIN `auditoria`.`tablas` ON `tablas`.`Tab_Cod` = `logs`.`Tab_Cod`
			INNER JOIN `exa`.`procesos` ON `logs`.`Pcs_Cod` = `procesos`.`Pcs_Cod`
			WHERE `logs`.`Usu_Cod` = $Par_Sql[0] AND (`logs`.`Log_Fec` BETWEEN '$Par_Sql[1]' AND '$Par_Sql[2]')";
			return $sql;
		break;
	}
}?>