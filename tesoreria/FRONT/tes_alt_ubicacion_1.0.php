<?Php 
/*Alias:	Registar 
Descripción: Permite registrar las ubicaciones
Fecha de actualización:	2011-06-12
Desarrollador:	Lewis CHimarro
MULTIEMPRESA : SI
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

if(isset($hdd_save))
{
 /* Evitar el reenvio de formularios */	
 if ($thisPost->postBlock($_POST['postID']))
	{	
	 $rs_ubicacion = $obBD_con1->consulta(sentencias_tes(280, $obBD_con1->parametros(trim(Ubi_Des))), $obBD_conexion->conexion);
     $row_rs_ubicacion = $obBD_con1->registros();
	 $total_rs_ubicacion =  $obBD_con1->numregistros();
	 
 		if ($total_rs_ubicacion<=0)
		{		
		 $obBD_ins1 =  new Class_Log_Datos_Tes;
		 /* Inicio de la transaccion */
		 $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		 $obBD_ins1->grabarv_registros(sentencias_tes(279, $obBD_ins1->parametros(trim($Ubi_Des).'*'.trim($Ubi_Obs))), $obBD_conexion->conexion);	
		 /* Fin de la transaccion */
   		 $obBD_ins1->fin_transaccion($obBD_conexion->conexion);		 
   		}
		else
		{
			echo "<script>alert('Descripción ya Existe!!')</script>";
		}			
	}
}
?>	 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		  	
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">		
</head>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr class="BarraTitulo">
	  <td>&raquo;<span class="Estilo1"></span> Registrar Ubicaci&oacute;n </td>
    </tr>
	<tr>
      <td valign="top"><form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>" >
		<FIELDSET>
		  <LEGEND>
		    <label class="Titulos2">Datos a registrar </label>
		 </LEGEND>
		<?Php 
    			mensaje_requerido(); 
	/* Creacion del campo repost */
				$thisPost->startPost();	//}//Fin del if ($thisPost->postBlock($_POST['postID']))
		?>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			 <tr>
    			<td width="15%" class="Etiqueta1"><p><span class="Asterisco">*</span> Descripcion: </p></td>
   			   <td width="85%" class="LetraNegra"><input border="0" name="Ubi_Des" type="text" id="Ubi_Des" value="" size="20" maxlength="20" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n: </td>
    <td width="85%" class="LetraNegra"><textarea name="Ubi_Obs" cols="50" id="Ubi_Obs" border="0"></textarea></td>
  </tr>
  </table>
<br />
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
</FIELDSET>
</form>
        <table width="80" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td align="left"><input name="Boton22" type="button" class="Boton_Guardar" id="Boton22" value= "Guardar" onClick= "validar_requeridos(form2, 'Ubi_Des', 1)" title="Guardar"></td>
          </tr>
        </table></td>
	</tr>
</table>
</body>
</html>
<?Php 
@$obBD_con1->free_result($rs_ubicacion);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar(); ?>