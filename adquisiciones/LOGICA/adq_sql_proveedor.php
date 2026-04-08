<?php
/**
 * Retorna consulta sql a ejecutarse
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualizaci�n:	2012-04-30
 *
 * @param int $id
 * @param array $Par_Sql
 * @return string $sql
 *
 * @package tesoreria.LOGICA
 */
	function sentencias_prv($id,$Par_Sql) {
		switch($id) {
			/* Verifica si existen datos de una persona */
		case 1:
			$sql= "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Sex, 
							IF (persona.Prs_Sex='M','Masculino','Femenino') as sexo , 
							Prs_Dir, Prs_Tel, Prs_Te2 ,Prs_Cel, ciudad.Ciu_Cod, identifica.Ide_Cod, identifica.Ide_Des, ciudad.Ciu_Des, Prs_Cor 
					FROM persona, identifica, ciudad 
					WHERE (Prs_Ced='$Par_Sql[0]' OR Prs_Ced='$Par_Sql[1]') AND
						identifica.Ide_Cod=persona.Ide_Cod AND
						ciudad.Ciu_Cod=persona.Ciu_Cod
					ORDER BY Prs_Cod";
			return $sql;
			break;
		
		/* Verifica si el proveedor esta registrado como persona */		
		case 2:
			$sql = "SELECT Prv_Cod
					FROM proveedore, persona 
					WHERE persona.Prs_Cod = proveedore.Prs_Cod AND
						persona.Prs_Cod = '$Par_Sql[0]' AND
						proveedore.Emp_Cod = '$Par_Sql[1]'";
			return $sql;
			break;
		
		/* Consulta las ciudades */
		case 3:
			$sql="SELECT Ciu_Cod, Ciu_Des FROM ciudad ORDER BY Ciu_Des";
			return $sql;
			break;
		
		/* insertar datos en la tabla proveedores */
		case 4:
		$sql = "INSERT INTO proveedore 
							( Prs_Cod, Prv_Com, Prv_Tic, Prv_Con, Prv_Nge, Prv_Apg, Prv_Tlg, Prv_Ceg, Prv_Cog, Prv_Ace, Prv_Fin, Prv_Fce, Prv_Fre, Prv_Fac, Prv_Act, Prv_Nct, Prv_Ect, Prv_Fax, Prv_Esp, Emp_Cod, Prv_Rep )
						VALUES
							( '$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]', '$Par_Sql[9]', '$Par_Sql[10]', '$Par_Sql[11]', '$Par_Sql[12]', '$Par_Sql[13]', '$Par_Sql[14]', '$Par_Sql[15]', '$Par_Sql[16]', '$Par_Sql[17]', '$Par_Sql[18]', '$Par_Sql[19]', '$Par_Sql[20]' );";
                //echo  $sql;
			return $sql;
			break;
		
		/* Inserta datos en persona */
		case 5:
		$sql= "INSERT INTO persona 
							(Prs_Ced, Ide_Cod, Prs_Nom, Prs_Ape, Ciu_Cod, Prs_Dir, Prs_Tel, Prs_Te2, Prs_Cel, Prs_Cor, Prs_Sex)
						VALUES
							('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]', '$Par_Sql[9]', '');";
                //echo  "<br>".$sql;
		return $sql;
		break;
		
		/* obtener datos de identificacion segun la cantidad de digitos ingresados */
		case 6:
		$sql= "SELECT Ide_Cod, Ide_Des, Ide_Max, Ide_Raz 
				FROM identifica 
				WHERE Ide_Max = '$Par_Sql[0]'";
		return $sql;
		break;
		
		/* Actualiza datos en person */
		case 7:
		$sql= "UPDATE persona 
				SET
					Prs_Ced = '$Par_Sql[0]',
					Ide_Cod = '$Par_Sql[1]',
					Prs_Nom = '$Par_Sql[2]', 
					Prs_Ape = '$Par_Sql[3]',
					Ciu_Cod = '$Par_Sql[4]',
					Prs_Dir = '$Par_Sql[5]',
					Prs_Tel = '$Par_Sql[6]', 
					Prs_Te2 = '$Par_Sql[7]', 
					Prs_Cel = '$Par_Sql[8]', 
					Prs_Cor = '$Par_Sql[9]'
				WHERE Prs_Cod = '$Par_Sql[10]';";
			return $sql;
			break;
		
		case 8:
		$sql= "UPDATE usuarios
				SET
					Usu_Ced = '$Par_Sql[0]'
				WHERE Prs_Cod = '$Par_Sql[1]';";
			return $sql;
			break;
		
		case 9:
		$sql= "SELECT Prv_Cod,Prs_Cor, proveedore.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape,Prv_Tic,Prv_Cor,Prs_Dir,ciudad.Ciu_Des,Prs_Tel,Prs_Te2, Prv_Com, Prv_Rep FROM proveedore,persona,ciudad WHERE (Prs_Ape LIKE '%$Par_Sql[0]%' OR Prv_Com LIKE '%$Par_Sql[0]%') AND proveedore.Emp_Cod='$Par_Sql[1]'  AND persona.Prs_Cod=proveedore.Prs_Cod AND ciudad.Ciu_Cod = persona.Ciu_Cod ORDER BY Prs_Ape,Prs_Nom ASC";
			return $sql;
			break;
		
		case 10:
		$sql= "SELECT Prv_Cod,proveedore.Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape,Prv_Tic FROM proveedore, persona WHERE Prs_Ced = '$Par_Sql[0]' AND persona.Prs_Cod=proveedore.Prs_Cod AND proveedore.Emp_Cod='$Par_Sql[1]'";
			return $sql;
			break;
		
		case 11:
		$sql = "SELECT 	ps.Prs_Cod, Prs_Ced,
						Prs_Nom, Prs_Ape, Prs_Dir,
						ci.Ciu_Cod, ci.Ciu_Des,
						Prs_Tel, Prs_Te2, Prs_Cel,
						Prs_Cor, ps.Ide_Cod, p.Prv_Cod,
						p.Prv_Com, p.Prv_Tic, p.Prv_Con,
						p.Prv_Nge, p.Prv_Apg, p.Prv_Tlg,
						p.Prv_Ceg, p.Prv_Cog, p.Prv_Ace,
						p.Prv_Fin, p.Prv_Fce, p.Prv_Fre,
						p.Prv_Fac, p.Prv_Act, p.Prv_Nct,
						p.Prv_Ect, p.Prv_Fax, p.Prv_Esp, p.Prv_Rep
				FROM persona ps
					INNER JOIN proveedore p ON p.Prs_Cod = ps.Prs_Cod
					INNER JOIN ciudad ci ON ci.Ciu_Cod = ps.Ciu_Cod
				WHERE p.Prv_Cod = '$Par_Sql[0]'";
			return $sql;
			break;
			
		case 12:
		$sql="UPDATE proveedore
				SET
					Prv_Com = '$Par_Sql[0]',
					Prv_Esp = '$Par_Sql[1]',
					Prv_Con = '$Par_Sql[2]',
					Prv_Nge = '$Par_Sql[3]',
					Prv_Apg = '$Par_Sql[4]',
					Prv_Tlg = '$Par_Sql[5]',
					Prv_Ceg = '$Par_Sql[6]',
					Prv_Cog = '$Par_Sql[7]',
					Prv_Ace = '$Par_Sql[8]',
					Prv_Fin = '$Par_Sql[9]',
					Prv_Fce = '$Par_Sql[10]',
					Prv_Fre = '$Par_Sql[11]',
					Prv_Fac = '$Par_Sql[12]',
					Prv_Act = '$Par_Sql[13]',
					Prv_Nct = '$Par_Sql[14]',
					Prv_Ect = '$Par_Sql[15]',
					Prv_Fax = '$Par_Sql[16]',
					Prv_Tic = '$Par_Sql[18]', Prv_Rep = '$Par_Sql[19]'
				WHERE Prv_Cod = '$Par_Sql[17]'";
				return $sql;
				break;
			
			case 13:
			$sql = "SELECT 
						Eva_Cod, Prv_Cod, Eva_Cpc, Eva_Rpl, Eva_Cpr, Eva_Afl,
						Eva_Cre, Eva_Mat FROM evalprovee WHERE
						Prv_Cod = '$Par_Sql[0]';";
			return $sql;
			break;
			
			case 14:
			$sql = "INSERT INTO evalprovee (Prv_Cod, Eva_Cpc, Eva_Rpl, Eva_Cpr, Eva_Afl, Eva_Cre, Eva_Mat )
							VALUES
								('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]', '$Par_Sql[3]', '$Par_Sql[4]', '$Par_Sql[5]', '$Par_Sql[6]' );";
			return $sql;
			break;
			
			case 15:
			$sql = "UPDATE evalprovee 
					SET
						Prv_Cod = '$Par_Sql[0]' , 
						Eva_Cpc = '$Par_Sql[1]' , 
						Eva_Rpl = '$Par_Sql[2]' , 
						Eva_Cpr = '$Par_Sql[3]' , 
						Eva_Afl = '$Par_Sql[4]' , 
						Eva_Cre = '$Par_Sql[5]' , 
						Eva_Mat = '$Par_Sql[6]'
					WHERE
					Eva_Cod = '$Par_Sql[7]' ;";
			return $sql;
			break;
			
			case 16:
			$sql = "SELECT v.Val_Esp,
						(CASE WHEN v.Val_Cum = 'S' THEN 'X' ELSE ' ' END)'SI',
						(CASE WHEN v.Val_Cum = 'N' THEN 'X' ELSE ' ' END)'NO',
						d.Dev_Ord
					FROM det_eval d
						INNER JOIN valor_evalu v ON d.Val_Cod = v.Val_Cod
					WHERE
						d.Prv_Cod = '$Par_Sql[0]' AND v.Val_Tip = '$Par_Sql[1]'";
				return $sql;
				break;

			case 17:
			$sql = "SELECT Val_Cod, Val_Esp,
							Val_Tec, Val_Tip, Val_Cum 
					FROM valor_evalu 
					WHERE
						Val_Tip='$Par_Sql[0]';";
				return $sql;
				break;
			
			/* insertar valores de evaluzacion de proveedores */
			case 18:
			$sql = "INSERT INTO det_eval (Val_Cod, Prv_Cod, Dev_Fec, Dev_Ord )
						VALUES
							('$Par_Sql[0]', '$Par_Sql[1]', '$Par_Sql[2]',
							(SELECT 
								(CASE
									WHEN ISNULL((SELECT Dev_Ord 
									FROM det_eval d
										INNER JOIN valor_evalu v ON d.Val_Cod=v.Val_Cod
									WHERE Prv_Cod='$Par_Sql[1]' AND Val_Tip = '$Par_Sql[3]'
									ORDER BY Dev_Ord DESC 
									LIMIT 0,1)) THEN '1'
								ELSE
									((SELECT Dev_Ord FROM det_eval d
										INNER JOIN valor_evalu v ON d.Val_Cod=v.Val_Cod
									WHERE Prv_Cod='$Par_Sql[1]' AND Val_Tip = '$Par_Sql[3]'
									ORDER BY Dev_Ord DESC 
									LIMIT 0,1)+1) END)Dev_Ord));";
				return $sql;
				break;
			
			/* Consulta la provicia y pais de la ciudad de la sucursal */
			case 21:
				$sql="SELECT
					provincia.Pro_Nom, pais.Pas_Nom
				FROM provincia
					INNER JOIN ciudad ON (provincia.Pro_Cod = ciudad.Pro_Cod)
					INNER JOIN regiones ON (provincia.Reg_Cod = regiones.Reg_Cod)
					INNER JOIN pais ON (regiones.Pas_Cod = pais.Pas_Cod)
				WHERE ciudad.Ciu_Cod = $Par_Sql[0]";
				return $sql;
				break;
					
			/* Consulta la informacion la ciudada en base a la sucursal */
			case 22:
				$sql="SELECT empresas.Emp_Nom, Emp_Ruc,
							ciudad.Ciu_Des, sucursal.Ciu_Cod,
							sucursal.Suc_Dir, sucursal.Suc_Te1,
							sucursal.Suc_Te2, sucursal.Suc_Fax,
							sucursal.Suc_Cor, sucursal.Suc_Web,
							sucursal.Suc_Des, empresas.Emp_Log
						FROM empresas, sucursal, ciudad
						WHERE sucursal.Suc_Cod = $Par_Sql[0] AND
							empresas.Emp_Cod = sucursal.Emp_Cod AND
							sucursal.Ciu_Cod = ciudad.Ciu_Cod";
				return $sql;
				break;
					
			/* Consulta los datos del usuario */
			case 23:
				$sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				return $sql;
				break;
		}
	} 
?>