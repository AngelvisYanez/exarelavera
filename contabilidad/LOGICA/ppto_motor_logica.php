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
 * @param int $Emp_Cod Código de empresa.
 * @param string $tip_doc Tipo de documento transaccional.
 * @param string $doc_id Clave primaria del documento específico.
 * @return array|null Regla coincidente o null si ninguna aplica.
 */
function ppto_regla_buscar($mysqli, $Emp_Cod, $tip_doc, $doc_id) {
    // Obtener todas las reglas activas para este origen y empresa ordenadas por prioridad.
    $reglas = ppto_persistencia_consultar($mysqli, 2, array('Emp_Cod' => $Emp_Cod, 'prg_tipo_documento' => $tip_doc));
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

    // 2. Obtener la ejecución real acumulada de esa partida.
    $ejecutado = ppto_persistencia_consultar($mysqli, 6, array(
        'ppe_id' => $ppe_id,
        'ppa_id' => $ppa_id,
        'pej_anio' => $anio,
        'pej_mes' => $mes
    ));

    $porcentaje = ($ejecutado / $presupuesto) * 100;

    // 3. Evaluar umbrales críticos de alarma
    if ($porcentaje >= 100) {
        ppto_persistencia_consultar($mysqli, 7, array(
            'ppe_id' => $ppe_id,
            'ppa_id' => $ppa_id,
            'pal_umbral' => 100,
            'pal_porcentaje_actual' => $porcentaje,
            'pej_id' => $pej_id
        ));
    } elseif ($porcentaje >= 80) {
        ppto_persistencia_consultar($mysqli, 7, array(
            'ppe_id' => $ppe_id,
            'ppa_id' => $ppa_id,
            'pal_umbral' => 80,
            'pal_porcentaje_actual' => $porcentaje,
            'pej_id' => $pej_id
        ));
    }
}

/**
 * Transacciona e imputa un documento en el ledger general de ejecución de presupuestos.
 *
 * @param mysqli $mysqli Conexión a la BD.
 * @param array $p Arreglo con la parametrización de la transacción.
 * @return bool Retorna true si se registró y evaluó correctamente, false de lo contrario.
 */
function ppto_documento_ejecutar($mysqli, $p) {
    // Verificar si ya existe una transacción idéntica previa
    $existe = ppto_persistencia_consultar($mysqli, 3, array(
        'pej_tipo_documento' => $p['pej_tipo_documento'],
        'pej_documento_codigo' => $p['pej_documento_codigo'],
        'pej_signo' => $p['pej_signo']
    ));

    if ($existe) {
        return true; // Transacción previamente procesada; se descarta para evitar duplicidad.
    }

    // Insertar la nueva transacción en la tabla pre_ejecucion
    $pej_id = ppto_persistencia_consultar($mysqli, 4, $p);

    if ($pej_id) {
        // Recalcular la ejecución y verificar desvíos/alertas
        ppto_alerta_verificar($mysqli, $p['ppe_id'], $p['ppa_id'], $pej_id, $p['pej_mes'], $p['pej_anio']);

        // Si la transacción proviene de un rubro de proyecto específico, recalcular su disponibilidad
        if (!empty($p['proy_id']) && !empty($p['pej_rubro'])) {
            include_once('ppto_motor_calculo.php');
            $clean_proy = $mysqli->real_escape_string($p['proy_id']);
            $clean_rubro = $mysqli->real_escape_string($p['pej_rubro']);

            $sql_pdp = "SELECT Pdp_Cod FROM pre_proyecto_detalles 
                        WHERE Ppe_Cod = " . (int)$p['ppe_id'] . " 
                          AND Ppa_Cod = " . (int)$p['ppa_id'] . " 
                          AND Pro_Cod = '$clean_proy' 
                          AND Pdp_Rubro = '$clean_rubro' LIMIT 1";
            $res_pdp = $mysqli->query($sql_pdp);
            if ($res_pdp && $row_pdp = $res_pdp->fetch_assoc()) {
                $pdp_id = (int)$row_pdp['Pdp_Cod'];
                
                // Actualización atómica de disponibilidades del rubro del proyecto
                $ejecutado = (float)$p['pej_monto'];
                $mes = (int)$p['pej_mes'];
                $fase = isset($p['pej_fase']) ? $p['pej_fase'] : 'E';

                if ($fase === 'C') {
                    $mysqli->query("UPDATE pre_proyecto_detalles_mes 
                                    SET Pdm_Comprometido = Pdm_Comprometido + $ejecutado,
                                        Pdm_Disponible = GREATEST(0, Pdm_PreMensual - Pdm_Ejecutado - (Pdm_Comprometido + $ejecutado))
                                    WHERE Pdp_Cod = $pdp_id AND Pdm_Mes = $mes");
                } else {
                    $mysqli->query("UPDATE pre_proyecto_detalles_mes 
                                    SET Pdm_Ejecutado = Pdm_Ejecutado + $ejecutado,
                                        Pdm_Disponible = GREATEST(0, Pdm_PreMensual - (Pdm_Ejecutado + $ejecutado) - Pdm_Comprometido)
                                    WHERE Pdp_Cod = $pdp_id AND Pdm_Mes = $mes");
                }
            }
        }

        return true;
    }

    return false;
}
