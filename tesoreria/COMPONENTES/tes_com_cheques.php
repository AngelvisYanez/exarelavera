<?php
/* 
Alias:	componente
Descripción: Componente que permite elegir la fecha en meses y tambien en un determinado 
				rango de fechas 
Fecha de actualización:	2009-09-09
Desarrollador:	Lewis Chimarro
*/
/* Variable de la forma de pago */
if (isset($For_Cod))
{
	/* Variable que almacena el nombre del campo que contiene el valor del cheque */
	if (isset($Hdd_Valor))
	{
		/* Variable que almacena el nombre del campo que contiene la fecha del cheque */
		if (isset($Hdd_Fecha))
		{	
			/* Consulta los tipos de pagos en relacion con compr_plan */
			$rs_tipo_pagos = $obBD_con1->consulta(sentencias_tes(258, $obBD_con1->parametros($For_Cod)), $obBD_conexion->conexion);
			$row_rs_tipo_pagos = $obBD_con1->registros();
			$total_rs_tipo_pagos = $obBD_con1->numregistros();
		?>
			<FIELDSET id="Fie_Cheques">
			<LEGEND>
			<label class="Titulos2">Tipos de Pago </label>
			</LEGEND>		
			<table width="615" border="0">
			<tbody id="Tbl_Cheques">
			  
			  <tr>
				<td width="112" class="Cabecera1">Descripci&oacute;n</td>
				<td width="76" align="center" class="Cabecera1">N&ordm; Documt.</td>
				<td width="69" align="center" class="Cabecera1">Valor</td>
				<td width="73" class="Cabecera1">Fec. Elab</td>
				<td width="226" class="Cabecera1">Observaci&oacute;n</td>
				<td width="33">&nbsp;</td>
			  </tr>
			</tbody>
			</table>
			<table width="479" border="0">
			  <tr>
				<td width="67">&nbsp;</td>
				<td width="103" class="LetraNegra">&nbsp;</td>
				<td width="48" class="Etiqueta1"><strong>TOTAL: </strong></td>
				<td width="243" class="LetraNegra"><input name="txt_total" type="text" id="txt_total" size="10" readonly="true" style="text-align:right" value="0"></td>
			  </tr>
			</table>
			<br/>
			<table width="125" border="0" cellpadding="0" cellspacing="0">
			  <tr>
				<td width="63" align="left"><input id="nfilas_ch" name="nfilas_ch" type="hidden" value="0" />
					<input name="Btn_Pagos" type="button" class="Boton_Dinero" id="Btn_Pagos" title="Agregar tipo de pago" onclick="botones_opcion(1, 'Tbl_Pagos*Tbl_BusCtas')" value="Pagos" /></td>
				<td width="62" align="left"><input name="Btn_BusCta" type="button" class="Boton_Libros" id="Btn_BusCta" title="Buscar cuenta contable" onclick="botones_opcion(2, 'Tbl_Pagos*Tbl_BusCtas')" value="Cuentas" /></td>
			  </tr>
			</table>
			<br />
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
					$rs_combo = $obBD_con1->consulta(sentencias_tes(257, $obBD_con1->parametros($Pla_Cod.'*'.$row_rs_tipo_pagos['Pag_Cod'])), $obBD_conexion->conexion);
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
				<td align="center"><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="	
					cursor:pointer" onclick="nueva_fila_cheque_com('Tbl_Cheques', <? echo $ban_cod; ?>,<? echo $ban_des; ?>, '<?Php echo $Hdd_Fecha; ?>', '<?Php echo $Hdd_Valor; ?>'); cal_total_cheques(5, 'nfilas_ch', 'datos_ch')" /></td>
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
			@$obBD_con1->free_result($rs_tipo_pagos);
			@$obBD_con1->free_result($rs_combo);
		}//Fin del if (isset($Hdd_Fecha))
		else
		{
			 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: Hdd_Fecha<br>
								Hdd_Fecha: Variable que contiene el nombre del texto que posse la fecha del documento", 2); 				
		}//Fin del else if (isset($Hdd_Fecha))
	}//Fin del if (isset($Hdd_Valor))
	else
	{
		 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: Hdd_Valor<br>
								Hdd_Valor: Variable que contiene el nombre del texto que posse el valor del documento", 2); 
	}
}//Fin del if (isset($For_Cod))
else
{
	 echo error_alerta("<< Error de componente: tes_com_cheques.php >> <br>Descripción: No se ha definido la Propiedad: For_Cod<br>
							For_Cod: Variable que contiene la forma de pago ", 2); 
}//Fin del else if (isset($For_Cod))
?>