<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/** 
 * Descripci�n: Permite el ingreso de los campos de los tipos de activos
 * Desarrollador:	Fabian Gallardo
  					Didimo Zamora
 * Fecha de actualizaci�n:	2011-03-24, 16-04-2013
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/act_log_campos_det.php');	
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
/**
 * Ajax que permite verificar si el campo ya existe 
 */
if(isset($iscamp))
{
	if($nom_camp!="")
	{
		$rs_nomcamp = $obBD_con1->getArrayConsulta(407,$nom_camp, $obBD_conexion); 
		$total_rs_nomcamp = count($rs_nomcamp);
?>
		 <table width="70%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="25%">
				<input name="Cam_Lar" type="text" id="Cam_Lar" size="50" maxlength="50" <?php if($total_rs_nomcamp == 0 ){ ?> value="<?php echo mb_convert_encoding($nom_camp, 'ISO-8859-1', 'UTF-8'); ?>" <?php }?> style="text-transform:uppercase" onBlur="if (trim(this.value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?iscamp=1&nom_camp=' + this.value,'div_existe') }" >
				</td>
                <td width="75%">
				<?php if($total_rs_nomcamp!=0)
				{ ?>
					<span class="Alertas3">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/eliminar.jpg" width="20" height="20" type="image"/>
					<?Php
					echo "&nbsp;&nbsp;&iexcl;El campo ( ".$nom_camp." ) ya existe!"; ?></span>
			    <?php 
				}
				else
				{?>
					<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="20" height="20">
				<?php 
				}?>
				</td>
             </tr>
         </table>
<?php  
	}
	exit();
}
/**
 * Grabado de los campos  
 */
if ($thisPost->postBlock($_POST['postID'])) 
{ 
 	if (isset($hdd_save)) { 
		 	/**
			 * Inicio de transaccion..
			 */
	  	$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/**
			 * Ingreso del campo si no se encuentra otra con la misma descripci�n
			 */
	  	$obBD_con1->operacionobBD(408, trim($Cam_Lar).'*'.trim($Cam_Cor).'*'.$Cam_Tip.'*'.trim($Cam_Obs), $obBD_conexion);
			/**
			 * Graba auditoria..
			 */
		//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
			/**
			 * Fin de transaccion ..
			 */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	  }
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
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
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; registrar Campos de Activos </td>
    </tr>
	<tr>
	  	<td height="389" valign="top">
         <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">
 <?php 
 /** 
  * Creacion del campo REPOST
  */
$thisPost->startPost();?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
		  
<?Php echo mensaje_requerido(); ?>
 <table width="100%" cellpadding="0" cellspacing="0" border="0">
    	<tr>
         <td width="16%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n Larga:</td>
          <td colspan="2"><div id="div_existe">
            <table width="70%" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="25%"><input name="Cam_Lar" type="text" id="Cam_Lar" size="50" maxlength="50" style="text-transform:uppercase"  onBlur="if (trim(this.value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?iscamp=1&nom_camp=' + this.value,'div_existe'); }" ></td>
                <td width="75%">&nbsp;</td>
              </tr>
            </table>
          </div></td>
          </tr>
		<tr>
		  <td class="Etiqueta1"><span class="Asterisco">*</span> Descripci�n Corta:</td>
		  <td><input name="Cam_Cor" type="text" id="Cam_Cor" size="10" maxlength="10" style="text-transform:uppercase"></td>
		  <td width="70%" colspan="-1">&nbsp;</td>
		</tr>
		<tr>
		  <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de dato:</td>
		  <td><select name="Cam_Tip" id="Cam_Tip">
            <?php 
				//$row_rs_cod = array ("NE","ND","CA","TX");
				$row_rs_cod = array ("NE","ND","TX","TC","BL");
				//$row_rs_des = array ("N�mero entero", "N�mero decimal","Caracter","Texto");				
				$row_rs_des = array ("N�mero entero", "N�mero decimal", "Texto Simple", "Texto Multilinea","SI / NO");
				for ($i=0;$i<count($row_rs_cod);$i++) 
			 	{  
	  			?>
            <option value="<?php echo $row_rs_cod[$i];?>"><?php echo $row_rs_des[$i];?></option>
            <?php
	 		}
	  		?>
          </select></td>
		  <td colspan="-1">&nbsp;</td>
		</tr>
		<tr>
		  <td width="16%" class="Etiqueta1">Observaci&oacute;n:</td>
            <td colspan="2"><textarea name="Cam_Obs" cols="45" rows="3" id="Cam_Obs" style="text-transform:uppercase"></textarea></td>
		    </tr>
    </table> 
</FIELDSET>	
<br> 
<table width="179" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="179" height="23">
		<button name="boton_guardar" id="boton_guardar" type="button"  class="btn btn-primary fileinput-button" title="Guardar" value="Guardar" onClick="validar_requeridos(this.form, 'Cam_Lar*Cam_Cor*Cam_Tip', 1)">
        	<i class="icon-book icon-white"></i>
			<span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
        </button>
          <input name="hdd_save" type="hidden" id="hdd_save">
      </td>
    </tr>
  </table>
</form>        </td>
  </tr>
</table>	
</div>  
<script type="text/javascript" src="../VALIDACIONES/act_par_campos_act.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>  
</BODY></HTML>
<?php
/**
 * Cerrado de las conexiones 
 */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>