<?Php
/* 
Alias:	-
Descripción: Cargar el menu-arbol del sistema informático
Fecha de actualización:	2010-10-19
Desarrollador:	Lewis Chimarro 
*/

require_once('../LOGICA/adm_log_menu.php');
require_once('../../Librerias/config.php/register_globals.php'); 
require_once('../LOGICA/TreeMenu.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($_SESSION['Ses_Dat_Dis']);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

///Creacion del objeto para el menu
$menu  = new HTML_TreeMenu();
	
///Recorrido de la variable de sesion de los perfiles de usuario
foreach($_SESSION['Ses_Lis_Per'] as $item)
{
	$mperf=$mperf." "."perfiorgan.Per_Cod=".$item." OR";
}			
	//echo $mperf;
///Cargado de Menús Principales. Ej: Gestión Administrativa, Gestión de Periodos los que tiene Org_Niv=0
$rs_organizado_cero = $obBD_con1->consulta(sentencias_adm(16, $obBD_con1->parametros(trim(substr($mperf,1,count($mperf)-3)))), $obBD_conexion->conexion);
$total_rs_organizado_cero = $obBD_con1->numregistros();
///Recorrido de los nodos de nivel cero
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

	/* Consulta los organizados de nivel 1 */
	$rs_organizado_uno = $obBD_con1->consulta(sentencias_adm(17, $obBD_con1->parametros(trim($v0["Org_Cod"]))), $obBD_conexion->conexion);
	$total_rs_organizado_uno = $obBD_con1->numregistros();
			
	if($total_rs_organizado_uno > 0)
	{					
		while($v1 = $obBD_con1->fetch_array($rs_organizado_uno))
		{		
			/* Consulta los organizados de nivel 2 */
			$rs_organizado_dos = $obBD_con1->consulta(sentencias_adm(31, $obBD_con1->parametros(trim($v1["Org_Cod"]))), $obBD_conexion->conexion);
			$total_rs_organizado_dos = $obBD_con1->numregistros();
				
			if($total_rs_organizado_dos > 0)
			{
				/* Variable para imprimir una vez el nodo dos */
				$print_dos = 0;
				
				while($v3 = $obBD_con1->fetch_array($rs_organizado_dos))
				{					
					/* Consulta los procesos del usuario */
					$rs_procesos = $obBD_con1->consulta(sentencias_adm(18, $obBD_con1->parametros(trim($v3["Org_Cod"]).'*'.trim(substr($mperf,1,count($mperf)-3)).'*P')), $obBD_conexion->conexion);
					$total_rs_procesos = $obBD_con1->numregistros();
					
					if($total_rs_procesos > 0)
					{
						/* Condicion para imprimir el nodo una sola vez */
						if ($print_dos == 0)
						{
							///Inicialización del Menú - carpetas
							$band=" ".$v1["Org_Des"];///Descripcion del nivel 1
							$icon=$v1["Org_Img"];///Icono del nivel 1 - carpeta cerrada
							$expandedIcon = $v1["Org_Ime"];///Icono del nivel 1 - carpeta abierta
							///Inserción de Nodos de nivel 1
							$node1_1[$c] = &$node1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 
																			'expandedIcon' => $expandedIcon)));							
							/* Asigna uno para que solo presente una vez el nodo dos */
							$print_dos = 1;
						}//Fin del if ($print_dos == 0)
						
						///Inicialización del Menú - carpetas
						$band=" ".$v3["Org_Des"];///Descripcion del nivel 2
						$icon=$v3["Org_Img"];///Icono del nivel 2 - carpeta cerrada
						$expandedIcon = $v3["Org_Ime"];///Icono del nivel 2 - carpeta abierta
						///Inserción de Nodos de nivel 1
						$node1_1_1[$c] = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 
																						'expandedIcon' => $expandedIcon)));
						
						while($v2=$obBD_con1->fetch_array($rs_procesos))
						{
							//$c=$c+1;							
							///Inicialización del Menú - procesos
							$band=" ".$v2["Pcs_Lin"];
							$url=$v2["Rut_Des"].$v2["Pcs_Nom"];
							$icon2=$v2["Pcs_Img"];
							$expandedIcon='';	
							$node1_1_1_1 = &$node1_1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'link' => $url, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));					
						}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
					}//FIn del if($total_rs_procesos > 0)
				}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
			}//Fin del if($total_rs_organizado_dos > 0)		
		}//while($v1 = $obBD_con1->fetch_array($rs_organizado_uno))

		/********************************************************************/
		/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNICAMENTE DOS NIVELES */
		/********************************************************************/		
		/* Consulta los organizados de nivel 2 */
		include("adm_con_treemenu_dos_1.0.php");
		/********************************************************************/
		/********************************************************************/		
	}//Fin del if($total_rs_organizado_uno > 0)
	else
	{		
		/********************************************************************/
		/********************************************************************/		
		/* Consulta los organizados de nivel 2 */
		include("adm_con_treemenu_dos_1.0.php");
		/********************************************************************/
		/********************************************************************/				
		
//		$rs_organizado_dos = $obBD_con1->consulta(sentencias_adm(31, $obBD_con1->parametros(trim($v0["Org_Cod"]))), $obBD_conexion->conexion);
//		$total_rs_organizado_dos = $obBD_con1->numregistros();
//			
//		if($total_rs_organizado_dos > 0)
//		{
//			while($v3 = $obBD_con1->fetch_array($rs_organizado_dos))
//			{					
//				///Inicialización del Menú - carpetas
//				$band=" ".$v3["Org_Des"];///Descripcion del nivel 2
//				$icon=$v3["Org_Img"];///Icono del nivel 2 - carpeta cerrada
//				$expandedIcon = $v3["Org_Ime"];///Icono del nivel 2 - carpeta abierta
//				///Inserción de Nodos de nivel 1
//				$node1_1[$c] = &$node1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon)));
//				
//				 Consulta los procesos del usuario */
//				$rs_procesos = $obBD_con1->consulta(sentencias_adm(18, $obBD_con1->parametros(trim($v3["Org_Cod"]).'*'.trim(substr($mperf,1,count($mperf)-3)).'*P')), $obBD_conexion->conexion);
//				$total_rs_procesos = $obBD_con1->numregistros();
//				
//				if($total_rs_procesos > 0)
//				{
//					while($v2=$obBD_con1->fetch_array($rs_procesos))
//					{
//						//$c=$c+1;							
//						///Inicialización del Menú - procesos
//						$band=" ".$v2["Pcs_Lin"];
//						$url=$v2["Rut_Des"].$v2["Pcs_Nom"];
//						$icon2=$v2["Pcs_Img"];
//						$expandedIcon='';	
//						$node1_1_1 = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'link' => $url, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));					
//					}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
//				}//FIn del if($total_rs_procesos > 0)
//			}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
//		}//Fin del if($total_rs_organizado_dos > 0)							

	}//FIn del else if($total_rs_organizado_uno > 0)
	$menu->addItem($node1[$c]);
}//Fin del while($v0= $obBD_con1->fetch_array($rs_organizado_cero))
	
$treeMenu = &new HTML_TreeMenu_DHTML($menu, array('/images' => '/images', 'defaultClass' => 'treeMenuDefault'));
$treeMenu->printMenu();

/* Liberacion de los cursores de la base de datos */
@$obBD_con1->free_result($rs_organizado_cero);
@$obBD_con1->free_result($rs_organizado_uno);
@$obBD_con1->free_result($rs_procesos);
@$obBD_con1->liberar();
?>