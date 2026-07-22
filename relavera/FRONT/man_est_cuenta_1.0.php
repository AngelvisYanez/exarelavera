<?php

/* DIRECTORIOS REQUERIDOS */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_man_est_cuenta_1.0.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_Estado_Cuenta($Ses_Dat_Dis);
/* Creacion del objeto mysql para las consultas */
$obBD_con1 =  new Class_Log_Datos_Estado_Cuenta;

/* formato para fechas */
$hoy = date("Y-m-d");
$hora = date("H:i:s");
$mes = date("m");

/* para pruebas */
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 9600);

/* DECLARACION DE AJAX */

/* Obtiene el cliente del usuario logueado */
$cliente_manifiesto = $obBD_con1->getRowConsulta(7, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);

/* Verificar perfil de usuario */
$row_perfil = $obBD_con1->getRowConsulta(8, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);
$es_perfil_plantas = (isset($row_perfil['count']) && $row_perfil['count'] > 0);
$row_admin_comp = $obBD_con1->getRowConsulta(14, array('Usu_Cod' => $Ses_Usu_Cod), $obBD_conexion);
$puede_comparar_saldos = (isset($row_admin_comp['count']) && (int) $row_admin_comp['count'] > 0);


// Cargar datos del grid principal
if(isset($_REQUEST['loadEstadoCuentaAjax'])){
    $parms = array(
        'Fec_IniM' => isset($_REQUEST['Fec_IniM']) ? $_REQUEST['Fec_IniM'] : '',
        'Fec_FinM' => isset($_REQUEST['Fec_FinM']) ? $_REQUEST['Fec_FinM'] : '',
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00',
        'Cli_Cod' => isset($_REQUEST['Cli_Cod']) ? $_REQUEST['Cli_Cod'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(1, $parms, $obBD_conexion);
    
    // Calcular Saldo para cada registro (Ama_Val - Abono)
    if (is_array($rows)) {
        foreach ($rows as &$row) {
            $abono = isset($row['Abono']) ? floatval($row['Abono']) : 0;
            $ama_val = isset($row['Ama_Val']) ? floatval($row['Ama_Val']) : 0;
            $saldo = $ama_val - $abono;
            $row['Saldo'] = floatval($saldo);
            $row['Ama_Val'] = floatval($ama_val);
            $row['Abono'] = floatval($abono);
        }
    }
    
    $resp = array( 'success' => true, 'rows' => $rows, 'total' => count($rows) );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar detalle/balance de un cliente
if(isset($_REQUEST['loadDetalleAjax'])){
    $resp = array('success' => false, 'message' => '');
    
    if (!isset($_REQUEST['Cli_Cod']) || empty($_REQUEST['Cli_Cod'])) {
        $resp['message'] = 'No se proporcionó el código del cliente';
        $obBD_con1->echoJson($resp);
        exit();
    }
    
    $parms = array(
        'Cli_Cod' => $_REQUEST['Cli_Cod'],
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Fec_Ini' => isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '',
        'Fec_Fin' => isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00'
    );
    
    // Obtener movimientos
    $data = $obBD_con1->getArrayConsulta(2, $parms, $obBD_conexion);
    
    // Obtener resumen
    $resumen_parms = array(
        'Cli_Cod' => $_REQUEST['Cli_Cod'],
        'Pla_Cod' => isset($_REQUEST['Pla_Cod']) ? $_REQUEST['Pla_Cod'] : '',
        'Fec_Ini' => isset($_REQUEST['Fec_Ini']) ? $_REQUEST['Fec_Ini'] : '',
        'Fec_Fin' => isset($_REQUEST['Fec_Fin']) ? $_REQUEST['Fec_Fin'] : ''
    );
    $resumen = $obBD_con1->getRowConsulta(5, $resumen_parms, $obBD_conexion);
        
    // Cconsulta dedicada para cabecera completa (Cliente + Cuenta)
    $header_info = $obBD_con1->getRowConsulta(12, array('Cli_Cod' => $_REQUEST['Cli_Cod']), $obBD_conexion);
    
    if ($obBD_con1->Error == 0) {
        $resp['success'] = true;
        $resp['data'] = isset($data['rows']) ? $data['rows'] : array();
        if (isset($data[0])) $resp['data'] = $data;
        
        $resp['resumen'] = $resumen;
        // Usar datos de header_info
        $resp['cliente'] = isset($header_info['Cliente']) ? $header_info['Cliente'] : 'Cliente';
        $resp['cliente_ruc'] = isset($header_info['Prs_Ced']) ? $header_info['Prs_Ced'] : '';
        $resp['cliente_cuenta'] = isset($header_info['Ban_Cue']) ? $header_info['Ban_Cue'] : '';
        $resp['message'] = 'Datos cargados correctamente';
    } else {
        $resp['message'] = 'Error al cargar datos: ' . $obBD_con1->MsgError;
    }
    
    $obBD_con1->echoJson($resp);
    exit();
}

// Consolidado: manifiestos técnicos (misma consulta que man_tec_1.0) + resumen manifiestos pendientes (estado de cuenta)
if (isset($_REQUEST['loadConsolidadoTecAjax'])) {
    $cli = isset($_REQUEST['Cli_Cod']) ? trim((string) $_REQUEST['Cli_Cod']) : '';
    $pla = isset($_REQUEST['Pla_Cod']) ? trim((string) $_REQUEST['Pla_Cod']) : '';
    $fi = isset($_REQUEST['Fec_IniM']) ? trim((string) $_REQUEST['Fec_IniM']) : '';
    $ff = isset($_REQUEST['Fec_FinM']) ? trim((string) $_REQUEST['Fec_FinM']) : '';

    $resp = array('success' => false, 'rows' => array(), 'message' => '');
    if ($cli === '') {
        $resp['message'] = 'Seleccione una planta con cliente asociado.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $header_info = $obBD_con1->getRowConsulta(12, array('Cli_Cod' => $cli), $obBD_conexion);
    $resumen = $obBD_con1->getRowConsulta(5, array(
        'Cli_Cod' => $cli,
        'Pla_Cod' => $pla,
        'Fec_Ini' => $fi,
        'Fec_Fin' => $ff,
    ), $obBD_conexion);
    $detalleEc = $obBD_con1->getArrayConsulta(18, array(
        'Cli_Cod' => $cli,
        'Pla_Cod' => $pla,
        'Fec_Ini' => $fi,
        'Fec_Fin' => $ff,
        'Mes_Cod' => '00',
    ), $obBD_conexion);
    $rowC = $obBD_con1->getRowConsulta(17, array(
        'Cli_Cod' => $cli,
        'Pla_Cod' => $pla,
        'Fec_Ini' => $fi,
        'Fec_Fin' => $ff,
    ), $obBD_conexion);

    $resp['success'] = true;
    $resp['rows'] = array(); // ya no se usa en render consolidado
    $resp['detalle_ec'] = is_array($detalleEc) ? $detalleEc : array();
    $resp['cliente'] = isset($header_info['Cliente']) ? $header_info['Cliente'] : '';
    $resp['cliente_ruc'] = isset($header_info['Prs_Ced']) ? $header_info['Prs_Ced'] : '';
    $resp['resumen'] = is_array($resumen) ? $resumen : array();
    $resp['manif_pend_cnt'] = (is_array($rowC) && isset($rowC['ManifiestosPendCnt'])) ? (int) $rowC['ManifiestosPendCnt'] : 0;
    $resp['ult_fec_fact'] = (is_array($rowC) && !empty($rowC['UltFecFact'])) ? $rowC['UltFecFact'] : '';
    $resp['ult_fec_man_gen'] = (is_array($rowC) && !empty($rowC['UltFecManGen'])) ? $rowC['UltFecManGen'] : '';
    $resp['manif_pend_monto'] = (is_array($resumen) && isset($resumen['ManifiestosPend'])) ? floatval($resumen['ManifiestosPend']) : 0;
    $saldoInicial = (is_array($resumen) && isset($resumen['SaldoInicial'])) ? floatval($resumen['SaldoInicial']) : 0;
    if (is_array($detalleEc) && count($detalleEc) > 0 && isset($detalleEc[0]['Saldo_Inicial_Hidden'])) {
        $saldoInicial = floatval($detalleEc[0]['Saldo_Inicial_Hidden']);
    }
    $resp['saldo_inicial'] = $saldoInicial;
    $resp['filtro'] = isset($_REQUEST['filtro']) ? trim((string)$_REQUEST['filtro']) : '';
    $resp['search'] = isset($_REQUEST['search']) ? trim((string)$_REQUEST['search']) : '';
    $resp['message'] = 'OK';
    $obBD_con1->echoJson($resp);
    exit();
}

// Buscar plantas
if(isset($_REQUEST['loadPlantasAjax'])){
    $parms = array(
        'search' => isset($_REQUEST['search']) ? $_REQUEST['search'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(6, $parms, $obBD_conexion);
    
    $resp = array( 'success' => true, 'rows' => $rows );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar detalle del movimiento (subgrid)
if(isset($_REQUEST['loadDetalleMovimientoAjax'])){
    $parms = array(
        'Ama_Cod' => isset($_REQUEST['Ama_Cod']) ? $_REQUEST['Ama_Cod'] : ''
    );
    $rows = $obBD_con1->getArrayConsulta(7, $parms, $obBD_conexion);
    
    $resp = array( 'success' => true, 'rows' => $rows );
    $obBD_con1->echoJson($resp);
    exit();
}

// Cargar datos grupales (por planta)
if (isset($_REQUEST['loadEstadoCuentaGrupalAjax'])) {
    $parms = array(
        'Fec_IniM' => isset($_REQUEST['Fec_IniM']) ? $_REQUEST['Fec_IniM'] : '',
        'Fec_FinM' => isset($_REQUEST['Fec_FinM']) ? $_REQUEST['Fec_FinM'] : '',
        'Mes_Cod' => isset($_REQUEST['Mes_Cod']) ? $_REQUEST['Mes_Cod'] : '00'
    );
    $rows = $obBD_con1->getArrayConsulta(9, $parms, $obBD_conexion);

    // Calcular totales para cada registro
    if (is_array($rows)) {
        foreach ($rows as &$row) {
            $saldo_inicial = isset($row['Saldo_Inicial']) ? floatval($row['Saldo_Inicial']) : 0;
            $depositos = isset($row['Depositos']) ? floatval($row['Depositos']) : 0;
            $retenciones = isset($row['Retenciones']) ? floatval($row['Retenciones']) : 0;
            $manifiestos_fact = isset($row['Manifiestos_Fact']) ? floatval($row['Manifiestos_Fact']) : 0;
            $manifiestos_pend = isset($row['Manifiestos_Pend']) ? floatval($row['Manifiestos_Pend']) : 0;

            $row['Saldo_Inicial'] = floatval($saldo_inicial);
            $row['Depositos'] = floatval($depositos);
            $row['Retenciones'] = floatval($retenciones);
            $row['Manifiestos_Fact'] = floatval($manifiestos_fact);
            $row['Manifiestos_Pend'] = floatval($manifiestos_pend);
        }
    }

    $resp = array('success' => true, 'rows' => $rows, 'total' => count($rows));
    $obBD_con1->echoJson($resp);
    exit();
}

// Comparación: saldo reporte grupal (período) vs saldo cabecera manifiesto (A - B) — solo administradores
if (isset($_REQUEST['loadComparacionSaldosAjax'])) {
    $resp = array('success' => false, 'message' => 'No autorizado.');
    if (!$puede_comparar_saldos) {
        $obBD_con1->echoJson($resp);
        exit();
    }
    $empCod = isset($_SESSION['Ses_Emp_Cod']) ? $_SESSION['Ses_Emp_Cod'] : '';
    $fecIni = isset($_REQUEST['Fec_IniM']) ? $_REQUEST['Fec_IniM'] : '';
    $fecFin = isset($_REQUEST['Fec_FinM']) ? $_REQUEST['Fec_FinM'] : '';
    $compararTodas = isset($_REQUEST['comparar_todas']) && (string) $_REQUEST['comparar_todas'] === '1';

    if ($empCod === '') {
        $resp['message'] = 'Falta empresa en sesión.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $filaComparacionPlanta = function ($plaCod, $cliCod) use ($obBD_con1, $obBD_conexion, $empCod, $fecIni, $fecFin) {
        $parmsEc = array(
            'Fec_IniM' => $fecIni,
            'Fec_FinM' => $fecFin,
            'Pla_Cod' => $plaCod,
        );
        $rowEc = $obBD_con1->getRowConsulta(10, $parmsEc, $obBD_conexion);
        if (!$rowEc || !isset($rowEc['Pla_Cod'])) {
            return null;
        }

        $rowAnt = $obBD_con1->getRowConsulta(11, array('Pla_Cod' => $plaCod), $obBD_conexion);
        $rowSf = $obBD_con1->getRowConsulta(13, array(
            'Cli_Cod' => $cliCod,
            'Pla_Cod' => $plaCod,
            'Emp_Cod' => $empCod,
        ), $obBD_conexion);

        $si = isset($rowEc['Saldo_Inicial']) ? floatval($rowEc['Saldo_Inicial']) : 0;
        $dep = isset($rowEc['Depositos']) ? floatval($rowEc['Depositos']) : 0;
        $ret = isset($rowEc['Retenciones']) ? floatval($rowEc['Retenciones']) : 0;
        $mf = isset($rowEc['Manifiestos_Fact']) ? floatval($rowEc['Manifiestos_Fact']) : 0;
        $mp = isset($rowEc['Manifiestos_Pend']) ? floatval($rowEc['Manifiestos_Pend']) : 0;
        $saldo_ec = $si + $dep + $ret - $mf - $mp;

        $anticipo = isset($rowAnt['saldo']) ? floatval($rowAnt['saldo']) : 0;
        $sinFact = isset($rowSf['saldo']) ? floatval($rowSf['saldo']) : 0;
        $saldo_cab = $anticipo - $sinFact;
        $diferencia = $saldo_ec - $saldo_cab;

        return array(
            'pla_cod' => $plaCod,
            'planta' => isset($rowEc['Planta']) ? $rowEc['Planta'] : '',
            'saldo_estado_cuenta' => $saldo_ec,
            'anticipo' => $anticipo,
            'sin_facturar' => $sinFact,
            'saldo_cabecera_manifiesto' => $saldo_cab,
            'diferencia' => $diferencia,
            'detalle_ec' => array(
                'saldo_inicial' => $si,
                'depositos' => $dep,
                'retenciones' => $ret,
                'manifiestos_fact' => $mf,
                'manifiestos_pend' => $mp,
            ),
        );
    };

    if ($compararTodas) {
        $lista = $obBD_con1->getArrayConsulta(6, array('search' => ''), $obBD_conexion);
        if (!is_array($lista)) {
            $lista = array();
        }
        $filas = array();
        $plaYa = array();
        foreach ($lista as $p) {
            if (empty($p['Pla_Cod'])) {
                continue;
            }
            $pcKey = (string) $p['Pla_Cod'];
            if (isset($plaYa[$pcKey])) {
                continue;
            }
            $plaYa[$pcKey] = true;
            $rowPla = $obBD_con1->getRowConsulta(15, array('Pla_Cod' => $p['Pla_Cod'], 'Emp_Cod' => $empCod), $obBD_conexion);
            if (!$rowPla || empty($rowPla['Cli_Cod'])) {
                continue;
            }
            $una = $filaComparacionPlanta($rowPla['Pla_Cod'], $rowPla['Cli_Cod']);
            if ($una !== null) {
                $filas[] = $una;
            }
        }
        usort($filas, function ($a, $b) {
            return strcasecmp(isset($a['planta']) ? $a['planta'] : '', isset($b['planta']) ? $b['planta'] : '');
        });
        $resp = array(
            'success' => true,
            'modo' => 'todas',
            'rows' => $filas,
            'total' => count($filas),
            'fec_ini' => $fecIni,
            'fec_fin' => $fecFin,
        );
        $obBD_con1->echoJson($resp);
        exit();
    }

    $plaReq = isset($_REQUEST['Pla_Cod']) ? trim($_REQUEST['Pla_Cod']) : '';
    if ($plaReq === '') {
        $resp['message'] = 'Seleccione una planta o use la opción “Todas las plantas”.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $rowPla = $obBD_con1->getRowConsulta(15, array('Pla_Cod' => $plaReq, 'Emp_Cod' => $empCod), $obBD_conexion);
    if (!$rowPla || !isset($rowPla['Pla_Cod']) || !isset($rowPla['Cli_Cod']) || $rowPla['Cli_Cod'] === '' || $rowPla['Cli_Cod'] === null) {
        $resp['message'] = 'Planta no válida o sin cliente asociado en esta empresa.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $una = $filaComparacionPlanta($rowPla['Pla_Cod'], $rowPla['Cli_Cod']);
    if ($una === null) {
        $resp['message'] = 'No se encontraron datos de estado de cuenta para la planta.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $resp = array_merge(array('success' => true, 'modo' => 'una'), $una);
    $obBD_con1->echoJson($resp);
    exit();
}

// Saldos Virtual - Individual (detalle movimientos con saldo acumulado)
if (isset($_REQUEST['loadSaldosVirtualAjax'])) {
    $cli = isset($_REQUEST['Cli_Cod']) ? trim((string) $_REQUEST['Cli_Cod']) : '';
    $pla = isset($_REQUEST['Pla_Cod']) ? trim((string) $_REQUEST['Pla_Cod']) : '';
    $fi = isset($_REQUEST['Fec_Ini']) ? trim((string) $_REQUEST['Fec_Ini']) : '';
    $ff = isset($_REQUEST['Fec_Fin']) ? trim((string) $_REQUEST['Fec_Fin']) : '';
    $agruparManDia = isset($_REQUEST['Agrupar_Man_Dia']) && (string) $_REQUEST['Agrupar_Man_Dia'] === '1';
    $tipRaw = isset($_REQUEST['Tip_Mov']) ? $_REQUEST['Tip_Mov'] : 'MAN,TRF,RET';
    if (is_array($tipRaw)) {
        $tips = array_map('strtoupper', $tipRaw);
    } else {
        $tips = array_map('trim', explode(',', strtoupper((string) $tipRaw)));
    }
    $tips = array_values(array_intersect($tips, array('MAN', 'TRF', 'RET')));
    if (empty($tips)) {
        $tips = array('MAN', 'TRF', 'RET');
    }

    $resp = array('success' => false, 'rows' => array(), 'message' => '');
    if ($cli === '') {
        $resp['message'] = 'Seleccione una planta / cliente.';
        $obBD_con1->echoJson($resp);
        exit();
    }
    if ($fi === '' || $ff === '') {
        $resp['message'] = 'Indique el rango de fechas.';
        $obBD_con1->echoJson($resp);
        exit();
    }

    $rows = $obBD_con1->getArrayConsulta(19, array(
        'Cli_Cod' => $cli,
        'Pla_Cod' => $pla,
        'Fec_Ini' => $fi,
        'Fec_Fin' => $ff,
        'Tip_Mov' => implode(',', $tips),
        'Agrupar_Man_Dia' => $agruparManDia ? '1' : '0',
    ), $obBD_conexion);

    $safe = array();
    $saldo = 0.0;
    $totIng = 0.0;
    $totEgr = 0.0;
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $concepto = isset($row['Concepto']) ? $row['Concepto'] : '';
            $ing = isset($row['Ingresos']) ? floatval($row['Ingresos']) : 0;
            $egr = isset($row['Egresos']) ? floatval($row['Egresos']) : 0;
            if ($concepto === 'Saldo Inicial') {
                $saldo = isset($row['Saldo_Inicial_Hidden']) ? floatval($row['Saldo_Inicial_Hidden']) : 0;
                $ing = 0;
                $egr = 0;
            } else {
                $saldo = $saldo + $ing - $egr;
                $totIng += $ing;
                $totEgr += $egr;
            }
            $safe[] = array(
                'IdMov' => isset($row['IdMov']) ? $row['IdMov'] : '',
                'Fecha' => isset($row['Fecha']) ? $row['Fecha'] : '',
                'Concepto' => $concepto,
                'Ingresos' => $ing,
                'Egresos' => $egr,
                'Saldo' => $saldo,
            );
        }
    }

    $header_info = $obBD_con1->getRowConsulta(12, array('Cli_Cod' => $cli), $obBD_conexion);
    $resp = array(
        'success' => true,
        'rows' => $safe,
        'total' => count($safe),
        'totales' => array('Ingresos' => $totIng, 'Egresos' => $totEgr, 'Saldo' => $saldo),
        'cliente' => isset($header_info['Cliente']) ? $header_info['Cliente'] : '',
        'message' => 'OK',
    );
    $obBD_con1->echoJson($resp);
    exit();
}

// Saldos Virtual - Grupal (resumen por planta)
if (isset($_REQUEST['loadSaldosVirtualGrupalAjax'])) {
    $fi = isset($_REQUEST['Fec_Ini']) ? trim((string) $_REQUEST['Fec_Ini']) : '';
    $ff = isset($_REQUEST['Fec_Fin']) ? trim((string) $_REQUEST['Fec_Fin']) : '';
    $pla = isset($_REQUEST['Pla_Cod']) ? trim((string) $_REQUEST['Pla_Cod']) : '';
    $search = isset($_REQUEST['search']) ? trim((string) $_REQUEST['search']) : '';

    if ($fi === '' || $ff === '') {
        $obBD_con1->echoJson(array('success' => false, 'rows' => array(), 'message' => 'Indique el rango de fechas.'));
        exit();
    }

    $rows = $obBD_con1->getArrayConsulta(20, array(
        'Fec_Ini' => $fi,
        'Fec_Fin' => $ff,
        'Pla_Cod' => $pla,
        'search' => $search,
    ), $obBD_conexion);

    $safe = array();
    if (is_array($rows)) {
        foreach ($rows as $row) {
            $si = isset($row['Saldo_Inicial']) ? floatval($row['Saldo_Inicial']) : 0;
            $ant = isset($row['Anticipos']) ? floatval($row['Anticipos']) : 0;
            $ret = isset($row['Anticipo_Retencion']) ? floatval($row['Anticipo_Retencion']) : 0;
            $man = isset($row['Manifiestos']) ? floatval($row['Manifiestos']) : 0;
            $safe[] = array(
                'Pla_Cod' => isset($row['Pla_Cod']) ? $row['Pla_Cod'] : '',
                'Planta' => isset($row['Planta']) ? $row['Planta'] : '',
                'Saldo_Inicial' => $si,
                'Anticipos' => $ant,
                'Anticipo_Retencion' => $ret,
                'Manifiestos' => $man,
                'Ingresos' => $ant + $ret,
                'Egresos' => $man,
                'Saldo' => $si + $ant + $ret - $man,
            );
        }
    }

    $obBD_con1->echoJson(array('success' => true, 'rows' => $safe, 'total' => count($safe), 'message' => 'OK'));
    exit();
}

/* Periodos */
$periodos = $obBD_con1->getArrayConsulta('perio_cont.selectWhere', array('perio_cont.Pec_Est' => 'A', 'setWhere' => array('setEmpCod'), 'order' => 'perio_cont.Pec_Fei DESC'), $obBD_conexion);
utf8_encode_deep($periodos);

$plantas_comparacion = array();
if ($puede_comparar_saldos) {
    $plantas_comparacion = $obBD_con1->getArrayConsulta(6, array('search' => ''), $obBD_conexion);
    if (!is_array($plantas_comparacion)) {
        $plantas_comparacion = array();
    }
}

?>

<!DOCTYPE html>
<HTML>
    <HEAD>
        <TITLE><?php echo " Estado de Cuenta"; ?></TITLE>
        <meta charset="UTF-8">

        <?php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
        <link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
        <link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
        <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
        <script type="text/javascript" src="../../framework/jquery/chosen/chosenDesc/chosenDesc.js"></script>
        
        <style>
            /* Estilos modernos para el formulario */
            .estado-cuenta-container { background: #DFE9F6; padding: 0; min-height: 100vh; }
            .estado-cuenta-card { background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
            .estado-cuenta-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: #ffffff; padding: 15px 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
            .estado-cuenta-header h3 { margin: 0; font-size: 18px; font-weight: 600; }
            .filtros-section {
                border: 1px solid #d6e0ea;
                border-radius: 10px;
                padding: 16px 18px 12px;
                margin-bottom: 18px;
                background: linear-gradient(180deg, #f8fbfe 0%, #eef4fa 100%);
                box-shadow: 0 2px 8px rgba(37, 68, 99, 0.06);
                position: relative;
            }
            .filtros-section > legend.Titulos2,
            .filtros-section > .Titulos2 {
                width: auto;
                border: none;
                margin-bottom: 12px;
                padding: 4px 12px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #254463;
                background: #ffffff;
                border-radius: 20px;
                box-shadow: 0 1px 3px rgba(37, 68, 99, 0.12);
            }
            .filtros-section .control-label,
            .filtros-section .label-xs {
                color: #3d5a73;
                font-weight: 600;
                font-size: 11px;
            }
            .filtros-section .form-control {
                border-color: #c5d4e3;
                border-radius: 6px;
                box-shadow: none;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .filtros-section .form-control:focus {
                border-color: #3b82a8;
                box-shadow: 0 0 0 2px rgba(59, 130, 168, 0.18);
            }
            .filtros-section .input-group-addon.alert-info {
                background: #254463;
                color: #fff;
                border-color: #254463;
                font-size: 11px;
                font-weight: 600;
            }
            .filtros-section .btn-success {
                background: linear-gradient(180deg, #2f9e5f 0%, #238b4f 100%);
                border-color: #1f7a45;
                font-weight: 600;
                border-radius: 6px;
                box-shadow: 0 1px 3px rgba(35, 139, 79, 0.3);
            }
            .filtros-section .btn-danger {
                background: linear-gradient(180deg, #e35d5d 0%, #c94444 100%);
                border-color: #b53a3a;
                font-weight: 600;
                border-radius: 6px;
            }
            .filtros-section .btn-info {
                background: linear-gradient(180deg, #3b82a8 0%, #2f6f91 100%);
                border-color: #2a6280;
                border-radius: 6px;
            }
            .btn-modern { padding: 8px 20px; border-radius: 6px; border: none; font-weight: 500; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; }
            .btn-modern:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
            .btn-success-modern { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; }
            .btn-primary-modern { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; }
            .btn-default-modern { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: #ffffff; }
            #detalle_container { margin-top: 20px; display: none; }
            .well { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
            @media (max-width: 768px) {
                .estado-cuenta-card { padding: 15px; }
                .estado-cuenta-header { padding: 12px 15px; margin: -15px -15px 15px -15px; }
                .sv-mode-bar { width: auto; }
                .sv-filter-head { flex-direction: column; align-items: flex-start; }
            }
            .modal-header { background: linear-gradient(135deg, #254463 0%, #1d354d 100%); color: white; }
            .nav-tabs { border-bottom: 2px solid #5c9ccc; }
            .nav-tabs > li { margin-bottom: -2px; }
            .nav-tabs > li > a { background: linear-gradient(to bottom, #f0f0f0 0%, #d0d0d0 100%); color: #333; border: 1px solid #aaa; border-bottom-color: #5c9ccc; border-radius: 6px 6px 0 0; font-weight: bold; margin-right: 2px; padding: 8px 15px; }
            .nav-tabs > li > a:hover { background: linear-gradient(to bottom, #e0e0e0 0%, #c0c0c0 100%); border-color: #999; border-bottom-color: #5c9ccc; }
            .nav-tabs > li.active > a,
            .nav-tabs > li.active > a:hover,
            .nav-tabs > li.active > a:focus { background: #fff; color: #d35400; border: 1px solid #5c9ccc; border-bottom-color: transparent; cursor: default; }
            .nav-tabs > li > a > i { margin-right: 5px; }
            #tabComparacion .chosen-container { font-size: 13px; max-width: 100%; }
            #tabComparacion .chosen-container .chosen-search input { font-size: 13px !important; padding: 4px 8px !important; }
            /* Saldos Virtual — filtros compactos */
            .sv-filter-panel {
                margin-bottom: 10px;
                border: 1px solid #c9dae8;
                border-left: 4px solid #254463;
                border-radius: 6px;
                background: #f7fafc;
                box-shadow: none;
                overflow: hidden;
            }
            .sv-filter-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                flex-wrap: wrap;
                padding: 6px 10px;
                background: linear-gradient(180deg, #eef4fa 0%, #e4edf5 100%);
                border-bottom: 1px solid #d5e1ec;
            }
            .sv-filter-title {
                margin: 0;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #254463;
            }
            .sv-mode-bar {
                display: inline-flex;
                gap: 0;
                align-items: stretch;
                margin: 0;
                padding: 2px;
                background: #d9e4ef;
                border: 1px solid #b7c9da;
                border-radius: 4px;
                box-shadow: none;
            }
            .sv-mode-opt {
                margin: 0;
                padding: 3px 10px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-transform: uppercase;
                color: #4a657a;
                cursor: pointer;
                border-radius: 3px;
                line-height: 1.3;
                transition: all 0.15s ease;
                user-select: none;
            }
            .sv-mode-opt input { display: none; }
            .sv-mode-opt.is-active,
            .sv-mode-opt:has(input:checked) {
                background: #254463;
                color: #ffffff;
                box-shadow: none;
            }
            .sv-filter-body {
                padding: 8px 10px 6px;
            }
            .sv-filtros {
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                overflow: visible;
            }
            .sv-filtros::before { display: none !important; }
            .sv-filtros > legend { display: none !important; }
            .sv-filtros .form-group { margin-bottom: 6px; }
            .sv-filtros .control-label,
            .sv-filtros .label-xs {
                padding-top: 4px;
                margin-bottom: 0;
                font-size: 10px;
                color: #3d5a73;
            }
            .sv-filtros .form-control {
                height: 26px;
                padding: 2px 6px;
                font-size: 11px;
            }
            .sv-filtros .datepicker-virt:disabled,
            .sv-filtros .datepicker-virtg:disabled {
                background-color: #eeeeee !important;
                color: #555555 !important;
                border-color: #cccccc !important;
                opacity: 1;
                cursor: not-allowed;
            }
            .sv-filtros .input-group {
                display: flex;
                align-items: stretch;
                width: 100%;
            }
            .sv-filtros .input-group .form-control {
                float: none;
                flex: 1 1 auto;
                width: 1%;
                height: 26px;
                line-height: 22px;
                margin: 0;
                box-sizing: border-box;
            }
            .sv-filtros .input-group-btn {
                display: flex;
                align-items: stretch;
                width: auto;
                white-space: nowrap;
                float: none;
            }
            .sv-filtros .input-group-btn > .btn {
                height: 26px;
                width: 28px;
                padding: 0;
                margin: 0;
                line-height: 24px;
                font-size: 11px;
                border-radius: 0;
                box-sizing: border-box;
            }
            .sv-filtros .input-group-btn > .btn:first-child {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
            }
            .sv-filtros .input-group-btn > .btn:last-child {
                border-top-right-radius: 4px;
                border-bottom-right-radius: 4px;
            }
            .sv-filtros #Pla_Nom_Virt {
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }
            .sv-filtros .input-group-addon {
                display: flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                width: auto;
                height: 26px;
                padding: 0 8px;
                font-size: 10px;
                line-height: 1;
                box-sizing: border-box;
                white-space: nowrap;
            }
            .sv-filtros .sv-actions {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 4px;
                padding-top: 0;
                height: 26px;
            }
            .sv-filtros .sv-btn-search {
                min-width: 72px;
                width: auto;
                height: 26px;
                padding: 0 8px;
                font-size: 11px;
                line-height: 24px;
            }
            .sv-filtros .sv-btn-search:disabled,
            .sv-filtros .sv-btn-search.disabled {
                opacity: 0.75;
                cursor: wait;
                pointer-events: none;
            }
            .sv-filtros .sv-group-day {
                display: flex;
                align-items: center;
                justify-content: flex-start;
                height: 26px;
                margin: 0;
                padding: 0 2px;
                color: #3d5a73;
                font-size: 10px;
                font-weight: 600;
                white-space: nowrap;
                cursor: pointer;
            }
            .sv-filtros .sv-group-day input {
                margin: 0 4px 0 0;
            }
            .sv-filtros .sv-tipo-multi {
                display: flex;
                flex-wrap: nowrap;
                align-items: stretch;
                width: 100%;
                height: 26px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background: #fff;
                overflow: hidden;
                box-sizing: border-box;
            }
            .sv-filtros .sv-tipo-multi .sv-tipo-chip {
                flex: 1 1 0;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 0 4px;
                font-size: 10px;
                font-weight: 600;
                color: #5a6a7a;
                cursor: pointer;
                border-right: 1px solid #e1e5ea;
                background: #f8fafc;
                white-space: nowrap;
                user-select: none;
                line-height: 1;
            }
            .sv-filtros .sv-tipo-multi .sv-tipo-chip:last-child {
                border-right: none;
            }
            .sv-filtros .sv-tipo-multi .sv-tipo-chip input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }
            .sv-filtros .sv-tipo-multi .sv-tipo-chip.is-on {
                background: #254463;
                color: #fff;
            }
            .sv-filtros .sv-tipo-multi .sv-tipo-chip:hover {
                filter: brightness(0.97);
            }
            .sv-grid-wrap { margin-top: 6px; }
            #gbox_gridSaldosVirtual .ui-jqgrid-htable th,
            #gbox_gridSaldosVirtualGrupal .ui-jqgrid-htable th {
                padding: 5px 2px !important;
                border-color: #8ea8bf !important;
                background: linear-gradient(#dceaf5, #bfd5e7) !important;
                color: #183b59;
                font-size: 11px;
                font-weight: 700;
            }
            #gbox_gridSaldosVirtual .ui-jqgrid-btable td,
            #gbox_gridSaldosVirtualGrupal .ui-jqgrid-btable td {
                padding: 5px 6px !important;
                border-color: #d8e0e7 !important;
                font-size: 11px;
                transition: background-color .12s ease;
            }
            #gridSaldosVirtual tr.sv-row-initial > td:first-child { border-left: 4px solid #526d82 !important; }
            #gridSaldosVirtual tr.sv-row-manifest > td:first-child { border-left: 4px solid #a95757 !important; }
            #gridSaldosVirtual tr.sv-row-transfer > td:first-child { border-left: 4px solid #2f8f5b !important; }
            #gridSaldosVirtual tr.sv-row-retention > td:first-child { border-left: 4px solid #28afdf !important; }
            .sv-concept {
                display: inline-flex;
                align-items: center;
                min-height: 20px;
                padding: 2px 8px;
                border: 1px solid transparent;
                border-radius: 10px;
                font-size: 10px;
                font-weight: 700;
                line-height: 14px;
                white-space: nowrap;
            }
            .sv-concept-initial { color: #40586b; background: #dfe8ee; border-color: #cad7e0; }
            .sv-concept-manifest { color: #763c3c; background: #efdada; border-color: #d9b2b2; }
            .sv-concept-transfer { color: #17633a; background: #d9f0e2; border-color: #addabe; }
            .sv-concept-retention { color: #17647f; background: #afe3f7; border-color: #28afdf; }
            .sv-concept-default { color: #3d5264; background: #edf1f4; border-color: #d7dfe5; }
            .sv-resumen {
                display: flex;
                flex-wrap: wrap;
                align-items: stretch;
                gap: 6px;
                margin: 8px 0 4px;
                padding: 6px 8px;
                background: #f4f7fa;
                border: 1px solid #d5dee7;
                border-radius: 4px;
            }
            .sv-resumen-item {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 28px;
                padding: 3px 10px;
                border: 1px solid #cfd9e3;
                border-radius: 4px;
                background: #fff;
                line-height: 1.1;
            }
            .sv-resumen-item .sv-resumen-label {
                color: #4a6073;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-transform: uppercase;
            }
            .sv-resumen-item .sv-resumen-val {
                color: #183b59;
                font-size: 15px;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                min-width: 1.4em;
                text-align: right;
            }
            .sv-resumen-item.is-transfer { border-left: 3px solid #2f8f5b; }
            .sv-resumen-item.is-transfer .sv-resumen-val { color: #17633a; }
            .sv-resumen-item.is-retention { border-left: 3px solid #28afdf; }
            .sv-resumen-item.is-retention .sv-resumen-val { color: #17647f; }
            .sv-resumen-item.is-manifest { border-left: 3px solid #a95757; }
            .sv-resumen-item.is-manifest .sv-resumen-val { color: #763c3c; }
            .sv-resumen-item.is-total {
                margin-left: auto;
                background: #e8f0f6;
                border-color: #8ea8bf;
            }
            .sv-resumen-item.is-total .sv-resumen-val { color: #183b59; }
            #gbox_gridSaldosVirtual .ui-jqgrid-sdiv td {
                background: #e8f0f6 !important;
                color: #183b59;
                font-weight: 700;
            }
        </style>
    </HEAD>

    <BODY>
        <div class="panel panel-main">
            <div class="panel-heading exa-header">
                <h3 class="panel-title">&raquo; Estado de Cuenta</h3>
            </div>
            <div class="panel-body ui-widget-content ui-corner-bottom exa-body">
                <!-- TABS -->
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px;">
                    <li role="presentation" class="active"><a href="#tabPlantas" aria-controls="tabPlantas" role="tab" data-toggle="tab">Individual</a></li>
                    <li role="presentation"><a href="#tabConsolidado" aria-controls="tabConsolidado" role="tab" data-toggle="tab">Consolidado</a></li>
                    <?php if (!$es_perfil_plantas) { ?>
                        <li role="presentation"><a href="#tabPlantero" aria-controls="tabPlantero" role="tab" data-toggle="tab">Grupal</a></li>
                    <?php } ?>
                    <?php if ($puede_comparar_saldos) { ?>
                        <li role="presentation"><a href="#tabComparacion" aria-controls="tabComparacion" role="tab" data-toggle="tab">Saldos Auditados</a></li>
                    <?php } ?>
                    <li role="presentation"><a href="#tabVirtual" aria-controls="tabVirtual" role="tab" data-toggle="tab">Saldos Virtual</a></li>
                </ul>

                <div class="tab-content">
                    <!-- TAB 1: Individual -->
                    <div role="tabpanel" class="tab-pane active" id="tabPlantas">
                        <!-- AMBIENTE PRINCIPAL -->
                        <div id="documentoSearch">
                    <div class="row">
                        <form name="searchEstadoCuenta" id="searchEstadoCuenta" class="form-horizontal normal">
                            <div class="col-sm-12">
                                <fieldset class="exa-fieldset filtros-section">
                                    <legend class="Titulos2">Filtros de Búsqueda</legend>
                                    
                                    <div class="row">
                                        <!-- Columna Izquierda: Filtro Planta y Cliente -->
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Planta:</label>
                                                <div class="col-xs-12 col-sm-10">
                                                    <div class="input-group" style="width: 100%;">
                                                        <input type="hidden" id="Pla_Cod" name="Pla_Cod" />
                                                        <input type="hidden" id="Cli_Cod" name="Cli_Cod" />
                                                        <input type="hidden" id="Ses_Emp_Nom" value="<?php echo isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'RELA VERA S.A.'; ?>" />
                                                        <input type="text" id="Pla_Nom" name="Pla_Nom" class="form-control input-xs" placeholder="Seleccione una planta..." readonly style="height: auto" />
                                                        <span class="input-group-btn">
                                                            <button type="button" id="btnBuscarPlanta" class="btn btn-info btn-xs" title="Buscar Planta">
                                                                <span class="glyphicon glyphicon-search"></span>
                                                            </button>
                                                            <button type="button" id="btnLimpiarPlanta" class="btn btn-danger btn-xs" title="Limpiar Planta">
                                                                <span class="glyphicon glyphicon-remove"></span>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Cliente:</label>
                                                <div class="col-xs-12 col-sm-10">
                                                    <input type="text" id="Cli_Nom" name="Cli_Nom" class="form-control input-xs" readonly style="height: auto" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Columna Derecha: Período y Fechas -->
                                        <div class="col-sm-6">
                                            <!-- Fila 1: Período y Mes -->
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-3 control-label label-xs">Período:</label>
                                                <div class="col-xs-12 col-sm-3">
                                                    <select name="Pec_Cod" id="Pec_Cod" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodo()">
                                                        <option value="T"><< TODOS >></option>
                                                        <option value="PF"><< Por Fechas >></option>
                                                        <?php
                                                        $currentYear = date("Y");
                                                        foreach ($periodos as $p) {
                                                            $year = substr($p['Pec_Fei'], 0, 4);
                                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                                            echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selected>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <label class="col-xs-12 col-sm-1 control-label label-xs">Mes:</label>
                                                <div class="col-xs-12 col-sm-3">
                                                    <select name="Mes_Cod" id="Mes_Cod" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodo()">
                                                        <option value="00"><< TODOS >></option>
                                                        <option value="01" <?php if($mes == '01') echo 'selected'; ?>>Enero</option>
                                                        <option value="02" <?php if($mes == '02') echo 'selected'; ?>>Febrero</option>
                                                        <option value="03" <?php if($mes == '03') echo 'selected'; ?>>Marzo</option>
                                                        <option value="04" <?php if($mes == '04') echo 'selected'; ?>>Abril</option>
                                                        <option value="05" <?php if($mes == '05') echo 'selected'; ?>>Mayo</option>
                                                        <option value="06" <?php if($mes == '06') echo 'selected'; ?>>Junio</option>
                                                        <option value="07" <?php if($mes == '07') echo 'selected'; ?>>Julio</option>
                                                        <option value="08" <?php if($mes == '08') echo 'selected'; ?>>Agosto</option>
                                                        <option value="09" <?php if($mes == '09') echo 'selected'; ?>>Septiembre</option>
                                                        <option value="10" <?php if($mes == '10') echo 'selected'; ?>>Octubre</option>
                                                        <option value="11" <?php if($mes == '11') echo 'selected'; ?>>Noviembre</option>
                                                        <option value="12" <?php if($mes == '12') echo 'selected'; ?>>Diciembre</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Fila 2: Fechas -->
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-3 control-label label-xs">Fecha:</label>
                                                <div class="col-xs-12 col-sm-9">
                                                    <div class="input-group input-group-xs" style="width: 100%;">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_IniM" name="Fec_IniM" class="form-control datepicker" style="text-align: center;" disabled />
                                                        <span class="input-group-addon" style="cursor: pointer;">
                                                            <i class="glyphicon glyphicon-transfer"></i>
                                                        </span>
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_FinM" name="Fec_FinM" class="form-control datepicker" style="text-align: center;" disabled />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fila 3: Botones -->
                                            <div class="form-group">
                                                <div class="col-xs-12 text-right">
                                                    <button type="button" id="btnBuscar" class="btn btn-success btn-xs" onclick="buscarEstadoCuenta()">
                                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                                    </button>
                                                    <button type="button" class="btn btn-default btn-xs btn-danger" onclick="limpiarFiltros()">
                                                        <span class="glyphicon glyphicon-trash"></span> Limpiar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            
                            <!-- Grid Principal de Estado de Cuenta (jqGrid) - OCULTO -->
                            <div class="col-sm-12" style="padding-bottom: 10px; display: none;">
                                <table id="gridEstadoCuenta"></table>
                                <div id="pagerEstadoCuenta"></div>
                            </div>

                            <!-- Contenedor de Detalle -->
                            <div class="col-sm-12">
                                <div id="detalle_container"></div>
                            </div>
                        </form>
                    </div>
                </div>
                </div>

                <!-- TAB: Consolidado (reporte tipo Manifiesto de Anticipos + línea manifiestos pendientes) -->
                <div role="tabpanel" class="tab-pane" id="tabConsolidado">
                    <div id="documentoSearchConsolidado">
                        <div class="row">
                            <form name="searchEstadoCuentaConsolidado" id="searchEstadoCuentaConsolidado" class="form-horizontal normal">
                                <div class="col-sm-12">
                                    <fieldset class="exa-fieldset filtros-section">
                                        <legend class="Titulos2">Filtros de Búsqueda</legend>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-xs-12 col-sm-2 control-label">Planta:</label>
                                                    <div class="col-xs-12 col-sm-10">
                                                        <div class="input-group" style="width: 100%;">
                                                            <input type="hidden" id="Pla_Cod_Cons" name="Pla_Cod_Cons" />
                                                            <input type="hidden" id="Cli_Cod_Cons" name="Cli_Cod_Cons" />
                                                            <input type="text" id="Pla_Nom_Cons" name="Pla_Nom_Cons" class="form-control input-xs" placeholder="Seleccione una planta..." readonly style="height: auto" />
                                                            <span class="input-group-btn">
                                                                <button type="button" id="btnBuscarPlantaCons" class="btn btn-info btn-xs" title="Buscar Planta">
                                                                    <span class="glyphicon glyphicon-search"></span>
                                                                </button>
                                                                <button type="button" id="btnLimpiarPlantaCons" class="btn btn-danger btn-xs" title="Limpiar Planta">
                                                                    <span class="glyphicon glyphicon-remove"></span>
                                                                </button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-12 col-sm-2 control-label">Cliente:</label>
                                                    <div class="col-xs-12 col-sm-10">
                                                        <input type="text" id="Cli_Nom_Cons" name="Cli_Nom_Cons" class="form-control input-xs" readonly style="height: auto" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-xs-12 col-sm-3 control-label label-xs">Período:</label>
                                                    <div class="col-xs-12 col-sm-3">
                                                        <select name="Pec_Cod_Cons" id="Pec_Cod_Cons" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodoCons()">
                                                            <option value="T"><< TODOS >></option>
                                                            <option value="PF"><< Por Fechas >></option>
                                                            <?php
                                                            $currentYearCons = date("Y");
                                                            foreach ($periodos as $p) {
                                                                $year = substr($p['Pec_Fei'], 0, 4);
                                                                $selectedCons = ($year == $currentYearCons) ? 'selected' : '';
                                                                echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selectedCons>$year</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <label class="col-xs-12 col-sm-1 control-label label-xs">Mes:</label>
                                                    <div class="col-xs-12 col-sm-3">
                                                        <select name="Mes_Cod_Cons" id="Mes_Cod_Cons" class="form-control input-xs" style="height: auto; width: 100%; text-align: center;" onchange="cambiarPeriodoCons()">
                                                            <option value="00" selected><< TODOS >></option>
                                                            <option value="01">Enero</option>
                                                            <option value="02">Febrero</option>
                                                            <option value="03">Marzo</option>
                                                            <option value="04">Abril</option>
                                                            <option value="05">Mayo</option>
                                                            <option value="06">Junio</option>
                                                            <option value="07">Julio</option>
                                                            <option value="08">Agosto</option>
                                                            <option value="09">Septiembre</option>
                                                            <option value="10">Octubre</option>
                                                            <option value="11">Noviembre</option>
                                                            <option value="12">Diciembre</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-xs-12 col-sm-3 control-label label-xs">Fecha:</label>
                                                    <div class="col-xs-12 col-sm-9">
                                                        <div class="input-group input-group-xs" style="width: 100%;">
                                                            <span class="input-group-addon alert-info">Desde</span>
                                                            <input type="text" id="Fec_IniM_Cons" name="Fec_IniM_Cons" class="form-control datepicker-cons" style="text-align: center;" disabled />
                                                            <span class="input-group-addon" title="Intercambiar fechas" style="cursor: pointer;" onclick="intercambiarFechasCons()">
                                                                <i class="glyphicon glyphicon-transfer"></i>
                                                            </span>
                                                            <span class="input-group-addon alert-info">Hasta</span>
                                                            <input type="text" id="Fec_FinM_Cons" name="Fec_FinM_Cons" class="form-control datepicker-cons" style="text-align: center;" disabled />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-xs-12 text-right">
                                                        <button type="button" id="btnBuscarConsolidado" class="btn btn-success btn-xs" onclick="buscarEstadoCuentaConsolidado()">
                                                            <span class="glyphicon glyphicon-search"></span> Buscar
                                                        </button>
                                                        <button type="button" class="btn btn-default btn-xs btn-danger" onclick="limpiarFiltrosConsolidado()">
                                                            <span class="glyphicon glyphicon-trash"></span> Limpiar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-sm-12" style="padding-top: 5px;">
                                    <div id="detalle_consolidado_container"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- TAB 2: Grupal -->
                <?php if (!$es_perfil_plantas) { ?>
                <div role="tabpanel" class="tab-pane" id="tabPlantero">
                    <!-- AMBIENTE GRUPAL -->
                        <div id="documentoSearchGrupal">
                            <div class="row">
                                <form name="searchEstadoCuentaGrupal" id="searchEstadoCuentaGrupal" class="form-horizontal normal">
                                    <div class="col-sm-10 col-sm-offset-1">
                                        <fieldset class="exa-fieldset filtros-section">
                                            <legend class="Titulos2">Filtros de Búsqueda</legend>

                                            <!-- Fila 1: Todos los filtros en una línea -->
                                            <div class="row" style="font-size: 14px;">
                                                <!-- Período -->
                                                <div class="col-xs-12 col-sm-3" style="padding-right: 8px; width: 200px;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Período:</label>
                                                    <select name="Pec_Cod_Grupal" id="Pec_Cod_Grupal" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoGrupal()">
                                                        <option value="T">
                                                            << TODOS>>
                                                        </option>
                                                        <option value="PF">
                                                            << Por Fechas>>
                                                        </option>
                                                        <?php
                                                        $currentYear = date("Y");
                                                        foreach ($periodos as $p) {
                                                            $year = substr($p['Pec_Fei'], 0, 4);
                                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                                            echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selected>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Mes -->
                                                <div class="col-xs-12 col-sm-2" style="padding-left: 8px; padding-right: 8px; width: 300px;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Mes:</label>
                                                    <select name="Mes_Cod_Grupal" id="Mes_Cod_Grupal" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoGrupal()">
                                                        <option value="00">
                                                            << TODOS>>
                                                        </option>
                                                        <option value="01" <?php if ($mes == '01') echo 'selected'; ?>>Enero</option>
                                                        <option value="02" <?php if ($mes == '02') echo 'selected'; ?>>Febrero</option>
                                                        <option value="03" <?php if ($mes == '03') echo 'selected'; ?>>Marzo</option>
                                                        <option value="04" <?php if ($mes == '04') echo 'selected'; ?>>Abril</option>
                                                        <option value="05" <?php if ($mes == '05') echo 'selected'; ?>>Mayo</option>
                                                        <option value="06" <?php if ($mes == '06') echo 'selected'; ?>>Junio</option>
                                                        <option value="07" <?php if ($mes == '07') echo 'selected'; ?>>Julio</option>
                                                        <option value="08" <?php if ($mes == '08') echo 'selected'; ?>>Agosto</option>
                                                        <option value="09" <?php if ($mes == '09') echo 'selected'; ?>>Septiembre</option>
                                                        <option value="10" <?php if ($mes == '10') echo 'selected'; ?>>Octubre</option>
                                                        <option value="11" <?php if ($mes == '11') echo 'selected'; ?>>Noviembre</option>
                                                        <option value="12" <?php if ($mes == '12') echo 'selected'; ?>>Diciembre</option>
                                                    </select>
                                                </div>

                                                <!-- Espacio en blanco -->
                                                <div class="col-xs-12 col-sm-1"></div>

                                                <!-- Fechas -->
                                                <div class="col-xs-12 col-sm-6" style="padding-left: 8px; white-space: nowrap;">
                                                    <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; font-size: 14px;">Fecha:</label>
                                                    <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; border-radius: 4px 0 0 4px; margin-bottom: 0; width: 65px;">Desde</span>
                                                    <input type="text" id="Fec_IniM_Grupal" name="Fec_IniM_Grupal" class="form-control datepicker" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0;" disabled />
                                                    <span class="input-group-addon" style="display: inline-block; cursor: pointer; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: auto;">
                                                        <i class="glyphicon glyphicon-transfer"></i>
                                                    </span>
                                                    <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: 65px;">Hasta</span>
                                                    <input type="text" id="Fec_FinM_Grupal" name="Fec_FinM_Grupal" class="form-control datepicker" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0 4px 4px 0; margin-bottom: 0;" disabled />
                                                </div>
                                            </div>

                                            <!-- Fila 2: Botones de acción -->
                                            <div class="row" style="margin-top: 15px;">
                                                <div class="col-xs-12 col-sm-offset-6 col-sm-6 text-right">
                                                    <button type="button" id="btnBuscarGrupal" class="btn btn-success btn-sm" onclick="buscarEstadoCuentaGrupal()">
                                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                                    </button>
                                                    <button type="button" id="btnExportarExcelGrupal" class="btn btn-primary btn-sm" onclick="exportarExcelGrupal()">
                                                        <span class="glyphicon glyphicon-download-alt"></span> Excel
                                                    </button>
                                                    <button type="button" id="btnExportarPDFGrupal" class="btn btn-danger btn-sm" onclick="exportarPDFGrupal()">
                                                        <span class="glyphicon glyphicon-file"></span> PDF
                                                    </button>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <!-- Contenedor de Resultados Grupal -->
                                    <div class="col-sm-12" style="margin-top: 20px;">
                                        <div id="detalle_grupal_container"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($puede_comparar_saldos) { ?>
                <!-- TAB 3: Comparación (solo administradores) -->
                <div role="tabpanel" class="tab-pane" id="tabComparacion">
                    <div id="documentoSearchComparacion">
                        <div class="row">
                            <form name="searchComparacion" id="searchComparacion" class="form-horizontal normal">
                                <div class="col-sm-10 col-sm-offset-1">
                                    <fieldset class="exa-fieldset filtros-section">
                                        <legend class="Titulos2">Comparación de saldos</legend>
                                        <p class="text-muted" style="font-size: 12px; margin-bottom: 12px;">
                                            <strong>Saldo estado de cuenta:</strong> mismo criterio que el reporte <em>Grupal</em> para el período elegido y la planta seleccionada.
                                            <strong>Saldo manifiesto:</strong> Anticipos (A) menos Sin facturar (B), igual que en Gestión de Manifiesto (sin filtro de fechas).
                                        </p>
                                        <div class="checkbox" style="margin-bottom: 10px;">
                                            <label>
                                                <input type="checkbox" id="comp_todas_plantas" name="comp_todas_plantas" value="1" />
                                                <strong>Todas las plantas</strong> — tabla con una fila por planta (misma comparación que abajo, en bloque).
                                            </label>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 12px;">
                                            <label class="col-xs-12 col-sm-2 control-label label-xs">Planta:</label>
                                            <div class="col-xs-12 col-sm-10">
                                                <select name="Pla_Cod_Comp" id="Pla_Cod_Comp" class="form-control input-sm pla-cod-comp-chosen" title="Escriba para filtrar la lista">
                                                    <option value="">— Escriba para buscar planta —</option>
                                                    <?php
                                                    foreach ($plantas_comparacion as $pc) {
                                                        if (empty($pc['Pla_Cod'])) {
                                                            continue;
                                                        }
                                                        $pn = isset($pc['Pla_Nom']) ? htmlspecialchars($pc['Pla_Nom'], ENT_QUOTES, 'UTF-8') : '';
                                                        $pv = htmlspecialchars($pc['Pla_Cod'], ENT_QUOTES, 'UTF-8');
                                                        echo "<option value=\"$pv\">$pn</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row" style="font-size: 14px;">
                                            <div class="col-xs-12 col-sm-3" style="padding-right: 8px; width: 200px;">
                                                <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Período:</label>
                                                <select name="Pec_Cod_Comp" id="Pec_Cod_Comp" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoComp()">
                                                    <option value="T">&lt;&lt; TODOS &gt;&gt;</option>
                                                    <option value="PF">&lt;&lt; Por Fechas &gt;&gt;</option>
                                                    <?php
                                                    $currentYearC = date("Y");
                                                    foreach ($periodos as $p) {
                                                        $year = substr($p['Pec_Fei'], 0, 4);
                                                        $selected = ($year == $currentYearC) ? 'selected' : '';
                                                        echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selected>$year</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-2" style="padding-left: 8px; padding-right: 8px; width: 300px;">
                                                <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; vertical-align: middle; font-size: 14px;">Mes:</label>
                                                <select name="Mes_Cod_Comp" id="Mes_Cod_Comp" class="form-control input-xs" style="display: inline-block; height: auto; width: 100px; text-align: center; vertical-align: middle; font-size: 14px;" onchange="cambiarPeriodoComp()">
                                                    <option value="00">&lt;&lt; TODOS &gt;&gt;</option>
                                                    <option value="01" <?php if ($mes == '01') echo 'selected'; ?>>Enero</option>
                                                    <option value="02" <?php if ($mes == '02') echo 'selected'; ?>>Febrero</option>
                                                    <option value="03" <?php if ($mes == '03') echo 'selected'; ?>>Marzo</option>
                                                    <option value="04" <?php if ($mes == '04') echo 'selected'; ?>>Abril</option>
                                                    <option value="05" <?php if ($mes == '05') echo 'selected'; ?>>Mayo</option>
                                                    <option value="06" <?php if ($mes == '06') echo 'selected'; ?>>Junio</option>
                                                    <option value="07" <?php if ($mes == '07') echo 'selected'; ?>>Julio</option>
                                                    <option value="08" <?php if ($mes == '08') echo 'selected'; ?>>Agosto</option>
                                                    <option value="09" <?php if ($mes == '09') echo 'selected'; ?>>Septiembre</option>
                                                    <option value="10" <?php if ($mes == '10') echo 'selected'; ?>>Octubre</option>
                                                    <option value="11" <?php if ($mes == '11') echo 'selected'; ?>>Noviembre</option>
                                                    <option value="12" <?php if ($mes == '12') echo 'selected'; ?>>Diciembre</option>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-1"></div>
                                            <div class="col-xs-12 col-sm-6" style="padding-left: 8px; white-space: nowrap;">
                                                <label class="control-label label-xs" style="display: inline-block; width: auto; margin-right: 5px; font-size: 14px;">Fecha:</label>
                                                <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; border-radius: 4px 0 0 4px; margin-bottom: 0; width: 65px;">Desde</span>
                                                <input type="text" id="Fec_IniM_Comp" name="Fec_IniM_Comp" class="form-control datepicker-comp" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0;" disabled />
                                                <span class="input-group-addon" title="Intercambiar fechas" style="display: inline-block; cursor: pointer; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: auto;" onclick="intercambiarFechasComp()">
                                                    <i class="glyphicon glyphicon-transfer"></i>
                                                </span>
                                                <span class="input-group-addon alert-info" style="display: inline-block; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0; margin-bottom: 0; width: 65px;">Hasta</span>
                                                <input type="text" id="Fec_FinM_Comp" name="Fec_FinM_Comp" class="form-control datepicker-comp" style="display: inline-block; text-align: center; width: 120px; font-size: 14px; vertical-align: middle; margin-left: -1px; border-radius: 0 4px 4px 0; margin-bottom: 0;" disabled />
                                            </div>
                                        </div>
                                        <div class="row" style="margin-top: 15px;">
                                            <div class="col-xs-12 text-right">
                                                <button type="button" class="btn btn-success btn-sm" onclick="buscarComparacionSaldos()">
                                                    <span class="glyphicon glyphicon-search"></span> Comparar
                                                </button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                                <div class="col-sm-12" style="margin-top: 10px;">
                                    <div id="detalle_comparacion_container"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <!-- TAB: Saldos Virtual -->
                <div role="tabpanel" class="tab-pane" id="tabVirtual">
                    <div class="sv-filter-panel">
                        <div class="sv-filter-head">
                            <h5 class="sv-filter-title" id="svFilterTitle">Filtros individual</h5>
                            <div class="sv-mode-bar">
                                <label class="sv-mode-opt is-active">
                                    <input type="radio" name="sv_modo" value="individual" checked onchange="cambiarModoSaldosVirtual()" /> Individual
                                </label>
                                <?php if (!$es_perfil_plantas) { ?>
                                <label class="sv-mode-opt">
                                    <input type="radio" name="sv_modo" value="grupal" onchange="cambiarModoSaldosVirtual()" /> Grupal
                                </label>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="sv-filter-body">
                            <!-- Individual -->
                            <div id="svPanelIndividual">
                                <form name="searchSaldosVirtual" id="searchSaldosVirtual" class="form-horizontal normal sv-filtros" onsubmit="return false;">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Planta:</label>
                                                <div class="col-xs-12 col-sm-10">
                                                    <div class="input-group input-group-xs" style="width:100%;">
                                                        <input type="hidden" id="Pla_Cod_Virt" name="Pla_Cod_Virt" />
                                                        <input type="hidden" id="Cli_Cod_Virt" name="Cli_Cod_Virt" />
                                                        <input type="text" id="Pla_Nom_Virt" name="Pla_Nom_Virt" class="form-control input-xs" placeholder="Seleccione una planta..." readonly />
                                                        <span class="input-group-btn" id="svPlantaBtns">
                                                            <button type="button" id="btnBuscarPlantaVirt" class="btn btn-info btn-xs" title="Buscar Planta"><span class="glyphicon glyphicon-search"></span></button>
                                                            <button type="button" id="btnLimpiarPlantaVirt" class="btn btn-danger btn-xs" title="Limpiar"><span class="glyphicon glyphicon-remove"></span></button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label">Cliente:</label>
                                                <div class="col-xs-12 col-sm-7">
                                                    <input type="text" id="Cli_Nom_Virt" name="Cli_Nom_Virt" class="form-control input-xs" readonly />
                                                </div>
                                                <div class="col-xs-12 col-sm-3 sv-actions">
                                                    <button type="button" class="btn btn-success btn-xs sv-btn-search" onclick="buscarSaldosVirtual()" title="Buscar"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label label-xs">Período:</label>
                                                <div class="col-xs-4 col-sm-2">
                                                    <select name="Pec_Cod_Virt" id="Pec_Cod_Virt" class="form-control input-xs" style="text-align:center;" onchange="cambiarPeriodoVirt()">
                                                        <?php
                                                        $currentYearV = date("Y");
                                                        foreach ($periodos as $p) {
                                                            $year = substr($p['Pec_Fei'], 0, 4);
                                                            $selectedV = ($year == $currentYearV) ? 'selected' : '';
                                                            echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selectedV>$year</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <label class="col-xs-12 col-sm-1 control-label label-xs">Mes:</label>
                                                <div class="col-xs-8 col-sm-3">
                                                    <select name="Mes_Cod_Virt" id="Mes_Cod_Virt" class="form-control input-xs" style="text-align:center;" onchange="cambiarPeriodoVirt()">
                                                        <option value="00" selected>&lt;&lt; TODOS &gt;&gt;</option>
                                                        <option value="PF">&lt;&lt; Por Fechas &gt;&gt;</option>
                                                        <option value="01">Enero</option>
                                                        <option value="02">Febrero</option>
                                                        <option value="03">Marzo</option>
                                                        <option value="04">Abril</option>
                                                        <option value="05">Mayo</option>
                                                        <option value="06">Junio</option>
                                                        <option value="07">Julio</option>
                                                        <option value="08">Agosto</option>
                                                        <option value="09">Septiembre</option>
                                                        <option value="10">Octubre</option>
                                                        <option value="11">Noviembre</option>
                                                        <option value="12">Diciembre</option>
                                                    </select>
                                                </div>
                                                <label class="col-xs-12 col-sm-1 control-label label-xs">Mostrar:</label>
                                                <div class="col-xs-12 col-sm-3">
                                                    <div class="sv-tipo-multi" id="Tip_Mov_Virt" title="Seleccione uno o más tipos">
                                                        <label class="sv-tipo-chip is-on"><input type="checkbox" value="MAN" checked /> Manif.</label>
                                                        <label class="sv-tipo-chip is-on"><input type="checkbox" value="TRF" checked /> Transf.</label>
                                                        <label class="sv-tipo-chip is-on"><input type="checkbox" value="RET" checked /> Reten.</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-xs-12 col-sm-2 control-label label-xs">Fecha:</label>
                                                <div class="col-xs-12 col-sm-7">
                                                    <div class="input-group input-group-xs" style="width:100%;">
                                                        <span class="input-group-addon alert-info">Desde</span>
                                                        <input type="text" id="Fec_IniM_Virt" name="Fec_IniM_Virt" class="form-control datepicker-virt" style="text-align:center;" disabled />
                                                        <span class="input-group-addon" style="cursor:pointer;" onclick="intercambiarFechasVirt()" title="Intercambiar"><i class="glyphicon glyphicon-transfer"></i></span>
                                                        <span class="input-group-addon alert-info">Hasta</span>
                                                        <input type="text" id="Fec_FinM_Virt" name="Fec_FinM_Virt" class="form-control datepicker-virt" style="text-align:center;" disabled />
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 col-sm-3">
                                                    <label class="sv-group-day" title="Consolidar únicamente los manifiestos por fecha">
                                                        <input type="checkbox" id="Agrupar_Man_Dia_Virt" value="1" />
                                                        Agrupar Manif.
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Grupal -->
                            <div id="svPanelGrupal" style="display:none;">
                                <form name="searchSaldosVirtualGrupal" id="searchSaldosVirtualGrupal" class="form-horizontal normal sv-filtros" onsubmit="return false;">
                                    <div class="row" style="font-size:12px;">
                                        <div class="col-xs-12 col-sm-3" style="width:190px;">
                                            <label class="control-label label-xs" style="display:inline-block;margin-right:4px;">Período:</label>
                                            <select id="Pec_Cod_VirtG" class="form-control input-xs" style="display:inline-block;width:95px;text-align:center;" onchange="cambiarPeriodoVirtG()">
                                                <?php
                                                foreach ($periodos as $p) {
                                                    $year = substr($p['Pec_Fei'], 0, 4);
                                                    $selectedVG = ($year == date('Y')) ? 'selected' : '';
                                                    echo "<option data-inicio='$p[Pec_Fei]' data-fin='$p[Pec_Fef]' value='$p[Pec_Cod]' $selectedVG>$year</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-xs-12 col-sm-2" style="width:200px;">
                                            <label class="control-label label-xs" style="display:inline-block;margin-right:4px;">Mes:</label>
                                            <select id="Mes_Cod_VirtG" class="form-control input-xs" style="display:inline-block;width:100px;text-align:center;" onchange="cambiarPeriodoVirtG()">
                                                <option value="00" selected>&lt;&lt; TODOS &gt;&gt;</option>
                                                <option value="PF">&lt;&lt; Por Fechas &gt;&gt;</option>
                                                <option value="01">Enero</option>
                                                <option value="02">Febrero</option>
                                                <option value="03">Marzo</option>
                                                <option value="04">Abril</option>
                                                <option value="05">Mayo</option>
                                                <option value="06">Junio</option>
                                                <option value="07">Julio</option>
                                                <option value="08">Agosto</option>
                                                <option value="09">Septiembre</option>
                                                <option value="10">Octubre</option>
                                                <option value="11">Noviembre</option>
                                                <option value="12">Diciembre</option>
                                            </select>
                                        </div>
                                        <div class="col-xs-12 col-sm-5">
                                            <label class="control-label label-xs" style="display:inline-block;margin-right:4px;">Fecha:</label>
                                            <span class="input-group-addon alert-info" style="display:inline-block;width:48px;padding:3px 5px;">Desde</span>
                                            <input type="text" id="Fec_IniM_VirtG" class="form-control datepicker-virtg" style="display:inline-block;width:100px;text-align:center;" disabled />
                                            <span class="input-group-addon alert-info" style="display:inline-block;width:48px;padding:3px 5px;">Hasta</span>
                                            <input type="text" id="Fec_FinM_VirtG" class="form-control datepicker-virtg" style="display:inline-block;width:100px;text-align:center;" disabled />
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top:6px;">
                                        <div class="col-sm-6">
                                            <div class="input-group input-group-xs">
                                                <span class="input-group-addon">Planta</span>
                                                <input type="text" id="searchPlantaVirtG" class="form-control" placeholder="Filtrar por nombre de planta..." />
                                            </div>
                                        </div>
                                        <div class="col-sm-6 sv-actions" style="justify-content:flex-start;">
                                            <button type="button" class="btn btn-success btn-xs sv-btn-search" onclick="buscarSaldosVirtualGrupal()"><span class="glyphicon glyphicon-search"></span> Buscar</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div id="svResumenTipos" class="sv-resumen" aria-live="polite">
                        <div class="sv-resumen-item is-transfer" title="Cantidad de transferencias / depósitos">
                            <span class="sv-resumen-label">Transferencias</span>
                            <span class="sv-resumen-val" id="svCntTransfer">0</span>
                        </div>
                        <div class="sv-resumen-item is-retention" title="Cantidad de retenciones">
                            <span class="sv-resumen-label">Retenciones</span>
                            <span class="sv-resumen-val" id="svCntRetention">0</span>
                        </div>
                        <div class="sv-resumen-item is-manifest" title="Cantidad de manifiestos">
                            <span class="sv-resumen-label">Manifiestos</span>
                            <span class="sv-resumen-val" id="svCntManifest">0</span>
                        </div>
                        <div class="sv-resumen-item is-total" title="Total de registros (sin saldo inicial)">
                            <span class="sv-resumen-label">Total</span>
                            <span class="sv-resumen-val" id="svCntTotal">0</span>
                        </div>
                    </div>
                    <div id="svPanelIndividualGrid" class="sv-grid-wrap">
                        <table id="gridSaldosVirtual"></table>
                        <div id="pagerSaldosVirtual"></div>
                    </div>
                    <div id="svPanelGrupalGrid" class="sv-grid-wrap" style="display:none;">
                        <table id="gridSaldosVirtualGrupal"></table>
                        <div id="pagerSaldosVirtualGrupal"></div>
                    </div>
                </div>
                
                </div> <!-- Fin tab-content -->
            </div>
        </div>

        <!-- Dialogo Buscar Planta (jQuery UI) -->
        <div id="plantaDialog" title="Buscar Planta" style="display: none;">
            <form class="form-horizontal normal">
                <div class="row">
                    <div class="col-sm-12">
                        <fieldset class="exa-fieldset">
                            <legend class="Titulos2">Criterios de Búsqueda</legend>
                            <div class="form-group">
                                <label class="col-xs-2 control-label label-xs">Buscar:</label>
                                <div class="col-xs-7">
                                    <input type="text" id="searchPlantaInput" class="form-control input-xs" placeholder="Ingrese nombre de planta o ciudad...">
                                </div>
                                <div class="col-xs-2">
                                    <button class="btn btn-success btn-xs btn-block" type="button" onclick="buscarPlantas()">
                                        <span class="glyphicon glyphicon-search"></span> Buscar
                                    </button>
                                </div>
                            </div>
                        </fieldset>
                        <div style="margin-top: 10px;">
                            <table id="gridPlantas"></table>
                            <div id="pagerPlantas"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <script src="../VALIDACIONES/man_est_cuenta_1.0.js?e=41"></script>
        <script type="text/javascript" src="../../framework/jquery/jquery.plugins/MaskedInput/jquery.maskedinput.1.4.1.min.js"></script>
        <script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.js?x=1"></script>
        <script type="text/javascript" src="../../framework/jquery/validate/jquery.validate.min.js"></script>
        <script type="text/javascript" src="../../framework/plugins/moment.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                <?php 
                // Verificar si se obtuvo un registro válido y si tiene planta asignada
                if ($cliente_manifiesto && isset($cliente_manifiesto['Pla_Cod']) && !empty($cliente_manifiesto['Pla_Cod'])) { 
                ?>
                    // Pre-cargar datos del usuario/planta
                    $("#Pla_Cod").val(<?php echo json_encode($cliente_manifiesto['Pla_Cod']); ?>);
                    $("#Pla_Nom").val(<?php echo json_encode($cliente_manifiesto['Pla_Nom']); ?>);
                    $("#Cli_Cod").val(<?php echo json_encode($cliente_manifiesto['Cli_Cod']); ?>);
                    $("#Cli_Nom").val(<?php echo json_encode($cliente_manifiesto['nombre']); ?>);
                    $("#Pla_Cod_Cons").val(<?php echo json_encode($cliente_manifiesto['Pla_Cod']); ?>);
                    $("#Pla_Nom_Cons").val(<?php echo json_encode($cliente_manifiesto['Pla_Nom']); ?>);
                    $("#Cli_Cod_Cons").val(<?php echo json_encode($cliente_manifiesto['Cli_Cod']); ?>);
                    $("#Cli_Nom_Cons").val(<?php echo json_encode($cliente_manifiesto['nombre']); ?>);
                    $("#Pla_Cod_Virt").val(<?php echo json_encode($cliente_manifiesto['Pla_Cod']); ?>);
                    $("#Pla_Nom_Virt").val(<?php echo json_encode($cliente_manifiesto['Pla_Nom']); ?>);
                    $("#Cli_Cod_Virt").val(<?php echo json_encode($cliente_manifiesto['Cli_Cod']); ?>);
                    $("#Cli_Nom_Virt").val(<?php echo json_encode($cliente_manifiesto['nombre']); ?>);
                    
                    // Ocultar botones de búsqueda de planta
                    $("#btnBuscarPlanta").hide();
                    $("#btnLimpiarPlanta").hide();
                    // Ocultar el contenedor de botones para que el input ocupe todo el ancho
                    $("#btnBuscarPlanta").closest(".input-group-btn").hide();
                    $("#btnBuscarPlantaCons").hide();
                    $("#btnLimpiarPlantaCons").hide();
                    $("#btnBuscarPlantaCons").closest(".input-group-btn").hide();
                    $("#svPlantaBtns").hide();
                <?php } ?>
                
                // Inicializar fechas según periodo/mes seleccionado
                cambiarPeriodo();
                cambiarPeriodoCons();
                if (typeof cambiarPeriodoVirt === 'function') {
                    cambiarPeriodoVirt();
                }
                if (typeof cambiarPeriodoVirtG === 'function' && $("#Pec_Cod_VirtG").length) {
                    cambiarPeriodoVirtG();
                }
                if ($("#Pec_Cod_Grupal").length) {
                    cambiarPeriodoGrupal();
                }
                if ($("#Pec_Cod_Comp").length) {
                    cambiarPeriodoComp();
                }

                // Planta (Comparación): listado largo — Chosen con búsqueda al escribir (inicializar al mostrar la pestaña por ancho correcto)
                var plaCompChosenListo = false;
                function inicializarChosenPlantaComparacion() {
                    if (plaCompChosenListo || !$("#Pla_Cod_Comp").length) {
                        return;
                    }
                    plaCompChosenListo = true;
                    $("#Pla_Cod_Comp").chosen({
                        width: "100%",
                        search_contains: true,
                        no_results_text: "No se encontró:",
                        allow_single_deselect: true
                    });
                }
                $('a[href="#tabComparacion"]').on("shown.bs.tab", function() {
                    inicializarChosenPlantaComparacion();
                });

                $(document).on("change", "#comp_todas_plantas", function() {
                    var on = $(this).is(":checked");
                    var $s = $("#Pla_Cod_Comp");
                    $s.prop("disabled", on);
                    if ($s.next(".chosen-container").length) {
                        $s.trigger("chosen:updated");
                    }
                });
            });
        </script>

        <?php
        // Cerrado y liberacion de las conexiones
            $obBD_con1->liberar();
            $obBD_conexion->cerrar();
        ?>
    </BODY>
</HTML>
