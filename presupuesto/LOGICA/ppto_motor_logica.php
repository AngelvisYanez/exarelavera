<?php
/**
 * ppto_motor_logica.php
 * Motor de Reglas e Integraciï¿½n Transaccional para EXA PPTO.
 * Procesa la evaluaciï¿½n de reglas e imputaciï¿½n de documentos contables.
 */

include_once('ppto_persistencia_logica.php');

/**
 * Mapea el tipo de documento del ERP con el nombre de su clave primaria fï¿½sica en la base de datos.
 *
 * @param string $tip_doc Tipo de documento (ventas, compras, rol_pagos, comprobantes, movimiento_cheques, asientos).
 * @return string|null Nombre de la clave primaria o null si no estï¿½ soportado.
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
            return 'mov_id';
        case 'asientos':
            return 'Asi_Cod';
        default:
            return null;
    }
}

/**
 * Evalï¿½a secuencialmente las reglas parametrizadas para un documento a fin de identificar la partida aplicable.
 *
 * @param mysqli $mysqli Conexiï¿½n a la BD.
 * @param int $Emp_Cod Cï¿½digo de empresa.
 * @param string $tip_doc Tipo de documento transaccional.
 * @param string $doc_id Clave primaria del documento especï¿½fico.
 * @return array|null Regla coincidente o null si ninguna aplica.
 */
function ppto_regla_buscar($mysqli, $Emp_Cod, $tip_doc, $doc_id) {
    // Obtener todas las reglas activas para este origen y empresa ordenadas por prioridad.
    $reglas = ppto_persistencia_consultar($mysqli, 2, array('Emp_Cod' => $Emp_Cod, 'Prg_TipDoc' => $tip_doc));
    if (empty($reglas)) {
        return null;
    }

    foreach ($reglas as $regla) {
        // Regla general directa (aplica directo sin evaluar condiciones adicionales).
        $campo_eval = isset($regla['prg_campo_evaluacion']) ? $regla['prg_campo_evaluacion'] : (isset($regla['Prg_Campo']) ? $regla['Prg_Campo'] : '');
        $valor_esp = isset($regla['prg_valor_esperado']) ? $regla['prg_valor_esperado'] : (isset($regla['Prg_Valor']) ? $regla['Prg_Valor'] : '');
        if ($campo_eval === '' || $campo_eval === null || $valor_esp === '' || $valor_esp === null) {
            return $regla;
        }

        $pk_campo = ppto_documento_pk_obtener($tip_doc);
        if (!$pk_campo) {
            continue;
        }

        $clean_doc_id = $mysqli->real_escape_string($doc_id);
        $clean_table = $mysqli->real_escape_string($tip_doc);
        $clean_pk = $mysqli->real_escape_string($pk_campo);
        $clean_campo = $mysqli->real_escape_string($campo_eval);

        // Consulta dinamica en la tabla origen para comprobar la condicion especial de evaluacion.
        $sql = "SELECT `$clean_campo` FROM `$clean_table` WHERE `$clean_pk` = '$clean_doc_id' LIMIT 1";
        $res = $mysqli->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            if ($row[$campo_eval] == $valor_esp) {
                return $regla;
            }
        }
    }

    return null;
}

/**
 * Audita e inserta alertas si el porcentaje de ejecuciï¿½n actual de una partida supera los lï¿½mites.
 *
 * @param mysqli $mysqli Conexiï¿½n a la BD.
 * @param int $Ppe_Cod ID de la versiï¿½n de presupuesto afectada.
 * @param int $Ppa_Cod ID de la partida presupuestaria afectada.
 * @param int $Pej_Cod ID de la ejecuciï¿½n presupuestaria causante.
 * @param int $mes Mes transaccionado.
 * @param int $anio Aï¿½o fiscal de la transacciï¿½n.
 * @return void
 */
function ppto_alerta_verificar($mysqli, $Ppe_Cod, $Ppa_Cod, $Pej_Cod, $mes, $anio) {
    // 1. Obtener el presupuesto acumulado mensual para esa partida.
    $presupuesto = ppto_persistencia_consultar($mysqli, 5, array(
        'Ppe_Cod' => $Ppe_Cod,
        'Ppa_Cod' => $Ppa_Cod,
        'mes' => $mes
    ));

    if ($presupuesto <= 0) {
        return; // Sin presupuesto asignado no evaluamos alertas por porcentaje de consumo.
    }

    // 2. Obtener el ejecutado acumulado de la partida hasta el mes especificado.
    $ejecutado = ppto_persistencia_consultar($mysqli, 6, array(
        'Ppe_Cod' => $Ppe_Cod,
        'Ppa_Cod' => $Ppa_Cod,
        'Pej_Ani' => $anio,
        'Pej_Mes' => $mes
    ));

    $pct = ($ejecutado / $presupuesto) * 100;

    // Umbrales fijos del sistema
    $umbrales = array(80, 90, 100);
    foreach ($umbrales as $umbral) {
        if ($pct >= $umbral) {
            // El caso 7 inserta con IGNORE la alerta de manera que no se repitan alertas idï¿½nticas para la misma ejecuciï¿½n.
            ppto_persistencia_consultar($mysqli, 7, array(
                'Ppe_Cod' => $Ppe_Cod,
                'Ppa_Cod' => $Ppa_Cod,
                'pal_umbral' => $umbral,
                'pal_porcentaje_actual' => $pct,
                'Pej_Cod' => $Pej_Cod
            ));
        }
    }
}

/**
 * Funciï¿½n principal expuesta para que otros mï¿½dulos registren un egreso o ingreso en el Presupuesto.
 * Soporta de forma completamente retrocompatible el control de proyectos por rubro y fases (Comprometido/Ejecutado).
 *
 * @param mysqli $mysqli Conexiï¿½n activa.
 * @param int $Emp_Cod ID de empresa.
 * @param int $Usu_Cod ID del usuario creador.
 * @param string $tip_doc Tipo de documento origen.
 * @param string $doc_id Cï¿½digo del documento.
 * @param string $estado Estado transaccional del documento ('A' = Activo, 'I' = Inactivo/Anulado).
 * @param string $fecha Fecha del documento (AAAA-MM-DD).
 * @param float $monto Importe numï¿½rico absoluto.
 * @param int|null $Suc_Cod Sucursal (opcional).
 * @param int|null $Dep_Cod Departamento (opcional).
 * @param string|null $Pro_Cod Cï¿½digo de proyecto (opcional para control por proyectos).
 * @param string $Pej_Fase Fase presupuestaria ('C' = Comprometido, 'E' = Ejecutado). Por defecto es 'E'.
 * @param string|null $Pej_Rubro Nombre del rubro analï¿½tico del proyecto (opcional).
 * @return bool Retorna true si fue imputado con ï¿½xito, false en caso contrario.
 */
function ppto_documento_ejecutar($mysqli, $Emp_Cod, $Usu_Cod, $tip_doc, $doc_id, $estado, $fecha, $monto, $Suc_Cod = null, $Dep_Cod = null, $Pro_Cod = null, $Pej_Fase = 'E', $Pej_Rubro = null) {
    if (!$mysqli || empty($Emp_Cod) || empty($tip_doc) || empty($doc_id) || empty($fecha) || $monto <= 0) {
        return false;
    }

    $time = strtotime($fecha);
    if (!$time) {
        return false;
    }
    $anio = (int)date('Y', $time);
    $mes = (int)date('n', $time);

    // 1. Localizar la versiï¿½n presupuestaria aprobada y activa ('A') de la empresa para ese aï¿½o.
    $Ppe_Cod = ppto_persistencia_consultar($mysqli, 1, array('Emp_Cod' => $Emp_Cod, 'Ppe_Ani' => $anio));
    if (!$Ppe_Cod) {
        return false; // Sin presupuesto parametrizado o activo, no hay afectaciï¿½n.
    }

    // 2. Buscar regla de coincidencia secuencial.
    $regla = ppto_regla_buscar($mysqli, $Emp_Cod, $tip_doc, $doc_id);
    if (!$regla) {
        return false; // Documento no aplica a ninguna regla presupuestaria activa.
    }

    $sig = $regla['Prg_Signo'];
    // Regla de Integridad de Anulaciï¿½n: Si el estado del documento es Inactivo ('I'), invertimos el signo para extornar el gasto.
    if ($estado === 'I') {
        $sig = ($sig === '+') ? '-' : '+';
    }

    // 3. Prevenir duplicaciï¿½n accidental (validando si ya se asentï¿½ el mismo documento con el mismo signo).
    $duplicado = ppto_persistencia_consultar($mysqli, 3, array(
        'Pej_TipDoc' => $tip_doc,
        'Pej_DocCod' => $doc_id,
        'Pej_Sig' => $sig
    ));
    if ($duplicado) {
        return false;
    }

    // 4. Escribir la afectaciï¿½n en el ledger presupuestario.
    $Pej_Cod = ppto_persistencia_consultar($mysqli, 4, array(
        'Ppe_Cod' => $Ppe_Cod,
        'Ppa_Cod' => $regla['Ppa_Cod'],
        'Emp_Cod' => $Emp_Cod,
        'Suc_Cod' => $Suc_Cod,
        'Dep_Cod' => $Dep_Cod,
        'Pro_Cod' => $Pro_Cod,
        'Pej_Mes' => $mes,
        'Pej_Ani' => $anio,
        'Pej_TipDoc' => $tip_doc,
        'Pej_DocCod' => $doc_id,
        'Pej_Mon' => $monto,
        'Pej_Sig' => $sig,
        'Pej_Fec' => $fecha,
        'Usu_Cod' => $Usu_Cod,
        'Prg_Cod' => $regla['Prg_Cod'],
        'Pej_Fase' => $Pej_Fase,
        'Pej_Rubro' => $Pej_Rubro
    ));

    if (!$Pej_Cod) {
        return false;
    }

    // 5. Integraciï¿½n con Proyectos: Actualizar el balance atï¿½mico mensual del proyecto
    if (!empty($Pro_Cod) && !empty($Pej_Rubro)) {
        $clean_proy = $mysqli->real_escape_string($Pro_Cod);
        $clean_rubro = $mysqli->real_escape_string($Pej_Rubro);
        
        $sql_pdp = "SELECT Pdp_Cod AS Pdp_Cod FROM pre_proyecto_detalles 
                    WHERE Ppe_Cod = $Ppe_Cod 
                      AND Ppa_Cod = " . (int)$regla['Ppa_Cod'] . " 
                      AND Pro_Cod = '$clean_proy' 
                      AND Pdp_Rubro = '$clean_rubro' 
                    LIMIT 1";
        $res_pdp = $mysqli->query($sql_pdp);
        if ($res_pdp && $row_pdp = $res_pdp->fetch_assoc()) {
            $Pdp_Cod = (int)$row_pdp['Pdp_Cod'];
            
            // Valor con signo (+ o -) para suma/resta acumulativa
            $signed_val = ($sig === '+' ? (float)$monto : -(float)$monto);
            $comp_val = ($Pej_Fase === 'C') ? $signed_val : 0.00;
            $ejec_val = ($Pej_Fase === 'E') ? $signed_val : 0.00;
            
            $mysqli->query("INSERT INTO pre_proyecto_detalles_mes 
                                (Pdp_Cod, Pdm_Mes, Pdm_DiasLab, Pdm_FacMensual, Pdm_PreMensual, Pdm_Ejecutado, Pdm_Comprometido, Pdm_Disponible)
                            VALUES 
                                ($Pdp_Cod, $mes, 20, 0.0833, 0.00, IF($ejec_val < 0, 0.00, $ejec_val), IF($comp_val < 0, 0.00, $comp_val), 0.00)
                            ON DUPLICATE KEY UPDATE 
                                Pdm_Ejecutado = Pdm_Ejecutado + $ejec_val,
                                Pdm_Comprometido = Pdm_Comprometido + $comp_val,
                                Pdm_Disponible = Pdm_PreMensual - (Pdm_Ejecutado + $ejec_val) - (Pdm_Comprometido + $comp_val)");
        }
    }

    // 6. Correr validaciones asï¿½ncronas de superaciï¿½n de lï¿½mites y alertas.
    ppto_alerta_verificar($mysqli, $Ppe_Cod, $regla['Ppa_Cod'], $Pej_Cod, $mes, $anio);

    return true;
}
