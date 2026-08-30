<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite registrar las marcas de los productos 
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-06-01
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_marca.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

 /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tes
   */
  $obBD_conexion = new Class_Log_Conexion_Mar($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tes
   */
  $obBD_con1 =  new Class_Log_Datos_Mar;
  
 

if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{  				
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1-> inicio_transaccion($obBD_conexion->conexion);
		
		/** 
		* Se guarda la marca 
		*/
		$obBD_con1->operacionobBD(2, $Mar_Des.'*'.$Ses_Emp_Cod, $obBD_conexion);
		
		/**
		* fin de la transacci�n 
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

/*
* Ajax para validar la existencia de una marca
*/
if (isset($ajax_mar))
{	
	/** 
	* Consultar si existe el nombre de marca  
	*/
	$row_rs_con_mar = $obBD_con1->getArrayConsulta(1, strtoupper($Mar_Des.'*'.$Ses_Emp_Cod), $obBD_conexion);
	
	if (count($row_rs_con_mar) > 0)
	{ ?>
		<input name="Mar_Des" type="text" id="Mar_Des" value="" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Mar_Des=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	!La marca: <?php echo strtoupper($Mar_Des); ?> ya existe!  
	<?php
	}
	else
	{ ?>
		<input name="Mar_Des" type="text" id="Mar_Des" value="<?Php echo $Mar_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Mar_Des=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />        
	<?php	
		}					
		?>
        <script type="text/javascript">
			document.getElementById('btnGuardar').disabled=false;
		</script>
        <?php
exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<HTML>
	<HEAD>
  <TITLE><?Php echo "Marca Registrar [EXA]"; ?></TITLE>
    <meta charset="UTF-8">
    	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/fac_val_marca.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<!--meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"-->
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar marca </td>
  </tr>
	<tr>
        <td height="400" valign="top">
        <form method="post" name= "form1" id= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">        
        <?Php 
        /** 
        * Creacion del campo repost 
        */
        $thisPost->startPost();
        ?>
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos a registrar</label>
        </LEGEND>
        <?Php echo mensaje_requerido(); 
		noEnterSubmit();
		?>
        <table width="574" border="0">
          <tr>
            <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
            <td width="458">
            <div class="Titulos2" id="div_mar">
            <input name="Mar_Des" type="text" id="Mar_Des" value="" size="30" maxlength="30" style="text-transform:uppercase" 
            onfocus="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Mar_Des='+this.value,'div_mar')">
            </div>
            </td>
          </tr>
      </table>     
	  </FIELDSET>	
      <br />
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
       <button type="button" id="btnGuadar" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Mar_Des', 1)">
               <i class="icon-book icon-white"></i>
               <span>Guardar</span>
      </button>         
      </form>    
      <br/>      
  <td/>
  <tr/>    
</table>  
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_marca.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>