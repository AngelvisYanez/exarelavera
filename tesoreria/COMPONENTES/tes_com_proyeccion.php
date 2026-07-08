<?Php
/* 
Alias:	componente proyeccion
Descripción: Componente que permite visualizar la proyeccion de intereses a 10 dias 
Fecha de actualización:	2009-09-09
Desarrollador:	Lewis Chimarro
*/
/* Variable del codigo del cliente */
if (isset($Com_Cli_Cod))
{
	/* Variable que almacena código del producto */
	if (isset($Com_Pro_Cod))
	{		 
		/* Variable que almacena código del acta general */
		if (isset($Com_Nge_Cod))
		{
			/* Variable que almacena código de la asignatura */
			if (isset($Com_Asi_Int))
			{
				/* Variable que almacena el saldo de la deuda */
				if (isset($Com_Saldo))
				{
					/* Variable que almacena la fecha ultima del interes*/
					if (isset($Com_Int_Fec))
					{ 
					$hoy = date("Y-m-d");
					?>
				<FIELDSET id="fie_proyeccciones">
				<LEGEND>
				<label class="Titulos2">Proyecciones de inter&eacute;s</label>
				</LEGEND>
				<?php
				/* Consulta la descripcion del producto */
				$rs_producto = $obBD_con1->consulta(sentencias_tes(462, $obBD_con1->parametros($Com_Pro_Cod)), $obBD_conexion->conexion);
				$row_rs_producto = $obBD_con1->registros();
				/* Consultar valor del porcentaje */
				$rs_por=$obBD_con1->consulta(sentencias_tes(657, $obBD_con1->parametros('')), $obBD_conexion->conexion);
				$row_rs_por=$obBD_con1->registros();	
			?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
				  <tr class="Cuerpo_ajax">
					<td width="4%"><strong>Rubro:</strong></td>
					<td width="29%"><?php echo $row_rs_producto['Ite_Lar']; ?></td>
					<td width="3%"><strong><span class="Estilo1">Valor</span>:</strong></td>
					<td width="64%"><?php echo formato_numero($Com_Saldo,2,4); ?></td>
				  </tr>
				</table>
				<table width="100%" border="1" cellpadding="0" cellspacing="0" class="Cabecera_ajax">
				  <tr>
					<td width="8%" align="center"><strong>Fecha </strong></td>
					<td width="6%" align="center"><span class="Estilo2">D&iacute;as Mora</span></td>
					<td width="8%" align="center"><span class="Estilo3"><strong>Int.  Diario </strong></span></td>
					<td width="30%" align="center"><strong>  Int. Acumulado = <span class="Estilo2">D&iacute;as Mora </span>* <span class="Estilo3"> Int. Diario</span> </strong></td>
					<td width="26%" align="center"><strong> <span class="Estilo4 Estilo2">Total Int.</span> = <span class="Estilo2">D&iacute;as Mora</span> *  Int. Acumulado  </strong></td>
					<td width="22%" align="center"><strong> <span class="Estilo5">$ TOTAL A PAGAR</span> = <span class="Estilo1">Valor </span>+ <span class="Estilo4">Total Int.</span> </strong></td>
				  </tr>
				<?php 
			$j=0;
			for($j=0;$j<=10;$j++)
			{ 
				$fecha=fechas_futuras($hoy, $j);
				?>
				  <tr class="Cuerpo_ajax" align="center">
					<td><?php echo $fecha; ?> </td>
					<td><?Php  
					/* Calculo de diferencia de d&iacute;as */
					/* $dis_res=encontrarcant_dias($row_rs_deuda['Deu_Fec'], $fecha,0); */								
					$Fecha=explode('-',$Com_Int_Fec);
					$Fecha2=explode('-',$fecha);
					$timestamp1 = mktime(0,0,0,$Fecha[1],$Fecha[2],$Fecha[0]);
					$timestamp2 = mktime(0,0,0,$Fecha2[1],$Fecha2[2],$Fecha2[0]);
					$segundos_diferencia = $timestamp1 - $timestamp2;  		
					$dias_diferencia = $segundos_diferencia / (60 * 60 * 24); 
					
					/* Consulta los dias de mora que tiene un rubro */
/*					$rs_mora = $obBD_con1->consulta(sentencias_tes(54, $obBD_con1->parametros($Com_Cli_Cod.'*'.$Com_Pro_Cod.'*'.$Com_Nge_Cod.
										'*'.$Com_Asi_Int)), $obBD_conexion->conexion);
					$row_rs_mora = $obBD_con1->registros();
					$dias_diferencia =  $row_rs_mora['Mora'];*/
					//echo $dis_res.'<br>';
					if ($dias_diferencia >= 0)
					{
						/* Variable inicializada en 1 para que el calculo de interes al multiplicar se mantenga el mismo */
						$dias_difer= 0;
						$inter = 0;		
						$tot_int = $Com_Saldo_Int; 
					}
					else
					{
						$dias_difer= abs($dias_diferencia) - $row_rs_por['Int_Dia']; //codigo anterior
						$inter= ($dias_difer * $row_rs_por['Int_Por']);
						$tot_int=($Com_Saldo * $inter)/100;						
					}
					echo round($dias_difer,2); //-$dis_res;
					
					?>
					</td>
					<td><?php echo $row_rs_por['Int_Por']."%"; ?></td>
					<td><?php 
									echo formato_numero($inter,2,4)."%";?></td>
					<td align="right"><?php 
									echo "$".formato_numero($tot_int,2,4);?></td>
					<td align="right"><?php echo "$".formato_numero($Com_Saldo + ($tot_int),2,4); ?></td>
				 </tr>	 			 
			<?php 			
			} //Fin del for($j=0;$j<=10;$j++)
			?>
				<tr>
					<td colspan="6" align="center"><img src="../../imagenes/ocultar2.jpg" height="12" style="cursor:pointer" alt="Ocultar" onClick="ShowHide('fie_proyeccciones')">
				  </td>
				  </tr>
			</table>	
	</FIELDSET>  
		<?php
		@$obBD_con1->free_result($rs_producto);
		@$obBD_con1->free_result($row_por);
					}//FIn del if (isset($Com_Int_Fec)
					else					
					{
						echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Int_Fec<br>
						Com_Int_Fec: Variable que contiene la ultima fecha del interés", 2);															
					}//Fin del else if (isset($Com_Int_Fec)
				}//Fin del if (isset($Com_Saldo)
				else
				{
					echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Saldo<br>
					Com_Saldo: Variable que contiene el saldo de la deuda", 2);									
				}//Fin del else if (isset($Com_Saldo)
			}//Fin del if (isset($Com_Asi_Int)
			else
			{
				echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Asi_Int<br>
				Com_Asi_Int: Variable que contiene el código de la asignatura (en caso de ser modular)", 2);					
			}//Fin del else if (isset($Com_Asi_Int)
		}//Fin del if (isset($Com_Nge_Cod))
		else
		{
			echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Nge_Cod<br>
			Com_Nge_Cod: Variable que contiene el código del acta general del estudiante", 2);			
		}//Fin del else if (isset($Com_Nge_Cod))
	}//Fin del if (isset($Com_Pro_Cod))
	else
	{
		echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Pro_Cod<br>
		Com_Pro_Cod: Variable que contiene el código del producto", 2);	
	}//Fin del else if (isset($Com_Pro_Cod))
}//FIn del if (isset($Com_Cli_Cod))
else
{ 
		echo error_alerta("<< Error de componente: tes_com_proyeccion.php >> <br>Descripción: No se ha definido la Propiedad: Com_Cli_Cod<br>
		Com_Cli_Cod: Variable que contiene el código del cliente", 2);
} /* fin del else if (isset($Com_Cli_Cod)) */
?>