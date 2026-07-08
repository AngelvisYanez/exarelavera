<?php 
/*Alias:	Modificar
Descripción: Permite modificar las ubicaciones
Fecha de actualización:	2010-06-13
Desarrollador:	Lewis Chimarro
MULTIEMPRESA : 
*/

require_once('../../administrador/LOGICA/seguridad.php');	
require_once('../LOGICA/logica.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');

/* Creacion del Objeto de conexion */  
$obBD_conexion = new Class_Log_Conexion_Tes;
/* Creacion del Objeto de datos */  
$obBD_con1 =  new Class_Log_Datos_Tes; 
/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;	

/* Busqueda del cliente */
if ($txt_busqueda != "")
{
	/* Parametros */	
	$param[]= trim($txt_busqueda);
	$rs_buscar = $obBD_con1->consulta(sentencias_tes(281, $obBD_con1->mensajes($param)), $obBD_conexion->conexion);
	$row_rs_buscar = $obBD_con1->registros();
	$total_rs_buscar = $obBD_con1->numregistros();		
}
	
	/* Consulta realizada en base el cliente seleccionado */
		elseif (isset($codigo))
		{
			/* Parametros */
			$param[] = $codigo;
			$rs_consulta = $obBD_con1->consulta(sentencias_tes(282, $obBD_con1->mensajes($param)), $obBD_conexion->conexion);
			$row_rs_consulta = $obBD_con1->registros();
			$total_rs_consulta = $obBD_con1->numregistros();	
		}

/* Evitar el reenvio de formularios */	
if ($thisPost->postBlock($_POST['postID']))
{
	if(isset($hdd_save) && !isset($hdd_atras))
	{
			/* Evitar el reenvio de formularios */	
			$obBD_ins1 =  new Class_Log_Datos_Tes;
			 /* parametros */
			 $param[] = $Ubi_Cod;
			 $param[] = $Ubi_Des;
			 $param[] = $Ubi_Obs;			 
			 $obBD_ins1->grabarv_registros(sentencias_tes(283, $obBD_ins1->mensajes($param)), $obBD_conexion->conexion);	
			 $obBD_ins1->fin_transaccion($obBD_conexion->conexion);	
	}	
}//Fin del if ($thisPost->postBlock($_POST['postID']))
?>
<HTML>
<HEAD>		
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Modificar Ubicaci&oacute;n</td>
  </tr>
	<tr>
        <td height="389" valign="top">
         <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1"> 
 <FIELDSET>
  <LEGEND>
  <label class="Titulos2">Buscar por :</label>
  </LEGEND>
	<?PHP mensaje_requerido(); ?>
  <table width="495" height="36" border="0" cellspacing="0" lass="">
    <tr>
      <td width="110" height="28" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda: </div></td>
      <td width="376" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase "></td>
      <td width="84"><div align="center">
        <input name="btn_buscar" type="button" class="Boton_Buscar" id="btn_buscar" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)" value="Buscar">
      </div></td>
    </tr>
  </table>
  </fieldset>
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
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr class="Cabecera1">
        <td width="2%">C&oacute;d. Int.</td>
        <td>Descripción</td>
        <td width="2%">&nbsp;</td>
      </tr>
      <?Php 
	  if($total_rs_buscar != 0)
		{
	  
	  do { ?>
      <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
          <td align="center"><?Php echo $row_rs_buscar['Ubi_Cod']; ?></td>
          <td align="left"><?Php echo marcar_cadena(trim($txt_busqueda), $row_rs_buscar['Ubi_Des'],'#FFFF00', 1); ?></td>
		  <td align="center"><input type="image" name="imageField" src="../../mascaras/model1/imagenes/forward.png" width="22" height="22" title="Elegir">
		    <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Ubi_Cod']; ?>">
            <input type="hidden" name="volver_busqueda" id="volver_busqueda" value="<?Php echo $txt_busqueda;?>"/>
		    </td>
        </tr>
      </form>
	  <?Php } while ($row_rs_buscar =$obBD_con1->fetch_assoc($rs_buscar)); 
	  }
	else
	{?>  
			<tr class="Fondo">
				<td colspan="3" align="center"><?php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1); ?></td>		
			</tr>	
	<?php }?>
    </table>
</FIELDSET>
  <?Php 
  	echo barra_estado($total_rs_buscar);
  }
  ?>

  
<form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
<?Php
if (isset($codigo) && !(isset($txt_busqueda)))
{
	/* Creacion del campo repost */
	$thisPost->startPost();	//}//Fin del if ($thisPost->postBlock($_POST['postID']))
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a modificar </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="16%" class="Etiqueta1">C&oacute;d. Int.:</td>
    <td width="84%" colspan="5" class="LetraNegra"><input  disabled="disabled" border="0" name="Con_Cod" type="text" id="Con_Cod"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Ubi_Cod']?>" />
      <input name="Ubi_Cod" type="hidden" id="Ubi_Cod"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Ubi_Cod']?>" /></td>
    </tr>
  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:  </td>
    <td colspan="5" class="LetraNegra">
      <input  name="Ubi_Des" type="text" id="Ubi_Des"  size="50" maxlength="50" value="<?Php echo $row_rs_consulta['Ubi_Des']?>" />      </td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td colspan="5" class="LetraNegra"><textarea name="Ubi_Obs" cols="50" id="Ubi_Obs" border="0"><?Php echo $row_rs_consulta['Ubi_Obs']?></textarea></td>
  </tr>
  </table>
</FIELDSET>
  <br />
  <table width="80" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td align="left"><input type="button" name="btn_atras" id="btn_atras" value="Enviar" class="Boton_Atras" title="Atr&aacute;s"
  onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*hdd_atras"; ?>','<?Php echo $volver_busqueda.'*1'; ?>')"></td>
      <td align="left"><input name="Boton2" type="button" class="Boton_Guardar" id="Boton2" value= "Guardar" onClick= "validar_requeridos(this.form, 'Ubi_Des', 1)" title="Guardar"></td>
    </tr>
  </table>
  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
<?Php 
} 
?>
</form>        </td>
  </tr>

</table>
</BODY>
</HTML>
<?Php
/*** Libero los recordsets **********/
@$obBD_con1->free_result ($rs_buscar);
@$obBD_con1->free_result($rs_consulta);
/* Cierra las conexiones */
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>