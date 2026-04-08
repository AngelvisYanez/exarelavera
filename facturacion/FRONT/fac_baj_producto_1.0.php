<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Anula o Activa los productos
* Fecha de actualizaci�n:	2014-05-28 
* Desarrollador: Jose Cumbicos
* Fecha de actualizaci�n:	2015-01-30 
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');	 
require_once('../LOGICA/fac_log_producto.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	

/**
* Llamado de la libreria para evitar el reenvio de datos 
*/
$thisPost = new Post_Block;
/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Pro($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Pro;

/**
* Pregunta si la variable esta setiada para poder grabar 
*/
if(isset($hdd_save) && !isset($hdd_volver))
{
 	/**
	* Evitar el reenvio de formularios 
	*/
	if ($thisPost->postBlock($_POST['postID']))
		{	
		  /**
		  * Inicio de la transaccion de guardado
		  */
		  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		  /**
		  * Actualizaci�n del estado del producto a Inactivo o Activo
		  */
		  $obBD_con1->operacionobBD(1021,$Pro_Cod.'*'.$Pro_Est,$obBD_conexion);		
		  /**
		  * FIn de la transacci�n
		  */
	   	  $obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}	
}

/**
* Busqueda del producto 
*/
if ($txt_busqueda != "")
{
  	/**
	* Consulta todos los producto 
	*/
	$rs_buscar = $obBD_con1->getArrayConsulta(1002,trim($txt_busqueda).'*'.$Ses_Emp_Cod,$obBD_conexion);
	$total_rs_buscar = count($rs_buscar);	
}
elseif (isset($codigo))
{	
	/**
	* Consulta la informaci�n del producto
	*/
	$row_rs_consulta = $obBD_con1->getRowConsulta(1010,$codigo,$obBD_conexion);	
	$total_rs_consulta = $row_rs_consulta['Ite_Cod'] > 0? 1 : 0;	
}
?>
<HTML>
<HEAD>		
 <!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
 <TITLE><?Php echo "Producto Anular [EXA]"; ?></TITLE>
  <meta charset="UTF-8">
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    <script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script language="javascript" src="../VALIDACIONES/fac_val_producto.js"></script>        
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
  <td height="10">&raquo; Activar/Desactivar/ Productos</td>
</tr>
<tr>
<td height="400" valign="top">
 <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
  <fieldset>
    <LEGEND>
        <label class="Titulos2">Buscar por :</label>
    </LEGEND>
    <?PHP mensaje_requerido(); ?>
    <table width="545" height="35" border="0" cellspacing="0" lass="">
    				<tr>
      					<td height="28" class="BarraBusqueda"><div><span class="Asterisco">*</span> Descripci&oacute;n: 
      					  <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50">
                        <button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button></div></td>
   					</tr>
  				</table>
	</fieldset>
  </form>
  <?Php   
  /**
  * Pregunta si la variables esta setiada para cargar los resultados
  */	
	if(isset($txt_busqueda))
	{ ?>
	<fieldset>		
	<LEGEND>
		<label class="Titulos2">Buscar por :</label>
	</LEGEND>	
    <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>					
    <tr>
     <th width="11%" align="center">Cód. Int.</th>
     <th width="19%" align="center">Categoria</th>
     <th width="38%" align="center">Descripci&oacute;n Larga</th>
     <th width="18%" align="center">Descripción Corta </th>
     <th width="11%" align="center">Marca</th>
     <th width="3%">&nbsp;</th>
    </tr>
    </thead>
    <tbody>
	<?Php 
	if($total_rs_buscar != 0)
	{ 
	  foreach($rs_buscar as $row_rs_buscar) {
	     if($row_rs_buscar['Pro_Est']=='I')
	  	  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}	
	   ?>
         <tr>
          <td width="11%" align="center"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Pro_Cod']; ?></FONT></td>
          <td width="19%" align="left"><FONT COLOR="<? echo $rojo;?>"><?Php echo $row_rs_buscar['Cat_Des']; ?></FONT></td>
		  <td><font color="<? echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Ite_Lar'],'#FFFF00',1).' '.marcar_cadena($txt_busqueda,$row_rs_buscar['Pro_Obs'],'#FFFF00',1); ?></font></td>
		  <td width="18%" align="left"><font color="<? echo $rojo; ?>"><?Php echo $row_rs_buscar['Ite_Cor'].' '.marcar_cadena($txt_busqueda,$row_rs_buscar['Pro_Obs'],'#FFFF00',1); ?></font></td>
		  <td width="11%" align="center"><FONT COLOR="<? echo $rojo;?>">
		  <?Php echo $row_rs_buscar['Mar_Des']; ?></FONT></td>
		   <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
          <td width="3%" align="center">
		   <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Pro_Cod']; ?>">
		   <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
		        	<i class=" icon-arrow-right icon-white"></i>            
              <input  type="hidden" id="volver_busqueda" name="volver_busqueda" value="<? echo $txt_busqueda; ?>">            
			  </td></form>
        </tr>
	  <?Php 
	  } 
	}
	else
	{ ?>  
        <tr>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center"><?php echo error_alerta("!No hay resultados que mostrar!", 1); ?></td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>		
        </tr>	
	<?php 
	}
	echo barra_estado($total_rs_buscar); ?>	
	</tbody>
    </table>
    <br>
  <?Php   	  	
	/**
	* Control para setear el arreglo solo cuando tenga valores
	*/
	if ($anulada > 0)
	{		
		$com_leyenda[1]=$anulada;
	}//Fin del if ($anulada > 0)
	?>
 	<?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>
 	</fieldset>    
<?Php
  }
  ?>
<form method="post" name="form2" action="<?Php $_SERVER['form1'] ?>">
<?Php
 $thisPost->startPost();
if (isset($codigo))
{
?>
<fieldset>
<LEGEND>
<label class="Titulos2">Datos a modificar </label>
</LEGEND>
<fieldset>
<LEGEND>
<label class="Titulos2">De la categoria </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="19%" class="Etiqueta1">C&oacute;d. Int. Producto:</td>
    <td width="81%" colspan="5" class="LetraNegra"><?Php echo $row_rs_consulta['Pro_Cod']?>
      <input    border="0" name="Ite_Cod" type="hidden" id="Ite_Cod"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Ite_Cod']?>" />
      <input    border="0" name="Pro_Cod" type="hidden" id="Pro_Cod"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Cod']?>" /></td>
    </tr>  
  <tr>
    <td class="Etiqueta1">Categoria: </td>
    <td colspan="5" class="LetraNegra"> <? echo $row_rs_consulta['Cat_Des']; ?>  </td>
  </tr>
  <tr>
    <td class="Etiqueta1">C&oacute;d. Secuencial: </td>
    <td colspan="5" class="LetraNegra"><? echo $row_rs_consulta['Pro_Cdc']; ?> </td>
  </tr>
  
  <tr>
    <td height="24" class="Etiqueta1">Descripci&oacute;n  Corta: </td>
    <td colspan="5" class="LetraNegra"><?Php echo $row_rs_consulta['Ite_Cor'].' '.$row_rs_consulta['Pro_Obs']?></td>
    </tr>
  <tr>
    <td class="Etiqueta1">Descripci&oacute;n Larga: </td>
    <td colspan="5" class="LetraNegra"><?Php echo $row_rs_consulta['Ite_Lar']?>   </td>
    </tr>
</table>
</fieldset>
<fieldset>
<LEGEND>
<label class="Titulos2">Del producto </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="19%" class="Etiqueta1">Detalle del producto:</td>
    <td width="81%" colspan="5" class="LetraNegra"><?Php echo $row_rs_consulta['Pro_Obs']?></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Marca:</td>
    <td colspan="5" class="LetraNegra">
       <?php  echo $row_rs_consulta['Mar_Des'];	?>
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Adquisici&oacute;n: </td>
    <td colspan="5" class="LetraNegra">
      <?php  echo $row_rs_consulta['Adq_Des'];  ?>     
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Iva: </td>
    <td colspan="5" class="LetraNegra">
        <?php  echo $row_rs_consulta['Iva_Por'];  ?>        
    </td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra:</td>
    <td colspan="5" class="LetraNegra"><?php echo $row_rs_consulta['Pro_Bar']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">&nbsp;</td>
    <td colspan="5" class="LetraNegra">&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1">&nbsp;</td>
    <td colspan="5" class="LetraNegra">&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1">&nbsp;</td>
    <td colspan="5" class="LetraNegra">&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span>Estado: </td>
    <td colspan="5" class="LetraNegra"> <select name="Pro_Est" id="Pro_Est">
        <option  <?Php if ($row_rs_consulta['Pro_Est'] =='A'){ echo "selected"; }?> value="A" >Activo</option>
        <option  <?Php if ($row_rs_consulta['Pro_Est'] =='I'){ echo "selected"; }?> value="I">Inactivo</option>
      </select>	</td>
  </tr>
</table>
</fieldset>
</fieldset>

<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <table width="253" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="110" align="left">
  <button type="button" class="btn btn-inverse fileinput-button" title="Atrás" onclick="campos_hide(this.form, '<?Php echo "txt_busqueda*hdd_volver"; ?>', '<?Php echo $volver_busqueda.'*1'; ?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
  </td>
      <td width="143" align="left">
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Pro_Cod*Pro_Est', 1)"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>
      <input  type="hidden" id="txt_busqueda" name="txt_busqueda" value="<? echo $volver_busqueda; ?>" /></td>
    </tr>
  </table>
  <br />
<?Php 
	}
 ?>
</form>
</td>
  </tr>
</table>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_producto.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>    
</BODY>
</HTML>
<?Php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();	
?>