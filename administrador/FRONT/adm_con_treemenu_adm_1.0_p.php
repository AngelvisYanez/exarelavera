<?Php
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../LOGICA/logica.php');
require_once('../LOGICA/TreeMenu.php');
/* 
Alias:	-
Descripción: Cargar el menu-arbol del sistema informático para su administracion
Fecha de actualización:	2011-03-12
Desarrollador:	Lewis Chimarro 
Fecha de actualización:	2011-10-21
Desarrollador:	Lewis Chimarro 
*/

/*
Funcion que genera un arbol recursivo para la administracion del sistema
$obBD_con1: Consulta de datos
$obBD_conexion: Conexión a la base de datos
$menu: Objeto menú creado con la clase TreeMenu
$nivel: Nivel de la recusividad, empieza en cero
$nodo: Objeto nodo que es pasado como parametro
*/
function arbol($obBD_con1, $obBD_conexion, $menu, $nivel, $nodo, $Com_Tipo, $codigo)
{		
	///Cargado de Menús Principales. Ej: Gestión Administrativa, Gestión de Periodos los que tiene Org_Niv=0
	$rs_organizado_cero = $obBD_con1->consulta(sentencias_adm(24, $obBD_con1->parametros($nivel)), $obBD_conexion->conexion);
	$v0 = $obBD_con1->fetch_assoc($rs_organizado_cero);
	$total_rs_organizado_cero = $obBD_con1->num_rows($rs_organizado_cero);

	///Recorrido de los nodos de N I V E L   0
	do
	{
		$c=$c+1;	
		/* Verifica que existe carpetas (organizados) */
		if ($total_rs_organizado_cero > 0)
		{		
			///Inicialización del Menú - carpetas
			$band=" ".$v0["Org_Des"];///Descripcion de la raiz
			$icon=$v0["Org_Img"];///Icono de la raiz - carpeta cerrada
			$expandedIcon = $v0["Org_Ime"];///Icono de la raiz - carpeta abierta
		
			echo "<font color='#FF0000'>";		
			/* Solo entra cuando el nivel esta en la raiz */	
			if ($nivel == 0)
			{
				//Inserción de Nodos de nivel cero
				$node1[$c] = new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => false));
				$menu->addItem($node1[$c]);
			}
			else
			{
				//Inserción de los siguiente Nodos
				$node1[$c] = &$nodo->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon)));				
			}
			echo "</font>";
			/* Llamada recursiva de la misma funcion */		
			arbol($obBD_con1, $obBD_conexion, $menu, $v0["Org_Cod"], $node1[$c], $Com_Tipo, $codigo);		
		}//Fin del if ($total_rs_organizado_cero > 0)
		else
		{
			/* Consulta los procesos del usuario en base al perfil */
			$rs_procesos = $obBD_con1->consulta(sentencias_adm(25, $obBD_con1->parametros(trim($nivel))), $obBD_conexion->conexion);
			$total_rs_procesos = $obBD_con1->numregistros();
			/* Verifica si existen procesos para un carpeta (organizado) */		
			//if($total_rs_procesos > 0)
			//{
				while($v2=$obBD_con1->fetch_array($rs_procesos))
				{
					/* Llamada a chequeo del proceso 
					En este caso usar include y no include_once ni requiere_once
					*/		
					include("adm_con_check_proceso_1.0.php");
					/* Se pone $node1_1_2, al final para evitar que se cree el nodo en un nodo padre ya ocupado */						
					$node1[$c] = &$nodo->addItem(new HTML_TreeNode(array('text' => $check.$img_pag.$band, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));
					$estado = "";	
				}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
			//}//FIn del if($total_rs_procesos > 0)		
		}//Fin del else if($total_rs_organizado_dos > 0)			
	}while($v0 = $obBD_con1->fetch_assoc($rs_organizado_cero));
	/* Liberacion de los cursores de la base de datos */
	@$obBD_con1->free_result($rs_organizado_cero);
	@$obBD_con1->free_result($rs_procesos);
	@$obBD_con1->liberar();	
}//Fin de funcion arbol


/* L L A M A D O    D E L    A R B O L */
/* Variable del tipo de requerimiento 
Com_Tipo = A:Alta, M:Modificación, C:Consulta */
if (isset($Com_Tipo))
{
	 ///Creacion del objeto para el menu
	$obBD_conexion = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
	$obBD_con1 =  new Class_Log_Datos_Adm;
	$menu  = new HTML_TreeMenu();
	arbol($obBD_con1, $obBD_conexion, $menu, 0, "", $Com_Tipo, $codigo);			  
	$treeMenu = new HTML_TreeMenu_DHTML($menu, array('/images' => '/images', 'defaultClass' => 'treeMenuDefault'));
	$treeMenu->printMenu();
}//Fin del if (isset($Com_Tipo))
else
{ 
		echo error_alerta("<< Error de componente: adm_con_treemenu_adm_1.0.php >> <br>Descripción: No se ha definido la Propiedad: Com_Tipo<br>
		Com_Tipo: Variable que tipo de requisito de la pagina", 2);
} /* fin del else if (isset($Com_Tipo)) */
?>