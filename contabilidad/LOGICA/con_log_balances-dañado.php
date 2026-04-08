<?php

/**
 * Logica de las paginas de balances
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizacion:	2012-10-04
 *
 * @package contabilidad.LOGICA
 */
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_balances.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package contabilidad.LOGICA
 */

class Class_Log_Conexion_Con extends MysqlConexion {} //Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Con extends MysqlDatos
{
	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function consultasobBD($sen_sql, $param, $obBD)
	{
		$Par_Sql = $this->parametros($param);
		return $this->consulta(sentencias_con($sen_sql, $Par_Sql), $obBD->conexion);
	}

	/**
	 * Realiza una consulta en la base de datos -  STARDARD
	 *
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 * @return result si existen datos de retorno
	 */
	function operacionobBD($sen_sql, $param, $obBD)
	{
		$Par_Sql = $this->parametros($param);
		return $this->grabarv_registros(sentencias_con($sen_sql, $Par_Sql), $obBD->conexion);
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql, $param, $obBD)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);

		$row =  $this->fetch_assoc($result);

		$this->free_result($result);

		return $row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Con $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */
	function getArrayConsulta($sen_sql, $param, $obBD)
	{
		$result = $this->consultasobBD($sen_sql, $param, $obBD);

		$array = array();

		while ($row_rs = $this->fetch_assoc($result)) {
			$array[] = $row_rs;
		}

		$this->free_result($result);

		return $array;
	}

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Con $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql, $param, $obBD)
	{
		$this->inicio_transaccion($obBD->conexion);

		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql, $param, $obBD);

		$this->fin_transaccion($obBD->conexion);
	}

	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $titulo T�tulo del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo, $obBD)
	{
		/**
		 * Consulta de la cabecera del reporte 
		 */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/**
		 * Consulta la provicia y pais de la sucursal 
		 */
		$row_provincia = $this->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD);

?>
		<table width="98%" border="0" cellpadding="0" cellspacing="0">
			<tr align="center">
				<td width="12%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
				<td width="88%" class="TITULO_REPORTE_2"><?php echo $row_institucion['Emp_Nom']; ?></td>
			</tr>
			<tr align="center">
				<td valign="top" class="Texto_Reporte">
					<div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp; <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div>
				</td>
			</tr>
			<tr align="center">
				<td valign="top" class="Texto_Reporte">
					<div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div>
				</td>
			</tr>
			<tr align="center">
				<td valign="top" class="Texto_Reporte">
					<div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div>
				</td>
			</tr>
			<tr align="center">
				<td align="center" valign="top" class="Texto_Reporte">
					<div align="center"><?php
										if (count($row_provincia) > 0) {
											$provincia = " - " . $row_provincia['Pro_Nom'] . ' - ' . $row_provincia['Pas_Nom'];
										} else {
											$provincia = "";
										}
										echo $row_institucion['Ciu_Des'] . $provincia; ?></div>
				</td>
			</tr>
			<tr align="center">
				<td colspan="2" valign="top">
					<hr />
				</td>
			</tr>
			<tr align="center">
				<td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $titulo; ?></td>
			</tr>
			<tr align="center">
				<td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
			</tr>
		</table>
	<?php
	}

	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $usuario C�digo del usuario 
	 */
	function fechaImpresion($sucursal, $obBD)
	{
		/**
		 * Consulta de la cabecera del reporte 
		 */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/**
		 * Consulta los datos del usuario 
		 */
		if (isset($usuario)) $row_usuario = $this->getRowConsulta(4, $usuario, $obBD);

		$fecha = explode("-", date("Y-m-d"));
		$fechaHoy =	$row_institucion['Ciu_Des'] . ", " . $fecha[2] . " de " . mes($fecha[1], 1) . " " . $fecha[0] . ", " . date("H:m:s");

		echo $fechaHoy;
	}

	/** 
	 * Funcion que devuelve un arreglo de los reportes del proceso 
	 */
	function reportes($pagina, $empresa, $obBD_conexion)
	{
		$pag = explode("/", $pagina);
		$row_rs_proceso = $this->getRowConsulta(12, $pag[count($pag) - 1], $obBD_conexion);

		$row_rs_reporte = $this->getArrayConsulta(13, $row_rs_proceso['Pcs_Cod'] . '*' . $empresa, $obBD_conexion);

		$i = 0;
		foreach ($row_rs_reporte as $row) {
			$i++;
			$reporte[$i] = $row['Pcs_Nom'];
		}
		return $reporte;
	}


	function getCtaChild($cuentas, $Pld_Rec)
	{
		$plan = array();
		foreach ($cuentas as $value) {
			if ($value['Pld_Rec'] == $Pld_Rec) {
				array_push($plan, $value);
				$plan = array_merge($plan, $this->getCtaChild($cuentas, $value['Pld_Cod']));
			}
		}
		return $plan;
	}
	function getTotalNodo($cuenta, $valores, $tipo)
	{
		$total = array('debe' => 0, 'haber' => 0, 'saldo' => 0, 'asignar' => false);

		foreach ($valores as $value) {
			if ($value['Pld_Cdc'] == $cuenta && $tipo == 'D') {
				$total = array('debe' => $value['debe'], 'haber' => $value['haber'], 'saldo' => ($value['debe'] - $value['haber']));
				$total['asignar'] = ($total['saldo'] > 0.009 || $total['saldo'] < -0.009);
				return $total;
			}
			if (startsWith($value['Pld_Cdc'] . '.', $cuenta . '.') && $tipo == 'G') {
				$total['debe'] = $total['debe'] + $value['debe'];
				$total['haber'] = $total['haber'] + $value['haber'];
				$total['saldo'] = $total['saldo'] + $value['debe'] - $value['haber'];
				if ($total['asignar'] == false && ($total['saldo'] > 0.009 || $total['saldo'] < -0.009))
					$total['asignar'] = true;
			}
		}
		return $total;
	}
	function formatCuenta($cuenta, $cant_cuent, $total, $format)
	{
		$cuenta['Total'] = formato_numero(($total['debe'] - $total['haber']), 2, $format);
		$cant_esp = 0;
		for ($i = 1; $i <= count($cant_cuent) - 1; $i++) {
			$cant_esp = $cant_esp + 2;
		}
		if (startsWith($cuenta['Pld_Cdc'], '2') || startsWith($cuenta['Pld_Cdc'], '3') || startsWith($cuenta['Pld_Cdc'], '4')) {
			$total['saldo'] = $total['saldo'] * -1;
			$cuenta['Total'] = formato_numero(($total['debe'] - $total['haber']) * -1, 2, $format);
		}
		if ($total['saldo'] < 0) $cuenta['Total'] = '<font color="#FF0000" >' . $cuenta['Total'] . '</font>';

		if ($cuenta['Pld_Rec'] == 0) $cuenta['Pld_Des'] = '<u>' . $cuenta['Pld_Des'] . '</u>';
		if ($cuenta['Pld_Tip'] == 'G') {
			$cuenta['Pld_Des'] = '<b>' . $cuenta['Pld_Des'] . '</b>';
			$cuenta['Pld_Cdc'] = '<b>' . $cuenta['Pld_Cdc'] . '</b>';
			$cuenta['Total'] = '<b>' . $cuenta['Total'] . '</b>';
		}
		$cuenta['Pld_Des'] = str_repeat("&nbsp;", $cant_esp) . $cuenta['Pld_Des'];
		return $cuenta;
	}

	function formatCuentaDiario($cuenta, $cant_cuent, $total, $format)
	{
		$cuenta['Total'] = formato_numero(($total['debe'] - $total['haber']), 2, $format);
		$cant_esp = 0;
		for ($i = 1; $i <= count($cant_cuent) - 1; $i++) {
			$cant_esp = $cant_esp + 2;
		}
		if (startsWith($cuenta['Pld_Cdc'], '2') || startsWith($cuenta['Pld_Cdc'], '3') || startsWith($cuenta['Pld_Cdc'], '4')) {
			$total['saldo'] = $total['saldo'] * -1;
			$cuenta['Total'] = formato_numero(($total['debe'] - $total['haber']) * -1, 2, $format);
		}
		if ($total['saldo'] < 0) $cuenta['Total'] = $cuenta['Total'];

		if ($cuenta['Pld_Rec'] == 0) $cuenta['Pld_Des'] = '<u>' . $cuenta['Pld_Des'] . '</u>';
		if ($cuenta['Pld_Tip'] == 'G') {
			$cuenta['Pld_Des'] = '<b>' . $cuenta['Pld_Des'] . '</b>';
			$cuenta['Pld_Cdc'] = '<b>' . $cuenta['Pld_Cdc'] . '</b>';
			$cuenta['Total'] = '<b>' . $cuenta['Total'] . '</b>';
		}
		$cuenta['Pld_Des'] = str_repeat("&nbsp;", $cant_esp) . $cuenta['Pld_Des'];
		return $cuenta;
	}



	function estadoBalance($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format, $sucursal = '')
	{
		/* consulto los valores del mayor - OPTIMIZADO: una sola consulta para todos los datos */
		$filtro_sucursal = ($sucursal != '') ? "usuarios.Suc_Cod = '$sucursal'" : "";
		$valores = $this->getArrayConsulta(240, $Pec_Cod . '*' . $ini . '*' . $fin . '*' . $filtro_sucursal, $obBD_conexion);

		/* OPTIMIZACIÓN: Crear índice en memoria para acceso rápido */
		$valoresIndex = array();
		foreach ($valores as $valor) {
			$valoresIndex[$valor['Pld_Cod']] = $valor;
		}

		/* OPTIMIZACIÓN: Cargar plan de cuentas completo una sola vez para evitar consultas recursivas */
		$gruposSQL_temp = "1=1"; // Cargar todas las cuentas activas
		$planCompleto = $this->getArrayConsulta(243, $cod . '*' . $gruposSQL_temp, $obBD_conexion);
		$planIndex = array();
		$planIndexPorPadre = array(); // Índice adicional por padre para búsqueda rápida
		foreach ($planCompleto as $cuenta) {
			$planIndex[$cuenta['Pld_Cod']] = $cuenta;
			if (!isset($planIndexPorPadre[$cuenta['Pld_Rec']])) {
				$planIndexPorPadre[$cuenta['Pld_Rec']] = array();
			}
			$planIndexPorPadre[$cuenta['Pld_Rec']][] = $cuenta;
		}

		/* calculo de utilidades - OPTIMIZADO: usar datos en memoria */
		$utilidades = $this->cargarTotalEstadosOptimizado($cod, $np, $ini, $fin, $obBD_conexion, 2, $valoresIndex, $planIndex, $planIndexPorPadre);

		$util = $utilidades[1] - ($utilidades[2] + $utilidades[3] + $utilidades[4]);
		if ($tipo == 1) {
			$utilidad = $this->getRowConsulta(242, $cod . '*' . ($util > 0 ? 'G' : 'P'), $obBD_conexion);
			$shouldAdd = true;
			for ($i = 0; $i < count($valores); $i++)
				if ($valores[$i]['Pld_Cod'] == $utilidad['Pld_Cod']) {
					$valores[$i]['debe'] = $valores[$i]['debe'] - $utilidades[1];
					$valores[$i]['haber'] = $valores[$i]['haber'] - ($utilidades[2] + $utilidades[3] + $utilidades[4]);
					$shouldAdd = false;
					break;
				}
			if ($shouldAdd) {
				$utilidad['debe'] = -$utilidades[1];
				$utilidad['haber'] = - ($utilidades[2] + $utilidades[3] + $utilidades[4]);
				array_push($valores, $utilidad);
			}
		}

		$gruposSQL = '';
		$grupos = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);

		for ($i = 0; $i < count($grupos); $i++) {
			$gruposSQL = $gruposSQL . ($gruposSQL != '' ? ' OR ' : '') . " Pld_Cdc LIKE '" . $grupos[$i]['Pld_Cdc'] . "%'";
			$grupos[$i]['total'] = $this->getTotalNodo($grupos[$i]['Pld_Cdc'], $valores, 'G');
		}

		/* consulto el plan de cuentas */
		$cuentas = $this->getArrayConsulta(243, $cod . '*' . $gruposSQL, $obBD_conexion);
		$plan = $this->getCtaChild($cuentas, 0);

		/* aqui armo el balance */
		$balance = array();
		for ($i = 0; $i < count($plan); $i++) {
			$total = $this->getTotalNodo($plan[$i]['Pld_Cdc'], $valores, $plan[$i]['Pld_Tip']);
			$cant_cuent = explode('.', $plan[$i]['Pld_Cdc']);

			if ($total['asignar'] && count($cant_cuent) <= $max_nivel) {
				$cuenta = $this->formatCuenta($plan[$i], $cant_cuent, $total, $format);
				$cuenta['cant_cuent'] = $cant_cuent;
				$cuenta['total'] = $total;
				array_push($balance, $cuenta);
			}
		}
		$ok = true;
		$valor_activo = 0;
		$valor_aux = 0;
		for ($i = 0; $i < count($balance); $i++) {
			$cuenta = $balance[$i];

			if (!isset($balance[($i + 1)]) || $cuenta['cant_cuent'][0] != $balance[($i + 1)]['cant_cuent'][0]) {
				foreach ($grupos as $raiz) {
					if ($raiz['Pld_Cdc'] == $cuenta['cant_cuent'][0]) {
						$totalGrupo = formato_numero(($raiz['total']['debe'] - $raiz['total']['haber']), 2, $format);
						if ($raiz['Pld_Cdc'] == '2' || $raiz['Pld_Cdc'] == '3' || $raiz['Pld_Cdc'] == '4') {
							$totalGrupo = formato_numero(($raiz['total']['debe'] - $raiz['total']['haber']) * -1, 2, $format);
							$raiz['total']['saldo'] = $raiz['total']['saldo'] * -1;
						}
						$valor_aux = $totalGrupo;
						if ($raiz['total']['saldo'] < 0) $totalGrupo = '<font color="#FF0000" >' . $totalGrupo . '</font>';
					}

					if ($ok) {
						$ok = false;
						$valor_activo = $valor_aux;
					}
				}
			}
		}
		switch ($tipo) {
			case 1:
				/* Sumatoria del Balance General */
				$totales = 0;
				foreach ($grupos as $raiz) {
					if ($raiz['Pld_Cdc'] == '2' || $raiz['Pld_Cdc'] == '3') {
						$totales = $totales + $raiz['total']['saldo'];
					}
				}

				echo "Estado: <span style='color:" . (formato_numero(($totales) * -1, 2, $format)  != ($valor_activo) ? "#FF0000'>Descuadrado" : "#0000FF'>Cuadrado") . "</span>";
				$num1 = floatval(str_replace(',', '', (formato_numero($totales * -1, 2, $format))));
				$num2 = floatval(str_replace(',', '', $valor_activo));
				// Realizamos la resta  
				$result = formato_numero(abs($num1 - $num2), 2, $format);
				echo " | Diferencia: " . $result;
				echo "<hr>";
				break;
		}
	}


	/**
	 * Carga los nodos (cuentas) en el balance general 
	 * $nivel = Contador de los niveles que tiene el plan de cuentas
	 * $max_nivel = Mximo nivel a presentar 
	 * $format = Formato de presentacin de los nmero 
	 */
	function cargarNodosBalance($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format, $Pec_Cod2_completo = null, $Pec_Fei_periodo = null, $Pec_Fef_periodo = null, $sucursal = '')
	{

		/* consulto los valores del mayor - OPTIMIZADO: una sola consulta para todos los datos */
		$filtro_sucursal = ($sucursal != '') ? "usuarios.Suc_Cod = '$sucursal'" : "";
		$valores = $this->getArrayConsulta(240, $Pec_Cod . '*' . $ini . '*' . $fin . '*' . $filtro_sucursal, $obBD_conexion);

		/* OPTIMIZACIÓN: Crear índice en memoria para acceso rápido */
		$valoresIndex = array();
		foreach ($valores as $valor) {
			$valoresIndex[$valor['Pld_Cod']] = $valor;
		}

		/* OPTIMIZACIÓN: Cargar plan de cuentas completo una sola vez para evitar consultas recursivas */
		$gruposSQL_temp = "1=1"; // Cargar todas las cuentas activas
		$planCompleto = $this->getArrayConsulta(243, $cod . '*' . $gruposSQL_temp, $obBD_conexion);
		$planIndex = array();
		$planIndexPorPadre = array(); // Índice adicional por padre para búsqueda rápida
		foreach ($planCompleto as $cuenta) {
			$planIndex[$cuenta['Pld_Cod']] = $cuenta;
			if (!isset($planIndexPorPadre[$cuenta['Pld_Rec']])) {
				$planIndexPorPadre[$cuenta['Pld_Rec']] = array();
			}
			$planIndexPorPadre[$cuenta['Pld_Rec']][] = $cuenta;
		}

		/* calculo de utilidades - OPTIMIZADO: usar datos en memoria */
		$utilidades = $this->cargarTotalEstadosOptimizado($cod, $np, $ini, $fin, $obBD_conexion, 2, $valoresIndex, $planIndex, $planIndexPorPadre);

		//
		$util = $utilidades[1] - ($utilidades[2] + $utilidades[3] + $utilidades[4]);
		if ($tipo == 1) {  // Aqui cargo la cuenta utilidad o perdida solo para balance general
			$utilidad = $this->getRowConsulta(242, $cod . '*' . ($util > 0 ? 'G' : 'P'), $obBD_conexion);
			$shouldAdd = true;
			for ($i = 0; $i < count($valores); $i++)
				if ($valores[$i]['Pld_Cod'] == $utilidad['Pld_Cod']) {
					//$this->echoLog($valores[$i]);echo '<br>';
					//Esta Linea esta bien solo la borre para darme cuenta cuando laidy mete mal la utilidad
					$valores[$i]['debe'] = $valores[$i]['debe'] - $utilidades[1];
					$valores[$i]['haber'] = $valores[$i]['haber'] - ($utilidades[2] + $utilidades[3] + $utilidades[4]);
					//$valores[$i]['debe']=-$utilidades[1];$valores[$i]['haber']=-($utilidades[2] + $utilidades[3] + $utilidades[4]);// quitar si se activa la anterior
					$shouldAdd = false;
					//$this->echoLog($valores[$i]);echo '<br>';
					break;
				}
			if ($shouldAdd) {
				$utilidad['debe'] = -$utilidades[1];
				$utilidad['haber'] = - ($utilidades[2] + $utilidades[3] + $utilidades[4]); //$utilidad['saldo']=$util*-1;
				array_push($valores, $utilidad);
				echo '<br>';
			}
		}
		//$this->echoLog($utilidades);

		/* consulto los grupos que lleva el balance */
		$gruposSQL = '';
		$grupos = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
		for ($i = 0; $i < count($grupos); $i++) {
			$gruposSQL = $gruposSQL . ($gruposSQL != '' ? ' OR ' : '') . " Pld_Cdc LIKE '" . $grupos[$i]['Pld_Cdc'] . "%'";
			$grupos[$i]['total'] = $this->getTotalNodo($grupos[$i]['Pld_Cdc'], $valores, 'G');
		}

		/* consulto el plan de cuentas */
		$cuentas = $this->getArrayConsulta(243, $cod . '*' . $gruposSQL, $obBD_conexion);
		$plan = $this->getCtaChild($cuentas, 0);

		/* aqui armo el balance */
		$balance = array();
		for ($i = 0; $i < count($plan); $i++) {
			$total = $this->getTotalNodo($plan[$i]['Pld_Cdc'], $valores, $plan[$i]['Pld_Tip']);
			$cant_cuent = explode('.', $plan[$i]['Pld_Cdc']);

			if ($total['asignar'] && count($cant_cuent) <= $max_nivel) {
				$cuenta = $this->formatCuenta($plan[$i], $cant_cuent, $total, $format);
				$cuenta['cant_cuent'] = $cant_cuent;
				$cuenta['total'] = $total;
				array_push($balance, $cuenta);
			}
		}   // echo $cod.' * '.$np.' * '.$ini.' * '.$fin.' * '.json_encode($utilidades); 
	?>
		<table width="100%" cellpadding="0" cellspacing="0" border="0" class="LetraNegra">
			<tbody>
				<?php
				/* Aqui Armo la tabla de cuentas */
				for ($i = 0; $i < count($balance); $i++) {
					$cuenta = $balance[$i];
					$last = ((!isset($balance[($i + 1)]) && $cuenta['Pld_Tip'] == 'D') || isset($balance[($i + 1)]) && $cuenta['Pld_Tip'] == 'D' && $balance[($i + 1)]['Pld_Tip'] == 'G');
					// Solo hacer clicables las cuentas de detalle (no los grupos)
					$esDetalle = ($cuenta['Pld_Tip'] == 'D');
					$codigoCuentaHtml = '';
					if ($esDetalle && $Pec_Cod2_completo !== null) {
						// Limpiar el código de cuenta de etiquetas HTML para la URL
						$codigoCuentaLimpio = strip_tags($cuenta['Pld_Cdc']);

						// Determinar si las fechas son personalizadas (diferentes al período completo)
						$fechasPersonalizadas = false;
						if ($Pec_Fei_periodo !== null && $Pec_Fef_periodo !== null) {
							// Si las fechas son diferentes a las del período, significa que se usó "Elegir fecha"
							if ($ini != $Pec_Fei_periodo || $fin != $Pec_Fef_periodo) {
								$fechasPersonalizadas = true;
							}
						}

						// Construir el enlace a mayorización con Pec_Cod2, código de cuenta y fechas
						$urlMayorizacion = 'con_con_mayorizacion_1.1.php?hdd_save2=1&txt_busqueda=' . urlencode($codigoCuentaLimpio) . '&Pec_Cod2=' . urlencode($Pec_Cod2_completo) . '&txt_fec_ini=' . urlencode($ini) . '&txt_fec_fin=' . urlencode($fin) . '&op=1';
						// Si las fechas son personalizadas, agregar Chk_Fec=1 para activar el checkbox
						if ($fechasPersonalizadas) {
							$urlMayorizacion .= '&Chk_Fec=1';
						}
						// Usar un target con nombre fijo para reutilizar la misma ventana
						// Efecto hover: subrayado y cambio de color al pasar el mouse
						$codigoCuentaHtml = '<a href="' . htmlspecialchars($urlMayorizacion, ENT_QUOTES, 'UTF-8') . '" target="mayorizacion_window" style="color: inherit; text-decoration: none; cursor: pointer;" onmouseover="this.style.textDecoration=\'underline\'; this.style.color=\'#0066cc\';" onmouseout="this.style.textDecoration=\'none\'; this.style.color=\'inherit\';" title="Ver mayorización de esta cuenta">' . $cuenta['Pld_Cdc'] . '</a>';
					} else {
						$codigoCuentaHtml = $cuenta['Pld_Cdc'];
					}
				?>
					<tr>
						<td width="2%"><?php echo $codigoCuentaHtml; ?></td>
						<td><?php echo utf8_encode($cuenta['Pld_Des']); ?></td>
						<?php
						$tdTotal = '';
						$tdLast = '';
						for ($j = 1; $j <= $max_nivel; $j++) {
							if (count($cuenta['cant_cuent']) == $j) {
								$tdTotal = '<td align="right">' . $cuenta['Total'] . '</td>' . $tdTotal;
								if ($last) $tdLast = '<td align="right" style="border-top: 1px solid #000">&nbsp;</td>' . $tdLast;
							} else {
								$tdTotal = '<td></td>' . $tdTotal;
								if ($last) $tdLast = '<td></td>' . $tdLast;
							}
						}
						echo $tdTotal;
						?>
					</tr>
					<?php if ($last) echo '<tr><td colspan="2"></td>' . $tdLast . '</tr>'; // solo pone la raya de la suma 
					?>
				<?php
					/* Presenta los Totales de los grupos */
					if (!isset($balance[($i + 1)]) || $cuenta['cant_cuent'][0] != $balance[($i + 1)]['cant_cuent'][0]) {
						foreach ($grupos as $raiz) {
							if ($raiz['Pld_Cdc'] == $cuenta['cant_cuent'][0]) {
								$totalGrupo = formato_numero(($raiz['total']['debe'] - $raiz['total']['haber']), 2, $format);

								if ($raiz['Pld_Cdc'] == '2' || $raiz['Pld_Cdc'] == '3' || $raiz['Pld_Cdc'] == '4') {
									$totalGrupo = formato_numero(($raiz['total']['debe'] - $raiz['total']['haber']) * -1, 2, $format);
									$raiz['total']['saldo'] = $raiz['total']['saldo'] * -1;
								}
								if ($raiz['total']['saldo'] < 0) $totalGrupo = '<font color="#FF0000" >' . $totalGrupo . '</font>';
								echo '<tr><td colspan="2" height="40" valign="top"><h3 style="margin-top:2px"><u><strong>TOTAL &nbsp;' . $raiz['Pld_Des'] . '</strong></u></h3></td><td colspan="' . ($max_nivel - 1) . '"></td><td style="border-top: 1px solid #000" align="right" valign="top"><h3 style="margin-top:2px">' . $totalGrupo . '</h3></td></tr>';
							}
						}
					}
				}
				switch ($tipo) {
					case 1:
						/* Sumatoria del Balance General */
						$totales = 0;
						foreach ($grupos as $raiz) {
							if ($raiz['Pld_Cdc'] == '2' || $raiz['Pld_Cdc'] == '3') {
								$totales = $totales + $raiz['total']['saldo'];
							}
						}
						echo '<tr><td colspan="2" height="40" valign="top"><h2 style="margin-top:6px"><u><strong>TOTAL PASIVO + PATRIMONIO =</strong></u></h2></td><td colspan="' . ($max_nivel - 1) . '"></td><td style="border-top: 2px solid #000" align="right" valign="top"><h2 style="margin-top:6px">' . formato_numero($totales * -1, 2, $format) . '</h2></td></tr>';
						break;
					case 2:
						/* Sumatoria del Estado de Resultados */
						$totales = 0;
						foreach ($grupos as $raiz) {
							if ($raiz['Pld_Cdc'] == '5' || $raiz['Pld_Cdc'] == '6' || $raiz['Pld_Cdc'] == '7') {
								$totales = $totales + $raiz['total']['saldo'];
							}
						}
						echo '<tr><td colspan="2" height="40" valign="top"><h3 style="margin-top:6px"><u><strong>TOTAL COSTOS + GASTOS =</strong></u></h3></td><td colspan="' . ($max_nivel - 1) . '"></td><td style="border-top: 1px solid #000" align="right" valign="top"><h3 style="margin-top:6px">' . formato_numero($totales, 2, $format) . '</h3></td></tr>';
						if ($util > 0)
							echo '<tr><td colspan="2" height="40" valign="top"><h2 style="margin-top:6px"><u><strong>UTILIDAD DEL EJERCICIO =</strong></u></h2></td><td colspan="' . ($max_nivel - 1) . '"></td><td align="right" valign="top"><h2 style="margin-top:6px">' . formato_numero($util, 2, $format) . '</h2></td></tr>';
						else
							echo '<tr><td colspan="2" height="40" valign="top"><h2 style="margin-top:6px"><u><strong>PERDIDAD DEL EJERCICIO =</strong></u></h2></td><td colspan="' . ($max_nivel - 1) . '"></td><td style="border-top: 2px solid #000" align="right" valign="top"><h2 style="margin-top:6px;color:#FF0000">' . formato_numero($util, 2, $format) . '</h2></td></tr>';
						break;
				}
				?>
			</tbody>
		</table>
		<?php
		//var_dump($plan);
	}




	function buscarProvedorDiario($obBD_conexion)
	{
		$empresa = $_SESSION['Ses_Emp_Cod'];
		$proveedorDiario = $this->getRowConsulta(500, $empresa, $obBD_conexion);
		return $proveedorDiario['Prv_Cod'];
	}


	function cargarDiarioDesdeBalance($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format)
	{
		$valores = $this->getArrayConsulta(240, $Pec_Cod . '*' . $ini . '*' . $fin, $obBD_conexion);
		$utilidades = $this->cargarTotalEstados($cod, $np, $ini, $fin, $obBD_conexion, 2);
		$util = $utilidades[1] - ($utilidades[2] + $utilidades[3]);

		if ($tipo == 1) {  // Aqui cargo la cuenta utilidad o perdida solo para balance general
			$utilidad = $this->getRowConsulta(242, $cod . '*' . ($util > 0 ? 'G' : 'P'), $obBD_conexion);
			$shouldAdd = true;
			for ($i = 0; $i < count($valores); $i++)
				if ($valores[$i]['Pld_Cod'] == $utilidad['Pld_Cod']) {
					//$this->echoLog($valores[$i]);echo '<br>';
					//Esta Linea esta bien solo la borre para darme cuenta cuando laidy mete mal la utilidad
					$valores[$i]['debe'] = $valores[$i]['debe'] - $utilidades[1];
					$valores[$i]['haber'] = $valores[$i]['haber'] - ($utilidades[2] + $utilidades[3]);
					//$valores[$i]['debe']=-$utilidades[1];$valores[$i]['haber']=-($utilidades[2] + $utilidades[3]);// quitar si se activa la anterior
					$shouldAdd = false;
					//$this->echoLog($valores[$i]);echo '<br>';
					break;
				}
			if ($shouldAdd) {
				$utilidad['debe'] = -$utilidades[1];
				$utilidad['haber'] = - ($utilidades[2] + $utilidades[3]); //$utilidad['saldo']=$util*-1;
				array_push($valores, $utilidad);
			}
		}


		$gruposSQL = '';
		$grupos = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
		for ($i = 0; $i < count($grupos); $i++) {
			$gruposSQL = $gruposSQL . ($gruposSQL != '' ? ' OR ' : '') . " Pld_Cdc LIKE '" . $grupos[$i]['Pld_Cdc'] . "%'";
			$grupos[$i]['total'] = $this->getTotalNodo($grupos[$i]['Pld_Cdc'], $valores, 'G');
		}

		/* consulto el plan de cuentas */
		$cuentas = $this->getArrayConsulta(243, $cod . '*' . $gruposSQL, $obBD_conexion);
		$plan = $this->getCtaChild($cuentas, 0);

		/* aqui armo el balance */
		$balance = array();
		for ($i = 0; $i < count($plan); $i++) {
			$total = $this->getTotalNodo($plan[$i]['Pld_Cdc'], $valores, $plan[$i]['Pld_Tip']);
			$cant_cuent = explode('.', $plan[$i]['Pld_Cdc']);

			if ($total['asignar'] && count($cant_cuent) <= $max_nivel) {
				$cuenta = $this->formatCuentaDiario($plan[$i], $cant_cuent, $total, $format);
				$cuenta['cant_cuent'] = $cant_cuent;
				$cuenta['total'] = $total;
				array_push($balance, $cuenta);
			}
		}


		$cuentasDetalle = array();

		for ($i = 0; $i < count($balance); $i++) {
			$cuenta = $balance[$i];
			$last = ((!isset($balance[($i + 1)]) && $cuenta['Pld_Tip'] == 'D') || isset($balance[($i + 1)]) && $cuenta['Pld_Tip'] == 'D' && $balance[($i + 1)]['Pld_Tip'] == 'G');

			$debeHaber = '';

			if ($cuenta['Pld_Tip'] == 'D') {
				if (startsWith($cuenta['Pld_Cdc'], '1')) {
					if ($cuenta['Total'] > 0) {
						$debeHaber = 'D';
						array_push($cuentasDetalle, $cuenta['Total']);
					} else {
						$debeHaber = 'H';
						array_push($cuentasDetalle, $cuenta['Total']);
					}
				}
				if (startsWith($cuenta['Pld_Cdc'], '2')) {
					if ($cuenta['Total'] < 0) {
						$debeHaber = 'D';
						array_push($cuentasDetalle, $cuenta['Total']);
					} else {
						$debeHaber = 'H';
						array_push($cuentasDetalle, $cuenta['Total']);
					}
				}
				if (startsWith($cuenta['Pld_Cdc'], '3')) {
					if ($cuenta['Total'] < 0) {
						$debeHaber = 'D';
						array_push($cuentasDetalle, $cuenta['Total']);
					} else {
						$debeHaber = 'H';
						array_push($cuentasDetalle, $cuenta['Total']);
					}
				}
				array_push($cuentasDetalle, $cuenta['Pld_Cod']);
				array_push($cuentasDetalle, $debeHaber);
			}

			$tdTotal = '';
			$tdLast = '';
			for ($j = 1; $j <= $max_nivel; $j++) {
				if (count($cuenta['cant_cuent']) == $j) {

					if ($cuenta['Pld_Tip'] == 'D') {
						$tdTotal = $cuenta['Total'] . 'Valor' . $tdTotal;
					} else {
						$tdTotal = $cuenta['Total'] . $tdTotal;
					}

					if ($last) $tdLast = $tdLast;
				} else {
					$tdTotal = $tdTotal;
					if ($last) $tdLast = $tdLast;
				}
			}
		}
		return $cuentasDetalle;
	}






	function cargarNodosBalance1($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format)
	{
		$j = $j + 1;
		/**
		 * Repite los espacios para formar el balance
		 */
		$espacios = str_repeat("&nbsp;", 27);
		if ($np == 0) {
			/**
			 * UTILIDADES = INGRESOS - (EGRESOS-COSTO + GASTOS) 
			 */
			$utilidades = $this->cargarTotalEstados($cod, $np, $ini, $fin, $obBD_conexion, 2);

			$util = $utilidades[1] - ($utilidades[2] + $utilidades[3]);

			/**
			 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 1 = Estado financiero 
			 */
			$rs_nodosrep = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
			//var_dump($rs_nodosrep);
			$nodo = current($rs_nodosrep);
		?>
			<table width="100%" cellpadding="0" cellspacing="0" border="0" class="LetraNegra">
				<?php
			} //Fin del if ($np == 0)
			else {
				$nivel++;
				/**
				 * Control para mostrar niveles en el balance general 
				 */
				if ($nivel < $max_nivel) {
					/**
					 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
					 */
					$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);
					//var_dump($Ses_Emp_Cod);
					// if($_SESSION['Ses_Emp_Cod']=='49') var_dump($np);
					$nodo = current($rs_nodosrep);
				} //FIn del if ($nivel < $max_nivel or $max_nivel == "T")			
			} //Fin del else if ($np == 0)
			/**
			 * Control para los espacios de las cuentas 
			 */
			$cant_cuent = explode('.', $nodo['Pld_Cdc']);
			$cant_esp = 0;
			for ($i = 1; $i <= count($cant_cuent) - 1; $i++) {
				$cant_esp = $cant_esp + 23;
			}
			$espacios_total = str_repeat("&nbsp;", $cant_esp);

			if (count($rs_nodosrep) > 0) {
				/**
				 * Variables para la linea de suma 
				 */
				$f = 0;
				$entrada_detalle = false;

				foreach ($rs_nodosrep as $row_rs_nodosrep[$j]) {
					/**
					 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
					 */
					$tipo_grupo = explode('.', $row_rs_nodosrep[$j]['Pld_Cdc']);
					/**
					 * Entra siempre y cuando sea del grupo patrimonio y el $cod_util sea diferente de cero 
					 */
					if ($tipo_grupo[0] == 3 && $cod_util != "") {
						/**************************************/
						/**
						 * Control para cruzar las utilidades 
						 */
						/**************************************/
						/** 
						 * Variable para asignar el valor de las utilidades a las cuentas
						 */
						global $asignar;
						$GLOBALS['asignar'] = "no";
						/**
						 * Ingresa cuando es la raiz de PATRIMONIO = 0 y la cuenta sea igual 
						 * a la de la utilidad
						 */
						if ($row_rs_nodosrep[$j]['Pld_Rec'] == 0 or $row_rs_nodosrep[$j]['Pld_Cod'] == $cod_util) {
							$GLOBALS['asignar'] = "si";
						} else {
							$GLOBALS['asignar'] = $this->secuenciaCuenta(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$obBD_conexion,
								$tipo,
								$cod_util
							);
						}
						/**
						 * Calculo de los totales 3=Patrimonio
						 */
						$total = 0;
						if ($GLOBALS['asignar'] == 'si') {
							$total = $this->calculoBalance(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$ini,
								$fin,
								$obBD_conexion,
								$row_rs_nodosrep[$j]['Pld_Tip'],
								0,
								$tipo_grupo[0]
							) + $util;
						} //Fin del if ($asignar == 'si'  || $row_rs_nodosrep[$j]['Pld_Cdc']==3)
						else {
							/**
							 * Consulta que otras cuentas como Perdidas del ejercicio
							 */
							$total = $this->calculoBalance(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$ini,
								$fin,
								$obBD_conexion,
								$row_rs_nodosrep[$j]['Pld_Tip'],
								0,
								$tipo_grupo[0]
							);
						}
					} //Fin del if ($tipo_grupo[0]==3)
					else {
						/**
						 * Calculo de los totales 
						 */
						$total = $this->calculoBalance(
							$cod,
							$row_rs_nodosrep[$j]['Pld_Cod'],
							$ini,
							$fin,
							$obBD_conexion,
							$row_rs_nodosrep[$j]['Pld_Tip'],
							0,
							$tipo_grupo[0]
						);
					} //Fin del else if ($tipo_grupo[0]==3)
					/**
					 * Contador para que aparezca la la raya de sumatoria del detalle 
					 */
					$f++;

					if ($total != 0) {
				?>
						<tr>
							<?php
							/**
							 * Variable que almacena el numero de la cuenta sin ningun formato 
							 */
							$cuenta_len = $row_rs_nodosrep[$j]['Pld_Cdc'];
							/**
							 * Control para agregar cero a las cuentas de detalle 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								//$cuenta = mascara_cuenta($row_rs_nodosrep[$j]['Pld_Cdc']);
								$cuenta = $row_rs_nodosrep[$j]['Pld_Cdc'];
								/**
								 * Variables para la linea de suma 
								 * Variable para saber si se ingreso al menos una vez de la raya 
								 */
								$entrada_detalle = true;
							} else {
								$cuenta = "<strong>" . $row_rs_nodosrep[$j]['Pld_Cdc'] . "</strong>";
							}
							?>
							<td width="2%"><?php
											/**
											 * Control para poner negrita en las cuentas de GRUPO 
											 */
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $cuenta;
											} else {
												echo "<strong>" . $cuenta . "</strong>";
											}
											?></td>
							<td><?php
								/**
								 * Control para poner negrita en las cuentas de GRUPO 
								 */
								if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
									echo str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'];
								} else {
									if ($np == 0) {
										/**
										 * Pone subrayado en caso de ser el nivel cero (0) del plan de cuentas 
										 */
										echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . "<u>" . $row_rs_nodosrep[$j]['Pld_Des'] . "</u></strong>";
									} else {
										echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'] . "</strong>";
									}
								}
								?></td>
							<td align="right"><?php
												/**
												 * Control para agregar cero a las cuentas de detalle 
												 */
												if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												?>
									<font <?php if ($total < 0) {
														echo "color='#FF0000'";
													} else {
														echo "class='LetraNegra'";
													} ?>><?php
															echo formato_numero($total, 2, $format);
															?></font><?php
																	} else {
																		echo "&nbsp;";
																	}
																		?>
							</td>
							<?php
							/**
							 * Control para agregar cero a las cuentas de grupo 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								$valor_grupo = "&nbsp;";
							} else {
								$valor_grupo = "<strong>" . formato_numero($total, 2, $format) . "</strong>"; //Fin del .$espacios_total						
								if ($np == 0) {
									/**
									 * Variable para el control de los grupos 
									 */
									$g++;
									$grupo[$g] = $total;
								} //Fin del if ($np == 0)
							}
							/**
							 * Devuelve un arreglo con la cantidad de niveles de la cuenta del plan 
							 */
							$niv_grupo = explode('.', $cuenta_len);

							for ($x = 1; $x <= $max_nivel; $x++) //$max_nivel
							{
							?>
								<td align="right" width="<?php if ($valor_grupo > 0) {
																echo "7";
															} else {
																echo "0";
															}  ?>%"><?php
																	if ($x == (($max_nivel + 1) - count($niv_grupo))) //Se suma uno por el nivel de detalle	+1
																	{
																	?>
										<font <?php if ($total < 0) {
																			echo "color='#FF0000'";
																		} else {
																			echo "class='LetraNegra'";
																		} ?>><?php
																				echo $valor_grupo;
																				?> </font><?php
																																			} else {
																																				echo "&nbsp;";
																																			}
																																				?>
								</td>
							<?php
							}
							/**
							 * Recursividad del cargado de los nodos 
							 */
							$this->cargarNodosBalance(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$ini,
								$fin,
								$obBD_conexion,
								$tipo,
								$Pec_Cod,
								$util,
								$cod_util,
								$nivel,
								$max_nivel,
								$format
							);
							?>
						</tr>
					<?php
					} //Fin if (($total != 0))

					/**
					 * Control para que aparezca la suma de los totales 
					 */
					if ($f == count($rs_nodosrep) && $entrada_detalle == true && $row_rs_nodosrep[$j]['Pld_Tip'] == 'D' /* && $bandera_raya == false && $entrada_detalle = false */) {
					?>
						<tr>
							<td>&nbsp;</td>
							<td>&nbsp;</td>
							<td align="right" valign="top">__________</td>
							<?php
							for ($x = 2; $x <= $max_nivel; $x++) {
							?>
								<td align="right" width="<?php if ($valor_grupo > 0) {
																echo "7";
															} else {
																echo "0";
															}  ?>%">&nbsp;</td>
							<?php
							} //Fin del for ($x=2; $x<=$max_nivel;$x++)
							?>
						</tr>
						<?php
					} //Fin del if ($f == $total_rs_nodosrep && $entrada_detalle == true && $row_rs_nodosrep[$j]['Pld_Tip']=='D')

					if ($np == 0) {
						if (($total != 0)) {
						?>
							<tr>
								<td colspan="<?php echo 4 + $max_nivel; ?>" align="left">
									<table width="100%" border="0" cellpadding="0" cellspacing="0">
										<tr>
											<td height="57">
												<h3><u><strong>TOTAL &nbsp;<?php echo $row_rs_nodosrep[$j]['Pld_Des']; ?></strong></u></h3>
											</td>
											<td align="right" valign="top">
												<h3>
													<font <?php
															if ($grupo[$g] < 0) {
																echo "color='#FF0000'";
															} else {
																echo "class='TITULO_REPORTE'";
															} ?>><strong>__________<br />
															<?php echo formato_numero($grupo[$g], 2, $format); ?></strong>
													</font>
												</h3>
											</td>
										</tr>
									</table>
								</td>
							</tr>
				<?php
						} //Fin del if (($total != 0))
					} //Fin del if ($np==0)
				} //Fin del foreach($rs_nodosrep as $row_rs_nodosrep[$j])
			} //if ($total_rs_nodosrep > 0)

			if ($np == 0) {
				/**
				 * PATRIMONIO_1 = ACTIVOS - PASIVOS 
				 */
				$act_pas = $grupo[1] - $grupo[2];
				/**
				 * PATRIMONIO = UTILIDAD + PATRIMONIO_1 
				 */
				$patrimonio = $grupo[3];
				?>
				<tr>
					<td colspan="<?php echo 4 + $max_nivel; ?>" align="left" class="TITULO_REPORTE">
						<?php
						/**
						 * Si TIPO = 1 entonces se trata de un balance general 
						 */
						switch ($tipo) {
							case 1:
						?>
								<br />
								<table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
										<td>
											<table width="100%" border="0" align="left" cellpadding="0" cellspacing="0">

												<tr>
													<td width="82%" class="TITULO_REPORTE">
														<div align="left">
															<h3>TOTAL PASIVO + PATRIMONIO = </h3>
														</div>
													</td>
													<td width="18%" align="right">
														<font <?php
																if ($grupo[2] + $patrimonio < 0) {
																	echo "color='#FF0000'";
																} else {
																	echo "class='TITULO_REPORTE'";
																} ?>>
															<h2>__________<br /><?php echo formato_numero($grupo[2] + $patrimonio, 2, $format); ?></h2>
														</font>
													</td>
												</tr>
												<?php
												if ($cod_util == "") {
												} //Fin del if ($cod_util == "")
												?>
											</table>
										</td>
									</tr>
								</table>
							<?php
								break;
							case 2:
							?>
								<table width="100%" border="0" cellspacing="0" cellpadding="0" align="left">
									<tr>
										<td class="TITULO_REPORTE">&nbsp;</td>
										<td class="LetraNegra" align="right">&nbsp;</td>
									</tr>
									<tr>
										<td class="TITULO_REPORTE">
											<h3>
												<div align="left">
													TOTAL DE COSTOS Y GASTOS =
												</div>
											</h3>
										</td>
										<td width="10%" class="" align="right">
											<h3>
												<font <?php
														$egresos = $grupo[2] + $grupo[3];
														if ($egresos < 0) {
															echo "color='#FF0000'";
														} else {
															echo "class=''";
														} ?>><strong><?php

																		echo formato_numero($egresos, 2, $format); ?></strong></font>
											</h3>
										</td>
									</tr>
									<tr>
										<td class="TITULO_REPORTE">
											<h3>
												<div align="left">
													<?php
													if ($util >= 0) { ?>
														UTILIDAD DEL EJERCICIO =
													<?php
													} else {
													?>
														PERDIDA DEL EJERCICIO =
													<?php
													} ?>
												</div>
											</h3>
										</td>
										<td width="10%" class="" align="right">
											<h3>
												<font <?php
														if ($util < 0) {
															echo "color='#FF0000'";
														} else {
															echo "class=''";
														} ?>><strong><?php echo formato_numero($util, 2, $format); ?></strong></font>
											</h3>
											<?php //echo number_format($util,2); 
											?>
										</td>
									</tr>
									<!--<tr>
                  <td class="TITULO_REPORTE"><div align="left">( - ) SUPERAVIT DE CAPITAL</div></td>
                  <td class="LetraNegra" align="right"><strong><?php //$aporte=($util * 15)/100; echo formato_numero($aporte, 2, $format); 
																?></strong><br />__________</td>
                </tr>
                <tr>
                  <td class="TITULO_REPORTE"><div align="left">UTILIDADES DEL EJERCICIO = </div></td>
                  <td class="LetraNegra" align="right"><strong><?php //echo formato_numero($util - $aporte, 2, $format); 
																?></strong></td>
                </tr>-->
								</table>
						<?php
								break;
						} //Fin del switch ($tipo){
						?>
					</td>
				</tr>
			</table>
		<?php
			}
		} //Fin del cargarNodosBalance($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)

		/**
		 * Funcion que calcula el total del balance general 
		 * $grupo = identifica a que grupo pertenece la cuenta. Ejemplo: 1=activo
		 */
		/**
		 * Versión optimizada que usa datos en memoria en lugar de consultas SQL individuales
		 */
		function calculoBalanceOptimizado($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $total, $grupo, $valoresIndex, $planIndex, $planIndexPorPadre)
		{
			$debe = 0;
			$haber = 0;

			/**
			 * OPTIMIZACIÓN: Usar datos en memoria en lugar de consulta SQL
			 */
			if (isset($valoresIndex[$np])) {
				$valor = $valoresIndex[$np];
				$debe = $valor['debe'];
				$haber = $valor['haber'];
			}

			/**
			 * 1 = Activo
			 * 2 = Pasivo
			 * 3 = Patrimonio
			 * 4 = Ingresos
			 * 5 = Costos y Gastos 
			 */
			if ($grupo == 2 || $grupo == 3 || $grupo == 4) {
				$saldos = $haber - $debe;
			} else {
				$saldos = $debe - $haber;
			}
			$total = $total + $saldos;

			/**
			 * OPTIMIZACIÓN: Usar plan de cuentas en memoria en lugar de consulta SQL
			 * Acceso directo usando índice por padre
			 */
			$hijos = isset($planIndexPorPadre[$np]) ? $planIndexPorPadre[$np] : array();

			if (count($hijos) > 0) {
				foreach ($hijos as $row_rs_nodosrep) {
					$tipo_grupo_hijo = explode('.', $row_rs_nodosrep['Pld_Cdc']);
					$total = $this->calculoBalanceOptimizado($cod, $row_rs_nodosrep['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo, $total, $grupo, $valoresIndex, $planIndex, $planIndexPorPadre);
				}
			}
			return $total;
		}

		function calculoBalance($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $total, $grupo, $sucursal = '')
		{
			$debe = 0;
			$haber = 0; //$i=$i+1;
			/**
			 * Consulta del total de movimiento de la cuenta inicial enviada como parametro
			 */
			$rs_saldos = $this->getArrayConsulta(212, $ini . '*' . $fin . '*' . $np . '*' . $sucursal, $obBD_conexion);
			//$grupo = current($rs_saldos);

			/**
			 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
			 */
			//$tipo_grupo = explode('.', $grupo['Pld_Cdc']);

			/**
			 * Se realiza esto porque solo deben haber dos registros 
			 * Des los dos supuestos registros encontrados toma por defecto el primero 
			 */
			foreach ($rs_saldos as $row_saldos) {
				if ($row_saldos['Asi_Deh'] == 'D') {
					$debe = $row_saldos['Asi_Val'];

					if (count($rs_saldos) == 1) {
						$haber = 0;
					}
				} elseif ($row_saldos['Asi_Deh'] == 'H') {

					$haber = $row_saldos['Asi_Val'];

					if (count($rs_saldos) == 1) {
						$debe = 0;
					}
				}
			}

			/**
			 * 1 = Activo
			 * 2 = Pasivo
			 * 3 = Patrimonio
			 * 4 = Ingresos
			 * 5 = Costos y Gastos 
			 */
			//if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)
			if ($grupo == 2 || $grupo == 3 || $grupo == 4) {
				$saldos = $haber - $debe;
			} else {
				$saldos = $debe - $haber;
			}
			$total = $total + $saldos;
			/**
			 * Consulta las cuentas del plan de cuentas GRUPOS 
			 */
			$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);

			if (count($rs_nodosrep) > 0) {
				foreach ($rs_nodosrep as $row_rs_nodosrep) {
					$total = $this->calculoBalance($cod, $row_rs_nodosrep['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo, $total, $grupo, $sucursal);
				}
			}
			return $total;
		}

		function cargarTotalEstados($cod, $np, $ini, $fin, $obBD_conexion, $tipo)
		{
			//$j=$j+1;
			if ($np == 0) {
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 2 = Estado de perdidas y ganancias 
				 */
				$rs_nodosrep = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
				//var_dump($rs_nodosrep);
			} else {
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
				 */
				$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);
			}

			if (count($rs_nodosrep) > 0) {
				foreach ($rs_nodosrep as $row_rs_nodosrep) {
					$tipo_grupo = explode('.', $row_rs_nodosrep['Pld_Cdc']);
					/**
					 * Calculo de los totales 
					 */
					$total = $this->calculoBalance(
						$cod,
						$row_rs_nodosrep['Pld_Cod'],
						$ini,
						$fin,
						$obBD_conexion,
						$row_rs_nodosrep['Pld_Tip'],
						0,
						$tipo_grupo[0]
					);
					//echo $cod.' - '.$row_rs_nodosrep['Pld_Cod'].' '.$row_rs_nodosrep['Pld_Cdc'].' ';var_dump($total); echo '<br>';
					if ($np == 0) {
						/**
						 * Variable para el control de los grupos 
						 */
						$g++;
						$grupo[$g] = $total;
					} //Fin del if ($np == 0)
					/**
					 * Recursividad del cargado de los nodos 
					 */
					$this->cargarTotalEstados($cod, $row_rs_nodosrep['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo);
				}
			} //if ($total_rs_nodosrep > 0)			

			if ($np == 0) {
				return $grupo;
			}
		} //Fin del cargar_total_estados($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)

		/**
		 * Versión optimizada que usa datos en memoria en lugar de consultas recursivas
		 */
		function cargarTotalEstadosOptimizado($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $valoresIndex, $planIndex, $planIndexPorPadre)
		{
			// 	$grupo = array();
			// 	$g = 0;

			// 	if ($np == 0)
			// 	{			
			// 		/**
			// 		* Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 2 = Estado de perdidas y ganancias 
			// 		*/
			// 		$rs_nodosrep = $this->getArrayConsulta(337, $cod.'*'.$np.'*'.$tipo, $obBD_conexion);
			// 	}
			// 	else
			// 	{
			// 		/**
			// 		* Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
			// 		*/
			// 		$rs_nodosrep = $this->getArrayConsulta(315, $cod.'*'.$np, $obBD_conexion);
			// 	}

			// 	if (count($rs_nodosrep) > 0)		
			// 	{
			// 		foreach($rs_nodosrep as $row_rs_nodosrep)
			// 		{	
			// 			$tipo_grupo = explode('.', $row_rs_nodosrep['Pld_Cdc']);
			// 			/**
			// 			* Calculo de los totales usando datos en memoria
			// 			*/
			// 			$total=$this->calculoBalanceOptimizado($cod,$row_rs_nodosrep['Pld_Cod'], $ini, $fin, 
			// 								$obBD_conexion, $row_rs_nodosrep['Pld_Tip'], 0, $tipo_grupo[0], $valoresIndex, $planIndex, $planIndexPorPadre); 		

			// 			if ($np == 0)
			// 			{
			// 				/**
			// 				* Variable para el control de los grupos 
			// 				*/
			// 				$g++;								
			// 				$grupo[$g]=$total;
			// 			}//Fin del if ($np == 0)
			// 			/**
			// 			* Recursividad del cargado de los nodos - OPTIMIZADO
			// 			*/
			// 			$subgrupos = $this->cargarTotalEstadosOptimizado($cod,$row_rs_nodosrep['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo, $valoresIndex, $planIndex, $planIndexPorPadre);
			// 			if ($np == 0 && is_array($subgrupos)) {
			// 				// Combinar subgrupos si es necesario
			// 			}
			// 		} 
			// 	}//if ($total_rs_nodosrep > 0)			

			// 	if ($np == 0)
			// 	{	
			// 		return $grupo;
			// 	}
			// } //Fin del cargar_total_estados optimizado
			$totales = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0);

			/* Identificar las cuentas raíz (normalmente 1, 2, 3, 4, 5, 6, 7, 8, 9) */
			$cuentasRaiz = array();
			if (isset($planIndexPorPadre[0])) {
				foreach ($planIndexPorPadre[0] as $cuenta) {
					$cuentasRaiz[] = $cuenta;
				}
			}

			foreach ($cuentasRaiz as $raiz) {
				$primerDigito = intval(substr($raiz['Pld_Cdc'], 0, 1));
				if ($primerDigito >= 1 && $primerDigito <= 9) {
					$totalNodo = $this->calculoBalanceOptimizado($cod, $raiz['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo, 0, $primerDigito, $valoresIndex, $planIndex, $planIndexPorPadre);
					$totales[$primerDigito] = $totalNodo;
				}
			}

			return $totales;
		}

		/**
		 * Funcion que busca si una cuenta esta dentro de un grupo determinado 
		 */
		function secuenciaCuenta($cod, $np, $obBD_conexion, $tipo, $cod_util)
		{
			$j = $j + 1;
			/**
			 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
			 */
			$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);

			if (count($rs_nodosrep) > 0) {
				foreach ($rs_nodosrep as $row_rs_nodosrep[$j]) {
					if (($row_rs_nodosrep[$j]['Pld_Cod'] == $cod_util)) {
						$valor = "si";
						$GLOBALS['asignar'] = "si";
						break;
					}
					/**
					 * Recursividad del cargado de los nodos 
					 */
					$this->secuenciaCuenta($cod, $row_rs_nodosrep[$j]['Pld_Cod'], $obBD_conexion, $tipo, $cod_util);
				}
			} //if ($total_rs_nodosrep > 0)			
			return $GLOBALS['asignar'];
		} //Fin del cargar_nodos_balance($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)

		/**
		 * Carga todos los nodos del plan de cuentas 
		 */


		function cargarNodosResumen($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format, $sql)
		{
			$rs_ver = $this->getArrayConsulta(240, $Pec_Cod . '*' . $ini . '*' . $fin . '*' . $sql, $obBD_conexion);
			$debe = 0;
			$haber = 0;
			$deudor = 0;
			$acreedor = 0;
			$html = "";
			foreach ($rs_ver as $fila) {
				$deudor = $deudor + round($fila['deudor'], 2);
				$acreedor = $acreedor + round($fila['acreedor'], 2);
				$debe = $debe + round($fila['debe'], 2);
				$haber = $haber + round($fila['haber'], 2);
				$html = $html . "<tr>
            <td style='font-size: 10px;'>$fila[Pld_Cdc]&nbsp;&nbsp;&nbsp;  " .  utf8_encode($fila['Pld_Des']) . "  </td>
            <td align='right'><font class='LetraNegra'>" . ($fila['debe'] == 0 ? '&nbsp;' : formato_numero($fila['debe'], 2, $format)) . "</font></td>
            <td align='right'><font class='LetraNegra'>" . ($fila['haber'] == 0 ? '&nbsp;' : formato_numero($fila['haber'], 2, $format)) . "</font></td>
            <td align='" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'center' : 'right') . "'><font class='LetraNegra'>" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? '-------' : ($fila['deudor'] == 0 ? '&nbsp;' : formato_numero($fila['deudor'], 2, $format))) . "</font></td>
            <td align='" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'center' : 'right') . "'><font class='LetraNegra'>" . ($fila['acreedor'] == 0 && $fila['deudor'] == 0 ? '-------' : ($fila['acreedor'] == 0 ? '&nbsp;' : formato_numero($fila['acreedor'], 2, $format))) . "</font></td></tr>";
			} ?>
		<table width="100%" cellpadding="0" cellspacing="0" border="1" class="Texto_Reporte">
			<tr>
				<td align="left"><strong>C&oacute;digo &nbsp;&nbsp;&nbsp;&nbsp; Detalle </strong></td>
				<td width="11%" align="center"><strong>Debe</strong></td>
				<td width="11%" align="center"><strong>Haber</strong></td>
				<td width="11%" align="center"><strong>Saldo Deudor</strong></td>
				<td width="11%" align="center"><strong>Saldo Acreedor</strong></td>
			</tr>
			<?php echo $html; ?>
			<tr>
				<td colspan="3">
					<div align="left">
						<h3>TOTALES</h3>
					</div>
				</td>

				<td align="right">
					<h3>&nbsp;<strong><?php echo formato_numero($deudor, 2, $format); ?></strong></h3>
				</td>
				<td align="right">
					<h3>&nbsp;<strong><?php echo formato_numero($acreedor, 2, $format); ?></strong></h3>
				</td>
			</tr>
		</table>
	<?php
		}
		function cargarNodosComprobacion($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format, $sucursal = '')
		{
			$filtro_sucursal = ($sucursal != '') ? "usuarios.Suc_Cod = '$sucursal'" : "";
			$rs_ver = $this->getArrayConsulta(240, $Pec_Cod . '*' . $ini . '*' . $fin . '*' . $filtro_sucursal, $obBD_conexion);
			$debe = 0;
			$haber = 0;
			$deudor = 0;
			$acreedor = 0;
			$html = "";
			foreach ($rs_ver as $fila) {
				$deudor = $deudor + round($fila['deudor'], 2);
				$acreedor = $acreedor + round($fila['acreedor'], 2);
				$debe = $debe + round($fila['debe'], 2);
				$haber = $haber + round($fila['haber'], 2);
				// 	$html = $html . "<tr " . ("" . $cod != "" . $fila['Pla_Cod'] || $fila['Pld_Est'] == 'I' ? 'style="background-color:#ffc1c1;" title="' . ($fila['Pld_Est'] == 'I' ? 'Cuenta Inactiva' : 'Cuenta en Plan Incorrecto') . '"' : '') . ">
				// <td style='font-size: 10px;'>$fila[Pld_Cdc]&nbsp;&nbsp;&nbsp; " .  utf8_encode($fila['Pld_Des']) . "   </td>
				// <td align='right'><font class='LetraNegra'>" . ($fila['debe'] == 0 ? '&nbsp;' : formato_numero($fila['debe'], 2, $format)) . "</font></td>
				// <td align='right'><font class='LetraNegra'>" . ($fila['haber'] == 0 ? '&nbsp;' : formato_numero($fila['haber'], 2, $format)) . "</font></td>
				// <td align='" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'center' : 'right') . "'><font class='LetraNegra'>" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? '-------' : ($fila['deudor'] == 0 ? '&nbsp;' : formato_numero($fila['deudor'], 2, $format))) . "</font></td>
				// <td align='" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'center' : 'right') . "'><font class='LetraNegra'>" . ($fila['acreedor'] == 0 && $fila['deudor'] == 0 ? '-------' : ($fila['acreedor'] == 0 ? '&nbsp;' : formato_numero($fila['acreedor'], 2, $format))) . "</font></td></tr>";
				$html = $html . "<tr class='" . ("" . $cod != "" . $fila['Pla_Cod'] || $fila['Pld_Est'] == 'I' ? 'row-inactive' : 'row-active') . "' title='" . ($fila['Pld_Est'] == 'I' ? 'Cuenta Inactiva' : ("" . $cod != "" . $fila['Pla_Cod'] ? 'Cuenta en Plan Incorrecto' : '')) . "'>
            <td class='cell-code'><span class='code-text'>$fila[Pld_Cdc]</span></td>
            <td class='cell-detail' style='text-align: left;'><span class='detail-text'>" . utf8_encode($fila['Pld_Des']) . "</span></td>
            <td class='cell-number'>" . ($fila['debe'] == 0 ? '-' : formato_numero($fila['debe'], 2, $format)) . "</td>
            <td class='cell-number'>" . ($fila['haber'] == 0 ? '-' : formato_numero($fila['haber'], 2, $format)) . "</td>
            <td class='cell-number " . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'cell-empty' : '') . "'>" . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? '---' : ($fila['deudor'] == 0 ? '-' : formato_numero($fila['deudor'], 2, $format))) . "</td>
            <td class='cell-number " . ($fila['deudor'] == 0 && $fila['acreedor'] == 0 ? 'cell-empty' : '') . "'>" . ($fila['acreedor'] == 0 && $fila['deudor'] == 0 ? '---' : ($fila['acreedor'] == 0 ? '-' : formato_numero($fila['acreedor'], 2, $format))) . "</td>
        </tr>";
			} ?>
		<!-- <table width="100%" cellpadding="0" cellspacing="0" border="1" class="Texto_Reporte">
			<tr>
				<td rowspan="2" align="left"><strong>C&oacute;digo &nbsp;&nbsp;&nbsp;&nbsp; Detalle </strong></td>
				<td colspan="2" align="center"><strong>SUMAS DEL MAYOR </strong></td>
				<td colspan="2" align="center"><strong>SALDOS</strong></td> -->
		<!-- <table width="100%" cellpadding="0" cellspacing="0" class="modern-table Texto_Reporte">
			<thead>
				<tr class="header-main">
					<th class="th-left" style="width: 100px;">Codigo</th>
					<th class="th-center">Detalle</th>
					<th colspan="2" class="th-center">SUMAS DEL MAYOR</th>
					<th colspan="2" class="th-center">SALDOS</th>
				</tr>
			</tr>
			<tr>
				<td width="11%" align="center"><strong>Debe</strong></td>
				<td width="11%" align="center"><strong>Haber</strong></td>
				<td width="11%" align="center"><strong>Deudor</strong></td>
				<td width="11%" align="center"><strong>Acreedor</strong></td>
			</tr> -->
		<table width="100%" cellpadding="0" cellspacing="0" class="modern-table Texto_Reporte">
			<thead>
				<tr class="header-main">
					<th class="th-left" style="width: 100px;">Codigo</th>
					<th class="th-center">Detalle</th>
					<th colspan="2" class="th-center">SUMAS DEL MAYOR</th>
					<th colspan="2" class="th-center">SALDOS</th>
				</tr>
				<tr class="header-sub">
					<th class="th-empty"></th>
					<th class="th-empty"></th>
					<th width="11%">Debe</th>
					<th width="11%">Haber</th>
					<th width="11%">Deudor</th>
					<th width="11%">Acreedor</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $html; ?>
				<!-- <tr>
					<td>
						<div align="left">
							<h3>TOTALES</h3>
						</div>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($debe, 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($haber, 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($deudor, 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($acreedor, 2, $format); ?></strong></h3>
					</td>
				</tr> -->
				</tbody>
			<tfoot>
				<tr class="row-totales">
					<td colspan="2" class="cell-label" style="text-align: right">TOTALES</td>
					<td class="cell-number"><?php echo formato_numero($debe, 2, $format); ?></td>
					<td class="cell-number"><?php echo formato_numero($haber, 2, $format); ?></td>
					<td class="cell-number"><?php echo formato_numero($deudor, 2, $format); ?></td>
					<td class="cell-number"><?php echo formato_numero($acreedor, 2, $format); ?></td>
				</tr>
			</tfoot>
		</table>
		<?php
		}



		function cargarNodosComprobacion_2($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $util, $cod_util, $nivel, $max_nivel, $format)
		{
			$j = $j + 1;
			$espacios = str_repeat("&nbsp;", 27);
			if ($np == 0) {
				/**
				 * UTILIDADES = INGRESOS - (EGRESOS-COSTO + GASTOS) 
				 */
				//$utilidades= $this->cargarTotalEstados($cod, $np, $ini, $fin, $obBD_conexion, 2);

				//$util = $utilidades[1] - ($utilidades[2] + $utilidades[3]);				
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 1 = Estado financiero 
				 */
				$rs_nodosrep = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
				//var_dump($rs_nodosrep);
		?>
			<table width="100%" cellpadding="0" cellspacing="0" border="1" class="Texto_Reporte">
				<tr>
					<td rowspan="2" align="left"><strong>C&oacute;digo &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Detalle </strong></td>
					<td colspan="2" align="center"><strong>SUMAS DEL MAYOR </strong></td>
					<td colspan="2" align="center"><strong>SALDOS</strong></td>
				</tr>
				<tr>
					<td width="11%" align="center"><strong>Debe</strong></td>
					<td width="11%" align="center"><strong>Haber</strong></td>
					<td width="11%" align="center"><strong>Deudor</strong></td>
					<td width="11%" align="center"><strong>Acreedor</strong></td>
				</tr>
				<?php
			} else {
				$nivel++;
				/**
				 * Control para mostrar niveles en el balance general 
				 */
				if ($nivel < $max_nivel) {
					/**
					 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
					 */
					$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);
				} //FIn del if ($nivel < $max_nivel)	
			}

			if (count($rs_nodosrep) > 0) {
				foreach ($rs_nodosrep as $row_rs_nodosrep[$j]) {
					/**
					 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
					 */
					$tipo_grupo = explode('.', $row_rs_nodosrep[$j]['Pld_Cdc']);
					/**
					 * Entra siempre y cuando sea del grupo patrimonio y el $cod_util sea diferente de cero 
					 */
					if ($tipo_grupo[0] == 3 && $cod_util != "") {
						/**************************************/
						/**
						 * Control para cruzar las utilidades 
						 */
						/**************************************/
						$asignar = $this->secuenciaCuenta(
							$cod,
							$row_rs_nodosrep[$j]['Pld_Cod'],
							$obBD_conexion,
							$tipo,
							$cod_util
						);
						/**
						 * Calculo de los totales 3=Patrimonio
						 */
						if ($asignar == 'si'  || $row_rs_nodosrep[$j]['Pld_Cdc'] == 3) {
							/**
							 * Calculo de los totales 
							 */
							$total_cadena = $this->calculoComprobacion(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$ini,
								$fin,
								$obBD_conexion,
								$row_rs_nodosrep[$j]['Pld_Tip'],
								0,
								0
							);
							$saldos = explode('*', $total_cadena);
							$saldos[1] = $saldos[1]; // + $util;
						} //Fin del if ($asignar == 'si'  || $row_rs_nodosrep[$j]['Pld_Cdc']==3)
						else {
							/**
							 * Calculo de los totales 
							 */
							$total_cadena = $this->calculoComprobacion(
								$cod,
								$row_rs_nodosrep[$j]['Pld_Cod'],
								$ini,
								$fin,
								$obBD_conexion,
								$row_rs_nodosrep[$j]['Pld_Tip'],
								0,
								0
							);
							$saldos = explode('*', $total_cadena);
						} //Fin del else if ($asignar == 'si'  || $row_rs_nodosrep[$j]['Pld_Cdc']==3)

					} //Fin del if ($tipo_grupo[0]==3)
					else {
						/**
						 * Calculo de los totales 
						 */
						$total_cadena = $this->calculoComprobacion(
							$cod,
							$row_rs_nodosrep[$j]['Pld_Cod'],
							$ini,
							$fin,
							$obBD_conexion,
							$row_rs_nodosrep[$j]['Pld_Tip'],
							0,
							0
						);
						$saldos = explode('*', $total_cadena);
					} //Fin del else if ($tipo_grupo[0]==3)

					/**
					 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
					 * 1 = Activo           - DEUDOR
					 * 2 = Pasivo           - ACREEDOR
					 * 3 = Patrimonio       - ACREEDOR
					 * 4 = Ingresos         - ACREEDOR
					 * 5 = Costos y Gastos  - DEUDOR 
					 */
					if ($saldos[0] == 0) {
						$debe = "&nbsp;";
					} else {
						$debe = formato_numero($saldos[0], 2, $format);
					}

					if ($saldos[1] == 0) {
						$haber = "&nbsp;";
					} else {
						$haber = formato_numero($saldos[1], 2, $format);
					}


					$total = $saldos[1] - $saldos[0]; //Formula especial
					/**
					 * Variables para presentacion por pantalla 
					 */
					$saldo_deudor = 0;
					$saldo_acreedor = 0;
					$deudor = 0;
					$acreedor = 0;
					if ($total >= 0) {
						$total = abs($total);
						$saldo_acreedor = formato_numero($total, 2, $format);
						$acreedor = $total;
					} else {
						$total = abs($total);
						$saldo_deudor = formato_numero($total, 2, $format);
						$deudor = $total;
					}
					/**
					 * Variables para los calculos 
					 */



					//				if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)
					//				{
					//					$total = $saldos[1] - $saldos[0]; //Formula especial
					//					/**
					//					* Variables para presentacion por pantalla 
					//					*/
					//					$saldo_deudor = 0;
					//					$saldo_acreedor = formato_numero($total,2,$format);
					//					/**
					//					* Variables para los calculos 
					//					*/
					//					$acreedor = $total;
					//					$deudor = 0;					
					//				}
					//				else 
					//				{
					//					$total = $saldos[0] - $saldos[1];
					//					/**
					//					* Variables para presentacion por pantalla 
					//					*/
					//					$saldo_deudor = formato_numero($total,2,$format);
					//					$saldo_acreedor = 0;
					//					/**
					//					* Variables para los calculos 
					//					*/
					//					$deudor = $total;
					//					$acreedor = 0;					
					//				}

					//if (($total != 0))

					if (($saldos[0] != 0 || $saldos[1] != 0)) {
						/**
						 * Cuenta la cantidad de niveles de las cuentas de grupo 
						 */
						$niv_grupo = explode('.', $row_rs_nodosrep[$j]['Pld_Cdc']);

						/**
						 * Control para mostrar a partir del segundo nivel de las cuentas 
						 */
						if (count($niv_grupo) > 1) { ?>
							<?php if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') { ?>
								<tr>
									<?php
									/**
									 * Control para agregar cero a las cuentas de detalle 
									 */
									if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
										//$cuenta = mascara_cuenta($row_rs_nodosrep[$j]['Pld_Cdc']);
										$cuenta = $row_rs_nodosrep[$j]['Pld_Cdc'];
									} else {
										$cuenta = "<strong>" . $row_rs_nodosrep[$j]['Pld_Cdc'] . "</strong>";
									}
									?><td style="font-size: 10px;"><?php
																	/**
																	 * Control para poner negrita en las cuentas de GRUPO 
																	 */
																	if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
																		echo $cuenta . '&nbsp;&nbsp;&nbsp;'/*$espacios*/ . $row_rs_nodosrep[$j]['Pld_Des'] . '<br>';
																	} else {
																		if ($np == 0) {
																			echo "<strong>" . $cuenta . $espacios . "<u>" . $row_rs_nodosrep[$j]['Pld_Des'] . "</u></strong><br>";
																		} else {
																			echo "<strong>" . $cuenta . $espacios . $row_rs_nodosrep[$j]['Pld_Des'] . "</strong><br>";
																		}
																	}
																	?></td>
									<td align="right">
										<font <?php if ($saldos[0] < 0) {
													echo "color='#FF0000'";
												} else {
													echo "class='LetraNegra'";
												} ?>>
											<?php if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $debe;
											} else {
												echo "<strong>" . $debe . "</strong>";
											}
											?></font>
									</td>
									<td align="right">
										<font <?php if ($saldos[1] < 0) {
													echo "color='#FF0000'";
												} else {
													echo "class='LetraNegra'";
												} ?>>
											<?php if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $haber;
											} else {
												echo "<strong>" . $haber . "</strong>";
											}
											?></font>
									</td>
									<td align="right">
										<font <?php if ($total < 0) {
													echo "color='#FF0000'";
												} else {
													echo "class='LetraNegra'";
												} ?>>

											<?php if ($saldo_deudor == 0) {
												$saldo_deudor = "&nbsp;";
											}
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $saldo_deudor;
											} else {
												echo "<strong>" . $saldo_deudor . "</strong>";
											}
											?> </font>
									</td>
									<td align="right">
										<font <?php if ($total < 0) {
													echo "color='#FF0000'";
												} else {
													echo "class='LetraNegra'";
												} ?>>
											<?php if ($saldo_acreedor == 0) {
												$saldo_acreedor = "&nbsp;";
											}

											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $saldo_acreedor;
											} else {
												echo "<strong>" . $saldo_acreedor . "</strong>";
											}
											?>
										</font>
									</td>
								</tr>
							<?php } ?>
				<?php
						} //Fin del if (count($niv_grupo) > 1)	
						if ($np == 0) {
							/**
							 * Variable para el control de los grupos 
							 */
							$g++;
							$grupo_d[$g] = $saldos[0];
							$grupo_h[$g] = $saldos[1];
							$saldos_grupo_d[$g] = $deudor;
							$saldos_grupo_h[$g] = $acreedor;
						} //Fin del if ($np == 0)
						/**
						 * Recursividad del cargado de los nodos 
						 */
						$this->cargarNodosComprobacion(
							$cod,
							$row_rs_nodosrep[$j]['Pld_Cod'],
							$ini,
							$fin,
							$obBD_conexion,
							$tipo,
							$Pec_Cod,
							$util,
							$cod_util,
							$nivel,
							$max_nivel,
							$format
						);
					} //Fin if (($total != 0))				
				} //Fin del foreach($rs_nodosrep as $row_rs_nodosrep[$j])
			} //if ($total_rs_nodosrep > 0)			

			if ($np == 0) {
				?>
				<tr>
					<td>
						<div align="left">
							<h3>TOTALES</h3>
						</div>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($grupo_d[1] + $grupo_d[2] + $grupo_d[3] + $grupo_d[4] + $grupo_d[5] + $grupo_d[6], 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($grupo_h[1] + $grupo_h[2] + $grupo_h[3] + $grupo_h[4] + $grupo_h[5] + $grupo_h[6], 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($saldos_grupo_d[1] + $saldos_grupo_d[2] + $saldos_grupo_d[3] +  $saldos_grupo_d[4] + $saldos_grupo_d[5] + $saldos_grupo_d[6], 2, $format); ?></strong></h3>
					</td>
					<td align="right">
						<h3>&nbsp;<strong><?php echo formato_numero($saldos_grupo_h[1] + $saldos_grupo_h[2] + $saldos_grupo_h[3] +  $saldos_grupo_h[4] + $saldos_grupo_h[5] + $saldos_grupo_h[6], 2, $format); ?></strong></h3>
					</td>
				</tr>
			</table>
		<?php
			}
		} //Fin del cargar_nodos_comprobacion($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)  

		/**
		 * Funcion que calcula el total del balance general 
		 */
		function calculoComprobacion($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $saldos_d, $saldos_h)
		{
			//die(var_dump($saldos));
			$i = $i + 1;
			/**
			 * Consulta del total de movimiento de la cuenta inicial enviada como parametro
			 */
			$rs_saldos = $this->getArrayConsulta(212, $ini . '*' . $fin . '*' . $np, $obBD_conexion);

			/**
			 * Se realiza esto porque solo deben haber dos registros 
			 * Des los dos supuestos registros encontrados toma por defecto el primero 
			 */
			foreach ($rs_saldos as $row_saldos) {
				if ($row_saldos['Asi_Deh'] == 'D') {
					$debe = $row_saldos['Asi_Val'];

					if (count($rs_saldos) == 1) {
						$haber = 0;
					}
				} elseif ($row_saldos['Asi_Deh'] == 'H') {

					$haber = $row_saldos['Asi_Val'];

					if (count($rs_saldos) == 1) {
						$debe = 0;
					}
				}
			}

			/**
			 * 1 = Activo
			 * 2 = Pasivo
			 * 3 = Patrimonio
			 * 4 = Ingresos
			 * 5 = Costos y Gastos 
			 */
			$saldos_d = $saldos_d + $debe; //Formula especial			
			$saldos_h = $saldos_h + $haber;

			/**
			 * Consulta las cuentas del plan de cuentas GRUPOS 
			 */
			$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);

			if (count($rs_nodosrep) > 0) {
				foreach ($rs_nodosrep as $row_rs_nodosrep[$i]) {
					$total_cadena = $this->calculoComprobacion($cod, $row_rs_nodosrep[$i]['Pld_Cod'], $ini, $fin, $obBD_conexion, $tipo, $saldos_d, $saldos_h);
					$saldos = explode('*', $total_cadena);
					$saldos_d = $saldos[0];
					$saldos_h = $saldos[1];
				}
			}

			return $saldos_d . '*' . $saldos_h;
		}

		/**
		 * Carga los nodos (cuentas) en el balance general 
		 * $nivel = Contador de los niveles que tiene el plan de cuentas
		 * $max_nivel = M�ximo nivel a presentar 
		 * $format = Formato de presentaci�n de los n�mero 
		 */
		function cargarNodosEfectivos($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $nivel, $max_nivel, $format)
		{
			$j = $j + 1;
			if ($np == 0) {
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 1 = Estado financiero 
				 */
				$rs_nodosrep = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
				//var_dump($rs_nodosrep);
				$nodo = current($rs_nodosrep);
		?>
			<table width="100%" cellpadding="0" cellspacing="0" border="1" class="LetraNegra">
				<?php
			} //Fin del if ($np == 0)
			else {
				$nivel++;
				/**
				 * Control para mostrar niveles en el balance general 
				 */
				if ($nivel < $max_nivel) {
					/**
					 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
					 */
					$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);
					$nodo = current($rs_nodosrep);
				} //FIn del if ($nivel < $max_nivel or $max_nivel == "T")			
			} //Fin del else if ($np == 0)

			if (count($rs_nodosrep) > 0) {
				/**
				 * Variables para la linea de suma 
				 */
				$f = 0;
				$entrada_detalle = false;

				foreach ($rs_nodosrep as $row_rs_nodosrep[$j]) {
					/**
					 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
					 */
					$tipo_grupo = explode('.', $row_rs_nodosrep[$j]['Pld_Cdc']);

					if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
						if ($tipo_grupo[0] == 4) {
							$d_h = 'D';
						} elseif ($tipo_grupo[0] == 5 or $tipo_grupo[0] == 6) {
							$d_h = 'H';
						}
						/**
						 * Consulta del total de movimiento de las cuentas banco de la cuenta inicial enviada como parametro
						 */
						$rs_bancos = $this->getArrayConsulta(214, $ini . '*' . $fin . '*' . $row_rs_nodosrep[$j]['Pld_Cod'], $obBD_conexion);
					}
					/**
					 * Contador para que aparezca la la raya de sumatoria del detalle 
					 */
					$f++;
					if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'G' or count($rs_bancos) > 0) {
				?>
						<tr>
							<?php
							/**
							 * Variable que almacena el numero de la cuenta sin ningun formato 
							 */
							$cuenta_len = $row_rs_nodosrep[$j]['Pld_Cdc'];
							/**
							 * Control para agregar cero a las cuentas de detalle 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								$cuenta = $row_rs_nodosrep[$j]['Pld_Cdc'];
								/**
								 * Variables para la linea de suma 
								 * Variable para saber si se ingreso al menos una vez de la raya 
								 */
								$entrada_detalle = true;
							} else {
								$cuenta = "<strong>" . $row_rs_nodosrep[$j]['Pld_Cdc'] . "</strong>";
							}
							?>
							<td width="4%"><?php
											/**
											 * Control para poner negrita en las cuentas de GRUPO 
											 */
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $cuenta;
											} else {
												echo "<strong>" . $cuenta . "</strong>";
											}
											?></td>
							<td width="42%"><?php
											/**
											 * Control para poner negrita en las cuentas de GRUPO 
											 */
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'];
											} else {
												if ($np == 0) {
													/**
													 * Pone subrayado en caso de ser el nivel cero (0) del plan de cuentas 
													 */
													echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . "<u>" . $row_rs_nodosrep[$j]['Pld_Des'] . "</u></strong>";
												} else {
													echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'] . "</strong>";
												}
											}
											?></td>
							<td width="54%" align="right"><?php
															/**
															 * Control para agregar cero a las cuentas de detalle 
															 */
															/*if ($row_rs_nodosrep[$j]['Pld_Tip']=='D')
					{*/
															?>
								<font <?php /*if ($total < 0){ echo "color='#FF0000'"; } else { echo "class='LetraNegra'"; }*/ ?>><?php
																																	/*echo formato_numero($total, 2, $format); */
																																	?></font><?php /*
					}
					else
					{*/
																																				echo "&nbsp;";
																																				//}
																																				?>
							</td>
							<?php
							/**
							 * Control para agregar cero a las cuentas de grupo 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								$valor_grupo = "&nbsp;";
							} else {
								$valor_grupo = "<strong>" . formato_numero($total, 2, $format) . "</strong>"; //Fin del .$espacios_total						
							}
							/**
							 * Devuelve un arreglo con la cantidad de niveles de la cuenta del plan 
							 */
							$niv_grupo = explode('.', $cuenta_len);
							?>
						</tr>
						<?php
						if (count($rs_bancos) > 0) {
							foreach ($rs_bancos as $row) {
								$rs_valores = $this->getArrayConsulta(215, $row['Com_Cod'] . '*' . $d_h, $obBD_conexion);
								if (count($rs_valores) > 0) {
									if ($d_h == 'H') {
										$row_proveedor = $this->getRowConsulta(216, $row['Com_Cod'], $obBD_conexion);
										$proveedor = " - " . $row_proveedor['Prs_Ape'] . ' ' . $row_proveedor['Prs_Nom'];
									} else {
										$proveedor = "";
									}
									foreach ($rs_valores as $row_valores) {
						?>
										<tr>
											<td>&nbsp;</td>
											<td><?php
												echo str_repeat("&nbsp;", strlen($cuenta_len) + 5) . $row_valores['Pld_Des'] . $proveedor;
												?></td>
											<td align="right"><?php
																echo $row_valores['Asi_Val'];
																?></td>
										</tr>
				<?php
									}
								}
							}
						}
					} //Fin if (($total != 0))				
					/**
					 * Recursividad del cargado de los nodos 
					 */
					$this->cargarNodosEfectivos(
						$cod,
						$row_rs_nodosrep[$j]['Pld_Cod'],
						$ini,
						$fin,
						$obBD_conexion,
						$tipo,
						$Pec_Cod,
						$nivel,
						$max_nivel,
						$format
					);
				} //Fin del foreach($rs_nodosrep as $row_rs_nodosrep[$j])
			} //if ($total_rs_nodosrep > 0)
			if ($np == 0) {
				?>
			</table>
		<?php
			}
		} //Fin del cargarNodosBalance($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)  

		/**
		 * Carga los nodos (cuentas) en el balance general 
		 * $nivel = Contador de los niveles que tiene el plan de cuentas
		 * $max_nivel = M�ximo nivel a presentar 
		 * $format = Formato de presentaci�n de los n�mero 
		 */
		function cargarNodosConversion($cod, $np, $ini, $fin, $obBD_conexion, $tipo, $Pec_Cod, $format, $detalle)
		{
			$j = $j + 1;
			if ($np == 0) {
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 1 = Estado financiero 
				 */
				/**
				 * Codigo del plan de cuenta NIFF asignado manualmente
				 */
				$cod = 4;
				$rs_nodosrep = $this->getArrayConsulta(337, $cod . '*' . $np . '*' . $tipo, $obBD_conexion);
				//var_dump($rs_nodosrep);
				$nodo = current($rs_nodosrep);
		?>
			<table width="100%" cellpadding="0" cellspacing="0" border="1" class="LetraNegra">
				<?php
			} //Fin del if ($np == 0)
			else {
				/**
				 * Consulta las cuentas del plan de cuentas en base al codigo del plan de cuentas 
				 */
				$rs_nodosrep = $this->getArrayConsulta(315, $cod . '*' . $np, $obBD_conexion);
				$nodo = current($rs_nodosrep);
			} //Fin del else if ($np == 0)

			if (count($rs_nodosrep) > 0) {
				/**
				 * Variables para la linea de suma 
				 */
				$f = 0;
				$entrada_detalle = false;

				foreach ($rs_nodosrep as $row_rs_nodosrep[$j]) {
					/**
					 * Es muy importante poner este explode antes de movimiento del apuntador de la consulta 
					 */
					$tipo_grupo = explode('.', $row_rs_nodosrep[$j]['Pld_Cdc']);

					if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
						if ($tipo_grupo[0] == 4) {
							$d_h = 'D';
						} elseif ($tipo_grupo[0] == 5 or $tipo_grupo[0] == 6) {
							$d_h = 'H';
						}

						/**
						 * Consulta del total de movimiento de las cuentas banco de la cuenta inicial enviada como parametro
						 */
						$rs_bancos = $this->getArrayConsulta(217, $ini . '*' . $fin . '*' . $row_rs_nodosrep[$j]['Pld_Cod'], $obBD_conexion);
					}
					/**
					 * Contador para que aparezca la la raya de sumatoria del detalle 
					 */
					$f++;
					if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'G' or count($rs_bancos) > 0) {
				?>
						<tr>
							<?php
							/**
							 * Variable que almacena el numero de la cuenta sin ningun formato 
							 */
							$cuenta_len = $row_rs_nodosrep[$j]['Pld_Cdc'];
							/**
							 * Control para agregar cero a las cuentas de detalle 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								$cuenta = $row_rs_nodosrep[$j]['Pld_Cdc'];
								/**
								 * Variables para la linea de suma 
								 * Variable para saber si se ingreso al menos una vez de la raya 
								 */
								$entrada_detalle = true;
							} else {
								$cuenta = "<strong>" . $row_rs_nodosrep[$j]['Pld_Cdc'] . "</strong>";
							}
							?>
							<td width="4%"><?php
											/**
											 * Control para poner negrita en las cuentas de GRUPO 
											 */
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo $cuenta;
											} else {
												echo "<strong>" . $cuenta . "</strong>";
											}
											?></td>
							<td width="42%"><?php
											/**
											 * Control para poner negrita en las cuentas de GRUPO 
											 */
											if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
												echo str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'];
											} else {
												if ($np == 0) {
													/**
													 * Pone subrayado en caso de ser el nivel cero (0) del plan de cuentas 
													 */
													echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . "<u>" . $row_rs_nodosrep[$j]['Pld_Des'] . "</u></strong>";
												} else {
													echo "<strong>" . str_repeat("&nbsp;", strlen($cuenta_len)) . $row_rs_nodosrep[$j]['Pld_Des'] . "</strong>";
												}
											}
											?></td>
							<td width="54%" align="right"><strong><?php
																	$total = $this->calculoConversion($row_rs_nodosrep[$j]['Pld_Cod'], $ini, $fin, $obBD_conexion, $row_rs_nodosrep[$j]['Pld_Tip'], $tipo_grupo[0], $saldos);
																	if ($total > 0) {
																		echo $total;
																	}
																	?>
								</strong>
							</td>
							<?php
							/**
							 * Control para agregar cero a las cuentas de grupo 
							 */
							if ($row_rs_nodosrep[$j]['Pld_Tip'] == 'D') {
								$valor_grupo = "&nbsp;";
							} else {
								$valor_grupo = "<strong>" . formato_numero($total, 2, $format) . "</strong>"; //Fin del .$espacios_total						
							}
							/**
							 * Devuelve un arreglo con la cantidad de niveles de la cuenta del plan 
							 */
							$niv_grupo = explode('.', $cuenta_len);
							?>
						</tr>
						<?php
						if (count($rs_bancos) > 0 and $detalle == 'S') {
							foreach ($rs_bancos as $row) {
								/**
								 * Control para tomar en valor del debe para cuando juagan varias cuentas como el caso de IESS y roles
								 */
								if ($row['Prv_Cod'] != 52/*IESS*/) {
									$rs_valores = $this->getArrayConsulta(215, $row['Com_Cod'] . '*' . $d_h, $obBD_conexion);
								} else {
									/**
									 * Solo entra cuando es iess en ginus
									 */
									$d_h = 'D';
									$rs_valores = $this->getArrayConsulta(218, $row['Com_Cod'] . '*' . $d_h . '*' . $row_rs_nodosrep[$j]['Pld_Cod'], $obBD_conexion);
								}

								$dividir = explode('-', $row['Com_Fec']);
								$compr = "Compr.:" . $dividir[1] . '-' . $row['Com_Num'];
								if (count($rs_valores) > 0) {
									if ($d_h == 'H') {
										$row_proveedor = $this->getRowConsulta(216, $row['Com_Cod'], $obBD_conexion);
										$proveedor = " - " . $row_proveedor['Prs_Ape'] . ' ' . $row_proveedor['Prs_Nom'];
									} else {
										$proveedor = "";
									}
									foreach ($rs_valores as $row_valores) {
						?>
										<tr>
											<td>&nbsp;</td>
											<td><?php
												echo str_repeat("&nbsp;", strlen($cuenta_len) + 5) . $compr . '-' . $row_valores['Pld_Des'] . $proveedor;
												?></td>
											<td align="right"><?php
																echo $row_valores['Asi_Val'] . str_repeat("&nbsp;", strlen($cuenta_len));
																?></td>
										</tr>
				<?php
									}
								}
							}
						}
					} //Fin if (($total != 0))				
					/**
					 * Recursividad del cargado de los nodos 
					 */
					$this->cargarNodosConversion(
						$cod,
						$row_rs_nodosrep[$j]['Pld_Cod'],
						$ini,
						$fin,
						$obBD_conexion,
						$tipo,
						$Pec_Cod,
						$format,
						$detalle
					);
				} //Fin del foreach($rs_nodosrep as $row_rs_nodosrep[$j])
			} //if ($total_rs_nodosrep > 0)
			if ($np == 0) {
				?>
			</table>
<?php
			}
		} //Fin del cargarNodosBalance($cod, $np, $ini, $fin, $obBD_con1, $obBD_conexion)  

		/**
		 * Funcion que calcula el total del balance conversion
		 */
		function calculoConversion($np, $ini, $fin, $obBD_conexion, $tipo, $grupo, $saldos)
		{
			$i = $i + 1;
			/**
			 * Ingresa cuando se trata de una cuenta de detalle
			 */
			if ($tipo == 'D') {
				if ($grupo == 4) //Ingresos
				{
					$d_h = 'D';
				} elseif ($grupo == 5 or $grupo == 6) //Gastos-Costos
				{
					$d_h = 'H';
				}
				/**
				 * Consulta del total de movimiento de las cuentas banco de la cuenta inicial enviada como parametro
				 */
				$rs_bancos = $this->getArrayConsulta(217, $ini . '*' . $fin . '*' . $np, $obBD_conexion);

				if (count($rs_bancos) > 0) {
					$total = 0;
					foreach ($rs_bancos as $row) {
						/**
						 * Control para tomar en valor del debe para cuando juagan varias cuentas como el caso de IESS y roles
						 */
						if ($row['Prv_Cod'] != 52/*IESS*/) {
							$rs_valores = $this->getRowConsulta(238, $row['Com_Cod'] . '*' . $d_h, $obBD_conexion);
							$total = $total + $rs_valores['Asi_Val'];
						} else {
							/**
							 * Solo entra cuando es iess en ginus
							 */
							$d_h = 'D';
							$rs_valores = $this->getRowConsulta(239, $row['Com_Cod'] . '*' . $d_h . '*' . $np, $obBD_conexion);
							$total = $total + $rs_valores['Asi_Val'];
						} //Fin del if ($row['Prv_Cod']!=52/*IESS*/)				
					} //Fin del foreach($rs_bancos as $row)
				} //Fin del if (count($rs_bancos)>0)				
			} //FIn del if ($tipo=='D')	
			return $total;
		}
	} //Fin class
?>