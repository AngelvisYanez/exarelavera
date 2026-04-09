<?php
	/**
	 * TIPO DE ACTIVOS FIJOS
	 */
	function sentencias_act($id,$Par_Sql)
	{
		switch($id)
		{	
			/**
			 * Insertar un nuevo tipo de activo
			 */
			case 601:
				$sql = "INSERT INTO tipo_activo (Tia_Cdc, Tia_Des, Tia_Dep, Tia_Obs, Tia_Tip, Tia_Rec, Emp_Cod) VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]',$Par_Sql[5],$Par_Sql[6])";
				//echo $sql;
				return $sql;
			break;
			
			/**
			 * Modificar un tipo de activo
			 */
			case 602:
				$sql = "UPDATE tipo_activo SET Tia_Des='$Par_Sql[1]',Tia_Tip='$Par_Sql[2]',Tia_Dep='$Par_Sql[3]',Tia_Obs='$Par_Sql[4]', Tia_Est='$Par_Sql[3]' WHERE Tia_Cod=$Par_Sql[0]";
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
				//echo $sql;
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
			 * Insertar "campos" en la tabla campos_act
			 */
			case 610:
				$sql = "INSERT INTO campos_act (Cam_Lar, Cam_Cor, Cam_Tip, Cam_Obs, Cam_Bus) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]','$Par_Sql[4]')";
				//echo $sql;
				return $sql;
			break;	
			
			/**
			 * Insertar para la tabla campos_plan
			 */
			case 611:
				$sql = "INSERT INTO campos_plan (Cam_Cod, Tia_Cod, Cam_Ord, Cam_Req) 
						VALUES ('$Par_Sql[0]','$Par_Sql[1]','$Par_Sql[2]','$Par_Sql[3]')";
				//echo $sql;
				return $sql;
			break;	
			
			/**
			 * Lista los campos pertenecientes a un tipo de activo OJO únicamente los que son DETALLE
			 */
			case 612:
				$sql = "SELECT campos_act.Cam_Cod,campos_act.Cam_Lar,campos_act.Cam_Cor,campos_act.Cam_Tip,campos_act.Cam_Sys,
						campos_act.Cam_Obs,campos_act.Cam_Bus,campos_plan.Cam_Ord,campos_plan.Cam_Req
						FROM campos_act,campos_plan,tipo_activo 
						WHERE Emp_Cod='$Par_Sql[0]' AND tipo_activo.Tia_Cod='$Par_Sql[1]' AND tipo_activo.Tia_Tip='D' AND campos_plan.Tia_Cod=tipo_activo.Tia_Cod AND campos_plan.Cam_Cod=campos_act.Cam_Cod";
				//echo $sql;
				return $sql;
			break;	
			
			/**
			 * Modificar campos de la tabla campos_act
			 */
			case 613:
				$sql = "UPDATE campos_act SET Cam_Lar='$Par_Sql[1]',Cam_Cor='$Par_Sql[2]',Cam_Tip='$Par_Sql[3]',Cam_Obs='$Par_Sql[4]', Cam_Bus='$Par_Sql[5]' WHERE Cam_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;	
			
			/**
			 * Modificar campos de la tabla campos_plan
			 */
			case 614:
				$sql = "UPDATE campos_plan SET Cam_Ord='$Par_Sql[1]',Cam_Req='$Par_Sql[2]'
						WHERE Cam_Cod=$Par_Sql[0]";
				//echo $sql;
				return $sql;
			break;	
			
			/**
			 * Permite determinar si el código para la categoría de tipo de activo existe
			 */
			case 615:
				$sql = "SELECT MAX(Tia_Cdc) AS max 
						FROM tipo_activo 
						WHERE Tia_Rec=$Par_Sql[0] AND Emp_Cod=$Par_Sql[1]";
				//echo $sql;
				return $sql;
			break;

	}
}
?>