<?Php 
/**
* Componente que muestra el campo número de comprobante 
*/
?>
<table width="100%" height="28" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15%" class="Etiqueta1"><span class="Asterisco">* </span>No Docmt.: </td>
    <td colspan="3"><span class="LetraNegra">
      <input name="Cop_Num" type="text" id="Cop_Num" size="16" maxlength="16" 
	  onblur="var formato=/^[0-9]{3}-[0-9]{3}-[0-9]{8}$/;	
	  if(validar_formato(this,formato,'Los n&uacute;meros de las facturas deben cumplir el siguiente formato: 999-999-99999999\nEjemplo: 001-001-00000586')){
	  ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numcom=1&Prv_Cod=' + document.getElementById('Prv_Cod').value +'&Tic_Cod=' + document.getElementById('Tic_Cod').value +'&Cop_Num=' + this.value,'div_con_num_com');  }"	  	  
	  >
    </span><span id="div_con_num_com"></span></td>
  </tr>
</table>