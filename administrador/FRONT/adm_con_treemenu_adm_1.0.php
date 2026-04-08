<?Php
/* 
Alias:	-
Descripción: Cargar el menu-arbol del sistema informático para su administracion
Fecha de actualización:	2011-03-12
Desarrollador:	Lewis Chimarro 
*/

/* Variable del tipo de requerimiento */
if (isset($Com_Tipo))
{
require_once('../LOGICA/logica.php');
require_once('../LOGICA/TreeMenu.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

///Creacion del objeto para el menu
$menu  = new HTML_TreeMenu();
		
///Cargado de Menús Principales. Ej: Gestión Administrativa, Gestión de Periodos los que tiene Org_Niv=0
$rs_organizado_cero = $obBD_con1->consulta(sentencias_adm(24, $obBD_con1->parametros(0)), $obBD_conexion->conexion);
$total_rs_organizado_cero = $obBD_con1->numregistros();
	
///Recorrido de los nodos de N I V E L   0
$c=0;
while($v0 = $obBD_con1->fetch_array($rs_organizado_cero))
{
	$c=$c+1;
	///Inicialización del Menú - carpetas
	$band=" ".$v0["Org_Des"];///Descripcion de la raiz
	$icon=$v0["Org_Img"];///Icono de la raiz - carpeta cerrada
	$expandedIcon = $v0["Org_Ime"];///Icono de la raiz - carpeta abierta
		
	echo "<font color='#FF0000'>";
	///Inserción de Nodos de nivel cero
	$node1[$c] = new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => false));
echo "</font>";
	/* Consulta los organizados de N I V E L   1 */
	$rs_organizado_uno = $obBD_con1->consulta(sentencias_adm(24, $obBD_con1->parametros(trim($v0["Org_Cod"]))), $obBD_conexion->conexion);
	$total_rs_organizado_uno = $obBD_con1->numregistros();

	if($total_rs_organizado_uno > 0)
	{
		while($v1 = $obBD_con1->fetch_array($rs_organizado_uno))
		{
			///Inicialización del Menú - carpetas
			$band=" ".$v1["Org_Des"];///Descripcion del nivel 1
			$icon=$v1["Org_Img"];///Icono del nivel 1 - carpeta cerrada
			$expandedIcon = $v1["Org_Ime"];///Icono del nivel 1 - carpeta abierta
			///Inserción de Nodos de nivel 1
			$node1_1[$c] = &$node1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon)));				
			/* Consulta los organizados de nivel 2 */
			$rs_organizado_dos = $obBD_con1->consulta(sentencias_adm(24, $obBD_con1->parametros(trim($v1["Org_Cod"]))), $obBD_conexion->conexion);
			$total_rs_organizado_dos = $obBD_con1->numregistros();
				
			if($total_rs_organizado_dos > 0)
			{
				while($v3 = $obBD_con1->fetch_array($rs_organizado_dos))
				{					
					///Inicialización del Menú - carpetas
					$band=" ".$v3["Org_Des"];///Descripcion del nivel 2
					$icon=$v3["Org_Img"];///Icono del nivel 2 - carpeta cerrada
					$expandedIcon = $v3["Org_Img"];///Icono del nivel 2 - carpeta abierta
					
					///Inserción de Nodos de nivel 1
					$node1_1_1[$c] = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon)));

					/* Consulta los procesos del usuario en base al perfil */
					$rs_procesos = $obBD_con1->consulta(sentencias_adm(25, $obBD_con1->parametros(trim($v3["Org_Cod"]))), $obBD_conexion->conexion);
					$total_rs_procesos = $obBD_con1->numregistros();
		
					if($total_rs_procesos > 0)
					{
						while($v2=$obBD_con1->fetch_array($rs_procesos))
						{
							/* Llamada a chequeo del proceso 
							En este caso usar include y no include_once ni requiere_once
							*/
							include("adm_con_check_proceso_1.0.php");
							$node1_1_1_1 = &$node1_1_1[$c]->addItem(new HTML_TreeNode(array('text' => $check.$img_pag.$band, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));
															
							$estado = "";					
						}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
					}//FIn del if($total_rs_procesos > 0)
				}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
			}//Fin del if($total_rs_organizado_dos > 0)	
			else
			{
				/* Consulta los procesos del usuario en base al perfil */
				$rs_procesos = $obBD_con1->consulta(sentencias_adm(25, $obBD_con1->parametros(trim($v1["Org_Cod"]))), $obBD_conexion->conexion);
				$total_rs_procesos = $obBD_con1->numregistros();
				
				if($total_rs_procesos > 0)
				{
					while($v2=$obBD_con1->fetch_array($rs_procesos))
					{
						/* Llamada a chequeo del proceso 
						En este caso usar include y no include_once ni requiere_once
						*/		
						include("adm_con_check_proceso_1.0.php");
						/* Se pone $node1_1_2, al final para evitar que se cree el nodo en un nodo padre ya ocupado */						
						$node1_1_2 = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $check.$img_pag.$band, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));

						$estado = "";	
					}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
				}//FIn del if($total_rs_procesos > 0)		
			}//Fin del else if($total_rs_organizado_dos > 0)			
		}//Fin del while($v1=mysqli_fetch_array($rs_organizado_uno))
	}//Fin del if($total_rs_organizado_uno > 0)
	$menu->addItem($node1[$c]);
}//Fin del while($v0= $obBD_con1->fetch_array($rs_organizado_cero))
	
$treeMenu = new HTML_TreeMenu_DHTML($menu, array('/images' => '/images', 'defaultClass' => 'treeMenuDefault'));
$treeMenu->printMenu();

/* Liberacion de los cursores de la base de datos */
@$obBD_con1->free_result($rs_organizado_cero);
@$obBD_con1->free_result($rs_organizado_uno);
@$obBD_con1->free_result($rs_procesos);
@$obBD_con1->free_result($rs_check);
@$obBD_con1->liberar();
}//Fin del if (isset($Com_Tipo))
else
{ 
		echo error_alerta("<< Error de componente: adm_con_treemenu_adm_1.0.php >> <br>Descripción: No se ha definido la Propiedad: Com_Tipo<br>
		Com_Tipo: Variable que tipo de requisito de la pagina", 2);
} /* fin del else if (isset($Com_Tipo)) */

?>