<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* Descripciï¿½n: Permite consultar la mayorizacion contable
* Fecha de actualizaciï¿½n:	2010-11-15 
* Desarrollador:	Lewis Chimarro 
* Fecha de actualizaciï¿½n:	2012-06-24
* Desarrollador:	Lewis Chimarro 
* Fecha de actualizaciï¿½n:	2015-05-05
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_mayorizacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');	

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

/**
* Permite inicializar la variable OP por primera y unica vez 
*/
if (!(isset($op)))
{ $op = 1; }

/**
* Cargado ajax de la busqueda de la cuenta 
*/
if (isset($buscod))
{
	if ($name_input == "grupo")
	{
		$parametro = "AND det_plan.Pld_Tip = 'G'";
	}
	else
	{
		$parametro = "AND det_plan.Pld_Tip = 'D'";	
	}
	
	if ($op_op=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(312,trim($buscod).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod, $obBD_conexion);
	}
	elseif ($op_op=='c')
	{
		/**
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$rs_buscar = $obBD_con1->getArrayConsulta(313,trim($buscod).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod, $obBD_conexion);
	}
	$total_rs_buscar = count($rs_buscar);
?>
	<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
            <thead>
			  <tr>
				<th width="10%">C&oacute;digo</th>
				  <th>Descripci&oacute;n</th>
				  <th>Grupo</th>				  
				  <th>Tipo</th>				  
				  <th width="7%">Estado</th>
				  <th width="7%">&nbsp;</th>
			  </tr>
              </thead>
              <tbody>
			  <?php 
			  if($total_rs_buscar != 0)		
				{			  
				  foreach($rs_buscar as $row)
				  {
					/**
					* Consulta del detallete de la CUENTA 
					*/
					$rs_recur = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);  
				  ?>
				  <tr class="Fondo">
					<td><?php echo $row['Pld_Cdc']; ?></td>
				  <td><?php echo utf8_encode($row['Pld_Des']); ?></td>
				  <td align="center"><?php if ($rs_recur['Pld_Des'] != ""){ echo $rs_recur['Pld_Des']; }else{ echo "&nbsp;"; } ?></td>
				  <td align="center"><?php echo $row['Pld_Tip']; ?></td>				  
				  <td align="center"><?php echo $row['Pld_Est']; ?></td>
				  <td align="center"><?php if ($row['Pld_Est'] == 'Activa'){?>
                  			<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="document.getElementById(document.getElementById('name_input').value).value='<?php echo $row['Pld_Cdc']; ?>'">
        						<i class="icon-arrow-right icon-white"></i>
        					</button>	                  
                  <?php }else{ echo "&nbsp;"; } ?>				
				  </td>
				  </tr>
				  <?php }//Fin del foreach
				}//Fin del if($total_rs_buscar != 0)	
				else
				{
				  ?>
                  <tr>
                    <td width="10%">&nbsp;</td>
                      <td><?php echo error_alerta(" No hay resultados que mostrar", 2)?></td>
                      <td>&nbsp;</td>				  
                      <td>&nbsp;</td>				  
                      <td width="7%">&nbsp;</td>
                      <td width="7%">&nbsp;</td>
                  </tr>
	  			<?php
				} //Fin del else if($total_rs_buscar != 0)	?>
               </tbody> 
		  </table>
		</FIELDSET>		
	  <?php 
		/**
		* Muestra la barra de estados con la cantidad de registros encontrados 
		*/
		echo barra_estado($total_rs_buscar);
	exit();
}//Fin del if (isset($cuenta))

/**
* Muestra el buscador de las cuentas contables
*/
if (isset($ajax_buscador))
{ ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" id="buscador">
      <tr>
        <td>
        <FIELDSET>
          <LEGEND>
          <label class="Titulos2">B&uacute;squeda de Cuentas </label>
          </LEGEND>
          <table width="444" border="0" cellpadding="0" cellspacing="0" >
            <tr>
              <td width="217"><input name="op_opciones" id="op_opciones" type="radio" value="d" checked="checked" onClick="document.getElementById('op_op').value = this.value; setfocus(document.getElementById('buscta')">
                  <span class="LetraNegra">Descripci&oacute;n</span></td>
              <td width="227"><input type="radio" name="op_opciones" id="op_opciones" value="c" onClick="document.getElementById('op_op').value = this.value; setfocus(document.getElementById('buscta')">
                  <span class="LetraNegra">C&oacute;digo</span></td>
            </tr>
          </table>
          <input name="op_op" type="hidden" id="op_op" value="d">
          <input name="name_input" type="hidden" id="name_input" value="<?php echo $ajax_input; ?>">
          <table width="579" border="0" cellpadding="0" cellspacing="0">
            <tbody id="tbusqueda">
              <tr>
                <td width="440" class="BarraBusqueda"><span class="Asterisco">*</span> Cuenta:
                <input name="buscta" type="text" id="buscta" size="40" maxlength="50" onKeyUp="parametro_injection(this)" onKeyPress="enter_ajax('<?php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op=<?php echo $op; ?>&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value+'&Pla_Cod=<?php echo $Pla_Cod; ?>', 'busqueda')"></td>
                <td width="139" align="center">
                  <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op=<?php echo $op; ?>&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value+'&Pla_Cod=<?php echo $Pla_Cod; ?>', 'busqueda')"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>                    
                </td>
              </tr>
            </tbody>
          </table>
       </FIELDSET>
          <div id="busqueda"> </div>      
  </table>
<?php
exit();	
	}

/**
* Muestra el detalle de los comprobantes
*/
if (isset($ajax_detalle))	
{
	$com_codigo = $ajax_codigo;
	 include("../COMPONENTES/con_con_detalleCompr.php"); 
exit();
}
/**
* Descripcion del periodo contable 
*/
$periodo = "del periodo contable ".substr($Pec_Fei, 0,4);		

if (isset($hdd_save2) or isset($hdd_save3))
{
	$hoy = date("Y-m-d");

/**
* OPCIONES 
*/
switch ($op){
	case 1: 
	/**
	* Cargado de los datos de la cabecera 
	*/
	if ($txt_busqueda != "")
	{
		/**
		* Consulta del detalle de la CUENTA buscada
		*/
		$row_cuenta = $obBD_con1->getRowConsulta(314,trim($txt_busqueda).'*'.$Pla_Cod,$obBD_conexion);				
	    $Pld_Cod = $row_cuenta['Pld_Cod'];				
		/**
		* Consulta del saldo, anterior a la fecha inicial dentro de un mismo periodo (No cambiar esta forma antigua de llamado de las sql)
		*/
		$fech_fut = fechas_futuras($txt_fec_ini, -1);	
		$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut.'*'.$Pld_Cod.'*'.$Pec_Cod))
										, $obBD_conexion->conexion);
		$row_rs_saldos = $obBD_con1->registros();
		$total_rs_saldos = $obBD_con1->numregistros();
	
		/**
		* Se realiza esto porque solo deben haber dos registros 
		*/
		/**
		* De los dos supuestos registros encontrados toma por defecto el primero 
		*/
		if ($row_rs_saldos['Asi_Deh'] == 'D')
		{
			$debe = $row_rs_saldos['Asi_Val'];		   
		   /**
		   * Mueve el puntero al inicio 
		   */
		   $row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);			
		}
		else
		{
			$debe = 0;
		}
	
		$haber= $row_rs_saldos['Asi_Val'];
		$tipo_grupo = explode('.', $txt_busqueda);
		/**
		* 1 = Activo
		* 2 = Pasivo
		* 3 = Patrimonio
		* 4 = Ingresos
		* 5 = Costos y Gastos 
		*/
		if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
		{//Nuevo
			$saldos = $haber - $debe; //Formula especial			
		}//Nuevo
		else //Nuevo
		{//Nuevo			
			$saldos = $debe - $haber;
		}//Nuevo		
		/**
		* Consulta del detalle de la mayorizacin 
		*/
		$rs_cuenta = $obBD_con1->getArrayConsulta(341, $txt_fec_ini.'*'.$txt_fec_fin.'*'.$Pld_Cod.'*'.$ordenar.'*'.$Pec_Cod.'*'.$Com_Aut							, $obBD_conexion);
		$total_rs_cuenta = count($rs_cuenta);
		/**
		* Carga el aï¿½o de la fecha incial 
		*/
		list($annn, $mess, $dia) = preg_split('![/.-]!', $fech_fut);
		$anio = date("Y", mktime(0,0,0,$mess,$dia,$annn));
	}//Fin del if ($txt_busqueda != "")
	break;
	case 2:
	if ($grupo != "")
	{
		/**
		* Consulta el codigo interno de la cuenta inicial 
		*/
		$rs_cuenta_int = $obBD_con1->getRowConsulta(216, trim($grupo).'*'.$Pla_Cod, $obBD_conexion);
		$Pld_Cod= $rs_cuenta_int['Pld_Cod'];
		/**
		* Consulta del rango de cuentas para la busqueda 
		*/
		$rs_rango = $obBD_con1->getArrayConsulta(203, $Pld_Cod, $obBD_conexion);
		$total_rs_rango = count($rs_rango);
	}		
	break;
}//FIn del case $op
}//Fin del if (isset($hdd_save))
else
{
	if (isset($hdd_save))
	{
		/**
		* Divide la cadena del periodo contable 
		*/
		$arreglo = explode("~",$Pec_Cod2); 		
		$Pec_Cod = $arreglo[0];
		$Pec_Fei = $arreglo[1];
		$Pec_Fef = $arreglo[2];
		$Pla_Cod = $arreglo[3];		
	}//Fin del if (isset($hdd_save))
	else
	{
		/**
		* Carga todos los periodos contables, Activos y Anulados 
		*/
		$rs_periodo = $obBD_con1->getArrayConsulta(219, $Ses_Emp_Cod, $obBD_conexion);
		$total_rs_periodo = count($rs_periodo);
		$row_rs_periodo = current($rs_periodo);
	}//Fin del else if (isset($hdd_save))
}
?>
<HTML>
	<HEAD>
		<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?php echo "Informe Mayor [EXA]"; ?></TITLE>
        <meta charset= "UTF-8"> 
		<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>  
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script language="javascript" src="../VALIDACIONES/con_val_mayorizacion.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>
        <!--Librerias para exportar a excel --> 
	    <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>        
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <!--Librerias para modal -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>         
       <script>
		$(function() { 
			/**
			* Campo 1 
			*/
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
		}); 		
        </script>                		        
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<div id="set1">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10">&raquo; mayorizaci&oacute;n general <?php echo $periodo; ?> </td>
    </tr>
	<tr>
        <td valign="top" height="400">		
            <form name="form1" method="post" action="<?php  echo $_SERVER['PHP_SELF']; ?>">
<?php
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($hdd_save3))
{ ?>
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">SelecciÃ³n Periodo Contable</label>
	</LEGEND>
    <table width="304" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
        <td width="115">
		  <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">		
		<select name="Pec_Cod2" id="Pec_Cod2" onChange="javascript: asignar_fechas(this.value)">
		<?php 
		if ($total_rs_periodo > 0)
		{
			foreach ($rs_periodo as $row)
			{
		?>
			<option value="<?php echo $row['Pec_Cod'].'~'.$row['Pec_Fei'].'~'.$row['Pec_Fef'].'~'.$row['Pla_Cod']; ?>"><?php echo $row['Periodo']; ?></option>	
		<?php		
			}
		}//Fin del if ($total_rs_periodo > 0)
		else
		{ ?>
			<option value=""></option>
		<?php
		}
		?>	
        </select>
         </td>
        <td width="120" align="center">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod2', 0)"> <i class="icon-search icon-white"></i> <span>Aceptar</span> </button>        
          <input name="hdd_save" type="hidden" id="hdd_save"></td>
      </tr>
    </table>
</FIELDSET>		
<?php
}//Fin del if (!isset($hdd_save))

if (isset($hdd_save) or isset($hdd_save2) or isset($hdd_save3))
{
	//$pag1= $_SERVER['PHP_SELF']."?op=1&Pec_Cod2=".$Pec_Cod2."&hdd_save=1";
	//$pag2= $_SERVER['PHP_SELF']."?op=2&Pec_Cod2=".$Pec_Cod2."&hdd_save=1";
	//tabs(2,'Cuenta'.'*'.'Grupos', $pag1.'*'.$pag2, $op);
	?>
    <form name="form1" method="post" action="<?php  echo $_SERVER['PHP_SELF']; ?>">
	<div id="ContTabul">		
	<table width="99%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="48%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
        <td width="52%"><?php include("../COMPONENTES/con_con_presentacion.php"); ?></td>
      </tr>
    </table>
    <input name="Pec_Cod2" type="hidden" id="Pec_Cod2" value="<?php echo $Pec_Cod2; ?>">
    <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
    <input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>">
    <input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
<?php
switch ($op){
	case 1: 
?>
<table width="99%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td><FIELDSET>
		<LEGEND>
			<label class="Etiqueta1">Buscar por cuenta:</label>
		</LEGEND>
		<table border="0" cellpadding="0" cellspacing="0">
    	<tr>
	      <td width="63" height="28" class="Etiqueta1"><span class="Asterisco">* </span>Cuenta:&nbsp;</td>
	      <td width="215" valign="middle">
                  <select name="bancos" id="bancos" onchange="$('#txt_busqueda').val($('#bancos').val());" style="width: 381px;">
                      <option>Seleccione el Banco...</option>
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(399,$Ses_Emp_Cod.'*'.$Pec_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option <?php if($row['Pld_Cdc']==$txt_busqueda) echo "selected"; ?> value="<?php echo $row['Pld_Cdc']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
<?php
        }
    }
?>
                              </select>
                  
                  <input name="txt_busqueda" type="hidden" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>" size="30" 
		  	maxlength="50" onBlur="validar_cuentas(form1, this)" onKeyUp="parametro_injection(this)"></td>
	      </tr>
  		</table>
	    </FIELDSET>
	</td>
  </tr>
</table>
<br>
<table width="274" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<td width="0">
    <button style="display:none" type="button" class="btn btn-success fileinput-button" title="Buscar Cuenta de Detalle" name="button1" id="button1" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=1&ajax_input=txt_busqueda&Pla_Cod=<?php echo $Pla_Cod; ?>', 'ajax_modal')">
           <i class="icon-list-alt icon-white"></i>
           <span>Cuenta</span>
           </button>    
    </td>
	<td width="164">
    <button type="button" class="btn btn-success fileinput-button" title="Mayorizar" name="button" id="button" onClick="validar_balance(this.form, this.form.txt_busqueda)">
           <i class="icon-check icon-white"></i>
           <span>Mayorizar</span>
           </button>    
		<input name="hdd_save2" type="hidden" id="hdd_save2">
        <input name="cantmodal" id="cantmodal" type="hidden" value="2">
        </td>
	</tr>
</table>	
<?php 
	break;	
}//Fin del case $op
	?>
		<input name="op" type="hidden" id="op" value="<?php echo $op; ?>">
		</form>
<?php 
switch ($op){
	case 1:
		if (isset($txt_busqueda))
		{
			$total_debe = 0;
			$total_haber = 0;
			?>
   <br />  
   <div id="Exportar_a_Excel">       
  <table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
    <tr>
      <td width="47" class="Etiqueta1">Desde:</td>
      <td width="201"><?php echo $txt_fec_ini; ?></td>
      <td width="125" class="Etiqueta1">Hasta:</td>
      <td width="387"><?php echo $txt_fec_fin; ?></td>
      </tr>
    <tr>
      <td class="Etiqueta1">CÃ³digo:</td>
      <td><?php echo $row_cuenta['Pld_Cdc_Grupo']; ?></td>
      <td class="Etiqueta1">GRUPO:</td>
      <td><?php echo $row_cuenta['Pld_Des_Grupo']; ?></td>
    </tr>
    <tr>
      <td class="Etiqueta1">C&oacute;digo:</td>
      <td><?php echo $row_cuenta['Pld_Cdc']; ?></td>
      <td class="Etiqueta1">Cuenta:</td>
      <td><?php echo $row_cuenta['Pld_Des']; ?></td>
      </tr>
  </table>
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
  <thead>
    <tr>      
      <th align="center" width="4%">C&oacute;d.</th>
      <th align="center" width="4%">Gen.</th>
      <th align="center" width="10%">No.</th>
      <th align="center" width="12%">Fecha</th>
      <th align="center" width="10%">Tipo pago.</th>
      <th align="center" width="28%">Cliente/Proveedor</th>
	  <th align="center" width="10%">NÂº de Cheque</th>
      <th align="center" width="15%">Detalle</th>
      <th align="center" width="7%">Debe</th>
      <th align="center" width="7%">Haber</th>
      <th align="center" width="7%">Saldo</th>
      <th align="center" width="4%">&nbsp;</th>	        	  
    </tr>
	</thead>
   <tbody>
	<?php
	if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
	?>
    <tr>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
	  <td>&nbsp;</td>
      <td>SALDO AL  <?php echo $dia.', de '.mes($mess, 1).', '.$annn; ?></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
      <td align="right" <?php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
      <td align="right" <?php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>>&nbsp;</td>
    </tr>
    <?php	
	$i=0; 
	  foreach($rs_cuenta as $row)
	  {
	  		/**
			* Consulta del cliente o proveedor 
			*/			
			if ($row['Cli_Cod'] != '')
			{
				/**
				* Consulta la descripcion del cliente 
				*/
				$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
			}
			if ($row['Prv_Cod'] != '')
			{
				/**
				* Consulta la descripcion del proveedor 
				*/
				$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
			}//Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
			$total_rs_proveedore = count($rs_proveedore);
		  
	  $i++; 
	  list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
	  ?>	
    <tr>
      <td align="center"><?php echo $row['Com_Cod']; ?></td>
      <td align="center"><?php echo $row['Com_Gen']; ?></td>
      <td align="center"><?php echo $row['Tia_Abr']."-".$mes."-".$row['Com_Num']; ?></td>
      <td align="center"><?php echo $row['Com_Fec']; ?></td>

      <!--td align="center"><?php echo (isset($row['Che_Num'])?'CH. '.$row['Che_Num']:''); ?></td-->

	  


	  <td align="center">
    <?php 
    echo (isset($row['Pag_des']) ? ' ' . $row['Pag_des'].": " : '') .  (isset($row['Vet_Che']) ? ' ' . $row['Vet_Che'] : ''); 
    ?>
</td>



      <td align="left"><?php echo $row_proveedore['Prs_Ape'].' '.$row_proveedore['Prs_Nom']; ?></td>	  	  
	  <td align="center"><?php echo (isset($row['Che_Num']) && $row['Che_Num'] != '' ? $row['Che_Num'] : (isset($row['Vet_Che']) && $row['Vet_Che'] != '' ? $row['Vet_Che'] : '&nbsp;')); ?></td>
      <td><?php echo $row['Com_Con']; ?></td>	  	  
   	  <td align="right"><?php if ($row['Asi_Deh'] == 'D')
	  					{
							echo formato_numero($row['Asi_Val'], 2, 2); 
							$debe = $row['Asi_Val'];
							$total_debe = $total_debe + $debe;							
						} 
						else 
						{ 
							echo "0,00"; 
							$debe = 0;
						}?></td>
      <td align="right"><?php if ($row['Asi_Deh'] == 'H')
	  					{
							echo formato_numero($row['Asi_Val'], 2, 2); 
							$haber = $row['Asi_Val'];
							$total_haber = $total_haber + $haber;							
						} 
						else 
						{ 
							echo "0,00"; 
							$haber = 0;
						}
			?></td>
			<?php 
			$tipo_grupo = explode('.', $txt_busqueda);
			/**
			* 1 = Activo
			* 2 = Pasivo
			* 3 = Patrimonio
			* 4 = Ingresos
			* 5 = Costos y Gastos 
			*/
			if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
			{//Nuevo
				$saldos = $saldos + ($haber - $debe); //Formula especial			
			}//Nuevo
			else //Nuevo
			{//Nuevo			
				$saldos = $saldos + ($debe - $haber);
			}//Nuevo			
			?>
	  <td align="right" <?php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
	  <td align="center"><button type="button" name="button<?php echo $i+1; ?>" id="button<?php echo $i+1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?php echo $row['Com_Cod']; ?>', 'ajax_modal')">
	        <i class="icon-info-sign icon-white"></i>
	        </button>	</td>
    </tr>
    <?php
	 } //Fin foreach;
	?>
    </tbody>
    <tfoot>	
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
	  <td>&nbsp;</td>
      <td align="right">TOTAL</td>
      <td align="right"><?php echo formato_numero($total_debe, 2, 2);?></td>
      <td align="right"><?php echo formato_numero($total_haber, 2, 2);?></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
    </tr>
    </tfoot>
    <?php
	  } else { ?>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><?php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
	  <td>&nbsp;</td>
	  <td>&nbsp;</td>
    </tr>
    <?php } //Fin del else	
		/**
		* Muestra la barra de estados con la cantidad de registros encontrados 
		*/
		echo barra_estado($total_rs_cuenta);
	} //Fin del if ($txt_busqueda)
	?>
  </table>
</div>	 
<?php 	
	break;
	
} //Fin del switch ?>
 </fielset>
<?php
}//Fin del if (isset($hdd_save))

if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) 
{ 
?>
<br>
<table width="220" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="110" scope="col"><form action="con_pri_mayorizacion_1.1.php" method="post" name= "form2" id="form2" target="_blank">
<button type="button" class="btn btn-primary start" title="Imprimir Mayor" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
<input name="op" type="hidden" id="op" value="<?php echo $op; ?>">
<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>">
<input name="txt_busqueda" type="hidden" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>">
<input name="grupo" type="hidden" id="grupo" value="<?php echo $grupo; ?>">
<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
<input name="ordenar" type="hidden" id="ordenar" value="<?php echo $ordenar; ?>">
</form></td>
    <td width="110" scope="col"><form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
  	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
  	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>
	</form></td>
  </tr>
</table>
<?php
}//Fin del if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) 
?>
</td>
  </tr>
</table>	  
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
</div>
	<div id="bgmodal"  class="bgmodal"   style="display:none">	
	<div id="ajax_modal"></div>
 </div>
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_mayorizacion.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY>
</HTML>
<?php 
/** 
* Cierra las conexiones 
*/
$obBD_conexion->cerrar();
?>