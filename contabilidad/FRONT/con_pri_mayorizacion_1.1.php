<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Descripciï¿½n: Permite consultar la mayorizacion contable
 * Fecha de actualizaciï¿½n:	2010-11-15 
 * Fecha de actualizaciï¿½n:	2015-05-06 
 * Desarrollador:	Lewis Chimarro 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_mayorizacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");

/**
 * OPCIONES 
 */
switch ($op) {
	case 1:
		/**
		 * Cargado de los datos de la cabecera 
		 */
		if ($txt_busqueda != "") {
			/**
			 * Consulta del detalle de la CUENTA buscada
			 */
			$row_cuenta = $obBD_con1->getRowConsulta(314, trim($txt_busqueda) . '*' . $Pla_Cod, $obBD_conexion);
			$Pld_Cod = $row_cuenta['Pld_Cod'];
			/**
			 * Consulta del saldo, anterior a la fecha inicial dentro de un mismo periodo (No cambiar esta forma antigua de llamado de las sql)
			 */
			$fech_fut = fechas_futuras($txt_fec_ini, -1);
			$rs_saldos = $obBD_con1->consulta(
				sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $Pld_Cod . '*' . $Pec_Cod)),
				$obBD_conexion->conexion
			);
			$row_rs_saldos = $obBD_con1->registros();
			$total_rs_saldos = $obBD_con1->numregistros();

			/**
			 * Se realiza esto porque solo deben haber dos registros 
			 */
			/**
			 * De los dos supuestos registros encontrados toma por defecto el primero 
			 */
			if ($row_rs_saldos['Asi_Deh'] == 'D') {
				$debe = $row_rs_saldos['Asi_Val'];
				/**
				 * Mueve el puntero al inicio 
				 */
				$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
			} else {
				$debe = 0;
			}

			$haber = $row_rs_saldos['Asi_Val'];
			$tipo_grupo = explode('.', $txt_busqueda);
			/**
			 * 1 = Activo
			 * 2 = Pasivo
			 * 3 = Patrimonio
			 * 4 = Ingresos
			 * 5 = Costos y Gastos 
			 */
			if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
			{ //Nuevo
				$saldos = $haber - $debe; //Formula especial			
			} //Nuevo
			else //Nuevo
			{ //Nuevo			
				$saldos = $debe - $haber;
			} //Nuevo		
			/**
			 * Consulta del detalle de la mayorizacin 
			 */
			$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $Pld_Cod . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
			$total_rs_cuenta = count($rs_cuenta);
			/**
			 * Carga el aï¿½o de la fecha incial 
			 */
			list($annn, $mess, $dia) = preg_split('![/.-]!', $fech_fut);
			$anio = date("Y", mktime(0, 0, 0, $mess, $dia, $annn));
		} //Fin del if ($txt_busqueda != "")
		break;
	case 2:
		if ($grupo != "") {
			/**
			 * Consulta el codigo interno de la cuenta inicial 
			 */
			$rs_cuenta_int = $obBD_con1->getRowConsulta(216, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
			$Pld_Cod = $rs_cuenta_int['Pld_Cod'];
			/**
			 * Consulta del rango de cuentas para la busqueda 
			 */
			//$rs_rango = $obBD_con1->getArrayConsulta(203, $Pld_Cod, $obBD_conexion);
			$rs_rango = $obBD_con1->getArrayConsulta(340, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
			$total_rs_rango = count($rs_rango);
		}
		break;
} //FIn del case $op
?>
<HTML>

<HEAD>
	<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</HEAD>

<BODY>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr align="center" class="Titulos3">
			<td>
				<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
					<tr align="center">
						<td colspan="4">&nbsp;
							<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Mayorizaci&oacute;n General " . $periodo, " ", $obBD_conexion); ?>
							&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<?Php
				function applyFormat(&$item)
				{
					$item = date('Y-m-d', $item);
				}
				switch ($op) {
					case 1:
						if (isset($txt_busqueda)) {
							$total_debe = 0;
							$total_haber = 0;

							if (isset($mensualizar) and $mensualizar == 'on') {
								$result = $obBD_con1->getMonthRanges($txt_fec_ini, $txt_fec_fin);
								array_walk_recursive($result, 'applyFormat');
				?>

								<br />
								<table width="669" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
									<tr>
										<td width="43" class="Texto_Reporte">
											<div align="right"><strong>Desde:</strong></div>
										</td>
										<td width="182">&nbsp;<?Php echo $txt_fec_ini; ?></td>
										<td width="64" class="Texto_Reporte">
											<div align="right"><strong>Hasta:</strong></div>
										</td>
										<td width="198">&nbsp;<?Php echo $txt_fec_fin; ?></td>
										<td width="55">&nbsp;</td>
										<td width="101">&nbsp;</td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?Php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>GRUPO:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?Php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?Php echo $row_cuenta['Pld_Cdc']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>Cuenta:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?Php echo $row_cuenta['Pld_Des']; ?></td>
									</tr>
								</table>

								<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
									<tr class="Texto_Listados">
										<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
										<td width="3%" align="center" bgcolor="#CCCCCC"><strong>Gen.</strong></td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>No. Com.</strong></div>
										</td>
										<td width="9%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Fecha</strong></div>
										</td>
										<td width="14%" align="center" bgcolor="#CCCCCC"><strong>No. Fact./ No. Che. </strong></td>
										<td width="22%" align="center" bgcolor="#CCCCCC"><strong>Proveedor</strong></td>
										<td width="20%" bgcolor="#CCCCCC">
											<div align="center"><strong>Detalle</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Debe</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Haber</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Saldo</strong></div>
										</td>
									</tr>

									<?Php
									foreach ($result as $r) {
										$total_debe=0;
										$total_haber=0;
										$rs_cuenta = $obBD_con1->getArrayConsulta(201, $r['start'] . '*' . $r['end'] . '*' . $Pld_Cod . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
										$total_rs_cuenta = count($rs_cuenta);

										$fech_fut = fechas_futuras($r['start'], -1);
										$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $Pld_Cod . '*' . $Pec_Cod)), $obBD_conexion->conexion);
										$row_rs_saldos = $obBD_con1->registros();
										$total_rs_saldos = $obBD_con1->numregistros();


										if ($row_rs_saldos['Asi_Deh'] == 'D') {
											$debe = $row_rs_saldos['Asi_Val'];
											$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
										} else {
											$debe = 0;
										}

										$haber = $row_rs_saldos['Asi_Val'];
										$tipo_grupo = explode('.', $txt_busqueda);

										if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
											$saldos = $haber - $debe;
										} else {
											$saldos = $debe - $haber;
										}

										list($annn, $mess, $dia) = preg_split('![/.-]!', $fech_fut);
										$anio = date("Y", mktime(0, 0, 0, $mess, $dia, $annn));
									?>

										<tr class="Texto_Listados">
											<td align="center">&nbsp;</td>
											<td align="center">&nbsp;</td>
											<td align="center">&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>SALDO AL <?php echo $dia . ', de ' . mes($mess, 1) . ', ' . $anio; ?></td>
											<td align="right">&nbsp;</td>
											<td align="right">&nbsp;</td>
											<td align="right" <?Php if ($saldos < 0) {
																	echo "style='color:#FF0000'";
																} ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
										</tr>

										<?Php
										$i = 0;
										if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
											foreach ($rs_cuenta as $row) {
												$numeroDocCompra = "";
												$numeroDocVenta = "";
												$cheque = $row['Che_Num'];

												if (!empty($row['Cli_Cod'])) {
													$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
													$numeroDocVenta = $row_numDoc['Vet_Num'];
												}
												if (!empty($row['Prv_Cod'])) {
													$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
													$numeroDocCompra = $row_numDoc['Cop_Num'];
												}

												if ($row['Tia_Ini'] == 'I') {

													$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
												} else {
													$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
												}
												$total_rs_proveedore = isset($rs_proveedore) ? count($rs_proveedore) : 0;
												$i++;
												list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
										?>
												<tr class="Texto_Listados">
													<td align="center"><?Php echo $cont; ?></td>
													<td align="center"><?Php echo $row['Com_Gen']; ?></td>
													<td align="center"><?Php echo  $row['Tia_Abr'] . "-" . $mes . "-" . str_pad($row['Com_Num'], 2, "0", STR_PAD_LEFT); ?></td>
													<td align="center"><?Php echo $row['Com_Fec']; ?></td>
													<td align="center"><?php echo $numeroDocVenta . $numeroDocCompra . ($cheque ? 'Cheque No. ' . $cheque : ''); ?></td>
													<td align="left" style="white-space: break-spaces; overflow: hidden;"><?Php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
													<!--td style="white-space: nowrap; overflow: hidden;"><?php echo cadena_mas($row['Com_Con'], 35); ?>&nbsp;</td-->
													<td align="left" style="white-space: break-spaces; overflow: hidden;"><?php echo ($row['Com_Con']); ?>&nbsp;</td>
													<td align="right">
														<?Php
														if ($row['Asi_Deh'] == 'D') {
															echo formato_numero($row['Asi_Val'], 2, 2);
															$debe = $row['Asi_Val'];
															$total_debe = $total_debe + $debe;
														} else {
															echo "0,00";
															$debe = 0;
														}
														?> 
													</td>
													<td align="right">
														<?Php
														if ($row['Asi_Deh'] == 'H') {
															echo formato_numero($row['Asi_Val'], 2, 2);
															$haber = $row['Asi_Val'];
															$total_haber = $total_haber + $haber;
														} else {
															echo "0,00";
															$haber = 0;
														}
														?>
													</td>
													<?Php
													$tipo_grupo = explode('.', $txt_busqueda);
													if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
														$saldos = $saldos + ($haber - $debe);
													} else {
														$saldos = $saldos + ($debe - $haber);
													}
													?>
													<td align="right" <?Php if ($saldos < 0) {
																			echo "style='color:#FF0000'";
																		} ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
												</tr>

											<?Php
											} //Fin foreach interno;
											?>

											<tr class="Texto_Listados">
												<td colspan="7" bgcolor="#FFFFFF"><strong>
														<div align="right">TOTAL</div>
													</strong></td>
												<td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_debe, 2, 2); ?></strong></td>
												<td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_haber, 2, 2); ?></strong></td>
												<td align="right" bgcolor="#FFFFFF">&nbsp;</td>
											</tr>

										<?Php
										} else {
										?>

											<tr>
												<td colspan="9"><?Php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
											</tr>

									<?Php
										} //Fin del else
									} //fin foreach meses	
									?>
								</table>
							<?Php

							} // fin if mensualizar
							else {
							?>

								<table width="669" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
									<tr>
										<td width="43" class="Texto_Reporte">
											<div align="right"><strong>Desde:</strong></div>
										</td>
										<td width="182">&nbsp;<?Php echo $txt_fec_ini; ?></td>
										<td width="64" class="Texto_Reporte">
											<div align="right"><strong>Hasta:</strong></div>
										</td>
										<td width="198">&nbsp;<?Php echo $txt_fec_fin; ?></td>
										<td width="55">&nbsp;</td>
										<td width="101">&nbsp;</td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?Php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>GRUPO:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?Php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?Php echo $row_cuenta['Pld_Cdc']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>Cuenta:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?Php echo $row_cuenta['Pld_Des']; ?></td>
									</tr>
								</table>
								<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
									<tr class="Texto_Listados">

										<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
										<td width="3%" align="center" bgcolor="#CCCCCC"><strong>Gen.</strong></td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>No. Com.</strong></div>
										</td>
										<td width="9%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Fecha</strong></div>
										</td>
										<td width="14%" align="center" bgcolor="#CCCCCC"><strong>No. Fact./ No. Che.</strong></td>
										<td width="22%" align="center" bgcolor="#CCCCCC"><strong>Proveedor</strong></td>
										<td width="20%" bgcolor="#CCCCCC">
											<div align="center"><strong>Detalle</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Debe</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Haber</strong></div>
										</td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Saldo</strong></div>
										</td>
									</tr>
									<?Php
									if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
									?>
										<tr class="Texto_Listados">
											<td align="center">&nbsp;</td>
											<td align="center">&nbsp;</td>
											<td align="center">&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
											<td>SALDO AL <?php echo $dia . ', de ' . mes($mess, 1) . ', ' . $anio; ?></td>
											<td align="right">&nbsp;</td>
											<td align="right">&nbsp;</td>
											<td align="right" <?Php if ($saldos < 0) {
																	echo "style='color:#FF0000'";
																} ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
										</tr>
										<?php $cont = 0;
										foreach ($rs_cuenta as $row) {
											$cont++;
											$cheque = $row['Che_Num'];

											/* Consultamos datos Venta */
											if (!empty($row['Cli_Cod'])) {
												$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
												$numeroDocVenta = $row_numDoc['Vet_Num'];
											}

											/* Consultamos datos Compras */
											if (!empty($row['Prv_Cod'])) {
												$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
												$numeroDocCompra = $row_numDoc['Cop_Num'];
											}
											/**
											 * Consulta del cliente o proveedor 
											 */
											if ($row['Tia_Ini'] == 'I') {
												/**
												 * Consulta la descripcion del cliente 
												 */
												$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
											} else {
												/**
												 * Consulta la descripcion del proveedor 
												 */
												$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
											} //Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
											$total_rs_proveedore = count($rs_proveedore);
											list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
										?>
											<tr class="Texto_Listados">
												<td align="center"><?Php echo $cont; ?></td>
												<td align="center"><?Php echo $row['Com_Gen']; ?></td>
												<td align="center"><?Php echo  $row['Tia_Abr'] . "-" . $mes . "-" . str_pad($row['Com_Num'], 2, "0", STR_PAD_LEFT); ?></td>
												<td align="center"><?Php echo $row['Com_Fec']; ?></td>
												<td align="center"><?php echo $numeroDocVenta . $numeroDocCompra . ($cheque ? 'Cheque No. ' . $cheque : ''); ?></td>
												<td align="left" style="white-space: break-spaces; overflow: hidden;"><?Php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
												<td align="left" style="white-space: break-spaces; overflow: hidden;"><?php echo ($row['Com_Con']); ?>&nbsp;</td>
												<td align="right"><?php if ($row['Asi_Deh'] == 'D') {
																		echo formato_numero($row['Asi_Val'], 2, 2);
																		$debe = $row['Asi_Val'];
																		$total_debe = $total_debe + $debe;
																	} else {
																		echo "0,00";
																		$debe = 0;
																	} ?></td>
												<td align="right"><?php if ($row['Asi_Deh'] == 'H') {
																		echo formato_numero($row['Asi_Val'], 2, 2);
																		$haber = $row['Asi_Val'];
																		$total_haber = $total_haber + $haber;
																	} else {
																		echo "0,00";
																		$haber = 0;
																	}
																	?></td>
												<?Php
												$tipo_grupo = explode('.', $txt_busqueda);
												/**
												 * 1 = Activo
												 * 2 = Pasivo
												 * 3 = Patrimonio
												 * 4 = Ingresos
												 * 5 = Costos y Gastos 
												 */
												if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
												{ //Nuevo
													$saldos = $saldos + ($haber - $debe); //Formula especial			
												} //Nuevo
												else //Nuevo
												{ //Nuevo			
													$saldos = $saldos + ($debe - $haber);
												} //Nuevo			
												?>
												<td align="right" <?Php if ($saldos < 0) {
																		echo "style='color:#FF0000'";
																	} ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
											</tr>
										<?Php
										} //Fin foreach;
										?>
										<tr class="Texto_Listados">
											<td colspan="7" bgcolor="#FFFFFF"><strong>
													<div align="right">TOTAL</div>
												</strong></td>
											<td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_debe, 2, 2); ?></strong></td>
											<td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_haber, 2, 2); ?></strong></td>
											<td align="right" bgcolor="#FFFFFF">&nbsp;</td>
										</tr>
									<?Php
									} else { ?>
										<tr>
											<td colspan="9"><?Php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
										</tr>
							<?php 		} //Fin del else
								}
							} //Fin del if ($txt_busqueda)
							?>
								</table>
								<?Php
								break;
							case 2:
								if (isset($grupo)) {
									if (isset($mensualizar) and $mensualizar == 'on') {
										$result = $obBD_con1->getMonthRanges($txt_fec_ini, $txt_fec_fin);
										array_walk_recursive($result, 'applyFormat');
								?>

										<table width="671" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
											<tr>
												<td width="40" class="Texto_Reporte">
													<div align="right"><strong>Desde</strong>:</div>
												</td>
												<td width="186">&nbsp;<?Php echo $txt_fec_ini; ?></td>
												<td width="62" class="Texto_Reporte">
													<div align="right"><strong>Hasta</strong>:</div>
												</td>
												<td width="200">&nbsp;<?Php echo $txt_fec_fin; ?></td>
												<td width="58">
													<div align="right"></div>
												</td>
												<td width="99">&nbsp;</td>
											</tr>
										</table>
										<hr>
										<?Php
										if ($total_rs_rango > 0) {
											$i = 0;
											foreach ($rs_rango as $row_rango) {
												$total_debe = 0;
												$total_haber = 0;
												$saldo = 0;
												$fech_fut = fechas_futuras($txt_fec_ini, -1);
												$rs_saldos = $obBD_con1->consulta(
													sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $row_rango['Pld_Cod'] . '*' . $Pec_Cod)),
													$obBD_conexion->conexion
												);
												$row_rs_saldos = $obBD_con1->registros();
												$total_rs_saldos = $obBD_con1->numregistros();
												if ($row_rs_saldos['Asi_Deh'] == 'D') {
													$debe = $row_rs_saldos['Asi_Val'];
													$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
												} else {
													$debe = 0;
												}

												$haber = $row_rs_saldos['Asi_Val'];
												$tipo_grupo = explode('.', $grupo);
												if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
													$saldos = $haber - $debe;
												} else {
													$saldos = $debe - $haber;
												}

												$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
												$total_rs_cuenta = count($rs_cuenta);

												if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
													$row_recur = $obBD_con1->getRowConsulta(204, $row_rango['Pld_Rec'], $obBD_conexion);
										?>


													<table width="754" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte" style="table-layout:fixed; border-collapse:collapse">
														<tr>
															<td class="Texto_Reporte">
																<div align="right"><strong>C&oacute;digo:</strong></div>
															</td>
															<td>&nbsp;<?Php echo $row_recur['Pld_Cdc']; ?></td>
															<td class="Texto_Reporte">
																<div align="right"><strong>GRUPO:</strong></div>
															</td>
															<td><?Php echo $row_recur['Pld_Des']; ?>&nbsp;</td>
														</tr>
														<tr>
															<td width="53" class="Texto_Reporte">
																<div align="right"><strong>C&oacute;digo:</strong></div>
															</td>
															<td width="117">&nbsp;<?Php echo $row_rango['Pld_Cdc']; ?></td>
															<td width="61" class="Texto_Reporte">
																<div align="right"><strong>Cuenta:</strong></div>
															</td>
															<td width="523">&nbsp;<?Php echo $row_rango['Pld_Des']; ?></td>
														</tr>
													</table>

													<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
														<tr class="Texto_Listados">
															<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
															<td width="3%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Gen.</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>No. Com.</strong></div>
															</td>
															<td width="9%" bgcolor="#CCCCCC">
																<div align="center"><strong>Fecha</strong></div>
															</td>
															<td width="14%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>No. Fact./ No. Che.</strong></div>
															</td>
															<td width="22%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Proveedor</strong></div>
															</td>
															<td width="20%" bgcolor="#CCCCCC">
																<div align="center"><strong>Detalle</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Debe</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Haber</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Saldo</strong></div>
															</td>
														</tr>

														<?php
														foreach ($result as $r) {															
															$registrosCuenta = $obBD_con1->getArrayConsulta(201, $r['start'] . '*' . $r['end'] . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);

															$fech_fut = fechas_futuras($r['start'], -1);
															list($ann, $mes, $dia) = preg_split('![/.-]!', $fech_fut);
															$anio = date("Y", mktime(0, 0, 0, $mes, $dia, $ann));
														?>
															<tr class="Texto_Listados">
																<td align="center">&nbsp;</td>
																<td align="center">&nbsp;</td>
																<td>&nbsp;</td>
																<td>&nbsp;</td>
																<td>&nbsp;</td>
																<td>SALDO AL <?php echo $dia . ', de ' . mes($mes, 1) . ', ' . $anio; ?></td>
																<td align="right">&nbsp;</td>
																<td align="right">&nbsp;</td>
																<td align="right" <?Php if ($saldos < 0) {echo "style='color:#FF0000'";} ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
															</tr>
															<?php
															$cont = 0;
															foreach ($registrosCuenta as $row_rs_cuenta) {
																$cont++;
																$cheque = $row_rs_cuenta['Che_Num'];
																if (!empty($row['Cli_Cod'])) {
																	$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																	$numeroDocVenta = $row_numDoc['Vet_Num'];
																}
																if (!empty($row['Prv_Cod'])) {
																	$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																	$numeroDocCompra = $row_numDoc['Cop_Num'];
																}
																if ($row_rs_cuenta['Tia_Ini'] == 'I') {
																	$rs_proveedore = $obBD_con1->getRowConsulta(217, $row_rs_cuenta['Cli_Cod'], $obBD_conexion);
																} else {
																	$rs_proveedore = $obBD_con1->getRowConsulta(218, $row_rs_cuenta['Prv_Cod'], $obBD_conexion);
																}
$total_rs_proveedore = count($rs_proveedore ?? []);
															?>
																<tr class="Texto_Listados">
																	<td align="center"><?Php echo $cont; ?></td>
																	<td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
																	<td align="center"><?Php echo  "C" . $row_rs_cuenta['Tia_Ini'] . "-" . $mes . "-" . $row_rs_cuenta['Com_Num']; ?>&nbsp;</td>
																	<td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?>&nbsp;</td>
																	<td align="center"><?php echo $numeroDocVenta . $numeroDocCompra . ($cheque ? 'Cheque No. ' . $cheque : ''); ?></td>
																	<td align="left" style="white-space: nowrap; overflow: hidden;"><?Php echo $rs_proveedore['Prs_Ape'] . ' ' . $rs_proveedore['Prs_Nom']; ?></td>
																	<td align="left" style="white-space: nowrap; overflow: hidden;"><?php echo ($row_rs_cuenta['Com_Con']); ?>&nbsp;</td>
																	
																	
																	<td width="9%" align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'D') {
																										echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																										$debe = $row_rs_cuenta['Asi_Val'];
																										$total_debe = $total_debe + $debe;
																									} else {
																										echo "0.00";
																										$debe = 0;
																									} ?></td>
																	<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'H') {
																							echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																							$haber = $row_rs_cuenta['Asi_Val'];
																							$total_haber = $total_haber + $haber;
																						} else {
																							echo "0.00";
																							$haber = 0;
																						}
																						?></td>
																	<?Php
																	$tipo_grupo = explode('.', $grupo);
																	if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
																		$saldos = $saldos + ($haber - $debe);
																	} else {
																		$saldos = $saldos + ($debe - $haber);
																	}
																	?>
																	<td align="right" <?Php if ($saldos < 0) {echo "style='color:#FF0000'";} ?>><?Php echo formato_numero($saldos, 2, 2);?></td>
																</tr>
															<?Php
															} //Fin $row_rs_cuenta
															?>
															<tr class="Texto_Listados">
																<td colspan="6" align="right" style="font-weight: bold;">Desde: <?Php echo $r['start'] ?> Hasta: <?Php echo $r['end'] ?></td>
																<td colspan="1">
																	<div align="right"><strong>TOTAL</strong></div>
																</td>
																<td align="right"><strong><?Php echo formato_numero($total_debe, 2, 2); ?></strong></td>
																<td align="right"><strong><?Php echo formato_numero($total_haber, 2, 2); ?></strong></td>
																<td align="right">&nbsp;</td>
															</tr>

														<?Php
														} //fin del foreach de meses
														?>

													</table>
													<br>
										<?Php
												} // Fin del if ($total_rs_cuenta > 0)
											} //Fin $row_rs_rango 
										} //Fin del if ($total_rs_rango > 0)
									} //fin del if mensualizar grupo
									else { //else del if mensualizar
										?>

										<table width="671" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
											<tr>
												<td width="40" class="Texto_Reporte">
													<div align="right"><strong>Desde</strong>:</div>
												</td>
												<td width="186">&nbsp;<?Php echo $txt_fec_ini; ?></td>
												<td width="62" class="Texto_Reporte">
													<div align="right"><strong>Hasta</strong>:</div>
												</td>
												<td width="200">&nbsp;<?Php echo $txt_fec_fin; ?></td>
												<td width="58">
													<div align="right"></div>
												</td>
												<td width="99">&nbsp;</td>
											</tr>
										</table>
										<hr>
										<?Php
										if ($total_rs_rango > 0) {
											$i = 0;
											foreach ($rs_rango as $row_rango) {
												$total_debe = 0;
												$total_haber = 0;
												$saldo = 0;
												/**
												 * Consulta del saldo, anterior a la inicial 
												 */
												$fech_fut = fechas_futuras($txt_fec_ini, -1);
												$rs_saldos = $obBD_con1->consulta(
													sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $row_rango['Pld_Cod'] . '*' . $Pec_Cod)),
													$obBD_conexion->conexion
												);
												$row_rs_saldos = $obBD_con1->registros();
												$total_rs_saldos = $obBD_con1->numregistros();
												/**
												 * Se realiza esto porque solo deben haber dos registros 
												 */
												/**
												 * De los dos supuestos registros encontrados toma por defecto el primero 
												 */
												if ($row_rs_saldos['Asi_Deh'] == 'D') {
													$debe = $row_rs_saldos['Asi_Val'];
													/**
													 * Mueve el puntero al inicio 
													 */
													$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
												} else {
													$debe = 0;
												}

												$haber = $row_rs_saldos['Asi_Val'];
												$tipo_grupo = explode('.', $grupo);
												/**
												 * 1 = Activo
												 * 2 = Pasivo
												 * 3 = Patrimonio
												 * 4 = Ingresos
												 * 5 = Costos y Gastos 
												 */
												if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
												{ //Nuevo
													$saldos = $haber - $debe; //Formula especial			
												} //Nuevo
												else //Nuevo
												{ //Nuevo			
													$saldos = $debe - $haber;
												} //Nuevo			
												/**
												 * Consulta del detalle de la mayorizacin 
												 */
												$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .
													$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
												$total_rs_cuenta = count($rs_cuenta);
												/**
												 * Carga el aï¿½o de la fecha incial 
												 */
												list($ann, $mes, $dia) = preg_split('![/.-]!', $fech_fut);
												$anio = date("Y", mktime(0, 0, 0, $mes, $dia, $ann));

												if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
													/**
													 * Consulta del detallete de la CUENTA 
													 */
													$row_recur = $obBD_con1->getRowConsulta(204, $row_rango['Pld_Rec'], $obBD_conexion);
										?>
													<table width="754" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte" style="table-layout:fixed; border-collapse:collapse">
														<tr>
															<td class="Texto_Reporte">
																<div align="right"><strong>C&oacute;digo:</strong></div>
															</td>
															<td>&nbsp;<?Php echo $row_recur['Pld_Cdc']; ?></td>
															<td class="Texto_Reporte">
																<div align="right"><strong>GRUPO:</strong></div>
															</td>
															<td><?Php echo $row_recur['Pld_Des']; ?>&nbsp;</td>
														</tr>
														<tr>
															<td width="53" class="Texto_Reporte">
																<div align="right"><strong>C&oacute;digo:</strong></div>
															</td>
															<td width="117">&nbsp;<?Php echo $row_rango['Pld_Cdc']; ?></td>
															<td width="61" class="Texto_Reporte">
																<div align="right"><strong>Cuenta:</strong></div>
															</td>
															<td width="523">&nbsp;<?Php echo $row_rango['Pld_Des']; ?></td>
														</tr>
													</table>

													<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
														<tr class="Texto_Listados">
															<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
															<td width="3%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Gen.</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>No. Com.</strong></div>
															</td>
															<td width="9%" bgcolor="#CCCCCC">
																<div align="center"><strong>Fecha</strong></div>
															</td>
															<td width="14%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>No. Fact./ No. Che.</strong></div>
															</td>
															<td width="22%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Proveedor</strong></div>
															</td>
															<td width="20%" bgcolor="#CCCCCC">
																<div align="center"><strong>Detalle</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Debe</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Haber</strong></div>
															</td>
															<td width="8%" align="center" bgcolor="#CCCCCC">
																<div align="center"><strong>Saldo</strong></div>
															</td>
														</tr>
														<tr class="Texto_Listados">
															<td align="center">&nbsp;</td>
															<td align="center">&nbsp;</td>
															<td>&nbsp;</td>
															<td>&nbsp;</td>
															<td>&nbsp;</td>
															<td>SALDO AL <?php echo $dia . ', de ' . mes($mes, 1) . ', ' . $anio; ?></td>
															<td align="right">&nbsp;</td>
															<td align="right">&nbsp;</td>
															<td align="right" <?Php if ($saldos < 0) {
																					echo "style='color:#FF0000'";
																				} ?>><?Php
																																		echo formato_numero($saldos, 2, 2); ?></td>
														</tr>
														<?php
														$cont = 0;
														foreach ($rs_cuenta as $row_rs_cuenta) {
															$cont++;
															$cheque = $row_rs_cuenta['Che_Num'];
															/* Consultamos datos Venta */
															if (!empty($row['Cli_Cod'])) {
																$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																$numeroDocVenta = $row_numDoc['Vet_Num'];
															}

															/* Consultamos datos Compras */
															if (!empty($row['Prv_Cod'])) {
																$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																$numeroDocCompra = $row_numDoc['Cop_Num'];
															}
															/**
															 * Consulta del cliente o proveedor 
															 */
															if ($row_rs_cuenta['Tia_Ini'] == 'I') {
																/**
																 * Consulta la descripcion del cliente 
																 */
																$rs_proveedore = $obBD_con1->getRowConsulta(217, $row_rs_cuenta['Cli_Cod'], $obBD_conexion);
															} else {
																/**
																 * Consulta la descripcion del proveedor 
																 */
																$rs_proveedore = $obBD_con1->getRowConsulta(218, $row_rs_cuenta['Prv_Cod'], $obBD_conexion);
															} //Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
															$total_rs_proveedore = count($rs_proveedore);
														?>
															<tr class="Texto_Listados">
																<td align="center"><?Php echo $cont; ?></td>
																<td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
																<td align="center"><?Php echo  "C" . $row_rs_cuenta['Tia_Ini'] . "-" . $mes . "-" . $row_rs_cuenta['Com_Num']; ?>&nbsp;</td>
																<td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?>&nbsp;</td>
																<td align="center"><?php echo $numeroDocVenta . $numeroDocCompra . ($cheque ? 'Cheque No. ' . $cheque : ''); ?></td>
																<td align="left" style="white-space: break-spaces; overflow: hidden;"><?Php echo $rs_proveedore['Prs_Ape'] . ' ' . $rs_proveedore['Prs_Nom']; ?></td>
																<td align="left" style="white-space: nowrap; overflow: hidden;"><?php echo $row_rs_cuenta['Com_Con']; ?>&nbsp;</td>
																<td width="9%" align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'D') {
																									echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																									$debe = $row_rs_cuenta['Asi_Val'];
																									$total_debe = $total_debe + $debe;
																								} else {
																									echo "0.00";
																									$debe = 0;
																								} ?></td>
																<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'H') {
																						echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																						$haber = $row_rs_cuenta['Asi_Val'];
																						$total_haber = $total_haber + $haber;
																					} else {
																						echo "0.00";
																						$haber = 0;
																					}
																					?></td>
																<?Php
																$tipo_grupo = explode('.', $grupo);
																/**
																 * 1 = Activo
																 * 2 = Pasivo
																 * 3 = Patrimonio
																 * 4 = Ingresos
																 * 5 = Costos y Gastos 
																 */
																if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
																{ //Nuevo
																	$saldos = $saldos + ($haber - $debe); //Formula especial			
																} //Nuevo
																else //Nuevo
																{ //Nuevo			
																	$saldos = $saldos + ($debe - $haber);
																} //Nuevo												
																?>
																<td align="right" <?Php if ($saldos < 0) {
																						echo "style='color:#FF0000'";
																					} ?>><?Php
																																			echo formato_numero($saldos, 2, 2);
																																			?></td>
															</tr>
														<?Php
														} //Fin $row_rs_cuenta
														?>
														<tr class="Texto_Listados">
															<td colspan="7">
																<div align="right"><strong>TOTAL</strong></div>
															</td>
															<td align="right"><strong><?Php echo formato_numero($total_debe, 2, 2); ?></strong></td>
															<td align="right"><strong><?Php echo formato_numero($total_haber, 2, 2); ?></strong></td>
															<td align="right">&nbsp;</td>
														</tr>
													</table>
													<br>
						<?Php
												} // Fin del if ($total_rs_cuenta > 0)
											} //Fin $row_rs_rango 
										} //Fin del if ($total_rs_rango > 0)
									} //fin del else mensualizar
								} //Fin del if (isset($grupo))
								break;
						} //Fin del switch
						/* Muestra u oculta el buscador */
						?>
			</td>
		</tr>
		<tr>
			<td align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
		</tr>
	</table>
</BODY>

</HTML>
<?Php
/** 
 * Cierra las conexiones 
 */
$obBD_conexion->cerrar();
?>