<?php
	  require_once('../../administrador/LOGICA/seguridad.php');
	  require_once('../LOGICA/fac_log_fac_ven.php');
	  require_once('../LOGICA/fac_log_deudas.php');
	  require_once('../../Librerias/procedimientos/almacenados_standar.php');	  
  	  require_once('../../Librerias/procedimientos/almacenados_matricula.php');	
	  require_once('../../Librerias/procedimientos/almacenados_academico.php');	

	/* Creacion del Objeto de conexion */
	$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
	/* Cracion del objeto mysql para las consultas */
	$obBD_con1 =  new Class_Log_Datos_Tes;
	  
	if (isset($Vet_Cod))
	{
		//$periodo = periodo_actual ('Per_Fea', 'Per_Fec', 'S');
        $rs_cliente = $obBD_con1->getArrayConsulta(37,$Vet_Cod,$obBD_conexion);					
		$total_rs_cliente=count($rs_cliente);
		$cliente = $row_rs_cliente['Vet_Cod'];
			
       // $rs_cliente_esc = consultas_tes(42, $row_rs_cliente['Prs_Ced'].'*'.$periodo);
		//$row_rs_cliente_esc = mysqli_fetch_assoc($rs_cliente_esc);
		//$total_rs_cliente_esc = mysqli_num_rows ($rs_cliente_esc);		

		/* Consulta la carrera del cliente */
		$row_rs_carrera = $obBD_con1->getRowConsulta(224,$row_rs_cliente['Nge_Cod'],$obBD_conexion);				
		$total_rs_carrera=$row_rs_carrera['Car_Nom'] > 0? 1 : 0;
				
		$row_rs_cabecera = $obBD_con1->getArrayConsulta(207,$Ses_Suc_Cod,$obBD_conexion);		
		$total_rs_cabecera = count($row_rs_cabecera);	
		
		/*Consulta del vendedor en base al codigo de la persona*/
	  	$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod,$obBD_conexion);//Variable de Sesion	  	
	  	$total_rs_vendedor = count ($row_rs_vendedor);

		/*Llamado del representate delcliente*/
		$row_rs_representante = $obBD_con1->getRowConsulta(33,$row_rs_cliente['Prs_Cod'],$obBD_conexion);				
		$total_rs_representante=$row_rs_representante['Cli_Fac'] > 0? 1 : 0;	  
		
	  	/*$rs_facturanum = consultas_tes(81, $row_rs_vendedor['Pun_Cod']);
	  	$row_rs_facturanum= mysqli_fetch_assoc($rs_facturanum);
	  	$total_rs_facturanum = mysqli_num_rows ($rs_facturanum);	
	   	$Suc_Sri = $row_rs_facturanum['Suc_Sri'];
	  	$Pun_Sri = $row_rs_facturanum['Pun_Sri'];*/

	  	$Vet_Num = $row_rs_cliente['Vet_Num'];		
	 }
	$banco = $row_rs_cliente['Bak_Des'];
	$cheque = $row_rs_cliente['Vet_Che'];		
	$cuenta = $row_rs_cliente['Vet_Cue'];	
	$observacion = $row_rs_cliente['Vet_Obs'];
?>				
<html>
<head>
<title>Ginus</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo6 {
	font-size: 18;
	font-weight: bold;
	color: 0;
}
.Estilo13 {color: 0}
.Estilo14 {font-size: 18px; font-family: Verdana, Arial, Helvetica, sans-serif;}
.Estilo15 {font-size: 12px}
.Estilo16 {font-size: 12px; font-style: normal; font-weight: normal; font-variant: normal; text-transform: none; text-decoration: none; font-family: Arial, Helvetica, sans-serif;}
.Estilo17 {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 18px;
	color: #FFFFFF;
}
.Estilo18 {font-size: 12px; font-weight: normal; text-align: justify; font-family: Arial, Helvetica, sans-serif;}
.Estilo19 {color: #FFFFFF}
.Estilo22 {
	font-size: 16;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	color: #000099;
	font-weight: bold;
	text-transform: uppercase;
}
-->
</style>
</head>

<body class="Cuerpo">
		 <table width="742" height="63" border="0" align="center">
           <tr>
             <td valign="top"><br><br><br><br><br><br><table width="310" border="0" cellpadding="0" cellspacing="0" class="Texto_Listados">
               <tr>
                   <td width="49">Se&ntilde;or(s):</td>
                 <td width="133"><? if ($total_rs_representante > 0){ echo $row_rs_representante['Est_Fac']; }
				 				else{ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; } ?></td>
                 <td width="7">&nbsp;</td>
                 <td width="93" align="right">Fecha:<?Php echo ' '.$row_rs_cliente['Caj_Fec']; ?></td>
               </tr>
                 <tr>
                   <td>R.U.C.:</td>
                   <td><? if ($total_rs_representante > 0){ echo $row_rs_representante['Est_Ruf']; }
				   			else 
							{ echo $row_rs_cliente['Prs_Ced']; }
				   ?></td>
                   <td>&nbsp;</td>
                   <td align="right">Tel&eacute;fono:<? echo ' '.$row_rs_cliente['Prs_Tel']; ?></td>
                 </tr>
                 <tr>
                   <td>Alumno:</td>
                   <td><? if ($total_rs_representante > 0){ echo $row_rs_cliente['Prs_Ape'].' '.$row_rs_cliente['Prs_Nom']; } ?></td>
                   <td>&nbsp;</td>
                   <td align="right">Lugar:<? echo ' '.$row_rs_cliente['Ciu_Des']; ?></td>
                 </tr>
                 <tr>
                   <td>Direcci&oacute;n:</td>
                   <td><? echo $row_rs_cliente['Prs_Dir']; ?></td>
                   <td>&nbsp;</td>
                   <td>&nbsp;</td>
                 </tr>
                 <tr>
                   <td>&nbsp;</td>
                   <td>&nbsp;</td>
                   <td>&nbsp;</td>
                   <td>&nbsp;</td>
                 </tr>
               </table>
               <table width="304" height="238" cellspacing="0" class="Texto_Listados">
                 <tr>
                   <td width="31" height="17" align="center">CANT</td>
                   <td width="170">DESCRIPCION                   </td>
                   <td width="45" align="center">P. UNI.</td>
                   <td align="center">IMPORTE</td>
                 </tr>
                 <tr>
                   <td height="59" colspan="5" valign="top" class="LetraNegra"><?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	foreach($rs_cliente as $row_rs_cliente){
	?>
                     <table width="300" border="0" cellpadding="0" cellspacing="0" class="Texto_Listados">
                       <tr>
                         <td width="30" height="17"><?Php echo $row_rs_cliente['Vet_Can']?></td>
                         <td width="120"><?Php echo $row_rs_cliente['Ite_Lar']?></td>
                         <td width="50" align="right"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?></td>
                         <td width="83" align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?></td>
                       </tr>
                     </table>
        <?Php 
		/* % de Descuento total */
		$Vet_Des = $row_rs_cliente['Vet_Des'];		
//		 Calculo del total de la factura 
		$subtotal= $subtotal + $row_rs_cliente['Vet_Imp'];
				
		/* Calculo de las tarifas */
		if ($row_rs_cliente['Iva_Por'] == 0)
		{
			$tarifa_0 = $tarifa_0 + $row_rs_cliente['Vet_Imp'];
			/*Descuento individual */
			$des_0 = $des_0 + ($row_rs_cliente['Vet_Imp'] * $row_rs_cliente['Vet_Dec'])/100;
		}
		else
		{
			$tarifa_12 = $tarifa_12 + $row_rs_cliente['Vet_Imp'];
			/*Descuento individual */
			$des_12 = $des_12 + ($row_rs_cliente['Vet_Imp'] * $row_rs_cliente['Vet_Dec'])/100;			
			$iva_12 = $row_rs_cliente['Iva_Por'];
		}						
				
	}
	/* Suma del descuento */
	$des = $des_0 + $des_12;
	/* calculo del iva con descuento individual */
	$iva = (($tarifa_12 - $des_12) * $iva_12)/100;

	/* Calculo del descuento total */
	if ($Vet_Des != 0)
	{
		$des = ($subtotal * $Vet_Des) / 100;
		$des_12 = ($tarifa_12 * $Vet_Des) / 100;
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
	}
	
	/*Calculo del total */
	$total = ($subtotal - $des) + $iva;
	
	/*  Retorno los calculos de las facturas */
	$resultados = explode('*',calculos($Vet_Cod));	

	?></td>
                 </tr>
                 <tr>
                   <td height="20" colspan="3" align="right">TOTAL GRAVADO I.V.A. 0% $ </td>
                   <td width="48" height="20" valign="top" align="right"><?Php echo number_format ($resultados[1], 2); ?> </td>
                 </tr>
                 <tr>
                   <td height="20" colspan="3" align="right">TOTAL GRAVADO I.V.A. 12% $</td>
                   <td width="48" height="20" valign="top" align="right"><?Php echo number_format ($resultados[2], 2); ?> </td>
                 </tr>
                 <tr>
                   <td height="20" colspan="3" align="right">SUBTOTAL $ </td>
                   <td width="48" height="20" valign="top" align="right"><?Php echo number_format ($resultados[0], 2); ?> </td>
                 </tr>
                 <tr>
                   <td colspan="3" valign="top" align="right">DESCUENTO $ </td>
                   <td width="48" height="20" valign="top" align="right"><?Php echo number_format($resultados[4], 2); ?> </td>
                 </tr>
                 <tr>
                   <td colspan="3" valign="top" align="right">I.V.A. $ </td>
                   <td width="48" height="20" valign="top" align="right"><?Php echo number_format($resultados[3], 2); ?> </td>
                 </tr>
                 <tr>
                   <td colspan="3" valign="top" align="right">TOTAL $ </td>
                   <td width="48" height="20" valign="top" align="right"><?php echo number_format($resultados[5], 2); ?></td>
                 </tr>
                 <tr>
                   <td height="20" colspan="4" align="left" valign="top" class="Texto_Listados">&nbsp;</td>
                 </tr>
                 <tr>
                   <td height="20" colspan="4" align="left" valign="top" class="Texto_Listados">ENTREGUE CONFORME&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;RECIBI CONFORME</td>
                 </tr>
               </table>
             <p>&nbsp;</p></td>
           </tr>
         </table>
		 <p>&nbsp;</p>
		 <br>
		 <p>&nbsp;</p>
		
</body>
</html>