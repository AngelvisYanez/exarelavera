<?php
/**
* Descripci�n: Componente listado de productos
* Fecha de actualizaci�n:	11-08-12
* Desarrollador:	Lewis Chimarro
*/	
require_once('../../Librerias/config.php/register_globals.php'); 
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion2 = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con2 =  new Class_Log_Datos_Comt; 	  

/**
* Cargado de los resultados de la busqueda de producto sin precio
*/
$rs_buscta =  $obBD_con2->getArrayConsulta(52, $buscador.'*'.$Ses_Suc_Cod,$obBD_conexion2);	
?>
<br>
<?Php 
$iva_cod=stripslashes($iva_cod);
$iva_por=stripslashes($iva_por);
$ice_cod=stripslashes($ice_cod);
$ice_por=stripslashes($ice_por);
?>
<table id="tbl_resultados" width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
<thead>
	<tr class="Cabecera1">
        <th width="11%" align="center"><strong>C&oacute;d. Int.</strong></th>
        <th width="31%"><strong>Descripci&oacute;n</strong></th>
        <th width="20%"><strong>Marca</strong></th>
        <th width="24%"><strong>Tipo</strong></th>
        <th width="11%"><strong>Pvp.</strong></th>		
        <th width="3%">&nbsp;</th>
    </tr>					
</thead>
<tbody>
    <?Php 
	if (count($rs_buscta) > 0) 
	{ 
		foreach($rs_buscta as $row_rs_buscta)
		{ 
			$Pro_Cod = $row_rs_buscta['Pro_Cod'];	
			$producto = $row_rs_buscta['Ite_Lar'].' '.$row_rs_buscta['Pro_Obs'];
			if (strlen($producto)>40)
			{
				$item= substr($producto,0,40).'...';	
			}else{
				$item= $producto;	
			}			
		?>
        <tr class="Fondo">
            <td align="center"><?php echo $row_rs_buscta['Pro_Cod']; ?></td>
            <td id="set1" title="<?php if (strlen($producto)>40){echo $producto;}?>"><?Php echo marcarCadenaColor($buscador, mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1'),'#FFFF00', '#000', 1); ?>
			</td>
            <td><?php echo $row_rs_buscta['Mar_Des']; ?></td>
            <td><?php echo $row_rs_buscta['Adq_Des']; ?></td>
            <td align="right"><?php echo formato_numero($row_rs_buscta['Pre_Pvp'],2,2); ?></td>	
            <td align="center">
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="nueva_fila_com_ice('c_contenido',<?Php echo $row_rs_buscta['Iva_Por']//echo $iva_cod; ?>,<?Php echo $row_rs_buscta['Iva_Cod'];//echo $iva_por; ?>,<?Php echo $ice_cod; ?>, <?Php echo $ice_por; ?>,'<?php echo $row_rs_buscta['Adq_Cor']; ?>',<?php echo $row_rs_buscta['Adq_Cod']; ?>,'<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '','<?php echo $row_rs_buscta['Ite_Lar']; ?>',<?php echo $row_rs_buscta['Pro_Cod']; ?>);">
        	<i class=" icon-arrow-right icon-white"></i>
        	</button>
            </td>
        </tr>
		<?php 
		}
	} 
	else 
	{ ?>
        <tr>
            <td>&nbsp;</td>
            <td><?php echo error_alerta(" No hay resultados que mostrar", 1)?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
<?php }?>
</tbody>
</table>
<?Php echo barra_estado(count($rs_buscta)); 
@$obBD_conexion2->cerrar();
?>