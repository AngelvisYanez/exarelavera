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
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente[0]['Caj_Fec']);?>
<table width="621" height="100%" border="0" align="center">
          <td width="615" height="242" colspan="4" align="left" valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
        <tr>
          <td colspan="4" align="center" class="LetraPlan">AGRONUEVO CIA. LTDA.</td>
        </tr>
        <tr>
          <td colspan="4" align="center" class="Texto_normal_10"><div align="center"> AV. ARENILLAS Y JOSE MENDOZA TELEF: 072995887 / 0985886358  HUAQUILLAS</div></td>
          </tr>
        <tr>
          <td align="left">&nbsp;</td>
          <td>&nbsp;</td>
          <td align="left">&nbsp;</td>
          <td align="left">&nbsp;</td>
        </tr>
        <tr>
          <td width="11%" align="left">No<strong>:</strong></td>
          <td width="55%"><input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?Php echo $cliente; ?>" >
		  <?php 
		  	$row_rs_punSri = $obBD_con1->getRowConsulta(1216,$row_rs_cliente[0]['Pun_Cod'],$obBD_conexion);			
			echo $row_rs_punSri['Suc_Sri']."-".$row_rs_punSri['Pun_Sri']."-".str_pad($row_rs_cliente[0]['Vet_Num'],9,'0',STR_PAD_LEFT)."&nbsp;&nbsp; SIN VALOR TRIBUTARIO"; 
		  ?>
          </td>
          <td align="left">FECHA:</td>
          <td align="left"><table width="47%" border="0" cellpadding="0" cellspacing="0" class="Texto_normal_10">
            <tr>
              <td width="101" align="left">&nbsp;<?php echo $dia."/".$mes."/".$anio;?></td>
            </tr>
          </table></td>
          </tr>
        <tr>
          <td align="left">CLIENTE<strong>:</strong></td>
          <td><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac'];  
			}
			else { echo $row_rs_cliente[0]['Prs_Ape'].' '.$row_rs_cliente[0]['Prs_Nom']; }
		    ?></td>
          <td width="9%" align="left">CIUDAD<strong>:</strong></td>
          <td width="25%"><?Php echo $row_institucion['Ciu_Des']; ?></td>
          </tr>
        <tr>
          <td align="left">R.U.C / C.I<strong>:</strong></td>
          <td><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente[0]['Prs_Ced']; 
			} ?></td>
          <td align="left">E-MAIL<strong>:</strong></td>
          <td align="left"><?php 
		  		if ($row_rs_cliente[0]['Prs_Cor']!="")
				{
					echo $row_rs_cliente[0]['Prs_Cor'];  
				}else{
					echo "ninguno";	
				}
			
		    ?></td>
          </tr>
        <tr>
          <td align="left">DIRECCI&Oacute;N:</td>
          <td colspan="3"><?php if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente[0]['Prs_Dir']; 
			} ?></td>
          </tr>
        <tr>
          <td colspan="4" align="left" valign="top">
            
            <?Php 
			 $tarifa_0 = 0;
			 $tarifa_12 = 0;	
			?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
              <tr>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
                <td align="center">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="4" align="center"><hr size=1></td>
              </tr>
              <tr class="Texto_normal_10">
                <td align="left"><strong>&nbsp;<!--Cant.--></strong>CANT.</td>
                <td align="left">PRODUCTO<strong>&nbsp;<!--Descripci&oacute;n--></strong></td>
                <td align="right">P. UNIT.&nbsp;<!--PVP--></td>
                <td align="right">IMPORTE<strong>&nbsp;<!--Total--></strong></td>
              </tr>
              <tr>
                <td colspan="4" align="left"><hr size=1></td>
              </tr> 
              <?php foreach($row_rs_cliente as $row){?>
              <tr>
                <td width="10" align="left"><?Php echo $row['Vet_Can']?></td>
                <td>&nbsp;&nbsp;<?Php echo $row['Ite_Lar'].' '.$row['Pro_Obs']?></td>
                <td width="60" align="right"><?Php echo number_format($row['Vet_Pru'], 2); ?>&nbsp;&nbsp;</td>
                <td width="40" align="right"><?Php echo number_format($row['Vet_Imp'], 2); ?></td>
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
              <tr>
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
				?>			  
            </table>          
          <!-- Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL -->          </td>
          </tr>
        
      </table>
<tr>
            <td colspan="4" valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
              <tr>
                <td width="89%" align="right">Tarifa 12%&nbsp;&nbsp;</td>
                <td width="11%" align="right"><?Php echo formato_numero($resultados[2]+0, 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right">Tarifa 0%&nbsp;&nbsp;</td>
                <td align="right"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right">Descuento<strong>&nbsp;&nbsp;</strong></td>
                <td align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right">Subtotal<strong>&nbsp;&nbsp;</strong></td>
                <td align="right"><?Php echo formato_numero($resultados[0], 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right">IVA<strong>&nbsp;&nbsp;</strong></td>
                <td align="right"><?Php echo formato_numero($resultados[3], 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right">TOTAL<strong>&nbsp;&nbsp;</strong></td>
                <td align="right"><?php echo number_format($resultados[5], 2); ?></td>
              </tr>
              <tr>
                <td colspan="2">Vendedor:
                <?Php   
						$rs_Vendedor = $obBD_con1->consulta(sentencias_tes(1217, $obBD_con1->parametros($vendedor)), $obBD_conexion->conexion);
						$rs_row_Vendedor = $obBD_con1->registros();
						
						$nom = explode(' ',$rs_row_Vendedor['Prs_Nom']);
						$ape = explode(' ',$rs_row_Vendedor['Prs_Ape']);
						echo $nom[0].' '.$ape[0]; 
				?></td>
              </tr>
              <tr>
                <td colspan="2" class="Texto_Listados"><strong>Nota:</strong> Estimado cliente su Factura Electr&oacute;nica ser&aacute; enviada a su E-mail</td>
              </tr>
            </table>                      
  <tr valign="top">
    <td valign="bottom">
</table>
  </tr>
</table>

</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>