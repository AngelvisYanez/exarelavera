<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite registrar las marcas de los productos 
 * 
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-06-01
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_marca.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tes
   */
  $obBD_conexion = new Class_Log_Conexion_Mar;
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tes
   */
  $obBD_con1 =  new Class_Log_Datos_Mar;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;
  
if ($txt_busqueda != "")
{
	/*
	* Consulta la marca
	*/
	$row_rs_buscar =  $obBD_con1->getArrayConsulta(3,trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion); 
}
else
{
	/*
	* Carga la marca seleccionada
	*/
	if (isset($codigo))
	{
		$row_rs_consulta = $obBD_con1->getRowConsulta(4,$codigo.'*'.$Ses_Emp_Cod, $obBD_conexion);
	}
}

/* 
* Almacena los datos modificados
*/
if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{	
		$obBD_con1->insertUpdateDelete(6,$Mar_Des.'*'.$codigo, $obBD_conexion);
		/* Destruye variables */
		unset($codigo);
		unset($row_rs_consulta);
	}
}			
?>
<HTML>
	<HEAD>		
    	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/tes_val_marca.js"></script>
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
	  <td height="10">&raquo; modificar marcas </td>
  </tr>
	<tr>
        <td height="389" valign="top">
          <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">  
   <br>
   <FIELDSET> 
   <legend>
  <label class="Titulos2">Buscar por:</label>
   </legend>
   <table width="528" height="36" border="0" cellspacing="0">
     <tr>
      <td width="79" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Marca:</div></td>
      <td width="336" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50"></td>
      <td width="107" align="center">
       <button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>
      </td>
    </tr>
</table>
</FIELDSET>
 </form> 
  <?Php
  	if(isset($txt_busqueda))
	{
  ?>
 <br>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda</label>
</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
          <th width="4%">C&oacute;d. Int.</th>
          <th width="82%">Descripción</th>
		  <th width="4%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
	  <?Php 
	if(count($row_rs_buscar) != 0)
	{
	  foreach ($row_rs_buscar as $row)
	  { ?>
	  <tr>
		<td align="center"><?Php echo $row['Mar_Cod']; ?></td>
		<td><?Php echo $row['Mar_Des']; ?></td>
		<td align="center">
        <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>"> 
        <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row['Mar_Cod'];?>" />
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
        </form>
        </td>		
	  </tr>
	  <?Php 
	  } 
  	}//FIn del if(count($row_rs_buscar != 0)
	else
	{ ?>
  	  <tr>
	    <td>&nbsp;</td>
	    <td><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	    <td align="center">&nbsp;</td>
	    </tr>
  <?Php
	} ?>
      </tbody>
  </table>
  <?Php echo barra_estado(count($row_rs_buscar)); ?>
</FIELDSET>
<?Php
  }
?>
<form method="post" name="form2" action="<?Php $_SERVER['form1'] ?>">
<?Php
if (isset($codigo) && !(isset($txt_busqueda)))
{
	/* Creacion del campo repost */
	$thisPost->startPost();
?>
<FIELDSET>
  <LEGEND>
<label class="Titulos2">Datos a modificar</label>
</LEGEND>
<?Php echo mensaje_requerido(); ?>
<table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="104" class="Etiqueta1"> <span class="Asterisco">* </span>Descripci&oacute;n:</td>
    <td width="394">
    <div id="div_mar">
    <input name="Mar_Des" type="text" id="Mar_Des" value="<?Php echo $row_rs_consulta['Mar_Des']?>" size="30" maxlength="30" style="text-transform:uppercase " onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&Mar_Des='+this.value+'&Mar_Cod=<?Php echo $row_rs_consulta['Mar_Cod']?>','div_mar')"></div>
    </td>
  </tr>
  <tr>
    <td class="Etiquetas">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
</FIELDSET> 
  <br>

  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <table width="100" border="0" class="Azul">
  <tr>
    <td width="100%" height="23"><div align="center">
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Mar_Des', 1)">
                   <i class="icon-book icon-white"></i>
                   <span>Guardar</span>
       </button> 
    </div></td>
  </tr>
</table>
  <input type="hidden" name="codigo" id="codigo" value="<?Php echo $codigo; ?>" />
<?Php
}
?>
</form>        
	</td>
  </tr>
</table>	
</div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_marca.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>