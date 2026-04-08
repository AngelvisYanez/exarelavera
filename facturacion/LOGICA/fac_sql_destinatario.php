<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author Jose Cumbicos Ortiz
 * @version 1.0
 * Fecha de actualización:	2012-04-16
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */
function sentencias_tra($id,$Par_Sql)
{
	switch($id)
	{
		/**
		 * Buscar una persona especifica
		 * @param string $Par_Sql[0] cedula de la persona
		 * @param string $Par_Sql[1] ruc de la persona
		 */
		case 1:
			$sql= "SELECT Prs_Cod
			FROM persona
			WHERE (Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND Prs_Est='A'";
			return $sql;
		break;
		
		/**
		 * Cadena para obtener los datos de una persona
		 * @param string $Par_Sql[0] Codigo principal de la persona
		 */
		case 2:
			$sql = "SELECT Des_Cod FROM guia_destin, persona
			WHERE persona.Prs_Cod = guia_destin.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]' AND guia_destin.Emp_Cod = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Obtiene datos de una persona
		 * @param string $Par_Sql[0] cedula de la persona
		 * @param string $Par_Sql[1] ruc de la persona
		 */
		case 3:
			$sql= "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Cor, Prs_Ape, Prs_Sex, IF (persona.Prs_Sex='M','Masculino','Femenino') as sexo , Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel, identifica.Ide_Cod, Ide_Des FROM persona, identifica WHERE (Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND identifica.Ide_Cod=persona.Ide_Cod ";
			return $sql;
		break;
		
		/**
		 * Cadena que nos permitira saber que tipo de identificacion es la ingresada
		 * @param string $Par_Sql[0] numero de la identificacion eje 10
		 */
		case 4:
			$sql= "SELECT Ide_Cod, Ide_Des, Ide_Max, Ide_Raz
			FROM identifica
			WHERE Ide_Max = '$Par_Sql[0]'";
			return $sql;
		break;
		
		/**
		 * Cadena para obtener todas las ciudades
		 */
		case 5:
			$sql="SELECT Ciu_Cod, Ciu_Des FROM ciudad ORDER BY Ciu_Des";
			return $sql;
		break;
		
		/**
		 * cadena para obtener todas las zonas
		 */
		case 6:
			$sql= "SELECT Zon_Cod, Zon_Des FROM zonas WHERE Zon_Est='A'";
			return $sql;
		break;
		
		/**
		 * Insertado de datos en la tabla persona
		 */
		case 7:
			$sql= "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Dir, Ide_Cod,Prs_Cor) VALUES (Trim('$Par_Sql[0]'), Trim(UPPER('$Par_Sql[1]')),Trim(UPPER('$Par_Sql[2]')),UPPER('$Par_Sql[3]'),Trim(UPPER('$Par_Sql[4]')),Trim(UPPER('$Par_Sql[5]')))";
			//echo $sql."<br>";
			return $sql;
		break;
		
		/**
		 * Insertado de datos en transporte
		 */
		case 8:
			$sql= "INSERT INTO guia_destin (Prs_Cod, Emp_Cod, Des_Sri, Des_Adu) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]')";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * busqueda de cliente segun apellido y filtrado por empresa
		 */
		case 9:
			$sql = "SELECT guia_destin.Prs_Cod, guia_destin.Des_Cod, guia_destin.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
					FROM guia_destin
					INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)
					WHERE (guia_destin.Emp_Cod = '$Par_Sql[0]') AND persona.Prs_Ape LIKE '%$Par_Sql[1]%';";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * busqueda de cliente por cedula y filtrado por empresa
		 */
		case 10:
			$sql = "SELECT guia_destin.Prs_Cod, guia_destin.Des_Cod, guia_destin.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
			FROM guia_destin
			INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)
			WHERE (guia_destin.Emp_Cod = '$Par_Sql[0]') AND persona.Prs_Ced = '$Par_Sql[1]';";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * obtener datos de cliente - persona
		 */
		case 11:
		//, cliente.Zon_Cod
			$sql ="SELECT guia_destin.Prs_Cod, Des_Cod,Des_Sri,Des_Adu, guia_destin.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir, persona.Prs_Sex, persona.Prs_Tel,persona.Prs_Cor, persona.Prs_Te2, persona.Prs_Cel
    			  FROM 
				  guia_destin
    			  INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)     			 
    			  WHERE (guia_destin.Des_Cod = '$Par_Sql[0]');";
				  //echo $sql;
			return $sql;
		break;
		
		/**
		 * actualizado de datos de persona
		 */
		case 12:
			$sql="UPDATE persona SET Prs_Ced='$Par_Sql[0]', Prs_Nom=Trim(UPPER('$Par_Sql[1]')), Prs_Ape=Trim(UPPER('$Par_Sql[2]')), Prs_Dir=Trim(UPPER('$Par_Sql[3]')),Prs_Cor='$Par_Sql[4]' WHERE Prs_Cod = '$Par_Sql[5]'";
			return $sql;
		break;
		
		case 13:
			$sql = "UPDATE guia_destin SET Prs_Cod='$Par_Sql[0]', Emp_Cod='$Par_Sql[1]', Des_Sri='$Par_Sql[2]', Des_Adu='$Par_Sql[3]' WHERE Des_Cod='$Par_Sql[4]'";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * obtener datos de Destinatario - persona
		 */
		case 14:
			$sql ="SELECT guia_destin.Prs_Cod, Tra_Cod, guia_destin.Emp_Cod, persona.Prs_Ced,persona.Prs_Cor, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Dir
			FROM guia_destin
			INNER JOIN persona ON (guia_destin.Prs_Cod = persona.Prs_Cod)				
			WHERE (Des_Cod = '$Par_Sql[0]');";
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
				return $sql;
				break;
					
				/**
				 * Consulta los datos del usuario
				 */
			case 23:
				$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				return $sql;
				break;
	}
}
?>