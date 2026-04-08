<!DOCTYPE table PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?Php
/**
 * Alias: [--]
 * Descripción: Componente que muestra las deudas del cliente.
 * Fecha de actualización: 2010-02-25.
 * Desarrollador: Lewis Chimarro.
 * 
 * @author car.87cod :)
 * @fecha 26-10-2012
*/

/**
 * Variable del codigo del cliente
 */
if(isset($Com_Codigo))
{
	/**
	 * Variable para el tipo de requisito
	 */
	if (isset($Com_Tipo))
	{
		/**
		 * Creacion del Objeto de conexion
		 */
		$Com_obBD_conexion = new Class_Log_Conexion_Deu;
		
		/**
		 * Cracion del objeto mysql para las consultas
		 */
		$Com_obBD_con1 =  new Class_Log_Datos_Deu;
		
		/**
		 * consultar cliente en tabla estudiante
		 */
		$row = $Com_obBD_con1->getRowConsulta(1, $Com_Codigo, $Com_obBD_conexion);
		
		/**
		 * true -> si es un estudiante
		 * false -> cliente de una empresa de servicios
		 * @var bool
		 */
		$Cli_Est = ($row['count'] == 1);
		
		if($Cli_Est){
			/**
			 * FUNCION QUE CARGA AUTOMATICAMENTE LOS RUBROS
			 */
			$Com_obBD_con1->generar_deudas($Com_Codigo, $obBD_conexion);
		}
		
		
		/**
		 * Configuración del modulo de tesoreria
		 */
		$row_rs_confi_teso = $Com_obBD_con1->getRowConsulta(46, $Ses_Emp_Cod, $Com_obBD_conexion);
		
		?>
		<FIELDSET>
					<LEGEND>
						<label class="Titulos2">Deudas existentes </label>
					</LEGEND>
		<table border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
			<thead>
				<tr>
				      <th width="6%">C&oacute;d. Int.</th>
				      <th>Descripci&oacute;n</th>
				      <th width="10%">Fecha vencimiento</th>
				      <th width="8%">Valor</th>
				      <?php if($Cli_Est){?><th width="8%">% Beca</th><?php }?>
				      <th width="8%">Valor a Pagar</th>
				   	  <?php if($Com_Tipo != 1){?><th width="2%">&nbsp;</th><?php }?>
				   	  <?php if($Com_Tipo == 1){?><th width="2%">&nbsp;</th><?php }?>
				   	  <?php if($Com_Tipo != 1 && $Com_Tipo != 0){?><th width="2%">&nbsp;</th><?php }?>
				</tr>
			</thead>
			<tbody>
			<?php 
			
			/**
			 * Consulta las deudas del estudiante 
			 */
			$Arr_Deudas = $Com_obBD_con1->getArrayConsulta(($Cli_Est) ? 263 : 7, $Com_Codigo, $Com_obBD_conexion);
			
			if ( count($Arr_Deudas) > 0 ) {
				
				/**
				 * Almacenara el total de las deudas
				 */
				$saldo_total = 0;
				
				foreach($Arr_Deudas as $row_rs_deudas){
					
					$naranja='';
					/**
					 * Codigo del producto 
					 */
					$Pro_Cod = $row_rs_deudas['Pro_Cod'];
						
					/**
					 * Codigo de la asignatura 
					 */
					$Asi_Int = $row_rs_deudas['Asi_Int'];
					
					/**
					 * Codigo autogenerado por contrato
					 */
					$Deu_Int = $row_rs_deudas['Deu_Int'];
					
					
					if($Cli_Est){
						//cuando es un estudiante
						
						/**
						 * Codigo de la acta de notas generales
						 */
						$Nge_Cod = $row_rs_deudas['Nge_Cod'];
						
						/**
						 * Verificar el saldo de la deuda en ventas
						 */
						$row_rs_pagos = $Com_obBD_con1->getRowConsulta(68, $Com_Codigo.'*'.$Pro_Cod.'*'.$Nge_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
						
						/**
						 * Consulta el valor de beca del estudiante
						 */
						$row_rs_becas = $Com_obBD_con1->getRowConsulta(76, $row_rs_deudas['Bec_Cod'].'*'.$Pro_Cod, $Com_obBD_conexion);
						
						/**
						 * Control para presentar por pantalla el % y determinar si es general o individual
						 * de las becas del estudiante 
						 */
						if ($row_rs_becas['Bec_Pot'] > 0)
						{
							$mensaje = $row_rs_becas['Bec_Pot'];
							$porc_beca = $row_rs_becas['Bec_Pot'];
						}
						else
						{
							if ($row_rs_becas['Bec_Por'] > 0)
							{
								$mensaje = $row_rs_becas['Bec_Por'];
								$porc_beca = $row_rs_becas['Bec_Por'];
							}
							else
							{
								$mensaje = "&nbsp;";
								$porc_beca = 0;
							}
						}
							
						$valor_beca = ($row_rs_deudas['Deu_Val'] * $porc_beca) / 100;
						$saldo = (round($row_rs_deudas['Deu_Val'],2) - round($valor_beca,2)) - round($row_rs_pagos['Vet_Imp'],2);
						
						/**
						 * Solo entra y muestra las deudas sean mayores a CERO
						 */
						if (round($saldo,2) > 0)
						{

							$saldo_total = $saldo_total + $saldo;
						
							/**
							 * CONTROL PARA EL CALCULO DEL INTERES 
							 *
							 * Calculo del interes 
							 */
							$Com_obBD_con1->interes($Com_Codigo, $Pro_Cod, $Nge_Cod, $Asi_Int, $saldo, $Deu_Int, $Com_obBD_conexion);
							
							/**
							 * Consulta los rubro del intereses
							 */
							$Arr_Interes = $Com_obBD_con1->getArrayConsulta(58, $Com_Codigo.'*'.$Nge_Cod.'*'.$Asi_Int.'*'.$Pro_Cod.'*'.$Deu_Int, $Com_obBD_conexion);
							
							if ($row_rs_deudas['Deu_Fec'] < $hoy){
								
								/**
								 * Inicia la variable del componente de leyanda
								 */
								if(!isset($com_leyenda[2])){
									$com_leyenda[2] = 1;
								}
								
								$naranja= $row_rs_confi_teso['Col_Cad'];
							}
							else{ 
								$naranja='';
							}
						}
						
					}else{
						//cuando no es un estudiante
						
						/**
						 * Codigo del contrato
						 */
						$Cnt_Cod = $row_rs_deudas['Cnt_Cod'];
						
						/**
						 * Verificar el saldo de la deuda en ventas
						 */
						$row_rs_pagos = $Com_obBD_con1->getRowConsulta(2, $Com_Codigo.'*'.$Pro_Cod.'*'.$Cnt_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
						
						$saldo = (round($row_rs_deudas['Deu_Val'],2) - round($row_rs_pagos['Vet_Imp'],2));
						
						/**
						 * Solo entra y muestra las deudas sean mayores a CERO
						 */
						if (round($saldo,2) > 0)
						{
						
							$saldo_total = $saldo_total + $saldo;
						
							/**
							 * CONTROL PARA EL CALCULO DEL INTERES
							 *
							 * Calculo del interes
							 */
							$Com_obBD_con1->InteresServicios($Com_Codigo, $Pro_Cod, $Cnt_Cod, $Asi_Int, $saldo, $Deu_Int, $Com_obBD_conexion);
							
							/**
							 * Consulta los rubro del intereses
							 */
							$Arr_Interes = $Com_obBD_con1->getArrayConsulta(8, $Com_Codigo.'*'.$Cnt_Cod.'*'.$Asi_Int.'*'.$Pro_Cod.'*'.$Deu_Int, $Com_obBD_conexion);
								
							if ($row_rs_deudas['Deu_Fec'] < $hoy){
						
								/**
								 * Inicia la variable del componente de leyanda
								 */
								if(!isset($com_leyenda[2])){
									$com_leyenda[2] = 1;
								}
						
								$naranja = $row_rs_confi_teso['Col_Cad'];
							}
							else{
								$naranja='';
							}
						}
					}
					
					/**
					 * mostrar resultados
					 */
					if (round($saldo,2) > 0){
						?>
						<tr>
							<td align="center">
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo $row_rs_deudas['Pro_Cod'];?>
								</FONT>
							</td>
							<td>
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo $row_rs_deudas['Ite_Lar']; ?>
								</FONT>
							</td>
							<td align="center">
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo $row_rs_deudas['Deu_Fec']; ?>
								</FONT>
							</td>
							<td align="right">
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo formato_numero($row_rs_deudas['Deu_Val'] -  $row_rs_pagos['Vet_Imp'],2,4); ?>
								</FONT>
							</td>
							<?php if($Cli_Est){?>
							<td align="center">
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo $mensaje; ?>
								</FONT>
							</td>
							<?php }?>
							<td align="right">
								<FONT COLOR="<? echo $naranja;?>">
									<?Php echo formato_numero($saldo,2,4); ?>
								</FONT>
							</td>
							<?php if($Com_Tipo != 1){?>
							<td align="center">
								<form action="#">
									<button type="button" class="btn btn-success btn-mini" title="Ver Detalle" onclick="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?<?php 
											echo 'detalle='.(($Cli_Est)? '1&Nge_Cod='.$Nge_Cod.'&Com_Codigo='.$Com_Codigo.'&Bec_Cod='.$row_rs_deudas['Bec_Cod'].'&Pro_Cod='.$Pro_Cod:'2&Cnt_Cod='.$Cnt_Cod);
											?>','contenido');Muestra_Aparecer();">
										<i class=" icon-info-sign icon-white"></i>
									</button>
								</form>
							</td>
							<?php }?>
							<?php if($Com_Tipo == 1){?>
							<td align="center">
								<?php 
						 		switch ($Com_Tipo){
									/**
									 * Alta de ventas
									 */
									case 1:
								?>
									<form action="#">
										<button type="button" class="btn btn-success btn-mini" title="Agregar Item" onclick="<?php 
										
											echo "nuevaFila('c_contenido','".$row_rs_deudas['Pro_Cod']."','".$row_rs_deudas['Ite_Lar']."','".formato_numero(($row_rs_deudas['Deu_Val'] - $valor_beca) -  $row_rs_pagos['Vet_Imp'],2,1)."','".$row_rs_deudas['Iva_Por']."','".$row_rs_deudas['Iva_Cod']."','".$row_rs_deudas['Nge_Cod']."','".$row_rs_deudas['Deu_Rec']."','".$row_rs_deudas['Asi_Int']."','si','si',".count($Arr_Interes).",'".$row_rs_deudas['Cnt_Cod']."','".$row_rs_deudas['Deu_Int']."');";
															
											if (count($Arr_Interes) > 0){
												/**
							  				     * Agrega automaticamente los rubros recursivos
							 					 */
							 					foreach($Arr_Interes as $row_rs_interes){
							 						
													/**
													 * Consulta los pagos realizados segun el Nge_Cod o el Cnt_Cod
													 */
													if($Cli_Est){
														$row_rs_pagos_int = $Com_obBD_con1->getRowConsulta(69, $Com_Codigo.'*'.$row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
													}else{
														$row_rs_pagos_int = $Com_obBD_con1->getRowConsulta(9, $Com_Codigo.'*'.$row_rs_interes['Pro_Cod'].'*'.$Cnt_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
													}
																			
													/**
													 * Saldo del interes
													 */
													$saldo_int = round($row_rs_interes['Deu_Val'],2) - round($row_rs_pagos_int['Vet_Imp'],2);
																
													if ($saldo_int > 0){
																			
														echo "nuevaFila('c_contenido','".$row_rs_interes['Pro_Cod']."','".$row_rs_interes['Ite_Lar']."','".formato_numero(($row_rs_interes['Deu_Val']) - $row_rs_pagos_int['Vet_Imp'],2,1)."','".$row_rs_interes['Iva_Por']."','".$row_rs_interes['Iva_Cod']."','".$row_rs_interes['Nge_Cod']."','".$row_rs_interes['Deu_Rec']."','".$row_rs_interes['Asi_Int']."','si','si','0','".$row_rs_interes['Cnt_Cod']."','".$row_rs_interes['Deu_Int']."');";
													}
												}
											}
											echo "asignar_total_fac();";
											?>">
											<i class=" icon-arrow-right icon-white"></i>
											</button>
										</form>
										<?
									break;
						 		}
								?>
							</td>
							<?php }?>
							<?php if($Com_Tipo != 1 && $Com_Tipo != 0){?>
							<td>
								<?php 
									switch ($Com_Tipo){

										/**
										 * Modificar una deuda
										 */
										case 2:
											$Saldo_Real = formato_numero($row_rs_deudas['Deu_Val'] -  $row_rs_pagos['Vet_Imp'],2,1);
											$Saldo_Neto = formato_numero($saldo,2,1);
												
											$_aux = "&Com_Saldo_Real=$Saldo_Real&Com_Saldo_Neto=$Saldo_Neto&";
											
											/**
											 * baja de deudas cuando es estudiante
											 */
											if($Cli_Est){
												
												$_aux .= "Modificar=1&Nge_Cod=$Nge_Cod&Com_Beca=$porc_beca";
												
											}else{
												
												/**
												 * baja de estudiantes cuando es cliente
												 */
												$_aux .= "Modificar=2&Cnt_Cod=$Cnt_Cod";
											}
											?>
											<form action="#">
												<button type="button" class="btn btn-primary btn-mini" title="Modificar Deuda" onclick="Muestra_Aparecer();ajax_datos('<?php echo $_SERVER['PHP_SELF']."?codigo=$Com_Codigo&Com_Pro_Cod=$Pro_Cod&Com_Pago=".formato_numero($row_rs_pagos['Vet_Imp'],2,1)."&txt_busqueda=$txt_busqueda&op_opciones=$op_opciones&Asi_Int=$Asi_Int&Deu_Int=$Deu_Int".$_aux;?>','contenido');">
													<i class="icon-edit icon-white"></i>
												</button>
											</form>
											<?php
											break;
										/**
										 * Anular deduda
										 */
										case 3:
											$Saldo_Real = formato_numero($row_rs_deudas['Deu_Val'] -  $row_rs_pagos['Vet_Imp'],2,1);
											$Saldo_Neto = formato_numero($saldo,2,1);
											
											$_aux = "&Com_Saldo_Real=$Saldo_Real&Com_Saldo_Neto=$Saldo_Neto&";
											/**
											 * baja de deudas cuando es estudiante
											 */
											if($Cli_Est){
												$_aux .= "Cancelar=1&Nge_Cod=$Nge_Cod&Com_Beca=$porc_beca";
											}else{
												/**
												* baja de estudiantes cuando es cliente
												*/
												
												$_aux .= "Cancelar=2&Cnt_Cod=$Cnt_Cod";
											}
											?>
												<form action="#">
													<button type="button" class="btn btn-danger btn-mini" title="Cancelar Deuda" onclick="Muestra_Aparecer();ajax_datos('<?php echo $_SERVER['PHP_SELF']."?codigo=$Com_Codigo&Com_Pro_Cod=$Pro_Cod&Com_Pago=".formato_numero($row_rs_pagos['Vet_Imp'],2,1)."&txt_busqueda=$txt_busqueda&op_opciones=$op_opciones&Asi_Int=$Asi_Int&Deu_Int=$Deu_Int".$_aux;?>','contenido');">
														<i class=" icon-trash icon-white"></i>
													</button>
												</form>
											<?php
										break;
									}
								?>
							</td>
							<?php }?>
						</tr>
						<?php
						if(count($Arr_Interes) > 0){
							foreach($Arr_Interes as $row_rs_interes){
								/**
								 * Consulta los pagos realizados segun el Nge_Cod o el Cnt_Cod
								 */
								if($Cli_Est){
									$row_rs_pagos_int = $Com_obBD_con1->getRowConsulta(69, $Com_Codigo.'*'.$row_rs_interes['Pro_Cod'].'*'.$Nge_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
								}else{
									$row_rs_pagos_int = $Com_obBD_con1->getRowConsulta(9, $Com_Codigo.'*'.$row_rs_interes['Pro_Cod'].'*'.$Cnt_Cod.'*'.$Pro_Cod.'*'.$Asi_Int.'*'.$Deu_Int, $Com_obBD_conexion);
								}
																			
								/**
								 * Saldo del interes
								 */
								$saldo_int = round($row_rs_interes['Deu_Val'],2) - round($row_rs_pagos_int['Vet_Imp'],2);
																
								if ($saldo_int > 0){
									
									$saldo_total = $saldo_total + $saldo_int;
									?>
										<tr>
											<td align="center"><FONT COLOR="<? echo $naranja;?>"><strong><? echo $row_rs_interes['Pro_Cod']; ?></strong></FONT></td>
											<td align="left"><FONT COLOR="<? echo $naranja;?>"><strong><? echo $row_rs_interes['Ite_Lar']; ?></strong></FONT></td>
											<td align="center"><FONT COLOR="<? echo $naranja;?>"><strong><? echo $row_rs_interes['Deu_Fec']; ?></strong></FONT></td>						
											<td align="right"><FONT COLOR="<? echo $naranja;?>"><strong><? echo formato_numero($saldo_int,2,4); ?></strong></FONT></td>
											<?php if($Cli_Est){?><td>&nbsp;</td><?php }?>
											<td align="right"><FONT COLOR="<? echo $naranja;?>"><strong><? echo formato_numero($saldo_int,2,4); ?></strong></FONT></td>
											<?php if($Com_Tipo != 1){?><td align="center">
												<form action="#">
													<button type="button" class="btn btn-success btn-mini" title="Ver Detalle" onclick="ajax_datos('<?php echo $_SERVER['PHP_SELF'];?>?<?php echo 'detalle='.(($Cli_Est)? '1&Nge_Cod='.$Nge_Cod.'&Com_Codigo='.$Com_Codigo.'&Bec_Cod='.$row_rs_deudas['Bec_Cod'].'&Pro_Cod='.$Pro_Cod:'2&Cnt_Cod='.$Cnt_Cod);?>','contenido');Muestra_Aparecer();">
														<i class=" icon-info-sign icon-white"></i>
													</button>
												</form>
											</td><?php }?>
											<?php if($Com_Tipo == 1){?><td>&nbsp;</td><?php }?>
											<?php if($Com_Tipo != 1 && $Com_Tipo != 0){?><td>&nbsp;</td><?php } ?>
										</tr>
							      	<?Php
								} 
							}
						}
					}
					
					/**
					 * Para mostrar solo las deudas que debe pagar hasta la fecha actual
					 */
					if($Com_Tipo == 5 && $Cli_Est == false){
						if($hoy <= $row_rs_deudas['Deu_Fec']){
							break;
						}
					}
				}
			}else{
				?>
				<tr>
					<td>&nbsp;</td>
					<td><?php echo error_alerta("No hay resultados que mostrar", 2);?></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<?php if($Cli_Est){?><td>&nbsp;</td><?php }?>
					<?php if($Com_Tipo != 1){ ?><td>&nbsp;</td><?php }?>
					<?php if($Com_Tipo == 1){ ?><td>&nbsp;</td><?php }?>
					<?php if($Com_Tipo != 1 && $Com_Tipo != 0){?><td>&nbsp;</td><?php }?>
				</tr>
				<?php
		}
		?>
			</tbody>
			<tfoot>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<?php if($Cli_Est){?><td>&nbsp;</td><?php }?>
					<td align="right"><strong>TOTAL:</strong></td>
					<td align="right"><strong><?Php echo formato_numero($saldo_total,2,4); ?></strong></td>
					<?php if($Com_Tipo != 1){ ?><td>&nbsp;</td><?php } ?>
					<?php if($Com_Tipo == 1){ ?><td>&nbsp;</td><?php } ?>
					<?php if($Com_Tipo != 1 && $Com_Tipo != 0){?><td>&nbsp;</td><?php }?>
			    </tr>
			</tfoot>
		</table>
		</FIELDSET>
	<?php
	$Com_obBD_con1->liberar();
	$Com_obBD_conexion->cerrar();
	}else{
		echo error_alerta("<< Error de componente: tes_com_deudas.php >> <br>Descripción: No se ha definido la Propiedad: Com_Tipo<br>
				Com_Codigo: Variable que contiene el tipo de requisito para el componente: Alta = 1, Modificacion = 2, Baja = 3, Consulta = 4", 2);
	}
}else{
		echo error_alerta("<< Error de componente: tes_com_deudas.php >> <br>Descripción: No se ha definido la Propiedad: Com_Codigo<br>
				Com_Codigo: Variable que contiene el código del cliente", 2);
}			
?>