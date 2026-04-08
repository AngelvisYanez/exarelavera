<?Php 
//require_once('../LOGICA/logica.php');    
/**
*	Componente para controlar el ingreso del n�mero del comprobante de retenci�n atravez de un ajax
*	Desarrollador: Lewis Chimarro
*	Fecha creacion: 25/09/2009
*   Desarrollador: Lewis Chimarro
*	Modificado: 08/09/2010
*	Desarrollador: Lewis Chimarro
*	Fecha creacion: 20/08/2012
*/
/**
* componente ajax que despliega en pantalla un mensaje de alerta, 
* si el documento de compra ya se encuentra registrado en la base de datos 
*/
if(isset($ajax_con_numrenta))
{	
	/**
	* Utiliz� el c�digo del vendedor por la relaci�n directa que existe con la factura de compra 
	*/	
	$rs_existe_renta_documento = $obBD_con1->consulta(sentencias_comf(1059,$obBD_con1->parametros($Vnd_Cod.'*'.$Ret_Int.'*'.$AutSri)), $obBD_conexion->conexion);
	$row_rs_existe_renta_documento= $obBD_con1->registros();
	$total_rs_existe_renta_documento = $obBD_con1->numregistros();
?>	
  <table width="100%" height="28" border="0" cellpadding="0" cellspacing="0">
  <tr>
  	<td width="27%" class="Etiqueta1"><span class="Asterisco">* </span>No. Com. Reten:</td>
    <td colspan="2"><span class="LetraNegra">
	<input name="Ret_Int" type="text" id="Ret_Int" style="text-align:right" size="15" maxlength="15" value="<?Php if($Ret_Int >= $numIni && $Ret_Int <= $numFin){if($total_rs_existe_renta_documento>0){echo $Ret_Id_Man;}else{echo $Ret_Int;}}else{echo $Ret_Id_Man;}?>" onblur="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_con_numrenta=1&Pun_Cod=<?Php echo $Pun_Cod; ?>&AutSri=<?Php echo $AutSri; ?>&Aut_Cod=<?Php echo $Aut_Cod; ?>&Ret_Id_Man=<?Php echo $Ret_Id_Man; ?>&numIni=<? echo $numIni;?>&numFin=<? echo $numFin;?>&Vnd_Cod=<? echo $Vnd_Cod;?>&Ret_Int='+ this.value,'div_con_num_renta');">
	</span>
 <?     
	if($Ret_Int >= $numIni && $Ret_Int <= $numFin)	
	{		
		if($total_rs_existe_renta_documento>0)
		{ 	
			/**
			* muestra el mesaje de error 
			*/
	    	?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/cancel.gif" width="16" height="16" type="image"/><? 
			echo "&iexcl;Ya existe el n&uacute;mero de retenci&oacute;n ".$Ret_Int."!"; ?></span>
	<?  }else{ /* visto correcto del numero de retencion*/
			?>&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif"><?
		}
	}else{   
			/**
			* muestra el mesaje de error 
			*/
		    ?><span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/cancel.gif" width="16" height="16" type="image"/><? echo "&iexcl;El n&uacute;mero est&aacute; fuera de rango!";?></span>
 <? }?>
	</td>    
  </tr>
  </table>
<?
@$obBD_con1->free_result($rs_existe_renta_documento);
exit(); } 
?>