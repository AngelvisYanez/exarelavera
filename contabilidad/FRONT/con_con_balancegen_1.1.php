<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Descripcion: Permite consultar el balance general
 * Fecha de actualizacion:	2012-10-24
 * Desarrollador:	Lewis Chimarro 
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_balances.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
 * Configuración para aumentar el tiempo de ejecución del script
 * Útil cuando se procesan grandes volúmenes de datos contables
 */
set_time_limit(300); // 5 minutos (300 segundos)
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M'); // Aumentar también el límite de memoria si es necesario

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

/* Consulta todos los periodos activos */
if (!isset($Pec_Cod)) {
	/* Carga el periodos contable actual */
	$rs_periodos = $obBD_con1->getArrayConsulta(219, $Ses_Emp_Cod, $obBD_conexion);
	$perio = current($rs_periodos);
} else {
	/* Descripcion del periodo contable */
	$periodo = "en el periodo contable " . substr($Pec_Fei, 0, 4);
}
?>

<HTML>

<HEAD>
	<!--TITLE><?php echo $Ses_Sys_Nom; ?></TITLE-->
	<TITLE><?php echo "Estado Situacion Financiera [EXA]"; ?></TITLE>
	<meta charset="UTF-8">
	<meta http-equiv="Content-Type">
	<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<script type="text/javascript" src="../VALIDACIONES/con_val_balances.js"></script>
	<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
	<script type="text/javascript">
		$(function() {
			$('#set1 *').tooltip({
				showURL: false
			});
		});
	</script>
	<!--Librerias para exportar a excel -->
	<script type="text/javascript">
		$(document).ready(function() {
			/* LLamado a la class del boton exportar */
			$("#Boton_Excel").click(function(event) {
				$("#datos_a_enviar").val($("<div>").append($("#Exportar_a_Excel").eq(0).clone()).html());
				$("#FormularioExportacion").submit();
			});
		});
	</script>
	<!-- Estilos personalizados para Balance General -->
	<style>
		/* Contenedor principal moderno */
		.balance-container { background: #DFE9F6; padding: 0; min-height: 100vh;
		}
		/* Card moderna para el contenido */
		.balance-card { background: #DFE9F6; border-radius: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 0; margin-bottom: 10px; overflow: hidden; }
		/* Header moderno */
		.balance-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: #ffffff; padding: 12px 15px; border-radius: 0; margin: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box; }
		.balance-header h2 { margin: 0; font-size: 14px; font-weight: 600; display: flex; align-items: center; }
		.balance-header h2 span { margin-right: 8px; font-size: 18px; }
		/* Formularios modernos */
		.balance-form-section { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 8px; }
		@media (max-width: 768px) { .balance-form-section { grid-template-columns: 1fr; } }
		.balance-fieldset { background: #DFE9F6; border: 1px solid #6c757d; border-radius: 6px; padding: 12px; }
		.balance-fieldset legend { background: #DFE9F6; padding: 8px 15px; border-radius: 4px; border: 1px solid #6c757d; font-weight: 600; color: #495057; font-size: 15px; }
		.balance-results-legend { border: none !important; background: transparent !important; padding: 0 !important; }
		/* Aumentar tamaño de letra en los fieldsets */
		.balance-fieldset label,
		.balance-fieldset .Etiqueta1,
		.balance-fieldset .Titulos2 { font-size: 14px; }
		.balance-fieldset select,
		.balance-fieldset input[type="text"] { font-size: 14px; }
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
		.balance-results { background: #DFE9F6; border-radius: 6px; border: 1px solid #6c757d; padding: 12px; margin-top: 10px; width: 100%; box-sizing: border-box; max-width: 100%; }
		
		.balance-results-header { display: flex; justify-content: flex-start; align-items: flex-start; flex-direction: column; padding-bottom: 6px; border-bottom: 1px solid #6c757d; margin-bottom: 6px; margin-top: 0; line-height: 1.2; }
		
		.balance-results-header h3 { margin: 0 0 4px 0; color: #254463; font-size: 15px; font-weight: 600; text-align: left; line-height: 1.2; }
		.balance-results-header::before { content: ''; display: none; }
		.balance-date-range { background: #DFE9F6; padding: 4px 0; border-radius: 0px; margin-bottom: 4px; text-align: left; line-height: 1.2; font-size: 12px; }
		.balance-date-range strong { color: #495057; margin-right: 5px; font-size: 12px; line-height: 1.2; }
		/* Fondo celeste para la tabla del balance */
		.balance-table-container { background: #e7f3ff; border-radius: 6px; padding: 15px; }
		/* Tabla del balance mejorada */
		.balance-table-container { overflow-x: auto; overflow-y: visible; margin-top: 0px; background: #DFE9F6; border-radius: 6px; padding: 0px; width: 100%; box-sizing: border-box; max-width: 100%;
		}
		.balance-table-container table { width: 100% !important; max-width: 100% !important; table-layout: auto !important; }
		.balance-table-container table#Exportar_a_Excel { width: 100% !important; max-width: 100% !important; }
		/* Alinear mensaje de Estado a la izquierda */
		.balance-table-container table.LetraNegra tr:first-child td { text-align: left ; padding: -1px 15px !important; line-height: 1.0 !important; }
		.balance-table-container table.LetraNegra tr:first-child { text-align: left !important; }
		.balance-table-container table.LetraNegra tr:first-child td[colspan] { text-align: left ; padding: 2px 15px !important; line-height: 1.0 !important; }
		.balance-table-container hr { margin: 5px 0; border: none; border-top: 1px solid #6c757d; }
		/* Forzar alineación a la izquierda para el mensaje de Estado */
		.balance-table-container table.LetraNegra tr:first-child td * { text-align: left !important; }
		.balance-table { width: 100% !important; max-width: 100% !important; border-collapse: collapse; font-size: 11px; table-layout: auto !important; }
		table.balance-table#Exportar_a_Excel { width: 100% !important; max-width: 100% !important; }
		.balance-table td { padding: 3px 8px !important; border-bottom: none; text-align: left; vertical-align: top; line-height: 1.1; word-wrap: break-word; overflow-wrap: break-word; }
		/* Segunda columna (descripción) - permitir que se ajuste y se vea completo */
		.balance-table td:nth-child(2) { min-width: 150px; max-width: none; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
		.balance-table tr:hover { background: transparent; }
		/* Código de cuenta - más grande y alineado a la izquierda */
		.balance-table td:first-child { font-weight: 400; color: #254463; font-size: 11px; text-align: left !important; padding-right: 8px !important; padding-left: 8px !important; white-space: nowrap; max-width: 80px; }
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
		.balance-total-row td { padding: 6px 15px; font-size: 15px; color: #000000 !important; text-align: left; line-height: 1.2; font-weight: 700 !important; }
		.balance-total-row td:last-child { text-align: right !important; }
		/* Asegurar que los totales con h2 y h3 estén alineados a la derecha */
		.balance-table-container table.LetraNegra tr td[align="right"] { text-align: right !important; }
		.balance-table-container table.LetraNegra tr td[align="right"] h2,
		.balance-table-container table.LetraNegra tr td[align="right"] h3 { text-align: right !important; font-weight: 700 !important; }
		.balance-table td[align="right"] h2,
		.balance-table td[align="right"] h3 { text-align: right !important; font-weight: 700 !important; }
		
		/* Totales - aplicar negrita a todos los elementos dentro de celdas con align="right" */
		.balance-table-container table.LetraNegra tr td[align="right"] *,
		.balance-table td[align="right"] * {
			font-weight: 700 !important;
		}
		
		/* Estilos para elementos dentro de la tabla del balance */
		.balance-table h2, .balance-table h3 { text-align: left; margin: 0; font-weight: 400; }
		
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
		.balance-table tr td[align="right"] h3 {
			font-weight: 700 !important;
		}
		
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
		.balance-table-container table.LetraNegra td:nth-child(2) {
			min-width: 150px;
			max-width: none;
			word-wrap: break-word;
			overflow-wrap: break-word;
			white-space: normal;
		}
		
		/* Primera columna (código de cuenta) - más grande */
		.balance-table-container table.LetraNegra td:first-child {
			font-size: 14px;
			font-weight: 400;
			color: #254463;
			text-align: left !important;
			padding-right: 8px !important;
			padding-left: 8px !important;
			white-space: nowrap;
			max-width: 80px;
		}
		
		/* Segunda columna (descripción) - más grande */
		.balance-table-container table.LetraNegra td:nth-child(2) {
			font-size: 12px;
			text-align: left;
			color: #212529;
			border-spacing: 0px;
			min-width: 0;
			max-width: none;
			word-wrap: break-word;
			overflow-wrap: break-word;
		}
		
		/* Texto en negrita dentro de la tabla - totales en negrita y color negro */
		.balance-table-container table.LetraNegra strong {
			font-size: 12px;
			font-weight: 700 !important;
			color: #000000 !important;
		}
		
		/* Texto subrayado - totales en negrita y color negro */
		.balance-table-container table.LetraNegra u {
			font-size: 12px;
			font-weight: 700 !important;
			color: #000000 !important;
		}
		
		/* Asegurar que las filas con TOTAL estén en negrita y color negro */
		.balance-table-container table.LetraNegra tr td strong,
		.balance-table-container table.LetraNegra tr td u,
		.balance-table-container table.LetraNegra tr td strong *,
		.balance-table-container table.LetraNegra tr td u * {
			font-weight: 700 !important;
			color: #000000 !important;
		}
		
		/* Estado del balance */
		.balance-status {
			display: inline-block;
			padding: 8px 16px;
			border-radius: 20px;
			font-weight: 600;
			font-size: 13px;
			margin-top: 10px;
		}
		
		.balance-status.cuadrado {
			background: #d4edda;
			color: #155724;
		}
		
		.balance-status.descuadrado {
			background: #f8d7da;
			color: #721c24;
		}
		
		/* Acciones de exportación */
		.balance-export-actions {
			display: flex;
			gap: 15px;
			margin-top: 15px;
			padding-top: 12px;
			border-top: 1px solid #6c757d;
		}
		
		/* Mejoras para inputs y selects */
		.balance-fieldset select,
		.balance-fieldset input[type="text"] {
			border: 1px solid #ced4da;
			border-radius: 4px;
			padding: 8px 12px;
			transition: border-color 0.3s ease;
		}
		
		.balance-fieldset select:focus,
		.balance-fieldset input[type="text"]:focus {
			border-color: #007bff;
			outline: none;
			box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
		}
		
		/* Responsive */
		@media (max-width: 768px) {
			.balance-container { padding: 10px; }
			.balance-card { padding: 15px; }
			.balance-header { padding: 15px; margin: -15px -15px 15px -15px; }
			.balance-actions,
			.balance-export-actions { flex-direction: column; }
			.btn-modern { width: 100%; justify-content: center; }
		}
	</style>
	<!--meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"-->

</HEAD>

<BODY>
	<div id="set1">
		<div class="balance-container">
			<div class="balance-card">
				<div class="balance-header">
					<h2><span>&raquo;</span> Estado de Situación Financiera <?php echo isset($periodo) ? $periodo : ''; ?></h2>
				</div>
				<div style="padding: 15px;">
					<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<?php
						/**
						 * Control para la elecci�n del periodo contable 
						 */
						if (!isset($hdd_save) && !isset($hdd_save2)) {
						?>
							<FIELDSET class="balance-fieldset">
								<LEGEND class="balance-results-legend">
									<label class="Titulos2">Selección Periodo Contable</label>
								</LEGEND>
								<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
									<div style="display: flex; align-items: center; gap: 10px;">
										<label class="Etiqueta1" style="margin: 0;">Periodo:</label>
										<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $perio['Pec_Fei']; ?>" />
										<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $perio['Pec_Fef']; ?>" />
										<select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)" style="min-width: 120px; width: 120px;">
											<?php
											foreach ($rs_periodos as $row) {
											?>
												<option value="<?php echo $row['Pec_Cod'] . '*' . $row['Pec_Fei'] . '*' . $row['Pec_Fef'] . '*' . $row['Pla_Cod']; ?>"><?php echo $row['Periodo']; ?></option>
											<?php
											}
											?>
										</select>
									</div>
									<div>
										<button type="button" class="btn btn-success fileinput-button btn-modern btn-success-modern" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> 
											<i class="icon-search icon-white"></i> 
											<span>Aceptar</span>
										</button>
										<input name="hdd_save2" type="hidden" id="hdd_save2" />
									</div>
								</div>
							</FIELDSET>
						<?php
						} //Fin del if (!isset($hdd_save) && !isset($hdd_save2))

						/**
						 * Ingresa una vez selecionado el periodo 
						 */
						if (isset($Pec_Cod)) {
							/**
							 * Divide la cadena del periodo contable 
							 */
							$arreglo = explode("*", $Pec_Cod);
							$Pla_Cod = $arreglo[3];
						?>
							<div class="balance-form-section">
								<div><?php include("../COMPONENTES/con_con_anio_mes_fecha_v2.php"); ?></div>
								<div><?php include("../COMPONENTES/con_con_niveles_plan.php"); ?></div>
							</div>
							
							<div class="balance-actions">
								<button type="button" class="btn btn-success fileinput-button btn-modern btn-success-modern" title="Mostrar Balance General" name="button" id="button" onClick="validar_balance(this.form, this.form.cmb_mes)">
									<i class="icon-check icon-white"></i>
									<span>Calcular</span>
								</button>
								<input name="hdd_save" type="hidden" id="hdd_save" value="">
								<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
								<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
								<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">
								<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>">
								<input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
							</div>
						<?php
						} //Fin del if (isset($hdd_save2))
						?>
					</form>
					
					<?php
					if (isset($hdd_save)) {
					?>
						<FIELDSET class="balance-fieldset">
							<LEGEND class="balance-results-legend">
								<label class="Titulos2">Resultados de la Búsqueda</label>
							</LEGEND>
							<div class="balance-date-range">
								<strong>Desde:</strong> <?php echo $txt_fec_ini; ?> 
								<strong style="margin-left: 20px;">Hasta:</strong> <?php echo $txt_fec_fin; ?>
							</div>
							
							<div class="balance-table-container">
								<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Exportar_a_Excel" class="balance-table">
									<tr class="LetraNegra">
										<td colspan="4" style="padding: 0;">
											<?php
												/* Consulta de la cuenta de utilidades */
												$row_utilidades = $obBD_con1->getRowConsulta(220, $Pec_Cod, $obBD_conexion);
												$utilidad = $row_utilidades['Pld_Cod'];
												/* Construir Pec_Cod2 completo para pasar a mayorización (formato: Pec_Cod~Pec_Fei~Pec_Fef~Pla_Cod) */
												$Pec_Cod2_completo = $arreglo[0] . '~' . (isset($arreglo[1]) ? $arreglo[1] : $Pec_Fei) . '~' . (isset($arreglo[2]) ? $arreglo[2] : $Pec_Fef) . '~' . $Pla_Cod;
												/* Obtener fechas del período para comparar si son personalizadas */
												$Pec_Fei_periodo = isset($arreglo[1]) ? $arreglo[1] : $Pec_Fei;
												$Pec_Fef_periodo = isset($arreglo[2]) ? $arreglo[2] : $Pec_Fef;
												/* Carga los nodos del plan de cuentas */
												$obBD_con1->estadoBalance($Pla_Cod, 0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 1, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2);
												$obBD_con1->cargarNodosBalance($Pla_Cod, 0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 1, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2, $Pec_Cod2_completo, $Pec_Fei_periodo, $Pec_Fef_periodo);
											?>
										</td>
									</tr>
								</table>
							</div>
							
							<div class="balance-export-actions">
								<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form_volver" id="form_volver" style="display: inline-block;">
									<button type="submit" class="btn btn-modern" title="Volver a selección de período"
										style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #ffffff; width: auto; padding: 6px 14px;">
										<i class="icon-arrow-left icon-white"></i>
										<span> Volver</span>
									</button>
								</form>
								<form action="con_pri_balancegen_1.1.php" method="post" name="form2" id="form2" target="_blank">
									<button type="button" class="btn btn-primary start btn-modern btn-primary-modern" title="Imprimir Balance General" onclick="this.form.submit()"> 
										<i class="icon-print icon-white"></i> 
										<span>Imprimir</span> 
									</button>
									<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $arreglo[0]; ?>">
									<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>">
									<input name="utilidad" type="hidden" id="utilidad" value="<?php echo $utilidad; ?>">
									<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
									<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
									<input name="Max_Niv" type="hidden" id="Max_Niv" value="<?php echo $Max_Niv; ?>">
								</form>
								
								<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
									<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start btn-modern btn-primary-modern" title="Exportar Excel">
										<i class="icon-share icon-white"></i>
										<span>Exportar a Excel</span>
									</button>
									<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
								</form>
							</div>
						</FIELDSET>
					<?php
					} //if (isset($hdd_save))
					?>
					</form>
				</div>
			</div>
		</div>
	</div>
</BODY>

</HTML>
<?php
	/* Cierra las conexiones */
	@$obBD_con1->liberar();
	@$obBD_conexion->cerrar();
?>