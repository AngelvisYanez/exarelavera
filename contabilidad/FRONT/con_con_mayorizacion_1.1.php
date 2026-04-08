<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Descripción: Permite consultar la mayorizacion contable
 * Fecha de actualización:	2010-11-15 
 * Desarrollador:	Lewis Chimarro 
 * Fecha de actualización:	2012-06-24
 * Desarrollador:	Lewis Chimarro 
 * Fecha de actualización:	2015-05-05
 * Desarrollador:	Lewis Chimarro 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_mayorizacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');


/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;
/* Permite inicializar la variable OP por primera y unica vez */
if (!(isset($op))) {
	$op = 1;
}

/**
 * Cuando se llega desde el balance con Pec_Cod2 ya armado, inicializar
 * las variables del período para evitar errores en los componentes.
 */
if (isset($Pec_Cod2) && $Pec_Cod2 != '') {
	$__arrPec = explode('~', $Pec_Cod2);
	$Pec_Cod = $__arrPec[0];
	$Pec_Fei = $__arrPec[1];
	$Pec_Fef = $__arrPec[2];
	$Pla_Cod = $__arrPec[3];
}


/* Cargado ajax de la busqueda de la cuenta */
if (isset($buscod)) {
	if ($name_input == "grupo") {
		$parametro = "AND det_plan.Pld_Tip = 'G'";
	} else {
		$parametro = "AND det_plan.Pld_Tip = 'D'";
	}

	if ($op_op == 'd') {
		/**
		 * Cargado de los resultados de la busqueda por descripcion de la cuenta
		 */
		$rs_buscar = $obBD_con1->getArrayConsulta(312, trim($buscod) . '*' . $Ses_Emp_Cod . '*' . $parametro . '*' . $Pla_Cod, $obBD_conexion);
	} elseif ($op_op == 'c') {
		/**
		 * Cargado de los resultados de la busqueda por codigo de la cuenta
		 */
		$rs_buscar = $obBD_con1->getArrayConsulta(313, trim($buscod) . '*' . $Ses_Emp_Cod . '*' . $parametro . '*' . $Pla_Cod, $obBD_conexion);
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
				if ($total_rs_buscar != 0) {
					foreach ($rs_buscar as $row) {
						/**
						 * Consulta del detallete de la CUENTA 
						 */
						$rs_recur = $obBD_con1->getRowConsulta(204, $row['Pld_Rec'], $obBD_conexion);
				?>
						<tr class="Fondo">
							<td><?php echo $row['Pld_Cdc']; ?></td>
							<td><?php echo utf8_encode($row['Pld_Des']); ?></td>
							<td align="center"><?php if ($rs_recur['Pld_Des'] != "") {
													echo $rs_recur['Pld_Des'];
												} else {
													echo "&nbsp;";
												} ?></td>
							<td align="center"><?php echo $row['Pld_Tip']; ?></td>
							<td align="center"><?php echo $row['Pld_Est']; ?></td>
							<td align="center"><?php if ($row['Pld_Est'] == 'Activa') { ?>
									<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="document.getElementById(document.getElementById('name_input').value).value='<?php echo $row['Pld_Cdc']; ?>'">
										<i class="icon-arrow-right icon-white"></i>
									</button>
								<?php } else {
													echo "&nbsp;";
												} ?>
							</td>
						</tr>
					<?php } //Fin del foreach
				} //Fin del if($total_rs_buscar != 0)	
				else {
					?>
					<tr>
						<td width="10%">&nbsp;</td>
						<td><?php echo error_alerta(" No hay resultados que mostrar", 2) ?></td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td width="7%">&nbsp;</td>
						<td width="7%">&nbsp;</td>
					</tr>
				<?php
				} //Fin del else if($total_rs_buscar != 0)	
				?>
			</tbody>
		</table>
	</FIELDSET>
<?php
	/* Muestra la barra de estados con la cantidad de registros encontrados */
	echo barra_estado($total_rs_buscar);
	exit();
} //Fin del if (isset($cuenta))

/* Muestra el buscador de las cuentas contables */
if (isset($ajax_buscador)) { ?>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" id="buscador">
		<tr>
			<td>
				<FIELDSET>
					<LEGEND>
						<label class="Titulos2">B&uacute;squeda de Cuentas </label>
					</LEGEND>
					<table width="444" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td width="217"><input name="op_opciones" id="op_opciones" type="radio" value="d" checked="checked" onClick="document.getElementById('op_op').value = this.value; setfocus(document.getElementById('buscta'))">
								<span class="LetraNegra">Descripci&oacute;n</span>
							</td>
							<td width="227"><input type="radio" name="op_opciones" id="op_opciones" value="c" onClick="document.getElementById('op_op').value = this.value; setfocus(document.getElementById('buscta'))">
								<span class="LetraNegra">C&oacute;digo</span>
							</td>
						</tr>
					</table>	
					<input name="op_op" type="hidden" id="op_op" value="d">
					<input name="name_input" type="hidden" id="name_input" value="<?php echo $ajax_input; ?>">
					<table width="579" border="0" cellpadding="0" cellspacing="0">
						<tbody id="tbusqueda">
							<tr>
								<td width="440" class="BarraBusqueda"><span class="Asterisco">*</span> Cuenta:
									<input name="buscta" type="text" id="buscta" size="40" maxlength="50" onKeyUp="parametro_injection(this)" onKeyPress="enter_ajax('<?php echo $_SERVER['PHP_SELF']; ?>?buscod=' + document.getElementById('buscta').value + '&op=<?php echo $op; ?>&op_op=' + document.getElementById('op_op').value + '&name_input=' + document.getElementById('name_input').value + '&Pec_Cod=' + document.getElementById('Pec_Cod').value+'&Pla_Cod=<?php echo $Pla_Cod; ?>', 'busqueda')">
								</td>
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

/* Muestra el detalle de los comprobantes */
if (isset($ajax_detalle)) {
	$com_codigo = $ajax_codigo;
	include("../COMPONENTES/con_con_detalleCompr.php");
	exit();
}

/* Inicializar variables del período si se viene desde el balance (con hdd_save2 y Pec_Cod2) */
if (isset($hdd_save2) && isset($Pec_Cod2) && !isset($Pec_Cod)) {
	/* Divide la cadena del periodo contable que viene desde el balance */
	$arreglo_periodo = explode("~", $Pec_Cod2);
	$Pec_Cod = $arreglo_periodo[0];
	$Pec_Fei = isset($arreglo_periodo[1]) ? $arreglo_periodo[1] : '';
	$Pec_Fef = isset($arreglo_periodo[2]) ? $arreglo_periodo[2] : '';
	$Pla_Cod = isset($arreglo_periodo[3]) ? $arreglo_periodo[3] : '';
	
	/* Si viene desde el balance con txt_busqueda, establecer fechas por defecto del período */
	// if (isset($txt_busqueda) && $txt_busqueda != "" && !isset($txt_fec_ini)) {
	// 	$txt_fec_ini = $Pec_Fei;
	// 	$txt_fec_fin = $Pec_Fef;
	/* Si viene desde el balance con txt_busqueda, establecer fechas */
	if (isset($txt_busqueda) && $txt_busqueda != "") {
		/* Usar fechas de la URL si vienen, si no, usar fechas del período */
		if (!isset($txt_fec_ini) || $txt_fec_ini == "") {
			$txt_fec_ini = $Pec_Fei;
		}
		if (!isset($txt_fec_fin) || $txt_fec_fin == "") {
			$txt_fec_fin = $Pec_Fef;
		}
		/* Si viene Chk_Fec=1 desde el balance, significa que se usaron fechas personalizadas */
		if (isset($Chk_Fec) && $Chk_Fec == 1) {
			/* El checkbox ya viene activo, las fechas ya están en txt_fec_ini y txt_fec_fin */
		}
		/* Asegurar que op esté establecido */
		if (!isset($op)) {
			$op = 1;
		}
		/* Establecer variables por defecto para la consulta */
		if (!isset($ordenar)) {
			$ordenar = 'Com_Fec';
		}
		if (!isset($Com_Aut)) {
			$Com_Aut = '';
		}
	}
}

/* Descripcion del periodo contable */
// $periodo = "del periodo contable " . substr($Pec_Fei, 0, 4);
$periodo = "del periodo contable " . (isset($Pec_Fei) && $Pec_Fei != '' ? substr($Pec_Fei, 0, 4) : '');

if (isset($hdd_save2) or isset($hdd_save3)) {
	$hoy = date("Y-m-d");

	/**
	 * OPCIONES 
	 */
	switch ($op) {
		case 1:
			/**
			 * Cargado de los datos de la cabecera 
			 */
			if ($txt_busqueda != "") {
				/**
				 * Consulta del detalle de la CUENTA buscada
				 */
				$row_cuenta = $obBD_con1->getRowConsulta(314, trim($txt_busqueda) . '*' . $Pla_Cod, $obBD_conexion);
				$Pld_Cod = $row_cuenta['Pld_Cod'];

				/**
				 * Consulta del saldo, anterior a la fecha inicial dentro de un mismo periodo (No cambiar esta forma antigua de llamado de las sql)
				 */
				$fech_fut = fechas_futuras($txt_fec_ini, -1);
				$rs_saldos = $obBD_con1->consulta(
					sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $Pld_Cod . '*' . $Pec_Cod)),
					$obBD_conexion->conexion
				);
				$row_rs_saldos = $obBD_con1->registros();
				$total_rs_saldos = $obBD_con1->numregistros();

				/**
				 * Se realiza esto porque solo deben haber dos registros 
				 */
				/**
				 * De los dos supuestos registros encontrados toma por defecto el primero 
				 */
				if ($row_rs_saldos['Asi_Deh'] == 'D') {
					$debe = $row_rs_saldos['Asi_Val'];
					/**
					 * Mueve el puntero al inicio 
					 */
					$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
				} else {
					$debe = 0;
				}

				$haber = $row_rs_saldos['Asi_Val'];
				$tipo_grupo = explode('.', $txt_busqueda);
				/**
				 * 1 = Activo
				 * 2 = Pasivo
				 * 3 = Patrimonio
				 * 4 = Ingresos
				 * 5 = Costos y Gastos 
				 */
				if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
					$saldos = $haber - $debe;
				} else {
					$saldos = $debe - $haber;
				}
				/**
				 * Consulta del detalle de la mayorización 
				 */
				$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $Pld_Cod . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
				// //ChromePhp::log("",$rs_cuenta);
				$total_rs_cuenta = count($rs_cuenta);

				/**
				 * Carga el año de la fecha incial 
				 */
				list($annn, $mess, $dia) = split('[/.-]', $fech_fut);
				$anio = date("Y", mktime(0, 0, 0, $mess, $dia, $annn));
			} //Fin del if ($txt_busqueda != "")
			break;
		case 2:
			if ($grupo != "") {
				/**
				 * Consulta el codigo interno de la cuenta inicial 
				 */
				$rs_cuenta_int = $obBD_con1->getRowConsulta(216, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
				$Pld_Cod = $rs_cuenta_int['Pld_Cod'];
				/**
				 * Consulta del rango de cuentas para la busqueda 
				 */
				//$rs_rango = $obBD_con1->getArrayConsulta(203, $Pld_Cod, $obBD_conexion);
				$rs_rango = $obBD_con1->getArrayConsulta(340, trim($grupo) . '*' . $Pla_Cod, $obBD_conexion);
				$total_rs_rango = count($rs_rango);
			}
			break;
	} //FIn del case $op
} //Fin del if (isset($hdd_save))
else {
	if (isset($hdd_save)) {
		/**
		 * Divide la cadena del periodo contable 
		 */
		$arreglo = explode("~", $Pec_Cod2);
		$Pec_Cod = $arreglo[0];
		$Pec_Fei = $arreglo[1];
		$Pec_Fef = $arreglo[2];
		$Pla_Cod = $arreglo[3];
	} //Fin del if (isset($hdd_save))
	else {
		/**
		 * Carga todos los periodos contables, Activos y Anulados 
		 */
		$rs_periodo = $obBD_con1->getArrayConsulta(219, $Ses_Emp_Cod, $obBD_conexion);
		$total_rs_periodo = count($rs_periodo);
		$row_rs_periodo = current($rs_periodo);
	} //Fin del else if (isset($hdd_save))
}
?>
<HTML>

<HEAD>
	<TITLE><?php echo $Ses_Sys_Nom; ?></TITLE>
	<meta charset="UTF-8">

	<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
	<script language="javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script language="javascript" src="../VALIDACIONES/con_val_mayorizacion.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
	<script type="text/javascript">
		$(function() {
			$('#set1 *').tooltip({
				showURL: false
			});
		});
	</script>
	<!--Librerias para exportar a excel -->
	<script language="javascript">
		$(document).ready(function() {
			/* LLamado a la class del boton exportar */
			$("#Boton_Excel").click(function(event) {
				$("#datos_a_enviar").val($("<div>").append($("#Exportar_a_Excel").eq(0).clone()).html());
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
			$("#Com_Fec").datepicker();
			$("#Com_Fec").change(function() {
				$("#Com_Fec").datepicker("option", "dateFormat", "yy-mm-dd");
			});
		});
	</script>
	<meta http-equiv="Content-Type" content="text/html;">
</HEAD>

<BODY>
	<div id="set1">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
			<tr class="BarraTitulo">
				<td height="10">&raquo; mayorizaci&oacute;n general <?php echo $periodo; ?> </td>
			</tr>
			<tr>
				<td valign="top" height="400">
					<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<?php
						if (!isset($hdd_save) && !isset($hdd_save2) && !isset($hdd_save3)) { ?>
							<FIELDSET>
								<LEGEND>
									<label class="Titulos2">Selección Periodo Contable</label>
								</LEGEND>
								<table width="304" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
										<td width="115">
											<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
											<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>">
											<select name="Pec_Cod2" id="Pec_Cod2" onChange="javascript: asignar_fechas(this.value)">
												<?php
												if ($total_rs_periodo > 0) {
													foreach ($rs_periodo as $row) {
												?>
														<option value="<?php echo $row['Pec_Cod'] . '~' . $row['Pec_Fei'] . '~' . $row['Pec_Fef'] . '~' . $row['Pla_Cod']; ?>"><?php echo $row['Periodo']; ?></option>
													<?php
													}
												} //Fin del if ($total_rs_periodo > 0)
												else { ?>
													<option value=""></option>
												<?php
												}
												?>
											</select>
										</td>
										<td width="120" align="center">
											<button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod2', 0)"> <i class="icon-search icon-white"></i> <span>Aceptar</span> </button>
											<input name="hdd_save" type="hidden" id="hdd_save">
										</td>
									</tr>
								</table>
							</FIELDSET>
						<?php
						} //Fin del if (!isset($hdd_save))

						if (isset($hdd_save) or isset($hdd_save2) or isset($hdd_save3)) {
							$pag1 = $_SERVER['PHP_SELF'] . "?op=1&Pec_Cod2=" . $Pec_Cod2 . "&hdd_save=1";
							$pag2 = $_SERVER['PHP_SELF'] . "?op=2&Pec_Cod2=" . $Pec_Cod2 . "&hdd_save=1";
							tabs(2, 'Cuenta' . '*' . 'Grupos', $pag1 . '*' . $pag2, $op);
						?>
							<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
								<div id="ContTabul">
									<table width="99%" border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td width="50%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
											<td width="50%"><?php include("../COMPONENTES/con_con_presentacion.php"); ?></td>
										</tr>
									</table>
									<input name="Pec_Cod2" type="hidden" id="Pec_Cod2" value="<?php echo $Pec_Cod2; ?>">
									<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
									<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
									<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
									<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>">
									<input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
									<?php
									switch ($op) {
										case 1:
									?>
											<table width="99%" border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td>
														<FIELDSET>
															<LEGEND>
																<label class="Etiqueta1">Buscar por cuenta:</label>
															</LEGEND>
															<table border="0" cellpadding="0" cellspacing="0">
																<tr>
																	<td width="63" height="28" class="Etiqueta1"><span class="Asterisco">* </span>Cuenta:&nbsp;</td>
																	<td width="215" valign="middle"><input name="txt_busqueda" type="text" id="txt_busqueda" value="<?php echo isset($txt_busqueda) ? $txt_busqueda : ''; ?>" size="30" maxlength="50" onBlur="validar_cuentas(form1, this)" onKeyUp="parametro_injection(this)"></td>
																	<td width="100" valign="middle" height="28" class="Etiqueta1">Mensualizar:&nbsp;</td>
																	<td width="30" height="28" class="Etiqueta1"><input type="checkbox" name="mensualizar"></td>
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
														<button type="button" class="btn btn-success fileinput-button" title="Buscar Cuenta de Detalle" name="button1" id="button1" onClick="ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=1&ajax_input=txt_busqueda&Pla_Cod=<?php echo $Pla_Cod; ?>', 'ajax_modal')">
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
																	<td width="215"><input name="grupo" type="text" id="grupo" value="<?php echo $grupo; ?>" size="30" maxlength="50" onBlur="/*validar_cuentas(form1, this)*/"></td>
																	<td width="100" valign="middle" height="28" class="Etiqueta1">Mensualizar:&nbsp;</td>
																	<td width="30" height="28" class="Etiqueta1"><input type="checkbox" name="mensualizar"></td>
																</tr>
															</table>
														</FIELDSET>
													</td>
												</tr>
											</table>
											<br>
											<table border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td width="110">
														<button type="button" class="btn btn-success fileinput-button" title="Buscar Cuenta de Detalle" name="button1" id="button1" onClick="
        ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_buscador=1&ajax_input=grupo&Pla_Cod=<?php echo $Pla_Cod; ?>', 'ajax_modal')">
															<i class="icon-list-alt icon-white"></i>
															<span>Cuenta</span>
														</button>
														<input name="hdd_save3" type="hidden" id="hdd_save3">
													</td>
													<td width="154">
														<button type="button" class="btn btn-success fileinput-button" title="Mayorizar" name="button" id="button" onClick="validar_buscar_cuenta(document.form1, 'grupo')">
															<i class="icon-check icon-white"></i>
															<span>Mayorizar</span>
														</button>
														<input name="cantmodal" id="cantmodal" type="hidden" value="2">
													</td>
												</tr>
											</table>
									<?php
											break;
									} //Fin del case $op
									?>
									<input name="op" type="hidden" id="op" value="<?php echo $op; ?>">
							</form>


							<?php
							function applyFormat(&$item)
							{
								$item = date('Y-m-d', $item);
							}
							switch ($op) {
								case 1:
									if (isset($txt_busqueda)) {
										$total_debe = 0;
										$total_haber = 0;

										if (isset($mensualizar)) {
											$result = $obBD_con1->getMonthRanges($txt_fec_ini, $txt_fec_fin);
											array_walk_recursive($result, 'applyFormat');
							?>
											<div id="Exportar_a_Excel">
												<br />
												<table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
													<tr>
														<td width="47" class="Etiqueta1">Desde:</td>
														<td width="201"><?php echo $txt_fec_ini; ?></td>
														<td width="125" class="Etiqueta1">Hasta:</td>
														<td width="387"><?php echo $txt_fec_fin; ?></td>
													</tr>
													<tr>
														<td class="Etiqueta1">Codigo:</td>
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

												<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03" style="table-layout:fixed;">
													<thead>
														<tr>
															<th align="center" width="5%">C&oacute;d. Int.</th>
															<th align="center" width="3%">Gen.</th>
															<th align="center" width="6%">No. Com.</th>
															<th align="center" width="6%">Fecha</th>
															<th align="center" width="10%">No. Fact/ No.Cheque</th>
															<th align="center" width="8%">Ced/Ruc</th>
															<th align="center" width="20%">Cliente/Proveedor</th>
															<th align="center" width="21%">Detalle</th>
															<th align="center" width="6%">Debe</th>
															<th align="center" width="6%">Haber</th>
															<th align="center" width="6%">Saldo</th>
															<th align="center" width="3%">&nbsp;</th>
														</tr>
													</thead>
													<tbody>

														<?php
														foreach ($result as $r) {
															$rs_cuenta = $obBD_con1->getArrayConsulta(201, $r['start'] . '*' . $r['end'] . '*' . $Pld_Cod . '*' . $ordenar . '*' . $Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
															$total_rs_cuenta = count($rs_cuenta);
															$fech_fut = fechas_futuras($r['start'], -1);
															$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $Pld_Cod . '*' . $Pec_Cod)), $obBD_conexion->conexion);
															$row_rs_saldos = $obBD_con1->registros();
															$total_rs_saldos = $obBD_con1->numregistros();


															if ($row_rs_saldos['Asi_Deh'] == 'D') {
																$debe = $row_rs_saldos['Asi_Val'];
																$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
															} else {
																$debe = 0;
															}

															$haber = $row_rs_saldos['Asi_Val'];
															$tipo_grupo = explode('.', $txt_busqueda);

															if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
																$saldos = $haber - $debe;
															} else {
																$saldos = $debe - $haber;
															}

															list($annn, $mess, $dia) = split('[/.-]', $fech_fut);
															$anio = date("Y", mktime(0, 0, 0, $mess, $dia, $annn));?>
															<tr>
																<td align="center">&nbsp;</td>
																<td align="center">&nbsp;</td>
																<td align="center">&nbsp;</td>
																<td>&nbsp;</td>
																<td>&nbsp;</td>
																<td>&nbsp;</td>
																<td>SALDO AL <?php echo $dia . ', de ' . mes($mess, 1) . ', ' . $annn; ?></td>
																<td align="right">&nbsp;</td>
																<td align="right">&nbsp;</td>
																<td align="right" <?php if ($saldos < 0) {
																						echo "style='color:#FF0000'";
																					} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
																<td align="right" <?php if ($saldos < 0) {
																						echo "style='color:#FF0000'";
																					} ?>>&nbsp;</td>
															</tr>
															<?php
															if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
																$saldo_acumulado = $saldos; // se inicia con el saldo al corte anterior
																$total_debe = 0;
																$total_haber = 0;
																foreach ($rs_cuenta as $row) {
																	$numeroDocCompra = "";
																	$numeroDocVenta = "";
																	$cheque = $row['Che_Num'];

																	if (!empty($row['Cli_Cod'])) {
																		$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																		$numeroDocVenta = $row_numDoc['Vet_Num'];
																	}
																	if (!empty($row['Prv_Cod'])) {
																		$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																		$numeroDocCompra = $row_numDoc['Cop_Num'];
																	}

																	if ($row['Tia_Ini'] == 'I') {

																		$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
																	} else {
																		$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
																	}
																	$total_rs_proveedore = isset($rs_proveedore) ? count($rs_proveedore) : 0;
																	$i++;
																	list($ann, $mes, $dia) = split('[/.-]', $row['Com_Fec']);
															?>
																	<tr>
																		<td align="center"><?php echo $row['Com_Cod']; ?></td>
																		<td align="center"><?php echo $row['Com_Gen']; ?></td>
																		<td align="center"><?php echo  $row['Tia_Abr'] . "-" . $mes . "-" . str_pad($row['Com_Num'], 2, "0", STR_PAD_LEFT); ?></td>
																		<td align="center"><?php echo $row['Com_Fec']; ?></td>
																		<td align="left"><?php echo (isset($numeroDocVenta) ? $numeroDocVenta : '') . (isset($numeroDocCompra) ? $numeroDocCompra : '') . (isset($cheque) ? 'Cheque No. ' . $cheque : ''); ?></td>
																		<td align="center"><?php echo $row_proveedore['Prs_Ced']; ?></td>
																		<td align="left" style="white-space: nowrap; overflow: hidden;" title="<?php $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?>"><?php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
																		<td style="white-space: nowrap; overflow: hidden;" title="<?php echo $row['Com_Con']; ?>"><?php echo $row['Com_Con'];  echo $tipo_documento_compra ;   ?></td>
																		<td align="right">
																			<?php
																			if ($row['Asi_Deh'] == 'D') {
																				echo formato_numero($row['Asi_Val'], 2, 2);
																				$debe = $row['Asi_Val'];
																				// $total_debe = $total_debe + $debe;
																				$saldo_acumulado += $debe;
																				$total_debe += $debe;
																			} else {
																				echo "0,00";
																				$debe = 0;
																			}
																			?>
																		</td>
																		<td align="right">
																			<?php
																			if ($row['Asi_Deh'] == 'H') {
																				echo formato_numero($row['Asi_Val'], 2, 2);
																				$haber = $row['Asi_Val'];
																				// $total_haber = $total_haber + $haber;
																				$saldo_acumulado -= $haber;
																				$total_haber += $haber;
																			} else {
																				echo "0,00";
																				$haber = 0;
																			}
																			?>
																		</td>
																		<!--antes estaba solo $saldos-->
																		<td align="right" <?php if ($saldo_acumulado < 0) { 
																								echo "style='color:#FF0000'";
																							} ?>> <?php echo formato_numero($saldo_acumulado, 2, 2); ?> </td>
																		<td align="center"><button type="button" name="button<?php echo $i + 1; ?>" id="button<?php echo $i + 1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?php echo $row['Com_Cod']; ?>', 'ajax_modal')">
																				<i class="icon-info-sign icon-white"></i>
																			</button> </td>
																	</tr>

																<?php
																} //foreach interno
																?>
																<tr>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td style="font-weight: bold;">Desde: <?php echo $r['start']; ?> Hasta: <?php echo $r['end']; ?></td>
																	<td align="right" style="font-weight: bold;"> TOTAL</td>
																	<td align="right"><?php echo formato_numero($total_debe, 2, 2); ?></td>
																	<td align="right"><?php echo formato_numero($total_haber, 2, 2); ?></td>
																	<td align="right">&nbsp;</td>
																	<td align="right">&nbsp;</td>
																</tr>
															<?php
															} //if de conteo de cuenta y saldos
															else {

															?>
																<tr>
																	<td>&nbsp;</td>
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
																</tr>
														<?php
															} //fin del else
														} //foreach externo
														?>

													</tbody>
												</table>
												<br />


											<?php
										} // fin if mensualizar
										else {
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
															<td class="Etiqueta1">Codigo:</td>
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
													<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03" style="table-layout:fixed;">
														<thead>
															<tr>
																<th align="center" width="5%">C&oacute;d. Int.</th>
																<th align="center" width="3%">Gen.</th>
																<th align="center" width="6%">No. Com.</th>
																<th align="center" width="6%">Fecha</th>
																<th align="center" width="10%">No. Fact/ No.Cheque</th>
																<th align="center" width="8%">Ced/Ruc</th>
																<th align="center" width="20%">Cliente/Proveedor</th>
																<th align="center" width="21%">Detalle</th>
																<th align="center" width="6%">Debe</th>
																<th align="center" width="6%">Haber</th>
																<th align="center" width="6%">Saldo</th>
																<th align="center" width="3%">&nbsp;</th>
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
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>&nbsp;</td>
																	<td>SALDO AL <?php echo $dia . ', de ' . mes($mess, 1) . ', ' . $annn; ?></td>
																	<td align="right">&nbsp;</td>
																	<td align="right">&nbsp;</td>
																	<td align="right" <?php if ($saldos < 0) {
																							echo "style='color:#FF0000'";
																						} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
																	<td align="right" <?php if ($saldos < 0) {
																							echo "style='color:#FF0000'";
																						} ?>>&nbsp;</td>
																</tr>

																<?php
																$i = 0;

																
																foreach ($rs_cuenta as $row) {
																	$numeroDocCompra = "";
																	$numeroDocVenta = "";
																	$cheque = $row['Che_Num'];
																	$tipo_documento_venta = null;
																	$tipo_documento_compra = null;

																	//echo $row['Com_Cod'];
																	/* Consultamos datos Venta */
																	if (!empty($row['Cli_Cod'])) {
																		$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																		$numeroDocVenta = $row_numDoc['Vet_Num'];
																		$tipo_documento_venta = $row_numDoc['Tic_Des'];
																	}

																	
																	/* Consultamos datos Compras */
																	if (!empty($row['Prv_Cod'])) {
																		$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																		$numeroDocCompra = $row_numDoc['Cop_Num'];
																		$tipo_documento_compra = $row_numDoc['Tic_Des'];
																	}
																	/* Consulta del cliente o proveedor */
																	if ($row['Tia_Ini'] == 'I') {
																		/* Consulta la descripcion del cliente */
																		$row_proveedore = $obBD_con1->getRowConsulta(217, $row['Cli_Cod'], $obBD_conexion);
																	} elseif ($row['Tia_Abr'] == 'RL') { // nueva condicion para validar el rol de pago
																		if ($row['Tia_Ini'] == 'D') {
																			$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
																		} else {
																		// Empleado (Rol de pago)
																		$row_proveedore = $obBD_con1->getRowConsulta(2199, $row['Com_Cod'], $obBD_conexion);
																		}
																	} else {
																		/* Consulta la descripcion del proveedor */
																		$row_proveedore = $obBD_con1->getRowConsulta(218, $row['Prv_Cod'], $obBD_conexion);
																	} //Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
																	$total_rs_proveedore = isset($rs_proveedore) ? count($rs_proveedore) : 0;

																	$i++;
																	list($ann, $mes, $dia) = split('[/.-]', $row['Com_Fec']);


																	$documento_compra = isset($tipo_documento_compra) ? $tipo_documento_compra : '';
																	$documento_venta = isset($tipo_documento_venta) ? $tipo_documento_venta : '';
																	$informacion_documentos = $documento_compra .' '.$documento_venta;
																?>
																	<tr>
																		<td align="center"><?php echo $row['Com_Cod']; ?></td>
																		<td align="center"><?php echo $row['Com_Gen']; ?></td>
																		<td align="center"><?php echo  $row['Tia_Abr'] . "-" . $mes . "-" . str_pad($row['Com_Num'], 2, "0", STR_PAD_LEFT); ?></td>
																		<td align="center"><?php echo $row['Com_Fec']; ?></td>
																		<td align="left"><?php echo (isset($numeroDocVenta) ? $numeroDocVenta : '') . (isset($numeroDocCompra) ? $numeroDocCompra : '') . (isset($cheque) ? 'Cheque No. ' . $cheque : ''); ?></td>
																		<!-- <td align="left"><?php echo (isset($numeroDocVenta) ? $numeroDocVenta : '') . (isset($numeroDocCompra) ? $numeroDocCompra : '') . (isset($cheque) ? 'Ch #. ' . $cheque . (!empty($row['Pld_Des']) ? ' de ' . $row['Pld_Des'] : '') : ''); ?></td> -->
																		
																		<td align="center"><?php echo $row_proveedore['Prs_Ced']; ?></td>
																		<td align="left" style="white-space: nowrap; overflow: hidden;" title="<?php $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?>"><?php echo $row_proveedore['Prs_Ape'] . ' ' . $row_proveedore['Prs_Nom']; ?></td>
																		<!-- <td style="white-space: nowrap; overflow: hidden;" title="<?php echo $row['Com_Con']; ?>"><?php echo $row['Com_Con']; if (!empty($informacion_documentos)) { echo ' (' . $informacion_documentos . ')' ; } ?> </td> -->
																		
																		<!-- SI LA LINEA DE ABAJO DA PROBLEMAS PREGUNTAR QUIEN TIENE LA LINEA ANTERIOR -->

																		<td style="white-space: nowrap; overflow: hidden;" title="<?php echo $row['Com_Con'] . (!empty($row['Com_Con']) && !empty($row['Com_Obs']) ? ' / ' : '') . $row['Com_Obs']; ?>"><?php echo $row['Com_Con'] . (!empty($row['Com_Con']) && !empty($row['Com_Obs']) ? ' / ' : '') . $row['Com_Obs']; if (!empty($informacion_documentos)) { echo ' (' . $informacion_documentos . ')' ; } ?> </td> <!-- agregra el concepto del abono y la observacion en concreto--><td align="right"><?php if ($row['Asi_Deh'] == 'D') {
																								echo formato_numero($row['Asi_Val'], 2, 2);
																								$debe = $row['Asi_Val'];
																								$total_debe = $total_debe + $debe;
																							} else {
																								echo "0,00";
																								$debe = 0;
																							} ?></td>
																		<td align="right"><?php if ($row['Asi_Deh'] == 'H') {
																								echo formato_numero($row['Asi_Val'], 2, 2);
																								$haber = $row['Asi_Val'];
																								$total_haber = $total_haber + $haber;
																							} else {
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
																		if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
																		{ //Nuevo
																			$saldos = $saldos + ($haber - $debe); //Formula especial			
																		} //Nuevo
																		else //Nuevo
																		{ //Nuevo			
																			$saldos = $saldos + ($debe - $haber);
																		} //Nuevo			
																		?>
																		<td align="right" <?php if ($saldos < 0) {
																								echo "style='color:#FF0000'";
																							} ?>><?php echo formato_numero($saldos, 2, 2); ?></td>
																		<td align="center"><button type="button" name="button<?php echo $i + 1; ?>" id="button<?php echo $i + 1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?php echo $row['Com_Cod']; ?>', 'ajax_modal')">
																				<i class="icon-info-sign icon-white"></i>
																			</button> </td>
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
																<td align="right"><?php echo formato_numero($total_debe, 2, 2); ?></td>
																<td align="right"><?php echo formato_numero($total_haber, 2, 2); ?></td>
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
															<td>&nbsp;</td>
															<td><?php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
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
														} //fin else mensualizar
													} //Fin del if ($txt_busqueda)
											?>
													</table>
												</div>


												<?php
												break;
											case 2:
												if (isset($grupo)) {
													if (isset($mensualizar)) {
														$result = $obBD_con1->getMonthRanges($txt_fec_ini, $txt_fec_fin);
														array_walk_recursive($result, 'applyFormat');
												?>
														<br />
														<div id="Exportar_a_Excel">
															<table width="450" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
																<tr>
																	<td width="47" class="Etiqueta1">Desde:</td>
																	<td width="120"><?php echo $txt_fec_ini; ?></td>
																	<td width="73" class="Etiqueta1">Hasta:</td>
																	<td width="192"><?php echo $txt_fec_fin; ?></td>
																</tr>
															</table>
															<?php
															if ($total_rs_rango > 0) {
																$i = 0;

																foreach ($rs_rango as $row_rango) {
																	$total_debe = 0;
																	$total_haber = 0;
																	$saldo = 0;
																	$fech_fut = fechas_futuras($txt_fec_ini, -1);
																	$rs_saldos = $obBD_con1->consulta(
																		sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $row_rango['Pld_Cod'] . '*' . $Pec_Cod)),
																		$obBD_conexion->conexion
																	);
																	$row_rs_saldos = $obBD_con1->registros();
																	$total_rs_saldos = $obBD_con1->numregistros();

																	if ($row_rs_saldos['Asi_Deh'] == 'D') {
																		$debe = $row_rs_saldos['Asi_Val'];
																		$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
																	} else {
																		$debe = 0;
																	}

																	$haber = $row_rs_saldos['Asi_Val'];
																	$tipo_grupo = explode('.', $grupo);
																	if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) {
																		$saldos = $haber - $debe;
																	} else {
																		$saldos = $debe - $haber;
																	}

																	$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .
																		$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
																	$total_rs_cuenta = count($rs_cuenta);

																	if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
																		$row_recur = $obBD_con1->getRowConsulta(204, $row_rango['Pld_Rec'], $obBD_conexion); ?>

																		<table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
																			<tr>
																				<td class="Etiqueta1">C&oacute;digo:</td>
																				<td><?php echo $row_recur['Pld_Cdc']; ?></td>
																				<td class="Etiqueta1">GRUPO:</td>
																				<td><?php echo $row_recur['Pld_Des']; ?></td>
																			</tr>
																			<tr>
																				<td width="49" class="Etiqueta1">C&oacute;digo:</td>
																				<td width="201"><?php echo $row_rango['Pld_Cdc']; ?></td>
																				<td width="123" class="Etiqueta1">Cuenta:</td>
																				<td width="387"><?php echo $row_rango['Pld_Des']; ?></td>
																			</tr>
																		</table>
																		<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03" style="table-layout:fixed;">
																			<thead>
																				<tr>
																					<th align="center" width="5%">C&oacute;d. Int.</th>
																					<th align="center" width="3%">Gen.</th>
																					<th align="center" width="6%">No. Com.</th>
																					<th align="center" width="6%">Fecha</th>
																					<th align="center" width="10%">No. Fact/No. Che</th>
																					<th align="center" width="20%">Cliente/Proveedor</th>
																					<th align="center" width="21%">Detalle</th>
																					<th align="center" width="6%">Debe</th>
																					<th align="center" width="6%">Haber</th>
																					<th align="center" width="6%">Saldo</th>
																					<th align="center" width="4%">&nbsp;</th>
																				</tr>
																			</thead>
																			<tbody>
																				<?php
																				foreach ($result as $r) {
																					$registrosCuenta = $obBD_con1->getArrayConsulta(201, $r['start'] . '*' . $r['end'] . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .
																						$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);

																					$fech_fut = fechas_futuras($r['start'], -1);
																					list($ann, $mes, $dia) = split('[/.-]', $fech_fut);
																					$anio = date("Y", mktime(0, 0, 0, $mes, $dia, $ann));
																				?>
																					<tr>
																						<td align="center">&nbsp;</td>
																						<td align="center">&nbsp;</td>
																						<td align="center">&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>SALDO AL <?php echo $dia . ', de ' . mes($mes, 1) . ', ' . $anio; ?></td>
																						<td align="right">&nbsp;</td>
																						<td align="right">&nbsp;</td>
																						<td align="right" <?php if ($saldos < 0) {
																												echo "style='color:#FF0000'";
																											} ?>><?php
																												echo formato_numero($saldos, 2, 2); ?></td>
																						<td align="right">&nbsp;</td>
																					</tr>
																					<?php
																					foreach ($registrosCuenta as $row_rs_cuenta) {
																						$numeroDocCompra = "";
																						$numeroDocVenta = "";
																						if (!empty($row['Cli_Cod'])) {
																							$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																							$numeroDocVenta = $row_numDoc['Vet_Num'];
																						}
																						if (!empty($row['Prv_Cod'])) {
																							$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																							$numeroDocCompra = $row_numDoc['Cop_Num'];
																						}
																						list($ann, $mes, $dia) = split('[/.-]', $row_rs_cuenta['Com_Fec']);
																						if ($row_rs_cuenta['Tia_Ini'] == 'I') {
																							$rs_proveedore = $obBD_con1->getRowConsulta(217, $row_rs_cuenta['Cli_Cod'], $obBD_conexion);
																						} else {
																							$rs_proveedore = $obBD_con1->getRowConsulta(218, $row_rs_cuenta['Prv_Cod'], $obBD_conexion);
																						}
																						$total_rs_proveedore = count($rs_proveedore);
																						$i++;
																					?>
																						<tr>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Cod']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Gen']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Tia_Abr'] . "-" . $mes . "-" . $row_rs_cuenta['Com_Num']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Fec']; ?></td>
																							<td align="left"><?php echo (isset($numeroDocVenta) ? $numeroDocVenta : '') . (isset($numeroDocCompra) ? $numeroDocCompra : '') . (isset($cheque) ? 'Cheque No. ' . $cheque : ''); ?></td>
																							<td align="left"><?php echo $rs_proveedore['Prs_Ape'] . ' ' . $rs_proveedore['Prs_Nom']; ?></td>
																							<td><?php echo $row_rs_cuenta['Com_Con']; ?> </td>
																							<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'D') {
																													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																													$debe = $row_rs_cuenta['Asi_Val'];
																													$total_debe = $total_debe + $debe;
																												} else {
																													echo "0.00";
																													$debe = 0;
																												} ?></td>
																							<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'H') {
																													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																													$haber = $row_rs_cuenta['Asi_Val'];
																													$total_haber = $total_haber + $haber;
																												} else {
																													echo "0.00";
																													$haber = 0;
																												} ?></td>
																							<?php
																							$tipo_grupo = explode('.', $grupo);

																							if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
																							{
																								$saldos = $saldos + ($haber - $debe); //Formula especial			
																							} else {
																								$saldos = $saldos + ($debe - $haber);
																							}
																							?>
																							<td align="right" <?php if ($saldos < 0) {
																													echo "style='color:#FF0000'";
																												} ?>><?php
																													echo formato_numero($saldos, 2, 2);
																													?>
																							</td>
																							<td align="right"><button type="button" name="button<?php echo $i + 1; ?>" id="button<?php echo $i + 1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?php echo $row_rs_cuenta['Com_Cod']; ?>', 'ajax_modal')">
																									<i class="icon-info-sign icon-white"></i>
																								</button></td>
																						</tr>
																					<?php
																					} //Fin $row_rs_cuenta
																					?>
																					<tr>
																						<td colspan="6" align="right" style="font-weight: bold;">Desde: <?php echo $r['start'] ?> Hasta: <?php echo $r['end'] ?></td>
																						<td colspan="1" align="right" style="font-weight: bold;">TOTAL</td>
																						<td align="right"><?php echo formato_numero($total_debe, 2, 2); ?></td>
																						<td align="right"><?php echo formato_numero($total_haber, 2, 2); ?></td>
																						<td align="right">&nbsp;</td>
																						<td align="right">&nbsp;</td>
																					</tr>

																				<?php
																				} //fin del foreach de meses
																				?>
																			</tbody>
																		</table>
															<?php
																		echo barra_estado($total_rs_cuenta) . "<br>";
																	} // Fin del if ($total_rs_cuenta > 0)
																} //Fin $row_rs_rango 
															} //Fin del if ($total_rs_rango > 0)
														} //fin del if mensualizar grupo
														else { //else del if mensualizar
															?>


															<br />
															<div id="Exportar_a_Excel">
																<table width="450" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
																	<tr>
																		<td width="47" class="Etiqueta1">Desde:</td>
																		<td width="120"><?php echo $txt_fec_ini; ?></td>
																		<td width="73" class="Etiqueta1">Hasta:</td>
																		<td width="192"><?php echo $txt_fec_fin; ?></td>
																	</tr>
																</table>
																<?php
																if ($total_rs_rango > 0) {
																	$i = 0;
																	foreach ($rs_rango as $row_rango) {
																		$total_debe = 0;
																		$total_haber = 0;
																		$saldo = 0;

																		/**
																		 * Consulta del saldo, anterior a la inicial 
																		 */
																		$fech_fut = fechas_futuras($txt_fec_ini, -1);
																		$rs_saldos = $obBD_con1->consulta(
																			sentencias_con(202, $obBD_con1->parametros($fech_fut . '*' . $row_rango['Pld_Cod'] . '*' . $Pec_Cod)),
																			$obBD_conexion->conexion
																		);
																		$row_rs_saldos = $obBD_con1->registros();
																		$total_rs_saldos = $obBD_con1->numregistros();
																		/**
																		 * Se realiza esto porque solo deben haber dos registros 
																		 */
																		/**
																		 * De los dos supuestos registros encontrados toma por defecto el primero 
																		 */
																		if ($row_rs_saldos['Asi_Deh'] == 'D') {
																			$debe = $row_rs_saldos['Asi_Val'];
																			/**
																			 * Mueve el puntero al inicio 
																			 */
																			$row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);
																		} else {
																			$debe = 0;
																		}

																		$haber = $row_rs_saldos['Asi_Val'];
																		$tipo_grupo = explode('.', $grupo);
																		/**
																		 * 1 = Activo
																		 * 2 = Pasivo
																		 * 3 = Patrimonio
																		 * 4 = Ingresos
																		 * 5 = Costos y Gastos 
																		 */
																		if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
																		{ //Nuevo
																			$saldos = $haber - $debe; //Formula especial			
																		} //Nuevo
																		else //Nuevo
																		{ //Nuevo			
																			$saldos = $debe - $haber;
																		} //Nuevo			
																		/**
																		 * Consulta del detalle de la mayorizacin 
																		 */
																		$rs_cuenta = $obBD_con1->getArrayConsulta(201, $txt_fec_ini . '*' . $txt_fec_fin . '*' . $row_rango['Pld_Cod'] . '*' . $ordenar . '*' .
																			$Pec_Cod . '*' . $Com_Aut, $obBD_conexion);
																		$cheque = $row['Che_Num'];
																		$total_rs_cuenta = count($rs_cuenta);
																		/**
																		 * Carga el a�o de la fecha incial 
																		 */
																		list($ann, $mes, $dia) = split('[/.-]', $fech_fut);
																		$anio = date("Y", mktime(0, 0, 0, $mes, $dia, $ann));

																		if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
																			/**
																			 * Consulta del detallete de la CUENTA 
																			 */
																			$row_recur = $obBD_con1->getRowConsulta(204, $row_rango['Pld_Rec'], $obBD_conexion);
																?>

																			<table width="778" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
																				<tr>
																					<td class="Etiqueta1">C&oacute;digo:</td>
																					<td><?php echo $row_recur['Pld_Cdc']; ?></td>
																					<td class="Etiqueta1">GRUPO:</td>
																					<td><?php echo $row_recur['Pld_Des']; ?></td>
																				</tr>
																				<tr>
																					<td width="49" class="Etiqueta1">C&oacute;digo:</td>
																					<td width="201"><?php echo $row_rango['Pld_Cdc']; ?></td>
																					<td width="123" class="Etiqueta1">Cuenta:</td>
																					<td width="387"><?php echo $row_rango['Pld_Des']; ?></td>
																				</tr>
																			</table>
																			<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader03" style="table-layout:fixed;">
																				<thead>
																					<tr>
																						<th align="center" width="5%">C&oacute;d. Int.</th>
																						<th align="center" width="3%">Gen.</th>
																						<th align="center" width="6%">No. Com.</th>
																						<th align="center" width="6%">Fecha</th>
																						<th align="center" width="10%">No. Fact/No. Che</th>
																						<th align="center" width="20%">Cliente/Proveedor</th>
																						<th align="center" width="21%">Detalle</th>
																						<th align="center" width="6%">Debe</th>
																						<th align="center" width="6%">Haber</th>
																						<th align="center" width="6%">Saldo</th>
																						<th align="center" width="4%">&nbsp;</th>
																					</tr>
																				</thead>
																				<tbody>
																					<tr>
																						<td align="center">&nbsp;</td>
																						<td align="center">&nbsp;</td>
																						<td align="center">&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>&nbsp;</td>
																						<td>SALDO AL <?php echo $dia . ', de ' . mes($mes, 1) . ', ' . $anio; ?></td>
																						<td align="right">&nbsp;</td>
																						<td align="right">&nbsp;</td>
																						<td align="right" <?php if ($saldos < 0) {
																												echo "style='color:#FF0000'";
																											} ?>><?php
																												echo formato_numero($saldos, 2, 2); ?></td>
																						<td align="right">&nbsp;</td>
																					</tr>
																					<?php
																					foreach ($rs_cuenta as $row_rs_cuenta) {
																						$numeroDocCompra = "";
																						$numeroDocVenta = "";
																						/* Consultamos datos Venta */
																						if (!empty($row['Cli_Cod'])) {
																							$row_numDoc = $obBD_con1->getRowConsulta(1, $row['Com_Cod'], $obBD_conexion);
																							$numeroDocVenta = $row_numDoc['Vet_Num'];
																						}

																						/* Consultamos datos Compras */
																						if (!empty($row['Prv_Cod'])) {
																							$row_numDoc = $obBD_con1->getRowConsulta(2, $row['Com_Cod'], $obBD_conexion);
																							$numeroDocCompra = $row_numDoc['Cop_Num'];
																						}

																						list($ann, $mes, $dia) = split('[/.-]', $row_rs_cuenta['Com_Fec']);
																						/**
																						 * Consulta del cliente o proveedor 
																						 */
																						if ($row_rs_cuenta['Tia_Ini'] == 'I') {
																							/**
																							 * Consulta la descripcion del cliente 
																							 */
																							$rs_proveedore = $obBD_con1->getRowConsulta(217, $row_rs_cuenta['Cli_Cod'], $obBD_conexion);
																						} else {
																							/**
																							 * Consulta la descripcion del proveedor 
																							 */
																							$rs_proveedore = $obBD_con1->getRowConsulta(218, $row_rs_cuenta['Prv_Cod'], $obBD_conexion);
																						} //Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
																						$total_rs_proveedore = count($rs_proveedore);
																						$i++;
																					?>
																						<tr>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Cod']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Gen']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Tia_Abr'] . "-" . $mes . "-" . $row_rs_cuenta['Com_Num']; ?></td>
																							<td align="center"><?php echo $row_rs_cuenta['Com_Fec']; ?></td>
																							<td align="left"><?php echo (isset($numeroDocVenta) ? $numeroDocVenta : '') . (isset($numeroDocCompra) ? $numeroDocCompra : '') . (isset($cheque) ? 'Cheque No. ' . $cheque : ''); ?></td>
																							<td align="left"><?php echo $rs_proveedore['Prs_Ape'] . ' ' . $rs_proveedore['Prs_Nom']; ?></td>
																							<td><?php echo $row_rs_cuenta['Com_Con']; ?></td>
																							<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'D') {
																													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																													$debe = $row_rs_cuenta['Asi_Val'];
																													$total_debe = $total_debe + $debe;
																												} else {
																													echo "0.00";
																													$debe = 0;
																												} ?></td>
																							<td align="right"><?php if ($row_rs_cuenta['Asi_Deh'] == 'H') {
																													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2);
																													$haber = $row_rs_cuenta['Asi_Val'];
																													$total_haber = $total_haber + $haber;
																												} else {
																													echo "0.00";
																													$haber = 0;
																												} ?></td>
																							<?php
																							$tipo_grupo = explode('.', $grupo);
																							/**
																							 * 1 = Activo
																							 * 2 = Pasivo
																							 * 3 = Patrimonio
																							 * 4 = Ingresos
																							 * 5 = Costos y Gastos 
																							 */
																							if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4) //Nuevo
																							{ //Nuevo
																								$saldos = $saldos + ($haber - $debe); //Formula especial			
																							} //Nuevo
																							else //Nuevo
																							{ //Nuevo			
																								$saldos = $saldos + ($debe - $haber);
																							} //Nuevo
																							?>
																							<td align="right" <?php if ($saldos < 0) {
																													echo "style='color:#FF0000'";
																												} ?>><?php
																													echo formato_numero($saldos, 2, 2);
																													?>
																							</td>
																							<td align="right"><button type="button" name="button<?php echo $i + 1; ?>" id="button<?php echo $i + 1; ?>" class="btn btn-info btn-mini" title="Ver detalle" onclick="Muestra_Aparecer(); ajax_datos('<?php echo $_SERVER['PHP_SELF']; ?>?ajax_detalle=1&ajax_codigo=<?php echo $row_rs_cuenta['Com_Cod']; ?>', 'ajax_modal')">
																									<i class="icon-info-sign icon-white"></i>
																								</button></td>
																						</tr>
																					<?php
																					} //Fin $row_rs_cuenta
																					?>
																				</tbody>
																				<tfoot>
																					<tr>
																						<td colspan="7" align="right">TOTAL</td>
																						<td align="right"><?php echo formato_numero($total_debe, 2, 2); ?></td>
																						<td align="right"><?php echo formato_numero($total_haber, 2, 2); ?></td>
																						<td align="right">&nbsp;</td>
																						<td align="right">&nbsp;</td>
																					</tr>
																				</tfoot>
																			</table>

														<?php
																			/**
																			 * Muestra la barra de estados con la cantidad de registros encontrados 
																			 */
																			echo barra_estado($total_rs_cuenta) . "<br>";
																		} // Fin del if ($total_rs_cuenta > 0)
																	} //Fin $row_rs_rango 
																} //Fin del if ($total_rs_rango > 0)
															} //fin del else mensualizar
														} //Fin del if (isset($grupo))
														?>
															</div><?php
																	break;
															} //Fin del switch 
																	?>
													</fielset>
												<?php
											} //Fin del if (isset($hdd_save))

											if (((isset($total_rs_cuenta) && $total_rs_cuenta > 0) || (isset($total_rs_saldos) && $total_rs_saldos > 0)) || (isset($rs_rango) && count($rs_rango) > 0)) {
												?>
													<br>
													<table width="330" border="0" cellpadding="0" cellspacing="0">
														<tr>
															<td width="110" scope="col">
																<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form_volver" id="form_volver" style="display: inline-block;">
																	<button type="submit" class="btn" title="Volver a selección de período"
																		style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #ffffff; width: auto; padding: 6px 14px; border-radius: 6px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500; margin-left: 10px;">
																		<i class="icon-arrow-left icon-white"></i>
																		<span> Volver</span>
																	</button>
																</form>
															</td>
															<td width="110" scope="col">
																<form action="con_pri_mayorizacion_1.1.php" method="post" name="form2" id="form2" target="_blank">
																	<button type="button" class="btn btn-primary start" title="Imprimir Mayor" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
																	<input name="op" type="hidden" id="op" value="<?php echo $op; ?>">
																	<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $Pec_Cod; ?>">
																	<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>">
																	<input name="txt_busqueda" type="hidden" id="txt_busqueda" value="<?php echo $txt_busqueda; ?>">
																	<input name="grupo" type="hidden" id="grupo" value="<?php echo isset($grupo) ? $grupo : ''; ?>">
																	<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
																	<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
																	<input name="ordenar" type="hidden" id="ordenar" value="<?php echo $ordenar; ?>">
																	<input name="mensualizar" type="hidden" id="mensualizar" value="<?php echo $mensualizar; ?>">
																</form>
															</td>
															<td width="110" scope="col"><?php //if(count($rs_rango)==0){ 
																						?>
																<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
																	<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
																	<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
																		<i class=" icon-share icon-white"></i>
																		<span>Excel</span>
																	</button>
																</form><?php //} 
																		?>
															</td>
														</tr>
													</table>
													<br />
												<?php
											} //Fin del if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) 
												?>
				</td>
			</tr>
		</table>
		<div id="bgtransparent" class="bgtransparent" style="display:none" onClick="closeModal()">
		</div>
		<div id="bgmodal" class="bgmodal" style="display:none">
			<div id="ajax_modal"></div>
		</div>
	</div>
	<script type="text/javascript" src="../VALIDACIONES/con_par_mayorizacion.js"></script>
	<script type="text/javascript" src="../../Librerias/textbox/main.js"></script>
</BODY>

</HTML>
<?php
/*Cierra las conexiones  */
$obBD_conexion->cerrar();
?>