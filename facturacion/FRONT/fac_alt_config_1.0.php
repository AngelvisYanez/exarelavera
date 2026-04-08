<?Php 
/*Alias:	Registar 
Descripción: Permite registrar las configuracion de facturacion
Fecha de actualización:	2015-03-30
Desarrollador:	Jose Cumbicos
MULTIEMPRESA : SI
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_config.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/* Creacion del Objeto de conexion */  
$obBD_conexion = new Class_Log_Conexion_Cfg($Ses_Dat_Dis);
/* Creacion del Objeto de datos */  
$obBD_con1 =  new Class_Log_Datos_Cfg; 
/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;


if(isset($hdd_save))
{
 /* Evitar el reenvio de formularios */	
 if ($thisPost->postBlock($_POST['postID']))
	{		 	 
		 $obBD_ins1 =  new Class_Log_Datos_Cfg;
		 /* Inicio de la transaccion */
		 $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		 
		 if($hdd_total==0){
			/*insertamos la nueva configuracion*/ 
			$obBD_ins1->operacionobBD(2,$Ses_Emp_Cod.'*'.$opt_1.'*'.$opt_2,$obBD_conexion);			 	
		 }else{
			/*actualizamos el registro*/
			$obBD_ins1->operacionobBD(3, $opt_1.'*'.$opt_2.'*'.$Ses_Emp_Cod,$obBD_conexion); 
		 }
		 /* Fin de la transaccion */
   		 $obBD_ins1->fin_transaccion($obBD_conexion->conexion);		 
   	
	}
}

/*consultamos si existe configuracion existente segun Ses_Emp_Cod*/
$row_rs_buscaConfig= $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod,$obBD_conexion);
$total_rs_buscaConfig=$row_rs_buscaConfig['Cof_Cod'] > 0? 1 : 0;
if ($total_rs_buscaConfig!=0)
{
	$llevarCont=$row_rs_buscaConfig['Cof_Con'];
	$factElect=$row_rs_buscaConfig['Cof_Gce'];
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
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td>&raquo;<span class="Estilo1"></span> Configuraciones</td>
    </tr>
	<tr>
      <td valign="top" height="400">
      <?Php mensaje_requerido(); ?>
      <form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>" >
		<FIELDSET>
		  <LEGEND>
		    <label class="Titulos2">Configuraci&oacute;n de Contabilidad</label>
		 </LEGEND>
			<? 	/* Creacion del campo repost */
				$thisPost->startPost();	
			?>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			 <tr>
    			<td width="15%" class="Etiqueta1"><p><span class="Asterisco">*</span> Llevar Contabilidad:&nbsp;&nbsp;</p></td>
    			<td width="6%" class="LetraNegra">
                <input type="radio" name="opt_1" id="opt_1" value="S" <? if($llevarCont=='S'){ echo 'checked';}?>>
                <label for="opt_1">SI</label></td>
   			   <td width="8%" class="LetraNegra"><input name="opt_1" type="radio" id="Opt_1" value="N" <? if($llevarCont=='N'){ echo 'checked';}?>>
		       <label for="Opt_2">NO</label></td>
   			   <td width="71%" class="LetraNegra">&nbsp;</td>
             </tr>
  </table>
		  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
          <input name="hdd_total" type="hidden" id="hdd_total" value="<? echo $total_rs_buscaConfig;?>" />
          
        </FIELDSET>
        <FIELDSET>
		  <LEGEND>
		    <label class="Titulos2">Configuraci&oacute;n de Facturaci&oacute;n</label>
		 </LEGEND>		
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			 <tr>
    			<td width="15%" class="Etiqueta1"><p><span class="Asterisco">*</span> Generar Factura Electr&oacute;nica:&nbsp;&nbsp;</p></td>
    			<td width="6%" class="LetraNegra">
                <input type="radio" name="opt_2" id="opt_2" value="S" <? if($factElect=='S'){ echo 'checked';}?>>
                <label for="opt_1">SI</label></td>
   			   <td width="8%" class="LetraNegra"><input name="opt_2" type="radio" id="opt_2" value="N" <? if($factElect=='N'){ echo 'checked';}?>>
		       <label for="Opt_2">NO</label></td>
   			   <td width="71%" class="LetraNegra">&nbsp;</td>
             </tr>
  </table>		 
</FIELDSET>


        <table width="119" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="108" height="28" align="left">
            <button type="button" class="btn btn-primary start" title="Guardar" onClick="confirmacion(this.form)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    </button></td>
          </tr>
        </table>
        </form>
        </td>
	</tr>
</table>
</body>
</html>
<?Php 
@$obBD_con1->free_result($rs_ubicacion);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar(); ?>