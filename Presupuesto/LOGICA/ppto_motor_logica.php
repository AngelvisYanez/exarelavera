<?php
/**
 * ppto_motor_logica.php
 * Motor de Reglas e Integración Transaccional para EXA PPTO.
 * Procesa la evaluación de reglas e imputación de documentos contables.
 */

include_once('ppto_persistencia_logica.php');

/**
 * Mapea el tipo de documento del ERP con el nombre de su clave primaria física en la base de datos.
 *
 * @param string $tip_doc Tipo de documento (ventas, compras, rol_pagos, comprobantes, movimiento_cheques, asientos).
 * @return string|null Nombre de la clave primaria o null si no está soportado.
 */
function ppto_documento_pk_obtener($tip_doc) {
    switch ($tip_doc) {
        case 'ventas':
            return 'Vet_Cod';
        case 'compras':
            return 'Cop_Cod';
        case 'rol_pagos':
            return 'Rol_Cod';
        case 'comprobantes':
            return 'Com_Cod';
        case 'movimiento_cheques':
            return 'Mov_Cod';
        case 'asientos':
            return 'Asi_Cod';
        default:
            return null;
    }
}

/**
 * Evalúa secuencialmente las reglas parametrizadas para un documento a fin de identificar la partida aplicable.
 *
 * @param mysqli $mysqli Conexión a la BD.
 * @param int $emp_id Código de empresa.
 * @param string $tip_doc Tipo de documento transaccional.
 * @param string $doc_id Clave primaria del documento específico.
 * @return array|null Regla coincidente o null si ninguna aplica.
 */
function ppto_regla_buscar($mysqli, $emp_id, $tip_doc, $doc_id) {
    // Obtener todas las reglas activas para este origen y empresa ordenadas por prioridad.
    $reglas = ppto_persistencia_consultar($mysqli, 2, array('emp_id' => $emp_id, 'prg_tipo_documento' => $tip_doc));
    if (empty($reglas)) {
        return null;
    }

    foreach ($reglas as $regla) {
        // Regla general directa (aplica directo sin evaluar condiciones adicionales).
        if (empty($regla['prg_campo_evaluacion']) || empty($regla['prg_valor_esperado'])) {
            return $regla;
        }

        $pk_campo = ppto_documento_pk_obtener($tip_doc);
        if (!$pk_campo) {
            continue;
        }

        $clean_doc_id = $mysqli->real_escape_string($doc_id);
        $clean_table = $mysqli->real_escape_string($tip_doc);
        $clean_pk = $mysqli->real_escape_string($pk_campo);
        $clean_campo = $mysqli->real_escape_string($regla['prg_campo_evaluacion']);

        // Consulta dinámica en la tabla origen para comprobar la condición especial de evaluación.
        $sql = "SELECT `$clean_campo` FROM `$clean_table` WHERE `$clean_pk` = '$clean_doc_id' LIMIT 1";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            if ($row[$regla['prg_campo_evaluacion']] == $regla['prg_valor_esperado']) {
                return $regla;
            }
        }
    }

    return null;
}

/**
 * Audita e inserta alertas si el porcentaje de ejecución actual de una partida supera los límites.
 *
 * @param mysqli $mysqli Conexión a la BD.
 * @param int $ppe_id ID de la versión de presupuesto afectada.
 * @param int $ppa_id ID de la partida presupuestaria afectada.
 * @param int $pej_id ID de la ejecución presupuestaria causante.
 * @param int $mes Mes transaccionado.
 * @param int $anio Año fiscal de la transacción.
 * @return void
 */
function ppto_alerta_verificar($mysqli, $ppe_id, $ppa_id, $pej_id, $mes, $anio) {
    // 1. Obtener el presupuesto acumulado mensual para esa partida.
    $presupuesto = ppto_persistencia_consultar($mysqli, 5, array(
        'ppe_id' => $ppe_id,
        'ppa_id' => $ppa_id,
        'mes' => $mes
    ));

    if ($presupuesto <= 0) {
        return; // Sin presupuesto asignado no evaluamos alertas por porcentaje de consumo.
    }

    // 2. Obtener el ejecutado acumulado de la partida hasta el mes especificado.
    $ejecutado = ppto_persistencia_consultar($mysqli, 6, array(
        'ppe_id' => $ppe_id,
        'ppa_id' => $ppa_id,
        'pej_anio' => $anio,
        'pej_mes' => $mes
    ));

    $pct = ($ejecutado / $presupuesto) * 100;

    // Umbrales fijos del sistema
    $umbrales = array(80, 90, 100);
    foreach ($umbrales as $umbral) {
        if ($pct >= $umbral) {
            // El caso 7 inserta con IGNORE la alerta de manera que no se repitan alertas idénticas para la misma ejecución.
            ppto_persistencia_consultar($mysqli, 7, array(
                'ppe_id' => $ppe_id,
                'ppa_id' => $ppa_id,
                'pal_umbral' => $umbral,
                'pal_porcentaje_actual' => $pct,
                'pej_id' => $pej_id
            ));
        }
    }
}

/**
 * Función principal expuesta para que otros módulos registren un egreso o ingreso en el Presupuesto.
 * Soporta de forma completamente retrocompatible el control de proyectos por rubro y fases (Comprometido/Ejecutado).
 *
 * @param mysqli $mysqli Conexión activa.
 * @param int $emp_id ID de empresa.
 * @param int $usu_id ID del usuario creador.
 * @param string $tip_doc Tipo de documento origen.
 * @param string $doc_id Código del documento.
 * @param string $estado Estado transaccional del documento ('A' = Activo, 'I' = Inactivo/Anulado).
 * @param string $fecha Fecha del documento (AAAA-MM-DD).
 * @param float $monto Importe numérico absoluto.
 * @param int|null $suc_id Sucursal (opcional).
 * @param int|null $dep_id Departamento (opcional).
 * @param string|null $proy_id Código de proyecto (opcional para control por proyectos).
 * @param string $pej_fase Fase presupuestaria ('C' = Comprometido, 'E' = Ejecutado). Por defecto es 'E'.
 * @param string|null $pej_rubro Nombre del rubro analítico del proyecto (opcional).
 * @return bool Retorna true si fue imputado con éxito, false en caso contrario.
 */
function ppto_documento_ejecutar($mysqli, $emp_id, $usu_id, $tip_doc, $doc_id, $estado, $fecha, $monto, $suc_id = null, $dep_id = null, $proy_id = null, $pej_fase = 'E', $pej_rubro = null) {
    if (!$mysqli || empty($emp_id) || empty($tip_doc) || empty($doc_id) || empty($fecha) || $monto <= 0) {
        return false;
    }

    $time = strtotime($fecha);
    if (!$time) {
        return false;
    }
    $anio = (int)date('Y', $time);
    $mes = (int)date('n', $time);

    // 1. Localizar la versión presupuestaria aprobada y activa ('A') de la empresa para ese año.
    $ppe_id = ppto_persistencia_consultar($mysqli, 1, array('emp_id' => $emp_id, 'ppe_anio' => $anio));
    if (!$ppe_id) {
        return false; // Sin presupuesto parametrizado o activo, no hay afectación.
    }

    // 2. Buscar regla de coincidencia secuencial.
    $regla = ppto_regla_buscar($mysqli, $emp_id, $tip_doc, $doc_id);
    if (!$regla) {
        return false; // Documento no aplica a ninguna regla presupuestaria activa.
    }

    $sig = $regla['prg_signo'];
    // Regla de Integridad de Anulación: Si el estado del documento es Inactivo ('I'), invertimos el signo para extornar el gasto.
    if ($estado === 'I') {
        $sig = ($sig === '+') ? '-' : '+';
    }

    // 3. Prevenir duplicación accidental (validando si ya se asentó el mismo documento con el mismo signo).
    $duplicado = ppto_persistencia_consultar($mysqli, 3, array(
        'pej_tipo_documento' => $tip_doc,
        'pej_documento_codigo' => $doc_id,
        'pej_signo' => $sig
    ));
    if ($duplicado) {
        return false;
    }

    // 4. Escribir la afectación en el ledger presupuestario.
    $pej_id = ppto_persistencia_consultar($mysqli, 4, array(
        'ppe_id' => $ppe_id,
        'ppa_id' => $regla['ppa_id'],
        'emp_id' => $emp_id,
        'suc_id' => $suc_id,
        'dep_id' => $dep_id,
        'proy_id' => $proy_id,
        'pej_mes' => $mes,
        'pej_anio' => $anio,
        'pej_tipo_documento' => $tip_doc,
        'pej_documento_codigo' => $doc_id,
        'pej_monto' => $monto,
        'pej_signo' => $sig,
        'pej_fecha_documento' => $fecha,
        'usu_id' => $usu_id,
        'prg_id' => $regla['prg_id'],
        'pej_fase' => $pej_fase,
        'pej_rubro' => $pej_rubro
    ));

    if (!$pej_id) {
        return false;
    }

    // 5. Integración con Proyectos: Actualizar el balance atómico mensual del proyecto
    if (!empty($proy_id) && !empty($pej_rubro)) {
        $clean_proy = $mysqli->real_escape_string($proy_id);
        $clean_rubro = $mysqli->real_escape_string($pej_rubro);
        
        $sql_pdp = "SELECT pdp_id FROM exa_ppto_proyecto_detalles 
                    WHERE ppe_id = $ppe_id 
                      AND ppa_id = " . (int)$regla['ppa_id'] . " 
                      AND proy_id = '$clean_proy' 
                      AND pdp_rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_pdp = $mysqli->query($sql_pdp);
        if ($res_pdp && $row_pdp = $res_pdp->fetch_assoc()) {
            $pdp_id = (int)$row_pdp['pdp_id'];
            
            // Valor con signo (+ o -) para suma/resta acumulativa
            $signed_val = ($sig === '+' ? (float)$monto : -(float)$monto);
            $comp_val = ($pej_fase === 'C') ? $signed_val : 0.00;
            $ejec_val = ($pej_fase === 'E') ? $signed_val : 0.00;
            
            $mysqli->query("INSERT INTO exa_ppto_proyecto_detalles_mes 
                                (pdp_id, pdm_mes, pdm_dias_laborables, pdm_factor_mensual, pdm_presupuesto_mensual, pdm_ejecutado, pdm_comprometido, pdm_disponible)
                            VALUES 
                                ($pdp_id, $mes, 20, 0.0833, 0.00, IF($ejec_val < 0, 0.00, $ejec_val), IF($comp_val < 0, 0.00, $comp_val), 0.00)
                            ON DUPLICATE KEY UPDATE 
                                pdm_ejecutado = pdm_ejecutado + $ejec_val,
                                pdm_comprometido = pdm_comprometido + $comp_val,
                                pdm_disponible = pdm_presupuesto_mensual - (pdm_ejecutado + $ejec_val) - (pdm_comprometido + $comp_val)");
        }
    }

    // 6. Correr validaciones asíncronas de superación de límites y alertas.
    ppto_alerta_verificar($mysqli, $ppe_id, $regla['ppa_id'], $pej_id, $mes, $anio);

    return true;
}
