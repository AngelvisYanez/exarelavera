<?php
/* Alias: [--] ALMACEN
   Descripción: Componente que muestra los Rubros de un punto de imprecion.
   Fecha de actualización: 2010-06-29.
   Desarrollador: José Cumbicos.
*/

// Variables de ingreso del modulo ($busqueda,$codigo,$Sem_Cod)
/* Si el semestre esta creado para el cliente entonces de busca los rubros filtrados desde deudas */
if ($Sem_Cod > 0)
{	
	/* Cargado de los resultados de la busqueda de producto */
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(1207, $obBD_con1->parametros(trim($busqueda).'*'.$codigo.'*'.$Sem_Cod.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
}//Fin del if ($Sem_Cod > 0)
else
{		
	/* Cargado de los resultados de la busqueda de producto sin precio*/
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(1054, $obBD_con1->parametros(trim($busqueda).'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
}//Fin del else if ($Sem_Cod > 0)
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();			
?><br>
<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_resultados">
    <tr class="Cabecera1">
        <td width="10%"><strong>Cód. Int.</strong></td>
        <td width="35%"><strong>Descripci&oacute;n</strong></td>
        <td width="15%"><strong>Marca</strong></td>		
        <td width="5%"><strong>Adq.</strong></td>
        <td width="10%"><strong>Pvp</strong></td>
        <td width="10%"><strong>Stock</strong></td>
        <td width="5%">&nbsp;</td>
    </tr>		
<?php
	if ($total_rs_buscta > 0) {
		do { 
			$Pro_Cod = $row_rs_buscta['Pro_Cod']; ?>			
			<tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
				<td><div align="center"><?php echo $row_rs_buscta['Pro_Cod']; ?></div></td>
				<td><div align="left">&nbsp;<?Php echo marcarCadenaColor($busqueda,$row_rs_buscta['Ite_Lar'],'#FFFF00', '#000', 1); ?></div></td>
				<td align="left"><div align="left"><?php echo $row_rs_buscta['Mar_Des']; ?></div></td>	
				<td align="left"><div align="left"><?php echo $row_rs_buscta['Adq_Cor']; ?></div></td>
				<td align="right"><div align="right"><?php echo $row_rs_buscta['Pre_Pvp']; ?></div></td>
				<td align="right"><div align="right"><?php echo $row_rs_buscta['Stk_Can']; ?></div></td>
				<td>
				<div align="center">
                <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="			
			nueva_fila('c_contenido','<?php echo $row_rs_buscta['Pro_Ide'];?>','<?php echo $row_rs_buscta['Pro_Cod'];?>','<?php echo $row_rs_buscta['Ite_Lar'];?>','<?php if ($row_rs_buscta['Pre_Pvp'] > 0){ echo $row_rs_buscta['Pre_Pvp']; } ?>','<?php echo $row_rs_buscta['Iva_Por'];?>','<?php echo $row_rs_buscta['Iva_Cod'];?>','<?php echo $codigonota; ?>','','<?Php echo $_SERVER['PHP_SELF']; ?>','','<?php echo $bloc_cant; ?>','si','0'); asignar_total_fac();">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
            </div>				
            </td>
			</tr>
		<?php 
			} while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	} else { ?>
			<tr>
				<td colspan="7"><?php echo error_alerta("¡No hay resultados que mostrar!", 1)?></td>
			</tr>
	<?php }?>
</table>
<?php
echo barra_estado($total_rs_buscta);
@$obBD_con1->free_result($rs_buscta);
?>