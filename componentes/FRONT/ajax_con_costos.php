<?Php require_once('../../componentes/LOGICA/logica.php');
/*
Ajax que permite cargar:
Cuenta de los costos
*/
if(isset($ajax_cst_cod))
{
	/*** Consultar las modalidades ********/
	$rs_costos = $obBD_con1->consulta(sentencias_com(202, $obBD_con1->parametros($Tip_Cst)), $obBD_conexion->conexion);
	$row_rs_costos = $obBD_con1->registros();
	$total_rs_costos = $obBD_con1->numregistros();
	?>
    <table width="600" height="20" border="1" cellpadding="0" cellspacing="0" class="Fondo" >
  <tr class="Cabecera_ajax">
    <td width="25" align="center"><strong>C&oacute;d. Int </strong></td>
    <td width="50" align="center"><strong>C&oacute;digo</strong></td>
    <td width="150" align="center"><strong>Descripción</strong></td>
    <td width="250" align="center"><strong>Grupo</strong></td>
    <td align="center" width="25"><strong>%</strong></td>
    </tr>
  <?Php 
   $total_por=0;
	   $i=0;
   if($row_rs_costos > 0)
   { /* inicio if($num_row_rs_iva_bienes>0){  */
	  
	   do {  /* inicio do{ */
		   	/* Consulta del detallete de la CUENTA */
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_costos['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();
 
		  	/* Consulta del detallete de la CUENTA (OTRO) */
			$rs_grupo = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_grupo = $obBD_con1->registros();

		   $i++;
			?>
		  <tr class="Cuerpo_ajax">
		    <td align="center"><?php echo $row_rs_costos['Pld_Cod'];?></td>
			<td><?php echo $row_rs_costos['Pld_Cdc'];?></td>
			<td align="left">
			<?php echo $row_rs_costos['Pld_Des']; ?>&nbsp;
			<input name="hdd_Pld[<?php  echo $i?>]" type="hidden" value="<?php  echo $row_rs_costos['Pld_Des'];?>"  id="hdd_Pld[<?php  echo $i?>]">
			<input name="Pld_Cod[<?php  echo $i?>]" type="hidden" value="<?php  echo $row_rs_costos['Pld_Cod'];?>"  id="Pld_Cod[<?php  echo $i?>]">			</td>
			<td align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ 
								echo "&nbsp;"; } ?></td>
			<td align="right">
			<input name="txt_por[<?php  echo $i?>]" type="text" id="txt_por[<?php  echo $i?>]" value="<?php echo $row_rs_costos['Tdc_Por']; ?>" size="4" style="text-align:right" onkeyup="numerico(this), suma_porcentaje(<?php echo $total_rs_costos; ?>, 'txt_por', 'suma_porc')"> <?php $total_por=$total_por + $row_rs_costos['Tdc_Por']; ?>
			<input name="hdd_cnt[<?php  echo $i?>]" type="hidden" value="<?php  echo $row_rs_costos['Pld_Cdc'];?>" id="hdd_cnt[<?php  echo $i?>]">			</td>
	  </tr>
		  <?php  } while ($row_rs_costos = $obBD_con1->fetch_assoc($rs_costos)); /* fin  } while ($row_rs_iva_bienes = mysqli_fetch_assoc($rs_iva_bienes)); */?>
			<tr class="Cuerpo_ajax">
			  <td colspan="4" align="right" class="Etiqueta1">
			TOTAL <?php echo "%"; ?>:
			<input name="cont" type="hidden" value="<?php  echo $i;?>"  id="cont">			</td>
			<td class="LetraNegra" align="right">
			<input name="suma_porc" type="text"  id="suma_porc" value="<?php echo formato_numero($total_por,2,2); ?>" size="7" style="text-align:right" readonly="true"></td>
			</tr>
			<?php
			} //if($row_rs_distr_costos > 0)
			else { 	?>
		  <tr>
			<td colspan="6" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		  </tr>
		  <?php } /* fin if($num_row_rs_iva_bienes>0){  */ ?>
</table>   
    <?Php
 	@$obBD_con1->free_result($row_rs_costos);
	exit();
}
?>
