<?php	
	function sentencias_com($id,$Par_Sql)
	{
	   	switch($id)
		{
		case 1:
		/**
		* Consulta las modalidades 
		*/
		$sql = "SELECT Mod_Cod, Mod_Des FROM modalidad WHERE Mod_Est='A'";
		return $sql;
		break;

		/* Consulta de etapas acad�micas de tipo NIVELACION */
		case 2:
		$consulta_etapas_2="SELECT etapas.Eta_Cod, etapas.Eta_Rec, etapas.Eta_Des FROM etapas WHERE etapas.Eta_Rec<>0 AND etapas.Eta_Est='A' ORDER BY etapas.Eta_Des";
		return $consulta_etapas_2;
		break;

		/**
		* Consulta de etapas
		*/
		case 3:
		$sql="SELECT Eta_Cod, Eta_Rec, Eta_Des FROM etapas WHERE Eta_Est='A' ORDER BY etapas.Eta_Des ASC";
		return $sql;
		break;

		/* Consultar los periodos y fechas de periodos de matriculacion */
		case 4:
		$sql="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
			IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 
			'Mayo', IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 
			'Septiembre', IF (MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, 
			YEAR(Per_Fef) as Ann_Fin, IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
			MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
			IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
			'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin
			FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int) INNER JOIN sucursal ON (periodos.Suc_Cod = 
			sucursal.Suc_Cod) WHERE periodos.Eta_Cod = $Par_Sql[0] AND periodos.Mod_Cod = $Par_Sql[1] AND ('$Par_Sql[2]' BETWEEN 
			perio_matr.Pem_Ini AND perio_matr.Pem_Fin) AND periodos.Suc_Cod = $Par_Sql[3]";
			return $sql;
		break;

		/* Seleccion de la carrera en base a la etapa para la inscripci�n omitiendo a las que se inscribio */
		case 5:
		$consulta_carreras_5="SELECT carreras.Car_Int, carreras.Car_Nom FROM carreras WHERE carreras.Eta_Cod=$Par_Sql[0] AND carreras.Car_Est='A' AND
carreras.Car_Int NOT IN (SELECT Car_Int FROM incritodet WHERE Est_Int = $Par_Sql[1] AND Per_Int = $Par_Sql[2])
ORDER BY  carreras.Car_Nom ASC";
		//echo $consulta_carreras_5;
		return $consulta_carreras_5;
		break;

		/* Consultar las carreras por empresa */
		case 6:
		$consulta_carreras_6="SELECT carreras.Car_Nom, carreras.Car_Int FROM carreras, escuelas WHERE carreras.Eta_Cod=$Par_Sql[0] AND carreras.Car_Est='A'
 AND escuelas.Esc_Int = carreras.Esc_Int  AND escuelas.Emp_Cod =  $Par_Sql[1] AND carreras.Car_Est = 'A' AND escuelas.Esc_Est = 'A' ORDER BY carreras.Car_Nom";	
		//echo $consulta_carreras_6;
		return $consulta_carreras_6;
		break;

		/* Consulta de semestres por el periodo y la carrera **/		
		case 7:
		$cargar_semestres_7="SELECT semestres.Sem_Cod, IF (Sem_Ver = 'N',CONCAT(niveles.Niv_Des,' {',Sem_Par,'} ',IF (Sem_Sec = 'D', 'Diurna', 
						IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_Des) as Sem_Nom, IF (Sem_Ver = 'N',CONCAT(niveles.Niv_Des,' {',Sem_Par,'} ',IF (Sem_Sec = 'D', 'Diurna', 
						IF (Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_De2) as Sem_No2 FROM niveles INNER JOIN semestres ON 
						(niveles.Niv_Cod = semestres.Niv_Cod) INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
						INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
						INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
						INNER JOIN carreras ON (promocione.Car_Int = carreras.Car_Int)
						WHERE promocione.Car_Int = $Par_Sql[0] AND semestres.Per_Int = $Par_Sql[1] AND periodos.Mod_Cod = $Par_Sql[2]
						ORDER BY semestres.Niv_Cod, semestres.Sem_Par, Sem_Sec";
		//echo $cargar_semestres_7;
		return $cargar_semestres_7;
		break;

		/* Consultar las carreras */
		case 8:
		$consulta_carreras_8="SELECT carreras.Car_Nom, carreras.Car_Int FROM carreras, escuelas 
		WHERE carreras.Eta_Cod='$Par_Sql[0]' AND carreras.Car_Est='A' AND escuelas.Esc_Int =
		carreras.Esc_Int 
		ORDER BY carreras.Car_Nom";	
		//echo $consulta_carreras_8;
		return $consulta_carreras_8;
		break;

		/* Consultar las carreras ojojojojoj se debe borrar o pasar a tesoreria*/
		case 9:
		$consulta_anios_9="SELECT YEAR(caja_aper.Caj_Fec) AS Anio FROM caja_aper INNER JOIN ventas ON (caja_aper.Caj_Cod = ventas.Caj_Cod)
							WHERE caja_aper.Pun_Cod = $Par_Sql[0] GROUP BY Anio ORDER BY Anio DESC";	
		//echo $consulta_anios_9;
		return $consulta_anios_9;
		break;
		
		/**
		* Consulta d todos los periodos activos 
		*/
		case 10:
		$cargar_per_10 = "SELECT Pec_Cod, Pec_Fei, Pec_Fef, Pec_Est, Year(Pec_Fei) as Periodo FROM perio_cont, plan_cuenta WHERE perio_cont.Pla_Cod = plan_cuenta.Pla_Cod AND Pec_Est = 'A' AND plan_cuenta.Emp_Cod= $Par_Sql[0] ORDER BY Pec_Fei Desc";
		//echo $cargar_per_10;
		return $cargar_per_10;
		break;
		
		/* Cargado de la b�squeda de cuentas en la p�gina de registro de comprobantes (Revisar la variable de sesi�n Emp_Cod */
		/* Busqueda de cuentas por descripcion */
		case 11:
		$bus_xmld_11="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Des LIKE '%$Par_Sql[0]%' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D' ORDER BY Pld_Cod";
//echo $bus_xmld_11;
		return $bus_xmld_11;	
		break;
			
		/* Busqueda de cuentas por codigo */
		case 12:
		$bus_xmlc_12="SELECT det_plan.Pld_Cod, det_plan.Pld_Cdc, Pld_Rec, det_plan.Pld_Des, empresas.Emp_Nom, Pla_Obs, IF (Pld_Tip='G', 'Grupo', 'Detalle') as Pld_Tip, IF (Pld_Est='A', 'Activa', 'Inactiva') as Pld_Est FROM det_plan, plan_cuenta, empresas WHERE plan_cuenta.Pla_Cod=det_plan.Pla_Cod AND plan_cuenta.Emp_Cod=empresas.Emp_Cod AND plan_cuenta.Emp_Cod=$Par_Sql[1] AND plan_cuenta.Pla_Est='A' AND det_plan.Pld_Cdc = '$Par_Sql[0]' AND det_plan.Pla_Cod = $Par_Sql[2] AND Pld_Tip = 'D'";
		//echo $bus_xmlc_12;
		return $bus_xmlc_12;	
		break;

		case 13:		
		/* Consulta los semestres asignados a un docente en base al periodo y carrera */
		$consul_semestres_13="SELECT DISTINCT 
  view_cursos_mal.Sem_Cod,
  view_cursos_mal.Sem_Nom,
  view_cursos_mal.Sem_No2,
  carreras.Car_Nom
FROM
  view_cursos_mal
  INNER JOIN distributi ON (view_cursos_mal.Sem_Cod = distributi.Sem_Cod)
  INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
  INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
WHERE
  personal.Prs_Cod = $Par_Sql[0] AND 
  view_cursos_mal.Per_Int = $Par_Sql[1]
ORDER BY
  view_cursos_mal.Niv_Cod"; 
 // echo $consul_semestres_13;										 
		return $consul_semestres_13;
		break;

  /* Consulta los periodos en las fechas de inicio y fin de clases */
  case 14: 
 $con_fec_periodos_14= 
 "SELECT DISTINCT 
  view_periodos_suc.Per_Int,
  view_periodos_suc.Suc_Des,
  view_periodos_suc.Ann_Ini,
  view_periodos_suc.Mes_Ini,
  view_periodos_suc.Ann_Fin,
  view_periodos_suc.Mes_Fin,
  etapas.Eta_Des
FROM
  view_periodos_suc
  INNER JOIN etapas ON (view_periodos_suc.Eta_Cod = etapas.Eta_Cod)
WHERE
  view_periodos_suc.Suc_Cod = $Par_Sql[0] AND 
  view_periodos_suc.Mod_Cod = $Par_Sql[1] AND 
  ('$Par_Sql[2]' BETWEEN view_periodos_suc.Per_Fea AND view_periodos_suc.Per_Fec)
ORDER BY
  view_periodos_suc.Per_Fea DESC";
  
  echo $con_fec_periodos_14;
  return $con_fec_periodos_14;
  break;

  case 15:
  /* Consulta de los distributivos en base al semestres y el docente */
  $consul_distri_15 = "SELECT distributi.Dis_Cod, Asi_Des, Dis_Sub FROM distributi, asignatura, personal WHERE distributi.Asi_Int
       = asignatura.Asi_Int AND distributi.Sem_Cod = $Par_Sql[0] AND distributi.Per_Cod = personal.Per_Cod AND personal.Prs_Cod = $Par_Sql[1]";
      // echo  $consul_distri_15;
  return $consul_distri_15;
  break;
		
		case 16:
		/**
		* Consulta la etapa en base al periodo
		*/
		$sql = "SELECT Eta_Cod FROM periodos WHERE Per_Int = $Par_Sql[0]";
		return $sql;
		break;

		/* 
		* Consultar las sucursales 
		*/
		case 101:
		$sql="SELECT sucursal.Suc_Des, sucursal.Suc_Cod  FROM sucursal WHERE Suc_Est = 'A' AND Emp_Cod = ".(isset($Par_Sql[0])?$Par_Sql[0]:'');
		return $sql;
		break;

	
		case 102:		
		/**
		* Consulta las carreras en base a al periodo y etapa 
		*/
         $sql= "SELECT carreras.Car_Int, carreras.Car_Nom FROM semestres,promocione,carreras WHERE semestres.Pro_Cod=promocione.Pro_Cod 
AND carreras.Car_Int= promocione.Car_Int AND semestres.Per_Int='$Par_Sql[1]' 
AND carreras.Eta_Cod='$Par_Sql[0]' 
AND carreras.Car_Est='A' 
GROUP BY carreras.Car_Int ORDER BY carreras.Car_Nom";
		return $sql;
		break;		





		/*** Consulto todos los peridos de matriculas ********/
		/* Consultar los periodos y fechas de periodos de matriculacion */
		case 103:
		$con_fec_periodos_103="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
			IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 
			'Mayo', IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 
			'Septiembre', IF (MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, 
			YEAR(Per_Fef) as Ann_Fin, IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
			MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
			IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
			'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin
			FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int) INNER JOIN sucursal ON (periodos.Suc_Cod = 
			sucursal.Suc_Cod) WHERE periodos.Eta_Cod = $Par_Sql[0] AND periodos.Mod_Cod = $Par_Sql[1]
			 AND ('$Par_Sql[2]' >= perio_matr.Pem_Ini) AND periodos.Suc_Cod = $Par_Sql[3]
			  GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea DESC";
			//echo $con_fec_periodos_103;
		return $con_fec_periodos_103;
		break;		
		/*** consultar las carreras en base a la etapa ***/
		case 104:
		$consultar_carrera_etapa_104=" SELECT carreras.Car_Int, carreras.Car_Nom FROM carreras WHERE carreras.Eta_Cod='$Par_Sql[0]' 
  AND carreras.Car_Est='A'  ORDER BY carreras.Car_Nom ASC";
  		return $consultar_carrera_etapa_104;
		break;

		/*** Consulta los periodos en las fechas de inicio y fin **************/
		/*case 105:
		$con_fec_periodos_105="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
			IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 
			'Mayo', IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 
			'Septiembre', IF (MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, 
			YEAR(Per_Fef) as Ann_Fin, IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
			MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
			IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
			'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin
			FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int) INNER JOIN sucursal ON (periodos.Suc_Cod = 
			sucursal.Suc_Cod) WHERE periodos.Eta_Cod = $Par_Sql[0] AND periodos.Mod_Cod = $Par_Sql[1] AND ('$Par_Sql[2]' BETWEEN 
			perio_matr.Pem_Ini AND periodos.Per_Fec) AND periodos.Suc_Cod = $Par_Sql[3] GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea  DESC ";
			//echo $con_fec_periodos_105;
		return $con_fec_periodos_105;
		break;*/
		
		/*** Consulta los periodos en las fechas de inicio y fin **************/
		case 105:
		$con_fec_periodos_105="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) as Ann_Ini, IF (MONTH(Per_Fea)=1,'Enero', 
			IF (MONTH(Per_Fea)=2, 'Febrero', IF (MONTH(Per_Fea)=3, 'Marzo', IF (MONTH(Per_Fea)=4, 'Abril', IF (MONTH(Per_Fea)=5, 
			'Mayo', IF(MONTH(Per_Fea)=6, 'Junio', IF (MONTH(Per_Fea)=7,'Julio', IF (MONTH(Per_Fea)=8, 'Agosto', IF (MONTH(Per_Fea)=9, 
			'Septiembre', IF (MONTH(Per_Fea)=10, 'Octubre', IF (MONTH(Per_Fea)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Ini, 
			YEAR(Per_Fef) as Ann_Fin, IF (MONTH(Per_Fef)=1,'Enero', IF (MONTH(Per_Fef)=2, 'Febrero', IF (
			MONTH(Per_Fef)=3, 'Marzo', IF (MONTH(Per_Fef)=4, 'Abril', IF (MONTH(Per_Fef)=5, 'Mayo', IF(MONTH(Per_Fef)=6, 'Junio',
			IF (MONTH(Per_Fef)=7,'Julio', IF (MONTH(Per_Fef)=8, 'Agosto', IF (MONTH(Per_Fef)=9, 'Septiembre', IF (MONTH(Per_Fef)=10,
			'Octubre', IF (MONTH(Per_Fef)=11, 'Noviembre', 'Diciembre'))))))))))) as Mes_Fin
			FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int >= perio_matr.Per_Int) INNER JOIN sucursal ON (periodos.Suc_Cod >= 
			sucursal.Suc_Cod) WHERE periodos.Eta_Cod = $Par_Sql[0] AND periodos.Mod_Cod = $Par_Sql[1] AND ('$Par_Sql[2]' BETWEEN 
			perio_matr.Pem_Ini AND periodos.Per_Fec) AND periodos.Suc_Cod = $Par_Sql[3] GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea  DESC ";
		//echo $con_fec_periodos_105;
		return $con_fec_periodos_105;
		break;
		

		/** Consulto los paises de la base de datos *********/
		case 106:
		$con_pais_106="SELECT Pas_Cod, Pas_Nom, Pas_Nac FROM pais WHERE Pas_Est='A'";
		return $con_pais_106;
		break;
		/** consulta de provincias */
		case 107:
		$con_provincias_107="SELECT Pro_Cod, Pro_Nom FROM provincia WHERE Reg_Cod='$Par_Sql[0]'";
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

	/* Consultar la descripci�n de las relaciones comunes */
		case 111:
		$niveles_111 = "SELECT Rel_Des, Rel_Cod FROM relacion WHERE Rel_Des LIKE '%$Par_Sql[0]%' ";
		//echo $niveles_111;
		return $niveles_111;
		break;

		case 112:		
		/**
		* Consulta las carreras en base a al periodo y etapa sin toman en cuenta el curso
		*/
         $sql= "SELECT carreras.Car_Int, carreras.Car_Nom FROM carreras, escuelas WHERE escuelas.Esc_Int = carreras.Esc_Int AND 
 carreras.Car_Est='A' AND escuelas.Emp_Cod = $Par_Sql[0] AND carreras.Eta_Cod = $Par_Sql[1] ORDER BY carreras.Car_Nom";
		return $sql;
		break;		


		/*** consulta para la distribucion de costos ***/
		/*Consulta los tipos de distribnbucion*/
		case 201:
		$consultar_cuenta=" SELECT Tdc_Cod, Tdc_Des FROM tipo_distr ORDER BY Tdc_Des";
  		return $consultar_cuenta;
		break;

		/*Consulta la distribuci�n de los costos*/
		case 202:
		$consultar_costos="SELECT dist_costo.Pld_Cod, dist_costo.Tdc_Cod, dist_costo.Tdc_Por, det_plan.Pld_Des, det_plan.Pld_Cdc, det_plan.Pld_Rec 
		FROM dist_costo, det_plan WHERE det_plan.Pld_Cod= dist_costo.Pld_Cod AND dist_costo.Tdc_Cod= $Par_Sql[0]";
  		//echo $consultar_costos;
		return $consultar_costos;
		break;
		
		/*Consulta plan de cuentas*/
		case 203:
		$consultar_plan="SELECT Pla_Cod, YEAR(Pla_Fec) AS Ann FROM plan_cuenta WHERE Pla_Est='A' ORDER BY Ann desc";
  		//echo $consultar_plan;
		return $consultar_plan;
		break;

		//sentencias miriam consulta periodos de inscripcion
		case 204:
		$consultar_per_inscr="SELECT periodos.Per_Int, sucursal.Suc_Des, YEAR(Per_Fea) AS Ann_Ini, IF(MONTH(Per_Fea) = 1, 'Enero', IF(MONTH(Per_Fea) = 2, 'Febrero', IF(MONTH(Per_Fea) = 3, 'Marzo', IF(MONTH(Per_Fea) = 4, 'Abril', IF(MONTH(Per_Fea) = 5, 'Mayo', IF(MONTH(Per_Fea) = 6, 'Junio', IF(MONTH(Per_Fea) = 7, 'Julio', IF(MONTH(Per_Fea) = 8, 'Agosto', IF(MONTH(Per_Fea) = 9, 'Septiembre', IF(MONTH(Per_Fea) = 10, 'Octubre', IF(MONTH(Per_Fea) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Ini,
  		YEAR(Per_Fef) AS Ann_Fin, IF(MONTH(Per_Fef) = 1, 'Enero', IF(MONTH(Per_Fef) = 2, 'Febrero', IF(MONTH(Per_Fef) = 3, 'Marzo', IF(MONTH(Per_Fef) = 4, 'Abril', IF(MONTH(Per_Fef) = 5, 'Mayo', IF(MONTH(Per_Fef) = 6, 'Junio', IF(MONTH(Per_Fef) = 7, 'Julio', IF(MONTH(Per_Fef) = 8, 'Agosto', IF(MONTH(Per_Fef) = 9, 'Septiembre', IF(MONTH(Per_Fef) = 10, 'Octubre', IF(MONTH(Per_Fef) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Fin FROM periodos INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int)
 		 INNER JOIN sucursal ON (periodos.Suc_Cod = sucursal.Suc_Cod) INNER JOIN incritodet ON (periodos.Per_Int = incritodet.Per_Int)
		WHERE periodos.Eta_Cod = $Par_Sql[0] AND  periodos.Mod_Cod = $Par_Sql[1] AND '$Par_Sql[2]' >= perio_matr.Pem_Ini AND 
  		periodos.Suc_Cod = $Par_Sql[3] GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea DESC";
  		//echo $consultar_per_inscr;
		return $consultar_per_inscr;
		break;

		case 205:
		/**
		* Consulta del personal por cedula 
		*/
 		$sql = "SELECT personal.Per_Cod, personal.Per_Tit, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced FROM persona, personal WHERE personal.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ced = '$Par_Sql[0]' ORDER BY persona.Prs_Ape, persona.Prs_Nom ASC";
		return $sql;
		break;
		
		case 206:
		/**
		* Consulta del cliente si es una persona por apellidos 
		*/
		$sql = "SELECT personal.Per_Cod, personal.Per_Tit, persona.Prs_Nom, persona.Prs_Ape, persona.Prs_Ced FROM persona, personal WHERE personal.Prs_Cod = persona.Prs_Cod AND persona.Prs_Ape LIKE '%$Par_Sql[0]%' ORDER BY persona.Prs_Ape ASC";
		return $sql;
		break;

		/*
		* Consulta los periodos en las fechas de inicio y fin 
		*/
		case 300:
		$sql="SELECT 
  periodos.Per_Int,
  YEAR(Per_Fea) AS Ann_Ini,
  IF(MONTH(Per_Fea) = 1, 'Enero', IF(MONTH(Per_Fea) = 2, 'Febrero', IF(MONTH(Per_Fea) = 3, 'Marzo', IF(MONTH(Per_Fea) = 4, 'Abril', IF(MONTH(Per_Fea) = 5, 'Mayo', IF(MONTH(Per_Fea) = 6, 'Junio', IF(MONTH(Per_Fea) = 7, 'Julio', IF(MONTH(Per_Fea) = 8, 'Agosto', IF(MONTH(Per_Fea) = 9, 'Septiembre', IF(MONTH(Per_Fea) = 10, 'Octubre', IF(MONTH(Per_Fea) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Ini,
  YEAR(Per_Fef) AS Ann_Fin,
  IF(MONTH(Per_Fef) = 1, 'Enero', IF(MONTH(Per_Fef) = 2, 'Febrero', IF(MONTH(Per_Fef) = 3, 'Marzo', IF(MONTH(Per_Fef) = 4, 'Abril', IF(MONTH(Per_Fef) = 5, 'Mayo', IF(MONTH(Per_Fef) = 6, 'Junio', IF(MONTH(Per_Fef) = 7, 'Julio', IF(MONTH(Per_Fef) = 8, 'Agosto', IF(MONTH(Per_Fef) = 9, 'Septiembre', IF(MONTH(Per_Fef) = 10, 'Octubre', IF(MONTH(Per_Fef) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Fin,
  etapas.Eta_Des, periodos.Eta_Cod
FROM
  periodos
  INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int)
  INNER JOIN etapas ON (periodos.Eta_Cod = etapas.Eta_Cod)
WHERE
  periodos.Mod_Cod = $Par_Sql[0] AND 
  '$Par_Sql[1]' BETWEEN perio_matr.Pem_Ini AND periodos.Per_Fec AND 
  periodos.Suc_Cod = $Par_Sql[2]
GROUP BY
  periodos.Per_Int
ORDER BY
  periodos.Per_Fea DESC";
		//echo $sql;
		return $sql;
		break;

case 301:
   $sql = "SELECT 
     semestres.Sem_Cod,
     IF(Sem_Ver = 'N', CONCAT(niveles.Niv_Des, ' [', Sem_Par, '] ', IF(Sem_Sec = 'D', 'Diurna', IF(Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_Des) AS Sem_Nom,
     IF(Sem_Ver = 'N', CONCAT(niveles.Niv_Des, ' [', Sem_Par, '] ', IF(Sem_Sec = 'D', 'Diurna', IF(Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_De2) AS Sem_No2
   FROM
     niveles
     INNER JOIN semestres ON (niveles.Niv_Cod = semestres.Niv_Cod)
     INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
     INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
     INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
   WHERE
     promocione.Car_Int = '$Par_Sql[0]' AND 
     semestres.Per_Int = '$Par_Sql[1]' AND 
     periodos.Mod_Cod = '$Par_Sql[2]' AND semestres.Sem_Cod NOT IN (SELECT incritodet.Sem_Cod FROM incritodet 
           WHERE incritodet.Est_Int = '$Par_Sql[3]')
   ORDER BY
    semestres.Niv_Cod;";
  return $sql;
  
  		case 302:
		/**
		* Consulta las modalidades 
		*/
		$sql = "SELECT DISTINCT modalidad.Mod_Cod, modalidad.Mod_Des FROM modalidad INNER JOIN periodos ON (modalidad.Mod_Cod = periodos.Mod_Cod)
WHERE modalidad.Mod_Est ='A' AND periodos.Suc_Cod = $Par_Sql[0]";
		//echo $sql;
		return $sql;
		break;

		/**
		* Consulto todos los peridos de matriculas 		
		* Consultar los periodos y fechas de periodos de matriculacion 
		*/
		case 303:
		$con_fec_periodos_303="SELECT 
  periodos.Per_Int,
  sucursal.Suc_Des,
  YEAR(Per_Fea) AS Ann_Ini,
  IF(MONTH(Per_Fea) = 1, 'Enero', IF(MONTH(Per_Fea) = 2, 'Febrero', IF(MONTH(Per_Fea) = 3, 'Marzo', IF(MONTH(Per_Fea) = 4, 'Abril', IF(MONTH(Per_Fea) = 5, 'Mayo', IF(MONTH(Per_Fea) = 6, 'Junio', IF(MONTH(Per_Fea) = 7, 'Julio', IF(MONTH(Per_Fea) = 8, 'Agosto', IF(MONTH(Per_Fea) = 9, 'Septiembre', IF(MONTH(Per_Fea) = 10, 'Octubre', IF(MONTH(Per_Fea) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Ini,
  YEAR(Per_Fef) AS Ann_Fin,
  IF(MONTH(Per_Fef) = 1, 'Enero', IF(MONTH(Per_Fef) = 2, 'Febrero', IF(MONTH(Per_Fef) = 3, 'Marzo', IF(MONTH(Per_Fef) = 4, 'Abril', IF(MONTH(Per_Fef) = 5, 'Mayo', IF(MONTH(Per_Fef) = 6, 'Junio', IF(MONTH(Per_Fef) = 7, 'Julio', IF(MONTH(Per_Fef) = 8, 'Agosto', IF(MONTH(Per_Fef) = 9, 'Septiembre', IF(MONTH(Per_Fef) = 10, 'Octubre', IF(MONTH(Per_Fef) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Fin,
  etapas.Eta_Des,  etapas.Eta_Cod
FROM
  periodos
  INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int)
  INNER JOIN sucursal ON (periodos.Suc_Cod = sucursal.Suc_Cod)
  INNER JOIN etapas ON (periodos.Eta_Cod = etapas.Eta_Cod) WHERE periodos.Mod_Cod = $Par_Sql[0]
			 AND ('$Par_Sql[1]' >= perio_matr.Pem_Ini) AND periodos.Suc_Cod = $Par_Sql[2]
			  GROUP BY periodos.Per_Int ORDER BY periodos.Per_Fea DESC";
			//echo $con_fec_periodos_303;
		return $con_fec_periodos_303;
		break;
				
		case 304:
		/* 
		* Consulta la descripcion de la recusividad de una sub-cuenta 
		*/
		$consul_recur = "SELECT det_plan.Pld_Rec, det_plan.Pld_Cdc, Pld_Des FROM det_plan WHERE det_plan.Pld_Cod = '$Par_Sql[0]'";
		//echo $consul_recur;
		return $consul_recur;
		break;

		/**
	   * Consulta los periodos en las fechas de inicio y fin de clases
	   */
	  case 305:
	   $sql=
		"SELECT DISTINCT
		view_periodos_suc.Per_Int,
		view_periodos_suc.Suc_Des,
		view_periodos_suc.Ann_Ini,
		view_periodos_suc.Mes_Ini,
		view_periodos_suc.Ann_Fin,
		view_periodos_suc.Mes_Fin,
		etapas.Eta_Des
		FROM
		view_periodos_suc
		INNER JOIN etapas ON (view_periodos_suc.Eta_Cod = etapas.Eta_Cod)
		WHERE
		view_periodos_suc.Suc_Cod = $Par_Sql[0] AND
		view_periodos_suc.Mod_Cod = $Par_Sql[1] AND
		('$Par_Sql[2]' BETWEEN view_periodos_suc.Per_Fea AND view_periodos_suc.Per_Fec)
		ORDER BY view_periodos_suc.Per_Fea DESC";
		return $sql;
		break;
   
		/**
		* Consulta los semestres asignados a un docente en base al periodo y carrera
		 */
		 case 306:
	   $sql="SELECT DISTINCT
	   view_cursos_mal.Sem_Cod,
	   view_cursos_mal.Sem_Nom,
	   view_cursos_mal.Sem_No2,
	   carreras.Car_Nom
	   FROM
	   view_cursos_mal
	   INNER JOIN distributi ON (view_cursos_mal.Sem_Cod = distributi.Sem_Cod)
	   INNER JOIN personal ON (distributi.Per_Cod = personal.Per_Cod)
	   INNER JOIN carreras ON (view_cursos_mal.Car_Int = carreras.Car_Int)
	   WHERE
	   personal.Prs_Cod = $Par_Sql[0] AND
	   view_cursos_mal.Per_Int = $Par_Sql[1]
	   ORDER BY
	   view_cursos_mal.Niv_Cod";
	   return $sql;
	   break;	

		case 307:
		$sql = "SELECT 
		 semestres.Sem_Cod,
		 IF(Sem_Ver = 'N', CONCAT(niveles.Niv_Des, ' [', Sem_Par, '] ', IF(Sem_Sec = 'D', 'Diurna', IF(Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_Des) AS Sem_Nom,
		 IF(Sem_Ver = 'N', CONCAT(niveles.Niv_Des, ' [', Sem_Par, '] ', IF(Sem_Sec = 'D', 'Diurna', IF(Sem_Sec = 'V', 'Vespertina', 'Nocturna'))), semestres.Sem_De2) AS Sem_No2
		FROM
		 niveles
		 INNER JOIN semestres ON (niveles.Niv_Cod = semestres.Niv_Cod)
		 INNER JOIN periodos ON (semestres.Per_Int = periodos.Per_Int)
		 INNER JOIN modalidad ON (periodos.Mod_Cod = modalidad.Mod_Cod)
		 INNER JOIN promocione ON (semestres.Pro_Cod = promocione.Pro_Cod)
		WHERE
		 promocione.Car_Int = '$Par_Sql[0]' AND 
		 semestres.Per_Int = '$Par_Sql[1]' AND 
		 periodos.Mod_Cod = '$Par_Sql[2]')
		ORDER BY
		semestres.Niv_Cod;";
		return $sql;

		/**
		* Consulta los periodos antes de las fechas
		*/
		case 308:
		$sql="SELECT 
  periodos.Per_Int,
  YEAR(Per_Fea) AS Ann_Ini,
  IF(MONTH(Per_Fea) = 1, 'Enero', IF(MONTH(Per_Fea) = 2, 'Febrero', IF(MONTH(Per_Fea) = 3, 'Marzo', IF(MONTH(Per_Fea) = 4, 'Abril', IF(MONTH(Per_Fea) = 5, 'Mayo', IF(MONTH(Per_Fea) = 6, 'Junio', IF(MONTH(Per_Fea) = 7, 'Julio', IF(MONTH(Per_Fea) = 8, 'Agosto', IF(MONTH(Per_Fea) = 9, 'Septiembre', IF(MONTH(Per_Fea) = 10, 'Octubre', IF(MONTH(Per_Fea) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Ini,
  YEAR(Per_Fef) AS Ann_Fin,
  IF(MONTH(Per_Fef) = 1, 'Enero', IF(MONTH(Per_Fef) = 2, 'Febrero', IF(MONTH(Per_Fef) = 3, 'Marzo', IF(MONTH(Per_Fef) = 4, 'Abril', IF(MONTH(Per_Fef) = 5, 'Mayo', IF(MONTH(Per_Fef) = 6, 'Junio', IF(MONTH(Per_Fef) = 7, 'Julio', IF(MONTH(Per_Fef) = 8, 'Agosto', IF(MONTH(Per_Fef) = 9, 'Septiembre', IF(MONTH(Per_Fef) = 10, 'Octubre', IF(MONTH(Per_Fef) = 11, 'Noviembre', 'Diciembre'))))))))))) AS Mes_Fin,
  etapas.Eta_Des, periodos.Eta_Cod
FROM
  periodos
  INNER JOIN perio_matr ON (periodos.Per_Int = perio_matr.Per_Int)
  INNER JOIN etapas ON (periodos.Eta_Cod = etapas.Eta_Cod)
WHERE
  periodos.Mod_Cod = $Par_Sql[0] AND 
  '$Par_Sql[1]' < periodos.Per_Fec AND 
  periodos.Suc_Cod = $Par_Sql[2]
GROUP BY
  periodos.Per_Int
ORDER BY
  periodos.Per_Fea DESC";
		//echo $sql;
		return $sql;
		break;

		}
	}
?> 