<?php
/**
 * ppto_reajustes_logica.php
 * Controlador de Reajustes, Reducciones, Incrementos y Transferencias Presupuestarias (EXA PPTO).
 * Implementa el control y validaciï¿½n de traspasos de fondos garantizando que el presupuesto inicial permanezca intacto.
 */

require_once('ppto_persistencia_logica.php');

/**
 * Valida la disponibilidad financiera de una partida o rubro de proyecto para asegurar que no quede en saldo negativo tras un reajuste.
 *
 * @param mysqli $mysqli Conexiï¿½n activa a la BD.
 * @param int $Ppe_Cod ID de la cabecera presupuestaria.
 * @param int $Ppa_Cod ID de la partida presupuestaria.
 * @param string|null $Pro_Cod Cï¿½digo de proyecto (opcional).
 * @param string|null $rubro Nombre de rubro de proyecto (opcional).
 * @param int $mes Mes a evaluar (1 a 12).
 * @param float $monto Monto que se intenta transferir o reducir.
 * @return bool Retorna true si hay fondos suficientes disponibles, false de lo contrario.
 */
function ppto_reajuste_validar_disponibilidad($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $rubro, $mes, $monto) {
    $Ppe_Cod = (int)$Ppe_Cod;
    $Ppa_Cod = (int)$Ppa_Cod;
    $mes = (int)$mes;
    $monto = (float)$monto;

    include_once('ppto_motor_calculo.php');

    if (!empty($Pro_Cod) && !empty($rubro)) {
        // Validaciï¿½n a nivel de rubros de proyectos
        $clean_proy = $mysqli->real_escape_string($Pro_Cod);
        $clean_rubro = $mysqli->real_escape_string($rubro);

        $sql = "SELECT Pdp_Cod FROM pre_proyecto_detalles 
                WHERE Ppe_Cod = $Ppe_Cod AND Ppa_Cod = $Ppa_Cod AND Pro_Cod = '$clean_proy' AND Pdp_Rubro = '$clean_rubro' LIMIT 1";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $Pdp_Cod = (int)$row['Pdp_Cod'];
            
            // Calculamos recursivamente la disponibilidad de este rubro de proyecto para el mes de corte
            $calc = ppto_motor_calcular_rubro($mysqli, $Pdp_Cod, $mes);
            $disponible = isset($calc['mensual'][$mes]['disponible']) ? (float)$calc['mensual'][$mes]['disponible'] : 0.00;
            return ($disponible >= $monto);
        }
        return false;
    } else {
        // Validaciï¿½n a nivel de partidas de la empresa (sin proyectos)
        $calc = ppto_motor_calcular_partida($mysqli, $Ppa_Cod, $mes);
        $disponible = (float)$calc['disponible'];
        return ($disponible >= $monto);
    }
}

/**
 * Registra y procesa un nuevo movimiento de reajuste presupuestario (transferencia, incremento, reducciï¿½n).
 * Implementa las reglas crï¿½ticas de integridad transaccional del ERP.
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param array $datos Arreglo de parï¿½metros del movimiento.
 * @return bool Retorna true si se procesï¿½ e insertï¿½ con ï¿½xito, false de lo contrario.
 */
function ppto_reajuste_registrar($mysqli, $datos) {
    $Emp_Cod = isset($datos['Emp_Cod']) ? (int)$datos['Emp_Cod'] : (isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1);
    $Usu_Cod = isset($datos['Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 1;
    
    $Ppe_Cod = (int)$datos['Ppe_Cod'];
    $tipo = strtolower(trim($datos['Rea_Tipo'])); // 'transferencia' | 'incremento' | 'reduccion'
    $mes = (int)$datos['Rea_Mes'];
    $monto = (float)$datos['Rea_Mon'];
    $justificacion = $mysqli->real_escape_string(trim($datos['Rea_Jus']));

    if (!in_array($tipo, array('transferencia', 'incremento', 'reduccion'))) {
        return false;
    }
    if ($monto <= 0.0001) {
        return false;
    }

    // Definiciï¿½n de variables de origen y destino
    $Ppa_Cod_Origen = isset($datos['Ppa_Cod_Origen']) && $datos['Ppa_Cod_Origen'] !== '' ? (int)$datos['Ppa_Cod_Origen'] : "NULL";
    $Pro_Cod_Origen = isset($datos['Pro_Cod_Origen']) && $datos['Pro_Cod_Origen'] !== '' ? "'" . $mysqli->real_escape_string($datos['Pro_Cod_Origen']) . "'" : "NULL";
    $Rea_RubroOrigen = isset($datos['Rea_RubroOrigen']) && $datos['Rea_RubroOrigen'] !== '' ? "'" . $mysqli->real_escape_string($datos['Rea_RubroOrigen']) . "'" : "NULL";

    $Ppa_Cod_Destino = isset($datos['Ppa_Cod_Destino']) && $datos['Ppa_Cod_Destino'] !== '' ? (int)$datos['Ppa_Cod_Destino'] : "NULL";
    $Pro_Cod_Destino = isset($datos['Pro_Cod_Destino']) && $datos['Pro_Cod_Destino'] !== '' ? "'" . $mysqli->real_escape_string($datos['Pro_Cod_Destino']) . "'" : "NULL";
    $Rea_RubroDestino = isset($datos['Rea_RubroDestino']) && $datos['Rea_RubroDestino'] !== '' ? "'" . $mysqli->real_escape_string($datos['Rea_RubroDestino']) . "'" : "NULL";

    // 1. REGLA DE NEGOCIO CRï¿½TICA: Validaciones especï¿½ficas de flujos
    if ($tipo === 'transferencia') {
        if ($Ppa_Cod_Origen === "NULL" || $Ppa_Cod_Destino === "NULL") {
            return false; // Una transferencia requiere partida de origen y destino obligatoriamente
        }
        
        // Comprobar disponibilidad de fondos en el origen
        $origen_proy = ($Pro_Cod_Origen !== "NULL") ? trim($datos['Pro_Cod_Origen']) : null;
        $origen_rubro = ($Rea_RubroOrigen !== "NULL") ? trim($datos['Rea_RubroOrigen']) : null;
        
        $disponibilidad_ok = ppto_reajuste_validar_disponibilidad($mysqli, $Ppe_Cod, $Ppa_Cod_Origen, $origen_proy, $origen_rubro, $mes, $monto);
        if (!$disponibilidad_ok) {
            return false; // Fondos insuficientes en el origen para autorizar el traspaso
        }
    } elseif ($tipo === 'reduccion') {
        if ($Ppa_Cod_Destino === "NULL") {
            return false;
        }
        
        // Comprobar disponibilidad antes de recortar/reducir presupuesto
        $destino_proy = ($Pro_Cod_Destino !== "NULL") ? trim($datos['Pro_Cod_Destino']) : null;
        $destino_rubro = ($Rea_RubroDestino !== "NULL") ? trim($datos['Rea_RubroDestino']) : null;
        
        $disponibilidad_ok = ppto_reajuste_validar_disponibilidad($mysqli, $Ppe_Cod, $Ppa_Cod_Destino, $destino_proy, $destino_rubro, $mes, $monto);
        if (!$disponibilidad_ok) {
            return false; // No se puede reducir el presupuesto por debajo de la cuota ya ejecutada/comprometida
        }
    } elseif ($tipo === 'incremento') {
        if ($Ppa_Cod_Destino === "NULL") {
            return false;
        }
    }

    // 2. Insertar movimiento en la bitacora relacional pre_reajustes
    $sql = "INSERT INTO pre_reajustes (
                Emp_Cod, Ppe_Cod, Rea_Tipo, 
                Ppa_Cod_Origen, Pro_Cod_Origen, Rea_RubroOrigen, 
                Ppa_Cod_Destino, Pro_Cod_Destino, Rea_RubroDestino, 
                Rea_Mes, Rea_Mon, Rea_Jus, Rea_FecReg, Usu_Cod
            ) VALUES (
                $Emp_Cod, $Ppe_Cod, '$tipo',
                $Ppa_Cod_Origen, $Pro_Cod_Origen, $Rea_RubroOrigen,
                $Ppa_Cod_Destino, $Pro_Cod_Destino, $Rea_RubroDestino,
                $mes, $monto, '$justificacion', NOW(), $Usu_Cod
            )";
            
    $res = $mysqli->query($sql);
    if ($res) {
        // 3. Forzar el recï¿½lculo dinï¿½mico de los balances mensuales en las tablas de proyectos (Fase 2)
        // de manera que el mayor de disponibilidad Pdm_Disponible refleje el impacto de inmediato.
        include_once('ppto_motor_calculo.php');
        
        if ($tipo === 'transferencia') {
            if ($Pro_Cod_Origen !== "NULL" && $Rea_RubroOrigen !== "NULL") {
                // Traspaso de rubros de proyectos
                $sql_pdp_orig = "SELECT Pdp_Cod FROM pre_proyecto_detalles WHERE Ppe_Cod = $Ppe_Cod AND Ppa_Cod = $Ppa_Cod_Origen AND Pro_Cod = $Pro_Cod_Origen AND Pdp_Rubro = $Rea_RubroOrigen LIMIT 1";
                $res_pdp_orig = $mysqli->query($sql_pdp_orig);
                if ($res_pdp_orig && $row_pdp_orig = $res_pdp_orig->fetch_assoc()) {
                    ppto_motor_calcular_rubro($mysqli, (int)$row_pdp_orig['Pdp_Cod'], $mes);
                }
            }
        }
        
        if ($Pro_Cod_Destino !== "NULL" && $Rea_RubroDestino !== "NULL") {
            $sql_pdp_dest = "SELECT Pdp_Cod FROM pre_proyecto_detalles WHERE Ppe_Cod = $Ppe_Cod AND Ppa_Cod = $Ppa_Cod_Destino AND Pro_Cod = $Pro_Cod_Destino AND Pdp_Rubro = $Rea_RubroDestino LIMIT 1";
            $res_pdp_dest = $mysqli->query($sql_pdp_dest);
            if ($res_pdp_dest && $row_pdp_dest = $res_pdp_dest->fetch_assoc()) {
                ppto_motor_calcular_rubro($mysqli, (int)$row_pdp_dest['Pdp_Cod'], $mes);
            }
        }
        
        return true;
    }
    return false;
}

/**
 * Consolida la sumatoria neta de reajustes aplicados a una partida estï¿½ndar hasta un mes especï¿½fico.
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $Ppe_Cod ID de cabecera presupuestaria.
 * @param int $Ppa_Cod ID de la partida presupuestaria.
 * @param int $mes Mes de corte (1 a 12).
 * @return float Retorna el total neto consolidado del ajuste (positivo para incrementos, negativo para reducciones/origen de traspaso).
 */
function ppto_reajuste_consolidar_partida($mysqli, $Ppe_Cod, $Ppa_Cod, $mes) {
    $Ppe_Cod = (int)$Ppe_Cod;
    $Ppa_Cod = (int)$Ppa_Cod;
    $mes = (int)$mes;

    $ajuste_neto = 0.00;

    // 1. Sumar incrementos y transferencias recibidas de esta partida como destino (Suman fondos)
    $sql_dest = "SELECT SUM(Rea_Mon) AS total_suma 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $Ppe_Cod 
                   AND Ppa_Cod_Destino = $Ppa_Cod 
                   AND Pro_Cod_Destino IS NULL -- Filtramos que sea estandar, no proyecto
                   AND Rea_Mes <= $mes 
                   AND Rea_Tipo IN ('incremento', 'transferencia')";
    $res_dest = $mysqli->query($sql_dest);
    if ($res_dest && $row_dest = $res_dest->fetch_assoc()) {
        $ajuste_neto += (float)$row_dest['total_suma'];
    }

    // 2. Restar reducciones y transferencias enviadas de esta partida como origen (Restan fondos)
    $sql_orig = "SELECT SUM(Rea_Mon) AS total_resta 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $Ppe_Cod 
                   AND (
                       (Ppa_Cod_Origen = $Ppa_Cod AND Rea_Tipo = 'transferencia' AND Pro_Cod_Origen IS NULL)
                       OR 
                       (Ppa_Cod_Destino = $Ppa_Cod AND Rea_Tipo = 'reduccion' AND Pro_Cod_Destino IS NULL)
                   )
                   AND Rea_Mes <= $mes";
    $res_orig = $mysqli->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $ajuste_neto -= (float)$row_orig['total_resta'];
    }

    return $ajuste_neto;
}

/**
 * Consolida la sumatoria neta de reajustes aplicados a un rubro analï¿½tico de un proyecto para un mes especï¿½fico.
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $Ppe_Cod ID de cabecera presupuestaria.
 * @param int $Ppa_Cod ID de la partida presupuestaria.
 * @param string $Pro_Cod Cï¿½digo de proyecto.
 * @param string $rubro Nombre de rubro de proyecto.
 * @param int $mes Mes de corte (1 a 12).
 * @return float Retorna el total neto consolidado del ajuste (positivo para incrementos, negativo para reducciones/origen de traspaso).
 */
function ppto_reajuste_consolidar_proyecto($mysqli, $Ppe_Cod, $Ppa_Cod, $Pro_Cod, $rubro, $mes) {
    $Ppe_Cod = (int)$Ppe_Cod;
    $Ppa_Cod = (int)$Ppa_Cod;
    $mes = (int)$mes;
    $clean_proy = $mysqli->real_escape_string($Pro_Cod);
    $clean_rubro = $mysqli->real_escape_string($rubro);

    $ajuste_neto = 0.00;

    // 1. Sumar incrementos y transferencias destinadas a este rubro de proyecto (Suman fondos)
    $sql_dest = "SELECT SUM(Rea_Mon) AS total_suma 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $Ppe_Cod 
                   AND Ppa_Cod_Destino = $Ppa_Cod 
                   AND Pro_Cod_Destino = '$clean_proy'
                   AND Rea_RubroDestino = '$clean_rubro'
                   AND Rea_Mes = $mes 
                   AND Rea_Tipo IN ('incremento', 'transferencia')";
    $res_dest = $mysqli->query($sql_dest);
    if ($res_dest && $row_dest = $res_dest->fetch_assoc()) {
        $ajuste_neto += (float)$row_dest['total_suma'];
    }

    // 2. Restar reducciones y transferencias enviadas desde este rubro de proyecto (Restan fondos)
    $sql_orig = "SELECT SUM(Rea_Mon) AS total_resta 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $Ppe_Cod 
                   AND (
                       (Ppa_Cod_Origen = $Ppa_Cod AND Pro_Cod_Origen = '$clean_proy' AND Rea_RubroOrigen = '$clean_rubro' AND Rea_Tipo = 'transferencia')
                       OR 
                       (Ppa_Cod_Destino = $Ppa_Cod AND Pro_Cod_Destino = '$clean_proy' AND Rea_RubroDestino = '$clean_rubro' AND Rea_Tipo = 'reduccion')
                   )
                   AND Rea_Mes = $mes";
    $res_orig = $mysqli->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $ajuste_neto -= (float)$row_orig['total_resta'];
    }

    return $ajuste_neto;
}
