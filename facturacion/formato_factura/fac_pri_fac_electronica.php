<?php

/**
 * @abstract Reporte de ventas para la impresiï¿½n en factura o nota de venta
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaciï¿½n  2012-05-23
 * @author Lewis Chimarro
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_fac_ven.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;

if (isset($Vet_Cod)) {

	$rs_cliente = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_cliente = $obBD_con1->registros();
	$total_rs_cliente = $obBD_con1->numregistros();
	$cliente = $row_rs_cliente['Vet_Cod'];
	$observacion = $row_rs_cliente['Vet_Obs'];
	$estudiante = $row_rs_cliente['Prs_Ape'] . ' ' . $row_rs_cliente['Prs_Nom'];
	$rs_representante = $obBD_con1->consulta(sentencias_tes(33, $obBD_con1->parametros($row_rs_cliente['Cli_Cod'])), $obBD_conexion->conexion);
	$row_rs_representante = $obBD_con1->registros();

	$rs_pagos = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_pagos = $obBD_con1->registros();
	$total_rs_pagos = $obBD_con1->numregistros();

	$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);
}
?>
<html>

<head>
	<title><?Php echo $Ses_Sys_Nom; ?></title>
	<meta http-equiv="Content-Type" content="text/html;">
	<meta charset="UTF-8">
	<style type="text/css">
		.style2 { color: #000099 }
		.Estilo1 { font-size: 12px }
		.textos { position: absolute; font-size: 10px; font-weight: bold; font: 10pt Arial, Helvetica, sans-serif; }
		.flota { position: absolute; font-size: 10px; font-weight: normal; font: 9pt Arial, Helvetica, sans-serif; white-space: pre-wrap; text-align: justify; }
		.detalle { position: absolute; font-size: 10px; font-weight: normal; font: 9pt Verdana, Geneva, sans-serif; }

		.ajustar {
			word-wrap: break-word;
		}

		table,
		th,
		td {
			font-size: 10px;
			font-weight: normal;
			font: 9pt Arial, Helvetica, sans-serif;
			padding-left: 0px;
			margin-left: 0px;
		}

		tr.border_bottom td {
			border-bottom: 1px solid black;
		}
	</style>
</head>

<body>
	<?Php
	list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);
	$top = 50;
	$topIn = 15;

	?>
	<span style="top:5px;left:150px;margin-left:80px;" class="textos"><img style="width: 90px; height: 100%;" src="<? echo $row_institucion['Emp_Log']; ?>"></span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:5px;" class="flota"><? echo $row_institucion['Emp_Nom']; ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">R.U.C: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? echo $row_institucion['Emp_Ruc']; ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Correo: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? echo $row_institucion['Suc_Cor']; ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Telef: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? echo $row_institucion['Suc_Te1']; ?></span>

	<?php
	$altoDireccion = count(explode("\n", $row_institucion['Suc_Dir'])) * 4; // Ajusta 12 segÃºn el tamaÃ±o de fuente y espaciado

	// Incrementar top antes de la direcciÃ³n
	?>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:5px;" class="textos">DirecciÃ³n: </span>
	<span style="top:<?php echo $top; ?>px;left:5px; white-space: pre-wrap; font-size: small; text-align: justify;" class="flota"><?php echo utf8(wrapText($row_institucion['Suc_Dir'], 40)); ?>
	</span>

	<?php
	$top = $top + $topIn * $altoDireccion;
	if ($row_institucion['Emp_Cnt'] == 'S') {
	?>
		<span style="top:<? echo $top;
							$top = $top + $topIn; ?>px;left:5px;" class="flota">OBLIGADO A LLEVAR CONTABILIDAD</span>
	<?Php
	}
	?>

	<?Php
	if ($row_institucion['Cof_Rim'] == 'S') {
	?>
		<span style="top:<? echo $top;
							$top = $top + $topIn; ?>px;left:5px;" class="flota">Contribuyente Regimen Rimpe</span>
	<?Php
	}
	?>

	<?Php
	if ($row_institucion['Cof_Age'] == 'S') {
	?>
		<span style="top:<? echo $top;
							$top = $top + $topIn; ?>px;left:5px;" class="flota">Agente de Retencion No. Resolucion 1</span>
	<?Php
	}
	?>

	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:0px;" class="flota"> - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Cliente: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:60px;" class="flota"><? if ($row_rs_representante['Cli_Fac'] != "") {
																					echo $row_rs_representante['Cli_Fac'];
																				} else {
																					echo utf8($row_rs_cliente['Prs_Ape']) . ' ' . utf8($row_rs_cliente['Prs_Nom']);
																				} ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">DirecciÃ³n: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? if ($row_rs_representante['Cli_Dir'] != "") {
																					echo substr($row_rs_representante['Cli_Dir'], 0, 31);
																				} else {
																					echo utf8(substr($row_rs_cliente['Prs_Dir'], 0, 31));
																				} ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">CI/R.U.C: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? if ($row_rs_representante['Cli_Fac'] != "") {
																					echo $row_rs_representante['Cli_Ruf'];
																				} else {
																					echo $row_rs_cliente['Prs_Ced'];
																				} ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Ciudad: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:60px;" class="flota"><? echo $row_rs_cliente['Ciu_Des']; ?></span>

	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:0px;" class="flota"> - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:5px;" class="textos">COMPROBANTE ELECTRONICO DE VENTA </span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Nro: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px;" class="flota"><? echo $row_rs_cliente['Fac_Num']; ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Fecha: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70PX;" class="flota"><? echo $dia . '/' . $mes . '/' . $anio; ?></span>
	<span style="top:<? echo $top; ?>px;left:5px;" class="textos">Clave: </span>
	<span style="top:<? echo $top;
						$top = $top + $topIn; ?>px;left:70px; width: 250px" class="flota ajustar"><? echo $row_rs_cliente['Vet_Sri']; ?> </span>

	<span style="top:<? echo $top + 10; ?>px;left:0px;" class="flota"> - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -</span>

	<table style="width: 300px; margin:0px; margin-top:<? echo $top; ?>px;">
		<tr>
			<th>Can. </th>
			<th>Descripcion</th>
			<th>P/Unit</th>
			<th>Total</th>
		</tr>
		<?

		$cant_decimales = 2;
		if ($Ses_Emp_Cod == 534  || $Ses_Emp_Cod == 531 || $Ses_Emp_Cod == 44  || $Ses_Emp_Cod == 340 ) {
			$cant_decimales = 4;
		}

		do { ?>

			<tr class="border_bottom">
				<td><? echo formato_numero($row_rs_cliente['Vet_Can'], 1, 1); ?></td>
				<td><? echo  utf8($row_rs_cliente['Ite_Lar']) . ' ' . utf8($row_rs_cliente['Pro_Obs']); ?></td>
				<td style="text-align: left;"><? echo number_format($row_rs_cliente['Vet_Pru'], $cant_decimales); ?></td>
				<td style="text-align: right;"><? echo number_format($row_rs_cliente['Vet_Imp'], $cant_decimales); ?></td>
			</tr>
		<?
		} while ($row_rs_cliente = $obBD_con1->fetch_assoc($rs_cliente));
		$resultados = explode('*', $obBD_con1->calculos($Vet_Cod, $obBD_conexion));

		?>
		<tr>
			<td style="text-align: right;" colspan="3">Subtotal:</td>
			<td><?Php echo    formato_numero($resultados[0], $cant_decimales, 1); ?></td>
		</tr>
		<tr>
			<td style="text-align: right;" colspan="3">Descuento</td>
			<td><?Php echo formato_numero($resultados[4],$cant_decimales, 1); ?></td>
		</tr>
		<tr>
			<td style="text-align: right;" colspan="3">Subtotal con descuento</td>
			<td><?Php echo formato_numero(($resultados[0] - $resultados[4]), $cant_decimales, 1); ?></td>
		</tr>
		<tr>
			<td style="text-align: right;" colspan="3">Iva 0%:</td>
			<td><?Php echo formato_numero($resultados[1] + 0, $cant_decimales, 1); ?></td>
		</tr>
		<tr>
			<td style="text-align: right;" colspan="3">Iva 15%:</td>
			<td><?Php echo formato_numero($resultados[3], $cant_decimales, 1); ?></td>
		</tr>
		<tr>
			<td style="text-align: right;" colspan="3">Total:</td>
			<td><?php echo number_format($resultados[3] + ($resultados[0] - $resultados[4]), 2); ?></td>
		</tr>
		<?php if (!empty($resultados[7]) && floatval($resultados[7]) != 0): ?>
			<tr>
				<td style="text-align: right;" colspan="3">Valor Efectivo:</td>
				<td><?php echo number_format($resultados[7], 2); ?></td>
			</tr>
			<?php endif; ?>
			<?php if (!empty($resultados[8]) && floatval($resultados[8]) != 0): ?>
			<tr>
				<td style="text-align: right;" colspan="3">Cambio:</td>
				<td><?php echo number_format($resultados[8], 2); ?></td>
			</tr>
			<?php endif; ?>
		<!--tr>
  	<td style="text-align: right;" colspan="3">Total:</td>
  	<td><?php echo number_format($resultados[5], 2); ?></td>
  </tr-->

	</table>
	<span style="left:0px;" class="flota"> - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - </span>
	<table style="margin-top:10px">
		<tr>
			<td style="text-align: left;"> <strong>ObservaciÃ³n:</strong> </td>
		</tr>
		<tr>
			<td>
				<p style=" white-space: pre-wrap; font-size:small"><?php echo utf8(wrapText($observacion, 50)); ?></p>
			</td>
		</tr>
	</table>

</body>

</html>
<?Php

function wrapText($text, $length)
{
	return wordwrap($text, $length, "\n", true);
}

@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>