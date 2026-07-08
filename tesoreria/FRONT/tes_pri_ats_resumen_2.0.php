<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
/**
 * pagina para generar Resumen Anexo Transaccional
 *
 * @author Jose Cumbicos
 * Ultima Actualizaci�n: 28-05-2016
 *
 * @package tesoreria
 */

require_once('../../Librerias/config.php/register_globals.php');
require_once($APP_REAL_PATH . '/administrador/LOGICA/logica.php');
require_once('../LOGICA/tes_log_anexo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
ini_set("memory_limit", "32M");
ini_set('max_execution_time', 300);
/** 
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_Anx($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_Anx;

/**
 *   Variables para Encabezado
 */
$Titulo = "TALON DE RESUMEN";
$mesIni = explode('-', $ini);
$mesFin = explode('-', $fin);
$mesLabel = ($mesFin[1]!==$mesIni[1]) ? ($mesIni[2].'/'.mes($mesIni[1], 1).' AL '.$mesFin[2].'/'.mes($mesFin[1], 1)).' DEL ' : mes($mesFin[1], 1);
$Subtitulo = "ANEXO TRANSACCIONAL " . $mesLabel . ' ' . $mesIni[0];
$row_rs_PorIva = $obBD_con1->getRowConsulta(876, $ini, $obBD_conexion);

function tipoCompr($cod)
{
	switch ($cod) {
		case 1:
			$x = 'FACTURA';
			break;
		case 2:
			$x = 'NOTA DE VENTA';
			break;
		case 3:
			$x = 'LIQUIDACION DE COMPRA';
			break;
		case 4:
			$x = 'NOTA DE CREDITO';
			break;
		case 5:
			$x = 'NOTA DE DEBITO';
			break;
		case 7:
			$x = 'RETENCION';
			break;
		case 15:
			$x = "COMPROBANTE DE VENTA EMITIDO EN EL EXTERIOR";
			break;

		case 18:
			$x = 'DOCUMENTOS AUTORIZADOS EN VENTAS EXCEPTO ND Y NC';
			break;
		case 21:
			$x = 'CARTA DE PORTE AEREO';
			break;
		case 41:
			$x = 'COMPROBANTES DE VENTA EMITIDO POR REEMBOLSO';
			break;
	}
	return $x;
}
function codigoRenta($cod)
{
	$x = '';
	switch ($cod) {
		case '303':
			$x = 'SERVICIOS HONORARIOS PROFESIONALES Y DIETAS';
			break;

		case '303A':
			$x = 'SERVICIOS PROFESIONALES PRESTADOS POR SOCIEDADES RESIDENTES';
			break;

		case '304':
			$x = 'SERVICIOS PREDOMINA EL INTELECTO';
			break;
		case '304A':
			$x = 'COMISIONES Y DEMÁS PAGOS POR SERVICIOS PREDOMINA INTELECTO NO RELACIONADOS CON EL T�TULO PROFESIONAL';
			break;
		case '304B':
			$x = 'PAGO A NOTARIOS Y REGISTRADORES A LA PROPIEDAD';
			break;
		case '304E':
			$x = 'HONORARIOS Y DEMAS PAGOS POR SERVICIOS DE DOCENCIA';
			break;
		case '307':
			$x = 'SERVICIOS PREDOMINA MANO DE OBRA';
			break;
		case '308':
			$x = 'SERVICIOS ENTRE SOCIEDADES';
			break;
		case '309':
			$x = 'SERVICIOS PUBLICIDAD Y COMUNICACION';
			break;
		case '310':
			$x = 'SERVICIOS TRANSPORTE PRIVADO DE PASAJEROS O SERVICIO PUBLICO O PRIVADO DE CARGA';
			break;
		case '312':
			$x = 'TRANSFERENCIA DE BIENES MUEBLES DE NATURALEZA CORPORAL';
			break;
		case '312A':
			$x = 'COMPRA DE BIENES DE ORIGEN AGRICOLA,AVICOLA,ETC';
			break;
		case '312V':
			$x = 'RETENCION EN LA FUENTE RENTA VENTAS';
			break;
		case '319':
			$x = 'ARRENDAMIENTOS MERCANTIL';
			break;
		case '320':
			$x = 'ARRENDAMIENTOS BIENES INMUEBLES';
			break;
		case '322':
			$x = 'SEGUROS Y REASEGUROS (PRIMAS Y SESIONES)';
			break;
		case '323':
			$x = 'RENDIMIENTOS FINANCIEROS PAGADOS A NATURALES Y SOCIEDADES (NO A IFIS)';
			break;
		case '323A':
			$x = "RENDIMIENTOS FINANCIEROS: DEPÓSITOS CTA. CORRIENTE";
			break;
		case '323E':
			$x = 'RENDIMIENTOS FINANCIEROS: DEPOSITO A PLAZO FIJO GRAVADOS';
			break;
		case '325':
			$x = 'LOTERIAS, RIFAS, APUESTAS, Y SIMILARES';
			break;
		case '327':
			$x = 'VENTAS DE COMBUSTIBLE A COMERCIALIZADORAS';
			break;
		case '328':
			$x = 'VENTAS DE COMBUSTIBLE A DISTRIBUIDORES';
			break;
		case '332':
			$x = 'OTRAS COMPRAS DE BIENES Y SERVICIOS NO SUJETAS A RETENCION';
			break;
		case '332G':
			$x = 'PAGOS CON TARJETA DE CREDITO';
			break;
		case '332I':
			$x = 'PAGO A TRAVÉS DE CONVENIO DE DEBITO';
			break;
		case '338':
			$x = 'COMPRAS LOCAL DE BANANO A PRODUCTOR';
			break;
		case '340':
			$x = 'OTRAS RETENCIONES APLICABLES 1%';
			break;
		case '341':
			$x = 'OTRAS RETENCIONES APLICABLES EL 2%';
			break;
		case '342':
			$x = 'OTRAS RENTENCIONES APLICABLES EL 8%';
			break;
		case '343':
			$x = 'OTRAS RETENCIONES APLICABLES EL 1%';
			break;
		case '343A':
			$x = 'POR ENERGIA ELECTRICA';
			break;
		case '343B':
			$x = 'POR ACTIVIDADES DE CONSTRUCCION DE OBRA MATERIAL INMUEBLE URBANIZACIÓN LOTIZACION O ACTIVIDADES SIM';
			break;
		case '344':
			$x = 'OTRAS RENTENCIONES APLICABLES EL 2%';
			break;
		case '344B':
			$x = 'ADQUISICION DE SUSTANCIAS MINERALES DENTRO DEL TERRITORIO NACIONAL';
			break;
		case '3440':
			$x = 'OTRAS RENTENCIONES APLICABLES EL 2.75%';
			break;
		case '344V':
			$x = 'OTRAS RETENCIONES APLICABLES AL 2%';
			break;
		case '345':
			$x = 'OTRAS RENTENCIONES APLICABLES EL 8%';
			break;
		case '348':
			$x = 'COMPRAS LOCAL DE BANANO A PRODUCTO';
			break;
		case '505':
			$x = 'PAGOS A NO RESIDENTES - INTERESES DE OTROS CREDITOS EXTERNOS';
			break;
	}
	return $x;
}

function buscaCodigo($auxArr, $valor)
{
	$x = 0;
	foreach ($auxArr as $clave => $cod) {
		$xy = $x;
		if ((string)$cod['Cod'] == $valor) {
			break;
		} else {
			$xy = "falso";
		}
		$x++;
	}
	return $xy;
}

$responce = array('success' => false);
try {
	require_once('../../Librerias/Xml/XML.php');
	$xml = simplexml_load_file($url);
	$retVtaArr = array(
		array("id" => 1, "nom" => "Valor de IVA que le han retenido", "val" => 0),
		array("id" => 2, "nom" => "Valor de Renta que le han retenido", "val" => 0)
	);
	$retIvaComArr = array(
		array("id" => 1, "nom" => "Retencion IVA 10%", "val" => 0),
		array("id" => 2, "nom" => "Retencion IVA 20%", "val" => 0),
		array("id" => 3, "nom" => "Retencion IVA 30%", "val" => 0),
		array("id" => 4, "nom" => "Retencion IVA 50%", "val" => 0),
		array("id" => 5, "nom" => "Retencion IVA 70%", "val" => 0),
		array("id" => 6, "nom" => "Retencion IVA 100%", "val" => 0)
	);
	/*COMPROBANTES DE COMPRAS*/
	foreach ($xml->compras->detalleCompras as $dato) {

		if (isset($comArr)) {
			$flag = buscaCodigo($comArr, (string)$dato->tipoComprobante);
			if ($flag === "falso") {
				array_push($comArr, array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1, 'NoGraIva' => (float)$dato->baseNoGraIva, "Sub0" => (float)$dato->baseImponible, "Sub12" => (float)$dato->baseImpGrav, "iva" => (float)$dato->montoIva));
			} else {
				$comArr[$flag]['NoGraIva'] += (float)$dato->baseNoGraIva;
				$comArr[$flag]['Sub0'] += (float)$dato->baseImponible;
				$comArr[$flag]['Sub12'] += (float)$dato->baseImpGrav;
				$comArr[$flag]['iva'] += (float)$dato->montoIva;
				$comArr[$flag]['reg']++;
			}
		} else {
			$comArr = array(array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1, 'NoGraIva' => (float)$dato->baseNoGraIva, "Sub0" => (float)$dato->baseImponible, "Sub12" => (float)$dato->baseImpGrav, "iva" => (float)$dato->montoIva));
		}
		/*IVA EN COMPRAS*/
		$retIvaComArr[0]['val'] += (float)$dato->valRetBien10;
		$retIvaComArr[1]['val'] += (float)$dato->valRetServ20;
		$retIvaComArr[2]['val'] += (float)$dato->valorRetBienes;
		$retIvaComArr[3]['val'] += (float)$dato->valRetServ50;
		$retIvaComArr[4]['val'] += (float)$dato->valorRetServicios;
		$retIvaComArr[5]['val'] += (float)$dato->valRetServ100;
		/*RETENCIONES DE RENTA EN COMPRAS*/
		$retnCmp = $dato->air;
		//var_dump($retnCmp);
		if (!empty($retnCmp->detalleAir))
			foreach ($retnCmp->detalleAir as $ret) {
				if (isset($retCmpArr)) {
					$flag = buscaCodigo($retCmpArr, (string)$ret->codRetAir);
					if ($flag === "falso") {
						array_push($retCmpArr, array("Cod" => (string)$ret->codRetAir, "reg" => 1, "base" => (float)$ret->baseImpAir, "val" => (float)$ret->valRetAir));
					} else {
						$retCmpArr[$flag]["base"] += (float)$ret->baseImpAir;
						$retCmpArr[$flag]["val"] += (float)$ret->valRetAir;
						$retCmpArr[$flag]["reg"]++;
					}
				} else {
					$retCmpArr = array(array("Cod" => (string)$ret->codRetAir, "reg" => 1, "base" => (float)$ret->baseImpAir, "val" => (float)$ret->valRetAir));
				}
			}
	}
	/*COMPROBANTE DE VENTAS*/
	foreach ($xml->ventas->detalleVentas as $dato) {
		if (isset($vntArr)) {
			$flag = buscaCodigo($vntArr, (string)$dato->tipoComprobante);
			if ($flag === "falso") {
				array_push($vntArr, array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => $dato->numeroComprobantes * 1, 'NoGraIva' => (float)$dato->baseNoGraIva, "Sub0" => (float)$dato->baseImponible, "Sub12" => (float)$dato->baseImpGrav, "iva" => (float)$dato->montoIva));
			} else {
				$vntArr[$flag]['NoGraIva'] += (float)$dato->baseNoGraIva;
				$vntArr[$flag]['Sub0'] += (float)$dato->baseImponible;
				$vntArr[$flag]['Sub12'] += (float)$dato->baseImpGrav;
				$vntArr[$flag]['iva'] += (float)$dato->montoIva;
				$vntArr[$flag]['reg'] += /*(int)*/$dato->numeroComprobantes * 1;
			}
		} else {
			$vntArr = array(array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => $dato->numeroComprobantes * 1, 'NoGraIva' => (float)$dato->baseNoGraIva, "Sub0" => (float)$dato->baseImponible, "Sub12" => (float)$dato->baseImpGrav, "iva" => (float)$dato->montoIva));
		}
		/*IVA Y RENTA EN VENTAS*/
		$retVtaArr[0]['val'] += (float)$dato->valorRetIva;
		$retVtaArr[1]['val'] += (float)$dato->valorRetRenta;
	}


	/*COMPROBANTES ANULADOS*/
	foreach ($xml->anulados->detalleAnulados as $dato) {
		if (isset($anuladoArr)) {
			$flag = buscaCodigo($anuladoArr, (string)$dato->tipoComprobante);
			if ($flag === "falso") {
				array_push($anuladoArr, array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1));
			} else {
				$anuladoArr[$flag]['reg']++;
			}
		} else {
			$anuladoArr = array(array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1));
		}
	}
	/*COMPROBANTES EXPORTACION*/
	if (!empty($xml->exportaciones))
		foreach ($xml->exportaciones->detalleExportaciones as $dato) {
			if (isset($exporArr)) {
				$flag = buscaCodigo($exporArr, (string)$dato->tipoComprobante);
				if ($flag === "falso") {
					array_push($exporArr, array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1, "val" => (float)$dato->valorFOB));
				} else {
					$exporArr[$flag]['val'] += (float)$dato->valorFOB;
					$exporArr[$flag]['reg']++;
				}
			} else {
				$exporArr = array(array("Cod" => (string)$dato->tipoComprobante, "nom" => tipoCompr((string)$dato->tipoComprobante), "reg" => 1, "val" => (float)$dato->valorFOB));
			}
		}
	/*RETENCIONES FINANCIERAS*/
	if (!empty($xml->rendFinancieros))
		foreach ($xml->rendFinancieros->detalleRendFinancieros as $dato) {
			if (isset($rndFinArr)) {
				$rndFinArr[0]['dep'] += (float)$dato->ahorroPN->totalDep;
				$rndFinArr[0]['gen'] += (float)$dato->ahorroPN->rendGen;

				$rndFinArr[1]['dep'] += (float)$dato->ctaExenta->totalDep;
				$rndFinArr[1]['gen'] += (float)$dato->ctaExenta->rendGen;
			} else {
				$rndFinArr = array(
					array("nom" => "Ahorros Personas Naturales", "dep" => (float)$dato->ahorroPN->totalDep, "gen" => (float)$dato->ahorroPN->rendGen),
					array("nom" => "Cuentas Exentas", "dep" => (float)$dato->ctaExenta->totalDep, "gen" => (float)$dato->ctaExenta->rendGen)
				);
			}
			/*RETENCIONES*/
			$retnFin = $dato->retenciones;
			foreach ($retnFin->detRet as $ret) {
				$codRet = $ret->airRend;
				foreach ($codRet->detalleAirRen as $info) {
					if (isset($codFinArr)) {
						$flag = buscaCodigo($codFinArr, (string)$info->codRetAir);
						if ($flag === "falso") {
							array_push($codFinArr, array("Cod" => (string)$info->codRetAir, "reg" => 1, "base" => (float)$info->baseImpAir, "val" => (float)$info->valRetAir));
						} else {
							$codFinArr[$flag]["base"] += (float)$info->baseImpAir;
							$codFinArr[$flag]["val"] += (float)$info->valRetAir;
							$codFinArr[$flag]["reg"]++;
						}
					} else {
						$codFinArr = array(array("Cod" => (string)$info->codRetAir, "reg" => 1, "base" => (float)$info->baseImpAir, "val" => (float)$info->valRetAir));
					}
				}
			}
		}
	//print_r($rndFinArr);		
	$responce['success'] = true;
} catch (Exception $e) {
	$responce['message'] = '<b class="red">ERROR:</b> ' . $e->getMessage();
}
//exit;
?>
<html>

<head>
	<title><?Php echo $Ses_Sys_Nom; ?></title>
	<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<style type="text/css">
	.totales {
		font-size: 10px;
		font-weight: normal;
		font: 8pt verdana;
	}

	.titEmp {
		font-size: 14px;
		font-weight: normal;
		font: 12pt verdana;
	}

	.txtValor {
		font-size: 10px;
		font-weight: normal;
		font: 9pt Courier;
	}
</style>

<body class="Cuerpo">
	<?php

	/* Consulta de la cabecera del reporte */
	$row_institucion = $obBD_con1->getRowConsulta(22, $Ses_Suc_Cod, $obBD_conexion);
	/* Consulta la provicia y pais de la sucursal */
	$row_provincia = $obBD_con1->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD_conexion);
	?>

	<table width="100%" height="907" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td height="58" valign="top">

				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr align="center">
						<td width="10%" rowspan="5" valign="top"><img src="../../mascaras/model2/imagenes/32x32/sri.png" width="100" height="70" /></td>
						<td width="80%" height="24" class="titEmp"><strong><?Php echo $row_institucion['Emp_Nom']; ?></strong></td>
						<td width="10%" rowspan="5" valign="top" class="TITULO_REPORTE_2"><img src="<?php echo $row_institucion['Emp_Log'] ?>" width="115" height="83" /></td>
					</tr>
					<tr align="center">
						<td valign="top" class="Texto_Reporte">
							<div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp; <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div>
						</td>
					</tr>
					<tr align="center">
						<td valign="top" class="Texto_Reporte">
							<div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div>
						</td>
					</tr>
					<tr align="center">
						<td valign="top" class="Texto_Reporte">
							<div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div>
						</td>
					</tr>
					<tr align="center">
						<td align="center" valign="top" class="Texto_Reporte">
							<div align="center"><?Php
												if (count($row_provincia) > 0) {
													$provincia = " - " . $row_provincia['Pro_Nom'] . ' - ' . $row_provincia['Pas_Nom'];
												} else {
													$provincia = "";
												}
												echo $row_institucion['Ciu_Des'] . $provincia; ?></div>
						</td>
					</tr>
					<tr align="center">
						<td colspan="3" valign="top">
							<hr />
						</td>
					</tr>
					<tr align="center">
						<td colspan="3" valign="top" class="TITULO_REPORTE"><?php echo $Titulo; ?></td>
					</tr>
					<tr align="center">
						<td colspan="3" valign="top" class="TITULO_REPORTE"><?php echo $Subtitulo; ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr valign="top">
			<td valign="top">
				<br>
				<table width="100%" height="501" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td height="29">
							<div align="center">
								<label class="TITULO_REPORTE"><?Php echo isset($nivel[$i]) ? $nivel[$i] : ''; ?></label>
								<?php
								$rsComprasNum = $obBD_con1->getArrayConsulta(865, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
								$totalCompras = count($rsComprasNum);
								if ($totalCompras > 0 &&  $aCom == 1) {
								?>
									<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
										<thead>
											<tr class="Texto_Listados">
												<th colspan="7" style="color: #FFF;" bgcolor="#025ECC">
													<div align="center">COMPRAS</div>
												</th>
											</tr>
											<tr class="Texto_Listados">
												<th width="6%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
												<th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
												<th width="12%" align="center" bgcolor="#99CCCC">No. Registros</th>
												<th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa 0%</th>
												<th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa <?php echo $row_rs_PorIva['Iva_Por'] . "%"; ?></th>
												<th width="12%" align="center" bgcolor="#99CCCC">BI No Objeto de IVA</th>
												<th width="12%" align="center" bgcolor="#99CCCC">Valor IVA</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$filas = 0;
											$sumNoGraIva = 0;
											$sumSub0 = 0;
											$sumSub12 = 0;
											$sumIva = 0;
											foreach ($comArr as $row) {
												$filas = 0;
												$filas += $row['reg'];
												if ($row['Cod'] != '04') {
													$sumNOIva += $row['NoGraIva'];
													$sumSub0 += $row['Sub0'];
													$sumSub12 += $row['Sub12'];
													$sumIva += $row['iva'];
												} else {
													$sumSub0 -= $row['Sub0'];
													$sumSub12 -= $row['Sub12'];
													$sumNOIva = 0;
													$sumIva -= $row['iva'];
												}
											?>
												<tr class="txtValor">
													<td align="center"><?Php echo $row['Cod']; ?></td>
													<td align="center" style="max-width: 180px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><?Php echo $row['nom']; ?></td>
													<td align="center"><?Php echo $filas; ?></td>
													<td align="right"><?Php echo number_format($row['Sub0'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['Sub12'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['NoGraIva'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['iva'], 2); ?></td>
												</tr>
											<?php } ?>
											<tr class="txtValor">
												<td colspan="3" align="right" class=""><strong>Total:</strong></td>
												<td align="right" class=""><strong><?php echo number_format($sumSub0, 2); ?></strong></td>
												<td align="right" class=""><strong><?php echo number_format($sumSub12, 2); ?></strong></td>
												<td align="right" class=""><strong><?php echo number_format($sumNOIva, 2); ?></strong></td>
												<td align="right" class=""><strong><?php echo number_format($sumIva, 2); ?></strong></td>
											</tr>

										</tbody>
									</table>


								<?php } ?>
							</div>
						</td>
					</tr>
					<tr>
						<td height="14">&nbsp;</td>
					</tr>
					<tr>
						<td height="14">
							<?php
							$rsVentasNum = $obBD_con1->getArrayConsulta(883, $Ses_Emp_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
							$totalVentas = count($rsVentasNum);
							if ($totalVentas > 0 && $bVen == 1) {
							?>
								<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
									<thead>
										<tr class="Texto_Listados">
											<th colspan="7" style="color: #FFF;" bgcolor="#025ECC">
												<div align="center">VENTAS</div>
											</th>
										</tr>
										<tr class="Texto_Listados">
											<th width="6%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
											<th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
											<th width="12%" align="center" bgcolor="#99CCCC">No. Registros</th>
											<th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa 0%</th>
											<th width="12%" align="center" bgcolor="#99CCCC">BI Tarifa Diferente 0%</th>
											<th width="12%" align="center" bgcolor="#99CCCC">BI No Objeto de IVA</th>
											<th width="12%" align="center" bgcolor="#99CCCC">Valor IVA</th>
										</tr>
									</thead>
									<tbody>
										<?php



										$filas = 0;
										$sumSub0 = 0;
										$sumSub12 = 0;
										$sumNOIva = 0;
										$sumIva = 0;

										foreach ($vntArr as $row) {
											if ($row['Cod'] == '41') continue;
											$filas = 0;
											//if ($row['Cod']=='18'){
											$filas += $row['reg'];
											if ($row['Cod'] != '04') {
												$sumSub0 += $row['Sub0'];
												$sumSub12 += $row['Sub12'];
												$sumNOIva += $row['NoGraIva'];
												$sumIva += $row['iva'];
											} else {
												$sumSub0 -= $row['Sub0'];
												$sumSub12 -= $row['Sub12'];
												$sumNOIva = 0;
												$sumIva -= $row['iva'];
											}
											//}
										?>
											<tr class="txtValor">
												<td align="center"><?Php echo $row['Cod']; ?></td>
												<td align="center" style="max-width: 180px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">&nbsp;<?Php echo $row['nom']; ?></td>
												<td align="center"><?Php echo $filas; ?></td>
												<td align="right"><?Php echo number_format($row['Sub0'], 2); ?></td>
												<td align="right"><?Php echo number_format($row['Sub12'], 2); ?></td>
												<td align="right"><?Php echo number_format($row['NoGraIva'], 2); ?></td>
												<td align="right"><?Php echo number_format($row['iva'], 2); ?></td>
											</tr>
										<?php }
										if ($filas == '0') { ?>
											<tr class="totales">
												<td colspan="6" align="right" class="">&nbsp;</td>
											</tr>
										<?php } ?>
										<tr class="txtValor">
											<td colspan="3" align="right"><strong>Total:</strong></td>
											<td align="right" class=""><strong><?php echo number_format($sumSub0, 2); ?></strong></td>
											<td align="right" class=""><strong><?php echo number_format($sumSub12, 2); ?></strong></td>
											<td align="right" class=""><strong><?php echo number_format($sumNOIva, 2); ?></strong></td>
											<td align="right" class=""><strong><?php echo number_format($sumIva, 2); ?></strong></td>
										</tr>
										<?php /*VENTAS DE TIPO REEMBOLSOS*/
										foreach ($vntArr as $row) {
											if ($row['Cod'] == '41') {
												$filas = 0;
												$sumSub0 = 0;
												$sumSub12 = 0;
												$sumNOIva = 0;
												$sumIva = 0;

												$filas += $row['reg'];
												if ($row['Cod'] != '04') {
													$sumSub0 += $row['Sub0'];

													$sumSub12 += $row['Sub12'];
													$sumNOIva += $row['NoGraIva'];
													$sumIva += $row['iva'];
												} else {
													$sumSub0 -= $row['Sub0'];

													$sumSub12 -= $row['Sub12'];
													$sumNOIva = 0;
													$sumIva -= $row['iva'];
												}

										?>
												<tr class="txtValor">
													<td align="center"><?Php echo $row['Cod']; ?></td>
													<td align="center"><?Php echo $row['nom']; ?></td>
													<td align="center"><?Php echo $filas; ?></td>
													<td align="right"><?Php echo number_format($row['Sub0'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['Sub12'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['NoGraIva'], 2); ?></td>
													<td align="right"><?Php echo number_format($row['iva'], 2); ?></td>
												</tr>
										<?php }
										} ?>
									</tbody>
								</table>
							<?php } ?>
						</td>
					</tr>

					<?php if (isset($anuladoArr)) { ?>
						<tr>
							<td height="7">&nbsp;</td>
						</tr>
						<tr>
							<td height="7">
								<table width="100%" border="1" style="border-collapse:collapse;" cellpadding="2" cellspacing="0">
									<thead>
										<tr class="Texto_Listados">
											<th colspan="3" style="color: #FFF;" bgcolor="#025ECC">
												<div align="center">ANULADOS</div>
											</th>
										</tr>
										<tr class="Texto_Listados">
											<th width="8%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
											<th width="70%" align="center" bgcolor="#99CCCC">Transacci�n</th>
											<th width="22%" colspan="2" align="center" bgcolor="#99CCCC">No. Registros</th>
										</tr>
									</thead>
									<tbody>
										<?php

										$totalAnulados = 0;
										foreach ($anuladoArr as $row) {
											$totalAnulados += $row['reg'];
										?>
											<tr class="txtValor">
												<td align="center"><?Php echo $row['Cod']; ?></td>
												<td align="center"><?Php echo $row['nom']; ?></span></td>
												<td colspan="2" align="center"><?Php echo $row['reg']; ?></td>
											</tr>
										<?php } ?>
										<tr class="txtValor">
											<td colspan="2" align="right"><strong>Total:</strong></td>
											<td align="center"><strong><?Php echo $totalAnulados; ?></strong></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					<?php }

					if (isset($exporArr)) { ?>
						<tr>
							<td height="7">&nbsp;</td>
						</tr>
						<tr>
							<td height="8">
								<table width="100%" border="1" style="border-collapse:collapse;" cellpadding="2" cellspacing="0">
									<thead>
										<tr class="Texto_Listados">
											<th colspan="4" style="color: #FFF;" bgcolor="#025ECC">
												<div align="center">EXPORTACIONES (VENTAS)</div>
											</th>
										</tr>
										<tr class="Texto_Listados">
											<th width="6%" align="center" bgcolor="#99CCCC">Cod.</th>
											<th width="34%" align="center" bgcolor="#99CCCC">Transacci&oacute;n</th>
											<th width="36%" align="center" bgcolor="#99CCCC">No. Registros</th>
											<th width="24%" align="center" bgcolor="#99CCCC">Valor FOB</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$totalExp = 0;
										foreach ($exporArr as $row) {
											$totalExp += $row['val'];
										?>
											<tr class="txtValor">
												<td align="center"><?Php echo $row['Cod']; ?></td>
												<td align="center" style="white-space: nowrap; overflow: hidden;"><span style="white-space: nowrap; overflow: hidden;"><?Php echo $row['nom']; ?></span></td>
												<td align="center" style="white-space: nowrap; overflow: hidden;"><?php echo $row['reg']; ?></td>
												<td align="right"><?Php echo number_format($row['val'], 2); ?></td>
											</tr>
										<?php } ?>
										<tr class="txtValor">
											<td colspan="3" align="right" class=""><strong>Total:</strong></td>
											<td align="right" class=""><strong><?Php echo number_format($totalExp, 2); ?></strong></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td height="15">&nbsp;</td>
					</tr>
					<tr>
						<td height="29" class="Texto_Listados"><strong>RESUMEN DE RETENCIONES </strong></td>
					</tr>
					<tr>
						<td height="30">
							<?php

							?>
							<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
								<thead>
									<tr class="Texto_Listados">
										<th colspan="5" style="color: #FFF;" bgcolor="#025ECC">
											<div align="center"><strong>RETENCION EN LA FUENTE DE IMPUESTO A LA RENTA</strong></div>
										</th>
									</tr>
									<tr class="Texto_Listados">
										<th width="7%" align="center" bgcolor="#99CCCC">C&oacute;d.</th>
										<th width="57%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
										<th width="12%" align="center" bgcolor="#99CCCC">No. Registros</th>
										<th width="12%" align="center" bgcolor="#99CCCC">Base Imponible</th>
										<th width="12%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$sumBase = 0;
									$sumRta = 0;
									foreach ($retCmpArr as $row) {
										$sumBase += $row['base'];
										$sumRta += $row['val'];
									?>
										<tr class="txtValor">
											<td align="center"><?Php echo $row['Cod']; ?></td>
											<td align="center" style="max-width: 180px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><?Php echo codigoRenta($row['Cod']); ?></td>
											<td align="center"><?Php echo $row['reg']; ?></td>
											<td align="right"><?Php echo number_format($row['base'], 2); ?></td>
											<td align="right"><?Php echo number_format($row['val'], 2); ?></td>
										</tr>
									<?php } ?>
									<tr class="txtValor">
										<td colspan="3" align="right" class=""><strong>Total:</strong></td>
										<td align="right" class=""><strong><?Php echo number_format($sumBase, 2); ?></strong></td>
										<td align="right" class=""><strong><?Php echo number_format($sumRta, 2); ?></strong></td>
									</tr>
								</tbody>
							</table>
							<?php ?>

						</td>
					</tr>
					<tr>
						<td height="19">&nbsp;</td>
					</tr>
					<tr>
						<td height="65">
							<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
								<thead>
									<tr class="Texto_Listados">
										<th colspan="3" style="color: #FFF;" bgcolor="#025ECC">
											<div align="center">RETENCION EN LA FUENTE DE IVA</div>
										</th>
									</tr>
									<tr class="Texto_Listados">
										<th width="17%" align="center" bgcolor="#99CCCC">Operaci&oacute;n</th>
										<th width="59%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
										<th width="24%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$filas = 0;
									$suma1 = 0;
									foreach ($retIvaComArr as $row) {
										$suma1 += $row['val'];
									?>
										<tr class="txtValor">
											<td align="center"><?Php echo "COMPRAS" ?></td>
											<td align="center" style="white-space: nowrap; overflow: hidden;"><?Php echo $row['nom']; ?></td>
											<td align="right"><?Php echo number_format($row['val'], 2); ?></td>
										</tr>
									<?php } ?>
									<tr class="txtValor">
										<td colspan="2" align="right" class=""><strong>Total:</strong></td>
										<td align="right" class=""><strong><?Php echo number_format($suma1, 2); ?></strong></td>
									</tr>

								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td height="19">&nbsp;</td>
					</tr>
					<tr>
						<td height="59">
							<?php

							?>
							<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
								<thead>
									<tr class="Texto_Listados">
										<th colspan="3" style="color: #FFF;" bgcolor="#025ECC">
											<div align="center">RESUMEN DE RETENCIONES QUE LE EFECTUARON EN EL PERIODO</div>
										</th>
									</tr>
									<tr class="Texto_Listados">
										<th width="17%" align="center" bgcolor="#99CCCC">Operaci&oacute;n</th>
										<th width="59%" align="center" bgcolor="#99CCCC">Concepto de Retenci&oacute;n</th>
										<th width="24%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
									</tr>
								</thead>
								<tbody>
									<tr class="txtValor">
										<td align="center">VENTA</td>
										<td align="center">Valor de IVA que le han retenido</td>
										<td align="right"><?Php echo number_format($retVtaArr[0]['val'], 2); ?></td>
									</tr>
									<tr class="txtValor">
										<td align="center">VENTA</td>
										<td align="center">Valor de Renta que le han retenido</td>
										<td align="right"><?Php echo number_format($retVtaArr[1]['val'], 2); ?></td>
									</tr>
									<tr class="txtValor">
										<td colspan="2" align="right" class=""><strong>Total:</strong></td>
										<td align="right" class=""><strong><?Php echo number_format($retVtaArr[0]['val'] + $retVtaArr[1]['val'], 2); ?></strong></td>
									</tr>
								</tbody>
							</table>
							<?php  ?>
						</td>
					</tr>

					<?php if (isset($codFinArr)) { ?>
						<tr>
							<td>
								<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
									<thead>
										<tr class="Texto_Listados">
											<th colspan="5" style="color: #FFF;" bgcolor="#025ECC">
												<div align="center">RENDIMIENTOS FINANCIEROS</div>
											</th>
										</tr>
										<tr class="Texto_Listados">
											<th width="7%" align="center" bgcolor="#99CCCC">Cod.</th>
											<th width="53%" align="center" bgcolor="#99CCCC">Concepto de Rendimiento que gener&oacute; la Retenci&oacute;n</th>
											<th width="16%" align="center" bgcolor="#99CCCC">No. Registros</th>
											<th width="12%" align="center" bgcolor="#99CCCC">Base Imponible</th>
											<th width="12%" align="center" bgcolor="#99CCCC">Valor Retenido</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($codFinArr as $datos) {
											$TotReg += $datos['reg'];
											$Totbase += $datos['base'];
											$Totmon += $datos['val'];
										?>
											<tr class="txtValor">
												<td align="center"><?php echo $datos['Cod']; ?></td>
												<td align="center"><?php echo codigoRenta($datos['Cod']); ?></td>
												<td align="center"><?php echo $datos['reg']; ?></td>
												<td align="right"><?php echo number_format($datos['base'], 2); ?></td>
												<td align="right"><?php echo number_format($datos['val'], 2); ?></td>
											</tr>
										<?php } ?>
										<tr class="txtValor">
											<td colspan="2" align="right" class=""><strong>Total:</strong></td>
											<td align="center"><strong><?php echo $TotReg; ?></strong></td>
											<td align="right"><strong><?php echo number_format($Totbase, 2); ?></strong></td>
											<td align="right"><strong><?php echo number_format($Totmon, 2); ?></strong></td>
										</tr>

									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td height="15">&nbsp;</td>
						</tr>
						<tr>
							<td>
								<table width="100%" border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">
									<thead>
										<tr class="Texto_Listados">
											<th width="53%" align="center" bgcolor="#99CCCC">TRANSACCIONES EXENTAS</th>
											<th width="12%" align="center" bgcolor="#99CCCC">Total</th>
											<th width="12%" align="center" bgcolor="#99CCCC">Rendimiento Generado</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($rndFinArr as $datos) {
											$Tot1 += $datos['dep'];
											$Tot2 += $datos['gen'];
										?>
											<tr class="txtValor">
												<td align="center"><?php echo $datos['nom']; ?></td>
												<td align="right"><?php echo number_format($datos['dep'], 2); ?></td>
												<td align="right"><?php echo number_format($datos['gen'], 2); ?></td>
											</tr>
										<?php } ?>
										<tr class="totales">
											<td align="right" class=""><strong>Total:</strong></td>
											<td align="right"><strong><?php echo number_format($Tot1, 2); ?></strong></td>
											<td align="right"><strong><?php echo number_format($Tot2, 2); ?></strong></td>
										</tr>

									</tbody>
								</table>
							</td>
						</tr>
					<?php } ?>
					<tr class="txtValor">
						<td valign="top"><?php echo "<strong>Fecha de Generaci�n:</strong>&nbsp;&nbsp;" . date("d/m/Y H:i:s"); ?></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?php if (!empty($_GET['imprimir']) || !empty($_REQUEST['imprimir'])) { ?>
<script type="text/javascript">
(function() {
	function mostrarImpresion() {
		try { window.print(); } catch (e) {}
	}
	if (document.readyState === 'complete') {
		mostrarImpresion();
	} else {
		window.addEventListener('load', mostrarImpresion);
	}
})();
</script>
<?php } ?>
</body>

</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>