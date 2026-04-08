<?php
/**
* Carga el periodos contable actual 
*/
$rs_periodos = $obBD_con1->consulta(sentencias_com(10,''), $obBD_conexion->conexion);
$row_rs_periodos = $obBD_con1->registros();
$total_rs_periodos = $obBD_con1->numregistros();
?>
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Selección Periodo Contable</label>
</LEGEND>
<table width="312" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td width="86" class="Etiqueta1">Periodos:&nbsp; </td>
	<td width="104">
	<select name="Pec_Cod" id="Pec_Cod" onChange="/* javascript: asignar_fechas(this.value) */">
	<?Php 
	if ($total_rs_periodos > 0)
	{
		do{
		?>
			<option value="<?Php echo $row_rs_periodos['Pec_Cod']; ?>">
			<?Php echo $row_rs_periodos['Periodo']; ?></option>	
		<?php		
		}while($row_rs_periodos = $obBD_con1->fetch_assoc($rs_periodos));
	}//Fin del if ($total_rs_periodo > 0)
	else
	{ ?>
		<option value=""></option>
	<?Php
	}//Fin del else if ($total_rs_periodos > 0)
	?>	
	</select>
	 </td>
	<td width="122" align="center">
    <button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
    </button>    
	<input name="hdd_Pec_Cod" type="hidden" id="hdd_Pec_Cod"></td>
  </tr>
</table>
</FIELDSET>
<?Php	
/* Libera la memoria ram */		 	  	  
@$obBD_con1->free_result($rs_periodos);
?>