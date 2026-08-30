<?php	
/* 
* Alias:					Registrar
* Descripci�n: 				Permite registrar los perfiles
* Fecha de actualizaci�n:	2011-03-13
* Desarrollador:			Lewis Chimarro
* Fecha de actualizaci�n:	2013-06-24
* Desarrollador:			Fabian Gallardo G.
*/

require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_perfil.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/* Creaci�n del objeto para evitar el reenvio*/
$thisPost = new Post_Block;

if (isset($hdd_save))
{
if ($thisPost->postBlock($_POST['postID'])) 
{			
	/* 
	* Creacion del Objeto de conexion 
	*/
	$obBD_conexion = new Class_Log_Conexion_Admp($Ses_Dat_Dis);
	
	/*
	* Cracion del objeto mysql para las consultas 
	*/
	$obBD_con1 =  new Class_Log_Datos_Admp; 	  

	/*
	* Inicio de la transaccion
	*/
	$obBD_con1->inicio_transaccion($obBD_conexion);
	
	/*
	* Objeto que contiene los parametros
	*/
	$parametros = $Per_Des.'*'.$Ses_Emp_Cod;
	
	/*
	* Inserci�n en la tabla de Perfiles 
	*/
	$obBD_con1->operacionobBD(1, $parametros ,$obBD_conexion);	
	
	$Per_Cod = $obBD_con1->insercionid($obBD_conexion->conexion);
		
		/*
		* Evalua si esta creado el arreglo 
		*/
		if (isset($nomchk))
		{
			foreach ($nomchk as $puntero => $item)
			{
				$parametross = $Per_Cod.'*'.$item;
				/* 
				* Almacena los procesos en un perfil 
				*/
				$obBD_con1->operacionobBD(2, $parametros,$obBD_conexion);	
			}//Fin del foreach ($nomchk as $puntero => $item)
		}//Fin del if (isset($nomchk))
	/*
	* Fin del la transaccion
	*/
	$obBD_con1->fin_transaccion($obBD_conexion);
}//Fin del if ($thisPost->postBlock($_POST['postID']))
}//Fin del if (isset($hdd_save))
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php")?>        
		<script type="text/javascript" src="../VALIDACIONES/adm_val_usuarios.js"></script>	
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>	
        <!--Librerias para interfaz -->               
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">	
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10"><span>&raquo;</span> registro De perfiles</td>
	</tr>
	<tr>	  	
        <td valign="top">
          <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2">
		   <?php  //Creacion del campo REPOST
			$thisPost->startPost(); ?>  
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar</label>
</LEGEND>
<?Php echo mensaje_requerido(); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="17%" class="Etiqueta1"><span class="Asterisco">* </span>Nombre del perfil:&nbsp;</td>
	<td width="83%"><input name="Per_Des" id="Per_Des" type="text" size="30" maxlength="30"></td>
  </tr>
</table><br>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="href">
  <tr>
    <td width="2%">&nbsp;</td>
    <td width="98%"><table width="200" height="100%" border="0" cellspacing="0" cellpadding="0">
      <tr class="menu">
        <td width="95%" height="100%" align="left" valign="top"><?php $Com_Tipo = 'A'; require_once("adm_con_treemenu_adm_1.0.php"); ?></td>
      </tr>
    </table></td>
  </tr>
</table>
</fieldset>
	<br>
	<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
	<table width="100" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td>
    <button name="bt_grabar" type="button" id="bt_grabar" class="btn btn-primary start" title="Guardar" onClick="validar_requeridos(this.form, 'Per_Des', 1)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
     </button>
    </td>
  </tr>
</table>
    </form></td>
  </tr>
</table>
</div>	  
</BODY></HTML>
<?php
/*
* cierro las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>