<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* @abstract Permite consultar los cheques 
* @author Lewis Chimarro
* @version 1.0
* Fecha de creaciï¿½n  2012-07-19
* Fecha de actualizaciï¿½n  2012-07-25
* @author Lewis Chimarro
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/tes_log_cheque.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');	
/**
* Creacion del Objeto de conexion
*/
$obBD_conexion = new Class_Log_Conexion_Che($Ses_Dat_Dis);
/** 
* Creacion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Che;
$hoy = date("Y-m-d");
$mes = date("m");	
if(isset($ajax)){
        $com_codigo = $ComCod;
	include('../COMPONENTES/con_con_detalleCompr.php'); 
        exit();
}
/**
* Permite inicializar la variable OP por primera y unica vez 
*/
if (!(isset($op)))
{
	$op = 1;
}	    
/**
* Consulta para la eleccion del periodo contable
*/
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($codigo))
{
	/**
	* Carga el periodos contable actual 
	*/
	$row_rs_periodos = $obBD_con1->getArrayConsulta(214, $Ses_Emp_Cod, $obBD_conexion);
}//Fin del if ($Pec_Cod)
else
{
	/**
	* OPCIONES 
	*/
	switch ($op){
	  case 1: 		
		if (isset($txt_busqueda))
		{
//			if ($op_opciones == "d")
//			{	
//				$rs_cabcompr = $obBD_con1->getArrayConsulta(312,'proveedore'.'*'.trim($txt_busqueda).
//								'*'.'D'.'*'.$Pec_Cod.'*'.'Prv_Cod'.'*'.$cmb_me, $obBD_conexion);
//			}//Fin del if ($op_opciones == "d")
//			else 
//			{			
//				/**
//				*  Control para busqueda mensual  
//				*/
//				$mes_array = explode('-', $txt_busqueda);
//				if (count($mes_array)==2)
//				{
//					$Par_Fec = "AND MONTH(Com_Fec)=$mes_array[0]";
//					$txt_busqueda = $mes_array[1];//$mes[1] es el numero del comprobante
//				}
//				$rs_cabcompr = $obBD_con1->getArrayConsulta(313,'proveedore'.'*'.trim($txt_busqueda).
//								'*'.'D'.'*'.$Pec_Cod.'*'.'Prv_Cod'.'*'.$Par_Fec, $obBD_conexion);
//			}//Fin del else if ($op_opciones == "d")
            $rs_cabcompr = $obBD_con1->getArrayConsulta(345,trim($txt_busqueda).'*'.$Ses_Emp_Cod.'*'.$Pec_Cod.'*'.$op_opciones.'*'.$bancos, $obBD_conexion);
			$row_rs_cabcompr = $rs_cabcompr;
			$total_rs_cabcompr = count($rs_cabcompr);
		}//Fin del if (isset($txt_busqueda))
		else
		{
			if (isset($codigo))
			{
				/**
				* Consulta los datos del comprobante 
				*/
				$rs_cabcomp = $obBD_con1->consulta(sentencias_che(149,$obBD_con1->parametros('proveedore'.'*'.$codigo.'*'.'D'.'*'.
										$Pec_Cod.'*'.'Prv_Cod')), $obBD_conexion->conexion);
				$row_rs_cabcomp = $obBD_con1->registros();
				$total_rs_cabcomp = $obBD_con1->numregistros();		
				/**
				* Cargado de los cheques segï¿½n el nï¿½mero de comprobante de egreso
				*/
				$rs_concomp = $obBD_con1->consulta(sentencias_che(143,$obBD_con1->parametros($codigo)), 
						$obBD_conexion->conexion);
				$row_rs_concomp = $obBD_con1->registros();
				$total_rs_concomp = $obBD_con1->numregistros();
			}//Fin del if (isset($codigo))
		}//FIn del else if (trim($txt_busqueda) !="") 
		break;
		case 2:
		if ($ini != "")
		{
			/**
			* Consulta del detalle de la mayorizacion 
			*/
			//$rs_tot_cheques = $obBD_con1->consulta(sentencias_che(163, $obBD_con1->parametros($ini.'*'.$fin.'*'.$opt_option.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);
			$rs_tot_cheques = $obBD_con1->consulta(sentencias_che(375, $obBD_con1->parametros($ini.'*'.$fin.'*'.$opt_option.'*'.$Ses_Emp_Cod.'*'.$opt_est)),$obBD_conexion->conexion);
			$row_rs_tot_cheques = $obBD_con1->registros();
			$total_rs_tot_cheques = $obBD_con1->numregistros();		
		}
		break;       
		}//FIn del case $op	
	/**
	* Divide la cadena del periodo contable 
	*/
	$arreglo = explode("*",$Pec_Cod); 		
	$Pec_Cod = $arreglo[0];
	/**
	* Consulta del periodo contable 
	*/
	$rs_periodo = $obBD_con1->consulta(sentencias_che(113, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
	$row_rs_periodo = $obBD_con1->registros();
	$total_rs_periodo = $obBD_con1->numregistros();
	/**
	* Descripcion del periodo contable 
	*/
	$periodo = "en el periodo contable ".substr($row_rs_periodo['Pec_Fei'], 0,4);				
}//Fin del if (!isset($hdd_save))		
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
		<?Php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../VALIDACIONES/tes_val_cheque.js"></script>
                <link rel="stylesheet" type="text/css" href="../../Librerias/jquery/modal/css/modal.css" />
   		<script type="text/javascript" src="../../Librerias/exportar/jquery-1.3.2.min.js"></script>
                <script type="text/javascript" src="../../Librerias/jquery/modal/js/modal.js"></script>
	    <script type="text/javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
			});
			});
		</script>                
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
  	    <script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() {
                $('#set1 *').tooltip({showURL: false});
          });              			
		</script>		
		</script>
   		<!--Librerias para calendario -->       
    	<script type="text/javascript" src="../../Librerias/validaciones/interfaz.datepicker.js"></script>    
        <script type="text/javascript" src="../../Librerias/masked/jquery.maskedinput-1.2.2.js"></script>		
    	<script>
		$(function() { 
			$( "#ini" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});	
			$( "#fin" ).datepicker({
				changeMonth: true, changeYear: true,
				/* Permite asignar una imagen */
				/*showOn: "button", buttonImage: imagen, buttonImageOnly: true,*/ dateFormat: "yy-mm-dd"});	
		}); 		
    	</script>   
    	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	</HEAD>		
<BODY>
<div id="set1">
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
	<tr class="BarraTitulo">
	  <td height="10">&raquo; Consultar cheques <?Php echo $periodo; ?></td>
  </tr>
	<tr>
            <td align="left" valign="top" height="400">	  
<?php 
if (!isset($hdd_save) && !isset($hdd_save2) && !isset($codigo)) 
{ ?>
	<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name= "form1">
	<FIELDSET>
	<LEGEND>
		<label class="Titulos2">Selecci&#243;n Periodo Contable</label>
	</LEGEND>
	<table width="280" border="0" cellspacing="0" cellpadding="0" >
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
	    <td width="84" align="center"><button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
	      <input name="hdd_save" type="hidden" id="hdd_save" /></td>
	    </tr>
	  </table>
	</FIELDSET>
	</form>			 	  	  
<?php 
}//Fin del if (!isset($hdd_save))      
else
{
	if (isset($hdd_save) || isset($txt_busqueda) || isset($ini))
	{
		$pag1= $_SERVER['PHP_SELF']."?op=1&Pec_Cod=".$Pec_Cod."&hdd_save";
		$pag2= $_SERVER['PHP_SELF']."?op=2&Pec_Cod=".$Pec_Cod."&hdd_save";
		tabs(2,'Individual*Totales', $pag1.'*'.$pag2, $op);
		?>		
		<div id="ContTabul">
		<form action="<?Php echo $_SERVER['PHP_SELF']?>" method="post" name= "form1">				
      	<?php 
		  switch ($op) 
		  {
			case 1: ?>      
            <FIELDSET>
            <LEGEND>
            <label class="Titulos2">Buscar cheques por: </label>
            </LEGEND>
            <table width="605" height="27" border="0">
              <tr>
                <td width="124" height="23" class="LetraNegra">
                <input name="op_opciones" type="radio" value="d"  <?php if(!isset($op_opciones)) {echo "checked";} if($op_opciones=="d") {echo "checked";} ?>  onClick="document.getElementById('cmb_mes').disabled=false; setfocus(this.form.txt_busqueda)">
                  Apellidos</td>
                <td width="167" class="LetraNegra"><input type="radio" name="op_opciones" value="n"  <?php if($op_opciones=="n") {echo "checked";} ?>  onclick=                  "document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)" />
No. de Cheque
               </td>
                <td width="300" class="LetraNegra"> <input type="radio" name="op_opciones" value="r"  <?php if($op_opciones=="r") {echo "checked";} ?>  onClick=                  "document.getElementById('cmb_mes').disabled=true; setfocus(this.form.txt_busqueda)">
                  No. de Comprobante</td>
              </tr>
              <tr>
                <td>Seleccione Banco:&nbsp;</td>
                <td colspan="2" class="LetraNegra"> 
                    <select style="display: none;" name="cmb_mes" id="cmb_mes" <?php if($op_opciones=="r") {echo "disabled";} ?>>
                      <option value=""><< TODOS >></option>
                      <?Php
                          for ($i=1;$i<=12;$i++)
                          { ?>
                      <option <?php if ($cmb_mes == ("AND MONTH(Com_Fec)=".$i)){ echo "selected"; } ?><?php //if ($i == $mes){ echo "selected"; } ?>  value="<?Php echo "AND MONTH(Com_Fec)=$i"; ?>"><?php 
                                    echo mes($i, 1) ?></option>
                      <?Php
                          } ?>
                  </select><select name="bancos" id="bancos" style=" width:300px">
                    <option value=""><< TODOS >></option>
<?php
    $rs_bancos = $obBD_con1->getArrayConsulta(339,$Ses_Emp_Cod, $obBD_conexion);
    if (count($rs_bancos) > 0) 
    { 
        foreach ($rs_bancos as $row){  
?>
                                  <option  <?php if($bancos==$row['Pld_Cod']) {echo "selected";} ?> value="<?php echo $row['Pld_Cod']; ?>"><?php echo $row['Pld_Des']." (Cta.#: ".$row['Ban_Cue'].")"; ?></option>
<?php
        }
    }
?>
                              </select></td>
                </tr>
            </table>
            <table width="570" height="36" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="77" height="28" class="BarraBusqueda"><div align="right">Busqueda:</div></td>
                  <td width="353" class="BarraBusqueda"><input name="txt_busqueda" type="text" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>" size="50" 
                                    maxlength="50"></td>
                  <td width="140"><div align="center">
                    <input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
                    <input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
                    <input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
                    <input name="hdd_save2" type="hidden" id="hdd_save2">		  
                    <input name="op" type="hidden" value="1" >
                    <button type="button" class="btn btn-success" title="Buscar" onclick="validar_requeridos(this.form, 'txt_busqueda', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>                
                  </div></td>
                </tr>
            </table>
            </FIELDSET>
			<?Php		
			break;	
			case 2: ?>
			<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Buscar por fechas:</label>
			</LEGEND>
			<table width="449" border="0" class="LetraNegra" >
			  <tr>
				<td width="96"><label>
				  <input name="opt_option" type="radio" value="A" checked="checked" onClick="document.getElementById('opt_est').value='A'">
				  Emitidos</label></td>
				<td width="107"><label>
				  <input name="opt_option" type="radio" value="I" onClick="document.getElementById('opt_est').value='I'">
				  Anulados</label></td>
				<td width="96">&nbsp;</td>
				<td width="132">&nbsp;<input type="hidden" id="opt_est" name="opt_est" value="A" /> </td>
			  </tr>
			</table>
			<table border="0" cellpadding="0" cellspacing="0">
			  <tr height="36">
				<td width="79" class="BarraBusqueda"><div align="right">Desde: </div></td>
				<td width="133" class="BarraBusqueda"><input name="ini" type="text" id="ini" value="<?php if (isset($ini)){ echo $ini; }else{ echo date("Y-m-d"); } ?>" 
						size="10" onBlur="validar_fecha2(this)" onKeyUp="mascara(this,'-',patron,true);"></td>
				<td width="54" class="BarraBusqueda"><div align="right">Hasta: </div></td>
				<td width="144" class="BarraBusqueda"><input name="fin" type="text" id="fin" value="<?php if (isset($ini)){ echo $fin; }else{ echo date("Y-m-d"); }?>"  size="10" onBlur="validar_fecha2(this)" onKeyUp="mascara(this,'-',patron,true);">
				</td>
				<td width="113"><div align="center">
					<input name="op" type="hidden" value="<?php echo $op; ?>" >
					<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">
					<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
					<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">				
					<button type="button" class="btn btn-success" title="Buscar" onclick="this.form.submit()"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
					<input name="hdd_save2" type="hidden" id="hdd_save2">
				</div></td>
			  </tr>
			</table>
			</FIELDSET>
			<?Php
			break;
		  }//Fin switch ($op) { ?>
		</form>	
	<?Php
	 switch ($op) 
	 {
		case 1:
		if(isset($txt_busqueda))
		{
		  ?>	
		<FIELDSET>
			<LEGEND>
				<label class="Titulos2">Resultados de la busqueda</label>
			</LEGEND>
			<table width="100%" border="0" cellpadding="1" cellspacing="0" class="fixedHeader01">
			  <thead>
				<tr>				  
				  <th width="6%">Cod. Int.</th>
                                  <th width="5%">Num.</th>
				  <th width="9%">Generaci&oacute;n</th>
				  <th width="11%">No. Compr </th>
                                  <th width="10%">C&eacute;dula/R.U.C.</th>
				  <th width="25%">Proveedor</th>
				  <th width="8%">Fecha</th>
				  <th width="10%">Valor</th>
                                  <th width="5%">&nbsp;</th>
				  <th width="5%">&nbsp;</th>
				  </tr>
				</thead>
			  <tbody>
		<?php
		if ($total_rs_cabcompr > 0) 
		{		 
			$i=0; 				
			 foreach ($row_rs_cabcompr as $row) //do 
			{
				$i++;
				if($row['Com_Est']=='I')
				  { $rojo='#FF0000'; $anulada++; }else{$rojo='';} ?>
				<tr>
				  <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Cod']; ?></font></td>
                                  <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Che_Num']; ?></font></td>
				  <td align="center"><font color="<?php echo $rojo; ?>">
					<?Php  if ($row['Com_Gen'] == 'M') echo "Manual"; else echo "Autom&aacute;tico";
			  ?>
					</font></td>
				  <td align="center"><font color="<?php echo $rojo; ?>">
					<?php 
			list($ann, $mes, $dia) = preg_split('![/.-]!', $row['Com_Fec']);
			  echo $row['Tia_Abr'].'-'.$mes.'-'.$row['Com_Num']; ?>
					</font></td>
				  <td><font color="<?php echo $rojo;?>"><?php echo $row['Prs_Ced']; ?></font></td>
				  <td><font color="<?php echo $rojo;?>"><?php echo $row['Prs_Ape']." ".$row['Prs_Nom']; ?></font></td>
				  <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Fec']; ?></font></td>
				  <td align="right"><font color="<?php echo $rojo;?>"><?php echo $row['Com_Val']; ?></font></td>
                                  <td align="center">
                                      <button type="button" class="btn btn-info btn-mini" onclick="Muestra_Aparecer();ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax=true&ComCod=<?php echo $row['Com_Cod']; ?>','ajax_modal')"><i class="icon-info-sign icon-white"></i></button>
                                  </td>
                                  <td align="center"><?php if ($row['Com_Est']=='A') { ?>
					<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form2" id="form2">
					  <input name="codigo" id="codigo" type="hidden"  value="<?php echo $row['Com_Cod']; ?>" />
					  <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>" />
					  <input name="volver_busqueda" id="volver_busqueda" type="hidden"  value="<?php echo $txt_busqueda; ?>" />
                                          <input name="volver_bancos" id="volver_bancos" type="hidden"  value="<?php echo $bancos; ?>" />
					  <input name="volver_opciones" id="volver_opciones" type="hidden"  value="<?php echo $op_opciones; ?>" />
					  <input name="volver_mes" id="volver_mes" type="hidden"  value="<?php echo $cmb_mes; ?>" />
					  <input name="op" type="hidden" value="<?Php echo $op; ?>" >
					  <input name="hdd_save" type="hidden" id="hdd_save">	
					  <button type="button" class="btn btn-success btn-mini" title="Elegir" onclick="this.form.submit()"> <i class="icon-arrow-right icon-white"></i></button>
					  </form>
					<?php
				}else { echo "&nbsp;"; } ?>
                                  </td>
                                  <!--<td align="center"><img src="../../imagenes/edit_add.png" id="mas[<?php echo $i; ?>]" width="25" height="25" title="Ver detalle" style="cursor:pointer" onclick="mas_menos(1,'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)" /><img src="../../imagenes/edit_remove.png" id="menos[<?php echo $i; ?>]" width="25" title="Ocultar detalle" style="cursor:pointer" height="25" onclick="mas_menos(2, 'mas[<?php echo $i;?>]', 'menos[<?php echo $i;?>]', <?Php echo $i; ?>)" /></td>-->
				  </tr>
				<!--<tr id="detalle[<?Php echo $i; ?>]">
				  <?php
				/**
				* Propiedad Codigo del comprobante 
				*/
				$com_codigo = $row['Com_Cod']; ?>
				  <td align="center">&nbsp;</td>
				  <td colspan="7"><?Php //include('../../contabilidad/COMPONENTES/con_con_detalleCompr.php'); ?></td>
				  <td align="center">&nbsp;</td>
				  </tr>-->
				<?php	  		
			   }//while ($row_rs_cabcompr = $obBD_con1->fetch_assoc($rs_cabcompr));  ?>
				<?Php 
		}//Fin del if ($total_rs_cabcompr > 0)
		else
		{ ?>
				<tr>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>				  
				  <td ><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td>
				  <td>&nbsp;</td><td>&nbsp;</td>
				  </tr>
				<?php
		}//Fin del else if(isset($txt_busqueda))
		  ?>
				</tbody>
			  </table>
			<?php 
			echo barra_estado($total_rs_cabcompr);
		?>	  	  
		</FIELDSET>
			<?Php
		if ($anulada > 0)
			{		
				$com_leyenda[1]=$anulada;
			}//Fin del if ($anulada > 0)
			?>
			<br/>
		<?php
		require_once('../../componentes/FRONT/com_con_leyenda.php');?> 
	<?php }//FIn del if(isset($txt_busqueda))
	/**
	* Control parte de la opcion 1 
	*/
	if ($total_rs_cabcomp >0)
	{
		/**
		* Consulta las cuentas de un comprobante de egreso 
		*/
		$rs_cuentas = $obBD_con1->consulta(sentencias_che(306,$obBD_con1->parametros($codigo)),$obBD_conexion->conexion);
		$row_rs_cuentas = $obBD_con1->registros();
		$total_rs_cuentas = $obBD_con1->numregistros();
	?>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Datos del Comprobante</label>
		</LEGEND>	 
		<table width="100%" border="0">
		  <tr>
			<td width="15%" class="Etiqueta1"><input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
				<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
				<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
			  C&oacute;d. Compr: </td>
			<td width="38%" class="LetraNegra">&nbsp;<?php list($ann, $mes, $dia) = preg_split('![/.-]!', $row_rs_cabcomp['Com_Fec']);
		  echo $row_rs_cabcomp['Tia_Abr'].'-'.$mes.'-'.$row_rs_cabcomp['Com_Num']; ?></td>
			<td width="11%" class="Etiqueta1">Fecha:</td>
			<td width="36%" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Fec']; ?></td>
		  </tr>
		  <tr>
			<td class="Etiqueta1">Nombre:</td>
			<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
			<td class="Etiqueta1">Valor:</td>
			<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Val']; ?></td>
		  </tr>
		  <tr>
			<td class="Etiqueta1">Concepto:</td>
			<td colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Con']; ?></td>
		  </tr>
		  <tr>
			<td valign="top" class="Etiqueta1">Observaci&oacute;n:</td>
			<td colspan="3" valign="top" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Obs']; ?></td>
		  </tr>
		</table>
		</FIELDSET>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Cuentas</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
		  <thead>
			<tr>
			  <th width="10%">C&oacute;digo</th>
			  <th>Descripci&oacute;n</th>
			  <th>Glosa</th>
			  <th width="10%">Debe</th>
			  <th width="10%">Haber</th>
			  </tr>
			</thead>
		  <tbody>
			<?php 
		if ($total_rs_cuentas > 0)
		{ 
			do {   
		?>
			<tr>
			  <td><?php echo $row_rs_cuentas['Pld_Cdc']; ?></td>
			  <td><?php echo $row_rs_cuentas['Pld_Des']; ?></td>
			  <td><?php echo $row_rs_cuentas['Asi_Glo']; ?></td>
			  <td align="right"><?php if ($row_rs_cuentas['Asi_Deh']=='D') { echo $row_rs_cuentas['Asi_Val']; $total=$total + $row_rs_cuentas['Asi_Val']; } ?></td>
			  <td align="right"><?php if ($row_rs_cuentas['Asi_Deh']=='H') { echo $row_rs_cuentas['Asi_Val']; } ?></td>
			  </tr>
			<?php } while($row_rs_cuentas=$obBD_con1->fetch_assoc($rs_cuentas)); } ?>
			<tr>
			  <td class="LetraNegra">&nbsp;</td>
			  <td class="LetraNegra">&nbsp;</td>
			  <td class="LetraNegra"><strong>Totales</strong></td>
			  <td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
			  <td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
			  </tr>
			</tbody>
		  </table>
		</FIELDSET>
		<FIELDSET>
		<LEGEND>
		<label class="Titulos2">Cheques emitidos</label>
		</LEGEND>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
		  <thead>
			<tr>
			  <th width="29%" align="center">Proveedor</th>
			  <th width="18%" align="center">Banco</th>
			  <th width="11%" align="center">N&ordm; Ch.</th>
			  <th width="10%" align="center">Valor</th>
			  <th width="12%" align="center">Fecha</th>
			  <th width="4%" align="center">&nbsp;</th>
			  <th width="4%" align="center">&nbsp;</th>
			  <th width="4%" align="center">&nbsp;</th>
			  <th width="4%" align="center">&nbsp;</th>
			  <th width="4%" align="center">&nbsp;</th>
                          <th width="4%" align="center">&nbsp;</th>
			  </tr>
			</thead>
		  <tbody>
			<?php if($total_rs_concomp != 0)
			  {
		 do {
			if($row_rs_concomp['Che_Est']=='I')
			  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
			$cod = $row_rs_concomp['Che_Cod'];
			$asi = $row_rs_concomp['Asi_Cod'];
			$ban = $row_rs_concomp['Ban_Cod'];
			$pro = $row_rs_concomp['Prv_Cod'];
                        $ruta='.'.(file_exists ('cheques/'.$Ses_Emp_Cod)?'/cheques/'.$Ses_Emp_Cod:'');
		 ?>
			<tr>
			  <td><font color="<?php echo $rojo;?>"><?php echo $row_rs_concomp['Prs_Ape'].' '.$row_rs_concomp['Prs_Nom']; ?></font></td>
			  <td><font color="<?php echo $rojo;?>"><?php echo $row_rs_concomp['Pld_Des'];?>
				</font></td>
			  <td align="right"><font color="<?php echo $rojo;?>"><?php echo $row_rs_concomp['Che_Num']; ?></font></td>
			  <td align="right"><font color="<?php echo $rojo;?>"><?php echo "$".''.number_format($row_rs_concomp['Che_Val'],2,'.',''); ?></font></td>
			  <td align="center"><font color="<?php echo $rojo;?>"><?php echo $row_rs_concomp['Che_Fec']; ?></font></td>
			  <form action="<?php echo $ruta; ?>/tes_pri_cheque_mac_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>
                                  <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" title="Ver cheque Banco Machala" width="22" height="35" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>
                  </td>
				</form>
			  <form action="<?php echo $ruta; ?>/tes_pri_cheque_pac_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>                
                <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" title="Ver cheque Banco Pac&iacute;fico" width="24" height="23" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>                  
                  </td>
				</form>
			  <form action="<?php echo $ruta; ?>/tes_pri_cheque_rum_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>                                
                <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" title="Ver cheque Banco Rumi&ntilde;ahui" width="30" height="15" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>                                    
                  </td>
				</form>
			  <form action="<?php echo $ruta; ?>/tes_pri_cheque_gua_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>                                                
                <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" title="Ver cheque Banco Guayaquil" width="36" height="18" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>                                                      
                  </td>
				</form>
			  <form action="<?php echo $ruta; ?>/tes_pri_cheque_pch_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>                
                <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" title="Ver cheque Banco del Pichincha" width="36" height="30" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>                  
                  </td>
				</form>
                           <form action="<?php echo $ruta; ?>/tes_pri_cheque_int_1.0.php" method="post" name= "form3" target="_blank" id="form3">
				<td align="center">
                <?Php 
				if($row_rs_concomp['Che_Est']=='A')
				{
				?>                
                <input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>" />
				  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>" />
				  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>" />
				  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>" />
				  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/ban_int.jpg" title="Ver cheque Banco Internacional" width="36" height="30" />
                  <?Php
				}//fin del if($row_rs_carcheq['Che_Est']=='I')
				else
				{  echo "&nbsp;";	}
				  ?>                  
                  </td>
				</form>
			  </tr>
			<?php } while ($row_rs_concomp = $obBD_con1->fetch_assoc($rs_concomp)); 
	   } //FIn del if($total_rs_concomp != 0) 
	   else 
	   { ?>
			<tr>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td><?Php echo error_alerta("No hay resultados que mostrar", 2); ?></td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  <td>&nbsp;</td>
                          <td>&nbsp;</td>
			  <td>&nbsp;</td>
			  </tr>
	<?php } //Fin del else if($total_rs_concomp != 0) ?>
			</tbody>
		  </table>
		<?php 
			echo barra_estado($total_rs_concomp); ?>
      <?Php
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>	  	  	
		</FIELDSET>	
		<br />
		<table width="312" border="0" cellpadding="0" cellspacing="0">
		  <tr>
				<td width="110">
				<form action="<?Php  echo $_SERVER['PHP_SELF']?>" method="post" name= "form2">
				<button type="button" class="btn btn-inverse fileinput-button" title="Atrï¿½s" onclick="campos_hide(this.form, 'txt_busqueda*op_opciones*cmb_mes*Pec_Cod*hdd_volver*bancos', '<?php echo $volver_busqueda.'*'.$volver_opciones.'*'.$volver_mes.'*'.$Pec_Cod.'*1*'.$volver_bancos;?>')"> <i class=" icon-arrow-left icon-white"></i> <span>&nbsp;&nbsp;Atr&aacute;s&nbsp;&nbsp;</span></button>
				 <input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>" />
				  <input name="hdd_save" type="hidden" id="hdd_save" />
				  <input name="op" type="hidden" value="<?Php echo $op; ?>" >
				</form>
				</td>
			</tr>
		</table>
		<?Php
	} //if (isset($codigo))
		break;//Fin del case 1
		case 2:
		 if (isset($ini)) 
		 { ?>
			<br>
            <div id="Exportar_a_Excel">
			<table width="407" border="0" cellspacing="0" cellpadding="0">
			  <tr>
				<td width="47" class="LetraNegra"><strong>Desde:</strong></td>
				<td width="138" class="LetraNegra"><?php echo $ini;?></td>
				<td width="46" class="LetraNegra"><strong>Desde:</strong></td>
				<td width="176"><span class="LetraNegra"><?php echo $fin;?></span></td>
			  </tr>
			  <tr>
				<td colspan="4" class="LetraNegra"><strong>Cheques:</strong>&nbsp;
						<?Php if ($opt_option == 'A'){ echo "Emitidos"; }
							else { echo "Anulados";}	 ?>
				</td>
				</tr>
			</table>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader03" style="table-layout:fixed;">
		<thead>
		  <tr>
			<th width="7%">N&ordm; Compr </th>    
			<th width="23%">Proveedor</th>
			<th width="20%">Banco</th>
			<th width="7%">N&ordm; Ch.</th>
			<th width="7%">Fecha</th>
			<th width="18%">Concepto</th>
			<th width="7%">Valor</th>
		  </tr>
		</thead>
		<tbody>  
		<?php 
		if ($total_rs_tot_cheques) 
		{
			$total = 0;
			do { 
				if($row_rs_tot_cheques['Che_Est']=='I')
				  { $rojo='#FF0000'; $anulada++; }else{$rojo='';}
				$total = $total + $row_rs_tot_cheques['Che_Val'];
			?>
          <tr>
            <td align="left"><font color="<?php echo $rojo; ?>">
              <?php 
			list($ann, $mes, $dia) = preg_split('![/.-]!', $row_rs_tot_cheques['Com_Fec']);
			  echo $row_rs_tot_cheques['Tia_Abr'].'-'.$mes.'-'.$row_rs_tot_cheques['Com_Num']; ?>
            </font></td>
            <td title="<?php echo $row_rs_tot_cheques['Prs_Ape'].' '.$row_rs_tot_cheques['Prs_Nom']; ?>" style="white-space: nowrap; overflow: hidden;"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_tot_cheques['Prs_Ape'].' '.$row_rs_tot_cheques['Prs_Nom']; ?></font></td>
            <td><font color="<?php echo $rojo; ?>"><?php echo $row_rs_tot_cheques['Pld_Des']; ?></font></td>
            <td align="right"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_tot_cheques['Che_Num']; ?></font></td>
            <td align="center"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_tot_cheques['Che_Fec']; ?></font></td>
            <td width="21%" style="white-space: nowrap; overflow: hidden;" title="<?php echo $row_rs_tot_cheques['Com_Con']; ?>"><font color="<?php echo $rojo; ?>"><?php echo $row_rs_tot_cheques['Com_Con']; ?></font></td>
            <td align="right"><font color="<?php echo $rojo; ?>"><?php echo "$".''.number_format($row_rs_tot_cheques['Che_Val'],2,'.',''); ?></font></td>
          </tr>
		<?php 	} while ($row_rs_tot_cheques = $obBD_con1->fetch_assoc($rs_tot_cheques)); ?>
		</tbody>
		<tfoot>
		  <tr>
            <td colspan="6" class="TITULO_REPORTE"><div align="right"><strong>TOTAL</strong></div></td>
            <td class="TITULO_REPORTE" align="right"><div align="right"><?php echo formato_numero($total,2,2); ?></div>            
            </td>
		  </tr>
		</tfoot>  	
		<?php 
		}//Fin del if ($total_rs_tot_cheques) 
		else 
		{ ?>
			  <tr>
				<td>&nbsp;</td>
				<td align="center"><?Php echo error_alerta(" No hay resultados que mostrar", 1); ?></td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			  </tr>
	 <?php } ?>		  
		</table>
        </div>
		<?php 
		echo barra_estado($total_rs_tot_cheques);
    if ($anulada > 0)
        {		
            $com_leyenda[1]=$anulada;
        }//Fin del if ($anulada > 0)
        ?>
        <br/>
    <?php
    require_once('../../componentes/FRONT/com_con_leyenda.php');?>	  	  	
		<?Php
		if ($total_rs_tot_cheques>0) 
		{
		?>
		<br>
		<table cellpadding="0" cellspacing="0" border="0">		
			<tr>
				<td width="110"><form action="tes_pri_cheque_tot_1.0.php" method="post" name="form3" target="_blank">
				<input name="ini" id="ini" type="hidden" value="<?Php echo $ini; ?>">
				<input name="fin" id="fin" type="hidden" value="<?Php echo $fin; ?>">
				<input name="opt_option" id="opt_option" type="hidden" value="<?Php echo $opt_option; ?>">
				<input name="opt_est" id="opt_est" type="hidden" value="<?Php echo $opt_est; ?>">				
               <button type="button" class="btn btn-primary start" title="Imprimir listado de cheques Emitidos" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
				</form>
				</td>
				<td width="203">
                <form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
                    <input type="hidden" id="datos_a_enviar" name="datos_a_enviar">
                    <button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
                           <i class=" icon-share icon-white"></i>
                           <span>Excel</span>
                    </button>
                    </form>                
                </td>		
			</tr>
		</table>
		<?Php
		}//Fin del if ($total_rs_tot_cheques)
	  } //Fin del if (isset($ini)) 
	  break;//Fin del case 2
	 }//FIn del swtich ?> 
 	</div><!--Fin del div id="ContTabul"-->
     <?php   
	}//FIn else	if (!isset($hdd_save) && !isset($hdd_save2) && !isset($codigo)) 
}
	?>		
		</td>
  </tr>
</table>
<?Php
	/** 
	* Control para ocultar el detalle de las filas 
	*/
	if($total_rs_cabcompr != 0)
	{
		ocultarDetalle($total_rs_cabcompr);
	}
?>
</div>
    <div id="bgtransparent" class="bgtransparent" style="display:none" onclick="closeModal()">
    </div>
    <div id="bgmodal"  class="bgmodal"  style="display:none">  
     <div id="ajax_modal"></div>
    </div>
<script type="text/javascript" src="../VALIDACIONES/tes_par_cheque.js"></script>
<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>	
</BODY></HTML>
<?Php
@$obBD_conexion->cerrar();
@$obBD_con1->liberar();
?>