<?
/**
* Descripci�n: Componente que muestra los Rubros de un punto de imprecion.
* Fecha de actualizaci�n: 2012-11-20
* Desarrollador: Lewis Chimarro
*/
/**
* Variables de ingreso del modulo ($busqueda,$codigo,$Sem_Cod)
*/
/**
* Si el semestre esta creado para el cliente entonces de busca los rubros filtrados desde deudas 
*/
if ($Sem_Cod > 0)
{	
	/**
	* Cargado de los resultados de la busqueda de producto en base al curso 
	*/
	$rs_buscta = $obBD_con1->getArrayConsulta(1207, trim($busqueda).'*'.$codigo.'*'.$Sem_Cod.'*'.$Ses_Emp_Cod,$obBD_conexion);
}//Fin del if ($Sem_Cod > 0)
else
{		
	/**
	* Cargado de los resultados de la busqueda de producto sin precio
	*/
	$rs_buscta = $obBD_con1->getArrayConsulta(1054, strtoupper(trim($busqueda)).'*'.$Ses_Emp_Cod,$obBD_conexion);
}//Fin del else if ($Sem_Cod > 0)
?><br>

<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_resultados" class="fixedHeader01">
 <thead>
    <tr>
        <th width="10%"><strong>C&oacute;d. Int.</strong></th>
        <th width="35%"><strong>Descripci&oacute;n</strong></th>
        <th width="15%"><strong>Marca</strong></th>		
        <th width="3%"><strong>Adq.</strong></th>
        <th width="10%">Precio</th>
        <th width="10%"><strong>Pvp</strong></th>
        <th width="10%"><strong>Stock</strong></th>
        <th width="7%">&nbsp;</th>
    </tr>		
 </thead>
 <tbody>
<?	
	/*Control para permitir a Empresas NoEditar precio unitario de la venta */
	$editPtoVenta=0;
	if($Ses_Emp_Cod==142){
		$editPtoVenta=-1; //variable para bloquear edicio precio unitario		
	}
	if (count($rs_buscta) > 0) 
	{					
		foreach($rs_buscta as $row_rs_buscta)	
		{ 
			$Pro_Cod = $row_rs_buscta['Pro_Cod']; ?>			
			<tr>
				<td align="center" width="10%"><? echo $row_rs_buscta['Pro_Cod']; ?></td>
				<td align="left" width="35%"><?Php echo marcarCadenaColor($busqueda,$row_rs_buscta['Ite_Lar'],'#FFFF00', '#000', 1); ?></td>
				<td align="left" width="15%"><? echo $row_rs_buscta['Mar_Des']; ?></td>	
				<td align="left" width="3%"><? echo $row_rs_buscta['Adq_Cor']; ?></td>
				<td align="right" width="10%"><? echo $row_rs_buscta['Pre_Pvp']; ?></td>
				<td align="right" width="10%"><?Php echo number_format($row_rs_buscta['Pre_Pvp'] + ($row_rs_buscta['Pre_Pvp'] * $row_rs_buscta['Iva_Por'])/100,2);?></td>
				<td align="right" width="10%"><? echo $row_rs_buscta['Stk_Can']; ?></td>
				<td align="center" width="7%"><button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="			
			nuevaFila_ventaManual('c_contenido','<?php echo $row_rs_buscta['Pro_Cod'];?>','<?php echo $row_rs_buscta['Ite_Lar'];?>','<?php if ($row_rs_buscta['Pre_Pvp'] > 0){ echo $row_rs_buscta['Pre_Pvp']; } ?>','<?php echo $row_rs_buscta['Iva_Por'];?>','<?php echo $row_rs_buscta['Iva_Cod'];?>','<?php echo $codigonota; ?>','','','<?php echo $bloc_cant; ?>','si','<? echo $editPtoVenta;?>','0','0','<?Php echo $row_rs_buscta['Pro_Uni']; ?>'); asignar_total_fac();">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>			
            </td>
			</tr>
		<? 
		} //Fin del foreach
	} else { ?>
			<tr>
				<td>&nbsp;</td>
		    <td><?php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1)?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
	<? }?>
    </tbody>
</table>
<?php
echo barra_estado(count($rs_buscta)); ?>