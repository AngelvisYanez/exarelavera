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
.Letra_punto_venta_2 {				
	font-family: Verdana;
	font-size: 10px;	
}
.Letra_punto_venta_3 {				
	font-family: Verdana;
	font-size: 10px;	
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente['Caj_Fec']);?>
<table width="457"  border="0" align="left" cellpadding="0" cellspacing="0">
<tr>
    <td height="110" colspan="4" align="left" valign="top">&nbsp;</td>
<tr>
    <td height="404" align="left" valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_3">
      <tr>
        <td height="25" colspan="2" valign="middle" class="Letra_punto_venta_2">&nbsp;
          <?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac'];  
			}
			else { echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; }
		    ?></td>
        </tr>
      <tr>
        <td height="25" colspan="2" valign="middle" class="Letra_punto_venta_2">&nbsp;
          <?php if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Dir']; 
			} ?></td>
        </tr>
      <tr>
        <td width="40%" height="25" valign="bottom" class="Letra_punto_venta_2">&nbsp;
          <?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Ced']; 
			} ?></td>
        <td width="60%" rowspan="2" align="left" valign="middle"><table width="48%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
          <tr>
            <td width="35%" height="25" align="right" valign="bottom">&nbsp;<?php echo $dia;?></td>
            <td width="33%" align="right" valign="bottom">&nbsp;<?php echo $mes;?></td>
            <td width="32%" align="right" valign="bottom">&nbsp;<?php echo $anio;?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td height="25" valign="bottom" class="Letra_punto_venta_2">&nbsp; <?Php echo $row_institucion['Ciu_Des']; ?></td>
      </tr>
      <tr>
        <td height="30" colspan="3" align="left" valign="top"><?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	
	?></td>
      </tr>
      <tr>
        <td height="18" colspan="3" align="left" valign="top"><table width="70%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
          <?php do{?>
          <tr>
            <td width="40" height="25" align="left" valign="middle"><div align="left"><?Php echo $row_rs_cliente['Vet_Can']?></div></td>
            <td width="140" align="left" valign="middle"><div align="left">&nbsp;<?Php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs']?></div></td>
            <td width="70" align="right" valign="middle"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?>&nbsp;</td>
            <td width="70" align="right" valign="middle" ><div align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?>&nbsp; </div></td>
          </tr>
          <?Php 
				  /* 
				  * % de Descuento total 
				  */
				  $Vet_Des = $row_rs_cliente['Vet_Des'];
				  /* 
				  * Consulta los rubro del intereses
				  */
				  $rs_interes = $obBD_con1->consulta(sentencias_tes(74, $obBD_con1->parametros($Vet_Cod.'*'.
							$row_rs_cliente['Nge_Cod'].'*'.$row_rs_cliente['Asi_Int'].'*'.$row_rs_cliente['Pro_Cod'])), 
							$obBD_conexion->conexion);
				  $row_rs_interes = $obBD_con1->registros();
				  $total_rs_interes = $obBD_con1->numregistros();
			
					if ($total_rs_interes > 0)
					{ 
						do{
						?>
          <tr>
            <td height="25" align="left" valign="middle"><div align="left"><?Php echo $row_rs_interes['Vet_Can']?></div></td>
            <td align="left" valign="middle">&nbsp;<?Php echo $row_rs_interes['Ite_Lar']?></td>
            <td align="right" valign="middle"><?Php echo number_format($row_rs_interes['Vet_Pru'], 2); ?>&nbsp;&nbsp;</td>
            <td align="right" valign="middle"><?Php echo number_format($row_rs_interes['Vet_Imp'], 2); ?></td>
          </tr>
          <?Php
						}while($row_rs_interes = $obBD_con1->fetch_assoc($rs_interes));
					}//Fin del if ($total_rs_interes > 0)				
				}while ($row_rs_cliente = $obBD_con1->fetch_assoc ($rs_cliente));
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
        </table></td>
      </tr>
    </table></td>
  <tr>
             <!-- Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL -->
            <td height="110" colspan="4" valign="top"><table width="311" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <tr>
                <td align="right"><!--Subtotal&nbsp;-->
                  &nbsp;</td>
                <td height="20" align="right"><?Php echo formato_numero($resultados[0], 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right"><!--Descuento&nbsp;-->
                  &nbsp;</td>
                <td height="20" align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?></td>
              </tr>
              <!-- <tr>
                <td align="right">Tarifa 0%&nbsp;&nbsp;</td>
                <td align="right"><?Php //echo formato_numero($resultados[1]+0, 2, 1); ?></td>
              </tr>-->
              <tr>
                <td width="28%" align="right"><!--Tarifa 12%&nbsp;-->
                  &nbsp;</td>
                <td width="72%" height="20" align="right">0.00
                  <?Php //echo formato_numero($resultados[2]+0, 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right"><!--IVA&nbsp;-->
                  &nbsp;</td>
                <td height="20" align="right"><?Php echo formato_numero($resultados[3], 2, 1); ?></td>
              </tr>
              <tr>
                <td align="right"><!--TOTAL&nbsp;-->
                  &nbsp;</td>
                <td height="20" align="right"><strong><?php echo number_format($resultados[5], 2); ?></strong></td>
              </tr>
              <tr>
                <td colspan="2"><!--Usuario:-->
                  <?Php 
						$nom = explode(' ',$Ses_Prs_Ape);
						$ape = explode(' ',$Ses_Prs_Nom);
						//echo $nom[0].' '.$ape[0]; 
				?></td>
              </tr>
            </table>
  <tr valign="top">
    <td width="457" height="21" valign="top">
</table>
  </tr>
</table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>