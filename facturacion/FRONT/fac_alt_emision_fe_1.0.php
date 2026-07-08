<?Php 
/*Alias:	Registar 
Descripción: Permite registrar las configuracion de facturacion
Fecha de actualización:	2015-03-30
Desarrollador:	Lewis CHimarro
MULTIEMPRESA : SI
*/	
require_once('../../administrador/LOGICA/seguridad.php');	  
require_once('../LOGICA/fac_log_emision_fe.php');
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
		 $tmp_name = $_FILES["archivo"]["tmp_name"]; 			
		 $name = $_FILES["archivo"]["name"]; 		
		 $tipo = explode("/",$_FILES['archivo']['type']);
		 if($tipo[0]=="text" || $hdd_ClvCon!="")
		 { 
			 $obBD_ins1 =  new Class_Log_Datos_Cfg;
			 /* Inicio de la transaccion */
			 $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);												
			 if($opt_1=='1'){
			    /*actualizamos el registro*/
				$obBD_ins1->operacionobBD(2, $opt_1.'*'.$hdd_ClvCon.'*'.$Ses_Emp_Cod,$obBD_conexion); 
			 }else{
				if($_FILES["archivo"]["tmp_name"]!="")
				{   
				   if(move_uploaded_file($_FILES["archivo"]["tmp_name"],$Ses_Emp_Cod."/".$name))
				   {				    
						/*actualizamos el registro*/
						$obBD_ins1->operacionobBD(2, $opt_1.'*'.$name.'*'.$Ses_Emp_Cod,$obBD_conexion); 					
					}else{
						?><script language="javascript">alert("AVISO:\n¡No se pudo subir el archivo!");</script><?php
					}
				}else{
					/*actualizamos el registro*/
					$obBD_ins1->operacionobBD(2, $opt_1.'*'.$hdd_ClvCon.'*'.$Ses_Emp_Cod,$obBD_conexion); 
				}
			 }
		  
			/* Fin de la transaccion */
			$obBD_ins1->fin_transaccion($obBD_conexion->conexion);		 
		 }else{
			?><script language="javascript">alert("AVISO:\n¡El archivo no es compatible, (solo .txt)!");</script><?php	 
		 }
	}
}

/*consultamos si existe configuracion existente segun Ses_Emp_Cod*/
$row_rs_buscaConfig= $obBD_con1->getRowConsulta(1, $Ses_Emp_Cod,$obBD_conexion);
$total_rs_buscaConfig=$row_rs_buscaConfig['Cof_Cod'] > 0? 1 : 0;
if ($total_rs_buscaConfig!=0)
{
	$emisionFE=$row_rs_buscaConfig['Cof_Fte'];	
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
      <form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" name="form2" >
		<FIELDSET>
		  <LEGEND>
		    <label class="Titulos2">Tipo de Emisi&oacute;n Factura Electr&oacute;nica</label>
		 </LEGEND>
			<?php 	/* Creacion del campo repost */
				$thisPost->startPost();	
			?>
			<table width="100%" border="0" cellpadding="0" cellspacing="0">
  			 <tr>
  			   <td class="Etiqueta1">Generar Factura Electr&oacute;nica:&nbsp;&nbsp;</td>
  			   <td class="LetraNegra" <?php if($row_rs_buscaConfig['Cof_Gce']=='N'){ echo "style='color:#F00'";}?>><?php if($row_rs_buscaConfig['Cof_Gce']=='S'){ echo 'ACTIVA';}else{ echo 'INACTIVA';}?></td>
  			   <td class="LetraNegra">&nbsp;</td>
  			   <td class="LetraNegra">&nbsp;</td>
		      </tr>
  			 <tr>
    			<td width="15%" class="Etiqueta1"><p><span class="Asterisco">*</span> Tipo de Emisi&oacute;n:&nbsp;&nbsp;</p></td>
    			<td width="7%" class="LetraNegra">
                <input type="radio" name="opt_1" id="opt_1" value="1"  <?php if($emisionFE=='1'){ echo 'checked';}?> onClick=" document.getElementById('archivo').disabled=true; ">
                <label for="opt_1">NORMAL</label></td>
   			   <td width="20%" class="LetraNegra"><input name="opt_1" type="radio" id="Opt_1" value="2" <?php if($emisionFE=='2'){ echo 'checked';}?> onClick=" document.getElementById('archivo').disabled=false; ">
		       <label for="Opt_2">INDISPONIBILIDAD DEL SISTEMA</label></td>
   			   <td width="58%" class="LetraNegra">&nbsp;</td>
             </tr>
  			 <tr>
  			   <td height="22" class="Etiqueta1">Clave de Contingencia:&nbsp;&nbsp;</td>
  			   <td colspan="2" class="LetraNegra" <?php if($row_rs_buscaConfig['Cof_Clv']==''){ echo "style='color:#F00'";}?>>
			   <input type="hidden" id="hdd_ClvCon" name="hdd_ClvCon" value="<?php echo $row_rs_buscaConfig['Cof_Clv'];?>">
			   <em>
			   <?php if($row_rs_buscaConfig['Cof_Clv']!=''){ echo $row_rs_buscaConfig['Cof_Clv']."&nbsp;&nbsp;&nbsp;(".count(file($Ses_Emp_Cod."/".$row_rs_buscaConfig['Cof_Clv']))." c&oacute;digos)";}else{ echo 'NINGUNA';}?>
			   </em></td>
  			   <td class="LetraNegra">&nbsp;</td>
		      </tr>
  			 <tr>
  			   <td height="22" class="Etiqueta1">&nbsp;</td>
  			   <td colspan="2" class="LetraNegra"><input name="archivo" <?php if($emisionFE=='1'){ echo 'disabled';}?> type="file" id="archivo" accept=".txt"></td>
  			   <td width="58%" class="LetraNegra">&nbsp;</td>
		      </tr>
          </table>
		  <input name="hdd_save" type="hidden" id="hdd_save" value="insertar" />
          <input name="hdd_total" type="hidden" id="hdd_total" value="<?php echo $total_rs_buscaConfig;?>" />
          
        </FIELDSET>
       <table width="119" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="108" height="28" align="left">
            <?php if($row_rs_buscaConfig['Cof_Gce']!='N'){?>
            <button type="button" class="btn btn-primary start" title="Guardar" onclick="if(document.getElementById('archivo').value==1){ validar_requeridos(this.form, 'archivo', 1);}else{confirmacion(this.form);}">
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
<?Php 
@$obBD_con1->free_result($rs_ubicacion);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar(); ?>