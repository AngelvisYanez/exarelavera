<?php	require_once('../../administrador/LOGICA/seguridad.php');
	require_once('../LOGICA/logica.php');
    require_once('../../Librerias/procedimientos/almacenados_standar.php');	
/** Actualizacion de datos de la retención ********************************************************/

if(isset($hdd_save))
{
    /** Inicio de la transacción *************/
	  
	   	$conexion=open_trans_tes();
		
		insercionesv_tes(502,$Ret_Cod.'*'.$Ret_Fec.'*'.trim($Ret_Con).'*'.$Ret_Num,$conexion);
		$cont=0;	
		$Cod_Upd= array();
		foreach($datos as $puntero => $item){
		$cont++;
		   $param[]=$item;
		   if($cont==8)
		   {  
		      array_push($Cod_Upd,$param[7]);
			  $cont=0;
			  unset($param);	
		   }
		}
		$cont=0;	
		foreach($Ret_Int_Com as $Int_Ret)
    	{
		 if(in_array($Int_Ret,$Cod_Upd))
		 {
		foreach($datos as $puntero => $item)
		{
		   $cont++;
		   $param[]=$item;
		   if($cont==8)
		   {     
			      $Ret=substr($param[2],0,1);
				  insercionesv_tes(503,$param[7].'*'.$Ret.'*'.$param[3].'*'.$param[6],$conexion);
				  unset($Ret);
                  $cont=0;
			      unset($param);			 
			 }			
		} }else	 {
						    insercionesv_tes(524,$Int_Ret, $conexion);
						 }}
		close_trans_tes($conexion);
		
	    /** Fin de la transaccion **********************************************************************************************************/
}


/** Cargado de los porcentajes d retención *******************************************************************************************/
if(isset($Ret_Cod))
{
	/** Consulta los datos de la retención a modificar ***********************************************/
	$rs_inf_retencion=consultas_tes(501,$Ret_Cod);
	$row_car_detalle=mysqli_fetch_assoc($rs_inf_retencion);	
}

/*******************Busqueda del codigo de las facturas ******************/

if (!(isset($op)))
{
	$op = 1;
}	    


/** Cargado de Iva Renta através de AJAX ********************/	
   
	if (isset($codigoret))
 {
		$rs_xml = consultas_tes(490,$codigoret);
		$row_rs_xml = mysqli_fetch_assoc($rs_xml);
		$total_rs_xml = mysqli_num_rows($rs_xml);
		
		if ($total_rs_xml > 0)
		{
			$codigoret=$row_rs_xml['Ren_Sri'];
			$porce_renta_sri=$row_rs_xml['Ren_Por'];
			$codinter=$row_rs_xml['Ren_Cod'];
		} else {
			$codigoret=0;
			$porce_renta_sri="NO EXISTE";
			$codinter="0";
			
		}		
	if (isset($porce_renta_sri)){
$return_value='<?xml version="1.0" standalone="yes"?><root><hijo>'.$porce_renta_sri.'</hijo><hijo>'.$codinter.'</hijo></root>';
	}
	header('Content-Type: text/xml'); 
	echo $return_value;
	exit();
}

/*****************OPCIONES*********************************************************************************************/


switch ($op){
	case 1: 
	/* Cargado de los datos de la cabecera */
	if ($txt_busqueda != "" && (!isset($Cop_Cod)))
	{
	    if ($op_opciones == "d")
		{
			$rs_buscar = consultas_tes(499, $txt_busqueda.'*'.'6');
		}
		else 
		{
			$rs_buscar = consultas_tes(504, $txt_busqueda.'*'.'6');
		}  
		unset($Ret_Cod);
		$row_rs_buscar = mysqli_fetch_assoc($rs_buscar);
  	    $total_rs_buscar = mysqli_num_rows($rs_buscar); 
	}
	
	
	 
	break;
}//FIn del case $op
?>
<HTML>
	<HEAD>
		<TITLE>Ginus</TITLE>
		<link href="../../Estilos/Estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/interfaz.css" rel="stylesheet" type="text/css">
		<link href="../../mascaras/model1/estilos/estilo1.css" rel="stylesheet" type="text/css">
		<link href="../../Estilos/Interfaz1.css" rel="stylesheet" type="text/css">	
	<link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/Validaciones.js"></script>	
		<script language="javascript" src="../../contabilidad/VALIDACIONES/Validaciones.js"></script>
		<script language="javascript" src="../../contabilidad/VALIDACIONES/XML.js"></script>
		<script language="javascript" src="../VALIDACIONES/XML.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/lang/calendar-es.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar-setup.js"></script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<?Php
if(isset($hdd_save)){

?>
<script language="javascript">
 ir('<?Php echo $_SERVER['PHP_SELF']; ?>');
</script>

<?Php
}

?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	 <tr class="BarraTitulo">
	  <td height="10" colspan="3"><p><span class="BarraTitulo">&raquo;</span> modificaci&oacute;n RETENCI&Oacute;N</p>      </td>
	  <td>&nbsp;</td>
    </tr>
	<tr>
	<tr>
      <td height="18" align="left" valign="top">
      
  <tr>
        <td height="389" valign="top">
		<form name="form1" method="post" action="<?Php $_SERVER['PHP_SELF']?>">
		
		<?Php
switch ($op){
	case 1: 
	
?><FIELDSET>
<legend>
		<label class="Titulos2">Buscar por:</label></legend>
<table width="495" border="0">
    <tr>
      <td width="242"><input name="op_opciones" type="radio" value="r" checked>
        <span class="LetraNegra">Apellidos </span></td>
      <td width="242"><input name="op_opciones" type="radio" value="d">
        <span class="LetraNegra">No. Comprob. de retenci&oacute;n </span></td>
      
    </tr>
  </table>

  <table width="495" height="36" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="90" height="28" class="BarraBusqueda"><div align="right"><span class="Asterisco">* </span>Búsqueda:</div></td>
      <td width="371" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase "></td>
      <td width="113"><div align="center">
        <input name="btn_buscar" type="button" class="Boton_Buscar" title="Buscar" id="btn_buscar" onClick="validar_buscar()" value="Buscar">
      </div></td>
    </tr>
  </table>
</FIELDSET>
  <?Php
  	if(isset($txt_busqueda))
	{
		if($total_rs_buscar != 0)
		{
  ?>
  <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="599"  border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="48" height="16">Nro. Int </td>
	    <td width="53">No. Com </td>
		   <td width="51">No. Fact</td>	
           <td width="65">Fecha</td>
           <td width="145" align="center">Apellidos</td>
         		  
		  <td width="145" align="center">Nombres</td>
		  <td width="50" align="center">Valor  </td>
		  <td width="24">&nbsp;</td>
      </tr>
	  <?Php do { ?>
	  <tr class="Fondo">
	    <td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php  $Ret_Int_Aut=$row_rs_buscar['Ret_Cod']; echo $Ret_Int_Aut; ?></td>
	    <td height="25" align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php  $Ret_Com=$row_rs_buscar['Ret_Num']; echo $Ret_Com; ?></td>
		<td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Cop_Num=$row_rs_buscar['Cop_Num']; echo $Cop_Num; ?></td>	
		<td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php $Ret_Fec=$row_rs_buscar['Ret_Fec']; echo $Ret_Fec; ?></td>
		<td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php echo $row_rs_buscar['Prs_Ape']; ?></td>
			
		<td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>
		<?Php echo $row_rs_buscar['Prs_Nom']; ?></td>
		<td align="center" <?Php if ($row_rs_buscar['Cop_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php
          $rs_base_impuesto=consultas_tes(500, $row_rs_buscar['Ret_Cod']);
		  $row_base_impu=mysqli_fetch_assoc($rs_base_impuesto);
		  $total=0;
		  do{
		     $total=$total+(($row_base_impu['Ret_Bas']*$row_base_impu['Ren_Por'])/100);
		  }while($row_base_impu=mysqli_fetch_assoc($rs_base_impuesto));
		  $Imp_Com= number_format($total, 2,'.',','); echo $Imp_Com;
		  unset($Imp_Com);
		   ?></td>
		<td align="center">
		<a href="<?Php echo $_POST['form1'];?>?Ret_Cod=<?Php echo $row_rs_buscar['Ret_Cod'];?>" 
		title="Editar"><img src="../../imagenes/editar.jpg" width="20" height="20" border="0">        </a>		    </td>
	  </tr>
	  <?Php } while ($row_rs_buscar = mysqli_fetch_assoc($rs_buscar)); ?>
  </table>
  
</FIELDSET>

  <?Php 
  	}
	else
	{
  		echo error_alerta("No hay resultados que mostrar", 2);
	}
  ?>
  <?Php
	}
	break;
	}

	?>
</form>
<?Php if(isset($Ret_Cod) && empty($txt_busqueda))
		{  ?>
<form action="<?Php  $_SERVER['form1']; ?>" method="post" name="form2" id="form2">

 <FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Proveedor </label>
</LEGEND>
<table width="85%" border="0">
  <tr>
    <td width="965"></td>
  </tr>
</table>
<table width="561" border="0">
  <tr>
    <td width="57" class="Etiqueta1">Cédula:</td>
    <td width="99" class="LetraNegra"><?Php echo $row_car_detalle['Prs_Ced'] ?></td>
	<td width="55" class="Etiqueta1">Nombres:</td>
    <td width="110" class="LetraNegra"><?Php echo $row_car_detalle['Prs_Nom'] ?></td>
    <td width="76" class="Etiqueta1">Apellidos: </td>
    <td width="126" class="LetraNegra"><?Php echo $row_car_detalle['Prs_Ape'] ?></td>
  </tr>
  <tr>
    <td width="57" class="Etiqueta1">Dirección:</td>
    <td colspan="3" class="LetraNegra"><?php echo $row_car_detalle['Prs_Dir']?></td>
	</tr>
</table>
</FIELDSET>
<br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos de la Retención </label>
</LEGEND>
 <?Php //echo mensaje_requerido(); ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Generales </label>
</LEGEND>
  <table width="559"  border="0">
  <tr>
  <td width="117"  class="Etiqueta1">No. Com. Retenci&oacute;n: </td>
  <td width="56" class="LetraNegra">
<input name="Ret_Num" type="text" id="Ret_Num" size="7" onBlur="numerico(this)" value="<?Php $Ret_Num=$row_car_detalle['Ret_Num']; echo $Ret_Num;   ?>"></td>
  <td width="110" class="Etiqueta1">Fecha del Comp.: </td>
   <td width="123" class="LetraNegra"><input name="Ret_Fec" type="text" id="Ret_Fec" value="<?php echo $row_car_detalle['Ret_Fec']; ?>" size="10" readonly="true">
     <img src="../../imagenes/calendario.jpg" alt="Ver calendario" name="calendario" width= "25" height="17" border="0" align="absmiddle" id="calendario">
     <script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "Ret_Fec",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendario",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script></td>
   <td width="70" class="Etiqueta1">No. Fact :</td>
   <td width="57" class="LetraNegra"><?Php  $Num_Fac=$row_car_detalle['Cop_Num']; echo $Num_Fac; ?>
     <input name="Ret_Cod" type="hidden" id="Ret_Cod" value="<?Php echo $row_car_detalle['Ret_Cod']; ?> "></td>
  </tr>
</table>
    <table width="559"  border="0">
      <tr>
        <td width="112" class="Etiqueta1"> Fecha  Emi. Comp:</td>
        <td width="73"><span class="LetraNegra">
          <?Php  $Fec=$row_car_detalle['Cop_Fec']; echo $Fec; ?>
        </span></td>
        <td width="116" class="Etiqueta1">Feha de Cad. Comp:</td>
        <td width="240" class="LetraNegra"><?Php  $Fcad=$row_car_detalle['Cop_Cad'];
		 if($Fcad==NULL || empty($Fcad))
		 {
		 /** Consultar la fecha de caducidad para la autorizacion del bloque de liquidaciones de compra *************************************/
		 $rs_fec_aut_liq_com=consultas_tes(523,$row_car_detalle['Aut_Cod']);
		 $row_fec_aut_liq=mysqli_fetch_assoc($rs_fec_aut_liq_com);
		 echo $row_fec_aut_liq['Aut_Cad'];
		
		 }else{ echo $Fcad; } 
		 ?></td>
        </tr>
      <tr>
        <td class="Etiqueta1">Tipo Compr:</td>
        <td colspan="3"><span class="LetraNegra">
          <?Php  $Tic=$row_car_detalle['Tic_Des']; echo $Tic; ?>
        </span></td>
        </tr>
    </table>
    <table width="559" height="41" border="0">
    
    <tr>
      <td width="116"  class="Etiqueta1">Por concepto de: </td>
      <td width="433" colspan="5">
	 <textarea name="Ret_Con" style="text-transform:uppercase" cols="50" id="textarea"><?Php echo $row_car_detalle['Ret_Con']; ?></textarea></td>
    </tr>
  </table>
</FIELDSET>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la retención </label>
</LEGEND>
  <table width="539" border="0">
	<tbody id="c_contenido">
	<tr>
		
		<td width="59" class="Cabecera1">Eje/Fiscal</td>
		<td width="72" class="Cabecera1">C&oacute;d. imp. </td>
		
		<td width="118" class="Cabecera1">Impuesto</td>
		<td width="94" class="Cabecera1">Base  </td>
		<td width="74" class="Cabecera1">% de reten </td>
		<td width="96" class="Cabecera1">Valor retenido</td>
	</tr>
	</tbody>
	</table>
  <table width="539" border="0">
	<tbody id="c_contenido">
	<?Php 
	/* Año fiscal actual */	
	
	$total_retenido=0;
	$ic=1;
	
	do
	{
		
		$fila++;
	$Fecha=explode('-',$row_car_detalle['Ret_Fec']);
	?>
	<input name="Ret_Int_Com[<?Php echo $ic; ?>]" id="Ret_Int_Com[<?Php echo $ic; ?>]" type="hidden" 
	value="<?Php echo $row_car_detalle['Ret_Int']; ?>">
	<tr>
	 
	  <td width="58" align="left" class="LetraNegra">
<input name="datos[<?Php echo $fila; ?>,1]" type="text" id="datos[<?Php echo $fila; ?>,1]" 
value="<?Php echo $Fecha[0];?>" size="5" maxlength="6" ></td>
		 <td width="74" align="right" class="LetraNegra"><div align="center">
	    <input name="datos[<?Php echo $fila; ?>,2]" type="text" id="datos[<?Php echo $fila; ?>,2]" 
		value="<?Php echo $row_car_detalle['Ren_Sri'];  ?>" onKeyUp="ajax_xml_renta_iva('tes_mod_retencion.php?codigoret=' + escape(this.value), this)" style="text-transform:uppercase" size="8" maxlength="8" >
	    </div></td>
	 
	  <td width="117" align="right" class="LetraNegra"><div align="center">
	    <input name="datos[<?Php echo $fila; ?>,3]" type="text" readonly="true" id="datos[<?Php 
	  					echo $fila; ?>,3]" value="<?Php echo $row_car_detalle['Ret_Imp']; ?>" size="15" maxlength="8" >
	    </div></td>    <td width="94" class="LetraNegra"><div align="center">
	    <input name="datos[<?Php echo $fila; ?>,4]" type="text" id="datos[<?Php echo $fila; ?>,4]" 
		value="<?Php echo number_format($row_car_detalle['Ret_Bas'],2,'.',''); ?>" size="12" maxlength="24" class="LetraNegra" readonly="true">
	    </div></td>
	
	  <td width="74" align="right" class="LetraNegra"><div align="center">
	    <input name="datos[<?Php echo $fila; ?>,5]" type="text" id="datos[<?Php 
	  					echo $fila; ?>,5]" readonly="true" size="8" maxlength="20" value="<?Php echo $row_car_detalle['Ren_Por'];  ?>" 
						 onBlur="numerico(this)" >
	    </div></td>
	  <td width="96" align="right" class="LetraNegra"><div align="center">
	    <input name="datos[<?Php echo $fila; ?>,6]" type="text" id="datos[<?Php 
	  					echo $fila; ?>,6]" value="<?Php echo ($row_car_detalle['Ret_Bas']*$row_car_detalle['Ren_Por'])/100; $total_retenido=$total_retenido+(($row_car_detalle['Ret_Bas']*$row_car_detalle['Ren_Por'])/100); ?>" size="12" maxlength="34" readonly="true">
	    
	    <input name="datos[<?Php echo $fila; ?>,7]" type="hidden" id="datos[<?Php	echo $fila; ?>,7]" 
		value="<?Php echo $row_car_detalle['Ren_Cod'];  ?>">
	    <input name="datos[<?Php echo $fila; ?>,8]" type="hidden" id="datos[<?Php	echo $fila; ?>,8]" 
		value="<?Php echo $row_car_detalle['Ret_Int'];  ?>">
	  </div></td>
	  <td width="96" align="right" class="LetraNegra"> <?Php  if($row_car_detalle['Ret_Imp']=='IVA') {  ?>
	    <input id="quitar_fila" type="button" class="BotonEliminar" name="quitar_fila" value="X" onClick="quitar_fila_retencion(this)">
	  <?Php  } ?></td>
	</tr>
	<?Php 
	$ic++;
	} while($row_car_detalle=mysqli_fetch_assoc($rs_inf_retencion));

		
	?>
	</tbody>
	<tr>
	  <td colspan="5" class="Etiqueta1">TOTAL RETENIDO :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	    <input id="nfilas" name="nfilas" type="hidden" value="<?Php echo $fila; ?>">	    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
	  <td class="LetraNegra"><div align="center">
	    <input name="t_subtotal" type="text" align="left" id="t_subtotal" size="12" maxlength="8" readonly="true" 
		value="<?Php echo number_format($total_retenido,2,'.',',');  ?>">
	    </div></td>
		<td class="LetraNegra">&nbsp;</td>
	</tr>
	</table>
	</fieldset>
		<br>
	<LEGEND>
	<label class="Titulos2"></label>
	</LEGEND>
	</FIELDSET>
	<br><br>
 <table width="339" border="0" cellpadding="0" cellspacing="0" class="Azul">
 <tr>
 <td width="28%" height="23"><font color="#3162a6" face                  ="Arial, Helvetica, sans-serif">
   <input name="btn_guardar" type="button" class="Boton_Guardar" id= "btn_guardar" title= "Guardar" onClick= "validar_retencion(this.form)" value="Actualizar">
 </font>
     <input name="hdd_save" type="hidden" id="hdd_save" value="insertar"> </td>
 </tr>
 </table>
 

 </form>
<?Php } ?>


 
</td>
</tr>
</table>	  
</BODY></HTML>
<?Php
@mysqli_free_result($rs_base_impuesto);
@mysqli_free_result($rs_inf_retencion);
@mysqli_free_result($rs_xml);
?>
