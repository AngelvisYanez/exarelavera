<?Php
/* 	componente que muestra el campo número de comprobante de retención a modificar
	Desarrollador: Freddy Jumbo C
	Fecha: 25/09/2009 
*/ ?>
<div id="div_con_num_renta">
<table width="100%" height="28" border="0" cellpadding="0" cellspacing="0">
  <tr><td width="27%" class="Etiqueta1"><span class="Asterisco">* </span>No. Com. Reten:</td>
    <td colspan="3">
	<span class="LetraNegra">
		<input name="Ret_Int" type="text" id="Ret_Int" style="text-align:right" size="15" maxlength="15" 
		value="<?Php if($num_row_rs_retencion_modificar==0){  echo $Ret_Id_Man; }else{ echo $row_rs_retencion_modificar['Ret_Num'];  } ?>" onblur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numrenta=1&Pun_Cod=<?Php echo $row_rs_vendedor['Pun_Cod']; ?>&Ret_Mod=<?Php echo $row_rs_retencion_modificar['Ret_Num'];?>&Aut_Cod=<?Php echo $autCodModificar; ?>&Vnd_Cod=' + document.getElementById('Vnd_Cod').value +'&Ret_Int=' + this.value,'div_con_num_renta');" >
</span>
    </td>
  </tr>
</table>
</div>