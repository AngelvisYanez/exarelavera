<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php

/**
* Descripción: Permite consultar el balance de perdidas y ganacias (resultados)
* Fecha de actualización:	2012-10-09
* Desarrollador:	Lewis Chimarro 
* Fecha de actualización:	2015-06-15
* Desarrollador:	Lewis Chimarro 
*/

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_estbalanc2.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Configuración para aumentar el tiempo de ejecución del script
 * Útil cuando se procesan grandes volúmenes de datos contables
 */
set_time_limit(3900); // 5 minutos (300 segundos)
ini_set('max_execution_time', 3900);
ini_set('memory_limit', '1024M'); // Aumentar también el límite de memoria si es necesario

/*  Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

/* Obtener parametro de sucursal */
$cmb_sucursal = (isset($_POST['cmb_sucursal']) ? $_POST['cmb_sucursal'] : (isset($_GET['cmb_sucursal']) ? $_GET['cmb_sucursal'] : ''));

/* Consulta todos los periodos activos */
if(!isset($Pec_Cod)) {
	/* Carga el periodos contable actual */
	$rs_periodos = $obBD_con1->getArrayConsulta(219,$Ses_Emp_Cod, $obBD_conexion);
	$perio = current($rs_periodos);
} else {
	/* Descripcion del periodo contable */
	$periodo = "en el periodo contable ".substr($Pec_Fei, 0,4);
}

?>
<HTML>
	<HEAD>
		<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
		<TITLE><?php echo "Estado Resultado [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<!--meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"-->
		<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/con_val_balances.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
          $(function() { $('#set1 *').tooltip({showURL: false}); });
		</script>
		<!--Librerias para exportar a excel --> 
		<script type="text/javascript">
			$(document).ready(function() {
				/* LLamado a la class del boton exportar */
				$("#Boton_Excel").click(function(event) {
					$("#datos_a_enviar").val( $("<div>").append( $("#Exportar_a_Excel").eq(0).clone()).html());
					$("#FormularioExportacion").submit();
				});
			});
		</script>
		<!-- Estilos personalizados para Estado de Resultado -->
		<style>
		/* Contenedor principal moderno */
		.balance-container { background: #DFE9F6; padding: 0; min-height: 100vh; }
		/* Card moderna para el contenido */
		.balance-card { background: #DFE9F6; border-radius: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 0; margin-bottom: 10px; overflow: hidden; }
		/* Header moderno */
		.balance-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: #ffffff; padding: 12px 15px; border-radius: 0; margin: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box; }
		.balance-header h2 { margin: 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; }
		.balance-header h2 span { margin-right: 8px; font-size: 18px; }
		/* Formularios modernos */
		.balance-form-section { display: grid; grid-template-columns: 2.4fr 1.2fr 0.8fr 1.4fr; gap: 6px; margin-bottom: 8px; width: 100%; box-sizing: border-box; align-items: stretch; }
		/* Estilos uniformes para todas las secciones */
		.balance-form-section > div { width: 100%; min-width: 0; box-sizing: border-box; height: 100%; display: flex; flex-direction: column; }
		/* Evitar que la sección de Utilidad se despegue */
		.balance-form-section > div:nth-child(3) { min-width: 0; max-width: 100%; }
		/* Aplicar estilos uniformes a TODOS los fieldsets dentro del form-section */
		.balance-form-section > div FIELDSET,
		.balance-form-section > div .balance-fieldset,
		.balance-form-section > div fieldset {
			padding: 8px !important;
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			margin: 0 !important;
			border: 1px solid #6c757d !important;
			border-width: 1px !important;
			height: 100% !important;
			border-style: solid !important;
			border-color: #6c757d !important;
			border-radius: 6px !important;
			background: #DFE9F6 !important;
			overflow: hidden;
		}
		.balance-form-section > div FIELDSET legend,
		.balance-form-section > div .balance-fieldset legend,
		.balance-form-section > div fieldset legend { font-size: 13px !important; padding: 4px 10px !important; border: none !important; background: transparent !important; font-weight: 600 !important; color: #495057 !important; }
		/* Sección de niveles - expandir a todo el ancho */
		.balance-form-section > div:nth-child(2) { width: 100%; max-width: 100%; }
		/* Estilos para el campo de utilidad */
		.balance-fieldset input[type="radio"] { margin-right: 3px; }
		.balance-fieldset label[for="cta1"],
		.balance-fieldset label[for="cta2"] { margin-right: 8px; }
		.balance-fieldset .bg { display: inline-flex; align-items: center; justify-content: flex-start; margin-left: 3px; }
		.info { display: inline-block; color: #2569ff; font-size: 20px; border-radius: 50%; border: solid 3px #161bab; width: 20px; height: 20px; font-weight: bold; text-align: center; cursor: help; }
		/* Aumentar el ancho de las celdas de la tabla de Utilidad en 25% */
		.balance-form-section > div:nth-child(3) table td { padding: 0 8px !important; width: auto; }
		.balance-form-section > div:nth-child(3) table td:first-child { width: auto; padding-right: 10px !important; padding-left: 5px !important; }
		.balance-form-section > div:nth-child(3) table td:nth-child(2) { width: auto; padding-right: 10px !important; padding-left: 5px !important; }
		.balance-form-section > div:nth-child(3) table td:last-child { width: auto; padding-left: 8px !important; padding-right: 5px !important; }
		/* Alinear todo el contenido de Utilidad a la izquierda */
		.balance-form-section > div:nth-child(3) table { width: auto; }
		.balance-form-section > div:nth-child(3) table td { padding: 0 5px; text-align: left; }
		@media (max-width: 1024px) { .balance-form-section { grid-template-columns: 1fr 1fr; } }
		@media (max-width: 768px) { .balance-form-section { grid-template-columns: 1fr; } }
		.balance-fieldset {
			background: #DFE9F6 !important;
			border: 1px solid #6c757d !important;
			border-width: 1px !important;
			border-style: solid !important;
			border-color: #6c757d !important;
			border-radius: 6px !important;
			padding: 8px !important;
			margin: 0 !important;
			width: 100%;
			box-sizing: border-box;
		}
		.balance-fieldset legend {
			background: transparent !important;
			padding: 4px 10px !important;
			border-radius: 0 !important;
			border: none !important;
			font-weight: 600;
			color: #495057;
			font-size: 13px !important;
		}
		.balance-results-legend { border: none !important; background: transparent !important; padding: 0 !important; }
		/* Aumentar tamaño de letra en los fieldsets */
		.balance-fieldset label,
		.balance-fieldset .Etiqueta1,
		.balance-fieldset .Titulos2 { font-size: 14px; }
		.balance-fieldset select,
		.balance-fieldset input[type="text"] { font-size: 14px; }
		/* Hacer más angostos los campos de fecha */
		.balance-form-section > div:nth-child(1) input[type="text"][id*="fec"],
		.balance-form-section > div:nth-child(1) input[type="text"][name*="fec"],
		.balance-form-section > div:nth-child(1) input[type="text"][id="txt_fec_ini"],
		.balance-form-section > div:nth-child(1) input[type="text"][id="txt_fec_fin"] {
			width: 90px !important;
			max-width: 90px !important;
			padding: 4px 6px !important;
		}
		/* Botones modernos */
		.balance-actions { display: flex; gap: 15px; margin-top: 8px; margin-bottom: 10px; flex-wrap: wrap; }
		.btn-modern {
			padding: 6px 18px;
			border-radius: 6px;
			border: none;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.3s ease;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			line-height: 1.0;
			font-size: 13px;
			max-width: fit-content;
			box-sizing: border-box;
		}
		
		.btn-modern span { line-height: 1.0; }
		.btn-modern i { line-height: 1.0; }
		.btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
		.btn-success-modern { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; }
		.btn-primary-modern { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; }
		/* Resultados modernos */
		.balance-results {
			background: #DFE9F6;
			border-radius: 6px;
			border: 1px solid #6c757d;
			padding: 12px;
			margin-top: 10px;
			width: 100%;
			box-sizing: border-box;
			max-width: 100%;
		}
		
		.balance-date-range {
			background: #DFE9F6;
			padding: 4px 0;
			border-radius: 0px;
			margin-bottom: 4px;
			text-align: left;
			line-height: 1.2;
			font-size: 12px;
		}
		
		.balance-date-range strong {
			color: #495057;
			margin-right: 5px;
			font-size: 12px;
			line-height: 1.2;
		}
		/* Tabla del balance mejorada */
		.balance-table-container {
			overflow-x: auto;
			overflow-y: visible;
			margin-top: 0px;
			background: #DFE9F6;
			border-radius: 6px;
			padding: 0px;
			width: 100%;
			box-sizing: border-box;
			max-width: 100%;
		}
		.balance-table-container table { width: 100% !important; max-width: 100% !important; table-layout: auto !important; }
		.balance-table-container table#Exportar_a_Excel { width: 100% !important; max-width: 100% !important; }
		.balance-table {
			width: 100% !important;
			max-width: 100% !important;
			border-collapse: collapse;
			font-size: 11px;
			table-layout: auto !important;
		}
		table.balance-table#Exportar_a_Excel { width: 100% !important; max-width: 100% !important; }
		.balance-table td {
			padding: 3px 8px !important;
			border-bottom: none;
			text-align: left;
			vertical-align: top;
			line-height: 1.1;
			word-wrap: break-word;
			overflow-wrap: break-word;
		}
		/* Segunda columna (descripción) - permitir que se ajuste y se vea completo */
		.balance-table td:nth-child(2) { min-width: 150px; max-width: none; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
		.balance-table tr:hover { background: transparent; }
		/* Código de cuenta - más grande y alineado a la izquierda */
		.balance-table td:first-child {
			font-weight: 400;
			color: #254463;
			font-size: 11px;
			text-align: left;
			padding-right: 8px !important;
			padding-left: 8px !important;
			white-space: nowrap;
			max-width: 80px;
		}
		/* Descripción de cuenta - más grande y alineada a la izquierda */
		.balance-table td:nth-child(2) { font-size: 11px; text-align: left; color: #212529; font-weight: 400; }
		/* Valores numéricos - alineados a la derecha */
		.balance-table td:last-child { text-align: left !important; font-weight: 500; color: #212529; font-size: 11px; }
		/* Celdas intermedias (para niveles) */
		.balance-table td:not(:first-child):not(:nth-child(2)):not(:last-child) { text-align: left; }
		/* Valores negativos en rojo */
		.balance-negative { color: #dc3545 !important; font-weight: 600; }
		/* Totales destacados */
		.balance-total-row { background: #b8daff !important; border-top: 2px solid #254463; font-weight: 700 !important; }
		.balance-total-row td {
			padding: 6px 15px;
			font-size: 15px;
			color: #000000 !important;
			text-align: left;
			line-height: 1.2;
			font-weight: 700 !important;
		}
		/* Asegurar que todos los totales estén en negrita */
		.balance-table-container table.LetraNegra tr td strong,
		.balance-table-container table.LetraNegra tr td u { font-weight: 700 !important; }
		.balance-total-row td:last-child { text-align: right !important; }
		/* Asegurar que los totales con h2 y h3 estén alineados a la derecha */
		.balance-table-container table.LetraNegra tr td[align="right"] { text-align: right !important; }
		.balance-table-container table.LetraNegra tr td[align="right"] h2,
		.balance-table-container table.LetraNegra tr td[align="right"] h3 { text-align: right !important; font-weight: 700 !important; }
		.balance-table td[align="right"] h2,
		.balance-table td[align="right"] h3 {
			text-align: right !important;
			font-weight: 700 !important;
		}
		/* Totales - aplicar negrita a todos los elementos dentro de celdas con align="right" */
		.balance-table-container table.LetraNegra tr td[align="right"] *,
		.balance-table td[align="right"] * { font-weight: 700 !important; }
		/* Estilos para elementos dentro de la tabla del balance */
		.balance-table h2,
		.balance-table h3 { text-align: left; margin: 0; font-weight: 400; }
		.balance-table h2 { font-size: 16px; font-weight: 400; }
		.balance-table h3 { font-size: 15px; font-weight: 400; }
		.balance-table-container table.LetraNegra h2,
		.balance-table-container table.LetraNegra h3 { font-weight: 400; }
		/* Totales con h2 y h3 en negrita - sobrescribir para totales */
		.balance-total-row h2,
		.balance-total-row h3,
		.balance-table-container table.LetraNegra .balance-total-row h2,
		.balance-table-container table.LetraNegra .balance-total-row h3,
		.balance-table-container table.LetraNegra tr td[align="right"] h2,
		.balance-table-container table.LetraNegra tr td[align="right"] h3,
		.balance-table tr td[align="right"] h2,
		.balance-table tr td[align="right"] h3 { font-weight: 700 !important; }
		/* Asegurar que todo el contenido de la tabla esté alineado a la izquierda por defecto */
		.balance-table-container table { text-align: left; }
		.balance-table-container table td { text-align: left; }
		.balance-table-container table td[align="right"] { text-align: left !important; }
		/* Asegurar que todas las celdas con valores numéricos estén a la derecha */
		.balance-table-container table.LetraNegra td:last-child { text-align: left !important; }
		.balance-table-container table.LetraNegra td[align="right"] { text-align: left !important; }
		/* Estilos específicos para la tabla generada por cargarNodosBalance */
		.balance-table-container table.LetraNegra { font-size: 13px; }
		.balance-table-container table.LetraNegra td {
			font-size: 12px;
			text-align: left;
			padding: 3px 8px !important;
			line-height: 1.1;
			word-wrap: break-word;
			overflow-wrap: break-word;
		}
		/* Segunda columna (descripción) en LetraNegra - permitir que se ajuste y se vea completo */
		.balance-table-container table.LetraNegra td:nth-child(2) { min-width: 150px; max-width: none; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
		/* Primera columna (código de cuenta) - más grande */
		.balance-table-container table.LetraNegra td:first-child {
			font-size: 14px;
			font-weight: 400;
			color: #254463;
			text-align: left;
			padding-right: 8px !important;
			padding-left: 8px !important;
			white-space: nowrap;
			max-width: 80px;
		}
		/* Segunda columna (descripción) - más grande */
		.balance-table-container table.LetraNegra td:nth-child(2) { font-size: 12px; text-align: left; color: #212529; border-spacing: 0px; }
		/* Texto en negrita dentro de la tabla - totales en negrita y color negro */
		.balance-table-container table.LetraNegra strong { font-size: 12px; font-weight: 700 !important; color: #000000 !important; }
		/* Texto subrayado - totales en negrita y color negro */
		.balance-table-container table.LetraNegra u { font-size: 12px; font-weight: 700 !important; color: #000000 !important; }
		/* Asegurar que las filas con TOTAL estén en negrita y color negro */
		.balance-table-container table.LetraNegra tr td strong,
		.balance-table-container table.LetraNegra tr td u,
		.balance-table-container table.LetraNegra tr td strong *,
		.balance-table-container table.LetraNegra tr td u * { font-weight: 700 !important; color: #000000 !important; }
		/* Acciones de exportación */
		.balance-export-actions { display: flex; gap: 15px; margin-top: 15px; padding-top: 12px; border-top: 1px solid #6c757d; }
		/* Mejoras para inputs y selects */
		.balance-fieldset select,
		.balance-fieldset input[type="text"] { border: 1px solid #ced4da; border-radius: 4px; padding: 8px 12px; transition: border-color 0.3s ease; }
		.balance-fieldset select:focus,
		.balance-fieldset input[type="text"]:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); }
		/* Responsive */
		@media (max-width: 768px) {
			.balance-container { padding: 10px; }
			.balance-card { padding: 15px; }
			.balance-header { padding: 15px; margin: -15px -15px 15px -15px; }
			.balance-actions,
			.balance-export-actions { flex-direction: column; }
			.btn-modern { width: 100%; justify-content: center; }
			.balance-form-section { grid-template-columns: 1fr; }
		}
	</style>
	</HEAD>

<BODY>
<div id="set1">
	<div class="balance-container">
		<div class="balance-card">
			<div class="balance-header">
				<h2><span>&raquo;</span> Estado de Resultado Integral <?php echo isset($periodo) ? $periodo : ''; ?></h2>
			</div>
			<div style="padding: 15px;">
				<form name="form1" method="post" action="<?php  echo $_SERVER['PHP_SELF']; ?>">
					<?php
					/* Control para la elección del periodo contable */
					if (!isset($hdd_save) && !isset($hdd_save2)) {
					?>		
					<FIELDSET class="balance-fieldset">
						<LEGEND class="balance-results-legend">
							<label class="Titulos2">Selección Periodo Contable</label>
						</LEGEND>
						<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<label class="Etiqueta1" style="margin: 0;">Periodo:&nbsp;</label>
								<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $perio['Pec_Fei']; ?>" />
								<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $perio['Pec_Fef']; ?>" />
								<select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)" style="min-width: 120px; width: 120px;">
									<?php 
									if (count($rs_periodos) > 0) {
										foreach($rs_periodos as $row_rs_periodo) {
									?>
									<option value="<?php echo $row_rs_periodo['Pec_Cod'].'*'.$row_rs_periodo['Pec_Fei'].'*'.$row_rs_periodo['Pec_Fef'].'*'.$row_rs_periodo['Pla_Cod']; ?>"><?php echo $row_rs_periodo['Periodo']; ?></option>
									<?php		
										}
									} else { ?>//Fin del if ($total_rs_periodo > 0)
									<option value=""></option>
									<?php
									}
									?>
								</select>
							</div>
							<div>
								<button type="button" class="btn btn-success fileinput-button btn-modern btn-success-modern" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> 
									<i class="icon-search icon-white"></i> 
									<span>Buscar</span>
								</button>
								<input name="hdd_save2" type="hidden" id="hdd_save2" />
							</div>
						</div>
					</FIELDSET>			 
					<?php 
					}//Fin del if (!isset($hdd_save) && !isset($hdd_save2))
					if (isset($Pec_Cod)) {
						/** * Divide la cadena del periodo contable */
						$arreglo = explode("*",$Pec_Cod); 
						$Pla_Cod = $arreglo[3];		
					?>		
					<div class="balance-form-section">
						<div><?php include("../COMPONENTES/con_con_anio_mes_fecha_v2.php"); ?></div>
						<div>
							<FIELDSET class="balance-fieldset">
								<LEGEND>
									<label class="Etiqueta1">Seleccionar Sucursal</label>
								</LEGEND>
								<table width="100%" border="0">
									<tr>
										<td class="Etiqueta1" style="white-space:nowrap; padding-right:4px;">Sucursal:</td>
										<td>
											<select name="cmb_sucursal" id="cmb_sucursal" style="width:100%;" disabled>
												<option value="">&lt;&lt; TODAS &gt;&gt;</option>
												<?php
												$rs_sucursales_epg = $obBD_con1->getArrayConsulta(245, $Ses_Emp_Cod, $obBD_conexion);
												foreach ($rs_sucursales_epg as $suc) {
													$sel_suc = (isset($cmb_sucursal) && $cmb_sucursal == $suc['Suc_Cod']) ? 'selected' : '';
													echo '<option value="' . $suc['Suc_Cod'] . '" ' . $sel_suc . '>' . $suc['Suc_Des'] . '</option>';
												}
												?>
											</select>
										</td>
									</tr>
								</table>
							</FIELDSET>
						</div>
						<div><?php include("../COMPONENTES/con_con_niveles_plan.php"); ?></div>
						<div>
							<FIELDSET class="balance-fieldset">  
								<LEGEND>
									<label class="Etiqueta1">Utilidad antes de participación e impuestos</label>
								</LEGEND>	
								<table width="100%" border="0">
									<tr>
										<td width="40%">
											<input type="radio" id="cta1" name="ctaUtilidad" value="si" <?php echo ($ctaUtilidad == 'si') ? 'checked' : '';?> >
											<label for="cta1">Si</label>
										</td>
										<td width="40%">
											<input type="radio" id="cta2" name="ctaUtilidad" value="no" <?php echo ($ctaUtilidad == 'no' or !isset($ctaUtilidad)) ? 'checked' : '';?>>
											<label for="cta2">No</label>
										</td>
										<td width="50%" class="bg">
											<i class="info" title="Configurar en parametrización - Estados Financieros - Configurar - Utilidad">&#8505;</i>
										</td>
									</tr>
								</table>
							</FIELDSET>
						</div>
					</div>

					<div class="balance-actions">
						<button type="button" class="btn btn-modern" title="Volver a selección de período"
						style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #ffffff; width: auto; padding: 6px 14px;"
						onclick="this.form.action='<?php echo $_SERVER['PHP_SELF']; ?>'; this.form.submit();">
						<i class="icon-arrow-left icon-white"></i>
						<span> Volver</span>
					</button>
					<button type="button" class="btn btn-success fileinput-button btn-modern btn-success-modern" title="Mostrar Estado de Perdidas y Ganancias" name="button" id="button" onClick="validar_balance(this.form, this.form.cmb_mes)">
						<i class="icon-check icon-white"></i>
						<span>Calcular</span>
					</button>      
						<input name="hdd_save" type="hidden" id="hdd_save" value="">
						<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
						<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
						<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">
						<input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
					</div>
					<?php
					}//Fin del if (isset($hdd_save2))
					?>
				</form>
					<?php
					if (isset($hdd_save)) {
					?>
					<FIELDSET class="balance-fieldset">  
						<LEGEND class="balance-results-legend">
							<label class="Titulos2">Resultados de la busqueda</label>
						</LEGEND>
						<div class="balance-date-range">
							<strong>Desde:</strong> <?php echo $txt_fec_ini; ?> 
							<strong style="margin-left: 20px;">Hasta:</strong> <?php echo $txt_fec_fin; ?>
							<?php if (isset($cmb_sucursal) && $cmb_sucursal != '') {
								$row_suc_epg = $obBD_con1->getRowConsulta(126, $cmb_sucursal, $obBD_conexion);
								$suc_nombre_epg = isset($row_suc_epg['Suc_Des']) ? $row_suc_epg['Suc_Des'] : $cmb_sucursal;
							?>
							<strong style="margin-left: 20px;">Sucursal:</strong> <?php echo htmlspecialchars($suc_nombre_epg); ?>
							<?php } ?>
						</div>
						
						<div class="balance-table-container">
							<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Exportar_a_Excel" class="balance-table">
								<tr class="LetraNegra">
									<td colspan="4"><?php
									/* Construir Pec_Cod2 completo para pasar a mayorización (formato: Pec_Cod~Pec_Fei~Pec_Fef~Pla_Cod) */
									$Pec_Cod2_completo = $arreglo[0] . '~' . (isset($arreglo[1]) ? $arreglo[1] : $Pec_Fei) . '~' . (isset($arreglo[2]) ? $arreglo[2] : $Pec_Fef) . '~' . $Pla_Cod;
									/* Obtener las fechas del período para pasarlas al método y determinar si son personalizadas */
									$Pec_Fei_periodo = isset($arreglo[1]) ? $arreglo[1] : $Pec_Fei;
									$Pec_Fef_periodo = isset($arreglo[2]) ? $arreglo[2] : $Pec_Fef;
									/* Sucursal seleccionada (vacío = todas) */
									$filtro_suc_epg = (isset($cmb_sucursal) && $cmb_sucursal != '') ? $cmb_sucursal : '';
									/* Carga los nodos del plan de cuentas */
									$obBD_con1->cargarNodosBalance($Pla_Cod,0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 2, $arreglo[0], 0, 0, 0, $Max_Niv, 2, $ctaUtilidad, $Pec_Cod2_completo, $Pec_Fei_periodo, $Pec_Fef_periodo, $filtro_suc_epg); 	
									?></td>
								</tr>
							</table>
						</div>
						
						<div class="balance-export-actions">
							<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form_volver" id="form_volver" style="display: inline-block;">
								<button type="submit" class="btn btn-modern" title="Volver a selección de período"
									style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #ffffff; width: auto; padding: 6px 14px; margin-left: 10px;">
									<i class="icon-arrow-left icon-white"></i>
									<span> Volver</span>
								</button>
							</form>

							<form action="con_pri_balest_p_g_1.2.php" method="post" name= "form2" id="form2" target="_blank">
								<button type="button" class="btn btn-primary start btn-modern btn-primary-modern" title="Imprimir Estado de Perdidas y Ganancias" onclick="this.form.submit()"> 
									<i class="icon-print icon-white"></i> 
									<span>Imprimir</span> 
								</button>             
								<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $arreglo[0]; ?>">
								<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
								<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
								<input name="Max_Niv" type="hidden" id="Max_Niv" value="<?php echo $Max_Niv; ?>">
								<input name="ctaUtilidad" type="hidden" id="ctaUtilidad" value="<?php echo $ctaUtilidad; ?>">
								<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>"> 
								<input name="utilidad" type="hidden" id="utilidad" value="<?php echo isset($utilidad) ? $utilidad : ''; ?>">
								<input name="cmb_sucursal" type="hidden" id="cmb_sucursal_print" value="<?php echo isset($cmb_sucursal) ? htmlspecialchars($cmb_sucursal) : ''; ?>">
							</form>
							<!-- BOTON DE EXPORTAR A EXCEL -->
							<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
								<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start btn-modern btn-primary-modern" title="Exportar Excel">
									<i class=" icon-share icon-white"></i>
									<span>Exportar a Excel</span>
								</button>         
								<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
							</form>
						</div>
					</FIELDSET>
					<?php
					}//if (isset($hdd_save))
					?>
			</div>
		</div>
	</div>
</div>
</BODY>
</HTML>
<?php 
	/* Cierra la Conexion */
	$obBD_conexion->cerrar();
?>
