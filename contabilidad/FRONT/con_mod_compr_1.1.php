<?
/**
* @abstract Permite modificar los comprobantes contables
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualizaciï¿½n: 2010-11-16
* Fecha de actualizaciï¿½n  2012-04-25
* Fecha de actualizaciï¿½n  2015-05-01
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

if (isset($loadCuenta)){
    $response['fila']=$fila;
    $response['rows'] = $obBD_con1->getArrayConsulta(332, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
    $response['total']=count($response['rows']);
    if($response['total']==1) $response['success']=true;
    else {$response['success']=false;$response['rows']=null;}
    utf8_encode_deep($response);
    echo json_encode($response);
    exit();
}
if(isset($changeCliente)){         
        if($op=='I') $campo='Cli_Cod'; else  $campo='Prv_Cod';
        $obBD_con1->inicio_transaccion($obBD_conexion->conexion);
        $obBD_con1->grabarv_registros(sentencias_con(364,$obBD_con1->parametros($campo.'*'.$Cod_Cod.'*'.$Com_Cod)), $obBD_conexion->conexion);
        $obBD_con1->fin_transaccion_nomsn($obBD_conexion->conexion);
        if($obBD_con1->Error==0) $responce['success']=true; else $responce['success']=false;$responce['message']=$obBD_con1->MsgError;
	echo json_encode($responce); exit();
}
if (isset($ajax_cliente))
{ 	?>
                
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
		<td>			
		<FIELDSET>
		<LEGEND>
                    <label class="Titulos2">B&uacute;squeda de <?php if($op=='I') echo 'Cliente'; else echo 'Proveedor'; ?> </label>
		</LEGEND>
                    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="form2" id="formSearch">
		<table width="450" border="0">
			<tr>
			  <td width="205">
                              
                              <input id="op_opciones" name="op_opciones" type="radio" value="d" checked onClick="setfocus(this.form.buscta)">
			  <span class="LetraNegra"><strong>Apellido</strong></span></td>
			  <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="c" onClick="setfocus(this.form.buscta)">
				<span class="LetraNegra"><strong>C&eacute;dula/R.U.C.</strong></span></td>
			</tr>
		</table> 
		<table width="554" height="36" border="0" cellpadding="0" cellspacing="0" >
		<tbody id="tbusqueda">
		  <tr>
			<td width="85" height="28" class="BarraBusqueda"><div align="right"><strong>B&uacute;squeda</strong>:</div></td>
			<td width="359" class="BarraBusqueda"><input type="text" value="" style="display: none" /><input name="buscta" type="text" id="buscta" size="50" maxlength="50" onKeyPress="if (trim(document.getElementById('buscta').value) != ''&&event.keyCode == 13){var form = this.form;ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscodcli='+document.getElementById('buscta').value+'&op_opciones='+form.elements['op_opciones'].value+'&Com_Cod=<?php echo $Com_Cod; ?>&op=<?php echo $op; ?>','busqueda');}" /></td>
			<td width="110" align="center">
            <button type="button" class="btn btn-success" title="Buscar" onClick="var form = this.form;ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscodcli='+document.getElementById('buscta').value+'&op_opciones='+form.elements['op_opciones'].value+'&Com_Cod=<?php echo $Com_Cod; ?>&op=<?php echo $op; ?>' ,'busqueda')"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
			</td>
		  </tr>
		</tbody>
		</table></form>  
		<div id="busqueda">
		</div>
		</FIELDSET>
		</td>
  	</tr>
                </table>      
<?php		
exit();	
}

if (isset($buscodcli))
{
		if ($op_opciones=='d')
		{
			/**
			* Cargado de los resultados de la busqueda por descripcion de la cuenta
			*/
                    if($op=='I')
			$rs_buspro = $obBD_con1->getArrayConsulta(317,trim($buscodcli).'*'.$Ses_Emp_Cod, $obBD_conexion);
                    else
                        $rs_buspro = $obBD_con1->getArrayConsulta(320,trim($buscodcli).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
		elseif ($op_opciones=='c')
		{
			/**
			* Cargado de los resultados de la busqueda por codigo de la cuenta
			*/
                    if($op=='I')
			$rs_buspro = $obBD_con1->getArrayConsulta(318,trim($buscodcli).'*'.$Ses_Emp_Cod, $obBD_conexion);
                    else
                        $rs_buspro = $obBD_con1->getArrayConsulta(321,trim($buscodcli).'*'.$Ses_Emp_Cod, $obBD_conexion);
		}
                //cambio
//		$row_rs_buspro = $obBD_con1->registros();
//		$total_rs_buspro = $obBD_con1->numregistros();
                $row_rs_buspro = $rs_buspro;
		$total_rs_buspro = count($rs_buspro);
                ?><div style="padding: 3px;"></div>
<table width="100%" border="1" cellpadding="0" cellspacing="0">
      <tr class="Cabecera1">
        <td width="40"><strong>C&oacute;d. Int. </strong></td>
        <td width="80"><strong>C&eacute;dula/R.U.C.</strong></td>
        <td><strong> <?php if($op=='I') echo 'Cliente'; else echo 'Proveedor'; ?> </strong></td>
        <td width="22">&nbsp;</td>
        </tr>
      <?php
	  if ($total_rs_buspro > 0) 
	  {
              //do { 
               foreach ($row_rs_buspro as $row){   
           ?>
      <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
        <td align="center"><?php if(isset($row['Cli_Cod']))echo $row['Cli_Cod']; else echo $row['Prv_Cod'];  ?></td>
        <td align="left"><?php echo $row['Prs_Ced']; ?></td>
        <td align="left"><?php echo $row['Prs_Ape'].' '. $row['Prs_Nom']; ?></td>
        <td align="center">
            <button type="button" class="btn btn-success btn-mini" onClick="changeCliente(<?php if(isset($row['Cli_Cod']))echo $row['Cli_Cod']; else echo $row['Prv_Cod']; ?>,<?php echo $Com_Cod; ?>,'<?php echo $row['Prs_Ape'].' '. $row['Prs_Nom']; ?>','<?php echo $row['Prs_Ced']; ?>','<?php echo $op; ?>')">
		        <i class="icon-arrow-right icon-white"></i>
            </button>
        </td>
        </tr>
      <?php 
               }
            //} while ($row_rs_buspro = $obBD_con1->fetch_assoc($rs_buspro));
	  } else { ?>
	  	<tr><td colspan="4" align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
	  	</tr>
	  <?php }?>
    </table>
    <?php echo barra_estado($total_rs_buspro); ?>	 
<?php 
@$obBD_con1->free_result($rs_buspro);
@$obBD_con1->free_result($rs_combo);
exit();
}//Fin del if (isset($buscod))
/**
* Buscador para agregar cuentas al comprobante
*/
if (isset($ajax_buscador))
{ ?>
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2"> 	
<? noEnterSubmit();?>	
<table width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr>
    <td>	
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Bï¿½squeda de Cuentas</label>
	</LEGEND>
	<table width="481" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="205"><input id="op_opciones" name="op_opciones" type="radio" checked="checked" value="d" onClick="document.getElementById('op_opciones').value='d'; setfocus(this.form.buscta)">
			<span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
		  <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="c" onClick="document.getElementById('op_opciones').value='c'; setfocus(this.form.buscta)">
			<span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
		</tr>
	</table>
	<table width="600" height="36" border="0" cellpadding="0" cellspacing="0">
	<tbody id="tbusqueda">
      <tr>
        <td width="80" height="28" class="BarraBusqueda"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
        <td width="387" class="BarraBusqueda"><input type="text" value="" style="display: none" /><input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod1=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','busqueda')}
		"></td>
        <td width="109" align="center">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar cuenta" onClick="if (trim(document.getElementById('buscta').value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod1=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','busqueda') }">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button></td>
      </tr>
	</tbody>
    </table>
	<div id="busqueda"></div>
	</FIELDSET>
   </td>
  </tr>
 </table>
</form>
<?Php	
exit();
}
/**
* Buscador para editar cuentas de la contabilizaciï¿½n
*/
if (isset($ajax_buscador2))
{ ?>
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2"> 
<? noEnterSubmit();?>	
<table width="100%" border="0" cellspacing="0" cellpadding="0">
 <tr>
    <td>	
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Bï¿½squeda de Cuentas</label>
	</LEGEND>
	<table width="481" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="205"><input id="op_opciones" name="op_opciones" type="radio" checked="checked" value="d" onClick="document.getElementById('op_opciones').value='d'; setfocus(this.form.buscta)">
			<span class="LetraNegra"><strong>Descripci&oacute;n</strong></span></td>
		  <td width="266"><input id="op_opciones" name="op_opciones" type="radio" value="c" onClick="document.getElementById('op_opciones').value='c'; setfocus(this.form.buscta)">
			<span class="LetraNegra"><strong>C&oacute;digo</strong></span></td>
		</tr>
	</table>
	<table width="600" height="36" border="0" cellpadding="0" cellspacing="0">
	<tbody id="tbusqueda">
      <tr>
        <td width="80" height="28" class="BarraBusqueda"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
        <td width="387" class="BarraBusqueda"><input type="text" value="" style="display: none" /><input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod2=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','busqueda')}
		"></td>
        <td width="109" align="center">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar cuenta" onClick="if (trim(document.getElementById('buscta').value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod2='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','busqueda') }">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button></td>
      </tr>
	</tbody>
    </table>
	<div id="busqueda"></div>
	</FIELDSET>
   </td>
  </tr>
 </table>
</form>
<?Php	
exit();
}
/**
* Cargado AJAX de los resultados de la bï¿½squeda 1
*/
if (isset($ajax_buscod1))
{	
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(331, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}
	if ($op_opciones=='c')
	{
		/** 
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(332, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}//Fin del if ($op_opciones=='d')
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="6%">C&oacute;d. Int.</td>
		<td width="10%"><strong>Cï¿½digo</strong></td>
		<td width="24%"><strong>Cuenta</strong></td>
		<td width="20%"><strong>Grupo</strong></td>
		<td width="10%"><strong>Tipo</strong></td>
		<td width="10%"><strong>Estado</strong></td>
		<td width="5%">&nbsp;</td>
		<td width="5%">&nbsp;</td>						
		</tr>
      <tbody>  
	  <?
	  if (count($row_rs_buscta) > 0) {
	  foreach ($row_rs_buscta as $row)
	  { 
		/**
		* Consulta del detallete de la CUENTA 
		*/
		$row_rs_recur = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
		/**
		* Consulta del detallete de la CUENTA (OTRO) 
		*/
		$row_rs_grupo = $obBD_con1->getRowConsulta(204, $row_rs_recur['Pld_Rec'], $obBD_conexion);
	  ?>
	  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
	    <td><? echo $row['Pld_Cod']; ?></td>
		<td><div align="left"><? echo $row['Pld_Cdc']; ?></div></td>
		<td><div align="left"><?Php echo marcar_cadena($buscod, $row['Pld_Des'],'#FFFF00', 1);?></div></td>
		<td><div align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Est']; ?></div></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Agregar cuenta al Debe" onClick="nueva_fila('c_contenido','debe','<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '<?Php echo 
		$row['Pld_Cod']; ?>', '<?Php echo $row['Pld_Cdc']; ?>', '<?Php echo $row['Pld_Des']; ?>')">
        	<i class="icon-white">D</i>           
        </button>        
        </td>
		<td align="center">
         <button type="button" class="btn btn-success btn-mini" title="Agregar cuenta al Haber" onClick="nueva_fila('c_contenido','haber','<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '<?Php echo 
		$row['Pld_Cod']; ?>', '<?Php echo $row['Pld_Cdc']; ?>', '<?Php echo $row['Pld_Des']; ?>')">
        	<i class="icon-white">H</i>           
        </button>        
        </td>				
	  </tr>
	  <? } //FIn del foreach ($row as $row)
	  } else { ?>
		<tr><td colspan="9" class="Alertas"><?Php echo error_alerta("ï¿½No hay resultados que mostrar!", 1); ?></td>
		</tr>
	  <? }//Fin del if ($total_rs_buscta > 0)
	  ?>
      </tbody>
	</table>
<? 
 echo barra_estado(count($row_rs_buscta));
exit();
}//if (isset($buscod))
/**
* Cargado AJAX de los resultados de la bï¿½squeda 2
*/
if (isset($ajax_buscod2))
{	
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(331, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}
	if ($op_opciones=='c')
	{
		/** 
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(332, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}//Fin del if ($op_opciones=='d')
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="6%">C&oacute;d. Int.</td>
		<td width="10%"><strong>Cï¿½digo</strong></td>
		<td width="24%"><strong>Cuenta</strong></td>
		<td width="20%"><strong>Grupo</strong></td>
		<td width="10%"><strong>Tipo</strong></td>
		<td width="10%"><strong>Estado</strong></td>
		<td width="5%">&nbsp;</td>					
		</tr>
      <tbody>  
	  <?
	  if (count($row_rs_buscta) > 0) {
	  foreach ($row_rs_buscta as $row)
	  { 
		/**
		* Consulta del detallete de la CUENTA 
		*/
		$row_rs_recur = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
		/**
		* Consulta del detallete de la CUENTA (OTRO) 
		*/
		$row_rs_grupo = $obBD_con1->getRowConsulta(204, $row_rs_recur['Pld_Rec'], $obBD_conexion);
	  ?>
	  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
	    <td><? echo $row['Pld_Cod']; ?></td>
		<td><div align="left"><? echo $row['Pld_Cdc']; ?></div></td>
		<td><div align="left"><?Php echo marcar_cadena($buscod, $row['Pld_Des'],'#FFFF00', 1);?></div></td>
		<td><div align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Est']; ?></div></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Agregar cuenta" onClick="
        document.getElementById('datos[<?php echo $fila; ?>,1]').value = <?Php echo $row['Pld_Cod']; ?>;
        document.getElementById('datos[<?php echo $fila; ?>,2]').value = '<?Php echo $row['Pld_Cdc']; ?>'; 
        document.getElementById('datos[<?php echo $fila; ?>,3]').value = '<?Php echo $row['Pld_Des']; ?>';        
        ">
        	<i class="icon-ok icon-white"></i>           
        </button>        
        </td>
	  </tr>
	  <? } //FIn del foreach ($row as $row)
	  } else { ?>
		<tr><td colspan="8" class="Alertas"><?Php echo error_alerta("ï¿½No hay resultados que mostrar!", 1); ?></td>
		</tr>
	  <? }//Fin del if ($total_rs_buscta > 0)
	  ?>
      </tbody>
	</table>
<? 
 echo barra_estado(count($row_rs_buscta));
exit();
}//if (isset($buscod))

/**
* Cargado AJAX de los resultados de la bï¿½squeda
*/
if (isset($buscod))
{	
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(331, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}
	if ($op_opciones=='c')
	{
		/** 
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$row_rs_buscta = $obBD_con1->getArrayConsulta(332, $buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);
	}//Fin del if ($op_opciones=='d')
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="6%">C&oacute;d. Int.</td>
		<td width="10%"><strong>Cï¿½digo</strong></td>
		<td width="24%"><strong>Cuenta</strong></td>
		<td width="20%"><strong>Grupo</strong></td>
		<td width="10%"><strong>Tipo</strong></td>
		<td width="10%"><strong>Estado</strong></td>
		<td width="5%">&nbsp;</td>
		<td width="5%">&nbsp;</td>						
		</tr>
      <tbody>  
	  <?
	  if (count($row_rs_buscta) > 0) {
	  foreach ($row_rs_buscta as $row)
	  { 
		/**
		* Consulta del detallete de la CUENTA 
		*/
		$row_rs_recur = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
		/**
		* Consulta del detallete de la CUENTA (OTRO) 
		*/
		$row_rs_grupo = $obBD_con1->getRowConsulta(204, $row_rs_recur['Pld_Rec'], $obBD_conexion);
	  ?>
	  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo"); ?> class="Fondo">
	    <td><? echo $row['Pld_Cod']; ?></td>
		<td><div align="left"><? echo $row['Pld_Cdc']; ?></div></td>
		<td><div align="left"><?Php echo marcar_cadena($buscod, $row['Pld_Des'],'#FFFF00', 1);?></div></td>
		<td><div align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><? echo $row['Pld_Est']; ?></div></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Agregar cuenta al Debe" onClick="nueva_fila('c_contenido','debe','<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '<?Php echo 
		$row['Pld_Cod']; ?>', '<?Php echo $row['Pld_Cdc']; ?>', '<?Php echo $row['Pld_Des']; ?>')">
        	<i class="icon-white">D</i>           
        </button>
		</td>
		<td align="center">
		<button type="button" class="btn btn-success btn-mini" title="Agregar cuenta al Haber" onClick="nueva_fila('c_contenido','haber','<?Php echo $_SERVER['PHP_SELF']; ?>?Pec_Cod=<?Php echo $Pec_Cod; ?>', '<?Php echo 
		$row['Pld_Cod']; ?>', '<?Php echo $row['Pld_Cdc']; ?>', '<?Php echo $row['Pld_Des']; ?>')">
        	<i class="icon-white">H</i>           
        </button>         
        </td>				
	  </tr>
	  <? } //FIn del foreach ($row as $row)
	  } else { ?>
		<tr><td colspan="9" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
	  <? }//Fin del if ($total_rs_buscta > 0)
	  ?>
      </tbody>
	</table>
<? 
 echo barra_estado(count($row_rs_buscta));
exit();
}//if (isset($buscod))

/** 
* Cargado de Informaciï¿½n a travï¿½s de AJAX 
*/
if (isset($codigo))
{
	/** 
	* Consulta las cuentas 
	*/
	$row_rs_buscli = $obBD_con1->getRowConsulta(319, $codigo.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod, $obBD_conexion);		

	if (count($row_rs_buscli) > 0)
	{
		$cuenta=$row_rs_buscli['Pld_Des'];
		$codigo=$row_rs_buscli['Pld_Cod'];
	} else {
		$cuenta="Cuenta Inexistente";
		$codigo=0;
	}
		
	if (isset($cuenta)) { 
	  $return_value = '<?xml version="1.0" standalone="yes"?><cuenta><descripcion>'.utf8_encode($cuenta).'</descripcion><codigo>'.$codigo.'</codigo></cuenta>';
	}
	header('Content-Type: text/xml'); 
	echo $return_value;	
	exit();
}//Fin del if (isset($codigo)) ?>

<?Php
/** 
* CONTROL PARA EVITAR DOBLE GUARDADO EN EL REENVIO 
* DE LA PAGINA  
*/
require_once('../../Librerias/postclass.php');	
/** 
* Creaciï¿½n del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

/** 
* Consulta para la eleccion del periodo contable
*/
if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($Com_Cod))
{
	/** 
	* Carga los periodos contables 
	*/
	$row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);			
}//Fin del if ($Pec_Cod)
else
{	
	/** 
	* Evitar el reenvio de formularios 
	*/
	if ($thisPost->postBlock($_POST['postID']))
	{
		/**
		* Guardado del comprobante modificado
		*/
		if (isset($bt_save) && !isset($hdd_volver))
		{	
			/**
			* Inicio de la transaccion
			*/
			$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
			/** 
			* Carga el aï¿½o de la fecha incial 
			*/
			$var_mes = explode('-', $Com_Fec);
		
			$hdd_mes = explode('-', $Hdd_Com_Fec);
			
			/** 
			* Control para cambiar el codigo del comprobante cuando se cambia de fecha del comprobante 
			*/
			if ($var_mes[1] != $hdd_mes[1])
			{
				/** 
				* Consulta el numero del comprobante de Egreso/Diario 
				*/
				$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);
			}//Fin del if ($mes[1] != $hdd_mes[1])
	
			/** 
			* Actualizacion de la cabecera del Comprobante 
			*/
			$obBD_con1->grabarv_registros(sentencias_con(355,$obBD_con1->parametros($Com_Num.'*'.$Com_Con.'*'.$Com_Val.'*'.$Com_Obs
													.'*'.$Com_Cod.'*'.$Com_Fec.'*'.$Tia_Cod)),$obBD_conexion->conexion);	
			/**
			* Control para eliminar las cuentas que han sido eliminadas 
			*/
			if ($oculto != "")
			{
				$datos_elim = explode('*', $oculto);
				for ($i=1; $i<=count($datos_elim)-1;$i++)
				{
					$obBD_con1->grabarv_registros(sentencias_con(329,$obBD_con1->parametros($datos_elim[$i])),
					$obBD_conexion->conexion);
				}
			}//Fin del if ($oculto != "")
				
			$indice_act = 0;
			foreach ($datos as $puntero => $item)
			{
				$cant++;
				$param[]=$item;
				if ($cant==5)
					{
						unset($indices);
						$cant=0;
						/**  
						* $codasi -- Arreglo que almacena el Asi_Cod
						* Separa el valor del puntero Ejemplo: 3,2 
						*/
						$indices = explode(',', $puntero);				
						$fila_indice = substr($puntero,0,strlen($indices[0]));
						/** 
						* Substrae del puntero el indice de la fila Ejmplo 3, 
						*/	
						if (isset($codasi[$fila_indice]))
						{				
							$obBD_con1->grabarv_registros(sentencias_con(151,$obBD_con1->parametros($param[4].'*'.$param[2].'*'.
											$param[3].'*'.$param[0].'*'.$codasi[$fila_indice])),$obBD_conexion->conexion);
						}// Fin del if (isset($contr)){
						else
						{
							if (substr($puntero,strlen($puntero)-1,1)==4)
							{																				
								$obBD_con1->grabarv_registros(sentencias_con(325,$obBD_con1->parametros($Com_Cod.'*'.'D'.'*'.
											$param[4].'*'.$param[2].'*'.$param[3].'*'.$param[0])),$obBD_conexion->conexion);										
							}//Fin del if (substr($puntero,strlen($puntero)-1,1)==4)					
							else 
							{
								$obBD_con1->grabarv_registros(sentencias_con(325,$obBD_con1->parametros($Com_Cod.'*'.'H'.'*'.
											$param[4].'*'.$param[2].'*'.$param[3].'*'.$param[0])),$obBD_conexion->conexion);	
							}//Fin del ELSE if (substr($puntero,strlen($puntero)-1,1)==4)									
						}		
						unset($param);
					}
				}
			$obBD_con1->fin_transaccion($obBD_conexion->conexion);
		}// Fin de Guardado
	}//Fin del if ($thisPost->postBlock($_POST['postID']))
	/**
	* Permite inicializar la variable OP por primera y unica vez 
	*/
	if (!(isset($op)))
	{
		$op = "I";
	}	   
	 
	/***************/
	/***************/	
	/*** OPCIONES***/
	/***************/
	/***************/
	switch ($op){	
		case "I":
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="cliente";
				$campo="Cli_Cod";
			}
		break;		
		case "E": //Inicio de la opcion 2
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;
		case "D": //Inicio de la opcion 3
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;
	}//Fin del switch ($op)

	if ($txt_busqueda !="") 
	{	
		if ($op_opciones == "d")
		{	
			/** 
			* Cargado de los comprobantes 
			*/		
			$row_rs_cabcomp = $obBD_con1->getArrayConsulta(154, $tabla.'*'.trim($txt_busqueda).'*'.$op.'*'.$Pec_Cod.'*'.$campo.'*'.$cmb_mes, $obBD_conexion);
		}
		else 
		{
			/**  
			* Control para busqueda mensual  
			*/
			$mes_array = explode('-', $txt_busqueda);
			if (count($mes_array)==2)
			{
				$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
				$busqueda = $mes_array[1];//$mes[1] es el numero del comprobante
			}
			$row_rs_cabcomp = $obBD_con1->getArrayConsulta(353, $tabla.'*'.trim($busqueda).'*'.$op.'*'.$Pec_Cod.'*'.$campo.'*'.$Par_Fec, $obBD_conexion);
		} 		
	}
	else 
	if (isset($cod))
	{
		/** 
		* Consulta de la informacion de un comprobante especifico 
		*/
		$row_rs_codcompr = $obBD_con1->getRowConsulta(354, $tabla.'*'.$cod.'*'.$op.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion);
	}//Fin del if (isset($cod))

	/**
	* Divide la cadena del periodo contable 
	*/
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];
	/** 
	* row_rs_periodo del periodo contable 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(113, $Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	$Pla_Cod = $row_rs_periodo['Pla_Cod'];
	
	/** 
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);			
}//Fin del else if ($Pec_Cod)

if(isset($ajax_info)){
	include('../COMPONENTES/con_con_detalleCompr.php');
exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    		
		<script language="javascript" src="../VALIDACIONES/XML.js"></script>
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_compr.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>         
       <script>
		$(function() { 
			/* Campo 1 */
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
		}); 		
        </script> 
        <style>
		#tbl_comp tr:hover {
			background-color: #CBDEFE  ;
		}
		</style>       
	</HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0"  cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Modificar  Comprobantes <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td height="400" align="left" valign="top">      
	<?
/**
* Control para la elecciï¿½n del periodo contable 
*/
if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($Com_Cod))
{
?>
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selecciï¿½n Periodo Contable</label>
	</LEGEND>
	<table width="251" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td width="53" class="Etiqueta1">Periodos:&nbsp; </td>
	    <td width="88">		
		<?php
		$periodo = current($row_rs_periodos); 
		?>
	      <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $periodo['Pec_Fei']; ?>">
	      <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $periodo['Pec_Fef']; ?>">	      
          <select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)">
	      <?Php 			
	if (count($row_rs_periodos) > 0)
	{
		foreach ($row_rs_periodos as $row)
		{
		?>
	        <option value="<?Php echo $row['Pec_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?Php echo $row['Periodo']; ?></option>
	        <?php		
		}//Fin del foreach ($row_rs_periodos as $row)
	}//Fin del if ($row_rs_periodos > 0)
	else
	{ ?>
	        <option value=""></option>
	        <?Php
	}//Fin del else if ($row_rs_periodos > 0)
	?>
	        </select></td>
	    <td width="110" align="center"><button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-ok icon-white"></i>
                    <span>Aceptar</span> </button>
	      <input name="hdd_save" type="hidden" id="hdd_save"></td>
	    </tr>
	  </table>
</FIELDSET>			 
</form>
<?Php
}//Fin del if (!isset($Pec_Cod))
else
{
		$pag1= $_SERVER['PHP_SELF']."?op=I&hdd_save&Pec_Cod=".$Pec_Cod;
		$pag2= $_SERVER['PHP_SELF']."?op=E&hdd_save&Pec_Cod=".$Pec_Cod;
		$pag3= $_SERVER['PHP_SELF']."?op=D&hdd_save&Pec_Cod=".$Pec_Cod;
		switch($op) {
			case "I": $activo = 1; break;
			case "E": $activo = 2; break; 
			case "D": $activo = 3; break; }								
		tabs(3,"Ingreso*Egreso*Diario", $pag1.'*'.$pag2.'*'.$pag3, $activo);
	?>
	<div id="ContTabul">
	<?Php	
	if (($op=="I" || $op=="E" || $op=="D"))
	{
	switch($op) {
	case "I": $etiqueta="Buscar Comprobante de ".$array_asien[0]." a modificar: "; break;
	case "E":	$etiqueta="Buscar Comprobante de ".$array_asien[1]." a modificar: "; break; 
	case "D":	$etiqueta="Buscar Comprobante de ".$array_asien[2]." a modificar: "; break; }
	?>
    <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2"><? echo $etiqueta; ?></label>
	</LEGEND>
	<table width="501" height="27" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td width="178" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d" checked onClick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)">
        Apellidos</td>
      <td width="225" class="LetraNegra"><input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)">
        No. de Comprobante </td>
      <td width="249" class="LetraNegra">
	  Mes:&nbsp;
      <?Php
	  /**
	  * Control para cargar el mes seleccionado
	  */
	  if (isset($cmb_mes))
	  {      		
		$m = explode("=",$cmb_mes);
		$mes = $m[1];
	  }
      ?>
	  <select name="cmb_mes" id="cmb_mes">
        <option value=""><< TODOS >></option>
        <?Php		
	  for ($i=1;$i<=12;$i++)
	  {
	  	?>
        <option <?php if ($i == $mes){ echo "selected"; } ?> value="<?Php echo "AND MONTH(Com_Fec)=$i"; ?>"><?php echo mes($i, 1) ?></option>
        <?Php
	  }
	  ?>
      </select></td>
    </tr>
  </table>
	<table width="570" height="37" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="402" height="28" class="BarraBusqueda"><div align="left">Busqueda:
		    <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="40" maxlength="50" onKeyUp="parametro_injection(this)">
		    <input name="op" type="hidden" id="op" value="<? echo $op; ?>" >
		    <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>"> 
		    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
		    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
   				</div>  </td>
		  <td width="168">&nbsp;<button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
		      <i class="icon-search icon-white"></i>
		      <span>Buscar</span>
		      </button>   </td>
		  </tr>
	  </table>
	</FIELDSET>
    </form>
<? }//Fin del if (($op==1 || $op==2 || $op==3)) 

if(isset($txt_busqueda))
{ ?>	
	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
	<table id="tbl_comp" width="100%" border="0" cellpadding="1" cellspacing="0" >
    <thead>
        <tr class="Cabecera1" height="27">
          <th width="4%">C&oacute;d. Int. </th>
          <th width="8%">Generaci&oacute;n</th>
          <th width="6%">No. Compr </th>
          <th width="9%">C&eacute;dula/R.U.C.</th>
          <th width="21%" align="left">Proveedor/Cliente</th>
          <th width="14%" align="left">Tipo Comprobante</th>
          <th width="17%" align="left">Concepto</th>
          <th width="8%">Fecha</th>
		  <th width="7%">Valor</th>
		  <th width="3%">&nbsp;</th>
		  <th width="3%">&nbsp;</th> 
        </tr>
     </thead>
     <tbody>
		<?
	if (count($row_rs_cabcomp) > 0) 
	{
		$i=0;
		foreach ($row_rs_cabcomp as $row) 
		{
		$i++;				
			 if($row['Com_Est']=='I')
	  		 { $rojo='#FF0000'; $anulada++; }else{$rojo='';}	
			 /** 
			 * Propiedad Codigo del comprobante 
			 */
			 $com_codigo = $row['Com_Cod'];		
	   ?>
	   <form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
		<tr class="Fondo">
		  <td align="center"><font color="<?php echo $rojo; ?>"><? echo $row['Com_Cod']; ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>"><?Php  if ($row['Com_Gen'] == 'M') echo "Manual"; else echo "Auto";
		  ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>">&nbsp;<? 
	  	list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
		  echo $row['Tia_Abr']."-".$mes.'-'.$row['Com_Num']; ?></font></td>
		  <td><font color="<?php echo $rojo; ?>"><? echo $row['Prs_Ced']; ?>&nbsp;</font></td>
		  <td><font color="<?php echo $rojo; ?>"><?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'], '#FFFF00', 1); ?></font></td>
		  <td><font color="<?php echo $rojo; ?>"><? echo $row['Tia_Des']; ?></font></td>
		  <td><font color="<?php echo $rojo; ?>"><? echo $row['Com_Con']; ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>">&nbsp;<? echo $row['Com_Fec']; ?></font></td>			
		  <td align="right"><font color="<?php echo $rojo; ?>">&nbsp;<? echo $row['Com_Val']; ?></font></td>
		  <td align="right"><button type="button" class="btn btn-info btn-mini" name="button1" id="button1" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<? echo $row['Com_Cod'];?>&Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>','ajax_modal')"><i class="icon-info-sign icon-white"></i></button></td>
		  <td align="center"><? 		 
		  /**
		  * Se permite modificar los comprobantes en caso de ser manuales 
		  */
		  if ($row['Com_Gen']=='M') 
		  { 		  
		  	 if ($row['Com_Est'] == 'A')
			  {
		  ?>
		    <input name="cod" id="cod" type="hidden"  value="<?php echo $row['Com_Cod']; ?>">
		    <input name="op" id="op" type="hidden"  value="<?php echo $op; ?>">
		    <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
		    <input name="volver_busqueda" id="volver_busqueda" type="hidden"  value="<?php echo $txt_busqueda; ?>">
		    <input name="volver_opciones" id="volver_opciones" type="hidden"  value="<?php echo $op_opciones; ?>">
		    <input name="volver_mes" id="volver_mes" type="hidden"  value="<?php echo $cmb_mes; ?>">               
		    <input name="hdd_save" id="hdd_save" type="hidden" value="">				
		    <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
		      <i class="icon-arrow-right icon-white"></i>
		      </button>		  			  		  
		    <?Php 
		  		}
			  else
			  {
					echo "&nbsp;";  
				}		  
		  } else { ?>
		    <img src="../../mascaras/model1/imagenes/32x32/encrypted.png" title="Comprobante bloqueado por generaciï¿½n automï¿½tica" width="22" height="22"> 
		    <?Php } ?>		  </td>					
		  </tr>
		</form>
        <?	  		
	  }//Fin del foreach
		}//FIn del if ($row_rs_cabcomp > 0) {	
		else
		{ ?>
			<tr>
			  <td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		<?php
		}//FIn del else if ($row_rs_cabcomp > 0) {	
	  ?>	
      </table>
	<?php echo barra_estado(count($row_rs_cabcomp)); ?>	  
	</FIELDSET>
   	<?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
<? }

if ($cod > 0 && !(isset($txt_busqueda)))
{
	if (count($row_rs_codcompr) > 0) 
	{
		/** 
		* Cargado de las cuentas a modificar 
		*/
		$row_rs_cuentas = $obBD_con1->getArrayConsulta(327, $row_rs_codcompr['Com_Cod'], $obBD_conexion);

		switch($op) 
		{
			case "I": $codigo=$Cli_Cod; $etiqueta=$array_asien[0]; break;
			case "E":	$codigo=$Prv_Cod; $etiqueta=$array_asien[1]; break;
			case "D":	$codigo=0; $etiqueta=$array_asien[2]; break; }
	?>
	<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2">
        <?Php	
	/**
	* Creacion del campo repost 
	*/
	$thisPost->startPost();	?>
	<input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">  	
    <input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>" />
<FIELDSET>
  <LEGEND>
	<label class="Titulos2">Datos del Comprobantes de <? echo $etiqueta; ?></label>
	</LEGEND>	
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Generales</label>
	<input name="Com_Cod" type="hidden" id="Com_Cod" value="<? echo $Com_Cod;?>">
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="Etiqueta1">Tipo Comprobante:</td>
    <td class="LetraNegra"><?Php
	/**
	* Consulta de los tipos de asientos 
	*/
	$row_rs_tipo_asien = $obBD_con1->getArrayConsulta(211, $op, $obBD_conexion);
	?>
      <select name="Tia_Cod" id="Tia_Cod">
        <option value="">Seleccione...</option>
        <?Php 
    	foreach ($row_rs_tipo_asien as $row) 
		{
			?>
        <option <?Php if ($row['Tia_Cod'] == $row_rs_codcompr['Tia_Cod']){ echo "selected"; } ?> value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?></option>
        <?php
		}
	?>
      </select></td>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha:</td>
    <td class="LetraNegra"><input name="Com_Fec" type="text" id="Com_Fec" size="12" maxlength="10"  value="<? echo $row_rs_codcompr['Com_Fec']; ?>" onBlur="validar_fecha2(this)" onKeyUp="mascara(this,'-',patron,true);" />
      <input name="Hdd_Com_Fec" type="hidden" id="Hdd_Com_Fec" value="<?Php echo $row_rs_codcompr['Com_Fec'];  ?>" /></td>
    </tr>
  <tr>
    <td class="Etiqueta1">No. Compr: </td>
    <td class="LetraNegra"><div id="div_codigo">&nbsp;<input name="Com_Num" type="text" readonly style="border:none" id="Com_Num" size="10" maxlength="10" value="<? echo $row_rs_codcompr['Com_Num']; ?>">
    </div></td>
    <td class="Etiqueta1">Valor:</td>
    <td><input name="Com_Val" type="text" id="Com_Val" size="12" maxlength="12" value="<? echo $row_rs_codcompr['Com_Val']; ?>" onKeyPress="return validar_decimal(event)" style="text-align:right" /></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><?php if($op=='I') echo 'Cliente'; else echo 'Proveedor';?>:</td>
    <td class="LetraNegra">&nbsp;<span id="CliCli"><? echo $row_rs_codcompr['Prs_Ape'].' '.$row_rs_codcompr['Prs_Nom']; ?></span>&nbsp;&nbsp;&nbsp;
        <button type="button" name="button1" id="button1" class="btn btn-mini btn-success fileinput-button" title="Cambiar Cliente/Proveedor" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_cliente=1&Com_Cod=<?php echo $row_rs_codcompr['Com_Cod']; ?>&op=<?php echo $op; ?>','ajax_modal')">
                        <i class="icon-edit icon-white"></i>                        
        </button> 
    </td>
    <td class="Etiqueta1">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1"> <span class="Asterisco">*</span> Concepto:</td>
    <td colspan="3"><textarea name="Com_Con" cols="71" id="Com_Con" style="text-transform:uppercase" onKeyUp="parametro_injection(this)"><? echo $row_rs_codcompr['Com_Con']; ?></textarea></td>
    </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td colspan="3" rowspan="2" valign="top"><textarea name="Com_Obs" cols="71" style="text-transform:uppercase" id="Com_Obs" onKeyUp="parametro_injection(this)"><? echo $row_rs_codcompr['Com_Obs']; ?></textarea></td>
    </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  </table>
</FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cuentas</label>
	</LEGEND>				
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
	<thead >
	<tr>
	  <th width="3%">&nbsp;</th>
		<th width="11%">C&oacute;digo</th>
		<th width="18%">Cuenta</th>
		<th width="41%">Glosa</th>
		<th width="12%">Debe</th>
		<th width="10%">Haber</th>
		<th width="5%">&nbsp;</th>
	</tr>
    </thead>
    <tbody id="c_contenido">
	<? if (count($row_rs_cuentas) > 0)
	{ 
		$t_debe = 0;
		$t_haber = 0;
		foreach ($row_rs_cuentas as $row) 
		{ 
			$fila++;
			if ($row['Asi_Deh']=='D'){
				$t_debe = $t_debe + $row['Asi_Val'];
			}elseif ($row['Asi_Deh']=='H'){
				$t_haber = $t_haber + $row['Asi_Val'];
			}
	?>
	<tr>
	  <td><input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? echo $row['Pld_Cod']; ?>">
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
      </td>
	  <td><input name="datos[<? echo $fila; ?>,2]" type="text" id="datos[<? echo $fila; ?>,2]" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="<? echo $row['Pld_Cdc']; ?>" size="7"></td>
		<td><input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" value="<? echo $row['Pld_Des']; ?>"></td>
		<td><input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="<? echo $row['Asi_Glo']; ?>"></td>
		<td align="right"><? if ($row['Asi_Deh']=='D') { ?><input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<? echo $row['Asi_Val']; ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()"><? } ?></td>
		<td align="right"><? if ($row['Asi_Deh']=='H') { ?><input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<? echo $row['Asi_Val']; ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()"><? } ?></td>
		<td align="center"><input id="quitar_fila2" type="button" name="quitar_fila2" value="X" onClick="quitar_fila_st(this); elimin_asi(<? echo $row['Asi_Cod']; ?>)">
		<input name="codasi<?php echo "[".$fila."]"; ?>" id="codasi<?php echo "[".$fila."]"; ?>" type="hidden" value="<?Php echo $row['Asi_Cod']; ?>">			  </td>
	</tr>
	<? } //Fin del foreach
	 } //Fin del if
	 ?>
	</tbody>
    <tfoot>
	<tr>
	  <td width="16">&nbsp;</td>
		<td width="55" class="LetraNegra">&nbsp;</td>
		<td width="134" class="LetraNegra">&nbsp;</td>
		<td width="152" class="Etiqueta1"><strong>TOTALES</strong></td>
		<td width="69" class="LetraNegra">
		  <div align="right">
		    <input name="t_debe" type="text" align="right" id="t_debe" size="7" style="text-align:right" maxlength="10" readonly value="<?Php echo  formato_numero($t_debe,2,1); ?>">
		    </div></td>
		<td width="69" class="LetraNegra">
		  <div align="right">
		    <input name="t_haber" type="text" align="right" id="t_haber" size="7" style="text-align:right" maxlength="10" readonly value="<?Php echo formato_numero($t_haber,2,1); ?>">
		    </div></td>
		<td width="13" class="LetraNegra">&nbsp;</td>
	</tr>
    </tfoot>
	</table>
	<!--<table width="207" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="103"><button type="button" class="btn btn-primary start" title="Agregar cuenta al Debe" onClick="nueva_fila('c_contenido','debe','<?Php //echo $_SERVER['PHP_SELF']; ?>?Pla_Cod=<?Php //echo $Pla_Cod; ?>&Pec_Cod=<?Php //echo $Pec_Cod; ?>', '', '', '')">
		  <i class="icon-list-alt icon-white"></i>
		  <span>Debe</span>
		  </button></td>
	    <td width="104"><button type="button" class="btn btn-primary start" title="Agregar cuenta al Haber" onClick="nueva_fila('c_contenido','haber','<?Php //echo $_SERVER['PHP_SELF']; ?>?Pla_Cod=<?Php //echo $Pla_Cod; ?>&Pec_Cod=<?Php //echo $Pec_Cod; ?>', '', '', '')">
           <i class="icon-list-alt icon-white"></i>
           <span>Haber</span>
    </button> </td>		
	  </tr>
	</table>-->
	<script language="javascript">
		//sumar_totales();
	</script>
	
		<input id="nfilas" name="nfilas" type="hidden" value="<? echo $fila; ?>">
		<input id="Com_Tip" name="Com_Tip" type="hidden" value="<? echo $tipo; ?>">
		<input id="Com_Cod" name="Com_Cod" type="hidden" value="<? echo $row_rs_codcompr['Com_Cod']; ?>">
		<input id="Asi_Cod" name="Asi_Cod" type="hidden" value="<? echo $row_rs_cuentas['Asi_Cod']; ?>">
		<input id="Com_Tipo" name="Com_Tipo" type="hidden" value="C">
		<input id="Codigo" name="Codigo" type="hidden" value="<? echo $codigo; ?>">
		<input id="op" name="op" type="hidden" value="<? echo $op; ?>">
		<? //if ($row_rs_codcompr['Prs_Ape'] =="VARIOS") {?>
		<!--<input name="Prv_Cod"type="hidden" value="<? //echo "3"; ?>">-->
		<? //}?>    
	<input name="oculto" type="hidden" id="oculto" value="">
    <?php
   echo barra_estado(count($row_rs_cuentas));
   ?>
   <div style="float:right;text-align:right;width: 200px;"><b>Diferencia:</b>
   	<input name="t_diff" type="text" align="right" id="t_diff" size="7"  style="text-align:right;" maxlength="10" readonly value="<?Php echo round($total_d-$total_h,2);?>">
    </div>
	</FIELDSET>	
	</FIELDSET>
	<br>
		<table width="312" border="0" cellpadding="0" cellspacing="0">
		  <tr>
		    <td width="101"><button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*op*hdd_volver', '<? echo $volver_busqueda.'*'.$volver_opciones.'*'.$volver_mes.'*'.$Pec_Cod.'*'.$op.'*1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
            </td>
			<td width="101">
			  <input id="bt_save" name="bt_save" type="hidden" value="Grabar">
              <input id="bt_save" name="bt_save" type="hidden" value="Grabar">
		      <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_inscomp(this.form)">
	          <i class="icon-book icon-white"></i>
	          <span>Guardar</span>
              </button>
		    </td>
			<td width="110"><button type="button" class="btn btn-success fileinput-button" title="Agregar cuenta" name="button1" id="button1" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','ajax_modal');">
           <i class="icon-list-alt icon-white"></i>
           <span>Agregar</span>
           </button>
           <input name="cantmodal" id="cantmodal" type="hidden" value="3"></td>
		  </tr>
		</table>
   </form>
	<br>

	<? } 
}////Fin del ELSE if (!isset($hdd_save) && !isset($txt_busqueda))
} ?></td>
  </tr>
</table>
</div>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	   <div id="ajax_modal">
        	 
       </div>  
</div>     

<!--<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar">
             
             </div>
       </div>
</div>-->
<script type="text/javascript" src="../VALIDACIONES/con_par_compr.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
<script>
    function changeCliente(Cod,Com,Cliente,Ruc,opc)
    {
            var op= confirm("Est\u00E1 seguro de realizar esta operaci\u00F3n?");

            if (op === true)
            {
                   //alert(asi+" "+che);
                   $.post( "<?Php echo $_SERVER['PHP_SELF']; ?>",{changeCliente:true,Cod_Cod:Cod,Com_Cod:Com,op:opc}, function( response ) {
                         if(response['success']===true){                           
                            $('#CliCli').html(Cliente);
                            closeModal();
                            alert("Transaccion Realizada con Ã‰xito!");                            
                         }else{alert(response['message']);}
                   },'json').fail(function(error) { alert("El Servidor ha fallado en responder!"); });
            }
    }
</script>
<script>
    function loadCuenta(fila,buscod){        
        $.get('<? echo $_SERVER['PHP_SELF'];?>',{loadCuenta:true,fila:fila,buscod:buscod,Pla_Cod:'<?php echo $Pla_Cod  ?>'},function(response){
            if(response['success']===true){
                document.getElementById('datos['+response['fila']+',1]').value = response['rows'][0]['Pld_Cod'];
                document.getElementById('datos['+response['fila']+',2]').value = response['rows'][0]['Pld_Cdc'];
                document.getElementById('datos['+response['fila']+',3]').value = response['rows'][0]['Pld_Des'];
            }else {Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila='+response['fila'],'ajax_modal');}
        },'json');
    }
</script>
</BODY></HTML>
<?php
@$obBD_conexion->cerrar();
?>