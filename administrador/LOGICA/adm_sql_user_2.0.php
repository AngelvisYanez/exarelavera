<?php
/**
 * Retorna consulta sql a ejecutarse
 * Asael Tello 24-08-2017
 */

function sentencias_cli($id,$Par_Sql) {
	switch($id) {
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
			$sql="UPDATE persona SET Prs_Ced='$Par_Sql[0]', Prs_Nom=Trim(UPPER('$Par_Sql[1]')), Prs_Ape=Trim(UPPER('$Par_Sql[2]')), Prs_Sex='$Par_Sql[3]',Prs_Dir=Trim(UPPER('$Par_Sql[4]')), Prs_Tel='$Par_Sql[5]', Prs_Te2='$Par_Sql[6]', Prs_Cel='$Par_Sql[7]', Ciu_Cod='$Par_Sql[8]', Ide_Cod='$Par_Sql[9]',Prs_Cor='$Par_Sql[10]' WHERE Prs_Cod = '$Par_Sql[11]'";
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
		case 17://Select para obtener los datos de una persona seg�n su c�dula
			$sql="SELECT persona.* FROM persona WHERE Prs_Ced LIKE '$Par_Sql[0]%'";
			return $sql;
			break;
		case 18://Select para comprobar si el usuario ya se encuentra registrado
			$sql="SELECT usuarios.* FROM usuarios WHERE Prs_Cod='$Par_Sql[0]' AND Suc_Cod = $Par_Sql[1]";
			return $sql;
			break;
		case 19://Insert en la tabla persona
			$sql="INSERT INTO persona(Prs_Ced,Prs_Ape,Prs_Nom,Prs_Dir,Prs_Cor,Prs_Sex,Ciu_Cod,Ide_Cod,Prs_Tel,Prs_Te2,Prs_Cel) VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Ape]','$Par_Sql[Prs_Nom]','$Par_Sql[Prs_Dir]','$Par_Sql[Prs_Cor]','$Par_Sql[Prs_Sex]',$Par_Sql[Ciu_Cod],$Par_Sql[Ide_Cod],'$Par_Sql[Prs_Tel]','$Par_Sql[Prs_Te2]','$Par_Sql[Prs_Cel]');";
			return $sql;
			break;
		case 20://Insert en la tabla cliente
			$sql="INSERT INTO cliente(Prs_Cod,Emp_Cod,Cli_Tic,Cli_Con) VALUES('$Par_Sql[Prs_Cod]','$Par_Sql[Emp_Cod]','$Par_Sql[Cli_Tic]','$Par_Sql[Cli_Con]');";
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
			$sql = "UPDATE cliente SET Prs_Cod='$Par_Sql[0]', Cli_Tic='$Par_Sql[1]', Cli_Con='$Par_Sql[2]' WHERE Cli_Cod='$Par_Sql[3]'";
			return $sql;
			break;

		case 27://Select para listar los clientes registrados en la empresa
			if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
			else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
			if(isset($Par_Sql["limits"])){
					$Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
					$campos="Cli_Cod,cliente.Prs_Cod,Prs_Ced,Prs_Ape,Cli_Con,persona.Ide_Cod,Ide_Sri,Prs_Ape,Prs_Nom,Cli_Tic,CONCAT(Prs_Ape,' ',Prs_Nom) AS cliente,Prs_Sex,Ciu_Cod,Prs_Dir,Prs_Tel,Prs_Te2,Prs_Cel,Prs_Cor";
			}
			else{$campos="COUNT(Cli_Cod) as total";$Par_Sql["limits"]="";}
			$sql = "SELECT $campos FROM cliente
					INNER JOIN persona ON cliente.Prs_Cod=persona.Prs_Cod
					INNER JOIN identifica ON persona.Ide_Cod=identifica.Ide_Cod
					WHERE $search AND Cli_Est='A' AND cliente.Emp_Cod=$Par_Sql[Emp_Cod] $Par_Sql[limits]";
			return $sql;
                break;

                case 28://  Get Perfiles by Empresa
                    $sql = "SELECT Per_Cod, Per_Des FROM perfiles WHERE Emp_Cod = $Par_Sql[0] AND Per_Est='A'";
                    return $sql;
                break;

                case 29://  Insert Persona
                    $sql = "INSERT INTO persona (Prs_Ced, Prs_Nom, Prs_Ape, Ciu_Cod, Prs_Sex, Prs_Est) "
                        .  "VALUES('$Par_Sql[Prs_Ced]','$Par_Sql[Prs_Nom]', '$Par_Sql[Prs_Ape]', $Par_Sql[Ciu_Cod], '$Par_Sql[Prs_Sex]', 'A')";
                    return $sql;
                break;

                case 30://  Insert Usuario
                    $contra="";
                    if(isset($Par_Sql['Usu_Pal']))
                        $contra="md5('$Par_Sql[Usu_Pal]')";
                    if(isset($Par_Sql['Usu_Pal_C']))
                        $contra="'$Par_Sql[Usu_Pal_C]'";
                    $sql = "INSERT INTO  usuarios (Prs_Cod, Suc_Cod, Usu_Ced, Usu_Pal, Usu_Cad) "
                        .  "VALUES($Par_Sql[Prs_Cod],$Par_Sql[Suc_Cod], '$Par_Sql[Usu_Ced]', $contra , 'N')";
                    return $sql;
                break;

                case 31://  Insert detalle usuario perfil
                    $sql = "INSERT INTO  usuarperfi(Usu_Cod, Per_Cod) "
                        .  "VALUES($Par_Sql[Usu_Cod], $Par_Sql[Per_Cod])";
                    return $sql;
                break;

                case 32://  Select Usu_Cod by prs_cod & suc_cod
                    $sql = "SELECT Usu_Cod FROM usuarios WHERE Prs_Cod = $Par_Sql[Prs_Cod] AND Suc_Cod = $Par_Sql[Suc_Cod]";
                    return $sql;
                break;

                case 33://  Select Prs_Cod by cedula
                    $sql = "SELECT Prs_Cod FROM persona WHERE Prs_Ced = '$Par_Sql[0]'";
                    return $sql;
                break;

                case 34://  Get number of points if there's a point created
                    $sql = "SELECT COUNT(Pun_Cod) as punto FROM puntos_imp WHERE Pun_Des = '$Par_Sql[Pun_Des]' AND Suc_Cod = $Par_Sql[Suc_Cod]";
                    return $sql;
                break;

                case 35://  Insert punto-impresion
                    $sql = "INSERT INTO puntos_imp(Suc_Cod, Pun_Des, Pun_Est) "
                        .  "VALUES($Par_Sql[Suc_Cod], '$Par_Sql[Pun_Des]', 'A')";
                    return $sql;
                break;

                case 36://  Insert vendedor
                    $sql = "INSERT INTO vendedor(Pun_Cod, Prs_Cod, Vnd_Est) "
                        .  "VALUES($Par_Sql[Pun_Cod], $Par_Sql[Prs_Cod], 'A')";
                    return $sql;
                break;

                case 37://  Select Pun_Cod by Suc_Cod and Pun_Des
                    $sql = "SELECT Pun_Cod FROM puntos_imp WHERE Pun_Des = '$Par_Sql[Pun_Des]' AND Suc_Cod = $Par_Sql[Suc_Cod]";
                    return $sql;
                break;

                case 38://  Select User by Cedula and Apellido
                    $sql = "SELECT persona.Prs_Cod, usuarios.Usu_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as persona, usuarios.Suc_Cod, persona.Prs_Cod, pu.Pun_Cod, pu.Pun_Des as Pun_Des_m, GROUP_CONCAT(DISTINCT mp.Pla_Nom SEPARATOR ', ') as Pla_Nom "
                        . "FROM persona INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod) "
                        . "left JOIN (SELECT vendedor.Prs_Cod,puntos_imp.Pun_Cod,Pun_Des,Pun_Est "
                        . "FROM puntos_imp INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod) "
                        . "WHERE puntos_imp.Suc_Cod = $Par_Sql[Suc_Cod] AND Vnd_Est='A') as pu ON pu.Prs_Cod=persona.Prs_Cod "
                        . "INNER JOIN sucursal as suc on suc.Suc_Cod= usuarios.Suc_Cod and suc.Emp_Cod=$_SESSION[Ses_Emp_Cod] "
						. "LEFT JOIN manifiesto_usuario mu ON mu.Usu_Cod = usuarios.Usu_Cod "
                        . "LEFT JOIN manifiesto_plantas mp ON mp.Pla_Cod = mu.Pla_Cod "
                        . "WHERE usuarios.Usu_Cod IN (SELECT max(usuarios.Usu_Cod) FROM usuarios,sucursal WHERE usuarios.Suc_Cod=sucursal.Suc_Cod AND Emp_Cod = $_SESSION[Ses_Emp_Cod]  GROUP BY usuarios.Prs_Cod )"
                        . "AND persona.".$Par_Sql['filtro']." '$Par_Sql[dato]' GROUP BY persona.Prs_Cod  ORDER BY persona ASC";
                    //echo $sql;
					return $sql;
                break;

                case 39://  Select all Users
                    $sql = "SELECT usuarios.Usu_Cod, persona.Prs_Ced, CONCAT(persona.Prs_Ape,' ',persona.Prs_Nom) as persona, usuarios.Suc_Cod, persona.Prs_Cod, pu.Pun_Cod, pu.Pun_Des as Pun_Des_m, GROUP_CONCAT(DISTINCT mp.Pla_Nom SEPARATOR ', ') as Pla_Nom "
                        . "FROM persona INNER JOIN usuarios ON (persona.Prs_Cod = usuarios.Prs_Cod)  left JOIN (SELECT vendedor.Prs_Cod,puntos_imp.Pun_Cod,Pun_Des,Pun_Est "
                        . "FROM puntos_imp INNER JOIN vendedor ON (puntos_imp.Pun_Cod = vendedor.Pun_Cod) "
                        . "WHERE puntos_imp.Suc_Cod = $Par_Sql[0] AND Vnd_Est='A') as pu ON pu.Prs_Cod=persona.Prs_Cod "
                        . "INNER JOIN sucursal as suc on suc.Suc_Cod = usuarios.Suc_Cod and suc.Emp_Cod= $_SESSION[Ses_Emp_Cod] "
						. "LEFT JOIN manifiesto_usuario mu ON mu.Usu_Cod = usuarios.Usu_Cod "
                        . "LEFT JOIN manifiesto_plantas mp ON mp.Pla_Cod = mu.Pla_Cod "
                        . "WHERE usuarios.Usu_Cod IN (SELECT max(usuarios.Usu_Cod) FROM usuarios,sucursal WHERE usuarios.Suc_Cod=sucursal.Suc_Cod AND usu_est = 'A' AND Emp_Cod = $_SESSION[Ses_Emp_Cod] GROUP BY usuarios.Prs_Cod ) "
                        . "GROUP BY usuarios.Usu_Cod ORDER BY persona ASC";
                    //echo $sql;
					return $sql;
                break;

                case 40://  Select Perfiles for User
                    $sql = "SELECT p.Per_Cod,p.Per_Des FROM perfiles p
                            INNER JOIN usuarperfi up ON up.Per_Cod = p.Per_Cod
                            /*INNER JOIN usuario u ON u.Usu_Cod = up.Usu_Cod*/
                            WHERE up.Usu_Cod = $Par_Sql[Usu_Cod]";
                    return $sql;
                break;

                case 41://  Actualiza Password de Usuario
                    $sql = "UPDATE usuarios SET Usu_Pal = md5('$Par_Sql[Usu_Pal]') WHERE Usu_Cod = $Par_Sql[Usu_Cod] AND Suc_Cod = $Par_Sql[Suc_Cod]";
                    return $sql;
                break;

                case 42://  Actualiza Descripcion de Punto by Persona y Sucursal
                    $sql = "UPDATE puntos_imp AS imp
                        INNER JOIN sucursal AS suc ON suc.Suc_Cod = imp.Suc_Cod
                        INNER JOIN vendedor AS ven ON ven.Pun_Cod = imp.Pun_Cod and ven.Prs_Cod = $Par_Sql[Prs_Cod]
                        SET imp.Pun_Des = '$Par_Sql[Pun_Des]'
                        WHERE suc.Emp_Cod = $_SESSION[Ses_Emp_Cod]";
                    return $sql;
                break;

                case 43://  Elimina Asignacion de Perfil a Usuario
                    $sql = "DELETE FROM usuarperfi WHERE Usu_Cod = $Par_Sql[Usu_Cod]";
                    return $sql;
                break;

                case 44://  Validar si existe el punto ante de actualizar
                    $sql = "SELECT COUNT(Pun_Cod) as punto FROM puntos_imp WHERE Pun_Cod = '$Par_Sql[Pun_Cod]' AND Suc_Cod = $Par_Sql[Suc_Cod]";
                    return $sql;
                break;

                case 45://  Get Dat_Cod by Empresa
                    $sql = "SELECT Dat_Cod FROM data WHERE Emp_Cod = $Par_Sql[0]";
                    return $sql;
                break;

                case 46://  INSERT User on ACCESS
                    $sql = "INSERT INTO access (Suc_Cod, Dat_Cod, Acc_Usr, Acc_Est) "
                        . "VALUES ($Par_Sql[Suc_Cod], $Par_Sql[Dat_Cod], '$Par_Sql[Acc_Usr]', 'A')";
                    //echo $sql;
					return $sql;
                break;


                case 47:
                    $sql = "SELECT * FROM  sucursal WHERE Emp_Cod= $Par_Sql[0]";
                    return $sql;
                break;

                case 48:
                    $estado="AND usu.Usu_Est='A'";
                    if(isset($Par_Sql['Sin_Est']))
                        $estado="";
                    $sql="SELECT * FROM sucursal AS suc
                        INNER JOIN usuarios AS usu ON usu.Suc_Cod=suc.Suc_Cod
                        where Emp_Cod=$_SESSION[Ses_Emp_Cod] AND usu.Prs_Cod =$Par_Sql[Prs_Cod] $estado";
                    return $sql;
                break;

                case 50://inactivar usuarios asignados a una persona en la empresa actual
                    $sql="UPDATE usuarios AS usu
                        INNER JOIN sucursal AS suc ON usu.Suc_Cod = suc.Suc_Cod and suc.Emp_Cod=$_SESSION[Ses_Emp_Cod]
                        SET usu.Usu_Est='I'
                        WHERE usu.Prs_Cod=$Par_Sql[Prs_Cod]";
                    return $sql;
                    break;
                case 51:
                    $sql="SELECT usu.* FROM usuarios AS usu
                        INNER JOIN sucursal AS suc ON suc.Suc_Cod = usu.Suc_Cod AND suc.Emp_Cod = $_SESSION[Ses_Emp_Cod] AND suc.Suc_Cod=$Par_Sql[Suc_Cod]
						INNER JOIN persona AS per ON usu.Prs_Cod=per.Prs_Cod AND per.Prs_Cod= $Par_Sql[Prs_Cod]";
                    return $sql;
                    break;
                case 52:
                    $sql="UPDATE usuarios SET Usu_Est='A' WHERE Usu_Cod=$Par_Sql[Usu_Cod] and Suc_Cod=$Par_Sql[Suc_Cod]";
                    return $sql;
					break;

				case 53:
					$sql="SELECT vendedor.* FROM vendedor
					INNER JOIN puntos_imp ON puntos_imp.Pun_Cod = vendedor.Pun_Cod
					WHERE Prs_Cod=$Par_Sql[Prs_Cod] AND Suc_Cod=$Par_Sql[Suc_Cod]";
					return $sql;
					break;
				case 54:
					$sql="DELETE FROM access WHERE Dat_Cod=$Par_Sql[Dat_Cod] AND Acc_Usr='$Par_Sql[Acc_Usr]'";
					return $sql;
					break;
				case 55:
					// Determinar el tipo de búsqueda
					$op_opciones = isset($Par_Sql['op_opciones']) ? $Par_Sql['op_opciones'] : '';
					$searchCli = isset($Par_Sql['searchCli']) ? addslashes($Par_Sql['searchCli']) : '';
					
					// Construir la condición de búsqueda según el tipo
					if ($op_opciones == "c") {
						// Búsqueda por cédula/RUC
						$search = $searchCli != '' ? "persona.Prs_Ced LIKE '%$searchCli%'" : "1=1";
					} else {
						// Búsqueda por nombre del cliente
						$search = $searchCli != '' ? "(CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape) LIKE '%$searchCli%' OR persona.Prs_Nom LIKE '%$searchCli%' OR persona.Prs_Ape LIKE '%$searchCli%')" : "1=1";
					}
					
					// Si no hay limits, es para contar registros
					if (empty($Par_Sql['limits'])) {
						$campos = "COUNT(cliente.Cli_Cod) AS total";
						$limits = "";
					} else {
						// Si hay limits, es para obtener los datos
						$campos = "cliente.Cli_Cod, persona.Prs_Cod, persona.Prs_Ced, 
									IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre, 
									persona.Prs_Dir";
						$limits = $Par_Sql['limits'];
					}
					
					$sql = "SELECT $campos
							FROM persona
								INNER JOIN cliente ON cliente.Prs_Cod = persona.Prs_Cod
							WHERE $search 
								AND cliente.Emp_Cod = '$_SESSION[Ses_Emp_Cod]'
								AND cliente.Cli_Est = 'A'
							$limits";
					return $sql;
					break;
				case 56:
					$Pla_Cod_value = (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '' && $Par_Sql['Pla_Cod'] !== null) ? $Par_Sql['Pla_Cod'] : 'NULL';
					$sql="INSERT INTO manifiesto_usuario (Usu_Cod, Cli_Cod, Pla_Cod) VALUES ($Par_Sql[Usu_Cod], $Par_Sql[Cli_Cod], $Pla_Cod_value)";
					return $sql;
					break;
				case 57://  Obtener cliente asignado a un usuario desde manifiesto_usuario
					$sql="SELECT mu.Cli_Cod, mu.Usu_Cod, mu.Pla_Cod, cliente.Prs_Cod, persona.Prs_Ced, 
							IF(persona.Prs_Nom=persona.Prs_Ape, persona.Prs_Nom, CONCAT(persona.Prs_Nom, ' ', persona.Prs_Ape)) AS nombre
							FROM manifiesto_usuario AS mu
							INNER JOIN cliente ON mu.Cli_Cod = cliente.Cli_Cod
							INNER JOIN persona ON cliente.Prs_Cod = persona.Prs_Cod
							WHERE mu.Usu_Cod = $Par_Sql[Usu_Cod]
							LIMIT 1";
					return $sql;
					break;
				case 58://  Eliminar cliente asignado a un usuario desde manifiesto_usuario
					$sql="DELETE FROM manifiesto_usuario WHERE Usu_Cod = $Par_Sql[Usu_Cod]";
					return $sql;
					break;
				case 61://  Verificar si existe registro en manifiesto_usuario para un Usu_Cod
					$sql="SELECT COUNT(*) as existe FROM manifiesto_usuario WHERE Usu_Cod = $Par_Sql[Usu_Cod]";
					return $sql;
					break;
				case 62://  Actualizar Cli_Cod y Pla_Cod en manifiesto_usuario
					$Pla_Cod_value = (isset($Par_Sql['Pla_Cod']) && $Par_Sql['Pla_Cod'] !== '' && $Par_Sql['Pla_Cod'] !== null) ? $Par_Sql['Pla_Cod'] : 'NULL';
					$sql="UPDATE manifiesto_usuario SET Cli_Cod = $Par_Sql[Cli_Cod], Pla_Cod = $Pla_Cod_value WHERE Usu_Cod = $Par_Sql[Usu_Cod]";
					return $sql;
					break;
				case 63:
					$sql = "SELECT Pla_Cod, Pla_Nom, Pla_Lic FROM manifiesto_plantas WHERE Pla_Est = 'A' AND Cli_Cod = '$Par_Sql[Cli_Cod]';";
					return $sql;
					break;
				case 64://Select para comprobar si el usuario ya se encuentra registrado
					$sql="SELECT usuarios.Suc_Cod FROM usuarios 
					INNER JOIN sucursal ON usuarios.Suc_Cod = sucursal.Suc_Cod 
					INNER JOIN empresas ON sucursal.Emp_Cod = empresas.Emp_Cod 
					WHERE Prs_Cod='$Par_Sql[Prs_Cod]' AND empresas.Emp_Cod = $Par_Sql[Emp_Cod]";
					return $sql;
					break;
	}
}
?>