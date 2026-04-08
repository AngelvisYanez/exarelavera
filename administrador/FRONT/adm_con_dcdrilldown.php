<?Php
/**
* Descripción: Cargar el menu-drill del sistema informático
* Fecha de actualización:	2013-06-06
* Desarrollador:	Fabian Gallardo Gonzaga
*/
?>
<link href="../../Librerias/dcdrilldown/css/dcdrilldown.css" rel="stylesheet" type="text/css" />
<link href="../../Librerias/dcdrilldown/css/skins/demo.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../../Librerias/jquery.min/jquery.min.js"></script>
<script type="text/javascript" src="../../Librerias/dcdrilldown/js/jquery.cookie.js"></script>
<script type="text/javascript" src="../../Librerias/dcdrilldown/js/jquery.dcdrilldown.1.2.min.js"></script>
<script type="text/javascript">
$(document).ready(function($){
	$('#drilldown-3').dcDrilldown({
			speed       	: 'slow',
			saveState		: true,
			showCount		: false,
			linkType		: 'breadcrumb',
			backText		: 'Inicio',
			defaultText		: 'Seleccione...',
	});
});
</script>
<?Php
require_once('../LOGICA/logica.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Adm;

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Adm;	  

/**
* Recorrido de la variable de sesion de los perfiles de usuario
*/
foreach($_SESSION['Ses_Lis_Per'] as $item)
{
	$mperf=$mperf." "."perfiorgan.Per_Cod=".$item." OR";
}			

/**
* Cargado de Menús Principales. Ej: Gestión Administrativa, Gestión de Periodos los que tiene Org_Niv=0
*/
$rs_organizado_cero = $obBD_con1->getArrayConsulta(16, trim(substr($mperf,1,count($mperf)-3)), $obBD_conexion);
?>
<div class="demo-dd demo-container">
	<ul id="drilldown-3">
		
<?php
/**
* Recorrido de los nodos de nivel cero
*/
foreach($rs_organizado_cero as $row_rs_organizado_cero)
{
	$c=$c+1;
	/**
	* Inicialización del Menú - carpetas
	*/
	$band=" ".$row_rs_organizado_cero["Org_Des"];///Descripcion de la raiz

	/**
	* Imprimir el nivel 0 
	*/	
?>	
	<li><a href="#"><?php echo $band; ?></a>
<?php    
    /**
	* Consulta los organizados de nivel 1 
	*/
	$rs_organizado_uno = $obBD_con1->getArrayConsulta(17, trim($row_rs_organizado_cero["Org_Cod"]), $obBD_conexion);
			
	if(count($rs_organizado_uno) > 0)
	{				
	?>
    <ul>
    <?php	
		foreach($rs_organizado_uno as $row_rs_organizado_uno)
		{		
			/**
			* Consulta los organizados de nivel 2 
			*/
			$rs_organizado_dos = $obBD_con1->getArrayConsulta(31, trim($row_rs_organizado_uno["Org_Cod"]), $obBD_conexion);
			
			$band=" ".$row_rs_organizado_uno["Org_Des"];///Descripcion del nivel 1
			?>
            <li><a href="#"><?php echo $band; ?></a>
            <?php
			
			if(count($rs_organizado_dos) > 0)
			{
				/**
				* Variable para imprimir una vez el nodo dos 
				*/
				$print_dos = 0;
			?>
			<ul>
			<?php
				foreach($rs_organizado_dos as $row_rs_organizado_dos)			
				{		
					/**
					* Consulta los procesos del usuario 
					*/
					$rs_procesos = $obBD_con1->getArrayConsulta(18, trim($row_rs_organizado_dos["Org_Cod"]).'*'.trim(substr($mperf,1,count($mperf)-3)).'*P', $obBD_conexion);
					
					$band=" ".$row_rs_organizado_dos["Org_Des"];///Descripcion del nivel 2
					/**
					* Imprimir el nivel 2 
					*/
					?>
                        
                        <?php	    
                        if(count($rs_procesos) > 0)
                        {
								/* Condicion para imprimir el nodo una sola vez */
								if ($print_dos == 0)
								{
									///Inicialización del Menú - carpetas
									$band=" ".$v1["Org_Des"];///Descripcion del nivel 1
									$icon=$v1["Org_Img"];///Icono del nivel 1 - carpeta cerrada
									$expandedIcon = $v1["Org_Ime"];///Icono del nivel 1 - carpeta abierta
									///Inserción de Nodos de nivel 1
									?>
                                    
                                    <?Php
									//$node1_1[$c] = &$node1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 
																					//'expandedIcon' => $expandedIcon)));							
									/* Asigna uno para que solo presente una vez el nodo dos */
									$print_dos = 1;
									?>
                                    <?php
								}//Fin del if ($print_dos == 0)
								
								///Inicialización del Menú - carpetas
								$band=" ".$v3["Org_Des"];///Descripcion del nivel 2
								$icon=$v3["Org_Img"];///Icono del nivel 2 - carpeta cerrada
								$expandedIcon = $v3["Org_Ime"];///Icono del nivel 2 - carpeta abierta
								?>
                                <ul>
                                <li><a href="#"><?php echo $band; ?>aaa</a>
                            	</li>
                                </ul>
                                <?php
								///Inserción de Nodos de nivel 1
								//$node1_1_1[$c] = &$node1_1[$c]->addItem(new HTML_TreeNode(array('text' => $band, 'icon' => $icon, 
																			//					'expandedIcon' => $expandedIcon)));
							
                            ?><li><a href="#"><?php echo $band; ?>bbb</a>
                            	</li>
                                <ul>
                                    <?php
                                        foreach($rs_procesos as $row_rs_procesos)
                                        {
                                            //$c=$c+1;							
                                            /**
											* Inicialización del Menú - procesos
											*/
                                            $band=" ".$row_rs_procesos["Pcs_Lin"];
                                            $url=$row_rs_procesos["Rut_Des"].$row_rs_procesos["Pcs_Nom"];
                                            $icon2=$row_rs_procesos["Pcs_Img"];
                                            $expandedIcon='';	
                                            
                                            /**
											* Impresion nivel 3 
											*/
                                            ?>
                                                <li><a href="<?php echo $url;?>" target="contenido">&nbsp;<?php echo $band; ?>&nbsp;<img src="../../Librerias/dcdrilldown/css/skins/images/arrow_down_off.png"/></a></li>
                                            <?php
                                        }//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
                                    ?>
                                </ul>
                            <?php
                        }//FIn del if($total_rs_procesos > 0)
                        ?>
                        </li>
					<?php
				}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
				?>
                </ul>
                <?php
			}//Fin del if($total_rs_organizado_dos > 0)
			?>
	        </li>
            <?php 		
		}//while($v1 = $obBD_con1->fetch_array($rs_organizado_uno))
		?>
        </ul>
        <?php
		/********************************************************************/
		/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNICAMENTE DOS NIVELES */
		/********************************************************************/		
		/**
		* Consulta los organizados de nivel 2 
		*/
		include("adm_con_dcdrilldown_dos.php");
		/********************************************************************/
		/********************************************************************/
	}else{//Fin del if($total_rs_organizado_uno > 0)
		include("adm_con_dcdrilldown_dos.php");
	}
	?>
  	 </li>
    <?php
}//Fin del while($v0= $obBD_con1->fetch_array($rs_organizado_cero))
	
/**
* Liberacion de los cursores de la base de datos 
*/
@$obBD_con1->liberar();
?>
	</ul>
</div>