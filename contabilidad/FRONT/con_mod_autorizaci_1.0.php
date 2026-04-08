<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Modificaci�n de las Autorizaciones
* Fecha de creaci�n:	Julio 2010
* Desarrollador:	Jose Cumbicos
* Fecha de actualizaci�n: 2011-Jun-06
* Desarrollador: Lewis Chimarro
* Fecha de actualizaci�n: 2012-05-10
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_autorizaci.php');
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
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
$hora = date("H:i:s");

/** 
* Edicion de nueva fecha del comprobante del SRI
*/
if (isset($ajax_autorizaci)) 
{   	
	$cadena=explode("*",$Pun_Cod);
  	/**
	* Carga los tipos de documentos de un Punto de Impresion
	*/
	$row_rs_documento = $obBD_con1->getRowConsulta(510, $codigo, $obBD_conexion); 
  ?>
<form method="post" name= "form2" action="<? echo $_SERVER['PHP_SELF'];?>">
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
<?Php echo mensaje_requerido(); $thisPost->startPost();?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="Azul">
  <tr>
    <td width="17%" class="Etiqueta1">Sucursal:</td>
    <td colspan="2" class="LetraPlan">&nbsp;<? echo $cadena[2];?></td>
  </tr>
  <tr>
    <td valign="top" class="Etiqueta1">Punto de impresi&oacute;n:</td>
    <td colspan="2" valign="top" class="LetraPlan">&nbsp;<? echo $cadena[1];?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Documento:</td>
    <td colspan="2" class="LetraPlan">&nbsp;<? echo $Tic_Des;?>
        <input type="hidden" id="Aut_Cod" name="Aut_Cod" value="<? echo $codigo?>">
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> N&ordm;  Tipo de Emisi&oacute;n:</td>
    <td colspan="2">
    <select name="Aut_Tem" id="Aut_Tem" style="width: 30%">
      <option <? if($Aut_Tem=='N'){ echo 'selected';}?> value="N">Normal</option>
      <option <? if($Aut_Tem=='E'){ echo 'selected';}?> value="E">Electr&oacute;nica</option>
    </select>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> N&ordm;  Autorizaci&oacute;n:</td>
    <td colspan="2"><input id="Aut_Sri" name="Aut_Sri" type="text" size="9" maxlength="10" style="text-transform:uppercase; text-align:right" onKeyPress="return validar_numeric(event)" onBlur="if(this.value!=''){minimo(this,10)}" value="<? echo $row_rs_documento['Aut_Sri']?>"></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Punto S.R.I.:</td>
    <td width="2%"><input name="Pun_Sri" id="Pun_Sri" type="text" size="3" maxlength="3" style="text-transform:uppercase; text-align:right" onKeyPress="return validar_numeric(event)" value="<? echo $row_rs_documento['Pun_Sri']?>"></td>
    <td width="81%" class="Texto_Reporte_Rojo">(Ejemplo: 001)</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha Inicio:</td>
    <td colspan="2"><input name="Aut_Fci" type="text" id="Aut_Fci" value="<? echo $row_rs_documento['Aut_Fci']?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)"></td>    
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha Caducidad:</td>
    <td colspan="2"><input name="Aut_Cad" type="text" id="Aut_Cad" value="<? echo $row_rs_documento['Aut_Cad']?>" size="8" onKeyUp="mascara(this,'-',patron,true)" onBlur="validar_fecha2(this)"></td>   
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Secuencia Inicial:</td>
    <td colspan="2"><input id="Num_Ini" name="Num_Ini" type="text" size="5" maxlength="7" style="text-transform:uppercase;text-align:right" onKeyPress="return validar_numeric(event)" value="<? echo $row_rs_documento['Aut_Ini']?>" onBlur="numeroMenor_Msn(this,document.getElementById('Num_Fin'),'No puede ingresar valores mayores que')">
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Secuencia Final:</td>
    <td colspan="2"><input id="Num_Fin" name="Num_Fin" type="text" size="5" maxlength="7" style="text-transform:uppercase;text-align:right" onKeyPress="return validar_numeric(event)" value="<? echo $row_rs_documento['Aut_Fin']?>" onBlur="numeroMayor_Msn(this,document.getElementById('Num_Ini'),'No puede ingresar valores menores que')">
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Alerta 1:</td>
    <td colspan="2"><input id="Alerta2" name="Alerta2" type="text" size="1" maxlength="4" style="text-transform:uppercase;text-align:right" onKeyPress="return validar_numeric(event)" value="<? echo $row_rs_documento['Aut_Ads']?>">
    (N&uacute;mero de documentos m&iacute;nimos para mostrar una alerta antes que termine la secuencia final)</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Alerta 2:</td>
    <td><input id="Alerta" name="Alerta" type="text" size="1" maxlength="2" style="text-transform:uppercase;text-align:right" onKeyPress="return validar_numeric(event)" value="<? echo $row_rs_documento['Aut_Adv']?>"></td>
    <td class="Texto_Reporte_Rojo">(D&iacute;as para mostrar una alerta antes  que caduque  la autorizaci&oacute;n)</td>
  </tr>
</table>
</FIELDSET>
        <input name="hdd_save1" type="hidden" id="hdd_save1">
<table width="154" height="54" border="0" class="Azul">
  <tr>
    <td width="100%" height="23">
	<input name="Pun_Cod" type="hidden" id="Pun_Cod" value="<? echo $Punt_Cod;?>">
	<input type="hidden" id="Pun_Cod" name="Pun_Cod" value="<? echo $Pun_Cod;?>">
	<input name="hdd_save" type="hidden" id="hdd_save" value="0">
	<input type="hidden" id="hdd_avilita_1" name="hdd_avilita_1" value="1">
	<input type="hidden" id="hdd_avilita_2" name="hdd_avilita_2" value="1">
    <button type="button" class="btn btn-primary start" title="Guardar" onclick="document.getElementById('hdd_save').value=1; if(fecha_mayor(Aut_Fci,Aut_Cad)!=false){ validar_requeridos(this.form,'Aut_Sri*Pun_Sri*Num_Ini*Num_Fin*Alerta',1)}">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button>        
	</td>
    </tr>
</table>
</form>
<script>
    $(function() { 
                    $( '#Aut_Fci' ).datepicker({
                      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,maxDate:'<? echo $row_rs_documento['Aut_Cad']?>',
                      onClose: function( selectedDate ) {
                        $( '#Aut_Cad' ).datepicker( "option", "minDate", selectedDate );
                      }
                    });
                    $( '#Aut_Cad' ).datepicker({
                      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,minDate:'<? echo $row_rs_documento['Aut_Fci']?>',
                      onClose: function( selectedDate ) {
                        $( '#Aut_Fci' ).datepicker( "option", "maxDate", selectedDate );
                      }
                    });
                });
</script>                   
<?Php
exit();
} //if (isset($ajax_autorizaci)) 

/**
* Ajax Carga el Punto de Imprecion
*/
if($ajax_punto==1)
{
	$cadena_suc=explode("*",$Suc_Cod);	
	$row_rs_puntoImp = $obBD_con1->getArrayConsulta(508, $cadena_suc[0], $obBD_conexion); ?>
	<select name="Pun_Cod" id="Pun_Cod">
	<option value="">Seleccione...</option>
	<? 
	foreach ($row_rs_puntoImp as $row)
	{?>
	<option value="<? echo $row['Pun_Cod']."*".$row['Pun_Des']."*".$cadena_suc[1];?>"><? echo $row['Pun_Des']?></option>
	<? 
    }//Fin del foreach --> $row_rs_puntoImp ?>
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
		  $obBD_con1->insertUpdateDelete(109, $Aut_Sri.'*'.$Pun_Sri.'*'.$Aut_Fci.'*'.$Aut_Cad.'*'.$Num_Ini.'*'.$Num_Fin.'*'.$Alerta.'*'.$Alerta2.'*'.$Aut_Cod.'*'.$Aut_Tem, $obBD_conexion);	
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
                    
                    var today = new Date();
                        $( '#Aut_Fci' ).datepicker({
                      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,
                      onClose: function( selectedDate ) {
                        $( '#Aut_Cad' ).datepicker( "option", "minDate", selectedDate );
                      }
                    });
                    $( '#Aut_Cad' ).datepicker({
                      changeMonth: true,changeYear: true, dateFormat: 'yy-mm-dd',firstDay: 1,
                      onClose: function( selectedDate ) {
                        $( '#Aut_Fci' ).datepicker( "option", "maxDate", selectedDate );
                      }
                    });
			/* Campo 1 */
//			$( "#Aut_Fci" ).datepicker();			
//			$( "#Aut_Fci" ).change(function() {
//			$( "#Aut_Fci" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
//			});			
//			/* Campo 2 */
//			$( "#Aut_Cad" ).datepicker();			
//			$( "#Aut_Cad" ).change(function() {
//			$( "#Aut_Cad" ).datepicker( "option", "dateFormat", "yy-mm-dd" );			
//			});					
		}); 		
        </script>    
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
	  <td height="10">&raquo;Modificar Autorizaci&oacute;n S.R.I. </td>   
  </tr>
	<tr>
  	  <td height="400" valign="top">
  <form method="post" name= "form1" action="<? echo $_SERVER['PHP_SELF'];?>">    
<?  
if (!isset($hdd_avilita_1)){ ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>  
<?Php echo mensaje_requerido(); ?>
  <table width="100%" border="0" cellpadding="0" cellspacing="1">
      <tr>
        <td class="Etiqueta1"><span><span class="Asterisco">* </span> Sucursal:</span></td>
        <td>
		<?
		/** 
		* Carga las sucursales de la empresa
		*/
		$row_rs_sucursal = $obBD_con1->getArrayConsulta(507, $Ses_Emp_Cod, $obBD_conexion);  ?>						
		<select name="Suc_Cod" id="Suc_Cod" onChange="ajax_datos('<?pHP echo $_SERVER['PHP_SELF']; ?>?ajax_punto=1&Suc_Cod=' + this.value,'div_punImp')">
          <option >Seleccione...</option>
		 <? 
		  foreach ($row_rs_sucursal as $row)
		  {?>
		  	<option value="<? echo $row['Suc_Cod']."*".$row['Suc_Des'];?>"><? echo $row['Suc_Des']?></option>          
		  <? } //fin del foreach $row_rs_sucursal ?>
        </select>		</td>
      </tr>
      <tr>
        <td width="149" class="Etiqueta1"><span class="Asterisco">* </span> Punto de Impresi&oacute;n:</td>
        <td>
		<div id="div_punImp">
		<select name="Pun_Cod" id="Pun_Cod">
          <option></option>                    
        </select>		
		</div>		</td>
        </tr>
    </table>
</FIELDSET>	
<br /> 
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="Azul">
    <tr>
      <td width="100%">
      <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Suc_Cod*Pun_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>                
          <input name="hdd_avilita_1" type="hidden" id="hdd_avilita_1">
		  <input name="hdd_avilita_2" type="hidden" id="hdd_avilita_2">		 
      </td>
    </tr>
  </table>
<? }// fin del if (!isset($hdd_avilita_1)) ?>

</form>
<? if(isset($hdd_avilita_2)){ ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2"> Resultado de la busqueda </label>
</LEGEND>   
  <? 
  	$cadena=explode("*",$Pun_Cod);	
	/**
	*  Carga los tipos de documentos de un Punto de Impresion
	*/
	$row_rs_documentos = $obBD_con1->getArrayConsulta(101, $cadena[0], $obBD_conexion); 
  ?>
  <table width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
		<td width="13%" class="Etiqueta1"><span class="Etiqueta1">Sucursal:</span></td>	
		<td width="87%" align="left" class="LetraPlan" >&nbsp;<? echo $cadena[2];?></td>	
  </tr>
  <tr>    
		<td width="13%" class="Etiqueta1"><span class="Etiqueta1">Punto de impresi&oacute;n:</span></td>	
		<td width="87%" align="left" class="LetraPlan" >&nbsp;<? echo $cadena[1];?></td>		
  </tr>  
  </table>
  <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
  <thead>
  <tr>
		<th width="6%" >Cod. Int.</th>
		<th width="35%">Tipo Documento</th>
		<th width="14%">Autorizaci&oacute;n</th>
		<th width="11%">Caducidad</th>
		<th width="13%">Secuencia Inicio</th>
		<th width="12%">Secuencia Final</th>
		<th width="9%">&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?   
  if(count($row_rs_documentos)!=0)
  { $i=1;
  foreach($row_rs_documentos as $row)
  {?>
  
  <tr>  
  <form method="post" name="form6[<? echo $i?>]" id="form6[<? echo $i?>]" action="<? echo $_SERVER['PHP_SELF'];?>">
    <td><div align="center"><?php echo $row['Tic_Cod'];?></div></td>
	<td >&nbsp;<?php echo $row['Tic_Des'];?></td>
    <td ><div align="center"><?php echo $row['Aut_Sri'];?></div></td>
    <td ><div align="center"><?php echo $row['Aut_Cad'];?></div></td>
    <td align="right" ><?php echo $row['Aut_Ini']; ?>&nbsp;</td>
	<td align="right"><?php echo $row['Aut_Fin']; ?>&nbsp;</td>
	
    <td align="center">	
	<input type="hidden" id="Aut_Cod" name="Aut_Cod" value="<?Php echo $row['Aut_Cod'];?>">	
	<input type="hidden" id="hdd_avilita_1" name="hdd_avilita_1" value="1">
	<input type="hidden" id="hdd_avilita_2" name="hdd_avilita_2" value="1">
	<input type="hidden" id="hdd_avilita_3" name="hdd_avilita_3" value="2">	
    <button type="button" class="btn btn-success fileinput-button" title="Editar Autorizaci�n" name="button<?php echo $i; ?>" id="button<?php echo $i; ?>" onclick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_autorizaci&codigo=<?php echo $row['Aut_Cod']; ?>&Pun_Cod=<?php echo $Pun_Cod; ?>&Tic_Des=<?php echo $row['Tic_Des'];?>&Aut_Tem=<?php echo $row['Aut_Tem'];?>', 'div_autorizaci')">
           <i class="icon-edit icon-white"></i>
           <span>Editar</span>
      </button>
	</td>
	 </form> 
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
	<input type="hidden" id="hdd_avilita_3" name="hdd_avilita_3">	
  </tr>	
<? } //if($row_rs_numdocumentos!=0)?>
  </tbody>
</table>
</FIELDSET>
	<table border="0" cellpadding="0" cellspacing="0">
	 <tr>
	   <td width="109">
	   <form method="post" name= "form4" action="<? echo $_SERVER['PHP_SELF'];?>">
        <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onClick="document.getElementById('hdd_avilita_3').value=0; this.form.submit()">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       </button>	   		
	   </form>       
	   </td>	  
	 </tr>
	</table>
	<input name="cantmodal" id="cantmodal" type="hidden" value="<?php echo $i; ?>" />  
<? } //if(isset($hdd_avilita_2)) ?>
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