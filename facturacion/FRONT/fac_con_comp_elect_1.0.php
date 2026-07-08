<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripción:Consuta RIDE comprobantes elctronicos.
* Fecha de actualización:	09-06-2015 
* Desarrollador:	Jose Cumbicos

*/	  

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_comp_elect.php');  	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;	 	 	 
/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");


if($Tic_Cod=='01' or $Tic_Cod=='4')
{    
	$numero=explode("-",$NumDoc);	
	if($op_opciones=='d') //Buscar por numero d docuemento
	{
		if($Tic_Cod=='01')//Factura
		{ 
		  $row_rs_datos = $obBD_con1->getArrayConsulta(1, (int)$numero[2].'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	
		  $url_pdf='../COMPONENTES/tesPdfFacturaElectronica_1.0.php';	
		}
		if($Tic_Cod=='04')//Nota de Credito
		{ 
		  $row_rs_datos = $obBD_con1->getArrayConsulta(1, (int)$numero[2].'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	 	
		  $url_pdf='../COMPONENTES/tesPdfNotasCreditoElectronica_1.0.php';		
		}
	}
	if($op_opciones=='r') //Buscar entre fechas
	{
		if($Tic_Cod=='01') //Factura
		{ 
		  $row_rs_datos = $obBD_con1->getArrayConsulta(3, $Fec_Ini.'*'.$Fec_Fin.'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	
		  $url_pdf='../COMPONENTES/tesPdfFacturaElectronica_1.0.php';	
		}
		if($Tic_Cod=='04') //Nota de Credito
		{ 
		  $row_rs_datos = $obBD_con1->getArrayConsulta(3, $Fec_Ini.'*'.$Fec_Fin.'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	 	
		  $url_pdf='../COMPONENTES/tesPdfNotasCreditoElectronica_1.0.php';		
		}
	}
	if($op_opciones=='c') //Buscar facturas consumidor final
	{		
		if($Tic_Cod=='01') //Factura
		{ 
		  $row_rs_datos = $obBD_con1->getArrayConsulta(6, (int)$numero[2].'*'.$Ses_Suc_Cod.'*'.$Tic_Cod.'*'.$Ses_Prs_Cod.'*4', $obBD_conexion);	
		  $url_pdf='../COMPONENTES/tesPdfFacturaElectronica_1.0.php';	
		}
	}
	
}

if($Tic_Cod=='07')//Retencion
{  
	$numero=explode("-",$NumDoc);
	if($op_opciones=='d') //Buscar por numero d docuemento
	{
		$row_rs_datos = $obBD_con1->getArrayConsulta(4, (int)$numero[2].'*'.$Tic_Cod.'*'.$Ses_Suc_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);					
	}
	if($op_opciones=='r') //Buscar entre fechas
	{
		$row_rs_datos = $obBD_con1->getArrayConsulta(5, $Fec_Ini.'*'.$Fec_Fin.'*'.$Tic_Cod.'*'.$Ses_Suc_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	
	}
	$url_pdf='../COMPONENTES/tesPdfRetencionElectronica_1.0.php';
}

if($Tic_Cod=='06')//Guia Remision
{  
	$numero=explode("-",$NumDoc);
	if($op_opciones=='d') //Buscar por numero d docuemento
	{
		$row_rs_datos = $obBD_con1->getArrayConsulta(7, (int)$numero[2].'*'.$Tic_Cod.'*'.$Ses_Suc_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);					
	}
	if($op_opciones=='r') //Buscar entre fechas
	{
		$row_rs_datos = $obBD_con1->getArrayConsulta(8, $Fec_Ini.'*'.$Fec_Fin.'*'.$Tic_Cod.'*'.$Ses_Suc_Cod.'*'.$Ses_Prs_Cod, $obBD_conexion);	
	}
	$url_pdf='../COMPONENTES/tesPdfGuiaRemisionElectronica_1.0.php';
}

$total_datos=count($row_rs_datos);

?>
<HTML><HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		 <?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>  
        <script language="javascript" src="../VALIDACIONES/fac_val_comp_elect.js"></script>  	   		          
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
		<script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>         	                                   
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>
        <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});  
		</script>        
        <script type="text/javascript"> 				
		function downloadURI(directorio,file) 
		{   
			var link = document.createElement("a");						
			link.download = file + '.xml';						
			link.href = directorio +"/"+ file+ '.xml';						
			link.click();
		}
		
		$(function() {
		$( "#Fec_Ini" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});
		$( "#Fec_Fin" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});
		});				       
		/**
		* Control de mascaras
		*/
		jQuery(function($){
			$("#NumDoc").mask("999-999-999999999",{placeholder:"_"});			
		});	      			
		</script>

		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; Consultar Comprobantes Electr&oacute;nicos</td>
</tr>
<tr>
 <td align="left" valign="top" height="400">
 <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form1" id="form1">
 <FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
      <?php  mensaje_requerido(); ?>
      <table width="604" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="116" class="Etiqueta1"><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
        <td width="488">            
        <select name="Tic_Cod" id="Tic_Cod" onchange="if(this.value==4 || this.value==6 || this.value==7){document.getElementById('td_consumidor').className = 'oculta'}else{document.getElementById('td_consumidor').className = 'muestra'}" >
            <option value="" selected="selected">Seleccione...</option>       
          	<option value="01" <?php if($Tic_Cod=='01'){ echo "selected";}?>>&raquo; FACTURA</option>
            <option value="07" <?php if($Tic_Cod=='07'){ echo "selected";}?>>&raquo; COMPROBANTE DE RETENCI&Oacute;N</option>
            <option value="04" <?php if($Tic_Cod=='04'){ echo "selected";}?>>&raquo; NOTA DE CR&Eacute;DITO</option>   
            <option value="06" <?php if($Tic_Cod=='06'){ echo "selected";}?>>&raquo; GU&Iacute;A DE REMISI&Oacute;N</option>   
        </select>        
        </td>
      </tr>
    </table>  
</FIELDSET>
<FIELDSET>
<LEGEND>
    <label class="Titulos2">Buscar  por:</label>
</LEGEND>
<table width="462" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="173"><input name="op_opciones" id="op_opciones1" type="radio" onClick="document.getElementById('tr_numero').className = 'muestra';document.getElementById('tr_fechas').className = 'oculta'" value="d" checked="checked">
        <span class="Etiqueta1">Nro. de Comprobante</span></td>
    <td width="147"><input type="radio" name="op_opciones" id="op_opciones2" value="r" onClick="document.getElementById('tr_fechas').className = 'muestra';document.getElementById('tr_numero').className = 'oculta'">
      <span class="Etiqueta1">Rango de Fechas </span></td>
    <td id="td_consumidor" width="142" class="LetraNegra"><input type="radio" name="op_opciones" id="op_opciones3" value="c" onclick="document.getElementById('tr_numero').className = 'muestra';document.getElementById('tr_fechas').className = 'oculta'" />
      <span class="Etiqueta1">Consumidor Final </span></td>
    </tr>
</table>
<script language="javascript"> ShowHide('td_consumidor'); </script>
<table width="566" border="0" cellpadding="0" cellspacing="0">
<tr id="tr_numero">
  <td>
  <table width="452" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="75" height="33" align="right" class="BarraBusqueda"><span class="Asterisco">*</span>&nbsp;N&uacute;mero:</td>
      <td width="276" class="BarraBusqueda">
        <input name="NumDoc" type="text" id="NumDoc" value="" size="16" maxlength="20"/>
      <span class="Titulos2">Ejm: 001-001-000000009</span></td>
      <td width="101" align="center" class="BarraBusqueda">
      <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success btn-mini" title="Buscar" onclick="if(document.getElementById('op_opciones2').checked){this.form.submit();}else{validar_requeridos(this.form, 'Tic_Cod*NumDoc', 0);}"> <i class="icon-search icon-white"></i> <span>Buscar</span></button></td>
    </tr>
  </table>
  </td>
</tr>
<tr id="tr_fechas">
  <td >
  <table width="452" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="73" height="33" align="right" class="BarraBusqueda"><span class="Asterisco">*</span>&nbsp;Desde:</td>
      <td width="99" class="BarraBusqueda"><input name="Fec_Ini" type="text" id="Fec_Ini" value="<?php echo date("Y-m-d");?>" size="10"  onkeyup="mascara(this,'-',patron, true);" onchange="valida_fechas_pdf('Fec_Ini','Fec_Fin','2')" /></td>
      <td width="75" align="right" class="BarraBusqueda"><span class="Asterisco">*</span>&nbsp;Hasta:</td>
      <td width="103" class="BarraBusqueda"><input name="Fec_Fin" type="text" id="Fec_Fin" size="10" value="<?php echo date("Y-m-d");?>" onkeyup="mascara(this,'-',patron, true);" onchange="valida_fechas_pdf('Fec_Ini','Fec_Fin','1')" /></td>
      <td width="105" align="center" class="BarraBusqueda"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Tic_Cod*Fec_Ini*Fec_Fin', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
    </tr>
  </table>
  </td>
</tr>
</table>
<script language="javascript"> ShowHide('tr_fechas'); </script>
</FIELDSET>
</form>
<br />
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
    <?php
    	$row_rs_datosCliente = $obBD_con1->getRowConsulta(2, $Ses_Usu_Cod, $obBD_conexion);	
	?>
	<table width="427" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="114" class="Etiqueta1">C&eacute;dula/R.U.C:</td>
      <td width="313" >&nbsp;<?php echo $row_rs_datosCliente['Prs_Ced'];?></td>
    </tr>
    <tr> 
      <td class="Etiqueta1">Cliente:</td>
      <td >&nbsp;<?php echo $row_rs_datosCliente['Prs_Ape'].' '.$row_rs_datosCliente['Prs_Nom'];?></td>
      </table>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	      <th width="13%">Comprobante</th>
          <th width="11%">No. Documento</th>
          <th width="30%">Clave de Acceso</th>
          <th width="23%">Autorizaci&oacute;n</th>
          <th width="10%">Fecha Autorizac.</th>
          <th width="2%">Xml</th>
          <th width="2%">Pdf </th>
      </tr>
     </thead>
     <tbody> 
	  <?Php 
	  if($total_datos!=0)
	  { 
	  foreach($row_rs_datos as $datos)
	  { 
	    /*Datos de Ventas y Notas de Credito */
	  	if($datos['Tic_Sri']=='01' or $datos['Tic_Sri']=='04')
		{ 
			$NumCop=$datos['Suc_Sri'].'-'.$datos['Pun_Sri'].'-'.str_pad($datos['Vet_Num'], 9, "0", STR_PAD_LEFT);
			$Clv_Acc=$datos['Vet_Xml'];
			$Num_Aut=$datos['Vet_Sri'];
			$estado=$datos['Vet_Aut'];			
		}	
		/*Datos de Retenciones */
		if($datos['Tic_Sri']=='07')
		{   
			$NumCop=$datos['Suc_Sri'].'-'.$datos['Pun_Sri'].'-'.str_pad($datos['Ret_Num'], 9, "0", STR_PAD_LEFT);
			$Clv_Acc=$datos['Ret_Xml'];
			$Num_Aut=$datos['Ret_Sri'];
			$estado=$datos['Ret_Aut'];			
		}	
		/*Datos de Guias de Remison */
		if($datos['Tic_Sri']=='06')
		{   
			$NumCop=$datos['Suc_Sri'].'-'.$datos['Pun_Sri'].'-'.str_pad($datos['Gui_Num'], 9, "0", STR_PAD_LEFT);
			$Clv_Acc=$datos['Gui_Xml'];
			$Num_Aut=$datos['Gui_Sri'];
			$estado=$datos['Gui_Aut'];			
		}	
		  ?>
		  <tr>	  
			<td align="center">
			<?Php 
				if($datos['Tic_Sri']=='07')
				{ 
					$arr = explode(" ",$datos['Tic_Des']);										
					$ticDes=$arr[2];										
				}else{
					$ticDes=$datos['Tic_Des']; 
				}
				echo $ticDes;
			?></td>
			<td align="center" ><strong><?Php echo $NumCop; ?></strong></td>
			<td align="center">&nbsp;<?Php echo $Clv_Acc; ?></td>
			<td align="center">
			<?php if($Num_Aut!='' && $estado=='S')
			{ 
				echo $Num_Aut;
			}else{ 
				echo '<font class="Alertas3"> PENDIENTE</font>';
			} 
			?></td>
			<td align="center">
			<?php if($Num_Aut!='' && $estado=='S')
			{ 
				$fechaAut=substr($Num_Aut,0,8);
				$f1=substr($fechaAut,0,2);
				$f2=substr($fechaAut,2,2);
				$f3=substr($fechaAut,4,4);
				echo $f1.' de '.mes($f2,1).' '.$f3;
			}else{ 
				echo '<font class="Alertas3"> PENDIENTE</font>';
			} ?>
            </td>
			<td align="left">
			  <?Php if($Num_Aut!='' && $estado=='S'){?>
			  <form name="form2" id="form2" method="post" target="_new" action="<?php echo $Ses_Emp_Cod."/".$Clv_Acc."_A.xml"; ?>">
			    <input name="Vet_Cod" id="Vet_Cod" type="hidden" value="<?Php echo $datos['Vet_Cod'];?>">										
			    <input name="Ret_Cod" id="Ret_Cod" type="hidden" value="<?Php echo $datos['Ret_Cod'];?>">
                <input name="Gui_Cod" id="Gui_Cod" type="hidden" value="<?Php echo $datos['Gui_Cod'];?>">										
			    <button type="button" class="btn btn-success btn-mini" title="Bajar XML" onclick="downloadURI('<?php echo $Ses_Emp_Cod;?>','<?php echo $Clv_Acc.'_A'?>')"><i class=" icon-download-alt icon-white"></i>
			      </button>
			    </form>
			  <?php }else{?>
              	 <button type="button" disabled="disabled" class="btn btn-success btn-mini" title="Bajar XML" onclick="downloadURI('<?php echo $Ses_Emp_Cod;?>','<?php echo $Clv_Acc.'_A'?>')"><i class=" icon-download-alt icon-white"></i>
			      </button> 
              <?php }?>
			  </td>
			<td align="center">			
			  <?Php if($Num_Aut!='' && $estado=='S'){?>
			  <form name="form3" id="form3" method="post" target="_new" action="<?php echo $url_pdf; ?>">				
			    <input name="op" id="op" type="hidden" value="<?php echo "I";?>"> 
			    <input name="logoUrl" id="logoUrl" type="hidden" value="<?php echo $Ses_Emp_Log;?>"> 
			    <input name="urlXml" id="urlXml" type="hidden" value="<?Php echo '../FRONT/'.$Ses_Emp_Cod."/".$Clv_Acc."_A.xml";?>">																				
			    <button type="button" class="btn btn-primary btn-mini" title="Bajar PDF" onclick="this.form.submit()"><i class=" icon-download-alt icon-white"></i></button>
			    </form>			
			  <?php }else{?>
              	<button type="button" disabled="disabled" class="btn btn-primary btn-mini" title="Bajar PDF" onclick="this.form.submit()"><i class=" icon-download-alt icon-white"></i></button>
              <?php }?>
			  </td>
		  </tr>
	  <?Php }//Fin del foreach
	  }else{
	 ?>
		<tr><td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>	   
	<?Php 
	}//Fin del else if($total_rs_buscar != 0) ?>
    </tbody>
  </table>
</FIELDSET>
<?php echo barra_estado($total_datos); ?>
<br />
 </td>
</tr>
</table>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/fac_par_comp_elect.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script> 
</BODY>
</HTML>
<?php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>