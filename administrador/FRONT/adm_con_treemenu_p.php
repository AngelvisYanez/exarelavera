<?Php
/* 
Alias:	-
Descripción: Cargar el menu-arbol del sistema informático
Fecha de actualización:	2010-10-19
Desarrollador:	Lewis Chimarro 
*/
/* 
Alias:	-
Descripción: Cargar el menu-arbol del sistema informático para su administracion
Fecha de actualización:	2011-03-12
Desarrollador:	Lewis Chimarro 
Fecha de actualización:	2011-10-21
Desarrollador:	Lewis Chimarro 
*/

require_once('../LOGICA/logica.php');
require_once('../LOGICA/TreeMenu.php');


function hojas($obBD_con1, $obBD_conexion, $nivel, $codigo)
{
	
}

/*
Funcion que genera un arbol recursivo para la administracion del sistema
$obBD_con1: Consulta de datos
$obBD_conexion: Conexión a la base de datos
$menu: Objeto menú creado con la clase TreeMenu
$nivel: Nivel de la recusividad, empieza en cero
$nodo: Objeto nodo que es pasado como parametro
*/
function arbollll($obBD_con1, $obBD_conexion, $menu, $nivel, $nodo, $codigo)
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
			if($total_rs_procesos > 0)
			{
				while($v2=$obBD_con1->fetch_array($rs_procesos))
				{					
					$band=" ".$v2["Pcs_Lin"];
					$url=$v2["Rut_Des"].$v2["Pcs_Nom"];
					$icon2=$v2["Pcs_Img"];
					$expandedIcon='';	
					/* Se pone $node1_1_3, al final para evitar que se cree el nodo en un nodo padre ya ocupado */
					$node[$c] = &$nodo->addItem(new HTML_TreeNode(array('text' => $band, 'link' => $url, 'icon' => $icon2, 'expandedIcon' => $expandedIcon)));								
				}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
			}//FIn del if($total_rs_procesos > 0)		
		}//Fin del else if($total_rs_organizado_dos > 0)			
	}while($v0 = $obBD_con1->fetch_assoc($rs_organizado_cero));
	/* Liberacion de los cursores de la base de datos */
	@$obBD_con1->free_result($rs_organizado_cero);
	@$obBD_con1->free_result($rs_procesos);
	@$obBD_con1->liberar();	
}//Fin de funcion arbol


function arbol($obBD_con1, $obBD_conexion, $menu, $nivel, $nodo, $codigo)
{		
		$c=1;
		$band="Registrar";
		$url="prueba";
		$icon2=$v2["Pcs_Img"];
		$expandedIcon='';	
		/* Se pone $node1_1_3, al final para evitar que se cree el nodo en un nodo padre ya ocupado */
		$node1_link[$c] = new HTML_TreeNode(array('text' => $band, 'link' => $url, 'icon' => $icon2, 'expandedIcon' => $expandedIcon));								

		///Inicialización del Menú - carpetas
		$band= "Escuelas";
		$icon=$v0["Org_Img"];///Icono de la raiz - carpeta cerrada
		$expandedIcon = $v0["Org_Ime"];///Icono de la raiz - carpeta abierta
		
		$node1[$c] = new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => false));		
		$node1[$c]->addItem(&$node1_link[$c]);

		///Inicialización del Menú - carpetas
		$band= "Academico";
		$icon=$v0["Org_Img"];///Icono de la raiz - carpeta cerrada
		$expandedIcon = $v0["Org_Ime"];///Icono de la raiz - carpeta abierta		
		$node2[$c] = new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => false), 
		array('ontoggle'=>'alert("Has cambiado la rama Programación");')); 


		
		$node2[$c]->addItem(&$node1[$c]);
		
		
		$menu->addItem($node2[$c]);

}//Fin de funcion arbol




/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Adm($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Adm; 	  

	 ///Creacion del objeto para el menu
	$menu  = new HTML_TreeMenu();
	arbol($obBD_con1, $obBD_conexion, $menu, 0, "", $codigo);			  
	$treeMenu = new HTML_TreeMenu_DHTML($menu, array('/images' => '/images', 'defaultClass' => 'treeMenuDefault'));
	$treeMenu->printMenu();
?>