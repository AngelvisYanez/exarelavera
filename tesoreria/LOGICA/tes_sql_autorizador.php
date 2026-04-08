<?php
	/*TESORERIA*/
	function sentencias_tes($id,$Par_Sql)
	{
		switch($id)
		{		
			case 119:
				/* Consulta de los datos de la persona */
				$consul_persona_119 = "SELECT ciudad.Ciu_Des, persona.Prs_Ced, persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, IF(persona.Prs_Sex = 'M', 'Masculino', 		 IF(persona.Prs_Sex = 'F', 'Femenino', '')) AS Prs_Sex, persona.Prs_San, persona.Prs_Fec, IF(persona.Prs_Esc = 'S', 
					 'Soltero/a', IF(persona.Prs_Esc = 'C', 'Casado/a', IF(persona.Prs_Esc = 'V', 'Viudo/a', IF(persona.Prs_Esc = 'D', 
					 'Divorciado/a', IF(persona.Prs_Esc = 'U', 'Union Libre/a', ''))))) AS Prs_Esc, persona.Prs_Dir, persona.Prs_Est,
					 persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Prs_Cor, persona.Pas_Cod,
					 pais.Pas_Nac, Ide_Cod FROM ciudad INNER JOIN persona ON (ciudad.Ciu_Cod = persona.Ciu_Cod) INNER JOIN pais ON (ciudad.Pas_Cod = 
					 pais.Pas_Cod) WHERE Prs_Cod = $Par_Sql[0]";
				//echo $consul_persona_119;
				return $consul_persona_119;
			break;
			
			/* Consultar persona */
			case 220:
			/* Consulta de los datos de la persona */
				$consul_persona_220 = "SELECT ciudad.Ciu_Des, persona.Prs_Ced, persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, IF(persona.Prs_Sex = 'M', 'Masculino', 
					 IF(persona.Prs_Sex = 'F', 'Femenino', '')) AS Prs_Sex, persona.Prs_San, persona.Prs_Fec, IF(persona.Prs_Esc = 'S', 
					 'Soltero/a', IF(persona.Prs_Esc = 'C', 'Casado/a', IF(persona.Prs_Esc = 'V', 'Viudo/a', IF(persona.Prs_Esc = 'D', 
					 'Divorciado/a', IF(persona.Prs_Esc = 'U', 'Union Libre/a', ''))))) AS Prs_Esc, persona.Prs_Dir, persona.Prs_Est,
					 persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Prs_Cor, persona.Pas_Cod,
					 pais.Pas_Nac, Ide_Cod FROM ciudad INNER JOIN persona ON (ciudad.Ciu_Cod = persona.Ciu_Cod) INNER JOIN pais ON (ciudad.Pas_Cod = 
					 pais.Pas_Cod) WHERE Prs_Ape LIKE '%$Par_Sql[0]%'";
				//echo $consul_persona_220;				
				return $consul_persona_220;
			break;
			
			/* Consultar persona */
			case 221:
			$consultar_persona_221="SELECT Prs_Cod FROM persona WHERE persona.Prs_Ced='$Par_Sql[0]'" ;
			//echo $consultar_persona_221;
	
			return $consultar_persona_221;
			break;
			
			case 222:
			/* Consulta de los datos de la persona */
			$consul_persona_222 = "SELECT ciudad.Ciu_Des, persona.Prs_Cod, persona.Prs_Nom, persona.Prs_Ape, IF(persona.Prs_Sex = 'M', 'Masculino', 
					 IF(persona.Prs_Sex = 'F', 'Femenino', '')) AS Prs_Sex, persona.Prs_San, persona.Prs_Fec, IF(persona.Prs_Esc = 'S', 
					 'Soltero/a', IF(persona.Prs_Esc = 'C', 'Casado/a', IF(persona.Prs_Esc = 'V', 'Viudo/a', IF(persona.Prs_Esc = 'D', 
					 'Divorciado/a', IF(persona.Prs_Esc = 'U', 'Union Libre/a', ''))))) AS Prs_Esc, persona.Prs_Dir,
					 persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Prs_Cor, persona.Pas_Cod,
					 pais.Pas_Nac, Ide_Cod FROM ciudad INNER JOIN persona ON (ciudad.Ciu_Cod = persona.Ciu_Cod) INNER JOIN pais ON (ciudad.Pas_Cod = 
					 pais.Pas_Cod) WHERE Prs_Ced = '$Par_Sql[0]'";
			return $consul_persona_222;
			break;
			
			case 223:
			/* Insertar la persona */
			$insert_perso_223 = "INSERT INTO persona (Ciu_Cod, Prs_Ced, Prs_Nom, Prs_Ape,  Prs_Fec,  Prs_Dir, Prs_Tel, Prs_Cel, Prs_Sex, Prs_Esc, Prs_Cor, Prs_San, Ide_Cod, Par_Cod) VALUES ('$Par_Sql[0]', '$Par_Sql[1]', UPPER('$Par_Sql[2]'), UPPER('$Par_Sql[3]'), '$Par_Sql[4]', UPPER('$Par_Sql[5]'), '$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]', '$Par_Sql[9]', '$Par_Sql[10]', UPPER('$Par_Sql[11]'), $Par_Sql[12], $Par_Sql[13])";
			return $insert_perso_223;
			break;
			
			/* Consulta de ciudades activas */
			case 224:
			$consulta_ciudad_224="SELECT Ciu_Cod, Ciu_Des FROM ciudad WHERE Ciu_Est = 'A' ORDER BY Ciu_Des";
			return $consulta_ciudad_224;
			break;
	
			// Editar los datos de la persona referente a cualquier puesto, es independiente de los dem?s cargos que se le imputen a la persona.
			case 615:
			$sql = "UPDATE persona, ciudad SET Prs_Sex='$Par_Sql[0]', Prs_Esc='$Par_Sql[1]', Prs_Dir=UPPER('$Par_Sql[2]'), persona.Ciu_Cod=$Par_Sql[3],
										Prs_Tel='$Par_Sql[4]', Prs_Te2='$Par_Sql[5]', Prs_Cor='$Par_Sql[6]', Prs_Cel='$Par_Sql[7]' WHERE ciudad.Ciu_Cod=persona.Ciu_Cod AND persona.Prs_Cod='$Par_Sql[8]';";
			return $sql;
			break;
			
			// Consultar un registro de Autorizador para determinar si existe o no.
			case 624:
			$sql = "SELECT Aut_Cod, Aut_Est FROM autorizado, persona, personal, distributi WHERE Prs_Ced='$Par_Sql[0]' AND autorizado.Dis_Cod=distributi.Dis_Cod AND distributi.Per_Cod=personal.Per_Cod AND personal.Prs_Cod=persona.Prs_Cod;";
			//echo $sql;
			return $sql;
			break;
			
			// Registrar un individuo del personal como Autorizador
			case 625:
				$sql = "INSERT INTO autorizado VALUES (null,$Par_Sql[1],(SELECT Dis_Cod from personal, persona, distributi WHERE persona.Prs_Cod=personal.Prs_Cod AND
		personal.Per_Cod=distributi.Per_Cod AND persona.Prs_Cod='$Par_Sql[0]'), 'A');";
			//echo $sql;
			return $sql;
			break;
			
			// Eliminar la asignacion de Autorizador al personal
			case 626:
				$sql = "UPDATE autorizado SET Aut_Est='$Par_Sql[0]' WHERE Aut_Cod=$Par_Sql[1];";
				return $sql;
			break;
	
			// Consultar un individuo del personal que esta asignado como Autorizador por la cedula
			case 627:
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, autorizado.Aut_Cod, autorizado.Aut_Est 
					FROM persona, personal, distributi, autorizado 
					WHERE autorizado.Dis_Cod=distributi.Dis_Cod AND distributi.Per_Cod=personal.Per_Cod AND personal.Prs_Cod=persona.Prs_Cod 
					AND persona.Prs_Ced='$Par_Sql[0]' AND autorizado.Emp_Cod=$Par_Sql[1];";
			return $sql;
			break;
			
			// Consultar un individuo del personal que esta asignado como Autorizador por el apellido
			case 628:
			$sql = "SELECT persona.Prs_Cod, persona.Prs_Ced, persona.Prs_Nom, persona.Prs_Ape, autorizado.Aut_Cod, autorizado.Aut_Est 
					FROM persona, personal, distributi, autorizado 
					WHERE autorizado.Dis_Cod=distributi.Dis_Cod AND distributi.Per_Cod=personal.Per_Cod AND personal.Prs_Cod=persona.Prs_Cod 
					AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND autorizado.Emp_Cod=$Par_Sql[1];";
			return $sql;
			break;
	
			// Consultar si la persona esta en el distributivo para determinar si se le puede asignar un cargo o no
			case 634:
			$sql = "SELECT * FROM distributi, personal, persona WHERE distributi.Per_Cod=personal.Per_Cod AND personal.Prs_Cod=persona.Prs_Cod AND Prs_Ced='$Par_Sql[0]';";
			return $sql;
			break;
		}
	}
?>