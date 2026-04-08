<?	

//require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_retencion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
*  Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Ret;

/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Ret;	 
		

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">
		<link href="../../css_teso.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>
        <script language="javascript" src="../VALIDACIONES/formexp.js"></script> 
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/lang/calendar-es.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar-setup.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY  >

<table width="100%" border="0"  cellpadding="0" cellspacing="0">
	 <tr class="Titulos1">
	  <td height="10" colspan="3"><p><span class="Titulos1">&raquo;</span> CONSULTAR RETENCI&Oacute;N</p>      </td>
	  </tr>
	<tr>
      <td height="389" valign="top">
		<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">
<?Php
  if($opf==1)
	{ 
		if($Chk_For==1) 
		{
			
			/**
			*  R E S U M E N  Consulta de totales de códigos de formularios por fecha de comprobante de compra 
			*/
			$rs_buscar=$obBD_con1->getArrayConsulta(548,$ini.'*'.$fin.'*'.'6'.'*'.$optest,$obBD_conexion);
        }//Fin del if($Chk_For==1) 
		else 
		{
 	        /**
			*  Consultar comprobantes de retención por fecha de comprobante de compra 
			*/
           	$rs_buscar = $obBD_con1->getArrayConsulta(543,$ini.'*'.$fin.'*'.'6'.'*'.$optest.'*'.$Ren_Cod,$obBD_conexion);
		}//Fin del else if($Chk_For==1) 
	}//Fin del if($opf==1)
	else
    {	
		if($Chk_For==1) 
		{ 
			/**
			*  Consulta de totales de códigos de formularios por fecha de comprobante de retención 
			*/
			$rs_buscar=$obBD_con1->getArrayConsulta(549,$ini.'*'.$fin.'*'.'6'.'*'.$optest,$obBD_conexion);
         }//Fin del if($Chk_For==1)
		 else 
		 { 
		 	$rs_buscar = $obBD_con1->getArrayConsulta(547,$ini.'*'.$fin.'*'.'6'.'*'.$optest.'*'.$Ren_Cod,$obBD_conexion);	
		 }//Fin del else if($Chk_For==1)
	 }//FIn del if($opf==1)		
	 $total_rs_buscar = count($rs_buscar);
	 $txt_busqueda=$total_rs_buscar;


if($total_rs_buscar != 0)
{
	 if($op==2) 
	 { 
	  ?>  
	  <br>
	  <table width="528" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="55" class="Etiquetas"><div align="left">Desde:</div></td>
		  <td width="162" class="LetraNegra"><?Php echo $ini?></td>
		  <td width="79" class="Etiquetas">Hasta:</td>
		  <td width="230" class="LetraNegra">&nbsp;<?Php echo $fin?></td>
		</tr>
	  </table>
	  
	  <table width="528" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="170" class="Etiquetas"><div align="left">Comprobantes de retención: </div></td>
		  <td width="52" align="left" class="LetraNegra">
		  <?php  
		  if ($optest == "A")
		   { 
			 echo 'Activas'; 
		   } else {
			 echo 'Anuladas';
		   } 
		   ?>     
          </td>
		  <td width="177" class="Etiquetas">
		  <?Php 
		  	if($Ren_Por!="")
			{ 
				echo 'Porcentaje de retención:';
			}  
			if(!empty($Ren_Cod))
			{ 
				echo 'Código de formulario:';
			}?>
          </td>
		  <td width="129" class="LetraNegra">&nbsp;&nbsp;<?Php if(($Ren_Por!="")) { if ($Ren_Por == "T"){ echo "Todos los %"; }
				else{ echo $Ren_Por.'%'; }}  if(!empty($Ren_Cod)) { echo $row_rs_buscar['Ren_Sri']; }  ?>  </td>
		</tr>
	  </table>  
	  <?Php
  }

if(!isset($Chk_For)) 
{ ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera">
	    <td width="29">&nbsp;</td>
	    <td width="40" align="center">Nro. Int. </td>
	    <td width="40" align="center">No. Ret. </td>
		   <td width="80" align="center">No. Fac. </td>	
           <td width="69" align="center">Fecha</td>
           <td width="170" align="center">Apellidos</td>
         		  
		  <td width="170" align="center">Nombres</td>
		  <td width="60" align="center">Base imp </td>
		  <td width="60" align="center">Valor</td>
		  <td width="20">&nbsp;</td>
      </tr>
	  <?Php
	   $itemr=1;
       $total_base=0;
	   $Suma_Val_Ret=0; 
	   $i=0;
   	   foreach($rs_buscar as $row_rs_buscar) { 
	   $i++;
	   ?>
	  <tr class="Fondo">
	    <td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onClick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onClick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"></td>
	    <td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Num_Int_Com= $row_rs_buscar['Ret_Cod']; echo $Num_Int_Com;  ?></td>
	    <td height="25" align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php  $Ret_Com=$row_rs_buscar['Ret_Num']; echo $Ret_Com; ?></td>
		<td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Cop_Num=$row_rs_buscar['Cop_Num']; echo $Cop_Num; ?></td>	
		<td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?Php 
		$Ret_Fec_Sav=$row_rs_buscar['Ret_Fec']; echo $Ret_Fec_Sav; ?></td>
		<td align="left" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
			
		<td align="left" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php echo $row_rs_buscar['Prs_Nom'];  if(empty($row_rs_buscar['Prs_Nom'])){ echo "&nbsp"; }   ?></td>
		<?Php /** Base de retención de las facturas *****************/ 
       if(isset($hdd)){
	    /* Control para la opcion 2 */
        if(!isset($bcheck))
		{
			if(!($Ren_Por=="T"))
			{
				/* ojojojojojo */
                $rs_base_impuesto=$obBD_con1->getArrayConsulta(515,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Por,$obBD_conexion);
 				$rs_base_rentas= $obBD_con1->getArrayConsulta(515,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Por,$obBD_conexion);
		   }//Fin del if(!empty($Ren_Por)){
		   else 
		   { 			
			   $rs_base_impuesto=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);
  		       $rs_base_rentas=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);        
			}//Fin del Else del if(!empty($Ren_Por)){
		}//Fin del if(!isset($bcheck)){
		else
		{
		     $rs_base_impuesto=$obBD_con1->getArrayConsulta(544,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Cod,$obBD_conexion);
			 $rs_base_rentas=$obBD_con1->getArrayConsulta(544,$row_rs_buscar['Ret_Cod'].'*'.$Ren_Cod,$obBD_conexion);
		}//Fin del else if(!isset($bcheck)){
}//Fin del if(!isset($Chk_For))    

if($op==1)
{ 
	 $rs_base_impuesto=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);
	 $rs_base_rentas=$obBD_con1->getArrayConsulta(500,$row_rs_buscar['Ret_Cod'],$obBD_conexion);		 
}
		/* Aqui no usar el $obBD_con1->registros() */
		/*******************************************/
    	$row_base_impu=mysqli_fetch_assoc($rs_base_impuesto);
	    $row_renta=mysqli_fetch_assoc($rs_base_rentas);
		/*******************************************/		
		?>
		<td align="right" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>		
		<?Php 			
		 foreach($rs_base_rentas as $row_rs_base_rentas){     
			 $base_impo=$base_impo+$row_rs_base_rentas['Ret_Bas'];
		 }
		    echo number_format($base_impo,2);
			unset($base_impo);
		?>		
        </td>
		<td align="right" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?php
		
		if(!($Ren_Por=="T"))
		{
           	$total=0;
			foreach($rs_base_impuesto as $row_rs_base_impuesto){
		    	$total_base=$total_base+$row_rs_base_impuesto['Ret_Bas'];
				 $total=$total+number_format(($row_rs_base_impuesto['Ret_Bas']*$row_rs_base_impuesto['Ren_Por'])/100,2,".","");
		 	}
		}//Fin del if(!empty($Ren_Por))
		else
		{
			$total=0;
		  	foreach($rs_base_impuesto as $row_rs_base_impuesto){
			$total_base=$total_base+$row_rs_base_impuesto['Ret_Bas'];
		    	 $total=$total+number_format((($row_rs_base_impuesto['Ret_Bas']*$row_rs_base_impuesto['Ren_Por'])/100),2,".","");
		 	}
		}//Fin del else if(!empty($Ren_Por))
		$Imp_Com= number_format($total, 2,'.','');
		echo $Imp_Com;
		$Suma_Val_Ret=$Suma_Val_Ret+$Imp_Com;
		unset($Imp_Com);
		?>
        </td>
		<td align="center">
		<a href="<?Php echo $_POST['form1'];?>?Ret_Cod=<?Php echo $row_rs_buscar['Ret_Cod'];?>" 
		title="Mostra"><img src="../../imagenes/vista.jpg" width="20" height="20" border="0">        </a>		    </td>
	  </tr>
	  <tr class="Fondo" id="detalle[<?Php echo $i; ?>]">
			<?php
			$row_rs_detalle = $obBD_con1->getRowConsulta(182,$row_rs_buscar['Ret_Cod'],$obBD_conexion);						
			$total_rs_detalle=$row_rs_detalle['Ren_Cod'] > 0? 1 : 0;			
			?>			  	  
	    <td align="center">&nbsp;</td>
	    <td colspan="8"><table width="100%" border="0" cellspacing="0" cellpadding="0" class="LetraNegra">
          <tr>
            <td width="10%" align="center">C&oacute;d. Imp. </td>
            <td width="37%" align="center">Descripci&oacute;n</td>
            <td width="15%" align="center">Base</td>
            <td width="14%" align="center">% Retenci&oacute;n </td>
            <td width="14%" align="center">Valor Retenido </td>
            <td width="10%" align="center">% Iva </td>
          </tr>
          <tr>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Sri']; ?></td>
            <td><?Php echo $row_rs_detalle['Ren_Con']; ?></td>
            <td align="right"><?Php echo number_format($row_rs_detalle['Ret_Bas'],2,".",""); ?></td>
            <td align="center"><?Php echo $row_rs_detalle['Ren_Por']; ?></td>
            <td align="right"><?Php echo number_format($row_rs_detalle['Val_Ret'],2,".",""); ?></td>
            <td align="right"><?Php echo $row_rs_detalle['Iva_Por']; ?></td>
          </tr>
        </table></td>
	    <td align="center">&nbsp;</td>
	    </tr>
		<!-- Control para ocultar las filas de detalle -->
		<script language="javascript">
		ShowHide('detalle[<?Php echo $i; ?>]');
		ShowHide('menos[<?Php echo $i; ?>]');		 
		 </script>		
	    <?Php $itemr++; } ?>
  </table>
</FIELDSET>
<?Php 
	echo barra_estado($total_rs_buscar);
} else {  

?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
<table  border="1" cellpadding="0" cellspacing="0">
  <tr class="Cabecera">
    <td width="29" align="center">Item</td>
    <td width="77" align="center" >C&oacute;d. Imp. </td>
    <td width="80" align="center">% de Reten. </td>
    <td width="126" align="center">Can. compr. compra </td>
    <td width="88" align="center">Base imp. </td>
    <td width="111" align="center">Valor retenido </td>
    </tr>
  <?Php  $itemr=1; $Suma_Val_Ret=0; $total_base=0; 
  foreach($rs_buscar as $row_rs_buscar) { 
  	/* Consulta el detalle de las retenciones para sumarlas una a una, debido a descuadres de decimales */	
	$rs_det_renta = $obBD_con1->getArrayConsulta(183,'6'.'*'.$ini.'*'.$fin.'*'.$row_rs_buscar['Ren_Cod'].'*'.$optest,$obBD_conexion);	

	$Tot_Ret=0;		
	$Tot_Bas = 0;
	foreach($rs_det_renta as $row_rs_det_renta){
		$Tot_Bas = $Tot_Bas + $row_rs_det_renta['Total']; 
		$Tot_Ret=$Tot_Ret + round($row_rs_det_renta['Renta'],2); 
	}
?>
  <tr class="Fondo">
    <td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?Php echo $itemr; ?></td>
    <td height="25" align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?Php $Ren_Sri=$row_rs_buscar['Ren_Sri']; echo $Ren_Sri; unset($Ren_Sri);  ?></td>
    <td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Por_Sri=$row_rs_buscar['Ren_Por']; echo $Por_Sri;   unset($Por_Sri);  ?>	</td>
    <td align="center" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Num_Cop=$row_rs_buscar['Num_Cop']; echo $Num_Cop;   unset($Num_Cop);  ?>	</td>
    <td align="right" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>  >
		<?Php 	
		$total_base= $total_base + $Tot_Bas;
		$Base_Tot=number_format($Tot_Bas,2); 
		echo $Base_Tot; 
		//$total_base=$total_base+number_format($Tot_Bas,2,'.','');     ?>	</td>
    <td align="right" <?Php if ($row_rs_buscar['Ret_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>  >
	<?Php 
		echo number_format($Tot_Ret,2,".",""); 
		$Suma_Val_Ret=$Suma_Val_Ret+$Tot_Ret;   
		unset($Base_Tot); unset($Tot_Bas); unset($Tot_Ret);    ?></td>
    </tr>
  <?Php $itemr++; }?>	
  <tr class="LetraNegra">
    <td height="25" colspan="4" class="Etiquetas">      Totales:</td>
    <td align="right"><?Php echo number_format($total_base,2,".","");  ?></td>
    <td align="right"><?Php echo number_format($Suma_Val_Ret,2,".",""); ?></td>
  </tr>
</table>
<br>
<?php
/**
*  Consulta del total de retenciones emitidas RENTA 
*/
$rs_num_renta = $obBD_con1->getArrayConsulta(184,'6'.'*'.$optest.'*'.$ini.'*'.$fin.'*'.'R',$obBD_conexion);
$total_rs_num_renta = count($rs_num_renta);

/**
*  Consulta del total de retenciones emitidas IVA 
*/
$rs_num_iva = $obBD_con1->getArrayConsulta(184,'6'.'*'.$optest.'*'.$ini.'*'.$fin.'*'.'I',$obBD_conexion);
$total_rs_num_iva = count($rs_num_iva);
?>
<table width="511" border="0" cellspacing="0" cellpadding="0" class="LetraNegra">
  <tr>
    <td width="30"><strong><?Php echo $total_rs_num_renta; ?></strong></td>
    <td width="461"><?Php echo " Comprobantes de Impuesto a la Renta"; ?></td>
  </tr>
  <tr>
    <td width="30"><strong><?Php echo $total_rs_num_iva; ?></strong></td>
    <td width="461"><?Php echo " Comprobantes de Iva"; ?></td>
  </tr>
</table>
</FIELDSET>
<?Php  
} 
?>

<?Php 
	if($op!=1 && !isset($Chk_For))
	{ ?>
<br>
  <table width="786"  border="0">
    <tr>
      <td width="82" height="25" align="center" >&nbsp;</td>
      <td width="545" align="center" class="Etiquetas" >TOTALES: </td>
      <td width="59" align="right" class="LetraNegra" ><?Php echo number_format($total_base,2,".","");  ?></td>
      <td width="58" align="right" class="LetraNegra" ><?Php echo number_format($Suma_Val_Ret,2,".",""); ?></td>
      <td width="20" align="right" class="LetraNegra" >&nbsp;</td>
    </tr>
  </table>
  <?Php 
  	}//Fin del if($op!=1 && !isset($Chk_For))
	}
?>
</form>
</div>	  
</td>
</tr>
</table>	  
</BODY></HTML>
<?Php
/* liberar conexiones en la BD */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
