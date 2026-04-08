<?php
$hoy=date("Y-m-d");
/* Carga el periodos contable actual */
$rs_plancuentas = $obBD_con1->consulta(sentencias_com(203,''), $obBD_conexion->conexion);
$row_rs_plancuentas = $obBD_con1->registros();
$total_rs_plancuentas = $obBD_con1->numregistros();
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Seleccion plan de cuentas </label>
</LEGEND>

<table width="60%" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td width="23%" class="Etiqueta1" align="left"><span class="Asterisco">*</span>Plan de Cuentas: </td>
	<td width="23%" align="left">
	<select name="Pla_Cod" id="Pla_Cod" >
	<?Php 
	if ($total_rs_plancuentas > 0)
	{
		do{
		?>
			<option value="<?Php echo $row_rs_plancuentas['Pla_Cod']; ?>">
			<?Php echo $row_rs_plancuentas['Ann']; ?></option>	
		<?php		
		}while($row_rs_plancuentas = $obBD_con1->fetch_assoc($rs_plancuentas));
	}//Fin del if ($total_rs_plancuentas > 0)?>
	</select>
    </td>
	<td width="54%" align="left">
	<input name="btn_periodo" type="button" title="Aceptar" class="Boton_Aceptar" id="btn_periodo" value="Aceptar" onClick="validar_requeridos(this.form, 'Pla_Cod', 0)">
	<input name="hdd_Pla_Cod" type="hidden" id="hdd_Pla_Cod"></td>
  </tr>
</table>
</FIELDSET>
<?Php	
/* Libera la memoria ram */		 	  	  
@$obBD_con1->free_result($rs_plancuentas);
?>