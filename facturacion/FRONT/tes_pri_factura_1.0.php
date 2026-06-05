<?php
/*
* Descripción: Reporte para la impresión de la factura (formato completo)
* Fecha de actualización: 2012-08-09
* Desarrollador: Lewis Chimarro
*/	
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_fac_ven.php');	  	
require_once('../../Librerias/procedimientos/almacenados_standar.php');		

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Tes($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Tes;	 	 	 

/**
* Color para la factura
*/
define(fix_color, "#000099");
	  
if (isset($Vet_Cod))
{
	$rs_cliente = $obBD_con1->getArrayConsulta(37, $Vet_Cod, $obBD_conexion);	
	$row_cliente = current($rs_cliente);
	$cliente = $row_cliente['Vet_Cod'];
 	$Vet_Num = $row_cliente['Vet_Num'];		
	/**
	* Control para mostrar datos academicos
	$rs_cliente_esc = consultas_tes(42, $row_rs_cliente['Prs_Ced'].'*'.$periodo);
	$row_rs_cliente_esc = mysqli_fetch_assoc($rs_cliente_esc);
	$total_rs_cliente_esc = mysqli_num_rows ($rs_cliente_esc);
	*/
	/**
	* Consulta los datos de la empresa
	*/				
	$row_rs_cabecera = $obBD_con1->getRowConsulta(207, $Ses_Suc_Cod, $obBD_conexion);
		
	/**
	* Consulta del vendedor en base al codigo de la persona
	*/
	$row_rs_vendedor = $obBD_con1->getRowConsulta(24, $Ses_Prs_Cod.'*'.$Ses_Suc_Cod, $obBD_conexion);
	/**
	* Consulta los datos de la serie 001-001 de la autorización
	*/		  
	$row_rs_facturanum = $obBD_con1->getRowConsulta(81, $row_cliente['Aut_Cod'], $obBD_conexion);
	$Aut_Sri = $row_rs_facturanum['Aut_Sri'];
	$Suc_Sri = $row_rs_facturanum['Suc_Sri'];
	$Pun_Sri = $row_rs_facturanum['Pun_Sri'];
	/* 
	* Consulta la provicia y pais de la sucursal 
	*/
	$row_provincia = $obBD_con1->getRowConsulta(3, $row_rs_cabecera['Ciu_Cod'], $obBD_conexion);

 /**
  $arreglo = explode(' ',$row_rs_cliente_esc['Car_Nom']); 
  for ($i=0; $i<=count($arreglo)-1;$i++)
  {
  $corte = cortar_cadena(0,2,$arreglo[$i]);
	if (strlen($corte) == 3)
	  {				  
	  echo $corte.". "; 
	  }
  } */
}//Fin del if (isset($Vet_Cod))

$banco = $row_cliente['Bak_Des'];
$cheque = $row_cliente['Vet_Che'];		
$cuenta = $row_cliente['Vet_Cue'];	
$observacion = $row_cliente['Vet_Obs'];
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?Php require_once("../../mascaras/model1/estilos/print.php"); ?>
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
.Estilo23 {
	font-size: 12;
	font-family: Verdana, Arial, Helvetica, sans-serif;
	color: #000099;
	font-weight: bold;
	text-transform: uppercase;
}
-->
</style>
</head>
<body>
<table width="650" height="63" border="0" align="center">
<tr>
 <td><table width="650"  height="168" border="0" cellpadding="0" cellspacing="0">
   <tr>
     <td width="108" rowspan="11" valign="top"><img src="<?php echo $row_rs_cabecera['Emp_Log']; ?>" width="108" height="84"></td>
     <td colspan="4" rowspan="7" valign="top" align="center" class="Estilo22"><label> <? echo	$row_rs_cabecera['Emp_Nom']; ?> </label></td>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td colspan="3"></td>
   </tr>
   <tr>
     <td height="15" colspan="3" align="center" class="tabla"><span class="Estilo14"><span class="style2 Estilo15">R.U.C.:</span></span><span class="Estilo16"><? echo	$row_rs_cabecera['Emp_Ruc']; ?></span></td>
   </tr>
   <tr>
     <td colspan="4" valign="top" align="center" class="Estilo23"><? echo	$row_rs_cabecera['Emp_Ren']; ?></td>
     <td height="15" colspan="3" bgcolor="<?Php echo fix_color; ?>" class="tabla">
      <div align="center"><span class="Estilo17">&nbsp;FACTURA</span></div>
     </td>
   </tr>
   <tr>
     <td colspan="4" class="Texto_Reporte" valign="top"><div align="center"><span class="style2">Direcci&oacute;n: <? echo	$row_rs_cabecera['Suc_Dir']; ?></span></div></td>
     <td colspan="5" bordercolor="0" valign="top" class="tabla">
     <div align="center" class="Estilo13">
     <? echo $Suc_Sri.' - '.$Pun_Sri.' - '.$Vet_Num; ?>
     </div><input name="Vet_Cod" type="hidden" id="Vet_Cod" value="<?Php echo $cliente; ?>" >
     </td>
   </tr>
   <tr>
     <td colspan="4" rowspan="2" valign="top" class="Texto_Reporte"><div align="center"><span class="style2">Tel&eacute;fono: <? echo	$row_rs_cabecera['Suc_Te1']; ?></span></div>
     <div align="center" class="style2"><?Php 
if (count($row_provincia) > 0)
{
    $provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
}
else
{
    $provincia = "";					
}
echo $row_rs_cabecera['Ciu_Des'].$provincia;?></div>            
     </td>
     <td colspan="3" valign="top" bordercolor="0" bgcolor="<?Php echo fix_color; ?>" class="Texto_Reporte Estilo19 tabla"><div align="center">AUTORIZACION SRI</div></td>
   </tr>
   <tr>
     <td colspan="3" valign="top" bordercolor="0" class="Texto_Reporte tabla"><div align="center">
       <? echo $Aut_Sri; ?>
     </div></td>
   </tr>
   <tr class="Texto_Reporte">
     <td class="style2"><div align="left">Se&ntilde;or(s):</div></td>
     <td colspan="4"><span><? echo $row_cliente['Prs_Ape'].' '.$row_cliente['Prs_Nom']; ?></span></td>
     <td width="70" bgcolor="<?Php echo fix_color; ?>" class="Texto_Reporte tabla">
         <div align="center" class="Estilo19">
           A&Ntilde;O
         </div>
     </td>
     <td width="70" bgcolor="<?Php echo fix_color; ?>" class="Texto_Reporte tabla">
         <div align="center" class="Estilo19">
           MES
         </div>
     </td>
    <td width="55" bgcolor="<?Php echo fix_color; ?>" class="Texto_Reporte tabla">
         <div align="center" class="Estilo19">
           D&Iacute;A
         </div>
     </td>
   </tr>
   <tr class="Texto_Reporte">
     <td valign="top" class="style2"><div align="left">Direcci&oacute;n:</div></td>
     <td colspan="4" valign="top"><? echo $row_cliente['Prs_Dir']; ?></td>
     <td valign="top" class="tabla"><div align="center">
         <?Php list($ann, $mes, $dia) = preg_split('![/.-]!', $row_cliente['Caj_Fec']); 
         echo $ann; ?>
     </div></td>
     <td valign="top" class="tabla"><div align="center"><?Php
     echo $mes; ?></div></td>
     <td valign="top" class="tabla"><div align="center"><?Php echo $dia; ?></div></td>
   </tr>
   <tr class="Texto_Reporte">
     <td height="19" class="style2"><div align="left">R.U.C. &oacute; C.I. No.:</div></td>
     <td width="138" class="Texto_Reporte"><? echo $row_cliente['Prs_Ced']; ?></td>
     <td width="6" class="Texto_Reporte">&nbsp;</td>
     <td width="23" class="Texto_Reporte"><span class="style2">Telf:</span></td>
     <td width="180" class="Texto_Reporte"><? echo $row_cliente['Prs_Tel']; ?></td>
     <td colspan="3" class="Texto_Reporte tabla"><span class="style2">LUGAR: </span>&nbsp;<? echo $row_rs_cabecera['Ciu_Des']; ?></td>
   </tr>
   <tr>
     <td height="19" colspan="5">&nbsp;</td>
     <td colspan="3" class="Texto_Reporte">&nbsp;</td>
   </tr>
 </table>
   <table width="650" border="0" cellpadding="0" cellspacing="0" class="tabla">
     <tr>
       <td width="111" bgcolor="<?Php echo fix_color; ?>" class="tabla"><div align="center" class="Estilo19">CANTIDAD</div></td>
       <td width="183" bgcolor="<?Php echo fix_color; ?>" class="tabla"><div class="Estilo19" align="center">DESCRIPCI&Oacute;N</div>
       </td>
       <td bgcolor="<?Php echo fix_color; ?>" class="tabla"><div align="center" class="Estilo19">P. UNITARIO </div></td>
       <td bgcolor="<?Php echo fix_color; ?>" class="tabla"><div align="center" class="Estilo19">&nbsp;&nbsp;IMPORTE</div></td>
     </tr>
     <?Php 
    $tarifa_0 = 0;
    $tarifa_12 = 0;
    foreach($rs_cliente as $row_rs_cliente)
    {
    ?>
     <tr class="Texto_Reporte">
       <td width="112" height="17"><div align="center"><?Php echo $row_rs_cliente['Vet_Can']?></div></td>
       <td width="165"><?Php echo $row_rs_cliente['Ite_Lar']?></td>
       <td width="159"><div align="right"><?Php echo number_format($row_rs_cliente['Vet_Pru'], 2); ?></div></td>
       <td width="119"><div align="right"><?Php echo number_format($row_rs_cliente['Vet_Imp'], 2); ?></div></td>
     </tr>                     
    <?Php 
    /**
    * % de Descuento total 
    */
    $Vet_Des = $row_rs_cliente['Vet_Des'];				
}//Fin del foreach($rs_cliente as $row_rs_cliente)
	/**
	* Suma del descuento }
	*/
	$des = $des_0 + $des_12;
	/**
	* calculo del iva con descuento individual 
	*/
	$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
	/**
	* Calculo del descuento total 
	*/
	if ($Vet_Des != 0)
	{
		$des = ($subtotal * $Vet_Des) / 100;
		$des_12 = ($tarifa_12 * $Vet_Des) / 100;
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;		
	}
	/**
	* Calculo del total 
	*/
	$total = ($subtotal - $des) + $iva;
	/**
	*  Retorno los calculos de las facturas 
	*/
	$resultados = explode('*', $obBD_con1->calculos($Vet_Cod, $obBD_conexion));	
	?>                 
     <tr>
       <td height="20" colspan="2" class="Texto_Reporte"><?Php if ($banco != "(Ninguno)") { echo $banco; } ?>
           <?Php echo $cheque; ?></td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2">TOTAL GRAVADO I.V.A. 0% $ </div></td>
       <td width="58" height="20" valign="top" class="Texto_Reporte tabla"><div align="right"><?Php echo number_format ($resultados[1], 2); ?> </div></td>
     </tr>
     <tr>
       <td height="20" colspan="2" class="Texto_Reporte"><?Php echo $cuenta ?></td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2">TOTAL GRAVADO I.V.A. 12% $</div></td>
       <td width="58" height="20" valign="top" class="Texto_Reporte tabla"><div align="right"><?Php echo number_format ($resultados[2], 2); ?> </div></td>
     </tr>
     <tr>
       <td height="20" colspan="2" class="Texto_Reporte">Obs.:<?Php echo $observacion ?></td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2">SUBTOTAL $</div></td>
       <td width="58" height="20" valign="top" class="Texto_Reporte tabla"><div align="right"><?Php echo number_format ($resultados[0], 2); ?> </div></td>
     </tr>
     <tr>
       <td colspan="2" valign="top" class="Texto_Reporte">&nbsp;</td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2" tabla>DESCUENTO $ </div></td>
       <td width="58" height="21" valign="top" class="Texto_Reporte tabla"><div align="right"><?Php echo number_format($resultados[4], 2); ?> </div></td>
     </tr>
     <tr>
       <td colspan="2" valign="top" class="Texto_Reporte">&nbsp;</td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2">I.V.A. $</div> </td>
       <td width="58" height="21" valign="top" class="Texto_Reporte tabla"><div align="right"><?Php echo number_format($resultados[3], 2); ?> </div></td>
     </tr>
     <tr>
       <td colspan="2" valign="top" class="Texto_Reporte"><div align="left">&nbsp;&nbsp;RECIBI CONFORME &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ENTREGUE CONFORME </div></td>
       <td width="209" valign="top" class="Texto_Reporte tabla"><div align="right" class="style2">TOTAL $ </div></td>
       <td width="59" height="20" valign="top" class="Texto_Reporte tabla"><div align="right"><?php echo number_format($resultados[5], 2); ?> </div></td>
     </tr>
   </table>
     </td>
   </tr>
<tr>
  <td><table width="100%" border="0" cellspacing="0" cellpadding="0" class="Texto_normal_9">
    <tr>
        <td width="50%">AUTORIZACI&Oacute;N: <?Php echo $row_rs_facturanum['Aut_Fci']; ?> - CADUCA: <?Php echo $row_rs_facturanum['Aut_Cad']; ?></td>
        <td width="50%" align="right">ORIGINAL: CLIENTE - COPIA: EMISOR</td>
      </tr>
  </table></td>
</tr>
 </table>
</body>
</html>
<?php 
$obBD_conexion->cerrar();
$obBD_con1->liberar();
?>