<?php
/**
 * Perfil operativo de proyecto (modo reinversion relavera vs clasico).
 */

if (!function_exists('ppto_proy_es_modo_reinversion')) {
    /**
     * @param mysqli $mysqli
     * @param string $proy_id
     * @param int $Emp_Cod
     * @return bool
     */
    function ppto_proy_es_modo_reinversion($mysqli, $proy_id, $Emp_Cod) {
        if ($proy_id === null || trim($proy_id) === '') {
            return false;
        }
        $esc = $mysqli->real_escape_string(trim($proy_id));
        $Emp_Cod = (int)$Emp_Cod;
        $res = $mysqli->query("SELECT pco_origen FROM exa_ppto_prod_config
            WHERE proy_id='$esc' AND Emp_Cod=$Emp_Cod LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            return strtolower(trim($row['pco_origen'])) === 'relavera';
        }
        return false;
    }
}

if (!function_exists('ppto_reinversion_por_formalizar')) {
    /**
     * Monto pendiente de formalizar (solo positivo).
     *
     * @param float $vigente_por_real
     * @param float $vigente
     * @return float
     */
    function ppto_reinversion_por_formalizar($vigente_por_real, $vigente) {
        return round(max(0.0, (float)$vigente_por_real - (float)$vigente), 2);
    }
}

if (!function_exists('ppto_reinversion_totales_partidas')) {
    /**
     * Suma agregada para KPIs modo reinversion (filas detalle, no grupo).
     *
     * @param array $partidas
     * @return array
     */
    function ppto_reinversion_totales_partidas($partidas) {
        $t = array(
            'asignado_formal' => 0.0,
            'derecho_por_real' => 0.0,
            'por_formalizar' => 0.0,
            'proyeccion_anual' => 0.0,
            'meses_cerrados' => 0,
        );
        foreach ($partidas as $row) {
            if (!empty($row['es_grupo'])) {
                continue;
            }
            $t['asignado_formal'] += (float)(isset($row['vigente']) ? $row['vigente'] : 0);
            $t['derecho_por_real'] += (float)(isset($row['vigente_por_real']) ? $row['vigente_por_real'] : 0);
            $t['por_formalizar'] += (float)(isset($row['por_formalizar']) ? $row['por_formalizar'] : 0);
            $t['proyeccion_anual'] += (float)(isset($row['vigente_proyectado']) ? $row['vigente_proyectado'] : 0);
            if (isset($row['meses_cerrados']) && (int)$row['meses_cerrados'] > $t['meses_cerrados']) {
                $t['meses_cerrados'] = (int)$row['meses_cerrados'];
            }
        }
        foreach (array('asignado_formal', 'derecho_por_real', 'por_formalizar', 'proyeccion_anual') as $k) {
            $t[$k] = round($t[$k], 2);
        }
        return $t;
    }
}
