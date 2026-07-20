<?php

/* Logica de las paginas para el control de kardex */
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("inv_sql_inventario_2.0.php");

/* Clase para conexion a la capa de acceso a datos*/
class Class_Log_Conexion_Inv extends MysqlConexion {} //Fin de clase Class_Log_Conexion
/* Clase para acceder a los datos */
class Class_Log_Datos_Inv extends MysqlDatosContab {
	function __construct() {
		$this->setSentencias('sentencias_inv');
	}

	function getPromedio($pro_cod, $Ses_Suc_Cod, $fechaFin, $obBD) {
		$kardex = $this->getArrayConsulta(1001, array('proCod' => $pro_cod, 'fechaFin' => $fechaFin), $obBD);
		$promedio = array();
		if (count($kardex) > 0) {
			$x = COUNT($kardex);
			for ($i = 1; $i < $x; $i++) {
				if ($i == 1) {
					$kardex[$i - 1]['Stock'] = $kardex[$i - 1]['Kar_Can'] * 1 - $kardex[$i - 1]['Kar_Sal'];
					$kardex[$i - 1]['Saldo'] = ($kardex[$i - 1]['Kar_Ims'] * 1) - ($kardex[$i - 1]['Kar_Ime'] * 1);
					$kardex[$i - 1]['Promedio'] = ($kardex[$i - 1]['Stock'] != 0 ? $kardex[$i - 1]['Saldo'] / $kardex[$i - 1]['Stock'] : 0);
				}
				if ($kardex[$i]['Kar_Sal'] * 1 != 0) {//Realiza venta
					if ($kardex[$i - 1]['Promedio'] != null) {
						$kardex[$i]['Kar_Pre'] = $kardex[$i - 1]['Promedio'];
						$kardex[$i]['Kar_Ime'] = floatval($kardex[$i]['Kar_Pre']) * floatval($kardex[$i]['Kar_Sal']);
					} else {
						$kardex[$i]['Kar_Ime'] = floatval($kardex[$i]['Kar_Pre']) * floatval($kardex[$i]['Kar_Sal']);
					}
				}
				$kardex[$i]['Stock'] = $kardex[($i - 1)]['Stock'] * 1 + $kardex[$i]['Kar_Can'] * 1 - $kardex[$i]['Kar_Sal'];
				$kardex[$i]['Saldo'] = ($kardex[($i - 1)]['Saldo'] * 1) + ($kardex[$i]['Kar_Ims'] * 1) - ($kardex[$i]['Kar_Ime'] * 1);
				$kardex[$i]['Promedio'] = ($kardex[$i]['Stock'] != 0 ? $kardex[$i]['Saldo'] / $kardex[$i]['Stock'] : $kardex[($i - 1)]['Promedio']);
			}
			$promedio['Promedio'] = $kardex[$x - 1]['Promedio'];
			$promedio['Saldo'] = $kardex[$x - 1]['Saldo'];
			$promedio['Stock'] = $kardex[$x - 1]['Stock'];
		} else {
			$promedio['Promedio'] = 0;
			$promedio['Saldo'] = 0;
			$promedio['Stock'] = 0;
		}
		$promedio['Promedio'] = round(floatval($promedio['Promedio']), 5);
		$promedio['Saldo'] = round(floatval($promedio['Saldo']), 5);
		$promedio['Stock'] = round(floatval($promedio['Stock']), 5);
		return $promedio;
	}

	function calcularRentabilidad($array, $Ses_Suc_Cod, $ini, $fin, $obBD_conexion) {
		foreach ($array as $key => &$row) {
			$Ite_Cod = $row['Pro_Cod']; //Codigo del producto
			$promedio = $this->getPromedio($Ite_Cod, $Ses_Suc_Cod, $fin, $obBD_conexion);
			$kardex1 = array();

			ChromePhp::log("pro_Cod:" . $Ite_Cod);

			$kardex2 = $this->getArrayConsulta('kardex_ie.11', array(
				'Pro_Cod' => $Ite_Cod,
				'Fecha_Ini' => $ini,
				'Fecha_Fin' => $fin
			), $obBD_conexion);


			ChromePhp::log("kardkex ie:".count($kardex2));

			if (count($kardex2) > 0) {
				$kardex = array_merge($kardex1, $kardex2);
				$x = COUNT($kardex);

				for ($i = 0; $i < $x; $i++) {
					$row['Ent_Stk'] += $kardex[$i]['Kar_Can'] * 1;
					$row['Ent_Sal'] += $kardex[$i]['Kar_Ims'] * 1;
					$row['Sal_Stk'] += $kardex[$i]['Kar_Sal'] * 1;
					$row['Sal_Sal'] += $kardex[$i]['Vet_Imp'] * 1;
				}
			} else {
				$kardex = $kardex1;
				$row['Ent_Stk'] = 0;
				$row['Ent_Sal'] = 0;
				$row['Sal_Stk'] = 0;
				$row['Sal_Sal'] = 0;
			}
			$row['Ent_Prp'] = $promedio['Promedio'];
			//$row['Ent_Stock'] =($row['Ent_Stk']>=$row['Sal_Stk']?$row['Sal_Stk']:null);
			//$row['Ent_Valor']=round($row['Ent_Prp']*$row['Ent_Stock'],4);
			$row['Ent_Stock'] = $row['Sal_Stk'];

			

			$row['Ent_Valor'] = round($row['Ent_Prp'] * $row['Ent_Stock'], 4);
			$row['Sal_Prp'] = ($row['Sal_Stk'] != 0 ? round($row['Sal_Sal'] / $row['Sal_Stk'], 2) : null);
			$row['Ren_Valor'] = $row['Sal_Sal'] - $row['Ent_Valor'];
			$row['Ren_Porce'] = ($row['Sal_Sal'] != 0 ? round(($row['Ren_Valor'] / $row['Sal_Sal']) * 100, 2) : 0);
			if ($row['Sal_Stk'] <= 0) {
				unset($array[$key]);
			}
		}
		return $array;
	}
}
