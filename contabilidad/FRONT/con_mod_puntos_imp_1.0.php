<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/** 
* Descripción: Modificar puntos de impresión
* Fecha de creación:	2012-05-11
* Desarrollador:	Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_puntos_imp.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/* Creación del objeto para evitar el reenvio */
$thisPost = new Post_Block;
/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;	  

/* Ajax que permite verificar si el tipo de embarque */
if(isset($ajax_emb))
{
	/**
	* Consulta datos de los puntos de impresión 
	*/
	$row_puntos_imp = $obBD_con1->getRowConsulta(6, trim($Pun_Des).'*'.$Ses_Suc_Cod.'*'.$Pun_Cod, $obBD_conexion);
	
	if (count($row_puntos_imp) == 0)
	{ ?>
		<input name="Pun_Des" type="text" id="Pun_Des" size="30" maxlength="30" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&amp;Pun_Des=' + this.value + '&Pun_Cod=<?php echo $row_punto['Pun_Cod']?>','div_emb')" value="<?php echo $Pun_Des; ?>" />&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
    <?Php    	
	}
	else
	{ ?>
		<input name="Pun_Des" type="text" id="Pun_Des" size="30" maxlength="30" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&amp;Pun_Des=' + this.value + '&Pun_Cod=<?php echo $row_punto['Pun_Cod']?>','div_emb')" />&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	¡Punto de impresión <?php echo "<font style='text-transform:uppercase'>".$Pun_Des."</font>"; ?> ya existe!        
<?Php	}
exit();
}

 if ($thisPost->postBlock($_POST['postID'])) 
 { 
 	if (isset($hdd_save)  && !isset($hdd_volver)) 
	{
			/**
			* Actualiza los puntos de archivos
			*/
			$obBD_con1->insertUpdateDelete(7, $Suc_Cod.'*'.$Pun_Des.'*'.$Pun_Ubi.'*'.$codigo, $obBD_conexion);
			unset($codigo);
	}
}

/* Busqueda de los datos del cliente */
if ($txt_busqueda != "")
{
   /**
	* Datos del cliente por apellido
	*/
   $row_rs_buscar = $obBD_con1->getArrayConsulta(4, trim($Ses_Suc_Cod.'*'.trim($txt_busqueda)), $obBD_conexion);   
}
else
{		
	if (isset($codigo))
	{		
		/**
		* Consulta el punto de impresión
		*/
		$row_punto = $obBD_con1->getRowConsulta(5, $codigo, $obBD_conexion);		
	}//Fin del if (isset($codigo))
}//Fin del if ($txt_busqueda != "")	

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>    
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
    <div id="set1">
<BODY>	
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
 <tr class="BarraTitulo">
   <td height="10">&raquo; Modificar de Puntos de Impresi&oacute;n</td>  
 </tr>
<tr>
  <td valign="top" height="400">	
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form1" id="form1">
    <fieldset>
      <legend>
      <label class="Titulos2">Buscar por:</label>
      </legend>
      <table width="572" height="36" border="0" cellspacing="0">
        <tr>
          <td width="80" height="28" class="BarraBusqueda"><div align="right">Descripci&oacute;n:</div></td>
          <td width="334" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" /></td>
          <td width="152"><div align="center">            
            <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
       </button>
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
    <table class="fixedHeader01" width="100%" border="1" cellpadding="0" cellspacing="0">
    <thead>
      <tr>
        <th width="8%">Cód. Int. </th>
        <th>Descripci&oacute;n</th>
        <th>Ubicaci&oacute;n</th>
        <th width="4%">&nbsp;</th>
        </tr>
       </thead>
       <tbody>
      <?Php 
	if(count($row_rs_buscar) > 0)
	{	  
		foreach($row_rs_buscar as $row)
		{	  
	  ?>
      <tr>
        <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
          <td align="center"><?Php echo $row['Pun_Cod']; ?></td>
          <td align="left">&nbsp;<?Php echo marcar_cadena($txt_busqueda,$row['Pun_Des'],'#FFFF00',1); ?>	
          </td>
          <td align="left"><?Php echo $row['Pun_Ubi']; ?></td>
          <td align="center"><?Php if ($row['Pun_Est'] == 'A') { ?>
            <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row['Pun_Cod'];?>">
            <input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">           			            
            <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
           <i class=" icon-arrow-right icon-white"></i>
           </button>
            <?php } else { echo "&nbsp;"; } ?>	  
            </td>
          </form>
        </tr>
      <?Php } //Fin del foreach($row_rs_buscar as $row)
  	}//FIn del if($total_rs_buscar != 0)
	else
	{ ?>
      <tr><td>&nbsp;</td>
        <td><?Php echo error_alerta("No hay resultados que mostrar", 1) ?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>	   
      <?Php 
	}//Fin del else if($total_rs_buscar != 0) ?>
    	</tbody>
      </table>
  </FIELDSET>
  <?php
	echo barra_estado(count($row_rs_buscar));
}//Fin del if(isset($txt_busqueda)) ?>

<?Php 
if (count($row_punto) > 0)
{  ?>
<br />
         <form method="post" name= "form" action="<?php echo $_SERVER['PHP_SELF'];?>">
  <FIELDSET>
   <LEGEND>
	<label class="Titulos2">Datos a modificar</label>
   </LEGEND> 
   <?Php
/*Muestra el mensaje de requerido*/
mensaje_requerido(); 
?>
   <table width="100%" cellpadding="0" cellspacing="0" border="0">
     <tr>
       <td class="Etiqueta1"><span class="Asterisco">* </span> Sucursal:</td>
       <td colspan="3"><?php
		/* 
		* Carga las sucursales de la empresa
		*/
		$row_rs_sucursal = $obBD_con1->getArrayConsulta(1, $Ses_Emp_Cod, $obBD_conexion);  ?>
         <select name="Suc_Cod" id="Suc_Cod">
           <option >Seleccione...</option>
           <?php 
		  foreach ($row_rs_sucursal as $row)
		  {?>
           <option <?php if ($row_punto['Suc_Cod']==$row['Suc_Cod']) echo "selected"; ?> value="<?php echo $row['Suc_Cod'];?>"><?php echo $row['Suc_Des']?></option>
           <?php } //fin del foreach $row_rs_sucursal ?>
         </select></td>
     </tr>
     <tr>
       <td width="16%" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
       <td colspan="3"><div id="div_existe">
         <table width="70%" border="0" cellpadding="0" cellspacing="0">
           <tr>
             <td><div id="div_emb">
               <input name="Pun_Des" type="text" id="Pun_Des" size="50" maxlength="50" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_emb=1&amp;Pun_Des=' + this.value + '&Pun_Cod=<?php echo $row_punto['Pun_Cod']?>','div_emb')" value="<?php echo $row_punto['Pun_Des'];?>" />
             </div></td>
           </tr>
         </table>
       </div></td>
     </tr>
     <tr>
       <td class="Etiqueta1"><span class="Asterisco">*</span> Ubicaci&oacute;n:</td>
       <td colspan="3"><textarea name="Pun_Ubi" id="Pun_Ubi" cols="50" rows="4"><?php echo $row_punto['Pun_Ubi'];?></textarea></td>
     </tr>
   </table>
  </FIELDSET> 
   <br />
<table width="265" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="96"> <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, '<?Php echo "txt_busqueda*hdd_volver"; ?>', '<?Php echo $volver_busqueda.'*'; ?>')">
                    <i class=" icon-arrow-left icon-white"></i>
                    <span>&nbsp;&nbsp;Atras&nbsp;&nbsp;</span>
       </button></td>
      <td width="169" height="23">
          <input name="hdd_save" type="hidden" id="hdd_save">
 <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Suc_Cod*Pun_Des*Pun_Ubi',1)" id="btn_guardar">
           <i class="icon-book icon-white"></i>
           <span>Guardar</span>
           </button> <input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo;?>">
           <input name="txt_busqueda" id="txt_busqueda" type="hidden" value="<?Php echo $volver_busqueda;?>">        
      </td>
    </tr>
  </table>  
  </form> 
<?php
} //if (count($row_rs_tipo) > 0)
?>  
  </td>
</tr>
</table>	   
</div>
<!-- Librerias para el tratamiento de la interfaz - cajas de texto -->
<script type="text/javascript" src="../VALIDACIONES/con_par_puntos_imp.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>   
</BODY>
</HTML>
<?php
/**
* Cierra la conexion a la base de datos 
*/
@$obBD_conexion->cerrar();
?>