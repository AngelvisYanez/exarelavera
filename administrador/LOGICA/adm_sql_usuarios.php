<?php
/**
 * Retorna consulta sql a ejecutarse
 * 
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-18
 * 
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 * 
 * @package administrador.LOGICA
 */
	function sentencias_admu($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			 * Busqueda en persona - Estudiante
			 * por apellido
			 */
			case 1:
				$sql = "SELECT persona.Prs_Cod, Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, estudiante.Est_Est AS Estado, estudiante.Emp_Cod
						FROM
    						estudiante
    					INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod)
						WHERE (estudiante.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ape LIKE '%$Par_Sql[1]%');";
				return $sql;
			break;
			
			/**
			 * Busqueda en persona - cliente
			 * por apellido
			 */
			case 2:
				$sql = "SELECT persona.Prs_Cod, cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, cliente.Cli_Est AS Estado, cliente.Emp_Cod
						FROM cliente 
						INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
						WHERE (cliente.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ape LIKE '%$Par_Sql[1]%');";
				return $sql;
			break;
			
			/**
			 * Busqueda en proveedor - persona
			 * por apellido
			 */
			case 3:
				$sql = "SELECT persona.Prs_Cod, proveedore.Prv_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, proveedore.Prv_Est AS Estado, proveedore.Emp_Cod
						FROM proveedore
    					INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
						WHERE (proveedore.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ape LIKE '%$Par_Sql[1]%');";
				return $sql;
			break;
			
			/**
			 * Busqueda en personal - persona
			 * por apellido
			 */
			case 4:
				$sql = "SELECT persona.Prs_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, personal.Per_Est AS Estado, personal.Emp_Cod
						FROM personal
						INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
						WHERE (personal.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ape LIKE '%$Par_Sql[1]%');";
				return $sql;
			break;
			
			/**
			 * Busqueda en persona - Estudiante
			 * por cedula
			 */
			case 5:
				$sql = "SELECT persona.Prs_Cod, Est_Int, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, estudiante.Est_Est AS Estado, estudiante.Emp_Cod
				FROM
				estudiante
				INNER JOIN persona ON (estudiante.Prs_Cod = persona.Prs_Cod)
				WHERE (estudiante.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ced = '$Par_Sql[1]');";
				return $sql;
			break;
					
			/**
			 * Busqueda en persona - cliente
			 * por cedula
			 */
			case 6:
				$sql = "SELECT persona.Prs_Cod, cliente.Cli_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, cliente.Cli_Est AS Estado, cliente.Emp_Cod
				FROM cliente
				INNER JOIN persona ON (cliente.Prs_Cod = persona.Prs_Cod)
				WHERE (cliente.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ced = '$Par_Sql[1]');";
				return $sql;
			break;
					
			/**
			 * Busqueda en proveedor - persona
			 * por cedula
			 */
			case 7:
				$sql = "SELECT persona.Prs_Cod, proveedore.Prv_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, proveedore.Prv_Est AS Estado, proveedore.Emp_Cod
				FROM proveedore
				INNER JOIN persona ON (proveedore.Prs_Cod = persona.Prs_Cod)
				WHERE (proveedore.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ced = '$Par_Sql[1]');";
				return $sql;
			break;
					
			/**
			 * Busqueda en personal - persona
			 * por cedula
			 */
			case 8:
				$sql = "SELECT persona.Prs_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, personal.Per_Est AS Estado, personal.Emp_Cod
				FROM personal
				INNER JOIN persona ON (personal.Prs_Cod = persona.Prs_Cod)
				WHERE (personal.Emp_Cod = '$Par_Sql[0]' AND persona.Prs_Ced = '$Par_Sql[1]');";
				return $sql;
			break;
			
			/**
			 * busqueda de sucursales segun empresa
			 */
			case 9: 
				$sql = "SELECT Suc_Cod, Suc_Des FROM sucursal WHERE Emp_Cod = '$Par_Sql[0]' ORDER BY Suc_Des";
				return $sql;
			break;
			
			/**
			 * obtener datos de una persona
			 */
			case 10:
				$sql = "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex
						FROM persona
						WHERE (Prs_Cod = '$Par_Sql[0]');";
				return $sql;
			break;
			
			/**
			 * Obtener los perfiles de usuarios
			 */
			case 11:
				$sql="SELECT perfiles.Per_Cod, perfiles.Per_Des, perfiles.Per_Est FROM perfiles WHERE perfiles.Emp_Cod = $Par_Sql[0] ORDER BY perfiles.Per_Des";
				return 	$sql;
			break;
			
			/**
			 * Insertado de usuario
			 */
			case 12:
				$sql="INSERT INTO usuarios (Prs_Cod,Suc_Cod,Usu_Ced,Usu_Pal,Usu_Tip,Usu_Est,Usu_Cad)
					  VALUES('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]',md5('$Par_Sql[3]'),'$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]');";
				return $sql;
			break;
			
			/**
			 * Insertado de perfiles
			 */
			case 13:
				$sql="INSERT INTO usuarperfi(`Usu_Cod`,`Per_Cod`)VALUES ($Par_Sql[0], $Par_Sql[1])";
				return $sql;
			break;
			
			/**
			 * busqueda de usuarios por apellido
			 */
			case 14:
				$sql="SELECT usuarios.Usu_Cod,persona.Prs_Cod,persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,persona.Prs_Est,usuarios.Usu_Est, usuarios.Usu_Ced,sucursal.Suc_Des,sucursal.Suc_Cod
				FROM persona
				INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)
				INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
				WHERE sucursal.Emp_Cod = $Par_Sql[0] AND Prs_Ape LIKE  '%$Par_Sql[1]%' ORDER BY Prs_Ape, Prs_Nom ASC";
				return $sql;
			break;
			
			/**
			 * busqueda de usuarios por cedula
			 */
			case 15:
				$sql="SELECT usuarios.Usu_Cod,persona.Prs_Cod,persona.Prs_Ced,persona.Prs_Nom,persona.Prs_Ape,persona.Prs_Est,usuarios.Usu_Est,sucursal.Suc_Des,sucursal.Suc_Cod
				FROM persona
				INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)
				INNER JOIN sucursal ON (usuarios.Suc_Cod = sucursal.Suc_Cod)
				WHERE sucursal.Emp_Cod = $Par_Sql[0] AND
				Prs_Ced = '$Par_Sql[1]' ORDER BY Prs_Ape, Prs_Nom ASC";
				return $sql;
			break;
			
			/**
			 * Busqueda de una persona por usuario
			 */
			case 16:
				$sql="SELECT usuarios.Usu_Cod, usuarios.Usu_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Est, usuarios.Suc_Cod, usuarios.Usu_Est, sucursal.Suc_Des, usuarios.Usu_Cad FROM persona, usuarios, sucursal WHERE persona.Prs_Cod=usuarios.Prs_Cod AND usuarios.Suc_Cod = sucursal.Suc_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				return $sql;
			break;
			
			/**
			 * Consultar los perfiles de usuarios
			 */
			case 17:
				$sql="SELECT usuarperfi.Per_Cod, concat(usuarperfi.Per_Cod,perfiles.Per_Est) as Per_Est2, perfiles.Per_Est, perfiles.Per_Des FROM usuarperfi, perfiles WHERE usuarperfi.Per_Cod = perfiles.Per_Cod AND usuarperfi.Usu_Cod=(SELECT Usu_Cod FROM usuarios WHERE Usu_Cod=$Par_Sql[0])";
				return $sql;
			break;
			
			/**
			 * eliminar los perfiles registrados por el usuario
			 */
			case 18:
				$sql="DELETE FROM usuarperfi WHERE Usu_Cod=$Par_Sql[0]";
				return $sql;
			break;
			
			/**
			 * Actualizar usuarios
			 */
			case 19:
				$sql="UPDATE usuarios SET Usu_Ced = '$Par_Sql[0]', Usu_Cad = '$Par_Sql[2]'  WHERE Usu_Cod =$Par_Sql[1]";
				return $sql;
			break;
			
			/**
			 * Actualizar usuarios
			 */
			case 20:
				$sql="UPDATE usuarios SET Usu_Pal='".md5($Par_Sql[0])."', Usu_Ced = '$Par_Sql[1]', Usu_Cad = '$Par_Sql[3]' WHERE Usu_Cod = $Par_Sql[2]";
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
			 * Verificar que no haya mas usuarios en esta sucursal
			 */
			case 24:
				$sql = "SELECT COUNT(`usuarios`.`Usu_Ced`) AS 'count' FROM `usuarios` INNER JOIN `sucursal` ON `usuarios`.`Suc_Cod` = `sucursal`.`Suc_Cod` INNER JOIN `empresas` ON `sucursal`.`Emp_Cod` = `empresas`.`Emp_Cod`
				WHERE `usuarios`.`Usu_Ced` = '$Par_Sql[0]' AND `empresas`.`Emp_Cod` = $Par_Sql[1]";
				return $sql;
			break;
			
			/**
			 * Contar el numero de sucursales de la empresa 
			 */
			case 25:
				$sql = "SELECT COUNT(Suc_Cod) AS 'count' FROM sucursal WHERE Emp_Cod = '$Par_Sql[0]'";
				return $sql;
			break;
			
			/**
			 * Obtener las sucursales que el usuario no tiene usuario
			 */
			case 26:
				$sql = "SELECT Suc_Cod, Suc_Des FROM sucursal 
				WHERE Emp_Cod = '$Par_Sql[0]' AND Suc_Cod NOT IN 
				(SELECT `usuarios`.`Suc_Cod` FROM `usuarios` INNER JOIN `sucursal` ON `usuarios`.`Suc_Cod` = `sucursal`.`Suc_Cod` INNER JOIN `empresas` ON `sucursal`.`Emp_Cod` = `empresas`.`Emp_Cod`WHERE `usuarios`.`Usu_Ced` = '$Par_Sql[1]' AND `empresas`.`Emp_Cod` = '$Par_Sql[0]')
				ORDER BY Suc_Des";
				return $sql;
			break;
			
			/**
			 * Busqueda de usuarios dependiendo del codigo 
			 */
			case 27:
				$sql = "SELECT usuarios.Usu_Cod, persona.Prs_Ced as Usu_Ced, persona.Prs_Ape, persona.Prs_Nom, persona.Prs_Est, usuarios.Suc_Cod, usuarios.Usu_Est, sucursal.Suc_Des, usuarios.Usu_Cad FROM persona, usuarios, sucursal WHERE persona.Prs_Cod=usuarios.Prs_Cod AND usuarios.Suc_Cod = sucursal.Suc_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				return $sql;
			break;
			
			/**
			 * Actualiza es estado del usuario 
			 */
			case 28:
			$sql = "UPDATE usuarios SET Usu_Est='$Par_Sql[0]' WHERE Usu_Cod = $Par_Sql[1]";
			return $sql;
			break;
			
			/**
			 * Permite seleccionar el tipo de menu del usuario 
			 */
			case 29:
			$sql="SELECT Usu_Men FROM usuarios WHERE usuarios.Usu_Cod = $Par_Sql[0]";  
			//echo $sql."<br>";
			return $sql;
			break; 
			
			/**
			 * Actualiza el tipo de menu del usuario 
			 */  
			case 30:
			$sql="UPDATE usuarios SET Usu_Men='$Par_Sql[0]' WHERE Usu_Cod = $Par_Sql[1]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 31:
			/* Consulta los organizado del nivel 0 */
			$sql = "(SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv FROM organizado WHERE organizado.Org_Cod IN 
(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[0]."))) ORDER BY organizado.Org_Ord) 
 UNION DISTINCT
 (SELECT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, 
organizado.Org_Ime FROM organizado WHERE organizado.Org_Cod IN  
(SELECT organizado.Org_Niv FROM procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON
 (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[0].")) AND organizado.Org_Niv = 0 ORDER BY organizado.Org_Ord)"; 
			//echo $sql; 
			return $sql;
			break;
			
			case 32:
			/* Consulta los organizados del arbol del siguiente nivel 1 */
			$sql = "SELECT DISTINCT organizado.Org_Det, organizado.Org_Ord,  organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, organizado.Org_Ime  FROM organizado	WHERE organizado.Org_Cod IN (SELECT organizado.Org_Niv	FROM  procesos  INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod) INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod) WHERE (".$Par_Sql[0].")) AND organizado.Org_Niv = $Par_Sql[0]";
			//echo $sql."<br>";
			return $sql;
			break;
			
			case 33:
			/* Consulta los organizados del arbol del siguiente nivel 2 */
			$sql ="SELECT DISTINCT organizado.Org_Det, organizado.Org_Ord, organizado.Org_Des, organizado.Org_Niv, organizado.Org_Cod, organizado.Org_Img, organizado.Org_Ime	FROM  procesos INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)  INNER JOIN organizado ON (procesos.Org_Cod = organizado.Org_Cod)	WHERE  organizado.Org_Niv = $Par_Sql[0]";// (".$Par_Sql[0].") AND
			//echo $sql."<br>";
			return $sql;
			break;	
			
			case 34:
			/* Consulta los procesos del arbol */
			$sql = "SELECT DISTINCT procesos.Pcs_Lin, rutas.Rut_Des, procesos.Pcs_Nom, procesos.Pcs_Img, procesos.Pcs_Det FROM rutas INNER JOIN procesos ON (rutas.Rut_Cod = procesos.Rut_Cod) INNER JOIN perfiorgan ON (procesos.Pcs_Cod = perfiorgan.Pcs_Cod)	WHERE procesos.Pcs_Est='A' AND procesos.Pcs_Tip = '$Par_Sql[2]' AND procesos.Org_Cod=$Par_Sql[0] AND (".$Par_Sql[1].") ORDER BY procesos.Pcs_Ord";
			//echo $sql;
			return $sql;
			break;

			case 35:
			/**
			* Consulta la base de datos de la empresa
			*/
			$sql = "SELECT 
  `data`.Dat_Cod
FROM
  `data`
WHERE data.Emp_Cod = $Par_Sql[0]";
			return $sql;
			break;
			
			case 36:
			/**
			* Inserta el usuario en la tabla master
			*/
			$sql = "INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr) VALUES ($Par_Sql[0], $Par_Sql[1], '$Par_Sql[2]')";
			return $sql;
			break;			

		    case 206:
		    $sql="UPDATE usuarios SET Usu_Pal='".md5($Par_Sql[0])."' WHERE Usu_Cod =$Par_Sql[1]";
		    return $sql;
		    break;
			
			
		}
	}
?>