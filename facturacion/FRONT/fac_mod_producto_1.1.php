<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php 
/**
* Descripci�n: Permite modificar productos
* Fecha de actualizaci�n:	2014-05-28 
* Desarrollador:	Jose Cumbicos
* Fecha de actualizaci�n:	2015-01-30
* Desarrollador:	Lewis Chimarro
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
		if($Pro_Gen==1)
		{		
		switch (strlen($Pro_Cod)) {
			case 1:
			 $Pro_var=$Pro_Cod."00000000000";
			break;
			case 2:
			 $Pro_var=$Pro_Cod."0000000000";
			break;
			case 3:
			 $Pro_var=$Pro_Cod."000000000";
			break;
			case 4:
			 $Pro_var=$Pro_Cod."00000000";
			break;
			case 5:
			 $Pro_var=$Pro_Cod."0000000";
			break;
			case 6:
			 $Pro_var=$Pro_Cod."000000";
			break;
			case 7:
			 $Pro_var=$Pro_Cod."00000";
			break;
			case 8:
			 $Pro_var=$Pro_Cod."0000";
			break;
			case 9:
			 $Pro_var=$Pro_Cod."000";
			break;
			case 10:
			 $Pro_var=$Pro_Cod."00";
			break;
			case 11:
			 $Pro_var=$Pro_Cod."0";
			break;
		}
		$Pro_Bar=$Pro_var;
		$Pro_varg='G';
		}
		else
		{
		$Pro_varg='M';
		}

	  /**
	  * Inicio de la transaccion de guardado
	  */
	  $obBD_con1->inicio_transaccion($obBD_conexion->conexion);		
	  /**
	  * Actualizacion del producto	 
	  */
	  $obBD_con1->operacionobBD(1038,$Adq_Cod.'*'.$Ite_Cod.'*'.$Mar_Cod.'*'.$Iva_Cod.'*'. $Pro_Obs.'*'.$Pro_Bar.'*'.$Ubi_Cod.'*'.$Uni_Cod.'*'.$Pro_Cod.'*'.$Pro_Cdc.'*'.$Pro_Sec.'*'.$Pro_Uni,$obBD_conexion);	
	  /**
	  * Actualiza la categoria, descripci�n corta y larga
	  */
	  //$obBD_con1->operacionobBD(1012,$Ite_Cod.'*'.$Cat_Cod.'*'.$Ite_Cor.'*'.$Ite_Lar,$obBD_conexion);				
	  /**
	  * Actualiza el codigo de barras
	  */
	  $obBD_con1->operacionobBD(1023,$Pro_Cod.'*'.$Pro_Bar.'*'.$Pro_varg,$obBD_conexion);
          /**
	  * Actualiza el precio
	  */
          if(!empty($Pre_Cod))
            $obBD_con1->operacionobBD(1210,$Ses_Suc_Cod.'*'.$Pre_Cod.'*'.$Pro_Cod.'*'.$Pre_Pvp,$obBD_conexion);
	  /**
	  * Fin de la transacci�n
	  */
	  $obBD_con1->fin_transaccion($obBD_conexion->conexion);			  
	}	
}
/**
* Fin del grabado 
*/

/**
* Busqueda del producto 
*/
if (isset($txt_busqueda))
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
	/**
	* Consulta las marcas 
	*/	
	$rs_marca= $obBD_con1->getArrayConsulta(428,$Ses_Emp_Cod,$obBD_conexion);	
	$total_rs_marca =  count($rs_marca);
	/**
	* Consulta el iva
	*/
	$rs_iva= $obBD_con1->getArrayConsulta(429,'',$obBD_conexion);	
	$total_rs_iva = count($rs_iva);				

	/**
	* Consulta el item
	*/
	$rs_item= $obBD_con1->getArrayConsulta(713,$Ses_Emp_Cod,$obBD_conexion);	
	$total_rs_item = count($rs_item);				
	
	/**
	* Consulta los tipos de adquisiciones
	*/
	$rs_adq= $obBD_con1->getArrayConsulta(712,'',$obBD_conexion); 	
	$total_rs_adq =  count($rs_adq);				
    /**
    * Consulta las categorias de tipo detalle
    */
	$rs_categoria= $obBD_con1->getArrayConsulta(1030,$Ses_Emp_Cod,$obBD_conexion);
	$total_rs_categoria =  count($rs_categoria);
	/**
	* Consulta las ubicaciones (perchas) del producto
	*/
	$rs_ubicacion= $obBD_con1->getArrayConsulta(1003,$Ses_Emp_Cod,$obBD_conexion);
	$total_rs_ubicacion =  count($rs_ubicacion);
	/**
	* Consulta las unidades de medida para los productos
	*/
	$rs_unidad= $obBD_con1->getArrayConsulta(1004, '',$obBD_conexion);
	$total_rs_unidad = count($rs_unidad);			
}
?>
<HTML>
<HEAD>		
 <TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
    <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
    <script type="text/javascript" src="../VALIDACIONES/fac_val_producto.js"></script>        
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
	  <td height="10">&raquo; Modificar Productos</td>
    </tr>
	<tr>
      <td height="400" valign="top">
		 <form action="<?Php echo $_SERVER['PHP_SELF']  ?>" method="post" name="form1" id="form1">
 			<fieldset>
  		 		<LEGEND>
  					<label class="Titulos2">Buscar por :</label>
  				</LEGEND>
   			<?PHP mensaje_requerido(); ?>
  				<table width="540" height="35" border="0" cellspacing="0" lass="">
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
		if(isset($txt_busqueda)){ ?>
        <fieldset>
          <LEGEND>
                 <label class="Titulos2">Resultados de la busqueda</label>
            </LEGEND>
            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
              <thead>
                <tr>
                 <th width="9%">C�d. Int.</th>
                 <th width="19%">Categoria</th>
                 <th width="38%">Descripci&oacute;n Larga</th>
                 <th width="19%">Descripci�n Corta </th>
                 <th width="11%">Marca</th>
                 <th width="4%">&nbsp;</th>                 
                </tr>
              </thead>
              <tbody>
      		<?Php 
			if($total_rs_buscar != 0)
			{ 
			  foreach( $rs_buscar as $row_rs_buscar) { 
				     if($row_rs_buscar['Pro_Est']=='I')
				  	  { $rojo='#FF0000'; $anulada++; }else{$rojo='';} ?>
                <tr>
                  <td width="9%" align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Pro_Cod']; ?></FONT></td>
                  <td width="19%" align="left"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Cat_Des'];  ?></FONT></td>
                  <td><font color="<?php echo $rojo;?>"><?Php echo marcar_cadena($txt_busqueda,$row_rs_buscar['Ite_Lar'],'#FFFF00',1).' '.marcar_cadena($txt_busqueda,$row_rs_buscar['Pro_Obs'],'#FFFF00',1); ?></font></td>
                  <td width="19%" align="left"><?Php echo $row_rs_buscar['Ite_Cor'].' '.marcar_cadena($txt_busqueda,$row_rs_buscar['Pro_Obs'],'#FFFF00',1); ?></td>
                  <td width="11%" align="center"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Mar_Des']; ?></FONT></td>
                  <td width="4%" align="center">
                  <form name="form3" id="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                  <?Php if ($row_rs_buscar['Pro_Est'] == 'A'){ ?>
                  <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()">
		        	<i class=" icon-arrow-right icon-white"></i>
        		  </button>
                      <input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Pro_Cod']; ?>">
                      <input  type="hidden" id="volver_busqueda" name="volver_busqueda" value="<?php echo $txt_busqueda; ?>">
       				 <?Php } else { echo "&nbsp;"; } ?>	
			     </form>
                  </td>      
                </tr>
	 		 <?Php 
			 	} 
			  }
			  else
			  {?>  
                  <tr>
                      <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
                        <td align="center"><?php echo error_alerta("�No hay resultados que mostrar!", 1); ?></td>
                        <td align="center">&nbsp;</td>
                        <td align="center">&nbsp;</td>
                        <td align="center">&nbsp;</td>		
                  </tr>	
		<?php } ?>
		</tbody>
    </table>
    </fieldset>
    <?php echo barra_estado($total_rs_buscar); ?>	
      <?Php   	
  	/**
	* Parametro de la busqueda por fecha en compras 
	*/
	/**
	* Control para setear el arreglo solo cuando tenga valores
	*/
	if ($anulada > 0)
	{		
		$com_leyenda[1]=$anulada;
	}//Fin del if ($anulada > 0)
	?><br>
 <?Php require_once('../../componentes/FRONT/com_con_leyenda.php');?>
	<?php  
    }//Fin del if(isset($txt_busqueda))
    ?>
<form method="post" name="form2" action="<?Php $_SERVER['form1'] ?>">
<?Php
/**
* Control para evitar el reenvio de datos
*/
 $thisPost->startPost();
 
if (isset($codigo))
{
?>
<br>
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
    <td width="18%" class="Etiqueta1">C&oacute;d. Int. Producto:</td>
    <td width="82%" colspan="5" class="LetraNegra"><input  disabled="disabled" border="0" name="Pro_Cod1" type="text" id="Pro_Cod1"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Cod']?>" />
      <!--<input    border="0" name="Ite_Cod" type="hidden" id="Ite_Cod"  size="15" maxlength="30" value="<?Php //echo $row_rs_consulta['Ite_Cod']?>" />-->
      <input    border="0" name="Pro_Cod" type="hidden" id="Pro_Cod"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Cod']?>" /></td>
    </tr>  
  <tr>
    <td class="Etiqueta1"> Categoria: </td>
    <td colspan="5" class="LetraNegra"><select name="Cat_Cod" id="Cat_Cod" onChange="ajax_datos('tes_alt_producto_cdc.php?ajax_pro=1&Cat_Cod='+document.getElementById('Cat_Cod').value,'div_contenedorsec')">      
      <?php  	  
		foreach($rs_categoria as $row_rs_categoria){ ?>
      <option   <?Php if($row_rs_consulta['Cat_Cod']==$row_rs_categoria['Cat_Cod']){ echo "selected='selected'";}{} ?> value="<?Php echo $row_rs_categoria['Cat_Cod']; ?>">
      <?php if ($total_rs_categoria >0)
			{
				echo ">>".$row_rs_categoria['Grupo'].'>_'.ucfirst(strtolower($row_rs_categoria['Cat_Des']));
			} ?>
      </option>
      <?php  
	  }?>
    </select> 
    </td>
  </tr>
  <tr>
    <td class="Etiqueta1">C&oacute;d. Secuencial: </td>
    <td colspan="5" class="LetraNegra"><table width="33%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="49%"><div id="div_contenedorsec"><input  disabled="disabled" border="0" name="Pro_Cdc1" type="text" id="Pro_Cdc1"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Cdc']?>" />
            <input    name="Pro_Cdc" type="hidden" id="Pro_Cdc"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Cdc']?>" />
            <input    name="Pro_Sec" type="hidden" id="Pro_Sec"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Pro_Sec']?>" />
          </div></td>
          <td width="51%"></td>
        </tr>
      </table>    </td>
  </tr>
  <tr>
    <td height="24" class="Etiqueta1">Descripci&oacute;n  Corta: </td>
    <td colspan="5" class="LetraNegra">
      <input  border="0" name="Ite_Cor" type="text" id="Ite_Cor"  size="15" maxlength="30" value="<?Php echo $row_rs_consulta['Ite_Cor']?>" readonly="readonly" />      
      <span class="Alertas3">Los cambios en las Descripciones Corta o Larga se aplicaran en las Facturas y otros Docs relacionados</span>
      </td>
    </tr>
  <tr>
    <td class="Etiqueta1">Descripci&oacute;n Larga: </td>
    <td colspan="5" class="LetraNegra"><!--<input   name="Ite_Lar" type="text" id="Ite_Lar" value="<?Php //echo $row_rs_consulta['Ite_Lar']?>" size="25" maxlength="30" />-->
      <select name="Ite_Cod" id="Ite_Cod" >
        <option   ></option>
        <?php  foreach($rs_item as $row_rs_item){  	 ?>
        <option <?Php if ($row_rs_item['Ite_Cod'] ==$row_rs_consulta['Ite_Cod']){ echo "selected"; }?>  value="<?Php echo $row_rs_item['Ite_Cod']; ?>">
          <?php          echo ">>".$row_rs_item['Cat_Des'].'>_'.$row_rs_item['Ite_Lar'];		?>
          </option>
        <?php	} 	?>
      </select></td>
    </tr>
</table>
</fieldset>
<fieldset>
<LEGEND>
<label class="Titulos2">Del producto</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">    
  <tr>
    <td class="Etiqueta1">Detalle del Producto:</td>
    <td colspan="5" class="LetraNegra"><input name="Pro_Obs" type="text" id="Pro_Obs" value="<?Php echo $row_rs_consulta['Pro_Obs']; ?>" style="width:200" size="60" /></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Marca:</td>
    <td colspan="5" class="LetraNegra">
        <select name="Mar_Cod" id="Mar_Cod" >
          <option   ></option>
          <?php  foreach($rs_marca as $row_rs_marca){  	 ?>
          <option <?Php if ($row_rs_marca['Mar_Cod'] ==$row_rs_consulta['Mar_Cod']){ echo "selected"; }?>  value="<?Php echo $row_rs_marca['Mar_Cod']; ?>">
          <?php          echo $row_rs_marca['Mar_Des'];		?>
          </option>
          <?php	} 	?>
      </select></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Adquisici&oacute;n: </td>
    <td colspan="5" class="LetraNegra"><select name="Adq_Cod" id="Adq_Cod">
      <option></option>
      <?php  
			foreach($rs_adq as $row_rs_adq){  		
	      ?>
      <option  <?Php if ($row_rs_adq['Adq_Cod']==$row_rs_consulta['Adq_Cod']){ echo "selected"; }?> value="<?Php echo $row_rs_adq['Adq_Cod']; ?>">
      <?php  	echo $row_rs_adq['Adq_Des'];  ?>
      </option>
      <?php	} ?>
    </select></td>
  </tr>  
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">* </span>Iva: </td>
    <td colspan="5" class="LetraNegra">
      <select name="Iva_Cod" id="Iva_Cod">
        <option></option>
        <?php foreach($rs_iva as $row_rs_iva){ ?>
        <option  <?Php if ($row_rs_iva['Iva_Cod'] ==$row_rs_consulta['Iva_Cod']){ echo "selected"; }?> value="<?Php echo $row_rs_iva['Iva_Cod']; ?>">
        <?php    		echo $row_rs_iva['Iva_Por'];  ?>
        </option>
        <?php }?>
      </select></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo de barra:</td>
    <td colspan="5" class="LetraNegra"><table width="57%" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="22%"><input <?Php if($row_rs_consulta['Pro_Gen']=="G"){echo "disabled='disabled'"; } ?> name="Pro_Bar" type="text" id="Pro_Bar" size="15" maxlength="15" value="<?php echo $row_rs_consulta['Pro_Bar']; ?>" /></td>
          <td width="4%"><input name="Pro_Gen" type="checkbox" id="Pro_Gen" onClick="check_generar()"  value="<?Php  if($row_rs_consulta['Pro_Gen']=="G"){ echo $Pro_Gen=1;}else{ echo $Pro_Gen=0;} ?>"  <?Php if($row_rs_consulta['Pro_Gen']=="G"){echo "checked"; } ?>>          </td>
          <td width="74%"><div class="LetraNegra" id='contenedorcheck'><?Php  if($row_rs_consulta['Pro_Gen']=="G"){ echo "Genera el c�digo del producto";}else{ echo "Ingrese el c�digo de barra del producto";} ?></div></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Ubicaci&oacute;n: </td>
    <td colspan="5" class="LetraNegra"><select name="Ubi_Cod" id="Ubi_Cod">
        <option></option>
        <?php foreach($rs_ubicacion as $row_rs_ubicacion){ ?>
        <option  <?Php if ($row_rs_ubicacion['Ubi_Cod'] ==$row_rs_consulta['Ubi_Cod']){ echo "selected"; }?> value="<?Php echo $row_rs_ubicacion['Ubi_Cod']; ?>">
        <?php	echo $row_rs_ubicacion['Ubi_Des'];  ?>
        </option>
        <?php }?>
    </select></td>
  </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Tipo de Medida: </td>
    <td colspan="5" class="LetraNegra"><select name="Uni_Cod" id="Uni_Cod" onChange="if (this.value==1){ document.getElementById('Pro_Uni').readOnly=true; document.getElementById('Pro_Uni').value = '1';  }else{ document.getElementById('Pro_Uni').readOnly =false;
document.getElementById('Pro_Uni').value = ''; }">
        <?php foreach( $rs_unidad as $row_rs_unidad){	?>
        <option  <?Php if ($row_rs_unidad['Uni_Cod'] ==$row_rs_consulta['Uni_Cod']){ echo "selected"; }?> value="<?Php echo $row_rs_unidad['Uni_Cod']; ?>">
        <?php 		echo $row_rs_unidad['Uni_Des'];	  ?>
        </option>
        <?php }?>
    </select>&nbsp;&nbsp;&nbsp;&nbsp;Medida:<input name="Pro_Uni" type="text" id="Pro_Uni" onBlur="if (document.getElementById('Uni_Cod').value!=1 && this.value==1){ alert ('�Ingresar valores iguales a uno, � seleccione el elemento UNIDAD! ');this.focus(); } "  onKeyPress="return validar_decimal(event)"  size="8" maxlength="8" readonly="readonly"  border="0" value="<?Php echo $row_rs_consulta['Pro_Uni']; ?>" /></td>
  </tr>
    <tr>
      <td class="Etiqueta1">Precio por unidad:</td>
      <td colspan="5" class="LetraNegra">
          <input name="Pre_Cod" type="hidden" value="<?php echo $row_rs_consulta['Pre_Cod']; ?>" />
          <input onkeypress="return validar_decimal(event)" border="0" name="Pre_Pvp" type="text" id="Pre_Pvp"  size="8" maxlength="8" style="text-align:right"  value="<?Php echo $row_rs_consulta['Pre_Pvp']; ?>" /></td>
      </tr>
    <tr>
      <td class="Etiqueta1">Descuento:</td>
      <td colspan="5" class="LetraNegra"><input name="Pro_Dsc" type="text" id="Pro_Dsc" style="text-align:right" onkeypress="return validar_decimal(event)" value="<?Php echo $row_rs_consulta['Pro_Dsc']; ?>"  size="8" maxlength="8" border="0" readonly="readonly" /></td>
    </tr>
    <tr>
      <td colspan="6" class="Etiqueta1"><div align="center"><?php 
	  $varcode=$row_rs_consulta['Pro_Bar'];
	  include("../../Librerias/barcode/generadorbarras.php") ?></div></td>
      </tr>
</table>
</fieldset>
</fieldset>
<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
  <table width="258" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="110" align="left">
  <button type="button" class="btn btn-inverse fileinput-button" title="Atr�s" onclick="campos_hide(this.form, '<?Php echo "txt_busqueda*hdd_volver"; ?>', '<?Php echo $volver_busqueda.'*1'; ?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
  </td>
      <td width="148" align="left"> 
      <button type="button" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Cat_Cod*Mar_Cod*Ite_Cor*Ite_Cod*Adq_Cod*Iva_Cod*Uni_Cod*Ubi_Cod', 1)"> <i class="icon-book icon-white"></i> <span>Guardar</span></button>
       <input  type="hidden" id="txt_busqueda" name="txt_busqueda" value="<?php echo $volver_busqueda; ?>">
	  </td>
    </tr>
  </table>
<?Php } ?>
<br />
</form></td>
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