<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/** 
* Descripción: Permite el ingreso de los puntos de impresión
* Desarrollador:	Lewis Chimarro
* Fecha de creaci+on:	2012-05-11
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_puntos_imp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;
/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;  

/* Ajax que permite verificar si el tipo de embarque */
if(isset($ajax_emb))
{
	/**
	* Consulta datos de los puntos de impresión 
	*/
	$row_puntos_imp = $obBD_con1->getRowConsulta(2, trim($Pun_Des).'*'.$Ses_Suc_Cod, $obBD_conexion);
	
	if (count($row_puntos_imp) == 0)
	{ ?>
		<input name="Pun_Des" type="text" id="Pun_Des" size="30" maxlength="30" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&Pun_Des=' + this.value,'div_emb')" value="<?php echo $Pun_Des; ?>" />&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
    <?Php    	
	}
	else
	{ ?>
		<input name="Pun_Des" type="text" id="Pun_Des" size="30" maxlength="30" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&Pun_Des=' + this.value,'div_emb')" />&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	¡Punto de impresión <?php echo "<font style='text-transform:uppercase'>".$Pun_Des."</font>"; ?> ya existe!        
<?Php	}
exit();
}

if ($thisPost->postBlock($_POST['postID']))
{ 
	if (isset($hdd_save)) 
	{
		/**
		* Agregar archivos
		*/
		$obBD_con1->insertUpdateDelete(3, $Suc_Cod.'*'.$Pun_Des.'*'.$Pun_Ubi, $obBD_conexion);
	}
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; registrar Puntos de Impresi&oacute;n</td>
  </tr>
	<tr>
	  	<td valign="top" height="400">
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form1" id="form1">
  <table width="100%" border="0" align="left">
  </table>
 <?php //Creacion del campo REPOST
$thisPost->startPost();?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>		  
<?Php echo mensaje_requerido(); ?>
 <table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
    	  <td class="Etiqueta1"><span class="Asterisco">* </span> Sucursal:</td>
    	  <td colspan="3"><?php
		/* 
		* Carga las sucursales de la empresa
		*/
		$row_rs_sucursal = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);  ?>
    	    <select name="Suc_Cod" id="Suc_Cod">
    	    <option >Seleccione...</option>
    	    <?php 
		  foreach ($row_rs_sucursal as $row)
		  {?>
    	    <option value="<?php echo $row['Suc_Cod'];?>"><?php echo $row['Suc_Des']?></option>
    	    <?php } //fin del foreach $row_rs_sucursal ?>
  	    </select></td>
  	  </tr>
    	<tr>
    	  <td width="16%" class="Etiqueta1"><span class="Asterisco">*</span> Descripción:</td>
    	  <td colspan="3"><div id="div_existe">
    	    <table width="70%" border="0" cellpadding="0" cellspacing="0">
    	      <tr>
    	        <td><div id="div_emb">
    	          <input name="Pun_Des" type="text" id="Pun_Des" size="50" maxlength="50" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&Pun_Des=' + this.value,'div_emb')" />
    	        </div></td>
    	        </tr>
    	      </table>
    	    </div></td>
  	  </tr>
    	<tr>
    	  <td class="Etiqueta1"><span class="Asterisco">*</span> Ubicaci&oacute;n:</td>
    	  <td colspan="3">
    	    <textarea name="Pun_Ubi" id="Pun_Ubi" cols="50" rows="4"></textarea></td>
  	  </tr>
		</table> 
</FIELDSET>	
<br> 
<table width="208" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="100%" height="23">
          <input name="hdd_save" type="hidden" id="hdd_save">
 <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Suc_Cod*Pun_Des*Pun_Ubi',1)" id="btn_guardar">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
           </button>          
      </td>
    </tr>
  </table>
</form>        
	</td>
  </tr>
</table>	    
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/con_par_puntos_imp.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
</BODY></HTML>
<?php
/**
* Cierra la conexion a la base de datos 
*/
@$obBD_conexion->cerrar();
?>