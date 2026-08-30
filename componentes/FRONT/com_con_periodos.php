<?Php
if (!function_exists('error_alerta')) return;
/* Evalua si estan seteadas las variables */
if (isset($Eta_Cod))
{
	if (isset($Ses_Suc_Cod))
	{
		if (isset($Mod_Cod))	
		{
			if (isset($hoy))
			{
				require_once('../../componentes/LOGICA/logica.php');
				/* Consulta los periodos actuales en base a la Etapa-Modalidad y fechas de inicio y fin de matricula */
				 $rs_periodos = $obBD_con1->consulta(sentencias_com(8, $obBD_con1->parametros($Eta_Cod.'*'.$Ses_Suc_Cod.'*'.$Mod_Cod.'*'.$hoy)), $obBD_conexion->conexion);
				 $row_rs_periodo = $obBD_con1->registros();
				 $total_rs_periodo = $obBD_con1->numregistros();
				?>
				
				<FIELDSET>
				<LEGEND>
					<label class="Titulos2">Seleccione el periodo:</label>
				</LEGEND>
				<table width="100%" border="0">
				  <tr>
					<td width="10%" class="Etiqueta1"><span class="Asterisco" >* </span>Periodo(s):</td>
					<td width="90%">	 
						<select name="Per_Int" id="Per_Int">
					 <option></option>
					 <?Php do{ ?>
						 <option value="<?Php echo $row_rs_periodo['Per_Int']; ?>"><?Php if ($total_rs_periodo > 0) {
						 echo $row_rs_periodo['Mes_Ini']."/".$row_rs_periodo['Ann_Ini']." - ".
							  $row_rs_periodo['Mes_Fin']."/".$row_rs_periodo['Ann_Fin']; if ($row_rs_periodo['Suc_Des'] != "") { echo ' ['.$row_rs_periodo['Suc_Des'].']'; }} ?>
						 </option>
					 <?Php }while($row_rs_periodo=$obBD_con1->fetch_assoc($rs_periodos)); ?>	 
					 </select>	 
				
					</td>
				  </tr>
				</table>
				</legend>
				</FIELDSET>
<?Php
			}//Fin del if (isset($hoy))
			else
			{
				echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: hoy<br>
						hoy: Variable que contiene la fecha actual del sistema", 2); 													
			}//Fin del else if (isset($hoy))
		}//Fin del if (isset($Mod_Cod))
		else
		{
			echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: Mod_Cod<br>
						Mod_Cod: Variable que contiene el c&oacute; de la modalidad", 2); 										
		}
	}//Fin del if (isset($Ses_Suc_Cod))
	else
	{
		echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: Ses_Suc_Cod<br>
						Ses_Suc_Cod: Variable que contiene el c&oacute; de la sucursal del usuario", 2); 								
	}//Fin del else if (isset($Ses_Suc_Cod))
}//Fin del if (isset($Eta_Cod))
else
{
	echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: Eta_Cod<br>
						Eta_Cod: Variable que contiene el c&oacute; de la etapa", 2); 							
}//Fin del else if (isset($Eta_Cod))
/* Libera la memoria ram */	
@$obBD_con1->free_result($rs_periodos);
?>