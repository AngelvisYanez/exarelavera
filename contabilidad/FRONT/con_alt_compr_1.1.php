<?
/*
* @abstract Permite registrar comprobantes de ingreso, egreso y diario
* @author Lewis Chimarro
* @version 1.0
* Fecha de creaciï¿½n  2009-12-11
* Fecha de actualizaciï¿½n  2012-04-25
* Fecha de actualizaciï¿½n  2015-04-30
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
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
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

/* cargado de cuentas via ajax */
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
/**
* Cargado de Informaciï¿½n a travï¿½s de AJAX 
*/
if (isset($codigo) && !isset($bt_save))
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
}//Fin del if (isset($codigo))
/**
* Buscador para agregar cuentas al comprobante
*/
if (isset($ajax_buscador))
{ ?>
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form2"> 	
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
        <td width="387" class="BarraBusqueda"><input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod1=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','busqueda')}
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
        <td width="387" class="BarraBusqueda"><input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('buscta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscod2=1&buscod='+document.getElementById('buscta').value+'&op_opciones='+document.getElementById('op_opciones').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','busqueda')}
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
* Ajax para cargar los meses para contabilizar ventas
*/
if (isset($caja))
{
	/**
	* Consulta los meses de las cajas de ventas
	*/
	$row_meses = $obBD_con1->getArrayConsulta(182, $Ann.'*'.$Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	?>
    <select name="Caj_Mes" id="Caj_Mes" onChange="ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?caja_mes&Ann=<?Php echo $Ann; ?>&Pec_Cod=<?Php echo $Pec_Cod; ?>&Mes='+this.value, 'div_caja_mes')">
    <option value="">Seleccione...</option>
    <?Php
	foreach ($row_meses as $row)
	{
	?>
    	<option value="<?Php echo $row['mes'] ?>"><?Php echo  mes($row['mes'],1) ?></option>
	<?Php
	}
	?>
    </select>
    <?Php
	exit();	
}
/**
* Dï¿½as de las ventas, para contabilizar
*/
if (isset($caja_mes))
{
	/** 
	* Consulta de la caja a generar 
	*/
	$row_rs_caja_aper = $obBD_con1->getArrayConsulta(181, $Ann.'*'.$Pec_Cod.'*'.$Ses_Emp_Cod.'*'.$Mes, $obBD_conexion); ?>
    
       <?Php 
	   if (count($row_rs_caja_aper)>0)
	   { ?>
	<form action="#" method="post" name="form5" id="form5" >
    <label class="LetraNegra">Marcar/Desmarcar Todos:&nbsp;</label><input type="checkbox" name="chkTodos" id="chkTodos" onClick="Marcar(this,this.form);"><br />
    <?php	
	$i=0;   
		foreach ($row_rs_caja_aper as $row)
		{
		?>
        <?php echo $row['Caj_Fec']; ?>
            <input type="checkbox" name="Caj_Cod[<?Php echo $i; ?>]" id="Caj_Cod[<?Php echo $i; ?>]" value="<?php echo $row['Caj_Cod'].'*'.$row['Caj_Fec']; ?>"  /><br />
        <?php
		$i++;
		} ?>
     </form>
     <?Php 
	   }
		?> 
<?Php
exit();
}//Fin del if (isset($caja))
/**
* Ajax para cargar los meses para contabilizar compras
*/
if (isset($ajax_compra))
{
	/**
	* Consulta los meses de las compras
	*/
	$row_meses = $obBD_con1->getArrayConsulta(185, $Pec_Cod, $obBD_conexion);
	?>
    <select name="Caj_Mes" id="Caj_Mes" onChange="ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?compra_mes&Pec_Cod=<?Php echo $Pec_Cod; ?>&Mes='+this.value, 'div_compra_mes')">
    <option value="">Seleccione...</option>
    <?Php
	foreach ($row_meses as $row)
	{
	?>
    	<option value="<?Php echo $row['mes'] ?>"><?Php echo  mes($row['mes'],1) ?></option>
	<?Php
	}
	?>
    </select>
    <?Php
	exit();	
}
/**
* Dï¿½as de las compras, para contabilizar
*/
if (isset($compra_mes))
{
	/** 
	* Consulta de los dï¿½as a generar 
	*/
	$row_rs_dias = $obBD_con1->getArrayConsulta(186, $Pec_Cod.'*'.$Mes, $obBD_conexion); ?>
       <?Php 
	   if (count($row_rs_dias)>0)
	   { ?>
	<form action="#" method="post" name="form5" id="form5" >
    <label class="LetraNegra">Marcar/Desmarcar Todos:&nbsp;</label><input type="checkbox" name="chkTodos" id="chkTodos" onClick="Marcar(this,this.form);"><br />
    <?php	
	$i=0;   
		foreach ($row_rs_dias as $row)
		{
		?>
        <?php echo $row['Cop_Fec'].' - '.$row['Cop_Num']; if($row['Tic_Sri']*1==0) echo " <b>(ND)</b>";?>
            <input type="checkbox" name="Cop_Cod[<?Php echo $i; ?>]" id="Cop_Cod[<?Php echo $i; ?>]" value="<?php echo $row['Cop_Cod'].'*'.$row['Cop_Fec']; ?>"  /><br />
        <?php
		$i++;
		} ?>
     </form>
     <?Php 
	   }
		?> 
<?Php
exit();
}//Fin del if (isset($caja))


/**
* Consulta para la eleccion del periodo contable
*/

if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($chk_diario) && !isset($chk_compra))
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
	* Grabado de los Comprobantes, ya sean estos de Ingreso / Egreso / Ajuste-Contabilidad 
	*/
	if (isset($bt_save) && !isset($hdd_volver))
	{	
		/**
		* Inicio de la transaccion
		*/
		$obBD_con1->inicio_transaccion($obBD_conexion->conexion);
		/** 
		* Mes del comprobante 
		*/
		$var_mes = explode('-', $Com_Fec);
		$Com_Num = $obBD_con1->codigoComprAuto($Tia_Cod, $Pec_Cod, $var_mes[1], $obBD_conexion);
		
		/** 
		* Inserciï¿½n del Comprobante 
		*/
		if ($op=="I") { $tabla="cliente"; $campo="Cli_Cod"; }
		if ($op=="E" || $op=="D") { $tabla="proveedore"; $campo="Prv_Cod"; }
		$obBD_con1->grabarv_registros(sentencias_con(324,$obBD_con1->parametros($Pec_Cod.'*'.$Codigo.'*'.$Com_Num.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod
											.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo)),$obBD_conexion->conexion);
		$ultimo = $obBD_con1->insercionid ($obBD_conexion->conexion);
	
		/** 
		* Control para el grabado del diario de caja
		*/
		if (isset($Caj_Cod))
		{
			
			$array_caja = explode('*', $Caj_Cod);
			foreach ($array_caja as $row)
			{
				$obBD_con1->grabarv_registros(sentencias_con(344,$obBD_con1->parametros($row.'*'.$ultimo)),$obBD_conexion->conexion);
			}
		}
		
		/** 
		* Control para el grabado de las relaciï¿½n con compras
		*/
		if (isset($Cop_Cod))
		{
			$array_caja = explode('*', $Cop_Cod);
			foreach ($array_caja as $row)
			{
				$obBD_con1->grabarv_registros(sentencias_con(201,$obBD_con1->parametros($row.'*'.$ultimo)),$obBD_conexion->conexion);
			}
		}

		/** 
		* Recorre la matriz de los datos de las cuentas seleccionadas 
		*/
		foreach ($datos as $puntero => $item)
		{
			$cant++;
			$param[]=$item;
			if ($cant==5)
				{
					$cant=0;
					if (substr($puntero,strlen($puntero)-1,1)==4)
					{
						/** 
						* Inserta los asientos del DEBE 
						*/
						$obBD_con1->grabarv_registros(sentencias_con(325,$obBD_con1->parametros($ultimo.'*'.'D'.'*'.$param[4].'*'.$param[2].'*'.$param[3].'*'.$param[0])),$obBD_conexion->conexion);
					}
					else
						/** 
						* Inserta los asientos del HABER 
						*/
						$obBD_con1->grabarv_registros(sentencias_con(325,$obBD_con1->parametros($ultimo.'*'.'H'.'*'.$param[4].'*'.$param[2].'*'.$param[3].'*'.$param[0])),$obBD_conexion->conexion);
						unset($param);
				}
		}
		/**
		* Finaliza la transacciï¿½n
		*/
		$obBD_con1->fin_transaccion($obBD_conexion->conexion);
	}//Fin del if (isset($bt_save))
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
			if (isset($chk_diario))
			{
				/**
				* Cuando se seleccionan varios dï¿½as, se toma como fecha el ï¿½ltimo dï¿½a del mes
				*/
				if (count($Caj_Cod) > 1)
				{
				    $arreglo_caja = explode("*",$Caj_Cod[0]); 
					list($ann, $mes, $dia) = preg_split('![/.-]!', $arreglo_caja[1]);
					$dia_ultimo = ultimoDia($mes,$ann);
					$fechaCompr = $ann."-".$mes."-".$dia_ultimo;
					for ($i=0; $i<=count($Caj_Cod);$i++)
					{
						if (isset($Caj_Cod[$i]))
						{
							unset($arreglo_caja);
							$arreglo_caja = explode("*",$Caj_Cod[$i]);
							$codigoCompr = $codigoCompr." caja_aper.Caj_Cod = ".$arreglo_caja[0]." OR";
							$codigoCaja = $codigoCaja.$arreglo_caja[0]."*";							
						}
					}
					$codigoCompr = " AND (".substr($codigoCompr, 0, -2).")";					
					$codigoCaja = substr($codigoCaja, 0, -1);
				}
				else
				{
					/**
					* Cuando se selecciona un dï¿½a
					*/
					$arreglo_caja = explode("*",current($Caj_Cod)); 
					$fechaCompr = $arreglo_caja[1];
					$codigoCompr = " AND caja_aper.Caj_Cod = ".$arreglo_caja[0];
					$codigoCaja = $arreglo_caja[0];							
				}
					
				/** 
				* Consulta el codigo del cliente reservado para la caja 
				*/
				$row_rs_caja_clien = $obBD_con1->getRowConsulta(180, $Ses_Emp_Cod, $obBD_conexion);			
				/** 
				* Nombre y Cï¿½digo del cliente 
				*/
				$Cli_Cod = $row_rs_caja_clien['Cli_Cod'];
				$Nombre = $row_rs_caja_clien['Prs_Ape'].' '.$row_rs_caja_clien['Prs_Nom'];											
			}
			else
			{
				if (isset($txt_busqueda))
				{
					if ($op_opciones=='a')
					{
						/**
						* Cargado de los resultados de la busqueda por descripcion de la cuenta
						*/
						$row_rs_buscli = $obBD_con1->getArrayConsulta(317, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
					}
					if ($op_opciones=='c')
					{
						/**
						* Cargado de los resultados de la busqueda por codigo de la cuenta
						*/
						$row_rs_buscli = $obBD_con1->getArrayConsulta(318, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
					}
				}//Fin del if (isset($txt_busqueda))
			}//Fin del else if (isset($chk_diario))
		break;
	
		case "E": //Inicio de la opcion 2
			if (isset($txt_busqueda))
			{
				if ($op_opciones=='a')
				{
					/**
					* Cargado de los resultados de la busqueda por descripcion de la cuenta
					*/
					$row_rs_buspro = $obBD_con1->getArrayConsulta(320, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
				}
				if ($op_opciones=='c')
				{
					/** 
					* Cargado de los resultados de la busqueda por codigo de la cuenta
					*/
					$row_rs_buspro = $obBD_con1->getArrayConsulta(321, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
				}
			}	
		break;
		
		case "D": //Inicio de la opcion 3
			if (isset($chk_compra))
			{
				/**
				* Cuando se seleccionan varios dï¿½as, se toma como fecha el ï¿½ltimo dï¿½a del mes
				*/
				if (count($Cop_Cod) > 1)
				{
				    $arreglo_caja = explode("*",$Cop_Cod[0]); 
					list($ann, $mes, $dia) = preg_split('![/.-]!', $arreglo_caja[1]);
					$dia_ultimo = ultimoDia($mes,$ann);
					$fechaCompr = $ann."-".$mes."-".$dia_ultimo;
					for ($i=0; $i<=count($Cop_Cod);$i++)
					{
						if (isset($Cop_Cod[$i]))
						{
							unset($arreglo_caja);
							$arreglo_caja = explode("*",$Cop_Cod[$i]);
							$codigoCompr = $codigoCompr." compras.Cop_Cod = ".$arreglo_caja[0]." OR";
							$codigoCaja = $codigoCaja.$arreglo_caja[0]."*";							
						}
					}
					$codigoCompr = " AND (".substr($codigoCompr, 0, -2).")";					
					$codigoCaja = substr($codigoCaja, 0, -1);
				}
				else
				{
					/**
					* Cuando se selecciona un dï¿½a
					*/
					$arreglo_caja = explode("*",current($Cop_Cod)); 
					$fechaCompr = $arreglo_caja[1];
					$codigoCompr = " AND compras.Cop_Cod = ".$arreglo_caja[0];
					$codigoCaja = $arreglo_caja[0];							
				}
					
				/** 
				* Consulta el codigo del cliente reservado para la caja 
				*/
				$row_rs_compas_prov = $obBD_con1->getRowConsulta(187, $Ses_Emp_Cod, $obBD_conexion);			
				/** 
				* Nombre y Cï¿½digo del cliente 
				*/
				$Prv_Cod = $row_rs_compas_prov['Prv_Cod'];
				$Nombre = $row_rs_compas_prov['Prs_Ape'].' '.$row_rs_compas_prov['Prs_Nom'];											
			}
			else
			{		
				if (isset($txt_busqueda))
				{
					if ($op_opciones=='a')
					{
						/**
						* Cargado de los resultados de la busqueda por descripcion de la cuenta
						*/
						$row_rs_buspro = $obBD_con1->getArrayConsulta(320, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
					}
					if ($op_opciones=='c')
					{
						/** 
						* Cargado de los resultados de la busqueda por codigo de la cuenta
						*/
						$row_rs_buspro = $obBD_con1->getArrayConsulta(321, trim($txt_busqueda).'*'.$Ses_Emp_Cod, $obBD_conexion);
					}
				}	
			}//Fin del chk_compra
		break;		
	}//Fin del switch ($op)

	/**
	* Divide la cadena del periodo contable 
	*/
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];

	/**
	* En esta consulta debe botar un solo registro ya en un aï¿½o contable normalmente se utiliza un plan de cuentas 
	*/
	$row_rs_cuenta_manual = $obBD_con1->getRowConsulta(113, $Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	$Pla_Cod = $row_rs_cuenta_manual['Pla_Cod'];	
	
	/** 
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".$row_rs_cuenta_manual['Ann'];			
}//Fin del else if ($Pec_Cod)
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/XML.js"></script>
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
		$( "#Com_Fec" ).datepicker({  
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});				
		}); 		
        </script>    
    </HEAD>
<BODY>
<div id="set1">
<?Php
if (isset($bt_save) && isset($ultimo) && !isset($hdd_volver))
{ 
	/**
	* Consulta del reporte para impresion 
	*/
	$pagina = $_SERVER['PHP_SELF'];
	$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod, $obBD_conexion);
	$hdd_comprobante = $reportes[1];
		
	if ($op=="I") 
	{ ?>
	<script language="javascript">
		windows('<?Php echo $hdd_comprobante; ?>?Com_Num=<? echo $Com_Num; ?>&codigo=<? echo $ultimo; ?>&tabla=<? echo $tabla; ?>&tipo=<? echo $Tia_Cod; ?>&campo=<? echo $campo; ?>&Pec_Cod=<?php echo $Pec_Cod;?>&op=<?Php echo $op; ?>','',800,600,'yes','yes', 'yes'); 
	</script>
    <? } else if ($op=="E") 
	{ ?>	   
	<script language="javascript">
		windows('<?Php echo $hdd_comprobante; ?>?Com_Num=<? echo $Com_Num; ?>&codigo=<? echo $ultimo; ?>&tabla=<? echo $tabla; ?>&tipo=<? echo $Tia_Cod; ?>&campo=<? echo $campo; ?>&Pec_Cod=<?php echo $Pec_Cod;?>&op=<?Php echo $op; ?>','',800,600,'yes','yes', 'yes'); 	
	</script>
	<? } else { ?>
	<script language="javascript">
	windows('<?Php echo $hdd_comprobante; ?>?Com_Num=<? echo $Com_Num; ?>&codigo=<? echo $ultimo; ?>&tabla=<? echo $tabla; ?>&tipo=<? echo $Tia_Cod; ?>&campo=<? echo $campo; ?>&Pec_Cod=<?php echo $Pec_Cod;?>&op=<?Php echo $op; ?>','',800,600,'yes','yes', 'yes'); 
    </script>
    <? 
		} 
	}//Fin del if ($op==1) 
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Registrar Comprobantes <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td height="400" align="left" valign="top">
<?Php
/**
* Control para la elecciï¿½n del periodo contable 
*/
if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($chk_diario) && !isset($chk_compra))
{
?>
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selecciï¿½n Periodo Contable</label>
	</LEGEND>
    <table width="252" border="0" cellspacing="0" cellpadding="0">
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
        </select>
         </td>
        <td width="111" align="center">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Pec_Cod', 0)">
                    <i class="icon-ok icon-white"></i>
                    <span>Aceptar</span>
        </button>                
        <input name="hdd_save" type="hidden" id="hdd_save"></td>
      </tr>
    </table>
</FIELDSET>	
</form>		 
<?Php
}//Fin del if (!isset($hdd_save) && !isset($txt_busqueda))
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
	<?php
	if (($op=="I" || $op=="E" || $op=="D") && (!isset($Cli_Cod) && !isset($Prv_Cod))) {
	switch($op) {
	case "I": $etiqueta="Buscar Clientes por: "; break;
	case "E":	$etiqueta="Buscar Proveedores por: "; break; 
	case "D":	$etiqueta="Buscar Proveedores por: "; break; }
	?>
	<br>
    <form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2"><? echo $etiqueta; ?></label>	
	</LEGEND>
	<table width="564" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="136"><input name="op_opciones" type="radio" value="a" checked onClick="setfocus(this.form.txt_busqueda)">
			<span class="LetraNegra">Apellido</span></td>
		  <td width="147"><input type="radio" name="op_opciones" value="c" onClick="setfocus(this.form.txt_busqueda)">
			<span class="LetraNegra">C&eacute;dula/RUC</span></td>
		  <td width="281" class="LetraNegra"><?php 
		  					/**
							* 1= Ingreso 
							*/
		  					switch ($op)
							{
								case "I":
		  					?>
		  					<input name="chk_diario" type="checkbox" id="chk_diario" value="checkbox" onClick=" 
												DisabEnab('txt_busqueda'); ShowHide('tbl_caja'); if (chk_diario.checked){ 
																	ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?caja&Ann=<?Php echo $row_rs_cuenta_manual['Ann']; //Antes row_rs_periodo ?>&Pec_Cod=<?Php echo $Pec_Cod; ?>', 'div_caja')}
												"> 
		  					Contabilizar Documentos de Ventas
							<?php
								break;
								case "D":
								?>
				  <input name="chk_compra" type="checkbox" id="chk_compra" value="checkbox" onClick=" 
												DisabEnab('txt_busqueda'); ShowHide('tbl_compra'); if (chk_compra.checked){ 
																	ajax_datos('<?php echo $_SERVER['PHP_SELF']?>?ajax_compra&Ann=<?Php echo $row_rs_cuenta_manual['Ann']; ?>&Pec_Cod=<?Php echo $Pec_Cod; ?>', 'div_compra')} "> 
		  					Contabilizar Documentos de Compras
                            <?php
								break;
							}//Fin del if ($op==1)
							?>
		  </td>
		</tr>
	  </table>
	  <table width="489" border="0" cellpadding="0" cellspacing="0" id="tbl_caja">
        <tr>
          <td width="48" class="LetraNegra">Mes:</td>
          <td width="131" class="LetraNegra"><div id="div_caja"></div></td>
          <td width="52" class="LetraNegra">Cajas:</td>
          <td width="258">
           
            <table width="150" border="0" cellspacing="0" cellpadding="0" class="fixedHeader01">
            <thead>
            </thead>
            <tbody>    
              <tr>
                <td>&nbsp;<div id="div_caja_mes"></div></td>
              </tr>
            </tbody>              
            </table>
          
          </td>
        </tr>
      </table>
	  <table width="489" border="0" cellpadding="0" cellspacing="0" id="tbl_compra">
	    <tr>
	      <td width="48" class="LetraNegra">Mes:</td>
	      <td width="131" class="LetraNegra"><div id="div_compra"></div></td>
	      <td width="52" class="LetraNegra">Documentos:</td>
	      <td width="258"><table width="150" border="0" cellspacing="0" cellpadding="0" class="fixedHeader01">
	        <thead>
	          </thead>
	        <tbody>
	          <tr>
	            <td>&nbsp;
	              <div id="div_compra_mes"></div></td>
	            </tr>
	          </tbody>
	        </table></td>
	      </tr>
	    </table>
<br />
	  	<script language="javascript">
		ShowHide('tbl_caja');
		ShowHide('tbl_compra');
		</script>
	  <table width="574" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="95" height="35" class="BarraBusqueda"><div align="right"><span class="Asterisco">*</span> Busqueda:</div></td>
		  <td width="479" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" onKeyUp="parametro_injection(this)">
		  
			<input name="op" type="hidden" value="<? echo $op; ?>" >
			<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>"> 
			<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fei']; //Antes row_rs_periodo ?>">
          	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fef']; //Antes row_rs_periodo ?>">	
             <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="
			if ((document.form1.chk_diario)){ 
				if (document.form1.chk_diario.checked){					
						submit() 
                 }else{ 
					validar_requeridos(this.form, 'txt_busqueda', 0) 
				 }
			}else{ 
	            if ((document.form1.chk_compra)){
	                if (document.form1.chk_compra.checked){					
						submit() 
                    }else{ 
						validar_requeridos(this.form, 'txt_busqueda', 0) 
                 	}
				}else{
                 	validar_requeridos(this.form, 'txt_busqueda', 0) 
                }   
            }
            ">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>       							
		  </td>
		</tr>
	  </table>	  
    </FIELDSET>
	</form>
	<? }
	/** 
	* Comprobantes de INGRESO
	*/	
	if (isset($txt_busqueda) && ($op=="I"))//or $op=="D"
	{
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
      <tr>
        <th width="6%" align="center"><strong>C&oacute;d. Int.</strong></th>
        <th width="14%" align="center"><strong>C&eacute;dula/R.U.C.</strong></th>
        <th width="76%" align="center"><strong>Cliente</strong></th>
        <th width="4%">&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      <?
	if (count($row_rs_buscli) > 0) 
	{
	  foreach ($row_rs_buscli as $row)
	  {
	  ?><form method="post" name="form2" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <tr>
        <td align="center"><? echo $row['Cli_Cod']; ?></td>
        <td><? echo $row['Prs_Ced']; ?></td>
        <td><? echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?></td>
        <td align="center">
        
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
		<input name="op" type="hidden" value="<? echo $op; ?>">
		<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
	    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fei']; //Antes row_rs_periodo?>">
        <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fef'];  //Antes row_rs_periodo ?>">		
    	<input name="hdd_save" id="hdd_save" type="hidden" value="">				
		<input name="Cli_Cod"type="hidden" value="<? echo $row['Cli_Cod']; ?>">
        <input name="volver_busqueda" id="volver_busqueda" type="hidden"  value="<?php echo $txt_busqueda; ?>">
              <input name="volver_opciones" id="volver_opciones" type="hidden"  value="<?php echo $op_opciones; ?>">
              <input name="volver_mes" id="volver_mes" type="hidden"  value="<?php echo $cmb_mes; ?>">               
		<input name="Nombre" type="hidden" value="<? echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?>">       
        </td>
      </tr> </form>
      <?	  
	  } //Fin del row_rs_buscli
	 } 
	 else 
	 { ?>
	  	<tr>
        <td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$periodo, 1); ?></td>
	  	<td>&nbsp;</td>
  	    </tr>
	 <? 
	 }?>
     </tbody>
    </table>
	<?Php echo barra_estado(count($row_rs_buscli)); ?>
	</FIELDSET>
<? }
	
	/** 
	* Comprobantes de EGRESO Y DIARIO
	*/		
	if (isset($txt_busqueda) && ($op=="E" || $op=="D"))
	{
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Resultados de la Busqueda</label>
	</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
     <thead>
      <tr>
        <th width="4%"><strong>C&oacute;d. Int. </strong></th>
        <th width="8%"><strong>C&eacute;dula/R.U.C.</strong></th>
        <th><strong>Proveedor</strong></th>
        <th width="4%">&nbsp;</th>
      </tr>
      </thead>
      <tbody>
      <?
	  if (count($row_rs_buspro) > 0) {
	  foreach($row_rs_buspro as $row) 
	  {
	  	echo "<form method='post' name='form2' action='".$_SERVER['PHP_SELF']."'>";
	  ?>
      <tr>
        <td align="center"><? echo $row['Prv_Cod']; ?></td>
        <td><? echo $row['Prs_Ced']; ?>&nbsp;</td>
        <td><? echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?>&nbsp;</td>
        <td align="center"><button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
        	<i class=" icon-arrow-right icon-white"></i>
        </button></td>
        <input name="op" type="hidden" value="<? echo $op; ?>">
		<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
	    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fei']; ?>">
        <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fef']; ?>">		
    	<input name="hdd_save" id="hdd_save" type="hidden" value="">				
		<input name="Prv_Cod"type="hidden" value="<? echo $row['Prv_Cod']; ?>">
		<input name="Nombre"type="hidden" value="<? echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?>">
      </tr>
      <?
	  echo "</form>";
	  } //Fin del foreach
	  } else { ?>
	  	<tr>
	  	  <td>&nbsp;</td>
	  	  <td>&nbsp;</td>
	  	  <td><?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($txt_busqueda)." ".$periodo, 1); ?></td>
	  	  <td>&nbsp;</td>
  	    </tr>
	  <? }?>
      </tbody>
    </table>
   	<?Php echo barra_estado(count($row_rs_buspro)); ?>
	</FIELDSET>
	<? }

if ((isset($Cli_Cod) && ($op=="I")) || (isset($Prv_Cod) && ($op=="E" || $op=="D")))
{
?>
	<script language="javascript">
	/* Evita el sumbit */
	document.onkeypress = stopRKey; 
	</script>
	<?php	
	/** 
	* En la opciï¿½n 3 colocar el cï¿½digo del cliente que se va a utilizar para los Comprobantes de Ajuste
	*/
	switch($op) {
	case "I": $codigo=$Cli_Cod; $etiqueta=$array_asien[0]; break;
	case "E": $codigo=$Prv_Cod; $etiqueta=$array_asien[1]; break;
	case "D": $codigo=$Prv_Cod; $etiqueta=$array_asien[2]; break; }
	?>
	<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form_1"> 
    <?Php	
	/**
	* Creacion del campo repost 
	*/
	$thisPost->startPost();	?>
	<input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fei']; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_cuenta_manual['Pec_Fef']; ?>"> 
    <input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>" />
<FIELDSET>
  <LEGEND>
	<label class="Titulos2">Datos del Comprobantes de <? echo $etiqueta; ?></label>
	</LEGEND>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Generales</label>
	</LEGEND>	
	<table width="100%" border="0" cellpadding="0" cellspacing="0">  
  <tr>
    <td class="Etiqueta1">Tipo Comprobante:</td>
    <td class="LetraNegra">
    <?Php
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
      <option value="<?php echo $row['Tia_Cod']; ?>"><?php echo $row['Tia_Des'] ?> </option>
      <?php
		}
	?>
    </select></td>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Fecha:</td>
    <td class="LetraNegra"><?php
		/**
		* Entra aqui cuando no se va a contabilizar compras o ventas
		*/
		if (!isset($chk_diario) and !isset($chk_compra))
		{ ?>
      <input name="Com_Fec" type="text" id="Com_Fec" onKeyUp="mascara(this,'-',patron,true);" size="10" maxlength="10" 
			value="<? if (isset($chk_diario)){				
			echo $fechaCompr; } else { echo $hoy; } ?>" <? if (isset($chk_diario)){ echo "readonly='true'"; } ?> onBlur="validar_fecha2(this)" />
      <?Php 
	 
		}//FIn del if (isset($chk_diario))
		else
		{ 
			echo $fechaCompr; 
			if ($op=="I"){
			?>
		      <input name="Caj_Cod" id="Caj_Cod" type="hidden" value="<?php echo $codigoCaja; ?>" />
           <?Php
			}elseif($op=="D"){
		   ?>   
      <input name="Cop_Cod" id="Cop_Cod" type="hidden" value="<?php echo $codigoCaja; ?>" />
     <?Php } ?>
      <input name="Com_Fec" id="Com_Fec" type="text" value="<?php echo $fechaCompr; ?>" style="display:none" />
      <?php
	  switch ($op){
		 case "I":
			/** 
			* Consulta de los totales de las ventas por rubros y por carreras 
			*/
			$row_rs_cuentas = $obBD_con1->getArrayConsulta(341, $codigoCompr, $obBD_conexion);			
			/** 
			* Consulta de los valores del DEBE - BANCO 
			*/
			$row_rs_cuentas_d = $obBD_con1->getArrayConsulta(343, $codigoCompr, $obBD_conexion);
			/** 
			* Consulta de los valores del DEBE - SIN BANCO 
			*/
			$row_rs_cuentas_d_sb = $obBD_con1->getArrayConsulta(345, $codigoCompr, $obBD_conexion);
			/** 
			* Consulta de los valores del HABER - SIN NGE_COD 
			*/
			$row_rs_cuentas_d_snge = $obBD_con1->getArrayConsulta(346, $codigoCompr, $obBD_conexion);
			/** 
			* Consulta la RENTA en ventas
			*/
//			  $row_renta_imp = $obBD_con1->getArrayConsulta(183, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
//                        $row_renta_iva = $obBD_con1->getArrayConsulta(358, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
//                        $row_renta =array_merge($row_renta_imp,$row_renta_iva);
                        
                        /* CAMBIO PARA ARREGLAR LOS CENTAVOS DESCUADRADOS EN CONTABILIZACION POR CAJA */
			$row_renta_imp = $obBD_con1->getArrayConsulta(183, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
                        $row_renta_iva = $obBD_con1->getArrayConsulta(358, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
                        
                        $row_renta_aux=array();
                        $row_renta_data =array_merge($row_renta_imp,$row_renta_iva);
                        foreach ($row_renta_data as $rwrent){
                            $ban=true;
                            $rwrent['Renta']=round($rwrent['Renta']*1,2);
                            for($i=0;$i<COUNT($row_renta_aux);$i++){
                                if($row_renta_aux[$i]['Ren_Por']==$rwrent['Ren_Por']&&$row_renta_aux[$i]['Ren_Sri']==$rwrent['Ren_Sri']){
                                    $ban=false;
                                    $row_renta_aux[$i]['Renta']=$row_renta_aux[$i]['Renta']+$rwrent['Renta'];
                                    break;
                                }
                            }
                            if($ban)
                                array_push ($row_renta_aux, $rwrent);
                        }                        
                        $row_renta=$row_renta_aux;
			/** 
			* Consulta los BANCOS en ventas
			*/
			$row_banco = $obBD_con1->getArrayConsulta(202, $Pla_Cod, $obBD_conexion);
			
		 break;
		 case "D":
			/** 
			* Consulta de los valores del DEBE - 
			*/
			$row_rs_cuentas_d = $obBD_con1->getArrayConsulta(188, $codigoCompr, $obBD_conexion);
			/** 
			* Consulta la RENTA en compras
			*/
			//$row_renta = $obBD_con1->getArrayConsulta(200, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
                     
                        /* CAMBIO PARA ARREGLAR LOS CENTAVOS DESCUADRADOS EN CONTABILIZACION POR CAJA */
			$row_renta_data = $obBD_con1->getArrayConsulta(200, $codigoCompr.'*'.$Pla_Cod, $obBD_conexion);
                        
                        $row_renta_aux=array();
                       
                        foreach ($row_renta_data as $rwrent){
                            $ban=true;
                            $rwrent['Renta']=round($rwrent['Renta']*1,2);
                            for($i=0;$i<COUNT($row_renta_aux);$i++){
                                if($row_renta_aux[$i]['Ren_Por']==$rwrent['Ren_Por']&&$row_renta_aux[$i]['Ren_Sri']==$rwrent['Ren_Sri']){
                                    $ban=false;
                                    $row_renta_aux[$i]['Renta']=$row_renta_aux[$i]['Renta']+$rwrent['Renta'];
                                    break;
                                }
                            }
                            if($ban)
                                array_push ($row_renta_aux, $rwrent);
                        }                        
                        $row_renta=$row_renta_aux;
                        
                        
		 
		 break;
	  }
		
	}//Fin del if (!isset($chk_diario))
	?></td>
    </tr>
  <tr>
    <td width="106" class="Etiqueta1">Proveedor/Cliente:</td>
    <td width="509" class="LetraNegra">&nbsp;<? echo $Nombre; ?></td>
    <td class="Etiqueta1">Valor:</td>
    <td><input name="Com_Val" type="text" id="Com_Val" size="10" maxlength="12" onKeyPress="return validar_decimal(event)" style="text-align:right" /></td>
    </tr>
  <tr>
    <td class="Etiqueta1"><span class="Asterisco">*</span> Concepto:</td>
    <td class="LetraNegra">&nbsp;
      <textarea name="Com_Con" cols="73" style="text-transform:uppercase" id="Com_Con" onKeyPress="return  validar_injections(event)"></textarea></td>
    <td class="Etiqueta1">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td colspan="3"><textarea name="Com_Obs" cols="73" style="text-transform:uppercase" id="Com_Obs" onKeyPress="return  validar_injections(event)"></textarea></td>
  </tr>
  </table>
	</FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cuentas</label>
	</LEGEND>		
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
	<thead>
	<tr>
	  <th width="1%">&nbsp;</th>
		<th width="11%">C&oacute;digo</th>
		<th width="18%">Cuenta</th>
		<th width="41%">Glosa</th>
		<th width="12%">Debe</th>
		<th width="12%">Haber</th>
		<th width="5%">&nbsp;</th>
	    </tr>
    </thead>
    <tbody id="c_contenido">
	<? 
	$fila=0;
	$total_d = 0;
	$total_h = 0;		
    switch ($op){
	case "I":
	/** 
	* Cargado para el DEBE 
	* Entra solo en el caso de encontrar valores en una caja seleccionada 
	*/
	if (count($row_rs_cuentas_d) > 0)
	{ 
	foreach ($row_rs_cuentas_d as $row)
	{ 
		$fila++;
		/** 
		* Se debe tomar el monto total de la factura 
		*/
		$total_d = $total_d + $row['Importe'];
	?>
	<tr>
	  <td width="20">
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]"  type="hidden" value="<? echo $row['Pld_Cod']; ?>">
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" id="datos[<? echo $fila; ?>,2]"  value="<? echo $row['Pld_Cdc']; ?>"  onkeypress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" size="7">
      </td>
	  <td width="224" class="LetraNegra">
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="30" maxlength="100" value="<?Php echo $row['Pld_Des']; ?>" readonly>
      </td>
	  <td width="281">
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="35" maxlength="25" value="<?Php echo $row['Vet_Che'].' - '."Factura No: ".$row['Vet_Num']; ?>">
      </td>
	  <td width="59" align="right">
      <input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<? echo round($row['Importe'],2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="Cod. Int.: <?php echo $row['Vet_Cod']; ?>">
      </td>
		<td width="63">&nbsp;</td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">
        </td>
	   </tr>
	<? } //Fin del foreach foreach ($row_rs_cuentas_d as $row)
	}//Fin del if (count($row_rs_cuentas_d) > 0)
	
	/** 
	* Cargado para el DEBE SIN BANCOS
	*/
	if (count($row_rs_cuentas_d_sb) > 0)
	{ 
	$total_valor = 0;
	foreach ($row_rs_cuentas_d_sb as $row)
	{ 
		$fila++;
		/** 
		* Consulta la RENTA de cada venta
		*/
		$row_renta_venta = $obBD_con1->getRowConsulta(184, $row['Vet_Cod'], $obBD_conexion);
                $row_renta_iva_venta = $obBD_con1->getRowConsulta(357, $row['Vet_Cod'], $obBD_conexion);	
		/** 
		* Se debe tomar el monto total de la factura 
		*/
		$total_d = $total_d + ($row['Importe']-$row_renta_venta['Renta']*1-$row_renta_iva_venta['Renta']*1);
                
		/**
		* Totaliza el valor de banco o clientes varios, para poner en la caja de texto superior
		*/
		$total_valor = $total_valor + ($row['Importe']-$row_renta_venta['Renta']*1-$row_renta_iva_venta['Renta']*1);
	?>
	<tr>
	  <td width="20">     
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<?php if(count($row_banco)==1) echo $row_banco[0]['Pld_Cod']; ?>">
	 <?Php
      if (count($row_banco)==0)
	  {
	  ?>   
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
    <?Php
	  }//Fin del if (count($row_banco)==0)
	?>    
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" id="datos[<? echo $fila; ?>,2]"  value="<?php if(count($row_banco)==1) echo $row_banco[0]['Pld_Cdc']; ?>" size="7" <?Php if (count($row_banco)==0) { ?> onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" <?php }else{ echo 'readonly'; }?>>
      </td>
	  <td width="224">
      <?Php
	  /**
	  * Carga lo bancos asignados a las ventas
	  */
	  if (count($row_banco)>1)
	  { ?>
<select name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" onChange="
	  var indice = this.selectedIndex;
	  document.getElementById('datos[<? echo $fila; ?>,1]').value = this.value; var cuenta=this.options[indice].text.split('-');
	  document.getElementById('datos[<? echo $fila; ?>,2]').value = cuenta[0] ">
          <option value=""></option>
          <?Php
		foreach ($row_banco as $row3)
		{
		?>
          <option value="<?Php echo $row3['Pld_Cod']; ?>"><?Php echo $row3['Pld_Cdc'].' - '.$row3['Pld_Des']; ?></option>
          <?php
		}
		?>
        </select>		  
	 <?Php 
	 }//Fin del if (count($row_banco)>0)
	 else
	 {
	  ?>
      	<input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="30" maxlength="100" value="<?php if(count($row_banco)==1) echo $row_banco[0]['Pld_Des']; ?>">
      <?Php
	 }//Fin del else if (count($row_banco)>0)
	  ?>
      </td>
	  <td width="281">
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="35" maxlength="25" title="Factura No: <?Php echo $row['Vet_Num']." - ".$row['Caj_Fec']; ?>" value="...Factura No: <?Php echo $row['Vet_Num']." - ".$row['Caj_Fec']; ?>"></td>
	  <td width="59" align="right">
      <input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<? echo round($row['Importe']-$row_renta_venta['Renta']*1-$row_renta_iva_venta['Renta']*1,2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="Cï¿½d. Int.: <?php echo $row['Vet_Cod']; ?>">
      </td>
		<td width="63">&nbsp;</td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">			  
        </td>
	    </tr>
	<? } //Fin del foreach
	} //Fin del if (count($row_rs_cuentas_d_sb) > 0)

	/** 
	* Cargado para el DEBE 
	* Entra solo en el caso de encontrar valores en RENTA 
	*/
	if (count($row_renta) > 0)
	{ 
	foreach ($row_renta as $row)//ventas
	{ 
            $aux=$obBD_con1->getRowConsulta(359, $row['Ren_Cod'].'*'.$Pla_Cod.'*V', $obBD_conexion);
            if (count($aux) > 0)
                $row=  array_merge($row,$aux);
		$fila++;
		if ($row['Renta'] >0)
		{
		/** 
		* Se debe tomar el monto total de la factura 
		*/
		$total_d = $total_d + round($row['Renta'],2);
                //var_dump($row);
	?>
           
	<tr>
	  <td width="20">
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? echo $row['Pld_Cod']; ?>">
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" id="datos[<? echo $fila; ?>,2]" size="7" value="<? echo $row['Pld_Cdc']; ?>" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}">
      </td>
	  <td width="224" class="LetraNegra">
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="30" maxlength="100" value="<? echo $row['Pld_Des']; ?>" readonly>
      </td>
	  <td width="281">
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="35" maxlength="25" value="<?Php if($row['Ren_Ret']=='I') echo "Ret. IVA "; else echo "Renta "; echo $row['Ren_Por']."% - ".$row['Ren_Sri'];  ?>">
      </td>
	  <td width="59" align="right">
      <input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<? echo round($row['Renta'],2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()">
      </td>
		<td width="63">&nbsp;</td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">
        </td>
	   </tr>
	<? } //Fin del foreach foreach ($row_rs_cuentas_d as $row)
	}//Fin del if ($row['Renta'] >0)
	}//Fin del if (count($row_rs_cuentas_d) > 0)

	
	/** 
	* Cargado para el HABER
	*/
	/** 
	* ALmacena el total del iva cobrado 
	*/
	$total_iva_cob = 0;

	if (count($row_rs_cuentas) > 0)
	{ 
		foreach ($row_rs_cuentas as $row)
		{
			$fila++;
			/** 
			* Se debe tomar el importe de las facturas 
			*/
			$total_h = $total_h + $row['Importe'];
			$Pro_Cod = $row['Pro_Cod'];
			$Car_Int = $row['Car_Int'];
			$Mod_Cod = $row['Mod_Cod'];
			
			/** 
			* Consulta las cuentas en relacion a los rubros 
			*/
			$row_rs_codigos = $obBD_con1->getArrayConsulta(342, $Pro_Cod.'*'.$Car_Int.'*'.$Mod_Cod, $obBD_conexion); 
			$array_codigo2 = current($row_rs_codigos);
			?>
	<tr>
	  <td>
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? echo $array_codigo2['Pld_Cod']; ?>">
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" <?Php if (count($row_rs_codigos) > 1) { ?> readonly 
	  <?php } ?> id="datos[<? echo $fila; ?>,2]" onKeyUp="cargar_cuenta('<?php echo $_SERVER['PHP_SELF']; ?>?Pla_Cod='+document.getElementById('Pla_Cod').value+'&Pec_Cod=<?Php echo $Pec_Cod; ?>&codigo=',this,this.parentNode.parentNode.childNodes(2).firstChild,this.parentNode.parentNode.childNodes(0).firstChild)" value="<? if (count($row_rs_codigos) == 1) { echo $array_codigo2['Pld_Cdc']; } ?>" size="7">
	  </td>
	  <td width="224"><?Php
	  if (count($row_rs_codigos) > 1)
	  {
	  ?>
        <select name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" onChange="
	  var indice = this.selectedIndex;
	  document.getElementById('datos[<? echo $fila; ?>,1]').value = this.value; var cuenta=this.options[indice].text.split('-');
	  document.getElementById('datos[<? echo $fila; ?>,2]').value = cuenta[0] ">
          <option value=""></option>
          <?Php
		foreach ($row_rs_codigos as $row2)
		{
		?>
          <option value="<?Php echo $row2['Pld_Cod']; ?>"><?Php echo $row2['Pld_Cdc'].' - '.$row2['Pld_Des']; ?></option>
          <?php
		}
		?>
        </select>
        <?Php
	  }
	  else
	  { ?>
        <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" value="<?Php echo $array_codigo2['Pld_Des']; ?>" readonly>
        <?Php
	  }
	  ?></td>
		<td width="281">
        <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="">
        </td>
		<td width="59">&nbsp;</td>
		<td width="63" align="right">
        <input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<?Php echo round($row['Importe'],2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="<?Php echo $row['Mod_Des'].' - '.$row['Car_Nom'].': '.$row['Ite_Lar']; ?>"><? //} ?>
        </td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">			  
        </td>
	    </tr>
	<? 
		/** 
		* Acumula el total del iva 
		*/
		$total_iva_cob = $total_iva_cob + $row['Iva'];
		} //Fin del foreach row_rs_cuentas
	}//FIn del if ($row_rs_cuentas > 0) 
	/** 
	* Cargado para el HABER SIN NGE_COD
	*/
	if (count($row_rs_cuentas_d_snge) > 0)
	{ 
		foreach ($row_rs_cuentas_d_snge as $row)
		{ 
			$fila++;
			$total_h = $total_h + $row['Importe'];
			$Pro_Cod = $row['Pro_Cod'];
			/** 
			* Consulta las cuentas en relacion a los rubros - SOLO RUBROS ventas
			*/
			$row_rs_codigos = $obBD_con1->getArrayConsulta(349, $Pro_Cod.'*'.$Pla_Cod, $obBD_conexion);	
	?>
	<tr>
	  <td width="20">
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="">
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" <?Php if (count($row_rs_codigos) > 0) { ?> readonly 
	  <?php } ?> id="datos[<? echo $fila; ?>,2]" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="" size="7">
      </td>
	  <td width="224"><?Php
	  if (count($row_rs_codigos) > 0)
	  {
	  ?>
	    <select name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" onChange="
	  var indice = this.selectedIndex;
	  document.getElementById('datos[<? echo $fila; ?>,1]').value = this.value; var cuenta=this.options[indice].text.split('-');
	  document.getElementById('datos[<? echo $fila; ?>,2]').value = cuenta[0] ">	  	  
	  	<option value=""></option>
		<?Php
		/** 
		* Inicializacion de la variable que se ran utilizadas en la parte inferior 
		*/
		$array_codigo = current($row_rs_codigos);
		$Pld_Cod = $array_codigo['Pld_Cod'];
		$Pld_Cdc = $array_codigo['Pld_Cdc'];
		foreach ($row_rs_codigos as $row2)
		{
		?>
			<option value="<?Php echo $row2['Pld_Cod']; ?>"><?Php echo $row2['Pld_Cdc'].' - '.$row2['Pld_Des']; ?></option>
		<?php
		}
		?>
	  </select>
	    <?Php 
		  /* 
		  * Control para seleccionar el unico registro encontrado 
		  */
		  if (count($row_rs_codigos) == 1)
		  { 
		  ?>
		  <script language="javascript">
			/* Asigna los valores cuando se trata de un solo registro */
		  document.getElementById('datos[<? echo $fila; ?>,3]').selectedIndex = 1;
	 	  document.getElementById('datos[<? echo $fila; ?>,1]').value = '<?Php echo $Pld_Cod; ?>'; 
		  document.getElementById('datos[<? echo $fila; ?>,2]').value = '<?Php echo $Pld_Cdc; ?>';					
		  </script> 
	  <?Php
	  	  }//Fin del if ($total_rs_codigos == 1)
	  }//Fin del if ($total_rs_codigos > 0)
	  else //Entra en este caso contrario en caso de no encontrar registro relacionado
	  { ?>
		<input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" 
		maxlength="100" value="" readonly>	  	  
	  <?Php
	  }
	  ?>	  </td>
		<td width="281">
        <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="">
        </td>
		<td width="59">&nbsp;</td>
		<td width="63" align="right">
		  <input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<?Php echo round($row['Importe'],2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="Factura No <?php echo $row['Vet_Cod'].': '.$row['Ite_Lar']; ?>">
		</td>
		<td width="33" align="center"><input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">			  
        </td>
	    </tr>
	<?  /** 
		* Acumula el total del iva 
		*/
		$total_iva_cob = $total_iva_cob + $row['Iva'];
	    } //FIn del foreach ($row_rs_cuentas_d_snge as $row)
	}//FIn del if ($total_rs_cuentas_d_snge > 0) 
	/** 
	* Cargado automatico del IVA 
	*/
	$fila++;	

	if ($total_iva_cob > 0)
	{
		/** 
		* Consulta el codigo del plan de cuenta del iva cobrado 
		*/
		$row_rs_iva_cobrado = $obBD_con1->getRowConsulta(352, $Pla_Cod, $obBD_conexion);
		/** 
		* Suma al total del haber el iva 
		*/
		$total_h = $total_h + $total_iva_cob;
	?>	
		<tr>
	  <td>
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? echo $row_rs_iva_cobrado['Pld_Cod']; ?>">
      </td>
	  <td>
      <input name="datos[<? echo $fila; ?>,2]" type="text" <?Php if (count($row_rs_iva_cobrado) > 0){ ?> readonly <?Php } ?> id="datos[<? echo $fila; ?>,2]"  onkeypress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="<? echo $row_rs_iva_cobrado['Pld_Cdc']; ?>" size="7">
      </td>
	  <td>
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" <?Php if (count($row_rs_iva_cobrado) == 0){ echo "style='color:#FF0000'"; } ?>   value="<?Php if (count($row_rs_iva_cobrado) > 0){ echo $row_rs_iva_cobrado['Pld_Des']; }else { echo "ï¿½No existe una cuenta contable configurada para el Iva Cobrado!"; }; ?>" readonly>
      </td>
	  <td>
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="">
      </td>
	  <td>&nbsp;</td>
	  <td align="right">
      <input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<?Php echo round($total_iva_cob,2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="<?Php ?>">
      </td>
	  <td>&nbsp;</td>
	  </tr>
	<?Php
	}//Fin del if ($total_iva_cob > 0)
	break; //Fin de case "I":
	case "D":

	/** 
	* Cargado para el DEBE DE COMPRAS
	*/
	/** 
	* ALmacena el total del iva cobrado 
	*/
	$total_iva_cob = 0;	
	if (count($row_rs_cuentas_d) > 0)
	{ 
	foreach ($row_rs_cuentas_d as $row)
	{ 
		$fila++;
		/** 
		* Consulta la RENTA de cada venta
		*/
		//$row_renta_venta = $obBD_con1->getRowConsulta(184, $row['Vet_Cod'], $obBD_conexion);		
		/** 
		* Se debe tomar el monto total de la factura 
		*/
		$total_d = $total_d + ($row['Importe']/*-$row_renta_venta['Renta']*/);	

	  $Pro_Cod=$row['Pro_Cod'];
	  /** 
	  * Consulta las cuentas en relacion a los rubros - SOLO RUBROS compras
	  */
	  $row_rs_codigos = $obBD_con1->getArrayConsulta(347, $Pro_Cod.'*'.$Pla_Cod, $obBD_conexion);			
	  $array_codigo2 = current($row_rs_codigos);			
	?>
	<tr>
	  <td width="20">
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<?Php echo $array_codigo2['Pld_Cod']; ?>">
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
      </td>
	  <td width="54">
     <input name="datos[<? echo $fila; ?>,2]" type="text" <?Php if (count($row_rs_codigos) > 1) { ?> readonly 
	  <?php } ?> id="datos[<? echo $fila; ?>,2]"  value="<? if (count($row_rs_codigos) == 1) { echo $array_codigo2['Pld_Cdc']; } ?>" size="7" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}">
      </td>
	  <td width="224">
      <?Php	
	  if (count($row_rs_codigos) > 1)
	  {
	  ?>
        <select name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" onChange="
	  var indice = this.selectedIndex;
	  document.getElementById('datos[<? echo $fila; ?>,1]').value = this.value; var cuenta=this.options[indice].text.split('-');
	  document.getElementById('datos[<? echo $fila; ?>,2]').value = cuenta[0] ">
          <option value=""></option>
          <?Php
		foreach ($row_rs_codigos as $row2)
		{
		?>
          <option value="<?Php echo $row2['Pld_Cod']; ?>"><?Php echo $row2['Pld_Cdc'].' - '.$row2['Pld_Des']; ?></option>
          <?php
		}
		?>
        </select>
        <?Php
	  }
	  else
	  { ?>
        <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" value="<?Php echo $array_codigo2['Pld_Des']; ?>" readonly>
        <?Php
	  }
	  ?>
      </td>
	  <td width="281">
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="35" maxlength="25" title="Factura No: <?Php echo $row['Cop_Num']; ?>" value="...Documento No: <?Php echo $row['Cop_Num']; ?>">
      </td>
	  <td width="59" align="right">
      <input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<? echo round($row['Importe']/*-$row_renta_venta['Renta']*/,2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="Cï¿½d. Int.: <?php echo $row['Cop_Cod']; ?>">
      </td>
		<td width="63">&nbsp;</td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">			  
        </td>
	    </tr>
	<? 
		$total_iva_cob = $total_iva_cob + $row['Iva'];
	} //Fin del foreach
	} //Fin del if (count($row_rs_cuentas_d_sb) > 0)

	if ($total_iva_cob > 0)
	{
		$fila++;
		/** 
		* Consulta el codigo del plan de cuenta del iva cobrado 
		*/
		$row_rs_iva_cobrado = $obBD_con1->getArrayConsulta(189, $Pla_Cod, $obBD_conexion);
	?>	
		<tr>
	  <td>
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? if (count($row_rs_iva_cobrado) > 0){echo $row_rs_iva_cobrado[0]['Pld_Cod'];} ?>">
      </td>
	  <td>
      <input name="datos[<? echo $fila; ?>,2]" type="text" <?Php if (count($row_rs_iva_cobrado) > 0){ ?> readonly <?Php } ?> id="datos[<? echo $fila; ?>,2]" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="<? echo $row_rs_iva_cobrado[0]['Pld_Cdc']; ?>" size="7">
      </td>
	  <td>
              <?php if (count($row_rs_iva_cobrado) <= 1){  /*var_dump($row_rs_iva_cobrado);*/ ?>
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" <?Php if (count($row_rs_iva_cobrado) == 0){ echo "style='color:#FF0000'"; } ?>   value="<?Php if (count($row_rs_iva_cobrado) > 0){ echo $row_rs_iva_cobrado[0]['Pld_Des']; }else { echo "ï¿½No existe una cuenta contable configurada para el Iva Cobrado!"; }; ?>" readonly>
              <?php }else{ ?>
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" <?Php if (count($row_rs_iva_cobrado) == 0){ echo "style='color:#FF0000'"; } ?>   value="<?Php if (count($row_rs_iva_cobrado) > 0){ echo $row_rs_iva_cobrado[0]['Pld_Des']; } ?>" readonly style="display:none">
      <select onChange="var tempAux=this.value.split('~');document.getElementById('datos[<? echo $fila; ?>,1]').value=tempAux[0];document.getElementById('datos[<? echo $fila; ?>,2]').value=tempAux[1];document.getElementById('datos[<? echo $fila; ?>,3]').value=tempAux[2];" >
            <?php foreach ($row_rs_iva_cobrado AS $rowI){ ?>
                <option value="<?php echo $rowI['Pld_Cod'].'~'.$rowI['Pld_Cdc'].'~'.$rowI['Pld_Des']; ?>"><?php echo $rowI['Pld_Des']; ?></option>
            <?php } ?>
        </select>    
              <?php } ?>
      </td>
	  <td>
      <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="">
      </td>
	  <td align="right"><input name="datos[<? echo $fila; ?>,4]" id="datos[<? echo $fila; ?>,4]" type="text" size="7" maxlength="10" value="<?Php echo round($total_iva_cob,2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="<?Php ?>" /></td>
	  <td align="right">&nbsp;</td>
	  <td>&nbsp;</td>
	  </tr>
	<?Php
		/** 
		* Suma al total del haber el iva 
		*/
		$total_d = $total_d + $total_iva_cob;

	}//Fin del if ($total_iva_cob > 0)


// Aqui va el iva


	/** 
	* Cargado para el HABER 
	* Entra solo en el caso de encontrar valores en RENTA 
	*/
	if (count($row_renta) > 0)
	{ 
	$total_renta = 0;
	foreach ($row_renta as $row)//compras
	{ 
            $aux=$obBD_con1->getRowConsulta(359, $row['Ren_Cod'].'*'.$Pla_Cod.'*C', $obBD_conexion);
            if (count($aux) > 0)
                $row=  array_merge($row,$aux);
		$fila++;
		if ($row['Renta'] >0)
		{
		/** 
		* Se debe tomar el monto total de la factura 
		*/
		$total_h = $total_h + round($row['Renta'],2);
		$total_renta = $total_renta + round($row['Renta'],2);
	?>
	<tr>
	  <td width="20">
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="<? echo $row['Pld_Cod']; ?>">
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" id="datos[<? echo $fila; ?>,2]" size="7" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="<? echo $row['Pld_Cdc']; ?>" />
      </td>
	  <td width="224" class="LetraNegra">
      <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="30" maxlength="100" value="<? echo $row['Pld_Des']; ?>" readonly>
      </td>
	  <td width="281">
      <input  name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="35" maxlength="25" value="<?Php  if($row['Ren_Ret']=='I') echo "Ret. IVA "; else echo "Renta "; echo $row['Ren_Por']."% - ".$row['Ren_Sri']; ?>">
      </td>
	  <td width="59">&nbsp;</td>
		<td width="63" align="right"><input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<? echo round($row['Renta'],2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" /></td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">
        </td>
	   </tr>
	<? } //Fin del foreach foreach ($row_rs_cuentas_d as $row)
	}//Fin del if ($row['Renta'] >0)
	}//Fin del if (count($row_rs_cuentas_d) > 0)
	
	/** 
	* Cargado para el HABER
	*/
	$total_h = $total_h + ($total_d-$total_renta);
	$fila++;
	?>
	<tr>
	  <td>
      <input name="datos[<? echo $fila; ?>,1]" id="datos[<? echo $fila; ?>,1]" type="hidden" value="">
      <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador2=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&fila=<?Php echo $fila; ?>','ajax_modal');">
        	<i class="icon-edit icon-white"></i>
        </button>
      </td>
	  <td width="54">
      <input name="datos[<? echo $fila; ?>,2]" type="text" 
	  id="datos[<? echo $fila; ?>,2]" onKeyPress="if (event.keyCode == 13){loadCuenta('<? echo $fila; ?>',this.value);}" value="" size="7">
	  </td>
	  <td width="224">
        <input name="datos[<? echo $fila; ?>,3]" id="datos[<? echo $fila; ?>,3]" type="text" size="20" maxlength="100" value="" readonly>
	</td>
		<td width="281">
        <input name="datos[<? echo $fila; ?>,6]" id="datos[<? echo $fila; ?>,6]" type="text" size="23" maxlength="25" value="">
        </td>
		<td width="59">&nbsp;</td>
		<td width="63" align="right">
        <input name="datos[<? echo $fila; ?>,5]" id="datos[<? echo $fila; ?>,5]" type="text" size="7" maxlength="10" value="<?Php echo round($total_d-$total_renta,2); ?>" style="text-align:right" onBlur="numerico(this)" onKeyUp="sumar_totales()" title="">
        </td>
		<td width="33" align="center">
        <input id="quitar_fila2" type="button" class="BotonEliminar" name="quitar_fila2" value="X" onClick="quitar_fila_st(this)">			  
        </td>
	    </tr>

	
	<?php
	break;
	} //Fin del  switch ($op)
	
	?> 
	</tbody>
    <tfoot>	
	<tr>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td align="right" class="Etiqueta1"><strong>TOTALES</strong>:</td>
	  <td align="right"><input name="t_debe" type="text" align="right" id="t_debe" size="7" style="text-align:right" maxlength="10" readonly value="<?Php echo round($total_d,2); ?>"></td>
	  <td align="right"><input name="t_haber" type="text" align="right" id="t_haber" size="7" style="text-align:right" maxlength="10" readonly value="<?Php echo round($total_h,2);?>"></td>
	  <td></td>
	  </tr>
      </tfoot>	
	</table>
    <!--
	<table width="203" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="100">
        <button type="button" class="btn btn-primary start" title="Agregar cuenta al Debe" onClick="nueva_fila('c_contenido','debe','<?Php //echo $_SERVER['PHP_SELF']; ?>?Pla_Cod='+document.getElementById('Pla_Cod').value+'&Pec_Cod=<?Php //echo $Pec_Cod; ?>', '', '', '')">
           <i class="icon-list-alt icon-white"></i>
           <span>Debe</span>
    </button>
        </td>
	    <td width="103">
        <button type="button" class="btn btn-primary start" title="Agregar cuenta al Haber" onClick="nueva_fila('c_contenido','haber','<?Php //echo $_SERVER['PHP_SELF']; ?>?Pla_Cod='+document.getElementById('Pla_Cod').value+'&Pec_Cod=<?Php //echo $Pec_Cod; ?>', '', '', '')">
           <i class="icon-list-alt icon-white"></i>
           <span>Haber</span>
    </button>        
        </td>		
	  </tr>
	</table>-->
    <input id="nfilas" name="nfilas" type="hidden" value="<?Php echo $fila; ?>">
    <input id="Codigo" name="Codigo" type="hidden" value="<? echo $codigo; ?>">
    <input id="op" name="op" type="hidden" value="<? echo $op; ?>">
	
     <?php
   echo barra_estado(count($row_rs_cuentas_d)+count($row_rs_cuentas_d_sb)+count($row_rs_cuentas)+count($row_rs_cuentas_d_snge));
   ?>
   <div style="float:right;text-align:right;width: 200px;"><b>Diferencia:</b>
   	<input name="t_diff" type="text" align="right" id="t_diff" size="7"  style="text-align:right;" maxlength="10" readonly value="<?Php echo round($total_d-$total_h,2);?>">
    </div>
	</FIELDSET>	
</FIELDSET>
	<br>
		<table width="324" border="0" cellpadding="0" cellspacing="0">
		  <tr>
		    <td width="106"><button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*op*hdd_volver', '<? echo $volver_busqueda.'*'.$volver_opciones.'*'.$volver_mes.'*'.$Pec_Cod.'*'.$op.'*1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button></td>
		    <td width="106">
            <input id="bt_save" name="bt_save" type="hidden" value="Grabar">
            <input id="hdd_save" name="hdd_save" type="hidden" value="1">            
		      <button type="button" class="btn btn-primary start" title="Guardar" onClick="validar_inscomp(this.form)">
	          <i class="icon-book icon-white"></i>
	          <span>Guardar</span>
	          </button>		        	  
	        </td>
		    <td width="112"><button type="button" class="btn btn-success fileinput-button" title="Agregar cuenta" name="button1" id="button1" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=1&Pec_Cod=<?Php echo $Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','ajax_modal');">
           <i class="icon-list-alt icon-white"></i>
           <span>Agregar</span>
           </button>
           <input name="cantmodal" id="cantmodal" type="hidden" value="2">
           </td>
		  </tr>
		</table>
	</form>
    <script language="javascript">
	document.getElementById('Com_Val').value = '<?Php echo round($total_d,2); ?>';
	</script>
	<br>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	   <div id="ajax_modal">
        	 
       </div>  
</div>
<?Php } //Fin del ELSE if (!isset($hdd_save) && !isset($txt_busqueda)) 
} ?></td>
  </tr>
</table>
</div>
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
<script type="text/javascript" src="../VALIDACIONES/con_par_compr.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	  
</BODY></HTML>
<?Php
$obBD_conexion->cerrar();
?>