<?Php 
/**
* Descripción: Permite modificar la clave por el usuario
* Fecha de actualización:	2011-03-14 
* Desarrollador:	Jose Cumbicos 
* Fecha de actualización:	2011-03-19 
* Desarrollador:	Lewis Chimarro
* Fecha de actualización:	2014-09-04 
* Desarrollador:	Lewis Chimarro
*/	
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;  

/**
* Evitar el reenvio de formularios 
*/
if ($thisPost->postBlock($_POST['postID'])) {  	 	  
	/**
	* Almacena los datos modificados 
	*/
	if (isset($hdd_save) && $Usu_Pal!="")
	{
		/**
		* Creacion del Objeto de conexion 
		*/
		$obBD_conexion = new Class_Log_Conexion_Admu($Ses_Dat_Dis);
	
		/**
		* Creacion del objeto mysql para las inserciones 
		*/
		$obBD_ins1 =  new Class_Log_Datos_Admu;
		/**
		* Inicio de la transaccion 
		*/
		$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/**
		* Inserción de datos de la inscripción 
		*/
		$obBD_ins1->grabarv_registros(sentencias_adm(206, $obBD_ins1->parametros($Usu_Pal.'*'.$Ses_Usu_Cod)),$obBD_conexion->conexion); 	
		
		/**
		* Condicion para reiniciar al index principal de la pagina 
		*/
		if (trim($hdd_save)=="outside")
		{
			/**
			* Cierre de la transacción sin mensaje de alerta, para evitar error en el 
			*redireccionamiento 
			*/
			$obBD_ins1->fin_transaccion_nomsn($obBD_conexion->conexion);
			session_destroy();
			header("Location: ../../index.php");
		}
		elseif(trim($hdd_save)=="inside")
		{
			/**
			* Cierre de la transacción 
			*/
			$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
		}//Fin del else elseif($hdd_save=="inside")
		//echo error_alerta("La Actualización de la Contraseña fue exitosa <br><br> Ingrese nuevamente al sistema con su número de cédula y la nueva clave <br><br>", 1); 	
	}//Fin del if (isset($hdd_save) && $Usu_Pal!="")
}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
?>
<HTML>
	<HEAD>		
		<TITLE>Iniciar sesión</TITLE>
		<script type="text/javascript" src="../VALIDACIONES/Validaciones.js"></script>	
                <script language="javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>        
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>					
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">		
	</HEAD>
<BODY>
<div id="set1">
<?php 
/**
* Utiliza el index de la pagina inicial del sitio index.php 
*/
//$host = explode("/",$HTTP_REFERER);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">	
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Cambio de clave de usuario</td>
	</tr>
	<tr>	  	
      <td valign="top" height="400"><form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
		<?php //Creacion del campo REPOST
		$thisPost->startPost();?>	
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos a modificar </label>
		</LEGEND>
		<?php mensaje_requerido(); ?>
		  <table width="100%" border="0" cellpadding="0" cellspacing="0">		  
		  <tr>
			<td width="17%" class="Etiqueta1">Usuario: </td>
			<td colspan="2" class="LetraNegra">&nbsp;<?Php echo $Ses_Usu_Ced; ?>
			<input name="Usu_Ced2" type="hidden" value="<?php echo $Ses_Usu_Ced; ?>"></td>
		  </tr>
		  <tr>
			<td class="Etiqueta1">Apellidos y Nombres: </td>
			<td colspan="2" class="LetraNegra">&nbsp;<?Php echo $Ses_Prs_Ape." ".$Ses_Prs_Nom; ?>
			</td>
		  </tr>
		  <tr>
			<td class="Etiqueta1">Clave: </td>
			<td width="35%">&nbsp;<input name="Usu_Pal" id="Usu_Pal" type="password" size="25" maxlength="32" onKeyUp="seguridad_clave(this.value)" onBlur="if (this.value.length != 0) { minimo(this, 6); }">		</td>
			<td width="48%">
			<table border="1" cellpadding="0" cellspacing="0" bordercolor="#333333">
			<tr><td>
			<table width="143" border="0" cellpadding="0" cellspacing="0">
				  <tr>
					<td id="niv1" width="16" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv2" width="25" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv3" width="42" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
					<td id="niv4" width="60" bgcolor="#FFFFFF" style="line-height:5px;"><label></label>&nbsp;</td>
				  </tr>
			</table>
			</td>
			</tr>
			</table>			
			</td>
		  </tr>
		  <tr>
			<td class="Etiqueta1"><span class="Asterisco">*</span> Confirmar Clave: </td>
			<td colspan="2">&nbsp;<input name="Usu_Pal2" id="Usu_Pal2" type="password" size="25" maxlength="32"  onBlur="if (this.value.length != 0) { minimo(this, 6); }"></td>
		  </tr>
	    </table>
</FIELDSET>
<br>
<table width="300" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td>
	<?php 
	//Antes if ($host[2] == "http://localhost/".strtolower($Ses_Sys_Sitio)."/") 
	if ($Ses_Sys_Sit=="outside") {?>
<button type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_usuarios_inicio()">
		           <i class="icon-book icon-white"></i>
		           <span>Guardar</span>
		</button>
        
		<input name="hdd_save" type="hidden" id="hdd_save" value="outside">
	<?php 
	}else { ?>        
       <button type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_usuarios()">
		           <i class="icon-book icon-white"></i>
		           <span>Guardar</span>
		</button> 
        
		<input name="hdd_save" type="hidden" id="hdd_save" value="inside">
  <?php } ?>
	</td>
  </tr>
</table>
<br>
      </form>
	</td>
  </tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/adm_par_usuarios.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	 	 
</BODY>
</HTML>
<?Php
/**
* Cerrado de las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>
