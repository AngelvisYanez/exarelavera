<?Php
/* Cargado del menu cuando existen dos niveles */
/********************************************************************/
/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNICAMENTE DOS NIVELES */
/********************************************************************/
/* Consulta los organizados de nivel 2 */
$rs_organizado_dos = $obBD_con1->consulta(sentencias_adm(31, $obBD_con1->parametros(trim($v0["Org_Cod"]))), $obBD_conexion->conexion);
$total_rs_organizado_dos = $obBD_con1->numregistros();
	
if($total_rs_organizado_dos > 0)
{
	while($v3 = $obBD_con1->fetch_array($rs_organizado_dos))
	{							
		/* Consulta los procesos del usuario */
		$rs_procesos = $obBD_con1->consulta(sentencias_adm(18, $obBD_con1->parametros(trim($v3["Org_Cod"]).'*'.trim(substr($mperf,1,count($mperf)-3)).'*P')), $obBD_conexion->conexion);
		$total_rs_procesos = $obBD_con1->numregistros();
		
		if($total_rs_procesos > 0)
		{
			///Inicialización del Menú - carpetas
			$band=" ".$v3["Org_Des"];///Descripcion del nivel 2
			$icon=$v3["Org_Img"];///Icono del nivel 2 - carpeta cerrada
			$expandedIcon = $v3["Org_Ime"];///Icono del nivel 2 - carpeta abierta
			///Inserción de Nodos de nivel 1
			$node1_1[$c] = &$node1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
			
			while($v2=$obBD_con1->fetch_array($rs_procesos))
			{
				//$c=$c+1;							
				///Inicialización del Menú - procesos
				$band=" ".$v2["Pcs_Lin"];
				$url=$v2["Rut_Des"].$v2["Pcs_Nom"];
				$icon2=$v2["Pcs_Img"];
				$expandedIcon='';	
				/* Se pone $node1_1_3, al final para evitar que se cree el nodo en un nodo padre ya ocupado */
				$node1_1_3 = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'link' => $url, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));			
			}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
		}//FIn del if($total_rs_procesos > 0)
	}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
}//Fin del if($total_rs_organizado_dos > 0)	
/********************************************************************/
/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNCIAMENTE DOS NIVELES */
/********************************************************************/
?>