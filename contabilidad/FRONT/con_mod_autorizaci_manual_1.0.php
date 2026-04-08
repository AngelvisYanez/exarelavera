<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripción: Modificación de las Autorizaciones
* Fecha de creación:	Julio 2010
* Desarrollador:	Jose Cumbicos
* Fecha de actualización: 2011-Jun-06
* Desarrollador: Lewis Chimarro
* Fecha de actualización: 2012-05-10
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_autorizaci_manual.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/** 
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;
/**
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$hora = date("H:i:s");


/**
* Ajax Carga el Punto de Imprecion
*/
if($ajax_punto==1)
{
	$cadena_suc=explode("*",$Suc_Cod);	
	$row_rs_puntoImp = $obBD_con1->getArrayConsulta(508, $cadena_suc[0], $obBD_conexion); ?>
	<select name="Pun_Cod" id="Pun_Cod" onchange="ajax_datos('<?pHP echo $_SERVER['PHP_SELF']; ?>?ajax_dcto=1&Pun_Cod=' + this.value + '&Suc_Cod=<? echo $Suc_Cod;?>','div_docAut')">
	<option value="">Seleccione...</option>
	<? 
	foreach ($row_rs_puntoImp as $row)
	{?>
	<option value="<? echo $row['Pun_Cod'];?>"><? echo $row['Pun_Des']?></option>
	<? 
    }//Fin del foreach --> $row_rs_puntoImp ?>
    </select>
<?
exit();
}

/**
* Ajax Carga el Los Documentos existentes en la tabla autorizacion
*/
if($ajax_dcto==1)
{	
	$row_rs_DoctoAut = $obBD_con1->getArrayConsulta(511, $Pun_Cod, $obBD_conexion); 
	$total_rs_DoctoAut=count($row_rs_DoctoAut);
?>
	<select name="Tic_Cod" id="Tic_Cod">
	<? if($total_rs_DoctoAut!=0){?>	
        <option value="">Seleccionar...</option>
		<? foreach ($row_rs_DoctoAut as $row){?>
			<option value="<?php echo $row['Tic_Cod']?>"><?php echo $row['Tic_Des']?></option>
		<? }
	}else{?>
    	<option value="">...</option>
    <? }?>
	</select>
<?
exit();
}

/** 
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID'])) 
{
  	 /** 
	 * Da de alta una nueva autorizacion
	 */
	 if ($hdd_save==1) 
	 {   	  
		$opcion = $_POST["optAut"]; 
		$obBD_ins1 =  new Class_Log_Datos_Con;
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		$obBD_ins1->operacionobBD(104, $Tic_Cod.'*'.$Pun_Cod, $obBD_conexion);
		foreach($opcion as $opt)
		{		  	
			$obBD_ins1->operacionobBD(109, 'A*'.$opt, $obBD_conexion);				
		}
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	 }
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>          
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>  
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
       <script>
		$(function() { 
			/* Campo 1 */
			$( "#Aut_Fci" ).datepicker();			
			$( "#Aut_Fci" ).change(function() {
			$( "#Aut_Fci" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
			});			
			/* Campo 2 */
			$( "#Aut_Cad" ).datepicker();			
			$( "#Aut_Cad" ).change(function() {
			$( "#Aut_Cad" ).datepicker( "option", "dateFormat", "yy-mm-dd" );			
			});					
		}); 		
        </script>    
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td height="10">&raquo;Cambiar Autorizaci&oacute;n S.R.I. </td>   
  </tr>
	<tr>
  	  <td height="400" valign="top">
  <form method="post" name= "form1" id="form1" action="<? echo $_SERVER['PHP_SELF'];?>">    

<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>  
<?Php echo mensaje_requerido(); ?>
  <table width="100%" border="0" cellpadding="0" cellspacing="1">
      <tr>
        <td width="149" class="Etiqueta1"><span 2="Asterisco"><span class="Asterisco">* </span> Sucursal:</span></td>
        <td>
		<?
		/** 
		* Carga las sucursales de la empresa
		*/
		$row_rs_sucursal = $obBD_con1->getArrayConsulta(507, $Ses_Emp_Cod, $obBD_conexion);  ?>						
		<select name="Suc_Cod" id="Suc_Cod" onChange="ajax_datos('<?pHP echo $_SERVER['PHP_SELF']; ?>?ajax_punto=1&Suc_Cod=' + this.value,'div_punImp')">
          <option value="">Seleccione...</option>
		 <? 
		  foreach ($row_rs_sucursal as $row)
		  {?>
		  	<option value="<? echo $row['Suc_Cod']."*".$row['Suc_Des'];?>"><? echo $row['Suc_Des']?></option>          
		  <? } //fin del foreach $row_rs_sucursal ?>
        </select>		</td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Punto de Impresi&oacute;n:</td>
        <td>
        <div id="div_punImp">
        <select name="Pun_Cod" id="Pun_Cod">
          <option value="">...</option>
        </select>
        </div>
        </td>
      </tr>
      <tr>
        <td class="Etiqueta1"><span class="Asterisco">* </span> Tipo Documento:</td>
        <td>
        <div id="div_docAut">
          <select name="Tic_Cod" id="Tic_Cod">
          <option value="">...</option>
        </select>
        </div>
        </td>
      </tr>
      </table>
</FIELDSET>	
<br /> 
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="Azul">
    <tr>
      <td width="100%">
      <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Suc_Cod*Pun_Cod*Tic_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>                
          <input name="hdd_avilita_1" type="hidden" id="hdd_avilita_1">
		  <input name="hdd_avilita_2" type="hidden" id="hdd_avilita_2">		 
      </td>
    </tr>
  </table>

</form>
<? if(isset($hdd_avilita_2)){ ?>
 <form method="post" name="form2" id="form2" action="<? echo $_SERVER['PHP_SELF'];?>">
 <?Php $thisPost->startPost(); ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Resultado de la busqueda </label>
</LEGEND>   
  <? 
  	//$cadena=explode("*",$Pun_Cod);	
	/**
	*  Carga los tipos de documentos de un Punto de Impresion
	*/
	$row_rs_documentos = $obBD_con1->getArrayConsulta(101, $Pun_Cod.'*'.$Tic_Cod, $obBD_conexion); 
	$total_rs_documentos=count($row_rs_documentos);
  ?>
  <table width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
		<td width="13%" class="Etiqueta1"><span class="Etiqueta1">Sucursal:</span></td>	
		<td width="87%" align="left" class="LetraPlan" >&nbsp;<? echo $row_rs_documentos[0]['Suc_Des'];?></td>	
  </tr>
  <tr>    
		<td width="13%" class="Etiqueta1"><span class="Etiqueta1">Punto de impresi&oacute;n:</span></td>	
		<td width="87%" align="left" class="LetraPlan" >&nbsp;<? echo $row_rs_documentos[0]['Pun_Des'];?></td>		
  </tr>  
  </table>
  <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
  <thead>
  <tr>
		<th width="6%" >Cod. Int.</th>
		<th width="35%">Tipo Documento</th>
		<th width="12%">Tipo</th>
		<th width="12%">Fecha Inicio</th>
		<th width="12%">Fecha Caducidad</th>
		<th width="12%">Secuencia Inicio</th>
		<th width="11%">Secuencia Final</th>
		<th width="15%">Estado</th>
		<th width="12%">Autorizaci&oacute;n</th>
		<th width="9%">&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?   
  if($total_rs_documentos!=0)
  { $i=1;
  foreach($row_rs_documentos as $row)
  {?>
  
  <tr>  
  
    <td><div align="center"><?php echo $row['Aut_Cod'];?></div></td>
	<td >&nbsp;<?php echo $row['Tic_Des'];?></td>
	<td align="center" ><?php echo $row['Aut_Tem']; ?></td>
	<td align="center" ><?php echo $row['Aut_Fci']; ?></td>
	<td align="center" ><?php echo $row['Aut_Cad']; ?></td>
    <td align="right" ><?php echo $row['Aut_Ini']; ?></td>
    <td align="right" ><?php echo $row['Aut_Fin']; ?></td>
    <td align="center" ><?php if($row['Aut_Est']=='A'){echo "ACTIVA";}else{ echo "&nbsp;";}?></td>
	<td align="right"><div align="center"><?php echo $row['Aut_Sri'];?></div></td>	
    <td align="center">	
   		
        <input type="hidden" id="Tic_Cod" name="Tic_Cod" value="<? echo $Tic_Cod;?>" /> 
        <input type="hidden" id="Pun_Cod" name="Pun_Cod" value="<? echo $Pun_Cod;?>" /> 
        <input type="hidden" id="total" name="total" value="<? echo $total_rs_documentos; ?>" />
    	<input type="radio" id="optAut[]" name="optAut[]" value="<?Php echo $row['Aut_Cod'];?>" <?php if($row['Aut_Est']=='A'){echo "checked";}?> />
    
	</td>	 
  </tr>
  <? $i++;
  	}//Fin del foreach -> $row_rs_documentos 
  }else{
  ?>
  <tr>
    <td>&nbsp;</td>
    <td align="center"><?php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1);?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
	<input type="hidden" id="hdd_avilita_3" name="hdd_avilita_3">	
  </tr>	
<? } //if($row_rs_numdocumentos!=0)?>
  </tbody>
</table>
</FIELDSET>
<? if($total_rs_documentos!=0){?>	
    <table border="0" cellpadding="0" cellspacing="0">
	 <tr>
	   <td width="109">
	 	<button name="btn_guardar" type="button" class="btn btn-primary fileinput-button" id= "btn_guardar" title= "Guardar" onClick= "if(confirmacion3(this.form)){this.form.submit();}" value="Actualizar"><i class=" icon-book icon-white"></i><span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span></button>	   		
	   <input type="hidden" id="hdd_save" name="hdd_save" value="1" />    
	   </td>	  
	 </tr>
	</table>
 <? }?>
	<input name="cantmodal" id="cantmodal" type="hidden" value="<?php echo $i; ?>" />
  </form>   
<? } //if(isset($hdd_avilita_2)) ?>
 <br />
 </td>
  </tr>
</table>	
<div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">
	<div id="div_autorizaci"></div>
</div>
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/con_par_autorizaci.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>     
</BODY></HTML>
<?Php
/**
* Cerrar las conexiones 
*/
$obBD_conexion->cerrar();
?>