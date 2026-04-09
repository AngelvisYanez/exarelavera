<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_rhu($id,$Par_Sql)
	{
		switch($id)
		{	
			
			
			case 97:
			/* Consulta del personal por cedula */
			$consultar_personales = "SELECT personal.Prs_Cod, personal.Per_Cod, persona.Prs_Ced, persona.Prs_Ape, persona.Prs_Nom
			FROM personal, persona WHERE persona.Prs_Cod = personal.Prs_Cod AND persona.Prs_Cod = $Par_Sql[0] AND 
			personal.Per_Est='A'";
			//echo $consultar_personales;
			return $consultar_personales;
			break;
			
			case 615:
			/* Consulta los niveles academicos */
			$niveles_acad="SELECT Nia_Cod, Nia_Des FROM nivel_acad WHERE Nia_Est = 'A'";
			return $niveles_acad;
			break;
					
			case 616:
			/* Inserta la cabecera del curriculo */ 
			$grabar_acad="INSERT INTO curriculo (Per_Cod, Cur_Fec, Cur_Hor) VALUES ($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]')";
			//echo $grabar_acad;
			return $grabar_acad;
			break;
			
			case 617:
			$grabar_cur_617="INSERT INTO det_cestud (Cur_Int,Pas_Cod,Cur_Cod,Nia_Cod,Cur_Ins,Cur_Tit,Cur_Ini,Cur_Fin,Cur_Obs, Cur_Def, Cur_Reg)
			VALUES ($Par_Sql[8],$Par_Sql[0],$Par_Sql[1] ,$Par_Sql[2],UPPER('$Par_Sql[3]'),UPPER('$Par_Sql[4]'),'$Par_Sql[5]', 
			'$Par_Sql[6]',UPPER('$Par_Sql[7]'),'$Par_Sql[9]', '$Par_Sql[10]')";
			//echo $grabar_cur_617;
			return $grabar_cur_617;
			break;
			
			case 618:
			/* Consulta los datos del personal en base al curriculo */
			$con_curri_618="SELECT personal.Per_Cod, curriculo.Cur_Cod, persona.Prs_Cod FROM persona,personal , curriculo WHERE 
			personal.Per_Cod= curriculo.Per_Cod AND persona.Prs_Cod= personal.Prs_Cod AND persona.Prs_Cod=$Par_Sql[0]";
			//echo $con_curri_618;
			return $con_curri_618;
			break;
			
			case 619:
			/* Selecciona los datos ingresados de los titulos */
			$con_curriculo_619="SELECT curriculo.Cur_Cod, det_cestud.Cur_Tit, det_cestud.Cur_Ins,det_cestud.Cur_Ini,det_cestud.Cur_Fin, 
			nivel_acad.Nia_Des, nivel_acad.Nia_Cod, pais.Pas_Nom, pais.Pas_Cod, det_cestud.Cur_Obs, det_cestud.Cur_Int, 
			det_cestud.Cur_Def, det_cestud.Cur_Reg
			FROM curriculo, det_cestud, nivel_acad, pais
			WHERE curriculo.Cur_Cod=det_cestud.Cur_Cod AND det_cestud.Nia_Cod=nivel_acad.Nia_Cod AND pais.Pas_Cod= det_cestud.Pas_Cod 
			AND det_cestud.Cur_Cod=$Par_Sql[0] ";
			//echo $con_curriculo_619;		
			return $con_curriculo_619;
			break;
			
			case 620:
			/* Consulta los datos de la experiencia laboral */
			$con_laboral_620="SELECT curriculo.Cur_Cod,det_clabor.Cur_Car,det_clabor.Cur_Dur,
			det_clabor.Cur_Ins,det_clabor.Cur_Ini,det_clabor.Cur_Fin, det_clabor.Cur_Int,
			pais.Pas_Nom, pais.Pas_Cod, det_clabor.Cur_Obs, YEAR(det_clabor.Cur_Ini) as Ann_Ini FROM curriculo, det_clabor,  pais    
			WHERE curriculo.Cur_Cod=det_clabor.Cur_Cod  AND pais.Pas_Cod= det_clabor.Pas_Cod 
			AND det_clabor.Cur_Cod=$Par_Sql[0] ORDER BY Ann_Ini";
			//echo $con_laboral_620;		
			return $con_laboral_620;
			break;
			
			case 621:
			/* Consulta los datos de la capacitacion */
			$con_capacitacion_621 ="SELECT tip_curria.Tca_Des,tip_curria.Tca_Cod, curriculo.Cur_Cod, det_ccapac.Cur_Tit, 
			det_ccapac.Cur_Dur, 
			det_ccapac.Cur_Ins,det_ccapac.Cur_Ini,det_ccapac.Cur_Fin, pais.Pas_Nom, pais.Pas_Cod, det_ccapac.Cur_Obs, 
			det_ccapac.Cur_Int, YEAR(det_ccapac.Cur_Ini) as Ann_Ini FROM curriculo, det_ccapac,  pais, tip_curria    
			WHERE tip_curria.Tca_Cod= det_ccapac.Tca_Cod AND curriculo.Cur_Cod=det_ccapac.Cur_Cod  AND pais.Pas_Cod= 
			det_ccapac.Pas_Cod 	AND det_ccapac.Cur_Cod=$Par_Sql[0] ORDER BY Ann_Ini";
			//echo $con_capacitacion_621;		
			return $con_capacitacion_621;
			break;
			
			case 622:
			/* Consulta los niveles de capacitacion */
			$niveles_acad_622 ="SELECT Tca_Cod, Tca_Des FROM tip_curria WHERE Tca_Est = 'A'";
			return $niveles_acad_622;
			break;
			
			case 623:
			/* Inserta la experiencia laboral */
			$grabar_labor_623="INSERT INTO det_clabor (Cur_Int,Pas_Cod,Cur_Cod,Cur_Car,Cur_Ins,Cur_Ini,Cur_Fin,Cur_Obs) 
	VALUES ($Par_Sql[7],$Par_Sql[0],$Par_Sql[1] ,UPPER('$Par_Sql[2]') ,UPPER('$Par_Sql[3]'),'$Par_Sql[4]','$Par_Sql[5]' ,UPPER('$Par_Sql[6]'))";
			//echo $grabar_labor_623;
			return $grabar_labor_623;
			break;
			
			case 624:
			/* Inserta los cursos de capacitacion */
			$grabar_capa_624="INSERT INTO det_ccapac (Cur_Int,Pas_Cod, Cur_Cod, Tca_Cod, Cur_Ins,Cur_Tit, Cur_Ini, Cur_Fin, Cur_Obs) VALUES ($Par_Sql[8], $Par_Sql[0],$Par_Sql[1] ,$Par_Sql[2],UPPER('$Par_Sql[3]'),UPPER('$Par_Sql[4]'),
			'$Par_Sql[5]' ,'$Par_Sql[6]',UPPER('$Par_Sql[7]'))";
			return $grabar_capa_624;
			break;
			
			case 625:
			/* Actualiza los datos del titulo seleccionado */
			$actua_acad="UPDATE det_cestud SET Pas_Cod=$Par_Sql[0],  Nia_Cod=$Par_Sql[1], 
			Cur_Ins=UPPER('$Par_Sql[2]'),Cur_Tit=UPPER('$Par_Sql[3]'), Cur_Ini='$Par_Sql[4]', Cur_Fin='$Par_Sql[5]', 
			Cur_Obs=UPPER('$Par_Sql[6]'), Cur_Def='$Par_Sql[8]', Cur_Reg='$Par_Sql[10]'
			WHERE Cur_Int= $Par_Sql[7] AND Cur_Cod = $Par_Sql[9]";
			//echo $actua_acad;
			return $actua_acad;
			break;
			
			
			
			case 626:
			/* Actualiza la experiencia laboral */
			$actua_labor_626=" UPDATE det_clabor SET Pas_Cod=$Par_Sql[0], Cur_Ins=UPPER('$Par_Sql[1]'),Cur_Car=UPPER('$Par_Sql[2]'), 
			Cur_Ini='$Par_Sql[3]',Cur_Fin='$Par_Sql[4]', Cur_Obs=UPPER('$Par_Sql[5]') WHERE Cur_Int= $Par_Sql[6] AND Cur_Cod = $Par_Sql[7]";
			//echo $actua_labor_626;
			return $actua_labor_626;
			break;
			
			case 627:
			/* Actualiza los cursos de capacitacion */
			$actua_capa=" UPDATE det_ccapac SET Pas_Cod=$Par_Sql[0], Tca_Cod=$Par_Sql[1], 
			Cur_Ins=UPPER('$Par_Sql[2]'),Cur_Tit=UPPER('$Par_Sql[3]'), Cur_Ini='$Par_Sql[4]', Cur_Fin='$Par_Sql[5]', Cur_Obs=UPPER('$Par_Sql[6]') 
			 WHERE Cur_Int= $Par_Sql[7] AND Cur_Cod = $Par_Sql[8]";
			//echo $actua_capa;
			return $actua_capa;
			break;
			
			/* Actualiza el titulo principal como NO PRINCIPAL */
			case 628:
			$actua_nivelacad_628=" UPDATE det_cestud SET Cur_Def='$Par_Sql[0]' WHERE Cur_Int= $Par_Sql[1] AND Cur_Cod = $Par_Sql[2]";
			//echo $actua_nivelacad_628;
			return $actua_nivelacad_628;
			break;
			
			/* Elimina los titulos de los estudiantes */
			case 630:
			$elim_academ_630=" DELETE FROM det_cestud WHERE Cur_Int=$Par_Sql[0] AND Cur_Cod=$Par_Sql[1] AND Nia_Cod=$Par_Sql[2] 
			ANd Pas_Cod=$Par_Sql[3]";
			//echo $elim_academ_630;
			return $elim_academ_630;
			break;
			
			case 631:
			/* Elimina la experiencia laboral */
			$elim_labor_631 =" DELETE FROM det_clabor WHERE Cur_Int=$Par_Sql[0] AND Cur_Cod=$Par_Sql[1] AND Pas_Cod=$Par_Sql[2] ";
			//echo $elim_labor_631;
			return $elim_labor_631;
			break;
			
			case 632:
			/* Elimina los cursos de capacitacion */
			$elim_capac_632 ="DELETE from det_ccapac where Cur_Int=$Par_Sql[0] and Cur_Cod=$Par_Sql[1] ANd Tca_Cod=$Par_Sql[2]
			AND Pas_Cod =$Par_Sql[3] ";
			//echo $elim_capac_632;
			return $elim_capac_632;
			break;
			
			case 638:
			/* Consulta el maximo de titulos en un curriculo */
			$con_cur_638="SELECT MAX(det_cestud.Cur_Int) as maximo FROM det_cestud WHERE Cur_Cod= $Par_Sql[0] ";
			return $con_cur_638;
			break;
			
			case 639:
			/* Consulta el maximo de la experiencia laboral en un curriculo */
			$con_labor_639="SELECT MAX(det_clabor.Cur_Int) as maximo FROM det_clabor WHERE Cur_Cod= $Par_Sql[0]  ";
			//echo $con_labor_639;
			return $con_labor_639;
			break;
			
			case 640:
			/* Consulta el maximo de las capacitaciones en un curriculo */
			$con_capac_640="SELECT MAX(det_ccapac.Cur_Int) as maximo FROM det_ccapac WHERE Cur_Cod= $Par_Sql[0]  ";
			//echo $con_capac_640;
			return $con_capac_640;
			break;
			
			/*Selecciona un titulo del personal que se encuentre por defecto D */
			case 641:
			$actua_nivelacad_641 =" SELECT Cur_Int from det_cestud WHERE Cur_Cod=$Par_Sql[0] AND Cur_Def='D' ";
			//echo $actua_nivelacad_641;
			return $actua_nivelacad_641;
			break;
			
			case 643:
			/* Consulta los paises */
			$sql_pai="SELECT Pas_Cod, Pas_Nom FROM pais ORDER BY Pas_Nom ASC";
			return $sql_pai;
			break;
        }
}
?>