<?Php require_once('../LOGICA/logica.php');    
/* componente ajax que despliega en pantalla un mensaje de alerta, 
si el documento de compra ya se encuentra registrado en la base de datos */
if(isset($ajax_con_numrenta))
{	/* utilizó el código del vendedor por la relación directa que existe con la factura de compra */
	
	$rs_valAutorizacion = $obBD_con1->consulta(sentencias_tes(988, $obBD_con1->parametros($Aut_Cod)), $obBD_conexion->conexion);
	$row_rs_valAutorizacion= $obBD_con1->registros();
	$total_rs_valAutorizacion = $obBD_con1->numregistros();
	$numIni=$row_rs_valAutorizacion['Aut_Ini'];
	$numFin=$row_rs_valAutorizacion['Aut_Fin'];
	
	$rs_existe_renta_documento = $obBD_con1->consulta(sentencias_tes(379, $obBD_con1->parametros($Vnd_Cod.'*'.$Ret_Mod.'*'.$Aut_Cod.'*'.$Ret_Int)), $obBD_conexion->conexion);
	$row_rs_existe_renta_documento= $obBD_con1->registros();
	$total_rs_existe_renta_documento = $obBD_con1->numregistros();
	
?>	
  <table width="100%" height="28" border="0" cellpadding="0" cellspacing="0">
  <tr><td width="27%" class="Etiqueta1"><span class="Asterisco">* </span>No. Com. Reten:</td>
    <td colspan="3">
	<span class="LetraNegra">
		<input name="Ret_Int" type="text" id="Ret_Int" style="text-align:right" size="15" maxlength="15" value="<?Php if($Ret_Int >= $numIni && $Ret_Int <= $numFin){if($total_rs_existe_renta_documento>0){echo $Ret_Mod;}else{echo $Ret_Int;}}else{echo $Ret_Mod;}?>" onblur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numrenta=1&Pun_Cod=<?Php echo $Pun_Cod; ?>&Ret_Mod=<?Php echo $Ret_Mod;?>&Aut_Cod=<?Php echo $Aut_Cod;?>&Vnd_Cod=' + document.getElementById('Vnd_Cod').value +'&Ret_Int=' + this.value,'div_con_num_renta');" >
</span>

<?  //verifica si el nuevo numero ingresado de retencion está dentro del rango de la Autorizacion
	if($Ret_Int >= $numIni && $Ret_Int <= $numFin)	
	{		
		if($total_rs_existe_renta_documento>0)
		{ 	/* muestra el mesaje de error */
	    	?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="20" height="20" type="image"/><? 
			echo "&iexcl;Ya existe el n&uacute;mero de retenci&oacute;n ".$Ret_Int." !"; ?></span>
	<?  }else{ /* visto correcto del numero de retencion*/
			?>&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif"><?
		}
	}else{   /* muestra el mesaje de error */
		    ?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="20" height="20" type="image"/><? echo 		
			"&iexcl;El n&uacute;mero est&aacute; fuera de rango!";?></span>
 <? }?>
    </td>
  </tr>
</table>
<?
@$obBD_con1->free_result($rs_existe_renta_documento);
exit(); 
}?>

