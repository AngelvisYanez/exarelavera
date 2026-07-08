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
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" media="print" />
<style type="text/css">
<!--
.Texto_Reporte {
	font-family:Arial, Helvetica, sans-serif;
	font-size: 11
	letter-spacing: -2px;}
.Texto_Reporte1 {
	font-family:Arial, Helvetica, sans-serif;
	font-size: 11
	letter-spacing: -2px;}
body {	
	margin-top: 0px;
}
-->
</style>
</head>
<body>
<table width="900"   border="0" align="left">
    <tr>
      <td width="90" valign="top">          
      <td width="800" height="101" valign="bottom"><table width="800" height="81" border="0" align="left" cellpadding="0" cellspacing="0">
        <tr>
          <td colspan="3" class="Texto_Reporte1"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></td>
          <td>&nbsp;</td>
        </tr>
        <tr>
          <td colspan="3" class="Texto_Reporte1"><?php echo $row_prin_renta['Prs_Ced']; ?></td>
          <td>&nbsp;</td>
        </tr>
        <tr >
          <td colspan="3" class="Texto_Reporte1"><?php echo $row_prin_renta['Prs_Dir']; ?></td>
          <td class="Texto_Reporte1" align="center">&nbsp;&nbsp;&nbsp;
            <?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); ?>
          <?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0]; ?></td>
        </tr>
        <tr>
          <td width="295" align="rigth" class="Texto_Reporte1" ><?php echo $row_prin_renta['Tic_Des']; ?></td>
          <td width="168" align="rigth" class="Texto_Reporte1" ><?php 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo "001-001-000".$row_prin_renta['Cop_Num']; 
		} 
		else 
		{  
			echo $row_prin_renta['Cop_Num'];
		}?></td>
          <td width="134" align="rigth" class="Texto_Reporte1" >&nbsp;</td>
          <td width="203" align="rigth" class="Texto_Reporte1" ><?php echo $row_prin_renta['Cop_Fec']; ?></td>
        </tr>
      </table>            
  <tr valign="top">
    <td colspan="2"  valign="top">    
    <table width="800" border="0" align="left" cellpadding="0" cellspacing="0">
      <tr>
        <td height="60" colspan="3" valign="top" class="Texto_Reporte"><?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	 $Cop_Des = $row_prin_renta['Cop_Des'];		
	 $observacion=$row_prin_renta['Cop_Obs'];	
     $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); ?>
          <table width="800" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte" >
            <?Php $Total_Ret=0; 
		  foreach($rs_prin_renta as $row)
		  { ?>
            <tr align="center" >
              <td width="57"  ><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
              <td width="93"   ><?Php $Sri_Cod=$row['Ren_Sri']; echo $obBD_con1->codAir($Sri_Cod); //"&nbsp"  ?></td>
              <td width="195"    ><?Php $Sri_Imp=$row['Ret_Imp']; echo $Sri_Imp;  ?></td>
              <td width="107"  ><?Php $Ren_Bas = number_format($row['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
              <td width="82" align="right"><?Php $Ren_Por= $row['Ren_Por'].'%'; echo $Ren_Por; ?></td>
              <td width="82" align="right" >
                <?Php $Val_Ret=($row['Ret_Bas']*$row['Ren_Por'])/100; 
			       $Val_Ret=number_format($Val_Ret,2,'.',''); 
				   echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?></td>
              </tr>
            <?Php }//Fin del foreach ?>
          </table></td>
        </tr>
      <tr>
        <td width="178" height="23" class="Texto_Reporte">&nbsp;</td>
        <td width="359" height="23" align="right">&nbsp;</td>
        <td width="88" height="23" class="Texto_Reporte" align="right"><div align="right"><?Php echo number_format ($Total_Ret, 2,'.',''); ?></div></td>
        </tr>
    </table>        
</table>
  </tr>
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