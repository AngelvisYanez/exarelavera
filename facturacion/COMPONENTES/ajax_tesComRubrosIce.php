<?
/**
* Descripcion: Componente que muestra los codigos ICE.
* Fecha de actualizacion: 2015-17-28
* Desarrollador: Jose Cumbicos
*/

if(isset($ajax_renta_ice_buscar))
{
	/**
	* Cargado de los codigos ICE
	*/	
	if($op=='d')
	{
		$rs_busCtaIce = $obBD_con1->getArrayConsulta(5,$ref,$obBD_conexion);		
	}
	if($op=='p')
	{
		$rs_busCtaIce = $obBD_con1->getArrayConsulta(6,$ref,$obBD_conexion);
	}
	$total_busCtaIce=count($rs_busCtaIce);
?>
 
<table width="100%" border="0" cellpadding="0" cellspacing="0" id="tbl_resultados" >
 <thead>
    <tr>
        <th width="9%"><strong>C&oacute;d. Int.</strong></th>
        <th width="15%"><strong>C&oacute;d. S.R.I.</strong></th>		
        <th width="55%"><strong>Descripci&oacute;n</strong></th>        
        <th width="11%"><strong>%.</strong></th>        
        <th width="10%">&nbsp;</th>
    </tr>		
 </thead>
 <tbody>
     <tr>
        <td colspan="5" align="center" ><hr /></td>
     </tr>
<?
	if ($total_busCtaIce != 0) 
	{
		foreach($rs_busCtaIce as $datos)	
		{   /*control para recortar el detalle del codigo ICE a 37 caracteres*/ 
			if(strlen($datos['Ice_Des'])>'37')
			{
				$DetIce=substr($datos['Ice_Des'],0,37).'...';
			}else{
				$DetIce=$datos['Ice_Des'];
			}
?>			
			
			<tr>				
				<td align="center" ><? echo $datos['Ice_Int']; ?></td>	
				<td align="center"><? echo $datos['Ice_Sri']; ?></td>
			  <td title="<? echo $datos['Ice_Des']; ?>"><? echo $DetIce; ?></td>
				<td align="right"><?Php echo $datos['Ice_Por']." %";?>&nbsp;&nbsp;</td>
				<td align="center">
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="
                document.getElementById(document.getElementById('hdd_Ice_Por').value).value='<?Php echo $datos['Ice_Por']; ?>'; 
                document.getElementById(document.getElementById('hdd_Ice_Sri').value).value='<?Php echo $datos['Ice_Sri']; ?>';
                document.getElementById(document.getElementById('hdd_Ice_Int').value).value='<?Php echo $datos['Ice_Int']; ?>'; 
                validar_text_com_ice();"><!--cal_ice_importe('Cop_Des',10,5,4);-->
        	<i class=" icon-arrow-right icon-white"></i>  
        		</button>			
            	</td>
			</tr>
		<? 
		} //Fin del foreach
	} else { ?>
			<tr>
				<td>&nbsp;</td>
		        <td>&nbsp;</td>
				<td><?php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1)?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
	<? }?>
    </tbody>
</table>
<br />
<?php
echo barra_estado($total_busCtaIce); 
	exit();
}
?>
