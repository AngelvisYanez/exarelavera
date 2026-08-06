<?php
/**
 * ppto_reajustes_logica.php
 * Controlador de Reajustes, Reducciones, Incrementos y Transferencias Presupuestarias (EXA PPTO).
 * Implementa el control y validación de traspasos de fondos garantizando que el presupuesto inicial permanezca intacto.
 */

require_once('ppto_persistencia_logica.php');

/**
 * Valida la disponibilidad financiera de una partida o rubro de proyecto para asegurar que no quede en saldo negativo tras un reajuste.
 *
 * @param mysqli $mysqli Conexión activa a la BD.
 * @param int $ppe_id ID de la cabecera presupuestaria.
 * @param int $ppa_id ID de la partida presupuestaria.
 * @param string|null $proy_id Código de proyecto (opcional).
 * @param string|null $rubro Nombre de rubro de proyecto (opcional).
 * @param int $mes Mes a evaluar (1 a 12).
 * @param float $monto Monto que se intenta transferir o reducir.
 * @return bool Retorna true si hay fondos suficientes disponibles, false de lo contrario.
 */
function ppto_reajuste_validar_disponibilidad($mysqli, $ppe_id, $ppa_id, $proy_id, $rubro, $mes, $monto) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $mes = (int)$mes;
    $monto = (float)$monto;

    include_once('ppto_motor_calculo.php');

    if (!empty($proy_id) && !empty($rubro)) {
        // Validación a nivel de rubros de proyectos
        $clean_proy = $mysqli->real_escape_string($proy_id);
        $clean_rubro = $mysqli->real_escape_string($rubro);

        $sql = "SELECT Pdp_Cod AS pdp_id FROM pre_proyecto_detalles 
                WHERE Ppe_Cod = $ppe_id AND Ppa_Cod = $ppa_id AND (Pro_Cod = '$clean_proy' OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Pdp_Rubro = '$clean_rubro' LIMIT 1";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            $pdp_id = (int)$row['pdp_id'];
            
            // Calculamos recursivamente la disponibilidad de este rubro de proyecto para el mes de corte
            $calc = ppto_motor_calcular_rubro($mysqli, $pdp_id, $mes);
            $disponible = isset($calc['mensual'][$mes]['disponible']) ? (float)$calc['mensual'][$mes]['disponible'] : 0.00;
            return ($disponible >= $monto);
        }
        return false;
    } else {
        // Validación a nivel de partidas de la empresa (sin proyectos)
        $calc = ppto_motor_calcular_partida($mysqli, $ppa_id, $mes);
        $disponible = (float)$calc['disponible'];
        return ($disponible >= $monto);
    }
}

/**
 * Registra y procesa un nuevo movimiento de reajuste presupuestario (transferencia, incremento, reducción).
 * Implementa las reglas críticas de integridad transaccional del ERP.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param array $datos Arreglo de parámetros del movimiento.
 * @return bool Retorna true si se procesó e insertó con éxito, false de lo contrario.
 */
function ppto_reajuste_registrar($mysqli, $datos) {
    $Emp_Cod = isset($datos['Emp_Cod']) ? (int)$datos['Emp_Cod'] : (isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1);
    $Usu_Cod = isset($datos['Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 1;
    
    $ppe_id = (int)$datos['ppe_id'];
    $tipo = strtolower(trim($datos['rea_tipo'])); // 'transferencia' | 'incremento' | 'reduccion'
    $mes = (int)$datos['rea_mes'];
    $monto = (float)$datos['rea_monto'];
    $justificacion = $mysqli->real_escape_string(trim($datos['rea_justificacion']));

    if (!in_array($tipo, array('transferencia', 'incremento', 'reduccion'))) {
        return false;
    }
    if ($monto <= 0.0001) {
        return false;
    }

    // Definición de variables de origen y destino
    $ppa_id_origen = isset($datos['ppa_id_origen']) && $datos['ppa_id_origen'] !== '' ? (int)$datos['ppa_id_origen'] : "NULL";
    $proy_id_origen = isset($datos['proy_id_origen']) && $datos['proy_id_origen'] !== '' ? "'" . $mysqli->real_escape_string($datos['proy_id_origen']) . "'" : "NULL";
    $pdp_rubro_origen = isset($datos['pdp_rubro_origen']) && $datos['pdp_rubro_origen'] !== '' ? "'" . $mysqli->real_escape_string($datos['pdp_rubro_origen']) . "'" : "NULL";

    $ppa_id_destino = isset($datos['ppa_id_destino']) && $datos['ppa_id_destino'] !== '' ? (int)$datos['ppa_id_destino'] : "NULL";
    $proy_id_destino = isset($datos['proy_id_destino']) && $datos['proy_id_destino'] !== '' ? "'" . $mysqli->real_escape_string($datos['proy_id_destino']) . "'" : "NULL";
    $pdp_rubro_destino = isset($datos['pdp_rubro_destino']) && $datos['pdp_rubro_destino'] !== '' ? "'" . $mysqli->real_escape_string($datos['pdp_rubro_destino']) . "'" : "NULL";

    // 1. REGLA DE NEGOCIO CRÍTICA: Validaciones específicas de flujos
    if ($tipo === 'transferencia') {
        if ($ppa_id_origen === "NULL" || $ppa_id_destino === "NULL") {
            return false; // Una transferencia requiere partida de origen y destino obligatoriamente
        }
        
        // Comprobar disponibilidad de fondos en el origen
        $origen_proy = ($proy_id_origen !== "NULL") ? trim($datos['proy_id_origen']) : null;
        $origen_rubro = ($pdp_rubro_origen !== "NULL") ? trim($datos['pdp_rubro_origen']) : null;
        
        $disponibilidad_ok = ppto_reajuste_validar_disponibilidad($mysqli, $ppe_id, $ppa_id_origen, $origen_proy, $origen_rubro, $mes, $monto);
        if (!$disponibilidad_ok) {
            return false; // Fondos insuficientes en el origen para autorizar el traspaso
        }
    } elseif ($tipo === 'reduccion') {
        if ($ppa_id_destino === "NULL") {
            return false;
        }
        
        // Comprobar disponibilidad antes de recortar/reducir presupuesto
        $destino_proy = ($proy_id_destino !== "NULL") ? trim($datos['proy_id_destino']) : null;
        $destino_rubro = ($pdp_rubro_destino !== "NULL") ? trim($datos['pdp_rubro_destino']) : null;
        
        $disponibilidad_ok = ppto_reajuste_validar_disponibilidad($mysqli, $ppe_id, $ppa_id_destino, $destino_proy, $destino_rubro, $mes, $monto);
        if (!$disponibilidad_ok) {
            return false; // No se puede reducir el presupuesto por debajo de la cuota ya ejecutada/comprometida
        }
    } elseif ($tipo === 'incremento') {
        if ($ppa_id_destino === "NULL") {
            return false;
        }
    }

    $proy_orig_val = ($proy_id_origen !== "NULL") ? "(SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide=$proy_id_origen OR Pro_Cod=$proy_id_origen LIMIT 1)" : "NULL";
    $proy_dest_val = ($proy_id_destino !== "NULL") ? "(SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide=$proy_id_destino OR Pro_Cod=$proy_id_destino LIMIT 1)" : "NULL";

    // 2. Insertar movimiento en la bitácora relacional pre_reajustes
    $sql = "INSERT INTO pre_reajustes (
                Emp_Cod, Ppe_Cod, Rea_Tipo, 
                Ppa_Cod_Origen, Pro_Cod_Origen, Rea_RubroOrigen, 
                Ppa_Cod_Destino, Pro_Cod_Destino, Rea_RubroDestino, 
                Rea_Mes, Rea_Mon, Rea_Jus, Rea_FecReg, Usu_Cod
            ) VALUES (
                $Emp_Cod, $ppe_id, '$tipo',
                $ppa_id_origen, $proy_orig_val, $pdp_rubro_origen,
                $ppa_id_destino, $proy_dest_val, $pdp_rubro_destino,
                $mes, $monto, '$justificacion', NOW(), $Usu_Cod
            )";
            
    $res = $mysqli->query($sql);
    if ($res) {
        // 3. Forzar el recálculo dinámico de los balances mensuales en las tablas de proyectos (Fase 2)
        // de manera que el mayor de disponibilidad pdm_disponible refleje el impacto de inmediato.
        include_once('ppto_motor_calculo.php');
        
        if ($tipo === 'transferencia') {
            if ($proy_id_origen !== "NULL" && $pdp_rubro_origen !== "NULL") {
                // Traspaso de rubros de proyectos
                $sql_pdp_orig = "SELECT Pdp_Cod AS pdp_id FROM pre_proyecto_detalles WHERE Ppe_Cod = $ppe_id AND Ppa_Cod = $ppa_id_origen AND (Pro_Cod = $proy_id_origen OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide=$proy_id_origen)) AND Pdp_Rubro = $pdp_rubro_origen LIMIT 1";
                $res_pdp_orig = $mysqli->query($sql_pdp_orig);
                if ($res_pdp_orig && $row_pdp_orig = $res_pdp_orig->fetch_assoc()) {
                    ppto_motor_calcular_rubro($mysqli, (int)$row_pdp_orig['pdp_id'], $mes);
                }
            }
        }
        
        if ($proy_id_destino !== "NULL" && $pdp_rubro_destino !== "NULL") {
            $sql_pdp_dest = "SELECT Pdp_Cod AS pdp_id FROM pre_proyecto_detalles WHERE Ppe_Cod = $ppe_id AND Ppa_Cod = $ppa_id_destino AND (Pro_Cod = $proy_id_destino OR Pro_Cod IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide=$proy_id_destino)) AND Pdp_Rubro = $pdp_rubro_destino LIMIT 1";
            $res_pdp_dest = $mysqli->query($sql_pdp_dest);
            if ($res_pdp_dest && $row_pdp_dest = $res_pdp_dest->fetch_assoc()) {
                ppto_motor_calcular_rubro($mysqli, (int)$row_pdp_dest['pdp_id'], $mes);
            }
        }
        
        return true;
    }
    return false;
}

/**
 * Consolida la sumatoria neta de reajustes aplicados a una partida estándar hasta un mes específico.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $ppe_id ID de cabecera presupuestaria.
 * @param int $ppa_id ID de la partida presupuestaria.
 * @param int $mes Mes de corte (1 a 12).
 * @return float Retorna el total neto consolidado del ajuste (positivo para incrementos, negativo para reducciones/origen de traspaso).
 */
function ppto_reajuste_consolidar_partida($mysqli, $ppe_id, $ppa_id, $mes) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $mes = (int)$mes;

    $ajuste_neto = 0.00;

    // 1. Sumar incrementos y transferencias recibidas de esta partida como destino (Suman fondos)
    $sql_dest = "SELECT SUM(Rea_Mon) AS total_suma 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $ppe_id 
                   AND Ppa_Cod_Destino = $ppa_id 
                   AND Pro_Cod_Destino IS NULL
                   AND Rea_Mes <= $mes 
                   AND Rea_Tipo IN ('incremento', 'transferencia')";
    $res_dest = $mysqli->query($sql_dest);
    if ($res_dest && $row_dest = $res_dest->fetch_assoc()) {
        $ajuste_neto += (float)$row_dest['total_suma'];
    }

    // 2. Restar reducciones y transferencias enviadas de esta partida como origen (Restan fondos)
    $sql_orig = "SELECT SUM(Rea_Mon) AS total_resta 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $ppe_id 
                   AND (
                       (Ppa_Cod_Origen = $ppa_id AND Rea_Tipo = 'transferencia' AND Pro_Cod_Origen IS NULL)
                       OR 
                       (Ppa_Cod_Destino = $ppa_id AND Rea_Tipo = 'reduccion' AND Pro_Cod_Destino IS NULL)
                   )
                   AND Rea_Mes <= $mes";
    $res_orig = $mysqli->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $ajuste_neto -= (float)$row_orig['total_resta'];
    }

    return $ajuste_neto;
}

/**
 * Consolida la sumatoria neta de reajustes aplicados a un rubro analítico de un proyecto para un mes específico.
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $ppe_id ID de cabecera presupuestaria.
 * @param int $ppa_id ID de la partida presupuestaria.
 * @param string $proy_id Código de proyecto.
 * @param string $rubro Nombre de rubro de proyecto.
 * @param int $mes Mes de corte (1 a 12).
 * @return float Retorna el total neto consolidado del ajuste (positivo para incrementos, negativo para reducciones/origen de traspaso).
 */
function ppto_reajuste_consolidar_proyecto($mysqli, $ppe_id, $ppa_id, $proy_id, $rubro, $mes) {
    $ppe_id = (int)$ppe_id;
    $ppa_id = (int)$ppa_id;
    $mes = (int)$mes;
    $clean_proy = $mysqli->real_escape_string($proy_id);
    $clean_rubro = $mysqli->real_escape_string($rubro);

    $ajuste_neto = 0.00;

    // 1. Sumar incrementos y transferencias destinadas a este rubro de proyecto (Suman fondos)
    $sql_dest = "SELECT SUM(Rea_Mon) AS total_suma 
                 FROM pre_reajustes 
                 WHERE Ppe_Cod = $ppe_id 
                   AND Ppa_Cod_Destino = $ppa_id 
                   AND (Pro_Cod_Destino = '$clean_proy' OR Pro_Cod_Destino IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy'))
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
                 WHERE Ppe_Cod = $ppe_id 
                   AND (
                       (Ppa_Cod_Origen = $ppa_id AND (Pro_Cod_Origen = '$clean_proy' OR Pro_Cod_Origen IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Rea_RubroOrigen = '$clean_rubro' AND Rea_Tipo = 'transferencia')
                       OR 
                       (Ppa_Cod_Destino = $ppa_id AND (Pro_Cod_Destino = '$clean_proy' OR Pro_Cod_Destino IN (SELECT Pro_Cod FROM pre_proyectos WHERE Pro_Ide='$clean_proy')) AND Rea_RubroDestino = '$clean_rubro' AND Rea_Tipo = 'reduccion')
                   )
                   AND Rea_Mes = $mes";
    $res_orig = $mysqli->query($sql_orig);
    if ($res_orig && $row_orig = $res_orig->fetch_assoc()) {
        $ajuste_neto -= (float)$row_orig['total_resta'];
    }

    return $ajuste_neto;
}
