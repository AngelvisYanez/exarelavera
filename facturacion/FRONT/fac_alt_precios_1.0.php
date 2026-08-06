<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php
/**
* pagina de ingreso de precios (fac_alt_precios_1.0.php) :)
*
* @author Jose Cumbicos
* Ultima Actualizaci�n: 28-05-2014
* @author Lewis Chimarro
* Ultima Actualizaci�n: 30-01-2015
*
* Permite buscar y visualizar los datos de un producto
* Permite ver todo el listado de precios del producto
* Permite ingresar nuevos precios al producto
*
* @package tesoreria
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_precios.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');


/**
 * objeto conexion
 */
$obBD_conexion = new Class_Log_Conexion_pre($Ses_Dat_Dis);

/**
 * objeto para extraer datos
 */
$obBD_con1 =  new Class_Log_Datos_pre;

/**
 * fecha actual
 */
$hoy = date("Y-m-d");

/**
 * Objeto evtiar reenvio de datos
 */
 $thisPost = new Post_Block;


if ($thisPost->postBlock($_POST['postID'])) 
	{
		if (isset($hdd_save)&&!isset($hdd_volver))
		{   
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_pre(1201,$obBD_con1->parametros($Pro_Cod.'*'.$Tpv_Cod)),$obBD_conexion->conexion);
			$obBD_con1->grabarv_registros(sentencias_pre(1100,$obBD_con1->parametros($Pro_Cod.'*'.$Pre_Tot.'*'.$Tpv_Cod.'*'.$hoy.'*'.$Pre_Com.'*'.$Pre_Por.'*'.$Pre_Uti.'*'.$ini.'*'.$fin.'*'.$Ses_Suc_Cod)),$obBD_conexion->conexion);
			$Pre_Cod = $obBD_con1->insercionid ($obBD_conexion->conexion);
		
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);	
		}	
}


if (isset($codigo))
{
	/**
	 * Datos de producto
	 */
	$row_rs_consulta = $obBD_con1->getRowConsulta(437, $codigo, $obBD_conexion);
			
	/**
	 * Datos de IVA
	 */
	$row_rs_iva = $obBD_con1->getRowConsulta(429, "", $obBD_conexion);
			
	$iva= $row_rs_iva['Iva_Cod'];
					
}
?>	
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom;?></TITLE>
		
        <link rel="stylesheet" type="text/css" media="all" href="../../Librerias/jscalendar/calendar-win2k-cold-1.css" title="win2k-cold-1" />

		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>	
        
		<!--<script type="text/javascript" src="../../Librerias/jscalendar/calendar.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/lang/calendar-es.js"></script>
		<script type="text/javascript" src="../../Librerias/jscalendar/calendar-setup.js"></script>-->
		<!--<script type="text/javascript" src="../../Librerias/java.js"></script>-->
		<script type="text/javascript" src="../VALIDACIONES/fac_val_precios.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script> 
	    <script type="text/javascript">$(function() {$('#set1 *').tooltip({showURL: false});});</script>
        
         <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script> 
		$(function() 
        {	
            /* Campo 1 */
			$( "#ini" ).datepicker();
			$( "#ini" ).change(function() {
			$( "#ini" ).datepicker( "option", "dateFormat", "yy-mm-dd" );});
			
			/* campo 2*/
			$( "#fin" ).datepicker();
			$( "#fin" ).change(function() {
			$( "#fin" ).datepicker( "option", "dateFormat", "yy-mm-dd" );});
		}); 		
        </script>
        
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">    

	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
  <tr class="BarraTitulo">
    <td height="10">
		<table width="98%" height="23" border="0" align="left" cellpadding="0" cellspacing="0" bgcolor="#C7E0CD" >
			<tr class="BarraTitulo">
				<td width="389">&raquo; Registrar  Precios </td>
			</tr>
		</table>
	
	</td>
  </tr>
  <tr>
    <td height="389" valign="top">
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="form1" name="form1">
    
      <table width="560" border="0" cellpadding="0" cellspacing="0" class="Pop">
        <tr>
			<td>
				<input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
			</td>
		</tr>
      </table>		  
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Buscar por: </label>
		</LEGEND>
        <?PHP mensaje_requerido(); ?>
		<table width="481" border="0">
		          <tr>
		            <td width="205"><input name="op_opciones" type="radio" value="d" checked>
		                <span class="LetraNegra">Descripci&oacute;n</span></td>
		            <td width="266"><input type="radio" name="op_opciones" value="r">
		                <span class="LetraNegra">C�digo</span></td>
		          </tr>
		        </table>
		        <table width="534" border="0" cellspacing="0">
		          <tr>
		            <td width="599" height="43" class="BarraBusqueda"><div align="left"><span class="Asterisco">* </span>Busqueda:
		              <input name="txt_busqueda" type="text" id="txt_busqueda" size="40" maxlength="50">
		            
			 				<button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_buscar()" >
			              			<i class="icon-search icon-white"></i>
			              			<span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
			       			</button>
		            </div></td>
	              </tr>
		        </table>
		        </fieldset>
		        </form>
      <?Php
  	if(isset($txt_busqueda) && !isset($codigo))
	{
		/**
		 * Arreglo de datos de productos resultante de la consulta
		 */
		$Arr_productos = $obBD_con1->getArrayConsulta(($op_opciones == "d")? 1002 : 462, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
		$Total_productos = count($Arr_productos);
  ?>   	<br>
		<fieldset>
	    <legend>
          <label class="Titulos2">Resultados de la busqueda</label>
        </legend>
        <table class="fixedHeader01" width="100%" border="1" cellpadding="0" cellspacing="0">
        <thead>
          <tr >
            <th width="9%">C&oacute;d. Int.</th>
            <th width="25%">Categoria</th>
            <th width="28%">Descripci&oacute;n Larga</th>
            <th width="19%">Descripci&oacute;n Corta </th>
            <th width="14%">Marca</th>
            <th width="5%">&nbsp;</th>
          </tr>
          </thead>
          <tbody>
          <?Php 
      if($Total_productos  != 0)
	  {	 
	  foreach($Arr_productos as $row_rs_buscar) 
	  { 
	  	 $rojo='';
	  	
	     if($row_rs_buscar['Pro_Est']=='I')
	     { 
	     	$rojo='#FF0000';
	     }
	  ?>
          <tr >
            <td align="center">
            	<FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Pro_Cod']; ?></FONT>
            </td>
            <td align="center">
            	<FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Cat_Des']; ?></FONT>
            </td>
            <td><FONT COLOR="<?php echo $rojo;?>"><?Php echo  marcar_cadena($txt_busqueda,$row_rs_buscar['Ite_Lar'].' '.$row_rs_buscar['Pro_Obs'],'#FFFF00',1); ?></FONT></td>
            <td align="center"><?Php echo $row_rs_buscar['Ite_Cor'].' '.$row_rs_buscar['Pro_Obs']; ?></td>
            <td align="center">
            	<FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Mar_Des']; ?></FONT>
            </td>
            <td align="center">
            	<?Php 
            		if ($row_rs_buscar['Pro_Est'] == 'A')
            		{ 
            	?>
            			<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" >
            			
            			<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
           					<i class=" icon-arrow-right icon-white"></i>
           				</button>
            			
                			<!-- <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/forward.png" width="22" height="22"  title="Elegir"> -->
                			<input name="codigo" id="codigo" type="hidden" value="<?Php echo $row_rs_buscar['Pro_Cod']; ?>">
                			<input  type="hidden" id="txt_busqueda" name="txt_busqueda" value="<?php echo $txt_busqueda; ?>">
                			<input  type="hidden" id="op_opciones" name="op_opciones" value="<?php echo $op_opciones; ?>">
                		</form>
                <?Php 
            		} 
            		else 
            		{ 
            			if(!isset($com_leyenda[1]))$com_leyenda[1]=1;
            			?>
    						<img src="../../mascaras/model1/imagenes/32x32/encrypted.png" title="No se puede editar porque ha sido desactivado" width="20" height="20">
    					<?php
            		} 
            	?>
            </td>
          </tr>
          <?php } }else{?>
          <tr >
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td><?php echo error_alerta("&iexcl;No hay resultados que mostrar!", 1); ?></td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
            <td align="center">&nbsp;</td>
          </tr>
     	  <?php }?>
          </tbody>
        </table>
		</fieldset>
		<?php echo barra_estado(count($Arr_productos)); ?>
      <?Php
 } // isset($txt_busqueda)

?>
<form method="post" name="form2" action="<?Php  $_SERVER['PHP_SELF']; ?>">
<?Php if (isset($codigo)){ ?>
<?php $thisPost->startPost();?>
	<FIELDSET>
       <legend>
       		<label class="Titulos2">Datos a registrar</label>
       </legend>
       <table width="100%" border="0">
         <tr>
           <td width="758"><?Php echo mensaje_requerido(); ?></td>
         </tr>
       </table>
       <table border="0" cellpadding="0" cellspacing="0">
		 <tr>
           <td width="140" class="Etiqueta1">Categoria:</td>
           <td width="364" class="LetraNegra">&nbsp;<?php echo $row_rs_consulta['Cat_Des']; ?>
              		<input name="Cat_Cod2" type="hidden" id="Cat_Cod2" value="<?Php echo $row_rs_consulta['Cat_Cod']; ?>">		   </td>
         </tr>
         <tr>
           <td class="Etiqueta1">Descripci&oacute;n Corta:</td>
           <td class="LetraNegra">&nbsp;<?php echo $row_rs_consulta['Ite_Cor']; ?></td>
         </tr>
         <tr>
           <td class="Etiqueta1">Descripci&oacute;n Larga:</td>
           <td class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Ite_Lar']; ?>
              <input name="Ite_Cod" type="hidden" id="Ite_Cod" value="<?Php echo $row_rs_consulta['Ite_Cod']; ?>">
			  <input name="Pro_Cod" type="hidden" id="Pro_Cod" value="<?Php echo $row_rs_consulta['Pro_Cod']; ?>"></td>
         </tr>
         <tr>
           <td class="Etiqueta1">Marca:</td>
           <td  class="LetraNegra">&nbsp;<?php echo $row_rs_consulta['Mar_Des']; ?>
              <input name="Mar_Cod2" type="hidden" id="Mar_Cod2" value="<?Php echo $row_rs_consulta['Mar_Cod']; ?>"></td>
         </tr>
		 <tr>
           <td class="Etiqueta1"> Iva:</td>
           <td  class="LetraNegra">&nbsp;<?Php echo $row_rs_consulta['Iva_Por'];	?>		   </td>
         </tr>
         </table>
         <br>
		 <FIELDSET>
	    <LEGEND>
		 <label class="Titulos2">Registro de precios </label>
	    </LEGEND>
        <table border="1" cellpadding="0" cellspacing="0" bordercolor="#333333">
	      <tr class="Cabecera1">
		    <td width="49" title="Precio de Compra">P.C.</td>
		    <td width="32" title="Porcentaje de Utilidad">%.U.</td>
		    <td width="31" title="Total de Utilidad">T.U.</td>
		    <td width="32" title="Precio de Venta al Publico [P.C. + T.U.]"><span class="Asterisco">* </span>P.V.P.</td>
		    <td width="36" title="Porcentaje de Descuento">%.DCT.</td>
		    <td width="36" title="Total de Descuento">T.DCT.</td>
		    <td width="39" title="Total [T.U. - T.DCT.]">TOTAL</td>
		    <td width="32" title="Ganancia Obtenida">GAN.</td>
		    <td width="80" title="Fecha Inicial">F.I.</td>
		    <td width="80" title="Fecha Final">F.F.</td>
		    <td width="114" title="Tipo de Precio"><span class="Asterisco">*</span> Tipo</td>
	      </tr>
	      <tr>
	        <td class="LetraNegra"><label style="text-align: center;">
	            <input type="text" onKeyUp="calcular()" onKeyPress="return validar_decimal(event)"  id="Pre_Com" name="Pre_Com" size="5" maxlength="15" style="text-align:right">
	        </label></td>
	        <td class="LetraNegra"><input type="text"  onKeyUp="calcular()" onKeyPress="return validar_decimal(event)" name="Pre_Por" id="Pre_Por" size="5" maxlength="15" style="text-align:right" ></td>
	        <td class="Cabecera1"><input type="text" value="0" onKeyPress="return validar_decimal(event)" id="Pre_Uti" name="Pre_Uti" size="5" maxlength="6" style="border:none; text-align:right; background:none" class="Cabecera1" readonly></td>
	        <td class="LetraNegra"><input type="text" onKeyUp="calcular2()" onKeyPress="return validar_decimal(event)" name="Pre_Pvp" id="Pre_Pvp" size="6" maxlength="15" style="text-align:right"></td>
	        <td class="LetraNegra"><input type="text" onKeyUp="calcular()" onKeyPress="return validar_decimal(event)" name="Pre_Dcs"  id="Pre_Dcs"size="5" maxlength="6" style="text-align:right" ></td>
	        <td class="Cabecera1"><input name="Pre_Dct" onKeyUp="calcular()" id="Pre_Dct" type="text" onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" style="border:none; text-align:right; background:none" class="Cabecera1" readonly></td>
	        <td class="Cabecera1"><input name="Pre_Tot" onKeyUp="calcular()" type="text" id="Pre_Tot" onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" style="border:none; text-align:right; background:none" class="Cabecera1" readonly></td>
	        <td class="Cabecera1"><input name="Pre_Gan" onKeyUp="calcular()" id="Pre_Gan" type="text"  onKeyPress="return validar_decimal(event)" value="0" size="6" maxlength="5" onChange="ColorGanancia();" style="border:none; text-align:right; background:none" class="Cabecera1" readonly></td>
	        <td class="LetraNegra">
			 
			<input name="ini" type="text" id="ini" value="" size="10" onKeyUp="mascara(this,'-',patron,true)"/>
    
		</td>
	        <td class="LetraNegra"><input name="fin" type="text" id="fin" value="" size="10" onKeyUp="mascara(this,'-',patron,true)" />
		</td>
	        <td class="LetraNegra">
            <?php 
            	/**
            	 * Obtiene los tipos de precio segun la sucursal
            	 */
            	$Arr_Tipo_precio = $obBD_con1->getArrayConsulta(1099, $Ses_Suc_Cod, $obBD_conexion);
            ?>
            <select name="Tpv_Cod" id="Tpv_Cod">
              <option value="">Seleccione...</option>
              <?php 
              	foreach($Arr_Tipo_precio as $row_rs_tprecio)
              	{  
              ?>
          			<option   value="<?Php echo $row_rs_tprecio['Tpv_Cod']; ?>">
          			<?php echo $row_rs_tprecio['Tpv_Des'];?>
          		</option>
          	<?php
				}	  
			?>
       		</select></td>
   		  </tr>
 		</table>
  		<br/>
  		<table border="0" cellpadding="0" cellspacing="0">
        <tr><td>&nbsp;</td></tr>
          <tr>
          <td width="110">
          <button type="button" class="btn btn-inverse fileinput-button" title="Atr&aacute;s" onClick="campos_hide(this.form, 'hdd_volver', '<?php echo '1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;&nbsp;</span>
       		 </button>
       <input type="hidden" value="<?php echo $txt_busqueda; ?>" name="txt_busqueda" id="txt_busqueda">
       <input type="hidden" value="<?php echo $op_opciones; ?>" name="op_opciones" id="op_opciones">
          </td>
            <td width="110" align="left"><font color="#3162a6" face="Arial, Helvetica, sans-serif">
            
            <button type="button" class="btn btn-primary fileinput-button" title="Guardar" onClick= "validar_requeridos(this.form, 'Pre_Pvp*Tpv_Cod', 1)" >
               <i class=" icon-book icon-white"></i>
               <span>&nbsp;&nbsp;Guardar&nbsp;&nbsp;</span>
       		 </button>
            
            </font></td>
          </tr>
        </table>
  		<input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
	    </FIELDSET>
	    
 <br/> <br/> 
 <FIELDSET>
		 <LEGEND>
		 <label class="Titulos2">Hisorial de Precios</label>
		 </LEGEND>
		 <?php 
		 /**
		  * Array de precios
		  */
		 $Arr_precios = $obBD_con1->getArrayConsulta(467, $codigo.'*'.$Ses_Suc_Cod, $obBD_conexion);
		 ?>
        <table width="461" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
        <thead>
	      <tr>
	        <th>C�d. Int.</th>
		    <th >Precio</th>
		    <th >Tipo</th>
		    <th >F.Inicio</th>
		    <th >F.Fin</th>
		    <th >Fecha
	        <input name="Pro_Cod2" type="hidden" id="Pro_Cod2" value="<?Php echo $row_rs_consulta['Pro_Cod']?>"></th>
          </tr>
          </thead>
          <tbody>
	         <?php 
	            foreach($Arr_precios as $row_rs_precio) 
	            {
	            	if ($row_rs_precio['Pre_Est'] == 'I') { if(!isset($com_leyenda[1]))$com_leyenda[1]=1; }
	         ?>
	      <tr style="font" >
	      <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?> 
        		<?Php echo $row_rs_precio['Pre_Cod'];   if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>
	      </td>
	        <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?> 
        		<?Php echo $row_rs_precio['Pre_Pvp'];   if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>
        	</td>
	        <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?>
	        	<?Php echo $row_rs_precio['Tpv_Des'];  if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>
	        </td>
	        <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?>
            	<?Php echo $row_rs_precio['Pre_Ini'];  if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>
            </td>
	        <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?>
              	<?Php echo $row_rs_precio['Pre_Fin'];  if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>
            </td>
	        <td class="LetraNegra">
	        	<?Php if ($row_rs_precio['Pre_Est'] == 'I'){ echo "<font color='#FF0000'>"; } ?>
	        	<?Php echo $row_rs_precio['Pre_Fec'];  if ($row_rs_precio['Pre_Est'] == 'I'){ echo "</font>"; } ?>  </td>
	      </tr>
	            <?php 
	            }
	            ?>
        </tbody>
 		</table>
	    </FIELDSET>
	    <?php echo barra_estado(count($Arr_precios));?>
 </FIELDSET> 
 <?php } ?>
          <br>
</form>
</td>
</tr>
</table>
<br/>
<?php
require_once('../../componentes/FRONT/com_con_leyenda.php');
?>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_precios.js"></script>
  <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>
</HTML>
<?php 
$obBD_con1->liberar();
$obBD_conexion->cerrar();			
?>