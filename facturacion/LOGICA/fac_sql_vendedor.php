<?php
/*TESORERIA*/

/*************************************FORMA DE PAGO********************************/
function sentencias_tes($id, $Par_Sql)
{
	$sql = "";
	switch ($id) {
		case 1:
			/* Consulta de persona por apellidos */
			$sql = "SELECT 
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape,
					  persona.Prs_Cod
					FROM
					  persona					  
					WHERE Prs_Ape LIKE '%$Par_Sql[0]%'  ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			break;

		case 2:
			/* Consulta del personal por cedula */
			$sql = "SELECT 
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape
					FROM
					  persona					  
					WHERE Prs_Ced= '$Par_Sql[0]' ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			break;

		case 3:
			/* Consulta del personal por codigo interno */
			$sql = "SELECT 
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  persona.Prs_Ape
					FROM
					  persona 
					WHERE Prs_Cod= '$Par_Sql[0]'";
			break;

		case 4:
			/* Consulta los puntos de imprecion */
			$sql = "SELECT 
					  Pun_Cod,Suc_Cod,Pun_Des,Pun_Ubi,Pun_Est
					FROM
					  puntos_imp 
					WHERE Pun_Est='A' AND Suc_Cod= '$Par_Sql[0]'";
			break;

		case 5:
			/* Consulta los datos del vendedor */
			$sql = "SELECT 
					  Vnd_Cod,Pun_Cod,Prs_Cod,Vnd_Est
					FROM
					  vendedor
					WHERE Pun_Cod='$Par_Sql[0]' AND Prs_Cod='$Par_Sql[1]' AND Vnd_Est='A'";
			break;

			/**
			 * Insertar un vendedor
			 */
		case 6:
			$sql = "INSERT INTO vendedor(Pun_Cod,Prs_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]')";
			break;

		case 7:
			/* Consulta los datos del vendedor por codigo Vnd_Cod */
			$sql = "SELECT 
					  puntos_imp.Pun_Des,
					  puntos_imp.Suc_Cod,
					  vendedor.Vnd_Cod,
					  vendedor.Prs_Cod
					FROM
					  puntos_imp
					  INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod)
					WHERE Prs_Cod='$Par_Sql[0]' AND Vnd_Est='A'";
			break;

		case 8:
			/* Consulta de persona por apellidos */
			$sql = "SELECT 
					  vendedor.Vnd_Cod,
					  vendedor.Pun_Cod,
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  puntos_imp.Pun_Des,
					  persona.Prs_Ape
					FROM
					  vendedor
					  INNER JOIN persona ON (vendedor.Prs_Cod = persona.Prs_Cod) 
					  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod) 
					WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND vendedor.Vnd_Est='A' ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			break;

		case 9:
			/* Consulta del personal por cedula */
			$sql = "SELECT 
					  vendedor.Vnd_Cod,
					  vendedor.Pun_Cod,
					  persona.Prs_Cod,
					  persona.Prs_Ced,
					  persona.Prs_Nom,
					  puntos_imp.Pun_Des,
					  persona.Prs_Ape
					FROM
					  vendedor
					  INNER JOIN persona ON (vendedor.Prs_Cod = persona.Prs_Cod)
					  INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod) 
					WHERE persona.Prs_Ced= '$Par_Sql[0]' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND vendedor.Vnd_Est='A' ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			break;

		case 10:
			/* Cambiamos el estado de todos los registro vendedor */
			$sql = "UPDATE vendedor SET Vnd_Est='$Par_Sql[0]' WHERE Prs_Cod='$Par_Sql[1]' AND Vnd_Est='A'";
			break;

		case 11:
			/* Modificamos el registro del vendedor */
			$sql = "UPDATE vendedor SET Pun_Cod='$Par_Sql[0]' , Vnd_Est='$Par_Sql[1]'  WHERE Prs_Cod='$Par_Sql[2]' AND Vnd_Cod='$Par_Sql[3]'";
			break;

		case 12: //Cargar trabajadores que pertenezcan a la sucursal y empresa 
			$sql = "SELECT 
				vendedor.Vnd_Cod,
				vendedor.Pun_Cod,
				persona.Prs_Cod,
				persona.Prs_Ced,
				persona.Prs_Nom,
				puntos_imp.Pun_Des,
				persona.Prs_Ape
			  FROM
				vendedor
				INNER JOIN persona ON (vendedor.Prs_Cod = persona.Prs_Cod) 
				INNER JOIN puntos_imp ON (vendedor.Pun_Cod = puntos_imp.Pun_Cod) 
			  WHERE persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND puntos_imp.Suc_Cod='$Par_Sql[1]' AND vendedor.Vnd_Est='A' ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
			break;

		case 13:
			if ($Par_Sql['op_opciones'] == "d") {
				$search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "Prs_Ced LIKE '$Par_Sql[search]%'";
			}

			if (isset($Par_Sql["limits"])) {
				$Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";

				$campos = "puntos_imp.Suc_Cod,puntos_imp.Suc_Cod,IFNULL(MAX(CASE WHEN puntos_imp.Pun_Des = 'Caja-Vendedores' THEN 'Caja-Vendedores' ELSE NULL END), 'false') AS Pun_Des,persona.Prs_Cod,Per_Cod,Ide_Des,Ciu_Des,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Prs_Sex,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,Prs_Esc,Prs_Fec,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Tit,IF(Per_Tit='Np','NO POSEE',IF(Per_Tit='Abg','ABOGADO/A',IF(Per_Tit='Bac','BACHILLER',IF(Per_Tit='Dr','DOCTOR/A',IF(Per_Tit='Eco','ECONOMISTA',IF(Per_Tit='Ing','INGENIERO/A',IF(Per_Tit='Lcd','LICENCIADO/A',''))))))) AS Per_Ti1,Per_Obs,IF(Per_Est='A','Activo','Inactivo') AS Per_Est,CURDATE() AS Fec_Sys";
				//$campos = "puntos_imp.Suc_Cod,puntos_imp.Pun_Des,persona.Prs_Cod,Per_Cod,Ide_Des,Ciu_Des,Prs_Ced,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado,Prs_Sex,IF(Prs_Sex='M','Masculino','Femenino') AS Prs_Gen,Prs_Esc,Prs_Fec,Prs_Cor,Prs_Dir,Prs_San,CONCAT(Prs_Nom,' ',Prs_Ape) AS personal,Per_Tit,IF(Per_Tit='Np','NO POSEE',IF(Per_Tit='Abg','ABOGADO/A',IF(Per_Tit='Bac','BACHILLER',IF(Per_Tit='Dr','DOCTOR/A',IF(Per_Tit='Eco','ECONOMISTA',IF(Per_Tit='Ing','INGENIERO/A',IF(Per_Tit='Lcd','LICENCIADO/A',''))))))) AS Per_Ti1,Per_Obs,IF(Per_Est='A','Activo','Inactivo') AS Per_Est,CURDATE() AS Fec_Sys";
			} else {
				$campos = "COUNT(Per_Cod) as total";
				$Par_Sql["limits"] = "";
			}
			$sql = "SELECT $campos FROM persona
                    INNER JOIN personal ON personal.Prs_Cod=persona.Prs_Cod
                    INNER JOIN identifica ON identifica.Ide_Cod=persona.Ide_Cod
                    LEFT JOIN ciudad ON ciudad.Ciu_Cod=persona.Ciu_Cod
					LEFT JOIN vendedor ON vendedor.Prs_Cod = persona.Prs_Cod
					LEFT JOIN puntos_imp ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
                    WHERE $search AND Per_Est='A' AND personal.Emp_Cod = $Par_Sql[Emp_Cod]  GROUP BY persona.Prs_Cod,puntos_imp.Suc_Cod     $Par_Sql[limits]    ";
			break;

		case 14:
			//Verifica si existe un vendedor registrado en una sucursal.
			$sql = "SELECT COUNT(*) AS Cant_Vent 
				FROM vendedor
				INNER JOIN puntos_imp ON vendedor.Pun_Cod = puntos_imp.Pun_Cod
				WHERE vendedor.Prs_Cod = $Par_Sql[0] AND puntos_imp.Suc_Cod = $Par_Sql[1] AND puntos_imp.Pun_Des='Caja-Vendedores'";
			break;

		case 15: //Registrar punto de impresion para vendedores
			$sql = "INSERT INTO puntos_imp(Suc_Cod,Pun_Des,Pun_Ubi) VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
			break;

		case 16:
			if ($Par_Sql['op_opciones'] == "d") {
				$search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "Prs_Ced LIKE '$Par_Sql[search]%'";
			}
			if (isset($Par_Sql["limits"])) {
				$Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
				$campos = "puntos_imp.Pun_Ubi,persona.Prs_Cod,vendedor.Vnd_Cod,vendedor.Pun_Cod,Prs_Est,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado, vendedor.Vnd_Est, sucursal.Suc_Des, puntos_imp.Pun_Des";
			} else {
				$campos = "COUNT(persona.Prs_Cod) as total";
				$Par_Sql["limits"] = "";
			}
			$sql = "SELECT $campos FROM persona
						INNER JOIN vendedor ON persona.Prs_Cod = vendedor.Prs_Cod
						INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod
						INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
						WHERE $search AND sucursal.Suc_Cod =$Par_Sql[Suc_Cod] AND sucursal.Emp_Cod = $Par_Sql[Emp_Cod]  AND puntos_imp.Pun_Des='Caja-Vendedores' $Par_Sql[limits]";
			break;

		case 17: /* Modificamos el registro del vendedor */
			$sql = "UPDATE puntos_imp SET Pun_Ubi='$Par_Sql[0]'  WHERE Suc_Cod='$Par_Sql[1]' AND Pun_Cod='$Par_Sql[2]' ";
			break;

		case 18:
			if ($Par_Sql['op_opciones'] == "d") {
				$search = "(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";
			} else {
				$search = "Prs_Ced LIKE '$Par_Sql[search]%'";
			}
			if (isset($Par_Sql["limits"])) {
				$Par_Sql["limits"] = "ORDER BY Prs_Ape $Par_Sql[limits]";
				$campos = "SUM(ventas_det.Vet_Imp) AS total_ventas ,COUNT(ventas.Vnd_Cod) AS cantidad_ventas,ventas.Vet_Sys,ventas.Vet_Cod , puntos_imp.Pun_Ubi,persona.Prs_Cod,vendedor.Vnd_Cod,vendedor.Pun_Cod,Prs_Est,Prs_Ced,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS empleado, vendedor.Vnd_Est, sucursal.Suc_Des, puntos_imp.Pun_Des";
			} else {
				$campos = "COUNT(persona.Prs_Cod) as total";
				$Par_Sql["limits"] = "";
			}

			$sql = "SELECT $campos FROM persona
						INNER JOIN vendedor ON persona.Prs_Cod = vendedor.Prs_Cod
						INNER JOIN puntos_imp ON puntos_imp.Pun_Cod=vendedor.Pun_Cod
						INNER JOIN sucursal ON sucursal.Suc_Cod=puntos_imp.Suc_Cod
						INNER JOIN ventas ON vendedor.Vnd_Cod = ventas.Vnd_Cod_Aux
						INNER JOIN ventas_det ON ventas.Vet_Cod = ventas_det.Vet_Cod
						WHERE $search
						AND sucursal.Emp_Cod = $Par_Sql[Emp_Cod] 
						AND puntos_imp.Pun_Des='Caja-Vendedores' 
						AND vendedor.Vnd_Est='A' AND (DATE(ventas.Vet_Sys) BETWEEN '$Par_Sql[ini]' AND '$Par_Sql[fin]') GROUP BY ventas.Vnd_Cod_Aux";
		break;
	}
	return $sql;
}
