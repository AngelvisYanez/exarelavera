<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php	
/**
* Descripcion: Permite consultar el balance de comprobacion
* Fecha de actualizacion:	2012-10-24
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_balances.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Con;

/* Obtencion de informacion de la empresa para cabeceras */
$row_institucion = $obBD_con1->getRowConsulta(126, $Ses_Suc_Cod, $obBD_conexion);
$row_provincia = $obBD_con1->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD_conexion);
$provincia_cab = (count($row_provincia) > 0) ? " - " . $row_provincia['Pro_Nom'] . ' - ' . $row_provincia['Pas_Nom'] : "";
$ubicacion_cab = $row_institucion['Ciu_Des'] . $provincia_cab;

/* Obtencion de parametros de busqueda */
$hdd_save = (isset($_POST['hdd_save']) ? $_POST['hdd_save'] : (isset($_GET['hdd_save']) ? $_GET['hdd_save'] : null));

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
		<TITLE><?php echo "Estado de Comprobacion [EXA]"; ?></TITLE>
        <meta charset= "UTF-8">
		<?php require_once("../../mascaras/model1/estilos/estilos.php"); ?>
		<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
		<script type="text/javascript" src="../VALIDACIONES/con_val_balances.js"></script>
		<script type="text/javascript" src="../../Librerias/validaciones/interfaz.js"></script>
		<script type="text/javascript"> 
			$(function() { $('#set1 *').tooltip({showURL: false}); });              			
		</script>        
		<style type="text/css">
			.modern-table {
				width: 100%;
				border-collapse: separate;
				border-spacing: 0;
				margin: 20px 0;
				background-color: #ffffff;
				border-radius: 12px;
				overflow: hidden;
				box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
				border: 1px solid #e1e8ed;
				font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			}
			.modern-table thead .header-main th {
				background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
				color: #ffffff;
				padding: 15px;
				font-size: 14px;
				text-transform: uppercase;
				letter-spacing: 1px;
				font-weight: 600;
				border: none;
			}
			.modern-table thead .header-main th:not(:first-child) {
				border-left: 1px solid rgba(255, 255, 255, 0.2);
			}
			.modern-table thead .header-sub th {
				background-color: #f8fafc;
				color: #475569;
				padding: 10px;
				font-size: 11px;
				font-weight: 700;
				text-transform: uppercase;
				border-bottom: 2px solid #e2e8f0;
				border-left: 2px solid #e2e8f0;
			}
			.modern-table thead .header-sub th:first-child {
				border-left: none;
			}
			.modern-table thead .header-sub th:last-child {
				border-right: none;
			}
			.modern-table tbody tr {
				transition: background-color 0.2s ease;
			}
			.modern-table tbody tr:nth-child(even) {
				background-color: #fdfdfd;
			}
			.modern-table tbody tr:hover {
				background-color: #f1f5f9;
			}
			.modern-table td {
				padding: 4px 15px;
				border-bottom: 2px solid #e2e8f0;
				color: #334155;
				font-size: 13px;
			}
			.modern-table .cell-code {
				white-space: nowrap;
				width: 100px;
			}
			.modern-table .cell-detail {
				text-align: center;
				font-weight: 500;
				border-left: 2px solid #e2e8f0;
			}
			.modern-table .code-text {
				font-weight: 800;
				color: #000000;
				font-family: 'Consolas', 'Monaco', monospace;
				font-size: 12px;
			}
			.modern-table .detail-text {
				color: #1e293b;
			}
			.modern-table .cell-number {
				text-align: right;
				font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
				font-weight: 500;
				color: #475569;
				border-left: 2px solid #e2e8f0;
			}
			.modern-table .cell-empty {
				color: #cbd5e1;
				text-align: center;
			}
			.modern-table .th-empty {
				background-color: #f8fafc !important;
				border-bottom: 2px solid #e2e8f0 !important;
			}
			.modern-table .row-inactive {
				background-color: #fff1f2 !important;
			}
			.modern-table .row-inactive .detail-text {
				color: #991b1b;
			}
			.modern-table tfoot .row-totales {
				background-color: #f8fafc;	
			}
			.modern-table tfoot .row-totales td {
				padding: 15px;
				border-top: 3px solid #1e3c72;
				font-weight: 800;
				font-size: 15px;
				color: #0f172a;
			}
			.modern-table tfoot .row-totales .cell-label {
				text-align: left;
				text-transform: uppercase;
				letter-spacing: 1px;
			}
			.th-left { text-align: left !important; }
			.th-center { text-align: center !important; }
			
			/* Report Header Info Classes */
			.report-header-info {
				display: flex;
				gap: 15px;
				flex-wrap: wrap;
				border-radius: 10px;
			}
			.info-pill {
				display: flex;
				align-items: center;
				background: #fff;
				padding: 6px 12px;
				border-radius: 20px;
				border: 1px solid #cbd5e1;
				box-shadow: 0 2px 4px rgba(0,0,0,0.03);
			}
			.info-label {
				font-size: 11px;
				font-weight: 700;
				color: #64748b;
				text-transform: uppercase;
				margin-right: 8px;
			}
			.info-value {
				font-size: 13px;
				font-weight: 600;
				color: #1e293b;
			}
			.report-table-wrapper {
				width: 100%;
				overflow-x: auto;
			}

			/* Adjust Fieldset style */
			fieldset {
				border-radius: 8px;
				padding: 15px;
			}
			legend {
				padding: 0 10px;
				font-weight: 600;
				color: #64748b;
				font-size: 0.9em;
			}
		</style>
		<!--meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"-->
	</HEAD>
	<BODY>
		<div id="set1">
			<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
				<tr class="BarraTitulo">
					<td height="10"><span class="">&nbsp;&raquo;</span> estado de comprobaci&oacute;n <?php echo $periodo; ?></td>
				</tr>
				<tr>
					<td height="389" valign="top">
						<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
							<?php
							/* Control para la eleccion del periodo contable */
							if (!isset($hdd_save) && !isset($hdd_save2)) {
							?>		
							<FIELDSET>
								<LEGEND> <label class="Titulos2">Selección Periodo Contable</label> </LEGEND>
								<table width="294" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
										<td width="115"><input name="Pec_Fei" id="Pec_Fei" type="hidden" style="text-align: center;" value="<?php echo $perio['Pec_Fei']; ?>" />
											<input name="Pec_Fef" id="Pec_Fef" type="hidden" style="text-align: center;" value="<?php echo $perio['Pec_Fef']; ?>" />
												<select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)">
													<?php 
													if (count($rs_periodos) > 0) {
														foreach($rs_periodos as $row_rs_periodo) {
														?>
															<option value="<?php echo $row_rs_periodo['Pec_Cod'].'*'.$row_rs_periodo['Pec_Fei'].'*'.$row_rs_periodo['Pec_Fef'].'*'.$row_rs_periodo['Pla_Cod']; ?>"><?php echo $row_rs_periodo['Periodo']; ?></option>
															<?php		
														}
													//Fin del if ($total_rs_periodo > 0)
													} else { ?>
														<option value=""></option>
														<?php
													}
													?>
												</select>
											</td>
										<td width="110" height="40" align="center"><button type="button" class="btn btn-success btn-mini" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Buscar</span></button>
										<input name="hdd_save2" type="hidden" id="hdd_save2" /></td>
										</tr>
								</table>
							</FIELDSET>			 
							<?php 
							}//Fin del if (!isset($hdd_save) && !isset($hdd_save2))
							if (isset($Pec_Cod)) {
								/* Divide la cadena del periodo contable */	
								$arreglo = explode("*",$Pec_Cod); 
								$Pla_Cod = $arreglo[3];	
							?>
							<table width="100%" border="0">
								<tr>
									<td width="70%"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
									<td width="30%"><input name="Max_Niv" value="20" type="text" style="display: none;" /> <?php //include("../COMPONENTES/con_con_niveles_plan.php"); ?></td>
								</tr>
							</table>
							<br>
							<table border="0">
								<tr>
									<td style="padding-right: 10px;">
										<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
											<button type="submit" class="btn btn-inverse" style="margin-left: 10px;" title="Volver a selección de período">
												<i class="icon-arrow-left icon-white"></i>
												<span>Volver</span>
											</button>
										</form>
									</td>
									<td>
									<button type="button" class="btn btn-success fileinput-button" title="Mostrar Balance de Comprobaci&oacute;n" name="button" id="button" onClick="validar_balance(this.form, this.form.cmb_mes)">
										<i class="icon-check icon-white"></i>
										<span>Calcular</span>
										</button>  
										<input name="hdd_save" type="hidden" id="hdd_save" value="">
										<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
										<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
										<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">
										<input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
									</td>
								</tr>
							</table>		
							<?php
							}//Fin del if (isset($hdd_save2))
							?>
						</form>
						<?php
						if (isset($hdd_save)) { 
						?>
						<FIELDSET class="results-fieldset">  
						<LEGEND><label class="Etiqueta1">Resultados de la búsqueda</label></LEGEND>
							<div id="Exportar" class="report-container">
								<!-- Header for Excel Export (Hidden on screen) -->
								<div class="excel-header" style="display: none;">
									<table width="100%" border="0" style="font-family: Arial, sans-serif;">
										<tr>
											<td align="center" colspan="6" style="font-size: 20px; font-weight: bold; color: #000;"><?php echo $row_institucion['Emp_Nom']; ?></td>
										</tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 13px; color: #333;"><strong>R.U.C.:</strong> <?php echo $row_institucion['Emp_Ruc']; ?> &nbsp; <strong>TELEFONO:</strong> <?php echo $row_institucion['Suc_Te1']; ?></td>
										</tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 13px; color: #333;"><strong>DIRECCION:</strong> <?php echo $row_institucion['Suc_Dir']; ?></td>
										</tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 13px; color: #333;"><strong>E-MAIL:</strong> <?php echo $row_institucion['Suc_Cor']; ?></td>
										</tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 13px; color: #333;"><?php echo $ubicacion_cab; ?></td>
										</tr>
										<tr><td colspan="6" style="height: 20px;">&nbsp;</td></tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 18px; font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px;">BALANCE DE SUMAS Y SALDOS</td>
										</tr>
										<tr>
											<td align="center" colspan="6" style="font-size: 14px; font-weight: bold; padding-bottom: 20px;">DESDE EL <?php echo $txt_fec_ini; ?> HASTA EL <?php echo $txt_fec_fin; ?></td>
										</tr>
									</table>
								</div>

								<div class="report-header-info">
									<div class="info-pill">
										<span class="info-label">Desde:</span>
										<span class="info-value"><?php echo $txt_fec_ini; ?></span>
									</div>
									<div class="info-pill">
										<span class="info-label">Hasta:</span>
										<span class="info-value"><?php echo $txt_fec_fin; ?></span>
									</div>
								</div>
								<div class="report-table-wrapper">
									<?php
									/* Consulta de la cuenta de utilidades */
									$row_utilidades = $obBD_con1->getRowConsulta(220, $Pec_Cod, $obBD_conexion);
									$utilidad = $row_utilidades['Pld_Cod'];
									/* Carga los nodos del plan de cuentas */
									$obBD_con1->cargarNodosComprobacion($Pla_Cod, 0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 3, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2);
									?>
								</div>
							</div>
						</FIELDSET>
						<br>
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td style="padding-right: 10px;">
									<button type="button" class="btn btn-inverse" style="margin-left: 10px;" title="Volver a selección de período" onclick="location.href='<?php echo $_SERVER['PHP_SELF']; ?>'">
										<i class="icon-arrow-left icon-white"></i>
										<span>Volver</span>
									</button>
								</td>
								<td width="100">
									<form action="con_pri_comprobaci_1.0.php" method="post" name= "form2" id="form2" target="_blank">
										<button type="button" class="btn btn-primary start" title="Imprimir Balance de Comprobaci&oacute;n" onclick="this.form.submit()"><i class="icon-print icon-white"></i><span> Imprimir</span></button>    
										<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $arreglo[0]; ?>">
										<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
										<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
										<input name="Max_Niv" type="hidden" id="Max_Niv" value="<?php echo $Max_Niv; ?>">
										<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>"> 
										<input name="utilidad" type="hidden" id="utilidad" value="<?php echo $utilidad; ?>" />
									</form>
								</td>
								<td width="170">
									<button name="Boton_Excel" onclick="exportReportExcel()" type="button" class="btn btn-primary start" title="Exportar Excel">
										<i class=" icon-share icon-white"></i><span> Exportar a Excel</span>
									</button>
								</td>
							</tr>
						</table>
						<?php
						}//if (isset($hdd_save))
						?>
					</td>
				</tr>
			</table>	
		</div>
		<script type="text/javascript">
			function exportReportExcel() {
				var content = document.getElementById('Exportar');
				var excelHeader = content.querySelector('.excel-header');
				var screenInfo = content.querySelector('.report-header-info');
				
				// Toggle visibility for export
				if(excelHeader) excelHeader.style.display = 'block';
				if(screenInfo) screenInfo.style.display = 'none';
				
				// Temporarily redefine the template to include styles
				var originalExportBlob = window.exportarExcelBlob;
				window.exportarExcelBlob = (function () {
					var template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><style>' +
					'.modern-table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }' +
					'.modern-table th, .modern-table td { border: 1px solid #000; padding: 5px; }' +
					'.header-main th { background-color: #1e3c72 !important; color: #ffffff !important; font-weight: bold; text-align: center; }' +
					'.header-sub th { background-color: #f8fafc; color: #475569; font-weight: bold; text-align: center; }' +
					'.cell-number { text-align: right; }' +
					'.cell-code { text-align: left; }' +
					'.cell-detail { text-align: center; }' +
					'.code-text { font-weight: bold; color: #000; }' +
					'.row-totales td { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #000; }' +
					'.th-empty { background-color: #f8fafc; border: none; border-bottom: 1px solid #000; }' +
					'</style></head><body>{table}</body></html>';
					var format = function (s, c) { return s.replace(/{(\w+)}/g, function (m, p) { return c[p]; }) };
					return function (table, name) {
						if (!table.nodeType) table = document.getElementById(table);
						var ctx = { worksheet: name || 'Reporte', table: table.innerHTML };
						return new Blob([format(template, ctx)], { type: 'application/vnd.ms-excel' });
					};
				})();

				// Perform export
				try {
					var blob = window.exportarExcelBlob('Exportar', 'Sumas y Saldos');
					downloadFile(blob, 'BalanceComprobacion-' + getDate() + '.xls');
				} catch (e) {
					console.error("Excel export error:", e);
				} finally {
					// Restore state
					if(excelHeader) excelHeader.style.display = 'none';
					if(screenInfo) screenInfo.style.display = 'flex';
					window.exportarExcelBlob = originalExportBlob;
				}
			}
		</script>
		<script type="text/ecmascript" src="../../Librerias/scripts/generales/ReportPrint.js"></script> 
	</BODY>
</HTML>
<?php 
	/* Cierre de la conexion */
	$obBD_conexion->cerrar();
?>