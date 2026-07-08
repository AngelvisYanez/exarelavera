<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite registrar las marcas de los productos 
 * 
 * @author juanpuxito
 * @version 1.0
 * Fecha de actualización:	27-05-2014
 *
 * @package Exa.Facturacion - OFSERCONT
 * 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_tip_asiento.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tes
   */
  $obBD_conexion = new Class_Log_Conexion_Tip($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tes
   */
  $obBD_con1 =  new Class_Log_Datos_Tip;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;

/* 
* Almacena los datos modificados
*/
if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{	
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_con1-> inicio_transaccion($obBD_conexion->conexion);
	   
	   $obBD_con1->operacionobBD(6,$Tia_Des.'*'.$Tia_Ini.'*'.$Tip_Abrev.'*'.$codigo,$obBD_conexion);
	
		/**
		* fin de la transacción 
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}
}

if ($txt_busqueda != "")
{
	/*
	* Consulta los tipos
	*/
	$row_rs_buscar =  $obBD_con1->getArrayConsulta(3,trim($txt_busqueda), $obBD_conexion); 
}
else
{
	/*
	* Carga los tipos seleccionados
	*/
	if (isset($codigo))
	{
		$row_rs_consulta = $obBD_con1->getRowConsulta(4,$codigo, $obBD_conexion);
	}
}	

if (isset($ajax_mod))
{		
		/** 
		* Consultar si existe el nombre de tipo de asiento 
		*/
		$row_rs_con_tip = $obBD_con1->getArrayConsulta(5, strtoupper($Tia_Des.'*'.$Tia_Ini), $obBD_conexion);				
		
		if (count($row_rs_con_tip) > 0)		
		{			
			if($Tia_Des==$des_orig) {								
		?>
        <input name="Tia_Des" type="text" id="Tia_Des" value="<?Php echo $Tia_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&des_orig=<?php echo $des_orig?>&Tia_Des='+this.value+'&Tia_Ini=<?Php echo $Tia_Ini; ?>','div_tiaDes')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
			
		<?php
			}else{ ?>
			<input name="Tia_Des" type="text" id="Tia_Des" value="<?php echo $Tia_Des?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&des_orig=<?php echo $des_orig?>&Tia_Des='+this.value+'&Tia_Ini=<?Php echo $Tia_Ini; ?>','div_tiaDes')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/gtk-no.gif" width="22" height="22" />	¡La marca: <?php echo strtoupper($Tia_Des); ?> ya existe!  	
			<?php	
			}
	    }else{		
	 ?>
			<input name="Tia_Des" type="text" id="Tia_Des" value="<?Php echo $Tia_Des; ?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&des_orig=<?php echo $des_orig?>&Tia_Des='+this.value+'&Tia_Ini=<?Php echo $Tia_Ini; ?>','div_tiaDes')">&nbsp;<img src="../../mascaras/model1/imagenes/32x32/aceptar.jpg" width="22" height="22" />
		<?php
		}			
exit();
}	
?>

<HTML>
	<HEAD>		
    	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>   
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/tes_par_tip_asient.js"></script>
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
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; modificar tipos de asientos </td>
  </tr>
	<tr>
        <td height="389" valign="top">
          <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">    
   <FIELDSET> 
   <legend>
  <label class="Titulos2">Buscar por:</label>
   </legend>
   <?Php echo mensaje_requerido(); ?>
   <table width="528" height="36" border="0" cellspacing="0">
     <tr>
      <td width="79" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Nombre:</div></td>
      <td width="336" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50"></td>
      <td width="107" class="BarraBusqueda" align="center" >
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
          <th width="4%">Abrev.</th>
          <th width="82%">Descripción</th>
           <th width="4%">Tipo</th>            
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
		<td align="center"><?Php echo $row['Tia_Cod']; ?></td>
        <td align="center"><?Php echo $row['Tia_Abr']; ?></td>
		<td ><?Php echo $row['Tia_Des']; ?></td>
        <td align="center"><?Php if($row['Tia_Ini']=="I"){ echo "INGRESO";} if($row['Tia_Ini']=="E"){ echo "EGRESO";}
		if($row['Tia_Ini']=="D"){ echo "DIARIO ";} ?></td>        
		<td align="center">
        <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>"> 
        <input type="hidden" name="des_orig" id="des_orig" value="<?Php echo $row['Tia_Des'];?>" />
        <input type="hidden" name="codigo" id="codigo" value="<?Php echo $row['Tia_Cod'];?>" />
         <input type="hidden" name="txt_busq" id="txt_busq" value="<?Php echo $txt_busqueda; ?>" />
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
  <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Tipo :         
          </td>
     <td>
         <select name="Tia_Ini" id="Tia_Ini" >                       	  						
              <option <?Php if($row_rs_consulta['Tia_Ini']=="I"){echo 'selected' ;}?> value='I'>Ingreso</option>              <option <?Php if($row_rs_consulta['Tia_Ini']=="E"){echo 'selected' ;}?> value='E'>Egreso</option>              				              <option <?Php if($row_rs_consulta['Tia_Ini']=="D"){echo 'selected' ;}?> value='D'>Diario</option>	       
          </select>	
      </td>       
  </tr>
  <tr>
    <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
    <td width="458">
    <div class="Titulos2" id="div_tiaDes">
     <input name="Tia_Des" type="text" id="Tia_Des" value="<?Php echo $row_rs_consulta['Tia_Des']?>" size="30" maxlength="30" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&ajax_mod=1&des_orig=<?php echo $des_orig?>&Tia_Des='+this.value+'&Tia_Ini='+document.getElementById('Tia_Ini').value,'div_tiaDes')"></div>
    </td>
  </tr>
  <tr>
  <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Abrev.:</td>
  <td width="458">
            <div class="Titulos2" id="div_tipabrev">
            <input name="Tip_Abrev" type="text" id="Tip_Abrev" value="<?Php echo $row_rs_consulta['Tia_Abr']?>" size="5" maxlength="30" style="text-transform:uppercase">
            </div>
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
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Tia_Ini*Tia_Des*Tip_Abrev', 1)">
                   <i class="icon-book icon-white"></i>
                   <span>Guardar</span>
       </button>    
    </div></td>
  </tr>
</table>
  <input type="hidden" name="txt_busqueda" id="txt_busqueda" value="<?Php echo $txt_busq; ?>" />
  <input type="hidden" name="codigo" id="codigo" value="<?Php echo $codigo; ?>" />
<?Php
}
?>
</form>        
	</td>
  </tr>
</table>	
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_tip_asient.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>