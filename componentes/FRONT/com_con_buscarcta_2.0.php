<?Php
if (!function_exists('error_alerta')) return;
/* Componente buscador para las cuentas contables */
/* Variable de la forma de pago */


if (isset($tipo_busc))
{	if (isset($Capa))
	{
		if (isset($Nombre_Buscador))
		{
			if (isset($Nombre_Opciones))
			{						
?>				<table width="600" border="0" cellspacing="0" cellpadding="0" id="Tbl_Cuentas"  >
				  <tr>
					<td>
					<FIELDSET class="Busqueda_ajax">
					  <LEGEND></LEGEND>
					  <table width="481" border="0">
						<tr>
						  <td width="205"><input id="<?Php echo $Nombre_Opciones; ?>" name="<?Php echo $Nombre_Opciones; ?>" type="radio" checked="checked" value="d" onClick="document.getElementById('<?Php echo $Nombre_Opciones; ?>').value='d'; setfocus(this.form.<?Php echo $Nombre_Buscador; ?>)">
							  <span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
						  <td width="266"><input id="<?Php echo $Nombre_Opciones; ?>" name="<?Php echo $Nombre_Opciones; ?>" type="radio" value="c" onClick="document.getElementById('<?Php echo $Nombre_Opciones; ?>').value='c'; setfocus(this.form.<?Php echo $Nombre_Buscador; ?>)">
							  <span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
						</tr>
					  </table>
					  <input type="hidden" name="Hdd_Pld_Cod" id="Hdd_Pld_Cod"/>
					  <input type="hidden" name="Hdd_Pld_Cdc" id="Hdd_Pld_Cdc"/>
					  <input type="hidden" name="Hdd_Pld_Des" id="Hdd_Pld_Des"/>	
					  <input type="hidden" name="Hdd_Fila" id="Hdd_Fila"/>	  
					  <table width="91%" height="36" border="0" cellpadding="0" cellspacing="0">
						<tbody id="tbusqueda">
						  <tr class="Busqueda_contenido_ajax">
							<td width="101" height="28"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
							<td width="550"><input name="<?Php echo $Nombre_Buscador; ?>" type="text" id="<?Php echo $Nombre_Buscador; ?>" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if(trim(document.getElementById('<?Php echo $Nombre_Buscador; ?>').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=<?Php echo $tipo_busc; ?>&ajax_buscod='+document.getElementById('<?Php echo $Nombre_Buscador; ?>').value+'&op_opciones='+document.getElementById('<?Php echo $Nombre_Opciones; ?>').value+'&Pec_Cod=<?Php echo $Com_Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','<?Php echo $Capa; ?>')} "></td>
							<td width="92" align="center"><input name="btn_buscarcta" type="button" class="Boton_Buscar" id="btn_buscarcta" onClick="if (trim(document.getElementById('<?Php echo $Nombre_Buscador; ?>').value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=<?Php echo $tipo_busc; ?>&ajax_buscod='+document.getElementById('<?Php echo $Nombre_Buscador; ?>').value+'&op_opciones='+document.getElementById('<?Php echo $Nombre_Opciones; ?>').value+'&Pec_Cod=<?Php echo $Com_Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','<?Php echo $Capa; ?>') }" 
						value="Buscar">
							</td>
						  </tr>
						</tbody>
					  </table>
					  <div id="<?Php echo $Capa; ?>"></div>
					</FIELDSET></td>
				  </tr>
				</table>
<?Php
			}//Fin del if (isset($Nombre_Opciones))
			else
			{
				echo error_alerta("<< Error de componente: com_con_buscarcta.php >> <br>Descripción: No se ha definido la Propiedad: Nombre_Opciones<br>
									Nombre_Opciones: Variable que contiene el nombre del option del buscador de las cuentas del plan de cuentas", 2); 							
			}//Fin del else if (isset($Nombre_Opciones))
		}//Fin del if (isset($Nombre_Buscador))
		else
		{
			echo error_alerta("<< Error de componente: com_con_buscarcta.php >> <br>Descripción: No se ha definido la Propiedad: Nombre_Buscador<br>
									Nombre_Buscador: Variable que contiene el nombre del buscador de las cuentas del plan de cuentas", 2); 	
		}//Fin del else if (isset($Nombre_Buscador))
	}//Fin del if (isset($Capa))
	else
	{ 
		echo error_alerta("<< Error de componente: com_con_buscarcta.php >> <br>Descripción: No se ha definido la Propiedad: Capa<br>
									Capa: Variable que contiene el nombre del cuadro de texto donde se va a escribir el valor a buscar", 2); 	
	}//Fin del else del if (isset($tipo_busc))	
}//Fin del if (isset($tipo_busc))
else
{
	echo error_alerta("<< Error de componente: com_con_buscarcta.php >> <br>Descripción: No se ha definido la Propiedad: tipo_busc<br>
								tipo_busc: Variable que contiene el tipo de busqueda F = Focus, C = Combo", 2); 	
}//Fin del else del if (isset($tipo_busc))
?>