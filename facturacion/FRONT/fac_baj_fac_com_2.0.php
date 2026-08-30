<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripci�n: Permite anular facturas de compra, retenciones, el comprobante de egreso/diario y los cheques
* Fecha de actualizaci�n:	2012-09-08  
* Desarrollador: Lewis Chimarro
* Fecha de actualizaci�n:	2014-01-06
* Desarrollador: Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_compras.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../componentes/LOGICA/logica.php');	
require_once('../../Librerias/postclass.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Comt($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Comt; 	  
/** 
* Creaci�n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

$hoy = date("Y-m-d");
/**
* Asingo el valor a la variable mes 
*/
$mes = date("m");

/**
 * Ajax del detalle de la factura de compra
 */
if (isset($ajax_info))
{ 
	include('../COMPONENTES/tesComDetalleCom.php'); 
	exit();	
}

/**
* Grabado de la eliminaci�n
*/
if(isset($elim))
{	
	/**
	* Evitar el reenvio de formularios 
	*/
	if ($thisPost->postBlock($_POST['postID'])) 
	{ 
		/**
		* Anulaci�n de la compra, comprobante contable, retencion y cheques
		*/
	 	$obBD_con1->anularCompras($Cop_Cod, $Ret_Cod, $Com_Cod, $Ses_Suc_Cod, $obBD_conexion)	;
	}//Fin del if ($thisPost->postBlock($_POST['postID'])) 
}		
	
if ($txt_busqueda != "")
{	
	/**
	* Evalua la consulta si por proveedor o numero de compra d=Proveedor  
	*/   
	if ($op_opciones == "d"){ //por apellido
		$rs_buscar = $obBD_con1->getArrayConsulta(468, trim($txt_busqueda).'*'.$Pec_Cod.'*'.$cmb_mes.'*'.$Tic_Cod, $obBD_conexion);	
	}
	if ($op_opciones == "c"){ //por cedula
		$rs_buscar = $obBD_con1->getArrayConsulta(470, trim($txt_busqueda).'*'.$Pec_Cod.'*'.$cmb_mes.'*'.$Tic_Cod, $obBD_conexion);	
	}
	if ($op_opciones == "r"){ //por numero documento
		$rs_buscar = $obBD_con1->getArrayConsulta(469, trim($txt_busqueda).'*'.$Pec_Cod.'*'.$Tic_Cod, $obBD_conexion);	
	}
}

/**
* Datos del periodo contable 
*/
if(isset($hdd_Pec_Cod))
{	
	/**
	* Carga el periodos contable actual 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(189, $Pec_Cod, $obBD_conexion);
	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);	
	/**
	* Consulta el tipo de comprobante 
	*/
	$rs_tip_compr = $obBD_con1->getArrayConsulta(729, '', $obBD_conexion);				
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?php require_once("../../mascaras/model1/estilos/estilos.php");?>
		<script type="text/javascript" src="../VALIDACIONES/tes_val_compras.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
        <!--Librerias para interfaz -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>  
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>                
        <script type="text/javascript"> 
        $(function() {
			$('#set1 *').tooltip({showURL: false});
		});              			
		</script>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10" colspan="3">&raquo; anular  Documentos de  Compras <?Php echo $periodo; ?></td>
    </tr>
  <tr>
     <td valign="top" height="400">
<?Php
/**
* Condicion para evaluar cuando mostrar los periodos contables 
*/
if (!isset($hdd_Pec_Cod) )
{
?><form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>">		
		<?Php require_once("../../componentes/FRONT/comConPeriodoCont.php"); ?>
 </form>
<?Php
}//Fin del if (!isset($hdd_Pec_Cod)) ?>

<?Php
if(isset($hdd_Pec_Cod))
{
?>
    <form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>"> 
	<FIELDSET>
	<legend>
	<label class="Titulos2">Tipo de documento:</label></legend>
    <table width="772" border="0" cellpadding="0" cellspacing="0">
  	<tr>
    	<td width="120" class="Etiqueta1"  ><span class="Asterisco">*</span> Tipo documento:&nbsp;</td>
	    <td width="652">
        <select name="Tic_Cod" id="Tic_Cod">
	      <?Php 
		  foreach($rs_tip_compr as $row_rs_tip_compr)
		  { ?>
	      <option <?Php if ($Tic_Cod == $row_rs_tip_compr['Tic_Cod']){ echo "selected";} ?> value="<?php echo $row_rs_tip_compr['Tic_Cod']?>"><?php echo $row_rs_tip_compr['Tic_Des'];?></option>
	      <?php
     	   } 
		?>
	      </select></td>
  </tr>
</table>  
</FIELDSET>
<FIELDSET>
<legend>
	<label class="Titulos2">Buscar por:</label>
</legend>
<table width="1009" border="0">
    <tr>
      <td width="160"><input name="op_opciones" type="radio" value="d" onClick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)"  style="cursor:pointer" checked>
        <span class="Etiqueta1">Proveedor	<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
		<input name="hdd_Pec_Cod" id="hdd_Pec_Cod" type="hidden" value="<?Php echo $hdd_Pec_Cod; ?>"></span></td>
      <td width="135"><input type="radio" name="op_opciones" value="c" style="cursor:pointer" onclick="setfocus(this.form.txt_busqueda)" />
        <span class="Etiqueta1">C.I/R.U.C </span></td>
      <td width="166"><input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)" style="cursor:pointer">
        <span class="Etiqueta1">No. Documento </span></td>
      <td width="530">
		<?php 
		/**
		* Parametro de la busqueda por fecha en compras 
		*/
		$Com_Fecha="AND MONTH(Cop_Fec)"; 
		?>			  
	  <?Php require_once('../../componentes/FRONT/com_con_meses.php');?>
      </td>
    </tr>
  </table>
<table width="610" height="36" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="405" height="28" class="BarraBusqueda"><span class="Asterisco">* </span>Busqueda:
      <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50" />
      &nbsp; </td>
    <td width="205" align="center"><button type="button" name="btn-buscar" id="btn-buscar" class="btn btn-success fileinput-button" title="Buscar compras" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button></td>
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
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="5%">C&oacute;d. Int. </th>
	    <th width="9%">Tipo documento</th>
	    <th width="10%">No. Documento</th>
        <th width="10%">No.  Retenci&oacute;n </th>
        <th width="10%">No. Compr </th>
        <th width="7%">Fecha </th>	  		  
	    <th width="32%" align="center">Proveedor</th>
	    <th width="5%">&nbsp;</th>         
		<th width="5%">&nbsp;</th>
      </tr>
     </thead>
     <tbody>
	  <?Php 	  
	  if(count($rs_buscar) > 0) 
		{	
			/**
			* Inicializo $i en 0 
			*/
			$i=0;  
			$existe_pagos=0;
	  		foreach($rs_buscar as $row_rs_buscar)
			{ 
				$i++; 
				/*consultamos si la compra tiene reposicion de caja chica*/
				$rs_CopCajaChica=$obBD_con1->getRowConsulta(1108, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
				
				if($row_rs_buscar['Cop_Est']=='I' )
				{ $rojo='#FF0000'; $anulada++; }else{$rojo='';}									
				/**
				* Consulto si la factura de compra ya tiene pagos realizados 
				*/
                $row_rs_comprobante_compra=$obBD_con1->getRowConsulta(345, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
				$row_rs_existe_pagos =$obBD_con1->getArrayConsulta(357, $row_rs_comprobante_compra['Com_Cod'], $obBD_conexion);	
				if(count($row_rs_existe_pagos) > 0) 
					$existe_pagos++; 
				?>         
		  	<form name="form1" method="post" action="<?Php echo $_SERVER['PHP_SELF']?>"> 
            <tr>            
		  		<td align="center"><FONT COLOR="<?php echo $rojo;?>">
	  			  <?Php $Cop_Cod=$row_rs_buscar['Cop_Cod']; echo $Cop_Cod; ?>
	  			  </FONT></td>
	    		<td align="center"><font color="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Tic_Des']; ?></font></td>
			    <td align="center"><FONT COLOR="<?php echo $rojo;?>">
				<?Php  $Num_Fac=$row_rs_buscar['Cop_Num']; echo $Num_Fac; ?></FONT></td>
				<td align="center"><FONT COLOR="<?php echo $rojo;?>">
			  <?Php
    			/**
				* Consulto el codigo de la retencion a modificar 
				*/
				$row_rs_retencion_modificar=$obBD_con1->getRowConsulta(373, $row_rs_buscar['Cop_Cod'], $obBD_conexion);
			     echo $row_rs_retencion_modificar['Ret_Num'];
				?></FONT></td>
				<td align="center"><FONT COLOR="<?php echo $rojo;?>">
				<?Php 
				/**
				* Consultar el c�digo del comprobante 
				*/
				$row_numero_comprobante=$obBD_con1->getRowConsulta(366, $row_rs_buscar['Cop_Cod'], $obBD_conexion);				
				echo $row_numero_comprobante['Com_Num']; ?></FONT></td>
				<td align="center"><FONT COLOR="<?php echo $rojo;?>">
				<?Php  $Fec_Com=$row_rs_buscar['Cop_Fec']; echo $Fec_Com; ?></FONT></td>
				<td align="left"><FONT COLOR="<?php echo $rojo;?>"><?Php echo $row_rs_buscar['Prs_Ape'].' '.$row_rs_buscar['Prs_Nom']; ?></FONT></td>
				<td align="center"><button type="button" class="btn btn-success btn-info" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<?php echo $row_rs_buscar['Cop_Cod'];?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>				
                <td align="center">
                
				  <?Php 
				if ($row_rs_buscar['Cop_Est'] == 'A')
				{
					if($rs_CopCajaChica['Cop_Cod']==''){	
						if(count($row_rs_existe_pagos)==0)
						{ ?>     
								  
									<?php
								   /**
									* Creacion del campo REPOST
									*/
									$thisPost->startPost();  ?>                                   
									<input type="hidden" id="op_opciones" name="op_opciones" value="<?php echo $op_opciones; ?>" />
									<input type="hidden" id="Tic_Cod" name="Tic_Cod" value="<?php echo $Tic_Cod; ?>" />
									<input type="hidden" id="cmb_mes" name="cmb_mes" value="<?php echo $cmb_mes; ?>" />
									<input type="hidden" id="txt_busqueda" name="txt_busqueda" value="<?php echo $txt_busqueda; ?>" />
									<input type="hidden" id="Cop_Cod" name="Cop_Cod" value="<?php echo $row_rs_buscar['Cop_Cod']; ?>" />
									<input type="hidden" id="Pec_Cod" name="Pec_Cod" value="<?Php echo $Pec_Cod; ?>" />
									<input type="hidden" id="hdd_Pec_Cod" name="hdd_Pec_Cod" value="1" />
									<input type="hidden" id="elim" name="elim" value="<?Php echo $row_rs_buscar['Cop_Est']; ?>" />  
									<input type="hidden" id="op" name="op" value="1" />    
									<input type="hidden" id="Ret_Cod" name="Ret_Cod" value="<?php echo $row_rs_retencion_modificar['Ret_Cod']; ?>" /> 
									<input type="hidden" id="Com_Cod" name="Com_Cod" value="<?php echo $row_numero_comprobante['Com_Cod']; ?>" />             
									<button type="button" class="btn btn-danger delete" title="Este bot�n permite Anular la Compra y documentos asociados como: Comprobante contable, Retenci�n y Cheques" onclick="alert('<< Ud. se dispone a anular esta compra y documentos asociados como: Comprobante contable, Retenci�n y Cheques, para lo cual una vez realizada la transacci&oacute;n NO se podran reversar los cambios >>'); confirmacion2(this.form);">
									<i class="icon-ban-circle icon-white"></i>
									<span></span>
									</button>  
								  
					  <?Php
						}
						else
						{ 						
						?>
					  <img src="../../mascaras/model1/imagenes/finance_thumb_sm.png" title="La compra mantiene pagos asociados, para anular esta compra primero anule el pago asociado">	
					  <?php	}
					}else{ $existe_pagos++;
		   				?><img src="../../mascaras/model1/imagenes/32x32/caja_chica.jpg" title="Posee reposici&oacute;n de Caja Chica" width="22" height="22"><?php						
					}
				}
				else
				{
						/**
						* Cuando la compra esta anulada, simplemente no muestra nada
						*/
						echo "&nbsp;";
				}
			?>       
                                              
			    </td>
	  		</tr>  
            </form>    
  		  <?Php 
		  }//FIn del foreach
	  }
	  else
	  { ?>
	  	<tr>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" ><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	    <td align="center" >&nbsp;</td>
	    <td align="center" >&nbsp;</td>
	    </tr>
		<?Php } ?>
      </tbody>  
  </table>
  <?Php echo barra_estado(count($rs_buscar));  
	/**
	* Control para ocultar el detalle de las filas 
	*/
	if(count($rs_buscar) != 0)
	{
		//ocultarDetalle(count($rs_buscar));
	}
?>
</FIELDSET>
<?Php
if ($anulada > 0)
{		
	$com_leyenda[1]=$anulada;
}//Fin del if ($anulada > 0)
?>
<br/>
<?php require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
<br>  
<?Php 
/**
* Si existe en la b�queda factura(s) con pagos realizados muestra la siguiente leyenda 
*/
if($existe_pagos>0){ /* inicio if($existe_pagos>0){  */ ?>
<table cellpadding="0" cellspacing="0">
     <tr>
       <td><fieldset>
         <legend>
           <label class="Titulos2">Leyenda:</label>
         </legend>
         <table width="96%" border="1" cellpadding="0" cellspacing="0">
           <tr>
             <td bgcolor="#FFFFFF" align="center"><img src="../../mascaras/model1/imagenes/32x32/caja_chica.jpg" title="Posee reposici&oacute;n de Caja Chica" width="22" height="22" /></td>
             <td bgcolor="#9CB8CF">&nbsp;</td>
             <td class="Cuerpo_ajax" align="center"><strong>La compra posee reposici&oacute;n de caja chica</strong></td>
           </tr>
           <tr>
             <td width="30" bgcolor="#FFFFFF" align="center"><img src="../../mascaras/model1/imagenes/32x32/dinero.png" width="22" height="22"></td>             
             <td width="69" bgcolor="#9CB8CF">&nbsp;</td>
             <td width="229" class="Cuerpo_ajax" align="center"><strong>La compra mantiene pagos vigentes </strong></td>
           </tr>
           <tr>
             <td width="30" bgcolor="#FFFFFF" align="center"><img src="../../mascaras/model1/imagenes/32x32/fac_ele.jpg" width="22" height="22"></td>
             <td width="69" bgcolor="#9CB8CF">&nbsp;</td>
             <td width="229" class="Cuerpo_ajax" align="center"><strong>Posee Retenci&oacute;n electr&oacute;nica Autorizada</strong></td>
           </tr>
         </table>
       </fieldset></td>
     </tr>
   </table>
 <?Php } /* fin if($existe_pagos>0){   */ 
	}
} ?>
 </td>
</tr>
</table>
</div>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
        	 <div id="mostrar"></div>
	</div>
</div> 
	<script type="text/javascript" src="../VALIDACIONES/fac_par_compras.js"></script>
    <script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  
</BODY>
</HTML>
<?Php
/**
* Cierra las conexiones 
*/
$obBD_con1->liberar();
$obBD_conexion->cerrar();
/**
* Fin cierre las conexiones 
*/
?>