<?	
/**
* Descripción: Duplicar comprobantes
* Fecha de actualización: 2016-Mar-16
* Desarrollador: Jose Cumbicos
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');	  
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creación del objeto para evitar el reenvio 
*/
$thisPost = new Post_Block;

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);;
/* Cracion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;
/***********************************************/
$hoy = date("Y-m-d");
$mes = date("m");

if(isset($ajax_info)){	
	include('../COMPONENTES/con_con_detalleCompr.php');
exit();
}

if(isset($ajax_info2)){		
	if($op=='1')
	{
		$row_rs_dato = $obBD_con1->getRowConsulta(374, $com_codigo, $obBD_conexion);
		$vet_codigo=$row_rs_dato['Vet_Cod'];		
		include('../COMPONENTES/con_com_detalle_ven.php');
	}
	if($op=='2')
	{
		$row_rs_dato = $obBD_con1->getRowConsulta(373, $com_codigo, $obBD_conexion);
		$cop_codigo=$row_rs_dato['Cop_Cod'];		
		include('../COMPONENTES/con_com_detalle_com.php');
	}
	if($op=='3')
	{
		$row_rs_dato = $obBD_con1->getRowConsulta(373, $com_codigo, $obBD_conexion);
		$cop_codigo=$row_rs_dato['Cop_Cod'];
		include('../COMPONENTES/con_com_detalle_com.php');
	}
exit();
}
/* Control para el calculo del numero de comprobantes */
if (isset($ajax_compr))
{  
	/* Carga el año de la fecha incial */
	list($ann, $mes, $dia) = split('[/.-]', $Com_Fec);
	$Com_Num = $obBD_con1->codigoComprAuto2($opTia, $Pec_Cod, $mes, $obBD_conexion);
	
?>
    <input name="ComNueNum" type="text" readonly="true" class="LetraNegra" id="ComNueNum" size="10" maxlength="7" value="<? echo $TiaAbr."-".date('m',$Com_Fec)."-".$Com_Num; ?>" style="border-style:none">
    <input name="hddComNueNum" type="hidden" readonly="true" class="LetraNegra" id="hddComNueNum" size="10" maxlength="7" value="<? echo $Com_Num; ?>" style="border-style:none">
<?Php
exit();
}//Fin del if (isset($ajax_compr))

/* Consulta para la eleccion del periodo contable*/
if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($Com_Cod))
{   
	/* Carga el periodos contable actual */
	$row_rs_periodos = $obBD_con1->getArrayConsulta(214,$Ses_Emp_Cod,$obBD_conexion);	
	$total_rs_periodos = count($row_rs_periodos);

}//Fin del if ($Pec_Cod)
else
{	
	/* Grabado de los Comprobantes, ya sean estos de Ingreso / Egreso / Ajuste */
	if ($thisPost->postBlock($_POST['postID'])) 
	{ 		
		if(isset($bt_save))
		{
			//$row_rs_perio = $obBD_con1->getRowConsulta(390,$Com_Fec.'*'.$Ses_Emp_Cod,$obBD_conexion);
			//$total_row_rs_perio=$row_rs_perio['Per_Cod'] > 0? 1 : 0;	
			
			/* Cracion del objeto mysql para las inserciones */
				$obBD_ins1 =  new Class_Log_Datos_Con;		
				/*Inicio de la transaccion*/
				$obBD_ins1->inicio_transaccion($obBD_conexion->conexion);
				$obBD_ins1->operacionobBD(324, $Pec_Cod.'*'.$nombre.'*'.$hddComNueNum.'*'.$Com_Fec.'*'.$Com_Con.'*'.$Tia_Cod.'*'.$Com_Val.'*'.$Com_Obs.'*'.$Com_Tipo.'*'.$campo,$obBD_conexion);
				$ultimo=$obBD_ins1->insercionid($obBD_conexion->conexion);
				
				for ($i=1;$i<=$f;$i++)  
				{
					$cuenta=current($cuen);
					next($cuen);
					$Asi_Deh=current($dh);
					next($dh);		
					$Asi_Val=current($val);
					next($val);
					$Asi_Con=current($desc);
					next($desc);
					$Asi_Glo=current($glosa);
					next($glosa);
					
					/* Inserción del asiento contable del Comprobante */
					$obBD_ins1->operacionobBD(325,$ultimo.'*'.$Asi_Deh.'*'.$Asi_Val.'*'.$Asi_Con.'*'.$Asi_Glo.'*'.$cuenta,$obBD_conexion);						
				}	
					
				/****************************************************************/
				$obBD_ins1->fin_transaccion($obBD_conexion->conexion);
				/***************************************************************/																					
	
	 }//FIn del if (isset($bt_save))
	}

	/*Permite inicializar la variable OP por primera y unica vez*/
	if (!(isset($op)))
	{
		$op = 1;
	}	 
	   
	switch ($op){
		case 3: //Inicio de la opcion 3
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;
		
		case 2: //Inicio de la opcion 2
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;
	
		case 1:
			if (isset($txt_busqueda) || isset($cod))
			{
				$tabla="cliente";
				$campo="Cli_Cod";
			}
		break;
	}//Fin del switch ($op)

	/* Cargado de los datos de la cabecera */
	if ($txt_busqueda !="") {	
		if ($op_opciones == "d")
			{	/*por nombre de proveecor/cliente*/
				$rs_cabcomp = $obBD_con1->getArrayConsulta(370,$tabla.'*'.trim($txt_busqueda).'*'.$TiaIni.'*'.$Pec_Cod.'*'.$campo.'*'.$cmb_mes.'*'.'A',$obBD_conexion);
			}
			else 
			{
				/*  Control para busqueda mensual  por numero de comprobante*/				
				$mes_array = explode('-', $txt_busqueda);
				if (count($mes_array)==2)
				{
					$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
					$txt_busqueda = $mes_array[1];//$mes[1] es el numero del comprobante
				}					
				$rs_cabcomp = $obBD_con1->getArrayConsulta(371,$tabla.'*'.trim($txt_busqueda).'*'.$TiaIni.'*'.$Pec_Cod.'*'.$campo.'*'.$cmb_mes.'*'.'A', $obBD_conexion);
			} 				
		$total_rs_cabcomp =count($rs_cabcomp);
	}
	else 
	{ 
	if (isset($cod))
	{				
		//$rs_codcompr = $obBD_con1->getRowConsulta(149,$tabla.'*'.$cod.'*'.$op.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion);
		$row_rs_codcompr = $obBD_con1->getRowConsulta(372,$tabla.'*'.$cod.'*'.$op.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion);							
		$total_rs_codcompr=$row_rs_codcompr['Com_Cod'] > 0? 1 : 0;
	}//Fin del if (isset($cod))
	}
	/*Divide la cadena del periodo contable */
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];
	/* Consulta del periodo contable */
	$rs_periodo = $obBD_con1->consulta(sentencias_con(113, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
	$row_rs_periodo = $obBD_con1->registros();
	$total_rs_periodo = $obBD_con1->numregistros();

	/* Descripcion del periodo contable */
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);			
/*************************************************/
/*************************************************/
}//Fin del else if ($Pec_Cod)
/*************************************************/
/*************************************************/

?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>    
		<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>		
		<script language="javascript" src="../VALIDACIONES/con_val_compr.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script>

        <script language="javascript" src="../VALIDACIONES/XML.js"></script>
        <script>
		$(function() { 
			//var imagen = "../../mascaras/model1/imagenes/32x32/calendar.gif";
			/* Campo 1 */
			$( "#Com_Fec1" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});		
		}); 
		</script>
        <style>
		#tbl_comp tr:hover {
			background-color: #CBDEFE  ;
		}
		</style>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; DUPLICAR COMPROBANTES <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td height="389" align="left" valign="top">
<form action="<? echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1"> 
	<?
/* Control para la elección del periodo contable */
if (!isset($hdd_save) && !isset($txt_busqueda) && !isset($Com_Cod))
{
?>
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selección Periodo Contable</label>
	</LEGEND>

    <table width="30%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="18%" class="Etiqueta1">Periodo:&nbsp; </td>
        <td width="32%">
		  <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodos[1]['Pec_Fei']; ?>">
          <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodos[1]['Pec_Fef']; ?>">		
		<select name="Pec_Cod" id="Pec_Cod" onChange="javascript: asignar_fechas(this.value)">
		<?Php 
		if ($total_rs_periodos > 0)
		{
			foreach($row_rs_periodos as $row){
			?>
				<option value="<?Php echo $row['Pec_Cod'].'*'.$row['Pec_Fei'].'*'.$row['Pec_Fef']; ?>"><?Php echo $row['Periodo']; ?></option>	
			<?php		
			};
		}//Fin del if ($total_rs_periodo > 0)
		else
		{ ?>
			<option value=""></option>
		<?Php
		}
		?>	
        </select>
         </td>
        <td width="50%" align="left"><button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'Pec_Cod', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        	</button>
        <input name="hdd_save" type="hidden" id="hdd_save">
        </td>
      </tr>
    </table>
</FIELDSET>			 
<?Php
}//Fin del if (!isset($Pec_Cod))
else
{
		/* Consulta de los tipos de asientos */
		$rs_tipo_asien = $obBD_con1->consulta(sentencias_con(210, ''), $obBD_conexion->conexion);
		$row_rs_tipo_asien = $obBD_con1->registros();
		$total_rs_tipo_asien = $obBD_con1->numregistros();
		do {
			$descripcion = $descripcion.$row_rs_tipo_asien['Tia_Des'].'*';
			$array_asien[] = $row_rs_tipo_asien['Tia_Des'];
		}while($row_rs_tipo_asien = mysqli_fetch_assoc($rs_tipo_asien));
	
		$pag1= $_SERVER['PHP_SELF']."?op=1&TiaIni=I&hdd_save&Pec_Cod=".$Pec_Cod;
		$pag2= $_SERVER['PHP_SELF']."?op=2&TiaIni=E&hdd_save&Pec_Cod=".$Pec_Cod;
		$pag3= $_SERVER['PHP_SELF']."?op=3&TiaIni=D&hdd_save&Pec_Cod=".$Pec_Cod;
		tabs(3,'Ingreso*Egreso*Diario', $pag1.'*'.$pag2.'*'.$pag3, $op);
	?>
	<div id="ContTabul">	
	<?
	if (($op==1 || $op==2 || $op==3)) {
	switch($op) {
	case 1: $etiqueta="Buscar Comprobante de Ingreso a duplicar: "; $TiaIni='I'; break;
	case 2:	$etiqueta="Buscar Comprobante de Egreso a duplicar: "; $TiaIni='E'; break; 
	case 3:	$etiqueta="Buscar Comprobante de Diario a duplicar: "; $TiaIni='D'; break; }
	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2"><? echo $etiqueta; ?></label>
	</LEGEND>
	<table width="539" height="27" border="0">
      <tr>
        <td width="116" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d" checked onClick="document.getElementById('cmb_mes').disabled=false; setfocus(form1.txt_busqueda)">
          Apellidos</td>
        <td width="197" class="LetraNegra"><input type="radio" name="op_opciones" value="r" onClick=" setfocus(form1.txt_busqueda)">
          No. de Comprobante </td>
        <td width="212" class="LetraNegra"> Mes:&nbsp;
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
	<table width="575" height="36" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="87" height="28" class="BarraBusqueda"><div align="right">Busqueda:</div></td>
        <td width="488" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)">&nbsp;&nbsp;&nbsp; <input name="op" id="op" type="hidden" value="<? echo $op; ?>" >
          <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
          <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
          <input name="TiaIni" id="TiaIni" type="hidden" value="<?php echo $TiaIni; ?>">
          <button type="submit" class="btn btn-success fileinput-button" title="Buscar">
            <i class="icon-search icon-white"></i>
            <span>Buscar</span>
            </button>        </td>
        </tr>
    </table>
	</FIELDSET>	
	<? } ?>	
</form>
<? 
if(isset($txt_busqueda))
	{
	  ?>	
	<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
	<table id="tbl_comp" width="100%" border="0" cellpadding="1" cellspacing="0">
    <thead>
        <tr class="Cabecera1">
          <th width="6%" height="26">No. Int </th>
          <th width="4%">Tipo</th>
          <th width="8%">No. Compr </th>
          <th width="10%">C&eacute;dula</th>
          <th width="20%" align="left">Proveedor/Cliente</th>
          <th width="14%">Tipo Comprob.          
		  <th width="17%">Concepto          
		  <th width="7%">Fecha</v>
		  <th width="8%">Valor</th>
		  <th width="3%">&nbsp;</th>
		  <th width="5%">&nbsp;</th>
		  <th width="3%">&nbsp;</th> 
        </tr>
    </thead>
    <tbody>
		<?
	if ($total_rs_cabcomp > 0) {		  				
		foreach($rs_cabcomp as $row_rs_cabcomp) {
	   ?>
	   <form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
		<tr class="Fondo">
		  <td align="center"><? echo $row_rs_cabcomp['Com_Cod']; ?></td>
		  <td align="center"><font color="<?php echo $rojo; ?>">
		    <?Php 
		  /**
		  * Control para mostrar si el comprobante es automatico o manual 
		  */		  
		  if ($row_rs_cabcomp['Com_Gen']=='A')
		  {
		  		echo "A";
		  }//Fin del if ($row_rs_compr_auto > 0)
		  else
		  {
		  		echo "M";
		  }//Fin del else if ($row_det_rs_ccpp_p > 0)
		  ?>
		  </font></td>		  
          <td align="center">&nbsp;<? echo $row_rs_cabcomp['Com_Num']; ?></td>
		  <td align="center" ><? echo $row_rs_cabcomp['Prs_Ced']; ?>&nbsp;</td>
		  <td >&nbsp;<? echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
		  <td align="center" ><font color="<?php echo $rojo; ?>"><? echo $row_rs_cabcomp['Tia_Des']; ?></font></td>
		  <td align="left" ><font color="<?php echo $rojo; ?>"><? echo $row_rs_cabcomp['Com_Con']; ?></font></td>		  
		  <td align="center" >&nbsp;<? echo $row_rs_cabcomp['Com_Fec']; ?></td>			
		  <td align="right" >&nbsp;<? echo $row_rs_cabcomp['Com_Val']; ?></td>
		  <td align="center"><button type="button" class="btn btn-info btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<? echo $row_rs_cabcomp['Com_Cod'];?>&Ses_Emp_Cod=<? echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
		  <td align="center"><button type="button" class="btn btn-success btn-mini" title="Ver Factura/Retención" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info2=1&com_codigo=<? echo $row_rs_cabcomp['Com_Cod'];?>&op=<? echo $op;?>&Ses_Suc_Cod=<? echo $Ses_Suc_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
		  <td align="center">		  		
          <input name="cod" id="cod" type="hidden"  value="<?php echo $row_rs_cabcomp['Com_Cod']; ?>">
          <input name="op" id="op" type="hidden"  value="<?php echo $op; ?>">
          <input name="Tia_Cod" id="Tia_Cod" type="hidden"  value="<?php echo $row_rs_cabcomp['Tia_Cod']; ?>">
          <input name="TiaIni" id="TiaIni" type="hidden" value="<?php echo $TiaIni; ?>">
          <input name="TiaAbr" id="TiaAbr" type="hidden" value="<?php echo $row_rs_cabcomp['Tia_Abr']; ?>">
          <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
          <input name="hdd_save" id="hdd_save" type="hidden" value="">				
          <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
          <i class=" icon-arrow-right icon-white"></i>
          </button>		  			  
		  </td>					
       </tr>
	   </form>
        <?	  		
	   };       
	}
		else
		{ ?>
			<tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
	      <td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="center"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
		<?php
		}
	  ?>	
      </tbody>	  
      </table>
	<?php 
		echo barra_estado($total_rs_cabcomp);
	?>	  
	</FIELDSET>
<? }

if ($cod > 0 && !(isset($txt_busqueda)))
{ 
  if ($total_rs_codcompr > 0) 
  {
	switch($op) {
	case 1: $etiqueta=$array_asien[0]; break;
	case 2:	$etiqueta=$array_asien[1]; break;
	case 3:	$etiqueta=$array_asien[2]; break; }	
	?>
	<br>
	<form method="post" name="form2" action="<?Php echo $_SERVER['PHP_SELF']; ?>">	
    <?Php 
	/** 
	* Creacion del campo repost 
	*/
	$thisPost->startPost();

	?>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Datos del Comprobante de  <? echo $etiqueta; ?></label>
	</LEGEND>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Generales</label>
	</LEGEND>	
	  <table width="100%" border="0" cellpadding="1" cellspacing="1">	
        <tr>
          <td width="12%" class="Etiqueta1">C&oacute;digo:</td>
          <td width="26%" class="LetraNegra"><? echo $row_rs_codcompr['Com_Num']; ?>
          <input name="Com_Cod" type="hidden" id="Com_Cod" value="<? echo $row_rs_codcompr['Com_Cod']; ?>">
          <input name="Tia_Cod" type="hidden" id="Tia_Cod" value="<? echo $Tia_Cod; ?>">
          </td>
          <td width="9%" class="Etiqueta1">N&uacute;mero: </td>
          <td width="53%" class="LetraNegra"><input name="Com_Num" type="text" class="LetraNegra" id="Com_Num" size="10" maxlength="7"  readonly="true" style="border-style:none" value="<? echo $row_rs_codcompr['Tia_Abr'].'-'.date('m',$row_rs_codcompr['Com_Fec']).'-'.$row_rs_codcompr['Com_Num']; ?>"></td>
        </tr>
		<tr>
		  <td class="Etiqueta1">Nombre:</td>
          <td class="LetraNegra"><div align="left">&nbsp;<? echo $row_rs_codcompr['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?> <input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<? echo $Pec_Cod; ?>">
              <? if ($row_rs_codcompr['Cli_Cod']!="") {?>
              <input name="nombre" type="hidden" id="nombre" value="<? echo $row_rs_codcompr['Cli_Cod']; ?>">
		  <? } else {?>
		  <input name="nombre" type="hidden" id="nombre" value="<? echo $row_rs_codcompr['Prv_Cod']; ?>">
		  <? }?>
          </div></td>
          <td class="Etiqueta1">Nuevo n&uacute;mero:</td>
          <td class="LetraNegra"><div id="div_codigo"><input name="ComNueNum" type="text" class="LetraNegra" id="ComNueNum" size="10" maxlength="7"  readonly="true" style="border-style:none" ></div></td>
		</tr>
		<tr>
		  <td class="Etiqueta1">Concepto:</td>
		  <td colspan="3" align="left" class="LetraNegra"><input name="Com_Con" type="text" id="Com_Con" size="30" value="<? echo $row_rs_codcompr['Com_Con']; ?>">		  </td>
		</tr>
		<tr>
			<td class="Etiqueta1">Observación:</td>
			<td colspan="3" align="left" class="LetraNegra"><input name="Com_Obs" type="text" id="Com_Obs" size="30" value="<? echo $row_rs_codcompr['Com_Obs']; ?>"> 
			<input name="Com_Tipo" type="hidden" id="Com_Tipo" value="<? echo $row_rs_codcompr['Com_Tipo']; ?>">
			<input name="op" type="hidden" id="op" value="<? echo $op; ?>">			</td>
		</tr>
		<tr>
			<td class="Etiqueta1">Fecha:</td>
			<td align="left" class="LetraNegra"><? echo $row_rs_codcompr['Com_Fec']; ?></td>
		    <td align="left" class="Etiqueta1"><span class="Asterisco">*</span> Fecha:</td>
		    <td align="left" class="LetraNegra"><input name="Com_Fec" type="text" class="LetraNegra" id="Com_Fec" size="10" maxlength="10" value="" onBlur="validar_fecha2(this); " onKeyUp="mascara(this,'-',patron,true); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_compr=1&opTia=<?Php echo $Tia_Cod; ?>&TiaAbr=<? echo $TiaAbr;?>&Pec_Cod=<?Php echo $Pec_Cod; ?>&Com_Fec='+this.value,'div_codigo')"></td>
		</tr>
		<tr>
		  <td class="Etiqueta1">Valor:</td>
		  <td colspan="3" align="left" class="LetraNegra"><? echo $row_rs_codcompr['Com_Val']; ?> <input name="Com_Val" type="hidden" id="Com_Val" value="<? echo $row_rs_codcompr['Com_Val']; ?>">		 </td>
	    </tr>
		</table>
	  </FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cuentas</label>
	</LEGEND>		  
	  <table width="80%" border="0" align="center" cellpadding="1" cellspacing="0">
	  <?
	  /* Cargado del detalle */
	  $rs_cuentas = $obBD_con1->getArrayConsulta(327,$row_rs_codcompr['Com_Cod'],$obBD_conexion);	  
	  $total_rs_cuentas = count($rs_cuentas);
	  $total=0;
	  foreach($rs_cuentas as $row_rs_cuentas) {
	  $f++;
	  ?>
	  
	  <tr align="center">
        <td class="LetraNegra" align="left"><? echo $obBD_con1->mascara_cuenta($row_rs_cuentas['Pld_Cdc']); ?><input name="cuen<?php echo "[".$f."]"; ?>" type="hidden" id="cuen<?php echo "[".$f."]"; ?>" value="<?Php echo $row_rs_cuentas['Pld_Cod']; ?>"></td>		
		<? if ($row_rs_cuentas['Asi_Deh']=='D') { ?>
		<td class="LetraNegra" align="left"><? echo $row_rs_cuentas['Pld_Des']; ?>		</td>
		<? } else { ?>
		<td class="LetraNegra" align="left">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<? echo $row_rs_cuentas['Pld_Des']; ?></td>
		<? }?>
		<input name="desc<?php echo "[".$f."]"; ?>" type="hidden" id="desc<?php echo "[".$f."]"; ?>" value="<?Php echo $row_rs_cuentas['Pld_Des']; ?>">
		<input name="glosa<?php echo "[".$f."]"; ?>" type="hidden" id="glosa<?php echo "[".$f."]"; ?>" value="<?Php echo $row_rs_cuentas['Asi_Glo']; ?>">
		<input name="dh<?php echo "[".$f."]"; ?>" type="hidden" id="dh<?php echo "[".$f."]"; ?>" value="<?Php echo $row_rs_cuentas['Asi_Deh']; ?>">
        <td class="LetraNegra">
          <div align="right">
            <? if ($row_rs_cuentas['Asi_Deh']=='D') { echo number_format($row_rs_cuentas['Asi_Val'],2); $total=$total + $row_rs_cuentas['Asi_Val']; } else { echo '&nbsp'; }?>
            </div></td>
		<td class="LetraNegra"><div align="right"><? if ($row_rs_cuentas['Asi_Deh']=='H') { echo number_format($row_rs_cuentas['Asi_Val'],2); } else{ echo '&nbsp'; } ?></div></td>
		<input name="val<?php echo "[".$f."]"; ?>" type="hidden" id="val<?php echo "[".$f."]"; ?>" value="<?Php echo $row_rs_cuentas['Asi_Val']; ?>">
      </tr>
	  <? } ?>
	  <input name="f" id="f" type="hidden" value="<?Php echo $f; ?>">
    </table>
	</FIELDSET>		  
	</FIELDSET>	
	<br>
	<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td align="left">
			  <input id="bt_save" name="bt_save" type="hidden" value="1">
<!--			  <input name="bt_grabar" type="button" class="Boton_Guardar" value="Duplicar" title="Duplicar" onClick="validar_dupli(this.form)">		-->
              <button type="button" class="btn btn-primary start" title="Guardar documento de compra" name="btn_guardar" onClick="validar_requeridos(this.form, 'Com_Fec', 1)">
   <i class="icon-book icon-white"></i>
   <span>Guardar</span>
   </button>
			</td>
		  <input name="tabla" type="hidden" value="<? echo $tabla; ?>">
		  <input name="campo" type="hidden" value="<? echo $campo; ?>"> 
		</tr>
      </table>
	</form>
      <? } //Fin del if ($total_rs_codcompr > 0)
	} //FIn del if ($cod > 0 && !(isset($txt_busqueda)))
}////Fin del ELSE if (!isset($hdd_save) && !isset($txt_busqueda))
?></td>
  </tr>
</table>	
<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar">
             
             </div>
       </div>
</div>  
</BODY></HTML>
<?php
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>