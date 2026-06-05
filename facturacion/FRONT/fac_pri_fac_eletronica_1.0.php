<?php 
/**
* @abstract Reporte de ventas para la impresión en factura o nota de venta
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-23
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
	$row_rs_cliente = $obBD_con1->getArrayConsulta(37,$Vet_Cod,$obBD_conexion);	
	$total_rs_cliente = count($row_rs_cliente);
	
	$cliente = $row_rs_cliente[0]['Vet_Cod'];	
	$observacion = $row_rs_cliente[0]['Vet_Obs'];
	$vendedor=$row_rs_cliente[0]['Vnd_Cod'];		
	$claveacceso=$row_rs_cliente[0]['Vet_Xml'];	
		
	$estudiante = $row_rs_cliente[0]['Prs_Ape'].' '.$row_rs_cliente[0]['Prs_Nom'];		
	/*
	* Llamado del representate delcliente
	*/
	$row_rs_representante = $obBD_con1->getRowConsulta(33,$row_rs_cliente[0]['Cli_Cod'],$obBD_conexion);
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
	/*$rs_pagos = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_pagos = $obBD_con1->registros();
	$total_rs_pagos = $obBD_con1->numregistros();	*/
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
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
body {
	margin-left: 0px;
}
</style>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 12px}
-->
.tituloFact{font: 14pt Tahoma, Geneva, sans-serif;}
.titulotabla{
	font: 9pt Tahoma, Geneva, sans-serif;
	color: #FFF;
	
}
.tituloFact2{font: 8pt Tahoma, Geneva, sans-serif;}
.etiquetaFact{
	font: 7pt Tahoma, Geneva, sans-serif;	
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente[0]['Caj_Fec']);?>
<table width="100%" border="0" align="center">
  <tr>
  	<td  align="left" valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
  	  <tr>
  	    <td colspan="6" align="center" class="LetraPlan"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  	      <tr>
  	        <td width="21%" align="left"><img src="<? echo $Ses_Emp_Log;?>" width="90" height="80"></td>
  	        <td width="79%"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  	          <tr>
  	            <td align="left" class="tituloFact"><strong><? echo $Ses_Emp_Cor;?></strong></td>
	            </tr>
  	          <tr>
  	            <td align="left" class="tituloFact2"><? echo strtoupper($Ses_Emp_Nom);?></td>
	            </tr>
  	          <tr>
  	            <td align="left" class="tituloFact2"><? echo $row_institucion['Suc_Dir'];?>. Telf: <? echo $row_institucion['Suc_Te1'].' - '.$row_institucion['Suc_Te2'];?></td>
	            </tr>
	          </table></td>
	        </tr>
	      </table></td>
	    </tr>
  	  <tr>
  	    <td colspan="6" align="left"><hr></td>
	    </tr>
  	  <tr>
  	    <td width="11%" align="left" class="etiquetaFact"><strong>No. FACTURA:</strong></td>
  	    <td width="32%" class="etiquetaFact">&nbsp;<strong>
  	      <? 
		  	$row_rs_punSri = $obBD_con1->getRowConsulta(1216,$row_rs_cliente[0]['Pun_Cod'],$obBD_conexion);			
			echo $row_rs_punSri['Suc_Sri']."-".$row_rs_punSri['Pun_Sri']."-".str_pad($row_rs_cliente[0]['Vet_Num'],9,'0',STR_PAD_LEFT); 
		  ?>
	      </strong></td>
  	    <td width="6%" align="left" class="etiquetaFact"><strong>EMISI&Oacute;N:</strong></td>
  	    <td width="14%" class="etiquetaFact">&nbsp;<? echo $dia."/".$mes."/".$anio;?></td>
  	    <td align="left" class="etiquetaFact"><strong>CIUDAD:</strong></td>
  	    <td align="left" class="etiquetaFact">&nbsp;<?Php echo $row_institucion['Ciu_Des']; ?></td>
	    </tr>
  	  <tr>
  	    <td align="left" class="etiquetaFact"><strong>CLIENTE:</strong></td>
  	    <td colspan="3" class="etiquetaFact">&nbsp;
  	      <? if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac'];  
			}
			else { echo $row_rs_cliente[0]['Prs_Ape'].' '.$row_rs_cliente[0]['Prs_Nom']; }
		    ?></td>
  	    <td width="10%" align="left" class="etiquetaFact"><strong>R.U.C / C.I:</strong></td>
  	    <td width="27%" class="etiquetaFact">&nbsp;
  	      <? if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente[0]['Prs_Ced']; 
			} ?></td>
	    </tr>
  	  <tr>
  	    <td align="left" class="etiquetaFact"><strong>DIRECCI&Oacute;N:</strong></td>
  	    <td colspan="5" class="etiquetaFact">&nbsp;
  	      <? if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente[0]['Prs_Dir']; 
			} ?></td>
	    </tr>
  	  <tr>
  	    <td colspan="6" align="left"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  	      <tr>
  	        <td width="14%" class="etiquetaFact"><strong>CLAVE DE ACCESO:</strong></td>
  	        <td width="86%" class="etiquetaFact">&nbsp;<? echo $claveacceso;?></td>
	        </tr>
	      </table></td>
	    </tr>
  	  <tr>
  	    <td colspan="6" align="left" valign="top"><?Php 
			 $tarifa_0 = 0;
			 $tarifa_12 = 0;	
			?>
  	      <br>
  	      <table width="100%" border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse" bordercolor="#AE0D09">
  	        <tr class="titulotabla">
  	          <td height="19" align="left" bgcolor="#AE0D09"><strong>&nbsp;
  	            <!--Cant.-->
	            </strong>CANT.</td>
  	          <td width="396" align="left" bgcolor="#AE0D09">DESCRIPCI&Oacute;N<strong>&nbsp;
  	            <!--Descripci&oacute;n-->
	            </strong></td>
  	          <td align="right" bgcolor="#AE0D09">P. UNIT.&nbsp;
  	            <!--PVP--></td>
  	          <td align="right" bgcolor="#AE0D09">IMPORTE<strong>&nbsp;
  	            <!--Total-->
	            </strong></td>
	          </tr>
  	        <tr>
  	          <td height="150" colspan="4" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  	            <tr>
  	              <td><table width="100%">
  	                <? foreach($row_rs_cliente as $row){?>
  	                <tr class="etiquetaFact">
  	                  <td width="83" align="left"><?Php echo $row['Vet_Can']?></td>
  	                  <td width="618">&nbsp;&nbsp;<?Php echo $row['Ite_Lar'].' '.$row['Pro_Obs']?></td>
  	                  <td width="245" align="right"><?Php echo number_format($row['Vet_Pru'], 2); ?>&nbsp;&nbsp;</td>
  	                  <td width="181" align="right"><?Php echo number_format($row['Vet_Imp'], 2); ?></td>
	                  </tr>
  	                <?Php 
				  /* 
				  * % de Descuento total 
				  */
				  $Vet_Des = $row['Vet_Des'];
				  /* 
				  * Consulta los rubro del intereses
				  */
				  $row_rs_interes = $obBD_con1->getArrayConsulta(74,$Vet_Cod.'*'.$row['Nge_Cod'].'*'.$v['Asi_Int'].'*'.$row['Pro_Cod'],$obBD_conexion);
				  $total_rs_interes = count($row_rs_interes);			
				  if ($total_rs_interes > 0)
					{ 
					  foreach($row_rs_interes as $datos){
				?>
  	                <tr class="etiquetaFact">
  	                  <td align="left"><?Php echo $datos['Vet_Can']?></td>
  	                  <td>&nbsp;&nbsp;<?Php echo $datos['Ite_Lar']?></td>
  	                  <td align="right"><?Php echo number_format($datos['Vet_Pru'], 2); ?>&nbsp;&nbsp;</td>
  	                  <td align="right"><?Php echo number_format($datos['Vet_Imp'], 2); ?></td>
	                  </tr>
  	                <?Php
						};//fin foreach($row_rs_interes as $datos)
					}//Fin del if ($total_rs_interes > 0)				
				};
				/* 
				* Suma del descuento 
				*/
				$des = $des_0 + $des_12;
				/* 
				* calculo del iva con descuento individual 
				*/
				$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
				/* 
				* Calculo del descuento total 
				*/
				if ($Vet_Des != 0)
				{
					$des = ($subtotal * $Vet_Des) / 100;
					$des_12 = ($tarifa_12 * $Vet_Des) / 100;
					$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
				}	
				/* 
				* Calculo del total 
				*/
				$total = ($subtotal - $des) + $iva;	
				/*  
				* Retorno los calculos de las facturas 
				*/
				$resultados = explode('*',$obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
	            $row_rs_formapago = $obBD_con1->getRowConsulta(1319,$Vet_Cod,$obBD_conexion);	
				?>
	                </table></td>
	              </tr>
  	            <tr>
  	              <td><table width="70%" border="0" cellspacing="0" cellpadding="0">
  	                <tr>
  	                  <td valign="top" class="etiquetaFact">&nbsp;</td>
  	                  <td valign="top" class="etiquetaFact">&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td width="11%" valign="top" class="etiquetaFact"><strong>&nbsp;<? if($row_rs_cliente[0]['Vet_Obs']!=''){?>OBSERVACI&Oacute;N:<? }?></strong></td>
  	                  <td width="89%" valign="top" class="etiquetaFact">&nbsp;<? if($row_rs_cliente[0]['Vet_Obs']!=''){ echo $row_rs_cliente[0]['Vet_Obs'];}?></td>
	                  </tr>
	                </table></td>
	              </tr>
  	            <tr>
  	              <td height="100" valign="bottom"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="etiquetaFact">
  	                <tr>
  	                  <td colspan="2" align="right">Sub-total Iva:&nbsp;</td>
  	                  <td width="12%" align="right"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?>&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td colspan="2" align="right">Sub-Total 0%:&nbsp;</td>
  	                  <td align="right"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?>&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td colspan="2" align="right">Descuento:&nbsp;</td>
  	                  <td align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?>&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td colspan="2" align="right">Subtotal:&nbsp;</td>
  	                  <td align="right"><?Php echo formato_numero($resultados[0], 2, 1); ?>&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td align="left">&nbsp;<strong>Forma de Pago:</strong> <? echo strtoupper($row_rs_formapago['Tpc_Des']);?></td>
  	                  <td align="right">Iva:&nbsp;</td>
  	                  <td align="right"><?Php echo formato_numero($resultados[3], 2, 1); ?>&nbsp;</td>
	                  </tr>
  	                <tr>
  	                  <td width="73%" align="left">&nbsp;<strong>Nota:</strong> Descargue su Factura Electr&oacute;nica en:  &nbsp;&nbsp;exa.ofsercont.com/facturacion/FRONT/electronica.php?Emp_Cod=<? echo $Ses_Emp_Cod;?>&Prs_Cod=<? echo $row_rs_cliente[0]['Prs_Cod'];?></td>
  	                  <td width="15%" align="right"><strong>TOTAL:&nbsp;</strong></td>
  	                  <td align="right"><strong><?php echo number_format($resultados[5], 2); ?></strong>&nbsp;</td>
	                  </tr>
	                </table></td>
	              </tr>
	            </table>
              
  	           
  	            </td>
	          </tr>
	        </table></td>
	    </tr>
    </table></td>
  </tr>
  <tr>

          <td height="5"  class="etiquetaFact" >&nbsp;</td>
  </tr>
  <tr>
    <td height="6"  class="etiquetaFact" >&nbsp;</td>
  </tr>
  <tr>
    <td height="13" align="center"  class="etiquetaFact" ><table width="100%" border="0" cellpadding="0" cellspacing="0" class="etiquetaFact">
      <tr>
        <td width="33%" align="center">____________________________________</td>
        <td width="33%">&nbsp;</td>
        <td width="33%" align="center">____________________________________</td>
      </tr>
      <tr>
        <td align="center">ENTREGUE CONFORME</td>
        <td>&nbsp;</td>
        <td align="center">RECIBI CONFORME</td>
      </tr>
      <tr>
        <td align="center"><?Php   
						$rs_Vendedor = $obBD_con1->consulta(sentencias_tes(1217, $obBD_con1->parametros($vendedor)), $obBD_conexion->conexion);
						$rs_row_Vendedor = $obBD_con1->registros();
						
						$nom = explode(' ',$rs_row_Vendedor['Prs_Nom']);
						$ape = explode(' ',$rs_row_Vendedor['Prs_Ape']);
						echo $nom[0].' '.$ape[0]; 
				?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
  </table>
  </tr>
</table>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>