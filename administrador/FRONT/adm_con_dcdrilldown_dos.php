<?Php
/** 
* Descripción: Cargar el menu-drill del sistema informático solo cuando existe dos niveles
* Fecha de actualización:	2013-06-06
* Desarrollador:	Fabian Gallardo Gonzaga
*/
/**
* Cargado del menu cuando existen dos niveles 
*/
/********************************************************************/
/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNICAMENTE DOS NIVELES */
/********************************************************************/
/**
* Consulta los organizados de nivel 2 
*/
if (!is_object($obBD_con1)) return;
$rs_organizado_dos = $obBD_con1->getArrayConsulta(31, trim($row_rs_organizado_cero["Org_Cod"]), $obBD_conexion);
	
if(count($rs_organizado_dos) > 0)
{
?>
	<ul>
<?php
	foreach($rs_organizado_dos as $row_rs_organizado_dos)
	{							
		/**
		* Consulta los procesos del usuario 
		*/
		$rs_procesos = $obBD_con1->getArrayConsulta(18, trim($row_rs_organizado_dos["Org_Cod"]).'*'.trim(substr($mperf,1,strlen($mperf)-3)).'*P', $obBD_conexion);
		if(count($rs_procesos) > 0)
		{
			/**
			* Inicialización del Menú - carpetas
			*/
			$band=" ".$row_rs_organizado_dos["Org_Des"];///Descripcion del nivel 2
			$icon=$row_rs_organizado_dos["Org_Img"];///Icono del nivel 2 - carpeta cerrada
			$expandedIcon = $row_rs_organizado_dos["Org_Ime"];///Icono del nivel 2 - carpeta abierta
			/**
			* Inserción de Nodos de nivel 1
			*/
			?>
			<li><a href="#"><?php echo $band; ?></a>
            	<ul>
			<?php
			foreach($rs_procesos as $row_rs_procesos)
			{
				/**
				* Inicialización del Menú - procesos
				*/
				$band=" ".$row_rs_procesos["Pcs_Lin"];
				$url=$row_rs_procesos["Rut_Des"].$row_rs_procesos["Pcs_Nom"];
				$icon2=$row_rs_procesos["Pcs_Img"];
				$expandedIcon='';	
			?>
					<li><a href="<?php echo $url;?>" target="contenido"><?php echo $band; ?>&nbsp;<img src="../../Librerias/dcdrilldown/css/skins/images/arrow_down_off.png"/></a></li>
			<?php
							
			}//FIn del while($v2=$obBD_con1->fetch_array($rs_procesos))
			?>
				</ul>
			<?php
		}//FIn del if($total_rs_procesos > 0)
	}//Fin del while($v3=mysqli_fetch_array($rs_organizado_dos))
	?>
		</ul>
	<?php
}//Fin del if($total_rs_organizado_dos > 0)	
/********************************************************************/
/* ESTE CONTROL ES EN CASO DE EXISTIR SOLO Y UNCIAMENTE DOS NIVELES */
/********************************************************************/
?>