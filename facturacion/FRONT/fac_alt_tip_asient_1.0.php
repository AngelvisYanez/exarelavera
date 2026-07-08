<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite Registrar los tipos de comprobantes
 * 
 * @author juanpuxito
 * @version 1.0
 * Fecha de actualización:	27-05-2014
 *
 * @package Exa.Facturacion - OFSERCONT
 * 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_tip_asiento.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tip
   */  
  $obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tip
   */
  $obBD_con1 =  new Class_Log_Datos_Tip;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;

if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{			
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1-> inicio_transaccion($obBD_conexion->conexion);
		
		/** 
		* Se guarda el tipo de comprobante 
		*/
		$obBD_con1->operacionobBD(2, $Tip_Des.'*'.$Tia_Ini.'*'.$Tip_Abrev, $obBD_conexion);
		
		/**
		* fin de la transacción 
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

if (isset($ajax_nuevo))
{		
		/** 
		* Consultar si existe el nombre de tipo de asiento 
		*/
		$row_rs_con_tip = $obBD_con1->getArrayConsulta(5, strtoupper($Tip_Des.'*'.$Tia_Ini), $obBD_conexion);		
		if (count($row_rs_con_tip) != 0)		
		{ 			
		?>        
		<input name="Tip_Des" type="text" id="Tip_Des" value="<?php echo $Tip_Des?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nuevo=1&Tip_Des='+this.value+'&Tia_Ini='+ document.getElementById('Tia_Ini').value,'div_tipdes')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	<span class="Alertas3">¡La marca: <?php echo strtoupper($Tip_Des); ?> ya existe!</span>  	
		<?php }else{ ?>
		<input name="Tip_Des" type="text" id="Tip_Des" value="<?Php echo $Tip_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nuevo=1&Tip_Des='+this.value+'&Tia_Ini='+document.getElementById('Tia_Ini').value,'div_tipdes')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />	
		<?php
		}
exit();
}
?>
<HTML>
	<HEAD>
    	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/fac_par_tip_asient.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Tipo Asiento </td>
    </tr>
	<tr>
        <td height="340" valign="top">
        <form method="post" name= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">        
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
        <?Php echo mensaje_requerido(); ?>
        <table width="55%" border="0">
          <tr>
          <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Tipo :         
          </td>
          <td>          
          <select name="Tia_Ini" id="Tia_Ini" onblur="if (trim(document.getElementById('Tip_Des').value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nuevo=1&Tip_Des='+ document.getElementById('Tip_Des').value +'&Tia_Ini='+ document.getElementById('Tia_Ini').value,'div_tipdes')" >
				<option value=''>Seleccione...</option>
            	<option value='I' >Ingreso</option>
                <option value='E' >Egreso</option>
                <option value='D' >Diario</option>
            </select>	           
          </td>          
          </tr>
          <tr>
            <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
            <td width="83%">
            <div id="div_tipdes">
            <input name="Tip_Des" type="text" id="Tip_Des" value="" size="30" maxlength="30" style="text-transform:uppercase"
             onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_nuevo=&Tip_Des='+ this.value +'&Tia_Ini='+ document.getElementById('Tia_Ini').value,'div_tipdes')">
            </div>
            </td>
          </tr>
          <tr>
          <td width="17%" class="Etiqueta1"><span class="Asterisco">*</span> Abrev.:</td>
           <td width="83%">          
            <input name="Tip_Abrev" type="text" id="Tip_Abrev" value="" size="5" maxlength="30" style="text-transform:uppercase">
            </td>
          </tr>
      </table>     
	  </FIELDSET>
        <br />
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form,'Tia_Ini*Tip_Des*Tip_Abrev', 1)">
               <i class="icon-book icon-white"></i>
               <span>Guardar</span>
      </button>         
      </form>    
      <br/>      
  <td/>
  <tr/>    
</table>  
</div> 
<script type="text/javascript" src="../VALIDACIONES/fac_par_tip_asient.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>