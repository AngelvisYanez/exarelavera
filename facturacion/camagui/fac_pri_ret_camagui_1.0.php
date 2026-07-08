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
	font-family: Verdana;
	font-size: 10px;	
}
.Letra_punto_venta_3 {				
	font-family: Verdana;
	font-size: 9px;	
}
</style></head>
<body>
<table width="519"   border="0" align="left">
        <td width="513" height="173" colspan="4" valign="top"><table width="100%" height="167" border="0" align="left" cellpadding="0" cellspacing="0">
        <tr>
          <td height="33" >&nbsp;</td>
          <td width="137" height="33" >&nbsp;</td>
          <td height="33" valign="bottom" >&nbsp;</td>
        </tr>
        <tr>
          <td width="163" height="34" >&nbsp;</td>
          <td height="34" >&nbsp;</td>
          <td height="34" valign="middle" ><table width="93%" height="25" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="50%">&nbsp;</td>
              <td width="50%" align="right" valign="middle"><?Php $Fec_Emi=explode('-',$row_prin_renta['Ret_Fec']); ?>
                <span><?php echo $Fec_Emi[2].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[0].'&nbsp&nbsp;&nbsp;'; ?></span></td>
            </tr>
          </table></td>
        </tr>
        <tr >
          <td height="18" colspan="2" align="left" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="19%">&nbsp;</td>
              <td width="81%"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></td>
            </tr>
          </table></td>
          <td align="left" valign="middle" class="Letra_punto_venta_2"><table width="88%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="38%" height="13" align="right">&nbsp;</td>
              <td width="62%"><?php echo $row_prin_renta['Prs_Ced']; ?></td>
              </tr>
          </table></td>
        </tr>
        <tr >
          <td height="20" colspan="2" align="left" valign="middle" class="Letra_punto_venta_2"><table width="100%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="19%">&nbsp;</td>
              <td width="81%"><?php echo $row_prin_renta['Prs_Dir']; ?></td>
            </tr>
          </table></td>
          <td height="20" valign="bottom" class="Letra_punto_venta_2"><table width="88%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="38%" height="13" align="right">&nbsp;</td>
              <td width="62%"><?php echo $row_prin_renta['Ciu_Des']; ?></td>
            </tr>
          </table></td>
        </tr>
        <tr >
          <td height="24" colspan="3" valign="top" class="Letra_punto_venta_2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
        <tr>
          <td height="19" align="center" valign="middle" class="Letra_punto_venta_2" ><?php echo $row_prin_renta['Tic_Des']; ?></td>
          <td align="center" valign="middle" class="Letra_punto_venta_2" ><?php 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo "001-001-000".$row_prin_renta['Cop_Num']; 
		} 
		else 
		{  
			echo $row_prin_renta['Cop_Num'];
		}?></td>
          <td width="210" colspan="-2" align="left" valign="middle" class="Letra_punto_venta_2"><table width="73%" height="14" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>
              <td width="20%">&nbsp;</td>
              <td width="80%"><?php echo $row_prin_renta['Aut_Fci'];?></td>
            </tr>
          </table></td>
        </tr>
        <tr >
          <td height="19" colspan="2" align="left" valign="bottom" class="Letra_punto_venta_2"><?php 
		if ($row_rs_renta['Aut_Cod'] != "") 
		{ 
			echo $row_prin_renta['Aut_Sri']; 
		} 
		else 
		{
			echo $row_prin_renta['Cop_Aut']; 
		}
		?></td>
          <td colspan="-2" align="left" valign="bottom" class="Letra_punto_venta_2">
          <table width="54%" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2">
            <tr>             
              <td width="25%" align="right">&nbsp;</td>
              <td width="26%" align="right"><?php echo $row_prin_renta['Aut_Cad']; ?></td>
              </tr>
          </table></td>
        </tr>
      </table>
  <tr valign="top">
    <td  valign="top"><table width="489" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
	<tr>
	  <td height="60" colspan="2" align="left" valign="top" class="Letra_punto_venta_2">
	  <?Php 
	 $tarifa_0 = 0;
	 $tarifa_12 = 0;
	 $Cop_Des = $row_prin_renta['Cop_Des'];		
	 $observacion=$row_prin_renta['Cop_Obs'];	
     $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']); ?>

    <table width="486" border="0" cellpadding="0" cellspacing="0" class="Letra_punto_venta_2" >
	 <tr align="center">
            <td colspan="6"></td>
          <tr align="center">
            <td height="15" colspan="6">&nbsp;</td>
            </tr> 
		  <?Php $Total_Ret=0; 
		  foreach($rs_prin_renta as $row)
		  { ?>
          <tr align="center" >
            <td width="61" align="center"  ><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
            <td width="82"   ><?Php $Sri_Cod=$row['Ren_Sri']; echo $obBD_con1->codAir($Sri_Cod); //"&nbsp"  ?></td>
            <td width="118"    ><?Php $Sri_Imp=$row['Ret_Imp']; echo $Sri_Imp;  ?></td>
            <td width="109"  ><?Php $Ren_Bas = number_format($row['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
            <td width="78" align="center">	<?Php $Ren_Por= $row['Ren_Por'].'%'; echo $Ren_Por; ?></td>
            <td width="101" align="right" >
             
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?Php $Val_Ret=($row['Ret_Bas']*$row['Ren_Por'])/100; 
			       $Val_Ret=number_format($Val_Ret,2,'.',''); 
				   echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  ?>
             </td></tr>
<?Php }//Fin del foreach ?>
		</table>		
        </td>
	  </tr>
	
	<tr>
	  <td height="2" colspan="2" class="Letra_punto_venta_2"></td>
	  </tr>
	<tr>
	  <td width="270" height="30" class="Letra_punto_venta_2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </td>
	  <td width="211" align="right" valign="bottom" class="Letra_punto_venta_2"><strong><?Php echo number_format ($Total_Ret, 2,'.',''); ?></strong></td>
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