<?php
/**
* @abstract Reporte de retención para la impresión 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-10-01
* @author Lewis Chimarro
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');		  

/*
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 	 	 
	 
if (isset($Ret_Cod))
{   
   $row_rs_renta=$obBD_con1->getRowConsulta(167,$Ret_Cod, $obBD_conexion);
   	  
   if ($row_rs_renta['Aut_Cod'] != "") 
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(166,$Ret_Cod, $obBD_conexion);	   
   }
   else
   {
	   $rs_prin_renta = $obBD_con1->getArrayConsulta(553,$Ret_Cod, $obBD_conexion);  
   } 
   $row_prin_renta = current($rs_prin_renta);
}	
?>				
<html>
<head>
<title><?Php echo $Ses_Sys_Nom; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<?Php //require_once("../../mascaras/model1/estilos/print.php"); ?>
<style type="text/css">
.Letra_punto_venta_2 {
				
	font-family: Calibri;
	font-weight: normal;
	font-size: 11px;
	letter-spacing: 0px;
}
</style>

<link rel="stylesheet" href="print.css" type="text/css" media="print" />

</head>
<body>
<table width="779"   border="0" align="center" cellpadding="0" cellspacing="0">
        <td width="779" height="96" colspan="4" valign="top">
      <table width="100%" height="192" border="0" align="left" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
        <tr>
          <td height="82" colspan="8" >&nbsp;</td>
        </tr>
        <tr>
          <td height="39" colspan="8" ><p>&nbsp;</p>
          <p>&nbsp;</p></td>
        </tr>
        <tr >
          <td height="10" valign="middle" class="Texto_Reporte">C.I./R.U.C.:</td>
          <td height="10" valign="middle" class="Texto_Reporte"><? echo $row_prin_renta['Prs_Ced']; ?></td>
          <td height="10" valign="middle" class="Texto_Reporte">CIUDAD:</td>
          <td height="10" valign="middle" class="Texto_Reporte"><? echo $row_prin_renta['Ciu_Des']; ?></td>
        </tr>
        <tr >
          <td height="10" valign="middle" class="Texto_Reporte">RAZON SOCIAL:</td>
          <td height="10" colspan="3" valign="middle" class="Texto_Reporte"><? echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></td>
        </tr>
        <tr >
          <td height="10" valign="bottom" class="Texto_Reporte">DIRECCI&Oacute;N:</td>
          <td valign="bottom" class="Texto_Reporte"><? echo $row_prin_renta['Prs_Dir']; ?></td>
          <td valign="bottom" class="Texto_Reporte">FECHA:</td>
          <td valign="bottom" class="Texto_Reporte">
		 
          <? echo $row_prin_renta['Ret_Fec']; //$Fec_Emi[2].$Fec_Emi[1].$Fec_Emi[0]; ?></td>
        </tr>
        <tr>
          <td height="10" align="rigth" class="Texto_Reporte" >&nbsp;</td>
          <td align="rigth" class="Texto_Reporte" >&nbsp;</td>
          <td align="rigth" class="Texto_Reporte" >&nbsp;</td>
          <td align="rigth" class="Texto_Reporte" >&nbsp;</td>
        </tr>
        <tr>
          <td width="112" height="10" align="rigth" class="Texto_Reporte" >TIPO COMPROBANTE:</td>
          <td width="229" align="rigth" class="Texto_Reporte" ><span class="LetraEval"><? echo $row_prin_renta['Tic_Des']; ?></span></td>
          <td width="95" align="rigth" class="Texto_Reporte" >No. DOCUMENTO:</td>
          <td width="210" align="rigth" class="Texto_Reporte" ><? 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo "001-001-000".$row_prin_renta['Cop_Num']; 
		} 
		else 
		{  
			echo $row_prin_renta['Cop_Num'];
		}?></td>
        </tr>
        <tr>
          <td height="10" align="rigth" valign="bottom" class="Texto_Reporte" >FECHA IMP. COMP:</td>
          <td height="10" align="rigth" valign="bottom" class="Texto_Reporte" ><? 
			if ($row_rs_renta['Aut_Cod'] != "") 
			{ 
				//echo $row_prin_renta['Aut_Fci']; 
			} 
			else 
			{
				echo $row_prin_renta['Cop_Imf']; 
			}?></td>
          <td align="rigth" valign="bottom" class="Texto_Reporte" >FECHA CAD. COMP.</td>
          <td valign="bottom" class="Texto_Reporte" align="left"><? echo $row_prin_renta['Cop_Cad']; ?></td>
        </tr>
        <tr>
          <td height="10" align="rigth" valign="bottom" class="Texto_Reporte" >AUTORIZ. COMP.</td>
          <td height="10" align="rigth" valign="bottom" class="Texto_Reporte" ><? 
			if ($row_rs_renta['Aut_Cod'] != "") 
			{ 
				//echo $row_prin_renta['Aut_Sri']; 
			} 
			else 
			{
				echo $row_prin_renta['Cop_Aut']; 
			}
			?></td>
          <td align="rigth" valign="bottom" class="Texto_Reporte" >&nbsp;</td>
          <td valign="bottom" class="Texto_Reporte" align="left">&nbsp;</td>
        </tr>
      </table>
 <tr valign="top">
   <td  valign="top"><table width="86%" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000" class="Letra_punto_venta_2">
     <tr>
       <td colspan="4" valign="top" class="Texto_Reporte"><?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	 $Cop_Des = $row_prin_renta['Cop_Des'];		
	 $observacion=$row_prin_renta['Cop_Obs'];	
     $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); ?>
         <table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2" >
           <tr align="center" >
             <td colspan="7">&nbsp;</td>
           </tr>
           <tr align="center" >
             <td>EJERCICIO</td>
             <td>TIPO</td>
             <td>COD.</td>
             <td>CONCEPTO</td>
             <td>BASE IMP.</td>
             <td align="center">%</td>
             <td align="right" >VALOR</td>
           </tr>
           <tr align="center" >
             <td colspan="7"  ><hr size=1></td>
           </tr>
           <?Php $Total_Ret=0; 
		  foreach($rs_prin_renta as $row)
		  { ?>
           <tr align="center">
             <td width="70"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
             <td width="79"><?Php $Sri_Imp=$row['Ret_Imp']; echo $Sri_Imp;  ?></td>
             <td width="48"><?Php echo $Sri_Cod=$row['Ren_Sri']; //echo $obBD_con1->codAir($Sri_Cod); //"&nbsp"  ?></td>
             <td width="210" align="left"><? echo $row['Ren_Con'];?></td>
             <td width="73" valign="middle"><?Php $Ren_Bas = number_format($row['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
             <td width="56" align="center" valign="middle"><?Php $Ren_Por= $row['Ren_Por'].'%'; echo $Ren_Por; ?></td>
             <td width="42" align="right" valign="middle" ><?Php $Val_Ret=($row['Ret_Bas']*$row['Ren_Por'])/100; 
			       $Val_Ret=number_format($Val_Ret,2,'.',''); 
				   echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?></td>
           </tr>
           <?Php }//Fin del foreach ?>
         </table></td>
     </tr>
     <tr>
       <td height="52" colspan="4" valign="bottom" class="Texto_Reporte">&nbsp;</td>
     </tr>
     <tr>
       <td width="161" height="23" class="Texto_Reporte">&nbsp;</td>
       <td width="223" height="23" align="right">&nbsp;</td>
       <td width="34" height="23" align="right" class="Texto_Reporte">Total:</td>
       <td width="113" align="right" ><?Php echo number_format ($Total_Ret, 2,'.',''); ?></td>
     </tr>
   </table>
  </table>
</body>
</html>
<?Php
/**
* Cierre de las conexiones
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>