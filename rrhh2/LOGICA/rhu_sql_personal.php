<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_rhu($id,$Par_Sql)
	{
		switch($id)
		{	
						
			case 1:
			/* Consulta los datos personales de la persona en base a la cedula */
			$sql = "SELECT Prs_Cod, Prs_Ced, Prs_Nom, Prs_Ape, Prs_Fec, Prs_Dir, Prs_Tel, Prs_Te2, Prs_Cel, Prs_Sex, 
			Prs_Esc, Ciu_Cod, Prs_Cor, Prs_Est FROM persona WHERE persona.Prs_Ced = '$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;
			
			case 2:
			/* Consulta del codigo del personal en base al codigo principal de la persona */
			$consultar_personal = "SELECT Per_Cod FROM personal WHERE personal.Prs_Cod = '$Par_Sql[0]' AND personal.Emp_Cod = '$Par_Sql[1]'";
			//echo $consultar_personal;
			return $consultar_personal;
			break;
			
			case 3:
			/* Insertar datos en la tabla persona */
			$insertar_personal = "INSERT INTO persona (Ciu_Cod, Prs_Ced, Prs_Nom, Prs_Ape,  Prs_Fec,  Prs_Dir, Prs_Tel, Prs_Te2, 
			Prs_Cel, Prs_Sex, Prs_Esc, Prs_Cor, Prs_San, Pas_Cod, Ide_Cod,Par_Cod )
			VALUE ('$Par_Sql[0]', '$Par_Sql[1]', UPPER('$Par_Sql[2]'), UPPER('$Par_Sql[3]'), '$Par_Sql[4]', UPPER('$Par_Sql[5]'), 
			'$Par_Sql[6]', '$Par_Sql[7]', '$Par_Sql[8]', '$Par_Sql[9]', '$Par_Sql[10]', '$Par_Sql[11]', '$Par_Sql[12]', '$Par_Sql[13]','$Par_Sql[14]','$Par_Sql[15]')";
			//echo $insertar_personal;
			return $insertar_personal;
			break;
			
			case 4:
			/* Consulta de los datos de la persona */
			$consul_persona = "SELECT ciudad.Ciu_Des, persona.Prs_Cod, persona.Prs_Ced ,  persona.Prs_Nom,identifica.Ide_Des, 
			persona.Prs_Ape, IF(persona.Prs_Sex = 'M', 'Masculino', IF(persona.Prs_Sex = 'F', 'Femenino', '')) 
			AS Prs_Sex, persona.Prs_San, persona.Prs_Fec, 
			IF(persona.Prs_Esc = 'S', 'Soltero/a', IF(persona.Prs_Esc = 'C', 'Casado/a', 
			IF(persona.Prs_Esc = 'V', 'Viudo/a', IF(persona.Prs_Esc = 'D', 'Divorciado/a', 
			IF(persona.Prs_Esc = 'U', 'Unión Libre/a', ''))))) AS Prs_Esc, 
			persona.Prs_Dir, persona.Ciu_Cod, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, 
			persona.Prs_Cor, persona.Pas_Cod FROM identifica, ciudad, persona WHERE ciudad.Ciu_Cod = persona.Ciu_Cod 
			AND identifica.Ide_Cod= persona.Ide_Cod  AND Prs_Cod = $Par_Sql[0]";			
			//echo $consul_persona;
			return $consul_persona;
			break;
					
			case 5:/* carga identificaciones */
			$identificacion= "SELECT Ide_Cod, Ide_Des FROM identifica WHERE Ide_Est='A'";
			return $identificacion;
			break;	
			
			case 6:
			/* Consulta los paises */
			$sql_pai="SELECT Pas_Cod, Pas_Nac, Pas_Nom FROM pais ORDER BY Pas_Nac ASC";
			return $sql_pai;
			//echo $sql_pai;
	
			/* Consulta de ciudades para la inscripción */
			case 7:
			$consulta_ciudad="SELECT Ciu_Cod, Ciu_Des FROM ciudad ORDER BY Ciu_Des";
			return $consulta_ciudad;
			break;
			
			/* Consulta de ciudades para la inscripción */
			case 8:
			$consulta_escu="SELECT Esc_Int, Esc_Cod, Esc_Nom FROM escuelas WHERE Esc_Est='A'";
			return $consulta_escu;
			break;
			
			case 9:
			/* Insertar datos en la tabla personal */
			$insertar_personal = "INSERT INTO personal (Prs_Cod, Per_Obs, Per_Tit, Per_Car, Emp_Cod) VALUES ($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]', UPPER('$Par_Sql[3]'),'$Par_Sql[4]')";
			//echo "<br>".$insertar_personal;
			return $insertar_personal;
			break;
			
			case 10:
			/* Consulta del personal por apellidos */
			$sql = "SELECT  personal.Prs_Cod, personal.Per_Cod , persona.Prs_Ced, 
			persona.Prs_Ape, persona.Prs_Fec, persona.Prs_Nom, IF (personal.Per_Est='A','Activo','Retirado') as Per_Est 
			FROM personal, persona 
			WHERE persona.Prs_Cod = personal.Prs_Cod AND 
			persona.Prs_Ape LIKE '%$Par_Sql[0]%' AND personal.Emp_Cod='$Par_Sql[1]' 
			ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
		    //echo $sql;
			return $sql;
			break;
			
			case 11:
			/* Consulta del personal por cedula */
			$sql = "SELECT distinct personal.Prs_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Ape, 
			persona.Prs_Nom, persona.Prs_Fec FROM personal, persona  WHERE persona.Prs_Cod = personal.Prs_Cod
			AND persona.Prs_Ced = '$Par_Sql[0]' AND personal.Emp_Cod='$Par_Sql[1]' AND
			personal.Per_Est='A'";
			//echo $sql;
			return $sql;
			break;
			
			case 12:
			/* Consulta de los datos de la tabla persona con la descripcion de las otras tablas como: Ciu_Des */
			$consultar_persona = "SELECT persona.Prs_Ced,  persona.Prs_Nom, persona.Prs_San, persona.Prs_Ape, IF(persona.Prs_Sex = 'M','Masculino','Femenino') as  Prs_Sex, YEAR(persona.Prs_Fec) as Ann_Ini, MONTH(persona.Prs_Fec) as Mes_Ini, DATE_FORMAT(persona.Prs_Fec,'%d') AS Dia_Ini, IF (persona.Prs_Esc='S','Soltero/a', IF (persona.Prs_Esc ='C','Casado/a', IF (persona.Prs_Esc='D', 'Divorciado/a', IF (persona.Prs_Esc ='V', 'Viudo/a', 'Unión libre')))) as Prs_Esc, persona.Prs_Dir, ciudad.Ciu_Des, persona.Prs_Tel, persona.Prs_Te2, persona.Prs_Cel, persona.Prs_Cor, persona.Ciu_Cod,persona.Pas_Cod,persona.Par_Cod, personal.Per_Car, persona.Ide_Cod, personal.Per_Tit, personal.Per_Obs, IF (personal.Per_Est = 'A', 'Activo', 'Retirado') as Per_Est,Ide_Des FROM personal, persona, ciudad,identifica 
			 WHERE identifica.Ide_Cod= persona.Ide_Cod  AND personal.Prs_Cod = persona.Prs_Cod AND persona.Ciu_Cod = ciudad.Ciu_Cod AND personal.Per_Cod = $Par_Sql[0]"; 
			//echo $consultar_persona;
			return $consultar_persona;
			break;
			
			case 13:
			/* Modifica o actualiza los cambios realizados en la tabla usuarios */
			$modificar_usuario = "UPDATE usuarios SET Usu_Ced ='$Par_Sql[0]' WHERE Prs_Cod = '$Par_Sql[1]'";
			//echo $modificar_usuario;
			return $modificar_usuario;
			break;
	
			case 14:
			/* Modifica o actualiza los cambios realizados en la tabla personal 
			$modificar_personal = "UPDATE personal SET  Per_Tit = UPPER('$Par_Sql[0]'),  Esc_Int = $Par_Sql[1], Per_Obs = 
			'$Par_Sql[2]',  Per_Car = $Par_Sql[3], Ide_Cod = $Par_Sql[4] WHERE Prs_Cod = $Par_Sql[5]";*/
			$modificar_personal = "UPDATE personal SET  Per_Tit = UPPER('$Par_Sql[0]'),  Per_Obs = 
			'$Par_Sql[1]',  Per_Car = $Par_Sql[2] WHERE Prs_Cod = $Par_Sql[3]";
			//echo "<br>".$modificar_personal;
			return $modificar_personal;
			break;
			
			case 15:
			/* Modifica o actualiza los cambios realizados en la tabla persona */
			$modificar_persona = "UPDATE persona SET  Prs_Ced = '$Par_Sql[0]', Prs_Nom = UPPER('$Par_Sql[1]'), Prs_Ape = 
			UPPER('$Par_Sql[2]'), Prs_Sex = '$Par_Sql[3]', 	Prs_Fec= '$Par_Sql[4]', Prs_Esc = '$Par_Sql[5]', Prs_Dir= 
			UPPER('$Par_Sql[6]'), Ciu_Cod = $Par_Sql[7], Prs_Tel = '$Par_Sql[8]', Prs_Te2 = 
			'$Par_Sql[9]', Prs_Cel = '$Par_Sql[10]', Prs_Cor = '$Par_Sql[11]', Ide_Cod=$Par_Sql[13], Pas_Cod = $Par_Sql[14], Par_Cod=$Par_Sql[15],Prs_San='$Par_Sql[16]'  WHERE Prs_Cod= $Par_Sql[12]";						
			//echo "<br>".$modificar_persona;
			return $modificar_persona;
						
			case 16:
			$sql="SELECT pais.Pas_Cod, pais.Pas_Nom, pais.Pas_Nac, regiones.Reg_Nom, regiones.Reg_Cod, provincia.Pro_Cod, provincia.Pro_Nom, ciudad.Ciu_Cod, ciudad.Ciu_Des,  parroquia.Par_Cod, parroquia.Par_Nom  FROM pais, regiones, ciudad, parroquia, provincia WHERE pais.Pas_Cod=regiones.Pas_Cod AND regiones.Reg_Cod=provincia.Reg_Cod AND provincia.Pro_Cod=ciudad.Pro_Cod AND ciudad.Ciu_Cod=parroquia.Ciu_Cod AND parroquia.Par_Cod='$Par_Sql[0]'";
			//echo $sql;
			return $sql;
			break;						
			
			case 106:
			$con_pais_106="SELECT Pas_Cod, Pas_Nom, Pas_Nac FROM pais WHERE Pas_Est='A'";
			return $con_pais_106;
			break;
			/** consulta de provincias */
			case 107:
			$con_provincias_107="SELECT Pro_Cod, Pro_Nom FROM provincia WHERE Reg_Cod='$Par_Sql[0]'";
			//echo $con_provincias_107; 
			return $con_provincias_107;
			break;
			/* Consulto las regiones registradas en la base de datos */
			case 108:
			$sql="SELECT Reg_Cod, Pas_Cod, Reg_Nom, Reg_Est FROM regiones WHERE Pas_Cod='$Par_Sql[0]' ";
			//echo $sql;
			return $sql;
			break;
			/* consultar ciudades */
			case 109:
			$con_ciudades_109="SELECT Ciu_Cod, Ciu_Des FROM ciudad WHERE Pro_Cod='$Par_Sql[0]' ORDER BY Ciu_Des ";
			//echo $con_ciudades_109;
			return $con_ciudades_109;
			break;
			/* consulta parroquias */
			case 110:
			$consulta_parroquias_110="SELECT Par_Cod, Par_Nom FROM parroquia WHERE Ciu_Cod='$Par_Sql[0]'";
			//echo $consulta_parroquias_110;
			return $consulta_parroquias_110;
			break;
			
			
        }
}
?>