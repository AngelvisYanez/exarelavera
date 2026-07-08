<?Php 
/*Alias:	Modificacion del menu del sistema por el Usuario
Descripción: Permite modificar el menu del sistema
Fecha de actualización:	2013-06-06
Desarrollador:	Fabian Gallardo Gonzaga

*/	
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_usuarios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/**
 * objeto para la conexion
 * @var Class_Log_Conexion_Adm
 */
$obBD_conexion = new Class_Log_Conexion_Admu;

/**
 * objeto para consultas
 * @var Class_Log_Datos_Adm
 */
$obBD_con1 =  new Class_Log_Datos_Admu;

/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;  

/* Evitar el reenvio de formularios */
if ($thisPost->postBlock($_POST['postID'])) {  	 	  
	/* Almacena los datos modificados */
	if (isset($_POST['hdd_save']))
	{
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		
		$param_usuario = $_POST['Usu_Men'].'*'.$_SESSION['Ses_Usu_Cod'];
		
		/* Inserción de datos de la inscripción */
		$obBD_con1->operacionobBD(30, $param_usuario, $obBD_conexion);
		
		/**
		 * grabar auditoria
		 */
		//$obBD_con1->grabarAuditoria($_SERVER['PHP_SELF'], $Ses_Usu_Cod, $obBD_conexion);
		
		/* Cierre de la transacción */
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	
	}//Fin del if (isset($hdd_save) && $Usu_Pal!="")
}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
?>
<HTML>
	<HEAD>		
		<TITLE>Configuracion Usuario</TITLE>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>

		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>					
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">		
	</HEAD>
<BODY>
<?php 
/* Utiliza el index de la pagina inicial del sitio index.php */
//$host = explode("/",$HTTP_REFERER);
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">	
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Configuraci&oacute;n de usuario</td>
	</tr>
	<tr>	  	
      <td valign="top"><form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name="form2" id="form2">
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
			<td class="Etiqueta1">Menu: </td>
			<td width="35%">&nbsp;
            <?php 
				$row_rs_consulta= $obBD_con1->getRowConsulta(29, $_SESSION['Ses_Usu_Cod'], $obBD_conexion);
				//$menu = $row_rs_consulta['Usu_Men'];
			?>
            <select name="Usu_Men" id="Usu_Men" >
            	<option value='T' <?php if($row_rs_consulta['Usu_Men'] == "T"){ echo 'selected';}?> >Tree Men&uacute; (Cl&aacute;sico)</option>
                <option value='B' <?php if($row_rs_consulta['Usu_Men'] == "B"){ echo 'selected';}?> >Drill Men&uacute; (Nuevo)</option>
            </select>		</td>
			<td width="48%">
			</td>
		  </tr>
	    </table>
</FIELDSET>
<br>
<button type="button" class="btn btn-primary start" title="Guardar" onClick= "validar_requeridos(this.form,'Usu_Men',1);">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
     </button>
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <br>
      </form>
	</td>
  </tr>
</table>	 
</BODY>
</HTML>
<?Php
/* Cerrado de las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>