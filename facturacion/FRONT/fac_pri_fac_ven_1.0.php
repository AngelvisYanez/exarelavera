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
	/* 
	* Consulta la carrera del cliente 
	*/
	$rs_carrera = $obBD_con1->consulta(sentencias_tes(224, $obBD_con1->parametros($row_rs_cliente['Nge_Cod'])),
									$obBD_conexion->conexion);
	$row_rs_carrera = $obBD_con1->registros();
	$total_rs_carrera = $obBD_con1->numregistros();	
	/*
	* Llamado del representate delcliente
	*/
	$rs_representante = $obBD_con1->consulta(sentencias_tes(33, $obBD_con1->parametros($row_rs_cliente['Cli_Cod'])),
									$obBD_conexion->conexion);
	$row_rs_representante = $obBD_con1->registros();
	
	/*
	* Consulta de los tipos de pago 
	*/
	$rs_pagos = $obBD_con1->consulta(sentencias_tes(316, $obBD_con1->parametros($Vet_Cod)), $obBD_conexion->conexion);
	$row_rs_pagos = $obBD_con1->registros();
	$total_rs_pagos = $obBD_con1->numregistros();				
}
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
</head>
<body>
<table width="600"  height="100%" border="0" align="left">
      <td  height="178" colspan="4" valign="middle"><table width="92%" border="0" align="left">
      <tr align="center">
        <td colspan="3">&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr align="center">
        <td colspan="3" align="right" valign="top" class="Texto_Reporte">
        <div align="right"><input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?Php echo $cliente; ?>" >
          No:&nbsp;<? echo $row_rs_cliente['Vet_Cod']; ?>
        </div>  
          </td>
        <td width="176" rowspan="2" valign="bottom" class="Texto_Reporte"><table width="148" border="0" align="right" cellpadding="0" cellspacing="0" class="Texto_bloques">
          <tr>
            <td align="center"><?Php list($ann, $mes, $dia) = split('[/.-]', $row_rs_cliente['Caj_Fec']); ?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $dia.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$mes.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$ann; ?> </td>
          </tr>
          <tr>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
          </tr>
        </table></td>
      </tr>
	  <tr align="center">
	    <td width="40" height="21" align="left">&nbsp;</td>
	    <td height="21" colspan="2" align="left" valign="bottom" class="Texto_Reporte">
		<? if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Fac']." (Est.:".$row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']." - ".$row_rs_cliente['Prs_Ced'].")";  }
								else { echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; }
		    ?></td>
	    </tr>
	  <tr align="center">
        <td align="left" class="LetraNegra">&nbsp;</td>
        <td width="346" align="left" valign="bottom" class="Texto_Reporte">
		<? if ($row_rs_representante['Cli_Fac'] != "")
			{ 
				echo $row_rs_representante['Cli_Ruf']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Ced']; 
			} ?></td>
        <td width="119" align="left" valign="bottom" class="Texto_Reporte"><? echo $row_rs_cliente['Ciu_Des']; ?></td>
        <td>&nbsp;</td>
      </tr>
      <tr align="center">
        <td align="left">&nbsp;</td>
        <td colspan="2" align="left" class="Texto_Reporte">
		<? if ($row_rs_representante['Cli_Dir'] != "")
			{ 
				echo $row_rs_representante['Cli_Dir']; 
			}
			else 
			{ 
				echo $row_rs_cliente['Prs_Dir']; 
			} ?></td>
        <td align="center" valign="top">
          <table width="100" border="0" align="right" cellpadding="0" cellspacing="0" class="Texto_normal_10">
            <tr>
              <td colspan="3"><? echo cortar_cadena_param(' ',$row_rs_carrera['Car_Nom']); ?></td>
            </tr>
            <tr>
              <td colspan="3"><? echo $row_rs_carrera['Mod_Des']; ?></td>
            </tr>
            <tr>
              <td colspan="3">
			  <?  if ($total_rs_carrera > 0)
			  		{ 
						echo $row_rs_carrera['Sem_Nom']; 
					} ?></td>
            </tr>
            <tr>
              <td colspan="3"><? echo $row_rs_carrera['Sem_Par']; ?></td>
              </tr>
          </table>
        </td>
      </tr>
    </table>
  <tr valign="top">
    <td height="613" valign="top">
    
    <table width="760" border="0" align="left" cellpadding="2" cellspacing="0">
	<tr>
	  <td height="140" colspan="3" valign="top" class="Texto_Reporte">
	  <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	do{
	?>
	    <table width="92%" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte" >
          <tr>
            <td width="112"><?Php echo $row_rs_cliente['Vet_Can']?></td>
            <td width="448"><?Php echo $row_rs_cliente['Ite_Lar']?></td>
            <td width="108" align="right"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?></td>
            <td width="88" align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?></td>
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
				<td width="112"><?Php echo $row_rs_interes['Vet_Can']?></td>
				<td width="448"><?Php echo $row_rs_interes['Ite_Lar']?></td>
				<td width="108" align="right"><?Php echo number_format($row_rs_interes['Vet_Pru'], 2); ?></td>
				<td width="88" align="right"><?Php echo number_format($row_rs_interes['Vet_Imp'], 2); ?></td>
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
          <tr>
            <td>&nbsp;</td>
            <td width="448"><?Php  echo $observacion ?></td>
            <td align="right">&nbsp;</td>
            <td align="right">&nbsp;</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
			<?Php
			/* 
			* Control para el banco 1 
			*/
			if ($row_rs_pagos['Ban_Cod'] > 0)
			{
			  $rs_banco = $obBD_con1->consulta(sentencias_tes(317, $obBD_con1->parametros($Vet_Cod.'*'.$row_rs_pagos['Vet_Num'].'*'.$row_rs_pagos['Ban_Cod'])), $obBD_conexion->conexion);
			  $row_rs_banco = $obBD_con1->registros();
			  $total_rs_banco = $obBD_con1->numregistros();
			  $banco=$row_rs_banco['Pld_Des']."&nbsp;&#8211;";
			}
			elseif ($row_rs_pagos['Bak_Cod'] > 1)//1=(Ninguno)
			{			
			  $rs_banco = $obBD_con1->consulta(sentencias_tes(318, $obBD_con1->parametros($Vet_Cod.'*'.$row_rs_pagos['Vet_Num'].'*'.$row_rs_pagos['Bak_Cod'])), $obBD_conexion->conexion);
			  $row_rs_banco = $obBD_con1->registros();
			  $total_rs_banco = $obBD_con1->numregistros();		
  			  $banco=$row_rs_banco['Bak_Des']."&nbsp;&#8211;";	
			}
			?>
            <td class="Texto_Reporte">&nbsp;</td>
            <td align="right">&nbsp;</td>
            <td align="right">&nbsp;</td>
          </tr>		  
        </table>
		</td>
	  </tr>	
	<tr>
	  <td width="103">&nbsp;</td>
	  <td width="369" rowspan="5">&nbsp;</td>
  	  <td width="276" rowspan="5" align="left" valign="top"><table width="214" border="0" align="left" cellpadding="0" cellspacing="0" class="Texto_Reporte">
        <tr>
          <td><div align="right"><?Php echo formato_numero($resultados[1], 2, 1); ?></div></td>
        </tr>
        <tr>
          <td ><div align="right"><?Php echo formato_numero($resultados[2], 2, 1); ?></div></td>
        </tr>
        <tr>
          <td ><div align="right"><?Php echo formato_numero($resultados[0], 2, 1); ?></div></td>
        </tr>
        <tr>
          <td ><div align="right"><?Php echo formato_numero($resultados[4], 2, 1); ?></div></td>
        </tr>
        <tr>
          <td ><div align="right"><?Php echo formato_numero($resultados[3], 2,1); ?></div></td>
        </tr>
        
        <tr>
          <td ><div align="right" class="Texto_bloques"><?php echo number_format($resultados[5], 2); ?></div></td>
        </tr>
      </table></td>	  
	  </tr>
	<tr>
	  <td width="103"></td>
	  </tr>
	<tr>
	  <td width="103"></td>
	  </tr>
	<tr>
	  <td width="103"></td>
	  </tr>
	<tr>
	  <td width="103"></td>
	  </tr>
	
	</table>	  
</table>
  </tr>
</table>
</body>
</html>
<?Php
@$obBD_con1->free_result($rs_cliente);
@$obBD_con1->free_result($rs_carrera);
@$obBD_con1->free_result($rs_representante);
@$obBD_con1->free_result($rs_pagos);
@$obBD_con1->free_result($rs_interes);
@$obBD_con1->free_result($rs_banco);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>