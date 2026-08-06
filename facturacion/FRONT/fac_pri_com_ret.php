<?php
//require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_academico.php');

/**
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret;

/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	
	 
if (isset($Ret_Cod) )
{			
   /**
   *	Cosultamos el detalle e la retencion
   */
   $rs_print_renta=$obBD_con1->getArrayConsulta(501,$Ret_Cod,$obBD_conexion);   
   $num_rows_compra=count($rs_print_renta);
}	
?>				
<html>
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
<link href="../../css_teso.css" rel="stylesheet" type="text/css">
 <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
<style type="text/css">
<!--
.style2 {color: #000099}
.Estilo1 {font-size: 18px}
-->
</style>
</head>

<body class="Cuerpo">
<table width="726"   border="0" align="left">
    <td width="570" height="188" colspan="4" valign="top">
      <table width="688" border="0" align="left">
      
     
      <tr>
        <td colspan="4" >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4" >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4" ></td>
        <td ></td>
      </tr>
	    <tr>
        <td colspan="4" ></td>
        <td ></td>
      </tr>
      <tr>
        <td colspan="4" >&nbsp;</td>
        <td >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="4" ><div align="right"></div></td>
        <td ><div align="right" class="LetraNegra">
          <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td width="80%" class="LetraNegra" align="right">N&ordm; &nbsp; <?php echo $rs_print_renta[0]['Ret_Num']; ?></td>
              <td width="20%">&nbsp;</td>
            </tr>
          </table>
        </div></td>
      </tr>
      <tr>
        <td colspan="4" >&nbsp;</td>
        <td width="203" >&nbsp;</td>
      </tr>
      <tr>
        <td colspan="5" ></td>
        </tr>
      <tr>
        <td colspan="4" >&nbsp;</td>
        <td ><?Php $Fec_Emi=explode('-',$rs_print_renta[0]['Ret_Fec']); ?>
          <div align="right">
            <table width="85%" border="0" align="center" cellpadding="0" cellspacing="0">              
              <tr>
                <td width="9%" align="center" class="LetraNegra">&nbsp;</td>
                <td width="80%" align="center" class="LetraNegra">
				<?php 
				echo $Fec_Emi[0].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[1].'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$Fec_Emi[2]; 
				?>
                </td>
                <td width="11%" align="center" class="LetraNegra">&nbsp;</td>
              </tr>
            </table>
          </div></td>
      </tr>
      <tr align="center">
        <td colspan="5" class=""></td>
        </tr>
      
	  <tr >
	    <td  colspan="3" class="LetraNegra"></td>
	    <td colspan="2" class="LetraNegra">
        <div align="left"></div>          
        <div align="left" class="LetraNegra">
	      <div align="left">
	        <div align="left"></div>
          </div>
        </div>	      
        </td>
	  </tr>
	  <tr >
	    <td  colspan="3" class="LetraNegra"></td>
	    <td colspan="2" rowspan="7" class="LetraNegra">
        <table width="80%" border="0" cellspacing="2" cellpadding="0">          
          <tr>
            <td align="right" class="LetraNegra">&nbsp;</td>
          </tr>
          <tr>
            <td align="right" height="4" ></td>
          </tr>
          <tr>
            <td align="right" class="LetraNegra"><?php echo $row_prin_renta['Cop_Num']; ?>&nbsp;&nbsp;&nbsp;</td>
          </tr>
          <tr>
            <td align="right" ></td>
          </tr>
		   <tr>
            <td align="right" ></td>
          </tr>
           <tr>
             <td align="right" ></td>
           </tr>
           <tr>
            <td align="right" class="LetraNegra"><?php echo $row_prin_renta['Cop_Fec']; ?></td>
          </tr>
		   <tr>
            <td align="right" ></td>
          </tr>
		  
        </table></td>
	  </tr>
	  <tr >
	    <td width="156" height="21" class="LetraNegra">&nbsp;</td>
	    <td colspan="2" class="LetraNegra"><div align="left"><?php echo $row_prin_renta['Prs_Ape'].' '.$row_prin_renta['Prs_Nom']; ?></div></td>
	    </tr>
	  <tr >
	    <td height="21" class="LetraNegra">&nbsp;</td>
	    <td colspan="2" class="LetraNegra"><?php echo $row_prin_renta['Prs_Ced']; ?></td>
	    </tr>
	  <tr >
	    <td  colspan="3" align="rigth" class="LetraNegra" ></td>
	    </tr>
	  <tr >
        <td height="21" align="rigth" class="LetraNegra" >&nbsp;</td>
        <td colspan="2" class="LetraNegra"><?php echo $row_prin_renta['Prs_Dir']; ?></td>
        </tr>
	  <tr >
	    <td colspan="3" align="rigth" class="LetraNegra" ></td>
	    </tr>
	  <tr >
	    <td colspan="3" align="rigth" class="LetraNegra" ></td>
	    </tr>
	  <tr >
	    <td class="LetraNegra" align="rigth" >&nbsp;</td>
	    <td colspan="2" class="LetraNegra"><?php echo $row_prin_renta['Tic_Des']; ?></td>
	    <td colspan="2" class="LetraNegra">&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $row_prin_renta['Ret_Con']; ?></td>
	  </tr>
    </table>
  <tr valign="top">
    <td  valign="top"><table width="689" border="0" align="left" cellpadding="2" cellspacing="0" bordercolor="#000000">
	<tr>
	  <td height="179" colspan="5" valign="top" class="LetraNegra">
	  <?Php 
		$tarifa_0 = 0;
	    $tarifa_12 = 0;
		$Cop_Des = $row_prin_renta['Cop_Des'];		
		$observacion=$row_prin_renta['Cop_Obs'];	
	    $Eje_Fis=explode('-',$row_prin_renta['Ret_Fec']);
		?>
	    <table width="642" border="0" class="LetraNegra" >         		        
			 <tr align="center" >
            <td colspan="6"  ></td>
          <tr align="center" >
            <td colspan="6" height="22"  >&nbsp;</td>
            </tr>
		  <tr align="center" >
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr> 
		  <?Php $Total_Ret=0; 
		  foreach($rs_print_renta as $row_prin_renta){ ?>
          <tr align="center">
            <td width="70"><?Php $Ejerci=$Eje_Fis[0]; echo $Ejerci; unset($Ejerci);  ?></td>
            <td width="82"><?Php $Sri_Cod=$row_prin_renta['Ren_Sri']; echo substr($Sri_Cod,0,3);  ?></td>
            <td width="198"><?Php $Sri_Imp=$row_prin_renta['Ret_Imp']; echo $Sri_Imp;  ?></td>
            <td width="109"><?Php $Ren_Bas = number_format($row_prin_renta['Ret_Bas'], 2,'.',','); echo $Ren_Bas; ?></td>
            <td width="83"><?Php $Ren_Por= number_format($row_prin_renta['Ren_Por'], 2); echo $Ren_Por; ?></td>
            <td width="81"><div align="right">
			<?Php $Val_Ret=($row_prin_renta['Ret_Bas']*$row_prin_renta['Ren_Por'])/100; 
			       $Val_Ret=number_format($Val_Ret,2,'.',','); 
				   echo $Val_Ret;
				  $Total_Ret=$Total_Ret+$Val_Ret;  
		    ?>
            </div>
            </td>
          </tr>
		  <?php } ?>
          </table>		
          </td>
	  </tr>
	
	<tr>
	  <td height="6" colspan="5" class="LetraNegra"></td>
	  </tr>
	<tr>
	  <td width="190" height="24" class="LetraNegra">&nbsp;</td>
	  <td width="375" height="24" align="right">&nbsp;</td>
  	  <td width="64" height="24" class="LetraNegra" align="right"><?Php echo number_format ($Total_Ret, 2,'.',','); ?></td>	  
	  <td width="37" class="LetraNegra" align="right">&nbsp;</td>
	  <td width="3" class="LetraNegra" align="right">&nbsp;</td>
	</tr>
	
	
	</table>
	  
</table>
  </tr>
</table>
</body>
</html>
<?Php
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>