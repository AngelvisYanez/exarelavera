<?Php 
/*Alias:	Registar 
Descripci�n: Permite configurar el modo de Ambiente de factura electronica
Fecha de actualizaci�n:	2015-03-30
Desarrollador:	Jose Cumbicos
MULTIEMPRESA : SI
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_ambiente_fe.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

/* Creacion del Objeto de conexion */  
$obBD_conexion = new Class_Log_Conexion_Cfg($Ses_Dat_Dis);
/* Creacion del Objeto de datos */  
$obBD_con1 =  new Class_Log_Datos_Cfg; 
/* Creaci�n del objeto para evitar el reenvio */
$thisPost = new Post_Block;


if(isset($hdd_save))
{
 	/* Evitar el reenvio de formularios */	
 	if ($thisPost->postBlock($_POST['postID']))
	{		 	 
		 $obBD_ins1 =  new Class_Log_Datos_Cfg;
		 /* Inicio de la transaccion */
		 $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		 
		/*actualizamos el registro*/
		$obBD_ins1->operacionobBD(2, $opt_1.'*'.$Ses_Emp_Cod,$obBD_conexion); 
		
		 /* Fin de la transaccion */
   		 $obBD_ins1->fin_transaccion($obBD_conexion->conexion);		    	
	}
}

/*consultamos si existe configuracion existente segun Ses_Emp_Cod*/
$row_rs_buscaConfig= $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod,$obBD_conexion);
$total_rs_buscaConfig=$row_rs_buscaConfig['Cof_Cod'] > 0? 1 : 0;
if ($total_rs_buscaConfig!=0)
{
	$ambienteFE=$row_rs_buscaConfig['Cof_Fac'];	
}

?>	 
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>		  	
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
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
		    <label class="Titulos2">Tipo de Ambiente Facturacion Electr&oacute;nica</label></LEGEND>
			<?php 	/* Creacion del campo repost */
				$thisPost->startPost();	
			?>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			 <tr>
  			   <td class="Etiqueta1"> Generar Factura Electr&oacute;nica:&nbsp;&nbsp;</td>
  			   <td class="LetraNegra" <?php if($row_rs_buscaConfig['Cof_Gce']=='N'){ echo "style='color:#F00'";}?>>&nbsp;<?php if($row_rs_buscaConfig['Cof_Gce']=='S'){ echo 'ACTIVA';}else{ echo 'INACTIVA';}?></td>
  			   <td class="LetraNegra">&nbsp;</td>
  			   <td class="LetraNegra">&nbsp;</td>
		      </tr>
  			 <tr>
    			<td width="15%" class="Etiqueta1"><p><span class="Asterisco">*</span> Tipo de Ambiente:&nbsp;&nbsp;</p></td>
    			<td width="8%" class="LetraNegra">
                <input type="radio" name="opt_1" id="opt_1" <?php if($ambienteFE=='1'){ echo 'checked';} ?> value="1">
                <label for="opt_1">Pruebas</label></td>
   			   <td width="8%" class="LetraNegra"><input name="opt_1" type="radio" id="Opt_1" <?php if($ambienteFE=='2'){ echo 'checked';}?> value="2">
		       <label for="Opt_2">Producci&oacute;n</label></td>
   			   <td width="69%" class="LetraNegra">&nbsp;</td>
             </tr>
  </table>
		  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
          <input name="hdd_total" type="hidden" id="hdd_total" value="<?php echo $total_rs_buscaConfig;?>" />          
        </FIELDSET>
        <table width="119" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="108" height="28" align="left">
            <?php if($row_rs_buscaConfig['Cof_Gce']!='N'){?>
            <button type="button" class="btn btn-primary start" title="Guardar" onClick="confirmacion(this.form)">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
    		</button>
    		<?php }?>
            </td>
          </tr>
        </table>
        </form>
        </td>
	</tr>
</table>
</body>
</html>
<?php
/**
* Cierra la conexi�n
*/
@$obBD_conexion->cerrar();
?>