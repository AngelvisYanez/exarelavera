<?php
/* Alias: [--]
   Descripción: Componente que permite mostrar los datos del cheque a modificar.
   Fecha de actualización: 07-08-2009
   Desarrollador: Freddy Jumbo
*/
if(isset($For_Cod)){ /* inicio if(isset($For_Cod)){ */
  if(isset($Cop_Bus)){ /* inicio if(isset($Cop_Bus)){ */
    if(isset($Pec_Cod)){ /* inicio if(isset($Pec_Cod)){ */
	  	/* Consulta los tipos de pagos en relacion con compr_plan */
	  	$rs_tipo_pagos = $obBD_con1->consulta(sentencias_tes(258, $obBD_con1->parametros($For_Cod)), $obBD_conexion->conexion);
	  	$row_rs_tipo_pagos = $obBD_con1->registros();
	  	$total_rs_tipo_pagos = $obBD_con1->numregistros();
?>
<FIELDSET id="Fie_Cheques">
<LEGEND>
<label class="Titulos2">Tipos de Pago </label>
</LEGEND>		
<table width="560" border="0">
	<tbody id="Tbl_Cheques">
  		<tr><td width="135" class="Cabecera1">Pago</td>
		  	<td width="60" align="center" class="Cabecera1">N&ordm; Ch.</td>
	  		<td width="76" align="center" class="Cabecera1">Valor</td>
	  		<td width="59" class="Cabecera1">Fec. Elab</td>
	  		<td width="180" class="Cabecera1">Observaci&oacute;n</td>
	  		<td width="24">&nbsp;</td> 
		</tr>
	  <?Php //Consulto la informacion del cheke Cop_Bus=> es el código interno de la fatura de compra 
	        //echo "Codigo ===>".$Cop_Bus;
	  		$rs_informacion_cheque=$obBD_con1->consulta(sentencias_tes(346, $obBD_con1->parametros($Cop_Bus)), $obBD_conexion->conexion); 
			$row_rs_informacion_cheque=$obBD_con1->registros();
			$num_row_rs_informacion_cheque=$obBD_con1->numregistros();	
			$valor=0; 
			do { $fila_che++;  		 ?> 
 		<tr>
		<td width="135">
		<?Php
		/* Consultar la información del cheque */
			$rs_cheque_compras=$obBD_con1->consulta(sentencias_tes(372, $obBD_con1->parametros($row_rs_informacion_cheque['Pld_Cod'])), $obBD_conexion->conexion); 
			$row_rs_cheque_compra_mod=$obBD_con1->registros();
			$num_row_rs_cheque_compra_mod=$obBD_con1->numregistros();	
			if($num_row_rs_cheque_compra_mod==0)
			{
			  $Ban_Tipo='O';
			}else
			{
			   $Ban_Tipo=$row_rs_cheque_compra_mod['Ban_Tip'];
			}
			/* Fin consultar la información del cheque */	
		
		 			$rs_cuentas_cheque=$obBD_con1->consulta(sentencias_tes(347, $obBD_con1->parametros($row_rs_cheque_compra_mod['Ban_Tip'])), $obBD_conexion->conexion);  		
					$row_rs_cuenta_cheque=$obBD_con1->registros();		?>
					<select name="datos_ch[<?Php echo $fila_che; ?>,3]" id="datos_ch[<?Php echo $fila_che; ?>,3]"  >
					<option value="<?php echo $row_rs_cheque_compra_mod['Ban_Cod'].'*'.$row_rs_informacion_cheque['Pld_Cod'].'*'.$Ban_Tip; ?>">
					<?php echo $row_rs_informacion_cheque['Pld_Des']; ?></option>
					</select>
					<input name="datos_ch[<?Php echo $fila_che; ?>,3]" id="datos_ch[<?Php echo $fila_che; ?>,3]"  value="<?Php echo $row_rs_cheque_compra_mod['Ban_Cod'].'*'.$row_rs_informacion_cheque['Pld_Cod'].'*'.$Ban_Tipo; ?>" type="hidden" />
		</td>
    	<td align="center" >
		<?php  		/* Consulto si hay cheque */
					$rs_cheque_consulta=$obBD_con1->consulta(sentencias_tes(367, $obBD_con1->parametros($row_rs_informacion_cheque['Asi_Cod'])), $obBD_conexion->conexion);
					$row_rs_cheque_consulta=$obBD_con1->registros();	
	  	?>
					<input name="datos_ch[<?Php echo $fila_che; ?>,4]" type="text" id="datos_ch[<?Php echo $fila_che; ?>,4]" size="10" maxlength="10" value="<?Php echo $row_rs_cheque_consulta['Che_Num']; ?>" readonly="" />
		</td>
    <td align="center" >
		<?Php
			/* Acumulo el valor del cheque */
			$valor=$valor+$row_rs_informacion_cheque['Asi_Val'];
		?>	
		<input  name="datos_ch[<?Php echo $fila_che; ?>,5]" type="text" id="datos_ch[<?Php echo $fila_che; ?>,5]" size="10" maxlength="10"  value="<?Php echo formato_numero($row_rs_informacion_cheque['Asi_Val'],2,1); ?>" style="text-align:right" onblur="numerico(this);" onkeyup="cal_total_cheques(5, 'nfilas_ch', 'datos_ch')"  />
	</td>
    <td ><input type="text" name="datos_ch[<?Php echo $fila_che; ?>,6]" id="datos_ch[<?Php echo $fila_che; ?>,6]" size="10" maxlength="10" value="<?Php echo $row_rs_cheque_consulta['Che_Fec']; ?>"  onKeyUp="mascara(this,'-',patron, true)"  />
	</td>
    	<td >
<input type="text" name="datos_ch[<?Php echo $fila_che; ?>,8]" id="datos_ch[<?Php echo $fila_che; ?>,8]" size="30" value="<?Php echo $row_rs_informacion_cheque['Asi_Con']; ?>" readonly="" />
<input type="hidden" name="datos_ch[<?Php echo $fila_che; ?>,9]" id="datos_ch[<?Php echo $fila_che; ?>,9]" value="<?Php echo $fila_che; ?>" />	   
		</td>
    <td>
<?Php  if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
<input name="quitar_fila" id="quitar_fila" type="button" class="BotonEliminar" onclick="quitar_fila_mod_che(this)" value="X"  />
	&nbsp;
	<?Php } ?>
	</td> 
		
	  	</tr>	 
  <?Php }while($row_rs_informacion_cheque=$obBD_con1->fetch_assoc($rs_informacion_cheque)); ?>
</tbody>
</table>
<table width="479" border="0">
  <tr>
	<td width="73">&nbsp;</td>
	<td width="123" class="Etiqueta1"><strong>TOTAL: 
	  <input id="nfilas_ch" name="nfilas_ch" type="hidden" value="<?Php echo $fila_che; ?>" />
	</strong></td>
	<td width="269" class="LetraNegra"><input name="txt_total" type="text" id="txt_total" size="10" readonly="true" style="text-align:right" value="<?Php echo formato_numero($valor,2,1); ?>"></td>
  </tr>
</table>
<br/>
<?Php  if(isset($ocul)){ /* inicio if(isset($ocul)){  */ ?>
<table width="125" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="63" align="left"><input name="Btn_Pagos" type="button" class="Boton_Dinero" id="Btn_Pagos" title="Agregar tipo de pago" onclick="botones_opcion(1, 'Tbl_Pagos*Tbl_BusCtas')" value="Pagos" /></td>
    <td width="62" align="left"><input name="Btn_BusCta" type="button" class="Boton_Libros" id="Btn_BusCta" title="Buscar cuenta contable" onclick="botones_opcion(2, 'Tbl_Pagos*Tbl_BusCtas')" value="Cuentas" /></td>
  </tr>
</table><br />
<?Php } /* inicio if(isset($ocul)){  */ ?>

<table width="300" border="1" cellspacing="0" cellpadding="0" id="Tbl_Pagos">
  <tr>
    <td class="Cabecera1">C&oacute;d. Int. </td>
    <td class="Cabecera1">Descripci&oacute;n</td>
    <td class="Cabecera1">&nbsp;</td>
  </tr>
  <?Php
  if ($total_rs_tipo_pagos > 0)
  {
  do{
		
		/* Consulta los bancos con su respectivo asiento contable */	
		$rs_combo = $obBD_con1->consulta(sentencias_tes(257, $obBD_con1->parametros($Pec_Cod.'*'.$row_rs_tipo_pagos['Pag_Cod'])), $obBD_conexion->conexion);
		$row_rs_combo = $obBD_con1->registros();
		$total_rs_combo = $obBD_con1->numregistros(); 

		/* Creacion del Array para luego ser procesado	*/
		do{ 
			$ban_cod[]=$row_rs_combo['Ban_Cod'].'*'.$row_rs_combo['Pld_Cod'].'*'.$row_rs_combo['Ban_Tip'];
			$ban_des[]=$row_rs_combo['Pld_Des'];
		} while ($row_rs_combo = $obBD_con1->fetch_assoc($rs_combo));		 
		
		// Procesamiento del Array a un formato entendible por Javascript
		$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
		$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';  		
  ?>
  <tr class="Fondo">
    <td width="30%" align="center"><?Php echo $row_rs_tipo_pagos['Pag_Cod']; ?></td>
    <td><?Php echo $row_rs_tipo_pagos['Pag_Des']; ?></td>
    <td align="center"><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="cursor:pointer"  
	onclick="nueva_fila_cheque_com('Tbl_Cheques',<?php echo $ban_cod; ?>,<?php echo $ban_des; ?>,'Com_Fec', 'Val_Pcc');" />
	</td>
  </tr>
  <?php
  	unset($ban_cod);
	unset($ban_des);
  }while($row_rs_tipo_pagos=$obBD_con1->fetch_assoc($rs_tipo_pagos));
  }//Fin del if ($total_rs_tipo_pagos > 0)
  else
  { ?>
  <tr class="Fondo">
    <td colspan="3" align="center"><?Php echo error_alerta("No existe una configuraci&oacute;n de los Tipos de Pagos en las facturas de compra", 2); ?></td>
  </tr>
  <?Php
  }//Fin del else if ($total_rs_tipo_pagos > 0)
  ?>
</table>
<table  border="0" cellspacing="0" cellpadding="0" id="Tbl_BusCtas">
<tr>
	<td><?Php 
		/* C = buscador con cargado en combos */
		$tipo_busc = 'C'; 
		$Capa = 'busqueda';
		$Nombre_Buscador = 'buscta_combo';//Cuadro de texto
		$Nombre_Opciones = 'op_opciones_combo';//Option
		?>
		<?Php include('../../componentes/FRONT/com_con_buscarcta.php'); ?>
	</td>
</tr>
</table>
</FIELDSET>
<script language="javascript" type="text/javascript">
	ShowHide('Tbl_Pagos'); 
	ShowHide('Tbl_BusCtas'); 
</script> 
<?php
$obBD_con1->free_result($rs_tipo_pagos);
$obBD_con1->free_result($rs_combo);
$obBD_con1->free_result($rs_informacion_cheque);
$obBD_con1->free_result($rs_cuentas_cheque);
	}else{  /* else  if(isset($Pec_Cod)){ */
		 		echo error_alerta("<< Error de componente: tes_com_mod_cheque.php >> <br>Descripción: No se ha definido la Propiedad: Pec_Cod<br>
    	    	Pec_Cod: Variable que contiene el nombre del texto que posse el codigo de la compra", 2);
		 }
	  }else{ /* else if(isset($Cop_Bus)){ */
  			
      			echo error_alerta("<< Error de componente: tes_com_mod_cheque.php >> <br>Descripción: No se ha definido la Propiedad: Cop_Bus<br>
    	    	Cop_Bus: Variable que contiene el nombre del texto que posse el codigo de la compra", 2);
  			}
		 }else{ /* else if(isset($For_Cod)){ */    
				echo error_alerta("<< Error de componente: tes_com_mod_cheque.php >> <br>Descripción: No se ha definido la Propiedad: For_Cod<br>
    	    	For_Cod: Variable que contiene el nombre del texto que posse la forma de pago", 2);
		
		} /* fin if(isset($Pec_Cod)){ */
?>