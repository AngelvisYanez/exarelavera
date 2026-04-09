<?php
	/**
	 * ACTIVOS FIJOS
	 */
	function sentencias_activo($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			 * Insertar un nuevo activo
			 */
			case 601:
				$sql = "INSERT INTO activo (Tia_Cod, Pri_Cod, Suc_Cod, Est_Cod, Prv_Cod, Act_Des, Act_Obs, Act_Cdc, Act_Can, Act_Bar, Act_Gen, Act_Val, Act_Res, Act_Ann, Act_Fec, Act_Gar, Act_Fot) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]','$Par_Sql[5]','$Par_Sql[6]','$Par_Sql[7]','$Par_Sql[8]','$Par_Sql[9]','M','$Par_Sql[11]','$Par_Sql[12]','$Par_Sql[13]','$Par_Sql[14]','$Par_Sql[15]','$Par_Sql[16]')";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Modificar para agregar la foto del activo
			 */
			case 602:
				$sql = "UPDATE activo SET Act_Fot='$Par_Sql[1]' WHERE Act_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Modificar para agregar el código de barras
			 */
			case 603:
				$sql = "UPDATE activo SET Act_Bar='$Par_Sql[1]', Act_Gen='$Par_Sql[2]' WHERE Act_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Tomar todos los tipos de activo de un nivel, cualquiera que este sea 
			 */
			/*case 604:
				$sql = "SELECT Tia_Cod, Tia_Des, Tia_Est, Tia_Rec, Tia_Cdc, Tia_Dep, Emp_Cod, Tia_Tip, Tia_Obs,Tia_Cod as id, Tia_Rec as parent, Tia_Des as text FROM tipo_activo WHERE Tia_Rec=$Par_Sql[0]";
				return $sql;
			break;*/
				
			/**
			 * Tomar el codigo Tia_Rec de un codigo determinado. Sirve para regresar de un directorio
			 */
			/*case 606:
				$sql = "SELECT Tia_Rec, Tia_Des FROM tipo_activo WHERE  Tia_Cod=$Par_Sql[0]";
				return $sql;
			break;*/
				
			/**
			 * Tomar la descripcion Tia_Des del nivel superior. Sirve para regresar de un directorio
			 */
			/*case 607:
				$sql = "SELECT Tia_Des FROM tipo_activo WHERE Tia_Cod=$Par_Sql[0] AND Emp_Cod = $Par_Sql[1]";
				return $sql;
			break;*/	
			
			/**
			 * Tomar todos los tipos de activo de todos los niveles para presentarlos en el jtree
			 */
			case 608:
				$sql = "SELECT Tia_Cod, Tia_Des, Tia_Est, Tia_Rec, Tia_Cdc, Tia_Dep, Emp_Cod, Tia_Tip, Tia_Obs, Tia_Cod as id, 
						CAST(IF(Tia_Rec=0,'#',Tia_Rec) AS CHAR) as parent, CONCAT(Tia_Cdc,' - ',Tia_Des) as 'text',
						IF(Tia_Rec=0,'fa fa-hand-o-right red bold',IF(Tia_Tip='G','glyphicon glyphicon-folder-open blue','fa fa-file-text green')) as icon 
						FROM tipo_activo 
						where Emp_Cod=$Par_Sql[0]";
				return $sql;
			break;
			
			/**
			 * Permite determinar si el código para la categoría de tipo de activo existe
			 */
			case 609:
				$sql = "SELECT MAX(CAST((SUBSTRING_INDEX(Tia_Cdc, '.', -1) + 0)AS DECIMAL)) AS max 
						FROM tipo_activo 
						WHERE Tia_Rec=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
				return $sql;
			break;
			
			/**
			 * Permite verificar de que la persona este registrada como perito
			 */
			case 610:
				$sql = "SELECT perito.Pri_Cod, persona.Prs_Nom, persona.Prs_Ape
						FROM persona,perito
						WHERE persona.Prs_Ced='$Par_Sql[0]' AND persona.Prs_Cod=perito.Prs_Cod AND perito.Pri_Est='A'";
				return $sql;
			break;
			
			/**
			 * Permite listar los proveedores
			 */
			case 611:
				if($Par_Sql['op_opciones']=="d") {$search="(Prs_Ape LIKE '%$Par_Sql[search]%' OR Prs_Nom LIKE '%$Par_Sql[search]%')";}
				else {$search="Prs_Ced LIKE '$Par_Sql[search]%'";}
				if(isset($Par_Sql["limits"])){
					$Par_Sql["limits"]="ORDER BY Prs_Ape $Par_Sql[limits]";
					$campos=" Prv_Cod, Prs_Ced, CONCAT(Prs_Ape,' ',Prs_Nom) as proveedor, Prv_Fax,Prs_Dir, IF (Prv_Est='A','Activo','Inactivo') as Prv_Est";
				}
				else{$campos="COUNT(Prv_Cod) as total";$Par_Sql["limits"]="";}
				$sql="SELECT $campos FROM proveedore, persona WHERE $search AND proveedore.Prs_Cod=persona.Prs_Cod AND proveedore.Emp_Cod = $Par_Sql[Emp_Cod] $Par_Sql[limits]";
//				echo $sql;
				return $sql;
            break;
			
			/**
			 * Permite listar los estados de activo para llenar el comoboBox
			 */
			case 612:
				$sql = "SELECT Est_Cod, Est_Des 
						FROM estado_act 
						WHERE Est_Est='A'";
				return $sql; 
			break;
			
			/**
			 * Permite listar los activos registrados y cuyo estado sea activo
			 */
			case 613:
				if($Par_Sql['op_BuscarActivo']=="d") {$search="(Act_Des LIKE '%$Par_Sql[search_activo]%')";}
				else {$search="Act_Bar LIKE '$Par_Sql[search_activo]%'";}
				if(isset($Par_Sql["limits"])){
					$Par_Sql["limits"]="ORDER BY activo.Act_Des $Par_Sql[limits]";
					$campos="activo.Act_Cod,activo.Tia_Cod,activo.Pri_Cod,activo.Est_Cod,estado_act.Est_Des,activo.Prv_Cod,CONCAT(per_proveedore.Prs_Ape,' ',per_proveedore.Prs_Nom) as proveedor,activo.Act_Des,activo.Act_Obs,activo.Act_Cdc,activo.Act_Can,activo.Act_Bar, 
activo.Act_Gen,activo.Act_Val,activo.Act_Res,activo.Act_Ann,activo.Act_Fec,activo.Act_Gar,activo.Act_Fot,per_perito.Prs_Ced AS ced_perito, 
CONCAT(per_perito.Prs_Nom,' ',per_perito.Prs_Ape)AS nom_perito,CONCAT(tipo_activo.Tia_Cdc,' - ',tipo_activo.Tia_Des) AS Tia_Des";
				}
				else{$campos="COUNT(Act_Cod) as total";$Par_Sql["limits"]="";}
				$sql="SELECT $campos FROM activo 
					  INNER JOIN perito ON activo.Pri_Cod=perito.Pri_Cod
					  INNER JOIN persona AS per_perito ON perito.Prs_Cod=per_perito.Prs_Cod 
                      INNER JOIN proveedore ON activo.Prv_Cod=proveedore.Prv_Cod 
					  INNER JOIN persona AS per_proveedore ON proveedore.Prs_Cod=per_proveedore.Prs_Cod 
					  INNER JOIN tipo_activo ON activo.Tia_Cod=tipo_activo.Tia_Cod 
					  INNER JOIN estado_act ON activo.Est_Cod=estado_act.Est_Cod 
					  WHERE $search AND activo.Act_Est='A' AND activo.Suc_Cod = $Par_Sql[Suc_Cod] $Par_Sql[limits]";
				//echo $Par_Sql['search_activo'];
				return $sql;
            break;
			
			/**
			 * Modificar los datos de un activo
			 */
			case 614:
				$sql = "UPDATE activo SET Tia_Cod='$Par_Sql[1]',Pri_Cod='$Par_Sql[2]',Est_Cod='$Par_Sql[3]', Prv_Cod='$Par_Sql[4]', 
						Act_Des='$Par_Sql[5]',Act_Obs='$Par_Sql[6]',Act_Cdc='$Par_Sql[7]', Act_Can='$Par_Sql[8]',Act_Bar='$Par_Sql[9]',Act_Gen='$Par_Sql[10]',Act_Gar='$Par_Sql[11]'
						WHERE Act_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Permite listar los estados de activo para llenar el comoboBox
			 */
			case 615:
				$sql = "SELECT Tia_Cod,CONCAT(Tia_Cdc,' - ',Tia_Des) AS descripcion 
						FROM tipo_activo 
						WHERE Tia_Tip='D' AND Emp_Cod=$Par_Sql[0]";
				return $sql; 
			break;
			
			/**
			 * Permite listar los campos concertiente a un tipo de activo específico
			 */
			case 616:
				$sql = "SELECT campos_act.Cam_Cod,campos_act.Cam_Lar,campos_act.Cam_Cor,campos_act.Cam_Tip,campos_plan.Cam_Ord,campos_plan.Cam_Req
						FROM tipo_activo,campos_act,campos_plan
						WHERE tipo_activo.Tia_Cod='$Par_Sql[0]' AND tipo_activo.Tia_Cod=campos_plan.Tia_Cod AND campos_plan.Cam_Cod=campos_act.Cam_Cod";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Permite insertar en la tabla det_activo
			 */
			case 617:
				$sql = "INSERT INTO det_activo (Act_Cod, Cam_Cod, Act_Val) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]')";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Permite listar campos del tipo de activo con la informacion registrada en la tabla det_activo
			 */
			case 618:
				$sql = "SELECT det_activo.Act_Cod,det_activo.Cam_Cod,det_activo.Act_Val,
       					campos_act.Cam_Lar,campos_act.Cam_Cor,campos_act.Cam_Tip,campos_act.Cam_Obs,campos_act.Cam_Bus,
       					campos_plan.Cam_Ord,campos_plan.Cam_Req
						FROM det_activo,campos_act,campos_plan 
						WHERE det_activo.Act_Cod='$Par_Sql[0]' AND det_activo.Cam_Cod=campos_act.Cam_Cod 
						AND campos_act.Cam_Cod=campos_plan.Cam_Cod";
				//echo $sql;
				return $sql; 
			break;
			
			/**
			 * Modificar los datos de un activo
			 */
			case 619:
				$sql = "UPDATE det_activo SET Act_Val='$Par_Sql[2]'
						WHERE Act_Cod=$Par_Sql[0] AND Cam_Cod=$Par_Sql[1]";
				//echo $sql;
				return $sql;
			break;
	}
}
?>