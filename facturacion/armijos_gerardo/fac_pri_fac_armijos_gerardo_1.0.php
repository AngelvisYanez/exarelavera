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

if (isset($Vet_Cod))
{
	/*
	* Consulta datos de los clientes
	*/
	$rs_cliente = $obBD_con1->consulta(sentencias_tes(37, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_cliente = $obBD_con1->registros();
	$total_rs_cliente = $obBD_con1->numregistros();	
	$cliente = $row_rs_cliente['Vet_Cod'];	
	$observacion = $row_rs_cliente['Vet_Obs'];	
	$estudiante = $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];		
	/*
	* Llamado del representate delcliente
	*/
	$rs_representante = $obBD_con1->consulta(sentencias_tes(33, $obBD_con1->parametros($row_rs_cliente['Cli_Cod'])),
									$obBD_conexion->conexion);
	$row_rs_representante = $obBD_con1->registros();
	/* 
	* Consulta la carrera del cliente 
	*/
	/*$rs_carrera = $obBD_con1->consulta(sentencias_tes(224, $obBD_con1->parametros($row_rs_cliente['Nge_Cod'])),
									$obBD_conexion->conexion);
	$row_rs_carrera = $obBD_con1->registros();
	$total_rs_carrera = $obBD_con1->numregistros();	*/		
	/*
	* Consulta de los tipos de pago 
	*/
	$rs_pagos = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_pagos = $obBD_con1->registros();
	$total_rs_pagos = $obBD_con1->numregistros();	
	/**
	* Consulta de la cabecera del reporte 
	*/
	$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);					
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 12px}
-->

.detalle{position: absolute;font-size: 10px;font-weight: normal;font: 10pt Verdana, Geneva, sans-serif;}
.rigth{text-align: right; width: 70px;}

.flota{position: absolute; font-size: 10px;font-weight: normal;font: 10pt Verdana, Geneva, sans-serif;}
.titulos{font-size: 10px;font: 10pt Verdana, Geneva, sans-serif;}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![/.-]!', $row_rs_cliente['Caj_Fec']);
$row=10;
?>



	<table style=" width: 100%;font-size: 12px; border: solid 1px; border-radius:15px; padding: 5px; font-family:Verdana, Geneva, sans-serif; font-size:12px;" >
				<tr>
					<td class='bold' align='left' style="font-size: 12px;">
						<strong>CLIENTE: </strong><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Fac'];}else{ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom'];}?>
						<td>
							<strong> No: </strong><? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Fac'];}else{ echo $row_rs_cliente['Fac_Num'];}?>
						</td>
					</td>
				</tr>
				<tr>
					<td class='bold' align='left' style="font-size: 12px;">
						<strong>DIRECCI&Oacute;N: </strong> <? if ($row_rs_representante['Cli_Dir'] != ""){echo substr($row_rs_representante['Cli_Dir'],0,31);}else{echo substr($row_rs_cliente['Prs_Dir'],0,31);}?>
					</td>
				</tr>
				<tr>
					<td class='bold' align='left' style="font-size: 12px;">
						<strong>C&Eacute;DULA: </strong> <? if ($row_rs_representante['Cli_Fac'] != ""){echo $row_rs_representante['Cli_Ruf'];}else{echo $row_rs_cliente['Prs_Ced'];}?>
					</td>
				</tr>
				<tr>
					<td class='bold' align='left' style="font-size: 12px;">
						<strong>CIUDAD: </strong> <? echo $row_institucion['Ciu_Des'];?>
					</td>
				</tr>
				<tr>
					<td class='bold' align='left' style="font-size: 12px;">
						<strong>FECHA: </strong> <? echo $dia.'/'.$mes.'/'.$anio; ?>
					</td>
				</tr>
			</table>
			<br/>

<table id="datosTabla" style="table-layout: fixed; width: 100%; word-wrap: break-word; border-collapse: collapse; font-family:Verdana, Geneva, sans-serif; font-size:12px"
				 align='center' cellpadding="5" border="1" class="noBorder">
					<thead>
						<tr>
							<th style="width:15%;font-size: 11px;">CANTIDAD</th>
							<th style="width:55%;font-size: 11px;" >DESCRIPCI&Oacute;N</th>
							<th style="width:15%;font-size: 11px;">P.UNITARIO</th>
							<th style="width:15%;font-size: 11px;" align='right'>TOTAL</th>
						</tr>
					</thead>
					<tbody id="tablita" class='noBorder' align='center' style="border-bottom: none;">
						
						<?php do{?>
						<tr>
							<td align='left'><? echo formato_numero($row_rs_cliente['Vet_Can'],1,1);?></td>
							<td align='left'><? echo $row_rs_cliente['Ite_Lar'] ." " . $row_rs_cliente['Des_Adi'];?></td>
							<td align='right'><? echo number_format($row_rs_cliente['Vet_Pru'], 2);?></td>
							<td align='right'><? echo number_format($row_rs_cliente['Vet_Imp'], 2);?></td>
						</tr>
						<?php } while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
							$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
							?>

					</tbody>
					<tbody class='noBorder' style="border-collapse: collapse;">

						<tr>
							<td rowspan="5" colspan="2"><strong>OBSERVACI&Oacute;N: </strong><? echo $observacion;?></td>
							<td colspan="1" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>SUBTOTAL:</strong> 
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_subtotal" align='right' name="t_subtotal" class="form-control input-xs databind datatitle bold"><?Php echo formato_numero($resultados[0], 2, 1); ?></span>
							</td>

						</tr>
						<tr>
							
							<td colspan="1" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>TARIFA 0%:</strong> 
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_tarifaCI" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></span>
							</td>

						</tr>
						<tr>
							
							<td colspan="1" align="right" class="bold" style=" border-top: 1px solid; font-size: 12px;">
								<strong>
									<span class="iva_por"></span>% IVA:</strong> 
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_tarifaDI" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"><?Php echo formato_numero($resultados[3], 2, 1); ?></span>
							</td>

						</tr>
						<tr>
							
							<td colspan="1" align="right" class="bold" style=" border-top: 1px solid;font-size: 12px;">
								<strong>DESCUENTO:</strong> 
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_descuento" align='right' name="t_descuento" class="form-control input-xs databind datatitle bold"><?Php echo formato_numero($resultados[4], 2, 1); ?></span>
							</td>

						</tr>
						<tr>
							
							<td colspan="1" align="right" class="bold" style=" border-top: 1px solid;font-size: 12px;">
								<strong>TOTAL FACTURA:</strong> 
							</td>
							<td align='right' style=" border-top: 1px solid;">
								<span id="t_rubros" align='right' name="t_rubros" class="form-control input-xs databind datatitle bold"><?php echo number_format($resultados[5], 2); ?></span>
							</td>

						</tr>
					</tbody>
				</table>
			<br/>

			
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>