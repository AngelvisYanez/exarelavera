<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * Descripci�n: Permite consultar el balance general
 * Fecha de actualizaci�n:	2012-10-24
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

/**
 * Creacion del Objeto de conexion 
 */
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/** 
 * Creacion del objeto mysql para las consultas 
 */
$obBD_con1 =  new Class_Log_Datos_Con;

/**
 * Consulta todos los periodos activos 
 */
if (!isset($Pec_Cod)) {
	/**
	 * Carga el periodos contable actual 
	 */
	$rs_periodos = $obBD_con1->getArrayConsulta(219, $Ses_Emp_Cod, $obBD_conexion);
	$perio = current($rs_periodos);
} else {
	/**
	 * Descripcion del periodo contable 
	 */
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
	<!--meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"-->

</HEAD>

<BODY>
	<div id="set1">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="table">
			<tr class="BarraTitulo">
				<td height="10"><span>&raquo;</span> Estado de Situación Financiera <?php echo $periodo; ?></td>
			</tr>
			<tr>
				<td valign="top" height="400">
					<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
						<?php
						/**
						 * Control para la elecci�n del periodo contable 
						 */
						if (!isset($hdd_save) && !isset($hdd_save2)) {
						?>
							<FIELDSET>
								<LEGEND>
									<label class="Titulos2">Selección Periodo Contable</label>
								</LEGEND>
								<table width="296" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td width="69" class="Etiqueta1">Periodo:&nbsp; </td>
										<td width="115"><input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $perio['Pec_Fei']; ?>" />
											<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $perio['Pec_Fef']; ?>" />
											<select name="Pec_Cod" id="Pec_Cod" onchange="javascript: asignar_fechas(this.value)">
												<?php
												foreach ($rs_periodos as $row) {
												?>
													<option value="<?php echo $row['Pec_Cod'] . '*' . $row['Pec_Fei'] . '*' . $row['Pec_Fef'] . '*' . $row['Pla_Cod']; ?>"><?php echo $row['Periodo']; ?></option>
												<?php
												}
												?>
											</select>
										</td>
										<td width="112" height="37" align="center">
											<button type="button" class="btn btn-success fileinput-button" title="Buscar" onclick="validar_requeridos(this.form, 'Pec_Cod', 0)"> <i class="icon-search icon-white"></i> <span>Aceptar</span></button>
											<input name="hdd_save2" type="hidden" id="hdd_save2" />
										</td>
									</tr>
								</table>
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
							<table width="100%" border="0">
								<tr>
									<td width="50%" valign="top"><?php include("../COMPONENTES/con_con_anio_mes_fecha.php"); ?></td>
									<td width="50%"><?php include("../COMPONENTES/con_con_niveles_plan.php"); ?></td>
								</tr>
							</table>
							<br>
							<table width="229" border="0">
								<tr>
									<td width="223">
										<button type="button" class="btn btn-success fileinput-button" title="Mostrar Balance General" name="button" id="button" onClick="validar_balance(this.form, this.form.cmb_mes)">
											<i class="icon-check icon-white"></i>
											<span>Calcular</span>
										</button>
										<input name="hdd_save" type="hidden" id="hdd_save" value="">
										<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $Pec_Fei; ?>">
										<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $Pec_Fef; ?>">
										<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?php echo $Pec_Cod; ?>">
										<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?php echo $Pla_Cod; ?>">
										<input name="hdd_ann" id="hdd_ann" type="hidden" value="<?php echo $Pec_Fei; ?>">
									</td>
								</tr>
							</table>
						<?php
						} //Fin del if (isset($hdd_save2))
						?>
					</form>
					<?php
					if (isset($hdd_save)) {
					?>
						<FIELDSET>
							<LEGEND>
								<label class="Etiqueta1">Resultados de la busqueda</label>
							</LEGEND>
							<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Exportar_a_Excel">
								<tr class="LetraNegra">
									<td colspan="4"><span class="Etiqueta1">Desde: </span><?php echo $txt_fec_ini; ?> &nbsp;<span class="Etiqueta1">Hasta:</span> <?php echo $txt_fec_fin; ?><br>
										<br>
									</td>
								</tr>
								<tr class="LetraNegra">
									<td colspan="4">
										<?php
										/**
										 * Consulta de la cuenta de utilidades 
										 */
										$row_utilidades = $obBD_con1->getRowConsulta(220, $Pec_Cod, $obBD_conexion);
										$utilidad = $row_utilidades['Pld_Cod'];


										/**
										 * Carga los nodos del plan de cuentas 
										 */
										$obBD_con1->estadoBalance($Pla_Cod, 0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 1, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2);
										$obBD_con1->cargarNodosBalance($Pla_Cod, 0, $txt_fec_ini, $txt_fec_fin, $obBD_conexion, 1, $arreglo[0], 0, $utilidad, 0, $Max_Niv, 2);

										?></td>
								</tr>
							</table>
						</FIELDSET>
						<br>
						<table border="0" cellpadding="0" cellspacing="0">
							<tr>
								<td width="111">
									<form action="con_pri_balancegen_1.1.php" method="post" name="form2" id="form2" target="_blank">
										<button type="button" class="btn btn-primary start" title="Imprimir Balance General" onclick="this.form.submit()"> <i class="icon-print icon-white"></i> <span>Imprimir</span> </button>
										<input name="Pec_Cod" type="hidden" id="Pec_Cod" value="<?php echo $arreglo[0]; ?>">
										<input name="Pla_Cod" type="hidden" id="Pla_Cod" value="<?php echo $Pla_Cod; ?>">
										<input name="utilidad" type="hidden" id="utilidad" value="<?php echo $utilidad; ?>">
										<input name="txt_fec_ini" type="hidden" id="txt_fec_ini" value="<?php echo $txt_fec_ini; ?>">
										<input name="txt_fec_fin" type="hidden" id="txt_fec_fin" value="<?php echo $txt_fec_fin; ?>">
										<input name="Max_Niv" type="hidden" id="Max_Niv" value="<?php echo $Max_Niv; ?>">
									</form>
								</td>
								<td width="137">
									<form action="../../Librerias/exportar/ficheroExcel.php" method="post" target="_blank" id="FormularioExportacion">
										<button name="Boton_Excel" id="Boton_Excel" type="button" class="btn btn-primary start" title="Exportar Excel">
											<i class=" icon-share icon-white"></i>
											<span>Excel</span>
										</button>
										<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" />
									</form>
								</td>
							</tr>
						</table>
					<?php
					} //if (isset($hdd_save))
					?>
				</td>
			</tr>
		</table>
	</div>
</BODY>

</HTML>
<?php
/**
 * Cierra las conexiones
 */
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>