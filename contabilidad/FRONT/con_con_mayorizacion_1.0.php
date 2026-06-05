<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?	
/**
* Descripciï¿½n: Permite consultar la mayorizacion contable
* Fecha de actualizaciï¿½n:	2010-11-15 
* Desarrollador:	Lewis Chimarro 
* Fecha de actualizaciï¿½n:	2012-06-24
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
{
	$op = 1;
}

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
		$rs_buscar = $obBD_con1->consulta(sentencias_con(312,$obBD_con1->parametros(trim($buscod).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod)), $obBD_conexion->conexion);
	}
	elseif ($op_op=='c')
	{
		/**
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$rs_buscar = $obBD_con1->consulta(sentencias_con(313,$obBD_con1->parametros(trim($buscod).'*'.$Ses_Emp_Cod.'*'.$parametro.'*'.$Pla_Cod)), $obBD_conexion->conexion);
	}
	$row_rs_buscar = $obBD_con1->registros();
	$total_rs_buscar = $obBD_con1->numregistros();					
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
			  <?Php 
			  if($total_rs_buscar != 0)		
				{			  
				  do { 
					/**
					* Consulta del detallete de la CUENTA 
					*/
					$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_buscar['Pld_Rec'])), $obBD_conexion->conexion);
					$row_rs_recur = $obBD_con1->registros();					  				  
				  ?>
				  <tr>
					<td><? echo $row_rs_buscar['Pld_Cdc']; ?></td>
				  	<td><?php echo utf8_encode($row_rs_buscar['Pld_Des']); ?></td>
				  	<td align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']; }else{ echo "&nbsp;"; } ?></td>
				  	<td align="center"><? echo $row_rs_buscar['Pld_Tip']; ?></td>				  
				  	<td align="center"><? echo $row_rs_buscar['Pld_Est']; ?></td>
				  	<td align="center">
					<?Php if ($row_rs_buscar['Pld_Est'] == 'Activa'){?>
                  	<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="document.getElementById(document.getElementById('name_input').value).value='<? echo $row_rs_buscar['Pld_Cdc']; ?>'">
        						<i class="icon-arrow-right icon-white"></i>
        			</button>
                  <?php }else{ echo "&nbsp;"; } ?>				
				  	</td>
				  </tr>
				  <?Php } while ($row_rs_buscar = $obBD_con1->fetch_assoc($rs_buscar));
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
	  			<?Php
				} //Fin del else if($total_rs_buscar != 0)	?>
                </tbody>
		  </table>
		</FIELDSET>		
	  <?Php 
		/**
		* Muestra la barra de estados con la cantidad de registros encontrados 
		*/
		echo barra_estado($total_rs_buscar);
	@$obBD_con1->free_result($rs_buscar);
	@$obBD_con1->free_result($rs_recur);	
	exit();
}//Fin del if (isset($cuenta))

/**
*  Busca las cuentas contables
*/
if(isset($cuentas))
{		
?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" id="buscador">
      <tr>
        <td>		
        <FIELDSET>
          <LEGEND>
          <label class="Titulos2">B&uacute;squeda de Cuentas </label>
          </LEGEND>
          <table width="444" border="0" cellpadding="0" cellspacing="0" >
            <tr>
              <td width="217"><input name="op_opciones" id="op_opciones" type="radio" value="d" checked="checked" onClick="document.getElementById('op_op').value = this.value; document.getElementById('buscta').focus();">
                  <span class="LetraNegra">Descripci&oacute;n</span></td>
              <td width="227"><input type="radio" name="op_opciones" id="op_opciones" value="c" onClick="document.getElementById('op_op').value = this.value; document.getElementById('buscta').focus();">
                  <span class="LetraNegra">C&oacute;digo</span></td>
            </tr>
          </table>
          <input name="op_op" type="hidden" id="op_op" value="d">
          <input name="name_input" type="hidden" id="name_input" value="<? echo $name_input?>">
          <table width="580" height="36" border="0" cellpadding="0" cellspacing="0">
            <tbody id="tbusqueda">
              <tr>
                <td width="66" height="28" class="BarraBusqueda" align="right"><span class="Asterisco">*</span> Cuenta:</td>
                <td width="550" class="BarraBusqueda">
                <input name="buscta" type="text" id="buscta" size="50" maxlength="50" onKeyUp="parametro_injection(this)" onKeyPress="enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op=<?Php echo $op; ?>&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value+'&Pla_Cod=<?Php echo $Pla_Cod; ?>', 'busqueda')">	
                <button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op=<?Php echo $op; ?>&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value+'&Pla_Cod=<?Php echo $Pla_Cod; ?>', 'busqueda')"> <i class="icon-search icon-white"></i> <span>Buscar</span> 
                </button>                    
                </td>
              </tr>
            </tbody>
          </table>          
      </FIELDSET>
      <div id="busqueda"></div>
  </table>
<?
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
		* Consulta  el codigo interno de la cuenta contable
		*/
		$rs_cuenta_manual = $obBD_con1->consulta(sentencias_con(209, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta_manual = $obBD_con1->registros();
		$total_rs_cuenta = $obBD_con1->numregistros();
	    $Pld_Cod = $row_rs_cuenta_manual['Pld_Cod'];		
		/**
		* Consulta del saldo, anterior a la inicial 
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
		$rs_cuenta = $obBD_con1->consulta(sentencias_con(201, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$Pld_Cod.'*'.$ordenar.'*'.$Pec_Cod))
										, $obBD_conexion->conexion);
		$row_rs_cuenta = $obBD_con1->registros();
		$total_rs_cuenta = $obBD_con1->numregistros();		
		/**
		* Carga el aï¿½o de la fecha incial 
		*/
		list($annn, $mess, $dia) = preg_split('![/.-]!', $fech_fut);
		$anio = date("Y", mktime(0,0,0,$mes,$dia,$ann));
	}//Fin del if ($txt_busqueda != "")
	break;
	case 2:
	if ($grupo != "")
	{
		/**
		* Consulta el codigo interno de la cuenta inicial 
		*/
		$rs_cuenta_int = $obBD_con1->consulta(sentencias_con(216, $obBD_con1->parametros(trim($grupo).'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta_int = $obBD_con1->registros();
		$Pld_Cod= $row_rs_cuenta_int['Pld_Cod'];
		/**
		* Consulta del rango de cuentas para la busqueda 
		*/
		$rs_rango = $obBD_con1->consulta(sentencias_con(203, $obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);
		$row_rs_rango = $obBD_con1->registros();
		$total_rs_rango = $obBD_con1->numregistros();
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
		$rs_periodo = $obBD_con1->consulta(sentencias_con(219, $obBD_con1->parametros($Ses_Emp_Cod)), $obBD_conexion->conexion);
		$row_rs_periodo = $obBD_con1->registros();
		$total_rs_periodo = $obBD_con1->numregistros();
	}//Fin del else if (isset($hdd_save))
}

if(isset($ajax_info)){
	include('../COMPONENTES/con_con_detalleCompr.php');
exit();
}
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>  
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
			/* Campo 1 */
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
		}); 		
        </script>                		        
		<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	</HEAD>
<BODY>
<div id="set1">
     <table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	 <tr class="BarraTitulo">
	  <td height="10">&raquo; mayorizaci&oacute;n general <?Php echo $periodo; ?> </td>
    </tr>
	<tr>
        <td height="340" valign="top">		
        <form name="form1" method="post" action="<?Php  echo $_SERVER['PHP_SELF']; ?>">
<?php
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($hdd_save3))
{ ?>
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">SelecciÃ³n Periodo Contable</label>
	</LEGEND>
    <table width="268" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
        <td width="115">
		  <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">		
		<select name="Pec_Cod2" id="Pec_Cod2" onChange="javascript: asignar_fechas(this.value)">
		<?Php 
		if ($total_rs_periodo > 0)
		{
		do{
		?>
			<option value="<?Php echo $row_rs_periodo['Pec_Cod'].'~'.$row_rs_periodo['Pec_Fei'].'~'.$row_rs_periodo['Pec_Fef'].'~'.$row_rs_periodo['Pla_Cod']; ?>"><?Php echo $row_rs_periodo['Periodo']; ?></option>	
		<?php		
		}while($row_rs_periodo = $obBD_con1->fetch_assoc($rs_periodo));
		}//Fin del if ($total_rs_periodo > 0)
		else
		{ ?>
			<option value=""></option>
		<?Php
		}
		?>	
        </select>
         </td>
        <td width="84" align="center">
        <button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod2', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>        
          <input name="hdd_save" type="hidden" id="hdd_save"></td>
      </tr>
    </table>
</FIELDSET>		
<?Php
}//Fin del if (!isset($hdd_save))

if (isset($hdd_save) or isset($hdd_save2) or isset($hdd_save3))
{
	$pag1= $_SERVER['PHP_SELF']."?op=1&Pec_Cod2=".$Pec_Cod2."&hdd_save=1";
	$pag2= $_SERVER['PHP_SELF']."?op=2&Pec_Cod2=".$Pec_Cod2."&hdd_save=1";
	tabs(2,'Cuenta'.'*'.'Grupos', $pag1.'*'.$pag2, $op);
	?>
    <form name="form1" method="post" action="<?Php  echo $_SERVER['PHP_SELF']; ?>">
	<div id="ContTabul">		
	<table width="99%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="43%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
        <td width="57%"><?php include("../COMPONENTES/con_con_presentacion.php"); ?></td>
      </tr>
    </table>
    <input name="Pec_Cod2" type="hidden" id="Pec_Cod2" value="<?php echo $Pec_Cod2; ?>">
    <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
    <input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>">
    <input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
<?Php
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
	      <td width="215" valign="middle"><input name="txt_busqueda" type="text" id="txt_busqueda" value="<?Php echo $txt_busqueda; ?>" size="30" 
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
	<td width="110">
    <button type="button" class="btn btn-success fileinput-button" title="Buscar Cuenta de Detalle" name="button1" id="button1" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cuentas=1&name_input=txt_busqueda&Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>&op=<? echo $op;?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','mostrar');">
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
	case 2:
?>
	<table width="99%" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>	
        <FIELDSET>
		<LEGEND>
			<label class="Titulos2">Buscar por grupos:</label>
		</LEGEND>
		<table border="0" cellpadding="0" cellspacing="0">
    	<tr>
	      <td width="63" height="28" class="Etiqueta1"><span class="Asterisco">* </span>Cuenta:&nbsp;</td>
	      <td width="215"><input name="grupo" type="text" id="grupo" value="<?Php echo $grupo; ?>" size="30" 
		  	maxlength="50" onBlur="/*validar_cuentas(form1, this)*/"></td>
	      </tr>
  		</table>
	</FIELDSET></td>
      </tr>
    </table>
<br>
	
	<table border="0" cellpadding="0" cellspacing="0">
	  <tr>		
		<td width="110">
        <button type="button" class="btn btn-success fileinput-button" title="Buscar Cuenta de Detalle" name="button1" id="button1" onClick=" Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?cuentas=1&name_input=grupo&Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>&op=<? echo $op;?>&Pla_Cod=<?Php echo $Pla_Cod; ?>','mostrar');">
           <i class="icon-list-alt icon-white"></i>
           <span>Cuenta</span>
           </button>
		  <input name="hdd_save3" type="hidden" id="hdd_save3"></td>
	    <td width="154">
        <button type="button" class="btn btn-success fileinput-button" title="Mayorizar" name="button" id="button" onClick="validar_buscar_cuenta(document.form1, 'grupo')">
           <i class="icon-check icon-white"></i>
           <span>Mayorizar</span>
           </button>    
             <input name="cantmodal" id="cantmodal" type="hidden" value="2">
            </td>
	  </tr>
	</table>			 
<?Php
	break;
}//Fin del case $op
	?>
		<input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
		</form>
<?Php 
switch ($op){
	case 1: 
		if (isset($txt_busqueda))
		{
			$total_debe = 0;
			$total_haber = 0;
			/**
			* Consulta del detallete de la CUENTA 
			*/
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_cuenta_manual['Pld_Rec'])), $obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros(); ?>
   <br />  
   <FIELDSET>
   <LEGEND>
        <label class="Titulos2">Detalle de MayorizaciÃ³n:</label>
   </LEGEND>
   <div id="Exportar_a_Excel">       
  <table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
    <tr>
      <td width="47" class="Etiqueta1">Desde:</td>
      <td width="201">&nbsp;<?Php echo $txt_fec_ini; ?></td>
      <td width="125" class="Etiqueta1">Hasta:</td>
      <td width="387">&nbsp;<?Php echo $txt_fec_fin; ?></td>
      </tr>
    <tr>
      <td class="Etiqueta1">CÃ³digo:</td>
      <td>&nbsp;<?Php echo $row_rs_recur['Pld_Cdc']; ?></td>
      <td class="Etiqueta1">GRUPO:</td>
      <td>&nbsp;<?Php echo $row_rs_recur['Pld_Des']; ?></td>
    </tr>
    <tr>
      <td class="Etiqueta1">C&oacute;digo:</td>
      <td>&nbsp;<?Php echo $row_rs_cuenta_manual['Pld_Cdc']; ?></td>
      <td class="Etiqueta1">Cuenta:</td>
      <td>&nbsp;<?Php echo $row_rs_cuenta_manual['Pld_Des']; ?></td>
      </tr>
  </table>
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
  <thead>
    <tr>      
      <th align="center" width="11%">C&oacute;d. Int.</th>
      <th align="center" width="11%">Generaci&oacute;n</th>
      <th align="center" width="7%">No. Com.</th>
      <th align="center" width="10%">Fecha</th>
      <th align="center" width="13%">Cliente/Proveedor</th>
      <th align="center" width="20%">Detalle</th>
      <th align="center" width="10%">Debe</th>
      <th align="center" width="10%">Haber</th>
      <th align="center" width="6%">Saldo</th>
      <th align="center" width="2%">&nbsp;</th>	        	  
    </tr>
	</thead>
   <tbody>
	<?Php
	if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
	?>
    <tr>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>SALDO AL  <?php echo $dia.', de '.mes($mess, 1).', '.$annn; ?></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
      <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
      <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>>&nbsp;</td>
    </tr>
    <?	
	/**
	* Inicializa las variables para mostrar detalles (+-) en la mayorizacion 
	*/
	$com_codigo_aux = $row_rs_cuenta['Com_Cod']; 
	$mas_menos = true;
	$i=0; 
	  do {
	  		/**
			* Consulta del cliente o proveedor 
			*/
			if ($row_rs_cuenta['Tia_Ini'] == 'I')
			{
				/**
				* Consulta la descripcion del cliente 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(217, $obBD_con1->parametros($row_rs_cuenta['Cli_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();							
			}
			else
			{
				/**
				* Consulta la descripcion del proveedor 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(218, $obBD_con1->parametros($row_rs_cuenta['Prv_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();					
			}//Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
		  
	  $i++; 
	  list($ann, $mes, $dia) = preg_split('![/.-]!', $row_rs_cuenta['Com_Fec']);
	  /**
	  * Control para mostrar una sola vez el + 
	  */
	  $com_codigo = $row_rs_cuenta['Com_Cod'];
	  ?>	
    <tr>
      <td align="center"><? echo $row_rs_cuenta['Com_Cod']; ?></td>
      <td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
      <td align="center"><?Php echo  "C".$row_rs_cuenta['Tia_Ini']."-".$mes."-".$row_rs_cuenta['Com_Num']; ?></td>
      <td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?></td>
      <td align="left"><?Php echo $row_rs_proveedore['Prs_Ape'].' '.$row_rs_proveedore['Prs_Nom']; ?></td>	  	  
      <td><? echo $row_rs_cuenta['Com_Con']; ?></td>	  	  
   	  <td align="right"><? if ($row_rs_cuenta['Asi_Deh'] == 'D')
	  					{
							echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
							$debe = $row_rs_cuenta['Asi_Val'];
							$total_debe = $total_debe + $debe;							
						} 
						else 
						{ 
							echo "0,00"; 
							$debe = 0;
						}?></td>
      <td align="right"><? if ($row_rs_cuenta['Asi_Deh'] == 'H')
	  					{
							echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
							$haber = $row_rs_cuenta['Asi_Val'];
							$total_haber = $total_haber + $haber;							
						} 
						else 
						{ 
							echo "0,00"; 
							$haber = 0;
						}
			?></td>
			<?Php 
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
	  <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
	  <td align="right"><button type="button" class="btn btn-success btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<? echo $row_rs_cuenta['Com_Cod'];?>&Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
    </tr>
	<?Php
	/* Muestra el mas solo una vez */		
	if ($mas_menos == true && $cmb_mas_menos=="true")
	{	  
	?>	
    <?Php
	}//FIn del if ($mas_menos == true)
	 } while ($row_rs_cuenta = $obBD_con1->fetch_assoc($rs_cuenta));
	?>
    </tbody>
    <tfoot>	
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td align="right">TOTAL</td>
      <td align="right"><?Php echo formato_numero($total_debe, 2, 2);?></td>
      <td align="right"><?Php echo formato_numero($total_haber, 2, 2);?></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
    </tr>
    </tfoot>
    <?Php
	  } else { ?>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><?Php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <? } //Fin del else	
		/**
		* Muestra la barra de estados con la cantidad de registros encontrados 
		*/
		echo barra_estado($total_rs_cuenta);
	} //Fin del if ($txt_busqueda)
	?>
    
  </table>
</div>
</FIELDSET>	 
<?Php 	
	break;
	case 2: 
	if (isset($grupo))
	{  ?>
    <hr>
    <div id="Exportar_a_Excel">        
  <?Php 
  if ($total_rs_rango > 0){
	/**
	* Inicializa las variables para mostrar detalles (+-) en la mayorizacion 
	*/ 
	$com_codigo_aux = $row_rs_cuenta['Com_Cod']; 
	$mas_menos = true;
    $i=0; 
	     
	  do{
		$total_debe = 0;
		$total_haber = 0;
		$saldo = 0;
		/**
		* Consulta del saldo, anterior a la inicial 
		*/
		$fech_fut = fechas_futuras($txt_fec_ini, -1);	
		$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut.'*'.$row_rs_rango['Pld_Cod'].'*'.$Pec_Cod)), 
							$obBD_conexion->conexion);
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
		$tipo_grupo = explode('.', $grupo);
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
		$rs_cuenta = $obBD_con1->consulta(sentencias_con(201, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$row_rs_rango['Pld_Cod'].'*'.$ordenar.'*'.$Pec_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta = $obBD_con1->registros();
		$total_rs_cuenta = $obBD_con1->numregistros();				
		/**
		* Carga el aï¿½o de la fecha incial 
		*/
		list($ann, $mes, $dia) = preg_split('![/.-]!', $fech_fut);
		$anio = date("Y", mktime(0,0,0,$mes,$dia,$ann));

		if ($total_rs_cuenta > 0 or $total_rs_saldos > 0){
			/**
			* Consulta del detallete de la CUENTA 
			*/
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_rango['Pld_Rec'])), $obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();				
		?>
        <table width="450" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
        <tr>
          <td width="47" class="Etiqueta1">Desde:</td>
          <td width="120"><?Php echo $txt_fec_ini; ?></td>
          <td width="73" class="Etiqueta1">Hasta:</td>
          <td width="192"><?Php echo $txt_fec_fin; ?></td>
          </tr>
      </table>
  		<table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
          <tr>
              <td class="Etiqueta1">C&oacute;digo:</td>
              <td><?Php echo $row_rs_recur['Pld_Cdc']; ?></td>
              <td class="Etiqueta1">GRUPO:</td>
              <td><?Php echo $row_rs_recur['Pld_Des']; ?></td>
           </tr>
           <tr>	
              <td width="49" class="Etiqueta1">C&oacute;digo:</td>
              <td width="201"><?Php echo $row_rs_rango['Pld_Cdc']; ?></td>
              <td width="123" class="Etiqueta1">Cuenta:</td>
              <td width="387"><?Php echo $row_rs_rango['Pld_Des']; ?></td>
           </tr>
		</table>
        <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03">
        <thead>
            <tr>
              <th align="center" width="4%">&nbsp;</th>
              <th align="center" width="5%">C&oacute;d. Int.</th>
              <th align="center" width="10%">Generaci&oacute;n</th>
              <th align="center" width="7%">No. Com.</th>
              <th align="center" width="10%">Fecha</th>
              <th align="center" width="13%">Cliente/Proveedor</th>
              <th align="center" width="21%">Detalle</th>
              <th align="center" width="10%">Debe</th>
              <th align="center" width="10%">Haber</th>
              <th align="center" width="10%">Saldo</th>	  
            </tr>	
           </thead>
           <tbody>
            <tr>
              <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                  <td align="center">&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>SALDO AL <?php echo $dia.', de '.mes($mes, 1).', '.$anio; ?></td>
                  <td align="right">&nbsp;</td>
                  <td align="right">&nbsp;</td>
                  <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php 
                                    echo formato_numero($saldos, 2, 2); ?></td>
            </tr>
	<?			  
	  do{ 
	  		/**
			* Consulta del cliente o proveedor 
			*/
			if ($row_rs_cuenta['Tia_Ini'] == 'I')
			{
				/**
				* Consulta la descripcion del cliente 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(217, $obBD_con1->parametros($row_rs_cuenta['Cli_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();							
			}
			else
			{
				/**
				* Consulta la descripcion del proveedor 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(218, $obBD_con1->parametros($row_rs_cuenta['Prv_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();					
			}//Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
	  
	  $i++;
	  ?>	
			<tr>
			  <td align="center"><?Php
	/**
	* Control para mostrar una sola vez el + 
	*/
	$com_codigo = $row_rs_cuenta['Com_Cod'];
	if ($com_codigo != $com_codigo_aux)
	{
		$com_codigo_aux = $row_rs_cuenta['Com_Cod'];
		$mas_menos = true;
	}//Fin del if ($com_codigo != $com_codigo_aux)
	elseif ($i > 1)// esta condicion es para mostrar el + al inicio
	{
		$mas_menos = false;
	}//Fin del else if ($com_codigo != $com_codigo_aux)	
	/**
	* Muestra el mas solo una vez 
	*/
	if ($mas_menos == true && $cmb_mas_menos=="true")
	{ ?>
    <img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onClick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)"><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onClick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)">
<?Php
	}//Fin del if ($com_codigo == $com_codigo_aux)
	else
	{
		echo "&nbsp;";
	}	
?>
 		  </td>
          <td align="center"><? echo $row_rs_cuenta['Com_Cod']; ?></td>
          <td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
          <td align="center"><?Php echo  "C".$row_rs_cuenta['Tia_Ini']."-".$mes."-".$row_rs_cuenta['Com_Num']; ?></td>
          <td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?></td>
          <td align="center"><?Php echo $row_rs_proveedore['Prs_Ape'].' '.$row_rs_proveedore['Prs_Nom']; ?></td>	  	  
          <td><? echo $row_rs_cuenta['Com_Con']; ?></td>	  	  
          <td align="right">
		  		<? if ($row_rs_cuenta['Asi_Deh'] == 'D')
					{
						echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
						$debe = $row_rs_cuenta['Asi_Val'];
						$total_debe = $total_debe + $debe;							
					} 
					else 
					{ 
						echo "0.00"; 
						$debe = 0;
					}?>
          </td>
		  <td align="right">
		  		<? if ($row_rs_cuenta['Asi_Deh'] == 'H')
					{
						echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
						$haber = $row_rs_cuenta['Asi_Val'];
						$total_haber = $total_haber + $haber;							
					} 
					else 
					{ 
						echo "0.00"; 
						$haber = 0;
					} ?></td>
					<?Php 
                    $tipo_grupo = explode('.', $grupo);
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
                      <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php 
                                        echo formato_numero($saldos, 2, 2);
                                        ?>
                      </td>
			      </tr>
					<?Php
                    /**
					* Muestra el mas solo una vez 
					*/
                    if ($mas_menos == true && $cmb_mas_menos=="true")
                    {	  
                    ?>							
					  <tr id="detalle[<?Php echo $i; ?>]">
					      <td align="center">&nbsp;</td>
					      <td colspan="9" align="center"><?Php include("../COMPONENTES/con_con_detalleCompr.php"); ?></td>
				      </tr>	
			    <?Php
					}//FIn del if ($mas_menos == true)
				 } while ($row_rs_cuenta = $obBD_con1->fetch_assoc($rs_cuenta));
				?>
                </tbody>
                <tfoot>	
				    <tr>
					      <td colspan="7" align="right">TOTAL</td>
					      <td align="right"><?Php echo formato_numero($total_debe, 2, 2);?></td>
					      <td align="right"><?Php echo formato_numero($total_haber, 2, 2);?></td>
					      <td align="right">&nbsp;</td>
				    </tr>
                </tfoot>
                
		</table> 
        </div>       
		<?Php
			/**
			* Muestra la barra de estados con la cantidad de registros encontrados 
			*/
			echo barra_estado($total_rs_cuenta)."<br>";			
		 	}// Fin del if ($total_rs_cuenta > 0)
	  } while ($row_rs_rango = $obBD_con1->fetch_assoc($rs_rango));
  } //Fin del if ($total_rs_rango > 0)
 }//Fin del if (isset($grupo))
	break;
} //Fin del switch ?>
 </fielset>
<?php
}//Fin del if (isset($hdd_save))

if ($total_rs_cuenta > 0 or $total_rs_saldos > 0 or $total_rs_cuenta_plan > 0 or $total_rs_rango>0) 
{ 
/**
* Condicion para mostrar o no el boton imprimir
*/
?>
<br>
<table width="220" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <th width="110" scope="col"><form action="con_pri_mayorizacion_1.0.php" method="post" name= "form2" id="form2" target="_blank">
<button type="button" class="btn btn-primary start" title="Imprimir Mayor" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
<input name="op" type="hidden" id="op" value="<?Php echo $op; ?>">
<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?Php echo $Pec_Cod; ?>">
<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?Php echo $Pla_Cod; ?>">
<input name="txt_busqueda" type="hidden" id="txt_busqueda" value="<?Php echo $txt_busqueda; ?>">
<input name="grupo" type="hidden" id="grupo" value="<?Php echo $grupo; ?>">
<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?Php echo $txt_fec_ini; ?>">
<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?Php echo $txt_fec_fin; ?>">
<input name="ordenar" type="hidden" id="ordenar" value="<?Php echo $ordenar; ?>">
</form></th>
    <th width="110" scope="col"><form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
  	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
  	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>
	</form></th>
  </tr>
</table>

<?Php
}//Fin del if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) 
?>
</td>
  </tr>
</table>  
</div>
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()"></div>
<div id="bgmodal"  class="bgmodal"   style="display:none">		
	<div id="mostrar"></div>
 </div>
<script type="text/javascript" src="../VALIDACIONES/con_par_mayorizacion.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY>
</HTML>
<?Php 
@$obBD_con1->free_result($rs_saldos);
@$obBD_con1->free_result($rs_cuenta);
@$obBD_con1->free_result($rs_rango);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_cuenta_int);
@$obBD_con1->free_result($rs_cuenta_manual);
@$obBD_con1->free_result($rs_periodo);
@$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>