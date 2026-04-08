<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 1.0
 * Fecha de actualización:	2012-04-16
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package tesoreria.LOGICA
 */


function sentencias_pac($id,$Par_Sql)
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
			$sql = "SELECT Cli_Cod FROM cliente, persona
			WHERE persona.Prs_Cod = cliente.Prs_Cod AND persona.Prs_Cod = '$Par_Sql[0]' AND cliente.Emp_Cod = '$Par_Sql[1]'";
			return $sql;
		break;
		
		/**
		 * Obtiene datos de una persona
		 * @param string $Par_Sql[0] cedula de la persona
		 * @param string $Par_Sql[1] ruc de la persona
		 */
		case 3:
			$sql= "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Cor, Prs_Ape, Prs_Sex, IF (persona.Prs_Sex='M','Masculino','Femenino') as sexo , Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel, ciudad.Ciu_Cod, identifica.Ide_Cod, Ide_Des, ciudad.Ciu_Des FROM persona, identifica, ciudad WHERE (Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND identifica.Ide_Cod=persona.Ide_Cod AND ciudad.Ciu_Cod=persona.Ciu_Cod ";
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
			$sql= "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, Prs_Dir, Prs_Tel, Prs_Te2, Prs_Cel, Ciu_Cod, Ide_Cod,Prs_Cor) VALUES (Trim('$Par_Sql[0]'), Trim(UPPER('$Par_Sql[1]')),Trim(UPPER('$Par_Sql[2]')),UPPER('$Par_Sql[3]'),Trim(UPPER('$Par_Sql[4]')),Trim(UPPER('$Par_Sql[5]')),Trim('$Par_Sql[6]'), Trim('$Par_Sql[7]'), '$Par_Sql[8]','$Par_Sql[9]','$Par_Sql[10]')";
			//echo $sql."<br>";
			return $sql;
		break;
		
		/**
		 * Insertado de datos en cliente
		 */
		case 8:
			$sql= "INSERT INTO cliente (Prs_Cod, Cli_Cup, Cli_Ruf, Cli_Fac, Emp_Cod, Cli_Tic,Cli_Tip,Cli_Con) VALUES ($Par_Sql[0], '$Par_Sql[2]', '$Par_Sql[3]', Trim(UPPER('$Par_Sql[4]')), '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]')";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * busqueda de cliente segun apellido y filtrado por empresa
		 */
		case 9:
			$sql = "SELECT cliente.Prs_Cod, Cli_Cod, cliente.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
					FROM cliente
					INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
					WHERE (cliente.Emp_Cod = '$Par_Sql[0]') AND persona.Prs_Ape LIKE '%$Par_Sql[1]%';";
			return $sql;
		break;
		
		/**
		 * busqueda de cliente por cedula y filtrado por empresa
		 */
		case 10:
			$sql = "SELECT cliente.Prs_Cod, Cli_Cod, cliente.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape
			FROM cliente
			INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
			WHERE (cliente.Emp_Cod = '$Par_Sql[0]') AND persona.Prs_Ced = '$Par_Sql[1]';";
			return $sql;
		break;
		
		/**
		 * obtener datos de cliente - persona
		 */
		case 11:
		//, cliente.Zon_Cod
			$sql ="SELECT cliente.Cli_Tip,cliente.Prs_Cod, Cli_Cod, cliente.Emp_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, cliente.Cli_Cup, cliente.Cli_Ruf,cliente.Cli_Fac,cliente.Cli_Con, persona.Prs_Dir, persona.Prs_Sex, persona.Prs_Tel,persona.Prs_Cor, persona.Prs_Te2, persona.Prs_Cel, cliente.Cli_Tic, persona.Ciu_Cod, ciudad.Ciu_Des,Ide_Cod
    			  FROM cliente
    			  INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod) 
    			  INNER JOIN ciudad ON (ciudad.Ciu_Cod = persona.Ciu_Cod)
    			  WHERE (Cli_Cod = '$Par_Sql[0]');";
				  //echo $sql;
			return $sql;
		break;
		
		/**
		 * actualizado de datos de persona
		 */
		case 12:
			$sql="UPDATE persona SET Prs_Ced='$Par_Sql[0]', Prs_Nom=Trim(UPPER('$Par_Sql[1]')), Prs_Ape=Trim(UPPER('$Par_Sql[2]')), Prs_Sex='$Par_Sql[3]',Prs_Dir=Trim(UPPER('$Par_Sql[4]')), Prs_Tel='$Par_Sql[5]', Prs_Te2='$Par_Sql[6]', Prs_Cel='$Par_Sql[7]', Ciu_Cod='$Par_Sql[8]', Ide_Cod='$Par_Sql[9]'".(!empty($Par_Sql[10])?",Prs_Cor='$Par_Sql[10]'":'')." WHERE Prs_Cod = '$Par_Sql[11]'";
			return $sql;
		break;
		
		case 13:
			$sql = "UPDATE cliente SET Cli_Cup='$Par_Sql[1]', Cli_Ruf='$Par_Sql[2]', Cli_Fac=Trim(UPPER('$Par_Sql[3]')), Emp_Cod='$Par_Sql[4]', Cli_Tic='$Par_Sql[5]', Cli_Tip='$Par_Sql[7]',Cli_Con='$Par_Sql[8]' WHERE Cli_Cod='$Par_Sql[6]' ";
			//echo $sql;
			return $sql;
		break;
		
		/**
		 * obtener datos de cliente - persona
		 */
		case 14:
			$sql ="SELECT cliente.Cli_Tip,cliente.Prs_Cod, Cli_Cod, cliente.Emp_Cod, persona.Prs_Ced,persona.Prs_Cor, persona.Prs_Nom, persona.Prs_Ape, cliente.Cli_Cup, cliente.Cli_Ruf
			, cliente.Cli_Fac, persona.Prs_Dir, persona.Prs_Sex, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, cliente.Cli_Tic,cliente.Cli_Con, persona.Ciu_Cod, ciudad.Ciu_Des
			FROM cliente
			INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
			INNER JOIN ciudad ON (ciudad.Ciu_Cod = persona.Ciu_Cod)			
			WHERE (Cli_Cod = '$Par_Sql[0]');";
			return $sql;
			break;
		
		case 15://Select para obtener el listado de las ciudades
			$sql = "SELECT Ciu_Cod,Ciu_Des,Pro_Nom,Pas_Nom
					FROM ciudad
					INNER JOIN provincia ON provincia.Pro_Cod=ciudad.Pro_Cod
					INNER JOIN pais ON pais.Pas_Cod=ciudad.Pas_Cod 
					WHERE Ciu_Est='A' AND Ciu_Des IS NOT NULL";
			return $sql;
			break;
		case 16://Select para obtener la lista de identificaciones
			$sql="SELECT *,IF(ISNULL(Ide_Pre),'Ec','Ex') AS Tipo FROM identifica WHERE Ide_Est='A'";
			return $sql;
			break;
		case 17://Select para obtener los datos de una persona según su cédula
			$sql="SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
			return $sql;
			break;
		case 18://Select para comprobar si el cliente ya se encuentra registrado
			$sql="SELECT Pac_Cod FROM paciente WHERE Prs_Cod='$Par_Sql[0]' AND Emp_Cod='$Par_Sql[1]'";
			return $sql;
			break;
		case 19://Insert en la tabla persona
			$sql="INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod,Prs_Tel,Prs_Te2,Prs_Cel) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod],'$Par_Sql[Prs_Tel]','$Par_Sql[Prs_Te2]','$Par_Sql[Prs_Cel]');";
			return $sql;
			break;
		case 20://Insert en la paciente
			$sql="INSERT INTO paciente(Prs_Cod,Emp_Cod,Pac_Emp,Pac_Fna,Pac_Dir,Pac_Cor) VALUES('$Par_Sql[Prs_Cod]','$Par_Sql[Emp_Cod]','$Par_Sql[Pac_Emp]','$Par_Sql[Pac_Fna]','$Par_Sql[Prs_Dir]',".(empty($Par_Sql['Pac_Cor'])?'NULL':"'$Par_Sql[Pac_Cor]'").");";
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

							/**
				* actualizado de datos de persona
				*/
		case 24:
			$sql="UPDATE persona SET Prs_Dir=Trim(UPPER('$Par_Sql[0]')), Prs_Tel='$Par_Sql[1]', Prs_Te2='$Par_Sql[2]', Prs_Cel='$Par_Sql[3]', Ciu_Cod='$Par_Sql[4]', Prs_Cor='$Par_Sql[5]' WHERE Prs_Cod = '$Par_Sql[6]'";
			return $sql;
		break;
		case 25:
			$sql= "SELECT Ide_Cod, Ide_Des, Ide_Max, Ide_Raz
					FROM identifica WHERE Ide_Est='A'";
			return $sql;
			break;
		
		case 26://Update sobre la tabla cliente
			$sql = "UPDATE paciente SET Prs_Cod='$Par_Sql[0]', Pac_Emp='$Par_Sql[1]', Pac_Fna='$Par_Sql[2]', Pac_Cor=".(!empty($Par_Sql[4])?"'$Par_Sql[4]'":'NULL')." WHERE Pac_Cod='$Par_Sql[3]'";
			//ChromePhp::log($sql);
			return $sql;
			break;
		
		case 27://Select para listar los clientes registrados en la empresa
			if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
			else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
			if(isset($Par_Sql["limits"])){
					$Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
					$campos="Pac_Cod,paciente.Prs_Cod,Prs_Ced,Prs_Ape,paciente.Pac_Emp,paciente.Pac_Fna,persona.Ide_Cod,Ide_Sri,Prs_Ape,Prs_Nom,CONCAT(Prs_Ape,' ',Prs_Nom) AS paciente,Prs_Sex,Ciu_Cod,Prs_Dir,Prs_Tel,Prs_Te2,Prs_Cel,IF(Pac_Cor IS NULL OR TRIM(Pac_Cor)='',Prs_Cor,Pac_Cor)AS Prs_Cor";
			}
			else{$campos="COUNT(Pac_Cod) as total";$Par_Sql["limits"]="";}
			$sql = "SELECT $campos FROM paciente
					INNER JOIN persona ON paciente.Prs_Cod=persona.Prs_Cod
					INNER JOIN identifica ON persona.Ide_Cod=identifica.Ide_Cod
					WHERE $search AND Pac_Est='A' AND paciente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
			return $sql;
        break;
		
		case 28:
            if($Par_Sql['op_opciones']=="d") {$search="(persona.Prs_Ape LIKE '%$Par_Sql[search]%' OR persona.Prs_Nom)";}
			else {$search="persona.Prs_Ced LIKE '$Par_Sql[search]%'";}
                        if($Par_Sql['est_opciones']=="a"){$estado="paciente.Pac_Est = 'A'";}
                        else {$estado="paciente.Pac_Est = 'I'";}
                        $campos=empty($Par_Sql['limits'])?" COUNT(paciente.Pac_Cod) AS total":"paciente.Prs_Cod, Pac_Cod, paciente.Emp_Cod, persona.Prs_Ced, persona.Prs_Tel, persona.Prs_Cor, persona.Prs_Dir, persona.Prs_Nom, persona.Prs_Ape, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) AS paciente";
			$sql = "SELECT $campos
					FROM paciente
					INNER JOIN persona ON (paciente.Prs_Cod = persona.Prs_Cod)
					WHERE (paciente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]') AND $search AND $estado $Par_Sql[limits];";
			return $sql;
		break;
		case 29://Insert en la tabla caja_cliente
			$sql="INSERT INTO caja_clien(Cli_Cod) VALUES('$Par_Sql[Cli_Cod]');";
			return $sql;
			break;
	}
}
?>