<?php
	/**
	 *  ACTIVOS FIJOS 
	 */
	function sentencias_con($id,$Par_Sql)
	{
		switch($id)
		{								
			/**
			 * Insertar  la baja de un activo determinado
			 */
			 case 1:
			$sql="INSERT INTO baja_activo
					(Act_Cod,
					 Baj_Fec,
					 Baj_Fba,
					 Baj_Inf,
					 Baj_Des,
					 Baj_Qui,
					 Baj_Val)					
					VALUE 
					($Par_Sql[0],'$Par_Sql[1]','$Par_Sql[2]',UPPER('$Par_Sql[3]'),'$Par_Sql[4]','$Par_Sql[5]',$Par_Sql[6])";
				//echo $sql;
			return $sql;
			break;	
			
			/**
			 * Actualiza estado del Activo
			 */
			case 2:
			$sql="UPDATE activo SET Act_Est = 'I' 
					WHERE 
					Act_Cod=$Par_Sql[0]";
				//echo $sql;
			return $sql;
			break;	
			
				
			/**
			 * Conaulta los Activos
			 */
			case 3: 
			$conact = "SELECT 
							Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, 
							tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est
					   FROM 
					   		activo, tipo_activo 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND 
							Act_Des LIKE UPPER('%$Par_Sql[0]%')	AND
							tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			
			/**
			 * Conaulta los Activos registrados como baja. 
			 */
			case 4: 
			$conact = "SELECT 
							activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, 
							tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est, baja_activo.Baj_Cod
					   FROM 
					   		activo, tipo_activo, baja_activo 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND 
							Act_Des LIKE UPPER('%$Par_Sql[0]%')	AND
							baja_activo.Act_Cod=activo.Act_Cod AND
							tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			
			/**
			 * Conaulta los Activos registrados como baja segun el codigo del activo. 
			 */
			case 5: 
			$conact = "SELECT 
							activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, estado_act.Est_Cod, Prv_Cod, Act_Des, Act_Obs, 
							tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est, baja_activo.Baj_Cod,baja_activo.Baj_Fec,baja_activo.Baj_Inf,baja_activo.Baj_Fba,baja_activo.Baj_Des,baja_activo.Baj_Qui,
							baja_activo.Baj_Val,baja_activo.Baj_Est,estado_act.Est_Des
							
					   FROM 
					   		activo, tipo_activo, baja_activo,estado_act 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND 							
							baja_activo.Act_Cod=activo.Act_Cod AND
							activo.Est_Cod=estado_act.Est_Cod AND
							activo.Act_Cod =$Par_Sql[0] AND
							tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
		
				
			/**
			 * Actualiza estado del activo  por baja del activo.
			 */
			case 6: 
			$consuc = "UPDATE activo  SET
							activo.Est_Cod = $Par_Sql[0]
						
					   WHERE 
					   		activo.Act_Cod =  $Par_Sql[1]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
			 * Conaulta los Activos registrados como baja segun el estado del activo. 
			 */
			case 7: 
			$conact = "SELECT 
							activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, estado_act.Est_Cod, Prv_Cod, Act_Des, Act_Obs, 
							tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est, baja_activo.Baj_Cod,baja_activo.Baj_Fec,baja_activo.Baj_Inf,baja_activo.Baj_Fba,baja_activo.Baj_Des,baja_activo.Baj_Qui,
							baja_activo.Baj_Val,baja_activo.Baj_Est,estado_act.Est_Des
							
					   FROM 
					   		activo, tipo_activo, baja_activo,estado_act 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND 							
							baja_activo.Act_Cod=activo.Act_Cod AND
							activo.Est_Cod=estado_act.Est_Cod AND
							activo.Est_Cod= $Par_Sql[0] AND
							tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;			
			
			/**
			 * Conaulta los Activos registrados como baja por rango de fecha de  registro. 
			 */
			case 8: 
			$conact = "SELECT 
							activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, estado_act.Est_Cod, Prv_Cod, Act_Des, Act_Obs, 
							tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Est, baja_activo.Baj_Cod,baja_activo.Baj_Fec,baja_activo.Baj_Inf,baja_activo.Baj_Fba,baja_activo.Baj_Des,baja_activo.Baj_Qui,
							baja_activo.Baj_Val,baja_activo.Baj_Est,estado_act.Est_Des
							
					   FROM 
					   		activo, tipo_activo, baja_activo,estado_act 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND 							
							baja_activo.Act_Cod=activo.Act_Cod AND
							activo.Est_Cod=estado_act.Est_Cod AND
							baja_activo.Baj_Fec >= '$Par_Sql[0]' AND
							baja_activo.Baj_Fec <= '$Par_Sql[1]' AND
							tipo_activo.Emp_Cod=$Par_Sql[2]";
			//echo $conact;
			return $conact;
			break;

			
			/**
			 * Consulta el estdo del activo por código del estado
			 */
			case 9: 
			$consuc = "SELECT 
						Est_Des
						FROM 
							estado_act
						WHERE 
							 Est_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			
			



		
			
			
			
			/**
			 * Consulta las sucursales
			 */
			case 422: 
			$consuc = "SELECT 
						Suc_Cod, Suc_Des 
					   FROM 
					   	sucursal 
					   WHERE 
					   	Suc_Est = 'A' AND Emp_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			/**
			 * Consulta los estados
			 */
			case 423: 
			$consuc = "SELECT Est_Cod, Est_Des 
					   FROM estado_act 
					   WHERE Est_Est = 'A'";
			//echo $consuc;
			return $consuc;
			break;		
			
					
			/**
			 * Consulta el Activo  por codigo del activo
			 */
			case 431: 
			$conact = "SELECT 
							Act_Cod, activo.Tia_Cod, Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, 
							Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann, Act_Est 
					   FROM 
					   		activo, tipo_activo 
					   WHERE 
					   		activo.Tia_Cod = tipo_activo.Tia_Cod AND Act_Cod = $Par_Sql[0]";
			//echo $conact;
			return $conact;
			break;
	
			
			
			/**
			 * Consulta los Activos x Codigo Secuancial
			 */
			case 435: 
			$conact = "SELECT 
						Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar 
						FROM
						 activo, tipo_activo 
						WHERE 
						activo.Tia_Cod = tipo_activo.Tia_Cod AND
						Act_Cdc = '$Par_Sql[0]' AND 
						tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			
			/**
			 * Conaulta los Activos x Codigo de Barra
			 */
			case 436: 
			$conact = "SELECT 
						Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des,activo.Act_Est, Act_Obs, tipo_activo.Tia_Cdc, Act_Cdc, Act_Can, Act_Bar
					FROM
					 	activo, tipo_activo 
					WHERE 
						activo.Tia_Cod = tipo_activo.Tia_Cod AND
						Act_Bar =  Trim('$Par_Sql[0]') AND 
						tipo_activo.Emp_Cod=$Par_Sql[1]";
			//echo $conact;
			return $conact;
			break;
			

			
			/**
			 *  Consulta una seccion 
			 */
			case 465:
			$consec="SELECT 
						seccion.Dep_Cod, Sec_Cod, Sec_Des, Sec_Est, Dep_Des 
					FROM 
						seccion, departamen 
					WHERE 
						seccion.Sec_Est = 'A' AND 
						departamen.Dep_Cod = seccion.Dep_Cod AND 
						seccion.Sec_Cod = $Par_Sql[0]";
			//echo $consec;
			return $consec;
			break;
			
			/**
			 * Consulta las sucursales por codigo de sucursal.
			 */
			case 466: 
			$consuc = "SELECT 
						Suc_Cod, Suc_Des 
						FROM 
							sucursal
						WHERE 
							Suc_Est = 'A' AND Suc_Cod = $Par_Sql[0]";
			//echo $consuc;
			return $consuc;
			break;
			
			
			
			
		   
		   
		   
		 	
			/**
		     *Consulta de los campos  por tipo de Busqueda
		     */
		   case 470: 
			$sql = "SELECT 
					 campos_act.Cam_Cod,campos_act.Cam_Cor
					FROM
					 campos_act 
					WHERE
					 campos_act.Cam_Est= 'A' AND campos_act.Cam_Bus='S'";
					 //echo $sql;
		   return $sql;
		   break;
			
		    /**
		     * Consulta de los activos  por campos
		     */
		   case 471: 
		   $sql = "SELECT 
		   			activo.Act_Cod, activo.Tia_Cod,Tia_Des, Pri_Cod, Suc_Cod, Est_Cod, Act_Est, Prv_Cod, Act_Des, Act_Obs,
 		 tipo_activo.Tia_Cdc, Act_Cdc, Act_Can,det_activo.Act_val,campos_act.Cam_Cod, Act_Bar
		 			FROM 
						activo 
						INNER JOIN det_activo ON activo.Act_Cod= det_activo.Act_Cod
 		 				INNER JOIN tipo_activo ON activo.Tia_Cod= tipo_activo.Tia_Cod
                		INNER JOIN campos_act ON campos_act.Cam_Cod= det_activo.Cam_Cod
                	WHERE 
						campos_act.Cam_Cod= $Par_Sql[0]  AND det_activo.Act_val LIKE '%$Par_Sql[1]%'";					   		   //echo $sql;
		   return $sql;
		   break;
		
		/**
		 * Obtener el estado del activo  segun el codigo del activo
		 */
		case 480:
			$sql="SELECT 
					estado_act.Est_Des,estado_act.Est_Cod
				  FROM 
				  	activo 
				  INNER JOIN estado_act ON activo.Est_Cod = estado_act.Est_Cod
				  WHERE activo.Act_Cod=$Par_Sql[0]";
			//echo $sql;
		return $sql;
		
			/**
			 * Consultar sucursal en base al código 
			 */
		case 483:
			$cons_codsuc = "SELECT
							empresas.Emp_Cod, empresas.Emp_Nom, empresas.Emp_Ruc, ciudad.Ciu_Cod, ciudad.Ciu_Des, sucursal.Suc_Sri, sucursal.Suc_Dir, sucursal.Suc_Des, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax, sucursal.Suc_Cor, sucursal.Suc_Web ,Emp_Log
							FROM 
								empresas,ciudad,sucursal
							WHERE 
								sucursal.Ciu_Cod = ciudad.Ciu_Cod AND sucursal.Emp_Cod = empresas.Emp_Cod AND sucursal.Suc_Cod = $Par_Sql[0]";
		//echo $cons_codsuc;
			return $cons_codsuc;
			break;
			
			
			 /**
			  *  Consulta la provicia y pais de la ciudad de la sucursal
			  */
				   case 5000:
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
					//echo $sql;
					return $sql;
				   break;
						
					  /**
					   *  Consulta la información la ciudada en base a la sucursal
					   */
					   case 5001:
						$sql="SELECT 
								empresas.Emp_Nom, Emp_Ruc, ciudad.Ciu_Des, sucursal.Ciu_Cod, sucursal.Suc_Dir, sucursal.Suc_Te1, sucursal.Suc_Te2, sucursal.Suc_Fax,
						sucursal.Suc_Cor, sucursal.Suc_Web, sucursal.Suc_Des, empresas.Emp_Log 
							FROM 
								empresas, sucursal, ciudad 
							WHERE 
								sucursal.Suc_Cod = $Par_Sql[0] AND 
								empresas.Emp_Cod = sucursal.Emp_Cod AND 
								sucursal.Ciu_Cod = ciudad.Ciu_Cod";
						//echo $sql;
						return $sql;
					   break;				   
					   
					 /**
					 *  Consulta los datos del usuario
					 */
					case 5002:
						$sql="SELECT 
								Prs_Ape, Prs_Nom, Prs_Ced 
							 FROM 
								persona, usuarios 
							 WHERE 
								persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
						//echo $sql;
						return $sql;
					 break;
					 
				/**
				   * Consulta los datos del usuario
				   */
				  case 5003:
				   $sql="SELECT Prs_Ape, Prs_Nom FROM persona, usuarios WHERE persona.Prs_Cod = usuarios.Prs_Cod AND usuarios.Usu_Cod = $Par_Sql[0]";
				   return $sql;
				  break;
			
			
		}
	}
?>