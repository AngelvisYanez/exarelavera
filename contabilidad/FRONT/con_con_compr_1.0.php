<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
* @abstract Permite la consulta individual y total de los comprobantes 
* @author Lewis Chimarro
* @version 1.0
* Fecha de actualización  2012-05-01
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_compr.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
/*
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* 
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");
$mes = date("m");

/* 
* Consulta para la eleccion del periodo contable
*/
if (!isset($hdd_save) && !isset($txt_busqueda))
{
	/* 
	* Carga los periodos contables 
	*/
	$row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);
}//Fin del if (!isset($hdd_save) && !isset($txt_busqueda))
else
{	
	/* 
	* Permite inicializar la variable OP por primera y unica vez 
	*/
	if (!(isset($op)))
	{
		$op = 1;
	}	    
	
	/***************/
	/***************/	
	/*** OPCIONES***/
	/***************/
	/***************/
	switch ($op)
	{
		case 1:
			if (isset($txt_busqueda) || isset($codigo))
			{
				$tabla="cliente";
				$campo="Cli_Cod";
			}
		break;
				
		case 2: 
			if (isset($txt_busqueda) || isset($codigo))
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;

		case 3: 
			if (isset($txt_busqueda) || isset($codigo) )
			{
				$tabla="proveedore";
				$campo="Prv_Cod";
			}	
		break;	
	}//Fin del switch ($op)
	/* 
	* Cargado de los datos de la cabecera 
	*/
	if (isset($txt_busqueda))
	{	
		if ($op_opciones == "d")
		{
			$row_rs_cabcomp = $obBD_con1->getArrayConsulta(148, $tabla.'*'.$txt_busqueda.'*'.$op.'*'.$Pec_Cod.'*'.$campo.'*'.$cmb_mes, $obBD_conexion);
		}
		else 
		{
			/*  
			* Control para busqueda mensual  
			*/
			$mes_array = explode('-', $txt_busqueda);
			if (count($mes_array)==2)
			{
				$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
				$busqueda = $mes_array[1];//$mes_array[1] es el numero del comprobante
			}
			$row_rs_cabcomp = $obBD_con1->getArrayConsulta(348, $tabla.'*'.$txt_busqueda.'*'.$op.'*'.$Pec_Cod.'*'.$campo.'*'.$Par_Fec, $obBD_conexion);
		}  			
	}
	else if (isset($codigo))
	{
		/* 
		* Consulta de la cabecera del comprobante 
		*/
		$row_rs_codcompr = $obBD_con1->getRowConsulta(147, $tabla.'*'.$codigo.'*'.$op.'*'.$Pec_Cod.'*'.$campo, $obBD_conexion);
		/* 
		* Cargado de las cuentas a consultar 
		*/
		$row_rs_cuentas = $obBD_con1->getArrayConsulta(327, $row_rs_codcompr['Com_Cod'], $obBD_conexion);	
		
		/* 
		* Consulta del reporte para impresion 
		*/
		$pagina = $_SERVER['PHP_SELF'];
		$reportes = $obBD_con1->reportes($pagina, $Ses_Emp_Cod,$obBD_conexion);
		$hdd_reportes = $reportes[1];	
	}

	if (isset($txt_fec_ini))
	{
		/* 
		* CRITERIO = tipo - tabla - campo 
		*/
		$criterio=explode("*",$Com_Tip);
		/* 
		* SQL = tabla - tipo - campo 
		*/
		switch ($Com_Aut){
			case 'T':
				$generacion = "";
			break;
			case 'M':
				$generacion = " AND comprobantes.Com_Gen = 'M'";
			break;
			case 'A':
				$generacion = " AND comprobantes.Com_Gen = 'A'";
			break;
		}
		$row_rs_comfec = $obBD_con1->getArrayConsulta(335, $criterio[1].'*'.$criterio[0].'*'.$criterio[2].'*'.$txt_fec_ini.'*'.$txt_fec_fin.'*'.$option.'*'.$generacion.'*'.$Ses_Emp_Cod, $obBD_conexion);
	}
	
	/*
	* Divide la cadena del periodo contable 
	*/
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];
	/* 
	* Consulta del periodo contable 
	*/
	$row_rs_periodo = $obBD_con1->getRowConsulta(113, $Pec_Cod.'*'.$Ses_Emp_Cod, $obBD_conexion);
	/* 
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".$row_rs_periodo['Ann'];			
}//FIn del else if (!isset($hdd_save) && !isset($txt_busqueda))

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
		<script language="javascript" src="../VALIDACIONES/con_val_compr.js"></script>
        <script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.modals.js"></script> 
	    <script language="javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>

	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	    <!--Librerias para calendario -->       
        <script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script> 
        <script>
		$(function() { 
			/* Campo 1 */
			$( "#Com_Fec" ).datepicker();			
			$( "#Com_Fec" ).change(function() {
			$( "#Com_Fec" ).datepicker( "option", "dateFormat", "yy-mm-dd" );
		});			
		}); 		
        </script>    
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"></HEAD>
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Consultar  Comprobantes <?Php echo $periodo; ?></td>
  </tr>
	<tr>
      <td height="389" align="left" valign="top"> 
<?Php
/* Control para la elección del periodo contable */
if (!isset($hdd_save) && !isset($txt_busqueda))
{
?>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selección Periodo Contable</label>
	</LEGEND>
	<table width="225" border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td width="53" class="Etiqueta1">Periodos:&nbsp; </td>
	    <td width="88"><?php
		$periodo = current($row_rs_periodos); 
		?>
	      <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $periodo['Pec_Fei']; ?>" />
	      <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $periodo['Pec_Fef']; ?>" />
	      <select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)">
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
	    <td width="84" align="center"><button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
	      <input name="hdd_save" type="hidden" id="hdd_save" /></td>
	    </tr>
	  </table>  
</FIELDSET>		
</form>	 
<?Php
}//Fin del if (!isset($hdd_save) && !isset($txt_busqueda))	
else
{
	/* 
	* Consulta de los tipos de asientos 
	*/
	$row_rs_tipo_asien = $obBD_con1->getArrayConsulta(210, '',$obBD_conexion);

	foreach ($row_rs_tipo_asien as $row) 
	{
		$descripcion = $descripcion.$row['Tia_Des'].'*';
		$array_asien[] = $row['Tia_Des'];
	}

	$pag1= $_SERVER['PHP_SELF']."?op=1&hdd_save&Pec_Cod=".$Pec_Cod;
	$pag2= $_SERVER['PHP_SELF']."?op=2&hdd_save&Pec_Cod=".$Pec_Cod;
	$pag3= $_SERVER['PHP_SELF']."?op=3&hdd_save&Pec_Cod=".$Pec_Cod;
	$pag4= $_SERVER['PHP_SELF']."?op=4&hdd_save&Pec_Cod=".$Pec_Cod;
	tabs(4,$descripcion.'Totales', $pag1.'*'.$pag2.'*'.$pag3.'*'.$pag4, $op); ?>
<div id="ContTabul">
<?Php	
if (($op==1 || $op==2 || $op==3)) 
{
		switch($op) {
		case 1: $etiqueta="Buscar Comprobante de ".$array_asien[0]." por: "; 
		break;
		case 2:	$etiqueta="Buscar Comprobante de ".$array_asien[1]." por: "; 
		break; 
		case 3:	$etiqueta="Buscar Comprobante de ".$array_asien[2]." por: "; 
		break; 
		} ?>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name= "form1">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2"><?php echo $etiqueta; ?></label>
	</LEGEND>
	<table width="495" height="27" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="178" height="23" class="Etiqueta1"><div align="left">
              <input name="op_opciones" type="radio" value="d" checked onClick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)">
          Apellidos</div></td>
        <td width="225" class="Etiqueta1"><div align="left">
              <input type="radio" name="op_opciones" value="r" onClick="document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)">
          No. de Comprobante </div></td>
        <td width="249" class="Etiqueta1"> <div align="left">Mes:&nbsp;
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
          </select>
        </div></td>
      </tr>
    </table>
	<table width="595" height="38" border="0" cellpadding="0" cellspacing="0">
		<tr>
		  <td width="111" height="28" class="BarraBusqueda"><div align="right">Busqueda:</div></td>
		  <td width="484" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="50" maxlength="50" onKeyUp="parametro_injection(this)">
			<input name="op" type="hidden" value="<?php echo $op; ?>" >
			<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>"> 
			<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">					
			
			<button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>
		  </td>
		</tr>
	  </table>
	</FIELDSET>
</form>   
	<?php 
	if(isset($txt_busqueda))
	{ ?>	
		<FIELDSET>
		<LEGEND>
			<label class="Titulos2">Resultados de la busqueda</label>
		</LEGEND>
	<table width="100%" border="0" cellpadding="1" cellspacing="0">
    <thead>
        <tr class="Cabecera1">
          <th width="4%">No. Int </th>
          <th width="4%">Tipo</th>
          <th width="4%">No. Compr </th>
          <th width="15%">C&eacute;dula/R.U.C.</th>
          <th width="40%">Proveedor/Cliente</th>
          <th width="15%">Fecha</th>
		  <th width="10%">Valor</th>
		  <th width="4%">&nbsp;</th>
		  <th width="4%">&nbsp;</th> 
        </tr>
     </thead>
     <tbody>	    
		<?php 			
	if (count($row_rs_cabcomp) > 0) 
	{	  		
		$i=0;
		foreach ($row_rs_cabcomp as $row)
		{ 
		$i++;
			 if($row['Com_Est']=='I')
	  		 { $rojo='#FF0000'; $anulada++; }else{$rojo='';}			 					
		?>
 	    <form name="form3" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <?php 
			/* Propiedad Codigo del comprobante */
			$com_codigo = $row['Com_Cod']; 
		?>
		<tr class="Fondo">
		  <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Com_Cod']; ?></font></td>		  
		  <td align="center"><font color="<?php echo $rojo; ?>"><?Php 
		  /* Control para mostrar si el comprobante es automatico o manual */		  
		  if ($row['Com_Gen']=='A')
		  {
		  		echo "Autom&aacute;tico";
		  }//Fin del if ($row_rs_compr_auto > 0)
		  else
		  {
		  		echo "Manual";
		  }//Fin del else if ($row_det_rs_ccpp_p > 0)
		  ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>">&nbsp;<?php 
  	  	list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
		  echo $mes.'-'.$row['Com_Num']; ?></font></td>
		  <td><font color="<?php echo $rojo; ?>"><?php echo $row['Prs_Ced']; ?>&nbsp;</font></td>
		  <td><font color="<?php echo $rojo; ?>">
		    <?Php echo marcar_cadena($_POST['txt_busqueda'], $row['Prs_Ape']." ".$row['Prs_Nom'], '#FFFF00', 1); ?></font></td>
		  <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row['Com_Fec']; ?></font></td>			
		  <td align="right"><font color="<?php echo $rojo; ?>">&nbsp;<?php echo $row['Com_Val']; ?></font></td>
		  <td align="center"><button type="button" class="btn btn-success btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<?php echo $row['Com_Cod'];?>&Ses_Emp_Cod=<?php echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
		  <td align="center">
		    <input name="op" id="op" type="hidden"  value="<?php echo $op; ?>">
		    <input name="codigo" id="codigo" type="hidden"  value="<?php echo $row['Com_Cod']; ?>">
		    <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
		    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
		    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>"> 
		    <input name="hdd_save" id="hdd_save" type="hidden" value="">			
		    <input name="volver_txt_busqueda" value="<?php echo $txt_busqueda;?>" type="hidden">
		    <input name="volver_op_opciones" value="<?php echo $op_opciones;?>" type="hidden">
		    <input name="volver_cmb_mes" value="<?php echo $cmb_mes;?>" type="hidden">	        
		    <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="this.form.submit()">
		      <i class="icon-arrow-right icon-white"></i>
		      </button>	      </td>
		  </tr>
		</form>
        <?php
	  	} // Fin del foreach
  		}//Fin del //Fin del if ($total_rs_cabcomp > 0)
		else
		{ ?>
		  <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?Php echo error_alerta(" No hay resultados que mostrar", 1); ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
		<?Php		
		}//Fin del else //Fin del if ($total_rs_cabcomp > 0)	  
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
    	<?php require_once('../../componentes/FRONT/com_con_leyenda.php');?>  
		<?php  
		/* 
		* Control para ocultar el detalle de las filas 
		*/
		if(count($row_rs_cabcomp) != 0)
		{
			ocultarDetalle(count($row_rs_cabcomp));
		}
	}//Fin del if(isset($txt_busqueda)) 

  if ($codigo > 0 && count($row_rs_codcompr)>0) 
  { ?>
	<br>   
<FIELDSET>
<LEGEND>
<label class="Titulos2">Resultados de la busqueda </label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="109">&nbsp;</td>
    <td width="359">&nbsp;</td>
    <td width="52">&nbsp;</td>
    <td width="174">&nbsp;</td>
  </tr>
  <tr>
    <td class="Etiqueta1">No. Compr: </td>
    <td class="LetraNegra"><div id="div_codigo">&nbsp;
      <?php echo $row_rs_codcompr['Com_Num']; ?>
    </div></td>
    <td class="Etiqueta1">Fecha:</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_codcompr['Com_Fec']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Proveedor/Cliente:</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_codcompr['Prs_Ape'].' '.$row_rs_codcompr['Prs_Nom']; ?></td>
    <td class="Etiqueta1">Valor:</td>
    <td class="LetraNegra">&nbsp;<?php echo $row_rs_codcompr['Com_Val']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Concepto:</td>
    <td colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_codcompr['Com_Con']; ?></td>
  </tr>
  <tr>
    <td class="Etiqueta1">Observaci&oacute;n:</td>
    <td colspan="3" rowspan="2" valign="top" class="LetraNegra">&nbsp;<?php echo $row_rs_codcompr['Com_Obs']; ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
  <thead >
    <tr>
      <th width="3%">&nbsp;</th>
      <th width="11%">C&oacute;digo</th>
      <th width="18%">Cuenta</th>
      <th width="41%">Glosa</th>
      <th width="12%">Debe</th>
      <th width="10%">Haber</th>
      </tr>
  </thead>
  <tbody id="c_contenido">
    <?php if (count($row_rs_cuentas) > 0)
	{ 
		foreach ($row_rs_cuentas as $row) 
		{ 
			$fila++;
	?>
    <tr>
      <td><?php echo $row['Asi_Cod']; ?></td>
      <td><?php echo $row['Pld_Cdc']; ?></td>
      <td><?php echo $row['Pld_Des']; ?></td>
      <td><?php echo $row['Asi_Glo']; ?></td>
      <td align="right"><?php if ($row['Asi_Deh']=='D') { 
	  		$sum_debe = $sum_debe + $row['Asi_Val'];
	  		 echo $row['Asi_Val']; } ?></td>
      <td align="right"><?php if ($row['Asi_Deh']=='H') { 
	  		$sum_haber = $sum_haber + $row['Asi_Val'];
			echo $row['Asi_Val']; } ?></td>
      </tr>
    <?php } //Fin del foreach
	 } //Fin del if
	 ?>
  </tbody>
  <tr>
    <td >&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td><strong>TOTALES</strong></td>
    <td><div align="right">
      <?Php echo formato_numero($sum_debe,2,3); ?>
    </div></td>
    <td><div align="right">
      <?php echo formato_numero($sum_haber,2,3); ?>
    </div></td>
    </tr>
</table>
</FIELDSET>
<br>
	<table width="300" border="0" cellpadding="0" cellspacing="0">
    	  <tr>
    	    <td width="101">
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name= "form2"> 
            <button type="button" class="btn btn-inverse fileinput-button" title="Atras" onClick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*op*hdd_volver', '<?php echo $volver_txt_busqueda.'*'.$volver_op_opciones.'*'.$volver_cmb_mes.'*'.$Pec_Cod.'*'.$op.'*1';?>')">
               <i class=" icon-arrow-left icon-white"></i>
               <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span>
       		 </button>
             </form>
            </td>
			<td width="199">
			<?php if ($row_rs_codcompr['Com_Est']=='A') 
			{ ?>
            <form action="<?Php echo $hdd_reportes; ?>" method="post" name= "form2" target="_blank"> 
				<button type="button" class="btn btn-primary start" title="Imprimir Comprobante" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
                <input name="Com_Cod" type="hidden" value="<?php echo $row_rs_codcompr['Com_Cod']; ?>">
		  <input name="codigo" type="hidden" value="<?php echo $row_rs_codcompr['Com_Cod']; ?>">
		  <input name="tabla" type="hidden" value="<?php echo $tabla; ?>">
		  <input name="tipo" type="hidden" value="<?php echo $op; ?>">
		  <input name="campo" type="hidden" value="<?php echo $campo; ?>">
  		  <input name="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">				
          </form>
			<?Php 
			} 
			else 
			{ 
				echo error_alerta (" Comprobante ANULADO", 2); 
			} ?>
            </td>			
		</tr>			
      </table>
<?Php 
  }
} //Fin del if (($op==1 || $op==2 || $op==3)) {
else 
{ ?>
	<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" name="form5"> 
	<table width="98%" border="0">
	<tr>
	<td width="453" valign="top">
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Buscar por:</label>
	</LEGEND>
	<table width="449" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="98"><label>          
            <input name="option" type="radio" value="A" checked="checked">
         	<span align="left" class="Etiqueta1">   Activos</span>
        	</label>
        </td>
        <td width="109"><label>
          <input name="option" type="radio" value="I">
          <span class="Etiqueta1">Anulados</span></label>
        </td>
        <td width="75"><label class="Etiqueta1">Generaci&oacute;n:</label></td>
        <td width="167"><select name="Com_Aut" id="Com_Aut">
          <option <?php if ($Com_Aut == "T"){ echo "selected"; } ?> value="T"><< TODOS >></option>
          <option <?php if ($Com_Aut == "M"){ echo "selected"; } ?> value="M">Manual</option>
 	      <option <?php if ($Com_Aut == "A"){ echo "selected"; } ?> value="A">Automáticos</option>
        </select>
        </td>
      </tr>
    </table>
	<table width="450" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
      <tr>
        <td width="115" class="Etiqueta1">Tipo de Comprobante:</td>
        <td width="322">
          <select name="Com_Tip" id="Com_Tip">
            <?Php
			$tip = explode("*", $Com_Tip);
			foreach ($row_rs_tipo_asien as $row)
			{
			?>
        	    <option <?Php if ($row['Tia_Cod'] == $tip[0]){ echo "selected"; } ?> value=<?Php if ($row['Tia_Cod'] == 2 || $row['Tia_Cod'] == 3){ ?>
										"<?Php echo $row['Tia_Cod']; ?>*proveedore*Prv_Cod" <?Php }
								else { ?>
										"<?Php echo $row['Tia_Cod']; ?>*cliente*Cli_Cod" <?Php } ?>> <?Php echo $row['Tia_Des'] ?>
                </option>
            <?Php
			} ?>
          </select></td>
        <td width="13"></td>
        </tr>
    </table>
	</FIELDSET>	</td>
	<td width="585">
	<FIELDSET>
    <LEGEND>
    <label class="Titulos2">Opciones de presentación</label>
    </LEGEND>
    <table width="342" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
      <tr>
        <td colspan="6">
          <input name="Todos" type="checkbox" id="Todos" value="checkbox" onClick="todo_check(this.form, 2,this, 'Niv_Cod')">Todos 
          <hr></td>
      </tr>
      <tr>
        <td width="46%"><label><input name="Niv_Cod[1]" type="checkbox" id="Niv_Cod[1]" value="C">                
				No Int. Compr. </label>
        </td>
        <td width="54%"><label>
                  <input name="Niv_Cod[2]" type="checkbox" id="Niv_Cod[2]" value="I">Concepto</label>
        </td>
       </tr>
     </table>
	</FIELDSET>	
  	</td>
	</tr>
	</table>
	<table width="541" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="406">
        <input type="hidden" id="hdd_ann" value="<?php echo $row_rs_periodo['Ann']; ?>" />
		<?Php 
		list($ann, $mes, $dia) = preg_split('![/.-]!', date("Y/m/d"));
		/* 
		* Fecha de inicio del componente 
		*/
		$Pec_Fei = $row_rs_periodo['Ann'].'-'.$mes.'-'.$dia;
		require_once("../../componentes/FRONT/com_con_mes_fecha.php"); ?></td>
        <td width="135"><div align="center">
            <input name="op" type="hidden" value="<?php echo $op; ?>">
			<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>"> 
			<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">	
								
			<button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="this.form.submit()">
                    <i class="icon-search icon-white"></i>
                    <span>Buscar</span>
        </button>
          	<input name="hdd_save" id="hdd_save" type="hidden" value="">
          	<input name="hdd" type="hidden" id="hdd">
        </div></td>
      </tr>
    </table>
    </form>
    <br>
    <?php 
	/* 
	* En caso de ser una busqueda por fechas 
	*/
	if (isset($txt_fec_ini)) 
	{ ?>
    <div id="Exportar_a_Excel">
	<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Resultados de la busqueda</label>
	</LEGEND>
      <table width="395" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="87" class="Etiqueta1">Desde:</td>
            <td width="80" class="LetraNegra"><input type="hidden" id="txt_fec_ini" name="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>"><?Php echo $txt_fec_ini?></td>
            <td width="37" class="Etiqueta1">Hasta:</td>
            <td width="191" class="LetraNegra"><input type="hidden" name="txt_fec_fin" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">&nbsp;<?Php echo $txt_fec_fin?></td>
          </tr>
      </table>
         <table width="393" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="88" class="Etiqueta1">Comprobantes: 
            </td>
            <td width="305" class="LetraNegra">&nbsp;<?php  if ($option == "A"){ echo "Activos"; } else { echo "Anulados"; } ?></td>
            </tr>
        </table>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
          <tr>
            <?Php if(isset($Niv_Cod[1])){ ?><th width="8%">No. Int </th><?Php } ?>
            <th width="8%">Tipo</th>            
            <th width="8%">No. Compr </th>
            <th width="12%">Fecha</th>
            <th width="30%">Proveedor/Cliente</th>
            <?Php if(isset($Niv_Cod[2])){ ?><th width="21%">Concepto</th><?Php } ?>
            <th width="11%">Valor</th>
            <th width="2%">&nbsp;</th>
          </tr>
    </thead>
    <tbody>
    <?php 
	if (count($row_rs_comfec) > 0)
	{ 
		$i=0;
		$total_fin = 0;	
		//$cont_compr = 0;
		foreach ($row_rs_comfec as $row)
		{ 	
			$i++;
			/* Propiedad Codigo del comprobante */
			$com_codigo = $row['Com_Cod'];				
	?>
          <tr>
            <?Php if(isset($Niv_Cod[1])){ ?><td align="center"><?php echo $row['Com_Cod']; ?></td><?Php } ?>
            <td align="center">
              <?Php 
		  /* 
		  * Control para mostrar si el comprobante es automatico o manual 
		  */	
		  if ($row['Com_Gen'] == 'A')
		  {
		  		echo "Autom&aacute;tico";
		  }
		  else
		  {
		  		echo "Manual";
		  }
		  ?>
              </td>            
            <td align="center"><?php 
			list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
			echo $mes.'-'.$row['Com_Num']; ?></td>
            <td align="center"><?php echo $row['Com_Fec']; ?></td>
            <td><?php echo $row['Prs_Ape'].' '.$row['Prs_Nom']; ?></td>
            <?Php if(isset($Niv_Cod[2])){ ?><td><?php echo $row['Com_Con']; ?></td><?Php } ?>
            <td align="right"><?php echo $row['Com_Val']; ?></td>
            <td align="right"><button type="button" class="btn btn-success btn-mini" title="Detalle del registro" onClick="Muestra_Aparecer(); ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_info=1&com_codigo=<?php echo $row['Com_Cod'];?>&Ses_Emp_Cod=<?php echo $Ses_Emp_Cod;?>','mostrar')"><i class="icon-info-sign icon-white"></i></button></td>
          </tr>
          <?php  $total_fin = $total_fin + $row['Com_Val'];
		}//Fin del if ($mostrar == $Com_Aut)
	 }//Fin del if ($total_rs_comfec >0)
	else
	{ ?>
          <tr>
            <td>&nbsp;</td>
             <?Php if(isset($Niv_Cod[1])){ ?><td>&nbsp;</td><?php } ?>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
            <?Php if(isset($Niv_Cod[2])){ ?><td>&nbsp;</td><?php } ?>
            <td colspan="2">&nbsp;</td>
            </tr>	
          <?php	
	}//Fin del if ($total_rs_comfec >0)
	?>
    </tbody>
	  </table> 
   <?php    
	echo barra_estado(count($row_rs_comfec));
		
	if (count($row_rs_comfec) >0)
	{ 	?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="85%" align="right"><strong>TOTAL:</strong> </td>
            <td width="8%" align="right"><strong><?PHP echo formato_numero($total_fin,2,2); ?></strong></td>
            <td width="7%" align="right">&nbsp;&nbsp;</td>
          </tr>
        </table>
	</FIELDSET>	
    </div>
    <br />	
    <?Php 
	if (count($row_rs_comfec) > 0)
	{ ?>		
		<table width="263" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td width="42%" align="center" >
                <form name='formulario' method='post' action='con_pri_comprb_1.0.php' target='_blank' >
			   <input name="option" type="hidden" value="<?php echo $option; ?>">
			   <input name="Com_Tip" id="Com_tip" type="hidden" value="<?Php  echo $Com_Tip; ?>"> 
			<input name="txt_fec_ini" id="txt_fec_ini" type="hidden" value="<?php echo $txt_fec_ini; ?>">
          	<input name="txt_fec_fin" id="txt_fec_fin" type="hidden" value="<?php echo $txt_fec_fin; ?>">								
          	<input name="hdd_save" id="hdd_save" type="hidden" value="">
          	
			<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
          	<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">	
			
			 <input name="op" type="hidden" value="<?php echo $op; ?>" >
			<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
		<input name="Niv_Cod" id="Niv_Cod" type="hidden" value="<?Php  echo $Niv_Cod; ?>">
			<input name="hdd_save" id="hdd_save" type="hidden" value="">
		    <button type="button" class="btn btn-primary start" title="Imprimir Comprobantes" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>               
            </form>  
            </td>
                <td width="58%" align="center" ><!--<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
  	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
  	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
           <i class=" icon-share icon-white"></i>
           <span>Excel</span>
	</button>
	</form>!--></td>
              </tr>
        </table>
 		  	
<?php	
	}//FIn del if (count($row_rs_comfec) > 0)
}//Fin del if ($total_rs_comfec >0)
		/* 
		* Control para ocultar el detalle de las filas 
		*/		
		if(count($row_rs_comfec) != 0)
		{			
			ocultarDetalle(count($row_rs_comfec));
		}

	} //Fin del else if (($op==1 || $op==2 || $op==3)) {	?>
	</form>
  <?Php      
} ?>
</div>
<?Php
		} //Fin del ELSE if (!isset($hdd_save) && !isset($txt_busqueda))  ?>
	</td>
  </tr>
</table>	  
</div>

<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal();"></div>
    <div id="bgmodal"  class="bgmodal" style="display:none" >
       <div id="ajax_modal">
        	 <div id="mostrar">
             
             </div>
       </div>
</div>
</div>
<script type="text/javascript" src="../VALIDACIONES/con_par_compr.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY></HTML><?Php 
$obBD_conexion->cerrar();
?>