<?php
	/**
	* Facturación inventario de las compras
	*/
	function sentencias_rhu($id,$Par_Sql)
	{
		switch($id)
		{														
			/* Consulta la informacion del departamento */
			case 26:
			$consultar_departamento = "SELECT Dep_Cod, Dep_Des, Dep_Est, Dep_Cdc FROM departamen WHERE departamen.Dep_Cod=$Par_Sql[0]";
			return $consultar_departamento;
			break;
			
			/* Consulta un area especifica */
			case 663:								
			$cargar_areas_663="SELECT Are_Cod, Are_Des FROM areas_rrhh WHERE areas_rrhh.Are_Cod = $Par_Sql[0]";
			//echo $cargar_areas_663;
			return $cargar_areas_663;
			break; 
	
			/* busqueda de area departamentales */
			case 664:								
			$cargar_areas_664="SELECT Are_Cod, Are_Des FROM areas_rrhh WHERE areas_rrhh.Are_Est = 'A' AND areas_rrhh.Emp_Cod = $Par_Sql[0]";
			//echo $cargar_areas_664;
			return $cargar_areas_664;
			break; 
	
			/* Consulta los departamentos de nivel 0 */
			case 665:								
			$departamentos_665="SELECT Dep_Cod,Dep_Des,Dep_Rec,Dep_Est FROM departamen WHERE Are_Cod = $Par_Sql[0] AND Dep_Rec = $Par_Sql[1]";
			//echo "<br>".$departamentos_665;
			return $departamentos_665;
			break; 
			
			/* Cargado del recursivo de la cuenta para poder mostrar la dirección de volver atrás*/
			case 666:
			$direca_666="SELECT Dep_Rec FROM departamen WHERE Are_Cod = $Par_Sql[0] AND  Dep_Cod=$Par_Sql[1]";
			return $direca_666;
			break;
			
			/*  Consulta de la ubicacion del departamento */
			case 667:
			$direc_667="SELECT Dep_Cod, Dep_Des FROM departamen WHERE Are_Cod = $Par_Sql[0] AND Dep_Cod=$Par_Sql[1]";
			return $direc_667;
			break;
			
			/*  Insercion de los departamentos */
			case 668:
			$direc_668="INSERT INTO departamen (Are_Cod, Dep_Des, Dep_Rec,Emp_Cod)  
				VALUE ($Par_Sql[0], '$Par_Sql[1]', $Par_Sql[2], $Par_Sql[3])";
			return $direc_668;
			break;						
			
			/*  Insercion de los cargos */
			case 669:
			$direc_669="INSERT INTO tiposcargo (Dep_Cod, Tic_Des)  
				VALUE ($Par_Sql[0], '$Par_Sql[1]')";
				//echo $direc_669;
			return $direc_669;
			break;
			
			/*  Consulta de los cargos */
			case 670:
			$direc_670="SELECT Tic_Cod, Tic_Des, Tic_Est FROM tiposcargo WHERE tiposcargo.Dep_Cod = $Par_Sql[0]";
			//echo $direc_670;
			return $direc_670;
			break;
			
			/*  Update de los departamentos */
			case 671:
			$direc_671="UPDATE departamen SET Dep_Des = '$Par_Sql[0]', Dep_Cdc ='$Par_Sql[2]'  WHERE Dep_Cod = $Par_Sql[1]";
			//echo $direc_671;
			return $direc_671;
			break;
			
			/*  Consulta de los cargos especificamente */
			case 672:
			$direc_672="SELECT Tic_Cod, Tic_Des, Tic_Est FROM tiposcargo WHERE tiposcargo.Tic_Cod = $Par_Sql[0]";
			//echo $direc_672;
			return $direc_672;
			break;
			
			/*  Insercion de las secciones */
			case 706:
			$direc_706="INSERT INTO seccion (Dep_Cod, Sec_Des) VALUE ($Par_Sql[0], '$Par_Sql[1]')";
			//echo $direc_706;
			return $direc_706;
			break;
			
			/*  Consulta de las secciones */
			case 707:
			$direc_707="SELECT Sec_Cod, Sec_Des, Sec_Est FROM seccion WHERE seccion.Dep_Cod = $Par_Sql[0]";
			//echo $direc_707;
			return $direc_707;
			break;
			
			/*  Consulta de las secciones especificamente */
			case 708:
			$direc_708="SELECT Sec_Cod, Sec_Des, Sec_Est FROM seccion WHERE seccion.Sec_Cod = $Par_Sql[0]";
			//echo $direc_708;
			return $direc_708;
			break;
			
        }
}
?>