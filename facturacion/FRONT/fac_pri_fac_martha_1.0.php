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
	font-size: 11px;	
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente['Caj_Fec']);?>
<table width="703" height="100%" border="0" align="left">
<tr>
          <td width="697" height="476" colspan="4" align="left" valign="top">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" style="table-layout:fixed;" class="Letra_punto_venta_2">
        <tr>
          <td height="103" valign="bottom" class="Letra_punto_venta_2">&nbsp;</td>
          <td colspan="2" align="center" valign="bottom">&nbsp;</td>
        </tr>
        <tr>
          <td height="21" colspan="3" valign="bottom" class="Letra_punto_venta_2">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="14%">&nbsp;</td>
              <td width="86%" height="25"><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac'];  
			}
			else { echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; }
		    ?></td>
            </tr>
          </table></td>
          </tr>
        <tr>
          <td colspan="2" valign="middle" class="Letra_punto_venta_2" style="white-space: nowrap; overflow: hidden;"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="21%">&nbsp;</td>
              <td width="79%" height="25"><span class="Letra_punto_venta_2" style="white-space: nowrap; overflow: hidden;">
                <?php if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Dir']; 
			} ?>
              </span></td>
            </tr>
          </table></td>
          <td width="24%" rowspan="2" align="right" valign="middle"><table width="70%" height="18" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="27%" align="right" valign="middle" class="Letra_punto_venta_2">&nbsp;<?php echo $dia;?></td>
              <td width="37%" align="right" valign="middle" class="Letra_punto_venta_2">&nbsp;<?php echo $mes;?></td>
              <td width="36%" align="right" valign="middle" class="Letra_punto_venta_2">&nbsp;<?php echo $anio;?></td>
              </tr>
          </table></td>
          </tr>
        <tr>
          <td width="53%" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="43%">&nbsp;</td>
              <td width="57%" height="25"><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Ced']; 
			} ?></td>
              </tr>
          </table></td>
          <td width="22%" align="left" valign="middle" class="Letra_punto_venta_2"><?Php //echo $row_institucion['Ciu_Des']; ?></td>
          </tr>
        <tr>
          <td height="52">&nbsp;</td>
          <td colspan="2" align="center" valign="middle">&nbsp;</td>
          </tr>
        <tr>
          <td colspan="3" align="left" valign="top">
            
            <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	
	?>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <?php do{?>
              <tr>
                <td width="80" align="center" ><?Php echo $row_rs_cliente['Vet_Can']?></td>
                <td width="283"><div align="left">&nbsp;<?Php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs']?></div></td>
                <td width="160" align="right"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?>&nbsp;</td>
                <td width="60" align="right" ><div align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?></div></td>
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
            </table>          
          <!-- Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL -->          
        </td>
       </tr>
        
      </table>
<tr>
            <td  valign="top"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <tr>
                <td width="77%" height="25" align="right"><!--Subtotal&nbsp;-->
                  &nbsp;</td>
                <td width="23%" height="12" align="right"><?Php echo formato_numero($resultados[0], 2, 1); ?></td>
              </tr>
              <tr>
                <td height="25" align="right"><!--Descuento&nbsp;-->
                  &nbsp;</td>
                <td height="12" align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?></td>
              </tr>
              <tr>
                <td height="25" align="right"><!--IVA&nbsp;-->
                  &nbsp;</td>
                <td height="12" align="right"><?Php echo formato_numero($resultados[3], 2, 1); ?></td>
              </tr>
              <tr>
                <td height="25" align="right"><!--Tarifa 0%&nbsp;&nbsp;--></td>
                <td height="12" align="right"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></td>
              </tr>
              <!-- <tr>
                <td width="68%" align="right"><!--Tarifa 12%&nbsp;</td>
                <td width="32%" height="10" align="right"><?Php //echo formato_numero($resultados[2]+0, 2, 1); ?></td>
              </tr>-->
              <tr>
                <td height="20" align="right"><!--TOTAL&nbsp;-->
                  &nbsp;</td>
                <td height="25" align="right"><?php echo number_format($resultados[5], 2); ?></td>
              </tr>
              <tr>
                <td colspan="2"><!--Usuario:-->
                  <?Php 
						$nom = explode(' ',$Ses_Prs_Ape);
						$ape = explode(' ',$Ses_Prs_Nom);
						//echo $nom[0].' '.$ape[0]; 
				?></td>
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