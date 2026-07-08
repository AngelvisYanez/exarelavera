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
	/*font-family: Verdana;*/
	font-family: Verdana;
	font-size: 10px;	
}
td[Attributes Style] {   
    height: 10px;    
}
</style>
</head>
<body>
<?Php  list($anio, $mes, $dia) = preg_split('![-]!', $row_rs_cliente['Caj_Fec']);?>
<table width="799" height="100%" border="0" align="left">
<tr>
          <td width="793" height="193" colspan="4" align="left" valign="top">
          <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
        <tr>
          <td height="86" align="right" valign="bottom">&nbsp;</td>
          <td colspan="2" valign="bottom" height="86">&nbsp;</td>
        </tr>
        <tr>
          <td width="11%" height="25" align="right" valign="middle">&nbsp;</td>
          <td colspan="2" valign="middle"><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac'];  
			}
			else { echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; }
		    ?></td>
          </tr>
        <tr>
          <td height="25">&nbsp;</td>
          <td colspan="2"><?php if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Dir']; 
			} ?></td>
          </tr>
        <tr>
          <td height="25">&nbsp;</td>
          <td width="38%"><?php if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Ced']; 
			} ?></td>
          <td width="51%" align="right" valign="bottom"><table width="32%" height="19" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="42%" align="center" valign="top">&nbsp;<?php echo $dia." / ".$mes." / ".$anio;?>&nbsp;</td>
              </tr>
          </table></td>
          </tr>
        <!--<tr>
          <td height="18">&nbsp;</td>
          <td><?Php //echo $row_institucion['Ciu_Des']; ?></td>
          <td width="51%" align="right" valign="bottom">&nbsp;</td>
          </tr>-->
        <tr>
          <td height="17" colspan="3" align="left" valign="top">&nbsp;</td>
        </tr>
        <tr>
          <td height="68" colspan="3" align="left" valign="top">
          <br>
          <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	
	?>
            <table width="99%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
              <tr>
                <td align="center">&nbsp;<!--Cant.--></td>
                <td align="center">&nbsp;<!--Descripci&oacute;n--></td>
                <td align="center">&nbsp;<!--PVP--></td>
                <td align="center">&nbsp;<!--Total--></td>
              </tr>
              <?php do{?>
              <tr>
                <td width="60" align="left"><div align="left"><?Php echo $row_rs_cliente['Vet_Can']?></div></td>
                <td width="507"><div align="left">&nbsp;<?Php echo $row_rs_cliente['Ite_Lar'].' '.$row_rs_cliente['Pro_Obs']?></div></td>
                <td width="96" align="right"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?></td>
                <td width="80" align="right" ><div align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?></div></td>
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
                <td align="left"><div align="left"><?Php echo $row_rs_interes['Vet_Can']?></div></td>
                <td>&nbsp;<?Php echo $row_rs_interes['Ite_Lar']?></td>
                <td align="right"><?Php echo number_format($row_rs_interes['Vet_Pru'], 2); ?>&nbsp;&nbsp;</td>
                <td align="right"><?Php echo number_format($row_rs_interes['Vet_Imp'], 2); ?></td>
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
            <td height="336" colspan="4" valign="bottom">
			<br><br><br><br>
			<table width="99%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse" class="Letra_punto_venta_2">             
              <tr>
                <td width="26%" height="22" align="right"><!--Subtotal&nbsp;-->&nbsp;</td>
                <td width="74%" align="right" valign="bottom"><?Php echo formato_numero($resultados[0], 2, 1); ?></td>
              </tr>                                                  
              <tr>
                <td height="22" align="right"><!--Tarifa 0%-->&nbsp;&nbsp;</td>
                <td align="right" valign="bottom"><?Php echo formato_numero($resultados[1]+0, 2, 1); ?></td>
              </tr>
              <tr>
                <td height="22" align="right"><!--IVA&nbsp;-->&nbsp;</td>
                <td align="right" valign="bottom"><?Php echo formato_numero($resultados[3], 2, 1); ?></td>
              </tr>
              <tr>
                <td height="22" align="right"><!--TOTAL&nbsp;-->&nbsp;</td>
                <td align="right" valign="bottom"><?php echo number_format($resultados[5], 2); ?></td>
              </tr>              
            </table>                      
  <tr valign="top">
    <td valign="top">
  </table>
  </tr>
</table>
</body>
</html>
<?Php
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>