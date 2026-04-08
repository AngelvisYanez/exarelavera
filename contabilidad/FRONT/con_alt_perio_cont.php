<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripción: Alta de Periodos Contables
* Fecha de actualización: 2015-Feb-25
* Desarrollador: Jose Cumbicos
*/

require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/con_log_perio_cont.php');	  
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



if(isset($hdd_save)) 
{ 
 	  $rs_periodo = $obBD_con1->getRowConsulta(112, $Pec_Fei.'*'.$Pec_Fef, $obBD_conexion);
	  $total_rs_periodo=$rs_periodo['Pec_Cod'] > 0? 1 : 0;	 	 
	  if ($total_rs_periodo == 0) 
	  {		  	
	  	   $obBD_ins1 =  new Class_Log_Datos_Con;
		   /**
		   * inicio de la transaccion 
		   */
		   $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		   
		   $obBD_ins1->operacionobBD(111, $Pec_Fei.'*'.$Pec_Fef, $obBD_conexion);
		   /**
		   * fin de la transacción 
		   */
		   $obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	  }
	  else
	  {
?>
		<script LANGUAGE="JavaScript">	
				alert ("¡No se ha podido guardar los datos porque la fecha es menor a la del periodo anterior o ya existe en la base de datos!");
		</script>
<?Php
	   }
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
        <script language="javascript" src="../VALIDACIONES/con_val_perio_cont.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script> 				         
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
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
			$( "#Pec_Fei" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});				
			/* Campo 2 */
			$( "#Pec_Fef" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});						
		}); 		
        </script>   
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
<tr class="BarraTitulo">
	  <td height="10">&raquo; registro de periodos contables </td>
 </tr>
	<tr>
	<td height="400" valign="top">
    <form method="post" name= "form" id= "form" action="<? echo $_SERVER['PHP_SELF'];?>">
    <FIELDSET>
    <LEGEND>
    <label class="Titulos2">Datos a registrar</label>
    </LEGEND>
      <table width="100%" border="0">
      <tr>
           <td width="758"><?Php echo mensaje_requerido(); ?></td>
      </tr>
      </table>
      <table border="0">
          <tr>
              <td width="155" class="Etiqueta1"><span class="Asterisco">*</span> Fecha de Inicio:</td>
                <td width="393"><input name="Pec_Fei" type="text" id="Pec_Fei" value="<?php echo date("Y-m-d"); ?>" size="15" onKeyUp="mascara(this,'-',patron,true)" readonly="true"></td>
            </tr>
            <tr>
              <td width="155" class="Etiqueta1"><span class="Asterisco">*</span> Fecha de fin:</td>
                <td width="393"><input name="Pec_Fef" type="text" id="Pec_Fef" value="<?php echo date("Y-m-d"); ?>" size="15" onKeyUp="mascara(this,'-',patron,true)" readonly="true"></td>
            </tr>
        </table>
     
    </FIELDSET>	 
     <table width="100%" border="0" class="Azul">
        <tr>
          <td width="100%" height="23">
            <input name="button" type="button" class="Boton_Guardar" title="Guardar" value="Guardar" onClick="validar_perio_cont(form)">
            <input name="hdd_save" type="hidden" id="hdd_save">
          </p></td>
        </tr>
      </table>
	 </form>        
</td>
</tr>
</table>	    
</BODY>
</HTML>
<?Php 	
/**
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>