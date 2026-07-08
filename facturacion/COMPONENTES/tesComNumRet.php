<?Php
/**
* Componente que muestra el campo número de comprobante de retnción 
* Desarrollador: Freddy Jumbo C
* Fecha: 25/09/2009 	
* Modificado: Jose Cumbicos 08/09/2010 
* Desarrollador: Lewis Chimarro
* Fecha: 2012-08-20
*/ ?>
  <div id="div_con_num_renta">	
  <table width="100%" height="28" border="0" cellpadding="0" cellspacing="0">
  <tr>
  	<td width="27%" class="Etiqueta1"><span class="Asterisco">* </span>No. Com. Reten: </td>
    <td colspan="2"><span class="LetraNegra"><input name="Ret_Int" type="text" id="Ret_Int" style="text-align:right" size="15" maxlength="15" value="<?Php echo $Ret_Id_Man; ?>" onblur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numrenta=1&Pun_Cod=<?Php echo $row_rs_vendedor['Pun_Cod']; ?>&AutSri=<?Php echo $row_rs_autorizacion['Aut_Sri']; ?>&Aut_Cod=<?Php echo $row_rs_autorizacion['Aut_Cod']; ?>&Ret_Id_Man=<?Php echo $Ret_Id_Man; ?>&numIni=<?php echo $row_autorizacion_sri['Aut_Ini'];?>&numFin=<?php echo $row_autorizacion_sri['Aut_Fin'];?>&Vnd_Cod=' + document.getElementById('Vnd_Cod').value +'&Ret_Int=' + this.value,'div_con_num_renta');">
	</span>
	</td>    
  </tr>
  </table>
  </div>