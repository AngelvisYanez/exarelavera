<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?
/**
 * Descripcion: Permite consultar la mayorizacion contable
 * Fecha de actualizacion:	2025-03-24
 * Desarrollador: Patricio Moreno
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_reporte_cuenta.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

// Uso para la version local
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 600);


$hoy = date("Y-m-d");

/* OPCIONES */
switch ($op) {
	case 1:
		/* Cargado de los datos de la cabecera */
		if ($txt_busqueda != "") {
			/* Consulta del detalle de la CUENTA buscada */
			$row_cuenta = $obBD_con1->getRowConsulta(314, trim($txt_busqueda) . '*' . $Pla_Cod, $obBD_conexion);
			$Pld_Cod = $row_cuenta['Pld_Cod'];
			/* Consulta del saldo, anterior a la fecha inicial dentro de un mismo periodo (No cambiar esta forma antigua de llamado de las sql) */
			$fech_fut = fechas_futuras($txt_fec_ini, -1);
			$rs_saldos = $obBD_con1->consulta(
				sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $Pld_Cod . '*' . $Pec_Cod)),
				$obBD_conexion->conexion
			);
			$row_rs_saldos = $obBD_con1->registros();
			$total_rs_saldos = $obBD_con1->numregistros();

			/* Se realiza esto porque solo deben haber dos registros */
			/* De los dos supuestos registros encontrados toma por defecto el primero */
			if ($row_rs_saldos['Asi_Deh'] == 'D') {
				$debe = $row_rs_saldos['Asi_Val'];
				/* Mueve el puntero al inicio */
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
			if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) { //Nuevo
				$saldos = $haber - $debe; //Formula especial			
			} else { //Nuevo			
				$saldos = $debe - $haber;
			} //Nuevo		
			/* Consulta del detalle de la mayorizacion */
			$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $Pld_Cod . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
			$total_rs_cuenta = count($rs_cuenta);
			/* Carga el año de la fecha incial */
			list($annn, $mess, $dia) = split('[/.-]', $fech_fut);
			$anio = date("Y", mktime(0, 0, 0, $mess, $dia, $annn));
		} //Fin del if ($txt_busqueda != "")
		break;
	case 2:
		if ($grupo != "") {
			/* Consulta el codigo interno de la cuenta inicial */
			$rs_cuenta_int = $obBD_con1->getRowConsulta(216, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
			$Pld_Cod = $rs_cuenta_int['Pld_Cod'];
			/* Consulta del rango de cuentas para la busqueda */
			//$rs_rango = $obBD_con1->getArrayConsulta(203, $Pld_Cod, $obBD_conexion);
			$rs_rango = $obBD_con1->getArrayConsulta(340, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
			$total_rs_rango = count($rs_rango);
		}
		break;
} //FIn del case $op
?>
<HTML>

<HEAD>
	<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
	<?php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</HEAD>

<BODY>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr align="center" class="Titulos3">
			<td>
				<table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
					<tr align="center">
						<td colspan="4">&nbsp;
							<?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Reporte General " . $periodo, " ", $obBD_conexion); ?>
							&nbsp;</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<?php
				function applyFormat(&$item) {
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
										<td width="182">&nbsp;<?php echo $txt_fec_ini; ?></td>
										<td width="64" class="Texto_Reporte">
											<div align="right"><strong>Hasta:</strong></div>
										</td>
										<td width="198">&nbsp;<?php echo $txt_fec_fin; ?></td>
										<td width="55">&nbsp;</td>
										<td width="101">&nbsp;</td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>GRUPO:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?php echo $row_cuenta['Pld_Cdc']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>Cuenta:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?php echo $row_cuenta['Pld_Des']; ?></td>
									</tr>
								</table>

								<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
									<tr class="Texto_Listados">
										<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
										<td width="3%" align="center" bgcolor="#CCCCCC"><strong>Nº Cuenta.</strong></td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Cuenta. Cont.</strong></div>
										</td>
										<td width="9%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Fecha</strong></div>
										</td>
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

									<?php
									foreach ($result as $r) {
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

										list($annn, $mess, $dia) = split('[/.-]', $fech_fut);
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
											<td align="right" <?php if ($saldos < 0) {
																	echo "style='color:#FF0000'";
																} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
										</tr>

										<?php
										$i = 0;
										if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
											foreach ($rs_cuenta as $row) {

												if (!empty($row['Cli_Cod'])) {
													$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
												}
												if (!empty($row['Prv_Cod'])) {
													$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
												}

												if ($row['Tia_Ini'] == 'I') {
													$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
												} else {
													$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
												}
												$total_rs_proveedore = isset($rs_proveedore) ? count($rs_proveedore) : 0;
												$i++;
												list($ann, $mes, $dia) = split('[/.-]', $row['Com_Fec']);
										?>
												<tr class="Texto_Listados">
													<td align="center"><?php echo $cont; ?></td>
													<td align="center"><?php echo str_replace('.', '', $row['Pld_Cdc']); ?></td>
													<td align="center"><?php echo $row['Pld_Des']; ?></td>
													<td align="center"><?php echo $row['Com_Fec']; ?></td>
													<td align="left" style="white-space: break-spaces; overflow: hidden;"><?php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
													<td align="left" style="white-space: break-spaces; overflow: hidden;"><? echo ($row['Com_Con']); ?>&nbsp;</td>
													<td align="right">
														<?php
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
														<?php
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
													<?php
													$tipo_grupo = explode('.', $txt_busqueda);
													if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
														$saldos = $saldos + ($haber - $debe);
													} else {
														$saldos = $saldos + ($debe - $haber);
													}
													?>
													<td align="right" <?php if ($saldos < 0) {
																			echo "style='color:#FF0000'";
																		} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
												</tr>

											<?php
											} //Fin foreach interno;
											?>

											<tr class="Texto_Listados">
												<td colspan="7" bgcolor="#FFFFFF"><strong>
														<div align="right">TOTAL</div>
													</strong></td>
												<td align="right" bgcolor="#FFFFFF"><strong><?php echo formato_numero($total_debe, 2, 2); ?></strong></td>
												<td align="right" bgcolor="#FFFFFF"><strong><?php echo formato_numero($total_haber, 2, 2); ?></strong></td>
												<!-- <td align="right" bgcolor="#FFFFFF">&nbsp;</td> -->
											</tr>

										<?php
										} else {
										?>

											<tr>
												<td colspan="9"><?php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
											</tr>

									<?php
										} //Fin del else
									} //fin foreach meses	
									?>
								</table>
							<?php
							} else {// fin if mensualizar
							?>
								<!-- Para Imprimir Individual -->
								<table width="669" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
									<tr>
										<td width="43" class="Texto_Reporte">
											<div align="right"><strong>Desde:</strong></div>
										</td>
										<td width="182">&nbsp;<?php echo $txt_fec_ini; ?></td>
										<td width="64" class="Texto_Reporte">
											<div align="right"><strong>Hasta:</strong></div>
										</td>
										<td width="198">&nbsp;<?php echo $txt_fec_fin; ?></td>
										<td width="55">&nbsp;</td>
										<td width="101">&nbsp;</td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>GRUPO:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
									</tr>
									<tr>
										<td class="Texto_Reporte">
											<div align="right"><strong>C&oacute;digo:</strong></div>
										</td>
										<td>&nbsp;<?php echo $row_cuenta['Pld_Cdc']; ?></td>
										<td class="Texto_Reporte">
											<div align="right"><strong>Cuenta:</strong></div>
										</td>
										<td colspan="3">&nbsp;<?php echo $row_cuenta['Pld_Des']; ?></td>
									</tr>
								</table>
								<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
									<tr class="Texto_Listados">

										<td width="3%" align="center" bgcolor="#CCCCCC">Ord.</td>
										<td width="3%" align="center" bgcolor="#CCCCCC"><strong>Nº Cuenta.</strong></td>
										<td width="8%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Cuenta. Cont.</strong></div>
										</td>
										<td width="9%" align="center" bgcolor="#CCCCCC">
											<div align="center"><strong>Fecha</strong></div>
										</td>
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
									<?php
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
											<td align="right" <?php if ($saldos < 0) {
																	echo "style='color:#FF0000'";
																} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
										</tr>
										<? $cont = 0;
										foreach ($rs_cuenta as $row) {
											$cont++;
											/* Consultamos datos Venta */
											if (!empty($row['Cli_Cod'])) {
												$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
											}
											/* Consultamos datos Compras */
											if (!empty($row['Prv_Cod'])) {
												$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
											}
											/*Consulta del cliente o proveedor */
											if ($row['Tia_Ini'] == 'I') {
												/* Consulta la descripcion del cliente */
												$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
											} else {
												/* Consulta la descripcion del proveedor */
												$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
											} //Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
											$total_rs_proveedore = count($rs_proveedore);
											list($ann, $mes, $dia) = split('[/.-]', $row['Com_Fec']);
										?>
											<tr class="Texto_Listados">
												<td align="center"><?php echo $cont; ?></td>
												<td align="center"><?php echo str_replace('.', '', $row['Pld_Cdc']); ?></td>
												<td align="center"><?php echo $row['Pld_Des']; ?></td>
												<td align="center"><?php echo $row['Com_Fec']; ?></td>
												<td align="left" style="white-space: break-spaces; overflow: hidden;"><?php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
												<td align="left" style="white-space: break-spaces; overflow: hidden;"><? echo ($row['Com_Con']); ?>&nbsp;</td>
												<td align="right"><? if ($row['Asi_Deh'] == 'D') {
																		echo formato_numero($row['Asi_Val'], 2, 2);
																		$debe = $row['Asi_Val'];
																		$total_debe = $total_debe + $debe;
																	} else {
																		echo "0,00";
																		$debe = 0;
																	} ?></td>
												<td align="right"><? if ($row['Asi_Deh'] == 'H') {
																		echo formato_numero($row['Asi_Val'], 2, 2);
																		$haber = $row['Asi_Val'];
																		$total_haber = $total_haber + $haber;
																	} else {
																		echo "0,00";
																		$haber = 0;
																	}
																	?></td>
												<?php
												$tipo_grupo = explode('.', $txt_busqueda);
												/**
												 * 1 = Activo
												 * 2 = Pasivo
												 * 3 = Patrimonio
												 * 4 = Ingresos
												 * 5 = Costos y Gastos 
												 */
												if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) { //Nuevo
													$saldos = $saldos + ($haber - $debe); //Formula especial			
												} else { //Nuevo			
													$saldos = $saldos + ($debe - $haber);
												} //Nuevo			
												?>
												<td align="right" <?php if ($saldos < 0) {
																		echo "style='color:#FF0000'";
																	} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
											</tr>
										<?php
										} //Fin foreach;
										?>
										<tr class="Texto_Listados">
											<td colspan="7" bgcolor="#FFFFFF"><strong>
													<div align="right">TOTAL</div>
												</strong></td>
											<td align="right" bgcolor="#FFFFFF"><strong><?php echo formato_numero($total_debe, 2, 2); ?></strong></td>
											<td align="right" bgcolor="#FFFFFF"><strong><?php echo formato_numero($total_haber, 2, 2); ?></strong></td>
											<!-- <td align="right" bgcolor="#FFFFFF">&nbsp;</td> -->
										</tr>
									<?php
									} else { ?>
										<tr>
											<td colspan="9"><?php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
										</tr>
							<? 		} //Fin del else
								}
							} //Fin del if ($txt_busqueda)
							?>
								</table>
								<?php
								break;

							// CASO DE IMPRESION Y EXPORTACION DE TOTALES
							case 2:
								if (isset($grupo)) {
									?>
									<table width="671" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
										<tr>
											<td width="40" class="Texto_Reporte" align="right"><strong>Desde:</strong></td>
											<td width="186">&nbsp;<?php echo $txt_fec_ini; ?></td>
											<td width="62" class="Texto_Reporte" align="right"><strong>Hasta:</strong></td>
											<td width="200">&nbsp;<?php echo $txt_fec_fin; ?></td>
											<td width="58">&nbsp;</td>
											<td width="99">&nbsp;</td>
										</tr>
									</table>
									<hr>
									<?php
							
									$allRows = array();
									$globalCounter = 0;
									$totalDebe = 0;
									$totalHaber = 0;
									$totalSaldo = 0;
									$tipo_grupo = explode('.', $grupo);
							
									if ($total_rs_rango > 0) {
										$fech_fut = fechas_futuras($txt_fec_ini, -1);
							
										foreach ($rs_rango as $row_rango) {
											$rs_saldos = $obBD_con1->consulta(
												sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $row_rango['Pld_Cod'] . '*' . $Pec_Cod)),
												$obBD_conexion->conexion
											);
											$row_rs_saldos = $obBD_con1->registros();
							
											$debe  = ($row_rs_saldos['Asi_Deh'] == 'D') ? $row_rs_saldos['Asi_Val'] : 0;
											$haber = ($row_rs_saldos['Asi_Deh'] == 'H') ? $row_rs_saldos['Asi_Val'] : 0;
											$saldos = in_array($tipo_grupo[0], array(2, 3, 4)) ? $haber - $debe : $debe - $haber;
							
											$rs_cuenta = $obBD_con1->getArrayConsulta(201,
												$txt_fec_ini . '*' . $txt_fec_fin . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut,
												$obBD_conexion
											);
							
											foreach ($rs_cuenta as $row_rs_cuenta) {
												$globalCounter++;
							
												$rs_proveedore = ($row_rs_cuenta['Tia_Ini'] == 'I') ?
													$obBD_con1->getRowConsulta(217, $row_rs_cuenta['Cli_Cod'], $obBD_conexion) :
													$obBD_con1->getRowConsulta(218, $row_rs_cuenta['Prv_Cod'], $obBD_conexion);
												$proveedorStr = $rs_proveedore['Prs_Ape'] . ' ' . $rs_proveedore['Prs_Nom'];
							
												$debe  = ($row_rs_cuenta['Asi_Deh'] == 'D') ? $row_rs_cuenta['Asi_Val'] : 0;
												$haber = ($row_rs_cuenta['Asi_Deh'] == 'H') ? $row_rs_cuenta['Asi_Val'] : 0;
							
												if (in_array($tipo_grupo[0], array(2, 3, 4))) {
													$saldos += ($haber - $debe);
												} else {
													$saldos += ($debe - $haber);
												}
							
												$totalDebe += $debe;
												$totalHaber += $haber;
												$totalSaldo += $saldos; // Se suma el saldo correctamente
							
												$allRows[] = array(
													'Ord'       => $globalCounter,
													'CtaNum'    => str_replace('.', '', $row_rs_cuenta['Pld_Cdc']),
													'CtaCont'   => $row_rs_cuenta['Pld_Des'],
													'Fecha'     => $row_rs_cuenta['Com_Fec'],
													'Proveedor' => $proveedorStr,
													'Detalle'   => $row_rs_cuenta['Com_Con'],
													'Debe'      => $debe,
													'Haber'     => $haber,
													'Saldo'     => $saldos
												);
											}
										}
									}
							
									if (!empty($allRows)) {
										?>
										<table width="100%" border="1" cellpadding="0" cellspacing="0" style="table-layout:fixed; border-collapse:collapse">
											<thead>
												<tr class="Texto_Listados" bgcolor="#CCCCCC">
													<th width="3%" align="center">Ord.</th>
													<th width="6%" align="center">Nº Cuenta</th>
													<th width="8%" align="center">Cuenta Cont.</th>
													<th width="9%" align="center">Fecha</th>
													<th width="22%" align="center">Proveedor</th>
													<th width="20%" align="center">Detalle</th>
													<th width="8%" align="center">Debe</th>
													<th width="8%" align="center">Haber</th>
													<th width="8%" align="center">Saldo</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($allRows as $rowData): ?>
													<tr class="Texto_Listados">
														<td align="center"><?php echo $rowData['Ord']; ?></td>
														<td align="center"><?php echo $rowData['CtaNum']; ?></td>
														<td align="center"><?php echo $rowData['CtaCont']; ?></td>
														<td align="center"><?php echo $rowData['Fecha']; ?></td>
														<td align="left"><?php echo $rowData['Proveedor']; ?></td>
														<td align="left"><?php echo $rowData['Detalle']; ?></td>
														<td align="right"><?php echo formato_numero($rowData['Debe'], 2, 2); ?></td>
														<td align="right"><?php echo formato_numero($rowData['Haber'], 2, 2); ?></td>
														<td align="right" <?php if ($rowData['Saldo'] < 0) echo "style='color:#FF0000'"; ?>>
															<?php echo formato_numero($rowData['Saldo'], 2, 2); ?>
														</td>
													</tr>
												<?php endforeach; ?>
												<tr class="Texto_Listados" bgcolor="#CCCCCC">
													<td colspan="6" align="right"><strong>TOTALES:</strong></td>
													<td align="right"><strong><?php echo formato_numero($totalDebe, 2, 2); ?></strong></td>
													<td align="right"><strong><?php echo formato_numero($totalHaber, 2, 2); ?></strong></td>
													<td align="right"><strong><?php echo formato_numero($totalSaldo, 2, 2); ?></strong></td>
												</tr>
											</tbody>
										</table>
										<br>
										<?php
									}
								}
								break;
							
							
							
						} //Fin del switch
						/* Muestra u oculta el buscador */
						?>
			</td>
		</tr>
		<tr>
			<td align="center"><?php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
		</tr>
	</table>
</BODY>

</HTML>
<?php
/** 
 * Cierra las conexiones 
 */
$obBD_conexion->cerrar();
?>