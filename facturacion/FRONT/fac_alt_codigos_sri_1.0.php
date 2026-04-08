<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Permite registrar Los Codigos renta_iva 
 * 
 * @author Jose Cumbicos
 * @version 1.0
 * Fecha de actualizaci�n:	2014-07-30
 * 
 * @package tesoreria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/fac_log_codigos_sri.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	
require_once('../../Librerias/postclass.php');	

  /**
   * objeto para la conexion
   * @var Class_Log_Conexion_Tes
   */
  $obBD_conexion = new Class_Log_Conexion_Cod($Ses_Dat_Dis);
  
  /**
   * objeto para consultas
   * @var Class_Log_Datos_Tes
   */
  $obBD_con1 =  new Class_Log_Datos_Cod;
  $obBD_ins1 =  new Class_Log_Datos_Cod;
  
  /**
   * Llamado de la libreria para evitar el reenvio de datos
   * @var Post_Block
   */
  $thisPost = new Post_Block;


if (isset($hdd_save))
{
	if ($thisPost->postBlock($_POST['postID']))
	{			
	   /**
	   * inicio de la transaccion 
	   */
	   $obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
		
		/** 
		* Se guarda la renta_iva
		*/
		$Ren_Ing='N';
		/* damos de baja al codigo actual */
		$obBD_ins1->operacionobBD(15,$Ren_Sri,$obBD_conexion);	
		
		$Adq=explode("*",$Ren_Tip);
			
		/* ingresamos el codigo */
		$obBD_ins1->operacionobBD(2,$Ren_Sri."*".$Ren_Con."*".$Ren_Por."*".$Ren_Ing."*".$Adq[1]."*".$Ren_Ret.'*'.$Adq[0], $obBD_conexion);
		$Ren_Cod = $obBD_ins1->insercionid ($obBD_conexion->conexion);	
		if($Hdd_Pld_Cod!="")
		{
			/** 
			* Se guarda la reniva_pla
			*/
			$obBD_con1->operacionobBD(8,$Ren_Cod."*".$Hdd_Pld_Cod, $obBD_conexion);
		}
		
		/**
		* fin de la transacci�n 
		*/
		$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
	}
}

/*
* Ajax para validar la existencia de una marca
*/
if (isset($ajax_mar))
{	
	/** 
	* consulta  si existe hay otro codigo igual  
	*/
	$row_rs_con_mar = $obBD_con1->getRowConsulta(1, $Ren_Sri, $obBD_conexion);
	if (count($row_rs_con_mar) <> 0)
	{ ?>
		<input name="Ren_Sri" type="text" id="Ren_Sri" value="<?php echo $Ren_Sri;?>" size="10" maxlength="10" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Ren_Sri=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/error.gif" width="16" height="16" />&nbsp;<span class="Alertas">�El c&oacute;digo: <?php echo strtoupper($Ren_Sri)."(".$row_rs_con_mar['Ren_Por']."%)"; ?> ser&aacute; remplazado!</span>  
	<?php
	}
	else
	{ ?>
		<input name="Ren_Sri" type="text" id="Ren_Sri" value="<?php echo $Ren_Sri; ?>" size="10" maxlength="10" style="text-transform:uppercase" onblur="if (trim(this.value) != '')ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Ren_Sri=' + this.value,'div_mar')">&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif" />        
	<?php	
		}					
		?>
        <script language="javascript">
			document.getElementById('btnGuardar').disabled=false;
		</script>
        <?php
exit();
}

if (isset($ajax_con))
{	
	/** 
	* Consultar si existe otro concepto igual
	*/
	$row_rs_con_mar = $obBD_con1->getArrayConsulta(3, strtoupper($Ren_Con).'*'.$Ren_Por, $obBD_conexion);	
	if (count($row_rs_con_mar) > 0)
	{ ?>
		<input name="Ren_Con" type="text" id="Ren_Con" value="" size="50" style="text-transform:uppercase" 
            onblur="if (trim(this.value) != ''){ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_con=1&Ren_Por='+ document.getElementById('Ren_Por').value +'&Ren_Con='+this.value,'div_RenCon')}" />&nbsp;<img src="../../mascaras/model1/imagenes/32x32/btn_close.gif" width="16" height="16" /><span class="Alertas3">	�El concepto: <?php echo strtoupper($Ren_Con)." con porcentaje %".$Ren_Por." "; ?> ya existe!</span>            
	<?php
	}
	else
	{ ?>
		<input name="Ren_Con" type="text" id="Ren_Con" value="<?php echo $Ren_Con; ?>" size="50" style="text-transform:uppercase"onblur="if (trim(this.value) != ''){ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_con=1&Ren_Por='+ document.getElementById('Ren_Por').value +'&Ren_Con='+this.value,'div_RenCon')}">&nbsp;<img src="../../mascaras/model1/imagenes/ok-s.gif" />        
	<?php	
		}					
		?>
        <script language="javascript">
			document.getElementById('btnGuardar').disabled=false;
		</script>
        <?php
exit();
}

if ($ajax_buscador=="F")
{	
	if ($op_opciones=='d')
	{
		// Cargado de los resultados de la busqueda por descripcion de la cuenta
		$row_rs_buscta = $obBD_con1->getArrayConsulta(5,$ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod,$obBD_conexion);
		$total_rs_buscta=count($row_rs_buscta);
	}
	if ($op_opciones=='c')
	{
		// Cargado de los resultados de la busqueda por codigo de la cuenta
		$row_rs_buscta = $obBD_con1->getRowConsulta(6,$ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod,$obBD_conexion);
		$total_rs_buscta=$row_rs_buscta['Pld_Cod'] > 0? 1 : 0;
	}					
	?>
	<table width="100%" height="20" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	 <thead> 
      <tr>
	    <th width="8%"><strong>C&oacute;d. Int.</strong></th>
		<th width="11%"><strong>C&oacute;digo</strong></th>
		<th width="35%"><strong>Descripci&oacute;n</strong></th>
		<th width="22%"><strong>Grupo</strong></th>
		<th width="11%"><strong>Tipo</strong></th>
		<th width="9%"><strong>Estado</strong></th>
		<th width="4%">&nbsp;</th>
		</tr>
     </thead>
     <tbody>
	  <?php
	  if ($total_rs_buscta > 0) {
	  foreach($row_rs_buscta as $row) { 
		/* Consulta del detallete de la CUENTA */
		$row_rs_recur = $obBD_con1->getRowConsulta(7,$row['Pld_Rec'],$obBD_conexion);			
		/* Consulta del detalle de la CUENTA (OTRO) */
		$row_rs_grupo = $obBD_con1->getRowConsulta(7, $row['Pld_Rec'],$obBD_conexion);
						  						  
	  ?>
	  <tr>
	    <td align="center"><?php echo $row['Pld_Cod']; ?></td>
		<td align="left"><?php echo $row['Pld_Cdc']; ?></td>
		<td align="left"><?php echo $row['Pld_Des']; ?></td>
		<td>
        <div align="center">
		<?php if ($row_rs_recur['Pld_Des'] != "")
			{ 	
				echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; 
			}else{ 
				echo "&nbsp;"; 
			} ?>
        </div></td>
		<td align="center"><div align="center"><?php echo $row['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><?php echo $row['Pld_Est']; ?></div></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="document.getElementById('Hdd_Pld_Cod').value='<?php echo $row['Pld_Cod'];?>'; document.getElementById('Pld_Des').value='<?php echo $row['Pld_Des'];?>';document.getElementById('Pld_Cdc').value='<?php echo $row['Pld_Cdc'];?>'">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>        
        </td> 
	  </tr>
	  <?php };
	  } else { ?>
		<tr>
          <td class="Alertas">&nbsp;</td>
		  <td class="Alertas">&nbsp;</td>
		  <td align="center" class="Alertas"><?php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		  <td class="Alertas">&nbsp;</td>
		  <td class="Alertas">&nbsp;</td>
		  <td class="Alertas">&nbsp;</td>
		  <td class="Alertas">&nbsp;</td>
		  
		</tr>
	  <?php }?>
    </tbody>
	</table>
	
<?php 
	exit();
}
?>

<HTML>
	<HEAD>
    	<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/tes_val_codigos_sri.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
         <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
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
	  <td height="10">&raquo; Registrar C&oacute;digos SRI</td>
  </tr>
	<tr>
        <td height="400" valign="top">
        <form method="post" name= "form1" action="<?php echo $_SERVER['PHP_SELF'];?>">        
        <?php 
        /** 
        * Creacion del campo repost 
        */
        $thisPost->startPost();
        ?>
        <FIELDSET>
        <LEGEND>
        <label class="Titulos2">Datos a registrar</label>
        </LEGEND>
        <?php echo mensaje_requerido(); 
		noEnterSubmit();
		?>
        <table width="1188" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> C&oacute;digo Sri:</td>
            <td colspan="2">
              <div id="div_mar">
                <input name="Ren_Sri" type="text" id="Ren_Sri" value="" size="10" maxlength="10" style="text-transform:uppercase" onblur="if (trim(this.value) != ''){ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_mar=1&Ren_Sri='+this.value,'div_mar')}">
                </div>
              </td>
          </tr>
          <tr>
            <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Porcentaje (%):</td>
            <td colspan="2" class="Titulos2">            
              <input name="Ren_Por" type="text" id="Ren_Por" onKeyPress="return validar_decimal(event)" style="text-align:right" size="10" maxlength="10"/>            
              </td>
          </tr>
          <tr>
            <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Bienes/Servicios:</td>
            <td colspan="2" class="LetraNegra">
            <?php
            	/* Consulta adquisicion  */
				$row_rs_adqui = $obBD_con1->getArrayConsulta(16,'',$obBD_conexion);		
			?> 
              <select name="Ren_Tip" id="Ren_Tip">
                <option value="">Seleccione...</option>                
                <?php foreach($row_rs_adqui as $row){?>
                	<option value="<?php echo $row['Adq_Cod'].'*'.$row['Adq_Cor'];?>" ><?php echo $row['Adq_Des'];?></option>                
                <?php }?>
              </select>
            </td>
          </tr>
          <tr>
            <td width="106" class="Etiqueta1"><span class="Asterisco">*</span> Renta/Iva:</td>
            <td colspan="2" class="LetraNegra">
              <select name="Ren_Ret" id="Ren_Ret">
                <option value="">Seleccione...</option>
                <option value="R">Renta</option>
            	<option value="I">Iva</option>
              </select>
            </td>
          </tr>
          <tr>
            <td class="Etiqueta1"><span class="Asterisco">*</span> Descripci&oacute;n:</td>
            <td colspan="2">
            <div id="div_RenCon">
              <input name="Ren_Con" type="text" id="Ren_Con" style="text-transform:uppercase" onblur="if (trim(this.value) != ''){ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_con=1&amp;Ren_Por='+ document.getElementById('Ren_Por').value +'&amp;Ren_Con='+this.value,'div_RenCon')}" value="" size="50" />
            </div></td>
          </tr>
          <tr>
            <td width="106" class="Etiqueta1">Cuenta Contable:</td>
            <td width="308">
            <input name="Pld_Des" type="text" id="Pld_Des" style="text-transform:uppercase" value="" size="50" maxlength="50" readonly="readonly" />
            <input type="hidden" name="Hdd_Pld_Cod" id="Hdd_Pld_Cod" value=""/>                               
            </td>
            <td width="774">
            <button type="button" class="btn btn-success btn-mini" title="Buscar" onClick="Muestra_Aparecer()">
	    <i class="icon-search icon-white"></i>
	    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
	    	</button>
            </td>
          </tr>
               <tr>
                <td width="106" class="Etiqueta1">C&oacute;digo Cuenta:</td>
                <td colspan="2">
                    <input type="text" id="Pld_Cdc" value="" readonly/>
                  </td>
              </tr>
        </table>     
	  </FIELDSET>	
      <br />
      <input name="hdd_save" type="hidden" id="hdd_save" value="insertar">
       <button type="button" id="btnGuadar" class="btn btn-primary start" title="Guardar" onclick="validar_requeridos(this.form, 'Ren_Sri*Ren_Por*Ren_Tip*Ren_Ret*Ren_Con', 1)">
               <i class="icon-book icon-white"></i>
               <span>Guardar</span>
      </button>         
      </form>    
      <br/>
      
      <td/>
  <tr/>    
</table>
</div>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal" style="display:none">		
	<div id="ajax_modal">     
        <?php
		/** 
		* Consultar el plan de cuenta activo
		*/
		$row_rs_con_plan = $obBD_con1->getRowConsulta(4, $Ses_Emp_Cod, $obBD_conexion);	
		$Pla_Cod=$row_rs_con_plan['Pla_Cod'];
        $tipo_busc="F" ;
		$Capa="div_1"; 
		$Nombre_Buscador ="txtBuscar";
		$Nombre_Opciones="opt";
		?>
		<FIELDSET>
        <LEGEND>
            <label class="Titulos2">B&uacute;squeda de Cuentas</label>
        </LEGEND>	
		<table width="44%" border="0">
                <tr>
                  <td width="51%">
                  <input id="<?php echo $Nombre_Opciones; ?>" name="<?php echo $Nombre_Opciones; ?>" type="radio" checked="checked" value="d" onClick="document.getElementById('<?php echo $Nombre_Opciones; ?>').value='d'; setfocus(this.form.<?php echo $Nombre_Buscador; ?>)">
                      <span class="LetraNegra"><strong>Descripci&oacute;n</strong></span>
                  </td>
                  <td width="49%">
                <input id="<?php echo $Nombre_Opciones; ?>" name="<?php echo $Nombre_Opciones; ?>" type="radio" value="c" onClick="document.getElementById('<?php echo $Nombre_Opciones; ?>').value='c'; setfocus(this.form.<?php echo $Nombre_Buscador; ?>)">
                      <span class="LetraNegra"><strong>C&oacute;digo</strong></span>
                  </td>
                </tr>
              </table>                           
        <table height="36" border="0" cellpadding="0" cellspacing="0">
            <tbody>
          <tr>
              <td width="80" height="28" class="BarraBusqueda" style="border-right: 0px;padding-right: 10px;padding-left: 10px;"><div align="right"><strong>B&uacute;squeda</strong></div></td>
              <td width="387" class="BarraBusqueda" style="border-left: 0px;"><input class="text" id="<?php echo $Nombre_Buscador; ?>" name="<?php echo $Nombre_Buscador; ?>"  onKeyUp="parametro_injection(this)" onchange="if (trim(document.getElementById('<?php echo $Nombre_Buscador; ?>').value) != ''){ ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=<?php echo $tipo_busc; ?>&ajax_buscod='+document.getElementById('<?php echo $Nombre_Buscador; ?>').value+'&op_opciones='+document.getElementById('<?php echo $Nombre_Opciones; ?>').value+'&Pec_Cod=<?php echo $Com_Pec_Cod; ?>&Pla_Cod=<?php echo $Pla_Cod; ?>','<?php echo $Capa; ?>') }" type="text" size="50" maxlength="50" placeholder="Ingrese cuenta a buscar..." autofocus /></td>
              <td width="109" align="center">
                <button type="button" onclick="if (trim(document.getElementById('<?php echo $Nombre_Buscador; ?>').value) != ''){ ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=<?php echo $tipo_busc; ?>&ajax_buscod='+document.getElementById('<?php echo $Nombre_Buscador; ?>').value+'&op_opciones='+document.getElementById('<?php echo $Nombre_Opciones; ?>').value+'&Pec_Cod=<?php echo $Com_Pec_Cod; ?>&Pla_Cod=<?php echo $Pla_Cod; ?>','<?php echo $Capa; ?>') }" class="btn btn-success fileinput-button" title="Buscar cuenta" >
               <i class="icon-search icon-white"></i>
               <span>Buscar</span>
               </button></td>
          </tr>
            </tbody>
        </table>      
                    <div id="<?php echo $Capa; ?>" style="padding-top: 15px;"></div>
           </FIELDSET>     
    </div>
</div>
<script type="text/javascript" src="../VALIDACIONES/fac_par_marca.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  	
</BODY>
</HTML>
<?php
/* 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();	
?>
